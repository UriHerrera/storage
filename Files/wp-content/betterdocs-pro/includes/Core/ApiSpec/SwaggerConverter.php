<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Swagger / OpenAPI 2.0 → OpenAPI 3.0 converter.
 *
 * Swagger 2.0 is still very common (the public Petstore `/v2`, most older
 * internal APIs), but every consumer in this feature — the endpoint-doc
 * builder, the spec summary, the Try-it panel — reads OpenAPI 3 shapes only.
 * Rather than teach each of them a second dialect, 2.0 documents are converted
 * once at ingest, exactly like Postman collections, and the converted document
 * is what gets stored. Nothing downstream ever sees a 2.0 document.
 *
 * Scope is the 2.0 surface that actually changes shape in 3.0:
 *
 * | 2.0                                   | 3.0                                          |
 * |---------------------------------------|----------------------------------------------|
 * | `schemes` + `host` + `basePath`       | `servers[].url`                              |
 * | `parameters[in=body]` + `consumes`    | `requestBody.content[<mime>].schema`         |
 * | `parameters[in=formData]`             | `requestBody.content[form mime].schema`      |
 * | non-body parameter type/format/items  | `parameter.schema`                           |
 * | `responses[].schema` + `produces`     | `responses[].content[<mime>].schema`         |
 * | `definitions`                         | `components.schemas`                         |
 * | `securityDefinitions`                 | `components.securitySchemes`                 |
 * | `type: file`                          | `type: string, format: binary`               |
 *
 * `$ref` pointers are rewritten in one pass at the end, after every fragment
 * has been moved, so a copied subtree never keeps a stale `#/definitions/…`.
 */
class SwaggerConverter {
	/**
	 * Parameter keys that describe the *value* in 2.0 and therefore belong
	 * inside `schema` in 3.0. Everything else stays on the parameter.
	 *
	 * @var string[]
	 */
	const SCHEMA_KEYS = [
		'type', 'format', 'items', 'enum', 'default', 'maximum', 'exclusiveMaximum',
		'minimum', 'exclusiveMinimum', 'maxLength', 'minLength', 'pattern', 'maxItems',
		'minItems', 'uniqueItems', 'multipleOf'
	];

	/**
	 * Is this a Swagger/OpenAPI 2.0 document?
	 *
	 * @param array $spec
	 * @return bool
	 */
	public function is_swagger( array $spec ) {
		if ( isset( $spec['openapi'] ) ) {
			return false;
		}

		return isset( $spec['swagger'] ) && 0 === strpos( (string) $spec['swagger'], '2.' );
	}

	/**
	 * @param array $spec A 2.0 document.
	 * @return array An OpenAPI 3.0 document.
	 */
	public function convert( array $spec ) {
		$out = [
			'openapi' => '3.0.3',
			'info'    => isset( $spec['info'] ) && is_array( $spec['info'] ) ? $spec['info'] : [
				'title'   => __( 'API', 'betterdocs-pro' ),
				'version' => '1.0.0'
			]
		];

		// info.version is required in 3.0 and merely conventional in 2.0.
		if ( ! isset( $out['info']['version'] ) || '' === (string) $out['info']['version'] ) {
			$out['info']['version'] = '1.0.0';
		}
		if ( ! isset( $out['info']['title'] ) || '' === (string) $out['info']['title'] ) {
			$out['info']['title'] = __( 'API', 'betterdocs-pro' );
		}

		$servers = $this->build_servers( $spec );
		if ( ! empty( $servers ) ) {
			$out['servers'] = $servers;
		}

		$global_consumes = $this->mimes( $spec, 'consumes', 'application/json' );
		$global_produces = $this->mimes( $spec, 'produces', 'application/json' );

		$out['paths'] = $this->build_paths( $spec, $global_consumes, $global_produces );

		$components = $this->build_components( $spec );
		if ( ! empty( $components ) ) {
			$out['components'] = $components;
		}

		foreach ( [ 'security', 'tags', 'externalDocs' ] as $passthrough ) {
			if ( isset( $spec[ $passthrough ] ) ) {
				$out[ $passthrough ] = $spec[ $passthrough ];
			}
		}

		return $this->rewrite_refs( $out );
	}

