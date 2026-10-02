<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Content restrictions intentionally use exclusionary query params and
// meta queries to filter docs by membership/category/KB.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key

use WP_Term;
use WPDeveloper\BetterDocs\Utils\Base;

class ContentRestrictions extends Base {
    private $settings;
    private $current_user;

    private $is_user_logged_in;

    /**
     * Re-entry guard for filter_restricted_terms_clauses(). get_restricted_categories()
     * runs its own get_terms() (the "all" case), which re-fires terms_clauses; the
     * guard makes that nested lookup return unfiltered so the restricted set is
     * computed from the full term list rather than recursing.
     * @var bool
     */
    private $terms_clauses_guard = false;

    public function __construct( Settings $settings ) {
        $this->settings          = $settings;
        $this->current_user      = wp_get_current_user();
        $this->is_user_logged_in = is_user_logged_in();

        if ( ! $this->settings->get( 'enable_content_restriction', false ) ) {
            return;
        }

        if ( $this->settings->get( 'enable_disable', false ) ) {
            add_filter( 'betterdocs_ia_query_string_array', [$this, 'ia_query_string_array'], 10, 4 );
        }

        add_filter( 'betterdocs_terms_query_args', [$this, 'exclude_terms'], 11, 1 );
        add_filter( 'betterdocs_articles_args', [$this, 'exclude_posts'], 20, 3 );
        add_filter( 'betterdocs_tag_tax_query', [$this, 'tag_template_tax_query'], 11, 1 );
        add_filter( 'betterdocs_docs_tax_query_args', [$this, 'live_search_tax_query'], 20, 5 );
        add_action( 'template_redirect', [$this, 'template_redirect'], 99 );

        // Let wp_safe_redirect() honor the admin-configured (off-site) Redirect URL
        // instead of silently rewriting it to wp-admin (only this one host is allowed).
        add_filter( 'allowed_redirect_hosts', [$this, 'allow_restricted_redirect_host'] );

        add_filter( 'advanced_search_query_params', [$this, 'filter_params'], 10, 1 );

        //Filter Search Results Based IKB
        add_filter( 'rest_docs_query', [$this, 'filter_ia_search_results'], 10, 2 );

        //Filter Doc Category Terms Based On IKB
        add_filter( 'rest_doc_category_query', [$this, 'filter_ia_doc_categories'], 10, 2 );

        //Filter Posts Which Are Attached To Restricted MKB & Doc Categories(Note: This will restrict all doc posts from anywhere using WP_Query)
        add_action( 'pre_get_posts', [$this, 'filter_posts'], 9999, 1 );

        //Docs That Are Inside 'post__not_in' Are Remove From Single Docs For Redirection To Work
        add_action( 'pre_get_posts', [$this, 'filter_posts_'], 10000, 1 );

        // Enforce restriction on the read paths the filters above miss: the REST
        // single-item routes, the docs REST collection with ?include[]=, site
        // search (filter_posts scopes to docs archives, not is_search()), the
        // sitemap and oEmbed. Mirrors the advanced-mode engine; all key on
        // get_restricted_doc_ids_for_current_user() and no-op when nothing is
        // restricted for the current viewer.
        add_filter( 'rest_request_before_callbacks', [$this, 'restrict_single_item_rest_read'], 10, 3 );
        add_filter( 'rest_docs_query', [$this, 'filter_rest_docs_collection'], 91, 2 );
        add_action( 'pre_get_posts', [$this, 'exclude_restricted_docs_from_feed_search'] );
        add_filter( 'wp_sitemaps_posts_query_args', [$this, 'exclude_restricted_docs_from_sitemap'], 10, 2 );
        add_filter( 'oembed_response_data', [$this, 'block_restricted_doc_oembed'], 10, 2 );

        /**
         * Modify Popular Docs Query (For Shortcode & Widget) When Internal Knowledgebase Is Enabled
         */
        add_filter( 'posts_clauses', [$this, 'restrict_popular_docs_query'], 11, 2 );

        // Mirror content restriction onto the taxonomy layer. The existing
        // exclude_terms()/filter_ia_doc_categories() filters set `exclude`, but
        // WP_Term_Query ignores `exclude` whenever `include` is set — so
        // /wp/v2/doc_category?include[]=<restricted_id>, the public
        // betterdocs/v1/get-terms endpoint (raw get_terms()), and the category
        // grid's doc counts all still surface restricted doc_category /
        // knowledge_base terms, leaking hidden category names and revealing that
        // hidden content exists. Filtering in terms_clauses appends a SQL
        // `t.term_id NOT IN (...)`, which core cannot discard the way it discards
        // `exclude`, so it holds even against ?include[]=.
        add_filter( 'terms_clauses', [$this, 'filter_restricted_terms_clauses'], 11, 3 );
    }

