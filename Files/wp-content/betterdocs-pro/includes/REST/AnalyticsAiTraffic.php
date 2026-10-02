<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WP_REST_Response;
use WPDeveloper\BetterDocsPro\Core\AiTrafficCollector;

/**
 * Read API for the AI Traffic dashboard (Advanced Analytics v1.5).
 *
 *   GET /analytics/ai-traffic?days=N
 *
 * Aggregates {prefix}betterdocs_analytics_ai_daily (written by
 * Core\AiTrafficCollector) against the human rollup in
 * {prefix}betterdocs_analytics_daily into the single payload the AI Traffic
 * tab renders: KPI totals + deltas, per-agent stats, AI-vs-human daily series,
 * the 7×24 activity heatmap, the most-fetched docs table, and the composite
 * AI Visibility Score.
 *
 * Extends AnalyticsReports for the shared date-window resolution and the RBAC
 * permission model (read_docs_analytics / analytics_roles).
 */
// Dashboard reads over prepared aggregate queries — no caching by design.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
class AnalyticsAiTraffic extends AnalyticsReports {
	/** Most-fetched docs rows returned (the view paginates client-side). */
	const PAGES_LIMIT = 50;

	/** External llms.txt / robots.txt checks are cached this long. */
	const CHECKS_TTL = 12 * HOUR_IN_SECONDS;

	public function register() {
		$this->get( '/analytics/ai-traffic', [ $this, 'report' ], [ 'days' => $this->days_arg() ] );

		// Manual "Rescan" — bust the 12h llms.txt/robots transient and re-run the
		// checks now, so publishing llms.txt or editing robots.txt reflects without
		// waiting out CHECKS_TTL. Mirrors /analytics/links/scan; POST is gated by
		// permission_check() behind edit_docs_settings. (#7)
		$this->post( '/analytics/ai-traffic/rescan', [ $this, 'rescan' ], [] );
	}

	/**
	 * Force-refresh the AI Visibility site checks (llms.txt + robots.txt) and
	 * return the fresh result. User-triggered, so the synchronous loopback
	 * fetches here are acceptable (unlike the report path — see warm_site_checks).
	 */
	public function rescan() {
		$checks = self::warm_site_checks();
		return new WP_REST_Response( [ 'ok' => true, 'checks' => $checks ], 200 );
	}

