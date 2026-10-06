<?php
/**
 * MCP rate limiter — a per-IP lockout on FAILED token authentication.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The pairing token is a 256-bit secret, so online brute-forcing is already
 * infeasible. This limiter stops the cheaper abuse — a flood of bad-token
 * requests burning CPU and filling logs — and gives a rotated token's stale
 * clients a hard wall. Defence in depth, not the primary control.
 *
 * Model: count consecutive FAILED attempts per client IP in a rolling window
 * (transient-backed). At or past the threshold the IP is locked out for the rest
 * of that window; a successful authentication clears the counter immediately.
 *
 * "Failed" means a credential was **presented and rejected**. A request with no
 * `Authorization` header never reaches the counter — that is the first step of
 * the OAuth handshake (the client is asking for the RFC 9728 challenge), so
 * counting it would lock out every OAuth client during ordinary discovery.
 *
 * The window is fixed from the first failure: recording a failure never extends
 * it, so a stranded client that retries every few minutes cannot re-arm its own
 * lockout forever. Waiting out one window always works.
 *
 * Threshold and window come from the `BETTERDOCS_MCP_MAX_FAILS` /
 * `BETTERDOCS_MCP_LOCKOUT_SECONDS` constants, then the
 * `betterdocs_mcp_rate_limit` filter.
 *
 * @since 4.9.0
 */
final class MCPRateLimiter {

	/**
	 * Transient key prefix; the client-IP hash is appended.
	 *
	 * @since 4.9.0
	 */
	const PREFIX = 'betterdocs_mcp_rl_';

	/**
	 * Default: lock out after this many failed attempts.
	 *
	 * @since 4.9.0
	 */
	const DEFAULT_MAX_FAILS = 10;

	/**
	 * Default: lockout / rolling-window length, in seconds.
	 *
	 * @since 4.9.0
	 */
	const DEFAULT_LOCKOUT = 900;

	/**
	 * Is the current client locked out?
	 *
	 * Call this *before* comparing the token, so a locked IP never reaches the
	 * constant-time compare at all.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_locked() {
		$limits = self::limits();

		return self::attempts() >= $limits[0];
	}

	/**
	 * Record a failed attempt for the current client.
	 *
	 * @since 4.9.0
	 *
	 * @return bool True when this failure crossed into a lockout.
	 */
	public static function record_failure() {
		$limits = self::limits();
		$max    = $limits[0];
		$window = $limits[1];

		$key   = self::key();
		$entry = get_transient( $key );

		if ( is_array( $entry ) && isset( $entry['count'], $entry['until'] ) ) {
			++$entry['count'];

			// Preserve the ORIGINAL window end: the TTL is the remaining time
			// only, never a fresh full window.
			$remaining = max( 1, (int) $entry['until'] - time() );

			set_transient( $key, $entry, $remaining );

			return $entry['count'] >= $max;
		}

		// First failure in a window. This also migrates a legacy integer entry:
		// a stale int simply restarts as a fresh window of one.
		$entry = [
			'count' => 1,
			'until' => time() + $window
		];

		set_transient( $key, $entry, $window );

		return 1 >= $max;
	}

	/**
	 * Clear the counter for the current client — call on a successful auth.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function clear() {
		delete_transient( self::key() );
	}

	/**
	 * Seconds until the current client's window ends.
	 *
	 * Falls back to the full window length when no entry exists — honest, now
	 * that the window is fixed.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	public static function retry_after() {
		$entry = get_transient( self::key() );

		if ( is_array( $entry ) && isset( $entry['until'] ) ) {
			return max( 1, (int) $entry['until'] - time() );
		}

		$limits = self::limits();

		return $limits[1];
	}

	/**
	 * How many clients are currently locked out, across all IPs.
	 *
	 * A diagnostic for the self-test: a healthy loopback plus a locked-out
	 * remote client is exactly the state a stranded connector produces — a
	 * rotated-away token, still retrying — and it is otherwise invisible.
	 *
	 * Returns null under a persistent object cache: transients do not live in
	 * the options table there, so the count is unknowable and claiming zero
	 * would be a lie.
	 *
	 * @since 4.9.0
	 *
	 * @return int|null Locked-out client count, or null when unknowable.
	 */
	public static function active_lockouts() {
		if ( wp_using_ext_object_cache() ) {
			return null;
		}

		global $wpdb;

		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_col' ) ) {
			return null;
		}

		$limits = self::limits();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- read-only diagnostic scan over transient rows; no core API enumerates them.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::PREFIX ) . '%'
			)
		);

		$locked = 0;

		foreach ( (array) $rows as $row ) {
			$entry = maybe_unserialize( $row );
			$count = is_array( $entry ) && isset( $entry['count'] )
				? (int) $entry['count']
				: ( is_numeric( $entry ) ? (int) $entry : 0 );

			if ( $count >= $limits[0] ) {
				++$locked;
			}
		}

		return $locked;
	}

	/**
	 * Current failed-attempt count for this client (0 when none).
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	private static function attempts() {
		$value = get_transient( self::key() );

		if ( is_array( $value ) && isset( $value['count'] ) ) {
			return (int) $value['count'];
		}

		// Legacy integer entries, from before the fixed-window format.
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Transient key bound to the hashed client IP.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function key() {
		return self::PREFIX . md5( self::client_ip() );
	}

	/**
	 * Resolve `[ max_fails, lockout_seconds ]` from the constants, then filter.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private static function limits() {
		$max    = defined( 'BETTERDOCS_MCP_MAX_FAILS' ) ? (int) BETTERDOCS_MCP_MAX_FAILS : self::DEFAULT_MAX_FAILS;
		$window = defined( 'BETTERDOCS_MCP_LOCKOUT_SECONDS' ) ? (int) BETTERDOCS_MCP_LOCKOUT_SECONDS : self::DEFAULT_LOCKOUT;

		/**
		 * Filter the MCP failed-auth rate limit.
		 *
		 * @since 4.9.0
		 *
		 * @param array $limits `[ max_fails, lockout_seconds ]`.
		 */
		$limits = (array) apply_filters( 'betterdocs_mcp_rate_limit', [ $max, $window ] );

		$max    = isset( $limits[0] ) ? max( 1, (int) $limits[0] ) : self::DEFAULT_MAX_FAILS;
		$window = isset( $limits[1] ) ? max( 1, (int) $limits[1] ) : self::DEFAULT_LOCKOUT;

		return [ $max, $window ];
	}

	/**
	 * Best-effort client IP.
	 *
	 * `REMOTE_ADDR` only — `X-Forwarded-For` is spoofable, so trusting it would
	 * let an attacker dodge the limit or lock out a victim. Behind a reverse
	 * proxy every client shares one `REMOTE_ADDR` and therefore one bucket; such
	 * a site should set `REMOTE_ADDR` upstream, or opt in through the filter
	 * below, which is safe only when the proxy is known to overwrite the
	 * forwarded header.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function client_ip() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- used only as a rate-limit bucket key (md5'd), never output or stored raw.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

		/**
		 * Filter the IP used as the MCP rate-limit bucket key.
		 *
		 * An opt-in escape hatch for sites behind a trusted reverse proxy, where
		 * `REMOTE_ADDR` is the proxy and every client would otherwise collapse
		 * into a single bucket. Only return a forwarded header's value when the
		 * proxy is known to overwrite it.
		 *
		 * @since 4.9.0
		 *
		 * @param string $ip Resolved `REMOTE_ADDR` ('' when unavailable).
		 */
		$ip = (string) apply_filters( 'betterdocs_mcp_client_ip', $ip );

		return '' !== $ip ? $ip : 'unknown';
	}
}
