<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$tag                 = betterdocs()->template_helper->is_valid_tag( $tag );
//for related categories title
if ( isset( $widget_type ) && ( $widget_type == 'related-categories' ) ) {
	$title = wp_sprintf( '<a href="%s">%s</a>', $permalink, $title );
}

echo wp_kses_post( '<' . $tag . ' class="betterdocs-category-title">' . $title . '</' . $tag . '>' );
