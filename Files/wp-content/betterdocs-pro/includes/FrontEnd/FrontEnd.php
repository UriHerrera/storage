<?php

namespace WPDeveloper\BetterDocsPro\FrontEnd;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Plugin;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocsPro\Core\InstantAnswer;
use WPDeveloper\BetterDocsPro\Core\Encyclopedia;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;
use WPDeveloper\BetterDocsPro\Core\ContentRestrictions;
use  WPDeveloper\BetterDocsPro\Core\AccessControl;
use WPDeveloper\BetterDocsPro\Core\Glossaries;
use WPDeveloper\BetterDocsPro\Utils\Helper;

class FrontEnd extends Base
{
    private $container;
    private $database;
    private $settings;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->database  = $this->container->get(Database::class);
        $this->settings  = $this->container->get(Settings::class);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // add_filter( 'betterdocs_not_eligible_archive', [$this, 'is_archive'] );
        add_filter('betterdocs_archives_template', [$this, 'archives_template'], 10, 4);
        add_filter('betterdocs_template_params', [$this, 'layout_3_template_params'], 11, 3);
        if ($this->settings->get('advance_search', false)) {
            add_filter('betterdocs_search_shortcode_attributes', [$this, 'search_shortcode_attributes'], 11, 1);
        }
        add_filter( 'betterdocs_search_form_attr', [$this, 'search_form_attr'], 11, 1 );
        add_filter( 'betterdocs_live_search_form_footer', [$this, 'advance_search_form'], 11, 1 );
        add_filter( 'betterdocs_after_live_search_form', [$this, 'popular_search_keyword'], 11, 1 );
        add_filter('select_live_search_template', [$this, 'render_search_template'], 10, 1);

        if( betterdocs()->settings->get('enable_content_restriction') && $this->settings->get('internal_knowledge_base_type') == 'basic' && isset( wp_get_current_user()->roles ) && ! in_array( 'administrator', wp_get_current_user()->roles ) ) { //for admin this feature will not work as admin can view all docs and categories
            $this->container->get( ContentRestrictions::class );
        }

        $this->container->get( InstantAnswer::class );

        if ($this->settings->get('show_attachment')) {
            add_action('betterdocs_docs_before_social', [$this, 'render_attachment_markup'], 11);
        }

        if ($this->settings->get('show_related_docs')) {
            add_action('betterdocs_docs_before_social', [$this, 'render_related_docs_shortcode'], 12);
        }

        if ($this->settings->get('enable_encyclopedia', false)) {
            $this->container->get(Encyclopedia::class);
        }
        if ( $this->settings->get( 'enable_glossaries', false ) ) {
            $this->container->get( Glossaries::class );
            add_filter('the_content', [$this, 'wrap_glossaries']);
        }
        
