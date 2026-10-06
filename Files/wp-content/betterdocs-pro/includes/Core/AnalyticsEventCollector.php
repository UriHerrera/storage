<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WP_REST_Request;

/**
 * Pro raw-event collection (Advanced Analytics v1.0).
 *
 * Hooks the Free tracker's `betterdocs_analytics_view_recorded` action and
 * writes an enriched row into {prefix}betterdocs_analytics_events for the
 * Action Scheduler aggregation (Unit 8) to roll up. The Free simple-aggregate
 * write still happens for the Overview; this adds the richer dimensions
 * (referrer, device, knowledge base, language) the Pro modules surface.
 *
 * Privacy: the raw IP is never stored — only a salted SHA-256 hash, and only
 * when the `anonymize_ip_addresses` setting allows it.
 */
class AnalyticsEventCollector {
	public function __construct() {
		add_action( 'betterdocs_analytics_view_recorded', [ $this, 'record_event' ], 10, 2 );
		add_action( 'betterdocs_analytics_scroll_recorded', [ $this, 'record_scroll' ], 10, 2 );
		add_action( 'betterdocs_analytics_search_recorded', [ $this, 'record_search' ], 10, 3 );
	}

	/**
	 * Record a front-end search as a 'search' event for the search rollup.
	 *
	 * object_id is 0 (a search is not tied to one doc); the keyword and a
	 * normalized md5 keyword_hash live in the payload so the aggregator can
	 * group by keyword per kb/lang/day. `no_result` is the zero-result flag —
	 * the cheapest, highest-signal input to content-gap detection.
	 *
	 * @param string $keyword   The search term.
	 * @param bool   $no_result True when the search returned no results.
	 * @param mixed  $request   The ingest request (unused; kept for parity/future kb scope).
	 */
	public function record_search( $keyword, $no_result = false, $request = null ) {
		global $wpdb;

		$keyword = trim( (string) $keyword );
		if ( $keyword === '' ) {
			return;
		}

		$cookieless   = (bool) betterdocs()->settings->get( 'analytics_cookieless', false );
		$keyword_hash = md5( strtolower( $keyword ) );

		$wpdb->insert(
			$wpdb->prefix . 'betterdocs_analytics_events',
			[
				'event_type'   => 'search',
				'object_id'    => 0,
				'session_hash' => $cookieless ? null : $this->session_hash(),
				'kb_id'        => 0,
				'lang'         => '',
				'payload'      => wp_json_encode(
					[
						'keyword'      => mb_substr( $keyword, 0, 191 ),
						'keyword_hash' => $keyword_hash,
						'no_result'    => $no_result ? 1 : 0,
					]
				),
				'ip_hash'      => $cookieless ? null : $this->client_ip_hash(),
				'created_at'   => current_time( 'mysql', true ),
				'processed'    => 0
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d' ]
		);
	}

	/**
	 * Record a reading-completion (scroll-depth) event for the rollup.
	 *
	 * @param int $post_id Doc post id.
	 * @param int $depth   Max scroll depth 0–100.
	 */
	public function record_scroll( $post_id, $depth ) {
		global $wpdb;

		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return;
		}
		$depth = max( 0, min( 100, (int) $depth ) );

		$cookieless = (bool) betterdocs()->settings->get( 'analytics_cookieless', false );

		$wpdb->insert(
			$wpdb->prefix . 'betterdocs_analytics_events',
			[
				'event_type'   => 'scroll',
				'object_id'    => $post_id,
				'session_hash' => $cookieless ? null : $this->session_hash(),
				'kb_id'        => $this->get_kb_id( $post_id ),
				'lang'         => $this->get_post_lang( $post_id ),
				'payload'      => wp_json_encode( [ 'depth' => $depth ] ),
				'ip_hash'      => $cookieless ? null : $this->client_ip_hash(),
				'created_at'   => current_time( 'mysql', true ),
				'processed'    => 0
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d' ]
		);
	}

	/**
	 * @param int             $post_id Doc post id.
	 * @param WP_REST_Request $request Ingest request (carries the client referrer).
	 */
	public function record_event( $post_id, $request = null ) {
		global $wpdb;

		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return;
		}

		$referrer = '';
		if ( $request instanceof WP_REST_Request ) {
			$referrer = esc_url_raw( (string) $request->get_param( 'referrer' ) );
		}

		// Cookieless mode (privacy-strict): store no IP-derived data at all — no
		// session/ip hashes and no country (country resolution uses the raw IP).
		$cookieless = (bool) betterdocs()->settings->get( 'analytics_cookieless', false );

		$country = '';
		if ( ! $cookieless ) {
			// Resolve country from the raw client IP (proxy-aware when the site
			// opts in — see client_ip()) before it is hashed for storage.
			$country = (string) apply_filters( 'betterdocs_analytics_event_country', '', $this->client_ip() );
		}

		$payload = wp_json_encode(
			[
				'referrer' => $referrer,
				'device'   => wp_is_mobile() ? 'mobile' : 'desktop',
				'country'  => $country
			]
		);

		$wpdb->insert(
			$wpdb->prefix . 'betterdocs_analytics_events',
			[
				'event_type'   => 'view',
				'object_id'    => $post_id,
				'session_hash' => $cookieless ? null : $this->session_hash(),
				'kb_id'        => $this->get_kb_id( $post_id ),
				'lang'         => $this->get_post_lang( $post_id ),
				'payload'      => $payload,
				'ip_hash'      => $cookieless ? null : $this->client_ip_hash(),
				'created_at'   => current_time( 'mysql', true ),
				'processed'    => 0
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d' ]
		);
	}

	/**
	 * Knowledge base term id for the doc, when multi-KB is enabled; else 0.
	 */
	protected function get_kb_id( $post_id ) {
		if ( ! betterdocs()->settings->get( 'multiple_kb' ) ) {
			return 0;
		}
		$terms = wp_get_post_terms( $post_id, 'knowledge_base', [ 'fields' => 'ids' ] );
		return ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0] : 0;
	}

