<?php
/**
 * Shared helpers for the API-documentation abilities.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WP_Error;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;

/**
 * The API-documentation abilities all drive the same `betterdocs/v1/api-ref`
 * REST routes the admin screen uses, so they share three things: the Pro guard,
 * unwrapping the `{ success, data }` envelope those routes return, and turning a
 * route's `WP_Error` into a typed {@see AbilityError}. Keeping them here means
 * the seven abilities read as behaviour, not plumbing — and one error map, not
 * seven that drift.
 *
 * @since 4.9.1
 */
trait TalksToApiDocs {

	/**
	 * Refuse when Pro cannot actually serve the call, the way every Pro ability
	 * does. Returns null when the call may proceed.
	 *
	 * @since 4.9.1
	 *
	 * @return \WP_Error|null
	 */
	protected function api_blocked() {
		$state = $this->pro_state();

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, $this->feature );
		}

		return null;
	}

	/**
	 * Dispatch an internal REST request to the API-reference routes and hand back
	 * the payload, unwrapped. A route failure comes back as a typed AbilityError
	 * rather than the raw `WP_Error`.
	 *
	 * @since 4.9.1
	 *
	 * @param string   $method     HTTP verb.
	 * @param string   $route      Route beneath `betterdocs/v1`, e.g. `/api-ref`.
	 * @param array    $params     Query params for GET, body params otherwise.
	 * @param int|null $not_found  Reference id to name in a 404, when known.
	 * @return mixed|\WP_Error Unwrapped payload, or an AbilityError.
	 */
	protected function api_call( $method, $route, array $params = [], $not_found = null ) {
		$resp = $this->dispatch( $method, $route, $params );

		if ( is_wp_error( $resp ) ) {
			return $this->map_api_error( $resp, $not_found );
		}

		if ( is_array( $resp ) && array_key_exists( 'data', $resp ) ) {
			return $resp['data'];
		}

		if ( is_array( $resp ) && array_key_exists( 'message', $resp ) ) {
			return $resp['message'];
		}

		return $resp;
	}

	/**
	 * Map a route's `WP_Error` to the ability vocabulary.
	 *
	 * @since 4.9.1
	 *
	 * @param \WP_Error $error     The route error.
	 * @param int|null  $not_found Reference id to name in a 404, when known.
	 * @return \WP_Error An AbilityError.
	 */
	protected function map_api_error( WP_Error $error, $not_found = null ) {
		$code   = (string) $error->get_error_code();
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;

		if ( 404 === $status || 'betterdocs_api_ref_not_found' === $code ) {
			return AbilityError::not_found( 'api_reference', null === $not_found ? '' : (string) $not_found );
		}

		return AbilityError::upstream( $error->get_error_message(), [ 'code' => $code ] );
	}

	/**
	 * The compact row an API reference is reported as. Passes the admin row
	 * through, keeping only the fields an agent acts on — the drawer-only
	 * branding/proxy detail is noise in a tool result.
	 *
	 * @since 4.9.1
	 *
	 * @param array $row A `prepare_reference()` row.
	 * @return array
	 */
	protected function shape_reference( array $row ) {
		$summary = isset( $row['summary'] ) && is_array( $row['summary'] ) ? $row['summary'] : null;

		return [
			'id'           => isset( $row['id'] ) ? (int) $row['id'] : 0,
			'title'        => isset( $row['title'] ) ? (string) $row['title'] : '',
			'slug'         => isset( $row['slug'] ) ? (string) $row['slug'] : '',
			'status'       => isset( $row['status'] ) ? (string) $row['status'] : '',
			'permalink'    => isset( $row['permalink'] ) ? (string) $row['permalink'] : '',
			'source'       => isset( $row['source'] ) ? (string) $row['source'] : '',
			'source_kind'  => isset( $row['source_kind'] ) ? (string) $row['source_kind'] : '',
			'materialized' => ! empty( $row['materialized'] ),
			'summary'      => $summary
		];
	}
}
