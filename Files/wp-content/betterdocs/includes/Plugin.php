<?php

namespace WPDeveloper\BetterDocs;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Admin\Analytics;
use WPDeveloper\BetterDocs\Admin\Customizer\Customizer;
use WPDeveloper\BetterDocs\Admin\HelpScoutMigration;
use WPDeveloper\BetterDocs\Admin\ReportEmail;
use WPDeveloper\BetterDocs\Core\Admin;
use WPDeveloper\BetterDocs\Core\AnalyticsTracker;
use WPDeveloper\BetterDocs\Core\AnalyticsRetention;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Core\Install;
use WPDeveloper\BetterDocs\Core\KBMigration;
use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Core\Request;
use WPDeveloper\BetterDocs\Core\Rewrite;
use WPDeveloper\BetterDocs\Core\Roles;
use WPDeveloper\BetterDocs\Core\Scripts;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\ShortcodeFactory;
use WPDeveloper\BetterDocs\Core\WriteWithAI;
use WPDeveloper\BetterDocs\Core\ArticleSummary;
use WPDeveloper\BetterDocs\Core\ArticleQualityScore;
use WPDeveloper\BetterDocs\Core\UnifiedMetabox;
use WPDeveloper\BetterDocs\Core\DocsAISuite;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;
use WPDeveloper\BetterDocs\Dependencies\DI\ContainerBuilder;
use WPDeveloper\BetterDocs\Editors\Editor;
use WPDeveloper\BetterDocs\FrontEnd\FrontEnd;
use WPDeveloper\BetterDocs\FrontEnd\PrintTemplate;
use WPDeveloper\BetterDocs\FrontEnd\SearchExtender;
use WPDeveloper\BetterDocs\FrontEnd\TemplateTags;
use WPDeveloper\BetterDocs\FrontEnd\WooProductFAQ;
use WPDeveloper\BetterDocs\Abilities\AbilitiesRegistrar;
use WPDeveloper\BetterDocs\Mcp\MCPManager;
use WPDeveloper\BetterDocs\Modules\StyleHandler as ModulesStyleHandler;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Utils\Enqueue;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Utils\Views;

final class Plugin {
    private static $_instance = null;
    /**
     * Assets manager
     *
     * @var Enqueue
     */
    public $assets;
    /**
     * View manager
     *
     * @var Views
     */
    public $views;
    /**
     * Container Manager
     *
     * @var Container
     */
    public $container;
    /**
     * Editor Manager
     * @var Editor
     */
    public $editor;
    /**
     * Helper class
     * @var Helper
     */
    public $helper;
    /**
     * KBMigration class
     * @var KBMigration
     */
    public $kbmigration;
    /**
     * KBMigration class
     * @var Admin
     */
    public $admin;
    /**
     * Helper class
     * @var Database
     */
    public $database;
    /**
     * Helper class
     * @var Settings
     */
    public $settings;
    /**
     * Helper class
     * @var TemplateTags
     */
    public $template_helper;
    /**
     * Article Summary class
     * @var ArticleSummary
     */
    public $article_summary;
    /**
     * Customizer class
     * @var Customizer
     */
    public $customizer;
    /**
     * Query class
     * @var Query
     */
    public $query;
    /**
     * Rewrite Class
     * @var Rewrite
     */
    public $rewrite;
    /**
     * Request Class
     * @var Request
     */
    public $request;
    /**
     * Analytics Class
     * @var Analytics
     */
    public $analytics;
    /**
     * Plugin Version
     * @var string
     */
    public $version = '4.9.3';

    /**
     * WriteWithAI Class
     * @var string
     */
    public $ai_autowrtie;
    public $backgroundProccessor;

    /**
     * ArticleQualityScore Class
     * @var ArticleQualityScore
     */
    public $article_quality_score;

    /**
     * Plugin DB Version
     * @var string
     */
    public $db_version = '1.0.3';

