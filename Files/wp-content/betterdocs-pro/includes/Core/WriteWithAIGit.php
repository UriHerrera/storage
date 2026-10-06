<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPDeveloper\BetterDocs\Utils\Base;

/**
 * Bridge that powers the "From Git" tab of the free plugin's Write with AI modal.
 *
 * Write with AI lives in the free plugin, but Git access (OAuth token + API) is
 * Pro-only. The free REST endpoint delegates to Pro via two filters:
 *
 *  - `betterdocs_write_with_ai_git`       → report Pro/Git status to the modal.
 *  - `betterdocs_write_with_ai_git_fetch` → fetch content from a pasted Git URL.
 *
 * Unlike GitIntegration's sync helpers (which operate on the *configured* repo),
 * this parses an arbitrary pasted URL (a pull request or a file in any repo the
 * connected account can read) and calls the provider API directly.
 *
 * @package WPDeveloper\BetterDocsPro\Core
 */
class WriteWithAIGit extends Base {

	/**
	 * @var Settings
	 */
	protected $settings;

	/** Keep the assembled Git text within the endpoint's source budget. */
	const MAX_PATCH_LENGTH = 3000;
	const MAX_PR_FILES     = 30;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_filter( 'betterdocs_write_with_ai_git', [ $this, 'git_status' ] );
		add_filter( 'betterdocs_write_with_ai_git_fetch', [ $this, 'fetch' ], 10, 3 );

