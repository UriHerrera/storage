<?php
/**
 * List doc categories or doc tags ability.
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
 * Read the taxonomy: what categories and tags exist, how many docs are in each,
 * and which knowledge bases the categories belong to.
 *
 * The one tool an agent should call before creating terms, because it is what
 * turns "add this to the installation guide category" into an id instead of a
 * near-miss duplicate.
 *
 * `hide_empty` defaults to **false**: a category with no docs in it yet is
 * exactly the one an agent is about to file something into, and hiding it would
 * make the tool answer "it does not exist" about a category that does. With it
 * on, `total` can come back lower than the number of items — measured on the
 * rig: `get_terms( hide_empty => true )` keeps `u20-parent` because its child
 * has a doc, while `get_terms( fields => count )`, which is where `X-WP-Total`
 * comes from, runs a flat `count > 0` and answers 3 for those same 4 terms.
 * WordPress' behaviour, in core, on any hierarchical taxonomy; the field
 * description says so rather than pretending the numbers agree.
 *
 * `knowledge_base` is a `doc_category` filter that BetterDocs Pro implements
 * (`MultipleKB::modify_doc_category_rest_query()`), so on a site without Pro it
 * is refused with the typed Pro state rather than silently ignored — a filter
 * that is dropped returns *every* category, which reads as "they are all in this
 * knowledge base".
 *
 * @since 4.9.0
 */
class ListTerms extends AbilityBase {

