<?php


if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// echo '<div class="letter-start alphabet-big-view" data-scroll-letter="' . $letter . '">' . $letter . '</div>';

// echo '<div class="letter-start alphabet-big-round-view" data-scroll-letter="' . $letter . '">' . $letter . '</div>';

// echo '<div class="letter-start alphabet-big-gradient-view" data-scroll-letter="' . $letter . '">' . $letter . '</div>';

echo '<div class="letter-start ' . esc_attr( $start_letter_style ) . '" data-scroll-letter="' . esc_attr( $letter ) . '"><span>' . esc_html( $letter ) . '</span></div>';
