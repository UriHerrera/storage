<?php
/**
 * MCP connection self-test — the loopback ladder.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilitiesRegistrar;

/**
 * Exercises the MCP round trip the way an external client would, and reports
 * *where* it broke.
 *
 * {@see MCPHealth} says what this site is; this says whether it answers. It
 * makes real loopback HTTP requests, so it is a `POST` route and a deliberate
 * action, never something a page load triggers.
 *
 * Five surfaces, each one a real client depends on:
 *
 * 1. `endpoint`   — the pretty URL a user pastes (`/betterdocs/mcp`), with the
 *                   pairing token. It depends on rewrite rules, so it is the
 *                   check that fails on plain permalinks or an unflushed table.
 * 2. `fallback`   — the always-on `/wp-json/betterdocs/v1/mcp` route. Working
 *                   here while the pretty URL fails is what separates "MCP is
 *                   down" from "only the pretty URL is".
 * 3. `discovery`  — the RFC 9728 / RFC 8414 metadata, in both the `/.well-known/`
 *                   form and the REST aliases.
 * 4. `challenge`  — an **unauthenticated** call, which must answer 401 with a
 *                   `WWW-Authenticate` header. It is the only thing an
 *                   OAuth-only client has to go on: no challenge reads to it as
 *                   "this server does not implement OAuth", however healthy
 *                   everything else is.
 * 5. `user_agent` — the same probe under the User-Agents real MCP backends
 *                   send, to catch a "block bad bots" rule that answers
 *                   WordPress and refuses every AI client.
 *
 * The staged result names the first failing step:
 * `disabled` → `not_connected` → `unreachable` → `tls` → `redirect` → `auth` →
 * `no_tools` → `rewrite` → `discovery` → `challenge` → `ok`.
 *
 * @since 4.9.0
 */
final class MCPSelfTest {

	/**
	 * How long each loopback request may take, in seconds.
	 *
	 * @since 4.9.0
	 */
	const TIMEOUT = 10;

	/**
	 * User-Agents to replay the challenge probe with.
	 *
	 * The shapes real MCP backends send. None of them is a browser, which is
	 * exactly what a "block bad bots" rule keys on: a site that answers
	 * WordPress' own User-Agent but refuses these is unreachable for every AI
	 * client while looking perfectly healthy from the inside.
	 *
	 * @since 4.9.0
	 */
	const CLIENT_USER_AGENTS = [
		'python-requests/2.32.3',
		'node-fetch/3.3.2'
	];

