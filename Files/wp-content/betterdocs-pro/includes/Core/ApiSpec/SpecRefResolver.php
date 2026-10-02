<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local JSON-pointer `$ref` resolution for parsed OpenAPI documents.
 *
 * Only document-local refs (`#/…`) are resolved — SpecValidator already
 * rejects external refs at ingest, but a defensive pass here leaves any
 * non-local ref untouched. Cycle-safe and depth-capped: on a cycle, missing
 * target, or depth overrun the `$ref` node is returned as-is so a renderer
 * can print the ref name instead of recursing forever.
 */
class SpecRefResolver {
	/**
	 * Maximum nesting depth a resolve pass will follow.
	 */
	const MAX_DEPTH = 10;

	/**
	 * Deep-resolve local $refs inside a node against the document root.
	 *
	 * @param mixed $node    Subtree to resolve (array or scalar).
	 * @param array $root    The full parsed spec document.
	 * @param int   $depth   Internal recursion depth.
	 * @param array $visited Internal pointer-visit set for cycle detection.
	 * @return mixed
	 */
	public function resolve( $node, array $root, $depth = 0, array $visited = [] ) {
		if ( ! is_array( $node ) || $depth > self::MAX_DEPTH ) {
			return $node;
		}

		if ( isset( $node['$ref'] ) && is_string( $node['$ref'] ) ) {
			$pointer = $node['$ref'];

			// Non-local or already being resolved up-stack → leave untouched.
			if ( 0 !== strpos( $pointer, '#/' ) || isset( $visited[ $pointer ] ) ) {
				return $node;
			}

			$target = $this->lookup_pointer( $pointer, $root );

			if ( null === $target ) {
				return $node;
			}

			$visited[ $pointer ] = true;

			// Sibling keys beside $ref (3.1 allows summary/description) win
			// over the target's keys per the JSON Reference semantics we need.
			//
			// They are resolved too. Previously they were merged back verbatim,
			// so a `$ref` nested inside a sibling survived into the output and
			// downstream consumers saw an unresolved pointer where they expect a
			// resolved node. Resolving them uses the SAME $visited set and depth,
			// so the cycle and depth guarantees are unchanged.
			$siblings = $node;
			unset( $siblings['$ref'] );

			foreach ( $siblings as $key => $value ) {
				$siblings[ $key ] = $this->resolve( $value, $root, $depth + 1, $visited );
			}

			$resolved = $this->resolve( $target, $root, $depth + 1, $visited );

			return is_array( $resolved ) ? array_merge( $resolved, $siblings ) : $resolved;
		}

		foreach ( $node as $key => $value ) {
			$node[ $key ] = $this->resolve( $value, $root, $depth + 1, $visited );
		}

		return $node;
	}

	/**
	 * Look up a `#/a/b~1c` JSON pointer in the document root.
	 *
	 * @param string $pointer
	 * @param array  $root
	 * @return array|string|int|float|bool|null Null when the path is missing.
	 */
	public function lookup_pointer( $pointer, array $root ) {
		$path    = substr( $pointer, 2 ); // strip '#/'
		$current = $root;

		foreach ( explode( '/', $path ) as $token ) {
			// RFC 6901 unescape: ~1 → /, ~0 → ~ (in that order).
			$token = str_replace( [ '~1', '~0' ], [ '/', '~' ], $token );

			if ( ! is_array( $current ) || ! array_key_exists( $token, $current ) ) {
				return null;
			}

			$current = $current[ $token ];
		}

		return $current;
	}
}
