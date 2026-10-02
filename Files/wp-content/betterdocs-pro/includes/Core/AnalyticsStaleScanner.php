<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Stale Content detection (Content Intelligence, Analytics v2.0).
 *
 * A nightly Action Scheduler job walks published docs in chunks and scores each
 * one for staleness (0–100, higher = more stale) from signals BetterDocs already
 * collects — no LLM, no API key. Qualifying docs are written as `type = 'stale'`
 * rows into betterdocs_analytics_insights; docs that are no longer stale have
 * their auto-generated ('new') rows pruned at the end of a full pass.
 *
 * Staleness is a weighted sum of:
 *   - age      : months since the article was last modified          (≤ 40)
 *   - decline  : drop in views, recent 30d vs the prior 30d          (≤ 25)
 *   - reaction : share of unhappy reactions in the recent window     (≤ 15)
 *   - broken   : broken outbound links (from the Link Health scan)   (≤ 10)
 *   - ai_drop  : drop in AI-agent fetches, recent 30d vs prior 30d   (≤ 10)
 *
 * Contradiction-with-newer-docs (the STALE_FLAGS `contradict` flag) is a
 * cloud-corroborated P1 signal handled in Phase 2, not here.
 */
class AnalyticsStaleScanner {
	const START_HOOK  = 'betterdocs_analytics_stale_scan_start';
	const RUN_HOOK    = 'betterdocs_analytics_stale_scan_run';
	const OFFSET_OPT  = 'betterdocs_analytics_stale_scan_offset';
	const STARTED_OPT = 'betterdocs_analytics_stale_scan_started';
	const LAST_OPT    = 'betterdocs_analytics_stale_scan_last';
	const CHUNK       = 25;

