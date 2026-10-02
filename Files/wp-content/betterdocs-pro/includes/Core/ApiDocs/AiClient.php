<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

use WP_Error;
use WPDeveloper\BetterDocs\Utils\AIHelper;
use WPDeveloper\BetterDocs\Utils\AIUsage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The v2.0 AI delivery ladder (ADR-020): sites with the Write-with-AI OpenAI
 * key (`ai_autowrite_api_key`) call OpenAI directly through Free's AIHelper —
 * their key, their bill; keyless sites use the hosted BetterDocs proxy
 * (`{proxy}/v1/api-docs`, HMAC contract mirroring sample-docs, metered).
 *
 * Transport + prompts only. Planning what to generate and where results land
 * is AiOverlay's job. Every call is server-side; the key never leaves PHP.
 */
class AiClient {
	/**
	 * Operations per AI request (ADR-023).
	 */
	const CHUNK_SIZE = 8;

	/**
	 * The rung a call will use.
	 *
	 * @return string 'byok'|'proxy'
	 */
	public function mode() {
		return $this->helper()->has_api_key() ? 'byok' : 'proxy';
	}

	/**
	 * Generate content for a chunk of operations.
	 *
	 * @param string $task       'descriptions'|'examples'
	 * @param array  $operations [ { key, method, path, summary, description,
	 *                              parameters, request, responses } ] — compact
	 *                            digests, ≤ CHUNK_SIZE.
	 * @param array  $context    { api_title, api_description }
	 *
	 * @return array|WP_Error {
	 *     @type array $ops  op_key => task payload (see AiOverlay).
	 *     @type array $meta { mode, model?, tokens_used?, credits_remaining? }
	 * }
	 */
	public function generate_ops( $task, array $operations, array $context = [] ) {
		if ( ! in_array( $task, [ 'descriptions', 'examples' ], true ) ) {
			return new WP_Error( 'betterdocs_api_ai_bad_task', __( 'Unknown AI task.', 'betterdocs-pro' ) );
		}

		if ( empty( $operations ) ) {
			return [
				'ops'  => [],
				'meta' => [ 'mode' => $this->mode() ]
			];
		}

		if ( 'byok' === $this->mode() ) {
			return $this->byok_ops( $task, $operations, $context );
		}

		return $this->proxy_ops( $task, $operations, $context );
	}

	/* ---------------------------------------------------------------- */
	/* BYOK rung                                                         */
	/* ---------------------------------------------------------------- */

	/**
	 * @param string $task
	 * @param array  $operations
	 * @param array  $context
	 * @return array|WP_Error
	 */
	protected function byok_ops( $task, array $operations, array $context ) {
		$system = 'descriptions' === $task
			? 'You write friendly, precise API reference prose. For every operation in the input JSON, write '
				. 'a description in CommonMark markdown (2-4 sentences: what it does, when to use it, notable '
				. 'parameters or caveats) and, when the operation has no summary, a short summary (max 8 words). '
				. 'Never invent parameters, fields or behaviour not present in the input. '
				. 'Reply with ONLY a JSON object: {"ops":{"<key>":{"description":"...","summary":"..."}}} — '
				. 'omit "summary" for operations that already have one. No markdown fences, no commentary.'
			: 'You write realistic API examples. For every operation in the input JSON, produce example JSON '
				. 'values for each listed request/response slot, strictly matching the given schema (correct '
				. 'types, all required properties, plausible realistic values — never placeholder strings like '
				. '"string" or "value"). Reply with ONLY a JSON object: '
				. '{"ops":{"<key>":{"request":{"<media>":<example>},"responses":{"<code>":{"<media>":<example>}}}}} '
				. '— include only the slots listed in the input. No markdown fences, no commentary.';

		$user = wp_json_encode(
			[
				'api'        => [
					'title'       => isset( $context['api_title'] ) ? (string) $context['api_title'] : '',
					'description' => isset( $context['api_description'] ) ? (string) $context['api_description'] : ''
				],
				'operations' => $operations
			]
		);

		$text = $this->helper()->make_openai_request(
			[
				$this->helper()->create_system_message( $system ),
				$this->helper()->create_user_message( $user )
			],
			[
				'model'       => $this->model(),
				'max_tokens'  => 'examples' === $task ? 4000 : 2500,
				'temperature' => 0.4,
				'timeout'     => 60
			]
		);

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$parsed = $this->decode_ops( (string) $text );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		AIUsage::record( 'api_docs_ai', 0, $task );

		return [
			'ops'  => $parsed,
			'meta' => [
				'mode'  => 'byok',
				'model' => $this->model()
			]
		];
	}

	/**
	 * Strict JSON expectations (ADR-023): one decode, tolerate a fenced reply,
	 * anything else is a typed error the caller reports per-chunk.
	 *
	 * @param string $text
	 * @return array|WP_Error op_key => payload
	 */
	protected function decode_ops( $text ) {
		$text = trim( $text );

		// Models occasionally fence even when told not to.
		if ( 0 === strpos( $text, '```' ) ) {
			$text = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $text );
		}

		$data = json_decode( $text, true );

		if ( ! is_array( $data ) || ! isset( $data['ops'] ) || ! is_array( $data['ops'] ) ) {
			return new WP_Error( 'betterdocs_api_ai_bad_json', __( 'The AI reply was not the expected JSON shape.', 'betterdocs-pro' ) );
		}

