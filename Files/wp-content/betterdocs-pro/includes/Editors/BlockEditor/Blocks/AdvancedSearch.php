<?php


namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\SearchForm;


class AdvancedSearch extends SearchForm {
    protected $frontend_scripts = [
        'betterdocs-pro',
        'betterdocs-search-modal'
    ];

    protected $editor_scripts = [
        'advanced-search',
        'betterdocs-search-modal'
    ];

    public function get_default_attributes() {
        return [
            'blockId'                       => '',
            'placeholderText'               => __( 'Search', 'betterdocs-pro' ),
            'popularSearchText'             => __('Popular Search','betterdocs-pro'),
            'categorySearch'                => false,
            'searchButton'                  => false,
            'popularSearch'                 => false,
            'searchLayout'                  => 'layout-1',
            'searchHeading'                 => '',
            'searchSubHeading'              => '',
            'searchHeadingTag'              => 'h2',
            'searchSubHeadingTag'           => 'h3',
            'searchButtonLayout2'           => false, //for search modal
            'categorySearchLayout2'         => false, //for search modal
            'popularSearchLayout2'          => false, //for search modal,
            'initialFAQNumber'              => 5, //for search modal,
            'initialDocsNumber'             => 5, //for search modal,
            'searchModalQueryTermIds'       => '', //for search modal
            'searchModalQueryDocIds'        => '', //for search modal
            'searchModalQueriesFaqGroupIds' => ''
        ];
    }

    public function view_params() {
        $settings = &$this->attributes;

        $_shortcode_attributes = [
            'placeholder'            => esc_html( $settings['placeholderText'] ),
            'popular_search_title'   => esc_html( $settings['popularSearchText'] ),
            'category_search'        => $settings['categorySearch'],
            'search_button'          => $settings['searchButton'],
            'popular_search'         => $settings['popularSearch'],
            'heading'                => isset( $settings['searchHeading'] ) ? esc_html( $settings['searchHeading'] ) : '',
            'subheading'             => isset( $settings['searchSubHeading'] ) ? esc_html( $settings['searchSubHeading'] ) : '',
            'heading_tag'            => isset( $settings['searchHeadingTag'] ) ? esc_attr( $settings['searchHeadingTag'] ) : 'h2',
            'subheading_tag'         => isset( $settings['searchSubHeadingTag'] ) ? esc_attr( $settings['searchSubHeadingTag'] ) : 'h3'
        ];

        return [
            'shortcode_attr' => $_shortcode_attributes
        ];
    }

}
