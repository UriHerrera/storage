<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( ! empty( $related_articles ) ) {
    $list = '';

    // Docs the current viewer must not see (BetterDocs content restriction, both
    // basic and advanced IKB modes). A manually-picked related article that is
    // hidden or password-protected must not leak its title or link on the front
    // end, so build the exclusion set once and skip those docs below.
    $bd_restricted_ids = ( function_exists( 'betterdocs_pro' ) && method_exists( betterdocs_pro(), 'get_restricted_doc_ids' ) )
        ? array_map( 'intval', (array) betterdocs_pro()->get_restricted_doc_ids() )
        : array();

    $bd_is_hidden_doc = function ( $post_id ) use ( $bd_restricted_ids ) {
        $post_id = (int) $post_id;
        if ( in_array( $post_id, $bd_restricted_ids, true ) ) {
            return true;
        }
        return (bool) get_post_field( 'post_password', $post_id );
    };

    foreach ( $related_articles as $article ) {
        $docId            = isset( $article->docId ) ? $article->docId : '';
        $selectedCategory = isset( $article->selectedCategory ) ? $article->selectedCategory : '';
        $svg_icon         = '<svg xmlns="http://www.w3.org/2000/svg" class="external" height="14" width="14" viewBox="0 0 512 512"><path d="M432 320H400a16 16 0 0 0 -16 16V448H64V128H208a16 16 0 0 0 16-16V80a16 16 0 0 0 -16-16H48A48 48 0 0 0 0 112V464a48 48 0 0 0 48 48H400a48 48 0 0 0 48-48V336A16 16 0 0 0 432 320zM488 0h-128c-21.4 0-32.1 25.9-17 41l35.7 35.7L135 320.4a24 24 0 0 0 0 34L157.7 377a24 24 0 0 0 34 0L435.3 133.3 471 169c15 15 41 4.5 41-17V24A24 24 0 0 0 488 0z"></path></svg>';

        if ( $docId == 'all-docs' ) {
            $args = [
                'post_type'   => 'docs',
                'numberposts' => -1,
                'tax_query'   => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    [
                        'taxonomy'         => 'doc_category',
                        'terms'            => $selectedCategory,
                        'include_children' => false
                    ]
                ]
            ];
            $posts = get_posts( $args );
            foreach ( $posts as $doc ) {
                $post_title     = isset( $doc->post_title ) ? $doc->post_title : '';
                $post_permalink = get_permalink( $doc->ID );
                $post_type      = get_post_type( $doc->ID  );
                $post_status    = get_post_status( $doc->ID  );
                if ( $post_type === 'docs' && $post_status === 'publish' && ! $bd_is_hidden_doc( $doc->ID ) ) {
                    $list .= '<li>
                                <a href="' . esc_url( $post_permalink ) . '">' . esc_html( $post_title ) . '</a>' .
                                '<a href="' . esc_url( $post_permalink ) . '">' . $svg_icon . '</a>
                             </li>';
                }
            }
        } else {
            $post_object    = get_post( $docId );
            $post_permalink = get_permalink( $docId );
            $post_type      = get_post_type( $docId );
            $post_status    = get_post_status( $docId );
            $post_title     = isset( $post_object->post_title ) ? $post_object->post_title : '';
            if ( $post_type === 'docs' && $post_status === 'publish' && ! $bd_is_hidden_doc( $docId ) ) {
                $list .= '<li>
                                <a href="' . esc_url( $post_permalink ) . '">' . esc_html( $post_title ) . '</a>' .
                                '<a href="' . esc_url( $post_permalink ) . '">' . $svg_icon . '</a>
                        </li>';
            }
        }
    }

    // $layout and $title come from block/shortcode attributes, which are author
    // controlled — escape them for their respective contexts. $list is markup
    // this template just built, with every interpolated value already escaped.
    echo '<div class="betterdocs-related-articles-container-front ' . esc_attr( $layout ) . '">' .
        ( $show_title ? '<p class="related-articles-title">' . esc_html( $title ) . '</p>' : '' ) . '
        <ul class="related-articles-list">' . ( $list ) . '</ul>
        </div>';
}
