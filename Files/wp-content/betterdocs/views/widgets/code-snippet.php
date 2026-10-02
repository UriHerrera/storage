<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Template for BetterDocs Code Snippet Widget
 *
 * @var string $code_content
 * @var string $language
 * @var bool $show_copy_button
 * @var bool $show_line_numbers
 * @var string $theme
 * @var string $widget_type
 */

if ( empty( $code_content ) ) {
    return;
}

// Use the template part for consistent rendering (block + Elementor share it).
$view_object->get( 'templates/parts/code-snippet', [
    'code_content'        => $code_content,
    'language'            => $language,
    'code_variants'       => isset( $code_variants ) ? $code_variants : [],
    'show_copy_button'    => $show_copy_button,
    'show_header'         => isset( $show_header ) ? $show_header : true,
    'show_line_numbers'   => $show_line_numbers,
    'show_language_label' => isset( $show_language_label ) ? $show_language_label : true,
    'theme'               => $theme,
    'widget_type'         => $widget_type,
    'file_name'           => isset( $file_name ) ? $file_name : '',
    'show_traffic_lights' => isset( $show_traffic_lights ) ? $show_traffic_lights : true,
    'show_file_icon'      => isset( $show_file_icon ) ? $show_file_icon : true,
    'file_icon'           => isset( $file_icon ) ? $file_icon : '',
] );
