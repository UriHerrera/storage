<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$tag = betterdocs()->template_helper->is_valid_tag( $tag );

echo wp_kses_post(
	'<' . $tag . ' class="betterdocs-entry-title" id="betterdocs-entry-title">' . get_the_title() . '</' . $tag . '>'
);
