<?php
/**
 * MCP tool catalog, built from the Abilities API registry.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilitiesRegistrar;
use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;

/**
 * Turns the abilities registry into the MCP tool surface, and back again.
 *
 * There is no hand-written tool catalog: every registered `betterdocs/*` and
 * `betterdocs-pro/*` ability is one MCP tool, so the two can never drift. What
 * this class adds on top of the registry is the naming, the scope model and the
 * error vocabulary.
 *
 * **Naming.** MCP tool names cannot contain `/`, so the ability prefix is
 * stripped — and `bd-` is put back in its place. That prefix is not decoration:
 * an agent may have several MCP servers connected at once, and another plugin's
 * catalog can carry its own `create-term` or `get-settings`. `bd-` makes every
 * name in this catalog unambiguous, and the reverse lookup puts the ability
 * namespace back.
 *
 * **The classifier.** A read-only credential may only invoke read tools, and a
 * tool is a read when its bare name starts with `get-` or `list-`. The `bd-`
 * prefix has to come off *before* that test — a classifier that asks whether
 * `bd-get-status` starts with `get-` calls every tool in the catalog a write,
 * silently emptying a read-only connection's tool list.
 *
 * **The errors.** `invoke()` runs the ability through the Abilities API's own
 * `execute()` (ADR-031) so MCP calls are validated exactly like every other
 * client's, then maps the runtime's deliberately generic codes back onto the
 * typed vocabulary an agent can branch on.
 *
 * @since 4.9.0
 */
final class MCPTools {

	/**
	 * Ability-name prefixes exposed as MCP tools. Free owns `betterdocs/`; Pro
	 * registers under `betterdocs-pro/` through the
	 * `betterdocs_register_abilities` filter.
	 *
	 * @since 4.9.0
	 */
	const ABILITY_PREFIXES = [ 'betterdocs/', 'betterdocs-pro/' ];

	/**
	 * Prefix every BetterDocs MCP tool name carries.
	 *
	 * @since 4.9.0
	 */
	const TOOL_PREFIX = 'bd-';

	/**
	 * Bare-name prefixes that mark a tool as read-only.
	 *
	 * @since 4.9.0
	 */
	const READ_PREFIXES = [ 'get-', 'list-' ];

	/**
	 * Per-request read-only override. Null means "ask the pairing token".
	 *
	 * Set by `MCPServer` when an OAuth access token, which carries its own
	 * scopes, authorised the request.
	 *
	 * @since 4.9.0
	 *
	 * @var bool|null
	 */
	private static $read_only_override = null;

	/**
	 * Records the active credential's read-only state for this request.
	 *
	 * @since 4.9.0
	 *
	 * @param bool|null $read_only True/false to force it; null to fall back to
	 *                             the pairing token's own scope.
	 * @return void
	 */
	public static function set_read_only_override( $read_only ) {
		self::$read_only_override = null === $read_only ? null : (bool) $read_only;
	}

	/**
	 * Whether the active MCP credential may only read.
	 *
	 * Falls back to the pairing token's scope. The call is `class_exists()`-guarded,
	 * so with no override and no pairing class the answer is false, which is the
	 * same answer a full-access pairing gives.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_read_only() {
		if ( null !== self::$read_only_override ) {
			return self::$read_only_override;
		}

		if ( class_exists( __NAMESPACE__ . '\\MCPPairing' ) && method_exists( __NAMESPACE__ . '\\MCPPairing', 'is_read_only' ) ) {
			return (bool) MCPPairing::is_read_only();
		}

		return false;
	}

	/**
	 * The MCP tool name for an ability, or '' when the ability is not ours.
	 *
	 * @since 4.9.0
	 *
	 * @param string $ability_name Full ability name, e.g. `betterdocs/create-doc`.
	 * @return string Tool name (`bd-create-doc`), or '' to be skipped.
	 */
	public static function tool_name( $ability_name ) {
		$bare = self::strip_prefix( (string) $ability_name );

		if ( null === $bare || '' === $bare ) {
			return '';
		}

		return self::TOOL_PREFIX . $bare;
	}

