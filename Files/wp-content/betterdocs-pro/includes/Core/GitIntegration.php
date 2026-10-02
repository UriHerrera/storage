<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Utils\Base;

/**
 * Git Integration Core Class
 *
 * Handles Git repository synchronization for BetterDocs
 *
 * @package WPDeveloper\BetterDocs\Core
 * @since 1.0.0
 */
class GitIntegration extends Base {

	/**
	 * Settings instance
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Constructor
	 *
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->init();
	}

	/**
	 * Initialize Git Integration
	 */
	public function init() {
		// Only initialize if Git integration is enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// AJAX handlers
		add_action( 'wp_ajax_betterdocs_git_sync_document', [ $this, 'ajax_sync_document' ] );
		add_action( 'wp_ajax_betterdocs_git_pull_document', [ $this, 'ajax_pull_document' ] );
		add_action( 'wp_ajax_betterdocs_git_test_connection', [ $this, 'ajax_test_connection' ] );
		add_action( 'wp_ajax_betterdocs_git_push_if_missing', [ $this, 'ajax_push_if_missing' ] );
		add_action( 'wp_ajax_betterdocs_github_get_diff', [ $this, 'ajax_get_diff' ] );
		add_action( 'wp_ajax_betterdocs_git_fetch_status', [ $this, 'ajax_fetch_status' ] );
		add_action( 'wp_ajax_betterdocs_github_list_md_files', [ $this, 'ajax_list_md_files' ] );
		add_action( 'wp_ajax_betterdocs_github_import_md', [ $this, 'ajax_import_md' ] );
		add_action( 'wp_ajax_betterdocs_git_commit_history', [ $this, 'ajax_commit_history' ] );

		// Auto-sync on save if enabled
		if ( $this->settings->get( 'git_auto_sync', false ) ) {
			add_action( 'save_post_docs', [ $this, 'auto_sync_on_save' ], 20, 1 );
		}

		// Background sync action
		add_action( 'betterdocs_git_sync_document_background', [ $this, 'background_sync_document' ] );
	}

	/**
	 * Upper bound on how many repository files one import request may process.
	 */
	const MAX_IMPORT_FILES = 100;

