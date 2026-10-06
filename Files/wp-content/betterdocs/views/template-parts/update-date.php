<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( betterdocs()->settings->get( 'show_last_update_time' ) ) {
    echo wp_sprintf( '<div class="update-date">%s %s</div>', esc_html__( 'Updated on', 'betterdocs' ),  get_the_modified_date() ); //phpcs:ignore
}
