<?php

/**
 *
 * @link              https://wpdeveloper.com
 * @since             1.0.0
 * @package           Betterdocs_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       BetterDocs Pro
 * Plugin URI:        https://betterdocs.co
 * Description:       Help your customers browse the docs and find instant answers through BetterDocs Instant Answers. Get access to Multiple KB, Insightful Analytics & many more!
 * Version:           4.3.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       betterdocs-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

define( 'BETTERDOCS_PRO_FILE', __FILE__ );

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Bundled Action Scheduler (Advanced Analytics v1.0 aggregation backbone).
 *
 * Not Mozart-prefixed: Action Scheduler is a shared library with built-in
 * version negotiation — the newest copy registered across all active plugins
 * wins, so loading our own copy never conflicts with WooCommerce/others.
 * Declared in composer.json (excluded from Mozart); this committed copy is the
 * canonical bundle. Required unconditionally — action-scheduler.php self-guards
 * and registers its version for negotiation; must load before `plugins_loaded`.
 */
$betterdocs_pro_as = __DIR__ . '/libs/action-scheduler/action-scheduler.php';
if ( file_exists( $betterdocs_pro_as ) ) {
    require_once $betterdocs_pro_as;
}

/**
 * Intiate the BetterDocs (Pro) Plugin
 *
 * @package WP-Background-Processing
 */
function betterdocs_pro() {
    return \WPDeveloper\BetterDocsPro\Plugin::get_instance();
}

/**
 * Initialize BetterDocs (Pro)
 * Here, begins the execution of the plugin.
 *
 * Returns the main instance of BetterDocs Pro.
 *
 * @since  3.0
 * @return \WPDeveloper\BetterDocsPro\Plugin
 */

betterdocs_pro();
