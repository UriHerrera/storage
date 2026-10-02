<?php

namespace WPDeveloper\BetterDocsPro\Traits;

// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in

trait MKB {
    public function reset_attributes() {
        $this->attributes['term_icon_meta_key'] = 'knowledge_base_image-id';

        // $this->attributes['terms_order'] = 'ASC';
        if ( betterdocs()->settings->get( 'alphabetically_order_term', false ) ) {
            $this->attributes['terms_orderby'] = 'name';
        } else {
            $this->attributes['meta_key']      = 'kb_order';
        }
    }

    public function term_permalink( $permalink, $term, $taxonomy ) {
        return get_term_link( $term->term_id, 'knowledge_base' );
    }

    public function kb_terms( $term, $taxonomy ) {
        $current_term = get_term_by( 'slug', $term->slug, $taxonomy, OBJECT );

        if ( ! $current_term || is_wp_error( $current_term ) ) {
            return '';
        }

        $_term_attr   = get_term_meta( $current_term->term_id, 'doc_category_knowledge_base', true );

        if ( ! is_array( $_term_attr ) ) {
            return [];
        }

        return array_values( array_filter( $_term_attr, function ( $item ) {
            return ! empty( $item ) && ( is_string( $item ) || is_numeric( $item ) );
        } ) );
    }

    public function get_first_kb_slug( $term, $taxonomy ) {
        $_term_attr = $this->kb_terms( $term, $taxonomy );

        if ( empty( $_term_attr ) || ! is_array( $_term_attr ) || ! isset( $_term_attr[0] ) || ! is_scalar( $_term_attr[0] ) ) {
            return 'non-knowledgebase';
        }

        return (string) $_term_attr[0];
    }
}
