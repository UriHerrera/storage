<?php

namespace WPDeveloper\BetterDocs\AI\Providers;

/**
 * OpenRouter provider — one key, many upstream models, all behind an
 * OpenAI-compatible Chat Completions API. OpenRouter recommends sending
 * HTTP-Referer and X-Title headers for attribution/ranking.
 *
 * @since 4.4.0
 */
class OpenRouterProvider extends OpenAICompatibleProvider {

    public function id() {
        return 'openrouter';
    }

    public function label() {
        return 'OpenRouter';
    }

    protected function base_url() {
        return 'https://openrouter.ai/api/v1';
    }

    /**
     * {@inheritDoc}
     */
    protected function extra_headers() {
        return apply_filters( 'betterdocs_openrouter_headers', array(
            'HTTP-Referer' => home_url(),
            'X-Title'      => get_bloginfo( 'name' ),
        ) );
    }
}
