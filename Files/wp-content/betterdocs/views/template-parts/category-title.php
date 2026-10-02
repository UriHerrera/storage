<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$tag                 = betterdocs()->template_helper->is_valid_tag( $tag );
$category_title_link = isset( $category_title_link ) ? $category_title_link : '';
if ( isset( $widget_type ) && ( $widget_type !== 'category-box' ) && ( $widget_type == 'category-grid' && $category_title_link ) ) {
	$title = wp_sprintf( '<a href="%s">%s</a>', $permalink, $title );
}

//for related categories title
if ( isset( $widget_type ) && ( $widget_type == 'related-categories' ) ) {
	$title = wp_sprintf( '<a href="%s">%s</a>', $permalink, $title );
}

echo wp_kses_post( '<' . $tag . ' class="betterdocs-category-title">' . $title . '</' . $tag . '>' );
