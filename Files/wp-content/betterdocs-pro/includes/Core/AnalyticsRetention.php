<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Pro analytics retention + GDPR (Advanced Analytics v1.0).
 *
 * A daily Action Scheduler job purges, but ONLY when a finite retention window
 * is configured via the data_retention_days setting (default 0 = keep forever):
 *   - raw events older than 30 days (RAW_DAYS) — processed or not;
 *   - aggregated + legacy tables older than the configured retention window
 *     (filterable via betterdocs_analytics_retention_days).
 *
 * With the default "Forever" retention nothing is purged, so updating from an
 * older version never drops previously-collected analytics. Because the Free
 * retention purge (Core\AnalyticsRetention in the Free plugin) is a no-op while
 * Pro is active, Pro also trims the legacy betterdocs_analytics /
 * betterdocs_search_log tables here when a finite window is set.
 *
 * Also registers a WordPress privacy eraser and exposes purge_all() for a
 * full GDPR wipe (admin tool / WP-CLI).
 */
class AnalyticsRetention {
	const HOOK     = 'betterdocs_pro_analytics_cleanup';
	const RAW_DAYS = 30;

	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::HOOK, [ $this, 'run_cleanup' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
		add_filter( 'betterdocs_default_settings', [ $this, 'register_settings_defaults' ] );
	}

