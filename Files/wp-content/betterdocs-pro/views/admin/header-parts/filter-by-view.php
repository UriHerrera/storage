<?php
    
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter UI.
$post_status = ( isset( $_GET['view'] ) ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
?>

<select class="dashboard-search-field dashboard-select-view" name="view" id="dashboard-select-view">
	<option value=""><?php esc_html_e( 'Views', 'betterdocs-pro' );?></option>
	<option value="most_viewed"<?php echo ( 'most_viewed' === $post_status ) ? ' selected' : '' ?>>
		<?php esc_html_e( 'Most Viewed', 'betterdocs-pro' );?>
	</option>
	<option value="least_viewed"<?php echo ( 'least_viewed' === $post_status ) ? ' selected' : '' ?>>
		<?php esc_html_e( 'Least Viewed', 'betterdocs-pro' );?>
	</option>
</select>