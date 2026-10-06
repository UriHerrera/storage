<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// FAQ-by-category lookups require tax_query / meta_key filters by design.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;

class FAQBuilder extends Base {
	/**
	 * REST API namespace
	 * @var string
	 */
	private $namespace = 'betterdocs';
	public $post_type  = 'betterdocs_faq';
	public $category   = 'betterdocs_faq_category';

	/**
	 * Product FAQ groups taxonomy. Shares the betterdocs_faq post type with the
	 * General FAQ groups but is a separate taxonomy so the two never mix in the
	 * admin or on the front end. Used by the WooCommerce Product FAQ tab.
	 *
	 * @var string
	 */
	public $product_category = 'betterdocs_product_faq_category';

	/**
	 * Post meta recording which FAQ Builder tab an FAQ belongs to: 'product' or
	 * 'general'.
	 *
	 * A General and a Product FAQ are the same post type, distinguished only by which
	 * taxonomy their group lives in — so an FAQ with NO group had no scope at all, and
	 * an ungrouped Product FAQ (its group deleted, or created without one) was
	 * indistinguishable from a General one. That's why it used to surface under the
	 * General tab's "Uncategorized". Recording the scope on the post lets each tab own
	 * its own Uncategorized bucket.
	 */
	const SCOPE_META = '_betterdocs_faq_scope';

	/** Option flag for the one-time backfill of SCOPE_META on pre-existing FAQs. */
	const SCOPE_BACKFILL_OPTION = 'betterdocs_faq_scope_backfilled';

	/**
	 * Term meta on a Product FAQ group holding the product_cat term IDs the
	 * group is assigned to. Products in those categories inherit the group.
	 */
	const GROUP_PRODUCT_CATS_META = '_betterdocs_faq_group_product_cats';

	/**
	 * Term meta on a Product FAQ group holding the individual product IDs the
	 * group is assigned to directly.
	 */
	const GROUP_PRODUCTS_META = '_betterdocs_faq_group_products';

	/**
	 * Term meta flagging a Product FAQ group as shown on every product (no
	 * per-product/per-category targeting). Set on the store-aware sample groups
	 * so they render storefront-wide immediately; cleared the moment the owner
	 * saves explicit category/product assignments.
	 */
	const GROUP_ALL_PRODUCTS_META = '_betterdocs_faq_group_all_products';

	/**
	 * Per-request cache of draft FAQ counts keyed by taxonomy → [ term_id => count ].
	 * Lets the betterdocs_draft_count REST field resolve every term from a single
	 * grouped query instead of one WP_Query per term (N+1).
	 *
	 * @var array<string, array<int, int>>
	 */
	protected $draft_count_cache = [];

	/**
	 *
	 * Initialize the class and start calling our hooks and filters
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct() {
		// assign default admin capabilities for docs, doc terms, doc tags, knowledge base
		add_action( 'init', [ $this, 'register_post' ] );
		// fires after a new betterdocs_faq_category is created
		add_action( 'created_betterdocs_faq_category', [ $this, 'action_created_betterdocs_faq_category' ], 10, 2 );
		add_action( 'created_betterdocs_product_faq_category', [ $this, 'action_created_betterdocs_faq_category' ], 10, 2 );
		add_action( 'rest_api_init', [ $this, 'register_api_endpoint' ] );
		add_action( 'rest_api_init', [ $this, 'register_category_count_fields' ] );

		// Keep each FAQ's scope ('general' | 'product') in sync however its group is
		// assigned — the Builder, the sample-FAQ generator, an import, or the classic
		// post editor all end up here — so an FAQ that later loses its group is still
		// known to belong to its own tab's "Uncategorized" bucket.
		add_action( 'set_object_terms', [ $this, 'sync_faq_scope_meta' ], 10, 4 );
		// One-time backfill for FAQs that predate the scope meta.
		add_action( 'admin_init', [ $this, 'maybe_backfill_faq_scope' ] );
		add_action( 'rest_betterdocs_faq_category_query', [ $this, 'faq_category_orderby_meta' ], 10, 2 );
		add_action( 'rest_betterdocs_product_faq_category_query', [ $this, 'faq_category_orderby_meta' ], 10, 2 );

		// Classic FAQ Group list (edit-tags.php?taxonomy=betterdocs_faq_category):
		// enable drag-and-drop ordering + persist it, mirroring the classic Doc
		// Categories screen.
		add_action( 'admin_enqueue_scripts', [ $this, 'classic_category_scripts' ] );
		add_action( 'wp_ajax_update_faq_cat_order', [ $this, 'ajax_update_category_order' ] );
		add_action( 'admin_head', [ $this, 'order_admin_terms' ] );
	}

	/**
	 * Expose a per-group draft FAQ count on the betterdocs_faq_category REST
	 * response so the FAQ Builder can show "N questions • M draft". The term
	 * `count` only tracks published FAQs (via _update_post_term_count), so the
	 * draft count is computed here.
	 *
	 * @since 4.4.0
	 */
	public function register_category_count_fields() {
		foreach ( [ $this->category, $this->product_category ] as $taxonomy ) {
			register_rest_field(
				$taxonomy,
				'betterdocs_draft_count',
				[
					'get_callback' => function ( $term ) {
						$counts = $this->get_draft_counts_by_term( $term['taxonomy'] );
						return isset( $counts[ (int) $term['id'] ] ) ? (int) $counts[ (int) $term['id'] ] : 0;
					},
					'schema'       => [
						'type'    => 'integer',
						'context' => [ 'view', 'edit' ],
					],
				]
			);
		}
	}

