<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Content Health Score (Content Intelligence, Analytics v2.0).
 *
 * A nightly Action Scheduler job scores every published doc on five components
 * — no LLM — and writes a `type = 'health'` row per doc into
 * betterdocs_analytics_insights. At the end of a pass it rolls the per-doc rows
 * into a site-level snapshot (score + grade + per-component averages + a 0–100
 * distribution) appended to a history option, which powers the 90-day trend and
 * the month-over-month delta on the Health screen.
 *
 * Components (each 0–100, weighted into the composite):
 *   - engagement  (25%) : scroll depth + reading-completion rate
 *   - helpfulness (25%) : happy / (happy + unhappy) reaction ratio, last 30 days
 *                         where rated, else lifetime (reactions are too sparse
 *                         for a 30-day window on its own)
 *   - freshness   (20%) : how recently the article was modified
 *   - findability (15%) : whether the article actually gets found (recent views)
 *   - accuracy    (15%) : reused ArticleQualityScore, minus broken-link penalty
 */
class ContentHealthScorer {
	const START_HOOK  = 'betterdocs_content_health_scan_start';
	const RUN_HOOK    = 'betterdocs_content_health_scan_run';
	const OFFSET_OPT  = 'betterdocs_content_health_scan_offset';
	const STARTED_OPT = 'betterdocs_content_health_scan_started';
	const HISTORY_OPT = 'betterdocs_content_health_history';
	const CHUNK       = 25;
	const HISTORY_MAX = 120;

	const WEIGHTS = [
		'engagement'  => 0.25,
		'helpfulness' => 0.25,
		'freshness'   => 0.20,
		'findability' => 0.15,
		'accuracy'    => 0.15,
	];

	/** @var AnalyticsInsightStore */
	protected $store;

