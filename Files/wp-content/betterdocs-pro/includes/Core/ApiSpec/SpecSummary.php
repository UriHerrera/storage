<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The normalized, cache-friendly digest of a parsed spec: what the admin list
 * UI shows and what the Free endpoint cap reasons about. Stored in the
 * reference's `_bd_api_spec_summary` meta, keyed by the raw spec's sha256.
 */
class SpecSummary {
	/**
	 * Stable operation order used everywhere (list UI, Free truncation):
	 * document `paths` order, then this method order within each path.
	 *
	 * @var string[]
	 */
	const METHOD_ORDER = [ 'get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace' ];

	/**
	 * Build the summary.
	 *
	 * @param array  $spec Parsed spec.
	 * @param string $raw  Raw bytes (hashed for cache identity).
	 * @return array
	 */
	public function summarize( array $spec, $raw ) {
		$operations = $this->operations( $spec );

		$tags = [];
		if ( isset( $spec['tags'] ) && is_array( $spec['tags'] ) ) {
			foreach ( $spec['tags'] as $tag ) {
				if ( is_array( $tag ) && isset( $tag['name'] ) ) {
					$tags[] = (string) $tag['name'];
				}
			}
		}

		$servers = [];
		if ( isset( $spec['servers'] ) && is_array( $spec['servers'] ) ) {
			foreach ( $spec['servers'] as $server ) {
				if ( is_array( $server ) && isset( $server['url'] ) ) {
					$servers[] = (string) $server['url'];
				}
			}
		}

		return [
			'openapi'         => isset( $spec['openapi'] ) ? (string) $spec['openapi'] : '',
			'title'           => isset( $spec['info']['title'] ) ? (string) $spec['info']['title'] : '',
			'version'         => isset( $spec['info']['version'] ) ? (string) $spec['info']['version'] : '',
			'description'     => isset( $spec['info']['description'] ) ? wp_trim_words( (string) $spec['info']['description'], 40 ) : '',
			'operation_count' => count( $operations ),
			'tags'            => $tags,
			'servers'         => $servers,
			'hash'            => hash( 'sha256', (string) $raw )
		];
	}

	/**
	 * Ordered [path, method] pairs for every operation in the document.
	 *
	 * @param array $spec
	 * @return array<int, array{0: string, 1: string}>
	 */
	public function operations( array $spec ) {
		$operations = [];

		if ( empty( $spec['paths'] ) || ! is_array( $spec['paths'] ) ) {
			return $operations;
		}

		foreach ( $spec['paths'] as $path => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			foreach ( self::METHOD_ORDER as $method ) {
				if ( isset( $item[ $method ] ) && is_array( $item[ $method ] ) ) {
					$operations[] = [ (string) $path, $method ];
				}
			}
		}

		return $operations;
	}

	/**
	 * A copy of the spec truncated to the first $max operations (stable order),
	 * used by the Free tier of the spec route. Components/servers/etc. are kept
	 * so the remaining operations' $refs still resolve; now-empty path items
	 * are dropped.
	 *
	 * @param array $spec
	 * @param int   $max
	 * @return array{spec: array, total: int, rendered: int}
	 */
	public function truncate( array $spec, $max ) {
		$operations = $this->operations( $spec );
		$total      = count( $operations );

		if ( $total <= $max ) {
			return [
				'spec'     => $spec,
				'total'    => $total,
				'rendered' => $total
			];
		}

		$keep = [];
		foreach ( array_slice( $operations, 0, $max ) as $op ) {
			$keep[ $op[0] ][ $op[1] ] = true;
		}

		$paths = [];
		foreach ( $spec['paths'] as $path => $item ) {
			if ( ! isset( $keep[ $path ] ) || ! is_array( $item ) ) {
				continue;
			}

			$kept_item = [];
			foreach ( $item as $key => $value ) {
				$is_method = in_array( $key, self::METHOD_ORDER, true );

				// Keep non-operation keys (parameters, servers, summary …)
				// and only the surviving methods.
				if ( ! $is_method || isset( $keep[ $path ][ $key ] ) ) {
					$kept_item[ $key ] = $value;
				}
			}

			$paths[ $path ] = $kept_item;
		}

		$spec['paths'] = $paths;

		return [
			'spec'     => $spec,
			'total'    => $total,
			'rendered' => $max
		];
	}
}