	/**
	 * Register analytics privacy/GeoIP setting defaults so they are readable and
	 * savable. The admin UI fields are bound in the settings unit (Unit 20).
	 *
	 * @param array $defaults
	 * @return array
	 */
	public function register_settings_defaults( $defaults ) {
		if ( ! is_array( $defaults ) ) {
			return $defaults;
		}
		if ( ! array_key_exists( 'analytics_cookieless', $defaults ) ) {
			$defaults['analytics_cookieless'] = false;
		}
		if ( ! array_key_exists( 'maxmind_account_id', $defaults ) ) {
			$defaults['maxmind_account_id'] = '';
		}
		if ( ! array_key_exists( 'maxmind_license_key', $defaults ) ) {
			$defaults['maxmind_license_key'] = '';
		}
		if ( ! array_key_exists( 'analytics_ga4', $defaults ) ) {
			$defaults['analytics_ga4'] = false;
		}
		// How GA4 forwarding is delivered when analytics_ga4 is on:
		// 'datalayer' (GTM/dataLayer push, the original behavior), 'gtag'
		// (BetterDocs injects gtag.js on doc pages), or 'mp' (server-side
		// Measurement Protocol). Only one path emits per view — no double counting.
		if ( ! array_key_exists( 'ga4_method', $defaults ) ) {
			$defaults['ga4_method'] = 'datalayer';
		}
		// Public GA4 Measurement ID (e.g. G-XXXXXXX) — used by gtag + Measurement Protocol.
		if ( ! array_key_exists( 'ga4_measurement_id', $defaults ) ) {
			$defaults['ga4_measurement_id'] = '';
		}
		// Measurement Protocol API secret — a real credential; read server-side only,
		// never localized to the frontend, masked in the settings REST response.
		if ( ! array_key_exists( 'ga4_api_secret', $defaults ) ) {
			$defaults['ga4_api_secret'] = '';
		}
		return $defaults;
	}

	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( self::HOOK ) ) {
			as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Pro retention window for aggregated/legacy data, in days.
	 *
	 * Driven by the data_retention_days setting (the Analytics → Settings
	 * "Data retention" control). 0 — the default — means "keep forever", in
	 * which case run_cleanup() purges nothing. Filterable for site overrides.
	 */
	public function retention_days() {
		$setting = (int) betterdocs()->settings->get( 'data_retention_days', 0 );
		return (int) apply_filters( 'betterdocs_analytics_retention_days', $setting );
	}

	public function run_cleanup() {
		global $wpdb;

		// Raw events: 30-day window — ALWAYS trimmed, independent of the retention
		// setting. This is the write-hottest table (every view/scroll beacon), and
		// anything older than RAW_DAYS is already rolled up into the daily aggregate,
		// so it must never grow unbounded — not even under "keep forever" (retention 0).
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}betterdocs_analytics_events WHERE created_at < DATE_SUB( NOW(), INTERVAL %d DAY )",
				self::RAW_DAYS
			)
		);

		$retention = $this->retention_days();

		// "Forever" (0 / unset, the default) — keep the aggregated + legacy tables
		// intact so an update or an unconfigured site never loses collected analytics.
		if ( $retention <= 0 ) {
			return;
		}

		// Aggregated tables (date-keyed) at the Pro window. ai_daily is the AI-Traffic
		// hourly rollup (one row per agent × post × day × hour) — keyed on stat_date.
		foreach ( [ 'betterdocs_analytics_daily', 'betterdocs_analytics_search', 'betterdocs_analytics_ai_daily' ] as $table ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}{$table} WHERE stat_date < DATE_SUB( CURDATE(), INTERVAL %d DAY )",
					$retention
				)
			);
		}

		// Datetime-keyed tables (feedback inbox + raw legacy) at the Pro window.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}betterdocs_analytics_feedback WHERE created_at < DATE_SUB( NOW(), INTERVAL %d DAY )",
				$retention
			)
		);

		// Legacy Free tables (created_at-keyed) — Pro keeps the longer window since
		// Free's purge is skipped while Pro is active. NOTE: betterdocs_analytics_links
		// is intentionally NOT time-purged here — its created_at is rewritten on every
		// weekly re-scan (Core\AnalyticsLinkScanner DELETEs + re-INSERTs each doc's
		// rows), so a finite window would wrongly empty the Link Health report for docs
		// not re-scanned within it. That per-doc replace already bounds the table's
		// growth; purge_all() still wipes it for a full GDPR erase.
		foreach ( [ 'betterdocs_analytics', 'betterdocs_search_log' ] as $table ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}{$table} WHERE created_at < DATE_SUB( CURDATE(), INTERVAL %d DAY )",
					$retention
				)
			);
		}

		// betterdocs_search_keyword is not date-keyed, so the sweep above leaves
		// its rows behind once their last log is pruned — they would otherwise
		// accumulate for the lifetime of the site. Bounded per run to keep the
		// cron cheap; the rest goes on the next pass.
		// (MySQL rejects LIMIT on a multi-table DELETE, hence the subquery; the
		// extra derived-table wrapper is required too, or it errors with
		// "can't specify target table for update in FROM clause".)
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}betterdocs_search_keyword
			WHERE id IN (
				SELECT id FROM (
					SELECT k.id
					FROM {$wpdb->prefix}betterdocs_search_keyword k
					LEFT JOIN {$wpdb->prefix}betterdocs_search_log l ON l.keyword_id = k.id
					WHERE l.id IS NULL
					LIMIT 5000
				) orphans
			)"
		);
	}

	/**
	 * Register a WordPress personal-data eraser. The analytics store keeps no
	 * directly-identifying data (IPs are salted-hashed or absent in cookieless
	 * mode; no emails/user ids on events), so there is nothing to erase per
	 * person — we report that transparently per WP's eraser contract.
	 *
	 * @param array $erasers
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['betterdocs-analytics'] = [
			'eraser_friendly_name' => __( 'BetterDocs Analytics', 'betterdocs-pro' ),
			'callback'             => [ $this, 'erase_personal_data' ]
		];
		return $erasers;
	}

	/**
	 * @param string $email
	 * @param int    $page
	 * @return array
	 */
	public function erase_personal_data( $email, $page = 1 ) {
		return [
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => [
				__( 'BetterDocs Analytics stores only aggregated, anonymized data (no email, name or raw IP), so there is no personal data to erase.', 'betterdocs-pro' )
			],
			'done'           => true
		];
	}

	/**
	 * Full GDPR wipe of all analytics data (admin tool / WP-CLI).
	 */
	public function purge_all() {
		global $wpdb;
		$tables = [
			'betterdocs_analytics_events',
			'betterdocs_analytics_daily',
			'betterdocs_analytics_search',
			'betterdocs_analytics_ai_daily',
			'betterdocs_analytics_feedback',
			'betterdocs_analytics_links',
			'betterdocs_analytics',
			'betterdocs_search_log',
			'betterdocs_search_keyword'
		];
		foreach ( $tables as $table ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}{$table}" );
		}
	}
}
