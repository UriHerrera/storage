<?php
/**
 * Update a knowledge base ability.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;

/**
 * Rename a knowledge base, change its slug or description, or replace the doc
 * categories filed under it.
 *
 * **`categories` replaces.** The list given is the list the knowledge base ends
 * up with: categories no longer named have this knowledge base's slug taken out
 * of their membership meta, categories newly named have it added, and a category
 * that belongs to other knowledge bases keeps those. `[]` empties the knowledge
 * base without deleting a single category. Omitting `categories` leaves
 * membership exactly as it was.
 *
 * **A slug change migrates membership.** The relationship is stored as the
 * knowledge base's *slug* on each doc category, so renaming the slug without
 * rewriting those categories would silently unfile every one of them — their
 * permalinks would fall back to `/non-knowledgebase/` and the knowledge-base
 * archive would empty. The categories that carried the old slug are therefore
 * read before the update and rewritten to the new one after it.
 *
 * @since 4.3.0
 */
class UpdateKnowledgeBase extends CreateKnowledgeBase {

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/update-knowledge-base';
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

		$id = isset( $input['id'] ) ? (int) $input['id'] : 0;

		$term = $this->require_knowledge_base( $id );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$params = $this->update_params( $input );

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// Resolved before the write, so a category the caller may not create
		// cannot leave the knowledge base half-renamed.
		$category_ids = $this->resolve_category_input( $input );

		if ( is_wp_error( $category_ids ) ) {
			return $category_ids;
		}

		$old_slug = (string) $term->slug;

		// Read while the old slug is still the one on disk — after the update it
		// may name nothing.
		$was_assigned = $this->assigned_categories( $old_slug );

		if ( is_wp_error( $was_assigned ) ) {
			return $was_assigned;
		}

		$updated = $this->dispatch( 'POST', '/knowledge_base/' . $id, $params, 'wp/v2' );

		if ( is_wp_error( $updated ) ) {
			return $this->map_kb_error( $updated, 'knowledge_base', __( 'update a knowledge base', 'betterdocs-pro' ) );
		}

		$updated  = (array) $updated;
		$new_slug = isset( $updated['slug'] ) ? (string) $updated['slug'] : $old_slug;

		$membership = $this->apply_membership( $was_assigned, $category_ids, $old_slug, $new_slug );

		if ( is_wp_error( $membership ) ) {
			return $membership;
		}

		$categories = $this->assigned_categories( $new_slug );

		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		return $this->kb_shape( $updated, $categories );
	}

	/**
	 * Turn validated input into `wp/v2/knowledge_base` parameters, refusing a
	 * call that would change nothing.
	 *
	 * @since 4.3.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	protected function update_params( array $input ) {
		$params = [];

		foreach ( [ 'name', 'slug', 'description' ] as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$params[ $field ] = (string) $input[ $field ];
			}
		}

		if ( isset( $params['name'] ) && '' === trim( $params['name'] ) ) {
			return AbilityError::invalid_input( 'name', __( 'A knowledge base needs a name.', 'betterdocs-pro' ) );
		}

		if ( empty( $params ) && ! isset( $input['categories'] ) ) {
			return AbilityError::invalid_input(
				'input',
				__( 'Nothing to update: send at least one of name, slug, description or categories besides id.', 'betterdocs-pro' )
			);
		}

		return $params;
	}

	/**
	 * Move the knowledge base's slug out of the categories that should no longer
	 * carry it and into the ones that should.
	 *
	 * Removal runs first, so a slug rename that keeps the same categories leaves
	 * each of them with exactly one entry rather than both spellings.
	 *
	 * @since 4.3.0
	 *
	 * @param array[]    $was_assigned Categories that carried the old slug.
	 * @param int[]|null $category_ids Replacement category ids, or null to keep membership.
	 * @param string     $old_slug     Slug before the update.
	 * @param string     $new_slug     Slug after the update.
	 * @return true|\WP_Error
	 */
	protected function apply_membership( array $was_assigned, $category_ids, $old_slug, $new_slug ) {
		$was = [];

		foreach ( $was_assigned as $category ) {
			$was[] = (int) $category['id'];
		}

		$targets = null === $category_ids ? $was : array_map( 'intval', $category_ids );

		foreach ( $was as $category_id ) {
			$keeps = in_array( $category_id, $targets, true );

			// Strip the old slug from anything dropped, and from anything kept
			// when the slug itself moved.
			if ( $keeps && $old_slug === $new_slug ) {
				continue;
			}

			$unfiled = $this->unfile_category( $category_id, $old_slug );

			if ( is_wp_error( $unfiled ) ) {
				return $unfiled;
			}
		}

		foreach ( $targets as $category_id ) {
			$filed = $this->file_category( $category_id, $new_slug );

			if ( is_wp_error( $filed ) ) {
				return $filed;
			}
		}

		return true;
	}

	/**
	 * Load the knowledge base, refusing an id that is not one.
	 *
	 * @since 4.3.0
	 *
	 * @param int $id Term id.
	 * @return \WP_Term|\WP_Error
	 */
	protected function require_knowledge_base( $id ) {
		$id   = (int) $id;
		$term = $id > 0 ? get_term( $id, $this->kb_taxonomy() ) : null;

		if ( ! $term || is_wp_error( $term ) ) {
			return AbilityError::not_found( $this->term_object_name( $this->kb_taxonomy() ), $id );
		}

		return $term;
	}

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'update a knowledge base', 'betterdocs-pro' );
	}
}
