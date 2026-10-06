<?php
/**
 * Update a doc category or doc tag ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Terms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;

/**
 * Rename a term, move it, re-describe it, or change which knowledge bases a
 * doc category belongs to.
 *
 * `knowledge_bases` **replaces** the list, so `[]` clears it — the same rule the
 * Docs tools use for term arrays, and the reason it is spelled out in the field
 * description rather than left to be discovered.
 *
 * Changing a doc category's knowledge bases changes its **permalink**: the KB
 * slug is a path segment (`/docs/<kb>/<category>/`). Worth knowing before an
 * agent tidies up somebody's taxonomy.
 *
 * @since 4.9.0
 */
class UpdateTerm extends CreateTerm {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		parent::__construct();

		$this->id          = 'betterdocs/update-term';
		$this->label       = __( 'Update term', 'betterdocs' );
		$this->description = __( 'Update a BetterDocs doc category or doc tag by id. Only the fields you send change. knowledge_bases REPLACES the list a doc category belongs to — send [] to clear it. Changing it also changes the category permalink, because the knowledge base slug is part of the URL.', 'betterdocs' );
		$this->capability  = 'edit_doc_terms';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 2.0,
			'openWorldHint' => false
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {
		$schema = parent::get_input_schema();

		$schema['required'] = [ 'taxonomy', 'id' ];

		$schema['properties']['name']['description'] = __( 'A new name. Omit to leave it alone.', 'betterdocs' );

		$schema['properties']['knowledge_bases']['description'] = __( 'REPLACES the knowledge bases this doc category belongs to, by id, slug or name. Send [] to clear it, omit to leave it alone. Never created here.', 'betterdocs' );

		$schema['properties'] = array_merge(
			[
				'taxonomy' => $schema['properties']['taxonomy'],
				'id'       => [
					'type'        => 'integer',
					'description' => __( 'The term id to update. Required.', 'betterdocs' )
				]
			],
			$schema['properties']
		);

		return $schema;
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$taxonomy = isset( $input['taxonomy'] ) ? (string) $input['taxonomy'] : '';

		// Before anything is read or written: a taxonomy that is switched off
		// answers with the setting to change, not with `not_found` (ADR-061).
		$available = $this->taxonomy_available( $taxonomy );

		if ( is_wp_error( $available ) ) {
			return $available;
		}

		$term = $this->require_term( isset( $input['id'] ) ? $input['id'] : 0, $taxonomy );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$params = $this->build_params( $input, $taxonomy );

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// Before the rename is written, so a knowledge base that cannot be
		// assigned does not leave the term half-updated.
		$kb_slugs = $this->resolve_kb_input( $input, $taxonomy );

		if ( is_wp_error( $kb_slugs ) ) {
			return $kb_slugs;
		}

		if ( [] === $params && ! isset( $input['knowledge_bases'] ) ) {
			return AbilityError::invalid_input(
				'input',
				__( 'Nothing to update: send at least one field besides taxonomy and id.', 'betterdocs' )
			);
		}

		$updated = (array) $term;

		if ( [] !== $params ) {
			$response = $this->dispatch( 'POST', '/' . $taxonomy . '/' . (int) $term->term_id, $params, 'wp/v2' );

			if ( is_wp_error( $response ) ) {
				return $this->map_term_error(
					$response,
					$taxonomy,
					sprintf(
						/* translators: 1: term type, 2: term id. */
						__( 'edit %1$s #%2$d', 'betterdocs' ),
						$this->term_object_name( $taxonomy ),
						(int) $term->term_id
					)
				);
			}

			$updated = (array) $response;
		}

		if ( null !== $kb_slugs ) {
			$assigned = $this->assign_knowledge_bases( (int) $term->term_id, $kb_slugs );

			if ( is_wp_error( $assigned ) ) {
				return $assigned;
			}

			$updated = $assigned;
		}

		if ( empty( $updated['id'] ) ) {
			// Nothing was dispatched (the caller sent only `knowledge_bases: []`
			// on a category that had none), so read the term back rather than
			// answering from a `WP_Term` that has different keys.
			$read = $this->dispatch( 'GET', '/' . $taxonomy . '/' . (int) $term->term_id, [ 'context' => 'view' ], 'wp/v2' );

			if ( is_wp_error( $read ) ) {
				return $this->map_term_error( $read, $taxonomy, __( 'read the term back', 'betterdocs' ) );
			}

			$updated = (array) $read;
		}

		return array_merge( $this->term_shape( $updated, $taxonomy ), [ 'created' => false ] );
	}
}
