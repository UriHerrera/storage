<?php
/**
 * Encrypt-at-rest for plugin secrets.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Libsodium `secretbox` under a key derived from the site's auth salt, marked
 * by a `bdenc:v1:` prefix, so anything holding a credential can store it
 * without keeping its own copy of the crypto.
 *
 * What this protects: a leaked **database**. A backup, a shared-host read,
 * another plugin's SQL injection — none of them yield a usable secret. What it
 * does not protect: a leaked **server**, because the key is derived from
 * `wp-config.php`.
 *
 * Two behaviours are deliberate and load-bearing:
 *
 * - **Plaintext passthrough.** Where `sodium` is missing or `wp_salt()` is
 *   unavailable, `encrypt()` returns the value unchanged rather than storing
 *   nothing. A host that cannot encrypt keeps working; the value simply lacks
 *   the prefix, and `decrypt()` recognises it as legacy plaintext.
 * - **A ciphertext that will not open returns `''`, never itself.** The auth
 *   salt was rotated, or the row is corrupt. Handing the envelope back would
 *   send it upstream as if it were the credential and produce an opaque failure
 *   a long way from the cause.
 *
 * Because of the second rule, a class storing an authenticator must keep a
 * separate SHA-256 of it ({@see \WPDeveloper\BetterDocs\Mcp\MCPPairing}):
 * verification then survives a salt rotation that makes the display copy
 * unrecoverable.
 *
 * `final`, static, no hooks.
 *
 * @since 4.9.0
 */
final class SecretAtRest {

	/**
	 * Marks a value as encrypted by this class.
	 *
	 * @since 4.9.0
	 */
	const PREFIX = 'bdenc:v1:';

	/**
	 * Encrypt a secret for storage.
	 *
	 * @since 4.9.0
	 *
	 * @param string $value Raw secret.
	 * @return string Ciphertext envelope, or the value unchanged where this
	 *                host cannot encrypt.
	 */
	public static function encrypt( $value ) {
		$value = (string) $value;

		if ( '' === $value || ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return $value;
		}

		$key = self::key();

		if ( '' === $key ) {
			return $value;
		}

		try {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $value, $nonce, $key );

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transport encoding for a sodium ciphertext, not obfuscation.
			return self::PREFIX . base64_encode( $nonce . $cipher );
		} catch ( \Exception $e ) {
			return $value;
		}
	}

	/**
	 * Decrypt a value written by {@see self::encrypt()}.
	 *
	 * A value without the marker is legacy plaintext and is returned untouched.
	 * A marked value that will not open returns `''` — see the class docblock.
	 *
	 * @since 4.9.0
	 *
	 * @param string $value Stored value.
	 * @return string Plaintext, the original value, or ''.
	 */
	public static function decrypt( $value ) {
		$value = (string) $value;

		if ( ! self::is_encrypted( $value ) ) {
			return $value;
		}

		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return $value;
		}

		$key = self::key();

		if ( '' === $key ) {
			return $value;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodes our own ciphertext envelope; strict mode is on.
		$decoded = base64_decode( substr( $value, strlen( self::PREFIX ) ), true );

		if ( false === $decoded || strlen( $decoded ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}

		$nonce  = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $key );

		return false === $plain ? '' : $plain;
	}

	/**
	 * Whether a stored value carries this class's marker.
	 *
	 * @since 4.9.0
	 *
	 * @param string $value Stored value.
	 * @return bool
	 */
	public static function is_encrypted( $value ) {
		return 0 === strncmp( (string) $value, self::PREFIX, strlen( self::PREFIX ) );
	}

	/**
	 * Derive the 32-byte key from the site's auth salt.
	 *
	 * SHA-256's output length is `SODIUM_CRYPTO_SECRETBOX_KEYBYTES`, so the
	 * raw digest is the key.
	 *
	 * @since 4.9.0
	 *
	 * @return string Raw 32-byte key, or '' when salts are unavailable.
	 */
	private static function key() {
		if ( ! function_exists( 'wp_salt' ) ) {
			return '';
		}

		$salt = (string) wp_salt( 'auth' );

		if ( '' === $salt ) {
			return '';
		}

		return hash( 'sha256', 'betterdocs-mcp|' . $salt, true );
	}
}
