<?php

namespace WPDeveloper\BetterDocsPro;

if ( ! defined( 'ABSPATH' ) ) {exit;}

use Exception;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\Views as FreeViews;
use WPDeveloper\BetterDocsPro\Admin\Customizer\Customizer;
use WPDeveloper\BetterDocsPro\Core\Admin;
use WPDeveloper\BetterDocsPro\Core\Install;
use WPDeveloper\BetterDocsPro\Core\Installer;
use WPDeveloper\BetterDocsPro\Core\MultipleKB;
use WPDeveloper\BetterDocsPro\Core\Query;
use WPDeveloper\BetterDocsPro\Core\Roles;
use WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing\Manager as LicenseManager;
use WPDeveloper\BetterDocsPro\FrontEnd\FrontEnd;
use WPDeveloper\BetterDocsPro\Shortcodes\Attachment;
use WPDeveloper\BetterDocsPro\Shortcodes\BetterdocsEncyclopedia;
use WPDeveloper\BetterDocsPro\Shortcodes\CategoryBoxTwo;
use WPDeveloper\BetterDocsPro\Shortcodes\CategoryGridList;
use WPDeveloper\BetterDocsPro\Shortcodes\CategoryGridTwo;
use WPDeveloper\BetterDocsPro\Shortcodes\ListView;
use WPDeveloper\BetterDocsPro\Shortcodes\MultipleKB as MultipleKBShortcode;
use WPDeveloper\BetterDocsPro\Shortcodes\MultipleKBList;
use WPDeveloper\BetterDocsPro\Shortcodes\MultipleKBTabGrid;
use WPDeveloper\BetterDocsPro\Shortcodes\MultipleKBTwo;
use WPDeveloper\BetterDocsPro\Shortcodes\MultipleKBThree;
use WPDeveloper\BetterDocsPro\Shortcodes\PopularArticles;
use WPDeveloper\BetterDocsPro\Shortcodes\RelatedCategories;
use WPDeveloper\BetterDocsPro\Shortcodes\RelatedDocs;
use WPDeveloper\BetterDocsPro\Shortcodes\SidebarList;
use WPDeveloper\BetterDocsPro\Shortcodes\ExtendSearchModal;
use WPDeveloper\BetterDocsPro\Shortcodes\ApiReference as ApiReferenceShortcode;
use WPDeveloper\BetterDocsPro\Utils\Enqueue;
use WPDeveloper\BetterDocsPro\Utils\Helper;
use WPDeveloper\BetterDocsPro\Core\AccessControl;
use WPDeveloper\BetterDocsPro\Core\ContentRestrictions;
use WPDeveloper\BetterDocsPro\Shortcodes\AdvancedSearch;

final class Plugin {
    /**
     * Plugin Version
     * @var string
     */
    public $version = '4.3.1';

    /**
     * Plugin DB Version
     *
     * 1.4.0 creates {prefix}betterdocs_api_specs for API Documentation.
     * 1.5.0 widens betterdocs_user_journeys.ip_address to varchar(64).
     * 1.6.0 creates {prefix}betterdocs_analytics_insights for Content Intelligence.
     *
     * This must be higher than any version already SHIPPED, not merely higher
     * than this branch's previous value. check_db_updates() runs its dbDelta
     * only while `version_compare( stored, code, '<' )` holds, so adopting a
     * number existing installs already carry means the migration never fires
     * for exactly the people who need it — 4.1.0 shipped 1.3.0, so a branch
     * that also says 1.3.0 leaves every updated site without the table and the
     * whole feature dead. Fresh activations hide it, because activation creates
     * the tables outright.
     *
     * The same trap applies ACROSS branches, not just across releases: the
     * api-docs work and the ip_address widening were developed in parallel and
     * both independently picked 1.4.0. Merging them produced no conflict — both
     * sides literally said "1.4.0" — so a site that had already run a 4.2.0
     * pre-release would carry 1.4.0, get the api_specs table, and never receive
     * the column ALTER. Hence 1.5.0. When two branches both bump this, the
     * merge must take max+1, never the shared value.
     *
     * It then happened a third time: the Content Intelligence branch bumped to
     * 1.5.0 for the insights table while dev independently used 1.5.0 for the
     * ip_address widening. Per the rule above this merge takes 1.6.0, so a site
     * that already ran either pre-release still gets the other's dbDelta. Check
     * `git show master:includes/Plugin.php` before picking a number on a
     * long-lived feature branch.
     *
     * @var string
     */
    public $db_version = '1.6.0';

