<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

use WP_Error;
use WPDeveloper\BetterDocsPro\Dependencies\Symfony\Component\Yaml\Yaml;
use WPDeveloper\BetterDocsPro\Dependencies\Symfony\Component\Yaml\Exception\ParseException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns raw OpenAPI bytes (JSON or YAML) into a PHP array — safely.
 *
 * Safety posture (PRD §7.4): size cap before parse, safe YAML load only (no
 * object/const/custom-tag flags — unknown tags throw), JSON depth cap, and a
 * post-parse node-count cap so YAML anchor expansion can't balloon memory.
 */
class SpecParser {
	/**
	 * Maximum nesting depth accepted from either format.
	 */
	const MAX_DEPTH = 128;

	/**
	 * Post-parse cap on total array nodes. This is a backstop only — for YAML it
	 * cannot be the billion-laughs guard, because aliases expand INSIDE
	 * Yaml::parse() and the memory is already gone by the time we could count.
	 * See guard_raw_yaml().
	 */
	const MAX_NODES = 500000;

	/**
	 * Ceiling on the anchors/aliases a document may declare. OpenAPI documents
	 * express reuse with `$ref`, not YAML anchors, so real specs sit far below
	 * this; it exists to keep the expansion estimate itself cheap.
	 */
	const MAX_ANCHORS = 256;
	const MAX_ALIASES = 1024;

	/**
	 * Parse a raw spec.
	 *
	 * @param string      $raw       Raw file contents.
	 * @param string|null $format    'json'|'yaml'|null (null = sniff).
	 * @param int         $max_bytes Size cap; 0 = caller already enforced it.
	 *
	 * @return array|WP_Error ['spec' => array, 'format' => 'json'|'yaml'] or error.
	 */
	public function parse( $raw, $format = null, $max_bytes = 0 ) {
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return new WP_Error( 'betterdocs_api_spec_empty', __( 'The spec file is empty.', 'betterdocs-pro' ) );
		}

		if ( $max_bytes > 0 && strlen( $raw ) > $max_bytes ) {
			return new WP_Error(
				'betterdocs_api_spec_too_large',
				sprintf(
					/* translators: 1: spec size, 2: allowed size */
					__( 'The spec is %1$s — larger than the allowed %2$s.', 'betterdocs-pro' ),
					size_format( strlen( $raw ) ),
					size_format( $max_bytes )
				)
			);
		}

		if ( null === $format ) {
			$format = $this->sniff_format( $raw );
		}

		if ( 'json' === $format ) {
			$spec = json_decode( $raw, true, self::MAX_DEPTH );

			if ( null === $spec && JSON_ERROR_NONE !== json_last_error() ) {
				return new WP_Error(
					'betterdocs_api_spec_invalid_json',
					sprintf(
						/* translators: %s: JSON parser error message */
						__( 'Invalid JSON: %s', 'betterdocs-pro' ),
						json_last_error_msg()
					)
				);
			}
		} else {
			if ( ! function_exists( 'ctype_digit' ) ) {
				return new WP_Error(
					'betterdocs_api_spec_missing_ctype',
					__( 'YAML parsing requires the PHP ctype extension. Enable ext-ctype, or upload the spec as JSON.', 'betterdocs-pro' )
				);
			}

			// Everything dangerous about YAML happens INSIDE the parser, so the
			// raw bytes have to be vetted first.
			$guard = $this->guard_raw_yaml( $raw );
			if ( is_wp_error( $guard ) ) {
				return $guard;
			}

			// The Symfony YAML component is vendored into Pro's own Dependencies dir
			// (namespace WPDeveloper\BetterDocsPro\Dependencies\Symfony). API
			// Documentation is Pro-only, so it no longer depends on Free bundling it.
			// The deprecation shim defines a global trigger_deprecation() the parser
			// touches on a few legacy syntax paths; require it before parsing.
			require_once BETTERDOCS_PRO_ABSPATH . 'includes/Dependencies/Symfony/Component/Yaml/deprecation-shim.php';

			try {
				// No flags: no object/const support, unknown custom tags throw.
				$spec = Yaml::parse( $raw );
			} catch ( ParseException $e ) {
				$line = $e->getParsedLine();

				return new WP_Error(
					'betterdocs_api_spec_invalid_yaml',
					$line > 0
						? sprintf(
							/* translators: 1: line number, 2: YAML parser error message */
							__( 'Invalid YAML at line %1$d: %2$s', 'betterdocs-pro' ),
							$line,
							$e->getMessage()
						)
						: sprintf(
							/* translators: %s: YAML parser error message */
							__( 'Invalid YAML: %s', 'betterdocs-pro' ),
							$e->getMessage()
						)
				);
			}
		}

		if ( ! is_array( $spec ) || array_values( $spec ) === $spec ) {
			return new WP_Error(
				'betterdocs_api_spec_not_object',
				__( 'The spec must be a JSON/YAML object (an OpenAPI document), not a scalar or list.', 'betterdocs-pro' )
			);
		}

		$nodes = $this->count_nodes( $spec, self::MAX_NODES );
		if ( $nodes > self::MAX_NODES ) {
			return new WP_Error(
				'betterdocs_api_spec_too_complex',
				__( 'The spec expands to too many nodes to process safely.', 'betterdocs-pro' )
			);
		}

