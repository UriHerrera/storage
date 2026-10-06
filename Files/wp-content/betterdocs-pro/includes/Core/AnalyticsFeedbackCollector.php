<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Pro per-item feedback capture (Advanced Analytics v1.0).
 *
 * Hooks the Free `betterdocs_feedback_recorded` action and writes one row per
 * reaction into {prefix}betterdocs_analytics_feedback (status defaults to
 * 'new'), powering the Feedback Inbox stream + bulk status actions. The Free
 * daily reaction aggregate (betterdocs_analytics) is unchanged.
 */
class AnalyticsFeedbackCollector {
	public function __construct() {
		add_action( 'betterdocs_feedback_recorded', [ $this, 'record' ], 10, 2 );
	}

	/**
	 * @param int    $post_id Doc post id.
	 * @param string $feeling happy|sad|normal.
	 */
	public function record( $post_id, $feeling ) {
		global $wpdb;

		$post_id = (int) $post_id;
		$allowed = [ 'happy', 'sad', 'normal' ];
		if ( ! $post_id || ! in_array( $feeling, $allowed, true ) ) {
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'betterdocs_analytics_feedback',
			[
				'post_id'    => $post_id,
				'feeling'    => $feeling,
				'comment'    => null,
				'status'     => 'new',
				'kb_id'      => $this->get_kb_id( $post_id ),
				'lang'       => $this->get_post_lang( $post_id ),
				'created_at' => current_time( 'mysql', true )
			],
			[ '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);
	}

	protected function get_kb_id( $post_id ) {
		if ( ! betterdocs()->settings->get( 'multiple_kb' ) ) {
			return 0;
		}
		$terms = wp_get_post_terms( $post_id, 'knowledge_base', [ 'fields' => 'ids' ] );
		return ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0] : 0;
	}

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
}
