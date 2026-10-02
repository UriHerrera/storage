<?php
/**
 * The FAQ group and FAQ shapes the eight FAQ abilities answer with, and the
 * translation from the FAQ Builder's own return values to typed errors.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Utils\BlockBuilder;

/**
 * Eight tools, two objects — and one place that knows how odd the routes
 * underneath them are.
 *
 * The FAQ Builder's REST routes (`betterdocs/faq/*`, note: **no `/v1`**) predate
 * every convention in this plugin. `create_category` answers
 * `{success, term_id}`, `create_post` answers a **bare integer**,
 * `update_category` and `delete_category` answer a bare `true`, and none of them
 * declares an `args` schema. This trait is where those become objects, so an
 * agent sees the same `{id, name, slug, …}` from every FAQ tool.
 *
 * Three behaviours of those routes drive the design and are worth stating,
 * because each one silently destroys data if a tool sends only what changed:
 *
 * 1. `update_category` hands its parameters straight to `wp_update_term()`, so
 *    an absent `description` or `slug` **blanks** the stored one, and an absent
 *    `group_icon_url` overwrites the group's icon with an empty string.
 * 2. `update_post` does `sanitize_text_field( $post_title )` and
 *    `wp_kses_post( $post_content )` on whatever it was given, so an absent
 *    title or answer is written as `''`.
 * 3. Its `status` branch builds `[ 'ID', 'post_type', 'status' ]` for
 *    `wp_update_post()`, which has no `status` field — the FAQ's post status
 *    never changes, and the same call skips the title and content branch
 *    entirely.
 *
 * So every update tool reads the object first, merges, and sends the whole
 * record; and a status change goes through `wp/v2/betterdocs_faq` (post status)
 * or `betterdocs/faq/category_status` (the group's `status` term meta), which
 * are the paths that actually work.
 *
 * @since 4.9.0
 */
trait ShapesFAQs {

	// `self::GROUP_TAXONOMY`, `self::FAQ_POST_TYPE`, `self::FAQ_NS` and
	// `self::FAQ_STATUSES` are declared on `Abilities\AbilityBase`, which every
	// user of this trait extends: PHP 7.4 traits cannot carry constants.

	/**
	 * `status` as the FAQ group tools declare it.
	 *
	 * The group's status is the `status` term meta the FAQ Builder toggles —
	 * `1` shows the group on the front end, `0` hides it
	 * (`Query::faq_terms_query_args()` filters on exactly that). It is spelled
	 * `publish`/`draft` here so it reads like every other status in this tool
	 * set.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected static function group_status_schema() {
		return [
			'type'        => 'string',
			'enum'        => [ 'publish', 'draft' ],
			'description' => __( 'publish shows the group on the front end, draft hides it. This is the FAQ Builder\'s enabled/disabled switch, stored as the group\'s status term meta.', 'betterdocs' )
		];
	}

	/**
	 * The FAQ group summary all four group tools answer with.
	 *
	 * @since 4.9.0
	 *
	 * @param array $term A `wp/v2/betterdocs_faq_category` item.
	 * @return array
	 */
	protected function group_shape( array $term ) {
		return [
			'id'          => isset( $term['id'] ) ? (int) $term['id'] : 0,
			'name'        => isset( $term['name'] ) ? (string) $term['name'] : '',
			'slug'        => isset( $term['slug'] ) ? (string) $term['slug'] : '',
			'description' => isset( $term['description'] ) ? (string) $term['description'] : '',
			'status'      => $this->group_status_of( $term ),
			'order'       => (int) $this->term_meta_first( $term, 'order', 0 ),
			// WordPress' term count tracks **published** posts only, which is
			// why the draft count is a separate number rather than folded in.
			'faq_count'   => isset( $term['count'] ) ? (int) $term['count'] : 0,
			'draft_count' => isset( $term['betterdocs_draft_count'] ) ? (int) $term['betterdocs_draft_count'] : 0
		];
	}

