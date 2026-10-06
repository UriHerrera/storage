<?php
/**
 * MCP pairing lifecycle — mint / rotate / revoke the per-site connection token.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Core\SecretAtRest;

/**
 * The "paste one string and you are connected" path, for the clients that do
 * not speak OAuth: an administrator clicks Connect, this class mints a 32-byte
 * secret, and the user pastes either the connect URL (token in the path) or the
 * endpoint plus a Bearer header into their AI client. `MCPServer` validates it
 * directly — no hosted infrastructure is involved.
 *
 * The token is admin-equivalent and never expires: `MCPServer` runs the call as
 * the user who minted it, so this one string reaches every write tool. It is
 * therefore stored as two separate values (ADR-007). The plaintext copy is never
 * the credential of record:
 *
 * - `token_hash` — SHA-256, and the only thing authentication compares.
 * - `site_token` — the same token under {@see SecretAtRest}, kept only so the
 *   UI, the config snippets, the AI prompt and the self-test can show it.
 *
 * Separating them is the point. Verifying against the hash rather than a
 * decrypted copy means a rotated auth salt — which makes the ciphertext
 * unrecoverable — does not lock out clients already holding a valid token; they
 * keep authenticating, and the UI reports the value as unavailable until an
 * administrator rotates it.
 *
 * A row written before this (plaintext, no hash) verifies once against the
 * plaintext and is upgraded in place, so nobody has to re-pair.
 *
 * State lives in the non-autoloaded option `betterdocs_mcp_pairing`:
 *
 *     {
 *       site_token:   string  ciphertext (`bdenc:v1:…`) of the secret,
 *       token_hash:   string  sha256 of the secret — the authenticator,
 *       connected:    bool,
 *       connected_at: int     unix ts,
 *       scopes:       string[] e.g. ['read','write'],
 *       user_id:      int     who minted it; MCP calls run as them,
 *       last_used:    int     throttled to one write a minute
 *     }
 *
 * @since 4.9.0
 */
final class MCPPairing {

	/**
	 * Option key holding all MCP pairing state.
	 *
	 * @since 4.9.0
	 */
	const OPTION = 'betterdocs_mcp_pairing';

	/**
	 * Path segment of the pretty per-site endpoint.
	 *
	 * @since 4.9.0
	 */
	const SITE_ENDPOINT_PATH = 'betterdocs/mcp';

	/**
	 * Scopes granted on connect unless read-only was asked for.
	 *
	 * @since 4.9.0
	 */
	const DEFAULT_SCOPES = [ 'read', 'write' ];

	/**
	 * Throttle window, in seconds, for `last_used` writes — at most one option
	 * write a minute, so a busy client cannot turn every call into a database
	 * write.
	 *
	 * @since 4.9.0
	 */
	const LAST_USED_THROTTLE = 60;

