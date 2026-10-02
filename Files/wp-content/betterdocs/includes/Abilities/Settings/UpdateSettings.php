<?php
/**
 * Write settings ability.
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
 * Change BetterDocs settings — all of them at once, or none of them.
 *
 * **Every key is validated before anything is written.** One bad value refuses
 * the whole call and lists every problem it found, so a batch of twelve
 * settings never lands half-applied and an agent gets all its mistakes in one
 * answer rather than one per round trip.
 *
 * The write goes to `Core\Settings::save_settings()` directly rather than
 * through `POST betterdocs/v1/settings`. That route is the same method behind a
 * REST controller Pro decorates with a reCAPTCHA `rest_pre_dispatch` check —
 * a human-verification gate an in-process agent call cannot satisfy and should
 * not have to.
 *
 * "Nothing changed" is success: sending a setting the value it already has is
 * a no-op, not a failure, and the answer says which keys were already right.
 *
 * @since 4.9.0
 */
class UpdateSettings extends AbilityBase {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/update-settings';
		$this->label       = __( 'Update settings', 'betterdocs' );
		$this->description = __( 'Change BetterDocs settings. Call bd-get-settings-schema first: each key has a type and, often, a closed set of values. Every key is checked before anything is written, so a call either applies in full or changes nothing. enable_mcp cannot be changed here. Some keys make permalinks stale or only take effect from the next request — the answer says which.', 'betterdocs' );
		$this->capability  = 'edit_docs_settings';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			// Destructive because it overwrites configuration that is not
			// versioned anywhere: there is no undo for a settings write.
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => true,
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
			'required'             => [ 'settings' ],
			'properties'           => [
				'settings' => [
					'type'        => 'object',
					'description' => __( 'The settings to write, as {key: value}. Types follow bd-get-settings-schema: toggles take true/false, numbers take whole numbers, selects take one of their allowed values, checkbox-selects take an array of them.', 'betterdocs' )
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
				'updated'             => [ 'type' => 'object' ],
				'unchanged'           => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ]
				],
				'rewrite_consequence' => [ 'type' => 'boolean' ],
				'notes'               => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ]
				]
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
		$settings = isset( $input['settings'] ) ? (array) $input['settings'] : [];

		if ( empty( $settings ) ) {
			return AbilityError::invalid_input(
				'settings',
				__( 'Send at least one setting to change, as {key: value}.', 'betterdocs' )
			);
		}

		$before  = (array) betterdocs()->settings->get_all( false );
		$coerced = [];
		$errors  = [];

		// Validate everything first. A partial write is worse than a refusal:
		// the caller cannot tell which half landed.
		foreach ( $settings as $key => $value ) {
			$key   = (string) $key;
			$valid = SettingsSchema::validate( $key, $value );

			if ( is_wp_error( $valid ) ) {
				$errors[] = [
					'key'      => $key,
					'error'    => $valid->get_error_code(),
					'message'  => $valid->get_error_message(),
					'wp_error' => $valid
				];

				continue;
			}

			$coerced[ $key ] = SettingsSchema::coerce( $key, $value );
		}

		if ( ! empty( $errors ) ) {
			return $this->refuse( $errors );
		}

		$saved = betterdocs()->settings->save_settings( $coerced );

		if ( is_wp_error( $saved ) ) {
			// `unauthorized_action` is the one refusal that is about the caller
			// rather than the values.
			if ( 'unauthorized_action' === $saved->get_error_code() ) {
				return AbilityError::capability_missing( 'edit_docs_settings', __( 'change BetterDocs settings', 'betterdocs' ) );
			}

			return AbilityError::upstream( $saved->get_error_message(), [ 'code' => (string) $saved->get_error_code() ] );
		}

		// `save_settings()` answers false when the stored array did not change.
		// That is not a failure — the settings are what the caller asked for.
		$after     = (array) betterdocs()->settings->get_all( false );
		$updated   = [];
		$unchanged = [];

		foreach ( array_keys( $coerced ) as $key ) {
			$new = array_key_exists( $key, $after ) ? $after[ $key ] : null;
			$old = array_key_exists( $key, $before ) ? $before[ $key ] : null;

			$updated[ $key ] = SettingsSchema::to_public( $key, $new );

			if ( $new === $old ) {
				$unchanged[] = $key;
			}
		}

		return array_merge(
			[
				'updated'   => $updated,
				'unchanged' => $unchanged
			],
			$this->consequences( array_keys( $coerced ) )
		);
	}

	/**
	 * The typed refusal for a call that validated badly.
	 *
	 * The **first** key's own error is returned unchanged — code, message and
	 * every typed field — so a `pro_required` stays `pro_required` with
	 * `fixable_by_agent: false`, and only then is the full list attached under
	 * `errors` so an agent can fix everything in one go instead of one refusal
	 * per round trip.
	 *
	 * @since 4.9.0
	 *
	 * @param array[] $errors One entry per bad key.
	 * @return \WP_Error
	 */
	protected function refuse( array $errors ) {
		$first = $errors[0];

		/** @var \WP_Error $error */
		$error = $first['wp_error'];
		$data  = (array) $error->get_error_data();

		if ( ! isset( $data['field'] ) ) {
			$data['field'] = $first['key'];
		}

		$data['errors'] = array_map(
			static function ( array $entry ) {
				return [
					'key'     => $entry['key'],
					'error'   => $entry['error'],
					'message' => $entry['message']
				];
			},
			$errors
		);

		return new \WP_Error( $error->get_error_code(), $error->get_error_message(), $data );
	}

	/**
	 * What the caller should know about the keys that were written.
	 *
	 * @since 4.9.0
	 *
	 * @param string[] $keys Written keys.
	 * @return array
	 */
	protected function consequences( array $keys ) {
		$notes   = [];
		$rewrite = false;

		foreach ( $keys as $key ) {
			$entry = SettingsSchema::entry( $key );

			if ( null === $entry ) {
				continue;
			}

			if ( $entry['rewrite_consequence'] ) {
				$rewrite = true;
			}
		}

		if ( $rewrite ) {
			$notes[] = __( 'Permalinks will be flushed on the next request; pretty URLs may take one more request to settle.', 'betterdocs' );
		}

		if ( in_array( 'multiple_kb', $keys, true ) ) {
			$notes[] = __( 'Takes effect from the next request: the knowledge_base taxonomy registers on the next load.', 'betterdocs' );
		}

		foreach ( $keys as $key ) {
			if ( SettingsSchema::is_secret( $key ) ) {
				$notes[] = sprintf(
					/* translators: %s: setting key. */
					__( '"%s" was written but can never be read back; bd-get-settings returns it masked.', 'betterdocs' ),
					$key
				);
			}
		}

		return [
			'rewrite_consequence' => $rewrite,
			'notes'               => $notes
		];
	}
}
