<?php
/**
 * Update an FAQ group ability.
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
 * Rename an FAQ group, change its description or slug, or switch it on and off.
 *
 * Extends {@see CreateFAQGroup} so the two accept the same fields with the
 * same meanings, all optional here.
 *
 * **Everything unnamed is preserved, deliberately.**
 * `FAQBuilder::update_faq_category()` hands its parameters to
 * `wp_update_term()` unfiltered, so a call carrying only a new title would
 * blank the description and the slug — and its `update_term_meta( …,
 * 'faq_group_icon', … )` would wipe the group's icon as well. This reads the
 * group first and sends the whole record, so an omitted field means "leave it".
 *
 * @since 4.9.0
 */
class UpdateFAQGroup extends CreateFAQGroup {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		parent::__construct();

		$this->id          = 'betterdocs/update-faq-group';
		$this->label       = __( 'Update FAQ group', 'betterdocs' );
		$this->description = __( 'Rename a BetterDocs FAQ group, change its description or slug, or switch it between publish and draft (the FAQ Builder\'s enabled/disabled switch). Fields you leave out keep their current values.', 'betterdocs' );
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
					'description' => __( 'The FAQ group id. Required.', 'betterdocs' )
				]
			],
			$schema['properties']
		);

		$schema['properties']['title']['description'] = __( 'A new name for the group. Left alone when omitted; changing it does not change the slug.', 'betterdocs' );
		$schema['properties']['slug']['description']  = __( 'A new URL slug. Left alone when omitted.', 'betterdocs' );

		return $schema;
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => self::group_shape_schema()
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$id      = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$current = $this->group_item( $id );

		if ( is_wp_error( $current ) ) {
			return $current;
		}

		$fields = [ 'title', 'description', 'slug', 'status' ];
		$given  = array_intersect_key( $input, array_flip( $fields ) );

		if ( empty( $given ) ) {
			return AbilityError::invalid_input(
				'input',
				__( 'Nothing to update: send at least one of title, description, slug or status.', 'betterdocs' )
			);
		}

		if ( isset( $input['title'] ) && '' === trim( (string) $input['title'] ) ) {
			return AbilityError::invalid_input( 'title', __( 'An FAQ group needs a name.', 'betterdocs' ) );
		}

		$written = $this->write_group( $id, $input, $current );

		if ( is_wp_error( $written ) ) {
			return $written;
		}

		if ( isset( $input['status'] ) ) {
			$status = $this->apply_group_status( $id, (string) $input['status'], false );

			if ( is_wp_error( $status ) ) {
				return $status;
			}
		}

		$item = $this->group_item( $id );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		return $this->group_shape( $item );
	}

	/**
	 * Send the whole record to `faq/update_category`, merged over what the group
	 * holds now.
	 *
	 * Skipped entirely when only `status` was sent, so a switch on or off never
	 * rewrites the term row.
	 *
	 * @since 4.9.0
	 *
	 * @param int   $id      Group id.
	 * @param array $input   Validated input.
	 * @param array $current The group as it is now.
	 * @return true|\WP_Error
	 */
	protected function write_group( $id, array $input, array $current ) {
		$changes = array_intersect_key( $input, array_flip( [ 'title', 'description', 'slug' ] ) );

		if ( empty( $changes ) ) {
			return true;
		}

		$params = [
			'term_id'        => $id,
			'title'          => isset( $input['title'] ) ? (string) $input['title'] : (string) $current['name'],
			'description'    => isset( $input['description'] ) ? (string) $input['description'] : (string) $current['description'],
			'slug'           => isset( $input['slug'] ) ? (string) $input['slug'] : (string) $current['slug'],
			// Preserved by hand: the route writes this meta on every call, so
			// omitting it would delete the group's icon.
			'group_icon_url' => (string) $this->term_meta_first( $current, 'faq_group_icon', '' )
		];

		$result = $this->dispatch( 'POST', '/faq/update_category', $params, self::FAQ_NS );

		if ( is_wp_error( $result ) ) {
			return $this->map_faq_error( $result, __( 'update an FAQ group', 'betterdocs' ) );
		}

		if ( true !== $result ) {
			return AbilityError::upstream( __( 'BetterDocs did not confirm the FAQ group update.', 'betterdocs' ) );
		}

		return true;
	}
}