    /**
     * Exclude restricted doc_category / knowledge_base terms at the SQL level for
     * viewers who fail the content-visibility role check.
     *
     * Runs on every get_terms() for those taxonomies (front end, REST collection
     * and single-item, and the public get-terms endpoint). Privileged viewers
     * (is_visible_by_role() true) and unrelated taxonomies pass through untouched.
     *
     * @param array    $clauses
     * @param string[] $taxonomies
     * @param array    $args
     * @return array
     */
    public function filter_restricted_terms_clauses( $clauses, $taxonomies, $args ) {
        if ( $this->terms_clauses_guard ) {
            return $clauses;
        }

        $target = array_intersect( (array) $taxonomies, [ 'doc_category', 'knowledge_base' ] );
        if ( empty( $target ) || $this->is_visible_by_role() ) {
            return $clauses;
        }

        $this->terms_clauses_guard = true;

        $restricted_term_ids = [];
        foreach ( $target as $taxonomy ) {
            $is_kb = ( 'knowledge_base' === $taxonomy );
            $ids   = $this->get_restricted_categories( $taxonomy, $is_kb );
            if ( ! is_wp_error( $ids ) && ! empty( $ids ) ) {
                $restricted_term_ids = array_merge( $restricted_term_ids, array_map( 'intval', (array) $ids ) );
            }
        }

        $this->terms_clauses_guard = false;

        $restricted_term_ids = array_values( array_unique( array_filter( $restricted_term_ids ) ) );
        if ( ! empty( $restricted_term_ids ) ) {
            $clauses['where'] .= ' AND t.term_id NOT IN (' . implode( ', ', $restricted_term_ids ) . ')';
        }

        return $clauses;
    }

    public function restrict_popular_docs_query( $clauses, $wp_query ) {
        if ( ! $this->is_visible_by_role_ia( $this->current_user ) && isset( $wp_query->query['meta_key'] ) && $wp_query->query['meta_key'] == '_betterdocs_meta_views' ) {
            $restricted_doc_ids = $this->get_restricted_doc_ids();
            if( ! empty( $restricted_doc_ids ) ) {
                global $wpdb;
                $restricted_doc_ids = '( '.implode( ", ", $restricted_doc_ids ).' )';
                $clauses['where'] .= " AND {$wpdb->prefix}posts.ID NOT IN {$restricted_doc_ids}";
            }
        }
        return $clauses;
    }

