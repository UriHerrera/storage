<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SSRF-hardened remote OpenAPI spec fetcher (PRD §7.4).
 *
 * Every hop is validated: scheme locked to http(s), the resolved host must
 * not be a private/reserved/loopback IP, redirects are followed manually
 * (≤3) with each Location re-validated, and the response body is size-capped.
 * Sends conditional headers so unchanged specs cost a single 304.
 *
 * Validation resolves the host and then PINS the connection to the address it
 * vetted (see request_guarded()). Without that, validating and fetching are two
 * independent DNS lookups and an attacker controlling the zone can answer the
 * first with a public IP and the second with 127.0.0.1 — the classic
 * DNS-rebinding TOCTOU. This class is the single guarded egress path for the
 * feature; ApiProxy uses it too rather than calling wp_remote_request itself.
 */
class SpecFetcher {
	const MAX_REDIRECTS = 3;
	const TIMEOUT       = 15;

	/**
	 * @param string $url
	 * @param string $etag
	 * @param string $last_modified
	 * @param int    $max_bytes
	 *
	 * @return array|WP_Error {
	 *     @type bool   $not_modified True on a 304 (body absent).
	 *     @type string $body
	 *     @type string $etag
	 *     @type string $last_modified
	 *     @type string $format 'json'|'yaml' (from the final URL / content-type)
	 * }
	 */
	public function fetch( $url, $etag = '', $last_modified = '', $max_bytes = 0 ) {
		$hops = 0;

		while ( true ) {
			$target = $this->resolve_target( $url );
			if ( is_wp_error( $target ) ) {
				return $target;
			}

			$headers = [ 'Accept' => 'application/json, application/yaml, text/yaml, text/plain, */*' ];
			if ( $etag ) {
				$headers['If-None-Match'] = $etag;
			}
			if ( $last_modified ) {
				$headers['If-Modified-Since'] = $last_modified;
			}

			$response = $this->request_guarded(
				$url,
				[
					'method'      => 'GET',
					'timeout'     => self::TIMEOUT,
					'redirection' => 0, // We follow manually so every hop is re-validated.
					'headers'     => $headers,
					'sslverify'   => true
				],
				$target,
				$max_bytes
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'betterdocs_api_sync_http', $response->get_error_message() );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( 304 === $code ) {
				return [ 'not_modified' => true ];
			}

			if ( in_array( $code, [ 301, 302, 303, 307, 308 ], true ) ) {
				if ( ++$hops > self::MAX_REDIRECTS ) {
					return new WP_Error( 'betterdocs_api_sync_redirects', __( 'Too many redirects while fetching the spec.', 'betterdocs-pro' ) );
				}

				$location = wp_remote_retrieve_header( $response, 'location' );
				if ( ! $location ) {
					return new WP_Error( 'betterdocs_api_sync_redirect_noloc', __( 'The server redirected without a location.', 'betterdocs-pro' ) );
				}

				// Resolve relative redirects against the current URL, then loop
				// (the top of the loop re-runs the full SSRF guard).
				$url = $this->resolve_redirect( $location, $url );
				continue;
			}

			if ( 200 !== $code ) {
				return new WP_Error(
					'betterdocs_api_sync_status',
					sprintf(
						/* translators: %d: HTTP status code */
						__( 'The spec URL returned HTTP %d.', 'betterdocs-pro' ),
						$code
					)
				);
			}

			$body = (string) wp_remote_retrieve_body( $response );

			// request_guarded() caps the transfer at $max_bytes + 1 so an oversized
			// body is aborted mid-stream rather than buffered whole and trimmed
			// afterwards — the previous order let a multi-GB response OOM the
			// worker before this check could ever run. Landing on the +1 byte is
			// therefore the "it was too big" signal.
			if ( $max_bytes > 0 && strlen( $body ) > $max_bytes ) {
				return new WP_Error(
					'betterdocs_api_sync_too_large',
					sprintf(
						/* translators: %s: maximum allowed size */
						__( 'The fetched spec exceeds the %s limit.', 'betterdocs-pro' ),
						size_format( $max_bytes )
					)
				);
			}

			$content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );

			return [
				'not_modified'  => false,
				'body'          => $body,
				'etag'          => (string) wp_remote_retrieve_header( $response, 'etag' ),
				'last_modified' => (string) wp_remote_retrieve_header( $response, 'last-modified' ),
				'format'        => $this->sniff_format( $url, $content_type, $body )
			];
		}
	}

	/**
	 * Reject anything that isn't a public http(s) URL. Blocks private, reserved
	 * and loopback ranges for every resolved A/AAAA record.
	 *
	 * @param string $url
	 * @return true|WP_Error
	 */
	public function validate_url( $url ) {
		$target = $this->resolve_target( $url );

		return is_wp_error( $target ) ? $target : true;
	}

	/**
	 * Validate a URL and return the address the connection must be pinned to.
	 *
	 * Splitting this out of validate_url() is the whole point: callers need the
	 * IP that was actually vetted, so the subsequent request can be forced onto
	 * it instead of performing a second, independent DNS lookup an attacker can
	 * answer differently.
	 *
	 * @param string $url
	 * @return array{host:string,port:int,ip:string,pin:bool}|WP_Error
	 */
	public function resolve_target( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
			return new WP_Error( 'betterdocs_api_sync_scheme', __( 'Only http(s) spec URLs are allowed.', 'betterdocs-pro' ) );
		}

		if ( empty( $parts['host'] ) ) {
			return new WP_Error( 'betterdocs_api_sync_host', __( 'The spec URL has no host.', 'betterdocs-pro' ) );
		}

		$scheme = strtolower( $parts['scheme'] );
		$host   = $parts['host'];
		$port   = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );

		/**
		 * Escape hatch for site owners who intentionally sync from an internal
		 * host (e.g. a spec server on a private LAN). Off by default.
		 *
		 * This disables the SSRF guard for the host it is engaged for, so it is
		 * logged when it fires — an allow-everything callback is otherwise an
		 * invisible bypass of every check below.
		 */
		if ( apply_filters( 'betterdocs_api_ref_ssrf_allow', false, $host, $url ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf( '[BetterDocs] SSRF guard bypassed via betterdocs_api_ref_ssrf_allow for host "%s".', $host )
				);
			}

			// Not pinned: the owner has taken responsibility for this host, and
			// it may well resolve to an address the guard would reject.
			return [ 'host' => $host, 'port' => $port, 'ip' => '', 'pin' => false ];
		}

		$ips = $this->resolve_ips( $host );

		if ( empty( $ips ) ) {
			return new WP_Error( 'betterdocs_api_sync_dns', __( 'The spec URL host could not be resolved.', 'betterdocs-pro' ) );
		}

		// EVERY record must be public: pinning one good address is no defence if
		// another record would have been used on a retry.
		foreach ( $ips as $ip ) {
			if ( ! $this->is_public_ip( $ip ) ) {
				return new WP_Error(
					'betterdocs_api_sync_private',
					__( 'The spec URL resolves to a private or reserved address, which is not allowed.', 'betterdocs-pro' )
				);
			}
		}

		return [
			'host' => trim( $host, '[]' ),
			'port' => $port,
			'ip'   => reset( $ips ),
			'pin'  => true
		];
	}

	/**
	 * Run a request with the connection pinned to the pre-validated IP and the
	 * response capped during transfer.
	 *
	 * Pinning uses cURL's CURLOPT_RESOLVE, which pre-seeds the DNS cache for
	 * host:port. The URL keeps its hostname, so SNI and certificate validation
	 * still work — unlike swapping the host for an IP and sending a Host header.
	 *
	 * `limit_response_size` is honoured by both WP transports and aborts the
	 * transfer once the cap is passed, which is what makes the size limit a real
	 * defence rather than a post-hoc trim of an already-buffered body.
	 *
	 * @param string $url
	 * @param array  $args      wp_remote_request() args.
	 * @param array  $target    From resolve_target().
	 * @param int    $max_bytes 0 for no cap.
	 * @return array|WP_Error
	 */
	public function request_guarded( $url, array $args, array $target, $max_bytes = 0 ) {
		if ( $max_bytes > 0 ) {
			// +1 so a body landing exactly on the cap is still distinguishable
			// from one that was truncated.
			$args['limit_response_size'] = $max_bytes + 1;
		}

		$pin = null;

		if ( ! empty( $target['pin'] ) && ! empty( $target['ip'] ) ) {
			$resolve = sprintf( '%s:%d:%s', $target['host'], $target['port'], $target['ip'] );

			$pin = function ( $handle ) use ( $resolve ) {
				curl_setopt( $handle, CURLOPT_RESOLVE, [ $resolve ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			};

			add_action( 'http_api_curl', $pin, 10, 1 );
		}

		try {
			return wp_remote_request( $url, $args );
		} finally {
			if ( $pin ) {
				remove_action( 'http_api_curl', $pin, 10 );
			}
		}
	}

	/**
	 * Resolve a host's IPv4 + IPv6 addresses. A bare IP host short-circuits.
	 *
	 * @param string $host
	 * @return string[]
	 */
	protected function resolve_ips( $host ) {
		$host = trim( $host, '[]' ); // IPv6 literal.

		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return [ $host ];
		}

		$ips = [];

		$v4 = @gethostbynamel( $host ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( is_array( $v4 ) ) {
			$ips = array_merge( $ips, $v4 );
		}

		if ( function_exists( 'dns_get_record' ) ) {
			$aaaa = @dns_get_record( $host, DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_array( $aaaa ) ) {
				foreach ( $aaaa as $rec ) {
					if ( ! empty( $rec['ipv6'] ) ) {
						$ips[] = $rec['ipv6'];
					}
				}
			}
		}

		return array_unique( $ips );
	}

	/**
	 * Public-routable check: rejects private, reserved and loopback ranges
	 * (covers RFC1918, link-local, loopback, ULA, etc. via PHP's filter flags).
	 *
	 * PHP's flags judge an IPv6 address on its own textual form, so the embedded
	 * IPv4 formats slip through: `::ffff:127.0.0.1` and `::127.0.0.1` both
	 * validate as "public" IPv6 while routing to loopback, and NAT64's
	 * `64:ff9b::/96` maps an arbitrary v4 address (including 169.254.169.254)
	 * into v6 space. Unwrap the embedded v4 and judge THAT, and reject the
	 * translation prefixes outright.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function is_public_ip( $ip ) {
		$ip = trim( (string) $ip, '[]' );

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$packed = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( false === $packed || 16 !== strlen( $packed ) ) {
				return false;
			}

			// NAT64 (64:ff9b::/96) and the local-use variant (64:ff9b:1::/48):
			// both carry a v4 destination we would otherwise never inspect.
			if ( 0 === strncmp( $packed, "\x00\x64\xff\x9b", 4 ) ) {
				return false;
			}

			// IPv4-mapped (::ffff:0:0/96) and deprecated IPv4-compatible
			// (::/96) — first 12 bytes are zero, or 10 zeros + 0xffff.
			$v4 = '';
			if ( 0 === strncmp( $packed, str_repeat( "\x00", 10 ) . "\xff\xff", 12 ) ) {
				$v4 = inet_ntop( substr( $packed, 12 ) );
			} elseif ( 0 === strncmp( $packed, str_repeat( "\x00", 12 ), 12 ) && "\x00\x00\x00\x00" !== substr( $packed, 12 ) ) {
				$v4 = inet_ntop( substr( $packed, 12 ) );
			}

			if ( $v4 ) {
				return $this->is_public_ip( $v4 );
			}
		}

		return (bool) filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	/**
	 * @param string $location
	 * @param string $base
	 * @return string
	 */
	public function resolve_redirect( $location, $base ) {
		if ( wp_parse_url( $location, PHP_URL_SCHEME ) ) {
			return $location;
		}

		$b = wp_parse_url( $base );
		$scheme = $b['scheme'] ?? 'https';
		$host   = $b['host'] ?? '';
		$port   = isset( $b['port'] ) ? ':' . $b['port'] : '';

		if ( 0 === strpos( $location, '/' ) ) {
			return "{$scheme}://{$host}{$port}{$location}";
		}

		$path = isset( $b['path'] ) ? preg_replace( '#/[^/]*$#', '/', $b['path'] ) : '/';
		return "{$scheme}://{$host}{$port}{$path}{$location}";
	}

	/**
	 * @param string $url
	 * @param string $content_type
	 * @param string $body
	 * @return string 'json'|'yaml'
	 */
	protected function sniff_format( $url, $content_type, $body ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( preg_match( '/\.(ya?ml)$/i', $path ) ) {
			return 'yaml';
		}
		if ( preg_match( '/\.json$/i', $path ) ) {
			return 'json';
		}
		if ( false !== stripos( $content_type, 'yaml' ) ) {
			return 'yaml';
		}
		if ( false !== stripos( $content_type, 'json' ) ) {
			return 'json';
		}

		// Fall back to a content sniff (JSON starts with { or [).
		return ( '{' === substr( ltrim( $body ), 0, 1 ) ) ? 'json' : 'yaml';
	}
}
