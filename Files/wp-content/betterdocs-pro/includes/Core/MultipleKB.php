<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Multiple-KB groups docs by knowledge_base term meta (kb_order) and
// taxonomy joins — meta_key / meta_query / tax_query and PostNotIn filters
// are intrinsic to the feature.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocsPro\Traits\MKB;
use WPDeveloper\BetterDocs\Core\PostType;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Admin\Builder\Rules;

class MultipleKB extends Base {
    use MKB;
    /**
     * Summary of post_type
     * @var PostType
     */
    private $post_type;
    /**
     * Summary of settings
     * @var Settings
     */
    private $settings;

    public $is_enable = false;

    public function __construct( PostType $type, Settings $settings ) {
        $this->post_type = $type;
        $this->settings  = $settings;
        $this->is_enable = $this->settings->get( 'multiple_kb', false );

        add_filter( 'betterdocs_settings_content_restriction_fields', [$this, 'internal_kb_settings'], 11, 1 );
        add_filter( 'betterdocs_docs_type_rewrite_permalink', [$this, 'type_rewrite_permalink'], 11, 3 );

        // Add filters for private docs support in knowledge_base taxonomy (always needed)
        add_filter( 'betterdocs_modify_terms_args_for_private_docs', [$this, 'modify_terms_args_for_private_docs'], 10, 1 );
        add_filter( 'betterdocs_get_term_docs_count_for_private_filter', [$this, 'get_term_docs_count_for_private_filter'], 10, 2 );

        // Expose the Handbook Layout cover-image attachment id as writable doc_category
        // term meta so the React Doc Categories slide-over can save it over REST. Always
        // on (independent of multiple_kb) and on `init` so REST writes register it.
        add_action( 'init', [$this, 'register_doc_category_pro_meta'] );

        // React Doc Categories slide-over: inject the Pro fields bundle (KB select +
        // Handbook cover picker) on the Free Doc Categories admin screen.
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_doc_category_fields'], 18 );

        /**
         * Return if KB is disabled.
         */
        if ( ! $this->is_enable ) {
            return;
        }

        $this->init();
        $this->admin_init();
    }

    public function init() {
        add_action( 'init', [$this, 'register_taxonomy'] );
        add_filter( 'betterdocs_category_rewrite', [$this, 'category_rewrite'], 10, 2 );
        add_filter( 'betterdocs_term_permalink', [$this, 'term_permalink'], 21, 4 );

        add_filter( 'betterdocs_nested_terms_args', [$this, 'nested_terms_args'], 11 );
        add_filter( 'betterdocs_post_type_link', [$this, 'post_type_link'], 1, 3 );
        add_filter( 'betterdocs_docs_count', [$this, 'counts'], 11, 4 );
        add_filter( 'betterdocs_enable_multiple_knowledge_base', [$this, 'enable'], 9 );
        add_filter( 'betterdocs_docs_tax_query_args', [$this, 'docs_tax_query_args'], 20, 5 );
        add_filter( 'betterdocs_shortcodes_default_atts', [$this, 'shortcodes_default_atts'], 20, 2 );
        add_filter( 'betterdocs_sidebar_template_shortcode_params', [$this, 'archive_template_shortcode_params'], 11, 3 );
        add_filter( 'betterdocs_archive_template_shortcode_params', [$this, 'archive_template_shortcode_params'], 11, 3 );
        add_filter( 'betterdocs_terms_meta_query_args', [$this, 'terms_meta_query'], 10, 4 );
        add_filter( 'betterdocs_breadcrumb_before_archives', [$this, 'breadcrumbs'], 20, 1 );
        add_filter( 'rest_knowledge_base_collection_params', [$this, 'add_rest_orderby_params_on_knowledge_base'], 10, 1 );
        add_filter( 'rest_knowledge_base_query', [$this, 'modify_knowledge_base_rest_query'], 10, 2 );
        add_filter( 'betterdocs_export_type_options', [$this, 'export_type_options'], 10, 1 );
        add_filter( 'betterdocs_export_fields', [$this, 'export_fields'], 10, 1 );
        add_filter( 'rest_doc_category_query', [$this, 'modify_doc_category_rest_query'], 11, 2 );
        add_filter( 'rest_doc_category_query', [$this, 'filter_category_mkb'], 10, 2 );
        add_filter( 'betterdocs_get_child_term_ids_args', [$this, 'child_term_ids_args'], 10, 1 );
        add_filter( 'betterdocs_search_shortcode_attributes', [$this, 'search_shortcode_attributes'], 10, 2 );
    }

    public function modify_doc_category_rest_query( $args, $request ) {
        $params = ! empty( $request->get_params() ) ? $request->get_params() : [];
        if ( array_key_exists( 'knowledge_base', $params ) ) {
            $args['meta_query'] = [
                'relation' => 'AND',
                [
                    'key'     => 'doc_category_knowledge_base',
                    'value'   => $params['knowledge_base'],
                    'compare' => 'LIKE'
                ]
            ];
            $argc['order'] = 'ASC';
        }
        return $args;
    }

