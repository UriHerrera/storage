<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\CSSGenerator;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;


class Encyclopedia extends Base
{
    /**
     * Upper bound for any per-page/limit value accepted by the public
     * encyclopedia AJAX endpoints.
     */
    const MAX_PER_PAGE = 100;

    public $settings;

    /**
     * Clamp a client-supplied per-page/limit value into a usable range.
     *
     * These endpoints are registered on wp_ajax_nopriv and their values reach
     * EncyclopediaQuery::get_current_letter_docs(), which builds its SQL as
     * `$limit > 0 ? 'LIMIT %d' : ''` — so a submitted 0 (also what absint()
     * yields for any non-numeric input) dropped the LIMIT clause entirely and
     * returned every matching row. docs_sort_by_letter() then repeats that once
     * per letter of the alphabet.
     *
     * Zero is therefore treated as "unspecified" and falls back to the default
     * rather than meaning "unlimited".
     *
     * @param mixed $value   Raw request value.
     * @param int   $default Value to use when nothing usable was supplied.
     * @return int
     */
    protected function clamp_per_page( $value, $default )
    {
        $value = absint( $value );

        if ( $value < 1 ) {
            $value = $default;
        }

        return min( $value, self::MAX_PER_PAGE );
    }

    public function __construct()
    {

        add_action('wp_ajax_load_more_docs_section', [$this, 'load_more_docs_section']);
        add_action('wp_ajax_nopriv_load_more_docs_section', [$this, 'load_more_docs_section']);

        add_action('wp_ajax_load_more_docs', [$this, 'load_more_docs']);
        add_action('wp_ajax_nopriv_load_more_docs', [$this, 'load_more_docs']);

        // functions.php or your custom plugin file
        add_action('wp_ajax_get_current_letter_docs', [$this, 'get_current_letter_docs_callback']);
        add_action('wp_ajax_nopriv_get_current_letter_docs', [$this, 'get_current_letter_docs_callback']);

        // functions.php or your custom plugin file
        add_action('wp_ajax_docs_sort_by_letter', [$this, 'docs_sort_by_letter_callback']);
        add_action('wp_ajax_nopriv_docs_sort_by_letter', [$this, 'docs_sort_by_letter_callback']);

        // add_filter('template_include', [$this, 'custom_template_include']);

        $enable_encyclopedia = betterdocs()->settings->get('enable_encyclopedia', false);

        if ($enable_encyclopedia) {
            add_action('betterdocs::settings::saved', [$this, 'create_encyclopedia_page']);
        }
        add_action('save_post', [$this, 'update_title_slug'], 10, 3);

    }


    // Function to create an 'encyclopedia' page
    public function create_encyclopedia_page()
    {

        $title  = betterdocs()->settings->get('encyclopedia_page_title', 'Encyclopedia');
        $slug  = betterdocs()->settings->get('encyclopedia_root_slug', 'encyclopedia');

        $old_title = get_option('encyclopedia_page_title');
        $old_slug = get_option('encyclopedia_page_slug');


        $post = get_post(get_option('encyclopedia_page_id'));
        if (empty($post) || $post && $post->post_type !== 'page') {
            delete_option('encyclopedia_page_id');
        }

        if (empty(get_option('encyclopedia_page_id'))) {

            $page_args = array(
                'post_title'   => $title,
                'post_content' => '[betterdocs_encyclopedia]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $slug,
            );

            $encyclopedia_page_query = new \WP_Query(array(
                'post_type'              => 'page',
                'title'                  => $title,
                'post_status'            => 'all',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ));
            $encyclopedia_page = !empty($encyclopedia_page_query->posts) ? $encyclopedia_page_query->posts[0] : null;

            if (!$encyclopedia_page) {

                $page_id = wp_insert_post($page_args);

                update_option('encyclopedia_page_id', $page_id);
                update_option('encyclopedia_page_title', $title);
                update_option('encyclopedia_page_slug', $slug);

                return $page_id;
            } else {
                return $encyclopedia_page->ID;
            }
        } else {
            $page_data = array(
                'ID'           => get_option('encyclopedia_page_id'),
                'post_title'   => $title,
                'post_name'    => $slug
            );

            if ($old_title !== $title || $old_slug !== $slug) {
                wp_update_post($page_data);
                update_option('encyclopedia_page_title', $title);
                update_option('encyclopedia_page_slug', $slug);
            }
        }
    }


    public function update_title_slug($post_id, $post, $update)
    {
        $en_post_id = get_option('encyclopedia_page_id');
        $post = get_post($en_post_id);

        if ($post) {
            $post_title = $post->post_title;
            $post_slug = $post->post_name;
        }

        if ($post_id == $en_post_id) {
            $bd_settings = get_option('betterdocs_settings');
            $bd_settings['encyclopedia_page_title'] = $post_title;
            $bd_settings['encyclopedia_root_slug'] = $post_slug;

            update_option('betterdocs_settings', $bd_settings);
        }
    }