	/** Below this score an article is healthy enough to not be flagged. */
	const MIN_STALE_SCORE = 30;

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
			as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::START_HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Begin a full pass: stamp the start time (used to prune no-longer-stale rows
	 * afterwards), reset the cursor, enqueue the first chunk.
	 */
	public function start_scan() {
		if ( function_exists( 'as_next_scheduled_action' ) && false !== as_next_scheduled_action( self::RUN_HOOK, [], 'betterdocs' ) ) {
			return; // A pass is already walking — don't stack a second.
		}
		update_option( self::STARTED_OPT, current_time( 'mysql', true ) );
		update_option( self::OFFSET_OPT, 0 );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::RUN_HOOK, [], 'betterdocs' );
		} else {
			$this->run_chunk();
		}
	}

	/**
	 * Score one chunk of docs, then re-enqueue until the KB is exhausted. On the
	 * final chunk, prune stale 'new' rows that this pass did not refresh (they are
	 * no longer stale).
	 */
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
	 * Close out a full pass: drop 'new' stale rows not refreshed since the pass
	 * began (their articles are no longer stale), then clear the cursor.
	 */
	protected function finish_pass() {
		global $wpdb;

		$started = (string) get_option( self::STARTED_OPT, '' );
		if ( $started !== '' ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}betterdocs_analytics_insights
					WHERE type = 'stale' AND status = 'new' AND updated_at < %s",
					$started
				)
			);
		}

		delete_option( self::OFFSET_OPT );
		delete_option( self::STARTED_OPT );
		update_option( self::LAST_OPT, time() );
	}

	/**
	 * Score a single doc and upsert (or leave absent) its stale insight.
	 */
	public function score_and_store( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return;
		}

		$result = $this->score_post( $post );

		// Below threshold → not stale. Any prior 'new' row is pruned in finish_pass
		// (it simply won't be refreshed this pass).
		if ( $result['score'] < self::MIN_STALE_SCORE ) {
			return;
		}

		$this->store->upsert(
			'stale',
			$this->store->object_hash( 'stale', $post_id ),
			$result['score'],
			[
				'post_id'      => $post_id,
				'title'        => get_the_title( $post_id ),
				'category'     => $this->primary_category( $post_id ),
				'staleness'    => $result['score'],
				'flags'        => $result['flags'],
				'last_updated' => human_time_diff( strtotime( $post->post_modified_gmt . ' UTC' ), time() ) . ' ago',
				'modified_gmt' => $post->post_modified_gmt,
				'trend_dir'    => $result['trend_dir'],
				'spark'        => $result['spark'],
				'permalink'    => get_permalink( $post_id ),
				'edit_link'    => get_edit_post_link( $post_id, 'raw' ),
			],
			[ 'object_id' => $post_id, 'source' => 'local' ]
		);
	}

	/**
	 * Compute the staleness score, contributing flags, and a 6-point view
	 * sparkline for one post. Returns [ score, flags[], trend_dir, spark[] ].
	 */
	public function score_post( $post ) {
		global $wpdb;

		$post_id = (int) $post->ID;
		$flags   = [];
		$score   = 0.0;

		// --- age (≤ 40) ---
		$modified = strtotime( $post->post_modified_gmt . ' UTC' );
		$months   = $modified ? ( ( time() - $modified ) / MONTH_IN_SECONDS ) : 0;
		if ( $months > 3 ) {
			$score += min( 40, ( ( $months - 3 ) / 15 ) * 40 );
		}
		if ( $months >= 8 ) {
			$flags[] = 'age';
		} elseif ( $months >= 6 ) {
			$flags[] = 'age6';
		}

		$daily = $wpdb->prefix . 'betterdocs_analytics_daily';

		// --- view decline (≤ 25): recent 30d vs prior 30d ---
		$recent_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( views ), 0 ) FROM {$daily}
				WHERE post_id = %d AND stat_date >= %s",
				$post_id,
				gmdate( 'Y-m-d', strtotime( '-30 days' ) )
			)
		);
		$prior_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( views ), 0 ) FROM {$daily}
				WHERE post_id = %d AND stat_date >= %s AND stat_date < %s",
				$post_id,
				gmdate( 'Y-m-d', strtotime( '-60 days' ) ),
				gmdate( 'Y-m-d', strtotime( '-30 days' ) )
			)
		);
		$trend_dir = 0;
		if ( $prior_views >= 5 && $recent_views < $prior_views ) {
			$drop  = ( $prior_views - $recent_views ) / $prior_views;
			$score += min( 25, $drop * 25 );
			$trend_dir = -1;
			if ( $drop >= 0.3 ) {
				$flags[] = 'decline';
			}
		} elseif ( $recent_views > $prior_views ) {
			$trend_dir = 1;
		}

		// --- unhappy reactions (≤ 15): recent 30d ---
		$reactions = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( happy ), 0 ) AS happy,
					COALESCE( SUM( sad ), 0 ) AS sad,
					COALESCE( SUM( normal ), 0 ) AS normal
				FROM {$daily}
				WHERE post_id = %d AND stat_date >= %s",
				$post_id,
				gmdate( 'Y-m-d', strtotime( '-30 days' ) )
			)
		);
		if ( $reactions ) {
			$total = (int) $reactions->happy + (int) $reactions->sad + (int) $reactions->normal;
			if ( $total >= 3 ) {
				$sad_ratio = (int) $reactions->sad / $total;
				$score    += min( 15, $sad_ratio * 30 );
			}
		}

		// --- broken links (≤ 10) ---
		$broken = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}betterdocs_analytics_links
				WHERE post_id = %d AND is_broken = 1",
				$post_id
			)
		);
		if ( $broken > 0 ) {
			$score  += min( 10, $broken * 5 );
			$flags[] = 'broken';
		}

		// --- AI-fetch decline (≤ 10): recent 30d vs prior 30d ---
		$ai = $wpdb->prefix . 'betterdocs_analytics_ai_daily';
		$recent_ai = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( fetches ), 0 ) FROM {$ai}
				WHERE post_id = %d AND stat_date >= %s",
				$post_id,
				gmdate( 'Y-m-d', strtotime( '-30 days' ) )
			)
		);
		$prior_ai = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( fetches ), 0 ) FROM {$ai}
				WHERE post_id = %d AND stat_date >= %s AND stat_date < %s",
				$post_id,
				gmdate( 'Y-m-d', strtotime( '-60 days' ) ),
				gmdate( 'Y-m-d', strtotime( '-30 days' ) )
			)
		);
		if ( $prior_ai >= 5 && $recent_ai < $prior_ai ) {
			$score += min( 10, ( ( $prior_ai - $recent_ai ) / $prior_ai ) * 10 );
		}

		// --- outdated version mention (opt-in via filter) ---
		// Sites expose their current product version(s) through the filter; a doc
		// that mentions only an older x.y version is flagged. Off by default.
		$current_version = (string) apply_filters( 'betterdocs_stale_current_version', '' );
		if ( $current_version !== '' && $this->mentions_outdated_version( $post->post_content, $current_version ) ) {
			$score  += 8;
			$flags[] = 'version';
		}

		return [
			'score'     => (float) min( 100, round( $score, 2 ) ),
			'flags'     => array_values( array_unique( $flags ) ),
			'trend_dir' => $trend_dir,
			'spark'     => $this->view_sparkline( $post_id ),
		];
	}

	/**
	 * True when the content references an x.y version lower than $current (major
	 * then minor compare) and never references the current one.
	 */
	protected function mentions_outdated_version( $content, $current ) {
		if ( ! preg_match_all( '/\b(\d+)\.(\d+)(?:\.\d+)?\b/', (string) $content, $m, PREG_SET_ORDER ) ) {
			return false;
		}
		if ( ! preg_match( '/^(\d+)\.(\d+)/', $current, $cur ) ) {
			return false;
		}
		$cur_key      = (int) $cur[1] * 1000 + (int) $cur[2];
		$has_current  = false;
		$has_outdated = false;
		foreach ( $m as $v ) {
			$key = (int) $v[1] * 1000 + (int) $v[2];
			if ( $key === $cur_key ) {
				$has_current = true;
			} elseif ( $key < $cur_key ) {
				$has_outdated = true;
			}
		}
		return $has_outdated && ! $has_current;
	}

	/**
	 * Six weekly view buckets (oldest → newest) for the row sparkline.
	 *
	 * @return int[]
	 */
	protected function view_sparkline( $post_id ) {
		global $wpdb;
		$daily = $wpdb->prefix . 'betterdocs_analytics_daily';
		$spark = [];
		for ( $week = 5; $week >= 0; $week-- ) {
			$start   = gmdate( 'Y-m-d', strtotime( '-' . ( ( $week + 1 ) * 7 ) . ' days' ) );
			$end     = gmdate( 'Y-m-d', strtotime( '-' . ( $week * 7 ) . ' days' ) );
			$spark[] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE( SUM( views ), 0 ) FROM {$daily}
					WHERE post_id = %d AND stat_date >= %s AND stat_date < %s",
					$post_id,
					$start,
					$end
				)
			);
		}
		return $spark;
	}

	/**
	 * Human-readable primary doc_category name (first assigned), or ''.
	 */
	protected function primary_category( $post_id ) {
		$terms = get_the_terms( $post_id, 'doc_category' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$first = reset( $terms );
			return $first ? $first->name : '';
		}
		return '';
	}
}
