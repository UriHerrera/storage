<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Link Health scanner (Advanced Analytics v1.0).
 *
 * A weekly Action Scheduler job (and a manual "scan now") walks published docs
 * in chunks, extracts their links, checks each with wp_safe_remote_head, and records
 * the result in {prefix}betterdocs_analytics_links. Work is drained chunk by
 * chunk (re-enqueue) so large knowledge bases never time out.
 *
 * The per-URL check is routed through the `betterdocs_analytics_link_status`
 * filter so hosts (and tests) can short-circuit the network call.
 */
class AnalyticsLinkScanner {
	const START_HOOK = 'betterdocs_analytics_link_scan_start';
	const RUN_HOOK   = 'betterdocs_analytics_link_scan_run';
	const OFFSET_OPT = 'betterdocs_analytics_link_scan_offset';
	const LAST_OPT   = 'betterdocs_analytics_link_scan_last';
	const CHUNK      = 20;

	/**
	 * Redirect hops followed per link. Each hop is revalidated against the SSRF
	 * rules before it is requested.
	 */
	const MAX_REDIRECTS = 3;

	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::START_HOOK, [ $this, 'start_scan' ] );
		add_action( self::RUN_HOOK, [ $this, 'run_chunk' ] );
	}

	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( self::START_HOOK ) ) {
			as_schedule_recurring_action( time() + DAY_IN_SECONDS, WEEK_IN_SECONDS, self::START_HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Begin a fresh full scan: reset the cursor and enqueue the first chunk.
	 * Used by the weekly schedule and the "scan now" REST trigger.
	 */
	public function start_scan() {
		// Don't stack a second walker on top of an in-progress scan: a rapid
		// double "Scan Now" would otherwise reset the offset under a running
		// chunk and interleave offset writes (wasteful, and can skip ranges).
		if ( function_exists( 'as_next_scheduled_action' ) && false !== as_next_scheduled_action( self::RUN_HOOK, [], 'betterdocs' ) ) {
			return;
		}
		update_option( self::OFFSET_OPT, 0 );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::RUN_HOOK, [], 'betterdocs' );
		} else {
			$this->run_chunk();
		}
	}

	/**
	 * Process one chunk of docs, then re-enqueue until the KB is fully scanned.
	 */
	public function run_chunk() {
		$offset = (int) get_option( self::OFFSET_OPT, 0 );

		$ids = get_posts(
			[
				'post_type'      => 'docs',
				'post_status'    => 'publish',
				'posts_per_page' => self::CHUNK,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true
			]
		);

		if ( empty( $ids ) ) {
			delete_option( self::OFFSET_OPT );
			update_option( self::LAST_OPT, time() );
			return;
		}

		foreach ( $ids as $post_id ) {
			$this->scan_post( (int) $post_id );
		}

		update_option( self::OFFSET_OPT, $offset + count( $ids ) );

		if ( count( $ids ) >= self::CHUNK && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::RUN_HOOK, [], 'betterdocs' );
		} elseif ( count( $ids ) < self::CHUNK ) {
			delete_option( self::OFFSET_OPT );
			update_option( self::LAST_OPT, time() );
		}
	}

	/**
	 * Re-scan a single doc: replace its link rows with a freshly checked set.
	 */
	public function scan_post( $post_id ) {
		global $wpdb;

		$post_id = (int) $post_id;
		$content = (string) get_post_field( 'post_content', $post_id );
		$links   = $this->extract_links( $content );
		$table   = $wpdb->prefix . 'betterdocs_analytics_links';
		$now     = current_time( 'mysql', true );

		// Replace this doc's links so removed links don't linger.
		$wpdb->delete( $table, [ 'post_id' => $post_id ], [ '%d' ] );

		foreach ( $links as $url => $type ) {
			$status    = 0;
			$is_broken = 0;

			if ( $type !== 'anchor' ) {
				$result    = $this->check_url( $url );
				$status    = (int) $result['status'];
				$is_broken = $result['broken'] ? 1 : 0;
			}

			$wpdb->insert(
				$table,
				[
					'post_id'      => $post_id,
					'url'          => $url,
					'url_hash'     => md5( $url ),
					'http_status'  => $status,
					'link_type'    => $type,
					'is_broken'    => $is_broken,
					'last_checked' => $now,
					'created_at'   => $now
				],
				[ '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ]
			);
		}
	}

	/**
	 * Extract unique links from HTML, keyed url => type (internal|external|anchor).
	 * mailto:/tel:/javascript: are ignored.
	 *
	 * @return array<string,string>
	 */
	public function extract_links( $content ) {
		$links = [];
		if ( ! $content || stripos( $content, '<a' ) === false ) {
			return $links;
		}

		if ( ! preg_match_all( '/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\']/i', $content, $matches ) ) {
			return $links;
		}

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		foreach ( $matches[1] as $href ) {
			$href = trim( html_entity_decode( $href ) );
			if ( $href === '' ) {
				continue;
			}
			// Browsers strip control/whitespace chars from within a scheme before
			// parsing it, so normalize the same way before deciding a scheme is
			// unsafe — otherwise "java&#9;script:" slips past a naive ^javascript: test.
			$scheme_probe = preg_replace( '/[\x00-\x20]+/', '', $href );
			if ( preg_match( '#^(?:javascript|data|vbscript|mailto|tel|file):#i', $scheme_probe ) ) {
				continue;
			}
			if ( strpos( $href, '#' ) === 0 ) {
				$links[ $href ] = 'anchor';
				continue;
			}

			$host = wp_parse_url( $href, PHP_URL_HOST );
			if ( empty( $host ) ) {
				// Relative URL — internal.
				$links[ $href ] = 'internal';
			} else {
				$links[ $href ] = ( $host === $home_host ) ? 'internal' : 'external';
			}
		}

		return $links;
	}

	/**
	 * Check a URL's reachability. Routed through a filter for stubbing; otherwise
	 * a HEAD request with a GET fallback. Returns [ 'status' => int, 'broken' => bool ].
	 *
	 * Many hosts (and WAFs/CDNs like Cloudflare) reject HEAD requests or bot-like
	 * user agents with 403/405 even though the page is perfectly reachable in a
	 * browser. We send a browser-like user agent and, when HEAD errors or returns
	 * 403/405, retry once with GET before deciding the link is broken.
	 */
	public function check_url( $url ) {
		$filtered = apply_filters( 'betterdocs_analytics_link_status', null, $url );
		if ( is_int( $filtered ) ) {
			return [ 'status' => $filtered, 'broken' => ( $filtered === 0 || $filtered >= 400 ) ];
		}

		// Relative hrefs are same-origin by definition; resolve them so the
		// request layer has an absolute URL to work with.
		$target = \WP_Http::make_absolute_url( $url, home_url( '/' ) );
		if ( ! $target ) {
			return [ 'status' => 0, 'broken' => true ];
		}

		$args = [
			'timeout' => 7,
			// Do not let the HTTP layer follow redirects for us — every hop has to
			// be re-validated before we connect to it.
			'redirection' => 0,
			'user-agent'  => 'Mozilla/5.0 (compatible; BetterDocs-LinkHealth/1.0; +' . home_url( '/' ) . ')'
		];

		$code = 0;

		for ( $hop = 0; $hop <= self::MAX_REDIRECTS; $hop++ ) {
			// SSRF guard, applied to the initial URL *and* to every redirect
			// target. Validating only the first host let a public URL bounce the
			// scanner into loopback, RFC1918 or cloud-metadata addresses.
			if ( ! $this->is_allowed_scheme( $target ) || $this->is_blocked_url( $target ) ) {
				return [ 'status' => 0, 'broken' => true ];
			}

			// wp_safe_remote_* layers core's own reject_unsafe_urls validation on
			// top of our check.
			$response = wp_safe_remote_head( $target, $args );
			$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

			// HEAD blocked/unsupported (network error, Forbidden, or Method Not Allowed):
			// retry with GET, which many servers answer even when they refuse HEAD.
			if ( $code === 0 || $code === 403 || $code === 405 ) {
				$get_response = wp_safe_remote_get( $target, $args );
				if ( ! is_wp_error( $get_response ) ) {
					$response = $get_response;
					$code     = (int) wp_remote_retrieve_response_code( $response );
				}
			}

			if ( ! in_array( $code, [ 301, 302, 303, 307, 308 ], true ) ) {
				break;
			}

			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( empty( $location ) || is_array( $location ) ) {
				break;
			}

			// Location may be relative — resolve against the URL just fetched,
			// then loop so the new target is validated before it is requested.
			$next = \WP_Http::make_absolute_url( $location, $target );
			if ( ! $next || $next === $target ) {
				break;
			}

			$target = $next;
		}

		return [ 'status' => $code, 'broken' => ( $code === 0 || $code >= 400 ) ];
	}

	/**
	 * Only plain HTTP(S) may be requested. extract_links() already drops
	 * javascript:/data:/file: and friends, but a redirect Location never passes
	 * through that path.
	 */
	protected function is_allowed_scheme( $url ) {
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

		return in_array( strtolower( (string) $scheme ), [ 'http', 'https' ], true );
	}

	/**
	 * SSRF check: block external URLs that resolve to a private, reserved, or
	 * link-local address (RFC1918, loopback, and the 169.254.169.254 cloud
	 * metadata endpoint). The site's own host is always allowed so internal-link
	 * checks keep working on local/staging installs that resolve to a private IP.
	 *
	 * check_url() applies this to every hop, not just the first, so a public URL
	 * that redirects toward an internal address is rejected at the hop that turns
	 * inward rather than being followed.
	 *
	 * Residual risk: the host is resolved here and again by the HTTP transport,
	 * so a DNS entry that changes between those two lookups (rebinding) can still
	 * differ. Pinning the resolved address requires transport-level support that
	 * WP_Http does not expose.
	 */
	protected function is_blocked_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false; // Relative/internal URL — same-origin, trusted.
		}

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( strtolower( $host ) === strtolower( (string) $home_host ) ) {
			return false; // The site's own host is trusted.
		}

		$ip = filter_var( $host, FILTER_VALIDATE_IP ) ? $host : gethostbyname( $host );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false; // Unresolvable — let the HTTP layer report it broken.
		}

		// Reject anything outside the normal public unicast ranges.
		return ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}
}
