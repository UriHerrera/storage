<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\AIHelper;

/**
 * Real-time Related Docs Panel - Recommendation Engine
 *
 * This class handles generating intelligent related document recommendations
 * using content-based filtering, collaborative filtering, and AI analysis.
 */
class RelatedDocsEngine extends Base {

    /**
     * Post-meta key holding the editor-generated AI suggestion cache.
     *
     * The editor metabox is generation-gated: nothing is generated until the
     * author clicks "Generate". The result is cached here and replayed on
     * every later editor visit (no OpenAI call) until "Regenerate" overwrites
     * it.
     */
    const SUGGESTIONS_META_KEY = '_betterdocs_ai_related_suggestions';

    /**
     * Hard ceiling on the recommendation count.
     *
     * `$limit` had only a lower guard, but generate_recommendations() expands it
     * into `posts_per_page = $limit * 2` and `* 3` across several candidate
     * queries, so a large value multiplies straight into the database.
     * REST\RelatedDocs::MAX_PUBLIC_LIMIT clamps the public route, but that is one
     * caller guarding a public method — the ceiling belongs where the
     * multiplication happens, so every entry point (public route, editor
     * metabox, persisted AI suggestions) is bounded, including a
     * `recommendation_limit` setting left at an absurd value.
     */
    const MAX_LIMIT = 20;

    /**
     * Settings instance
     *
     * @var Settings
     */
    private $settings;

    /**
     * RelatedDocsTracker instance
     *
     * @var RelatedDocsTracker
     */
    private $tracker;

    /**
     * AIHelper instance
     *
     * @var AIHelper
     */
    private $ai_helper;

    public function __construct( Settings $settings, RelatedDocsTracker $tracker, AIHelper $ai_helper ) {
        $this->settings = $settings;
        $this->tracker = $tracker;
        $this->ai_helper = $ai_helper;
    }



