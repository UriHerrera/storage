<?php
/**
 * Get related docs ability.
 *
 * @package BetterDocsPro
 * @since   4.9.1
 */

namespace WPDeveloper\BetterDocsPro\Abilities\RelatedDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;

/**
 * Read the saved related-doc suggestions for a doc — the persisted list shown
 * under the article. Reads the suggestion cache only; it never runs the AI
 * engine (that stays an explicit editor action), so it is a cheap, side-effect
 * free read.
 *
 * @since 4.9.1
 */
class GetRelatedDocs extends ProAbility {

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/get-related-docs';
	}

	/**
	 * @since 4.9.1
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$state = $this->pro_state();

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, $this->feature );
		}

		$doc_id = isset( $input['doc_id'] ) ? (int) $input['doc_id'] : 0;

		if ( $doc_id <= 0 ) {
			return AbilityError::invalid_input( 'doc_id', __( 'Give the doc id to read related suggestions for.', 'betterdocs-pro' ) );
		}

		$post = get_post( $doc_id );

		if ( ! $post || 'docs' !== $post->post_type ) {
			return AbilityError::not_found( 'docs', (string) $doc_id );
		}

		$resp = $this->dispatch( 'GET', '/saved-related-docs', [ 'article_id' => $doc_id ] );

		if ( is_wp_error( $resp ) ) {
			return AbilityError::upstream( $resp->get_error_message(), [ 'code' => (string) $resp->get_error_code() ] );
		}

		$data = is_array( $resp ) && isset( $resp['data'] ) && is_array( $resp['data'] ) ? $resp['data'] : [];
		$recs = isset( $data['recommendations'] ) && is_array( $data['recommendations'] ) ? array_values( $data['recommendations'] ) : [];

		return [
			'doc_id'       => $doc_id,
			'related'      => $recs,
			'generated_at' => isset( $data['generated_at'] ) ? $data['generated_at'] : null
		];
	}

	/**
	 * @since 4.9.1
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'read related docs', 'betterdocs-pro' );
	}
}
