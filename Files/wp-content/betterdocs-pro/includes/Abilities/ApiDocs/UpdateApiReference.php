<?php
/**
 * Update API reference ability.
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
 * Update an API reference: rename it, change its slug or status, or adjust the
 * display settings (Try-it panel and label, code-sample theme). Only the fields
 * given are changed; everything else is left as it was.
 *
 * @since 4.9.1
 */
class UpdateApiReference extends ProAbility {

	use TalksToApiDocs;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/update-api-reference';
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

		$body = [];

		if ( isset( $input['title'] ) ) {
			$body['title'] = (string) $input['title'];
		}

		if ( isset( $input['slug'] ) ) {
			$body['slug'] = (string) $input['slug'];
		}

		if ( isset( $input['status'] ) && in_array( $input['status'], [ 'draft', 'publish' ], true ) ) {
			$body['status'] = (string) $input['status'];
		}

		if ( array_key_exists( 'tryit_enabled', $input ) ) {
			// apply_settings stores the raw meta; the render reads '0' as off.
			$body['tryit_enabled'] = $input['tryit_enabled'] ? '1' : '0';
		}

		if ( isset( $input['tryit_label'] ) ) {
			$body['tryit_label'] = (string) $input['tryit_label'];
		}

		if ( isset( $input['code_theme'] ) && in_array( $input['code_theme'], [ 'light', 'dark' ], true ) ) {
			$body['code_theme'] = (string) $input['code_theme'];
		}

		if ( empty( $body ) ) {
			return AbilityError::invalid_input( 'settings', __( 'Give at least one field to change.', 'betterdocs-pro' ) );
		}

		$row = $this->api_call( 'POST', '/api-ref/' . $id, $body, $id );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		return $this->shape_reference( (array) $row );
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'update an API reference', 'betterdocs-pro' );
	}
}
