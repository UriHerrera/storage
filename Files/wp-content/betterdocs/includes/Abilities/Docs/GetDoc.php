<?php
/**
 * Read one doc ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Docs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesDocs;
use WPDeveloper\BetterDocs\Utils\BlockBuilder;

/**
 * Read one doc, by id or by slug.
 *
 * Returns **both** forms of the body: `content.raw` is the stored block markup,
 * which is what `bd-update-doc` expects back, and `content.rendered` is what a
 * visitor sees. An agent asked to "fix the second paragraph" needs the raw
 * form; an agent asked "does this page mention X" needs the rendered one.
 *
 * `faq_groups` reports which FAQ groups the doc's blocks filter to, decoded
 * from the block attributes by {@see BlockBuilder::find_faq_blocks()} — the
 * question `bd-attach-faq` will need answered before it changes anything.
 * It is read from the post itself rather than from the response, so it is the
 * same answer whichever context the read ran in.
 *
 * **The context degrades rather than refusing.** `WP_REST_Posts_Controller`
 * answers `context=edit` with 403 `rest_forbidden_context` for a doc the caller
 * may read but not edit (measured on WordPress 7.1: `mcp-author` on another
 * user's published doc). Refusing outright would tell an agent it cannot read a
 * doc it demonstrably can, so the request is made in `view` context instead and
 * `content.raw` comes back empty — which is exactly what "you may read this,
 * not edit it" means.
 *
 * @since 4.9.0
 */
class GetDoc extends AbilityBase {

	use ResolvesTerms;
	use ShapesDocs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/get-doc';
		$this->label       = __( 'Get doc', 'betterdocs' );
		$this->description = __( 'Read one BetterDocs doc by id or slug: its raw block content, its rendered HTML, its categories, tags and knowledge bases, its view and reaction counts, and which FAQ groups its FAQ blocks show.', 'betterdocs' );
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
				'id'      => [
					'type'        => 'integer',
					'description' => __( 'The doc id. Give this or slug.', 'betterdocs' )
				],
				'slug'    => [
					'type'        => 'string',
					'description' => __( 'The doc slug, as it appears in the URL. Give this or id.', 'betterdocs' )
				],
				'context' => [
					'type'        => 'string',
					'enum'        => [ 'view', 'edit' ],
					'default'     => 'edit',
					'description' => __( 'edit (default) includes the raw block content, and quietly falls back to view for a doc you may read but not edit; view returns only the rendered HTML.', 'betterdocs' )
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
			'properties' => array_merge(
				self::doc_summary_schema(),
				[
					'content'     => [
						'type'       => 'object',
						'properties' => [
							'raw'      => [ 'type' => 'string' ],
							'rendered' => [ 'type' => 'string' ]
						]
					],
					'total_views' => [ 'type' => 'integer' ],
					'reactions'   => [
						'type'       => 'object',
						'properties' => [
							'happy'  => [ 'type' => 'integer' ],
							'normal' => [ 'type' => 'integer' ],
							'sad'    => [ 'type' => 'integer' ]
						]
					],
					'faq_groups'  => [
						'type'  => 'array',
						'items' => [ 'type' => 'integer' ]
					]
				]
			)
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$context = isset( $input['context'] ) ? (string) $input['context'] : 'edit';
		$id      = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$slug    = isset( $input['slug'] ) ? trim( (string) $input['slug'] ) : '';

		if ( $id <= 0 && '' === $slug ) {
			return AbilityError::invalid_input( 'id', __( 'Give either id or slug.', 'betterdocs' ) );
		}

		if ( $id <= 0 ) {
			$id = $this->id_for_slug( $slug );

			if ( is_wp_error( $id ) ) {
				return $id;
			}
		}

		$post = $this->require_doc( $id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! current_user_can( 'read_post', $post->ID ) ) {
			return AbilityError::capability_missing(
				'read_private_docs',
				sprintf(
					/* translators: %d: doc id. */
					__( 'read doc #%d', 'betterdocs' ),
					(int) $post->ID
				)
			);
		}

		// Edit context is refused outright for a doc this user may read but not
		// edit, so ask for it only when it can be given.
		if ( 'edit' === $context && ! current_user_can( 'edit_post', $post->ID ) ) {
			$context = 'view';
		}

		$doc = $this->dispatch( 'GET', '/docs/' . (int) $post->ID, [ 'context' => $context ], 'wp/v2' );

		if ( is_wp_error( $doc ) ) {
			return $this->map_rest_error(
				$doc,
				sprintf(
					/* translators: %d: doc id. */
					__( 'read doc #%d', 'betterdocs' ),
					(int) $post->ID
				),
				$post
			);
		}

		$doc = (array) $doc;

		return array_merge(
			$this->doc_summary( $doc ),
			[
				'content'     => [
					// Empty in view context: WordPress does not hand the stored
					// markup to someone who cannot edit the post, and neither
					// should this.
					'raw'      => isset( $doc['content']['raw'] ) ? (string) $doc['content']['raw'] : '',
					'rendered' => isset( $doc['content']['rendered'] ) ? (string) $doc['content']['rendered'] : ''
				],
				'total_views' => isset( $doc['total_views'] ) ? (int) $doc['total_views'] : 0,
				'reactions'   => $this->reactions( isset( $doc['reactions'] ) ? (array) $doc['reactions'] : [] ),
				// From the post, not the response: the block attributes are the
				// same whichever context was granted, and reporting no FAQ
				// groups because the context was narrowed would be a wrong
				// answer rather than a withheld one.
				'faq_groups'  => $this->faq_group_ids( (string) $post->post_content )
			]
		);
	}

	/**
	 * The doc id behind a slug.
	 *
	 * `status => any` because an agent asked to finish a draft knows its slug,
	 * not its id; the ability's own `edit_docs` gate and the `read_post` check
	 * below are what decide whether it may see it.
	 *
	 * @since 4.9.0
	 *
	 * @param string $slug Doc slug.
	 * @return int|\WP_Error
	 */
	protected function id_for_slug( $slug ) {
		$found = $this->dispatch(
			'GET',
			'/docs',
			[
				'slug'     => $slug,
				'status'   => 'any',
				'per_page' => 1,
				'context'  => 'edit'
			],
			'wp/v2'
		);

		if ( is_wp_error( $found ) ) {
			return $this->map_rest_error( $found, __( 'look a doc up by slug', 'betterdocs' ) );
		}

		if ( ! is_array( $found ) || empty( $found[0]['id'] ) ) {
			return AbilityError::not_found( 'doc', $slug );
		}

		return (int) $found[0]['id'];
	}

	/**
	 * Reaction counts as integers, with every key present.
	 *
	 * @since 4.9.0
	 *
	 * @param array $reactions What the REST field returned.
	 * @return array
	 */
	protected function reactions( array $reactions ) {
		return [
			'happy'  => isset( $reactions['happy'] ) ? (int) $reactions['happy'] : 0,
			'normal' => isset( $reactions['normal'] ) ? (int) $reactions['normal'] : 0,
			'sad'    => isset( $reactions['sad'] ) ? (int) $reactions['sad'] : 0
		];
	}

	/**
	 * FAQ group ids the doc's FAQ blocks include.
	 *
	 * @since 4.9.0
	 *
	 * @param string $raw Raw block content.
	 * @return int[]
	 */
	protected function faq_group_ids( $raw ) {
		$ids = [];

		foreach ( BlockBuilder::find_faq_blocks( $raw ) as $block ) {
			foreach ( $block['include'] as $group ) {
				if ( isset( $group['id'] ) ) {
					$ids[] = (int) $group['id'];
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
