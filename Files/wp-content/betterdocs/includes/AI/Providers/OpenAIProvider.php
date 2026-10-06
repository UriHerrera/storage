<?php

namespace WPDeveloper\BetterDocs\AI\Providers;

use WPDeveloper\BetterDocs\Utils\AIHelper;

/**
 * OpenAI Chat Completions provider.
 *
 * Adds the GPT-5 family handling on top of the shared OpenAI-compatible base:
 * GPT-5 models require `max_completion_tokens`, reject a custom temperature, and
 * bill internal reasoning against the output budget — so we send a low
 * `reasoning_effort` to avoid empty completions. The original GPT-5 generation
 * accepts `minimal`; the gpt-5.x point releases (e.g. gpt-5.5) dropped it and
 * need `none` instead (see default_reasoning_effort()).
 *
 * @since 4.4.0
 */
class OpenAIProvider extends OpenAICompatibleProvider {

    public function id() {
        return 'openai';
    }

    public function label() {
        return 'OpenAI';
    }

    protected function base_url() {
        return 'https://api.openai.com/v1';
    }

    /**
     * {@inheritDoc}
     */
    protected function build_payload( $model, $messages, $max_tokens, $temperature = null ) {
        if ( 0 === strpos( (string) $model, 'gpt-5' ) ) {
            return array(
                'model'                 => $model,
                'messages'              => $messages,
                'max_completion_tokens' => (int) $max_tokens,
                'reasoning_effort'      => apply_filters( 'betterdocs_openai_gpt5_reasoning_effort', $this->default_reasoning_effort( $model ), $model, $max_tokens ),
            );
        }

        return parent::build_payload( $model, $messages, $max_tokens, $temperature );
    }

    /**
     * Default reasoning_effort for a gpt-5* model.
     *
     * The original GPT-5 generation (gpt-5, gpt-5-mini, gpt-5-nano) accepts
     * 'minimal'. The gpt-5.x point releases (e.g. gpt-5.5) dropped 'minimal'
     * from the API and only accept none|low|medium|high; sending 'minimal'
     * returns a 400 "Unsupported value: 'reasoning_effort'". For those we
     * default to 'none' — no reasoning tokens, the fastest option, leaving the
     * whole token budget for visible output (closest to gpt-5 'minimal'
     * behaviour). Override per model via the
     * betterdocs_openai_gpt5_reasoning_effort filter.
     *
     * @param string $model OpenAI model identifier.
     * @return string reasoning_effort value.
     */
    protected function default_reasoning_effort( $model ) {
        return AIHelper::is_gpt5_point_release( $model ) ? 'none' : 'minimal';
    }
}
