<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side request samples (cURL / JavaScript / PHP / Python) for one
 * OpenAPI operation, plus schema→example JSON synthesis.
 *
 * Output MUST be deterministic (stable ordering, no timestamps/randomness) —
 * the materializer's edit-detection hashes generated content (ADR-014).
 *
 * ## Escaping
 *
 * Every value interpolated below — URL, header names and values, method, body —
 * originates in the uploaded spec and is therefore attacker-controlled. These
 * samples exist to be COPIED AND PASTED into a terminal or editor, so a quote
 * that terminates its string early is a code-execution bug on the reader's
 * machine, not a cosmetic one: `--data '…'` with an example of `x' $(id) '`
 * becomes a shell substitution the moment it is pasted. wp_json_encode() does
 * not help — JSON escapes `"` and `\`, never `'`.
 *
 * So nothing is interpolated raw. Each language gets its own escaper (esc_sh,
 * esc_js, esc_php, esc_py, esc_cs, esc_java) matching the quoting style that
 * emitter actually uses, and header names/values are filtered to what HTTP
 * permits before they ever reach an emitter.
 */
class CodeSampleGenerator {
	/**
	 * Build the shared request context for an operation.
	 *
	 * @param string $method    Lowercase HTTP method.
	 * @param string $path      Templated path (/pets/{petId}).
	 * @param array  $operation Resolved operation object.
	 * @param array  $spec      Resolved-ish root (servers, securitySchemes).
	 * @return array{method:string,url:string,headers:array<string,string>,query:array<string,string>,body:?string}
	 */
	public function context( $method, $path, array $operation, array $spec ) {
		$server = isset( $spec['servers'][0]['url'] ) ? rtrim( (string) $spec['servers'][0]['url'], '/' ) : 'https://api.example.com';

		$headers = [];
		$query   = [];
		$body    = null;

		// Auth placeholder from the effective security requirement.
		$security = isset( $operation['security'] ) ? $operation['security'] : ( isset( $spec['security'] ) ? $spec['security'] : [] );
		$schemes  = isset( $spec['components']['securitySchemes'] ) ? $spec['components']['securitySchemes'] : [];

		if ( ! empty( $security ) && is_array( $security ) ) {
			$first = reset( $security );
			$name  = is_array( $first ) ? key( $first ) : null;

			if ( $name && isset( $schemes[ $name ] ) && is_array( $schemes[ $name ] ) ) {
				$scheme = $schemes[ $name ];
				$type   = isset( $scheme['type'] ) ? $scheme['type'] : '';

				if ( 'http' === $type && 'bearer' === strtolower( isset( $scheme['scheme'] ) ? $scheme['scheme'] : '' ) ) {
					$headers['Authorization'] = 'Bearer YOUR_TOKEN';
				} elseif ( 'http' === $type && 'basic' === strtolower( isset( $scheme['scheme'] ) ? $scheme['scheme'] : '' ) ) {
					$headers['Authorization'] = 'Basic BASE64_CREDENTIALS';
				} elseif ( 'apiKey' === $type ) {
					$key_name = isset( $scheme['name'] ) ? $scheme['name'] : 'X-Api-Key';
					if ( 'query' === ( isset( $scheme['in'] ) ? $scheme['in'] : 'header' ) ) {
						$query[ $key_name ] = 'YOUR_API_KEY';
					} else {
						$headers[ $key_name ] = 'YOUR_API_KEY';
					}
				} elseif ( 'oauth2' === $type || 'openIdConnect' === $type ) {
					$headers['Authorization'] = 'Bearer YOUR_TOKEN';
				}
			}
		}

		// Required query parameters with example values.
		if ( isset( $operation['parameters'] ) && is_array( $operation['parameters'] ) ) {
			foreach ( $operation['parameters'] as $param ) {
				if ( ! is_array( $param ) || 'query' !== ( isset( $param['in'] ) ? $param['in'] : '' ) || empty( $param['required'] ) ) {
					continue;
				}

				$name           = isset( $param['name'] ) ? (string) $param['name'] : '';
				$schema         = isset( $param['schema'] ) && is_array( $param['schema'] ) ? $param['schema'] : [];
				$query[ $name ] = $this->scalar_to_string( $this->example_from_schema( $schema ) );
			}
		}

		// JSON request body example. A media-level `example` (spec-authored or
		// AI-overlay-injected) beats schema synthesis.
		if ( isset( $operation['requestBody']['content']['application/json'] ) && is_array( $operation['requestBody']['content']['application/json'] ) ) {
			$media = $operation['requestBody']['content']['application/json'];

			if ( array_key_exists( 'example', $media ) ) {
				$headers['Content-Type'] = 'application/json';
				$body                    = $this->encode_example( $media['example'] );
			} elseif ( isset( $media['schema'] ) && is_array( $media['schema'] ) ) {
				$headers['Content-Type'] = 'application/json';
				$body                    = $this->encode_example( $this->example_from_schema( $media['schema'] ) );
			}
		}

		// Filter headers once, here, so no emitter can be the one that forgets.
		// An HTTP field name is a token (RFC 9110); anything else is not a header
		// the reader could send anyway, so dropping it loses nothing real. Values
		// keep their characters but lose the control codes that would split the
		// sample across lines.
		$clean = [];
		foreach ( $headers as $name => $value ) {
			$name = $this->safe_header_name( $name );

			if ( '' !== $name ) {
				$clean[ $name ] = $this->safe_header_value( $value );
			}
		}

		return [
			// Constrained rather than trusted: the method reaches the samples as
			// a bare word in several languages.
			'method'  => preg_replace( '/[^A-Z]/', '', strtoupper( (string) $method ) ) ?: 'GET',
			'url'     => $server . $path,
			'headers' => $clean,
			'query'   => $query,
			'body'    => $body
		];
	}