    public function filter_posts( $wp_query ) {
        // Internal bookkeeping queries opt out. Content restriction answers
        // "what may this visitor read"; it must never answer "what exists".
        //
        // The API-docs materializer looks up the docs it generated previously to
        // decide create-vs-update. When a generated doc sat in a restricted
        // category this filter hid it, so the materializer concluded it was
        // missing and created a DUPLICATE on every sync — reproduced as three
        // Introduction docs for one reference. It bites hardest on the
        // background runner, which processes large specs as user 0, where
        // nothing is exempt. The term pruners have the same exposure: a
        // restricted-but-occupied term would look empty and be deleted.
        //
        // The nightly analytics jobs need the same exemption for the same reason:
        // they are neither a reader nor a page render. They run under
        // WP-Cron/Action Scheduler with no logged-in user, so the
        // non-administrator branch that loads this class is always taken, every
        // doc id lands in post__not_in, and the scanners conclude the knowledge
        // base is empty. They then PRUNE the rows they wrote on the last healthy
        // run: turning on content restriction silently deleted a site's stale and
        // health history. See AnalyticsStaleScanner::run_chunk().
        if ( $wp_query->get( 'betterdocs_bypass_restrictions' ) ) {
            return;
        }

        // Scope guard: only apply to docs-type queries. Without this, the hook
        // fires on every WP_Query (including third-party product archive queries),
        // injecting doc IDs into post__not_in and breaking unrelated filters such
        // as Filter Everything Pro on WooCommerce category pages.
        $post_type = $wp_query->get( 'post_type' );
        $is_docs   = $post_type === 'docs'
                     || ( is_array( $post_type ) && in_array( 'docs', $post_type, true ) );

        if ( ! $is_docs ) {
            // For secondary queries there is no post_type → nothing to restrict.
            // For the main query, fall back to WordPress conditional tags which are
            // reliable at pre_get_posts priority 9999 (parse_query runs first).
            if ( ! $wp_query->is_main_query()
                 || ! ( is_singular( 'docs' )
                        || is_post_type_archive( 'docs' )
                        || is_tax( 'doc_category' )
                        || is_tax( 'knowledge_base' ) ) ) {
                return;
            }
        }

        if ( ! $this->is_visible_by_role_ia( $this->current_user ) ) {
            $restricted_doc_ids = wp_parse_id_list( (array) $this->get_restricted_doc_ids() );
            if ( ! empty( $restricted_doc_ids ) ) {
                // Subtract restricted IDs from post__in as well. WP_Query gives
                // post__in precedence over post__not_in, so a REST collection
                // request with ?include[]=<id> (or the -<id> variant, which core
                // absint()s back) would otherwise bypass the post__not_in set
                // below. Normalise with absint via wp_parse_id_list() first.
                $post_in = wp_parse_id_list( (array) $wp_query->get( 'post__in' ) );
                if ( ! empty( $post_in ) ) {
                    $post_in = array_values( array_diff( $post_in, $restricted_doc_ids ) );
                    $wp_query->set( 'post__in', empty( $post_in ) ? array( 0 ) : $post_in );
                }
                $existing = wp_parse_id_list( (array) $wp_query->get( 'post__not_in' ) );
                $wp_query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $restricted_doc_ids ) ) ) );
            }
        }
    }

    public function filter_posts_( &$wp_query ) {
        // This deliberately removes restricted IDs from post__not_in for a single
        // doc so the post is fetched and template_redirect can then 404/redirect
        // it. A FEED request for that same single doc (/docs/<slug>/feed/,
        // ?feed=rss2) is is_single too, but feeds never reach template_redirect's
        // redirect — so un-excluding the post here leaks its full title and body
        // to anonymous users. Leave the exclusion intact for feeds so the item is
        // simply omitted from the feed.
        if ( $wp_query instanceof \WP_Query && $wp_query->is_feed() ) {
            return;
        }

        // Same opt-out as filter_posts() — see the note there.
        if ( $wp_query->get( 'betterdocs_bypass_restrictions' ) ) {
            return;
        }

        if ( ! $this->is_visible_by_role_ia( $this->current_user ) && isset( $wp_query->is_single ) && isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == 'docs' && $wp_query->is_single ) {
            $restricted_doc_ids = $this->get_restricted_doc_ids();
            if ( isset( $wp_query->query_vars['post__not_in'] ) && ! empty( $wp_query->query_vars['post__not_in'] ) && ! empty( $restricted_doc_ids ) ) {
                $difference_posts = array_diff( $wp_query->query_vars['post__not_in'], $restricted_doc_ids );
                $wp_query->set( 'post__not_in', $difference_posts );
            }
        }
    }

    /**
     * Restricted doc post IDs for the current user in basic content-restriction mode.
     *
     * Unlike the internal {@see self::get_restricted_doc_ids()}, this method does not apply
     * the `is_tax('knowledge_base')` short-circuit (which is specific to template_redirect flow)
     * and honours the content-visibility role check, so it is safe for REST / addon consumers
     * such as betterdocs-ai-chatbot.
     *
     * @return int[]
     */
    public function get_restricted_doc_ids_for_current_user() {
        if ( $this->is_visible_by_role_ia( $this->current_user ) ) {
            return array();
        }

        global $wpdb;

        $doc_terms = is_wp_error( $this->get_restricted_categories() ) ? array() : $this->get_restricted_categories();
        $kb_terms  = is_wp_error( $this->get_restricted_categories( 'knowledge_base', true ) ) ? array() : $this->get_restricted_categories( 'knowledge_base', true );
        $term_ids  = array_merge( $doc_terms, $kb_terms );

        if ( empty( $term_ids ) ) {
            return array();
        }

        $placeholders = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
        // Term-relationship lookup against core tables; $placeholders are %d tokens bound via $wpdb->prepare().
        //
        // Joined through term_taxonomy rather than matching term_ids straight
        // against term_relationships.term_taxonomy_id. Those two ids are equal
        // on most installs and the old query worked by coincidence, but they
        // are independent sequences — anywhere they have drifted (a site that
        // has had terms split, or content imported), the wrong rows come back.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $results      = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT tr.object_id
               FROM {$wpdb->term_relationships} tr
         INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
              WHERE tt.term_id IN ( $placeholders )",
            $term_ids
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        return array_map( 'intval', (array) $results );
    }

    /**
     * Exclude restricted post IDs from WP_Query args, robustly against ?include[]=.
     *
     * WP_Query resolves targeting as `if p / elseif post__in / elseif
     * post__not_in`, so post__not_in is dropped when post__in (REST include[]) is
     * present. Subtract restricted IDs from post__in as well.
     *
     * @param array $args
     * @param int[] $restricted
     * @return array
     */
    private function apply_restricted_post_ids( $args, $restricted ) {
        $restricted = wp_parse_id_list( (array) $restricted );
        if ( empty( $restricted ) ) {
            return $args;
        }

        // A single-post request (p / attachment_id) takes precedence over
        // post__in/post__not_in in WP_Query, so a restricted one would bypass the
        // exclusion — neutralise it.
        if ( ! empty( $args['p'] ) && in_array( absint( $args['p'] ), $restricted, true ) ) {
            $args['p']        = 0;
            $args['post__in'] = [ 0 ];
        }

        if ( ! empty( $args['post__in'] ) ) {
            // Normalise with absint FIRST. WP_Query absint()s post__in when it
            // builds the query, so a negative id (e.g. -115) would otherwise
            // survive this array_diff() and be re-emitted by core as ID IN (115),
            // leaking the restricted doc — the ?include[]=-<id> bypass.
            $args['post__in'] = array_values( array_diff( wp_parse_id_list( (array) $args['post__in'] ), $restricted ) );
            if ( empty( $args['post__in'] ) ) {
                $args['post__in'] = [ 0 ];
            }
        }

        $existing            = isset( $args['post__not_in'] ) ? wp_parse_id_list( (array) $args['post__not_in'] ) : [];
        $args['post__not_in'] = array_values( array_unique( array_merge( $existing, $restricted ) ) );

        return $args;
    }

    /**
     * Exclude restricted docs from the docs REST collection.
     *
     * filter_ia_search_results() only filters when a search keyword is present,
     * so a plain /wp/v2/docs (and /wp/v2/docs?include[]=<id>) otherwise returns
     * restricted docs. This filter always applies the restricted-ID exclusion.
     *
     * @param array $args
     * @param \WP_REST_Request $request
     * @return array
     */
    public function filter_rest_docs_collection( $args, $request ) {
        return $this->apply_restricted_post_ids( $args, $this->get_restricted_doc_ids_for_current_user() );
    }

    /**
     * Enforce restriction on the REST single-item read routes (docs + terms).
     *
     * The collection filters do not fire for get_item(), so a restricted doc or
     * category is otherwise readable at /wp/v2/docs/{id} etc. Runs on
     * rest_request_before_callbacks so a returned WP_Error is a clean error
     * response (rest_prepare_* would fatal in get_item()'s link_header()).
     *
     * @param \WP_REST_Response|\WP_Error|null $response
     * @param array $handler
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error|null
     */
    public function restrict_single_item_rest_read( $response, $handler, $request ) {
        if ( is_wp_error( $response ) || null !== $response ) {
            return $response;
        }
        if ( 'GET' !== $request->get_method() || 'edit' === $request->get_param( 'context' ) ) {
            return $response;
        }

        $route = (string) $request->get_route();

        if ( preg_match( '#^/wp/v2/docs/(\d+)$#', $route, $m ) ) {
            $restricted = array_map( 'intval', (array) $this->get_restricted_doc_ids_for_current_user() );
            if ( in_array( (int) $m[1], $restricted, true ) ) {
                return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'betterdocs-pro' ), [ 'status' => 404 ] );
            }
            return $response;
        }

        if ( preg_match( '#^/wp/v2/(doc_category|knowledge_base)/(\d+)$#', $route, $m ) ) {
            $is_kb      = 'knowledge_base' === $m[1];
            $restricted = is_wp_error( $this->get_restricted_categories( $m[1], $is_kb ) ) ? [] : $this->get_restricted_categories( $m[1], $is_kb );
            if ( in_array( (int) $m[2], array_map( 'intval', (array) $restricted ), true ) ) {
                return new \WP_Error( 'rest_term_invalid', __( 'Term does not exist.', 'betterdocs-pro' ), [ 'status' => 404 ] );
            }
        }

        return $response;
    }

    /**
     * Exclude restricted docs from the main feed and site-search queries.
     * filter_posts() scopes to docs archives and single docs, missing is_search()
     * (which spans post types) and feeds.
     *
     * @param \WP_Query $query
     * @return void
     */
    public function exclude_restricted_docs_from_feed_search( $query ) {
        if ( is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
            return;
        }
        if ( ! $query->is_feed() && ! $query->is_search() ) {
            return;
        }

        $restricted = array_map( 'intval', (array) $this->get_restricted_doc_ids_for_current_user() );
        if ( empty( $restricted ) ) {
            return;
        }

        $updated = $this->apply_restricted_post_ids(
            [ 'post__in' => (array) $query->get( 'post__in' ), 'post__not_in' => (array) $query->get( 'post__not_in' ) ],
            $restricted
        );
        $query->set( 'post__not_in', $updated['post__not_in'] );
        if ( ! empty( $query->get( 'post__in' ) ) ) {
            $query->set( 'post__in', $updated['post__in'] );
        }
    }

    /**
     * Exclude restricted docs from the core docs sitemap.
     *
     * @param array $args
     * @param string $post_type
     * @return array
     */
    public function exclude_restricted_docs_from_sitemap( $args, $post_type ) {
        if ( 'docs' !== $post_type ) {
            return $args;
        }
        return $this->apply_restricted_post_ids( $args, $this->get_restricted_doc_ids_for_current_user() );
    }

    /**
     * Block oEmbed output for a restricted doc.
     *
     * @param array $data
     * @param \WP_Post $post
     * @return array
     */
    public function block_restricted_doc_oembed( $data, $post ) {
        if ( ! ( $post instanceof \WP_Post ) || 'docs' !== $post->post_type ) {
            return $data;
        }
        $restricted = array_map( 'intval', (array) $this->get_restricted_doc_ids_for_current_user() );
        if ( in_array( (int) $post->ID, $restricted, true ) ) {
            return [];
        }
        return $data;
    }

    private function get_restricted_doc_ids() {
        global $wpdb;

        $restricted_doc_terms = is_wp_error( $this->get_restricted_categories() ) || is_tax( 'knowledge_base' ) ? [] : $this->get_restricted_categories(); // inside knowledge_base page do not include doc terms, the logic below will work for knowledge_base page which will include all the doc terms
        $restricted_kb_terms  = is_wp_error( $this->get_restricted_categories( 'knowledge_base', true ) ) ? [] : $this->get_restricted_categories( 'knowledge_base', true );
        $merged_term_ids      = array_merge( $restricted_doc_terms, $restricted_kb_terms );

        if ( ! empty( $merged_term_ids ) ) {
            $IDS_IN = array_reduce( $merged_term_ids, function ( $accumulator ) {
                static $index = 0;
                if ( $index != 0 ) {
                    $accumulator .= ', ';
                }
                $index++;
                return $accumulator . '%d';
            }, "IN ( " );

            $IDS_IN .= ' )';

            // $IDS_IN is composed from %d placeholders only (built up by array_reduce above) and is bound via $wpdb->prepare with $merged_term_ids.
            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT object_id FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id $IDS_IN", $merged_term_ids ), ARRAY_A );
            // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $results = array_column( $results, 'object_id' );
            return $results;
        }

        return [];
    }

    public function filter_params( $_args ) {
        if ( ! $this->is_visible_by_role_ia( $this->current_user ) ) {
            $restricted_categories = $this->get_restricted_categories();
            $_restricted_kb_terms  = $this->get_restricted_categories( 'knowledge_base', true );

            if ( ! empty( $_restricted_kb_terms ) ) {
                foreach ( $_restricted_kb_terms as $kb_term_id ) {
                    $query = [
                        'taxonomy'   => 'doc_category',
                        'fields'     => 'ids',
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key'     => 'doc_category_knowledge_base',
                                'value'   => get_term_field( 'slug', $kb_term_id, 'knowledge_base' ),
                                'compare' => 'LIKE'
                            ]
                        ]
                    ];
                    $term_ids = get_terms( $query );
                    if ( ! empty( $term_ids ) ) {
                        foreach ( $term_ids as $term_id ) {
                            if ( ! in_array( $term_id, $restricted_categories ) ) {
                                array_push( $restricted_categories, $term_id );
                            }
                        }
                    }
                }
            }

            if ( ! empty( $restricted_categories ) ) {
                $_args['exclude'] = $restricted_categories;
            }
        }

        return $_args;
    }

    public function filter_ia_search_results( $query_args, $request ) {
        if ( ! $this->is_visible_by_role_ia( $this->current_user ) ) {
            $restricted_categories = $this->get_restricted_categories();
            $_restricted_kb_terms  = $this->get_restricted_categories( 'knowledge_base', true );
            $search_keyword        = isset( $query_args['s'] ) ? $query_args['s'] : '';
            if ( strlen( $search_keyword ) > 0 && count( $restricted_categories ) > 0 ) {
                $query_args['tax_query'][] = [
                    'taxonomy'         => 'doc_category',
                    'field'            => 'term_id',
                    'operator'         => 'NOT IN',
                    'terms'            => $restricted_categories,
                    'include_children' => true
                ];
            }
            if ( $this->settings->get( 'multiple_kb', false ) && count( $_restricted_kb_terms ) > 0 && strlen( $search_keyword ) > 0 ) {
                $query_args['tax_query'][] = [
                    'taxonomy'         => 'knowledge_base',
                    'field'            => 'term_id',
                    'terms'            => $_restricted_kb_terms,
                    'operator'         => 'NOT IN',
                    'include_children' => true
                ];
            }
            return $query_args;
        }
        return $query_args;
    }

    public function filter_ia_doc_categories( $query_args, $request ) {
        if ( ! $this->is_visible_by_role_ia( $this->current_user ) ) {
            $restricted_categories = is_wp_error( $this->get_restricted_categories() ) ? [] : $this->get_restricted_categories();
            $_restricted_kb_terms  = is_wp_error( $this->get_restricted_categories( 'knowledge_base', true ) ) ? [] : $this->get_restricted_categories( 'knowledge_base', true );
            if ( $this->settings->get( 'multiple_kb', false ) && count( $_restricted_kb_terms ) > 0 ) {
                $merged_ids = [];
                foreach ( $_restricted_kb_terms as $term_id ) {
                    $query = [
                        'taxonomy'   => 'doc_category',
                        'fields'     => 'ids',
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key'     => 'doc_category_knowledge_base',
                                'value'   => get_term_field( 'slug', $term_id, 'knowledge_base' ),
                                'compare' => 'LIKE'
                            ]
                        ]
                    ];
                    $term_ids   = get_terms( $query );
                    $merged_ids = array_merge( $term_ids, $merged_ids );
                    $merged_ids = array_merge( $merged_ids, $restricted_categories );
                }
                $query_args['exclude'] = $merged_ids;
            } else if ( count( $restricted_categories ) > 0 ) {
                $query_args['exclude'] = $restricted_categories;
            }
        }
        return $query_args;
    }

    public function template_redirect() {
        if ( ! $this->is_visible_by_role() && ( is_post_type_archive( 'docs' ) || is_singular( 'docs' ) || is_tax( 'knowledge_base' ) || is_tax( 'doc_category' ) ) ) {
            global $wp_query;
            $_is_restricted          = false;
            $_current_queried_object = get_queried_object();
            $_restricted_docs_page   = $this->settings->get( 'restrict_template', ['all'] );
            $_taxonomy               = $_current_queried_object instanceof WP_Term ? $_current_queried_object->taxonomy : null;
            $_settings_key           = is_tax( 'knowledge_base' ) ? 'restrict_kb' : 'restrict_category';
            $_restricted_terms       = (array) $this->settings->get( $_settings_key, ['all'] );

            $_docs_terms = $_docs_kbs = [];

            if ( is_singular( 'docs' ) ) {
                $_docs_terms = get_the_terms( get_the_ID(), 'doc_category' );
                $_docs_kbs   = get_the_terms( get_the_ID(), 'knowledge_base' );

                $_docs_terms = ! is_array( $_docs_terms ) ? [] : $_docs_terms;
                $_docs_kbs   = ! is_array( $_docs_kbs ) ? [] : $_docs_kbs;

                // The doc's own terms only, matched against the configured list
                // below. Ancestors are deliberately not consulted: restricting a
                // parent category does not restrict its children, so a doc in
                // "Sample > Docs" is governed by `docs` being listed, not by
                // `sample` being listed.
                $_docs_terms = array_map( function ( $term ) {return $term->slug;}, $_docs_terms );
                $_docs_kbs = array_map( function ( $term ) {return $term->slug;}, $_docs_kbs );
            }

            switch ( true ) {
                case in_array( 'all', $_restricted_docs_page ):
                case in_array( 'docs', $_restricted_docs_page ):
                    $_is_restricted = true;
                    break;
                case $_taxonomy != null && is_tax( $_taxonomy ) && in_array( $_taxonomy, $_restricted_docs_page ):
                    if ( in_array( 'all', $_restricted_terms ) || in_array( $wp_query->query[$_taxonomy], $_restricted_terms ) ) {
                        $_is_restricted = true;
                    }
                    break;
                case is_singular( 'docs' ) && ( in_array( 'doc_category', $_restricted_docs_page ) || in_array( 'knowledge_base', $_restricted_docs_page ) ):
                    $_is_doc_terms = in_array( 'doc_category', $_restricted_docs_page );
                    if ( $_is_doc_terms ) {
                        $_restricted_terms = (array) $this->settings->get( 'restrict_category', ['all'] );
                        if ( in_array( 'all', $_restricted_terms ) || array_intersect( $_docs_terms, $_restricted_terms ) ) {
                            $_is_restricted = true;
                        }
                    }

                    $_is_kb_terms = in_array( 'knowledge_base', $_restricted_docs_page );
                    if ( $_is_kb_terms ) {
                        $_restricted_terms = (array) $this->settings->get( 'restrict_kb', ['all'] );
                        if ( in_array( 'all', $_restricted_terms ) || array_intersect( $_docs_kbs, $_restricted_terms ) ) {
                            $_is_restricted = true;
                        }
                    }
                    break;
                // case is_singular( 'docs' ) && in_array( 'doc_category', $_restricted_docs_page ):
                //     $_restricted_terms = (array) $this->settings->get( 'restrict_category', ['all'] );
                //     if ( in_array( 'all', $_restricted_terms ) || array_intersect( $_docs_terms, $_restricted_terms ) ) {
                //         $_is_restricted = true;
                //     }
                //     break;
                // case is_singular( 'docs' ) && in_array( 'knowledge_base', $_restricted_docs_page ):
                //     $_restricted_terms = (array) $this->settings->get( 'restrict_kb', ['all'] );
                //     if ( in_array( 'all', $_restricted_terms ) || array_intersect( $_docs_kbs, $_restricted_terms ) ) {
                //         $_is_restricted = true;
                //     }
                //     break;
                default:
                    $_is_restricted = false;
                    break;
            }

            if ( $_is_restricted ) {
                $this->redirect_restricted_users();
            }
        }
    }

    public function redirect_restricted_users() {
        $restricted_redirect_url = $this->settings->get( 'restricted_redirect_url', '' );
        if ( $restricted_redirect_url ) {
            wp_safe_redirect( $restricted_redirect_url );
            exit;
        } else {
            global $wp_query;
            $wp_query->set_404();

            /**
             * set_404() only flips the query flag so the theme renders its 404
             * template — the HTTP status stays 200 unless we say otherwise. That
             * soft-404 is the worst of both worlds: the visitor is denied, but
             * page caches, CDNs and search engines all treat a 200 as a real,
             * cacheable, indexable page for the restricted URL. Send a true 404
             * and defeat caching explicitly.
             *
             * get_template_part()/exit() stay out: template_redirect runs before
             * template selection, so WordPress picks the 404 template itself.
             */
            status_header( 404 );
            nocache_headers();
        }
    }

    /**
     * Whitelist the admin-configured Redirect URL host so wp_safe_redirect() honors
     * an off-site target instead of rewriting it to wp-admin. Only the single
     * configured host is allowed; other hosts stay blocked.
     *
     * @param string[] $hosts Allowed redirect hosts.
     * @return string[]
     */
    public function allow_restricted_redirect_host( $hosts ) {
        $url  = $this->settings->get( 'restricted_redirect_url', '' );
        $host = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
        if ( $host && ! in_array( $host, (array) $hosts, true ) ) {
            $hosts[] = $host;
        }
        return $hosts;
    }

    public function exclude_terms( $query_args ) {
        $_taxonomy             = $query_args['taxonomy'];
        $_taxonomy             = empty( $_taxonomy ) ? 'doc_category' : $_taxonomy;
        $_is_kb                = $_taxonomy === 'knowledge_base' ? true : false;
        $_restricted_docs_page = (array) $this->settings->get( 'restrict_template', ['all'] ); // $restrict_template
        $_restricted_kb        = $this->settings->get( 'restrict_kb', ['all'] );

        $_restricted_terms = $this->get_restricted_categories( $_taxonomy, $_is_kb );
        if ( ! $this->is_visible_by_role() && ! empty( $_restricted_terms ) ) {
            $query_args['exclude'] = $_restricted_terms;
        } elseif ( ! $this->is_visible_by_role() && in_array( 'knowledge_base', $_restricted_docs_page ) && ! empty( $_restricted_kb ) && is_tax( 'doc_category' ) ) { //exclude terms when knowledge_base template & kb's are selected from advanced ikb settings
            global $wp_query;
            $current_mkb           = isset( $wp_query->query['knowledge_base'] ) ? $wp_query->query['knowledge_base'] : '';
            $query_args['exclude'] = in_array( $current_mkb, $_restricted_kb ) ? array_merge( $this->fetch_categories_based_on_kb( $_restricted_kb ), [get_queried_object_id()] ) : ( in_array( get_queried_object_id(), $this->fetch_categories_based_on_kb( $_restricted_kb ) ) ? array_diff( $this->fetch_categories_based_on_kb( $_restricted_kb ), [get_queried_object_id()] ) : $this->fetch_categories_based_on_kb( $_restricted_kb ) ); // if a category is assigned to 2 mkb or more, remove the current category from terms based the kb restriction on doc category page
        }

        return $query_args;
    }

    //exclude posts when knowledge_base template & kb's are selected from advanced ikb settings
    public function exclude_posts( $post_args ) {
        $_restricted_docs_page = (array) $this->settings->get( 'restrict_template', ['all'] ); // $restrict_template
        $_restricted_kb        = $this->settings->get( 'restrict_kb', ['all'] );
        if ( ! $this->is_visible_by_role() && in_array( 'knowledge_base', $_restricted_docs_page ) && ! empty( $_restricted_kb ) && is_tax( 'doc_category' ) ) {
            global $wp_query;
            $current_mkb              = isset( $wp_query->query['knowledge_base'] ) ? $wp_query->query['knowledge_base'] : '';
            $post_args['tax_query'][] = [
                'taxonomy'         => 'doc_category',
                'field'            => 'term_id',
                'terms'            => in_array( $current_mkb, $_restricted_kb ) ? array_merge( $this->fetch_categories_based_on_kb( $_restricted_kb ), [get_queried_object_id()] ) : ( in_array( get_queried_object_id(), $this->fetch_categories_based_on_kb( $_restricted_kb ) ) ? array_diff( $this->fetch_categories_based_on_kb( $_restricted_kb ), [get_queried_object_id()] ) : $this->fetch_categories_based_on_kb( $_restricted_kb ) ),
                'include_children' => true,
                'operator'         => 'NOT IN'
            ];
        }
        return $post_args;
    }

    public function fetch_categories_based_on_kb( $restricted_kb ) {
        if ( in_array( 'all', $restricted_kb ) ) {
            $kb_terms_args = [
                'taxonomy'   => 'doc_category',
                'hide_empty' => true,
                'fields'     => 'ids'
            ];
            $kb_terms = get_terms( $kb_terms_args );
            return $kb_terms;
        } else {
            $kb_terms_args = [
                'taxonomy'   => 'doc_category',
                'hide_empty' => true,
                'meta_query' => [
                    'relation' => 'OR'
                ],
                'fields'     => 'ids'
            ];

            foreach ( $restricted_kb as $term ) {
                $kb_terms_args['meta_query'][] = [
                    'key'     => 'doc_category_knowledge_base',
                    'value'   => $term,
                    'compare' => 'LIKE'
                ];
            }

            $kb_terms = get_terms( $kb_terms_args );
            return $kb_terms;
        }
    }

    /**
     * This
     *
     * @param mixed $tax_query
     * @return mixed
     */
    public function tag_template_tax_query( $tax_query ) {
        if ( ! $this->is_visible_by_role() ) {
            $_restricted_terms = $this->get_restricted_categories();
            $_terms_query      = [];
            if ( ! empty( $_restricted_terms ) ) {
                $_terms_query = [
                    'taxonomy'         => 'doc_category',
                    'field'            => 'term_id',
                    'operator'         => 'NOT IN',
                    'terms'            => $_restricted_terms,
                    'include_children' => true
                ];
            }

            $_restricted_kb_terms = $this->get_restricted_categories( 'knowledge_base', true );
            if ( $this->settings->get( 'multiple_kb', false ) && ! empty( $_restricted_kb_terms ) ) {
                $_terms_query = [
                    'taxonomy'         => 'knowledge_base',
                    'field'            => 'term_id',
                    'terms'            => $_restricted_kb_terms,
                    'operator'         => 'NOT IN',
                    'include_children' => true
                ];
            }

            if ( ! empty( $_terms_query ) ) {
                $tax_query[] = $_terms_query;
            }

            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }
        }
        return $tax_query;
    }

    /**
     * This method is responsible for Live Search Restriction Query Modification.
     *
     * @since 2.5.0
     *
     * @param array $tax_query
     * @param bool $_multiple_kb
     * @param string $_term_slug
     * @param string $_kb_slug
     * @param array $_origin_args
     *
     * @return array
     */
    public function live_search_tax_query( $tax_query, $_multiple_kb, $_term_slug, $_kb_slug, $_origin_args ) {
        if ( ! $this->is_visible_by_role() && isset( $_origin_args['s'] ) ) {
            $tax_query = $this->tag_template_tax_query( $tax_query );
        }

        return $tax_query;
    }

    public function is_visible_by_role() {
        global $current_user;

        if ( ! is_user_logged_in() ) {
            return false;
        }

        $content_visibility = $this->settings->get( 'content_visibility', ['all'] );
        if ( in_array( 'all', $content_visibility, true ) ) {
            return true;
        }

        // If The User Has Multiple Roles Assigned
        $roles         = $current_user->roles;
        $_user_can_see = count( array_intersect( $roles, $content_visibility ) ) >= 1;

        return $_user_can_see;
    }

    public function is_visible_by_role_ia( $user ) {
        $current_user = $user;

        if ( ! $this->is_user_logged_in ) {
            return false;
        }

        $content_visibility = $this->settings->get( 'content_visibility', ['all'] );
        if ( in_array( 'all', $content_visibility, true ) ) {
            return true;
        }

        // If The User Has Multiple Roles Assigned
        $roles         = $current_user->roles;
        $_user_can_see = count( array_intersect( $roles, $content_visibility ) ) >= 1;

        return $_user_can_see;
    }

    public function get_restricted_categories( $taxonomy = 'doc_category', $is_kb = false ) {
        $_restricted_docs_page       = (array) $this->settings->get( 'restrict_template', ['all'] ); // $restrict_template
        $_restricted_docs_categories = (array) $this->settings->get( 'restrict_category', ['all'] ); // $restrict_category

        $_term_ids  = [];
        $_restriced = ( in_array( 'all', $_restricted_docs_page ) || in_array( $taxonomy, $_restricted_docs_page ) );

        if ( $is_kb ) {
            $_restricted_docs_categories = (array) $this->settings->get( 'restrict_kb', ['all'] ); // $restrict_kb
            $_restriced                  = $_restriced && $this->settings->get( 'multiple_kb', false );
        }

        if ( $_restriced && in_array( 'all', $_restricted_docs_categories ) ) {
            $_term_ids = get_terms( [
                'taxonomy'        => $taxonomy,
                'fields'          => 'ids',
                'suppress_filter' => true
            ] );
        } elseif ( $_restriced && ! in_array( 'all', $_restricted_docs_categories ) ) {
            foreach ( $_restricted_docs_categories as $category ) {
                $term = get_term_by( 'slug', $category, $taxonomy );
                // Listed terms only — a restricted parent does NOT pull in its
                // children. Cascading would silently re-restrict child
                // categories an existing site had deliberately left open, and
                // the setting is an explicit list: to restrict a child, add the
                // child. (Note for API references: the materializer files docs
                // under "<Reference> > <Tag>", so restricting a whole reference
                // means listing each tag term, and a tag added to the spec later
                // creates a new term that is open until it is listed too.)
                if ( $term != false ) {
                    $_term_ids[] = $term->term_id;
                }
            }
        }

        return $_term_ids;
    }

    /**
     * This method is responsible for Instant Answer Query String generation.
     *
     * @param mixed $query_strings_array
     * @param mixed $content_type
     * @param mixed $is_search
     * @param mixed $content_list
     * @return array
     */
    public function ia_query_string_array( $query_strings_array, $content_type, $is_search, $content_list ) {
        if ( ! $this->is_visible_by_role() ) {
            $_restricted_categories = $this->get_restricted_categories();
            switch ( $content_type ) {
                case 'docs':
                    if ( ! empty( $_restricted_categories ) ) {
                        $query_strings_array['doc_category_exclude'] = implode( ',', $_restricted_categories );
                    }
                    if ( ! empty( $_restricted_kbs = $this->get_restricted_categories( 'knowledge_base', true ) ) ) {
                        $query_strings_array['knowledge_base_exclude'] = implode( ',', $_restricted_kbs );
                    }
                    break;
                case 'docs_categories':
                    if ( empty( $content_list ) && ! empty( $_restricted_categories ) ) {
                        $_term_ids = get_terms( [
                            'taxonomy' => 'doc_category',
                            'fields'   => 'ids'
                        ] );

                        $query_strings_array[$is_search ? 'doc_category' : 'include'] = implode( ',', array_diff( $_term_ids, $_restricted_categories ) );
                    }
                    break;
            }
        }

        return $query_strings_array;
    }
}