	/**
	 * `schemes` × `host` + `basePath` → `servers`.
	 *
	 * A 2.0 document may omit host (meaning "same host as the doc is served
	 * from"); with nothing to build a URL from, emit basePath alone as a
	 * relative server, which 3.0 allows.
	 *
	 * @param array $spec
	 * @return array
	 */
	protected function build_servers( array $spec ) {
		$host      = isset( $spec['host'] ) ? trim( (string) $spec['host'] ) : '';
		$base_path = isset( $spec['basePath'] ) ? trim( (string) $spec['basePath'] ) : '';
		$schemes   = isset( $spec['schemes'] ) && is_array( $spec['schemes'] ) ? $spec['schemes'] : [];

		if ( '' !== $base_path && '/' !== substr( $base_path, 0, 1 ) ) {
			$base_path = '/' . $base_path;
		}
		$base_path = rtrim( $base_path, '/' );

		if ( '' === $host ) {
			return '' === $base_path ? [] : [ [ 'url' => $base_path ] ];
		}

		// Prefer https when the document offers both.
		$schemes = array_values( array_filter( array_map( 'strval', $schemes ) ) );
		if ( empty( $schemes ) ) {
			$schemes = [ 'https' ];
		} elseif ( in_array( 'https', $schemes, true ) ) {
			$schemes = array_merge( [ 'https' ], array_diff( $schemes, [ 'https' ] ) );
		}

		$servers = [];
		foreach ( $schemes as $scheme ) {
			if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
				continue; // ws/wss have no meaning for the Try-it panel.
			}
			$servers[] = [ 'url' => $scheme . '://' . $host . $base_path ];
		}

