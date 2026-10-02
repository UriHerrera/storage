<?php
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- shortcode exposes user-driven exclusion attribute.
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

class CategoryGrid extends Shortcode {
	protected $layout_class = 'layout-1';

	/**
	 * A list of deprecated attributes.
	 * @var array<string, string>
	 */
	protected $deprecated_attributes = [
		'category'       => 'taxonomy',
		'posts_per_grid' => 'posts_per_page',
		'icon'           => 'show_icon',
		'post_counter'   => 'show_count'
	];

	public function __construct( Settings $settings, Query $query, Helper $helper, Defaults $defaults ) {
		parent::__construct( $settings, $query, $helper, $defaults );

		add_action( 'wp_ajax_betterdocs_lazy_category_body',        [ $this, 'ajax_lazy_category_body' ] );
		add_action( 'wp_ajax_nopriv_betterdocs_lazy_category_body', [ $this, 'ajax_lazy_category_body' ] );
	}

	public function get_name() {
		return 'betterdocs_category_grid';
	}

	public function get_style_depends() {
		return [ 'betterdocs-category-grid' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-category-grid' ];
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return [
			'sidebar_list'             => false,
			'taxonomy'                 => 'doc_category',
			'show_icon'                => true,
			'category_icon'            => '',
			'masonry'                  => false,
			'posts_per_page'           => $this->settings->get( 'posts_number', 0 ),
			'orderby'                  => $this->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
			'order'                    => $this->settings->get( 'docs_order', 'ASC' ),
			'show_count'               => $this->settings->get( 'post_count' ),
			'count_suffix'             => '',
			'count_suffix_singular'    => '',
			'column'                   => $this->settings->get( 'column_number' ),
			'nested_subcategory'       => $this->settings->get( 'nested_subcategory' ),
			'terms'                    => '',
			'terms_orderby'            => '',
			'terms_order'              => '',
			'terms_include'            => '',
			'terms_exclude'            => '',
			'terms_offset'             => '',
			'kb_slug'                  => '',
			'multiple_knowledge_base'  => false,
			'disable_customizer_style' => false,
			'title_tag'                => 'h2',
			'category_title_link'      => false,
			'layout_type'              => '',
			'list_icon_url'            => '',
			'list_icon_name'           => 'list',
			'show_list_icon'           => true,
			'sidebar_layout'           => '',
			'lazy_load'                => false,
			'wrapper_class'            => []
		];
	}

	public function generate_attributes() {
		$attributes = [
			'class' => [
				'betterdocs-category-grid-inner-wrapper',
				$this->layout_class
			]
		];

		$masonry = (bool) $this->settings->get( 'masonry_layout', false );
		if ( $this->has( 'masonry' ) ) {
			$masonry = (bool) $this->attributes['masonry'];
		}

		if ( ! is_singular( 'docs' ) && ! is_tax( 'doc_category' ) && ! is_tax( 'doc_tag' ) ) {
			if ( $this->attributes['sidebar_list'] == true ) {
				$attributes['class'][] = 'layout-flex';
			} elseif ( $masonry == true ) {
				wp_enqueue_script( 'masonry' );
				$attributes['class'][] = 'masonry';
			} else {
				$attributes['class'][] = 'layout-flex';
			}
			if ( $this->attributes['sidebar_list'] == true ) {
				$_column_val = 1;
			} elseif ( $this->isset( 'column' ) ) {
				$_column_val = $this->attributes['column'];
			} else {
				$_column_val = $this->settings->get( 'column_number' );
			}

			$attributes['class'][]             = 'docs-col-' . $_column_val;
			$attributes['data-column_desktop'] = esc_html( $_column_val );
			$attributes['style']               = "--column: $_column_val;";

			if ( $this->isset( 'disable_customizer_style', false ) ) {
				$attributes['class'][] = 'single-kb';
			}
		}


		return $attributes;
	}

	public function header_layout_sequence( $sequence, $layout, $widget_type, $args ) {
		return [ 'category_icon', 'category_title', 'category_counts', 'collapse_icon' ];
	}

	public function render( $atts, $content = null ) {
		if ( (bool) $this->attributes['sidebar_list'] ) {
			add_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_layout_sequence' ], 10, 4 );
		}

		$this->views( 'layouts/base' );

		if ( (bool) $this->attributes['sidebar_list'] ) {
			remove_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_layout_sequence' ], 10 );
		}
	}

	public function view_params() {
		$exploremore_btn     = $this->settings->get( 'exploremore_btn' );
		$button_text         = $this->settings->get( 'exploremore_btn_txt' );
		$category_title_link = isset( $this->attributes['category_title_link'] ) ? $this->attributes['category_title_link'] : '';

		$show_button = false;
		if ( $this->attributes['posts_per_page'] == -1 ) {
			$show_button = false;
		} elseif ( $exploremore_btn && ! is_singular( 'docs' ) && Helper::get_tax() != 'doc_category' && ! is_tax( 'doc_tag' ) ) {
			$show_button = true;
		}

		$terms_query = $this->query->terms_query(
			[
				'taxonomy'           => $this->attributes['taxonomy'],
				'multiple_kb'        => ( $this->attributes['multiple_knowledge_base'] && ! empty( $this->attributes['kb_slug'] ) ) ? true : false,
				'kb_slug'            => $this->attributes['kb_slug'],
				'terms'              => $this->attributes['terms'],
				'order'              => $this->attributes['terms_order'],
				'orderby'            => $this->attributes['terms_orderby'],
				'nested_subcategory' => $this->attributes['nested_subcategory']
			]
		);

		if ( $this->attributes['terms_include'] ) {
			$terms_query['include'] = $this->attributes['terms_include'];
		}

		if ( $this->attributes['terms_exclude'] ) {
			$terms_query['exclude'] = $this->attributes['terms_exclude'];
		}

		if ( $this->attributes['terms_offset'] ) {
			$terms_query['offset'] = (int) $this->attributes['terms_offset'];
		}

		$inner_wrapper_attr = $this->generate_attributes();

		$docs_query = [
			'orderby'        => $this->attributes['orderby'],
			'order'          => $this->attributes['order'],
			'posts_per_page' => $this->attributes['posts_per_page']
		];

		/**
		 * Add This Attribute When Using Outside Betterdocs Templates Only
		 */
		if ( $this->attributes['multiple_knowledge_base'] && ( ! empty( $this->attributes['kb_slug'] ) ) && ( ! betterdocs()->helper->is_templates() ) ) {
			$inner_wrapper_attr['data-mkb-slug'] = $this->attributes['kb_slug'];
		}

		// Prepare wrapper attributes with custom classes
		$wrapper_attr_classes = [ 'betterdocs-category-grid-wrapper' ];

		// Add custom wrapper classes if provided
		if ( $this->isset( 'wrapper_class' ) ) {
			$wrapper_classes = $this->attributes['wrapper_class'];

			// Handle both array and string formats
			if ( is_string( $wrapper_classes ) ) {
				// Split by comma or space and clean up
				$wrapper_classes = preg_split( '/[,\s]+/', $wrapper_classes );
				$wrapper_classes = array_filter( array_map( 'trim', $wrapper_classes ) );
			} elseif ( is_array( $wrapper_classes ) ) {
				$wrapper_classes = array_filter( $wrapper_classes );
			}

			if ( ! empty( $wrapper_classes ) ) {
				$wrapper_attr_classes = array_merge( $wrapper_attr_classes, $wrapper_classes );
			}
		}

		return [
			'wrapper_attr'           => [ 'class' => $wrapper_attr_classes ],
			'inner_wrapper_attr'     => $inner_wrapper_attr,
			'layout'                 => 'default',
			'widget_type'            => 'category-grid',
			'terms_query_args'       => $terms_query,
			'docs_query_args'        => $docs_query,
			'nested_docs_query_args' => $docs_query,
			'list_icon_name'         => ($this->attributes['list_icon_name'] ?? 'list') == 'list' ? 'list' : [ 'value' => $this->attributes['list_icon_name'] ?? 'list' ],
			'show_header'            => true,
			'show_list'              => true,
			'show_title'             => true,
			'show_button'            => $show_button,
			'button_text'            => $button_text,
			'show_button_icon'       => true,
			'button_icon_position'   => true,
			'title_tag'              => $this->attributes['title_tag'] ?? 'h2',
			'button_icon'            => true,
			'category_title_link'    => $category_title_link,
			'layout_type'            => $this->attributes['layout_type'] ?? '',
			'list_icon_url'          => $this->attributes['list_icon_url'] ?? '',
			'show_list_icon'         => $this->attributes['show_list_icon'] ?? true,
			'sidebar_layout'         => $this->attributes['sidebar_layout'] ?? '',
			'lazy_load'              => ! empty( $this->attributes['lazy_load'] )
		];
	}

	/**
	 * AJAX handler for lazy-loaded category bodies. Returns the inner HTML of
	 * a single .betterdocs-body (docs list + nested subcategories) for one term.
	 * Reuses the same template (`template-parts/category-list`) that the full
	 * page render uses, which in turn hits the nested-categories fragment cache.
	 *
	 * Public read, intentionally no nonce — matches the SearchForm/SearchModal
	 * nopriv pattern and stays compatible with full-page caching (a per-request
	 * nonce would go stale on cached anonymous pages and silently break the
	 * lazy expand). This endpoint only ever returns data that is already public:
	 * WP_Query runs publish-only and Query::get_doc_ids_by_term applies the same
	 * capability filtering as the on-page render, so private/draft docs never
	 * leak. The response is the published doc titles for a term — the same set a
	 * visitor sees by navigating to that category — so there is nothing to gate
	 * beyond what the normal archive already exposes.
	 */
	public function ajax_lazy_category_body() {
		$term_id       = isset( $_POST['term_id'] ) ? (int) $_POST['term_id'] : 0;
		$kb_slug       = isset( $_POST['kb_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['kb_slug'] ) ) : '';
		$multiple_kb   = ! empty( $_POST['multiple_kb'] ) && ! empty( $kb_slug );
		// category_icon comes from the client when the body is inside a
		// sidebar layout that uses folder icons (Sleek / layout-7). Allowed
		// values match what nested-categories.php branches on.
		$category_icon = '';
		if ( isset( $_POST['category_icon'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['category_icon'] ) );
			if ( in_array( $raw, array( 'folder', 'folder-open' ), true ) ) {
				$category_icon = $raw;
			}
		}

		if ( $term_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'invalid term_id' ], 400 );
		}

		$term = get_term( $term_id, 'doc_category' );
		if ( ! $term instanceof \WP_Term ) {
			wp_send_json_error( [ 'message' => 'term not found' ], 404 );
		}

		$docs_query = [
			'orderby'        => $this->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
			'order'          => $this->settings->get( 'docs_order', 'ASC' ),
			'posts_per_page' => -1,
			'term_id'        => $term->term_id,
			'term_slug'      => $term->slug,
			'multiple_kb'    => $multiple_kb,
			'kb_slug'        => $kb_slug,
		];

		$view_object = betterdocs()->views;

		ob_start();
		$view_object->get(
			'template-parts/category-list',
			[
				'view_object'        => $view_object,
				'term'               => $term,
				'query_args'         => betterdocs()->query->docs_query_args( $docs_query ),
				'nested_subcategory' => (bool) $this->settings->get( 'archive_nested_subcategory' ),
				'nested_docs_query_args' => [
					'multiple_kb'    => $multiple_kb,
					'kb_slug'        => $kb_slug,
					'posts_per_page' => -1,
				],
				'nested_terms_query' => [
					'orderby' => $this->settings->get( 'terms_orderby', 'betterdocs_order' ),
					'order'   => $this->settings->get( 'terms_order', 'ASC' ),
				],
				'show_list_icon'     => true,
				'list_icon_name'     => 'list',
				'list_icon_url'      => '',
				'layout_type'        => '',
				'widget_type'        => 'category-grid',
				'sidebar_layout'     => '',
				'category_icon'      => $category_icon,
				'multiple_knowledge_base' => $multiple_kb,
				'kb_slug'            => $kb_slug,
			]
		);
		$html = ob_get_clean();

		wp_send_json_success( [
			'term_id' => $term->term_id,
			'html'    => $html,
		] );
	}
}
