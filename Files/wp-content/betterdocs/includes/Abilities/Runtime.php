<?php
/**
 * Ownership of the WordPress Abilities API runtime.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Decides who owns the Abilities API in the current request and, when WordPress
 * core owns it, makes BetterDocs' bundled copy inert.
 *
 * WordPress ships the Abilities API in core from 6.9 (`wp_register_ability()` is
 * `@since 6.9.0`). BetterDocs still bundles a copy under `dependencies/` because
 * the plugin supports WordPress 6.4, where core has no such API — so the bundle
 * owns the API on 6.4 to 6.8, and core owns it from 6.9 on. Nothing here compares
 * versions: `owner()` reflects on where `wp_get_abilities()` is actually defined,
 * which is why this paragraph falling out of date could never change behaviour. The bundled package guards its functions and registry classes with
 * `function_exists()` / `class_exists()`, so on 7.1 core wins those — but its
 * `includes/bootstrap.php` also loads two classes core does not define under the
 * same names (`WP_REST_Abilities_Init`, `WP_Abilities_Assets_Init`) and hooks them
 * unconditionally. Left alone on 7.1 that means a second handler set on every
 * `/wp-abilities/v1/*` route and a 137 KB script on every wp-admin screen.
 *
 * @since 4.9.0
 */
final class Runtime {

	/**
	 * Option remembering which owner the debug line was last written for.
	 *
	 * Only ever read or written while `WP_DEBUG` is on. Not a health-report source
	 * — call `owner()` for that; this exists solely to keep the log quiet.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const LOGGED_OWNER_OPTION = 'betterdocs_mcp_runtime_logged_owner';

	/**
	 * Memoised result of `owner()` for this request.
	 *
	 * @since 4.9.0
	 *
	 * @var array|null
	 */
	private static $owner = null;

	/**
	 * Whether the bundled hooks have already been removed once.
	 *
	 * Guards the debug log line, not the removals themselves — `stand_down()` is
	 * idempotent and is deliberately run more than once per request.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private static $stood_down = false;

	/**
	 * Wires the stand-down. Called from `betterdocs.php` right after the bundled
	 * runtime is required.
	 *
	 * Runs once immediately — the bundle hooks itself while `autoload_packages.php`
	 * is being required, so the hooks already exist by the time this is reached —
	 * and once more very late on `plugins_loaded`, which catches a copy of the same
	 * package carried by a plugin that loads after BetterDocs. Both passes finish
	 * before `init` and `rest_api_init` fire.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function init(): void {
		self::stand_down();

		add_action( 'plugins_loaded', [ self::class, 'stand_down' ], 9999 );
	}

	/**
	 * Reports which copy of the Abilities API is loaded in this request.
	 *
	 * `source` is one of:
	 * - `core`     — WordPress core's own copy, under `ABSPATH . WPINC`.
	 * - `bundled`  — the copy shipped by this plugin, under `dependencies/vendor/`.
	 * - `foreign`  — a copy owned by something else (another plugin, mu-plugin, a
	 *                Composer install elsewhere). Ours lost the Jetpack Autoloader's
	 *                newest-version vote, or was never in the running.
	 * - `none`     — the API is not loaded at all.
	 *
	 * Memoised: nothing can redeclare a PHP function mid-request, so the answer
	 * cannot change once taken.
	 *
	 * @since 4.9.0
	 *
	 * @return array {
	 *     @type string      $source  One of `core`, `bundled`, `foreign`, `none`.
	 *     @type string      $path    Normalised file that declares `wp_get_abilities`, or ''.
	 *     @type string|null $version Bundled package version when `source` is `bundled`, else null.
	 * }
	 */
	public static function owner(): array {
		if ( null !== self::$owner ) {
			return self::$owner;
		}

		if ( ! function_exists( 'wp_get_abilities' ) ) {
			self::$owner = [
				'source'  => 'none',
				'path'    => '',
				'version' => null
			];

			return self::$owner;
		}

		$path = '';

		try {
			$reflection = new \ReflectionFunction( 'wp_get_abilities' );
			$file       = $reflection->getFileName();
			$path       = is_string( $file ) ? wp_normalize_path( $file ) : '';
		} catch ( \ReflectionException $e ) {
			$path = '';
		}

		$source = 'foreign';

		if ( '' !== $path ) {
			if ( 0 === strpos( $path, trailingslashit( wp_normalize_path( ABSPATH . WPINC ) ) ) ) {
				$source = 'core';
			} elseif ( self::is_bundled_path( $path ) ) {
				$source = 'bundled';
			}
		}

		self::$owner = [
			'source'  => $source,
			'path'    => $path,
			'version' => 'bundled' === $source ? self::bundled_version() : null
		];

		return self::$owner;
	}

