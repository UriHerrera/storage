<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use WP_REST_Server;
use WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing\Contracts\BaseApi;

/**
 * Unified API handler for both REST and AJAX transports.
 *
 * @property string $namespace
 * @property string $action_prefix
 * @property string $textdomain
 */
#[\AllowDynamicProperties]
class Api extends BaseApi {
	private $version  = 'v1';
	private $api_type = '';

	/**
	 * Registers REST routes or AJAX actions based on the configured API type.
	 *
	 * @throws Exception If required config (namespace or action_prefix) is missing.
	 */
	public function register(): void {
		$this->api_type = strtolower( $this->license_manager->api );

		if ( 'rest' === $this->api_type ) {
			if ( ! isset( $this->namespace ) ) {
				throw new Exception( "namespace is missing in your rest configuration." );
			}

			add_action( 'rest_api_init', [ $this, 'routes' ] );
		} elseif ( 'ajax' === $this->api_type ) {
			if ( ! isset( $this->action_prefix ) ) {
				throw new Exception( "action_prefix needs to be set in ajax configuration" );
			}

			add_action( "wp_ajax_{$this->action_prefix}/license/activate", [ $this, 'activate' ] );
			add_action( "wp_ajax_{$this->action_prefix}/license/deactivate", [ $this, 'deactivate' ] );
			add_action( "wp_ajax_{$this->action_prefix}/license/submit-otp", [ $this, 'submit_otp' ] );
			add_action( "wp_ajax_{$this->action_prefix}/license/resend-otp", [ $this, 'resend_otp' ] );
			add_action( "wp_ajax_{$this->action_prefix}/license/get-license", [ $this, 'get_license' ] );
			add_action( "wp_ajax_{$this->action_prefix}/license/delete-license", [ $this, 'delete_license_action' ] );
		}
	}

	/**
	 * Returns the API configuration array with transport-specific URL and settings.
	 *
	 * @return array
	 */
	public function get_api_config(): array {
		$config = parent::get_api_config();

		if ( 'rest' === $this->api_type ) {
			$config['api_url'] = esc_url( trailingslashit( rest_url( $this->get_namespace() ) ) );
		} elseif ( 'ajax' === $this->api_type ) {
			$config['action']  = $this->action_prefix;
			$config['api_url'] = esc_url( admin_url( 'admin-ajax.php' ) );
		}

		return $config;
	}

	// ── REST-specific methods ──────────────────────────────────────────

	/**
	 * Registers all REST API routes for license management.
	 *
	 * @return void
	 */
	public function routes() {
		$this->route( '/license/activate', [ $this, 'activate' ], $this->args() );
		$this->route( '/license/deactivate', [ $this, 'deactivate' ] );
		$this->route( '/license/submit-otp', [ $this, 'submit_otp' ], $this->args( [
			'otp' => [
				'required'          => true,
				'validate_callback' => function ( $param ) {
					return is_string( $param ) && ! empty( $param );
				},
				'sanitize_callback' => 'sanitize_text_field',
			],
		] ) );
		$this->route( '/license/resend-otp', [ $this, 'resend_otp' ], $this->args() );
		$this->route( '/license/get-license', [ $this, 'get_license' ] );
		$this->route( '/license/delete-license', [ $this, 'delete_license_action' ] );
	}

	/**
	 * Builds the default REST route argument definitions, merged with overrides.
	 *
	 * @param array $args Additional argument definitions to merge.
	 *
	 * @return array
	 */
	private function args( array $args = [] ): array {
		return wp_parse_args( $args, [
			'license_key' => [
				'required'          => true,
				'validate_callback' => function ( $param ) {
					return is_string( $param ) && ! empty( $param );
				},
				'sanitize_callback' => 'sanitize_text_field',
			],
		] );
	}

	/**
	 * Returns the versioned REST namespace string.
	 *
	 * @return string
	 */
	private function get_namespace(): string {
		return $this->namespace . '/' . $this->version;
	}

	/**
	 * Registers a single POST REST route with permission callback.
	 *
	 * @param string   $endpoint The route endpoint path.
	 * @param callable $callback The route handler callback.
	 * @param array    $args     Route argument definitions.
	 *
	 * @return bool Whether the route was registered successfully.
	 */
	private function route( $endpoint, $callback, $args = [] ) {
		return register_rest_route( $this->get_namespace(), $endpoint, [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => $callback,
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => $args,
		] );
	}

	// ── AJAX-specific methods ──────────────────────────────────────────

	/**
	 * Sends a JSON error response and terminates execution.
	 *
	 * @param string $code    The error code.
	 * @param string $message The error message.
	 *
	 * @return void
	 */
	private function ajax_error( string $code, string $message ): void {
		wp_send_json_error( [
			'code'    => $code,
			'message' => $message,
		] );

		// Explicit exit as a defence-in-depth measure. wp_send_json_error() calls
		// wp_die() internally, but test-environment shims sometimes bypass that,
		// meaning execution could fall through into the caller without this guard.
		exit;
	}

