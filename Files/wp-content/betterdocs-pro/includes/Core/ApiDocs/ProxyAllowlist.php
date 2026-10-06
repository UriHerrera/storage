<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Documentation — the Try-it proxy's per-reference host allowlist.
 *
 * The proxy forwards a reader's "Try it" request server-side (so the browser's
 * CORS policy can't block it), and will only ever call a host the reference's
 * owner listed. Both the switch and the list live on the reference itself —
 * meta `_bd_api_proxy_enabled` / `_bd_api_proxy_hosts`, edited in the API Docs
 * Create/Edit drawer — so two APIs on one site can't reach each other's hosts.
 *
 * Entries are normalized to bare hostnames on the way in AND on the way out,
 * because "the host you call" is not what people type: `https://app.example.com`,
 * `app.example.com/v1`, `*.example.com` and `example.com:8443` all mean the same
 * host here, and matching them literally against `wp_parse_url(…, PHP_URL_HOST)`
 * silently denies every request.
 */
final class ProxyAllowlist {

	/**
	 * Is the proxy switched on for this reference?
	 *
	 * @param int $reference_id
	 * @return bool
	 */
	public static function enabled_for( $reference_id ) {
		$reference_id = (int) $reference_id;

		if ( ! $reference_id ) {
			return false;
		}

		return '1' === (string) get_post_meta( $reference_id, '_bd_api_proxy_enabled', true );
	}

	/**
	 * How long a proxy grant stays valid. Two ticks are accepted on verify, so
	 * the effective window is 12–24h — long enough that a cached docs page keeps
	 * working, short enough that a leaked token expires on its own.
	 */
	const TOKEN_TTL = 43200; // 12 hours.

	/**
	 * A short-lived grant binding "may use the proxy" to ONE reference.
	 *
	 * The proxy is reader-facing and therefore unauthenticated, which previously
	 * made it callable by anyone who knew the route: enabling it for a single
	 * API opened a server-side request primitive to that API's allowlisted hosts
	 * for the whole internet. A WP nonce is not sufficient on its own here —
	 * core only checks `X-WP-Nonce` when a cookie is present, so an anonymous
	 * caller skips it entirely, and an anonymous nonce is one shared value for
	 * the whole site rather than something scoped to a reference.
	 *
	 * This token is emitted only where the Try-it button is actually rendered,
	 * so holding one means "I loaded a page that offers this API's playground".
	 * It is not a substitute for the host allowlist or the SSRF guard — both
	 * still run on every hop — it just closes the drive-by surface.
	 *
	 * @param int $reference_id
	 * @param int $offset Ticks into the past (1 = the previous window).
	 * @return string
	 */
	public static function token( $reference_id, $offset = 0 ) {
		$tick = (int) floor( time() / self::TOKEN_TTL ) - (int) $offset;

		return substr( wp_hash( 'bd_api_proxy|' . (int) $reference_id . '|' . $tick, 'nonce' ), -16 );
	}

	/**
	 * @param int    $reference_id
	 * @param string $token
	 * @return bool
	 */
	public static function verify_token( $reference_id, $token ) {
		$token = (string) $token;

		if ( '' === $token ) {
			return false;
		}

		// Accept the previous window too, so a page loaded just before a tick
		// boundary does not break mid-session.
		foreach ( [ 0, 1 ] as $offset ) {
			if ( hash_equals( self::token( $reference_id, $offset ), $token ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The reference's allowed hosts, normalized.
	 *
	 * @param int $reference_id
	 * @return string[]
	 */
	public static function hosts_for( $reference_id ) {
		return self::parse( (string) get_post_meta( (int) $reference_id, '_bd_api_proxy_hosts', true ) );
	}

	/**
	 * May the proxy forward to this URL on behalf of this reference?
	 *
	 * @param int    $reference_id
	 * @param string $target Full target URL.
	 * @return bool
	 */
	public static function allows( $reference_id, $target ) {
		$host = self::normalize( (string) wp_parse_url( $target, PHP_URL_HOST ) );

		if ( '' === $host ) {
			return false;
		}

		/**
		 * Filter the allowed hosts for one reference (array of bare hostnames).
		 * Lets code permit a host without it being stored on the reference.
		 *
		 * @param string[] $hosts
		 * @param string   $target       Target URL.
		 * @param int      $reference_id Owning API reference.
		 */
		$hosts = apply_filters(
			'betterdocs_api_ref_proxy_allowlist',
			self::hosts_for( $reference_id ),
			$target,
			(int) $reference_id
		);

		foreach ( (array) $hosts as $allowed ) {
			$allowed = self::normalize( $allowed );

			if ( '' === $allowed ) {
				continue;
			}

			// Exact host, or a subdomain of an allowed host.
			if ( $host === $allowed || substr( $host, - ( strlen( $allowed ) + 1 ) ) === '.' . $allowed ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Split a raw textarea value into normalized hosts.
	 *
	 * @param string $raw One per line, or comma/space separated.
	 * @return string[] Unique, in the order given.
	 */
	public static function parse( $raw ) {
		$hosts = array();

		foreach ( preg_split( '/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY ) as $entry ) {
			$host = self::normalize( $entry );

			if ( '' !== $host && ! in_array( $host, $hosts, true ) ) {
				$hosts[] = $host;
			}
		}

		return $hosts;
	}

	/**
	 * Hosts back to the stored/edited form — one per line.
	 *
	 * @param string[] $hosts
	 * @return string
	 */
	public static function format( array $hosts ) {
		return implode( "\n", $hosts );
	}

	/**
	 * One entry → a bare lowercase hostname.
	 *
	 * Accepts `https://app.example.com/v1?x=1`, `app.example.com:8443`,
	 * `*.example.com`, `EXAMPLE.com.` and plain `example.com`.
	 *
	 * @param string $value
	 * @return string '' when nothing host-like is left.
	 */
	public static function normalize( $value ) {
		$value = strtolower( trim( (string) $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( false !== strpos( $value, '//' ) ) {
			// wp_parse_url needs a scheme to see a host in `//example.com`.
			$parsed = wp_parse_url( 0 === strpos( $value, '//' ) ? 'https:' . $value : $value, PHP_URL_HOST );
			$value  = null === $parsed ? '' : (string) $parsed;
		}

		// Strip anything after the authority, then userinfo and port.
		$value = preg_replace( '#[/?\#].*$#', '', $value );
		$value = preg_replace( '#^.*@#', '', (string) $value );
		$value = preg_replace( '#:\d+$#', '', (string) $value );

		// A leading wildcard is redundant — subdomains already match.
		$value = preg_replace( '#^\*\.#', '', (string) $value );
		$value = trim( (string) $value, '.' );

		// Reject anything that isn't plausibly a hostname (or an IP literal).
		if ( ! preg_match( '/^[a-z0-9._-]+$/', $value ) || false === strpos( $value, '.' ) ) {
			return '';
		}

		return $value;
	}
}
