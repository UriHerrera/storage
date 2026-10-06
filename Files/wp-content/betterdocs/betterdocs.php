<?php

/**
 * Plugin Name:       BetterDocs
 * Plugin URI:        https://betterdocs.co/
 * Description:       Create stunning Knowledge base & FAQs for your WordPress website and reduce support pressure with the help of BetterDocs. Get access to amazing templates and create fully customizable KB with AI Write.
 * Version:           4.9.3
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       betterdocs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use WPDeveloper\BetterDocs\Plugin;

defined( 'ABSPATH' ) || exit;

define( 'BETTERDOCS_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/includes/v2x-compatibility.php';
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Bundled MCP runtime (WordPress Abilities API + Parsedown).
 *
 * Loaded through the Jetpack Autoloader so that if the same library is also
 * shipped by another plugin — or lands in WordPress core — the newest copy
 * wins and loads once, with no fatal class collisions. This lets BetterDocs
 * serve its MCP connector out of the box, without the standalone Abilities
 * API plugin. See docs/mcp-server.md for the update procedure.
 *
 * On WordPress 7.1+, where core ships the Abilities API itself, Runtime::init()
 * stands the bundled copy down so it stops duplicating core's REST routes and
 * admin assets; on 6.4-7.0 the bundle is the only API and is left untouched.
 *
 * @since 4.9.0
 */
$betterdocs_mcp_runtime = __DIR__ . '/dependencies/vendor/autoload_packages.php';
if ( is_readable( $betterdocs_mcp_runtime ) ) {
    require_once $betterdocs_mcp_runtime;

    \WPDeveloper\BetterDocs\Abilities\Runtime::init();
}
unset( $betterdocs_mcp_runtime );

/**
 * Declare WooCommerce HPOS (custom order tables) compatibility. BetterDocs
 * never touches order storage, so this is a pure compatibility flag. Must run
 * on before_woocommerce_init — the Plugin bootstraps on `init`, which is too
 * late for WooCommerce to read the declaration.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            BETTERDOCS_PLUGIN_FILE,
            true
        );
    }
} );

/**
 * Initiate the BetterDocs Plugin
 *
 * @return Plugin
 * @since 2.5.0
 */
function betterdocs(): Plugin {
    /**
     * Remove PRO Functionalities if pro is not updated.
     */
    if ( ! function_exists( 'betterdocs_pro' ) ) {
        remove_action( 'betterdocs_init', 'run_betterdocs_pro' );
    }

    return Plugin::get_instance();
}

/**
 * Initialize BetterDocs (Free)
 * Here, begins the execution of the plugin.
 *
 * Returns the main instance of BetterDocs.
 *
 * @return Plugin
 * @since  3.0
 */

betterdocs();
