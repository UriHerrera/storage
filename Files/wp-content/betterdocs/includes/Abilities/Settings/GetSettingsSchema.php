<?php
/**
 * Read the settings schema ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;

/**
 * What every BetterDocs setting is: its type, its default, the values it
 * accepts, the tab it lives on, whether it needs Pro, and what changing it
 * costs.
 *
 * The tool to call **before** `bd-update-settings`. BetterDocs has around 250
 * writable settings on a Pro site and no two sites carry the same set — Pro
 * adds whole tabs, add-ons add fields, filters remove them — so the only
 * reliable list is this one, read from the site in front of you.
 *
 * @since 4.9.0
 */
class GetSettingsSchema extends AbilityBase {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/get-settings-schema';
		$this->label       = __( 'Get settings schema', 'betterdocs' );
		$this->description = __( 'List every BetterDocs setting this site accepts, with its type, default, allowed values, tab, whether it needs Pro, and any consequence of changing it. Call this before bd-update-settings: the settings differ from site to site, and a value that is not in a key\'s allowed list is refused.', 'betterdocs' );
		$this->capability  = 'edit_docs_settings';
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
				'tab' => [
					'type'        => 'string',
					'description' => __( 'Only the settings on this tab, by tab id (the ids are in the tabs list this tool returns). Worth using: the whole schema is large.', 'betterdocs' )
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
				'tabs'     => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'       => [ 'type' => 'string' ],
							'label'    => [ 'type' => 'string' ],
							'included' => [ 'type' => 'boolean' ]
						]
					]
				],
				'settings' => [ 'type' => 'object' ],
				'count'    => [ 'type' => 'integer' ]
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
		$tabs   = SettingsSchema::tabs();
		$schema = SettingsSchema::resolve();
		$tab    = isset( $input['tab'] ) ? (string) $input['tab'] : '';

		if ( '' !== $tab ) {
			$known = wp_list_pluck( $tabs, 'id' );

			if ( ! in_array( $tab, $known, true ) ) {
				return AbilityError::invalid_input(
					'tab',
					sprintf(
						/* translators: %s: tab id. */
						__( 'There is no settings tab called "%s" on this site.', 'betterdocs' ),
						$tab
					),
					$known
				);
			}

			$schema = array_filter(
				$schema,
				static function ( array $entry ) use ( $tab ) {
					return $entry['tab'] === $tab;
				}
			);
		}

		return [
			'tabs'     => $tabs,
			'settings' => $schema,
			'count'    => count( $schema )
		];
	}
}
