<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;

class EncyclopediaNavigation extends Block {

    public $is_pro = true;

    /**
     * unique name of block
     * @return string
     */
    public function get_name() {
        return 'betterdocs-encyclopedia-navigation';
    }

    protected $editor_styles = array(
        'betterdocs-encyclopedia'
    );

    protected $frontend_styles = array(
        'betterdocs-encyclopedia'
    );

    public function get_default_attributes() {
        return array(
            'blockId' => '',
            'alphabet_list_style' => 'box'
        );
    }

    public function render( $attributes, $content ) {
        $this->views( 'layouts/encyclopedia/navigation' );
    }

    public function view_params() {
        return array(
            'blockId' => $this->attributes[ 'blockId' ] . ' ',
            'alphabet_list_style' => $this->attributes[ 'alphabet_list_style' ],
            'docs_by_letter' => EncyclopediaQuery::docs_sort_by_letter(),
            'current_letter' => ! empty( strtoupper( substr( get_the_title(), 0, 1 ) ) ) ? strtoupper( substr( get_the_title(), 0, 1 ) ) : ''
        );
    }
}
