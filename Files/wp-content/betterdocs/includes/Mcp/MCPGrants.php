<?php
/**
 * MCP grant invalidation when a user is deleted or demoted.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * An MCP grant is a credential that acts *as a person*. When that person is
 * deleted, or demoted below the `edit_docs` floor {@see MCPServer::impersonate()}
 * enforces, the grant has to stop being a credential.
 *
 * `MCPServer` already refuses such a call at request time with a typed
 * `capability_missing`, so nothing unsafe happens without this class. What this
 * adds is that the grant *disappears* — from `betterdocs_mcp_oauth`, and so from
 * the admin's Connected Apps list. A dead grant sitting in a list an admin reads
 * to answer "who can reach this site?" is a wrong answer wearing the shape of a
 * right one, and a re-promoted user would silently get their old client back.
 *
 * Hooks, all after WordPress has finished the change:
 *
 * - `deleted_user`     — revoke that user's OAuth grants; if the site's pairing
 *                        token was minted by them, disconnect it too.
 * - `set_user_role`    — the user's roles were replaced.
 * - `remove_user_role` — one role was taken away.
 *
 * The two role hooks re-ask `user_can( $id, 'edit_docs' )` rather than reasoning
 * about the role that changed: a user may hold several roles, and only the
 * resulting capability matters.
 *
 * @since 4.9.0
 */
final class MCPGrants {

	/**
	 * Whether the hooks are already attached.
	 *
	 * On a Pro site the container resolves two `Roles` objects and could resolve
	 * this more than once; revocation is destructive enough to be worth making
	 * literally-once rather than merely idempotent.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private static $hooked = false;

	/**
	 * Attach the hooks. Called from `MCPManager::__construct()`.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_action( 'deleted_user', [ __CLASS__, 'on_user_deleted' ] );
		add_action( 'set_user_role', [ __CLASS__, 'on_role_changed' ] );
		add_action( 'remove_user_role', [ __CLASS__, 'on_role_changed' ] );
	}

	/**
	 * A user was deleted: nothing they granted may survive them.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id Deleted user.
	 * @return bool Whether anything was revoked.
	 */
	public static function on_user_deleted( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			return false;
		}

		$revoked = self::revoke_oauth( $user_id );
		$revoked = self::disconnect_pairing_of( $user_id ) || $revoked;

		if ( $revoked ) {
			self::log( sprintf( 'Revoked MCP grants for deleted user #%d.', $user_id ) );
		}

		return $revoked;
	}

	/**
	 * A user's roles changed: revoke only if they fell below the floor.
	 *
	 * Fires for both `set_user_role` and `remove_user_role`, which is why the
	 * second parameter is ignored — the question is never "which role went
	 * away?" but "can this user still be impersonated?".
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User whose roles changed.
	 * @return bool Whether anything was revoked.
	 */
	public static function on_role_changed( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			return false;
		}

		$capability = class_exists( __NAMESPACE__ . '\\MCPServer' )
			? MCPServer::IMPERSONATION_CAPABILITY
			: 'edit_docs';

		if ( user_can( $user_id, $capability ) ) {
			return false;
		}

		$revoked = self::revoke_oauth( $user_id );
		$revoked = self::disconnect_pairing_of( $user_id ) || $revoked;

		if ( $revoked ) {
			self::log(
				sprintf(
					'Revoked MCP grants for user #%1$d, who no longer holds "%2$s".',
					$user_id,
					$capability
				)
			);
		}

		return $revoked;
	}

	/**
	 * Drop every OAuth code, access token and refresh token issued to a user.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User id.
	 * @return bool Whether anything was removed.
	 */
	private static function revoke_oauth( $user_id ) {
		if ( ! class_exists( __NAMESPACE__ . '\\MCPOAuth' ) ) {
			return false;
		}

		return (bool) MCPOAuth::revoke_user( $user_id );
	}

	/**
	 * Disconnect the site's pairing token when this user is the one it runs as.
	 *
	 * A pairing token is an admin-equivalent credential impersonating a user who
	 * no longer qualifies, so it goes — leaving it alive because it "only"
	 * belongs to a deleted account is how a site keeps answering MCP calls
	 * nobody owns.
	 *
	 * It goes with `disconnect( false )`, not the kill switch (ADR-042). The
	 * default `disconnect()` also calls `MCPOAuth::revoke_all()`, which is
	 * right for the admin pressing Disconnect and wrong here: this class has
	 * already revoked *this* user's grants with `revoke_user()`, and deleting
	 * one account must not disconnect everybody else's AI client from the site.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User id.
	 * @return bool Whether the pairing was disconnected.
	 */
	private static function disconnect_pairing_of( $user_id ) {
		if ( ! class_exists( __NAMESPACE__ . '\\MCPPairing' ) ) {
			return false;
		}

		if ( ! MCPPairing::is_connected() || MCPPairing::user_id() !== (int) $user_id ) {
			return false;
		}

		MCPPairing::disconnect( false );

		return true;
	}

	/**
	 * WP_DEBUG-only diagnostic. Never logs a token or a code.
	 *
	 * @since 4.9.0
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private static function log( $message ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
		error_log( '[BD-MCP] ' . $message );
	}
}
