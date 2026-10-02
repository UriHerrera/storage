<?php
/**
 * List API references ability.
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
 * List the API references on this site, each with its title, slug, status,
 * source and whether its spec has been materialized into docs.
 *
 * The tool an agent calls before creating one — so it ingests into the reference
 * that already exists instead of making a near-duplicate — and to find the id
 * every other API-docs tool needs.
 *
 * @since 4.9.1
 */
class ListApiReferences extends ProAbility {

	use TalksToApiDocs;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/list-api-references';
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

		$data = $this->api_call( 'GET', '/api-ref' );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$references = isset( $data['references'] ) && is_array( $data['references'] ) ? $data['references'] : [];
		$items      = [];

		foreach ( $references as $row ) {
			$items[] = $this->shape_reference( (array) $row );
		}

		// max_references is PHP_INT_MAX in Pro (unlimited); that serializes as a
		// misleading 9.2e18 float in JSON, so report unlimited as null.
		$max = isset( $data['max_references'] ) ? (int) $data['max_references'] : 0;

		return [
			'references'     => $items,
			'total'          => count( $items ),
			'max_references' => $max >= PHP_INT_MAX ? null : $max
		];
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'list API references', 'betterdocs-pro' );
	}
}
