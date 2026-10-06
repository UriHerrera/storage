<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;

/**
 * BetterDocs roles and capabilities.
 *
 * Capabilities are granted on activation ({@see Install::activate()} →
 * {@see self::setup()}), which covers the five roles WordPress ships with. Two
 * gaps are repaired here:
 *
 * - a role **created after activation** never receives anything, because
 *   WordPress has no hook on `add_role()`;
 * - a site that was activated silently (by another plugin) never ran
 *   `setup()` at all.
 *
 * {@see self::grant_caps_to_role()} is the repair, exposed to support and to
 * site owners as an action:
 *
 *     wp eval 'do_action( "betterdocs_grant_caps", "support" );'
 *
 * On a **Pro-active site** capability assignment belongs to Pro's Settings →
 * Roles screen (ADR-033): Pro's `Core\Roles::saved_settings()` grants the
 * bundle to every role listed in `article_roles`, and `reconcile_role_caps()`
 * adds *and revokes* `edit_docs_settings` / `read_docs_analytics` /
 * `read_faq_builder` on every admin load. Granting capabilities directly there
 * would be undone on the next `admin_init` and would make the Settings screen
 * lie about who holds what, so the repair writes the **setting** instead and
 * lets Pro do the granting.
 *
 * Nothing here ever revokes.
 *
 * @since 4.9.0 grant_caps_to_role(), repair_new_roles(), bucket_for_role() and
 *              the `betterdocs_grant_caps` action.
 */
class Roles extends Base {
	/**
	 * Option holding the role slugs this site has already seen.
	 *
	 * Seeded (without granting) the first time {@see self::repair_new_roles()}
	 * runs, so an existing site is never changed retroactively; from then on
	 * anything absent from it is a role created after activation.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const KNOWN_ROLES_OPTION = 'betterdocs_known_roles';

	/**
	 * The capabilities BetterDocs Pro reconciles on every `admin_init`.
	 *
	 * Pro's `Core\Roles::reconcile_role_caps()` adds **and revokes** these three
	 * from `settings_roles` / `analytics_roles` / `faq_roles`, so writing them
	 * onto a role directly is undone on the next admin page load. The `edit_docs`
	 * bundle is not reconciled — Pro only grants it, on save — which is what
	 * makes {@see self::grant_caps_to_role()}'s top-up safe.
	 *
	 * @since 4.9.0
	 *
	 * @var string[]
	 */
	const PRO_RECONCILED_CAPS = [ 'edit_docs_settings', 'read_docs_analytics', 'read_faq_builder' ];

	/**
	 * Summary of Database
	 * @var Database
	 */
	public $database;

	public function __construct( Database $database ) {
		$this->database = $database;

		$this->assign_admin_capabilities(); //will run when it is called

		/**
		 * Grant one role its default BetterDocs capabilities.
		 *
		 * The supported repair path for a role created after activation, and
		 * the one line support can hand a site owner:
		 * `wp eval 'do_action( "betterdocs_grant_caps", "support" );'`
		 *
		 * @since 4.9.0
		 *
		 * @param string $role Role slug.
		 */
		add_action( 'betterdocs_grant_caps', [ $this, 'grant_caps_to_role' ] );

		add_action( 'admin_init', [ $this, 'repair_new_roles' ] );
	}

	/**
	 * Ensure the administrator role always has every BetterDocs capability.
	 *
	 * Capabilities are normally granted on activation via Install::activate() ->
	 * setup(). But when BetterDocs is installed/activated silently by another
	 * plugin (e.g. Essential Addons from its Integrations screen, or BetterDocs
	 * Pro), the activation hook does not fire, so setup() never runs and the
	 * administrator never receives caps like edit_docs, edit_docs_settings or
	 * read_docs_analytics. That hides most of the BetterDocs admin menu (leaving
	 * only Quick Setup, gated by the core `delete_users` cap, and FAQ Builder)
	 * and makes the pages return "Sorry, you are not allowed to access this page."
	 *
	 * This self-heals that on every request: any missing admin cap is added back.
	 * add_cap() is only called for caps the role lacks, so once healed there are
	 * no further DB writes.
	 *
	 * @return void
	 */
	public function assign_admin_capabilities() {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$capabilities = $this->defaults_capabilities();
		$admin_caps   = isset( $capabilities['administrator'] ) ? $capabilities['administrator'] : [];

		if ( empty( $admin_caps ) ) {
			return;
		}

		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			return;
		}

