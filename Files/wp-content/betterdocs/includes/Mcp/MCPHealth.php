<?php
/**
 * Side-effect-free MCP health report.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilitiesRegistrar;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocs\Abilities\Runtime;

/**
 * One read-only picture of everything an MCP connection depends on.
 *
 * The report answers, in a single request, the questions support otherwise asks
 * for over three rounds of email: which plugin versions, who owns the Abilities
 * API, how many BetterDocs abilities registered, whether the site's own URLs
 * agree with each other, whether this user actually holds the capabilities the
 * tools gate on, and whether the database schema is current.
 *
 * Two properties are load-bearing and both are pinned by tests:
 *
 * 1. **Side-effect free.** No outbound HTTP, no writes, no state. Anything that
 *    exercises the round trip belongs to {@see MCPSelfTest}, which is a
 *    separate route precisely so this one stays safe to call from anywhere,
 *    including a broken site.
 * 2. **No secret material, at any depth.** Not the pairing token, not an OAuth
 *    access or refresh token, not a hash of one. `MCPPairing::public_status()`
 *    deliberately carries the connection token for the admin UI — this report
 *    must not reuse it, and reads the individual accessors instead.
 *
 * It is admin-gated but **not** gated by `enable_mcp` (ADR-013): a diagnostic
 * you can only read once the thing already works is not a diagnostic.
 *
 * @since 4.9.0
 */
final class MCPHealth {

	/**
	 * Every capability BetterDocs defines, in `Core\Roles` order.
	 *
	 * Held as a constant rather than read from `Roles::defaults_capabilities()`
	 * because that list is filtered (`betterdocs_default_caps`): a site that
	 * filtered a capability away would quietly shrink `missing` and hide the
	 * very gap this report exists to show. The constant is the specification;
	 * the administrator bucket is expected to equal it.
	 *
	 * @since 4.9.0
	 */
	const REQUIRED_CAPABILITIES = [
		'edit_docs',
		'edit_others_docs',
		'edit_private_docs',
		'edit_published_docs',
		'read_private_docs',
		'publish_docs',
		'delete_docs',
		'delete_private_docs',
		'delete_published_docs',
		'delete_others_docs',
		'manage_doc_terms',
		'edit_doc_terms',
		'delete_doc_terms',
		'manage_knowledge_base_terms',
		'edit_knowledge_base_terms',
		'delete_knowledge_base_terms',
		'edit_docs_settings',
		'read_docs_analytics',
		'read_faq_builder'
	];

	/**
	 * Build the report.
	 *
	 * @since 4.9.0
	 *
	 * @param int|null $user_id Whose capabilities to report on. Null (the
	 *                          default) means the current user.
	 * @return array
	 */
	public function report( $user_id = null ) {
		$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;

		return [
			'plugin'       => $this->plugin_info(),
			'mcp'          => $this->mcp_info(),
			'abilities'    => $this->abilities_info(),
			'runtime'      => $this->runtime_info(),
			'capabilities' => $this->capabilities_info( $user_id ),
			'user'         => $this->user_info( $user_id ),
			'urls'         => self::analyze_urls( home_url(), site_url(), rest_url() ),
			'database'     => $this->database_info()
		];
	}