	/**
	 * The primary endpoint the user pastes into their AI client.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function site_endpoint() {
		return home_url( '/' . self::SITE_ENDPOINT_PATH );
	}

	/**
	 * Always-on fallback endpoint under the REST namespace, for hosts where the
	 * pretty rewrite cannot be served (plain permalinks, for instance).
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function site_endpoint_fallback() {
		return rest_url( 'betterdocs/v1/mcp' );
	}

	/**
	 * The single URL that carries the token in its path. Empty when not
	 * connected, or when the display copy cannot be decrypted.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function connect_url() {
		$token = self::site_token();

		if ( '' === $token ) {
			return '';
		}

		return self::site_endpoint() . '/' . $token;
	}

	/**
	 * Current pairing state, defaults merged.
	 *
	 * @since 4.9.0
	 *
	 * @return array {
	 *     @type string   $site_token   Decrypted token, '' when unrecoverable.
	 *     @type string   $token_hash   SHA-256 verifier.
	 *     @type bool     $connected    Whether a pairing is active.
	 *     @type int      $connected_at Unix timestamp of the first connect.
	 *     @type string[] $scopes       Granted scopes.
	 *     @type int      $user_id      User the connection runs as.
	 *     @type int      $last_used    Unix timestamp of the last MCP call.
	 * }
	 */
	public static function state() {
		$stored = get_option( self::OPTION, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$raw = isset( $stored['site_token'] ) ? (string) $stored['site_token'] : '';

		return [
			// Decrypted for display and for the self-test's own probe. Stored
			// encrypted (ADR-007) — a database read on its own no longer yields
			// a usable, admin-equivalent credential.
			'site_token'   => '' === $raw ? '' : SecretAtRest::decrypt( $raw ),
			// What verify_token() compares against. Held separately so a token
			// whose ciphertext can no longer be opened keeps authenticating the
			// clients already configured with it.
			'token_hash'   => isset( $stored['token_hash'] ) ? (string) $stored['token_hash'] : '',
			'connected'    => ! empty( $stored['connected'] ),
			'connected_at' => isset( $stored['connected_at'] ) ? (int) $stored['connected_at'] : 0,
			'scopes'       => isset( $stored['scopes'] ) && is_array( $stored['scopes'] )
				? array_values( array_map( 'strval', $stored['scopes'] ) )
				: [],
			'user_id'      => isset( $stored['user_id'] ) ? (int) $stored['user_id'] : 0,
			'last_used'    => isset( $stored['last_used'] ) ? (int) $stored['last_used'] : 0
		];
	}

	/**
	 * Whether a presented token is this site's pairing token.
	 *
	 * Compared against the stored hash in constant time. A row written before
	 * ADR-007 holds a plaintext token and no hash: it is verified against the
	 * plaintext once and then upgraded in place.
	 *
	 * @since 4.9.0
	 *
	 * @param string $presented Token presented by the client.
	 * @return bool
	 */
	public static function verify_token( $presented ) {
		$presented = (string) $presented;

		if ( '' === $presented ) {
			return false;
		}

		$state = self::state();

		if ( '' !== $state['token_hash'] ) {
			return hash_equals( $state['token_hash'], self::hash( $presented ) );
		}

		// Legacy row: plaintext, no hash.
		if ( '' === $state['site_token'] || ! hash_equals( $state['site_token'], $presented ) ) {
			return false;
		}

		self::upgrade_legacy_storage( $presented );

		return true;
	}

	/**
	 * Record that the pairing token was just used to authenticate an MCP call.
	 *
	 * Throttled to at most one option write per `LAST_USED_THROTTLE` seconds.
	 * No-op when nothing is stored.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function touch_last_used() {
		$stored = get_option( self::OPTION, [] );

		if ( ! is_array( $stored ) || empty( $stored['site_token'] ) ) {
			return;
		}

		$now  = time();
		$last = isset( $stored['last_used'] ) ? (int) $stored['last_used'] : 0;

		if ( $now - $last < self::LAST_USED_THROTTLE ) {
			return;
		}

		$stored['last_used'] = $now;

		update_option( self::OPTION, $stored, false );
	}

	/**
	 * The pairing token in the clear, for display only.
	 *
	 * Empty when not connected, and empty when the ciphertext will not open —
	 * the salt was rotated, or the row was migrated without `wp-config.php`.
	 * Never use this to authenticate; use {@see self::verify_token()}.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function site_token() {
		return self::state()['site_token'];
	}

	/**
	 * The user the connection runs as (the token's minter).
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	public static function user_id() {
		return self::state()['user_id'];
	}

	/**
	 * Whether a pairing token is active for this site.
	 *
	 * Deliberately asks the *stored* credential, not the decrypted display
	 * copy: a site whose auth salt changed still has a working pairing.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_connected() {
		$state = self::state();

		return $state['connected'] && ( '' !== $state['token_hash'] || '' !== $state['site_token'] );
	}

	/**
	 * Whether the active pairing is limited to read-only tools.
	 *
	 * **False when nothing is paired.** An absent pairing is not a read-only
	 * pairing: `MCPTools::is_read_only()` falls back here only when no OAuth
	 * scope override is set, which on a real request means the pairing path,
	 * where a pairing exists by definition. Answering `true` for an unpaired
	 * site would silently hide every write tool from `tools/list` in the admin
	 * UI, the health report and wp-cli.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_read_only() {
		if ( ! self::is_connected() ) {
			return false;
		}

		return ! in_array( 'write', self::state()['scopes'], true );
	}

	/**
	 * Sanitised snapshot for the MCP admin page.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public static function public_status() {
		$state = self::state();

		return [
			'connected'         => self::is_connected(),
			'connection_token'  => $state['site_token'],
			// Distinguishes "not connected" from "connected, but the display
			// copy cannot be decrypted" — the salt-rotation case, where the
			// pairing still authenticates and the UI must offer a rotate
			// rather than claim there is no connection.
			'token_available'   => '' !== $state['site_token'],
			'connect_url'       => self::connect_url(),
			'mcp_endpoint'      => self::site_endpoint(),
			'mcp_endpoint_rest' => self::site_endpoint_fallback(),
			'connected_at'      => $state['connected_at'],
			'last_used'         => $state['last_used'],
			'scopes'            => $state['scopes'],
			'read_only'         => self::is_read_only(),
			// Ready-to-paste connection recipes (header-based — the secret
			// stays out of URLs, so it cannot leak into server or proxy logs).
			'config'            => self::config_snippets(),
			// A drop-in instruction the user can paste into their AI client so
			// it sets the connection up itself. The default is token-less and
			// leaves the client to walk the OAuth flow; the second carries the
			// connection token, for a machine with no browser (ADR-055).
			'ai_prompt'         => self::ai_prompt(),
			'ai_prompt_token'   => self::ai_prompt_token()
		];
	}

	/**
	 * Ready-to-paste connection recipes for the dashboard, both header-based.
	 * Empty strings when the token is unavailable.
	 *
	 * @since 4.9.0
	 *
	 * @return array {
	 *     @type string $cli  Claude Code one-liner.
	 *     @type string $json Portable `mcpServers` block.
	 * }
	 */
	public static function config_snippets() {
		$token = self::site_token();

		if ( '' === $token ) {
			return [
				'cli'  => '',
				'json' => ''
			];
		}

		$endpoint = self::site_endpoint();

		// Claude Code one-liner. The CLI requires the positional NAME and URL
		// BEFORE any flags (`claude mcp add <name> <url> --flags`).
		$cli = sprintf(
			'claude mcp add betterdocs %s --transport http --header "Authorization: Bearer %s"',
			$endpoint,
			$token
		);

		// Portable mcpServers JSON block (Claude Desktop and other clients).
		$json = wp_json_encode(
			[
				'mcpServers' => [
					'betterdocs' => [
						'url'     => $endpoint,
						'headers' => [
							'Authorization' => 'Bearer ' . $token
						]
					]
				]
			],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		return [
			'cli'  => $cli,
			'json' => is_string( $json ) ? $json : ''
		];
	}

	/**
	 * A copy-paste instruction the user hands to their AI assistant so *it*
	 * sets the connection up. Empty when not connected.
	 *
	 * **Carries no credential.** The endpoint answers an unauthenticated call
	 * with `401` + `WWW-Authenticate: Bearer resource_metadata=…`, which is the
	 * challenge that starts the OAuth flow, so an assistant needs the URL and
	 * nothing else (ADR-054/ADR-055). {@see self::ai_prompt_token()} is the
	 * variant for a machine with no browser to approve in.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function ai_prompt() {
		if ( ! self::is_connected() ) {
			return '';
		}

		$endpoint = self::site_endpoint();

		$lines = [
			'Add the following remote MCP server to your connections so you can manage the documentation on my WordPress site, then use it.',
			'',
			'Server name: BetterDocs',
			'Server URL: ' . $endpoint,
			'Transport: streamable HTTP',
			'Authentication: OAuth — when you first connect, the server answers with a sign-in link; open it, sign in to the WordPress site and approve.',
			'Access level: as the approving user.',
			'',
			'If you use the Claude Code CLI, this is the exact command:',
			'  claude mcp add --transport http betterdocs ' . $endpoint,
			'Then run /mcp and choose BetterDocs to approve.',
			'',
			'Add it now, confirm it is connected by calling its "bd-list-docs" tool, and tell me which docs you can see.'
		];

		$prompt = implode( "\n", $lines );

		/**
		 * Filter the copy-paste AI setup prompt shown on the MCP page.
		 *
		 * @since 4.9.0
		 *
		 * @param string $prompt    The default prompt text.
		 * @param bool   $read_only Whether the connection is read-only.
		 */
		return (string) apply_filters( 'betterdocs_mcp_ai_prompt', $prompt, self::is_read_only() );
	}

	/**
	 * The same instruction with the connection token in it, for a machine that
	 * cannot open a browser to approve an OAuth grant.
	 *
	 * It lives behind the MCP page's
	 * "No browser on that machine?" disclosure and is never rendered until
	 * someone opens it (ADR-055) — the read-only wording stays here rather than
	 * in {@see self::ai_prompt()} because scope is a property of a *pairing
	 * token*; an OAuth grant acts as the user who approved it. Empty when not
	 * connected, or when the stored token cannot be decrypted.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function ai_prompt_token() {
		if ( ! self::is_connected() ) {
			return '';
		}

		$token = self::site_token();

		if ( '' === $token ) {
			return '';
		}

		$endpoint = self::site_endpoint();
		$access   = self::is_read_only()
			? 'read-only (read docs, FAQs, categories, settings and analytics)'
			: 'read-write (can create and update docs, FAQs, categories and settings)';

		$lines = [
			'Add the following remote MCP server to your connections so you can manage the documentation on my WordPress site, then use it.',
			'',
			'Server name: BetterDocs',
			'Server URL: ' . $endpoint,
			'Transport: streamable HTTP',
			'Authentication: Bearer token (in the Authorization header)',
			'API key: ' . $token,
			'Access level: ' . $access,
			'',
			'If you use the Claude Code CLI, this is the exact command (name and URL come BEFORE the flags):',
			'  ' . self::config_snippets()['cli'],
			'',
			'Add it now, confirm it is connected by calling its "bd-list-docs" tool, and tell me which docs you can see.'
		];

		$prompt = implode( "\n", $lines );

		/**
		 * Filter the token-bearing variant of the AI setup prompt.
		 *
		 * Deliberately a second filter rather than a third argument on
		 * `betterdocs_mcp_ai_prompt`: a callback already registered against that
		 * filter would otherwise rewrite both texts at once, and the two say
		 * different things about authentication (ADR-055).
		 *
		 * @since 4.9.0
		 *
		 * @param string $prompt    The default token-bearing prompt text.
		 * @param bool   $read_only Whether the connection is read-only.
		 */
		return (string) apply_filters( 'betterdocs_mcp_ai_prompt_token', $prompt, self::is_read_only() );
	}