	/**
	 * The registered ability behind a tool name, or null.
	 *
	 * Free and Pro bare names never collide, so the prefixes are tried in order
	 * and the first registered hit wins. A name without the `bd-` prefix is not
	 * one of our tools at all — that is what stops a bare `get-settings` (which
	 * could be any plugin's) from resolving here.
	 *
	 * The probe is `wp_has_ability()`, never `wp_get_ability()`: a miss on the
	 * latter calls `_doing_it_wrong()` (`WP_Abilities_Registry::get_registered()`,
	 * in core and in the bundled copy alike), so trying `betterdocs/` before
	 * `betterdocs-pro/` would write a notice into `debug.log` on **every** call to
	 * a Pro tool. Measured on the rig before this was changed.
	 *
	 * @since 4.9.0
	 *
	 * @param string $tool Tool name.
	 * @return string|null Ability name, or null when nothing matches.
	 */
	public static function ability_name( $tool ) {
		$tool = (string) $tool;

		if ( 0 !== strpos( $tool, self::TOOL_PREFIX ) ) {
			return null;
		}

		$bare = substr( $tool, strlen( self::TOOL_PREFIX ) );

		if ( '' === $bare || ! function_exists( 'wp_has_ability' ) ) {
			return null;
		}

		foreach ( self::ABILITY_PREFIXES as $prefix ) {
			if ( wp_has_ability( $prefix . $bare ) ) {
				return $prefix . $bare;
			}
		}

		return null;
	}

