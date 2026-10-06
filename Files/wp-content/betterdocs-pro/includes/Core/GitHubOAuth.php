<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPDeveloper\BetterDocs\Utils\Base;

/**
 * Git OAuth / Token Handler (GitHub OAuth + GitLab Token)
 *
 * GitHub: Zero-config OAuth via external auth proxy.
 * GitLab: User provides Project ID + Access Token directly.
 *
 * @package WPDeveloper\BetterDocs\Core
 * @since 4.1.3
 */
class GitHubOAuth extends Base {

	protected $settings;

	const TOKEN_OPTION    = 'betterdocs_github_oauth_token';
	const USER_OPTION     = 'betterdocs_github_oauth_user';
	const STATE_TRANSIENT = 'betterdocs_github_oauth_state';
	const DEFAULT_PROXY_URL = 'https://api.betterdocs.co';

	// GitHub App user tokens are short-lived (~8h) but come with a long-lived
	// refresh token (~6mo). We store the refresh token + absolute expiry so
	// get_valid_token() can transparently renew the access token via the proxy.
	const REFRESH_OPTION         = 'betterdocs_github_oauth_refresh';
	const EXPIRES_OPTION         = 'betterdocs_github_oauth_expires';         // access-token expiry (unix ts)
	const REFRESH_EXPIRES_OPTION = 'betterdocs_github_oauth_refresh_expires'; // refresh-token expiry (unix ts)
	const REFRESH_LOCK_TRANSIENT = 'betterdocs_github_oauth_refresh_lock';
	/**
	 * Set the first time this site completes a GitHub connection, and never
	 * cleared on disconnect — that is the whole point of it.
	 *
	 * The App's `installations/new` URL only redirects back to our callback
	 * because "Request user authorization (OAuth) during installation" is on,
	 * and that fires while an installation is being *created*. Send a site whose
	 * account already has the App installed there and GitHub shows the configure
	 * page and never calls back, so the popup waits forever for a one-time code
	 * that cannot arrive — a disconnected site could not reconnect until someone
	 * uninstalled the App from the GitHub account by hand.
	 *
	 * So this records "an installation probably already exists for this site's
	 * owner", which routes the next connect through the ordinary web flow
	 * (`flow=auth`) instead. A stale true is harmless: the web flow works
	 * whether or not the App is installed, and the "Choose repositories" action
	 * always reaches the install screen.
	 */
	const INSTALLED_OPTION = 'betterdocs_github_app_installed';
	// Refresh this many seconds BEFORE the token actually expires (clock-skew margin).
	const EXPIRY_MARGIN = 60;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		// GitHub OAuth (via proxy)
		add_action( 'wp_ajax_betterdocs_git_oauth_start', [ $this, 'ajax_oauth_start' ] );
		add_action( 'wp_ajax_betterdocs_github_oauth_callback', [ $this, 'handle_oauth_callback' ] );
		add_action( 'wp_ajax_betterdocs_github_oauth_start', [ $this, 'ajax_oauth_start' ] ); // legacy

		// Shared
		add_action( 'wp_ajax_betterdocs_git_oauth_disconnect', [ $this, 'ajax_oauth_disconnect' ] );
		add_action( 'wp_ajax_betterdocs_github_oauth_disconnect', [ $this, 'ajax_oauth_disconnect' ] );
		add_action( 'wp_ajax_betterdocs_git_get_user', [ $this, 'ajax_get_user' ] );
		add_action( 'wp_ajax_betterdocs_github_get_user', [ $this, 'ajax_get_user' ] );
		add_action( 'wp_ajax_betterdocs_git_providers', [ $this, 'ajax_providers' ] );

		// GitHub API (via proxy)
		add_action( 'wp_ajax_betterdocs_git_repos', [ $this, 'ajax_github_repos' ] );
		add_action( 'wp_ajax_betterdocs_github_repos', [ $this, 'ajax_github_repos' ] );

		// Shared branch/contents (routes to correct provider)
		add_action( 'wp_ajax_betterdocs_git_branches', [ $this, 'ajax_branches' ] );
		add_action( 'wp_ajax_betterdocs_github_branches', [ $this, 'ajax_branches' ] );
		add_action( 'wp_ajax_betterdocs_git_contents', [ $this, 'ajax_contents' ] );
		add_action( 'wp_ajax_betterdocs_github_contents', [ $this, 'ajax_contents' ] );

