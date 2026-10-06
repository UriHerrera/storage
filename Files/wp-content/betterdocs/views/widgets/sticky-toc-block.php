<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo '<div class="sticky-toc-block-wrapper ' . esc_attr( $blockId ) . '">';
betterdocs()->views->get( 'templates/parts/sticky-toc' );
echo '</div>';
