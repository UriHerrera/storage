<?php

namespace WPDeveloper\BetterDocs\Insights;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Admin\ReportEmail;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product-usage analytics collector (Free tier).
 *
 * Hooks the `betterdocs_insights_data` filter exposed by {@see Insights::get_data()}
 * and injects BetterDocs-specific metric groups (all prefixed `bd_`) into the daily
 * wpinsight payload. Pro and the AI Chatbot add-on register their own collectors on
 * the same filter — each plugin owns its own metrics, so a tier's keys appear in the
 * payload only when that plugin is active.
 *
 * Design notes (see docs/insights-tracking.md for the full reference):
 *  - The client is WRITE-ONLY. It ships a current snapshot; wpinsight owns history and
 *    reconstructs true lifetime from the daily time series. The `totals` group is the
 *    sum of rows that currently exist locally — it legitimately drops if a user clears
 *    their analytics, which is why it is NOT called "lifetime".
 *  - The whole computed slice is cached in a 24h transient so the daily cron computes
 *    at most once per day; the cache is busted when settings are saved.
 *
 * MAINTENANCE CONVENTION: when a new user-facing feature/setting is introduced, the
 * same PR must (1) add its flag/metric to the relevant group here and (2) update
 * docs/insights-tracking.md.
 *
 * @since 4.5.4
 */
class Collector extends Base {
	/**
	 * Transient key for the cached metric slice.
	 */
	const CACHE_KEY = 'betterdocs_insights_cache';

	/**
	 * Free-tier boolean feature flags. Pro-gated flags (multiple_kb, advance_search,
	 * enable_content_restriction, enable_glossaries, enable_encyclopedia, …) are
	 * intentionally NOT here — they ship from the Pro collector.
	 *
	 * @var string[]
	 */
	const FEATURE_FLAGS = [
		'enable_toc',
		'enable_sticky_toc',
		'live_search',
		'enable_faq_schema',
		'enable_reporting',
		'enable_estimated_reading_time',
		'enable_navigation',
		'enable_comment',
		'enable_breadcrumb',
		'enable_tags',
		'enable_print_icon',
		'enable_sidebar_cat_list',
		'masonry_layout',
		'nested_subcategory',
	];

	public function __construct() {
		add_filter( 'betterdocs_insights_data', [ $this, 'collect' ], 10, 1 );
		// Recompute sooner than the 24h TTL when settings change.
		add_action( 'betterdocs::settings::saved', [ $this, 'flush_cache' ] );
	}

	/**
	 * Filter callback: merge the cached `bd_*` groups into the tracking payload.
	 *
	 * @param array $body
	 * @return array
	 */
	public function collect( $body ) {
		$cached = get_transient( self::CACHE_KEY );
		if ( ! is_array( $cached ) ) {
			$cached = [
				'bd_features'   => $this->features(),
				'bd_counts'     => $this->content_counts(),
				'bd_engagement' => $this->engagement(),
				'bd_builders'   => $this->builders(),
				'bd_ai'         => $this->ai(),
				'bd_config'     => $this->config_posture(),
			];
			set_transient( self::CACHE_KEY, $cached, DAY_IN_SECONDS );
		}

		// wpinsight only persists base fields + a single `optional_data` field;
		// nest the metric groups there (encoded to JSON in Insights::get_data()).
		$body = (array) $body;
		$opt  = ( isset( $body['optional_data'] ) && is_array( $body['optional_data'] ) ) ? $body['optional_data'] : [];

		$body['optional_data'] = array_merge( $opt, $cached );

		return $body;
	}

	public function flush_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Booleanized map of which Free features are turned on.
	 */
	protected function features() {
		$settings = betterdocs()->settings;
		$flags    = [];
		foreach ( self::FEATURE_FLAGS as $key ) {
			$flags[ $key ] = (int) (bool) $settings->get( $key, false );
		}
		return $flags;
	}

