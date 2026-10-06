<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;
use WPDeveloper\BetterDocs\Core\Shortcode;

class RelatedDocs extends Shortcode {

    protected $is_pro = true;

    public function get_name() {
        return 'betterdocs_related_docs';
    }

    public function default_attributes() {
        return [
            'layout'          => 'layout-1',
            'show_title'      => true,
            'title'           => __( 'Related Docs', 'betterdocs-pro' ),
            'post_id' => get_the_ID()
        ];
    }

    public function render( $atts, $content = null ) {
        $this->views( 'templates/parts/related-docs' );
    }

    public function view_params() {
        $related_articles_parsed = [];
        $related_articles        = get_post_meta( $this->attributes['post_id'], '_betterdocs_related_articles', true );

        if ( is_array( $related_articles ) && ! empty( $related_articles ) ) {
            foreach ( $related_articles as $article ) {
                $article = json_decode( $article );
                array_push( $related_articles_parsed, $article );
            }
        }

        return [
            'layout'           => $this->attributes['layout'],
            'title'            => $this->attributes['title'],
            'show_title'       => $this->attributes['show_title'],
            'related_articles' => $related_articles_parsed
        ];
    }
}
