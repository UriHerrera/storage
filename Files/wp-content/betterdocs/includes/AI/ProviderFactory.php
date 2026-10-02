<?php

namespace WPDeveloper\BetterDocs\AI;

use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\AI\Providers\OpenAIProvider;
use WPDeveloper\BetterDocs\AI\Providers\GeminiProvider;
use WPDeveloper\BetterDocs\AI\Providers\ClaudeProvider;
use WPDeveloper\BetterDocs\AI\Providers\DeepSeekProvider;
use WPDeveloper\BetterDocs\AI\Providers\OpenRouterProvider;

/**
 * Resolves the active AI provider from settings and constructs it with the
 * right per-platform API key and the global model. This is the single entry
 * point the content-suite features use; they never name a concrete provider.
 *
 * @since 4.4.0
 */
class ProviderFactory {

    /**
     * Per-platform API key settings are stored as ai_api_key_<platform>.
     *
     * Deliberately private: OpenAI does NOT follow this scheme (see
     * OPENAI_KEY_FIELD), so hand-concatenating the prefix would silently read
     * the wrong — always empty — setting for the default platform. Go through
     * key_field_for() instead; it is the only sanctioned way to name a key field.
     */
    private const KEY_PREFIX = 'ai_api_key_';

    /**
     * OpenAI keeps the original single-provider field name. Write with AI has
     * always been OpenAI-only, so the stored key is already the OpenAI key —
     * reusing the name means existing installs need no migration and no user
     * has to re-enter anything when multi-platform support lands.
     */
    const OPENAI_KEY_FIELD = 'ai_autowrite_api_key';

    /**
     * Settings key that holds the API key for a platform.
     *
     * @param string $platform
     * @return string
     */
    public static function key_field_for( $platform ) {
        return 'openai' === $platform ? self::OPENAI_KEY_FIELD : self::KEY_PREFIX . $platform;
    }

    /**
     * @var Settings
     */
    private $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Platform id => provider class. Filterable so Pro / add-ons can register
     * or replace providers without editing core.
     *
     * @return array<string,string>
     */
    public function provider_map() {
        return apply_filters( 'betterdocs_ai_providers', array(
            'openai'     => OpenAIProvider::class,
            'gemini'     => GeminiProvider::class,
            'claude'     => ClaudeProvider::class,
            'deepseek'   => DeepSeekProvider::class,
            'openrouter' => OpenRouterProvider::class,
        ) );
    }

    /**
     * Whether a platform id has a registered provider.
     *
     * @param string $platform
     * @return bool
     */
    public function is_supported( $platform ) {
        $map = $this->provider_map();
        return isset( $map[ $platform ] );
    }

    /**
     * Currently selected platform, defaulting to OpenAI.
     *
     * @return string
     */
    public function active_platform() {
        $platform = (string) $this->settings->get( 'ai_platform', 'openai' );
        return $this->is_supported( $platform ) ? $platform : 'openai';
    }

    /**
     * Stored API key for a platform.
     *
     * @param string $platform
     * @return string
     */
    public function api_key_for( $platform ) {
        return (string) $this->settings->get( self::key_field_for( $platform ), '' );
    }

    /**
     * Resolved global model for the active platform. Falls back through the
     * legacy per-feature model and then the platform default, so a not-yet
     * migrated install still resolves a valid model.
     *
     * @param string|null $platform
     * @return string
     */
    public function active_model( $platform = null ) {
        $platform = $platform ? $platform : $this->active_platform();
        $model    = (string) $this->settings->get( 'ai_model', '' );

        if ( '' !== $model && ModelRegistry::has_model( $platform, $model ) ) {
            return $model;
        }

        $legacy = (string) $this->settings->get( 'write_with_ai_model', '' );
        if ( '' !== $legacy && ModelRegistry::has_model( $platform, $legacy ) ) {
            return $legacy;
        }

        return ModelRegistry::default_model( $platform );
    }

    /**
     * Build the provider for the active platform (or an explicit one).
     *
     * @param string|null $platform Optional platform override.
     * @param string|null $model    Optional model override.
     * @return \WPDeveloper\BetterDocs\AI\Contracts\AIProvider
     */
    public function make( $platform = null, $model = null ) {
        $platform = $platform ? $platform : $this->active_platform();
        if ( ! $this->is_supported( $platform ) ) {
            $platform = 'openai';
        }

        $map   = $this->provider_map();
        $class = $map[ $platform ];
        $key   = $this->api_key_for( $platform );

        if ( null === $model ) {
            $model = ( $platform === $this->active_platform() )
                ? $this->active_model( $platform )
                : ModelRegistry::default_model( $platform );
        }

        return new $class( $key, $model );
    }

    /**
     * Build a provider with an explicit key/model — used by key validation
     * before anything is persisted.
     *
     * @param string $platform
     * @param string $api_key
     * @param string $model
     * @return \WPDeveloper\BetterDocs\AI\Contracts\AIProvider
     */
    public function make_with( $platform, $api_key, $model = '' ) {
        if ( ! $this->is_supported( $platform ) ) {
            $platform = 'openai';
        }
        $map   = $this->provider_map();
        $class = $map[ $platform ];
        return new $class( $api_key, $model );
    }

    /**
     * Validate a key for a platform.
     *
     * @param string $platform
     * @param string $api_key
     * @return array array( 'valid' => bool, 'message' => string )
     */
    public function validate( $platform, $api_key ) {
        return $this->make_with( $platform, $api_key )->validate_key( $api_key );
    }
}
