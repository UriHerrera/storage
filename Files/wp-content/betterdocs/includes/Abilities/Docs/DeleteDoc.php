<?php
/**
 * Delete a doc ability.
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

/**
 * Trash a doc, or delete it for good.
 *
 * `force` defaults to **false**, so the ordinary call is reversible: the doc
 * goes to the trash and a human can put it back. `force: true` is the one that
 * cannot be undone, which is why `destructiveHint` is true on this tool and a
 * client is expected to confirm before calling it.
 *
 * @since 4.9.0
 */
class DeleteDoc extends AbilityBase {

	use ResolvesTerms;
	use ShapesDocs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/delete-doc';
		$this->label       = __( 'Delete doc', 'betterdocs' );
		$this->description = __( 'Trash a BetterDocs doc, or delete it permanently with force:true. Trashing is reversible; force is not.', 'betterdocs' );
		$this->capability  = 'delete_docs';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => false,
			'priority'      => 3.0,
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
			'required'             => [ 'id' ],
			'properties'           => [
				'id'    => [
					'type'        => 'integer',
					'description' => __( 'The doc id to delete. Required.', 'betterdocs' )
				],
				'force' => [
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'false (default) moves the doc to the trash and can be undone. true deletes it permanently.', 'betterdocs' )
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
				'id'      => [ 'type' => 'integer' ],
				'deleted' => [ 'type' => 'boolean' ],
				'trashed' => [ 'type' => 'boolean' ],
				'title'   => [ 'type' => 'string' ]
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
		$post = $this->require_doc( isset( $input['id'] ) ? $input['id'] : 0 );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$force = ! empty( $input['force'] );

		if ( ! current_user_can( 'delete_post', $post->ID ) ) {
			return AbilityError::capability_missing(
				$this->deleting_capability( $post ),
				sprintf(
					/* translators: %d: doc id. */
					__( 'delete doc #%d', 'betterdocs' ),
					(int) $post->ID
				)
			);
		}

		$title = get_the_title( $post );

		$deleted = $this->dispatch(
			'DELETE',
			'/docs/' . (int) $post->ID,
			[ 'force' => $force ],
			'wp/v2'
		);

		if ( is_wp_error( $deleted ) ) {
			return $this->map_rest_error(
				$deleted,
				sprintf(
					/* translators: %d: doc id. */
					__( 'delete doc #%d', 'betterdocs' ),
					(int) $post->ID
				),
				$post
			);
		}

		return [
			'id'      => (int) $post->ID,
			'deleted' => true,
			'trashed' => ! $force,
			'title'   => (string) $title
		];
	}
}