	/**
	 * Whether a tool changes state.
	 *
	 * The `bd-` prefix comes off first; see the class docblock for why that
	 * ordering is the whole point. A name that is not one of ours is treated as
	 * a write, so an unrecognised tool can never be waved through a read-only
	 * credential.
	 *
	 * @since 4.9.0
	 *
	 * @param string $tool Tool name.
	 * @return bool
	 */
	public static function is_write_tool( $tool ) {
		$tool = (string) $tool;

		if ( 0 !== strpos( $tool, self::TOOL_PREFIX ) ) {
			return true;
		}

		$bare = substr( $tool, strlen( self::TOOL_PREFIX ) );

		foreach ( self::READ_PREFIXES as $prefix ) {
			if ( 0 === strpos( $bare, $prefix ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The catalog, in MCP `tools/list` shape.
	 *
	 * Scoped to what the credential may actually invoke: a read-only connection
	 * is shown only read tools, because listing a tool `invoke()` would refuse
	 * makes an agent plan around an action it cannot take.
	 *
	 * The Pro state is probed **once** for the whole list and handed to every
	 * ability's `describe()`, so a catalog of 28 tools costs one probe and every
	 * description agrees about the site.
	 *
	 * @since 4.9.0
	 *
	 * @param array|null $pro_state Optional pre-probed state, for callers that
	 *                              already have one (and for tests).
	 * @return array[] One entry per tool: name, title, description, inputSchema,
	 *                 annotations, and `_meta.requires_pro` — the spec's extension
	 *                 point on `Tool`, because a compliant client drops unknown
	 *                 top-level keys (ADR-053).
	 */
	public static function list( $pro_state = null ) {
		$state     = is_array( $pro_state ) ? $pro_state : ProState::get();
		$read_only = self::is_read_only();
		$out       = [];

		foreach ( self::abilities() as $ability ) {
			$ability_name = (string) $ability->get_name();
			$name         = self::tool_name( $ability_name );

			if ( '' === $name ) {
				continue;
			}

			if ( $read_only && self::is_write_tool( $name ) ) {
				continue;
			}

			$instance     = AbilitiesRegistrar::instance( $ability_name );
			$requires_pro = $instance ? $instance->requires_pro() : (bool) self::meta( $ability, 'requires_pro', false );
			$title        = $instance ? $instance->get_label() : $ability_name;

			$out[] = [
				'name'        => $name,
				'title'       => $requires_pro ? sprintf(
					/* translators: %s: tool label. */
					__( '%s (Pro)', 'betterdocs' ),
					$title
				) : $title,
				'description' => $instance ? $instance->describe( $state ) : (string) $ability->get_description(),
				'inputSchema' => self::input_schema( $ability ),
				'annotations' => self::annotations( $ability, $instance ),
				'_meta'       => [ 'requires_pro' => $requires_pro ]
			];
		}

		// Stable order, so a client diffing two `tools/list` responses sees only
		// real changes and not registry ordering.
		usort(
			$out,
			static function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return $out;
	}

	/**
	 * Run one tool.
	 *
	 * Goes through `WP_Ability::execute()` rather than straight to our own
	 * callback (ADR-031): that is where input normalisation, JSON Schema
	 * validation, the permission check, output validation and the runtime's
	 * filters live, and an MCP call must be validated exactly like a call from
	 * any other Abilities client. What comes back is then translated —
	 * deliberately generic runtime codes in, typed BetterDocs errors out.
	 *
	 * @since 4.9.0
	 *
	 * @param string $tool Tool name.
	 * @param array  $args Decoded arguments.
	 * @return mixed|\WP_Error Plain data, or a typed error.
	 */
	public static function invoke( $tool, array $args ) {
		$tool = (string) $tool;

		AbilitiesRegistrar::ensure_registered();

		$ability_name = self::ability_name( $tool );

		if ( null === $ability_name ) {
			return AbilityError::unknown_tool( $tool );
		}

		if ( self::is_write_tool( $tool ) && self::is_read_only() ) {
			return AbilityError::read_only( $tool );
		}

		// A known hit, so `wp_get_ability()` is quiet here — see `ability_name()`.
		$ability = function_exists( 'wp_get_ability' ) ? wp_get_ability( $ability_name ) : null;

		if ( ! $ability ) {
			return AbilityError::unknown_tool( $tool );
		}

		$result = $ability->execute( $args );

		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		return self::map_runtime_error( $result, $tool, $ability_name );
	}

	/**
	 * Translates an Abilities API error into the typed vocabulary.
	 *
	 * The runtime answers a refused call with a deliberately generic
	 * `ability_invalid_permissions` — it will not say which capability was
	 * missing, on the reasoning that whoever was refused should not learn what
	 * they lack. An agent, though, is acting for a person who *can* be told, and
	 * a refusal it cannot explain is a dead end. We know the capability, because
	 * the ability instance declared it, so the typed error is rebuilt here.
	 *
	 * Anything that already carries `data['error']` is one of ours and passes
	 * through untouched. Everything left over is a bug on our side rather than
	 * the caller's, so it becomes `upstream_error` and is logged under
	 * `WP_DEBUG`.
	 *
	 * Public because it is the seam the unit tests exercise: the mapping has to be
	 * verifiable without provoking the runtime into producing each code.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error        What the runtime returned.
	 * @param string    $tool         Tool name, for the message.
	 * @param string    $ability_name Ability name, to find the declared capability.
	 * @return \WP_Error
	 */
	public static function map_runtime_error( \WP_Error $error, $tool, $ability_name ) {
		$data = $error->get_error_data();

		if ( is_array( $data ) && isset( $data['error'] ) ) {
			return $error;
		}

		$code    = (string) $error->get_error_code();
		$message = (string) $error->get_error_message();

		if ( 'ability_invalid_permissions' === $code ) {
			$instance   = AbilitiesRegistrar::instance( $ability_name );
			$capability = $instance ? $instance->get_capability() : '';

			return AbilityError::capability_missing(
				$capability,
				sprintf(
					/* translators: %s: MCP tool name. */
					__( 'run the "%s" tool', 'betterdocs' ),
					$tool
				)
			);
		}

		if ( 'ability_invalid_input' === $code || 'ability_missing_input_schema' === $code ) {
			return AbilityError::invalid_input( self::input_field( $error ), $message );
		}

		// `ability_invalid_output`, `ability_callback_exception`,
		// `ability_invalid_execute_callback`, `ability_invalid_permission_callback`
		// and anything a controller returned untyped: all our side of the line.
		self::log( sprintf( 'tool %s failed with %s: %s', $tool, $code, $message ) );

		return AbilityError::upstream(
			$message,
			[
				'ability'      => $ability_name,
				'runtime_code' => $code
			]
		);
	}

	/**
	 * Every registered ability that belongs to BetterDocs.
	 *
	 * @since 4.9.0
	 *
	 * @return object[] `WP_Ability` instances.
	 */
	private static function abilities() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return [];
		}

		// Our abilities reach the registry through `wp_abilities_api_init`,
		// which fires once, from whichever copy of the API owns the global
		// functions. If a foreign copy owns them and fired before our hook was
		// attached, the catalog would be empty while auth and discovery all
		// reported success. This replays once where a replay can work, and is a
		// no-op otherwise (ADR-032).
		AbilitiesRegistrar::ensure_registered();

		$out = [];

		foreach ( wp_get_abilities() as $ability ) {
			if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
				continue;
			}

			if ( null !== self::strip_prefix( (string) $ability->get_name() ) ) {
				$out[] = $ability;
			}
		}

		return $out;
	}

	/**
	 * The ability's input schema, in the shape MCP clients validate against.
	 *
	 * @since 4.9.0
	 *
	 * @param object $ability Registered ability.
	 * @return array
	 */
	private static function input_schema( $ability ) {
		$schema = method_exists( $ability, 'get_input_schema' ) ? $ability->get_input_schema() : [];

		if ( ! is_array( $schema ) || empty( $schema ) ) {
			return [
				'type'       => 'object',
				'properties' => (object) []
			];
		}

		$schema = self::normalize_schema( $schema );

		if ( ! isset( $schema['type'] ) ) {
			$schema['type'] = 'object';
		}

		if ( ! isset( $schema['properties'] ) ) {
			$schema['properties'] = (object) [];
		}

		return $schema;
	}

	/**
	 * MCP annotations for one tool.
	 *
	 * The Abilities API and MCP spell the same hints differently — `readonly`
	 * against `readOnlyHint`, and so on — so the ability keeps the API's
	 * spelling and the translation happens here, at the boundary. `priority` is
	 * an Abilities-side ordering hint with no MCP counterpart and is not sent.
	 *
	 * @since 4.9.0
	 *
	 * @param object                   $ability  Registered ability.
	 * @param AbilityBase|null $instance Our own instance, when we have it.
	 * @return array
	 */
	private static function annotations( $ability, $instance ) {
		$source = $instance ? $instance->get_annotations() : self::meta( $ability, 'annotations', [] );

		if ( ! is_array( $source ) ) {
			$source = [];
		}

		return [
			'readOnlyHint'    => ! empty( $source['readonly'] ),
			'destructiveHint' => ! empty( $source['destructive'] ),
			'idempotentHint'  => ! empty( $source['idempotent'] ),
			'openWorldHint'   => ! empty( $source['openWorldHint'] )
		];
	}

	/**
	 * One `meta` value off a registered ability, whichever accessor the runtime
	 * copy provides.
	 *
	 * @since 4.9.0
	 *
	 * @param object $ability  Registered ability.
	 * @param string $key      Meta key.
	 * @param mixed  $fallback Value when the key is absent.
	 * @return mixed
	 */
	private static function meta( $ability, $key, $fallback = null ) {
		if ( method_exists( $ability, 'get_meta_item' ) ) {
			$value = $ability->get_meta_item( $key );

			return null === $value ? $fallback : $value;
		}

		if ( method_exists( $ability, 'get_meta' ) ) {
			$meta = $ability->get_meta();

			return isset( $meta[ $key ] ) ? $meta[ $key ] : $fallback;
		}

		return $fallback;
	}

	/**
	 * JSON Schema fix-ups for MCP clients.
	 *
	 * An empty PHP `properties` array encodes as `[]`, but the schema spec — and
	 * every MCP SDK validator — needs an object, `{}`. Recurses first, so a
	 * nested field-less object is fixed too.
	 *
	 * @since 4.9.0
	 *
	 * @param array $schema JSON Schema node.
	 * @return array
	 */
	private static function normalize_schema( array $schema ) {
		foreach ( $schema as $key => $value ) {
			if ( is_array( $value ) ) {
				$schema[ $key ] = self::normalize_schema( $value );
			}
		}

		if ( isset( $schema['properties'] ) && [] === $schema['properties'] ) {
			$schema['properties'] = (object) [];
		}

		// Same trap one key over: an all-optional ability declares `default => []`
		// so the runtime can fill a missing input (ADR-030), and that encodes as
		// `[]` against a `type: object` schema — which a client that applies
		// defaults before validating would then reject as its own schema's fault.
		// Only an object-typed node is touched; an array field's empty default is
		// already right.
		if ( isset( $schema['type'], $schema['default'] ) && 'object' === $schema['type'] && [] === $schema['default'] ) {
			$schema['default'] = (object) [];
		}

		return $schema;
	}

	/**
	 * Which input field the runtime rejected.
	 *
	 * The runtime does not say directly: core rebuilds the schema validator's
	 * error as a bare `ability_invalid_input` with no data, keeping only the
	 * reason in the message ("… Reason: input[status] is not one of …"). The
	 * data is checked first anyway, in case a filter or a future version
	 * supplies it.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error Runtime error.
	 * @return string Field name, or '' when it cannot be told.
	 */
	private static function input_field( \WP_Error $error ) {
		$data = $error->get_error_data();

		if ( is_array( $data ) ) {
			foreach ( [ 'param', 'field' ] as $key ) {
				if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
					return $data[ $key ];
				}
			}
		}

		if ( preg_match( '/input\[([^\]]+)\]/', (string) $error->get_error_message(), $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Strips a BetterDocs ability prefix.
	 *
	 * @since 4.9.0
	 *
	 * @param string $ability_name Full ability name.
	 * @return string|null Bare name, or null when the ability is not ours.
	 */
	private static function strip_prefix( $ability_name ) {
		foreach ( self::ABILITY_PREFIXES as $prefix ) {
			if ( 0 === strpos( $ability_name, $prefix ) ) {
				return substr( $ability_name, strlen( $prefix ) );
			}
		}

		return null;
	}

	/**
	 * WP_DEBUG-only diagnostic.
	 *
	 * @since 4.9.0
	 *
	 * @param string $message What happened.
	 * @return void
	 */
	private static function log( $message ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
		error_log( '[BD-MCP] ' . $message );
	}
}
