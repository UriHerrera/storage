<?php
/**
 * The one term shape the Terms abilities answer with, and the knowledge-base
 * assignment they share.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;

/**
 * Four tools, one term object.
 *
 * `knowledge_bases` is reported as **slugs**, not ids, because that is what the
 * `doc_category_knowledge_base` meta stores and what every reader of it
 * compares against — including the permalink builder, which is why assigning a
 * knowledge base changes a category's URL.
 *
 * @since 4.9.0
 */
trait ShapesTerms {

	// `self::TERM_TAXONOMIES` and `self::KB_META_KEY` are declared on
	// `Abilities\AbilityBase`, which every user of this trait extends: PHP 7.4
	// traits cannot carry constants.

	/**
	 * `taxonomy` as every one of the four declares it.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected static function taxonomy_schema() {
		return [
			'type'        => 'string',
			'enum'        => self::TERM_TAXONOMIES,
			'description' => __( 'Which taxonomy: doc_category, doc_tag or glossaries. Knowledge bases have their own tools. Glossary terms need the enable_glossaries setting on. Required.', 'betterdocs' )
		];
	}

	/**
	 * The term summary all four answer with.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $term     A `wp/v2/<taxonomy>` item.
	 * @param string $taxonomy Taxonomy name.
	 * @return array
	 */
	protected function term_shape( array $term, $taxonomy ) {
		$out = [
			'id'              => isset( $term['id'] ) ? (int) $term['id'] : 0,
			'taxonomy'        => (string) $taxonomy,
			'name'            => isset( $term['name'] ) ? (string) $term['name'] : '',
			'slug'            => isset( $term['slug'] ) ? (string) $term['slug'] : '',
			'description'     => isset( $term['description'] ) ? (string) $term['description'] : '',
			'parent'          => isset( $term['parent'] ) ? (int) $term['parent'] : 0,
			// WordPress counts **published** posts here, so a category holding
			// only drafts reports 0.
			'count'           => isset( $term['count'] ) ? (int) $term['count'] : 0,
			'url'             => isset( $term['link'] ) ? (string) $term['link'] : '',
			'knowledge_bases' => $this->kb_slugs( $term )
		];

		// BetterDocs' own REST fields, present on `doc_category` only.
		if ( isset( $term['doc_category_order'] ) && null !== $term['doc_category_order'] ) {
			$out['doc_category_order'] = (int) $term['doc_category_order'];
		}

		if ( isset( $term['total_docs_count'] ) && null !== $term['total_docs_count'] ) {
			$out['total_docs_count'] = (int) $term['total_docs_count'];
		}

		// Glossary terms carry their own three metas, and their description is
		// one of them rather than the term column (ADR-061). `status` is reported
		// as the readable `publish` / `draft` the tools accept, not the stored
		// `'1'` / `'0'`.
		if ( 'glossaries' === $taxonomy ) {
			$meta = isset( $term['meta'] ) && is_array( $term['meta'] ) ? $term['meta'] : [];

			$stored_status = $this->first_meta_value( $meta, 'status' );

			$out['status'] = '0' === $stored_status ? 'draft' : 'publish';
			$out['order']  = (int) $this->first_meta_value( $meta, 'order' );

			$description = $this->first_meta_value( $meta, self::GLOSSARY_DESCRIPTION_META );

			if ( '' !== $description ) {
				$out['description'] = $description;
			}
		}

		return $out;
	}

