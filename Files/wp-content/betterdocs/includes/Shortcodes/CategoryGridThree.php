<?php
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- shortcode exposes user-driven exclusion attribute.
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Shortcode;

class CategoryGridThree extends Shortcode {
	protected $layout_class = 'layout-3';

	public function get_name() {
		return 'betterdocs_category_grid_3';
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
			'category_icon'            => 'folder',
			'masonry'                  => false,
			'posts_per_page'           => $this->settings->get( 'posts_number', 0 ),
			'orderby'                  => $this->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
			'order'                    => $this->settings->get( 'docs_order', 'ASC' ),
			'show_count'               => false,
			'count_suffix'             => '',
			'count_suffix_singular'    => '',
			'column'                   => 4, //$this->settings->get( 'column_number' ),
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
			'category_title_link'      => true,
			'layout_type'              => '',
			'list_icon_url'            => false,
		];
	}

	public function generate_attributes() {
		$attributes = [
			'class' => [
				'betterdocs-category-grid-inner-wrapper',
				$this->layout_class
			]
		];

		if ( ! is_singular( 'docs' ) && ! is_tax( 'doc_category' ) && ! is_tax( 'doc_tag' ) ) {
			$attributes['class'][] = 'layout-flex';

			if ( $this->isset( 'column' ) ) {
				$_column_val = $this->attributes['column'];
			} else {
				$_column_val = $this->settings->get( 'column_number' );
			}

			$attributes['class'][]             = 'docs-col-' . $_column_val;
			$attributes['data-column_desktop'] = esc_html( $_column_val );
			$attributes['style']               = "--column: $_column_val;";
		}

		return $attributes;
	}

	public function header_layout_sequence( $sequence, $layout, $widget_type, $args ) {
		return [ 'category_icon', 'category_title' ];
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
		$category_title_link = isset( $this->attributes['category_title_link'] ) ? $this->attributes['category_title_link'] : '';

		$terms_query = $this->query->terms_query(
			[
				'taxonomy'           => $this->attributes['taxonomy'],
				'multiple_kb'        => ( $this->attributes['multiple_knowledge_base'] && ! empty( $this->attributes['kb_slug'] ) ) ? true : false,
				'kb_slug'            => $this->attributes['kb_slug'],
				'terms'              => $this->attributes['terms'],
				'order'              => $this->attributes['terms_order'],
				'orderby'            => $this->attributes['terms_orderby']
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

		return [
			'wrapper_attr'           => [ 'class' => [ 'betterdocs-category-grid-three-wrapper' ] ],
			'inner_wrapper_attr'     => $inner_wrapper_attr,
			'layout'                 => 'layout-3',
			'widget_type'            => 'category-grid',
			'terms_query_args'       => $terms_query,
			'docs_query_args'        => $docs_query,
			'show_header'            => true,
			'show_list'              => true,
			'show_title'             => true,
			'show_button'            => false,
			'button_text'            => '',
			'category_title_link'    => $category_title_link,
			'layout_type'            => $this->attributes['layout_type'],
			'list_icon_url'          => $this->attributes['list_icon_url'],
		];
	}
}
