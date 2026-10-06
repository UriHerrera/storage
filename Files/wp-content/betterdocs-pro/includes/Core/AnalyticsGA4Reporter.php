<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Server-side Google Analytics 4 forwarding via the Measurement Protocol.
 *
 * Fires only when GA4 forwarding is enabled AND the delivery method is 'mp' AND
 * both a Measurement ID and an API secret are configured. The API secret is read
 * here (server-side) and is NEVER exposed to the frontend. Requests are
 * non-blocking so they never slow the view-ingest beacon.
 *
 * Mirrors the client-side gtag/dataLayer methods exactly — the SAME event names
 * regardless of delivery method (betterdocs_doc_view on a recorded view,
 * betterdocs_doc_complete on a ≥90% scroll), so GA4 reports don't fragment by
 * method. The client-side and server-side methods are mutually exclusive
 * (chosen by `ga4_method`), so a view is never double-sent.
 */
class AnalyticsGA4Reporter {

	const ENDPOINT = 'https://www.google-analytics.com/mp/collect';

	public function __construct() {
		// Priority 20: after AnalyticsEventCollector (10) has recorded the event.
		add_action( 'betterdocs_analytics_view_recorded', [ $this, 'send_view' ], 20, 2 );
		add_action( 'betterdocs_analytics_scroll_recorded', [ $this, 'send_complete' ], 20, 2 );
	}

	/**
	 * Send a doc view for a recorded view — named `betterdocs_doc_view` to match
	 * the client-side methods, so the event is consistent in GA4 no matter which
	 * delivery method a site uses (QA #11).
	 *
	 * @param int   $post_id
	 * @param mixed $request  WP_REST_Request (unused; kept for hook signature).
	 * @return void
	 */
	public function send_view( $post_id, $request = null ) {
		$this->send_event( 'betterdocs_doc_view', (int) $post_id, [] );
	}

	/**
	 * Send a reading-completion event once the reader scrolled ≥90% (matches the
	 * client-side threshold in analytics-tracker.js).
	 *
	 * @param int $post_id
	 * @param int $depth    0-100
	 * @return void
	 */
	public function send_complete( $post_id, $depth ) {
		if ( (int) $depth < 90 ) {
			return;
		}
		$this->send_event( 'betterdocs_doc_complete', (int) $post_id, [ 'depth' => (int) $depth ] );
	}

	/**
	 * Non-blocking Measurement Protocol send. Reads the (server-side only) config
	 * and no-ops unless GA4 forwarding is on, the method is 'mp', and both the
	 * Measurement ID and API secret are set.
	 *
	 * @param string $event_name
	 * @param int    $post_id
	 * @param array  $extra_params
	 * @return void
	 */
	protected function send_event( $event_name, $post_id, $extra_params ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return;
		}

		if ( ! (bool) betterdocs()->settings->get( 'analytics_ga4', false ) ) {
			return;
		}
		if ( 'mp' !== (string) betterdocs()->settings->get( 'ga4_method', 'datalayer' ) ) {
			return;
		}

		$measurement_id = sanitize_text_field( (string) betterdocs()->settings->get( 'ga4_measurement_id', '' ) );
		$api_secret     = trim( (string) betterdocs()->settings->get( 'ga4_api_secret', '' ) );
		if ( '' === $measurement_id || '' === $api_secret ) {
			return;
		}

		$url = add_query_arg(
			[
				'measurement_id' => $measurement_id,
				'api_secret'     => $api_secret,
			],
			self::ENDPOINT
		);

		$params = array_merge(
			[
				'page_title'    => wp_strip_all_tags( get_the_title( $post_id ) ),
				'page_location' => get_permalink( $post_id ),
				'doc_id'        => $post_id,
				// GA4 needs an engagement signal or the session won't register.
				'engagement_time_msec' => 1,
			],
			(array) $extra_params
		);

		// When gtag runs elsewhere on the site its `_ga_<STREAM>` cookie carries the
		// session id — include it so MP events stitch into the same GA4 session
		// instead of opening a new one. Omitted when absent (standalone MP mode).
		$session_id = $this->session_id();
		if ( '' !== $session_id ) {
			$params['session_id'] = $session_id;
		}

		$body = [
			'client_id' => $this->client_id(),
			'events'    => [
				[
					'name'   => $event_name,
					'params' => $params,
				],
			],
		];

		wp_remote_post(
			$url,
			[
				'timeout'  => 2,
				'blocking' => false,
				'headers'  => [ 'Content-Type' => 'application/json' ],
				'body'     => wp_json_encode( $body ),
			]
		);
	}

	/**
	 * The current GA4 session id from the visitor's `_ga_<STREAM>` cookie
	 * (`GS?.1.<session_id>.<session_number>…`), or '' when no gtag session exists.
	 *
	 * @return string
	 */
	protected function session_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only cookie for GA session id.
		foreach ( (array) $_COOKIE as $name => $value ) {
			if ( 0 !== strpos( (string) $name, '_ga_' ) ) {
				continue;
			}
			$parts = explode( '.', sanitize_text_field( wp_unslash( (string) $value ) ) );
			if ( isset( $parts[2] ) && ctype_digit( $parts[2] ) ) {
				return $parts[2];
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return '';
	}

	/**
	 * A stable GA4 client id. Prefers the visitor's `_ga` cookie (so events join
	 * their real GA4 web sessions when gtag is present anywhere on the site);
	 * otherwise a first-party `betterdocs_ga_cid` cookie, generated once and reused.
	 *
	 * @return string
	 */
	protected function client_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only cookie for GA client id.
		if ( ! empty( $_COOKIE['_ga'] ) ) {
			// `_ga` is `GA1.1.<cid1>.<cid2>`; the GA4 client_id is `<cid1>.<cid2>`.
			$parts = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) ) );
			$n     = count( $parts );
			if ( $n >= 4 ) {
				return $parts[ $n - 2 ] . '.' . $parts[ $n - 1 ];
			}
		}

		if ( ! empty( $_COOKIE['betterdocs_ga_cid'] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE['betterdocs_ga_cid'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// GA-style client id: <random-uint32>.<unix-timestamp>.
		$cid = wp_rand( 1, 2147483647 ) . '.' . time();
		if ( ! headers_sent() ) {
			setcookie( 'betterdocs_ga_cid', $cid, time() + YEAR_IN_SECONDS, '/' );
		}
		$_COOKIE['betterdocs_ga_cid'] = $cid;
		return $cid;
	}
}
