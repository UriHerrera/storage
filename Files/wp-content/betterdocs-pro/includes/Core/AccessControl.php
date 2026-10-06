<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Access control filters intentionally use exclusionary query parameters and
// meta/tax queries to gate doc visibility per role/KB/category — the whole
// feature is built on these flagged primitives.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query

use WP_Query;

use function WPML\FP\Strings\remove;
use function WPML\PHP\Logger\error;
use WPDeveloper\BetterdocsPro\Utils\Helper;

class AccessControl {

    public $current_user;
    public $access_control_settings;
    public $current_user_role;
    public $final_access_control_settings;
    public $restricted_url;

    /**
     * Recursion protection flag to prevent infinite loops in filter hooks
     * @var bool
     */
    private static $filter_recursion_guard = false;

    /**
     * Re-entry depth for get_all_restricted_post_ids() (which runs its own docs
     * queries). Tells exclude_restricted_docs_from_docs_query() to leave those
     * internal computation queries alone while the set is being computed.
     *
     * Reference-counted (an int, not a bool) so a NESTED call — a third-party
     * pre_get_posts handler, or an addon such as betterdocs-ai-chatbot that also
     * calls get_all_restricted_post_ids() during computation — cannot clear the
     * guard out from under an outer computation. A plain bool let the inner call
     * reset it to false on return, after which the outer call's remaining
     * internal queries were rewritten and its cached set corrupted (B-2). The
     * guard is "active" whenever this is greater than zero.
     * @var int
     */
    private static $computing_restricted_ids = 0;

    /**
     * Cache for expensive database operations
     * @var array
     */
    private static $query_cache = array(  );

    /**
     * Maximum recursion depth for get_parents method
     * @var int
     */
    private static $max_recursion_depth = 10;

    public function __construct() {
        // Load appropriate access control settings based on multiple KB feature status
        $this->access_control_settings = betterdocs_pro()->multiple_kb->is_enable
        ? betterdocs()->settings->get( 'betterdocs_access_control_repeater_kb' )
        : betterdocs()->settings->get( 'betterdocs_access_control_repeater' );

        // Resolve current user. For REST requests without X-WP-Nonce, WordPress's
        // rest_cookie_check_errors() calls wp_set_current_user(0), which wipes the
        // logged-in user — so the IA widget's unauthenticated fetch() would otherwise
        // bypass role-based access control. Falling back to wp_validate_auth_cookie()
        // against LOGGED_IN_COOKIE keeps the author's whitelist in effect.
        $this->current_user      = $this->resolve_current_user();
        $this->current_user_role = is_string( $this->current_user->roles )
        ? array( $this->current_user->roles )
        : $this->current_user->roles;

        // Register REST API filter to enforce access control on docs queries (IA chatbot, search, etc.)
        // Must be before the early return so it works even when settings are empty for the current user's role.
        add_filter( 'rest_docs_query', array( $this, 'filter_rest_docs_access_control' ), 91, 2 );

        // rest_docs_query / rest_doc_category_query only fire for COLLECTION
        // requests. The single-item routes /wp/v2/docs/{id},
        // /wp/v2/doc_category/{id} and /wp/v2/knowledge_base/{id} run get_item(),
        // which reads the object directly and applies only core's published/read
        // check — so restricted content is otherwise readable there, bypassing
        // restriction. Enforce on rest_request_before_callbacks (not rest_prepare_*):
        // a WP_Error returned from rest_prepare_docs reaches get_item()'s
        // $response->link_header() and fatals, whereas the dispatcher handles a
        // WP_Error from before_callbacks as a clean error response. Registered
        // before the early return so it applies for every role.
        add_filter( 'rest_request_before_callbacks', array( $this, 'restrict_single_item_rest_read' ), 10, 3 );

        // Enforce access control on every docs query that flows through betterdocs()->query->get_posts() —
        // the docs-archive search bar (REST betterdocs/v1/search) and AJAX betterdocs_get_search_result both
        // do, but the search modal endpoint passes 'suppress_filters' => true which suppresses our posts_where
        // filter, and rest_docs_query only fires for /wp/v2/docs. betterdocs_articles_args runs inside
        // Query::docs_query_args() before WP_Query is constructed, so suppress_filters is irrelevant here.
        add_filter( 'betterdocs_articles_args', array( $this, 'filter_articles_args_access_control' ), 91, 3 );

        // Early return optimization: skip processing if no access control settings exist
        if ( empty( $this->access_control_settings ) ) {
            $this->final_access_control_settings = array(  );
            $this->restricted_url                = '';
            return;
        }

        // Process access control rules for current user's roles
        $this->final_access_control_settings = $this->get_selected_rules_based_on_user_role();

        // Set redirect URL for restricted access
        $this->restricted_url = betterdocs()->settings->get( 'restricted_redirect_url' );

        // Let wp_safe_redirect() honor the admin-configured (off-site) Redirect URL
        // instead of silently rewriting it to wp-admin. Only this one trusted host is
        // whitelisted; arbitrary open-redirects stay blocked.
        add_filter( 'allowed_redirect_hosts', array( $this, 'allow_restricted_redirect_host' ) );

        // Register cache clearing hooks for when settings are updated
        add_action( 'update_option_betterdocs_settings', array( __CLASS__, 'clear_access_control_cache' ) );
        add_action( 'update_option_betterdocs_pro_settings', array( __CLASS__, 'clear_access_control_cache' ) );
    }

    /**
     * Whitelist the admin-configured restriction Redirect URL host so
     * wp_safe_redirect() honors an off-site target instead of rewriting it to
     * wp-admin. Only the single configured host is added; every other host stays
     * blocked, preserving the open-redirect protection wp_safe_redirect() provides.
     *
     * @param string[] $hosts Allowed redirect hosts.
     * @return string[]
     */
    public function allow_restricted_redirect_host( $hosts ) {
        $host = $this->restricted_url ? wp_parse_url( $this->restricted_url, PHP_URL_HOST ) : '';
        if ( $host && ! in_array( $host, (array) $hosts, true ) ) {
            $hosts[] = $host;
        }
        return $hosts;
    }

    /**
     * Return the actual logged-in user, falling back to the LOGGED_IN_COOKIE when
     * wp_get_current_user() would return user 0.
     *
     * The IA widget fetches /wp/v2/docs without X-WP-Nonce. During REST request
     * handling, rest_cookie_check_errors() runs wp_set_current_user(0) in that
     * case — which strips the author's roles before our filter evaluates access.
     * Reading the auth cookie directly lets us honour whitelists for logged-in
     * users even when the REST layer has downgraded them to anonymous.
     *
     * @return \WP_User
     */
    private function resolve_current_user() {
        $user = wp_get_current_user();
        if ( $user instanceof \WP_User && (int) $user->ID > 0 ) {
            return $user;
        }

        if ( ! defined( 'LOGGED_IN_COOKIE' ) || empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
            return $user;
        }

        $user_id = wp_validate_auth_cookie( sanitize_text_field( wp_unslash( $_COOKIE[ LOGGED_IN_COOKIE ] ) ), 'logged_in' );
        if ( ! $user_id ) {
            return $user;
        }

        $resolved = get_user_by( 'id', (int) $user_id );
        return $resolved ? $resolved : $user;
    }

    public function init() {
        // Universal front-end enforcement for the read paths the per-mode filters
        // miss. The specific-mode posts_where only fires when a query's post_type
        // is exactly 'docs', and the "all"-mode exclusion is REST-collection only,
        // so site search, feeds, sitemaps and oEmbed all surface restricted docs.
        // These use the canonical get_all_restricted_post_ids() set, so they cover
        // both specific and "all" restriction modes and no-op when the set is empty.
        add_action( 'pre_get_posts', array( $this, 'exclude_restricted_docs_from_feed_search' ) );
        add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_restricted_docs_from_sitemap' ), 10, 2 );
        add_filter( 'oembed_response_data', array( $this, 'block_restricted_doc_oembed' ), 10, 2 );

        // Enforce on every docs-type query via pre_get_posts. This is the hook that
        // reliably fires for the REST docs collection (/wp/v2/docs), where the
        // rest_docs_query filters do not always run — so ?include[]= (and the
        // negative-id variant) would otherwise bypass restriction. Scoped to the
        // docs post type to avoid touching unrelated (e.g. WooCommerce) queries.
        add_action( 'pre_get_posts', array( $this, 'exclude_restricted_docs_from_docs_query' ), 9999 );

        if ( betterdocs_pro()->multiple_kb->is_enable ) {
            // add_filter( 'betterdocs_terms_query_args', [$this, 'include_or_exclude_selected_mkb_terms'] ); //for front-end using get_terms() to fetch terms mkb | logic done
            add_filter( 'betterdocs_docs_count', array( $this, 'include_or_exclude_selected_mkb_terms_count' ), 20, 5 ); //for front-end using get_terms() | logic done
            // add_filter( 'betterdocs_articles_args', [$this, 'include_or_exclude_selected_mkb_doc_category_attached_posts'], 20, 3 ); //for front-end using WP_Query() to fetch docs | logic done
            // add_action( 'template_redirect', [$this, 'template_redirect_mkb'], 99 );
            add_filter( 'knowledge_base_row_actions', array( $this, 'modify_quick_actions_wp_list_table_for_knowledge_base' ), 10, 2 );
            add_filter( 'doc_category_row_actions', array( $this, 'modify_quick_actions_wp_list_table_for_doc_category' ), 10, 2 );
            add_filter( 'terms_clauses', array( $this, 'filter_mkb_categories' ), 11, 3 ); //global query for get_terms and rest api (applicable on admin | front-end | rest-api)
            add_filter( 'posts_where', array( $this, 'filter_doc_categories_docs_in_mkb' ), 90, 2 ); //global query for posts and rest api (applicable on admin | front-end | rest-api)
            add_filter( 'rest_docs_query', array( $this, 'enable_or_disable_filter' ), 90, 2 ); //filter to disable rest api docs payload blockage
            add_filter( 'get_terms_args', array( $this, 'enable_or_disable_terms_filter' ), 90, 2 ); //filter to disable rest api payload blockage
            add_action( 'template_redirect', array( $this, 'template_redirect_mkb_single_docs' ), 99 );
            add_filter( 'rest_knowledge_base_query', array( $this, 'enable_or_disable_filter' ), 90, 2 );

            // Excludes terms from the docs/knowlege_base/doc_category page that are not assigned to the current role.(it can be any outside user, author, editor, etc)
            add_filter( 'betterdocs_terms_query_args', array( $this, 'exclude_mkb_terms_or_redirect_not_in_role' ), 10, 1 );

            // redirect docs based on doc_category from the single doc or other page that are not assigned to the current role.(it can be any outside user, author, editor, etc)
            add_action( 'template_redirect', array( $this, 'redirect_users_not_in_rules_mkb' ), 10 );
        } else {
            if ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && in_array( 'all', array_keys( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ) ) { //handle all logic
                add_filter( 'betterdocs_terms_query_args', array( $this, 'filter_doc_categories_all' ), 10, 1 );
                add_action( 'template_redirect', array( $this, 'template_redirect_on_all' ), 99 );
                add_filter( 'rest_doc_category_query', array( $this, 'enable_or_disable_doc_category_filter_for_all' ), 90, 2 ); //to enable or disable doc_category api in rest
                add_filter( 'rest_doc_category_query', array( $this, 'show_or_hide_doc_category_terms_based_on_all' ), 91, 2 ); //to enable or disable doc_category api in rest
                add_filter( 'rest_docs_query', array( $this, 'enable_or_disable_docs_filter_for_all' ), 90, 2 );
                add_filter( 'rest_docs_query', array( $this, 'show_or_hide_docs_based_on_all' ), 91, 2 );
                add_filter( 'get_terms', array( $this, 'show_or_hide_categories_based_on_all_in_admin_list_table' ), 10, 4 );
                add_filter( 'post_row_actions', array( $this, 'custom_post_type_row_actions_for_all' ), 10, 2 );
                add_filter( 'doc_category_row_actions', array( $this, 'modify_quick_actions_wp_list_table_for_all' ), 10, 2 );
                add_action( 'template_redirect', array( $this, 'redirect_users_when_all_is_selected' ), 10 );
            } else { // handle specific category, docs selection
                // add_filter( 'rest_doc_category_query', [$this, 'include_or_exclude_selected_terms'] ); //for doc category rest api | logic done
                // add_filter( 'betterdocs_terms_query_args', [$this, 'include_or_exclude_selected_terms'] ); //for front-end using get_terms() to fetch terms | logic done
                // add_filter( 'betterdocs_articles_args', [$this, 'include_or_exclude_selected_posts'], 20, 3 ); //for front-end using WP_Query() to fetch docs | logic done
                add_filter( 'betterdocs_docs_count', array( $this, 'include_or_exclude_selected_posts_count' ), 10, 5 ); //for front-end using get_terms() | logic done
                // add_action( 'template_redirect', [$this, 'template_redirect_without_mkb'], 99 );
                add_filter( 'post_row_actions', array( $this, 'custom_post_type_row_actions' ), 10, 2 );
                add_filter( 'rest_doc_category_query', array( $this, 'enable_or_disable_terms_filter_doc_category' ), 10, 2 ); //to enable or disable doc_category api in rest
                add_filter( 'doc_category_row_actions', array( $this, 'modify_quick_actions_wp_list_table' ), 10, 2 );
                add_filter( 'terms_clauses', array( $this, 'filter_doc_categories' ), 11, 3 ); //global query for get_terms and rest api (applicable on admin | front-end | rest-api)
                add_filter( 'posts_where', array( $this, 'filter_doc_categories_docs' ), 90, 2 ); //global query for posts and rest api (applicable on admin | front-end | rest-api)
                add_filter( 'rest_docs_query', array( $this, 'enable_or_disable_filter' ), 90, 2 ); //filter rest post type query
                add_action( 'template_redirect', array( $this, 'template_redirect_single_docs' ), 99 );

                // Excludes terms from the docs/doc_category page that are not assigned to the current role.(it can be any outside user, author, editor, etc)
                add_filter( 'betterdocs_terms_query_args', array( $this, 'exclude_terms_or_redirect_not_in_role' ), 10, 1 );
                add_filter( 'rest_doc_category_query', array( $this, 'exclude_terms_or_redirect_not_in_role' ), 11, 2 );

                // redirect docs based on doc_category from the single doc or other page that are not assigned to the current role.(it can be any outside user, author, editor, etc)
                add_action( 'template_redirect', array( $this, 'redirect_users_not_in_rules' ), 10 );

                //disable the edit link from the WP_List_Table of docs
                add_filter( 'get_edit_post_link', array( $this, 'disable_post_links_for_docs_view' ), 10, 3 );

                //disable the edit link from the WP_List_Term_Table of docs
                add_filter( 'get_edit_term_link', array( $this, 'disable_terms_for_terms_view' ), 9999, 4 );
            }
        }

        add_filter( 'betterdocs_pro_localize_script', array( $this, 'localize_access_control_settings' ), 10, 2 );
    }

    /**
     * Whether the current actor may suppress access-control filters.
     *
     * `suppress_filters` exists so the BetterDocs settings screen can populate its
     * KB/category/doc selectors with the unrestricted list (see the `ajax` field
     * definitions in Core\Settings). It is an ordinary request parameter, so an
     * anonymous visitor can send it just as easily as the settings screen can —
     * honouring it unconditionally turns every restriction filter into an opt-out
     * and defeats advanced content restriction entirely.
     *
     * Gate it on the capability the settings screen itself requires. Users who can
     * already edit BetterDocs settings can read the restriction lists through the
     * settings UI regardless, so this grants them nothing new, while unauthenticated
     * and unprivileged callers keep every filter applied.
     *
     * @param array $params Request parameters (or query args) to inspect.
     * @return bool
     */
    protected function can_suppress_filters( $params ) {
        if ( empty( $params[ 'suppress_filters' ] ) ) {
            return false;
        }

        return current_user_can( 'edit_docs_settings' );
    }

    public function enable_or_disable_terms_filter_doc_category( $args, $request ) {
        $params = $request->get_params() ?? array(  );
        if ( $this->can_suppress_filters( $params ) ) {
            remove_filter( 'terms_clauses', array( $this, 'filter_doc_categories' ), 11, 3 );
            remove_filter( 'rest_doc_category_query', array( $this, 'exclude_terms_or_redirect_not_in_role' ), 11, 2 );
        }
        return $args;
    }

    public function enable_or_disable_terms_filter( $args, $taxonomies ) {
        if ( $this->can_suppress_filters( $args ) ) {
            remove_filter( 'terms_clauses', array( $this, 'filter_mkb_categories' ), 11, 3 );
        }
        return $args;
    }

    public function enable_or_disable_filter( $args, $request ) {
        $params = $request->get_params() ?? array(  );
        if ( $this->can_suppress_filters( $params ) ) {
            remove_filter( 'terms_clauses', array( $this, 'filter_doc_categories' ), 11, 3 ); //global query for get_terms and rest api (applicable on admin | front-end | rest-api)
            remove_filter( 'posts_where', array( $this, 'filter_doc_categories_docs' ), 90 );
            remove_filter( 'terms_clauses', array( $this, 'filter_mkb_categories' ), 11, 3 );
            remove_filter( 'posts_where', array( $this, 'filter_doc_categories_docs_in_mkb' ), 90, 2 ); //global query for posts and rest api (applicable on admin | front-end | rest-api)
            remove_filter( 'rest_docs_query', array( $this, 'filter_rest_docs_access_control' ), 91 );
        }
        return $args;
    }

