<?php
/**
 * Create an FAQ ability.
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
 * Create one question and answer, optionally filed into a group.
 *
 * The answer is **HTML, not blocks** — an FAQ is edited in the FAQ Builder's
 * rich-text box and printed with `the_content()`, never opened in the block
 * editor. Markdown is the default input format because that is what a model
 * writes; it is converted with Parsedown in safe mode.
 *
 * `group_name` is find-or-create: a name that matches an existing group (by
 * slug, then by name) files the FAQ there, and a name that matches nothing
 * creates the group. `group_id` never creates anything — an id that does not
 * exist is a mistake, not an instruction.
 *
 * @since 4.9.0
 */
class CreateFAQ extends AbilityBase {

	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/create-faq';
		$this->label       = __( 'Create FAQ', 'betterdocs' );
		$this->description = __( 'Create a BetterDocs FAQ — one question and its answer — optionally in a group. The answer is markdown by default and is stored as HTML. group_name creates the group when it does not exist yet; group_id must already exist.', 'betterdocs' );
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
			'destructive'   => false,
			'idempotent'    => false,
			'priority'      => 2.0,
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
			'required'             => [ 'question', 'answer' ],
			'properties'           => [
				'question'      => [
					'type'        => 'string',
					'description' => __( 'The question, as a visitor would ask it. Required. Stored as the FAQ\'s title, so HTML is stripped.', 'betterdocs' )
				],
				'answer'        => [
					'type'        => 'string',
					'description' => __( 'The answer. Required. Markdown by default; see answer_format.', 'betterdocs' )
				],
				'answer_format' => [
					'type'        => 'string',
					'enum'        => [ 'markdown', 'html' ],
					'default'     => 'markdown',
					'description' => __( 'How to read the answer. markdown is converted to HTML with Parsedown in safe mode; html is passed through wp_kses_post. FAQ answers are HTML, never blocks.', 'betterdocs' )
				],
				'group_id'      => [
					'type'        => 'integer',
					'description' => __( 'File the FAQ into this FAQ group, by id. The group must exist.', 'betterdocs' )
				],
				'group_name'    => [
					'type'        => 'string',
					'description' => __( 'File the FAQ into this FAQ group, by name or slug. The group is created when nothing matches. Send group_id or group_name, not both.', 'betterdocs' )
				],
				'status'        => [
					'type'        => 'string',
					'enum'        => self::FAQ_STATUSES,
					'default'     => 'publish',
					'description' => __( 'Post status. Defaults to publish, which is what the FAQ Builder does; a draft FAQ does not render.', 'betterdocs' )
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
			'properties' => self::faq_shape_schema()
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$question = isset( $input['question'] ) ? trim( (string) $input['question'] ) : '';

		if ( '' === $question ) {
			return AbilityError::invalid_input( 'question', __( 'An FAQ needs a question.', 'betterdocs' ) );
		}

		// Resolved before the write, as `bd-create-term` does it (ADR-048): a
		// group reference that cannot be honoured must not leave a stray FAQ
		// behind.
		$group = $this->group_ref_input( $input, true );

		if ( is_wp_error( $group ) ) {
			return $group;
		}

		$answer = $this->answer_html(
			isset( $input['answer'] ) ? $input['answer'] : '',
			isset( $input['answer_format'] ) ? (string) $input['answer_format'] : 'markdown'
		);

		$created = $this->dispatch(
			'POST',
			'/faq/create_post',
			[
				// Slashed on purpose: `wp_insert_post()` unslashes whatever it
				// is given, so an unslashed answer loses every backslash in it
				// (the same trap corrupts block attributes).
				'post_title'   => wp_slash( $question ),
				'post_content' => wp_slash( $answer ),
				'term_id'      => null === $group ? 0 : (int) $group
			],
			self::FAQ_NS
		);

		if ( is_wp_error( $created ) ) {
			return $this->map_faq_error( $created, __( 'create an FAQ', 'betterdocs' ) );
		}

		$id = (int) $created;

		if ( $id <= 0 ) {
			return AbilityError::upstream( __( 'The FAQ was not created and BetterDocs reported no reason.', 'betterdocs' ) );
		}

		$status = $this->apply_faq_status( $id, isset( $input['status'] ) ? (string) $input['status'] : 'publish' );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$item = $this->faq_item( $id );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		return $this->faq_shape( $item );
	}

	/**
	 * Put the FAQ into the status that was asked for.
	 *
	 * `FAQBuilder::insert_betterdocs_faq()` hard-codes `post_status =>
	 * 'publish'` and `update_betterdocs_faq()`'s status branch builds
	 * `[ 'ID', 'post_type', 'status' ]` for `wp_update_post()`, which has no
	 * `status` field and therefore changes nothing — measured. So a status other
	 * than publish is applied afterwards through `wp/v2/betterdocs_faq`, the
	 * only path that works. The FAQ is briefly published in that window; the
	 * post type is not publicly queryable and nothing links to it, but it is
	 * worth knowing.
	 *
	 * @since 4.9.0
	 *
	 * @param int    $id     FAQ post id.
	 * @param string $status Wanted post status.
	 * @return true|\WP_Error
	 */
	protected function apply_faq_status( $id, $status ) {
		if ( '' === $status || 'publish' === $status ) {
			return true;
		}

		$post = get_post( (int) $id );

		if ( $post && $status === $post->post_status ) {
			return true;
		}

		$updated = $this->dispatch(
			'POST',
			'/' . self::FAQ_POST_TYPE . '/' . (int) $id,
			[ 'status' => $status ],
			'wp/v2'
		);

		if ( is_wp_error( $updated ) ) {
			return $this->map_faq_error( $updated, __( 'change an FAQ\'s status', 'betterdocs' ) );
		}

		return true;
	}
}
