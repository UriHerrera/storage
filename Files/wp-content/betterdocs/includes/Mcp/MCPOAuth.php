<?php
/**
 * OAuth 2.1 authorization server for the BetterDocs MCP endpoint.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The "paste the site URL and nothing else" connection path.
 *
 * A pairing token covers clients that accept a Bearer token you paste in.
 * This class covers the spec-compliant ones — Claude's remote connectors, ChatGPT
 * — which are given only the MCP endpoint URL and run the OAuth 2.1
 * authorization-code + PKCE flow themselves:
 *
 *   unauthenticated MCP call → 401 + `WWW-Authenticate` → client fetches
 *   `/.well-known/oauth-protected-resource` and `…/oauth-authorization-server` →
 *   dynamic registration (RFC 7591) → `/betterdocs/authorize` (a person consents,
 *   with PKCE) → `/token` (code + verifier → access + refresh) → MCP calls with
 *   `Authorization: Bearer …`, validated by {@see self::validate_token()}.
 *
 * The security contract, which the rest of the MCP layer relies on:
 *
 * - **PKCE S256 is required.** OAuth 2.1 has no implicit grant and no plain
 *   challenge; a request without S256 is refused at `/authorize`, before anything
 *   is issued.
 * - **Codes are single-use, 60 seconds, and bound** to client id, redirect URI,
 *   challenge and the approving user. The code is burned *before* verification,
 *   so a failed attempt cannot be retried.
 * - **Only hashes are stored.** Authorization codes, access tokens and refresh
 *   tokens exist in the clear exactly once — in the redirect back to the client
 *   and in the `/token` response; the option holds SHA-256 and nothing else, so a
 *   database dump yields no usable credential (ADR-037). Lookups are by hash and
 *   comparisons are `hash_equals()`.
 * - **Refresh rotates.** A refresh grant drops the old refresh token *and* the
 *   access token it minted, so a stolen refresh token is detectable by the real
 *   client suddenly failing.
 * - **Scope decides read vs write** — a `read`-only grant refuses every write
 *   tool, exactly like a read-only pairing token (ADR-017).
 *
 * Who may consent is *not* decided here: the consent screen gates on
 * `edit_docs` (ADR-006), and every ability re-checks its own capability on every
 * call, so a grant can never exceed what the granting user could do themselves.
 *
 * All state lives in one non-autoloaded option, pruned lazily on every read.
 *
 * @since 4.9.0
 */
final class MCPOAuth {

	/**
	 * Option holding every OAuth server record.
	 *
	 * @since 4.9.0
	 */
	const OPTION = 'betterdocs_mcp_oauth';

	/**
	 * Authorization-code lifetime, in seconds. Deliberately short — a real
	 * client exchanges within a second or two.
	 *
	 * @since 4.9.0
	 */
	const CODE_TTL = 60;

	/**
	 * Access-token lifetime, in seconds. One hour, refreshable.
	 *
	 * @since 4.9.0
	 */
	const ACCESS_TTL = 3600;

	/**
	 * Refresh-token lifetime, in seconds. Thirty days.
	 *
	 * @since 4.9.0
	 */
	const REFRESH_TTL = 2592000;

	/**
	 * Scopes advertised and honoured. `mcp` is the umbrella scope MCP clients
	 * ask for; it means read **and** write.
	 *
	 * @since 4.9.0
	 */
	const SUPPORTED_SCOPES = [ 'mcp', 'read', 'write' ];

	/**
	 * Seconds between `last_used` writes for one client. A busy connector would
	 * otherwise turn every MCP call into a database write.
	 *
	 * @since 4.9.0
	 */
	const LAST_USED_THROTTLE = 60;

	/**
	 * How many client registrations to keep.
	 *
	 * RFC 7591 registration is necessarily open — a client has to register
	 * before it can hold any credential — so without a cap anyone on the
	 * internet can grow this option without bound, and every read pays for it.
	 * A client holding a live grant is never evicted, so the cap only ever
	 * discards abandoned registrations.
	 *
	 * @since 4.9.0
	 */
	const MAX_CLIENTS = 50;

	/**
	 * How long an unused registration survives, in seconds. A client that
	 * registers and never completes consent has abandoned the flow; a real one
	 * exchanges a code within the minute.
	 *
	 * @since 4.9.0
	 */
	const CLIENT_TTL = 86400;