	/**
	 * One term-meta value, whichever shape the controller reported it in.
	 *
	 * A registered single meta reads back as a scalar, but the glossary feature
	 * also rewrites these keys into a one-element array in its own REST filter,
	 * so both shapes reach this method.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $meta The item's `meta` map.
	 * @param string $key  Meta key.
	 * @return string
	 */
	protected function first_meta_value( array $meta, $key ) {
		if ( ! isset( $meta[ $key ] ) ) {
			return '';
		}

		$value = $meta[ $key ];

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * JSON Schema for {@see self::term_shape()}.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected static function term_shape_schema() {
		return [
			'id'                 => [ 'type' => 'integer' ],
			'taxonomy'           => [ 'type' => 'string' ],
			'name'               => [ 'type' => 'string' ],
			'slug'               => [ 'type' => 'string' ],
			'description'        => [ 'type' => 'string' ],
			'parent'             => [ 'type' => 'integer' ],
			'count'              => [ 'type' => 'integer' ],
			'url'                => [ 'type' => 'string' ],
			'knowledge_bases'    => [
				'type'  => 'array',
				'items' => [ 'type' => 'string' ]
			],
			// Absent on `doc_tag`, which registers neither REST field.
			'doc_category_order' => [ 'type' => 'integer' ],
			'total_docs_count'   => [ 'type' => 'integer' ],
			// `glossaries` only.
			'status'             => [ 'type' => 'string' ],
			'order'              => [ 'type' => 'integer' ]
		];
	}

	/**
	 * The knowledge-base slugs a term item carries.
	 *
	 * @since 4.9.0
	 *
	 * @param array $term A `wp/v2/<taxonomy>` item.
	 * @return string[]
	 */
	protected function kb_slugs( array $term ) {
		$raw = isset( $term['meta'][ self::KB_META_KEY ] ) ? $term['meta'][ self::KB_META_KEY ] : [];

		if ( ! is_array( $raw ) ) {
			$raw = '' === $raw || null === $raw ? [] : [ $raw ];
		}

		$slugs = [];

		foreach ( $raw as $slug ) {
			if ( is_scalar( $slug ) && '' !== (string) $slug ) {
				$slugs[] = (string) $slug;
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Turn knowledge-base references into the slugs the meta key stores.
	 *
	 * Slugs, not ids: `doc_category_knowledge_base` holds slugs, and its
	 * registered sanitiser drops anything numeric precisely so an id written here
	 * cannot
	 * masquerade as one.
	 *
	 * @since 4.9.0
	 *
	 * @param array $refs Knowledge-base ids, slugs or names.
	 * @return string[]|\WP_Error
	 */
	protected function kb_slugs_for( array $refs ) {
		$available = $this->taxonomy_available( 'knowledge_base' );

		if ( is_wp_error( $available ) ) {
			return $available;
		}

		$ids = $this->resolve_terms( $refs, 'knowledge_base', false );

		if ( is_wp_error( $ids ) ) {
			return $ids;
		}

		$slugs = [];

		foreach ( $ids as $id ) {
			$summary = $this->term_summary( $id, 'knowledge_base' );

			if ( null !== $summary ) {
				$slugs[] = $summary['slug'];
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Load a term, refusing an id from the wrong taxonomy.
	 *
	 * @since 4.9.0
	 *
	 * @param int    $id       Term id.
	 * @param string $taxonomy Taxonomy the caller named.
	 * @return object|\WP_Error
	 */
	protected function require_term( $id, $taxonomy ) {
		$id   = (int) $id;
		$term = $id > 0 ? get_term( $id, $taxonomy ) : null;

		if ( ! $term || is_wp_error( $term ) ) {
			return AbilityError::not_found( $this->term_object_name( $taxonomy ), $id );
		}

		return $term;
	}

	/**
	 * Translate a `wp/v2/<taxonomy>` refusal into the typed vocabulary.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error    What the controller returned.
	 * @param string    $taxonomy Taxonomy in play.
	 * @param string    $what     What the caller was trying to do, as a phrase.
	 * @return \WP_Error
	 */
	protected function map_term_error( \WP_Error $error, $taxonomy, $what ) {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();
		$data    = (array) $error->get_error_data();

		$object = get_taxonomy( $taxonomy );

		switch ( $code ) {
			case 'rest_term_invalid':
			case 'rest_term_invalid_id':
				return AbilityError::not_found( $this->term_object_name( $taxonomy ), isset( $data['id'] ) ? $data['id'] : 0 );

			case 'rest_cannot_create':
				return AbilityError::capability_missing(
					$object && isset( $object->cap->edit_terms ) ? (string) $object->cap->edit_terms : 'manage_doc_terms',
					$what
				);

			case 'rest_cannot_update':
				// The membership meta key carries its own `auth_callback`,
				// so this one code covers two different capabilities and the
				// answer has to say which.
				if ( isset( $data['key'] ) && self::KB_META_KEY === $data['key'] ) {
					return AbilityError::capability_missing( 'manage_knowledge_base_terms', __( 'assign knowledge bases to a doc category', 'betterdocs' ) );
				}

				return AbilityError::capability_missing(
					$object && isset( $object->cap->edit_terms ) ? (string) $object->cap->edit_terms : 'edit_doc_terms',
					$what
				);

			case 'rest_cannot_delete':
				return AbilityError::capability_missing(
					$object && isset( $object->cap->delete_terms ) ? (string) $object->cap->delete_terms : 'delete_doc_terms',
					$what
				);

			case 'rest_forbidden':
			case 'rest_forbidden_context':
				return AbilityError::capability_missing(
					$object && isset( $object->cap->manage_terms ) ? (string) $object->cap->manage_terms : 'manage_doc_terms',
					$what
				);

			case 'rest_trash_not_supported':
				return AbilityError::conflict( $message, [ 'object' => $this->term_object_name( $taxonomy ) ] );

			case 'rest_invalid_param':
				$field = isset( $data['params'] ) && is_array( $data['params'] ) ? (string) key( $data['params'] ) : '';

				return AbilityError::invalid_input(
					'' !== $field ? $field : 'input',
					'' !== $field && isset( $data['params'][ $field ] ) ? (string) $data['params'][ $field ] : $message
				);

			default:
				return AbilityError::upstream( $message, [ 'code' => (string) $code ] );
		}
	}
}
