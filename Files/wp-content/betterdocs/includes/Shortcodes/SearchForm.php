<?php
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

class SearchForm extends Shortcode {
	public function __construct( Settings $settings, Query $query, Helper $helper, Defaults $defaults ) {
		parent::__construct( $settings, $query, $helper, $defaults );

		add_action( 'wp_ajax_nopriv_betterdocs_get_search_result', [ $this, 'get_search_results' ] );
		add_action( 'wp_ajax_betterdocs_get_search_result', [ $this, 'get_search_results' ] );
	}

	/**
	 * Modify search query to properly handle non-English characters
	 * 
	 * @param string $search The search SQL for WHERE clause
	 * @param WP_Query $query The WP_Query instance
	 * @return string Modified search SQL
	 */
	public function improve_search_for_non_english( $search, $query ) {
		global $wpdb;

		// Only modify our BetterDocs search queries
		if ( ! isset( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] !== 'docs' ) {
			return $search;
		}

		// Only modify if there's a search term
		if ( empty( $query->query_vars['s'] ) ) {
			return $search;
		}

		$search_term = $query->query_vars['s'];

		// If the search term contains non-ASCII characters, we need to ensure proper UTF-8 handling
		if ( preg_match('/[^\x00-\x7F]/', $search_term) ) {
			// Get the search term with proper escaping
			$like = '%' . $wpdb->esc_like( $search_term ) . '%';

			// Build a UTF-8 compatible search query
			// Search in post_title, post_content, and post_excerpt
			// Note: Removed COLLATE clause to avoid collation mismatch with TranslatePress tables
			$search = $wpdb->prepare(
				" AND (
					({$wpdb->posts}.post_title LIKE %s)
					OR ({$wpdb->posts}.post_content LIKE %s)
					OR ({$wpdb->posts}.post_excerpt LIKE %s)",
				$like,
				$like,
				$like
			);

			// If TranslatePress is active, also search in the translation dictionary
		if ( class_exists( '\TRP_Translate_Press' ) ) {
			// Get language codes
			$lang_codes = $this->get_trp_language_code();
			$default_lang = $lang_codes['default_language'];
			$current_lang = $lang_codes['current_language'];
			
			// Only search in translation table if current language is different from default
			if ( $default_lang !== $current_lang ) {
				$default_lang = preg_replace( '/[^a-z0-9_]/', '', $default_lang );
				$current_lang = preg_replace( '/[^a-z0-9_]/', '', $current_lang );
				// TranslatePress table naming: wp_trp_dictionary_{default_lang}_{current_lang}
				$trp_table = $wpdb->prefix . 'trp_dictionary_' . $default_lang . '_' . $current_lang;

				if ( $this->table_exists( $trp_table ) ) {
					// $trp_table is composed from $wpdb->prefix + sanitized lang slugs (preg_replace allowlist above);
					// $like is esc_like()-wrapped with intentional % wildcards; CONCAT() wildcards are query literals, not user input.
					// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$trp_search = $wpdb->prepare(
						" OR EXISTS (
							SELECT 1 FROM {$trp_table} trp
							WHERE (trp.original LIKE %s OR trp.translated LIKE %s)
							AND trp.status != 2
							AND (
								{$wpdb->posts}.post_title COLLATE utf8mb4_unicode_ci = trp.original COLLATE utf8mb4_unicode_ci
								OR {$wpdb->posts}.post_content COLLATE utf8mb4_unicode_ci LIKE CONCAT('%%', trp.original COLLATE utf8mb4_unicode_ci, '%%')
								OR {$wpdb->posts}.post_excerpt COLLATE utf8mb4_unicode_ci = trp.original COLLATE utf8mb4_unicode_ci
							)
						)",
						$like,
						$like
					);
					// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					$search .= $trp_search;
				}
			}
		}


