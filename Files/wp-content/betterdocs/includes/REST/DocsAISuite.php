<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\AIHelper;
use WPDeveloper\BetterDocs\Utils\AIUsage;

/**
 * REST endpoints powering the BetterDocs "Enhance Docs with AI" editor actions
 * for the `docs` post type: AI-suggested taxonomy terms (doc_category / doc_tag)
 * and AI-generated post excerpts. Both reuse the existing OpenAI integration in
 * WriteWithAI / AIHelper (key `ai_autowrite_api_key`, model `write_with_ai_model`).
 *
 * Auto-discovered + registered under the `betterdocs/v1` namespace by Plugin.php.
 */
class DocsAISuite extends BaseAPI {

    /**
     * Taxonomies eligible for AI term suggestions.
     */
    const ALLOWED_TAXONOMIES = array( 'doc_category', 'doc_tag', 'glossaries' );

    /**
     * Generic, catch-all / placeholder names that are never useful suggestions
     * for any taxonomy. Compared case-insensitively against the trimmed name.
     */
    const BLOCKED_TERMS = array(
        'uncategorized',
        'uncategorised',
        'general',
        'miscellaneous',
        'misc',
        'other',
        'others',
        'none',
        'n/a',
        'untagged'
    );

    public function register() {
        $this->post(
            '/ai-suggest-terms',
            array( $this, 'suggest_terms' ),
            array(
                'post_id' => array(
                    'type'     => 'integer',
                    'required' => true
                ),
                'taxonomy' => array(
                    'type'     => 'string',
                    'required' => true
                ),
                'content' => array(
                    'type'     => 'string',
                    'required' => false,
                    'default'  => ''
                ),
                'title' => array(
                    'type'     => 'string',
                    'required' => false,
                    'default'  => ''
                ),
                'max_suggestions' => array(
                    'type'     => 'integer',
                    'required' => false,
                    'default'  => 6
                )
            )
        );
    }

    public function permission_check() {
        // Gate on edit_others_posts to match the sibling FAQ/Glossary AI endpoints
        // (AIFaq/AIGlossary) and keep Author-role users from spending the AI budget.
        return current_user_can( 'edit_others_posts' );
    }

    /**
     * Shared gate: feature toggle + Write-with-AI enabled + API key present.
     *
     * @return \WPDeveloper\BetterDocs\Core\WriteWithAI|\WP_Error
     */
    protected function ai_guard() {
        if ( ! $this->settings->get( 'enable_docs_ai_suite', true ) ) {
            return $this->error(
                'ai_disabled',
                __( 'Docs AI features are disabled. Enable "Enhance Docs with AI" in BetterDocs settings.', 'betterdocs' ),
                400
            );
        }

        $write_ai = betterdocs()->ai_autowrtie;

        if ( empty( $write_ai ) || ! $write_ai->isEnabledWriteWithAI() ) {
            return $this->error(
                'ai_disabled',
                __( 'Write with AI is disabled. Enable it from BetterDocs settings.', 'betterdocs' ),
                400
            );
        }

        if ( empty( $write_ai->get_api_key() ) ) {
            return $this->error(
                'ai_no_key',
                __( 'OpenAI API key is missing. Add one in BetterDocs settings.', 'betterdocs' ),
                400
            );
        }

        return $write_ai;
    }

