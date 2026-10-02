<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WP_REST_Response;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecFetcher;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\ProxyAllowlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Try-it playground proxy (Pro, opt-in — PRD F5.3).
 *
 * Off by default. Forwards a reader's "Try it" request to a host allowlisted on
 * the API reference the request came from, after the full SSRF guard, with
 * WordPress cookies/nonces stripped from the outbound call, a hard timeout, a
 * response-size cap, and a per-IP rate limit. Nothing about request or response
 * bodies is logged.
 *
 * The switch and the allowlist are per reference (`ref` query arg → its own
 * meta), so enabling the proxy for one API never opens it for another.
 *
 * Wire-compatible with Scalar's proxy: it calls
 * `proxyUrl?ref=<id>&scalar_url=<target>` and forwards the real
 * method/headers/body.
 */
class ApiProxy extends BaseAPI {
	const TIMEOUT        = 15;
	const MAX_BYTES      = 2097152; // 2 MB
	const RATE_LIMIT     = 60;      // requests…
	const RATE_WINDOW    = 60;      // …per this many seconds, per IP.
	// A second ceiling for the whole reference: the per-IP budget is only as
	// good as the caller's willingness to keep one address, so this bounds total
	// egress a single API can be made to generate regardless of source.
	const RATE_LIMIT_REF = 600;
	const MAX_REDIRECTS  = 3;

	/**
	 * Request headers we never forward (auth/identity of the WP session, hop
	 * headers). Everything else the reader set (Authorization, X-Api-Key, …)
	 * is passed through so real auth flows work.
	 *
	 * @var string[]
	 */
	const STRIP_HEADERS = [
		'cookie',
		'x-wp-nonce',
		'host',
		'content-length',
		'connection',
		'x-forwarded-for',
		'x-real-ip',
	];

