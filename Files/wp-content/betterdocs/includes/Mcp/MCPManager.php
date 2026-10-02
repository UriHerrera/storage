<?php
/**
 * MCP manager — rewrites, the pretty endpoint, discovery and the REST surface.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * BetterDocs speaks MCP directly at this site's own URL, so the user pastes
 * their own site into their AI client — endpoint plus token, or, for
 * OAuth-capable clients, the URL and nothing else:
 *
 *     https://thissite.com/betterdocs/mcp                (pretty, via rewrite)
 *     https://thissite.com/betterdocs/mcp/<64-hex>       (token in the path)
 *     https://thissite.com/wp-json/betterdocs/v1/mcp     (always-on fallback)
 *
 * This class owns the transport: the rewrite rules, `parse_request` handling for
 * the pretty paths, the discovery documents, and every REST route. The JSON-RPC
 * itself is {@see MCPServer}; the tool surface is {@see MCPTools}; auth is
 * {@see MCPPairing} or {@see MCPOAuth}.
 *
 * `enable_mcp` is the master switch. Off means the endpoint, the discovery
 * documents and the OAuth register/token routes all refuse. It never gates
 * ability registration, and it never gates `/mcp/health` (ADR-013) — a health
 * report you can only read when the thing is already working is useless.
 *
 * Discovery is served four ways on purpose (ADR-014):
 *
 * - `/.well-known/oauth-{protected-resource,authorization-server}/betterdocs/mcp`
 *   — the RFC 9728 §3.1 / RFC 8414 §3.1 path-**insert** form, which is what a
 *   spec-compliant client derives from our path-based issuer.
 * - the bare root form, as a fallback for clients that only try that.
 * - `/betterdocs/mcp/.well-known/…` — the older OpenID Connect **suffix**
 *   convention; clients built on an OIDC library often try that shape first.
 * - REST aliases under `betterdocs/v1`, which the 401 challenge points at, so a
 *   host that intercepts `/.well-known/` cannot break the handshake.
 *
 * The path-specific well-known rules matter for coexistence: rewrite rules are
 * keyed by their regex, so two plugins sharing one broad rule would silently
 * overwrite each other depending on registration order.
 *
 * @since 4.9.0
 */
final class MCPManager {

	/**
	 * REST namespace shared with the rest of the plugin.
	 *
	 * @since 4.9.0
	 */
	const NS = 'betterdocs/v1';

	/**
	 * Query var flagging a pretty `/betterdocs/mcp` request.
	 *
	 * @since 4.9.0
	 */
	const QUERY_VAR = 'betterdocs_mcp';

	/**
	 * Query var carrying the token when it arrives in the URL path.
	 *
	 * @since 4.9.0
	 */
	const TOKEN_QUERY_VAR = 'betterdocs_mcp_token';

	/**
	 * Query var flagging a `/.well-known/` OAuth discovery request.
	 *
	 * @since 4.9.0
	 */
	const WELLKNOWN_QUERY_VAR = 'betterdocs_mcp_wellknown';

	/**
	 * Query var flagging the browser-facing OAuth consent page.
	 *
	 * Served **outside** the REST API deliberately: a REST route only honours
	 * cookie auth when a REST nonce comes with it, and a browser arriving from
	 * `wp-login.php` carries the cookie and no nonce — so `is_user_logged_in()`
	 * would be false there and the consent screen would loop back to login for
	 * ever. A normal front-end URL sees ordinary cookie auth.
	 *
	 * @since 4.9.0
	 */
	const AUTHORIZE_QUERY_VAR = 'betterdocs_mcp_authorize';

	/**
	 * Bumped whenever the rewrite rule *set* changes shape, to force a single
	 * flush that evicts rules retired in an earlier version. Stored against
	 * `betterdocs_mcp_rewrite_ver`; see {@see self::maybe_flush()}.
	 *
	 * @since 4.9.3
	 */
	const REWRITE_VER = '2';

	/**
	 * Whether a rewrite flush has already been triggered this request.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private static $flushed = false;

	/**
	 * The side-effect-free report behind `GET /mcp/health`.
	 *
	 * @since 4.9.0
	 *
	 * @var MCPHealth
	 */
	private $health;

	/**
	 * The loopback ladder behind `POST /mcp/self-test`.
	 *
	 * @since 4.9.0
	 *
	 * @var MCPSelfTest
	 */
	private $self_test;

