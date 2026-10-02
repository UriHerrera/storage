<?php
/**
 * The settings schema, resolved from the live settings tree.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocs\Core\Settings;

/**
 * BetterDocs has no settings schema file — it has a **UI tree**.
 *
 * `Core\Settings::settings_args()` is 1,900 lines of tabs, sections and fields
 * written for the admin's form builder, filtered by a dozen hooks (Pro adds
 * whole tabs through them), and `get_default()` is a separate flat map of
 * defaults that has drifted from the tree's own `default` keys. Neither alone
 * says what an agent may write.
 *
 * This class joins them at runtime, after `init`, and emits one entry per
 * **data-bearing** field: what type it is in JSON, what it defaults to, which
 * values it accepts, whether it needs Pro, whether writing it has consequences.
 * That resolved map is the contract behind all three settings tools, and it is
 * the thing `bd-get-settings-schema` hands an agent before it writes anything.
 *
 * Two rules keep it honest:
 *
 * - **`get_default()` wins on values, the tree wins on shape.** Where a field's
 *   own `default` disagrees with `get_default()`, the flat map is what
 *   `Settings::get()` actually returns, so it is the default an agent is told.
 * - **Nothing is emitted that cannot be written back.** Whole tabs are dropped
 *   (ADR-010), so are the field types that are buttons, uploaders and repeaters,
 *   and so is anything the UI itself disables.
 *
 * @since 4.9.0
 */
final class SettingsSchema {

	/**
	 * Tabs the MCP surface does not expose (ADR-010): licensing, the
	 * import/export and migration tooling (file uploads and destructive
	 * one-shots), and Git Sync (credentials plus a repository the agent has no
	 * business rewiring).
	 *
	 * @since 4.9.0
	 */
	const EXCLUDED_TABS = [ 'tab-license', 'tab-import-export', 'tab-migration', 'tab-git-sync' ];

	/**
	 * Keys refused before the schema is even consulted, with the reason.
	 *
	 * `enable_mcp` is the MCP master switch. It is not in the settings tree at
	 * all (it lives only in `get_default()`, deliberately off the Settings
	 * screen), so an agent writing it would otherwise be told "not a BetterDocs
	 * setting" — true, and useless. A tool must not be able to switch off the
	 * server it is talking through.
	 *
	 * @since 4.9.0
	 */
	const NOT_WRITABLE_KEYS = [
		'enable_mcp' => 'enable_mcp is the MCP master switch and cannot be changed from here. An administrator can toggle it under BetterDocs → MCP.'
	];

	/**
	 * Credential-shaped setting keys the MCP surface must never read back, on
	 * top of `Settings::sensitive_api_key_fields()` — which lists only the AI
	 * provider keys. `recaptcha_secret_key` is Pro's server-side reCAPTCHA
	 * secret: data-bearing, credential-shaped, and absent from that list, so
	 * without this it was read back in clear by `bd-get-settings`.
	 * `secret_keys()` unions this with the AI list and a filter so Pro and
	 * add-ons can extend it (ADR-059).
	 *
	 * @since 4.9.0
	 */
	const MCP_SECRET_KEYS = [
		'recaptcha_secret_key'
	];

	/**
	 * Field types that carry no stored value: layout, buttons, one-shot
	 * actions, uploaders, code viewers and the repeaters deferred to v2.
	 *
	 * `copy-to-clipboard` is here too, against `03-ARCHITECTURE.md`'s type
	 * table: measured on the rig, all 16 of them are shortcode strings shown for
	 * copying, every one absent from `get_default()`, and writing one would put
	 * a key in the option that nothing ever reads.
	 *
	 * @since 4.9.0
	 */
	const SKIPPED_TYPES = [
		'section',
		'tab',
		'title',
		'button',
		'action',
		'html',
		'codeviewer',
		'cross_domain_code',
		'wwa_instructions',
		'ai_edit_actions',
		'settingsuploader',
		'importerupload',
		'github-repo-settings',
		'better-repeater',
		'copy-to-clipboard'
	];

