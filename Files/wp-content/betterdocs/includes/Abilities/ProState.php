<?php
/**
 * BetterDocs Pro state probe.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * One per-request answer to "what can this site actually do?".
 *
 * Three things decide whether a knowledge-base or analytics tool can run: is Pro
 * on disk, is it active, and — for the knowledge-base family — is Multiple
 * Knowledge Base switched on. Each of those is a different sentence to an agent
 * and a different fix, so they are probed once, collapsed into a `state` slug,
 * and reused: by the stubs' refusals, by the live descriptions in `tools/list`,
 * and by `bd-get-status`.
 *
 * The probe is memoised for the request. `tools/list` describes 28 tools from a
 * single probe — {@see self::probe_count()} exists so that stays measurable — and
 * `reset()` clears it for tests.
 *
 * The licence is **reported, never enforced** (ADR-004): BetterDocs Pro's own
 * features run whether or not `betterdocs_pro_software__license_status` is
 * `valid`, so an MCP server that refused on it would be stricter than the
 * product it speaks for. It is also the *last* thing the state reports, so an
 * unlicensed site still gets the actionable answer when something else is
 * genuinely in the way (ADR-034).
 *
 * @since 4.9.0
 */
final class ProState {

	/**
	 * Pro's plugin basename, as WordPress keys it.
	 *
	 * @since 4.9.0
	 */
	const PRO_BASENAME = 'betterdocs-pro/betterdocs-pro.php';

	/**
	 * Option Pro writes its licence status into.
	 *
	 * @since 4.9.0
	 */
	const LICENSE_OPTION = 'betterdocs_pro_software__license_status';

	/**
	 * Memoised probe (everything except `state`, which depends on the caller's
	 * feature).
	 *
	 * @since 4.9.0
	 *
	 * @var array|null
	 */
	private static $probe = null;

	/**
	 * How many times the site was really probed this request.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private static $probe_count = 0;

	/**
	 * The Pro state as this request sees it.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $kb_feature Whether the caller needs the Multiple Knowledge Base
	 *                         feature. Analytics does not, so it must not be told
	 *                         a setting it does not use is in its way.
	 * @return array {
	 *     @type bool        $installed              Pro's plugin file is on disk.
	 *     @type bool        $active                 Pro is active.
	 *     @type string|null $version                Pro's version when active, else null.
	 *     @type string      $license_status         Raw licence status option ('' when unset).
	 *     @type bool        $licensed               Licence status is exactly `valid`.
	 *     @type bool        $multiple_kb            The Multiple Knowledge Base setting is on.
	 *     @type bool        $kb_taxonomy_registered `knowledge_base` is a registered taxonomy.
	 *     @type string      $state                  One of `pro_not_installed`, `pro_not_active`,
	 *                                               `pro_active_setting_off`, `pro_unlicensed`, `ok`.
	 * }
	 */
	public static function get( bool $kb_feature = true ): array {
		$state = self::probe();

		$state['state'] = self::resolve_state( $state, $kb_feature );

		return $state;
	}

	/**
	 * Whether a state stops the feature from running at all.
	 *
	 * `pro_unlicensed` is deliberately **not** blocking (ADR-004) — it is
	 * reported in descriptions and in `bd-get-status`, and execution proceeds.
	 *
	 * @since 4.9.0
	 *
	 * @param array $state A {@see self::get()} result.
	 * @return bool
	 */
	public static function is_blocking( array $state ): bool {
		$slug = isset( $state['state'] ) ? (string) $state['state'] : '';

		return in_array( $slug, [ 'pro_not_installed', 'pro_not_active', 'pro_active_setting_off' ], true );
	}

	/**
	 * Appends what is true on *this* site to a tool's base description.
	 *
	 * Used by every Pro stub and by Pro's own knowledge-base
	 * abilities, so the sentence an agent reads in `tools/list` is the same one
	 * it would get as a refusal — it can decide not to call the tool at all, or
	 * fix the setting first.
	 *
	 * @since 4.9.0
	 *
	 * @param string $base              Static description of the tool.
	 * @param array  $state             A {@see self::get()} result.
	 * @param string $setting_tool_hint MCP tool name that can turn the setting on.
	 * @return string
	 */
	public static function describe( string $base, array $state, string $setting_tool_hint = 'bd-update-settings' ): string {
		$slug = isset( $state['state'] ) ? (string) $state['state'] : 'ok';

		switch ( $slug ) {
			case 'pro_not_installed':
			case 'pro_not_active':
				$suffix = __( 'Requires BetterDocs Pro, which is not active on this site.', 'betterdocs' );
				break;

			case 'pro_unlicensed':
				$status = isset( $state['license_status'] ) ? (string) $state['license_status'] : '';

				$suffix = sprintf(
					/* translators: %s: licence status reported by BetterDocs Pro. */
					__( 'BetterDocs Pro is active but its licence is not valid (status: %s); the tool still works.', 'betterdocs' ),
					'' !== $status ? $status : __( 'none', 'betterdocs' )
				);
				break;

			case 'pro_active_setting_off':
				$suffix = sprintf(
					/* translators: %s: MCP tool name that writes settings. */
					__( 'Multiple Knowledge Base is off — enable it with %s ({"multiple_kb": true}); it takes effect from the next request.', 'betterdocs' ),
					$setting_tool_hint
				);
				break;

			default:
				return $base;
		}

		$base = rtrim( $base );

		return '' !== $base ? $base . ' ' . $suffix : $suffix;
	}