	/**
	 * JSON Schema for {@see self::group_shape()}.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected static function group_shape_schema() {
		return [
			'id'          => [ 'type' => 'integer' ],
			'name'        => [ 'type' => 'string' ],
			'slug'        => [ 'type' => 'string' ],
			'description' => [ 'type' => 'string' ],
			'status'      => [ 'type' => 'string' ],
			'order'       => [ 'type' => 'integer' ],
			'faq_count'   => [ 'type' => 'integer' ],
			'draft_count' => [ 'type' => 'integer' ]
		];
	}

	/**
	 * The FAQ summary all four FAQ tools answer with.
	 *
	 * @since 4.9.0
	 *
	 * @param array $post A `wp/v2/betterdocs_faq` item, ideally in edit context.
	 * @return array
	 */
	protected function faq_shape( array $post ) {
		$groups   = isset( $post[ self::GROUP_TAXONOMY ] ) ? (array) $post[ self::GROUP_TAXONOMY ] : [];
		$group_id = isset( $groups[0] ) ? (int) $groups[0] : 0;

		return [
			'id'       => isset( $post['id'] ) ? (int) $post['id'] : 0,
			'question' => $this->rendered_or_raw_field( isset( $post['title'] ) ? $post['title'] : '' ),
			'answer'   => $this->rendered_or_raw_field( isset( $post['content'] ) ? $post['content'] : '' ),
			'group'    => $group_id > 0 ? $this->group_summary( $group_id ) : null,
			'status'   => isset( $post['status'] ) ? (string) $post['status'] : '',
			'order'    => $this->faq_order_position( isset( $post['id'] ) ? (int) $post['id'] : 0, $group_id )
		];
	}

	/**
	 * JSON Schema for {@see self::faq_shape()}.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected static function faq_shape_schema() {
		return [
			'id'       => [ 'type' => 'integer' ],
			'question' => [ 'type' => 'string' ],
			'answer'   => [ 'type' => 'string' ],
			'group'    => [
				'type'       => [ 'object', 'null' ],
				'properties' => [
					'id'   => [ 'type' => 'integer' ],
					'name' => [ 'type' => 'string' ],
					'slug' => [ 'type' => 'string' ]
				]
			],
			'status'   => [ 'type' => 'string' ],
			'order'    => [ 'type' => [ 'integer', 'null' ] ]
		];
	}

	/**
	 * `{id, name, slug}` for an FAQ group id, or null when it has gone.
	 *
	 * @since 4.9.0
	 *
	 * @param int $id Term id.
	 * @return array|null
	 */
	protected function group_summary( $id ) {
		$term = get_term( (int) $id, self::GROUP_TAXONOMY );

		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		return [
			'id'   => (int) $term->term_id,
			'name' => (string) $term->name,
			'slug' => (string) $term->slug
		];
	}

	/**
	 * An FAQ's position in its group's manual order, 1-based.
	 *
	 * The order lives in the group's `_betterdocs_faq_order` term meta as a
	 * comma-separated id list, newest first — the FAQ Builder's drag-and-drop
	 * writes it and `Query` reads it. Null when the FAQ has no group, or when
	 * the group's list does not mention it (an FAQ assigned outside the
	 * Builder).
	 *
	 * @since 4.9.0
	 *
	 * @param int $post_id  FAQ post id.
	 * @param int $group_id FAQ group term id.
	 * @return int|null
	 */
	protected function faq_order_position( $post_id, $group_id ) {
		$post_id  = (int) $post_id;
		$group_id = (int) $group_id;

		if ( $post_id <= 0 || $group_id <= 0 ) {
			return null;
		}

		$stored = (string) get_term_meta( $group_id, '_betterdocs_faq_order', true );
		$ids    = array_values( array_filter( array_map( 'trim', explode( ',', $stored ) ), 'strlen' ) );
		$index  = array_search( (string) $post_id, $ids, true );

		return false === $index ? null : (int) $index + 1;
	}

	/**
	 * A term meta value out of a REST term item.
	 *
	 * `register_term_meta()` is called without `single`, so every one of these
	 * keys arrives as an array of values rather than a scalar.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $term     A `wp/v2/betterdocs_faq_category` item.
	 * @param string $key      Meta key.
	 * @param mixed  $fallback Value when the key is absent or empty.
	 * @return mixed
	 */
	protected function term_meta_first( array $term, $key, $fallback = '' ) {
		$value = isset( $term['meta'][ $key ] ) ? $term['meta'][ $key ] : null;

		if ( is_array( $value ) ) {
			$value = count( $value ) > 0 ? reset( $value ) : null;
		}

		return ( null === $value || '' === $value ) ? $fallback : $value;
	}

	/**
	 * `publish` or `draft` for a group item.
	 *
	 * A group with no `status` meta at all — an import that predates the FAQ
	 * Builder — is `draft`, because that is how the front end treats it.
	 *
	 * @since 4.9.0
	 *
	 * @param array $term A `wp/v2/betterdocs_faq_category` item.
	 * @return string
	 */
	protected function group_status_of( array $term ) {
		return '1' === (string) $this->term_meta_first( $term, 'status', '0' ) ? 'publish' : 'draft';
	}

