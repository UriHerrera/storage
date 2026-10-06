<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\AIUsage;

/**
 * Server-side proxy for the "Define Glossaries with AI" feature.
 *
 * The glossary admin app used to call OpenAI directly from the browser with the secret
 * API key — this endpoint moves that call server-side so the key never leaves the site.
 * Uses the same dynamic model/token settings as Write with AI (write_with_ai_model).
 */
class AIGlossary extends BaseAPI {

    const MAX_TERM_LENGTH        = 200;
    const MAX_INSTRUCTION_LENGTH = 1000;

    public function register() {
        $this->post(
            '/ai-glossary',
            array( $this, 'generate' ),
            array(
                'term' => array(
                    'type'     => 'string',
                    'required' => true
                ),
                'instruction' => array(
                    'type'     => 'string',
                    'required' => false,
                    'default'  => ''
                ),
                'previous_description' => array(
                    'type'     => 'string',
                    'required' => false,
                    'default'  => ''
                )
            )
        );
    }

    public function permission_check() {
        return current_user_can( 'edit_others_posts' );
    }

    public function generate( WP_REST_Request $request ) {
        $settings = betterdocs()->settings;

        if ( ! $settings->get( 'enable_glossaries_write_with_ai', true ) ) {
            return $this->error(
                'ai_disabled',
                __( 'Define Glossaries with AI is disabled. Enable it from BetterDocs settings.', 'betterdocs-pro' ),
                400
            );
        }

        $write_ai = betterdocs()->ai_autowrtie;
        if ( empty( $write_ai ) || empty( $write_ai->get_api_key() ) ) {
            return $this->error(
                'ai_no_key',
                __( 'OpenAI API key is missing. Add one in BetterDocs settings.', 'betterdocs-pro' ),
                400
            );
        }

        $term         = trim( wp_strip_all_tags( (string) $request->get_param( 'term' ) ) );
        $instruction  = wp_strip_all_tags( (string) $request->get_param( 'instruction' ) );
        $previous     = wp_strip_all_tags( (string) $request->get_param( 'previous_description' ) );

        if ( $term === '' ) {
            return $this->error(
                'ai_empty_term',
                __( 'Please provide a glossary term.', 'betterdocs-pro' ),
                400
            );
        }

        if ( strlen( $term ) > self::MAX_TERM_LENGTH ) {
            $term = substr( $term, 0, self::MAX_TERM_LENGTH );
        }
        if ( strlen( $instruction ) > self::MAX_INSTRUCTION_LENGTH ) {
            $instruction = substr( $instruction, 0, self::MAX_INSTRUCTION_LENGTH );
        }

        $prompt = $this->build_prompt( $term, $previous, $instruction );
        $system = __( 'You are a helpful assistant that writes concise, accurate glossary definitions in plain text. Do not use any Markdown or special formatting — no asterisks, bold, italics, headings, bullet points, backticks or code fences.', 'betterdocs-pro' );

        $result = $write_ai->generate_text( $prompt, $system );

        if ( empty( $result['success'] ) ) {
            $message = isset( $result['error'] ) ? (string) $result['error'] : __( 'Unknown AI error.', 'betterdocs-pro' );
            return $this->error( 'ai_upstream', $message, 502 );
        }

        $definition = $this->clean_response( (string) $result['content'] );

        if ( $definition === '' ) {
            return $this->error(
                'ai_empty_response',
                __( 'The AI returned no definition. Try again.', 'betterdocs-pro' ),
                502
            );
        }

        // Glossary terms are taxonomy terms generated before save — no reliable post id.
        AIUsage::record( 'glossaries_write_with_ai' );

        return $this->success(
            array(
                'definition' => $definition,
                'model'      => isset( $result['model'] ) ? $result['model'] : null
            )
        );
    }

    /**
     * Build the generation prompt. Ported from the former browser-side buildPrompt().
     */
    protected function build_prompt( $term, $previous_description, $instruction ) {
        $instruction = trim( $instruction );

        if ( $instruction !== '' && $previous_description !== '' ) {
            return implode(
                "\n",
                array(
                    sprintf( 'You are revising a glossary definition for the term "%s".', $term ),
                    'Previous definition: ' . $previous_description,
                    'Revise it based on this instruction: ' . $instruction,
                    'Return only the revised definition, 1-2 sentences, plain text, no Markdown, no asterisks, no heading or label.'
                )
            );
        }

        return sprintf(
            'Write a concise, plain-text glossary definition (1-2 sentences) for the term: %s. Return only the definition — no Markdown, no asterisks, no heading, no label, no quotes.',
            $term
        );
    }

    /**
     * Strip leading labels / dashes / surrounding quotes. Ported from cleanResponse().
     */
    protected function clean_response( $text ) {
        $text = trim( $text );
        $text = preg_replace( '/^\s*(glossary term|definition)\s*:\s*/i', '', $text );
        $text = preg_replace( '/^\s*[-–—]\s*/u', '', $text );
        $text = $this->strip_markdown( $text );
        $text = preg_replace( '/^["“”\']+|["“”\']+$/u', '', $text );

        return trim( $text );
    }

    /**
     * Safety net: strip common Markdown the model may still emit despite the
     * plain-text instruction, so definitions never render literal asterisks.
     */
    protected function strip_markdown( $text ) {
        $text = preg_replace( '/```.*?```/s', '', $text );          // fenced code blocks
        $text = preg_replace( '/\*\*(.+?)\*\*/s', '$1', $text );    // **bold**
        $text = preg_replace( '/__(.+?)__/s', '$1', $text );        // __bold__
        $text = preg_replace( '/`([^`]*)`/', '$1', $text );         // `inline code`
        $text = preg_replace( '/^\s{0,3}#{1,6}\s+/m', '', $text );  // # headings
        $text = preg_replace( '/^\s{0,3}[-*+]\s+/m', '', $text );   // -, *, + bullet markers

        return trim( $text );
    }
}
