<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedules a weekly WP-Cron event to keep license status fresh.
 */
class CronChecker {
	/**
	 * @var Manager
	 */
	private $manager;

	/**
	 * @var string The cron hook name.
	 */
	private $hook;

	/**
	 * Initializes the cron checker. Only schedules if a license is already active.
	 *
	 * @param Manager $manager The license manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
		$this->hook    = $manager->db_prefix . '_weekly_license_check';

		add_filter( 'cron_schedules', [ $this, 'add_weekly_schedule' ] );
		add_action( $this->hook, [ $this, 'check' ] );

		if ( ! empty( $manager->get_store()->get_license() ) ) {
			self::schedule( $manager->db_prefix );
		}
	}

	/**
	 * Registers the 'wpdeveloper_weekly' cron recurrence interval if it is not
	 * already present. Using a namespaced key avoids collisions with other plugins
	 * that might register their own 'weekly' interval with a different value.
	 *
	 * @param array $schedules Existing WP-Cron schedules.
	 *
	 * @return array
	 */
	public function add_weekly_schedule( array $schedules ): array {
		if ( ! isset( $schedules['wpdeveloper_weekly'] ) ) {
			$schedules['wpdeveloper_weekly'] = [
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'betterdocs-pro' ),
			];
		}

		return $schedules;
	}

	/**
	 * Performs a fresh remote license check and updates local status if changed.
	 *
	 * @return void
	 */
	public function check() {
		$store = $this->manager->get_store();

		$license = $store->get_license();
		if ( empty( $license ) ) {
			return;
		}

		$old_status = $store->get_status();

		// Force a fresh remote check; cached data is only replaced on success,
		// so a server outage never destroys it.
		$response = $this->manager->check( true );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$new_status = $store->get_status();

		do_action( 'wpdeveloper_licensing_status_checked', $new_status, $old_status, $this->manager );
	}

	/**
	 * Schedules the weekly license check cron event if not already scheduled.
	 *
	 * @param string $db_prefix The database prefix used by the Manager instance.
	 *
	 * @return void
	 */
	public static function schedule( $db_prefix ) {
		$hook = $db_prefix . '_weekly_license_check';

		if ( ! wp_next_scheduled( $hook ) ) {
			wp_schedule_event( time(), 'weekly', $hook );
		}
	}

	/**
	 * Unschedules the weekly license check cron event.
	 *
	 * Call this in the consuming plugin's deactivation hook.
	 *
	 * @param string $db_prefix The database prefix used by the Manager instance.
	 *
	 * @return void
	 */
	public static function unschedule( $db_prefix ) {
		$hook = $db_prefix . '_weekly_license_check';

		// Clear all scheduled occurrences regardless of which recurrence key was
		// used (handles the 'wpdeveloper_weekly' key used since v2.1 as well as
		// any orphaned 'weekly' events created by earlier versions).
		wp_clear_scheduled_hook( $hook );
	}
}