	/**
	 * WordPress returns `{raw, rendered}` in edit context and `{rendered}` in
	 * view context; either way an agent wants one string.
	 *
	 * Named apart from `ShapesDocs::rendered_or_raw()` so the two traits can be
	 * used in the same class without colliding.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $field A REST title/content field.
	 * @return string
	 */
	protected function rendered_or_raw_field( $field ) {
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
	 * Load one FAQ group as a REST item.
	 *
	 * @since 4.9.0
	 *
	 * @param int $id Term id.
	 * @return array|\WP_Error
	 */
	protected function group_item( $id ) {
		$id = (int) $id;

		$term = $id > 0 ? get_term( $id, self::GROUP_TAXONOMY ) : null;

		if ( ! $term || is_wp_error( $term ) ) {
			return AbilityError::not_found( 'FAQ group', $id );
		}

		$item = $this->dispatch( 'GET', '/' . self::GROUP_TAXONOMY . '/' . $id, [ 'context' => 'view' ], 'wp/v2' );

		if ( is_wp_error( $item ) ) {
			return $this->map_faq_error( $item, __( 'read an FAQ group', 'betterdocs' ) );
		}

		return (array) $item;
	}

	/**
	 * Load one FAQ as a REST item, refusing anything that is not an FAQ.
	 *
	 * @since 4.9.0
	 *
	 * @param int $id Post id.
	 * @return array|\WP_Error
	 */
	protected function faq_item( $id ) {
		$id   = (int) $id;
		$post = $id > 0 ? get_post( $id ) : null;

		if ( ! $post || self::FAQ_POST_TYPE !== $post->post_type ) {
			return AbilityError::not_found( 'FAQ', $id );
		}

		$item = $this->dispatch( 'GET', '/' . self::FAQ_POST_TYPE . '/' . $id, [ 'context' => 'edit' ], 'wp/v2' );

		if ( is_wp_error( $item ) ) {
			return $this->map_faq_error( $item, __( 'read an FAQ', 'betterdocs' ) );
		}

		return (array) $item;
	}

	/**
	 * Resolve a group reference — an id, a slug or a name — to a term id.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $ref    Group id, slug or name.
	 * @param bool  $create Whether an unmatched **name** may be created.
	 * @return int|\WP_Error
	 */
	protected function resolve_group( $ref, $create ) {
		if ( is_int( $ref ) || ( is_string( $ref ) && ctype_digit( $ref ) ) ) {
			$term = get_term( (int) $ref, self::GROUP_TAXONOMY );

			if ( ! $term || is_wp_error( $term ) ) {
				return AbilityError::not_found( 'FAQ group', (int) $ref );
			}

			return (int) $term->term_id;
		}

		if ( ! is_string( $ref ) || '' === trim( $ref ) ) {
			return AbilityError::invalid_input(
				'group',
				__( 'An FAQ group reference must be a group id, a slug or a name.', 'betterdocs' )
			);
		}

		$ref = trim( $ref );

		foreach ( [ 'slug', 'name' ] as $field ) {
			$term = get_term_by( $field, 'slug' === $field ? sanitize_title( $ref ) : $ref, self::GROUP_TAXONOMY );

			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $term->term_id;
			}
		}

		if ( ! $create ) {
			return AbilityError::not_found( 'FAQ group', $ref );
		}

		return $this->create_group( [ 'title' => $ref ] );
	}

	/**
	 * Create an FAQ group through the FAQ Builder's own route.
	 *
	 * Not `wp_insert_term()`: that runs no capability check at all, and not
	 * `POST wp/v2/betterdocs_faq_category` either, which is gated on
	 * `edit_doc_terms` and would refuse a user these tools are meant to serve
	 * (the FAQ Builder's own gate is `edit_others_posts || edit_others_docs` —
	 * ADR-005). The route also carries the side effects the Builder depends on:
	 * `created_betterdocs_faq_category` stamps the new group's `order` and
	 * `status` meta.
	 *
	 * @since 4.9.0
	 *
	 * @param array $params `title`, and optionally `description` / `slug`.
	 * @return int|\WP_Error New term id.
	 */
	protected function create_group( array $params ) {
		$result = $this->dispatch( 'POST', '/faq/create_category', $params, self::FAQ_NS );

		if ( is_wp_error( $result ) ) {
			return $this->map_faq_error(
				$result,
				sprintf(
					/* translators: %s: FAQ group name. */
					__( 'create the FAQ group "%s"', 'betterdocs' ),
					isset( $params['title'] ) ? $params['title'] : ''
				)
			);
		}

		$result = (array) $result;

		if ( empty( $result['success'] ) || empty( $result['term_id'] ) ) {
			return AbilityError::upstream( __( 'The FAQ group was not created and BetterDocs reported no reason.', 'betterdocs' ) );
		}

		return (int) $result['term_id'];
	}

