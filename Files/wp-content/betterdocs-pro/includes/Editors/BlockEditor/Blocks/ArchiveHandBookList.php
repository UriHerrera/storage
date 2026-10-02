<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\ArchiveList;

class ArchiveHandBookList extends ArchiveList {
    protected $editor_scripts = [
        'archive-list-handbook',
        'betterdocs-pro-blocks-editor'
    ];

    protected $editor_styles = [
        'betterdocs-fontawesome',
        'betterdocs-blocks-editor',
        'betterdocs-doc-archive-list',
        'betterdocs-doc_category',
        'archive-list-handbook'
    ];

    protected $frontend_styles = [
        'betterdocs-fontawesome',
        'betterdocs-doc-archive-list',
        'betterdocs-doc_category',
        'archive-list-handbook'
    ];

    public function get_default_attributes() {
        return [
            'blockId'               => '',
            'nested_subcategory'    => false,
            'order'                 => 'asc',
            'orderby'               => 'betterdocs_order',
            'layout'                => 'layout-1',
            'list_icon'             => 'far fa-file-alt',
            'postsPerPageLayoutTwo' => -1,
            'listIconImageUrl'      => '',
            'pagination'            => false,
            'listTitleTag'          => 'h2',
            'listEntryHeadingTag'   => 'h2'
        ];
    }

    public function render( $attributes, $content ) {
        // Call parent render method
        parent::render( $attributes, $content );
    }

    public function render_handbook_view() {
        $this->views( 'widgets/handbook-list' );
    }

    public function view_params() {
        // Get the parent's view params
        $default_params = parent::view_params();

        return $default_params;
    }
}
