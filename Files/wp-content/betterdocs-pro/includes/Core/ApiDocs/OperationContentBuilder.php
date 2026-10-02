<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecRefResolver;
use WPDeveloper\BetterDocsPro\Dependencies\Parsedown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the generated post_title/post_content for endpoint + introduction
 * docs as Gutenberg block markup (`serialize_blocks()` over block arrays).
 *
 * MUST be deterministic — identical input bytes-identical output (ADR-014):
 * no timestamps, stable iteration order, stable JSON encoding.
 */
class OperationContentBuilder {
	/**
	 * @var SpecRefResolver
	 */
	protected $resolver;

	/**
	 * @var CodeSampleGenerator
	 */
	protected $samples;

	/**
	 * @var Parsedown
	 */
	protected $markdown;

	/**
	 * Colour mode the generated Code Snippet blocks are created with, from the
	 * reference currently being built ('light' | 'dark'). Set per build so the
	 * snippet emitters — which are several calls deep and don't carry the
	 * reference — don't each need it threaded through.
	 *
	 * @var string
	 */
	protected $code_theme = 'light';

	public function __construct() {
		$this->resolver = new SpecRefResolver();
		$this->samples  = new CodeSampleGenerator();

		require_once BETTERDOCS_PRO_ABSPATH . 'includes/Dependencies/Parsedown.php';
		$this->markdown = new Parsedown();
		$this->markdown->setSafeMode( true );
	}

	/**
	 * Build one endpoint doc.
	 *
	 * @param string   $method    Lowercase method.
	 * @param string   $path      Templated path.
	 * @param array    $operation Raw (unresolved) operation object.
	 * @param array    $spec      Full parsed spec.
	 * @param \WP_Post $reference The owning reference (explorer link).
	 * @return array{title:string, content:string, excerpt:string}
	 */
	public function build_endpoint( $method, $path, array $operation, array $spec, \WP_Post $reference ) {
		$this->code_theme = BrandPropagator::code_theme( $reference->ID );

		$operation = $this->resolver->resolve( $operation, $spec );

		$title = isset( $operation['summary'] ) && '' !== trim( (string) $operation['summary'] )
			? trim( (string) $operation['summary'] )
			: strtoupper( $method ) . ' ' . $path;

		$blocks = [ $this->banner_block( $method, $path, $operation, $reference ) ];

		if ( ! empty( $operation['description'] ) ) {
			$blocks = array_merge( $blocks, $this->markdown_blocks( (string) $operation['description'] ) );
		}

		$blocks = array_merge(
			$blocks,
			$this->parameters_blocks( isset( $operation['parameters'] ) ? (array) $operation['parameters'] : [] ),
			$this->request_body_blocks( isset( $operation['requestBody'] ) ? (array) $operation['requestBody'] : [] ),
			$this->responses_blocks( isset( $operation['responses'] ) ? (array) $operation['responses'] : [] ),
			$this->code_sample_blocks( $method, $path, $operation, $spec ),
			// Response example bodies as one tabbed code-snippet-tab block —
			// relocated into the right sidebar under the request Code Samples.
			$this->response_example_blocks( isset( $operation['responses'] ) ? (array) $operation['responses'] : [] )
		);

		return [
			'title'   => $title,
			'content' => serialize_blocks( $blocks ),
			'excerpt' => isset( $operation['description'] )
				? wp_trim_words( wp_strip_all_tags( $this->markdown->text( (string) $operation['description'] ) ), 30 )
				: ''
		];
	}

