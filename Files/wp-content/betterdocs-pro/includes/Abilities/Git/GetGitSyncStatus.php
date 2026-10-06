<?php
/**
 * Git sync status ability.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Git;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;
use WPDeveloper\BetterDocsPro\Core\GitIntegration;

/**
 * Report the Git integration configuration and whether it is connected —
 * provider, repository, branch, folder, file naming and auto-sync. Pass a doc id
 * to also get that doc's last sync time and status.
 *
 * Read-only: the access token is never returned, only whether one is present
 * (`connected`). Syncing itself stays a human, admin-screen action.
 *
 * @since 4.9.1
 */
class GetGitSyncStatus extends ProAbility {

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/get-git-sync-status';
	}

	/**
	 * @since 4.9.1
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$state = $this->pro_state();

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, $this->feature );
		}

		$config = betterdocs()->container->get( GitIntegration::class )->get_config();

		$out = [
			'enabled'        => (bool) betterdocs()->settings->get( 'enable_git_integration', false ),
			// The token is a secret; report only that one is present.
			'connected'      => '' !== trim( (string) ( isset( $config['access_token'] ) ? $config['access_token'] : '' ) ),
			'provider'       => isset( $config['provider'] ) ? (string) $config['provider'] : '',
			'repository_url' => isset( $config['repository_url'] ) ? (string) $config['repository_url'] : '',
			'branch'         => isset( $config['branch'] ) ? (string) $config['branch'] : '',
			'docs_directory' => isset( $config['docs_directory'] ) ? (string) $config['docs_directory'] : '',
			'file_naming'    => isset( $config['file_naming'] ) ? (string) $config['file_naming'] : '',
			'auto_sync'      => ! empty( $config['auto_sync'] ),
			'doc'            => null
		];

		if ( ! empty( $input['doc_id'] ) ) {
			$doc_id = (int) $input['doc_id'];
			$post   = get_post( $doc_id );

			if ( ! $post || 'docs' !== $post->post_type ) {
				return AbilityError::not_found( 'docs', (string) $doc_id );
			}

			$out['doc'] = [
				'id'          => $doc_id,
				'last_sync'   => (string) get_post_meta( $doc_id, '_betterdocs_git_last_sync', true ),
				'sync_status' => (string) get_post_meta( $doc_id, '_betterdocs_git_sync_status', true )
			];
		}

		return $out;
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'read the Git sync status', 'betterdocs-pro' );
	}
}
