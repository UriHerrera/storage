<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts a Postman Collection (v2.1 / v2.0) into an OpenAPI 3.0.3 document
 * that the rest of the API-docs pipeline (SpecValidator, SpecSummary,
 * Materializer, OperationContentBuilder, CodeSampleGenerator, AiOverlay, the
 * Scalar explorer) consumes unchanged.
 *
 * The conversion runs ONCE at ingest (SpecIngestor), and the resulting OpenAPI
 * JSON is what gets stored + re-parsed downstream — so the whole read path stays
 * OpenAPI-only. It MUST be deterministic (document iteration order, no
 * timestamps/random) so the stored bytes are stable and the materializer's
 * content hashing doesn't churn (ADR-014).
 *
 * Scope: collection-only variable resolution (from the collection's own
 * `variable[]`), only local structures (no `$ref`s), best-effort body/response
 * synthesis from saved examples.
 */
class PostmanConverter {
	/**
	 * HTTP methods OpenAPI recognises on a path item.
	 *
	 * @var string[]
	 */
	const METHODS = [ 'get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace' ];

	/**
	 * Depth cap for schema inference from example values (billion-laughs guard).
	 */
	const INFER_MAX_DEPTH = 8;

	/**
	 * Collection variable name → value, built once per convert().
	 *
	 * @var array<string, string>
	 */
	protected $variables = [];

	/**
	 * securitySchemes accumulated across the walk (name → scheme object).
	 *
	 * @var array<string, array>
	 */
	protected $security_schemes = [];

	/**
	 * operationId → true, for deterministic de-duplication.
	 *
	 * @var array<string, bool>
	 */
	protected $seen_operation_ids = [];

	/**
	 * tag name → true, preserving first-seen order in $tag_list.
	 *
	 * @var array<string, bool>
	 */
	protected $seen_tags = [];

	/**
	 * Ordered [ { name, description? } ] tag definitions.
	 *
	 * @var array<int, array>
	 */
	protected $tag_list = [];

