<?php

namespace WPDeveloper\BetterDocsPro\Utils;

// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in

use WPDeveloper\BetterDocsChatbot\Dependencies\WPDeveloper\WPBGProcess\AIChatbotBackgroundProcessing;

class Helper {
    public static function get_plugins( $plugin_basename = null ) {
        if ( ! function_exists( 'get_plugins' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        return $plugin_basename == null ? $plugins : isset( $plugins[$plugin_basename] );
    }

    public static function is_plugin_active( $plugin_basename ) {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active( $plugin_basename );
    }

    public static function strip_javascript_inline( $html ) {
        // Remove script tags and their contents
        $html = preg_replace( '/<script[^>]*>.*?<\/script>/is', '', $html );

        // Remove javascript: protocols but keep the anchor tags
        $html = preg_replace( '/href\s*=\s*(["\'])\s*javascript:.*?\1/i', 'href="#"', $html );

        // Remove all event handlers (onclick, onload, etc) but keep the elements
        $html = preg_replace( '/\s+on\w+\s*=\s*(["\'])?[^"\']*\1?/i', '', $html );

        // Remove any inline javascript: in attributes
        $html = preg_replace( '/javascript\s*:/i', '', $html );

        return $html;
    }

    public static function AIChatbot() {
        new AIChatbotBackgroundProcessing();
    }

    public static function remove_all_from_content_restriction_for_doc_categories( &$terms ) {
        foreach ( $terms as $term_id => $term_status ) {
            if ( $term_id == 'all' ) {
                unset( $terms['all'] );
            }
        }
    }

    /**
     * Function To Check If A String Is Unicode
     *
     * @param string $text
     * @return bool
     */
    public static function contains_unicode( $text ) {
        return preg_match( '/[^\x00-\x7F]/', $text );
    }

    public static function get_glossary_terms() {
        $default_params = [
            'taxonomy'   => 'glossaries',
            'order'      => 'asc',
            'orderby'    => 'meta_value_num',
            'hide_empty' => '',
            'meta_key'   => 'status'
        ];
        $terms              = get_terms( $default_params );
        $current_glossaries = [];

        foreach ( $terms as $result ) {
            $meta      = get_term_meta( $result->term_id );
            $term_data = [
                'term_id'          => $result->term_id,
                'name'             => $result->name,
                'slug'             => $result->slug,
                'term_group'       => $result->term_group,
                'term_taxonomy_id' => $result->term_taxonomy_id,
                'taxonomy'         => $result->taxonomy,
                'description'      => $result->description,
                'parent'           => $result->parent,
                'count'            => $result->count,
                'filter'           => $result->filter,
                'meta_data'        => $meta // add all meta here
            ];
            $current_glossaries[] = $term_data;
        }

        return $current_glossaries;
    }
}
