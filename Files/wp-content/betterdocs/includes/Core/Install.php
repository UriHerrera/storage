<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class Install extends Base {
	/**
	 * Container
	 * @var Container
	 */
	public $container;
	/**
	 * Database
	 * @var Database
	 */
	public $database;

	public function __construct( Container $container ) {
		$this->container = $container;
		$this->database  = $this->container->get( Database::class );

		register_activation_hook( BETTERDOCS_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( BETTERDOCS_PLUGIN_FILE, [ $this, 'deactivate' ] );

		//Update message for showing notice for new release
		add_action( 'in_plugin_update_message-betterdocs/betterdocs.php', [ $this, 'plugin_update_message' ], 10, 2 );
		add_action( 'init', [ $this, 'check_db_updates' ], 1 );
		add_action( 'init', [ $this, 'check_version' ], 5 );
	}

	/**
	 * This is for upgrade message.
	 *
	 * @param mixed $plugin_data
	 * @param mixed $response
	 * @return void
	 */
	public function plugin_update_message( $plugin_data, $response ) {
		if ( isset( $response->upgrade_notice ) ) {
			$new_version                = $plugin_data['new_version'];
			$current_version            = betterdocs()->version;
			$current_version_minor_part = explode( '.', $current_version )[1];
			$new_version_minor_part     = explode( '.', $new_version )[1];

			betterdocs()->views->get(
				'admin/notices/upgrade',
				[
					'response'    => $response,
					'plugin_data' => $plugin_data,
					'major'       => ! ( $current_version_minor_part === $new_version_minor_part )
				]
			);
		}
	}

	/**
	 * This method will run when version is updated.
	 * @return void
	 */
	public function check_version() {
		$betterdocs_version      = get_option( 'betterdocs_version', '2.3.6' );
		$betterdocs_code_version = betterdocs()->version;
		$requires_update         = version_compare( $betterdocs_version, $betterdocs_code_version, '<' );

		if ( $requires_update ) {
			/**
			 * This block of codes will execute
			 * if this plugin is activated via PRO PLUGIN.
			 */
			if ( $this->database->get_transient( 'maybe_betterdocs_installed_by_pro' ) ) {
				$this->activate();

				$this->database->delete_transient( 'maybe_betterdocs_installed_by_pro' );
			}

			// Re-check if any setup needed.
			$this->check_db_updates();

			if ( get_option( 'betterdocs_version' ) && str_replace( '.', '', $betterdocs_code_version ) >= 370 ) {
				$this->migrate_customizer();
			}

			// Re-check if any migration is needed.
			$this->container->get( Migration::class )->init( str_replace( '.', '', $betterdocs_code_version ) );

			// Updated current version number of plugin.
			$this->update_version();
		}
	}

	/**
	 * Update BetterDocs version to current.
	 */
	private function update_version() {
		update_option( 'betterdocs_version', betterdocs()->version );
	}

	/**
	 * This method will run when activation hit
	 * @return void
	 */
	public function activate() {
		// Create DB Tables if not created.
		$this->check_db_updates();

		// Set admin roles
		$this->container->get( Roles::class )->setup();

		// Save default settings.
		// $this->container->get( Settings::class )->save_default_settings();

		// Set redirect transient
		if ( current_user_can( 'delete_users' ) ) {
			$this->database->set_transient( 'betterdocs_maybe_redirect', true );
		}

		$this->database->set_transient( 'betterdocs_flush_rewrite_rules', true );
	}

	/**
	 * This method will run when deactivation hit.
	 * @return void
	 */
	public function deactivate() {
		// Flush all the existing rewrite rules
		flush_rewrite_rules();

		// Reset admin roles
		$this->container->get( Roles::class )->setup( true );
	}

	/**
	 * This method responsible for setup db tables for the plugin
	 * @return void
	 */
	public function check_db_updates() {
		$_db_version      = get_option( 'betterdocs_db_version', '1.0' );
		$_db_code_version = betterdocs()->db_version;
		$requires_update  = version_compare( $_db_version, $_db_code_version, '<' );

		if ( ! $requires_update ) {
			return;
		}

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// keyword_hash exists purely to make the lookup indexable: `keyword` is
		// TEXT and is matched with BINARY (for collation safety), which no index
		// can serve — so every front-end search used to full-scan this table.
		// The hash narrows to a single row via the index; the BINARY comparison
		// still decides the match, so behaviour is unchanged.
		$search_keyword_table = $wpdb->prefix . 'betterdocs_search_keyword';
		$search_keyword       = "CREATE TABLE $search_keyword_table (
            id bigint NOT NULL AUTO_INCREMENT,
            keyword text NOT NULL,
            keyword_hash char(32) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY keyword_hash (keyword_hash)
        ) {$charset_collate};";

		$search_log_table = $wpdb->prefix . 'betterdocs_search_log';
		$search_log       = "CREATE TABLE $search_log_table (
            id bigint NOT NULL AUTO_INCREMENT,
            keyword_id bigint NOT NULL,
            count mediumint(9) NULL,
            not_found_count mediumint(9) NULL,
            created_at date DEFAULT '0000-00-00' NOT NULL,
            PRIMARY KEY (id),
            KEY keyword_id (keyword_id),
            KEY created_at (created_at),
            KEY keyword_created (keyword_id, created_at)
        ) {$charset_collate};";

		$_analytics_table = $wpdb->prefix . 'betterdocs_analytics';
		$analytics_table  = "CREATE TABLE $_analytics_table (
            id bigint NOT NULL AUTO_INCREMENT,
            post_id bigint DEFAULT 0 NOT NULL,
            impressions bigint DEFAULT 0 NOT NULL,
            unique_visit bigint DEFAULT 0 NOT NULL,
            happy bigint DEFAULT 0 NOT NULL,
            sad bigint DEFAULT 0 NOT NULL,
            normal bigint DEFAULT 0 NOT NULL,
            created_at date DEFAULT '0000-00-00' NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY impressions (impressions),
            KEY unique_visit (unique_visit),
            KEY happy (happy),
            KEY sad (sad),
            KEY normal (normal),
            KEY created_at (created_at),
            UNIQUE KEY post_created (post_id, created_at)
        ) {$charset_collate};";

		// The betterdocs_api_specs table moved to Pro (API Documentation is a
		// Pro-only feature; Pro's Install creates it).

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $search_keyword . $search_log . $analytics_table );

		// Update DB Version
		update_option( 'betterdocs_db_version', $_db_code_version );
	}

	public function migrate_customizer() {
		/**
		 * Seed the layout Customizer settings for installs that never explicitly
		 * picked a layout. Some consumers read the raw theme_mod without a fallback
		 * (e.g. FrontEnd::article_reactions()), so an unseeded mod can render
		 * differently from the layout the front-end otherwise defaults to.
		 *
		 * Two rules keep this safe and correct:
		 *   1. Only seed a mod that has never been set ( get_theme_mod() === false ).
		 *      An explicit choice — including an explicit 'layout-1' — is never
		 *      overwritten, so existing sites cannot change layout on update.
		 *   2. Pull the value from Customizer\Defaults (the single source of truth
		 *      the front-end already renders from) instead of a hardcoded string, so
		 *      it can never drift from the real default again. Pro defaults are merged
		 *      in automatically when BetterDocs Pro is active.
		 */
		$defaults    = betterdocs()->customizer->defaults->defaults();
		$layout_mods = [
			'betterdocs_search_layout_select',
			'betterdocs_docs_layout_select',
			'betterdocs_single_layout_select',
			'betterdocs_archive_layout_select',
			'betterdocs_select_faq_template',
			'betterdocs_multikb_layout_select',   // BetterDocs Pro.
			'betterdocs_select_faq_template_mkb', // BetterDocs Pro.
		];

		foreach ( $layout_mods as $mod ) {
			// Respect an explicit user choice (including an explicit 'layout-1').
			if ( get_theme_mod( $mod, false ) !== false ) {
				continue;
			}

			// Only seed when a real default is registered (skips inactive add-ons).
			if ( empty( $defaults[ $mod ] ) ) {
				continue;
			}

			set_theme_mod( $mod, $defaults[ $mod ] );
		}

		$search_heading = get_theme_mod( 'betterdocs_live_search_heading_switch' );
		if ( empty( $search_heading ) ) {
			set_theme_mod( 'betterdocs_live_search_heading_switch', false );
		}

		if ( get_option( 'betterdocs_settings' ) && betterdocs()->settings->get( 'enable_estimated_reading_time' ) == false ) {
			betterdocs()->settings->save( 'enable_estimated_reading_time', false );
		}
	}
}