	/**
	 * Build the reference's Introduction doc.
	 *
	 * @param array    $spec
	 * @param \WP_Post $reference
	 * @return array{title:string, content:string, excerpt:string}
	 */
	public function build_introduction( array $spec, \WP_Post $reference ) {
		$this->code_theme = BrandPropagator::code_theme( $reference->ID );

		$info   = isset( $spec['info'] ) && is_array( $spec['info'] ) ? $spec['info'] : [];
		$blocks = [];

		if ( ! empty( $info['description'] ) ) {
			$blocks = array_merge( $blocks, $this->markdown_blocks( (string) $info['description'] ) );
		}

		// (v3.1) No "Open the interactive API Explorer" pointer: the reference
		// URL now renders THIS Introduction content natively (there is no
		// separate explorer page), so the link was circular — ADR-032.

		// Base URLs.
		if ( ! empty( $spec['servers'] ) && is_array( $spec['servers'] ) ) {
			$rows = [];
			foreach ( $spec['servers'] as $server ) {
				if ( ! is_array( $server ) || empty( $server['url'] ) ) {
					continue;
				}
				$rows[] = [
					'<code>' . esc_html( (string) $server['url'] ) . '</code>',
					esc_html( isset( $server['description'] ) ? (string) $server['description'] : '' )
				];
			}

			if ( $rows ) {
				$blocks[] = $this->heading_block( __( 'Base URLs', 'betterdocs-pro' ) );
				$blocks[] = $this->table_block( [ __( 'URL', 'betterdocs-pro' ), __( 'Description', 'betterdocs-pro' ) ], $rows );
			}
		}

		// Authentication.
		$schemes = isset( $spec['components']['securitySchemes'] ) && is_array( $spec['components']['securitySchemes'] )
			? $spec['components']['securitySchemes']
			: [];

		if ( $schemes ) {
			$rows = [];
			foreach ( $schemes as $name => $scheme ) {
				$scheme = $this->resolver->resolve( is_array( $scheme ) ? $scheme : [], $spec );
				$type   = isset( $scheme['type'] ) ? (string) $scheme['type'] : '';
				$detail = '';

				if ( 'http' === $type ) {
					$detail = sprintf(
						/* translators: %s: HTTP auth scheme (bearer/basic) */
						__( 'HTTP %s authentication', 'betterdocs-pro' ),
						isset( $scheme['scheme'] ) ? (string) $scheme['scheme'] : ''
					);
				} elseif ( 'apiKey' === $type ) {
					$detail = sprintf(
						/* translators: 1: parameter name, 2: location (header/query/cookie) */
						__( 'API key via %1$s (%2$s)', 'betterdocs-pro' ),
						'<code>' . esc_html( isset( $scheme['name'] ) ? (string) $scheme['name'] : '' ) . '</code>',
						esc_html( isset( $scheme['in'] ) ? (string) $scheme['in'] : 'header' )
					);
				} elseif ( 'oauth2' === $type ) {
					$detail = __( 'OAuth 2.0', 'betterdocs-pro' );
				} elseif ( 'openIdConnect' === $type ) {
					$detail = __( 'OpenID Connect', 'betterdocs-pro' );
				}

				$rows[] = [
					'<code>' . esc_html( (string) $name ) . '</code>',
					esc_html( $type ),
					$detail
				];
			}

			$blocks[] = $this->heading_block( __( 'Authentication', 'betterdocs-pro' ) );
			$blocks[] = $this->table_block(
				[ __( 'Scheme', 'betterdocs-pro' ), __( 'Type', 'betterdocs-pro' ), __( 'Details', 'betterdocs-pro' ) ],
				$rows
			);
		}

		// Version / contact / license.
		$meta_rows = [];
		if ( ! empty( $info['version'] ) ) {
			$meta_rows[] = [ esc_html__( 'Version', 'betterdocs-pro' ), esc_html( (string) $info['version'] ) ];
		}
		if ( ! empty( $info['contact']['name'] ) || ! empty( $info['contact']['url'] ) || ! empty( $info['contact']['email'] ) ) {
			$contact     = isset( $info['contact'] ) ? $info['contact'] : [];
			$bits        = [];
			if ( ! empty( $contact['name'] ) ) {
				$bits[] = esc_html( (string) $contact['name'] );
			}
			if ( ! empty( $contact['url'] ) ) {
				$bits[] = '<a href="' . esc_url( (string) $contact['url'] ) . '">' . esc_html( (string) $contact['url'] ) . '</a>';
			}
			if ( ! empty( $contact['email'] ) ) {
				$bits[] = esc_html( (string) $contact['email'] );
			}
			$meta_rows[] = [ esc_html__( 'Contact', 'betterdocs-pro' ), implode( ' — ', $bits ) ];
		}
		if ( ! empty( $info['license']['name'] ) ) {
			$meta_rows[] = [ esc_html__( 'License', 'betterdocs-pro' ), esc_html( (string) $info['license']['name'] ) ];
		}

		if ( $meta_rows ) {
			$blocks[] = $this->heading_block( __( 'API Details', 'betterdocs-pro' ) );
			$blocks[] = $this->table_block( [ '', '' ], $meta_rows );
		}

		return [
			'title'   => __( 'Introduction', 'betterdocs-pro' ),
			'content' => serialize_blocks( $blocks ),
			'excerpt' => ! empty( $info['description'] )
				? wp_trim_words( wp_strip_all_tags( $this->markdown->text( (string) $info['description'] ) ), 30 )
				: ''
		];
	}