	/**
	 * Registers every hook. Resolved from the container in
	 * `Plugin::initialize()`, which runs on `init` at priority 0; the two
	 * diagnostics are autowired alongside it.
	 *
	 * @since 4.9.0
	 *
	 * @param MCPHealth    $health    Health reporter.
	 * @param MCPSelfTest $self_test Loopback self-test.
	 */
	public function __construct( MCPHealth $health, MCPSelfTest $self_test ) {
		$this->health    = $health;
		$this->self_test = $self_test;

		add_action( 'init', [ $this, 'add_rewrite' ] );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		// Claim our own discovery URLs on `do_parse_request`, which runs before
		// the rewrite table is even consulted, so another plugin's broad
		// `.well-known/oauth-*` catch-all rewrite cannot answer BetterDocs' own
		// discovery URL with its `resource`. Scoped to `betterdocs/mcp` only —
		// this never intercepts anyone else's path. Priority 0 so it wins over a
		// rival that also hooks here late.
		add_filter( 'do_parse_request', [ $this, 'serve_own_discovery' ], 0 );
		add_action( 'parse_request', [ $this, 'maybe_handle_pretty_endpoint' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		add_action( 'admin_notices', [ $this, 'warn_when_runtime_missing' ] );

		// The page's master switch writes through BetterDocs' own settings
		// route, so this is where "MCP was just turned on" is observable. Mint
		// there as well as on the first status read, so the token exists before
		// the page asks for it (ADR-056).
		add_action( 'betterdocs::settings::saved', [ $this, 'mint_on_enable' ], 10, 3 );

		// Deleting or demoting a user kills the grants they made. Attached from
		// here rather than from `Plugin` so the
		// MCP transport and its grant lifecycle come up together.
		MCPGrants::init();
	}

	/**
	 * Whether the MCP integration is switched on.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! function_exists( 'betterdocs' ) ) {
			return false;
		}

		$plugin = betterdocs();

		if ( ! is_object( $plugin ) || ! isset( $plugin->settings ) || ! is_object( $plugin->settings ) ) {
			return false;
		}

		return (bool) $plugin->settings->get( 'enable_mcp', false );
	}

	/**
	 * Register the rewrite rules, and self-heal the rewrite table.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function add_rewrite() {
		foreach ( self::rules() as $regex => $query ) {
			add_rewrite_rule( $regex, $query, 'top' );
		}

		// No broad `(?:/.*)?` catch-all: that regex is identical across every
		// plugin built on this transport, so it becomes a single shared rewrite
		// key whose winner answers *every* plugin's `/.well-known/oauth-*/*`
		// discovery URL — returning its own `resource` for a path it does not
		// own, which RFC 9728 clients reject on the exact-match check. Each rule
		// in self::rules() is scoped to `betterdocs/mcp`, so only our own
		// discovery URLs route here. The bare-root form is intentionally not
		// served: RFC 9728 clients derive the path-suffixed URL from the MCP
		// endpoint, and a suffix-less URL cannot disambiguate two MCP plugins on
		// one site anyway.
		self::maybe_flush();
	}

	/**
	 * The rewrite rules this plugin owns, as `regex => query`.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private static function rules() {
		return [
			// Token-in-URL form: one string the user pastes into a client that
			// has no separate token field. The bare path still takes a Bearer
			// header.
			'^betterdocs/mcp/([a-f0-9]{64})/?$' => 'index.php?' . self::QUERY_VAR . '=1&' . self::TOKEN_QUERY_VAR . '=$matches[1]',
			'^betterdocs/mcp/?$'                => 'index.php?' . self::QUERY_VAR . '=1',
			'^\.well-known/oauth-(protected-resource|authorization-server)/betterdocs/mcp/?$' => 'index.php?' . self::WELLKNOWN_QUERY_VAR . '=$matches[1]',
			'^betterdocs/mcp/\.well-known/oauth-(protected-resource|authorization-server)/?$' => 'index.php?' . self::WELLKNOWN_QUERY_VAR . '=$matches[1]',
			'^betterdocs/mcp/\.well-known/openid-configuration/?$' => 'index.php?' . self::WELLKNOWN_QUERY_VAR . '=authorization-server',
			'^betterdocs/authorize/?$'          => 'index.php?' . self::AUTHORIZE_QUERY_VAR . '=1'
		];
	}

	/**
	 * Flush once if any rule of ours is missing from the stored table, so the
	 * endpoints work without a manual permalink re-save — and so a rule added in
	 * a later version installs itself on upgrade.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	private static function maybe_flush() {
		if ( self::$flushed ) {
			return;
		}

		// One-time flush when the rule set changes shape between versions. The
		// missing-rule check below only *adds* rules; it never evicts one that
		// was removed — such as the retired `(?:/.*)?` catch-all, which would
		// otherwise linger in the stored table and keep answering other plugins'
		// discovery URLs until permalinks were re-saved by hand.
		if ( self::REWRITE_VER !== (string) get_option( 'betterdocs_mcp_rewrite_ver', '' ) ) {
			self::$flushed = true;
			flush_rewrite_rules( false );
			update_option( 'betterdocs_mcp_rewrite_ver', self::REWRITE_VER, false );

			return;
		}

		$rules = get_option( 'rewrite_rules' );

		if ( ! is_array( $rules ) ) {
			return;
		}

		foreach ( array_keys( self::rules() ) as $regex ) {
			if ( ! isset( $rules[ $regex ] ) ) {
				self::$flushed = true;
				flush_rewrite_rules( false );

				return;
			}
		}
	}

	/**
	 * Register our query vars.
	 *
	 * @since 4.9.0
	 *
	 * @param string[] $vars Registered query vars.
	 * @return string[]
	 */
	public function register_query_vars( $vars ) {
		if ( ! is_array( $vars ) ) {
			return $vars;
		}

		$vars[] = self::QUERY_VAR;
		$vars[] = self::TOKEN_QUERY_VAR;
		$vars[] = self::WELLKNOWN_QUERY_VAR;
		$vars[] = self::AUTHORIZE_QUERY_VAR;

		return $vars;
	}

	/**
	 * Serve BetterDocs' own OAuth discovery documents straight from the request
	 * URI, before WordPress matches any rewrite rule.
	 *
	 * This is what makes the discovery URLs hijack-proof: the rewrite table is a
	 * flat, order-dependent list shared by every plugin, so a plugin whose broad
	 * `.well-known/oauth-*` catch-all happens to sit above our path-specific rule
	 * would otherwise answer our own URL with its `resource`. Matching the URI
	 * here — on `do_parse_request`, before rules are consulted — sidesteps that
	 * ordering entirely. The patterns are anchored to `betterdocs/mcp`, so this
	 * only ever claims BetterDocs' own paths and never intercepts another
	 * plugin's discovery URL. When MCP is off it does nothing and lets the
	 * request fall through.
	 *
	 * @since 4.9.3
	 *
	 * @param bool $continue Whether WordPress should continue parsing the request.
	 * @return bool The unchanged flag when this is not one of our URLs; otherwise
	 *              the response is emitted and the request exits.
	 */
	public function serve_own_discovery( $continue ) {
		if ( ! self::is_enabled() ) {
			return $continue;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path only, matched with a literal-anchored regex, never stored or output.
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		// RFC 9728 path-insert form and the OIDC suffix form both name a document.
		if (
			preg_match( '#/\.well-known/oauth-(protected-resource|authorization-server)/betterdocs/mcp/?$#', $path, $m )
			|| preg_match( '#/betterdocs/mcp/\.well-known/oauth-(protected-resource|authorization-server)/?$#', $path, $m )
		) {
			$this->emit_discovery( $m[1] );
		}

		if ( preg_match( '#/betterdocs/mcp/\.well-known/openid-configuration/?$#', $path ) ) {
			$this->emit_discovery( 'authorization-server' );
		}

		return $continue;
	}

	/**
	 * Serve the pretty paths.
	 *
	 * Runs on `parse_request`, before the main query, and short-circuits
	 * WordPress entirely.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP $wp The WP request object.
	 * @return void
	 */
	public function maybe_handle_pretty_endpoint( $wp ) {
		$vars = isset( $wp->query_vars ) && is_array( $wp->query_vars ) ? $wp->query_vars : [];

		if ( ! empty( $vars[ self::WELLKNOWN_QUERY_VAR ] ) ) {
			$this->emit_discovery( (string) $vars[ self::WELLKNOWN_QUERY_VAR ] );

			return;
		}

		if ( ! empty( $vars[ self::AUTHORIZE_QUERY_VAR ] ) ) {
			// The master switch is checked inside, so a switched-off site
			// answers with the same branded page as every other refusal
			// rather than a bare status line.
			$this->handle_authorize_page();

			return;
		}

		if ( empty( $vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		// We never open an SSE stream, so there is nothing to GET here. Say so
		// with the method the client should have used, rather than letting the
		// JSON-RPC layer answer a parse error to an empty body.
		if ( 'POST' !== strtoupper( (string) self::request_method() ) ) {
			status_header( 405 );
			header( 'Allow: POST' );
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Cache-Control: no-store, private' );
			echo wp_json_encode(
				[
					'error'   => 'method_not_allowed',
					'message' => 'The BetterDocs MCP endpoint accepts POST only.'
				]
			);
			exit;
		}

		$request = new \WP_REST_Request( 'POST', '/' . self::NS . '/mcp' );
		$request->set_header( 'content-type', 'application/json' );

		$auth = self::server_header( 'authorization' );

		if ( null !== $auth ) {
			$request->set_header( 'authorization', $auth );
		}

		// A token in the path is surfaced as a Bearer header, so there is one
		// place that reads a credential. A real header, if also sent, wins.
		$path_token = isset( $vars[ self::TOKEN_QUERY_VAR ] ) ? (string) $vars[ self::TOKEN_QUERY_VAR ] : '';

		if ( '' !== $path_token && '' === (string) $request->get_header( 'authorization' ) ) {
			$request->set_header( 'authorization', 'Bearer ' . $path_token );
		}

		$request->set_body( (string) file_get_contents( 'php://input' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading the raw request body; there is no WordPress API for it.

		$this->emit_json( MCPServer::handle( $request ) );
	}

	/**
	 * Emit a discovery document from the pretty path.
	 *
	 * @since 4.9.0
	 *
	 * @param string $doc `protected-resource` or `authorization-server`.
	 * @return void
	 */
	private function emit_discovery( $doc ) {
		// Only answer BetterDocs' own discovery URLs. Every rule that sets the
		// well-known query var carries `betterdocs/mcp` in its path, so a request
		// that lacks it reached here through some other plugin's catch-all
		// rewrite — 404 rather than hand back BetterDocs metadata for a resource
		// we do not own (which an RFC 9728 client would reject anyway). This also
		// guards the window after an upgrade, before a retired catch-all is
		// flushed out of the stored rewrite table.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path only, compared with strpos, never stored or output.
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		if ( ! self::is_enabled() || false === strpos( $path, 'betterdocs/mcp' ) ) {
			status_header( 404 );
			exit;
		}

		status_header( 200 );
		header( 'Content-Type: application/json; charset=utf-8' );
		// Discovery metadata is public and stable — unlike everything else this
		// class serves, it is safe to cache and expensive to re-fetch.
		header( 'Cache-Control: public, max-age=3600' );

		echo wp_json_encode( self::discovery_document( $doc ) );
		exit;
	}

	/**
	 * The discovery document for a name.
	 *
	 * @since 4.9.0
	 *
	 * @param string $doc `protected-resource` or `authorization-server`.
	 * @return array
	 */
	private static function discovery_document( $doc ) {
		return 'authorization-server' === $doc
			? MCPOAuth::authorization_server_metadata()
			: MCPOAuth::protected_resource_metadata();
	}

	/**
	 * The browser-facing OAuth consent page.
	 *
	 * Served through a rewrite rather than as a REST route, so ordinary cookie
	 * authentication works after the `wp-login.php` round trip — see
	 * {@see self::AUTHORIZE_QUERY_VAR} for why a REST route cannot.
	 *
	 * The order of the checks is deliberate. The master switch comes first, then
	 * the visitor, then the OAuth parameters: a logged-out prober therefore
	 * learns nothing about which client ids this site has registered, because it
	 * is sent to the login screen either way.
	 *
	 * `GET` renders the consent screen. `POST` is the nonce-checked submission:
	 * Approve issues a single-use authorization code and redirects to the
	 * client's registered `redirect_uri`; Deny redirects there with
	 * `error=access_denied`. Either way this method emits its own response —
	 * an HTML page or a redirect — and exits.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function handle_authorize_page() {
		if ( ! self::is_enabled() ) {
			$this->emit_oauth_error_page(
				__( 'MCP is switched off', 'betterdocs' ),
				__( 'This site is not accepting AI client connections right now. An administrator can switch MCP on under BetterDocs → MCP.', 'betterdocs' ),
				404
			);
		}

		$is_post = 'POST' === strtoupper( (string) self::request_method() );

		// The parameters arrive on the query string for the consent link and in
		// the body for the form submit. The nonce is verified below, before any
		// POST value is acted on; the GET side is an ordinary OAuth
		// authorization request and carries none by design.
		// phpcs:disable WordPress.Security.NonceVerification
		$source = $is_post ? $_POST : $_GET;
		// phpcs:enable WordPress.Security.NonceVerification

		$params = [];

		foreach ( [ 'client_id', 'redirect_uri', 'response_type', 'code_challenge', 'code_challenge_method', 'scope', 'state', 'approve', 'deny', '_betterdocs_oauth_nonce' ] as $key ) {
			$params[ $key ] = isset( $source[ $key ] ) ? sanitize_text_field( wp_unslash( $source[ $key ] ) ) : '';
		}

		if ( ! is_user_logged_in() ) {
			$this->redirect_to_login();
		}

		// ADR-006: anyone who can edit docs may connect a client. They grant
		// only their own powers — every ability re-checks its own capability —
		// and the floor is the one `MCPServer` impersonates against, so a
		// grant approved here can never be dead on arrival.
		if ( ! current_user_can( MCPServer::IMPERSONATION_CAPABILITY ) ) {
			$this->emit_oauth_error_page(
				__( 'This account cannot connect an AI client', 'betterdocs' ),
				sprintf(
					/* translators: %s: the required WordPress capability, e.g. edit_docs. */
					__( 'Your account can\'t connect an AI client to BetterDocs: it needs the "%s" capability. Ask an administrator, or use bd-get-status\'s capability list.', 'betterdocs' ),
					MCPServer::IMPERSONATION_CAPABILITY
				),
				403
			);
		}

		$req = MCPOAuth::validate_authorize_request( $params );

		if ( is_wp_error( $req ) ) {
			$data         = $req->get_error_data();
			$redirectable = is_array( $data ) && ! empty( $data['redirectable'] );

			// Report the error back to the client only when the destination is
			// one this site registered for it. An unknown client, or a
			// redirect_uri that matches nothing, never gets a redirect — that
			// is the open-redirect guard.
			if ( $redirectable && '' !== $params['redirect_uri'] ) {
				$this->redirect_error( $params['redirect_uri'], $req->get_error_code(), $req->get_error_message(), $params['state'] );
			}

			$this->emit_oauth_error_page( __( 'Could not authorize', 'betterdocs' ), $req->get_error_message(), 400 );
		}

		if ( $is_post ) {
			if ( ! wp_verify_nonce( $params['_betterdocs_oauth_nonce'], 'betterdocs_oauth_consent' ) ) {
				$this->emit_oauth_error_page(
					__( 'Security check failed', 'betterdocs' ),
					__( 'This consent form is no longer valid. Start the connection again from your AI client.', 'betterdocs' ),
					403
				);
			}

			if ( '' === $params['approve'] ) {
				$this->redirect_error( $req['redirect_uri'], 'access_denied', __( 'The user denied the request.', 'betterdocs' ), $req['state'] );
			}

			$this->redirect_success( $req['redirect_uri'], MCPOAuth::issue_code( $req, get_current_user_id() ), $req['state'] );
		}

		$this->emit_consent_screen( $req );
	}

	/**
	 * The absolute URL of the authorize request being served.
	 *
	 * Used as the return address for the `wp-login.php` round trip.
	 * `home_url()` re-anchors the path on this site, so a crafted
	 * `REQUEST_URI` cannot turn the login redirect into an off-site one.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function current_authorize_url() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- rebuilding this site's own URL; home_url() anchors it to this host and wp_login_url() escapes it.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		return home_url( $uri );
	}

	/**
	 * Send an anonymous visitor to wp-login, returning here afterwards.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	private function redirect_to_login() {
		wp_safe_redirect( wp_login_url( $this->current_authorize_url() ) );
		exit;
	}

	/**
	 * Redirect back to the client with the authorization code.
	 *
	 * @since 4.9.0
	 *
	 * @param string $redirect_uri The client's validated redirect URI.
	 * @param string $code         The authorization code.
	 * @param string $state        The client's opaque state value.
	 * @return void
	 */
	private function redirect_success( $redirect_uri, $code, $state ) {
		$args = [ 'code' => rawurlencode( (string) $code ) ];

		if ( '' !== (string) $state ) {
			$args['state'] = rawurlencode( (string) $state );
		}

		// Not wp_safe_redirect(): `redirect_uri` is the client's own off-site
		// callback, and `validate_authorize_request()` has already matched it
		// against the set this site registered for that client.
		wp_redirect( add_query_arg( $args, $redirect_uri ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- validated OAuth redirect_uri.
		exit;
	}

	/**
	 * Redirect back to the client with an OAuth error.
	 *
	 * @since 4.9.0
	 *
	 * @param string $redirect_uri The client's validated redirect URI.
	 * @param string $error        OAuth error code.
	 * @param string $description  Human-readable description.
	 * @param string $state        The client's opaque state value.
	 * @return void
	 */
	private function redirect_error( $redirect_uri, $error, $description, $state ) {
		$args = [
			'error'             => rawurlencode( (string) $error ),
			'error_description' => rawurlencode( (string) $description )
		];

		if ( '' !== (string) $state ) {
			$args['state'] = rawurlencode( (string) $state );
		}

		wp_redirect( add_query_arg( $args, $redirect_uri ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- validated OAuth redirect_uri.
		exit;
	}

	/**
	 * Render the consent screen and stop.
	 *
	 * Self-contained HTML: no theme, no admin chrome, no enqueued asset. This
	 * page is shown to someone arriving from an AI client, often mid-handshake,
	 * and it has to look the same on every site whatever the theme does.
	 *
	 * @since 4.9.0
	 *
	 * @param array $req Validated authorize parameters from {@see MCPOAuth::validate_authorize_request()}.
	 * @return void
	 */
	private function emit_consent_screen( array $req ) {
		$scope     = isset( $req['scope'] ) ? (string) $req['scope'] : 'mcp';
		$read_only = MCPOAuth::scope_is_read_only( $scope );
		$user      = wp_get_current_user();
		$site_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		$client = isset( $req['client_name'] ) && '' !== (string) $req['client_name']
			? (string) $req['client_name']
			: __( 'An AI assistant', 'betterdocs' );

		// Where the authorization code is about to be sent, and everything this
		// page is willing to say about it. Client registration is open
		// (RFC 7591) and `client_name` is whatever the registrant typed, so the
		// name alone proves nothing — anyone can register "Claude" pointing at
		// their own callback. The callback host is the one field an attacker
		// cannot choose freely, so the mark and the warning are both read from
		// it and never from the name (ADR-064).
		$callback = self::callback_identity( (string) $req['redirect_uri'] );

		$access_label = $read_only
			? __( 'Read-only', 'betterdocs' )
			: __( 'Read & write', 'betterdocs' );

		$access_desc = $read_only
			? __( 'It can read your documentation, but it cannot change anything on this site.', 'betterdocs' )
			: __( 'It acts as you: anything it creates, edits or deletes is recorded under your account.', 'betterdocs' );

		$display_name = '' !== $user->display_name ? $user->display_name : $user->user_login;
		$role_label   = self::current_user_role_label();

		// The initial, not `get_avatar()`: a Gravatar is a third-party image
		// request, and this page must not tell anyone that this person is
		// approving this app right now (ADR-064).
		$initial = strtoupper( mb_substr( $display_name, 0, 1 ) );

		// Preserve every OAuth parameter, so the POST re-validates identically.
		$hidden = '';

		foreach ( [ 'client_id', 'redirect_uri', 'code_challenge', 'scope', 'state' ] as $key ) {
			$hidden .= sprintf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $key ),
				esc_attr( isset( $req[ $key ] ) ? (string) $req[ $key ] : '' )
			);
		}

		// Re-asserted as literals: nothing else can have reached this point.
		$hidden .= '<input type="hidden" name="code_challenge_method" value="S256" />';
		$hidden .= '<input type="hidden" name="response_type" value="code" />';
		$hidden .= '<input type="hidden" name="_betterdocs_oauth_nonce" value="' . esc_attr( wp_create_nonce( 'betterdocs_oauth_consent' ) ) . '" />';

		$this->page_headers( 200 );

		echo '<!doctype html><html lang="' . esc_attr( str_replace( '_', '-', get_locale() ) ) . '"><head><meta charset="utf-8">';
		echo '<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer">';
		echo '<title>' . esc_html__( 'Connect an AI client to BetterDocs', 'betterdocs' ) . '</title>';
		echo '<style>' . self::page_styles() . '</style></head><body><main class="card consent">'; // phpcs:ignore WordPress.Security.EscapeOutput -- static stylesheet.

		// --- Identity ---------------------------------------------------------
		// Two tiles, the app and this site, joined by an arrow: who is asking,
		// and what they are asking about. Every other fact on the page hangs off
		// this one.
		echo '<div class="lockup">';

		// Every client tile is white with a hairline border and the glyph in its
		// own colour: the two vendor marks carry their own fill from the file,
		// and our own glyphs take the tint as their stroke. One treatment for
		// the whole row, so a mark we drew never looks more or less endorsed
		// than a mark a vendor drew (ADR-066, replacing ADR-065's split).
		$tile_class = 'tile plain';
		$tile_style = 'color:' . $callback['tint'];

		echo '<div class="idt"><span class="' . esc_attr( $tile_class ) . '" style="' . esc_attr( $tile_style ) . '">'
			. self::client_mark( $callback['mark'] ) // phpcs:ignore WordPress.Security.EscapeOutput -- static icon markup chosen by key.
			. '</span><span class="idn"><b>' . esc_html( $client ) . '</b>';

		if ( $callback['trusted'] ) {
			echo '<span class="host">' . esc_html( $callback['host'] ) . '</span>';
		} else {
			echo '<span class="host warn">' . self::warn_mark() . esc_html( $callback['host'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static icon markup; the host is esc_html'd.
		}

		echo '</span></div>';

		echo '<div class="link" aria-hidden="true"><i></i><em>' . self::arrow_mark() . '</em><i></i></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static icon markup.

		echo '<div class="idt"><span class="tile bd">' . self::brand_mark( 30 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static icon markup.
		echo '<span class="idn"><b>BetterDocs</b><span class="host">' . esc_html( $site_host ) . '</span></span></div>';

		echo '</div>';

		echo '<p class="idline">' . sprintf(
			/* translators: 1: the AI client's name, escaped and wrapped in <strong>. 2: this site's host, escaped and wrapped in <strong>. */
			esc_html__( '%1$s wants to work with the documentation on %2$s.', 'betterdocs' ),
			'<strong>' . esc_html( $client ) . '</strong>',
			'<strong>' . esc_html( $site_host ) . '</strong>'
		) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- translated literal; both substitutions are esc_html'd above.

		// --- Access -----------------------------------------------------------
		echo '<div class="acc"><div class="top">';
		echo '<span class="badge' . ( $read_only ? ' ro' : '' ) . '">' . esc_html( $access_label ) . '</span>';
		echo '<p>' . esc_html( $access_desc ) . '</p>';
		echo '</div></div>';

		// --- Who is approving --------------------------------------------------
		if ( '' !== $role_label ) {
			$who = sprintf(
				/* translators: 1: the login of the person approving, escaped and wrapped in <strong>. 2: their role. */
				esc_html__( 'Signed in as %1$s &middot; %2$s', 'betterdocs' ),
				'<strong>' . esc_html( $display_name ) . '</strong>',
				esc_html( $role_label )
			);
		} else {
			$who = sprintf(
				/* translators: %s: the login of the person approving, escaped and wrapped in <strong>. */
				esc_html__( 'Signed in as %s', 'betterdocs' ),
				'<strong>' . esc_html( $display_name ) . '</strong>'
			);
		}

		echo '<p class="who"><span class="avatar" aria-hidden="true">' . esc_html( $initial ) . '</span><span>'
			. $who // phpcs:ignore WordPress.Security.EscapeOutput -- translated literal; every substitution was esc_html'd where it was built.
			. '</span></p>';

		// --- What it will be able to do ----------------------------------------
		// A native <details>: the seven lines are one click away and still on the
		// page, and the disclosure works with scripts off, which this page must.
		$can = self::consent_capability_lines( $read_only );

		if ( ! empty( $can ) ) {
			echo '<details class="disc"><summary>' . sprintf(
				/* translators: 1: the AI client's name. 2: how many things it will be able to do. */
				esc_html__( 'What %1$s will be able to do (%2$d)', 'betterdocs' ),
				esc_html( $client ),
				count( $can )
			) . self::chevron_mark() . '</summary><ul class="caps">'; // phpcs:ignore WordPress.Security.EscapeOutput -- translated literal; the client name is esc_html'd and the icon is static.

			foreach ( $can as $line ) {
				echo '<li>' . self::tick_mark() . '<span>' . esc_html( $line ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static icon markup; the line is esc_html'd.
			}

			echo '</ul></details>';
		}

		echo '<p class="note">' . self::lock_mark() . '<span>' . esc_html__( 'Secured with OAuth. You can revoke this app at any time under BetterDocs → MCP.', 'betterdocs' ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static icon markup; the text is esc_html'd.

		echo '<form method="post" action="' . esc_url( MCPOAuth::authorize_url() ) . '">';
		echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput -- every value escaped with esc_attr() where it was built.
		echo '<div class="actions">';
		echo '<button type="submit" class="deny" name="deny" value="1">' . esc_html__( 'Deny', 'betterdocs' ) . '</button>';
		echo '<button type="submit" class="approve" name="approve" value="1">' . esc_html__( 'Approve', 'betterdocs' ) . '</button>';
		echo '</div></form></main></body></html>';

		exit;
	}

	/**
	 * Render a standalone OAuth error page and stop.
	 *
	 * Never carries a code, a token or any other secret: this page is reachable
	 * by anyone who can guess the URL.
	 *
	 * @since 4.9.0
	 *
	 * @param string $title   Short headline.
	 * @param string $message What went wrong, in plain language.
	 * @param int    $status  HTTP status code.
	 * @return void
	 */
	private function emit_oauth_error_page( $title, $message, $status = 400 ) {
		$this->page_headers( $status );

		echo '<!doctype html><html lang="' . esc_attr( str_replace( '_', '-', get_locale() ) ) . '"><head><meta charset="utf-8">';
		echo '<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer">';
		echo '<title>' . esc_html__( 'Authorization error', 'betterdocs' ) . '</title>';
		echo '<style>' . self::page_styles() . '</style></head><body><main class="card center">'; // phpcs:ignore WordPress.Security.EscapeOutput -- static stylesheet.
		echo '<div class="brand"><span class="logo">' . self::brand_mark() . '</span><b>BetterDocs</b></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
		echo '<h1>' . esc_html( (string) $title ) . '</h1>';
		echo '<p class="sub">' . esc_html( (string) $message ) . '</p>';
		echo '</main></body></html>';

		exit;
	}

	/**
	 * Status and security headers shared by both browser pages.
	 *
	 * `X-Frame-Options` and `Referrer-Policy` are the two that matter here: the
	 * consent screen grants an access token on one click, so it must never be
	 * framed, and the authorization code lands in a URL the browser must not
	 * leak onward in a `Referer`.
	 *
	 * @since 4.9.0
	 *
	 * @param int $status HTTP status code.
	 * @return void
	 */
	private function page_headers( $status ) {
		status_header( (int) $status );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Cache-Control: no-store' );
		header( 'X-Frame-Options: DENY' );
		header( 'Referrer-Policy: no-referrer' );
	}

	/**
	 * What this grant lets the app do, in the approving user's own terms.
	 *
	 * Derived from `current_user_can()` over the capabilities the abilities
	 * actually gate on, because an OAuth grant carries exactly the powers of the
	 * person approving it and nothing more (ADR-006).
	 *
	 * @since 4.9.0
	 *
	 * @param bool $read_only Whether the requested scope is read-only.
	 * @return string[] Human-readable lines; may be empty.
	 */
	private static function consent_capability_lines( $read_only ) {
		// [ capability, read-only phrasing, read-write phrasing ]. The
		// capability is held in a variable on purpose: these are BetterDocs'
		// own capabilities, not core's.
		$map = [
			[ 'edit_docs', __( 'Read docs, categories, tags and knowledge bases', 'betterdocs' ), __( 'Create and edit docs', 'betterdocs' ) ],
			[ 'delete_docs', '', __( 'Trash and delete docs', 'betterdocs' ) ],
			[ 'manage_doc_terms', '', __( 'Create and manage doc categories and tags', 'betterdocs' ) ],
			[ 'manage_knowledge_base_terms', '', __( 'Create and manage knowledge bases (BetterDocs Pro)', 'betterdocs' ) ],
			[ 'edit_others_docs', __( 'Read FAQs and FAQ groups', 'betterdocs' ), __( 'Create and manage FAQs and FAQ groups', 'betterdocs' ) ],
			[ 'edit_docs_settings', __( 'Read BetterDocs settings (API keys stay hidden)', 'betterdocs' ), __( 'Read and change BetterDocs settings', 'betterdocs' ) ],
			[ 'read_docs_analytics', __( 'Read documentation analytics', 'betterdocs' ), __( 'Read documentation analytics', 'betterdocs' ) ]
		];

		$lines = [];

		foreach ( $map as $entry ) {
			list( $capability, $read_label, $write_label ) = $entry;

			$label = $read_only ? $read_label : $write_label;

			if ( '' === $label || ! current_user_can( $capability ) ) {
				continue;
			}

			$lines[] = $label;
		}

		return array_values( array_unique( $lines ) );
	}

	/**
	 * The approving user's role, as a translated label.
	 *
	 * @since 4.9.0
	 *
	 * @return string Empty when the user somehow holds no role.
	 */
	private static function current_user_role_label() {
		$user = wp_get_current_user();

		if ( ! isset( $user->roles ) || ! is_array( $user->roles ) || empty( $user->roles ) ) {
			return '';
		}

		$slug  = (string) reset( $user->roles );
		$roles = wp_roles();
		$names = is_object( $roles ) && method_exists( $roles, 'get_names' ) ? $roles->get_names() : [];

		return isset( $names[ $slug ] ) ? translate_user_role( $names[ $slug ] ) : $slug;
	}

	/**
	 * The BetterDocs mark, inlined.
	 *
	 * Inline rather than an `<img>` from `assets/`: this page must render
	 * identically with no second request, on a site whose asset URLs may be
	 * behind a CDN or an offline dev host.
	 *
	 * @since 4.9.0
	 *
	 * @param int $size Edge length in pixels. 22 in the error page's header
	 *                  lockup, 30 in the consent screen's identity tile.
	 * @return string
	 */
	private static function brand_mark( $size = 22 ) {
		$size = (int) $size;

		return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
			. '<path d="M7.60796 6.34451H11.6196C12.011 6.34451 12.3289 6.01678 12.3289 5.61342C12.3289 5.21006 12.011 4.88232 11.6196 4.88232H7.60796C7.21658 4.88232 6.89859 5.21006 6.89859 5.61342C6.89043 6.01678 7.21658 6.34451 7.60796 6.34451Z" fill="#fff"/>'
			. '<path d="M5.99453 9.19326H10.0061C10.3975 9.19326 10.7155 8.86553 10.7155 8.46217C10.7155 8.05881 10.3975 7.73108 10.0061 7.73108H5.99453C5.60315 7.73108 5.28516 8.05881 5.28516 8.46217C5.28516 8.86553 5.60315 9.19326 5.99453 9.19326Z" fill="#fff"/>'
			. '<path d="M9.07685 11.3698C9.07685 10.9664 8.75885 10.6387 8.36747 10.6387H4.35586C3.96448 10.6387 3.64648 10.9664 3.64648 11.3698C3.64648 11.7731 3.96448 12.1009 4.35586 12.1009H8.36747C8.75885 12.1009 9.07685 11.7731 9.07685 11.3698Z" fill="#fff"/>'
			. '<path d="M14.5798 8.0084C14.5554 7.94958 14.5228 7.90756 14.4901 7.85714L15.5583 5.95798C16.1453 4.92437 16.1453 3.68908 15.5664 2.65546C14.9875 1.62185 13.952 1 12.786 1H7.94273C6.80936 1 5.74938 1.63025 5.17047 2.64706L0.441324 11.042C-0.145742 12.0756 -0.145742 13.3109 0.43317 14.3445C1.01208 15.3782 2.0476 16 3.21358 16H11.5059C12.9817 16 14.3352 15.2269 15.118 13.9412C15.9089 12.6555 15.9904 11.0588 15.3544 9.68908L14.5798 8.0084ZM4.43664 14.4454H3.21358C2.5939 14.4454 2.0476 14.1176 1.73776 13.563C1.42792 13.0168 1.43608 12.3613 1.74592 11.8067L6.47506 3.41176C6.77675 2.87395 7.34751 2.53782 7.95088 2.53782H12.7942C13.4139 2.53782 13.9602 2.86555 14.27 3.42017C14.5798 3.96639 14.5717 4.62185 14.2618 5.17647L9.52454 13.5714C9.22286 14.1092 8.66025 14.4454 8.05688 14.4454H4.43664ZM13.8542 13.1092C13.3486 13.9412 12.468 14.4454 11.514 14.4454H10.7721C10.7884 14.4118 10.8128 14.3866 10.8291 14.3529L13.5932 9.45378L14.0172 10.3529C14.4168 11.2437 14.3597 12.2773 13.8542 13.1092Z" fill="#fff"/>'
			. '</svg>';
	}

	/**
	 * A small check mark for the capability list.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function tick_mark() {
		return '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="20 6 9 17 4 12"/></svg>';
	}

	/**
	 * A small padlock for the footer note.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function lock_mark() {
		return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
	}

	/**
	 * The mark for one client, by key.
	 *
	 * Two kinds of drawing live here. `claude` and `openai` are the vendors' own
	 * published logo files, used exactly as published — path, viewBox and fill
	 * unchanged — each keeping its own colour on a white tile, which is why
	 * neither uses `currentColor` (ADR-066). `cursor`, `code` and `generic` are
	 * our own glyphs on the shared 24-unit grid and take the surrounding text
	 * colour. `react-src/admin/mcp/components/Icons.js` carries the same five
	 * marks for the admin page, so a client looks the same wherever it is drawn:
	 * change one and change both.
	 *
	 * Every mark is inline and self-contained: **no remote image may ever be
	 * fetched or referenced here** — it would leak the visit and the visitor's
	 * IP to a third party at exactly the moment they are deciding whether to
	 * trust that third party.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key One of `claude`, `openai`, `cursor`, `code`, `generic`.
	 * @return string
	 */
	private static function client_mark( $key ) {
		// The vendors' own files, kept verbatim. Do not recolour, re-draw or
		// re-grid them: an approximation of somebody else's logo is precisely
		// what ADR-066 exists to undo.
		$files = [
			'claude' => [ '0 0 100 100', 'hsl(14.8, 63.1%, 59.6%)', 'm19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z' ],
			'openai' => [ '0 0 320 320', '#000000', 'm297.06 130.97c7.26-21.79 4.76-45.66-6.85-65.48-17.46-30.4-52.56-46.04-86.84-38.68-15.25-17.18-37.16-26.95-60.13-26.81-35.04-.08-66.13 22.48-76.91 55.82-22.51 4.61-41.94 18.7-53.31 38.67-17.59 30.32-13.58 68.54 9.92 94.54-7.26 21.79-4.76 45.66 6.85 65.48 17.46 30.4 52.56 46.04 86.84 38.68 15.24 17.18 37.16 26.95 60.13 26.8 35.06.09 66.16-22.49 76.94-55.86 22.51-4.61 41.94-18.7 53.31-38.67 17.57-30.32 13.55-68.51-9.94-94.51zm-120.28 168.11c-14.03.02-27.62-4.89-38.39-13.88.49-.26 1.34-.73 1.89-1.07l63.72-36.8c3.26-1.85 5.26-5.32 5.24-9.07v-89.83l26.93 15.55c.29.14.48.42.52.74v74.39c-.04 33.08-26.83 59.9-59.91 59.97zm-128.84-55.03c-7.03-12.14-9.56-26.37-7.15-40.18.47.28 1.3.79 1.89 1.13l63.72 36.8c3.23 1.89 7.23 1.89 10.47 0l77.79-44.92v31.1c.02.32-.13.63-.38.83l-64.41 37.19c-28.69 16.52-65.33 6.7-81.92-21.95zm-16.77-139.09c7-12.16 18.05-21.46 31.21-26.29 0 .55-.03 1.52-.03 2.2v73.61c-.02 3.74 1.98 7.21 5.23 9.06l77.79 44.91-26.93 15.55c-.27.18-.61.21-.91.08l-64.42-37.22c-28.63-16.58-38.45-53.21-21.95-81.89zm221.26 51.49-77.79-44.92 26.93-15.54c.27-.18.61-.21.91-.08l64.42 37.19c28.68 16.57 38.51 53.26 21.94 81.94-7.01 12.14-18.05 21.44-31.2 26.28v-75.81c.03-3.74-1.96-7.2-5.2-9.06zm26.8-40.34c-.47-.29-1.3-.79-1.89-1.13l-63.72-36.8c-3.23-1.89-7.23-1.89-10.47 0l-77.79 44.92v-31.1c-.02-.32.13-.63.38-.83l64.41-37.16c28.69-16.55 65.37-6.7 81.91 22 6.99 12.12 9.52 26.31 7.15 40.1zm-168.51 55.43-26.94-15.55c-.29-.14-.48-.42-.52-.74v-74.39c.02-33.12 26.89-59.96 60.01-59.94 14.01 0 27.57 4.92 38.34 13.88-.49.26-1.33.73-1.89 1.07l-63.72 36.8c-3.26 1.85-5.26 5.31-5.24 9.06l-.04 89.79zm14.63-31.54 34.65-20.01 34.65 20v40.01l-34.65 20-34.65-20z' ]
		];

		if ( isset( $files[ $key ] ) ) {
			$f = $files[ $key ];

			return '<svg width="30" height="30" viewBox="' . $f[0] . '" fill="' . $f[1] . '" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
				. '<path d="' . $f[2] . '"/></svg>';
		}

		// Cursor's arrow is a closed shape, drawn filled rather than as a
		// hairline outline — the same drawing the vendor's own icon uses, and
		// the shape reads at 28px where a 1.8-unit stroke would not.
		if ( 'cursor' === $key ) {
			return '<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
				. '<path d="M4 3l16 7-6.6 2.4L11 20 4 3z"/></svg>';
		}

		// [ stroke width, path data ] on the shared 24x24 grid.
		$strokes = [
			'code'    => [ '1.8', '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>' ],
			'generic' => [ '1.8', '<path d="M12 3v18M3 12h18M6.3 6.3l11.4 11.4M17.7 6.3L6.3 17.7"/>' ]
		];

		$icon = isset( $strokes[ $key ] ) ? $strokes[ $key ] : $strokes['generic'];

		return '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="'
			. $icon[0] . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icon[1] . '</svg>';
	}

	/**
	 * The warning triangle shown beside an untrusted callback host.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function warn_mark() {
		return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4.5"/><path d="M12 17.2h.01"/></svg>';
	}

	/**
	 * The arrow joining the two tiles of the identity lockup.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function arrow_mark() {
		return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<path d="M4.5 12h14"/><path d="M13 6.5 18.5 12 13 17.5"/></svg>';
	}

	/**
	 * The chevron on the capability disclosure's summary.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function chevron_mark() {
		return '<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<polyline points="6 9.5 12 15.5 18 9.5"/></svg>';
	}

	/**
	 * Everything the consent screen may say about a callback URL.
	 *
	 * Registration (RFC 7591, {@see MCPOAuth::register_client()}) stores three
	 * fields — `name`, `redirect_uris`, `created`. `logo_uri` is deliberately
	 * not among them and must not become one: it is attacker-supplied, so a
	 * hostile client could register itself with Anthropic's logo and wear it on
	 * this screen, and fetching it would tell a third party the exact moment
	 * this person sat down to approve an app, from their IP (ADR-064).
	 *
	 * That leaves the callback host as the only identity a client cannot choose
	 * freely — it is where the authorization code is about to be sent, so a
	 * client that lies about it gets nothing. Both the mark and the warning are
	 * read from it.
	 *
	 * @since 4.9.0
	 *
	 * @param string $redirect_uri The validated `redirect_uri` from the request.
	 * @return array {
	 *     @type string $host    What to print: host, plus `:port` when there is one.
	 *     @type string $mark    Mark key for {@see self::client_mark()}.
	 *     @type string $tint    Brand colour for the tile, `#rrggbb`.
	 *     @type bool   $solid   Whether the mark is a vendor's own logo file
	 *                           rather than one of our glyphs. Retained for the
	 *                           tested contract; this screen gives every tile
	 *                           the same treatment now (ADR-066).
	 *     @type bool   $trusted Whether the host may be stated quietly.
	 * }
	 */
	private static function callback_identity( $redirect_uri ) {
		$redirect_uri = (string) $redirect_uri;
		$scheme       = strtolower( (string) wp_parse_url( $redirect_uri, PHP_URL_SCHEME ) );
		$host         = strtolower( (string) wp_parse_url( $redirect_uri, PHP_URL_HOST ) );
		$port         = wp_parse_url( $redirect_uri, PHP_URL_PORT );

		// Native clients may register a custom scheme with no host at all
		// (`myapp:/cb`). Nothing is known about those, so they get the generic
		// mark and the warning, and the whole URI is what we can honestly print.
		$display = '' === $host ? $redirect_uri : $host;

		if ( '' !== $host && $port ) {
			// The port is part of the identity: two locally-running apps differ
			// by nothing else.
			$display .= ':' . (int) $port;
		}

		$mark     = self::client_mark_for_host( $host );
		$loopback = 'code' === $mark['mark'];

		// Untrusted is the default, and three separate things earn it: a host
		// this site does not recognise, a raw IP literal off the loopback, and
		// cleartext `http://` anywhere but the loopback. A recognised host over
		// https is trusted; so is the editor/CLI loopback flow, which is http
		// by nature and never leaves the machine.
		$trusted = true;

		if ( ! $mark['known'] ) {
			$trusted = false;
		} elseif ( ! $loopback && self::is_ip_literal( $host ) ) {
			$trusted = false;
		} elseif ( ! $loopback && 'https' !== $scheme ) {
			$trusted = false;
		}

		return [
			'host'    => $display,
			'mark'    => $mark['mark'],
			'tint'    => $mark['tint'],
			'solid'   => $mark['solid'],
			'trusted' => $trusted
		];
	}

	/**
	 * Pick a client mark from a callback host.
	 *
	 * Matched **exactly or on a dot boundary**, never as a bare substring:
	 * `claude.ai` and `foo.claude.ai` are Claude, while
	 * `claude.ai.attacker.example`, `notclaude.ai` and `claude.ai.` are not and
	 * fall through to the generic mark. A screen that gets this wrong tells the
	 * person about to click Approve a lie about who they are talking to.
	 *
	 * @since 4.9.0
	 *
	 * @param string $host Bare callback host, lower-cased, no port.
	 * @return array {
	 *     @type string $mark  One of `claude`, `openai`, `cursor`, `code`, `generic`.
	 *     @type string $tint  Brand colour, `#rrggbb`.
	 *     @type bool   $solid Whether the mark is a vendor's own logo file.
	 *     @type bool   $known Whether the host was recognised at all.
	 * }
	 */
	private static function client_mark_for_host( $host ) {
		$host = strtolower( (string) $host );

		// Host => [ mark, tint, solid ]. Nothing here is keyed on the client's
		// *name* on purpose (ADR-064). `solid` marks the hosts whose glyph is a
		// vendor's own logo file rather than one of ours; every tile on this
		// screen is white either way now (ADR-066), so nothing here reads it —
		// it stays because `callback_identity()`'s shape is pinned by tests.
		$map = [
			'claude.ai'   => [ 'claude', '#d97757', true ],
			'chatgpt.com' => [ 'openai', '#000000', true ],
			'openai.com'  => [ 'openai', '#000000', true ],
			'cursor.sh'   => [ 'cursor', '#0f172a', true ],
			'localhost'   => [ 'code', '#0098ff', false ],
			'127.0.0.1'   => [ 'code', '#0098ff', false ],
			'[::1]'       => [ 'code', '#0098ff', false ]
		];

		foreach ( $map as $known => $triple ) {
			if ( self::host_matches( $host, $known ) ) {
				return [
					'mark'  => $triple[0],
					'tint'  => $triple[1],
					'solid' => $triple[2],
					'known' => true
				];
			}
		}

		return [
			'mark'  => 'generic',
			'tint'  => '#00b884',
			'solid' => false,
			'known' => false
		];
	}

	/**
	 * Whether `$host` is `$known` itself or a subdomain of it.
	 *
	 * The whole point is the dot: a plain `strpos()` or a `str_ends_with()`
	 * without it would hand `notclaude.ai` Claude's mark.
	 *
	 * @since 4.9.0
	 *
	 * @param string $host  Candidate host, already lower-cased.
	 * @param string $known Known host, lower-case.
	 * @return bool
	 */
	private static function host_matches( $host, $known ) {
		$host  = (string) $host;
		$known = (string) $known;

		if ( '' === $host || '' === $known ) {
			return false;
		}

		if ( $host === $known ) {
			return true;
		}

		// An IP literal has no subdomains. `evil.127.0.0.1` is a name somebody
		// else can own; it is not this machine, and it must not inherit the
		// loopback's trust.
		if ( self::is_ip_literal( $known ) ) {
			return false;
		}

		return strlen( $host ) > strlen( $known )
			&& substr( $host, - ( strlen( $known ) + 1 ) ) === '.' . $known;
	}

	/**
	 * Whether a host is a bare IP address rather than a name.
	 *
	 * `wp_parse_url()` hands back IPv6 hosts still wrapped in their brackets,
	 * which `FILTER_VALIDATE_IP` will not take.
	 *
	 * @since 4.9.0
	 *
	 * @param string $host Bare host, no port.
	 * @return bool
	 */
	private static function is_ip_literal( $host ) {
		$host = trim( (string) $host, '[]' );

		return '' !== $host && false !== filter_var( $host, FILTER_VALIDATE_IP );
	}

	/**
	 * The inline stylesheet shared by the consent and error pages.
	 *
	 * BetterDocs' own tokens (`docs/design-system.md`): brand green `#00b884`,
	 * cards at radius 12–16px, `--text-color-*` neutrals.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function page_styles() {
		return '*{box-sizing:border-box}'
			. 'body{font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;'
			. 'background:#f7f8fa radial-gradient(900px 460px at 50% -12%,#ecfdf3,rgba(247,248,250,0)) no-repeat;'
			. 'color:#101828;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}'
			. '.card{width:100%;max-width:460px;background:#fff;border:1px solid #e4e7ec;border-radius:16px;padding:28px;box-shadow:0 12px 40px rgba(16,24,40,.08)}'
			. '.card.center{text-align:center;max-width:440px}'
			. '.card.consent{max-width:440px}'
			. '.brand{display:flex;align-items:center;gap:10px;margin-bottom:20px}'
			. '.card.center .brand{justify-content:center}'
			. '.logo{width:36px;height:36px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;'
			. 'background:linear-gradient(135deg,#00c896,#00a877);box-shadow:0 6px 16px rgba(0,184,132,.32)}'
			. '.brand b{font-size:14px;font-weight:700;letter-spacing:.01em;color:#101828}'
			. 'h1{font-size:20px;line-height:1.3;font-weight:700;margin:0 0 6px}'
			. '.sub{color:#667085;font-size:13.5px;margin:0 0 20px}.sub strong{color:#101828;font-weight:600}'
			// -- the identity lockup: who is asking, and about what -------------
			. '.lockup{display:flex;align-items:flex-start;justify-content:center;margin:0 0 18px}'
			. '.idt{display:flex;flex-direction:column;align-items:center;gap:9px;width:116px}'
			. '.tile{width:56px;height:56px;border-radius:17px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto}'
			. '.tile.bd{background:linear-gradient(135deg,#00c896,#00a877);box-shadow:0 8px 20px rgba(0,184,132,.32)}'
			// A solid vendor tile sits beside the BetterDocs tile, which casts a
			// shadow; without one of its own the pair reads as two different
			// kinds of object.
			. '.tile.plain{background:#fff;border:1px solid #e4e7ec;box-shadow:0 6px 16px rgba(16,24,40,.10)}'
			. '.idn{display:flex;flex-direction:column;align-items:center;gap:3px}'
			. '.idn b{font-size:12px;font-weight:700;color:#344054}'
			. '.idn .host{font-size:12px;line-height:1.35;color:#667085;word-break:break-all}'
			. '.idn .host.warn{color:#b54708;font-weight:600;display:inline-flex;align-items:center;gap:4px;word-break:normal}'
			. '.idn .host.warn svg{flex:0 0 auto}'
			. '.link{display:flex;align-items:center;width:58px;margin-top:27px;color:#98a2b3}'
			. '.link i{flex:1;border-top:2px dotted #d0d5dd}'
			. '.link em{width:24px;height:24px;flex:0 0 auto;margin:0 4px;border-radius:50%;background:#fff;border:1px solid #e4e7ec;'
			. 'display:inline-flex;align-items:center;justify-content:center}'
			. '.idline{font-size:16px;line-height:1.45;color:#475467;text-align:center;margin:0 0 18px}'
			. '.idline strong{color:#101828;font-weight:700}'
			// -- the access block ------------------------------------------------
			. '.acc{border:1px solid #e4e7ec;border-radius:12px;overflow:hidden;background:#fff;margin:0 0 14px}'
			. '.acc .top{padding:14px 16px;background:#f7fefc}'
			. '.acc .top p{color:#475467;font-size:13px;margin:9px 0 0}'
			. '.badge{display:inline-flex;align-items:center;font-size:12px;font-weight:700;padding:3px 10px;border-radius:999px;'
			. 'background:#d1fadf;color:#027a48;border:1px solid #a6f4c5}'
			. '.badge.ro{background:#fef0c7;color:#b54708;border-color:#fdb022}'
			// -- who is approving ------------------------------------------------
			. '.who{display:flex;align-items:center;gap:9px;font-size:13px;color:#667085;margin:0 0 14px;padding:0 2px}'
			. '.who strong{color:#101828;font-weight:600}'
			. '.avatar{width:24px;height:24px;border-radius:50%;flex:0 0 auto;background:#eaecf0;color:#475467;font-size:11px;'
			. 'font-weight:700;display:inline-flex;align-items:center;justify-content:center}'
			// -- the capability disclosure (native <details>, no script) ----------
			. 'details.disc{border:1px solid #e4e7ec;border-radius:10px;margin:0 0 14px;background:#fff}'
			. 'details.disc>summary{list-style:none;cursor:pointer;padding:11px 14px;font-size:13.5px;font-weight:600;color:#344054;'
			. 'display:flex;align-items:center;gap:8px}'
			. 'details.disc>summary::-webkit-details-marker{display:none}'
			. 'details.disc>summary:focus-visible{outline:2px solid #00b884;outline-offset:2px;border-radius:9px}'
			. 'details.disc .chev{margin-left:auto;color:#98a2b3;transition:transform .15s}'
			. 'details.disc[open] .chev{transform:rotate(180deg)}'
			. 'details.disc .caps{margin:0;padding:2px 14px 14px}'
			. '.caps{list-style:none;margin:0 0 18px;padding:0;display:grid;gap:7px}'
			. '.caps li{display:flex;align-items:flex-start;gap:8px;font-size:13.5px;color:#344054}'
			. '.caps svg{color:#00b884;flex:0 0 auto;margin-top:3px}'
			// -- the footer note and the decision ---------------------------------
			. '.note{display:flex;align-items:center;gap:7px;color:#98a2b3;font-size:12px;margin:0 0 20px}'
			. '.note svg{flex:0 0 auto}'
			. '.actions{display:flex;gap:12px}'
			. 'button{flex:1;padding:12px;border-radius:10px;border:0;font:inherit;font-size:14px;font-weight:600;cursor:pointer;'
			. 'transition:filter .15s,background .15s,transform .05s}button:active{transform:translateY(1px)}'
			. 'button:focus-visible{outline:2px solid #00b884;outline-offset:2px}'
			. '.approve{background:#00b884;color:#fff;box-shadow:0 6px 16px rgba(0,184,132,.28)}.approve:hover{filter:brightness(1.06)}'
			. '.deny{background:#fff;color:#475467;border:1px solid #d0d5dd}.deny:hover{background:#f7f8fa}'
			. '@media (max-width:420px){.card{padding:22px}.idt{width:104px}.link{width:44px}.idline{font-size:15px}}';
	}

	/**
	 * Register every REST route.
	 *
	 * Registered directly rather than through `BaseAPI` (ADR-015): the transport
	 * routes need `__return_true` with in-handler auth, the management routes
	 * need `manage_options`, and `BaseAPI` has one permission callback per
	 * class.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function register_rest() {
		// --- Transport -------------------------------------------------------
		// `permission_callback` is `__return_true` because MCPServer does its
		// own token auth and has to answer a JSON-RPC 401 with the RFC 9728
		// challenge, not a bare WordPress permission failure.
		register_rest_route(
			self::NS,
			'/mcp',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'rest_mcp' ],
					'permission_callback' => '__return_true'
				],
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'rest_mcp_get' ],
					'permission_callback' => '__return_true'
				]
			]
		);

		// --- Discovery aliases (ADR-014) -------------------------------------
		register_rest_route(
			self::NS,
			'/mcp/oauth/protected-resource',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_protected_resource' ],
				'permission_callback' => '__return_true'
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/oauth/authorization-server',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_authorization_server' ],
				'permission_callback' => '__return_true'
			]
		);

		// --- OAuth 2.1 -------------------------------------------------------
		// Public by necessity: a client has to reach these *before* it holds any
		// credential. `/authorize` is deliberately not here — see
		// AUTHORIZE_QUERY_VAR.
		register_rest_route(
			self::NS,
			'/mcp/oauth/register',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_oauth_register' ],
				'permission_callback' => '__return_true'
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/oauth/token',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_oauth_token' ],
				'permission_callback' => '__return_true'
			]
		);

		// --- Management (the MCP admin page) ---------------------------------
		register_rest_route(
			self::NS,
			'/mcp/connection',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_connection' ],
				'permission_callback' => [ $this, 'admin_permission' ]
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/connect',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_connect' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'read_only' => [
						'type'              => 'boolean',
						'required'          => false,
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'description'       => __( 'Grant read-only access: no doc, term, FAQ or settings changes.', 'betterdocs' )
					]
				]
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/rotate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_rotate' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'read_only' => [
						'type'              => 'boolean',
						'required'          => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'description'       => __( 'Optionally set read-only on the new token; omit to keep the current scopes.', 'betterdocs' )
					]
				]
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/disconnect',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_disconnect' ],
				'permission_callback' => [ $this, 'admin_permission' ]
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/apps',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_apps' ],
				'permission_callback' => [ $this, 'admin_permission' ]
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/apps/revoke',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_revoke_app' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'client_id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __( 'The OAuth client_id to revoke.', 'betterdocs' )
					]
				]
			]
		);
		register_rest_route(
			self::NS,
			'/mcp/self-test',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_self_test' ],
				'permission_callback' => [ $this, 'admin_permission' ]
			]
		);
		// Not gated by `enable_mcp` (ADR-013): the first question an admin asks
		// is "why is this not working", and a health report that needs the
		// feature switched on cannot answer it.
		register_rest_route(
			self::NS,
			'/mcp/health',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_health' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'user' => [
						'type'        => 'integer',
						'required'    => false,
						'description' => __( 'Report this user\'s capabilities instead of the caller\'s.', 'betterdocs' )
					]
				]
			]
		);
	}

	/**
	 * Capability gate for the management routes.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * POST `/mcp` — JSON-RPC over the wp-json fallback path.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function rest_mcp( $request ) {
		return MCPServer::handle( $request );
	}

	/**
	 * GET `/mcp` — there is nothing to read here.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_mcp_get() {
		$response = new \WP_REST_Response(
			[
				'error'   => 'method_not_allowed',
				'message' => __( 'The BetterDocs MCP endpoint accepts POST only.', 'betterdocs' )
			],
			405
		);

		$response->header( 'Allow', 'POST' );
		$response->header( 'Cache-Control', 'no-store, private' );

		return $response;
	}

	/**
	 * GET `/mcp/oauth/protected-resource` — RFC 9728 metadata.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_protected_resource() {
		return $this->discovery_response( 'protected-resource' );
	}

	/**
	 * GET `/mcp/oauth/authorization-server` — RFC 8414 metadata.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_authorization_server() {
		return $this->discovery_response( 'authorization-server' );
	}

	/**
	 * A discovery document as a REST response, or a JSON 404 when MCP is off.
	 *
	 * @since 4.9.0
	 *
	 * @param string $doc `protected-resource` or `authorization-server`.
	 * @return \WP_REST_Response
	 */
	private function discovery_response( $doc ) {
		if ( ! self::is_enabled() ) {
			return new \WP_REST_Response(
				[
					'code'    => 'betterdocs_mcp_disabled',
					'message' => __( 'MCP is disabled on this site.', 'betterdocs' ),
					'data'    => [ 'status' => 404 ]
				],
				404
			);
		}

		$response = new \WP_REST_Response( self::discovery_document( $doc ), 200 );
		$response->header( 'Cache-Control', 'public, max-age=3600' );

		return $response;
	}

	/**
	 * POST `/mcp/oauth/register` — RFC 7591 dynamic client registration.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request JSON body with `redirect_uris`.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_oauth_register( $request ) {
		if ( ! self::is_enabled() ) {
			return new \WP_Error(
				'betterdocs_mcp_disabled',
				__( 'MCP is disabled on this site.', 'betterdocs' ),
				[ 'status' => 403 ]
			);
		}

		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			$body = [];
		}

		$result = MCPOAuth::register_client( $body );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = new \WP_REST_Response( $result, 201 );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * POST `/mcp/oauth/token` — the code and refresh grants.
	 *
	 * OAuth sends `application/x-www-form-urlencoded`; JSON is accepted too.
	 * Errors follow RFC 6749 §5.2 (`error` / `error_description`), not
	 * WordPress' REST error shape, because that is what OAuth clients parse.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Token request.
	 * @return \WP_REST_Response
	 */
	public function rest_oauth_token( $request ) {
		if ( ! self::is_enabled() ) {
			return self::token_error( 'invalid_request', __( 'MCP is disabled on this site.', 'betterdocs' ), 403 );
		}

		$body = $request->get_body_params();

		if ( empty( $body ) ) {
			$json = $request->get_json_params();
			$body = is_array( $json ) ? $json : [];
		}

		$body = array_map( 'strval', $body );

		$result = MCPOAuth::exchange_token( $body );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			$data = is_array( $data ) ? $data : [];

			return self::token_error(
				isset( $data['error'] ) ? (string) $data['error'] : 'invalid_request',
				isset( $data['error_description'] ) ? (string) $data['error_description'] : $result->get_error_message(),
				isset( $data['status'] ) ? (int) $data['status'] : 400
			);
		}

		$response = new \WP_REST_Response( $result, 200 );
		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'Pragma', 'no-cache' );

