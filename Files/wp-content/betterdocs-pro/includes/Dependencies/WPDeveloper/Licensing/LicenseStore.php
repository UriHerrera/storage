<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encapsulates all WordPress option/transient operations for license data.
 * Handles DB key naming and migration.
 */
class LicenseStore {
	/**
	 * @var string
	 */
	protected $prefix;

	/**
	 * Initializes the license store with the given database prefix.
	 *
	 * @param string $db_prefix
	 */
	public function __construct( string $db_prefix ) {
		$this->prefix = $db_prefix;
	}

	/**
	 * Retrieves the stored license key.
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public function get_license( string $default = '' ): string {
		return get_option( "{$this->prefix}_license", $default );
	}

	/**
	 * Stores the license key in the database.
	 *
	 * @param string $license
	 *
	 * @return bool
	 */
	public function set_license( string $license ): bool {
		return update_option( "{$this->prefix}_license", $license, 'no' );
	}

	/**
	 * Deletes the stored license key from the database.
	 *
	 * @return bool
	 */
	public function delete_license(): bool {
		return delete_option( "{$this->prefix}_license" );
	}

	/**
	 * Retrieves the current license status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return (string) get_option( "{$this->prefix}_license_status", '' );
	}

	/**
	 * Stores the license status in the database.
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function set_status( string $status = 'valid' ): bool {
		return update_option( "{$this->prefix}_license_status", $status, 'no' );
	}

	/**
	 * Deletes the stored license status from the database.
	 *
	 * @return bool
	 */
	public function delete_status(): bool {
		return delete_option( "{$this->prefix}_license_status" );
	}

	/**
	 * Retrieves the cached license data from a transient.
	 *
	 * @return mixed
	 */
	public function get_license_data() {
		return get_transient( "{$this->prefix}_license_data" );
	}

	/**
	 * Caches the license data as a transient.
	 *
	 * @param mixed    $data
	 * @param int|null $expiration Seconds. Defaults to 3 months.
	 *
	 * @return bool
	 */
	public function set_license_data( $data, $expiration = null ): bool {
		if ( null === $expiration ) {
			$expiration = MONTH_IN_SECONDS * 3;
		}

		return set_transient( "{$this->prefix}_license_data", $data, $expiration );
	}

	/**
	 * Deletes the cached license data transient.
	 *
	 * @return bool
	 */
	public function delete_license_data(): bool {
		return delete_transient( "{$this->prefix}_license_data" );
	}

	/**
	 * Retrieves the stored license error information.
	 *
	 * @return mixed
	 */
	public function get_error() {
		return get_option( "{$this->prefix}_license_data_error", '' );
	}

	/**
	 * Stores license error information in the database.
	 *
	 * @param array $error Error data: 'code', 'message' (user-facing, translatable),
	 *                     'type' ('connection'|'license') and 'detail' (raw
	 *                     transport text for debugging; empty for license errors).
	 *
	 * @return bool
	 */
	public function set_error( array $error ): bool {
		return update_option( "{$this->prefix}_license_data_error", $error );
	}

	/**
	 * Deletes the stored license error information.
	 *
	 * @return bool
	 */
	public function delete_error(): bool {
		return delete_option( "{$this->prefix}_license_data_error" );
	}

	/**
	 * Saves license key, status, and cached data in one call.
	 *
	 * @param string $license
	 * @param object $response
	 *
	 * @return void
	 */
	public function save_all( string $license, $response ): void {
		$this->set_license( $license );
		$this->set_status( $response->license );
		$this->set_license_data( $response );
	}

	/**
	 * Deletes license key, status, cached data, and optionally error.
	 *
	 * @param bool $with_error
	 *
	 * @return void
	 */
	public function purge_all( bool $with_error = true ): void {
		$this->delete_license();
		$this->delete_status();
		$this->delete_license_data();

		if ( $with_error ) {
			$this->delete_error();
		}
	}

	/**
	 * Migrates license key and status from old option names to the current ones.
	 * Skips if the current options already have data. Old options are left in place.
	 *
	 * @param array $map Associative array with optional keys 'license' and 'status',
	 *                   each holding the old option name to migrate from.
	 *
	 * @return void
	 */
	public function maybe_migrate_from( array $map ): void {
		if ( get_option( "{$this->prefix}_license_migrated", false ) ) {
			return;
		}

		if ( isset( $map['license'] ) && empty( $this->get_license() ) ) {
			$old_license = get_option( $map['license'], '' );

			if ( ! empty( $old_license ) ) {
				$this->set_license( $old_license );
			}
		}

		if ( isset( $map['status'] ) && empty( $this->get_status() ) ) {
			$old_status = get_option( $map['status'], '' );

			if ( ! empty( $old_status ) ) {
				$this->set_status( $old_status );
			}
		}

		update_option( "{$this->prefix}_license_migrated", true, 'no' );
	}

	/**
	 * Migrates error data from the old inconsistent key ({prefix}license_data_error)
	 * to the new consistent key ({prefix}_license_data_error).
	 *
	 * @return void
	 */
	public function maybe_migrate_error_key(): void {
		$old_key = "{$this->prefix}license_data_error";
		$new_key = "{$this->prefix}_license_data_error";

		$old_value = get_option( $old_key );

		if ( false !== $old_value ) {
			update_option( $new_key, $old_value );
			delete_option( $old_key );
		}
	}
}
