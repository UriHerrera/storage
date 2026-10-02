<?php
/**
 * The one doc shape every Docs ability answers with, and the translation from
 * WordPress' REST errors to BetterDocs' typed ones.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;

/**
 * Five tools, one summary object.
 *
 * `bd-create-doc`, `bd-update-doc`, `bd-get-doc` and every item of
 * `bd-list-docs` return the same keys in the same order, so an agent learns the
 * shape once. `bd-get-doc` adds the content and the analytics on top.
 *
 * The error half matters as much. `WP_REST_Posts_Controller` refuses with
 * `rest_cannot_edit` whether the caller lacks `edit_others_docs`, or
 * `edit_published_docs`, or is simply not allowed near the post — one code for
 * three different fixes. {@see self::editing_capability()} works out which
 * capability a specific post actually needs from that post's author and status,
 * so the refusal names the thing an administrator would have to grant.
 *
 * @since 4.9.0
 */
trait ShapesDocs {

	/**
	 * The BetterDocs post type these tools address.
	 *
	 * @since 4.9.0
	 */
	protected static $doc_post_type = 'docs';

	/**
	 * The doc summary every Docs tool answers with.
	 *
	 * @since 4.9.0
	 *
	 * @param array $doc A `wp/v2/docs` item.
	 * @return array
	 */
	protected function doc_summary( array $doc ) {
		$id = isset( $doc['id'] ) ? (int) $doc['id'] : 0;

		return [
			'id'              => $id,
			'title'           => $this->rendered_or_raw( isset( $doc['title'] ) ? $doc['title'] : '' ),
			'slug'            => isset( $doc['slug'] ) ? (string) $doc['slug'] : '',
			'status'          => isset( $doc['status'] ) ? (string) $doc['status'] : '',
			'url'             => isset( $doc['link'] ) ? (string) $doc['link'] : '',
			'edit_url'        => $id > 0 ? admin_url( 'post.php?post=' . $id . '&action=edit' ) : '',
			'excerpt'         => $this->rendered_or_raw( isset( $doc['excerpt'] ) ? $doc['excerpt'] : '' ),
			'categories'      => $this->term_summaries( isset( $doc['doc_category'] ) ? (array) $doc['doc_category'] : [], 'doc_category' ),
			'tags'            => $this->term_summaries( isset( $doc['doc_tag'] ) ? (array) $doc['doc_tag'] : [], 'doc_tag' ),
			'knowledge_bases' => $this->term_summaries( isset( $doc['knowledge_base'] ) ? (array) $doc['knowledge_base'] : [], 'knowledge_base' ),
			'glossaries'      => $this->term_summaries( isset( $doc['glossaries'] ) ? (array) $doc['glossaries'] : [], 'glossaries' ),
			'author'          => $this->author_summary( isset( $doc['author'] ) ? (int) $doc['author'] : 0 ),
			'modified'        => isset( $doc['modified'] ) ? (string) $doc['modified'] : ''
		];
	}

	/**
	 * JSON Schema for {@see self::doc_summary()}.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected static function doc_summary_schema() {
		$term_list = [
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'integer' ],
					'name' => [ 'type' => 'string' ],
					'slug' => [ 'type' => 'string' ]
				]
			]
		];

		return [
			'id'              => [ 'type' => 'integer' ],
			'title'           => [ 'type' => 'string' ],
			'slug'            => [ 'type' => 'string' ],
			'status'          => [ 'type' => 'string' ],
			'url'             => [ 'type' => 'string' ],
			'edit_url'        => [ 'type' => 'string' ],
			'excerpt'         => [ 'type' => 'string' ],
			'categories'      => $term_list,
			'tags'            => $term_list,
			'knowledge_bases' => $term_list,
			// Empty unless the enable_glossaries setting is on.
			'glossaries'      => $term_list,
			'author'          => [
				'type'       => 'object',
				'properties' => [
					'id'   => [ 'type' => 'integer' ],
					'name' => [ 'type' => 'string' ]
				]
			],
			'modified'        => [ 'type' => 'string' ]
		];
	}

	/**
	 * WordPress returns `{raw, rendered}` in edit context and `{rendered}` in
	 * view context; either way an agent wants one string.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $field A REST title/excerpt field.
	 * @return string
	 */
	protected function rendered_or_raw( $field ) {
		if ( is_string( $field ) ) {
			return $field;
		}

		if ( ! is_array( $field ) ) {
			return '';
		}

		if ( isset( $field['raw'] ) && '' !== $field['raw'] ) {
			return (string) $field['raw'];
		}

		return isset( $field['rendered'] ) ? (string) $field['rendered'] : '';
	}

