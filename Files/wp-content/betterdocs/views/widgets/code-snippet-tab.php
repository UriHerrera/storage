<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Template for BetterDocs Code Snippet Tab Widget (Elementor).
 *
 * @var array  $responses
 * @var bool   $show_copy_button
 * @var bool   $show_line_numbers
 * @var string $theme
 * @var string $widget_type
 */

if ( empty( $responses ) ) {
    return;
}

// Use the template part for consistent rendering (block + Elementor share it).
$view_object->get( 'templates/parts/code-snippet-tab', [
    'responses'         => isset( $responses ) ? $responses : [],
    'show_copy_button'  => $show_copy_button,
    'show_header'       => isset( $show_header ) ? $show_header : true,
    'show_line_numbers' => $show_line_numbers,
    'theme'             => $theme,
    'widget_type'       => $widget_type
] );