			$search .= " ) ";
		}

		return $search;
	}

	/**
	 * Get TranslatePress language codes (default and current) for table name construction
	 * 
	 * @return array Array with 'default_language' and 'current_language' keys
	 */
	private function get_trp_language_code() {
		$result = [
			'default_language' => 'en_US',
			'current_language' => 'en_US'
		];
		
		if ( class_exists( '\TRP_Translate_Press' ) ) {
			$trp = \TRP_Translate_Press::get_trp_instance();
			if ( isset( $trp ) && method_exists( $trp, 'get_component' ) ) {
				$trp_settings = $trp->get_component( 'settings' );
				
				// Get default language from settings
				if ( $trp_settings ) {
					$settings = $trp_settings->get_settings();
					if ( isset( $settings['default-language'] ) ) {
						$result['default_language'] = strtolower( $settings['default-language'] );
					}
				}
				
				// Get current language from global variable
				global $TRP_LANGUAGE;
				if ( isset( $TRP_LANGUAGE ) && ! empty( $TRP_LANGUAGE ) ) {
					$result['current_language'] = strtolower( $TRP_LANGUAGE );
				}
			}
		}
		
		return $result;
	}

	/**
	 * Check if a database table exists
	 */
	private function table_exists( $table_name ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- schema check, caching would mask plugin-activation state.
		$result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		return $result === $table_name;
	}

	public function get_style_depends() {
		$handlers = [ 'betterdocs-search' ];
		return $handlers;
	}

	public function get_script_depends() {
		$handlers = [ 'betterdocs-search'];

		if ( is_tax() ) {
			$handlers[] = 'betterdocs-glossaries';
		}
		return $handlers;
	}

	public function get_search_results() {
		global $wpdb;
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- public live-search endpoint, no state change.
		$search_input = isset( $_POST['search_input'] ) ? sanitize_text_field( wp_unslash( $_POST['search_input'] ) ) : '';
		$search_cat   = isset( $_POST['search_cat'] ) ? wp_strip_all_tags( wp_unslash( $_POST['search_cat'] ) ) : '';
		$lang         = isset( $_POST['lang'] ) ? wp_strip_all_tags( wp_unslash( $_POST['lang'] ) ) : '';
		$kb_slug      = isset( $_POST['kb_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['kb_slug'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$tax_query = [];
		if ( $search_cat ) {
			$tax_query = [
				[
					'taxonomy'         => 'doc_category',
					'field'            => 'slug',
					'terms'            => $search_cat,
					'operator'         => 'AND',
					'include_children' => true
				]
			];
		}

		// Don't build KB tax_query here - let the MultipleKB filter handle it
		// We just pass kb_slug in the args

		$term = get_term_by( 'slug', $search_cat );

		$post_status = ['publish'];

		if( current_user_can( 'read_private_docs' ) ) {
			array_push($post_status,  'private');
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- search query supports user-selected category filter.
		$args = [
			'term_id'          => isset( $term->term_id ) ? $term->term_id : 0,
			'post_type'        => 'docs',
			'post_status'      => $post_status,
			'posts_per_page'   => -1,
			'suppress_filters' => false,  // Changed to false to allow posts_search filter
			's'                => $search_input,
			'orderby'          => 'relevance',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- category-scoped search is a core BetterDocs feature; the taxonomy filter is intrinsic to the query.
			'tax_query'        => $tax_query,
			'kb_slug'          => $kb_slug // Pass kb_slug for filter hooks
		];

		// Handle WPML multilingual search
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			// If search term contains non-ASCII characters (e.g., Chinese, Japanese, Bangla),
			// search across all languages to find translated posts
			if ( preg_match('/[^\x00-\x7F]/', $search_input) ) {
				// Non-ASCII search: bypass WPML language filtering but allow posts_search filter
				// This allows searching across all languages
				$args['suppress_filters'] = true; // phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts,WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- non-ASCII search must reach all WPML translations.
			} else {
				// ASCII-only search (English), use WPML filters to restrict to current language
				$args['suppress_filters'] = false;
				$args['lang'] = ICL_LANGUAGE_CODE;
			}
		}
		// Handle TranslatePress - always allow posts_search filter for non-ASCII
		elseif ( class_exists( '\TRP_Translate_Press' ) && preg_match('/[^\x00-\x7F]/', $search_input) ) {
			// For TranslatePress, we need posts_search filter to run
			$args['suppress_filters'] = false;
		}

		// Add filter to improve search for non-English characters
		add_filter( 'posts_search', [ $this, 'improve_search_for_non_english' ], 10, 2 );

		$search_results = $this->query->get_posts( $args );

		// Remove filter after query to avoid affecting other queries
		remove_filter( 'posts_search', [ $this, 'improve_search_for_non_english' ], 10 );

		$response = [];

		ob_start();
		betterdocs()->views->get(
			'shortcode-parts/search-results',
			[
				'search_results' => $search_results,
				'search_input'   => $search_input
			]
		);

		$_output = ob_get_clean();

		$_input_not_found = '';
		if ( ! $search_results->have_posts() ) {
			$_input_not_found = $search_input;
		}

		$response['post_lists'] = $_output;

		if ( $_output && strlen( $search_input ) >= 3 ) {
			betterdocs()->query->insert_search_keyword( $search_input, $_input_not_found );
		}

		wp_reset_postdata();

		wp_send_json_success( $response );
	}

	public function get_name() {
		return 'betterdocs_search_form';
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return apply_filters(
			'betterdocs_search_form_attr',
			[
				'placeholder'    => __( 'Search', 'betterdocs' ),
				'heading'        => '',
				'subheading'     => '',
				'heading_tag'    => 'h1',
				'subheading_tag' => 'p',
				'kb_based_search' => '' // KB slug to filter search results
			]
		);
	}

	public function render( $atts, $content = null ) {
		// Get kb_based_search from shortcode attribute (KB slug)
		$kb_based_search = isset( $atts['kb_based_search'] ) ? sanitize_text_field( $atts['kb_based_search'] ) : '';
		
		betterdocs()->assets->localize(
			'betterdocs-search',
			'betterdocsSearchConfigTwo',
			[
				'is_post_type_archive' => is_post_type_archive( 'docs' ),
				'kb_based_search' => $kb_based_search,
			]
		);

		$this->views( 'shortcodes/search' );
	}
}
