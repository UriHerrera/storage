<?php
/**
 * List FAQ groups ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesFAQs;

/**
 * What FAQ groups exist, how many questions are in each, and which of them the
 * front end is actually showing.
 *
 * The tool to call before creating a group or attaching one to a doc, because
 * it is what turns "the billing FAQ" into an id instead of a second group with
 * a near-identical name.
 *
 * **Order is the site's own.** `FAQBuilder::faq_category_orderby_meta()` hooks
 * `rest_betterdocs_faq_category_query` and sets `orderby`/`order` from the FAQ
 * Builder's header preference (the `betterdocs_faq_order` option) on every REST
 * request for this taxonomy — measured, not assumed. So this tool takes no
 * `orderby`: the list comes back in the order the FAQ Builder and the front end
 * show it, which is the order an agent should reason about anyway.
 *
 * @since 4.9.0
 */
class ListFAQGroups extends AbilityBase {

	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/list-faq-groups';
		$this->label       = __( 'List FAQ groups', 'betterdocs' );
		$this->description = __( 'List BetterDocs FAQ groups with their question counts and whether each is published or draft, in the order the site shows them. Call this before creating a group so you reuse what exists.', 'betterdocs' );
		$this->capability  = 'edit_others_docs';
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
			'properties'           => [
				'search'   => [
					'type'        => 'string',
					'description' => __( 'Free-text search over group names and slugs.', 'betterdocs' )
				],
				'status'   => [
					'type'        => 'string',
					'enum'        => [ 'any', 'publish', 'draft' ],
					'default'     => 'any',
					'description' => __( 'publish lists only the groups the front end shows, draft only the hidden ones. Defaults to any.', 'betterdocs' )
				],
				'page'     => [
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1
				],
				'per_page' => [
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => 50,
					'description' => __( 'Groups per page, 1–100.', 'betterdocs' )
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
						'properties' => self::group_shape_schema()
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
		$page     = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 50;
		$status   = isset( $input['status'] ) ? (string) $input['status'] : 'any';

		$params = [
			// View, not edit: `WP_REST_Terms_Controller` refuses `context=edit`
			// without the taxonomy's `edit_terms` capability, and this
			// taxonomy's is `edit_doc_terms` — which the FAQ tools deliberately
			// do not require (ADR-005). Term meta, the count and the draft count
			// are all present in view context anyway.
			'context'    => 'view',
			'hide_empty' => false,
			'page'       => $page,
			'per_page'   => $per_page
		];

		if ( isset( $input['search'] ) && '' !== (string) $input['search'] ) {
			$params['search'] = (string) $input['search'];
		}

		$response = $this->dispatch_groups( $params, $status );

		if ( $response->is_error() ) {
			return $this->map_faq_error( $response->as_error(), __( 'list FAQ groups', 'betterdocs' ) );
		}

		$headers = $response->get_headers();
		$items   = [];

		foreach ( (array) $response->get_data() as $term ) {
			$items[] = $this->group_shape( (array) $term );
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
	 * Run the collection request, narrowing it to one `status` when asked.
	 *
	 * The group's published/hidden state is a term meta the REST controller
	 * knows nothing about, so the filter goes in as a `meta_query` on this one
	 * request — identity-compared, so nothing else in the page is affected — and
	 * `X-WP-Total` then counts the same set the items came from. Filtering the
	 * page after the fact would have made `total` a lie.
	 *
	 * `draft` deliberately also matches a group with **no** `status` meta at
	 * all: the front end (`Query::faq_terms_query_args()`) shows only `status =
	 * 1`, so a group that predates the meta is hidden, and reporting it as
	 * published would be wrong.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $params Query parameters.
	 * @param string $status `any`, `publish` or `draft`.
	 * @return \WP_REST_Response
	 */
	protected function dispatch_groups( array $params, $status ) {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/' . self::GROUP_TAXONOMY );

		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_query_params( $params );

		if ( 'publish' !== $status && 'draft' !== $status ) {
			return rest_do_request( $request );
		}

		$meta_query = 'publish' === $status
			? [
				[
					'key'     => 'status',
					'value'   => '1',
					'compare' => '='
				]
			]
			: [
				'relation' => 'OR',
				[
					'key'     => 'status',
					'value'   => '1',
					'compare' => '!='
				],
				[
					'key'     => 'status',
					'compare' => 'NOT EXISTS'
				]
			];

		$narrow = function ( $args, $filtered_request ) use ( $request, $meta_query ) {
			if ( $filtered_request !== $request ) {
				return $args;
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the group's published state is a term meta; filtering on it is the feature.
			$args['meta_query'] = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) && ! empty( $args['meta_query'] )
				? [
					'relation' => 'AND',
					$args['meta_query'],
					$meta_query
				]
				: $meta_query;

			return $args;
		};

		add_filter( 'rest_' . self::GROUP_TAXONOMY . '_query', $narrow, 20, 2 );

		$response = rest_do_request( $request );

		remove_filter( 'rest_' . self::GROUP_TAXONOMY . '_query', $narrow, 20 );

		return $response;
	}
}