	/**
	 * Field type → JSON type.
	 *
	 * @since 4.9.0
	 */
	const TYPE_MAP = [
		'toggle'                => 'boolean',
		'text'                  => 'string',
		'textarea'              => 'string',
		'permalink_structure'   => 'string',
		'media'                 => 'string',
		'colorpicker'           => 'string',
		'number'                => 'integer',
		'min_token_number'      => 'integer',
		'select'                => 'string',
		'radio-card'            => 'string',
		'platform_model_select' => 'string',
		'checkbox-select'       => 'array'
	];

	/**
	 * Keys whose change makes WordPress rewrite rules stale.
	 *
	 * The first six are what `Core\Rewrite::flush_rewrite_rules()` compares
	 * (measured at `Rewrite.php` L134–144) before setting the
	 * `betterdocs_flush_rewrite_rules` transient. The last two change which
	 * taxonomies register at all, from the next request, and pretty knowledge
	 * base URLs need a flush after them.
	 *
	 * @since 4.9.0
	 */
	const REWRITE_KEYS = [
		'permalink_structure',
		'docs_slug',
		'builtin_doc_page',
		'docs_page',
		'tag_slug',
		'category_slug',
		'multiple_kb',
		'disable_root_slug_mkb'
	];

	/**
	 * Keys `Core\Settings::fallback_slugs()` silently restores when they are
	 * saved empty (its own `$cannot_be_empty`, which is private — kept in step
	 * with it by hand).
	 *
	 * Refusing an empty value up front is the difference between "that did not
	 * work" and a write that reports success and quietly puts the old value
	 * back.
	 *
	 * @since 4.9.0
	 */
	const CANNOT_BE_EMPTY = [
		'breadcrumb_doc_title',
		'docs_slug',
		'category_slug',
		'tag_slug',
		'permalink_structure',
		'docs_page'
	];

	/**
	 * The subset of {@see self::CANNOT_BE_EMPTY} an empty value is refused for.
	 *
	 * `docs_page` is left out on purpose: its fallback is conditional — with
	 * `builtin_doc_page` on, "no page" is the normal state, and only with the
	 * built-in page off does `fallback_slugs()` step in (by switching
	 * `builtin_doc_page` back on rather than restoring the page). The other five
	 * are plain slug strings whose empty value is always discarded.
	 *
	 * @since 4.9.0
	 */
	const REFUSE_EMPTY = [
		'breadcrumb_doc_title',
		'docs_slug',
		'category_slug',
		'tag_slug',
		'permalink_structure'
	];

	/**
	 * What a secret reads back as. A constant, never
	 * `Helper::mask_api_key()`'s partial mask: that keeps the prefix and the
	 * last four characters for a human who needs to recognise their own key,
	 * and neither of those belongs in a model's context.
	 *
	 * @since 4.9.0
	 */
	const MASK = '********';

	/**
	 * Resolved schema, per request.
	 *
	 * @since 4.9.0
	 *
	 * @var array|null
	 */
	private static $schema = null;

	/**
	 * Resolved tab list, per request.
	 *
	 * @since 4.9.0
	 *
	 * @var array|null
	 */
	private static $tabs = null;

	/**
	 * The whole schema, keyed by setting name.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $fresh Rebuild instead of using the memo.
	 * @return array<string, array>
	 */
	public static function resolve( $fresh = false ) {
		if ( $fresh || null === self::$schema ) {
			self::build();
		}

		return self::$schema;
	}

	/**
	 * `[ { id, label, included }, … ]` for every tab in the tree.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $fresh Rebuild instead of using the memo.
	 * @return array[]
	 */
	public static function tabs( $fresh = false ) {
		if ( $fresh || null === self::$tabs ) {
			self::build();
		}

		return self::$tabs;
	}

	/**
	 * One entry, or null.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key Setting key.
	 * @return array|null
	 */
	public static function entry( $key ) {
		$schema = self::resolve();

		return isset( $schema[ $key ] ) ? $schema[ $key ] : null;
	}