	/**
	 * Whether WordPress core provides the Abilities API in this request.
	 *
	 * Derived from `owner()` rather than from a bare `function_exists()` captured
	 * before the bundle is required. Core loads `wp-includes/abilities-api.php`
	 * from `wp-settings.php`, before any plugin file runs, so when core has the API
	 * the bundle's own `function_exists()` guard never fires and the declaring file
	 * is always core's. That makes `owner()` correct whether it is consulted before
	 * or after the require, and leaves `betterdocs.php` with nothing to capture.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function core_provides_api(): bool {
		$owner = self::owner();

		return 'core' === $owner['source'];
	}

	/**
	 * Removes the bundled package's hooks when core owns the Abilities API.
	 *
	 * Only `source === 'core'` triggers this, so on WordPress 6.4–6.8 — where the
	 * bundle *is* the Abilities API and those hooks are the only thing registering
	 * the REST routes and the client script — nothing is removed, by construction.
	 *
	 * The core-abilities registration hooks (`wp_abilities_api_categories_init`,
	 * `wp_abilities_api_init`) are deliberately left alone: the bundle only adds
	 * them when `wp_register_core_abilities()` is undefined, which never happens
	 * when core owns the API, and they are keyed by function name so core's own
	 * registration is not duplicated either way.
	 *
	 * Idempotent — safe to call on every pass.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function stand_down(): void {
		$owner = self::owner();

		if ( 'core' !== $owner['source'] ) {
			return;
		}

		$removed = false;

		// bootstrap.php L59-66 — re-registers the six /wp-abilities/v1/* routes that
		// core already registers from create_initial_rest_routes() at priority 99.
		if ( class_exists( 'WP_REST_Abilities_Init', false ) ) {
			if ( remove_action( 'rest_api_init', [ 'WP_REST_Abilities_Init', 'register_routes' ], 11 ) ) {
				$removed = true;
			}
		}

		// bootstrap.php L69-77 — registers the `wp-abilities` script handle and
		// enqueues it on every wp-admin screen. Core ships a script module instead.
		if ( class_exists( 'WP_Abilities_Assets_Init', false ) ) {
			if ( remove_action( 'init', [ 'WP_Abilities_Assets_Init', 'register_assets' ], 10 ) ) {
				$removed = true;
			}

			if ( remove_action( 'admin_enqueue_scripts', [ 'WP_Abilities_Assets_Init', 'admin_enqueue_scripts' ], 10 ) ) {
				$removed = true;
			}
		}

		if ( $removed && ! self::$stood_down ) {
			self::$stood_down = true;

			self::log_owner_change(
				'Abilities API owned by WordPress core (' . $owner['path'] . '); bundled runtime stood down.',
				$owner['source']
			);
		}
	}

	/**
	 * Whether a normalised path sits inside this plugin's bundled runtime.
	 *
	 * Both sides are compared raw and through `realpath()`, because a plugin
	 * directory is often a symlink in development and the two ends of the
	 * comparison do not always resolve it the same way.
	 *
	 * @since 4.9.0
	 *
	 * @param string $path Normalised absolute path to test.
	 * @return bool
	 */
	private static function is_bundled_path( string $path ): bool {
		$candidates = [ $path ];
		$real       = realpath( $path );

		if ( is_string( $real ) ) {
			$candidates[] = wp_normalize_path( $real );
		}

		foreach ( self::bundle_dirs() as $dir ) {
			foreach ( $candidates as $candidate ) {
				if ( 0 === strpos( $candidate, $dir ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * The bundled runtime's vendor directory, raw and resolved.
	 *
	 * Derived from `BETTERDOCS_PLUGIN_FILE` — defined in `betterdocs.php` before the
	 * runtime is required — and not from `BETTERDOCS_ROOT_DIR_PATH`, which is only
	 * defined later, when `Plugin::define_constants()` runs.
	 *
	 * @since 4.9.0
	 *
	 * @return array List of normalised, trailing-slashed directories.
	 */
	private static function bundle_dirs(): array {
		$root = defined( 'BETTERDOCS_PLUGIN_FILE' ) ? dirname( BETTERDOCS_PLUGIN_FILE ) : dirname( __DIR__, 2 );
		$dir  = trailingslashit( wp_normalize_path( $root ) ) . 'dependencies/vendor/';
		$dirs = [ $dir ];
		$real = realpath( $dir );

		if ( is_string( $real ) ) {
			$dirs[] = trailingslashit( wp_normalize_path( $real ) );
		}

		return array_unique( $dirs );
	}

	/**
	 * Version of the bundled Abilities API package, read from the runtime's own
	 * `composer/installed.json`.
	 *
	 * @since 4.9.0
	 *
	 * @return string|null Package version, or null when it cannot be determined.
	 */
	private static function bundled_version() {
		foreach ( self::bundle_dirs() as $dir ) {
			$file = $dir . 'composer/installed.json';

			if ( ! is_readable( $file ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a bundled build artefact on disk, not a remote resource.
			$raw = file_get_contents( $file );

			if ( ! is_string( $raw ) ) {
				continue;
			}

			$data = json_decode( $raw, true );

			if ( ! is_array( $data ) || empty( $data['packages'] ) || ! is_array( $data['packages'] ) ) {
				continue;
			}

			foreach ( $data['packages'] as $package ) {
				if ( isset( $package['name'], $package['version'] ) && 'wordpress/abilities-api' === $package['name'] ) {
					return (string) $package['version'];
				}
			}
		}

		return null;
	}

	/**
	 * Writes one debug-only diagnostic line, and only when the owner has changed
	 * since the last one was written.
	 *
	 * Standing down is the correct, expected behaviour on WordPress 6.9+, so a line
	 * on every request would be pure noise — a diagnostic log is for anomalies, not
	 * the happy path. What is worth a line is the *change*:
	 * the first stand-down after a WordPress upgrade, or another plugin taking the
	 * API over. The last-logged owner lives in a non-autoloaded option that is only
	 * touched while `WP_DEBUG` is on, so production pays nothing for it.
	 *
	 * @since 4.9.0
	 *
	 * @param string $message Message to log, without the prefix.
	 * @param string $source  Owner reported by `owner()['source']`.
	 * @return void
	 */
	private static function log_owner_change( string $message, string $source ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		if ( get_option( self::LOGGED_OWNER_OPTION ) === $source ) {
			return;
		}

		update_option( self::LOGGED_OWNER_OPTION, $source, false );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
		error_log( '[BD-MCP] ' . $message );
	}
}