	/**
	 * Free and Pro versions, and the full Pro state.
	 *
	 * `ProState::get( false )` — the knowledge-base feature flag is deliberately
	 * *not* asked about, so a site with Multiple Knowledge Base switched off
	 * reports `pro_unlicensed` or `ok` rather than the tool-facing
	 * `pro_active_setting_off` (ADR-034). This is a report, not a refusal.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function plugin_info() {
		return [
			'free_version' => defined( 'BETTERDOCS_VERSION' ) ? BETTERDOCS_VERSION : null,
			'pro'          => ProState::get( false )
		];
	}

	/**
	 * The switch, the endpoints, the pairing record and the OAuth grants.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function mcp_info() {
		return [
			'enabled'       => MCPManager::is_enabled(),
			'endpoint'      => MCPPairing::site_endpoint(),
			'endpoint_rest' => MCPPairing::site_endpoint_fallback(),
			'authorize_url' => MCPOAuth::authorize_url(),
			'discovery'     => [
				'well_known' => [
					'protected_resource'   => home_url( '/.well-known/oauth-protected-resource/' . MCPPairing::SITE_ENDPOINT_PATH ),
					'authorization_server' => home_url( '/.well-known/oauth-authorization-server/' . MCPPairing::SITE_ENDPOINT_PATH )
				],
				'rest_alias' => [
					'protected_resource'   => MCPOAuth::resource_metadata_url(),
					'authorization_server' => rest_url( MCPManager::NS . '/mcp/oauth/authorization-server' )
				]
			],
			// Built field by field from the individual accessors. `public_status()`
			// carries the connection token for the admin UI and must never be
			// spread into this report.
			'pairing'       => $this->pairing_info(),
			'oauth_apps'    => count( MCPOAuth::connected_apps() ),
			// Null under an external object cache, where the transients this
			// counts are not in the options table.
			'lockouts'      => MCPRateLimiter::active_lockouts()
		];
	}

	/**
	 * The pairing record with every credential field left out.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function pairing_info() {
		$state = MCPPairing::state();

		return [
			'connected'    => MCPPairing::is_connected(),
			'connected_at' => isset( $state['connected_at'] ) ? (int) $state['connected_at'] : 0,
			'last_used'    => isset( $state['last_used'] ) ? (int) $state['last_used'] : 0,
			'read_only'    => MCPPairing::is_read_only(),
			// The list `read_only` is derived from, so a scope nobody expected
			// is visible rather than collapsed into a boolean.
			'scopes'       => isset( $state['scopes'] ) && is_array( $state['scopes'] ) ? $state['scopes'] : [],
			'user_id'      => MCPPairing::user_id()
		];
	}

	/**
	 * Registry diagnostics plus the BetterDocs ability names.
	 *
	 * Only our two prefixes are listed: another plugin's abilities are none of
	 * this report's business, and a support transcript should not enumerate
	 * them.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function abilities_info() {
		$info = AbilitiesRegistrar::diagnostics();

		$names = [];

		if ( function_exists( 'wp_get_abilities' ) ) {
			foreach ( wp_get_abilities() as $ability ) {
				if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
					continue;
				}

				$name = (string) $ability->get_name();

				foreach ( MCPTools::ABILITY_PREFIXES as $prefix ) {
					if ( 0 === strpos( $name, $prefix ) ) {
						$names[] = $name;

						break;
					}
				}
			}

			sort( $names );
		}

		$info['names']   = $names;
		$info['summary'] = AbilitiesRegistrar::summary();

		return $info;
	}

	/**
	 * Who owns the Abilities API, and what else the runtime brought with it.
	 *
	 * The provider version comes from `Runtime::owner()`, never from
	 * `WP_ABILITIES_API_VERSION`: that constant is defined by whichever
	 * `bootstrap.php` loaded first, so on a core-owned site it still reports the
	 * bundled 0.4.0. When core owns the API the
	 * meaningful version is WordPress' own.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function runtime_info() {
		$owner = Runtime::owner();

		if ( 'core' === $owner['source'] ) {
			$owner['version'] = get_bloginfo( 'version' );
		}

		return [
			'abilities_api'      => $owner,
			'parsedown_version'  => self::bundled_version( 'erusev/parsedown' ),
			'jetpack_autoloader' => self::bundled_version( 'automattic/jetpack-autoloader' ),
			// Without libsodium `Core\SecretAtRest` stores the pairing token
			// in the clear (it degrades to a passthrough rather than failing),
			// so this is a real finding, not trivia.
			'sodium'             => function_exists( 'sodium_crypto_secretbox' )
		];
	}

	/**
	 * A package version from the bundled runtime's own `installed.json`.
	 *
	 * @since 4.9.0
	 *
	 * @param string $package Composer package name.
	 * @return string|null Null when the runtime is missing or the package is not in it.
	 */
	private static function bundled_version( $package ) {
		$file = dirname( __DIR__, 2 ) . '/dependencies/vendor/composer/installed.json';

		if ( ! is_readable( $file ) ) {
			return null;
		}

		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a bundled file from disk; WP_Filesystem is not initialised on a REST request.

		if ( ! is_array( $data ) || ! isset( $data['packages'] ) || ! is_array( $data['packages'] ) ) {
			return null;
		}

		foreach ( $data['packages'] as $entry ) {
			if ( isset( $entry['name'], $entry['version'] ) && $package === $entry['name'] ) {
				return (string) $entry['version'];
			}
		}

		return null;
	}