	/**
	 * Resolve the `group_id` / `group_name` pair every FAQ tool accepts.
	 *
	 * @since 4.9.0
	 *
	 * @param array $input  Validated input.
	 * @param bool  $create Whether an unmatched `group_name` may be created.
	 * @return int|null|\WP_Error Term id, or null when neither field was sent.
	 */
	protected function group_ref_input( array $input, $create ) {
		$has_id   = isset( $input['group_id'] ) && '' !== $input['group_id'];
		$has_name = isset( $input['group_name'] ) && '' !== trim( (string) $input['group_name'] );

		if ( $has_id && $has_name ) {
			return AbilityError::invalid_input(
				'group_name',
				__( 'Send group_id or group_name, not both.', 'betterdocs' )
			);
		}

		if ( $has_id ) {
			return $this->resolve_group( (int) $input['group_id'], false );
		}

		if ( $has_name ) {
			return $this->resolve_group( (string) $input['group_name'], $create );
		}

		return null;
	}

	/**
	 * Turn an answer an agent wrote into the HTML an FAQ stores.
	 *
	 * FAQ answers are plain HTML, not blocks: the FAQ Builder edits them in
	 * TinyMCE and the front end prints `the_content()` of a post that never sees
	 * the block editor. Markdown is converted with Parsedown in safe mode and
	 * passed through `wp_kses_post()`; HTML is passed through `wp_kses_post()`
	 * too, so both formats store the same class of markup — which is also what
	 * `FAQBuilder::update_betterdocs_faq()` enforces on its own path.
	 *
	 * @since 4.9.0
	 *
	 * @param string $answer Raw answer.
	 * @param string $format `markdown` or `html`.
	 * @return string
	 */
	protected function answer_html( $answer, $format ) {
		$answer = (string) $answer;

		if ( 'html' === $format ) {
			return wp_kses_post( $answer );
		}

		return BlockBuilder::markdown_to_html( $answer );
	}

	/**
	 * Translate a refusal from an FAQ route into the typed vocabulary.
	 *
	 * The FAQ Builder routes answer with whatever `wp_insert_term()`,
	 * `wp_update_post()` or the permission callback produced, so this covers
	 * both families: the legacy codes and the `wp/v2` ones the status and read
	 * paths can return.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error What the route returned.
	 * @param string    $what  What the caller was trying to do, as a phrase.
	 * @return \WP_Error
	 */
	protected function map_faq_error( \WP_Error $error, $what ) {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();
		$data    = (array) $error->get_error_data();

		switch ( $code ) {
			case 'rest_forbidden':
			case 'rest_forbidden_context':
			case 'rest_cannot_create':
			case 'rest_cannot_edit':
			case 'rest_cannot_edit_others':
			case 'rest_cannot_delete':
			case 'rest_cannot_read':
			case 'rest_cannot_update':
				// Every FAQ route is gated on the one additive capability
				// BetterDocs adds there, so there is only one capability to name.
				return AbilityError::capability_missing( 'edit_others_docs', $what );

			case 'rest_cannot_publish':
				return AbilityError::capability_missing( 'publish_docs', $what );

			case 'betterdocs_invalid_faq':
			case 'rest_post_invalid_id':
				return AbilityError::not_found( 'FAQ', isset( $data['id'] ) ? $data['id'] : 0 );

			case 'invalid_term_id':
			case 'rest_term_invalid':
			case 'rest_term_invalid_id':
				return AbilityError::not_found( 'FAQ group', isset( $data['id'] ) ? $data['id'] : 0 );

			case 'term_exists':
				$existing = is_array( $data ) && isset( $data['term_id'] ) ? (int) $data['term_id'] : (int) ( is_scalar( $data ) ? $data : 0 );

				return AbilityError::conflict(
					__( 'An FAQ group with that name already exists.', 'betterdocs' ),
					[
						'object' => 'FAQ group',
						'id'     => $existing
					]
				);

			case 'empty_term_name':
				return AbilityError::invalid_input( 'title', __( 'An FAQ group needs a name.', 'betterdocs' ) );

			case 'rest_already_trashed':
				return AbilityError::conflict( $message, [ 'object' => 'FAQ' ] );

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