    private static $_instance = null;

    /**
     * License Manager instance
     * @var LicenseManager
     */
    private $licenseManager;

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
        if ( static::$_instance == null ) {
            static::$_instance = new self();

            do_action( 'betterdocs_pro_loaded' );
        }

        return static::$_instance;
    }

    /**
     * Container
     * @var \WPDeveloper\BetterDocs\Dependencies\DI\ContainerBuilder
     */
    public $container;

    /**
     * Assets manager
     *
     * @var Enqueue
     */
    public $assets;

    /**
     * Views Manager
     *
     * @var FreeViews
     */
    public $views;

    /**
     * Query
     *
     * @var Query
     */
    public $query;

    /**
     * Customizer
     *
     * @var Customizer
     */
    public $customizer;

    /**
     * Multiple KB
     *
     * @var MultipleKB
     */
    public $multiple_kb;

    public function __construct() {
        $this->define_constants();

        /**
         * Register activation and deactivation hooks
         * and version updates check
         */
        new Install();

        // Admin Notices
        add_action( 'admin_notices', array( $this, 'required_plugin' ) );
        add_action( 'admin_notices', array( $this, 'compatibility_notices' ) );

        add_action( 'betterdocs_init_before', array( $this, 'before_init' ) );
        add_filter( 'betterdocs_shortcodes', array( $this, 'pro_shortcodes' ) );
        add_action( 'betterdocs_init', array( $this, 'initialize' ) );

        add_filter( 'rest_pre_dispatch', array( $this, 'validate_recaptcha_on_settings_save' ), 10, 3 );

        // Check if BetterDocs Free is installed/activated or not.
        if ( ! Helper::is_plugin_active( 'betterdocs/betterdocs.php' ) ) {
            new Installer();
        }

        /**
         * After Setup Theme
         */
        add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );

        /**
         * After Plugins Loaded
         */
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    /**
     * Summary of define_constants
     * @return void
     */
    private function define_constants() {
        $this->define( 'BETTERDOCS_PRO_VERSION', $this->version );
        $this->define( 'BETTERDOCS_PRO_DB_VERSION', $this->db_version );
        $this->define( 'BETTERDOCS_PRO_ABSPATH', dirname( BETTERDOCS_PRO_FILE ) . '/' );
        $this->define( 'BETTERDOCS_PRO_ABSURL', plugin_dir_url( BETTERDOCS_PRO_FILE ) );
        $this->define( 'BETTERDOCS_PRO_PLUGIN_BASENAME', plugin_basename( BETTERDOCS_PRO_FILE ) );
        // Compiled block metadata lives under assets/build/ since the Node 24 asset
        // restructure; register_block_type() fails silently if this path is wrong.
        $this->define( 'BETTERDOCS_PRO_BLOCKS_DIRECTORY', BETTERDOCS_PRO_ABSPATH . 'assets/build/blocks/' );
        $this->define( 'BETTERDOCS_PRO_FSE_TEMPLATES_PATH', BETTERDOCS_PRO_ABSPATH . 'views/templates/fse/' );

        $this->define( 'BETTERDOCS_PRO_STORE_URL', 'https://api.wpdeveloper.com/' );
        $this->define( 'BETTERDOCS_PRO_SL_ITEM_ID', 342422 );
        $this->define( 'BETTERDOCS_PRO_SL_ITEM_SLUG', 'betterdocs-pro' );
        $this->define( 'BETTERDOCS_PRO_SL_ITEM_NAME', 'BetterDocs Pro' );
        $this->define( 'BETTERDOCS_PRO_SL_DB_PREFIX', 'betterdocs_pro_software_' );
        // $this->define( 'BETTERDOCS_FREE_PLUGIN', BETTERDOCS_PRO_ADMIN_DIR_PATH . 'library/betterdocs.zip' );
    }

    public function required_plugin() {
        $plugin                = 'betterdocs/betterdocs.php';
        $_betterdocs_activated = Helper::is_plugin_active( $plugin );
        if ( $_betterdocs_activated ) {
            return;
        }

        $_betterdocs_installed = Helper::get_plugins( $plugin );
        $button_text           = $_betterdocs_installed ? __( 'Activate Now', 'betterdocs-pro' ) : __( 'Install Now', 'betterdocs-pro' );

        $button_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=betterdocs' ), 'install-plugin_betterdocs' );
        if ( $_betterdocs_installed ) {
            $button_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
        }

        include BETTERDOCS_PRO_ABSPATH . 'views/admin/notices/activate.php';
    }

    public function compatibility_notices() {
        $plugin      = 'betterdocs/betterdocs.php';
        $plugins     = Helper::get_plugins();
        $plugin_data = $plugins[ $plugin ];

        // Require the paired Free release: the Analytics UI is version-coupled to
        // Free's analytics shell, so an older Free renders a broken/partial panel.
        if ( isset( $plugin_data[ 'Version' ] ) && version_compare( $plugin_data[ 'Version' ], '4.7.0', '>=' ) ) {
            return;
        }

        include BETTERDOCS_PRO_ABSPATH . 'views/admin/notices/compatibility.php';
    }

    public function license_notice() {
        // Only show notice if license is expired.
        // NOTE: Hooked to 'admin_notices' which never fires during REST/AJAX/WPML-intercepted requests.
        $license_status = get_option( 'betterdocs_pro_software__license_status', '' );

        if ( 'expired' !== $license_status ) {
            return;
        }

        /* translators: 1: opening <strong>, 2: closing </strong>, 3: opening <a> tag, 4: closing </a> tag */
        $message = sprintf( __( '%1$sBetterDocs Pro%2$s license has expired. Please click %3$s here %4$s to renew your license.', 'betterdocs-pro' ), '<strong>', '</strong>', '<a href="https://betterdocs.co/#pricing">', '</a>' );
        echo '<div class="notice notice-warning is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
    }

    public function initialize() {

        betterdocs()->load_plugin_textdomain( 'betterdocs-pro', BETTERDOCS_PRO_FILE );

        // Add license notice hook for standard WP admin pages.
        // On BetterDocs-specific screens, Admin.php re-adds it at in_admin_header priority 999
        // (after CacheBank wipes all admin_notices at priority 10).
        add_action( 'admin_notices', array( $this, 'license_notice' ) );

        /**
         * Plugin Licensing
         * @since 2.5.0
         */
        $is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;
        if ( ! $is_rest && isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
            $request_uri = sanitize_text_field( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) );
            $is_rest     = strpos( $request_uri, rest_get_url_prefix() ) !== false;
        }

        if ( is_admin() || wp_doing_cron() || wp_doing_ajax() || $is_rest || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            $this->license();
        }

        $this->container = betterdocs()->container;
        $this->container->get( FrontEnd::class );
        $this->container->get( Admin::class );
        $this->container->get( Roles::class );

        /**
         * Abilities API / MCP: hands Pro's five abilities to Free's registrar
         * through the `betterdocs_register_abilities` filter, replacing the
         * placeholders Free registered for them. Resolved here because
         * initialize() runs on `betterdocs_init`, which Free fires from its own
         * `init` callback at priority 0 — before anything reads the abilities
         * registry, which is what builds the list.
         *
         * @since 4.3.0
         */
        $this->container->get( \WPDeveloper\BetterDocsPro\Abilities\Registrar::class );

        // Pro usage-analytics collector. initialize() runs on `betterdocs_init`
        // (every request, incl. WP-Cron), so its `betterdocs_insights_data` filter
        // is registered whenever the tracking payload is built.
        $this->container->get( \WPDeveloper\BetterDocsPro\Insights\Collector::class );

        // Initialize Real-time Related Docs Panel.
        // Note: the AI Suggestions feature now renders ONLY in the admin
        // metabox (AIInsights). The frontend display class was removed in
        // response to product feedback — readers should not see auto-generated
        // recommendations on docs pages. Tracker still feeds collaborative
        // recommendations consumed by the admin REST endpoint.
        $this->container->get( Core\RelatedDocsTracker::class );
        $this->container->get( Core\RelatedDocsEngine::class );

        // Advanced Analytics: GeoIP enrichment (country) + monthly DB refresh.
        $this->container->get( Core\AnalyticsGeoIP::class );
        // Advanced Analytics: enrich each Free-recorded view into the raw events table.
        $this->container->get( Core\AnalyticsEventCollector::class );
        // Advanced Analytics: capture per-item feedback into the inbox table.
        $this->container->get( Core\AnalyticsFeedbackCollector::class );
        // Advanced Analytics: Action Scheduler rollup of raw events into the daily table.
        $this->container->get( Core\AnalyticsAggregator::class );
        // Advanced Analytics: retention purge (raw 30d / aggregated 12mo) + GDPR.
        $this->container->get( Core\AnalyticsRetention::class );
        // Advanced Analytics: Link Health scanner (weekly + manual).
        $this->container->get( Core\AnalyticsLinkScanner::class );
        // Advanced Analytics: server-side GA4 forwarding (Measurement Protocol).
        $this->container->get( Core\AnalyticsGA4Reporter::class );
        // Advanced Analytics v1.5: server-side AI-agent detection on doc requests.
        $this->container->get( Core\AiTrafficCollector::class );
        // Advanced Analytics v1.5: serve docs as Markdown to AI agents (.md / Accept).
        $this->container->get( Core\MarkdownEndpoint::class );
        // Content Intelligence v2.0: nightly rule-based Stale Content detection.
        $this->container->get( Core\AnalyticsStaleScanner::class );
        // Content Intelligence v2.0: nightly Content Health scoring + site snapshot.
        $this->container->get( Core\ContentHealthScorer::class );
        // Content Intelligence v2.0: cloud sync client (Gaps + Duplicates).
        $this->container->get( Core\ContentIntelligenceService::class );

        /**
         * Content Intelligence (Pro). Free declares the menu slot and, on its own,
         * fills it with a locked teaser. This flag is how Free knows the real
         * screen is available and stands its teaser down — Plugin::has_content_intelligence()
         * reads it, and both the menu slot and the teaser route key off that.
         */
        add_filter( 'betterdocs_pro_has_content_intelligence', '__return_true' );
        // Swaps Free's teaser bundle for Pro's Content Intelligence app on
        // betterdocs_page_betterdocs-content-iq.
        $this->container->get( \WPDeveloper\BetterDocsPro\Admin\ContentIntelligence::class );

        /**
         * Register activation and deactivation hooks
         * and version updates check
         */
        // $this->container->get( Install::class );

        $this->assets      = $this->container->get( Enqueue::class );
        $this->views       = $this->container->get( FreeViews::class );
        $this->customizer  = $this->container->get( Customizer::class );
        $this->multiple_kb = $this->container->get( MultipleKB::class );
        $this->query       = $this->container->get( Query::class );

        add_filter( 'betterdocs_pro_has_git_integration', '__return_true' );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\GitIntegration::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\GitHubOAuth::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\GitHubSync::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\WriteWithAIGit::class );

        /**
         * API Documentation (Pro). The Free base is already Pro-aware — caps,
         * full-spec serving, and the "Powered by BetterDocs" footer all key off
         * betterdocs()->is_pro_active(), so activating Pro lifts them with no
         * DI remap needed. Pro adds new behaviour on top (URL sync, per-reference
         * visibility enforcement, Try-it proxy — wired in their own units).
         */
        add_filter( 'betterdocs_pro_has_api_docs', '__return_true' );
        // API Documentation is a Pro-only feature: the CPT, spec store, REST,
        // admin app, block and shortcode all live in Pro. Boot the hub (registers
        // the CPT, meta, rewrite rule, single template and admin menu).
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiReferences::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiReferenceSync::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiReferenceVisibility::class );
        // v1.5: endpoint materialization (operations → real docs posts) + the
        // background worker (must instantiate on every request so its async
        // handler hooks are present when a queued batch loops back).
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiDocs\Materializer::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiDocs\MaterializeProcess::class );
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiDocs\Frontend::class );
        // v2.0: the AI overlay read-side merges (served spec + endpoint docs).
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiDocs\AiOverlay::class );
        // The AI queue worker must instantiate on every request so its async
        // handler hooks are present when a queued batch loops back.
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\ApiDocs\AiProcess::class );

        /**
         * Glossaries (Pro). The taxonomy, its term meta, the whole
         * `betterdocs/glossary/*` REST surface and the React manager all live in
         * Pro — Free ships only the locked teaser that holds the
         * `betterdocs-glossaries` route until Pro is active.
         *
         * Both units are booted unconditionally so the taxonomy registration and
         * the REST routes exist for every request; each one re-checks
         * `enable_glossaries` itself, because the setting is what the user
         * toggles at runtime and a stale bootstrap would leave the taxonomy
         * unregistered until the next page load.
         *
         * @since 4.2.3 Moved here from Free.
         */
        $this->container->get( \WPDeveloper\BetterDocsPro\Core\GlossaryTaxonomy::class );

        if ( betterdocs()->settings->get( 'enable_glossaries', false ) ) {
            $this->container->get( \WPDeveloper\BetterDocsPro\Core\Glossaries::class );
        }

        if ( betterdocs()->settings->get( 'enable_content_restriction' ) && betterdocs()->settings->get( 'internal_knowledge_base_type' ) == 'advanced' && isset( wp_get_current_user()->roles ) && ! in_array( 'administrator', wp_get_current_user()->roles ) ) { //for admin this feature will not work as admin can view all docs and categories
            $this->container->get( AccessControl::class )->init();
        }
        /**
         * Initialize API
         */
        add_action( 'rest_api_init', array( $this, 'api_initialization' ) );
    }

    public function pro_shortcodes( $shortcodes ) {

        $is_enable_encyclopedia = betterdocs()->settings->get( 'enable_encyclopedia' );

        $shortcodeClasses = array(
            Attachment::class,
            CategoryBoxTwo::class,
            ListView::class,
            MultipleKBTabGrid::class,
            PopularArticles::class,
            MultipleKBShortcode::class,
            MultipleKBTwo::class,
            MultipleKBThree::class,
            MultipleKBList::class,
            CategoryGridTwo::class,
            CategoryGridList::class,
            SidebarList::class,
            RelatedCategories::class,
            RelatedDocs::class,
            ExtendSearchModal::class,
            AdvancedSearch::class,
            ApiReferenceShortcode::class
        );

        if ( $is_enable_encyclopedia ) {
            $shortcodeClasses[  ] = BetterdocsEncyclopedia::class;
        }

        return array_merge( $shortcodes, $shortcodeClasses );
    }

    /**
     * This methods will invoked after theme is setup.
     * @return void
     */
    public function setup_theme() {
        add_image_size( 'betterdocs-category-thumb', 360, 512 );
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound
        }
    }

    public function admin_init() {
        if ( defined( 'DOING_AJAXX' ) && DOING_AJAX || ! is_admin() ) {
            return;
        }
    }

    public function before_init() {
        add_filter( 'betterdocs_container_config', array( $this, 'container_config' ) );
    }

    public function scripts( $hook ) {
    }

    public function container_config( $configs ) {
        $config_array = require_once BETTERDOCS_PRO_ABSPATH . 'includes/config.php';

        if ( is_array( $config_array ) ) {
            $configs = array_merge( $configs, $config_array );
        }

        return $configs;
    }

    /**
     * Block the BetterDocs settings save when reCAPTCHA is enabled but
     * the Site Key or Secret Key is missing. Returns a WP_Error short-circuit
     * so the admin sees a clear inline message and nothing is persisted.
     *
     * @param mixed            $result
     * @param \WP_REST_Server  $server
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function validate_recaptcha_on_settings_save( $result, $server, $request ) {
        if ( ! ( $request instanceof \WP_REST_Request ) ) {
            return $result;
        }
        if ( $request->get_method() !== 'POST' ) {
            return $result;
        }
        if ( $request->get_route() !== '/betterdocs/v1/settings' ) {
            return $result;
        }

        $params = $request->get_params();
        if ( empty( $params[ 'enable_recaptcha' ] ) ) {
            return $result;
        }

        $site_key   = isset( $params[ 'recaptcha_site_key' ] ) ? trim( (string) $params[ 'recaptcha_site_key' ] ) : '';
        $secret_key = isset( $params[ 'recaptcha_secret_key' ] ) ? trim( (string) $params[ 'recaptcha_secret_key' ] ) : '';

        if ( '' === $site_key || '' === $secret_key ) {
            return new \WP_Error(
                'recaptcha_keys_required',
                __( 'reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.', 'betterdocs-pro' ),
                array( 'status' => 400 )
            );
        }

        return $result;
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

    public function license() {
        if ( ! did_action( 'betterdocs_loaded' ) ) {
            return;
        }

        // Guard required by the licensing library's README. If the class is
        // missing at runtime — a partial plugin update, or a host serving stale
        // OPcache — an unguarded static call throws an uncaught Error on 'init'
        // and white-screens the whole site on every request. Degrade to
        // "license inactive" instead.
        if ( ! class_exists( LicenseManager::class ) ) {
            return;
        }

        try {
            $this->licenseManager = LicenseManager::get_instance( array(
                'plugin_file' => BETTERDOCS_PRO_FILE,
                'version' => $this->version,
                'item_id' => BETTERDOCS_PRO_SL_ITEM_ID,
                'item_name' => BETTERDOCS_PRO_SL_ITEM_NAME,
                'item_slug' => BETTERDOCS_PRO_SL_ITEM_SLUG,
                'storeURL' => BETTERDOCS_PRO_STORE_URL,
                'textdomain' => 'betterdocs-pro',
                'db_prefix' => BETTERDOCS_PRO_SL_DB_PREFIX,
                'page_slug' => 'betterdocs-settings#license',

                'scripts_handle' => 'betterdocs-pro-settings',
                'screen_id' => array(
                    "betterdocs_page_betterdocs-settings",
                    "toplevel_page_betterdocs-dashboard",
                    "betterdocs_page_betterdocs-admin",
                    "betterdocs_page_betterdocs-analytics",
                    "betterdocs_page_betterdocs-faq",
                    "betterdocs_page_betterdocs-ai-chatbot"
                ),
                'api' => 'rest',
                // Production builds must not run the licensing SDK in dev mode. The
                // store (BETTERDOCS_PRO_STORE_URL) is a public HTTPS host, so the
                // host-allowance the flag enabled is unnecessary, and its staging
                // update endpoint has no live consumer.
                'dev_mode' => false,
                'rest' => array(
                    'namespace' => 'betterdocs-pro',
                    'permission' => 'delete_users'
                )
            ) );
        } catch ( \Exception $e ) {
            error_log( 'Licensing Error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    /**
     * @return LicenseManager|null
     */
    public function get_license_manager() {
        return $this->licenseManager;
    }

    /**
     * Restricted doc post IDs for the current user, applying whichever internal knowledge-base
     * mode is active (basic → {@see ContentRestrictions}, advanced → {@see AccessControl}).
     *
     * Intended as the single public entry point for addons such as betterdocs-ai-chatbot that
     * need to know which docs the current user must not see — both for REST responses and for
     * any content the addon ships to third-party services.
     *
     * Returns an empty array when content restriction is disabled or the current user is an
     * administrator, so callers do not need to re-check those conditions.
     *
     * @return int[]
     */
    public function get_restricted_doc_ids() {
        if ( ! betterdocs()->settings->get( 'enable_content_restriction', false ) ) {
            return array();
        }

        if ( is_user_logged_in() && in_array( 'administrator', wp_get_current_user()->roles, true ) ) {
            return array();
        }

        $mode = betterdocs()->settings->get( 'internal_knowledge_base_type', 'basic' );

        if ( 'advanced' === $mode ) {
            return $this->container->get( AccessControl::class )->get_all_restricted_post_ids();
        }

        return $this->container->get( ContentRestrictions::class )->get_restricted_doc_ids_for_current_user();
    }
}