	public function report( WP_REST_Request $request ) {
		global $wpdb;

		list( $start, $end, $days ) = $this->resolve_window( $request );
		$prev_start = gmdate( 'Y-m-d', strtotime( "-{$days} days", strtotime( $start ) ) );

		$ai_table    = $wpdb->prefix . 'betterdocs_analytics_ai_daily';
		$daily_table = $wpdb->prefix . 'betterdocs_analytics_daily';

		$catalog    = AiTrafficCollector::catalog();
		$agent_meta = [];
		foreach ( $catalog as $agent ) {
			$agent_meta[ $agent['id'] ] = $agent;
		}

		// ---- Totals (current + previous window) ----

		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( fetches ), 0 ) AS fetches,
					COUNT( DISTINCT agent ) AS agents_seen,
					COUNT( DISTINCT post_id ) AS unique_docs
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date <= %s",
				$start,
				$end
			),
			ARRAY_A
		);
		$prev_totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( fetches ), 0 ) AS fetches, COUNT( DISTINCT agent ) AS agents_seen
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date < %s",
				$prev_start,
				$start
			),
			ARRAY_A
		);

		$human_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( views ), 0 ) FROM {$daily_table} WHERE stat_date >= %s AND stat_date <= %s",
				$start,
				$end
			)
		);
		$prev_human_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( views ), 0 ) FROM {$daily_table} WHERE stat_date >= %s AND stat_date < %s",
				$prev_start,
				$start
			)
		);

		$ai_fetches  = (int) ( $totals['fetches'] ?? 0 );
		$agents_seen = (int) ( $totals['agents_seen'] ?? 0 );
		$unique_docs = (int) ( $totals['unique_docs'] ?? 0 );

		$ratio      = $human_views > 0 ? round( $ai_fetches / $human_views, 2 ) : null;
		$prev_ratio = $prev_human_views > 0 ? round( (int) $prev_totals['fetches'] / $prev_human_views, 2 ) : null;

		// Percentage change vs the previous window; null when there is no baseline.
		$delta = function ( $current, $previous ) {
			$previous = (float) $previous;
			if ( $previous <= 0 ) {
				return null;
			}
			return round( ( ( (float) $current - $previous ) / $previous ) * 100, 1 );
		};

		// ---- Per-agent stats ----

		$agent_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT agent, SUM( fetches ) AS fetches, COUNT( DISTINCT post_id ) AS unique_pages, MAX( last_fetch ) AS last_fetch
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date <= %s
				GROUP BY agent ORDER BY fetches DESC",
				$start,
				$end
			)
		);

		$prev_agent_map = [];
		$prev_agent_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT agent, SUM( fetches ) AS fetches FROM {$ai_table} WHERE stat_date >= %s AND stat_date < %s GROUP BY agent",
				$prev_start,
				$start
			)
		);
		foreach ( (array) $prev_agent_rows as $row ) {
			$prev_agent_map[ $row->agent ] = (int) $row->fetches;
		}

		// One query yields both the per-agent sparklines and the AI daily series.
		$daily_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT agent, stat_date, SUM( fetches ) AS fetches
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date <= %s
				GROUP BY agent, stat_date",
				$start,
				$end
			)
		);
		$agent_daily = [];
		$ai_daily    = [];
		foreach ( (array) $daily_rows as $row ) {
			$agent_daily[ $row->agent ][ $row->stat_date ] = (int) $row->fetches;
			$ai_daily[ $row->stat_date ] = ( $ai_daily[ $row->stat_date ] ?? 0 ) + (int) $row->fetches;
		}

		$human_daily_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT stat_date, SUM( views ) AS views FROM {$daily_table} WHERE stat_date >= %s AND stat_date <= %s GROUP BY stat_date",
				$start,
				$end
			)
		);
		$human_daily = [];
		foreach ( (array) $human_daily_rows as $row ) {
			$human_daily[ $row->stat_date ] = (int) $row->views;
		}

		// Zero-filled window dates drive the series and every agent spark.
		$window_dates = [];
		$cursor = strtotime( $start );
		$end_ts = strtotime( $end );
		while ( $cursor <= $end_ts ) {
			$window_dates[] = gmdate( 'Y-m-d', $cursor );
			$cursor        += DAY_IN_SECONDS;
		}

		$series = [];
		foreach ( $window_dates as $d ) {
			$series[] = [
				'date'  => $d,
				'ai'    => $ai_daily[ $d ] ?? 0,
				'human' => $human_daily[ $d ] ?? 0
			];
		}

		$now_gmt    = time();
		$agents     = [];
		$last_fetch = null; // newest hit across all agents
		foreach ( (array) $agent_rows as $row ) {
			if ( ! isset( $agent_meta[ $row->agent ] ) ) {
				continue; // agent since removed from the catalog
			}
			$meta  = $agent_meta[ $row->agent ];
			$spark = [];
			foreach ( $window_dates as $d ) {
				$spark[] = $agent_daily[ $row->agent ][ $d ] ?? 0;
			}

			$seen_ts   = $row->last_fetch ? strtotime( $row->last_fetch . ' UTC' ) : 0;
			$last_seen = $seen_ts
				/* translators: %s: human-readable time difference, e.g. "3 mins". */
				? sprintf( __( '%s ago', 'betterdocs-pro' ), human_time_diff( $seen_ts, $now_gmt ) )
				: '';

			$agents[] = [
				'id'           => $meta['id'],
				'name'         => $meta['name'],
				'company'      => $meta['company'],
				'category'     => $meta['category'],
				'color'        => $meta['color'],
				'ua'           => $meta['ua'],
				'fetches'      => (int) $row->fetches,
				'unique_pages' => (int) $row->unique_pages,
				'trend'        => $delta( (int) $row->fetches, $prev_agent_map[ $row->agent ] ?? 0 ),
				'last_seen'    => $last_seen,
				'spark'        => $spark
			];

			if ( $seen_ts && ( $last_fetch === null || $seen_ts > $last_fetch['ts'] ) ) {
				$last_fetch = [
					'ts'    => $seen_ts,
					'agent' => $meta['name'],
					'ago'   => $last_seen
				];
			}
		}
		if ( $last_fetch !== null ) {
			unset( $last_fetch['ts'] );
		}

		// agents_seen must match the catalog-filtered agent list + donut built from
		// $agents. The COUNT(DISTINCT agent) totals above also count agents since
		// removed from the catalog, so the KPI would disagree with the table after a
		// catalog/filter change — recount from the rendered set instead. (#13)
		$agents_seen      = count( $agents );
		$prev_agents_seen = count( array_intersect_key( $prev_agent_map, $agent_meta ) );

		// ---- Activity heatmap (7 days × 24 hours, UTC; WEEKDAY() is 0 = Monday,
		// matching the dashboard's Mon-first row order) ----

		$heat_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT agent, WEEKDAY( stat_date ) AS dow, stat_hour, SUM( fetches ) AS fetches
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date <= %s
				GROUP BY agent, dow, stat_hour",
				$start,
				$end
			)
		);
		$empty_grid = array_fill( 0, 7, array_fill( 0, 24, 0 ) );
		$heatmap    = [ 'all' => $empty_grid, 'agents' => [] ];
		foreach ( (array) $heat_rows as $row ) {
			$d = (int) $row->dow;
			$h = (int) $row->stat_hour;
			if ( $d < 0 || $d > 6 || $h < 0 || $h > 23 || ! isset( $agent_meta[ $row->agent ] ) ) {
				continue;
			}
			if ( ! isset( $heatmap['agents'][ $row->agent ] ) ) {
				$heatmap['agents'][ $row->agent ] = $empty_grid;
			}
			$heatmap['all'][ $d ][ $h ]                    += (int) $row->fetches;
			$heatmap['agents'][ $row->agent ][ $d ][ $h ] += (int) $row->fetches;
		}

		// ---- Most-fetched docs ----

		$pages = $this->most_fetched_pages( $start, $end, $prev_start, $agent_meta, $delta );

		// ---- Composite AI Visibility Score ----

		$visibility = $this->visibility_score(
			$catalog,
			$unique_docs,
			$agents_seen,
			// Previous-window inputs for the "vs prev" points delta.
			(int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT( DISTINCT post_id ) FROM {$ai_table} WHERE stat_date >= %s AND stat_date < %s",
					$prev_start,
					$start
				)
			),
			$prev_agents_seen
		);

		return new WP_REST_Response(
			[
				'days'       => $days,
				'window'     => [ 'start' => $start, 'end' => $end ],
				'totals'     => [
					'ai_fetches'  => $ai_fetches,
					'human_views' => $human_views,
					'share_pct'   => ( $ai_fetches + $human_views ) > 0
						? round( $ai_fetches / ( $ai_fetches + $human_views ) * 100, 1 )
						: 0,
					'ratio'       => $ratio,
					'agents_seen' => $agents_seen,
					'unique_docs' => $unique_docs
				],
				'delta'      => [
					'ai_fetches'  => $delta( $ai_fetches, $prev_totals['fetches'] ?? 0 ),
					'agents_seen' => $delta( $agents_seen, $prev_agents_seen ),
					'ratio'       => ( $ratio !== null && $prev_ratio !== null ) ? $delta( $ratio, $prev_ratio ) : null
				],
				'last_fetch' => $last_fetch,
				'agents'     => $agents,
				'series'     => $series,
				'heatmap'    => $heatmap,
				'pages'      => $pages,
				'visibility' => $visibility
			],
			200
		);
	}

	/**
	 * Top docs by AI fetches in the window, each with its top fetching agent,
	 * windowed human views, AI:human ratio, and trend vs the previous window.
	 *
	 * @param string   $start      Window start (Y-m-d, inclusive).
	 * @param string   $end        Window end (Y-m-d, inclusive).
	 * @param string   $prev_start Previous-window start.
	 * @param array    $agent_meta Catalog keyed by agent id.
	 * @param callable $delta      Percentage-change helper.
	 * @return array[]
	 */
	protected function most_fetched_pages( $start, $end, $prev_start, $agent_meta, $delta ) {
		global $wpdb;

		$ai_table = $wpdb->prefix . 'betterdocs_analytics_ai_daily';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM( fetches ) AS fetches
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date <= %s
				GROUP BY post_id ORDER BY fetches DESC LIMIT %d",
				$start,
				$end,
				self::PAGES_LIMIT
			)
		);
		if ( empty( $rows ) ) {
			return [];
		}

		$post_ids = array_map( 'intval', wp_list_pluck( $rows, 'post_id' ) );
		$ids_in   = implode( ',', $post_ids );

		// Prime post + term caches once for all rows so the per-doc loop below
		// (get_post_type / get_the_title / get_the_terms) hits cache instead of
		// firing N+1 queries across up to PAGES_LIMIT docs. (#9)
		_prime_post_caches( $post_ids, false, false );
		update_object_term_cache( $post_ids, 'docs' );

		// Top fetching agent per doc.
		$top_agent_map = [];
		$per_agent_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, agent, SUM( fetches ) AS fetches
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date <= %s AND post_id IN ({$ids_in})
				GROUP BY post_id, agent",
				$start,
				$end
			)
		);
		foreach ( (array) $per_agent_rows as $row ) {
			$pid = (int) $row->post_id;
			if ( ! isset( $top_agent_map[ $pid ] ) || (int) $row->fetches > $top_agent_map[ $pid ]['fetches'] ) {
				$top_agent_map[ $pid ] = [ 'agent' => $row->agent, 'fetches' => (int) $row->fetches ];
			}
		}

		// Windowed human views for the same docs.
		$human_map = [];
		$human_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM( views ) AS views
				FROM {$wpdb->prefix}betterdocs_analytics_daily
				WHERE stat_date >= %s AND stat_date <= %s AND post_id IN ({$ids_in})
				GROUP BY post_id",
				$start,
				$end
			)
		);
		foreach ( (array) $human_rows as $row ) {
			$human_map[ (int) $row->post_id ] = (int) $row->views;
		}

		// Previous-window fetches for the per-doc trend.
		$prev_map = [];
		$prev_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM( fetches ) AS fetches
				FROM {$ai_table} WHERE stat_date >= %s AND stat_date < %s AND post_id IN ({$ids_in})
				GROUP BY post_id",
				$prev_start,
				$start
			)
		);
		foreach ( (array) $prev_rows as $row ) {
			$prev_map[ (int) $row->post_id ] = (int) $row->fetches;
		}

		$pages = [];
		foreach ( $rows as $row ) {
			$pid = (int) $row->post_id;
			if ( 'docs' !== get_post_type( $pid ) ) {
				continue; // deleted or repurposed since the fetches were recorded
			}

			$terms    = get_the_terms( $pid, 'doc_category' );
			$category = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';

			$top_meta = null;
			if ( isset( $top_agent_map[ $pid ] ) && isset( $agent_meta[ $top_agent_map[ $pid ]['agent'] ] ) ) {
				$m        = $agent_meta[ $top_agent_map[ $pid ]['agent'] ];
				$top_meta = [
					'id'       => $m['id'],
					'name'     => $m['name'],
					'company'  => $m['company'],
					'category' => $m['category'],
					'color'    => $m['color']
				];
			}

			$human   = $human_map[ $pid ] ?? 0;
			$pages[] = [
				'post_id'     => $pid,
				'title'       => get_the_title( $pid ),
				'category'    => $category,
				'ai_fetches'  => (int) $row->fetches,
				'human_views' => $human,
				'ratio'       => $human > 0 ? round( (int) $row->fetches / $human, 2 ) : null,
				'trend'       => $delta( (int) $row->fetches, $prev_map[ $pid ] ?? 0 ),
				'top_agent'   => $top_meta
			];
		}

		return $pages;
	}

	/**
	 * Composite AI Visibility Score (0–100 + grade), from five equally weighted
	 * signals. The two site checks (llms.txt / robots.txt) hit the site's own
	 * front and are transient-cached; the rest read data already in hand.
	 *
	 * @param array $catalog          Agent catalog.
	 * @param int   $unique_docs      Distinct docs fetched by AI in the window.
	 * @param int   $agents_seen      Distinct agents seen in the window.
	 * @param int   $prev_unique_docs Same, previous window.
	 * @param int   $prev_agents_seen Same, previous window.
	 * @return array { score, grade, delta, breakdown }
	 */
	protected function visibility_score( $catalog, $unique_docs, $agents_seen, $prev_unique_docs, $prev_agents_seen ) {
		$checks = self::site_checks( $catalog );

		$published = (int) wp_count_posts( 'docs' )->publish;
		$fresh     = 0;
		if ( $published > 0 ) {
			$fresh = (int) ( new \WP_Query( [
				'post_type'      => 'docs',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'date_query'     => [
					[ 'column' => 'post_modified_gmt', 'after' => '180 days ago' ]
				]
			] ) )->found_posts;
		}

		$pct = function ( $part, $whole ) {
			return $whole > 0 ? min( 100, (int) round( $part / $whole * 100 ) ) : 0;
		};

		$agent_count = max( 1, count( $catalog ) );
		$score_of    = function ( $depth, $coverage ) use ( $checks, $pct, $fresh, $published ) {
			$parts = [
				$checks['llms_txt'] ? 100 : 0,
				$checks['robots_score'],
				$pct( $fresh, $published ),
				$depth,
				$coverage
			];
			return (int) round( array_sum( $parts ) / count( $parts ) );
		};

		$depth      = $pct( $unique_docs, $published );
		$coverage   = $pct( $agents_seen, $agent_count );
		$score      = $score_of( $depth, $coverage );
		$prev_score = $score_of( $pct( $prev_unique_docs, $published ), $pct( $prev_agents_seen, $agent_count ) );

		$grade = 'F';
		foreach ( [ 'A' => 85, 'B' => 70, 'C' => 55, 'D' => 40 ] as $g => $min ) {
			if ( $score >= $min ) {
				$grade = $g;
				break;
			}
		}

		// Generic, plugin-agnostic recommendations per low signal. BetterDocs
		// deliberately does not generate llms.txt / edit robots.txt — that space
		// belongs to SEO plugins; we measure and point the user there.
		$freshness = $pct( $fresh, $published );
		$blocked   = (int) ( $checks['blocked'] ?? 0 );

		return [
			'score'     => $score,
			'grade'     => $grade,
			'delta'     => $score - $prev_score,
			'breakdown' => [
				[
					'label'  => __( 'llms.txt published', 'betterdocs-pro' ),
					'value'  => $checks['llms_txt'] ? 100 : 0,
					'weight' => 20,
					'hint'   => $checks['llms_txt'] ? null : __( 'No llms.txt file found. Publish one — most SEO plugins can generate it — so AI agents can discover your docs.', 'betterdocs-pro' )
				],
				[
					'label'  => __( 'Robots.txt friendliness', 'betterdocs-pro' ),
					'value'  => $checks['robots_score'],
					'weight' => 20,
					'hint'   => $checks['robots_score'] >= 100 ? null : sprintf(
						/* translators: %d: number of AI agents fully blocked by robots.txt. */
						_n(
							'Your robots.txt fully blocks %d known AI agent. Review its rules (e.g. with your SEO plugin) if that is unintended.',
							'Your robots.txt fully blocks %d known AI agents. Review its rules (e.g. with your SEO plugin) if that is unintended.',
							$blocked,
							'betterdocs-pro'
						),
						$blocked
					)
				],
				[
					'label'  => __( 'Content freshness', 'betterdocs-pro' ),
					'value'  => $freshness,
					'weight' => 20,
					'hint'   => $freshness >= 50 ? null : sprintf(
						/* translators: %d: percentage of docs updated in the last 6 months. */
						__( 'Only %d%% of docs were updated in the last 6 months. AI crawlers re-fetch fresh content more often.', 'betterdocs-pro' ),
						$freshness
					)
				],
				[
					'label'  => __( 'AI fetch depth', 'betterdocs-pro' ),
					'value'  => $depth,
					'weight' => 20,
					'hint'   => $depth >= 30 ? null : sprintf(
						/* translators: %d: percentage of published docs fetched by AI agents. */
						__( 'AI agents reached only %d%% of your published docs. An llms.txt index and internal links help them discover more.', 'betterdocs-pro' ),
						$depth
					)
				],
				[
					'label'  => __( 'Agent coverage', 'betterdocs-pro' ),
					'value'  => $coverage,
					'weight' => 20,
					'hint'   => $coverage >= 40 ? null : sprintf(
						/* translators: 1: number of AI agents seen, 2: total known AI agents. */
						__( 'Only %1$d of %2$d known AI agents visited in this window. A public llms.txt and a permissive robots.txt attract more.', 'betterdocs-pro' ),
						$agents_seen,
						$agent_count
					)
				]
			]
		];
	}

	/**
	 * llms.txt presence + robots.txt AI-friendliness, transient-cached because
	 * both require an HTTP request against the site's own front.
	 *
	 * Static + no instance state, so the always-loaded AiTrafficCollector can warm
	 * the transient off a cron tick (see warm_site_checks / #10) without spinning up
	 * a REST controller.
	 *
	 * @param array $catalog Agent catalog (for the robots.txt block scan).
	 * @return array { llms_txt: bool, robots_score: int, blocked: int }
	 */
	protected static function site_checks( $catalog ) {
		return self::run_site_checks( $catalog );
	}

	/**
	 * Force-refresh the AI Visibility site checks, bypassing the transient, and
	 * cache the fresh result. Called by the twice-daily cron (AiTrafficCollector
	 * schedules 'betterdocs_warm_ai_visibility_checks') so the report path never
	 * pays the ~1s loopback-fetch cost lazily (#10), and by the manual Rescan (#7).
	 *
	 * @return array { llms_txt: bool, robots_score: int, blocked: int }
	 */
	public static function warm_site_checks() {
		delete_transient( 'betterdocs_ai_visibility_checks' );
		return self::run_site_checks( AiTrafficCollector::catalog() );
	}

	/**
	 * @param array $catalog Agent catalog (for the robots.txt block scan).
	 * @return array { llms_txt: bool, robots_score: int, blocked: int }
	 */
	protected static function run_site_checks( $catalog ) {
		$cached = get_transient( 'betterdocs_ai_visibility_checks' );
		if ( is_array( $cached ) && isset( $cached['llms_txt'], $cached['robots_score'], $cached['blocked'] ) ) {
			return $cached;
		}

		$llms     = wp_remote_head( home_url( '/llms.txt' ), [ 'timeout' => 5, 'redirection' => 2 ] );
		$llms_txt = ! is_wp_error( $llms ) && 200 === wp_remote_retrieve_response_code( $llms );

		// Count catalog agents that a robots.txt block fully disallows.
		$robots_score = 100;
		$blocked      = 0;
		$robots       = wp_remote_get( home_url( '/robots.txt' ), [ 'timeout' => 5, 'redirection' => 2 ] );
		if ( ! is_wp_error( $robots ) && 200 === wp_remote_retrieve_response_code( $robots ) ) {
			$body    = (string) wp_remote_retrieve_body( $robots );
			$lines   = preg_split( '/\r\n|\r|\n/', $body );
			$current = []; // agent ids named by the User-agent lines of the open block
			foreach ( $lines as $line ) {
				$line = trim( preg_replace( '/#.*$/', '', $line ) );
				if ( $line === '' ) {
					continue;
				}
				if ( preg_match( '/^user-agent\s*:\s*(.+)$/i', $line, $m ) ) {
					$token = trim( $m[1] );
					foreach ( $catalog as $agent ) {
						foreach ( (array) $agent['match'] as $needle ) {
							if ( false !== stripos( $token, rtrim( $needle, '/' ) ) ) {
								$current[ $agent['id'] ] = true;
								break;
							}
						}
					}
				} elseif ( preg_match( '/^disallow\s*:\s*\/\s*$/i', $line ) ) {
					$blocked += count( $current );
					$current  = [];
				} elseif ( preg_match( '/^(allow|disallow)\s*:/i', $line ) ) {
					// A scoped rule closes the pending block without a full block.
					$current = [];
				}
			}
			$robots_score = max( 0, 100 - (int) round( $blocked / max( 1, count( $catalog ) ) * 100 ) );
		}

		$checks = [ 'llms_txt' => $llms_txt, 'robots_score' => $robots_score, 'blocked' => $blocked ];
		set_transient( 'betterdocs_ai_visibility_checks', $checks, self::CHECKS_TTL );

		return $checks;
	}
}
