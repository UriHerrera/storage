<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Post;
use WP_Term;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class PostType extends Base {
	public $post_type = 'docs';
	public $position  = 5;
	public $category  = 'doc_category';
	public $tag       = 'doc_tag';
	// Owned by Pro (BetterDocsPro\Core\GlossaryTaxonomy) as of Pro 4.3.1. Free
	// only registers it as a backward-compat shim for older Pro — see register().
	public $glossaries = 'glossaries';

	public $docs_archive;
	public $docs_slug;
	public $cat_slug;
	/**
	 * Database
	 * @var Database
	 */
	private $database = null;

	/**
	 * Summary of Settings
	 * @var Settings
	 */
	private $settings = null;

	/**
	 * Rewrite class
	 * @var Rewrite
	 */
	private $rewrite = null;

	/**
	 * Initially Invoked Functions
	 * @since 2.5.0
	 *
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		$this->database = $container->get( Database::class );
		$this->settings = $container->get( Settings::class );
		$this->rewrite  = $container->get( Rewrite::class );

		$this->docs_archive = $this->docs_slug();
		$this->docs_slug    = $this->docs_slug();
		$this->cat_slug     = $this->category_slug();
	}

	public static function permalink_structure() {
		return apply_filters( 'betterdocs_doc_permalink_default', ( self::get_instance( betterdocs()->container ) )->docs_slug() );
	}

	public function init() {
		add_filter( 'post_type_link', [ $this, 'post_link' ], 1, 3 );
		// Sync the modified date to the publish date when a scheduled doc is published.
		// Registered here (not in admin_init) so it also runs during WP-Cron, where is_admin() is false.
		add_action( 'transition_post_status', [ $this, 'sync_modified_date_on_publish' ], 10, 3 );
		// One-time backfill for docs that were already published from a schedule before this fix.
		$this->maybe_backfill_modified_dates();
		add_filter( 'rest_docs_collection_params', [ $this, 'add_rest_orderby_params' ], 10, 1 );
		add_filter( 'rest_doc_category_collection_params', [ $this, 'add_rest_orderby_params_on_doc_category' ], 10, 1 );
		add_filter( 'rest_doc_category_query', [ $this, 'modify_doc_category_rest_query' ], 10, 2 );

		// NOTE: the `doc_category_order` REST field (read + write) is registered in
		// REST/CategoryField.php. It used to be registered here too, but the
		// CategoryField registration (loaded later) won and re-registered it with a
		// string-typed thumbnail schema — which broke order writes. Keeping a single
		// canonical registration there avoids that conflict.

		// Make the category icon attachment id writable over REST so the React
		// Doc Categories slide-over can save it (the classic $_POST save path never
		// fires on a REST term write). The read-only `thumbnail` field still exposes
		// the resolved URL for display.
		register_term_meta(
			'doc_category',
			'doc_category_image-id',
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				// The field only ever holds an attachment ID; coerce any REST
				// input to a non-negative integer so an arbitrary string can't
				// be persisted verbatim.
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_docs' );
				},
			]
		);

		// The Knowledge Base assignment. Written since 3.x from $_POST['doc_category_kb']
		// on the classic term screens and read in ~30 places (Free Request/Query, the
		// category blocks and widgets, Pro's MultipleKB/AccessControl/ContentRestrictions)
		// as a serialised array of KB **slugs** compared with LIKE or in_array() — but
		// never registered, so it was invisible to the REST API and unwritable by anything
		// that is not a form POST. Registering it here is what lets the doc-category REST
		// route (and the MCP `bd-create-term` / `bd-update-term` tools) file a category
		// into a knowledge base.
		//
		// `manage_knowledge_base_terms` rather than `edit_docs`: this is the same decision
		// as creating a knowledge base, and Pro gates the taxonomy itself on that cap.
		//
		// @since 4.9.0
		register_term_meta(
			'doc_category',
			'doc_category_knowledge_base',
			[
				'type'              => 'array',
				'description'       => __( 'Knowledge Bases this doc category belongs to, by slug.', 'betterdocs' ),
				'single'            => true,
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_category_knowledge_bases' ],
				'auth_callback'     => function () {
					return current_user_can( 'manage_knowledge_base_terms' );
				},
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
				],
			]
		);

		// Expose (and allow setting) a term's language for WPML/Polylang so the React
		// admin can show a language filter bar + a language selector. Applies to both
		// the doc_category and doc_tag taxonomies.
		foreach ( [ $this->category, $this->tag ] as $bd_lang_taxonomy ) {
			register_rest_field(
				$bd_lang_taxonomy,
				'lang',
				[
					'get_callback'    => function ( $item ) use ( $bd_lang_taxonomy ) {
						if ( ! Helper::is_multilingual_active() ) {
							return null;
						}
						$term = get_term( $item['id'], $bd_lang_taxonomy );
						return ( $term && ! is_wp_error( $term ) ) ? Helper::get_term_language( $term ) : '';
					},
					'update_callback' => function ( $value, $term ) {
						if ( ! current_user_can( 'edit_docs' ) || ! Helper::is_multilingual_active() ) {
							return;
						}
						Helper::set_term_language( $term, sanitize_text_field( (string) $value ) );
					},
					'schema'          => [
						'type'    => [ 'string', 'null' ],
						'context' => [ 'view', 'edit' ],
					],
				]
			);
		}

		// All-languages bypass for the doc_tag REST list (mirrors doc_category).
		add_filter( 'rest_doc_tag_query', [ $this, 'modify_doc_tag_rest_query' ], 10, 2 );

		add_action( 'before_delete_post', [ $this, 'delete_analytics_rows_on_post_delete' ], 10, 1 );
		add_filter('rest_prepare_doc_category', [$this, 'modify_term_response'], 10, 3); //modify rest api doc category term count when nested_subcategory is enabled
		if( $this->settings->get( 'enable_category_hierarchy_slugs' ) ) { // reigster hierarchy based slug rewrite rule, for doc category
			add_filter('betterdocs_category_rewrite', [$this, 'enable_nested_hierarchy_doc_category_slug'], 10, 1);
		}
	}

	public function modify_term_response( $response, $item, $request ){
		if( $request->get_param('nested_subcategory') != null && $request->get_param('nested_subcategory') ) {
			$response->data['count'] = betterdocs()->query->get_docs_count(
				$item,
				$request->get_param('nested_subcategory'),
				[
					'multiple_knowledge_base' => false,
					'kb_slug'                 => ''
				]
			);
		}
		return $response;

	}

	public function enable_nested_hierarchy_doc_category_slug($doc_category_rewrite_payload) {
		$doc_category_rewrite_payload['hierarchical'] = true;
		return $doc_category_rewrite_payload;
	}

	/**
	 * Add menu_order param to the list of rest api orderby values
	 */
	public function add_rest_orderby_params( $params ) {
		$params['orderby']['enum'][] = 'menu_order';
		return $params;
	}

	/**
	 * Add doc_category_order param to the list of rest api orderby values on doc_category taxonomy
	 */
	public function add_rest_orderby_params_on_doc_category( $params ) {
		$params['orderby']['enum'][] = 'doc_category_order';
		return $params;
	}

	/**
	 * Modify doc_category rest query for doc_category_order meta key
	 */
	public function modify_doc_category_rest_query( $args, $request ) {
		$order_by = $request->get_param( 'orderby' );
		if ( isset( $order_by ) && 'doc_category_order' === $order_by ) {
			// Get language-specific meta key for multilingual sites
			$meta_key = $this->get_category_order_meta_key();
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- ordering by user-assigned doc_category_order meta is core feature.
			$args['meta_key'] = $meta_key;
			$args['orderby']  = 'meta_value_num';
			$args['order']    = $request->get_param( 'order' ) ?: 'ASC';
		}

		return $this->maybe_all_languages_args( $args, $request );
	}

	/**
	 * Modify doc_tag REST query — only the all-languages bypass is needed (tags
	 * are name-ordered, no custom order meta).
	 */
	public function modify_doc_tag_rest_query( $args, $request ) {
		return $this->maybe_all_languages_args( $args, $request );
	}

	/**
	 * When the React admin requests bd_all_languages=1, return terms across every
	 * language (the React app filters/group by language client-side). Bypasses the
	 * per-language term filter both WPML and Polylang add.
	 */
	private function maybe_all_languages_args( $args, $request ) {
		if ( $request->get_param( 'bd_all_languages' ) && Helper::is_multilingual_active() ) {
			// Polylang: empty lang drops the language WHERE clause → all languages.
			if ( function_exists( 'pll_current_language' ) ) {
				$args['lang'] = '';
			}
			// WPML: this arg makes WPML's get_terms_args + terms_clauses filters skip
			// language scoping (WPML\TaxonomyTermTranslation\Hooks::shouldSkip), so the
			// query returns terms in every language. Request-scoped — no restore needed.
			$args['wpml_skip_filters'] = true;
		}

		return $args;
	}

	public function post_link( $url, $post, $leavename = false ) {
	if ( 'docs' != get_post_type( $post ) ) {
		return $url;
	}

	$cat_terms = wp_get_object_terms( $post->ID, 'doc_category' );
	
	// If Multiple KB is active, try to get the category that belongs to the current KB
	if ( taxonomy_exists( 'knowledge_base' ) && is_array( $cat_terms ) && ! empty( $cat_terms ) && count( $cat_terms ) > 1 ) {
		// Try to get KB slug from cookie first, then from query
		$kb_slug = '';
		if ( isset( $_COOKIE['last_knowledge_base'] ) ) {
			$kb_slug = sanitize_text_field( wp_unslash( $_COOKIE['last_knowledge_base'] ) );
		}
		
		global $wp_query;
		if ( empty( $kb_slug ) && isset( $wp_query->query['knowledge_base'] ) ) {
			$kb_slug = $wp_query->query['knowledge_base'];
		}
		
		// If we have a KB slug, find the category that belongs to this KB
		if ( ! empty( $kb_slug ) ) {
			foreach ( $cat_terms as $cat_term ) {
				$term_kbs = get_term_meta( $cat_term->term_id, 'doc_category_knowledge_base', true );
				if ( is_array( $term_kbs ) && in_array( $kb_slug, $term_kbs ) ) {
					// Found a category that belongs to this KB, use it
					$cat_terms = [ $cat_term ];
					break;
				}
			}
		}
	}

	if( is_array( $cat_terms ) && ! empty( $cat_terms ) ) {
		// Only use the first category to build the hierarchy
		$doccat_terms = self::build_category_path( $cat_terms[0] );
	} else {
		$doccat_terms = 'uncategorized';
	}

	$url = str_replace( '%doc_category%', $doccat_terms, $url );
	return apply_filters( 'betterdocs_post_type_link', $url, $post, $leavename );
}

	/**
	 * Build the URL path for a doc category term.
	 *
	 * When `enable_category_hierarchy_slugs` is on this returns the full
	 * parent/child chain (e.g. `handbook/articles`), otherwise just the term slug.
	 *
	 * @param \WP_Term|int $term The doc_category term (or term id).
	 * @return string The category path, without leading/trailing slashes.
	 */
	public static function build_category_path( $term ) {
		if ( ! $term instanceof WP_Term ) {
			$term = get_term( $term, 'doc_category' );
		}

		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		if ( ! betterdocs()->settings->get( 'enable_category_hierarchy_slugs' ) ) {
			return $term->slug;
		}

		$path         = [ $term->slug ];
		$process_term = $term;
		// Guard against a corrupted parent chain looping forever.
		$seen = [ (int) $term->term_id => true ];

		while ( $process_term->parent != 0 && ! isset( $seen[ (int) $process_term->parent ] ) ) {
			$parent_term = get_term( $process_term->parent, 'doc_category' );

			if ( ! $parent_term instanceof WP_Term ) {
				break;
			}

			$seen[ (int) $parent_term->term_id ] = true;
			array_unshift( $path, $parent_term->slug );
			$process_term = $parent_term;
		}

		return implode( '/', $path );
	}
	public function ajax() {
		/**
		 * All kind of ajax related to post type: docs
		 * for admin side.
		 */
		add_action( 'wp_ajax_update_doc_cat_order', [ $this, 'update_category_order' ] );
		add_action( 'wp_ajax_update_doc_order_by_category', [ $this, 'update_docs_order_by_category' ] );
		add_action( 'wp_ajax_update_docs_term', [ $this, 'update_docs_term' ] );
	}

	public function admin_init() {
		$this->ajax();

		add_action( 'new_to_auto-draft', [ $this, 'auto_add_category' ] );
		add_action( 'save_post_docs', [ $this, 'save_docs' ] );
		add_action( 'rest_after_insert_docs', [ $this, 'save_docs' ] );

		// Doc Category Taxonomy EXTRA Fields
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'transition_post_status', [ $this, 'clear_docs_object_cache' ], 10, 3 );
		add_action( 'doc_category_add_form_fields', [ $this, 'add_form_fields' ], 10, 2 );
		add_action( 'doc_category_edit_form_fields', [ $this, 'edit_form_fields' ], 10, 2 );
		add_action( 'created_doc_category', [ $this, 'save_category_meta' ], 11, 2 );
		add_action( 'edited_doc_category', [ $this, 'updated_category_meta' ], 11, 2 );

		// Order the terms on the admin side.
		add_action( 'admin_head', [ $this, 'order_terms' ] );
		add_action( 'admin_notices', [ $this, 'multilingual_migration_notice' ] );
	}

	/**
	 * Show admin notice for multilingual migration if needed
	 */
	public function multilingual_migration_notice() {
		// Only show on doc_category taxonomy page
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'edit-doc_category' ) {
			return;
		}

		// Only show if multilingual plugin is active
		if ( ! Helper::is_multilingual_active() ) {
			return;
		}

		// Handle migration request
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( isset( $_GET['run_betterdocs_migration'] ) && wp_verify_nonce( $nonce, 'betterdocs_migration' ) ) {
			$this->run_category_migration();
			return;
		}

		// Check if migration is needed for both category and document orders
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time admin migration check; cache would mask migration completion.
		$has_base_cat_orders = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
			WHERE tm.meta_key = 'doc_category_order' AND tt.taxonomy = 'doc_category'"
		);

		$has_base_docs_orders = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
			WHERE tm.meta_key = '_docs_order' AND tt.taxonomy = 'doc_category' AND tm.meta_value != ''"
		);

		$languages = Helper::get_available_languages();
		$has_lang_cat_orders = 0;
		$has_lang_docs_orders = 0;

		if ( ! empty( $languages ) ) {
			$first_lang = $languages[0];
			$lang_cat_meta_key = 'doc_category_order_' . $first_lang;
			$lang_docs_meta_key = '_docs_order_' . $first_lang;

			$has_lang_cat_orders = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
				INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
				WHERE tm.meta_key = %s AND tt.taxonomy = 'doc_category'",
				$lang_cat_meta_key
			) );

			$has_lang_docs_orders = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
				INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
				WHERE tm.meta_key = %s AND tt.taxonomy = 'doc_category' AND tm.meta_value != ''",
				$lang_docs_meta_key
			) );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Show notice if we have base orders but no language-specific orders
		$needs_cat_migration = $has_base_cat_orders > 0 && $has_lang_cat_orders == 0;
		$needs_docs_migration = $has_base_docs_orders > 0 && $has_lang_docs_orders == 0;

		if ( $needs_cat_migration || $needs_docs_migration ) {
			$migration_url = add_query_arg(
				[ 'run_betterdocs_migration' => '1', 'nonce' => wp_create_nonce( 'betterdocs_migration' ) ],
				admin_url( 'admin.php?page=betterdocs-doc-categories' )
			);

			echo '<div class="notice notice-warning is-dismissible">';
			echo '<p><strong>' . esc_html__( 'BetterDocs Multilingual Migration Required', 'betterdocs' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'Your site uses a multilingual plugin and has existing ordering data that needs to be migrated:', 'betterdocs' ) . '</p>';
			echo '<ul style="margin-left: 20px;">';
			if ( $needs_cat_migration ) {
				echo '<li>' . esc_html( sprintf( /* translators: %d: number of categories */ __( '• Category ordering (%d categories)', 'betterdocs' ), (int) $has_base_cat_orders ) ) . '</li>';
			}
			if ( $needs_docs_migration ) {
				echo '<li>' . esc_html( sprintf( /* translators: %d: number of categories */ __( '• Document ordering (%d categories with custom doc orders)', 'betterdocs' ), (int) $has_base_docs_orders ) ) . '</li>';
			}
			echo '</ul>';
			echo '<p><a href="' . esc_url( $migration_url ) . '" class="button button-primary">' . esc_html__( 'Run Migration Now', 'betterdocs' ) . '</a></p>';
			echo '</div>';
		}
	}

	/**
	 * Run comprehensive migration for both category and document orders
	 */
	public function run_category_migration() {
		$result = Helper::migrate_all_orders_to_multilingual();

		if ( $result ) {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>Migration Completed Successfully!</strong></p>';
			echo '<p>Both category orders and document orders have been migrated to work with your multilingual setup.</p>';
			echo '</div>';
		} else {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p><strong>Migration Failed</strong></p>';
			echo '<p>There was an error migrating the orders. Please try again or contact support.</p>';
			echo '</div>';
		}

		// Clear cache
		wp_cache_flush();
	}

	public function scripts( $hook ) {
		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && $current_screen->id !== 'edit-doc_category' ) {
			return;
		}

		wp_enqueue_media();

		betterdocs()->assets->enqueue( 'betterdocs-category-edit', 'admin/js/category-edit.js' );

		betterdocs()->assets->localize(
			'betterdocs-category-edit',
			'betterdocsCategorySorting',
			[
				'action'      => 'update_doc_cat_order',
				'selector'    => '.taxonomy-doc_category',
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'doc_cat_order_nonce' ),
				'paged'       => isset( $_GET['paged'] ) &&
								isset( $_GET['nonce'] ) &&
								wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'doc_cat_order_nonce' )
								? absint( wp_unslash( $_GET['paged'] ) ) : 0,
				'per_page_id' => "edit_{$current_screen->taxonomy}_per_page"
			]
		);
	}

	/**
	 * Function to clear object cache when a new 'docs' post is published.
	 */
	public function clear_docs_object_cache( $new_status, $old_status, $post ) {
		if ( $post->post_type === 'docs' && $new_status === 'publish' ) {
			wp_cache_flush();
		}
	}

	/**
	 * Sync the modified date to the publish date when a scheduled 'docs' post goes live.
	 *
	 * When a doc is scheduled, WordPress stores `post_modified` as the time it was last
	 * edited (i.e. when it was scheduled), while `post_date` holds the future publish time.
	 * On the scheduled run, wp_publish_post() only flips the status to 'publish' and never
	 * touches `post_modified`. As a result the single doc "Updated on" line (which uses
	 * get_the_modified_date()) shows the scheduling date instead of the actual published
	 * date. Here we align `post_modified`/`post_modified_gmt` with the publish date so the
	 * "Updated on" metadata matches the WordPress published date.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function sync_modified_date_on_publish( $new_status, $old_status, $post ) {
		if ( $post->post_type !== 'docs' ) {
			return;
		}

		if ( $old_status !== 'future' || $new_status !== 'publish' ) {
			return;
		}

		global $wpdb;

		// Update directly to avoid re-triggering save/transition hooks (and an infinite loop).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- deliberate raw write to skip save_post/transition hooks; clean_post_cache() below refreshes caches.
		$wpdb->update(
			$wpdb->posts,
			[
				'post_modified'     => $post->post_date,
				'post_modified_gmt' => $post->post_date_gmt,
			],
			[ 'ID' => $post->ID ]
		);

		clean_post_cache( $post->ID );
	}

	/**
	 * One-time backfill of the modified date for docs that were published from a
	 * schedule *before* the sync_modified_date_on_publish() fix existed.
	 *
	 * For a scheduled post, `post_modified` is the time it was last edited (the
	 * scheduling time), which is earlier than `post_date` (the future publish time).
	 * A normally-published doc never has post_modified earlier than post_date, so
	 * `post_modified_gmt < post_date_gmt` cleanly identifies the affected docs.
	 * Runs once, guarded by an option flag.
	 */
	public function maybe_backfill_modified_dates() {
		if ( get_option( 'betterdocs_scheduled_modified_backfill_done' ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time bulk backfill of post_modified for scheduled docs; runs once behind an option flag, no per-row caching applies.
		$wpdb->query(
			"UPDATE {$wpdb->posts}
			 SET post_modified = post_date, post_modified_gmt = post_date_gmt
			 WHERE post_type = 'docs'
			   AND post_status = 'publish'
			   AND post_modified_gmt < post_date_gmt"
		);

		update_option( 'betterdocs_scheduled_modified_backfill_done', 1, false );

		wp_cache_flush();
	}

	public function add_form_fields( $taxonomy ) {
		betterdocs()->views->get( 'admin/taxonomy/add' );
	}

	public function edit_form_fields( $term, $taxonomy ) {
		$term_meta   = get_option( "doc_category_$term->term_id" );
		// Get language-specific meta key with fallback for multilingual sites
		$meta_key    = $this->get_category_order_meta_key( null, $term->term_id );
		$cat_order   = get_term_meta( $term->term_id, $meta_key, true );
		$cat_icon_id = get_term_meta( $term->term_id, 'doc_category_image-id', true );

		betterdocs()->views->get(
			'admin/taxonomy/edit',
			[
				'term'    => $term,
				'meta'    => $term_meta,
				'order'   => $cat_order,
				'icon_id' => $cat_icon_id
			]
		);
	}

	/**
	 * Normalise a doc category's Knowledge Base assignment to a list of slugs.
	 *
	 * Runs on **every** write to `doc_category_knowledge_base` — the REST route,
	 * the classic term screens, the importers — because that is what a registered
	 * `sanitize_callback` does.
	 *
	 * Slugs, never ids. Every reader compares this array against a KB term's
	 * `slug`, either with `in_array()` or with a `meta_query` `LIKE` over the
	 * serialised string, so a numeric id in the list would match nothing it was
	 * meant to and would widen the `LIKE` into unrelated rows (`7` matches
	 * `i:7;`, `"7"`, any slug containing a 7). Numeric entries are therefore
	 * **dropped**, not slugified — the one cost being a knowledge base whose slug
	 * is literally a number, which cannot be told apart from an id here.
	 *
	 * `sanitize_title()` is the right normaliser because it is what WordPress
	 * used to build the slug in the first place: it is the identity on an
	 * existing slug, turns a name ("Install Guide") into one, and percent-encodes
	 * a non-ASCII slug back to the stored form. That last case also repairs the
	 * classic write path, which `urldecode()`s before storing (L594) and so wrote
	 * a decoded string the readers' primary comparison could not match —
	 * `Request::get_term_by_slug_or_encoded()` exists to work around exactly that.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $value Raw value: an array of slugs, or a single slug.
	 * @return string[] Unique, non-empty slugs, re-indexed.
	 */
	public function sanitize_category_knowledge_bases( $value ) {
		if ( ! is_array( $value ) ) {
			$value = ( null === $value || '' === $value ) ? [] : [ $value ];
		}

		$slugs = [];

		foreach ( $value as $item ) {
			if ( is_array( $item ) || is_object( $item ) || is_bool( $item ) ) {
				continue;
			}

			$item = trim( (string) $item );

			// An id, not a slug. See the docblock.
			if ( '' === $item || is_numeric( $item ) ) {
				continue;
			}

			$slug = sanitize_title( $item );

			if ( '' === $slug ) {
				continue;
			}

			$slugs[] = $slug;
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Save custom meta data for the category when a term is added.
	 * Meta data is saved from $_POST['term_meta'] array.
	 * If 'doc_category_kb' is set in $_POST, it updates 'doc_category_knowledge_base' meta data.
	 * It also sets the term order using set_term_order function.
	 * @param int $term_id The ID of the term being saved.
	 * @param int $tt_id The term taxonomy ID.
	 */
	public function save_category_meta( $term_id, $tt_id ) {
		// Ensure the current user has capabilities to edit terms
		if ( ! current_user_can( 'manage_doc_terms' ) ) {
			return;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['term_meta'] ) && is_array( $_POST['term_meta'] ) ) {
			// Sanitize the input array
			$term_meta = array_map( 'sanitize_text_field', wp_unslash( $_POST['term_meta'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$cat_keys = array_keys( $term_meta );
			foreach ( $cat_keys as $key ) {
				if ( isset( $term_meta[ $key ] ) ) {
					add_term_meta( $term_id, "doc_category_$key", $term_meta[ $key ] );
				}
			}
		}

		// @todo PRO
		if ( isset( $_POST['doc_category_kb'] ) && is_array( $_POST['doc_category_kb'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Some multilingual plugins send URL-encoded slugs. We must decode them before sanitizing.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$doc_category_kb = array_map( 'urldecode', wp_unslash( $_POST['doc_category_kb'] ) );
			$doc_category_kb = array_map( 'sanitize_text_field', $doc_category_kb );

			// Update the term meta with the sanitized array
			update_term_meta( $term_id, 'doc_category_knowledge_base', $doc_category_kb );
		}

		// Default the taxonomy's terms' order if it's not set.
		$this->set_term_order( $term_id, $tt_id );
	}

	/**
	 * Set the term order when a new term is created.
	 * If the term has a parent, updates the doc_category_order based on the parent's order.
	 * Otherwise, assigns the maximum doc_category_order among all terms plus one.
	 */
	public function set_term_order( $term_id, $tt_id ) {
		// Verify term exists and is of the correct taxonomy
		$term = get_term( $term_id, 'doc_category' );

		// Bail if term is invalid
		if ( is_wp_error( $term ) || ! $term ) {
			return;
		}

		if ( $term->parent !== 0 ) {
			$this->update_doc_category_order_by_parent( $term_id, $term->parent );
		} else {
			$max_order = $this->get_max_taxonomy_order( 'doc_category' );
			// Get language-specific meta key for multilingual sites
			$meta_key = $this->get_category_order_meta_key();
			update_term_meta( $term_id, $meta_key, $max_order );
		}
	}

	/**
	 * Update category meta data when a term is updated.
	 * Updates custom term meta data such as 'doc_category_kb'.
	 * If the parent term is changed, updates the doc_category_order based on the new parent's order.
	 * @param int $term_id The ID of the term being updated.
	 */
	public function updated_category_meta( $term_id ) {
		$term              = get_term( $term_id, 'doc_category' );
		$current_parent_id = $term->parent;
		// Update custom meta data from $_POST['term_meta'] array
		if ( isset( $_POST['term_meta'] ) && is_array( $_POST['term_meta'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$term_meta = array_map( 'sanitize_text_field', wp_unslash( $_POST['term_meta'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$cat_keys  = array_keys( $term_meta );
			foreach ( $cat_keys as $key ) {
				if ( isset( $term_meta[ $key ] ) ) {
					update_term_meta( $term_id, "doc_category_$key", $term_meta[ $key ] );
				}
			}
		}

		// Update 'doc_category_knowledge_base' meta data if 'doc_category_kb' is set in $_POST
		if ( isset( $_POST['doc_category_kb'] ) && is_array( $_POST['doc_category_kb'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Some multilingual plugins send URL-encoded slugs. We must decode them before sanitizing.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$doc_category_kb = array_map( 'urldecode', wp_unslash( $_POST['doc_category_kb'] ) );
			$doc_category_kb = array_map( 'sanitize_text_field', $doc_category_kb );
			update_term_meta( $term_id, 'doc_category_knowledge_base', $doc_category_kb );
		}
	}

	/**
	 * Get language-specific meta key for category ordering with fallback
	 *
	 * @param string|null $language Language code, if null will auto-detect
	 * @param int|null $term_id Term ID for specific term checks
	 * @return string Language-specific meta key or base key as fallback
	 */
	public function get_category_order_meta_key( $language = null, $term_id = null ) {
		return Helper::get_meta_key_with_fallback( 'doc_category_order', $term_id, $language );
	}

	/**
	 * Get language-specific meta key for docs ordering with fallback
	 *
	 * @param string|null $language Language code, if null will auto-detect
	 * @param int|null $term_id Term ID for specific term checks
	 * @return string Language-specific meta key or base key as fallback
	 */
	public function get_docs_order_meta_key( $language = null, $term_id = null ) {
		return Helper::get_meta_key_with_fallback( '_docs_order', $term_id, $language );
	}

	/**
	 * Update the doc_category_order for a term based on its parent's order.
	 */
	public function update_doc_category_order_by_parent( $term_id, $term_parent_id ) {
		// Verify user capabilities
		if ( ! current_user_can( 'manage_doc_terms' ) ) {
			return;
		}
		
		// Get language-specific meta key with fallback
		$meta_key = $this->get_category_order_meta_key( null, $term_parent_id );

		// Get the parent's order or default to 1
		$parent_order = (int) get_term_meta( $term_parent_id, $meta_key, true );

		if ( $parent_order === 0 ) {
			$parent_order = $this->get_max_taxonomy_order( 'doc_category', $term_parent_id );
		}

		$order = $parent_order + 1;

		// Use the same meta key pattern for the child term
		$child_meta_key = $this->get_category_order_meta_key( null, $term_id );
		update_term_meta( $term_id, $child_meta_key, (int) $order );
	}

	/**
	 * Get the maximum doc_category_order for this taxonomy.
	 * If $parent_term_id is provided, it retrieves the maximum order under that parent.
	 * Otherwise, it retrieves the maximum order among all terms.
	 * @param string $tax_slug The taxonomy slug.
	 * @param int|null $parent_term_id The parent term ID (optional).
	 * @return int The maximum doc_category_order.
	 */
	private function get_max_taxonomy_order( $tax_slug, $parent_term_id = null ) {
		global $wpdb;

		// Prepare the table names safely
		$terms_table         = $wpdb->terms;
		$term_taxonomy_table = $wpdb->term_taxonomy;
		$termmeta_table      = $wpdb->termmeta;

		if ( $parent_term_id !== null ) {
			// Query with parent_term_id
			$query          = "
                SELECT MAX(CAST(tm.meta_value AS UNSIGNED))
                FROM {$terms_table} AS t
                INNER JOIN {$term_taxonomy_table} AS tt ON t.term_id = tt.term_id
                INNER JOIN {$termmeta_table} AS tm ON tm.term_id = t.term_id
                WHERE tt.taxonomy = %s
                AND tm.meta_key = 'doc_category_order'
                AND tt.parent = %d
            ";
            $max_term_order = $wpdb->get_var( $wpdb->prepare( $query, $tax_slug, $parent_term_id ) ); // phpcs:ignore
		} else {
			// Query without parent_term_id
			$query          = "
                SELECT MAX(CAST(tm.meta_value AS UNSIGNED))
                FROM {$terms_table} AS t
                INNER JOIN {$term_taxonomy_table} AS tt ON t.term_id = tt.term_id
                INNER JOIN {$termmeta_table} AS tm ON tm.term_id = t.term_id
                WHERE tt.taxonomy = %s
                AND tm.meta_key = 'doc_category_order'
            ";
            $max_term_order = $wpdb->get_var( $wpdb->prepare( $query, $tax_slug ) ); // phpcs:ignore
		}

		// Return the result as an integer, defaulting to 1 if no results found
		return (int) $max_term_order === 0 || empty( $max_term_order ) ? 1 : (int) $max_term_order + 1;
	}

	/**
	 * Summary of order_terms
	 * @return void
	 */
	public function order_terms() {
		global $current_screen;
		$screen_id = isset( $current_screen->id ) ? $current_screen->id : '';

		if ( in_array( $screen_id, [ 'betterdocs_page_betterdocs-admin', 'betterdocs_page_betterdocs-settings', 'admin_page_betterdocs-admin' ] ) ) {
			$this->default_term_order( 'doc_category' );
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
        if ( ! isset( $_GET['orderby'] ) && ! empty( $current_screen->base ) && $current_screen->base === 'edit-tags' && $current_screen->taxonomy === 'doc_category' ) {
			$this->default_term_order( $current_screen->taxonomy );
			add_filter( 'terms_clauses', [ $this, 'set_tax_order' ], 10, 3 );
		}
	}

	/**
	 * Default the taxonomy's terms' order if it's not set.
	 *
	 * @param string $tax_slug The taxonomy's slug.
	 */
	private function default_term_order( $tax_slug ) {
		$terms = get_terms(
			[
				'taxonomy'   => $tax_slug,
				'hide_empty' => false,
			]
		);

		$order = $this->get_max_taxonomy_order( $tax_slug );

		if ( ! is_array( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			if ( ! get_term_meta( $term->term_id, 'doc_category_order', true ) ) {
				update_term_meta( $term->term_id, 'doc_category_order', $order );
				++$order;
			}
		}
	}

	/**
	 * Re-Order the taxonomies based on the doc_category_order value.
	 * Uses fallback logic to check language-specific meta first, then base meta
	 *
	 * @param array $pieces     Array of SQL query clauses.
	 * @param array $taxonomies Array of taxonomy names.
	 * @param array $args       Array of term query args.
	 */
	public function set_tax_order( $pieces, $taxonomies, $args ) {
		global $wpdb;

		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy === 'doc_category' ) {
				// Check if we should use language-specific ordering
				$current_language = Helper::get_current_admin_language();
				$base_meta_key = 'doc_category_order';

				if ( Helper::is_multilingual_active() && $current_language ) {
					$lang_meta_key = $base_meta_key . '_' . $current_language;

					// $current_language is attacker-controlled (?lang=) and
					// sanitize_text_field() does not strip quotes, so the meta keys
					// must be bound, never interpolated (CVE-2026-15104).
					// Use COALESCE to fall back from language-specific to base meta key
					$join_statement = $wpdb->prepare(
						" LEFT JOIN $wpdb->termmeta AS term_meta_lang ON t.term_id = term_meta_lang.term_id AND term_meta_lang.meta_key = %s",
						$lang_meta_key
					);
					$join_statement .= $wpdb->prepare(
						" LEFT JOIN $wpdb->termmeta AS term_meta_base ON t.term_id = term_meta_base.term_id AND term_meta_base.meta_key = %s",
						$base_meta_key
					);

					if ( ! $this->does_substring_exist( $pieces['join'], 'term_meta_lang' ) ) {
						$pieces['join'] .= $join_statement;
					}

					// Order by language-specific meta if available, otherwise use base meta
					$pieces['orderby'] = 'ORDER BY CAST( COALESCE( NULLIF(term_meta_lang.meta_value, ""), term_meta_base.meta_value ) AS UNSIGNED )';
				} else {
					// Non-multilingual or no language detected - use base meta key
					$join_statement = " LEFT JOIN $wpdb->termmeta AS term_meta ON t.term_id = term_meta.term_id AND term_meta.meta_key = '$base_meta_key'";

					if ( ! $this->does_substring_exist( $pieces['join'], $join_statement ) ) {
						$pieces['join'] .= $join_statement;
					}

					$pieces['orderby'] = 'ORDER BY CAST( term_meta.meta_value AS UNSIGNED )';
				}
			}
		}

		return $pieces;
	}

	/**
	 * Check if a substring exists inside a string.
	 *
	 * @param string $string    The main string (haystack) we're searching in.
	 * @param string $substring The substring we're searching for.
	 *
	 * @return bool True if substring exists, else false.
	 */
	protected function does_substring_exist( $string, $substring ) {
		return strstr( $string, $substring ) !== false;
	}

	/**
	 * Auto Add in Category, Adding from Sorting
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	public function auto_add_category( $post ) {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			// Unslash and sanitize the REQUEST_URI
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			if ( strpos( $request_uri, 'wp-admin/post-new.php' ) === false ) {
				return;
			}
		} else {
			return;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read from new-post URL.
        if ( empty( $_GET['cat'] ) ) {
			return;
		}

		// Sanitize and unslash the 'cat' parameter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read from new-post URL.
        $cat = sanitize_text_field( wp_unslash( $_GET['cat'] ) );
		if ( false === ( $cat = get_term_by( 'term_id', $cat, 'doc_category' ) ) ) {
			return;
		}

		wp_set_post_terms( $post->ID, [ $cat->term_id ], 'doc_category', false );
	}

	public function update_category_order() {
		if ( ! check_ajax_referer( 'doc_cat_order_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Nonce Failed', 'betterdocs' ) );
		}

		if ( ! current_user_can( 'manage_doc_terms' ) ) {
			wp_send_json_error( __( 'You don\'t have permission to manage docs term.', 'betterdocs' ) );
		}

		wp_cache_flush();

		if ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) {
			// Sanitize and validate each element in the array
			$taxonomy_ordering_data = array_map(
				function ( $item ) {
					if ( is_array( $item ) && isset( $item['term_id'], $item['order'] ) ) {
							return [
								'term_id' => intval( $item['term_id'] ),
								'order'   => intval( $item['order'] ),
							];
					}
					return null; // Discard invalid items
        }, wp_unslash( $_POST['data'] ) ); // phpcs:ignore

			// Remove any null entries
			$taxonomy_ordering_data = array_filter( $taxonomy_ordering_data );
		} else {
			$taxonomy_ordering_data = []; // Default to an empty array if not set
		}

		if ( isset( $_POST['base_index'] ) ) {
            $base_index = intval( $_POST['base_index'] ); //phpcs:ignore
		} else {
			$base_index = 0; // Default to 0 if not set
		}

		// Get language-specific meta key for multilingual sites
		$meta_key = $this->get_category_order_meta_key();
		foreach ( $taxonomy_ordering_data as $order_data ) {
			// Ensure $order_data is an array with required keys
			if ( is_array( $order_data ) && isset( $order_data['term_id'], $order_data['order'] ) ) {
				if ( $base_index > 0 ) {
					$current_position = get_term_meta( $order_data['term_id'], $meta_key, true );

					if ( (int) $current_position < (int) $base_index ) {
						continue;
					}
				}

				// Update term meta with sanitized and validated values using language-specific key
				update_term_meta( $order_data['term_id'], $meta_key, ( (int) $order_data['order'] + (int) $base_index ) );
			}
		}

		wp_send_json_success( __( 'Successfully updated.', 'betterdocs' ) );
	}

	/**
	 * AJAX Handler to update docs position.
	 */
	public function update_docs_order_by_category() {
		if ( ! check_ajax_referer( 'doc_cat_order_nonce', 'doc_cat_order_nonce', false ) ) {
			wp_send_json_error( __( 'Nonce Failed', 'betterdocs' ) );
		}

		if ( ! current_user_can( 'edit_docs' ) ) {
			wp_send_json_error( __( 'You don\'t have permission to update docs term.', 'betterdocs' ) );
		}

		// Log WPML context for debugging
		if ( Helper::is_multilingual_active() ) {
			$current_lang = Helper::get_current_admin_language();
			usleep( 100000 ); // 100ms delay to prevent race conditions
		}

		if ( isset( $_POST['docs_ordering_data'] ) && is_array( $_POST['docs_ordering_data'] ) ) {
			// Unslash and sanitize each element in the array
			$docs_ordering_data = implode( ',', array_map( 'intval', array_map( 'sanitize_text_field', wp_unslash( $_POST['docs_ordering_data'] ) ) ) );
		} else {
			$docs_ordering_data = ''; // Default to an empty string if not set
		}

		$term_id = isset( $_POST['list_term_id'] ) ? intval( wp_unslash( $_POST['list_term_id'] ) ) : 0;

		if ( ! $term_id ) {
			wp_send_json_error( __( 'Invalid term ID.', 'betterdocs' ) );
		}

		// Verify term exists and user has permission to edit it
		$term = get_term( $term_id, 'doc_category' );
		if ( is_wp_error( $term ) || ! $term ) {
			wp_send_json_error( __( 'Invalid category.', 'betterdocs' ) );
		}

		// Always write the language-specific key when a language is detected.
		// Falling back to the base key on first save lets WPML's "copy from
		// original" term-meta behavior overwrite our save with the primary
		// language's order on the next read, which is what made the order
		// appear to revert after reload on secondary-language admin UIs.
		$meta_key = Helper::get_meta_key_for_save( '_docs_order' );
		$existing = (string) get_term_meta( $term_id, $meta_key, true );

		if ( $existing === (string) $docs_ordering_data ) {
			// Already at this order — nothing to update. update_term_meta()
			// would return false in this case, which the old code mis-reported
			// as a failure ("Something went wrong.").
			wp_send_json_success( __( 'Successfully updated.', 'betterdocs' ) );
		}

		$result = update_term_meta( $term_id, $meta_key, $docs_ordering_data );

		if ( $result !== false ) {
			// Only flush cache after successful update
			wp_cache_flush();
			wp_send_json_success( __( 'Successfully updated.', 'betterdocs' ) );
		}

		wp_send_json_error( __( 'Something went wrong.', 'betterdocs' ) );
	}

	/**
	 * AJAX Handler to update docs term assignment.
	 */
	public function update_docs_term() {
		if ( ! check_ajax_referer( 'doc_cat_order_nonce', 'doc_cat_order_nonce', false ) ) {
			wp_send_json_error( __( 'Nonce Failed', 'betterdocs' ) );
		}

		if ( ! current_user_can( 'edit_docs' ) ) {
			wp_send_json_error( __( 'You don\'t have permission to update docs term.', 'betterdocs' ) );
		}

		// Log WPML context for debugging
		if ( Helper::is_multilingual_active() ) {
			$current_lang = Helper::get_current_admin_language();
		}

		$object_id    = isset( $_POST['object_id'] ) ? intval( wp_unslash( $_POST['object_id'] ) ) : 0;
		$term_id      = isset( $_POST['list_term_id'] ) ? intval( wp_unslash( $_POST['list_term_id'] ) ) : 0;
		$prev_term_id = isset( $_POST['prev_term_id'] ) ? intval( wp_unslash( $_POST['prev_term_id'] ) ) : 0;

		if ( ! $term_id || ! $object_id ) {
			wp_send_json_error( __( 'Invalid object or term ID.', 'betterdocs' ) );
		}

		// Verify the post exists and is a docs post
		$post = get_post( $object_id );
		if ( ! $post || $post->post_type !== 'docs' ) {
			wp_send_json_error( __( 'Invalid document.', 'betterdocs' ) );
		}

		// Verify the term exists
		$term = get_term( $term_id, 'doc_category' );
		if ( is_wp_error( $term ) || ! $term ) {
			wp_send_json_error( __( 'Invalid category.', 'betterdocs' ) );
		}

		// Remove from previous category if specified
		if ( $prev_term_id ) {
			$prev_term = get_term( $prev_term_id, 'doc_category' );
			if ( ! is_wp_error( $prev_term ) && $prev_term ) {
				wp_remove_object_terms( $object_id, $prev_term_id, 'doc_category' );
			}
		}

		// Get existing terms to preserve other category assignments
		$extra_terms_of_doc = wp_get_post_terms( $object_id, 'doc_category', [ 'fields' => 'ids' ] );

		// Prepare term IDs array
		$term_ids = [ $term_id ];
		if ( count( $extra_terms_of_doc ) > 0 ) {
			$term_ids = array_merge( $term_ids, $extra_terms_of_doc );
			$term_ids = array_unique( $term_ids ); // Remove duplicates
		}

		// Set the post terms
		$terms_added = wp_set_post_terms( $object_id, $term_ids, 'doc_category' );

		if ( ! is_wp_error( $terms_added ) ) {
			// Clear relevant caches
			clean_post_cache( $object_id );
			wp_send_json_success( __( 'Successfully updated.', 'betterdocs' ) );
		}

		wp_send_json_error( __( 'Something went wrong.', 'betterdocs' ) );
	}

	/**
	 * Update docs_term meta when new post created
	 */
	public function save_docs( $post_id ) {
		// bail out if this is an autosave
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}

		$term_list = wp_get_post_terms( $post_id, 'doc_category', [ 'fields' => 'ids' ] );

		//save estimated reading text in post
		$est_reading_text = isset( $_POST['estimated_reading_text'] ) ? sanitize_text_field( wp_unslash( $_POST['estimated_reading_text'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- runs on save_post, WP verifies post-edit nonce upstream.

		update_post_meta( $post_id, '_betterdocs_est_reading_text', $est_reading_text );

		if ( ! empty( $term_list ) ) {
			foreach ( $term_list as $term_id ) {
				$term_meta = get_term_meta( $term_id, '_docs_order', true );
				if ( ! empty( $term_meta ) ) {
					$_term_meta_array = explode( ',', $term_meta );

					if ( ! in_array( $post_id, $_term_meta_array ) ) {
						array_unshift( $_term_meta_array, $post_id );
						$_docs_order_data = filter_var_array( wp_unslash( $_term_meta_array ), FILTER_SANITIZE_NUMBER_INT );
						update_term_meta( $term_id, '_docs_order', implode( ',', $_docs_order_data ) );
					}
				} else {
					update_term_meta( $term_id, '_docs_order', implode( ',', [ $post_id ] ) );
				}
			}
		}
	}

	public function register() {
		/**
		 * Flush Rewrite Rules
		 */
		if ( $this->database->get_transient( 'betterdocs_flush_rewrite_rules' ) ) {
			betterdocs()->rewrite->rules();

			flush_rewrite_rules();
			$this->database->delete_transient( 'betterdocs_flush_rewrite_rules' );
		}

		$this->register_post_type();
		$this->register_category_taxonomy();

		// The `glossaries` taxonomy is normally registered by Pro
		// (BetterDocsPro\Core\GlossaryTaxonomy) as of Pro 4.3.1 — Glossaries is a
		// Pro feature. But an OLDER Pro (< 4.3.1) still expects Free to own it (it
		// was a Free feature before the move) and does not register it itself. In
		// that transition window nobody would register `glossaries`, so its terms
		// become unqueryable and encyclopedia/glossary permalinks fall back to the
		// archive page. Register it here as a backward-compat shim ONLY while an
		// outdated Pro is active; Pro 4.3.1+ (has_glossaries) owns it, so this is
		// skipped then and never double-registers.
		if ( betterdocs()->settings->get( 'enable_glossaries', false ) && betterdocs()->glossaries_needs_pro_update() ) {
			$this->register_glossaries_taxonomy();
		}

		$this->register_tag_taxonomy();
	}

	/**
	 * Register the post type: docs
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_post_type() {
		$singular_name = $this->settings->get( 'breadcrumb_doc_title' );

		$labels = [
			'name'               => ( $singular_name ) ? $singular_name : 'Docs',
			'singular_name'      => ( $singular_name ) ? $singular_name : 'Docs',
			'menu_name'          => __( 'BetterDocs', 'betterdocs' ),
			'name_admin_bar'     => __( 'Docs', 'betterdocs' ),
			'add_new'            => __( 'Add New', 'betterdocs' ),
			'add_new_item'       => __( 'Add New Docs', 'betterdocs' ),
			'new_item'           => __( 'New Docs', 'betterdocs' ),
			'edit_item'          => __( 'Edit Docs', 'betterdocs' ),
			'view_item'          => __( 'View Docs', 'betterdocs' ),
			'all_items'          => __( 'All Docs', 'betterdocs' ),
			'search_items'       => __( 'Search Docs', 'betterdocs' ),
			'parent_item_colorn' => null,
			'not_found'          => __( 'No docs found', 'betterdocs' ),
			'not_found_in_trash' => __( 'No docs found in trash', 'betterdocs' )
		];

		$betterdocs_articles_caps = apply_filters( 'betterdocs_articles_caps', 'edit_posts', 'article_roles' );

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Add new doc from here', 'betterdocs' ),
			'public'              => true,
			'public_queryable'    => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => $betterdocs_articles_caps,
			'query_var'           => true,
			'capability_type'     => [ 'doc', 'docs' ],
			'hierarchical'        => false,
			'map_meta_cap'        => true,
			'menu_position'       => $this->position,
			'show_in_rest'        => true,
			'menu_icon'           => betterdocs()->assets->icon( 'betterdocs-icon-white.svg' ),
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields', 'comments' ]
		];

		$builtin_doc_page = $this->settings->get( 'builtin_doc_page', false );
		$docs_page        = $this->settings->get( 'docs_page' );

		$args['has_archive'] = ! $builtin_doc_page && $docs_page ? false : $this->docs_archive;

		$args['rewrite'] = betterdocs()->rewrite->docs_type_rewrite(
			[
				'slug'       => $this->docs_archive,
				'with_front' => false
			],
			$this->docs_slug
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Register the taxonomy for Category
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_category_taxonomy() {
		$category_labels = [
			'name'              => __( 'Docs Categories', 'betterdocs' ),
			'singular_name'     => __( 'Docs Category', 'betterdocs' ),
			'all_items'         => __( 'Docs Categories', 'betterdocs' ),
			'parent_item'       => __( 'Parent Docs Category', 'betterdocs' ),
			'parent_item_colon' => __( 'Parent Docs Category:', 'betterdocs' ),
			'edit_item'         => __( 'Edit Category', 'betterdocs' ),
			'update_item'       => __( 'Update Category', 'betterdocs' ),
			'add_new_item'      => __( 'Add New Docs Category', 'betterdocs' ),
			'new_item_name'     => __( 'New Docs Category Name', 'betterdocs' ),
			'menu_name'         => __( 'Categories', 'betterdocs' )
		];

		$category_args = [
			'hierarchical'      => true,
			'public'            => true,
			'labels'            => $category_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'has_archive'       => true,
			'capabilities'      => [
				'manage_terms' => 'manage_doc_terms',
				'edit_terms'   => 'edit_doc_terms',
				'delete_terms' => 'delete_doc_terms',
				'assign_terms' => 'edit_docs'
			]
		];

		$category_args['rewrite'] = apply_filters(
			'betterdocs_category_rewrite',
			[
				'slug'       => $this->cat_slug,
				'with_front' => false
			],
			$this->cat_slug
		);

		register_taxonomy( $this->category, [ $this->post_type ], $category_args );
	}

	/**
	 * Register the taxonomy for Tags.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_tag_taxonomy() {
		$tags_labels = [
			'name'                       => __( 'Docs Tags', 'betterdocs' ),
			'singular_name'              => __( 'Tag', 'betterdocs' ),
			'search_items'               => __( 'Search Tags', 'betterdocs' ),
			'popular_items'              => __( 'Popular Tags', 'betterdocs' ),
			'all_items'                  => __( 'All Tags', 'betterdocs' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Tag', 'betterdocs' ),
			'update_item'                => __( 'Update Tag', 'betterdocs' ),
			'add_new_item'               => __( 'Add New Tag', 'betterdocs' ),
			'new_item_name'              => __( 'New Tag Name', 'betterdocs' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'betterdocs' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'betterdocs' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'betterdocs' ),
			'menu_name'                  => __( 'Tags', 'betterdocs' )
		];

		$tag_args = [
			'hierarchical'          => true,
			'labels'                => $tags_labels,
			'show_ui'               => true,
			'update_count_callback' => '_update_post_term_count',
			'show_admin_column'     => true,
			'query_var'             => true,
			'show_in_rest'          => true,
			'capabilities'          => [
				'manage_terms' => 'manage_doc_terms',
				'edit_terms'   => 'edit_doc_terms',
				'delete_terms' => 'delete_doc_terms',
				'assign_terms' => 'edit_docs'
			]
		];

		$tag_slug = $this->settings->get( 'tag_slug' );

		$tag_args['rewrite'] = apply_filters(
			'betterdocs_tags_rewrite',
			[
				'slug'       => ! empty( $tag_slug ) ? $tag_slug : 'docs-tag',
				'with_front' => false
			]
		);

		register_taxonomy( $this->tag, [ $this->post_type ], $tag_args );
	}

	/**
	 * Backward-compatibility registration of the `glossaries` taxonomy.
	 *
	 * Glossaries moved from Free to Pro in Pro 4.3.1, which registers the
	 * taxonomy itself. Free only calls this while an OLDER Pro (< 4.3.1) is
	 * active — see register() — so that glossary terms stay queryable and their
	 * encyclopedia permalinks keep resolving during the update window. Mirrors
	 * the pre-move Free registration, minus the flush-on-every-request it used to
	 * do: the flush here is one-time, guarded by an option, and only re-runs if
	 * the encyclopedia base slug changes.
	 *
	 * @return void
	 */
	public function register_glossaries_taxonomy() {
		$encyclopedia_root_slug = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopedia' );

		$labels = [
			'name'              => __( 'Glossaries Terms', 'betterdocs' ),
			'singular_name'     => __( 'Glossaries Term', 'betterdocs' ),
			'all_items'         => __( 'Glossaries Terms', 'betterdocs' ),
			'parent_item'       => __( 'Parent Glossaries Term', 'betterdocs' ),
			'parent_item_colon' => __( 'Parent Glossaries Term:', 'betterdocs' ),
			'edit_item'         => __( 'Edit Term', 'betterdocs' ),
			'update_item'       => __( 'Update Glossary', 'betterdocs' ),
			'add_new_item'      => __( 'Add New Glossaries Term', 'betterdocs' ),
			'new_item_name'     => __( 'New Glossaries Term Name', 'betterdocs' ),
			'menu_name'         => __( 'Glossaries', 'betterdocs' )
		];

		// Read/routing-only shim. Editing glossary terms is intentionally locked
		// while Pro is outdated — Free shows the "update Pro to manage Glossaries"
		// teaser (show_glossary_teaser()). We must NOT expose the classic taxonomy
		// admin screen: old Free persisted the definition to the `glossary_term_description`
		// term meta via now-removed save hooks, so the native description field here
		// would write the wrong field and desync old Pro's block/widget (which read
		// the meta). Keep the taxonomy public + queryable for front-end permalinks,
		// but keep every admin/editing surface off so there is no corruption path.
		$args = [
			'hierarchical'      => true,
			'public'            => true,
			'labels'            => $labels,
			'show_ui'           => false,
			'show_in_menu'      => false,
			'show_admin_column' => false,
			'query_var'         => true,
			'show_in_rest'      => false,
			'has_archive'       => true,
			'rewrite'           => [
				'slug'       => $encyclopedia_root_slug,
				'with_front' => false,
			],
			'capabilities'      => [
				'manage_terms' => 'manage_doc_terms',
				'edit_terms'   => 'edit_doc_terms',
				'delete_terms' => 'delete_doc_terms',
				'assign_terms' => 'edit_docs'
			]
		];

		register_taxonomy( $this->glossaries, [ $this->post_type ], $args );

		// Keep the hierarchical term permastruct old Pro relied on: /<root>/<term>.
		global $wp_rewrite;
		$wp_rewrite->extra_permastructs[ $this->glossaries ]['struct'] = '/' . $encyclopedia_root_slug . '/%' . $this->glossaries . '%';

		// One-time flush so the taxonomy's rewrite rules exist in the DB when this
		// compat registration first activates (or the base slug changes). Guarded
		// by an option so it does NOT flush on every request. register() runs on
		// `init` priority 9, so the 999 flush fires later in the same request. The
		// completion marker is written INSIDE the flush callback (after the flush
		// actually runs), so a request that dies before init:999 does not mark the
		// job done — it simply retries next request rather than leaving the rewrite
		// rules permanently unflushed.
		$flush_marker = 'compat:' . $encyclopedia_root_slug;
		if ( get_option( 'betterdocs_glossaries_compat_rewrite' ) !== $flush_marker ) {
			add_action(
				'init',
				function () use ( $flush_marker ) {
					flush_rewrite_rules();
					update_option( 'betterdocs_glossaries_compat_rewrite', $flush_marker, false );
				},
				999
			);
		}
	}

	/**
	 * Get Docs Slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function docs_slug() {
		return $this->rewrite->get_base_slug();
	}

	/**
	 * Get Category Taxonomy Slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function category_slug() {
		return $this->settings->get( 'category_slug', 'docs-category' );
	}

	public function highlight_admin_menu( $parent_file ) {
		global $current_screen;
		// Keep the BetterDocs top-level menu open/highlighted on the classic
		// taxonomy screens it owns — including the classic FAQ Group list
		// (edit-tags.php?taxonomy=betterdocs_faq_category), which otherwise
		// collapses the menu when switching from the React FAQ Builder.
		if ( $current_screen->id === 'edit-docs' || $current_screen->id === 'admin_page_betterdocs-admin' || in_array( $current_screen->id, [ 'edit-doc_tag', 'edit-doc_category', 'edit-betterdocs_faq_category' ] ) ) {
			$parent_file = 'betterdocs-dashboard';
		} elseif ( in_array( $current_screen->id, [ 'edit-doc_tag', 'edit-doc_category' ] ) ) {
			$parent_file = 'edit.php?post_type=docs';
		}

		return apply_filters( 'betterdocs_highlight_admin_menu', $parent_file, $current_screen );
	}

	public function highlight_admin_submenu( $submenu_file ) {
		global $current_screen, $pagenow;

		if ( $current_screen->post_type == 'docs' ) {
			if ( $pagenow == 'edit.php' ) {
				$submenu_file = 'betterdocs-admin';
			}
			if ( $pagenow == 'post.php' ) {
				$submenu_file = 'edit.php?post_type=docs';
			}
			if ( $pagenow == 'post-new.php' ) {
				$submenu_file = 'post-new.php?post_type=docs';
			}
			if ( $current_screen->id === 'edit-doc_category' ) {
				$submenu_file = 'betterdocs-doc-categories';
			}
			if ( $current_screen->id === 'edit-doc_tag' ) {
				$submenu_file = 'betterdocs-doc-tags';
			}
		}

		// Classic FAQ Group list — highlight the FAQ Builder submenu so the
		// active item matches the React builder it was switched from.
		if ( $current_screen->id === 'edit-betterdocs_faq_category' ) {
			$submenu_file = 'betterdocs-faq';
		}

		if ( 'betterdocs_page_betterdocs-settings' == $current_screen->id ) {
			$submenu_file = 'betterdocs-settings';
		}

		if ( 'betterdocs_page_betterdocs-analytics' == $current_screen->id ) {
			$submenu_file = 'betterdocs-analytics';
		}
		if ( 'betterdocs_page_betterdocs-ai-chatbot' == $current_screen->id ) {
            $submenu_file = 'betterdocs-ai-chatbot';
        }

		if ( 'betterdocs_page_betterdocs-setup' == $current_screen->id ) {
			$submenu_file = 'betterdocs-setup';
		}

		return apply_filters( 'betterdocs_highlight_admin_submenu', $submenu_file, $current_screen, $pagenow );
	}

	/**
	 * Deletes rows from the analytics table when a 'docs' post is deleted from trash.
	 *
	 * This function is hooked into the 'wp_trash_post' action in WordPress. It checks
	 * if the deleted post is of type 'docs'. If so, it deletes the corresponding rows
	 * from the analytics table where the post_id matches the ID of the deleted post.
	 *
	 * @param int $post_id The ID of the deleted post.
	 * @return void
	 */
	public function delete_analytics_rows_on_post_delete( $post_id ) {
		// Check if the deleted post is of type 'docs'
		if ( get_post_type( $post_id ) === 'docs' ) {
			global $wpdb;
			$analytics_table = $wpdb->prefix . 'betterdocs_analytics';

			// Delete the entire row from the analytics table where post_id matches the deleted post
            $wpdb->query($wpdb->prepare("DELETE FROM $analytics_table WHERE post_id = %d", $post_id)); // phpcs:ignore
		}
	}
}