	use ResolvesTerms;
	use ShapesTerms;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/list-terms';
		$this->label       = __( 'List terms', 'betterdocs' );
		$this->description = __( 'List BetterDocs doc categories or doc tags, with their doc counts, parents and knowledge bases. Filter by search text, parent or knowledge base. Call this before creating terms so you reuse what exists instead of making near-duplicates.', 'betterdocs' );
		$this->capability  = 'edit_docs';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.5,
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
			'required'             => [ 'taxonomy' ],
			'properties'           => [
				'taxonomy'       => self::taxonomy_schema(),
				'search'         => [
					'type'        => 'string',
					'description' => __( 'Free-text search over term names and slugs.', 'betterdocs' )
				],
				'parent'         => [
					'type'        => [ 'integer', 'string' ],
					'description' => __( 'Only the children of this doc category, by id or name. Use 0 for top-level terms only.', 'betterdocs' )
				],
				'knowledge_base' => [
					'type'        => [ 'integer', 'string' ],
					'description' => __( 'Only doc categories filed into this knowledge base, by id, slug or name. Needs BetterDocs Pro with Multiple Knowledge Base on.', 'betterdocs' )
				],
				'hide_empty'     => [
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'true drops terms with no docs in them. Defaults to false. Both taxonomies are hierarchical, so WordPress keeps a parent whose child has docs even when the parent itself has none — and counts it out of total, which is why total can be lower than the number of items on this filter.', 'betterdocs' )
				],
				'orderby'        => [
					'type'        => 'string',
					'enum'        => [ 'name', 'count', 'slug', 'id', 'description', 'doc_category_order' ],
					'default'     => 'name',
					'description' => __( 'Sort field. doc_category_order is the manual order set in the BetterDocs admin, and applies to doc_category only.', 'betterdocs' )
				],
				'order'          => [
					'type'    => 'string',
					'enum'    => [ 'asc', 'desc' ],
					'default' => 'asc'
				],
				'page'           => [
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1
				],
				'per_page'       => [
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => 50,
					'description' => __( 'Terms per page, 1–100.', 'betterdocs' )
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
				'items'       => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => self::term_shape_schema()
					]
				],
				'total'       => [ 'type' => 'integer' ],
				'total_pages' => [ 'type' => 'integer' ],
				'page'        => [ 'type' => 'integer' ],
				'per_page'    => [ 'type' => 'integer' ]
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
		$page     = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 50;

		// Before anything is read or written: a taxonomy that is switched off
		// answers with the setting to change, not with `not_found` (ADR-061).
		$available = $this->taxonomy_available( $taxonomy );

		if ( is_wp_error( $available ) ) {
			return $available;
		}

		$params = [
			// View, not edit. `WP_REST_Terms_Controller::get_items_permissions_check()`
			// refuses `context=edit` with `rest_forbidden_context` unless the
			// caller holds the taxonomy's `edit_terms` capability — which an
			// author does not, so this read-only tool would have been
			// administrator- and editor-only. Measured on the rig: term `meta`,
			// `doc_category_order` and `total_docs_count` are all present in
			// view context anyway, so nothing is lost by asking for less.
			'context'    => 'view',
			'hide_empty' => ! empty( $input['hide_empty'] ),
			'orderby'    => isset( $input['orderby'] ) ? (string) $input['orderby'] : 'name',
			'order'      => isset( $input['order'] ) ? (string) $input['order'] : 'asc',
			'page'       => $page,
			'per_page'   => $per_page
		];

		if ( isset( $input['search'] ) && '' !== (string) $input['search'] ) {
			$params['search'] = (string) $input['search'];
		}

		$parent = $this->parent_param( $input, $taxonomy );

		if ( is_wp_error( $parent ) ) {
			return $parent;
		}

		if ( null !== $parent ) {
			$params['parent'] = $parent;
		}

		if ( isset( $input['knowledge_base'] ) && '' !== $input['knowledge_base'] ) {
			if ( 'doc_category' !== $taxonomy ) {
				return AbilityError::invalid_input(
					'knowledge_base',
					__( 'Only doc categories belong to knowledge bases.', 'betterdocs' )
				);
			}

			$slugs = $this->kb_slugs_for( [ $input['knowledge_base'] ] );

			if ( is_wp_error( $slugs ) ) {
				return $slugs;
			}

			// Pro filters on the slug, not the id, and does so with a `LIKE`
			// over the membership meta — which over-reports (finding D). The KB
			// path re-checks each match against its own membership before it
			// answers.
			return $this->list_by_knowledge_base(
				$taxonomy,
				$params,
				isset( $slugs[0] ) ? $slugs[0] : '',
				$page,
				$per_page
			);
		}

		$response = $this->dispatch_terms( $taxonomy, $params );

		if ( $response->is_error() ) {
			return $this->map_term_error( $response->as_error(), $taxonomy, __( 'list terms', 'betterdocs' ) );
		}

		$headers = $response->get_headers();
		$items   = [];

		foreach ( (array) $response->get_data() as $term ) {
			$items[] = $this->term_shape( (array) $term, $taxonomy );
		}

		return [
			'items'       => $items,
			'total'       => isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $items ),
			'total_pages' => isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1,
			'page'        => $page,
			'per_page'    => $per_page
		];
	}

	/**
	 * List the doc categories that truly belong to a knowledge base.
	 *
	 * Multiple Knowledge Base filters `knowledge_base` with a `LIKE` over the
	 * serialised `doc_category_knowledge_base` meta, so the query also returns a
	 * category filed under a knowledge base whose slug merely *contains* the
	 * requested one (`qa-kb` matches `qa-kb-2`). Pro's own KB tools re-check the
	 * membership meta after the query; this list did not, so a `qa-kb-2`
	 * category was reported under `qa-kb` (ADR-059, finding D).
	 *
	 * The whole `LIKE` match set is walked, each candidate confirmed against its
	 * own membership slugs (the exact `in_array` Pro uses), and the confirmed
	 * set is paged in PHP so `total` and `total_pages` count what the tool
	 * returns rather than the wider `LIKE` match. The `betterdocs/v1/doc-categories-kb`
	 * reader is not used for this: it carries the same `LIKE`, and its own
	 * `parent: 0` / `hide_empty: true` constraints would drop legitimate members.
	 *
	 * @since 4.9.0
	 *
	 * @param string $taxonomy    Always `doc_category` here.
	 * @param array  $base_params The query parameters built for the listing.
	 * @param string $slug        Resolved knowledge-base slug.
	 * @param int    $page        Requested page.
	 * @param int    $per_page    Requested per_page.
	 * @return array|\WP_Error
	 */
	protected function list_by_knowledge_base( $taxonomy, array $base_params, $slug, $page, $per_page ) {
		if ( '' === (string) $slug ) {
			// Matches the terms controller's own count for an empty collection.
			return [
				'items'       => [],
				'total'       => 0,
				'total_pages' => 0,
				'page'        => (int) $page,
				'per_page'    => (int) $per_page
			];
		}

		$gather      = array_merge(
			$base_params,
			[
				'knowledge_base' => (string) $slug,
				'per_page'       => 100
			]
		);
		$members     = [];
		$total_pages = 1;
		$p           = 1;

		// 20 pages of 100 is two thousand categories in one knowledge base; past
		// that something is wrong with the site, not with the paging (the cap
		// Pro's own `assigned_categories()` uses).
		while ( $p <= $total_pages && $p <= 20 ) {
			$gather['page'] = $p;
			$response       = $this->dispatch_terms( $taxonomy, $gather );

			if ( $response->is_error() ) {
				return $this->map_term_error( $response->as_error(), $taxonomy, __( 'list terms', 'betterdocs' ) );
			}

			foreach ( (array) $response->get_data() as $term ) {
				$term = (array) $term;

				if ( in_array( (string) $slug, $this->kb_slugs( $term ), true ) ) {
					$members[] = $term;
				}
			}

			$headers     = $response->get_headers();
			$total_pages = isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1;
			++$p;
		}

		$total  = count( $members );
		$offset = ( (int) $page - 1 ) * (int) $per_page;
		$items  = [];

		foreach ( array_slice( $members, max( 0, $offset ), (int) $per_page ) as $term ) {
			$items[] = $this->term_shape( $term, $taxonomy );
		}

		return [
			'items'       => $items,
			'total'       => $total,
			'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
			'page'        => (int) $page,
			'per_page'    => (int) $per_page
		];
	}

	/**
	 * Run the collection request with this tool's own parameters intact.
	 *
	 * BetterDocs Pro's `InstantAnswer::order_ia_doc_taxonomies()` hooks
	 * `rest_doc_category_query` and, whenever `$_GET` is empty, overwrites
	 * `number`, `hide_empty`, `orderby`, `order` and `meta_key` with the
	 * Instant Answer widget's own settings — it is guarding against a widget
	 * request, but `$_GET` is empty for *every* internal `rest_do_request()`,
	 * which is exactly how an ability calls a route. Measured on the rig: a
	 * request for `per_page 100, hide_empty false, orderby name` reached
	 * `get_terms()` as `number 10, hide_empty 1, orderby meta_value_num`, so
	 * this tool's documented parameters did nothing and a site with more than
	 * ten doc categories could never page past the first ten.
	 *
	 * The fix is local and does not touch Pro: a filter at a later priority
	 * puts back what this request asked for, and only for this request —
	 * identity-compared, so nothing else in the page is affected. `orderby` is
	 * deliberately left alone when the caller asked for `doc_category_order`,
	 * because Free's own `PostType::modify_doc_category_rest_query()` rewrites
	 * that one into a meta-value sort at priority 10 and is right to.
	 *
	 * @since 4.9.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $params   Query parameters.
	 * @return \WP_REST_Response
	 */
	protected function dispatch_terms( $taxonomy, array $params ) {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/' . $taxonomy );

		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_query_params( $params );

		if ( 'doc_category' !== $taxonomy ) {
			return rest_do_request( $request );
		}

		$restore = function ( $args, $filtered_request ) use ( $request, $params ) {
			if ( $filtered_request !== $request ) {
				return $args;
			}

			$args['number']     = (int) $params['per_page'];
			$args['offset']     = ( (int) $params['page'] - 1 ) * (int) $params['per_page'];
			$args['hide_empty'] = (bool) $params['hide_empty'];
			$args['order']      = strtoupper( (string) $params['order'] );

			if ( 'doc_category_order' !== $params['orderby'] ) {
				$args['orderby'] = (string) $params['orderby'];
				unset( $args['meta_key'] );
			}

			return $args;
		};

		add_filter( 'rest_doc_category_query', $restore, 20, 2 );

		$response = rest_do_request( $request );

		remove_filter( 'rest_doc_category_query', $restore, 20 );

		return $response;
	}

	/**
	 * The `parent` query parameter, or null when there is none.
	 *
	 * `0` is a meaningful value here — "top level only" — so it cannot be
	 * folded into the "omitted" case.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $input    Validated input.
	 * @param string $taxonomy Taxonomy name.
	 * @return int|null|\WP_Error
	 */
	protected function parent_param( array $input, $taxonomy ) {
		if ( ! isset( $input['parent'] ) || '' === $input['parent'] ) {
			return null;
		}

		if ( 0 === $input['parent'] || '0' === $input['parent'] ) {
			return 0;
		}

		if ( 'doc_category' !== $taxonomy ) {
			return AbilityError::invalid_input(
				'parent',
				__( 'These tools nest doc categories only; a doc tag takes no parent.', 'betterdocs' )
			);
		}

		$ids = $this->resolve_terms( [ $input['parent'] ], 'doc_category', false );

		if ( is_wp_error( $ids ) ) {
			return $ids;
		}

		return isset( $ids[0] ) ? (int) $ids[0] : 0;
	}
}