	/* ---------------------------------------------------------------- */
	/* Sections                                                          */
	/* ---------------------------------------------------------------- */

	/**
	 * Method + path banner with the Try-it drawer trigger.
	 *
	 * Emits the `betterdocs/api-tryit` dynamic block carrying the per-endpoint
	 * attrs plus the reference's branding colours, in fixed key order for
	 * byte-stability (ADR-014). Falls back to a `core/html` block with the
	 * original static markup when the block isn't registered.
	 *
	 * The colours are written into the block so an author can see and adjust
	 * them in the editor's Style panel. They are only a seed: an empty colour
	 * attribute inherits the reference at render time, and re-branding the
	 * reference rewrites these attrs across every generated doc in one pass
	 * (see BrandPropagator).
	 *
	 * Label and show/hide stay out of the markup on purpose — they are read at
	 * render time from the reference's meta, so they apply without a rebuild.
	 *
	 * @return array Parsed-block array for one block.
	 */
	protected function banner_block( $method, $path, array $operation, \WP_Post $reference ) {
		$colors = AccentPalette::reference_colors( $reference->ID );

		$attrs = [
			'method' => strtolower( (string) $method ),
			'path'   => (string) $path,
			'refId'  => (int) $reference->ID
		];

		// Only when actually branded — an unbranded reference keeps emitting the
		// exact bytes it always has, so adopting this costs no rebuild.
		if ( '' !== $colors['accent'] ) {
			$attrs['accentColor'] = $colors['accent'];
		}

		if ( '' !== $colors['contrast'] ) {
			$attrs['accentTextColor'] = $colors['contrast'];
		}

		if ( $this->tryit_block_registered() ) {
			return $this->block( 'betterdocs/api-tryit', $attrs, '' );
		}

		// Fallback: the original static banner markup (default label — the
		// fallback isn't the customizable path, so it stays deterministic).
		$html = sprintf(
			'<div class="betterdocs-api-endpoint-banner betterdocs-api-endpoint-doc">' .
			'<span class="betterdocs-api-method betterdocs-api-method--%1$s">%2$s</span>' .
			'<code class="betterdocs-api-endpoint-path">%3$s</code>' .
			'<button type="button" class="betterdocs-api-tryit" data-ref-id="%4$d" data-method="%1$s" data-path="%5$s">%6$s</button>' .
			'</div>',
			esc_attr( strtolower( $method ) ),
			esc_html( strtoupper( $method ) ),
			esc_html( $path ),
			(int) $reference->ID,
			esc_attr( $path ),
			esc_html__( 'Try it', 'betterdocs-pro' )
		);

		return $this->html_block( $html );
	}

	/**
	 * Is the dynamic Try-it block available to render?
	 *
	 * @return bool
	 */
	protected function tryit_block_registered() {
		return class_exists( '\WP_Block_Type_Registry' )
			&& \WP_Block_Type_Registry::get_instance()->is_registered( 'betterdocs/api-tryit' );
	}

