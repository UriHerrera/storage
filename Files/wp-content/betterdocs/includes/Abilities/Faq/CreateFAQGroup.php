<?php
/**
 * Create an FAQ group ability.
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
 * Create an FAQ group — or hand back the one that already exists.
 *
 * **Find-or-create**, exactly as `bd-create-term` does it: a name that is taken
 * comes back with `created: false` and the existing group's id, so "make sure
 * these groups exist" is expressible as one repeatable call. Without it the
 * second run of the same batch would fail on `term_exists` and an agent would
 * have to guess whether that meant "already there" or "went wrong".
 *
 * The group's `status` is the FAQ Builder's enabled/disabled switch: a `draft`
 * group and its questions do not render on the front end.
 *
 * @since 4.9.0
 */
class CreateFAQGroup extends AbilityBase {

	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/create-faq-group';
		$this->label       = __( 'Create FAQ group', 'betterdocs' );
		$this->description = __( 'Create a BetterDocs FAQ group (the FAQ Builder calls it a category). A name that already exists is returned as it is, with created:false, so the call is safe to repeat. FAQ groups are what bd-attach-faq puts on a doc.', 'betterdocs' );
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
			'required'             => [ 'title' ],
			'properties'           => [
				'title'       => [
					'type'        => 'string',
					'description' => __( 'The group name, as a human would write it. Required. Capped at 200 characters, the WordPress term-name limit.', 'betterdocs' )
				],
				'description' => [
					'type'        => 'string',
					'description' => __( 'Optional description, shown by some FAQ layouts.', 'betterdocs' )
				],
				'slug'        => [
					'type'        => 'string',
					'description' => __( 'URL slug. Derived from the name when omitted.', 'betterdocs' )
				],
				'status'      => array_merge(
					self::group_status_schema(),
					[ 'default' => 'publish' ]
				)
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
				self::group_shape_schema(),
				[ 'created' => [ 'type' => 'boolean' ] ]
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
		$title = isset( $input['title'] ) ? trim( (string) $input['title'] ) : '';

		if ( '' === $title ) {
			return AbilityError::invalid_input( 'title', __( 'An FAQ group needs a name.', 'betterdocs' ) );
		}

		$existing = $this->find_group( $title, isset( $input['slug'] ) ? (string) $input['slug'] : '' );
		$created  = false;

		if ( 0 === $existing ) {
			$params = [ 'title' => $title ];

			foreach ( [ 'description', 'slug' ] as $field ) {
				if ( isset( $input[ $field ] ) ) {
					$params[ $field ] = (string) $input[ $field ];
				}
			}

			$existing = $this->create_group( $params );

			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			$created = true;
		}

		$status = $this->apply_group_status( (int) $existing, isset( $input['status'] ) ? (string) $input['status'] : 'publish', $created );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$item = $this->group_item( (int) $existing );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		return array_merge( $this->group_shape( $item ), [ 'created' => $created ] );
	}

	/**
	 * The id of the group this title (or slug) already names, or 0.
	 *
	 * Checked before the write rather than after `term_exists` comes back,
	 * because `wp_insert_term()` reports the duplicate id in three different
	 * shapes depending on how it was reached; asking first is one lookup and one
	 * answer.
	 *
	 * @since 4.9.0
	 *
	 * @param string $title Group name.
	 * @param string $slug  Requested slug, if any.
	 * @return int
	 */
	protected function find_group( $title, $slug = '' ) {
		$candidates = [ [ 'name', $title ], [ 'slug', sanitize_title( '' !== $slug ? $slug : $title ) ] ];

		foreach ( $candidates as $candidate ) {
			list( $field, $value ) = $candidate;

			if ( '' === $value ) {
				continue;
			}

			$term = get_term_by( $field, $value, self::GROUP_TAXONOMY );

			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $term->term_id;
			}
		}

		return 0;
	}

	/**
	 * Set the group's `status` term meta when it is not already what was asked
	 * for.
	 *
	 * A freshly created group is `1` (the `created_betterdocs_faq_category`
	 * hook stamps it), so `publish` on create writes nothing.
	 *
	 * @since 4.9.0
	 *
	 * @param int    $term_id  Group id.
	 * @param string $status   `publish` or `draft`.
	 * @param bool   $created  Whether the group was just created.
	 * @return true|\WP_Error
	 */
	protected function apply_group_status( $term_id, $status, $created ) {
		$wanted = 'draft' === $status ? '0' : '1';

		if ( ! $created ) {
			$current = (string) get_term_meta( $term_id, 'status', true );

			if ( $current === $wanted ) {
				return true;
			}
		} elseif ( '1' === $wanted ) {
			return true;
		}

		$result = $this->dispatch(
			'POST',
			'/faq/category_status',
			[
				'term_id' => $term_id,
				'status'  => $wanted
			],
			self::FAQ_NS
		);

		if ( is_wp_error( $result ) ) {
			return $this->map_faq_error( $result, __( 'change an FAQ group\'s status', 'betterdocs' ) );
		}

		return true;
	}
}