	/**
	 * An HTTP field name, or '' when the spec supplied something that is not one.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function safe_header_name( $name ) {
		$name = (string) $name;

		return preg_match( '/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name ) ? $name : '';
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected function safe_header_value( $value ) {
		// Drop C0/C1 controls (CR and LF above all — those are header injection
		// in a real client and line-breakage in a printed sample).
		return preg_replace( '/[\x00-\x1F\x7F-\x9F]/u', '', (string) $value );
	}

	/**
	 * Wrap a value as a POSIX shell single-quoted string.
	 *
	 * Single quotes are literal in the shell with exactly one exception — you
	 * cannot put a single quote inside them — so the standard trick is to close
	 * the string, emit an escaped quote, and reopen: `'` → `'\''`.
	 *
	 * @param string $value
	 * @return string Including the surrounding quotes.
	 */
	protected function esc_sh( $value ) {
		return "'" . str_replace( "'", "'\\''", (string) $value ) . "'";
	}

	/**
	 * Body of a single-quoted JavaScript string.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function esc_js( $value ) {
		return str_replace(
			[ '\\', "'", "\r", "\n", '</' ],
			[ '\\\\', "\\'", '\\r', '\\n', '<\\/' ],
			(string) $value
		);
	}

	/**
	 * Body of a single-quoted PHP string (only \ and ' are special).
	 *
	 * @param string $value
	 * @return string
	 */
	protected function esc_php( $value ) {
		return str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], (string) $value );
	}

	/**
	 * Body of a double-quoted Python string.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function esc_py( $value ) {
		return str_replace(
			[ '\\', '"', "\r", "\n" ],
			[ '\\\\', '\\"', '\\r', '\\n' ],
			(string) $value
		);
	}

	/**
	 * Body of a regular (non-verbatim) C# string.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function esc_cs( $value ) {
		return str_replace(
			[ '\\', '"', "\r", "\n" ],
			[ '\\\\', '\\"', '\\r', '\\n' ],
			(string) $value
		);
	}

	/**
	 * Body of a regular Java string literal.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function esc_java( $value ) {
		return str_replace(
			[ '\\', '"', "\r", "\n" ],
			[ '\\\\', '\\"', '\\r', '\\n' ],
			(string) $value
		);
	}

	/**
	 * Contents of a Java text block (`"""…"""`), which keeps real newlines.
	 *
	 * Backslash is still an escape character inside a text block, and a literal
	 * `"""` would close it early — so both need handling, but newlines must NOT
	 * be escaped or the block loses the formatting it exists for.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function esc_java_textblock( $value ) {
		$value = str_replace( '\\', '\\\\', (string) $value );

		return str_replace( '"""', '\\"\\"\\"', $value );
	}

	/**
	 * @param array $ctx From context().
	 * @return string
	 */
	public function curl( array $ctx ) {
		$url   = $this->url_with_query( $ctx );
		$lines = [ sprintf( "curl --request %s \\\n  --url %s", $ctx['method'], $this->esc_sh( $url ) ) ];

		foreach ( $ctx['headers'] as $name => $value ) {
			$lines[] = '  --header ' . $this->esc_sh( $name . ': ' . $value );
		}

		if ( null !== $ctx['body'] ) {
			$lines[] = '  --data ' . $this->esc_sh( $ctx['body'] );
		}

		return implode( " \\\n", $lines );
	}

	/**
	 * @param array $ctx
	 * @return string
	 */
	public function javascript( array $ctx ) {
		$options = [ sprintf( "  method: '%s'", $ctx['method'] ) ];

		if ( ! empty( $ctx['headers'] ) ) {
			$pairs = [];
			foreach ( $ctx['headers'] as $name => $value ) {
				$pairs[] = sprintf( "    '%s': '%s'", $this->esc_js( $name ), $this->esc_js( $value ) );
			}
			$options[] = "  headers: {\n" . implode( ",\n", $pairs ) . "\n  }";
		}

		if ( null !== $ctx['body'] ) {
			// The body is wp_json_encode() output, so it is already a valid JS
			// object literal; it is the surrounding quoted values that needed
			// escaping, not this.
			$options[] = '  body: JSON.stringify(' . $ctx['body'] . ')';
		}

		return sprintf(
			"const response = await fetch('%s', {\n%s\n});\n\nconst data = await response.json();\nconsole.log(data);",
			$this->esc_js( $this->url_with_query( $ctx ) ),
			implode( ",\n", $options )
		);
	}

	/**
	 * @param array $ctx
	 * @return string
	 */
	public function php( array $ctx ) {
		$headers = [];
		foreach ( $ctx['headers'] as $name => $value ) {
			$headers[] = sprintf( "  '%s'", $this->esc_php( $name . ': ' . $value ) );
		}

		$code  = "<?php\n\$curl = curl_init();\n\ncurl_setopt_array(\$curl, [\n";
		$code .= sprintf( "  CURLOPT_URL => '%s',\n", $this->esc_php( $this->url_with_query( $ctx ) ) );
		$code .= "  CURLOPT_RETURNTRANSFER => true,\n";
		$code .= sprintf( "  CURLOPT_CUSTOMREQUEST => '%s',\n", $ctx['method'] );

		if ( ! empty( $headers ) ) {
			$code .= "  CURLOPT_HTTPHEADER => [\n" . implode( ",\n", $headers ) . "\n  ],\n";
		}

		if ( null !== $ctx['body'] ) {
			$code .= sprintf( "  CURLOPT_POSTFIELDS => '%s',\n", $this->esc_php( $ctx['body'] ) );
		}

		$code .= "]);\n\n\$response = curl_exec(\$curl);\ncurl_close(\$curl);\n\necho \$response;";

		return $code;
	}

	/**
	 * @param array $ctx
	 * @return string
	 */
	public function python( array $ctx ) {
		$code = "import requests\n\n";

		$args = [ sprintf( '"%s"', $this->esc_py( $this->url_with_query( $ctx ) ) ) ];

		if ( ! empty( $ctx['headers'] ) ) {
			$pairs = [];
			foreach ( $ctx['headers'] as $name => $value ) {
				$pairs[] = sprintf( '    "%s": "%s"', $this->esc_py( $name ), $this->esc_py( $value ) );
			}
			$code .= "headers = {\n" . implode( ",\n", $pairs ) . "\n}\n\n";
			$args[] = 'headers=headers';
		}

		if ( null !== $ctx['body'] ) {
			$code .= 'payload = ' . $ctx['body'] . "\n\n";
			$args[] = 'json=payload';
		}

		$code .= sprintf(
			"response = requests.%s(%s)\n\nprint(response.json())",
			strtolower( $ctx['method'] ),
			implode( ', ', $args )
		);

		return $code;
	}

	/**
	 * @param array $ctx
	 * @return string
	 */
	public function csharp( array $ctx ) {
		$code = "using System.Net.Http;\n";
		if ( null !== $ctx['body'] ) {
			$code .= "using System.Text;\n";
		}
		$code .= "\nvar client = new HttpClient();\n";
		$code .= sprintf( "var request = new HttpRequestMessage(new HttpMethod(\"%s\"), \"%s\");\n", $ctx['method'], $this->esc_cs( $this->url_with_query( $ctx ) ) );

		foreach ( $ctx['headers'] as $name => $value ) {
			if ( 'content-type' === strtolower( $name ) ) {
				continue;
			}
			$code .= sprintf( "request.Headers.Add(\"%s\", \"%s\");\n", $this->esc_cs( $name ), $this->esc_cs( $value ) );
		}

		if ( null !== $ctx['body'] ) {
			// Verbatim string: doubling the quotes IS the escape here — a
			// verbatim literal has no backslash escapes, so it must not go
			// through esc_cs(). Keeps the pretty JSON's newlines.
			$code .= sprintf( "request.Content = new StringContent(@\"%s\", Encoding.UTF8, \"application/json\");\n", str_replace( '"', '""', $ctx['body'] ) );
		}

		$code .= "var response = await client.SendAsync(request);\n";
		$code .= "var body = await response.Content.ReadAsStringAsync();\nConsole.WriteLine(body);";

		return $code;
	}

	/**
	 * @param array $ctx
	 * @return string
	 */
	public function java( array $ctx ) {
		$code  = "import java.net.URI;\nimport java.net.http.*;\n\n";
		$code .= "HttpClient client = HttpClient.newHttpClient();\n";
		$code .= "HttpRequest request = HttpRequest.newBuilder()\n";
		$code .= sprintf( "    .uri(URI.create(\"%s\"))\n", $this->esc_java( $this->url_with_query( $ctx ) ) );

		foreach ( $ctx['headers'] as $name => $value ) {
			$code .= sprintf( "    .header(\"%s\", \"%s\")\n", $this->esc_java( $name ), $this->esc_java( $value ) );
		}

		$publisher = null !== $ctx['body']
			? sprintf( "HttpRequest.BodyPublishers.ofString(\"\"\"\n%s\n\"\"\")", $this->esc_java_textblock( $ctx['body'] ) )
			: 'HttpRequest.BodyPublishers.noBody()';

		$code .= sprintf( "    .method(\"%s\", %s)\n    .build();\n\n", $ctx['method'], $publisher );
		$code .= "HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());\n";
		$code .= "System.out.println(response.body());";

		return $code;
	}

	/**
	 * Synthesize an example value for a (resolved) schema.
	 * Preference: example → default → first enum → type placeholder.
	 *
	 * @param array $schema
	 * @param int   $depth
	 * @return mixed
	 */
	public function example_from_schema( array $schema, $depth = 0 ) {
		if ( $depth > 5 ) {
			return null;
		}

		foreach ( [ 'example', 'default' ] as $key ) {
			if ( array_key_exists( $key, $schema ) ) {
				return $schema[ $key ];
			}
		}

		if ( isset( $schema['enum'][0] ) ) {
			return $schema['enum'][0];
		}

		if ( isset( $schema['allOf'] ) && is_array( $schema['allOf'] ) ) {
			$merged = [];
			foreach ( $schema['allOf'] as $part ) {
				if ( is_array( $part ) ) {
					$value = $this->example_from_schema( $part, $depth + 1 );
					if ( is_array( $value ) ) {
						$merged = array_merge( $merged, $value );
					}
				}
			}
			return $merged;
		}

		foreach ( [ 'oneOf', 'anyOf' ] as $poly ) {
			if ( isset( $schema[ $poly ][0] ) && is_array( $schema[ $poly ][0] ) ) {
				return $this->example_from_schema( $schema[ $poly ][0], $depth + 1 );
			}
		}

		$type = isset( $schema['type'] ) ? $schema['type'] : null;
		// 3.1 union types: pick the first non-null.
		if ( is_array( $type ) ) {
			$non_null = array_values( array_diff( $type, [ 'null' ] ) );
			$type     = isset( $non_null[0] ) ? $non_null[0] : 'null';
		}

		switch ( $type ) {
			case 'object':
			default:
				if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
					$object = [];
					foreach ( $schema['properties'] as $name => $prop ) {
						if ( is_array( $prop ) ) {
							$object[ $name ] = $this->example_from_schema( $prop, $depth + 1 );
						}
					}
					return $object;
				}
				if ( 'object' === $type ) {
					return new \stdClass();
				}
				return null;

			case 'array':
				$items = isset( $schema['items'] ) && is_array( $schema['items'] ) ? $schema['items'] : [];
				return [ $this->example_from_schema( $items, $depth + 1 ) ];

			case 'string':
				$format = isset( $schema['format'] ) ? $schema['format'] : '';
				$map    = [
					'date-time' => '2024-01-15T09:30:00Z',
					'date'      => '2024-01-15',
					'uuid'      => '123e4567-e89b-12d3-a456-426614174000',
					'email'     => 'user@example.com',
					'uri'       => 'https://example.com',
					'binary'    => '<binary data>'
				];
				return isset( $map[ $format ] ) ? $map[ $format ] : 'string';

			case 'integer':
				return 1;

			case 'number':
				return 1.0;

			case 'boolean':
				return true;

			case 'null':
				return null;
		}
	}

	/**
	 * Pretty, stable JSON for embedding in samples.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function encode_example( $value ) {
		return (string) wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * @param array $ctx
	 * @return string
	 */
	protected function url_with_query( array $ctx ) {
		if ( empty( $ctx['query'] ) ) {
			return $ctx['url'];
		}

		$pairs = [];
		foreach ( $ctx['query'] as $name => $value ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( $value );
		}

		return $ctx['url'] . '?' . implode( '&', $pairs );
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function scalar_to_string( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		return 'value';
	}
}
