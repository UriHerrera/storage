<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use WP_Error;

/**
 * @property int             $item_id
 * @property string          $version
 * @property string          $storeURL
 * @property string          $db_prefix
 * @property string          $textdomain
 * @property string          $item_name
 * @property string          $plugin_file
 * @property string          $page_slug
 * @property string|string[] $screen_id
 * @property string          $scripts_handle
 * @property bool            $dev_mode
 * @property string          $api
 * @property string          $namespace
 */
#[\AllowDynamicProperties]
class Manager {
	private        $_version     = '2.3.1';
	private static $_instance    = null;
	protected      $license      = '';
	protected      $license_data = null;

	/**
	 * @var LicenseStore
	 */
	protected $store;

	/**
	 * Actions whose failed requests are tracked for the retry backoff.
	 *
	 * @var string[]
	 */
	private const BACKOFF_ACTIONS = [
		'activate_license',
		'activate_license_by_otp',
		'resend_otp_for_license',
		'deactivate_license',
		'check_license',
	];

	/**
	 * @var array
	 */
	protected $args = [
		'version'        => '',
		'plugin_file'    => '',
		'item_id'        => 0,
		'item_name'      => '',
		'item_slug'      => '',
		'storeURL'       => 'https://api.wpdeveloper.com',
		'textdomain'     => '',
		'db_prefix'      => '',
		'scripts_handle' => '',
		'screen_id'      => '',
		'page_slug'      => '',
		'api'            => ''
	];

	/**
	 * @var array
	 */
	private $endpoints = [
		'activate_license'   => '/activate-license',
		'activate_license_by_otp'   => '/activate-license',
		'resend_otp_for_license'   => '/activate-license',
		'deactivate_license' => '/deactivate-license',
		'check_license'      => '/check-license',
		'get_version'      => '/get-latest-license',
	];

	/**
	 * @var array
	 */
	private $error = [];

	/**
	 * Returns the singleton instance of the Manager.
	 *
	 * @param array $args Configuration arguments.
	 *
	 * @return self
	 *
	 * @throws Exception If required args are missing.
	 */
	public static function get_instance( $args ) {
		if ( null === self::$_instance ) {
			self::$_instance = new self( $args );
		}

		return self::$_instance;
	}

