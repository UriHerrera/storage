<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Core\Settings;

/**
 * Real-time Related Docs Panel - User Journey Tracker
 * 
 * This class handles tracking user interactions and session context
 * for generating intelligent related document recommendations.
 */
class RelatedDocsTracker extends Base {

    /**
     * Hard caps for the public tracking endpoint. The client batches roughly
     * `journey_batch_size` (default 10) interactions per request, so these are
     * far above legitimate use while still bounding what one request can write.
     */
    const MAX_INTERACTIONS_PER_REQUEST = 50;
    const MAX_SESSION_ID_LENGTH        = 64;
    const MAX_USER_AGENT_LENGTH        = 255;
    // Matches the ip_address column width. A sha256 hex digest (the anonymised
    // form) is exactly 64 chars and a raw IPv6 address is at most 45, so this
    // never truncates a well-formed value — it only stops an over-long one from
    // failing the INSERT under MySQL strict mode.
    const MAX_IP_LENGTH                = 64;

    /**
     * Event types this endpoint accepts.
     *
     * Anything else used to reach the INSERT with an empty payload, so an
     * attacker could write unlimited rows carrying arbitrary event names.
     * Must stay in sync with the cases in sanitize_event_data().
     */
    const ALLOWED_EVENT_TYPES = [
        'page_view',
        'scroll_depth',
        'internal_click',
        'search_query'
    ];

    /**
     * Settings instance
     *
     * @var Settings
     */
    private $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;

        // Initialize hooks
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tracking_script' ] );
        add_action( 'wp_ajax_betterdocs_track_user_journey', [ $this, 'ajax_track_user_journey' ] );
        add_action( 'wp_ajax_nopriv_betterdocs_track_user_journey', [ $this, 'ajax_track_user_journey' ] );

        // Ensure tables exist when tracking is needed
        add_action( 'wp_loaded', [ $this, 'ensure_tables_exist' ] );