	/**
	 * Whether a parsed document looks like a Postman collection (not OpenAPI).
	 *
	 * @param array $spec
	 * @return bool
	 */
	public function is_postman( array $spec ) {
		// A real OpenAPI/Swagger doc wins outright.
		if ( isset( $spec['openapi'] ) || isset( $spec['swagger'] ) ) {
			return false;
		}

		if ( ! isset( $spec['item'] ) || ! is_array( $spec['item'] ) ) {
			return false;
		}

		$info = isset( $spec['info'] ) && is_array( $spec['info'] ) ? $spec['info'] : [];

		if ( isset( $info['_postman_id'] ) ) {
			return true;
		}

		if ( isset( $info['schema'] ) && is_string( $info['schema'] )
			&& false !== strpos( $info['schema'], 'schema.getpostman.com' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert a Postman collection array into an OpenAPI 3.0.3 array.
	 *
	 * @param array $collection
	 * @return array
	 */
	public function convert( array $collection ) {
		$this->variables         = $this->collect_variables( $collection );
		$this->security_schemes  = [];
		$this->seen_operation_ids = [];
		$this->seen_tags         = [];
		$this->tag_list          = [];

		$info = isset( $collection['info'] ) && is_array( $collection['info'] ) ? $collection['info'] : [];

		$openapi = [
			'openapi' => '3.0.3',
			'info'    => $this->build_info( $info ),
			'servers' => $this->build_servers( $collection ),
			'paths'   => []
		];

		// Collection-level auth → default security scheme for every operation
		// that doesn't declare its own.
		$default_security = $this->auth_to_security( isset( $collection['auth'] ) ? $collection['auth'] : null );

		$paths = [];
		$this->walk_items(
			isset( $collection['item'] ) && is_array( $collection['item'] ) ? $collection['item'] : [],
			'',
			$default_security,
			$paths
		);

		$openapi['paths'] = $paths;

		if ( $this->tag_list ) {
			$openapi['tags'] = $this->tag_list;
		}

		if ( $this->security_schemes ) {
			$openapi['components'] = [ 'securitySchemes' => $this->security_schemes ];
		}

		if ( $default_security ) {
			$openapi['security'] = $default_security;
		}

		return $openapi;
	}

	/* ------------------------------------------------------------------ */
	/* info / servers                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * @param array $info
	 * @return array
	 */
	protected function build_info( array $info ) {
		$title = isset( $info['name'] ) && '' !== trim( (string) $info['name'] )
			? (string) $info['name']
			: 'API';

		$out = [
			'title'   => $title,
			'version' => isset( $info['version'] ) && '' !== (string) $info['version']
				? (string) $info['version']
				: '1.0.0'
		];

		$description = $this->text_value( isset( $info['description'] ) ? $info['description'] : '' );
		if ( '' !== $description ) {
			$out['description'] = $description;
		}

		return $out;
	}

	/**
	 * Derive servers: a named base-URL collection variable, else the most common
	 * scheme+host across request URLs, else a {baseUrl} server variable.
	 *
	 * @param array $collection
	 * @return array
	 */
	protected function build_servers( array $collection ) {
		foreach ( [ 'baseUrl', 'base_url', 'basePath', 'host', 'url' ] as $name ) {
			if ( isset( $this->variables[ $name ] ) && '' !== $this->variables[ $name ] ) {
				$url = $this->resolve_vars( $this->variables[ $name ] );
				if ( '' !== $url ) {
					return [ [ 'url' => rtrim( $url, '/' ) ] ];
				}
			}
		}

		$hosts = [];
		$this->collect_hosts(
			isset( $collection['item'] ) && is_array( $collection['item'] ) ? $collection['item'] : [],
			$hosts
		);

		if ( $hosts ) {
			arsort( $hosts );
			$top = (string) key( $hosts );
			if ( '' !== $top ) {
				return [ [ 'url' => rtrim( $top, '/' ) ] ];
			}
		}

		// Unknown base URL — common when a Postman collection relies on an
		// environment we don't have. Emit an absolute placeholder (RFC 2606
		// example host) rather than a `{{var}}` template: a relative template
		// would resolve against the docs page origin in the Try-it panel and
		// silently hit the WordPress site. The reader can override the base URL
		// in the Try-it panel.
		return [ [ 'url' => 'https://api.example.com' ] ];
	}

	/**
	 * Recursively collect scheme://host(:port) frequencies from every request.
	 *
	 * @param array $items
	 * @param array $hosts By-ref frequency map.
	 * @return void
	 */
	protected function collect_hosts( array $items, array &$hosts ) {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( isset( $item['item'] ) && is_array( $item['item'] ) ) {
				$this->collect_hosts( $item['item'], $hosts );
				continue;
			}

			if ( ! isset( $item['request'] ) ) {
				continue;
			}

			$raw = $this->request_url_raw( $item['request'] );
			if ( '' === $raw ) {
				continue;
			}

			$resolved = $this->resolve_vars( $raw );
			$parts    = wp_parse_url( $resolved );
			if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
				continue;
			}

			$base = $parts['scheme'] . '://' . $parts['host'];
			if ( ! empty( $parts['port'] ) ) {
				$base .= ':' . $parts['port'];
			}

			$hosts[ $base ] = isset( $hosts[ $base ] ) ? $hosts[ $base ] + 1 : 1;
		}
	}

	/* ------------------------------------------------------------------ */
	/* item tree walk                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Walk the Postman item tree. Folders push a tag; leaf requests become
	 * operations keyed into $paths.
	 *
	 * @param array  $items
	 * @param string $tag              Current folder/tag name ('' at root).
	 * @param array  $default_security Collection-level security.
	 * @param array  $paths            By-ref OpenAPI paths accumulator.
	 * @return void
	 */
	protected function walk_items( array $items, $tag, array $default_security, array &$paths ) {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			// Folder: nested items. Its name is the tag for descendants.
			if ( isset( $item['item'] ) && is_array( $item['item'] ) ) {
				$folder = isset( $item['name'] ) ? (string) $item['name'] : $tag;
				$this->register_tag( $folder, $this->text_value( isset( $item['description'] ) ? $item['description'] : '' ) );
				$this->walk_items( $item['item'], $folder, $default_security, $paths );
				continue;
			}

			if ( ! isset( $item['request'] ) || ! is_array( $item['request'] ) ) {
				continue;
			}

			$this->add_operation( $item, $tag, $default_security, $paths );
		}
	}

