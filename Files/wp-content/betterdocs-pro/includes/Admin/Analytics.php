<?php

namespace WPDeveloper\BetterDocsPro\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Hit-counter writes to the betterdocs_analytics table on every doc view —
// caching reads/writes here would defeat the purpose (analytics need to be
// real-time and per-request). The direct queries below all use $wpdb->prepare.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Admin\Analytics as FreeAnalytics;

class Analytics extends FreeAnalytics {
    public function __construct( Database $database ) {
        parent::__construct( $database );

        /**
         * View collection now lives in Free (Core\AnalyticsTracker, async beacon)
         * as of Advanced Analytics v1.0, so the synchronous wp_head counter below
         * is retired to avoid double-counting. Pro adds richer collection on top
         * of the raw events table in a later unit. set_cookies()/update_analytics()
         * are kept for back-compat but no longer hooked.
         */
    }

    public function set_cookies() {
        if ( betterdocs()->settings->get('unique_visitor_count') == false ) {
            return;
        }

        if ( is_singular( 'docs' ) && $this->is_eligible_visits() == true ) {
            $post_id = get_the_ID();
            if ( ! isset( $_COOKIE["docs_visited_{$post_id}"] ) ) {
                setcookie( 'docs_visited_' . $post_id, true, time() + ( 86400 * 7 ), "/" );
            }
        }
    }

