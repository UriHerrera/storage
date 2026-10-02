<?php
/**
 * MCP server — the per-site JSON-RPC endpoint.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\AbilitiesRegistrar;

/**
 * BetterDocs speaks MCP directly at this site's own URL
 * (`https://thissite.com/betterdocs/mcp`), so no hosted broker is in the path.
 * MCP's Streamable-HTTP transport is JSON-RPC 2.0 over HTTP POST; this class is
 * the small server surface an AI client needs:
 *
 *   - `initialize`      → protocol version, capabilities, serverInfo, instructions
 *   - `ping`            → `{}`
 *   - `tools/list`      → {@see MCPTools::list()}
 *   - `tools/call`      → {@see MCPTools::invoke()}, wrapped as MCP content
 *   - `notifications/*` → acknowledged with no body (202)
 *   - batches           → an array of messages, notifications dropped from the reply
 *
 * **Auth** is a Bearer credential, either the static pairing token
 * ({@see MCPPairing}) or an OAuth 2.1 access token ({@see MCPOAuth}). On
 * success the request runs *as* the granting user, so every ability's own
 * `current_user_can()` check still applies and a credential can never exceed
 * what the person who granted it could do themselves. The impersonation floor is
 * `edit_docs`, not `manage_options` (ADR-006): BetterDocs' capability model has
 * real non-admin API users.
 *
 * A grant whose user no longer qualifies is **dead, not absent**: it answers
 * `403` with a typed `capability_missing`, never a bare `401`. The difference
 * matters to a client — a 401 says "authenticate", and an OAuth client would
 * loop through the whole flow again to arrive at the same place.
 *
 * An unauthenticated call gets `401` plus the RFC 9728 `WWW-Authenticate`
 * challenge pointing at {@see MCPOAuth::resource_metadata_url()} — the REST
 * alias, so hosts that intercept `/.well-known/` still work (ADR-014). A request
 * with **no** credential never counts against the rate limiter: that is the
 * opening move of the OAuth flow, not a guess.
 *
 * @since 4.9.0
 */
final class MCPServer {

	/**
	 * MCP protocol version this server implements.
	 *
	 * @since 4.9.0
	 */
	const PROTOCOL_VERSION = '2025-06-18';

	/**
	 * The capability a credential's user must still hold for the grant to be
	 * alive (ADR-006). Deliberately not `manage_options`: BetterDocs' capability
	 * model has real non-admin API users. Every ability then re-checks its own,
	 * finer capability on top of this floor.
	 *
	 * @since 4.9.0
	 */
	const IMPERSONATION_CAPABILITY = 'edit_docs';

	/**
	 * JSON-RPC error codes. The last two are in the implementation-defined
	 * `-32000..-32099` range.
	 *
	 * @since 4.9.0
	 */
	const PARSE_ERROR      = -32700;
	const INVALID_REQUEST  = -32600;
	const METHOD_NOT_FOUND = -32601;
	const INVALID_PARAMS   = -32602;
	const DISABLED         = -32000;
	const UNAUTHORIZED     = -32001;

