<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class EncyclopediaDescription extends Block {
    public $is_pro = true;

    protected $editor_styles   = ['betterdocs-encyclopedia', 'betterdocs-pro-blocks-editor'];
    protected $frontend_styles = ['betterdocs-encyclopedia', 'betterdocs-pro-blocks-editor'];
    protected $editor_scripts  = ['betterdocs-pro-blocks-editor'];

    public function get_name() {
        return 'betterdocs-encyclopedia-description';
    }

    public function get_default_attributes() {
        return [
            'blockId' => ''
        ];
    }

    public function render( $attributes, $content ) {
        $this->views( 'templates/parts/encyclopedia-description' );
    }

    public function view_params() {
        $term = get_queried_object();

        $term_id            = isset( $term->term_id ) ? $term->term_id : 0;
        $custom_description = get_term_meta( $term_id, 'glossary_term_description', true );

        $description = ! empty( $custom_description ) ? $custom_description : ( isset( $term->description ) ? $term->description : '' );

        return [
            'blockId'     => $this->attributes['blockId'],
            'description' => $description
        ];
    }
}
