<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\AIUsage;

class AIEdit extends BaseAPI {

    const MAX_SELECTION_LENGTH   = 12000;
    const MAX_INSTRUCTION_LENGTH = 1000;

    public function register() {
        $this->post(
            '/ai-edit',
            array( $this, 'generate' ),
            array(
                'post_id' => array(
                    'type' => 'integer',
                    'required' => true
                ),
                'action' => array(
                    'type' => 'string',
                    'required' => true
                ),
                'selection' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                ),
                'selection_type' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => 'block'
                ),
                'instruction' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                ),
                'option' => array(
                    'type' => 'string',
                    'required' => false,
                    'default' => ''
                ),
                'instruction_ids' => array(
                    'type' => 'array',
                    'required' => false,
                    'default' => array()
                )
            )
        );
    }

    public function permission_check() {
        // Gate on edit_others_posts to match the sibling FAQ/Glossary AI endpoints
        // (AIFaq/AIGlossary) and keep Author-role users from spending the AI budget.
        return current_user_can( 'edit_others_posts' );
    }

    public function generate( WP_REST_Request $request ) {
        $write_ai = betterdocs()->ai_autowrtie;

        if ( empty( $write_ai ) || ! $write_ai->isEnabledWriteWithAI() ) {
            return $this->error(
                'ai_disabled',
                __( 'Write with AI is disabled. Enable it from BetterDocs settings.', 'betterdocs' ),
                400
            );
        }

        $api_key = $write_ai->get_api_key();
        if ( empty( $api_key ) ) {
            return $this->error(
                'ai_no_key',
                __( 'AI API key is missing. Add one in BetterDocs settings.', 'betterdocs' ),
                400
            );
        }

        $action         = sanitize_key( (string) $request->get_param( 'action' ) );
        $selection_type = sanitize_key( (string) $request->get_param( 'selection_type' ) );
        $selection      = (string) $request->get_param( 'selection' );
        $instruction    = (string) $request->get_param( 'instruction' );
        $option         = sanitize_text_field( (string) $request->get_param( 'option' ) );

        if ( strlen( $selection ) > self::MAX_SELECTION_LENGTH ) {
            $selection = substr( $selection, 0, self::MAX_SELECTION_LENGTH );
        }
        if ( strlen( $instruction ) > self::MAX_INSTRUCTION_LENGTH ) {
            $instruction = substr( $instruction, 0, self::MAX_INSTRUCTION_LENGTH );
        }

        $instruction = wp_kses_post( $instruction );

        // The selection is content to transform, sent verbatim in the OpenAI request
        // body (not rendered), and for block selections it is serialized Gutenberg
        // markup (`<!-- wp:… -->` delimiters + block HTML). wp_kses_post() would strip
        // anything off the post allowlist — inline SVG, embeds, data-* attributes — and
        // mangle block delimiters, so the model never sees them and they vanish on
        // Accept. Preserve the markup here; WordPress applies kses on the normal save
        // path per the user's capability. Only guard against malformed UTF-8.
        $selection   = wp_check_invalid_utf8( $selection );

        if ( 'inline' !== $selection_type ) {
            $selection_type = 'block';
        }

        $presets = self::get_presets();

        if ( 'custom' !== $action && ! isset( $presets[ $action ] ) ) {
            return $this->error(
                'ai_bad_action',
                __( 'Unknown AI action.', 'betterdocs' ),
                400
            );
        }

        if ( trim( wp_strip_all_tags( $instruction ) ) === '' ) {
            return $this->error(
                'ai_empty_instruction',
                __( 'Please provide a prompt for the AI.', 'betterdocs' ),
                400
            );
        }

        if ( trim( wp_strip_all_tags( $selection ) ) === '' && 'continue' !== $action ) {
            return $this->error(
                'ai_empty_selection',
                __( 'No content selected for the AI to work on.', 'betterdocs' ),
                400
            );
        }

        $prompt = $this->build_prompt( $action, $presets, $selection, $selection_type, $instruction, $option );

        // Selected instruction sets → extra system messages layered on the base prompt.
        $extra_system = $write_ai->get_instruction_messages( (array) $request->get_param( 'instruction_ids' ) );

        $result = $write_ai->generate_openai_response_ai_edit( $prompt, $extra_system );

        if ( empty( $result[ 'success' ] ) ) {
            $message = isset( $result[ 'error' ] ) ? (string) $result[ 'error' ] : __( 'Unknown AI error.', 'betterdocs' );
            return $this->error( 'ai_upstream', $message, 502 );
        }

        // Sanitize the model-generated HTML before it reaches the editor (rendered
        // via dangerouslySetInnerHTML in the Edit-with-AI preview): strip <script>,
        // event handlers, <iframe> and javascript: URLs while keeping valid markup.
        $content = wp_kses_post( $this->clean_output( (string) $result[ 'content' ] ) );

        if ( '' === $content ) {
            return $this->error(
                'ai_empty_response',
                __( 'The AI returned no content. Try again or rephrase your instruction.', 'betterdocs' ),
                502
            );
        }

        AIUsage::record( 'ai_edit', (int) $request->get_param( 'post_id' ), $action );

        return $this->success(
            array(
                'content' => $content,
                'action' => $action,
                'usage' => array(
                    'prompt_tokens' => isset( $result[ 'prompt_tokens' ] ) ? $result[ 'prompt_tokens' ] : null,
                    'completion_tokens' => isset( $result[ 'completion_tokens' ] ) ? $result[ 'completion_tokens' ] : null,
                    'total_tokens' => isset( $result[ 'total_tokens' ] ) ? $result[ 'total_tokens' ] : null
                ),
                'model' => isset( $result[ 'model' ] ) ? $result[ 'model' ] : null
            )
        );
    }

    protected function build_prompt( $action, $presets, $selection, $selection_type, $instruction, $option ) {
        $core = trim( $instruction );

        $preset      = 'custom' !== $action && isset( $presets[ $action ] ) ? $presets[ $action ] : null;
        $format_hint = isset( $preset[ 'format' ] ) && $preset[ 'format' ]
        ? $preset[ 'format' ]
        : ( 'inline' === $selection_type ? __( 'Return only plain text (no block markup, no quotes, no explanations).', 'betterdocs' )
            : __( 'Return only valid Gutenberg-compatible HTML using standard tags such as <p>, <h2>, <ul>, <ol>, <li>, <strong>, <em>, <a>, <code>. Do not include explanations, preambles, code fences, or markdown.', 'betterdocs' ) );

        $prompt = $core . "\n\n";
        $prompt .= __( 'Formatting requirements:', 'betterdocs' ) . ' ' . $format_hint . "\n\n";
        $prompt .= __( 'Content to work on:', 'betterdocs' ) . "\n";
        $prompt .= "---\n" . $selection . "\n---";

        return $prompt;
    }

    protected function clean_output( $content ) {
        $content = trim( $content );

        // Strip a leading fence with any info-string (```html, ```markdown, ```jsx,
        // ```text, or a bare ```), not just ```html — otherwise the info-string line
        // leaks into the block as literal text.
        $content = preg_replace( '/^```[a-z]*\s*\n?/i', '', $content );
        $content = preg_replace( '/\n?```\s*$/', '', $content );

        return trim( $content );
    }

    /**
     * The built-in Edit-with-AI actions, in display order.
     *
     * Single source of truth for the action chips (modal) and the prompt presets
     * (server). Each entry is the editable shape persisted under the `ai_edit_actions`
     * setting: { id, label, instruction, enabled, predefined }. Server-only metadata
     * (tone/language option, table formatting) lives in {@see self::predefined_extras()}
     * and is merged back in at resolve time — it is never stored or user-editable.
     *
     * @return array<int,array{id:string,label:string,instruction:string,enabled:bool,predefined:bool}>
     */
    public static function default_actions() {
        $defaults = array(
            'improve'     => array( __( 'Improve Writing', 'betterdocs' ), 'Improve the writing quality, clarity, grammar, and structure of the content below while preserving its original meaning and intent.' ),
            'shorten'     => array( __( 'Make Shorter', 'betterdocs' ), 'Make the content below more concise. Preserve all essential information; remove redundancy and filler.' ),
            'expand'      => array( __( 'Make Longer', 'betterdocs' ), 'Expand the content below with more detail, examples, and clear explanations. Keep the original voice.' ),
            'simplify'    => array( __( 'Simplify', 'betterdocs' ), 'Rewrite the content below in simpler language so it is easy to understand for a non-technical reader.' ),
            'fix_grammar' => array( __( 'Fix Grammar', 'betterdocs' ), 'Fix only the grammar, spelling, and punctuation in the content below. Do not rewrite or change the meaning or style.' ),
            'change_tone' => array( __( 'Change Tone', 'betterdocs' ), 'Rewrite the content below in a {option} tone while preserving its meaning.' ),
            'translate'   => array( __( 'Translate', 'betterdocs' ), 'Translate the content below into {option}. Preserve meaning, tone, and any HTML structure.' ),
            'summarize'   => array( __( 'Summarize', 'betterdocs' ), 'Summarize the content below into 2 to 3 clear sentences.' ),
            'create_table' => array( __( 'Create Table', 'betterdocs' ), 'Extract the statistics, figures, or comparable data from the content below and represent them as a single HTML table. Infer clear column headers. Include every distinct data point. If no tabular data can be reasonably inferred, return the best possible structured representation.' ),
        );

        $actions = array();
        foreach ( $defaults as $id => $pair ) {
            $actions[] = array(
                'id'          => $id,
                'label'       => $pair[0],
                'instruction' => $pair[1],
                'enabled'     => true,
                'predefined'  => true,
            );
        }

        return $actions;
    }

    /**
     * Server-only metadata for the predefined actions, keyed by id.
     *
     * Never persisted and not user-editable — merged into resolved presets so that
     * `change_tone`/`translate` keep their {option} substitution and `create_table`
     * keeps its single-<table> formatting contract.
     *
     * @return array<string,array{needs_option?:string,structural?:bool,format?:string}>
     */
    private static function predefined_extras() {
        return array(
            'change_tone'  => array( 'needs_option' => 'tone' ),
            'translate'    => array( 'needs_option' => 'language' ),
            'create_table' => array(
                'structural' => true,
                'format'     => 'Return only one complete <table> element with <thead> and <tbody>. Use <th scope="col"> for header cells. Do not include <style>, <script>, inline CSS classes, comments, explanations, preambles, code fences, or markdown.',
            ),
        );
    }

    /**
     * The resolved action list: stored overrides merged onto {@see self::default_actions()}.
     *
     * Predefined actions always appear (so newly shipped ones surface on sites with an
     * older stored value) carrying any saved enabled/label/instruction overrides; custom
     * (predefined:false) actions are appended in their stored order. Mirrors the
     * normalize-then-guarantee pattern of {@see \WPDeveloper\BetterDocs\Core\WriteWithAI::get_instructions()}.
     *
     * @return array<int,array{id:string,label:string,instruction:string,enabled:bool,predefined:bool}>
     */
    public static function get_actions() {
        $defaults = self::default_actions();

        $stored = betterdocs()->settings->get( 'ai_edit_actions', array() );
        $by_id  = array();
        if ( is_array( $stored ) ) {
            foreach ( $stored as $item ) {
                if ( ! is_array( $item ) || empty( $item['id'] ) ) {
                    continue;
                }
                $id            = sanitize_key( (string) $item['id'] );
                $by_id[ $id ]  = array(
                    'id'          => $id,
                    'label'       => isset( $item['label'] ) ? (string) $item['label'] : '',
                    'instruction' => isset( $item['instruction'] ) ? (string) $item['instruction'] : '',
                    'enabled'     => isset( $item['enabled'] ) ? (bool) $item['enabled'] : true,
                    'predefined'  => ! empty( $item['predefined'] ),
                );
            }
        }

        $actions   = array();
        $seen       = array();

        // Predefined actions first, in canonical order, with stored overrides applied.
        foreach ( $defaults as $default ) {
            $id          = $default['id'];
            $seen[ $id ] = true;
            $override    = isset( $by_id[ $id ] ) ? $by_id[ $id ] : array();
            $actions[]   = array(
                'id'          => $id,
                'label'       => $default['label'], // label is locked for predefined.
                'instruction' => isset( $override['instruction'] ) && '' !== trim( $override['instruction'] ) ? $override['instruction'] : $default['instruction'],
                'enabled'     => array_key_exists( 'enabled', $override ) ? $override['enabled'] : true,
                'predefined'  => true,
            );
        }

        // Custom actions (anything stored that isn't a predefined id), in stored order.
        foreach ( $by_id as $id => $item ) {
            if ( isset( $seen[ $id ] ) || $item['predefined'] ) {
                continue;
            }
            if ( '' === trim( $item['instruction'] ) ) {
                continue; // an action with no prompt would do nothing.
            }
            $actions[] = array(
                'id'          => $id,
                'label'       => '' !== trim( $item['label'] ) ? $item['label'] : $id,
                'instruction' => $item['instruction'],
                'enabled'     => $item['enabled'],
                'predefined'  => false,
            );
        }

        return $actions;
    }

    /**
     * The enabled actions exposed to the editor modal, in its expected shape.
     *
     * @return array<int,array{id:string,label:string,prompt:string,needsOption:(string|false),structural:bool}>
     */
    public static function get_localized_actions() {
        $extras  = self::predefined_extras();
        $actions = array();

        foreach ( self::get_actions() as $action ) {
            if ( empty( $action['enabled'] ) ) {
                continue;
            }
            $extra = isset( $extras[ $action['id'] ] ) ? $extras[ $action['id'] ] : array();
            $actions[] = array(
                'id'          => $action['id'],
                'label'       => $action['label'],
                'prompt'      => $action['instruction'],
                'needsOption' => isset( $extra['needs_option'] ) ? $extra['needs_option'] : false,
                'structural'  => ! empty( $extra['structural'] ),
            );
        }

        return $actions;
    }

    /**
     * The enabled actions as prompt presets, keyed by id, for {@see self::generate()}.
     *
     * Derived from {@see self::get_actions()} so the stored settings are the single
     * source of truth; disabled actions are absent and therefore rejected by the route.
     *
     * @return array<string,array{prompt:string,needs_option?:string,format?:string}>
     */
    public static function get_presets() {
        $extras  = self::predefined_extras();
        $presets = array();

        foreach ( self::get_actions() as $action ) {
            if ( empty( $action['enabled'] ) ) {
                continue;
            }
            $preset = array( 'prompt' => $action['instruction'] );
            if ( isset( $extras[ $action['id'] ] ) ) {
                $preset = array_merge( $preset, $extras[ $action['id'] ] );
            }
            $presets[ $action['id'] ] = $preset;
        }

        return $presets;
    }
}
