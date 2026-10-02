<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use stdClass;

/**
 * Allows plugins to use their own update API.
 *
 * @author Easy Digital Downloads
 * @version 1.9.2
 */
#[\AllowDynamicProperties]
class Updater {

	private $api_url     = '';
	private $api_data    = [];
	private $plugin_file = '';
	private $name        = '';
	private $slug        = '';
	private $version     = '';
	private $wp_override = false;
	private $beta        = false;
	private $failed_request_cache_key;

	/**
	 * Class constructor.
	 *
	 * @param string $_api_url The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array  $_api_data Optional data to send with API calls.
	 *
	 * @uses hook()
	 *
	 * @uses plugin_basename()
	 */
	public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {

		global $edd_plugin_data;

		$this->api_url                  = trailingslashit( $_api_url );
		$this->api_data                 = $_api_data;
		$this->plugin_file              = $_plugin_file;
		$this->name                     = plugin_basename( $_plugin_file );
		$this->slug                     = basename( $_plugin_file, '.php' );
		$this->version                  = $_api_data['version'];
		$this->wp_override              = isset( $_api_data['wp_override'] ) && $_api_data['wp_override'];
		$this->beta                     = ! empty( $this->api_data['beta'] );
		$this->failed_request_cache_key = 'edd_sl_failed_http_' . md5( $this->api_url );

		$edd_plugin_data[ $this->slug ] = $this->api_data;

		/**
		 * Fires after the $edd_plugin_data is set up.
		 *
		 * @param array $edd_plugin_data Array of EDD SL plugin data.
		 *
		 * @since x.x.x
		 *
		 */
		do_action( 'post_edd_sl_plugin_updater_setup', $edd_plugin_data );

		// Set up hooks.
		$this->init();
	}

	/**
	 * Set up WordPress filters to hook into WPs update process.
	 *
	 * @return void
	 * @uses add_filter()
	 *
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugins_api_filter' ], 10, 3 );
		add_action( 'after_plugin_row', [ $this, 'show_update_notification' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'show_changelog' ] );
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @param array $_transient_data Update array build by WordPress.
	 *
	 * @return array|object Modified update array with custom plugin data.
	 * @uses api_request()
	 *
	 */
	public function check_update( $_transient_data ) {
		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new stdClass();
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
			return $_transient_data;
		}

		$current = $this->get_repo_api_data();
		if ( false !== $current && is_object( $current ) && isset( $current->new_version ) ) {
			if ( version_compare( $this->version, $current->new_version, '<' ) ) {
				$_transient_data->response[ $this->name ] = $current;
			} else {
				// Populating the no_update information is required to support auto-updates in WordPress 5.5.
				$_transient_data->no_update[ $this->name ] = $current;
			}
		}
		$_transient_data->last_checked           = time();
		$_transient_data->checked[ $this->name ] = $this->version;