	/**
	 * Scalar hash-navigation URL for one operation on the explorer page.
	 * Format observed from Scalar 1.62: #tag/{tag-slug}/{METHOD}{path}.
	 */
	public function explorer_operation_url( \WP_Post $reference, $tag, $method, $path ) {
		$base = get_permalink( $reference );

		if ( '' === $tag ) {
			return $base;
		}

		return $base . '#tag/' . rawurlencode( sanitize_title( $tag ) ) . '/' . strtoupper( $method ) . $path;
	}

	protected function parameters_blocks( array $parameters ) {
		if ( empty( $parameters ) ) {
			return [];
		}

		$groups = [];
		foreach ( $parameters as $param ) {
			if ( ! is_array( $param ) || empty( $param['name'] ) ) {
				continue;
			}
			$in              = isset( $param['in'] ) ? (string) $param['in'] : 'query';
			$groups[ $in ][] = $param;
		}

		if ( empty( $groups ) ) {
			return [];
		}

		$labels = [
			'path'   => __( 'Path Parameters', 'betterdocs-pro' ),
			'query'  => __( 'Query Parameters', 'betterdocs-pro' ),
			'header' => __( 'Header Parameters', 'betterdocs-pro' ),
			'cookie' => __( 'Cookie Parameters', 'betterdocs-pro' )
		];

		$blocks = [];

		foreach ( [ 'path', 'query', 'header', 'cookie' ] as $location ) {
			if ( empty( $groups[ $location ] ) ) {
				continue;
			}

			$rows = [];
			foreach ( $groups[ $location ] as $param ) {
				$schema = isset( $param['schema'] ) && is_array( $param['schema'] ) ? $param['schema'] : [];
				$rows[] = [
					'<code>' . esc_html( (string) $param['name'] ) . '</code>',
					esc_html( $this->schema_type_label( $schema ) ),
					empty( $param['required'] )
						? esc_html__( 'Optional', 'betterdocs-pro' )
						: '<strong>' . esc_html__( 'Required', 'betterdocs-pro' ) . '</strong>',
					esc_html( isset( $param['description'] ) ? (string) $param['description'] : '' )
				];
			}

			$blocks[] = $this->heading_block( $labels[ $location ] );
			$blocks[] = $this->table_block(
				[
					__( 'Name', 'betterdocs-pro' ),
					__( 'Type', 'betterdocs-pro' ),
					__( 'Required', 'betterdocs-pro' ),
					__( 'Description', 'betterdocs-pro' )
				],
				$rows
			);
		}

		return $blocks;
	}

	protected function request_body_blocks( array $request_body ) {
		if ( empty( $request_body['content'] ) || ! is_array( $request_body['content'] ) ) {
			return [];
		}

		// Prefer JSON; fall back to the first declared content type.
		$content_type = isset( $request_body['content']['application/json'] )
			? 'application/json'
			: (string) array_keys( $request_body['content'] )[0];

		$media  = $request_body['content'][ $content_type ];
		$schema = isset( $media['schema'] ) && is_array( $media['schema'] ) ? $media['schema'] : [];

		$blocks   = [];
		$blocks[] = $this->heading_block( __( 'Request Body', 'betterdocs-pro' ) );
		$blocks[] = $this->paragraph_block(
			sprintf(
				/* translators: %s: content type */
				esc_html__( 'Content type: %s', 'betterdocs-pro' ),
				'<code>' . esc_html( $content_type ) . '</code>'
			) . ( empty( $request_body['required'] ) ? '' : ' — <strong>' . esc_html__( 'required', 'betterdocs-pro' ) . '</strong>' )
		);

		$rows = $this->schema_rows( $schema );
		if ( $rows ) {
			$blocks[] = $this->table_block(
				[
					__( 'Field', 'betterdocs-pro' ),
					__( 'Type', 'betterdocs-pro' ),
					__( 'Required', 'betterdocs-pro' ),
					__( 'Description', 'betterdocs-pro' )
				],
				$rows
			);
		}

		if ( 'application/json' === $content_type && ( array_key_exists( 'example', $media ) || $schema ) ) {
			// Media-level example (spec-authored or AI overlay) beats synthesis.
			$example = array_key_exists( 'example', $media )
				? $media['example']
				: $this->samples->example_from_schema( $schema );

			$blocks[] = $this->code_snippet_block(
				$this->samples->encode_example( $example ),
				'json',
				__( 'Example request', 'betterdocs-pro' )
			);
		}

		return $blocks;
	}

