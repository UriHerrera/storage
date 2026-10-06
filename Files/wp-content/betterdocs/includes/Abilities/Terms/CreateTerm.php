<?php
/**
 * Create a doc category or doc tag ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Terms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesTerms;

/**
 * Create a doc category or a doc tag — or hand back the one that already
 * exists.
 *
 * **Find-or-create.** A duplicate name is not an error: WordPress answers
 * `term_exists` with the existing id, and this returns that term with
 * `created: false`. An agent building a taxonomy from a list of names can run
 * the same batch twice and get the same twelve categories, which is the only
 * way "make sure these categories exist" is expressible as a tool call.
 *
 * `knowledge_bases` writes the `doc_category_knowledge_base` term meta
 * registered for REST — the assignment that decides a category's permalink and
 * which knowledge base archive it belongs to. It is `doc_category` only, needs
 * `manage_knowledge_base_terms`, and never creates a knowledge base.
 *
 * @since 4.9.0
 */
class CreateTerm extends AbilityBase {

	use ResolvesTerms;
	use ShapesTerms;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/create-term';
		$this->label       = __( 'Create term', 'betterdocs' );
		$this->description = __( 'Create a BetterDocs doc category or doc tag. A name that already exists is returned as it is, with created:false, so the call is safe to repeat. Doc categories may also be filed into knowledge bases, which must already exist.', 'betterdocs' );
		$this->capability  = 'manage_doc_terms';
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
			'idempotent'    => false,
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
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'taxonomy', 'name' ],
			'properties'           => [
				'taxonomy'        => self::taxonomy_schema(),
				'name'            => [
					'type'        => 'string',
					'description' => __( 'The term name, as a human would write it. Required.', 'betterdocs' )
				],
				'slug'            => [
					'type'        => 'string',
					'description' => __( 'URL slug. Derived from the name when omitted.', 'betterdocs' )
				],
				'description'     => [
					'type'        => 'string',
					'description' => __( 'Optional description shown on the term archive.', 'betterdocs' )
				],
				'parent'          => [
					'type'        => [ 'integer', 'string', 'null' ],
					'description' => __( 'Parent doc category, by id or name. Send 0 (or null) to move it to the top level. doc_category only.', 'betterdocs' )
				],
				'knowledge_bases' => self::term_ref_schema( __( 'Knowledge bases to file this doc category into, by id, slug or name. doc_category only; never created here — use bd-create-knowledge-base.', 'betterdocs' ) ),
				'status'          => [
					'type'        => 'string',
					'enum'        => [ 'publish', 'draft' ],
					'description' => __( 'Whether the glossary term is shown on the front end. glossaries only; defaults to publish.', 'betterdocs' )
				],
				'order'           => [
					'type'        => 'integer',
					'minimum'     => 0,
					'description' => __( 'Sort position among glossary terms, lowest first. glossaries only.', 'betterdocs' )
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
			'properties' => array_merge(
				self::term_shape_schema(),
				[ 'created' => [ 'type' => 'boolean' ] ]
			)
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

		$params = $this->build_params( $input, $taxonomy );

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// Resolved *before* anything is written. Doing it after the term exists
		// turns a refusal into a half-finished job: measured on the rig with Pro
		// deactivated, `bd-create-term {name, knowledge_bases}` answered
		// `pro_required` and left the new category behind anyway.
		$kb_slugs = $this->resolve_kb_input( $input, $taxonomy );

		if ( is_wp_error( $kb_slugs ) ) {
			return $kb_slugs;
		}

		$created = true;
		$term    = $this->dispatch( 'POST', '/' . $taxonomy, $params, 'wp/v2' );

		if ( is_wp_error( $term ) ) {
			$existing = $this->existing_term_id( $term );

			if ( 0 === $existing ) {
				return $this->map_term_error(
					$term,
					$taxonomy,
					sprintf(
						/* translators: %s: term type, e.g. "doc category". */
						__( 'create a %s', 'betterdocs' ),
						$this->term_object_name( $taxonomy )
					)
				);
			}

			// Find-or-create: the name is taken, so hand back what is there.
			$created = false;
			$term    = $this->dispatch( 'GET', '/' . $taxonomy . '/' . $existing, [ 'context' => 'view' ], 'wp/v2' );

			if ( is_wp_error( $term ) ) {
				return $this->map_term_error( $term, $taxonomy, __( 'read the term that already exists', 'betterdocs' ) );
			}
		}

		$term = (array) $term;

		if ( empty( $term['id'] ) ) {
			return AbilityError::upstream( __( 'The term was not created and WordPress reported no reason.', 'betterdocs' ) );
		}

		if ( null !== $kb_slugs ) {
			$assigned = $this->assign_knowledge_bases( (int) $term['id'], $kb_slugs );

			if ( is_wp_error( $assigned ) ) {
				return $assigned;
			}

			$term = $assigned;
		}

		return array_merge( $this->term_shape( $term, $taxonomy ), [ 'created' => $created ] );
	}

	/**
	 * Turn validated input into `wp/v2/<taxonomy>` parameters.
	 *
	 * Shared with {@see UpdateTerm}.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $input    Validated input.
	 * @param string $taxonomy Taxonomy name.
	 * @return array|\WP_Error
	 */
	protected function build_params( array $input, $taxonomy ) {
		$params = [];

		foreach ( [ 'name', 'slug', 'description' ] as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$params[ $field ] = (string) $input[ $field ];
			}
		}

		// A glossary term's description is the `glossary_term_description` meta,
		// not the term's own column: BetterDocs drains that column into the meta
		// and blanks it, and both the admin screen and the A–Z front end read the
		// meta first (ADR-061). Writing the column instead would be silently
		// discarded the next time someone edited the term.
		if ( 'glossaries' === $taxonomy && array_key_exists( 'description', $params ) ) {
			$params['meta'][ self::GLOSSARY_DESCRIPTION_META ] = $params['description'];

			unset( $params['description'] );
		}

		$meta = $this->glossary_meta( $input, $taxonomy );

		if ( is_wp_error( $meta ) ) {
			return $meta;
		}

		if ( [] !== $meta ) {
			$params['meta'] = isset( $params['meta'] ) ? array_merge( $params['meta'], $meta ) : $meta;
		}

		if ( isset( $input['knowledge_bases'] ) && 'doc_category' !== $taxonomy ) {
			return AbilityError::invalid_input(
				'knowledge_bases',
				__( 'Only doc categories belong to knowledge bases.', 'betterdocs' )
			);
		}

		if ( ! array_key_exists( 'parent', $input ) ) {
			return $params;
		}

		if ( 'doc_category' !== $taxonomy ) {
			return AbilityError::invalid_input(
				'parent',
				__( 'These tools nest doc categories only; a doc tag takes no parent.', 'betterdocs' )
			);
		}

		// `0`, `"0"` and `null` mean "the top level" — the same convention
		// `bd-list-terms`' parent filter uses (ADR-059, finding B). WordPress
		// un-nests a category sent `parent: 0`, so the value is passed straight
		// through: id 0 is not a term and would otherwise resolve to `not_found`,
		// which is why moving a category to the top level used to be refused.
		$parent_ref = $input['parent'];

		if ( null === $parent_ref || 0 === $parent_ref || '0' === $parent_ref ) {
			$params['parent'] = 0;

			return $params;
		}

		// `false`: naming a parent that does not exist is a mistake worth
		// reporting, not an instruction to invent a category.
		$parent = $this->resolve_terms( [ $parent_ref ], 'doc_category', false );

		if ( is_wp_error( $parent ) ) {
			return $parent;
		}

		$params['parent'] = isset( $parent[0] ) ? (int) $parent[0] : 0;

		return $params;
	}

	/**
	 * The id WordPress reports on a duplicate name, or 0 when the error is
	 * something else.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error What the controller returned.
	 * @return int
	 */
	protected function existing_term_id( \WP_Error $error ) {
		if ( 'term_exists' !== $error->get_error_code() ) {
			return 0;
		}

		$data = $error->get_error_data();

		if ( is_array( $data ) && ! empty( $data['term_id'] ) ) {
			return (int) $data['term_id'];
		}

		// `wp_insert_term()` puts the id in the data directly; the REST
		// controller adds the `term_id` key on top. Accept either.
		return is_scalar( $data ) ? (int) $data : 0;
	}

	/**
	 * Resolve the `knowledge_bases` input to slugs, or null when there is none.
	 *
	 * Every reason this can refuse — Pro absent, Multiple Knowledge Base off, a
	 * knowledge base that does not exist, the wrong taxonomy — is known before
	 * the first write, which is the point of doing it here.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $input    Validated input.
	 * @param string $taxonomy Taxonomy name.
	 * @return string[]|null|\WP_Error
	 */
	protected function resolve_kb_input( array $input, $taxonomy ) {
		if ( ! isset( $input['knowledge_bases'] ) ) {
			return null;
		}

		if ( 'doc_category' !== $taxonomy ) {
			return AbilityError::invalid_input(
				'knowledge_bases',
				__( 'Only doc categories belong to knowledge bases.', 'betterdocs' )
			);
		}

		return $this->kb_slugs_for( (array) $input['knowledge_bases'] );
	}

	/**
	 * The glossary-only term metas, validated against the taxonomy in play.
	 *
	 * `status` is the string `'1'` / `'0'` the feature stores — that is what
	 * `BetterDocsPro\Core\Glossaries::update_glossary_status()` writes and what the admin
	 * screen and the A–Z front end read — so the tools take the readable
	 * `publish` / `draft` and map it. `order` is stored as a string too, and
	 * lowest sorts first.
	 *
	 * Both are refused on the other taxonomies rather than ignored: silently
	 * dropping a field an agent sent is how an agent learns the wrong model of
	 * the tool.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $input    Validated input.
	 * @param string $taxonomy Taxonomy name.
	 * @return array|\WP_Error Meta map, possibly empty.
	 */
	protected function glossary_meta( array $input, $taxonomy ) {
		$meta = [];

		foreach ( [ 'status', 'order' ] as $field ) {
			if ( ! isset( $input[ $field ] ) ) {
				continue;
			}

			if ( 'glossaries' !== $taxonomy ) {
				return AbilityError::invalid_input(
					$field,
					__( 'Only glossary terms take a status or an order.', 'betterdocs' )
				);
			}

			$meta[ $field ] = 'status' === $field
				? ( 'publish' === (string) $input['status'] ? '1' : '0' )
				: (string) (int) $input['order'];
		}

		return $meta;
	}

	/**
	 * Write the knowledge-base assignment and return the term as it now is.
	 *
	 * Replace semantics: the list given is the list the category ends up with,
	 * and `[]` clears it.
	 *
	 * @since 4.9.0
	 *
	 * @param int      $term_id Doc category id.
	 * @param string[] $slugs   Knowledge-base slugs, already resolved.
	 * @return array|\WP_Error The updated term item.
	 */
	protected function assign_knowledge_bases( $term_id, array $slugs ) {
		$updated = $this->dispatch(
			'POST',
			'/doc_category/' . (int) $term_id,
			[
				'meta' => [ self::KB_META_KEY => $slugs ]
			],
			'wp/v2'
		);

		if ( is_wp_error( $updated ) ) {
			return $this->map_term_error( $updated, 'doc_category', __( 'assign knowledge bases to a doc category', 'betterdocs' ) );
		}

		return (array) $updated;
	}
}
