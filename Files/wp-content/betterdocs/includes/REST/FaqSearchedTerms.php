<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_Error;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class FaqSearchedTerms extends BaseAPI {
    public function permission_check() {
        // Only allow logged-in users with FAQ management capabilities
        return current_user_can( 'read_faq_builder' );
    }

    public function register() {
        $this->get( 'faq-terms-by-keyword-search', [$this, 'search_logic'], [
            'password' => [
                'description' => __( 'The password for password-protected FAQs.', 'betterdocs' ),
                'type'        => 'string',
            ],
            'taxonomy' => [
                'description' => __( 'The FAQ taxonomy to search within.', 'betterdocs' ),
                'type'        => 'string',
            ],
        ] );
        $this->post( 'faq-accordion-toggle', [$this, 'toggle_enable_disable'] );
    }

    public function search_logic( $request ) {
        $keyword = $request->get_param( 'search' );

        if ( empty( $keyword ) ) {
            $error = new WP_Error( 400, __( 'FAQ Search Parameter Cannot Be Empty', 'betterdocs' ) );
            return rest_ensure_response( $error );
        }

        // Scope the search to the requested FAQ taxonomy (General vs. WooCommerce
        // Product FAQ Groups). Whitelisted so an arbitrary taxonomy can't be queried.
        $allowed_taxonomies = [ 'betterdocs_faq_category', 'betterdocs_product_faq_category' ];
        $taxonomy           = $request->get_param( 'taxonomy' );
        if ( ! in_array( $taxonomy, $allowed_taxonomies, true ) ) {
            $taxonomy = 'betterdocs_faq_category';
        }

        $term_ids = [];
        // Map of category term_id => [ matched FAQ ids ] so the UI can show
        // only the FAQs that matched the keyword (not the whole group).
        $term_faq_map = [];

        // Determine allowed post statuses based on user permissions
        $post_status = ['publish'];
        if( current_user_can( 'read_private_docs' ) ) {
            $post_status[] = 'private';
        }
        // The FAQ Builder is an editor-facing tool, so draft FAQs must be
        // searchable too. QA-006: gate drafts on `edit_others_posts` (Editor+)
        // rather than `edit_posts` (Author) — the query has no author scope, so
        // an Author-level user would otherwise see every author's draft FAQs.
        if( current_user_can( 'edit_others_posts' ) ) {
            $post_status[] = 'draft';
        }

        $args = [
            'post_type'      => 'betterdocs_faq',
            'post_status'    => $post_status,
            's'              => $keyword,
            'posts_per_page' => -1
        ];

        // Exclude password-protected posts unless user has permission
        if ( ! current_user_can( 'edit_posts' ) ) {
            $args['has_password'] = false;
        }

        $query = new \WP_Query( $args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_obj = get_post( get_the_ID() );

                // Check if user can access password-protected content
                if ( ! empty( $post_obj->post_password ) ) {
                    $can_access = $this->can_access_password_content( $post_obj, $request );
                    if ( ! $can_access ) {
                        continue; // Skip this FAQ
                    }
                }

                $categories = get_the_terms( get_the_ID(), $taxonomy );

                if ( $categories ) {
                    foreach ( $categories as $category ) {
                        $term_ids[]                           = $category->term_id;
                        $term_faq_map[ $category->term_id ][] = (int) get_the_ID();
                    }
                }
            }
            wp_reset_postdata();
        }

        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'search'     => $keyword
            ]
        );

        foreach ( $terms as $term ) {
            $term_ids[] = $term->term_id;
        }

        $term_ids = array_unique( $term_ids );

        if ( empty( $term_ids ) ) {
            return rest_ensure_response( [] );
        }

        $terms_with_meta = [];

        $terms_payload = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'include'    => $term_ids
            ]
        );

        foreach ( $terms_payload as $term ) {
            $meta                          = get_term_meta( $term->term_id );
            $meta['_betterdocs_faq_order'] = empty( get_term_meta( $term->term_id, '_betterdocs_faq_order', true ) ) ? [] : [get_term_meta( $term->term_id, '_betterdocs_faq_order', true )];
            $term->meta                    = $meta;
            // FAQs in this group that matched the keyword. Empty when the group
            // was matched only by its name (then the UI shows the whole group).
            $term->matched_faqs            = isset( $term_faq_map[ $term->term_id ] )
                ? array_values( array_unique( $term_faq_map[ $term->term_id ] ) )
                : [];
            array_push( $terms_with_meta, $term );
        }

        return rest_ensure_response( $terms_with_meta );
    }

    public function toggle_enable_disable( $request ) {
        $body_params = json_decode( $request->get_body() );

        // QA-005: reject invalid / empty JSON instead of dereferencing null
        // (a PHP 8+ fatal: "Attempt to read property on null").
        if ( ! is_object( $body_params ) ) {
            return new WP_Error(
                'rest_invalid_json',
                __( 'Invalid request body.', 'betterdocs' ),
                array( 'status' => 400 )
            );
        }

        $faq_id = isset( $body_params->faq_id ) ? absint( $body_params->faq_id ) : 0;
        // QA-005: normalize to a stored boolean ('1'/'0') — never write the raw
        // request value straight to post meta.
        $toggle = ( isset( $body_params->toggle ) && $body_params->toggle ) ? '1' : '0';

        if ( $faq_id != 0 ) {
            // Security check: Verify user can edit this FAQ post
            if ( ! current_user_can( 'edit_post', $faq_id ) ) {
                return new WP_Error(
                    'rest_cannot_edit',
                    __( 'Sorry, you are not allowed to edit this FAQ.', 'betterdocs' ),
                    array( 'status' => 403 )
                );
            }

            // Verify the post is actually a FAQ post type
            $post = get_post( $faq_id );
            if ( ! $post || $post->post_type !== 'betterdocs_faq' ) {
                return new WP_Error(
                    'rest_post_invalid_id',
                    __( 'Invalid FAQ ID.', 'betterdocs' ),
                    array( 'status' => 404 )
                );
            }

            $previous_toggle = get_post_meta( $faq_id, 'faq_open_by_default', true );
            return update_post_meta( $faq_id, 'faq_open_by_default', $toggle, $previous_toggle );
        }

        return new WP_Error(
            'rest_missing_callback_param',
            __( 'FAQ ID is required.', 'betterdocs' ),
            array( 'status' => 400 )
        );
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
        if ( empty( $request ) || empty( $request['password'] ) ) {
            return false;
        }

        // Double-check the request password.
        return hash_equals( $post->post_password, $request['password'] );
    }
}
