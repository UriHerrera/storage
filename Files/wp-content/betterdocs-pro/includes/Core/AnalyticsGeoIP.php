<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * GeoIP enrichment via a bundled MaxMind GeoLite2-Country database
 * (Advanced Analytics v1.0).
 *
 * The reader library (MaxMind\Db\Reader, MIT) is bundled in libs/maxmind-db;
 * the database itself is NOT shipped (MaxMind licensing). A monthly Action
 * Scheduler job downloads/refreshes GeoLite2-Country.mmdb into uploads when both
 * a `maxmind_account_id` and a `maxmind_license_key` setting are present. Until a
 * database exists, country resolution degrades gracefully to '' — no errors, no
 * per-request HTTP calls.
 *
 * Download auth: MaxMind retired the legacy license-key-only permalink
 * (download.maxmind.com/app/geoip_download?license_key=…) in 2024 — it now 401s
 * even for valid keys. The current endpoint is
 * download.maxmind.com/geoip/databases/GeoLite2-Country/download?suffix=tar.gz
 * and requires HTTP Basic auth with the Account ID + License Key; it 302-redirects
 * to a presigned Cloudflare R2 URL that carries its own query-string auth (so the
 * Basic header must NOT be forwarded to it).
 *
 * Resolution runs at event-collection time (raw IP still available) via the
 * `betterdocs_analytics_event_country` filter; only the country code is stored.
 */
class AnalyticsGeoIP {
	const UPDATE_HOOK = 'betterdocs_analytics_geoip_update';

	/**
	 * @var \MaxMind\Db\Reader|null|false false = not yet attempted.
	 */
	private $reader = false;

