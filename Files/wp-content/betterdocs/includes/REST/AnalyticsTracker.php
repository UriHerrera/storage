<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_REST_Request;
use WP_REST_Response;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Core\AnalyticsTracker as Tracker;

/**
 * Write-only ingest for the lightweight Free view tracker.
 *
 * POST betterdocs/v1/analytics/view  { post_id, u, nonce }
 *
 * Kept intentionally thin: verify nonce, validate the doc, hand off to the
 * Core tracker's record_view(). No reads/joins beyond the single upsert.
 */
class AnalyticsTracker extends BaseAPI {
	public function register() {
		$this->post(
			'/analytics/view',
			[ $this, 'track' ],
			[
				'post_id' => [
					'type'              => 'integer',
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( $param ) && is_numeric( $param ) && get_post( (int) $param ) !== null;
					}
				],
				'u'       => [
					'type'     => 'integer',
					'required' => false,
					'default'  => 0
				]
			]
		);
	}

	/**
	 * Public endpoint, gated by a wp_rest nonce. The tracker sends it via the
	 * X-WP-Nonce header (with credentials), which also satisfies WordPress's
	 * global REST cookie nonce check for logged-in visitors. Falls back to a
	 * _wpnonce request param.
	 */
	public function permission_check( $request = null ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return false;
		}

		$nonce = $request->get_header( 'x_wp_nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	public function track( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$tracker = $this->container->get( Tracker::class );

		// Reading-completion signal: a scroll-depth update for an already-counted
		// view. Does not record a new view (no double count).
		if ( $request->get_param( 'event' ) === 'scroll' ) {
			$depth = max( 0, min( 100, (int) $request->get_param( 'depth' ) ) );
			if ( get_post( $post_id ) && $tracker->is_eligible() ) {
				/**
				 * Fires with a doc's max scroll depth (0–100) on page hide.
				 * Pro records it as a 'scroll' event for the reading-completion rollup.
				 */
				do_action( 'betterdocs_analytics_scroll_recorded', $post_id, $depth );
			}
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}

		$unique  = (int) $request->get_param( 'u' );

		$recorded = $tracker->record_view( $post_id, $unique );

		if ( $recorded ) {
			/**
			 * Fires after a doc view is recorded into the simple aggregate.
			 * Pro hooks this to write an enriched row into the raw events table
			 * (referrer, device, kb, language, hashed ip/session).
			 *
			 * @param int             $post_id Doc post id.
			 * @param WP_REST_Request $request The ingest request (carries referrer).
			 */
			do_action( 'betterdocs_analytics_view_recorded', $post_id, $request );
		}

		return new WP_REST_Response( [ 'success' => (bool) $recorded ], 200 );
	}
}
