<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing\Contracts;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing\Manager;

/**
 * @property array|string $screen_id
 * @property string       $handle
 * @property int          $item_id
 */
#[\AllowDynamicProperties]
abstract class BaseApi {
	protected $config = null;

	/**
	 * @var Manager
	 */
	protected $license_manager;

	/**
	 * Initializes the API handler with config from the license manager.
	 *
	 * @param Manager $license_manager The license manager instance.
	 */
	public function __construct( Manager $license_manager ) {
		$this->license_manager     = $license_manager;
		$this->config              = $this->license_manager->get_args( $this->license_manager->api );
		$this->config['handle']    = $this->license_manager->get_args( 'scripts_handle' );
		$this->config['screen_id'] = $this->license_manager->get_args( 'screen_id' );
		$this->config['item_id']   = $this->license_manager->get_args( 'item_id' );

		$this->register();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 11 );
	}

	/**
	 * Localizes the API configuration script on matching admin screens.
	 *
	 * @param string $hook The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		if ( is_array( $this->screen_id ) && ! in_array( $hook, $this->screen_id, true ) ) {
			return;
		}

		if ( ! is_array( $this->screen_id ) && $this->screen_id !== $hook ) {
			return;
		}

		wp_localize_script( $this->handle, 'wpdeveloperLicenseManagerConfig', $this->get_api_config() );
	}

	/**
	 * Returns the base API configuration array for script localization.
	 *
	 * @return array
	 */
	public function get_api_config(): array {
		return [
			'textdomain' => $this->license_manager->textdomain,
			'apiType'    => $this->license_manager->api,
			'nonce'      => wp_create_nonce( "wpdeveloper_sl_{$this->item_id}_nonce" )
		];
	}

	/**
	 * Magic getter that resolves config values or falls back to the license manager.
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 *
	 * @throws Exception If the property is not found in either source.
	 */
	public function __get( $name ) {
		if ( isset( $this->config[ $name ] ) ) {
			return $this->config[ $name ];
		} elseif ( isset( $this->license_manager->{$name} ) ) {
			return $this->license_manager->get_args( $name );
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( "Please provide $name for api configuration." );
		}
	}

	/**
	 * Checks if a config property exists in the local config or license manager.
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function __isset( $name ) {
		return isset( $this->config[ $name ] ) || isset( $this->license_manager->{$name} );
	}

	/**
	 * Verifies a nonce against the expected licensing nonce action.
	 *
	 * @param string $nonce The nonce value to verify.
	 *
	 * @return bool
	 */
	protected function verify_nonce( string $nonce ): bool {
		return wp_verify_nonce( $nonce, "wpdeveloper_sl_{$this->item_id}_nonce" );
	}

	/**
	 * Checks if the current user has the required capability.
	 *
	 * @return bool
	 */
	public function permission_check(): bool {
		return current_user_can( isset( $this->permission ) ? $this->permission : 'delete_users' );
	}

	/**
	 * This method has to be implemented by each subclass based on their needs.
	 *
	 * i.e:
	 * For REST API this method can have route registration functionalities.
	 * For AJAX API this method can have actions in placed.
	 *
	 * @return void
	 */
	abstract public function register(): void;

	/**
	 * Handles a license activation request.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array for AJAX).
	 *
	 * @return mixed
	 */
	abstract public function activate( $request );

	/**
	 * Handles a license deactivation request.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array for AJAX).
	 *
	 * @return mixed
	 */
	abstract public function deactivate( $request );

	/**
	 * Handles an OTP submission request for license verification.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array for AJAX).
	 *
	 * @return mixed
	 */
	abstract public function submit_otp( $request );

	/**
	 * Handles a request to resend the OTP verification code.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array for AJAX).
	 *
	 * @return mixed
	 */
	abstract public function resend_otp( $request );

	/**
	 * Handles a request to delete local license data without contacting the remote server.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array for AJAX).
	 *
	 * @return mixed
	 */
	abstract public function delete_license_action( $request );
}
