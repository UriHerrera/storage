<?php

namespace WPDeveloper\BetterDocs\AI\Providers;

/**
 * Base for platforms that speak the OpenAI Chat Completions wire format
 * (OpenAI itself, DeepSeek, OpenRouter, …). Subclasses only declare their id,
 * label, and base URL; everything else — payload, auth, parsing, key
 * validation — is shared here.
 *
 * @since 4.4.0
 */
abstract class OpenAICompatibleProvider extends BaseProvider {

    /**
     * API base URL without trailing slash, e.g. 'https://api.openai.com/v1'.
     *
     * @return string
     */
    abstract protected function base_url();

    /**
     * Chat completions endpoint.
     *
     * @return string
     */
    protected function chat_url() {
        return $this->base_url() . '/chat/completions';
    }

    /**
     * Endpoint used to validate a key (a cheap authenticated GET).
     *
     * @return string
     */
    protected function models_url() {
        return $this->base_url() . '/models';
    }

    /**
     * Authorization + any platform-specific headers.
     *
     * @param string $api_key
     * @return array
     */
    protected function request_headers( $api_key ) {
        return array_merge(
            array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            $this->extra_headers()
        );
    }

    /**
     * Optional extra headers (OpenRouter uses these for attribution).
     *
     * @return array
     */
    protected function extra_headers() {
        return array();
    }

    /**
     * Build the request body. Overridden by OpenAIProvider for the GPT-5 family.
     *
     * @param string      $model
     * @param array       $messages
     * @param int         $max_tokens
     * @param float|null  $temperature
     * @return array
     */
    protected function build_payload( $model, $messages, $max_tokens, $temperature = null ) {
        $payload = array(
            'model'      => $model,
            'messages'   => $messages,
            'max_tokens' => (int) $max_tokens,
        );
        if ( null !== $temperature ) {
            $payload['temperature'] = (float) $temperature;
        }
        return $payload;
    }

    /**
     * {@inheritDoc}
     */
    public function chat( $messages, $options = array() ) {
        if ( empty( $this->api_key ) ) {
            return new \WP_Error( 'no_api_key', sprintf(
                /* translators: %s: provider label */
                __( '%s API key is not configured.', 'betterdocs' ),
                $this->label()
            ) );
        }

        $model       = $this->resolve_model( $options );
        $context     = isset( $options['context'] ) ? $options['context'] : null;
        $max_tokens  = $this->floor_tokens(
            isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2500,
            $model,
            $context
        );
        $temperature = isset( $options['temperature'] ) ? $options['temperature'] : null;
        $timeout     = isset( $options['timeout'] ) ? $options['timeout'] : 50;

        $payload = $this->build_payload( $model, $messages, $max_tokens, $temperature );

        $status = null;
        $data   = $this->post_json( $this->chat_url(), $this->request_headers( $this->api_key ), $payload, $timeout, $status );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        if ( ! empty( $data['error'] ) ) {
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'betterdocs' );
            // Classify by HTTP status so a rate limit reads as one. Every platform on
            // this wire format (OpenAI, DeepSeek, OpenRouter) answers a quota/rate
            // rejection with 429 and a retired model with 404, but their raw text
            // differs per vendor — without this the REST layer relayed that text
            // verbatim as a bare `ai_upstream` 502 and the user never learned it was
            // a rate limit they could simply wait out.
            return new \WP_Error( 'provider_error', $this->classify_http_error( $status, $message, $model ) );
        }

        if ( ! isset( $data['choices'][0]['message']['content'] ) || '' === $data['choices'][0]['message']['content'] ) {
            return new \WP_Error( 'no_content', sprintf(
                /* translators: %s: provider label */
                __( 'No content received from %s.', 'betterdocs' ),
                $this->label()
            ) );
        }

        $usage = $this->normalize_usage(
            isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : array(),
            array( 'prompt' => 'prompt_tokens', 'completion' => 'completion_tokens', 'total' => 'total_tokens' )
        );

        return $this->success(
            $data['choices'][0]['message']['content'],
            isset( $data['model'] ) ? $data['model'] : $model,
            $usage,
            isset( $data['choices'][0]['finish_reason'] ) ? $data['choices'][0]['finish_reason'] : null
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validate_key( $api_key = '' ) {
        $api_key = $api_key !== '' ? $api_key : $this->api_key;

        if ( empty( $api_key ) ) {
            return array(
                'valid'   => false,
                'message' => __( 'Please insert your API key to use AI features.', 'betterdocs' ),
            );
        }

        $response = wp_remote_get( $this->models_url(), array(
            'headers' => $this->request_headers( $api_key ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'valid' => false, 'message' => $response->get_error_message() );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 === $code ) {
            return array( 'valid' => true, 'message' => __( 'Valid API Key', 'betterdocs' ) );
        }

        $body    = json_decode( wp_remote_retrieve_body( $response ), true );
        $message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Invalid API Key', 'betterdocs' );
        return array( 'valid' => false, 'message' => $message );
    }
}