	/**
	 * Connect — mint a connection token for this site's MCP endpoint.
	 *
	 * Idempotent: re-connecting keeps the existing token and its scopes, so a
	 * paired client is not silently broken. Use {@see self::rotate()} to change
	 * either.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $read_only Grant only the `read` scope on a NEW token.
	 * @return array Public status.
	 */
	public static function connect( $read_only = false ) {
		$state = self::state();

		if ( '' !== $state['token_hash'] || '' !== $state['site_token'] ) {
			$stored = get_option( self::OPTION, [] );

			if ( ! is_array( $stored ) ) {
				$stored = [];
			}

			// The credential itself is left exactly as stored — including a
			// ciphertext this site can no longer open, whose hash still
			// authenticates every configured client. Minting a replacement is
			// what rotate() is for, never a side effect of pressing Connect.
			$stored['connected']    = true;
			$stored['connected_at'] = $state['connected_at'] ? $state['connected_at'] : time();
			$stored['scopes']       = ! empty( $state['scopes'] )
				? $state['scopes']
				: self::scopes_for( (bool) $read_only );
			$stored['user_id']      = $state['user_id'] ? $state['user_id'] : get_current_user_id();

			update_option( self::OPTION, $stored, false );

			return self::public_status();
		}

		$token = self::mint_token();

		update_option(
			self::OPTION,
			[
				'site_token'   => SecretAtRest::encrypt( $token ),
				'token_hash'   => self::hash( $token ),
				'connected'    => true,
				'connected_at' => time(),
				'scopes'       => self::scopes_for( (bool) $read_only ),
				'user_id'      => get_current_user_id(),
				'last_used'    => 0
			],
			false
		);

		return self::public_status();
	}

