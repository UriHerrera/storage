<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- core docs taxonomy REST endpoints; meta filtering required.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- endpoint exposes user-driven exclusion.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table identifiers (WP-provided) and dynamic %s placeholders are intentional.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- the uncategorized-docs scan needs raw SQL because WP_Query has no NOT-IN-via-subquery primitive.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- list is rebuilt per-request from live post/term state; cache would mask uncategorized status changes.
namespace WPDeveloper\BetterDocs\REST;

use stdClass;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\Helper;

class DocCategories extends BaseAPI {
    public function permission_check(): bool {
        return true;
        // return current_user_can( 'edit_docs' );
    }

    public function register() {
        $this->get( 'doc-categories', array( $this, 'get_response' ), array(
            'password' => array(
                'description' => __( 'The password for password-protected docs.', 'betterdocs' ),
                'type' => 'string'
            )
        ) );
        $this->get( 'doc-categories-kb', array( $this, 'doc_categories_kb_response' ) );
    }

    public function doc_categories_kb_response( $request ) {
        $mkb              = ! empty( $request->get_param( 'knowledge_base' ) ) ? get_term( $request->get_param( 'knowledge_base' ), 'knowledge_base' ) : '';
        $mkb              = ! empty( $mkb ) ? $mkb->slug : '';
        $suppress_filters = ! empty( $request->get_param( 'suppress_filters' ) ) ? $request->get_param( 'suppress_filters' ) : '';

        $terms_query = betterdocs()->query->terms_query(
            array(
                'parent' => 0,
                'hide_empty' => true,
                'taxonomy' => 'doc_category',
                'orderby' => 'betterdocs_order',
                'order' => 'ASC'
            )
        );

        if ( ! empty( $suppress_filters ) ) {
            $terms_query[ 'suppress_filters' ] = $suppress_filters;
        }

        if ( ! empty( $mkb ) ) {
            $terms_query[ 'meta_query' ] = array(
                'relation' => 'AND',
                array(
                    'key' => 'doc_category_knowledge_base',
                    'value' => $mkb,
                    'compare' => 'LIKE'
                )
            );
            $terms_query[ 'order' ] = 'ASC';
        }

        $terms = get_terms( $terms_query );

        $terms = $this->convert_terms_to_array_of_std_objects( $terms );

        return $terms;
    }

    /**
     * Reorder a list of doc rows according to a saved id sequence.
     * Ids in $saved_order keep that order; ids not present are appended.
     *
     * @param array $docs        Doc data rows (each has an 'id' key).
     * @param array $saved_order Ordered list of post ids from `_docs_order_<lang>`.
     * @return array
     */
    private function sort_by_saved_order( $docs, $saved_order ) {
        if ( empty( $docs ) || empty( $saved_order ) ) {
            return $docs;
        }

        $saved_order = array_map( 'intval', (array) $saved_order );
        $position    = array_flip( $saved_order );
        $tail_index  = count( $saved_order );

        $sorted = $docs;
        usort( $sorted, function ( $a, $b ) use ( $position, &$tail_index ) {
            $a_id = isset( $a['id'] ) ? (int) $a['id'] : 0;
            $b_id = isset( $b['id'] ) ? (int) $b['id'] : 0;

            $a_pos = isset( $position[ $a_id ] ) ? $position[ $a_id ] : PHP_INT_MAX;
            $b_pos = isset( $position[ $b_id ] ) ? $position[ $b_id ] : PHP_INT_MAX;

            return $a_pos <=> $b_pos;
        } );

        return $sorted;
    }

    private function convert_terms_to_array_of_std_objects( $payload ) {
        $terms = array();

        foreach ( $payload as $term ) {
            $object = new stdClass();

            $object->term_id          = $term->term_id;
            $object->name             = $term->name;
            $object->slug             = $term->slug;
            $object->term_group       = $term->term_group;
            $object->term_taxonomy_id = $term->term_taxonomy_id;
            $object->taxonomy         = $term->taxonomy;
            $object->description      = $term->description;
            $object->parent           = $term->parent;
            $object->count            = $term->count;
            $object->filter           = $term->filter;
            $object->meta             = $term->meta;

            array_push( $terms, $object );
        }

        return $terms;
    }

