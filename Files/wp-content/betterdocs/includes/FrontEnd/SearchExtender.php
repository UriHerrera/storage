<?php

namespace WPDeveloper\BetterDocs\FrontEnd;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Extends WordPress search SQL on `docs` queries to also match docs whose
 * assigned `doc_tag` or `doc_category` term names contain the search term.
 *
 * The filter is registered globally so it applies to every search query against
 * the `docs` post type — including WP core's `GET /wp/v2/docs?search=...`,
 * BetterDocs' `/betterdocs/v1/search`, and the shortcode/widget AJAX paths.
 */
class SearchExtender {
	public function __construct() {
		add_filter( 'posts_search', [ $this, 'extend_search' ], 20, 2 );
	}

	/**
	 * Inject an OR clause that matches docs whose related taxonomy term names
	 * contain the search term.
	 *
	 * @param string    $search The search SQL clause (already begins with " AND (").
	 * @param \WP_Query $query  The WP_Query instance.
	 * @return string Modified search SQL.
	 */
	public function extend_search( $search, $query ) {
		global $wpdb;

		if ( empty( $search ) ) {
			return $search;
		}

		$post_type = isset( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : '';
		if ( is_array( $post_type ) ) {
			if ( ! in_array( 'docs', $post_type, true ) ) {
				return $search;
			}
		} elseif ( $post_type !== 'docs' ) {
			return $search;
		}

		$search_term = isset( $query->query_vars['s'] ) ? (string) $query->query_vars['s'] : '';
		if ( $search_term === '' ) {
			return $search;
		}

		$taxonomies = [ 'doc_tag', 'doc_category' ];
		$like       = '%' . $wpdb->esc_like( $search_term ) . '%';

		$placeholders = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );
		$args         = $taxonomies;
		$args[]       = $like;

		// $placeholders is a generated run of %s tokens bound via $args below; all
		// interpolated identifiers are $wpdb core table names, not user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$subquery = $wpdb->prepare(
			"{$wpdb->posts}.ID IN (
				SELECT DISTINCT tr.object_id
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
				WHERE tt.taxonomy IN ({$placeholders}) AND t.name LIKE %s
			)",
			$args
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return preg_replace( '/^\s*AND\s*\(/', " AND ({$subquery} OR ", $search, 1 );
	}
}
