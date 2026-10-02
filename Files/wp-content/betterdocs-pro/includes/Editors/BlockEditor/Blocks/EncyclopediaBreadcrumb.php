<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class EncyclopediaBreadcrumb extends Block {

    public $is_pro = true;

    protected $editor_styles   = ['betterdocs-breadcrumb', 'betterdocs-pro-blocks-editor'];
    protected $frontend_styles = ['betterdocs-breadcrumb', 'betterdocs-pro-blocks-editor'];
    protected $editor_scripts = ['betterdocs-pro-blocks-editor'];

    public function get_name() {
        return 'encyclopedia-breadcrumb';
    }

    public function get_default_attributes() {
        return [
            'blockId' => ''
        ];
    }

    public function render( $attributes, $content ) {
        $this->views( 'templates/parts/encyclopedia-breadcrumb' );
    }

    public function view_params() {
        $doc_title      = get_the_title();
        $current_letter = strtoupper( mb_substr( get_the_title(), 0, 1 ) );
        $doc_title      = get_the_title();
        if ( is_tax( 'glossaries' ) ) {
            $term           = get_queried_object();
            $doc_title      = $term->name;
            $current_letter = strtoupper( mb_substr( $doc_title, 0, 1 ) );
        }
        $encyclopdeia_page_title = betterdocs()->settings->get( 'encyclopedia_page_title', 'Encyclopedia' );
        $slug                    = get_option( 'encyclopedia_current_page_slug' );
        $encyclopdeia_title      = home_url() . '/' . $slug . '/';
        $encyclopdeia_url        = home_url() . '/' . $slug . '/';
        $current_letter_url      = home_url() . '/' . $slug . '/?encyclopedia_prefix=' . $current_letter;

        return [
            'blockId'                 => $this->attributes['blockId'],
            'doc_title'               => $doc_title,
            'encyclopdeia_page_title' => $encyclopdeia_page_title,
            'slug'                    => $slug,
            'encyclopdeia_title'      => $encyclopdeia_title,
            'encyclopdeia_url'        => $encyclopdeia_url,
            'current_letter_url'      => $current_letter_url,
            'current_letter'          => $current_letter
        ];
    }
}