        // Daily cleanup of stale tracking rows (honors data_retention_days setting).
        add_action( 'init', [ $this, 'maybe_schedule_cleanup' ] );
        add_action( 'betterdocs_related_docs_cleanup', [ $this, 'run_cleanup' ] );
    }

    /**
     * Schedule the daily cleanup once if not already scheduled.
     */
    public function maybe_schedule_cleanup() {
        if ( ! wp_next_scheduled( 'betterdocs_related_docs_cleanup' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'betterdocs_related_docs_cleanup' );
        }
    }

    /**
     * Delete tracking rows older than data_retention_days from both tables.
     * Without this, wp_betterdocs_user_journeys grows unbounded — defeating
     * the privacy/retention promise made in the settings UI.
     */
    public function run_cleanup() {
        global $wpdb;

        // Default 0 = keep forever, matching the Analytics → Settings default and
        // AnalyticsRetention. An unset setting (e.g. an existing site that never
        // opened the new Analytics → Settings tab) must NOT silently purge journey
        // rows — the `$days < 1` guard below short-circuits the DELETEs.
        $days = (int) $this->settings->get( 'data_retention_days', 0 );
        if ( $days < 1 ) {
            return;
        }

        $journeys    = $wpdb->prefix . 'betterdocs_user_journeys';
        $suggestions = $wpdb->prefix . 'betterdocs_related_suggestions';

        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$journeys} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$suggestions} WHERE updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Enqueue tracking script on docs pages
     */
    public function enqueue_tracking_script() {
        // Only enqueue on docs pages and if feature is enabled
        if ( ! is_singular( 'docs' ) || ! $this->is_tracking_enabled() ) {
            return;
        }

        global $post;

        // Enqueue the tracking script
        betterdocs_pro()->assets->enqueue( 'betterdocs-related-docs-tracker', 'public/js/related-docs-tracker.js', [ 'jquery' ] );

        // Localize script with settings and data
        betterdocs_pro()->assets->localize( 'betterdocs-related-docs-tracker', 'betterdocsRelatedDocsTracker', [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'betterdocs_track_user_journey' ),
            'post_id'     => $post->ID,
            'settings'    => $this->get_tracking_settings(),
            'debug'       => defined( 'WP_DEBUG' ) && WP_DEBUG
        ] );
    }

    /**
     * Check if tracking is enabled.
     *
     * Realtime tracking lives under the master "Show Related Docs" toggle —
     * if the parent feature is off, we must not enqueue the tracker script
     * or schedule cleanup crons that won't have anything to clean up.
     */
    private function is_tracking_enabled() {
        return $this->settings->get( 'show_related_docs', false )
            && $this->settings->get( 'enable_realtime_related_docs', false );
    }

    /**
     * Get tracking settings
     */
    private function get_tracking_settings() {
        return [
            'track_page_views'    => $this->settings->get( 'track_page_views', true ),
            'track_scroll_depth'  => $this->settings->get( 'track_scroll_depth', true ),
            'track_internal_clicks' => $this->settings->get( 'track_internal_clicks', true ),
            'track_search_queries' => $this->settings->get( 'track_search_queries', true ),
            'batch_size'          => $this->settings->get( 'journey_batch_size', 10 ),
            'batch_interval'      => $this->settings->get( 'journey_batch_interval', 15000 ), // 15 seconds
            'session_timeout'     => $this->settings->get( 'journey_session_timeout', 1800 ), // 30 minutes
        ];
    }

    /**
     * AJAX handler for tracking user journey data
     */
    public function ajax_track_user_journey() {
        // Honour the feature toggle on the WRITE path, not just the enqueue path.
        // enqueue_tracking_script() stops emitting the script when the feature is
        // off, but the wp_ajax_nopriv_ handler stayed registered and the nonce
        // stays valid for its full lifetime — so a nonce scraped while the feature
        // was on kept writing journey rows after an admin turned it off.
        if ( ! $this->is_tracking_enabled() ) {
            wp_send_json_error( [ 'message' => 'Tracking disabled' ], 403 );
        }

        // Verify nonce
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'betterdocs_track_user_journey' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
        }

        // Throttle: cap at 60 requests/min per client IP. Endpoint is
        // wp_ajax_nopriv, nonce is on every docs page — without this an attacker
        // can scrape one nonce and bloat wp_betterdocs_user_journeys forever.
        $session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );

        // Bound the client-chosen session identifier: it is stored on every row
        // and previously formed part of the rate-limit key.
        if ( strlen( $session_id ) > self::MAX_SESSION_ID_LENGTH ) {
            wp_send_json_error( [ 'message' => 'Invalid data provided' ] );
        }

        if ( ! $this->within_rate_limit() ) {
            wp_send_json_error( [ 'message' => 'Rate limit exceeded' ], 429 );
        }

        // Beacon path serializes interactions as a JSON string (see related-docs-tracker.js
        // sendBatch). The regular jQuery AJAX path posts a real array. Normalize both.
        $raw_interactions = isset( $_POST['interactions'] ) ? wp_unslash( $_POST['interactions'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( is_string( $raw_interactions ) ) {
            $decoded          = json_decode( $raw_interactions, true );
            $raw_interactions = is_array( $decoded ) ? $decoded : [];
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );

        if ( empty( $raw_interactions ) || empty( $session_id ) || ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Invalid data provided' ] );
        }

        // One request may not carry an unbounded number of interactions — each
        // one becomes its own persistent row.
        if ( count( $raw_interactions ) > self::MAX_INTERACTIONS_PER_REQUEST ) {
            wp_send_json_error( [ 'message' => 'Invalid data provided' ] );
        }

        // The tracker only ever reports on the public docs page the visitor is
        // reading. Requiring a published docs post stops arbitrary integers (and
        // draft/private IDs) from being recorded as journey targets.
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'docs' || $post->post_status !== 'publish' ) {
            wp_send_json_error( [ 'message' => 'Invalid data provided' ] );
        }

        $saved_count = 0;
        foreach ( $raw_interactions as $interaction ) {
            if ( ! is_array( $interaction ) ) {
                continue;
            }
            if ( $this->save_interaction( $session_id, $post_id, $interaction ) ) {
                $saved_count++;
            }
        }

        wp_send_json_success( [
            'message' => 'Journey data saved successfully',
            'saved_count' => $saved_count
        ] );
    }

    /**
     * Per-IP rate limit. Returns false once the bucket is over the cap.
     *
     * The bucket used to include the client-supplied session_id, so simply
     * rotating that value on every request minted a fresh bucket and the cap
     * never applied. Only REMOTE_ADDR — a value the client cannot choose —
     * decides the bucket now. A missing REMOTE_ADDR fails closed rather than
     * granting every such request its own unlimited bucket.
     */
    private function within_rate_limit() {
        $remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( $remote_ip === '' ) {
            return false;
        }

        $bucket    = 'bd_rdt_rl_' . md5( $remote_ip );
        $count     = (int) get_transient( $bucket );
        $cap       = 60; // requests per window
        $window    = MINUTE_IN_SECONDS;

        if ( $count >= $cap ) {
            return false;
        }

        // Re-set with same TTL each hit; first hit creates the window, later hits
        // top up the count without resetting the window via WP transient semantics.
        set_transient( $bucket, $count + 1, $window );

        return true;
    }

    /**
     * Save individual interaction to database
     */
    private function save_interaction( $session_id, $post_id, $interaction ) {
        global $wpdb;

        // Validate interaction data
        $event_type = sanitize_text_field( $interaction['type'] ?? '' );
        $event_data = $interaction['data'] ?? [];

        // Reject unrecognised event types outright. sanitize_event_data() dropped
        // their payload but the row was still inserted, so any non-empty string
        // was a free write into the journeys table.
        if ( empty( $event_type ) || ! in_array( $event_type, self::ALLOWED_EVENT_TYPES, true ) ) {
            return false;
        }

        // Sanitize event data based on type
        $sanitized_data = $this->sanitize_event_data( $event_type, $event_data );

        $table_name = $wpdb->prefix . 'betterdocs_user_journeys';

        $insert_data = [
            'session_id'  => $session_id,
            'user_id'     => get_current_user_id() ?: null,
            'article_id'  => $post_id,
            'event_type'  => $event_type,
            'event_data'  => wp_json_encode( $sanitized_data ),
            // Store only enough of the UA to identify a browser/platform. It is
            // client-controlled and unbounded, and these rows are retained
            // indefinitely by default, so keeping the full string is both a
            // storage and a fingerprinting concern.
            'user_agent'  => $this->minimal_user_agent(),
            'ip_address'  => $this->get_client_ip(),
            'created_at'  => current_time( 'mysql' )
        ];

        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            [ '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

        return $result !== false;
    }

    /**
     * Sanitize event data based on event type
     */
    private function sanitize_event_data( $event_type, $data ) {
        switch ( $event_type ) {
            case 'page_view':
                return [
                    'referrer' => sanitize_url( $data['referrer'] ?? '' ),
                    'timestamp' => intval( $data['timestamp'] ?? 0 )
                ];

            case 'scroll_depth':
                return [
                    'depth_percentage' => min( 100, max( 0, intval( $data['depth_percentage'] ?? 0 ) ) ),
                    'max_depth' => min( 100, max( 0, intval( $data['max_depth'] ?? 0 ) ) ),
                    'timestamp' => intval( $data['timestamp'] ?? 0 )
                ];

            case 'internal_click':
                return [
                    'target_url' => sanitize_url( $data['target_url'] ?? '' ),
                    'target_article_id' => intval( $data['target_article_id'] ?? 0 ),
                    'link_text' => sanitize_text_field( $data['link_text'] ?? '' ),
                    'timestamp' => intval( $data['timestamp'] ?? 0 )
                ];

            case 'search_query':
                return [
                    'query' => sanitize_text_field( $data['query'] ?? '' ),
                    'results_count' => intval( $data['results_count'] ?? 0 ),
                    'timestamp' => intval( $data['timestamp'] ?? 0 )
                ];

            default:
                // Unknown event_type — drop the payload rather than store
                // arbitrary client-supplied data unsanitized.
                return [];
        }
    }

    /**
     * Return a length-bounded user agent string for storage.
     *
     * The header is client-controlled and has no practical length limit, so an
     * unbounded copy of it is written to a table that keeps rows forever under
     * the default retention setting.
     *
     * @return string
     */
    private function minimal_user_agent() {
        if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return '';
        }

        $agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

        return substr( $agent, 0, self::MAX_USER_AGENT_LENGTH );
    }

    /**
     * Get the client IP, honoring the anonymize_ip_addresses setting.
     *
     * Only trusts REMOTE_ADDR — HTTP_CLIENT_IP / HTTP_X_FORWARDED_FOR are
     * client-controlled and would let an attacker spoof their hash bucket.
     * If a reverse proxy is in front of WP, REMOTE_ADDR will be the proxy's
     * IP — that's an infra concern, not a tracker concern.
     */
    private function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        if ( $this->settings->get( 'anonymize_ip_addresses', true ) ) {
            $ip = hash( 'sha256', $ip . wp_salt() );
        }

        return substr( $ip, 0, self::MAX_IP_LENGTH );
    }

    /**
     * Ensure database tables exist
     */
    public function ensure_tables_exist() {
        if ( ! $this->is_tracking_enabled() ) {
            return;
        }

        global $wpdb;
        
        $table_name = $wpdb->prefix . 'betterdocs_user_journeys';
        
        // Check if table exists
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if ( ! $table_exists ) {
            // Serialized: create_tables() builds EVERY Pro table, and this runs
            // on ordinary front-end requests, so concurrent visitors could each
            // decide the table was missing and issue the same CREATE — the loser
            // surfacing "Table ... already exists". GET_LOCK (timeout 0) lets one
            // request do it while the others simply move on.
            global $wpdb;

            $lock = $wpdb->prefix . 'bd_pro_db_update';
            $got  = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 0 )', $lock ) );

            if ( '0' === (string) $got ) {
                return; // Another request is building them right now.
            }

            try {
                // Re-check inside the lock — the holder before us may have just
                // created it.
                // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $exists_now = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
                // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

                if ( ! $exists_now ) {
                    $install = new Install();
                    $install->create_tables();
                }
            } finally {
                if ( '1' === (string) $got ) {
                    $wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $lock ) );
                }
            }
        }
    }

    /**
     * Get user journey data for analysis
     */
    public function get_user_journey_data( $article_id, $limit = 100, $date_range = '7 days' ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'betterdocs_user_journeys';
        
        $date_condition = '';
        if ( $date_range ) {
            $date_condition = $wpdb->prepare( 
                "AND created_at >= DATE_SUB(NOW(), INTERVAL %s)", 
                $date_range 
            );
        }

        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE article_id = %d {$date_condition}
             ORDER BY created_at DESC
             LIMIT %d",
            $article_id,
            $limit
        );

        return $wpdb->get_results( $query );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Get session context for recommendations
     */
    public function get_session_context( $session_id, $current_article_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'betterdocs_user_journeys';

        // Get recent session activity
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare(
            "SELECT article_id, event_type, event_data, created_at
             FROM {$table_name}
             WHERE session_id = %s
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
             ORDER BY created_at DESC
             LIMIT 20",
            $session_id
        );

        $session_data = $wpdb->get_results( $query );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return [
            'current_article_id' => $current_article_id,
            'recent_articles' => $this->extract_recent_articles( $session_data ),
            'search_queries' => $this->extract_search_queries( $session_data ),
            'engagement_level' => $this->calculate_engagement_level( $session_data )
        ];
    }

    /**
     * Extract recently viewed articles from session data
     */
    private function extract_recent_articles( $session_data ) {
        $articles = [];
        foreach ( $session_data as $data ) {
            if ( $data->event_type === 'page_view' && $data->article_id ) {
                $articles[] = $data->article_id;
            }
        }
        return array_unique( $articles );
    }

    /**
     * Extract search queries from session data
     */
    private function extract_search_queries( $session_data ) {
        $queries = [];
        foreach ( $session_data as $data ) {
            if ( $data->event_type === 'search_query' ) {
                $event_data = json_decode( $data->event_data, true );
                if ( ! empty( $event_data['query'] ) ) {
                    $queries[] = $event_data['query'];
                }
            }
        }
        return $queries;
    }

    /**
     * Calculate engagement level based on session data
     */
    private function calculate_engagement_level( $session_data ) {
        $scroll_depths = [];
        $page_views = 0;
        
        foreach ( $session_data as $data ) {
            if ( $data->event_type === 'scroll_depth' ) {
                $event_data = json_decode( $data->event_data, true );
                $scroll_depths[] = $event_data['depth_percentage'] ?? 0;
            } elseif ( $data->event_type === 'page_view' ) {
                $page_views++;
            }
        }

        $avg_scroll = empty( $scroll_depths ) ? 0 : array_sum( $scroll_depths ) / count( $scroll_depths );
        
        // Simple engagement scoring (0-100)
        return min( 100, ( $avg_scroll * 0.7 ) + ( $page_views * 10 ) );
    }
}
