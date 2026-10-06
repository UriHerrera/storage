<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- core block query; tax filtering required.
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class ArchiveList extends Block {

	public $tax_query_block = [];

	public function get_name() {
		return 'doc-archive-list';
	}

	protected $editor_styles = [
		'betterdocs-fontawesome',
		'betterdocs-blocks-editor',
		'betterdocs-doc-archive-list',
		'betterdocs-doc_category',
		'betterdocs-category-archive-doc-list',
		'betterdocs-pagination'
	];

	protected $frontend_styles = [
		'betterdocs-fontawesome',
		'betterdocs-doc-archive-list',
		'betterdocs-doc_category',
		'betterdocs-category-archive-doc-list',
		'betterdocs-pagination'
	];

	public function get_default_attributes() {
		return [
			'blockId'               => '',
			'nested_subcategory'    => false,
			'order'                 => 'asc',
			'orderby'               => 'betterdocs_order',
			'layout'                => 'layout-1',
			'list_icon'             => 'far fa-file-alt',
			'postsPerPageLayoutTwo' => -1,
			'listIconImageUrl'      => '',
			'pagination'            => false,
			'listTitleTag'          => 'h2',
			'listEntryHeadingTag'   => 'h3'
		];
	}

	public function render( $attributes, $content ) {
		if ( $this->attributes['layout'] == 'layout-2' ) {
			// Layout 2 is handled by Pro version
			add_action( 'archive_handbook_list', [$this, 'render_handbook_view'] );
		}
		$this->views( 'widgets/block-archive-list' );
	}

	public function render_handbook_view() {
		// This method can be overridden by Pro version
	}

	public function view_params() {
		// Sanitize the layout attribute to prevent path traversal (LFI).
		$this->attributes['layout'] = sanitize_file_name( $this->attributes['layout'] );

		global $wp_query;
		$_term_slug = '';
		if ( isset( $wp_query->query ) && array_key_exists( 'doc_category', $wp_query->query ) ) {
			$_term_slug = $wp_query->query['doc_category'];
		}

		if ( isset( $wp_query->query ) && array_key_exists( 'doc_tag', $wp_query->query ) ) {
			$_term_slug = $wp_query->query['doc_tag'];
			add_filter(
				'betterdocs_docs_tax_query_args',
				function ( $tax_query, $_multiple_kb, $_term_slug, $_kb_slug, $_origin_args ) {
					$tax_query[0]['taxonomy'] = 'doc_tag';
					unset( $tax_query[0]['operator'] );
					unset( $tax_query[0]['include_children'] );
					$this->tax_query_block = $tax_query;
					return $tax_query;
				},
				10,
				5
			);
			add_filter(
				'betterdocs_articles_args',
				function ( $args, $_term_id, $_origin_args ) {
					if ( empty( $args['tax_query'] ) ) {
						$args['tax_query'] = $this->tax_query_block;
					}
					return $args;
				},
				10,
				3
			);
		}

		$term = ! empty( get_term_by( 'slug', $_term_slug, 'doc_category' ) ) ? get_term_by( 'slug', $_term_slug, 'doc_category' ) : get_term_by( 'slug', $_term_slug, 'doc_tag' );

		// Determine posts per page based on pagination settings
		$posts_per_page = -1; // default: show all posts
		if ( $this->attributes['pagination'] && ( $this->attributes['layout'] == 'layout-1' || $this->attributes['layout'] == 'layout-3' || $this->attributes['layout'] == 'layout-4' ) ) {
			$posts_per_page = isset( $this->attributes['postsPerPageLayoutTwo'] ) && $this->attributes['postsPerPageLayoutTwo'] > 0
				? $this->attributes['postsPerPageLayoutTwo']
				: 10;
		}

		$_docs_query = [
			'term_id'        => isset( $term->term_id ) ? $term->term_id : 0,
			'orderby'        => $this->attributes['orderby'],
			'order'          => $this->attributes['order'],
			'kb_slug'        => '',
			'posts_per_page' => $posts_per_page,
			'term_slug'      => isset( $term->slug ) ? $term->slug : ''
		];

		$default_params = [
			'term'               => $term,
			'nested_subcategory' => (bool) $this->attributes['nested_subcategory'],
			'list_icon_name'     => ! empty( $this->attributes['listIconImageUrl'] ) ? [ 'value' => [ 'url' => str_replace( 'blob:', '', $this->attributes['listIconImageUrl'] ) ] ] : ( ! empty( $this->attributes['list_icon'] ) ? [ 'value' => [ 'url' => $this->attributes['list_icon'] ] ] : ( ! empty( betterdocs()->settings->get( 'docs_list_icon' ) ) ? [ 'value' => [ 'url' => betterdocs()->settings->get( 'docs_list_icon' )['url'] ] ] : [] ) ),
			'query_args'         => betterdocs()->query->docs_query_args( $_docs_query ),
			'title_tag'           => $this->attributes['listEntryHeadingTag'] ?? 'h2',
			'docs_list_title_tag' => $this->attributes['listTitleTag'] ?? 'h2',
			'layout'             => $this->attributes['layout'],
			'posts_per_page'     => $posts_per_page,
			'list_icon_url'      => '',
			'layout_type'        => 'block',
			'archive_layout'     => $this->attributes['layout']
		];

		if ( $this->attributes['pagination'] && ( $this->attributes['layout'] == 'layout-1' || $this->attributes['layout'] == 'layout-3' || $this->attributes['layout'] == 'layout-4' ) ) {
			$page = get_query_var( 'paged' ) != '' ? get_query_var( 'paged' ) : 1;

			// Use postsPerPageLayoutTwo for layouts 2, 3, and 4
			$posts_per_page = isset( $this->attributes['postsPerPageLayoutTwo'] ) && $this->attributes['postsPerPageLayoutTwo'] > 0
				? $this->attributes['postsPerPageLayoutTwo']
				: 10;

			$default_params['query_args']['paged']          = $page;
			$default_params['query_args']['posts_per_page'] = $posts_per_page;
			$default_params['page']                         = $page;
			$default_params['posts_per_page']               = $posts_per_page;
			$default_params['pagination']                   = $this->attributes['pagination'];
		}

		return $default_params;
	}
}
