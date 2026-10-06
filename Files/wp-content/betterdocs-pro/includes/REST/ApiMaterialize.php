<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\Materializer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endpoint-docs materialization management (Pro).
 *
 * POST   /api-ref/{id}/materialize   enable + run (body: { rebuild?: bool })
 * GET    /api-ref/{id}/materialize   status + report (+ chatbot new-docs count)
 * DELETE /api-ref/{id}/materialize   disable + trash generated docs
 */
class ApiMaterialize extends BaseAPI {
	public function register() {
		$args = [
			'id' => [ 'sanitize_callback' => 'absint' ]
		];

		$this->post( '/api-ref/(?P<id>[\d]+)/materialize', [ $this, 'run' ], $args );
		$this->get( '/api-ref/(?P<id>[\d]+)/materialize', [ $this, 'status' ], $args );
		$this->register_endpoint( '/api-ref/(?P<id>[\d]+)/materialize', [ $this, 'remove' ], $args, WP_REST_Server::DELETABLE );
	}

	public function permission_check() {
		return current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) );
	}

	/**
	 * POST — enable materialization and run (or queue) it.
	 */
	public function run( WP_REST_Request $request ) {
		$reference = $this->find_reference( $request['id'] );

		if ( is_wp_error( $reference ) ) {
			return $reference;
		}

		// The flag has to go on before the run so a spec that lands mid-flight is
		// picked up by Materializer::handle_ingest(). But it is also what the admin
		// reads back as "endpoint docs are on", so a failed run must not leave it
		// set — restore whatever it was on error rather than forcing '0': a 409
		// means a concurrent run owns it and is legitimately mid-materialization.
		$previous = (string) get_post_meta( $reference->ID, '_bd_api_materialize', true );

		update_post_meta( $reference->ID, '_bd_api_materialize', '1' );

		$materializer = $this->materializer();
		$result       = $materializer->materialize( $reference->ID );

		if ( is_wp_error( $result ) ) {
			$status = 'betterdocs_api_materialize_locked' === $result->get_error_code() ? 409 : 400;

			if ( 409 !== $status ) {
				update_post_meta( $reference->ID, '_bd_api_materialize', '' === $previous ? '0' : $previous );
			}

			return $this->error( $result->get_error_code(), $result->get_error_message(), $status );
		}

		return $this->success( $this->status_payload( $reference->ID ) );
	}

	/**
	 * GET — current status + report + admin conveniences.
	 */
	public function status( WP_REST_Request $request ) {
		$reference = $this->find_reference( $request['id'] );

		if ( is_wp_error( $reference ) ) {
			return $reference;
		}

		return $this->success( $this->status_payload( $reference->ID ) );
	}

	/**
	 * DELETE — unmaterialize (trash generated docs).
	 */
	public function remove( WP_REST_Request $request ) {
		$reference = $this->find_reference( $request['id'] );

		if ( is_wp_error( $reference ) ) {
			return $reference;
		}

		$result = $this->materializer()->unmaterialize( $reference->ID );

		return $this->success( $result );
	}

	/* ---------------------------------------------------------------- */

	protected function status_payload( $reference_id ) {
		$status = get_post_meta( $reference_id, '_bd_api_materialize_status', true );
		$intro  = (int) get_post_meta( $reference_id, '_bd_api_intro_doc_id', true );
		$parent = (int) get_post_meta( $reference_id, '_bd_api_parent_term_id', true );

		$payload = [
			'materialized' => betterdocs()->container->get( ApiReferences::class )->is_materialized( $reference_id ),
			'status'       => is_array( $status ) ? $status : null,
			'intro'        => $intro ? [
				'id'        => $intro,
				'edit_link' => get_edit_post_link( $intro, 'raw' ),
				'permalink' => get_permalink( $intro )
			] : null,
			'category'     => $parent ? [
				'id'        => $parent,
				'edit_link' => admin_url( 'term.php?taxonomy=doc_category&tag_ID=' . $parent . '&post_type=docs' )
			] : null
		];

		// Chatbot affordance: how many of THIS reference's docs are waiting to be
		// embedded.
		//
		// `saved_docs_post_ids` is the chatbot's site-wide pending queue, so
		// counting it reported every unsynced doc on the site — a number with no
		// relationship to the API reference whose drawer you are looking at.
		// Intersecting it with the docs this reference generated gives the figure
		// the panel claims to be showing.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Both halves matter: the option can say "active" for a plugin that is not
		// actually loaded (folder renamed, boot fataled), and the whole panel —
		// including its set-up prompt — must stay off an install that has no
		// chatbot at all rather than advertise a plugin that isn't there.
		$chatbot_loaded = is_plugin_active( 'betterdocs-ai-chatbot/betterdocs-ai-chatbot.php' )
			&& class_exists( '\WPDeveloper\BetterDocsChatbot\Core\AIChatbot' );

		if ( $chatbot_loaded ) {
			$pending = get_option( 'saved_docs_post_ids', [] );
			$pending = is_array( $pending ) ? array_map( 'intval', $pending ) : [];

			// Docs that previously failed to embed are pending too — the chatbot's
			// own trigger folds them in, so the count has to as well or it reads
			// 0 while the button still has work to do.
			$errors = get_option( 'betterdocs_ai_chatbot_error_posts', [] );

			if ( is_array( $errors ) && ! empty( $errors ) ) {
				foreach ( $errors as $key => $value ) {
					if ( is_numeric( $key ) && is_array( $value ) ) {
						$pending[] = (int) $key;
					} elseif ( is_numeric( $key ) && is_numeric( $value ) ) {
						$pending[] = (int) $value;
					}
				}
			}

			// existing_docs_map() is the same lookup the materializer uses, so it
			// covers the Introduction as well as every endpoint doc.
			$mine = array_map( 'intval', array_values( $this->materializer()->existing_docs_map( $reference_id ) ) );
			$ours = array_values( array_unique( array_intersect( $pending, $mine ) ) );

			// The pending queue alone cannot tell "already embedded" from "never
			// queued": on a chatbot that was never set up nothing is queued, so
			// intersecting it reported 0 and the panel claimed everything was in
			// sync. `unsynced` answers the question the panel actually asks —
			// which of this reference's docs the chatbot does not hold — and
			// `configured` says whether a sync could run at all.
			$synced   = get_option( 'betterdocs_ai_chatbot_synced_posts', [] );
			$synced   = is_array( $synced ) ? array_map( 'intval', $synced ) : [];
			$unsynced = array_values( array_diff( $mine, $synced ) );

			// Reported apart, not as one "configured" flag: an admin whose licence
			// is fine needs to be told about the missing API key, not sent back to
			// a licence screen that has nothing wrong with it.
			$license_ok = 'valid' === get_option( 'betterdocs_chatbot_software__license_status' );
			$key_ok     = $this->chatbot_key_present();

			$payload['chatbot'] = [
				'active'     => true,
				'license_ok' => $license_ok,
				'key_ok'     => $key_ok,
				'configured' => $license_ok && $key_ok,
				'new_count'  => count( $ours ),
				'unsynced'   => count( $unsynced ),
				'settings_url'  => admin_url( 'admin.php?page=betterdocs-settings' ),
				'license_url'   => admin_url( 'admin.php?page=betterdocs-settings&tab=tab-license' ),
				// The chatbot's sync trigger has no per-reference scope, so the
				// button still embeds the whole pending queue. Surfaced so the UI
				// can say so instead of implying otherwise.
				'total_pending' => count( array_unique( $pending ) )
			];
		} else {
			$payload['chatbot'] = [ 'active' => false ];
		}

		return $payload;
	}

	/**
	 * Whether a key for the configured AI platform is stored. Deliberately
	 * local-only: this runs on every drawer open, so it must never make the
	 * network call the chatbot's own live validator makes — presence is all the
	 * panel needs to decide between "add a key" and "sync".
	 *
	 * @return bool
	 */
	protected function chatbot_key_present() {
		// Platform-aware accessor when the chatbot is loaded (Gemini installs keep
		// their key in a per-provider field); the legacy option is the fallback.
		if ( is_callable( [ '\WPDeveloper\BetterDocsChatbot\Core\AIChatbot', 'get_chat_api_key_plain' ] ) ) {
			return '' !== \WPDeveloper\BetterDocsChatbot\Core\AIChatbot::get_chat_api_key_plain();
		}

		return '' !== trim( (string) betterdocs()->settings->get( 'ai_chatbot_api_key', '' ) );
	}

	protected function find_reference( $id ) {
		$post = get_post( absint( $id ) );

		if ( ! $post || 'betterdocs_api_ref' !== $post->post_type ) {
			return $this->error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ), 404 );
		}

		return $post;
	}

	protected function materializer() {
		return $this->container->get( Materializer::class );
	}
}