    public function __construct() {
        $this->define_constants();

        do_action( 'betterdocs_init_before' );

        $this->setup_container();
        /**
         * Register activation and deactivation hooks
         * and version updates check
         */
        $this->container->get( Install::class );

        /**
         * Abilities API registrar.
         *
         * Resolved here, at plugin load, and not from initialize(): both the
         * bundled Abilities API and WordPress core's build their registry as a
         * lazy singleton and fire `wp_abilities_api_init` from it, at or after
         * `init`, the first time anything reads the registry. Our listeners
         * therefore have to be attached before that first read, whenever it
         * happens — and a container resolve on `init` would already be too late
         * for a plugin that reads the registry earlier in the same hook.
         */
        $this->container->get( AbilitiesRegistrar::class );

        add_action( 'init', array( $this, 'initialize' ), 0 );

        /**
         * Initialize API
         */
        add_action( 'rest_api_init', array( $this, 'api_initialization' ) );

        /**
         * For admin only
         */
        add_action( 'admin_init', array( $this, 'admin_init' ), 0 );

        /**
         * For AJAX only
         */
        $this->ajax();

        /**
         * Style Handler For Parsing and Saving Styles as file.
         */
        ModulesStyleHandler::init();
    }

    private function define_constants() {
        $this->define( 'BETTERDOCS_VERSION', $this->version );
        $this->define( 'BETTERDOCS_DB_VERSION', $this->db_version );
        $this->define( 'BETTERDOCS_ABSPATH', dirname( BETTERDOCS_PLUGIN_FILE ) . '/' );
        $this->define( 'BETTERDOCS_ABSURL', plugin_dir_url( BETTERDOCS_PLUGIN_FILE ) );
        $this->define( 'BETTERDOCS_PLUGIN_BASENAME', plugin_basename( BETTERDOCS_PLUGIN_FILE ) );
        // Compiled block metadata lives under assets/build/ since the Node 24 asset
        // restructure; register_block_type() fails silently if this path is wrong.
        $this->define( 'BETTERDOCS_BLOCKS_DIRECTORY', BETTERDOCS_ABSPATH . 'assets/build/blocks/' );
        $this->define( 'BETTERDOCS_ROOT_DIR_PATH', plugin_dir_path( BETTERDOCS_PLUGIN_FILE ) );
        $this->define( 'BETTERDOCS_FSE_TEMPLATES_PATH', BETTERDOCS_ROOT_DIR_PATH . 'views/templates/fse' );

        /**
         * Third Party Constants
         * @since 2.5.0
         *
         * WPML compatibility with Polylang
         */
        if ( Helper::is_plugin_active( 'polylang/polylang.php' ) ) {
            // Polylang's documented integration constant — must use the upstream-defined name.
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
            define( 'PLL_WPML_COMPAT', false );
        }
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            // Caller passes plugin-prefixed names (BETTERDOCS_*); $name comes from a controlled internal allowlist.
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound
            define( $name, $value );
        }
    }

    public function setup_container() {
        $config_array = require_once BETTERDOCS_ABSPATH . 'includes/config.php';
        $config       = apply_filters( 'betterdocs_container_config', $config_array );
        $builder      = new ContainerBuilder();

        $builder->addDefinitions( $config );
        $this->container = $builder->build();
    }

    public function initialize() {

        /**
         * Setup localization.
         */
        $this->load_plugin_textdomain();

        $this->container->get( Scripts::class );
        $this->rewrite = $this->container->get( Rewrite::class );
        $this->request = $this->container->get( Request::class );
        $this->query   = $this->container->get( Query::class );

        // Initialize background process
        $this->backgroundProccessor = $this->container->get( HelpScoutMigration::class );

        $this->rewrite->init();
        $this->request->init();

        $this->assets      = $this->container->get( Enqueue::class );
        $this->views       = $this->container->get( Views::class );
        $this->helper      = $this->container->get( Helper::class );
        $this->kbmigration = $this->container->get( KBMigration::class );
        $this->admin       = $this->container->get( Admin::class );
        $this->database    = $this->container->get( Database::class );
        $this->settings    = $this->container->get( Settings::class );
        $this->analytics   = $this->container->get( Analytics::class );
        // Free analytics collectors: the frontend view/scroll tracker and the
        // daily retention purge. Each self-registers its hooks on construct.
        $this->container->get( AnalyticsTracker::class );
        $this->container->get( AnalyticsRetention::class );

        $this->template_helper = $this->container->get( TemplateTags::class );
        $this->customizer      = $this->container->get( Customizer::class );
        $this->editor          = $this->container->get( Editor::class );
        $this->ai_autowrtie    = $this->container->get( WriteWithAI::class );
        $this->article_summary = $this->container->get( ArticleSummary::class );

        // Initialize unified metabox before individual features
        $this->container->get( UnifiedMetabox::class );
        $this->article_quality_score = $this->container->get( ArticleQualityScore::class );

        // Editor "Suggest Categories / Tags / Glossaries" AI actions on the docs
        // post type (gated by enable_docs_ai_suite + an OpenAI key inside the class).
        $this->container->get( DocsAISuite::class );

        $this->container->get( Admin::class );
        $this->container->get( Roles::class );

        /**
         * MCP transport: rewrite rules, the pretty endpoint, OAuth discovery
         * and every REST route. Resolved here rather than at plugin load
         * (where the abilities registrar has to be) because its earliest hook
         * is `init`, which is what this method already runs on.
         */
        $this->container->get( MCPManager::class );

        $this->container->get( ReportEmail::class );
        // Usage-analytics collector. Registered here (runs on every request, incl.
        // WP-Cron) rather than in Admin so its `betterdocs_insights_data` filter
        // callback is present whenever the tracking payload is built.
        $this->container->get( \WPDeveloper\BetterDocs\Insights\Collector::class );
        /**
         * Initialize Shortcode
         * Make sure you have listed out all shortcode in shortcode factory.
         */
        $this->container->get( ShortcodeFactory::class )->init();

        $this->container->get( FrontEnd::class );
        $this->container->get( SearchExtender::class );

        // Running logo header / page footer for the browser's own Print command.
        // Registered unconditionally: it covers every front-end page, including
        // the ones that carry no BetterDocs print button.
        $this->container->get( PrintTemplate::class );

        /**
         * Single-product FAQ rendering (WooCommerce). The class itself bails when
         * WooCommerce is inactive or the feature is disabled.
         */
        $this->container->get( WooProductFAQ::class );

        do_action( 'betterdocs_init' );

        $this->editor->init();
    }

    /**
     * Load plugins textdomain `betterdocs` into actions.
     * @return void
     */
    public function load_plugin_textdomain( $textdomain = 'betterdocs', $plugin_file = BETTERDOCS_PLUGIN_FILE ) {
        $locale = determine_locale();

        /**
         * Filter to adjust the BetterDocs locale to use for translations.
         */
        // 'plugin_locale' is a WP-core filter; using its documented name is required.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $locale = apply_filters( 'plugin_locale', $locale, $textdomain );

        if ( file_exists( WP_LANG_DIR . "/$textdomain-" . $locale . '.mo' ) ) {
            unload_textdomain( $textdomain );
            load_textdomain( $textdomain, WP_LANG_DIR . "/$textdomain-" . $locale . '.mo' );
        }
    }

    /**
     * For AJAX Only
     * @return void
     */
    public function ajax() {
    }

    /**
     * Create a plugin instance.
     *
     * @param mixed ...$args
     *
     * @return static
     *
     * @suppress PHP0441
     * @since 2.5.0
     */
    public static function get_instance() {
        if ( null == self::$_instance ) {
            self::$_instance = new self();

            do_action( 'betterdocs_loaded' );
        }

        return self::$_instance;
    }

    /**
     * Hooked with `admin_init` action.
     * @return void
     */
    public function admin_init() {
        /**
         * Maybe Redirect
         * for setup related settings.
         */
        $this->maybe_redirect();
    }

    /**
     * Summary of maybe_redirect
     * @return void
     */
    public function maybe_redirect() {
        // Bail if no activation transient is set.
        if ( ! $this->database->get_transient( 'betterdocs_maybe_redirect' ) ) {
            return;
        }

        // Delete the activation transient.
        $this->database->delete_transient( 'betterdocs_maybe_redirect' );

        if ( ! is_multisite() ) {
            $betterdocs_settings = get_option( 'betterdocs_settings' );
            if ( $betterdocs_settings ) {
                wp_safe_redirect( add_query_arg( array( 'page' => 'betterdocs-settings' ), admin_url( 'admin.php' ) ) );
            } else {
                wp_safe_redirect( add_query_arg( array( 'page' => 'betterdocs-setup' ), admin_url( 'admin.php' ) ) );
            }
            // This runs at `admin_init` priority 0. Without exiting, the rest of
            // admin_init still executes and can mutate the very state the redirect
            // target depends on before the browser ever follows the Location header.
            exit;
        }
    }

    /**
     * Is Pro Plugin Is Installed?
     * @return bool
     */
    public function is_pro_installed() {
        return $this->helper->get_plugins( 'betterdocs-pro/betterdocs-pro.php' );
    }

    /**
     * Is Pro Plugin Is Active?
     * @return bool
     */
    public function pro_file() {
        return WP_PLUGIN_DIR . '/betterdocs-pro/betterdocs-pro.php';
    }

    public function is_pro_active() {
        if ( file_exists( $this->pro_file() ) ) {
            return $this->helper->is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' );
        }

        return false;
    }

    public function chatbot_file() {
        return WP_PLUGIN_DIR . '/betterdocs-ai-chatbot/betterdocs-ai-chatbot.php';
    }

    public function is_chatbot_active() {
        if ( file_exists( $this->chatbot_file() ) ) {
            return $this->helper->is_plugin_active( 'betterdocs-ai-chatbot/betterdocs-ai-chatbot.php' );
        }

        return false;
    }

    /**
     * Whether Pro provides the real API Documentation screen.
     *
     * Pro flips this via `betterdocs_pro_has_api_docs`; the class_exists() default
     * keeps the gate correct against a Pro build that predates that filter.
     *
     * @return bool
     */
    public function has_api_docs() {
        return (bool) apply_filters(
            'betterdocs_pro_has_api_docs',
            class_exists( '\\WPDeveloper\\BetterDocsPro\\Core\\ApiReferences' )
        );
    }

    /**
     * Whether Free should show its locked API Docs teaser.
     *
     * Only without Pro. An older Pro has already paid, so they get nothing here —
     * they need a plugin update, not an upsell.
     *
     * @return bool
     */
    public function show_api_docs_teaser() {
        return ! $this->is_pro_active() && ! $this->has_api_docs();
    }

    /**
     * Whether the real Glossaries screen is available (i.e. Pro is providing it).
     *
     * Glossaries now lives in Pro (moved out of Free), so — like Content
     * Intelligence and API Docs — there is a concrete Pro class to detect. The
     * class_exists() default keeps the gate correct against a Pro build that
     * predates the move (that Pro is active but does NOT ship the manager, so
     * this must be false, not simply `is_pro_active()`); the filter lets Pro/add-ons
     * flip it explicitly.
     *
     * @return bool
     */
    public function has_glossaries() {
        return (bool) apply_filters(
            'betterdocs_pro_has_glossaries',
            class_exists( '\\WPDeveloper\\BetterDocsPro\\Core\\GlossaryTaxonomy' )
        );
    }

    /**
     * Whether Free should render its locked Glossaries screen instead of the real
     * manager.
     *
     * True whenever the Pro manager is not available — i.e. Pro is off (a genuine
     * upsell) OR an older Pro that predates the glossaries move is active (it can no
     * longer provide the manager, so the slot must not be left blank). Only a Pro new
     * enough to ship GlossaryTaxonomy hides this and takes over the route.
     *
     * @return bool
     */
    public function show_glossary_teaser() {
        return ! $this->has_glossaries();
    }

    /**
     * Whether the locked Glossaries screen is showing because an *outdated* Pro
     * is active (as opposed to no Pro at all).
     *
     * Glossaries moved into Pro, so a Pro that predates the move is active but no
     * longer provides the manager. That user already owns Pro, so the locked
     * screen must ask them to UPDATE Pro rather than upsell "get Pro". Mirrors the
     * has_glossaries() capability check.
     *
     * @return bool
     */
    public function glossaries_needs_pro_update() {
        return $this->is_pro_active() && ! $this->has_glossaries();
    }

    /**
     * The BetterDocs Pro version that first ships the Glossaries manager, shown in
     * the "please update Pro" copy. Filterable so the target can move with Pro.
     *
     * @return string
     */
    public function glossaries_min_pro_version() {
        return (string) apply_filters( 'betterdocs_glossaries_min_pro_version', '4.3.1' );
    }

    /**
     * Whether Pro provides the real Content Intelligence screen.
     *
     * Pro flips this via `betterdocs_pro_has_content_intelligence`; the
     * class_exists() default keeps the gate correct against a Pro build that
     * predates that filter.
     *
     * @return bool
     */
    public function has_content_intelligence() {
        return (bool) apply_filters(
            'betterdocs_pro_has_content_intelligence',
            class_exists( '\\WPDeveloper\\BetterDocsPro\\Core\\ContentIntelligenceService' )
        );
    }

    /**
     * Whether Free should show its locked Content Intelligence teaser.
     *
     * Only without Pro. An older Pro has already paid, so they get nothing here —
     * they need a plugin update, not an upsell.
     *
     * @return bool
     */
    public function show_content_intelligence_teaser() {
        return ! $this->is_pro_active() && ! $this->has_content_intelligence();
    }

    public function pro_version() {
        if ( ! $this->is_pro_active() ) {
            return false;
        }

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data( $this->pro_file() );

        return $plugin_data[ 'Version' ];
    }

    /**
     * Get all the API initialized.
     * @return void
     */
    public function api_initialization() {
        $_api_classes = scandir( __DIR__ . DIRECTORY_SEPARATOR . 'REST' );

        if ( ! empty( $_api_classes ) && is_array( $_api_classes ) ) {
            foreach ( $_api_classes as $class ) {
                if ( '.' == $class || '..' == $class || strpos( $class, '.' ) === 0 ) {
                    continue;
                }

                $classname  = basename( $class, '.php' );
                $classname  = '\\' . __NAMESPACE__ . "\\REST\\$classname";
                $_api_class = $this->container->get( $classname );

                if ( $_api_class instanceof BaseAPI ) {
                    $_api_class->register();
                }
            }
        }
    }

    public function is_betterdocs_screen( $hook, $admin_check = true ): bool {
        /**
         * Filter the list of admin screen hook suffixes treated as BetterDocs
         * screens (controls whether the React admin bundle + styles load).
         * Pro/add-ons can register their own React pages here, e.g. the
         * Knowledge Base admin page.
         *
         * @param string[] $screens Full hook suffixes (e.g. betterdocs_page_betterdocs-foo).
         */
        $screens = apply_filters( 'betterdocs_admin_screens', array(
            'toplevel_page_betterdocs-dashboard',
            'toplevel_page_betterdocs-admin',
            'admin_page_betterdocs-admin',
            'betterdocs_page_betterdocs-admin',
            'betterdocs_page_betterdocs-analytics',
            'betterdocs_page_betterdocs-content-iq',
            'betterdocs_page_betterdocs-settings',
            'betterdocs_page_betterdocs-mcp',
            'betterdocs_page_betterdocs-faq',
            'betterdocs_page_betterdocs-glossaries',
            'betterdocs_page_betterdocs-ai-chatbot',
            'betterdocs_page_betterdocs-api-docs',
            'betterdocs_page_betterdocs-doc-categories',
            'betterdocs_page_betterdocs-doc-tags',
        ) );

        if ( $admin_check ) {
            if ( in_array( $hook, $screens ) ) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function get_betterdocs_screen() {
        $registered_screens = array(
            'toplevel_page_betterdocs-dashboard',
            'admin_page_betterdocs-admin',
            'betterdocs_page_betterdocs-admin',
            'betterdocs_page_betterdocs-settings',
            'betterdocs_page_betterdocs-mcp',
            'betterdocs_page_betterdocs-analytics',
            'betterdocs_page_betterdocs-faq',
            'betterdocs_page_betterdocs-glossaries',
            'betterdocs_page_betterdocs-ai-chatbot',
            'betterdocs_page_betterdocs-api-docs',
            'betterdocs_page_betterdocs-doc-categories',
            'betterdocs_page_betterdocs-doc-tags',
            'edit-docs'
        );

        $current_screen_id = get_current_screen() != null ? get_current_screen()->id : '';

        if ( in_array( $current_screen_id, $registered_screens ) ) {
            return $current_screen_id;
        }

        return false;
    }
}
