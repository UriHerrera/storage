<?php

namespace WPDeveloper\BetterDocs\AI\Providers;

/**
 * DeepSeek provider. DeepSeek exposes an OpenAI-compatible Chat Completions
 * API, so only the base URL differs.
 *
 * @since 4.4.0
 */
class DeepSeekProvider extends OpenAICompatibleProvider {

    public function id() {
        return 'deepseek';
    }

    public function label() {
        return 'DeepSeek';
    }

    protected function base_url() {
        return 'https://api.deepseek.com/v1';
    }
}