    /**
     * Get related docs recommendations
     */
    public function get_related_docs( $request ) {
        $article_id = $request->get_param( 'article_id' );
        $session_id = $request->get_param( 'session_id' );
        $limit      = (int) $request->get_param( 'limit' );

        if ( ! $article_id ) {
            return new \WP_Error( 'invalid_article_id', 'Invalid article ID provided', [ 'status' => 400 ] );
        }

        // Public REST consumers can call this without `limit`; an unset param
        // becomes 0 which collapses `posts_per_page = limit * 2` to 0 inside
        // generate_recommendations() and returns []. Fall back to the configured
        // setting (or the same default the generator uses) so the endpoint
        // works without the admin metabox always sending an explicit limit.
        if ( $limit <= 0 ) {
            $limit = (int) $this->settings->get( 'recommendation_limit', 5 );
            if ( $limit <= 0 ) {
                $limit = 5;
            }
        }

        // Get session context if session_id provided
        $session_context = null;
        if ( $session_id ) {
            $session_context = $this->tracker->get_session_context( $session_id, $article_id );
        }

        // Generate recommendations using hybrid approach
        $recommendations = $this->generate_recommendations( $article_id, $session_context, $limit );

        // Record initial impression rows (UPSERT). Re-renders increment impression_count once
        // per render here. Hover/engagement does NOT re-increment — see record_engagement().
        $this->track_impressions( $article_id, $recommendations );

        return rest_ensure_response( [
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
                'session_context' => $session_context,
                'generated_at' => current_time( 'mysql' )
            ]
        ] );
    }

    /**
     * Generate recommendations for the editor and persist them to post meta.
     *
     * Unlike get_related_docs() this deliberately does NOT call
     * track_impressions() — generating a preview inside the editor is not a
     * frontend impression and must not inflate the analytics counters.
     */
    public function generate_and_persist_suggestions( $article_id, $limit = 5 ) {
        $article_id = (int) $article_id;
        $limit      = (int) $limit;

        if ( $limit <= 0 ) {
            $limit = (int) $this->settings->get( 'recommendation_limit', 5 );
            if ( $limit <= 0 ) {
                $limit = 5;
            }
        }

        $recommendations = $this->generate_recommendations( $article_id, null, $limit );

        $payload = [
            'recommendations' => $recommendations,
            'generated_at'    => current_time( 'mysql' ),
        ];

        update_post_meta( $article_id, self::SUGGESTIONS_META_KEY, $payload );

        return $payload;
    }

    /**
     * Read the persisted editor suggestion cache for an article.
     *
     * Returns an empty payload (never null) when nothing has been generated
     * yet, so the metabox can render its "Generate" empty state without an
     * OpenAI call.
     */
    public function get_persisted_suggestions( $article_id ) {
        $stored = get_post_meta( (int) $article_id, self::SUGGESTIONS_META_KEY, true );

        if ( ! is_array( $stored ) || empty( $stored['recommendations'] ) ) {
            return [
                'recommendations' => [],
                'generated_at'    => null,
            ];
        }

        return [
            'recommendations' => $stored['recommendations'],
            'generated_at'    => isset( $stored['generated_at'] ) ? $stored['generated_at'] : null,
        ];
    }

    /**
     * Generate recommendations using hybrid approach
     */
    private function generate_recommendations( $article_id, $session_context = null, $limit = 5 ) {
        $recommendations = [];

        // Single choke point for both entry points (get_related_docs() and
        // generate_and_persist_suggestions()) — see MAX_LIMIT.
        $limit = (int) $limit;
        if ( $limit <= 0 ) {
            $limit = 5;
        }
        if ( $limit > self::MAX_LIMIT ) {
            $limit = self::MAX_LIMIT;
        }

        // Get content-based recommendations
        $content_based = $this->get_content_based_recommendations( $article_id, $limit * 2 );

        // Get collaborative filtering recommendations
        $collaborative = $this->get_collaborative_recommendations( $article_id, $session_context, $limit * 2 );

        // Get AI-powered semantic recommendations. AI semantic analysis is no
        // longer a separate opt-in toggle: it runs automatically whenever
        // Real-time Related Docs is enabled and an OpenAI API key is
        // configured. With no key we skip the request entirely and degrade to
        // content + collaborative (no wasted round-trip / error_log noise).
        $ai_recommendations = [];
        if ( $this->settings->get( 'enable_realtime_related_docs', false )
            && $this->ai_helper && $this->ai_helper->has_api_key() ) {
            $ai_recommendations = $this->get_ai_semantic_recommendations( $article_id, $limit );
        }

        // Combine and rank recommendations
        $all_recommendations = array_merge( $content_based, $collaborative, $ai_recommendations );

        // Remove duplicates and current article
        $unique_recommendations = $this->deduplicate_recommendations( $all_recommendations, $article_id );

        // Drop any candidate the current viewer must not see BEFORE scoring, so a
        // restricted or password-protected doc can never be ranked into (or leaked
        // through) the returned set. Every candidate path above — content, the
        // collaborative co-view SQL, and AI semantic search — can otherwise surface
        // hidden docs and expose their titles and links.
        $unique_recommendations = $this->remove_restricted_recommendations( $unique_recommendations );

        // Score and rank recommendations
        $scored_recommendations = $this->score_recommendations( $unique_recommendations, $article_id, $session_context );

        // Return top recommendations
        return array_slice( $scored_recommendations, 0, $limit );
    }

    /**
     * Get content-based recommendations using categories, tags, and keywords
     */
    private function get_content_based_recommendations( $article_id, $limit = 10 ) {
        $scored = []; // article_id => score data

        $current_post = get_post( $article_id );
        if ( ! $current_post ) return [];

        // Get current article's terms
        $categories    = wp_get_post_terms( $article_id, 'doc_category', [ 'fields' => 'ids' ] );
        $tags          = wp_get_post_terms( $article_id, 'doc_tag', [ 'fields' => 'ids' ] );
        $cat_count     = is_array( $categories ) ? count( $categories ) : 0;
        $tag_count     = is_array( $tags ) ? count( $tags ) : 0;
        $current_words = $this->extract_keywords( $current_post->post_title . ' ' . $current_post->post_content );

        // When Multiple KB is on, scope candidates to the source doc's KB so
        // recommendations don't bleed across knowledge bases. Without this,
        // a doc in KB A would happily get suggested docs from KB B as long as
        // they share a category — confusing for readers and authors alike.
        $kb_tax_query = $this->get_kb_scope_tax_query( $article_id );

        // Fetch candidate articles from same categories
        if ( $cat_count > 0 ) {
            $category_articles = get_posts( [
                'post_type'      => 'docs',
                'post_status'    => 'publish',
                'posts_per_page' => $limit * 3,
                'post__not_in'   => [ $article_id ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                'tax_query'      => $this->merge_tax_queries( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    [ [
                        'taxonomy' => 'doc_category',
                        'field'    => 'term_id',
                        'terms'    => $categories,
                    ] ],
                    $kb_tax_query
                ),
            ] );

            foreach ( $category_articles as $article ) {
                $aid = $article->ID;
                if ( ! isset( $scored[ $aid ] ) ) {
                    $scored[ $aid ] = [ 'post' => $article, 'cat_overlap' => 0, 'tag_overlap' => 0, 'keyword_sim' => 0 ];
                }
                // Count how many categories are shared
                $article_cats = wp_get_post_terms( $aid, 'doc_category', [ 'fields' => 'ids' ] );
                $overlap      = count( array_intersect( $categories, is_array( $article_cats ) ? $article_cats : [] ) );
                $scored[ $aid ]['cat_overlap'] = $cat_count > 0 ? $overlap / $cat_count : 0;
            }
        }

        // Fetch candidate articles from same tags
        if ( $tag_count > 0 ) {
            $tag_articles = get_posts( [
                'post_type'      => 'docs',
                'post_status'    => 'publish',
                'posts_per_page' => $limit * 3,
                'post__not_in'   => [ $article_id ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                'tax_query'      => $this->merge_tax_queries( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    [ [
                        'taxonomy' => 'doc_tag',
                        'field'    => 'term_id',
                        'terms'    => $tags,
                    ] ],
                    $kb_tax_query
                ),
            ] );

            foreach ( $tag_articles as $article ) {
                $aid = $article->ID;
                if ( ! isset( $scored[ $aid ] ) ) {
                    $scored[ $aid ] = [ 'post' => $article, 'cat_overlap' => 0, 'tag_overlap' => 0, 'keyword_sim' => 0 ];
                }
                $article_tags = wp_get_post_terms( $aid, 'doc_tag', [ 'fields' => 'ids' ] );
                $overlap      = count( array_intersect( $tags, is_array( $article_tags ) ? $article_tags : [] ) );
                $scored[ $aid ]['tag_overlap'] = $tag_count > 0 ? $overlap / $tag_count : 0;
            }
        }

        // Fallback when the source doc has no category and no tag: seed the
        // candidate set with recent docs (scoped to the same KB when MKB is
        // on) so keyword_similarity can still surface something. Without this
        // fallback the panel stays empty on fresh sites where authors haven't
        // categorized their docs yet.
        if ( empty( $scored ) ) {
            $fallback_args = [
                'post_type'      => 'docs',
                'post_status'    => 'publish',
                'posts_per_page' => $limit * 3,
                'post__not_in'   => [ $article_id ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            if ( ! empty( $kb_tax_query ) ) {
                $fallback_args['tax_query'] = $kb_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            }
            $fallback_articles = get_posts( $fallback_args );
            foreach ( $fallback_articles as $article ) {
                $aid = $article->ID;
                $scored[ $aid ] = [ 'post' => $article, 'cat_overlap' => 0, 'tag_overlap' => 0, 'keyword_sim' => 0 ];
            }
        }

        // Compute keyword similarity for each candidate
        foreach ( $scored as $aid => &$data ) {
            $candidate_words         = $this->extract_keywords( $data['post']->post_title . ' ' . $data['post']->post_content );
            $data['keyword_sim']     = $this->keyword_similarity( $current_words, $candidate_words );
        }
        unset( $data );

        // Build final recommendations with composite score
        $recommendations = [];
        foreach ( $scored as $aid => $data ) {
            // Weighted composite: 40% category overlap, 25% tag overlap, 35% keyword similarity
            $composite = ( $data['cat_overlap'] * 0.40 )
                       + ( $data['tag_overlap'] * 0.25 )
                       + ( $data['keyword_sim'] * 0.35 );

            // Build human-readable reason
            $reasons = [];
            if ( $data['cat_overlap'] > 0 ) $reasons[] = __( 'Shared category', 'betterdocs-pro' );
            if ( $data['tag_overlap'] > 0 ) $reasons[] = __( 'Shared tags', 'betterdocs-pro' );
            if ( $data['keyword_sim'] > 0.15 ) $reasons[] = __( 'Similar content', 'betterdocs-pro' );

            $recommendations[] = [
                'id'         => $aid,
                'title'      => $data['post']->post_title,
                'url'        => get_permalink( $aid ),
                'type'       => 'content_based',
                'reason'     => implode( ' · ', $reasons ) ?: __( 'Related content', 'betterdocs-pro' ),
                'base_score' => $composite,
            ];
        }

        // Sort by base_score descending, take top $limit
        usort( $recommendations, function ( $a, $b ) {
            return $b['base_score'] <=> $a['base_score'];
        } );

        return array_slice( $recommendations, 0, $limit );
    }

    /**
     * Build a knowledge_base tax_query clause for the source doc's KBs.
     *
     * Returns an empty array when MKB is off, when the doc has no KB term,
     * or when the `knowledge_base` taxonomy isn't registered (e.g. the
     * filter set runs against a site that has the table but the taxonomy
     * is gated off). Callers should treat `[]` as "no KB scope".
     */
    private function get_kb_scope_tax_query( $article_id ) {
        if ( ! $this->settings->get( 'multiple_kb', false ) ) {
            return [];
        }

        if ( ! taxonomy_exists( 'knowledge_base' ) ) {
            return [];
        }

        $kbs = wp_get_post_terms( $article_id, 'knowledge_base', [ 'fields' => 'ids' ] );
        if ( is_wp_error( $kbs ) || empty( $kbs ) ) {
            return [];
        }

        return [ [
            'taxonomy' => 'knowledge_base',
            'field'    => 'term_id',
            'terms'    => $kbs,
        ] ];
    }

    /**
     * Merge a primary tax_query clause with an optional KB scope clause
     * under AND semantics. Keeps a single-clause shape when KB scope is
     * absent, so we don't pessimize the simple-case query plan.
     */
    private function merge_tax_queries( array $primary, array $kb_scope ) {
        if ( empty( $kb_scope ) ) {
            return $primary;
        }
        return array_merge(
            [ 'relation' => 'AND' ],
            $primary,
            $kb_scope
        );
    }

    /**
     * Extract significant keywords from text (lowercased, stopwords removed).
     */
    private function extract_keywords( $text ) {
        $text  = wp_strip_all_tags( $text );
        $text  = strtolower( $text );
        $words = preg_split( '/[\s\-_.,;:!?()\[\]{}"\'\/\\\\]+/', $text, -1, PREG_SPLIT_NO_EMPTY );

        $stop = array_flip( [
            'the','a','an','and','or','but','in','on','at','to','for','of','with','by','from',
            'is','it','this','that','was','are','be','has','had','have','will','would','could',
            'should','may','can','do','does','did','not','no','so','if','how','what','when',
            'where','which','who','why','your','you','we','our','i','my','me','up','out',
            'about','into','than','then','them','they','their','there','these','those','its',
            'been','being','were','also','just','more','some','any','all','each','very',
        ] );

        $filtered = [];
        foreach ( $words as $w ) {
            if ( strlen( $w ) >= 3 && ! isset( $stop[ $w ] ) && ! is_numeric( $w ) ) {
                $filtered[] = $w;
            }
        }

        return array_count_values( $filtered );
    }

    /**
     * Compute cosine-like keyword similarity between two word frequency maps.
     * Returns 0.0 – 1.0.
     */
    private function keyword_similarity( $words_a, $words_b ) {
        if ( empty( $words_a ) || empty( $words_b ) ) return 0;

        $all_words = array_unique( array_merge( array_keys( $words_a ), array_keys( $words_b ) ) );

        $dot = 0; $mag_a = 0; $mag_b = 0;
        foreach ( $all_words as $w ) {
            $a = $words_a[ $w ] ?? 0;
            $b = $words_b[ $w ] ?? 0;
            $dot   += $a * $b;
            $mag_a += $a * $a;
            $mag_b += $b * $b;
        }

        $denom = sqrt( $mag_a ) * sqrt( $mag_b );
        return $denom > 0 ? $dot / $denom : 0;
    }

    /**
     * Get collaborative filtering recommendations based on user journey data
     */
    private function get_collaborative_recommendations( $article_id, $session_context = null, $limit = 10 ) {
        global $wpdb;

        $recommendations = [];
        $table_name = $wpdb->prefix . 'betterdocs_user_journeys';

        // Find articles frequently viewed together with current article
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare(
            "SELECT j2.article_id, COUNT(*) as co_view_count
             FROM {$table_name} j1
             JOIN {$table_name} j2 ON j1.session_id = j2.session_id
             WHERE j1.article_id = %d
             AND j2.article_id != %d
             AND j1.event_type = 'page_view'
             AND j2.event_type = 'page_view'
             AND j1.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY j2.article_id
             ORDER BY co_view_count DESC
             LIMIT %d",
            $article_id,
            $article_id,
            $limit
        );

        $co_viewed_articles = $wpdb->get_results( $query );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        foreach ( $co_viewed_articles as $article ) {
            $post = get_post( $article->article_id );
            if ( $post && $post->post_status === 'publish' ) {
                $recommendations[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink( $post->ID ),
                    'type' => 'collaborative',
                    'reason' => 'Frequently viewed together',
                    'base_score' => min( 0.9, 0.4 + ( $article->co_view_count * 0.1 ) ),
                    'co_view_count' => $article->co_view_count
                ];
            }
        }

        // If session context available, find articles viewed by similar users
        if ( $session_context && ! empty( $session_context['recent_articles'] ) ) {
            $recent_articles = array_slice( $session_context['recent_articles'], 0, 5 );
            $placeholders = implode( ',', array_fill( 0, count( $recent_articles ), '%d' ) );

            // Custom analytics table; identifiers are plugin-controlled, values are prepared.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            $query = $wpdb->prepare(
                "SELECT j2.article_id, COUNT(*) as similarity_score
                 FROM {$table_name} j1
                 JOIN {$table_name} j2 ON j1.session_id = j2.session_id
                 WHERE j1.article_id IN ({$placeholders})
                 AND j2.article_id NOT IN ({$placeholders})
                 AND j2.article_id != %d
                 AND j1.event_type = 'page_view'
                 AND j2.event_type = 'page_view'
                 AND j1.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY j2.article_id
                 ORDER BY similarity_score DESC
                 LIMIT %d",
                array_merge( $recent_articles, $recent_articles, [ $article_id, $limit ] )
            );

            $similar_user_articles = $wpdb->get_results( $query );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

            foreach ( $similar_user_articles as $article ) {
                $post = get_post( $article->article_id );
                if ( $post && $post->post_status === 'publish' ) {
                    $recommendations[] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'url' => get_permalink( $post->ID ),
                        'type' => 'collaborative',
                        'reason' => 'Popular with similar users',
                        'base_score' => min( 0.8, 0.3 + ( $article->similarity_score * 0.1 ) ),
                        'similarity_score' => $article->similarity_score
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Get AI-powered semantic recommendations
     */
    private function get_ai_semantic_recommendations( $article_id, $limit = 5 ) {
        $recommendations = [];

        try {
            $current_post = get_post( $article_id );
            if ( ! $current_post ) {
                return $recommendations;
            }

            // Prepare content for AI analysis
            $content = wp_strip_all_tags( $current_post->post_content );
            $title = $current_post->post_title;

            // Use AI to find semantically similar articles
            $prompt = "Analyze this documentation article and suggest 5 related topics that users might want to read next:\n\nTitle: {$title}\n\nContent: " . substr( $content, 0, 1000 ) . "\n\nReturn only a JSON array of topic keywords, no explanations.";

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that analyzes documentation and suggests related topics. Return only valid JSON arrays.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $ai_response = $this->ai_helper->make_openai_request( $messages, [
                'max_tokens'  => 200,
                'temperature' => 0.3,
                'model'       => $this->settings->get( 'related_docs_model', 'gpt-4o-mini' )
            ] );

            if ( $ai_response && ! is_wp_error( $ai_response ) ) {
                $topics = json_decode( $ai_response, true );

                if ( is_array( $topics ) ) {
                    // Search for articles matching AI-suggested topics
                    foreach ( array_slice( $topics, 0, $limit ) as $topic ) {
                        $search_results = get_posts( [
                            'post_type' => 'docs',
                            'post_status' => 'publish',
                            'posts_per_page' => 2,
                            'post__not_in' => [ $article_id ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                            's' => sanitize_text_field( $topic ),
                            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                                [
                                    'key' => '_password',
                                    'compare' => 'NOT EXISTS'
                                ]
                            ]
                        ] );

                        foreach ( $search_results as $result ) {
                            $recommendations[] = [
                                'id' => $result->ID,
                                'title' => $result->post_title,
                                'url' => get_permalink( $result->ID ),
                                'type' => 'ai_semantic',
                                'reason' => 'AI detected semantic similarity',
                                'base_score' => 0.85,
                                'ai_topic' => $topic
                            ];
                        }
                    }
                }
            }
        } catch ( \Exception $e ) {
            // Log error but don't break the recommendation flow
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'BetterDocs AI Semantic Recommendations Error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }

        return $recommendations;
    }

    /**
     * Strip recommendations the current viewer must not see.
     *
     * Removes BetterDocs content-restricted docs (resolved for the current viewer
     * across both basic and advanced IKB modes via
     * betterdocs_pro()->get_restricted_doc_ids()) as well as password-protected
     * and non-published docs. Applied to the candidate set before scoring so the
     * front-end Related Docs box, the editor AI suggestions, and any other
     * consumer of generate_recommendations() can never surface a hidden doc's
     * title or link.
     *
     * @param array $recommendations
     * @return array
     */
    private function remove_restricted_recommendations( $recommendations ) {
        if ( empty( $recommendations ) ) {
            return $recommendations;
        }

        $restricted = array();
        if ( function_exists( 'betterdocs_pro' ) && method_exists( betterdocs_pro(), 'get_restricted_doc_ids' ) ) {
            $restricted = array_flip( array_map( 'intval', (array) betterdocs_pro()->get_restricted_doc_ids() ) );
        }

        $filtered = array();
        foreach ( $recommendations as $rec ) {
            $id = isset( $rec['id'] ) ? (int) $rec['id'] : 0;
            if ( ! $id || isset( $restricted[ $id ] ) ) {
                continue;
            }

            $post = get_post( $id );
            if ( ! $post || 'publish' !== $post->post_status || ! empty( $post->post_password ) ) {
                continue;
            }

            $filtered[] = $rec;
        }

        return $filtered;
    }

    /**
     * Remove duplicate recommendations and current article
     */
    private function deduplicate_recommendations( $recommendations, $current_article_id ) {
        $unique = [];
        $seen_ids = [ $current_article_id ];

        foreach ( $recommendations as $rec ) {
            if ( ! in_array( $rec['id'], $seen_ids ) ) {
                $unique[] = $rec;
                $seen_ids[] = $rec['id'];
            }
        }

        return $unique;
    }

    /**
     * Score and rank recommendations
     */
    private function score_recommendations( $recommendations, $article_id, $session_context = null ) {
        if ( empty( $recommendations ) ) return [];

        foreach ( $recommendations as &$rec ) {
            $score = $rec['base_score']; // 0.0–1.0 from content/collab/AI

            // Type multiplier
            switch ( $rec['type'] ) {
                case 'ai_semantic':
                    $score *= 1.15;
                    break;
                case 'collaborative':
                    $score *= 1.10;
                    break;
            }

            // Additive boosts
            $score += $this->get_engagement_boost( $rec['id'] );  // 0–0.2
            $score += $this->get_recency_boost( $rec['id'] );     // 0–0.1

            if ( $session_context ) {
                $score += $this->get_context_boost( $rec['id'], $session_context ); // 0–0.1
            }

            $rec['final_score'] = round( $score, 4 );
        }
        unset( $rec );

        // Sort by final score descending
        usort( $recommendations, function ( $a, $b ) {
            return $b['final_score'] <=> $a['final_score'];
        } );

        // Normalize percentages relative to the best score in this set.
        // The top result maps to ~92–97%, others scale proportionally.
        // This ensures visible differentiation even with similar base scores.
        $max_score = $recommendations[0]['final_score'];
        $min_score = end( $recommendations )['final_score'];
        reset( $recommendations );

        foreach ( $recommendations as &$rec ) {
            if ( $max_score > 0 && $max_score !== $min_score ) {
                // Map score range to 45–97% — always meaningful, never identical
                $normalized = ( $rec['final_score'] - $min_score ) / ( $max_score - $min_score );
                $rec['relevance_percentage'] = round( 45 + ( $normalized * 52 ) );
            } else {
                // All scores identical (edge case) — show moderate relevance
                $rec['relevance_percentage'] = round( min( 85, $rec['final_score'] * 90 ) );
            }
        }
        unset( $rec );

        return $recommendations;
    }

    /**
     * Get engagement boost based on article performance
     */
    private function get_engagement_boost( $article_id ) {
        $cache_key = 'betterdocs_eng_boost_' . $article_id;
        $cached    = get_transient( $cache_key );

        if ( $cached !== false ) {
            return (float) $cached;
        }

        global $wpdb;

        $analytics_table = $wpdb->prefix . 'betterdocs_analytics';
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $analytics = $wpdb->get_row( $wpdb->prepare(
            "SELECT impressions, happy, sad FROM {$analytics_table}
             WHERE post_id = %d
             ORDER BY created_at DESC
             LIMIT 1",
            $article_id
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $boost = 0;
        if ( $analytics ) {
            $total_feedback = $analytics->happy + $analytics->sad;
            if ( $total_feedback > 0 ) {
                $boost = ( $analytics->happy / $total_feedback ) * 0.2;
            }
        }

        set_transient( $cache_key, $boost, HOUR_IN_SECONDS );

        return $boost;
    }

    /**
     * Get recency boost for recently published articles
     */
    private function get_recency_boost( $article_id ) {
        $post = get_post( $article_id );
        if ( ! $post ) {
            return 0;
        }

        $days_old = ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS;

        // Boost newer articles (within 30 days)
        if ( $days_old <= 30 ) {
            return ( 30 - $days_old ) / 30 * 0.1; // Max 0.1 boost
        }

        return 0;
    }

    /**
     * Get context boost based on session data
     */
    private function get_context_boost( $article_id, $session_context ) {
        $boost = 0;

        // Boost if article matches search queries
        if ( ! empty( $session_context['search_queries'] ) ) {
            $post = get_post( $article_id );
            if ( $post ) {
                $content = strtolower( $post->post_title . ' ' . $post->post_content );
                foreach ( $session_context['search_queries'] as $query ) {
                    if ( strpos( $content, strtolower( $query ) ) !== false ) {
                        $boost += 0.15;
                        break;
                    }
                }
            }
        }

        // Boost based on engagement level
        $engagement_level = $session_context['engagement_level'] ?? 0;
        if ( $engagement_level > 70 ) {
            $boost += 0.1; // High engagement users get more sophisticated recommendations
        }

        return min( 0.3, $boost ); // Cap context boost
    }

    /**
     * Track impressions for analytics.
     *
     * Called from get_related_docs() at render time so the admin metabox can
     * report impression counts via the analytics endpoint.
     */
    public function track_impressions( $article_id, $recommendations ) {
        global $wpdb;

        $suggestions_table = $wpdb->prefix . 'betterdocs_related_suggestions';

        foreach ( $recommendations as $rec ) {
            // Store relevance_percentage (0–100) so analytics AVG reads directly as %
            $score = isset( $rec['relevance_percentage'] ) ? $rec['relevance_percentage'] : round( $rec['final_score'] * 100 );

            // Custom analytics table; identifiers are plugin-controlled, values are prepared.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$suggestions_table}
                 (article_id, suggested_article_id, suggestion_type, relevance_score, impression_count, created_at, updated_at)
                 VALUES (%d, %d, %s, %f, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                 impression_count = impression_count + 1,
                 relevance_score = %f,
                 updated_at = NOW()",
                $article_id,
                $rec['id'],
                $rec['type'],
                $score,
                $score
            ) );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
    }

    /**
     * Record a hover/engagement event without double-counting impressions.
     *
     * Updates updated_at on the existing suggestion row keyed by the unique
     * (article_id, suggested_article_id, suggestion_type) tuple. Does not
     * increment impression_count — that's already handled at render time by
     * track_impressions(). Adding a real engagement_count column would need
     * a DB migration; until then the timestamp serves as "last engaged".
     */
    public function record_engagement( $article_id, $suggested_article_id, $suggestion_type ) {
        global $wpdb;

        $suggestions_table = $wpdb->prefix . 'betterdocs_related_suggestions';

        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$suggestions_table}
             SET updated_at = NOW()
             WHERE article_id = %d
             AND suggested_article_id = %d
             AND suggestion_type = %s",
            $article_id,
            $suggested_article_id,
            $suggestion_type
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Track clicks for analytics
     */
    public function track_click( $article_id, $suggested_article_id, $suggestion_type ) {
        global $wpdb;

        $suggestions_table = $wpdb->prefix . 'betterdocs_related_suggestions';

        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$suggestions_table}
             SET click_count = click_count + 1,
                 updated_at = NOW()
             WHERE article_id = %d
             AND suggested_article_id = %d
             AND suggestion_type = %s",
            $article_id,
            $suggested_article_id,
            $suggestion_type
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Get analytics data
     */
    public function get_analytics( $request ) {
        $article_id = $request->get_param( 'article_id' );
        $date_range = $request->get_param( 'date_range' ) ?: '7';

        global $wpdb;
        $suggestions_table = $wpdb->prefix . 'betterdocs_related_suggestions';

        // Sanitize date range to ensure it's a number
        $date_range = absint( $date_range );
        $where_conditions = [ "created_at >= DATE_SUB(NOW(), INTERVAL {$date_range} DAY)" ];
        $params = [];

        if ( $article_id ) {
            $where_conditions[] = "article_id = %d";
            $params[] = $article_id;
        }

        $where_clause = implode( ' AND ', $where_conditions );

        // Get overall performance metrics
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $performance = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COUNT(*) as total_suggestions,
                SUM(impression_count) as total_impressions,
                SUM(click_count) as total_clicks,
                AVG(relevance_score) as avg_relevance,
                CASE
                    WHEN SUM(impression_count) > 0
                    THEN (SUM(click_count) / SUM(impression_count)) * 100
                    ELSE 0
                END as click_through_rate
             FROM {$suggestions_table}
             WHERE {$where_clause}",
            $params
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        // Get top performing suggestions
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $top_suggestions = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                s.suggested_article_id,
                p.post_title,
                s.suggestion_type,
                s.impression_count,
                s.click_count,
                s.relevance_score,
                CASE
                    WHEN s.impression_count > 0
                    THEN (s.click_count / s.impression_count) * 100
                    ELSE 0
                END as ctr
             FROM {$suggestions_table} s
             JOIN {$wpdb->posts} p ON s.suggested_article_id = p.ID
                 AND p.post_type = 'docs'
                 AND p.post_status = 'publish'
             WHERE {$where_clause}
             ORDER BY s.click_count DESC, s.impression_count DESC
             LIMIT 10",
            $params
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        // Get suggestion type breakdown
        // Custom analytics table; identifiers are plugin-controlled, values are prepared.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $type_breakdown = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                suggestion_type,
                COUNT(*) as count,
                SUM(impression_count) as impressions,
                SUM(click_count) as clicks,
                AVG(relevance_score) as avg_relevance
             FROM {$suggestions_table}
             WHERE {$where_clause}
             GROUP BY suggestion_type
             ORDER BY clicks DESC",
            $params
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        return rest_ensure_response( [
            'success' => true,
            'data' => [
                'performance' => $performance,
                'top_suggestions' => $top_suggestions,
                'type_breakdown' => $type_breakdown,
                'date_range' => $date_range,
                'article_id' => $article_id
            ]
        ] );
    }
}