	/**
	 * Rotate — mint a brand-new token, invalidating the previous one
	 * immediately. The leaked-token remedy; optionally flips read-only.
	 *
	 * @since 4.9.0
	 *
	 * @param bool|null $read_only Null keeps the current scopes; true/false sets them.
	 * @return array Public status, with the fresh token.
	 */
	public static function rotate( $read_only = null ) {
		$state  = self::state();
		$scopes = null === $read_only
			? ( ! empty( $state['scopes'] ) ? $state['scopes'] : self::DEFAULT_SCOPES )
			: self::scopes_for( (bool) $read_only );

		$token = self::mint_token();

		update_option(
			self::OPTION,
			[
				'site_token'   => SecretAtRest::encrypt( $token ),
				'token_hash'   => self::hash( $token ),
				'connected'    => true,
				'connected_at' => time(),
				'scopes'       => $scopes,
				'user_id'      => get_current_user_id() ? get_current_user_id() : $state['user_id'],
				'last_used'    => 0
			],
			false
		);

		return self::public_status();
	}

	/**
	 * Disconnect — revoke the pairing token and, by default, every OAuth grant,
	 * so the admin page's Disconnect button is a single kill switch for all MCP
	 * access.
	 *
	 * `$revoke_oauth` exists for the one caller that must not be a kill switch:
	 * {@see MCPGrants::disconnect_pairing_of()} fires from `deleted_user` and
	 * has already revoked *that* user's own grants with
	 * `MCPOAuth::revoke_user()`. Deleting one account must not disconnect every
	 * other person's AI client from the site (ADR-042).
	 *
	 * @since 4.9.0
	 *
	 * @param bool $revoke_oauth Whether to revoke every OAuth grant as well.
	 *                           Default true — the kill-switch behaviour the
	 *                           admin page and the REST route rely on.
	 * @return array Public status after disconnecting.
	 */
	public static function disconnect( $revoke_oauth = true ) {
		delete_option( self::OPTION );

		if ( $revoke_oauth ) {
			MCPOAuth::revoke_all();
		}

		return self::public_status();
	}