	/**
	 * Build one operation from a leaf item and insert it at paths[path][method].
	 *
	 * @param array  $item
	 * @param string $tag
	 * @param array  $default_security
	 * @param array  $paths By-ref.
	 * @return void
	 */
	protected function add_operation( array $item, $tag, array $default_security, array &$paths ) {
		$request = $item['request'];
		$method  = strtolower( isset( $request['method'] ) ? (string) $request['method'] : 'get' );

		if ( ! in_array( $method, self::METHODS, true ) ) {
			return;
		}

		list( $path, $path_params, $query_params ) = $this->normalize_url( $request );

		if ( '' === $path ) {
			$path = '/';
		}

		$name = isset( $item['name'] ) ? (string) $item['name'] : strtoupper( $method ) . ' ' . $path;

		$operation = [
			'operationId' => $this->unique_operation_id( $name, $method, $path ),
			'summary'     => $name
		];

		if ( '' !== $tag ) {
			$operation['tags'] = [ $tag ];
		}

		$description = $this->text_value( isset( $request['description'] ) ? $request['description'] : '' );
		if ( '' !== $description ) {
			$operation['description'] = $description;
		}

		$parameters = array_merge(
			$path_params,
			$query_params,
			$this->header_params( isset( $request['header'] ) ? $request['header'] : [] )
		);
		if ( $parameters ) {
			$operation['parameters'] = $parameters;
		}

		$body = $this->request_body( isset( $request['body'] ) ? $request['body'] : null );
		if ( null !== $body ) {
			$operation['requestBody'] = $body;
		}

		$operation['responses'] = $this->responses( isset( $item['response'] ) ? $item['response'] : [] );

		// Per-request auth overrides the collection default.
		if ( isset( $request['auth'] ) ) {
			$security = $this->auth_to_security( $request['auth'] );
			if ( $security ) {
				$operation['security'] = $security;
			} elseif ( $this->is_noauth( $request['auth'] ) ) {
				$operation['security'] = []; // explicit "no auth"
			}
		}

		if ( ! isset( $paths[ $path ] ) ) {
			$paths[ $path ] = [];
		}

		// First operation wins a given method+path (deterministic).
		if ( ! isset( $paths[ $path ][ $method ] ) ) {
			$paths[ $path ][ $method ] = $operation;
		}
	}

	/* ------------------------------------------------------------------ */
	/* URL → path + parameters                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Normalise a Postman request URL into an OpenAPI templated path plus path
	 * and query parameters.
	 *
	 * @param array $request
	 * @return array{0:string,1:array,2:array} [ path, pathParams[], queryParams[] ]
	 */
	protected function normalize_url( array $request ) {
		$url = isset( $request['url'] ) ? $request['url'] : '';

		$segments   = [];
		$query      = [];
		$var_meta   = [];

		if ( is_array( $url ) ) {
			if ( isset( $url['path'] ) && is_array( $url['path'] ) ) {
				foreach ( $url['path'] as $seg ) {
					$segments[] = is_array( $seg ) ? '' : (string) $seg;
				}
			} elseif ( isset( $url['raw'] ) ) {
				$segments = $this->path_segments_from_raw( (string) $url['raw'] );
			}

			if ( isset( $url['query'] ) && is_array( $url['query'] ) ) {
				$query = $url['query'];
			}

			if ( isset( $url['variable'] ) && is_array( $url['variable'] ) ) {
				foreach ( $url['variable'] as $v ) {
					if ( is_array( $v ) && isset( $v['key'] ) ) {
						$var_meta[ (string) $v['key'] ] = $v;
					}
				}
			}
		} else {
			$raw      = (string) $url;
			$segments = $this->path_segments_from_raw( $raw );
			$query    = $this->query_from_raw( $raw );
		}

		// A leading `{{var}}` segment is the base URL host (e.g. `{{base_url}}`),
		// not a path parameter — it belongs to the server, so drop it from the
		// path (unresolved base-URL vars fall back to a {baseUrl} server).
		while ( ! empty( $segments ) && '' !== $segments[0]
			&& preg_match( '/^\{\{.*\}\}$/', $segments[0] ) ) {
			array_shift( $segments );
		}

		$path_params = [];
		$path_out    = '';

		foreach ( $segments as $seg ) {
			if ( '' === $seg ) {
				continue;
			}

			$param_name = $this->segment_param_name( $seg );

			if ( null !== $param_name ) {
				$path_out .= '/{' . $param_name . '}';
				$path_params[] = $this->path_param( $param_name, isset( $var_meta[ $param_name ] ) ? $var_meta[ $param_name ] : null );
			} else {
				$path_out .= '/' . $seg;
			}
		}

		return [ $path_out, $path_params, $this->query_params( $query ) ];
	}

	/**
	 * If a path segment is a Postman variable (`:id` or `{{id}}`), return the
	 * OpenAPI parameter name; else null (it is a literal segment).
	 *
	 * @param string $seg
	 * @return string|null
	 */
	protected function segment_param_name( $seg ) {
		if ( '' !== $seg && ':' === $seg[0] ) {
			return sanitize_key( substr( $seg, 1 ) );
		}

		if ( preg_match( '/^\{\{\s*([^}]+?)\s*\}\}$/', $seg, $m ) ) {
			return sanitize_key( $m[1] );
		}

		return null;
	}

