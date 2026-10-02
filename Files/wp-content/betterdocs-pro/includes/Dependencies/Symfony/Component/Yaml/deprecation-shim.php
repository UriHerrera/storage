<?php
/**
 * Guarded shim for symfony/deprecation-contracts' global trigger_deprecation().
 *
 * The vendored Yaml component calls trigger_deprecation() on a few legacy
 * syntax paths (e.g. `0`-prefixed octal scalars in user-supplied specs). The
 * upstream contract is designed to be duplicated safely behind a
 * function_exists() guard; loading it here avoids a fatal when no other
 * plugin has loaded symfony/deprecation-contracts.
 *
 * Loaded by WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecParser before parsing.
 */

if ( ! function_exists( 'trigger_deprecation' ) ) {
	/**
	 * Triggers a silenced deprecation notice.
	 *
	 * @param string $package The name of the Composer package that is triggering the deprecation
	 * @param string $version The version of the package that introduced the deprecation
	 * @param string $message The message of the deprecation
	 * @param mixed  ...$args Values to insert in the message using printf() formatting
	 */
	function trigger_deprecation( string $package, string $version, string $message, ...$args ): void {
		@trigger_error( ( $package || $version ? "Since $package $version: " : '' ) . ( $args ? vsprintf( $message, $args ) : $message ), \E_USER_DEPRECATED );
	}
}