	/**
	 * Handle one MCP HTTP request.
	 *
	 * Reads the JSON-RPC message from the request body, authenticates, and
	 * dispatches. Notifications get a bodyless 202.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Incoming request (raw body).
	 * @return \WP_REST_Response
	 */
	public static function handle( $request ) {
		// A previous request in this process may have left an override behind;
		// this one's credential is the only thing that may decide it.
		MCPTools::set_read_only_override( null );

		self::debug_tap( $request );

		// The admin toggle is the master switch: off means no MCP surface at
		// all. It never gates ability registration or /mcp/health (ADR-013).
		if ( ! self::is_enabled() ) {
			return self::decorate(
				self::error_response(
					null,
					self::DISABLED,
					__( 'MCP is disabled on this site. Enable it under BetterDocs → MCP.', 'betterdocs' ),
					403
				)
			);
		}

		$presented = self::extract_token( $request );

		// Lockout first, so a rate-limited IP never reaches the compare. Only a
		// client that actually presented something can be locked out.
		if ( '' !== $presented && MCPRateLimiter::is_locked() ) {
			$response = self::error_response(
				null,
				self::UNAUTHORIZED,
				__( 'Too many failed attempts. Try again later.', 'betterdocs' ),
				429
			);

			// Keep the challenge on the 429 as well: a client that only ever
			// sees a bare 429 concludes the server has no OAuth at all.
			$response->header( 'WWW-Authenticate', self::challenge_header() );
			$response->header( 'Retry-After', (string) MCPRateLimiter::retry_after() );

			return self::decorate( $response );
		}

		$user_id = self::authorize( $presented );

		if ( null === $user_id ) {
			if ( '' !== $presented ) {
				MCPRateLimiter::record_failure();
			}

			$response = self::error_response(
				null,
				self::UNAUTHORIZED,
				__( 'Unauthorized: invalid or missing connection token.', 'betterdocs' ),
				401
			);

			// RFC 9728: point OAuth-capable clients at the protected-resource
			// metadata so they can start the flow.
			$response->header( 'WWW-Authenticate', self::challenge_header() );

			return self::decorate( $response );
		}

		if ( ! self::impersonate( $user_id ) ) {
			// The credential is genuine; the user behind it is gone or no
			// longer allowed. That is a dead grant, not a missing one.
			return self::decorate( self::dead_grant_response() );
		}

		MCPRateLimiter::clear();

		$raw = (string) $request->get_body();
		$msg = json_decode( $raw, true );

		if ( null === $msg && JSON_ERROR_NONE !== json_last_error() ) {
			return self::decorate(
				self::error_response( null, self::PARSE_ERROR, __( 'Parse error: body is not valid JSON.', 'betterdocs' ), 400 )
			);
		}

		// A batch is an array of messages. Answer each one; per JSON-RPC, drop
		// the notifications from the reply.
		if ( is_array( $msg ) && array_key_exists( 0, $msg ) ) {
			$responses = [];

			foreach ( $msg as $one ) {
				$answer = self::dispatch( is_array( $one ) ? $one : [] );

				if ( null !== $answer ) {
					$responses[] = $answer;
				}
			}

			if ( empty( $responses ) ) {
				return self::decorate( new \WP_REST_Response( null, 202 ) );
			}

			return self::decorate( new \WP_REST_Response( $responses, 200 ) );
		}

		if ( ! is_array( $msg ) ) {
			return self::decorate(
				self::error_response( null, self::INVALID_REQUEST, __( 'Invalid request.', 'betterdocs' ), 400 )
			);
		}

		$response = self::dispatch( $msg );

		if ( null === $response ) {
			// A notification — acknowledged, with no body.
			return self::decorate( new \WP_REST_Response( null, 202 ) );
		}

		return self::decorate( new \WP_REST_Response( $response, 200 ) );
	}

