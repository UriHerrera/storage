<?php

namespace WPDeveloper\BetterDocsPro\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The encyclopedia's alphabet index is a hand-built aggregation over posts and
// glossary terms, with optional WPML/Polylang joins spliced in — the dynamic
// fragments are internal, and only the letter is user-influenced (bound via
// $wpdb->prepare).
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

use WPDeveloper\BetterDocs\Utils\Helper;

/**
 * Query layer behind the Encyclopedia's A–Z index.
 *
 * Encyclopedia is a Pro-only feature — the `enable_encyclopedia` setting is
 * flagged `is_pro`, and the block, Elementor widget, shortcode and customizer
 * sections that render it all ship in Pro. These methods are its engine: they
 * build the per-letter listing (from either `docs` posts or `glossaries` terms,
 * depending on `encyclopedia_source`) and the alphabet range itself, including
 * the non-Latin scripts.
 *
 * Generic utilities they lean on — language detection, excerpt building — stay
 * in Free's `Utils\Helper` and are called through it.
 *
 * @since 4.2.3 Moved from Free's `WPDeveloper\BetterDocs\Utils\Helper`, where
 *              they had no callers at all: every call site was here in Pro.
 */
class EncyclopediaQuery {
    public static function get_current_letter_docs( $current_letter, $limit = 0 ) {
    	global $wpdb;

    	$limit     = absint( $limit );
    	$limit_sql = $limit > 0 ? $wpdb->prepare( 'LIMIT %d', $limit ) : '';

    	// Check if the encyclopedia_prefix parameter is set

        $encyclopeia_suorce     = betterdocs()->settings->get( 'encyclopedia_source', 'docs' );
        $enable_glossaries      = betterdocs()->settings->get( 'enable_glossaries', false );
        $encyclopedia_root_slug = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopdia' );
        // Sanitize values that may be interpolated into raw SQL fragments below.
        $encyclopedia_root_slug = sanitize_title( $encyclopedia_root_slug );

    	// if($enable_glossaries && $encyclopeia_suorce === 'glossaries'){
    	if ( $enable_glossaries && $encyclopeia_suorce === 'glossaries' ) {
    		$lang_join = '';
    		$lang_where = '';

    		// Add language filtering if multilingual plugin is active and we should apply filtering
    		$current_language = Helper::get_current_language();
    		if ( $current_language && Helper::is_multilingual_active() && Helper::should_apply_language_filtering() ) {
    			// Restrict language code to a safe character set before SQL interpolation.
    			$current_language = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $current_language );
    			// For WPML, use icl_translations table
    			if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
    				$lang_join = " LEFT JOIN {$wpdb->prefix}icl_translations icl_t ON icl_t.element_id = t.term_id AND icl_t.element_type = 'tax_glossaries'";
    				$lang_where = " AND (icl_t.language_code = '$current_language' OR icl_t.language_code IS NULL)";
    			}
    			// For Polylang, use term_relationships with language taxonomy
    			elseif ( function_exists( 'pll_current_language' ) ) {
    				$lang_join = " LEFT JOIN {$wpdb->term_relationships} tr ON t.term_id = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt_lang ON tr.term_taxonomy_id = tt_lang.term_taxonomy_id AND tt_lang.taxonomy = 'language' LEFT JOIN {$wpdb->terms} t_lang ON tt_lang.term_id = t_lang.term_id";
    				$lang_where = " AND (t_lang.slug = '$current_language' OR t_lang.slug IS NULL)";
    			}
    		}