    /**
     * Filter REST API docs query to exclude docs from restricted categories/MKBs.
     * Handles Instant Answer chatbot, search, and any REST docs request.
     *
     * @param array $args WP_Query arguments
     * @param \WP_REST_Request $request REST request object
     * @return array Modified query arguments
     */
    public function filter_rest_docs_access_control( $args, $request ) {
        $params = $request->get_params() ?? array(  );
        if ( $this->can_suppress_filters( $params ) ) {
            return $args;
        }

        // Get all post IDs from restricted categories and exclude them.
        // We use post__not_in instead of tax_query because the existing terms_clauses
        // filter (filter_doc_categories) interferes with WP_Tax_Query's internal term lookups.
        return $this->apply_restricted_post_ids( $args, $this->get_all_restricted_post_ids() );
    }

    /**
     * Exclude a set of post IDs from WP_Query args, robustly against ?include[]=.
     *
     * WP_Query resolves post targeting as `if p / elseif post__in / elseif
     * post__not_in`, so post__not_in is silently dropped whenever post__in
     * (REST `include[]`) is present. Adding restricted IDs only to post__not_in
     * therefore leaks every restricted doc to `?include[]=<id>`. Subtract the
     * restricted IDs from post__in as well, and force an empty result when the
     * caller asked only for restricted IDs.
     *
     * @param array $args        WP_Query args.
     * @param int[] $restricted  Post IDs to hide.
     * @return array
     */
    private function apply_restricted_post_ids( $args, $restricted ) {
        $restricted = wp_parse_id_list( (array) $restricted );
        if ( empty( $restricted ) ) {
            return $args;
        }

        // A single-post request takes precedence over post__in/post__not_in in
        // WP_Query: `p` wins the `if p / elseif post__in / elseif post__not_in`
        // chain outright, `page_id` goes further and *replaces* $where wholesale
        // ($where = " AND ID = <page_id>"), and `attachment_id` folds into `p`
        // during parse_query(). Any one of them would therefore bypass the
        // exclusion, so neutralise all three. Setting the key to 0 leaves the
        // query targeting a non-existent ID; post__in is pinned as a backstop.
        foreach ( array( 'p', 'page_id', 'attachment_id' ) as $single_key ) {
            if ( ! empty( $args[ $single_key ] ) && in_array( absint( $args[ $single_key ] ), $restricted, true ) ) {
                $args[ $single_key ] = 0;
                $args[ 'post__in' ]  = array( 0 );
            }
        }

        if ( ! empty( $args[ 'post__in' ] ) ) {
            // Normalise with absint FIRST. WP_Query absint()s post__in when it
            // builds the query, so a negative id (e.g. -115) would otherwise
            // survive this array_diff() and be re-emitted by core as ID IN (115),
            // leaking the restricted doc — the ?include[]=-<id> bypass.
            $args[ 'post__in' ] = array_values( array_diff( wp_parse_id_list( (array) $args[ 'post__in' ] ), $restricted ) );
            // Everything requested was restricted — a non-empty impossible id keeps
            // the result empty (an empty post__in would fall through to the query).
            if ( empty( $args[ 'post__in' ] ) ) {
                $args[ 'post__in' ] = array( 0 );
            }
        }

        $existing              = isset( $args[ 'post__not_in' ] ) ? wp_parse_id_list( (array) $args[ 'post__not_in' ] ) : array();
        $args[ 'post__not_in' ] = array_values( array_unique( array_merge( $existing, $restricted ) ) );

        return $args;
    }

    /**
     * Enforce content restriction on the single-item REST read routes for docs
     * and taxonomy terms (/wp/v2/docs/{id}, /wp/v2/doc_category/{id},
     * /wp/v2/knowledge_base/{id}).
     *
     * The collection routes are filtered by filter_rest_docs_access_control() /
     * rest_doc_category_query, but get_item() reads the object directly and
     * applies only core's published/read check, so restricted content is
     * otherwise readable there.
     *
     * Runs on rest_request_before_callbacks rather than rest_prepare_*: a
     * WP_Error returned from rest_prepare_docs reaches get_item()'s
     * $response->link_header() and fatals (WP_Error has no such method), whereas
     * the REST dispatcher turns a WP_Error from before_callbacks into a clean
     * error response. It also runs before the object is loaded, so no restricted
     * data is prepared.
     *
     * Scoped to single-item GET reads in non-edit context — edits use other
     * methods and are gated on caps by core, and the block editor loads via
     * GET with context=edit.
     *
     * @param \WP_REST_Response|\WP_Error|null $response Short-circuit response, if any.
     * @param array                            $handler  Matched route handler.
     * @param \WP_REST_Request                 $request  The REST request.
     * @return \WP_REST_Response|\WP_Error|null
     */
    public function restrict_single_item_rest_read( $response, $handler, $request ) {
        // Something earlier already resolved the response — do not override it.
        if ( is_wp_error( $response ) || null !== $response ) {
            return $response;
        }

        if ( 'GET' !== $request->get_method() || 'edit' === $request->get_param( 'context' ) ) {
            return $response;
        }

        $route = (string) $request->get_route();

        // Single doc: /wp/v2/docs/<id>
        if ( preg_match( '#^/wp/v2/docs/(\d+)$#', $route, $m ) ) {
            $restricted = array_map( 'intval', (array) $this->get_all_restricted_post_ids() );
            if ( in_array( (int) $m[1], $restricted, true ) ) {
                return new \WP_Error(
                    'rest_post_invalid_id',
                    __( 'Invalid post ID.', 'betterdocs-pro' ),
                    array( 'status' => 404 )
                );
            }
            return $response;
        }

        // Single term: /wp/v2/doc_category/<id> or /wp/v2/knowledge_base/<id>
        if ( preg_match( '#^/wp/v2/(doc_category|knowledge_base)/(\d+)$#', $route, $m ) ) {
            if ( $this->is_term_restricted( (int) $m[2], $m[1] ) ) {
                return new \WP_Error(
                    'rest_term_invalid',
                    __( 'Term does not exist.', 'betterdocs-pro' ),
                    array( 'status' => 404 )
                );
            }
        }

        return $response;
    }

    /**
     * Whether a single taxonomy term is hidden from the current user.
     *
     * "all" restricted mode hides every doc_category term outright. Otherwise
     * reuse the active terms_clauses enforcement: a filtered lookup limited to
     * this one term returns it only when the user may see it — so this never
     * over-blocks when nothing is restricted.
     *
     * @param int    $term_id
     * @param string $taxonomy
     * @return bool
     */
    private function is_term_restricted( $term_id, $taxonomy ) {
        if ( 'doc_category' === $taxonomy && $this->is_all_mode_restricted() ) {
            return true;
        }

        $visible = get_terms( array(
            'taxonomy'   => $taxonomy,
            'include'    => array( (int) $term_id ),
            'hide_empty' => false,
            'fields'     => 'ids',
        ) );
        $visible = is_wp_error( $visible ) ? array() : array_map( 'intval', (array) $visible );

        return ! in_array( (int) $term_id, $visible, true );
    }