		return $servers;
	}

	/**
	 * @param array    $spec
	 * @param string[] $global_consumes
	 * @param string[] $global_produces
	 * @return array
	 */
	protected function build_paths( array $spec, array $global_consumes, array $global_produces ) {
		$paths = [];

		if ( empty( $spec['paths'] ) || ! is_array( $spec['paths'] ) ) {
			return $paths;
		}

		$methods = [ 'get', 'put', 'post', 'delete', 'options', 'head', 'patch' ];

		foreach ( $spec['paths'] as $path => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$new_item = [];

			foreach ( $item as $key => $value ) {
				$lower = strtolower( (string) $key );

				if ( 'parameters' === $lower && is_array( $value ) ) {
					// Path-level parameters can't carry a body in 2.0, so this
					// only ever needs the non-body conversion.
					$split = $this->split_parameters( $value );
					if ( ! empty( $split['others'] ) ) {
						$new_item['parameters'] = $split['others'];
					}
					continue;
				}

				if ( ! in_array( $lower, $methods, true ) || ! is_array( $value ) ) {
					$new_item[ $key ] = $value;
					continue;
				}

				$new_item[ $lower ] = $this->build_operation( $value, $global_consumes, $global_produces );
			}

			$paths[ $path ] = $new_item;
		}

		return $paths;
	}

	/**
	 * @param array    $operation
	 * @param string[] $global_consumes
	 * @param string[] $global_produces
	 * @return array
	 */
	protected function build_operation( array $operation, array $global_consumes, array $global_produces ) {
		$consumes = $this->mimes( $operation, 'consumes', '' );
		$produces = $this->mimes( $operation, 'produces', '' );

		$consumes = ! empty( $consumes ) ? $consumes : $global_consumes;
		$produces = ! empty( $produces ) ? $produces : $global_produces;

		$out = $operation;
		unset( $out['consumes'], $out['produces'], $out['parameters'], $out['responses'] );

		$split = $this->split_parameters( isset( $operation['parameters'] ) ? (array) $operation['parameters'] : [] );

		if ( ! empty( $split['others'] ) ) {
			$out['parameters'] = $split['others'];
		}

		$body = $this->build_request_body( $split['body'], $split['form'], $consumes );
		if ( null !== $body ) {
			$out['requestBody'] = $body;
		}

		$out['responses'] = $this->build_responses(
			isset( $operation['responses'] ) ? (array) $operation['responses'] : [],
			$produces
		);

		return $out;
	}

	/**
	 * Sort a 2.0 parameter list into the body parameter, the formData
	 * parameters, and everything else (already converted to 3.0 shape).
	 *
	 * @param array $parameters
	 * @return array{body: ?array, form: array, others: array}
	 */
	protected function split_parameters( array $parameters ) {
		$body   = null;
		$form   = [];
		$others = [];

		foreach ( $parameters as $param ) {
			if ( ! is_array( $param ) ) {
				continue;
			}

			// A `$ref` to a shared parameter can't be inspected here; components
			// conversion handles the target, and the pointer is rewritten later.
			if ( isset( $param['$ref'] ) ) {
				$others[] = $param;
				continue;
			}

			$in = isset( $param['in'] ) ? (string) $param['in'] : '';

			if ( 'body' === $in ) {
				$body = $param;
				continue;
			}

			if ( 'formData' === $in ) {
				$form[] = $param;
				continue;
			}

			$others[] = $this->convert_parameter( $param );
		}

		return [
			'body'   => $body,
			'form'   => $form,
			'others' => $others
		];
	}

	/**
	 * Move a non-body parameter's value keywords into `schema`.
	 *
	 * @param array $param
	 * @return array
	 */
	protected function convert_parameter( array $param ) {
		$schema = [];

		foreach ( self::SCHEMA_KEYS as $key ) {
			if ( array_key_exists( $key, $param ) ) {
				$schema[ $key ] = $param[ $key ];
				unset( $param[ $key ] );
			}
		}

		// `collectionFormat` became style/explode.
		if ( isset( $param['collectionFormat'] ) ) {
			$format = (string) $param['collectionFormat'];
			unset( $param['collectionFormat'] );

			$map = [
				'csv'   => [ 'form', false ],
				'ssv'   => [ 'spaceDelimited', false ],
				'pipes' => [ 'pipeDelimited', false ],
				'multi' => [ 'form', true ]
			];

			if ( isset( $map[ $format ] ) ) {
				$param['style']   = $map[ $format ][0];
				$param['explode'] = $map[ $format ][1];
			}
		}

		if ( ! empty( $schema ) ) {
			$param['schema'] = $this->convert_schema( $schema );
		}

		return $param;
	}

	/**
	 * body / formData parameters → a 3.0 requestBody.
	 *
	 * @param array|null $body
	 * @param array      $form
	 * @param string[]   $consumes
	 * @return array|null
	 */
	protected function build_request_body( $body, array $form, array $consumes ) {
		if ( null !== $body ) {
			$schema = isset( $body['schema'] ) && is_array( $body['schema'] ) ? $body['schema'] : [];

			$content = [];
			foreach ( $consumes as $mime ) {
				$content[ $mime ] = [ 'schema' => $this->convert_schema( $schema ) ];
			}

			if ( empty( $content ) ) {
				$content = [ 'application/json' => [ 'schema' => $this->convert_schema( $schema ) ] ];
			}

			$request_body = [ 'content' => $content ];

			if ( isset( $body['description'] ) ) {
				$request_body['description'] = $body['description'];
			}
			if ( ! empty( $body['required'] ) ) {
				$request_body['required'] = true;
			}

			return $request_body;
		}

		if ( empty( $form ) ) {
			return null;
		}

		$properties = [];
		$required   = [];
		$has_file   = false;

		foreach ( $form as $param ) {
			$name = isset( $param['name'] ) ? (string) $param['name'] : '';
			if ( '' === $name ) {
				continue;
			}

			$schema = [];
			foreach ( self::SCHEMA_KEYS as $key ) {
				if ( array_key_exists( $key, $param ) ) {
					$schema[ $key ] = $param[ $key ];
				}
			}

			if ( isset( $param['description'] ) ) {
				$schema['description'] = $param['description'];
			}

			if ( isset( $schema['type'] ) && 'file' === $schema['type'] ) {
				$has_file = true;
			}

			$properties[ $name ] = $this->convert_schema( $schema );

			if ( ! empty( $param['required'] ) ) {
				$required[] = $name;
			}
		}

		if ( empty( $properties ) ) {
			return null;
		}

		// A file part forces multipart; otherwise honour an explicit form mime
		// from `consumes`, defaulting to urlencoded.
		$mime = 'application/x-www-form-urlencoded';
		if ( $has_file ) {
			$mime = 'multipart/form-data';
		} else {
			foreach ( $consumes as $candidate ) {
				if ( 'multipart/form-data' === $candidate || 'application/x-www-form-urlencoded' === $candidate ) {
					$mime = $candidate;
					break;
				}
			}
		}

		$schema = [
			'type'       => 'object',
			'properties' => $properties
		];

		if ( ! empty( $required ) ) {
			$schema['required'] = $required;
		}

		return [ 'content' => [ $mime => [ 'schema' => $schema ] ] ];
	}

	/**
	 * @param array    $responses
	 * @param string[] $produces
	 * @return array
	 */
	protected function build_responses( array $responses, array $produces ) {
		$out = [];

		foreach ( $responses as $status => $response ) {
			if ( ! is_array( $response ) ) {
				continue;
			}

			$out[ $status ] = $this->convert_response( $response, $produces );
		}

		// `responses` is required in 3.0.
		if ( empty( $out ) ) {
			$out['default'] = [ 'description' => __( 'Successful response.', 'betterdocs-pro' ) ];
		}

		return $out;
	}

	/**
	 * @param array    $response
	 * @param string[] $produces
	 * @return array
	 */
	protected function convert_response( array $response, array $produces ) {
		if ( isset( $response['$ref'] ) ) {
			return $response;
		}

		$out = $response;
		unset( $out['schema'], $out['examples'] );

		// `description` is required in 3.0.
		if ( ! isset( $out['description'] ) || '' === (string) $out['description'] ) {
			$out['description'] = __( 'Response', 'betterdocs-pro' );
		}

		$mimes = ! empty( $produces ) ? $produces : [ 'application/json' ];

		if ( isset( $response['schema'] ) && is_array( $response['schema'] ) ) {
			$schema  = $this->convert_schema( $response['schema'] );
			$content = [];
			foreach ( $mimes as $mime ) {
				$content[ $mime ] = [ 'schema' => $schema ];
			}
			$out['content'] = $content;
		}

		// 2.0 examples are keyed by mime at the response level; 3.0 puts a single
		// `example` inside the matching media type.
		if ( isset( $response['examples'] ) && is_array( $response['examples'] ) ) {
			foreach ( $response['examples'] as $mime => $example ) {
				$mime = (string) $mime;
				if ( ! isset( $out['content'][ $mime ] ) ) {
					$out['content'][ $mime ] = [];
				}
				$out['content'][ $mime ]['example'] = $example;
			}
		}

		if ( isset( $out['headers'] ) && is_array( $out['headers'] ) ) {
			foreach ( $out['headers'] as $name => $header ) {
				if ( is_array( $header ) ) {
					$out['headers'][ $name ] = $this->convert_parameter( $header );
				}
			}
		}

		return $out;
	}

	/**
	 * definitions / securityDefinitions / shared parameters + responses →
	 * `components`.
	 *
	 * @param array $spec
	 * @return array
	 */
	protected function build_components( array $spec ) {
		$components = [];

		if ( ! empty( $spec['definitions'] ) && is_array( $spec['definitions'] ) ) {
			$schemas = [];
			foreach ( $spec['definitions'] as $name => $schema ) {
				$schemas[ $name ] = is_array( $schema ) ? $this->convert_schema( $schema ) : $schema;
			}
			$components['schemas'] = $schemas;
		}

		if ( ! empty( $spec['parameters'] ) && is_array( $spec['parameters'] ) ) {
			$parameters = [];
			foreach ( $spec['parameters'] as $name => $param ) {
				if ( ! is_array( $param ) ) {
					continue;
				}
				// A shared body parameter has no 3.0 equivalent under
				// components.parameters; it becomes a requestBody instead.
				$in = isset( $param['in'] ) ? (string) $param['in'] : '';
				if ( 'body' === $in || 'formData' === $in ) {
					continue;
				}
				$parameters[ $name ] = $this->convert_parameter( $param );
			}
			if ( ! empty( $parameters ) ) {
				$components['parameters'] = $parameters;
			}
		}

		if ( ! empty( $spec['responses'] ) && is_array( $spec['responses'] ) ) {
			$responses = [];
			foreach ( $spec['responses'] as $name => $response ) {
				if ( is_array( $response ) ) {
					$responses[ $name ] = $this->convert_response( $response, [ 'application/json' ] );
				}
			}
			if ( ! empty( $responses ) ) {
				$components['responses'] = $responses;
			}
		}

		$schemes = $this->build_security_schemes( $spec );
		if ( ! empty( $schemes ) ) {
			$components['securitySchemes'] = $schemes;
		}

		return $components;
	}

	/**
	 * `securityDefinitions` → `components.securitySchemes`.
	 *
	 * @param array $spec
	 * @return array
	 */
	protected function build_security_schemes( array $spec ) {
		if ( empty( $spec['securityDefinitions'] ) || ! is_array( $spec['securityDefinitions'] ) ) {
			return [];
		}

		$out = [];

		foreach ( $spec['securityDefinitions'] as $name => $scheme ) {
			if ( ! is_array( $scheme ) ) {
				continue;
			}

			$type = isset( $scheme['type'] ) ? (string) $scheme['type'] : '';

			if ( 'basic' === $type ) {
				// 2.0's only http scheme became http+scheme in 3.0.
				$converted = [ 'type' => 'http', 'scheme' => 'basic' ];
				if ( isset( $scheme['description'] ) ) {
					$converted['description'] = $scheme['description'];
				}
				$out[ $name ] = $converted;
				continue;
			}

			if ( 'apiKey' === $type ) {
				$out[ $name ] = $scheme;
				continue;
			}

			if ( 'oauth2' === $type ) {
				$flow_name = isset( $scheme['flow'] ) ? (string) $scheme['flow'] : '';

				// 2.0 flow names were renamed in 3.0.
				$flow_map = [
					'implicit'    => 'implicit',
					'password'    => 'password',
					'application' => 'clientCredentials',
					'accessCode'  => 'authorizationCode'
				];

				$flow_key = isset( $flow_map[ $flow_name ] ) ? $flow_map[ $flow_name ] : 'implicit';

				$flow = [ 'scopes' => isset( $scheme['scopes'] ) && is_array( $scheme['scopes'] ) ? $scheme['scopes'] : [] ];

				if ( isset( $scheme['authorizationUrl'] ) && in_array( $flow_key, [ 'implicit', 'authorizationCode' ], true ) ) {
					$flow['authorizationUrl'] = $scheme['authorizationUrl'];
				}
				if ( isset( $scheme['tokenUrl'] ) && in_array( $flow_key, [ 'password', 'clientCredentials', 'authorizationCode' ], true ) ) {
					$flow['tokenUrl'] = $scheme['tokenUrl'];
				}

				$converted = [
					'type'  => 'oauth2',
					'flows' => [ $flow_key => $flow ]
				];

				if ( isset( $scheme['description'] ) ) {
					$converted['description'] = $scheme['description'];
				}

				$out[ $name ] = $converted;
				continue;
			}

			$out[ $name ] = $scheme;
		}

		return $out;
	}

	/**
	 * Schema-level 2.0 → 3.0 differences, applied recursively.
	 *
	 * @param array $schema
	 * @param int   $depth
	 * @return array
	 */
	protected function convert_schema( array $schema, $depth = 0 ) {
		if ( $depth > 64 ) {
			return $schema;
		}

		// `type: file` only ever existed for formData/response bodies.
		if ( isset( $schema['type'] ) && 'file' === $schema['type'] ) {
			$schema['type']   = 'string';
			$schema['format'] = 'binary';
		}

		// 2.0 allowed `x-nullable`; 3.0 has `nullable`.
		if ( isset( $schema['x-nullable'] ) && ! isset( $schema['nullable'] ) ) {
			$schema['nullable'] = (bool) $schema['x-nullable'];
			unset( $schema['x-nullable'] );
		}

		// `exclusiveMinimum`/`exclusiveMaximum` are booleans in 2.0 and numbers
		// in 3.1 — 3.0 keeps the boolean form, so they pass through untouched.

		foreach ( [ 'properties', 'definitions', 'patternProperties' ] as $map_key ) {
			if ( isset( $schema[ $map_key ] ) && is_array( $schema[ $map_key ] ) ) {
				foreach ( $schema[ $map_key ] as $name => $child ) {
					if ( is_array( $child ) ) {
						$schema[ $map_key ][ $name ] = $this->convert_schema( $child, $depth + 1 );
					}
				}
			}
		}

		foreach ( [ 'items', 'additionalProperties', 'not' ] as $single_key ) {
			if ( isset( $schema[ $single_key ] ) && is_array( $schema[ $single_key ] ) ) {
				$schema[ $single_key ] = $this->convert_schema( $schema[ $single_key ], $depth + 1 );
			}
		}

		foreach ( [ 'allOf', 'anyOf', 'oneOf' ] as $list_key ) {
			if ( isset( $schema[ $list_key ] ) && is_array( $schema[ $list_key ] ) ) {
				foreach ( $schema[ $list_key ] as $i => $child ) {
					if ( is_array( $child ) ) {
						$schema[ $list_key ][ $i ] = $this->convert_schema( $child, $depth + 1 );
					}
				}
			}
		}

		return $schema;
	}

	/**
	 * Rewrite every 2.0 `$ref` pointer to its 3.0 location, in one pass over the
	 * finished document.
	 *
	 * @param mixed $node
	 * @param int   $depth
	 * @return mixed
	 */
	protected function rewrite_refs( $node, $depth = 0 ) {
		if ( ! is_array( $node ) || $depth > 128 ) {
			return $node;
		}

		$map = [
			'#/definitions/' => '#/components/schemas/',
			'#/parameters/'  => '#/components/parameters/',
			'#/responses/'   => '#/components/responses/'
		];

		foreach ( $node as $key => $value ) {
			if ( '$ref' === $key && is_string( $value ) ) {
				foreach ( $map as $from => $to ) {
					if ( 0 === strpos( $value, $from ) ) {
						$node[ $key ] = $to . substr( $value, strlen( $from ) );
						break;
					}
				}
				continue;
			}

			if ( is_array( $value ) ) {
				$node[ $key ] = $this->rewrite_refs( $value, $depth + 1 );
			}
		}

		return $node;
	}

	/**
	 * Read a mime list (`consumes`/`produces`), falling back to `$default`.
	 *
	 * @param array  $source
	 * @param string $key
	 * @param string $default Empty string means "no fallback".
	 * @return string[]
	 */
	protected function mimes( array $source, $key, $default ) {
		$mimes = [];

		if ( isset( $source[ $key ] ) && is_array( $source[ $key ] ) ) {
			foreach ( $source[ $key ] as $mime ) {
				$mime = trim( (string) $mime );
				if ( '' !== $mime ) {
					$mimes[] = $mime;
				}
			}
		}

		if ( empty( $mimes ) && '' !== $default ) {
			$mimes[] = $default;
		}

		return array_values( array_unique( $mimes ) );
	}
}
