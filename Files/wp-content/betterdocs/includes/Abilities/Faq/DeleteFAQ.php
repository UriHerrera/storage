<?php
/**
 * Delete an FAQ ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesFAQs;

/**
 * Remove one FAQ. **Permanently.**
 *
 * `FAQBuilder::delete_betterdocs_faq()` calls `wp_delete_post()` without
 * forcing, which for `post` and `page` would mean the trash — but core routes
 * only those two post types there, so a custom post type like
 * `betterdocs_faq` is deleted outright. Measured on WordPress 7.1: the FAQ is
 * gone from the database and a second call answers `not_found`.
 *
 * The answer still reports `trashed`, from the post's state afterwards, so a
 * site whose plugins do intervene is described accurately rather than assumed.
 *
 * @since 4.9.0
 */
class DeleteFAQ extends AbilityBase {

	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/delete-faq';
		$this->label       = __( 'Delete FAQ', 'betterdocs' );
		$this->description = __( 'Delete a BetterDocs FAQ. This is permanent on a standard site: WordPress sends only posts and pages to the trash, so a custom post type like an FAQ is removed outright. The answer reports whether it was trashed or deleted.', 'betterdocs' );
		$this->capability  = 'edit_others_docs';
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
			'priority'      => 2.5,
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
				'id' => [
					'type'        => 'integer',
					'description' => __( 'The FAQ id. Required.', 'betterdocs' )
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
				'id'       => [ 'type' => 'integer' ],
				'deleted'  => [ 'type' => 'boolean' ],
				'trashed'  => [ 'type' => 'boolean' ],
				'question' => [ 'type' => 'string' ]
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
		$id   = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$post = $id > 0 ? get_post( $id ) : null;

		if ( ! $post || self::FAQ_POST_TYPE !== $post->post_type ) {
			return AbilityError::not_found( 'FAQ', $id );
		}

		$question = (string) $post->post_title;

		$result = $this->dispatch( 'POST', '/faq/delete_post', [ 'post_id' => $id ], self::FAQ_NS );

		if ( is_wp_error( $result ) ) {
			return $this->map_faq_error( $result, __( 'delete an FAQ', 'betterdocs' ) );
		}

		if ( empty( $result ) ) {
			return AbilityError::upstream( __( 'BetterDocs did not confirm the FAQ deletion.', 'betterdocs' ) );
		}

		$after = get_post( $id );

		return [
			'id'       => $id,
			'deleted'  => true,
			'trashed'  => (bool) ( $after && 'trash' === $after->post_status ),
			'question' => $question
		];
	}
}