        // Set last_knowledge_base cookie for FSE themes (where archives_template filter doesn't run)
        add_action( 'template_redirect', [ $this, 'set_knowledge_base_cookie' ] );
    }
    
    /**
     * Set last_knowledge_base cookie for taxonomy archives
     * This is needed for FSE themes where the archives_template filter doesn't run
     */
    public function set_knowledge_base_cookie() {
        if ( is_tax( 'doc_category' ) ) {
            global $wp_query;
            $_kb_slug = isset( $wp_query->query['knowledge_base'] ) ? $wp_query->query['knowledge_base'] : null;
            if ( $_kb_slug ) {
                $this->set_last_knowledge_base_cookie( $_kb_slug );
            }
        } elseif ( is_tax( 'knowledge_base' ) ) {
            $object = get_queried_object();
            if ( $object && isset( $object->slug ) ) {
                $this->set_last_knowledge_base_cookie( $object->slug );
            }
        }
    }

    /**
     * Write the long-lived last_knowledge_base cookie with explicit transport
     * and scope attributes.
     *
     * The cookie lives for two years, so its defaults matter. Without Secure it
     * is transmitted in cleartext on any plain-HTTP request to the site; without
     * an explicit SameSite it falls back to browser-dependent behaviour and may
     * ride along on cross-site requests.
     *
     * Deliberately *not* HttpOnly: the value drives front-end knowledge base
     * selection and is read by the plugin's own scripts, so hiding it from
     * JavaScript would break that. It carries no authentication value — only
     * which KB was viewed last.
     *
     * @param string $slug Knowledge base slug.
     * @return void
     */
    protected function set_last_knowledge_base_cookie( $slug ) {
        // Never attempt a Set-Cookie once output has started.
        if ( headers_sent() ) {
            return;
        }

        setcookie(
            'last_knowledge_base',
            sanitize_title( $slug ),
            [
                'expires'  => time() + ( YEAR_IN_SECONDS * 2 ),
                'path'     => COOKIEPATH ? COOKIEPATH : '/',
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax'
            ]
        );
    }

    public function render_search_template($layout) {
        $multiple_kb_switch  = $this->settings->get( 'multiple_kb' );
        $multiple_kb_layout  = betterdocs()->customizer->defaults->get( 'betterdocs_multikb_layout_select' );
        $docs_layout         = betterdocs()->customizer->defaults->get( 'betterdocs_docs_layout_select' );
        $search_layout       = betterdocs()->customizer->defaults->get( 'betterdocs_search_layout_select' );

        if ( is_post_type_archive( 'docs' ) ) {
            if( $multiple_kb_switch && $multiple_kb_layout != 'layout-5' && ! $search_layout ) {
                $layout = 'layout-1';
            } else if( $multiple_kb_switch && $multiple_kb_layout == 'layout-5' && ! $search_layout ) {
                $layout = 'layout-2';
            }
        } else if ( is_tax( 'knowledge_base' ) ){
            if ( $docs_layout != "layout-7" && ! $search_layout ) {
                $layout = 'layout-1';
            } else if ( $docs_layout == 'layout-7' && ! $search_layout ) {
                $layout = 'layout-2';
            }
        } else if ( is_tax('glossaries') ) {
            if( ! $search_layout ) {
                $layout = 'layout-2';
            } else {
                $layout = $search_layout;
            }
        }

        return $layout;
    }

    public function render_attachment_markup() {
        echo do_shortcode( '[betterdocs_attachments]' );
    }

    public function render_related_docs_shortcode() {
        $single = betterdocs()->database->get_theme_mod( 'betterdocs_single_layout_select', 'layout-1' );
        if ( $single == 'layout-8' ) {
            echo do_shortcode( '[betterdocs_related_docs layout="layout-2"]' );
        } else {
            echo do_shortcode( '[betterdocs_related_docs]' );
        }
    }

    public function enqueue_scripts() {
        if (is_singular('docs')) {
            wp_enqueue_style('single-doc-attachments');
            wp_enqueue_style('single-doc-related-articles');
        }

        if (is_tax('knowledge_base')) {
            wp_enqueue_style('betterdocs-docs');
        }

        if (is_post_type_archive('docs') || is_singular('docs') || is_tax('doc_category') || is_tax('knowledge_base')) {
            wp_enqueue_script('betterdocs-pro');
        }
    }

    public function is_archive($is_archive) {
        return $is_archive || is_tax('knowledge_base');
    }

    public function archives_template($template, $layout, $_default_template, $views) {
        $_is_kb    = is_tax('knowledge_base');
        $_template = $template;

        if ( is_post_type_archive( 'docs' ) ) {
            if ( $this->settings->get( 'multiple_kb' ) ) {
                $kb_layout = $this->database->get_theme_mod( 'betterdocs_multikb_layout_select', 'layout-5' );
                $_template = 'templates/archives/mkb/' . $kb_layout;
            }
        } elseif (is_tax('doc_category')) {
            global $wp_query;
            $_kb_slug = isset($wp_query->query['knowledge_base']) ? $wp_query->query['knowledge_base'] : null;
            if ($_kb_slug) {
                $this->set_last_knowledge_base_cookie( $_kb_slug );
            }
            $category_layout = $this->database->get_theme_mod( 'betterdocs_archive_layout_select', 'layout-7' );
            
            if ( $category_layout == 'layout-6' || $category_layout == 'layout-7' || $category_layout == 'layout-8' ) {
                $_template       = 'templates/archives/categories/' . $category_layout;
            } else {
                $_template         = 'templates/taxonomy-doc_category';
            }
        }

        if ($_is_kb) {
            $object = get_queried_object();
            if ( $object && isset( $object->slug ) ) {
                $this->set_last_knowledge_base_cookie( $object->slug );
            }
        }

        if (!empty($_template)) {
            $eligible_template = $views->path($_template, $_default_template);

            if (file_exists($eligible_template)) {
                $template = &$eligible_template;
            }
        }

        return $template;
    }

    public function layout_3_template_params($params, $layout, $term) {
        if ($layout === 'layout-3') {
            $params['term_count'] = [
                'count'           => isset($params['term_count']['count']) ? $params['term_count']['count'] : 0,
                'prefix'          => '',
                'suffix'          => __( 'Docs', 'betterdocs-pro' ),
                'suffix_singular' => __( 'Doc', 'betterdocs-pro' )
            ];
        }

        return $params;
    }

    public function layout_3_header_sequence($_layout_sequence, $layout, $style_type, $term) {
        $_return_val = $_layout_sequence;

        if ($layout === 'layout-3' && $style_type == 'box') {
            $_count = array_pop($_return_val);

            $_return_val['description'] = function () use ($term) {
                betterdocs()->views->get('template-parts/common/description', [
                    'description' => $term->description
                ]);
            };

            $_return_val['count'] = $_count;
        }

        return $_return_val;
    }

    public function layout_filename($filename, $origin_layout) {
        $filename = ($origin_layout === 'layout-3') ? 'default' : $filename;
        return $filename;
    }

    public function search_form_attr($atts) {
        $search_button_text = betterdocs()->settings->get('search_button_text', __('Search', 'betterdocs-pro'));

        $atts['category_search']      = false;
        $atts['search_button']        = false;
        $atts['popular_search']       = false;
        $atts['popular_search_title'] = '';
        $atts['search_button_text']   = $search_button_text;

        return $atts;
    }

    public function search_shortcode_attributes($atts) {
        $atts['category_search']      = betterdocs()->customizer->defaults->get('betterdocs_category_search_toggle');
        $atts['search_button']        = betterdocs()->customizer->defaults->get('betterdocs_search_button_toggle');
        $atts['popular_search']       = betterdocs()->customizer->defaults->get('betterdocs_popular_search_toggle');
        $atts['popular_search_title'] = betterdocs()->customizer->defaults->get('betterdocs_popular_search_text');
        return $atts;
    }

    public function advance_search_form($attr) {
        return betterdocs()->views->get('template-parts/search/category-button', $attr['params']);
    }

    public function popular_search_keyword($attr) {
        return betterdocs()->views->get('template-parts/search/popular-keyword', $attr['params']);
    }


    public function fetch_glossaries($letter = '') {
        global $wpdb;

        // Get settings
        $encyclopedia_source = betterdocs()->settings->get('encyclopedia_source', 'docs');
        $enable_glossaries = betterdocs()->settings->get('enable_glossaries', false);
        $encyclopedia_root_slug  = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopdia' );


        // Check if glossaries are enabled and the source is 'glossaries'
        if (betterdocs()->is_pro_active() && $enable_glossaries && $encyclopedia_source === 'glossaries') {
            // Prepare the base query
            $query = "
                SELECT t.term_id, t.name, t.slug, '' AS post_excerpt, CONCAT('" . get_home_url() . "/$encyclopedia_root_slug/', t.slug) AS permalink, tt.description
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'glossaries'
            ";

            // Add letter filtering if a letter is specified
            if (!empty($letter)) {
                $query .= $wpdb->prepare(" AND SUBSTRING(t.name, 1, 1) = %s", $letter);
            }

            // Append the ordering clause
            $query .= " ORDER BY t.name ASC";

            // The base $query is composed from string literals; the only user-influenced fragment ($letter) was bound through $wpdb->prepare above.
            $results = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter

            // Retrieve meta data for each term
            $current_glossaries = [];
            foreach ($results as $result) {
                $term_id = $result['term_id'];
                $meta_data = get_term_meta($term_id);

                // Include meta data in the result
                $result['meta_data'] = $meta_data;
                $current_glossaries[] = $result;
            }

            return $current_glossaries;
        }

        return []; // Return an empty array if glossaries are not enabled or source is not 'glossaries'
    }

    public function wrap_glossaries($content)
    {
        if (is_singular('docs')) {
            // Protect code snippets from glossary auto-linking: language-dropdown
            // labels (e.g. "PHP") must never become encyclopedia links — that
            // hijacks the language switcher — and glossary terms should not be
            // linkified inside sample code either.
            $protected = [];
            $content   = preg_replace_callback(
                '~<pre\b[^>]*>.*?</pre>|<span class="betterdocs-code-lang-label">.*?</span>~is',
                function ($matches) use (&$protected) {
                    $key               = '<!--bd-glossary-protected-' . count($protected) . '-->';
                    $protected[$key]   = $matches[0];
                    return $key;
                },
                $content
            );

            $glossaries     = Helper::get_glossary_terms();
            $glossary_terms = [];

            foreach ($glossaries as $glossary) {
                $status = isset( $glossary['meta_data']['status'][0] ) ?  $glossary['meta_data']['status'][0] : false;

                if( $status == 0 ) {
                    continue; // skip processing if glossary is disabled
                }

                $lowercase_glossary_name = function_exists('mb_strtolower') ?
                    mb_strtolower($glossary['name'], 'UTF-8') : strtolower($glossary['name']);

                $description = !empty($glossary['description']) ?
                    $glossary['description'] : (!empty($glossary['meta_data']['glossary_term_description'][0]) ?
                        $glossary['meta_data']['glossary_term_description'][0] : '');

                $tooltip = !empty($description) ? esc_attr($description) : '';
                
                // Get term link and check if it's valid
                $term_link = get_term_link($glossary['slug'], 'glossaries');
                if (is_wp_error($term_link)) {
                    continue; // skip processing if term link is invalid
                }
                
                $glossary_terms[$lowercase_glossary_name] = '<span class="glossary-tooltip-container" data-tooltip="' . $tooltip . '"><a href="' . esc_url($term_link) . '" target="_blank">' . esc_html($glossary['name']) . '</a></span>';

            }

            foreach ($glossary_terms as $term => $replacement) {
                // Escape the term to ensure it's treated as a literal string
                $escaped_term = preg_quote($term, '/');
                if( Helper::contains_unicode( $escaped_term ) ) {
                    $pattern = '/(?<!alt=")(?<!\pL)(' . $escaped_term . ')(?!\pL)(?![^<>]*>)(?![^<]*<\/a>)/iu';
                } else {
                    $pattern = '/(?<!alt=")\b(' . $escaped_term . ')\b(?![^<>]*>)(?![^<]*<\/a>)/iu';
                }
                $content = preg_replace_callback($pattern, function ($matches) use ($replacement) {
                    return $replacement;
                }, $content);
            }

            // Restore the protected code-snippet regions.
            if (! empty($protected)) {
                $content = strtr($content, $protected);
            }

            return $content;
        }

        return $content;
    }
}
