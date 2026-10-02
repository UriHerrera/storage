<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Structural OpenAPI 3.0/3.1 validation with human-readable, pointered errors.
 *
 * Deliberately not a full JSON-Schema validation of the OpenAPI meta-schema
 * (ADR-003) — it checks what the renderer and the Free endpoint cap rely on,
 * and rejects the dangerous stuff (external/file $refs) outright.
 */
class SpecValidator {
	/**
	 * HTTP methods an OpenAPI path item may define operations for.
	 *
	 * @var string[]
	 */
	const METHODS = [ 'get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace' ];

	/**
	 * Validate a parsed spec.
	 *
	 * @param array $spec
	 * @return array{valid: bool, errors: array<int, array{pointer: string, message: string}>}
	 */
	public function validate( array $spec ) {
		$errors = [];

		$openapi = isset( $spec['openapi'] ) ? (string) $spec['openapi'] : '';
		if ( '' === $openapi ) {
			$errors[] = $this->error( '/openapi', __( 'Missing the required "openapi" version field. Swagger 2.0 documents ("swagger": "2.0") are not supported — convert to OpenAPI 3.x first.', 'betterdocs-pro' ) );
		} elseif ( 0 !== strpos( $openapi, '3.' ) ) {
			$errors[] = $this->error(
				'/openapi',
				sprintf(
					/* translators: %s: the openapi version string found in the document */
					__( 'Unsupported OpenAPI version "%s" — only 3.0.x and 3.1.x are supported.', 'betterdocs-pro' ),
					$openapi
				)
			);
		}

		if ( empty( $spec['info'] ) || ! is_array( $spec['info'] ) ) {
			$errors[] = $this->error( '/info', __( 'Missing the required "info" object.', 'betterdocs-pro' ) );
		} else {
			if ( empty( $spec['info']['title'] ) ) {
				$errors[] = $this->error( '/info/title', __( 'Missing the required "info.title" field.', 'betterdocs-pro' ) );
			}
			if ( empty( $spec['info']['version'] ) ) {
				$errors[] = $this->error( '/info/version', __( 'Missing the required "info.version" field.', 'betterdocs-pro' ) );
			}
		}

		$has_paths    = isset( $spec['paths'] ) && is_array( $spec['paths'] );
		$has_webhooks = isset( $spec['webhooks'] ) && is_array( $spec['webhooks'] ); // OpenAPI 3.1

		if ( ! $has_paths && ! $has_webhooks ) {
			$errors[] = $this->error( '/paths', __( 'The document defines no "paths" (and no 3.1 "webhooks") — there is nothing to render.', 'betterdocs-pro' ) );
		}

		if ( $has_paths ) {
			$operation_ids = [];

			foreach ( $spec['paths'] as $path => $item ) {
				$path_pointer = '/paths/' . $this->escape( (string) $path );

				if ( '' === $path || '/' !== ( (string) $path )[0] ) {
					$errors[] = $this->error(
						$path_pointer,
						sprintf(
							/* translators: %s: the offending path key */
							__( 'Path "%s" must start with "/".', 'betterdocs-pro' ),
							$path
						)
					);
				}

				if ( ! is_array( $item ) ) {
					$errors[] = $this->error( $path_pointer, __( 'Path item must be an object.', 'betterdocs-pro' ) );
					continue;
				}

				foreach ( self::METHODS as $method ) {
					if ( ! isset( $item[ $method ] ) ) {
						continue;
					}

					$op_pointer = $path_pointer . '/' . $method;

					if ( ! is_array( $item[ $method ] ) ) {
						$errors[] = $this->error( $op_pointer, __( 'Operation must be an object.', 'betterdocs-pro' ) );
						continue;
					}

					if ( ! isset( $item[ $method ]['responses'] ) && 0 === strpos( (string) $openapi, '3.0' ) ) {
						$errors[] = $this->error( $op_pointer . '/responses', __( 'Operation is missing the "responses" object (required in OpenAPI 3.0).', 'betterdocs-pro' ) );
					}

					if ( isset( $item[ $method ]['operationId'] ) ) {
						$op_id = (string) $item[ $method ]['operationId'];

						if ( isset( $operation_ids[ $op_id ] ) ) {
							$errors[] = $this->error(
								$op_pointer . '/operationId',
								sprintf(
									/* translators: 1: duplicated operationId, 2: pointer of the first occurrence */
									__( 'Duplicate operationId "%1$s" (first used at %2$s).', 'betterdocs-pro' ),
									$op_id,
									$operation_ids[ $op_id ]
								)
							);
						} else {
							$operation_ids[ $op_id ] = $op_pointer;
						}
					}
				}
			}
		}

		$this->walk_refs( $spec, '', $errors );

		return [
			'valid'  => empty( $errors ),
			'errors' => $errors
		];
	}

	/**
	 * Reject $refs that leave the document: file://, absolute URLs, or
	 * relative-file references. v1.0 renders single-document specs only;
	 * Scalar resolves internal (#/…) refs client-side.
	 *
	 * @param array  $tree
	 * @param string $pointer
	 * @param array  $errors  By-ref error collector.
	 * @param int    $depth
	 * @return void
	 */
	protected function walk_refs( $tree, $pointer, &$errors, $depth = 0 ) {
		if ( $depth > 200 || count( $errors ) > 100 ) {
			return;
		}

		foreach ( $tree as $key => $value ) {
			$here = $pointer . '/' . $this->escape( (string) $key );

			if ( '$ref' === (string) $key && is_string( $value ) && 0 !== strpos( $value, '#' ) ) {
				$errors[] = $this->error(
					$here,
					sprintf(
						/* translators: %s: the external $ref target */
						__( 'External $ref "%s" is not supported — bundle the spec into a single document (e.g. with "redocly bundle" or "swagger-cli bundle") and re-upload.', 'betterdocs-pro' ),
						$value
					)
				);
			}

			if ( is_array( $value ) ) {
				$this->walk_refs( $value, $here, $errors, $depth + 1 );
			}
		}
	}

	/**
	 * @param string $pointer JSON pointer to the offending node.
	 * @param string $message Human-readable, translated message.
	 * @return array{pointer: string, message: string}
	 */
	protected function error( $pointer, $message ) {
		return [
			'pointer' => $pointer,
			'message' => $message
		];
	}

	/**
	 * JSON-pointer token escaping (RFC 6901): ~ → ~0, / → ~1.
	 *
	 * @param string $token
	 * @return string
	 */
	protected function escape( $token ) {
		return str_replace( [ '~', '/' ], [ '~0', '~1' ], $token );
	}
}
