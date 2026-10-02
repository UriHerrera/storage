<?php
/**
 * Create API reference ability.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocsPro\Abilities\ProAbility;
use WPDeveloper\BetterDocsPro\Abilities\Traits\TalksToApiDocs;

/**
 * Create an API reference. Title, slug and status are optional — the title is
 * adopted from the spec's `info.title` when a spec is ingested and none was set,
 * so an agent can create the empty reference first and let
 * {@see IngestApiSpec} name it.
 *
 * @since 4.9.1
 */
class CreateApiReference extends ProAbility {

	use TalksToApiDocs;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/create-api-reference';
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

		$row = $this->api_call( 'POST', '/api-ref', $body );

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
		return __( 'create an API reference', 'betterdocs-pro' );
	}
}