	public function __construct() {
		$this->store = new AnalyticsInsightStore();
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::START_HOOK, [ $this, 'start_scan' ] );
		add_action( self::RUN_HOOK, [ $this, 'run_chunk' ] );
	}

	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( self::START_HOOK ) ) {
			// Offset from the stale scan (which also runs daily) so the two
			// per-doc walkers don't hammer the DB in the same minute.
			as_schedule_recurring_action( time() + 2 * HOUR_IN_SECONDS, DAY_IN_SECONDS, self::START_HOOK, [], 'betterdocs' );
		}
	}

	public function start_scan() {
		if ( function_exists( 'as_next_scheduled_action' ) && false !== as_next_scheduled_action( self::RUN_HOOK, [], 'betterdocs' ) ) {
			return;
		}
		update_option( self::STARTED_OPT, current_time( 'mysql', true ) );
		update_option( self::OFFSET_OPT, 0 );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::RUN_HOOK, [], 'betterdocs' );
		} else {
			$this->run_chunk();
		}
	}

	public function run_chunk() {
		$offset = (int) get_option( self::OFFSET_OPT, 0 );

		$ids = get_posts(
			[
				'post_type'      => 'docs',
				'post_status'    => 'publish',
				// Content restriction is a visitor rule; this is an admin-side
				// scan. Without the opt-out the job runs with no logged-in user,
				// every doc lands in post__not_in, and the scan prunes the rows it
				// wrote last night — losing history because a setting was toggled.
				'betterdocs_bypass_restrictions' => true,
				'posts_per_page' => self::CHUNK,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);

		if ( empty( $ids ) ) {
			$this->finish_pass();
			return;
		}

		foreach ( $ids as $post_id ) {
			$this->score_and_store( (int) $post_id );
		}

		update_option( self::OFFSET_OPT, $offset + count( $ids ) );

		if ( count( $ids ) >= self::CHUNK && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::RUN_HOOK, [], 'betterdocs' );
		} else {
			$this->finish_pass();
		}
	}

	/**
	 * Drop health rows for docs that no longer exist / are unpublished (not
	 * refreshed this pass), then snapshot the site-level score.
	 */
	protected function finish_pass() {
		global $wpdb;

		$started = (string) get_option( self::STARTED_OPT, '' );
		if ( $started !== '' ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}betterdocs_analytics_insights
					WHERE type = 'health' AND status = 'new' AND updated_at < %s",
					$started
				)
			);
		}

		$this->snapshot_site_health();

		delete_option( self::OFFSET_OPT );
		delete_option( self::STARTED_OPT );
	}

	public function score_and_store( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return;
		}

		$components = $this->score_components( $post );

		$health = 0.0;
		foreach ( self::WEIGHTS as $key => $weight ) {
			$health += $components[ $key ] * $weight;
		}
		$health = round( $health, 2 );

		// Weakest component drives the "what to fix" hint on the underperformers row.
		asort( $components );
		$weakest = key( $components );

		$this->store->upsert(
			'health',
			$this->store->object_hash( 'health', $post_id ),
			$health,
			[
				'post_id'    => $post_id,
				'title'      => get_the_title( $post_id ),
				'category'   => $this->primary_category( $post_id ),
				'health'     => $health,
				'grade'      => $this->grade( $health ),
				'components' => $components,
				'weakest'    => $weakest,
				'edit_link'  => get_edit_post_link( $post_id, 'raw' ),
			],
			[ 'object_id' => $post_id, 'source' => 'local' ]
		);
	}

	/**
	 * Five component scores (0–100) for one post.
	 *
	 * @return array{engagement:float,helpfulness:float,freshness:float,findability:float,accuracy:float}
	 */
	public function score_components( $post ) {
		global $wpdb;

		$post_id = (int) $post->ID;
		$daily   = $wpdb->prefix . 'betterdocs_analytics_daily';
		$since   = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		// The windowed columns keep the 30-day semantics engagement/findability
		// want; happy_all/sad_all are lifetime so helpfulness can fall back to
		// them (see below) instead of going neutral on a quiet month.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( CASE WHEN stat_date >= %s THEN views ELSE 0 END ), 0 ) AS views,
					COALESCE( SUM( CASE WHEN stat_date >= %s THEN reading_completions ELSE 0 END ), 0 ) AS completions,
					COALESCE( AVG( CASE WHEN stat_date >= %s THEN NULLIF( avg_scroll_depth, 0 ) END ), 0 ) AS scroll,
					COALESCE( SUM( CASE WHEN stat_date >= %s THEN happy ELSE 0 END ), 0 ) AS happy,
					COALESCE( SUM( CASE WHEN stat_date >= %s THEN sad ELSE 0 END ), 0 ) AS sad,
					COALESCE( SUM( happy ), 0 ) AS happy_all,
					COALESCE( SUM( sad ), 0 ) AS sad_all
				FROM {$daily}
				WHERE post_id = %d",
				$since,
				$since,
				$since,
				$since,
				$since,
				$post_id
			)
		);

		$views       = $row ? (int) $row->views : 0;
		$completions = $row ? (int) $row->completions : 0;
		$scroll      = $row ? (float) $row->scroll : 0.0;
		$happy       = $row ? (int) $row->happy : 0;
		$sad         = $row ? (int) $row->sad : 0;
		$happy_all   = $row ? (int) $row->happy_all : 0;
		$sad_all     = $row ? (int) $row->sad_all : 0;

		// Engagement: half scroll-depth, half completion rate. Neutral 60 when the
		// article has no recent traffic to judge (avoids punishing new content).
		if ( $views > 0 ) {
			$completion_rate = min( 1, $completions / max( 1, $views ) );
			$engagement      = ( $scroll * 0.5 ) + ( $completion_rate * 100 * 0.5 );
		} else {
			$engagement = 60.0;
		}

		// Helpfulness: happy share of decisive reactions.
		//
		// Reactions accrue far more slowly than views — a doc collects a handful
		// over its lifetime and typically none at all in any given 30 days — so
		// scoring them on the same 30-day window as engagement drove helpfulness
		// to the neutral constant for essentially every doc (QA measured 70 on
		// all 192). Prefer the recent window while it has signal, fall back to
		// the lifetime ratio, and stay neutral only for an article nobody has
		// ever rated, which is the one case where 70 actually means "unknown".
		$reactions     = $happy + $sad;
		$reactions_all = $happy_all + $sad_all;
		if ( $reactions > 0 ) {
			$helpfulness = ( $happy / $reactions ) * 100;
		} elseif ( $reactions_all > 0 ) {
			$helpfulness = ( $happy_all / $reactions_all ) * 100;
		} else {
			$helpfulness = 70.0;
		}

		// Freshness: decays ~8 pts/month from the last modification.
		$modified  = strtotime( $post->post_modified_gmt . ' UTC' );
		$months    = $modified ? ( ( time() - $modified ) / MONTH_IN_SECONDS ) : 0;
		$freshness = max( 0, 100 - ( $months * 8 ) );

		// Findability: does it actually get found? Scales recent views toward a
		// benchmark (filterable). Baseline 40 so zero-traffic isn't a hard zero.
		$benchmark   = max( 1, (int) apply_filters( 'betterdocs_health_view_benchmark', 50 ) );
		$findability = $views > 0 ? min( 100, 40 + 60 * min( 1, $views / $benchmark ) ) : 40.0;

		// Accuracy: reuse the AI Quality Score when present, else neutral 75; then
		// subtract a broken-link penalty (up to 20).
		$quality = get_post_meta( $post_id, '_betterdocs_article_quality_analysis', true );
		$accuracy = ( is_array( $quality ) && isset( $quality['overall_score'] ) )
			? (float) $quality['overall_score']
			: 75.0;
		$broken = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}betterdocs_analytics_links
				WHERE post_id = %d AND is_broken = 1",
				$post_id
			)
		);
		$accuracy = max( 0, $accuracy - min( 20, $broken * 5 ) );

		return [
			'engagement'  => round( min( 100, $engagement ), 2 ),
			'helpfulness' => round( min( 100, $helpfulness ), 2 ),
			'freshness'   => round( min( 100, $freshness ), 2 ),
			'findability' => round( min( 100, $findability ), 2 ),
			'accuracy'    => round( min( 100, $accuracy ), 2 ),
		];
	}

	/**
	 * Roll the per-doc health rows into one score + component averages.
	 *
	 * `$scope` is an optional, already-prepared SQL fragment (" AND object_id IN
	 * ( … )") narrowing the rows to one knowledge base — it contains no `%`
	 * placeholders, so it is safe to concatenate. Returns null when no health rows
	 * match, which callers read as "nothing scored yet" rather than a score of 0.
	 *
	 * Public because the Health endpoint computes a selected KB's headline score
	 * live from the current rows instead of reading a snapshot (see
	 * AnalyticsReports::health_site_score()), and both paths must average the
	 * same way.
	 *
	 * @return array{score:float,components:array,docs:int}|null
	 */
	public function aggregate( $scope = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		$agg = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $scope is prepared by the caller; $table is a prefix concat.
			"SELECT COUNT(*) AS docs, AVG( score ) AS score,
				AVG( CAST( JSON_EXTRACT( payload, '$.components.engagement' ) AS DECIMAL(6,2) ) ) AS engagement,
				AVG( CAST( JSON_EXTRACT( payload, '$.components.helpfulness' ) AS DECIMAL(6,2) ) ) AS helpfulness,
				AVG( CAST( JSON_EXTRACT( payload, '$.components.freshness' ) AS DECIMAL(6,2) ) ) AS freshness,
				AVG( CAST( JSON_EXTRACT( payload, '$.components.findability' ) AS DECIMAL(6,2) ) ) AS findability,
				AVG( CAST( JSON_EXTRACT( payload, '$.components.accuracy' ) AS DECIMAL(6,2) ) ) AS accuracy
			FROM {$table} WHERE type = 'health'{$scope}"
		);

		if ( ! $agg || (int) $agg->docs === 0 ) {
			return null;
		}

		return [
			'score'      => round( (float) $agg->score, 1 ),
			'components' => [
				'engagement'  => round( (float) $agg->engagement, 1 ),
				'helpfulness' => round( (float) $agg->helpfulness, 1 ),
				'freshness'   => round( (float) $agg->freshness, 1 ),
				'findability' => round( (float) $agg->findability, 1 ),
				'accuracy'    => round( (float) $agg->accuracy, 1 ),
			],
			'docs'       => (int) $agg->docs,
		];
	}

	/**
	 * Roll the per-doc health rows into one site snapshot and append it to the
	 * history option (keyed by date, so a same-day re-run overwrites).
	 */
	protected function snapshot_site_health() {
		$snapshot = $this->aggregate();

		if ( null === $snapshot ) {
			return;
		}

		// Per-KB snapshots alongside the site one, under a 'kbs' key mapping term
		// slug → the same shape. The trend and the month-over-month delta are the
		// only two numbers the Health screen cannot recompute live from today's
		// rows, so without this a knowledge base could never show either. Cost is
		// one aggregate per knowledge_base term, once a night.
		$kbs = $this->kb_snapshots();
		if ( ! empty( $kbs ) ) {
			$snapshot['kbs'] = $kbs;
		}

		$history = get_option( self::HISTORY_OPT, [] );
		if ( ! is_array( $history ) ) {
			$history = [];
		}
		$history[ gmdate( 'Y-m-d' ) ] = $snapshot;

		// Keep the trailing HISTORY_MAX days.
		if ( count( $history ) > self::HISTORY_MAX ) {
			$history = array_slice( $history, -self::HISTORY_MAX, null, true );
		}

		update_option( self::HISTORY_OPT, $history, false );
	}

	/**
	 * One aggregate per knowledge base, keyed by term slug — the slug being what
	 * the `kb` request parameter carries everywhere else in Analytics.
	 *
	 * Scoping goes through the post taxonomy rather than the insight row's kb_id
	 * column (which the local scanners leave at 0): a doc that belongs to two
	 * knowledge bases then counts toward both, which a single column could not
	 * express. Empty when Multiple Knowledge Base is off — the taxonomy is not
	 * registered, so get_terms() returns a WP_Error.
	 *
	 * @return array<string,array{score:float,components:array,docs:int}>
	 */
	protected function kb_snapshots() {
		$terms = get_terms( [ 'taxonomy' => 'knowledge_base', 'hide_empty' => false ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		global $wpdb;
		$out = [];

		foreach ( $terms as $term ) {
			$scope = $wpdb->prepare(
				" AND object_id IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d )",
				(int) $term->term_taxonomy_id
			);
			$agg = $this->aggregate( $scope );
			if ( null !== $agg ) {
				$out[ $term->slug ] = $agg;
			}
		}

		return $out;
	}

	/**
	 * Letter grade for a 0–100 health score. Band edges match the design's
	 * "78 → B" calibration.
	 */
	public function grade( $score ) {
		if ( $score >= 90 ) {
			return 'A';
		}
		if ( $score >= 75 ) {
			return 'B';
		}
		if ( $score >= 60 ) {
			return 'C';
		}
		if ( $score >= 40 ) {
			return 'D';
		}
		return 'F';
	}

	protected function primary_category( $post_id ) {
		$terms = get_the_terms( $post_id, 'doc_category' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$first = reset( $terms );
			return $first ? $first->name : '';
		}
		return '';
	}
}