	/**
	 * Map a read-only flag to the granted scope list.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $read_only Whether to grant read-only access.
	 * @return string[]
	 */
	private static function scopes_for( $read_only ) {
		return $read_only ? [ 'read' ] : self::DEFAULT_SCOPES;
	}

	/**
	 * Mint a 32-byte random token (64 hex characters).
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function mint_token() {
		return bin2hex( random_bytes( 32 ) );
	}

	/**
	 * SHA-256 verifier for the pairing token at rest.
	 *
	 * Mirrors `MCPOAuth`'s own hashing, which has always stored access and
	 * refresh tokens this way.
	 *
	 * @since 4.9.0
	 *
	 * @param string $value Raw token.
	 * @return string
	 */
	private static function hash( $value ) {
		return hash( 'sha256', (string) $value );
	}

	/**
	 * Re-store a legacy plaintext token encrypted, with its hash.
	 *
	 * @since 4.9.0
	 *
	 * @param string $token Raw token, already verified.
	 * @return void
	 */
	private static function upgrade_legacy_storage( $token ) {
		$stored = get_option( self::OPTION, [] );

		if ( ! is_array( $stored ) ) {
			return;
		}

		$stored['site_token'] = SecretAtRest::encrypt( $token );
		$stored['token_hash'] = self::hash( $token );

		update_option( self::OPTION, $stored, false );
	}
}
