<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$_faq_taxonomy = isset( $shortcode_attr['faq_taxonomy'] ) ? sanitize_key( $shortcode_attr['faq_taxonomy'] ) : 'betterdocs_faq_category';
$faq_terms     = get_terms( betterdocs()->query->faq_terms_query_args( '', '', [], $_faq_taxonomy ) );

if ( $enable && $have_posts && ! empty( $faq_terms ) ) {
	// Only use settings value if faq_schema is not already set in shortcode attributes
	if ( ! isset( $shortcode_attr['faq_schema'] ) ) {
		$enable_faq_schema            = betterdocs()->settings->get( 'enable_faq_schema' );
		$shortcode_attr['faq_schema'] = $enable_faq_schema;
	}

	$attributes = betterdocs()->template_helper->get_html_attributes( $shortcode_attr );

	if ( $layout === 'layout-1' ) {
		echo do_shortcode( '[betterdocs_faq_list_modern ' . $attributes . ']' );
	} elseif ( $layout === 'layout-2' ) {
		echo do_shortcode( '[betterdocs_faq_list_classic ' . $attributes . ']' );
	} elseif ( $layout === 'layout-3' ) {
		echo do_shortcode( '[betterdocs_faq_list_layout_3 ' . $attributes . ']' );
	} elseif ( $layout === 'layout-4' ) {
		echo do_shortcode( '[betterdocs_faq_tab ' . $attributes . ']' );
	}
}