	/**
	 * @param string     $name
	 * @param array|null $meta Postman url.variable entry.
	 * @return array
	 */
	protected function path_param( $name, $meta ) {
		$param = [
			'name'     => $name,
			'in'       => 'path',
			'required' => true,
			'schema'   => [ 'type' => 'string' ]
		];

		if ( is_array( $meta ) ) {
			$desc = $this->text_value( isset( $meta['description'] ) ? $meta['description'] : '' );
			if ( '' !== $desc ) {
				$param['description'] = $desc;
			}
			if ( isset( $meta['value'] ) && '' !== (string) $meta['value'] ) {
				$param['example'] = $this->resolve_vars( (string) $meta['value'] );
			}
		}

		return $param;
	}

	/**
	 * @param array $query Postman url.query entries.
	 * @return array OpenAPI query parameters.
	 */
	protected function query_params( array $query ) {
		$params = [];

		foreach ( $query as $q ) {
			if ( ! is_array( $q ) || ! isset( $q['key'] ) || '' === (string) $q['key'] ) {
				continue;
			}
			if ( ! empty( $q['disabled'] ) ) {
				continue;
			}

			$param = [
				'name'     => (string) $q['key'],
				'in'       => 'query',
				'required' => false,
				'schema'   => [ 'type' => 'string' ]
			];

			$desc = $this->text_value( isset( $q['description'] ) ? $q['description'] : '' );
			if ( '' !== $desc ) {
				$param['description'] = $desc;
			}
			if ( isset( $q['value'] ) && '' !== (string) $q['value'] ) {
				$param['example'] = $this->resolve_vars( (string) $q['value'] );
			}

			$params[] = $param;
		}

		return $params;
	}

	/**
	 * @param mixed $headers Postman request.header (array or raw string).
	 * @return array OpenAPI header parameters.
	 */
	protected function header_params( $headers ) {
		if ( ! is_array( $headers ) ) {
			return [];
		}

		$skip   = [ 'content-type', 'accept', 'authorization' ];
		$params = [];

		foreach ( $headers as $h ) {
			if ( ! is_array( $h ) || ! isset( $h['key'] ) || '' === (string) $h['key'] ) {
				continue;
			}
			if ( ! empty( $h['disabled'] ) ) {
				continue;
			}
			if ( in_array( strtolower( (string) $h['key'] ), $skip, true ) ) {
				continue;
			}

			$param = [
				'name'     => (string) $h['key'],
				'in'       => 'header',
				'required' => false,
				'schema'   => [ 'type' => 'string' ]
			];

			$desc = $this->text_value( isset( $h['description'] ) ? $h['description'] : '' );
			if ( '' !== $desc ) {
				$param['description'] = $desc;
			}
			if ( isset( $h['value'] ) && '' !== (string) $h['value'] ) {
				$param['example'] = $this->resolve_vars( (string) $h['value'] );
			}

			$params[] = $param;
		}

		return $params;
	}

	/* ------------------------------------------------------------------ */
	/* request body                                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * @param mixed $body Postman request.body.
	 * @return array|null OpenAPI requestBody, or null when there is no body.
	 */
	protected function request_body( $body ) {
		if ( ! is_array( $body ) || empty( $body['mode'] ) ) {
			return null;
		}

		$mode = (string) $body['mode'];

		if ( 'raw' === $mode ) {
			$raw = isset( $body['raw'] ) ? (string) $body['raw'] : '';
			if ( '' === trim( $raw ) ) {
				return null;
			}

			$language = '';
			if ( isset( $body['options']['raw']['language'] ) ) {
				$language = strtolower( (string) $body['options']['raw']['language'] );
			}

			$decoded = json_decode( $this->resolve_vars( $raw ), true );
			if ( ( 'json' === $language || '' === $language ) && JSON_ERROR_NONE === json_last_error() && ( is_array( $decoded ) ) ) {
				return $this->body_content( 'application/json', $this->infer_schema( $decoded ), $decoded );
			}

			$media = 'json' === $language ? 'application/json' : ( 'xml' === $language ? 'application/xml' : 'text/plain' );
			return $this->body_content( $media, [ 'type' => 'string' ], $this->resolve_vars( $raw ) );
		}

		if ( 'urlencoded' === $mode ) {
			$fields = isset( $body['urlencoded'] ) && is_array( $body['urlencoded'] ) ? $body['urlencoded'] : [];
			list( $schema, $example ) = $this->kv_schema( $fields );
			return $this->body_content( 'application/x-www-form-urlencoded', $schema, $example );
		}

		if ( 'formdata' === $mode ) {
			$fields = isset( $body['formdata'] ) && is_array( $body['formdata'] ) ? $body['formdata'] : [];
			list( $schema, $example ) = $this->kv_schema( $fields );
			return $this->body_content( 'multipart/form-data', $schema, $example );
		}

		if ( 'graphql' === $mode && isset( $body['graphql'] ) && is_array( $body['graphql'] ) ) {
			$example = [
				'query'     => isset( $body['graphql']['query'] ) ? (string) $body['graphql']['query'] : '',
				'variables' => isset( $body['graphql']['variables'] ) ? (string) $body['graphql']['variables'] : ''
			];
			return $this->body_content( 'application/json', $this->infer_schema( $example ), $example );
		}

		if ( 'file' === $mode ) {
			return $this->body_content( 'application/octet-stream', [ 'type' => 'string', 'format' => 'binary' ], null );
		}

		return null;
	}

