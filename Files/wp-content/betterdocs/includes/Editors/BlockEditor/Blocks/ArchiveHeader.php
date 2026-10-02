<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class ArchiveHeader extends Block {

	protected $editor_styles = [ 'betterdocs-category-archive-header' ];

	protected $frontend_styles = [ 'betterdocs-category-archive-header', 'betterdocs-doc_category' ];

	public function get_name() {
		return 'archive-header';
	}

	public function get_default_attributes() {
		return [
			'blockId'  => '',
			'titleTag' => 'h2'
		];
	}

	public function render( $attributes, $content ) {
		$this->views( 'widgets/archive-header' );
	}

	public function view_params() {
		$current_category = get_queried_object();

		$args = betterdocs()->query->docs_query_args(
			[
				'term_id'        => isset( $current_category->term_id ) ? $current_category->term_id : '',
				'term_slug'      => isset( $current_category->slug ) ? $current_category->slug : '',
				'posts_per_page' => -1
			]
		);

		$post_query = new \WP_Query( $args );

		$_nested_categories = betterdocs()->query->get_child_term_ids_by_parent_id( 'doc_category', ( isset( $current_category->term_id ) ? $current_category->term_id : '' ) );

		if ( $_nested_categories ) {
			$sub_terms_count = count( explode( ',', $_nested_categories ) );
		} else {
			$sub_terms_count = 0;
		}

		return [
			'current_category' => $current_category,
			'found_posts'      => $post_query->found_posts,
			'show_count'       => true,
			'title_tag'        => $this->attributes['titleTag'],
			'show_icon'        => true,
			'sub_terms_count'  => $sub_terms_count,
		];
	}
}