	/**
	 * Whether the MCP endpoint is switched on.
	 *
	 * `MCPManager` owns the toggle and its filter; the setting is also read
	 * directly here, so this class is testable and measurable on its own. The two must agree — `MCPManager::is_enabled()` reads the same key.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private static function is_enabled() {
		if ( class_exists( __NAMESPACE__ . '\\MCPManager' ) && method_exists( __NAMESPACE__ . '\\MCPManager', 'is_enabled' ) ) {
			return (bool) MCPManager::is_enabled();
		}

		if ( ! function_exists( 'betterdocs' ) ) {
			return false;
		}

		$plugin = betterdocs();

		if ( ! is_object( $plugin ) || ! isset( $plugin->settings ) || ! is_object( $plugin->settings ) ) {
			return false;
		}

		return ! empty( $plugin->settings->get( 'enable_mcp' ) );
	}

	/**
	 * Dispatch one JSON-RPC message.
	 *
	 * @since 4.9.0
	 *
	 * @param array $msg Decoded JSON-RPC message.
	 * @return array|null The response envelope, or null for a notification.
	 */
	private static function dispatch( array $msg ) {
		$method = isset( $msg['method'] ) ? (string) $msg['method'] : '';
		$id     = isset( $msg['id'] ) ? $msg['id'] : null;
		$params = isset( $msg['params'] ) && is_array( $msg['params'] ) ? $msg['params'] : [];

		// A message with no `id` is a notification: acknowledged, never answered.
		$is_notification = ! array_key_exists( 'id', $msg );

		switch ( $method ) {
			case 'initialize':
				return self::result(
					$id,
					[
						'protocolVersion' => self::PROTOCOL_VERSION,
						'capabilities'    => [
							'tools' => [ 'listChanged' => false ]
						],
						'serverInfo'      => [
							'name'    => 'betterdocs',
							'version' => defined( 'BETTERDOCS_VERSION' ) ? BETTERDOCS_VERSION : '0.0.0'
						],
						'instructions'    => self::instructions()
					]
				);

			case 'ping':
				return self::result( $id, (object) [] );

			case 'tools/list':
				$tools = MCPTools::list();

				// An empty catalog while MCP is enabled means the Abilities
				// runtime never loaded, and the client sees a clean, useless
				// connection. Leave a trail for whoever debugs it; the health
				// report carries the loud version.
				if ( empty( $tools ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
					error_log( '[BD-MCP] tools/list returned 0 tools. ' . AbilitiesRegistrar::summary() );
				}

				return self::result( $id, [ 'tools' => $tools ] );

			case 'tools/call':
				return self::call_tool( $id, $params );

			default:
				if ( $is_notification || 0 === strpos( $method, 'notifications/' ) ) {
					return null;
				}

				return self::error( $id, self::METHOD_NOT_FOUND, 'Method not found: ' . $method );
		}
	}

	/**
	 * The `instructions` string handed to the client on `initialize`.
	 *
	 * One paragraph, because clients put it straight into the model's context.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function instructions() {
		return __( 'Call bd-get-status first: it reports the BetterDocs and BetterDocs Pro versions, which capabilities the connected user holds, and which features are switched on, so you can tell a refusal apart from a misconfiguration before you try anything. Every tool describes its own availability — a tool that needs BetterDocs Pro, or a setting that is currently off, says so in its description and returns a typed error explaining what would make it work. Prefer names over ids where a tool accepts both; it will find or create the matching term.', 'betterdocs' );
	}

	/**
	 * Run a `tools/call` and wrap the answer as MCP content.
	 *
	 * A tool-level failure is a *successful* JSON-RPC response carrying
	 * `isError: true` (per MCP), so the model reads the typed object instead of
	 * the transport swallowing it. The same object is sent twice: as JSON text,
	 * which every client renders, and as `structuredContent`, which the clients
	 * that understand it can act on (ADR-016).
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $id     JSON-RPC id.
	 * @param array $params `{ name: string, arguments: array }`.
	 * @return array
	 */
	private static function call_tool( $id, array $params ) {
		$name = isset( $params['name'] ) ? (string) $params['name'] : '';
		$args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : [];

		if ( '' === $name ) {
			return self::error( $id, self::INVALID_PARAMS, __( 'Missing tool name.', 'betterdocs' ) );
		}

		$result = MCPTools::invoke( $name, $args );

		if ( is_wp_error( $result ) ) {
			return self::result( $id, self::content( self::error_payload( $result ), true ) );
		}

		return self::result( $id, self::content( $result, false ) );
	}

	/**
	 * The typed object carried by a `WP_Error` from the ability layer.
	 *
	 * `AbilityError` always puts `error` and `message` in the data, but a
	 * `WP_Error` from anywhere else may not, so both are filled in from the code
	 * and the message when they are missing.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error The error.
	 * @return array
	 */
	private static function error_payload( \WP_Error $error ) {
		$data = $error->get_error_data();

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		if ( ! isset( $data['error'] ) ) {
			$data['error'] = (string) $error->get_error_code();
		}

		if ( ! isset( $data['message'] ) ) {
			$data['message'] = (string) $error->get_error_message();
		}

		return $data;
	}

	/**
	 * Wrap a payload as an MCP tool result.
	 *
	 * @since 4.9.0
	 *
	 * @param array $payload  Result or typed error object.
	 * @param bool  $is_error Whether this is a tool-level failure.
	 * @return array
	 */
	private static function content( array $payload, $is_error ) {
		return [
			'content'           => [
				[
					'type' => 'text',
					'text' => wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
				]
			],
			'structuredContent' => $payload,
			'isError'           => (bool) $is_error
		];
	}

	/**
	 * Validate the presented Bearer credential.
	 *
	 * Pairing token first, then OAuth. Each path sets the read-only override
	 * from its own grant, so the two can disagree without one leaking into the
	 * other.
	 *
	 * @since 4.9.0
	 *
	 * @param string $presented Credential from the request.
	 * @return int|null The granting user's id, or null when nothing matched.
	 */
	private static function authorize( $presented ) {
		$presented = (string) $presented;

		if ( '' === $presented ) {
			return null;
		}

		// Path 1: the static per-site pairing token. Compared through
		// verify_token(), which checks the stored hash — the token is encrypted
		// at rest and never compared in the clear (ADR-007).
		if ( MCPPairing::verify_token( $presented ) ) {
			MCPTools::set_read_only_override( MCPPairing::is_read_only() );
			MCPPairing::touch_last_used();

			return MCPPairing::user_id();
		}

		// Path 2: an OAuth 2.1 access token. Its own granted scope decides
		// read-only, independent of the pairing token's scopes.
		$grant = MCPOAuth::validate_token( $presented );

		if ( is_array( $grant ) ) {
			MCPTools::set_read_only_override( MCPOAuth::scope_is_read_only( $grant['scope'] ) );

			return (int) $grant['user_id'];
		}

		return null;
	}

	/**
	 * Run the request as the user who granted the credential.
	 *
	 * Refuses when that user no longer exists or no longer holds `edit_docs` —
	 * a deleted or demoted user's grants die with them (ADR-006). Every
	 * ability then re-checks its own capability on top of this floor.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id Granting user id.
	 * @return bool
	 */
	private static function impersonate( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user || ! user_can( $user, self::IMPERSONATION_CAPABILITY ) ) {
			return false;
		}

		wp_set_current_user( $user_id );

		return true;
	}