	/**
	 * Run the round trip.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function run() {
		$endpoint = MCPPairing::site_endpoint();
		$fallback = MCPPairing::site_endpoint_fallback();

		$result = [
			'ok'                  => false,
			'stage'               => '',
			'message'             => '',
			'endpoint'            => $endpoint,
			'endpoint_rest'       => $fallback,
			'mcp_enabled'         => MCPManager::is_enabled(),
			'connected'           => MCPPairing::is_connected(),
			'http_status'         => null,
			'redirected'          => false,
			'authenticated'       => false,
			'tools_count'         => null,
			'checks'              => [],
			// Findings that are worth showing but do not fail the test.
			'caveats'             => [],
			'locked_clients'      => null,
			// url => decoded metadata, or the raw body when it is not JSON.
			'discovery_documents' => []
		];

		if ( ! $result['mcp_enabled'] ) {
			$result['stage']   = 'disabled';
			$result['message'] = __( 'MCP access is turned off, so the endpoint refuses every request. Switch MCP on above and run the test again.', 'betterdocs' );

			return $result;
		}

		if ( ! $result['connected'] ) {
			$result['stage']   = 'not_connected';
			$result['message'] = __( 'No connection token exists yet. Click Connect to mint one, then run the test again.', 'betterdocs' );

			return $result;
		}

		$token = MCPPairing::site_token();

		if ( '' === $token ) {
			$result['stage']   = 'not_connected';
			$result['message'] = __( 'This site is paired, but the stored copy of the connection token cannot be decrypted — usually because the WordPress security salts changed. Existing clients still authenticate; rotate the token and reconnect them to be able to test it.', 'betterdocs' );

			return $result;
		}

		$pretty = $this->probe_jsonrpc( $endpoint, $token );
		$rest   = $this->probe_jsonrpc( $fallback, $token );

		// The top-level fields describe the primary (pretty) endpoint, falling
		// back to the REST route when the pretty URL never answered at all.
		$primary                 = 'unreachable' === $pretty['stage'] ? $rest : $pretty;
		$result['http_status']   = $primary['status'];
		$result['redirected']    = 'redirect' === $primary['stage'];
		$result['authenticated'] = $primary['authenticated'];
		$result['tools_count']   = $primary['tools'];

		$result['checks'][] = self::check( 'endpoint', __( 'Connection URL', 'betterdocs' ), $pretty['stage'], $pretty['detail'] );
		$result['checks'][] = self::check( 'fallback', __( 'REST fallback URL', 'betterdocs' ), $rest['stage'], $rest['detail'] );

		$discovery                     = $this->probe_discovery();
		$result['discovery_documents'] = $discovery['documents'];
		$result['checks'][]            = self::check( 'discovery', __( 'OAuth discovery', 'betterdocs' ), $discovery['stage'], $discovery['detail'] );

		if ( ! empty( $discovery['caveat'] ) ) {
			$result['caveats'][] = $discovery['caveat'];
		}

		$challenge          = $this->probe_challenge( $endpoint, $fallback );
		$result['checks'][] = self::check( 'challenge', __( 'OAuth challenge', 'betterdocs' ), $challenge['stage'], $challenge['detail'] );

		// Only reported when it could actually run: claiming a pass that was
		// never measured is the failure mode this whole test exists to avoid.
		$user_agent = $this->probe_user_agent( $endpoint );

		if ( null !== $user_agent ) {
			$result['checks'][] = self::check( 'user_agent', __( 'Client access', 'betterdocs' ), $user_agent['stage'], $user_agent['detail'] );
		}

		$this->add_lockout_check( $result );

		// The pretty URL failing while the fallback works is its own finding:
		// the site is usable, but only through the REST URL.
		if ( 'ok' !== $pretty['stage'] && 'ok' === $rest['stage'] ) {
			$result['stage']   = 'rewrite';
			$result['message'] = sprintf(
				/* translators: 1: pretty MCP endpoint URL, 2: REST fallback URL. */
				__( 'The connection URL %1$s did not answer correctly, but the REST fallback %2$s works. Re-save Settings → Permalinks to rebuild the rewrite rules; until then, give your AI client the fallback URL.', 'betterdocs' ),
				$endpoint,
				$fallback
			);

			return $result;
		}

		// `$user_agent` is deliberately not in this list. It is the one check that
		// does not measure this server: it probes what a *generic* HTTP client
		// sees, and a host that refuses those may still answer the AI client's own
		// User-Agent — which cannot be known from here. Letting that proxy signal
		// overrule four checks that completed a real round trip reported working
		// sites as broken (ADR-067). It is still reported, as its own warning row.
		foreach ( [ $pretty, $rest, $discovery, $challenge ] as $check ) {
			if ( null === $check || 'ok' === $check['stage'] ) {
				continue;
			}

			$result['stage']   = $check['stage'];
			$result['message'] = $check['detail'];

			return $result;
		}

		$result['ok']      = true;
		$result['stage']   = 'ok';
		$result['message'] = sprintf(
			/* translators: %d: number of MCP tools returned. */
			_n(
				'Connection healthy: the endpoint authenticated, offered OAuth, and returned %d tool.',
				'Connection healthy: the endpoint authenticated, offered OAuth, and returned %d tools.',
				(int) $result['tools_count'],
				'betterdocs'
			),
			(int) $result['tools_count']
		);

		return $result;
	}

	// -- Probes ---------------------------------------------------------------

	/**
	 * One authenticated JSON-RPC `tools/list` round trip.
	 *
	 * @since 4.9.0
	 *
	 * @param string $url   Endpoint to call.
	 * @param string $token Pairing token.
	 * @return array `{ stage, status, tools, authenticated, detail }`
	 */
	private function probe_jsonrpc( $url, $token ) {
		$response = wp_remote_post(
			$url,
			[
				'timeout'     => self::TIMEOUT,
				// Do not follow redirects: a 301/302 here *is* the finding —
				// the classic http↔https scheme bounce — so surface it verbatim.
				'redirection' => 0,
				'local'       => true,
				'sslverify'   => self::verify_certificate( $url ),
				'headers'     => [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json'
				],
				'body'        => wp_json_encode(
					[
						'jsonrpc' => '2.0',
						'id'      => 1,
						'method'  => 'tools/list'
					]
				)
			]
		);

		$out = [
			'stage'         => 'ok',
			'status'        => null,
			'tools'         => null,
			'authenticated' => false,
			'detail'        => ''
		];

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();

			if ( self::is_tls_error( $error ) ) {
				$out['stage']  = 'tls';
				$out['detail'] = sprintf(
					/* translators: 1: endpoint URL, 2: the transport error PHP reported. */
					__( 'PHP could not verify this site\'s TLS certificate (common on local sites with self-signed certificates). %1$s reported: %2$s. Add `add_filter( \'https_local_ssl_verify\', \'__return_false\' );` in a mu-plugin for local testing.', 'betterdocs' ),
					$url,
					$error
				);

				return $out;
			}

			$out['stage']  = 'unreachable';
			$out['detail'] = sprintf(
				/* translators: 1: endpoint URL, 2: the transport error PHP reported. */
				__( '%1$s could not be reached: %2$s.', 'betterdocs' ),
				$url,
				$error
			);

			return $out;
		}

		$status        = (int) wp_remote_retrieve_response_code( $response );
		$out['status'] = $status;

		if ( in_array( $status, [ 301, 302, 307, 308 ], true ) ) {
			$location = (string) wp_remote_retrieve_header( $response, 'location' );

			$out['stage']  = 'redirect';
			$out['detail'] = '' !== $location
				? sprintf(
					/* translators: 1: endpoint URL, 2: the URL it redirected to. */
					__( '%1$s redirected to %2$s instead of answering. A redirect between HTTP and HTTPS usually means the Site Address and WordPress Address schemes disagree.', 'betterdocs' ),
					$url,
					$location
				)
				: sprintf(
					/* translators: %s: endpoint URL. */
					__( '%s redirected instead of answering, which usually means the Site Address and WordPress Address schemes disagree.', 'betterdocs' ),
					$url
				);

			return $out;
		}

		if ( 404 === $status ) {
			$out['stage']  = 'rewrite';
			$out['detail'] = sprintf(
				/* translators: %s: endpoint URL. */
				__( '%s returned 404 — WordPress does not know this URL. Re-save Settings → Permalinks to rebuild the rewrite rules.', 'betterdocs' ),
				$url
			);

			return $out;
		}

		if ( 401 === $status || 403 === $status ) {
			$out['stage']  = 'auth';
			$out['detail'] = sprintf(
				/* translators: %s: endpoint URL. */
				__( '%s rejected the connection token (authentication failed). Rotate the token and reconnect your AI client.', 'betterdocs' ),
				$url
			);

			return $out;
		}

		if ( 429 === $status ) {
			$out['stage']  = 'auth';
			$out['detail'] = sprintf(
				/* translators: %s: endpoint URL. */
				__( '%s is rate-limiting this server after repeated failed tokens. Wait for the lockout to lapse, then rotate the token and reconnect.', 'betterdocs' ),
				$url
			);

			return $out;
		}

		$body         = (string) wp_remote_retrieve_body( $response );
		$content_type = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
		$decoded      = json_decode( $body, true );

		// Under plain permalinks the pretty path does not 404 — WordPress
		// serves the FRONT PAGE at it, with a cheerful 200 and `text/html`.
		// Status alone therefore cannot tell a working endpoint from a
		// completely absent one, which is why the content type and the body
		// shape are checked before anything else (Batch 2 review).
		if ( ! is_array( $decoded ) ) {
			$out['stage']  = 'rewrite';
			$out['detail'] = sprintf(
				/* translators: 1: endpoint URL, 2: HTTP status code, 3: the Content-Type header received. */
				__( '%1$s answered %2$d with %3$s instead of JSON — WordPress is serving an ordinary page at this URL, not the MCP endpoint. Under plain permalinks the pretty URL silently returns the front page; re-save Settings → Permalinks, or give your AI client the REST fallback URL.', 'betterdocs' ),
				$url,
				$status,
				'' !== $content_type ? $content_type : __( 'no content type', 'betterdocs' )
			);

			return $out;
		}

		if ( ! isset( $decoded['jsonrpc'] ) ) {
			$out['stage']  = 'rewrite';
			$out['detail'] = sprintf(
				/* translators: 1: endpoint URL, 2: HTTP status code. */
				__( '%1$s answered %2$d with JSON that is not a JSON-RPC response, so something other than the MCP endpoint is serving this URL. Re-save Settings → Permalinks, or use the REST fallback URL.', 'betterdocs' ),
				$url,
				$status
			);

			return $out;
		}

		$out['authenticated'] = true;

		$tools = isset( $decoded['result']['tools'] ) && is_array( $decoded['result']['tools'] )
			? $decoded['result']['tools']
			: null;

		// An EMPTY tool list is a failure, not a pass: that is precisely the
		// shape of a connection an AI client calls healthy while having nothing
		// to call. The registry snapshot goes in the detail, so support can
		// tell "no BetterDocs abilities registered" from "the runtime is
		// missing entirely".
		if ( 200 !== $status || null === $tools || [] === $tools ) {
			$out['stage']  = 'no_tools';
			$out['tools']  = is_array( $tools ) ? count( $tools ) : 0;
			$out['detail'] = sprintf(
				/* translators: 1: endpoint URL, 2: abilities-registry diagnostic summary. */
				__( '%1$s answered but returned no tool catalog. Confirm the MCP runtime shipped with this build and that abilities registered. Diagnostics — %2$s', 'betterdocs' ),
				$url,
				AbilitiesRegistrar::summary()
			);

			return $out;
		}

		$out['tools']  = count( $tools );
		$out['detail'] = sprintf(
			/* translators: 1: endpoint URL, 2: number of tools returned. */
			__( '%1$s authenticated and returned %2$d tools.', 'betterdocs' ),
			$url,
			count( $tools )
		);

		return $out;
	}

	/**
	 * Fetch the OAuth discovery documents and confirm they identify this site.
	 *
	 * Both shapes are probed: the `/.well-known/` pair a spec-compliant client
	 * derives from the issuer, and the REST aliases the 401 challenge points at
	 * (ADR-014). A host that intercepts `/.well-known/` — an nginx `location`
	 * block for ACME is the usual culprit — breaks the first pair while the
	 * aliases keep working, and that is a **caveat, not a failure**: every
	 * client we know of reaches the metadata through the challenge.
	 *
	 * @since 4.9.0
	 *
	 * @return array `{ stage, detail, documents, caveat }`
	 */
	private function probe_discovery() {
		$path = MCPPairing::SITE_ENDPOINT_PATH;

		$well_known = [
			home_url( '/.well-known/oauth-protected-resource/' . $path )   => 'resource',
			home_url( '/.well-known/oauth-authorization-server/' . $path ) => 'issuer'
		];

		$aliases = [
			MCPOAuth::resource_metadata_url() => 'resource',
			rest_url( MCPManager::NS . '/mcp/oauth/authorization-server' ) => 'issuer'
		];

		$expected = [
			'resource' => MCPOAuth::resource(),
			'issuer'   => MCPOAuth::issuer()
		];

		$documents = [];

		$well_known_result = $this->fetch_metadata( $well_known, $expected, $documents );
		$alias_result      = $this->fetch_metadata( $aliases, $expected, $documents );

		// The aliases are the pair the challenge points at, so they are the
		// ones that must work.
		if ( null !== $alias_result ) {
			return [
				'stage'     => 'discovery',
				'detail'    => $alias_result,
				'documents' => $documents,
				'caveat'    => ''
			];
		}

		if ( null !== $well_known_result ) {
			return [
				'stage'     => 'ok',
				'detail'    => __( 'The REST discovery aliases are served and identify this site exactly. The /.well-known/ documents are not — see the caveat below.', 'betterdocs' ),
				'documents' => $documents,
				'caveat'    => sprintf(
					/* translators: %s: the reason the /.well-known/ document could not be read. */
					__( 'This site does not serve the /.well-known/ OAuth documents, usually because the web server handles that path itself before WordPress sees it. Every client we have tested finds the metadata through the 401 challenge instead, which points at the REST alias, so this is not fatal — but a client that only tries /.well-known/ will not connect. Details: %s', 'betterdocs' ),
					$well_known_result
				)
			];
		}

		// Everything is served and self-consistent — but self-consistent with
		// whatever `home_url()` says, so a site stored as http:// while really
		// served over https:// is consistently wrong.
		$scheme_issue = $this->probe_scheme();

		if ( null !== $scheme_issue ) {
			return [
				'stage'     => 'discovery',
				'detail'    => $scheme_issue,
				'documents' => $documents,
				'caveat'    => ''
			];
		}

		return [
			'stage'     => 'ok',
			'detail'    => __( 'All four OAuth discovery documents are served and advertise this site\'s MCP endpoint exactly.', 'betterdocs' ),
			'documents' => $documents,
			'caveat'    => ''
		];
	}

	/**
	 * Fetch a set of metadata documents and check the identifier in each.
	 *
	 * A "the key exists" check is not enough: it passes on another plugin's
	 * metadata served from the same `/.well-known/` path, which is exactly the
	 * hijack the per-path rewrite rules exist to avoid. The identifier has to
	 * equal the one computed here, character for character.
	 *
	 * @since 4.9.0
	 *
	 * @param array $urls      `url => identifier key`.
	 * @param array $expected  `identifier key => expected value`.
	 * @param array $documents Collected documents, by reference.
	 * @return string|null The failure detail, or null when all of them passed.
	 */
	private function fetch_metadata( array $urls, array $expected, array &$documents ) {
		foreach ( $urls as $url => $key ) {
			$response = wp_remote_get(
				$url,
				[
					'timeout'     => self::TIMEOUT,
					'redirection' => 0,
					'local'       => true,
					'sslverify'   => self::verify_certificate( $url )
				]
			);

			if ( is_wp_error( $response ) ) {
				return sprintf(
					/* translators: 1: discovery document URL, 2: transport error. */
					__( 'The OAuth discovery document %1$s could not be fetched: %2$s. Clients that connect by URL alone cannot authenticate without it.', 'betterdocs' ),
					$url,
					$response->get_error_message()
				);
			}

			$status = (int) wp_remote_retrieve_response_code( $response );
			$raw    = (string) wp_remote_retrieve_body( $response );
			$body   = json_decode( $raw, true );

			$documents[ $url ] = is_array( $body ) ? $body : $raw;

			if ( 200 !== $status || ! is_array( $body ) || ! isset( $body[ $key ] ) ) {
				return sprintf(
					/* translators: 1: discovery document URL, 2: HTTP status code. */
					__( 'The OAuth discovery document %1$s returned %2$d instead of valid metadata. Re-save Settings → Permalinks; if it persists, the web server or another plugin is claiming that URL.', 'betterdocs' ),
					$url,
					$status
				);
			}

			$advertised = (string) $body[ $key ];

			if ( $advertised !== $expected[ $key ] ) {
				return sprintf(
					/* translators: 1: metadata field name, 2: value found in the document, 3: the value it should carry, 4: discovery document URL. */
					__( 'The discovery document %4$s advertises %1$s "%2$s" but this site\'s MCP endpoint is "%3$s". RFC 9728 requires an exact match, so clients reject the metadata and report that the server does not implement OAuth. If the two differ only by scheme, a reverse proxy is terminating TLS without passing X-Forwarded-Proto; otherwise something else is serving this URL.', 'betterdocs' ),
					$key,
					$advertised,
					$expected[ $key ],
					$url
				);
			}
		}

		return null;
	}

	/**
	 * Catch the reverse-proxy scheme trap.
	 *
	 * WordPress stores an `http://` home URL, so every advertised OAuth
	 * identifier is `http://`, while the site is really served over `https://`.
	 * Everything is internally consistent, so no comparison against our own
	 * values can see it — the only tell is that the `https://` variant of the
	 * endpoint answers too.
	 *
	 * @since 4.9.0
	 *
	 * @return string|null The detail, or null when nothing is wrong.
	 */
	private function probe_scheme() {
		$endpoint = MCPPairing::site_endpoint();

		if ( 'https' === wp_parse_url( $endpoint, PHP_URL_SCHEME ) ) {
			return null;
		}

		$secure = set_url_scheme( $endpoint, 'https' );

		if ( null === $this->probe_status( $secure, null ) ) {
			// No HTTPS at all. A plain-HTTP site is its own (reported) problem,
			// not the proxy misconfiguration this check is for.
			return null;
		}

		return sprintf(
			/* translators: 1: the http:// endpoint the documents advertise, 2: the https:// endpoint that also answers. */
			__( 'The discovery documents advertise %1$s, but %2$s answers as well — WordPress is storing an http:// Site Address behind a proxy that terminates TLS. AI clients connect over https and reject the http identifier as a mismatch. Fix the Site Address in Settings → General, or have the proxy send X-Forwarded-Proto.', 'betterdocs' ),
			$endpoint,
			$secure
		);
	}

	/**
	 * Confirm an unauthenticated call answers 401 *with* the challenge.
	 *
	 * A client that connects by URL alone has nothing else to discover OAuth
	 * from: a bare 401, or any other status, reads to it as "this server does
	 * not implement OAuth".
	 *
	 * @since 4.9.0
	 *
	 * @param string $endpoint Pretty endpoint URL.
	 * @param string $fallback REST fallback URL.
	 * @return array `{ stage, detail }`
	 */
	private function probe_challenge( $endpoint, $fallback ) {
		$answered = false;

		foreach ( [ $endpoint, $fallback ] as $url ) {
			$response = $this->unauthenticated_probe( $url, null );

			if ( is_wp_error( $response ) ) {
				continue; // Reachability is another check's job.
			}

			$answered = true;

			$status    = (int) wp_remote_retrieve_response_code( $response );
			$challenge = (string) wp_remote_retrieve_header( $response, 'www-authenticate' );

			if ( 401 !== $status ) {
				return [
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: 1: endpoint URL, 2: HTTP status code. */
						__( 'An unauthenticated call to %1$s answered %2$d instead of 401. Clients that connect by URL alone need the 401 challenge to start the OAuth flow.', 'betterdocs' ),
						$url,
						$status
					)
				];
			}

			if ( '' === $challenge ) {
				return [
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: %s: endpoint URL. */
						__( '%s answered 401 but sent no WWW-Authenticate header — a security plugin or proxy is most likely stripping it. Clients that connect by URL alone will report that this server does not implement OAuth.', 'betterdocs' ),
						$url
					)
				];
			}

			// The challenge is only useful if the URL inside it resolves: that
			// URL is the client's entire entry point into the flow.
			if ( ! preg_match( '/resource_metadata="([^"]+)"/i', $challenge, $matches ) ) {
				return [
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: 1: endpoint URL, 2: the WWW-Authenticate header value received. */
						__( '%1$s sent a WWW-Authenticate header with no resource_metadata URL (%2$s). Clients have nowhere to look up this site\'s OAuth metadata.', 'betterdocs' ),
						$url,
						$challenge
					)
				];
			}

			$metadata_url = $matches[1];
			$metadata     = wp_remote_get(
				$metadata_url,
				[
					'timeout'     => self::TIMEOUT,
					'redirection' => 2, // A host-level redirect to the real document is fine.
					'local'       => true,
					'sslverify'   => self::verify_certificate( $metadata_url )
				]
			);

			$reachable = ! is_wp_error( $metadata )
				&& 200 === (int) wp_remote_retrieve_response_code( $metadata )
				&& is_array( json_decode( (string) wp_remote_retrieve_body( $metadata ), true ) );

			if ( ! $reachable ) {
				return [
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: 1: the resource_metadata URL from the challenge header, 2: endpoint URL. */
						__( 'The challenge from %2$s points at %1$s, but that URL does not return OAuth metadata. It is the first thing a client fetches, so the connection fails there.', 'betterdocs' ),
						$metadata_url,
						$url
					)
				];
			}
		}

		// Neither URL answered at all. Reporting `ok` here would be the exact
		// false pass this test exists to prevent — an unreachable endpoint is
		// not a passing challenge. The other checks name the reachability
		// failure; this one only has to refuse to claim success.
		if ( ! $answered ) {
			return [
				'stage'  => 'challenge',
				'detail' => __( 'The OAuth challenge could not be checked because the endpoint did not answer. Fix the connection error above and run the test again.', 'betterdocs' )
			];
		}

		return [
			'stage'  => 'ok',
			'detail' => __( 'Unauthenticated calls answer with the OAuth challenge, so clients that have only the URL can authenticate.', 'betterdocs' )
		];
	}

	/**
	 * Detect a host that answers WordPress but refuses generic clients by User-Agent.
	 *
	 * Two blind spots, both worth stating plainly. This runs from the server's own
	 * IP, which host firewalls usually trust, so it catches User-Agent filtering
	 * but **not** an IP-range block of the AI vendor: a green result does not prove
	 * an external client can connect. And the User-Agents below are representative,
	 * not the ones any particular vendor sends, so a red result does not prove one
	 * cannot — which is why this never sets the overall verdict (ADR-067).
	 *
	 * @since 4.9.0
	 *
	 * @param string $endpoint Pretty endpoint URL.
	 * @return array|null `{ stage, detail }`, or null when it could not run.
	 */
	private function probe_user_agent( $endpoint ) {
		$baseline = $this->probe_status( $endpoint, null );

		if ( null === $baseline ) {
			return null; // Endpoint unreachable — another check owns that.
		}

		foreach ( self::CLIENT_USER_AGENTS as $agent ) {
			$status = $this->probe_status( $endpoint, $agent );

			if ( null === $status || $status === $baseline ) {
				continue;
			}

			// A different status is only damning when it is a refusal. An MCP
			// answer — a 401 challenge, a 200, a 202 — is fine under any UA.
			if ( in_array( $status, [ 200, 202, 401 ], true ) ) {
				continue;
			}

			return [
				'stage'  => 'ua_filter',
				'detail' => sprintf(
					/* translators: 1: the User-Agent string tried, 2: the HTTP status it received, 3: the HTTP status WordPress' own User-Agent received. */
					__( 'The endpoint answered %3$d for WordPress but %2$d for a generic HTTP client\'s User-Agent (%1$s). A security plugin, firewall or "block bad bots" rule is refusing non-browser clients. Whether that affects your assistant depends on the User-Agent it sends, which this test cannot see — connect it and check that tools load. If they do not, exempt the MCP and /.well-known/ paths.', 'betterdocs' ),
					$agent,
					$status,
					$baseline
				)
			];
		}

		return [
			'stage'  => 'ok',
			'detail' => __( 'The endpoint answers generic HTTP clients the same way it answers WordPress, so no bot filter is refusing them. This cannot see an IP-level block of the AI vendor.', 'betterdocs' )
		];
	}

	/**
	 * Add the rate-limiter finding to the result.
	 *
	 * The loopback above can pass while a *remote* client is walled off by the
	 * failed-authentication limiter — the state a connector still holding a
	 * rotated-away token produces. It matters more than it looks: a lockout
	 * refuses **valid** credentials from that IP too, so behind a reverse proxy,
	 * where every client shares one `REMOTE_ADDR`, one stale connector locks
	 * out everybody (Batch 2 review).
	 *
	 * @since 4.9.0
	 *
	 * @param array $result The result being built, by reference.
	 * @return void
	 */
	private function add_lockout_check( array &$result ) {
		$lockouts = MCPRateLimiter::active_lockouts();

		$result['locked_clients'] = $lockouts;

		if ( null === $lockouts || $lockouts < 1 ) {
			return;
		}

		$detail = sprintf(
			/* translators: 1: number of locked-out clients, 2: the filter name that overrides client identification. */
			_n(
				'%1$d client address is locked out after repeated failed authentications — usually a connector still holding a rotated-away token. While a lockout holds, that address is refused even when it presents a valid token, so behind a reverse proxy (where every client shares one address) one stale connector can wall off everyone; the `%2$s` filter is how you teach the limiter to read the real client address. Lockouts clear within 15 minutes of the retries stopping.',
				'%1$d client addresses are locked out after repeated failed authentications — usually connectors still holding rotated-away tokens. While a lockout holds, that address is refused even when it presents a valid token, so behind a reverse proxy (where every client shares one address) one stale connector can wall off everyone; the `%2$s` filter is how you teach the limiter to read the real client address. Lockouts clear within 15 minutes of the retries stopping.',
				$lockouts,
				'betterdocs'
			),
			$lockouts,
			'betterdocs_mcp_client_ip'
		);

		$result['checks'][] = self::check( 'lockouts', __( 'Client lockouts', 'betterdocs' ), 'locked_clients', $detail );

		// A lockout is a warning, not a broken connection: the loopback just
		// proved the endpoint works. It must not overwrite a genuine ladder
		// failure, so it is recorded as a caveat and the stage is left alone.
		$result['caveats'][] = $detail;
	}

	// -- Helpers --------------------------------------------------------------

	/**
	 * One unauthenticated `initialize` call.
	 *
	 * @since 4.9.0
	 *
	 * @param string      $url   Endpoint to call.
	 * @param string|null $agent User-Agent to send, or null for WordPress' own.
	 * @return array|\WP_Error
	 */
	private function unauthenticated_probe( $url, $agent ) {
		$args = [
			'timeout'     => self::TIMEOUT,
			'redirection' => 0,
			'local'       => true,
			'sslverify'   => self::verify_certificate( $url ),
			'headers'     => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json'
			],
			'body'        => wp_json_encode(
				[
					'jsonrpc' => '2.0',
					'id'      => 1,
					'method'  => 'initialize',
					'params'  => []
				]
			)
		];

		if ( null !== $agent ) {
			$args['user-agent'] = $agent;
		}

		return wp_remote_post( $url, $args );
	}

	/**
	 * Status code of one unauthenticated probe, or null if it never answered.
	 *
	 * @since 4.9.0
	 *
	 * @param string      $url   Endpoint to call.
	 * @param string|null $agent User-Agent to send, or null for WordPress' own.
	 * @return int|null
	 */
	private function probe_status( $url, $agent ) {
		$response = $this->unauthenticated_probe( $url, $agent );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		return (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Whether to verify the certificate on one loopback request.
	 *
	 * Since WordPress 5.9 the HTTP API runs on the Requests 2 transport, which
	 * applies `https_ssl_verify` and **never** `https_local_ssl_verify` — that
	 * filter survives only in the legacy cURL/streams transports nothing uses
	 * any more. Core's own loopback callers therefore apply it themselves and
	 * pass the answer as `sslverify` (`cron.php`, `WP_Site_Health`,
	 * `wp-admin/includes/file.php`); measured on WordPress 7.1, and this does
	 * the same, so the advice in the `tls` message actually works.
	 *
	 * The default is `true`, not core's `false`: skipping verification would
	 * make this test report a healthy connection on a site whose certificate no
	 * real client will accept, which is the exact false pass it exists to
	 * prevent. Relaxing it stays a deliberate, filtered choice.
	 *
	 * @since 4.9.0
	 *
	 * @param string $url URL about to be requested.
	 * @return bool|string
	 */
	private static function verify_certificate( $url ) {
		/** This filter is documented in wp-includes/class-wp-http-streams.php */
		return apply_filters( 'https_local_ssl_verify', true, $url );
	}

	/**
	 * Whether a transport error is a certificate problem.
	 *
	 * @since 4.9.0
	 *
	 * @param string $message Transport error message.
	 * @return bool
	 */
	private static function is_tls_error( $message ) {
		$message = (string) $message;

		return false !== stripos( $message, 'ssl' )
			|| false !== stripos( $message, 'certificate' )
			|| false !== stripos( $message, 'tls' );
	}

	/**
	 * Shape one check for the UI list.
	 *
	 * @since 4.9.0
	 *
	 * @param string $id     Check id.
	 * @param string $label  Human-readable label.
	 * @param string $stage  Resulting stage (`ok` when it passed).
	 * @param string $detail Explanatory line.
	 * @return array
	 */
	private static function check( $id, $label, $stage, $detail ) {
		return [
			'id'     => $id,
			'label'  => $label,
			'ok'     => 'ok' === $stage,
			'detail' => $detail
		];
	}
}