    public function suggest_terms( WP_REST_Request $request ) {
        $write_ai = $this->ai_guard();
        if ( is_wp_error( $write_ai ) ) {
            return $write_ai;
        }

        $post_id  = (int) $request->get_param( 'post_id' );
        $taxonomy = sanitize_key( (string) $request->get_param( 'taxonomy' ) );
        $max      = (int) $request->get_param( 'max_suggestions' );
        $max      = max( 1, min( 15, $max > 0 ? $max : 6 ) );

        if ( ! in_array( $taxonomy, self::ALLOWED_TAXONOMIES, true ) ) {
            return $this->error(
                'invalid_taxonomy',
                __( 'Unsupported taxonomy for AI suggestions.', 'betterdocs' ),
                400
            );
        }

        list( $title, $content ) = $this->resolve_content( $request, $post_id );

        if ( '' === trim( $content ) ) {
            return $this->error(
                'empty_content',
                __( 'Add some content before requesting suggestions.', 'betterdocs' ),
                400
            );
        }

        $labels_map = array(
            'doc_category' => 'categories',
            'doc_tag'      => 'tags',
            'glossaries'   => 'glossary terms'
        );
        $label = isset( $labels_map[ $taxonomy ] ) ? $labels_map[ $taxonomy ] : 'terms';

        // Bias the model toward reusing terms that already exist on the site.
        $existing      = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 200,
            'fields'     => 'names'
        ) );
        $existing_list = ( is_array( $existing ) && ! empty( $existing ) ) ? implode( ', ', $existing ) : '';

        $system = sprintf(
            'You are a documentation taxonomist. Suggest up to %1$d concise, relevant %2$s for the documentation article. Prefer reusing terms from the provided existing list when they fit. Each term should be 1-3 words in Title Case. Avoid generic, catch-all, or placeholder terms (for example "Uncategorized", "General", "Miscellaneous", "Other") — only suggest terms that genuinely describe the article. Return ONLY a JSON array of strings, for example ["Getting Started","Billing"]. No prose, no object keys, no code fences.',
            $max,
            $label
        );

        // Glossary terms are a curated, defined set — never invent new ones.
        if ( 'glossaries' === $taxonomy ) {
            $system .= ' IMPORTANT: Choose ONLY from the provided existing list of glossary terms. Do not invent or suggest any term that is not in that list. If none of the existing terms fit, return an empty array [].';
        }

        $user = '';
        if ( '' !== $title ) {
            $user .= "Title: {$title}\n\n";
        }
        if ( '' !== $existing_list ) {
            $user .= "Existing {$label}: {$existing_list}\n\n";
        }
        $user .= "Article content:\n{$content}";

        $result = $write_ai->generate_openai_response_raw( $system, $user, 0.3 );

        if ( empty( $result[ 'success' ] ) ) {
            $message = isset( $result[ 'error' ] ) ? (string) $result[ 'error' ] : __( 'Unknown AI error.', 'betterdocs' );
            return $this->error( 'ai_upstream', $message, 502 );
        }

        // An empty list is a VALID outcome, not an error: for glossaries the model
        // is explicitly told to return [] when no curated term applies, and for
        // category/tag the article may simply not match anything worth suggesting.
        // We return success with an empty `suggestions` array and let the client
        // show a calm "nothing matched" notice instead of a scary "try again".
        $names = $this->parse_string_list( (string) $result[ 'content' ], $max );

        $suggestions = array();
        $seen        = array();
        foreach ( $names as $name ) {
            $key = strtolower( trim( $name ) );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }
            $seen[ $key ] = true;

            // Drop generic, catch-all / placeholder names for every taxonomy.
            if ( in_array( $key, self::BLOCKED_TERMS, true ) ) {
                continue;
            }

            $term = get_term_by( 'name', $name, $taxonomy );

            // Glossaries must come from the existing, curated set only — never
            // surface a term the AI invented that has no glossary definition.
            if ( 'glossaries' === $taxonomy && ! $term ) {
                continue;
            }

            $suggestions[] = array(
                'name'    => $name,
                'term_id' => $term ? (int) $term->term_id : null,
                'is_new'  => $term ? false : true
            );
        }

        // Usage telemetry: a successful AI suggestion run, bucketed by taxonomy
        // (doc_category/doc_tag/glossaries). An empty result is still a use of the
        // feature, so record on any successful upstream response.
        AIUsage::record( 'ai_suggest_terms', $post_id, $taxonomy );

        return $this->success( array(
            'taxonomy'    => $taxonomy,
            'suggestions' => $suggestions,
            'model'       => isset( $result[ 'model' ] ) ? $result[ 'model' ] : null
        ) );
    }

    /**
     * Resolve the title + AI-ready content for a request, preferring the live
     * editor content sent by the client and falling back to the saved post.
     *
     * @return array{0:string,1:string} [ $title, $content ]
     */
    protected function resolve_content( WP_REST_Request $request, $post_id ) {
        $title   = sanitize_text_field( (string) $request->get_param( 'title' ) );
        $content = (string) $request->get_param( 'content' );

        if ( '' === trim( wp_strip_all_tags( $content ) ) && $post_id > 0 ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $content = $post->post_content;
                if ( '' === $title ) {
                    $title = $post->post_title;
                }
            }
        }

        if ( '' === $title && $post_id > 0 ) {
            $title = get_the_title( $post_id );
        }

        $ai_helper = $this->container->get( AIHelper::class );
        $content   = $ai_helper->prepare_content_for_ai( $content, 4000 );

        return array( $title, $content );
    }

    /**
     * Tolerant parse of the model output into a clean list of term-name strings.
     * Handles JSON arrays (of strings or simple objects), code fences, and a
     * comma/newline fallback when JSON can't be found.
     *
     * @return string[]
     */
    protected function parse_string_list( $raw, $max ) {
        $raw = trim( $raw );
        $raw = preg_replace( '/^```(?:json)?\s*/i', '', $raw );
        $raw = preg_replace( '/```\s*$/', '', $raw );

        $names = array();

        $start = strpos( $raw, '[' );
        $end   = strrpos( $raw, ']' );
        if ( false !== $start && false !== $end && $end > $start ) {
            $json    = substr( $raw, $start, $end - $start + 1 );
            $decoded = json_decode( $json, true );
            if ( is_array( $decoded ) ) {
                foreach ( $decoded as $item ) {
                    if ( is_string( $item ) ) {
                        $names[] = $item;
                    } elseif ( is_array( $item ) ) {
                        if ( isset( $item[ 'name' ] ) ) {
                            $names[] = $item[ 'name' ];
                        } elseif ( isset( $item[ 'term' ] ) ) {
                            $names[] = $item[ 'term' ];
                        }
                    }
                }
            }
        }

        // Fallback: split on newlines/commas and strip bullets/numbering/quotes.
        if ( empty( $names ) ) {
            $parts = preg_split( '/[\r\n,]+/', $raw );
            if ( is_array( $parts ) ) {
                foreach ( $parts as $part ) {
                    $part = trim( $part, " \t\"'`-•*0123456789.()[]" );
                    if ( '' !== $part ) {
                        $names[] = $part;
                    }
                }
            }
        }

        $clean = array();
        foreach ( $names as $name ) {
            $name = trim( sanitize_text_field( $name ) );
            if ( '' === $name || mb_strlen( $name ) > 60 ) {
                continue;
            }
            $clean[] = $name;
            if ( count( $clean ) >= $max ) {
                break;
            }
        }

        return $clean;
    }
}