		return [
			'spec'   => $spec,
			'format' => $format
		];
	}

	/**
	 * JSON if it reads like JSON, otherwise YAML (YAML is a JSON superset, so
	 * the fallback is always parseable-or-erroring, never silently wrong).
	 *
	 * @param string $raw
	 * @return string 'json'|'yaml'
	 */
	public function sniff_format( $raw ) {
		$first = substr( ltrim( $raw ), 0, 1 );

		return ( '{' === $first ) ? 'json' : 'yaml';
	}

	/**
	 * Pre-parse guard for raw YAML: alias-expansion bound and nesting depth.
	 *
	 * Both attacks this blocks are unreachable from inside the parser:
	 *
	 * - **Billion laughs.** Symfony expands `&anchor`/`*alias` into real nested
	 *   arrays during parse and offers no expansion limit, so a ~10 KB document
	 *   can materialize hundreds of MB before MAX_NODES gets a chance to run.
	 *   The size cap does not help — the input really is small.
	 * - **Depth.** MAX_DEPTH was only ever passed to json_decode. Yaml::parse
	 *   recurses to the document's full depth, and so did count_nodes, so a
	 *   compact `[[[[…]]]]` document overflows the C stack. That is a segfault,
	 *   not an exception: nothing downstream can catch it.
	 *
	 * The expansion estimate is deliberately conservative rather than exact —
	 * computing the true figure would mean implementing the expansion we are
	 * trying to avoid. Worst case is every alias nesting inside every anchor, so
	 * the bound is (aliases/anchors + 1) ^ anchors, which flags the classic
	 * 9-anchor/81-alias bomb (~10^9 nodes) while leaving the handful of anchors
	 * a hand-written spec might use far below the cap.
	 *
	 * @param string $raw
	 * @return true|WP_Error
	 */
	protected function guard_raw_yaml( $raw ) {
		// Strip quoted scalars and comments first: `&`, `*`, `[` and `{` inside
		// a description are ordinary characters, and counting them as structure
		// would reject perfectly good specs.
		$stripped = preg_replace( '/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'/', '""', $raw );
		$stripped = preg_replace( '/(?m)(?<!\S)#.*$/', '', (string) $stripped );

		preg_match_all( '/(?<![\w&])&([A-Za-z0-9_\-.]+)/', (string) $stripped, $anchor_hits );
		preg_match_all( '/(?<![\w*])\*([A-Za-z0-9_\-.]+)/', (string) $stripped, $alias_hits );

		$anchors = count( $anchor_hits[1] );
		$aliases = count( $alias_hits[1] );

		if ( $anchors > self::MAX_ANCHORS || $aliases > self::MAX_ALIASES ) {
			return new WP_Error(
				'betterdocs_api_spec_too_many_anchors',
				__( 'The spec declares an unusual number of YAML anchors or aliases and was refused. Use $ref for reuse in OpenAPI documents.', 'betterdocs-pro' )
			);
		}

		if ( $anchors > 0 && $aliases > 0 ) {
			$factor = ( $aliases / $anchors ) + 1;

			// log-space so the bound itself can never overflow.
			if ( $anchors * log( $factor ) > log( self::MAX_NODES ) ) {
				return new WP_Error(
					'betterdocs_api_spec_alias_bomb',
					__( 'The spec\'s YAML aliases expand to too much data to process safely.', 'betterdocs-pro' )
				);
			}
		}

		$depth = $this->raw_yaml_depth( (string) $stripped );

		if ( $depth > self::MAX_DEPTH ) {
			return new WP_Error(
				'betterdocs_api_spec_too_deep',
				sprintf(
					/* translators: %d: maximum nesting depth */
					__( 'The spec nests deeper than the %d levels allowed.', 'betterdocs-pro' ),
					self::MAX_DEPTH
				)
			);
		}

		return true;
	}

	/**
	 * Approximate nesting depth of a YAML document from its raw text.
	 *
	 * Block nesting is tracked with an indent stack (the same shape a parser
	 * uses); flow nesting is the bracket/brace depth. They compose — a flow
	 * collection can open inside an indented block — so the answer is the deepest
	 * combined point rather than the larger of the two.
	 *
	 * @param string $raw Comment- and string-stripped YAML.
	 * @return int
	 */
	protected function raw_yaml_depth( $raw ) {
		$max     = 0;
		$indents = [];
		$flow    = 0;

		foreach ( preg_split( '/\R/', $raw ) as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}

			// Inside a flow collection, indentation carries no structure.
			if ( 0 === $flow ) {
				$indent = strlen( $line ) - strlen( ltrim( $line, " \t" ) );

				while ( ! empty( $indents ) && end( $indents ) >= $indent ) {
					array_pop( $indents );
				}

				$indents[] = $indent;
			}

			$base = count( $indents );

			foreach ( str_split( $line ) as $char ) {
				if ( '[' === $char || '{' === $char ) {
					$flow++;
					$max = max( $max, $base + $flow );
				} elseif ( ']' === $char || '}' === $char ) {
					$flow = max( 0, $flow - 1 );
				}
			}

			$max = max( $max, $base + $flow );

			// A document this deep is already rejected; stop before the scan
			// itself becomes the expensive part.
			if ( $max > self::MAX_DEPTH ) {
				return $max;
			}
		}

		return $max;
	}

	/**
	 * Count array nodes, bailing early once past the cap.
	 *
	 * Depth-limited as well as count-limited: this walks a tree that may have
	 * come from YAML, where nothing upstream of it enforced a depth, and PHP
	 * gives no way to recover from blowing the native stack.
	 *
	 * @param array $tree
	 * @param int   $cap
	 * @param int   $count
	 * @param int   $depth
	 * @return int
	 */
	protected function count_nodes( $tree, $cap, $count = 0, $depth = 0 ) {
		if ( $depth > self::MAX_DEPTH ) {
			return $cap + 1; // Treated as "too complex" by the caller.
		}

		foreach ( $tree as $value ) {
			$count++;

			if ( $count > $cap ) {
				return $count;
			}

			if ( is_array( $value ) ) {
				$count = $this->count_nodes( $value, $cap, $count, $depth + 1 );

				if ( $count > $cap ) {
					return $count;
				}
			}
		}

		return $count;
	}
}