	public function __construct() {
		add_filter( 'betterdocs_analytics_event_country', [ $this, 'country_for_ip' ], 10, 2 );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::UPDATE_HOOK, [ $this, 'update_database' ] );
		// Saving the MaxMind credentials must activate GeoIP right away — not wait
		// up to a day/month for the recurring refresh (QA #4). Also acts as
		// credential validation: the download 401s on a bad Account ID / License
		// Key and the outcome lands in the status.
		add_action( 'betterdocs::settings::saved', [ $this, 'on_settings_saved' ], 10, 3 );
	}

	/**
	 * After a settings save: when either MaxMind credential (Account ID or License
	 * Key) was added or changed, kick off the database download asynchronously
	 * (falls back to inline when Action Scheduler isn't loaded yet).
	 *
	 * @param bool  $saved
	 * @param array $new_settings
	 * @param array $old_settings
	 */
	public function on_settings_saved( $saved, $new_settings = [], $old_settings = [] ) {
		$new_key = isset( $new_settings['maxmind_license_key'] ) ? trim( (string) $new_settings['maxmind_license_key'] ) : '';
		$old_key = isset( $old_settings['maxmind_license_key'] ) ? trim( (string) $old_settings['maxmind_license_key'] ) : '';
		$new_acc = isset( $new_settings['maxmind_account_id'] ) ? trim( (string) $new_settings['maxmind_account_id'] ) : '';
		$old_acc = isset( $old_settings['maxmind_account_id'] ) ? trim( (string) $old_settings['maxmind_account_id'] ) : '';
		// Nothing to do without a license key, or when neither credential changed.
		if ( '' === $new_key || ( $new_key === $old_key && $new_acc === $old_acc ) ) {
			return;
		}
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::UPDATE_HOOK, [], 'betterdocs' );
		} else {
			$this->update_database();
		}
	}

	/**
	 * Current GeoIP status for the settings UI / REST (QA #1/#6): whether the
	 * credentials are set, the database exists, and the last download attempt's
	 * outcome. `account_id_set` lets the UI prompt legacy license-key-only installs
	 * to add the Account ID MaxMind now requires (QA round-3 #4).
	 *
	 * @return array{key_set:bool,account_id_set:bool,db_present:bool,last_checked:int,last_updated:int,last_error:string}
	 */
	public static function status() {
		$saved = (array) get_option( 'betterdocs_geoip_status', [] );
		$error = (string) ( $saved['last_error'] ?? '' );

		$key_set        = '' !== trim( (string) betterdocs()->settings->get( 'maxmind_license_key', '' ) );
		$account_id_set = '' !== trim( (string) betterdocs()->settings->get( 'maxmind_account_id', '' ) );

		// With no license key there are no credentials to have been rejected — drop a
		// stale last_error (e.g. a prior 401) so clearing the key returns the UI to a
		// clean "add your credentials" empty state instead of a lingering failure.
		if ( ! $key_set ) {
			$error = '';
		}

		// A mask-shaped stored credential is a corrupted remnant of a pre-fix save
		// (the settings guard now prevents new ones). Report it as "not set" with an
		// explicit re-enter message instead of letting downloads 401 mysteriously.
		if ( self::credentials_corrupted() ) {
			return [
				'key_set'        => false,
				'account_id_set' => false,
				'db_present'     => self::is_available(),
				'last_checked'   => (int) ( $saved['last_checked'] ?? 0 ),
				'last_updated'   => (int) ( $saved['last_updated'] ?? 0 ),
				'last_error'     => __( 'Your stored MaxMind credentials were corrupted by an earlier save — please re-enter your Account ID and License Key.', 'betterdocs-pro' ),
			];
		}

		// Legacy installs that saved a license key before the Account ID field
		// existed can never download (MaxMind's key-only endpoint was retired) —
		// surface a clear "add your Account ID" prompt over the stale last_error.
		if ( $key_set && ! $account_id_set ) {
			$error = __( 'MaxMind now requires an Account ID alongside the license key. Add your MaxMind Account ID to enable GeoIP downloads.', 'betterdocs-pro' );
		}

		return [
			'key_set'        => $key_set,
			'account_id_set' => $account_id_set,
			'db_present'     => self::is_available(),
			'last_checked'   => (int) ( $saved['last_checked'] ?? 0 ),
			'last_updated'   => (int) ( $saved['last_updated'] ?? 0 ),
			'last_error'     => $error,
		];
	}

	/**
	 * Whether either stored MaxMind credential (Account ID or License Key) is a
	 * persisted mask (contains a run of asterisks — no real credential does). See
	 * the sensitive-key guard in Free's Core\Settings; credentials corrupted before
	 * that fix stay broken until the user re-enters them (round-2 follow-up #1).
	 */
	public static function credentials_corrupted() {
		$key = trim( (string) betterdocs()->settings->get( 'maxmind_license_key', '' ) );
		$acc = trim( (string) betterdocs()->settings->get( 'maxmind_account_id', '' ) );
		return ( '' !== $key && (bool) preg_match( '/\*{4,}/', $key ) )
			|| ( '' !== $acc && (bool) preg_match( '/\*{4,}/', $acc ) );
	}

	/**
	 * Persist the outcome of a download attempt (surfaced by status()).
	 *
	 * @param string $error Empty string on success.
	 */
	protected function record_attempt( $error = '' ) {
		$saved                 = (array) get_option( 'betterdocs_geoip_status', [] );
		$saved['last_checked'] = time();
		$saved['last_error']   = (string) $error;
		if ( '' === $error ) {
			$saved['last_updated'] = time();
		}
		update_option( 'betterdocs_geoip_status', $saved, false );
	}

	/**
	 * Absolute path to the GeoLite2-Country database in uploads.
	 */
	public function db_path() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['basedir'] ) . 'betterdocs/geoip/GeoLite2-Country.mmdb';
	}

	/**
	 * Whether GeoIP country resolution is available right now — i.e. the
	 * GeoLite2-Country database exists and is readable. Static + stateless so
	 * callers (e.g. the engagement REST endpoint) can check it without
	 * instantiating this hook-registering class.
	 *
	 * @return bool
	 */
	public static function is_available() {
		$uploads = wp_upload_dir();
		$path    = trailingslashit( $uploads['basedir'] ) . 'betterdocs/geoip/GeoLite2-Country.mmdb';
		return is_readable( $path );
	}

	/**
	 * Filter callback: resolve a country ISO code for an IP, or '' if unavailable.
	 *
	 * @param string $country Incoming value (short-circuits if already set).
	 * @param string $ip      Raw client IP.
	 */
	public function country_for_ip( $country, $ip = '' ) {
		if ( ! empty( $country ) || empty( $ip ) ) {
			return $country;
		}

		$reader = $this->get_reader();
		if ( ! $reader ) {
			return '';
		}

		try {
			$record = $reader->get( $ip );
			if ( is_array( $record ) && ! empty( $record['country']['iso_code'] ) ) {
				return (string) $record['country']['iso_code'];
			}
		} catch ( \Throwable $e ) {
			// Invalid IP / address not in DB — silently degrade.
		}

		return '';
	}

	/**
	 * Lazily build the reader. Returns null when no DB or reader is available.
	 *
	 * @return \MaxMind\Db\Reader|null
	 */
	protected function get_reader() {
		if ( false !== $this->reader ) {
			return $this->reader;
		}

		$this->reader = null;

		$path = $this->db_path();
		if ( ! is_readable( $path ) ) {
			return null;
		}

		// Reuse an already-loaded reader (C extension / WooCommerce) when present.
		if ( ! class_exists( '\\MaxMind\\Db\\Reader' ) ) {
			$base = BETTERDOCS_PRO_ABSPATH . 'libs/maxmind-db/src/MaxMind/Db/';
			foreach (
				[
					'Reader/Util.php',
					'Reader/InvalidDatabaseException.php',
					'Reader/Metadata.php',
					'Reader/Decoder.php',
					'Reader.php'
				] as $file
			) {
				if ( file_exists( $base . $file ) ) {
					require_once $base . $file;
				}
			}
		}

		if ( ! class_exists( '\\MaxMind\\Db\\Reader' ) ) {
			return null;
		}

		try {
			$this->reader = new \MaxMind\Db\Reader( $path );
		} catch ( \Throwable $e ) {
			$this->reader = null;
		}

		return $this->reader;
	}

	/**
	 * Schedule the monthly database refresh once.
	 */
	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		// No license key -> nothing to refresh; don't schedule a pointless monthly
		// job (QA #7). The save-time trigger schedules the first download instead.
		if ( '' === trim( (string) betterdocs()->settings->get( 'maxmind_license_key', '' ) ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( self::UPDATE_HOOK ) ) {
			as_schedule_recurring_action( time() + DAY_IN_SECONDS, MONTH_IN_SECONDS, self::UPDATE_HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Download + extract GeoLite2-Country.mmdb when the MaxMind credentials are
	 * configured. No-op without them; failures degrade quietly (country stays '').
	 */
	public function update_database() {
		$license_key = trim( (string) betterdocs()->settings->get( 'maxmind_license_key', '' ) );
		$account_id  = trim( (string) betterdocs()->settings->get( 'maxmind_account_id', '' ) );

		if ( '' === $license_key ) {
			return false;
		}

		// Never call MaxMind with a corrupted (mask-shaped) credential — it can only 401.
		if ( self::credentials_corrupted() ) {
			$this->record_attempt( __( 'Your stored MaxMind credentials were corrupted by an earlier save — please re-enter your Account ID and License Key.', 'betterdocs-pro' ) );
			return false;
		}

		// MaxMind's current download endpoint requires HTTP Basic auth with BOTH the
		// Account ID and the License Key. Without an Account ID the request can only
		// 401 — surface a configuration prompt rather than an opaque "unauthorized"
		// so legacy license-key-only installs know what to add (QA round-3 #4).
		if ( '' === $account_id ) {
			$this->record_attempt( __( 'MaxMind now requires an Account ID alongside the license key. Add your MaxMind Account ID to enable GeoIP downloads.', 'betterdocs-pro' ) );
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$tmp = $this->download_archive( $account_id, $license_key );
		if ( is_wp_error( $tmp ) ) {
			$this->record_attempt( $this->map_download_error( $tmp ) );
			return false;
		}

		$extracted = false;
		try {
			if ( ! class_exists( '\PharData' ) ) {
				throw new \RuntimeException( __( 'The PHP Phar extension is not available on this server, so the downloaded database could not be extracted.', 'betterdocs-pro' ) );
			}

			$dest_dir = dirname( $this->db_path() );
			wp_mkdir_p( $dest_dir );

			$phar = new \PharData( $tmp );
			foreach ( new \RecursiveIteratorIterator( $phar ) as $file ) {
				if ( substr( $file->getFilename(), -5 ) === '.mmdb' ) {
					$extracted = copy( $file->getPathname(), $this->db_path() );
					break;
				}
			}

			$this->reader = false; // force a reload on next lookup

			$this->record_attempt(
				$extracted ? '' : __( 'The downloaded archive did not contain a database file.', 'betterdocs-pro' )
			);
		} catch ( \Throwable $e ) {
			// Extraction failed — leave any existing DB in place, surface the reason.
			$this->record_attempt(
				sprintf(
					/* translators: %s: extraction error message */
					__( 'Database extraction failed: %s', 'betterdocs-pro' ),
					$e->getMessage()
				)
			);
		}

		@unlink( $tmp );
		return $extracted;
	}

	/**
	 * Fetch the GeoLite2-Country tar.gz from MaxMind's current endpoint into a temp
	 * file. Authenticates with HTTP Basic (Account ID + License Key); download_url()
	 * can't set auth headers, so this uses wp_remote_get with a streamed body.
	 *
	 * The endpoint 302-redirects to a presigned Cloudflare R2 URL. We follow that
	 * redirect manually (redirection => 0 on the authenticated request) so the
	 * Authorization header is NOT forwarded to R2 — presigned S3/R2 URLs reject
	 * requests that carry both query-string auth and an Authorization header.
	 *
	 * @param string $account_id
	 * @param string $license_key
	 * @return string|\WP_Error Temp file path on success.
	 */
	protected function download_archive( $account_id, $license_key ) {
		$endpoint = add_query_arg(
			[ 'suffix' => 'tar.gz' ],
			'https://download.maxmind.com/geoip/databases/GeoLite2-Country/download'
		);

		$auth = 'Basic ' . base64_encode( $account_id . ':' . $license_key );

		$response = wp_remote_get(
			$endpoint,
			[
				'timeout'     => 30,
				'redirection' => 0,
				'headers'     => [ 'Authorization' => $auth ],
			]
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		// Redirect: MaxMind hands back a presigned R2 URL — stream it with no auth header.
		if ( $status >= 300 && $status < 400 ) {
			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( empty( $location ) || is_array( $location ) ) {
				return new \WP_Error( 'geoip_no_location', __( 'MaxMind returned a redirect without a download URL.', 'betterdocs-pro' ) );
			}

			// This target is followed manually and its body is streamed to disk
			// and then unpacked, so it must not be able to point the server at
			// internal infrastructure or downgrade the transfer to plaintext.
			$location = \WP_Http::make_absolute_url( $location, $endpoint );
			if ( ! $this->is_safe_download_url( $location ) ) {
				return new \WP_Error( 'geoip_bad_location', __( 'MaxMind redirected to an unsupported download URL.', 'betterdocs-pro' ) );
			}

			return $this->stream_to_temp( $location, [] );
		}

		// Some setups may stream the archive directly on the authenticated request.
		if ( 200 === $status ) {
			return $this->stream_to_temp( $endpoint, [ 'Authorization' => $auth ] );
		}

		// 401/403 = bad Account ID / License Key; anything else = server/network issue.
		return new \WP_Error(
			'geoip_http',
			sprintf(
				/* translators: %d: HTTP status code returned by MaxMind */
				__( 'MaxMind returned HTTP %d.', 'betterdocs-pro' ),
				$status
			),
			[ 'status' => $status ]
		);
	}

	/**
	 * Whether a download URL is safe to fetch.
	 *
	 * Requires HTTPS and a host that does not resolve to a private, reserved or
	 * link-local address. The MaxMind endpoint itself is a fixed constant, but
	 * the presigned URL it redirects to is attacker-influenceable by anyone who
	 * can tamper with that response.
	 *
	 * @param string $url
	 * @return bool
	 */
	protected function is_safe_download_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return false;
		}

		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return false;
		}

		if ( strtolower( $parts['scheme'] ) !== 'https' ) {
			return false;
		}

		$host = $parts['host'];
		$ip   = filter_var( $host, FILTER_VALIDATE_IP ) ? $host : gethostbyname( $host );

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			// Unresolvable — let the HTTP layer fail it rather than guessing.
			return true;
		}

		return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Stream a URL's body to a temp file via wp_remote_get, returning the path.
	 *
	 * @param string $url
	 * @param array  $headers Extra request headers (empty for presigned R2 URLs).
	 * @return string|\WP_Error
	 */
	protected function stream_to_temp( $url, $headers = [] ) {
		$tmp = wp_tempnam( 'betterdocs-geoip' );
		if ( ! $tmp ) {
			return new \WP_Error( 'geoip_tmp', __( 'Could not create a temporary file for the GeoIP download.', 'betterdocs-pro' ) );
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $tmp,
				'headers'  => $headers,
			]
		);
		if ( is_wp_error( $response ) ) {
			@unlink( $tmp );
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			@unlink( $tmp );
			return new \WP_Error(
				'geoip_http',
				sprintf(
					/* translators: %d: HTTP status code returned while downloading */
					__( 'MaxMind returned HTTP %d.', 'betterdocs-pro' ),
					$status
				),
				[ 'status' => $status ]
			);
		}

		return $tmp;
	}

	/**
	 * Turn a download WP_Error into a user-facing status message. A 401/403 is the
	 * credential-validation signal (QA #2) — bad Account ID or License Key; anything
	 * else is a download/network problem (QA #6).
	 *
	 * @param \WP_Error $error
	 * @return string
	 */
	protected function map_download_error( \WP_Error $error ) {
		$data   = $error->get_error_data();
		$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 0;

		if ( 401 === $status || 403 === $status ) {
			return __( 'MaxMind rejected your credentials. Check that the Account ID and License Key are both correct.', 'betterdocs-pro' );
		}

		return sprintf(
			/* translators: %s: error message from the download attempt */
			__( 'Database download failed: %s', 'betterdocs-pro' ),
			$error->get_error_message()
		);
	}
}