	/**
	 * Language code for the doc when WPML/Polylang is active; else ''.
	 */
	protected function get_post_lang( $post_id ) {
		if ( function_exists( 'pll_get_post_language' ) ) {
			$lang = pll_get_post_language( $post_id, 'slug' );
			return $lang ? $lang : '';
		}
		$wpml = apply_filters( 'wpml_post_language_details', null, $post_id );
		if ( is_array( $wpml ) && ! empty( $wpml['language_code'] ) ) {
			return $wpml['language_code'];
		}
		return '';
	}

	/**
	 * Best-effort client IP. REMOTE_ADDR by default — proxy headers are trivially
	 * spoofable, so they are only consulted when the site explicitly opts in via
	 * the `betterdocs_analytics_trust_proxy_headers` filter (return true when the
	 * site sits behind a trusted reverse proxy / CDN — e.g. Cloudflare — that
	 * overwrites these headers). Candidates are validated as real IPs; anything
	 * invalid falls back to REMOTE_ADDR. A final `betterdocs_analytics_client_ip`
	 * filter covers unusual proxy setups.
	 */
	protected function client_ip() {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( apply_filters( 'betterdocs_analytics_trust_proxy_headers', false ) ) {
			foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR' ] as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}
				// X-Forwarded-For can be a comma list — the left-most entry is the client.
				$candidates = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				$candidate  = trim( $candidates[0] );
				if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
					$remote = $candidate;
					break;
				}
			}
		}

		return (string) apply_filters( 'betterdocs_analytics_client_ip', $remote );
	}

	/**
	 * Salted SHA-256 of the client IP. Raw IP never stored.
	 */
	protected function client_ip_hash() {
		if ( ! betterdocs()->settings->get( 'anonymize_ip_addresses', true ) ) {
			// Even when anonymization is off we do not persist a raw IP here;
			// the events table is hash-only by design.
			return null;
		}
		$ip = $this->client_ip();
		return $ip === '' ? null : hash( 'sha256', $ip . wp_salt() );
	}

	/**
	 * Coarse, privacy-safe session id (hashed IP + UA). Used for grouping only.
	 */
	protected function session_hash() {
		$ip = $this->client_ip();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
		if ( $ip === '' && $ua === '' ) {
			return null;
		}
		return hash( 'sha256', $ip . '|' . $ua . '|' . wp_salt() );
	}
}
