<?php
/**
 * Search insights ability.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Insights;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;

/**
 * Read what visitors search for: the most-used search terms in the knowledge
 * base and how often each was searched. The signal that tells an agent which
 * docs to write next — the biggest gaps are the popular searches with no good
 * answer.
 *
 * Reads the search log directly (keyword + summed count) rather than the
 * frontend `popular_search_keyword()` helper, which returns bare strings above a
 * threshold; an agent wants the counts and the full ranked list.
 *
 * @since 4.9.1
 */
class GetSearchInsights extends ProAbility {

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/get-search-insights';
	}

	/**
	 * @since 4.9.1
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$state = $this->pro_state();

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, $this->feature );
		}

		global $wpdb;

		$keyword_table = $wpdb->prefix . 'betterdocs_search_keyword';
		$log_table     = $wpdb->prefix . 'betterdocs_search_log';

		// The search-log tables are created when the analytics feature first runs;
		// a site that has never logged a search has neither. Report "no data"
		// rather than erroring on a missing table.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is our own prefix + literal.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$keyword_table}'" ) !== $keyword_table ) {
			return [ 'keywords' => [], 'total' => 0 ];
		}

		$limit = isset( $input['limit'] ) ? min( 100, max( 1, (int) $input['limit'] ) ) : 20;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are our own prefix + literals; the only user value ($limit) is bound via %d.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT k.keyword AS keyword, SUM(l.count) AS count
				 FROM {$keyword_table} k
				 JOIN {$log_table} l ON k.id = l.keyword_id
				 WHERE k.keyword IS NOT NULL AND k.keyword <> ''
				 GROUP BY l.keyword_id
				 ORDER BY count DESC
				 LIMIT %d",
				$limit
			)
		);
		// phpcs:enable

		$keywords = [];

		foreach ( (array) $rows as $row ) {
			$keywords[] = [
				'keyword' => (string) $row->keyword,
				'count'   => (int) $row->count
			];
		}

		return [
			'keywords' => $keywords,
			'total'    => count( $keywords )
		];
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'read search insights', 'betterdocs-pro' );
	}
}
