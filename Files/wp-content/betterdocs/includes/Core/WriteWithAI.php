<?php

    namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


    use WPDeveloper\BetterDocs\Utils\Base;
    use WPDeveloper\BetterDocs\Core\Settings;
    use WPDeveloper\BetterDocs\Core\PostType;

    use WPDeveloper\BetterDocs\Utils\Helper;
    use WPDeveloper\BetterDocs\Utils\AIHelper;
    use WPDeveloper\BetterDocs\AI\ProviderFactory;
    use WPDeveloper\BetterDocs\AI\ModelRegistry;
    use WPDeveloper\BetterDocs\Utils\AIUsage;
    use WPDeveloper\BetterDocs\REST\AIEdit;

    class WriteWithAI extends Base {

    public $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
        // Get the post ID from the URL
        $post_id = isset( $_GET[ 'post' ] ) ? intval( $_GET[ 'post' ] ) : 0; // phpcs:ignore

        if ( ! empty( $_GET[ 'post_type' ] ) ) { // phpcs:ignore
            $post_type = $_GET[ 'post_type' ]; // phpcs:ignore
        } elseif ( $post_id > 0 ) {
            $post_type = get_post_type( $post_id );
        } else {
            $post_type = '';
        }

        if ( ! empty( $this->isEnabledWriteWithAI() ) && 'docs' == $post_type ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_ai_edit_assets' ) );
        }
        // Legacy AJAX handler kept for back-compat; the redesigned modal uses the REST route.
        add_action( 'wp_ajax_generate_openai_content', array( $this, 'generate_openai_content_callback' ) );
    }

    public function enqueue_ai_edit_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        global $post_type;
        if ( 'docs' !== $post_type ) {
            return;
        }

        $factory = new ProviderFactory( $this->settings );
        $api_key = $factory->api_key_for( $factory->active_platform() );
        $has_key = ! empty( $api_key );

        // Resolve the *active* AI platform + model (multi-platform aware) so the
        // modal reflects the current Settings → AI selection instead of the legacy
        // OpenAI-only `write_with_ai_model` key.
        $active_platform = $factory->active_platform();
        $active_model    = $factory->active_model();
        $platform_labels = ModelRegistry::platforms();
        $model_labels    = ModelRegistry::models( $active_platform );

        // Glossary suggestions are existing-terms-only, and the glossaries taxonomy is only
        // registered by Pro (see BetterDocsPro\Core\GlossaryTaxonomy::register_glossaries_taxonomy).
        // So the "Suggest glossaries" control must require, on top of the two settings, that Pro is
        // active AND at least one glossary term exists — otherwise the modal advertises an offer that can never
        // return anything. Mirrors the Docs-AI-suite availability check in Core\DocsAISuite.
        $glossary_count               = wp_count_terms( array( 'taxonomy' => 'glossaries', 'hide_empty' => false ) );
        $has_glossary_terms           = ! is_wp_error( $glossary_count ) && (int) $glossary_count > 0;
        $glossary_suggestions_enabled = (bool) $this->settings->get( 'enable_glossaries', false )
            && (bool) $this->settings->get( 'show_glossary_suggestions', true )
            && betterdocs()->is_pro_active()
            && $has_glossary_terms;

        // Write with AI — loads even without a key so the modal can show its
        // "add an API key" banner (matches the legacy inline form behavior).
        betterdocs()->assets->enqueue( 'betterdocs-write-with-ai', 'blocks/write-with-ai.js' );
        betterdocs()->assets->enqueue( 'betterdocs-write-with-ai-style', 'blocks/write-with-ai-style.css' );

        wp_localize_script(
            'betterdocs-write-with-ai',
            'betterdocsWriteWithAI',
            array(
                'rest_url'               => esc_url_raw( rest_url( 'betterdocs/v1/write-with-ai' ) ),
                'rest_nonce'             => wp_create_nonce( 'wp_rest' ),
                'post_id'                => get_the_ID(),
                'has_key'                => $has_key,
                // Term-suggestion (Docs AI Suite) wiring reused by the Write-with-AI
                // preview step. Endpoint + gate mirror REST\DocsAISuite / Core\DocsAISuite.
                'rest_suggest_url'             => esc_url_raw( rest_url( 'betterdocs/v1/ai-suggest-terms' ) ),
                'suggest_terms_enabled'        => (bool) $this->settings->get( 'enable_docs_ai_suite', true ),
                // Glossary suggestions follow the glossary feature AND real availability
                // (Pro active + at least one glossary term); see the computation above.
                'glossary_suggestions_enabled' => $glossary_suggestions_enabled,
                'platform'               => $active_platform,
                'platform_label'         => isset( $platform_labels[ $active_platform ] ) ? $platform_labels[ $active_platform ] : ucfirst( (string) $active_platform ),
                'model'                  => $active_model,
                'model_label'            => isset( $model_labels[ $active_model ] ) ? $model_labels[ $active_model ] : $active_model,
                'max_token'              => (int) $this->settings->get( 'ai_autowrite_max_token', 2500 ),
                'settings_url'           => esc_url( admin_url( 'admin.php?page=betterdocs-settings#betterdocs-ai' ) ),
                'woo_active'             => class_exists( 'WooCommerce' ),
                'is_multilingual_active' => Helper::is_multilingual_active(),
                'language_options'       => Helper::get_active_languages(),
                'instructions'           => $this->get_instruction_choices(),
                // From Git is a Pro feature. Pro is detected here; Git enabled/auth
                // status is filled in by Pro via the filter below (default: off).
                'is_pro_active'          => betterdocs()->is_pro_active(),
                'git'                    => apply_filters(
                    'betterdocs_write_with_ai_git',
                    array(
                        'enabled'      => false,
                        'connected'    => false,
                        'settings_url' => admin_url( 'admin.php?page=betterdocs-settings#git-sync' ),
                    )
                ),
            )
        );

        // AI Edit — only meaningful with a key configured.
        if ( ! $has_key ) {
            return;
        }

        betterdocs()->assets->enqueue( 'betterdocs-ai-edit', 'blocks/ai-edit.js' );
        betterdocs()->assets->enqueue( 'betterdocs-ai-edit-style', 'blocks/ai-edit-style.css' );

        wp_localize_script(
            'betterdocs-ai-edit',
            'betterdocsAIEdit',
            array(
                'rest_url' => esc_url_raw( rest_url( 'betterdocs/v1/ai-edit' ) ),
                'rest_nonce' => wp_create_nonce( 'wp_rest' ),
                'post_id' => get_the_ID(),
                'instructions' => $this->get_instruction_choices(),
                'actions' => AIEdit::get_localized_actions()
            )
        );
    }

    public function isEnabledWriteWithAI() {
        $isEnableAutoWrite = $this->settings->get( 'enable_write_with_ai', true );
        return $isEnableAutoWrite;
    }

    public function isValidAPIKey( $apiKey ) {
        if ( empty( $apiKey ) ) {
            return array(
                'valid'   => false,
                'message' => 'Please Insert your <a href="/admin.php?page=betterdocs-settings#betterdocs-ai">API Key</a> to use this Write with AI feature.'
            );
        }

        $factory = new ProviderFactory( $this->settings );
        return $factory->validate( $factory->active_platform(), $apiKey );
    }

    public function get_api_key() {
        $factory = new ProviderFactory( $this->settings );
        return $factory->api_key_for( $factory->active_platform() );
    }

    /**
     * The built-in "Default" instruction body.
     *
     * Single source of truth shared by {@see Settings::get_default()} (which seeds
     * the editable "Default" instruction set) and {@see self::get_system_prompt()}
     * (the fallback when no saved Default content exists).
     *
     * @return string
     */
    public static function default_instruction_content() {
        return <<<'PROMPT'
You are a Senior Technical Writer specializing in comprehensive, high-quality documentation. Your goal is to produce documentation that scores 100/100 on clarity, completeness, and structure.

## Output format

Return only HTML body content. Never wrap output in `<!doctype>`, `<html>`, `<head>`, `<body>`, or markdown code fences (no ```html ... ```).

Use semantic, Gutenberg-friendly tags only:
- Headings: `<h2>`, `<h3>`, `<h4>` (do not emit `<h1>` — it is reserved for the document title)
- Paragraphs: `<p>` for prose
- Lists: `<ul>`/`<ol>` with `<li>` for any list of items, steps, or bullet points — never fake a list with paragraphs or `<br>`
- Links: `<a href="https://...">link text</a>` with absolute URLs
- Inline emphasis: `<strong>`, `<em>`, `<code>`
- Code blocks: `<pre><code>...</code></pre>` for multi-line code or commands
- Quotes: `<blockquote>`
- Tables: `<table>` with `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`
- Images: `<img src="..." alt="...">`

Apply `<span class="highlight">key term</span>` to important topic terms inside headings and to the first occurrence of each keyword in body text. Use it sparingly — never wrap whole sentences or wrap text inside `href`, `src`, `alt`, or other attributes.

Do not emit `<style>`, `<script>`, `<iframe>`, `<form>`, or inline `style=""` / `class=""` attributes (other than the `highlight` class above).

If — and only if — the user's prompt explicitly asks for raw/custom HTML, embed code, or a non-standard structure, follow that request instead of these defaults.
PROMPT;
    }

    /**
     * The saved instruction sets, normalized to a list of { id, title, content }.
     *
     * Stored in the `write_with_ai_instructions` setting. Always returns at least
     * the built-in "Default" set so callers never have to special-case an empty
     * option (e.g. a site whose stored value predates this feature).
     *
     * @return array<int,array{id:string,title:string,content:string}>
     */
    public function get_instructions() {
        $stored = $this->settings->get( 'write_with_ai_instructions', array() );

        $instructions = array();
        if ( is_array( $stored ) ) {
            foreach ( $stored as $item ) {
                if ( ! is_array( $item ) || empty( $item['id'] ) ) {
                    continue;
                }
                $instructions[] = array(
                    'id'      => sanitize_key( (string) $item['id'] ),
                    'title'   => isset( $item['title'] ) ? (string) $item['title'] : '',
                    'content' => isset( $item['content'] ) ? (string) $item['content'] : '',
                );
            }
        }

        // Guarantee a Default set is always present.
        $has_default = false;
        foreach ( $instructions as $item ) {
            if ( 'default' === $item['id'] ) {
                $has_default = true;
                break;
            }
        }
        if ( ! $has_default ) {
            array_unshift(
                $instructions,
                array(
                    'id'      => 'default',
                    'title'   => __( 'Default/Core', 'betterdocs' ),
                    'content' => self::default_instruction_content(),
                )
            );
        }

        return $instructions;
    }

    /**
     * The selectable (non-default) instruction sets exposed to the editor UI.
     *
     * Only id + title are sent to the browser; the prompt body stays server-side
     * and is resolved at request time by {@see self::get_instruction_messages()}.
     *
     * @return array<int,array{id:string,title:string}>
     */
    public function get_instruction_choices() {
        $choices = array();
        foreach ( $this->get_instructions() as $item ) {
            if ( 'default' === $item['id'] ) {
                continue;
            }
            if ( '' === trim( $item['content'] ) ) {
                continue; // skip empty sets — they'd inject nothing.
            }
            $choices[] = array(
                'id'    => $item['id'],
                'title' => '' !== trim( $item['title'] ) ? $item['title'] : $item['id'],
            );
        }
        return $choices;
    }

    /**
     * Resolve selected instruction ids into extra `system` messages.
     *
     * The "Default" set is intentionally excluded — it is always sent as the base
     * system prompt by {@see self::get_system_prompt()}. Unknown or empty ids are
     * silently ignored. Output order follows the requested $ids order.
     *
     * @param array $ids
     * @return array<int,array{role:string,content:string}>
     */
    public function get_instruction_messages( $ids ) {
        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return array();
        }

        $by_id = array();
        foreach ( $this->get_instructions() as $item ) {
            $by_id[ $item['id'] ] = $item;
        }

        $messages = array();
        $seen     = array();
        foreach ( $ids as $id ) {
            $id = sanitize_key( (string) $id );
            if ( 'default' === $id || isset( $seen[ $id ] ) || ! isset( $by_id[ $id ] ) ) {
                continue;
            }
            $content = trim( $by_id[ $id ]['content'] );
            if ( '' === $content ) {
                continue;
            }
            $seen[ $id ] = true;
            $messages[]  = array(
                'role'    => 'system',
                'content' => $content,
            );
        }

        return $messages;
    }

    /**
     * Coerce a list of extra messages into well-formed `system` chat messages.
     *
     * Accepts either pre-built [ 'role'=>'system', 'content'=>… ] entries (as
     * returned by {@see self::get_instruction_messages()}) or bare strings, and
     * drops anything empty. Keeps the OpenAI payload builders tolerant of either
     * shape so callers can pass instruction ids or ready-made messages.
     *
     * @param array $extra_system
     * @return array<int,array{role:string,content:string}>
     */
    protected function normalize_extra_system( $extra_system ) {
        if ( empty( $extra_system ) || ! is_array( $extra_system ) ) {
            return array();
        }

        $messages = array();
        foreach ( $extra_system as $entry ) {
            if ( is_string( $entry ) ) {
                $content = trim( $entry );
                $role    = 'system';
            } elseif ( is_array( $entry ) && isset( $entry['content'] ) ) {
                $content = trim( (string) $entry['content'] );
                $role    = isset( $entry['role'] ) ? (string) $entry['role'] : 'system';
            } else {
                continue;
            }

            if ( '' === $content ) {
                continue;
            }

            $messages[] = array(
                'role'    => $role,
                'content' => $content,
            );
        }

        return $messages;
    }

    public function get_system_prompt() {
        // Prefer the saved (editable) "Default" instruction set; fall back to the
        // built-in body when it's missing or has been cleared.
        $prompt = self::default_instruction_content();
        foreach ( $this->get_instructions() as $item ) {
            if ( 'default' === $item['id'] && '' !== trim( $item['content'] ) ) {
                $prompt = $item['content'];
                break;
            }
        }

        return apply_filters( 'betterdocs_write_with_ai_system_prompt', $prompt );
    }

    /**
     * Build the system + user messages and chat options shared by the
     * Write-with-AI generator and the in-editor AI-Edit endpoint. Routes through
     * the active AI platform (ProviderFactory), so it works for every provider.
     *
     * @param string $prompt
     * @param int|null $max_tokens   Optional cap override (e.g. a "large" doc).
     * @param array  $extra_system   Optional extra system messages.
     * @return array array( array $messages, array $options )
     */
    private function ai_request_args( $prompt, $max_tokens = null, $extra_system = array() ) {
        $messages = array_merge(
            array( array( 'role' => 'system', 'content' => $this->get_system_prompt() ) ),
            $this->normalize_extra_system( $extra_system ),
            array( array( 'role' => 'user', 'content' => $prompt ) )
        );

        return array( $messages, $this->ai_chat_options( $max_tokens ) );
    }

    /**
     * Assemble the chat options passed to the active provider. Document
     * generation is long-running on every platform: reasoning models (gpt-5*)
     * and larger non-OpenAI models (e.g. Claude Opus) routinely need well over
     * the old 50s default to return a full doc, which timed them out mid-
     * generation. Give every request a generous ceiling; fast models simply
     * finish early and are unaffected.
     *
     * @param int|null   $max_tokens  Optional token cap override.
     * @param float|null $temperature Optional sampling temperature.
     * @return array
     */
    private function ai_chat_options( $max_tokens = null, $temperature = null ) {
        $timeout = 300;
        if ( function_exists( 'set_time_limit' ) ) {
            set_time_limit( 300 );
        }

        $options = array(
            'max_tokens' => null !== $max_tokens ? (int) $max_tokens : (int) $this->settings->get( 'ai_autowrite_max_token', 2500 ),
            'context'    => 'write_with_ai',
            'timeout'    => $timeout,
        );

        if ( null !== $temperature ) {
            $options['temperature'] = $temperature;
        }

        return $options;
    }

    public function generate_openai_response( $prompt, $keywords, $max_tokens = null, $extra_system = array() ) {
        try {
            list( $messages, $options ) = $this->ai_request_args( $prompt, $max_tokens, $extra_system );

            $result = ( new ProviderFactory( $this->settings ) )->make()->chat( $messages, $options );

            if ( is_wp_error( $result ) ) {
                return $result->get_error_message();
            }

            return $result['content'];
        } catch ( \Exception $error ) {
            return 'Error: ' . $error->getMessage();
        }
    }

    /**
     * Generate documentation from an uploaded image using a vision-capable model.
     *
     * Mirrors generate_openai_response() but sends the picture alongside the text
     * prompt as an OpenAI-format multimodal user message
     * (`content: [ {type:text}, {type:image_url} ]`). The OpenAI-compatible
     * provider forwards that message array to the wire verbatim, so no provider
     * change is needed. Only OpenAI vision models are wired — Claude and Gemini
     * use a different image envelope, so they are refused with a clear error
     * instead of being sent a payload they would reject.
     *
     * @param string   $prompt       Composed instruction prompt.
     * @param array    $image        { data_uri:string, mime:string }.
     * @param int|null $max_tokens   Optional token cap.
     * @param array    $extra_system Extra system messages (instruction sets).
     * @return string|\WP_Error Generated content, or WP_Error on guard/failure.
     */
    public function generate_vision_response( $prompt, $image, $max_tokens = null, $extra_system = array() ) {
        if ( empty( $image['data_uri'] ) ) {
            return new \WP_Error( 'ai_vision_no_image', __( 'No image data to send to the AI.', 'betterdocs' ) );
        }

        $factory  = new ProviderFactory( $this->settings );
        $platform = $factory->active_platform();
        $model    = $factory->active_model( $platform );

        if ( ! $this->platform_supports_vision( $platform, $model ) ) {
            return new \WP_Error(
                'ai_no_vision',
                sprintf(
                    /* translators: 1: AI platform id, 2: model name. */
                    __( 'The configured AI model (%1$s / %2$s) can\'t read images. Switch to an OpenAI vision model such as GPT-4o or GPT-4o mini in BetterDocs → Settings → AI Content Suite, or upload a PDF/DOCX/TXT instead.', 'betterdocs' ),
                    $platform,
                    '' !== (string) $model ? $model : 'default'
                )
            );
        }

        try {
            $messages = array_merge(
                array( array( 'role' => 'system', 'content' => $this->get_system_prompt() ) ),
                $this->normalize_extra_system( $extra_system ),
                array(
                    array(
                        'role'    => 'user',
                        'content' => array(
                            array( 'type' => 'text', 'text' => (string) $prompt ),
                            array( 'type' => 'image_url', 'image_url' => array( 'url' => (string) $image['data_uri'] ) ),
                        ),
                    ),
                )
            );

            $result = $factory->make()->chat( $messages, $this->ai_chat_options( $max_tokens ) );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            return $result['content'];
        } catch ( \Exception $error ) {
            return new \WP_Error( 'ai_vision_failed', 'Error: ' . $error->getMessage() );
        }
    }

    /**
     * Whether the active platform + model can accept image input in the OpenAI
     * multimodal format. Deliberately conservative: only OpenAI vision model
     * families qualify, because Claude and Gemini require a different image
     * envelope this path does not build. gpt-3.5 (text-only) is excluded.
     *
     * @param string $platform
     * @param string $model
     * @return bool
     */
    protected function platform_supports_vision( $platform, $model ) {
        if ( 'openai' !== $platform ) {
            return false;
        }

        $model = strtolower( (string) $model );

        if ( '' === $model || false !== strpos( $model, 'gpt-3.5' ) ) {
            return false;
        }

        foreach ( array( 'gpt-4o', 'gpt-4.1', 'gpt-4-turbo', 'gpt-4-vision', 'chatgpt-4o', 'gpt-5', 'o1', 'o3', 'o4' ) as $family ) {
            if ( false !== strpos( $model, $family ) ) {
                return true;
            }
        }

        return false;
    }

    public function get_outline_system_prompt() {
        $prompt = <<<'PROMPT'
You are a Senior Technical Writer. Produce a documentation OUTLINE only — not the full article.

Return ONLY a JSON array (no prose, no markdown, no code fences). Each element is an object:
  { "level": "h2" | "h3", "text": "Section heading" }

Rules:
- 5 to 9 top-level "h2" sections that comprehensively cover the topic.
- Add "h3" sub-sections only where they genuinely help; keep each one directly after its parent h2.
- Headings are concise, descriptive, and free of numbering.
- Do not include an h1/title and do not include any text outside the JSON array.
PROMPT;

        return apply_filters( 'betterdocs_write_with_ai_outline_system_prompt', $prompt );
    }

    /**
     * Generate a documentation outline as a structured array of sections.
     *
     * @param string $user_prompt The composed prompt (title/keywords/instructions).
     * @return array { success:bool, outline?:array<int,array{level:string,text:string}>, error?:string }
     */
    public function generate_outline_response( $user_prompt, $extra_system = array() ) {
        $result = $this->generate_text( $user_prompt, $this->get_outline_system_prompt(), $extra_system );

        if ( empty( $result['success'] ) ) {
            return array(
                'success' => false,
                'error'   => isset( $result['error'] ) ? $result['error'] : 'AI error',
            );
        }

        $outline = $this->parse_outline( (string) $result['content'] );

        if ( empty( $outline ) ) {
            return array(
                'success' => false,
                'error'   => 'The AI returned an outline we could not read. Please try again.',
            );
        }

        return array(
            'success' => true,
            'outline' => $outline,
        );
    }

    /**
     * Parse a model outline response (ideally a JSON array) into a clean list of
     * { level, text } sections. Tolerates code fences and stray prose.
     *
     * @param string $content
     * @return array<int,array{level:string,text:string}>
     */
    protected function parse_outline( $content ) {
        $content = trim( $content );
        $content = preg_replace( '/^```(?:json)?\s*/i', '', $content );
        $content = preg_replace( '/```\s*$/', '', $content );

        // Grab the first JSON array if the model wrapped it in prose.
        if ( preg_match( '/\[[\s\S]*\]/', $content, $m ) ) {
            $content = $m[0];
        }

        $decoded = json_decode( $content, true );
        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $outline = array();
        foreach ( $decoded as $item ) {
            if ( ! is_array( $item ) || empty( $item['text'] ) ) {
                continue;
            }
            $level = isset( $item['level'] ) && 'h3' === strtolower( (string) $item['level'] ) ? 'h3' : 'h2';
            $text  = sanitize_text_field( (string) $item['text'] );
            if ( '' === $text ) {
                continue;
            }
            $outline[] = array( 'level' => $level, 'text' => $text );
        }

        return $outline;
    }

    public function generate_openai_response_ai_edit( $prompt, $extra_system = array() ) {
        $factory = new ProviderFactory( $this->settings );
        $model   = $factory->active_model();

        $messages = array_merge(
            array( array( 'role' => 'system', 'content' => $this->get_system_prompt() ) ),
            $this->normalize_extra_system( $extra_system ),
            array( array( 'role' => 'user', 'content' => $prompt ) )
        );

        $result = $factory->make()->chat( $messages, $this->ai_chat_options() );

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'error'   => $result->get_error_message(),
                'model'   => $model
            );
        }

        $usage = isset( $result['usage'] ) && is_array( $result['usage'] ) ? $result['usage'] : array();

        return array(
            'success'           => true,
            'content'           => $result['content'],
            'model'             => isset( $result['model'] ) ? $result['model'] : $model,
            'prompt_tokens'     => isset( $usage['prompt_tokens'] ) ? $usage['prompt_tokens'] : null,
            'completion_tokens' => isset( $usage['completion_tokens'] ) ? $usage['completion_tokens'] : null,
            'total_tokens'      => isset( $usage['total_tokens'] ) ? $usage['total_tokens'] : null,
            'finish_reason'     => isset( $result['finish_reason'] ) ? $result['finish_reason'] : null
        );
    }

    /**
     * Generic chat completion using the active Write-with-AI platform/model/token
     * settings. Shared by lightweight, plain-text generators (glossary definitions,
     * FAQ answers, outlines) that need the same dynamic model as Write with AI /
     * AI Edit but a caller-supplied system prompt instead of the doc-authoring HTML
     * prompt. Routes through ProviderFactory so every provider is supported.
     *
     * @param string $user_prompt   The user message.
     * @param string $system_prompt Optional system message (omitted when empty).
     * @param array  $extra_system  Optional extra system messages.
     * @return array { success:bool, content?:string, error?:string, model:string, *_tokens?:int }
     */
    public function generate_text( $user_prompt, $system_prompt = '', $extra_system = array() ) {
        $factory = new ProviderFactory( $this->settings );
        $model   = $factory->active_model();

        $messages = array();
        if ( $system_prompt !== '' ) {
            $messages[] = array( 'role' => 'system', 'content' => $system_prompt );
        }
        foreach ( $this->normalize_extra_system( $extra_system ) as $extra ) {
            $messages[] = $extra;
        }
        $messages[] = array( 'role' => 'user', 'content' => $user_prompt );

        $result = $factory->make()->chat( $messages, $this->ai_chat_options() );

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'error'   => $result->get_error_message(),
                'model'   => $model
            );
        }

        $usage = isset( $result['usage'] ) && is_array( $result['usage'] ) ? $result['usage'] : array();

        return array(
            'success'           => true,
            'content'           => $result['content'],
            'model'             => isset( $result['model'] ) ? $result['model'] : $model,
            'prompt_tokens'     => isset( $usage['prompt_tokens'] ) ? $usage['prompt_tokens'] : null,
            'completion_tokens' => isset( $usage['completion_tokens'] ) ? $usage['completion_tokens'] : null,
            'total_tokens'      => isset( $usage['total_tokens'] ) ? $usage['total_tokens'] : null,
            'finish_reason'     => isset( $result['finish_reason'] ) ? $result['finish_reason'] : null
        );
    }

    /**
     * Generate a chat completion with a caller-supplied system + user prompt.
     * Mirrors generate_openai_response_ai_edit() (same model/token floor/timeout
     * handling and return shape) but does NOT force the documentation-writer
     * system prompt, so callers such as the Docs AI Suite (taxonomy suggestions,
     * excerpts) can supply task-appropriate instructions. Routes through the active
     * provider via ProviderFactory.
     *
     * @param string     $system_prompt System instruction for the model.
     * @param string     $user_prompt   User message / content payload.
     * @param float|null $temperature   Optional sampling temperature (ignored for gpt-5*).
     * @return array{success:bool,content?:string,error?:string,model:string,...}
     */
    public function generate_openai_response_raw( $system_prompt, $user_prompt, $temperature = null ) {
        $factory = new ProviderFactory( $this->settings );
        $model   = $factory->active_model();

        $messages = array(
            array( 'role' => 'system', 'content' => $system_prompt ),
            array( 'role' => 'user', 'content' => $user_prompt )
        );

        $result = $factory->make()->chat( $messages, $this->ai_chat_options( null, $temperature ) );

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'error'   => $result->get_error_message(),
                'model'   => $model
            );
        }

        $usage = isset( $result['usage'] ) && is_array( $result['usage'] ) ? $result['usage'] : array();

        return array(
            'success'           => true,
            'content'           => $result['content'],
            'model'             => isset( $result['model'] ) ? $result['model'] : $model,
            'prompt_tokens'     => isset( $usage['prompt_tokens'] ) ? $usage['prompt_tokens'] : null,
            'completion_tokens' => isset( $usage['completion_tokens'] ) ? $usage['completion_tokens'] : null,
            'total_tokens'      => isset( $usage['total_tokens'] ) ? $usage['total_tokens'] : null,
            'finish_reason'     => isset( $result['finish_reason'] ) ? $result['finish_reason'] : null
        );
    }

    public function generate_openai_content_callback() {
        // Verify the nonce
        $ai_nonce = isset( $_POST['ai_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $ai_nonce, 'generate_openai_content_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce' );
            wp_die();
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
            wp_die();
        }

        $prompt   = isset( $_POST['prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $keywords = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';

        $ai_instance = new WriteWithAI( $this->settings );

        $generated_content = $ai_instance->generate_openai_response( $prompt, $keywords );

        // Count a successful generation (skip obvious upstream errors).
        if ( is_string( $generated_content ) && $generated_content !== '' && strpos( $generated_content, 'Error:' ) !== 0 ) {
            $post_id = isset( $_POST[ 'post_id' ] ) ? intval( $_POST[ 'post_id' ] ) : 0; //phpcs:ignore
            AIUsage::record( 'write_with_ai', $post_id );
        }

        // Send the generated content as the AJAX response
        wp_send_json_success( $generated_content );
        wp_die();
    }
}
