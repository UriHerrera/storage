<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$mods      = betterdocs()->customizer->defaults->generate_defaults();
$query     = new WP_Query( ['post_type' => 'betterdocs_faq', 'post_status' => 'publish'] );
$faq_terms = get_terms( betterdocs()->query->faq_terms_query_args() );

betterdocs()->views->get( 'layouts/faq', [
    'enable'         => $mods['betterdocs_faq_switch_mkb'],
    'have_posts'     => $query->have_posts(),
    'layout'         => $mods['betterdocs_select_faq_template_mkb'],
    'shortcode_attr' => [
        'groups'                => $mods['betterdocs_select_specific_faq_mkb'],
        'class'                 => 'faq-mkb betterdocs-faq-' . $mods['betterdocs_select_faq_template_mkb'],
        'faq_heading'           => $mods['betterdocs_faq_title_text_mkb'],
        'faq_section_title_tag' => $mods['betterdocs_faq_section_title_tag_mkb'],
        'faq_group_title_tag'   => $mods['betterdocs_faq_group_title_tag_mkb']
    ]
] );
