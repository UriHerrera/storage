<?php
/**
 * Ingest API spec ability.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;
use WPDeveloper\BetterDocsPro\Abilities\Traits\TalksToApiDocs;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecFetcher;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecIngestor;

/**
 * Ingest an OpenAPI or Postman spec into an API reference. The agent passes the
 * raw spec text, or a `source_url` to fetch it from; a Postman collection is
 * converted to OpenAPI on the way in. This replaces any spec the reference
 * already had.
 *
 * The admin upload route takes a multipart file, which an MCP client cannot
 * send, so this ability accepts the spec as text (or a URL) and drives the same
 * ingestor the upload and URL-sync paths both funnel through.
 *
 * @since 4.9.1
 */
class IngestApiSpec extends ProAbility {

	use TalksToApiDocs;

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/ingest-api-spec';
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
			return AbilityError::invalid_input( 'id', __( 'Give the API reference id.', 'betterdocs-pro' ) );
		}

		$has_spec = isset( $input['spec'] ) && '' !== trim( (string) $input['spec'] );
		$has_url  = isset( $input['source_url'] ) && '' !== trim( (string) $input['source_url'] );

		if ( ! $has_spec && ! $has_url ) {
			return AbilityError::invalid_input( 'spec', __( 'Give the spec text, or a source_url to fetch it from.', 'betterdocs-pro' ) );
		}

		// Confirm the reference exists first, so a bad id is a clean not_found
		// rather than an orphaned spec row.
		$row = $this->api_call( 'GET', '/api-ref/' . $id, [], $id );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$container = betterdocs()->container;
		$format    = isset( $input['format'] ) && in_array( $input['format'], [ 'json', 'yaml' ], true ) ? (string) $input['format'] : null;
		$store_args = [];

		if ( $has_spec ) {
			$raw = (string) $input['spec'];
		} else {
			$max_bytes = (int) $container->get( ApiReferences::class )->max_spec_bytes();
			$fetched   = $container->get( SpecFetcher::class )->fetch( (string) $input['source_url'], '', '', $max_bytes );

			if ( is_wp_error( $fetched ) ) {
				return AbilityError::upstream( $fetched->get_error_message(), [ 'code' => (string) $fetched->get_error_code() ] );
			}

			$raw        = isset( $fetched['body'] ) ? (string) $fetched['body'] : '';
			$format     = isset( $fetched['format'] ) && in_array( $fetched['format'], [ 'json', 'yaml' ], true ) ? (string) $fetched['format'] : $format;
			$store_args = [ 'source_url' => (string) $input['source_url'] ];

			if ( '' === trim( $raw ) ) {
				return AbilityError::upstream( __( 'The source_url returned an empty spec.', 'betterdocs-pro' ) );
			}
		}

		$result = $container->get( SpecIngestor::class )->ingest( $id, $raw, $format, $store_args );

		if ( is_wp_error( $result ) ) {
			return $this->map_ingest_error( $result );
		}

		return [
			'id'          => $id,
			'result'      => isset( $result['result'] ) ? (string) $result['result'] : 'stored',
			'source_kind' => isset( $result['source_kind'] ) ? (string) $result['source_kind'] : 'openapi',
			'summary'     => isset( $result['summary'] ) && is_array( $result['summary'] ) ? $result['summary'] : null
		];
	}

	/**
	 * A spec that will not parse or validate is the caller's input to fix, so it
	 * comes back as `invalid_input` with the validator's pointers; anything else
	 * (storage failure) is `upstream`.
	 *
	 * @since 4.9.1
	 *
	 * @param \WP_Error $error Ingestor error.
	 * @return \WP_Error An AbilityError.
	 */
	protected function map_ingest_error( $error ) {
		$code = (string) $error->get_error_code();

		// Every spec-content problem — empty, unparseable, not an object, failed
		// validation, un-convertible Postman — is the caller's spec to fix, so it
		// comes back as invalid_input with the validator's pointers when present.
		// Only a storage failure is genuinely on our side (upstream).
		if ( 0 === strpos( $code, 'betterdocs_api_spec_' ) && 'betterdocs_api_spec_store_failed' !== $code ) {
			$data    = $error->get_error_data();
			$allowed = is_array( $data ) && isset( $data['errors'] ) ? $data['errors'] : null;

			return AbilityError::invalid_input( 'spec', $error->get_error_message(), $allowed );
		}

		return AbilityError::upstream( $error->get_error_message(), [ 'code' => $code ] );
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'ingest an API spec', 'betterdocs-pro' );
	}
}