	/**
	 * The OAuth issuer.
	 *
	 * Path-based, which RFC 8414 §2 allows: using the MCP endpoint URL itself
	 * means clients derive the path-suffixed well-known URLs
	 * (`/.well-known/oauth-authorization-server/betterdocs/mcp`) and stay
	 * specific to BetterDocs even when another plugin runs its own MCP OAuth
	 * server at the same site root.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function issuer() {
		return untrailingslashit( home_url( '/betterdocs/mcp' ) );
	}

	/**
	 * The protected resource identifier — the MCP endpoint URL.
	 *
	 * `MCPPairing` owns the endpoint's address, and the issuer is the same
	 * string by construction.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function resource() {
		if ( class_exists( __NAMESPACE__ . '\\MCPPairing' ) && method_exists( __NAMESPACE__ . '\\MCPPairing', 'site_endpoint' ) ) {
			return MCPPairing::site_endpoint();
		}

		return self::issuer();
	}

	/**
	 * The browser-facing consent page.
	 *
	 * Served outside the REST API, through a rewrite, so ordinary cookie
	 * authentication works after the wp-login round trip. A REST route would see
	 * the cookie without a nonce, treat the visitor as logged out, and loop back
	 * to the login screen.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function authorize_url() {
		return home_url( '/betterdocs/authorize' );
	}

	/**
	 * The token endpoint.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function token_url() {
		return rest_url( 'betterdocs/v1/mcp/oauth/token' );
	}

	/**
	 * The dynamic client registration endpoint.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function register_url() {
		return rest_url( 'betterdocs/v1/mcp/oauth/register' );
	}

	/**
	 * Where the 401 challenge points for protected-resource metadata.
	 *
	 * The REST alias rather than the `/.well-known/` path, because a
	 * non-trivial number of hosts, security plugins and CDNs intercept
	 * `/.well-known/` for ACME and never let WordPress see it (ADR-014). Both
	 * forms are served; this is the one advertised, and it is filterable for the
	 * site where neither is reachable and the document has to come from
	 * somewhere else entirely.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function resource_metadata_url() {
		/**
		 * Filters the protected-resource metadata URL advertised in the
		 * `WWW-Authenticate` challenge.
		 *
		 * @since 4.9.0
		 *
		 * @param string $url The advertised URL.
		 */
		return apply_filters(
			'betterdocs_mcp_resource_metadata_url',
			rest_url( 'betterdocs/v1/mcp/oauth/protected-resource' )
		);
	}

	/**
	 * RFC 9728 protected-resource metadata: which authorization server protects
	 * the MCP endpoint.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public static function protected_resource_metadata() {
		return [
			'resource'                 => self::resource(),
			'authorization_servers'    => [ self::issuer() ],
			'scopes_supported'         => self::SUPPORTED_SCOPES,
			'bearer_methods_supported' => [ 'header' ]
		];
	}

	/**
	 * RFC 8414 authorization-server metadata: the endpoint map, and only the
	 * capabilities actually implemented.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public static function authorization_server_metadata() {
		return [
			'issuer'                                => self::issuer(),
			'authorization_endpoint'                => self::authorize_url(),
			'token_endpoint'                        => self::token_url(),
			'registration_endpoint'                 => self::register_url(),
			'scopes_supported'                      => self::SUPPORTED_SCOPES,
			'response_types_supported'              => [ 'code' ],
			'grant_types_supported'                 => [ 'authorization_code', 'refresh_token' ],
			'code_challenge_methods_supported'      => [ 'S256' ],
			'token_endpoint_auth_methods_supported' => [ 'none' ]
		];
	}

	/**
	 * Register a public client (RFC 7591).
	 *
	 * No client secret is issued: these are public clients and PKCE is what
	 * binds the code to them. Everything in the request is attacker-controlled
	 * and gets persisted, so the record is bounded on every axis — how many
	 * redirect URIs, how long each may be, how long the name may be — and the
	 * client list is pruned on the way in.
	 *
	 * @since 4.9.0
	 *
	 * @param array $body Parsed JSON registration request.
	 * @return array|\WP_Error The RFC 7591 registration response.
	 */
	public static function register_client( array $body ) {
		$redirect_uris = isset( $body['redirect_uris'] ) && is_array( $body['redirect_uris'] )
			? array_values( array_filter( array_map( 'strval', $body['redirect_uris'] ), [ self::class, 'is_valid_redirect_uri' ] ) )
			: [];

		$redirect_uris = array_values(
			array_unique(
				array_filter(
					$redirect_uris,
					static function ( $uri ) {
						return strlen( $uri ) <= 2048;
					}
				)
			)
		);
		$redirect_uris = array_slice( $redirect_uris, 0, 5 );

		if ( empty( $redirect_uris ) ) {
			return new \WP_Error(
				'invalid_redirect_uri',
				__( 'At least one valid redirect_uri is required.', 'betterdocs' ),
				[ 'status' => 400 ]
			);
		}

		$name = isset( $body['client_name'] ) ? sanitize_text_field( (string) $body['client_name'] ) : __( 'MCP Client', 'betterdocs' );

		if ( strlen( $name ) > 128 ) {
			$name = substr( $name, 0, 128 );
		}

		$client_id = 'bd_' . bin2hex( random_bytes( 16 ) );

		$state                          = self::state();
		$state['clients'][ $client_id ] = [
			'redirect_uris' => $redirect_uris,
			'name'          => $name,
			'created'       => time()
		];
		$state['clients']               = self::prune_clients( $state );

		self::save( $state );

		return [
			'client_id'                  => $client_id,
			'client_id_issued_at'        => time(),
			'redirect_uris'              => $redirect_uris,
			'client_name'                => $name,
			'token_endpoint_auth_method' => 'none',
			'grant_types'                => [ 'authorization_code', 'refresh_token' ],
			'response_types'             => [ 'code' ]
		];
	}

	/**
	 * Check an `/authorize` request without issuing anything.
	 *
	 * Returns a sanitised parameter bag, or a `WP_Error`. Whether an error may
	 * be reported back to the client by redirect is in the error's
	 * `redirectable` data: a bad `redirect_uri` or an unknown client must **not**
	 * redirect, because at that point the destination is not one this site has
	 * ever trusted — that is the open-redirect guard.
	 *
	 * @since 4.9.0
	 *
	 * @param array $params Query parameters.
	 * @return array|\WP_Error
	 */
	public static function validate_authorize_request( array $params ) {
		$client_id     = isset( $params['client_id'] ) ? (string) $params['client_id'] : '';
		$redirect_uri  = isset( $params['redirect_uri'] ) ? (string) $params['redirect_uri'] : '';
		$response_type = isset( $params['response_type'] ) ? (string) $params['response_type'] : '';
		$challenge     = isset( $params['code_challenge'] ) ? (string) $params['code_challenge'] : '';
		$method        = isset( $params['code_challenge_method'] ) ? (string) $params['code_challenge_method'] : '';
		$scope         = isset( $params['scope'] ) ? (string) $params['scope'] : 'mcp';
		$state         = isset( $params['state'] ) ? (string) $params['state'] : '';

		$client = self::client( $client_id );

		if ( null === $client ) {
			return new \WP_Error( 'invalid_client', __( 'Unknown client_id.', 'betterdocs' ), [ 'status' => 400 ] );
		}

		if ( ! in_array( $redirect_uri, $client['redirect_uris'], true ) ) {
			return new \WP_Error( 'invalid_redirect_uri', __( 'redirect_uri does not match a registered value.', 'betterdocs' ), [ 'status' => 400 ] );
		}

		if ( 'code' !== $response_type ) {
			return new \WP_Error(
				'unsupported_response_type',
				__( 'Only response_type=code is supported.', 'betterdocs' ),
				[
					'status'       => 400,
					'redirectable' => true
				]
			);
		}

		// OAuth 2.1: PKCE with S256 is mandatory for public clients. `plain`
		// is refused too — it protects nothing against an attacker who can see
		// the authorization request.
		if ( 'S256' !== $method || '' === $challenge ) {
			return new \WP_Error(
				'invalid_request',
				__( 'PKCE with code_challenge_method=S256 is required.', 'betterdocs' ),
				[
					'status'       => 400,
					'redirectable' => true
				]
			);
		}

		return [
			'client_id'      => $client_id,
			'client_name'    => $client['name'],
			'redirect_uri'   => $redirect_uri,
			'code_challenge' => $challenge,
			'scope'          => self::normalize_scope( $scope ),
			'state'          => $state
		];
	}

	/**
	 * Issue an authorization code, once a person has consented.
	 *
	 * Bound to the client, the redirect URI, the PKCE challenge, the granted
	 * scope and the approving user. Single-use, 60 seconds.
	 *
	 * The record is keyed by `hash( 'sha256', $code )`, never by the code
	 * itself, so the option holds no usable credential even during that minute
	 * (ADR-037). The caller receives the only plaintext copy.
	 *
	 * @since 4.9.0
	 *
	 * @param array $req     Output of {@see self::validate_authorize_request()}.
	 * @param int   $user_id The user who approved.
	 * @return string The authorization code.
	 */
	public static function issue_code( array $req, $user_id ) {
		$code  = bin2hex( random_bytes( 32 ) );
		$state = self::state();

		$state['codes'][ self::hash( $code ) ] = [
			'client_id'    => isset( $req['client_id'] ) ? (string) $req['client_id'] : '',
			'redirect_uri' => isset( $req['redirect_uri'] ) ? (string) $req['redirect_uri'] : '',
			'challenge'    => isset( $req['code_challenge'] ) ? (string) $req['code_challenge'] : '',
			'scope'        => isset( $req['scope'] ) ? (string) $req['scope'] : 'mcp',
			'user_id'      => (int) $user_id,
			'expires'      => time() + self::CODE_TTL
		];

		self::save( $state );

		return $code;
	}

	/**
	 * The token endpoint's two grants.
	 *
	 * @since 4.9.0
	 *
	 * @param array $body POST body parameters.
	 * @return array|\WP_Error RFC 6749 token response, or an OAuth error.
	 */
	public static function exchange_token( array $body ) {
		$grant = isset( $body['grant_type'] ) ? (string) $body['grant_type'] : '';

		if ( 'authorization_code' === $grant ) {
			return self::grant_authorization_code( $body );
		}

		if ( 'refresh_token' === $grant ) {
			return self::grant_refresh_token( $body );
		}

		return self::oauth_error( 'unsupported_grant_type', __( 'Unsupported grant_type.', 'betterdocs' ) );
	}

	/**
	 * Validate a bearer access token presented to the MCP endpoint.
	 *
	 * @since 4.9.0
	 *
	 * @param string $token Raw access token from the Authorization header.
	 * @return array|null `{client_id, scope, user_id}` when valid, else null.
	 */
	public static function validate_token( $token ) {
		$token = (string) $token;

		if ( '' === $token ) {
			return null;
		}

		$state = self::state();
		$hash  = self::hash( $token );

		if ( ! isset( $state['tokens'][ $hash ] ) ) {
			return null;
		}

		$entry = $state['tokens'][ $hash ];

		if ( (int) $entry['expires'] < time() ) {
			return null;
		}

		// Record activity against the client, so "Connected apps" can show a
		// last-used date. Throttled, and kept on the client record so it
		// survives access-token rotation.
		$client_id = (string) $entry['client_id'];
		$now       = time();

		if ( isset( $state['clients'][ $client_id ] ) && is_array( $state['clients'][ $client_id ] ) ) {
			$last = isset( $state['clients'][ $client_id ]['last_used'] ) ? (int) $state['clients'][ $client_id ]['last_used'] : 0;

			if ( $now - $last >= self::LAST_USED_THROTTLE ) {
				$state['clients'][ $client_id ]['last_used'] = $now;
				self::save( $state );
			}
		}

		return [
			'client_id' => $client_id,
			'scope'     => (string) $entry['scope'],
			'user_id'   => (int) $entry['user_id']
		];
	}

	/**
	 * Whether a granted scope is read-only.
	 *
	 * `mcp` is the umbrella scope and includes writing, so only a grant
	 * carrying neither `write` nor `mcp` — `read` alone — is read-only.
	 *
	 * @since 4.9.0
	 *
	 * @param string $scope Space-separated scope string.
	 * @return bool
	 */
	public static function scope_is_read_only( $scope ) {
		$parts = self::scope_parts( (string) $scope );

		return ! in_array( 'write', $parts, true ) && ! in_array( 'mcp', $parts, true );
	}

	/**
	 * Drop every OAuth record: clients, codes, tokens, refresh grants.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function revoke_all() {
		delete_option( self::OPTION );
	}

	/**
	 * Cut one app off.
	 *
	 * Drops its access tokens, refresh tokens and pending codes, so it
	 * disappears from {@see self::connected_apps()} immediately while every
	 * other connection carries on.
	 *
	 * The registration itself is deliberately **kept**. MCP clients cache the
	 * `client_id` from their first registration and reuse it when reconnecting
	 * rather than registering afresh; deleting the record would answer that
	 * reconnect with "Unknown client_id". Keeping it lets the app come back —
	 * which still needs fresh consent and mints brand-new tokens, so revocation
	 * loses nothing.
	 *
	 * @since 4.9.0
	 *
	 * @param string $client_id Client to revoke.
	 * @return bool Whether any live grant was removed.
	 */
	public static function revoke_client( $client_id ) {
		$client_id = (string) $client_id;

		if ( '' === $client_id ) {
			return false;
		}

		return self::revoke_where(
			static function ( array $entry ) use ( $client_id ) {
				return isset( $entry['client_id'] ) && (string) $entry['client_id'] === $client_id;
			}
		);
	}

	/**
	 * Drop every grant a given user approved.
	 *
	 * A grant can never exceed what the granting user may do, so when that user
	 * is deleted or demoted their grants have to go with them — otherwise a
	 * token keeps acting as a person who no longer has the capability, or no
	 * longer exists. `MCPManager` hooks `deleted_user` and `set_user_role` here.
	 *
	 * The client registrations stay, for the same reason as
	 * {@see self::revoke_client()}: another user may reconnect the same app.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User whose grants to revoke.
	 * @return bool Whether anything was removed.
	 */
	public static function revoke_user( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			return false;
		}

		return self::revoke_where(
			static function ( array $entry ) use ( $user_id ) {
				return isset( $entry['user_id'] ) && (int) $entry['user_id'] === $user_id;
			}
		);
	}

	/**
	 * The apps currently holding a live grant, for the admin's "Connected apps"
	 * list.
	 *
	 * A client counts as connected while it holds an unexpired refresh token —
	 * the durable thirty-day grant — or an access token. One that registered but
	 * never completed consent is not connected and is not listed. Newest first.
	 *
	 * Returns no hash and no token, ever: this feeds an admin screen, and there
	 * is nothing here a person needs a credential to see.
	 *
	 * @since 4.9.0
	 *
	 * @return array[]
	 */
	public static function connected_apps() {
		$state = self::state();

		// Refresh tokens are the durable grant, so they are read first; access
		// tokens fill in a client whose refresh has already expired.
		$active = [];

		foreach ( [ 'refresh', 'tokens' ] as $bucket ) {
			foreach ( $state[ $bucket ] as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$cid = isset( $entry['client_id'] ) ? (string) $entry['client_id'] : '';

				if ( '' === $cid ) {
					continue;
				}

				$expires = isset( $entry['expires'] ) ? (int) $entry['expires'] : 0;

				if ( ! isset( $active[ $cid ] ) ) {
					$active[ $cid ] = [
						'scope'   => isset( $entry['scope'] ) ? (string) $entry['scope'] : 'mcp',
						'user_id' => isset( $entry['user_id'] ) ? (int) $entry['user_id'] : 0,
						'expires' => $expires
					];

					continue;
				}

				// Several grants for one app: report the one that lasts longest.
				if ( $expires > $active[ $cid ]['expires'] ) {
					$active[ $cid ]['expires'] = $expires;
				}
			}
		}

		$apps = [];

		foreach ( $active as $cid => $info ) {
			$client = isset( $state['clients'][ $cid ] ) && is_array( $state['clients'][ $cid ] ) ? $state['clients'][ $cid ] : [];

			$apps[] = [
				'client_id'     => $cid,
				'name'          => isset( $client['name'] ) ? (string) $client['name'] : __( 'MCP Client', 'betterdocs' ),
				'scope'         => $info['scope'],
				'read_only'     => self::scope_is_read_only( $info['scope'] ),
				'user_id'       => $info['user_id'],
				'user_login'    => self::user_login( $info['user_id'] ),
				// The one field an attacker cannot fake: client registration is
				// open (RFC 7591) and `name` is whatever the registrant typed,
				// so the admin page shows where a grant actually sends access.
				'redirect_uris' => isset( $client['redirect_uris'] ) && is_array( $client['redirect_uris'] )
					? array_values( array_map( 'strval', $client['redirect_uris'] ) )
					: [],
				'created'       => isset( $client['created'] ) ? (int) $client['created'] : 0,
				'last_used'     => isset( $client['last_used'] ) ? (int) $client['last_used'] : 0,
				'expires'       => $info['expires']
			];
		}

		usort(
			$apps,
			static function ( array $a, array $b ) {
				return $b['created'] <=> $a['created'];
			}
		);

		return $apps;
	}

	/**
	 * BASE64URL(SHA256(verifier)) — the PKCE S256 transformation.
	 *
	 * Public so the unit suite can pin it against RFC 7636's own vector: get
	 * this wrong and every connection fails, or worse, succeeds without really
	 * checking anything.
	 *
	 * @since 4.9.0
	 *
	 * @param string $verifier PKCE code verifier.
	 * @return string
	 */
	public static function s256( $verifier ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- base64url is the encoding RFC 7636 defines for the S256 challenge; nothing here is being hidden.
		return rtrim( strtr( base64_encode( hash( 'sha256', (string) $verifier, true ) ), '+/', '-_' ), '=' );
	}

	/**
	 * The authorization_code grant: verify the code and PKCE, mint tokens.
	 *
	 * @since 4.9.0
	 *
	 * @param array $body POST body.
	 * @return array|\WP_Error
	 */
	private static function grant_authorization_code( array $body ) {
		$code         = isset( $body['code'] ) ? (string) $body['code'] : '';
		$client_id    = isset( $body['client_id'] ) ? (string) $body['client_id'] : '';
		$redirect_uri = isset( $body['redirect_uri'] ) ? (string) $body['redirect_uri'] : '';
		$verifier     = isset( $body['code_verifier'] ) ? (string) $body['code_verifier'] : '';

		$state = self::state();
		$chash = self::hash( $code );

		if ( '' === $code || ! isset( $state['codes'][ $chash ] ) ) {
			return self::oauth_error( 'invalid_grant', __( 'Unknown or expired authorization code.', 'betterdocs' ) );
		}

		$entry = $state['codes'][ $chash ];

		// Single-use, and burned *before* verification: a code that failed
		// PKCE must not be available for a second attempt.
		unset( $state['codes'][ $chash ] );
		self::save( $state );

		if ( (int) $entry['expires'] < time() ) {
			return self::oauth_error( 'invalid_grant', __( 'Authorization code expired.', 'betterdocs' ) );
		}

		if ( ! hash_equals( (string) $entry['client_id'], $client_id ) ) {
			return self::oauth_error( 'invalid_grant', __( 'client_id mismatch.', 'betterdocs' ) );
		}

		if ( ! hash_equals( (string) $entry['redirect_uri'], $redirect_uri ) ) {
			return self::oauth_error( 'invalid_grant', __( 'redirect_uri mismatch.', 'betterdocs' ) );
		}

		if ( '' === $verifier || ! hash_equals( (string) $entry['challenge'], self::s256( $verifier ) ) ) {
			return self::oauth_error( 'invalid_grant', __( 'PKCE verification failed.', 'betterdocs' ) );
		}

		return self::mint_tokens( (string) $entry['client_id'], (string) $entry['scope'], (int) $entry['user_id'] );
	}

	/**
	 * The refresh_token grant: rotate the refresh token, issue a fresh access
	 * token, and revoke both of the old ones.
	 *
	 * @since 4.9.0
	 *
	 * @param array $body POST body.
	 * @return array|\WP_Error
	 */
	private static function grant_refresh_token( array $body ) {
		$refresh   = isset( $body['refresh_token'] ) ? (string) $body['refresh_token'] : '';
		$client_id = isset( $body['client_id'] ) ? (string) $body['client_id'] : '';

		$state = self::state();
		$rhash = self::hash( $refresh );

		if ( '' === $refresh || ! isset( $state['refresh'][ $rhash ] ) ) {
			return self::oauth_error( 'invalid_grant', __( 'Unknown refresh token.', 'betterdocs' ) );
		}

		$entry = $state['refresh'][ $rhash ];

		if ( '' !== $client_id && ! hash_equals( (string) $entry['client_id'], $client_id ) ) {
			return self::oauth_error( 'invalid_grant', __( 'client_id mismatch.', 'betterdocs' ) );
		}

		unset( $state['refresh'][ $rhash ] );

		if ( isset( $entry['access_hash'] ) ) {
			unset( $state['tokens'][ $entry['access_hash'] ] );
		}

		self::save( $state );

		return self::mint_tokens( (string) $entry['client_id'], (string) $entry['scope'], (int) $entry['user_id'] );
	}

	/**
	 * Mint an access + refresh pair, store the hashes, return the raw values.
	 *
	 * This is the only moment either token exists in the clear.
	 *
	 * @since 4.9.0
	 *
	 * @param string $client_id Client id.
	 * @param string $scope     Granted scope.
	 * @param int    $user_id   Resource owner.
	 * @return array
	 */
	private static function mint_tokens( $client_id, $scope, $user_id ) {
		$access  = bin2hex( random_bytes( 32 ) );
		$refresh = bin2hex( random_bytes( 32 ) );
		$ahash   = self::hash( $access );
		$rhash   = self::hash( $refresh );

		$state = self::state();

		$state['tokens'][ $ahash ] = [
			'client_id' => $client_id,
			'scope'     => $scope,
			'user_id'   => $user_id,
			'expires'   => time() + self::ACCESS_TTL,
			'refresh'   => $rhash
		];

		$state['refresh'][ $rhash ] = [
			'access_hash' => $ahash,
			'client_id'   => $client_id,
			'scope'       => $scope,
			'user_id'     => $user_id,
			'expires'     => time() + self::REFRESH_TTL
		];

		self::save( $state );

		return [
			'access_token'  => $access,
			'token_type'    => 'Bearer',
			'expires_in'    => self::ACCESS_TTL,
			'refresh_token' => $refresh,
			'scope'         => $scope
		];
	}

	/**
	 * Remove every code, token and refresh grant matching a predicate.
	 *
	 * @since 4.9.0
	 *
	 * @param callable $matches Receives one record, returns whether to drop it.
	 * @return bool Whether anything was removed.
	 */
	private static function revoke_where( callable $matches ) {
		$state   = self::state();
		$removed = false;

		foreach ( [ 'tokens', 'refresh', 'codes' ] as $bucket ) {
			foreach ( $state[ $bucket ] as $key => $entry ) {
				if ( is_array( $entry ) && $matches( $entry ) ) {
					unset( $state[ $bucket ][ $key ] );
					$removed = true;
				}
			}
		}

		if ( $removed ) {
			self::save( $state );
		}

		return $removed;
	}

	/**
	 * Load state with defaults, pruning anything expired on the way out so the
	 * option cannot grow without bound.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private static function state() {
		$stored = get_option( self::OPTION, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$state = [
			'clients' => isset( $stored['clients'] ) && is_array( $stored['clients'] ) ? $stored['clients'] : [],
			'codes'   => isset( $stored['codes'] ) && is_array( $stored['codes'] ) ? $stored['codes'] : [],
			'tokens'  => isset( $stored['tokens'] ) && is_array( $stored['tokens'] ) ? $stored['tokens'] : [],
			'refresh' => isset( $stored['refresh'] ) && is_array( $stored['refresh'] ) ? $stored['refresh'] : []
		];

		$now = time();

		foreach ( [ 'codes', 'tokens' ] as $bucket ) {
			foreach ( $state[ $bucket ] as $key => $entry ) {
				if ( ! isset( $entry['expires'] ) || (int) $entry['expires'] < $now ) {
					unset( $state[ $bucket ][ $key ] );
				}
			}
		}

		foreach ( $state['refresh'] as $key => $entry ) {
			if ( isset( $entry['expires'] ) && (int) $entry['expires'] < $now ) {
				unset( $state['refresh'][ $key ] );
			}
		}

		return $state;
	}

	/**
	 * Persist state. Autoload off — this is a hot, request-scoped option.
	 *
	 * @since 4.9.0
	 *
	 * @param array $state State to store.
	 * @return void
	 */
	private static function save( array $state ) {
		update_option( self::OPTION, $state, false );
	}

	/**
	 * One registered client.
	 *
	 * @since 4.9.0
	 *
	 * @param string $client_id Client id.
	 * @return array|null
	 */
	private static function client( $client_id ) {
		$client_id = (string) $client_id;

		if ( '' === $client_id ) {
			return null;
		}

		$clients = self::state()['clients'];

		if ( ! isset( $clients[ $client_id ] ) || ! is_array( $clients[ $client_id ] ) ) {
			return null;
		}

		$client = $clients[ $client_id ];

		return [
			'redirect_uris' => isset( $client['redirect_uris'] ) && is_array( $client['redirect_uris'] ) ? array_map( 'strval', $client['redirect_uris'] ) : [],
			'name'          => isset( $client['name'] ) ? (string) $client['name'] : 'MCP Client',
			'created'       => isset( $client['created'] ) ? (int) $client['created'] : 0
		];
	}

	/**
	 * Bound the registered-client list.
	 *
	 * Abandoned registrations past `CLIENT_TTL` go first; if that is not enough,
	 * the oldest of what is left. A client referenced by a live code, access
	 * token or refresh token is **never** dropped — evicting one breaks a
	 * working connection — so a site legitimately holding more than
	 * `MAX_CLIENTS` live grants keeps every one of them and the cap simply stops
	 * applying to that remainder.
	 *
	 * @since 4.9.0
	 *
	 * @param array $state Full state.
	 * @return array The clients array to store.
	 */
	private static function prune_clients( array $state ) {
		$clients = $state['clients'];
		$in_use  = [];

		foreach ( [ 'codes', 'tokens', 'refresh' ] as $bucket ) {
			foreach ( $state[ $bucket ] as $entry ) {
				if ( is_array( $entry ) && isset( $entry['client_id'] ) ) {
					$in_use[ (string) $entry['client_id'] ] = true;
				}
			}
		}

		$now = time();

		foreach ( $clients as $id => $client ) {
			$created = isset( $client['created'] ) ? (int) $client['created'] : 0;

			if ( ! isset( $in_use[ $id ] ) && $created + self::CLIENT_TTL < $now ) {
				unset( $clients[ $id ] );
			}
		}

		if ( count( $clients ) <= self::MAX_CLIENTS ) {
			return $clients;
		}

		$evictable = array_filter(
			$clients,
			static function ( $id ) use ( $in_use ) {
				return ! isset( $in_use[ $id ] );
			},
			ARRAY_FILTER_USE_KEY
		);

		uasort(
			$evictable,
			static function ( $a, $b ) {
				return ( isset( $a['created'] ) ? (int) $a['created'] : 0 ) <=> ( isset( $b['created'] ) ? (int) $b['created'] : 0 );
			}
		);

		foreach ( array_keys( $evictable ) as $id ) {
			if ( count( $clients ) <= self::MAX_CLIENTS ) {
				break;
			}

			unset( $clients[ $id ] );
		}

		return $clients;
	}

	/**
	 * SHA-256, the form every token is stored in.
	 *
	 * @since 4.9.0
	 *
	 * @param string $value Raw secret.
	 * @return string
	 */
	private static function hash( $value ) {
		return hash( 'sha256', (string) $value );
	}

	/**
	 * Split a scope string into its parts.
	 *
	 * @since 4.9.0
	 *
	 * @param string $scope Space-separated scopes.
	 * @return string[]
	 */
	private static function scope_parts( $scope ) {
		$parts = preg_split( '/\s+/', trim( (string) $scope ) );

		return is_array( $parts ) ? $parts : [];
	}

	/**
	 * Constrain a requested scope to what is supported. Defaults to `mcp`.
	 *
	 * @since 4.9.0
	 *
	 * @param string $requested Requested scope string.
	 * @return string
	 */
	private static function normalize_scope( $requested ) {
		$parts = array_values( array_intersect( self::scope_parts( $requested ), self::SUPPORTED_SCOPES ) );

		if ( empty( $parts ) ) {
			return 'mcp';
		}

		return implode( ' ', $parts );
	}

	/**
	 * The login name behind a user id, for the connected-apps list.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User id.
	 * @return string Empty when the user no longer exists.
	 */
	private static function user_login( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 || ! function_exists( 'get_userdata' ) ) {
			return '';
		}

		$user = get_userdata( $user_id );

		return $user && isset( $user->user_login ) ? (string) $user->user_login : '';
	}

	/**
	 * Whether a redirect URI is structurally acceptable: http(s), or a native
	 * client's custom scheme.
	 *
	 * @since 4.9.0
	 *
	 * @param string $uri Candidate redirect URI.
	 * @return bool
	 */
	private static function is_valid_redirect_uri( $uri ) {
		$uri = trim( (string) $uri );

		if ( '' === $uri ) {
			return false;
		}

		if ( ! preg_match( '#^([a-zA-Z][a-zA-Z0-9+.\-]*)://#', $uri, $matches ) ) {
			return false;
		}

		// Registration is unauthenticated, so the scheme is attacker-chosen.
		// `javascript://…`, `data://…` and `vbscript://…` all satisfy the shape
		// above, and this value is later handed to `wp_redirect()` and rendered
		// on the consent screen. Browsers refuse to navigate to those, so this
		// is not the last line of defence — but a credential callback has no
		// business being one of them either.
		$scheme = strtolower( $matches[1] );

		return ! in_array( $scheme, [ 'javascript', 'data', 'vbscript', 'file' ], true );
	}

	/**
	 * A `WP_Error` whose data carries an RFC 6749 error code, so the token route
	 * can render the OAuth error body verbatim.
	 *
	 * @since 4.9.0
	 *
	 * @param string $code    OAuth error code.
	 * @param string $message Human-readable description.
	 * @return \WP_Error
	 */
	private static function oauth_error( $code, $message ) {
		return new \WP_Error(
			$code,
			$message,
			[
				'status'            => 400,
				'error'             => $code,
				'error_description' => $message
			]
		);
	}
}