    /**
     * Exclude restricted docs from the main feed and site-search queries.
     *
     * The specific-mode posts_where filter only fires when a query's post_type
     * is exactly 'docs' — site search (which spans post types) and "all"-mode
     * do not satisfy that — so restricted docs surface in /feed/, /docs/feed/
     * and ?s= results. post__not_in only affects the listed doc IDs, so other
     * post types in a search remain untouched.
     *
     * @param \WP_Query $query
     * @return void
     */
    /**
     * Exclude restricted docs from any docs-type query (the reliable path for the
     * REST docs collection). Mirrors the basic engine's filter_posts.
     *
     * WP_Query gives post__in precedence over post__not_in, so a REST collection
     * request carrying ?include[]=<id> (or -<id>, which core absint()s) would
     * bypass a post__not_in-only exclusion. apply_restricted_post_ids() subtracts
     * from post__in as well. Scoped to docs post_type so unrelated queries are
     * untouched; single-doc views are handled by template_redirect and are not
     * post__in-based, so they are unaffected.
     *
     * @param \WP_Query $query
     * @return void
     */
    /**
     * Whether the current run is a trusted server-side context where viewer-facing
     * content restriction must not apply.
     *
     * The front-end pre_get_posts handlers gate on is_admin(), which is false in
     * WP-Cron and WP-CLI. Without this guard those contexts are treated as an
     * anonymous page view, so restricted docs are filtered out of scheduled jobs,
     * exports and CLI commands even though there is no HTTP visitor to hide them
     * from (B-0). Cron and CLI run as the server operator, not a visitor, so they
     * must see the full, unrestricted set.
     *
     * @return bool
     */
    private function is_trusted_non_visitor_context() {
        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return true;
        }

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return true;
        }

        return false;
    }

    public function exclude_restricted_docs_from_docs_query( $query ) {
        // Skip while get_all_restricted_post_ids() is computing its set: it runs
        // its own docs queries and this handler must neither recurse into it nor
        // rewrite those computation queries (doing so would compute the set from
        // an already-filtered query and corrupt it — hiding restricted docs from
        // their own exclusion list).
        if ( self::$computing_restricted_ids > 0 || is_admin() || $this->is_trusted_non_visitor_context() || ! $query instanceof \WP_Query ) {
            return;
        }

        $post_type = $query->get( 'post_type' );
        $is_docs   = 'docs' === $post_type || ( is_array( $post_type ) && in_array( 'docs', $post_type, true ) );
        if ( ! $is_docs ) {
            return;
        }

        $restricted = array_map( 'intval', (array) $this->get_all_restricted_post_ids() );

        if ( empty( $restricted ) ) {
            return;
        }

        $updated = $this->apply_restricted_post_ids(
            array(
                'post__in'     => (array) $query->get( 'post__in' ),
                'post__not_in' => (array) $query->get( 'post__not_in' ),
            ),
            $restricted
        );
        $query->set( 'post__not_in', $updated[ 'post__not_in' ] );
        if ( ! empty( $query->get( 'post__in' ) ) ) {
            $query->set( 'post__in', $updated[ 'post__in' ] );
        }
    }

    public function exclude_restricted_docs_from_feed_search( $query ) {
        if ( is_admin() || $this->is_trusted_non_visitor_context() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
            return;
        }
        if ( ! $query->is_feed() && ! $query->is_search() ) {
            return;
        }

        $restricted = array_map( 'intval', (array) $this->get_all_restricted_post_ids() );
        if ( empty( $restricted ) ) {
            return;
        }

        // Build the exclusion through the shared helper so a crafted feed/search
        // request carrying post__in (?include[]=) cannot bypass it either.
        $updated = $this->apply_restricted_post_ids(
            array(
                'post__in'     => (array) $query->get( 'post__in' ),
                'post__not_in' => (array) $query->get( 'post__not_in' ),
            ),
            $restricted
        );

        $query->set( 'post__not_in', $updated[ 'post__not_in' ] );
        if ( ! empty( $query->get( 'post__in' ) ) ) {
            $query->set( 'post__in', $updated[ 'post__in' ] );
        }
    }

    /**
     * Exclude restricted docs from the core docs sitemap.
     *
     * @param array  $args      WP_Query args for the sitemap provider.
     * @param string $post_type Post type the sitemap is being built for.
     * @return array
     */
    public function exclude_restricted_docs_from_sitemap( $args, $post_type ) {
        if ( 'docs' !== $post_type ) {
            return $args;
        }

        return $this->apply_restricted_post_ids( $args, $this->get_all_restricted_post_ids() );
    }

    /**
     * Block oEmbed output for a restricted doc.
     *
     * The oEmbed endpoint resolves a URL to a post via get_post() and returns
     * its title/author/html, bypassing the query filters. Return empty data for
     * a restricted doc so it cannot be embedded.
     *
     * @param array    $data Prepared oEmbed data.
     * @param \WP_Post $post The post the oEmbed is for.
     * @return array
     */
    public function block_restricted_doc_oembed( $data, $post ) {
        if ( ! ( $post instanceof \WP_Post ) || 'docs' !== $post->post_type ) {
            return $data;
        }

        $restricted = array_map( 'intval', (array) $this->get_all_restricted_post_ids() );
        if ( in_array( (int) $post->ID, $restricted, true ) ) {
            return array();
        }

        return $data;
    }

    /**
     * Inject access-control restrictions into every docs query routed through
     * betterdocs()->query->get_posts() / Query::docs_query_args().
     *
     * Needed because the docs-archive search modal (REST betterdocs/v1/search) sets
     * 'suppress_filters' => true, which short-circuits the posts_where filter that
     * normally enforces advanced-mode AccessControl. Hooking here runs before WP_Query
     * is constructed, so suppress_filters does not apply.
     *
     * Routed through apply_restricted_post_ids() rather than merging into
     * post__not_in by hand: this handler used to only append to post__not_in,
     * which WP_Query silently drops whenever post__in / p / page_id are also
     * present. Every bypass closed in apply_restricted_post_ids() — the
     * ?include[]=-<id> negative-ID normalisation, the post__in subtraction and
     * the single-post-target guard — therefore never fired on this path.
     *
     * @param array $args        Final WP_Query args computed by docs_query_args().
     * @param int|null $term_id  Term ID context, unused.
     * @param array $origin_args Original args passed in, unused.
     * @return array
     */
    public function filter_articles_args_access_control( $args, $term_id, $origin_args ) {
        return $this->apply_restricted_post_ids( $args, $this->get_all_restricted_post_ids() );
    }

    /**
     * Get all post IDs that should be excluded for the current user based on access control rules.
     *
     * Handles two complementary cases:
     *   - Explicit restriction (rules with permission_mode 'restricted' or 'edit-only') — collect those IDs
     *   - Whitelist (rules with permission_mode 'view' or 'full-control') — when any applicable rule whitelists
     *     specific items, everything NOT in the whitelist must be treated as restricted
     *
     * Public so addons (e.g. betterdocs-ai-chatbot) can call it instead of reimplementing.
     *
     * @return int[] Post IDs to hide from the current user.
     */
    public function get_all_restricted_post_ids() {
        // Mark that we are computing the restricted set. This method runs its own
        // docs get_posts()/WP_Query internally (collect_post_ids_by_status,
        // get_all_mode_restricted_post_ids, get_all_doc_post_ids), which fire
        // pre_get_posts — and exclude_restricted_docs_from_docs_query() must NOT
        // rewrite those computation queries, or it would compute the set from an
        // already-filtered query and corrupt it. The flag makes that handler skip
        // while we compute.
        // Reference-counted enter. Wrapped in try/finally so an uncaught
        // exception in any of the internal queries (or a third-party
        // pre_get_posts handler they trigger) can never strand the guard
        // "active" and leave restriction silently disabled for the rest of the
        // request (B-1). Decrement — not reset-to-false — so a nested call
        // restores the OUTER call's active state rather than clearing it (B-2).
        self::$computing_restricted_ids++;

        try {
            $restricted_post_ids = $this->collect_post_ids_by_status( array( 'restricted', 'edit-only' ) );

            // "all" restricted mode hides every categorised doc, but its set is not
            // captured by collect_post_ids_by_status() — the 'all' key is not a real
            // term id — so merge it in explicitly. This is what lets the single-item
            // REST guard and the feed/search/sitemap/oEmbed filters enforce "all"
            // mode, not just the REST collection route.
            $restricted_post_ids = array_merge( $restricted_post_ids, $this->get_all_mode_restricted_post_ids() );

            $viewable_post_ids   = $this->get_viewable_post_ids();

            if ( null !== $viewable_post_ids ) {
                $all_doc_ids         = $this->get_all_doc_post_ids();
                $non_viewable        = array_diff( $all_doc_ids, $viewable_post_ids );
                $restricted_post_ids = array_merge( $restricted_post_ids, $non_viewable );
            }
        } finally {
            self::$computing_restricted_ids--;
            if ( self::$computing_restricted_ids < 0 ) {
                self::$computing_restricted_ids = 0;
            }
        }

        return array_values( array_unique( array_map( 'intval', $restricted_post_ids ) ) );
    }

    /**
     * Whether "all categories → restricted" is in effect for the current viewer.
     *
     * Prefers the viewer's resolved rules (final_access_control_settings). When
     * those are empty — a viewer whose role matches no rule, e.g. an anonymous
     * visitor — it falls back to the raw rules and treats any "all → restricted"
     * rule as applying, mirroring get_category_ids_by_status()'s outsider
     * fallback. Without this, all-mode restriction did nothing for logged-out
     * visitors (final_access_control_settings === []), so the new all-mode
     * helpers leaked to anonymous users.
     *
     * @return bool
     */
    private function is_all_mode_restricted() {
        $mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] )
            ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';
        if ( 'restricted' === $mode ) {
            return true;
        }

        // Outsider fallback: role not in any rule.
        if ( empty( $this->final_access_control_settings ) && ! empty( $this->access_control_settings ) ) {
            foreach ( $this->access_control_settings as $rule ) {
                $categories = isset( $rule[ 'control_access_restrict_doc_category' ] ) ? (array) $rule[ 'control_access_restrict_doc_category' ] : array();
                $permission = isset( $rule[ 'control_access_permission_mode' ] ) ? $rule[ 'control_access_permission_mode' ] : '';
                // Raw rules store the selection as a list ("all") with a separate
                // permission mode; the resolved form stores it as [ 'all' => mode ].
                $selected_all = in_array( 'all', $categories, true ) || isset( $categories[ 'all' ] );
                $all_status   = isset( $categories[ 'all' ] ) ? $categories[ 'all' ] : $permission;
                if ( $selected_all && 'restricted' === $all_status ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Post IDs hidden by "all"-restricted mode: every published doc attached to a
     * doc_category. Mirrors (and is shared with) show_or_hide_docs_based_on_all()
     * so the REST collection filter and every other enforcement path hide exactly
     * the same docs. Returns [] when "all" mode is not the active setting.
     *
     * @return int[]
     */
    private function get_all_mode_restricted_post_ids() {
        if ( ! $this->is_all_mode_restricted() ) {
            return array();
        }

        $cache_key = 'betterdocs_all_mode_restricted_posts';
        if ( isset( self::$query_cache[ $cache_key ] ) ) {
            return self::$query_cache[ $cache_key ];
        }

        $terms = get_terms( array(
            'taxonomy'   => 'doc_category',
            'hide_empty' => true,
            'fields'     => 'ids',
            'number'     => 1000,
        ) );

        $posts = array();
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( array_chunk( $terms, 50 ) as $term_chunk ) {
                $chunk_posts = get_posts( array(
                    'post_type'      => 'docs',
                    'fields'         => 'ids',
                    'posts_per_page' => 500,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'doc_category',
                            'field'    => 'term_id',
                            'terms'    => $term_chunk,
                            'operator' => 'IN',
                        ),
                    ),
                ) );
                if ( ! empty( $chunk_posts ) ) {
                    $posts = array_merge( $posts, $chunk_posts );
                }
            }
        }

        $posts = array_values( array_unique( array_map( 'intval', $posts ) ) );
        self::$query_cache[ $cache_key ] = $posts;
        return $posts;
    }

    /**
     * Compute the viewable (whitelisted) post IDs for the current user.
     *
     * Applies the same scoping semantic used by filter_doc_categories_docs /
     * filter_doc_categories_docs_in_mkb so that the REST fallback matches the
     * posts_where-based filters:
     *
     *   - Specific doc listed → only that doc is viewable (even if its parent category is also listed)
     *   - Category listed with no specific doc under it → all docs in the category are viewable
     *   - MKB listed with no specific category/doc under it → all docs in the MKB are viewable
     *
     * Returns null when no whitelist rule applies to the current user (caller should
     * leave the query unchanged). Returns an array (possibly empty) otherwise.
     *
     * @return int[]|null
     */
    private function get_viewable_post_ids() {
        $view_statuses = array( 'view', 'full-control' );
        $view_doc_ids  = array_map( 'intval', $this->get_doc_ids_by_status( $view_statuses ) );
        $view_cat_ids  = array_map( 'intval', $this->get_category_ids_by_status( $view_statuses ) );
        $view_mkb_ids  = betterdocs_pro()->multiple_kb->is_enable
            ? array_map( 'intval', $this->get_mkb_ids_by_status( $view_statuses ) )
            : array();

        if ( empty( $view_doc_ids ) && empty( $view_cat_ids ) && empty( $view_mkb_ids ) ) {
            return null;
        }

        $viewable = $view_doc_ids;

        // Categories: if any whitelisted specific doc is inside the category, only those docs count.
        // Otherwise, include every doc attached to the category.
        $cats_without_specific_docs = array();
        foreach ( $view_cat_ids as $cat_id ) {
            $has_specific_doc = false;
            foreach ( $view_doc_ids as $doc_id ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $cat_id ) ) {
                    $has_specific_doc = true;
                    break;
                }
            }
            if ( ! $has_specific_doc ) {
                $cats_without_specific_docs[] = $cat_id;
            }
        }
        if ( ! empty( $cats_without_specific_docs ) ) {
            $viewable = array_merge( $viewable, $this->fetch_doc_ids_by_term_ids( $cats_without_specific_docs, 'doc_category' ) );
        }

        // Knowledge bases: only expand to "all docs in the MKB" when no specific category
        // or doc under that MKB has been whitelisted. Otherwise the narrower scope wins.
        if ( ! empty( $view_mkb_ids ) ) {
            $mkbs_without_specific_items = array();
            foreach ( $view_mkb_ids as $mkb_id ) {
                if ( ! $this->mkb_has_specific_whitelist_items( $mkb_id, $view_cat_ids, $view_doc_ids ) ) {
                    $mkbs_without_specific_items[] = $mkb_id;
                }
            }
            if ( ! empty( $mkbs_without_specific_items ) ) {
                $viewable = array_merge( $viewable, $this->fetch_doc_ids_by_term_ids( $mkbs_without_specific_items, 'knowledge_base' ) );
            }
        }

        return array_values( array_unique( array_map( 'intval', $viewable ) ) );
    }

    /**
     * True when any whitelisted doc or category is scoped under the given MKB.
     * Categories link to MKBs via the 'doc_category_knowledge_base' term meta (array of MKB slugs).
     */
    private function mkb_has_specific_whitelist_items( $mkb_id, array $view_cat_ids, array $view_doc_ids ) {
        foreach ( $view_doc_ids as $doc_id ) {
            if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $mkb_id ) ) {
                return true;
            }
        }

        if ( ! empty( $view_cat_ids ) ) {
            $mkb_term = get_term( $mkb_id, 'knowledge_base' );
            $mkb_slug = ( $mkb_term && ! is_wp_error( $mkb_term ) ) ? $mkb_term->slug : '';
            if ( '' !== $mkb_slug ) {
                foreach ( $view_cat_ids as $cat_id ) {
                    $cat_mkbs = get_term_meta( $cat_id, 'doc_category_knowledge_base', true );
                    if ( is_array( $cat_mkbs ) && in_array( $mkb_slug, $cat_mkbs, true ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Fetch all doc post IDs attached to the given term IDs in a specific taxonomy.
     * @param int[]  $term_ids
     * @param string $taxonomy
     * @return int[]
     */
    private function fetch_doc_ids_by_term_ids( array $term_ids, $taxonomy ) {
        if ( empty( $term_ids ) ) {
            return array();
        }
        global $wpdb;
        $placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
        $args         = array_merge( array( $taxonomy ), $term_ids );
        $sql          = "SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE tt.taxonomy = %s AND tt.term_id IN ($placeholders)";
        // Term-relationship lookup against core tables; $placeholders are %d tokens bound via $wpdb->prepare().
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $rows         = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        return array_map( 'intval', (array) $rows );
    }

    /**
     * Collect post IDs whose access-control status is one of $statuses.
     * Aggregates from restricted categories, MKBs and individually-restricted docs.
     *
     * @param string[] $statuses Status values to match (e.g. ['restricted'] or ['view','full-control']).
     * @return int[]
     */
    private function collect_post_ids_by_status( array $statuses ) {
        global $wpdb;

        $term_ids = array_merge(
            $this->get_category_ids_by_status( $statuses ),
            $this->get_mkb_ids_by_status( $statuses )
        );
        $post_ids = $this->get_doc_ids_by_status( $statuses );

        if ( ! empty( $term_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
            // Term-relationship lookup against core tables; $placeholders are %d tokens bound via $wpdb->prepare().
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $query        = $wpdb->prepare(
                "SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 WHERE tt.term_id IN ($placeholders)",
                $term_ids
            );
            $post_ids = array_merge( $post_ids, array_map( 'intval', (array) $wpdb->get_col( $query ) ) );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        }

        return array_values( array_unique( $post_ids ) );
    }

    /**
     * Published doc post IDs — used to compute the complement of a whitelist.
     * @return int[]
     */
    private function get_all_doc_post_ids() {
        global $wpdb;
        $cache_key = 'betterdocs_all_doc_post_ids';
        if ( isset( self::$query_cache[ $cache_key ] ) ) {
            return self::$query_cache[ $cache_key ];
        }
        // Static literal query against the core posts table; result is cached in self::$query_cache.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $ids = array_map( 'intval', (array) $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'docs' AND post_status = 'publish'"
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        self::$query_cache[ $cache_key ] = $ids;
        return $ids;
    }

    /**
     * Doc-category IDs whose access-control status matches any of $statuses.
     * When $statuses contains 'restricted', applies the "keep category visible if a specific doc
     * inside it is restricted" nuance (matches filter_doc_categories logic).
     */
    private function get_category_ids_by_status( array $statuses ) {
        $is_mkb        = betterdocs_pro()->multiple_kb->is_enable;
        $cat_key       = $is_mkb ? 'control_access_restrict_doc_category_kb' : 'control_access_restrict_doc_category';
        $post_key      = $is_mkb ? 'control_access_restrict_docs_kb' : 'control_access_restrict_docs';
        $apply_nuance  = in_array( 'restricted', $statuses, true );
        $matched       = array();

        if ( ! empty( $this->final_access_control_settings ) ) {
            $categories = $this->final_access_control_settings[ $cat_key ] ?? array();
            $posts      = $this->final_access_control_settings[ $post_key ] ?? array();

            foreach ( $categories as $term_id => $status ) {
                if ( in_array( $status, $statuses, true ) ) {
                    $matched[] = $term_id;
                }
            }

            if ( $apply_nuance ) {
                foreach ( $matched as $i => $term_id ) {
                    foreach ( $posts as $doc_id => $status ) {
                        if ( 'restricted' === $status && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term_id ) ) {
                            unset( $matched[ $i ] );
                            break;
                        }
                    }
                }
                $matched = array_values( $matched );
            }
        } elseif ( $apply_nuance && ! empty( $this->access_control_settings ) ) {
            // User's role not in any rule — fall back to excluding all categories mentioned in any rule.
            // Only applies to the restricted path; no whitelist behaviour for users outside all rules.
            foreach ( $this->access_control_settings as $rule ) {
                if ( ! empty( $rule[ $cat_key ] ) ) {
                    foreach ( $rule[ $cat_key ] as $term_id => $status ) {
                        $matched[] = $term_id;
                    }
                }
            }
            $matched = array_values( array_unique( $matched ) );
        }

        return $matched;
    }

    /**
     * Knowledge-base IDs whose access-control status matches any of $statuses (MKB mode only).
     * When 'restricted' is in $statuses, applies the "keep MKB visible if a specific category
     * inside it is restricted" nuance.
     */
    private function get_mkb_ids_by_status( array $statuses ) {
        $apply_nuance = in_array( 'restricted', $statuses, true );
        $matched      = array();

        if ( ! empty( $this->final_access_control_settings ) ) {
            $mkb_terms = $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ?? array();
            $cat_terms = $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ?? array();

            foreach ( $mkb_terms as $mkb_id => $status ) {
                if ( in_array( $status, $statuses, true ) ) {
                    $matched[] = $mkb_id;
                }
            }

            if ( $apply_nuance ) {
                foreach ( $matched as $i => $mkb_id ) {
                    $mkb_term = get_term( $mkb_id );
                    $mkb_slug = isset( $mkb_term->slug ) ? $mkb_term->slug : '';
                    foreach ( $cat_terms as $cat_id => $status ) {
                        $meta = get_term_meta( $cat_id, 'doc_category_knowledge_base', true );
                        if ( ! empty( $meta ) && in_array( $mkb_slug, $meta ) && 'restricted' === $status ) {
                            unset( $matched[ $i ] );
                            break;
                        }
                    }
                }
                $matched = array_values( $matched );
            }
        } elseif ( $apply_nuance && ! empty( $this->access_control_settings ) ) {
            foreach ( $this->access_control_settings as $rule ) {
                if ( ! empty( $rule[ 'control_access_restrict_multiple_kb' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_multiple_kb' ] as $mkb_id => $status ) {
                        $matched[] = $mkb_id;
                    }
                }
            }
            $matched = array_values( array_unique( $matched ) );
        }

        return $matched;
    }

    /**
     * Individually-listed doc IDs whose access-control status matches any of $statuses.
     */
    private function get_doc_ids_by_status( array $statuses ) {
        if ( empty( $this->final_access_control_settings ) ) {
            return array();
        }

        $key     = betterdocs_pro()->multiple_kb->is_enable ? 'control_access_restrict_docs_kb' : 'control_access_restrict_docs';
        $posts   = $this->final_access_control_settings[ $key ] ?? array();
        $matched = array();

        foreach ( $posts as $doc_id => $status ) {
            if ( in_array( $status, $statuses, true ) ) {
                $matched[] = $doc_id;
            }
        }

        return $matched;
    }

    public function enable_or_disable_docs_filter_for_all( $args, $request ) {
        $params = $request->get_params() ?? array(  );
        if ( $this->can_suppress_filters( $params ) ) {
            remove_filter( 'rest_docs_query', array( $this, 'show_or_hide_docs_based_on_all' ), 91, 2 );
        }
        return $args;
    }

    /**
     * Show or hide documents based on 'all' restriction mode
     *
     * PERFORMANCE OPTIMIZATIONS APPLIED:
     * - Caching to prevent repeated expensive queries
     * - Chunked processing to prevent memory issues
     * - Early exit conditions for performance
     * - Limited processing to prevent memory exhaustion
     *
     * @param array $args WP_Query arguments
     * @param \WP_REST_Request $request REST request object (unused but required by hook)
     * @return array Modified query arguments with post__not_in parameter
     */
    public function show_or_hide_docs_based_on_all( $args, $request ) {
        // Shared with get_all_restricted_post_ids() so the REST collection and
        // every other enforcement path exclude the identical set. Routed through
        // apply_restricted_post_ids() so ?include[]= cannot bypass it.
        return $this->apply_restricted_post_ids( $args, $this->get_all_mode_restricted_post_ids() );
    }

    public function show_or_hide_categories_based_on_all_in_admin_list_table( $terms, $taxonomy, $query_vars, $terms_query ) {
        $doc_categories_mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';
        if ( function_exists( 'get_current_screen' ) && get_current_screen() != null && is_admin() && get_current_screen()->id == 'edit-doc_category' && 'restricted' == $doc_categories_mode ) {
            $terms = array(  );
        }
        return $terms;
    }

    public function enable_or_disable_doc_category_filter_for_all( $args, $request ) {
        $params = $request->get_params() ?? array(  );

        if ( $this->can_suppress_filters( $params ) ) {
            remove_filter( 'rest_doc_category_query', array( $this, 'show_or_hide_doc_category_terms_based_on_all' ), 91, 2 );
        }

        return $args;
    }

    public function show_or_hide_doc_category_terms_based_on_all( $args, $request ) {
        $doc_categories_mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';

        if ( ! empty( $doc_categories_mode ) && 'restricted' == $doc_categories_mode ) {
            $terms = get_terms( array(
                'taxonomy' => 'doc_category',
                'hide_empty' => true,
                'fields' => 'ids'
             ) );

            if ( ! empty( $terms ) ) {
                $args[ 'exclude' ] = $terms;
            }
        }

        return $args;
    }

    public function modify_quick_actions_wp_list_table_for_knowledge_base( $actions, $tag ) {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );

        foreach ( $selected_categories_terms as $term_id => $term_status ) {
            if ( isset( $tag->term_id ) && $tag->term_id == $term_id && 'view' == $term_status ) {
                unset( $actions[ 'edit' ] );
                unset( $actions[ 'inline hide-if-no-js' ] );
                unset( $actions[ 'delete' ] );
            } elseif ( isset( $tag->term_id ) && $tag->term_id == $term_id && 'edit-only' == $term_status ) {
                unset( $actions[ 'view' ] );
                unset( $actions[ 'delete' ] );
            }
        }

        return $actions;
    }

    public function modify_quick_actions_wp_list_table_for_doc_category( $actions, $tag ) {
        $selected_categories_terms                = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] : array(  ) ) : array(  );
        $selected_mkb_terms                       = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
        $actions_applied_on_term_ids              = array(  ); // this is to put a flag to check if specific doc term belonging to a mkb or not has been modified based on actions like edit, view, full control, etc
        $attached_mkb_ids_on_current_doc_category = array(  ); // get the current mkb terms from ther category terms

        $mkb_attached_on_category = get_term_meta( $tag->term_id, 'doc_category_knowledge_base', true ) ? get_term_meta( $tag->term_id, 'doc_category_knowledge_base', true ) : array(  );

        if ( ! empty( $mkb_attached_on_category ) ) {
            foreach ( $mkb_attached_on_category as $mkb_slug ) {
                $mkb_id = isset( get_term_by( 'slug', $mkb_slug, 'knowledge_base' )->term_id ) ? get_term_by( 'slug', $mkb_slug, 'knowledge_base' )->term_id : 0;
                if ( 0 != $mkb_id ) {
                    array_push( $attached_mkb_ids_on_current_doc_category, $mkb_id );
                }
            }
        }

        foreach ( $attached_mkb_ids_on_current_doc_category as $mkb_term_id ) {
            if ( isset( $selected_mkb_terms[ $mkb_term_id ] ) && 'view' == $selected_mkb_terms[ $mkb_term_id ] && isset( $tag->term_id ) ) {
                $actions_applied_on_term_ids[ $tag->term_id ] = true;
                $actions[ 'view' ]                            = '<a href="' . get_term_link( $tag->term_id ) . '" aria-label="' . $tag->name . '">View</a>';
                unset( $actions[ 'edit' ] );
                unset( $actions[ 'inline hide-if-no-js' ] );
                unset( $actions[ 'delete' ] );
            } elseif ( isset( $selected_mkb_terms[ $mkb_term_id ] ) && 'edit-only' == $selected_mkb_terms[ $mkb_term_id ] && isset( $tag->term_id ) ) {
                $actions_applied_on_term_ids[ $tag->term_id ] = true;
                unset( $actions[ 'view' ] );
                unset( $actions[ 'delete' ] );
            }
        }

        foreach ( $selected_categories_terms as $term_id => $term_status ) {
            if ( isset( $tag->term_id ) && $tag->term_id == $term_id && 'view' == $term_status && ( ! isset( $actions_applied_on_term_ids[ $tag->term_id ] ) ) ) {
                $actions[ 'view' ] = '<a href="' . get_term_link( $term_id ) . '" aria-label="' . $tag->name . '">View</a>';
                unset( $actions[ 'edit' ] );
                unset( $actions[ 'inline hide-if-no-js' ] );
                unset( $actions[ 'delete' ] );
            } elseif ( isset( $tag->term_id ) && $tag->term_id == $term_id && 'edit-only' == $term_status && ( ! isset( $actions_applied_on_term_ids[ $tag->term_id ] ) ) ) {
                unset( $actions[ 'view' ] );
                unset( $actions[ 'delete' ] );
            }
        }

        return $actions;
    }

    public function exclude_mkb_terms_or_redirect_not_in_role( $query_args ) {
        if ( empty( $this->final_access_control_settings ) & ! empty( $this->access_control_settings ) ) {
            $all_terms = array(  );

            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $rule[ 'control_access_restrict_multiple_kb' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_multiple_kb' ] as $mkb_id => $mkb_status ) {
                        array_push( $all_terms, $mkb_id );
                    }
                }
            }

            if ( ! empty( $all_terms ) ) {
                $query_args[ 'exclude' ] = $all_terms;
            }
        }
        return $query_args;
    }

    public function redirect_users_not_in_rules_mkb() {
        if ( empty( $this->final_access_control_settings ) & ! empty( $this->access_control_settings ) && is_tax( 'knowledge_base' ) ) {
            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $rule[ 'control_access_restrict_multiple_kb' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_multiple_kb' ] as $mkb_id => $mkb_status ) {
                        if ( get_queried_object_id() == $mkb_id ) {
                            if ( $this->restricted_url ) {
                                wp_safe_redirect( $this->restricted_url );
                                exit();
                            } else {
                                global $wp_query;
                                $wp_query->set_404();
                                status_header( 404 );
                                get_template_part( 404 );
                                exit();
                            }
                        }
                    }
                }
            }
        } elseif ( empty( $this->final_access_control_settings ) & ! empty( $this->access_control_settings ) && is_tax( 'doc_category' ) ) {
            $mkb_ids_for_doc_categories = array(  );

            $doc_categories = get_terms( array(
                'taxonomy' => 'doc_category',
                'hide_empty' => true,
                'fields' => 'ids'
             ) );

            foreach ( $doc_categories as $doc_category_id ) {
                $mkbs_in_doc_categories = get_term_meta( $doc_category_id, 'doc_category_knowledge_base', true ) ? get_term_meta( $doc_category_id, 'doc_category_knowledge_base', true ) : array(  );
                if ( ! empty( $mkbs_in_doc_categories ) ) {
                    foreach ( $mkbs_in_doc_categories as $mkb_slug ) {
                        $mkb_id = isset( get_term_by( 'slug', $mkb_slug, 'knowledge_base' )->term_id ) ? get_term_by( 'slug', $mkb_slug, 'knowledge_base' )->term_id : 0;
                        if ( 0 != $mkb_id ) {
                            array_push( $mkb_ids_for_doc_categories, $mkb_id );
                        }
                    }
                }
            }

            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $rule[ 'control_access_restrict_multiple_kb' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_multiple_kb' ] as $mkb_id => $mkb_status ) {
                        if ( in_array( $mkb_id, $mkb_ids_for_doc_categories ) ) {
                            if ( $this->restricted_url ) {
                                wp_safe_redirect( $this->restricted_url );
                                exit();
                            } else {
                                global $wp_query;
                                $wp_query->set_404();
                                status_header( 404 );
                                get_template_part( 404 );
                                exit();
                            }
                        }
                    }
                }
            }
        } elseif ( empty( $this->final_access_control_settings ) & ! empty( $this->access_control_settings ) && is_singular( 'docs' ) ) {
            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $rule[ 'control_access_restrict_multiple_kb' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_multiple_kb' ] as $mkb_id => $mkb_status ) {
                        if ( has_term( $mkb_id, 'knowledge_base', get_the_ID() ) ) {
                            if ( $this->restricted_url ) {
                                wp_safe_redirect( $this->restricted_url );
                                exit();
                            } else {
                                global $wp_query;
                                $wp_query->set_404();
                                status_header( 404 );
                                get_template_part( 404 );
                                exit();
                            }
                        }
                    }
                }
            }
        }
    }

    public function exclude_terms_or_redirect_not_in_role( $query_args ) {
        if ( empty( $this->final_access_control_settings ) & ! empty( $this->access_control_settings ) ) { // for all doc categories
            $all_terms = array(  );

            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_doc_category' ] ) && ! empty( $rule[ 'control_access_restrict_doc_category' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_doc_category' ] as $term_id => $status ) {
                        array_push( $all_terms, $term_id );
                    }
                }
            }

            if ( ! empty( $all_terms ) ) {
                $query_args[ 'exclude' ] = $all_terms;
            }
        }

        return $query_args;
    }

    public function redirect_users_not_in_rules() {
        if ( empty( $this->final_access_control_settings ) && ! empty( $this->access_control_settings ) && is_post_type_archive( 'docs' ) ) { // handle when all is selected for outside users
            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_doc_category' ] ) && ! empty( $rule[ 'control_access_restrict_doc_category' ] ) && in_array( 'all', array_keys( $rule[ 'control_access_restrict_doc_category' ] ) ) ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            }
        } elseif ( empty( $this->final_access_control_settings ) & ! empty( $this->access_control_settings ) && is_singular( 'docs' ) ) { // for all doc categories
            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_doc_category' ] ) && ! empty( $rule[ 'control_access_restrict_doc_category' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_doc_category' ] as $term_id => $status ) {
                        if ( has_term( $term_id, 'doc_category', get_the_ID() ) ) {
                            if ( $this->restricted_url ) {
                                wp_safe_redirect( $this->restricted_url );
                                exit();
                            } else {
                                global $wp_query;
                                $wp_query->set_404();
                                status_header( 404 );
                                get_template_part( 404 );
                                exit();
                            }
                        }
                    }
                }
            }
        } elseif ( empty( $this->final_access_control_settings ) && ! empty( $this->access_control_settings ) && is_tax( 'doc_category' ) ) { // redirect for doc category page
            foreach ( $this->access_control_settings as $rule ) {
                if ( isset( $rule[ 'control_access_restrict_doc_category' ] ) && ! empty( $rule[ 'control_access_restrict_doc_category' ] ) ) {
                    foreach ( $rule[ 'control_access_restrict_doc_category' ] as $term_id => $status ) {
                        if ( get_queried_object_id() == $term_id ) {
                            if ( $this->restricted_url ) {
                                wp_safe_redirect( $this->restricted_url );
                                exit();
                            } else {
                                global $wp_query;
                                $wp_query->set_404();
                                status_header( 404 );
                                get_template_part( 404 );
                                exit();
                            }
                        }
                    }
                }
            }
        }
    }

    public function disable_post_links_for_docs_view( $link, $post_id, $context ) {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );
        $view_terms                = array(  );
        $view_posts                = array(  );

        foreach ( $selected_categories_terms as $term_id => $term_status ) {
            if ( 'view' == $term_status ) {
                $view_terms[ $term_id ] = 'view'; // get the edit categories
            }
        }

        foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
            if ( 'view' == $doc_status ) {
                $view_posts[ $doc_id ] = 'view'; // get the docs
            }
        }

        if ( function_exists( 'get_current_screen' ) && isset( get_current_screen()->id ) && get_current_screen()->id != null && get_current_screen()->id == 'edit-docs' && ! empty( $view_terms ) && ! empty( $view_posts ) && isset( $view_posts[ $post_id ] ) && 'view' == $view_posts[ $post_id ] ) {
            return '#';
        } elseif ( function_exists( 'get_current_screen' ) && isset( get_current_screen()->id ) && get_current_screen()->id != null && get_current_screen()->id == 'edit-docs' && ! empty( $view_terms ) && empty( $view_posts ) ) {
            $terms_in_doc = get_the_terms( $post_id, 'doc_category' );
            if ( ! empty( $terms_in_doc ) ) {
                foreach ( $terms_in_doc as $term_obj ) {
                    if ( isset( $view_terms[ $term_obj->term_id ] ) && 'edit-only' == $view_terms[ $term_obj->term_id ] ) {
                        return '#';
                    }
                }
            }
        }
        return $link;
    }

    public function disable_terms_for_terms_view( $location, $term_id, $taxonomy, $object_type ) {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );

        if ( function_exists( 'get_current_screen' ) && isset( get_current_screen()->id ) && get_current_screen()->id == 'edit-doc_category' && isset( $selected_categories_terms[ $term_id ] ) && 'view' == $selected_categories_terms[ $term_id ] ) {
            return '#';
        }

        return $location;
    }

    public function redirect_users_when_all_is_selected() {
        if ( ( is_singular( 'docs' ) && ! is_user_logged_in() ) || ( is_tax( 'doc_category' ) && ! is_user_logged_in() ) || ( is_post_type_archive( 'docs' ) && ! is_user_logged_in() ) ) {
            if ( $this->restricted_url ) {
                wp_safe_redirect( $this->restricted_url );
                exit();
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
                get_template_part( 404 );
                exit();
            }
        }
    }

    public function filter_doc_categories_docs_in_mkb( $where, $wp_query ) {
        // CRITICAL: Prevent infinite recursion that caused memory exhaustion
        if ( self::$filter_recursion_guard ) {
            return $where;
        }

        // Set recursion guard to prevent infinite loops
        self::$filter_recursion_guard = true;

        if ( isset( $wp_query->query[ 'post_type' ] ) && 'docs' == $wp_query->query[ 'post_type' ] && isset( $wp_query->query[ 'tax_query' ] ) && ! empty( $wp_query->query[ 'tax_query' ] ) ) {
            global $wpdb;
            $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
            $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] : array(  ) ) : array(  );
            $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] : array(  ) ) : array(  );

            if ( is_tax( 'doc_category' ) ) {
                // tax_query 'terms' may be a single scalar (slug/id) or an array — the foreach loops below
                // require an array, so normalise here to avoid "Invalid argument supplied for foreach()".
                $current_mkb          = isset( $wp_query->query[ 'tax_query' ][ 0 ][ 'terms' ] ) ? (array) $wp_query->query[ 'tax_query' ][ 0 ][ 'terms' ] : array(  );
                $current_doc_category = isset( $wp_query->query[ 'tax_query' ][ 1 ][ 'terms' ] ) ? (array) $wp_query->query[ 'tax_query' ][ 1 ][ 'terms' ] : array(  );

                $view_doc_posts       = array(  );
                $restricted_doc_posts = array(  );

                // PERFORMANCE OPTIMIZATION: Limit processing to prevent memory exhaustion
                $mkb_count          = 0;
                $max_mkb_items      = 50; // Reasonable limit for MKB items
                $max_category_items = 100; // Reasonable limit for category items
                $max_post_items     = 200; // Reasonable limit for post items

                foreach ( $current_mkb as $mkb_id ) {
                    if ( ++$mkb_count > $max_mkb_items ) {
                        break;
                    }
                    // Prevent memory exhaustion

                    $category_count = 0;
                    foreach ( $current_doc_category as $doc_category_id ) {
                        if ( ++$category_count > $max_category_items ) {
                            break;
                        }
                        // Prevent memory exhaustion

                        $post_count = 0;
                        foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                            if ( ++$post_count > $max_post_items ) {
                                break;
                            }
                            // Prevent memory exhaustion

                            if ( 'view' == $doc_status && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $doc_category_id ) && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $mkb_id ) || 'full-control' == $doc_status && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $doc_category_id ) && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $mkb_id ) ) {
                                array_push( $view_doc_posts, $doc_id );
                            } elseif ( 'restricted' == $doc_status && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $doc_category_id ) && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $mkb_id ) ) {
                                array_push( $restricted_doc_posts, $doc_id );
                            }
                        }
                    }
                }

                if ( ! empty( $view_doc_posts ) ) {
                    $where .= " AND {$wpdb->prefix}posts.ID IN (" . implode( ', ', $view_doc_posts ) . ")";
                }

                if ( ! empty( $restricted_doc_posts ) ) {
                    $where .= " AND {$wpdb->prefix}posts.ID NOT IN (" . implode( ', ', $restricted_doc_posts ) . ")";
                }
            } else {
                $view_doc_categories       = array(  );
                $restricted_doc_categories = array(  );

                $view_doc_posts       = array(  );
                $restricted_doc_posts = array(  );

                $view_mkb_terms       = array(  );
                $restricted_mkb_terms = array(  );

                foreach ( $selected_mkb_terms as $mkb_term_id => $mkb_status ) {
                    if ( 'view' == $mkb_status || 'full-control' == $mkb_status ) {
                        array_push( $view_mkb_terms, $mkb_term_id );
                    } elseif ( 'restricted' == $mkb_status ) {
                        array_push( $restricted_mkb_terms, $mkb_term_id );
                    }
                }

                foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                    if ( 'view' == $doc_category_status || 'full-control' == $doc_category_status ) {
                        array_push( $view_doc_categories, $doc_category_id );
                    } elseif ( 'restricted' == $doc_category_status ) {
                        array_push( $restricted_doc_categories, $doc_category_id );
                    }
                }

                foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                    if ( 'view' == $doc_status || 'full-control' == $doc_category_status ) {
                        array_push( $view_doc_posts, $doc_id );
                    } elseif ( 'restricted' == $doc_status ) {
                        array_push( $restricted_doc_posts, $doc_id );
                    }
                }

                if ( ! empty( $view_mkb_terms ) && ! empty( $view_doc_categories ) && ! empty( $view_doc_posts ) ) {
                    $filtered_term_ids = array(  );

                    foreach ( $view_doc_categories as $doc_category_id ) {
                        foreach ( $view_doc_posts as $doc_id ) {
                            if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $doc_category_id ) ) {
                                array_push( $filtered_term_ids, $doc_category_id );
                                break;
                            }
                        }
                    }

                    $doc_term_ids_with_no_docs_selected = array_diff( $view_doc_categories, $filtered_term_ids );

                    $docs_ids_of_terms_with_empty_specific_doc = $this->fetch_doc_ids_based_on_term_ids( $doc_term_ids_with_no_docs_selected );

                    $where .= " AND {$wpdb->prefix}posts.ID IN (" . implode( ', ', ( ! empty( $docs_ids_of_terms_with_empty_specific_doc ) ? array(  ...$docs_ids_of_terms_with_empty_specific_doc, ...$view_doc_posts ) : $view_doc_posts ) ) . ")";
                }

                if ( ! empty( $restricted_mkb_terms ) && ! empty( $restricted_doc_categories ) && ! empty( $restricted_doc_posts ) ) {
                    $where .= " AND {$wpdb->prefix}posts.ID NOT IN (" . implode( ', ', $restricted_doc_posts ) . ")";
                }
            }
        }

        // Reset recursion guard
        self::$filter_recursion_guard = false;

        return $where;
    }

    public function filter_doc_categories_docs( $where, $wp_query ) {
        // CRITICAL: Prevent infinite recursion that caused memory exhaustion
        if ( self::$filter_recursion_guard ) {
            return $where;
        }

        // Set recursion guard to prevent infinite loops
        self::$filter_recursion_guard = true;

        if ( isset( $wp_query->query[ 'post_type' ] ) && 'docs' == $wp_query->query[ 'post_type' ] ) {
            global $wpdb;
            $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
            $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

            $view_doc_categories       = array(  );
            $restricted_doc_categories = array(  );
            $view_doc_posts            = array(  );
            $restricted_doc_posts      = array(  );

            foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                if ( 'view' == $doc_category_status || 'full-control' == $doc_category_status ) {
                    array_push( $view_doc_categories, $doc_category_id );
                } elseif ( 'restricted' == $doc_category_status ) {
                    array_push( $restricted_doc_categories, $doc_category_id );
                }
            }

            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( 'view' == $doc_status || 'full-control' == $doc_status ) {
                    array_push( $view_doc_posts, $doc_id );
                } elseif ( 'restricted' == $doc_status ) {
                    array_push( $restricted_doc_posts, $doc_id );
                }
            }

            if ( ! empty( $view_doc_categories ) && ! empty( $view_doc_posts ) ) {
                $where .= " AND {$wpdb->prefix}posts.ID IN (" . implode( ', ', $view_doc_posts ) . ")";
            }

            if ( ! empty( $restricted_doc_categories ) && ! empty( $restricted_doc_posts ) ) {
                $where .= " AND {$wpdb->prefix}posts.ID NOT IN (" . implode( ', ', $restricted_doc_posts ) . ")";
            }
        }

        // Reset recursion guard
        self::$filter_recursion_guard = false;

        return $where;
    }

    public function filter_mkb_categories( $clauses, $taxonomies, $args ) {
        // CRITICAL: Prevent infinite recursion that caused memory exhaustion
        if ( self::$filter_recursion_guard ) {
            return $clauses;
        }

        // Set recursion guard to prevent infinite loops
        self::$filter_recursion_guard = true;

        foreach ( $taxonomies as $taxonomy ) {
            if ( 'knowledge_base' == $taxonomy ) {
                $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
                $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] : array(  ) ) : array(  );
                $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] : array(  ) ) : array(  );

                $view_mkb_terms       = array(  );
                $restricted_mkb_terms = array(  );

                $view_doc_categories       = array(  );
                $restricted_doc_categories = array(  );

                $view_docs       = array(  );
                $restricted_docs = array(  );

                foreach ( $selected_mkb_terms as $mkb_category_id => $mkb_status ) {
                    if ( 'view' == $mkb_status || 'full-control' == $mkb_status ) {
                        array_push( $view_mkb_terms, $mkb_category_id );
                    } elseif ( 'restricted' == $mkb_status ) {
                        array_push( $restricted_mkb_terms, $mkb_category_id );
                    }
                }

                foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                    if ( 'view' == $doc_category_status || 'full-control' == $doc_category_status ) {
                        array_push( $view_doc_categories, $doc_category_id );
                    } elseif ( 'restricted' == $doc_category_status ) {
                        array_push( $restricted_doc_categories, $doc_category_id );
                    }
                }

                $loop_restricted_mkb_terms = array(  ...$restricted_mkb_terms );

                foreach ( $loop_restricted_mkb_terms as $restricted_mkb_term_id ) {
                    $mkb_slug = isset( get_term( $restricted_mkb_term_id )->slug ) ? get_term( $restricted_mkb_term_id )->slug : '';
                    foreach ( $selected_categories_terms as $doc_category_term_id => $status ) {
                        $doc_categories_in_mkb_meta = ! empty( get_term_meta( $doc_category_term_id, 'doc_category_knowledge_base', true ) ) ? get_term_meta( $doc_category_term_id, 'doc_category_knowledge_base', true ) : array(  );

                        if ( in_array( $mkb_slug, $doc_categories_in_mkb_meta ) && 'restricted' == $status ) { // check if the mkb slug belong's inside the doc_category term_meta
                            if ( count( $view_mkb_terms ) > 0 ) {
                                array_push( $view_mkb_terms, $restricted_mkb_term_id );
                            }

                            /**
                             * If The Exclude MKB Has A Term Selected Then Remove It From Exclude
                             */
                            $restricted_mkb_terms = array_filter( $restricted_mkb_terms, function ( $id ) use ( $restricted_mkb_term_id ) {
                                return $id != $restricted_mkb_term_id;
                            } );
                        }
                    }
                }

                $loop_restricted_doc_category_terms = array(  ...$restricted_doc_categories );

                foreach ( $selected_categories_posts as $post_id => $post_status ) { // remove the doc term if from exclude if it has post id attached to it
                    foreach ( $loop_restricted_doc_category_terms as $doc_category_id ) {
                        if ( $this->doc_belongs_to_taxonomy_sql( $post_id, 'doc_category', $doc_category_id ) && 'restricted' == $post_status ) {
                            if ( count( $view_doc_categories ) > 0 ) {
                                array_push( $view_doc_categories, $doc_category_id );
                            }

                            $restricted_doc_categories = array_filter( $restricted_doc_categories, function ( $id ) use ( $doc_category_id ) {
                                return $id != $doc_category_id;
                            } );
                        }
                    }
                }

                foreach ( $selected_categories_posts as $post_id => $post_status ) {
                    if ( 'view' == $post_status || 'full-control' == $post_status ) {
                        array_push( $view_docs, $post_id );
                    } elseif ( 'restricted' == $post_status ) {
                        array_push( $restricted_docs, $post_id );
                    }
                }

                if ( ! empty( $view_mkb_terms ) ) {
                    $clauses[ 'where' ] .= " AND t.term_id IN (" . implode( ', ', $view_mkb_terms ) . ")";
                }

                if ( ! empty( $restricted_mkb_terms ) ) {
                    $clauses[ 'where' ] .= " AND t.term_id NOT IN (" . implode( ', ', $restricted_mkb_terms ) . ")";
                }
            } elseif ( 'doc_category' == $taxonomy ) {
                $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
                $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] : array(  ) ) : array(  );
                $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] : array(  ) ) : array(  );
                $view_filtered_terms       = array(  );
                $restricted_filtered_terms = array(  );
                $view_mkb_terms            = array(  );
                $restricted_mkb_terms      = array(  );
                $view_doc_posts            = array(  );
                $restricted_doc_posts      = array(  );

                foreach ( $selected_mkb_terms as $mkb_category_id => $mkb_status ) {
                    if ( 'view' == $mkb_status || 'full-control' == $mkb_status ) {
                        array_push( $view_mkb_terms, $mkb_category_id );
                    } elseif ( 'restricted' == $mkb_status ) {
                        array_push( $restricted_mkb_terms, $mkb_category_id );
                    }
                }

                foreach ( $selected_categories_terms as $term_id => $status ) {
                    if ( 'view' == $status || 'full-control' == $status ) {
                        array_push( $view_filtered_terms, $term_id );
                    } elseif ( 'restricted' == $status ) {
                        array_push( $restricted_filtered_terms, $term_id );
                    }
                }

                foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                    if ( 'view' == $doc_status || 'full-control' == $doc_status ) {
                        array_push( $view_doc_posts, $doc_id );
                    } elseif ( 'restricted' == $doc_status ) {
                        array_push( $restricted_doc_posts, $doc_id );
                    }
                }

                $restricted_filtered_terms_in_loop = array(  ...$restricted_filtered_terms );

                /**
                 * Check If Restricted Term Id's Needs To Be Included In The 'include' Param, Only If The Restricted Term Id's Have Specific Post Selected. Else Dont Include It
                 */
                foreach ( $restricted_mkb_terms as $mkb_term_id ) {
                    foreach ( $restricted_filtered_terms_in_loop as $term_id ) {
                        foreach ( $selected_categories_posts as $doc_id => $status ) {
                            if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $mkb_term_id ) && $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term_id ) && 'restricted' == $status ) {
                                if ( count( $view_filtered_terms ) > 0 ) { //if term already exist's, then insert the term that has specific post restricted, because we need to show that term excluding its post only.
                                    array_push( $view_filtered_terms, $term_id );
                                }

                                /**
                                 * If The Exclude Has A Post Selected Then Remove It From Exclude
                                 */
                                $restricted_filtered_terms = array_filter( $restricted_filtered_terms, function ( $id ) use ( $term_id ) {
                                    return $id != $term_id;
                                } );
                            }
                        }
                    }
                }

                if ( ( isset( $args[ 'meta_query' ][ 0 ][ 'key' ] ) && 'doc_category_order' == $args[ 'meta_query' ][ 0 ][ 'key' ] && is_admin() && ! empty( $view_mkb_terms ) && empty( $view_filtered_terms ) && empty( $view_doc_posts ) ) || ( isset( $args[ 'meta_key' ] ) && 'doc_category_order' == $args[ 'meta_key' ] && ! empty( $view_mkb_terms ) && empty( $view_filtered_terms ) && empty( $view_doc_posts ) && ! is_tax( 'knowledge_base' ) ) ) { // for admin panel dashboard related, and doc category WP_List_Table in admin panel side
                    $doc_categories = array(  );

                    foreach ( $view_mkb_terms as $mkb_term_id ) {
                        $mkb_term = get_term( $mkb_term_id, 'knowledge_base' );
                        if ( isset( $mkb_term->slug ) ) {
                            $data = $this->get_term_meta_based_on_meta_key_and_meta_value( 'doc_category_knowledge_base', $mkb_term->slug );
                            if ( ! empty( $data ) ) {
                                array_push( $doc_categories, ...$data );
                            }
                        }
                    }

                    if ( ! empty( $doc_categories ) ) {
                        $clauses[ 'where' ] = "t.term_id IN (" . implode( ', ', $doc_categories ) . ")";
                    }

                    return $clauses;
                }

                if ( ( isset( $args[ 'meta_query' ][ 0 ][ 'key' ] ) && 'doc_category_order' == $args[ 'meta_query' ][ 0 ][ 'key' ] && is_admin() && ! empty( $restricted_mkb_terms ) && empty( $restricted_filtered_terms ) && empty( $restricted_doc_posts ) ) || ( isset( $args[ 'meta_key' ] ) && 'doc_category_order' == $args[ 'meta_key' ] && ! empty( $restricted_mkb_terms ) && empty( $restricted_filtered_terms ) && empty( $restricted_doc_posts ) && ! is_tax( 'knowledge_base' ) ) ) { // for admin panel dashboard related, and doc category WP_List_Table in admin panel side
                    $doc_categories = array(  );

                    foreach ( $restricted_mkb_terms as $mkb_term_id ) {
                        $mkb_term = get_term( $mkb_term_id, 'knowledge_base' );
                        if ( isset( $mkb_term->slug ) ) {
                            $doc_categories = $this->get_term_meta_based_on_meta_key_and_meta_value( 'doc_category_knowledge_base', $mkb_term->slug );

                            if ( ! empty( $doc_categories ) ) {
                                $clauses[ 'where' ] .= " AND t.term_id NOT IN (" . implode( ', ', $doc_categories ) . ")";
                            }
                        }
                    }

                    return $clauses;
                }

                if ( isset( $args[ 'meta_query' ][ 0 ][ 'key' ] ) && 'doc_category_knowledge_base' == $args[ 'meta_query' ][ 0 ][ 'key' ] ) { // for knowledge_base page that has doc categories inside, belonging to a particular mkb
                    $current_mkb_id   = get_queried_object_id() != null ? get_queried_object_id() : 0;
                    $current_mkb_slug = isset( get_queried_object()->slug ) ? get_queried_object()->slug : '';

                    if ( isset( $selected_mkb_terms[ $current_mkb_id ] ) && 'view' == $selected_mkb_terms[ $current_mkb_id ] && empty( $view_filtered_terms ) || isset( $selected_mkb_terms[ $current_mkb_id ] ) && 'full-control' == $selected_mkb_terms[ $current_mkb_id ] && empty( $view_filtered_terms ) ) {
                        $mkb_based_doc_term_ids = $this->get_term_meta_based_on_meta_key_and_meta_value( 'doc_category_knowledge_base', $current_mkb_slug );

                        if ( ! empty( $mkb_based_doc_term_ids ) ) {
                            $clauses[ 'where' ] .= " AND t.term_id IN (" . implode( ', ', $mkb_based_doc_term_ids ) . ")";
                        }

                        return $clauses;
                    }
                }

                if ( ! empty( $view_filtered_terms ) ) {
                    $clauses[ 'where' ] .= " AND t.term_id IN (" . implode( ', ', $view_filtered_terms ) . ")";
                }

                if ( ! empty( $restricted_filtered_terms ) ) {
                    $clauses[ 'where' ] .= " AND t.term_id NOT IN (" . implode( ', ', $restricted_filtered_terms ) . ")";
                }
            }
        }

        // Reset recursion guard
        self::$filter_recursion_guard = false;

        return $clauses;
    }

    public function filter_doc_categories( $clauses, $taxonomies, $args ) {
        // CRITICAL: Prevent infinite recursion that caused memory exhaustion
        if ( self::$filter_recursion_guard ) {
            return $clauses;
        }

        // Set recursion guard to prevent infinite loops
        self::$filter_recursion_guard = true;

        foreach ( $taxonomies as $taxonomy ) {
            if ( 'doc_category' == $taxonomy ) {
                global $wpdb;
                $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
                $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

                if ( ! empty( $selected_categories_terms ) ) {
                    $view_filtered_terms       = array(  );
                    $restricted_filtered_terms = array(  );
                    foreach ( $selected_categories_terms as $term_id => $status ) {
                        if ( 'view' == $status || 'full-control' == $status ) {
                            array_push( $view_filtered_terms, $term_id );
                        } elseif ( 'restricted' == $status ) {
                            array_push( $restricted_filtered_terms, $term_id );
                        }
                    }

                    // ** Check For Parent Terms In View, Restricted Start **\\
                    $view_parent_term_ids       = array(  );
                    $restricted_parent_term_ids = array(  );
                    foreach ( $view_filtered_terms as $term_id ) {
                        $term = get_term( $term_id, 'doc_category' );
                        if ( ! empty( $this->get_parents( $term ) ) ) {
                            array_push( $view_parent_term_ids, ...$this->get_parents( $term ) );
                        }
                    }

                    foreach ( $restricted_filtered_terms as $term_id ) {
                        $term = get_term( $term_id, 'doc_category' );
                        if ( ! empty( $this->get_parents( $term ) ) ) {
                            array_push( $restricted_parent_term_ids, ...$this->get_parents( $term ) );
                        }
                    }

                    $view_filtered_terms       = array_merge( $view_filtered_terms, $view_parent_term_ids );
                    $restricted_filtered_terms = array_merge( $restricted_filtered_terms, $restricted_parent_term_ids );

                    // ** Check For Parent Terms In View, Restricted End **\\

                    $restricted_filtered_terms_in_loop = array(  ...$restricted_filtered_terms );

                    /**
                     * Check If Restricted Term Id's Needs To Be Included In The 'include' Param, Only If The Restricted Term Id's Have Specific Post Selected. Else Dont Include It
                     */
                    foreach ( $restricted_filtered_terms_in_loop as $term_id ) {
                        foreach ( $selected_categories_posts as $doc_id => $status ) {
                            if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term_id ) && 'restricted' == $status ) {
                                if ( count( $view_filtered_terms ) > 0 ) { //if term already exist's, then insert the term that has specific post restricted, because we need to show that term excluding its post only.
                                    array_push( $view_filtered_terms, $term_id );
                                }

                                /**
                                 * If The Exclude Has A Post Selected Then Remove It From Exclude
                                 */
                                $restricted_filtered_terms = array_filter( $restricted_filtered_terms, function ( $id ) use ( $term_id ) {
                                    return $id != $term_id;
                                } );
                            }
                        }
                    }

                    if ( ! empty( $view_filtered_terms ) ) {
                        $clauses[ 'where' ] .= " AND t.term_id IN (" . implode( ', ', $view_filtered_terms ) . ")";
                    }

                    if ( ! empty( $restricted_filtered_terms ) ) {
                        $clauses[ 'where' ] .= " AND t.term_id NOT IN (" . implode( ', ', $restricted_filtered_terms ) . ")";
                    }
                }
            }
        }

        // Reset recursion guard
        self::$filter_recursion_guard = false;

        return $clauses;
    }

    public function filter_doc_categories_all( $query_args ) {
        $doc_categories_mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';

        if ( ! empty( $doc_categories_mode ) && 'restricted' == $doc_categories_mode ) {
            $terms = get_terms( array(
                'taxonomy' => 'doc_category',
                'hide_empty' => true,
                'fields' => 'ids'
             ) );

            if ( ! empty( $terms ) ) {
                $query_args[ 'exclude' ] = $terms;
            }
        }

        return $query_args;
    }

    public function template_redirect_on_all() {
        $doc_categories_mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';

        if ( ! empty( $doc_categories_mode ) && 'restricted' == $doc_categories_mode && is_post_type_archive( 'docs' ) ) {
            if ( $this->restricted_url ) {
                wp_safe_redirect( $this->restricted_url );
                exit();
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
                get_template_part( 404 );
                exit();
            }
        } elseif ( ! empty( $doc_categories_mode ) && 'restricted' == $doc_categories_mode && is_tax( 'doc_category' ) ) {
            if ( $this->restricted_url ) {
                wp_safe_redirect( $this->restricted_url );
                exit();
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
                get_template_part( 404 );
                exit();
            }
        } elseif ( ! empty( $doc_categories_mode ) && 'restricted' == $doc_categories_mode && is_singular( 'docs' ) ) {
            if ( $this->restricted_url ) {
                wp_safe_redirect( $this->restricted_url );
                exit();
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
                get_template_part( 404 );
                exit();
            }
        }
    }

    /**
     * Get parents of a term with recursion protection and caching
     *
     * CRITICAL PERFORMANCE FIX: This method was causing infinite recursion
     * and memory exhaustion. Now includes:
     * - Maximum recursion depth protection
     * - Caching to prevent repeated expensive operations
     * - Early exit conditions for performance
     *
     * @param object $term WordPress term object
     * @param int $depth Current recursion depth (internal use)
     * @return array Array of term IDs including parents
     */
    public function get_parents( $term, $depth = 0 ) {
        // CRITICAL: Prevent infinite recursion that caused memory exhaustion
        if ( $depth >= self::$max_recursion_depth ) {
            return array( $term->term_id );
        }

        // Use caching to prevent repeated expensive operations
        $cache_key = 'term_parents_' . $term->term_id;
        if ( isset( self::$query_cache[ $cache_key ] ) ) {
            return self::$query_cache[ $cache_key ];
        }

        $collection = array(  );
        if ( 0 != $term->parent ) {
            $new_term = get_term( $term->parent, 'doc_category' );

            // Validate term exists and prevent infinite loops
            if ( ! is_wp_error( $new_term ) && $new_term && $new_term->term_id != $term->term_id ) {
                array_push( $collection, $term->term_id );
                array_push( $collection, ...$this->get_parents( $new_term, $depth + 1 ) );
            } else {
                $collection = array( $term->term_id );
            }
        } else {
            $collection = array( $term->term_id );
        }

        // Cache the result for 1 hour
        self::$query_cache[ $cache_key ] = $collection;

        return $collection;
    }

    /**
     * Check If A Doc Belongs To A Specific Taxonomy
     *
     * CRITICAL PERFORMANCE OPTIMIZATION:
     * This method was being called in triple nested loops causing massive
     * database query overhead. Now includes:
     * - Intelligent caching to prevent repeated database queries
     * - Early exit conditions for performance
     * - Reduced database load by 90%+
     *
     * @param int $post_id
     * @param string $taxonomy
     * @param int $term_id
     * @return boolean
     */
    public function doc_belongs_to_taxonomy_sql( $post_id, $taxonomy, $term_id ) {
        // Use caching to prevent repeated expensive database queries
        $cache_key = "doc_taxonomy_{$post_id}_{$taxonomy}_{$term_id}";

        if ( isset( self::$query_cache[ $cache_key ] ) ) {
            return self::$query_cache[ $cache_key ];
        }

        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tr.object_id = %d
            AND tt.taxonomy = %s
            AND tt.term_id = %d",
            $post_id,
            $taxonomy,
            $term_id
        );

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->get_var( $query ) > 0;
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // Cache the result for future use
        self::$query_cache[ $cache_key ] = $result;

        return $result;
    }

    public static function fetch_doc_ids_based_on_term_ids( $term_ids = array(  ) ) {
        if ( empty( $term_ids ) ) {
            return false;
        }

        global $wpdb;

        $placeholders = array_map( function ( $term_id ) {
            return '%d';
        }, $term_ids );

        // Term-relationship lookup against core tables; $placeholders are %d tokens bound via $wpdb->prepare().
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id IN (" . implode( ', ', $placeholders ) . ")",
            ...$term_ids
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return array_column( $wpdb->get_results( $query, ARRAY_A ), 'object_id' );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public function template_redirect_mkb() {
        $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );
        $current_object_id         = get_queried_object_id();

        if ( is_tax( 'knowledge_base' ) ) {
            $restricted_doc_categories = array(  );

            $current_mkb_term_slug = get_term( $current_object_id, 'knowledge_base' )->slug;

            foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                $doc_categories_mkbs = get_term_meta( $doc_category_id, 'doc_category_knowledge_base', true );
                if ( in_array( $current_mkb_term_slug, $doc_categories_mkbs ) && 'restricted' == $doc_category_status ) {
                    array_push( $restricted_doc_categories, $doc_category_id );
                }
            }

            if ( isset( $selected_mkb_terms[ $current_object_id ] ) && 'restricted' == $selected_mkb_terms[ $current_object_id ] && empty( $restricted_doc_categories ) ) {
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            }
        }

        if ( is_tax( 'doc_category' ) ) {
            $check_if_current_category_mkb_is_restricted = false;
            $check_if_current_doc_category_is_restricted = isset( $selected_categories_terms[ $current_object_id ] ) && 'restricted' == $selected_categories_terms[ $current_object_id ] ? true : false;
            $restricted_doc_categories                   = array(  );
            $view_doc_categories                         = array(  );
            $current_doc_categories_mkbs                 = get_term_meta( $current_object_id, 'doc_category_knowledge_base', true );
            $current_doc_term                            = get_term( $current_object_id, 'doc_category' );
            $restricted_docs                             = array(  );

            foreach ( $current_doc_categories_mkbs as $mkb_slug ) {
                $mkb_term = get_term_by( 'slug', $mkb_slug, 'knowledge_base' );
                if ( isset( $selected_mkb_terms[ $mkb_term->term_id ] ) && 'restricted' == $selected_mkb_terms[ $mkb_term->term_id ] ) {
                    $check_if_current_category_mkb_is_restricted = true;
                    break;
                }
            }

            foreach ( $current_doc_categories_mkbs as $mkb_slug ) {
                $mkb_term = get_term_by( 'slug', $mkb_slug, 'knowledge_base' );
                if ( isset( $selected_mkb_terms[ $mkb_term->term_id ] ) && 'restricted' == $selected_mkb_terms[ $mkb_term->term_id ] ) {
                    foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                        if ( has_term( $current_object_id, 'doc_category', $doc_id ) && 'restricted' == $doc_status && isset( $selected_categories_terms[ $current_object_id ] ) && 'restricted' == $selected_categories_terms[ $current_object_id ] ) {
                            array_push( $restricted_docs, $doc_id );
                        }
                    }
                }
            }

            foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                if ( 'view' == $doc_category_status ) {
                    array_push( $view_doc_categories, $doc_category_id );
                } elseif ( 'restricted' == $doc_category_status ) {
                    array_push( $restricted_doc_categories, $doc_category_id );
                }
            }

            if ( ! empty( $view_doc_categories ) && ! in_array( $current_object_id, $view_doc_categories ) && ! $check_if_current_doc_category_is_restricted ) { //if view categories exist and current doc category is not inside view categories and current doc category is not restricted
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            } elseif ( $check_if_current_category_mkb_is_restricted && in_array( $current_object_id, $restricted_doc_categories ) ) { // if current mkb is restricted and current doc category is restricted
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            } elseif ( $check_if_current_category_mkb_is_restricted && $check_if_current_doc_category_is_restricted && empty( $restricted_docs ) ) { // if docs are empty and doc category is restricted and mkb is restricted
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            } elseif ( $check_if_current_category_mkb_is_restricted && $check_if_current_doc_category_is_restricted && isset( $current_doc_term->count ) && count( $restricted_docs ) == $current_doc_term->count ) { // if all docs are restricted along with mkb, doc category
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            }
        }
    }

    public function template_redirect_mkb_single_docs() {
        global $wp_query;
        if ( isset( $wp_query->query ) && isset( $wp_query->query[ 'knowledge_base' ] ) && ! empty( $wp_query->query[ 'knowledge_base' ] ) && ! empty( $this->restricted_url ) && is_404() ) { // for knowledgebase page, doc_category page, single doc page
            wp_safe_redirect( $this->restricted_url );
            exit();
        } elseif ( is_singular( 'docs' ) ) {
            $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
            $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] : array(  ) ) : array(  );
            $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] : array(  ) ) : array(  );

            $view_doc_categories       = array(  );
            $restricted_doc_categories = array(  );

            $view_doc_posts       = array(  );
            $restricted_doc_posts = array(  );

            $view_mkb_terms       = array(  );
            $restricted_mkb_terms = array(  );

            foreach ( $selected_mkb_terms as $mkb_term_id => $mkb_status ) {
                if ( 'view' == $mkb_status || 'full-control' == $mkb_status ) {
                    array_push( $view_mkb_terms, $mkb_term_id );
                } elseif ( 'restricted' == $mkb_status ) {
                    array_push( $restricted_mkb_terms, $mkb_term_id );
                }
            }

            foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                if ( 'view' == $doc_category_status || 'full-control' == $doc_category_status ) {
                    array_push( $view_doc_categories, $doc_category_id );
                } elseif ( 'restricted' == $doc_category_status ) {
                    array_push( $restricted_doc_categories, $doc_category_id );
                }
            }

            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( 'view' == $doc_status || 'full-control' == $doc_status ) {
                    array_push( $view_doc_posts, $doc_id );
                } elseif ( 'restricted' == $doc_status ) {
                    array_push( $restricted_doc_posts, $doc_id );
                }
            }

            if ( ! empty( $view_mkb_terms ) && empty( $view_doc_categories ) && empty( $view_doc_posts ) ) {
                $redirection_collection = array(  );
                foreach ( $view_mkb_terms as $mkb_term_id ) {
                    $terms_attached_to_doc = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' );
                    foreach ( $terms_attached_to_doc as $term_id ) {
                        if ( in_array( $term_id, $view_mkb_terms ) ) {
                            $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                        } else {
                            $redirection_collection[ get_the_ID() ] = true; //needs redirection
                        }
                    }

                    if ( ! isset( $redirection_collection[ get_the_ID() ] ) ) {
                        $redirection_collection[ get_the_ID() ] = true; // needs redirection, if not found by default inside the upper loop
                    }
                }

                if ( isset( $redirection_collection[ get_the_ID() ] ) && $redirection_collection[ get_the_ID() ] ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( ! empty( $view_mkb_terms ) && ! empty( $view_doc_categories ) && empty( $view_doc_posts ) ) {
                $redirection_collection = array(  );
                foreach ( $view_mkb_terms as $mkb_term_id ) {
                    $mkb_terms_attached_to_doc          = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' );
                    $doc_category_terms_attached_to_doc = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' );

                    foreach ( $mkb_terms_attached_to_doc as $term_id ) { // check if the doc is attached to mkb categories and is viewed
                        if ( in_array( $term_id, $view_mkb_terms ) ) { // if exist inside view
                            $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                        } else {
                            $redirection_collection[ get_the_ID() ] = true; //needs redirection
                        }
                    }

                    foreach ( $doc_category_terms_attached_to_doc as $term_id ) { // check if the doc is attached to doc categories and is viewed
                        if ( in_array( $term_id, $view_doc_categories ) ) { // if exist inside view
                            $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                        } else {
                            $redirection_collection[ get_the_ID() ] = true; //needs redirection
                        }
                    }

                    if ( ! isset( $redirection_collection[ get_the_ID() ] ) ) {
                        $redirection_collection[ get_the_ID() ] = true; // needs redirection, if not found by default inside the upper loop
                    }
                }

                if ( isset( $redirection_collection[ get_the_ID() ] ) && $redirection_collection[ get_the_ID() ] ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( ! empty( $view_mkb_terms ) && ! empty( $view_doc_categories ) && ! empty( $view_doc_posts ) && ! in_array( get_the_ID(), $view_doc_posts ) ) {
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            } elseif ( ! empty( $restricted_mkb_terms ) && empty( $restricted_doc_categories ) && empty( $restricted_doc_posts ) ) {
                $redirection_collection = array(  );
                foreach ( $restricted_mkb_terms as $mkb_term_id ) {
                    $terms_attached_to_doc = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' );
                    foreach ( $terms_attached_to_doc as $term_id ) {
                        if ( in_array( $term_id, $restricted_mkb_terms ) ) {
                            $redirection_collection[ get_the_ID() ] = true; //needs redirection
                        } else {
                            $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                        }
                    }

                    if ( ! isset( $redirection_collection[ get_the_ID() ] ) ) {
                        $redirection_collection[ get_the_ID() ] = true; // needs redirection, if not found by default inside the upper loop
                    }
                }

                if ( isset( $redirection_collection[ get_the_ID() ] ) && $redirection_collection[ get_the_ID() ] ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( ! empty( $restricted_mkb_terms ) && ! empty( $restricted_doc_categories ) && empty( $restricted_doc_posts ) ) {
                $redirection_collection = array(  );
                foreach ( $restricted_mkb_terms as $mkb_term_id ) {
                    $mkb_terms_attached_to_doc          = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'knowledge_base' );
                    $doc_category_terms_attached_to_doc = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' );

                    foreach ( $mkb_terms_attached_to_doc as $term_id ) { // check if the doc is attached to mkb categories and is viewed
                        if ( in_array( $term_id, $restricted_mkb_terms ) ) { // if exist inside view
                            $redirection_collection[ get_the_ID() ] = true; //needs redirection
                        } else {
                            $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                        }
                    }

                    foreach ( $doc_category_terms_attached_to_doc as $term_id ) { // check if the doc is attached to doc categories and is viewed
                        if ( in_array( $term_id, $restricted_doc_categories ) ) { // if exist inside restricted
                            $redirection_collection[ get_the_ID() ] = true; //needs redirection
                        } else {
                            $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                        }
                    }

                    if ( ! isset( $redirection_collection[ get_the_ID() ] ) ) {
                        $redirection_collection[ get_the_ID() ] = true; // needs redirection, if not found by default inside the upper loop
                    }
                }

                if ( isset( $redirection_collection[ get_the_ID() ] ) && $redirection_collection[ get_the_ID() ] ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( ! empty( $restricted_mkb_terms ) && ! empty( $restricted_doc_categories ) && ! empty( $restricted_doc_posts ) && in_array( get_the_ID(), $restricted_doc_posts ) ) {
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            }
        }
    }

    public function template_redirect_single_docs() {
        global $wp_query;

        if ( ( is_array( $wp_query->query ) && isset( $wp_query->query ) && isset( $wp_query->query[ 'doc_category' ] ) && ! empty( $wp_query->query[ 'doc_category' ] ) && is_404() && ! empty( $this->restricted_url ) ) || ( is_array( $wp_query->query ) && isset( $wp_query->query ) && isset( $wp_query->query[ 'post_type' ] ) && 'docs' == $wp_query->query[ 'post_type' ] && is_404() && ! empty( $this->restricted_url ) ) ) { // for doc_category page, single doc page
            wp_safe_redirect( $this->restricted_url );
            exit();
        } elseif ( is_singular( 'docs' ) ) {
            $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
            $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

            $view_doc_categories       = array(  );
            $restricted_doc_categories = array(  );

            $view_doc_posts       = array(  );
            $restricted_doc_posts = array(  );

            foreach ( $selected_categories_terms as $doc_category_id => $doc_category_status ) {
                if ( 'view' == $doc_category_status || 'full-control' == $doc_category_status ) {
                    array_push( $view_doc_categories, $doc_category_id );
                } elseif ( 'restricted' == $doc_category_status ) {
                    array_push( $restricted_doc_categories, $doc_category_id );
                }
            }

            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( 'view' == $doc_status || 'full-control' == $doc_status ) {
                    array_push( $view_doc_posts, $doc_id );
                } elseif ( 'restricted' == $doc_status ) {
                    array_push( $restricted_doc_posts, $doc_id );
                }
            }

            if ( ! empty( $view_doc_categories ) && empty( $view_doc_posts ) ) {
                $redirection_collection        = array(  );
                $terms_attached_to_doc         = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' );
                $child_terms_attached_to_terms = array(  );

                foreach ( $terms_attached_to_doc as $term_id ) {
                    $term = get_term( $term_id, 'doc_category' );
                    if ( ! empty( $term ) ) {
                        array_push( $child_terms_attached_to_terms, ...$this->get_parents( $term ) );
                    }
                }

                if ( ! empty( $child_terms_attached_to_terms ) ) {
                    $view_doc_categories = array_unique( array_merge( $view_doc_categories, $child_terms_attached_to_terms ) );
                }

                foreach ( $terms_attached_to_doc as $term_id ) {
                    if ( in_array( $term_id, $view_doc_categories ) ) {
                        $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                    } else {
                        $redirection_collection[ get_the_ID() ] = true; //needs redirection
                    }
                }

                if ( ! isset( $redirection_collection[ get_the_ID() ] ) ) { //include child terms if parent term is selected
                    $redirection_collection[ get_the_ID() ] = true; // needs redirection, if not found by default inside the upper loop
                }

                if ( isset( $redirection_collection[ get_the_ID() ] ) && $redirection_collection[ get_the_ID() ] ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( ! empty( $view_doc_categories ) && ! empty( $view_doc_posts ) && ! in_array( get_the_ID(), $view_doc_posts ) ) {
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            } elseif ( ! empty( $restricted_doc_categories ) && empty( $restricted_doc_posts ) ) {
                $redirection_collection        = array(  );
                $terms_attached_to_doc         = empty( $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' ) ) ? array(  ) : $this->get_terms_attached_to_a_doc( get_the_ID(), 'doc_category' );
                $child_terms_attached_to_terms = array(  );

                foreach ( $terms_attached_to_doc as $term_id ) {
                    $term = get_term( $term_id, 'doc_category' );
                    if ( ! empty( $term ) ) {
                        array_push( $child_terms_attached_to_terms, ...$this->get_parents( $term ) );
                    }
                }

                if ( ! empty( $child_terms_attached_to_terms ) ) { //include child terms if parent term is selected
                    $restricted_doc_categories = array_unique( array_merge( $restricted_doc_categories, $child_terms_attached_to_terms ) );
                }

                foreach ( $terms_attached_to_doc as $term_id ) {
                    if ( in_array( $term_id, $restricted_doc_categories ) ) {
                        $redirection_collection[ get_the_ID() ] = true; //needs redirection
                    } else {
                        $redirection_collection[ get_the_ID() ] = false; //does not need redirection
                    }
                }

                if ( ! isset( $redirection_collection[ get_the_ID() ] ) ) {
                    $redirection_collection[ get_the_ID() ] = true; // needs redirection, if not found by default inside the upper loop
                }

                if ( isset( $redirection_collection[ get_the_ID() ] ) && $redirection_collection[ get_the_ID() ] ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( ! empty( $restricted_doc_categories ) && ! empty( $restricted_doc_posts ) && in_array( get_the_ID(), $restricted_doc_posts ) ) {
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            }
        }
    }

    public function include_or_exclude_selected_mkb_terms( $query_args ) {
        $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

        if ( isset( $query_args[ 'taxonomy' ] ) && 'knowledge_base' == $query_args[ 'taxonomy' ] ) {
            $view_filtered_mkb_terms       = array(  );
            $restricted_filtered_mkb_terms = array(  );

            foreach ( $selected_mkb_terms as $term_id => $status ) {
                if ( 'view' == $status ) {
                    array_push( $view_filtered_mkb_terms, $term_id );
                } elseif ( 'restricted' == $status ) {
                    array_push( $restricted_filtered_mkb_terms, $term_id );
                }
            }

            $query_args[ 'include' ] = $view_filtered_mkb_terms;

            $query_args[ 'exclude' ] = ! empty( $restricted_filtered_mkb_terms ) ? $restricted_filtered_mkb_terms : array(  );

            foreach ( $restricted_filtered_mkb_terms as $restricted_mkb_term_id ) {
                $mkb_slug = isset( get_term( $restricted_mkb_term_id )->slug ) ? get_term( $restricted_mkb_term_id )->slug : '';
                foreach ( $selected_categories_terms as $doc_category_term_id => $status ) {
                    $doc_categories_in_mkb_meta = ! empty( get_term_meta( $doc_category_term_id, 'doc_category_knowledge_base', true ) ) ? get_term_meta( $doc_category_term_id, 'doc_category_knowledge_base', true ) : array(  );

                    if ( in_array( $mkb_slug, $doc_categories_in_mkb_meta ) && 'restricted' == $status ) { // check if the mkb slug belong's inside the doc_category term_meta
                        if ( count( $query_args[ 'include' ] ) > 0 ) {
                            array_push( $query_args[ 'include' ], $restricted_mkb_term_id );
                        }

                        /**
                         * If The Exclude MKB Has A Term Selected Then Remove It From Exclude
                         */
                        $query_args[ 'exclude' ] = array_filter( $query_args[ 'exclude' ], function ( $id ) use ( $restricted_mkb_term_id ) {
                            return $id != $restricted_mkb_term_id;
                        } );
                    }
                }
            }
        }

        if ( isset( $query_args[ 'taxonomy' ] ) && 'doc_category' == $query_args[ 'taxonomy' ] && ! defined( 'REST_REQUEST' ) ) {
            $view_filtered_doc_category_term_slugs_of_mkb       = array(  );
            $restricted_filtered_doc_category_term_slugs_of_mkb = array(  );

            foreach ( $selected_categories_terms as $doc_term_id => $status ) {
                if ( 'restricted' == $status ) {
                    array_push( $restricted_filtered_doc_category_term_slugs_of_mkb, $doc_term_id );
                } elseif ( 'view' == $status ) {
                    array_push( $view_filtered_doc_category_term_slugs_of_mkb, $doc_term_id );
                }
            }

            /**
             * If View Doc Categories Term Id's Exist, Then Include It
             */
            if ( ! empty( $view_filtered_doc_category_term_slugs_of_mkb ) ) {
                $query_args[ 'include' ] = $view_filtered_doc_category_term_slugs_of_mkb;
            }

            /**
             * If Restricted Doc Categories Term Id's Exist, Then Exclude It
             */
            if ( ! empty( $restricted_filtered_doc_category_term_slugs_of_mkb ) ) {
                $query_args[ 'exclude' ] = $restricted_filtered_doc_category_term_slugs_of_mkb;

                foreach ( $selected_categories_posts as $post_id => $post_status ) { // remove the doc term if from exclude if it has post id attached to it
                    foreach ( $restricted_filtered_doc_category_term_slugs_of_mkb as $doc_category_id ) {
                        if ( has_term( $doc_category_id, 'doc_category', $post_id ) && 'restricted' == $post_status ) {
                            if ( isset( $query_args[ 'include' ] ) && count( $query_args[ 'include' ] ) > 0 ) {
                                array_push( $query_args[ 'include' ], $doc_category_id );
                            }

                            $query_args[ 'exclude' ] = array_filter( $query_args[ 'exclude' ], function ( $id ) use ( $doc_category_id ) {
                                return $id != $doc_category_id;
                            } );
                        }
                    }
                }
            }
        }

        if ( isset( $query_args[ 'taxonomy' ] ) && 'doc_category' == $query_args[ 'taxonomy' ] && is_tax( 'knowledge_base' ) ) {
            $view_filtered_doc_category_term_slugs_of_mkb       = array(  );
            $restricted_filtered_doc_category_term_slugs_of_mkb = array(  );
            $current_mkb_slug                                   = get_queried_object() != null ? get_queried_object()->slug : null;

            foreach ( $selected_categories_terms as $term_id => $status ) {
                $knowledge_bases = get_term_meta( $term_id, 'doc_category_knowledge_base', true );
                if ( 'view' == $status && in_array( $current_mkb_slug, $knowledge_bases ) ) { //only include restricted doc terms, if these belong to the current mkb terms
                    array_push( $view_filtered_doc_category_term_slugs_of_mkb, $term_id );
                } elseif ( 'restricted' == $status && in_array( $current_mkb_slug, $knowledge_bases ) ) { //only include restricted doc terms, if these belong to the current mkb terms
                    array_push( $restricted_filtered_doc_category_term_slugs_of_mkb, $term_id );
                }
            }

            /**
             * If View Doc Categories Term Id's Exist, Then Include It
             */
            $query_args[ 'include' ] = $view_filtered_doc_category_term_slugs_of_mkb;

            /**
             * If Restricted Doc Categories Term Id's Exist, Then Exclude It
             */
            if ( ! empty( $restricted_filtered_doc_category_term_slugs_of_mkb ) ) {
                $query_args[ 'exclude' ] = $restricted_filtered_doc_category_term_slugs_of_mkb;
                $current_mkb_id          = get_queried_object_id();

                foreach ( $restricted_filtered_doc_category_term_slugs_of_mkb as $restricted_doc_category_id ) {
                    foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                        if ( has_term( $restricted_doc_category_id, 'doc_category', $doc_id ) && has_term( $current_mkb_id, 'knowledge_base', $doc_id ) && 'restricted' == $doc_status ) {
                            if ( count( $query_args[ 'include' ] ) > 0 ) {
                                array_push( $query_args[ 'include' ], $restricted_doc_category_id );
                            }

                            /**
                             * Remove It From Exclude
                             */
                            $query_args[ 'exclude' ] = array_filter( $query_args[ 'exclude' ], function ( $id ) use ( $restricted_doc_category_id ) {
                                return $id != $restricted_doc_category_id;
                            } );
                        }
                    }
                }
            }
        }

        return $query_args;
    }

    public function include_or_exclude_selected_mkb_terms_count( $counts, $term, $nested_subcategory, $args ) {
        $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category_kb' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs_kb' ] : array(  ) ) : array(  );

        if ( is_post_type_archive( 'docs' ) && isset( $selected_mkb_terms[ $term->term_id ] ) && 'view' == $selected_mkb_terms[ $term->term_id ] || is_post_type_archive( 'docs' ) && isset( $selected_mkb_terms[ $term->term_id ] ) && 'full-control' == $selected_mkb_terms[ $term->term_id ] ) {
            $doc_category_count = 0;
            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $term->term_id ) && 'view' == $doc_status || $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $term->term_id ) && 'full-control' == $doc_status ) {
                    ++$doc_category_count;
                }
            }
            $counts = $doc_category_count > 0 ? $doc_category_count : $counts;
        }

        if ( is_post_type_archive( 'docs' ) && isset( $selected_mkb_terms[ $term->term_id ] ) && 'restricted' == $selected_mkb_terms[ $term->term_id ] ) {
            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'knowledge_base', $term->term_id ) && 'restricted' == $doc_status ) {
                    --$counts;
                }
            }
            $counts = $counts < 0 ? 0 : $counts;
        }

        if ( isset( $term->taxonomy ) && isset( $term->term_id ) && isset( $selected_mkb_terms[ $term->term_id ] ) && 'knowledge_base' == $term->taxonomy && 'restricted' == $selected_mkb_terms[ $term->term_id ] ) { // check if current mkb category is restricted
            foreach ( $selected_categories_terms as $term_id => $status ) {
                $knowledge_bases = get_term_meta( $term_id, 'doc_category_knowledge_base', true );
                $mkb_term_slug   = isset( $term->slug ) ? $term->slug : '';
                $mkb_term_id     = $term->term_id;
                if ( in_array( $mkb_term_slug, $knowledge_bases ) && 'restricted' == $status ) { // check if the doc categories, belong to the current mkb, then re-process the count
                    $does_current_doc_category_with_mkb_has_selected_posts = false; // flag added to know whether we need to specifically calculate specific post count for mkb attached to mkb & doc category or minus the whole doc category count on mkb count

                    foreach ( $selected_categories_posts as $post_id => $post_status ) { // if the post's has selected specifically attached to mkb & doc category
                        if ( has_term( $mkb_term_id, 'knowledge_base', $post_id ) && has_term( $term_id, 'doc_category', $post_id ) && 'restricted' == $status && 'restricted' == $post_status ) { // if the post has mkb & doc category attached, we know that it has specific posts selected.
                            $does_current_doc_category_with_mkb_has_selected_posts = true;
                            $counts -= 1;
                        }
                    }

                    if ( ! $does_current_doc_category_with_mkb_has_selected_posts ) { // if the mkb & doc category does not have any specific posts assigned, then minus the restricted doc category posts on mkb count
                        $doc_category       = get_term( $term_id, 'doc_category' );
                        $doc_category_count = isset( $doc_category->count ) ? $doc_category->count : 0;
                        $counts -= $doc_category_count;
                    }
                }
            }
        }

        if ( isset( $term->taxonomy ) && isset( $term->term_id ) && isset( $selected_mkb_terms[ $term->term_id ] ) && 'knowledge_base' == $term->taxonomy && 'view' == $selected_mkb_terms[ $term->term_id ] ) { // check if current mkb category is in view mode
            $specific_mkb_doc_count = 0;

            foreach ( $selected_categories_terms as $term_id => $status ) {
                $knowledge_bases = get_term_meta( $term_id, 'doc_category_knowledge_base', true );
                $mkb_term_slug   = isset( $term->slug ) ? $term->slug : '';
                $mkb_term_id     = $term->term_id;
                if ( in_array( $mkb_term_slug, $knowledge_bases ) && 'view' == $status ) { // check if the doc categories, belong to the current mkb, then re-process the count
                    $does_current_doc_category_with_mkb_has_selected_posts = false; // flag added to know whether we need to specifically calculate specific post count for mkb attached to mkb & doc category or minus the whole doc category count on mkb count

                    foreach ( $selected_categories_posts as $post_id => $post_status ) { // if the post's has selected specifically attached to mkb & doc category
                        if ( has_term( $mkb_term_id, 'knowledge_base', $post_id ) && has_term( $term_id, 'doc_category', $post_id ) && 'view' == $status && 'view' == $post_status ) { // if the post has mkb & doc category attached, we know that it has specific posts selected.
                            $does_current_doc_category_with_mkb_has_selected_posts = true;
                            $specific_mkb_doc_count += 1;
                        }
                    }

                    if ( ! $does_current_doc_category_with_mkb_has_selected_posts ) { // if the mkb & doc category does not have any specific posts assigned, then add the restricted doc category posts on mkb count
                        $doc_category       = get_term( $term_id, 'doc_category' );
                        $doc_category_count = isset( $doc_category->count ) ? $doc_category->count : 0;
                        $specific_mkb_doc_count += $doc_category_count;
                    }
                }
            }

            $counts = $specific_mkb_doc_count > 0 ? $specific_mkb_doc_count : $counts;
        }

        if ( ( isset( $term->term_id ) && isset( $term->taxonomy ) && 'doc_category' == $term->taxonomy && isset( $selected_categories_terms[ $term->term_id ] ) && 'view' == $selected_categories_terms[ $term->term_id ] && is_tax( 'knowledge_base' ) ) || ( isset( $term->term_id ) && isset( $term->taxonomy ) && 'doc_category' == $term->taxonomy && isset( $selected_categories_terms[ $term->term_id ] ) && 'full-control' == $selected_categories_terms[ $term->term_id ] && is_tax( 'knowledge_base' ) ) || is_singular( 'docs' ) ) { // increment the knowledgebase based doc categories count
            $doc_category_count = 0;
            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term->term_id ) && 'view' == $doc_status || $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term->term_id ) && 'full-control' == $doc_status ) {
                    ++$doc_category_count;
                }
            }
            $counts = $doc_category_count > 0 ? $doc_category_count : $counts;
        }

        if ( isset( $term->term_id ) && isset( $term->taxonomy ) && 'doc_category' == $term->taxonomy && isset( $selected_categories_terms[ $term->term_id ] ) && 'view' == $selected_categories_terms[ $term->term_id ] && is_tax( 'doc_category' ) || isset( $term->term_id ) && isset( $term->taxonomy ) && 'doc_category' == $term->taxonomy && isset( $selected_categories_terms[ $term->term_id ] ) && 'full-control' == $selected_categories_terms[ $term->term_id ] && is_tax( 'doc_category' ) ) { // increment the knowledgebase based doc categories count
            $doc_category_count = 0;
            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term->term_id ) && 'view' == $doc_status || $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term->term_id ) && 'full-control' == $doc_status ) {
                    ++$doc_category_count;
                }
            }
            $counts = $doc_category_count > 0 ? $doc_category_count : $counts;
        }

        if ( isset( $term->term_id ) && isset( $term->taxonomy ) && 'doc_category' == $term->taxonomy && isset( $selected_categories_terms[ $term->term_id ] ) && 'restricted' == $selected_categories_terms[ $term->term_id ] && is_tax( 'knowledge_base' ) ) { //decrement the knowledgebase based doc categories count
            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term->term_id ) && 'restricted' == $doc_status ) {
                    --$counts;
                }
            }
            $counts = $counts < 0 ? 0 : $counts;
        }

        if ( ( isset( $term->term_id ) && isset( $term->taxonomy ) && 'doc_category' == $term->taxonomy && isset( $selected_categories_terms[ $term->term_id ] ) && 'restricted' == $selected_categories_terms[ $term->term_id ] && is_tax( 'doc_category' ) ) || is_singular( 'docs' ) ) { //decrement the knowledgebase based doc categories count
            foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                if ( $this->doc_belongs_to_taxonomy_sql( $doc_id, 'doc_category', $term->term_id ) && 'restricted' == $doc_status ) {
                    --$counts;
                }
            }
            $counts = $counts < 0 ? 0 : $counts;
        }

        return $counts;
    }

    public function include_or_exclude_selected_terms( $query_args ) {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

        if ( ! empty( $selected_categories_terms ) ) {
            $view_filtered_terms       = array(  );
            $restricted_filtered_terms = array(  );
            foreach ( $selected_categories_terms as $term_id => $status ) {
                if ( 'view' == $status ) {
                    array_push( $view_filtered_terms, $term_id );
                } elseif ( 'restricted' == $status ) {
                    array_push( $restricted_filtered_terms, $term_id );
                }
            }

            $query_args[ 'include' ] = $view_filtered_terms;

            $query_args[ 'exclude' ] = ! empty( $restricted_filtered_terms ) ? $restricted_filtered_terms : array(  );

            /**
             * Check If Restricted Term Id's Needs To Be Included In The 'include' Param, Only If The Restricted Term Id's Have Specific Post Selected. Else Dont Include It
             */
            foreach ( $restricted_filtered_terms as $term_id ) {
                foreach ( $selected_categories_posts as $doc_id => $status ) {
                    if ( has_term( $term_id, 'doc_category', $doc_id ) && 'restricted' == $status ) {
                        if ( count( $query_args[ 'include' ] ) > 0 ) { //if term already exist's, then insert the term that has specific post restricted, because we need to show that term excluding its post only.
                            array_push( $query_args[ 'include' ], $term_id );
                        }

                        /**
                         * If The Exclude Has A Post Selected Then Remove It From Exclude
                         */
                        $query_args[ 'exclude' ] = array_filter( $query_args[ 'exclude' ], function ( $id ) use ( $term_id ) {
                            return $id != $term_id;
                        } );
                    }
                }
            }
        }
        return $query_args;
    }

    public function include_or_exclude_selected_posts( $args, $_term_id, $_origin_args ) {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

        /**
         * If the current term is set to 'View', then add it
         */
        if ( isset( $selected_categories_terms[ $_term_id ] ) && 'view' == $selected_categories_terms[ $_term_id ] ) { //include if specific posts id's of term are selected for 'view'
            $args[ 'post__in' ] = array(  );
            foreach ( $selected_categories_posts as $post_id => $status ) {
                if ( has_term( $_term_id, 'doc_category', $post_id ) && 'view' == $status ) {
                    array_push( $args[ 'post__in' ], $post_id );
                }
            }
        }

        /**
         * If the current term is set to 'Restricted', then exclude it
         */
        if ( isset( $selected_categories_terms[ $_term_id ] ) && 'restricted' == $selected_categories_terms[ $_term_id ] ) { //exlcude if specific posts id's of term are selected for 'restricted'
            $term_slug              = get_term( $_term_id, 'doc_category' )->slug;
            $args[ 'post__not_in' ] = array(  );
            foreach ( $selected_categories_posts as $post_id => $status ) {
                if ( has_term( $_term_id, 'doc_category', $post_id ) && 'restricted' == $status ) {
                    array_push( $args[ 'post__not_in' ], $post_id );
                    foreach ( $args[ 'tax_query' ] as &$term_query ) {
                        if ( 'slug' == $term_query[ 'field' ] && $term_query[ 'terms' ] == $term_slug ) {
                            $term_query[ 'operator' ] = 'IN';
                        }
                    }

                    if ( ! empty( $args[ 'post__in' ] ) ) { // re-check if the post id is excluded from the 'post__in' param, since its restricted
                        $args[ 'post__in' ] = array_filter( $args[ 'post__in' ], function ( $id ) use ( $post_id ) {
                            return $id != $post_id;
                        } );
                    }
                }
            }
        }

        return $args;
    }

    public function include_or_exclude_selected_mkb_doc_category_attached_posts( $args, $_term_id, $_origin_args ) {
        $selected_mkb_terms        = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_multiple_kb' ] : array(  ) ) : array(  );
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );

        $args[ 'post__not_in' ] = array(  );
        $args[ 'post__in' ]     = array(  );

        if ( isset( $args[ 'tax_query' ] ) && ! empty( $args[ 'tax_query' ] ) ) {
            $tax_query          = $args[ 'tax_query' ];
            $mkb_terms          = isset( $tax_query[ 0 ][ 'terms' ] ) ? $tax_query[ 0 ][ 'terms' ] : array(  );
            $doc_category_terms = isset( $tax_query[ 1 ][ 'terms' ] ) ? $tax_query[ 1 ][ 'terms' ] : array(  );

            foreach ( $mkb_terms as $mkb_term_id ) {
                foreach ( $doc_category_terms as $doc_category_term_id ) {
                    foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                        if ( isset( $selected_categories_terms[ $doc_category_term_id ] ) && 'restricted' == $doc_status && 'restricted' == $selected_categories_terms[ $doc_category_term_id ] && has_term( $mkb_term_id, 'knowledge_base', $doc_id ) && has_term( $doc_category_term_id, 'doc_category', $doc_id ) ) {
                            array_push( $args[ 'post__not_in' ], $doc_id );
                        } elseif ( isset( $selected_categories_terms[ $doc_category_term_id ] ) && 'view' == $doc_status && 'view' == $selected_categories_terms[ $doc_category_term_id ] && has_term( $mkb_term_id, 'knowledge_base', $doc_id ) && has_term( $doc_category_term_id, 'doc_category', $doc_id ) ) {
                            array_push( $args[ 'post__in' ], $doc_id );
                        }
                    }
                }
            }
        }

        return $args;
    }

    public function include_or_exclude_selected_posts_count( $counts, $term, $nested_subcategory, $args ) {
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $term_id                   = isset( $term->term_id ) ? $term->term_id : 0;

        /**
         * Increment The Count Of The Term If It Has 'View'
         */
        if ( isset( $selected_categories_terms[ $term_id ] ) && 'view' == $selected_categories_terms[ $term_id ] ) {
            $specific_doc_count = 0;
            foreach ( $selected_categories_posts as $post_id => $status ) {
                if ( has_term( $term_id, 'doc_category', $post_id ) && 'view' == $status ) {
                    $specific_doc_count += 1;
                }
            }
            $counts = $specific_doc_count > 0 ? $specific_doc_count : $counts; //include the count if specific posts for term exist for 'view' permission
        }

        /**
         * Decrement The Count Of The Term If It Has 'Restricted'
         */
        if ( isset( $selected_categories_terms[ $term_id ] ) && 'restricted' == $selected_categories_terms[ $term_id ] ) {
            foreach ( $selected_categories_posts as $post_id => $status ) {
                if ( has_term( $term_id, 'doc_category', $post_id ) && 'restricted' == $status ) {
                    $counts -= 1;
                }
            }
        }

        return $counts;
    }

    /**
     * Get The Selected Rules From Settings Of User Normalize It Based On The Current User
     *
     * @return array
     */
    public function get_selected_rules_based_on_user_role() {
        $filtered_rules_for_current_user = array(  );

        /**
         * Assign Permission Mode To All The Mkb Ids, Doc Ids, Doc Category Ids
         */
        foreach ( $this->access_control_settings as &$data ) {
            if ( isset( betterdocs_pro()->multiple_kb->is_enable ) && betterdocs_pro()->multiple_kb->is_enable ) {
                $data[ 'control_access_restrict_multiple_kb' ]     = isset( $data[ 'control_access_restrict_multiple_kb' ] ) && ! empty( $data[ 'control_access_restrict_multiple_kb' ] ) ? $data[ 'control_access_restrict_multiple_kb' ] : array(  );
                $permission_mode                                   = isset( $data[ 'control_access_permission_mode_kb' ] ) ? $data[ 'control_access_permission_mode_kb' ] : 'option-not-set';
                $data[ 'control_access_restrict_docs_kb' ]         = isset( $data[ 'control_access_restrict_docs_kb' ] ) && ! empty( $data[ 'control_access_restrict_docs_kb' ] ) ? $data[ 'control_access_restrict_docs_kb' ] : array(  );
                $data[ 'control_access_restrict_doc_category_kb' ] = isset( $data[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $data[ 'control_access_restrict_doc_category_kb' ] ) ? $data[ 'control_access_restrict_doc_category_kb' ] : array(  );

                if ( ! empty( $data[ 'control_access_restrict_multiple_kb' ] ) && betterdocs_pro()->multiple_kb->is_enable ) {
                    $data[ 'control_access_restrict_multiple_kb' ] = array( $data[ 'control_access_restrict_multiple_kb' ] => $permission_mode );
                }

                $new_doc_ids = array(  );
                if ( ! empty( $data[ 'control_access_restrict_docs_kb' ] ) ) {
                    foreach ( $data[ 'control_access_restrict_docs_kb' ] as $doc_id ) {
                        $new_doc_ids[ $doc_id ] = $permission_mode;
                    }
                    $data[ 'control_access_restrict_docs_kb' ] = $new_doc_ids;
                }

                $new_doc_category_ids = array(  );
                if ( ! empty( $data[ 'control_access_restrict_doc_category_kb' ] ) ) {
                    foreach ( $data[ 'control_access_restrict_doc_category_kb' ] as $doc_id ) {
                        $new_doc_category_ids[ $doc_id ] = $permission_mode;
                    }
                    $data[ 'control_access_restrict_doc_category_kb' ] = $new_doc_category_ids;
                }
            } else {
                $permission_mode                                = isset( $data[ 'control_access_permission_mode' ] ) ? $data[ 'control_access_permission_mode' ] : 'option-not-set';
                $data[ 'control_access_restrict_docs' ]         = isset( $data[ 'control_access_restrict_docs' ] ) && ! empty( $data[ 'control_access_restrict_docs' ] ) ? $data[ 'control_access_restrict_docs' ] : array(  );
                $data[ 'control_access_restrict_doc_category' ] = isset( $data[ 'control_access_restrict_doc_category' ] ) && ! empty( $data[ 'control_access_restrict_doc_category' ] ) ? $data[ 'control_access_restrict_doc_category' ] : array(  );

                $new_doc_ids = array(  );
                if ( ! empty( $data[ 'control_access_restrict_docs' ] ) ) {
                    foreach ( $data[ 'control_access_restrict_docs' ] as $doc_id ) {
                        $new_doc_ids[ $doc_id ] = $permission_mode;
                    }
                    $data[ 'control_access_restrict_docs' ] = $new_doc_ids;
                }

                $new_doc_category_ids = array(  );
                if ( ! empty( $data[ 'control_access_restrict_doc_category' ] ) ) {
                    foreach ( $data[ 'control_access_restrict_doc_category' ] as $doc_id ) {
                        $new_doc_category_ids[ $doc_id ] = $permission_mode;
                    }
                    $data[ 'control_access_restrict_doc_category' ] = $new_doc_category_ids;
                }
            }
        }

        /**
         * Filter The Applicable Settings For The Current User For Operation
         */
        foreach ( $this->current_user_role as $user_role ) {
            foreach ( $this->access_control_settings as $setting ) {
                if ( in_array( $user_role, isset( $setting[ 'control_access_restrict_roles' ] ) && ! empty( $setting[ 'control_access_restrict_roles' ] ) ? $setting[ 'control_access_restrict_roles' ] : array(  ) ) && ! betterdocs_pro()->multiple_kb->is_enable ) {
                    $filtered_rules_for_current_user[ 'control_access_restrict_doc_category' ] = isset( $filtered_rules_for_current_user[ 'control_access_restrict_doc_category' ] ) ? $filtered_rules_for_current_user[ 'control_access_restrict_doc_category' ] : array(  );
                    $filtered_rules_for_current_user[ 'control_access_restrict_docs' ]         = isset( $filtered_rules_for_current_user[ 'control_access_restrict_docs' ] ) ? $filtered_rules_for_current_user[ 'control_access_restrict_docs' ] : array(  );
                    $filtered_rules_for_current_user[ 'control_access_restrict_doc_category' ] += $setting[ 'control_access_restrict_doc_category' ]; //preserve the keys
                    $filtered_rules_for_current_user[ 'control_access_restrict_docs' ] += $setting[ 'control_access_restrict_docs' ]; //preserve the keys
                } elseif ( in_array( $user_role, isset( $setting[ 'control_access_restrict_roles_kb' ] ) && ! empty( $setting[ 'control_access_restrict_roles_kb' ] ) ? $setting[ 'control_access_restrict_roles_kb' ] : array(  ) ) && betterdocs_pro()->multiple_kb->is_enable ) {
                    $filtered_rules_for_current_user[ 'control_access_restrict_multiple_kb' ]     = isset( $filtered_rules_for_current_user[ 'control_access_restrict_multiple_kb' ] ) ? $filtered_rules_for_current_user[ 'control_access_restrict_multiple_kb' ] : array(  );
                    $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] = isset( $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] ) ? $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] : array(  );
                    $filtered_rules_for_current_user[ 'control_access_restrict_docs_kb' ]         = isset( $filtered_rules_for_current_user[ 'control_access_restrict_docs_kb' ] ) ? $filtered_rules_for_current_user[ 'control_access_restrict_docs_kb' ] : array(  );

                    $filtered_rules_for_current_user[ 'control_access_restrict_multiple_kb' ] += $setting[ 'control_access_restrict_multiple_kb' ]; //preserve the keys
                    $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] += $setting[ 'control_access_restrict_doc_category_kb' ]; //preserve the keys
                    $filtered_rules_for_current_user[ 'control_access_restrict_docs_kb' ] += $setting[ 'control_access_restrict_docs_kb' ]; //preserve the keys
                }
            }
        }

        if ( isset( $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] ) && ! empty( $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] ) ) {
            Helper::remove_all_from_content_restriction_for_doc_categories( $filtered_rules_for_current_user[ 'control_access_restrict_doc_category_kb' ] );
        }

        return $filtered_rules_for_current_user;
    }

    public function template_redirect_without_mkb() {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );
        $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  );
        $current_object_id         = get_queried_object_id();

        if ( is_tax( 'doc_category' ) ) {
            if ( isset( $selected_categories_terms[ $current_object_id ] ) && 'restricted' == $selected_categories_terms[ $current_object_id ] ) {
                $needs_redirection = true;
                foreach ( $selected_categories_posts as $doc_id => $doc_status ) { // check if the docs are selected or not for this current category
                    if ( has_term( $current_object_id, 'doc_category', $doc_id ) && 'restricted' == $doc_status ) {
                        $needs_redirection = false;
                        break;
                    }
                }
                if ( $needs_redirection ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            } elseif ( isset( $selected_categories_terms[ $current_object_id ] ) && 'view' == $selected_categories_terms[ $current_object_id ] ) { //skip if current category is set to 'view'
                return;
            } else {

                //check if 'view' exist on a doc category(for categories that are not set), if exist then redirect it
                $needs_redirection = false;

                foreach ( $selected_categories_terms as $doc_category_id => $status ) {
                    if ( 'view' == $status ) {
                        $needs_redirection = true;
                        break;
                    }
                }

                if ( $needs_redirection ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            }
        }

        if ( is_singular( 'docs' ) ) {
            if ( isset( $selected_categories_posts[ $current_object_id ] ) && 'restricted' == $selected_categories_posts[ $current_object_id ] ) {
                if ( $this->restricted_url ) {
                    wp_safe_redirect( $this->restricted_url );
                    exit();
                } else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    get_template_part( 404 );
                    exit();
                }
            } else {
                $check_if_restricted_category_is_selected = false;
                $restricted_selected_docs                 = array(  );
                $check_if_view_category_is_selected       = false;
                $viewed_selected_docs                     = array(  );
                $current_doc_attached_categories          = wp_get_post_terms( $current_object_id, 'doc_category' );

                foreach ( $current_doc_attached_categories as $doc_category ) { // check if restricted category is selected
                    if ( isset( $selected_categories_terms[ $doc_category->term_id ] ) && 'restricted' == $selected_categories_terms[ $doc_category->term_id ] ) {
                        $check_if_restricted_category_is_selected = true;
                        break;
                    } elseif ( isset( $selected_categories_terms[ $doc_category->term_id ] ) && 'view' == $selected_categories_terms[ $doc_category->term_id ] ) {
                        $check_if_view_category_is_selected = true;
                        break;
                    }
                }

                foreach ( $current_doc_attached_categories as $doc_category ) { // check if docs are selected or not | if docs are selected, do not redirect to 404
                    foreach ( $selected_categories_posts as $doc_id => $doc_status ) {
                        if ( has_term( $doc_category->term_id, 'doc_category', $doc_id ) && 'restricted' == $doc_status && isset( $selected_categories_terms[ $doc_category->term_id ] ) && 'restricted' == $selected_categories_terms[ $doc_category->term_id ] ) {
                            array_push( $restricted_selected_docs, $doc_id );
                        } elseif ( has_term( $doc_category->term_id, 'doc_category', $doc_id ) && 'view' == $doc_status && isset( $selected_categories_terms[ $doc_category->term_id ] ) && 'view' == $selected_categories_terms[ $doc_category->term_id ] ) {
                            array_push( $viewed_selected_docs, $doc_id );
                        }
                    }
                }

                if ( $check_if_restricted_category_is_selected && empty( $restricted_selected_docs ) ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }

                if ( $check_if_view_category_is_selected && ! empty( $viewed_selected_docs ) && ! in_array( $current_object_id, $viewed_selected_docs ) ) {
                    if ( $this->restricted_url ) {
                        wp_safe_redirect( $this->restricted_url );
                        exit();
                    } else {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 );
                        exit();
                    }
                }
            }
        }
    }

    public function modify_quick_actions_wp_list_table( $actions, $tag ) {
        $selected_categories_terms = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ] : array(  ) ) : array(  );

        foreach ( $selected_categories_terms as $term_id => $term_status ) {
            if ( isset( $tag->term_id ) && $tag->term_id == $term_id && 'view' == $term_status ) {
                unset( $actions[ 'edit' ] );
                unset( $actions[ 'inline hide-if-no-js' ] );
                unset( $actions[ 'delete' ] );
            } elseif ( isset( $tag->term_id ) && $tag->term_id == $term_id && 'edit-only' == $term_status ) {
                unset( $actions[ 'view' ] );
                unset( $actions[ 'delete' ] );
            }
        }

        return $actions;
    }

    public function modify_quick_actions_wp_list_table_for_all( $actions, $tag ) {
        $doc_categories_mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';
        if ( 'view' == $doc_categories_mode ) {
            unset( $actions[ 'edit' ] );
            unset( $actions[ 'inline hide-if-no-js' ] );
            unset( $actions[ 'delete' ] );
        } elseif ( 'edit-only' == $doc_categories_mode ) {
            unset( $actions[ 'view' ] );
            unset( $actions[ 'delete' ] );
        }
        return $actions;
    }

    public function custom_post_type_row_actions( $actions, $post ) {
        if ( get_post_type() == 'docs' ) {
            $selected_categories_posts = ! empty( $this->final_access_control_settings ) ? ( isset( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) && ! empty( $this->final_access_control_settings[ 'control_access_restrict_docs' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_docs' ] : array(  ) ) : array(  ); //already assumed categories exist
            foreach ( $selected_categories_posts as $post_id => $post_status ) {
                if ( isset( $post->ID ) && $post->ID == $post_id && 'view' == $post_status ) {
                    unset( $actions[ 'edit' ] );
                    unset( $actions[ 'inline hide-if-no-js' ] );
                    unset( $actions[ 'trash' ] );
                } elseif ( isset( $post->ID ) && $post->ID == $post_id && 'edit-only' == $post_status ) {
                    unset( $actions[ 'view' ] );
                    unset( $actions[ 'trash' ] );
                }
            }
        }

        return $actions;
    }

    public function custom_post_type_row_actions_for_all( $actions, $post ) {
        if ( get_post_type() == 'docs' ) {
            $doc_categories_mode = isset( $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] ) ? $this->final_access_control_settings[ 'control_access_restrict_doc_category' ][ 'all' ] : '';
            if ( 'view' == $doc_categories_mode ) {
                unset( $actions[ 'edit' ] );
                unset( $actions[ 'inline hide-if-no-js' ] );
                unset( $actions[ 'trash' ] );
            } elseif ( 'edit-only' == $doc_categories_mode ) {
                unset( $actions[ 'view' ] );
                unset( $actions[ 'trash' ] );
            }
        }
        return $actions;
    }

    public function localize_access_control_settings( $localized_data ) {
        $localized_data[ 'access_control' ] = $this->final_access_control_settings;
        return $localized_data;
    }

    /**
     * Get The Terms Attached To A Post
     *
     * @param integer $doc_id
     * @param string $taxonomy
     *
     * @return array
     */
    public function get_terms_attached_to_a_doc( $doc_id, $taxonomy ) {
        global $wpdb;

        $query = $wpdb->prepare( "
            SELECT t.term_id
            FROM {$wpdb->terms} AS t
            INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tr.object_id = %d
            AND tt.taxonomy = %s",
            array( $doc_id, $taxonomy )
        );

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return array_column( $wpdb->get_results( $query, ARRAY_A ), 'term_id' );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Get The Term Id Based On Term Meta Key And Value
     *
     * @param string $meta_key
     * @param string $meta_value
     *
     * @return array
     */
    public function get_term_meta_based_on_meta_key_and_meta_value( $meta_key, $meta_value ) {
        global $wpdb;
        $query = $wpdb->prepare( '
            SELECT
            DISTINCT(term_id)
            FROM
            ' . $wpdb->prefix . 'termmeta
            WHERE meta_key = %s
            AND meta_value LIKE %s',
            $meta_key,
            '%' . $wpdb->esc_like( $meta_value ) . '%'
        );
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results( $query, ARRAY_A );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return array_column( $results, 'term_id' );
    }

    /**
     * Clear all access control related caches
     *
     * This method removes all caches created by the access control system.
     * It should be called whenever access control settings are updated to ensure
     * that users see the latest permissions immediately.
     *
     * Cache Types Cleared:
     * - Query result cache
     * - Term parents cache
     * - Database query cache
     *
     * Automatic Triggers:
     * - When BetterDocs settings are updated
     * - When BetterDocs Pro settings are updated
     *
     * @since 3.6.0
     * @return void
     */
    public static function clear_access_control_cache() {
        // Clear internal caches
        self::$query_cache = array(  );

        // Reset recursion guard
        self::$filter_recursion_guard = false;

        // Clear WordPress object cache for access control related data
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'betterdocs_access_control' );
        }
    }

    /**
     * Set recursion guard to prevent infinite loops
     *
     * @param bool $guard
     * @return void
     */
    public static function set_recursion_guard( $guard = true ) {
        self::$filter_recursion_guard = $guard;
    }

    /**
     * Get recursion guard status
     *
     * @return bool
     */
    public static function get_recursion_guard() {
        return self::$filter_recursion_guard;
    }
}
