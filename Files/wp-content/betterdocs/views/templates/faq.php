<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$mods      = betterdocs()->customizer->defaults->generate_defaults();
$query     = new WP_Query(
	[
		'post_type'   => 'betterdocs_faq',
		'post_status' => 'publish'
	]
);
$faq_terms = get_terms( betterdocs()->query->faq_terms_query_args() );

betterdocs()->views->get(
	'layouts/faq',
	[
		'enable'         => $mods['betterdocs_faq_switch'],
		'have_posts'     => $query->have_posts(),
		'layout'         => $mods['betterdocs_select_faq_template'],
		'shortcode_attr' => [
			'groups'                => $mods['betterdocs_select_specific_faq'],
			'class'                 => 'faq-doc betterdocs-faq-' . $mods['betterdocs_select_faq_template'],
			'faq_heading'           => $mods['betterdocs_faq_title_text'],
			'faq_section_title_tag' => $mods['betterdocs_faq_section_title_tag'],
			'faq_group_title_tag'   => $mods['betterdocs_faq_group_title_tag']
		]
	]
);