    public function get_response( $request ) {
        global $wpdb;

        $mkb      = $request->get_param( 'knowledge_base' );
        $per_page = $request->get_param( 'per_page' );
        $page     = $request->get_param( 'page' );

        $default_args = array(
            'hide_empty' => false,
            'taxonomy' => 'doc_category',
            'orderby' => 'betterdocs_order',
            'order' => 'ASC'
        );

        if ( 0 != $per_page ) {
            $default_args[ 'number' ] = $per_page;
        }

        if ( 0 != $page ) {
            $default_args[ 'offset' ] = ( $page * $per_page ) - $per_page;
        }

        $terms_query = betterdocs()->query->terms_query( $default_args );

        if ( ! empty( $mkb ) ) {
            $terms_query[ 'meta_query' ] = array(
                'relation' => 'AND',
                array(
                    'key' => 'doc_category_knowledge_base',
                    'value' => $mkb,
                    'compare' => 'LIKE'
                )
            );
            $terms_query[ 'order' ] = 'ASC';
        }

        $terms    = get_terms( $terms_query );
        $response = array();

        // Determine allowed post statuses based on user permissions
        $post_status = array( 'publish' );
        if ( current_user_can( 'read_private_docs' ) ) {
            $post_status[] = 'private';
        }
        // Admin users with edit_docs capability should see all post statuses
        if ( current_user_can( 'edit_docs' ) ) {
            $post_status = array( 'publish', 'draft', 'pending', 'private', 'future' );
        }

        foreach ( $terms as $term ) {
            $original_args = array(
                'post_type' => 'docs',
                'posts_per_page' => '-1',
                'post_status' => $post_status,
                'term_id' => $term->term_id,
                'term_slug' => $term->slug,
                'nested_subcategory' => false,
                'orderby' => 'betterdocs_order'
            );

            // Exclude password-protected posts unless user has permission
            if ( ! current_user_can( 'edit_posts' ) ) {
                $original_args[ 'has_password' ] = false;
            }

            if ( ! empty( $mkb ) ) {
                $original_args[ 'multiple_kb' ] = true;
                $original_args[ 'kb_slug' ]     = $mkb;
            }

            $query_args = betterdocs()->query->docs_query_args( $original_args );

            $posts                      = betterdocs()->query->get_posts( $query_args, true );
            $response[ $term->term_id ] = array();

            if ( ! $posts->have_posts() ) {
                wp_reset_postdata();
            }
            while ( $posts->have_posts() ):
                $posts->the_post();
                $post_obj = get_post( get_the_ID() );

                // Double-check password protection for individual posts
                if ( ! empty( $post_obj->post_password ) ) {
                    $can_access = $this->can_access_password_content( $post_obj, $request );
                    if ( ! $can_access ) {
                        continue; // Skip this post
                    }
                }

                $data = $this->get_doc_data( get_the_ID(), $request );
                array_push( $response[ $term->term_id ], $data );
            endwhile;

            wp_reset_postdata();
            wp_reset_query(); // phpcs:ignore WordPress.WP.DiscouragedFunctions.wp_reset_query_wp_reset_query -- explicit global WP_Query reset after a custom loop; wp_reset_postdata() above only restores post data.

            // WP_Query's `orderby=post__in` is stripped by some plugins/filters
            // (notably WPML on REST requests), so apply the saved order in PHP
            // here as the source of truth. Posts not in the saved order are
            // appended at the end in their existing query order.
            $response[ $term->term_id ] = $this->sort_by_saved_order(
                $response[ $term->term_id ],
                betterdocs()->query->get_docs_order_by_terms( $term->term_id )
            );
        }

        /**
         * Uncategories Docs
         */
        // Build secure query for uncategorized docs with proper post status filtering.
        // $wpdb->posts / $wpdb->term_relationships / $wpdb->term_taxonomy are WP-provided
        // table identifiers (safe to interpolate). Dynamic %s placeholder count is built
        // from a fixed-shape $post_status array.
        $post_status_placeholders = implode( ',', array_fill( 0, count( $post_status ), '%s' ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- WP table identifiers; placeholders dynamically generated to match $post_status size.
        $_post__not_in_query = $wpdb->prepare(
            "SELECT ID as post_id from $wpdb->posts WHERE post_type = %s AND post_status IN ($post_status_placeholders) AND post_status != 'trash' AND post_status != 'auto-draft' AND ID NOT IN ( SELECT object_id as post_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ( SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s ) )",
            array_merge( array( 'docs' ), $post_status, array( 'doc_category' ) )
        );

        $_post__not_in = $wpdb->get_col( $_post__not_in_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared above.

        if ( ! empty( $_post__not_in ) ) {
            $uncategorized_docs       = array();
            $uncategorized_query_args = array(
                'post_type' => 'docs',
                'post_status' => $post_status,
                'post__in' => $_post__not_in
            );

            // Exclude password-protected posts unless user has permission
            if ( ! current_user_can( 'edit_posts' ) ) {
                $uncategorized_query_args[ 'has_password' ] = false;
            }

            $_uncategorized_docs_query = new \WP_Query( $uncategorized_query_args );

            if ( ! $_uncategorized_docs_query->have_posts() ) {
                wp_reset_postdata();
            }
            while ( $_uncategorized_docs_query->have_posts() ):
                $_uncategorized_docs_query->the_post();
                $post_obj = get_post( get_the_ID() );

                // Double-check password protection for individual posts
                if ( ! empty( $post_obj->post_password ) ) {
                    $can_access = $this->can_access_password_content( $post_obj, $request );
                    if ( ! $can_access ) {
                        continue; // Skip this post
                    }
                }

                $data = $this->get_doc_data( get_the_ID(), $request );
                array_push( $uncategorized_docs, $data );
            endwhile;

            wp_reset_postdata();
            wp_reset_postdata();

            $response[ 'uncategorized' ] = $uncategorized_docs;
        }

        unset( $terms_query[ 'offset' ] );
        unset( $terms_query[ 'number' ] );
        $total_terms = wp_count_terms( $terms_query );

        return array(
            'data' => $response,
            'total_terms' => $total_terms
        );
    }

    /**
     * Get Doc Data Based On Doc ID
     *
     * @param int $id Post ID
     * @param WP_REST_Request $request REST request object
     * @return array
     */
    public function get_doc_data( $id, $request = null ) {
        $post_data = get_post( $id );
        $data      = array(
            'author' => (int) $post_data->post_author,
            'author_info' => array(
                'name' => get_the_author_meta( 'display_name', $post_data->post_author ),
                'author_nicename' => get_the_author_meta( 'nicename', $post_data->post_author ),
                'author_url' => get_author_posts_url( $post_data->post_author )
            ),
            'unique_id' => uniqid( 'doc' ),
            'id' => $post_data->ID,
            'title' => $post_data->post_title,
            'slug' => get_post_field( 'post_name', $id ),
            'link' => get_permalink( $id ),
            'status' => get_post_status(),
            'date' => $post_data->post_date,
            'date_gmt' => $post_data->post_date_gmt,
            'doc_category' => wp_get_post_terms( $id, 'doc_category', array( 'fields' => 'ids' ) ),
            'doc_tag' => wp_get_post_terms( $id, 'doc_tag', array( 'fields' => 'ids' ) ),
            'comment_status' => $post_data->comment_status
        );

        // Only expose password to users with edit permissions
        if ( current_user_can( 'edit_docs' ) ) {
            $data[ 'password' ] = $post_data->post_password;
        }

        // Add password protection indicator
        if ( ! empty( $post_data->post_password ) ) {
            $data[ 'password_protected' ] = true;
        } else {
            $data[ 'password_protected' ] = false;
        }

        if ( taxonomy_exists( 'knowledge_base' ) ) {
            $data[ 'knowledge_base' ] = wp_get_post_terms( $id, 'knowledge_base', array( 'fields' => 'ids' ) );
        }

        return $data;
    }

    /**
     * Checks if the user can access password-protected content.
     *
     * This method determines whether we need to override the regular password
     * check in core with a filter.
     *
     * @param WP_Post         $post    Post to check against.
     * @param WP_REST_Request $request Request data to check.
     * @return bool True if the user can access password-protected content, otherwise false.
     */
    public function can_access_password_content( $post, $request ) {
        if ( empty( $post->post_password ) ) {
            // No filter required.
            return true;
        }

        /*
         * Users always get access to password protected content if they have
         * the `edit_post` meta capability.
         */
        if ( current_user_can( 'edit_post', $post->ID ) ) {
            return true;
        }

        // No password provided in request, no auth.
        if ( empty( $request ) || empty( $request[ 'password' ] ) ) {
            return false;
        }

        // Double-check the request password.
        return hash_equals( $post->post_password, $request[ 'password' ] );
    }
}
