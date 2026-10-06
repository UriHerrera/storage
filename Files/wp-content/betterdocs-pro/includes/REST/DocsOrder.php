<?php

namespace WPDeveloper\BetterDocsPro\REST;

// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\Helper;

class DocsOrder extends BaseAPI {

    /**
     * Hard ceiling on posts_per_page for these public routes.
     */
    const MAX_PER_PAGE = 100;

    /**
     * Argument schema shared by the two docs-ordering routes.
     *
     * The routes are public and `per_page` used to be handed straight to
     * posts_per_page with no validation, so `per_page=-1` (or any large value)
     * asked the database for every published doc in one request. Declaring the
     * schema lets the REST layer reject out-of-range and malformed values before
     * the callback runs.
     *
     * @return array
     */
    protected function docs_order_args() {
        return [
            'orderby'      => [
                'type'    => 'string',
                'enum'    => [ 'betterdocs_order', 'menu_order' ],
                'default' => 'menu_order'
            ],
            'order'        => [
                'type'    => 'string',
                'enum'    => [ 'ASC', 'DESC', 'asc', 'desc' ],
                'default' => 'ASC'
            ],
            'per_page'     => [
                'type'    => 'integer',
                'minimum' => 1,
                'maximum' => self::MAX_PER_PAGE,
                'default' => 10
            ],
            'doc_category' => [
                'type'              => 'integer',
                'minimum'           => 0,
                'default'           => 0,
                'sanitize_callback' => 'absint'
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function register() {
        $this->get( '/docs_order', [$this, 'docs_order'], $this->docs_order_args() );
        $this->get( '/knowledge_base', [$this, 'fetch_kb_doc_category'] );
        $this->get( '/doc_category', [$this, 'docs_order'], $this->docs_order_args() );
    }

    /**
     * These are editor-side listing helpers, not public endpoints.
     *
     * BaseAPI::permission_check() returns true, so inheriting it left all three
     * routes fully anonymous: /knowledge_base returned the complete
     * knowledge_base term list to any unauthenticated caller with no arguments
     * at all. The only consumer is the multiple-kb-tab block's edit.js, which
     * runs in the block editor and therefore always has edit_posts.
     *
     * @return bool
     */
    public function permission_check() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * BetterDocs Order API Callback
     */
    public function docs_order( $attr ) {
        // The declared schema already bounds this, but the callback must not
        // depend on that being the only way it is ever reached.
        $per_page = isset( $attr['per_page'] ) ? absint( $attr['per_page'] ) : 10;
        if ( $per_page < 1 ) {
            $per_page = 10;
        }
        $per_page = min( $per_page, self::MAX_PER_PAGE );

        $query_args = [
            'post_status'    => 'publish',
            'post_type'      => 'docs',
            'posts_per_page' => $per_page,
            'orderby'        => isset( $attr['orderby'] ) && $attr['orderby'] === 'betterdocs_order' ? 'post__in' : 'menu_order'
        ];

        $term_id    = isset( $attr['doc_category'] ) ? absint( $attr['doc_category'] ) : 0;
        // Get language-specific meta key with fallback
        $meta_key   = Helper::get_meta_key_with_fallback( '_docs_order', $term_id );
        $docs_order = get_term_meta( $term_id, $meta_key, true );

        $term_object = get_term( $term_id );

        if ( is_wp_error( $term_object ) || ! $term_object ) {
            return new \WP_REST_Response( [], 200 );
        }

        if ( ! isset( $query_args['tax_query'] ) ) {
            $query_args['tax_query'] = [];
        }

        $query_args['tax_query'][] =
            [
            'taxonomy'         => 'doc_category',
            'field'            => 'slug',
            'terms'            => $term_object->slug,
            'operator'         => 'AND',
            'include_children' => false
        ];

        $query_args['orderby'] = ! empty( $docs_order ) ? $query_args['orderby'] : 'menu_order';

        $new_ids = [];
        global $wpdb;

        if ( ! empty( $docs_order ) ) {
            $docs_order = explode( ',', $docs_order );

            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $term_id is an absint()'d term id; intentional custom query.
            $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = $term_id" );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

            if ( ! is_null( $results ) && ! empty( $results ) && is_array( $results ) ) {
                $object_ids = array_filter( $results, function ( $value ) use ( $docs_order ) {
                    return ! in_array( $value->object_id, $docs_order );
                } );

                if ( ! empty( $object_ids ) ) {
                    array_walk( $object_ids, function ( $value ) use ( &$new_ids ) {
                        $new_ids[] = $value->object_id;
                    } );
                }
            }
        } else {
            $docs_order = [];
        }

        $query_args['post__in'] = array_merge( $new_ids, $docs_order );

        $data = [];
        $loop = new \WP_Query( $query_args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                $loop->the_post();
                $docs                      = [];
                $docs['id']                = get_the_ID();
                $docs['title']['rendered'] = wp_kses( get_the_title( get_the_ID() ), betterdocs()->template_helper::ALLOWED_HTML_TAGS );
                $docs['permalink']         = get_permalink();
                $data[]                    = $docs;
            }
            wp_reset_postdata();
        }
        return $data;
    }

    public function fetch_kb_doc_category( WP_REST_Request $request ) {
        $hide_empty         = $request->get_param( 'hide_empty' );
        $kb_order           = $request->get_param( 'order' );
        $kb_orderby         = $request->get_param( 'orderby' );
        $kb_per_page        = $request->get_param( 'per_page' );
        $include            = $request->get_param( 'include' );
        $exclude            = $request->get_param( 'exclude' );
        $nested_subcategory = $request->get_param( 'nested_subcategory' );

        $new_kb_terms = [];

        $kb_terms_args = [
            'taxonomy'   => 'knowledge_base',
            'hide_empty' => $hide_empty,
            'parent'     => 0,
            'offset'     => 0,
            'number'     => $kb_per_page,
            'include'    => $include,
            'exclude'    => $exclude,
            'order'      => $kb_order,
            'orderby'    => $kb_orderby,
            'meta_key'   => 'kb_order'
        ];
        $kb_terms_query_args = betterdocs()->query->terms_query( $kb_terms_args );
        $kb_terms = betterdocs()->query->get_terms( $kb_terms_query_args );

        $doc_terms_args = [
            'hide_empty' => true,
            'taxonomy'   => 'doc_category',
            'parent'     => 0,
            'order'      => $kb_order,
            'orderby'    => $kb_orderby,
            'number'     => 'all'
        ];

        if ( $nested_subcategory == 'false' ) {
            unset( $doc_terms_args['parent'] );
        }
        if ( ! is_wp_error( $kb_terms ) ) {
            foreach ( $kb_terms as $kb ) {
                $doc_terms_args['meta_query'] = [
                    'relation' => 'OR',
                    [
                        'key'     => 'doc_category_knowledge_base',
                        'value'   => isset( $kb->slug ) ? $kb->slug : '',
                        'compare' => 'LIKE'
                    ]
                ];

                if( gettype($kb) == 'array' ) {
                    $kb = (object)$kb;
                }

                $kb->doc_categories = [];
                $doc_categories     = get_terms( $doc_terms_args );
                foreach ( $doc_categories as $doc_category ) {
                    $_counts = betterdocs()->query->get_docs_count( $doc_category, $nested_subcategory == 'false' ? false : true, [
                        'multiple_knowledge_base' => true,
                        'kb_slug'                 => $kb->slug
                    ] );

                    if ( $_counts <= 0 ) {
                        continue;
                    }

                    $attachment_id = get_term_meta( $doc_category->term_id, 'doc_category_image-id', true );

                    if ( ! $attachment_id ) {
                        $doc_category->thumbnail = null;
                    } else {
                        $doc_category->thumbnail = wp_get_attachment_url( $attachment_id );
                    }

                    array_push( $kb->doc_categories, $doc_category );
                }

                array_push( $new_kb_terms, $kb );
            }
        }
        return $new_kb_terms;
    }

    public function get_ordered_doc_categories( $data ) {
        // Use betterdocs_order which will handle fallback logic automatically
        $args = array(
            'taxonomy' => 'doc_category',
            'orderby' => 'betterdocs_order',
            'order' => 'ASC', // Order from lowest to highest
        );

        // Use BetterDocs query method which handles the fallback logic
        $doc_categories = betterdocs()->query->terms_query( $args );

        return rest_ensure_response( $doc_categories );
    }
}
