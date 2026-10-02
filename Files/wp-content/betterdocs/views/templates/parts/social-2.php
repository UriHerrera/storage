<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$mods = betterdocs()->customizer->defaults->generate_defaults();

if ( ! $mods['betterdocs_post_social_share'] ) {
	return;
}

$social_sharing_text = $mods['betterdocs_social_sharing_text'];
$social_sharing_title_tag = $mods['betterdocs_social_share_title_tag'];
$facebook_sharing    = $mods['betterdocs_post_social_share_facebook'];
$twitter_sharing     = $mods['betterdocs_post_social_share_twitter'];
$linkedin_sharing    = $mods['betterdocs_post_social_share_linkedin'];
$pinterest_sharing   = $mods['betterdocs_post_social_share_pinterest'];

$attributes = betterdocs()->template_helper->get_html_attributes(
	[
		'layout'    => 'layout-2',
		'title'     => "{$social_sharing_text}",
		'title_tag' => "{$social_sharing_title_tag}",
		'facebook'  => "{$facebook_sharing}",
		'twitter'   => "{$twitter_sharing}",
		'linkedin'  => "{$linkedin_sharing}",
		'pinterest' => "{$pinterest_sharing}"
	]
);

echo do_shortcode( '[betterdocs_social_share ' . $attributes . ']' );