    public function load_more_docs_section()
    {

        $nonce = isset($_POST['_nonce']) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';
        $limit = $this->clamp_per_page( isset($_POST['section_per_page']) ? wp_unslash( $_POST['section_per_page'] ) : 0, 5 );
        $learn_more_text = isset($_POST['learn_more_text']) ? sanitize_text_field( wp_unslash( $_POST['learn_more_text'] ) ) : '';
        $explore_more_text = isset($_POST['explore_more_text']) ? sanitize_text_field( wp_unslash( $_POST['explore_more_text'] ) ) : '';

        $allowed_heading_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div'];
        $item_heading_tag = isset($_POST['item_heading_tag']) && in_array( sanitize_text_field( wp_unslash( $_POST['item_heading_tag'] ) ), $allowed_heading_tags, true)
            ? sanitize_text_field( wp_unslash( $_POST['item_heading_tag'] ) )
            : 'h2';

        //     'doc_style' => $doc_style,
        // 'dictionary_docs_per_page' => $dictionary_docs_per_page,
        // 'dictionary_loadmore' => $dictionary_loadmore,
        // 'docs_loadmore' => $dictionary_docs_loadmore,
        // 'current_letter' => $current_letter,
        // 'shown_sections' => $shown_sections,
        // 'total_section_pages' => $total_section_pages,
        // 'total_doc_pages' => $total_doc_pages,

        if (!wp_verify_nonce($nonce, 'encyclopedia_nonce')) {
            die('Invalid nonce');
        }

        $allowed_doc_styles = ['doc-grid', 'doc-list', 'doc-list-2'];
        $doc_style = isset($_POST['doc_style']) && in_array( sanitize_text_field( wp_unslash( $_POST['doc_style'] ) ), $allowed_doc_styles, true)
            ? sanitize_text_field( wp_unslash( $_POST['doc_style'] ) )
            : 'doc-grid';
        $docs_per_page = $this->clamp_per_page( isset($_POST['docs_per_page']) ? wp_unslash( $_POST['docs_per_page'] ) : 0, 10 );

        // if ($doc_style === 'doc-grid') {
        //     $start_letter_style = isset($_POST['start_letter_style']) ? $_POST['start_letter_style'] : 'alphabet-big-view';
        // } else {
        //     $start_letter_style = isset($_POST['start_letter_style_']) ? $_POST['start_letter_style_'] : 'alphabet-list-view';
        // }


        $allowed_letter_styles = ['alphabet-big-view', 'alphabet-big-round-view', 'alphabet-big-gradient-view', 'alphabet-list-view'];
        $start_letter_style = isset($_POST['start_letter_style']) && in_array( sanitize_text_field( wp_unslash( $_POST['start_letter_style'] ) ), $allowed_letter_styles, true)
            ? sanitize_text_field( wp_unslash( $_POST['start_letter_style'] ) )
            : 'alphabet-big-round-view';



        $page = isset($_POST['page']) ? intval( wp_unslash( $_POST['page'] ) ) : 1;
        $docs_by_letter = EncyclopediaQuery::docs_sort_by_letter($limit);

        ob_start();

        // Your code to generate additional content based on the page number goes here
        $max_sections = $limit;
        $shown_sections = 0;
        $available_sections = array_keys(array_filter($docs_by_letter));

        $alphabet_range = array_slice($available_sections, $page * $max_sections, $max_sections);

        foreach ($alphabet_range as $letter) {
            if ($shown_sections >= $max_sections) {
                break;
            }

            if (!empty($docs_by_letter[$letter])) {

                $explore_count = count(EncyclopediaQuery::get_current_letter_docs($letter, "")) - $docs_per_page;


                if ($start_letter_style === 'alphabet-list-view') {
                    betterdocs_pro()->views->get('layouts/encyclopedia/letter', [
                        'letter' => $letter,
                        'start_letter_style' => $start_letter_style
                    ]);
                }
                echo "<div class='encyclopedia-section-" . esc_attr($letter) . " section-item'>";

                if ($start_letter_style !== 'alphabet-list-view') {
                    betterdocs_pro()->views->get('layouts/encyclopedia/letter', [
                        'letter' => $letter,
                        'start_letter_style' => $start_letter_style
                    ]);
                }

                foreach ($docs_by_letter[$letter] as $doc) {
                    $excerpt = $doc['post_excerpt'];

                    betterdocs_pro()->views->get("layouts/encyclopedia/$doc_style", [
                        'doc' => $doc,
                        'excerpt' => $excerpt,
                        'dictionary_learn_more_text' => $learn_more_text,
                        'item_heading_tag' => $item_heading_tag,

                    ]);
                }

                betterdocs_pro()->views->get("layouts/encyclopedia/explore-count", [
                    'explore_count' => $explore_count,
                    'explore_url' => '?encyclopedia_prefix=' . $letter . '',
                    'doc_style' => $doc_style,
                    'dictionary_explore_more_text' => $explore_more_text,

                ]);

                echo '</div>';
                $shown_sections++;
            }
        }

        $output = ob_get_clean();

        wp_send_json_success($output);
    }


