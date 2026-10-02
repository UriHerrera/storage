<div
	<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
		$attributes = betterdocs()->template_helper->get_html_attributes( $shortcode_attr );
		echo do_shortcode( shortcode_unautop( '[betterdocs_search_form ' . $attributes . ']' ) );
	?>
</div>
