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

class MultipleKBTab extends Block {
    public $is_pro = true;

    protected $editor_scripts = [
        'betterdocs-pro-blocks-editor'
    ];

    protected $editor_styles = [
        'betterdocs-pro-blocks-editor',
        'betterdocs-fontawesome',
        'betterdocs-category-tab-grid'
    ];

    protected $frontend_styles = [
        'betterdocs-category-tab-grid',
        'betterdocs-fontawesome'
    ];

    protected $frontend_scripts = [
        'betterdocs-pro-mkb-tab-grid'
    ];

    public function get_name() {
        return 'multiple-kb-tab';
    }

    public function betterdocs_nested_docs_args( $_params ) {
        return [
            'multiple_kb'    => true,
            'posts_per_page' => $this->attributes['postPerSubcategory'],
            'order'          => $this->attributes['subCategoryOrder'],
            'orderby'        => $this->attributes['subCategoryOrderBy']
        ];
    }

    public function betterdocs_template_params( $_params, $layout, $term, $widget_type ) {
        $_params['query_args'] = betterdocs()->query->docs_query_args( array_merge( $_params['query_args'], [
            'posts_per_page' => $this->attributes['postsPerPage'],
            'orderby'        => $this->attributes['postsOrderBy'],
            'order'          => $this->attributes['postsOrder']
        ] ) );

        $_params['nested_subcategory'] = $this->attributes['enableNestedSubcategory'];

        return $_params;
    }

    public function render( $attributes, $content ) {
        if ( (bool) $this->attributes['enableNestedSubcategory'] ) {
            add_filter( 'betterdocs_nested_docs_args', [$this, 'betterdocs_nested_docs_args'], 15 );
        }

        add_filter( 'betterdocs_template_params', [$this, 'betterdocs_template_params'], 10, 4 );

        $this->views( 'layouts/tab-grid/default' );

        remove_filter( 'betterdocs_template_params', [$this, 'betterdocs_template_params'], 10 );
        if ( (bool) $this->attributes['enableNestedSubcategory'] ) {
            remove_filter( 'betterdocs_nested_docs_args', [$this, 'betterdocs_nested_docs_args'], 15 );
        }
    }

    public function get_default_attributes() {
        return [
            'blockId'                 => '',
            'includeCategories'       => '',
            'excludeCategories'       => '',
            'boxPerPage'              => 9,
            'postsPerPage'            => 5,
            'postsOrderBy'            => 'title',
            'postsOrder'              => 'asc',
            'orderBy'                 => 'name',
            'order'                   => 'asc',
            'showIcon'                => true,
            'showTitle'               => true,
            'titleTag'                => 'h2',
            'showCount'               => true,
            'enableNestedSubcategory' => false,
            'postPerSubcategory'      => -1,
            'subCategoryOrder'        => 'asc',
            'subCategoryOrderBy'      => 'title',
            'showButton'              => true,
            'buttonText'              => __( "Explore Button", "betterdocs-pro" ),
            'listIcon'                => '',
            'listIconImageUrl'        => ''
        ];
    }

    public function view_params() {
        $attributes = &$this->attributes;

        $this->attributes['terms_order']        = $attributes['order'];
        $this->attributes['terms_orderby']      = $attributes['orderBy'];
        $this->attributes['nested_subcategory'] = $attributes['enableNestedSubcategory'];

        $_terms_args = [
            'taxonomy'   => 'knowledge_base',
            'hide_empty' => true,
            'parent'     => 0,
            'order'      => $attributes['order'],
            'offset'     => 0,
            'number'     => $attributes['boxPerPage'],
            'orderby'    => $attributes['orderBy'],
            'meta_key'   => 'kb_order'
        ];

        $includes = $this->string_to_array( $attributes['includeCategories'] );
        $excludes = $this->string_to_array( $attributes['excludeCategories'] );

        if ( ! empty( $includes ) ) {
            $_terms_args['include'] = array_diff( $includes, (array) $excludes );
        }

        if ( ! empty( $excludes ) ) {
            $_terms_args['exclude'] = $excludes;
        }

        $kb_terms_query = betterdocs()->query->terms_query( $_terms_args );

        $this->attributes['nested_subcategory'] = $this->attributes['enableNestedSubcategory'];
        $this->attributes['show_title']         = $this->attributes['showTitle'];
        $this->attributes['number']             = 'all';

        return [
            'wrapper_attr'       => [
                'class' => ['betterdocs-category-tab-grid-wrapper betterdocs-wrapper betterdocs-wraper']
            ],
            'kb_terms'           => get_terms( $kb_terms_query ),
            'terms_order'        => $attributes['order'],
            'terms_orderby'      => $attributes['orderBy'],
            'show_header'        => true,
            'show_title'         => $attributes['showTitle'],
            'title_tag'          => $attributes['titleTag'],
            'show_list'          => true,
            'list_icon_name'     => ! empty( $this->attributes['listIconImageUrl'] ) ? ['value' => ['url' => str_replace( 'blob:', '', $this->attributes['listIconImageUrl'] )]] : ( ! empty( $this->attributes['listIcon'] ) ? ['value' => ['url' => $this->attributes['listIcon']]] : ( ! empty( betterdocs()->settings->get( 'docs_list_icon' ) ) ? ['value' => ['url' => betterdocs()->settings->get( 'docs_list_icon' )['url']]] : 'list' ) ),
            'show_count'         => false,
            'show_button'        => $attributes['showButton'],
            'button_text'        => $attributes['buttonText'],
            'nested_subcategory' => $attributes['enableNestedSubcategory'],
            'show_icon'          => $attributes['showIcon'],
            'list_icon_url'      => '',
            'layout_type'        => 'block'
        ];
    }
}