	/**
	 * Magic getter for accessing config values as properties.
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		if ( isset( $this->args[ $name ] ) ) {
			return $this->args[ $name ];
		}

		return null;
	}

	/**
	 * Magic isset check for config values.
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function __isset( $name ) {
		return isset( $this->args[ $name ] );
	}

	/**
	 * Initializes the license manager with configuration, storage, API, and hooks.
	 *
	 * @param array $args Configuration arguments.
	 *
	 * @throws Exception If required args are missing.
	 */
	public function __construct( array $args ) {
		foreach ( $this->args as $property => $value ) {
			if ( ! array_key_exists( $property, $args ) && empty( $value ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( "$property is missing in licensing." );
			}
		}

		$this->args = wp_parse_args( $args, $this->args );

		$this->store = new LicenseStore( $this->db_prefix );
		$this->store->maybe_migrate_error_key();

		if ( ! empty( $this->args['migrate_from'] ) && is_array( $this->args['migrate_from'] ) ) {
			$this->store->maybe_migrate_from( $this->args['migrate_from'] );
		}

		if ( true === $this->dev_mode ) {
			// Scope the external-host allowance to our store URL only, so other
			// plugins' wp_safe_remote_* calls are not affected.
			add_filter( 'http_request_host_is_external', [ $this, 'allow_store_host' ], 10, 2 );
		}

		add_action( "{$this->db_prefix}_license_recheck", [ $this, 'background_recheck' ] );

		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 999 );

		if ( ! empty( $this->args['api'] ) ) {
			$api_type = strtolower( $this->args['api'] );

			if ( ! isset( $this->args[ $api_type ] ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( "$api_type is missing in licensing." );
			}

			new Api( $this );
		}

		add_action( 'init', [ $this, 'plugin_updater' ] );

		$weekly_check = isset( $this->args['weekly_check'] ) ? $this->args['weekly_check'] : false;
		if ( $weekly_check ) {
			new CronChecker( $this );
		}

		$action_links = isset( $this->args['action_links'] ) ? $this->args['action_links'] : false;
		if ( $action_links ) {
			add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), [ $this, 'plugin_action_links' ] );
		}
	}

	/**
	 * Returns the underlying LicenseStore instance.
	 *
	 * @return LicenseStore
	 */
	public function get_store(): LicenseStore {
		return $this->store;
	}

	/**
	 * Displays admin notices for license errors or activation prompts.
	 *
	 * @return void
	 */
	public function admin_notices(): void {
		// Do not output HTML during REST API requests — it corrupts the JSON response.
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_is_json_request() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( null === $this->license_data ) {
			// Cached/local data only — admin pages must never block on a remote check.
			$this->license_data = $this->get_license_data( false );
		}

		$this->error = $this->get_error();

		if ( ! empty( $this->error ) ) {
			// Connection problems are not actionable for the user and the license
			// keeps working from its stored status — stay silent site-wide.
			if ( ! is_array( $this->error ) || ! isset( $this->error['message'] ) || $this->is_connection_error( $this->error ) ) {
				return;
			}

			$this->render_notice( $this->error['message'] );

			return;
		}

		if ( ! empty( $this->license_data ) ) {
			return;
		}

		/* translators: 1: opening anchor tag, 2: closing anchor tag, 3: plugin name */
		$message = sprintf( __( '%1$sActivate your %3$s License Key%2$s to receive regular updates and secure your WordPress website.', 'betterdocs-pro' ), '<a style="text-decoration: underline; font-weight: bold;" href="' . esc_url( admin_url( 'admin.php?page=' . $this->page_slug ) ) . '">', '</a>', $this->item_name );

		if ( isset( $this->args['activation_notice'] ) ) {
			$message = $this->args['activation_notice'];
		}

		$this->render_notice( $message );
	}

	/**
	 * Renders an admin notice with an optional plugin icon.
	 *
	 * @param string $message The notice message HTML.
	 *
	 * @return void
	 */
	private function render_notice( $message ) {
		$icon_html = '';

		if ( ! empty( $this->args['item_icon'] ) ) {
			$icon_html = sprintf(
				'<img src="%s" alt="%s" style="width: 24px; height: 24px; margin-right: 10px; vertical-align: middle;" />',
				esc_url( $this->args['item_icon'] ),
				esc_attr( $this->item_name )
			);
		}

		$notice = sprintf(
			'<div style="padding: 10px; display: flex; align-items: center;" class="%1$s-notice wpdeveloper-licensing-notice notice notice-error">%2$s<p>%3$s</p></div>',
			sanitize_html_class( $this->textdomain ),
			$icon_html,
			$message
		);

		echo wp_kses_post( $notice );
	}

	/**
	 * Initializes the plugin updater to check for updates from the store API.
	 *
	 * @return void
	 */
	public function plugin_updater(): void {
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;

		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$_license = $this->store->get_license();

		new Updater( $this->storeURL, $this->plugin_file, [
			'sdk_version'     => $this->_version,
			'version'         => $this->version,
			'license'         => $_license,
			'item_id'         => $this->item_id,
			'update_endpoint' => true === $this->dev_mode ? 'staging-get-latest-version' : 'get-latest-version',
			'author'          => empty( $this->author ) ? 'WPDeveloper' : $this->author,
			'beta'            => isset( $this->beta ) ? $this->beta : false,
		] );
	}

	/**
	 * Retrieves configuration arguments, or a single argument by name.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get_args( string $name = '' ) {
		return empty( $name ) ? $this->args : $this->args[ $name ];
	}

	/**
	 * Enqueues license data as a localized script on matching admin screens.
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

		// Local data only — an admin page render must never block on a remote
		// request. A cold cache serves the stale shape and schedules a recheck.
		wp_localize_script( $this->scripts_handle, 'wpdeveloperLicenseData', $this->get_license_data( false ) );
	}

	/**
	 * Retrieves the full license data array including key, status, and cached API data.
	 *
	 * When the cache is empty and fresh remote data is unavailable (or remote
	 * requests are not allowed), a minimal array with the masked key and the
	 * last-known status is returned, flagged with 'is_stale' => true.
	 *
	 * @param bool $allow_remote Whether a remote check may be performed when the
	 *                           cache is empty. When false, a background recheck
	 *                           is scheduled instead of blocking the request.
	 *
	 * @return array
	 */
	public function get_license_data( bool $allow_remote = true ): array {
		$_license        = $this->store->get_license();

		if ( empty( $_license ) ) {
			return [];
		}

		$_license_data   = $this->store->get_license_data();
		if ( false !== $_license_data ) {
			$_license_data = (array) $_license_data;
		}

		if ( empty( $_license_data ) ) {
			if ( ! $allow_remote ) {
				$this->schedule_background_recheck();

				return $this->get_stale_license_data( $_license );
			}

			$response = $this->check();
			if ( is_wp_error( $response ) ) {
				return $this->get_stale_license_data( $_license );
			}

			$_license_data = (array) $response;
		}

		return array_merge( [
			'license_key'        => $_license,
			'hidden_license_key' => $this->hide_license_key( $_license ),
			'license_status'     => $this->store->get_status()
		], $_license_data );
	}

	/**
	 * Builds the minimal license data array served while no fresh remote data is
	 * available. Both key fields are masked — the raw key is never exposed here,
	 * matching the warm-cache path where the cached response's masked key wins
	 * the array_merge.
	 *
	 * @param string $_license The stored license key.
	 *
	 * @return array
	 */
	private function get_stale_license_data( string $_license ): array {
		$masked = $this->hide_license_key( $_license );

		return [
			'license_key'        => $masked,
			'hidden_license_key' => $masked,
			'license_status'     => $this->store->get_status(),
			'is_stale'           => true,
		];
	}

	/**
	 * Schedules a one-off background recheck of the license data.
	 *
	 * Connection problems retry quickly (following the per-action exponential
	 * request backoff, starting at ~60s); definitive license errors (expired,
	 * disabled, ...) retry at most once a day to avoid pointless remote checks.
	 *
	 * @return void
	 */
	private function schedule_background_recheck() {
		$hook = "{$this->db_prefix}_license_recheck";

		if ( wp_next_scheduled( $hook ) ) {
			return;
		}

		$stored = $this->store->get_error();

		if ( ! empty( $stored ) && ! $this->is_connection_error( $stored ) ) {
			$delay = DAY_IN_SECONDS;
		} else {
			// Follow the armed check backoff so the event is not a no-op.
			$delay = max( MINUTE_IN_SECONDS, $this->get_backoff_remaining( 'check_license' ) );
		}

		wp_schedule_single_event( time() + $delay, $hook );
	}

	/**
	 * Runs the scheduled background recheck. Hooked to {db_prefix}_license_recheck.
	 *
	 * @return void
	 */
	public function background_recheck(): void {
		if ( '' === $this->store->get_license() ) {
			return;
		}

		$this->check();
	}

	/**
	 * Clears any pending background recheck event.
	 *
	 * @return void
	 */
	private function clear_background_recheck() {
		wp_clear_scheduled_hook( "{$this->db_prefix}_license_recheck" );
	}

	/**
	 * Masks the middle portion of a license key for display purposes.
	 *
	 * @param string $_license The full license key.
	 *
	 * @return string
	 */
	public function hide_license_key( string $_license ): string {
		$length = mb_strlen( $_license ) - 10;

		return substr_replace( $_license, mb_substr( preg_replace( '/\S/', '*', $_license ), 5, $length ), 5, $length );
	}

	/**
	 * Activates a license key against the remote store.
	 *
	 * @param array $args Arguments containing 'license_key'.
	 *
	 * @return object|WP_Error The API response or error.
	 */
	public function activate( $args = [] ) {
		$this->license = sanitize_text_field( isset( $args['license_key'] ) ? trim( $args['license_key'] ) : '' );
		$response      = $this->remote_post( 'activate_license', [], true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		/**
		 * Return if license required OTP to activate.
		 */
		if ( isset( $response->license ) && 'required_otp' === $response->license ) {
			return $response;
		}

		$this->store->delete_error();

		$this->store->save_all( $this->license, $response );

		$this->maybe_schedule_cron();

		do_action( 'wpdeveloper_licensing_activated', $response, $this->license, $this );

		return $response;
	}

	/**
	 * Deactivates the current license key against the remote store.
	 *
	 * @return object|WP_Error The API response or error.
	 */
	public function deactivate() {
		$this->license = $this->store->get_license();
		$response      = $this->remote_post( 'deactivate_license', [], true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->store->purge_all();

		$this->maybe_unschedule_cron();
		$this->clear_background_recheck();

		do_action( 'wpdeveloper_licensing_deactivated', $response, $this );

		return $response;
	}

	/**
	 * Submits an OTP verification code to activate a license.
	 *
	 * @param array $args Arguments containing 'license_key' and 'otp'.
	 *
	 * @return object|WP_Error The API response or error.
	 */
	public function submit_otp( $args = [] ) {
		$this->license = sanitize_text_field( isset( $args['license_key'] ) ? trim( $args['license_key'] ) : '' );
		$response      = $this->remote_post( 'activate_license_by_otp', $args, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->store->save_all( $this->license, $response );

		$this->maybe_schedule_cron();

		do_action( 'wpdeveloper_licensing_activated', $response, $this->license, $this );

		return $response;
	}

	/**
	 * Requests the remote store to resend an OTP verification code.
	 *
	 * @param array $args Arguments containing 'license_key'.
	 *
	 * @return object|WP_Error The API response or error.
	 */
	public function resend_otp( $args ) {
		$this->license = sanitize_text_field( isset( $args['license_key'] ) ? trim( $args['license_key'] ) : '' );

		return $this->remote_post( 'resend_otp_for_license', $args, true );
	}

	/**
	 * Checks the current license status against the remote store.
	 *
	 * @param bool $force When true, cached data is ignored and a fresh remote
	 *                    check is performed. The cache is only overwritten on
	 *                    success, so transport failures never destroy good data.
	 *
	 * @return array|object|WP_Error The cached or fresh license data, or error.
	 */
	public function check( bool $force = false ) {
		$this->license = $this->store->get_license();

		if ( ! $force ) {
			$_license_data = $this->store->get_license_data();

			if ( false !== $_license_data ) {
				$_license_data = (array) $_license_data;
			}

			if ( ! empty( $_license_data ) ) {
				return $_license_data;
			}
		}

		$response = $this->remote_post( 'check_license' );

		if ( is_wp_error( $response ) ) {
			// A definitive answer from the server (expired, disabled, ...) means the
			// cached data is wrong; transport failures must keep the cache intact.
			if ( $this->is_license_error( $response ) ) {
				$this->store->delete_license_data();
			}

			return $response;
		}

		if ( isset( $response->license ) ) {
			$this->store->set_status( $response->license );
		}

		$this->store->set_license_data( $response );
		$this->store->delete_error();

		do_action( 'wpdeveloper_licensing_checked', $response, $this );

		return $response;
	}

	/**
	 * Sends a remote POST request to the licensing store API.
	 *
	 * @param string $action         The EDD action to perform (e.g. 'activate_license').
	 * @param array  $args           Additional arguments to include in the request body.
	 * @param bool   $user_initiated Whether the request was explicitly triggered by the
	 *                               user (activate/deactivate/OTP). User-initiated
	 *                               requests are never short-circuited by the backoff.
	 *
	 * @return object|WP_Error The decoded API response or error.
	 */
	public function remote_post( $action, $args = [], $user_initiated = false ) {
		if ( empty( $this->license ) ) {
			return new WP_Error( 'empty_license', __( 'Please provide a valid license.', 'betterdocs-pro' ) );
		}

		// The failure backoff only paces background checks; an explicit user
		// action must always reach the server, otherwise a single transient
		// failure would lock the customer out until the flag expires.
		if ( ! $user_initiated && $this->request_recently_failed( $action ) ) {
			return new WP_Error( 'request_backoff', __( 'The license server is temporarily unavailable. Please try again later.', 'betterdocs-pro' ), [
				'type'        => 'connection',
				'retry_after' => $this->get_backoff_remaining( $action ),
			] );
		}

		$defaults = [
			'sdk_version' => $this->_version,
			'edd_action'  => $action,
			'license'     => $this->license,
			'item_id'     => $this->item_id,
			'item_name'   => rawurlencode( $this->item_name ), // the name of our product in EDD
			'url'         => home_url(),
			'version'     => $this->version,
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		$args = wp_parse_args( $args, $defaults );

		$args = apply_filters( 'wpdeveloper_licensing_api_request_args', $args, $action, $this );

		// Re-enforce security-critical keys so no filter can tamper with them.
		$args['edd_action'] = $action;
		$args['license']    = $this->license;
		$args['item_id']    = $this->item_id;
		$args['url']        = home_url();

		/**
		 * Filters the request timeout for licensing API calls.
		 *
		 * Activation is the heaviest store action; the default is generous so a
		 * slow-but-successful activation is not misread as a connection failure.
		 *
		 * @param int     $timeout Timeout in seconds. Default 30.
		 * @param string  $action  The EDD action being performed.
		 * @param Manager $manager The Manager instance.
		 */
		$timeout = (int) apply_filters( 'wpdeveloper_licensing_request_timeout', 30, $action, $this );

		$response = wp_safe_remote_post( $this->get_api_url( $action ), [
			'timeout'   => $timeout,
			'sslverify' => (true !== $this->dev_mode) || is_ssl(),
			'body'      => $args,
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Only background failures are logged — the backoff never gates
			// user-initiated actions, so their records would be write-only.
			if ( ! $user_initiated ) {
				$this->log_failed_request( $action, $response );
			}

			if ( $user_initiated ) {
				/* translators: %s: plugin name */
				$friendly_message = sprintf( __( 'Could not connect to the license server to verify your %s license. This is usually temporary — please try again in a few minutes.', 'betterdocs-pro' ), $this->item_name );
			} else {
				/* translators: %s: plugin name */
				$friendly_message = sprintf( __( 'Could not connect to the license server to verify your %s license. This is usually temporary and will be retried automatically.', 'betterdocs-pro' ), $this->item_name );
			}

			if ( is_wp_error( $response ) ) {
				$this->store->set_error( [
					'code'    => $response->get_error_code(),
					'message' => $friendly_message,
					'type'    => 'connection',
					'detail'  => $response->get_error_message(),
				] );

				// Fire the action with the original error so loggers keep the raw detail.
				do_action( 'wpdeveloper_licensing_error', $response, $action, $this );

				return new WP_Error( $response->get_error_code(), $friendly_message, [
					'type'   => 'connection',
					'detail' => $response->get_error_message(),
				] );
			}

			$detail = sprintf( 'HTTP %d response from the license server.', wp_remote_retrieve_response_code( $response ) );

			$error = new WP_Error( 'unknown', $friendly_message, [
				'type'   => 'connection',
				'detail' => $detail,
			] );

			$this->store->set_error( [
				'code'    => 'unknown',
				'message' => $friendly_message,
				'type'    => 'connection',
				'detail'  => $detail,
			] );

			do_action( 'wpdeveloper_licensing_error', $error, $action, $this );

			return $error;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( null === $license_data ) {
			// A 200 with an unreadable body (WAF challenge, maintenance page,
			// fatal with a 200 status) is still a connection-class failure and
			// must keep pacing background checks.
			if ( ! $user_initiated ) {
				$this->log_failed_request( $action, $response );
			}

			$this->store->set_error( [
				'code'    => 'invalid_response',
				'message' => __( 'An error occurred, please try again.', 'betterdocs-pro' ),
				'type'    => 'connection',
				'detail'  => 'The license server returned an unreadable response.',
			] );

			$error = new WP_Error( 'invalid_response', __( 'An error occurred, please try again.', 'betterdocs-pro' ), [
				'type'   => 'connection',
				'detail' => 'The license server returned an unreadable response.',
			] );

			do_action( 'wpdeveloper_licensing_error', $error, $action, $this );

			return $error;
		}

		// A parseable JSON answer — even a license error — proves transport works.
		$this->clear_failed_request( $action );

		$license_data = $this->maybe_error( $license_data );

		if ( is_wp_error( $license_data ) ) {
			$this->store->set_error( [
				'code'    => $license_data->get_error_code(),
				'message' => $license_data->get_error_message(),
				'type'    => 'license',
				'detail'  => '',
			] );

			do_action( 'wpdeveloper_licensing_error', $license_data, $action, $this );
		} else {
			$license_data->license_key = $this->hide_license_key( $this->license );
			$this->store->delete_error();
		}

		return $license_data;
	}

	/**
	 * Returns the API URL for a given action.
	 *
	 * Per-action endpoint routing (e.g. /activate-license, /deactivate-license) is
	 * currently disabled — all actions post to the bare storeURL regardless of the
	 * action name or dev_mode flag.
	 *
	 * @param string $action The EDD action name.
	 *
	 * @return string
	 */
	private function get_api_url( $action ) {
		if ( false && isset( $this->endpoints[ $action ] ) ) {
			$endpoint = $this->endpoints[ $action ];

			if ( true === $this->dev_mode ) {
				$endpoint = '/staging-' . ltrim( $endpoint, '/' );
			}

			return rtrim( $this->storeURL, '/' ) . $endpoint;
		}

		return $this->storeURL;
	}

	/**
	 * Checks the API response for error conditions and returns a WP_Error if found.
	 *
	 * @param object $license_data The decoded API response object.
	 *
	 * @return object|WP_Error The original data if successful, or a WP_Error.
	 */
	private function maybe_error( $license_data ) {
		if ( false === $license_data->success ) {
			$error_code = 'unknown';

			if ( isset( $license_data->error ) ) {
				$error_code = $license_data->error;
			} elseif ( isset( $license_data->license ) ) {
				$error_code = $license_data->license;
			}

			switch ( $error_code ) {
				case 'expired':
					/* translators: 1: plugin name, 2: license key expiration date */
					$message = sprintf( __( 'Your <strong>%1$s</strong> license key expired on %2$s.', 'betterdocs-pro' ), $this->item_name, date_i18n( get_option( 'date_format' ), $license_data->expires ) );
					break;

				case 'invalid_otp':
					$message = __( 'Your license confirmation code is invalid.', 'betterdocs-pro' );
					break;

				case 'expired_otp':
					$message = __( 'Your license confirmation code has been expired.', 'betterdocs-pro' );
					break;

				case 'revalidate_license':
					/* translators: 1: opening strong tag, 2: closing strong tag, 3: opening anchor tag, 4: closing anchor tag, 5: plugin name */
					$message = sprintf( __( '%1$sAttention:%2$s Please %3$sVerify your %5$s License Key%4$s to get regular updates & secure your WordPress website.', 'betterdocs-pro' ), '<strong>', '</strong>', '<a style="text-decoration: underline; font-weight: bold;" href="' . esc_url( admin_url( 'admin.php?page=' . $this->page_slug ) ) . '">', '</a>', $this->item_name );
					break;

				case 'disabled':
				case 'revoked':
					/* translators: 1: plugin name */
					$message = sprintf( __( 'Your <strong>%s</strong> license key has been disabled.', 'betterdocs-pro' ), $this->item_name );
					break;

				case 'invalid':
				case 'missing':
					$message = __( 'Invalid license.', 'betterdocs-pro' );
					break;

				case 'inactive':
				case 'site_inactive':
					/* translators: the plugin name */
					$message = sprintf( __( 'Your <strong>%s</strong> license is not active for this URL.', 'betterdocs-pro' ), $this->item_name );
					break;

				case 'item_name_mismatch':
					/* translators: the plugin name */
					$message = sprintf( __( 'This appears to be an invalid license key for <strong>%s</strong>.', 'betterdocs-pro' ), $this->item_name );
					break;

				case 'no_activations_left':
					$message = __( 'Your license key has reached its activation limit.', 'betterdocs-pro' );
					break;

				case 'custom':
					$message = ! empty( $license_data->message ) ? $license_data->message : __( 'Something went wrong.', 'betterdocs-pro' );
					break;

				default:
					$message = __( 'An error occurred, please try again.', 'betterdocs-pro' );
					break;
			}

			return new WP_Error( $error_code, wp_kses( $message, 'post' ), [ 'type' => 'license' ] );
		}

		return $license_data;
	}

	/**
	 * Determines whether a WP_Error represents a definitive license error from
	 * the store (expired, disabled, ...), as opposed to a transport failure.
	 *
	 * @param mixed $error The error to inspect.
	 *
	 * @return bool
	 */
	private function is_license_error( $error ): bool {
		if ( ! is_wp_error( $error ) ) {
			return false;
		}

		$data = $error->get_error_data();

		return is_array( $data ) && isset( $data['type'] ) && 'license' === $data['type'];
	}

	/**
	 * Determines whether a stored error array represents a connection problem.
	 *
	 * Errors stored by versions before 2.2.0 have no 'type' key; for those the
	 * error code is matched against the known transport-level codes.
	 *
	 * @param mixed $error The stored error value.
	 *
	 * @return bool
	 */
	private function is_connection_error( $error ): bool {
		if ( ! is_array( $error ) ) {
			return false;
		}

		if ( isset( $error['type'] ) ) {
			return 'connection' === $error['type'];
		}

		$legacy_codes = [ 'http_request_failed', 'http_request_not_executed', 'request_backoff', 'invalid_response' ];

		return isset( $error['code'] ) && in_array( $error['code'], $legacy_codes, true );
	}

	/**
	 * Deletes all local license data without contacting the remote server.
	 *
	 * @return void
	 */
	public function delete_license() {
		$this->store->purge_all();
		$this->license      = '';
		$this->license_data = null;

		$this->maybe_unschedule_cron();
		$this->clear_background_recheck();
		$this->clear_failed_request();

		do_action( 'wpdeveloper_licensing_deleted', $this );
	}

	/**
	 * Adds a "Manage License" link to the plugin action links on the Plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$license_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . $this->page_slug ) ),
			__( 'Manage License', 'betterdocs-pro' )
		);

		array_unshift( $links, $license_link );

		return $links;
	}

	/**
	 * Returns the option name tracking failed requests for a given action.
	 *
	 * The key is scoped per store, product, and action so one product's failed
	 * background check never throttles another product — or another action —
	 * on the same site.
	 *
	 * @param string $action The EDD action name.
	 *
	 * @return string
	 */
	private function failed_request_cache_key( $action ) {
		return 'wpdeveloper_sl_failed_http_' . md5( $this->storeURL . '|' . $this->item_id . '|' . $action );
	}

	/**
	 * Determines if a recent API request for this action has failed.
	 *
	 * @param string $action The EDD action name.
	 *
	 * @return bool
	 */
	private function request_recently_failed( $action ) {
		$key     = $this->failed_request_cache_key( $action );
		$details = get_option( $key );

		if ( ! is_array( $details ) || empty( $details['until'] ) ) {
			return false;
		}

		if ( time() > (int) $details['until'] ) {
			// Expired: allow the next attempt. The record is kept briefly so
			// consecutive failures keep escalating, then cleaned up.
			if ( time() - (int) $details['until'] > HOUR_IN_SECONDS ) {
				delete_option( $key );
			}

			return false;
		}

		return true;
	}

	/**
	 * Returns the seconds remaining until the backoff for an action expires.
	 *
	 * @param string $action The EDD action name.
	 *
	 * @return int
	 */
	private function get_backoff_remaining( $action ) {
		$details = get_option( $this->failed_request_cache_key( $action ) );

		if ( is_array( $details ) && isset( $details['until'] ) ) {
			return max( 0, (int) $details['until'] - time() );
		}

		return 0;
	}

	/**
	 * Extracts a usable Retry-After value (in seconds) from a throttling
	 * response (HTTP 429/503), if the server sent one.
	 *
	 * @param mixed $response The wp_safe_remote_post() return value.
	 *
	 * @return int|null Seconds to wait, or null when not applicable.
	 */
	private function get_retry_after( $response ) {
		if ( empty( $response ) || is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 429 !== $code && 503 !== $code ) {
			return null;
		}

		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );

		if ( ! is_scalar( $retry_after ) || ! is_numeric( $retry_after ) ) {
			return null;
		}

		$retry_after = (int) $retry_after;

		// A zero/negative value is meaningless for pacing; fall back to the
		// exponential backoff instead of disabling it.
		if ( $retry_after <= 0 ) {
			return null;
		}

		return min( $retry_after, HOUR_IN_SECONDS );
	}

	/**
	 * Logs a failed HTTP request and schedules the next allowed background
	 * attempt: an exponential backoff (60s, 120s, 240s, ... capped at 1 hour,
	 * plus up to 10% jitter), or the server's own numeric Retry-After for
	 * 429/503 responses.
	 *
	 * @param string $action   The EDD action that failed.
	 * @param mixed  $response The failed wp_safe_remote_post() return value.
	 *
	 * @return void
	 */
	private function log_failed_request( $action, $response = null ) {
		$key     = $this->failed_request_cache_key( $action );
		$details = get_option( $key );
		$count   = 1;

		if ( is_array( $details ) && isset( $details['count'], $details['until'] ) ) {
			// Escalate only while failures are consecutive; a record from a
			// long-resolved outage starts over at 1.
			if ( time() - (int) $details['until'] < HOUR_IN_SECONDS ) {
				$count = min( (int) $details['count'] + 1, 10 );
			}
		}

		$delay = $this->get_retry_after( $response );

		if ( null === $delay ) {
			$delay  = (int) min( MINUTE_IN_SECONDS * pow( 2, $count - 1 ), HOUR_IN_SECONDS );
			$delay += wp_rand( 0, (int) ( $delay / 10 ) ); // Jitter so many sites don't retry in sync.
		}

		update_option( $key, [
			'until' => time() + $delay,
			'count' => $count,
		], 'no' );
	}

	/**
	 * Clears all failed request flags after a successful response. A readable
	 * answer proves the store is reachable, so every action's backoff — and
	 * the pre-2.2 shared flag — is reset.
	 *
	 * @param string $current_action The action that just succeeded; its record is
	 *                               cleared even when it is not a known backoff action.
	 *
	 * @return void
	 */
	private function clear_failed_request( $current_action = '' ) {
		$actions = self::BACKOFF_ACTIONS;

		if ( '' !== $current_action && ! in_array( $current_action, $actions, true ) ) {
			$actions[] = $current_action;
		}

		foreach ( $actions as $action ) {
			delete_option( $this->failed_request_cache_key( $action ) );
		}

		// Legacy shared key (< 2.2.0) — keyed on store URL only.
		delete_option( 'wpdeveloper_sl_failed_http_' . md5( $this->storeURL ) );
	}

	/**
	 * Allows outbound HTTP requests to the configured store host when dev_mode
	 * is active. Only the store's own hostname is allowed; all other hosts
	 * continue to be evaluated by WordPress's normal external-host policy.
	 *
	 * Hooked to 'http_request_host_is_external'.
	 *
	 * @param bool   $is_external Whether the host is considered external.
	 * @param string $host        The hostname being evaluated.
	 *
	 * @return bool
	 */
	public function allow_store_host( bool $is_external, string $host ): bool {
		$store_host = wp_parse_url( $this->storeURL, PHP_URL_HOST );

		if ( $store_host && $host === $store_host ) {
			return true;
		}

		return $is_external;
	}

	/**
	 * Schedules the weekly cron check if the weekly_check option is enabled.
	 *
	 * @return void
	 */
	private function maybe_schedule_cron() {
		$weekly_check = isset( $this->args['weekly_check'] ) ? $this->args['weekly_check'] : false;

		if ( $weekly_check ) {
			CronChecker::schedule( $this->db_prefix );
		}
	}

	/**
	 * Unschedules the weekly cron check if the weekly_check option is enabled.
	 *
	 * @return void
	 */
	private function maybe_unschedule_cron() {
		$weekly_check = isset( $this->args['weekly_check'] ) ? $this->args['weekly_check'] : false;

		if ( $weekly_check ) {
			CronChecker::unschedule( $this->db_prefix );
		}
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->get_license() instead.
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public function get_license( $default = '' ) {
		return $this->store->get_license( $default );
	}

	/**
	 * Persists the in-memory license key.
	 *
	 * Restored in 2.3.0 rather than added: this method was public on the
	 * pre-2.0.0 LicenseManager class and was dropped during the rename, so old
	 * code reaching the shim would fatal with "Call to undefined method" — the
	 * same failure mode {@see LicenseManager} exists to prevent.
	 *
	 * @deprecated 2.0.0 Use get_store()->set_license() instead.
	 *
	 * @return bool
	 */
	public function set_license() {
		return $this->store->set_license( (string) $this->license );
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->get_license_data() instead.
	 *
	 * @return mixed
	 */
	public function get_license_data_raw() {
		return $this->store->get_license_data();
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->set_license_data() instead.
	 *
	 * @param mixed    $response
	 * @param int|null $expiration
	 */
	public function set_license_data( $response, $expiration = null ) {
		$this->store->set_license_data( $response, $expiration );
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->delete_license_data() instead.
	 *
	 * @return bool
	 */
	public function remove_license_data() {
		return $this->store->delete_license_data();
	}

	/**
	 * Retrieves the stored error, clearing it if fresh license data exists.
	 *
	 * Stale data (served while the store is unreachable) must not clear the
	 * error — otherwise definitive errors like 'expired' would be swallowed.
	 *
	 * @return array|string The error array, or empty string if no error.
	 */
	private function get_error() {
		if ( $this->license_data && empty( $this->license_data['is_stale'] ) ) {
			$this->store->delete_error();

			return '';
		}

		$stored = $this->store->get_error();

		// A connection error with no stored license key is a leftover from a
		// failed activation attempt — it must never suppress the activation
		// prompt on a site that has no license at all.
		if ( ! empty( $stored ) && $this->is_connection_error( $stored ) && '' === $this->store->get_license() ) {
			$this->store->delete_error();

			return '';
		}

		return $stored;
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->get_status() instead.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->store->get_status();
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->set_status() instead.
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function set_status( $status = 'valid' ) {
		return $this->store->set_status( $status );
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->purge_all() instead.
	 *
	 * @param bool $withError
	 */
	public function removeData( $withError = true ) {
		$this->store->purge_all( $withError );
	}

	/**
	 * @deprecated 2.0.0 Use get_store()->save_all() instead.
	 *
	 * @param object $response
	 */
	public function addData( $response ) {
		$this->store->save_all( $this->license, $response );
	}
}
