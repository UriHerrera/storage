<?php

namespace WPDeveloper\BetterDocs\AI\Contracts;

/**
 * Contract every AI chat provider must fulfil.
 *
 * A provider wraps a single platform's chat-completion API (OpenAI, Gemini,
 * Claude, DeepSeek, OpenRouter, …). The rest of BetterDocs talks to providers
 * only through this interface, so adding a platform is a new class — not a new
 * conditional sprinkled across the content-suite features.
 *
 * @since 4.4.0
 */
interface AIProvider {

    /**
     * Stable platform identifier stored in settings, e.g. 'openai'.
     *
     * @return string
     */
    public function id();

    /**
     * Human-readable platform name for the UI, e.g. 'OpenAI'.
     *
     * @return string
     */
    public function label();

    /**
     * Send a chat-completion request.
     *
     * Messages use the normalized shape:
     *   array( array( 'role' => 'system'|'user'|'assistant', 'content' => string ), … )
     *
     * Options (all optional):
     *   - model       string  Overrides the provider's configured model.
     *   - max_tokens  int     Output cap (floored by the feature min-token policy).
     *   - temperature float   Sampling temperature (ignored where unsupported).
     *   - context     string  Feature key for min-token enforcement, e.g. 'write_with_ai'.
     *   - timeout     int     Request timeout in seconds.
     *
     * On success returns the normalized result:
     *   array(
     *     'success'       => true,
     *     'content'       => string,
     *     'model'         => string,
     *     'usage'         => array( 'prompt_tokens'=>int|null, 'completion_tokens'=>int|null, 'total_tokens'=>int|null ),
     *     'finish_reason' => string|null,
     *   )
     *
     * @param array $messages
     * @param array $options
     * @return array|\WP_Error Normalized result or a WP_Error on failure.
     */
    public function chat( $messages, $options = array() );

    /**
     * Validate an API key against the platform.
     *
     * @param string $api_key Key to test; falls back to the configured key when empty.
     * @return array array( 'valid' => bool, 'message' => string )
     */
    public function validate_key( $api_key = '' );

    /**
     * Curated model list for this platform.
     *
     * @return array<string,string> model_id => label
     */
    public function models();

    /**
     * Default model id for this platform.
     *
     * @return string
     */
    public function default_model();
}