		// "Browse repository" picker for the From Git tab: list the connected
		// account's repos, a repo's open PRs/issues, and a repo directory's files.
		add_filter( 'betterdocs_write_with_ai_git_repos', [ $this, 'list_repos' ], 10, 1 );
		add_filter( 'betterdocs_write_with_ai_git_items', [ $this, 'list_items' ], 10, 3 );
		add_filter( 'betterdocs_write_with_ai_git_contents', [ $this, 'list_contents' ], 10, 4 );
	}

	/**
	 * Report Git status to the Write with AI modal.
	 *
	 * @param array $status Default status from the free plugin.
	 * @return array
	 */
	public function git_status( $status ) {
		return array(
			'enabled'      => (bool) $this->settings->get( 'enable_git_integration', false ),
			'connected'    => GitHubOAuth::is_connected(),
			'provider'     => GitHubOAuth::get_provider(),
			// When off, the free modal hides the "From Git" tab entirely (default on).
			'ai_enabled'   => (bool) $this->settings->get( 'enable_write_with_ai_git', true ),
			'repo_label'   => (string) $this->settings->get( 'git_repository_url', '' ),
			'settings_url' => admin_url( 'admin.php?page=betterdocs-settings#git-sync' ),
		);
	}

	/**
	 * Fetch content from a pasted Git URL.
	 *
	 * @param mixed  $result  Passed-through default (null).
	 * @param string $git_url The URL pasted by the user.
	 * @param array  $context { post_id }.
	 * @return array|\WP_Error { type, title, content, source_label } or WP_Error.
	 */
	public function fetch( $result, $git_url, $context = array() ) {
		if ( ! GitHubOAuth::is_connected() ) {
			return new \WP_Error( 'git_not_connected', __( 'Git Sync is not connected. Connect an account in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}

		$git_url  = trim( (string) $git_url );

		// Drop any query string / fragment before matching. GitHub's line-linked file
		// view appends ?plain=1#L1-L10, which the (.+)$ path captures would otherwise
		// swallow into the API path and produce a spurious 404. PR/issue/commit numbers
		// and file paths never need a query or fragment, so this is safe for every branch.
		$git_url  = preg_replace( '/[?#].*$/', '', $git_url );

		$token    = GitHubOAuth::get_valid_token();
		$provider = GitHubOAuth::get_provider();

		// GitHub pull request: .../owner/repo/pull/123
		if ( preg_match( '#github\.com/([^/]+)/([^/]+)/pull/(\d+)#', $git_url, $m ) ) {
			return $this->fetch_github_pr( $m[1], $m[2], (int) $m[3], $token );
		}

		// GitHub issue: .../owner/repo/issues/123
		if ( preg_match( '#github\.com/([^/]+)/([^/]+)/issues/(\d+)#', $git_url, $m ) ) {
			return $this->fetch_github_issue( $m[1], $m[2], (int) $m[3], $token );
		}

		// GitHub commit: .../owner/repo/commit/<sha>
		if ( preg_match( '#github\.com/([^/]+)/([^/]+)/commit/([0-9a-fA-F]+)#', $git_url, $m ) ) {
			return $this->fetch_github_commit( $m[1], $m[2], $m[3], $token );
		}

		// GitHub file (blob view): .../owner/repo/blob/<ref>/<path>
		if ( preg_match( '#github\.com/([^/]+)/([^/]+)/blob/([^/]+)/(.+)$#', $git_url, $m ) ) {
			return $this->fetch_github_file( $m[1], $m[2], $m[3], $m[4], $token );
		}

		// GitHub raw content host: raw.githubusercontent.com/owner/repo/<ref>/<path>
		if ( preg_match( '#raw\.githubusercontent\.com/([^/]+)/([^/]+)/([^/]+)/(.+)$#', $git_url, $m ) ) {
			return $this->fetch_github_file( $m[1], $m[2], $m[3], $m[4], $token );
		}

		// GitLab file (blob view): .../-/blob/<ref>/<path> — fetch the raw form.
		if ( 'gitlab' === $provider && preg_match( '#(https?://[^/]+/.+?)/-/blob/([^/]+)/(.+)$#', $git_url, $m ) ) {
			$raw = $m[1] . '/-/raw/' . $m[2] . '/' . $m[3];
			return $this->fetch_gitlab_raw( $raw, basename( $m[3] ), $token );
		}

		// GitLab merge request: .../<group>/<project>/-/merge_requests/123
		if ( 'gitlab' === $provider && preg_match( '#(https?://[^/]+)/(.+?)/-/merge_requests/(\d+)#', $git_url, $m ) ) {
			return $this->fetch_gitlab_mr( $m[1], $m[2], (int) $m[3], $token );
		}

		return new \WP_Error( 'git_unsupported_url', __( 'That link is not supported yet. Paste a GitHub pull request, issue, or commit URL, a GitLab merge request URL, or a GitHub/GitLab file URL.', 'betterdocs-pro' ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// "Browse repository" picker — powers the From Git tab's repo → PR/Issue/File
	// selection. The picker only builds a github.com URL client-side; the actual
	// content fetch still flows through fetch() above. Browsing is GitHub-only
	// (GitLab connections are single-project; those users paste a URL instead).
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Shared guard for the browse endpoints: require a connected GitHub account.
	 *
	 * @return string|\WP_Error The stored token, or WP_Error when unavailable.
	 */
	protected function browse_guard() {
		if ( ! GitHubOAuth::is_connected() ) {
			return new \WP_Error( 'git_not_connected', __( 'Git Sync is not connected. Connect an account in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}
		if ( 'github' !== GitHubOAuth::get_provider() ) {
			return new \WP_Error( 'git_browse_unsupported', __( 'Repository browsing is available for GitHub connections. Paste a URL to use a GitLab source.', 'betterdocs-pro' ) );
		}
		return GitHubOAuth::get_valid_token();
	}

	/**
	 * Filter: list the connected account's repositories for the browse picker.
	 *
	 * @return array|\WP_Error [ ['value'=>'owner/repo','label'=>…,'default_branch'=>…,'private'=>bool], … ]
	 */
	public function list_repos( $result ) {
		$token = $this->browse_guard();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$repos = GitHubOAuth::list_repos();
		if ( is_wp_error( $repos ) ) {
			return $repos;
		}

		$out = array();
		foreach ( (array) $repos as $r ) {
			if ( isset( $r['full_name'] ) ) {
				$full = (string) $r['full_name'];
			} elseif ( isset( $r['owner']['login'], $r['name'] ) ) {
				$full = $r['owner']['login'] . '/' . $r['name'];
			} else {
				$full = isset( $r['name'] ) ? (string) $r['name'] : '';
			}
			if ( '' === $full ) {
				continue;
			}
			$out[] = array(
				'value'          => $full,
				'label'          => $full,
				'default_branch' => isset( $r['default_branch'] ) ? (string) $r['default_branch'] : '',
				'private'        => ! empty( $r['private'] ),
			);
		}
		return $out;
	}

	/**
	 * Filter: list a repo's open pull requests or issues (GitHub, direct API).
	 *
	 * @param mixed  $result Passed-through default (null).
	 * @param string $repo   "owner/repo".
	 * @param string $kind   "pull" | "issue".
	 * @return array|\WP_Error [ ['value'=>html_url,'label'=>'#N Title','number'=>N], … ]
	 */
	public function list_items( $result, $repo, $kind ) {
		$token = $this->browse_guard();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		if ( ! preg_match( '#^([^/\s]+)/([^/\s]+)$#', (string) $repo, $m ) ) {
			return new \WP_Error( 'git_bad_repo', __( 'Invalid repository.', 'betterdocs-pro' ) );
		}
		$endpoint = ( 'issue' === $kind ) ? 'issues' : 'pulls';
		$url = "https://api.github.com/repos/{$m[1]}/{$m[2]}/{$endpoint}?state=open&per_page=50&sort=updated&direction=desc";

		$data = $this->github_api_get( $url, $token );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$out = array();
		foreach ( (array) $data as $it ) {
			// GitHub's issues list also returns PRs; drop them when listing issues.
			if ( 'issue' === $kind && isset( $it['pull_request'] ) ) {
				continue;
			}
			if ( ! isset( $it['number'], $it['html_url'] ) ) {
				continue;
			}
			$num   = (int) $it['number'];
			$title = isset( $it['title'] ) ? (string) $it['title'] : '';
			$out[] = array(
				'value'  => (string) $it['html_url'],
				'label'  => '#' . $num . ( '' !== $title ? '  ' . $title : '' ),
				'number' => $num,
			);
		}
		return $out;
	}

	/**
	 * Filter: list a repo directory's entries (files + folders) for the file browser.
	 *
	 * @param mixed  $result Passed-through default (null).
	 * @param string $repo   "owner/repo".
	 * @param string $path   Directory path ('' = repo root).
	 * @param string $ref    Branch/ref ('' = resolve the repo's default branch).
	 * @return array|\WP_Error [ 'ref'=>…, 'path'=>…, 'items'=>[ ['name','path','type'=>'file'|'dir'] ] ]
	 */
	public function list_contents( $result, $repo, $path, $ref ) {
		$token = $this->browse_guard();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		if ( ! preg_match( '#^([^/\s]+)/([^/\s]+)$#', (string) $repo, $m ) ) {
			return new \WP_Error( 'git_bad_repo', __( 'Invalid repository.', 'betterdocs-pro' ) );
		}
		$owner = $m[1];
		$name  = $m[2];
		$path  = trim( (string) $path, '/' );
		$ref   = (string) $ref;

		// Resolve the default branch once when the caller didn't supply a ref, so
		// the browser can build a correct blob URL for whatever file is selected.
		if ( '' === $ref ) {
			$meta = $this->github_api_get( "https://api.github.com/repos/{$owner}/{$name}", $token );
			if ( is_wp_error( $meta ) ) {
				return $meta;
			}
			$ref = isset( $meta['default_branch'] ) && '' !== $meta['default_branch'] ? (string) $meta['default_branch'] : 'main';
		}

		$url  = "https://api.github.com/repos/{$owner}/{$name}/contents/" . $this->encode_path( $path ) . '?ref=' . rawurlencode( $ref );
		$data = $this->github_api_get( $url, $token );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// A file path returns a single object; the browser lists directory entries.
		if ( isset( $data['type'] ) ) {
			$data = array( $data );
		}

		$items = array();
		foreach ( (array) $data as $entry ) {
			if ( ! isset( $entry['name'], $entry['type'] ) ) {
				continue;
			}
			$items[] = array(
				'name' => (string) $entry['name'],
				'path' => isset( $entry['path'] ) ? (string) $entry['path'] : ltrim( $path . '/' . $entry['name'], '/' ),
				'type' => ( 'dir' === $entry['type'] ) ? 'dir' : 'file',
			);
		}

		// Folders first, then files — each alphabetically (case-insensitive).
		usort( $items, function ( $a, $b ) {
			if ( $a['type'] !== $b['type'] ) {
				return 'dir' === $a['type'] ? -1 : 1;
			}
			return strcasecmp( $a['name'], $b['name'] );
		} );

		return array( 'ref' => $ref, 'path' => $path, 'items' => $items );
	}

	/**
	 * Fetch a pull request (title + description + changed-file diffs) from GitHub.
	 */
	protected function fetch_github_pr( $owner, $repo, $number, $token ) {
		$base = "https://api.github.com/repos/{$owner}/{$repo}/pulls/{$number}";

		$pr = $this->github_api_get( $base, $token );
		if ( is_wp_error( $pr ) ) {
			return $pr;
		}

		$title = isset( $pr['title'] ) ? (string) $pr['title'] : sprintf( 'Pull request #%d', $number );
		$body  = isset( $pr['body'] ) ? (string) $pr['body'] : '';

		$parts   = array();
		$parts[] = '# ' . $title;
		if ( '' !== trim( $body ) ) {
			$parts[] = $body;
		}

		$files = $this->github_api_get( $base . '/files?per_page=100', $token );
		if ( ! is_wp_error( $files ) && is_array( $files ) ) {
			$parts = array_merge( $parts, $this->format_github_file_diffs( $files ) );
		}

		return array(
			'type'         => 'pr',
			'title'        => $title,
			'content'      => implode( "\n\n", $parts ),
			'source_label' => __( 'pull request', 'betterdocs-pro' ),
		);
	}

	/**
	 * Fetch an issue (title + body + discussion comments) from GitHub. The rationale
	 * for a change usually lives in the issue thread, so this is a strong doc source.
	 */
	protected function fetch_github_issue( $owner, $repo, $number, $token ) {
		$base = "https://api.github.com/repos/{$owner}/{$repo}/issues/{$number}";

		$issue = $this->github_api_get( $base, $token );
		if ( is_wp_error( $issue ) ) {
			return $issue;
		}

		$title = isset( $issue['title'] ) ? (string) $issue['title'] : sprintf( 'Issue #%d', $number );
		$body  = isset( $issue['body'] ) ? (string) $issue['body'] : '';

		$parts   = array();
		$parts[] = '# ' . $title;
		if ( '' !== trim( $body ) ) {
			$parts[] = $body;
		}

		$comments = $this->github_api_get( $base . '/comments?per_page=100', $token );
		if ( ! is_wp_error( $comments ) && is_array( $comments ) && ! empty( $comments ) ) {
			$parts[] = '## Discussion';
			$count   = 0;
			foreach ( $comments as $comment ) {
				if ( $count >= self::MAX_PR_FILES ) {
					$parts[] = sprintf( '…and %d more comment(s).', count( $comments ) - self::MAX_PR_FILES );
					break;
				}
				$user = isset( $comment['user']['login'] ) ? (string) $comment['user']['login'] : 'user';
				$text = isset( $comment['body'] ) ? (string) $comment['body'] : '';
				if ( '' === trim( $text ) ) {
					continue;
				}
				$parts[] = "**@{$user}:**\n" . $text;
				$count++;
			}
		}

		return array(
			'type'         => 'issue',
			'title'        => $title,
			'content'      => implode( "\n\n", $parts ),
			'source_label' => __( 'issue', 'betterdocs-pro' ),
		);
	}

	/**
	 * Fetch a single commit (message + changed-file diffs) from GitHub.
	 */
	protected function fetch_github_commit( $owner, $repo, $sha, $token ) {
		$url = "https://api.github.com/repos/{$owner}/{$repo}/commits/{$sha}";

		$commit = $this->github_api_get( $url, $token );
		if ( is_wp_error( $commit ) ) {
			return $commit;
		}

		$message = isset( $commit['commit']['message'] ) ? (string) $commit['commit']['message'] : '';
		$short   = substr( $sha, 0, 7 );
		$heading = sprintf( 'Commit %s', $short );

		$parts   = array();
		$parts[] = '# ' . $heading;
		if ( '' !== trim( $message ) ) {
			$parts[] = $message;
		}

		if ( ! empty( $commit['files'] ) && is_array( $commit['files'] ) ) {
			$parts = array_merge( $parts, $this->format_github_file_diffs( $commit['files'] ) );
		}

		return array(
			'type'         => 'commit',
			'title'        => $heading,
			'content'      => implode( "\n\n", $parts ),
			'source_label' => __( 'commit', 'betterdocs-pro' ),
		);
	}

	/**
	 * Guard a GitLab URL built from a pasted link before the site's stored
	 * PRIVATE-TOKEN is attached to it.
	 *
	 * The GitLab branches of fetch() take the host straight out of whatever the
	 * user pasted, so without this a link such as
	 * `https://attacker.tld/x/-/blob/main/y` would hand the site's GitLab token to
	 * `attacker.tld` (token exfiltration) and turn the site into a request proxy
	 * for arbitrary hosts (SSRF). Only the instance the site actually connected to
	 * is allowed: hosts are compared on their own, case-insensitively, so a
	 * self-hosted instance keeps working, and the request is forced onto the
	 * connected instance's scheme so a pasted `http://` link cannot downgrade an
	 * `https` instance.
	 *
	 * @param string $url URL assembled from the pasted link.
	 * @return string|\WP_Error The URL to request, or WP_Error when the host is not the connected instance.
	 */
	protected function verify_gitlab_url( $url ) {
		$url        = (string) $url;
		$oauth_user = get_option( GitHubOAuth::USER_OPTION, array() );
		// Mirrors GitIntegration: no configured instance means gitlab.com.
		$instance = ( is_array( $oauth_user ) && ! empty( $oauth_user['instance_url'] ) )
			? (string) $oauth_user['instance_url']
			: 'https://gitlab.com';

		$configured = wp_parse_url( $instance );
		if ( empty( $configured['host'] ) ) {
			// A self-hosted instance may have been saved without a scheme.
			$configured = wp_parse_url( 'https://' . ltrim( $instance, '/' ) );
		}

		$expected_host = isset( $configured['host'] ) ? strtolower( (string) $configured['host'] ) : '';
		$actual_host   = (string) wp_parse_url( $url, PHP_URL_HOST );
		$actual_host   = strtolower( $actual_host );

		if ( '' === $expected_host || '' === $actual_host || $expected_host !== $actual_host ) {
			return new \WP_Error( 'git_host_not_allowed', sprintf(
				/* translators: %s: host of the connected GitLab instance, e.g. gitlab.com. */
				__( 'That link is not on your connected GitLab instance (%s). BetterDocs only reads from the instance connected in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ),
				'' !== $expected_host ? $expected_host : 'gitlab.com'
			) );
		}

		// Never downgrade the connection: an http:// link is lifted to the connected
		// instance's scheme (https unless a plain-http instance was configured), and a
		// pasted https:// link always stays on https.
		$scheme        = ! empty( $configured['scheme'] ) ? strtolower( (string) $configured['scheme'] ) : 'https';
		$actual_scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		if ( 'https' !== $actual_scheme && $scheme !== $actual_scheme ) {
			$url = preg_replace( '#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $scheme . '://', $url, 1 );
		}

		return $url;
	}

	/**
	 * Fetch a merge request (title + description + changed-file diffs) from GitLab.
	 */
	protected function fetch_gitlab_mr( $host, $project, $iid, $token ) {
		// The host comes from the pasted URL — never send the token elsewhere.
		$host = $this->verify_gitlab_url( $host );
		if ( is_wp_error( $host ) ) {
			return $host;
		}

		$base = rtrim( $host, '/' ) . '/api/v4/projects/' . rawurlencode( $project ) . '/merge_requests/' . $iid;

		$mr = $this->gitlab_api_get( $base, $token );
		if ( is_wp_error( $mr ) ) {
			return $mr;
		}

		$title = isset( $mr['title'] ) ? (string) $mr['title'] : sprintf( 'Merge request !%d', $iid );
		$body  = isset( $mr['description'] ) ? (string) $mr['description'] : '';

		$parts   = array();
		$parts[] = '# ' . $title;
		if ( '' !== trim( $body ) ) {
			$parts[] = $body;
		}

		$changes = $this->gitlab_api_get( $base . '/changes', $token );
		if ( ! is_wp_error( $changes ) && ! empty( $changes['changes'] ) && is_array( $changes['changes'] ) ) {
			$parts[] = '## Changed files';
			$count   = 0;
			$total   = count( $changes['changes'] );
			foreach ( $changes['changes'] as $file ) {
				if ( $count >= self::MAX_PR_FILES ) {
					$parts[] = sprintf( '…and %d more file(s).', $total - self::MAX_PR_FILES );
					break;
				}
				$name = isset( $file['new_path'] ) ? (string) $file['new_path'] : ( isset( $file['old_path'] ) ? (string) $file['old_path'] : '' );
				$diff = isset( $file['diff'] ) ? (string) $file['diff'] : '';
				$block = "### {$name}";
				if ( '' !== $diff ) {
					if ( strlen( $diff ) > self::MAX_PATCH_LENGTH ) {
						$diff = substr( $diff, 0, self::MAX_PATCH_LENGTH ) . "\n… (diff truncated)";
					}
					$block .= "\n```diff\n" . $diff . "\n```";
				}
				$parts[] = $block;
				$count++;
			}
		}

		return array(
			'type'         => 'mr',
			'title'        => $title,
			'content'      => implode( "\n\n", $parts ),
			'source_label' => __( 'merge request', 'betterdocs-pro' ),
		);
	}

	/**
	 * Format a GitHub file list (from a PR or a commit) into a "## Changed files"
	 * block, one "### <name> (<status>)" section per file with its ```diff``` patch,
	 * capping the file count and each patch length to keep within the source budget.
	 *
	 * @param array $files GitHub API file entries ({ filename, status, patch }).
	 * @return array Lines to append to the content parts.
	 */
	protected function format_github_file_diffs( $files ) {
		$parts = array( '## Changed files' );
		$count = 0;
		$total = count( $files );

		foreach ( $files as $file ) {
			if ( $count >= self::MAX_PR_FILES ) {
				$parts[] = sprintf( '…and %d more file(s).', $total - self::MAX_PR_FILES );
				break;
			}
			$name   = isset( $file['filename'] ) ? (string) $file['filename'] : '';
			$status = isset( $file['status'] ) ? (string) $file['status'] : '';
			$patch  = isset( $file['patch'] ) ? (string) $file['patch'] : '';
			$block  = "### {$name} ({$status})";
			if ( '' !== $patch ) {
				if ( strlen( $patch ) > self::MAX_PATCH_LENGTH ) {
					$patch = substr( $patch, 0, self::MAX_PATCH_LENGTH ) . "\n… (diff truncated)";
				}
				$block .= "\n```diff\n" . $patch . "\n```";
			}
			$parts[] = $block;
			$count++;
		}

		return $parts;
	}

	/**
	 * Fetch a single file's content from GitHub via the contents API.
	 */
	protected function fetch_github_file( $owner, $repo, $ref, $path, $token ) {
		$path = ltrim( $path, '/' );
		$url  = "https://api.github.com/repos/{$owner}/{$repo}/contents/" . $this->encode_path( $path ) . '?ref=' . rawurlencode( $ref );

		$response = $this->github_api_get( $url, $token );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( empty( $response['content'] ) ) {
			return new \WP_Error( 'git_empty_file', __( 'That file appears to be empty or is not a text file.', 'betterdocs-pro' ) );
		}

		$content = base64_decode( str_replace( "\n", '', $response['content'] ) );

		return array(
			'type'         => 'file',
			'title'        => basename( rawurldecode( $path ) ),
			'content'      => (string) $content,
			'source_label' => __( 'documentation file', 'betterdocs-pro' ),
		);
	}

	/**
	 * Fetch a raw GitLab file URL using the stored private token.
	 */
	protected function fetch_gitlab_raw( $raw_url, $name, $token ) {
		// The host comes from the pasted URL — never send the token elsewhere.
		$raw_url = $this->verify_gitlab_url( $raw_url );
		if ( is_wp_error( $raw_url ) ) {
			return $raw_url;
		}

		$response = wp_remote_get( $raw_url, array(
			'headers' => array( 'PRIVATE-TOKEN' => $token ),
			'timeout' => 20,
		) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 400 ) {
			return new \WP_Error( 'gitlab_fetch_failed', __( 'Could not read that GitLab file. Check the link and your access.', 'betterdocs-pro' ) );
		}
		$content = wp_remote_retrieve_body( $response );
		if ( '' === trim( (string) $content ) ) {
			return new \WP_Error( 'git_empty_file', __( 'That file appears to be empty.', 'betterdocs-pro' ) );
		}

		return array(
			'type'         => 'file',
			'title'        => $name,
			'content'      => (string) $content,
			'source_label' => __( 'documentation file', 'betterdocs-pro' ),
		);
	}

	/**
	 * GET a GitHub API URL. Sends the OAuth token when connected via GitHub;
	 * public repos still work unauthenticated (rate-limited).
	 *
	 * GitHub App user-to-server tokens (the "ghu_" tokens BetterDocs stores) are
	 * short-lived and expire. An expired token returns 401 "Bad credentials" even
	 * for public content, and GitHub reuses 403 for both "forbidden" and "rate
	 * limit exceeded". So on a 401/403 from an authenticated request we retry once
	 * anonymously — a public URL should not fail just because the saved token went
	 * stale — and only surface an error (with an accurate, cause-specific message)
	 * when the anonymous retry can't read it either.
	 *
	 * @param string $url                   GitHub API URL.
	 * @param string $token                 Stored OAuth token (may be empty/expired).
	 * @param bool   $allow_anonymous_retry Internal: false on the retry to stop recursion.
	 * @return array|\WP_Error
	 */
	protected function github_api_get( $url, $token, $allow_anonymous_retry = true ) {
		$sent_token = ! empty( $token ) && 'github' === GitHubOAuth::get_provider();

		$headers = array(
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'BetterDocs-WriteWithAI/1.0',
		);
		if ( $sent_token ) {
			$headers['Authorization'] = 'token ' . $token;
		}

		$response = wp_remote_get( $url, array( 'headers' => $headers, 'timeout' => 30 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Authenticated request was rejected (expired token) or throttled — try again
		// without the token. Public repos resolve; if the anonymous read also fails we
		// fall through and report against the original authenticated response.
		if ( $sent_token && $allow_anonymous_retry && ( 401 === $code || 403 === $code ) ) {
			$anonymous = $this->github_api_get( $url, '', false );
			if ( ! is_wp_error( $anonymous ) ) {
				return $anonymous;
			}
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 404 === $code ) {
			return new \WP_Error( 'git_not_found', __( 'Nothing was found at that URL. Check the link, and confirm your connected account can access the repository.', 'betterdocs-pro' ) );
		}
		if ( 401 === $code ) {
			// Reached only after the anonymous retry above also failed, so the content
			// isn't publicly readable. It's either private (the expired token is why we
			// can't see it) or a bad URL — cover both.
			return new \WP_Error( 'git_token_expired', __( 'Couldn’t read that from GitHub. If it’s a private repository, your GitHub connection may have expired — reconnect your account in BetterDocs Settings → Git Integration and try again. If it’s public, double-check the URL.', 'betterdocs-pro' ) );
		}
		if ( 403 === $code ) {
			// GitHub returns 403 for both permission denials and rate limiting; the
			// remaining-requests header (or the message) tells the two apart.
			$remaining    = (string) wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
			$rate_limited = '0' === $remaining
				|| ( isset( $body['message'] ) && false !== stripos( (string) $body['message'], 'rate limit' ) );
			if ( $rate_limited ) {
				$msg = $sent_token
					? __( 'GitHub rate limit reached for your connected account. Please wait a few minutes and try again.', 'betterdocs-pro' )
					: __( 'GitHub rate limit reached. Connect a GitHub account in BetterDocs Settings → Git Integration to raise the limit, or wait a few minutes.', 'betterdocs-pro' );
				return new \WP_Error( 'git_rate_limited', $msg );
			}
			// "Resource not accessible by integration" is GitHub telling us the App
			// itself lacks a permission — not that this user lacks access. The two
			// need different answers, and conflating them sent people to reconnect
			// their account over and over for something reconnecting cannot fix.
			// Pull request and issue URLs are where this bites: reading them needs
			// the App's Pull requests / Issues permissions, while commit, blob and
			// raw URLs only need Contents and keep working.
			if ( isset( $body['message'] ) && false !== stripos( (string) $body['message'], 'not accessible by integration' ) ) {
				return new \WP_Error(
					'git_app_permission',
					__( 'The BetterDocs GitHub App does not have permission to read this kind of link. Pull request and issue links need the app\'s Pull requests and Issues permissions; commit, file and raw links work without them. Ask whoever administers the BetterDocs app on your GitHub organization to grant those permissions, then approve the update on the installation.', 'betterdocs-pro' )
				);
			}

			return new \WP_Error( 'git_forbidden', __( 'Your connected GitHub account cannot access that repository. Confirm the account has access, or reconnect it in BetterDocs Settings → Git Integration.', 'betterdocs-pro' ) );
		}
		if ( $code >= 400 ) {
			$msg = isset( $body['message'] ) ? (string) $body['message'] : __( 'The Git request failed. Please try again.', 'betterdocs-pro' );
			return new \WP_Error( 'git_request_failed', $msg );
		}

		return $body;
	}

	/**
	 * GET a GitLab API URL using the stored private token. Mirrors github_api_get()'s
	 * status-to-WP_Error mapping so the modal shows the same messages.
	 *
	 * @return array|\WP_Error
	 */
	protected function gitlab_api_get( $url, $token ) {
		$headers = array( 'Accept' => 'application/json' );
		if ( ! empty( $token ) ) {
			$headers['PRIVATE-TOKEN'] = $token;
		}

		$response = wp_remote_get( $url, array( 'headers' => $headers, 'timeout' => 30 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 404 === $code ) {
			return new \WP_Error( 'git_not_found', __( 'Nothing was found at that URL. Check the link, and confirm your connected account can access the repository.', 'betterdocs-pro' ) );
		}
		if ( 401 === $code || 403 === $code ) {
			return new \WP_Error( 'git_forbidden', __( 'Your connected Git account cannot access that repository.', 'betterdocs-pro' ) );
		}
		if ( $code >= 400 ) {
			$msg = isset( $body['message'] ) ? ( is_array( $body['message'] ) ? implode( ' ', $body['message'] ) : (string) $body['message'] ) : __( 'The Git request failed. Please try again.', 'betterdocs-pro' );
			return new \WP_Error( 'git_request_failed', $msg );
		}

		return $body;
	}

	/**
	 * URL-encode each path segment while keeping the slashes.
	 *
	 * Idempotent: the path captured from a pasted URL may already be
	 * percent-encoded (e.g. a space arrives as "%20"). Decoding each segment
	 * before re-encoding prevents double-encoding ("%20" → "%2520"), which
	 * GitHub's contents API treats as a filename mismatch (404 / git_not_found).
	 */
	protected function encode_path( $path ) {
		return implode( '/', array_map(
			function ( $seg ) {
				return rawurlencode( rawurldecode( $seg ) );
			},
			explode( '/', $path )
		) );
	}
}
