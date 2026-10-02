<?php

namespace WPDeveloper\BetterDocs\AI\Providers;

/**
 * Anthropic Claude provider (Messages API).
 *
 * Differences from OpenAI: auth via `x-api-key` + `anthropic-version`, the
 * system prompt is a top-level `system` string (not a message), `max_tokens`
 * is required, and the reply text lives in `content[].text` blocks.
 *
 * @since 4.4.0
 */
class ClaudeProvider extends BaseProvider {

    /**
     * Anthropic API version header value.
     */
    const ANTHROPIC_VERSION = '2023-06-01';

    public function id() {
        return 'claude';
    }

    public function label() {
        return 'Anthropic Claude';
    }

    protected function base_url() {
        return 'https://api.anthropic.com/v1';
    }

    /**
     * @param string $api_key
     * @return array
     */
    protected function request_headers( $api_key ) {
        return array(
            'Content-Type'      => 'application/json',
            'x-api-key'         => $api_key,
            'anthropic-version' => self::ANTHROPIC_VERSION,
        );
    }

    /**
     * Whether a model rejects the sampling parameters (`temperature`, `top_p`,
     * `top_k`) with a 400.
     *
     * Anthropic removed them starting with Opus 4.7; Sonnet 5 rejects any
     * non-default value. Sending `temperature` to one of these fails the whole
     * request — not a degraded answer, a hard error — so it is stripped rather
     * than forwarded. Older models (Sonnet 4.5, Haiku 4.5, Opus 4.5/4.6) still
     * accept it and are unaffected.
     *
     * @param string $model
     * @return bool
     */
    protected function rejects_sampling( $model ) {
        $model = (string) $model;

        if ( in_array( $model, array( 'claude-opus-5', 'claude-sonnet-5' ), true ) ) {
            return true;
        }

        // Opus 4.7 and every Opus release after it.
        return (bool) preg_match( '/^claude-opus-4-(?:[7-9]|\d{2,})/', $model );
    }

    /**
     * Whether a model thinks by default when no `thinking` parameter is sent.
     *
     * This matters for `max_tokens`, which caps thinking AND the visible answer
     * together: on these models a budget sized for the answer alone can be spent
     * mostly on reasoning and return a truncated document. Opus 4.7/4.8 do NOT
     * think unless asked, so they are deliberately absent.
     *
     * @param string $model
     * @return bool
     */
    protected function thinks_by_default( $model ) {
        return in_array( (string) $model, array( 'claude-opus-5', 'claude-sonnet-5' ), true );
    }

    /**
     * Split normalized messages into Claude's top-level system string and a
     * user/assistant messages array.
     *
     * @param array $messages
     * @return array array( string $system, array $messages )
     */
    protected function map_messages( $messages ) {
        $system = array();
        $turns  = array();

        foreach ( $messages as $message ) {
            $role    = isset( $message['role'] ) ? $message['role'] : 'user';
            $content = isset( $message['content'] ) ? (string) $message['content'] : '';

            if ( 'system' === $role ) {
                $system[] = $content;
                continue;
            }

            $turns[] = array(
                'role'    => ( 'assistant' === $role ) ? 'assistant' : 'user',
                'content' => $content,
            );
        }

        return array( implode( "\n\n", $system ), $turns );
    }

    /**
     * {@inheritDoc}
     */
    public function chat( $messages, $options = array() ) {
        if ( empty( $this->api_key ) ) {
            return new \WP_Error( 'no_api_key', sprintf( __( '%s API key is not configured.', 'betterdocs' ), $this->label() ) );
        }

        $model      = $this->resolve_model( $options );
        $context    = isset( $options['context'] ) ? $options['context'] : null;
        $max_tokens = $this->floor_tokens( isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2500, $model, $context );

        // A thinking model spends part of max_tokens on reasoning the user never
        // sees, so a budget sized for the answer alone returns a truncated one.
        // Double it, since the alternative — disabling thinking — costs the quality
        // that motivated choosing these models.
        if ( $this->thinks_by_default( $model ) ) {
            $max_tokens = (int) $max_tokens * 2;
        }

        list( $system, $turns ) = $this->map_messages( $messages );

        $payload = array(
            'model'      => $model,
            'max_tokens' => (int) $max_tokens,
            'messages'   => $turns,
        );
        if ( '' !== $system ) {
            $payload['system'] = $system;
        }
        // Dropped rather than forwarded on models that reject it — see
        // rejects_sampling(). Steering those models is done through the prompt.
        if ( isset( $options['temperature'] ) && null !== $options['temperature']
            && ! $this->rejects_sampling( $model ) ) {
            $payload['temperature'] = (float) $options['temperature'];
        }

        $timeout = isset( $options['timeout'] ) ? $options['timeout'] : 50;

        $status = null;
        $data   = $this->post_json( $this->base_url() . '/messages', $this->request_headers( $this->api_key ), $payload, $timeout, $status );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        if ( ! empty( $data['error'] ) ) {
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'betterdocs' );
            // Classify by HTTP status: Anthropic carries the reason in `error.type`
            // (rate_limit_error, not_found_error) and leaves `error.message` free-form,
            // so matching on the text alone is unreliable — the status is not.
            return new \WP_Error( 'provider_error', $this->classify_http_error( $status, $message, $model ) );
        }

        $content = $this->extract_text( $data );
        if ( '' === $content ) {
            return new \WP_Error( 'no_content', sprintf( __( 'No content received from %s.', 'betterdocs' ), $this->label() ) );
        }

        $usage = $this->normalize_usage(
            isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : array(),
            array( 'prompt' => 'input_tokens', 'completion' => 'output_tokens', 'total' => null )
        );

        return $this->success(
            $content,
            isset( $data['model'] ) ? $data['model'] : $model,
            $usage,
            isset( $data['stop_reason'] ) ? $data['stop_reason'] : null
        );
    }

    /**
     * Concatenate text blocks from the Messages API response.
     *
     * @param array $data
     * @return string
     */
    protected function extract_text( $data ) {
        if ( empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
            return '';
        }
        $text = '';
        foreach ( $data['content'] as $block ) {
            if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
                $text .= $block['text'];
            }
        }
        return $text;
    }

    /**
     * {@inheritDoc}
     */
    public function validate_key( $api_key = '' ) {
        $api_key = $api_key !== '' ? $api_key : $this->api_key;

        if ( empty( $api_key ) ) {
            return array( 'valid' => false, 'message' => __( 'Please insert your API key to use AI features.', 'betterdocs' ) );
        }

        $response = wp_remote_get( $this->base_url() . '/models', array(
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
