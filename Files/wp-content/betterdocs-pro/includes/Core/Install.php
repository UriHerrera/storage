<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPDeveloper\BetterDocs\Core\Roles;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class Install {
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

    public function __construct() {
        register_activation_hook( BETTERDOCS_PRO_FILE, [$this, 'activate'] );
        register_deactivation_hook( BETTERDOCS_PRO_FILE, [$this, 'deactivate'] );

        add_action( 'init', [$this, 'init'], -999 );
        add_action( 'init', [$this, 'check_db_updates'], 1 );
        add_action( 'init', [$this, 'check_version'], 5 );
        // After the new tables exist (priority 1), seed them once from the
        // legacy Free analytics table so history from older versions survives.
        add_action( 'init', [$this, 'maybe_backfill_legacy_analytics'], 6 );
        // Expand legacy per-day reaction counts into per-item Feedback Inbox rows
        // so the inbox shows history collected before the update.
        add_action( 'init', [$this, 'maybe_backfill_legacy_feedback'], 7 );
        // Drop backfill rows that duplicate pipeline rows for the same (post, day)
        // on installs that ran the old key-collision-based backfill (QA #15).
        add_action( 'init', [$this, 'maybe_repair_duplicate_daily_rows'], 8 );
        // Reset Pro sites left on the legacy 90-day retention default to Forever.
        add_action( 'init', [$this, 'maybe_reset_pro_retention_forever'], 9 );
    }

    /**
     * One-time: reset Pro sites still on the legacy 90-day retention default back
     * to Forever (0) — Pro's default and recommended value. An older Pro version
     * defaulted data_retention_days to 90; that value persisted as a stored setting
     * after updating, so the dashboard kept showing "90 days" instead of "Forever"
     * (the new default never overrides a stored value). Only the exact legacy
     * default (90) is reset — deliberate longer windows (180/365/730) are preserved.
     * Non-destructive: Forever keeps more data, it never deletes any.
     */
    public function maybe_reset_pro_retention_forever() {
        if ( get_option( 'betterdocs_pro_retention_forever_migrated' ) ) {
            return;
        }
        $settings = get_option( 'betterdocs_settings' );
        if ( is_array( $settings ) && isset( $settings['data_retention_days'] ) && 90 === (int) $settings['data_retention_days'] ) {
            $settings['data_retention_days'] = 0; // 0 = Forever
            update_option( 'betterdocs_settings', $settings );
        }
        update_option( 'betterdocs_pro_retention_forever_migrated', 1, false );
    }

    public function init() {
        if( ! class_exists( '\WPDeveloper\BetterDocs\Plugin' ) ) {
            return;
        }

        $this->container = betterdocs()->container;
        $this->database  = $this->container->get( Database::class );

        if ( $this->database->get_transient( 'betterdocs_pro_activated' ) ) {
            // Create DB Tables if not created.
            $this->check_db_updates();

            // Set admin roles
            $this->container->get( Roles::class )->setup( true );

            // Save default settings.
            // $this->container->get( Settings::class )->save_default_settings();

            $this->database->delete_transient( 'betterdocs_pro_activated' );
        }
    }

    public function activate() {
        // Flush all the existing rewrite rules
        set_transient( 'betterdocs_flush_rewrite_rules', true );

        // This flag will re-run some checks after activation of the plugin.
        set_transient( 'betterdocs_pro_activated', true );
    }

    public function deactivate() {
        // Flush all the existing rewrite rules
        set_transient( 'betterdocs_flush_rewrite_rules', true );

        // Remove roles
        if( $this->container !== null ) {
            $this->container->get( Roles::class )->setup( true );
        }

        // Clear the related-docs retention cron so we don't leak orphaned hooks.
        $next = wp_next_scheduled( 'betterdocs_related_docs_cleanup' );
        if ( $next ) {
            wp_unschedule_event( $next, 'betterdocs_related_docs_cleanup' );
        }
    }

    public function check_version() {
        $betterdocs_pro_version  = get_option( 'betterdocs_pro_version', '2.2.7' );
        $betterdocs_code_version = betterdocs_pro()->version;
        $requires_update         = version_compare( $betterdocs_pro_version, $betterdocs_code_version, '<' );

        if ( $requires_update && did_action( 'betterdocs_loaded' ) >= 1 ) {
            // Re-check if any db setup needed
            $this->check_db_updates();

            // Re-check if any migration is needed.
            $this->container->get( Migration::class )->init( str_replace( '.', '', $betterdocs_code_version ) );

            $this->update_version();
        }
    }

    /**
     * Update BetterDocs Pro version to current.
     */
    private function update_version() {
        update_option( 'betterdocs_pro_version', betterdocs_pro()->version );
    }

    /**
     * Run the schema migration when the stored DB version is behind the code.
     *
     * Serialized with the same cross-request mutex the analytics backfills use.
     * The version gate below is a read-modify-write — read the option, run the
     * dbDelta, then write the option — and it is reachable from four places that
     * are not coordinated with each other: this `init` callback (priority 1),
     * `init()` via the activation transient (priority -999), `check_version()`
     * (priority 5) and RelatedDocsTracker's missing-table check.
     *
     * On a plugin update a burst of concurrent requests (admin page, heartbeat,
     * admin-ajax, cron) therefore all pass the gate before any of them writes
     * the option, and all call create_tables() at once. dbDelta decides between
     * CREATE and ALTER from its own `SHOW TABLES` snapshot, so two of them can
     * each decide the table is missing and issue CREATE — the loser surfaces
     * "Table 'wp_betterdocs_api_specs' already exists" to the user.
     *
     * GET_LOCK makes exactly one request do the work; the rest return
     * immediately (timeout 0) and pick the tables up already built.
     */
    public function check_db_updates() {
        global $wpdb;

        $_db_version      = get_option( 'betterdocs_pro_db_version', '1.0' );
        $_db_code_version = betterdocs_pro()->db_version;
        $requires_update  = version_compare( $_db_version, $_db_code_version, '<' );

        if ( ! $requires_update ) {
            return;
        }

        $this->with_migration_lock( $wpdb->prefix . 'bd_pro_db_update', function () use ( $_db_code_version ) {
            // Re-read inside the lock: the request that held it before us has
            // already migrated, so without this we would repeat the whole dbDelta.
            if ( ! version_compare( get_option( 'betterdocs_pro_db_version', '1.0' ), $_db_code_version, '<' ) ) {
                return;
            }

            $this->create_tables();

            update_option( 'betterdocs_pro_db_version', $_db_code_version );
        } );
    }

    /**
     * Create database tables for BetterDocs Pro features
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Real-time Related Docs Panel - User Journey Tracking Table
        //
        // ip_address is varchar(64), not varchar(45): anonymize_ip_addresses is on
        // by default and stores a sha256 hex digest, which is exactly 64 chars. At
        // varchar(45) MySQL either truncated the digest (collapsing distinct IPs
        // into one bucket) or, under strict mode, rejected the INSERT outright and
        // silently dropped the journey row. Widening requires a db_version bump so
        // dbDelta ALTERs the column on existing installs — see Plugin::$db_version.
        $_user_journeys_table = $wpdb->prefix . 'betterdocs_user_journeys';
        $user_journeys_table = "CREATE TABLE $_user_journeys_table (
            id bigint NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint DEFAULT NULL,
            article_id bigint DEFAULT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            user_agent text,
            ip_address varchar(64),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY article_id (article_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) {$charset_collate};";

        // Real-time Related Docs Panel - AI Suggestions Performance Table
        $_related_suggestions_table = $wpdb->prefix . 'betterdocs_related_suggestions';
        $related_suggestions_table = "CREATE TABLE $_related_suggestions_table (
            id bigint NOT NULL AUTO_INCREMENT,
            article_id bigint NOT NULL,
            suggested_article_id bigint NOT NULL,
            suggestion_type varchar(50) NOT NULL,
            relevance_score decimal(5,2) DEFAULT 0.00,
            click_count int DEFAULT 0,
            impression_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY  unique_suggestion (article_id, suggested_article_id, suggestion_type),
            KEY article_id (article_id),
            KEY suggested_article_id (suggested_article_id),
            KEY suggestion_type (suggestion_type),
            KEY relevance_score (relevance_score)
        ) {$charset_collate};";

        // API Documentation — raw OpenAPI spec store (Pro-only feature). Moved
        // from Free's Install (the whole feature now lives in Pro).
        $_api_specs_table = $wpdb->prefix . 'betterdocs_api_specs';
        $api_specs_table  = "CREATE TABLE $_api_specs_table (
            id bigint NOT NULL AUTO_INCREMENT,
            reference_id bigint DEFAULT 0 NOT NULL,
            raw longtext NOT NULL,
            format varchar(8) DEFAULT 'json' NOT NULL,
            hash char(64) DEFAULT '' NOT NULL,
            source_url text NULL,
            etag varchar(255) DEFAULT '' NOT NULL,
            last_modified varchar(64) DEFAULT '' NOT NULL,
            fetched_at datetime NULL,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY  (id),
            KEY reference_id (reference_id),
            KEY reference_active (reference_id, is_active)
        ) {$charset_collate};";

        // ---- Advanced Analytics v1.0 tables ----

        // Raw event stream (write-only hot path, ~30-day retention).
        $_analytics_events_table = $wpdb->prefix . 'betterdocs_analytics_events';
        $analytics_events_table  = "CREATE TABLE $_analytics_events_table (
            id bigint NOT NULL AUTO_INCREMENT,
            event_type varchar(32) NOT NULL,
            object_id bigint DEFAULT 0 NOT NULL,
            session_hash char(64) DEFAULT NULL,
            kb_id bigint DEFAULT 0 NOT NULL,
            lang varchar(12) DEFAULT NULL,
            payload longtext NOT NULL,
            ip_hash char(64) DEFAULT NULL,
            created_at datetime NOT NULL,
            processed tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            KEY processed_created (processed, created_at),
            KEY event_type (event_type),
            KEY object_id (object_id)
        ) {$charset_collate};";

        // Per-doc per-day rollup (superset of the Free betterdocs_analytics table).
        $_analytics_daily_table = $wpdb->prefix . 'betterdocs_analytics_daily';
        $analytics_daily_table  = "CREATE TABLE $_analytics_daily_table (
            id bigint NOT NULL AUTO_INCREMENT,
            post_id bigint DEFAULT 0 NOT NULL,
            kb_id bigint DEFAULT 0 NOT NULL,
            lang varchar(12) DEFAULT '' NOT NULL,
            views bigint DEFAULT 0 NOT NULL,
            unique_views bigint DEFAULT 0 NOT NULL,
            happy bigint DEFAULT 0 NOT NULL,
            sad bigint DEFAULT 0 NOT NULL,
            normal bigint DEFAULT 0 NOT NULL,
            avg_scroll_depth decimal(5,2) DEFAULT 0.00 NOT NULL,
            reading_completions bigint DEFAULT 0 NOT NULL,
            stat_date date DEFAULT '0000-00-00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  uniq_post_day (post_id, kb_id, lang, stat_date),
            KEY stat_date (stat_date),
            KEY post_id (post_id)
        ) {$charset_collate};";

        // Search rollup (zero-result, CTR, result-count dimensions).
        $_analytics_search_table = $wpdb->prefix . 'betterdocs_analytics_search';
        $analytics_search_table  = "CREATE TABLE $_analytics_search_table (
            id bigint NOT NULL AUTO_INCREMENT,
            keyword varchar(191) NOT NULL,
            keyword_hash char(32) NOT NULL,
            search_count bigint DEFAULT 0 NOT NULL,
            results_count_avg decimal(8,2) DEFAULT 0.00 NOT NULL,
            zero_result_count bigint DEFAULT 0 NOT NULL,
            click_through_count bigint DEFAULT 0 NOT NULL,
            kb_id bigint DEFAULT 0 NOT NULL,
            lang varchar(12) DEFAULT '' NOT NULL,
            stat_date date DEFAULT '0000-00-00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  uniq_kw_day (keyword_hash, kb_id, lang, stat_date),
            KEY stat_date (stat_date),
            KEY zero_result_count (zero_result_count)
        ) {$charset_collate};";

        // Unified feedback stream for the Inbox (per-event status, bulk actions).
        $_analytics_feedback_table = $wpdb->prefix . 'betterdocs_analytics_feedback';
        $analytics_feedback_table  = "CREATE TABLE $_analytics_feedback_table (
            id bigint NOT NULL AUTO_INCREMENT,
            post_id bigint DEFAULT 0 NOT NULL,
            feeling varchar(16) NOT NULL,
            comment text DEFAULT NULL,
            session_hash char(64) DEFAULT NULL,
            status varchar(16) DEFAULT 'new' NOT NULL,
            kb_id bigint DEFAULT 0 NOT NULL,
            lang varchar(12) DEFAULT '' NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // Link health scan results.
        $_analytics_links_table = $wpdb->prefix . 'betterdocs_analytics_links';
        $analytics_links_table  = "CREATE TABLE $_analytics_links_table (
            id bigint NOT NULL AUTO_INCREMENT,
            post_id bigint DEFAULT 0 NOT NULL,
            url text NOT NULL,
            url_hash char(32) NOT NULL,
            http_status smallint DEFAULT 0 NOT NULL,
            link_type varchar(16) DEFAULT '' NOT NULL,
            is_broken tinyint(1) DEFAULT 0 NOT NULL,
            last_checked datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  uniq_post_url (post_id, url_hash),
            KEY is_broken (is_broken),
            KEY last_checked (last_checked)
        ) {$charset_collate};";

        // ---- Advanced Analytics v1.5: AI traffic ----

        // Per-agent per-doc hourly fetch rollup, written directly by the
        // server-side AI-agent detector (Core\AiTrafficCollector). Hour
        // granularity powers the activity heatmap; last_fetch keeps the exact
        // newest hit for the "Last AI fetch" live line.
        $_analytics_ai_table = $wpdb->prefix . 'betterdocs_analytics_ai_daily';
        $analytics_ai_table  = "CREATE TABLE $_analytics_ai_table (
            id bigint NOT NULL AUTO_INCREMENT,
            agent varchar(32) NOT NULL,
            post_id bigint DEFAULT 0 NOT NULL,
            fetches bigint DEFAULT 0 NOT NULL,
            stat_date date DEFAULT '0000-00-00' NOT NULL,
            stat_hour tinyint DEFAULT 0 NOT NULL,
            last_fetch datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  uniq_agent_post_hour (agent, post_id, stat_date, stat_hour),
            KEY stat_date (stat_date),
            KEY post_id (post_id),
            KEY agent (agent)
        ) {$charset_collate};";

        // ---- Advanced Analytics v2.0: Content Intelligence ----

        // Unified insight store for every Content Intelligence module (gaps,
        // stale, health, duplicates, rewrite suggestions). One row per detected
        // item, carrying a JSON payload shaped for the module's React view and a
        // lifecycle status the user drives from the dashboard.
        //
        // `source` records where the row was produced: 'local' for the rule-based
        // nightly jobs (stale/health) that run entirely in WordPress, 'cloud' for
        // items returned by the betterdocs-chat-ai analysis service (duplicates,
        // gaps). `object_hash` is the natural key that lets a re-run UPSERT an
        // existing item instead of duplicating it:
        //   - stale/health : md5 of the post id
        //   - duplicate     : md5 of the two post ids sorted ascending
        //   - gap           : md5 of the normalized cluster/topic signature
        $_analytics_insights_table = $wpdb->prefix . 'betterdocs_analytics_insights';
        $analytics_insights_table  = "CREATE TABLE $_analytics_insights_table (
            id bigint NOT NULL AUTO_INCREMENT,
            type varchar(32) NOT NULL,
            object_id bigint DEFAULT 0 NOT NULL,
            object_hash char(32) NOT NULL,
            score decimal(6,2) DEFAULT 0.00 NOT NULL,
            status varchar(16) DEFAULT 'new' NOT NULL,
            source varchar(16) DEFAULT 'local' NOT NULL,
            payload longtext NOT NULL,
            kb_id bigint DEFAULT 0 NOT NULL,
            lang varchar(12) DEFAULT '' NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  uniq_type_object (type, object_hash, kb_id, lang),
            KEY type_status (type, status),
            KEY score (score),
            KEY object_id (object_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(
            $user_journeys_table .
            $related_suggestions_table .
            $api_specs_table .
            $analytics_events_table .
            $analytics_daily_table .
            $analytics_search_table .
            $analytics_feedback_table .
            $analytics_links_table .
            $analytics_ai_table .
            $analytics_insights_table
        );
    }

    /**
     * Cross-request mutex around a one-shot migration. On a plugin update a burst
     * of concurrent `init` requests would otherwise all run the same heavy migration
     * at once — racing into "Deadlock found" and duplicate-key errors on the shared
     * aggregate tables (the get_option() guard is not atomic). GET_LOCK serializes
     * them: the first request runs the body, the rest bail immediately (timeout 0)
     * and the winner records the completion option. If the server has no GET_LOCK
     * (returns NULL) the body still runs unlocked — never worse than before.
     *
     * @param string   $name Server-scoped lock name.
     * @param callable $fn   Migration body; invoked only when we don't lose the race.
     */
    protected function with_migration_lock( $name, callable $fn ) {
        global $wpdb;
        $got = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 0 )', $name ) );
        if ( '0' === (string) $got ) {
            return; // another request holds it and will complete the migration
        }
        $locked = ( '1' === (string) $got );
        try {
            $fn();
        } finally {
            if ( $locked ) {
                $wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $name ) );
            }
        }
    }

    /**
     * Run a migration write, retrying on InnoDB deadlock (error 1213). The live
     * aggregator writes the same aggregate tables, so a migration statement can
     * deadlock with it even when serialized against other migration copies — retry
     * with backoff (as the error itself advises) instead of surfacing the raw
     * "Deadlock found" wpdberror. Errors are hidden during the attempts so a
     * transient deadlock never prints to the admin.
     *
     * @param string $sql Interpolation-safe SQL.
     * @return bool True when the statement ultimately succeeded.
     */
    protected function query_with_deadlock_retry( $sql ) {
        global $wpdb;
        $prev = $wpdb->hide_errors();
        try {
            for ( $attempt = 1; $attempt <= 4; $attempt++ ) {
                $wpdb->query( $sql );
                $err = (string) $wpdb->last_error;
                if ( '' === $err ) {
                    return true;
                }
                if ( false === stripos( $err, 'deadlock' ) ) {
                    return false; // non-deadlock error — don't spin
                }
                usleep( 150000 * $attempt ); // 0.15s, 0.3s, 0.45s, 0.6s
            }
            return false;
        } finally {
            $wpdb->show_errors( $prev );
        }
    }

    /**
     * One-time backfill of the legacy {prefix}betterdocs_analytics rows into the
     * Advanced-Analytics {prefix}betterdocs_analytics_daily rollup.
     *
     * The new dashboard reads views/article metrics from betterdocs_analytics_daily,
     * which only the forward-looking event pipeline populates. Without this copy,
     * a site that collected analytics on an older (master) version would see an
     * empty Article Performance / Views dashboard after updating. Runs exactly
     * once (guarded by an option) and uses INSERT IGNORE so any (post, day) the
     * new pipeline has already written is left untouched — no double counting.
     */
    public function maybe_backfill_legacy_analytics() {
        if ( get_option( 'betterdocs_analytics_legacy_backfilled' ) ) {
            return;
        }

        global $wpdb;

        $legacy = $wpdb->prefix . 'betterdocs_analytics';
        $daily  = $wpdb->prefix . 'betterdocs_analytics_daily';

        // If either table is missing there is nothing to migrate — mark done.
        $has_legacy = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy ) );
        $has_daily  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $daily ) );
        if ( ! $has_legacy || ! $has_daily ) {
            update_option( 'betterdocs_analytics_legacy_backfilled', 1, false );
            return;
        }

        $this->with_migration_lock( $wpdb->prefix . 'bd_bf_analytics', function () use ( $wpdb, $legacy, $daily ) {
            // Re-check inside the lock — the request that held it may have just finished.
            if ( get_option( 'betterdocs_analytics_legacy_backfilled' ) ) {
                return;
            }

            // Chunk by post so each INSERT is short-lived. A single table-wide
            // INSERT ... SELECT ... NOT EXISTS gap-locks the whole daily table and
            // deadlocked against the live aggregator writing the same table.
            $post_ids = array_map(
                'intval',
                (array) $wpdb->get_col( "SELECT DISTINCT post_id FROM {$legacy} WHERE post_id > 0 AND created_at <> '0000-00-00'" )
            );

            $all_ok = true;
            foreach ( array_chunk( $post_ids, 200 ) as $chunk ) {
                $in = implode( ',', $chunk );
                // Map legacy columns onto the daily rollup (kb_id/lang default to 0/'').
                // Dedupe on (post_id, stat_date) — NOT the table's 4-column unique key:
                // pipeline rows carry kb_id/lang, so INSERT IGNORE alone can never
                // collide with them and the same day's views would land twice (QA #15).
                // NOT EXISTS keeps each chunk idempotent, so a retried run is safe.
                $ok = $this->query_with_deadlock_retry(
                    "INSERT INTO {$daily}
                        ( post_id, kb_id, lang, views, unique_views, happy, sad, normal, avg_scroll_depth, reading_completions, stat_date )
                     SELECT l.post_id, 0, '', SUM( l.impressions ), SUM( l.unique_visit ), SUM( l.happy ), SUM( l.sad ), SUM( l.normal ), 0.00, 0, l.created_at
                     FROM {$legacy} l
                     WHERE l.post_id IN ({$in}) AND l.created_at <> '0000-00-00'
                       AND NOT EXISTS (
                           SELECT 1 FROM {$daily} d
                           WHERE d.post_id = l.post_id AND d.stat_date = l.created_at
                       )
                     GROUP BY l.post_id, l.created_at"
                );
                $all_ok = $all_ok && $ok;
            }

            // Only mark done when every chunk landed; otherwise a later request
            // retries the unfinished chunks (idempotent via NOT EXISTS).
            if ( $all_ok ) {
                update_option( 'betterdocs_analytics_legacy_backfilled', 1, false );
            }
        } );
    }

    /**
     * One-shot repair for installs where the original backfill double-counted:
     * its INSERT IGNORE only deduped on the full (post, kb, lang, date) key, so a
     * kb_id=0/lang='' backfill row could coexist with the pipeline's kb/lang row
     * for the same (post, day) — the same visits summed twice in every report.
     * Where such a pipeline twin exists, the backfill row is redundant (Free's
     * tracker and the event pipeline recorded the same views) — drop it.
     */
    public function maybe_repair_duplicate_daily_rows() {
        if ( get_option( 'betterdocs_analytics_daily_deduped' ) ) {
            return;
        }

        global $wpdb;

        $daily = $wpdb->prefix . 'betterdocs_analytics_daily';
        if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $daily ) ) ) {
            update_option( 'betterdocs_analytics_daily_deduped', 1, false );
            return;
        }

        $this->with_migration_lock( $wpdb->prefix . 'bd_bf_dedupe', function () use ( $wpdb, $daily ) {
            if ( get_option( 'betterdocs_analytics_daily_deduped' ) ) {
                return;
            }
            // Idempotent DELETE (a re-run finds nothing) — retry on deadlock vs the
            // live aggregator, and only record completion once it succeeds.
            $ok = $this->query_with_deadlock_retry(
                "DELETE b FROM {$daily} b
                 JOIN {$daily} p
                   ON p.post_id = b.post_id AND p.stat_date = b.stat_date AND p.id <> b.id
                 WHERE b.kb_id = 0 AND b.lang = ''
                   AND ( p.kb_id <> 0 OR p.lang <> '' )"
            );
            if ( $ok ) {
                update_option( 'betterdocs_analytics_daily_deduped', 1, false );
            }
        } );
    }

    /**
     * One-time expansion of legacy per-day reaction COUNTS
     * ({prefix}betterdocs_analytics happy/sad/normal) into per-item
     * {prefix}betterdocs_analytics_feedback rows, so the Feedback Inbox shows the
     * reaction history collected before the Advanced-Analytics update (which only
     * captures per-item rows going forward).
     *
     * Legacy data is per-day aggregate counts, so each count of N is fanned out
     * into N synthetic rows. Those rows carry no comment, status = 'resolved'
     * (already-triaged history, so they don't inflate the "New" badge) and
     * created_at = that day at 12:00:00. There is no natural UNIQUE key to dedupe
     * on, so this MUST stay strictly one-shot — guarded by its own option.
     */
    public function maybe_backfill_legacy_feedback() {
        if ( get_option( 'betterdocs_feedback_legacy_backfilled' ) ) {
            return;
        }

        global $wpdb;

        $legacy   = $wpdb->prefix . 'betterdocs_analytics';
        $feedback = $wpdb->prefix . 'betterdocs_analytics_feedback';

        $has_legacy   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy ) );
        $has_feedback = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $feedback ) );
        if ( ! $has_legacy || ! $has_feedback ) {
            update_option( 'betterdocs_feedback_legacy_backfilled', 1, false );
            return;
        }

        $this->with_migration_lock( $wpdb->prefix . 'bd_bf_feedback', function () use ( $wpdb, $legacy, $feedback ) {
            if ( get_option( 'betterdocs_feedback_legacy_backfilled' ) ) {
                return;
            }

            // A 0..99 numbers table lets a COUNT of N fan out into N rows in pure SQL.
            // Per-doc-per-day reaction counts above 99 are clipped (acceptable for a
            // one-time legacy snapshot).
            $tally   = '(SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4'
                     . ' UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ';
            $numbers = "(SELECT ( a.n + b.n * 10 ) AS seq FROM {$tally} a, {$tally} b) num";

            $statements = [];
            foreach ( [ 'happy', 'sad', 'normal' ] as $feeling ) {
                // $feeling is a hardcoded allowlisted column name — safe to interpolate.
                $statements[] = $wpdb->prepare(
                    "INSERT INTO {$feedback}
                        ( post_id, feeling, comment, session_hash, status, kb_id, lang, created_at )
                     SELECT l.post_id, %s, NULL, NULL, 'resolved', 0, '', CONCAT( l.created_at, ' 12:00:00' )
                     FROM {$legacy} l
                     JOIN {$numbers} ON num.seq < l.{$feeling}
                     WHERE l.post_id > 0 AND l.created_at <> '0000-00-00' AND l.{$feeling} > 0",
                    $feeling
                );
            }

            // This backfill has no natural unique key (it fans counts into rows), so it
            // must be strictly all-or-nothing: run the three inserts in one transaction
            // and only record completion on COMMIT — otherwise a retried run would
            // double the rows. Retry the whole transaction on deadlock; errors hidden.
            $prev = $wpdb->hide_errors();
            $done = false;
            try {
                for ( $attempt = 1; $attempt <= 4; $attempt++ ) {
                    $wpdb->query( 'START TRANSACTION' );
                    $err = '';
                    foreach ( $statements as $sql ) {
                        $wpdb->query( $sql );
                        if ( '' !== (string) $wpdb->last_error ) {
                            $err = (string) $wpdb->last_error;
                            break;
                        }
                    }
                    if ( '' === $err ) {
                        $wpdb->query( 'COMMIT' );
                        $done = true;
                        break;
                    }
                    $wpdb->query( 'ROLLBACK' );
                    if ( false === stripos( $err, 'deadlock' ) ) {
                        break; // non-retryable
                    }
                    usleep( 150000 * $attempt );
                }
            } finally {
                $wpdb->show_errors( $prev );
            }

            if ( $done ) {
                update_option( 'betterdocs_feedback_legacy_backfilled', 1, false );
            }
        } );
    }
}
