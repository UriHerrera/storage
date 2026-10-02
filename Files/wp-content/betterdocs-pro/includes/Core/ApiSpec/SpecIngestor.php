<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

use WP_Error;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The shared spec ingestion pipeline: parse → validate → store → cache summary.
 *
 * Transport-agnostic (returns arrays / WP_Error, not HTTP responses) so both
 * the REST upload controller (Free) and the URL-sync fetcher (Pro) run the
 * exact same validation and storage path.
 */
class SpecIngestor {
	/**
	 * @var SpecParser
	 */
	protected $parser;

	/**
	 * @var SpecValidator
	 */
	protected $validator;

	/**
	 * @var SpecStore
	 */
	protected $store;

	/**
	 * @var SpecSummary
	 */
	protected $summary;

	/**
	 * @var PostmanConverter
	 */
	protected $postman;

	/**
	 * @var SwaggerConverter
	 */
	protected $swagger;

	public function __construct( SpecParser $parser, SpecValidator $validator, SpecStore $store, SpecSummary $summary, PostmanConverter $postman, SwaggerConverter $swagger ) {
		$this->parser    = $parser;
		$this->validator = $validator;
		$this->store     = $store;
		$this->summary   = $summary;
		$this->postman   = $postman;
		$this->swagger   = $swagger;
	}

	/**
	 * Ingest a raw spec for a reference.
	 *
	 * @param int         $reference_id
	 * @param string      $raw
	 * @param string|null $format     'json'|'yaml'|null (sniff).
	 * @param array       $store_args { source_url?, etag?, last_modified? }.
	 *
	 * @return array|WP_Error {
	 *     @type string $result  'stored'|'unchanged'
	 *     @type array  $summary Normalized spec summary.
	 *     @type array  $errors  Validation errors (only when WP_Error 'betterdocs_api_spec_invalid').
	 * }
	 */
	public function ingest( $reference_id, $raw, $format = null, $store_args = [] ) {
		$max_bytes = betterdocs()->container->get( ApiReferences::class )->max_spec_bytes();

		$parsed = $this->parser->parse( $raw, $format, $max_bytes );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		// Postman Collection → OpenAPI. Convert once here so everything
		// downstream (validate/store/summarize + the materializer/explorer)
		// sees a normal OpenAPI document. The converted JSON is what gets
		// stored, so re-parses never re-convert.
		$source_kind = 'openapi';
		if ( $this->postman->is_postman( $parsed['spec'] ) ) {
			$converted = $this->postman->convert( $parsed['spec'] );

			$encoded = wp_json_encode( $converted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( false === $encoded ) {
				return new WP_Error(
					'betterdocs_api_spec_postman_invalid',
					__( 'The Postman collection could not be converted to OpenAPI.', 'betterdocs-pro' )
				);
			}

			$parsed['spec']   = $converted;
			$parsed['format'] = 'json';
			$raw              = $encoded;
			$source_kind      = 'postman';
		}

		// Swagger / OpenAPI 2.0 → OpenAPI 3.0, on the same terms as Postman above:
		// converted once here, stored converted, so the validator and everything
		// downstream only ever see a 3.x document.
		if ( 'postman' !== $source_kind && $this->swagger->is_swagger( $parsed['spec'] ) ) {
			$converted = $this->swagger->convert( $parsed['spec'] );

			$encoded = wp_json_encode( $converted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( false === $encoded ) {
				return new WP_Error(
					'betterdocs_api_spec_swagger_invalid',
					__( 'The Swagger 2.0 document could not be converted to OpenAPI 3.', 'betterdocs-pro' )
				);
			}

			$parsed['spec']   = $converted;
			$parsed['format'] = 'json';
			$raw              = $encoded;
			$source_kind      = 'swagger';
		}

		$verdict = $this->validator->validate( $parsed['spec'] );

		if ( ! $verdict['valid'] ) {
			return new WP_Error(
				'betterdocs_api_spec_invalid',
				__( 'The spec failed validation.', 'betterdocs-pro' ),
				[ 'errors' => $verdict['errors'] ]
			);
		}

		$result = $this->store->save( $reference_id, $raw, $parsed['format'], $store_args );

		if ( 'error' === $result ) {
			return new WP_Error( 'betterdocs_api_spec_store_failed', __( 'The spec could not be stored.', 'betterdocs-pro' ) );
		}

		$summary = $this->summary->summarize( $parsed['spec'], $raw );
		update_post_meta( $reference_id, '_bd_api_spec_summary', $summary );

		$this->maybe_adopt_spec_title( $reference_id, $summary );

		// Remember whether this reference was imported from a Postman collection
		// (drives the admin "Postman"/"Swagger 2.0" badge). The stored spec is
		// always OpenAPI 3.
		update_post_meta( $reference_id, '_bd_api_source_kind', $source_kind );

		/**
		 * Fires after a spec is successfully ingested for a reference — the
		 * single trigger for downstream consumers (Pro's endpoint-docs
		 * materializer). Covers both the upload REST flow and Pro's URL sync,
		 * which funnel through this ingestor.
		 *
		 * @param int    $reference_id
		 * @param string $result       'stored' (new spec) | 'unchanged' (same hash).
		 * @param array  $summary      Normalized spec summary (incl. sha256 hash).
		 * @param string $source_kind  'openapi' | 'postman' | 'swagger'.
		 */
		do_action( 'betterdocs_api_spec_ingested', $reference_id, $result, $summary, $source_kind );

		return [
			'result'      => $result,
			'summary'     => $summary,
			'source_kind' => $source_kind
		];
	}

	/**
	 * Name a reference that was created without one from the spec's `info.title`.
	 *
	 * The Create drawer no longer requires a name, so the REST endpoint inserts
	 * the reference under a placeholder and sets `_bd_api_title_auto`. This is
	 * the first moment a real name is knowable, and it is the right place for it
	 * because every route that supplies a spec — upload, URL sync, Postman
	 * import — funnels through ingest().
	 *
	 * The flag is cleared on the first successful rename, so re-syncing a spec
	 * whose title later changes upstream never renames a reference behind the
	 * user's back, and a name they typed is never touched at all.
	 *
	 * @param int   $reference_id
	 * @param array $summary Normalized spec summary.
	 * @return void
	 */
	protected function maybe_adopt_spec_title( $reference_id, array $summary ) {
		if ( '1' !== (string) get_post_meta( $reference_id, '_bd_api_title_auto', true ) ) {
			return;
		}

		$title = isset( $summary['title'] ) ? sanitize_text_field( (string) $summary['title'] ) : '';

		if ( '' === $title ) {
			return;
		}

		// post_name goes along with it: the placeholder produced an "untitled-api"
		// slug, and the reference is seconds old, so nothing can be linking to it
		// yet. Passing the derived slug lets wp_insert_post() run its usual
		// uniqueness pass rather than leaving the placeholder slug in place.
		wp_update_post(
			[
				'ID'         => $reference_id,
				'post_title' => $title,
				'post_name'  => sanitize_title( $title )
			]
		);

		delete_post_meta( $reference_id, '_bd_api_title_auto' );
	}
}
