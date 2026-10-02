<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Shortcodes\CategoryBox;

class CategoryBoxThree extends CategoryBox {
	protected $layout_class = 'layout-4';

	/**
	 * Summary of get_id
	 * @return string
	 */
	public function get_name() {
		return 'betterdocs_category_box_3';
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return [
			'taxonomy'                 => 'doc_category',
			'column'                   => $this->settings->get( 'column_number', 4 ),
			'nested_subcategory'       => $this->settings->get( 'nested_subcategory', false ),
			'terms'                    => '',
			'terms_order'              => $this->settings->get( 'terms_order', 'ASC' ),
			'terms_orderby'            => $this->settings->get( 'terms_orderby', 'betterdocs_order' ),
			'kb_slug'                  => '',
			'multiple_knowledge_base'  => false,
			'disable_customizer_style' => false,
			'title_tag'                => 'h2',
			'show_description'         => false,
			'show_icon'                => true,
			'category_icon'            => 'folder',
			'last_update'              => '',
		];
	}

	public function header_sequence( $_layout_sequence, $layout, $widget_type, $_defined_vars ) {
		$_new_layout_sequence = [
			'category_icon',
			[
				'class'    => 'betterdocs-category-title-counts',
				'sequence' => [ 'category_title', 'category_description', 'sub_category_counts', 'last_update' ]
			]
		];

		return $_new_layout_sequence;
	}

	/**
	 * Summary of render
	 *
	 * @param mixed $atts
	 * @param mixed $content
	 * @return mixed
	 */
	public function render( $atts, $content = null ) {
		parent::render( $atts, $content );
	}

	public function view_params() {
		$parent_params = parent::view_params();
		$query_arg     = $parent_params['terms_query_args'];

		// get_terms() returns a WP_Error for an invalid taxonomy, and the taxonomy
		// is a shortcode attribute the author controls — [betterdocs_category_box_3
		// taxonomy=nope] reached count(WP_Error), which is a TypeError on PHP 8 and
		// takes the whole page down with a 500 for anonymous visitors.
		$_terms     = get_terms( $query_arg );
		$term_count = is_wp_error( $_terms ) ? 0 : count( $_terms );
		$styles     = '';

		if ( $this->isset( 'column' ) ) {
			$_c = intval( $this->attributes['column'] );
		} else {
			$_c = 4;
		}

		if ( is_tax( 'doc_category' ) && ! $this->isset( 'column' ) ) {
			$_c = 3;
		}

		$styles  .= "--column: $_c;";
		$styles  .= "--count: $term_count;";
		$reminder = $term_count % $_c;
		$styles  .= "--reminder: $reminder;";

		$classes = [ 'betterdocs-categories-folder', $this->layout_class ];

		if ( $this->isset( 'border_bottom', true ) ) {
			$classes[] = 'border-bottom';
		}

		if ( $this->isset( 'disable_customizer_style', false ) ) {
			$classes[] = 'single-kb';
		}

		$inner_wrapper_attr = [
			'class' => $classes,
			'style' => $styles,
		];

		$_view_params = [
			'layout'             => 'layout-4',
			'reminder'           => $reminder,
			'column'             => $_c,
			'total_terms'        => $term_count,
			'inner_wrapper_attr' => $inner_wrapper_attr,
			'show_icon'          => $this->attributes['show_icon']
		];

		return $this->merge( $parent_params, $_view_params );
	}
}