	/**
	 * Content volume counts (all WP-cached or single indexed COUNTs).
	 */
	protected function content_counts() {
		$docs = (int) ( wp_count_posts( 'docs' )->publish ?? 0 );
		$faqs = (int) ( wp_count_posts( 'betterdocs_faq' )->publish ?? 0 );

		$doc_category = $this->term_count( 'doc_category' );

		return [
			'docs'                   => $docs,
			'faqs'                   => $faqs,
			'doc_category'           => $doc_category,
			'doc_tag'                => $this->term_count( 'doc_tag' ),
			'glossaries'             => $this->term_count( 'glossaries' ),
			'betterdocs_faq_category' => $this->term_count( 'betterdocs_faq_category' ),
			'avg_docs_per_category'  => $doc_category > 0 ? round( $docs / $doc_category, 2 ) : 0,
		];
	}

	/**
	 * Single indexed term COUNT, guarded against WP_Error / unregistered taxonomy.
	 */
	protected function term_count( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}
		$count = wp_count_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );
		return is_wp_error( $count ) ? 0 : (int) $count;
	}

	/**
	 * Runtime aggregates: current retained `totals` + rolling `last_7d` window.
	 */
	protected function engagement() {
		return [
			'totals'  => $this->engagement_totals(),
			'last_7d' => $this->engagement_last_7d(),
		];
	}

	/**
	 * Current retained totals — SUM over whatever rows currently exist. NOT a
	 * protected lifetime; wpinsight reconstructs true lifetime from the daily series.
	 */
	protected function engagement_totals() {
		global $wpdb;

		$analytics = $wpdb->get_row(
			"SELECT SUM(impressions) AS views, SUM(unique_visit) AS unique_visit, SUM(happy) AS happy, SUM(sad) AS sad, SUM(normal) AS normal
			 FROM {$wpdb->prefix}betterdocs_analytics"
		);

		$search = $wpdb->get_row(
			"SELECT SUM(count) AS search_found, SUM(not_found_count) AS search_not_found, COUNT(DISTINCT keyword_id) AS distinct_keywords
			 FROM {$wpdb->prefix}betterdocs_search_log"
		);

		return [
			'views'             => (int) ( $analytics->views ?? 0 ),
			'unique_visit'      => (int) ( $analytics->unique_visit ?? 0 ),
			'happy'             => (int) ( $analytics->happy ?? 0 ),
			'sad'               => (int) ( $analytics->sad ?? 0 ),
			'normal'            => (int) ( $analytics->normal ?? 0 ),
			'search_found'      => (int) ( $search->search_found ?? 0 ),
			'search_not_found'  => (int) ( $search->search_not_found ?? 0 ),
			'distinct_keywords' => (int) ( $search->distinct_keywords ?? 0 ),
		];
	}

	/**
	 * Rolling last-7-day window, reusing ReportEmail's date-scoped aggregation.
	 */
	protected function engagement_last_7d() {
		/** @var ReportEmail $report */
		$report = betterdocs()->container->get( ReportEmail::class );

		$end   = current_time( 'Y-m-d' );
		$start = gmdate( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) );

		$views  = $report->get_views( $start, $end );
		$search = $report->get_search( $start, $end );

		$views  = isset( $views[0] ) ? $views[0] : null;
		$search = isset( $search[0] ) ? $search[0] : null;

		return [
			'views'            => (int) ( $views->views ?? 0 ),
			'unique_visit'     => (int) ( $views->unique_visit ?? 0 ),
			'reactions'        => (int) ( $views->reactions ?? 0 ),
			'search_count'     => (int) ( $search->search_count ?? 0 ),
			'search_found'     => (int) ( $search->search_found ?? 0 ),
			'search_not_found' => (int) ( $search->search_not_found_count ?? 0 ),
			'new_docs'         => (int) $report->count_new_docs( $start, $end ),
		];
	}

	/**
	 * Which builder(s) the KB is authored with. Cheap heuristics — no post_content
	 * table scan; Gutenberg/shortcode are detected over the 20 most-recent docs.
	 */
	protected function builders() {
		global $wpdb;

		$elementor = (int) (bool) $wpdb->get_var(
			"SELECT 1 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_elementor_edit_mode' AND p.post_type = 'docs' LIMIT 1"
		);

		$gutenberg = 0;
		$shortcode = 0;
		$recent    = get_posts(
			[
				'post_type'        => 'docs',
				'post_status'      => 'publish',
				'posts_per_page'   => 20,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			]
		);
		foreach ( $recent as $post ) {
			if ( ! $gutenberg && has_blocks( $post->post_content ) ) {
				$gutenberg = 1;
			}
			if ( ! $shortcode && strpos( (string) $post->post_content, '[betterdocs' ) !== false ) {
				$shortcode = 1;
			}
			if ( $gutenberg && $shortcode ) {
				break;
			}
		}

		return [
			'elementor' => $elementor,
			'gutenberg' => $gutenberg,
			'shortcode' => $shortcode,
		];
	}

	/**
	 * AI adoption — booleans + non-secret model names. NEVER the API key value.
	 */
	protected function ai() {
		$settings = betterdocs()->settings;

		// `show_glossary_suggestions` defaults to true and has no settings-UI field, so
		// reporting it raw would mark every Free-only site as "using" a feature it can
		// never run. Mirror the runtime gate in Core\WriteWithAI: the glossaries taxonomy
		// is registered for Pro only, so the "Suggest glossaries" offer is reachable only
		// when the glossary feature is on AND Pro is active. Do NOT "simplify" this back
		// to a bare setting read. ($has_glossary_terms from the runtime gate is
		// deliberately excluded — that is per-request content state, not config posture.)
		$glossary_suggestions = (int) (
			(bool) $settings->get( 'enable_glossaries', false )
			&& (bool) $settings->get( 'show_glossary_suggestions', true )
			&& betterdocs()->is_pro_active()
		);

		return [
			'write_with_ai'         => (int) (bool) $settings->get( 'enable_write_with_ai', false ),
			'article_summary'       => (int) (bool) $settings->get( 'enable_article_summary', false ),
			'chatbot'               => (int) (bool) $settings->get( 'enable_ai_chatbot', false ),
			'autowrite_key_set'     => (int) ! empty( $settings->get( 'ai_autowrite_api_key', '' ) ),
			'chatbot_key_set'       => (int) ! empty( $settings->get( 'ai_chatbot_api_key', '' ) ),
			'write_with_ai_model'   => (string) $settings->get( 'write_with_ai_model', '' ),
			'article_summary_model' => (string) $settings->get( 'article_summary_model', '' ),
			// AI adoption toggles introduced with the 4.6.3 AI surfaces.
			'sample_docs'           => (int) (bool) $settings->get( 'enable_ai_sample_docs', true ),
			'docs_ai_suite'         => (int) (bool) $settings->get( 'enable_docs_ai_suite', true ),
			'glossary_suggestions'  => $glossary_suggestions,
			// Per-feature usage counts (how many times each AI action ran). All keys
			// always present (default 0); see Utils\AIUsage for the recording side.
			'usage'                 => \WPDeveloper\BetterDocs\Utils\AIUsage::snapshot(),
		];
	}

	/**
	 * Non-sensitive configuration posture (no emails/URLs/free-text).
	 */
	protected function config_posture() {
		$settings = betterdocs()->settings;

		return [
			'layout'              => (string) $settings->get( 'layout', '' ),
			'permalink_structure' => (string) $settings->get( 'permalink_structure', '' ),
			'reporting_frequency' => (string) $settings->get( 'reporting_frequency', '' ),
			'posts_number'        => (int) $settings->get( 'posts_number', 0 ),
			'column_number'       => (int) $settings->get( 'column_number', 0 ),
			// Analytics Overview (4.6.3) posture — key-set booleans only, never secrets.
			'ga4_configured'      => (int) ! empty( $settings->get( 'ga4_api_secret', '' ) ),
			'geoip_configured'    => (int) ! empty( $settings->get( 'maxmind_license_key', '' ) ),
			'dark_mode'           => (int) (bool) $settings->get( 'dark_mode', false ),
		];
	}
}