	/**
	 * `{id, name}` for a user id.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User id.
	 * @return array
	 */
	protected function author_summary( $user_id ) {
		$user_id = (int) $user_id;
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;

		return [
			'id'   => $user_id,
			'name' => $user && isset( $user->display_name ) ? (string) $user->display_name : ''
		];
	}

	/**
	 * Load a doc, refusing anything that is not one.
	 *
	 * @since 4.9.0
	 *
	 * @param int $id Post id.
	 * @return \WP_Post|\WP_Error
	 */
	protected function require_doc( $id ) {
		$id   = (int) $id;
		$post = $id > 0 ? get_post( $id ) : null;

		if ( ! $post || self::$doc_post_type !== $post->post_type ) {
			return AbilityError::not_found( 'doc', $id );
		}

		return $post;
	}

	/**
	 * The capability a specific doc needs before this user may edit it.
	 *
	 * WordPress' `edit_post` meta capability resolves to a different primitive
	 * depending on who wrote the post and whether it is published; naming the
	 * generic `edit_docs` in the refusal would send an administrator to grant a
	 * capability the user already holds.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Post $post The doc.
	 * @return string
	 */
	protected function editing_capability( \WP_Post $post ) {
		if ( (int) $post->post_author !== get_current_user_id() ) {
			return 'edit_others_docs';
		}

		if ( 'publish' === $post->post_status ) {
			return 'edit_published_docs';
		}

		if ( 'private' === $post->post_status ) {
			return 'edit_private_docs';
		}

		return 'edit_docs';
	}

	/**
	 * The same question for deletion.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Post $post The doc.
	 * @return string
	 */
	protected function deleting_capability( \WP_Post $post ) {
		if ( (int) $post->post_author !== get_current_user_id() ) {
			return 'delete_others_docs';
		}

		if ( 'publish' === $post->post_status ) {
			return 'delete_published_docs';
		}

		if ( 'private' === $post->post_status ) {
			return 'delete_private_docs';
		}

		return 'delete_docs';
	}

	/**
	 * Translate a `wp/v2/docs` refusal into BetterDocs' typed vocabulary.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error     $error What the controller returned.
	 * @param string        $what  What the caller was trying to do, as a phrase.
	 * @param \WP_Post|null $post  The doc in play, when there is one.
	 * @return \WP_Error
	 */
	protected function map_rest_error( \WP_Error $error, $what, $post = null ) {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();
		$data    = (array) $error->get_error_data();

		switch ( $code ) {
			case 'rest_post_invalid_id':
			case 'rest_post_invalid_page_number':
				return AbilityError::not_found( 'doc', $post instanceof \WP_Post ? (int) $post->ID : 0 );

			case 'rest_cannot_create':
				return AbilityError::capability_missing( 'edit_docs', $what );

			case 'rest_cannot_publish':
				return AbilityError::capability_missing( 'publish_docs', $what );

			case 'rest_cannot_edit':
			case 'rest_cannot_edit_others':
				return AbilityError::capability_missing(
					$post instanceof \WP_Post ? $this->editing_capability( $post ) : 'edit_docs',
					$what
				);

			case 'rest_cannot_delete':
				return AbilityError::capability_missing(
					$post instanceof \WP_Post ? $this->deleting_capability( $post ) : 'delete_docs',
					$what
				);

			case 'rest_cannot_read':
			case 'rest_forbidden':
			case 'rest_forbidden_context':
				return AbilityError::capability_missing( 'read_private_docs', $what );

			case 'rest_already_trashed':
				return AbilityError::conflict( $message, [ 'object' => 'doc' ] );

			case 'rest_invalid_param':
				$field = isset( $data['params'] ) && is_array( $data['params'] ) ? (string) key( $data['params'] ) : '';

				return AbilityError::invalid_input(
					'' !== $field ? $field : 'input',
					'' !== $field && isset( $data['params'][ $field ] ) ? (string) $data['params'][ $field ] : $message
				);

			default:
				return AbilityError::upstream( $message, [ 'code' => (string) $code ] );
		}
	}
}
