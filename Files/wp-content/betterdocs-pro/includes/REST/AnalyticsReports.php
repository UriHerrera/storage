<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WP_REST_Response;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocsPro\Core\AiTrafficCollector;

/**
 * Read API for the Advanced Analytics v1.0 dashboards (Pro).
 *
 * New endpoints under betterdocs/v1/analytics/* that read the aggregated tables
 * with $wpdb->prepare (deliberately NOT the esc_sql style of the legacy
 * REST/Analytics class). Dashboards read aggregated tables only — never the raw
 * events table.
 *
 *   GET /analytics/summary?days=N            KPI strip
 *   GET /analytics/articles?days=N&...        paginated top articles
 *   GET /analytics/articles/{id}?days=N       per-article daily series (drill-down)
 *
 * Search / engagement / feedback / link endpoints ship with their module units.
 */
class AnalyticsReports extends BaseAPI {
	public function register() {
		$this->get( '/analytics', [ $this, 'index' ], [] );

		$this->get( '/analytics/summary', [ $this, 'summary' ], [ 'days' => $this->days_arg(), 'kb' => $this->kb_arg() ] );

		$this->get(
			'/analytics/articles',
			[ $this, 'articles' ],
			[
				'days'     => $this->days_arg(),
				'kb'       => $this->kb_arg(),
				'per_page' => [
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => function ( $p ) {
						$p = (int) $p;
						return ( $p >= 1 && $p <= 100 ) ? $p : 20;
					}
				],
				'page'     => [
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => function ( $p ) {
						$p = (int) $p;
						return $p >= 1 ? $p : 1;
					}
				]
			]
		);

		$this->get(
			'/analytics/articles/(?P<id>\d+)',
			[ $this, 'article_detail' ],
			[
				'days' => $this->days_arg(),
				'id'   => [
					'type'              => 'integer',
					'required'          => true,
					'validate_callback' => function ( $p ) {
						return ! empty( $p ) && is_numeric( $p );
					}
				]
			]
		);

		// Article Performance sub-tabs: docs by category / by knowledge base.
		// Same paginated, date-windowed shape as /analytics/articles.
		$leading_args = array_merge(
			[ 'days' => $this->days_arg(), 'kb' => $this->kb_arg() ],
			$this->pagination_args()
		);
		$this->get( '/analytics/categories', [ $this, 'categories' ], $leading_args );
		$this->get( '/analytics/knowledge-bases', [ $this, 'knowledge_bases' ], $leading_args );

		// Reactions: happy/neutral/unhappy KPIs + Most/Least Helpful docs.
		$this->get(
			'/analytics/reactions',
			[ $this, 'reactions' ],
			array_merge(
				[
					'days'  => $this->days_arg(),
					'kb'    => $this->kb_arg(),
					'order' => [
						'type'              => 'string',
						'default'           => 'most',
						'sanitize_callback' => function ( $p ) {
							return $p === 'least' ? 'least' : 'most';
						}
					]
				],
				$this->pagination_args()
			)
		);

		$this->get(
			'/analytics/search',
			[ $this, 'search' ],
			array_merge(
				[
					'days'  => $this->days_arg(),
					'scope' => [
						'type'              => 'string',
						'default'           => 'all',
						'sanitize_callback' => function ( $p ) {
							return $p === 'zero' ? 'zero' : 'all';
						}
					]
				],
				$this->pagination_args()
			)
		);

		$this->get( '/analytics/engagement', [ $this, 'engagement' ], [ 'days' => $this->days_arg(), 'kb' => $this->kb_arg() ] );

		$this->get(
			'/analytics/feedback',
			[ $this, 'feedback' ],
			[
				'days'     => $this->days_arg(),
				'kb'       => $this->kb_arg(),
				'status'   => [ 'type' => 'string', 'default' => '' ],
				'feeling'  => [ 'type' => 'string', 'default' => '' ],
				'per_page' => [
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => function ( $p ) {
						$p = (int) $p;
						return ( $p >= 1 && $p <= 100 ) ? $p : 20;
					}
				],
				'page'     => [
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => function ( $p ) {
						$p = (int) $p;
						return $p >= 1 ? $p : 1;
					}
				]
			]
		);

		$this->post(
			'/analytics/feedback/bulk',
			[ $this, 'feedback_bulk' ],
			[
				'ids'    => [ 'type' => 'array', 'required' => true, 'items' => [ 'type' => 'integer' ] ],
				'status' => [
					'type'              => 'string',
					'required'          => true,
					'validate_callback' => function ( $p ) {
						return in_array( $p, [ 'new', 'reviewed', 'resolved' ], true );
					}
				]
			]
		);

		$this->get(
			'/analytics/links',
			[ $this, 'links' ],
			[
				'kb'       => $this->kb_arg(),
				'per_page' => [
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => function ( $p ) {
						$p = (int) $p;
						return ( $p >= 1 && $p <= 100 ) ? $p : 20;
					}
				],
				'page'     => [
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => function ( $p ) {
						$p = (int) $p;
						return $p >= 1 ? $p : 1;
					}
				]
			]
		);

		$this->post( '/analytics/links/scan', [ $this, 'links_scan' ], [] );

		// GeoIP status + on-demand database download (settings-tab "Download now").
		$this->get( '/analytics/geoip/status', [ $this, 'geoip_status' ], [] );
		$this->post( '/analytics/geoip/refresh', [ $this, 'geoip_refresh' ], [] );

		// GA4 "send test event" — definitive connection check for the MP method.
		$this->post( '/analytics/ga4/test', [ $this, 'ga4_test' ], [] );

		$this->get( '/analytics/authors', [ $this, 'authors' ], [ 'days' => $this->days_arg(), 'kb' => $this->kb_arg() ] );

		$this->get(
			'/analytics/export/(?P<module>[a-z_]+)',
			[ $this, 'export' ],
			[
				'days'   => $this->days_arg(),
				'kb'     => $this->kb_arg(),
				'module' => [
					'type'              => 'string',
					'required'          => true,
					'validate_callback' => function ( $p ) {
						return in_array( $p, [ 'overview', 'articles', 'categories', 'knowledge_bases', 'reactions', 'search', 'feedback', 'links', 'authors', 'engagement', 'ai' ], true );
					}
				]
			]
		);

		// ---- Content Intelligence v2.0 ----
		// Read the rule-based local modules (Stale, Health) plus a combined
		// overview. Gaps/Duplicates land here in Phase 2 (cloud-sourced rows).
		$this->get( '/analytics/insights/overview', [ $this, 'insights_overview' ], [ 'kb' => $this->kb_arg() ] );
		$this->get(
			'/analytics/insights/stale',
			[ $this, 'insights_stale' ],
			array_merge(
				[
					'kb'   => $this->kb_arg(),
					'sort' => [
						'type'              => 'string',
						'default'           => 'staleness',
						'sanitize_callback' => function ( $p ) {
							return in_array( $p, [ 'staleness', 'updated' ], true ) ? $p : 'staleness';
						}
					]
				],
				$this->pagination_args()
			)
		);
		// Pagination applies to the underperformers table inside the response; the
		// score, trend and distribution it also returns are page-independent.
		$this->get( '/analytics/insights/health', [ $this, 'insights_health' ], array_merge( [ 'kb' => $this->kb_arg() ], $this->pagination_args() ) );
		// Cloud modules (populated by ContentIntelligenceService from the AI service).
		$this->get( '/analytics/insights/gaps', [ $this, 'insights_gaps' ], array_merge( [ 'kb' => $this->kb_arg() ], $this->pagination_args() ) );
		$this->get( '/analytics/insights/duplicates', [ $this, 'insights_duplicates' ], [ 'kb' => $this->kb_arg() ] );
		$this->post( '/analytics/insights/sync', [ $this, 'insights_sync' ], [] );
		$this->post(
			'/analytics/insights/gaps/(?P<id>\d+)/draft',
			[ $this, 'insight_gap_draft' ],
			[
				'id' => [
					'type'              => 'integer',
					'required'          => true,
					'validate_callback' => function ( $p ) {
						return ! empty( $p ) && is_numeric( $p );
					}
				]
			]
		);
		$this->post(
			'/analytics/insights/(?P<id>\d+)/status',
			[ $this, 'insight_status' ],
			[
				'id'     => [
					'type'              => 'integer',
					'required'          => true,
					'validate_callback' => function ( $p ) {
						return ! empty( $p ) && is_numeric( $p );
					}
				],
				'status' => [
					'type'              => 'string',
					'required'          => true,
					'validate_callback' => function ( $p ) {
						return in_array( $p, [ 'new', 'accepted', 'dismissed', 'done' ], true );
					}
				]
			]
		);
	}

	public function permission_check( $request = null ) {
		// Mutations (e.g. feedback bulk status, link scan) need an editor capability;
		// reads honor the analytics read capability + the analytics_roles setting.
		if ( $request instanceof WP_REST_Request && $request->get_method() !== 'GET' ) {
			return current_user_can( 'edit_docs_settings' );
		}
		return $this->can_read();
	}

	/**
	 * Read access (RBAC): the read_docs_analytics cap, an admin, or a role
	 * listed in the `analytics_roles` setting.
	 */
	protected function can_read() {
		if ( current_user_can( 'read_docs_analytics' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}
		$roles = (array) betterdocs()->settings->get( 'analytics_roles', [] );
		if ( empty( $roles ) || ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		return ! empty( array_intersect( $roles, (array) $user->roles ) );
	}

	/**
	 * Read-only API discovery: lists the available report endpoints.
	 */
	public function index() {
		return new WP_REST_Response(
			[
				'version'   => '1.0',
				'readonly'  => true,
				'endpoints' => [
					'summary', 'articles', 'articles/{id}', 'search',
					'engagement', 'feedback', 'links', 'authors', 'export/{module}'
				]
			],
			200
		);
	}

	protected function days_arg() {
		return [
			// No strict `integer` type — the "All time" preset sends days=all,
			// which is mapped to the full data span in resolve_window().
			'default'           => 30,
			'sanitize_callback' => function ( $p ) {
				if ( 'all' === $p ) {
					return 'all';
				}
				$p = (int) $p;
				// Pro retains 12 months.
				return ( $p >= 1 && $p <= 365 ) ? $p : 30;
			}
		];
	}

	protected function cutoff_date( $days ) {
		// Inclusive window: [ today - (days-1) .. today ] spans exactly $days days.
		// Using -$days here made the current window N+1 days while the previous
		// window (prev_cutoff .. cutoff, half-open) stayed N — biasing every delta.
		return gmdate( 'Y-m-d', strtotime( '-' . ( (int) $days - 1 ) . ' days' ) );
	}

	/**
	 * Earliest recorded date across the analytics + search-log tables — the lower
	 * bound for the "All time" window. Falls back to 30 days ago when empty.
	 */
	protected function earliest_date() {
		global $wpdb;
		$a = $wpdb->get_var( "SELECT MIN( created_at ) FROM {$wpdb->prefix}betterdocs_analytics" );
		$s = $wpdb->get_var( "SELECT MIN( created_at ) FROM {$wpdb->prefix}betterdocs_search_log" );
		$dates = array_filter( [ $a, $s ], function ( $d ) {
			return $d && '0000-00-00' !== $d;
		} );
		return empty( $dates ) ? gmdate( 'Y-m-d', strtotime( '-30 days' ) ) : min( $dates );
	}

	/**
	 * YYYY-MM-DD or '' — guards the custom-range params before they touch SQL.
	 */
	protected function sanitize_date( $value ) {
		$value = (string) $value;
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	/**
	 * Resolve the active date window for a request: returns [ $start, $end, $days ]
	 * where $start/$end are inclusive Y-m-d bounds and $days is the inclusive day
	 * span (used for trend loops and the previous-window delta).
	 *
	 * A valid start_date + end_date pair (the "Custom range" calendar) wins;
	 * otherwise the `days` preset (last N days, ending today) is used — so every
	 * dashboard endpoint becomes range-aware through a single call. start_date /
	 * end_date are read raw and sanitized here, so routes need not register them.
	 */
	protected function resolve_window( WP_REST_Request $request ) {
		$start = $this->sanitize_date( $request->get_param( 'start_date' ) );
		$end   = $this->sanitize_date( $request->get_param( 'end_date' ) );

		if ( $start && $end ) {
			if ( $start > $end ) {
				$tmp = $start; $start = $end; $end = $tmp;
			}
			// +1 so the span is INCLUSIVE, matching the shared ">= start AND <= end"
			// SQL bounds — otherwise the current window is one day longer than the
			// previous window (start - $days), re-introducing the #8 delta bias for
			// custom ranges. Mirrors the "all time" branch below.
			$days = (int) round( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) + 1;
			return [ $start, $end, max( 1, $days ) ];
		}

		// "All time" — span from the earliest recorded data through today, so the
		// shared `created_at >= start AND <= end` clauses impose no real lower bound.
		if ( 'all' === $request->get_param( 'days' ) ) {
			$start = $this->earliest_date();
			$end   = gmdate( 'Y-m-d' );
			$days  = max( 1, (int) round( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) + 1 );
			return [ $start, $end, $days ];
		}

		$days = (int) $request->get_param( 'days' );
		if ( $days < 1 || $days > 365 ) {
			$days = 30;
		}
		return [ $this->cutoff_date( $days ), gmdate( 'Y-m-d' ), $days ];
	}

	/**
	 * Shared per_page (1–100, default 20) + page (>=1) arg schema for the
	 * paginated list endpoints.
	 */
	private function pagination_args() {
		return [
			'per_page' => [
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => function ( $p ) {
					$p = (int) $p;
					return ( $p >= 1 && $p <= 100 ) ? $p : 20;
				}
			],
			'page'     => [
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => function ( $p ) {
					$p = (int) $p;
					return $p >= 1 ? $p : 1;
				}
			]
		];
	}

	private function kb_arg() {
		// Wrap sanitize_title in a closure: WP invokes sanitize_callback as
		// ( $value, $request, $param ), and sanitize_title's 2nd arg is a fallback
		// title — passing $request there returns the request object for empty input.
		return [
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => function ( $p ) {
				return sanitize_title( (string) $p );
			}
		];
	}

	/**
	 * Leading SQL fragment ( " AND <col> IN ( … )" ) constraining a post-id column
	 * to docs in the given knowledge_base slug — used by the Knowledge Base header
	 * filter (only present when Multiple Knowledge Base is enabled). Returns '' when
	 * no KB is selected or the slug is unknown, so callers concatenate it
	 * unconditionally. The fragment resolves to a literal integer term_taxonomy_id
	 * (no `%` placeholders left), so it is safe to embed inside another prepare().
	 * `$col` is always a hardcoded column name, never user input.
	 */
	private function kb_filter( $kb, $col = 'post_id' ) {
		if ( empty( $kb ) ) {
			return '';
		}
		$term = get_term_by( 'slug', $kb, 'knowledge_base' );
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}
		global $wpdb;
		return $wpdb->prepare(
			" AND {$col} IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d )",
			(int) $term->term_taxonomy_id
		);
	}

	public function summary( WP_REST_Request $request ) {
		global $wpdb;

		list( $cutoff, $end, $days ) = $this->resolve_window( $request );
		// Knowledge Base filter (post-scoped). Search totals stay global — the
		// search log is keyword-scoped and has no post/KB association.
		$kb = $this->kb_filter( (string) $request->get_param( 'kb' ) );

		$views = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( views ), 0 ) AS total_views, COALESCE( SUM( unique_views ), 0 ) AS total_unique_views
				FROM {$wpdb->prefix}betterdocs_analytics_daily
				WHERE stat_date >= %s AND stat_date <= %s{$kb}",
				$cutoff,
				$end
			),
			ARRAY_A
		);

