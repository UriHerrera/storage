<?php

namespace WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backward compatibility shim for the pre-2.0.0 class name.
 *
 * This class was renamed `LicenseManager` -> `Manager` in 2.0.0. A consuming
 * plugin can end up running pre-rename bytecode against a post-rename copy of
 * this library — typically because the host serves stale OPcache
 * (`opcache.validate_timestamps=0`) after an update, or because a plugin update
 * was interrupted. Class names are resolved at runtime through the fresh
 * on-disk autoloader, so that old code looks for `LicenseManager`, does not find
 * it, and throws an uncaught `Error` on the `init` hook. That takes the entire
 * site down on every request, front end and admin, instead of degrading to
 * "license inactive".
 *
 * Keeping this class on disk makes that lookup succeed and the site stays up.
 *
 * It must remain a real class declaration at the PSR-4 path rather than a
 * `class_alias()` call: `composer dump-autoload -o` only records files that
 * actually declare a class, and Mozart/PHP-Scoper prefix this declaration along
 * with the rest of the namespace so every consumer inherits the shim on its
 * next build.
 *
 * @deprecated 2.0.0 Use {@see Manager} instead.
 */
class LicenseManager extends Manager {
	/**
	 * Whether the deprecation notice has already been logged this request.
	 *
	 * @var bool
	 */
	private static $notice_logged = false;

	/**
	 * Returns the shared license manager singleton.
	 *
	 * Deliberately returns the `Manager` instance rather than a `LicenseManager`
	 * one. `Manager::get_instance()` stores its singleton with `self::`, so both
	 * class names already resolve to the same object; a subclass-owned instance
	 * would register every hook, the API routes and the plugin updater a second
	 * time. Old code assigns this to a variable and calls methods on it, so the
	 * concrete class name is not observable to it.
	 *
	 * @param array $args Configuration arguments.
	 *
	 * @return Manager
	 *
	 * @throws \Exception If required args are missing.
	 */
	public static function get_instance( $args ) {
		self::log_deprecation();

		return Manager::get_instance( $args );
	}

	/**
	 * Logs a one-time deprecation notice when debug logging is enabled.
	 *
	 * Writes to the debug log rather than calling `_doing_it_wrong()`. This class
	 * is reached on every request of an affected install, and `_doing_it_wrong()`
	 * can emit output before headers are sent, which would corrupt AJAX and REST
	 * responses on exactly the sites this shim exists to keep alive.
	 *
	 * @return void
	 */
	private static function log_deprecation() {
		if ( self::$notice_logged ) {
			return;
		}

		self::$notice_logged = true;

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log(
			sprintf(
				'WPDeveloper Licensing: %1$s is deprecated since 2.0.0, use %2$s instead. The calling plugin is running pre-rename code — check for stale OPcache or an incomplete plugin update.',
				LicenseManager::class,
				Manager::class
			)
		);
	}
}