    public function update_analytics() {
        global $post_type, $post, $user_ID, $wpdb;

        if ( $post_type !== 'docs' || ! is_singular( 'docs' ) ) {
            return;
        }

        if ( wp_is_post_revision( $post ) || is_preview() ) {
            return;
        }

        $post_id            = isset( $post->ID ) ? (int) $post->ID : null;
        $is_eligible_visits = $this->is_eligible_visits();

        if ( ! $is_eligible_visits || $post_id === null ) {
            return;
        }

        // find if this date data available on betterdocs_analytics
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from {$wpdb->prefix}betterdocs_analytics where post_id = %d and created_at = %s",
                [
                    $post_id,
                    gmdate( "Y-m-d" )
                ]
            )
        );

        if ( betterdocs()->settings->get('unique_visitor_count') != false ) {
            if ( ! empty( $result ) ) {
                $impressions_increment = $result[0]->impressions + 1;
                if ( ! isset( $_COOKIE['docs_visited_' . $post->ID] ) ) {
                    $unique_visit = $result[0]->unique_visit + 1;
                } else {
                    $unique_visit = $result[0]->unique_visit;
                }
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}betterdocs_analytics
                                SET impressions = %d, unique_visit = %d
                                WHERE created_at = %s AND post_id = %d",
                        [(int) $impressions_increment, (int) $unique_visit, gmdate( 'Y-m-d' ), $post_id]
                    )
                );
            } else {
                $unique_visit = ( ! isset( $_COOKIE['docs_visited_' . $post->ID] ) ) ? 1 : 0;
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}betterdocs_analytics
                                ( post_id, impressions, unique_visit, created_at )
                                VALUES ( %d, %d, %d, %s )",
                        [$post_id, 1, $unique_visit, gmdate( 'Y-m-d' )]
                    )
                );
            }
        }

        $views = get_post_meta( $post_id, '_betterdocs_meta_views', true );
        $views = is_string( $views ) ? (int) $views : $views;

        if ( $views === null ) {
            add_post_meta( $post_id, '_betterdocs_meta_views', 1 );
        } else {
            update_post_meta( $post_id, '_betterdocs_meta_views', ++$views );
        }
    }

    protected function is_eligible_visits() {
        $should_count   = false;
        $analytics_from = betterdocs()->settings->get( 'analytics_from', 'everyone' );
        /**
         * Inspired from WP-Postviews for
         * this pece of code.
         */
        switch ( $analytics_from ) {
            case 'everyone':
                $should_count = true;
                break;
            case 'guests':
                if ( empty( $_COOKIE[USER_COOKIE] ) && (int) $user_ID === 0 ) {
                    $should_count = true;
                }
                break;
            case 'registered_users':
                if ( (int) $user_ID > 0 ) {
                    $should_count = true;
                }
                break;
        }

        $exclude_bot_analytics = betterdocs()->settings->get( 'exclude_bot_analytics', true );
        if ( $exclude_bot_analytics == 1 ) {
            /**
             * Inspired from WP-Postviews for
             * this piece of code.
             */
            $bots = [
                'Google Bot'    => 'google',
                'MSN'           => 'msnbot',
                'Alex'          => 'ia_archiver',
                'Lycos'         => 'lycos',
                'Ask Jeeves'    => 'jeeves',
                'Altavista'     => 'scooter',
                'AllTheWeb'     => 'fast-webcrawler',
                'Inktomi'       => 'slurp@inktomi',
                'Turnitin.com'  => 'turnitinbot',
                'Technorati'    => 'technorati',
                'Yahoo'         => 'yahoo',
                'Findexa'       => 'findexa',
                'NextLinks'     => 'findlinks',
                'Gais'          => 'gaisbo',
                'WiseNut'       => 'zyborg',
                'WhoisSource'   => 'surveybot',
                'Bloglines'     => 'bloglines',
                'BlogSearch'    => 'blogsearch',
                'PubSub'        => 'pubsub',
                'Syndic8'       => 'syndic8',
                'RadioUserland' => 'userland',
                'Gigabot'       => 'gigabot',
                'Become.com'    => 'become.com',
                'Baidu'         => 'baiduspider',
                'so.com'        => '360spider',
                'Sogou'         => 'spider',
                'soso.com'      => 'sosospider',
                'Yandex'        => 'yandex'
            ];
            $useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
            foreach ( $bots as $name => $lookfor ) {
                if ( ! empty( $useragent ) && ( false !== stripos( $useragent, $lookfor ) ) ) {
                    $should_count = false;
                    break;
                }
            }
        }

        return $should_count;
    }

    public function get_views( $post_id ) {
        global $wpdb;
        $reactions = $wpdb->get_results(
            $wpdb->prepare( "
                SELECT sum(impressions) as totalViews
                FROM {$wpdb->prefix}betterdocs_analytics
                WHERE post_id = %d",
                $post_id
            )
        );

        return $reactions[0]->totalViews;
    }

    /**
     * Loads on EVERY BetterDocs screen, not just the Analytics page — the same gate
     * Core\ApiReferences::enqueue_admin_app() uses, and for the same reason.
     *
     * This bundle does not render a page by itself: it registers a `betterdocs_routes`
     * filter into Free's dashboard SPA, which boots once per admin screen. The filter
     * has to be registered before that boot, so the bundle must already be on the page
     * the user is navigating *from*. Restrict it to the Analytics hook and the SPA has
     * no Pro route to switch to, so clicking Analytics either hard-reloads or — worse,
     * since Free registers a locked teaser on the same path — renders "Analytics is a
     * Pro feature" on a site that owns Pro.
     *
     * Until now this happened by accident: Free's _enqueue() re-invokes enqueue() with
     * the Analytics hook hard-coded so its own stylesheet loads everywhere, and that
     * literal slipped past the old page guard. Stating the gate directly makes the
     * behaviour survive a change to that parent method.
     *
     * @param string $hook
     * @return void
     */
    public function enqueue( $hook ) {
        if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        // The advanced analytics bundle is version-coupled to Free's analytics
        // shell. If the installed Free is older than the coordinated analytics
        // release (4.7.0), do NOT swap in the Pro bundle — it would render against
        // a mismatched shell and break the panel. Fall back to Free's own
        // self-contained Overview; Plugin::compatibility_notices() prompts the
        // user to update Free.
        if ( version_compare( betterdocs()->version, '4.7.0', '<' ) ) {
            return;
        }

        wp_dequeue_script( 'betterdocs-admin' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-analytics', 'admin/css/analytics.css' );

        /**
         * The shared `bda-*` design system, also loaded by the Content Intelligence
         * screen. It used to be imported by this app's Dashboard.js, which compiled
         * a byte-identical copy into content-intelligence.css as well.
         *
         * Ordered after analytics.css, and declaring it as a dependency so WordPress
         * cannot reorder them: that is the order the two had when webpack
         * concatenated them into one file (index.js imports analytics.scss, the
         * Dashboard component imported the shell), and the cascade is preserved
         * rather than re-derived.
         */
        betterdocs_pro()->assets->enqueue( 'betterdocs-bda-shell', 'admin/css/bda-shell.css', [ 'betterdocs-analytics' ] );

        betterdocs_pro()->assets->enqueue( 'betterdocs-analytics', 'admin/js/analytics.js' );
        wp_enqueue_script( 'betterdocs-admin' );

        $multiple_kb = betterdocs()->settings->get( 'multiple_kb' ) == 1;

        betterdocs_pro()->assets->localize(
            'betterdocs-analytics',
            'betterdocs_pro',
            [
                'dir_url'         => BETTERDOCS_PRO_ABSURL,
                'rest_url'        => get_rest_url(),
                'free_version'    => betterdocs()->version,
                'pro_version'     => betterdocs_pro()->version,
                'nonce'           => wp_create_nonce( 'wp_rest' ),
                'multiple_kb'     => $multiple_kb,
                'knowledge_bases' => $multiple_kb ? $this->get_kb_options() : []
            ]
        );
    }

    /**
     * Knowledge Base options for the analytics header filter, returned only when
     * Multiple Knowledge Base is enabled. Shape: [ { value: slug, label: name } ].
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

    public function views() {
        betterdocs()->admin->output();
    }

}
