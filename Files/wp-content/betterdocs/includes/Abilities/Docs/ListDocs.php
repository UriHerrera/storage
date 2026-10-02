<?php
/**
 * List docs ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Docs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesDocs;

/**
 * Find docs, by search text, term, status or author.
 *
 * Filters **never create the term they filter on**. `{tag: "typo"}` answers
 * `not_found` rather than quietly creating `typo`, returning nothing, and
 * leaving a junk term behind — the failure mode that makes a read tool
 * dangerous.
 *
 * The page counts come from `X-WP-Total` / `X-WP-TotalPages` on the internal
 * response, so `total` is the real number of matches and not the size of the
 * page that was returned. Measured on WordPress 7.1: those headers count every
 * match while `WP_REST_Posts_Controller` drops the items the caller may not
 * read, so a page legitimately comes back shorter than `total` suggests. The
 * headers are still the right source — `total_pages` is what a client loops
 * over — and the tool description says the two can differ.
 *
 * The listing runs in **view** context, not edit: in edit context the same
 * controller returns only the docs the caller may *edit*, so an author asking
 * "what documentation exists?" would be shown their own three drafts and
 * nothing else (measured: 1 item of 11 for `mcp-author`, against 10 in view
 * context). A summary needs no raw fields, so view is both correct and more
 * useful.
 *
 * @since 4.9.0
 */
class ListDocs extends AbilityBase {

	use ResolvesTerms;
	use ShapesDocs;

	/**
	 * Statuses that may be asked for.
	 *
	 * @since 4.9.0
	 */
	const STATUSES = [ 'any', 'publish', 'draft', 'private', 'pending', 'future', 'trash' ];

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/list-docs';
		$this->label       = __( 'List docs', 'betterdocs' );
		$this->description = __( 'List and filter BetterDocs docs by search text, category, tag, knowledge base, glossary term, status, author or slug. Terms may be given by id, slug or name; a filter never creates a term. Returns a page of doc summaries. total counts every match, while items omits any doc this user may not read, so a page can be shorter than total suggests.', 'betterdocs' );
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
			'properties'           => [
				'search'         => [
					'type'        => 'string',
					'description' => __( 'Free-text search over title and content.', 'betterdocs' )
				],
				'category'       => [
					'type'        => [ 'integer', 'string' ],
					'description' => __( 'A doc category, by id, slug or name. Never created.', 'betterdocs' )
				],
				'tag'            => [
					'type'        => [ 'integer', 'string' ],
					'description' => __( 'A doc tag, by id, slug or name. Never created.', 'betterdocs' )
				],
				'knowledge_base' => [
					'type'        => [ 'integer', 'string' ],
					'description' => __( 'A knowledge base, by id, slug or name. Never created.', 'betterdocs' )
				],
				'glossary'       => [
					'type'        => [ 'integer', 'string' ],
					'description' => __( 'A glossary term, by id, slug or name. Never created. Needs the enable_glossaries setting on.', 'betterdocs' )
				],
				'status'         => [
					'type'        => 'string',
					'enum'        => self::STATUSES,
					'default'     => 'any',
					'description' => __( 'Publication status to filter on. Defaults to any.', 'betterdocs' )
				],
				'author'         => [
					'type'        => 'integer',
					'description' => __( 'Only docs by this user id.', 'betterdocs' )
				],
				'slug'           => [
					'type'        => 'string',
					'description' => __( 'Only the doc with this exact slug.', 'betterdocs' )
				],
				'orderby'        => [
					'type'        => 'string',
					'enum'        => [ 'date', 'modified', 'title', 'menu_order', 'relevance', 'id' ],
					'default'     => 'date',
					'description' => __( 'Sort field. relevance only means anything alongside search.', 'betterdocs' )
				],
				'order'          => [
					'type'    => 'string',
					'enum'    => [ 'asc', 'desc' ],
					'default' => 'desc'
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
					'default'     => 20,
					'description' => __( 'Docs per page, 1–100.', 'betterdocs' )
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
						'properties' => self::doc_summary_schema()
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
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;

		$params = [
			'context'  => 'view',
			'status'   => isset( $input['status'] ) ? (string) $input['status'] : 'any',
			'orderby'  => isset( $input['orderby'] ) ? (string) $input['orderby'] : 'date',
			'order'    => isset( $input['order'] ) ? (string) $input['order'] : 'desc',
			'page'     => $page,
			'per_page' => $per_page
		];

		foreach ( [ 'search', 'slug' ] as $field ) {
			if ( isset( $input[ $field ] ) && '' !== (string) $input[ $field ] ) {
				$params[ $field ] = (string) $input[ $field ];
			}
		}

		if ( isset( $input['author'] ) ) {
			$params['author'] = (int) $input['author'];
		}

		$filters = [
			'category'       => 'doc_category',
			'tag'            => 'doc_tag',
			'knowledge_base' => 'knowledge_base',
			'glossary'       => 'glossaries'
		];

		foreach ( $filters as $field => $taxonomy ) {
			if ( ! isset( $input[ $field ] ) || '' === $input[ $field ] ) {
				continue;
			}

			// `false`: a filter never creates the term it is filtering on.
			$ids = $this->resolve_terms( [ $input[ $field ] ], $taxonomy, false );

			if ( is_wp_error( $ids ) ) {
				return $ids;
			}

			$params[ $taxonomy ] = $ids;
		}

		$response = $this->dispatch_response( 'GET', '/docs', $params, 'wp/v2' );

		if ( $response->is_error() ) {
			$error = $response->as_error();

			// A page past the last one is an empty page, not a missing doc.
			if ( $this->is_page_out_of_range( $error ) ) {
				return $this->empty_page( '/docs', $params, $page, $per_page, 'wp/v2' );
			}

			return $this->map_rest_error( $error, __( 'list docs', 'betterdocs' ) );
		}

		$headers = $response->get_headers();
		$items   = [];

		foreach ( (array) $response->get_data() as $doc ) {
			$items[] = $this->doc_summary( (array) $doc );
		}

		return [
			'items'       => $items,
			'total'       => isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $items ),
			'total_pages' => isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1,
			'page'        => $page,
			'per_page'    => $per_page
		];
	}
}
