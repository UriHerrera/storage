<?php
/**
 * Get API reference ability.
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
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecParser;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecStore;

/**
 * Read one API reference by id: its settings and spec summary, and — when
 * `include_operations` is set — a compact inventory of the operations the spec
 * documents (method, path, summary, tags), so an agent can see what endpoints
 * exist without fetching the whole spec.
 *
 * @since 4.9.1
 */
class GetApiReference extends ProAbility {

	use TalksToApiDocs;

	/**
	 * Default cap on the operations list. Keeps a large spec from returning a
	 * thousand-line tool result when the caller did not ask for a size.
	 *
	 * @since 4.9.1
	 *
	 * @var int
	 */
	const DEFAULT_MAX_OPERATIONS = 100;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/get-api-reference';
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
			return \WPDeveloper\BetterDocs\Abilities\AbilityError::invalid_input( 'id', __( 'Give the API reference id.', 'betterdocs-pro' ) );
		}

		$row = $this->api_call( 'GET', '/api-ref/' . $id, [], $id );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$out = $this->shape_reference( (array) $row );

		if ( ! empty( $input['include_operations'] ) ) {
			$max = isset( $input['max_operations'] ) ? max( 1, (int) $input['max_operations'] ) : self::DEFAULT_MAX_OPERATIONS;
			$out['operations'] = $this->operations( $id, $max );
		}

		return $out;
	}

	/**
	 * A compact operations inventory from the reference's active spec, or an
	 * empty list when there is no spec yet or it cannot be parsed. Reading the
	 * spec is best-effort: a get should still return the reference row even when
	 * the operations cannot be listed.
	 *
	 * @since 4.9.1
	 *
	 * @param int $reference_id Reference id.
	 * @param int $max          Cap on the number of operations returned.
	 * @return array
	 */
	protected function operations( $reference_id, $max ) {
		$container = betterdocs()->container;
		$active    = $container->get( SpecStore::class )->get_active( $reference_id );

		if ( ! $active || empty( $active->raw ) ) {
			return [];
		}

		$parsed = $container->get( SpecParser::class )->parse( $active->raw, isset( $active->format ) ? $active->format : null );

		if ( is_wp_error( $parsed ) || ! isset( $parsed['spec']['paths'] ) || ! is_array( $parsed['spec']['paths'] ) ) {
			return [];
		}

		$methods = [ 'get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace' ];
		$ops     = [];

		foreach ( $parsed['spec']['paths'] as $path => $operations ) {
			if ( ! is_array( $operations ) ) {
				continue;
			}

			foreach ( $operations as $method => $operation ) {
				if ( ! in_array( strtolower( (string) $method ), $methods, true ) || ! is_array( $operation ) ) {
					continue;
				}

				$ops[] = [
					'method'  => strtoupper( (string) $method ),
					'path'    => (string) $path,
					'summary' => isset( $operation['summary'] ) ? (string) $operation['summary'] : '',
					'tags'    => isset( $operation['tags'] ) && is_array( $operation['tags'] ) ? array_values( array_map( 'strval', $operation['tags'] ) ) : []
				];

				if ( count( $ops ) >= $max ) {
					return $ops;
				}
			}
		}

		return $ops;
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'read an API reference', 'betterdocs-pro' );
	}
}
