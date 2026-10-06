<?php
/**
 * Nested subcategory renderer for the Doc Category Archive List widget's
 * Layout 2 and Layout 3.
 *
 * Mirrors the Layout 1 call in template-parts/category-list.php, but buffers
 * the output so the load-bearing `betterdocs-articles-list` wrapper — the
 * stylesheet hides the collapsed arrow via
 * `.betterdocs-articles-list .betterdocs-nested-category-title svg.arrow-down`
 * — is emitted only when there is something to render. template-parts/
 * nested-categories returns early for a term with no renderable children,
 * which would otherwise leave a stray empty <ul> carrying list spacing on
 * every childless category.
 *
 * @var bool          $nested_subcategory
 * @var \WP_Term|null  $current_category
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $nested_subcategory ) ) {
	return;
}

$bd_parent_term = isset( $current_category ) ? $current_category : ( isset( $term ) ? $term : null );
if ( ! ( $bd_parent_term instanceof \WP_Term ) ) {
	return;
}

// Forward the accumulated params to the shared partial, exactly like
// template-parts/category-list.php does for Layout 1.
$_defined = get_defined_vars();
$_params  = isset( $_defined['params'] ) ? $_defined['params'] : [];
$_params  = wp_parse_args(
	[
		'term'          => $bd_parent_term,
		'term_id'       => $bd_parent_term->term_id,
		'list_icon_url' => isset( $list_icon_url ) ? $list_icon_url : '',
	],
	$_params
);
if ( isset( $layout_type ) && ( 'widget' === $layout_type || 'block' === $layout_type ) ) {
	$_params['list_icon_name'] = isset( $list_icon_name ) ? $list_icon_name : '';
}

ob_start();
$view_object->get( 'template-parts/nested-categories', $_params );
$bd_nested_html = trim( ob_get_clean() );

if ( '' !== $bd_nested_html ) {
	// The inner markup is already escaped by template-parts/nested-categories.
	echo '<ul class="betterdocs-articles-list betterdocs-archive-nested-list">' . $bd_nested_html . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
