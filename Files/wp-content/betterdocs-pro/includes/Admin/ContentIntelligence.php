<?php

namespace WPDeveloper\BetterDocsPro\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Content Intelligence admin screen (Pro).
 *
 * Free owns the page itself — it declares the `betterdocs-content-iq`
 * submenu slot and, without Pro, fills it with the locked teaser. Pro does not
 * re-register the menu; it swaps the *bundle* that Free's dashboard app mounts
 * on that screen. `admin/js/content-intelligence.js` is a side-effect
 * micro-frontend: it hooks `betterdocs_routes` and the header filters, so the
 * real Content Intelligence app replaces Free's teaser route in place.
 *
 * Deliberately not a subclass of Admin\Analytics: that class carries the
 * hit-counter/report-email hooks from Free's Analytics base, and extending it
 * would register them a second time.
 *
 * @since 4.3.0
 */
class ContentIntelligence {
    /**
     * Submenu page hook for `betterdocs-content-iq` under the
     * `betterdocs` parent menu.
     */
    const PAGE_HOOK = 'betterdocs_page_betterdocs-content-iq';

    /**
     * Shared script/style handle. Matches the webpack entry name so the JS and
     * the CSS extracted from it stay addressable as one unit.
     */
    const HANDLE = 'betterdocs-content-intelligence';

    /**
     * Free release that introduced the Content Intelligence shell (menu slot,
     * teaser route, `content_intelligence_teaser` localize key).
     */
    const MIN_FREE_VERSION = '4.9.0';

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    /**
     * Loads on EVERY BetterDocs screen, not only PAGE_HOOK — matching
     * Core\ApiReferences::enqueue_admin_app() and Admin\Analytics::enqueue().
     *
     * This bundle registers a `betterdocs_routes` filter into Free's dashboard SPA
     * rather than rendering a page itself, and that SPA boots once per admin screen.
     * Scoped to its own page hook, the filter was absent everywhere else, so the
     * router had no client-side route to switch to and the Content Intelligence menu
     * item hard-reloaded while every other BetterDocs menu item did not.
     *
     * @param string $hook
     * @return void
     */
    public function enqueue( $hook ) {
        if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        /**
         * This bundle is version-coupled to Free's dashboard shell: it mounts by
         * filtering Free's route registry and header. An older Free has neither
         * the route registry entry nor the page, so swapping the bundle in would
         * render nothing. Bail and let Free stay authoritative;
         * Plugin::compatibility_notices() prompts the user to update Free.
         */
        if ( version_compare( betterdocs()->version, self::MIN_FREE_VERSION, '<' ) ) {
            return;
        }

        /**
         * Same ordering dance as Admin\Analytics::enqueue(): Free's dashboard app
         * (`betterdocs-admin`) is already queued on every BetterDocs screen, and
         * it reads the route/header registries at mount. Dequeuing it, inserting
         * this micro-frontend, then re-enqueuing guarantees our filters are
         * registered before the app boots — otherwise the teaser route wins.
         */
        wp_dequeue_script( 'betterdocs-admin' );

        /**
         * The shared `bda-*` design system (tokens, shell, nav, cards, tables, KPI
         * cards, banners, locked panels), which the Analytics screen loads too. One
         * stylesheet, its own webpack entry — this screen used to `@import` it into
         * its own SCSS, which compiled a second copy of every shared rule.
         */
        betterdocs_pro()->assets->enqueue( 'betterdocs-bda-shell', 'admin/css/bda-shell.css' );

        // webpack extracts the SCSS imported by the JS entry into
        // assets/build/admin/css/content-intelligence.css (MiniCssExtractPlugin
        // rewrites `admin/js/<name>` to `admin/css/<name>.css`). RemoveEmptyScripts
        // /MiniCss emit nothing when the entry imports no styles, so probe the
        // build output instead of enqueueing a 404.
        //
        // Depends on the shell so the CI-only rules stay after it in the cascade —
        // the order they had when the shell was `@import`ed at the top of this
        // screen's SCSS.
        if ( $this->has_stylesheet() ) {
            betterdocs_pro()->assets->enqueue( self::HANDLE, 'admin/css/content-intelligence.css', [ 'betterdocs-bda-shell' ] );
        }

        betterdocs_pro()->assets->enqueue( self::HANDLE, 'admin/js/content-intelligence.js' );
        wp_enqueue_script( 'betterdocs-admin' );

        $multiple_kb = betterdocs()->settings->get( 'multiple_kb' ) == 1;

        /**
         * Identical payload to the one Admin\Analytics ships on the Analytics
         * screen. The Content Intelligence views were moved out of the Analytics
         * bundle and still read `betterdocs_pro` for the REST root, the nonce and
         * the KB header filter, so it has to be present under this handle too —
         * the Analytics bundle is not loaded on this page.
         */
        betterdocs_pro()->assets->localize(
            self::HANDLE,
            'betterdocs_pro',
            [
                'dir_url'         => BETTERDOCS_PRO_ABSURL,
                'rest_url'        => get_rest_url(),
                'free_version'    => betterdocs()->version,
                'pro_version'     => betterdocs_pro()->version,
                'nonce'           => wp_create_nonce( 'wp_rest' ),
                'multiple_kb'     => $multiple_kb,
                'knowledge_bases' => $multiple_kb ? $this->get_kb_options() : [],
                // CTA targets for the Content Intelligence chatbot-state banner
                // (see CIOverviewView). Built with admin_url() here so the React
                // side never has to guess the admin base.
                'license_url'     => admin_url( 'admin.php?page=betterdocs-settings&tab=tab-license' ),
                'ai_chatbot_url'  => admin_url( 'admin.php?page=betterdocs-settings&tab=tab-ai-chatbot' ),
            ]
        );
    }

    /**
     * Whether the build actually emitted a stylesheet for this entry.
     *
     * @return bool
     */
    protected function has_stylesheet() {
        return file_exists( BETTERDOCS_PRO_ABSPATH . 'assets/build/admin/css/content-intelligence.css' );
    }

    /**
     * Knowledge Base options for the header filter, returned only when Multiple
     * Knowledge Base is enabled. Shape: [ { value: slug, label: name } ].
     *
     * Mirrors Admin\Analytics::get_kb_options(); that copy is `protected` on a
     * class this one must not extend, so it cannot be reused as-is. Extracting
     * both into a shared trait/helper is the right follow-up.
     *
     * @return array
     */
    protected function get_kb_options() {
        $terms = get_terms( [
            'taxonomy'   => 'knowledge_base',
            'hide_empty' => false,
            'parent'     => 0
        ] );

        if ( is_wp_error( $terms ) ) {
            return [];
        }

        return array_map( function ( $term ) {
            return [ 'value' => $term->slug, 'label' => $term->name ];
        }, $terms );
    }
}