	/**
	 * Check if Git integration is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) $this->settings->get( 'enable_git_integration', false );
	}

	/**
	 * Resolve and authorize the docs post an AJAX request is acting on.
	 *
	 * `edit_docs` is the generic "may use the docs editor" capability — it says
	 * nothing about *which* document the caller may touch. Every Git handler used
	 * to check only that plus post existence, so any user in `article_roles`
	 * (typically a delegated Author) could pass an arbitrary `post_id` and read,
	 * export, or overwrite another author's draft or private doc. All the nonces
	 * needed are localized onto every docs edit screen, so obtaining one is
	 * trivial for such a user.
	 *
	 * Checking `edit_post` instead runs the request through map_meta_cap, which
	 * resolves to edit_docs / edit_others_docs / edit_published_docs / edit_private_docs
	 * as appropriate for that specific post and actor.
	 *
	 * Sends the JSON error and halts when access is denied.
	 *
	 * @param int $post_id Post ID supplied by the request.
	 * @return \WP_Post
	 */
	protected function authorize_document_request( $post_id ) {
		$post_id = (int) $post_id;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || $post->post_type !== 'docs' ) {
			wp_send_json_error( __( 'This document could not be found or is not a valid BetterDocs article.', 'betterdocs-pro' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			// Deliberately identical to the not-found message so the handler does
			// not become an existence oracle for other authors' documents.
			wp_send_json_error( __( 'This document could not be found or is not a valid BetterDocs article.', 'betterdocs-pro' ) );
		}

		return $post;
	}

	/**
	 * Get Git configuration
	 *
	 * @return array
	 */
	public function get_config() {
		// Prefer OAuth token; fall back to legacy personal access token setting for backwards-compat.
		$oauth_token = GitHubOAuth::get_valid_token();
		$access_token = $oauth_token ?: $this->settings->get( 'git_access_token', '' );

		return [
			'provider'         => $this->settings->get( 'git_provider', 'github' ),
			'repository_url'   => $this->settings->get( 'git_repository_url', '' ),
			'branch'           => $this->settings->get( 'git_branch', 'main' ),
			'access_token'     => $access_token,
			// Intentionally read the option directly so an empty string (= Root folder)
			// is preserved. Settings::get() coerces empty strings back to the default.
			'docs_directory'   => $this->get_docs_directory_setting(),
			'file_naming'      => $this->settings->get( 'git_file_naming', 'slug' ),
			'auto_sync'        => $this->settings->get( 'git_auto_sync', false ),
		];
	}

	/**
	 * AJAX handler for syncing a document to Git
	 */
	public function ajax_sync_document() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_git_sync_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$this->authorize_document_request( $post_id );

		$commit_message = isset( $_POST['commit_message'] ) ? sanitize_text_field( wp_unslash( $_POST['commit_message'] ) ) : '';
		$result = $this->sync_document_to_git( $post_id, $commit_message );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for pulling changes from Git
	 */
	public function ajax_pull_document() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_git_pull_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$this->authorize_document_request( $post_id );

		$result = $this->pull_document_from_git( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for testing Git connection
	 */
	public function ajax_test_connection() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_git_test_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'betterdocs-pro' ) );
		}

		$result = $this->test_git_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for pushing document if missing in repository
	 */
	public function ajax_push_if_missing() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_git_push_missing_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$post    = $this->authorize_document_request( $post_id );

		// First check if file exists in repository
		$config = $this->get_config();
		$file_path = $this->resolve_file_path( $post_id, $post );

		$git_content = $this->fetch_from_repository( $file_path, $config );

		if ( ! is_wp_error( $git_content ) ) {
			// File exists, so we can't push (would overwrite)
			wp_send_json_error( __( 'This file already exists in the repository. Use the Push button to update it, or Pull to fetch the latest version.', 'betterdocs-pro' ) );
		}

		// File doesn't exist, so push it
		$result = $this->sync_document_to_git( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Auto-sync document on save
	 *
	 * @param int $post_id
	 */
	public function auto_sync_on_save( $post_id ) {
		// Only sync explicitly requested auto-sync if we are not autosaving
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Ensure we only auto-sync officially published live docs
		if ( get_post_status( $post_id ) !== 'publish' ) {
			return;
		}

		// Sync in background to avoid blocking the save process
		wp_schedule_single_event( time(), 'betterdocs_git_sync_document_background', [ $post_id ] );
	}

	/**
	 * Background sync document
	 *
	 * @param int $post_id
	 */
	public function background_sync_document( $post_id ) {
		$this->sync_document_to_git( $post_id );
	}

	/**
	 * Sync a document to Git repository
	 *
	 * @param int $post_id
	 * @return array|WP_Error
	 */
	public function sync_document_to_git( $post_id, $commit_message = '' ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'docs' ) {
			return new \WP_Error( 'invalid_post', __( 'This document could not be found or is not a valid BetterDocs article. Please save the document first and try again.', 'betterdocs-pro' ) );
		}

		if ( $post->post_status === 'auto-draft' && empty( trim( $post->post_content ) ) ) {
			return new \WP_Error( 'empty_content', __( 'Cannot push an empty document. Please add some content first.', 'betterdocs-pro' ) );
		}

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			$settings_url = admin_url( 'admin.php?page=betterdocs-settings#git-sync' );
			return new \WP_Error(
				'missing_config',
				__( 'Git integration is not fully configured. Please go to BetterDocs Settings → Git Integration, connect your account, and select a repository before syncing.', 'betterdocs-pro' ),
				[ 'settings_url' => $settings_url ]
			);
		}

		// Convert post content to Markdown
		$markdown_content = $this->convert_to_markdown( $post );

		// Get file path
		$file_path = $this->get_file_path( $post );

		// Update sync status
		update_post_meta( $post_id, '_betterdocs_git_sync_status', 'syncing' );

		try {
			// Commit to Git repository
			$commit_result = $this->commit_to_repository( $file_path, $markdown_content, $post, $commit_message );

			if ( is_wp_error( $commit_result ) ) {
				// No-changes is not an error — return success-like response
				if ( $commit_result->get_error_code() === 'no_changes' ) {
					update_post_meta( $post_id, '_betterdocs_git_sync_status', 'synced' );
					return [
						'no_changes'           => true,
						'message'              => $commit_result->get_error_message(),
						'last_sync'            => current_time( 'mysql' ),
						'last_sync_formatted'  => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
						'file_path'            => $file_path,
					];
				}
				update_post_meta( $post_id, '_betterdocs_git_sync_status', 'error' );
				return $commit_result;
			}

			// Update meta data
			update_post_meta( $post_id, '_betterdocs_git_sync_status', 'synced' );
			update_post_meta( $post_id, '_betterdocs_git_last_sync', current_time( 'mysql' ) );
			update_post_meta( $post_id, '_betterdocs_git_file_path', $file_path );

			if ( isset( $commit_result['commit_hash'] ) ) {
				update_post_meta( $post_id, '_betterdocs_git_commit_hash', $commit_result['commit_hash'] );
			}

			return [
				'message'              => __( 'Document synced successfully', 'betterdocs-pro' ),
				'last_sync'            => current_time( 'mysql' ),
				'last_sync_formatted'  => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'commit_hash'          => $commit_result['commit_hash'] ?? '',
				'file_path'            => $file_path
			];

		} catch ( \Exception $e ) {
			update_post_meta( $post_id, '_betterdocs_git_sync_status', 'error' );
			return new \WP_Error( 'sync_failed', $e->getMessage() );
		}
	}

	/**
	 * Pull document changes from Git repository
	 *
	 * @param int $post_id
	 * @return array|WP_Error
	 */
	public function pull_document_from_git( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'docs' ) {
			return new \WP_Error( 'invalid_post', __( 'This document could not be found or is not a valid BetterDocs article. Please save the document first and try again.', 'betterdocs-pro' ) );
		}

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			$settings_url = admin_url( 'admin.php?page=betterdocs-settings#git-sync' );
			return new \WP_Error(
				'missing_config',
				__( 'Git integration is not fully configured. Please go to BetterDocs Settings → Git Integration, connect your account, and select a repository before syncing.', 'betterdocs-pro' ),
				[ 'settings_url' => $settings_url ]
			);
		}

		// Get file path
		$file_path = $this->resolve_file_path( $post_id, $post );

		try {
			// Fetch content from Git repository
			$git_content = $this->fetch_from_repository( $file_path, $config );

			if ( is_wp_error( $git_content ) ) {
				// If file doesn't exist in repository, offer to push current content
				if ( $git_content->get_error_code() === 'github_api_error' ) {
					$error_data = $git_content->get_error_data();
					if ( isset( $error_data['status_code'] ) && $error_data['status_code'] === 404 ) {
						return new \WP_Error(
							'file_not_found_in_repo',
							sprintf(
								/* translators: %s: file path within the repository. */
								__( 'The file "%s" does not exist in the repository — it may not have been pushed yet, or it was deleted. Would you like to push this document to Git instead?', 'betterdocs-pro' ),
								$file_path
							),
							[ 'suggest_push' => true, 'file_path' => $file_path ]
						);
					}
				}
				return $git_content;
			}

			// Convert Markdown to HTML
			$html_content = $this->convert_from_markdown( $git_content['content'] );

			// Check if content is already identical — skip update if no changes
			if ( trim( $post->post_content ) === trim( $html_content ) ) {
				return [
					'no_changes'         => true,
					'message'            => __( 'Already up to date — no changes to pull.', 'betterdocs-pro' ),
					'last_sync'          => current_time( 'mysql' ),
					'last_sync_formatted' => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
					'commit_hash'        => $git_content['sha'] ?? '',
					'file_path'          => $file_path,
				];
			}

			// Update post content. Temporarily remove KSES filters so Gutenberg
			// block comments (e.g. `<!-- wp:paragraph -->`) survive wp_update_post
			// for users without the `unfiltered_html` capability — stripped block
			// markers are the root cause of the editor's "Attempt Recovery" state.
			$updated_post = [
				'ID' => $post_id,
				'post_content' => $html_content,
				'post_modified' => current_time( 'mysql' ),
				'post_modified_gmt' => current_time( 'mysql', 1 )
			];

			// Only lift KSES for actors who could have saved this markup by hand.
			// Repository content is not necessarily trusted — anyone able to commit
			// to the configured repo can influence it — so stripping KSES for a
			// user without `unfiltered_html` would let that content persist markup
			// the user is not allowed to store. Those users keep KSES, which costs
			// them block comments rather than site integrity.
			$kses_was_active = has_filter( 'content_save_pre', 'wp_filter_post_kses' )
				&& current_user_can( 'unfiltered_html' );
			if ( $kses_was_active ) {
				kses_remove_filters();
			}
			$result = wp_update_post( $updated_post, true );
			if ( $kses_was_active ) {
				kses_init_filters();
			}

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Update meta data
			update_post_meta( $post_id, '_betterdocs_git_last_sync', current_time( 'mysql' ) );
			update_post_meta( $post_id, '_betterdocs_git_sync_status', 'synced' );

			if ( isset( $git_content['sha'] ) ) {
				update_post_meta( $post_id, '_betterdocs_git_commit_hash', $git_content['sha'] );
			}

			return [
				'message' => __( 'Document updated successfully from Git repository', 'betterdocs-pro' ),
				'last_sync' => current_time( 'mysql' ),
				'last_sync_formatted' => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'commit_hash' => $git_content['sha'] ?? '',
				'file_path' => $file_path
			];

		} catch ( \Exception $e ) {
			update_post_meta( $post_id, '_betterdocs_git_sync_status', 'error' );
			return new \WP_Error( 'pull_failed', $e->getMessage() );
		}
	}

	/**
	 * Test Git connection
	 *
	 * @return array|WP_Error
	 */
	public function test_git_connection() {
		$config = $this->get_config();

		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			return new \WP_Error( 'missing_config', __( 'Repository URL and access token are required. Please configure these in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}

		// Test API connection based on provider
		switch ( $config['provider'] ) {
			case 'github':
				return $this->test_github_connection( $config );
			case 'gitlab':
				return $this->test_gitlab_connection( $config );
			case 'bitbucket':
				return $this->test_bitbucket_connection( $config );
			default:
				return new \WP_Error( 'unsupported_provider', __( 'The selected Git provider is not supported. BetterDocs currently supports GitHub and GitLab.', 'betterdocs-pro' ) );
		}
	}

	/**
	 * Convert WordPress post content to Markdown
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	protected function convert_to_markdown( $post ) {
		// Push raw post_content as-is — preserves Gutenberg block comments,
		// HTML tags, and all formatting exactly as stored in WordPress.
		$content = $post->post_content;

		$final  = "<h1>" . esc_html( $post->post_title ) . "</h1>\n\n";
		$final .= trim( $content ) . "\n";

		return $final;
	}

	/**
	 * Read git_docs_directory raw from the options row so an empty string
	 * (= Root folder) survives. Settings::get() replaces empty strings with
	 * the default, which would force pushes into /docs/ even when the user
	 * explicitly selected the repository root.
	 *
	 * @return string
	 */
	protected function get_docs_directory_setting() {
		$options = get_option( 'betterdocs_settings', [] );
		return array_key_exists( 'git_docs_directory', $options )
			? (string) $options['git_docs_directory']
			: 'docs';
	}

	/**
	 * Get file path for a post in the Git repository
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	protected function get_file_path( $post ) {
		$config   = $this->get_config();
		$dir      = trim( (string) $config['docs_directory'], '/' );
		$docs_dir = $dir === '' ? '' : trailingslashit( $dir );

		switch ( $config['file_naming'] ) {
			case 'id':
				$filename = $post->ID . '.md';
				break;
			case 'title':
				$title = sanitize_file_name( $post->post_title );
				if ( empty( $title ) ) {
					$title = 'doc-' . $post->ID;
				}
				$filename = $title . '.md';
				break;
			case 'slug':
			default:
				$slug = $post->post_name;
				if ( empty( $slug ) ) {
					$slug = sanitize_title( $post->post_title );
				}
				if ( empty( $slug ) ) {
					$slug = 'doc-' . $post->ID;
				}
				$filename = $slug . '.md';
				break;
		}

		return $docs_dir . $filename;
	}

	/**
	 * Resolve a post's git file path, accounting for docs_directory changes.
	 *
	 * If the stored _betterdocs_git_file_path meta is in a different directory
	 * than the current setting (e.g. user changed docs_directory in admin),
	 * rebuild the path with the current directory + the same filename so diff,
	 * fetch, and pull all target the new location.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 * @return string
	 */
	protected function resolve_file_path( $post_id, $post ) {
		$stored = get_post_meta( $post_id, '_betterdocs_git_file_path', true );
		if ( empty( $stored ) ) {
			return $this->get_file_path( $post );
		}

		$config      = $this->get_config();
		$current_dir = trim( (string) $config['docs_directory'], '/' );
		$stored_dir  = trim( (string) dirname( $stored ), '/' );
		if ( $stored_dir === '.' ) {
			$stored_dir = '';
		}

		if ( $stored_dir === $current_dir ) {
			return $stored;
		}

		// Directory changed — rebuild with new dir + same filename.
		$filename = basename( $stored );
		return $current_dir === '' ? $filename : $current_dir . '/' . $filename;
	}

	/**
	 * Commit content to Git repository
	 *
	 * @param string $file_path
	 * @param string $content
	 * @param WP_Post $post
	 * @return array|WP_Error
	 */
	protected function commit_to_repository( $file_path, $content, $post, $commit_message = '' ) {
		$config = $this->get_config();

		switch ( $config['provider'] ) {
			case 'github':
				return $this->commit_to_github( $file_path, $content, $post, $config, $commit_message );
			case 'gitlab':
				return $this->commit_to_gitlab( $file_path, $content, $post, $config, $commit_message );
			case 'bitbucket':
				return $this->commit_to_bitbucket( $file_path, $content, $post, $config );
			default:
				return new \WP_Error( 'unsupported_provider', __( 'The selected Git provider is not supported. BetterDocs currently supports GitHub and GitLab.', 'betterdocs-pro' ) );
		}
	}

	/**
	 * Test GitHub connection
	 *
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function test_github_connection( $config ) {
		// Parse repository URL to get owner and repo
		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return $repo_info;
		}

		// Test repository access
		$api_url = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}";
		$response = $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check if we have push access
		$permissions = $response['permissions'] ?? [];
		$can_push = $permissions['push'] ?? false;

		if ( ! $can_push ) {
			return new \WP_Error( 'no_push_access', __( 'Your access token does not have push/write permissions for this repository. Please ensure the token has the correct scopes (GitHub: "repo", GitLab: "api") and that you have Maintainer or higher access.', 'betterdocs-pro' ) );
		}

		return [
			'message' => sprintf(
				/* translators: %s: repository full name (owner/repo). */
				__( 'Successfully connected to GitHub repository: %s', 'betterdocs-pro' ),
				$response['full_name']
			),
			'repository' => $response['full_name'],
			'permissions' => $permissions
		];
	}

	/**
	 * Test GitLab connection
	 *
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function test_gitlab_connection( $config ) {
		return new \WP_Error( 'not_implemented', __( 'GitLab connection test is not yet available.', 'betterdocs-pro' ) );
	}

	/**
	 * Test Bitbucket connection
	 *
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function test_bitbucket_connection( $config ) {
		return new \WP_Error( 'not_implemented', __( 'Bitbucket connection test is not yet available.', 'betterdocs-pro' ) );
	}



	/**
	 * Commit content to GitHub repository
	 *
	 * @param string $file_path
	 * @param string $content
	 * @param WP_Post $post
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function commit_to_github( $file_path, $content, $post, $config, $commit_message = '' ) {
		// Parse repository URL to get owner and repo
		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return $repo_info;
		}

		$api_url = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}/contents/{$file_path}";

		// First, try to get the current file to get its SHA (required for updates)
		$current_file = $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );
		$sha = null;

		if ( ! is_wp_error( $current_file ) && isset( $current_file['sha'] ) ) {
			$sha = $current_file['sha'];

			// Detect no-changes: compare remote content with local content
			if ( isset( $current_file['content'] ) ) {
				$remote_content = base64_decode( str_replace( "\n", '', $current_file['content'] ) );
				if ( $remote_content === $content ) {
					return new \WP_Error( 'no_changes', __( 'No changes to push — content is already up to date.', 'betterdocs-pro' ) );
				}
			}
		}

		// Prepare commit data
		/* translators: %s: document title. */
		$default_message = sprintf( __( 'Updated %s via BetterDocs', 'betterdocs-pro' ), $post->post_title );
		$commit_data = [
			'message' => ! empty( $commit_message ) ? $commit_message : $default_message,
			'content' => base64_encode( $content ),
			'branch' => $config['branch'],
		];

		// Add SHA if file exists (for updates)
		if ( $sha ) {
			$commit_data['sha'] = $sha;
		}

		// Make the commit
		$response = $this->github_api_request( $api_url, 'PUT', $commit_data, $config['access_token'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return [
			'commit_hash' => $response['commit']['sha'] ?? '',
			/* translators: %s: document title. */
			'message' => sprintf( __( 'Successfully committed %s to GitHub', 'betterdocs-pro' ), $post->post_title ),
			'url' => $response['content']['html_url'] ?? ''
		];
	}

	/**
	 * Validate and confine a client-supplied repository file path before import.
	 *
	 * ajax_import_md() receives a list of paths from the request and used to hand
	 * each one straight to fetch_from_repository(). sanitize_text_field() leaves
	 * "../" intact, so a delegated author could supply a path that escapes the
	 * configured docs directory ("../.github/workflows/deploy.yml", "../../.env")
	 * and pull any file the configured repository token can read into a doc — an
	 * arbitrary-repository-file read via path traversal.
	 *
	 * Enforce that the path is a plain, relative, traversal-free Markdown path,
	 * and — when a docs directory is configured — that it lives inside that
	 * directory, i.e. the exact scope the picker (list_md_files) exposes.
	 *
	 * @param string $file_path Raw path from the request.
	 * @param array  $config    Git config from get_config().
	 * @return string|false Clean repo-relative path, or false when it must be rejected.
	 */
	protected function sanitize_repo_file_path( $file_path, $config ) {
		$file_path = (string) $file_path;

		// No null bytes and no backslashes (some providers treat "\" as a separator).
		if ( $file_path === '' || strpos( $file_path, "\0" ) !== false || strpos( $file_path, '\\' ) !== false ) {
			return false;
		}

		// Repo-relative paths only: reject a scheme or protocol-relative prefix.
		if ( preg_match( '#^([a-z][a-z0-9+.\-]*:)?//#i', $file_path ) ) {
			return false;
		}

		$file_path = ltrim( $file_path, '/' );

		// Reject any traversal or empty component outright instead of resolving it.
		foreach ( explode( '/', $file_path ) as $segment ) {
			if ( $segment === '' || $segment === '.' || $segment === '..' ) {
				return false;
			}
		}

		// Imports are Markdown documents.
		if ( strtolower( substr( $file_path, -3 ) ) !== '.md' ) {
			return false;
		}

		// Confine to the configured docs directory — the same scope list_md_files
		// offers in the picker. An empty directory means the repository root is the
		// docs area by explicit admin choice, so only the checks above apply.
		$docs_dir = trim( (string) $config['docs_directory'], '/' );
		if ( $docs_dir !== '' && strpos( $file_path, $docs_dir . '/' ) !== 0 ) {
			return false;
		}

		return $file_path;
	}

	/**
	 * Fetch content from GitHub repository
	 *
	 * @param string $file_path
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function fetch_from_repository( $file_path, $config ) {
		switch ( $config['provider'] ) {
			case 'github':
				return $this->fetch_from_github( $file_path, $config );
			case 'gitlab':
				return $this->fetch_from_gitlab( $file_path, $config );
			case 'bitbucket':
				return $this->fetch_from_bitbucket( $file_path, $config );
			default:
				return new \WP_Error( 'unsupported_provider', __( 'The selected Git provider is not supported. BetterDocs currently supports GitHub and GitLab.', 'betterdocs-pro' ) );
		}
	}

	/**
	 * Fetch content from GitHub repository
	 *
	 * @param string $file_path
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function fetch_from_github( $file_path, $config ) {
		// Parse repository URL to get owner and repo
		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return $repo_info;
		}

		// Encode each path segment (preserving the "/" separators) so a path can
		// never inject query/fragment characters into the API URL.
		$encoded_path = implode( '/', array_map( 'rawurlencode', explode( '/', $file_path ) ) );
		$api_url      = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}/contents/{$encoded_path}";

		// Add branch parameter
		$api_url .= '?ref=' . urlencode( $config['branch'] );

		$response = $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['content'] ) ) {
			return new \WP_Error( 'no_content', __( 'The file exists in the repository but appears to be empty. Please check the file on GitHub/GitLab directly.', 'betterdocs-pro' ) );
		}

		// Decode base64 content
		$content = base64_decode( $response['content'] );

		return [
			'content' => $content,
			'sha' => $response['sha'],
			'url' => $response['html_url'] ?? ''
		];
	}

	/**
	 * Make GitHub API request
	 *
	 * @param string $url
	 * @param string $method
	 * @param array|null $data
	 * @param string $token
	 * @return array|WP_Error
	 */
	protected function github_api_request( $url, $method = 'GET', $data = null, $token = '' ) {
		$args = [
			'method' => $method,
			'headers' => [
				'Authorization' => 'token ' . $token,
				'Accept' => 'application/vnd.github.v3+json',
				'User-Agent' => 'BetterDocs-Git-Integration/1.0'
			],
			'timeout' => 30
		];

		if ( $data && in_array( $method, [ 'POST', 'PUT', 'PATCH' ] ) ) {
			$args['body'] = wp_json_encode( $data );
			$args['headers']['Content-Type'] = 'application/json';
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$decoded_body = json_decode( $body, true );

		if ( $status_code >= 400 ) {
			$raw_message = isset( $decoded_body['message'] ) ? $decoded_body['message'] : '';
			$error_message = $this->humanize_api_error( $status_code, $raw_message, 'github' );
			return new \WP_Error( 'github_api_error', $error_message, [ 'status_code' => $status_code ] );
		}

		return $decoded_body;
	}

	/**
	 * Parse GitHub repository URL
	 *
	 * @param string $url
	 * @return array|WP_Error
	 */
	protected function parse_github_url( $url ) {
		// Remove .git suffix if present
		$url = preg_replace( '/\.git$/', '', $url );

		// Match full GitHub URL patterns (https://github.com/owner/repo)
		if ( preg_match( '#github\.com[:/]([^/]+)/([^/]+)#', $url, $matches ) ) {
			return [
				'owner' => $matches[1],
				'repo'  => $matches[2],
			];
		}

		// Match short owner/repo format (from repo settings dropdown)
		if ( preg_match( '#^([^/]+)/([^/]+)$#', trim( $url ) ) ) {
			$parts = explode( '/', trim( $url ), 2 );
			return [
				'owner' => $parts[0],
				'repo'  => $parts[1],
			];
		}

		return new \WP_Error( 'invalid_github_url', __( 'The repository URL format is not recognized. Please use the format "owner/repo" (e.g. "acme/docs") or a full GitHub URL.', 'betterdocs-pro' ) );
	}

	/**
	 * Convert Markdown content to HTML
	 *
	 * @param string $markdown
	 * @return string
	 */
	protected function convert_from_markdown( $markdown ) {
		$content = $markdown;

		// Extract and remove front matter
		if ( preg_match( '/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $markdown, $matches ) ) {
			$content = $matches[2];
		}

		// Remove legacy wrapper divs if they exist. Matches the whole
		// `<div class="heading-wrapper">…</div>` + optional `<div class="content-wrapper">`
		// opener, and its paired closing `</div>` at the end of the content.
		if ( preg_match( '/<div class="heading-wrapper">/i', $content ) ) {
			$content = preg_replace( '/<div class="heading-wrapper">\s*<h1>.*?<\/h1>\s*<\/div>\s*(<div class="content-wrapper">\s*)?/is', '', $content );
			$content = preg_replace( '/\s*<\/div>\s*$/is', '', $content );
		}

		// Remove leading <h1> title (already handled as post_title)
		$content = preg_replace( '/^\s*<h1>[^<]*<\/h1>\s*/i', '', $content, 1 );

		// Return content as-is from origin without auto-wrapping in Gutenberg
		// block comments. Push sends raw post_content, pull restores it raw.
		return trim( $content );
	}

	/**
	 * Commit content to GitLab repository (placeholder)
	 *
	 * @param string $file_path
	 * @param string $content
	 * @param WP_Post $post
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function commit_to_gitlab( $file_path, $content, $post, $config, $commit_message = '' ) {
		$oauth_user = get_option( GitHubOAuth::USER_OPTION, [] );
		$project_id = $oauth_user['project_id'] ?? $config['repository_url'] ?? '';
		$base_url   = $oauth_user['instance_url'] ?? 'https://gitlab.com';

		if ( empty( $project_id ) ) {
			return new \WP_Error( 'missing_project_id', __( 'GitLab Project ID is not configured. Please reconnect your GitLab account in BetterDocs Settings with a valid Project ID.', 'betterdocs-pro' ) );
		}

		$pid    = urlencode( $project_id );
		$branch = $config['branch'] ?: 'main';
		$token  = $config['access_token'];

		if ( empty( $commit_message ) ) {
			$commit_message = sprintf( 'Update %s via BetterDocs', $post->post_title );
		}

		// Check if file already exists (to decide create vs update action)
		$check_url = "{$base_url}/api/v4/projects/{$pid}/repository/files/" . urlencode( $file_path ) . '?ref=' . urlencode( $branch );
		$check     = wp_remote_get( $check_url, [
			'headers' => [ 'PRIVATE-TOKEN' => $token ],
			'timeout' => 15,
		] );
		$exists = ! is_wp_error( $check ) && wp_remote_retrieve_response_code( $check ) === 200;

		// Detect no-changes: fetch raw file content and compare directly
		if ( $exists ) {
			$raw_url = "{$base_url}/api/v4/projects/{$pid}/repository/files/" . urlencode( $file_path ) . '/raw?ref=' . urlencode( $branch );
			$raw_response = wp_remote_get( $raw_url, [
				'headers' => [ 'PRIVATE-TOKEN' => $token ],
				'timeout' => 15,
			] );

			if ( ! is_wp_error( $raw_response ) && wp_remote_retrieve_response_code( $raw_response ) === 200 ) {
				$remote_content = wp_remote_retrieve_body( $raw_response );
				if ( $remote_content === $content ) {
					return new \WP_Error( 'no_changes', __( 'No changes to push — content is already up to date.', 'betterdocs-pro' ) );
				}
			}
		}

		// GitLab Commits API — single file commit
		$api_url  = "{$base_url}/api/v4/projects/{$pid}/repository/commits";
		$response = wp_remote_post( $api_url, [
			'headers' => [
				'PRIVATE-TOKEN' => $token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'branch'         => $branch,
				'commit_message' => $commit_message,
				'actions'        => [
					[
						'action'    => $exists ? 'update' : 'create',
						'file_path' => $file_path,
						'content'   => $content,
					],
				],
			] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$raw_msg = $body['message'] ?? $body['error'] ?? '';
			if ( is_array( $raw_msg ) ) $raw_msg = implode( ', ', $raw_msg );
			return new \WP_Error( 'gitlab_api_error', $this->humanize_api_error( $code, $raw_msg, 'gitlab' ) );
		}

		return [
			'commit_hash' => $body['id'] ?? '',
			'message'     => __( 'Pushed to GitLab successfully.', 'betterdocs-pro' ),
		];
	}

	/**
	 * Commit content to Bitbucket repository (placeholder)
	 *
	 * @param string $file_path
	 * @param string $content
	 * @param WP_Post $post
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function commit_to_bitbucket( $file_path, $content, $post, $config ) {
		return new \WP_Error( 'not_implemented', __( 'Bitbucket integration not yet implemented', 'betterdocs-pro' ) );
	}

	/**
	 * Fetch content from GitLab repository (placeholder)
	 *
	 * @param string $file_path
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function fetch_from_gitlab( $file_path, $config ) {
		$oauth_user = get_option( GitHubOAuth::USER_OPTION, [] );
		$project_id = $oauth_user['project_id'] ?? $config['repository_url'] ?? '';
		$base_url   = $oauth_user['instance_url'] ?? 'https://gitlab.com';

		if ( empty( $project_id ) ) {
			return new \WP_Error( 'missing_project_id', __( 'GitLab Project ID is not configured. Please reconnect your GitLab account in BetterDocs Settings with a valid Project ID.', 'betterdocs-pro' ) );
		}

		$pid    = urlencode( $project_id );
		$branch = $config['branch'] ?: 'main';
		$token  = $config['access_token'];

		$api_url = "{$base_url}/api/v4/projects/{$pid}/repository/files/" . urlencode( $file_path ) . '?ref=' . urlencode( $branch );

		$response = wp_remote_get( $api_url, [
			'headers' => [ 'PRIVATE-TOKEN' => $token ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 404 ) {
			return new \WP_Error( 'github_api_error', __( 'File not found in the repository. It may have been deleted or the file path has changed.', 'betterdocs-pro' ), [ 'status_code' => 404 ] );
		}

		if ( $code >= 400 ) {
			$raw_msg = $body['message'] ?? $body['error'] ?? '';
			return new \WP_Error( 'gitlab_api_error', $this->humanize_api_error( $code, $raw_msg, 'gitlab' ) );
		}

		// GitLab returns file content as base64
		$content = isset( $body['content'] ) ? base64_decode( $body['content'] ) : '';

		return [
			'content' => $content,
			'sha'     => $body['last_commit_id'] ?? $body['blob_id'] ?? '',
		];
	}

	/**
	 * Fetch content from Bitbucket repository (placeholder)
	 *
	 * @param string $file_path
	 * @param array $config
	 * @return array|WP_Error
	 */
	protected function fetch_from_bitbucket( $file_path, $config ) {
		return new \WP_Error( 'not_implemented', __( 'Bitbucket integration not yet implemented', 'betterdocs-pro' ) );
	}

	/**
	 * Convert HTML to Markdown
	 *
	 * @param string $html
	 * @return string
	 */
	protected function html_to_markdown( $html ) {
		// Clean up the HTML first
		$html = $this->clean_html_for_markdown( $html );

		// Convert common HTML elements to Markdown
		$conversions = [
			// Headers
			'/<h1[^>]*>(.*?)<\/h1>/i' => '# $1',
			'/<h2[^>]*>(.*?)<\/h2>/i' => '## $1',
			'/<h3[^>]*>(.*?)<\/h3>/i' => '### $1',
			'/<h4[^>]*>(.*?)<\/h4>/i' => '#### $1',
			'/<h5[^>]*>(.*?)<\/h5>/i' => '##### $1',
			'/<h6[^>]*>(.*?)<\/h6>/i' => '###### $1',

			// Bold and Italic
			'/<strong[^>]*>(.*?)<\/strong>/i' => '**$1**',
			'/<b[^>]*>(.*?)<\/b>/i' => '**$1**',
			'/<em[^>]*>(.*?)<\/em>/i' => '*$1*',
			'/<i[^>]*>(.*?)<\/i>/i' => '*$1*',

			// Links
			'/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i' => '[$2]($1)',

			// Images
			'/<img[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*[\/]?>/i' => '![$2]($1)',
			'/<img[^>]*alt=["\']([^"\']*)["\'][^>]*src=["\']([^"\']*)["\'][^>]*[\/]?>/i' => '![$1]($2)',
			'/<img[^>]*src=["\']([^"\']*)["\'][^>]*[\/]?>/i' => '![]($1)',

			// Code
			'/<code[^>]*>(.*?)<\/code>/i' => '`$1`',
			'/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is' => "```\n$1\n```",
			'/<pre[^>]*>(.*?)<\/pre>/is' => "```\n$1\n```",

			// Lists
			'/<ul[^>]*>/i' => '',
			'/<\/ul>/i' => '',
			'/<ol[^>]*>/i' => '',
			'/<\/ol>/i' => '',
			'/<li[^>]*>(.*?)<\/li>/i' => '- $1',

			// Blockquotes
			'/<blockquote[^>]*>(.*?)<\/blockquote>/is' => '> $1',

			// Line breaks and paragraphs
			'/<br[^>]*\/?>/i' => "\n",
			'/<p[^>]*>/i' => '',
			'/<\/p>/i' => "\n\n",

			// Tables (basic)
			'/<table[^>]*>/i' => '',
			'/<\/table>/i' => "\n",
			'/<tr[^>]*>/i' => '',
			'/<\/tr>/i' => "|\n",
			'/<th[^>]*>(.*?)<\/th>/i' => '| $1 ',
			'/<td[^>]*>(.*?)<\/td>/i' => '| $1 ',

			// Remove remaining HTML tags
			'/<[^>]+>/' => '',
		];

		foreach ( $conversions as $pattern => $replacement ) {
			$html = preg_replace( $pattern, $replacement, $html );
		}

		// Clean up extra whitespace
		$html = preg_replace( '/\n\s*\n\s*\n/', "\n\n", $html );
		$html = trim( $html );

		// Decode HTML entities
		$html = html_entity_decode( $html, ENT_QUOTES, 'UTF-8' );

		return $html;
	}

	/**
	 * Convert Markdown to HTML
	 *
	 * @param string $markdown
	 * @return string
	 */
	/**
	 * Convert Markdown to Gutenberg-compatible block HTML.
	 *
	 * Step 1: Convert markdown syntax to HTML using regex (headings, bold, etc.)
	 * Step 2: Wrap each HTML element in Gutenberg block comments so the editor
	 *         renders proper blocks instead of a single Classic block.
	 */
	protected function markdown_to_html( $markdown ) {
		// Normalize line endings
		$markdown = str_replace( "\r\n", "\n", $markdown );
		$markdown = str_replace( "\r", "\n", $markdown );

		// ── Step 1: Convert Markdown to HTML ──────────────────────────
		$conversions = [
			// Code blocks (fenced) — must come before inline code
			'/```\n(.*?)\n```/s' => '<pre><code>$1</code></pre>',

			// Headers
			'/^###### (.*$)/m' => '<h6>$1</h6>',
			'/^##### (.*$)/m'  => '<h5>$1</h5>',
			'/^#### (.*$)/m'   => '<h4>$1</h4>',
			'/^### (.*$)/m'    => '<h3>$1</h3>',
			'/^## (.*$)/m'     => '<h2>$1</h2>',
			'/^# (.*$)/m'      => '<h1>$1</h1>',

			// Images (before links to avoid conflict)
			'/!\[([^\]]*)\]\(([^)]+)\)/' => '<img src="$2" alt="$1" />',

			// Links
			'/\[([^\]]+)\]\(([^)]+)\)/' => '<a href="$2">$1</a>',

			// Bold and Italic
			'/\*\*(.*?)\*\*/' => '<strong>$1</strong>',
			'/\*(.*?)\*/'     => '<em>$1</em>',

			// Inline code
			'/`([^`]+)`/' => '<code>$1</code>',

			// Lists
			'/^- (.*$)/m'       => '<li>$1</li>',
			'/^\* (.*$)/m'      => '<li>$1</li>',
			'/^\d+\. (.*$)/m'   => '<li>$1</li>',

			// Blockquotes
			'/^> (.*$)/m' => '<blockquote>$1</blockquote>',

			// Horizontal rules
			'/^(---+|\*\*\*+|___+)\s*$/m' => '<hr />',

			// Paragraphs — double newlines become paragraph breaks
			'/\n\n/' => '</p><p>',
		];

		foreach ( $conversions as $pattern => $replacement ) {
			$markdown = preg_replace( $pattern, $replacement, $markdown );
		}

		// Wrap in paragraphs
		$html = '<p>' . $markdown . '</p>';

		// Clean up empty paragraphs
		$html = preg_replace( '/<p>\s*<\/p>/', '', $html );

		// Fix list wrapping — consecutive <li> inside <p> become <ul>
		$html = preg_replace( '/<p>(<li>.*?<\/li>)<\/p>/s', '<ul>$1</ul>', $html );
		$html = preg_replace( '/<\/li>\s*<li>/', '</li><li>', $html );

		// Fix blockquote wrapping
		$html = preg_replace( '/<p>(<blockquote>.*?<\/blockquote>)<\/p>/s', '$1', $html );

		// ── Step 2: Wrap HTML elements in Gutenberg block comments ────

		// Headings
		for ( $i = 1; $i <= 6; $i++ ) {
			$attrs = $i !== 2 ? ' {"level":' . $i . '}' : '';
			$html = preg_replace(
				'/<h' . $i . '>(.*?)<\/h' . $i . '>/s',
				"<!-- wp:heading{$attrs} -->\n<h{$i} class=\"wp-block-heading\">$1</h{$i}>\n<!-- /wp:heading -->",
				$html
			);
		}

		// Code blocks
		$html = preg_replace(
			'/<pre><code>(.*?)<\/code><\/pre>/s',
			"<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>$1</code></pre>\n<!-- /wp:code -->",
			$html
		);

		// Lists
		$html = preg_replace(
			'/<ul>(.*?)<\/ul>/s',
			"<!-- wp:list -->\n<ul class=\"wp-block-list\">$1</ul>\n<!-- /wp:list -->",
			$html
		);

		// Blockquotes
		$html = preg_replace(
			'/<blockquote>(.*?)<\/blockquote>/s',
			"<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>$1</p></blockquote>\n<!-- /wp:quote -->",
			$html
		);

		// Images
		$html = preg_replace(
			'/<img src="([^"]*)" alt="([^"]*)" \/>/',
			"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"$1\" alt=\"$2\"/></figure>\n<!-- /wp:image -->",
			$html
		);

		// Horizontal rules
		$html = preg_replace(
			'/<hr \/>/',
			"<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->",
			$html
		);

		// Paragraphs — wrap remaining <p>...</p> that aren't already inside a block
		$html = preg_replace(
			'/(?<!-->)\s*<p>(.*?)<\/p>/s',
			"\n<!-- wp:paragraph -->\n<p>$1</p>\n<!-- /wp:paragraph -->",
			$html
		);

		// Clean up whitespace
		$html = preg_replace( '/\n{3,}/', "\n\n", $html );
		$html = trim( $html );

		return $html;
	}

	/**
	 * Clean HTML for better Markdown conversion
	 *
	 * @param string $html
	 * @return string
	 */
	protected function clean_html_for_markdown( $html ) {
		// Remove WordPress-specific elements that don't translate well
		$html = preg_replace( '/<div[^>]*class="[^"]*wp-block[^"]*"[^>]*>/i', '<div>', $html );

		// Remove empty paragraphs
		$html = preg_replace( '/<p[^>]*>\s*<\/p>/i', '', $html );

		// Convert WordPress image captions
		$html = preg_replace(
			'/<div[^>]*class="[^"]*wp-caption[^"]*"[^>]*>.*?<img([^>]*)>.*?<p[^>]*class="[^"]*wp-caption-text[^"]*"[^>]*>(.*?)<\/p>.*?<\/div>/is',
			'<img$1 alt="$2" />',
			$html
		);

		// Remove WordPress shortcodes that weren't processed
		$html = preg_replace( '/\[[^\]]+\]/', '', $html );

		// Clean up extra whitespace
		$html = preg_replace( '/\s+/', ' ', $html );
		$html = preg_replace( '/>\s+</', '><', $html );

		return $html;
	}

	/**
	 * Process WordPress-specific content before conversion
	 *
	 * @param string $content
	 * @return string
	 */
	protected function process_wordpress_content( $content ) {
		// Handle Gutenberg blocks
		if ( has_blocks( $content ) ) {
			$content = $this->process_gutenberg_blocks( $content );
		}

		// Process common WordPress shortcodes
		$content = $this->process_wordpress_shortcodes( $content );

		return $content;
	}

	/**
	 * Process Gutenberg blocks for better Markdown conversion
	 *
	 * @param string $content
	 * @return string
	 */
	protected function process_gutenberg_blocks( $content ) {
		// Parse blocks
		$blocks = parse_blocks( $content );
		$processed_content = '';

		foreach ( $blocks as $block ) {
			$processed_content .= $this->render_block_for_markdown( $block );
		}

		return $processed_content;
	}

	/**
	 * Render a single block for Markdown conversion
	 *
	 * @param array $block
	 * @return string
	 */
	protected function render_block_for_markdown( $block ) {
		if ( empty( $block['blockName'] ) ) {
			return $block['innerHTML'] ?? '';
		}

		switch ( $block['blockName'] ) {
			case 'core/heading':
				$level = $block['attrs']['level'] ?? 2;
				$content = wp_strip_all_tags( $block['innerHTML'] );
				return str_repeat( '#', $level ) . ' ' . $content . "\n\n";

			case 'core/paragraph':
				return $block['innerHTML'] . "\n\n";

			case 'core/list':
				return $block['innerHTML'] . "\n\n";

			case 'core/quote':
				$content = wp_strip_all_tags( $block['innerHTML'] );
				return '> ' . $content . "\n\n";

			case 'core/code':
				$content = wp_strip_all_tags( $block['innerHTML'] );
				return "```\n" . $content . "\n```\n\n";

			case 'core/image':
				// Extract image info from block
				if ( isset( $block['attrs']['id'] ) ) {
					$image_id = $block['attrs']['id'];
					$image_url = wp_get_attachment_url( $image_id );
					$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
					if ( $image_url ) {
						return "![{$image_alt}]({$image_url})\n\n";
					}
				}
				return $block['innerHTML'] . "\n\n";

			case 'core/table':
				return $block['innerHTML'] . "\n\n";

			default:
				// For unknown blocks, return the rendered HTML
				return render_block( $block ) . "\n\n";
		}
	}

	/**
	 * Process WordPress shortcodes for better Markdown conversion
	 *
	 * @param string $content
	 * @return string
	 */
	protected function process_wordpress_shortcodes( $content ) {
		// Handle common shortcodes that should be preserved or converted

		// Convert [caption] shortcode to image with caption
		$content = preg_replace_callback(
			'/\[caption[^\]]*\](.*?)\[\/caption\]/s',
			function( $matches ) {
				$caption_content = $matches[1];
				// Extract image and caption text
				if ( preg_match( '/<img[^>]*src=["\']([^"\']*)["\'][^>]*>(.*)$/s', $caption_content, $img_matches ) ) {
					$img_src = $img_matches[1];
					$caption_text = wp_strip_all_tags( trim( $img_matches[2] ) );
					return "![{$caption_text}]({$img_src})\n\n*{$caption_text}*\n\n";
				}
				return $caption_content;
			},
			$content
		);

		// Convert [code] shortcode
		$content = preg_replace( '/\[code[^\]]*\](.*?)\[\/code\]/s', "```\n$1\n```", $content );

		// Convert [quote] shortcode
		$content = preg_replace( '/\[quote[^\]]*\](.*?)\[\/quote\]/s', "> $1", $content );

		// Remove other shortcodes that don't have good Markdown equivalents
		$content = preg_replace( '/\[[^\]]+\]/', '', $content );

		return $content;
	}

	/**
	 * Process images for GitHub compatibility
	 *
	 * @param string $markdown
	 * @param WP_Post $post
	 * @return string
	 */
	// ── New AJAX actions for GitHub Sync toolbar button ────────────────────

	/**
	 * AJAX: Get diff between current post content and the version in GitHub
	 */
	public function ajax_get_diff() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_github_diff_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$post    = $this->authorize_document_request( $post_id );

		// Auto-drafts with no real content should show "no changes".
		if ( $post->post_status === 'auto-draft' && empty( trim( $post->post_content ) ) ) {
			wp_send_json_success( [
				'diff_lines' => [],
				'additions'  => 0,
				'deletions'  => 0,
				'changes'    => 0,
			] );
		}

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			wp_send_json_error( __( 'Git integration is not fully configured. Please connect your account and select a repository in BetterDocs Settings.', 'betterdocs-pro' ) );
		}

		$file_path = $this->resolve_file_path( $post_id, $post );

		// Fetch remote version
		$remote = $this->fetch_from_repository( $file_path, $config );
		if ( is_wp_error( $remote ) ) {
			// If file not found remotely, the whole doc is "new"
			$local_md = $this->convert_to_markdown( $post );
			$lines    = explode( "\n", $local_md );
			$diff_lines = [];
			foreach ( $lines as $i => $line ) {
				$diff_lines[] = [ 'type' => 'add', 'line_no' => $i + 1, 'content' => $line ];
			}
			wp_send_json_success( [
				'diff_lines' => $diff_lines,
				'additions'  => count( $lines ),
				'deletions'  => 0,
				'changes'    => count( $lines ),
			] );
		}

		$local_md  = $this->convert_to_markdown( $post );
		$remote_md = $remote['content'];

		$diff_result = $this->compute_diff( $remote_md, $local_md );
		wp_send_json_success( $diff_result );
	}

	/**
	 * AJAX handler for "Fetch" — returns diff + remote branch status (commits behind).
	 * Used by the editor dropdown to show a GitHub-Desktop-style fetch indicator.
	 */
	public function ajax_fetch_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_git_fetch_status_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$post    = $this->authorize_document_request( $post_id );

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			wp_send_json_error( __( 'Git integration is not fully configured. Please connect your account and select a repository in BetterDocs Settings.', 'betterdocs-pro' ) );
		}

		$file_path = $this->resolve_file_path( $post_id, $post );

		// Diff portion (same logic as ajax_get_diff).
		$diff_result = [
			'diff_lines' => [],
			'additions'  => 0,
			'deletions'  => 0,
			'changes'    => 0,
		];
		$remote_exists = true;
		if ( $post->post_status === 'auto-draft' && empty( trim( $post->post_content ) ) ) {
			// keep default
		} else {
			$remote = $this->fetch_from_repository( $file_path, $config );
			if ( is_wp_error( $remote ) ) {
				$remote_exists = false;
				$local_md = $this->convert_to_markdown( $post );
				$lines    = explode( "\n", $local_md );
				$add      = [];
				foreach ( $lines as $i => $line ) {
					$add[] = [ 'type' => 'add', 'line_no' => $i + 1, 'content' => $line ];
				}
				$diff_result = [
					'diff_lines' => $add,
					'additions'  => count( $lines ),
					'deletions'  => 0,
					'changes'    => count( $lines ),
				];
			} else {
				$local_md    = $this->convert_to_markdown( $post );
				$diff_result = $this->compute_diff( $remote['content'], $local_md );
			}
		}

		// Remote branch status.
		$last_pushed_hash = get_post_meta( $post_id, '_betterdocs_git_commit_hash', true );
		$behind           = $this->get_commits_behind( $last_pushed_hash, $config );

		$branch = isset( $config['branch'] ) ? $config['branch'] : 'main';

		wp_send_json_success( array_merge( $diff_result, [
			'branch'          => $branch,
			'remote_exists'   => $remote_exists,
			'behind_by'       => is_array( $behind ) && isset( $behind['behind_by'] ) ? intval( $behind['behind_by'] ) : null,
			'latest_commit'   => is_array( $behind ) && isset( $behind['latest_commit'] ) ? $behind['latest_commit'] : null,
			'has_last_pushed' => ! empty( $last_pushed_hash ),
		] ) );
	}

	/**
	 * Return recent commits touching the current doc's file (or the branch
	 * when the file isn't tracked yet) for the History tab.
	 */
	public function ajax_commit_history() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_git_commit_history_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$post    = $this->authorize_document_request( $post_id );

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			wp_send_json_error( __( 'Git integration is not fully configured. Please connect your account and select a repository in BetterDocs Settings.', 'betterdocs-pro' ) );
		}

		$file_path = $this->resolve_file_path( $post_id, $post );

		$commits = $this->get_commit_history( $file_path, $config, 15 );
		wp_send_json_success( [
			'commits' => is_array( $commits ) ? $commits : [],
			'branch'  => isset( $config['branch'] ) ? $config['branch'] : 'main',
		] );
	}

	protected function get_commit_history( $file_path, $config, $limit = 15 ) {
		$provider = isset( $config['provider'] ) ? $config['provider'] : 'github';
		if ( $provider === 'gitlab' ) {
			return $this->get_gitlab_commit_history( $file_path, $config, $limit );
		}
		return $this->get_github_commit_history( $file_path, $config, $limit );
	}

	protected function get_github_commit_history( $file_path, $config, $limit = 15 ) {
		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return [];
		}
		$branch = isset( $config['branch'] ) ? $config['branch'] : 'main';
		$args   = [ 'sha' => $branch, 'per_page' => $limit ];
		if ( ! empty( $file_path ) ) {
			$args['path'] = $file_path;
		}
		$api_url = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}/commits?" . http_build_query( $args );
		$response = $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return [];
		}
		$out = [];
		foreach ( $response as $c ) {
			if ( ! is_array( $c ) ) {
				continue;
			}
			$sha_full = isset( $c['sha'] ) ? $c['sha'] : '';
			$out[] = [
				'sha'     => $sha_full ? substr( $sha_full, 0, 7 ) : '',
				'sha_full'=> $sha_full,
				'message' => isset( $c['commit']['message'] ) ? strtok( $c['commit']['message'], "\n" ) : '',
				'author'  => isset( $c['commit']['author']['name'] ) ? $c['commit']['author']['name'] : '',
				'avatar'  => isset( $c['author']['avatar_url'] ) ? $c['author']['avatar_url'] : '',
				'date'    => isset( $c['commit']['author']['date'] ) ? $c['commit']['author']['date'] : '',
				'url'     => isset( $c['html_url'] ) ? $c['html_url'] : '',
			];
		}
		return $out;
	}

	protected function get_gitlab_commit_history( $file_path, $config, $limit = 15 ) {
		$oauth_user = get_option( GitHubOAuth::USER_OPTION, [] );
		$project_id = $oauth_user['project_id'] ?? $config['repository_url'] ?? '';
		$base_url   = $oauth_user['instance_url'] ?? 'https://gitlab.com';
		if ( empty( $project_id ) ) {
			return [];
		}
		$pid  = urlencode( $project_id );
		$args = [
			'ref_name' => isset( $config['branch'] ) ? $config['branch'] : 'main',
			'per_page' => $limit,
		];
		if ( ! empty( $file_path ) ) {
			$args['path'] = $file_path;
		}
		$url = "{$base_url}/api/v4/projects/{$pid}/repository/commits?" . http_build_query( $args );
		$res = wp_remote_get( $url, [
			'headers' => [ 'PRIVATE-TOKEN' => $config['access_token'] ],
			'timeout' => 15,
		] );
		if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) >= 400 ) {
			return [];
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $body ) ) {
			return [];
		}
		$out = [];
		foreach ( $body as $c ) {
			$out[] = [
				'sha'     => isset( $c['short_id'] ) ? $c['short_id'] : '',
				'sha_full'=> isset( $c['id'] ) ? $c['id'] : '',
				'message' => isset( $c['title'] ) ? $c['title'] : '',
				'author'  => isset( $c['author_name'] ) ? $c['author_name'] : '',
				'avatar'  => '',
				'date'    => isset( $c['created_at'] ) ? $c['created_at'] : '',
				'url'     => isset( $c['web_url'] ) ? $c['web_url'] : '',
			];
		}
		return $out;
	}

	/**
	 * Compare a stored local commit hash with the current remote branch HEAD.
	 * Returns ['behind_by' => int, 'latest_commit' => [...]] or null when the
	 * count cannot be determined (e.g. no prior push, provider unsupported).
	 */
	protected function get_commits_behind( $last_hash, $config ) {
		$provider = isset( $config['provider'] ) ? $config['provider'] : 'github';
		if ( empty( $last_hash ) ) {
			$latest = $this->get_latest_commit( $config );
			if ( $latest ) {
				return [ 'behind_by' => null, 'latest_commit' => $latest ];
			}
			return null;
		}
		if ( $provider === 'gitlab' ) {
			return $this->get_gitlab_commits_behind( $last_hash, $config );
		}
		return $this->get_github_commits_behind( $last_hash, $config );
	}

	protected function get_github_commits_behind( $last_hash, $config ) {
		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return null;
		}
		$branch  = isset( $config['branch'] ) ? $config['branch'] : 'main';
		$api_url = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}/compare/" . rawurlencode( $last_hash ) . '...' . rawurlencode( $branch );

		$response = $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return null;
		}

		$latest = null;
		if ( ! empty( $response['commits'] ) && is_array( $response['commits'] ) ) {
			$last = end( $response['commits'] );
			if ( is_array( $last ) ) {
				$latest = [
					'sha'     => isset( $last['sha'] ) ? substr( $last['sha'], 0, 7 ) : '',
					'message' => isset( $last['commit']['message'] ) ? strtok( $last['commit']['message'], "\n" ) : '',
					'author'  => isset( $last['commit']['author']['name'] ) ? $last['commit']['author']['name'] : '',
					'date'    => isset( $last['commit']['author']['date'] ) ? $last['commit']['author']['date'] : '',
				];
			}
		}

		return [
			'behind_by'     => isset( $response['behind_by'] ) ? intval( $response['behind_by'] ) : 0,
			'latest_commit' => $latest,
		];
	}

	protected function get_gitlab_commits_behind( $last_hash, $config ) {
		$oauth_user = get_option( GitHubOAuth::USER_OPTION, [] );
		$project_id = $oauth_user['project_id'] ?? $config['repository_url'] ?? '';
		$base_url   = $oauth_user['instance_url'] ?? 'https://gitlab.com';
		if ( empty( $project_id ) ) {
			return null;
		}
		$pid    = urlencode( $project_id );
		$branch = isset( $config['branch'] ) ? $config['branch'] : 'main';
		$url    = "{$base_url}/api/v4/projects/{$pid}/repository/compare?from=" . rawurlencode( $last_hash ) . '&to=' . rawurlencode( $branch );

		$response = wp_remote_get( $url, [
			'headers' => [ 'PRIVATE-TOKEN' => $config['access_token'] ],
			'timeout' => 15,
		] );
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 400 ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return null;
		}
		$commits = isset( $body['commits'] ) && is_array( $body['commits'] ) ? $body['commits'] : [];
		$latest  = null;
		if ( ! empty( $commits ) ) {
			$last = end( $commits );
			$latest = [
				'sha'     => isset( $last['short_id'] ) ? $last['short_id'] : '',
				'message' => isset( $last['title'] ) ? $last['title'] : '',
				'author'  => isset( $last['author_name'] ) ? $last['author_name'] : '',
				'date'    => isset( $last['created_at'] ) ? $last['created_at'] : '',
			];
		}
		return [
			'behind_by'     => count( $commits ),
			'latest_commit' => $latest,
		];
	}

	/**
	 * Fetch the latest branch commit when we have no local hash to compare against.
	 */
	protected function get_latest_commit( $config ) {
		$provider = isset( $config['provider'] ) ? $config['provider'] : 'github';
		$branch   = isset( $config['branch'] ) ? $config['branch'] : 'main';
		if ( $provider === 'gitlab' ) {
			$oauth_user = get_option( GitHubOAuth::USER_OPTION, [] );
			$project_id = $oauth_user['project_id'] ?? $config['repository_url'] ?? '';
			$base_url   = $oauth_user['instance_url'] ?? 'https://gitlab.com';
			if ( empty( $project_id ) ) {
				return null;
			}
			$pid = urlencode( $project_id );
			$url = "{$base_url}/api/v4/projects/{$pid}/repository/commits?ref_name=" . rawurlencode( $branch ) . '&per_page=1';
			$res = wp_remote_get( $url, [
				'headers' => [ 'PRIVATE-TOKEN' => $config['access_token'] ],
				'timeout' => 15,
			] );
			if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) >= 400 ) {
				return null;
			}
			$body = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( ! is_array( $body ) || empty( $body[0] ) ) {
				return null;
			}
			$c = $body[0];
			return [
				'sha'     => isset( $c['short_id'] ) ? $c['short_id'] : '',
				'message' => isset( $c['title'] ) ? $c['title'] : '',
				'author'  => isset( $c['author_name'] ) ? $c['author_name'] : '',
				'date'    => isset( $c['created_at'] ) ? $c['created_at'] : '',
			];
		}

		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return null;
		}
		$api_url = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}/commits?sha=" . rawurlencode( $branch ) . '&per_page=1';
		$res = $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );
		if ( is_wp_error( $res ) || ! is_array( $res ) || empty( $res[0] ) ) {
			return null;
		}
		$c = $res[0];
		return [
			'sha'     => isset( $c['sha'] ) ? substr( $c['sha'], 0, 7 ) : '',
			'message' => isset( $c['commit']['message'] ) ? strtok( $c['commit']['message'], "\n" ) : '',
			'author'  => isset( $c['commit']['author']['name'] ) ? $c['commit']['author']['name'] : '',
			'date'    => isset( $c['commit']['author']['date'] ) ? $c['commit']['author']['date'] : '',
		];
	}

	/**
	 * Compute a simple unified diff between two strings.
	 * Returns structured diff lines for the frontend renderer.
	 *
	 * @param string $old
	 * @param string $new
	 * @return array
	 */
	protected function compute_diff( $old, $new ) {
		$old_lines  = explode( "\n", $old );
		$new_lines  = explode( "\n", $new );
		$diff_lines = [];
		$additions  = 0;
		$deletions  = 0;

		// Use a simple LCS-based diff
		$n = count( $old_lines );
		$m = count( $new_lines );

		// Build LCS table (limit to 500 lines each to avoid memory issues)
		$max = 500;
		if ( $n > $max || $m > $max ) {
			// Fallback: treat entire file as changed
			foreach ( $old_lines as $i => $line ) {
				$diff_lines[] = [ 'type' => 'del', 'line_no' => $i + 1, 'content' => $line ];
				$deletions++;
			}
			foreach ( $new_lines as $i => $line ) {
				$diff_lines[] = [ 'type' => 'add', 'line_no' => $i + 1, 'content' => $line ];
				$additions++;
			}
			return compact( 'diff_lines', 'additions', 'deletions' ) + [ 'changes' => $additions + $deletions ];
		}

		$lcs = array_fill( 0, $n + 1, array_fill( 0, $m + 1, 0 ) );
		for ( $i = $n - 1; $i >= 0; $i-- ) {
			for ( $j = $m - 1; $j >= 0; $j-- ) {
				$lcs[ $i ][ $j ] = ( $old_lines[ $i ] === $new_lines[ $j ] )
					? $lcs[ $i + 1 ][ $j + 1 ] + 1
					: max( $lcs[ $i + 1 ][ $j ], $lcs[ $i ][ $j + 1 ] );
			}
		}

		$i = $j = 0;
		$context = 3;
		$pending = [];

		while ( $i < $n || $j < $m ) {
			if ( $i < $n && $j < $m && $old_lines[ $i ] === $new_lines[ $j ] ) {
				$pending[] = [ 'type' => 'ctx', 'line_no' => $j + 1, 'content' => $old_lines[ $i ] ];
				$i++;
				$j++;
			} elseif ( $j < $m && ( $i >= $n || $lcs[ $i ][ $j + 1 ] >= $lcs[ $i + 1 ][ $j ] ) ) {
				$diff_lines[] = [ 'type' => 'add', 'line_no' => $j + 1, 'content' => $new_lines[ $j ] ];
				$additions++;
				$pending = [];
				$j++;
			} else {
				$diff_lines[] = [ 'type' => 'del', 'line_no' => $i + 1, 'content' => $old_lines[ $i ] ];
				$deletions++;
				$pending = [];
				$i++;
			}
		}

		$changes = $additions + $deletions;

		return compact( 'diff_lines', 'additions', 'deletions', 'changes' );
	}

	/**
	 * AJAX: List .md files in the configured docs directory of the GitHub repo
	 */
	public function ajax_list_md_files() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_github_list_md_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}
		// Repo-level operation: this enumerates the file listing of the
		// admin-configured (often private) repository. `edit_docs` is only the
		// generic "may use the docs editor" cap, so a Contributor/Author in
		// article_roles could otherwise read the private repo's file names. Gate
		// on the same capability that owns the connection (test/diff use it too).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'betterdocs-pro' ) );
		}

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			wp_send_json_error( __( 'Git integration is not fully configured. Please connect your account and select a repository in BetterDocs Settings.', 'betterdocs-pro' ) );
		}

		$docs_dir = trim( $config['docs_directory'], '/' );
		$response = $this->list_directory_contents( $config, $docs_dir );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		if ( ! is_array( $response ) ) {
			wp_send_json_error( __( 'Unexpected API response', 'betterdocs-pro' ) );
		}

		// GitHub uses type=file, GitLab uses type=blob.
		$file_type = $config['provider'] === 'gitlab' ? 'blob' : 'file';

		$files = [];
		foreach ( $response as $item ) {
			if ( ! is_array( $item ) ) continue;
			if ( isset( $item['type'] ) && $item['type'] === $file_type && isset( $item['name'] ) && substr( $item['name'], -3 ) === '.md' ) {
				// Check if a doc already exists — by git file path meta or slug
				$slug = basename( $item['name'], '.md' );
				$existing = get_posts( [
					'post_type'      => 'docs',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Plugin-controlled meta lookup to map a single git file path to its doc.
					'meta_key'       => '_betterdocs_git_file_path',
					'meta_value'     => $item['path'],
					// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				] );
				if ( empty( $existing ) ) {
					$existing = get_posts( [
						'post_type'      => 'docs',
						'name'           => $slug,
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					] );
				}

				$files[] = [
					'name'          => $item['name'],
					'path'          => $item['path'],
					'exists_as_doc' => ! empty( $existing ),
				];
			}
		}

		wp_send_json_success( [ 'files' => $files ] );
	}

	/**
	 * List directory contents from the configured Git provider.
	 *
	 * @param array  $config   Git config from get_config().
	 * @param string $dir_path Directory path inside the repository.
	 * @return array|WP_Error  Array of file/tree items or WP_Error.
	 */
	protected function list_directory_contents( $config, $dir_path ) {
		if ( $config['provider'] === 'gitlab' ) {
			return $this->list_gitlab_directory( $config, $dir_path );
		}

		// GitHub
		$repo_info = $this->parse_github_url( $config['repository_url'] );
		if ( is_wp_error( $repo_info ) ) {
			return $repo_info;
		}

		$api_url  = "https://api.github.com/repos/{$repo_info['owner']}/{$repo_info['repo']}/contents/{$dir_path}";
		$api_url .= '?ref=' . urlencode( $config['branch'] );

		return $this->github_api_request( $api_url, 'GET', null, $config['access_token'] );
	}

	/**
	 * List files in a GitLab project directory via the Repository Tree API.
	 *
	 * @param array  $config   Git config from get_config().
	 * @param string $dir_path Directory path inside the repository.
	 * @return array|WP_Error  Array of tree items or WP_Error.
	 */
	protected function list_gitlab_directory( $config, $dir_path ) {
		$oauth_user = get_option( GitHubOAuth::USER_OPTION, [] );
		$project_id = $oauth_user['project_id'] ?? $config['repository_url'] ?? '';
		$base_url   = $oauth_user['instance_url'] ?? 'https://gitlab.com';

		if ( empty( $project_id ) ) {
			return new \WP_Error( 'missing_project_id', __( 'GitLab Project ID is not configured. Please reconnect your GitLab account in BetterDocs Settings with a valid Project ID.', 'betterdocs-pro' ) );
		}

		$pid    = urlencode( $project_id );
		$branch = $config['branch'] ?: 'main';
		$token  = $config['access_token'];

		$api_url = "{$base_url}/api/v4/projects/{$pid}/repository/tree?per_page=100";
		$api_url .= '&ref=' . urlencode( $branch );
		if ( $dir_path ) {
			$api_url .= '&path=' . urlencode( $dir_path );
		}

		$response = wp_remote_get( $api_url, [
			'headers' => [ 'PRIVATE-TOKEN' => $token ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 404 ) {
			return new \WP_Error( 'directory_not_found', __( 'The configured docs directory was not found in the repository. Please check the directory path in BetterDocs Settings.', 'betterdocs-pro' ) );
		}

		if ( $code >= 400 ) {
			$raw_msg = $body['message'] ?? $body['error'] ?? '';
			return new \WP_Error( 'gitlab_api_error', $this->humanize_api_error( $code, $raw_msg, 'gitlab' ) );
		}

		return is_array( $body ) ? $body : [];
	}

	/**
	 * AJAX: Import selected .md files from Git as docs posts
	 */
	public function ajax_import_md() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'betterdocs_github_import_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'betterdocs-pro' ) );
		}
		// Repo-level bulk operation: pulls arbitrary files out of the
		// admin-configured repository and creates/overwrites docs from them. Like
		// list_md_files above, this must not be reachable with the generic
		// `edit_docs` cap that every article_roles member holds — it would let a
		// junior writer both discover private-repo file names and mass-create docs.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'betterdocs-pro' ) );
		}

		// The React modal sends `files` as a JSON-encoded array (so it can
		// pass through application/x-www-form-urlencoded as a single field).
		// Plain $_POST won't decode it — we have to do that ourselves before
		// iterating, otherwise every fetch would target a literal '["foo.md"]'
		// path and fail.
		$raw = isset( $_POST['files'] ) ? wp_unslash( $_POST['files'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON blob; each decoded element is sanitized below.
		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;
		if ( ! is_array( $decoded ) ) {
			$decoded = is_array( $raw ) ? $raw : ( $raw === '' ? [] : [ $raw ] );
		}
		$files = [];
		foreach ( $decoded as $file ) {
			if ( is_string( $file ) && $file !== '' ) {
				$files[] = sanitize_text_field( $file );
			}
		}
		if ( empty( $files ) ) {
			wp_send_json_error( __( 'No files selected', 'betterdocs-pro' ) );
		}

		// The file list is attacker-supplied and was previously unbounded — each
		// entry costs a repository round-trip plus a post write.
		if ( count( $files ) > self::MAX_IMPORT_FILES ) {
			wp_send_json_error(
				sprintf(
					/* translators: %d: maximum number of files that can be imported at once. */
					__( 'Too many files selected. Import at most %d files at a time.', 'betterdocs-pro' ),
					self::MAX_IMPORT_FILES
				)
			);
		}

		// Importing creates documents. Publishing them requires the publish
		// capability — without it the import lands as drafts for review rather
		// than silently granting a contributor the ability to publish.
		$new_post_status = current_user_can( 'publish_docs' ) ? 'publish' : 'draft';

		$config = $this->get_config();
		if ( empty( $config['repository_url'] ) || empty( $config['access_token'] ) ) {
			wp_send_json_error( __( 'Git integration is not fully configured. Please connect your account and select a repository in BetterDocs Settings.', 'betterdocs-pro' ) );
		}

		$imported = [];
		$errors   = [];

		foreach ( $files as $file_path ) {
			// Confine to a traversal-free Markdown path inside the docs directory
			// before it is fetched — the client can send any string here.
			$file_path = $this->sanitize_repo_file_path( $file_path, $config );
			if ( false === $file_path ) {
				$errors[] = __( 'a rejected file path', 'betterdocs-pro' );
				continue;
			}

			$response = $this->fetch_from_repository( $file_path, $config );
			if ( is_wp_error( $response ) || empty( $response['content'] ) ) {
				$errors[] = $file_path;
				continue;
			}

			$md_content = $response['content'];
			$sha        = $response['sha'] ?? '';
			$html       = $this->convert_from_markdown( $md_content );

			// Extract title from front matter, HTML h1 wrapper, or first Markdown heading
			$title = '';
			if ( preg_match( '/^---\s*\ntitle:\s*["\']?(.+?)["\']?\s*\n/s', $md_content, $m ) ) {
				$title = trim( $m[1], '"\'` ' );
			} elseif ( preg_match( '/<h1>(.*?)<\/h1>/i', $md_content, $m ) ) {
				$title = trim( wp_strip_all_tags( $m[1] ) );
			} elseif ( preg_match( '/^#\s+(.+)/m', $md_content, $m ) ) {
				$title = trim( $m[1] );
			}
			if ( empty( $title ) ) {
				$title = ucwords( str_replace( [ '-', '_' ], ' ', basename( $file_path, '.md' ) ) );
			}

			$slug = basename( $file_path, '.md' );

			// Check if post already exists — first by git file path meta, then by slug
			$existing = get_posts( [
				'post_type'      => 'docs',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Plugin-controlled meta lookup to map a single git file path to its doc.
				'meta_key'       => '_betterdocs_git_file_path',
				'meta_value'     => $file_path,
				// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			] );

			if ( empty( $existing ) ) {
				$existing = get_posts( [
					'post_type'      => 'docs',
					'name'           => $slug,
					'post_status'    => 'any',
					'posts_per_page' => 1,
				] );
			}

			if ( $existing ) {
				// A repository path or slug can match *any* existing doc, including
				// one owned by another author. Matching is not authorization — the
				// caller must be able to edit that specific document.
				if ( ! current_user_can( 'edit_post', $existing[0]->ID ) ) {
					$errors[] = $file_path;
					continue;
				}

				$post_data = [
					'ID'           => $existing[0]->ID,
					'post_content' => $html,
					'post_title'   => $title,
				];
				$post_id = wp_update_post( $post_data );
			} else {
				$post_data = [
					'post_type'    => 'docs',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => $html,
					'post_status'  => $new_post_status,
				];
				$post_id = wp_insert_post( $post_data );
			}

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_betterdocs_git_file_path', $file_path );
				update_post_meta( $post_id, '_betterdocs_git_sync_status', 'synced' );
				update_post_meta( $post_id, '_betterdocs_git_last_sync', current_time( 'mysql' ) );
				if ( $sha ) {
					update_post_meta( $post_id, '_betterdocs_git_commit_hash', $sha );
				}
				$imported[] = $post_id;
			} else {
				$errors[] = $file_path;
			}
		}

		if ( ! empty( $errors ) && empty( $imported ) ) {
			/* translators: %s: comma-separated list of file paths that failed to import. */
			wp_send_json_error( sprintf( __( 'Failed to import: %s', 'betterdocs-pro' ), implode( ', ', $errors ) ) );
		}

		wp_send_json_success( [
			'imported' => count( $imported ),
			'errors'   => count( $errors ),
			'post_ids' => $imported,
		] );
	}

	// ── End of new AJAX actions ─────────────────────────────────────────────

	protected function process_images_for_github( $markdown, $post ) {
		// Convert relative image URLs to absolute URLs
		$site_url = get_site_url();

		// Find all image references in Markdown
		$markdown = preg_replace_callback(
			'/!\[([^\]]*)\]\(([^)]+)\)/',
			function( $matches ) use ( $site_url ) {
				$alt_text = $matches[1];
				$image_url = $matches[2];

				// Convert relative URLs to absolute
				if ( strpos( $image_url, 'http' ) !== 0 ) {
					if ( strpos( $image_url, '/' ) === 0 ) {
						$image_url = $site_url . $image_url;
					} else {
						$image_url = $site_url . '/' . $image_url;
					}
				}

				return "![{$alt_text}]({$image_url})";
			},
			$markdown
		);

		return $markdown;
	}

	/**
	 * Convert raw API error responses into clear, actionable messages.
	 *
	 * @param int    $status_code HTTP status code.
	 * @param string $raw_message Raw message from the API (e.g. "Not Found").
	 * @param string $provider    'github' or 'gitlab'.
	 * @return string
	 */
	protected function humanize_api_error( $status_code, $raw_message, $provider = 'github' ) {
		$label = $provider === 'gitlab' ? 'GitLab' : 'GitHub';

		switch ( $status_code ) {
			case 401:
				return sprintf(
					/* translators: %s: Git provider name (GitHub or GitLab). */
					__( 'Authentication failed — your %s access token is invalid or has expired. Please disconnect and reconnect your account in BetterDocs Settings.', 'betterdocs-pro' ),
					$label
				);

			case 403:
				return sprintf(
					/* translators: %s: Git provider name (GitHub or GitLab). */
					__( 'Permission denied — your %s token does not have the required permissions to perform this action. Please ensure it has push/write access to this repository.', 'betterdocs-pro' ),
					$label
				);

			case 404:
				return sprintf(
					/* translators: %s: Git provider name (GitHub or GitLab). */
					__( 'Repository or file not found on %s. It may have been deleted, renamed, or your access token no longer has permission to view it. Please verify the repository still exists and your connection is active.', 'betterdocs-pro' ),
					$label
				);

			case 409:
				return __( 'Conflict — the file was modified on the remote repository since your last sync. Please pull the latest changes first, then try pushing again.', 'betterdocs-pro' );

			case 422:
				return sprintf(
					/* translators: 1: Git provider name (GitHub or GitLab); 2: raw error message from the provider. */
					__( 'The request was rejected by %1$s. This usually means the branch is protected or the commit data is invalid. Details: %2$s', 'betterdocs-pro' ),
					$label,
					$raw_message
				);

			case 429:
				return sprintf(
					/* translators: %s: Git provider name (GitHub or GitLab). */
					__( 'Rate limit exceeded — too many requests to the %s API. Please wait a few minutes and try again.', 'betterdocs-pro' ),
					$label
				);

			case 500:
			case 502:
			case 503:
				return sprintf(
					/* translators: %s: Git provider name (GitHub or GitLab). */
					__( '%s is temporarily unavailable (server error). Please try again in a few minutes.', 'betterdocs-pro' ),
					$label
				);

			default:
				if ( ! empty( $raw_message ) && strtolower( $raw_message ) !== 'not found' ) {
					return sprintf(
						/* translators: 1: Git provider name (GitHub or GitLab); 2: HTTP status code; 3: raw error message. */
						__( '%1$s API error (%2$d): %3$s', 'betterdocs-pro' ),
						$label,
						$status_code,
						$raw_message
					);
				}
				return sprintf(
					/* translators: 1: Git provider name (GitHub or GitLab); 2: HTTP status code. */
					__( 'An unexpected error occurred while communicating with %1$s (HTTP %2$d). Please check your connection settings and try again.', 'betterdocs-pro' ),
					$label,
					$status_code
				);
		}
	}
}
