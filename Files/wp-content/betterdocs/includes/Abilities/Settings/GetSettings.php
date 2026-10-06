<?php
/**
 * Read settings values ability.
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
 * What the settings are set to right now.
 *
 * Values come from `Core\Settings::get_all( false )` — stored values merged
 * over the defaults, which is what the plugin itself reads — and are mapped to
 * the JSON types the schema advertises, so a toggle reads `true`, not `"1"`.
 *
 * API keys are **never** returned. They are listed under `masked` with a
 * constant `********` in their place; an agent can write a new key and cannot
 * read the one that is there.
 *
 * @since 4.9.0
 */
class GetSettings extends AbilityBase {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/get-settings';
		$this->label       = __( 'Get settings', 'betterdocs' );
		$this->description = __( 'Read BetterDocs settings as they are now, merged with the defaults. Ask for specific keys or a whole tab; with neither, every setting comes back. API keys are masked and can never be read.', 'betterdocs' );
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
				'keys' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => __( 'Only these setting keys. Unknown keys are reported in unknown rather than failing the call.', 'betterdocs' )
				],
				'tab'  => [
					'type'        => 'string',
					'description' => __( 'Only the settings on this tab, by tab id.', 'betterdocs' )
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
				'settings' => [ 'type' => 'object' ],
				'masked'   => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ]
				],
				'unknown'  => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ]
				],
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
		$schema = SettingsSchema::resolve();
		$tab    = isset( $input['tab'] ) ? (string) $input['tab'] : '';

		if ( '' !== $tab ) {
			$known = wp_list_pluck( SettingsSchema::tabs(), 'id' );

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
		}

		$unknown = [];
		$wanted  = [];

		if ( ! empty( $input['keys'] ) ) {
			foreach ( (array) $input['keys'] as $key ) {
				$key = (string) $key;

				if ( isset( $schema[ $key ] ) ) {
					$wanted[] = $key;
					continue;
				}

				$unknown[] = $key;
			}
		} else {
			$wanted = array_keys( $schema );
		}

		// `false`, not `true`: the merged view is what the plugin's own readers
		// see, so a setting that has never been saved reports its default
		// rather than nothing at all.
		$stored   = (array) betterdocs()->settings->get_all( false );
		$settings = [];
		$masked   = [];

		foreach ( $wanted as $key ) {
			if ( '' !== $tab && $schema[ $key ]['tab'] !== $tab ) {
				continue;
			}

			$value = array_key_exists( $key, $stored ) ? $stored[ $key ] : $schema[ $key ]['default'];

			$settings[ $key ] = SettingsSchema::to_public( $key, $value );

			if ( ! $schema[ $key ]['readable'] ) {
				$masked[] = $key;
			}
		}

		return [
			'settings' => $settings,
			'masked'   => $masked,
			'unknown'  => $unknown,
			'count'    => count( $settings )
		];
	}
}
