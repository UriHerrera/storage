<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$tag = empty( $tag ) ? 'h2' : $tag;
// $tag lands in a tag-name position, so clamp it to a safe HTML tag via the
// allow-list rather than relying on strtolower() (which leaves a space/= intact).
$tag = betterdocs()->template_helper->is_valid_tag( $tag );
printf( '<%1$s id="betterdocs-entry-title" class="betterdocs-entry-title" %2$s>%3$s</%1$s>', $tag, $wrapper_attr, wp_kses_post( $title ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
