<?php
/**
 * List knowledge bases ability.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;
use WPDeveloper\BetterDocsPro\Abilities\Traits\FilesDocCategories;

/**
 * Read the knowledge bases on this site, each with the doc categories filed
 * under it.
 *
 * The tool an agent should call before creating one, so it files docs into the
 * knowledge base that exists instead of making a near-duplicate.
 *
 * Read in **view** context and with `hide_empty` off, for the same two reasons
 * Free's `bd-list-terms` does (ADR-048): edit context is refused for anyone
 * without `edit_knowledge_base_terms`, which would make a read-only tool
 * administrator- and editor-only; and a knowledge base with nothing in it yet is
 * exactly the one an agent is about to file something into.
 *
 * @since 4.3.0
 */
class ListKnowledgeBases extends ProAbility {

	use ResolvesTerms;
	use FilesDocCategories;

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/list-knowledge-bases';
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

		$page     = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;

		$params = [
			'context'    => 'view',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'asc',
			'page'       => $page,
			'per_page'   => $per_page
		];

		if ( isset( $input['search'] ) && '' !== (string) $input['search'] ) {
			$params['search'] = (string) $input['search'];
		}

		$response = $this->dispatch_response( 'GET', '/knowledge_base', $params, 'wp/v2' );

		if ( $response->is_error() ) {
			return $this->map_kb_error( $response->as_error(), 'knowledge_base', __( 'list knowledge bases', 'betterdocs-pro' ) );
		}

		$items = [];

		foreach ( (array) $response->get_data() as $term ) {
			$term = (array) $term;
			$slug = isset( $term['slug'] ) ? (string) $term['slug'] : '';

			$categories = $this->assigned_categories( $slug );

			if ( is_wp_error( $categories ) ) {
				return $categories;
			}

			$items[] = $this->kb_shape( $term, $categories );
		}

		$headers = $response->get_headers();

		return [
			'items'       => $items,
			'total'       => isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $items ),
			'total_pages' => isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1,
			'page'        => $page,
			'per_page'    => $per_page
		];
	}

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'list knowledge bases', 'betterdocs-pro' );
	}
}
