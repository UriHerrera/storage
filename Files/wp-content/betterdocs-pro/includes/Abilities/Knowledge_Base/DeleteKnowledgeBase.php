<?php
/**
 * Delete a knowledge base ability.
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
 * Delete a knowledge base. The doc categories filed under it are unfiled, never
 * deleted.
 *
 * Terms have no trash, so this is `force=true` and there is nothing to undo.
 *
 * Unfiling is not tidiness. Membership lives as the knowledge base's **slug** in
 * each doc category's `doc_category_knowledge_base` meta, and every reader of
 * that meta — Pro's REST filter, the archive query, the permalink builder —
 * matches on the string. A deleted knowledge base whose slug were left behind
 * would keep those categories pointing at a name nothing resolves, so
 * `categories_updated` reports how many were actually rewritten.
 *
 * @since 4.3.0
 */
class DeleteKnowledgeBase extends ProAbility {

	use ResolvesTerms;
	use FilesDocCategories;

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/delete-knowledge-base';
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

		$id   = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$term = $id > 0 ? get_term( $id, $this->kb_taxonomy() ) : null;

		if ( ! $term || is_wp_error( $term ) ) {
			return AbilityError::not_found( $this->term_object_name( $this->kb_taxonomy() ), $id );
		}

		$name = (string) $term->name;
		$slug = (string) $term->slug;

		// Read the membership before the delete: if the delete refuses, nothing
		// has been unfiled and the site is exactly as it was.
		$assigned = $this->assigned_categories( $slug );

		if ( is_wp_error( $assigned ) ) {
			return $assigned;
		}

		$deleted = $this->dispatch( 'DELETE', '/knowledge_base/' . $id, [ 'force' => true ], 'wp/v2' );

		if ( is_wp_error( $deleted ) ) {
			return $this->map_kb_error( $deleted, 'knowledge_base', __( 'delete a knowledge base', 'betterdocs-pro' ) );
		}

		$updated = 0;

		foreach ( $assigned as $category ) {
			$unfiled = $this->unfile_category( (int) $category['id'], $slug );

			if ( is_wp_error( $unfiled ) ) {
				return $unfiled;
			}

			if ( $unfiled ) {
				++$updated;
			}
		}

		return [
			'id'                 => $id,
			'name'               => $name,
			'slug'               => $slug,
			'deleted'            => true,
			'categories_updated' => $updated
		];
	}

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'delete a knowledge base', 'betterdocs-pro' );
	}
}