		// Reactions still live in the legacy aggregate table.
		$reactions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( happy + sad + normal ), 0 ) FROM {$wpdb->prefix}betterdocs_analytics WHERE created_at >= %s AND created_at <= %s{$kb}",
				$cutoff,
				$end
			)
		);

		$search = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( count ), 0 ) AS total_searches, COALESCE( SUM( not_found_count ), 0 ) AS total_not_found
				FROM {$wpdb->prefix}betterdocs_search_log WHERE created_at >= %s AND created_at <= %s",
				$cutoff,
				$end
			),
			ARRAY_A
		);

		// AI-agent fetches (v1.5) — windowed total for the Overview "Human + AI" lens.
		$ai_fetches = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( fetches ), 0 ) FROM {$wpdb->prefix}betterdocs_analytics_ai_daily WHERE stat_date >= %s AND stat_date <= %s",
				$cutoff,
				$end
			)
		);

		// Per-day series powering the KPI card sparklines (zero-filled over the window).
		$views_daily = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT stat_date AS d, COALESCE( SUM( views ), 0 ) AS views, COALESCE( SUM( unique_views ), 0 ) AS uniques
				FROM {$wpdb->prefix}betterdocs_analytics_daily WHERE stat_date >= %s AND stat_date <= %s{$kb} GROUP BY stat_date",
				$cutoff,
				$end
			),
			ARRAY_A
		);
		$search_daily = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE( created_at ) AS d, COALESCE( SUM( count ), 0 ) AS c
				FROM {$wpdb->prefix}betterdocs_search_log WHERE created_at >= %s AND created_at <= %s GROUP BY DATE( created_at )",
				$cutoff,
				$end
			),
			ARRAY_A
		);
		$reactions_daily = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE( created_at ) AS d, COALESCE( SUM( happy + sad + normal ), 0 ) AS c
				FROM {$wpdb->prefix}betterdocs_analytics WHERE created_at >= %s AND created_at <= %s{$kb} GROUP BY DATE( created_at )",
				$cutoff,
				$end
			),
			ARRAY_A
		);

		$views_map = $uniques_map = $search_map = $reactions_map = [];
		foreach ( (array) $views_daily as $r ) {
			$views_map[ $r['d'] ]   = (int) $r['views'];
			$uniques_map[ $r['d'] ] = (int) $r['uniques'];
		}
		foreach ( (array) $search_daily as $r ) {
			$search_map[ $r['d'] ] = (int) $r['c'];
		}
		foreach ( (array) $reactions_daily as $r ) {
			$reactions_map[ $r['d'] ] = (int) $r['c'];
		}

		$spark_views = $spark_uniques = $spark_searches = $spark_reactions = [];
		$trend  = [];
		$cursor = strtotime( $cutoff );
		$end_ts = strtotime( $end );
		while ( $cursor <= $end_ts ) {
			$d                 = gmdate( 'Y-m-d', $cursor );
			$spark_views[]     = $views_map[ $d ] ?? 0;
			$spark_uniques[]   = $uniques_map[ $d ] ?? 0;
			$spark_searches[]  = $search_map[ $d ] ?? 0;
			$spark_reactions[] = $reactions_map[ $d ] ?? 0;
			// Date-labeled series for the Trends charts (Article Performance, Overview).
			$trend[] = [
				'date'      => $d,
				'views'     => $views_map[ $d ] ?? 0,
				'uniques'   => $uniques_map[ $d ] ?? 0,
				'searches'  => $search_map[ $d ] ?? 0,
				'reactions' => $reactions_map[ $d ] ?? 0
			];
			$cursor += DAY_IN_SECONDS;
		}

		// Previous equal-length window (immediately before $cutoff), for the
		// "vs prev" delta on each KPI card.
		$prev_cutoff = gmdate( 'Y-m-d', strtotime( "-{$days} days", strtotime( $cutoff ) ) );

		$prev_views = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( views ), 0 ) AS total_views, COALESCE( SUM( unique_views ), 0 ) AS total_unique_views
				FROM {$wpdb->prefix}betterdocs_analytics_daily WHERE stat_date >= %s AND stat_date < %s{$kb}",
				$prev_cutoff,
				$cutoff
			),
			ARRAY_A
		);
		$prev_reactions = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( happy + sad + normal ), 0 ) FROM {$wpdb->prefix}betterdocs_analytics WHERE created_at >= %s AND created_at < %s{$kb}",
				$prev_cutoff,
				$cutoff
			)
		);
		$prev_searches = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( count ), 0 ) FROM {$wpdb->prefix}betterdocs_search_log WHERE created_at >= %s AND created_at < %s",
				$prev_cutoff,
				$cutoff
			)
		);
		$prev_ai_fetches = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( fetches ), 0 ) FROM {$wpdb->prefix}betterdocs_analytics_ai_daily WHERE stat_date >= %s AND stat_date < %s",
				$prev_cutoff,
				$cutoff
			)
		);

		// Percentage change vs the previous window; null when there is no baseline.
		$delta = function ( $current, $previous ) {
			$previous = (int) $previous;
			if ( $previous <= 0 ) {
				return null;
			}
			return round( ( ( (int) $current - $previous ) / $previous ) * 100, 1 );
		};

		return new WP_REST_Response(
			[
				'days' => $days,
				'kpis' => [
					'total_views'        => (int) ( $views['total_views'] ?? 0 ),
					'total_unique_views' => (int) ( $views['total_unique_views'] ?? 0 ),
					'total_reactions'    => (int) $reactions,
					'total_searches'     => (int) ( $search['total_searches'] ?? 0 ),
					'total_not_found'    => (int) ( $search['total_not_found'] ?? 0 ),
					'total_ai_fetches'   => $ai_fetches
				],
				'spark' => [
					'total_views'        => $spark_views,
					'total_unique_views' => $spark_uniques,
					'total_searches'     => $spark_searches,
					'total_reactions'    => $spark_reactions
				],
				'delta' => [
					'total_views'        => $delta( $views['total_views'] ?? 0, $prev_views['total_views'] ?? 0 ),
					'total_unique_views' => $delta( $views['total_unique_views'] ?? 0, $prev_views['total_unique_views'] ?? 0 ),
					'total_searches'     => $delta( $search['total_searches'] ?? 0, $prev_searches ),
					'total_reactions'    => $delta( $reactions, $prev_reactions ),
					'total_ai_fetches'   => $delta( $ai_fetches, $prev_ai_fetches )
				],
				'trend' => $trend
			],
			200
		);
	}

	public function articles( WP_REST_Request $request ) {
		global $wpdb;

		list( $start, $end, $days ) = $this->resolve_window( $request );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;
		$kb       = $this->kb_filter( (string) $request->get_param( 'kb' ) );

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT post_id ) FROM {$wpdb->prefix}betterdocs_analytics_daily WHERE stat_date >= %s AND stat_date <= %s{$kb}",
				$start,
				$end
			)
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM( views ) AS views, SUM( unique_views ) AS unique_views
				FROM {$wpdb->prefix}betterdocs_analytics_daily
				WHERE stat_date >= %s AND stat_date <= %s{$kb}
				GROUP BY post_id
				ORDER BY views DESC
				LIMIT %d OFFSET %d",
				$start,
				$end,
				$per_page,
				$offset
			)
		);

		// Reactions live in the legacy aggregate (same source as summary/authors).
		// Fetch them only for this page's posts in one query, then merge.
		$reactions_map = [];
		$post_ids = array_map( 'intval', wp_list_pluck( (array) $rows, 'post_id' ) );
		if ( ! empty( $post_ids ) ) {
			$ids_in = implode( ',', $post_ids );
			$reaction_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, SUM( happy + sad + normal ) AS reactions
					FROM {$wpdb->prefix}betterdocs_analytics
					WHERE created_at >= %s AND created_at <= %s AND post_id IN ({$ids_in})
					GROUP BY post_id",
					$start,
					$end
				)
			);
			foreach ( (array) $reaction_rows as $rr ) {
				$reactions_map[ (int) $rr->post_id ] = (int) $rr->reactions;
			}
		}

		$items = [];
		foreach ( (array) $rows as $row ) {
			$post_id = (int) $row->post_id;
			$items[] = [
				'post_id'      => $post_id,
				'title'        => get_the_title( $post_id ),
				'permalink'    => get_permalink( $post_id ),
				'status'       => get_post_status( $post_id ),
				'views'        => (int) $row->views,
				'unique_views' => (int) $row->unique_views,
				'reactions'    => $reactions_map[ $post_id ] ?? 0
			];
		}

		return new WP_REST_Response(
			[
				'days'     => $days,
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => $total,
				'items'    => $items
			],
			200
		);
	}

	/**
	 * Shared engine for the Article Performance "Categories" and "Knowledge Base"
	 * sub-tabs: aggregate analytics_daily by the doc's terms in $taxonomy, paged
	 * and date-windowed exactly like articles(). Joining through term_relationships
	 * (not analytics_daily.kb_id, which is 0 for rows backfilled from older
	 * versions) keeps historical/migrated data visible.
	 *
	 * @param string $taxonomy  'doc_category' or 'knowledge_base'.
	 * @param bool   $apply_kb  Honor the KB header filter (categories) vs. ignore
	 *                          it (the KB tab is itself a per-KB breakdown).
	 */
	private function leading_terms( WP_REST_Request $request, $taxonomy, $apply_kb = true ) {
		global $wpdb;

		list( $start, $end ) = $this->resolve_window( $request );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;
		$kb       = $apply_kb ? $this->kb_filter( (string) $request->get_param( 'kb' ), 'd.post_id' ) : '';

		$daily = $wpdb->prefix . 'betterdocs_analytics_daily';
		// Published docs only — matches the old dashboard's definition so term
		// totals don't include drafts/trashed/deleted docs (QA #15).
		$from  = "FROM {$daily} d
			JOIN {$wpdb->posts} p ON p.ID = d.post_id AND p.post_status = 'publish'
			JOIN {$wpdb->term_relationships} tr ON tr.object_id = d.post_id
			JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
			WHERE d.stat_date >= %s AND d.stat_date <= %s{$kb}";

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT( DISTINCT tt.term_id ) {$from}", $taxonomy, $start, $end )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id AS term_id,
					SUM( d.views ) AS views,
					SUM( d.unique_views ) AS unique_views
				{$from}
				GROUP BY tt.term_id
				ORDER BY views DESC
				LIMIT %d OFFSET %d",
				$taxonomy,
				$start,
				$end,
				$per_page,
				$offset
			)
		);

		// Reactions come from the LIVE legacy aggregate — analytics_daily's
		// happy/sad/normal are only ever written by the one-time backfill (the
		// aggregator never updates them), so sourcing them there would freeze
		// term reactions at the update snapshot (QA #15; same pattern articles()
		// already uses).
		$kb_legacy     = $apply_kb ? $this->kb_filter( (string) $request->get_param( 'kb' ), 'a.post_id' ) : '';
		$reaction_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id AS term_id, SUM( a.happy + a.sad + a.normal ) AS reactions
				FROM {$wpdb->prefix}betterdocs_analytics a
				JOIN {$wpdb->posts} p ON p.ID = a.post_id AND p.post_status = 'publish'
				JOIN {$wpdb->term_relationships} tr ON tr.object_id = a.post_id
				JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
				WHERE a.created_at >= %s AND a.created_at <= %s{$kb_legacy}
				GROUP BY tt.term_id",
				$taxonomy,
				$start,
				$end
			)
		);
		$reactions_map = [];
		foreach ( (array) $reaction_rows as $r ) {
			$reactions_map[ (int) $r->term_id ] = (int) $r->reactions;
		}

		$items = [];
		foreach ( (array) $rows as $row ) {
			$term = get_term( (int) $row->term_id );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$link = get_term_link( $term );
			$items[] = [
				'term_id'      => (int) $row->term_id,
				'name'         => $term->name,
				'slug'         => $term->slug,
				'link'         => is_wp_error( $link ) ? '' : $link,
				'views'        => (int) $row->views,
				'unique_views' => (int) $row->unique_views,
				'reactions'    => isset( $reactions_map[ (int) $row->term_id ] ) ? $reactions_map[ (int) $row->term_id ] : 0
			];
		}

		return new WP_REST_Response(
			[
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => $total,
				'items'    => $items
			],
			200
		);
	}

	/** Article Performance → Categories sub-tab (doc_category breakdown). */
	public function categories( WP_REST_Request $request ) {
		return $this->leading_terms( $request, 'doc_category', true );
	}

	/** Article Performance → Knowledge Base sub-tab (knowledge_base breakdown). */
	public function knowledge_bases( WP_REST_Request $request ) {
		return $this->leading_terms( $request, 'knowledge_base', false );
	}

	/**
	 * Reactions analytics: happy / neutral / unhappy KPI totals + a paginated docs
	 * list ranked by helpfulness. `order=most` (default) ranks by happy reactions,
	 * `order=least` by unhappy (sad) — mirroring the legacy Most/Least Helpful tabs.
	 * Reactions are read from the legacy aggregate (same source as summary/authors).
	 */
	public function reactions( WP_REST_Request $request ) {
		global $wpdb;

		list( $start, $end, $days ) = $this->resolve_window( $request );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;
		$least    = ( $request->get_param( 'order' ) === 'least' );
		$kb       = $this->kb_filter( (string) $request->get_param( 'kb' ) );

		$table = $wpdb->prefix . 'betterdocs_analytics';

		// KPI totals over the window (order-independent).
		$kpis = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( happy ), 0 ) AS happy, COALESCE( SUM( normal ), 0 ) AS normal, COALESCE( SUM( sad ), 0 ) AS sad
				FROM {$table} WHERE created_at >= %s AND created_at <= %s{$kb}",
				$start,
				$end
			),
			ARRAY_A
		);

		// Per-day series powering each KPI card's sparkline (zero-filled over the window).
		$daily = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at AS d, COALESCE( SUM( happy ), 0 ) AS h, COALESCE( SUM( normal ), 0 ) AS n, COALESCE( SUM( sad ), 0 ) AS s
				FROM {$table} WHERE created_at >= %s AND created_at <= %s{$kb} GROUP BY created_at",
				$start,
				$end
			),
			ARRAY_A
		);
		$hm = $nm = $sm = [];
		foreach ( (array) $daily as $r ) {
			$hm[ $r['d'] ] = (int) $r['h'];
			$nm[ $r['d'] ] = (int) $r['n'];
			$sm[ $r['d'] ] = (int) $r['s'];
		}
		$spark_happy = $spark_normal = $spark_sad = [];
		$trend  = [];
		$cursor = strtotime( $start );
		$end_ts = strtotime( $end );
		while ( $cursor <= $end_ts ) {
			$d              = gmdate( 'Y-m-d', $cursor );
			$spark_happy[]  = $hm[ $d ] ?? 0;
			$spark_normal[] = $nm[ $d ] ?? 0;
			$spark_sad[]    = $sm[ $d ] ?? 0;
			// Date-labeled series for the Reactions Trends chart.
			$trend[] = [
				'date'   => $d,
				'happy'  => $hm[ $d ] ?? 0,
				'normal' => $nm[ $d ] ?? 0,
				'sad'    => $sm[ $d ] ?? 0
			];
			$cursor        += DAY_IN_SECONDS;
		}

		// Previous equal-length window (immediately before $start) for the "vs prev" delta.
		$prev_cutoff = gmdate( 'Y-m-d', strtotime( "-{$days} days", strtotime( $start ) ) );
		$prev = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( happy ), 0 ) AS happy, COALESCE( SUM( normal ), 0 ) AS normal, COALESCE( SUM( sad ), 0 ) AS sad
				FROM {$table} WHERE created_at >= %s AND created_at < %s{$kb}",
				$prev_cutoff,
				$start
			),
			ARRAY_A
		);
		$pct = function ( $current, $previous ) {
			$previous = (int) $previous;
			if ( $previous <= 0 ) {
				return null;
			}
			return round( ( ( (int) $current - $previous ) / $previous ) * 100, 1 );
		};

		// Rank + filter column are hardcoded (never user input): happy for Most
		// Helpful, sad for Least Helpful; only docs with that reaction are listed.
		$rank_col = $least ? 'sad' : 'happy';

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM (
					SELECT post_id FROM {$table}
					WHERE created_at >= %s AND created_at <= %s{$kb}
					GROUP BY post_id
					HAVING SUM( {$rank_col} ) > 0
				) t",
				$start,
				$end
			)
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM( happy ) AS happy, SUM( normal ) AS normal, SUM( sad ) AS sad,
					SUM( happy + normal + sad ) AS total
				FROM {$table}
				WHERE created_at >= %s AND created_at <= %s{$kb}
				GROUP BY post_id
				HAVING SUM( {$rank_col} ) > 0
				ORDER BY {$rank_col} DESC
				LIMIT %d OFFSET %d",
				$start,
				$end,
				$per_page,
				$offset
			)
		);

		$items = [];
		foreach ( (array) $rows as $row ) {
			$post_id = (int) $row->post_id;
			$items[] = [
				'post_id'   => $post_id,
				'title'     => get_the_title( $post_id ),
				'permalink' => get_permalink( $post_id ),
				'status'    => get_post_status( $post_id ),
				'happy'     => (int) $row->happy,
				'normal'    => (int) $row->normal,
				'sad'       => (int) $row->sad,
				'total'     => (int) $row->total
			];
		}

		return new WP_REST_Response(
			[
				'kpis'     => [
					'happy'  => (int) ( $kpis['happy'] ?? 0 ),
					'normal' => (int) ( $kpis['normal'] ?? 0 ),
					'sad'    => (int) ( $kpis['sad'] ?? 0 )
				],
				'spark'    => [
					'happy'  => $spark_happy,
					'normal' => $spark_normal,
					'sad'    => $spark_sad
				],
				'delta'    => [
					'happy'  => $pct( $kpis['happy'] ?? 0, $prev['happy'] ?? 0 ),
					'normal' => $pct( $kpis['normal'] ?? 0, $prev['normal'] ?? 0 ),
					'sad'    => $pct( $kpis['sad'] ?? 0, $prev['sad'] ?? 0 )
				],
				'trend'    => $trend,
				'order'    => $least ? 'least' : 'most',
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => $total,
				'items'    => $items
			],
			200
		);
	}

	public function article_detail( WP_REST_Request $request ) {
		global $wpdb;

		list( $cutoff, $end, $days ) = $this->resolve_window( $request );
		$post_id = (int) $request->get_param( 'id' );
		$end_dt  = $end . ' 23:59:59';

		// Only expose analytics for actual docs — never let an arbitrary post/CPT
		// id surface another object's title, status, or feedback comments.
		if ( 'docs' !== get_post_type( $post_id ) ) {
			return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}

		$series = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT stat_date, SUM( views ) AS views, SUM( unique_views ) AS unique_views,
					AVG( avg_scroll_depth ) AS avg_scroll_depth, SUM( reading_completions ) AS reading_completions
				FROM {$wpdb->prefix}betterdocs_analytics_daily
				WHERE post_id = %d AND stat_date >= %s AND stat_date <= %s
				GROUP BY stat_date
				ORDER BY stat_date ASC",
				$post_id,
				$cutoff,
				$end
			)
		);

		// Reactions (happy/sad/normal) live in the legacy aggregate table — the
		// daily rollup never populates those columns — so read them from
		// betterdocs_analytics keyed by day, matching every other endpoint
		// (summary / authors / reactions). Without this the drawer showed 0.
		$reactions_by_date = [];
		$reaction_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE( created_at ) AS stat_date,
					SUM( happy ) AS likes, SUM( sad ) AS dislikes, SUM( normal ) AS neutral
				FROM {$wpdb->prefix}betterdocs_analytics
				WHERE post_id = %d AND created_at >= %s AND created_at <= %s
				GROUP BY DATE( created_at )",
				$post_id,
				$cutoff,
				$end_dt
			)
		);
		foreach ( (array) $reaction_rows as $rrow ) {
			$reactions_by_date[ $rrow->stat_date ] = [ (int) $rrow->likes, (int) $rrow->dislikes, (int) $rrow->neutral ];
		}

		$trend = [];
		$reactions_trend = [];
		$total_views = 0;
		$total_unique = 0;
		$total_completions = 0;
		$total_likes = 0;
		$total_dislikes = 0;
		$total_neutral = 0;
		$scroll_sum = 0.0;
		$scroll_days = 0;
		foreach ( (array) $series as $row ) {
			$v = (int) $row->views;
			$u = (int) $row->unique_views;
			list( $l, $d, $n ) = $reactions_by_date[ $row->stat_date ] ?? [ 0, 0, 0 ];
			$total_views      += $v;
			$total_unique     += $u;
			$total_likes      += $l;
			$total_dislikes   += $d;
			$total_neutral    += $n;
			$total_completions += (int) $row->reading_completions;
			if ( (float) $row->avg_scroll_depth > 0 ) {
				$scroll_sum += (float) $row->avg_scroll_depth;
				$scroll_days++;
			}
			$trend[] = [
				'date'         => $row->stat_date,
				'views'        => $v,
				'unique_views' => $u
			];
			$reactions_trend[] = [
				'date'     => $row->stat_date,
				'likes'    => $l,
				'dislikes' => $d,
				'neutral'  => $n
			];
		}

		// Country / device breakdown + raw referrers from the events payload.
		// payload is JSON: {"referrer": "...", "device": "...", "country": "XX"}.
		$events_table = $wpdb->prefix . 'betterdocs_analytics_events';
		$breakdown_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					JSON_UNQUOTE( JSON_EXTRACT( payload, '$.country' ) ) AS country,
					JSON_UNQUOTE( JSON_EXTRACT( payload, '$.device' ) )  AS device,
					JSON_UNQUOTE( JSON_EXTRACT( payload, '$.referrer' ) ) AS referrer
				FROM {$events_table}
				WHERE object_id = %d AND created_at >= %s AND created_at <= %s",
				$post_id,
				$cutoff,
				$end_dt
			),
			ARRAY_A
		);

		$countries = [];
		$devices   = [];
		$referrers = [];
		foreach ( (array) $breakdown_rows as $row ) {
			$c = isset( $row['country'] ) && $row['country'] !== 'null' ? strtoupper( trim( $row['country'] ) ) : '';
			$d = isset( $row['device'] )  && $row['device']  !== 'null' ? strtolower( trim( $row['device'] ) )  : '';
			if ( $c !== '' ) { $countries[ $c ] = ( $countries[ $c ] ?? 0 ) + 1; }
			if ( $d !== '' ) { $devices[ $d ]   = ( $devices[ $d ]   ?? 0 ) + 1; }
			if ( ! empty( $row['referrer'] ) && $row['referrer'] !== 'null' ) {
				$referrers[] = $row['referrer'];
			}
		}
		arsort( $countries );
		arsort( $devices );

		// Search sources — the standard "analytics SaaS" approach: parse each
		// pageview's referrer for a search query parameter and aggregate. Catches
		// both the site's own search (?s=, ?search=) and external search engines
		// that still pass the query (?q=, ?query=).
		$query_keys = [ 's', 'q', 'search', 'query' ];
		$sources    = [];
		foreach ( $referrers as $ref ) {
			$parts = wp_parse_url( $ref );
			if ( empty( $parts['query'] ) ) {
				continue;
			}
			parse_str( $parts['query'], $params );
			foreach ( $query_keys as $k ) {
				if ( ! empty( $params[ $k ] ) && is_scalar( $params[ $k ] ) ) {
					$kw = trim( (string) $params[ $k ] );
					if ( strlen( $kw ) >= 2 ) {
						$kw = mb_strtolower( $kw );
						$sources[ $kw ] = ( $sources[ $kw ] ?? 0 ) + 1;
					}
					break;
				}
			}
		}
		arsort( $sources );
		$sources = array_slice( $sources, 0, 10, true );
		$top_queries = [];
		foreach ( $sources as $kw => $count ) {
			$top_queries[] = [ 'query' => $kw, 'count' => (int) $count ];
		}

		$top_countries = [];
		foreach ( array_slice( $countries, 0, 6, true ) as $code => $count ) {
			$top_countries[] = [ 'country' => $code, 'count' => (int) $count ];
		}
		$top_devices = [];
		foreach ( array_slice( $devices, 0, 4, true ) as $name => $count ) {
			$top_devices[] = [ 'device' => $name, 'count' => (int) $count ];
		}

		// Recent feedback for this article (matches the Feedback Inbox shape).
		$feedback = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, feeling, comment, created_at
				FROM {$wpdb->prefix}betterdocs_analytics_feedback
				WHERE post_id = %d AND created_at >= %s AND created_at <= %s
				ORDER BY created_at DESC LIMIT 10",
				$post_id,
				$cutoff,
				$end_dt
			),
			ARRAY_A
		);

		// AI Traffic drawer tab (v1.5): per-agent fetch breakdown for this doc.
		$ai_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT agent, SUM( fetches ) AS fetches
				FROM {$wpdb->prefix}betterdocs_analytics_ai_daily
				WHERE post_id = %d AND stat_date >= %s AND stat_date <= %s
				GROUP BY agent
				ORDER BY fetches DESC",
				$post_id,
				$cutoff,
				$end
			)
		);

		$agent_meta = [];
		foreach ( AiTrafficCollector::catalog() as $agent ) {
			$agent_meta[ $agent['id'] ] = $agent;
		}

		$ai_agents  = [];
		$ai_fetches = 0;
		foreach ( (array) $ai_rows as $row ) {
			if ( ! isset( $agent_meta[ $row->agent ] ) ) {
				continue; // agent since removed from the catalog
			}
			$meta        = $agent_meta[ $row->agent ];
			$ai_fetches += (int) $row->fetches;
			$ai_agents[] = [
				'id'       => $meta['id'],
				'name'     => $meta['name'],
				'company'  => $meta['company'],
				'category' => $meta['category'],
				'color'    => $meta['color'],
				'value'    => (int) $row->fetches
			];
		}

		// Post status + edit URL for the drawer header (badge + "Open in editor").
		$post       = get_post( $post_id );
		$status_obj = get_post_status_object( $post ? $post->post_status : 'publish' );

		return new WP_REST_Response(
			[
				'days'            => $days,
				'post_id'         => $post_id,
				'title'           => get_the_title( $post_id ),
				'status'          => [
					'slug'  => $post ? (string) $post->post_status : '',
					'label' => $status_obj ? (string) $status_obj->label : ''
				],
				'edit_url'        => $post && current_user_can( 'edit_post', $post_id ) ? get_edit_post_link( $post_id, 'raw' ) : '',
				'totals'          => [
					'views'                => $total_views,
					'unique_views'         => $total_unique,
					'likes'                => $total_likes,
					'dislikes'             => $total_dislikes,
					'neutral'              => $total_neutral,
					'avg_scroll_depth'     => $scroll_days ? round( $scroll_sum / $scroll_days ) : 0,
					'reading_completions'  => $total_completions
				],
				'trend'           => $trend,
				'reactions_trend' => $reactions_trend,
				'breakdown'       => [
					'countries' => $top_countries,
					'devices'   => $top_devices
				],
				'ai'              => [
					'fetches'     => $ai_fetches,
					'human_views' => $total_views,
					'ratio'       => $total_views > 0 ? round( $ai_fetches / $total_views, 2 ) : null,
					'top_agent'   => $ai_agents[0] ?? null,
					'agents'      => $ai_agents
				],
				'sources'         => $top_queries,
				'feedback'        => array_map(
					function ( $f ) {
						return [
							'id'         => (int) $f['id'],
							'feeling'    => (string) $f['feeling'],
							'comment'    => (string) $f['comment'],
							'created_at' => (string) $f['created_at']
						];
					},
					(array) $feedback
				)
			],
			200
		);
	}

	/**
	 * Search analytics: popular queries, zero-result queries, daily trend + KPIs.
	 * Reads the legacy betterdocs_search_* tables (already populated by Free).
	 */
	public function search( WP_REST_Request $request ) {
		global $wpdb;

		list( $cutoff, $end, $days ) = $this->resolve_window( $request );

		$log = $wpdb->prefix . 'betterdocs_search_log';
		$kw  = $wpdb->prefix . 'betterdocs_search_keyword';

		// Active sub-tab + pagination. scope=all → most-searched; scope=zero → the
		// zero-result queries. Both are paginated ($order/$having are fixed enums,
		// safe to interpolate).
		$scope    = ( $request->get_param( 'scope' ) === 'zero' ) ? 'zero' : 'all';
		$per_page = (int) $request->get_param( 'per_page' );
		$per_page = ( $per_page >= 1 && $per_page <= 100 ) ? $per_page : 20;
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$offset   = ( $page - 1 ) * $per_page;

		// Tab-badge totals: distinct keywords searched, and distinct zero-result ones.
		$total_all = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT keyword_id ) FROM {$log} WHERE created_at >= %s AND created_at <= %s",
				$cutoff,
				$end
			)
		);
		$total_zero = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM (
					SELECT l.keyword_id FROM {$log} l
					WHERE l.created_at >= %s AND l.created_at <= %s
					GROUP BY l.keyword_id HAVING SUM( l.not_found_count ) > 0
				) t",
				$cutoff,
				$end
			)
		);

		$having = ( 'zero' === $scope ) ? 'HAVING not_found > 0' : '';
		$order  = ( 'zero' === $scope ) ? 'not_found DESC' : 'searches DESC';
		$total  = ( 'zero' === $scope ) ? $total_zero : $total_all;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT k.keyword AS keyword, SUM( l.count ) AS searches, SUM( l.not_found_count ) AS not_found
				FROM {$log} l
				INNER JOIN {$kw} k ON k.id = l.keyword_id
				WHERE l.created_at >= %s AND l.created_at <= %s
				GROUP BY l.keyword_id {$having}
				ORDER BY {$order}
				LIMIT %d OFFSET %d",
				$cutoff,
				$end,
				$per_page,
				$offset
			)
		);
		$items = [];
		foreach ( (array) $rows as $r ) {
			$items[] = [ 'keyword' => $r->keyword, 'searches' => (int) $r->searches, 'not_found' => (int) $r->not_found ];
		}

		$trend_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at AS date, SUM( count ) AS searches, SUM( not_found_count ) AS not_found
				FROM {$log}
				WHERE created_at >= %s AND created_at <= %s
				GROUP BY created_at
				ORDER BY created_at ASC",
				$cutoff,
				$end
			)
		);
		$trend = [];
		foreach ( (array) $trend_rows as $r ) {
			$trend[] = [ 'date' => $r->date, 'searches' => (int) $r->searches, 'not_found' => (int) $r->not_found ];
		}

		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( count ), 0 ) AS total_searches, COALESCE( SUM( not_found_count ), 0 ) AS total_not_found
				FROM {$log} WHERE created_at >= %s AND created_at <= %s",
				$cutoff,
				$end
			),
			ARRAY_A
		);

		// Overview "Top Search Queries" card reads `popular` — a top list
		// independent of the active scope/pagination, so it stays correct even
		// when this response was fetched for the zero-result tab.
		$popular = [];
		$popular_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT k.keyword AS keyword, SUM( l.count ) AS searches
				FROM {$log} l
				INNER JOIN {$kw} k ON k.id = l.keyword_id
				WHERE l.created_at >= %s AND l.created_at <= %s
				GROUP BY l.keyword_id
				ORDER BY searches DESC
				LIMIT 7",
				$cutoff,
				$end
			)
		);
		foreach ( (array) $popular_rows as $r ) {
			$popular[] = [ 'keyword' => $r->keyword, 'searches' => (int) $r->searches ];
		}

		return new WP_REST_Response(
			[
				'days'       => $days,
				'scope'      => $scope,
				'page'       => $page,
				'per_page'   => $per_page,
				'total'      => $total,       // total rows for the active tab (paginates $items)
				'total_all'  => $total_all,   // tab-badge counts
				'total_zero' => $total_zero,
				'kpis'       => [
					'total_searches'  => (int) ( $totals['total_searches'] ?? 0 ),
					'total_not_found' => (int) ( $totals['total_not_found'] ?? 0 )
				],
				'items'      => $items,
				'popular'    => $popular,
				'trend'      => $trend
			],
			200
		);
	}

	/**
	 * Reader engagement: device / country / referrer / language breakdowns from
	 * the raw events table. device/country/referrer live in the JSON payload
	 * (aggregated with JSON_EXTRACT — degrades to empty on MySQL without JSON);
	 * language is the real `lang` column.
	 */
	public function engagement( WP_REST_Request $request ) {
		global $wpdb;

		// Events store created_at as a DATETIME, so bound to the end of the last day.
		list( $cutoff_d, $end_d, $days ) = $this->resolve_window( $request );
		$cutoff = $cutoff_d . ' 00:00:00';
		$end    = $end_d . ' 23:59:59';
		$events = $wpdb->prefix . 'betterdocs_analytics_events';
		// Events store the doc id in `object_id`, so the KB filter scopes by that.
		$kb = $this->kb_filter( (string) $request->get_param( 'kb' ), 'object_id' );

		$by_json = function ( $path ) use ( $wpdb, $events, $cutoff, $end, $kb ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT JSON_UNQUOTE( JSON_EXTRACT( payload, %s ) ) AS k, COUNT(*) AS c
					FROM {$events}
					WHERE event_type = 'view' AND created_at >= %s AND created_at <= %s{$kb}
					GROUP BY k
					ORDER BY c DESC",
					$path,
					$cutoff,
					$end
				)
			);
			return is_array( $rows ) ? $rows : [];
		};

		// Device.
		$device = [];
		foreach ( $by_json( '$.device' ) as $r ) {
			$label = ( $r->k === null || $r->k === '' ) ? 'unknown' : $r->k;
			$device[] = [ 'label' => $label, 'count' => (int) $r->c ];
		}

		// Country — omit unresolved (no-GeoIP) rows so the Geography panel shows its
		// clean "needs MaxMind" empty-state instead of a misleading lone "Unknown" row.
		$geo = [];
		foreach ( $by_json( '$.country' ) as $r ) {
			if ( $r->k === null || $r->k === '' ) {
				continue;
			}
			$geo[] = [ 'label' => $r->k, 'count' => (int) $r->c ];
		}

		// Referrer — collapse full URLs to host, "Direct" for empty.
		$ref_hosts = [];
		foreach ( $by_json( '$.referrer' ) as $r ) {
			$url  = (string) $r->k;
			$host = $url === '' ? __( 'Direct', 'betterdocs-pro' ) : ( wp_parse_url( $url, PHP_URL_HOST ) ?: __( 'Direct', 'betterdocs-pro' ) );
			$ref_hosts[ $host ] = ( $ref_hosts[ $host ] ?? 0 ) + (int) $r->c;
		}
		arsort( $ref_hosts );
		$referrer = [];
		foreach ( array_slice( $ref_hosts, 0, 15, true ) as $host => $count ) {
			$referrer[] = [ 'label' => $host, 'count' => $count ];
		}

		// Language — real column.
		$language = [];
		$lang_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT lang AS k, COUNT(*) AS c FROM {$events}
				WHERE event_type = 'view' AND created_at >= %s AND created_at <= %s{$kb}
				GROUP BY lang ORDER BY c DESC",
				$cutoff,
				$end
			)
		);
		foreach ( (array) $lang_rows as $r ) {
			$label = ( $r->k === null || $r->k === '' ) ? 'unknown' : $r->k;
			$language[] = [ 'label' => $label, 'count' => (int) $r->c ];
		}

		return new WP_REST_Response(
			[
				'days'            => $days,
				'device'          => $device,
				'geo'             => $geo,
				'referrer'        => $referrer,
				'language'        => $language,
				// Lets the Geography card pick a precise empty-state message:
				// no key / key saved but DB not downloaded yet / DB present but no data.
				'geoip_available' => \WPDeveloper\BetterDocsPro\Core\AnalyticsGeoIP::is_available(),
				'geoip_key_set'   => '' !== trim( (string) betterdocs()->settings->get( 'maxmind_license_key', '' ) )
			],
			200
		);
	}

	/**
	 * Feedback Inbox: paginated per-item stream + status/feeling counts.
	 * Filters: status (new|reviewed|resolved), feeling (happy|sad|normal).
	 */
	public function feedback( WP_REST_Request $request ) {
		global $wpdb;

		// Feedback created_at is a DATETIME — bound to the end of the last day.
		list( $cutoff_d, $end_d, $days ) = $this->resolve_window( $request );
		$cutoff  = $cutoff_d . ' 00:00:00';
		$end     = $end_d . ' 23:59:59';
		$status  = (string) $request->get_param( 'status' );
		$feeling = (string) $request->get_param( 'feeling' );
		$per     = (int) $request->get_param( 'per_page' );
		$page    = (int) $request->get_param( 'page' );
		$offset  = ( $page - 1 ) * $per;
		$table   = $wpdb->prefix . 'betterdocs_analytics_feedback';
		$kb      = $this->kb_filter( (string) $request->get_param( 'kb' ) );

		// Build the filtered WHERE with prepared args.
		$where = 'created_at >= %s AND created_at <= %s' . $kb;
		$args  = [ $cutoff, $end ];
		if ( in_array( $status, [ 'new', 'reviewed', 'resolved' ], true ) ) {
			$where .= ' AND status = %s';
			$args[] = $status;
		}
		if ( in_array( $feeling, [ 'happy', 'sad', 'normal' ], true ) ) {
			$where .= ' AND feeling = %s';
			$args[] = $feeling;
		}

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $args )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, post_id, feeling, comment, status, created_at FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( $args, [ $per, $offset ] )
			)
		);

		$items = [];
		foreach ( (array) $rows as $r ) {
			$items[] = [
				'id'         => (int) $r->id,
				'post_id'    => (int) $r->post_id,
				'title'      => get_the_title( (int) $r->post_id ),
				'feeling'    => $r->feeling,
				'comment'    => $r->comment,
				'status'     => $r->status,
				'created_at' => $r->created_at
			];
		}

		// Status + feeling counts over the window (unfiltered by status/feeling).
		$status_counts = [ 'new' => 0, 'reviewed' => 0, 'resolved' => 0 ];
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) c FROM {$table} WHERE created_at >= %s AND created_at <= %s{$kb} GROUP BY status", $cutoff, $end ) ) as $r ) {
			if ( isset( $status_counts[ $r->status ] ) ) {
				$status_counts[ $r->status ] = (int) $r->c;
			}
		}
		$feeling_counts = [ 'happy' => 0, 'normal' => 0, 'sad' => 0 ];
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT feeling, COUNT(*) c FROM {$table} WHERE created_at >= %s AND created_at <= %s{$kb} GROUP BY feeling", $cutoff, $end ) ) as $r ) {
			if ( isset( $feeling_counts[ $r->feeling ] ) ) {
				$feeling_counts[ $r->feeling ] = (int) $r->c;
			}
		}

		return new WP_REST_Response(
			[
				'days'           => $days,
				'page'           => $page,
				'per_page'       => $per,
				'total'          => $total,
				'items'          => $items,
				'status_counts'  => $status_counts,
				'feeling_counts' => $feeling_counts
			],
			200
		);
	}

	/**
	 * Bulk-update feedback status. Requires edit capability (see permission_check).
	 */
	public function feedback_bulk( WP_REST_Request $request ) {
		global $wpdb;

		$ids    = array_filter( array_map( 'intval', (array) $request->get_param( 'ids' ) ) );
		$status = (string) $request->get_param( 'status' );

		if ( empty( $ids ) ) {
			return new WP_REST_Response( [ 'updated' => 0 ], 200 );
		}

		$table        = $wpdb->prefix . 'betterdocs_analytics_feedback';
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s WHERE id IN ( {$placeholders} )",
				array_merge( [ $status ], $ids )
			)
		);

		return new WP_REST_Response( [ 'updated' => (int) $updated, 'status' => $status ], 200 );
	}

	/**
	 * Link Health: working/broken counts, last-scan time, and a paginated list
	 * of broken links with the doc they live in. Anchors are excluded from counts.
	 */
	public function links( WP_REST_Request $request ) {
		global $wpdb;

		$per    = (int) $request->get_param( 'per_page' );
		$page   = (int) $request->get_param( 'page' );
		$offset = ( $page - 1 ) * $per;
		$table  = $wpdb->prefix . 'betterdocs_analytics_links';
		$kb     = $this->kb_filter( (string) $request->get_param( 'kb' ) );

		$counts = $wpdb->get_row(
			"SELECT COUNT(*) AS total, COALESCE( SUM( is_broken ), 0 ) AS broken
			FROM {$table} WHERE link_type IN ( 'internal', 'external' ){$kb}",
			ARRAY_A
		);
		$total  = (int) ( $counts['total'] ?? 0 );
		$broken = (int) ( $counts['broken'] ?? 0 );

		$total_broken = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_broken = 1{$kb}" );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, url, http_status, link_type, last_checked
				FROM {$table} WHERE is_broken = 1{$kb}
				ORDER BY last_checked DESC
				LIMIT %d OFFSET %d",
				$per,
				$offset
			)
		);

		$items = [];
		foreach ( (array) $rows as $r ) {
			$items[] = [
				'post_id'      => (int) $r->post_id,
				'title'        => get_the_title( (int) $r->post_id ),
				'url'          => esc_url( $r->url ),
				'http_status'  => (int) $r->http_status,
				'link_type'    => $r->link_type,
				'last_checked' => $r->last_checked
			];
		}

		$last = (int) get_option( 'betterdocs_analytics_link_scan_last', 0 );

		return new WP_REST_Response(
			[
				'checked'      => $total,
				'broken'       => $broken,
				'working'      => max( 0, $total - $broken ),
				'total'        => $total_broken,
				'page'         => $page,
				'per_page'     => $per,
				'items'        => $items,
				'last_scanned' => $last ? gmdate( 'c', $last ) : null
			],
			200
		);
	}

	/**
	 * Trigger a fresh Link Health scan (write — needs edit capability).
	 */
	public function links_scan() {
		$this->container->get( \WPDeveloper\BetterDocsPro\Core\AnalyticsLinkScanner::class )->start_scan();
		return new WP_REST_Response( [ 'started' => true ], 200 );
	}

	/**
	 * Author Performance: views/unique/docs per doc author (from daily), plus
	 * reactions from the legacy aggregate.
	 */
	public function authors( WP_REST_Request $request ) {
		global $wpdb;

		list( $cutoff, $end, $days ) = $this->resolve_window( $request );
		$kb_raw = (string) $request->get_param( 'kb' );
		$kb_d   = $this->kb_filter( $kb_raw, 'd.post_id' );
		$kb_a   = $this->kb_filter( $kb_raw, 'a.post_id' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_author AS author_id, SUM( d.views ) AS views, SUM( d.unique_views ) AS unique_views, COUNT( DISTINCT d.post_id ) AS docs
				FROM {$wpdb->prefix}betterdocs_analytics_daily d
				INNER JOIN {$wpdb->posts} p ON p.ID = d.post_id
				WHERE d.stat_date >= %s AND d.stat_date <= %s AND p.post_type = 'docs'{$kb_d}
				GROUP BY p.post_author
				ORDER BY views DESC
				LIMIT 50",
				$cutoff,
				$end
			)
		);

		$reaction_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_author AS author_id, SUM( a.happy + a.sad + a.normal ) AS reactions
				FROM {$wpdb->prefix}betterdocs_analytics a
				INNER JOIN {$wpdb->posts} p ON p.ID = a.post_id
				WHERE a.created_at >= %s AND a.created_at <= %s AND p.post_type = 'docs'{$kb_a}
				GROUP BY p.post_author",
				$cutoff,
				$end
			)
		);
		$reactions = [];
		foreach ( (array) $reaction_rows as $r ) {
			$reactions[ (int) $r->author_id ] = (int) $r->reactions;
		}

		$items = [];
		foreach ( (array) $rows as $r ) {
			$author_id = (int) $r->author_id;
			$items[] = [
				'author_id'    => $author_id,
				'name'         => get_the_author_meta( 'display_name', $author_id ) ?: __( 'Unknown', 'betterdocs-pro' ),
				'docs'         => (int) $r->docs,
				'views'        => (int) $r->views,
				'unique_views' => (int) $r->unique_views,
				'reactions'    => $reactions[ $author_id ] ?? 0
			];
		}

		return new WP_REST_Response( [ 'days' => $days, 'items' => $items ], 200 );
	}

	/**
	 * GeoIP status for the settings UI: key set, DB present, last attempt outcome.
	 */
	public function geoip_status() {
		return new WP_REST_Response( \WPDeveloper\BetterDocsPro\Core\AnalyticsGeoIP::status(), 200 );
	}

	/**
	 * On-demand GeoLite2 download ("Download now" button). Runs synchronously —
	 * the admin is waiting on the button — and returns the fresh status, whose
	 * last_error carries the invalid-key / extraction message when it fails.
	 */
	public function geoip_refresh() {
		$geoip = $this->container->get( \WPDeveloper\BetterDocsPro\Core\AnalyticsGeoIP::class );
		$geoip->update_database();
		return new WP_REST_Response( \WPDeveloper\BetterDocsPro\Core\AnalyticsGeoIP::status(), 200 );
	}

	/**
	 * GA4 connection test. For the Measurement Protocol method this is definitive:
	 * the payload is validated against GA4's debug endpoint (returns per-field
	 * validation messages), and on success a real `betterdocs_test_event` is sent
	 * so it shows up in GA4 Realtime. The client-side methods can't be verified
	 * from the server, so they get guidance instead.
	 */
	public function ga4_test() {
		$enabled = (bool) betterdocs()->settings->get( 'analytics_ga4', false );
		$method  = (string) betterdocs()->settings->get( 'ga4_method', 'datalayer' );
		if ( ! $enabled ) {
			return new WP_REST_Response( [ 'status' => 'error', 'message' => __( 'GA4 forwarding is disabled — enable the toggle first.', 'betterdocs-pro' ) ], 200 );
		}

		if ( 'gtag' === $method ) {
			return new WP_REST_Response( [ 'status' => 'info', 'message' => __( 'The Google Tag method runs in the reader\'s browser. Open a doc page, then check GA4 → Reports → Realtime for a betterdocs_doc_view event.', 'betterdocs-pro' ) ], 200 );
		}
		if ( 'mp' !== $method ) {
			return new WP_REST_Response( [ 'status' => 'info', 'message' => __( 'The dataLayer method relies on your Google Tag Manager container. Verify your GTM tags fire on betterdocs_doc_view, then check GA4 Realtime.', 'betterdocs-pro' ) ], 200 );
		}

		$measurement_id = sanitize_text_field( (string) betterdocs()->settings->get( 'ga4_measurement_id', '' ) );
		$api_secret     = trim( (string) betterdocs()->settings->get( 'ga4_api_secret', '' ) );
		if ( '' === $measurement_id || '' === $api_secret ) {
			return new WP_REST_Response( [ 'status' => 'error', 'message' => __( 'Enter both a Measurement ID and an API secret, then save before testing.', 'betterdocs-pro' ) ], 200 );
		}

		$body = wp_json_encode(
			[
				'client_id' => wp_rand( 1, 2147483647 ) . '.' . time(),
				'events'    => [
					[
						'name'   => 'betterdocs_test_event',
						'params' => [
							'page_location'        => home_url( '/' ),
							'engagement_time_msec' => 1,
						],
					],
				],
			]
		);
		$args = [
			'timeout' => 8,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => $body,
		];
		$query = [
			'measurement_id' => $measurement_id,
			'api_secret'     => $api_secret,
		];

		// 1) Validate against the debug endpoint — it echoes validation messages.
		$debug = wp_remote_post( add_query_arg( $query, 'https://www.google-analytics.com/debug/mp/collect' ), $args );
		if ( is_wp_error( $debug ) ) {
			return new WP_REST_Response( [ 'status' => 'error', 'message' => $debug->get_error_message() ], 200 );
		}
		$messages = json_decode( wp_remote_retrieve_body( $debug ), true );
		$messages = isset( $messages['validationMessages'] ) ? (array) $messages['validationMessages'] : [];
		if ( ! empty( $messages ) ) {
			$first = $messages[0];
			return new WP_REST_Response(
				[
					'status'  => 'error',
					'message' => sprintf(
						/* translators: %s: validation message from GA4 */
						__( 'GA4 rejected the test event: %s', 'betterdocs-pro' ),
						isset( $first['description'] ) ? $first['description'] : wp_json_encode( $first )
					),
				],
				200
			);
		}

		// 2) Accepted — fire it for real so it appears in GA4 Realtime.
		wp_remote_post( add_query_arg( $query, 'https://www.google-analytics.com/mp/collect' ), $args );

		return new WP_REST_Response(
			[
				'status'  => 'success',
				'message' => __( 'Test event accepted — look for betterdocs_test_event in GA4 → Reports → Realtime (may take a minute).', 'betterdocs-pro' ),
			],
			200
		);
	}

	/**
	 * Stream a module's data as CSV. Modules: overview | articles | categories |
	 * knowledge_bases | reactions | search | feedback | links | authors |
	 * engagement | ai (AI Traffic per-agent breakdown).
	 */
	public function export( WP_REST_Request $request ) {
		$module = (string) $request->get_param( 'module' );
		$kb_raw = (string) $request->get_param( 'kb' );
		list( $start, $end ) = $this->resolve_window( $request );

		list( $headers, $rows ) = $this->export_rows( $module, $start, $end, $kb_raw, $request );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		// User-facing filename maps the internal module slug to a friendly name
		// ('articles' → 'docs', 'knowledge_bases' → 'knowledge-bases').
		$slug = [
			'overview'        => 'overview',
			'articles'        => 'docs',
			'categories'      => 'categories',
			'knowledge_bases' => 'knowledge-bases',
			'reactions'       => 'reactions',
			'search'          => 'search',
			'feedback'        => 'feedback',
			'links'           => 'links',
			'authors'         => 'authors',
			'engagement'      => 'engagement',
			'ai'              => 'ai-traffic',
		];
		$name = isset( $slug[ $module ] ) ? $slug[ $module ] : $module;
		header( 'Content-Disposition: attachment; filename="betterdocs-' . $name . '-' . gmdate( 'Ymd' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array_map( [ $this, 'neutralize_csv_cell' ], (array) $headers ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, array_map( [ $this, 'neutralize_csv_cell' ], (array) $row ) );
		}
		// export_rows() caps every module at 5,000 rows; when the cap is hit the
		// file is (likely) partial — say so instead of silently truncating (QA #16).
		if ( count( $rows ) >= 5000 ) {
			fputcsv( $out, [ '# Export truncated at 5,000 rows — narrow the date range or filters for complete data.' ] );
		}
		fclose( $out );
		exit;
	}

	/**
	 * Neutralize CSV / spreadsheet formula injection. A cell whose first character
	 * is one of = + - @ (or a leading tab / carriage return that Excel treats as a
	 * formula lead-in) is prefixed with a single quote so the spreadsheet opens it
	 * as literal text instead of executing it. Visitor-controlled columns (search
	 * keywords, link URLs) are the attack vector; sanitize_text_field() does NOT
	 * strip these leads, so neutralize every exported cell.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function neutralize_csv_cell( $value ) {
		$value = (string) $value;
		if ( $value !== '' && in_array( $value[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Build [ headerRow, dataRows[] ] for a module's CSV export (unpaginated, capped).
	 *
	 * @return array{0:array,1:array}
	 */
	public function export_rows( $module, $start, $end, $kb_raw = '', $request = null ) {
		global $wpdb;
		$cutoff = $start;
		$end_dt = $end . ' 23:59:59';
		$cap    = 5000;
		$kb     = $this->kb_filter( $kb_raw );

		// Active section/filter forwarded by the header Export button. Sanitized to
		// fixed enums so they are safe to use in the queries below.
		$order  = ( $request && $request->get_param( 'order' ) === 'least' ) ? 'least' : 'most';
		$scope  = ( $request && $request->get_param( 'scope' ) === 'all' ) ? 'all' : 'zero';
		$status = $request ? (string) $request->get_param( 'status' ) : '';
		$status = in_array( $status, [ 'new', 'reviewed', 'resolved' ], true ) ? $status : '';

		switch ( $module ) {
			case 'articles':
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, SUM( views ) AS views, SUM( unique_views ) AS unique_views
						FROM {$wpdb->prefix}betterdocs_analytics_daily WHERE stat_date >= %s AND stat_date <= %s{$kb}
						GROUP BY post_id ORDER BY views DESC LIMIT %d",
						$cutoff,
						$end,
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					$out[] = [ get_the_title( (int) $r->post_id ), get_permalink( (int) $r->post_id ), (int) $r->views, (int) $r->unique_views ];
				}
				return [ [ 'Doc', 'URL', 'Views', 'Unique Views' ], $out ];

			case 'search':
				// scope=zero → only queries that returned no results (the Zero-Result sub-tab).
				$having = ( 'zero' === $scope ) ? ' HAVING not_found > 0' : '';
				$rows   = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT k.keyword AS keyword, SUM( l.count ) AS searches, SUM( l.not_found_count ) AS not_found
						FROM {$wpdb->prefix}betterdocs_search_log l
						INNER JOIN {$wpdb->prefix}betterdocs_search_keyword k ON k.id = l.keyword_id
						WHERE l.created_at >= %s AND l.created_at <= %s GROUP BY l.keyword_id{$having} ORDER BY searches DESC LIMIT %d",
						$cutoff,
						$end,
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					$out[] = [ $r->keyword, (int) $r->searches, (int) $r->not_found ];
				}
				return [ [ 'Query', 'Searches', 'No Results' ], $out ];

			case 'feedback':
				// Honor the active Status filter (new|reviewed|resolved) when set.
				$status_sql = $status ? $wpdb->prepare( ' AND status = %s', $status ) : '';
				$rows       = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, feeling, status, created_at FROM {$wpdb->prefix}betterdocs_analytics_feedback
						WHERE created_at >= %s AND created_at <= %s{$kb}{$status_sql} ORDER BY created_at DESC LIMIT %d",
						$cutoff,
						$end_dt,
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					$out[] = [ get_the_title( (int) $r->post_id ), $r->feeling, $r->status, $r->created_at ];
				}
				return [ [ 'Doc', 'Reaction', 'Status', 'Date' ], $out ];

			case 'links':
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, url, http_status, link_type FROM {$wpdb->prefix}betterdocs_analytics_links
						WHERE is_broken = 1{$kb} ORDER BY last_checked DESC LIMIT %d",
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					$out[] = [ get_the_title( (int) $r->post_id ), $r->url, (int) $r->http_status, $r->link_type ];
				}
				return [ [ 'Doc', 'Link', 'HTTP Status', 'Type' ], $out ];

			case 'overview':
				// A summary snapshot so the Overview export differs from Article Performance.
				$daily_t = $wpdb->prefix . 'betterdocs_analytics_daily';
				$v       = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COALESCE( SUM( views ), 0 ) AS views, COALESCE( SUM( unique_views ), 0 ) AS uniques
						FROM {$daily_t} WHERE stat_date >= %s AND stat_date <= %s{$kb}",
						$cutoff,
						$end
					)
				);
				$sr = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COALESCE( SUM( count ), 0 ) AS searches, COALESCE( SUM( not_found_count ), 0 ) AS not_found
						FROM {$wpdb->prefix}betterdocs_search_log WHERE created_at >= %s AND created_at <= %s",
						$cutoff,
						$end
					)
				);
				$rx = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE( SUM( happy + sad + normal ), 0 ) FROM {$wpdb->prefix}betterdocs_analytics WHERE created_at >= %s AND created_at <= %s{$kb}",
						$cutoff,
						$end
					)
				);
				return [
					[ 'Metric', 'Value' ],
					[
						[ 'Total Views', (int) ( $v->views ?? 0 ) ],
						[ 'Unique Readers', (int) ( $v->uniques ?? 0 ) ],
						[ 'Searches', (int) ( $sr->searches ?? 0 ) ],
						[ 'No-Result Searches', (int) ( $sr->not_found ?? 0 ) ],
						[ 'Reactions', $rx ],
					],
				];

			case 'reactions':
				// Ranked helpfulness list from the legacy reaction store, honoring order=most|least.
				$legacy   = $wpdb->prefix . 'betterdocs_analytics';
				$rank_col = ( 'least' === $order ) ? 'sad' : 'happy';
				$rows     = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, SUM( happy ) AS happy, SUM( normal ) AS normal, SUM( sad ) AS sad, SUM( happy + normal + sad ) AS total
						FROM {$legacy} WHERE created_at >= %s AND created_at <= %s{$kb}
						GROUP BY post_id HAVING SUM( {$rank_col} ) > 0 ORDER BY {$rank_col} DESC LIMIT %d",
						$cutoff,
						$end,
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					$out[] = [ get_the_title( (int) $r->post_id ), (int) $r->happy, (int) $r->normal, (int) $r->sad, (int) $r->total ];
				}
				return [ [ 'Doc', 'Happy', 'Neutral', 'Unhappy', 'Total' ], $out ];

			case 'categories':
			case 'knowledge_bases':
				$taxonomy = ( 'knowledge_bases' === $module ) ? 'knowledge_base' : 'doc_category';
				$daily_t  = $wpdb->prefix . 'betterdocs_analytics_daily';
				// KB filter applies only to the category breakdown (post-scoped);
				// the knowledge-base breakdown is itself global.
				$kb_join = ( 'knowledge_bases' === $module ) ? '' : $this->kb_filter( $kb_raw, 'd.post_id' );
				$rows    = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT tt.term_id AS term_id, SUM( d.views ) AS views, SUM( d.unique_views ) AS unique_views
						FROM {$daily_t} d
						JOIN {$wpdb->posts} p ON p.ID = d.post_id AND p.post_status = 'publish'
						JOIN {$wpdb->term_relationships} tr ON tr.object_id = d.post_id
						JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
						WHERE d.stat_date >= %s AND d.stat_date <= %s{$kb_join}
						GROUP BY tt.term_id ORDER BY views DESC LIMIT %d",
						$taxonomy,
						$cutoff,
						$end,
						$cap
					)
				);
				// Reactions from the LIVE legacy aggregate — analytics_daily's
				// reaction columns are a frozen backfill snapshot (see leading_terms()).
				$kb_legacy_x   = ( 'knowledge_bases' === $module ) ? '' : $this->kb_filter( $kb_raw, 'a.post_id' );
				$reaction_rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT tt.term_id AS term_id, SUM( a.happy + a.sad + a.normal ) AS reactions
						FROM {$wpdb->prefix}betterdocs_analytics a
						JOIN {$wpdb->posts} p ON p.ID = a.post_id AND p.post_status = 'publish'
						JOIN {$wpdb->term_relationships} tr ON tr.object_id = a.post_id
						JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
						WHERE a.created_at >= %s AND a.created_at <= %s{$kb_legacy_x}
						GROUP BY tt.term_id",
						$taxonomy,
						$cutoff,
						$end
					)
				);
				$rx_map = [];
				foreach ( (array) $reaction_rows as $r ) {
					$rx_map[ (int) $r->term_id ] = (int) $r->reactions;
				}
				$out = [];
				foreach ( (array) $rows as $r ) {
					$term = get_term( (int) $r->term_id );
					if ( ! $term || is_wp_error( $term ) ) {
						continue;
					}
					$out[] = [ $term->name, (int) $r->views, (int) $r->unique_views, isset( $rx_map[ (int) $r->term_id ] ) ? $rx_map[ (int) $r->term_id ] : 0 ];
				}
				$term_label = ( 'knowledge_bases' === $module ) ? 'Knowledge Base' : 'Category';
				return [ [ $term_label, 'Views', 'Unique Views', 'Reactions' ], $out ];

			case 'engagement':
				// Combined device / country / referrer / language breakdown from the events stream.
				$events    = $wpdb->prefix . 'betterdocs_analytics_events';
				$cutoff_ev = $cutoff . ' 00:00:00';
				$kb_ev     = $this->kb_filter( $kb_raw, 'object_id' );
				$out       = [];
				$json_dims = [ 'Device' => '$.device', 'Country' => '$.country', 'Referrer' => '$.referrer' ];
				foreach ( $json_dims as $type => $path ) {
					$rows = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT JSON_UNQUOTE( JSON_EXTRACT( payload, %s ) ) AS k, COUNT(*) AS c
							FROM {$events} WHERE event_type = 'view' AND created_at >= %s AND created_at <= %s{$kb_ev}
							GROUP BY k ORDER BY c DESC LIMIT %d",
							$path,
							$cutoff_ev,
							$end_dt,
							$cap
						)
					);
					foreach ( (array) $rows as $r ) {
						$empty = ( null === $r->k || '' === $r->k );
						$val   = $empty ? ( 'Referrer' === $type ? __( 'Direct', 'betterdocs-pro' ) : 'Unknown' ) : $r->k;
						$out[] = [ $type, $val, (int) $r->c ];
					}
				}
				$lang_rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT lang AS k, COUNT(*) AS c FROM {$events}
						WHERE event_type = 'view' AND created_at >= %s AND created_at <= %s{$kb_ev}
						GROUP BY lang ORDER BY c DESC LIMIT %d",
						$cutoff_ev,
						$end_dt,
						$cap
					)
				);
				foreach ( (array) $lang_rows as $r ) {
					$val   = ( null === $r->k || '' === $r->k ) ? 'unknown' : $r->k;
					$out[] = [ 'Language', $val, (int) $r->c ];
				}
				return [ [ 'Type', 'Value', 'Sessions' ], $out ];

			case 'authors':
				$kb_d = $this->kb_filter( $kb_raw, 'd.post_id' );
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT p.post_author AS author_id, SUM( d.views ) AS views, SUM( d.unique_views ) AS unique_views, COUNT( DISTINCT d.post_id ) AS docs
						FROM {$wpdb->prefix}betterdocs_analytics_daily d
						INNER JOIN {$wpdb->posts} p ON p.ID = d.post_id
						WHERE d.stat_date >= %s AND d.stat_date <= %s AND p.post_type = 'docs'{$kb_d}
						GROUP BY p.post_author ORDER BY views DESC LIMIT %d",
						$cutoff,
						$end,
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					$out[] = [ get_the_author_meta( 'display_name', (int) $r->author_id ), (int) $r->docs, (int) $r->views, (int) $r->unique_views ];
				}
				return [ [ 'Author', 'Docs', 'Views', 'Unique Views' ], $out ];

			case 'ai':
				// AI Traffic (v1.5): per-agent breakdown over the window. Non-catalog
				// agent ids (removed via the betterdocs_ai_traffic_agents filter) are
				// skipped so the export matches the dashboard's agent table/donut (#13).
				$ai_t    = $wpdb->prefix . 'betterdocs_analytics_ai_daily';
				$catalog = AiTrafficCollector::catalog();
				$meta    = [];
				foreach ( $catalog as $a ) {
					$meta[ $a['id'] ] = $a;
				}
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT agent, SUM( fetches ) AS fetches, COUNT( DISTINCT post_id ) AS unique_pages, MAX( last_fetch ) AS last_fetch
						FROM {$ai_t} WHERE stat_date >= %s AND stat_date <= %s
						GROUP BY agent ORDER BY fetches DESC LIMIT %d",
						$cutoff,
						$end,
						$cap
					)
				);
				$out = [];
				foreach ( (array) $rows as $r ) {
					if ( ! isset( $meta[ $r->agent ] ) ) {
						continue;
					}
					$m     = $meta[ $r->agent ];
					$out[] = [ $m['name'], $m['company'], $m['category'], (int) $r->fetches, (int) $r->unique_pages, $r->last_fetch ? $r->last_fetch . ' UTC' : '' ];
				}
				return [ [ 'Agent', 'Company', 'Category', 'AI Fetches', 'Unique Docs', 'Last Fetch (UTC)' ], $out ];
		}

		return [ [], [] ];
	}

	/**
	 * Letter grade for a 0–100 score (mirrors ContentHealthScorer::grade).
	 */
	private function health_grade( $score ) {
		if ( $score >= 90 ) { return 'A'; }
		if ( $score >= 75 ) { return 'B'; }
		if ( $score >= 60 ) { return 'C'; }
		if ( $score >= 40 ) { return 'D'; }
		return 'F';
	}

	/**
	 * Content Intelligence overview: KPI strip + the single top action.
	 *
	 * `kb` scopes everything that can be attributed to a doc — stale, duplicates
	 * and health — via the post taxonomy, not via the row's kb_id column: the
	 * local scanners still write kb_id = 0 (single-KB assumption for v2.0 Phase 1)
	 * and a column could not represent a doc that belongs to two KBs anyway.
	 *
	 * Gaps stay global. A gap is derived from search queries and has no doc to
	 * attribute — the same reason summary() keeps search totals global.
	 */
	public function insights_overview( WP_REST_Request $request ) {
		if ( ! $this->insights_table_ready() ) {
			return $this->insights_unavailable( [
				'mode'        => 'local',
				'gaps'        => [ 'count' => 0, 'new_this_week' => 0 ],
				'stale'       => [ 'count' => 0, 'critical' => 0 ],
				'duplicates'  => [ 'count' => 0 ],
				'health'      => [ 'score' => 0, 'grade' => '', 'delta' => 0.0, 'scored' => false ],
				'suggestions' => [ 'applied' => 0, 'total' => 0, 'rate' => 0 ],
				'top_action'  => null,
			] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		$kb_slug = (string) $request->get_param( 'kb' );
		$kb      = $this->kb_filter( $kb_slug, 'object_id' );

		$stale_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'stale' AND status IN ( 'new', 'accepted' ){$kb}" );
		$stale_crit  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'stale' AND status IN ( 'new', 'accepted' ) AND score >= 75{$kb}" );
		$gaps_total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'gap' AND status IN ( 'new', 'accepted' )" );

		// Counted through the same pair-aware helper the Duplicates screen uses, so
		// the KPI and the list it links to can never disagree. A SQL filter on
		// object_id would have zeroed this card outright: a duplicate is about two
		// docs and its row carries the first one at most.
		$dupes_total = count( $this->duplicate_items( $kb_slug ) );

		list( $health_score, $health_grade, $health_delta, $health_components, $health_scored ) = $this->health_site_score( $kb_slug );

		// Gaps found in the last seven days — "5 new this week" on the KPI. Counted
		// on created_at, not status: a gap the user has already accepted is still
		// news this week, and dropping it would make the number shrink as they work.
		$gaps_new = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE type = 'gap' AND status IN ( 'new', 'accepted' ) AND created_at >= %s",
				gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
			)
		);

		// How much of what Content Intelligence proposed the user actually acted on.
		// 'done' is applied; 'accepted' is queued but not finished, so it counts
		// toward engagement but not toward applied. Dismissed rows stay in the
		// denominator on purpose — deciding an insight is wrong is a real signal
		// about suggestion quality, and hiding it would flatter the rate.
		//
		// Only proposal types count. `health` writes one telemetry row per
		// published doc and proposes nothing, so counting it made the denominator
		// the size of the library and pinned the rate near 0% however much the
		// user actually did (QA saw total = 195 against 192 health rows). Excluding
		// by type rather than listing gap/stale/duplicate keeps future proposal
		// types (e.g. 'rewrite') counted the day they ship.
		//
		// Deliberately site-wide even under a KB filter. This measures how good the
		// suggestions are, not how one knowledge base is doing, and gaps — which
		// have no doc and so no KB — are a large share of the denominator; scoping
		// it would silently redefine the metric as "stale and duplicates only"
		// the moment a KB is picked.
		$sugg_total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type != 'health'" );
		$sugg_applied = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type != 'health' AND status IN ( 'done', 'accepted' )" );

		// Top action: the highest-demand active gap, falling back to the most-stale
		// article when there are no gaps yet. Gap-first because a missing article is
		// a bigger win than refreshing one that already exists, and because a brand
		// new site has stale rows long before it has enough search history to find
		// gaps — leading with "refresh this" there would be noise.
		$top     = null;
		$gap_row = $wpdb->get_row( "SELECT payload, score FROM {$table} WHERE type = 'gap' AND status IN ( 'new', 'accepted' ) ORDER BY score DESC LIMIT 1" );
		if ( $gap_row ) {
			$payload = json_decode( (string) $gap_row->payload, true );
			if ( is_array( $payload ) ) {
				$queries = isset( $payload['queries'] ) && is_array( $payload['queries'] ) ? $payload['queries'] : [];
				$top     = [
					'type'       => 'gap',
					'title'      => $payload['suggested_title'] ?? ( $queries[0] ?? '' ),
					'score'      => (float) $gap_row->score,
					'confidence' => $payload['confidence'] ?? null,
					'queries'    => array_slice( $queries, 0, 6 ),
					// Per-intent evidence from the cloud analysis. Shape:
					// { zero_result: n, low_yield: n, feedback: n }. Absent on rows
					// written before the breakdown shipped — the UI degrades to the
					// total rather than rendering zeroes.
					'signals'    => isset( $payload['signals'] ) && is_array( $payload['signals'] ) ? $payload['signals'] : null,
					'volume'     => (int) ( $payload['search_volume'] ?? 0 ),
				];
			}
		}
		if ( null === $top ) {
			// KB-scoped, matching the Stale card above it: the fallback must point
			// at an article the filtered screen actually lists.
			$top_row = $wpdb->get_row( "SELECT payload, score FROM {$table} WHERE type = 'stale' AND status IN ( 'new', 'accepted' ){$kb} ORDER BY score DESC LIMIT 1" );
			if ( $top_row ) {
				$payload = json_decode( (string) $top_row->payload, true );
				if ( is_array( $payload ) ) {
					$top = [
						'type'    => 'stale',
						'title'   => $payload['title'] ?? '',
						'score'   => (float) $top_row->score,
						'post_id' => (int) ( $payload['post_id'] ?? 0 ),
					];
				}
			}
		}

		$service = $this->ci_service();

		return new WP_REST_Response(
			[
				// Which gap engine is running. The overview uses it to stop
				// presenting Duplicates as a module that found nothing when it is
				// a module that never ran.
				'mode'        => $service ? $service->gap_mode() : 'local',
				// Why the cloud engine is (not) running, so the UI can name what is
				// missing — unlicensed add-on, unconfigured, first sync pending —
				// instead of showing every not-cloud site the same add-on hint.
				'chatbot_state' => $service ? $service->chatbot_state() : 'none',
				'gaps'        => [ 'count' => $gaps_total, 'new_this_week' => $gaps_new ],
				'stale'       => [ 'count' => $stale_total, 'critical' => $stale_crit ],
				'duplicates'  => [ 'count' => $dupes_total ],
				'health'      => [ 'score' => $health_score, 'grade' => $health_grade, 'delta' => $health_delta, 'scored' => $health_scored ],
				'suggestions' => [
					'applied' => $sugg_applied,
					'total'   => $sugg_total,
					'rate'    => $sugg_total > 0 ? (int) round( $sugg_applied / $sugg_total * 100 ) : 0,
				],
				'top_action'  => $top,
			],
			200
		);
	}

	/**
	 * Health score, grade, month-over-month delta and component averages —
	 * site-wide, or for one knowledge base when `$kb_slug` is given.
	 *
	 * Site-wide reads the latest ContentHealthScorer snapshot. A knowledge base
	 * instead aggregates today's per-doc health rows live: the per-KB snapshots
	 * only start accruing on the first nightly pass after they shipped, and a
	 * freshly filtered screen must not read "0 / F" until then. The two agree by
	 * construction — both call ContentHealthScorer::aggregate().
	 *
	 * The delta always needs history, so it comes from the snapshots either way
	 * and stays 0.0 for a knowledge base until two of them exist.
	 *
	 * The fifth return value separates "nothing has been scored yet" from "we
	 * measured this library and it scores zero". Both used to come back as 0/F, so
	 * a site whose first pass had not run yet was shown a red failing grade for
	 * content nobody had assessed. Callers render the unscored state neutrally and
	 * offer the scan instead of a verdict. The grade is returned empty rather than
	 * 'F' so a caller that forgets to check cannot print a letter that means
	 * nothing.
	 *
	 * @return array{0:float,1:string,2:float,3:array,4:bool} [ score, grade, delta, components, scored ]
	 */
	private function health_site_score( $kb_slug = '' ) {
		$history = get_option( 'betterdocs_content_health_history', [] );
		$history = is_array( $history ) ? $history : [];
		ksort( $history );

		if ( '' !== $kb_slug ) {
			$scorer = $this->health_scorer();
			$agg    = $scorer ? $scorer->aggregate( $this->kb_filter( $kb_slug, 'object_id' ) ) : null;
			if ( null === $agg ) {
				return [ 0, '', 0.0, [], false ];
			}
			$score = (float) $agg['score'];
			return [
				round( $score, 1 ),
				$this->health_grade( $score ),
				$this->health_delta( $history, $score, $kb_slug ),
				$agg['components'],
				true,
			];
		}

		if ( empty( $history ) ) {
			return [ 0, '', 0.0, [], false ];
		}

		$latest     = end( $history );
		$score      = isset( $latest['score'] ) ? (float) $latest['score'] : 0.0;
		$components = isset( $latest['components'] ) && is_array( $latest['components'] ) ? $latest['components'] : [];

		return [ round( $score, 1 ), $this->health_grade( $score ), $this->health_delta( $history, $score, '' ), $components, true ];
	}

	/**
	 * Change against the snapshot closest to 30 days before the newest one.
	 * `$kb_slug` reads the per-KB series (`$snap['kbs'][ $slug ]`) instead of the
	 * site series; dates recorded before per-KB snapshots existed simply have no
	 * entry and are skipped, so the delta appears once the series is old enough.
	 */
	private function health_delta( array $history, $score, $kb_slug = '' ) {
		if ( empty( $history ) ) {
			return 0.0;
		}
		$dates  = array_keys( $history );
		$target = gmdate( 'Y-m-d', strtotime( end( $dates ) . ' -30 days' ) );
		$delta  = 0.0;
		foreach ( $history as $date => $snap ) {
			if ( $date > $target ) {
				continue;
			}
			$past = '' === $kb_slug ? $snap : ( $snap['kbs'][ $kb_slug ] ?? null );
			if ( isset( $past['score'] ) ) {
				$delta = round( $score - (float) $past['score'], 1 );
			}
		}
		return $delta;
	}

	/**
	 * Editor URL for a doc, resolved per request rather than read back from the
	 * insight payload.
	 *
	 * The scanners call get_edit_post_link() as they write their rows, and that
	 * function returns null whenever the current user cannot edit the post — which
	 * is every Action Scheduler pass, since those run with no user at all. So a row
	 * written by the nightly job stores `edit_link: null` and the screen's button
	 * silently never renders: 18 of 18 stale rows on the test site. Resolving here
	 * also scopes the link to whoever is actually looking at the screen instead of
	 * to whoever happened to trigger the scan.
	 *
	 * @param int $post_id
	 * @return string Empty when the post is gone or the user cannot edit it.
	 */
	private function doc_edit_link( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 ) {
			return '';
		}
		$link = get_edit_post_link( $post_id, 'raw' );
		return $link ? (string) $link : '';
	}

	/**
	 * The Content Health scorer from Pro's container, or null when it is not
	 * registered. Resolved through the container so the nightly job's hooks are
	 * not registered a second time by a REST request.
	 *
	 * @return \WPDeveloper\BetterDocsPro\Core\ContentHealthScorer|null
	 */
	private function health_scorer() {
		$class = \WPDeveloper\BetterDocsPro\Core\ContentHealthScorer::class;
		if ( ! class_exists( $class ) ) {
			return null;
		}
		$scorer = betterdocs()->container->get( $class );
		return $scorer instanceof $class ? $scorer : null;
	}

	/**
	 * Stale Content list + KPIs, paginated. Active items only (new/accepted).
	 */
	public function insights_stale( WP_REST_Request $request ) {
		if ( ! $this->insights_table_ready() ) {
			return $this->insights_unavailable( [
				'items' => [],
				'total' => 0,
				'page'  => 1,
				'kpis'  => [ 'total' => 0, 'critical' => 0, 'avg_staleness' => 0 ],
			] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;
		$order_by = $request->get_param( 'sort' ) === 'updated' ? 'updated_at DESC' : 'score DESC';

		// Scope to the selected KB through the doc the row is about. Stale rows are
		// per-doc and carry object_id = post_id, so the same post-scoped filter the
		// rest of Analytics uses works here — no dependence on the row's kb_id
		// column, which the local scanners leave at 0 (single-KB assumption, Phase 1).
		// Going through the taxonomy also keeps a doc that lives in two KBs visible
		// under both, which a single kb_id column could not express.
		$kb = $this->kb_filter( (string) $request->get_param( 'kb' ), 'object_id' );

		$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'stale' AND status IN ( 'new', 'accepted' ){$kb}" );
		$critical = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'stale' AND status IN ( 'new', 'accepted' ) AND score >= 75{$kb}" );
		$avg      = (float) $wpdb->get_var( "SELECT COALESCE( AVG( score ), 0 ) FROM {$table} WHERE type = 'stale' AND status IN ( 'new', 'accepted' ){$kb}" );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, score, status, payload FROM {$table}
				WHERE type = 'stale' AND status IN ( 'new', 'accepted' ){$kb}
				ORDER BY {$order_by} LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$items = [];
		foreach ( (array) $rows as $r ) {
			$payload = json_decode( (string) $r->payload, true );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$payload['id']     = (int) $r->id;
			$payload['status'] = $r->status;
			// Re-resolved, never trusted from the row: the nightly scanner writes this
			// as null because it runs with no user (see doc_edit_link()), which is why
			// the table's Edit button never appeared for auto-detected rows.
			$payload['edit_link'] = $this->doc_edit_link( $payload['post_id'] ?? 0 );
			$items[] = $payload;
		}

		return new WP_REST_Response(
			[
				'items' => $items,
				'total' => $total,
				'page'  => $page,
				'kpis'  => [
					'total'         => $total,
					'critical'      => $critical,
					'avg_staleness' => round( $avg, 1 ),
				],
			],
			200
		);
	}

	/**
	 * Content Health: score + components + trend + distribution + the
	 * lowest-scoring articles, for the whole site or one knowledge base.
	 *
	 * Every number here is scoped by `kb` through the doc each health row is
	 * about (object_id = post_id), the same taxonomy route the rest of Analytics
	 * uses — see kb_filter(). The trend is the exception: it can only come from
	 * history, so it reads the per-KB snapshot series and is short (or empty)
	 * until enough nightly passes have run since that series began.
	 */
	public function insights_health( WP_REST_Request $request ) {
		if ( ! $this->insights_table_ready() ) {
			return $this->insights_unavailable( [
				'score'           => 0,
				'grade'           => '',
				'scored'          => false,
				'delta'           => 0.0,
				'components'      => [],
				'trend'           => [],
				'distribution'    => [],
				'underperformers' => [],
				'total'           => 0,
				'page'            => 1,
				'per_page'        => (int) $request->get_param( 'per_page' ),
			] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		$kb_slug = (string) $request->get_param( 'kb' );
		$kb      = $this->kb_filter( $kb_slug, 'object_id' );

		$history = get_option( 'betterdocs_content_health_history', [] );
		$history = is_array( $history ) ? $history : [];
		ksort( $history );

		list( $score, $grade, $delta, $components, $scored ) = $this->health_site_score( $kb_slug );

		// 90-day trend as [ { date, score } ], trailing. A KB reads its own series
		// out of the snapshot's 'kbs' map and skips days recorded before that KB
		// had a snapshot, rather than plotting them as zeroes.
		$trend = [];
		foreach ( array_slice( $history, -90, null, true ) as $date => $snap ) {
			$point = '' === $kb_slug ? $snap : ( $snap['kbs'][ $kb_slug ] ?? null );
			if ( ! isset( $point['score'] ) ) {
				continue;
			}
			$trend[] = [ 'date' => $date, 'score' => (float) $point['score'] ];
		}

		// Distribution histogram across the per-doc health rows. Banded on the
		// rounded score — scores are fractional, so `>= 0 AND <= 20` then
		// `>= 21` silently dropped everything landing between two bands (154 of
		// 158 rows on the test corpus) and the bars under-counted the library.
		// Rounding is also what the reader sees: the article table shows 20.6 as 21.
		$bands = [
			'0-20'   => [ 0, 20 ],
			'21-40'  => [ 21, 40 ],
			'41-60'  => [ 41, 60 ],
			'61-80'  => [ 61, 80 ],
			'81-100' => [ 81, 100 ],
		];
		$distribution = [];
		foreach ( $bands as $label => $range ) {
			$distribution[] = [
				'band'  => $label,
				'count' => (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE type = 'health' AND ROUND( score ) >= %d AND ROUND( score ) <= %d{$kb}",
						$range[0],
						$range[1]
					)
				),
			];
		}

		// Underperformers: the weakest articles, worst first, paginated.
		//
		// This was a hard LIMIT 8 with no total, so the screen showed eight rows out
		// of however many scored docs the site has (158 here) and gave no way to
		// reach the rest — the list stopped without saying it had stopped. The KPI
		// strip and distribution above it are already whole-library numbers, so the
		// table was the one place that quietly wasn't.
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;

		$under_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'health'{$kb}" );
		$under       = [];
		$rows        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT payload, score FROM {$table} WHERE type = 'health'{$kb} ORDER BY score ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
		foreach ( (array) $rows as $r ) {
			$payload = json_decode( (string) $r->payload, true );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$post_id = (int) ( $payload['post_id'] ?? 0 );
			$under[] = [
				'title'    => $payload['title'] ?? '',
				'category' => $payload['category'] ?? '',
				'health'   => (float) $r->score,
				'grade'    => $payload['grade'] ?? $this->health_grade( (float) $r->score ),
				'weakest'  => $payload['weakest'] ?? '',
				// The article the row is about. Without these the "Improve" action had
				// nothing to act on and could only switch sub-tabs — to Stale Content,
				// a different measurement that frequently does not even list the doc.
				'post_id'   => $post_id,
				'edit_link' => $this->doc_edit_link( $post_id ),
			];
		}

		return new WP_REST_Response(
			[
				'score'          => $score,
				'grade'          => $grade,
				'scored'         => $scored,
				'delta'          => $delta,
				'components'     => $components,
				'trend'          => $trend,
				'distribution'   => $distribution,
				'underperformers'=> $under,
				// Paging state for the underperformers table only — every other key
				// here describes the whole (KB-scoped) library and is page-independent.
				'total'          => $under_total,
				'page'           => $page,
				'per_page'       => $per_page,
			],
			200
		);
	}

	/**
	 * Update one insight's lifecycle status (accept / dismiss / done).
	 */
	public function insight_status( WP_REST_Request $request ) {
		$id     = (int) $request->get_param( 'id' );
		$status = (string) $request->get_param( 'status' );

		$store   = new \WPDeveloper\BetterDocsPro\Core\AnalyticsInsightStore();
		$changed = $store->set_status( $id, $status );

		return new WP_REST_Response( [ 'success' => true, 'id' => $id, 'status' => $status, 'changed' => (int) $changed ], 200 );
	}

	/**
	 * Content Gaps (cloud-detected) — paginated, active items only.
	 */
	public function insights_gaps( WP_REST_Request $request ) {
		if ( ! $this->insights_table_ready() ) {
			return $this->insights_unavailable( [
				'items'           => [],
				'total'           => 0,
				'page'            => 1,
				'ai_connected'    => false,
				'last_sync'       => 0,
				'has_signals'     => false,
				'mode'            => 'local',
				'cloud_available' => false,
			] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type = 'gap' AND status IN ( 'new', 'accepted' )" );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, score, status, payload FROM {$table}
				WHERE type = 'gap' AND status IN ( 'new', 'accepted' )
				ORDER BY score DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$items = [];
		foreach ( (array) $rows as $r ) {
			$payload = json_decode( (string) $r->payload, true );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$payload['id']     = (int) $r->id;
			$payload['status'] = $r->status;
			$items[] = $payload;
		}

		$service = $this->ci_service();

		return new WP_REST_Response( [
			'items'        => $items,
			'total'        => $total,
			'page'         => $page,
			'ai_connected' => $this->ai_key_connected(),
			'last_sync'    => (int) get_option( 'betterdocs_ci_last_sync', 0 ),
			// Lets the UI say "no searches to analyse yet" instead of implying
			// detection ran and found your docs cover everything.
			'has_signals'  => $this->has_query_signals(),
			// Which engine produced these rows — 'cloud' (semantic, needs the AI
			// Chatbot add-on) or 'local' (lexical, from search analytics alone).
			// The screen reads differently in each mode and says which it is
			// rather than presenting two different measurements as one.
			'mode'         => $service ? $service->gap_mode() : 'local',
			// Whether switching to the semantic engine is actually available, so
			// the upsell is only shown to sites that can act on it.
			'cloud_available' => $service ? (bool) $service->cloud_gaps_available() : false,
			// So the upsell names the actual blocker (no add-on vs unlicensed vs
			// unconfigured) instead of always selling the add-on.
			'chatbot_state' => $service ? $service->chatbot_state() : 'none',
		], 200 );
	}

	/**
	 * The Content Intelligence service, or null when Pro's container has not
	 * registered it.
	 *
	 * @return \WPDeveloper\BetterDocsPro\Core\ContentIntelligenceService|null
	 */
	protected function ci_service() {
		$class = \WPDeveloper\BetterDocsPro\Core\ContentIntelligenceService::class;
		if ( ! class_exists( $class ) ) {
			return null;
		}
		$service = betterdocs()->container->get( $class );
		return $service instanceof $class ? $service : null;
	}

	/**
	 * Does the Content Intelligence table exist?
	 *
	 * Every insights read runs raw SQL against {prefix}betterdocs_analytics_insights.
	 * When the table is absent — an upgrade that has not run its migration yet, a
	 * restore from a partial dump, a site where dbDelta failed — each of those
	 * queries raises a database error and returns null, so the screen renders zeros
	 * built out of failures and the log fills with MySQL 1146. Checked once per
	 * request and cached, since a single overview response makes a dozen queries
	 * and the answer cannot change midway.
	 */
	protected function insights_table_ready() {
		static $ready = null;

		if ( null !== $ready ) {
			return $ready;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		$ready = ( $found === $table );

		return $ready;
	}

	/**
	 * The response an insights endpoint returns when the table is missing.
	 *
	 * A 200 with an explicit `available: false` rather than an error: the screen's
	 * job in this state is to say "not ready yet", and a 500 would render it as a
	 * crash the reader cannot act on. `$payload` carries the endpoint's own empty
	 * shape so the client's destructuring still finds the keys it expects.
	 *
	 * @param array $payload Endpoint-shaped defaults.
	 */
	protected function insights_unavailable( array $payload = [] ) {
		return new WP_REST_Response(
			array_merge(
				[
					'available' => false,
					'message'   => __( 'Content Intelligence data is not ready yet. It is prepared when the plugin finishes upgrading.', 'betterdocs-pro' ),
				],
				$payload
			),
			200
		);
	}

	/**
	 * Whether the site holds any reader signal to detect gaps from.
	 */
	protected function has_query_signals() {
		$service = $this->ci_service();
		return $service ? (bool) $service->has_query_signals() : false;
	}

	/**
	 * The active duplicate pairs as decoded payloads, scoped to `$kb_slug`.
	 *
	 * A duplicate is about two docs, not one, so it cannot be scoped by the
	 * single object_id column the other modules filter on — a pair is kept when
	 * *either* side is in the knowledge base, which is what makes the pair
	 * actionable from that KB's screen. Cheap to do in PHP: the set is capped at
	 * 100 rows and membership resolves in one term_relationships query.
	 *
	 * @return array<int,array> payloads, each carrying id + status
	 */
	private function duplicate_items( $kb_slug = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		$rows = $wpdb->get_results(
			"SELECT id, score, status, payload FROM {$table}
			WHERE type = 'duplicate' AND status IN ( 'new', 'accepted' )
			ORDER BY score DESC LIMIT 100"
		);

		$items = [];
		foreach ( (array) $rows as $r ) {
			$payload = json_decode( (string) $r->payload, true );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$payload['id']     = (int) $r->id;
			$payload['status'] = $r->status;
			$items[] = $payload;
		}

		if ( '' === $kb_slug || empty( $items ) ) {
			return $items;
		}

		$term = get_term_by( 'slug', $kb_slug, 'knowledge_base' );
		if ( ! $term || is_wp_error( $term ) ) {
			return $items; // unknown slug — same no-op kb_filter() applies
		}

		$ids = [];
		foreach ( $items as $item ) {
			$ids[] = (int) ( $item['a_id'] ?? 0 );
			$ids[] = (int) ( $item['b_id'] ?? 0 );
		}
		$ids = array_filter( array_unique( $ids ) );
		if ( empty( $ids ) ) {
			return [];
		}

		$in     = implode( ',', array_map( 'intval', $ids ) );
		$in_kb  = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in is an int-cast id list.
				"SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d AND object_id IN ( {$in} )",
				(int) $term->term_taxonomy_id
			)
		);
		$in_kb = array_flip( array_map( 'intval', (array) $in_kb ) );

		return array_values(
			array_filter(
				$items,
				function ( $item ) use ( $in_kb ) {
					return isset( $in_kb[ (int) ( $item['a_id'] ?? 0 ) ] ) || isset( $in_kb[ (int) ( $item['b_id'] ?? 0 ) ] );
				}
			)
		);
	}

	/**
	 * Duplicate / overlap pairs (cloud-detected).
	 */
	public function insights_duplicates( WP_REST_Request $request ) {
		if ( ! $this->insights_table_ready() ) {
			return $this->insights_unavailable( [
				'items'     => [],
				'total'     => 0,
				'last_sync' => 0,
				'mode'      => 'local',
			] );
		}

		$items = $this->duplicate_items( (string) $request->get_param( 'kb' ) );

		$service = $this->ci_service();

		return new WP_REST_Response( [
			'items'     => $items,
			'total'     => count( $items ),
			'last_sync' => (int) get_option( 'betterdocs_ci_last_sync', 0 ),
			// Duplicate detection is embedding-only — there is no honest lexical
			// stand-in for "these two articles say the same thing", since near
			// duplicates routinely share little vocabulary. In local mode the
			// screen has to say it was never analysed rather than show an empty
			// list that reads as "your articles cover distinct ground".
			'mode'      => $service ? $service->gap_mode() : 'local',
			// So the locked panel can name the actual blocker (add-on missing vs
			// unlicensed vs unconfigured vs first-sync-pending) instead of always
			// telling a site with the chatbot installed to go get the chatbot.
			'chatbot_state' => $service ? $service->chatbot_state() : 'none',
		], 200 );
	}

	/**
	 * Manually run the analysis: gaps (local or cloud) plus the Health and Stale
	 * passes. Needs an editor cap (mutation — see permission_check).
	 *
	 * Reports what actually happened. This used to answer `success: true` before
	 * anything had been inspected, so a cloud sync whose every request failed —
	 * missing token, network error, 4xx from the service — still told the user it
	 * had worked while the screen sat unchanged with no explanation.
	 */
	public function insights_sync( WP_REST_Request $request ) {
		$service = $this->ci_service();
		if ( ! $service ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'errors'  => [ __( 'Content Intelligence is unavailable on this site.', 'betterdocs-pro' ) ],
				],
				503
			);
		}

		$result = $service->run_sync();
		$errors = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : [];

		return new WP_REST_Response(
			[
				// The gap engine either found something or failed trying; the Health
				// and Stale walkers are queued rather than finished, so the client is
				// told which ones started instead of being handed a completion it
				// would have to invent.
				'success'   => empty( $errors ),
				'mode'      => $result['mode'] ?? 'local',
				'gaps'      => $result['gaps'] ?? null,
				'scans'     => $result['scans'] ?? [],
				'errors'    => $errors,
				'last_sync' => (int) ( $result['last_sync'] ?? get_option( 'betterdocs_ci_last_sync', 0 ) ),
			],
			empty( $errors ) ? 200 : 500
		);
	}

	/**
	 * Is any key available for a generative action? Gates the BYOK actions.
	 *
	 * Resolved through ContentIntelligenceService's ladder — the chatbot's key
	 * when that add-on is configured, the AI Content Suite key otherwise — so a
	 * site already running the chatbot is not asked to supply a second key before
	 * it can draft a gap title. Falls back to the raw AI Content Suite setting if
	 * Pro's container has not registered the service.
	 */
	private function ai_key_connected() {
		$service = $this->ci_service();
		if ( $service ) {
			return null !== $service->ai_action_credentials();
		}
		return ! empty( betterdocs()->settings->get( 'ai_autowrite_api_key', '' ) );
	}

	/**
	 * Run one chat completion for a gap draft against explicitly-resolved
	 * credentials.
	 *
	 * WriteWithAI::generate_openai_response_raw() would be the shorter route, but
	 * it builds its provider from the *active* platform and the AI Content Suite
	 * key with no way to pass another. That is the wrong key for a site running
	 * the chatbot, which has one on file already. Going through make_with() keeps
	 * the ladder honest; the fallback covers the case where the service is not
	 * registered and the ladder produced nothing.
	 *
	 * @param array|null $creds  from ContentIntelligenceService::ai_action_credentials()
	 * @param string     $system
	 * @param string     $user
	 * @return array { success, content|error, model, key_source }
	 */
	private function ai_draft( $creds, $system, $user ) {
		if ( ! $creds ) {
			$ai  = betterdocs()->container->get( \WPDeveloper\BetterDocs\Core\WriteWithAI::class );
			$out = $ai->generate_openai_response_raw( $system, $user, 0.4 );
			$out['key_source'] = 'content_suite';
			return $out;
		}

		$factory  = new \WPDeveloper\BetterDocs\AI\ProviderFactory( betterdocs()->settings );
		$provider = $factory->make_with( $creds['platform'], $creds['api_key'], $creds['model'] );

		$result = $provider->chat(
			[
				[ 'role' => 'system', 'content' => $system ],
				[ 'role' => 'user', 'content' => $user ],
			],
			[
				'max_tokens'  => (int) betterdocs()->settings->get( 'ai_autowrite_max_token', 2500 ),
				'temperature' => 0.4,
				'context'     => 'content_intelligence',
				'timeout'     => 120,
			]
		);

		if ( is_wp_error( $result ) ) {
			return [
				'success'    => false,
				'error'      => $result->get_error_message(),
				'model'      => $creds['model'],
				'key_source' => $creds['source'],
			];
		}

		return [
			'success'    => true,
			'content'    => isset( $result['content'] ) ? $result['content'] : '',
			'model'      => isset( $result['model'] ) ? $result['model'] : $creds['model'],
			'key_source' => $creds['source'],
		];
	}

	/**
	 * BYOK: draft a suggested article title + outline for a content gap from its
	 * clustered reader queries, using the site's own Write-with-AI OpenAI key
	 * (the free WriteWithAI path — WP → OpenAI directly, key never leaves the
	 * site). The suggestion is persisted onto the gap row.
	 */
	public function insight_gap_draft( WP_REST_Request $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';
		$id    = (int) $request->get_param( 'id' );

		$creds = $this->ci_service() ? $this->ci_service()->ai_action_credentials() : null;
		if ( ! $creds && ! $this->ai_key_connected() ) {
			return new WP_REST_Response( [
				'success'   => false,
				'connected' => false,
				'message'   => __( 'Connect an API key under Settings → AI Content Suite to generate drafts.', 'betterdocs-pro' ),
			], 400 );
		}

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, payload FROM {$table} WHERE id = %d AND type = 'gap'", $id ) );
		if ( ! $row ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Gap not found.', 'betterdocs-pro' ) ], 404 );
		}

		$payload = json_decode( (string) $row->payload, true );
		$queries = is_array( $payload ) ? (array) ( $payload['queries'] ?? [] ) : [];
		if ( empty( $queries ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'No reader queries to draft from.', 'betterdocs-pro' ) ], 400 );
		}

		$query_list = implode( "\n", array_map( function ( $q ) { return '- ' . $q; }, array_slice( $queries, 0, 12 ) ) );
		$system     = 'You are a documentation strategist. Given the real searches readers ran that returned no good answer, propose ONE concise, specific help-article title that would answer them, followed by a short 3–5 bullet outline. Respond as plain text: first line the title, then the bullets.';
		$user       = "Reader searches:\n{$query_list}\n\nSuggest the article title and outline.";

		$result = $this->ai_draft( $creds, $system, $user );

		if ( empty( $result['success'] ) ) {
			return new WP_REST_Response( [
				'success' => false,
				'message' => isset( $result['error'] ) ? $result['error'] : __( 'AI request failed.', 'betterdocs-pro' ),
			], 502 );
		}

		$content = trim( (string) $result['content'] );
		// First non-empty line is the suggested title.
		$lines   = array_values( array_filter( array_map( 'trim', explode( "\n", $content ) ) ) );
		$title   = $lines ? preg_replace( '/^#+\s*|^title:\s*/i', '', $lines[0] ) : '';

		// Persist the suggestion onto the gap row.
		if ( is_array( $payload ) ) {
			$payload['suggested_title']   = $title;
			$payload['suggested_outline'] = $content;
			$wpdb->update( $table, [ 'payload' => wp_json_encode( $payload ), 'updated_at' => current_time( 'mysql', true ) ], [ 'id' => $id ], [ '%s', '%s' ], [ '%d' ] );
		}

		return new WP_REST_Response( [
			'success'    => true,
			'title'      => $title,
			'outline'    => $content,
			'model'      => isset( $result['model'] ) ? $result['model'] : null,
			// Which key paid for this call — 'chatbot' or 'content_suite'.
			'key_source' => isset( $result['key_source'] ) ? $result['key_source'] : null,
		], 200 );
	}
}
