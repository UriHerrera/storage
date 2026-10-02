<?php
/**
 * Materialize API reference ability.
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
 * Turn an ingested API spec into BetterDocs docs, one per operation. `run`
 * starts a background materialization and returns its state; `status` reports an
 * in-flight or finished run. The work continues server-side regardless, so an
 * agent starts it and polls with `status` rather than blocking on it.
 *
 * @since 4.9.1
 */
class MaterializeApiReference extends ProAbility {

	use TalksToApiDocs;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/materialize-api-reference';
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

		$action = isset( $input['action'] ) && 'status' === $input['action'] ? 'status' : 'run';

		if ( 'status' === $action ) {
			$payload = $this->api_call( 'GET', '/api-ref/' . $id . '/materialize', [], $id );
		} else {
			$payload = $this->api_call( 'POST', '/api-ref/' . $id . '/materialize', [], $id );
		}

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$status = isset( $payload['status'] ) && is_array( $payload['status'] ) ? $payload['status'] : [];

		return [
			'id'           => $id,
			'state'        => isset( $status['state'] ) ? (string) $status['state'] : 'idle',
			'materialized' => ! empty( $payload['materialized'] ),
			'progress'     => isset( $status['progress'] ) && is_array( $status['progress'] ) ? $status['progress'] : null
		];
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'materialize an API reference', 'betterdocs-pro' );
	}
}
