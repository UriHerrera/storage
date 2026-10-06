<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Core\Settings;

/**
 * "Enhance Docs with AI" — enqueues the editor bundle that injects BetterDocs'
 * branded AI "Suggest" actions (Suggest Categories / Suggest Tags) into the
 * native Gutenberg taxonomy panels for the `docs` post type.
 *
 * The whole feature is gated by the `enable_docs_ai_suite` setting (default on).
 * When disabled, nothing is loaded and WordPress behaves exactly as it would
 * without BetterDocs.
 */
class DocsAISuite extends Base {

    public $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;

        if ( ! $this->is_enabled() ) {
            return;
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function is_enabled() {
        return (bool) $this->settings->get( 'enable_docs_ai_suite', true );
    }

    public function enqueue_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        global $post_type;
        if ( 'docs' !== $post_type ) {
            return;
        }

        // Graceful: with no OpenAI key the AI actions can't work, so don't render
        // them — the native panels keep behaving normally.
        $write_ai = betterdocs()->ai_autowrtie;
        if ( empty( $write_ai ) || empty( $write_ai->get_api_key() ) ) {
            return;
        }

        betterdocs()->assets->enqueue( 'betterdocs-docs-ai', 'blocks/docs-ai.js' );
        betterdocs()->assets->enqueue( 'betterdocs-docs-ai-style', 'blocks/docs-ai-style.css' );

        wp_localize_script(
            'betterdocs-docs-ai',
            'betterdocsDocsAI',
            array(
                'restSuggestTerms'    => esc_url_raw( rest_url( 'betterdocs/v1/ai-suggest-terms' ) ),
                'nonce'               => wp_create_nonce( 'wp_rest' ),
                'postId'              => get_the_ID(),
                'taxonomies'          => array( 'doc_category', 'doc_tag' ),
                'i18n'                => array(
                    'suggestCategories' => __( 'Suggest Categories', 'betterdocs' ),
                    'suggestTags'       => __( 'Suggest Tags', 'betterdocs' ),
                    'suggesting'        => __( 'Suggesting…', 'betterdocs' ),
                    'newBadge'          => __( 'new', 'betterdocs' ),
                    'noSuggestions'     => __( 'No suggestions found for this doc.', 'betterdocs' ),
                    'error'             => __( 'Something went wrong. Please try again.', 'betterdocs' ),
                    'permissionError'   => __( 'You don’t have permission to create new terms.', 'betterdocs' )
                )
            )
        );
    }
}