		foreach ( $admin_caps as $cap ) {
			if ( ! $administrator->has_cap( $cap ) ) {
				$administrator->add_cap( $cap );
			}
		}
	}

	/**
	 * Assign FAQ Builder Capability To The Admin
	 *
	 * @deprecated Use assign_admin_capabilities() which grants the full admin
	 *             capability set (including read_faq_builder). Kept for backward
	 *             compatibility with any external callers.
	 */
	public function assgin_faq_builder_capability_to_admin() {
		if( current_user_can('administrator') && ! current_user_can('read_faq_builder') ) { // if the current user is admin, and current user does not have faq menu visibility option, then assign the faq menu visibility capability
			$current_user_role = get_role('administrator');
			$current_user_role->add_cap('read_faq_builder');
		}
	}

	/**
	 * Default Roles Capabilities
	 *
	 * @var array
	 */
	public function defaults_capabilities() {
		$default_capabilities = [
			'administrator' => [
				// post type related caps
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

				// doc_terms related caps
				'manage_doc_terms',
				'edit_doc_terms',
				'delete_doc_terms',

				// kb terms related caps
				'manage_knowledge_base_terms',
				'edit_knowledge_base_terms',
				'delete_knowledge_base_terms',

				// Settings and Analytics Related caps
				'edit_docs_settings',
				'read_docs_analytics',
				'read_faq_builder'
			],
			'editor'        => [
				// post type related caps
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

				// doc_terms related caps
				'manage_doc_terms',
				'edit_doc_terms',
				'delete_doc_terms',

				// kb terms related caps
				'manage_knowledge_base_terms',
				'edit_knowledge_base_terms',
				'delete_knowledge_base_terms'
			],
			'author'        => [
				'edit_docs',
				'edit_published_docs',
				'publish_docs',
				'delete_docs',
				'delete_published_docs'
			],
			'contributor'   => [
				'edit_docs',
				'delete_docs'
			],
			'other'         => [
				// post type related caps
				'edit_docs',
				'delete_docs'
			]
		];

		return apply_filters( 'betterdocs_default_caps', $default_capabilities );
	}

	/**
	 * The capability bucket a role slug belongs to.
	 *
	 * The five buckets are the keys of {@see self::defaults_capabilities()}
	 * (`administrator`, `editor`, `author`, `contributor`, `other`). A slug that
	 * is not itself a bucket — every role a site or another plugin created —
	 * falls to `other`, which is the deliberately small "can write and delete
	 * their own docs" set.
	 *
	 * Reads the filtered map, so a site that added a bucket through
	 * `betterdocs_default_caps` gets its own bucket back rather than `other`.
	 *
	 * @since 4.9.0
	 *
	 * @param string $role Role slug.
	 * @return string Bucket key.
	 */
	public function bucket_for_role( $role ) {
		$role         = sanitize_key( (string) $role );
		$capabilities = $this->defaults_capabilities();

		if ( '' !== $role && isset( $capabilities[ $role ] ) && 'other' !== $role ) {
			return $role;
		}

		return 'other';
	}

	/**
	 * Grant one role the capabilities of its bucket. Idempotent, never revokes.
	 *
	 * Two paths, because two different things own capability assignment:
	 *
	 * - **Free-only site** — adds the bucket's capabilities the role does not
	 *   already hold, straight onto the role (`WP_Roles::add_cap()`).
	 * - **Pro active** — adds the role to the `article_roles` setting (and to
	 *   `faq_roles` when the bucket is `editor`) through
	 *   `Settings::save_settings()`, which fires `betterdocs::settings::saved`
	 *   and lets Pro's `Core\Roles::saved_settings()` do the granting. Writing
	 *   the capabilities here instead would be reverted by Pro's
	 *   `reconcile_role_caps()` on the next `admin_init`, and Settings → Roles
	 *   would show a role holding nothing it lists. `settings_roles` and
	 *   `analytics_roles` are never touched: `edit_docs_settings` and
	 *   `read_docs_analytics` are the two capabilities a site owner most likely
	 *   meant to withhold. See ADR-033.
	 *
	 * Both paths do nothing at all when there is nothing to add, so the double
	 * registration on a Pro site (Free's `Roles` and Pro's subclass are separate
	 * container entries, and both constructors hook this action) costs one
	 * array comparison the second time round.
	 *
	 * The Pro path needs `edit_docs_settings` — `Settings::save_settings()`
	 * refuses otherwise — so run it as an administrator
	 * (`wp eval --user=1 '…'`). It returns an empty array and logs under
	 * `WP_DEBUG` when the save is refused.
	 *
	 * @since 4.9.0
	 *
	 * @param string      $role   Role slug. Must already exist.
	 * @param string|null $bucket Bucket to grant; defaults to the role's own.
	 * @return string[] The capabilities this call added, re-read from the role.
	 */
	public function grant_caps_to_role( $role, $bucket = null ) {
		$role = sanitize_key( (string) $role );

		if ( '' === $role || ! function_exists( 'wp_roles' ) ) {
			return [];
		}

		$roles = wp_roles();

		if ( ! is_object( $roles ) || ! method_exists( $roles, 'is_role' ) || ! $roles->is_role( $role ) ) {
			return [];
		}

		$bucket       = null === $bucket ? $this->bucket_for_role( $role ) : sanitize_key( (string) $bucket );
		$capabilities = $this->defaults_capabilities();
		$caps         = isset( $capabilities[ $bucket ] ) ? (array) $capabilities[ $bucket ] : (array) $capabilities['other'];

		if ( empty( $caps ) ) {
			return [];
		}

		$before = $this->role_caps( $role );

		if ( $this->pro_owns_capabilities() ) {
			$this->add_role_to_pro_settings( $role, $bucket );

			// Pro grants on save, so a role that was *already* listed gets
			// nothing from the line above — and a role can be listed while
			// holding nothing at all: Pro's `Install::init()` runs
			// `Roles::setup( true )` on the request after every Pro
			// (re)activation, which strips the bundle from every non-admin role
			// without touching the setting (measured 2026-08-23). Top the
			// listed role up here. Only the bundle: the three capabilities Pro
			// reconciles from its own settings are never written by hand.
			if ( $this->listed_in_pro_settings( $role ) ) {
				$held = $this->role_caps( $role );

				foreach ( array_diff( $caps, self::PRO_RECONCILED_CAPS ) as $cap ) {
					if ( ! in_array( $cap, $held, true ) ) {
						$roles->add_cap( $role, $cap );
					}
				}
			}
		} else {
			foreach ( $caps as $cap ) {
				if ( ! in_array( $cap, $before, true ) ) {
					$roles->add_cap( $role, $cap );
				}
			}
		}

		return array_values( array_intersect( $caps, array_diff( $this->role_caps( $role ), $before ) ) );
	}

	/**
	 * Grant the default bucket to every role this site has not seen before.
	 *
	 * Runs on `admin_init`. **Free-only sites only** — on a Pro site Settings →
	 * Roles is the source of truth and a new role is simply one more box to
	 * tick, so this returns immediately rather than quietly widening a list the
	 * site owner curates (ADR-033).
	 *
	 * The first run **seeds and grants nothing**: an existing site's roles were
	 * left as they are on purpose, and retroactively granting `edit_docs` to
	 * every custom role on upgrade would be a surprise. Only roles that appear
	 * after that first sweep are repaired.
	 *
	 * @since 4.9.0
	 *
	 * @return array<string,string[]> Role slug => capabilities granted.
	 */
	public function repair_new_roles() {
		if ( ! function_exists( 'wp_roles' ) || ! function_exists( 'betterdocs' ) ) {
			return [];
		}

		if ( betterdocs()->is_pro_active() ) {
			return [];
		}

		$roles = wp_roles();

		if ( ! is_object( $roles ) || ! method_exists( $roles, 'get_names' ) ) {
			return [];
		}

		$current = array_map( 'strval', array_keys( (array) $roles->get_names() ) );
		$known   = $this->database->get( self::KNOWN_ROLES_OPTION, false );

		// First run: remember what is here, change nothing.
		if ( ! is_array( $known ) ) {
			$this->database->save( self::KNOWN_ROLES_OPTION, $current );

			return [];
		}

		$known   = array_map( 'strval', $known );
		$granted = [];

		foreach ( array_diff( $current, $known ) as $role ) {
			$caps = $this->grant_caps_to_role( $role );

			if ( ! empty( $caps ) ) {
				$granted[ $role ] = $caps;
			}
		}

		if ( array_values( $current ) !== array_values( $known ) ) {
			$this->database->save( self::KNOWN_ROLES_OPTION, $current );
		}

		return $granted;
	}

	/**
	 * The capabilities a role currently holds, as a flat list of slugs.
	 *
	 * Reads `WP_Roles::$roles`, **not** `get_role()`. `WP_Roles::add_cap()`
	 * writes to `$roles` and to the `wp_user_roles` option but leaves the
	 * already-built `WP_Role` objects alone, so a `get_role()` taken after a
	 * grant in the same request still reports the capabilities the role had
	 * before it — measured on WordPress 7.1. `WP_Role::add_cap()`, which is what
	 * Pro uses, updates both, so this is correct for either path.
	 *
	 * @since 4.9.0
	 *
	 * @param string $role Role slug.
	 * @return string[]
	 */
	protected function role_caps( $role ) {
		$roles = function_exists( 'wp_roles' ) ? wp_roles() : null;

		if ( is_object( $roles ) && isset( $roles->roles[ $role ]['capabilities'] ) && is_array( $roles->roles[ $role ]['capabilities'] ) ) {
			return array_keys( array_filter( $roles->roles[ $role ]['capabilities'] ) );
		}

		$role_object = get_role( $role );

		if ( ! is_object( $role_object ) || ! isset( $role_object->capabilities ) || ! is_array( $role_object->capabilities ) ) {
			return [];
		}

		return array_keys( array_filter( $role_object->capabilities ) );
	}

	/**
	 * Whether BetterDocs Pro is the one assigning capabilities on this site.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	protected function pro_owns_capabilities() {
		return function_exists( 'betterdocs' ) && betterdocs()->is_pro_active();
	}

	/**
	 * Whether Pro's `article_roles` setting lists this role.
	 *
	 * @since 4.9.0
	 *
	 * @param string $role Role slug.
	 * @return bool
	 */
	protected function listed_in_pro_settings( $role ) {
		$settings = betterdocs()->settings;

		if ( ! is_object( $settings ) || ! method_exists( $settings, 'get' ) ) {
			return false;
		}

		$listed = (array) $settings->get( 'article_roles', [ 'administrator' ] );

		return in_array( (string) $role, array_map( 'strval', $listed ), true );
	}

	/**
	 * Add a role to Pro's role settings, so Pro grants it the bundle.
	 *
	 * Writes nothing when the role is already listed, which is what makes
	 * {@see self::grant_caps_to_role()} idempotent on a Pro site.
	 *
	 * @since 4.9.0
	 *
	 * @param string $role   Role slug.
	 * @param string $bucket Bucket being granted.
	 * @return bool Whether a save was attempted and succeeded.
	 */
	protected function add_role_to_pro_settings( $role, $bucket ) {
		$settings = betterdocs()->settings;

		if ( ! is_object( $settings ) || ! method_exists( $settings, 'save_settings' ) ) {
			return false;
		}

		$payload = [];
		$keys    = [ 'article_roles' ];

		// `read_faq_builder` is not in the `editor` bucket, so the Free path
		// never grants it; Pro keeps FAQ Builder access in its own setting and
		// the rig's own baseline lists `editor` there. Only the editor bucket
		// asks for it.
		if ( 'editor' === $bucket ) {
			$keys[] = 'faq_roles';
		}

		foreach ( $keys as $key ) {
			$listed = (array) $settings->get( $key, [ 'administrator' ] );
			$listed = array_values( array_unique( array_map( 'strval', $listed ) ) );

			if ( ! in_array( $role, $listed, true ) ) {
				$listed[]        = $role;
				$payload[ $key ] = $listed;
			}
		}

		if ( empty( $payload ) ) {
			return false;
		}

		$saved = $settings->save_settings( $payload );

		if ( is_wp_error( $saved ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
				error_log(
					sprintf(
						'[BD-MCP] Could not add the "%1$s" role to BetterDocs Pro\'s role settings: %2$s',
						$role,
						$saved->get_error_message()
					)
				);
			}

			return false;
		}

		return (bool) $saved;
	}

	public function setup( $remove = false ) {
		if ( $this->database->get( '_betterdocs_caps_initialized', false ) && ! $remove ) {
			return;
		}

		global $wp_roles;

		$capabilities = $this->defaults_capabilities();

		if ( $remove ) {
			unset( $capabilities['administrator'] );
		}

		foreach ( $capabilities as $role => $caps ) {
			foreach ( $caps as $cap ) {
				if ( $remove ) {
					$wp_roles->remove_cap( $role, $cap );
					continue;
				}

				$wp_roles->add_cap( $role, $cap );
			}
		}

		$this->database->save( '_betterdocs_caps_initialized', ! $remove );
	}
}