    public function load_more_docs()
    {

        $nonce = isset($_POST['_nonce']) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';

        $allowed_heading_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div'];
        $item_heading_tag = isset($_POST['item_heading_tag']) && in_array( sanitize_text_field( wp_unslash( $_POST['item_heading_tag'] ) ), $allowed_heading_tags, true)
            ? sanitize_text_field( wp_unslash( $_POST['item_heading_tag'] ) )
            : 'h2';

        if (!wp_verify_nonce($nonce, 'encyclopedia_nonce')) {
            die('Invalid nonce');
        }

        $page = isset($_POST['page']) ? intval( wp_unslash( $_POST['page'] ) ) : 0;

        $current_letter = isset($_POST['encyclopedia_prefix']) ? sanitize_text_field( wp_unslash( $_POST['encyclopedia_prefix'] ) ) : '';
        $allowed_doc_styles = ['doc-grid', 'doc-list', 'doc-list-2'];
        $doc_style = isset($_POST['doc_style']) && in_array( sanitize_text_field( wp_unslash( $_POST['doc_style'] ) ), $allowed_doc_styles, true)
            ? sanitize_text_field( wp_unslash( $_POST['doc_style'] ) )
            : 'doc-grid';
        $docs_per_page = $this->clamp_per_page( isset($_POST['docs_per_page']) ? wp_unslash( $_POST['docs_per_page'] ) : 0, 10 );
        $encyclopeia_suorce  = betterdocs()->settings->get('encyclopedia_source', 'docs');
        $enable_glossaries  = betterdocs()->settings->get('enable_glossaries', false);
        $encyclopedia_root_slug  = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopdia' );


        $max_docs = $docs_per_page;

        global $wpdb;

        if ($enable_glossaries && $encyclopeia_suorce === 'glossaries') {
            $query = "
                SELECT t.term_id, t.name AS post_title, '' AS post_excerpt, CONCAT('" . get_home_url() . "/$encyclopedia_root_slug/', t.slug) AS guid, tt.description AS post_content
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'glossaries'
                AND SUBSTRING(t.name, 1, 1) = %s
                ORDER BY t.name ASC, t.term_id ASC
            ";
        } else {
            $query = "
                SELECT ID, post_title, post_excerpt, guid, post_content
                FROM {$wpdb->posts}
                WHERE post_type = 'docs'
                AND post_status = 'publish'
                AND SUBSTRING(post_title, 1, 1) = %s
                ORDER BY post_title ASC, ID ASC
            ";
        }

        // Custom term/post lookup against core tables; query string is built from
        // safe literals and the only dynamic value is bound via $wpdb->prepare().
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $current_letter_docs = $wpdb->get_results($wpdb->prepare($query, $current_letter), ARRAY_A);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if (!empty($current_letter_docs)) {

            ob_start();

            // Assuming $page is zero-based
            $start_index = $page * $max_docs;

            $docs_range = array_slice($current_letter_docs, $start_index, $max_docs);

            foreach ($docs_range as $doc) {

                if ($start_index >= count($current_letter_docs)) {
                    break;
                }

                $excerpt = !empty($doc['post_excerpt']) ? $doc['post_excerpt'] : Helper::get_custom_excerpt($doc['post_content'], 15);

                if (empty($doc['permalink'])) {
                    if (isset($doc['ID'])) {
                        $doc['permalink'] = get_the_permalink($doc['ID']);
                    } else {
                        $doc['permalink'] = $doc['guid'];
                    }
                }

                betterdocs_pro()->views->get("layouts/encyclopedia/$doc_style", [
                    'doc' => $doc,
                    'excerpt' => $excerpt,
                    'item_heading_tag' => $item_heading_tag
                ]);
            }

            $page++;
        }

        $docs_output = ob_get_clean();

        wp_send_json_success($docs_output);
    }


    public function get_current_letter_docs_callback()
    {
        $currentLetter = isset($_POST['currentLetter']) ? sanitize_text_field( wp_unslash( $_POST['currentLetter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $limit = $this->clamp_per_page( isset($_POST['limit']) ? wp_unslash( $_POST['limit'] ) : 0, 10 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $currentLetterDocs = EncyclopediaQuery::get_current_letter_docs($currentLetter, $limit);

        wp_send_json($currentLetterDocs);
        wp_die();
    }


    public function docs_sort_by_letter_callback()
    {
        $limit = $this->clamp_per_page( isset($_POST['docs_limit']) ? wp_unslash( $_POST['docs_limit'] ) : 0, 10 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $docsByLetter = EncyclopediaQuery::docs_sort_by_letter($limit);
        wp_send_json($docsByLetter);
        wp_die();
    }

    public function custom_template_include($template)
    {

        if (is_tax('glossaries')) {
            // Use a custom taxonomy template for your custom taxonomy
            $new_template = plugin_dir_path(__FILE__) . 'templates/taxonomy-custom_taxonomy.php';
            if ($new_template != '') {
                return $new_template;
            }
        }

        // For other cases, return the original template
        return $template;
    }
}
