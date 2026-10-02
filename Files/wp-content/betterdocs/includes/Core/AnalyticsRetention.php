<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;

/**
 * Free analytics retention: a daily native-cron purge of the simple aggregate
 * tables. Free keeps a rolling 12 months (365 days) of analytics by default —
 * enough history to be useful, while unlimited retention stays a Pro perk. A
 * site can shorten the window with the data_retention_days setting, or change /
 * disable it (0 = keep forever) via the betterdocs_analytics_retention_days
 * filter.
 *
 * Pro-safe: when Pro is active the purge is skipped entirely, because Pro
 * manages its own retention (unlimited by default) on top of these tables.
 * Mirrors the scheduling pattern in Pro's RelatedDocsTracker.
 */
class AnalyticsRetention extends Base {
	const CRON_HOOK = 'betterdocs_analytics_cleanup';

	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule_cleanup' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_cleanup' ] );

		if ( defined( 'BETTERDOCS_PLUGIN_FILE' ) ) {
			register_deactivation_hook( BETTERDOCS_PLUGIN_FILE, [ $this, 'clear_cleanup' ] );
		}
	}

	/**
	 * Schedule the daily purge once if it isn't already scheduled.
	 */
	public function maybe_schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public function clear_cleanup() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Free keeps a rolling 12 months of analytics by default.
	 */
	const DEFAULT_RETENTION_DAYS = 365;

	/**
	 * Delete aggregate rows older than the retention window. No-op when Pro is
	 * active so Pro's longer (unlimited-by-default) retention is never trimmed
	 * by Free.
	 */
	public function run_cleanup() {
		if ( betterdocs()->is_pro_active() ) {
			return;
		}

		// A site may shorten the window via the setting; the filter can lengthen
		// it or disable pruning entirely (0 => keep forever). Default is 12 months.
		$setting = (int) betterdocs()->settings->get( 'data_retention_days', 0 );
		$window  = $setting > 0 ? $setting : self::DEFAULT_RETENTION_DAYS;
		$days    = (int) apply_filters( 'betterdocs_analytics_retention_days', $window );
		if ( $days < 1 ) {
			return; // filtered to 0 => keep forever.
		}

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}betterdocs_analytics WHERE created_at < DATE_SUB( CURDATE(), INTERVAL %d DAY )",
				$days
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}betterdocs_search_log WHERE created_at < DATE_SUB( CURDATE(), INTERVAL %d DAY )",
				$days
			)
		);
	}
}