	/**
	 * @param string $media
	 * @param array  $schema
	 * @param mixed  $example null = omit.
	 * @return array
	 */
	protected function body_content( $media, array $schema, $example ) {
		$content = [ 'schema' => $schema ];

		if ( null !== $example ) {
			$content['example'] = $example;
		}

		return [
			'content' => [ $media => $content ]
		];
	}

	/**
	 * Build an object schema + example from Postman key/value field lists
	 * (urlencoded / formdata).
	 *
	 * @param array $fields
	 * @return array{0:array,1:array} [ schema, example ]
	 */
	protected function kv_schema( array $fields ) {
		$properties = [];
		$example    = [];

		foreach ( $fields as $f ) {
			if ( ! is_array( $f ) || ! isset( $f['key'] ) || '' === (string) $f['key'] ) {
				continue;
			}
			if ( ! empty( $f['disabled'] ) ) {
				continue;
			}

			$key                = (string) $f['key'];
			$properties[ $key ] = [ 'type' => 'string' ];

			if ( isset( $f['value'] ) ) {
				$example[ $key ] = $this->resolve_vars( (string) $f['value'] );
			}
		}

		$schema = [ 'type' => 'object' ];
		if ( $properties ) {
			$schema['properties'] = $properties;
		}

		return [ $schema, $example ];
	}

