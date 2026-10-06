<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

class RelatedCategories extends Shortcode {
    protected $is_pro = true;

    /**
     * A list of deprecated attributes.
     * @var array<string, string>
     */
    protected $deprecated_attributes = [
        'multiple_kb' => 'multiple_knowledge_base'
    ];

    protected $map_view_vars = [
        'terms_title_tag' => 'title_tag'
    ];

    public function __construct( Settings $settings, Query $query, Helper $helper, Defaults $defaults ) {
        parent::__construct( $settings, $query, $helper, $defaults );
        //gutenberg ajax request
        add_action( 'wp_ajax_nopriv_load_more_terms_gutenberg', [$this, 'load_more_terms'] );
        add_action( 'wp_ajax_load_more_terms_gutenberg', [$this, 'load_more_terms'] );

        add_action( 'wp_ajax_nopriv_load_more_terms', [$this, 'load_more_terms'] );
        add_action( 'wp_ajax_load_more_terms', [$this, 'load_more_terms'] );
    }

    /**
     * Summary of get_id
     * @return array|string
     */
    public function get_name() {
        return 'betterdocs_related_categories';
    }

    public function get_style_depends() {
        return ['betterdocs-related-categories'];
    }

    /**
     * Summary of default_attributes
     * @return array
     */
    public function default_attributes() {
        return [
            'terms_order'             => $this->settings->get( 'alphabetically_order_term' ) ? 'ASC' : $this->settings->get( 'terms_order' ),
            'terms_orderby'           => $this->settings->get( 'alphabetically_order_term' ) ? 'name' : $this->settings->get( 'terms_orderby' ),
            'multiple_knowledge_base' => $this->settings->get( 'multiple_kb' ),
            'nested_subcategory'      => $this->settings->get( 'nested_subcategory' ),
            'heading'                 => __( 'Other Categories', 'betterdocs-pro' ),
            'load_more_text'          => __( 'Load More', 'betterdocs-pro' ),
            'terms_title_tag'         => 'h2'
        ];
    }

    public function load_more_button( $terms ) {
        if ( empty( $terms ) || count( $terms ) < 4 ) {
            return;
        }
        $this->views( 'layouts/related-categories/load-more' );
    }

    public function heading( $terms ) {
        if ( empty( $terms ) ) {
            return;
        }

        $this->views( 'layouts/related-categories/heading' );
    }

    public function get_script_depends() {
        return ['betterdocs-related-categories'];
    }