	/**
	 * The inline "Responses" documentation: heading + per-status field tables +
	 * the per-status example body (single-language JSON). The examples are ALSO
	 * collected into one tabbed code-snippet-tab block (see
	 * response_example_blocks()) for the right sidebar — the inline copies stay
	 * in the content flow.
	 *
	 * @param array $responses
	 * @return array[]
	 */
	protected function responses_blocks( array $responses ) {
		if ( empty( $responses ) ) {
			return [];
		}

		$blocks   = [];
		$blocks[] = $this->heading_block( __( 'Responses', 'betterdocs-pro' ) );

		ksort( $responses, SORT_STRING );

		foreach ( $responses as $status => $response ) {
			if ( ! is_array( $response ) ) {
				continue;
			}

			$description = isset( $response['description'] ) ? (string) $response['description'] : '';

			$blocks[] = $this->heading_block(
				trim( $status . ' — ' . $description, ' —' ),
				3,
				'betterdocs-api-response betterdocs-api-response--' . substr( (string) $status, 0, 1 ) . 'xx'
			);

			$media  = isset( $response['content']['application/json'] ) && is_array( $response['content']['application/json'] )
				? $response['content']['application/json']
				: [];
			$schema = isset( $media['schema'] ) && is_array( $media['schema'] ) ? $media['schema'] : null;

			if ( is_array( $schema ) ) {
				$rows = $this->schema_rows( $schema );
				if ( $rows ) {
					$blocks[] = $this->table_block(
						[
							__( 'Field', 'betterdocs-pro' ),
							__( 'Type', 'betterdocs-pro' ),
							__( 'Required', 'betterdocs-pro' ),
							__( 'Description', 'betterdocs-pro' )
						],
						$rows
					);
				}
			}

			if ( is_array( $schema ) || array_key_exists( 'example', $media ) ) {
				// Media-level example (spec-authored or AI overlay) beats synthesis.
				$example = array_key_exists( 'example', $media )
					? $media['example']
					: $this->samples->example_from_schema( $schema );

				$blocks[] = $this->code_snippet_block(
					$this->samples->encode_example( $example ),
					'json',
					sprintf(
						/* translators: %s: HTTP status code */
						__( 'Example %s response', 'betterdocs-pro' ),
						$status
					)
				);
			}
		}

		return $blocks;
	}

	/**
	 * One `betterdocs/code-snippet-tab` block — a tab per status code, each
	 * holding the example JSON body. Relocated to the right sidebar (under the
	 * request Code Samples) by Frontend::relocate_code_samples().
	 *
	 * @param array $responses
	 * @return array[]
	 */
	protected function response_example_blocks( array $responses ) {
		if ( empty( $responses ) ) {
			return [];
		}

		ksort( $responses, SORT_STRING );

		$tabs = [];
		foreach ( $responses as $status => $response ) {
			if ( ! is_array( $response ) ) {
				continue;
			}

			$media = isset( $response['content']['application/json'] ) && is_array( $response['content']['application/json'] )
				? $response['content']['application/json']
				: [];
			$schema = isset( $media['schema'] ) && is_array( $media['schema'] ) ? $media['schema'] : null;

			if ( ! is_array( $schema ) && ! array_key_exists( 'example', $media ) ) {
				continue;
			}

			// Media-level example (spec-authored or AI overlay) beats synthesis.
			$example = array_key_exists( 'example', $media )
				? $media['example']
				: $this->samples->example_from_schema( $schema );

			// Fixed key order keeps the serialized block byte-stable (ADR-014).
			$tabs[] = [
				'status'      => (string) $status,
				'language'    => 'json',
				'codeContent' => $this->samples->encode_example( $example )
			];
		}

		if ( empty( $tabs ) ) {
			return [];
		}

		return [
			$this->heading_block( __( 'Response', 'betterdocs-pro' ) ),
			$this->code_snippet_tab_block( $tabs )
		];
	}

