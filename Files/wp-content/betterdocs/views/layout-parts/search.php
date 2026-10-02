<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$_wrapper_attr = 'class="betterdocs-search-form-wrapper betterdocs-search-form-wrap"';

if ( isset( $wrapper_attr ) ) {
	$_wrapper_attr = $wrapper_attr;
}

?>

<div
	<?php echo $_wrapper_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo do_shortcode( '[betterdocs_search_form ' . $attributes . ']' ); ?>
</div>
