<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use WPDeveloper\BetterDocs\Utils\Helper;

/**
 * This portion will decide which tab will be showed.
 */
$admin_tab = Helper::admin_tab();
?>
<div class="wrap">
	<hr class="wp-header-end">
	<div class="betterdocs-listing-wrapper">
		<?php do_action( 'betterdocs_listing_header', $admin_tab ); ?>
	</div>

	<div id="betterdocs-dashboard-app"></div>
	<div class="betterdocs-dashboard-container">
		<?php betterdocs()->views->get( 'admin/settings/blocks' ); ?>
	</div>
</div>
