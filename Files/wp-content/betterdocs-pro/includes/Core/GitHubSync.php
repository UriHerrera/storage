<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPDeveloper\BetterDocs\Utils\Base;

/**
 * GitHub Sync Button — mounts a React-based Git Sync app (toolbar button +
 * modal dialog) into the Gutenberg block editor for docs.
 *
 * @package WPDeveloper\BetterDocs\Core
 * @since 4.1.3
 */
class GitHubSync extends Base {

	/**
	 * @var Settings
	 */
	protected $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		$post_id   = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0; // phpcs:ignore
		$post_type = '';

		if ( ! empty( $_GET['post_type'] ) ) { // phpcs:ignore
			$post_type = sanitize_key( $_GET['post_type'] ); // phpcs:ignore
		} elseif ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
		}

		if ( $post_type === 'docs' && $this->is_enabled() ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
			add_action( 'admin_footer', [ $this, 'render_mount_point' ] );
		}
	}

	protected function is_enabled() {
		return (bool) $this->settings->get( 'enable_git_integration', false );
	}

	/**
	 * Enqueue SweetAlert2 + React git-sync modal bundle + localized config.
	 */
	public function enqueue_editor_scripts( $hook ) {
		if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
			return;
		}

		betterdocs_pro()->assets->enqueue( 'betterdocs-sweetalert', 'vendor/js/sweetalert2.min.js' );

		betterdocs_pro()->assets->enqueue(
			'betterdocs-git-sync-modal',
			'admin/css/git-sync-modal.css'
		);
		betterdocs_pro()->assets->enqueue(
			'betterdocs-git-sync-modal',
			'admin/js/git-sync-modal.js',
			[ 'wp-element', 'wp-i18n', 'wp-data', 'betterdocs-sweetalert' ]
		);
		betterdocs_pro()->assets->localize(
			'betterdocs-git-sync-modal',
			'bdGitSync',
			$this->get_config()
		);
	}

	/**
	 * Gather all configuration the React app needs (post context, nonces, i18n).
	 */
	protected function get_config() {
		$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0; // phpcs:ignore
		$post    = $post_id ? get_post( $post_id ) : null;

		$repo_url     = $this->settings->get( 'git_repository_url', '' );
		$branch       = $this->settings->get( 'git_branch', 'main' );
		$options      = get_option( 'betterdocs_settings', [] );
		$docs_dir     = array_key_exists( 'git_docs_directory', $options ) ? (string) $options['git_docs_directory'] : 'docs';
		$file_naming  = $this->settings->get( 'git_file_naming', 'slug' );
		$last_sync    = $post_id ? get_post_meta( $post_id, '_betterdocs_git_last_sync', true ) : '';
		$commit_hash  = $post_id ? get_post_meta( $post_id, '_betterdocs_git_commit_hash', true ) : '';
		$sync_status  = $post_id ? get_post_meta( $post_id, '_betterdocs_git_sync_status', true ) : '';
		$is_connected = GitHubOAuth::is_connected();
		$oauth_user   = get_option( GitHubOAuth::USER_OPTION, null );
		$provider     = GitHubOAuth::get_provider();

		// Parse repo name for display.
		$repo_label = $repo_url;
		if ( $provider === 'gitlab' && ! empty( $oauth_user['project_name'] ) ) {
			$repo_label = $oauth_user['project_name'];
		} elseif ( $repo_url && preg_match( '#github\.com[:/]([^/]+/[^/\.]+)#', $repo_url, $m ) ) {
			$repo_label = $m[1];
		} elseif ( $repo_url ) {
			$repo_label = basename( rtrim( $repo_url, '/' ) );
		}

		// Determine file path.
		$filename = '';
		if ( $post ) {
			switch ( $file_naming ) {
				case 'id':
					$filename = $post_id . '.md';
					break;
				case 'title':
					$title    = sanitize_file_name( $post->post_title );
					$filename = ( ! empty( $title ) ? $title : 'doc-' . $post_id ) . '.md';
					break;
				default:
					$slug = $post->post_name;
					if ( empty( $slug ) ) {
						$slug = sanitize_title( $post->post_title );
					}
					if ( empty( $slug ) ) {
						$slug = 'doc-' . $post_id;
					}
					$filename = $slug . '.md';
			}
		}
		$dir_trim  = trim( (string) $docs_dir, '/' );
		$file_path = $filename ? ( $dir_trim === '' ? $filename : trailingslashit( $dir_trim ) . $filename ) : '';

		// Human-readable last sync.
		$last_sync_human = '';
		$short_hash      = '';
		if ( $last_sync ) {
			$diff            = human_time_diff( strtotime( $last_sync ), time() );
			/* translators: %s: human-readable time difference (e.g. "5 minutes"). */
			$last_sync_human = sprintf( __( '%s ago', 'betterdocs-pro' ), $diff );
		}
		if ( $commit_hash ) {
			$short_hash = substr( $commit_hash, 0, 7 );
		}

		$current_user        = wp_get_current_user();
		$current_user_login  = $current_user && $current_user->user_login ? $current_user->user_login : '';
		$current_user_name   = $current_user && $current_user->display_name ? $current_user->display_name : $current_user_login;
		$current_user_avatar = $current_user && $current_user->ID ? get_avatar_url( $current_user->ID, [ 'size' => 40 ] ) : '';

		$provider_label = $provider === 'gitlab' ? 'GitLab' : 'GitHub';
		$sync_label     = $provider_label . ' Sync';
		$push_label     = __( 'Push to', 'betterdocs-pro' ) . ' ' . $provider_label;
		$pull_label     = __( 'Pull from', 'betterdocs-pro' ) . ' ' . $provider_label;
		$up_to_date     = __( 'Up to date with', 'betterdocs-pro' ) . ' ' . $provider_label;

		return [
			'postId'        => $post_id,
			'provider'      => $provider,
			'repoLabel'     => $repo_label,
			'branch'        => $branch,
			'filePath'      => $file_path,
			'docsDir'       => $dir_trim,
			'fileNaming'    => $file_naming,
			'lastSyncHuman' => $last_sync_human,
			'shortHash'     => $short_hash,
			'syncStatus'    => $sync_status,
			'isConnected'   => (bool) $is_connected,
			'hasRepo'       => ! empty( $repo_url ),
			'settingsUrl'   => admin_url( 'admin.php?page=betterdocs-settings#git-sync' ),
			'editDocsUrl'   => admin_url( 'edit.php?post_type=docs' ),
			'user'          => [
				'login'  => $current_user_login,
				'name'   => $current_user_name,
				'avatar' => $current_user_avatar,
			],
			'nonces'        => [
				'sync'        => wp_create_nonce( 'betterdocs_git_sync_nonce' ),
				'pull'        => wp_create_nonce( 'betterdocs_git_pull_nonce' ),
				'diff'        => wp_create_nonce( 'betterdocs_github_diff_nonce' ),
				'fetchStatus' => wp_create_nonce( 'betterdocs_git_fetch_status_nonce' ),
				'import'      => wp_create_nonce( 'betterdocs_github_import_nonce' ),
				'listMd'      => wp_create_nonce( 'betterdocs_github_list_md_nonce' ),
				'history'     => wp_create_nonce( 'betterdocs_git_commit_history_nonce' ),
				'oauth'       => wp_create_nonce( 'betterdocs_github_oauth_nonce' ),
			],
			'i18n'          => [
				'githubSync'        => $sync_label,
				'pushToGitHub'      => $push_label,
				'pullFromGitHub'    => $pull_label,
				'pushing'           => __( 'Pushing…', 'betterdocs-pro' ),
				'fetching'          => __( 'Fetching…', 'betterdocs-pro' ),
				'pushed'            => __( 'Pushed!', 'betterdocs-pro' ),
				'pulled'            => __( 'Pulled! Reloading…', 'betterdocs-pro' ),
				'upToDate'          => $up_to_date,
				'commitPlaceholder' => __( 'Commit message (required)', 'betterdocs-pro' ),
				'commitRequired'    => __( 'Commit message is required', 'betterdocs-pro' ),
				'lastSynced'        => __( 'Last synced', 'betterdocs-pro' ),
				'notSyncedYet'      => __( 'Not synced yet', 'betterdocs-pro' ),
				'saveFirst'         => __( 'Save the document first to enable sync', 'betterdocs-pro' ),
				/* translators: %s: Git provider name (e.g. GitHub or GitLab). */
				'connecting'        => sprintf( __( 'Connect %s in Settings', 'betterdocs-pro' ), $provider_label ),
				'noRepo'            => __( 'Configure repository in Settings', 'betterdocs-pro' ),
				'importMdFiles'     => __( 'Import MD Files', 'betterdocs-pro' ),
				'importLoading'     => __( 'Loading files…', 'betterdocs-pro' ),
				'importSelected'    => __( 'Import Selected', 'betterdocs-pro' ),
				'importing'         => __( 'Importing…', 'betterdocs-pro' ),
				'importDone'        => __( 'Imported!', 'betterdocs-pro' ),
				'checkingDiff'      => __( 'Checking diff…', 'betterdocs-pro' ),
				'noDiff'            => __( 'No local changes', 'betterdocs-pro' ),
				'fetch'             => __( 'Fetch', 'betterdocs-pro' ),
				'fetchingRemote'    => __( 'Fetching…', 'betterdocs-pro' ),
				'lastFetch'         => __( 'Last Fetch:', 'betterdocs-pro' ),
				'fetchNever'        => __( 'Not fetched yet', 'betterdocs-pro' ),
				'secondsAgo'        => __( 'just now', 'betterdocs-pro' ),
				'summaryPlaceholder'=> __( 'Summary (required)', 'betterdocs-pro' ),
				'descPlaceholder'   => __( 'Description (optional)', 'betterdocs-pro' ),
				'summaryRequired'   => __( 'Summary is required', 'betterdocs-pro' ),
				/* translators: %s: Git provider name (e.g. GitHub or GitLab). */
				'behindByOne'       => __( '%s is 1 commit behind', 'betterdocs-pro' ),
				/* translators: 1: Git provider name; 2: number of commits behind. */
				'behindByMany'      => __( '%1$s is %2$d commits behind', 'betterdocs-pro' ),
				/* translators: %s: Git provider name (e.g. GitHub or GitLab). */
				'upToDateWith'      => __( 'Up to date with %s', 'betterdocs-pro' ),
				'remoteMissing'     => __( 'File not yet on remote', 'betterdocs-pro' ),
				'latestOnRemote'    => __( 'Latest on remote', 'betterdocs-pro' ),
				'overwriteTitle'    => __( 'Overwrite existing docs?', 'betterdocs-pro' ),
				/* translators: %d: number of files that already exist as docs. */
				'overwriteBody'     => __( '%d file(s) already exist as docs and will be overwritten. Continue?', 'betterdocs-pro' ),
				'confirmLabel'      => __( 'Continue', 'betterdocs-pro' ),
				'cancelLabel'       => __( 'Cancel', 'betterdocs-pro' ),
				'modalTitle'        => $sync_label,
				'tabPush'           => __( 'Push', 'betterdocs-pro' ),
				'tabPull'           => __( 'Pull', 'betterdocs-pro' ),
				'tabHistory'        => __( 'History', 'betterdocs-pro' ),
				'close'             => __( 'Close', 'betterdocs-pro' ),
				'fetchOrigin'       => __( 'Fetch origin', 'betterdocs-pro' ),
				'lastFetchedPrefix' => __( 'Last fetched', 'betterdocs-pro' ),
				'fetchNeverShort'   => __( 'never', 'betterdocs-pro' ),
				'justNow'           => __( 'just now', 'betterdocs-pro' ),
				'changedFileOne'    => __( '1 changed file', 'betterdocs-pro' ),
				/* translators: %d: number of changed files. */
				'changedFileMany'   => __( '%d changed files', 'betterdocs-pro' ),
				'commitAndPushTo'   => __( 'Commit & push to', 'betterdocs-pro' ),
				'pullFrom'          => __( 'Pull from', 'betterdocs-pro' ),
				'nothingToPush'     => __( 'Nothing to push', 'betterdocs-pro' ),
				'upToDateTitle'     => __( 'Everything up to date', 'betterdocs-pro' ),
				'upToDateDesc'      => __( 'Your branch is in sync with', 'betterdocs-pro' ),
				'makeChangesHint'   => __( 'Make changes in the editor to see them here.', 'betterdocs-pro' ),
				'committingAs'      => __( 'Committing as', 'betterdocs-pro' ),
				'diffStatsOne'      => __( '1 file', 'betterdocs-pro' ),
				/* translators: %d: number of files. */
				'diffStatsMany'     => __( '%d files', 'betterdocs-pro' ),
				'additions'         => __( 'additions', 'betterdocs-pro' ),
				'deletions'         => __( 'deletions', 'betterdocs-pro' ),
				'pullHint'          => __( 'Fetch to see if new commits are waiting on the remote.', 'betterdocs-pro' ),
				'pullNone'          => __( 'No incoming commits', 'betterdocs-pro' ),
				'pullNoneDesc'      => __( 'Your local docs match the latest commit on', 'betterdocs-pro' ),
				'pullIncomingOne'   => __( '1 incoming commit from', 'betterdocs-pro' ),
				/* translators: %d: number of incoming commits. */
				'pullIncomingMany'  => __( '%1$d incoming commits from', 'betterdocs-pro' ),
				'historyLoading'    => __( 'Loading commit history…', 'betterdocs-pro' ),
				'historyEmpty'      => __( 'No commits yet', 'betterdocs-pro' ),
				'historyEmptyDesc'  => __( 'This file has not been pushed to the remote repository.', 'betterdocs-pro' ),
				'fetchedAt'         => __( 'Fetched', 'betterdocs-pro' ),
			],
		];
	}

	/**
	 * Print the React mount point in admin_footer.
	 */
	public function render_mount_point() {
		echo '<div id="bd-git-sync-root"></div>';
	}
}
