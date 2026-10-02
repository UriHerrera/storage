<?php
    
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( $show_term_image == false ) return;

    echo '<div class="betterdocs-term-img">';
    $cat_image_id = get_term_meta( $term->term_id, 'doc_category_thumb-id', true );
    $image_size = isset( $image_size ) ? $image_size : 'full';
    $attr = [
        'alt'   => 'betterdocs-category-image',
        'class' => ['betterdocs-category-thumb-image']
    ];

    if ( $cat_image_id ) {
        $icon_url    = wp_get_attachment_image_url( $cat_image_id, $image_size );
        $attr['alt'] = get_post_meta( $cat_image_id, '_wp_attachment_image_alt', true );
    } else {
        $icon_url = betterdocs_pro()->assets->icon( $image_size . '-default.png', true );
    }

    $attr['src']      = esc_url( $icon_url );
    $image_attributes = betterdocs()->template_helper->get_html_attributes( $attr );

    echo wp_kses_post( '<img ' . $image_attributes . ' />' );
    echo '</div>';
?>