	protected function code_sample_blocks( $method, $path, array $operation, array $spec ) {
		$ctx = $this->samples->context( $method, $path, $operation, $spec );

		// Ordered languages — the first is the primary tab, the rest become the
		// language-dropdown variants of ONE code-snippet block (Mintlify style).
		// Stable order keeps the serialized block hash byte-stable (ADR-014).
		$samples = [
			[ 'curl', 'cURL', $this->samples->curl( $ctx ) ],
			[ 'javascript', 'JavaScript', $this->samples->javascript( $ctx ) ],
			[ 'python', 'Python', $this->samples->python( $ctx ) ],
			[ 'php', 'PHP', $this->samples->php( $ctx ) ],
			[ 'csharp', 'C#', $this->samples->csharp( $ctx ) ],
			[ 'java', 'Java', $this->samples->java( $ctx ) ]
		];

		$primary  = array_shift( $samples );
		$variants = [];
		foreach ( $samples as $sample ) {
			$variants[] = [
				'language'    => $sample[0],
				'codeContent' => $sample[2]
			];
		}

		return [
			$this->heading_block( __( 'Code Samples', 'betterdocs-pro' ) ),
			$this->code_snippet_block( $primary[2], $primary[0], $primary[1], $variants )
		];
	}

	/* ---------------------------------------------------------------- */
	/* Schema helpers                                                    */
	/* ---------------------------------------------------------------- */