	/**
	 * Verifies nonce and user permissions for AJAX requests.
	 *
	 * @return void
	 */
	private function nonce_permission_check(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is verified in this line.
		if ( ! isset( $_POST['_nonce'] ) || ! $this->verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) ) ) {
			$this->ajax_error( 'nonce_error', __( 'Nonce Verifications Failed.', 'betterdocs-pro' ) );
		}

		if ( ! $this->permission_check() ) {
			$this->ajax_error( 'no_permission', __( 'You don\'t have permission to take this action.', 'betterdocs-pro' ) );
		}
	}

	/**
	 * Sends the AJAX response as JSON, handling WP_Error conversion.
	 *
	 * @param object|WP_Error $response The response to send.
	 *
	 * @return void
	 */
	private function send_response( $response ) {
		if ( is_wp_error( $response ) ) {
			$this->ajax_error( $response->get_error_code(), $response->get_error_message() );
		}

		wp_send_json_success( $response );
	}

	// ── Endpoint methods ───────────────────────────────────────────────

	/**
	 * Handles a license activation request via REST or AJAX.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array).
	 *
	 * @return object|WP_Error|void REST returns the response; AJAX sends JSON and exits.
	 */
	public function activate( $request = [] ) {
		if ( 'rest' === $this->api_type ) {
			return $this->license_manager->activate( [
				'license_key' => sanitize_text_field( $request->get_param( 'license_key' ) ),
			] );
		}

		$this->nonce_permission_check();

		$response = $this->license_manager->activate( [
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in nonce_permission_check().
			'license_key' => sanitize_text_field( wp_unslash( isset( $_POST['license_key'] ) ? $_POST['license_key'] : '' ) ),
		] );

		$this->send_response( $response );
	}

	/**
	 * Handles a license deactivation request via REST or AJAX.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array).
	 *
	 * @return object|WP_Error|void REST returns the response; AJAX sends JSON and exits.
	 */
	public function deactivate( $request = [] ) {
		if ( 'rest' === $this->api_type ) {
			return $this->license_manager->deactivate();
		}

		$this->nonce_permission_check();

		$response = $this->license_manager->deactivate();

		$this->send_response( $response );
	}

	/**
	 * Handles an OTP submission request via REST or AJAX.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array).
	 *
	 * @return object|WP_Error|void REST returns the response; AJAX sends JSON and exits.
	 */
	public function submit_otp( $request = [] ) {
		if ( 'rest' === $this->api_type ) {
			$args = [
				'otp'         => sanitize_text_field( $request->get_param( 'otp' ) ),
				'license_key' => sanitize_text_field( $request->get_param( 'license_key' ) ),
			];

			return $this->license_manager->submit_otp( $args );
		}

		$this->nonce_permission_check();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in nonce_permission_check().
		$args = [
			'otp'         => sanitize_text_field( wp_unslash( isset( $_POST['otp'] ) ? $_POST['otp'] : '' ) ),
			'license_key' => sanitize_text_field( wp_unslash( isset( $_POST['license'] ) ? $_POST['license'] : '' ) ),
		];

		$response = $this->license_manager->submit_otp( $args );

		$this->send_response( $response );
	}

	/**
	 * Handles a request to resend the OTP code via REST or AJAX.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array).
	 *
	 * @return object|WP_Error|void REST returns the response; AJAX sends JSON and exits.
	 */
	public function resend_otp( $request = [] ) {
		if ( 'rest' === $this->api_type ) {
			$args = [
				'license_key' => sanitize_text_field( $request->get_param( 'license_key' ) ),
			];

			return $this->license_manager->resend_otp( $args );
		}

		$this->nonce_permission_check();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in nonce_permission_check().
		$args = [
			'license_key' => sanitize_text_field( wp_unslash( isset( $_POST['license'] ) ? $_POST['license'] : '' ) ),
		];

		$response = $this->license_manager->resend_otp( $args );

		$this->send_response( $response );
	}

	/**
	 * Returns the current license data for display, via REST or AJAX.
	 *
	 * @return array|void REST returns the data array; AJAX sends JSON and exits.
	 */
	public function get_license() {
		if ( 'ajax' === $this->api_type ) {
			$this->nonce_permission_check();
		}

		// Local data only — the endpoint must answer fast during a store outage.
		// A cold cache serves the stale shape and schedules a background recheck.
		$license_data = $this->license_manager->get_license_data( false );

		/* translators: %s: plugin name */
		$title = sprintf( __( '%s License', 'betterdocs-pro' ), $this->license_manager->item_name );

		if ( ! isset( $license_data['license_key'] ) ) {
			$data = [ 'title' => $title, 'key' => '', 'status' => 'invalid', 'is_stale' => false ];

			if ( 'ajax' === $this->api_type ) {
				wp_send_json_success( $data );
			}

			return $data;
		}

		$license_key = $this->license_manager->hide_license_key( $license_data['license_key'] );
		$status      = $license_data['license_status'];
		$data        = [
			'title'    => $title,
			'key'      => $license_key,
			'status'   => $status,
			'is_stale' => ! empty( $license_data['is_stale'] ),
		];

		if ( 'ajax' === $this->api_type ) {
			wp_send_json_success( $data );
		}

		return $data;
	}

	/**
	 * Handles a request to delete local license data without contacting the remote server.
	 *
	 * @param mixed $request The incoming request (WP_REST_Request or empty array).
	 *
	 * @return array|void REST returns the data array; AJAX sends JSON and exits.
	 */
	public function delete_license_action( $request = [] ) {
		if ( 'ajax' === $this->api_type ) {
			$this->nonce_permission_check();
		}

		$this->license_manager->delete_license();

		$data = [ 'success' => true ];

		if ( 'ajax' === $this->api_type ) {
			wp_send_json_success( $data );
		}

		return $data;
	}
}
