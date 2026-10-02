<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Action Scheduler aggregation (Advanced Analytics v1.0).
 *
 * A recurring 15-minute job drains unprocessed rows from
 * {prefix}betterdocs_analytics_events and rolls 'view' events up into
 * {prefix}betterdocs_analytics_daily.
 *
 * Idempotent by design: for each "dirty" (post, kb, lang, day) group it
 * RECOMPUTES the totals from all of that group's events and SETs the daily row
 * (via INSERT ... ON DUPLICATE KEY UPDATE on the uniq_post_day key), rather than
 * incrementing. A re-run can never double-count, and COUNT(DISTINCT session_hash)
 * gives a correct unique count. The Free betterdocs_analytics table is left
 * untouched — Free maintains it directly, so there is no dual-write here.
 */
class AnalyticsAggregator {
	const HOOK  = 'betterdocs_analytics_aggregate';
	const BATCH = 1000;

	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::HOOK, [ $this, 'run' ] );
	}

	/**
	 * Schedule the recurring rollup once (every 15 minutes).
	 */
	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( self::HOOK ) ) {
			as_schedule_recurring_action( time() + MINUTE_IN_SECONDS, 15 * MINUTE_IN_SECONDS, self::HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Drain a batch of unprocessed view events into the daily rollup.
	 */
	public function run() {
		global $wpdb;

		$events = $wpdb->prefix . 'betterdocs_analytics_events';
		$daily  = $wpdb->prefix . 'betterdocs_analytics_daily';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, object_id, kb_id, lang, DATE( created_at ) AS d
				FROM {$wpdb->prefix}betterdocs_analytics_events
				WHERE processed = 0 AND event_type IN ( 'view', 'scroll' )
				ORDER BY id ASC
				LIMIT %d",
				self::BATCH
			)
		);

		if ( ! empty( $rows ) ) {
			$ids    = [];
			$groups = [];
			foreach ( $rows as $r ) {
				$ids[] = (int) $r->id;
				// Key the dirty group; lang may be '' which is fine.
				$groups[ $r->object_id . '|' . $r->kb_id . '|' . $r->lang . '|' . $r->d ] = [
					'post_id' => (int) $r->object_id,
					'kb_id'   => (int) $r->kb_id,
					'lang'    => (string) $r->lang,
					'date'    => $r->d
				];
			}

			foreach ( $groups as $g ) {
				$views_agg = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT(*) AS views, COUNT( DISTINCT session_hash ) AS unique_views
						FROM {$wpdb->prefix}betterdocs_analytics_events
						WHERE event_type = 'view' AND object_id = %d AND kb_id = %d AND lang = %s AND DATE( created_at ) = %s",
						$g['post_id'],
						$g['kb_id'],
						$g['lang'],
						$g['date']
					)
				);

				$scroll_agg = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							AVG( CAST( JSON_EXTRACT( payload, '$.depth' ) AS DECIMAL(6,2) ) ) AS avg_depth,
							SUM( CASE WHEN CAST( JSON_EXTRACT( payload, '$.depth' ) AS UNSIGNED ) >= 90 THEN 1 ELSE 0 END ) AS completions
						FROM {$wpdb->prefix}betterdocs_analytics_events
						WHERE event_type = 'scroll' AND object_id = %d AND kb_id = %d AND lang = %s AND DATE( created_at ) = %s",
						$g['post_id'],
						$g['kb_id'],
						$g['lang'],
						$g['date']
					)
				);

				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$wpdb->prefix}betterdocs_analytics_daily
							( post_id, kb_id, lang, stat_date, views, unique_views, avg_scroll_depth, reading_completions )
						VALUES ( %d, %d, %s, %s, %d, %d, %f, %d )
						ON DUPLICATE KEY UPDATE
							views = VALUES( views ),
							unique_views = VALUES( unique_views ),
							avg_scroll_depth = VALUES( avg_scroll_depth ),
							reading_completions = VALUES( reading_completions )",
						$g['post_id'],
						$g['kb_id'],
						$g['lang'],
						$g['date'],
						$views_agg ? (int) $views_agg->views : 0,
						$views_agg ? (int) $views_agg->unique_views : 0,
						$scroll_agg && $scroll_agg->avg_depth !== null ? (float) $scroll_agg->avg_depth : 0,
						$scroll_agg ? (int) $scroll_agg->completions : 0
					)
				);
			}

			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}betterdocs_analytics_events SET processed = 1 WHERE id IN ( {$placeholders} )",
					$ids
				)
			);
		}

		$search_rows = $this->run_search();

		// Drain pattern: if either batch was full there may be more — run again now.
		$more = ( count( $rows ) >= self::BATCH ) || ( $search_rows >= self::BATCH );
		if ( $more && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Roll unprocessed 'search' events into betterdocs_analytics_search.
	 *
	 * Same idempotent recompute contract as the view/scroll rollup: a dirty
	 * (keyword_hash, kb, lang, day) group is fully recomputed from ALL of that
	 * group's search events and SET via ON DUPLICATE KEY UPDATE, so re-runs can
	 * never double-count. click_through_count is left untouched here — it is
	 * maintained separately when search-click events land (follow-up).
	 *
	 * @return int Number of search rows drained this pass (for the drain check).
	 */
	protected function run_search() {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, kb_id, lang,
					JSON_UNQUOTE( JSON_EXTRACT( payload, '$.keyword_hash' ) ) AS kw_hash,
					DATE( created_at ) AS d
				FROM {$wpdb->prefix}betterdocs_analytics_events
				WHERE processed = 0 AND event_type = 'search'
				ORDER BY id ASC
				LIMIT %d",
				self::BATCH
			)
		);

		if ( empty( $rows ) ) {
			return 0;
		}

		$ids    = [];
		$groups = [];
		foreach ( $rows as $r ) {
			$ids[] = (int) $r->id;
			$groups[ $r->kw_hash . '|' . $r->kb_id . '|' . $r->lang . '|' . $r->d ] = [
				'kw_hash' => (string) $r->kw_hash,
				'kb_id'   => (int) $r->kb_id,
				'lang'    => (string) $r->lang,
				'date'    => $r->d
			];
		}

		foreach ( $groups as $g ) {
			$agg = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) AS search_count,
						SUM( CASE WHEN CAST( JSON_EXTRACT( payload, '$.no_result' ) AS UNSIGNED ) = 1 THEN 1 ELSE 0 END ) AS zero_result_count,
						AVG( CASE WHEN JSON_EXTRACT( payload, '$.results' ) IS NOT NULL
								THEN CAST( JSON_EXTRACT( payload, '$.results' ) AS DECIMAL(8,2) ) END ) AS results_avg,
						MAX( JSON_UNQUOTE( JSON_EXTRACT( payload, '$.keyword' ) ) ) AS keyword
					FROM {$wpdb->prefix}betterdocs_analytics_events
					WHERE event_type = 'search'
						AND JSON_UNQUOTE( JSON_EXTRACT( payload, '$.keyword_hash' ) ) = %s
						AND kb_id = %d AND lang = %s AND DATE( created_at ) = %s",
					$g['kw_hash'],
					$g['kb_id'],
					$g['lang'],
					$g['date']
				)
			);

			$keyword = ( $agg && $agg->keyword !== null ) ? mb_substr( (string) $agg->keyword, 0, 191 ) : '';

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}betterdocs_analytics_search
						( keyword, keyword_hash, search_count, results_count_avg, zero_result_count, click_through_count, kb_id, lang, stat_date )
					VALUES ( %s, %s, %d, %f, %d, 0, %d, %s, %s )
					ON DUPLICATE KEY UPDATE
						keyword = VALUES( keyword ),
						search_count = VALUES( search_count ),
						results_count_avg = VALUES( results_count_avg ),
						zero_result_count = VALUES( zero_result_count )",
					$keyword,
					$g['kw_hash'],
					$agg ? (int) $agg->search_count : 0,
					( $agg && $agg->results_avg !== null ) ? (float) $agg->results_avg : 0,
					$agg ? (int) $agg->zero_result_count : 0,
					$g['kb_id'],
					$g['lang'],
					$g['date']
				)
			);
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}betterdocs_analytics_events SET processed = 1 WHERE id IN ( {$placeholders} )",
				$ids
			)
		);

		return count( $rows );
	}
}
