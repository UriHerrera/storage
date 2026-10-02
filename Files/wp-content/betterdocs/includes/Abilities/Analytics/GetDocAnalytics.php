<?php
/**
 * Per-doc analytics ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesDocs;

/**
 * How one doc is doing: views, unique views and the three reaction counts, for
 * all time or a date range, optionally day by day.
 *
 * **What the numbers mean matters more than the numbers.** Free records a view
 * from a JavaScript beacon on a **single doc page** — `AnalyticsTracker` posts
 * to `betterdocs/v1/analytics/view` once the page is open — so archives, search
 * results and REST reads count for nothing, a visitor with JavaScript off is
 * invisible, and the `analytics_from` setting can exclude logged-in users or
 * guests entirely. `unique_views` only moves when the browser says this is its
 * first view of that doc **and** `unique_visitor_count` is on. The reactions
 * come from the feedback widget on the same page.
 *
 * Site-wide analytics — leading docs, categories, searches — is
 * `bd-get-analytics`, which needs BetterDocs Pro.
 *
 * @since 4.9.0
 */
class GetDocAnalytics extends AbilityBase {

	use ShapesDocs;

	/**
	 * Post meta BetterDocs Pro keeps its own view rollup in, for the "popular
	 * docs" ordering its widgets use. Reported when it exists, never written.
	 *
	 * @since 4.9.0
	 */
	const PRO_VIEWS_META = '_betterdocs_meta_views';

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/get-doc-analytics';
		$this->label       = __( 'Get doc analytics', 'betterdocs' );
		$this->description = __( 'Read one doc\'s views, unique views and reactions, for all time or a date range, optionally broken down by day. BetterDocs Free counts a view only when someone opens the single doc page in a browser, so these are page views, not REST reads. Site-wide analytics is bd-get-analytics, which needs BetterDocs Pro.', 'betterdocs' );
		$this->capability  = 'read_docs_analytics';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.5,
			'openWorldHint' => false
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'doc_id' ],
			'properties'           => [
				'doc_id'     => [
					'type'        => 'integer',
					'description' => __( 'The doc to report on. Required.', 'betterdocs' )
				],
				'start_date' => [
					'type'        => 'string',
					'description' => __( 'First day to count, as YYYY-MM-DD. Omit for all time.', 'betterdocs' )
				],
				'end_date'   => [
					'type'        => 'string',
					'description' => __( 'Last day to count, as YYYY-MM-DD, inclusive. Omit for all time.', 'betterdocs' )
				],
				'group_by'   => [
					'type'        => 'string',
					'enum'        => [ 'none', 'day' ],
					'default'     => 'none',
					'description' => __( 'day also returns a per-day series. Days with no activity are absent from it rather than reported as zero.', 'betterdocs' )
				]
			],
			'default'              => []
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		$day = [
			'date'         => [ 'type' => 'string' ],
			'views'        => [ 'type' => 'integer' ],
			'unique_views' => [ 'type' => 'integer' ],
			'happy'        => [ 'type' => 'integer' ],
			'normal'       => [ 'type' => 'integer' ],
			'sad'          => [ 'type' => 'integer' ]
		];

		return [
			'type'       => 'object',
			'properties' => [
				'doc_id'       => [ 'type' => 'integer' ],
				'title'        => [ 'type' => 'string' ],
				'url'          => [ 'type' => 'string' ],
				'range'        => [
					'type'       => 'object',
					'properties' => [
						'start' => [ 'type' => [ 'string', 'null' ] ],
						'end'   => [ 'type' => [ 'string', 'null' ] ]
					]
				],
				'views'        => [ 'type' => 'integer' ],
				'unique_views' => [ 'type' => 'integer' ],
				'reactions'    => [
					'type'       => 'object',
					'properties' => [
						'happy'  => [ 'type' => 'integer' ],
						'normal' => [ 'type' => 'integer' ],
						'sad'    => [ 'type' => 'integer' ]
					]
				],
				'series'       => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => $day
					]
				],
				'pro_extras'   => [
					'type'       => 'object',
					'properties' => [
						'meta_views' => [ 'type' => 'integer' ]
					]
				]
			]
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$doc_id = isset( $input['doc_id'] ) ? (int) $input['doc_id'] : 0;
		$doc    = $this->require_doc( $doc_id );

		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		$start = $this->date_input( $input, 'start_date' );

		if ( is_wp_error( $start ) ) {
			return $start;
		}

		$end = $this->date_input( $input, 'end_date' );

		if ( is_wp_error( $end ) ) {
			return $end;
		}

		if ( null !== $start && null !== $end && $start > $end ) {
			return AbilityError::invalid_input(
				'start_date',
				__( 'start_date is after end_date.', 'betterdocs' )
			);
		}

		$series  = 'day' === ( isset( $input['group_by'] ) ? $input['group_by'] : 'none' );
		$rows    = $this->rows( $doc_id, $start, $end, $series );
		$totals  = [
			'views'        => 0,
			'unique_views' => 0,
			'happy'        => 0,
			'normal'       => 0,
			'sad'          => 0
		];
		$per_day = [];

		foreach ( $rows as $row ) {
			$totals['views']        += (int) $row['views'];
			$totals['unique_views'] += (int) $row['unique_views'];
			$totals['happy']        += (int) $row['happy'];
			$totals['normal']       += (int) $row['normal'];
			$totals['sad']          += (int) $row['sad'];

			if ( $series ) {
				$per_day[] = [
					'date'         => (string) $row['date'],
					'views'        => (int) $row['views'],
					'unique_views' => (int) $row['unique_views'],
					'happy'        => (int) $row['happy'],
					'normal'       => (int) $row['normal'],
					'sad'          => (int) $row['sad']
				];
			}
		}

		$out = [
			'doc_id'       => $doc_id,
			'title'        => (string) get_the_title( $doc_id ),
			'url'          => (string) get_permalink( $doc_id ),
			'range'        => [
				'start' => $start,
				'end'   => $end
			],
			'views'        => $totals['views'],
			'unique_views' => $totals['unique_views'],
			'reactions'    => [
				'happy'  => $totals['happy'],
				'normal' => $totals['normal'],
				'sad'    => $totals['sad']
			]
		];

		if ( $series ) {
			$out['series'] = $per_day;
		}

		$extras = $this->pro_extras( $doc_id );

		if ( null !== $extras ) {
			$out['pro_extras'] = $extras;
		}

		return $out;
	}

	/**
	 * The analytics rows for one doc, as `[ date, views, unique_views, happy,
	 * normal, sad ]`.
	 *
	 * A read, and only a read. Two shapes rather than one because a doc viewed
	 * every day for three years has a thousand rows and the common call wants a
	 * single number: without `group_by: day` the database does the summing.
	 *
	 * @since 4.9.0
	 *
	 * @param int         $doc_id Doc id.
	 * @param string|null $start  `YYYY-MM-DD` or null.
	 * @param string|null $end    `YYYY-MM-DD` or null.
	 * @param bool        $series Whether the per-day rows are wanted.
	 * @return array[]
	 */
	protected function rows( $doc_id, $start, $end, $series ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'betterdocs_analytics';
		$where  = 'post_id = %d';
		$params = [ (int) $doc_id ];

		if ( null !== $start ) {
			$where   .= ' AND created_at >= %s';
			$params[] = $start;
		}

		if ( null !== $end ) {
			$where   .= ' AND created_at <= %s';
			$params[] = $end;
		}

		$columns = $series
			? 'created_at AS date, impressions AS views, unique_visit AS unique_views, happy, normal, sad'
			: "'' AS date, SUM(impressions) AS views, SUM(unique_visit) AS unique_views, SUM(happy) AS happy, SUM(normal) AS normal, SUM(sad) AS sad";

		$order = $series ? ' ORDER BY created_at ASC' : '';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,PluginCheck.Security.DirectDB.UnescapedDBParameter -- read-only aggregate over BetterDocs' own analytics table; $table and $columns are literals, the %d/%s placeholders live in $where (also built from literals) so the sniff cannot see them, every value is prepared, and there is no cache layer for this table (the plugin's own REST readers do the same).
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE {$where}{$order}",
				$params
			),
			ARRAY_A
		);
		// phpcs:enable

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * A `YYYY-MM-DD` input, or null when it was not sent.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $input Validated input.
	 * @param string $field Field name.
	 * @return string|null|\WP_Error
	 */
	protected function date_input( array $input, $field ) {
		if ( ! isset( $input[ $field ] ) || '' === trim( (string) $input[ $field ] ) ) {
			return null;
		}

		$value = trim( (string) $input[ $field ] );
		$parts = explode( '-', $value );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) || ! checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ) {
			return AbilityError::invalid_input(
				$field,
				sprintf(
					/* translators: 1: field name, 2: the value sent. */
					__( '%1$s must be a date as YYYY-MM-DD; got "%2$s".', 'betterdocs' ),
					$field,
					$value
				)
			);
		}

		return $value;
	}

	/**
	 * Pro's own numbers for this doc, when Pro has recorded any.
	 *
	 * @since 4.9.0
	 *
	 * @param int $doc_id Doc id.
	 * @return array|null
	 */
	protected function pro_extras( $doc_id ) {
		if ( ! metadata_exists( 'post', (int) $doc_id, self::PRO_VIEWS_META ) ) {
			return null;
		}

		return [ 'meta_views' => (int) get_post_meta( (int) $doc_id, self::PRO_VIEWS_META, true ) ];
	}
}
