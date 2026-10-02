<?php

namespace WPDeveloper\BetterDocsPro\REST;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class Thumbnail extends BaseAPI {
    public function register() {
        $this->register_field( 'knowledge_base', 'thumbnail', [
            'get_callback' => [$this, 'thumbnail_image']
        ] );
    }

    public function thumbnail_image( $object ) {
        $attachment_id = get_term_meta( $object['id'], 'knowledge_base_image-id', true );
        if ( ! $attachment_id ) {
            return;
        }
        return wp_get_attachment_url( $attachment_id );
    }
}
