<?php
/**
 * List FAQs ability.
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
 * Read the questions: all of them, or one group's, with their answers.
 *
 * `group_name` here is **find-only** — a name that matches nothing is a
 * `not_found`, never a new group. A filter that created what it was looking for
 * would answer "no results" and litter the taxonomy at the same time (the same
 * rule `bd-list-docs` follows for categories and tags).
 *
 * `status` defaults to `any`, because an agent asked to fix a draft answer has
 * to be able to see it.
 *
 * @since 4.9.0
 */
class ListFAQs extends AbilityBase {

	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/list-faqs';
		$this->label       = __( 'List FAQs', 'betterdocs' );
		$this->description = __( 'List BetterDocs FAQs with their questions, answers and groups. Filter by group, search text or status. Naming a group that does not exist is an error here, not an invitation to create it.', 'betterdocs' );
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
				'group_id'   => [
					'type'        => 'integer',
					'description' => __( 'Only FAQs in this group, by id.', 'betterdocs' )
				],
				'group_name' => [
					'type'        => 'string',
					'description' => __( 'Only FAQs in this group, by name or slug. The group must already exist. Send group_id or group_name, not both.', 'betterdocs' )
				],
				'search'     => [
					'type'        => 'string',
					'description' => __( 'Free-text search over questions and answers.', 'betterdocs' )
				],
				'status'     => [
					'type'        => 'string',
					'enum'        => [ 'any', 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
					'default'     => 'any',
					'description' => __( 'Post status to list. Defaults to any, which is everything except trashed FAQs.', 'betterdocs' )
				],
				'page'       => [
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1
				],
				'per_page'   => [
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => 20,
					'description' => __( 'FAQs per page, 1–100. Answers are returned in full, so keep this small.', 'betterdocs' )
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
						'properties' => self::faq_shape_schema()
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

		// Find-only: a listing must never create the group it was asked about.
		$group = $this->group_ref_input( $input, false );

		if ( is_wp_error( $group ) ) {
			return $group;
		}

		$params = [
			// Edit context, for `content.raw`: the answer an agent should read
			// and rewrite is the stored HTML, not the filtered render. Every
			// caller holds `edit_others_docs`, which is what the posts
			// controller asks for here.
			'context'  => 'edit',
			'status'   => isset( $input['status'] ) ? (string) $input['status'] : 'any',
			'page'     => $page,
			'per_page' => $per_page,
			'orderby'  => 'date',
			'order'    => 'desc'
		];

		if ( null !== $group ) {
			$params[ self::GROUP_TAXONOMY ] = [ (int) $group ];
		}

		if ( isset( $input['search'] ) && '' !== (string) $input['search'] ) {
			$params['search'] = (string) $input['search'];
		}

		$response = $this->dispatch_response( 'GET', '/' . self::FAQ_POST_TYPE, $params, 'wp/v2' );

		if ( $response->is_error() ) {
			$error = $response->as_error();

			// A page past the last one is an empty page, not an upstream fault.
			if ( $this->is_page_out_of_range( $error ) ) {
				return $this->empty_page( '/' . self::FAQ_POST_TYPE, $params, $page, $per_page, 'wp/v2' );
			}

			return $this->map_faq_error( $error, __( 'list FAQs', 'betterdocs' ) );
		}

		$headers = $response->get_headers();
		$items   = [];

		foreach ( (array) $response->get_data() as $post ) {
			$items[] = $this->faq_shape( (array) $post );
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
