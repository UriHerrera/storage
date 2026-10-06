<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WP_REST_Server;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\AiOverlay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * v2.0 AI-layer management (Pro).
 *
 * POST   /api-ref/{id}/ai-generate  run generation (body: { targets?: string[] })
 * GET    /api-ref/{id}/ai-generate  status + overlay counts + mode + changelog link
 * DELETE /api-ref/{id}/ai-generate  clear the stored overlay
 */
class ApiAi extends BaseAPI {
	public function register() {
		$args = [
			'id' => [ 'sanitize_callback' => 'absint' ]
		];

		$this->post( '/api-ref/(?P<id>[\d]+)/ai-generate', [ $this, 'run' ], $args );
		$this->get( '/api-ref/(?P<id>[\d]+)/ai-generate', [ $this, 'status' ], $args );
		$this->register_endpoint( '/api-ref/(?P<id>[\d]+)/ai-generate', [ $this, 'remove' ], $args, WP_REST_Server::DELETABLE );
	}

	public function permission_check() {
		return current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) );
	}

	/**
	 * POST — generate descriptions/examples through the AI ladder.
	 */
	public function run( WP_REST_Request $request ) {
		$reference = $this->find_reference( $request['id'] );

		if ( is_wp_error( $reference ) ) {
			return $reference;
		}

		$targets = (array) ( $request->get_param( 'targets' ) ?: [ 'descriptions', 'examples' ] );
		$targets = array_values( array_intersect( [ 'descriptions', 'examples' ], array_map( 'sanitize_key', $targets ) ) );

		if ( empty( $targets ) ) {
			return $this->error( 'betterdocs_api_ai_bad_targets', __( 'targets must include "descriptions" and/or "examples".', 'betterdocs-pro' ), 400 );
		}

		$result = $this->overlay()->generate( $reference->ID, $targets );

		if ( is_wp_error( $result ) ) {
			$status = 'betterdocs_api_ai_locked' === $result->get_error_code() ? 409 : 400;

			return $this->error( $result->get_error_code(), $result->get_error_message(), $status );
		}

		return $this->success( $this->status_payload( $reference->ID ) );
	}

	/**
	 * GET — current status + admin conveniences.
	 */
	public function status( WP_REST_Request $request ) {
		$reference = $this->find_reference( $request['id'] );

		if ( is_wp_error( $reference ) ) {
			return $reference;
		}

		return $this->success( $this->status_payload( $reference->ID ) );
	}

	/**
	 * DELETE — drop the stored overlay (generated prose/examples disappear
	 * from the explorer immediately; materialized docs on next rebuild).
	 */
	public function remove( WP_REST_Request $request ) {
		$reference = $this->find_reference( $request['id'] );

		if ( is_wp_error( $reference ) ) {
			return $reference;
		}

		if ( $this->overlay()->is_locked( $reference->ID ) ) {
			return $this->error( 'betterdocs_api_ai_locked', __( 'An AI generation run is in progress — try again when it completes.', 'betterdocs-pro' ), 409 );
		}

		$counts = $this->overlay_counts( $reference->ID );
		$this->overlay()->clear( $reference->ID );

		return $this->success( [ 'cleared' => $counts['ops'] ] );
	}

	/* ---------------------------------------------------------------- */

	protected function status_payload( $reference_id ) {
		$overlay = $this->overlay();

		return [
			'status'  => $overlay->get_status( $reference_id ),
			'running' => $overlay->is_locked( $reference_id ),
			'mode'    => $overlay->client()->mode(),
			'overlay' => $this->overlay_counts( $reference_id )
		];
	}

	/**
	 * @param int $reference_id
	 * @return array { ops, descriptions, examples }
	 */
	protected function overlay_counts( $reference_id ) {
		$stored       = $this->overlay()->get_overlay( $reference_id );
		$descriptions = 0;
		$examples     = 0;

		foreach ( $stored['ops'] as $entry ) {
			if ( ! empty( $entry['description'] ) ) {
				$descriptions++;
			}

			if ( ! empty( $entry['examples'] ) ) {
				$examples++;
			}
		}

		return [
			'ops'          => count( $stored['ops'] ),
			'descriptions' => $descriptions,
			'examples'     => $examples
		];
	}

	protected function find_reference( $id ) {
		$post = get_post( absint( $id ) );

		if ( ! $post || 'betterdocs_api_ref' !== $post->post_type ) {
			return $this->error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ), 404 );
		}

		return $post;
	}

	/**
	 * @return AiOverlay
	 */
	protected function overlay() {
		return $this->container->get( AiOverlay::class );
	}
}
