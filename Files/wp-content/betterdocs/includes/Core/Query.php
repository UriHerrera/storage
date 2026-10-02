<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query,WordPress.DB.SlowDBQuery.slow_db_query_tax_query,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- core docs query builder; meta/tax filtering required for docs/category/KB filtering.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- excludes are user-driven (settings UI) and part of the query builder's public contract.
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Query;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class Query extends Base {
    private $container;
    protected $database;
    protected $settings;

    public function __construct( Container $container, Database $database, Settings $settings ) {
        $this->container = $container;
        $this->database  = $database;
        $this->settings  = $settings;

        add_action( 'parse_term_query', array( $this, 'parse_term_query' ) );
        // add_action( 'parse_query', [$this, 'parse_query'], 1 );
        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 1 );
        add_filter( 'betterdocs_base_terms_args', array( $this, 'modify_terms_args_for_private_docs' ), 10, 1 );
        // Drops terms whose private-inclusive count is 0 after `hide_empty` was
        // turned off by modify_terms_args_for_private_docs().
        add_filter( 'get_terms', array( $this, 'filter_terms_for_private_docs' ), 10, 3 );

        // Invalidate cached doc-category counts on any write that could change them.
        add_action( 'save_post_docs', array( $this, 'flush_term_counts_cache' ) );
        add_action( 'deleted_post', array( $this, 'flush_term_counts_cache_on_post' ), 10, 2 );
        add_action( 'edited_doc_category', array( $this, 'flush_term_counts_cache' ) );
        add_action( 'created_doc_category', array( $this, 'flush_term_counts_cache' ) );
        add_action( 'delete_doc_category', array( $this, 'flush_term_counts_cache' ) );
        add_action( 'set_object_terms', array( $this, 'flush_term_counts_cache_on_set' ), 10, 4 );
        // wp_update_term_count_now() — the `wp term recount` repair path, and any core
        // count update — fires edited_term_taxonomy, not any of the above. Without this
        // the recount fixes the DB while we keep serving the cached counts (#166).
        add_action( 'edited_term_taxonomy', array( $this, 'flush_term_counts_cache_on_term_taxonomy' ), 10, 2 );

        /**
         * These below filters are hooked for navigation only.
         *
         * For old version of this portion.
         * @see `betterdocs_single_post_nav` filter
         *
         * For details:
         * @see https://developer.wordpress.org/reference/functions/get_next_post/
         * @see https://developer.wordpress.org/reference/functions/get_previous_post/
         *
         * @link https://developer.wordpress.org/reference/hooks/get_adjacent_post_where/
         */
        add_filter( 'get_next_post_where', array( $this, 'next_post_where' ), 99, 5 );
        add_filter( 'get_previous_post_where', array( $this, 'previous_post_where' ), 99, 5 );

        $this->init();

        /**
         * Modify Popular Docs Query (For Shortcode & Widget)
         */
        add_filter( 'posts_clauses', array( $this, 'mod_query_popular_docs' ), 10, 2 );
    }

    public function mod_query_popular_docs( $clauses, $wp_query ) {
        if ( isset( $wp_query->query[ 'meta_key' ] ) ) {
            if ( '_betterdocs_meta_views' == $wp_query->query[ 'meta_key' ] ) {
                global $wpdb;
                $order                = isset( $wp_query->query[ 'order' ] ) ? $wp_query->query[ 'order' ] : '';
                $order_by_query       = ( 'ASC' == $order || 'DESC' == $order ) ? "SUM({$wpdb->prefix}betterdocs_analytics.impressions) {$order}" : ( 'MODIFIED' == $order ? "{$wpdb->prefix}posts.post_modified_gmt DESC" : "{$wpdb->prefix}posts.post_date_gmt DESC" );
                $clauses[ 'join' ]    = "JOIN {$wpdb->prefix}betterdocs_analytics ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}betterdocs_analytics.post_id";
                $clauses[ 'where' ]   = ! current_user_can( 'read_private_docs' ) ? "AND ( ( {$wpdb->prefix}posts.post_type = 'docs' ) AND ( {$wpdb->prefix}posts.post_status = 'publish' OR {$wpdb->prefix}posts.post_status = 'future' ) )" : "AND ( ( {$wpdb->prefix}posts.post_type = 'docs' ) AND ( {$wpdb->prefix}posts.post_status = 'publish' OR {$wpdb->prefix}posts.post_status = 'future' OR {$wpdb->prefix}posts.post_status = 'draft' OR {$wpdb->prefix}posts.post_status = 'pending' OR {$wpdb->prefix}posts.post_status = 'private' ) )";
                $clauses[ 'orderby' ] = $order_by_query;
                $clauses[ 'groupby' ] = "{$wpdb->prefix}betterdocs_analytics.post_id";
                if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
                    global $sitepress;
                    if ( $sitepress->is_setup_complete() ) {
                        $constant_language_code = ICL_LANGUAGE_CODE;
                        $clauses[ 'join' ] .= " JOIN {$wpdb->prefix}icl_translations ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}icl_translations.element_id";
                        $clauses[ 'where' ] .= " AND ( {$wpdb->prefix}icl_translations.language_code = '{$constant_language_code}' ) AND ( {$wpdb->prefix}icl_translations.element_type = CONCAT('post_', {$wpdb->prefix}posts.post_type ) )";
                    }
                }
            }
        }
        return $clauses;
    }

    public function init() {
    }

    /**
     * Modify terms args to include terms whose only docs are private, for users allowed to read them.
     *
     * Gated on being logged in rather than on `read_private_docs`, because a doc's own
     * author may read their private doc without holding that capability. Which docs
     * actually count is decided per post by can_read_doc().
     *
     * @param array $args
     * @return array
     */
    public function modify_terms_args_for_private_docs( $args ) {
        // Logged-out visitors always get WordPress' stock `hide_empty` behaviour.
        if ( ! is_user_logged_in() || ! isset( $args[ 'taxonomy' ] ) ) {
            return $args;
        }

        /**
         * Let add-ons opt their own taxonomies into the private-docs rescue path.
         *
         * BetterDocs Pro hooks this for `knowledge_base` (Multiple KB), where docs
         * ARE assigned to the KB term directly.
         *
         * @param array $args Term query args.
         */
        $_filtered = apply_filters( 'betterdocs_modify_terms_args_for_private_docs', $args );
        if ( is_array( $_filtered ) ) {
            $args = $_filtered;
        }

        // Already handled by an add-on (e.g. Pro's Multiple KB).
        if ( ! empty( $args[ '_betterdocs_filter_private' ] ) ) {
            return $args;
        }

        /**
         * Taxonomies whose terms have docs assigned directly, so a private-inclusive
         * object count can rescue a term WordPress dropped for `hide_empty`.
         *
         * `knowledge_base` is included so a KB whose only docs are private still shows
         * up for privileged users even on older Pro builds that predate the filter above.
         */
        $supported_taxonomies = array( 'doc_category', 'knowledge_base' );
        if ( ! in_array( $args[ 'taxonomy' ], $supported_taxonomies, true ) ) {
            return $args;
        }

        // If hide_empty is true, we need to modify the logic to include terms with private docs
        if ( isset( $args[ 'hide_empty' ] ) && $args[ 'hide_empty' ] ) {
            // Set hide_empty to false and we'll filter manually later
            $args[ 'hide_empty' ] = false;
            // Add a flag to indicate we need to filter manually
            $args[ '_betterdocs_filter_private' ] = true;
        }

        return $args;
    }

    public function parse_term_query( $term_query ) {
        if ( empty( $term_query->query_vars[ 'taxonomy' ] ) ) {
            return;
        }

        if ( ! in_array( 'doc_category', $term_query->query_vars[ 'taxonomy' ], true ) ) {
            return;
        }

        global $current_screen;

        if ( null == $current_screen ) {
            return;
        }

        if ( 'doc_category' !== $current_screen->taxonomy || 'edit-doc_category' != $current_screen->id ) {
            return;
        }

        // Use base meta key for admin listing - fallback logic is handled in set_tax_order
        $meta_key = 'doc_category_order';

        $term_query->query_vars[ 'meta_query' ] = array(
            array(
                'key' => $meta_key,
                'type' => 'NUMERIC'
            )
        );

        $term_query->query_vars[ 'orderby' ] = 'meta_value_num';
    }

    public function parse_query( &$query ) {
        // dump( is_single(), $query->query_vars );
        // if ( is_single() && isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] == 'docs' ) {
        //     $query->is_single = false;
        //     $query->is_archive = true;
        //     $query->set( 'knowledge_base', $query->query_vars['docs'] );
        // }
    }

    public function pre_get_posts( &$query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( is_tax( 'doc_category' ) ) {
            $query->set( 'post_type', 'docs' );
            $query->set( 'posts_per_archive_page', -1 );

            $term = get_term_by( 'slug', $query->get( 'doc_category', '' ), 'doc_category' );

            if ( $term && isset( $term->term_id ) ) {
                $post__in = $this->get_docs_order_by_terms( $term->term_id );
            } else {
                $post__in = array();
            }
            if ( ! empty( $post__in ) ) {
                $query->set( 'orderby', 'post__in' );
                $query->set( 'post__in', $post__in );
            }

            // if ( ! empty( $query->query_vars['knowledge_base'] ) ) {
            //     $query->betterdocs_terms = get_terms( [
            //         'taxonomy'   => 'doc_category',
            //         'parent'     => 0,
            //         'hide_empty' => true,
            //         'meta_query' => [
            //             'relation' => 'OR',
            //             [
            //                 'key'     => 'doc_category_knowledge_base',
            //                 'value'   => $query->query_vars['knowledge_base'],
            //                 'compare' => 'LIKE'
            //             ]
            //         ]
            //     ] );
            // }
        }

        // dump( is_archive(), $query->query_vars );
    }

    /**
     * Get docs orders by term_id.
     *
     * @since 2.5.0
     * @param int $term_id
     *
     * @return array
     */
    public function get_docs_order_by_terms( $term_id ) {
        global $wpdb;

        // Get language-specific meta key with fallback
        $meta_key    = \WPDeveloper\BetterDocs\Utils\Helper::get_meta_key_with_fallback( '_docs_order', $term_id );
        $_docs_order = get_term_meta( $term_id, $meta_key, true );

        $query     = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = %d", $term_id );
        $query_key = "docs_order_by_terms_{$term_id}_{$meta_key}_" . md5( $query );

        if ( ( $results = $this->database->get_cache( $query_key ) ) !== false ) {
            return $results;
        }

        if ( ! empty( $_docs_order ) ) {
            $_docs_order = explode( ',', $_docs_order );
            $new_ids     = array();

            // $query is prepared upstream; results are cached via $this->database->get_cache above (line 210).
            $results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

            if ( is_array( $results ) && ! empty( $results ) ) {
                $object_ids = array_filter(
                    $results,
                    function ( $value ) use ( $_docs_order ) {
                        return ! in_array( $value->object_id, $_docs_order );
                    }
                );

                if ( ! empty( $object_ids ) ) {
                    array_walk(
                        $object_ids,
                        function ( $value ) use ( &$new_ids ) {
                            $new_ids[  ] = $value->object_id;
                        }
                    );
                }
            }

            $_docs_order = array_merge( $new_ids, $_docs_order );
            $this->database->set_cache( $query_key, $_docs_order, 1 );

            return $_docs_order;
        }

        return array();
    }

    /**
     * For determine the next post ID
     *
     * @link https://developer.wordpress.org/reference/hooks/get_adjacent_post_where/
     *
     * @param mixed $where
     * @param mixed $in_same_term
     * @param mixed $excluded_terms
     * @param mixed $taxonomy
     * @param mixed $post
     * @return mixed
     */
    public function next_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
        return $this->get_adjacent_post_id( 'next', $where, $post, $taxonomy );
    }

    /**
     * For determine the previous post ID
     *
     * @link https://developer.wordpress.org/reference/hooks/get_adjacent_post_where/
     *
     * @param mixed $where
     * @param mixed $in_same_term
     * @param mixed $excluded_terms
     * @param mixed $taxonomy
     * @param mixed $post
     * @return mixed
     */
    public function previous_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
        return $this->get_adjacent_post_id( 'previous', $where, $post, $taxonomy );
    }

    /**
     * Get where clause for next/previous post ID mainly used in post navigation.
     *
     * @see `previous_post_where` and `next_post_where` methods.
     *
     * @param mixed $adjacent
     * @param mixed $where
     * @param mixed $post
     * @param mixed $taxonomy
     * @return mixed
     */
    private function get_adjacent_post_id( $adjacent, $where, $post, $taxonomy ) {
        if ( 'doc_category' !== $taxonomy ) {
            return $where;
        }

        $_id    = null;
        $_terms = get_the_terms( $post->ID, 'doc_category' );
        if ( empty( $_terms ) ) {
            return $where;
        }

        global $wp_query, $wpdb;

        $_docs_order = $this->get_docs_order_by_terms( $_terms[ 0 ]->term_id );

        $_orderby    = $this->settings->get( 'alphabetically_order_post', 'betterdocs_order' );
        $_order      = $this->settings->get( 'docs_order', 'ASC' );
        $_docs_order = 'betterdocs_order' === $_orderby ? $_docs_order : array();

        if ( empty( $_docs_order ) ) {
            $statuses = array( 'publish' );

            if ( is_user_logged_in() ) {
                $statuses[  ] = 'private';
            }

            $_args = array(
                'post_status' => $statuses,
                // Required: with an explicit `post_status` WordPress only narrows private
                // docs to the ones the user may read when `perm` is `readable`. Without it,
                // listing `private` would expose every private doc to any logged-in user.
                'perm' => 'readable',
                'term_id' => $_terms[ 0 ]->term_id
            );
            if ( isset( $wp_query->query_vars[ 'doc_category' ] ) ) {
                $_args[ 'tax_query' ][  ] = array(
                    'taxonomy' => 'doc_category',
                    'field' => 'slug',
                    'terms' => $wp_query->query_vars[ 'doc_category' ],
                    'operator' => 'AND',
                    'include_children' => false
                );
            } elseif ( isset( $wp_query->query_vars[ 'name' ] ) && isset( $wp_query->query_vars[ 'post_type' ] ) && 'docs' == $wp_query->query_vars[ 'post_type' ] ) {
                $_post     = get_page_by_path( $wp_query->query_vars[ 'name' ], OBJECT, 'docs' );
                $doc_terms = array();

                if ( isset( $_post->ID ) ) {
                    $terms = get_the_terms( $_post->ID, 'doc_category' );
                    if ( ! empty( $terms ) ) {
                        $doc_terms = wp_list_pluck( $terms, 'term_id' );
                    }
                }

                if ( ! empty( $doc_terms ) ) {
                    $_args[ 'tax_query' ][  ] = array(
                        'taxonomy' => 'doc_category',
                        'field' => 'term_id',
                        'terms' => $doc_terms,
                        'operator' => 'AND',
                        'include_children' => false
                    );
                    $_args[ 'term_id' ] = $doc_terms[ 0 ];
                }
            } else {
                // Fallback: use the term from current post
                $_args[ 'tax_query' ][  ] = array(
                    'taxonomy' => 'doc_category',
                    'field' => 'term_id',
                    'terms' => $_terms[ 0 ]->term_id,
                    'operator' => 'AND',
                    'include_children' => false
                );
            }

            $_args[ 'orderby' ] = $_orderby;
            if ( 'betterdocs_order' != $_orderby ) {
                $_args[ 'order' ] = $_order;
            }

            /**
             * Before Query
             */
            do_action_ref_array( 'betterdocs_navigation_docs_query', array( &$_args ) );
            $docs = $this->get_posts( $this->docs_query_args( $_args ) );

            $_docs = array();
            if ( $docs->have_posts() ) {
                array_map(
                    function ( $_post ) use ( &$_docs ) {
                        $_docs[  ] = $_post->ID;
                    },
                    $docs->posts
                );
            }

            $_docs_order = $_docs;
        }

        $_docs_order = apply_filters( 'betterdocs_adjacent_docs_order', $_docs_order, $_terms );

        // Ensure consistent type comparison by converting all IDs to integers
        $_docs_order = array_map( 'intval', $_docs_order );
        $_docs_order = array_values( $_docs_order ); // Re-index array after type conversion

        $_id_index = array_search( (int) $post->ID, $_docs_order, true );

        // If current post not found in order list, return original where clause
        if ( false === $_id_index ) {
            return $where;
        }

        $_id_index = 'next' === $adjacent ? $_id_index + 1 : $_id_index - 1;
        $_id       = isset( $_docs_order[ $_id_index ] ) ? (int) $_docs_order[ $_id_index ] : null;

        // Fix: replace only ID comparison in where clause using regex
        if ( $_id ) {
            // This replaces any 'p.ID < N', 'p.ID > N', 'p.ID <= N', or 'p.ID >= N' etc, with 'p.ID = $_id'
            $where = preg_replace( '/p\.ID\s*[<>!=]+\s*\d+/', 'p.ID = ' . (int) $_id, $where );
        }

        return $where;
    }

    public function parse_terms_args( $args = array() ) {
        $_default_args = array(
            'hide_empty' => true,
            'taxonomy' => 'doc_category'
        );

        // OrderBy & Order
        $_orderby = ! empty( $args[ 'orderby' ] ) && 1 != $args[ 'orderby' ] ? $args[ 'orderby' ] : 'name';
        $_order   = ! empty( $args[ 'order' ] ) ? $args[ 'order' ] : '';

        if ( 'betterdocs_order' == $_orderby ) {
            // Use base meta key - fallback logic will be handled in the terms_clauses filter
            $args[ 'meta_key' ] = 'doc_category_order';
            $args[ 'orderby' ]  = 'meta_value_num';
            $args[ 'order' ]    = 'ASC';
        } else {
            $args[ 'orderby' ] = $_orderby;
            $args[ 'order' ]   = $_order;
        }

        // Nested Sub Category
        // $args['parent'] = 0;
        // if ( $nested_subcategory == true ) {
        // }

        if ( ! isset( $args[ 'number' ] ) ) {
            global $wp_query;
            if ( null === $wp_query->query || ( isset( $wp_query->query[ 'post_type' ] ) && 'docs' != $wp_query->query[ 'post_type' ] ) ) {
                $args[ 'number' ] = 4;
            }
        }

        // Includes
        if ( isset( $args[ 'include' ] ) ) {
            $_include          = ! is_array( $args[ 'include' ] ) ? explode( ',', $args[ 'include' ] ) : $args[ 'include' ];
            $args[ 'include' ] = $_include;
            $args[ 'orderby' ] = 'include';

            unset( $args[ 'parent' ] );
        }

        $_meta_query          = ! empty( $args[ 'meta_query' ] ) ? $args[ 'meta_query' ] : array();
        $args[ 'meta_query' ] = apply_filters( 'betterdocs_taxonomy_object_meta_query', $_meta_query, $args );

        return apply_filters( 'betterdocs_category_terms_object', wp_parse_args( $args, $_default_args ), $args );
    }

    public function get_terms( $args ) {
        $parsed_args = $this->parse_terms_args( $args );
        $terms       = get_terms( $parsed_args );

        // Filter terms manually if we need to consider private docs the current user may read
        if ( isset( $parsed_args[ '_betterdocs_filter_private' ] ) && $parsed_args[ '_betterdocs_filter_private' ] && is_user_logged_in() ) {
            $terms = array_filter( $terms, function ( $term ) {
                // Get the actual count including private docs for users with read_private_docs capability
                return $this->get_private_inclusive_docs_count( $term ) > 0;
            } );
        }

        return $terms;
    }

    /**
     * Drop terms with no visible docs after `hide_empty` was disabled for private docs.
     *
     * modify_terms_args_for_private_docs() turns `hide_empty` off so WordPress stops
     * dropping terms whose only docs are private. Without this counterpart, every
     * genuinely empty term would leak into the listing for privileged users. Runs only
     * for queries carrying the `_betterdocs_filter_private` flag, so anonymous and
     * unflagged queries are untouched.
     *
     * @param array|WP_Error $terms
     * @param array|null     $taxonomies
     * @param array          $args
     * @return array|WP_Error
     */
    public function filter_terms_for_private_docs( $terms, $taxonomies, $args ) {
        if ( empty( $args[ '_betterdocs_filter_private' ] ) || empty( $terms ) || is_wp_error( $terms ) ) {
            return $terms;
        }

        if ( ! is_user_logged_in() ) {
            return $terms;
        }

        // Only term objects can be counted; `ids`, `names`, `count`, ... pass through.
        $_fields = isset( $args[ 'fields' ] ) ? $args[ 'fields' ] : 'all';
        if ( ! in_array( $_fields, array( 'all', 'all_with_object_id' ), true ) ) {
            return $terms;
        }

        $_filtered = array_filter( $terms, function ( $term ) {
            if ( ! is_object( $term ) ) {
                return true;
            }

            return $this->get_private_inclusive_docs_count( $term ) > 0;
        } );

        return array_values( $_filtered );
    }

    /**
     * Docs count for a term, including private docs when the user may read them.
     *
     * KB terms need object-in-term counting rather than the doc_category count path,
     * which BetterDocs Pro provides through the filter below; get_docs_count() is the
     * fallback and already counts via get_objects_in_term() for the term's own taxonomy.
     *
     * @param WP_Term $term
     * @return int
     */
    /**
     * Whether the current user may see a doc in a listing.
     *
     * Public docs are visible to everyone. A private doc is visible to whoever may read
     * it — `read_post` maps to the base `read` capability for the doc's own author and
     * to `read_private_docs` for everyone else, so owners see their own private docs
     * without needing the capability. Other non-public statuses (draft, pending) stay
     * out of listings for everyone, as before.
     *
     * @param int $post_id
     * @return bool
     */
    /**
     * Cache-key fragment identifying whose visibility a cached count reflects.
     *
     * Everyone who can read every private doc sees the same numbers, so they share one
     * `priv` bucket; authors differ from each other and get their own. Logged-out
     * visitors all share bucket `0`, keeping the anonymous cache as hot as before.
     *
     * @return string
     */
    protected function count_cache_viewer_key() {
        if ( ! is_user_logged_in() ) {
            return '0';
        }

        if ( current_user_can( 'read_private_docs' ) ) {
            return 'priv';
        }

        return 'u' . get_current_user_id();
    }

    public function can_read_doc( $post_id ) {
        if ( is_post_publicly_viewable( $post_id ) ) {
            return true;
        }

        return 'private' === get_post_status( $post_id ) && current_user_can( 'read_post', $post_id );
    }

    protected function get_private_inclusive_docs_count( $term ) {
        /**
         * Let add-ons supply their own private-inclusive count for a term.
         *
         * Passing `null` means "not handled" — implementations that don't recognise the
         * term's taxonomy return the value untouched, and we fall back to get_docs_count().
         *
         * @param int|null $count
         * @param WP_Term  $term
         */
        $_count = apply_filters( 'betterdocs_get_term_docs_count_for_private_filter', null, $term );

        if ( null !== $_count ) {
            return (int) $_count;
        }

        // Count nested sub-category docs too, so a parent whose docs live only in its
        // children stays visible to logged-in users exactly as it does for anonymous
        // visitors (whose grid is descendant-aware). get_docs_count( …, true ) already
        // filters each descendant doc through can_read_doc() via get_doc_ids_by_term(),
        // so private/unreadable docs never resurrect a term. A non-nested count here
        // hid those parents from logged-in users only.
        return (int) $this->get_docs_count( $term, true );
    }

    public function get_child_terms( $args ) {
        if ( ! isset( $args[ 'number' ] ) ) {
            global $wp_query;
            if ( null === $wp_query->query || ( isset( $wp_query->query[ 'post_type' ] ) && 'docs' != $wp_query->query[ 'post_type' ] ) ) {
                $args[ 'number' ] = 1;
            }
        }

        return $this->get_terms( $args );
    }

    /**
     * Get POSTs of Docs type.
     *
     * @param mixed $args
     * @return WP_Query
     */
    public function get_posts( $args, $ignore = false ) {
        if ( ! $ignore ) {
            $args = $this->docs_query_args( $args );
        }

        return new WP_Query( $args );
    }

    public function get_taxonomy( $tax = '' ) {
        global $wp_query;
        if ( is_tax( 'knowledge_base' ) ) {
            $_tax = $wp_query->tax_query->queried_terms;
            if ( array_key_exists( 'doc_category', $_tax ) ) {
                $tax = 'doc_category';
            } else {
                $tax = 'knowledge_base';
            }
        } elseif ( is_tax( 'doc_category' ) ) {
            $tax = 'doc_category';
        }

        return $tax;
    }

    public function terms_query( $args = array() ) {
        global $wp_query;
        $_origin_args = $args;

        $default_args = array(
            'hide_empty' => true,
            'taxonomy' => 'doc_category',
            'orderby' => 'name'
        );

        /**
         * Number Set
         *
         * @FIX: If Built-in Docs page off and docs_page in use, then Terms Query Number 4|1 set.
         */
        // if ( $wp_query->query === NULL || ( isset( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] != 'docs' ) ) {
        //     $default_args['number'] = 1;
        // }

        /**
         * Nested Sub Category
         */
        if ( isset( $args[ 'nested_subcategory' ] ) && true == $args[ 'nested_subcategory' ] ) {
            $default_args[ 'parent' ] = 0;
            unset( $args[ 'nested_subcategory' ] );
            // if ( $wp_query->query === NULL || ( isset( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] != 'docs' ) ) {
            //     $default_args['number'] = 4;
            // }
        }

        /**
         * OrderBy and Order
         */
        if ( ! isset( $args[ 'orderby' ] ) ) {
            $_orderby = $this->settings->get( 'terms_orderby', 'name' );
            $_order   = $this->settings->get( 'terms_order', '' );
        } else {
            $_orderby = $args[ 'orderby' ];
            $_order   = ! empty( $args[ 'order' ] ) ? $args[ 'order' ] : '';
        }

        if ( 'betterdocs_order' === $_orderby ) {
            // Use different meta keys for different taxonomies
            if ( isset( $args[ 'taxonomy' ] ) && 'knowledge_base' === $args[ 'taxonomy' ] ) {
                $default_args[ 'meta_key' ] = 'kb_order';
            } else {
                $default_args[ 'meta_key' ] = 'doc_category_order';
            }
            $_orderby = 'meta_value_num';
            $_order   = 'ASC';
        } elseif ( true === $_orderby ) {
            $_orderby = 'name';
        }

        $args[ 'orderby' ] = $_orderby;
        if ( ! empty( $_order ) ) {
            $args[ 'order' ] = $_order;
        }

        /**
         * @todo old hook
         * hook: betterdocs_child_taxonomy_meta_query
         */
        $_multiple_kb = apply_filters(
            'betterdocs_query_args_multiple_kb_enabled',
            isset( $args[ 'multiple_kb' ] ) ? (bool) $args[ 'multiple_kb' ] : false,
            $_origin_args
        );

        $_kb_slug = isset( $args[ 'kb_slug' ] ) ? trim( $args[ 'kb_slug' ] ) : '';

        unset( $args[ 'multiple_kb' ] );
        unset( $args[ 'kb_slug' ] );

        $meta_query = ! empty( $args[ 'meta_query' ] ) ? $args[ 'meta_query' ] : array();
        $meta_query = apply_filters( 'betterdocs_terms_meta_query_args', $meta_query, $_multiple_kb, $_kb_slug, $_origin_args );

        if ( ! empty( $meta_query ) ) {
            $default_args[ 'meta_query' ] = $meta_query;
        }

        if ( ! empty( $args[ 'terms' ] ) ) {
            $args[ 'include' ] = explode( ',', $args[ 'terms' ] );
            $args[ 'orderby' ] = 'include';
            $args[ 'order' ]   = 'ASC';

            unset( $default_args[ 'parent' ] );
            unset( $args[ 'parent' ] );
            unset( $args[ 'terms' ] );
        }

        $_query_args = wp_parse_args( $args, $default_args );
        $_query_args = apply_filters( 'betterdocs_terms_query_args', $_query_args, $_origin_args );

        // Apply private docs logic for logged-in users
        $_query_args = $this->modify_terms_args_for_private_docs( $_query_args );

        return $_query_args;
    }

    public function get_term_parents( $term_id, $taxonomy = 'doc_category', $args = array() ) {
        $term = get_term( $term_id, $taxonomy );
        if ( is_wp_error( $term ) ) {
            return $term;
        }

        if ( ! $term || ! isset( $term->term_id ) ) {
            return array();
        }

        $_lists      = array();
        $origin_term = $term_id;
        $term_id     = $term->term_id;

        $defaults = array(
            'format' => 'name',
            'inclusive' => true
        );

        $args = wp_parse_args( $args, $defaults );

        $args[ 'inclusive' ] = wp_validate_boolean( $args[ 'inclusive' ] );

        $parents = get_ancestors( $term_id, $taxonomy );

        if ( $args[ 'inclusive' ] ) {
            array_unshift( $parents, $term_id );
        }

        foreach ( array_reverse( $parents ) as $term_id ) {
            $parent         = get_term( $term_id, $taxonomy );
            $name           = ( 'slug' === $args[ 'format' ] ) ? $parent->slug : $parent->name;
            $term_permalink = get_term_link( $parent->term_id, $taxonomy );
            $term_permalink = apply_filters( 'betterdocs_breadcrumb_term_permalink', $term_permalink, $term_id );

            $_item = array(
                'url' => $term_permalink,
                'text' => $name
            );

            $_lists[  ] = $_item;
        }

        return apply_filters( 'betterdocs_breadcrumb_archive_lists', $_lists, $origin_term );
    }

    /**
     * Get all non-empty child term IDs recursively for a given taxonomy and parent term.
     *
     * This function retrieves all child terms for a specified taxonomy and parent term,
     * recursively fetching child terms of child terms, and returns only those with a non-zero post count.
     *
     * @param string $taxonomy  The taxonomy name (e.g., 'doc_category').
     * @param int    $parent_id The ID of the parent term to start retrieving children from.
     *
     * @return array An array of non-empty child term IDs.
     */
    public function get_all_child_term_ids( $taxonomy, $parent_id ) {
        $version   = $this->database->get_cache_version( 'betterdocs_term_counts' );
        $cache_key = "bd_term_counts_v{$version}_child_ids_{$taxonomy}_{$parent_id}";

        $cached = wp_cache_get( $cache_key, 'betterdocs' );
        if ( false !== $cached ) {
            return $cached;
        }

        // get_terms( child_of => X ) walks the full descendant tree using WP's
        // internally cached term hierarchy, replacing the previous recursive
        // get_term()-in-a-loop pattern with one call.
        $args = apply_filters(
            'betterdocs_get_child_term_ids_args',
            array(
                'taxonomy'   => $taxonomy,
                'child_of'   => $parent_id,
                'hide_empty' => true,
                'fields'     => 'ids',
            )
        );

        $ids = get_terms( $args );
        $ids = ( is_wp_error( $ids ) || ! is_array( $ids ) ) ? array() : array_map( 'intval', $ids );

        wp_cache_set( $cache_key, $ids, 'betterdocs', HOUR_IN_SECONDS * 6 );

        return $ids;
    }

    /**
     * Get all nested child term IDs of a specific parent term in a taxonomy and return as a comma-separated string.
     *
     * @param string $taxonomy The taxonomy.
     * @param int $parent_id The parent term ID.
     * @return string The comma-separated term IDs.
     */
    public function get_child_term_ids_by_parent_id( $taxonomy, $parent_id ) {
        $term_ids = $this->get_all_child_term_ids( $taxonomy, $parent_id );
        return implode( ',', $term_ids );
    }

    public function get_terms_children( $taxonomy, $parent_id ) {
        $children           = get_term_children( $parent_id, $taxonomy );
        $non_empty_children = array();
        if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
            foreach ( $children as $child_id ) {
                $child_term = get_term( $child_id, $taxonomy );

                // Only include non-empty terms
                if ( $child_term && $child_term->count > 0 ) {
                    $non_empty_children[  ] = $child_term;
                }
            }
        }

        return $non_empty_children;
    }

    public function get_terms_children_ids( $taxonomy, $parent_id ) {
        $children = $this->get_terms_children( $taxonomy, $parent_id );
        $term_ids = wp_list_pluck( $children, 'term_id' );

        return implode( ',', $term_ids );
    }

    public function count_terms_children( $taxonomy, $parent_id ) {
        return count( $this->get_terms_children( $taxonomy, $parent_id ) );
    }

    public function is_inner_templates() {
        if ( is_tax( 'knowledge_base' ) || is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) || is_singular( 'docs' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Summary of docs_query_args
     * @param mixed $args
     * @throws \Exception
     * @return mixed
     */
    public function docs_query_args( $args, $filter = array() ) {
        $_origin_args = $args;

        $default_args = array(
            'post_type' => 'docs'
        );

        if ( ! empty( $args[ 'post_type' ] ) && trim( $args[ 'post_type' ] ) === 'docs_any' ) {
            $default_args[ 'post_type' ]   = 'docs';
            $default_args[ 'post_status' ] = 'any';

            unset( $args[ 'post_type' ] );
        }

        /**
         * OrderBy and Order
         */
        if ( ! isset( $args[ 'orderby' ] ) ) {
            $_orderby = $this->settings->get( 'alphabetically_order_post' );
            $_order   = $this->settings->get( 'docs_order', 'ASC' );
        } else {
            $_orderby = $args[ 'orderby' ];
            $_order   = ! empty( $args[ 'order' ] ) ? $args[ 'order' ] : 'ASC';
        }

        if ( 'betterdocs_order' != $_orderby ) {
            if ( true === $_orderby ) {
                $args[ 'orderby' ] = 'title';
            } else {
                $args[ 'orderby' ] = $_orderby;
            }

            $args[ 'order' ] = $_order;
        } elseif ( 'betterdocs_order' == $_orderby ) {
            unset( $args[ 'orderby' ] );
        }

        if ( empty( $args[ 'orderby' ] ) ) {
            unset( $args[ 'order' ] );
        }

        /**
         * Term ID
         */
        $_term_id = null;
        if ( ! empty( $args[ 'term_id' ] ) ) {
            $_term_id = intval( $args[ 'term_id' ] );
            unset( $args[ 'term_id' ] );
        }

        // if ( $_term_id == null ) {
        //     throw new \Exception( __( '$args["term_id"] cannot be null.', 'betterdocs' ) );
        // }

        /**
         * Term Slug for tax_query
         */
        $_term_slug = '';
        if ( ! empty( $args[ 'term_slug' ] ) ) {
            $_term_slug = trim( $args[ 'term_slug' ] );
            unset( $args[ 'term_slug' ] );
        }

        /**
         * @todo old hook
         * hook: betterdocs_cat_template_multikb
         */

        $_multiple_kb = apply_filters(
            'betterdocs_enable_multiple_knowledge_base',
            isset( $args[ 'multiple_kb' ] ) ? (bool) $args[ 'multiple_kb' ] : false,
            $_origin_args
        );

        $_kb_slug = isset( $args[ 'kb_slug' ] ) ? trim( $args[ 'kb_slug' ] ) : '';

        unset( $args[ 'multiple_kb' ] );
        unset( $args[ 'kb_slug' ] );

        $tax_query = array(
            array(
                'taxonomy' => 'doc_category',
                'field' => 'slug',
                'terms' => $_term_slug,
                'operator' => 'AND',
                'include_children' => false
            )
        );

        if ( ! isset( $args[ 'orderby' ] ) || 'betterdocs_order' == $args[ 'orderby' ] ) {
            $args[ 'orderby' ]  = 'post__in';
            $args[ 'post__in' ] = $this->get_docs_order_by_terms( $_term_id );
        }

        if ( isset( $args[ 'tax_query' ] ) ) {
            $tax_query = $args[ 'tax_query' ];
        }

        $args[ 'tax_query' ] = apply_filters(
            'betterdocs_docs_tax_query_args',
            $tax_query,
            $_multiple_kb,
            $_term_slug,
            $_kb_slug,
            $_origin_args,
            $this->is_inner_templates()
        );
        /**
         * Final parse args
         */
        $args = wp_parse_args( $args, $default_args );

        if ( ! empty( $filter ) ) {
            $filter = array_flip( $filter );
            $args   = array_filter(
                $args,
                function ( $item ) use ( $filter ) {
                    return ! array_key_exists( $item, $filter );
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        $final_args = apply_filters( 'betterdocs_articles_args', $args, $_term_id, $_origin_args );

        // Process betterdocs_order AFTER all filters have run
        if ( isset( $final_args[ 'orderby' ] ) && 'betterdocs_order' == $final_args[ 'orderby' ] ) {
            $docs_order = $this->get_docs_order_by_terms( $_term_id );

            if ( ! empty( $docs_order ) ) {
                $final_args[ 'orderby' ]  = 'post__in';
                $final_args[ 'post__in' ] = $docs_order;
            } else {
                $final_args[ 'orderby' ] = 'menu_order';
                $final_args[ 'order' ]   = 'ASC';
            }
        }

        return $final_args;
    }

    /**
     * The front-end FAQ ordering preference, set via the FAQ Builder header
     * dropdown (the `.betterdocs-dropdown-select` control, saved to the
     * `betterdocs_faq_order` option). The front end mirrors the builder so it
     * shows FAQ groups (and the FAQs inside them) in the same order the admin
     * sees while building.
     *
     * @return string One of: default, most_recent, least_recent, a_to_z, z_to_a, most_questions.
     */
    public function get_faq_order_key() {
        $key     = get_option( 'betterdocs_faq_order', 'default' );
        $allowed = array( 'default', 'most_recent', 'least_recent', 'a_to_z', 'z_to_a', 'most_questions' );

        if ( ! in_array( $key, $allowed, true ) ) {
            $key = 'default';
        }

        return apply_filters( 'betterdocs_faq_order_key', $key );
    }

    /**
     * Translate the FAQ order preference into `get_terms()` order clauses for
     * the FAQ group list. Mirrors the admin builder's ORDER_MAP so the front
     * end matches what's configured there.
     *
     * @return array orderby/order (+ meta_key for the manual `default` order).
     */
    public function faq_terms_order_clause() {
        switch ( $this->get_faq_order_key() ) {
            case 'most_recent':
                return array( 'orderby' => 'term_id', 'order' => 'DESC' );
            case 'least_recent':
                return array( 'orderby' => 'term_id', 'order' => 'ASC' );
            case 'a_to_z':
                return array( 'orderby' => 'name', 'order' => 'ASC' );
            case 'z_to_a':
                return array( 'orderby' => 'name', 'order' => 'DESC' );
            case 'most_questions':
                return array( 'orderby' => 'count', 'order' => 'DESC' );
            case 'default':
            default:
                // Manual drag-drop order stored in the `order` term meta.
                return array( 'meta_key' => 'order', 'orderby' => 'meta_value_num', 'order' => 'ASC' );
        }
    }

    public function faq_terms_query_args( $includes = '', $excludes = '', $args = array(), $taxonomy = 'betterdocs_faq_category' ) {
        $_args = array(
            'taxonomy' => $taxonomy,
            'include' => $includes,
            'exclude' => $excludes,
            'meta_query' => array(
                array(
                    'key' => 'status',
                    'value' => 1,
                    'compare' => '=='
                )
            )
        );

        $_args = array_merge( $_args, $this->faq_terms_order_clause() );

        if ( 'all' == $_args[ 'include' ] ) {
            unset( $_args[ 'include' ] );
            unset( $_args[ 'exclude' ] );
        }

        if ( empty( $_args[ 'exclude' ] ) ) {
            unset( $_args[ 'exclude' ] );
        }

        if ( empty( $_args[ 'include' ] ) ) {
            unset( $_args[ 'include' ] );
        }

        return wp_parse_args( $args, $_args );
    }

    public function get_faq_by_term( $term_id, $taxonomy = 'betterdocs_faq_category' ) {
        global $wpdb;

        $args = array(
            'post_type' => 'betterdocs_faq',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id,
                    'operator' => 'AND'
                )
            ),
            'posts_per_page' => -1
        );

        // Order the FAQs inside the group to mirror the FAQ Builder header
        // dropdown. `default`/`most_questions` keep the manual drag-drop order
        // (the others sort live, just like the admin builder does).
        switch ( $this->get_faq_order_key() ) {
            case 'most_recent':
                $args[ 'orderby' ] = 'ID';
                $args[ 'order' ]   = 'DESC';
                break;
            case 'least_recent':
                $args[ 'orderby' ] = 'ID';
                $args[ 'order' ]   = 'ASC';
                break;
            case 'a_to_z':
                $args[ 'orderby' ] = 'title';
                $args[ 'order' ]   = 'ASC';
                break;
            case 'z_to_a':
                $args[ 'orderby' ] = 'title';
                $args[ 'order' ]   = 'DESC';
                break;
            case 'default':
            case 'most_questions':
            default:
                $args[ 'orderby' ]  = 'post__in';
                $args[ 'post__in' ] = $this->get_faq_orders( $term_id );
                break;
        }

        return new WP_Query( $args );
    }

    public function get_faq_orders( $term_id = null ) {
        global $wpdb;
        $faq_order = get_term_meta( $term_id, '_betterdocs_faq_order', true );
        $faq_order = explode( ',', $faq_order );

        $query     = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = %d", $term_id );
        $query_key = 'betterdocs_faq_order_' . md5( $query );

        if ( ( $results = $this->database->get_cache( $query_key ) ) !== false ) {
            return $results;
        }

        if ( ! empty( $faq_order ) ) {
            $new_ids = array();
            // $query is prepared upstream; results are cached via $this->database->get_cache above (line 980).
            $results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

            if ( ! is_null( $results ) && ! empty( $results ) && is_array( $results ) ) {
                $object_ids = array_filter(
                    $results,
                    function ( $value ) use ( $faq_order ) {
                        return ! in_array( $value->object_id, $faq_order );
                    }
                );

                if ( ! empty( $object_ids ) ) {
                    array_walk(
                        $object_ids,
                        function ( $value ) use ( &$new_ids ) {
                            $new_ids[  ] = $value->object_id;
                        }
                    );
                }
            }

            $faq_order = array_merge( $new_ids, $faq_order );
        }

        $this->database->set_cache( $query_key, $faq_order, 1 );

        return $faq_order;
    }

    public function get_faq_terms( $terms = array(), $taxonomy = 'betterdocs_faq_category' ) {
        $_terms = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'status',
                        'value' => 1,
                        'compare' => '=='
                    )
                )
            )
        );

        if ( ! is_wp_error( $_terms ) ) {
            foreach ( $_terms as $term ) {
                $terms[ $term->term_id ] = $term->name;
            }
        }

        return $terms;
    }

    public function get_doc_terms( $terms = array() ) {
        $_terms = get_terms(
            array(
                'taxonomy' => 'doc_category',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            )
        );

        if ( ! is_wp_error( $_terms ) ) {
            foreach ( $_terms as $term ) {
                $terms[ $term->term_id ] = $term->name;
            }
        }

        return $terms;
    }

    public function flush_term_counts_cache() {
        $this->database->bump_cache_version( 'betterdocs_term_counts' );
    }

    public function flush_term_counts_cache_on_post( $post_id, $post = null ) {
        if ( $post && isset( $post->post_type ) && $post->post_type === 'docs' ) {
            $this->flush_term_counts_cache();
        }
    }

    public function flush_term_counts_cache_on_set( $object_id, $terms, $tt_ids, $taxonomy ) {
        if ( $taxonomy === 'doc_category' ) {
            $this->flush_term_counts_cache();
        }
    }

    /**
     * Invalidate the count cache when a term count is recalculated.
     *
     * wp_update_term_count_now() (a recount, `wp term recount`, or any core count
     * update) fires edited_term_taxonomy for each affected term. The version bump is
     * a single update_option, so it is debounced to once per request with a static
     * flag: one bump already invalidates every cached count, and a bulk recount would
     * otherwise write the option once per term. (#166)
     *
     * @param int    $tt_id    Term taxonomy id.
     * @param string $taxonomy Taxonomy name.
     */
    public function flush_term_counts_cache_on_term_taxonomy( $tt_id, $taxonomy ) {
        static $flushed = false;

        if ( $flushed || ! in_array( $taxonomy, array( 'doc_category', 'knowledge_base' ), true ) ) {
            return;
        }

        $this->flush_term_counts_cache();
        $flushed = true;
    }

    public function get_docs_count( $term, $nested_subcategory = false, $args = array() ) {
        // Validate term object
        if ( ! is_object( $term ) ) {
            return 0;
        }

        $counts = isset( $term->count ) ? $term->count : 0;

        if ( ! isset( $term->term_id ) || ! is_numeric( $term->term_id ) ) {
            return apply_filters( 'betterdocs_docs_count', $counts, $term, $nested_subcategory, $args );
        }

        $version  = $this->database->get_cache_version( 'betterdocs_term_counts' );
        // Counts depend on who is asking: an author sees their own private docs, so the
        // key carries the user id. Logged-out visitors all share user 0.
        $viewer   = $this->count_cache_viewer_key();
        $kb_slug  = isset( $args['kb_slug'] ) ? $args['kb_slug'] : '';
        $multi    = ! empty( $args['multiple_knowledge_base'] ) ? 1 : 0;
        $nested   = $nested_subcategory ? 1 : 0;
        $cache_key = "bd_term_counts_v{$version}_docs_count_{$term->term_id}_{$nested}_{$viewer}_{$kb_slug}_{$multi}";

        $cached = wp_cache_get( $cache_key, 'betterdocs' );
        if ( false !== $cached ) {
            return apply_filters( 'betterdocs_docs_count', $cached, $term, $nested_subcategory, $args );
        }

        if ( false == $nested_subcategory ) {
            // For non-nested categories, we need to recalculate counts based on user capabilities
            // Only proceed if we have a valid term with required properties
            if ( isset( $term->taxonomy ) ) {
                // Get all post IDs for this term
                $post_ids = get_objects_in_term( $term->term_id, $term->taxonomy );

                if ( ! empty( $post_ids ) ) {
                    _prime_post_caches( $post_ids, false, false );

                    // Public docs for everyone, plus any private doc this user may read
                    // (its own author, or a `read_private_docs` holder).
                    $filtered_post_ids = array_filter( $post_ids, function ( $post_id ) {
                        return $this->can_read_doc( $post_id );
                    } );

                    $counts = count( $filtered_post_ids );
                } else {
                    $counts = 0;
                }
            }
        } else {
            $_child_terms_docs_ids = $this->get_doc_ids_by_term( $term, null, $nested_subcategory );
            if ( is_array( $_child_terms_docs_ids ) ) {
                $counts = count( $_child_terms_docs_ids );
            }
        }

        wp_cache_set( $cache_key, $counts, 'betterdocs', HOUR_IN_SECONDS * 6 );

        return apply_filters( 'betterdocs_docs_count', $counts, $term, $nested_subcategory, $args );
    }

    public function get_doc_ids_by_term( $term, $optional = null, $nested_subcategory = false ) {
        // Check if term has required properties and is a valid object
        if ( ! is_object( $term ) || ! isset( $term->term_id ) || ! isset( $term->taxonomy ) || ! is_numeric( $term->term_id ) ) {
            return false;
        }

        $version     = $this->database->get_cache_version( 'betterdocs_term_counts' );
        $viewer      = $this->count_cache_viewer_key();
        $nested      = $nested_subcategory ? 1 : 0;
        $optional_id = is_object( $optional ) && isset( $optional->term_id ) ? (int) $optional->term_id : 0;
        $cache_key   = "bd_term_counts_v{$version}_doc_ids_{$term->term_id}_{$nested}_{$optional_id}_{$viewer}";

        $cached = wp_cache_get( $cache_key, 'betterdocs' );
        if ( false !== $cached ) {
            return $cached;
        }

        $args = array(
            'taxonomy' => $term->taxonomy,
            'include'  => $term->term_id,
        );
        if ( $nested_subcategory ) {
            $args[ 'child_of' ] = $term->term_id;
            unset( $args[ 'include' ] );
        }
        $_child_terms = get_terms( $args );

        if ( ! is_array( $_child_terms ) ) {
            return false;
        }

        array_unshift( $_child_terms, $term );

        $_child_terms_ids      = array_column( $_child_terms, 'term_id' );
        $_child_terms_taxs     = array_column( $_child_terms, 'taxonomy' );
        $_child_terms_docs_ids = get_objects_in_term( $_child_terms_ids, $_child_terms_taxs );

        if ( null !== $optional ) {
            $_optional_doc_ids     = get_objects_in_term( $optional->term_id, $optional->taxonomy );
            $_child_terms_docs_ids = array_intersect( $_child_terms_docs_ids, $_optional_doc_ids );
        }

        if ( ! empty( $_child_terms_docs_ids ) ) {
            _prime_post_caches( $_child_terms_docs_ids, false, false );
        }

        $filtered = array_filter( $_child_terms_docs_ids, function ( $doc_id ) {
            return $this->can_read_doc( $doc_id );
        } );

        wp_cache_set( $cache_key, $filtered, 'betterdocs', HOUR_IN_SECONDS * 6 );

        return $filtered;
    }

    /**
     * Get the common query arguments for WP_Query.
     *
     * @param string $terms The taxonomy.
     * @param string $term_slug The taxonomy term slug.
     * @param array $additional_args Additional arguments to merge with the common arguments.
     * @return array The query arguments.
     */
    private function tax_query_args( $terms, $term_slug, $additional_args = array() ) {
        $common_args = array(
            'post_type' => 'docs',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => $terms,
                    'field' => 'slug',
                    'terms' => $term_slug
                )
            )
        );
        return array_merge( $common_args, $additional_args );
    }

    /**
     * Get the latest updated date for a specific taxonomy term.
     *
     * @param string $terms The taxonomy.
     * @param string $term_slug The taxonomy term slug.
     * @return string|null The latest modified date or null if no posts found.
     */
    public function latest_updated_date( $terms, $term_slug ) {
        $args = $this->tax_query_args(
            $terms,
            $term_slug,
            array(
                'posts_per_page' => 1,
                'orderby' => 'modified',
                'order' => 'DESC'
            )
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $latest_post = get_the_modified_date();
                wp_reset_postdata();
                return $latest_post;
            }
        } else {
            return null;
        }
    }

    /**
     * Check if there are any new posts within the last 7 days for a specific taxonomy term.
     *
     * @param string $terms The taxonomy.
     * @param string $term_slug The taxonomy term slug.
     * @return bool True if there are new posts, false otherwise.
     */
    public function check_new_posts( $terms, $term_slug ) {
        $date_7_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

        $args = $this->tax_query_args(
            $terms,
            $term_slug,
            array(
                'posts_per_page' => 1,
                'orderby' => 'modified',
                'order' => 'DESC',
                'date_query' => array(
                    array(
                        'after' => $date_7_days_ago,
                        'inclusive' => true
                    )
                )
            )
        );

        $query = new WP_Query( $args );

        $has_new_posts = $query->have_posts();

        wp_reset_postdata();

        return $has_new_posts;
    }

    public function insert_search_keyword( $search_input, $input_not_found ) {
        if ( empty( $search_input ) ) {
            return false;
        }

        global $wpdb;

        $keyword_hash = md5( $search_input );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- live search-keyword analytics; cache would defeat the purpose.
        // Matched on keyword_hash first so the index can serve the lookup — the
        // BINARY comparison that follows is what actually decides equality (it
        // avoids collation mismatch errors across latin1/utf8/utf8mb4), but no
        // index can serve it, so on its own it full-scanned the table on every
        // single front-end search.
        $search = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$wpdb->prefix}betterdocs_search_keyword
                WHERE keyword_hash = %s AND BINARY keyword = %s",
                $keyword_hash,
                $search_input
            )
        );

        if ( ! empty( $search ) ) {
            $search_log = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$wpdb->prefix}betterdocs_search_log
                    WHERE created_at = %s AND keyword_id = %d",
                    gmdate( 'Y-m-d' ),
                    $search[ 0 ]->id
                )
            );

            if ( ! empty( $search_log ) ) {
                if ( ! empty( $input_not_found ) ) {
                    $tbl_field = 'not_found_count';
                    $count     = $search_log[ 0 ]->not_found_count + 1;
                } else {
                    $tbl_field = 'count';
                    $count     = $search_log[ 0 ]->count + 1;
                }
                // $tbl_field is validated immediately above to be either 'count' or
                // 'not_found_count' — safe to interpolate as a column identifier.
                // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- $tbl_field is an allowlisted column name ('count'|'not_found_count'); safe to interpolate.
                $insert = $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}betterdocs_search_log SET {$tbl_field} = %d WHERE created_at = %s AND keyword_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        $count,
                        $search_log[ 0 ]->created_at,
                        $search_log[ 0 ]->keyword_id
                    )
                );
            } else {
                if ( ! empty( $input_not_found ) ) {
                    $count           = 0;
                    $not_found_count = 1;
                } else {
                    $count           = 1;
                    $not_found_count = 0;
                }
                $insert = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}betterdocs_search_log
                        ( keyword_id, count, not_found_count, created_at  )
                        VALUES ( %d, %d, %d, %s )",
                        array(
                            $search[ 0 ]->id,
                            $count,
                            $not_found_count,
                            gmdate( 'Y-m-d' )
                        )
                    )
                );
            }
        } else {
            $insert = $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}betterdocs_search_keyword
                    ( keyword, keyword_hash )
                    VALUES ( %s, %s )",
                    array(
                        $search_input,
                        $keyword_hash
                    )
                )
            );

            if ( $insert ) {
                if ( ! empty( $input_not_found ) ) {
                    $count           = 0;
                    $not_found_count = 1;
                } else {
                    $count           = 1;
                    $not_found_count = 0;
                }
                $insert = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}betterdocs_search_log
                        ( keyword_id, count, not_found_count, created_at )
                        VALUES ( %d, %d, %d, %s )",
                        array(
                            $wpdb->insert_id,
                            $count,
                            $not_found_count,
                            gmdate( 'Y-m-d' )
                        )
                    )
                );
            }
        }
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return $insert;
    }

    /**
     * Counts the number of 'doc_category' terms assigned to a specific 'knowledge_base' by slug.
     *
     * This method retrieves all terms in the 'doc_category' taxonomy and checks the term meta
     * 'doc_category_knowledge_base' to determine how many 'doc_category' terms are associated
     * with a given 'knowledge_base' slug.
     *
     * @param string $knowledge_base_slug The slug of the 'knowledge_base' to count the assignments for.
     *
     * @return int The count of 'doc_category' terms assigned to the specified 'knowledge_base'.
     */
    public function count_doc_categories_for_knowledge_base( $knowledge_base_slug ) {
        $count = 0;

        $doc_categories = get_terms(
            array(
                'taxonomy' => 'doc_category',
                'hide_empty' => true
            )
        );

        if ( ! empty( $doc_categories ) && ! is_wp_error( $doc_categories ) ) {
            foreach ( $doc_categories as $term ) {
                $meta_value = get_term_meta( $term->term_id, 'doc_category_knowledge_base', true );

                if ( $meta_value ) {
                    $knowledge_bases = maybe_unserialize( $meta_value );

                    // Check if the specific knowledge base slug is present in the meta value array
                    if ( is_array( $knowledge_bases ) && in_array( $knowledge_base_slug, $knowledge_bases ) ) {
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }
}
