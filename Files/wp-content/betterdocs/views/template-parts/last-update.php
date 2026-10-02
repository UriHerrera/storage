<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $last_update ) {
	return;
}
	$date                   = betterdocs()->query->latest_updated_date( $term->taxonomy, $term->slug );
	$last_updated_time_text = isset( $last_updated_time_text ) ? $last_updated_time_text : __( 'Last Updated', 'betterdocs' );
?>
<p class="betterdocs-last-update">
	<?php
	printf( '%s: %s', esc_html( $last_updated_time_text ), esc_html( $date ) );
	?>
</p>