    public function load_more_terms() {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'show-more-catergories' ) ) {
            die( 'Cheating&huh?' );
        }

        $current_term_id = isset( $_GET['current_term_id'] ) ? sanitize_text_field( wp_unslash( $_GET['current_term_id'] ) ) : '';
        $kb_slug         = isset( $_GET['kb_slug'] ) ? sanitize_text_field( wp_unslash( $_GET['kb_slug'] ) ) : '';

        // Public endpoint: `page` drives the query offset, so bound it rather
        // than accepting arbitrary (or non-numeric) values from the request.
        $page               = isset( $_GET['page'] ) ? absint( wp_unslash( $_GET['page'] ) ) : 2;
        $page               = max( 1, min( $page, 1000 ) );
        // `title_tag` is interpolated into an opening/closing tag name downstream.
        // sanitize_text_field() leaves spaces intact, so a value like
        // "h2 onmouseover=..." would survive and inject an attribute. Run it
        // through the shared allowlist, which falls back to 'div'.
        $title_tag          = isset( $_GET['title_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['title_tag'] ) ) : 'h2';
        $title_tag          = betterdocs()->template_helper->is_valid_tag( $title_tag );
        $multiple_kb        = $this->settings->get( 'multiple_kb' );
        $terms_order        = $this->settings->get( 'alphabetically_order_term' ) != 'off' ? 'ASC' : $this->settings->get( 'terms_order' );
        $terms_orderby      = $this->settings->get( 'alphabetically_order_term' ) != 'off' ? 'name' : $this->settings->get( 'terms_orderby' );
        $nested_subcategory = $this->settings->get( 'nested_subcategory' );
        $per_page           = 4;
        $offset             = $per_page * ( $page - 1 );

        $block_attributes = ! empty( $_GET['block_attributes'] ) ? wp_unslash( $_GET['block_attributes'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        //check if block attributes exist, based on these props, perform ajax request for "Load More"(Only For Gutenberg)
        if ( ! empty( $block_attributes ) ) {
            $multiple_kb        = isset( $block_attributes['multipleKnowledgeBase'] ) ? sanitize_text_field( $block_attributes['multipleKnowledgeBase'] ) : '';
            $terms_order        = isset( $block_attributes['termsOrder'] ) ? sanitize_text_field( $block_attributes['termsOrder'] ) : '';
            $terms_orderby      = isset( $block_attributes['termsOrderBy'] ) ? sanitize_text_field( $block_attributes['termsOrderBy'] ) : '';
            $nested_subcategory = isset( $block_attributes['nestedSubCategory'] ) ? sanitize_text_field( $block_attributes['nestedSubCategory'] ) : '';
        }

        $_term_query_args = $this->query->terms_query( [
            'taxonomy'           => 'doc_category',
            'hide_empty'         => true,
            'multiple_kb'        => ( $multiple_kb && ! empty( $kb_slug ) ) ? true : false,
            'kb_slug'            => $kb_slug,
            'order'              => $terms_order,
            'orderby'            => $terms_orderby,
            'nested_subcategory' => $nested_subcategory,
            'number'             => $per_page,
            'offset'             => $offset,
            'exclude'            => [$current_term_id]
        ] );
        $terms = get_terms( $_term_query_args );

        $_term_query_args['offset'] = $per_page * (  ( $page + 1 ) - 1 );
        // Same WP_Error guard as CategoryBoxThree::view_params() — an invalid
        // taxonomy makes get_terms() return WP_Error, and count(WP_Error) is a
        // PHP 8 TypeError that takes the page down.
        $_offset_terms              = get_terms( $_term_query_args );
        $_has_term                  = is_wp_error( $_offset_terms ) ? 0 : count( $_offset_terms );

        $output = '';

        if ( ! empty( $terms ) && is_array( $terms ) ) {
            foreach ( $terms as $term ) {
                ob_start();

                $_counts = betterdocs()->query->get_docs_count( $term, $nested_subcategory, [
                    'multiple_knowledge_base' => isset( $multiple_kb ) ? $multiple_kb : false,
                    'kb_slug'                 => isset( $kb_slug ) ? $kb_slug : ''
                ] );

                if ( $_counts <= 0 ) {
                    continue;
                }

                $permalink = apply_filters(
                    'betterdocs_term_permalink',
                    get_term_link( $term->term_id, $term->taxonomy ), $term, 'doc_category', $_term_query_args
                );

                betterdocs()->views->get( 'layouts/related-categories/default', [
                    'term'            => $term,
                    'title_tag'       => $title_tag,
                    'show_term_image' => true,
                    'permalink'       => $permalink,
                    'widget_type'     => 'related-categories',
                    'counts'          => $_counts
                ] );

                $output .= ob_get_clean();
            }
        }

        wp_send_json_success( [
            'html'          => $output,
            'has_more_term' => $_has_term > 0
        ] );
    }

    /**
     * Summary of render
     *
     * @param mixed $atts
     * @param mixed $content
     * @return mixed
     */
    public function render( $atts, $content = null ) {
        betterdocs_pro()->assets->localize( 'betterdocs-related-categories', 'betterdocsRelatedTerms', [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'show-more-catergories' ),
            'title_tag'       => $this->attributes['terms_title_tag'],
            'current_term_id' => get_queried_object_id(),
            'kb_slug'         => betterdocs_pro()->multiple_kb->get_kb_slug()
        ] );

        add_action( 'betterdocs_base_layout_inner_wrapper_before', [$this, 'heading'] );
        add_action( 'betterdocs_base_layout_inner_wrapper_after', [$this, 'load_more_button'] );

        $this->views( 'layouts/base' );

        remove_action( 'betterdocs_base_layout_inner_wrapper_before', [$this, 'heading'] );
        remove_action( 'betterdocs_base_layout_inner_wrapper_after', [$this, 'load_more_button'] );
    }

    public function view_params() {
        $_kb_slug    = betterdocs_pro()->multiple_kb->get_kb_slug();
        $terms_query = $this->query->terms_query( [
            'multiple_kb'        => ( $this->attributes['multiple_knowledge_base'] && ! empty( $_kb_slug ) ) ? true : false,
            'order'              => $this->attributes['terms_order'],
            'orderby'            => $this->attributes['terms_orderby'],
            'nested_subcategory' => $this->attributes['nested_subcategory'],
            'number'             => 4,
            'kb_slug'            => $_kb_slug,
            'exclude'            => [get_queried_object_id()]
        ] );

        $_view_params = [
            'wrapper_attr'       => [
                'class' => ['betterdocs-related-terms-wrapper']
            ],
            'inner_wrapper_attr' => [
                'class' => ['betterdocs-related-terms-inner-wrapper']
            ],
            'layout'             => 'default',
            'terms_query_args'   => $terms_query,
            'widget_type'        => 'related-categories'
        ];

        return $_view_params;
    }
}
