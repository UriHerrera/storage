<?php
/**
 * Delete a doc category or doc tag ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Terms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesTerms;

/**
 * Delete a doc category or doc tag.
 *
 * There is no trash for terms — WordPress deletes them outright, which is why
 * `wp/v2` requires `force=true` and why this tool is annotated destructive with
 * no gentler mode to fall back on.
 *
 * **The docs survive.** Deleting a category unfiles every doc in it; it does not
 * delete them. Deleting a parent category promotes its children to the top
 * level rather than removing them. Both are WordPress' behaviour and both are in
 * the tool description, because "delete the empty category" is a request an
 * agent will act on without checking whether it is really empty.
 *
 * @since 4.9.0
 */
class DeleteTerm extends AbilityBase {

	use ResolvesTerms;
	use ShapesTerms;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/delete-term';
		$this->label       = __( 'Delete term', 'betterdocs' );
		$this->description = __( 'Delete a BetterDocs doc category or doc tag. Terms have no trash, so this cannot be undone. Docs in a deleted category are not deleted — they become uncategorised — and child categories move up to the top level.', 'betterdocs' );
		$this->capability  = 'delete_doc_terms';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => false,
			'priority'      => 3.0,
			'openWorldHint' => false
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'taxonomy', 'id' ],
			'properties'           => [
				'taxonomy' => self::taxonomy_schema(),
				'id'       => [
					'type'        => 'integer',
					'description' => __( 'The term id to delete. Required.', 'betterdocs' )
				]
			],
			'default'              => []
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'id'       => [ 'type' => 'integer' ],
				'taxonomy' => [ 'type' => 'string' ],
				'deleted'  => [ 'type' => 'boolean' ],
				'name'     => [ 'type' => 'string' ],
				// Published docs only — WordPress' term counts ignore drafts.
				'docs'     => [ 'type' => 'integer' ]
			]
		];
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

		$name  = (string) $term->name;
		$count = (int) $term->count;

		// `force` is not optional for a taxonomy term: `WP_REST_Terms_Controller`
		// refuses without it, because there is nowhere to trash a term to.
		$deleted = $this->dispatch(
			'DELETE',
			'/' . $taxonomy . '/' . (int) $term->term_id,
			[ 'force' => true ],
			'wp/v2'
		);

		if ( is_wp_error( $deleted ) ) {
			return $this->map_term_error(
				$deleted,
				$taxonomy,
				sprintf(
					/* translators: 1: term type, 2: term id. */
					__( 'delete %1$s #%2$d', 'betterdocs' ),
					$this->term_object_name( $taxonomy ),
					(int) $term->term_id
				)
			);
		}

		return [
			'id'       => (int) $term->term_id,
			'taxonomy' => $taxonomy,
			'deleted'  => true,
			'name'     => $name,
			// How many docs just lost this term. Reported because an agent that
			// deleted a category holding forty docs should be able to say so.
			// WordPress counts published posts only, so drafts in the category
			// are unfiled without being counted here.
			'docs'     => $count
		];
	}
}
