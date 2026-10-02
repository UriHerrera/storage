<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WP_REST_Server;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;
use WPDeveloper\BetterDocsPro\Core\RelatedDocsEngine;

class RelatedDocs extends BaseAPI {

    /**
     * RelatedDocsEngine instance
     *
     * @var RelatedDocsEngine
     */
    private $engine;

    public function __construct( Settings $settings, Container $container ) {
        parent::__construct( $settings, $container );
        $this->engine = $container->get( RelatedDocsEngine::class );
    }

    public function register() {
        // Public route — frontend feature, no auth required.
        register_rest_route( $this->get_namespace(), 'get-related-docs', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'get_related_docs' ],
            'permission_callback' => '__return_true',
        ] );

        // Editor route — read the persisted (already-generated) suggestion
        // cache for an article. No engine run, no OpenAI call.
        register_rest_route( $this->get_namespace(), 'saved-related-docs', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_saved_related_docs' ],
            'permission_callback' => function ( WP_REST_Request $request ) {
                return $this->can_edit_article( $request->get_param( 'article_id' ) );
            },
        ] );

        // Editor route — run the engine, persist, and return the suggestions.
        // This is the only path that may trigger an OpenAI call from the
        // editor, and only on an explicit "Generate"/"Regenerate" click.
        register_rest_route( $this->get_namespace(), 'generate-related-docs', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'generate_related_docs' ],
            'permission_callback' => function ( WP_REST_Request $request ) {
                return $this->can_edit_article( $request->get_param( 'article_id' ) );
            },
        ] );

        // Admin-only route — analytics dashboard.
        register_rest_route( $this->get_namespace(), 'related-docs-analytics', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_analytics' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * Capability gate for the editor suggestion routes.
     *
     * The author must be able to edit the specific docs post. A separate
     * route per permission tier — never branch authorization on the request
     * URI.
     */
    private function can_edit_article( $article_id ) {
        $article_id = (int) $article_id;

        if ( ! $article_id ) {
            return false;
        }

        return current_user_can( 'edit_post', $article_id );
    }

    /**
     * Hard ceiling on the number of recommendations a public caller may request.
     *
     * `limit` feeds `posts_per_page = limit * 2` inside the engine's candidate
     * queries, so an uncapped value lets an anonymous caller ask for arbitrarily
     * large scans and response payloads.
     */
    const MAX_PUBLIC_LIMIT = 20;

    /**
     * Whether an anonymous caller is allowed to ask for recommendations about
     * this source post.
     *
     * The route is public because it backs the front-end Related Docs widget, so
     * the only source posts it may act on are the ones a visitor could already
     * read: published, not password protected, and not hidden by BetterDocs
     * content restriction. Without this the endpoint happily reads the title and
     * body of drafts and private docs, feeds them to the configured OpenAI
     * account, and leaks their topics back through the recommendations.
     *
     * Editors keep full access — they go through the separate
     * saved/generate routes, which are gated on `edit_post`.
     *
     * @param \WP_Post $post Source docs post.
     * @return bool
     */
    private function is_publicly_readable( $post ) {
        if ( 'publish' !== $post->post_status ) {
            return current_user_can( 'read_post', $post->ID );
        }

        if ( ! empty( $post->post_password ) ) {
            return false;
        }

        $restricted = betterdocs_pro()->get_restricted_doc_ids();

        return ! in_array( (int) $post->ID, array_map( 'intval', (array) $restricted ), true );
    }

    /**
     * Per-IP request cap for the public route within RATE_WINDOW.
     */
    const RATE_LIMIT  = 20;
    const RATE_WINDOW = 5 * MINUTE_IN_SECONDS;

    /**
     * Throttle the public route per client IP.
     *
     * The route is unauthenticated and, when real-time Related Docs plus an
     * OpenAI key are configured, each call can trigger a paid OpenAI request and
     * impression writes. Without a cap an anonymous caller could drive API cost
     * and DB writes at will. Fails closed when no client address is available.
     *
     * @return true|\WP_Error
     */
    private function check_rate_limit() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( $ip === '' ) {
            return $this->error( 'rate_limited', 'Too many requests. Please try again shortly.', 429 );
        }

        $key   = 'betterdocs_related_docs_rl_' . md5( $ip );
        $count = (int) get_transient( $key );
        if ( $count >= self::RATE_LIMIT ) {
            return $this->error( 'rate_limited', 'Too many requests. Please try again shortly.', 429 );
        }

        set_transient( $key, $count + 1, self::RATE_WINDOW );
        return true;
    }

    /**
     * Get related docs for an article
     */
    public function get_related_docs( WP_REST_Request $request ) {
        $article_id = (int) $request->get_param( 'article_id' );

        if ( empty( $article_id ) ) {
            return $this->error( 'missing_article_id', 'Article ID is required', 400 );
        }

        // The parent feature must actually be switched on. Registration is
        // unconditional, so without this the engine runs (and writes impression
        // rows) on sites that never enabled Related Docs at all.
        if ( ! $this->settings->get( 'show_related_docs', false ) ) {
            return $this->error( 'feature_disabled', 'Related Docs is not enabled', 404 );
        }

        // Validate that the article exists and is a docs post
        $post = get_post( $article_id );
        if ( ! $post || $post->post_type !== 'docs' ) {
            return $this->error( 'invalid_article', 'Invalid article ID or not a docs post', 400 );
        }

        // Existence and post type are not authorization — the source post has to
        // be one this caller could already read.
        if ( ! $this->is_publicly_readable( $post ) ) {
            return $this->error( 'invalid_article', 'Invalid article ID or not a docs post', 400 );
        }

        // Throttle AFTER the cheap validations above, so a flood of invalid or
        // unreadable requests (all rejected without doing any work) cannot drain
        // the per-IP bucket and lock out legitimate callers sharing an egress IP.
        // Only requests that would actually run the engine count against it.
        $rate = $this->check_rate_limit();
        if ( is_wp_error( $rate ) ) {
            return $rate;
        }

        // Clamp before the engine turns this into posts_per_page.
        $limit = (int) $request->get_param( 'limit' );
        if ( $limit > self::MAX_PUBLIC_LIMIT ) {
            $request->set_param( 'limit', self::MAX_PUBLIC_LIMIT );
        }

        try {
            // Call the engine method directly with the request
            return $this->engine->get_related_docs( $request );

        } catch ( \Exception $e ) {
            // Never surface internal exception text to an anonymous caller.
            return $this->internal_error( 'Error getting related docs', $e );
        }
    }

    /**
     * Return the persisted suggestion cache for the editor metabox.
     *
     * Never runs the engine — the metabox stays empty (showing its
     * "Generate" CTA) until the author explicitly generates.
     */
    public function get_saved_related_docs( WP_REST_Request $request ) {
        $article_id = (int) $request->get_param( 'article_id' );

        $post = get_post( $article_id );
        if ( ! $post || $post->post_type !== 'docs' ) {
            return $this->error( 'invalid_article', 'Invalid article ID or not a docs post', 400 );
        }

        try {
            return rest_ensure_response( [
                'success' => true,
                'data'    => $this->engine->get_persisted_suggestions( $article_id ),
            ] );
        } catch ( \Exception $e ) {
            return $this->internal_error( 'Error reading saved related docs', $e );
        }
    }

    /**
     * Generate (or regenerate) suggestions for the editor and persist them.
     */
    public function generate_related_docs( WP_REST_Request $request ) {
        $article_id = (int) $request->get_param( 'article_id' );

        $post = get_post( $article_id );
        if ( ! $post || $post->post_type !== 'docs' ) {
            return $this->error( 'invalid_article', 'Invalid article ID or not a docs post', 400 );
        }

        // Editor generation requires an OpenAI key. Without one the engine would
        // silently fall back to content/collaborative-only results, which is
        // misleading in the metabox — the author asked for *AI* suggestions.
        // The public get-related-docs path intentionally still degrades (rule #8);
        // this gate is editor-only. The JS surfaces the `no_api_key` code as the
        // "Configuration Required" card.
        if ( empty( $this->settings->get( 'ai_autowrite_api_key', '' ) ) ) {
            return $this->error( 'no_api_key', 'OpenAI API key is not configured.', 400 );
        }

        $limit = (int) $request->get_param( 'limit' );

        try {
            return rest_ensure_response( [
                'success' => true,
                'data'    => $this->engine->generate_and_persist_suggestions( $article_id, $limit ),
            ] );
        } catch ( \Exception $e ) {
            return $this->internal_error( 'Error generating related docs', $e );
        }
    }

    /**
     * Get analytics data for related docs
     */
    public function get_analytics( WP_REST_Request $request ) {
        try {
            // Call the engine method directly with the request
            return $this->engine->get_analytics( $request );

        } catch ( \Exception $e ) {
            return $this->internal_error( 'Error getting analytics', $e );
        }
    }

    /**
     * Return a generic 500 to the client and keep the detail server-side.
     *
     * Engine exceptions on these routes originate from the OpenAI helper and the
     * database layer, so their messages can carry credentials, request URLs,
     * SQL fragments and filesystem paths. None of that belongs in a REST
     * response, even a capability-gated one.
     *
     * @param string     $context Short description of the failing operation.
     * @param \Exception $e       The caught exception.
     * @return \WP_Error
     */
    private function internal_error( $context, \Exception $e ) {
        error_log( 'BetterDocs Related Docs Error: ' . $context . ': ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

        return $this->error( 'exception', $context, 500 );
    }
}
