<?php
/**
 * Delete API reference ability.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;
use WPDeveloper\BetterDocsPro\Abilities\Traits\TalksToApiDocs;

/**
 * Delete an API reference and its stored spec. Docs already materialized from it
 * are left in place — deleting the reference is not a way to delete content an
 * agent may not have created.
 *
 * @since 4.9.1
 */
class DeleteApiReference extends ProAbility {

	use TalksToApiDocs;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/delete-api-reference';
	}

	/**
	 * @since 4.9.1
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$blocked = $this->api_blocked();

		if ( null !== $blocked ) {
			return $blocked;
		}

		$id = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $id <= 0 ) {
			return AbilityError::invalid_input( 'id', __( 'Give the API reference id.', 'betterdocs-pro' ) );
		}

		// Read the title first, both to confirm the reference exists (a clean
		// not_found rather than a silent success) and to name it in the result.
		$row = $this->api_call( 'GET', '/api-ref/' . $id, [], $id );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$title = isset( $row['title'] ) ? (string) $row['title'] : '';

		$deleted = $this->api_call( 'DELETE', '/api-ref/' . $id, [], $id );

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		return [
			'id'      => $id,
			'title'   => $title,
			'deleted' => true
		];
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'delete an API reference', 'betterdocs-pro' );
	}
}
