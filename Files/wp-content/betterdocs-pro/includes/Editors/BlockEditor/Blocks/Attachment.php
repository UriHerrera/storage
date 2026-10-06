<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class Attachment extends Block {

    public $is_pro           = true;
    protected $editor_styles = [
        'betterdocs-pro-blocks-editor'
    ];

    protected $editor_scripts  = ['betterdocs-pro-blocks-editor'];
    protected $frontend_styles = ['single-doc-attachment'];

    public function get_name() {
        return 'attachment';
    }

    public function get_default_attributes() {
        return [
            'blockId'                => '',
            'attachmentLabel'        => __( 'Attachments', 'betterdocs-pro' ),
            'fileFormatName'         => '',
            'showAttachmentSize'     => true,
            'showAttachmentIcon'     => true,
            'openAttachmentInNewTab' => false
        ];
    }

    public function render( $attributes, $content ) {
        $this->views( 'blocks/attachment' );
    }

    public function view_params() {
        return [
            'blockId'                     => $this->attributes['blockId'],
            'attachment_heading_label'    => $this->attributes['attachmentLabel'],
            'default_attachment_name'     => $this->attributes['fileFormatName'],
            'show_attachment_icon'        => $this->attributes['showAttachmentIcon'],
            'show_attachment_size'        => $this->attributes['showAttachmentSize'],
            'show_attachments_in_new_tab' => $this->attributes['openAttachmentInNewTab'],
            'show_attachments'            => true
        ];
    }
}