	/**
	 * How many real probes have happened this request.
	 *
	 * `tools/list` must describe every tool from one probe; the acceptance
	 * battery asserts this stays at 1 across a full listing.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	public static function probe_count(): int {
		return self::$probe_count;
	}

	/**
	 * Clears the memoised probe and the counter. Tests only.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$probe       = null;
		self::$probe_count = 0;
	}

	/**
	 * Reads the site once.
	 *
	 * `installed` is answered before `active` on purpose: `is_pro_active()` goes
	 * through `Helper::is_plugin_active()`, which `include_once`s
	 * `wp-admin/includes/plugin.php` and so *defines* `get_plugins()` as a side
	 * effect. Probing in this order keeps the branch taken here the same whether
	 * or not something else already loaded that file.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private static function probe(): array {
		if ( null !== self::$probe ) {
			return self::$probe;
		}

		++self::$probe_count;

		$installed = self::pro_installed();
		$active    = self::pro_active();
		$license   = (string) get_option( self::LICENSE_OPTION, '' );

		self::$probe = [
			'installed'              => $installed,
			'active'                 => $active,
			'version'                => defined( 'BETTERDOCS_PRO_VERSION' ) ? (string) BETTERDOCS_PRO_VERSION : null,
			'license_status'         => $license,
			'licensed'               => 'valid' === $license,
			'multiple_kb'            => (bool) self::setting( 'multiple_kb' ),
			'kb_taxonomy_registered' => function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'knowledge_base' )
		];

		return self::$probe;
	}

	/**
	 * Collapses the probe into one state slug.
	 *
	 * Precedence: not installed, not active, setting off, unlicensed, ok — most
	 * blocking first, and the licence last of the four because it blocks nothing.
	 *
	 * `pro_active_setting_off` deliberately outranks `pro_unlicensed`, which is
	 * the one place this diverges from the written plan (ADR-034). ADR-004 says
	 * the licence is reported and never enforced; if the licence outranked the
	 * setting then on an unlicensed site — the ordinary state of a fresh Pro
	 * install, and what the rig runs — a knowledge-base tool with Multiple
	 * Knowledge Base switched off would answer "the licence is not valid"
	 * instead of the actionable "switch the setting on": the licence would
	 * quietly change what an agent is told. It still surfaces on its own
	 * whenever nothing else is in the way.
	 *
	 * @since 4.9.0
	 *
	 * @param array $probe      Probe fields.
	 * @param bool  $kb_feature Whether the caller needs Multiple Knowledge Base.
	 * @return string
	 */
	private static function resolve_state( array $probe, bool $kb_feature ): string {
		if ( empty( $probe['installed'] ) && empty( $probe['active'] ) ) {
			return 'pro_not_installed';
		}

		if ( empty( $probe['active'] ) ) {
			return 'pro_not_active';
		}

		if ( $kb_feature && empty( $probe['multiple_kb'] ) ) {
			return 'pro_active_setting_off';
		}

		if ( empty( $probe['licensed'] ) ) {
			return 'pro_unlicensed';
		}

		return 'ok';
	}

	/**
	 * Whether Pro's plugin file is on disk, active or not.
	 *
	 * `get_plugins()` is the accurate answer (it is what the Plugins screen
	 * reads) but only exists once `wp-admin/includes/plugin.php` is loaded, which
	 * is not the case on a front-end request. The file check is the fallback, and
	 * is what a normal MCP request uses.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private static function pro_installed(): bool {
		if ( function_exists( 'get_plugins' ) ) {
			$plugins = get_plugins();

			return is_array( $plugins ) && isset( $plugins[ self::PRO_BASENAME ] );
		}

		return defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . self::PRO_BASENAME );
	}

	/**
	 * Whether Pro is active, asked of BetterDocs itself rather than of
	 * WordPress, so multisite network activation and any future override are
	 * answered the same way the rest of the plugin answers them.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private static function pro_active(): bool {
		if ( ! function_exists( 'betterdocs' ) ) {
			return false;
		}

		return (bool) betterdocs()->is_pro_active();
	}

	/**
	 * Reads one BetterDocs setting, tolerating a plugin that has not booted.
	 *
	 * @since 4.9.0
	 *
	 * @param string $key Setting key.
	 * @return mixed Null when settings are unreachable.
	 */
	private static function setting( string $key ) {
		if ( ! function_exists( 'betterdocs' ) ) {
			return null;
		}

		$plugin = betterdocs();

		if ( ! is_object( $plugin ) || ! isset( $plugin->settings ) || ! is_object( $plugin->settings ) ) {
			return null;
		}

		return $plugin->settings->get( $key );
	}
}