	/**
	 * Drop the memo. For tests, and for anything that changes the tree mid-request.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function reset() {
		self::$schema = null;
		self::$tabs   = null;
	}

	/**
	 * Whether a key is one of the API keys that must never be read back.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_secret( $key ) {
		return in_array( (string) $key, self::secret_keys(), true );
	}

	/**
	 * May this key be written with this value?
	 *
	 * Every refusal is typed and says what would work instead, because the
	 * caller is a model that will otherwise guess.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Proposed value.
	 * @return true|\WP_Error
	 */
	public static function validate( $key, $value ) {
		$key = (string) $key;

		if ( isset( self::NOT_WRITABLE_KEYS[ $key ] ) ) {
			return AbilityError::invalid_input( $key, self::NOT_WRITABLE_KEYS[ $key ] );
		}

		$entry = self::entry( $key );

		if ( null === $entry ) {
			return AbilityError::invalid_input(
				$key,
				sprintf(
					/* translators: %s: setting key. */
					__( '"%s" is not a BetterDocs setting; call bd-get-settings-schema for the keys this site accepts.', 'betterdocs' ),
					$key
				)
			);
		}

		if ( ! $entry['writable'] ) {
			return AbilityError::invalid_input(
				$key,
				sprintf(
					/* translators: %s: setting key. */
					__( '"%s" is listed but not writable on this site — BetterDocs disables the field, usually because the feature it belongs to is not installed.', 'betterdocs' ),
					$key
				)
			);
		}

		if ( $entry['pro'] && ! betterdocs()->is_pro_active() ) {
			return AbilityError::pro_required( ProState::get( false ), $entry['label'] );
		}

		$typed = self::check_type( $entry, $value );

		if ( is_wp_error( $typed ) ) {
			return $typed;
		}

		$coerced = self::coerce( $key, $value );

		if ( in_array( $key, self::REFUSE_EMPTY, true ) && self::is_empty( $coerced ) ) {
			return AbilityError::invalid_input(
				$key,
				sprintf(
					/* translators: 1: setting key, 2: default value. */
					__( '"%1$s" cannot be empty: BetterDocs puts the default ("%2$s") back on save, so an empty value is silently discarded.', 'betterdocs' ),
					$key,
					is_scalar( $entry['default'] ) ? (string) $entry['default'] : ''
				)
			);
		}

		if ( is_array( $entry['enum'] ) && ! empty( $entry['enum'] ) ) {
			$allowed = array_map( 'strval', $entry['enum'] );
			$given   = 'array' === $entry['type'] ? (array) $coerced : [ $coerced ];

			foreach ( $given as $one ) {
				if ( ! in_array( (string) $one, $allowed, true ) ) {
					return AbilityError::invalid_input(
						$key,
						sprintf(
							/* translators: 1: value, 2: setting key. */
							__( '"%1$s" is not an allowed value for "%2$s".', 'betterdocs' ),
							is_scalar( $one ) ? (string) $one : gettype( $one ),
							$key
						),
						$entry['enum']
					);
				}
			}
		}

		if ( 'integer' === $entry['type'] ) {
			if ( null !== $entry['min'] && $coerced < $entry['min'] ) {
				return AbilityError::invalid_input(
					$key,
					sprintf(
						/* translators: 1: setting key, 2: minimum. */
						__( '"%1$s" must be at least %2$s.', 'betterdocs' ),
						$key,
						$entry['min']
					)
				);
			}

			if ( null !== $entry['max'] && $coerced > $entry['max'] ) {
				return AbilityError::invalid_input(
					$key,
					sprintf(
						/* translators: 1: setting key, 2: maximum. */
						__( '"%1$s" must be at most %2$s.', 'betterdocs' ),
						$key,
						$entry['max']
					)
				);
			}
		}

		return true;
	}

	/**
	 * The value as it should be stored.
	 *
	 * Booleans become real booleans: `Core\Settings::save_settings()` normalises
	 * `'1'`/`'on'`/`'true'` to `true` anyway, and `get_all()` casts back to
	 * whatever type the default has, so this is the one representation that
	 * survives both directions unchanged.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Proposed value.
	 * @return mixed
	 */
	public static function coerce( $key, $value ) {
		$entry = self::entry( $key );
		$type  = null === $entry ? 'string' : $entry['type'];

		switch ( $type ) {
			case 'boolean':
				return self::truthy( $value );

			case 'integer':
				return (int) $value;

			case 'array':
				$out = [];

				foreach ( (array) $value as $item ) {
					if ( is_scalar( $item ) ) {
						$out[] = (string) $item;
					}
				}

				return array_values( array_unique( $out ) );

			default:
				return is_scalar( $value ) ? (string) $value : '';
		}
	}