	public function register() {
		register_rest_route(
			$this->get_namespace(),
			'/api-ref/proxy',
			[
				'methods'             => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
				'callback'            => [ $this, 'proxy' ],
				'permission_callback' => '__return_true' // Reader-facing; gated inside.
			]
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|\WP_Error
	 */
	public function proxy( WP_REST_Request $request ) {
		$ref_id = (int) $request->get_param( 'ref' );
		$ref    = $ref_id ? get_post( $ref_id ) : null;

		if ( ! $ref || 'betterdocs_api_ref' !== $ref->post_type ) {
			return $this->deny( __( 'Unknown API reference.', 'betterdocs-pro' ), 400 );
		}

		if ( ! ProxyAllowlist::enabled_for( $ref_id ) ) {
			return $this->deny( __( 'The Try-it proxy is turned off for this API reference.', 'betterdocs-pro' ), 403 );
		}

		$target = $request->get_param( 'scalar_url' );
		if ( ! $target ) {
			$target = $request->get_param( 'url' );
		}

		if ( ! $target ) {
			return $this->deny( __( 'No target URL.', 'betterdocs-pro' ), 400 );
		}

		// Proof the caller loaded a page that actually offers this reference's
		// playground. Without it the route is a general-purpose server-side
		// request primitive for anyone who knows the URL.
		if ( ! ProxyAllowlist::verify_token( $ref_id, (string) $request->get_param( 'token' ) ) ) {
			return $this->deny( __( 'This Try-it session has expired. Reload the page and try again.', 'betterdocs-pro' ), 403 );
		}

		if ( $this->rate_limited( $ref_id ) ) {
			return $this->deny( __( 'Too many requests. Slow down.', 'betterdocs-pro' ), 429 );
		}

		$response = $this->forward( $request, $target, $ref_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = (string) wp_remote_retrieve_body( $response );

		if ( strlen( $body ) > self::MAX_BYTES ) {
			$body = substr( $body, 0, self::MAX_BYTES );
		}

		$out = new WP_REST_Response( null, (int) wp_remote_retrieve_response_code( $response ) );

		// The upstream Content-Type is NOT echoed back. This endpoint lives on
		// the site's own origin, so passing through `text/html` (or anything a
		// browser will sniff into markup) makes an allowlisted host — or any
		// path on it that reflects input — able to run script with the site's
		// cookies and nonces. The playground reads the body as text and renders
		// it itself, so it never needs a meaningful type.
		$out->header( 'Content-Type', 'text/plain; charset=utf-8' );
		$out->header( 'X-Content-Type-Options', 'nosniff' );
		$out->header( 'Content-Disposition', 'attachment' );
		$out->header( 'Content-Security-Policy', "default-src 'none'; sandbox" );
		$out->header( 'X-BetterDocs-Proxied', '1' );
		// The real type is still useful to the client, just not as a directive
		// the browser will act on.
		$out->header( 'X-BetterDocs-Upstream-Type', (string) wp_remote_retrieve_header( $response, 'content-type' ) );

		// Emit the raw upstream body verbatim. Registered per request and removed
		// again once it has served, so a long-running process can't accumulate
		// closures that fire on later requests.
		$emit = function ( $served, $result ) use ( $body, &$emit ) {
			remove_filter( 'rest_pre_serve_request', $emit, 10 );
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- upstream API payload, passed through verbatim by design; served as text/plain + nosniff + attachment.
			return true;
		};

		add_filter( 'rest_pre_serve_request', $emit, 10, 2 );

		return $out;
	}

	/**
	 * Forward the reader's request, following redirects MANUALLY so every hop is
	 * re-checked.
	 *
	 * The previous implementation handed `redirection => 3` to wp_remote_request
	 * and let cURL follow internally. The guard and the allowlist then applied
	 * only to the first URL: an allowlisted host with an open redirect
	 * (`…/redirect?to=http://169.254.169.254/`) was enough to reach cloud
	 * metadata or anything else on the internal network, with the response
	 * handed back verbatim. Each hop now re-runs BOTH the SSRF guard and the
	 * per-reference allowlist, and the connection is pinned to the address that
	 * was vetted so DNS can't change underneath us.
	 *
	 * @param WP_REST_Request $request
	 * @param string          $target
	 * @param int             $ref_id
	 * @return array|\WP_Error
	 */
	protected function forward( WP_REST_Request $request, $target, $ref_id ) {
		$fetcher = new SpecFetcher();
		$method  = $request->get_method();
		$hops    = 0;

		while ( true ) {
			// SSRF guard (scheme lock + private/reserved/loopback rejection),
			// returning the address to pin.
			$pin = $fetcher->resolve_target( $target );
			if ( is_wp_error( $pin ) ) {
				return $this->deny( $pin->get_error_message(), 403 );
			}

			// Owner allowlist — the target host must be explicitly permitted on
			// this reference. The message names the host so the fix is obvious.
			if ( ! ProxyAllowlist::allows( $ref_id, $target ) ) {
				return $this->deny(
					sprintf(
						/* translators: %s: the API host that was refused. */
						__( '"%s" is not on this API reference\'s proxy allowlist. Add it under Allowed API hosts when editing the reference.', 'betterdocs-pro' ),
						(string) wp_parse_url( $target, PHP_URL_HOST )
					),
					403
				);
			}

			$response = $fetcher->request_guarded(
				$target,
				[
					'method'      => $method,
					'timeout'     => self::TIMEOUT,
					'redirection' => 0, // Followed manually below.
					'headers'     => $this->outbound_headers( $request ),
					'body'        => $request->get_body(),
					'sslverify'   => true
				],
				$pin,
				self::MAX_BYTES
			);

			if ( is_wp_error( $response ) ) {
				return $this->deny( $response->get_error_message(), 502 );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( ! in_array( $code, [ 301, 302, 303, 307, 308 ], true ) ) {
				return $response;
			}

			if ( ++$hops > self::MAX_REDIRECTS ) {
				return $this->deny( __( 'Too many redirects from the upstream API.', 'betterdocs-pro' ), 502 );
			}

			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( ! $location ) {
				return $this->deny( __( 'The upstream API redirected without a location.', 'betterdocs-pro' ), 502 );
			}

			$target = $fetcher->resolve_redirect( $location, $target );

			// 303 (and 301/302 in practice) turn the follow-up into a GET.
			if ( 303 === $code ) {
				$method = 'GET';
			}
		}
	}

	/**
	 * Headers to forward: everything the reader set except the strip-list.
	 *
	 * @param WP_REST_Request $request
	 * @return array<string,string>
	 */
	protected function outbound_headers( WP_REST_Request $request ) {
		$headers = [];

		foreach ( $request->get_headers() as $name => $values ) {
			$key = strtolower( str_replace( '_', '-', $name ) );

			if ( in_array( $key, self::STRIP_HEADERS, true ) ) {
				continue;
			}

			$headers[ $key ] = is_array( $values ) ? implode( ', ', $values ) : $values;
		}

		return $headers;
	}

	/**
	 * Fixed-window rate limit, counted per IP *and* per reference.
	 *
	 * Two changes from the original: the increment is atomic, and the reference
	 * gets its own budget.
	 *
	 * `get_transient()` then `set_transient()` is a read-modify-write — a burst
	 * of concurrent requests all read the same count and all write count+1, so
	 * the limit could be overrun by however many workers were in flight. Object
	 * caches expose an atomic incr(); the option-backed fallback uses the
	 * autoload-free option row plus a bounded compare-and-set retry, which is
	 * the closest thing available without a dedicated table.
	 *
	 * The per-IP budget alone is trivially defeated by rotating source
	 * addresses, so a second counter bounds total egress for one reference
	 * regardless of who is asking.
	 *
	 * @param int $ref_id
	 * @return bool True when over the limit.
	 */
	protected function rate_limited( $ref_id ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$buckets = [
			// Per-IP.
			[ 'bd_api_proxy_ip_' . hash( 'sha256', $ip . wp_salt() ), self::RATE_LIMIT ],
			// Per-reference, for the whole site.
			[ 'bd_api_proxy_ref_' . (int) $ref_id, self::RATE_LIMIT_REF ],
		];

		foreach ( $buckets as list( $key, $limit ) ) {
			if ( $this->bump( $key, self::RATE_WINDOW ) > $limit ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Atomically increment a fixed-window counter and return the new value.
	 *
	 * @param string $key
	 * @param int    $window Seconds.
	 * @return int
	 */
	protected function bump( $key, $window ) {
		// Bucket the key by window so an expiring counter can never be read as a
		// fresh one mid-window.
		$bucket = (int) floor( time() / $window );
		$slot   = $key . '_' . $bucket;

		if ( wp_using_ext_object_cache() ) {
			$group = 'betterdocs_api_proxy';

			// add() only succeeds when the key is absent, so exactly one caller
			// seeds the window and everyone else increments it. incr() is atomic
			// in every persistent backend WordPress supports.
			wp_cache_add( $slot, 0, $group, $window * 2 );

			$count = wp_cache_incr( $slot, 1, $group );

			// A backend that dropped the key between add() and incr() returns
			// false; treat that as the first request of the window.
			return false === $count ? 1 : (int) $count;
		}

		// No persistent object cache. Options carry a UNIQUE index on
		// option_name, so add_option() is an atomic insert — exactly one caller
		// can seed the window. Subsequent bumps use a bounded compare-and-set:
		// update_option() returns false when the row did not change, which is
		// the signal that another worker got there first and we should re-read.
		$option = '_bd_api_rl_' . md5( $slot );

		// Drop the previous window's row while seeding this one, so the fallback
		// does not accumulate a row per IP per window (transients would expire on
		// their own, but they offer no atomic increment).
		$previous = '_bd_api_rl_' . md5( $key . '_' . ( $bucket - 1 ) );

		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			$current = (int) get_option( $option, 0 );

			if ( 0 === $current ) {
				if ( add_option( $option, 1, '', 'no' ) ) {
					delete_option( $previous );
					return 1;
				}
				continue; // Someone else seeded it; re-read and increment.
			}

			if ( update_option( $option, $current + 1, 'no' ) ) {
				return $current + 1;
			}

			// Force a fresh read rather than the cached value we just lost on.
			wp_cache_delete( $option, 'options' );
		}

		// Sustained contention means sustained traffic — fail closed.
		return PHP_INT_MAX;
	}

	/**
	 * @param string $message
	 * @param int    $status
	 * @return \WP_Error
	 */
	protected function deny( $message, $status ) {
		return new \WP_Error( 'betterdocs_api_proxy_denied', $message, [ 'status' => $status ] );
	}
}