    public function filter_category_mkb( $prepared_args, $request ) {
        $params = $request->get_params();
        $mkb    = isset( $params['filter']['knowledgebase'] ) ? $params['filter']['knowledgebase'] : '';
        if ( ! empty( $mkb ) ) {
            $prepared_args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => 'doc_category_knowledge_base',
                    'value'   => $mkb,
                    'compare' => 'LIKE'
                ]
            ];
        }
        return $prepared_args;
    }

    public function child_term_ids_args( $args ) {
        $args['meta_query'] = [
            [
                'key'     => 'doc_category_knowledge_base',
                'value'   => $this->get_kb_slug(),
                'compare' => 'LIKE'
            ]
        ];

        return $args;
    }

    public function modify_knowledge_base_rest_query( $args, $request ) {
        $order_by = $request->get_param( 'orderby' );
        if ( isset( $order_by ) && 'kb_order' === $order_by ) {
            $args['meta_key'] = $order_by;
            $args['orderby']  = 'meta_value_num';
        }

        // All-languages bypass for the React admin language bar (WPML/Polylang).
        if ( $request->get_param( 'bd_all_languages' ) && \WPDeveloper\BetterDocs\Utils\Helper::is_multilingual_active() ) {
            if ( function_exists( 'pll_current_language' ) ) {
                $args['lang'] = '';
            }
            $args['wpml_skip_filters'] = true;
        }

        return $args;
    }

    public function add_rest_orderby_params_on_knowledge_base( $params ) {
        $params['orderby']['enum'][] = 'kb_order';
        return $params;
    }

    public function term_permalink( $permalink, $term, $taxonomy, $params ) {
        if ( ( $term->taxonomy == 'doc_category' ) && empty( $params['kb_slug'] ) && $this->get_first_kb_slug( $term, $taxonomy ) === 'non-knowledgebase' ) {
            $_kb_slug = $this->get_kb_slug();
            return str_replace( [$_kb_slug, '%knowledge_base%'], trim( 'non-knowledgebase' ), $permalink );
        }

        if ( ! empty( $params['kb_slug'] ) ) {
            $_kb_slug = $this->get_kb_slug();
            if ( empty( $_kb_slug ) ) {
                $_kb_slug = $this->get_first_kb_slug( $term, $taxonomy );
            }
            return str_replace( [$_kb_slug, '%knowledge_base%'], trim( $params['kb_slug'] ), $permalink );
        }

        return $permalink;
    }

    public function type_rewrite_permalink( $permalink, $slug, $permalink_structure ) {
        $bettredocs_settings = get_option( 'bettredocs_settings' );
        if ( $bettredocs_settings && ! $this->is_enable && strpos( $permalink, '%knowledge_base%' ) >= 0 && betterdocs()->database->get( 'permalink_structure' ) ) {
            $permalink = trim( str_replace( '%knowledge_base%', '', $permalink ), '/' );
            $this->settings->save_settings( ['permalink_structure' => $permalink] );
        }

        return $permalink;
    }

    public function nested_terms_args( $args ) {
        global $wp_query;

        return $args;
    }

    public function post_type_link( $url, $post = null, $leavename = false ) {
        global $wp_query;
        $_kb_slug = isset( $wp_query->query['knowledge_base'] ) ? $wp_query->query['knowledge_base'] : null;

        // Validate that the post actually belongs to the requested KB. The
        // current request's `knowledge_base` query var is set from the
        // `last_knowledge_base` cookie (or a wrong-language `get_page_by_path`
        // fallback in `Request::parse()`), so on multilingual sites it can be a
        // KB the post isn't in. Using that KB to build the post's canonical URL
        // produces a 404'ing canonical (e.g. `/docs/advice/<bengali-cat>/<slug>/`
        // for a post whose KB is `বেটারডক্স`). Fall back to the post's own KB
        // terms when the requested KB doesn't match — and only honour the
        // requested KB when the post is genuinely cross-listed in it.
        $post_kb_terms = $post ? wp_get_object_terms( $post->ID, 'knowledge_base', [ 'fields' => 'slugs' ] ) : [];
        $post_kb_terms = is_array( $post_kb_terms ) ? $post_kb_terms : [];
        if ( ! empty( $post_kb_terms ) ) {
            if ( $_kb_slug === null || ! in_array( $_kb_slug, $post_kb_terms, true ) ) {
                $_kb_slug = $post_kb_terms[0];
            }
        } elseif ( $_kb_slug === null ) {
            $_kb_slug = 'non-knowledgebase';
        }

        //WPML related compatibility, change slug %knowledge_base% from docs/%knowledge_base% from single doc, when this docs/%knowledge_base%/%doc_category%/ is set for single permalink
        if ( is_single() && taxonomy_exists( 'knowledge_base' ) && is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
            $term_data = get_term_by( 'slug', $_kb_slug, 'knowledge_base' );
            $term_slug = isset( $term_data->slug ) ? $term_data->slug : '';
            return str_replace( '%knowledge_base%', $term_slug, $url );
        }

        return str_replace( '%knowledge_base%', $_kb_slug, $url );
    }

    public function counts( $counts, $term, $nested_subcategory, $args ) {
        // Handle knowledge_base taxonomy for private docs support
        if ( $nested_subcategory == false && is_object( $term ) && isset( $term->taxonomy ) && $term->taxonomy === 'knowledge_base' ) {
            // Recalculate count based on user capabilities for knowledge base terms
            if ( isset( $term->term_id ) && is_numeric( $term->term_id ) ) {
                return $this->get_knowledge_base_docs_count( $term );
            }
        }



        $kb_slug = ! empty( $args['kb_slug'] ) ? trim( $args['kb_slug'] ) : '';
        if ( empty( $kb_slug ) ) {
            if ( is_singular( 'docs' ) ) {
                $kb_terms = $this->single_kb_terms();
                $kb_slug  = ( ! empty( $kb_terms ) && $kb_terms[0] instanceof \WP_Term ) ? $kb_terms[0]->slug : '';
            } else {
                global $wp_query;
                $kb_slug = isset( $wp_query->query['knowledge_base'] ) ? $wp_query->query['knowledge_base'] : '';
            }
        }

        $_kbs = get_terms( [
            'taxonomy'   => 'knowledge_base',
            'slug'       => $kb_slug,
            'hide_empty' => false,
        ] );
        $_kb = ( ! empty( $_kbs ) && ! is_wp_error( $_kbs ) ) ? $_kbs[0] : false;

        if ( isset( $args['multiple_knowledge_base'] ) && $args['multiple_knowledge_base'] && $_kb ) {
            $_child_terms_docs_ids = betterdocs()->query->get_doc_ids_by_term( $term, $_kb, $nested_subcategory );
            
            if ( is_array( $_child_terms_docs_ids ) ) {
                $counts = count( $_child_terms_docs_ids );
            }
        }

        return $counts;
    }

    /**
     * Extend terms args modification for knowledge_base taxonomy
     *
     * @param array $args
     * @return array
     */
    public function modify_terms_args_for_private_docs( $args ) {
        // Gated on being logged in, not on `read_private_docs`: a doc's own author may
        // read their private doc without that capability, so their KB must survive
        // `hide_empty` too. Free's Query::can_read_doc() decides per doc.
        if ( ! is_user_logged_in() || ! isset( $args['taxonomy'] ) || $args['taxonomy'] !== 'knowledge_base' ) {
            return $args;
        }

        // If hide_empty is true, we need to modify the logic to include terms with private docs
        if ( isset( $args['hide_empty'] ) && $args['hide_empty'] ) {
            // Set hide_empty to false and we'll filter manually later
            $args['hide_empty'] = false;
            // Add a flag to indicate we need to filter manually
            $args['_betterdocs_filter_private'] = true;
        }

        return $args;
    }



    /**
     * Get docs count for knowledge_base taxonomy including private docs for logged-in users
     *
     * @param int $count
     * @param WP_Term $term
     * @return int
     */
    public function get_term_docs_count_for_private_filter( $count, $term ) {
        // Only handle knowledge_base taxonomy
        if ( ! is_object( $term ) || ! isset( $term->taxonomy ) || $term->taxonomy !== 'knowledge_base' ) {
            return $count;
        }

        return $this->get_knowledge_base_docs_count( $term );
    }

    /**
     * Get docs count for knowledge_base taxonomy including private docs for logged-in users
     *
     * @param WP_Term $term
     * @return int
     */
    public function get_knowledge_base_docs_count( $term ) {
        if ( ! is_object( $term ) || ! isset( $term->term_id ) || ! isset( $term->taxonomy ) ) {
            return 0;
        }

        // Get all post IDs for this knowledge_base term
        $post_ids = get_objects_in_term( $term->term_id, $term->taxonomy );

        if ( empty( $post_ids ) ) {
            return 0;
        }

        // Public docs for everyone, plus any private doc this user may read — its own
        // author, or a `read_private_docs` holder.
        //
        // Query::can_read_doc() only exists in Free >= 4.9.1 (the other half of
        // this same fix). Pro and Free update independently, so Pro 4.2.2 on
        // Free 4.9.0 is a perfectly ordinary install — and this runs from
        // counts(), hooked to `betterdocs_docs_count`, which older Free fires on
        // every KB/category count render. Calling it unguarded there is a
        // front-end fatal, not a degraded count. Mirror the method locally when
        // it is missing; the fallback is Free's own body, so both paths agree.
        $can_read_doc = method_exists( betterdocs()->query, 'can_read_doc' )
            ? function( $post_id ) {
                return betterdocs()->query->can_read_doc( $post_id );
            }
            : function( $post_id ) {
                if ( is_post_publicly_viewable( $post_id ) ) {
                    return true;
                }

                return 'private' === get_post_status( $post_id )
                    && current_user_can( 'read_post', $post_id );
            };

        $filtered_post_ids = array_filter( $post_ids, $can_read_doc );

        return count( $filtered_post_ids );
    }

    public function enable( $enable ) {
        return $this->is_enable;
    }

    public function add_fields() {
        $terms = get_terms( ['taxonomy' => 'knowledge_base', 'hide_empty' => false] );
        betterdocs()->views->get( 'admin/taxonomy/doc_category/add', ['terms' => $terms] );
    }

    public function update_fields( $term ) {
        global $wpdb;
        $terms = get_terms( ['taxonomy' => 'knowledge_base', 'hide_empty' => false] );
        
        // Directly fetch from DB to bypass Polylang's get_term_metadata filters and object cache
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $raw_meta = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->termmeta} WHERE term_id = %d AND meta_key = 'doc_category_knowledge_base'",
            $term->term_id
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $knowledge_base = ! empty( $raw_meta ) ? maybe_unserialize( $raw_meta ) : [];

        betterdocs()->views->get( 'admin/taxonomy/doc_category/edit', [
            'terms'          => $terms,
            'term'           => $term,
            'knowledge_base' => $knowledge_base
        ] );
    }

    public function admin_init() {
        /**
         * Return if its not admin.
         */
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_enqueue_scripts', [$this, 'enqueue'] );

        // React Knowledge Base admin page (replaces the native term screen in the menu).
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_kb_admin_app'], 20 );
        add_filter( 'betterdocs_admin_screens', [$this, 'register_kb_admin_screen'] );
        add_filter( 'betterdocs_admin_screen_slugs', [$this, 'register_kb_admin_screen_slug'] );

        $this->ajax();

        add_filter( 'betterdocs_highlight_admin_menu', [$this, 'highlight_admin_menu'], 10, 2 );
        add_filter( 'betterdocs_highlight_admin_submenu', [$this, 'highlight_admin_submenu'], 10, 3 );

        add_action( 'betterdocs_doc_category_add_form_before', [$this, 'add_fields'] );
        add_action( 'betterdocs_doc_category_update_form_before', [$this, 'update_fields'], 11, 1 );

        add_filter( 'doc_category_row_actions', [$this, 'disable_category_view'], 10, 2 );

        add_action( 'knowledge_base_add_form_fields', [$this, 'add_kb_icon'] );
        add_action( 'knowledge_base_edit_form_fields', [$this, 'edit_kb_icon'], 10, 2 );

        add_action( 'created_knowledge_base', [$this, 'save_kb_meta'], 10, 2 );
        add_action( 'edited_knowledge_base', [$this, 'update_kb_meta'], 10, 2 );

        add_action( 'admin_head', [$this, 'admin_order_terms'] );
    }

    public function ajax() {
        /**
         * All kind of ajax related to post type: docs
         * for admin side.
         */
        add_action( 'wp_ajax_update_knowledge_base_order', [$this, 'update_knowledge_base_order'] );
    }

    public function enqueue( $hook ) {
        $current_screen = get_current_screen();
        if ( ! isset( $current_screen->id ) || $current_screen->id !== 'edit-knowledge_base' ) {
            return;
        }

        wp_enqueue_media();
        betterdocs()->assets->enqueue( 'betterdocs-category-edit', 'admin/js/category-edit.js' );
        betterdocs_pro()->assets->localize(
            'betterdocs-category-edit',
            'betterdocsCategorySorting',
            [
                'action'      => 'update_knowledge_base_order',
                'selector'    => '.taxonomy-knowledge_base',
                'ajaxurl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'knowledge_base_order_nonce' ),
                'paged'       => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'per_page_id' => "edit_{$current_screen->taxonomy}_per_page"
            ]
        );
    }

    /**
     * Tell the Free plugin to treat the React Knowledge Base page as a BetterDocs
     * screen so it loads the dashboard bundle + admin styles there.
     */
    public function register_kb_admin_screen( $screens ) {
        $screens[] = 'betterdocs_page_betterdocs-knowledge-base';
        return $screens;
    }

    /**
     * Same as above for the (prefix-stripped) body-class screen list.
     */
    public function register_kb_admin_screen_slug( $slugs ) {
        $slugs[] = 'betterdocs-knowledge-base';
        return $slugs;
    }

    /**
     * Enqueue the Pro Knowledge Base React bundle. The bundle registers the route +
     * header filters, so it must load *before* the Free dashboard app renders —
     * dequeue/re-enqueue puts `betterdocs-admin` after it.
     *
     * Gated on `is_betterdocs_screen` rather than this one page: the same gate
     * enqueue_doc_category_fields() (below) and ApiReferences::enqueue_admin_app()
     * already use, and the effect the AI Chatbot / AI Chatbot Logs bundle gets by
     * being enqueued across the admin as well.
     *
     * Restricting it to its own screen is what made this page flash a bare shell.
     * Because the reorder below puts this bundle in front of `betterdocs-admin`,
     * a screen-only gate meant 240 KB of render-blocking script had to be fetched
     * cold the first time the page was opened. Until it landed `dashboard.js` could
     * not run, and `views/admin/main.php` leaves `#betterdocs-dashboard-app` empty
     * with the promo blocks below it — so the shell was all there was to see, with
     * no skeleton, since the skeleton only exists once React has mounted. Every
     * sibling screen avoided this purely by already being cached.
     */
    public function enqueue_kb_admin_app( $hook ) {
        if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        wp_dequeue_script( 'betterdocs-admin' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-knowledge-base', 'admin/js/knowledge-base.js' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-knowledge-base', 'admin/css/knowledge-base.css' );
        wp_enqueue_script( 'betterdocs-admin' );
    }


    /**
     * Menu callback for the React Knowledge Base page — renders the shared
     * dashboard shell that the Free `dashboard.js` app mounts into.
     */
    public function render_kb_admin_page() {
        betterdocs()->views->get( 'admin/main', [ 'admin_ui' => 'dnd' ] );
    }

    /**
     * Register the Handbook Layout cover-image attachment id as writable, REST-exposed
     * doc_category term meta. The read-only `handbookthumbnail` REST field (Free
     * CategoryField) still provides the resolved URL for display.
     */
    public function register_doc_category_pro_meta() {
        register_term_meta( 'doc_category', 'doc_category_thumb-id', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function () {
                return current_user_can( 'edit_docs' );
            },
        ] );
    }

    /**
     * Enqueue the Pro Doc Category fields bundle (Knowledge Base assignment +
     * Handbook cover picker). The bundle registers the
     * `betterdocs_doc_category_slideover_fields` filter (plus the KB table-column
     * filters), so it must load before the Free dashboard app renders —
     * dequeue/re-enqueue keeps `betterdocs-admin` after it.
     *
     * The Doc Categories slide-over lives inside the shared React admin SPA
     * (`betterdocs-admin` / dashboard.js), which is reachable via client-side
     * routing from every BetterDocs screen — e.g. create a Knowledge Base, then
     * route to Categories without a full reload. Gating this to the doc-categories
     * hook alone means the filter is never registered on that in-app path, so the
     * KB select / cover fields silently vanish until a hard reload. Enqueue it on
     * every screen the SPA loads on (same gate Free uses for dashboard.js) so the
     * filter is always present wherever the category drawer can open.
     */
    public function enqueue_doc_category_fields( $hook ) {
        if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        wp_dequeue_script( 'betterdocs-admin' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-doc-category-fields', 'admin/js/doc-category-fields.js' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-doc-category-fields', 'admin/css/doc-category-fields.css' );
        wp_enqueue_script( 'betterdocs-admin' );
    }

    public static function disable_category_view( $actions, $tag ) {
        unset( $actions['view'] );
        return $actions;
    }

    public function category_rewrite( $rewrite, $slug ) {
        $_docs_slug = $this->post_type->docs_archive;

        // FIXME: Need to remove this later.
        // if ( $this->settings->get( 'disable_root_slug_archive', false ) ) {
        //     $_docs_slug = '/';
        // }

        return ['slug' => trim( $_docs_slug, '/' ) . '/%knowledge_base%', 'with_front' => false];
    }

    public function breadcrumbs( $breadcrumbs ) {
        if ( is_post_type_archive( 'docs' ) ) {
            return $breadcrumbs;
        }

        $_term_slug = $this->get_kb_slug();
        if ( $_term_slug != 'non-knowledgebase' ) {
            $term = get_term_by( 'slug', $_term_slug, 'knowledge_base' );

            if ( is_singular( 'docs' ) && isset( $term->term_id ) ) {
                if ( has_term( $term->term_id, 'knowledge_base', get_the_ID() ) ) { // check if the cookie based kb-slug belongs to the knowledge_base for the single doc
                    $term_parents = betterdocs()->query->get_term_parents( $term->term_id, 'knowledge_base' );
                    $breadcrumbs  = array_merge( $breadcrumbs, $term_parents );
                } else { // if the cookie based kb-slug does not belongs to the multiple-kb for a single doc, then show the original kb's assigned to the single doc
                    $terms = wp_get_post_terms( get_the_ID(), 'knowledge_base' );
                    foreach ( $terms as $term ) {
                        $format = [
                            'url'  => get_term_link( $term->term_id, 'knowledge_base' ),
                            'text' => $term->name
                        ];
                        array_push( $breadcrumbs, $format );
                    }
                }
            } else if ( isset( $term->term_id ) ) {
                $term_parents = betterdocs()->query->get_term_parents( $term->term_id, 'knowledge_base' );
                $breadcrumbs  = array_merge( $breadcrumbs, $term_parents );
            }
        }

        return $breadcrumbs;
    }

    public function get_kb_slug( $_kb_slug = '' ) {
        global $wp_query;

        if ( ! betterdocs()->helper->is_templates() && ! empty( $_kb_slug ) ) {
            return $_kb_slug;
        }

        if ( empty( $_kb_slug ) && ! empty( $wp_query->query_vars['knowledge_base'] ) ) {
            $_kb_slug = $wp_query->query_vars['knowledge_base'];
            return $_kb_slug;
        }

        if ( empty( $_kb_slug ) && ! empty( $_COOKIE['last_knowledge_base'] ) ) {
            $_kb_slug = sanitize_text_field( wp_unslash( $_COOKIE['last_knowledge_base'] ) );
        }

        return $_kb_slug;
    }

    /**
     * Filter search shortcode attributes to inject KB slug for archive templates
     * 
     * @param array $attributes
     * @param array $mods
     * @return array
     */
    public function search_shortcode_attributes( $attributes, $mods ) {
        // Only apply KB filtering if kb_based_search is enabled and we're on a KB archive page
        if ( $this->settings->get( 'kb_based_search', false ) && is_tax( 'knowledge_base' ) ) {
            $current_kb = get_queried_object();
            if ( $current_kb && isset( $current_kb->slug ) ) {
                $attributes['kb_based_search'] = $current_kb->slug;
            }
        }

        return $attributes;
    }

    public function docs_tax_query_args( $tax_query, $_multiple_kb, $_term_slug, $_kb_slug, $_origin_args ) {
        global $wp_query;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only check on incoming AJAX request flag.
        $is_post_type_archive = isset( $_POST['is_post_type_archive'] ) ? sanitize_text_field( wp_unslash( $_POST['is_post_type_archive'] ) ) : '';
        

        // Check if kb_slug is provided from shortcode attribute (via $_origin_args)
        $shortcode_kb_slug = isset( $_origin_args['kb_slug'] ) ? $_origin_args['kb_slug'] : '';
        
        // Priority 1: Use shortcode attribute if provided (works for both search and initial queries)
        if ( ! empty( $shortcode_kb_slug ) && ! $is_post_type_archive ) {

            $tax_query[] = [
                'taxonomy'         => 'knowledge_base',
                'field'            => 'slug',
                'terms'            => $shortcode_kb_slug,
                'operator'         => 'AND',
                'include_children' => false
            ];

            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }

            return $tax_query;
        }
        
        // Priority 2: Use global kb_based_search setting if enabled
        // BUT skip cookie-based filtering for REST API requests (they should only filter when kb_slug is explicitly passed)
        $is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;
        if ( isset( $_origin_args['s'] ) && $this->settings->get( 'kb_based_search', false ) && ! $is_post_type_archive && ! $is_rest_request ) {
            $kb_slug_from_method = $this->get_kb_slug();
            
            $tax_query[] = [
                'taxonomy'         => 'knowledge_base',
                'field'            => 'slug',
                'terms'            => $kb_slug_from_method,
                'operator'         => 'AND',
                'include_children' => false
            ];

            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }


            return $tax_query;
        }
        


        if ( is_singular( 'docs' ) ) {
            $kb_terms       = $this->single_kb_terms();
            $knowledge_base = ( ! empty( $kb_terms ) && $kb_terms[0] instanceof \WP_Term ) ? $kb_terms[0]->slug : '';
        } elseif ( $_kb_slug ) {
            $knowledge_base = $_kb_slug;
        } else {
            $knowledge_base = isset( $wp_query->query['knowledge_base'] ) ? $wp_query->query['knowledge_base'] : '';
        }

        if ( $_multiple_kb == true && $knowledge_base != 'non-knowledgebase' ) {
            $taxes   = ['knowledge_base', 'doc_category'];
            $tax_map = [];

            foreach ( $taxes as $tax ) {
                $terms = get_terms( [
                    'taxonomy'   => $tax,
                    'hide_empty' => false
                ] );

                foreach ( $terms as $term ) {
                    $tax_map[$tax][$term->slug] = $term->term_taxonomy_id;
                }
            }

            $tax_query = [];

            if ( array_key_exists( 'knowledge_base', $tax_map ) && ! empty( $tax_map['knowledge_base'][$knowledge_base] ) ) {
                $tax_query[] = [
                    'taxonomy'         => 'knowledge_base',
                    'field'            => 'term_taxonomy_id',
                    'terms'            => [$tax_map['knowledge_base'][$knowledge_base]],
                    'include_children' => false
                ];
            }

            if ( array_key_exists( 'doc_category', $tax_map ) && ! empty( $tax_map['doc_category'][$_term_slug] ) ) {
                $tax_query[] = [
                    'taxonomy'         => 'doc_category',
                    'field'            => 'term_taxonomy_id',
                    'terms'            => [$tax_map['doc_category'][$_term_slug]],
                    'include_children' => false
                ];
            }

            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }

            if ( empty( $tax_query ) && isset( $_origin_args['tax_query'] ) && ! empty( $_origin_args['tax_query'] ) ) {
                $tax_query = $_origin_args['tax_query'];
            }
        }

        return $tax_query;
    }

    public function single_kb_terms() {
        global $post;

        $kb_terms = [];

        // `counts()` calls this behind `is_singular( 'docs' )`, but the post global can
        // still be null there (background/shutdown passes, or a route that resolves as
        // singular for a doc that is gone). Reading `->ID` on null warns under PHP 8 and
        // then returns [], which makes the caller fall back to `$kb_slug = ''` and count
        // against whichever KB sorts first. Bail out instead.
        if ( ! $post instanceof \WP_Post ) {
            return $kb_terms;
        }

        $term     = wp_get_post_terms( $post->ID, 'knowledge_base' );
        if ( ! is_wp_error( $term ) && ! empty( $term ) ) {
            $kb_terms[] = $term[0];
            if ( isset( $_COOKIE['last_knowledge_base'] ) ) {
                $last_kb_slug = sanitize_text_field( wp_unslash( $_COOKIE['last_knowledge_base'] ) );
                if ( has_term( $last_kb_slug, 'knowledge_base' ) ) {
                    // get_term_by() returns false when the cookie slug isn't resolvable in
                    // the active query language (WPML/Polylang) or for a stale/renamed term,
                    // even though has_term() matched loosely (id/slug/name). Only replace the
                    // real term when a WP_Term is actually returned, so callers never read a
                    // property on false.
                    $cookie_term = get_term_by( 'slug', $last_kb_slug, 'knowledge_base' );
                    if ( $cookie_term instanceof \WP_Term ) {
                        $kb_terms[0] = $cookie_term;
                    }
                }
            }
        }
        return $kb_terms;
    }

    public function shortcodes_default_atts( $default_atts, $shortdode ) {
        if ( $this->is_enable ) {
            // if ( isset( $default_atts['multiple_knowledge_base'] ) ) {
            //     $default_atts['multiple_knowledge_base'] = $this->is_enable;
            // }

            // if ( isset( $default_atts['kb_slug'] ) ) {
            //     $default_atts['kb_slug'] = $this->get_kb_slug( $default_atts['kb_slug'] );
            // }
        }

        return $default_atts;
    }

    public function archive_template_shortcode_params( $atts, $shortcode_name, $layout ) {
        if ( $this->is_enable ) {
            $atts['multiple_knowledge_base'] = $this->is_enable;
            if ( ! isset( $atts['kb_slug'] ) ) {
                if( is_singular('docs') ) { //for single docs sidebar
                    $term = get_term_by( 'slug', $this->get_kb_slug(), 'knowledge_base' ); //get the last visisted mkb term
                    $term_id = isset( $term->term_id ) ? $term->term_id : 0;
                    if ( has_term( $term_id, 'knowledge_base', get_the_ID() ) ) { // if the doc belongs to the last visited mkb, then pass the slug. Else do no pass the kb slug
                        $atts['kb_slug'] = $this->get_kb_slug();
                    }
                } else {
                    $atts['kb_slug'] = $this->get_kb_slug();
                }
            }
        }

        return $atts;
    }

    public function terms_meta_query( $meta_query, $_multiple_kb, $_kb_slug, $_origin_args ) {
        if ( ! $_multiple_kb ) {
            return $meta_query;
        }

        if ( ! empty( $_kb_slug ) ) {
            $meta_query = [
                'relation' => 'OR',
                [
                    'key'     => 'doc_category_knowledge_base',
                    'value'   => $this->get_kb_slug( $_kb_slug ),
                    'compare' => 'LIKE'
                ]
            ];
        }

        return $meta_query;
    }

    public function highlight_admin_menu( $parent_file, $current_screen ) {
        if ( in_array( $current_screen->id, ['edit-knowledge_base'] ) ) {
            $parent_file = 'betterdocs-dashboard';
        }

        return $parent_file;
    }

    public function highlight_admin_submenu( $submenu_file, $current_screen, $pagenow ) {
        if ( $current_screen->post_type == 'docs' ) {
            if ( $current_screen->id === 'edit-knowledge_base' ) {
                $submenu_file = 'edit-tags.php?taxonomy=knowledge_base&post_type=docs';
            }
        }

        return $submenu_file;
    }

    public function internal_kb_settings( $settings ) {
        $settings['restrict_kb'] = [
            'name'           => 'restrict_kb',
            'type'           => 'checkbox-select',
            'label'          => __( 'Restriction on Knowledge Bases', 'betterdocs-pro' ),
            'label_subtitle' => __( 'Selected Knowledge Bases will be restricted  ', 'betterdocs-pro' ),
            'priority'       => 9,
            'is_pro'         => true,
            'multiple'       => true,
            'default'        => 'all',
            'placeholder'    => __( 'Select any', 'betterdocs-pro' ),
            'filterValue'    => 'all',
            'options'        => $this->settings->get_terms( 'knowledge_base' ),
            'rules'          => Rules::logicalRule( [
                Rules::is( 'multiple_kb', true ),
                Rules::is( 'enable_content_restriction', true ),
                Rules::is( 'internal_knowledge_base_type', 'basic' )
            ] )
        ];
        return $settings;
    }

    /**
     * Register Knowledge Base Taxonomy
     */
    public function register_taxonomy() {
        $disable_root_slug_mkb = $this->settings->get( 'disable_root_slug_mkb' );
        $docs_archive          = $this->post_type->docs_archive;
        $permalink             = get_option( 'permalink_structure' );

        if ( $disable_root_slug_mkb == 1 && $permalink == "/%postname%/" ) {
            $docs_archive = '/';
        }

        /**
         * Register knowledge base taxonomy
         */
        $manage_labels = [
            'name'                => __( 'Knowledge Base', 'betterdocs-pro' ),
            'singular_name'       => __( 'Knowledge Base', 'betterdocs-pro' ),
            'search_items'        => __( 'Search Knowledge Base', 'betterdocs-pro' ),
            'all_items'           => __( 'All Knowledge Base', 'betterdocs-pro' ),
            'parent_item'         => null,
            'parent_item_colon'   => null,
            'edit_item'           => __( 'Edit Knowledge Base', 'betterdocs-pro' ),
            'update_item'         => __( 'Update Knowledge Base', 'betterdocs-pro' ),
            'not_found'           => __( 'No Knowledge Base found.', 'betterdocs-pro' ),
            'add_new_item'        => __( 'Add New Knowledge Base', 'betterdocs-pro' ),
            'new_item_name'       => __( 'New Knowledge Base Name', 'betterdocs-pro' ),
            'add_or_remove_items' => __( 'Add or reomve Knowledge Base', 'betterdocs-pro' ),
            'menu_name'           => __( 'Knowledge Base', 'betterdocs-pro' )
        ];

        $manage_args = [
            'hierarchical'          => true,
            'labels'                => $manage_labels,
            'show_ui'               => true,
            'update_count_callback' => '_update_post_term_count',
            'show_admin_column'     => true,
            'query_var'             => true,
            'show_in_rest'          => true,
            'has_archive'           => true,
            'rewrite'               => ['slug' => $docs_archive, 'with_front' => false],
            'capabilities'          => [
                'manage_terms' => 'manage_knowledge_base_terms',
                'edit_terms'   => 'edit_knowledge_base_terms',
                'delete_terms' => 'delete_knowledge_base_terms',
                'assign_terms' => 'edit_docs'
            ]
        ];

        register_taxonomy( 'knowledge_base', $this->post_type->post_type, $manage_args );

        // Expose the ordering + icon term meta to the REST API so the React
        // Knowledge Base admin page can read/write them.
        register_term_meta( 'knowledge_base', 'kb_order', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            // Explicit write permission so the React Knowledge Base slide-over can
            // save the order via the REST `meta` field (mirrors the icon meta).
            'auth_callback' => function () {
                return current_user_can( 'edit_knowledge_base_terms' );
            },
        ] );
        register_term_meta( 'knowledge_base', 'knowledge_base_image-id', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function () {
                return current_user_can( 'edit_knowledge_base_terms' );
            },
        ] );
    }

    /**
     * Add a form field in the new category page
     *
     * old: add_knowledge_base_meta
     *
     * @since 1.3.1
     */
    public function add_kb_icon() {
        betterdocs()->views->get( 'admin/taxonomy/kb/add-icon' );
    }

    public function edit_kb_icon( $term, $taxonomy ) {
        $icon_id = get_term_meta( $term->term_id, 'knowledge_base_image-id', true );
        betterdocs()->views->get( 'admin/taxonomy/kb/edit-icon', [
            'icon_id' => $icon_id,
            'term'    => $term
        ] );
    }

    /**
     * Save the form field
     *
     * old: save_knowledge_base_meta
     *
     * @since 2.5.0
     */
    public function save_kb_meta( $term_id ) {
        // Term-meta save runs on `created_knowledge_base` / `edited_knowledge_base`, which fire
        // after WP core has already verified the term-add/edit nonce; no extra nonce needed here.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['term_meta'] ) && is_array( $_POST['term_meta'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $term_meta = array_map( 'sanitize_text_field', wp_unslash( $_POST['term_meta'] ) );
            foreach ( $term_meta as $key => $value ) {
                add_term_meta( $term_id, "knowledge_base_$key", $value );
            }
        }

        $order = $this->get_max_taxonomy_order( 'knowledge_base' );
        update_term_meta( $term_id, 'kb_order', $order++ );
    }

    /*
     * Update the form field value
     *
     * @since 1.3.1
     */
    public function update_kb_meta( $term_id ) {
        // See save_kb_meta — fires after WP-core's term-edit nonce check.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['term_meta'] ) && is_array( $_POST['term_meta'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $term_meta = array_map( 'sanitize_text_field', wp_unslash( $_POST['term_meta'] ) );
            foreach ( $term_meta as $key => $value ) {
                update_term_meta( $term_id, "knowledge_base_$key", $value );
            }
        }
    }

    /**
     * Order the terms on the admin side.
     */
    public function admin_order_terms() {
        global $current_screen;
        $screen_id = isset( $current_screen->id ) ? $current_screen->id : '';

        if ( in_array( $screen_id, ['betterdocs_page_betterdocs-admin', 'betterdocs_page_betterdocs-settings'] ) ) {
            $this->default_term_order( 'knowledge_base' );
        }

        if ( ! isset( $_GET['orderby'] ) && ! empty( $current_screen->base ) && $current_screen->base === 'edit-tags' && $current_screen->taxonomy === 'knowledge_base' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $this->default_term_order( $current_screen->taxonomy );
            add_filter( 'terms_clauses', [$this, 'set_tax_order'], 10, 3 );
        }
    }

    /**
     *
     * Default the taxonomy's terms' order if it's not set.
     *
     * @param string $tax_slug The taxonomy's slug.
     */
    public function default_term_order( $tax_slug ) {
        $terms = get_terms( ['taxonomy' => $tax_slug, 'hide_empty' => false] );
        $order = $this->get_max_taxonomy_order( $tax_slug );

        foreach ( $terms as $term ) {
            if ( ! get_term_meta( $term->term_id, 'kb_order', true ) ) {
                update_term_meta( $term->term_id, 'kb_order', $order );
                $order++;
            }
        }
    }

    /**
     *
     * Get the maximum kb_order for this taxonomy.
     * This will be applied to terms that don't have a tax position.
     *
     */

    private function get_max_taxonomy_order( $tax_slug ) {
        global $wpdb;
        // Aggregate over core term tables; $tax_slug bound via $wpdb->prepare().
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $max_term_order = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT MAX( CAST( tm.meta_value AS UNSIGNED ) )
				FROM {$wpdb->terms} t
				JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = %s
				JOIN {$wpdb->termmeta} tm ON tm.term_id = t.term_id WHERE tm.meta_key = 'kb_order'",
                $tax_slug
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $max_term_order = is_array( $max_term_order ) ? current( $max_term_order ) : 0;
        return (int) $max_term_order === 0 || empty( $max_term_order ) ? 1 : (int) $max_term_order + 1;
    }

    /**
     * Re-Order the taxonomies based on the kb_order value.
     *
     * @param array $pieces     Array of SQL query clauses.
     * @param array $taxonomies Array of taxonomy names.
     * @param array $args       Array of term query args.
     */
    public function set_tax_order( $pieces, $taxonomies, $args ) {
        foreach ( $taxonomies as $taxonomy ) {
            global $wpdb;
            if ( $taxonomy === 'knowledge_base' ) {
                $join_statement = " LEFT JOIN $wpdb->termmeta AS kb_term_meta ON t.term_id = kb_term_meta.term_id AND kb_term_meta.meta_key = 'kb_order'";

                if ( ! $this->does_substring_exist( $pieces['join'], $join_statement ) ) {
                    $pieces['join'] .= $join_statement;
                }

                $pieces['orderby'] = 'ORDER BY CAST( kb_term_meta.meta_value AS UNSIGNED )';
            }
        }
        return $pieces;
    }

    /**
     * Check if a substring exists inside a string.
     *
     * @param string $string    The main string (haystack) we're searching in.
     * @param string $substring The substring we're searching for.
     *
     * @return bool True if substring exists, else false.
     */
    public function does_substring_exist( $string, $substring ) {
        return strstr( $string, $substring ) !== false;
    }

    public function update_knowledge_base_order() {
        if ( ! check_ajax_referer( 'knowledge_base_order_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }

        // A nonce proves the request came from a page we rendered, not that the
        // actor may reorder knowledge bases. The nonce happens to be localized
        // only on a capability-gated screen today, so this is defence in depth —
        // but that is an accident of where the nonce is printed, and any refactor
        // widening its exposure would turn this into unauthorized term-meta writes.
        if ( ! current_user_can( 'manage_knowledge_base_terms' ) ) {
            wp_send_json_error();
        }

        wp_cache_flush();

        // Nonce verified above; array is unslashed and each field sanitized via filter_var_array().
        $kb_ordering_data = isset( $_POST['data'] ) && is_array( $_POST['data'] ) ? filter_var_array( wp_unslash( $_POST['data'] ), FILTER_SANITIZE_NUMBER_INT ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $kb_index         = isset( $_POST['base_index'] ) ? (int) filter_var( wp_unslash( $_POST['base_index'] ), FILTER_SANITIZE_NUMBER_INT ) : 0;

        foreach ( $kb_ordering_data as $order_data ) {
            if ( $kb_index > 0 ) {
                $current_position = get_term_meta( $order_data['term_id'], 'kb_order', true );

                if ( (int) $current_position < (int) $kb_index ) {
                    continue;
                }
            }

            update_term_meta( $order_data['term_id'], 'kb_order', ( (int) $order_data['order'] + (int) $kb_index ) );
        }
        wp_send_json_success();
    }

    public function export_type_options( $options ) {
        $options['knowledge_base'] = __( 'Knowledge Base', 'betterdocs-pro' );
        return $options;
    }

    public function export_fields( $fields ) {
        $fields['export_kbs'] = [
            'name'           => 'export_kbs',
            'type'           => 'checkbox-select',
            'label'          => __( 'Select Knowledge Base', 'betterdocs-pro' ),
            'label_subtitle' => __( "Selected knowledge bases and its docs will be included in the export.", 'betterdocs-pro' ),
            'priority'       => 5,
            'multiple'       => true,
            'search'         => true,
            'default'        => ['all'],
            'placeholder'    => __( 'Select any', 'betterdocs-pro' ),
            'options'        => betterdocs()->settings->get_terms( 'knowledge_base' ),
            'filterValue'    => 'all',
            'rules'          => Rules::logicalRule( [
                Rules::is( 'multiple_kb', true ),
                Rules::is( 'export_type', 'knowledge_base' )
            ], 'and' )
        ];
        return $fields;
    }
}