		return $data['ops'];
	}

	/* ---------------------------------------------------------------- */
	/* Hosted-proxy rung                                                 */
	/* ---------------------------------------------------------------- */

	/**
	 * @param string $task
	 * @param array  $operations
	 * @param array  $context
	 * @return array|WP_Error
	 */
	protected function proxy_ops( $task, array $operations, array $context ) {
		$response = $this->call_proxy(
			[
				'task'       => $task,
				'operations' => $operations,
				'meta'       => $this->proxy_meta( $context )
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['ops'] ) || ! is_array( $response['ops'] ) ) {
			return new WP_Error( 'betterdocs_api_ai_bad_response', __( 'The AI service returned an unexpected response.', 'betterdocs-pro' ) );
		}

		AIUsage::record( 'api_docs_ai', 0, $task );

		return [
			'ops'  => $response['ops'],
			'meta' => [
				'mode'              => 'proxy',
				'model'             => isset( $response['meta']['model'] ) ? (string) $response['meta']['model'] : '',
				'tokens_used'       => isset( $response['meta']['tokens_used'] ) ? (int) $response['meta']['tokens_used'] : 0,
				'credits_remaining' => isset( $response['meta']['credits_remaining'] ) ? (int) $response['meta']['credits_remaining'] : null
			]
		];
	}

	/**
	 * POST to {proxy}/v1/api-docs — transport, auth and typed errors exactly
	 * like SampleDocs::call_proxy() (retry only on transport failure; any
	 * received HTTP status is final to avoid double-billing).
	 *
	 * @param array $payload
	 * @return array|WP_Error Decoded body.
	 */
	protected function call_proxy( array $payload ) {
		$url     = $this->proxy_url() . '/v1/api-docs';
		$secret  = $this->proxy_secret();
		$body    = wp_json_encode( $payload );
		$timeout = 45;

		$args = [
			'timeout' => $timeout,
			'headers' => [
				'Content-Type'           => 'application/json',
				'Accept'                 => 'application/json',
				'X-BetterDocs-Site'      => esc_url_raw( home_url() ),
				'X-BetterDocs-License'   => $this->license_key(),
				'X-BetterDocs-Signature' => hash_hmac( 'sha256', $body, $secret )
			],
			'body'    => $body
		];

		$attempts = 0;
		$response = null;

		while ( $attempts < 2 ) {
			$attempts++;

			$reset = function_exists( 'set_time_limit' ) && @set_time_limit( $timeout + 15 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			$response = wp_remote_post( $url, $args );

			if ( ! is_wp_error( $response ) || ! $reset ) {
				break;
			}
		}

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'betterdocs_api_ai_unreachable',
				__( 'Could not reach the BetterDocs AI service. Add your own OpenAI API key under Settings → AI Content Suite, or try again later.', 'betterdocs-pro' )
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$parsed = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 429 === $status || ( isset( $parsed['status'] ) && 'quota_exceeded' === $parsed['status'] ) ) {
			return new WP_Error(
				'betterdocs_api_ai_quota',
				__( 'The hosted AI credits for this site are used up. Add your own OpenAI API key under Settings → AI Content Suite to continue.', 'betterdocs-pro' )
			);
		}

		if ( $status >= 400 || ! is_array( $parsed ) ) {
			return new WP_Error(
				'betterdocs_api_ai_proxy_error',
				isset( $parsed['message'] ) ? (string) $parsed['message'] : __( 'The AI service returned an unexpected response.', 'betterdocs-pro' )
			);
		}

		return $parsed;
	}

	/* ---------------------------------------------------------------- */
	/* Config                                                            */
	/* ---------------------------------------------------------------- */

	/**
	 * @return AIHelper
	 */
	protected function helper() {
		static $helper = null;

		if ( null === $helper ) {
			$helper = new AIHelper( betterdocs()->settings );
		}

		return $helper;
	}

	/**
	 * @return string
	 */
	protected function model() {
		return (string) betterdocs()->settings->get( 'write_with_ai_model', 'gpt-4o-mini' );
	}

	/**
	 * @return string
	 */
	protected function proxy_url() {
		$base = get_option( 'betterdocs_ai_proxy_url', 'https://api.betterdocs.co/ai' );

		/** This filter is documented in Free includes/REST/SampleDocs.php */
		return untrailingslashit( apply_filters( 'betterdocs_ai_proxy_url', $base ) );
	}

	/**
	 * @return string
	 */
	protected function proxy_secret() {
		$secret = get_option( 'betterdocs_ai_proxy_secret', 'betterdocs-local-dev-secret' );

		/** This filter is documented in Free includes/REST/SampleDocs.php */
		return apply_filters( 'betterdocs_ai_proxy_secret', $secret );
	}

	/**
	 * @return string
	 */
	protected function license_key() {
		/** This filter is documented in Free includes/REST/SampleDocs.php */
		return apply_filters( 'betterdocs_ai_proxy_license', (string) get_option( 'betterdocs_pro_licenses', '' ) );
	}

	/**
	 * @param array $context
	 * @return array
	 */
	protected function proxy_meta( array $context ) {
		return [
			'api_title'   => isset( $context['api_title'] ) ? (string) $context['api_title'] : '',
			'old_version' => isset( $context['old_version'] ) ? (string) $context['old_version'] : '',
			'new_version' => isset( $context['new_version'] ) ? (string) $context['new_version'] : '',
			'locale'      => get_locale()
		];
	}

	/**
	 * @param array $op
	 * @return string
	 */
	protected function op_line( array $op ) {
		$line = strtoupper( $op['method'] ) . ' ' . $op['path'];

		if ( ! empty( $op['changes'] ) ) {
			$line .= ': ' . implode( '; ', wp_list_pluck( $op['changes'], 'message' ) );
		}

		return $line;
	}
}
