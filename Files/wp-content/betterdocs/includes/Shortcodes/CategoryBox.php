<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- ordering by user-assigned meta is required UX.
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Core\Shortcode;

class CategoryBox extends Shortcode {
	protected $layout_class          = 'layout-1';
	protected $deprecated_attributes = [
		'category' => 'taxonomy',
		'icon'     => 'show_icon'
	];

	public function get_name() {
		return 'betterdocs_category_box';
	}

	public function get_style_depends() {
		return [ 'betterdocs-category-box' ];
	}

	public function default_attributes() {
		return [
			'taxonomy'                 => 'doc_category',
			'parent'                   => 0,
			'meta_key'                 => '',
			'layout'                   => '',
			'column'                   => $this->settings->get( 'column_number', 3 ),
			'orderby'                  => $this->settings->get( 'alphabetically_order_post', 'title' ),
			'nested_subcategory'       => (bool) $this->settings->get( 'nested_subcategory', false ),
			'terms'                    => '',
			'terms_order'              => $this->settings->get( 'terms_order', 'ASC' ),
			'terms_orderby'            => $this->settings->get( 'terms_orderby', 'betterdocs_order' ),
			'show_icon'                => true,
			'kb_slug'                  => '',
			'title_tag'                => 'h2',
			'multiple_knowledge_base'  => false,
			'disable_customizer_style' => false,
			'border_bottom'            => false,
			'show_description'         => (bool) $this->customizer->get( 'betterdocs_doc_page_cat_desc', false )
		];
	}

	public function header_sequence( $_layout_sequence, $layout, $widget_type, $_defined_vars ) {
		return [ 'category_icon', 'category_title', 'category_description', 'category_counts' ];
	}

	public function render( $atts, $content = null ) {
		add_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_sequence' ], 10, 4 );

		$this->views( 'layouts/base' );

		remove_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_sequence' ], 10 );
	}

	public function view_params() {
		$classes = [ 'betterdocs-category-box-inner-wrapper', $this->layout_class, ( $this->layout_class != 'layout-4' ? 'layout-flex ash-bg' : '' ) ];
		$styles  = '';

		if ( $this->isset( 'column' ) ) {
			$_c        = intval( $this->attributes['column'] );
			$classes[] = 'docs-col-' . $_c;
			$styles   .= "--column: $_c;";
		}

		if ( $this->isset( 'border_bottom', true ) ) {
			$classes[] = 'border-bottom';
		}

		if ( $this->isset( 'disable_customizer_style', false ) ) {
			$classes[] = 'single-kb';
		}

		$_query_args = [
			'terms'              => $this->attributes['terms'],
			'order'              => $this->attributes['terms_order'],
			'orderby'            => $this->attributes['terms_orderby'],
			'multiple_kb'        => ( $this->attributes['multiple_knowledge_base'] && ! empty( $this->attributes['kb_slug'] ) ) ? true : false,
			'kb_slug'            => $this->attributes['kb_slug'],
			'nested_subcategory' => (bool) $this->attributes['nested_subcategory']
		];

		if ( $this->isset( 'taxonomy' ) ) {
			$_query_args['taxonomy'] = $this->attributes['taxonomy'];
		}

		// if ( $this->isset( 'parent', 0 ) ) {
		//     $_query_args['parent'] = $this->attributes['parent'];
		// }

		if ( $this->isset( 'meta_key' ) ) {
			$_query_args['meta_key'] = $this->attributes['meta_key'];
		}

		$terms_query = $this->query->terms_query( $_query_args );

		$show_count    = (bool) $this->settings->get( 'post_count', false );
		$singular_text = $this->settings->get( 'count_text_singular' );
		$plural_text   = $this->settings->get( 'count_text' );

		$inner_wrapper_attr = [
			'class' => $classes,
			'style' => $styles,
		];

		return [
			'wrapper_attr'          => [ 'class' => [ 'betterdocs-category-box-wrapper' ] ],
			'inner_wrapper_attr'    => $inner_wrapper_attr,
			'layout'                => 'default',
			'widget_type'           => 'category-box',

			'terms_query_args'      => $terms_query,

			'show_title'            => true,
			'show_count'            => $show_count,
			'count_suffix_singular' => $singular_text,
			'count_suffix'          => $plural_text,
			'title_tag'             => $this->attributes['title_tag']
		];
	}
}