	/**
	 * The stored value as an agent should see it.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key    Setting key.
	 * @param mixed  $stored Value from `Settings::get_all()`.
	 * @return mixed
	 */
	public static function to_public( $key, $stored ) {
		if ( self::is_secret( $key ) ) {
			return ( null === $stored || '' === $stored ) ? '' : self::MASK;
		}

		$entry = self::entry( $key );
		$type  = null === $entry ? 'string' : $entry['type'];

		switch ( $type ) {
			case 'boolean':
				return self::truthy( $stored );

			case 'integer':
				return (int) $stored;

			case 'array':
				$out = [];

				foreach ( (array) $stored as $item ) {
					if ( is_scalar( $item ) ) {
						$out[] = (string) $item;
					}
				}

				return array_values( $out );

			default:
				return is_scalar( $stored ) ? (string) $stored : '';
		}
	}

	/**
	 * WordPress' own idea of truth, plus the two spellings BetterDocs stores.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $value Any value.
	 * @return bool
	 */
	public static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return ! in_array( strtolower( trim( $value ) ), [ '', '0', 'off', 'false', 'no' ], true );
		}

		return (bool) $value;
	}

	/**
	 * Build the schema and the tab list from the live tree.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	private static function build() {
		$settings = betterdocs()->settings;
		$args     = $settings->settings_args();
		$defaults = array_merge( (array) $settings->get_default(), (array) $settings->get_pro_defaults() );
		$tabs     = isset( $args['tabs'] ) && is_array( $args['tabs'] ) ? $args['tabs'] : [];

		self::$schema = [];
		self::$tabs   = [];

		foreach ( $tabs as $tab_key => $tab ) {
			$id       = isset( $tab['id'] ) ? (string) $tab['id'] : (string) $tab_key;
			$included = ! in_array( $id, self::EXCLUDED_TABS, true );

			self::$tabs[] = [
				'id'       => $id,
				'label'    => isset( $tab['label'] ) ? (string) $tab['label'] : $id,
				'included' => $included
			];

			if ( ! $included || empty( $tab['fields'] ) ) {
				continue;
			}

			self::walk( (array) $tab['fields'], $id, '', $defaults );
		}
	}

	/**
	 * Recurse the tree, emitting one entry per data-bearing field.
	 *
	 * Sections and tabs nest arbitrarily deep — the Layout tab is tabs inside
	 * sections inside tabs inside a section — and each level may hold fields of
	 * its own, so the walk descends first and decides afterwards.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $fields   Field list.
	 * @param string $tab      Tab id.
	 * @param string $section  Nearest section label.
	 * @param array  $defaults Flat default map.
	 * @return void
	 */
	private static function walk( array $fields, $tab, $section, array $defaults ) {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? (string) $field['type'] : '';

			if ( ! empty( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$nested = ( 'section' === $type && isset( $field['label'] ) ) ? (string) $field['label'] : $section;

				self::walk( $field['fields'], $tab, $nested, $defaults );
			}

			if ( ! isset( self::TYPE_MAP[ $type ] ) || in_array( $type, self::SKIPPED_TYPES, true ) ) {
				continue;
			}

			$key = isset( $field['name'] ) ? (string) $field['name'] : '';

			if ( '' === $key || isset( self::NOT_WRITABLE_KEYS[ $key ] ) ) {
				continue;
			}

			self::$schema[ $key ] = self::entry_for( $key, $type, $field, $tab, $section, $defaults );
		}
	}

	/**
	 * One schema entry.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key      Setting key.
	 * @param string $type     Field type from the tree.
	 * @param array  $field    The field.
	 * @param string $tab      Tab id.
	 * @param string $section  Section label.
	 * @param array  $defaults Flat default map.
	 * @return array
	 */
	private static function entry_for( $key, $type, array $field, $tab, $section, array $defaults ) {
		$json_type = self::TYPE_MAP[ $type ];
		$secret    = self::is_secret( $key );

		// `get_default()` is authoritative: it is what `Settings::get()` returns
		// when nothing is stored, whatever the tree's own `default` says.
		$default = array_key_exists( $key, $defaults )
			? $defaults[ $key ]
			: ( array_key_exists( 'default', $field ) ? $field['default'] : null );

		$entry = [
			'key'                 => $key,
			'type'                => $json_type,
			'default'             => self::cast( $json_type, $default ),
			'label'               => isset( $field['label'] ) ? (string) $field['label'] : $key,
			'help'                => self::help_of( $field ),
			'tab'                 => $tab,
			'section'             => (string) $section,
			'pro'                 => ! empty( $field['is_pro'] ),
			'enum'                => self::enum_of( $field ),
			'min'                 => isset( $field['min'] ) ? (int) $field['min'] : null,
			'max'                 => isset( $field['max'] ) ? (int) $field['max'] : null,
			'writable'            => empty( $field['disabled'] ),
			'readable'            => ! $secret,
			'rewrite_consequence' => in_array( $key, self::REWRITE_KEYS, true ),
			'notes'               => []
		];

		$entry['notes'] = self::notes_for( $entry, $secret );

		return $entry;
	}

	/**
	 * The per-entry notes: the things that are true about writing this key and
	 * are not visible from its type.
	 *
	 * @since 4.9.0
	 *
	 * @param array $entry  The entry so far.
	 * @param bool  $secret Whether the key holds a secret.
	 * @return string[]
	 */
	private static function notes_for( array $entry, $secret ) {
		$notes = [];

		if ( $entry['rewrite_consequence'] ) {
			$notes[] = __( 'Changing this makes the permalink rules stale; BetterDocs flushes them on the next request, so pretty URLs may take one more request to settle.', 'betterdocs' );
		}

		if ( 'multiple_kb' === $entry['key'] ) {
			$notes[] = __( 'Takes effect from the next request: the knowledge_base taxonomy registers on the next load.', 'betterdocs' );
		}

		if ( in_array( $entry['key'], self::REFUSE_EMPTY, true ) ) {
			$notes[] = __( 'Cannot be empty: BetterDocs restores the default when an empty value is saved, so this tool refuses one instead.', 'betterdocs' );
		} elseif ( in_array( $entry['key'], self::CANNOT_BE_EMPTY, true ) ) {
			$notes[] = __( 'With builtin_doc_page off, saving this empty switches builtin_doc_page back on rather than leaving the site without a docs page.', 'betterdocs' );
		}

		if ( $secret ) {
			$notes[] = __( 'Write-only: this value is never read back, only replaced.', 'betterdocs' );
		}

		if ( ! $entry['writable'] ) {
			$notes[] = __( 'Disabled on this site, so it cannot be written.', 'betterdocs' );
		}

		if ( $entry['pro'] ) {
			$notes[] = __( 'Needs BetterDocs Pro.', 'betterdocs' );
		}

		return $notes;
	}

	/**
	 * The field's help text. The tree uses `label_subtitle` throughout — `help`
	 * is in the field vocabulary but unused on this checkout — so both are read.
	 *
	 * @since 4.9.0
	 *
	 * @param array $field The field.
	 * @return string
	 */
	private static function help_of( array $field ) {
		foreach ( [ 'help', 'label_subtitle' ] as $candidate ) {
			if ( ! empty( $field[ $candidate ] ) && is_string( $field[ $candidate ] ) ) {
				return $field[ $candidate ];
			}
		}

		return '';
	}

	/**
	 * The allowed values behind a field's `options`, or null.
	 *
	 * `Settings::normalize_options()` turns `value => label` into
	 * `value => [ 'value' => …, 'label' => … ]`, but the tree also carries raw
	 * `value => label` maps and plain lists, so all three shapes are read.
	 *
	 * @since 4.9.0
	 *
	 * @param array $field The field.
	 * @return array|null
	 */
	private static function enum_of( array $field ) {
		if ( empty( $field['options'] ) || ! is_array( $field['options'] ) ) {
			return null;
		}

		$options = $field['options'];
		$is_list = array_keys( $options ) === range( 0, count( $options ) - 1 );
		$values  = [];

		foreach ( $options as $option_key => $option ) {
			if ( is_array( $option ) ) {
				if ( array_key_exists( 'value', $option ) && is_scalar( $option['value'] ) ) {
					$values[] = $option['value'];
				} elseif ( ! $is_list ) {
					$values[] = $option_key;
				}

				continue;
			}

			// A list of scalars is the values themselves; a map is keyed by them.
			$values[] = $is_list ? $option : $option_key;
		}

		$values = array_values( array_filter( $values, 'is_scalar' ) );

		return empty( $values ) ? null : $values;
	}

	/**
	 * Cast a default to the JSON type the entry advertises.
	 *
	 * @since 4.9.0
	 *
	 * @param string $type  JSON type.
	 * @param mixed  $value Default value.
	 * @return mixed
	 */
	private static function cast( $type, $value ) {
		switch ( $type ) {
			case 'boolean':
				return self::truthy( $value );

			case 'integer':
				return (int) $value;

			case 'array':
				$out = [];

				foreach ( (array) $value as $item ) {
					if ( is_scalar( $item ) ) {
						$out[] = (string) $item;
					}
				}

				return array_values( $out );

			default:
				return is_scalar( $value ) ? (string) $value : '';
		}
	}

	/**
	 * Type check with the coercions this plugin's own storage performs.
	 *
	 * @since 4.9.0
	 *
	 * @param array $entry The schema entry.
	 * @param mixed $value Proposed value.
	 * @return true|\WP_Error
	 */
	private static function check_type( array $entry, $value ) {
		switch ( $entry['type'] ) {
			case 'boolean':
				$accepted = is_bool( $value )
					|| ( is_int( $value ) && in_array( $value, [ 0, 1 ], true ) )
					|| ( is_string( $value ) && in_array( strtolower( trim( $value ) ), [ '1', '0', '', 'on', 'off', 'true', 'false', 'yes', 'no' ], true ) );

				return $accepted ? true : self::type_error( $entry, __( 'true or false', 'betterdocs' ), $value );

			case 'integer':
				$accepted = is_int( $value ) || ( is_string( $value ) && '' !== $value && (string) (int) $value === trim( $value ) );

				return $accepted ? true : self::type_error( $entry, __( 'a whole number', 'betterdocs' ), $value );

			case 'array':
				if ( ! is_array( $value ) ) {
					return self::type_error( $entry, __( 'an array of strings', 'betterdocs' ), $value );
				}

				foreach ( $value as $item ) {
					if ( ! is_scalar( $item ) ) {
						return self::type_error( $entry, __( 'an array of strings', 'betterdocs' ), $value );
					}
				}

				return true;

			default:
				return is_scalar( $value ) ? true : self::type_error( $entry, __( 'a string', 'betterdocs' ), $value );
		}
	}

	/**
	 * The typed refusal for a wrong type.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $entry  The schema entry.
	 * @param string $wanted What the field takes, as a phrase.
	 * @param mixed  $value  What arrived.
	 * @return \WP_Error
	 */
	private static function type_error( array $entry, $wanted, $value ) {
		return AbilityError::invalid_input(
			$entry['key'],
			sprintf(
				/* translators: 1: setting key, 2: what the field accepts, 3: what was sent. */
				__( '"%1$s" takes %2$s; got %3$s.', 'betterdocs' ),
				$entry['key'],
				$wanted,
				is_scalar( $value ) ? '"' . (string) $value . '"' : gettype( $value )
			),
			is_array( $entry['enum'] ) ? $entry['enum'] : null
		);
	}

	/**
	 * Whether a coerced value counts as empty for the `$cannot_be_empty` rule.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $value Coerced value.
	 * @return bool
	 */
	private static function is_empty( $value ) {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		return '' === (string) $value || '0' === (string) $value;
	}

	/**
	 * The site's secret setting keys.
	 *
	 * @since 4.9.0
	 *
	 * @return string[]
	 */
	private static function secret_keys() {
		$ai_keys = class_exists( Settings::class ) && method_exists( Settings::class, 'sensitive_api_key_fields' )
			? (array) Settings::sensitive_api_key_fields()
			: [];

		$keys = array_merge( $ai_keys, self::MCP_SECRET_KEYS );

		/**
		 * Filters the setting keys the MCP surface treats as secret: masked on
		 * read, reported in `masked[]`, and marked write-only in the schema. Pro
		 * and add-ons extend it so credential-shaped keys their own settings add
		 * are never read back through `bd-get-settings`.
		 *
		 * @since 4.9.0
		 *
		 * @param string[] $keys Setting keys treated as secret.
		 */
		$keys = (array) apply_filters( 'betterdocs_mcp_secret_setting_keys', $keys );

		return array_values( array_unique( array_map( 'strval', $keys ) ) );
	}
}