	/**
	 * What this user holds of what BetterDocs asks for.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User to test.
	 * @return array
	 */
	private function capabilities_info( $user_id ) {
		$held    = [];
		$missing = [];

		$user = $user_id > 0 ? get_user_by( 'id', $user_id ) : null;

		foreach ( self::REQUIRED_CAPABILITIES as $capability ) {
			$can = $user ? user_can( $user, $capability ) : false;

			if ( $can ) {
				$held[] = $capability;
			} else {
				$missing[] = $capability;
			}
		}

		return [
			'user_id'  => $user_id,
			'required' => self::REQUIRED_CAPABILITIES,
			'held'     => $held,
			'missing'  => $missing
		];
	}

	/**
	 * Who the report is about.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User to describe.
	 * @return array
	 */
	private function user_info( $user_id ) {
		$user = $user_id > 0 ? get_user_by( 'id', $user_id ) : null;

		if ( ! $user ) {
			return [
				'id'           => $user_id,
				'login'        => '',
				'display_name' => '',
				'roles'        => [],
				'exists'       => false
			];
		}

		return [
			'id'           => (int) $user->ID,
			'login'        => isset( $user->user_login ) ? (string) $user->user_login : '',
			'display_name' => isset( $user->display_name ) ? (string) $user->display_name : '',
			'roles'        => isset( $user->roles ) && is_array( $user->roles ) ? array_values( $user->roles ) : [],
			'exists'       => true
		];
	}

	/**
	 * Whether this site's own URLs agree with each other.
	 *
	 * A `home`/`siteurl` scheme disagreement, or a REST URL on a different
	 * scheme from `home`, is the classic cause of an MCP client's requests
	 * bouncing between http and https and of a discovery document advertising
	 * an identifier no client will match.
	 *
	 * Pure function of its arguments, so a test can drive it directly.
	 *
	 * @since 4.9.0
	 *
	 * @param string $home_url The `home` option.
	 * @param string $site_url The `siteurl` option.
	 * @param string $rest_url The REST base.
	 * @return array
	 */
	public static function analyze_urls( $home_url, $site_url, $rest_url ) {
		$home = strtolower( (string) wp_parse_url( (string) $home_url, PHP_URL_SCHEME ) );
		$site = strtolower( (string) wp_parse_url( (string) $site_url, PHP_URL_SCHEME ) );
		$rest = strtolower( (string) wp_parse_url( (string) $rest_url, PHP_URL_SCHEME ) );

		return [
			'home'               => (string) $home_url,
			'siteurl'            => (string) $site_url,
			'rest_url'           => (string) $rest_url,
			'home_scheme'        => $home,
			'siteurl_scheme'     => $site,
			'rest_scheme'        => $rest,
			'is_https'           => 'https' === $home,
			'home_siteurl_match' => '' !== $home && $home === $site,
			'rest_matches_home'  => '' !== $rest && $rest === $home
		];
	}

	/**
	 * Schema currency for Free and, when present, Pro.
	 *
	 * A doc or FAQ tool can fail long after the endpoint itself answers, if the
	 * tables the plugin expects are a version behind.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function database_info() {
		$installed = (string) get_option( 'betterdocs_db_version', '' );
		$expected  = defined( 'BETTERDOCS_DB_VERSION' ) ? (string) BETTERDOCS_DB_VERSION : '';

		$info = [
			'installed_version' => '' !== $installed ? $installed : null,
			'expected_version'  => '' !== $expected ? $expected : null,
			'up_to_date'        => '' !== $installed && '' !== $expected && $installed === $expected
		];

		if ( defined( 'BETTERDOCS_PRO_DB_VERSION' ) ) {
			$pro_installed = (string) get_option( 'betterdocs_pro_db_version', '' );
			$pro_expected  = (string) BETTERDOCS_PRO_DB_VERSION;

			$info['pro'] = [
				'installed_version' => '' !== $pro_installed ? $pro_installed : null,
				'expected_version'  => '' !== $pro_expected ? $pro_expected : null,
				'up_to_date'        => '' !== $pro_installed && '' !== $pro_expected && $pro_installed === $pro_expected
			];
		}

		return $info;
	}
}
