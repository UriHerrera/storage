<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$shortcode_atts = betterdocs()->template_helper->shortcode_atts( [
    'title'      => $title,
    'show_title' => $show_title,
    'layout'     => $layout
], '', '' );

echo '<div class="betterdocs-related-articles-root '.esc_attr( $blockId ).'">'.do_shortcode( "[betterdocs_related_docs $shortcode_atts]" ).'</div>';