    		$query = "
                SELECT
                    t.term_id,
                    t.name AS post_title,
                    t.slug as slug,
                    '' AS post_excerpt,
                    CONCAT('" . get_home_url() . "/$encyclopedia_root_slug/', t.slug) AS permalink,
                    tt.description AS post_content,
                    JSON_OBJECT(
                        'status', COALESCE(MAX(CASE WHEN m.meta_key = 'status' THEN m.meta_value END), ''),
                        'glossary_term_description', COALESCE(MAX(CASE WHEN m.meta_key = 'glossary_term_description' THEN m.meta_value END), '')
                    ) AS meta_data
                FROM
                    {$wpdb->terms} t
                INNER JOIN
                    {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN
                    {$wpdb->termmeta} m ON t.term_id = m.term_id
                $lang_join
                WHERE
                    tt.taxonomy = 'glossaries'
                AND
                    SUBSTRING(t.name, 1, 1) = %s
                $lang_where
                GROUP BY
                    t.term_id
                ORDER BY
                    t.name ASC, t.term_id ASC
                $limit_sql
            ";
    	} else {
    		$lang_join = '';
    		$lang_where = '';

    		// Add language filtering for docs if multilingual plugin is active and we should apply filtering
    		$current_language = Helper::get_current_language();
    		if ( $current_language && Helper::is_multilingual_active() && Helper::should_apply_language_filtering() ) {
    			// Restrict language code to a safe character set before SQL interpolation.
    			$current_language = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $current_language );
    			// For WPML, use icl_translations table
    			if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
    				$lang_join = " LEFT JOIN {$wpdb->prefix}icl_translations icl_t ON icl_t.element_id = {$wpdb->posts}.ID AND icl_t.element_type = 'post_docs'";
    				$lang_where = " AND (icl_t.language_code = '$current_language' OR icl_t.language_code IS NULL)";
    			}
    			// For Polylang, use term_relationships with language taxonomy
    			elseif ( function_exists( 'pll_current_language' ) ) {
    				$lang_join = " LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt_lang ON tr.term_taxonomy_id = tt_lang.term_taxonomy_id AND tt_lang.taxonomy = 'language' LEFT JOIN {$wpdb->terms} t_lang ON tt_lang.term_id = t_lang.term_id";
    				$lang_where = " AND (t_lang.slug = '$current_language' OR t_lang.slug IS NULL)";
    			}
    		}

    		$query = "
                SELECT ID, post_title, post_excerpt, guid, post_content
                FROM {$wpdb->posts}
                $lang_join
                WHERE post_type = 'docs'
                AND post_status = 'publish'
                AND SUBSTRING(post_title, 1, 1) = %s
                $lang_where
                ORDER BY post_title ASC, ID ASC
                $limit_sql
            ";
    	}

    	$current_letter_docs = $wpdb->get_results( $wpdb->prepare( $query, $current_letter ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    	return $current_letter_docs;
    }

    public static function docs_sort_by_letter( $limit = 10 ) {
        global $wpdb;
        $enable_non_latin = betterdocs()->settings->get( 'encyclopedia_enable_non_latin' );
        $script           = betterdocs()->settings->get( 'encyclopedia_non_latin_option' );
        $letters          = self::get_character_range( $enable_non_latin, $script );

        $docs_by_letter     = [];
        $encyclopeia_suorce = betterdocs()->settings->get( 'encyclopedia_source', 'docs' );
        $enable_glossaries  = betterdocs()->settings->get( 'enable_glossaries', false );

        foreach ( $letters as $letter ) {
            $posts = self::get_current_letter_docs( $letter, $limit );

            if ( is_array( $posts ) && ! empty( $posts ) ) {
                foreach ( $posts as $post ) {
                    $description               = isset($post['meta_data']) ? \json_decode( $post['meta_data'], true ) : '';
                    $glossary_term_description = $description['glossary_term_description'] ?? '';

                    // Remove any <p> tags or other unwanted HTML tags
                    $glossary_term_description = wp_strip_all_tags( $glossary_term_description );
                    $post_excerpt              = wp_strip_all_tags( $post['post_excerpt'] ?? '' );

                    // Prepare post data
                    if ( $enable_glossaries && $encyclopeia_suorce === 'glossaries' ) {
                        // For glossaries
                        $permalink = '';

                        if ( isset( $post['slug'] ) ) {
                            $term_link = get_term_link( $post['slug'], 'glossaries' );

                            if ( ! is_wp_error( $term_link ) ) {
                                $permalink = $term_link;
                            }
                        }

                        $post_data = [
                            'id'           => $post['term_id'] ?? '',
                            'post_title'   => $post['post_title'] ?? '',
                            'post_excerpt' => ! empty( $post_excerpt )
                            ? $post_excerpt
                            : ( ! empty( $glossary_term_description )
                                ? Helper::get_custom_excerpt( $glossary_term_description, 15 )
                                : Helper::get_custom_excerpt( wp_strip_all_tags( $post['post_content'] ?? '' ), 15 ) ),
                            'permalink'    => $permalink,
                        ];
                    } else {
                        // For docs
                        $post_data = [
                            'id'           => $post['ID'] ?? '',
                            'post_title'   => $post['post_title'] ?? '',
                            'post_excerpt' => ! empty( $post_excerpt )
                            ? $post_excerpt
                            : Helper::get_custom_excerpt( wp_strip_all_tags( $post['post_content'] ?? '' ), 15 ),
                            'permalink'    => isset( $post['ID'] ) ? get_the_permalink( $post['ID'] ) : ''
                        ];
                    }

                    $docs_by_letter[$letter][] = $post_data;
                }
            }
        }

        return $docs_by_letter;
    }

    public static function get_character_range( $enable_non_latin, $script ) {
        if ( $enable_non_latin ) {
            switch ( $script ) {
                case 'arabic':
                    return self::unicodeRange( 'ء', 'ي' );
                case 'cyrillic':
                    return self::unicodeRange( 'А', 'Я' );
                case 'hebrew':
                    return self::unicodeRange( 'א', 'ת' );
                case 'greek':
                    return self::unicodeRange( 'Α', 'Ω' );
                default:
                    return range( 'A', 'Z' );
            }
        }

        return range( 'A', 'Z' );
    }

    public static function unicodeRange( $start, $end ) {
        $range = [];
        for ( $i = self::mb_ord_fallback( $start ); $i <= self::mb_ord_fallback( $end ); $i++ ) {
            $range[] = self::mb_chr_fallback( $i );
        }
        return $range;
    }

    public static function mb_ord_fallback( $char ) {
        $code = unpack( 'N', mb_convert_encoding( $char, 'UCS-4BE', 'UTF-8' ) );
        return $code[1];
    }

    public static function mb_chr_fallback( $code ) {
        return mb_convert_encoding( pack( 'N', $code ), 'UTF-8', 'UCS-4BE' );
    }}
