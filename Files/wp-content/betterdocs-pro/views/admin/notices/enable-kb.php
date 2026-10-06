<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
printf(
    '<p class="betterdocs-alert betterdocs-alert-warning elementor-alert elementor-alert-warning">%1$s <strong>%2$s</strong> %3$s <strong>%4$s</strong></p>.',
    esc_html__( 'Whoops! It seems like you have the', 'betterdocs-pro' ),
    esc_html__( '‘Multiple Knowledge Base’', 'betterdocs-pro' ),
    esc_html__( 'option disabled. Make sure to enable this option from your', 'betterdocs-pro' ),
    esc_html__( 'WordPress Dashboard -> BetterDocs -> Settings -> General.', 'betterdocs-pro' )
);