		return $response;
	}

	/**
	 * An RFC 6749 §5.2 error response.
	 *
	 * @since 4.9.0
	 *
	 * @param string $error       Error code.
	 * @param string $description Human-readable description.
	 * @param int    $status      HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function token_error( $error, $description, $status ) {
		$response = new \WP_REST_Response(
			[
				'error'             => (string) $error,
				'error_description' => (string) $description
			],
			(int) $status
		);

		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * GET `/mcp/connection` — pairing status for the admin page.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_connection() {
		$this->ensure_connected();

		$status = MCPPairing::public_status();

		$status['enable_mcp']        = self::is_enabled();
		$status['mcp_endpoint']      = MCPPairing::site_endpoint();
		$status['mcp_endpoint_rest'] = MCPPairing::site_endpoint_fallback();
		$status['authorize_url']     = MCPOAuth::authorize_url();
		$status['issuer']            = MCPOAuth::issuer();
		$status['discovery']         = [
			'protected_resource'   => MCPOAuth::resource_metadata_url(),
			'authorization_server' => rest_url( self::NS . '/mcp/oauth/authorization-server' )
		];

		return rest_ensure_response( $status );
	}

	/**
	 * Make sure a connection token exists whenever an administrator looks at
	 * the MCP page with MCP switched on.
	 *
	 * A site that has never minted one has no `config.cli`, no JSON block and
	 * no AI prompt — and before this existed the page answered that by hiding
	 * every client card behind a Connect button, including the two OAuth cards
	 * that need no token at all (ADR-056). Minting is idempotent
	 * ({@see MCPPairing::connect()} returns the existing record untouched) and
	 * this method is only reached from `manage_options`-gated routes, so it
	 * costs one option write on exactly one request per site.
	 *
	 * Read-write on purpose: a read-only pairing is a deliberate choice made
	 * through `POST /mcp/connect` (ADR-038), not something to fall into.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	private function ensure_connected() {
		if ( self::is_enabled() && ! MCPPairing::is_connected() ) {
			MCPPairing::connect();
		}
	}

	/**
	 * Mint the pairing token when `enable_mcp` is switched on.
	 *
	 * Hooked to BetterDocs' own settings save, which is the path the page's
	 * master switch writes through. Only an **off → on** transition mints: a
	 * save that leaves the switch alone must not resurrect a pairing an
	 * administrator deliberately disconnected.
	 *
	 * @since 4.9.0
	 *
	 * @param bool  $saved         Whether the option write succeeded.
	 * @param array $settings      The settings as saved.
	 * @param array $old_settings  The settings as they were.
	 * @return void
	 */
	public function mint_on_enable( $saved, $settings, $old_settings ) {
		$was = is_array( $old_settings ) && ! empty( $old_settings['enable_mcp'] );
		$now = is_array( $settings ) && ! empty( $settings['enable_mcp'] );

		if ( ! $was && $now ) {
			$this->ensure_connected();
		}
	}

	/**
	 * POST `/mcp/connect` — mint a connection token.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Carries optional `read_only`.
	 * @return \WP_REST_Response
	 */
	public function rest_connect( $request ) {
		return rest_ensure_response( MCPPairing::connect( (bool) $request->get_param( 'read_only' ) ) );
	}

	/**
	 * POST `/mcp/rotate` — mint a fresh token, killing the old one.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Carries optional `read_only`.
	 * @return \WP_REST_Response
	 */
	public function rest_rotate( $request ) {
		$read_only = null;

		if ( null !== $request->get_param( 'read_only' ) ) {
			$read_only = (bool) $request->get_param( 'read_only' );
		}

		return rest_ensure_response( MCPPairing::rotate( $read_only ) );
	}

	/**
	 * POST `/mcp/disconnect` — revoke the pairing token and every OAuth grant.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_disconnect() {
		return rest_ensure_response( MCPPairing::disconnect() );
	}

	/**
	 * GET `/mcp/apps` — the connected OAuth clients.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_apps() {
		return rest_ensure_response( [ 'oauth_apps' => MCPOAuth::connected_apps() ] );
	}

	/**
	 * POST `/mcp/apps/revoke` — cut off one OAuth client and return the
	 * refreshed list, so the UI updates in a single round trip.
	 *
	 * The pairing token has no per-client identity and is not listed here; it is
	 * rotated from the connection card instead.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request Carries `client_id`.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_revoke_app( $request ) {
		$client_id = (string) $request->get_param( 'client_id' );

		if ( '' === $client_id ) {
			return new \WP_Error(
				'betterdocs_missing_client_id',
				__( 'A client_id is required to revoke an OAuth app.', 'betterdocs' ),
				[ 'status' => 400 ]
			);
		}

		MCPOAuth::revoke_client( $client_id );

		return rest_ensure_response( [ 'oauth_apps' => MCPOAuth::connected_apps() ] );
	}

	/**
	 * POST `/mcp/self-test` — the loopback ladder.
	 *
	 * A `POST` rather than a `GET` because it is the one diagnostic that makes
	 * real outbound requests; nothing should trigger it by loading a page.
	 *
	 * @since 4.9.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_self_test() {
		return rest_ensure_response( $this->self_test->run() );
	}

	/**
	 * GET `/mcp/health` — the side-effect-free report.
	 *
	 * `?user=<id>` reports another user's capability set instead of the caller's,
	 * which is how support answers "why can this editor not create a doc?"
	 * without logging in as them. The route is already `manage_options`, and the
	 * report carries no secret for any user, so no further gate is needed —
	 * but an id that is not a real user is refused rather than silently
	 * reported as holding nothing.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_health( $request ) {
		$user_id = null;

		if ( is_object( $request ) && null !== $request->get_param( 'user' ) ) {
			$user_id = (int) $request->get_param( 'user' );

			if ( $user_id < 1 || ! get_user_by( 'id', $user_id ) ) {
				return new \WP_Error(
					'betterdocs_mcp_unknown_user',
					__( 'No user with that id exists on this site.', 'betterdocs' ),
					[ 'status' => 404 ]
				);
			}
		}

		return rest_ensure_response( $this->health->report( $user_id ) );
	}

	/**
	 * Admin notice when MCP is on but the bundled Abilities runtime is missing.
	 *
	 * That combination is almost always an incomplete package — a source archive,
	 * or a zip built without `dependencies/vendor/`. Everything else still works:
	 * OAuth discovers, tokens mint, clients connect, and `tools/list` is an empty
	 * array served as success. Three layers each fail softly and compose into a
	 * connector that connects and offers nothing, with no signal anywhere. Say it
	 * where an administrator will look.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function warn_when_runtime_missing() {
		if ( ! self::is_enabled() || function_exists( 'wp_register_ability' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'BetterDocs MCP: AI assistants will connect but see no tools.', 'betterdocs' ),
			esc_html__( 'MCP access is enabled, but the bundled Abilities runtime (dependencies/vendor/autoload_packages.php) is missing from this installation — usually a plugin package built without it. Reinstall BetterDocs from wordpress.org or an official build; until then, connected AI clients get an empty tool list.', 'betterdocs' )
		);
	}

	/**
	 * The live request's HTTP method.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private static function request_method() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared against a literal, never stored or output.
		return isset( $_SERVER['REQUEST_METHOD'] ) ? (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) : 'GET';
	}

	/**
	 * A header from the live PHP request.
	 *
	 * @since 4.9.0
	 *
	 * @param string $name Header name.
	 * @return string|null
	 */
	private static function server_header( $name ) {
		$key = 'HTTP_' . strtoupper( str_replace( '-', '_', (string) $name ) );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- a credential, compared in constant time downstream; it has to arrive verbatim.
		return isset( $_SERVER[ $key ] ) ? wp_unslash( $_SERVER[ $key ] ) : null;
	}

	/**
	 * Emit a `WP_REST_Response` as an HTTP response and stop.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_REST_Response $response Response to emit.
	 * @return void
	 */
	private function emit_json( $response ) {
		$status = $response->get_status();

		status_header( $status );

		foreach ( $response->get_headers() as $name => $value ) {
			// Re-assert the status on every header: PHP special-cases
			// WWW-Authenticate and forces a 401 when no status is given, which
			// would silently mask the 429 a lockout answers with.
			header( $name . ': ' . $value, true, $status );
		}

		$data = $response->get_data();

		if ( null !== $data ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $data );
		}

		exit;
	}
}
