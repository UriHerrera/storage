<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://wpdeveloper.com
 * @since      1.0.0
 *
 * @package    Betterdocs_Pro
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop the Real-time Related Docs tracking tables. WordPress doesn't fire
// activation->install->dbDelta a second time after uninstall, so leaving
// these around would just create orphan tables in user databases.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
// Plugin uninstall: removing custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}betterdocs_user_journeys" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}betterdocs_related_suggestions" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

// Clear scheduled retention cron in case it's still pending.
$betterdocs_next = wp_next_scheduled( 'betterdocs_related_docs_cleanup' );
if ( $betterdocs_next ) {
	wp_unschedule_event( $betterdocs_next, 'betterdocs_related_docs_cleanup' );
}
