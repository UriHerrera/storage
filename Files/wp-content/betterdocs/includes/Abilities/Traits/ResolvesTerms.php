<?php
/**
 * Turning term references an agent wrote into term ids.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;

/**
 * An agent knows terms by the names a human would use, not by id.
 *
 * Every content tool therefore accepts `["Getting started", 12, "how-to"]` and
 * this trait turns that into `[3, 12, 7]`. Two rules make it safe:
 *
 * 1. **Only a write creates.** A filter (`bd-list-docs {tag: "typo"}`) that
 *    created the term it was looking for would answer "no results" *and* litter
 *    the taxonomy, so `$create` is false on every read path and the refusal
 *    says so.
 * 2. **Creating goes through the REST route, never `wp_insert_term()`.**
 *    `wp_insert_term()` performs no capability check at all — measured on
 *    WordPress 7.1 — so an author holding only `edit_docs` could mint doc
 *    categories through a doc tool. `POST wp/v2/doc_category` runs
 *    `edit_doc_terms` first and gives a mappable error when it refuses.
 *
 * Knowledge bases are never created here: that is the Pro tool's job, and on a
 * site where the taxonomy is not even registered the honest answer is *why*
 * rather than "not found".
 *
 * @since 4.9.0
 */
trait ResolvesTerms {

	/**
	 * Taxonomies a content tool may create a term in.
	 *
	 * @since 4.9.0
	 *
	 * @var string[]
	 */
	protected static $creatable_taxonomies = [ 'doc_category', 'doc_tag', 'glossaries' ];

