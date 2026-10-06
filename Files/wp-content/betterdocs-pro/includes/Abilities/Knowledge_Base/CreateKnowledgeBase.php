<?php
/**
 * Create a knowledge base ability.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;
use WPDeveloper\BetterDocsPro\Abilities\Traits\FilesDocCategories;

/**
 * Create a knowledge base — or hand back the one that already exists.
 *
 * **Find-or-create**, the rule every BetterDocs term tool follows: a duplicate
 * name is not an error. WordPress answers `term_exists` with the id it already
 * has, and this returns that knowledge base with `created: false`, so an agent
 * asked to "make sure these three knowledge bases exist" can run the same batch
 * twice and get the same three ids.
 *
 * `categories` files doc categories under the new knowledge base by appending
 * its slug to their `doc_category_knowledge_base` meta. Unlike the knowledge
 * base itself, a doc category named here **is** created when it does not exist —
 * this is a write tool, and an agent building a knowledge base out of a list of
 * topic names should not have to make two calls per topic. Existing membership
 * of other knowledge bases is preserved.
 *
 * @since 4.3.0
 */
class CreateKnowledgeBase extends ProAbility {

	use ResolvesTerms;
	use FilesDocCategories;

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/create-knowledge-base';
	}

	/**
	 * @since 4.3.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$blocked = $this->kb_blocked();

		if ( null !== $blocked ) {
			return $blocked;
		}

		$name = isset( $input['name'] ) ? trim( (string) $input['name'] ) : '';

		if ( '' === $name ) {
			return AbilityError::invalid_input( 'name', __( 'A knowledge base needs a name.', 'betterdocs-pro' ) );
		}

		// Resolved before anything is written, the way Free's `bd-create-term`
		// resolves its knowledge bases first (ADR-048): every reason this call
		// can still refuse — a category name the caller may not create, a
		// category id that does not exist — is known before a knowledge base
		// exists to be left behind.
		$category_ids = $this->resolve_category_input( $input );

		if ( is_wp_error( $category_ids ) ) {
			return $category_ids;
		}

		$result = $this->create_or_find( $name, $input );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term = $result['term'];
		$slug = isset( $term['slug'] ) ? (string) $term['slug'] : '';

		if ( null !== $category_ids ) {
			$filed = $this->file_categories( $category_ids, $slug );

			if ( is_wp_error( $filed ) ) {
				return $filed;
			}
		}

		$categories = $this->assigned_categories( $slug );

		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		return array_merge(
			$this->kb_shape( $term, $categories ),
			[ 'created' => (bool) $result['created'] ]
		);
	}

	/**
	 * Create the knowledge base, or read back the one whose name is taken.
	 *
	 * @since 4.3.0
	 *
	 * @param string $name  Knowledge-base name.
	 * @param array  $input Validated input.
	 * @return array|\WP_Error `{ term, created }`, or the refusal.
	 */
	protected function create_or_find( $name, array $input ) {
		$params = [ 'name' => $name ];

		foreach ( [ 'slug', 'description' ] as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$params[ $field ] = (string) $input[ $field ];
			}
		}

		$created = true;
		$term    = $this->dispatch( 'POST', '/knowledge_base', $params, 'wp/v2' );

		if ( is_wp_error( $term ) ) {
			$existing = $this->existing_term_id( $term );

			if ( 0 === $existing ) {
				return $this->map_kb_error( $term, 'knowledge_base', __( 'create a knowledge base', 'betterdocs-pro' ) );
			}

			$created = false;
			$term    = $this->dispatch( 'GET', '/knowledge_base/' . $existing, [ 'context' => 'view' ], 'wp/v2' );

			if ( is_wp_error( $term ) ) {
				return $this->map_kb_error( $term, 'knowledge_base', __( 'read the knowledge base that already exists', 'betterdocs-pro' ) );
			}
		}

		$term = (array) $term;

		if ( empty( $term['id'] ) ) {
			return AbilityError::upstream( __( 'The knowledge base was not created and WordPress reported no reason.', 'betterdocs-pro' ) );
		}

		return [
			'term'    => $term,
			'created' => $created
		];
	}

	/**
	 * Resolve the `categories` input to doc-category ids, or null when there is
	 * none.
	 *
	 * @since 4.3.0
	 *
	 * @param array $input Validated input.
	 * @return int[]|null|\WP_Error
	 */
	protected function resolve_category_input( array $input ) {
		if ( ! isset( $input['categories'] ) ) {
			return null;
		}

		$refs = (array) $input['categories'];

		if ( empty( $refs ) ) {
			return [];
		}

		return $this->resolve_terms( $refs, 'doc_category', true );
	}

	/**
	 * File every resolved doc category under this knowledge base.
	 *
	 * @since 4.3.0
	 *
	 * @param int[]  $category_ids Doc category ids.
	 * @param string $slug         Knowledge-base slug.
	 * @return int|\WP_Error How many categories were actually written.
	 */
	protected function file_categories( array $category_ids, $slug ) {
		$written = 0;

		foreach ( $category_ids as $category_id ) {
			$filed = $this->file_category( $category_id, $slug );

			if ( is_wp_error( $filed ) ) {
				return $filed;
			}

			if ( $filed ) {
				++$written;
			}
		}

		return $written;
	}

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'create a knowledge base', 'betterdocs-pro' );
	}
}
