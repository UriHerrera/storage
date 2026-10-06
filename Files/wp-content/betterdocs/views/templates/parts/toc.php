<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! betterdocs()->settings->get( 'enable_toc', false ) ) {
	return;
}

// Check if post is password protected and user hasn't provided correct password
// This applies to all contexts: templates, Gutenberg blocks, and Elementor widgets
if ( post_password_required() ) {
	// Don't show ToC for password-protected posts until password is provided
	return;
}

$toc_hierarchy          = betterdocs()->settings->get( 'toc_hierarchy' );
$toc_list_number        = betterdocs()->settings->get( 'toc_list_number' );
$collapsible_toc_mobile = betterdocs()->settings->get( 'collapsible_toc_mobile' );
$supported_tag          = betterdocs()->settings->get( 'supported_heading_tag', '' );
$htags                  = $supported_tag ? implode( ',', $supported_tag ) : '';

$attributes = betterdocs()->template_helper->get_html_attributes(
	[
		'htags'                 => "{$htags}",
		'hierarchy'             => "{$toc_hierarchy}",
		'list_number'           => "{$toc_list_number}",
		'collapsible_on_mobile' => "{$collapsible_toc_mobile}"
	]
);

echo do_shortcode( '[betterdocs_toc ' . $attributes . ']' );