		return $_transient_data;
	}

	/**
	 * Get repo API data from store.
	 * Save to cache.
	 *
	 * @return stdClass|bool
	 */
	public function get_repo_api_data() {
		$version_info = $this->get_cached_version_info();

		if ( false === $version_info ) {
			$version_info = $this->api_request( 'plugin_latest_version', [
				'slug' => $this->slug,
				'beta' => $this->beta
			] );
			if ( ! $version_info ) {
				return false;
			}

			// This is required for your plugin to support auto-updates in WordPress 5.5.
			$version_info->plugin = $this->name;
			$version_info->id     = $this->name;
			$version_info->tested = $this->get_tested_version( $version_info );

			$this->set_version_info_cache( $version_info );
		}

		return $version_info;
	}

	/**
	 * Gets the plugin's tested version.
	 *
	 * @param object $version_info
	 *
	 * @return null|string
	 * @since 1.9.2
	 */
	private function get_tested_version( $version_info ) {

		// There is no tested version.
		if ( empty( $version_info->tested ) ) {
			return null;
		}

		// Strip off extra version data so the result is x.y or x.y.z.
		$bloginfo = explode( '-', get_bloginfo( 'version' ) );
		$current_wp_version = isset( $bloginfo[0] ) ? $bloginfo[0] : '';

		// The tested version is greater than or equal to the current WP version, no need to do anything.
		if ( version_compare( $version_info->tested, $current_wp_version, '>=' ) ) {
			return $version_info->tested;
		}
		$current_version_parts = explode( '.', $current_wp_version );
		$tested_parts          = explode( '.', $version_info->tested );

		// The current WordPress version is x.y.z, so update the tested version to match it.
		if ( isset( $current_version_parts[2] ) && $current_version_parts[0] === $tested_parts[0] && $current_version_parts[1] === $tested_parts[1] ) {
			$tested_parts[2] = $current_version_parts[2];
		}

		return implode( '.', $tested_parts );
	}

	/**
	 * Show the update notification on multisite sub-sites.
	 *
	 * @param string $file
	 * @param array  $plugin
	 */
	public function show_update_notification( $file, $plugin ) {

		// Return early if in the network admin, or if this is not a multisite installation.
		if ( is_network_admin() || ! is_multisite() ) {
			return;
		}

		// Allow single site admins to see that an update is available.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( $this->name !== $file ) {
			return;
		}

		// Do not print any message if update does not exist.
		$update_cache = get_site_transient( 'update_plugins' );

		if ( ! isset( $update_cache->response[ $this->name ] ) ) {
			if ( ! is_object( $update_cache ) ) {
				$update_cache = new stdClass();
			}
			$update_cache->response[ $this->name ] = $this->get_repo_api_data();
		}

		// Return early if this plugin isn't in the transient->response or if the site is running the current or newer version of the plugin.
		if ( empty( $update_cache->response[ $this->name ] ) || version_compare( $this->version, $update_cache->response[ $this->name ]->new_version, '>=' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">', esc_attr( $this->slug ), esc_attr( $file ), in_array( $this->name, $this->get_active_plugins(), true ) ? 'active' : 'inactive' );

		echo '<td colspan="3" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt"><p>';

		$changelog_link = '';
		if ( ! empty( $update_cache->response[ $this->name ]->sections->changelog ) ) {
			$changelog_link = add_query_arg( [
				'edd_sl_action' => 'view_plugin_changelog',
				'plugin'        => urlencode( $this->name ),
				'slug'          => urlencode( $this->slug ),
				'TB_iframe'     => 'true',
				'width'         => 77,
				'height'        => 911
			], self_admin_url( 'index.php' ) );
		}
		$update_link = add_query_arg( [
			'action' => 'upgrade-plugin',
			'plugin' => urlencode( $this->name )
		], self_admin_url( 'update.php' ) );

		printf( /* translators: the plugin name. */ esc_html__( 'There is a new version of %1$s available.', 'betterdocs-pro' ), esc_html( $plugin['Name'] ) );

		if ( ! current_user_can( 'update_plugins' ) ) {
			echo ' ';
			esc_html_e( 'Contact your network administrator to install the update.', 'betterdocs-pro' );
		} elseif ( empty( $update_cache->response[ $this->name ]->package ) && ! empty( $changelog_link ) ) {
			echo ' ';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( /* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */ __( '%1$sView version %2$s details%3$s.', 'betterdocs-pro' ), '<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">', esc_html( $update_cache->response[ $this->name ]->new_version ), '</a>' );
		} elseif ( ! empty( $changelog_link ) ) {
			echo ' ';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( /* translators: 1: opening anchor tag for changelog, 2: new plugin version, 3: closing anchor tag, 4: opening anchor tag for update, 5: closing anchor tag */ __( '%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'betterdocs-pro' ), '<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">', esc_html( $update_cache->response[ $this->name ]->new_version ), '</a>', '<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">', '</a>' );
		} else {
			printf( ' %1$s%2$s%3$s', '<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">', esc_html__( 'Update now.', 'betterdocs-pro' ), '</a>' );
		}

		do_action( "in_plugin_update_message-$file", $plugin, $plugin );

		echo '</p></div></td></tr>';
	}

	/**
	 * Gets the plugins active in a multisite network.
	 *
	 * @return array
	 */
	private function get_active_plugins() {
		$active_plugins         = (array) get_option( 'active_plugins' );
		$active_network_plugins = (array) get_site_option( 'active_sitewide_plugins' );

		return array_merge( $active_plugins, array_keys( $active_network_plugins ) );
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @param mixed  $_data
	 * @param string $_action
	 * @param object $_args
	 *
	 * @return object $_data
	 * @uses api_request()
	 *
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		if ( 'plugin_information' !== $_action ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->slug ) ) {
			return $_data;
		}

		$to_send = [
			'slug'   => $this->slug,
			'is_ssl' => is_ssl(),
			'fields' => [
				'banners' => [],
				'reviews' => false,
				'icons'   => []
			]
		];

		// Get the transient where we store the api request for this plugin for 24 hours
		$edd_api_request_transient = $this->get_cached_version_info();

		//If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
		if ( empty( $edd_api_request_transient ) ) {
			$api_response = $this->api_request( 'plugin_information', $to_send );

			// Expires in 3 hours
			$this->set_version_info_cache( $api_response );

			if ( false !== $api_response ) {
				$_data = $api_response;
			}
		} else {
			$_data = $edd_api_request_transient;
		}

		// Convert sections into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
			$_data->sections = $this->convert_object_to_array( $_data->sections );
		}

		// Convert banners into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
			$_data->banners = $this->convert_object_to_array( $_data->banners );
		}

		// Convert icons into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
			$_data->icons = $this->convert_object_to_array( $_data->icons );
		}

		// Convert contributors into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
			$_data->contributors = $this->convert_object_to_array( $_data->contributors );
		}

		if ( ! isset( $_data->plugin ) ) {
			$_data->plugin = $this->name;
		}

		return $_data;
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API
	 *
	 * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
	 * decoding, they are objects. This method allows us to pass in the object and return an associative array.
	 *
	 * @param stdClass $data
	 *
	 * @return array
	 * @since 3.6.5
	 *
	 */
	private function convert_object_to_array( $data ) {
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return [];
		}
		$new_data = [];
		foreach ( $data as $key => $value ) {
			$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
		}

		return $new_data;
	}

	/**
	 * Applies the store's SSL verification preference to package download requests.
	 *
	 * NOT REGISTERED. init() hooks only pre_set_site_transient_update_plugins,
	 * plugins_api, after_plugin_row and admin_init — this method is never called,
	 * and it is kept only because it is public API that a consumer could have
	 * wired up itself. Package downloads are verified by WordPress's own default
	 * (WP_Http defaults sslverify to true and nothing here overrides it), so
	 * hooking this would add no protection and would only expose a way to turn
	 * verification off via edd_sl_api_request_verify_ssl.
	 *
	 * Do not cite this method as the reason package downloads are verified.
	 *
	 * @deprecated Never active in this fork.
	 *
	 * @param array  $args
	 * @param string $url
	 *
	 * @return array $array
	 */
	public function http_request_args( $args, $url ) {
		if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
			$args['sslverify'] = $this->verify_ssl();
		}

		return $args;
	}

	/**
	 * Calls the API and, if successful, returns the object delivered by the API.
	 *
	 * @param string $_action The requested action.
	 * @param array  $_data Parameters for the API action.
	 *
	 * @return array|false|void
	 * @uses get_bloginfo()
	 * @uses wp_remote_post()
	 * @uses is_wp_error()
	 *
	 */
	private function api_request( $_action, $_data ) {
		$data = array_merge( $this->api_data, $_data );

		if ( $data['slug'] !== $this->slug ) {
			return;
		}

		// Don't allow a plugin to ping itself
		if ( trailingslashit( home_url() ) === $this->api_url ) {
			return false;
		}

		if ( $this->request_recently_failed() ) {
			return false;
		}

		return $this->get_version_from_remote();
	}

	/**
	 * Determines if a request has recently failed.
	 *
	 * @return bool
	 * @since 1.9.1
	 *
	 */
	private function request_recently_failed() {
		$failed_request_details = get_option( $this->failed_request_cache_key );

		// Request has never failed.
		if ( empty( $failed_request_details ) || ! is_numeric( $failed_request_details ) ) {
			return false;
		}

		/*
		 * Request previously failed, but the timeout has expired.
		 * This means we're allowed to try again.
		 */
		if ( time() > $failed_request_details ) {
			delete_option( $this->failed_request_cache_key );

			return false;
		}

		return true;
	}

	/**
	 * Logs a failed HTTP request for this API URL.
	 * We set a timestamp for 1 hour from now. This prevents future API requests from being
	 * made to this domain for 1 hour. Once the timestamp is in the past, API requests
	 * will be allowed again. This way if the site is down for some reason we don't bombard
	 * it with failed API requests.
	 *
	 * @see EDD_SL_Plugin_Updater::request_recently_failed
	 *
	 * @since 1.9.1
	 */
	private function log_failed_request() {
		update_option( $this->failed_request_cache_key, strtotime( '+1 hour' ) );
	}

	/**
	 * If available, show the changelog for sites in a multisite installation.
	 */
	public function show_changelog() {

		if ( empty( $_REQUEST['edd_sl_action'] ) || 'view_plugin_changelog' !== $_REQUEST['edd_sl_action'] ) {
			return;
		}

		if ( empty( $_REQUEST['plugin'] ) ) {
			return;
		}

		if ( empty( $_REQUEST['slug'] ) || $this->slug !== $_REQUEST['slug'] ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to install plugin updates', 'betterdocs-pro' ), esc_html__( 'Error', 'betterdocs-pro' ), [ 'response' => 403 ] );
		}

		$version_info = $this->get_repo_api_data();
		if ( isset( $version_info->sections ) ) {
			$sections = $this->convert_object_to_array( $version_info->sections );
			if ( ! empty( $sections['changelog'] ) ) {
				echo '<div style="background:#fff;padding:10px;">' . wp_kses_post( $sections['changelog'] ) . '</div>';
			}
		}

		exit;
	}

	/**
	 * Gets the current version information from the remote site.
	 *
	 * @return array|false
	 */
	private function get_version_from_remote() {
		$api_params = [
			'edd_action'  => 'get_version',
			'license'     => ! empty( $this->api_data['license'] ) ? $this->api_data['license'] : '',
			'item_name'   => isset( $this->api_data['item_name'] ) ? $this->api_data['item_name'] : false,
			'item_id'     => isset( $this->api_data['item_id'] ) ? $this->api_data['item_id'] : false,
			'version'     => isset( $this->api_data['version'] ) ? $this->api_data['version'] : false,
			'slug'        => $this->slug,
			'author'      => $this->api_data['author'],
			'url'         => home_url(),
			'beta'        => $this->beta,
			'php_version' => phpversion(),
			'wp_version'  => get_bloginfo( 'version' )
		];

		/**
		 * Filters the parameters sent in the API request.
		 *
		 * @param array  $api_params The array of data sent in the request.
		 * @param array  $this ->api_data    The array of data set up in the class constructor.
		 * @param string $this ->plugin_file The full path and filename of the file.
		 */
		$api_params = apply_filters( 'edd_sl_plugin_updater_api_params', $api_params, $this->api_data, $this->plugin_file );

		// $update_endpoint = isset( $this->api_data['update_endpoint'] ) ? $this->api_data['update_endpoint'] : 'get-version';

		// Never is_ssl(): that reports the scheme of the *inbound* request to this
		// site, not the outbound connection to the store. Under WP-Cron, WP-CLI, or
		// behind a TLS-terminating proxy it is false, which would silently disable
		// certificate verification for exactly the requests that drive updates.
		$request = wp_remote_post( $this->api_url, [
			'timeout'   => 15,
			'sslverify' => $this->verify_ssl(),
			'body'      => $api_params
		] );

		if ( is_wp_error( $request ) || ( 200 !== wp_remote_retrieve_response_code( $request ) ) ) {
			$this->log_failed_request();

			return false;
		}

		$request = json_decode( wp_remote_retrieve_body( $request ) );

		if ( $request && isset( $request->sections ) ) {
			$request->sections = $this->safe_unserialize( $request->sections );
		} else {
			$request = false;
		}

		if ( $request && isset( $request->banners ) ) {
			$request->banners = $this->safe_unserialize( $request->banners );
		}

		if ( $request && isset( $request->icons ) ) {
			$request->icons = $this->safe_unserialize( $request->icons );
		}

		if ( ! empty( $request->sections ) ) {
			foreach ( $request->sections as $key => $section ) {
				$request->$key = (array) $section;
			}
		}

		return $this->sanitize_package_urls( $request );
	}

	/**
	 * Strips download URLs that do not point at an allowed host.
	 *
	 * The store's answer decides which archive WordPress downloads and unpacks over
	 * the plugin directory, so an unvalidated URL in that answer is arbitrary code
	 * execution. Verifying the certificate protects the response in transit; this
	 * bounds the damage if the response is forged anyway — by a compromised store,
	 * a hijacked DNS record, or any other break in that trust.
	 *
	 * A rejected URL is emptied rather than discarding the whole response: the
	 * update then shows as available without a download link (a case
	 * show_update_notification() already renders) instead of the plugin silently
	 * dropping off the update channel.
	 *
	 * @param mixed $version_info The decoded store response.
	 *
	 * @return mixed The same value, with disallowed download URLs emptied.
	 */
	private function sanitize_package_urls( $version_info ) {
		if ( ! is_object( $version_info ) ) {
			return $version_info;
		}

		// 'package' drives the update transient; 'download_link' drives the
		// "Install Update Now" button on the plugin information modal.
		foreach ( [ 'package', 'download_link' ] as $field ) {
			if ( empty( $version_info->$field ) ) {
				continue;
			}

			if ( $this->is_allowed_package_url( $version_info->$field ) ) {
				continue;
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$rejected_host = wp_parse_url( (string) $version_info->$field, PHP_URL_HOST );

				// The full URL carries the license key as a query argument — log the
				// host only, which is all that is needed to allow it deliberately.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					sprintf(
						'WPDeveloper Licensing: refused update "%1$s" for %2$s — host "%3$s" is not allowed for store %4$s. Add it via the wpdeveloper_licensing_allowed_package_hosts filter if this is expected.',
						$field,
						$this->slug,
						empty( $rejected_host ) ? '(unparseable)' : $rejected_host,
						$this->api_url
					)
				);
			}

			$version_info->$field = '';
		}

		return $version_info;
	}

	/**
	 * Determines whether an update package may be downloaded from the given URL.
	 *
	 * Defaults to the store's own host, which is where EDD Software Licensing
	 * serves package downloads from. Redirects are still followed normally, so a
	 * store that hands off to S3 or another CDN behind its own URL keeps working;
	 * only a package URL that *starts* on a foreign host is refused.
	 *
	 * @param mixed $url The candidate download URL.
	 *
	 * @return bool
	 */
	private function is_allowed_package_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );

		if ( empty( $host ) || ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return false;
		}

		$store_host = wp_parse_url( $this->api_url, PHP_URL_HOST );
		$allowed    = empty( $store_host ) ? [] : [ strtolower( $store_host ) ];

		/**
		 * Filters the hosts an update package may be downloaded from.
		 *
		 * Subdomains of an allowed host are accepted; look-alike domains are not.
		 * Add an entry here when the store serves packages from a separate host,
		 * such as a CDN on a different domain.
		 *
		 * @param string[] $allowed Allowed hostnames. Defaults to the store host.
		 * @param string   $api_url The configured store URL.
		 * @param Updater  $updater The Updater instance.
		 */
		$allowed = apply_filters( 'wpdeveloper_licensing_allowed_package_hosts', $allowed, $this->api_url, $this );

		if ( ! is_array( $allowed ) ) {
			return false;
		}

		$host = strtolower( $host );

		foreach ( $allowed as $candidate ) {
			if ( ! is_string( $candidate ) || '' === $candidate ) {
				continue;
			}

			$candidate = strtolower( ltrim( $candidate, '.' ) );

			if ( $host === $candidate ) {
				return true;
			}

			// Match subdomains only. The leading dot is what stops
			// "evilwpdeveloper.com" from passing as "wpdeveloper.com".
			$suffix = '.' . $candidate;

			if ( strlen( $host ) > strlen( $suffix ) && substr( $host, - strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the version info from the cache, if it exists.
	 *
	 * @param string $cache_key
	 *
	 * @return mixed
	 */
	public function get_cached_version_info( $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		$cache = get_option( $cache_key );

		// Cache is expired
		if ( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
			return false;
		}

		// We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
		$cache['value'] = json_decode( $cache['value'] );
		if ( ! empty( $cache['value']->icons ) ) {
			$cache['value']->icons = (array) $cache['value']->icons;
		}

		// Re-check on read as well as on write: a response cached before this
		// validation existed would otherwise keep serving its package URL for the
		// remainder of the 3-hour window.
		return $this->sanitize_package_urls( $cache['value'] );
	}

	/**
	 * Adds the plugin version information to the database.
	 *
	 * @param string $value
	 * @param string $cache_key
	 */
	public function set_version_info_cache( $value = '', $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		$data = [
			'timeout' => strtotime( '+3 hours', time() ),
			'value'   => wp_json_encode( $value )
		];

		update_option( $cache_key, $data, 'no' );

		// Delete the duplicate option (legacy key formats)
		$legacy = $this->slug . $this->api_data['license'] . $this->beta;
		delete_option( 'edd_api_request_' . md5( serialize( $legacy ) ) );
		delete_option( 'edd_api_request_' . md5( $legacy ) );
	}

	/**
	 * Returns if the SSL of the store should be verified.
	 *
	 * @return bool
	 * @since  1.6.13
	 */
	private function verify_ssl() {
		return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
	}

	/**
	 * Safely unserializes data, preventing PHP object injection.
	 *
	 * @param mixed $data Data to unserialize.
	 *
	 * @return mixed
	 */
	private function safe_unserialize( $data ) {
		if ( ! is_string( $data ) || ! is_serialized( $data ) ) {
			return $data;
		}

		return unserialize( $data, [ 'allowed_classes' => false ] );
	}

	/**
	 * Gets the unique key (option name) for a plugin.
	 *
	 * @return string
	 * @since 1.9.0
	 */
	private function get_cache_key() {
		$string = $this->slug . $this->api_data['license'] . $this->beta;

		return 'edd_sl_' . md5( $string );
	}
}
