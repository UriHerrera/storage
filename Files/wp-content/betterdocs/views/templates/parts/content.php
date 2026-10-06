<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// 'the_content' is a WP-core filter; using its documented name is required.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
$content = apply_filters( 'the_content', get_the_content() );

$_htags = $_enable_toc = null;

if ( isset( $htags ) ) {
	$_htags = $htags;
}
if ( isset( $enable_toc ) ) {
	$_enable_toc = $enable_toc;
}

echo betterdocs()->template_helper->content( $content, $_htags, $_enable_toc ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