	/**
	 * Resolve a list of term references to term ids.
	 *
	 * A reference is an int id (must exist), or a string matched against the
	 * slug first and then the name.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $refs     Term references.
	 * @param string $taxonomy Taxonomy name.
	 * @param bool   $create   Whether an unmatched name may be created.
	 * @return int[]|\WP_Error Term ids, or the first refusal.
	 */
	protected function resolve_terms( array $refs, $taxonomy, $create ) {
		$taxonomy = (string) $taxonomy;

		$available = $this->taxonomy_available( $taxonomy );

		if ( is_wp_error( $available ) ) {
			return $available;
		}

		$ids = [];

		foreach ( $refs as $ref ) {
			$id = $this->resolve_term( $ref, $taxonomy, $create );

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$ids[] = $id;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Resolve one reference.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed  $ref      Term id, slug or name.
	 * @param string $taxonomy Taxonomy name.
	 * @param bool   $create   Whether an unmatched name may be created.
	 * @return int|\WP_Error
	 */
	protected function resolve_term( $ref, $taxonomy, $create ) {
		if ( is_int( $ref ) || ( is_string( $ref ) && ctype_digit( $ref ) ) ) {
			$term = get_term( (int) $ref, $taxonomy );

			if ( ! $term || is_wp_error( $term ) ) {
				return AbilityError::not_found( $this->term_object_name( $taxonomy ), (int) $ref );
			}

			return (int) $term->term_id;
		}

		if ( ! is_string( $ref ) || '' === trim( $ref ) ) {
			return AbilityError::invalid_input(
				$taxonomy,
				__( 'A term reference must be a term id, a slug or a name.', 'betterdocs' )
			);
		}

		$ref = trim( $ref );

		foreach ( [ 'slug', 'name' ] as $field ) {
			$term = get_term_by( $field, 'slug' === $field ? sanitize_title( $ref ) : $ref, $taxonomy );

			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $term->term_id;
			}
		}

		if ( ! $create ) {
			return AbilityError::not_found( $this->term_object_name( $taxonomy ), $ref );
		}

		if ( ! in_array( $taxonomy, self::$creatable_taxonomies, true ) ) {
			return AbilityError::not_found( $this->term_object_name( $taxonomy ), $ref );
		}

		return $this->create_term( $ref, $taxonomy );
	}

	/**
	 * Create a term through the REST route, so the capability check runs.
	 *
	 * @since 4.9.0
	 *
	 * @param string $name     Term name.
	 * @param string $taxonomy Taxonomy name.
	 * @return int|\WP_Error
	 */
	protected function create_term( $name, $taxonomy ) {
		$created = $this->dispatch( 'POST', '/' . $taxonomy, [ 'name' => $name ], 'wp/v2' );

		if ( is_wp_error( $created ) ) {
			$data = (array) $created->get_error_data();

			// A race, or a name that differs only by case: WordPress hands back
			// the existing id, which is exactly what find-or-create wanted.
			if ( 'term_exists' === $created->get_error_code() && ! empty( $data['term_id'] ) ) {
				return (int) $data['term_id'];
			}

			if ( 'rest_cannot_create' === $created->get_error_code() ) {
				$taxonomy_object = get_taxonomy( $taxonomy );
				$capability      = $taxonomy_object && isset( $taxonomy_object->cap->edit_terms )
					? (string) $taxonomy_object->cap->edit_terms
					: 'edit_doc_terms';

				return AbilityError::capability_missing(
					$capability,
					sprintf(
						/* translators: 1: term name, 2: taxonomy name. */
						__( 'create the %1$s "%2$s"', 'betterdocs' ),
						$this->term_object_name( $taxonomy ),
						$name
					)
				);
			}

			return AbilityError::upstream( $created->get_error_message(), [ 'taxonomy' => $taxonomy ] );
		}

		return isset( $created['id'] ) ? (int) $created['id'] : 0;
	}

	/**
	 * Whether the taxonomy can be used at all, and why not when it cannot.
	 *
	 * Only `knowledge_base` can legitimately be absent: BetterDocs Pro registers
	 * it, and only while Multiple Knowledge Base is on. An agent that asked for
	 * a knowledge base needs to know which of those two it is, because one is
	 * fixable with a tool call and the other is not.
	 *
	 * @since 4.9.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return true|\WP_Error
	 */
	protected function taxonomy_available( $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			return true;
		}

		// Glossaries are a **Free** feature behind a Free setting: with
		// `enable_glossaries` off the taxonomy is not registered at all, so the
		// honest answer names the setting and the write that turns it on. The
		// state is deliberately not a `pro_` one, so `requires_pro` stays false
		// (ADR-061).
		if ( 'glossaries' === $taxonomy ) {
			return AbilityError::setting_disabled(
				self::GLOSSARY_SETTING,
				[ self::GLOSSARY_SETTING => true ],
				__( 'Glossaries', 'betterdocs' ),
				'setting_off'
			);
		}

		if ( 'knowledge_base' !== $taxonomy ) {
			return AbilityError::upstream(
				sprintf(
					/* translators: %s: taxonomy name. */
					__( 'The "%s" taxonomy is not registered on this site.', 'betterdocs' ),
					$taxonomy
				),
				[ 'taxonomy' => $taxonomy ]
			);
		}

		$state = ProState::get( true );

		if ( 'pro_active_setting_off' === $state['state'] ) {
			return AbilityError::setting_disabled(
				'multiple_kb',
				[ 'multiple_kb' => true ],
				__( 'Knowledge bases', 'betterdocs' )
			);
		}

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, __( 'Knowledge bases', 'betterdocs' ) );
		}

		return AbilityError::upstream(
			__( 'The knowledge_base taxonomy is not registered, although BetterDocs Pro reports itself able to register it.', 'betterdocs' ),
			[
				'taxonomy' => 'knowledge_base',
				'state'    => $state['state']
			]
		);
	}

	/**
	 * `{id, name, slug}` for a term id, or null when it has gone.
	 *
	 * @since 4.9.0
	 *
	 * @param int    $id       Term id.
	 * @param string $taxonomy Optional taxonomy to scope the lookup.
	 * @return array|null
	 */
	protected function term_summary( $id, $taxonomy = '' ) {
		$term = '' !== $taxonomy ? get_term( (int) $id, $taxonomy ) : get_term( (int) $id );

		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		return [
			'id'   => (int) $term->term_id,
			'name' => (string) $term->name,
			'slug' => (string) $term->slug
		];
	}

	/**
	 * `{id, name, slug}` for a list of ids, dropping any that have gone.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $ids      Term ids.
	 * @param string $taxonomy Taxonomy to scope the lookup.
	 * @return array[]
	 */
	protected function term_summaries( array $ids, $taxonomy = '' ) {
		$out = [];

		foreach ( $ids as $id ) {
			$summary = $this->term_summary( $id, $taxonomy );

			if ( null !== $summary ) {
				$out[] = $summary;
			}
		}

		return $out;
	}

	/**
	 * What to call a term of this taxonomy in an error message.
	 *
	 * @since 4.9.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	protected function term_object_name( $taxonomy ) {
		$names = [
			'doc_category'            => __( 'doc category', 'betterdocs' ),
			'doc_tag'                 => __( 'doc tag', 'betterdocs' ),
			'knowledge_base'          => __( 'knowledge base', 'betterdocs' ),
			'glossaries'              => __( 'glossary term', 'betterdocs' ),
			'betterdocs_faq_category' => __( 'FAQ group', 'betterdocs' )
		];

		return isset( $names[ $taxonomy ] ) ? $names[ $taxonomy ] : __( 'term', 'betterdocs' );
	}

	/**
	 * JSON Schema for a list of term references.
	 *
	 * @since 4.9.0
	 *
	 * @param string $description Field description.
	 * @return array
	 */
	protected static function term_ref_schema( $description ) {
		return [
			'type'        => 'array',
			'description' => $description,
			'items'       => [ 'type' => [ 'integer', 'string' ] ]
		];
	}
}
