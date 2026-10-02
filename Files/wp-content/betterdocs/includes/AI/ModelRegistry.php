<?php

namespace WPDeveloper\BetterDocs\AI;

/**
 * Curated, static catalogue of platforms and their chat models.
 *
 * Single source of truth for:
 *  - the platform selector + model dropdown in Settings (localized to React),
 *  - server-side validation that a (platform, model) pair is allowed,
 *  - the per-platform default model used when none is stored.
 *
 * Model identifiers move fast; update the lists here on new releases, or filter
 * `betterdocs_ai_models` to add/replace entries without touching core.
 *
 * @since 4.4.0
 */
class ModelRegistry {

    /**
     * Ordered list of supported platforms (id => label).
     *
     * @return array<string,string>
     */
    public static function platforms() {
        return apply_filters( 'betterdocs_ai_platforms', array(
            'openai'     => 'OpenAI',
            'gemini'     => 'Google Gemini',
            'claude'     => 'Anthropic Claude',
            'deepseek'   => 'DeepSeek',
            'openrouter' => 'OpenRouter',
        ) );
    }

    /**
     * Full model catalogue: platform => array( model_id => label ).
     *
     * NOTE: verify model ids against each provider's current docs at release
     * time. Anthropic/Gemini/OpenRouter ids in particular rotate frequently.
     *
     * @return array<string,array<string,string>>
     */
    public static function catalogue() {
        $catalogue = array(
            'openai' => array(
                'gpt-4o-mini'  => 'GPT-4o Mini',
                'gpt-4o'       => 'GPT-4o',
                'gpt-4.1-nano' => 'GPT-4.1 Nano',
                'gpt-4.1-mini' => 'GPT-4.1 Mini',
                'gpt-4.1'      => 'GPT-4.1',
                'gpt-5-nano'   => 'GPT-5 Nano',
                'gpt-5-mini'   => 'GPT-5 Mini',
                'gpt-5'        => 'GPT-5',
                'gpt-5.5'      => 'GPT-5.5',
            ),
            // Cheapest first. Every id here is verified callable with a newly issued
            // Google API key. `gemini-2.5-flash` / `-flash-lite` were removed: Google
            // closed them to new users, and they still appear in ListModels while
            // :generateContent answers 404 "no longer available to new users" — so the
            // failure only surfaces at the first real request. The `-latest` aliases
            // lead because Google re-points them and they cannot go stale that way.
            'gemini' => array(
                'gemini-flash-lite-latest' => 'Gemini Flash Lite (latest)',
                'gemini-flash-latest'      => 'Gemini Flash (latest)',
                'gemini-3.5-flash'         => 'Gemini 3.5 Flash',
                'gemini-3.6-flash'         => 'Gemini 3.6 Flash',
                'gemini-2.5-pro'           => 'Gemini 2.5 Pro',
                'gemini-pro-latest'        => 'Gemini Pro (latest)',
            ),
            // Cheapest/fastest first. `claude-opus-4-1` was removed because Anthropic
            // retires it on 2026-08-05 — offering it now would hand a new install a
            // model that starts 404ing within days of release.
            //
            // Opus 4.8, Opus 5 and Sonnet 5 reject `temperature` outright (400), and
            // Opus 5 / Sonnet 5 think by default with `max_tokens` covering thinking
            // AND the answer. ClaudeProvider handles both — see rejects_sampling()
            // and thinks_by_default() there before adding a model here.
            'claude' => array(
                'claude-haiku-4-5'  => 'Claude Haiku 4.5',
                'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
                'claude-sonnet-5'   => 'Claude Sonnet 5',
                'claude-opus-4-8'   => 'Claude Opus 4.8',
                'claude-opus-5'     => 'Claude Opus 5',
            ),
            'deepseek' => array(
                'deepseek-chat'     => 'DeepSeek Chat',
                'deepseek-reasoner' => 'DeepSeek Reasoner',
            ),
            'openrouter' => array(
                'openai/gpt-4o-mini'              => 'OpenAI: GPT-4o Mini',
                'openai/gpt-4o'                   => 'OpenAI: GPT-4o',
                'anthropic/claude-sonnet-4.5'     => 'Anthropic: Claude Sonnet 4.5',
                'google/gemini-2.5-flash'         => 'Google: Gemini 2.5 Flash',
                'deepseek/deepseek-chat'          => 'DeepSeek: Chat',
                'meta-llama/llama-3.3-70b-instruct' => 'Meta: Llama 3.3 70B',
            ),
        );

        return apply_filters( 'betterdocs_ai_models', $catalogue );
    }

    /**
     * Default model id per platform.
     *
     * @return array<string,string>
     */
    public static function defaults() {
        return apply_filters( 'betterdocs_ai_default_models', array(
            'openai'     => 'gpt-4o-mini',
            // Auto-rolling alias, not a pinned version: the previous default here was
            // gemini-2.5-flash, which Google has closed to new API keys, so every new
            // Gemini install started on a model that 404s.
            'gemini'     => 'gemini-flash-latest',
            // Sonnet 5 is the best speed/intelligence balance in the Claude line and
            // is safe as a default now that ClaudeProvider drops `temperature` and
            // raises the token floor for thinking models.
            'claude'     => 'claude-sonnet-5',
            'deepseek'   => 'deepseek-chat',
            'openrouter' => 'openai/gpt-4o-mini',
        ) );
    }

    /**
     * Models for one platform (model_id => label). Empty array if unknown.
     *
     * @param string $platform
     * @return array<string,string>
     */
    public static function models( $platform ) {
        $catalogue = self::catalogue();
        return isset( $catalogue[ $platform ] ) ? $catalogue[ $platform ] : array();
    }

    /**
     * Default model id for a platform, falling back to its first listed model.
     *
     * @param string $platform
     * @return string
     */
    public static function default_model( $platform ) {
        $defaults = self::defaults();
        if ( ! empty( $defaults[ $platform ] ) ) {
            return $defaults[ $platform ];
        }
        $models = self::models( $platform );
        if ( empty( $models ) ) {
            return '';
        }
        reset( $models );
        return (string) key( $models );
    }

    /**
     * Whether a platform id is supported.
     *
     * @param string $platform
     * @return bool
     */
    public static function has_platform( $platform ) {
        $platforms = self::platforms();
        return isset( $platforms[ $platform ] );
    }

    /**
     * Whether a (platform, model) pair exists in the catalogue.
     *
     * @param string $platform
     * @param string $model
     * @return bool
     */
    public static function has_model( $platform, $model ) {
        $models = self::models( $platform );
        return isset( $models[ $model ] );
    }
}