	/**
	 * The 403 for a credential whose user cannot be impersonated.
	 *
	 * Carries the typed object in `error.data` so a client gets the same
	 * vocabulary here as it would from a tool (ADR-016).
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	private static function dead_grant_response() {
		$typed = AbilityError::capability_missing(
			self::IMPERSONATION_CAPABILITY,
			__( 'use this MCP connection', 'betterdocs' )
		);

		$payload = self::error_payload( $typed );

		$envelope                  = self::error( null, self::UNAUTHORIZED, $payload['message'] );
		$envelope['error']['data'] = $payload;

		return new \WP_REST_Response( $envelope, 403 );
	}

	/**
	 * The RFC 9728 `WWW-Authenticate` challenge value.
	 *
	 * Points at the REST alias rather than `/.well-known/…`, because a host that
	 * intercepts well-known paths would otherwise send the client somewhere that
	 * is not us (ADR-014); the alias is filterable.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function challenge_header() {
		return sprintf( 'Bearer resource_metadata="%s"', MCPOAuth::resource_metadata_url() );
	}

	/**
	 * Pull the token from `Authorization: Bearer …`.
	 *
	 * `MCPManager` sets this header synthetically when the token arrived
	 * as a path segment of the pretty endpoint, so there is one place that reads
	 * a credential.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return string
	 */
	private static function extract_token( $request ) {
		$auth = $request->get_header( 'authorization' );

		if ( is_string( $auth ) && preg_match( '/^Bearer\s+(.+)$/i', trim( $auth ), $matches ) ) {
			return trim( $matches[1] );
		}

		return '';
	}

	/**
	 * Headers every MCP response carries.
	 *
	 * `Cache-Control: no-store, private` is not optional: the pretty endpoint
	 * can carry the pairing token in its path, so a shared cache or proxy
	 * holding a response keyed on that URL would keep an admin-equivalent
	 * credential in its store (ADR-007).
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Response $response Response to decorate.
	 * @return \WP_REST_Response
	 */
	private static function decorate( $response ) {
		$response->header( 'MCP-Protocol-Version', self::PROTOCOL_VERSION );
		$response->header( 'Cache-Control', 'no-store, private' );
		$response->header( 'Content-Type', 'application/json' );

		return $response;
	}

	/**
	 * Opt-in diagnostic tap: define `BETTERDOCS_MCP_DEBUG` in `wp-config.php`.
	 *
	 * Logs the method, whether a credential was presented, and the tool name —
	 * never the credential, never the parameters, which routinely carry document
	 * content.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return void
	 */
	private static function debug_tap( $request ) {
		if ( ! defined( 'BETTERDOCS_MCP_DEBUG' ) || ! BETTERDOCS_MCP_DEBUG ) {
			return;
		}

		$msg    = json_decode( (string) $request->get_body(), true );
		$method = is_array( $msg ) && isset( $msg['method'] ) ? (string) $msg['method'] : '?';
		$tool   = is_array( $msg ) && isset( $msg['params']['name'] ) ? (string) $msg['params']['name'] : '-';

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- opt-in debug tap.
		error_log(
			sprintf(
				'[BD-MCP] in method=%s tool=%s auth=%s',
				$method,
				$tool,
				$request->get_header( 'authorization' ) ? 'yes' : 'no'
			)
		);
	}

	/**
	 * Build a JSON-RPC success envelope.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $id     JSON-RPC id.
	 * @param mixed $result Result payload.
	 * @return array
	 */
	private static function result( $id, $result ) {
		return [
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result
		];
	}

	/**
	 * Build a JSON-RPC error envelope.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed  $id      JSON-RPC id.
	 * @param int    $code    JSON-RPC error code.
	 * @param string $message Error message.
	 * @return array
	 */
	private static function error( $id, $code, $message ) {
		return [
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => [
				'code'    => (int) $code,
				'message' => (string) $message
			]
		];
	}

	/**
	 * Build a transport-level error response with an HTTP status.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed  $id      JSON-RPC id.
	 * @param int    $code    JSON-RPC error code.
	 * @param string $message Error message.
	 * @param int    $http    HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function error_response( $id, $code, $message, $http ) {
		return new \WP_REST_Response( self::error( $id, $code, $message ), (int) $http );
	}
}
