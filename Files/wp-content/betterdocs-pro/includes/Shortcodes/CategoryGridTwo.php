<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use WPDeveloper\BetterDocs\Shortcodes\CategoryGrid;

class CategoryGridTwo extends CategoryGrid {
    protected $layout_class = 'layout-2';
    /**
     * A list of deprecated attributes.
     * @var array<string, string>
     */
    protected $deprecated_attributes = [
        'category'       => 'taxonomy',
        'posts_per_grid' => 'posts_per_page',
        'icon'           => 'show_icon',
        'count'          => 'show_count'
    ];

    protected $is_pro = true;

    /**
     * Summary of get_id
     * @return array|string
     */
    public function get_name() {
        return 'betterdocs_category_grid_2';
    }

    /**
     * Summary of default_attributes
     * @return array
     */
    public function default_attributes() {
        return [
            'sidebar_list'             => false,
            'taxonomy'                 => 'doc_category',
            'show_icon'                => true,
            'category_icon'            => '',
            'masonry'                  => false,
            'posts_per_page'           => $this->settings->get( 'posts_number' ),
            'orderby'                  => $this->settings->get( 'alphabetically_order_post' ),
            'order'                    => $this->settings->get( 'docs_order' ),
            'show_count'               => false,
            'count_suffix'             => '',
            'count_suffix_singular'    => '',
            'column'                   => $this->settings->get( 'column_number', 3 ),
            'nested_subcategory'       => $this->settings->get( 'nested_subcategory', false ),
            'terms'                    => '',
            'terms_orderby'            => '',
            'terms_order'              => '',
            'terms_include'            => '',
            'terms_exclude'            => '',
            'terms_offset'             => '',
            'kb_slug'                  => '',
            'multiple_knowledge_base'  => false,
            'disable_customizer_style' => false,
            'title_tag'                => 'h2',
            'category_title_link'      => false,
            'layout_type'              => '',
            'list_icon_url'            => '',
            'list_icon_name'           => 'list',
            'show_list_icon'           => true,
            'sidebar_layout'           => '',
            'wrapper_class'            => []
        ];
    }

    public function header_sequence( $_layout_sequence, $layout, $widget_type, $_defined_vars ) {
        $_new_layout_sequence = ['category_icon', [
            'class'    => 'betterdocs-category-title-counts',
            'sequence' => ['category_title', 'category_counts']
        ]];

        return $_new_layout_sequence;
    }

    public function top_row( &$terms, &$_defined_vars ) {
        if ( $_defined_vars['widget_id'] !== $this->get_name() ) {
            return;
        }
        add_filter( 'betterdocs_header_layout_sequence', [$this, 'header_sequence'], 11, 4 );

        $_column = $this->settings->get( 'column_number', 3 );

        $_top_terms    = array_slice( $terms, 0, $_column );
        $_top_term_ids = array_column( $_top_terms, 'term_id' );
        $terms         = array_slice( $terms, $_column );

        betterdocs()->views->get( 'layout-parts/category-grid-top-section', [
            'term_ids' => implode( ',', $_top_term_ids ),
            'column' => $_column,
        ] );

        remove_filter( 'betterdocs_header_layout_sequence', [$this, 'header_sequence'], 11 );
    }

    /**
     * Summary of render
     *
     * @param mixed $atts
     * @param mixed $content
     * @return mixed
     */

    public function render( $atts, $content = null ) {
        add_action( 'betterdocs_layout_base_loop_start', [$this, 'top_row'], 10, 2 );
        parent::render( $atts, $content );
        remove_action( 'betterdocs_layout_base_loop_start', [$this, 'top_row'], 10 );
    }

    public function view_params() {
        $this->client_attributes['masonry'] = false;
        $this->attributes['post_counter']   = false;

        $_view_params = [
            'show_icon'        => false,
            'show_description' => false
        ];

        return $this->merge( parent::view_params(), $_view_params );
    }
}
