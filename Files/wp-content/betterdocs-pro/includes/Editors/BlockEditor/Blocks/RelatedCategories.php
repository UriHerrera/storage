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

class RelatedCategories extends Block {

    public $is_pro            = true;
    protected $editor_scripts = [
        'betterdocs-pro-blocks-editor'
    ];

    protected $editor_styles = [
        'betterdocs-related-categories',
        'betterdocs-doc_category'
    ];

    protected $frontend_scripts = [
        'betterdocs-related-categories'
    ];

    protected $frontend_styles = [
        'betterdocs-related-categories',
        'betterdocs-doc_category'
    ];

    public function get_name() {
        return 'related-categories';
    }

    public function get_default_attributes() {
        return [
            'blockId'               => '',
            'termsOrder'            => 'asc',
            'termsOrderBy'          => 'name',
            'multipleKnowledgeBase' => false,
            'nestedSubCategory'     => false,
            'mainTitleText'         => __( "Related Categories", "betterdocs-pro" ),
            'buttonText'            => __( "Load More", "betterdocs-pro" ),
            'terms_title_tag'       => 'h2'
        ];
    }

    public function heading( $terms ) {
        if ( empty( $terms ) ) {
            return;
        }

        $this->views( 'layouts/related-categories/heading' );
    }

    public function load_more_button( $terms ) {
        if ( empty( $terms ) || count( $terms ) < 4 ) {
            return;
        }
        $this->views( 'layouts/related-categories/load-more' );
    }

    public function render( $attributes, $content ) {
        $block_id = explode( '-', $attributes['blockId'] );
        $block_id = $block_id[count( $block_id ) - 1];
        $block_id = preg_replace("/[^a-zA-Z0-9]/", "", $block_id);

        unset($attributes['blockMeta']); //remove styles of block

        betterdocs_pro()->assets->localize( 'betterdocs-related-categories', "betterdocsRelatedTerms_" . $block_id, [
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'show-more-catergories' ),
            'title_tag'        => $this->attributes['terms_title_tag'],
            'current_term_id'  => get_queried_object_id(),
            'kb_slug'          => betterdocs_pro()->multiple_kb->get_kb_slug(),
            'block_attributes' => $attributes
        ] );

        add_action( 'betterdocs_base_layout_inner_wrapper_before', [$this, 'heading'] );
        add_action( 'betterdocs_base_layout_inner_wrapper_after', [$this, 'load_more_button'] );

        $this->views( 'layouts/base' );

        remove_action( 'betterdocs_base_layout_inner_wrapper_before', [$this, 'heading'] );
        remove_action( 'betterdocs_base_layout_inner_wrapper_after', [$this, 'load_more_button'] );
    }

    public function view_params() {
        $block_id = explode( '-', $this->attributes['blockId'] );
        $block_id = $block_id[count( $block_id ) - 1];

        $terms_query = betterdocs_pro()->query->terms_query( [
            'multiple_kb'        => $this->attributes['multipleKnowledgeBase'],
            'order'              => $this->attributes['termsOrder'],
            'orderby'            => $this->attributes['termsOrderBy'],
            'nested_subcategory' => $this->attributes['nestedSubCategory'],
            'number'             => 4,
            'kb_slug'            => betterdocs_pro()->multiple_kb->get_kb_slug(),
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
            'widget_type'        => 'related-categories',
            'heading'            => $this->attributes['mainTitleText'],
            'show_term_image'    => true,
            'title_tag'          => $this->attributes['terms_title_tag'],
            'load_more_text'     => $this->attributes['buttonText'],
            'block_id'           => $block_id
        ];

        return $_view_params;
    }
}
