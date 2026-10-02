<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;

class Migration extends Base {
    /**
     * Database
     * @var Database
     */
    private $database;

    public function __construct( Database $database ) {
        $this->database = $database;
    }

    public function init( $version ) {
        if( $version > 250 ) {
            for( $_version = 250; $_version <= $version; $_version++ ) {
                if( method_exists( $this, "v$_version") ) {
                    call_user_func([$this, "v$_version"]);
                }
            }
        }

        /**
         * Settings Migration
         */
        betterdocs()->settings->migration( $version );

        /**
         * License Migration
         */
        $this->license_migration();
    }

    public function v252(){
        $this->flush();
    }

    /**
     * One-time cleanup of attachment / related-article post meta that a
     * long-standing absint() bug corrupted to [0,0] on save (see
     * WPDevelopers/betterdocs-pro#51). The save and render paths are fixed in
     * Admin.php and the templates, but rows saved before the fix still hold the
     * coerced zeros: on affected sites they render nothing where the attachment
     * box should be and show "(.undefined)" rows in the editor until the doc is
     * re-saved. Scrub them once so those sites self-heal without a manual
     * re-save of every doc.
     *
     * Guarded by a one-time option because init()'s loop re-invokes every
     * matching v### method on every later upgrade (250 → target).
     */
    public function v392(){
        if ( get_option( 'betterdocs_pro_attachment_meta_cleaned' ) ) {
            return;
        }

        $this->cleanup_corrupt_meta( '_betterdocs_attachments', [ Admin::class, 'sanitize_attachments_meta' ] );
        $this->cleanup_corrupt_meta( '_betterdocs_related_articles', [ Admin::class, 'sanitize_json_object_meta' ] );

        update_option( 'betterdocs_pro_attachment_meta_cleaned', 1, false );
    }

    /**
     * Re-sanitize every stored value for a meta key with the same sanitizer the
     * save path uses, and rewrite only the rows that actually change.
     *
     * The value read back from the DB is unslashed, so it is re-slashed before
     * update_post_meta() (which unslashes internally) to round-trip any
     * backslashes in valid entries losslessly.
     *
     * @param string   $meta_key  Post meta key to scrub.
     * @param callable $sanitizer Sanitizer returning the cleaned array.
     */
    private function cleanup_corrupt_meta( $meta_key, $sanitizer ){
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
                $meta_key
            )
        );

        if ( empty( $rows ) ) {
            return;
        }

        foreach ( $rows as $row ) {
            $value = maybe_unserialize( $row->meta_value );

            if ( ! is_array( $value ) ) {
                continue;
            }

            $cleaned = call_user_func( $sanitizer, $value );

            if ( $cleaned !== $value ) {
                update_post_meta( $row->post_id, $meta_key, wp_slash( $cleaned ) );
            }
        }
    }

    public function v250(){
        // Settings migration
        betterdocs()->settings->v250();

        $this->flush();
    }

    /**
     * Licensing DB Migration
     */
    public function license_migration(){
        $_has_license = get_option( BETTERDOCS_PRO_SL_DB_PREFIX . '_license', false );

        if( $_has_license ) {
            return;
        }

        $old_license_key = get_option( 'betterdocs-pro-license-key', '' );
        if( ! empty( $old_license_key ) ) {
            update_option( BETTERDOCS_PRO_SL_DB_PREFIX . '_license', $old_license_key, 'no' );

            $license_status = get_option( 'betterdocs-pro-license-status' );
            update_option( BETTERDOCS_PRO_SL_DB_PREFIX . '_license_status', $license_status, 'no' );

            $license_data = get_transient( 'betterdocs-pro-license_data' );
            if( $license_data ) {
                set_transient( BETTERDOCS_PRO_SL_DB_PREFIX . '_license_data', $license_data, MONTH_IN_SECONDS * 3 );
            }
        }
    }

    // Flush Rewrite Rules
    private function flush(){
        set_transient( 'betterdocs_flush_rewrite_rules', true );
    }
}
