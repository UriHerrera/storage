<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Core\Shortcode;

class CategoryList extends Shortcode {
	public function get_name() {
		return 'betterdocs_category_list';
	}

	public function get_style_depends() {
		return [ 'betterdocs-category-grid-list' ];
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
			'post_type'               => 'docs',
			'category'                => 'doc_category',
			'orderby'                 => $this->settings->get( 'alphabetically_order_post' ),
			'order'                   => $this->settings->get( 'docs_order' ),
			'masonry'                 => '',
			'column'                  => '',
			'posts_per_page'          => -1,
			'nested_subcategory'      => '',
			'terms'                   => '',
			'terms_orderby'           => '',
			'terms_order'             => '',
			'kb_slug'                 => '',
			'multiple_knowledge_base' => false,
			'title_tag'               => 'h2',
			'layout_type'             => '',
			'list_icon_url'           => '',
			'sidebar_layout'          => '',
			'list_icon_name'          => '',
			'lazy_load'               => false
		];
	}

	public function view_params() {
		$terms_query = $this->query->terms_query(
			[
				'multiple_kb'        => ( $this->attributes['multiple_knowledge_base'] && ! empty( $this->attributes['kb_slug'] ) ) ? true : false,
				'kb_slug'            => $this->attributes['kb_slug'],
				'terms'              => $this->attributes['terms'],
				'order'              => $this->attributes['terms_order'],
				'orderby'            => $this->attributes['terms_orderby'],
				'nested_subcategory' => (bool) $this->attributes['nested_subcategory']
			]
		);

		$docs_query = [
			'orderby'        => $this->attributes['orderby'],
			'order'          => $this->attributes['order'],
			'posts_per_page' => $this->attributes['posts_per_page']
		];

		return [
			'wrapper_attr'          => [ 'class' => [ 'betterdocs-category-list-wrapper' ] ],
			'inner_wrapper_attr'    => [ 'class' => [ 'betterdocs-category-list-inner-wrapper' ] ],
			'is_edit_mode'          => false,
			'widget'                => $this,
			'layout'                => 'default',

			'default_multiple_kb'   => false,
			'terms_query_args'      => $terms_query,
			'docs_query_args'       => $docs_query,
			'widget_type'           => 'category-grid',

			'show_header'           => true,
			'show_list'             => true,
			'list_icon'             => true,

			'show_button'           => false,

			'show_count'            => false,
			'count_prefix'          => '',
			'count_suffix_singular' => '',
			'count_suffix'          => '',
			'show_title'            => true,
			'show_icon'             => false,
			'layout_type'           => $this->attributes['layout_type'],
			'list_icon_url'         => $this->attributes['list_icon_url'],
			'sidebar_layout'        => $this->attributes['sidebar_layout'],
			'list_icon_name'        => $this->attributes['list_icon_name'],
			'title_tag'             => $this->attributes['title_tag'],
			'lazy_load'             => ! empty( $this->attributes['lazy_load'] )
		];
	}

	public function render( $atts, $content = null ) {
		$this->views( 'layouts/base' );
	}
}