		// GitLab (direct API — no proxy needed)
		add_action( 'wp_ajax_betterdocs_gitlab_connect', [ $this, 'ajax_gitlab_connect' ] );
		add_action( 'wp_ajax_betterdocs_gitlab_branches', [ $this, 'ajax_gitlab_branches' ] );
		add_action( 'wp_ajax_betterdocs_gitlab_contents', [ $this, 'ajax_gitlab_contents' ] );

		// Save repo settings directly (bypasses quickbuilder form)
		add_action( 'wp_ajax_betterdocs_git_save_repo_setting', [ $this, 'ajax_save_repo_setting' ] );
	}

	private function get_proxy_url() {
		$url = rtrim( apply_filters( 'betterdocs_github_auth_proxy_url', self::DEFAULT_PROXY_URL ), '/' );

		// The proxy brokers the OAuth exchange, so tokens travel over it. A
		// filter that downgraded it to http:// — or pointed it somewhere without
		// a host — would leak them; fall back to the shipped default instead.
		return self::require_https_base( $url, rtrim( self::DEFAULT_PROXY_URL, '/' ) );
	}

	/**
	 * Require an https:// base URL with a real host, falling back when it is not.
	 *
	 * Used for both the OAuth proxy and the administrator-supplied GitLab
	 * instance URL. Both carry credentials (an OAuth token or a PRIVATE-TOKEN
	 * header) on every request, so a plaintext or malformed base would put those
	 * credentials on the wire in the clear.
	 *
	 * Self-hosted GitLab on a private network is deliberately still allowed: an
	 * administrator configured it explicitly, and blocking private hosts would
	 * break that supported setup. The scheme requirement is what protects the
	 * token.
	 *
	 * @param string $url      Candidate base URL.
	 * @param string $fallback Base URL to use when the candidate is unusable.
	 * @return string
	 */
	private static function require_https_base( $url, $fallback ) {
		$parts = wp_parse_url( (string) $url );

		if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return $fallback;
		}

		if ( strtolower( $parts['scheme'] ) !== 'https' ) {
			return $fallback;
		}

		return rtrim( $url, '/' );
	}

	public static function get_provider() {
		$user = get_option( self::USER_OPTION, null );
		return $user['provider'] ?? 'github';
	}

	// ═════════════════════════════════════════════════════════════════
	// GITHUB OAuth (via proxy)
	// ═════════════════════════════════════════════════════════════════

	public function ajax_providers() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );
		$response = wp_remote_get( $this->get_proxy_url() . '/providers', [ 'timeout' => 10 ] );
		if ( is_wp_error( $response ) ) {
			// Proxy unreachable — at minimum GitLab is always available (no proxy needed)
			wp_send_json_success( [ 'gitlab' ] );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$providers = $body['providers'] ?? [];
		// GitLab is always available since it doesn't need the proxy
		if ( ! in_array( 'gitlab', $providers, true ) ) {
			$providers[] = 'gitlab';
		}
		wp_send_json_success( $providers );
	}

	public function ajax_oauth_start() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'betterdocs-pro' ) );
		}

		$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? 'github' ) );
		$state    = wp_generate_uuid4();
		set_transient( self::STATE_TRANSIENT . '_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );

		$callback_url = admin_url( 'admin-ajax.php?action=betterdocs_github_oauth_callback' );
		$args         = [
			'provider'     => $provider,
			'callback_url' => $callback_url,
			'state'        => $state,
		];

		if ( 'github' === $provider ) {
			$args['flow'] = $this->github_auth_flow();
		}

		$auth_url = add_query_arg( $args, $this->get_proxy_url() . '/authorize' );

		wp_send_json_success( [ 'auth_url' => $auth_url, 'flow' => $args['flow'] ?? null ] );
	}

	/**
	 * Which GitHub entry point this connection attempt should use.
	 *
	 * 'install' — the App's installation screen, where the user picks which
	 *             repositories to grant. Right for a first run, and the only way
	 *             to reach the repository picker.
	 * 'auth'    — the ordinary web application flow, which mints a user token on
	 *             every call whether or not the App is installed. Required for a
	 *             reconnect; see INSTALLED_OPTION.
	 *
	 * An explicit `flow` in the request wins, so the UI can offer "Choose
	 * repositories" to a site that would otherwise be routed to 'auth'.
	 *
	 * @return string 'install' | 'auth'
	 */
	protected function github_auth_flow() {
		$requested = isset( $_POST['flow'] ) ? sanitize_key( wp_unslash( $_POST['flow'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is checked by the caller.
		if ( in_array( $requested, [ 'install', 'auth' ], true ) ) {
			return $requested;
		}

		if ( get_option( self::INSTALLED_OPTION ) ) {
			return 'auth';
		}

		// No marker, but credentials on file means this site connected before the
		// marker existed. Backfill rather than routing an upgraded site to the
		// install screen it can no longer complete.
		if ( self::get_token() || get_option( self::USER_OPTION ) ) {
			update_option( self::INSTALLED_OPTION, 1, false );
			return 'auth';
		}

		return 'install';
	}

	public function handle_oauth_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->output_popup_result( false, 'Insufficient permissions.' );
			return;
		}

		$onetime_code = isset( $_GET['onetime_code'] ) ? sanitize_text_field( wp_unslash( $_GET['onetime_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state        = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error        = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $error ) { $this->output_popup_result( false, $error ); return; }
		if ( empty( $onetime_code ) || empty( $state ) ) { $this->output_popup_result( false, 'Invalid OAuth response.' ); return; }

		$user_id      = get_current_user_id();
		$stored_state = get_transient( self::STATE_TRANSIENT . '_' . $user_id );
		if ( ! $stored_state || ! hash_equals( $stored_state, $state ) ) {
			$this->output_popup_result( false, 'OAuth state mismatch.' );
			return;
		}
		delete_transient( self::STATE_TRANSIENT . '_' . $user_id );

		$response = wp_remote_post( $this->get_proxy_url() . '/token', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [ 'code' => $onetime_code ] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) { $this->output_popup_result( false, $response->get_error_message() ); return; }

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['success'] ) || empty( $body['access_token'] ) || empty( $body['user']['login'] ) ) {
			$this->output_popup_result( false, $body['error'] ?? 'Failed to retrieve token.' );
			return;
		}

		self::store_tokens( $body );
		update_option( self::USER_OPTION, $body['user'], false );
		// Deliberately survives disconnect — see INSTALLED_OPTION.
		update_option( self::INSTALLED_OPTION, 1, false );
		$this->output_popup_result( true, null, $body['user'] );
	}

	private function output_popup_result( $success, $error_message = null, $user = null ) {
		$payload = $success
			? wp_json_encode( [ 'type' => 'betterdocs_github_oauth_success', 'user' => $user ] )
			: wp_json_encode( [ 'type' => 'betterdocs_github_oauth_error', 'message' => $error_message ] );
		?>
		<!DOCTYPE html><html><head><title><?php echo $success ? 'Connected' : 'Failed'; ?></title></head><body>
		<script>try{window.opener&&window.opener.postMessage(<?php echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON literal built with wp_json_encode for use as a JS value. ?>,<?php echo wp_json_encode( admin_url() ); ?>);}catch(e){}window.close();</script>
		<p style="font-family:sans-serif;text-align:center;margin-top:40px"><?php echo $success ? 'Connected! This window will close.' : esc_html( $error_message ); ?></p>
		</body></html>
		<?php
		exit;
	}

	/** Relay GET to auth proxy with stored GitHub token. */
	private function proxy_get( $endpoint, $params = [] ) {
		$token = self::get_valid_token();
		if ( ! $token ) return null;
		$params['provider'] = 'github';
		$url = $this->get_proxy_url() . $endpoint . '?' . http_build_query( $params );
		$r = wp_remote_get( $url, [ 'headers' => [ 'Authorization' => 'Bearer ' . $token ], 'timeout' => 30 ] );
		return ! is_wp_error( $r ) ? json_decode( wp_remote_retrieve_body( $r ), true ) : null;
	}

	public function ajax_github_repos() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );
		$data = $this->proxy_get( '/api/repos' );
		if ( ! $data || empty( $data['success'] ) ) {
			wp_send_json_error( $data['error'] ?? __( 'Could not fetch repositories.', 'betterdocs-pro' ) );
		}

		$repos = isset( $data['repos'] ) && is_array( $data['repos'] ) ? $data['repos'] : [];

		// An empty list has two very different causes and the UI has to tell them
		// apart. `installations: 0` means the account authorized the App but
		// never installed it anywhere — reachable now that reconnects use the
		// plain web flow — and the fix is to send the user to the install screen,
		// not to show an empty dropdown that reads as "you have no repositories".
		wp_send_json_success( [
			'repos'         => $repos,
			'installations' => isset( $data['installations'] ) ? (int) $data['installations'] : null,
			'install_url'   => $data['install_url'] ?? '',
		] );
	}

	// ═════════════════════════════════════════════════════════════════
	// GITLAB (direct API — user provides token)
	// ═════════════════════════════════════════════════════════════════

	/**
	 * AJAX: Validate GitLab token + project ID and save connection.
	 */
	public function ajax_gitlab_connect() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );

		$project_id   = sanitize_text_field( wp_unslash( $_POST['project_id'] ?? '' ) );
		$access_token = sanitize_text_field( wp_unslash( $_POST['access_token'] ?? '' ) );
		$instance_url = sanitize_text_field( wp_unslash( $_POST['instance_url'] ?? '' ) );

		if ( empty( $project_id ) || empty( $access_token ) ) {
			wp_send_json_error( __( 'Project ID and Access Token are required.', 'betterdocs-pro' ) );
		}

		// The PRIVATE-TOKEN header rides on every request to this base, so refuse
		// a plaintext or malformed instance URL rather than sending the token
		// over it.
		$base = $instance_url
			? self::require_https_base( $instance_url, '' )
			: 'https://gitlab.com';

		if ( $base === '' ) {
			wp_send_json_error( __( 'The GitLab instance URL must be a valid https:// address.', 'betterdocs-pro' ) );
		}

		// Validate token by fetching user info
		$user_response = wp_remote_get( $base . '/api/v4/user', [
			'headers' => [ 'PRIVATE-TOKEN' => $access_token ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $user_response ) ) {
			wp_send_json_error( $user_response->get_error_message() );
		}

		$user_body = json_decode( wp_remote_retrieve_body( $user_response ), true );
		$http_code = wp_remote_retrieve_response_code( $user_response );

		if ( $http_code === 401 || empty( $user_body['username'] ) ) {
			wp_send_json_error( __( 'Invalid access token. Please check the token and try again.', 'betterdocs-pro' ) );
		}

		// Validate project ID
		$proj_response = wp_remote_get( $base . '/api/v4/projects/' . urlencode( $project_id ), [
			'headers' => [ 'PRIVATE-TOKEN' => $access_token ],
			'timeout' => 15,
		] );

		$proj_body = json_decode( wp_remote_retrieve_body( $proj_response ), true );
		$proj_code = wp_remote_retrieve_response_code( $proj_response );

		if ( $proj_code === 404 || empty( $proj_body['id'] ) ) {
			wp_send_json_error( __( 'Project not found. Check the Project ID and ensure your token has access.', 'betterdocs-pro' ) );
		}

		// Store connection
		$user_data = [
			'login'        => $user_body['username'],
			'name'         => $user_body['name'] ?? $user_body['username'],
			'avatar_url'   => $user_body['avatar_url'] ?? '',
			'html_url'     => $user_body['web_url'] ?? '',
			'provider'     => 'gitlab',
			'instance_url' => $base,
			'project_id'   => $project_id,
			'project_name' => $proj_body['name_with_namespace'] ?? $proj_body['name'] ?? '',
			'project_url'  => $proj_body['web_url'] ?? '',
		];

		update_option( self::TOKEN_OPTION, $access_token, false );
		update_option( self::USER_OPTION, $user_data, false );

		wp_send_json_success( $user_data );
	}

	/** AJAX: GitLab branches (direct API). */
	public function ajax_gitlab_branches() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );

		$token = self::get_token();
		$user  = get_option( self::USER_OPTION );
		if ( ! $token || empty( $user['project_id'] ) ) wp_send_json_error( 'Not connected.' );

		$base = self::require_https_base( $user['instance_url'] ?? '', 'https://gitlab.com' );
		$pid  = urlencode( $user['project_id'] );

		$branches = [];
		$default  = '';
		$page     = 1;

		while ( $page <= 5 ) {
			$r = wp_remote_get( "{$base}/api/v4/projects/{$pid}/repository/branches?per_page=100&page={$page}", [
				'headers' => [ 'PRIVATE-TOKEN' => $token ],
				'timeout' => 15,
			] );
			$data = json_decode( wp_remote_retrieve_body( $r ), true );
			if ( ! is_array( $data ) || count( $data ) === 0 ) break;
			foreach ( $data as $b ) {
				$branches[] = $b['name'];
				if ( ! empty( $b['default'] ) ) $default = $b['name'];
			}
			if ( count( $data ) < 100 ) break;
			$page++;
		}

		wp_send_json_success( [ 'branches' => $branches, 'default_branch' => $default ] );
	}

	/** AJAX: GitLab directory listing (direct API). */
	public function ajax_gitlab_contents() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );

		$token  = self::get_token();
		$user   = get_option( self::USER_OPTION );
		$branch = sanitize_text_field( wp_unslash( $_GET['branch'] ?? '' ) );
		$path   = sanitize_text_field( wp_unslash( $_GET['path'] ?? '' ) );

		if ( ! $token || empty( $user['project_id'] ) ) wp_send_json_error( 'Not connected.' );

		$base = self::require_https_base( $user['instance_url'] ?? '', 'https://gitlab.com' );
		$pid  = urlencode( $user['project_id'] );

		$url = "{$base}/api/v4/projects/{$pid}/repository/tree?per_page=100";
		if ( $branch ) $url .= '&ref=' . urlencode( $branch );
		if ( $path )   $url .= '&path=' . urlencode( $path );

		$r    = wp_remote_get( $url, [ 'headers' => [ 'PRIVATE-TOKEN' => $token ], 'timeout' => 15 ] );
		$data = json_decode( wp_remote_retrieve_body( $r ), true );
		$dirs = [];
		if ( is_array( $data ) ) {
			foreach ( $data as $item ) {
				if ( ( $item['type'] ?? '' ) === 'tree' ) $dirs[] = $item['name'];
			}
		}

		wp_send_json_success( $dirs );
	}

	// ═════════════════════════════════════════════════════════════════
	// ═════════════════════════════════════════════════════════════════
	// SAVE REPO SETTINGS (direct to DB, bypasses quickbuilder form)
	// ═════════════════════════════════════════════════════════════════

	/**
	 * AJAX: Save a single repo-related setting directly to betterdocs_settings.
	 *
	 * Accepts: key (setting name), value (setting value).
	 * Only allows whitelisted git-related keys.
	 */
	public function ajax_save_repo_setting() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_docs_settings' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$key   = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) );
		$value = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );

		$allowed_keys = [
			'git_provider',
			'git_repository_url',
			'git_branch',
			'git_docs_directory',
		];

		if ( ! in_array( $key, $allowed_keys, true ) ) {
			wp_send_json_error( 'Invalid setting key.' );
		}

		$settings = get_option( 'betterdocs_settings', [] );
		$settings[ $key ] = $value;
		update_option( 'betterdocs_settings', $settings );

		wp_send_json_success( [ 'key' => $key, 'value' => $value ] );
	}

	// ═════════════════════════════════════════════════════════════════
	// SHARED — provider-aware routing
	// ═════════════════════════════════════════════════════════════════

	/** Branches — routes to GitHub (proxy) or GitLab (direct). */
	public function ajax_branches() {
		$provider = self::get_provider();
		if ( $provider === 'gitlab' ) {
			$this->ajax_gitlab_branches();
			return;
		}
		// GitHub via proxy
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );
		$repo = sanitize_text_field( wp_unslash( $_GET['repo'] ?? $_POST['repo'] ?? '' ) );
		if ( ! $repo ) wp_send_json_error( 'Missing repo.' );
		$data = $this->proxy_get( '/api/branches', [ 'repo' => $repo ] );
		$data && ! empty( $data['success'] )
			? wp_send_json_success( [ 'branches' => $data['branches'], 'default_branch' => $data['default_branch'] ?? '' ] )
			: wp_send_json_error( 'Could not fetch branches.' );
	}

	/** Contents — routes to GitHub (proxy) or GitLab (direct). */
	public function ajax_contents() {
		$provider = self::get_provider();
		if ( $provider === 'gitlab' ) {
			$this->ajax_gitlab_contents();
			return;
		}
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );
		$repo   = sanitize_text_field( wp_unslash( $_GET['repo'] ?? '' ) );
		$branch = sanitize_text_field( wp_unslash( $_GET['branch'] ?? '' ) );
		$path   = sanitize_text_field( wp_unslash( $_GET['path'] ?? '' ) );
		if ( ! $repo ) wp_send_json_error( 'Missing repo.' );
		$data = $this->proxy_get( '/api/contents', compact( 'repo', 'branch', 'path' ) );
		$data && ! empty( $data['success'] )
			? wp_send_json_success( $data['directories'] )
			: wp_send_json_error( 'Could not fetch directories.' );
	}

	// ═════════════════════════════════════════════════════════════════
	// DISCONNECT / USER / TOKEN
	// ═════════════════════════════════════════════════════════════════

	/**
	 * Hand the access token back to GitHub before forgetting it.
	 *
	 * Disconnect used to delete the local options and nothing else, which left a
	 * live user token — and the App installation behind it — holding read/write
	 * on the owner's repositories for as long as the token lived. Revoking is
	 * what makes "Disconnect" mean disconnected.
	 *
	 * Best-effort on purpose: the local credentials are cleared either way. A
	 * proxy that is down or a token GitHub has already dropped must not leave
	 * the user stuck connected to something they asked to leave.
	 *
	 * This does not uninstall the App. Removing an installation is
	 * `DELETE /app/installations/{id}`, which needs a JWT signed with the App's
	 * private key; the auth proxy holds only the client id and secret. The UI
	 * links the user to their GitHub installation settings to finish that off.
	 *
	 * @return bool Whether GitHub confirmed the revocation.
	 */
	protected function revoke_remote_token() {
		$token = self::get_token();
		if ( '' === $token ) {
			return false;
		}

		$response = wp_remote_post( $this->get_proxy_url() . '/revoke', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [ 'provider' => 'github', 'access_token' => $token ] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return ! empty( $body['success'] );
	}

	public function ajax_oauth_disconnect() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );

		// Before the token is deleted — afterwards there is nothing to revoke.
		$is_github = 'github' === $this->stored_provider();
		$revoked   = $is_github ? $this->revoke_remote_token() : false;

		// Disconnecting is the last moment we can still see that a GitHub
		// connection existed, and it is the only moment that matters: the next
		// connect attempt has to take the web flow or it will dead-end on the
		// App's configure page. Sites that connected before INSTALLED_OPTION
		// shipped have no marker to read, and without this they would hit the
		// exact bug this option was added to fix.
		if ( $is_github ) {
			update_option( self::INSTALLED_OPTION, 1, false );
		}

		delete_option( self::TOKEN_OPTION );
		delete_option( self::USER_OPTION );
		delete_option( self::REFRESH_OPTION );
		delete_option( self::EXPIRES_OPTION );
		delete_option( self::REFRESH_EXPIRES_OPTION );
		delete_transient( self::REFRESH_LOCK_TRANSIENT );

		// Clear cross-contaminated settings so switching providers starts fresh
		$settings = get_option( 'betterdocs_settings', [] );
		$keys = [ 'git_provider', 'git_repository_url', 'git_branch', 'git_docs_directory' ];
		$modified = false;
		foreach ( $keys as $k ) {
			if ( array_key_exists( $k, $settings ) ) {
				unset( $settings[ $k ] );
				$modified = true;
			}
		}
		if ( $modified ) {
			update_option( 'betterdocs_settings', $settings );
		}

		wp_send_json_success( [
			'message' => __( 'Disconnected.', 'betterdocs-pro' ),
			// Whether GitHub confirmed the token is dead, and where the user can
			// remove the App's repository access entirely. Both are reported so
			// the UI can tell the truth about what disconnecting did rather than
			// implying the App is gone from their account.
			'revoked'           => (bool) $revoked,
			'installations_url' => 'https://github.com/settings/installations',
		] );
	}

	/**
	 * The provider the stored credentials belong to, so a GitLab disconnect does
	 * not try to revoke a token at GitHub.
	 *
	 * @return string
	 */
	protected function stored_provider() {
		$settings = get_option( 'betterdocs_settings', [] );
		$provider = is_array( $settings ) && ! empty( $settings['git_provider'] )
			? (string) $settings['git_provider']
			: '';

		// No saved provider but a GitHub user on file still means a GitHub
		// connection — the repo settings are cleared on every disconnect, so a
		// half-configured site would otherwise skip revocation.
		if ( '' === $provider ) {
			$provider = get_option( self::USER_OPTION ) ? 'github' : '';
		}

		return $provider;
	}

	public function ajax_get_user() {
		check_ajax_referer( 'betterdocs_github_oauth_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions' );
		$user = get_option( self::USER_OPTION, null );
		$user ? wp_send_json_success( $user ) : wp_send_json_error( [ 'message' => 'not_connected' ] );
	}

	public static function get_token() {
		return (string) get_option( self::TOKEN_OPTION, '' );
	}

	/**
	 * Persist an access token — and, when present, its refresh token + expiries —
	 * from a proxy /token or /token/refresh response. Absolute timestamps are
	 * stored so get_valid_token() can decide when to renew.
	 *
	 * A response without expires_in (e.g. a GitHub App with token expiration
	 * turned off) stores a non-expiring access token, and get_valid_token() then
	 * never attempts a refresh.
	 *
	 * @param array $body Decoded proxy response.
	 */
	protected static function store_tokens( $body ) {
		if ( ! empty( $body['access_token'] ) ) {
			update_option( self::TOKEN_OPTION, (string) $body['access_token'], false );
		}
		if ( ! empty( $body['refresh_token'] ) ) {
			update_option( self::REFRESH_OPTION, (string) $body['refresh_token'], false );
		}
		update_option(
			self::EXPIRES_OPTION,
			! empty( $body['expires_in'] ) ? time() + (int) $body['expires_in'] : 0,
			false
		);
		if ( ! empty( $body['refresh_token_expires_in'] ) ) {
			update_option( self::REFRESH_EXPIRES_OPTION, time() + (int) $body['refresh_token_expires_in'], false );
		}
	}

	/**
	 * Return a currently-valid GitHub access token, transparently refreshing it
	 * through the proxy when it has expired (or is within EXPIRY_MARGIN of it).
	 *
	 * Use this everywhere a token is actually sent to GitHub/the proxy. On any
	 * refresh failure it returns the stored (stale) token so the caller's own
	 * 401 handling still surfaces a "reconnect" message.
	 *
	 * @return string
	 */
	public static function get_valid_token() {
		$token = self::get_token();
		if ( '' === $token ) {
			return '';
		}

		// No expiry recorded → a non-expiring GitHub token or a GitLab PAT; use as-is.
		$expires_at = (int) get_option( self::EXPIRES_OPTION, 0 );
		if ( $expires_at <= 0 || time() < ( $expires_at - self::EXPIRY_MARGIN ) ) {
			return $token;
		}

		$refreshed = self::refresh_access_token();
		return is_wp_error( $refreshed ) ? $token : $refreshed;
	}

	/**
	 * Exchange the stored refresh token for a fresh access token via the proxy.
	 *
	 * The GitHub App client secret lives on the proxy, so the plugin can't call
	 * GitHub's refresh endpoint directly — it POSTs the refresh token to the
	 * proxy's /token/refresh. GitHub rotates the refresh token on use, so a short
	 * transient lock stops two concurrent requests from spending it twice.
	 *
	 * @return string|\WP_Error New access token, or WP_Error.
	 */
	public static function refresh_access_token() {
		$refresh = (string) get_option( self::REFRESH_OPTION, '' );
		if ( '' === $refresh ) {
			return new \WP_Error( 'git_no_refresh_token', __( 'Your GitHub connection can’t be refreshed automatically — please reconnect your account in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}

		$refresh_expires_at = (int) get_option( self::REFRESH_EXPIRES_OPTION, 0 );
		if ( $refresh_expires_at > 0 && time() >= $refresh_expires_at ) {
			return new \WP_Error( 'git_refresh_expired', __( 'Your GitHub session has fully expired. Please reconnect your account in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}

		// Another request is already refreshing — briefly wait for it to store the
		// new token (the refresh token is single-use, so we must not spend it a
		// second time), then reuse whatever it stored. Poll in short slices instead
		// of one blind 0.6s sleep: return the fresh token as soon as it lands and
		// never block a worker for more than ~1s.
		if ( get_transient( self::REFRESH_LOCK_TRANSIENT ) ) {
			for ( $i = 0; $i < 10 && get_transient( self::REFRESH_LOCK_TRANSIENT ); $i++ ) {
				usleep( 100000 ); // 0.1s
			}
			$token = self::get_token();
			return '' !== $token ? $token : new \WP_Error( 'git_refresh_busy', __( 'Refreshing the GitHub connection — please try again.', 'betterdocs-pro' ) );
		}
		// The lock must outlive the refresh HTTP call (timeout 30s below): a slow
		// proxy must not let it expire mid-flight, or a concurrent request would
		// spend the same single-use refresh token twice and GitHub would bounce the
		// connection. Keep TTL ≥ timeout + margin.
		set_transient( self::REFRESH_LOCK_TRANSIENT, 1, 45 );

		$proxy    = rtrim( apply_filters( 'betterdocs_github_auth_proxy_url', self::DEFAULT_PROXY_URL ), '/' );
		$response = wp_remote_post( $proxy . '/token/refresh', array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'refresh_token' => $refresh, 'provider' => 'github' ) ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			delete_transient( self::REFRESH_LOCK_TRANSIENT );
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['success'] ) || empty( $body['access_token'] ) ) {
			delete_transient( self::REFRESH_LOCK_TRANSIENT );
			$msg = is_array( $body ) && ! empty( $body['error'] )
				? (string) $body['error']
				: __( 'Could not refresh the GitHub token. Please reconnect your account in BetterDocs Settings → Git Integration.', 'betterdocs-pro' );
			return new \WP_Error( 'git_refresh_failed', $msg );
		}

		// A refresh MUST return a rotated refresh_token (the one we just spent is now
		// single-use-invalid) AND a fresh expires_in (so future refreshes keep
		// firing). A response missing either is a proxy-contract violation — hard-fail
		// to the reconnect prompt instead of silently storing a stale refresh token
		// or a non-expiring access token, which would break the next refresh or
		// re-introduce the overnight-expiry bug after a single cycle.
		if ( empty( $body['refresh_token'] ) || empty( $body['expires_in'] ) ) {
			delete_transient( self::REFRESH_LOCK_TRANSIENT );
			return new \WP_Error( 'git_refresh_incomplete', __( 'GitHub returned an incomplete token refresh. Please reconnect your account in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}

		self::store_tokens( $body );
		delete_transient( self::REFRESH_LOCK_TRANSIENT );

		return (string) $body['access_token'];
	}

	/**
	 * List the connected GitHub account's repositories via the auth proxy.
	 *
	 * Mirrors ajax_github_repos() but is callable from other classes (the
	 * Write-with-AI "browse repository" picker) without an admin-ajax round-trip.
	 * Uses the same Bearer-token proxy call as proxy_get(), kept static so the
	 * caller doesn't need a GitHubOAuth instance.
	 *
	 * @return array|\WP_Error Raw repo objects from the proxy, or WP_Error.
	 */
	public static function list_repos() {
		$token = self::get_valid_token();
		if ( ! $token ) {
			return new \WP_Error( 'git_not_connected', __( 'Git Sync is not connected.', 'betterdocs-pro' ) );
		}

		$proxy = rtrim( apply_filters( 'betterdocs_github_auth_proxy_url', self::DEFAULT_PROXY_URL ), '/' );
		$url   = $proxy . '/api/repos?' . http_build_query( array( 'provider' => 'github' ) );

		$r = wp_remote_get( $url, array(
			'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			'timeout' => 30,
		) );
		if ( is_wp_error( $r ) ) {
			return $r;
		}

		$data = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			$msg = is_array( $data ) && ! empty( $data['error'] )
				? (string) $data['error']
				: __( 'Could not fetch repositories. Your Git connection may have expired — reconnect in BetterDocs Settings → Git Integration.', 'betterdocs-pro' );
			return new \WP_Error( 'git_repos_failed', $msg );
		}

		return isset( $data['repos'] ) && is_array( $data['repos'] ) ? $data['repos'] : array();
	}

	public static function is_connected() {
		return ! empty( self::get_token() );
	}
}
