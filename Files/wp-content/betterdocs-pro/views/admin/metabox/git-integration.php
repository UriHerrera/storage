<?php
/**
 * Git Integration Metabox Template
 *
 * @package BetterDocs
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Local template-scope variables (templates share scope via the loader); not true globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$post_id = get_the_ID();
$settings = betterdocs()->settings;

// Get current meta values
$git_sync_enabled = get_post_meta( $post_id, '_betterdocs_git_sync_enabled', true );
$git_file_path    = get_post_meta( $post_id, '_betterdocs_git_file_path', true );
$git_last_sync    = get_post_meta( $post_id, '_betterdocs_git_last_sync', true );
$git_sync_status  = get_post_meta( $post_id, '_betterdocs_git_sync_status', true );
$git_commit_hash  = get_post_meta( $post_id, '_betterdocs_git_commit_hash', true );

// Get Git settings
$git_provider       = $settings->get( 'git_provider', 'github' );
$git_repository_url = $settings->get( 'git_repository_url', '' );
$git_branch         = $settings->get( 'git_branch', 'main' );
$git_docs_directory = $settings->get( 'git_docs_directory', '' );
$git_file_naming    = $settings->get( 'git_file_naming', 'slug' );
$git_auto_sync      = $settings->get( 'git_auto_sync', false );

// Check if token is connected (OAuth or legacy PAT)
$is_connected = ! empty( \WPDeveloper\BetterDocsPro\Core\GitHubOAuth::get_token() );
$is_configured = ! empty( $git_repository_url ) && $is_connected;

// Build the correct repository URL based on provider
$oauth_user = get_option( \WPDeveloper\BetterDocsPro\Core\GitHubOAuth::USER_OPTION, [] );
if ( $git_provider === 'gitlab' ) {
	$gitlab_base = $oauth_user['instance_url'] ?? 'https://gitlab.com';
	$git_repo_link = $oauth_user['project_url'] ?? ( rtrim( $gitlab_base, '/' ) . '/projects/' . $git_repository_url );
} else {
	$git_repo_link = 'https://github.com/' . $git_repository_url;
}

// Generate file path if not set
if ( empty( $git_file_path ) && ! empty( $git_docs_directory ) ) {
	$post = get_post( $post_id );
	switch ( $git_file_naming ) {
		case 'id':
			$filename = $post_id . '.md';
			break;
		case 'title':
			$title = sanitize_file_name( $post->post_title );
			if ( empty( $title ) ) {
				$title = 'doc-' . $post_id;
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
				$slug = 'doc-' . $post_id;
			}
			$filename = $slug . '.md';
			break;
	}
	$git_file_path = trailingslashit( $git_docs_directory ) . $filename;
}

// Determine sync status color and text
$status_class = 'status-pending';
$status_text  = __( 'Pending', 'betterdocs-pro' );

switch ( $git_sync_status ) {
	case 'synced':
		$status_class = 'status-synced';
		$status_text  = __( 'Synced', 'betterdocs-pro' );
		break;
	case 'error':
		$status_class = 'status-error';
		$status_text  = __( 'Error', 'betterdocs-pro' );
		break;
	case 'conflict':
		$status_class = 'status-conflict';
		$status_text  = __( 'Conflict', 'betterdocs-pro' );
		break;
	case 'syncing':
		$status_class = 'status-syncing';
		$status_text  = __( 'Syncing...', 'betterdocs-pro' );
		break;
}

// Add nonce field
wp_nonce_field( 'betterdocs_git_integration_metabox', 'betterdocs_git_integration_nonce' );

$settings_url = esc_url( admin_url( 'admin.php?page=betterdocs-settings#tab-github-integration' ) );
?>

<div class="git-integration-metabox-wrapper">
	<style>
		.git-integration-metabox-wrapper { padding: 10px 0; }
		.git-status-indicator { display:inline-block;padding:4px 8px;border-radius:3px;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:10px; }
		.status-pending  { background:#f0f0f1;color:#646970; }
		.status-synced   { background:#d1e7dd;color:#0f5132; }
		.status-error    { background:#f8d7da;color:#721c24; }
		.status-conflict { background:#fff3cd;color:#856404; }
		.status-syncing  { background:#cff4fc;color:#055160; }
		.git-field-group { margin-bottom:15px; }
		.git-field-group label { display:block;font-weight:600;margin-bottom:5px; }
		.git-field-group input[type="text"],
		.git-field-group input[type="url"] { width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:3px; }
		.git-field-group input[type="checkbox"] { margin-right:8px; }
		.git-actions { margin-top:15px;padding-top:15px;border-top:1px solid #ddd; }
		.git-actions .button { margin-right:10px;margin-bottom:5px; }
		.git-info { background:#f9f9f9;padding:10px;border-radius:3px;margin-bottom:15px;font-size:12px; }
		.git-info strong { display:block;margin-bottom:5px; }
		.git-repository-link { word-break:break-all;color:#0073aa;text-decoration:none; }
		.git-repository-link:hover { text-decoration:underline; }
		.git-last-sync { color:#666;font-size:11px; }
		.git-not-configured { background:#fff3cd;border:1px solid #f0d484;padding:10px;border-radius:3px;margin-bottom:15px;font-size:12px; }
	</style>

	<?php if ( $is_connected && ! empty( $git_repository_url ) ) : ?>
		<div class="git-info">
			<strong><?php esc_html_e( 'Repository:', 'betterdocs-pro' ); ?></strong>
			<a href="<?php echo esc_url( $git_repo_link ); ?>" target="_blank" class="git-repository-link">
				<?php echo esc_html( $git_repository_url ); ?>
			</a>
			<br>
			<strong><?php esc_html_e( 'Branch:', 'betterdocs-pro' ); ?></strong> <?php echo esc_html( $git_branch ); ?>
		</div>
	<?php elseif ( ! $is_configured ) : ?>
		<div class="git-not-configured">
			<?php
			echo wp_kses_post( sprintf(
				/* translators: %s: URL to the Git settings page. */
				__( '⚠️ Git is not fully configured. <a href="%s">Configure Git settings</a> to enable sync.', 'betterdocs-pro' ),
				esc_url( $settings_url )
			) );
			?>
		</div>
	<?php endif; ?>

	<div class="git-field-group">
		<span class="git-status-indicator <?php echo esc_attr( $status_class ); ?>">
			<?php echo esc_html( $status_text ); ?>
		</span>
		<?php if ( ! empty( $git_last_sync ) ) : ?>
			<div class="git-last-sync">
				<?php
				printf(
					/* translators: %s: formatted date and time of the last sync. */
					esc_html__( 'Last sync: %s', 'betterdocs-pro' ),
					esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $git_last_sync ) ) )
				);
				?>
			</div>
		<?php endif; ?>
	</div>

	<div class="git-field-group">
		<label>
			<input type="checkbox" name="git_sync_enabled" value="1" <?php checked( $git_sync_enabled, '1' ); ?>>
			<?php esc_html_e( 'Enable Git sync for this document', 'betterdocs-pro' ); ?>
		</label>
	</div>

	<div class="git-field-group">
		<label for="git_file_path"><?php esc_html_e( 'File Path in Repository:', 'betterdocs-pro' ); ?></label>
		<input type="text" id="git_file_path" name="git_file_path" value="<?php echo esc_attr( $git_file_path ); ?>" placeholder="docs/example.md">
		<p class="description"><?php esc_html_e( 'Path where this document will be stored in the Git repository', 'betterdocs-pro' ); ?></p>
	</div>

	<?php if ( ! empty( $git_commit_hash ) ) : ?>
		<div class="git-field-group">
			<label><?php esc_html_e( 'Last Commit:', 'betterdocs-pro' ); ?></label>
			<code><?php echo esc_html( substr( $git_commit_hash, 0, 8 ) ); ?></code>
			<input type="hidden" name="git_commit_hash" value="<?php echo esc_attr( $git_commit_hash ); ?>">
		</div>
	<?php endif; ?>

	<div class="git-actions">
		<button type="button" class="button button-primary" id="git-smart-sync" <?php disabled( ! $is_configured ); ?>>
			<?php esc_html_e( 'Smart Sync', 'betterdocs-pro' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="git-sync-now" <?php disabled( ! $is_configured ); ?>>
			<?php esc_html_e( 'Push to Git', 'betterdocs-pro' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="git-pull-changes" <?php disabled( ! $is_configured ); ?>>
			<?php esc_html_e( 'Pull from Git', 'betterdocs-pro' ); ?>
		</button>
		<button type="button" class="button button-secondary" id="git-view-diff" <?php disabled( ! $is_configured ); ?>>
			<?php esc_html_e( 'View Diff', 'betterdocs-pro' ); ?>
		</button>
	</div>

	<div class="git-help" style="margin-top:10px;font-size:12px;color:#666;">
		<strong><?php esc_html_e( 'Smart Sync:', 'betterdocs-pro' ); ?></strong> <?php esc_html_e( 'Automatically pushes to Git if file doesn\'t exist, or pulls if it does.', 'betterdocs-pro' ); ?><br>
		<strong><?php esc_html_e( 'Push to Git:', 'betterdocs-pro' ); ?></strong> <?php esc_html_e( 'Sends current WordPress content to Git repository.', 'betterdocs-pro' ); ?><br>
		<strong><?php esc_html_e( 'Pull from Git:', 'betterdocs-pro' ); ?></strong> <?php esc_html_e( 'Gets latest content from Git repository.', 'betterdocs-pro' ); ?>
	</div>

	<!-- Hidden fields for AJAX updates -->
	<input type="hidden" name="git_last_sync" value="<?php echo esc_attr( $git_last_sync ); ?>">
	<input type="hidden" name="git_sync_status" value="<?php echo esc_attr( $git_sync_status ); ?>">
</div>

<script>
(function($) {
	'use strict';

	// ── Swal helper matching betterdocs-bulk-action-popup pattern ──────

	var swalPopup = {
		customClass: { popup: 'betterdocs-bulk-action-popup' }
	};

	function swalSuccess( msg ) {
		Swal.fire( Object.assign( {}, swalPopup, {
			text: msg,
			icon: 'success',
			showConfirmButton: false,
			timer: 2000
		} ) );
	}

	function swalError( msg ) {
		Swal.fire( Object.assign( {}, swalPopup, {
			text: msg,
			icon: 'error',
			showConfirmButton: true,
			confirmButtonText: '<?php echo esc_js( __( 'OK', 'betterdocs-pro' ) ); ?>'
		} ) );
	}

	function swalWarning( msg ) {
		Swal.fire( Object.assign( {}, swalPopup, {
			text: msg,
			icon: 'warning',
			showConfirmButton: false,
			timer: 2000
		} ) );
	}

	function swalIncomplete() {
		Swal.fire( Object.assign( {}, swalPopup, {
			icon: 'warning',
			title: '<?php echo esc_js( __( 'Git Not Configured', 'betterdocs-pro' ) ); ?>',
			html: '<?php
				echo wp_kses_post( sprintf(
					/* translators: %s: URL to the Git settings page. */
					__( 'Git configuration is incomplete. Please <a href="%s">connect your account and configure the repository</a> first.', 'betterdocs-pro' ),
					esc_js( $settings_url )
				) );
			?>',
			showConfirmButton: true,
			confirmButtonText: '<?php echo esc_js( __( 'Go to Settings', 'betterdocs-pro' ) ); ?>',
			showCancelButton: true,
			cancelButtonText: '<?php echo esc_js( __( 'Cancel', 'betterdocs-pro' ) ); ?>'
		} ) ).then( function( result ) {
			if ( result.isConfirmed ) {
				window.location.href = '<?php echo esc_js( $settings_url ); ?>';
			}
		} );
	}

	function isIncompleteError( msg ) {
		return msg && (
			msg.indexOf( 'incomplete' ) !== -1 ||
			msg.indexOf( 'connect your' ) !== -1 ||
			msg.indexOf( 'configure the repository' ) !== -1
		);
	}

	function handleError( msg ) {
		if ( isIncompleteError( msg ) ) {
			swalIncomplete();
		} else {
			swalError( msg || '<?php echo esc_js( __( 'Unknown error.', 'betterdocs-pro' ) ); ?>' );
		}
	}

	$(document).ready(function() {

		// ── Smart Sync ──────────────────────────────────────────────────

		$('#git-smart-sync').on('click', function() {
			var button      = $(this);
			var originalText = button.text();

			button.prop('disabled', true).text('<?php echo esc_js( __( 'Checking…', 'betterdocs-pro' ) ); ?>');

			// First try to pull — if file doesn't exist, it will suggest pushing
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action:  'betterdocs_git_pull_document',
					post_id: <?php echo intval( $post_id ); ?>,
					nonce:   '<?php echo esc_attr( wp_create_nonce( 'betterdocs_git_pull_nonce' ) ); ?>'
				},
				success: function(response) {
					if ( response.success ) {
						// Pull was successful
						$('.git-status-indicator').removeClass().addClass('git-status-indicator status-synced').text('<?php echo esc_js( __( 'Synced', 'betterdocs-pro' ) ); ?>');
						swalSuccess( '<?php echo esc_js( __( 'Smart Sync: Pulled latest changes from Git repository.', 'betterdocs-pro' ) ); ?>' );
						setTimeout( function() { location.reload(); }, 2100 );
					} else {
						var msg = response.data || '';
						if ( msg.indexOf('not found in repository') !== -1 ) {
							// File doesn't exist — offer to push it
							Swal.fire( Object.assign( {}, swalPopup, {
								text:              '<?php echo esc_js( __( 'File not found in repository. Push this document to Git?', 'betterdocs-pro' ) ); ?>',
								icon:              'question',
								showConfirmButton: true,
								showCancelButton:  true,
								confirmButtonText: '<?php echo esc_js( __( 'Yes, Push', 'betterdocs-pro' ) ); ?>',
								cancelButtonText:  '<?php echo esc_js( __( 'No', 'betterdocs-pro' ) ); ?>'
							} ) ).then( function( result ) {
								if ( ! result.isConfirmed ) {
									button.prop('disabled', false).text(originalText);
									return;
								}
								button.text('<?php echo esc_js( __( 'Pushing…', 'betterdocs-pro' ) ); ?>');
								$.ajax({
									url:  ajaxurl,
									type: 'POST',
									data: {
										action:  'betterdocs_git_sync_document',
										post_id: <?php echo intval( $post_id ); ?>,
										nonce:   '<?php echo esc_attr( wp_create_nonce( 'betterdocs_git_sync_nonce' ) ); ?>'
									},
									success: function(pushResponse) {
										if ( pushResponse.success ) {
											$('.git-status-indicator').removeClass().addClass('git-status-indicator status-synced').text('<?php echo esc_js( __( 'Synced', 'betterdocs-pro' ) ); ?>');
											swalSuccess( '<?php echo esc_js( __( 'Smart Sync: Document pushed to Git successfully.', 'betterdocs-pro' ) ); ?>' );
											if ( pushResponse.data && pushResponse.data.last_sync ) {
												$('input[name="git_last_sync"]').val(pushResponse.data.last_sync);
												$('.git-last-sync').html('<?php echo esc_js( __( 'Last sync:', 'betterdocs-pro' ) ); ?> ' + pushResponse.data.last_sync_formatted);
											}
										} else {
											$('.git-status-indicator').removeClass().addClass('git-status-indicator status-error').text('<?php echo esc_js( __( 'Error', 'betterdocs-pro' ) ); ?>');
											handleError( pushResponse.data );
										}
									},
									error: function() {
										$('.git-status-indicator').removeClass().addClass('git-status-indicator status-error').text('<?php echo esc_js( __( 'Error', 'betterdocs-pro' ) ); ?>');
										swalError( '<?php echo esc_js( __( 'Smart Sync failed due to network error.', 'betterdocs-pro' ) ); ?>' );
									},
									complete: function() {
										button.prop('disabled', false).text(originalText);
									}
								});
							});
						} else {
							handleError( msg );
							button.prop('disabled', false).text(originalText);
						}
					}
				},
				error: function() {
					swalError( '<?php echo esc_js( __( 'Smart Sync failed due to network error.', 'betterdocs-pro' ) ); ?>' );
					button.prop('disabled', false).text(originalText);
				}
			});
		});

		// ── Push to Git ─────────────────────────────────────────────────

		$('#git-sync-now').on('click', function() {
			var button       = $(this);
			var originalText = button.text();

			button.prop('disabled', true).text('<?php echo esc_js( __( 'Syncing…', 'betterdocs-pro' ) ); ?>');
			$('.git-status-indicator').removeClass().addClass('git-status-indicator status-syncing').text('<?php echo esc_js( __( 'Syncing…', 'betterdocs-pro' ) ); ?>');

			$.ajax({
				url:  ajaxurl,
				type: 'POST',
				data: {
					action:  'betterdocs_git_sync_document',
					post_id: <?php echo intval( $post_id ); ?>,
					nonce:   '<?php echo esc_attr( wp_create_nonce( 'betterdocs_git_sync_nonce' ) ); ?>'
				},
				success: function(response) {
					if ( response.success ) {
						if ( response.data && response.data.no_changes ) {
							swalWarning( '<?php echo esc_js( __( 'No changes to push — content is already up to date.', 'betterdocs-pro' ) ); ?>' );
						} else {
							$('.git-status-indicator').removeClass().addClass('git-status-indicator status-synced').text('<?php echo esc_js( __( 'Synced', 'betterdocs-pro' ) ); ?>');
							swalSuccess( '<?php echo esc_js( __( 'Document pushed to Git successfully.', 'betterdocs-pro' ) ); ?>' );
							if ( response.data && response.data.last_sync ) {
								$('input[name="git_last_sync"]').val(response.data.last_sync);
								$('.git-last-sync').html('<?php echo esc_js( __( 'Last sync:', 'betterdocs-pro' ) ); ?> ' + response.data.last_sync_formatted);
							}
							if ( response.data && response.data.commit_hash ) {
								$('input[name="git_commit_hash"]').val(response.data.commit_hash);
							}
						}
					} else {
						$('.git-status-indicator').removeClass().addClass('git-status-indicator status-error').text('<?php echo esc_js( __( 'Error', 'betterdocs-pro' ) ); ?>');
						handleError( response.data );
					}
				},
				error: function() {
					$('.git-status-indicator').removeClass().addClass('git-status-indicator status-error').text('<?php echo esc_js( __( 'Error', 'betterdocs-pro' ) ); ?>');
					swalError( '<?php echo esc_js( __( 'Sync failed due to network error.', 'betterdocs-pro' ) ); ?>' );
				},
				complete: function() {
					button.prop('disabled', false).text(originalText);
				}
			});
		});

		// ── Pull from Git ───────────────────────────────────────────────

		$('#git-pull-changes').on('click', function() {
			var button       = $(this);
			var originalText = button.text();

			button.prop('disabled', true).text('<?php echo esc_js( __( 'Pulling…', 'betterdocs-pro' ) ); ?>');

			$.ajax({
				url:  ajaxurl,
				type: 'POST',
				data: {
					action:  'betterdocs_git_pull_document',
					post_id: <?php echo intval( $post_id ); ?>,
					nonce:   '<?php echo esc_attr( wp_create_nonce( 'betterdocs_git_pull_nonce' ) ); ?>'
				},
				success: function(response) {
					if ( response.success ) {
						if ( response.data && response.data.no_changes ) {
							swalWarning( '<?php echo esc_js( __( 'Already up to date — no changes to pull.', 'betterdocs-pro' ) ); ?>' );
							button.prop('disabled', false).text(originalText);
						} else {
							$('.git-status-indicator').removeClass().addClass('git-status-indicator status-synced').text('<?php echo esc_js( __( 'Synced', 'betterdocs-pro' ) ); ?>');
							swalSuccess( '<?php echo esc_js( __( 'Changes pulled successfully! Reloading page…', 'betterdocs-pro' ) ); ?>' );
							setTimeout( function() { location.reload(); }, 2100 );
						}
					} else {
						var msg = response.data || '';
						if ( msg.indexOf('not found in repository') !== -1 ) {
							Swal.fire( Object.assign( {}, swalPopup, {
								text:              '<?php echo esc_js( __( 'File not found in repository. Would you like to push this document to Git instead?', 'betterdocs-pro' ) ); ?>',
								icon:              'question',
								showConfirmButton: true,
								showCancelButton:  true,
								confirmButtonText: '<?php echo esc_js( __( 'Yes, Push', 'betterdocs-pro' ) ); ?>',
								cancelButtonText:  '<?php echo esc_js( __( 'No', 'betterdocs-pro' ) ); ?>'
							} ) ).then( function( result ) {
								if ( result.isConfirmed ) {
									$('#git-sync-now').trigger('click');
								}
							} );
						} else {
							handleError( msg );
						}
					}
				},
				error: function() {
					swalError( '<?php echo esc_js( __( 'Pull failed due to network error.', 'betterdocs-pro' ) ); ?>' );
				},
				complete: function() {
					button.prop('disabled', false).text(originalText);
				}
			});
		});

		// ── View Diff ───────────────────────────────────────────────────

		$('#git-view-diff').on('click', function() {
			var postId  = <?php echo intval( $post_id ); ?>;
			var diffUrl = '<?php echo esc_js( admin_url( 'admin.php?page=betterdocs-git-diff&post_id=' ) ); ?>' + postId;
			var popup   = window.open( diffUrl, 'git-diff-' + postId,
				'width=1200,height=800,scrollbars=yes,resizable=yes,menubar=no,toolbar=no,location=no,status=no'
			);
			if ( popup ) {
				popup.focus();
			} else {
				swalError( '<?php echo esc_js( __( 'Please allow popups for this site to view the diff.', 'betterdocs-pro' ) ); ?>' );
			}
		});

	});
}(jQuery));
</script>
