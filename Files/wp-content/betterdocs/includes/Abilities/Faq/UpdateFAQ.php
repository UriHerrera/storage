<?php
/**
 * Update an FAQ ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;

/**
 * Edit a question, its answer, its group or its status.
 *
 * Extends {@see CreateFAQ} so both take the same fields, all optional here.
 *
 * **Everything unnamed is preserved.** `FAQBuilder::update_betterdocs_faq()`
 * writes `sanitize_text_field( $post_title )` and `wp_kses_post( $post_content )`
 * from whatever it was handed, so a call carrying only a new answer would
 * blank the question. This reads the FAQ first and sends both fields.
 *
 * @since 4.9.0
 */
class UpdateFAQ extends CreateFAQ {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		parent::__construct();

		$this->id          = 'betterdocs/update-faq';
		$this->label       = __( 'Update FAQ', 'betterdocs' );
		$this->description = __( 'Edit a BetterDocs FAQ: its question, its answer, the group it belongs to, or its status. Fields you leave out keep their current values. Moving an FAQ to another group replaces its group rather than adding one.', 'betterdocs' );
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
			'idempotent'    => true,
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
		$schema = parent::get_input_schema();

		$schema['required'] = [ 'id' ];

		unset( $schema['properties']['status']['default'] );

		$schema['properties'] = array_merge(
			[
				'id' => [
					'type'        => 'integer',
					'description' => __( 'The FAQ id. Required.', 'betterdocs' )
				]
			],
			$schema['properties']
		);

		$schema['properties']['question']['description']   = __( 'A new question. Left alone when omitted.', 'betterdocs' );
		$schema['properties']['answer']['description']     = __( 'A new answer, replacing the old one. Left alone when omitted.', 'betterdocs' );
		$schema['properties']['group_name']['description'] = __( 'Move the FAQ to this group, by name or slug; the group is created when nothing matches. Send group_id or group_name, not both.', 'betterdocs' );
		$schema['properties']['status']['description']     = __( 'A new post status. Left alone when omitted.', 'betterdocs' );

		return $schema;
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$id      = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$current = $this->faq_item( $id );

		if ( is_wp_error( $current ) ) {
			return $current;
		}

		$given = array_intersect_key( $input, array_flip( [ 'question', 'answer', 'group_id', 'group_name', 'status' ] ) );

		if ( empty( $given ) ) {
			return AbilityError::invalid_input(
				'input',
				__( 'Nothing to update: send at least one of question, answer, group_id, group_name or status.', 'betterdocs' )
			);
		}

		if ( isset( $input['question'] ) && '' === trim( (string) $input['question'] ) ) {
			return AbilityError::invalid_input( 'question', __( 'An FAQ needs a question.', 'betterdocs' ) );
		}

		$group = $this->group_ref_input( $input, true );

		if ( is_wp_error( $group ) ) {
			return $group;
		}

		$written = $this->write_faq( $id, $input, $current, $group );

		if ( is_wp_error( $written ) ) {
			return $written;
		}

		if ( isset( $input['status'] ) ) {
			$status = $this->apply_faq_status( $id, (string) $input['status'] );

			if ( is_wp_error( $status ) ) {
				return $status;
			}
		}

		$item = $this->faq_item( $id );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		return $this->faq_shape( $item );
	}

	/**
	 * Send question, answer and group to `faq/update_post`, merged over what
	 * the FAQ holds now.
	 *
	 * Skipped when only `status` was sent, so switching an FAQ to draft never
	 * rewrites its content.
	 *
	 * @since 4.9.0
	 *
	 * @param int      $id      FAQ post id.
	 * @param array    $input   Validated input.
	 * @param array    $current The FAQ as it is now.
	 * @param int|null $group   Resolved group id, or null when none was sent.
	 * @return true|\WP_Error
	 */
	protected function write_faq( $id, array $input, array $current, $group ) {
		$changes = array_intersect_key( $input, array_flip( [ 'question', 'answer', 'group_id', 'group_name' ] ) );

		if ( empty( $changes ) ) {
			return true;
		}

		$question = isset( $input['question'] )
			? (string) $input['question']
			: $this->rendered_or_raw_field( isset( $current['title'] ) ? $current['title'] : '' );

		$answer = isset( $input['answer'] )
			? $this->answer_html( $input['answer'], isset( $input['answer_format'] ) ? (string) $input['answer_format'] : 'markdown' )
			: $this->rendered_or_raw_field( isset( $current['content'] ) ? $current['content'] : '' );

		$result = $this->dispatch(
			'POST',
			'/faq/update_post',
			[
				'post_id'      => (int) $id,
				// Slashed for the same reason as on create: `wp_update_post()`
				// unslashes, so an unslashed answer loses its backslashes.
				'post_title'   => wp_slash( $question ),
				'post_content' => wp_slash( $answer ),
				// 0 means "leave the group alone" — the route only touches
				// terms when it is given an id that exists.
				'term_id'      => null === $group ? 0 : (int) $group
			],
			self::FAQ_NS
		);

		if ( is_wp_error( $result ) ) {
			return $this->map_faq_error( $result, __( 'update an FAQ', 'betterdocs' ) );
		}

		if ( empty( $result ) ) {
			return AbilityError::upstream( __( 'BetterDocs did not confirm the FAQ update.', 'betterdocs' ) );
		}

		return true;
	}
}