	/* ------------------------------------------------------------------ */
	/* responses                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Build the responses object from Postman saved example responses.
	 *
	 * @param mixed $responses Postman item.response.
	 * @return array OpenAPI responses (always non-empty — 3.0 requires it).
	 */
	protected function responses( $responses ) {
		$out = [];

		if ( is_array( $responses ) ) {
			foreach ( $responses as $response ) {
				if ( ! is_array( $response ) ) {
					continue;
				}

				$code = isset( $response['code'] ) ? (string) (int) $response['code'] : '200';
				if ( '0' === $code ) {
					$code = '200';
				}

				$description = isset( $response['status'] ) && '' !== (string) $response['status']
					? (string) $response['status']
					: ( isset( $response['name'] ) ? (string) $response['name'] : 'Response' );

				$entry = [ 'description' => $description ];

				$raw = isset( $response['body'] ) ? (string) $response['body'] : '';
				if ( '' !== trim( $raw ) ) {
					$language = isset( $response['_postman_previewlanguage'] ) ? strtolower( (string) $response['_postman_previewlanguage'] ) : '';
					$decoded  = json_decode( $raw, true );

					if ( ( 'json' === $language || '' === $language ) && JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
						$entry['content'] = [
							'application/json' => [
								'schema'  => $this->infer_schema( $decoded ),
								'example' => $decoded
							]
						];
					} else {
						$media = 'xml' === $language ? 'application/xml' : 'text/plain';
						$entry['content'] = [
							$media => [
								'schema'  => [ 'type' => 'string' ],
								'example' => $raw
							]
						];
					}
				}

				// First example for a status code wins (deterministic).
				if ( ! isset( $out[ $code ] ) ) {
					$out[ $code ] = $entry;
				}
			}
		}

		if ( empty( $out ) ) {
			$out['200'] = [ 'description' => __( 'Successful response', 'betterdocs-pro' ) ];
		}

		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* auth                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Map a Postman auth object to an OpenAPI security requirement, registering
	 * the scheme in components on the way.
	 *
	 * @param mixed $auth
	 * @return array Security requirement list (possibly empty).
	 */
	protected function auth_to_security( $auth ) {
		if ( ! is_array( $auth ) || empty( $auth['type'] ) ) {
			return [];
		}

		$type = (string) $auth['type'];

		if ( 'noauth' === $type ) {
			return [];
		}

		$name   = '';
		$scheme = null;

		if ( 'bearer' === $type ) {
			$name   = 'bearerAuth';
			$scheme = [ 'type' => 'http', 'scheme' => 'bearer' ];
		} elseif ( 'basic' === $type ) {
			$name   = 'basicAuth';
			$scheme = [ 'type' => 'http', 'scheme' => 'basic' ];
		} elseif ( 'apikey' === $type ) {
			$props = $this->auth_params( $auth, 'apikey' );
			$name  = 'apiKeyAuth';
			$scheme = [
				'type' => 'apiKey',
				'in'   => isset( $props['in'] ) && in_array( $props['in'], [ 'header', 'query', 'cookie' ], true ) ? $props['in'] : 'header',
				'name' => isset( $props['key'] ) && '' !== $props['key'] ? $props['key'] : 'X-Api-Key'
			];
		} elseif ( 'oauth2' === $type ) {
			$name   = 'oauth2';
			$scheme = [ 'type' => 'oauth2', 'flows' => new \stdClass() ];
		} else {
			return [];
		}

		if ( $scheme && ! isset( $this->security_schemes[ $name ] ) ) {
			$this->security_schemes[ $name ] = $scheme;
		}

		return [ [ $name => [] ] ];
	}

	/**
	 * Flatten a Postman auth params array (`auth.apikey` is a list of
	 * {key,value,type}) into a key→value map.
	 *
	 * @param array  $auth
	 * @param string $key  Sub-key (e.g. 'apikey').
	 * @return array
	 */
	protected function auth_params( array $auth, $key ) {
		$out = [];

		if ( isset( $auth[ $key ] ) && is_array( $auth[ $key ] ) ) {
			foreach ( $auth[ $key ] as $entry ) {
				if ( is_array( $entry ) && isset( $entry['key'] ) ) {
					$out[ (string) $entry['key'] ] = isset( $entry['value'] ) ? (string) $entry['value'] : '';
				}
			}
		}

		return $out;
	}

	/**
	 * @param mixed $auth
	 * @return bool
	 */
	protected function is_noauth( $auth ) {
		return is_array( $auth ) && isset( $auth['type'] ) && 'noauth' === $auth['type'];
	}

	/* ------------------------------------------------------------------ */
	/* schema inference                                                    */
	/* ------------------------------------------------------------------ */

	/**
	 * Infer a minimal OpenAPI schema from a decoded example value.
	 *
	 * @param mixed $value
	 * @param int   $depth
	 * @return array
	 */
	protected function infer_schema( $value, $depth = 0 ) {
		if ( $depth >= self::INFER_MAX_DEPTH ) {
			return new \stdClass() === $value ? [ 'type' => 'object' ] : [];
		}

		if ( is_bool( $value ) ) {
			return [ 'type' => 'boolean' ];
		}
		if ( is_int( $value ) ) {
			return [ 'type' => 'integer' ];
		}
		if ( is_float( $value ) ) {
			return [ 'type' => 'number' ];
		}
		if ( is_string( $value ) ) {
			return [ 'type' => 'string' ];
		}
		if ( null === $value ) {
			return [ 'type' => 'string', 'nullable' => true ];
		}

		if ( is_array( $value ) ) {
			// List → array; associative → object.
			if ( $value === array_values( $value ) ) {
				$items = isset( $value[0] ) ? $this->infer_schema( $value[0], $depth + 1 ) : [ 'type' => 'string' ];
				return [ 'type' => 'array', 'items' => $items ];
			}

			$properties = [];
			foreach ( $value as $k => $v ) {
				$properties[ (string) $k ] = $this->infer_schema( $v, $depth + 1 );
			}

			$schema = [ 'type' => 'object' ];
			if ( $properties ) {
				$schema['properties'] = $properties;
			}
			return $schema;
		}

		return [ 'type' => 'string' ];
	}

	/* ------------------------------------------------------------------ */
	/* helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Build the collection variable map (name → resolved-ish value).
	 *
	 * @param array $collection
	 * @return array<string,string>
	 */
	protected function collect_variables( array $collection ) {
		$map = [];

		if ( isset( $collection['variable'] ) && is_array( $collection['variable'] ) ) {
			foreach ( $collection['variable'] as $var ) {
				if ( is_array( $var ) && isset( $var['key'] ) ) {
					$map[ (string) $var['key'] ] = isset( $var['value'] ) ? (string) $var['value'] : '';
				}
			}
		}

		return $map;
	}

	/**
	 * Replace `{{var}}` tokens with known collection-variable values; unknown
	 * tokens are left literal.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function resolve_vars( $text ) {
		if ( false === strpos( $text, '{{' ) ) {
			return $text;
		}

		$vars = $this->variables;

		return preg_replace_callback(
			'/\{\{\s*([^}]+?)\s*\}\}/',
			static function ( $m ) use ( $vars ) {
				$key = $m[1];
				return isset( $vars[ $key ] ) && '' !== $vars[ $key ] ? $vars[ $key ] : $m[0];
			},
			$text
		);
	}

	/**
	 * The raw URL string of a request (from string or object form).
	 *
	 * @param mixed $request
	 * @return string
	 */
	protected function request_url_raw( $request ) {
		if ( ! is_array( $request ) || ! isset( $request['url'] ) ) {
			return '';
		}

		$url = $request['url'];

		if ( is_string( $url ) ) {
			return $url;
		}

		if ( is_array( $url ) && isset( $url['raw'] ) ) {
			return (string) $url['raw'];
		}

		return '';
	}

	/**
	 * Path segments from a raw URL string (host + scheme + query stripped).
	 *
	 * @param string $raw
	 * @return string[]
	 */
	protected function path_segments_from_raw( $raw ) {
		$raw  = $this->resolve_vars( $raw );
		$path = (string) wp_parse_url( $raw, PHP_URL_PATH );

		if ( '' === $path ) {
			// No scheme/host — treat the pre-query part as the path.
			$path = preg_replace( '/\?.*$/', '', $raw );
		}

		$path = trim( (string) $path, '/' );
		if ( '' === $path ) {
			return [];
		}

		return explode( '/', $path );
	}

	/**
	 * Query entries ({key,value}) parsed from a raw URL string.
	 *
	 * @param string $raw
	 * @return array
	 */
	protected function query_from_raw( $raw ) {
		$query = (string) wp_parse_url( $this->resolve_vars( $raw ), PHP_URL_QUERY );
		if ( '' === $query ) {
			return [];
		}

		$out = [];
		foreach ( explode( '&', $query ) as $pair ) {
			if ( '' === $pair ) {
				continue;
			}
			$bits  = explode( '=', $pair, 2 );
			$out[] = [
				'key'   => urldecode( $bits[0] ),
				'value' => isset( $bits[1] ) ? urldecode( $bits[1] ) : ''
			];
		}

		return $out;
	}

	/**
	 * A Postman description may be a string or a `{content, type}` object.
	 * Postman descriptions are spec'd as Markdown, but some collections store
	 * HTML — and OperationContentBuilder renders descriptions as Markdown
	 * (Parsedown in safe mode escapes raw HTML), which would print the tags as
	 * literal text. So HTML-looking values are converted to Markdown here.
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function text_value( $value ) {
		if ( is_array( $value ) && isset( $value['content'] ) && is_string( $value['content'] ) ) {
			$value = $value['content'];
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/<(p|h[1-6]|pre|ul|ol|li|div|br|blockquote|code|table|a|html|head|body|span|strong|em)\b/i', $value ) ) {
			return $this->html_to_markdown( $value );
		}

		return $value;
	}

	/**
	 * Best-effort HTML → Markdown for description fields, via DOMDocument so the
	 * structure (headings, lists, code blocks, links, emphasis) survives into
	 * the Markdown pipeline instead of being escaped.
	 *
	 * @param string $html
	 * @return string
	 */
	protected function html_to_markdown( $html ) {
		// Drop any <head>…</head> (styles/scripts/meta) and unwrap the
		// document-structure tags so DOMDocument gets a clean body fragment.
		// A common Postman export is an empty skeleton (`<html><head></head>
		// <body></body></html>`) — after this it is empty → no description.
		$html = preg_replace( '#<head\b[^>]*>.*?</head>#is', '', (string) $html );
		$html = preg_replace( '#</?(?:html|body)\b[^>]*>#i', '', $html );
		$html = trim( $html );

		if ( '' === $html ) {
			return '';
		}

		if ( ! class_exists( '\DOMDocument' ) ) {
			return trim( wp_strip_all_tags( $html ) );
		}

		$doc  = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$doc->loadHTML(
			'<?xml encoding="utf-8"?><div>' . $html . '</div>',
			LIBXML_NOERROR | LIBXML_NOWARNING
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$root = $doc->getElementsByTagName( 'div' )->item( 0 );
		$md   = $root ? $this->node_to_markdown( $root ) : '';

		$md = preg_replace( "/[ \t]+\n/", "\n", (string) $md );
		$md = preg_replace( "/\n{3,}/", "\n\n", $md );

		return trim( $md );
	}

	/**
	 * Recursively render a DOM node's children as Markdown.
	 *
	 * @param \DOMNode $node
	 * @return string
	 */
	protected function node_to_markdown( \DOMNode $node ) {
		$out = '';

		foreach ( $node->childNodes as $child ) {
			if ( XML_TEXT_NODE === $child->nodeType ) {
				// Whitespace is insignificant outside <pre> — collapse runs.
				$out .= preg_replace( '/\s+/', ' ', $child->nodeValue );
				continue;
			}

			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$tag = strtolower( $child->nodeName );

			switch ( $tag ) {
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
					$level = (int) substr( $tag, 1 );
					$out  .= "\n\n" . str_repeat( '#', $level ) . ' ' . trim( $this->node_to_markdown( $child ) ) . "\n\n";
					break;

				case 'p':
				case 'div':
					$out .= "\n\n" . trim( $this->node_to_markdown( $child ) ) . "\n\n";
					break;

				case 'br':
					$out .= "\n";
					break;

				case 'strong':
				case 'b':
					$out .= '**' . trim( $this->node_to_markdown( $child ) ) . '**';
					break;

				case 'em':
				case 'i':
					$out .= '*' . trim( $this->node_to_markdown( $child ) ) . '*';
					break;

				case 'code':
					$out .= '`' . $child->textContent . '`';
					break;

				case 'pre':
					$code = $child->textContent;
					$lang = '';
					$inner = $child->getElementsByTagName( 'code' )->item( 0 );
					if ( $inner && preg_match( '/language-([\w-]+)/', $inner->getAttribute( 'class' ), $m ) ) {
						$lang = $m[1];
					}
					$out .= "\n\n```" . $lang . "\n" . rtrim( $code ) . "\n```\n\n";
					break;

				case 'ul':
					$out .= "\n" . $this->list_to_markdown( $child, false ) . "\n";
					break;

				case 'ol':
					$out .= "\n" . $this->list_to_markdown( $child, true ) . "\n";
					break;

				case 'a':
					$href = $child->getAttribute( 'href' );
					$text = trim( $this->node_to_markdown( $child ) );
					$out .= ( '' !== $href ) ? '[' . $text . '](' . $href . ')' : $text;
					break;

				case 'blockquote':
					$inner_md = trim( $this->node_to_markdown( $child ) );
					$out     .= "\n\n> " . str_replace( "\n", "\n> ", $inner_md ) . "\n\n";
					break;

				default:
					$out .= $this->node_to_markdown( $child );
			}
		}

		return $out;
	}

	/**
	 * Render a <ul>/<ol> node as a Markdown list.
	 *
	 * @param \DOMNode $list
	 * @param bool     $ordered
	 * @return string
	 */
	protected function list_to_markdown( \DOMNode $list, $ordered ) {
		$lines = '';
		$i     = 1;

		foreach ( $list->childNodes as $li ) {
			if ( XML_ELEMENT_NODE !== $li->nodeType || 'li' !== strtolower( $li->nodeName ) ) {
				continue;
			}

			$marker = $ordered ? ( $i . '. ' ) : '- ';
			$text   = trim( $this->node_to_markdown( $li ) );
			$text   = preg_replace( "/\n{2,}/", "\n", $text );      // no blank lines inside an item
			$text   = str_replace( "\n", "\n  ", $text );           // indent continuations

			$lines .= $marker . $text . "\n";
			$i++;
		}

		return $lines;
	}

	/**
	 * A stable, unique operationId derived from the item name.
	 *
	 * @param string $name
	 * @param string $method
	 * @param string $path
	 * @return string
	 */
	protected function unique_operation_id( $name, $method, $path ) {
		$base = sanitize_title( $name );

		if ( '' === $base ) {
			$base = sanitize_title( $method . '-' . $path );
		}
		if ( '' === $base ) {
			$base = 'operation';
		}

		$candidate = $base;
		$n         = 2;
		while ( isset( $this->seen_operation_ids[ $candidate ] ) ) {
			$candidate = $base . '-' . $n;
			$n++;
		}

		$this->seen_operation_ids[ $candidate ] = true;

		return $candidate;
	}

	/**
	 * Record a tag (first-seen order preserved) with an optional description.
	 *
	 * @param string $name
	 * @param string $description
	 * @return void
	 */
	protected function register_tag( $name, $description ) {
		$name = trim( (string) $name );
		if ( '' === $name || isset( $this->seen_tags[ $name ] ) ) {
			return;
		}

		$this->seen_tags[ $name ] = true;

		$tag = [ 'name' => $name ];
		if ( '' !== $description ) {
			$tag['description'] = $description;
		}

		$this->tag_list[] = $tag;
	}
}
