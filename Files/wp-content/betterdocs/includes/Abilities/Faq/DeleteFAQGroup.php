<?php
/**
 * Delete an FAQ group ability.
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
 * Delete an FAQ group, and optionally everything in it.
 *
 * With `with_all_faqs` false — the default — the group goes and its questions
 * survive as uncategorised FAQs, findable again with
 * `bd-list-faqs {group_id: 0}`… which does not exist, so say it plainly: they
 * stay in the site and `bd-list-faqs` with no group still finds them.
 *
 * With `with_all_faqs` true every FAQ in the group is **permanently deleted**,
 * drafts and trashed ones included — `Helper::delete_specific_faq_posts_by_faq_category()`
 * force-deletes across every status. There is no undo, which is why the flag
 * defaults to false and the tool is annotated destructive.
 *
 * @since 4.9.0
 */
class DeleteFAQGroup extends AbilityBase {

	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/delete-faq-group';
		$this->label       = __( 'Delete FAQ group', 'betterdocs' );
		$this->description = __( 'Delete a BetterDocs FAQ group. By default its questions survive without a group; with with_all_faqs true every FAQ in the group is permanently deleted, including drafts. Docs that referenced the group stop showing it.', 'betterdocs' );
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
				'id'            => [
					'type'        => 'integer',
					'description' => __( 'The FAQ group id. Required.', 'betterdocs' )
				],
				'with_all_faqs' => [
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'true also deletes every FAQ in the group, permanently and in every status. false (the default) leaves the questions in place without a group.', 'betterdocs' )
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
				'id'           => [ 'type' => 'integer' ],
				'deleted'      => [ 'type' => 'boolean' ],
				'faqs_deleted' => [ 'type' => 'boolean' ],
				'name'         => [ 'type' => 'string' ],
				'faqs'         => [ 'type' => 'integer' ]
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
		$term = $id > 0 ? get_term( $id, self::GROUP_TAXONOMY ) : null;

		if ( ! $term || is_wp_error( $term ) ) {
			return AbilityError::not_found( 'FAQ group', $id );
		}

		$with_all = ! empty( $input['with_all_faqs'] );

		// Reported from before the delete: afterwards the term is gone and the
		// count with it, and an agent that just removed something should be able
		// to say what it removed.
		$name = (string) $term->name;
		$faqs = $this->count_faqs_in_group( $id );

		$result = $this->dispatch(
			'POST',
			'/faq/delete_category',
			[
				'term_id'       => $id,
				'with_all_post' => $with_all
			],
			self::FAQ_NS
		);

		if ( is_wp_error( $result ) ) {
			return $this->map_faq_error( $result, __( 'delete an FAQ group', 'betterdocs' ) );
		}

		if ( true !== $result ) {
			return AbilityError::upstream( __( 'BetterDocs did not confirm the FAQ group deletion.', 'betterdocs' ) );
		}

		return [
			'id'           => $id,
			'deleted'      => true,
			'faqs_deleted' => $with_all,
			'name'         => $name,
			'faqs'         => $faqs
		];
	}

	/**
	 * How many FAQs the group holds, in any status.
	 *
	 * The term's own `count` tracks published posts only, and "5 questions were
	 * deleted" has to include the drafts that went with them.
	 *
	 * @since 4.9.0
	 *
	 * @param int $id Group id.
	 * @return int
	 */
	protected function count_faqs_in_group( $id ) {
		$query = new \WP_Query(
			[
				'post_type'              => self::FAQ_POST_TYPE,
				'post_status'            => [ 'publish', 'draft', 'pending', 'future', 'private', 'trash' ],
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- counting one group's FAQs is the question being asked.
				'tax_query'              => [
					[
						'taxonomy' => self::GROUP_TAXONOMY,
						'field'    => 'term_id',
						'terms'    => (int) $id
					]
				]
			]
		);

		return (int) $query->found_posts;
	}
}
