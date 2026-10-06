<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use WPDeveloper\BetterDocs\Utils\Helper;

$_defined_vars = get_defined_vars();
$_params       = isset( $_defined_vars['params'] ) ? $_defined_vars['params'] : [];
$pagination    = isset( $_params['pagination'] ) ? $_params['pagination'] : false;

if ( isset( $archive_layout ) && $archive_layout == 'layout-1' ) {
	$view_object->get( 'template-parts/category-list', $_params );
} elseif ( isset( $archive_layout ) && $archive_layout == 'layout-3' ) {
	$post_query = new WP_Query( $_params['query_args'] );
	$view_object->get(
		'template-parts/archive-doc-list-2',
		[
			'current_category' => $_params['term'],
			'post_query'       => $post_query,
			'docs_list_title_tag' => isset( $_params['docs_list_title_tag'] ) ? $_params['docs_list_title_tag'] : 'h2'
		]
	);
	// Nested subcategories — emitted here (not inside the shared leaf template,
	// which the block and Customizer archive layouts also use) so it stays
	// scoped to this widget. Sibling after the grid, so it is not a grid cell.
	$view_object->get( 'template-parts/archive-nested-categories', $_params );
} elseif ( isset( $_params['query_args'] ) ) {
	$post_query = new WP_Query( $_params['query_args'] );
	$view_object->get(
		'template-parts/archive-doc-list',
		[
			'post_query'          => $post_query,
			'docs_list_title_tag' => isset( $_params['docs_list_title_tag'] ) ? $_params['docs_list_title_tag'] : 'h2'
		]
	);
	// Nested subcategories for Layout 2 (scoped to this widget — see above).
	$view_object->get( 'template-parts/archive-nested-categories', $_params );
}

if ( isset( $pagination ) && $pagination ) {
	$current_term = isset( $edit_mode ) && $edit_mode && isset( $widget_type ) && $widget_type == 'elementor' ? Helper::get_highest_docs_term() : ( isset( $_params['term'] ) ? $_params['term'] : '' );
	$query_args   = isset( $_params['query_args'] ) ? $_params['query_args'] : [];
	$post_query   = new WP_Query( $query_args );
	$posts_per_page = isset( $_params['posts_per_page'] ) ? $_params['posts_per_page'] : 10;
	$total_pages  = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / $posts_per_page );

	$link = ! is_wp_error( get_term_link( $current_term, 'doc_category' ) ) ? get_term_link( $current_term, 'doc_category' ) : ( get_the_ID() != false ? get_permalink( get_the_ID() ) : '' );

	$view_object->get(
		'template-parts/pagination',
		[
			'total_pages'  => $total_pages,
			'link'         => $link,
			'current_page' => isset( $_params['page'] ) ? $_params['page'] : 1,
			'template'     => 'doc_category'
		]
	);
}