	/**
	 * Draft FAQ counts for every term in a taxonomy, keyed by term_id.
	 *
	 * Computed once per request with a single grouped query and memoized, so the
	 * betterdocs_draft_count REST field no longer fires a WP_Query per term when
	 * a category list is assembled (avoids the N+1 on large group counts).
	 *
	 * @param string $taxonomy
	 * @return array<int, int>
	 */
	protected function get_draft_counts_by_term( $taxonomy ) {
		if ( isset( $this->draft_count_cache[ $taxonomy ] ) ) {
			return $this->draft_count_cache[ $taxonomy ];
		}

		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id, COUNT( p.ID ) AS draft_count
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				 WHERE p.post_type = %s
				   AND p.post_status = 'draft'
				   AND tt.taxonomy = %s
				 GROUP BY tt.term_id",
				$this->post_type,
				$taxonomy
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$counts = [];
		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row->term_id ] = (int) $row->draft_count;
		}

		return $this->draft_count_cache[ $taxonomy ] = $counts;
	}

	public function output() {
		betterdocs()->views->get( 'admin/faq-builder' );
	}

	/**
	 *
	 * Register post type and taxonomies
	 *
	 * @since    1.0.0
	 *
	 */
	public function register_post() {
		/**
		 * Register category taxonomy
		 */
		$category_labels = [
			'name'              => __( 'FAQ Categories', 'betterdocs' ),
			'singular_name'     => __( 'FAQ Category', 'betterdocs' ),
			'all_items'         => __( 'FAQ Categories', 'betterdocs' ),
			'parent_item'       => __( 'Parent FAQ Category', 'betterdocs' ),
			'parent_item_colon' => __( 'Parent FAQ Category:', 'betterdocs' ),
			'edit_item'         => __( 'Edit Category', 'betterdocs' ),
			'update_item'       => __( 'Update Category', 'betterdocs' ),
			'add_new_item'      => __( 'Add New FAQ Category', 'betterdocs' ),
			'new_item_name'     => __( 'New FAQ Category Name', 'betterdocs' ),
			'menu_name'         => __( 'Categories', 'betterdocs' )
		];

		$category_args = [
			'hierarchical'      => true,
			'public'            => false,
			'labels'            => $category_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'has_archive'       => false,
			'rewrite'           => false,
			'capabilities'      => [
				'manage_terms' => 'manage_doc_terms',
				'edit_terms'   => 'edit_doc_terms',
				'delete_terms' => 'delete_doc_terms',
				'assign_terms' => 'edit_docs'
			]
		];

		register_taxonomy( $this->category, [ $this->post_type ], $category_args );
		register_term_meta( $this->category, 'order', [ 'show_in_rest' => true ] );
		register_term_meta( $this->category, 'status', [ 'show_in_rest' => true ] );
		register_term_meta( $this->category, '_betterdocs_faq_order', [ 'show_in_rest' => true ] );
		register_term_meta( $this->category, 'faq_group_icon', [ 'show_in_rest' => true ]);

		/**
		 * Register the Product FAQ groups taxonomy (WooCommerce tab). It mirrors
		 * the General FAQ category taxonomy and shares the betterdocs_faq post
		 * type, but is kept separate so Product FAQ groups never appear in the
		 * General FAQ Builder tab.
		 */
		$product_category_labels = [
			'name'              => __( 'Product FAQ Categories', 'betterdocs' ),
			'singular_name'     => __( 'Product FAQ Category', 'betterdocs' ),
			'all_items'         => __( 'Product FAQ Categories', 'betterdocs' ),
			'parent_item'       => __( 'Parent Product FAQ Category', 'betterdocs' ),
			'parent_item_colon' => __( 'Parent Product FAQ Category:', 'betterdocs' ),
			'edit_item'         => __( 'Edit Product FAQ Category', 'betterdocs' ),
			'update_item'       => __( 'Update Product FAQ Category', 'betterdocs' ),
			'add_new_item'      => __( 'Add New Product FAQ Category', 'betterdocs' ),
			'new_item_name'     => __( 'New Product FAQ Category Name', 'betterdocs' ),
			'menu_name'         => __( 'Product FAQ Categories', 'betterdocs' )
		];

		$product_category_args               = $category_args;
		$product_category_args['labels']     = $product_category_labels;
		$product_category_args['show_admin_column'] = false;

		register_taxonomy( $this->product_category, [ $this->post_type ], $product_category_args );
		register_term_meta( $this->product_category, 'order', [ 'show_in_rest' => true ] );
		register_term_meta( $this->product_category, 'status', [ 'show_in_rest' => true ] );
		register_term_meta( $this->product_category, '_betterdocs_faq_order', [ 'show_in_rest' => true ] );
		register_term_meta( $this->product_category, 'faq_group_icon', [ 'show_in_rest' => true ] );

		// Product FAQ group assignments: which WooCommerce product categories and
		// which individual products this group is shown on. Stored on the group
		// term so the assignment lives in the Create/Update FAQ Group screen.
		$assignment_meta_args = [
			'single'       => true,
			'type'         => 'array',
			'show_in_rest' => [
				'schema' => [
					'type'  => 'array',
					'items' => [ 'type' => 'integer' ],
				],
			],
		];
		register_term_meta( $this->product_category, self::GROUP_PRODUCT_CATS_META, $assignment_meta_args );
		register_term_meta( $this->product_category, self::GROUP_PRODUCTS_META, $assignment_meta_args );
		register_term_meta(
			$this->product_category,
			self::GROUP_ALL_PRODUCTS_META,
			[
				'single'       => true,
				'type'         => 'boolean',
				'show_in_rest' => true,
			]
		);

		/**
		 * Register post type
		 */
		$labels = [
			'name'               => __( 'BetterDocs FAQ', 'betterdocs' ),
			'singular_name'      => __( 'BetterDocs FAQ', 'betterdocs' ),
			'menu_name'          => __( 'FAQ', 'betterdocs' ),
			'name_admin_bar'     => __( 'FAQ', 'betterdocs' ),
			'add_new'            => __( 'Add New', 'betterdocs' ),
			'add_new_item'       => __( 'Add New FAQ', 'betterdocs' ),
			'new_item'           => __( 'New FAQ', 'betterdocs' ),
			'edit_item'          => __( 'Edit FAQ', 'betterdocs' ),
			'view_item'          => __( 'View FAQ', 'betterdocs' ),
			'all_items'          => __( 'All FAQ', 'betterdocs' ),
			'search_items'       => __( 'Search FAQ', 'betterdocs' ),
			'parent_item_colorn' => null,
			'not_found'          => __( 'No FAQ found', 'betterdocs' ),
			'not_found_in_trash' => __( 'No FAQ found in trash', 'betterdocs' )
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Add new faq from here', 'betterdocs' ),
			'public'              => false,
			'public_queryable'    => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'capability_type'     => [ 'doc', 'docs' ],
			'hierarchical'        => true,
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'show_in_rest'        => true,
			'menu_icon'           => betterdocs()->assets->icon( 'betterdocs-icon-white.svg' ),
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields', 'comments' ]
		];

		register_post_type( $this->post_type, $args );
		register_post_meta( $this->post_type, 'faq_open_by_default', [ 'show_in_rest' => true, 'single' => true ] );
	}

	/**
	 * Default the taxonomy's terms' order if it's not set.
	 *
	 * @param string $tax_slug The taxonomy's slug.
	 */
	public function action_created_betterdocs_faq_category( $term_id ) {
		$term     = get_term( $term_id );
		$taxonomy = ( $term && ! is_wp_error( $term ) ) ? $term->taxonomy : $this->category;
		$order    = $this->get_max_taxonomy_order( $taxonomy );
		update_term_meta( $term_id, 'order', $order++ );
		update_term_meta( $term_id, 'status', 1 );
	}

	/**
	 * Default the taxonomy's terms' order if it's not set.
	 *
	 * @param string $tax_slug The taxonomy's slug.
	 */
	public function default_term_order( $tax_slug ) {
		$terms = get_terms(
			[
				'taxonomy'   => $tax_slug,
				'hide_empty' => false,
			]
		);
		$order = $this->get_max_taxonomy_order( $tax_slug );

		foreach ( $terms as $term ) {
			if ( ! get_term_meta( $term->term_id, 'order', true ) ) {
				update_term_meta( $term->term_id, 'order', $order );
				++$order;
			}
		}
	}

	/**
	 * Get the maximum order for this taxonomy. This will be applied to terms that don't have a tax position.
	 */
	private function get_max_taxonomy_order( $tax_slug ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- live max-order needed when assigning new terms; cache would be stale.
		$max_term_order = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT MAX( CAST( tm.meta_value AS UNSIGNED ) )
				FROM $wpdb->terms t
				JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id AND tt.taxonomy = %s
				JOIN $wpdb->termmeta tm ON tm.term_id = t.term_id WHERE tm.meta_key = 'order'",
				$tax_slug
			)
		);

		$max_term_order = is_array( $max_term_order ) ? current( $max_term_order ) : 0;

		return (int) $max_term_order === 0 || empty( $max_term_order ) ? 1 : (int) $max_term_order + 1;
	}

	/**
	 * Re-Order the taxonomies based on the order value.
	 *
	 * @param array $pieces     Array of SQL query clauses.
	 * @param array $taxonomies Array of taxonomy names.
	 * @param array $args       Array of term query args.
	 */
	public function set_tax_order( $pieces, $taxonomies, $args ) {
		// Only force the manual drag-drop `order` meta when the FAQ Builder
		// header dropdown is on "default". For name/count/id sort modes, let
		// get_terms() keep its own orderby so the front end matches the builder.
		if ( betterdocs()->query->get_faq_order_key() !== 'default' ) {
			return $pieces;
		}

		foreach ( $taxonomies as $taxonomy ) {
			global $wpdb;

			if ( $taxonomy === 'betterdocs_faq_category' ) {
				$join_statement = " LEFT JOIN $wpdb->termmeta AS term_meta ON t.term_id = term_meta.term_id AND term_meta.meta_key = 'order'";

				if ( ! $this->does_substring_exist( $pieces['join'], $join_statement ) ) {
					$pieces['join'] .= $join_statement;
				}

				$pieces['orderby'] = 'ORDER BY CAST( term_meta.meta_value AS UNSIGNED )';
			}
		}

		return $pieces;
	}

	/**
	 * Order the taxonomies on the front end.
	 */
	public function front_end_order_terms() {
		if ( ! is_admin() ) {
			add_filter( 'terms_clauses', [ $this, 'set_tax_order' ], 10, 3 );
		}
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
	 * Load the shared drag-and-drop sorter on the classic FAQ Group list
	 * (edit-tags.php?taxonomy=betterdocs_faq_category). Reuses the generic,
	 * config-driven admin/js/category-edit.js — the same script the classic Doc
	 * Categories screen uses — pointed at the FAQ category ordering AJAX action.
	 *
	 * jquery + jquery-ui-sortable are declared explicitly so `.sortable()` is
	 * always defined (the bare script reported jQuery/Sortable as undefined here
	 * because no sorter was enqueued on this screen at all).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function classic_category_scripts( $hook ) {
		if ( 'edit-tags.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->taxonomy !== $this->category ) {
			return;
		}

		betterdocs()->assets->enqueue(
			'betterdocs-category-edit',
			'admin/js/category-edit.js',
			[ 'jquery', 'jquery-ui-sortable' ]
		);

		betterdocs()->assets->localize(
			'betterdocs-category-edit',
			'betterdocsCategorySorting',
			[
				'action'      => 'update_faq_cat_order',
				'selector'    => '.taxonomy-' . $this->category,
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'faq_cat_order_nonce' ),
				// Paged only sets the ordering base offset (the AJAX write itself
				// is nonce-protected), so read it directly from the WP term-list
				// pagination link so cross-page ordering stays correct.
				'paged'       => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'per_page_id' => "edit_{$this->category}_per_page",
			]
		);
	}

	/**
	 * Persist FAQ Group order from the classic drag-and-drop sorter. Writes the
	 * `order` term meta that the React FAQ Builder and the front end already read,
	 * so all three stay in sync. Mirrors PostType::update_category_order.
	 */
	public function ajax_update_category_order() {
		if ( ! check_ajax_referer( 'faq_cat_order_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Nonce Failed', 'betterdocs' ) );
		}

		if ( ! $this->can_manage_faqs() ) {
			wp_send_json_error( __( 'You don\'t have permission to manage FAQ groups.', 'betterdocs' ) );
		}

		$base_index = isset( $_POST['base_index'] ) ? intval( $_POST['base_index'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) {
			$ordering = array_filter(
				array_map(
					function ( $item ) {
						if ( is_array( $item ) && isset( $item['term_id'], $item['order'] ) ) {
							return [
								'term_id' => intval( $item['term_id'] ),
								'order'   => intval( $item['order'] ),
							];
						}
						return null;
					},
					wp_unslash( $_POST['data'] ) // phpcs:ignore
				)
			);
		} else {
			$ordering = [];
		}

		foreach ( $ordering as $order_data ) {
			update_term_meta( $order_data['term_id'], 'order', $order_data['order'] + $base_index );
		}

		wp_send_json_success( __( 'Successfully updated.', 'betterdocs' ) );
	}

	/**
	 * Order the classic FAQ Group list by the manual `order` term meta so the
	 * drag-and-drop order persists across reloads. Seeds the meta for any terms
	 * missing it first (e.g. groups created before ordering existed). Mirrors
	 * PostType::order_terms for the Doc Categories screen.
	 */
	public function order_admin_terms() {
		global $current_screen;

		if (
			! isset( $_GET['orderby'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_screen &&
			! empty( $current_screen->base ) &&
			$current_screen->base === 'edit-tags' &&
			$current_screen->taxonomy === $this->category
		) {
			$this->default_term_order( $this->category );
			add_filter( 'terms_clauses', [ $this, 'admin_order_terms_clauses' ], 10, 3 );
		}
	}

	/**
	 * terms_clauses filter (classic FAQ Group admin list only): force ORDER BY
	 * the `order` term meta. Unlike set_tax_order this is not gated on the
	 * builder's saved order key — the classic screen is always manual ordering.
	 */
	public function admin_order_terms_clauses( $pieces, $taxonomies, $args ) {
		if ( ! in_array( $this->category, (array) $taxonomies, true ) ) {
			return $pieces;
		}

		global $wpdb;
		$join_statement = " LEFT JOIN $wpdb->termmeta AS bd_faq_order ON t.term_id = bd_faq_order.term_id AND bd_faq_order.meta_key = 'order'";

		if ( ! $this->does_substring_exist( $pieces['join'], $join_statement ) ) {
			$pieces['join'] .= $join_statement;
		}

		$pieces['orderby'] = 'ORDER BY CAST( bd_faq_order.meta_value AS UNSIGNED )';

		return $pieces;
	}

	/**
	 * Whether the current user may manage FAQ groups and FAQs.
	 *
	 * Two capabilities, either of which is enough. Nothing that could reach
	 * these routes before can be turned away now — the gate only widens.
	 *
	 * - `edit_others_posts` is the original gate, kept verbatim for backward
	 *   compatibility: every site that granted FAQ Builder access by granting a
	 *   core editor-level role keeps working exactly as it did.
	 * - `edit_others_docs` is the BetterDocs-side answer, added in 4.9.0. The
	 *   `betterdocs_faq` post type is registered with
	 *   `capability_type => [ 'doc', 'docs' ]` and `map_meta_cap => true`, so
	 *   WordPress already governs individual FAQ posts with the docs capability
	 *   family — the routes were the one place that asked for a *core* post
	 *   capability instead. A documentation-only role (BetterDocs' own `editor`
	 *   bucket, or anything Pro's `article_roles` grants) could edit every FAQ
	 *   post through `wp/v2/betterdocs_faq` and still get a 403 from the FAQ
	 *   Builder's own API.
	 *
	 * It is also what lines the REST routes up with the MCP FAQ abilities, which
	 * are gated on `edit_others_docs`: an agent that may create an FAQ through
	 * `bd-create-faq` reaches these same routes underneath.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function can_manage_faqs() {
		return current_user_can( 'edit_others_posts' ) || current_user_can( 'edit_others_docs' );
	}

	public function register_api_endpoint() {
		register_rest_route(
			$this->namespace,
			'/faq/sample_data',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'create_faq_sample' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/posts/(?P<type>\S+)',
			[
				'methods'             => [ 'GET' ],
				'callback'            => [ $this, 'fetch_faq_posts' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/create_category',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'create_faq_category' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/update_category',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'update_faq_category' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/delete_category',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'delete_faq_category' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/create_post',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'create_betterdocs_faq' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/update_post',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'update_betterdocs_faq' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/delete_post',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'delete_betterdocs_faq' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/category_status',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'update_category_status' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/category_order',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'update_faq_category_order' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/update_order_by_category',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'update_faq_order_by_category' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/order',
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ $this, 'update_faq_order_preference' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/uncategorised',
			[
				'methods'             => [ 'GET' ],
				'callback'            => [ $this, 'get_uncategorised_faq' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				}
			]
		);

		register_rest_route(
			$this->namespace,
			'/faq/category_search',
			[
				'methods'             => [ 'GET' ],
				'callback'            => [ $this, 'category_search' ],
				'permission_callback' => function () {
					return $this->can_manage_faqs();
				},
				'args'                => [
					'title' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field'
					],
					'taxonomy' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field'
					]
				]
			]
		);
	}

	public function create_faq_sample( $params ) {
		$sample_data = json_decode( $params->get_param( 'sample_data' ), true );
		foreach ( $sample_data as $key => $value ) {
			$insert_term = wp_insert_term(
				$key,
				'betterdocs_faq_category'
			);
			if ( $insert_term ) {
				foreach ( $value['posts'] as $key => $value ) {
					$this->insert_betterdocs_faq( $value['post_title'], $value['post_content'], $insert_term['term_id'] );
				}
			}
		}
		return true;
	}

	/**
	 * Resolve the taxonomy from a request, whitelisted to the General and
	 * Product FAQ category taxonomies. Defaults to the General taxonomy so
	 * existing callers (which never send a taxonomy) keep their behavior.
	 *
	 * @param \WP_REST_Request $params
	 * @return string
	 */
	private function resolve_taxonomy( $params ) {
		$taxonomy = $params->get_param( 'taxonomy' );
		return in_array( $taxonomy, [ $this->category, $this->product_category ], true )
			? $taxonomy
			: $this->category;
	}

	public function create_faq_category( $params ) {
		// The WP term `name` column is varchar(200); cap the length so an
		// over-long title can't trigger a DB insert error (the React form caps
		// this too, this is the server-side guard).
		$title       = mb_substr( (string) $params->get_param( 'title' ), 0, 200 );
		$description = $params->get_param( 'description' );
		$description = ( $description !== 'undefined' ) ? $description : '';
		$slug        = $params->get_param( 'slug' );
		$group_icon_url = $params->get_param('group_icon_url');
		$taxonomy    = $this->resolve_taxonomy( $params );
		$result      = $this->insert_betterdocs_faq_category(
			$title,
			$description,
			$group_icon_url,
			$slug,
			$taxonomy,
			(array) $params->get_param( 'product_cats' ),
			(array) $params->get_param( 'products' ),
			$this->all_products_param( $params )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array( 'success' => true, 'term_id' => (int) $result ) );
	}

	public function update_faq_category( $params ) {
		$term_id     = $params->get_param( 'term_id' );
		// Cap at the varchar(200) term-name limit (server-side guard).
		$title       = mb_substr( (string) $params->get_param( 'title' ), 0, 200 );
		$description = $params->get_param( 'description' );
		$group_icon_url = $params->get_param('group_icon_url');
		$description = ( $description !== 'undefined' ) ? $description : '';
		$slug        = $params->get_param( 'slug' );
		$taxonomy    = $this->resolve_taxonomy( $params );
		$update      = wp_update_term(
			$term_id,
			$taxonomy,
			[
				'name'        => $title,
				'slug'        => $slug,
				'description' => $description
			]
		);

		if ( is_wp_error( $update ) ) {
			return $update;
		} else {
			$term_id = isset( $update['term_id'] ) ?  $update['term_id']  : 0;
			if( $term_id != 0 ) {
				$previous_icon_url = get_term_meta($term_id, 'faq_group_icon', true);
				update_term_meta($term_id, 'faq_group_icon', $group_icon_url, $previous_icon_url);
				$this->save_group_assignments(
					$term_id,
					$taxonomy,
					(array) $params->get_param( 'product_cats' ),
					(array) $params->get_param( 'products' ),
					$this->all_products_param( $params )
				);
			}
			return true;
		}
	}

    public function delete_faq_category( $params ) {
        $term_id = $params->get_param( 'term_id' );
        $delete_all_docs = $params->get_param('with_all_post');
        $taxonomy = $this->resolve_taxonomy( $params );

        if ( $delete_all_docs ) {
            Helper::delete_specific_faq_posts_by_faq_category( $term_id, $taxonomy );
        }

        $delete  = wp_delete_term( $term_id, $taxonomy );

		if ( is_wp_error( $delete ) ) {
			return $delete;
		} else {
			return true;
		}
	}

	public function insert_betterdocs_faq_category( $title, $description, $icon_url, $slug = '', $taxonomy = 'betterdocs_faq_category', $product_cats = [], $products = [], $all_products = false ) {
		$insert_term = wp_insert_term(
			$title,
			$taxonomy,
			[
				'slug'        => $slug,
				'description' => $description
			]
		);

		if ( is_wp_error( $insert_term ) ) {
			return $insert_term;
		} else {
			$term_id = isset( $insert_term['term_id'] ) ?  $insert_term['term_id']  : 0;
			if( $term_id != 0 ) {
				update_term_meta($term_id, 'faq_group_icon', $icon_url);
				$this->save_group_assignments( $term_id, $taxonomy, $product_cats, $products, $all_products );
			}
			// Return the new term id so the admin can jump to its pagination
			// page and highlight it (mirrors the new-FAQ reveal flow).
			return (int) $term_id;
		}
	}

	/**
	 * Persist a Product FAQ group's targeting. Three mutually-exclusive scopes:
	 *  - all products      → GROUP_ALL_PRODUCTS_META, shown on every product page;
	 *  - specific cats/products → GROUP_PRODUCT_CATS_META / GROUP_PRODUCTS_META;
	 *  - none → the group is hidden on the storefront.
	 *
	 * No-op for any taxonomy other than the Product FAQ groups taxonomy.
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 * @param array  $product_cats Incoming product_cat term IDs.
	 * @param array  $products     Incoming product post IDs.
	 * @param bool   $all_products When true, show on ALL products and clear the cat/product
	 *                             targets (the scopes are mutually exclusive).
	 */
	private function save_group_assignments( $term_id, $taxonomy, $product_cats, $products, $all_products = false ) {
		if ( $taxonomy !== $this->product_category ) {
			return;
		}

		// "Show on all products" wins and clears the specific targets, so a store-wide
		// group (e.g. the generated "Shipping, Returns & Payments") can be switched to
		// specific categories/products and back, from the same modal.
		if ( $all_products ) {
			update_term_meta( $term_id, self::GROUP_ALL_PRODUCTS_META, true );
			delete_term_meta( $term_id, self::GROUP_PRODUCT_CATS_META );
			delete_term_meta( $term_id, self::GROUP_PRODUCTS_META );
			return;
		}

		$cat_ids = [];
		foreach ( (array) $product_cats as $cid ) {
			$cid = (int) $cid;
			if ( $cid > 0 && term_exists( $cid, 'product_cat' ) ) {
				$cat_ids[] = $cid;
			}
		}
		$cat_ids = array_values( array_unique( $cat_ids ) );

		$product_ids = [];
		foreach ( (array) $products as $pid ) {
			$pid = (int) $pid;
			if ( $pid > 0 && 'product' === get_post_type( $pid ) ) {
				$product_ids[] = $pid;
			}
		}
		$product_ids = array_values( array_unique( $product_ids ) );

		if ( empty( $cat_ids ) ) {
			delete_term_meta( $term_id, self::GROUP_PRODUCT_CATS_META );
		} else {
			update_term_meta( $term_id, self::GROUP_PRODUCT_CATS_META, $cat_ids );
		}

		if ( empty( $product_ids ) ) {
			delete_term_meta( $term_id, self::GROUP_PRODUCTS_META );
		} else {
			update_term_meta( $term_id, self::GROUP_PRODUCTS_META, $product_ids );
		}

		// Explicitly not "all products" → drop the match-all flag.
		delete_term_meta( $term_id, self::GROUP_ALL_PRODUCTS_META );
	}

	/**
	 * Read + normalize the `all_products` REST param (accepts '1'/'0'/true/false).
	 *
	 * @return bool
	 */
	private function all_products_param( $params ) {
		return filter_var( $params->get_param( 'all_products' ), FILTER_VALIDATE_BOOLEAN );
	}

	public function update_faq_category_order( $params ) {
		$faq_category_order = $params->get_param( 'faq_category_order' );
		$faq_category_order = json_decode( $faq_category_order, true );

		foreach ( $faq_category_order as $order_data ) {
			if ( (int) $order_data['current_position'] != (int) $order_data['updated_position'] ) {
				update_term_meta( $order_data['id'], 'order', ( (int) $order_data['updated_position'] ) );
			}
		}
		return true;
	}

	public function insert_betterdocs_faq( $post_title, $post_content, $term_id, $taxonomy = 'betterdocs_faq_category' ) {
		$post = wp_insert_post(
			[
				'post_type'    => 'betterdocs_faq',
				'post_title'   => wp_strip_all_tags( $post_title ),
				'post_content' => $post_content,
				'post_status'  => 'publish'
			]
		);

		// Stamp the scope from the tab this FAQ was created in. Doing it here (and not
		// only in sync_faq_scope_meta) is what makes an FAQ created WITHOUT a group land
		// in the right tab's "Uncategorized" — with no terms assigned, set_object_terms
		// never fires.
		if ( $post && ! is_wp_error( $post ) ) {
			update_post_meta(
				$post,
				self::SCOPE_META,
				$taxonomy === $this->product_category ? 'product' : 'general'
			);
		}

		if ( $term_id ) {
			$set_terms = wp_set_object_terms( $post, $term_id, $taxonomy );
			if ( is_wp_error( $set_terms ) ) {
				return $set_terms;
			}
			$this->update_faq_order_on_insert( $term_id, $post );
		}

		// Always return the new post ID so the admin can reveal/highlight the
		// created FAQ (the grouped path previously returned the term-meta result).
		return $post;
	}

	public function update_faq_order_on_insert( $term_id, $post ) {
		// QA-008: read the single stored value and strip blanks so the very first
		// insert (no existing meta) doesn't explode(',', null) into a corrupt [''].
		$existing      = (string) get_term_meta( $term_id, '_betterdocs_faq_order', true );
		$term_meta_arr = array_filter( array_map( 'trim', explode( ',', $existing ) ), 'strlen' );
		if ( ! in_array( (string) $post, $term_meta_arr, true ) ) {
			array_unshift( $term_meta_arr, $post );
			$docs_ordering_data = filter_var_array( wp_unslash( $term_meta_arr ), FILTER_SANITIZE_NUMBER_INT );
			return update_term_meta( $term_id, '_betterdocs_faq_order', implode( ',', $docs_ordering_data ) );
		}
	}

	/**
	 * Update _betterdocs_faq_order meta when new post created
	 */

	public function update_faq_order_by_category( $params ) {
		$term_id = $params->get_param( 'term_id' );
		$posts   = $params->get_param( 'posts' );
		return update_term_meta( $term_id, '_betterdocs_faq_order', $posts );
	}

	/**
	 * Persist the FAQ Builder header order dropdown so the front end can mirror
	 * it. Stored as a single global preference in the `betterdocs_faq_order`
	 * option and read back by Query::get_faq_order_key().
	 */
	public function update_faq_order_preference( $params ) {
		$order   = $params->get_param( 'order' );
		$allowed = array( 'default', 'most_recent', 'least_recent', 'a_to_z', 'z_to_a', 'most_questions' );

		if ( ! in_array( $order, $allowed, true ) ) {
			$order = 'default';
		}

		update_option( 'betterdocs_faq_order', $order );

		return rest_ensure_response( array( 'success' => true, 'order' => $order ) );
	}

	public function create_betterdocs_faq( $params ) {
		$post_title   = $params->get_param( 'post_title' );
		$post_content = $params->get_param( 'post_content' );
		$term_id      = $params->get_param( 'term_id' );
		$taxonomy     = $this->resolve_taxonomy( $params );
		return $this->insert_betterdocs_faq( $post_title, $post_content, $term_id, $taxonomy );
	}

	public function update_betterdocs_faq( $params ) {
		$post_id = (int) $params->get_param( 'post_id' );

		// QA-001: never let an arbitrary post ID be retyped/overwritten through
		// this endpoint. Only operate on posts that are already BetterDocs FAQs.
		if ( ! $post_id || get_post_type( $post_id ) !== $this->post_type ) {
			return new WP_Error( 'betterdocs_invalid_faq', __( 'Invalid FAQ ID.', 'betterdocs' ), [ 'status' => 404 ] );
		}

		$post_title   = $params->get_param( 'post_title' );
		$post_content = $params->get_param( 'post_content' );
		$status       = $params->get_param( 'status' );
		$term_id      = (int) $params->get_param( 'term_id' );
		$taxonomy     = $this->resolve_taxonomy( $params );
		if ( $status ) {
			$data = [
				'post_type' => 'betterdocs_faq',
				'ID'        => $post_id,
				'status'    => $status
			];
		} else {
			$data = [
				'post_type'    => 'betterdocs_faq',
				'ID'           => $post_id,
				// QA-009: sanitize server-side — the editor posts raw TinyMCE
				// HTML. Title is plain text; content keeps post-safe HTML only
				// (scripts / event handlers stripped) even for unfiltered_html users.
				'post_title'   => sanitize_text_field( $post_title ),
				'post_content' => wp_kses_post( $post_content ),
			];

			// QA-007: only assign a term that actually exists in the resolved FAQ
			// taxonomy — an invalid term_id would silently orphan the FAQ.
			if ( $term_id && term_exists( $term_id, $taxonomy ) ) {
				$data['tax_input'] = [
					$taxonomy => [ $term_id ],
				];

				// QA-008: build the order list defensively. On the first update the
				// term has no _betterdocs_faq_order meta; the old code did
				// explode(',', null) → [''] which corrupted the order with a blank
				// entry (plus a PHP 8.1 "null to explode" deprecation). Read the
				// single value and drop blanks instead.
				$existing      = (string) get_term_meta( $term_id, '_betterdocs_faq_order', true );
				$term_meta_arr = array_filter( array_map( 'trim', explode( ',', $existing ) ), 'strlen' );
				if ( ! in_array( (string) $post_id, $term_meta_arr, true ) ) {
					array_unshift( $term_meta_arr, $post_id );
					$docs_ordering_data = filter_var_array( wp_unslash( $term_meta_arr ), FILTER_SANITIZE_NUMBER_INT );
					update_term_meta( $term_id, '_betterdocs_faq_order', implode( ',', $docs_ordering_data ) );
				}
			}
		}

		return wp_update_post( $data );
	}

	public function delete_betterdocs_faq( $params ) {
		$post_id = (int) $params->get_param( 'post_id' );

		// QA-001: refuse to delete anything that isn't a BetterDocs FAQ.
		if ( ! $post_id || get_post_type( $post_id ) !== $this->post_type ) {
			return new WP_Error( 'betterdocs_invalid_faq', __( 'Invalid FAQ ID.', 'betterdocs' ), [ 'status' => 404 ] );
		}

		return wp_delete_post( $post_id );
	}

	public function faq_post_loop( $args ) {
		$posts = [];
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				$posts[ get_the_ID() ]['title']   = get_the_title();
				$posts[ get_the_ID() ]['content'] = get_the_content();
			endwhile;
		endif;

		return $posts;
	}

	public function update_category_status( $params ) {
		$term_id = $params->get_param( 'term_id' );
		$status  = $params->get_param( 'status' );
		return update_term_meta( $term_id, 'status', $status );
	}

	public function fetch_faq_posts( $params ) {
		$faq  = [];
		$type = $params->get_param( 'type' );

		if ( $type == 'category' ) {
			$taxonomy_objects = get_terms(
				[
					'taxonomy'   => 'betterdocs_faq_category',
					'hide_empty' => false
				]
			);

			if ( $taxonomy_objects && ! is_wp_error( $taxonomy_objects ) ) :
				foreach ( $taxonomy_objects as $term ) :
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- core FAQ-by-category query; tax filtering is required functionality.
					$args = [
						'post_type'     => 'betterdocs_faq',
						'post_status'   => 'publish',
						'post_per_page' => -1,
						'tax_query'     => [
							[
								'taxonomy' => 'betterdocs_faq_category',
								'field'    => 'term_id',
								'terms'    => $term->term_id
							]
						]
					];

					$posts = $this->faq_post_loop( $args );

					$faq[ $term->slug ] = [
						(array) $term,
						'posts' => $posts
					];
				endforeach;
			endif;
		} else {
			$args         = [
				'post_type'     => 'betterdocs_faq',
				'post_status'   => 'publish',
				'post_per_page' => -1
			];
			$posts        = $this->faq_post_loop( $args );
			$faq['posts'] = $posts;
		}

		return $faq;
	}

	/**
	 * Record an FAQ's scope whenever its group is (re)assigned, so the scope survives
	 * the group being deleted. Fires for every path that assigns terms.
	 *
	 * @param int    $object_id Post ID.
	 * @param array  $terms     Terms assigned (unused).
	 * @param array  $tt_ids    Term-taxonomy IDs.
	 * @param string $taxonomy  Taxonomy the terms belong to.
	 */
	public function sync_faq_scope_meta( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( get_post_type( $object_id ) !== $this->post_type ) {
			return;
		}

		if ( $taxonomy === $this->product_category && ! empty( $tt_ids ) ) {
			update_post_meta( $object_id, self::SCOPE_META, 'product' );
			return;
		}

		// Assigning a General group makes it a General FAQ — but only claim it if it
		// isn't already a Product FAQ that merely also carries a General term.
		if ( $taxonomy === $this->category && ! empty( $tt_ids ) ) {
			if ( get_post_meta( $object_id, self::SCOPE_META, true ) !== 'product' ) {
				update_post_meta( $object_id, self::SCOPE_META, 'general' );
			}
		}
	}

	/**
	 * One-time backfill: stamp the scope onto FAQs created before SCOPE_META existed.
	 * An FAQ holding a Product group is 'product'; everything else is 'general'.
	 * Ungrouped legacy FAQs have no way to be identified as product ones, so they stay
	 * in the General tab, which is exactly where they are today.
	 */
	public function maybe_backfill_faq_scope() {
		if ( get_option( self::SCOPE_BACKFILL_OPTION ) ) {
			return;
		}

		$product_faqs = get_posts(
			[
				'post_type'      => $this->post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one-time backfill.
				'tax_query'      => [
					[
						'taxonomy' => $this->product_category,
						'operator' => 'EXISTS',
					],
				],
			]
		);

		foreach ( $product_faqs as $faq_id ) {
			update_post_meta( $faq_id, self::SCOPE_META, 'product' );
		}

		update_option( self::SCOPE_BACKFILL_OPTION, 1 );
	}

	/**
	 * FAQs with no group, for the tab that asked. Each tab owns its own bucket:
	 * the Product tab lists ungrouped FAQs scoped to 'product', the General tab lists
	 * everything else that is ungrouped (so an ungrouped Product FAQ no longer leaks
	 * into General).
	 */
	public function get_uncategorised_faq( $request = null ) {
		$taxonomy   = $request instanceof WP_REST_Request ? $this->resolve_taxonomy( $request ) : $this->category;
		$is_product = ( $taxonomy === $this->product_category );

		$term_ids = function ( $tax ) {
			$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] );
			return is_wp_error( $terms ) ? [] : array_map(
				function ( $term ) {
					return $term->term_id;
				},
				$terms
			);
		};

		// "Ungrouped" always means: no group in THIS tab's taxonomy.
		$tax_query = [
			'relation' => 'AND',
			[
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids( $taxonomy ),
				'operator' => 'NOT IN'
			]
		];

		$meta_query = [];

		if ( $is_product ) {
			// Product tab: only FAQs that belong to the Product side.
			$meta_query[] = [
				'key'   => self::SCOPE_META,
				'value' => 'product',
			];
		} else {
			// General tab: exclude anything scoped to Product…
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => self::SCOPE_META,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => self::SCOPE_META,
					'value'   => 'product',
					'compare' => '!=',
				],
			];

			// …and, as before, anything still holding a Product group (belt and braces
			// for FAQs whose scope meta never got written).
			$product_terms = $term_ids( $this->product_category );
			if ( ! empty( $product_terms ) ) {
				$tax_query[] = [
					'taxonomy' => $this->product_category,
					'field'    => 'term_id',
					'terms'    => $product_terms,
					'operator' => 'NOT IN'
				];
			}
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- intentional NOT-IN scan to find FAQs without any group.
		$posts = get_posts(
			[
				'post_type'      => 'betterdocs_faq',
				'post_status'    => $this->can_manage_faqs() ? [ 'publish', 'draft' ] : 'publish',
				'posts_per_page' => -1,
				'tax_query'      => $tax_query,
				'meta_query'     => $meta_query,
			]
		);

		// get_posts() returns raw WP_Post objects without a `meta` property. The
		// FAQ Builder reads `faq.meta.faq_open_by_default` to seed the "Keep It
		// Open By default" switcher, so attach it here to mirror the scalar shape
		// the wp/v2 endpoint returns for categorized FAQs ('' when unset, '1'
		// when enabled). Without this the value is undefined and the switcher
		// defaults to ON for every uncategorized FAQ.
		foreach ( $posts as $post ) {
			$post->meta = [
				'faq_open_by_default' => get_post_meta( $post->ID, 'faq_open_by_default', true ),
			];
		}

		return $posts;
	}

	public function category_search( $request ) {

		$title    = $request['title'];
		$taxonomy = $this->resolve_taxonomy( $request );

		// Perform the taxonomy search
		$taxonomy_args = [
			'name__like' => $title,
			'taxonomy'   => $taxonomy,
			'hide_empty' => false
		];

		$taxonomies = get_terms( $taxonomy_args );

		if ( ! empty( $taxonomies ) ) {
			$result = [];
			foreach ( $taxonomies as $taxonomy ) {
				$result[] = [
					'id'          => $taxonomy->term_id,
					'count'       => $taxonomy->count,
					'description' => $taxonomy->description,
					'name'        => $taxonomy->name,
					'slug'        => $taxonomy->slug
					// Add more fields as needed
				];
			}
			// Return the taxonomy data
			return $result;
		} else {
			// Taxonomy not found
			return new WP_Error( 'taxonomy_not_found', 'Taxonomy not found.', [ 'status' => 404 ] );
		}
	}

	public function faq_category_orderby_meta( $args, $request ) {
		if ( ! in_array( $args['taxonomy'], [ $this->category, $this->product_category ], true ) ) {
			return $args;
		}

		// Order (and therefore paginate) the whole group list server-side to
		// match the FAQ Builder header dropdown, so page boundaries are cut on
		// the same sort the rows are shown in — otherwise page 1 could end on
		// "W" and page 2 start on "B". The admin passes the order key explicitly
		// (independent of when the preference option finishes saving); fall back
		// to the saved preference, then to the manual drag-drop order.
		$order_key = $request->get_param( 'betterdocs_faq_order' );
		if ( empty( $order_key ) ) {
			$order_key = get_option( 'betterdocs_faq_order', 'default' );
		}

		switch ( $order_key ) {
			case 'most_recent':
				$args['orderby'] = 'term_id';
				$args['order']   = 'DESC';
				unset( $args['meta_key'] );
				break;
			case 'least_recent':
				$args['orderby'] = 'term_id';
				$args['order']   = 'ASC';
				unset( $args['meta_key'] );
				break;
			case 'a_to_z':
				$args['orderby'] = 'name';
				$args['order']   = 'ASC';
				unset( $args['meta_key'] );
				break;
			case 'z_to_a':
				$args['orderby'] = 'name';
				$args['order']   = 'DESC';
				unset( $args['meta_key'] );
				break;
			case 'most_questions':
				$args['orderby'] = 'count';
				$args['order']   = 'DESC';
				unset( $args['meta_key'] );
				break;
			case 'default':
			default:
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'order';
				$args['order']    = 'ASC';
				break;
		}

		return $args;
	}
}
