<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( empty( $description ) ) {
    return;
}

$extra_class = isset( $widget_type ) && $widget_type == 'blocks' ? ' ' . $blockId : '';
echo '<div class="term-description' . esc_attr( $extra_class ) . '">' . wp_kses_post( wpautop( $description ) ) . '</div>';