	/**
	 * Flattened field rows for a (resolved) object schema, depth ≤ 3,
	 * nested names dotted (owner.address.city).
	 *
	 * @param array  $schema
	 * @param string $prefix
	 * @param int    $depth
	 * @return array[]
	 */
	protected function schema_rows( array $schema, $prefix = '', $depth = 0 ) {
		if ( $depth >= 3 ) {
			return [];
		}

		// allOf: merge parts.
		if ( isset( $schema['allOf'] ) && is_array( $schema['allOf'] ) ) {
			$rows = [];
			foreach ( $schema['allOf'] as $part ) {
				if ( is_array( $part ) ) {
					$rows = array_merge( $rows, $this->schema_rows( $part, $prefix, $depth ) );
				}
			}
			return $rows;
		}

		// Array root: describe items.
		if ( isset( $schema['type'] ) && 'array' === $schema['type'] && isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			return $this->schema_rows( $schema['items'], $prefix ? $prefix . '[]' : '[]', $depth );
		}

		if ( empty( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
			return [];
		}

		$required = isset( $schema['required'] ) && is_array( $schema['required'] ) ? $schema['required'] : [];
		$rows     = [];

		foreach ( $schema['properties'] as $name => $prop ) {
			if ( ! is_array( $prop ) ) {
				continue;
			}

			$full_name = $prefix ? $prefix . '.' . $name : (string) $name;

			$rows[] = [
				'<code>' . esc_html( $full_name ) . '</code>',
				esc_html( $this->schema_type_label( $prop ) ),
				in_array( $name, $required, true )
					? '<strong>' . esc_html__( 'Required', 'betterdocs-pro' ) . '</strong>'
					: esc_html__( 'Optional', 'betterdocs-pro' ),
				esc_html( isset( $prop['description'] ) ? (string) $prop['description'] : '' ) .
					( isset( $prop['enum'] ) && is_array( $prop['enum'] )
						? ' ' . esc_html(
							sprintf(
								/* translators: %s: allowed values list */
								__( 'Allowed: %s', 'betterdocs-pro' ),
								implode( ', ', array_map( 'strval', $prop['enum'] ) )
							)
						)
						: '' )
			];

			// Recurse into nested objects.
			if ( isset( $prop['properties'] ) || ( isset( $prop['type'] ) && 'object' === $prop['type'] && isset( $prop['properties'] ) ) ) {
				$rows = array_merge( $rows, $this->schema_rows( $prop, $full_name, $depth + 1 ) );
			}
		}

		return $rows;
	}

	/**
	 * Human type label: "string", "integer (int64)", "array of string", "string | null".
	 */
	protected function schema_type_label( array $schema ) {
		if ( isset( $schema['allOf'] ) ) {
			return 'object';
		}
		if ( isset( $schema['oneOf'] ) || isset( $schema['anyOf'] ) ) {
			return __( 'one of', 'betterdocs-pro' );
		}

		$type = isset( $schema['type'] ) ? $schema['type'] : 'object';

		if ( is_array( $type ) ) {
			return implode( ' | ', array_map( 'strval', $type ) );
		}

		if ( 'array' === $type ) {
			$items = isset( $schema['items'] ) && is_array( $schema['items'] ) ? $this->schema_type_label( $schema['items'] ) : '';
			return $items ? sprintf( 'array of %s', $items ) : 'array';
		}

		return isset( $schema['format'] ) ? sprintf( '%s (%s)', $type, $schema['format'] ) : (string) $type;
	}

	/* ---------------------------------------------------------------- */
	/* Block factories (pure, deterministic)                              */
	/* ---------------------------------------------------------------- */

	protected function block( $name, array $attrs, $html ) {
		return [
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => [],
			'innerHTML'    => $html,
			'innerContent' => [ $html ]
		];
	}

	protected function html_block( $html ) {
		return $this->block( 'core/html', [], "\n" . $html . "\n" );
	}

	protected function heading_block( $text, $level = 2, $class = '' ) {
		$attrs = 2 === $level ? [] : [ 'level' => $level ];
		if ( '' !== $class ) {
			$attrs['className'] = $class;
		}

		$class_attr = 'wp-block-heading' . ( '' !== $class ? ' ' . $class : '' );

		return $this->block(
			'core/heading',
			$attrs,
			sprintf( "\n<h%1\$d class=\"%2\$s\">%3\$s</h%1\$d>\n", (int) $level, esc_attr( $class_attr ), wp_kses_post( $text ) )
		);
	}

	protected function paragraph_block( $html ) {
		return $this->block( 'core/paragraph', [], "\n<p>" . wp_kses_post( $html ) . "</p>\n" );
	}

	protected function table_block( array $headers, array $rows ) {
		$thead = '';
		if ( array_filter( $headers ) ) {
			$cells = '';
			foreach ( $headers as $header ) {
				$cells .= '<th>' . wp_kses_post( $header ) . '</th>';
			}
			$thead = '<thead><tr>' . $cells . '</tr></thead>';
		}

		$tbody = '';
		foreach ( $rows as $row ) {
			$cells = '';
			foreach ( $row as $cell ) {
				$cells .= '<td>' . wp_kses_post( $cell ) . '</td>';
			}
			$tbody .= '<tr>' . $cells . '</tr>';
		}

		$html = "\n" . '<figure class="wp-block-table betterdocs-api-table"><table>' . $thead . '<tbody>' . $tbody . '</tbody></table></figure>' . "\n";

		return $this->block( 'core/table', [ 'className' => 'betterdocs-api-table' ], $html );
	}

	/**
	 * A betterdocs/code-snippet block (dynamic render), or core/code fallback
	 * when the snippet block isn't registered.
	 *
	 * @param string $code     Primary code.
	 * @param string $language Primary language.
	 * @param string $label    Header filename/title (shown for single-language).
	 * @param array  $variants Extra languages [ { language, codeContent } ] —
	 *                         when present, the block renders a language dropdown.
	 */
	protected function code_snippet_block( $code, $language, $label, array $variants = [] ) {
		if ( class_exists( '\WP_Block_Type_Registry' )
			&& \WP_Block_Type_Registry::get_instance()->is_registered( 'betterdocs/code-snippet' ) ) {
			$attrs = [
				'codeContent'       => $code,
				'language'          => $language,
				'showLanguageLabel' => true,
				'showCopyButton'    => true,
				'showLineNumbers'   => false,
				'theme'             => $this->code_theme,
				// Was passed but discarded before; now the header title (the
				// dropdown replaces it in multi-language mode).
				'fileName'          => (string) $label
			];

			if ( ! empty( $variants ) ) {
				$attrs['codeVariants'] = $variants;
			}

			return $this->block( 'betterdocs/code-snippet', $attrs, '' );
		}

		// core/code has no multi-language concept — stack the primary + variants.
		$html = "\n<pre class=\"wp-block-code\"><code>" . esc_html( $code ) . "</code></pre>\n";

		return $this->block( 'core/code', [], $html );
	}

	/**
	 * A betterdocs/code-snippet-tab block (dynamic render) — one status tab per
	 * entry — or a stacked core/code fallback when the block isn't registered.
	 *
	 * @param array $tabs [ { status, language, codeContent } ] in stable order.
	 * @return array Block array for serialize_blocks().
	 */
	protected function code_snippet_tab_block( array $tabs ) {
		if ( class_exists( '\WP_Block_Type_Registry' )
			&& \WP_Block_Type_Registry::get_instance()->is_registered( 'betterdocs/code-snippet-tab' ) ) {
			// Fixed key order → byte-stable serialized attrs (ADR-014).
			return $this->block(
				'betterdocs/code-snippet-tab',
				[
					'responses'       => $tabs,
					'showCopyButton'  => true,
					'showLineNumbers' => false,
					'theme'           => $this->code_theme
				],
				''
			);
		}

		// Fallback: stack each response as a labelled core/code block.
		$html = '';
		foreach ( $tabs as $tab ) {
			$status = isset( $tab['status'] ) ? (string) $tab['status'] : '';
			$html  .= "\n<pre class=\"wp-block-code\"><code>" . esc_html( ( '' !== $status ? $status . "\n" : '' ) . (string) $tab['codeContent'] ) . "</code></pre>\n";
		}

		return $this->block( 'core/code', [], $html );
	}

	/**
	 * Markdown → block list. Top-level <p>/<h*>/<ul>/<ol> become native
	 * blocks; anything else is kept verbatim in a core/html block.
	 *
	 * @param string $markdown
	 * @return array[]
	 */
	protected function markdown_blocks( $markdown ) {
		$html = $this->markdown->text( $markdown );
		$html = wp_kses_post( $html );

		if ( '' === trim( $html ) ) {
			return [];
		}

		$blocks = [];

		if ( ! preg_match_all( '#<(p|h[1-6]|ul|ol|pre|blockquote|table)\b[^>]*>.*?</\1>#s', $html, $matches, PREG_SET_ORDER ) ) {
			return [ $this->html_block( $html ) ];
		}

		foreach ( $matches as $match ) {
			$fragment = $match[0];
			$tag      = strtolower( $match[1] );

			if ( 'p' === $tag ) {
				$blocks[] = $this->block( 'core/paragraph', [], "\n" . $fragment . "\n" );
			} elseif ( 'ul' === $tag || 'ol' === $tag ) {
				$blocks[] = $this->block(
					'core/list',
					'ol' === $tag ? [ 'ordered' => true ] : [],
					"\n" . $fragment . "\n"
				);
			} elseif ( 0 === strpos( $tag, 'h' ) ) {
				$level    = (int) substr( $tag, 1 );
				$level    = max( 2, min( 6, $level ) ); // Never emit h1 inside content.
				$fragment = preg_replace( '#^<h[1-6]#', '<h' . $level . ' class="wp-block-heading"', $fragment );
				$fragment = preg_replace( '#</h[1-6]>$#', '</h' . $level . '>', $fragment );
				$blocks[] = $this->block( 'core/heading', 2 === $level ? [] : [ 'level' => $level ], "\n" . $fragment . "\n" );
			} else {
				$blocks[] = $this->html_block( $fragment );
			}
		}

		return $blocks;
	}
}
