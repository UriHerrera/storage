<?php
namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

if ( ! defined( 'ABSPATH' ) ) { exit; }


// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class MultipleKB extends Block {

    public $is_pro = true;

    protected $editor_scripts = array(
        'betterdocs-pro-blocks-editor'
    );

    protected $editor_styles = array(
        'betterdocs-fontawesome',
        'betterdocs-pro-blocks-editor',
        'betterdocs-blocks-category-box'
    );

    protected $frontend_styles = array(
        'betterdocs-fontawesome',
        'betterdocs-blocks-category-box',
        'betterdocs-docs'
    );

    /**
     * unique name of block
     * @return string
     */
    public function get_name() {
        return 'multiple-kb';
    }

    public function get_default_attributes() {
        return array(
            'blockId' => '',
            'categories' => array(),
            'includeCategories' => '',
            'excludeCategories' => '',
            'boxPerPage' => 9,
            'orderBy' => 'name',
            'order' => 'asc',
            'layout' => 'default',
            'showIcon' => true,
            'showTitle' => true,
            'titleTag' => 'h2',
            'showCount' => true,
            'prefix' => '',
            'suffix' => __( 'Docs', 'betterdocs-pro' ),
            'suffixSingular' => __( 'Doc', 'betterdocs-pro' ),
            'colRange' => 3,
            'TABcolRange' => 2,
            'MOBcolRange' => 1,
            'layout4Col' => 4,
            'showLastUpdatedTime' => true
        );
    }

    public function view_params() {
        $attributes = &$this->attributes;

        $terms_object = array(
            'taxonomy' => 'knowledge_base',
            'order' => $attributes[ 'order' ],
            'orderby' => $attributes[ 'orderBy' ],
            'number' => isset( $attributes[ 'boxPerPage' ] ) ? $attributes[ 'boxPerPage' ] : 5,
            'hide_empty' => true
        );

        if ( 'kb_order' === $attributes[ 'orderBy' ] ) {
            $terms_object[ 'meta_key' ] = 'kb_order';
            $terms_object[ 'orderby' ]  = 'meta_value_num';
        }

        $includes = $this->string_to_array( $attributes[ 'includeCategories' ] );
        $excludes = $this->string_to_array( $attributes[ 'excludeCategories' ] );
        $styles   = '';

        if ( ! empty( $includes ) ) {
            $terms_object[ 'include' ] = array_diff( $includes, (array) $excludes );
        }

        if ( ! empty( $excludes ) ) {
            $terms_object[ 'exclude' ] = $excludes;
        }

        $_wrapper_classes = array(
            'betterdocs-category-box-wrapper',
            'betterdocs-category-list-view-wrapper',
            'betterdocs-multiple-kb-list-wrapper',
            'betterdocs-multiple-kb-wrapper',
            'betterdocs-pro'
        );

        $_inner_wrapper_classes = array(
            'betterdocs-category-box-inner-wrapper',
            'layout-flex', 'default' === $attributes[ 'layout' ] ? 'layout-1' : $attributes[ 'layout' ],
            "betterdocs-column-" . $attributes[ 'colRange' ],
            "betterdocs-column-tablet-" . $attributes[ 'TABcolRange' ],
            "betterdocs-column-mobile-" . $attributes[ 'MOBcolRange' ]
        );

        if ( 'layout-4' == $attributes[ 'layout' ] ) {
            $_inner_wrapper_classes = array(
                'betterdocs-category-box-inner-wrapper',
                'layout-4',
                'docs-col-4',
                'single-kb',
                'betterdocs-categories-folder',
                'layout-4',
                'single-kb'
            );
            $column           = is_tax( 'doc_category' ) ? 3 : $attributes[ 'layout4Col' ];
            $terms_query_args = betterdocs()->query->terms_query( $terms_object );
            $terms_for_count  = betterdocs()->query->get_terms( $terms_query_args );
            $term_count       = ( ! is_wp_error( $terms_for_count ) ) ? count( $terms_for_count ) : 0;
            $reminder         = $term_count % $column;
            $styles .= "--column: $column;";
            $styles .= "--count: $term_count;";
            $styles .= "--reminder: $reminder;";
        }

        $wrapper_attr = array(
            'class' => $_wrapper_classes
        );
        $inner_wrapper_attr = array(
            'class' => $_inner_wrapper_classes,
            'style' => $styles,
            'data-column_desktop' => $attributes[ 'colRange' ],
            'data-column_tab' => $attributes[ 'TABcolRange' ],
            'data-column_mobile' => $attributes[ 'MOBcolRange' ]
        );

        $_params = array(
            'wrapper_attr' => $wrapper_attr,
            'inner_wrapper_attr' => $inner_wrapper_attr,
            'terms_query_args' => betterdocs()->query->terms_query( $terms_object ),
            'widget_type' => 'category-box',
            'multiple_knowledge_base' => false,
            'kb_slug' => '',
            'nested_subcategory' => false,
            'show_header' => true,
            'show_description' => false,
            'term_icon_meta_key' => 'knowledge_base_image-id',
            'title_tag' => $attributes[ 'titleTag' ]
        );

        if ( 'layout-4' == $attributes[ 'layout' ] ) {
            $column                     = is_tax( 'doc_category' ) ? 3 : $attributes[ 'layout4Col' ];
            $terms_query_args_2         = betterdocs()->query->terms_query( $terms_object );
            $terms_for_count_2          = betterdocs()->query->get_terms( $terms_query_args_2 );
            $term_count                 = ! is_wp_error( $terms_for_count_2 ) ? count( $terms_for_count_2 ) : 0;
            $reminder                   = $term_count % $column;
            $_params[ 'show_icon' ]     = $this->attributes[ 'showIcon' ];
            $_params[ 'show_count' ]    = $this->attributes[ 'showCount' ];
            $_params[ 'total_terms' ]   = $term_count;
            $_params[ 'reminder' ]      = $reminder;
            $_params[ 'category_icon' ] = 'folder';
            $_params[ 'last_update' ]   = $this->attributes[ 'showLastUpdatedTime' ];
            $_params[ 'taxonomy' ]      = 'knowledge_base';
            $_params[ 'column' ]        = $column;
            unset( $_params[ 'inner_wrapper_attr' ][ 'data-column_desktop' ] ); //not needed for layout 4
            unset( $_params[ 'inner_wrapper_attr' ][ 'data-column_tab' ] ); // not needed for layout 4
            unset( $_params[ 'inner_wrapper_attr' ][ 'data-column_mobile' ] ); // not needed for layout 4
        }

        return $_params;
    }

    public function filter_header_sequence( $_layout_sequence, $layout, $widget_type, $_defined_vars ) {
        $_new_layout_sequence = array( 'category_icon', array(
            'class' => 'betterdocs-category-title-counts',
            'sequence' => array( 'category_title', 'category_description', 'category_counts' )
        ) );

        if ( 'layout-4' == $layout ) {
            $_new_layout_sequence = array( 'category_icon', array(
                'class' => 'betterdocs-category-title-counts',
                'sequence' => array( 'category_title', 'category_description', 'sub_category_counts', 'last_update' )
            ) );
        }

        return $_new_layout_sequence;
    }

    public function render( $attributes, $content ) {
        if ( 'layout-4' == $attributes[ 'layout' ] ) {
            add_filter( 'betterdocs_layout_filename', array( $this, 'change_to_layout_four' ), 15, 3 );
        }

        add_filter( 'betterdocs_header_layout_sequence', array( $this, 'filter_header_sequence' ), 10, 4 );
        $this->views( 'layouts/base' );
        remove_filter( 'betterdocs_header_layout_sequence', array( $this, 'filter_header_sequence' ), 10 );
    }

    public function change_to_layout_four() {
        return 'layout-4';
    }
}
