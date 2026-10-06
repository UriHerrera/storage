<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Documentation — per-reference visibility (Pro).
 *
 * Enforced in two places that must agree: the gated spec REST route (via the
 * Free `betterdocs_api_ref_can_view` filter) and the rendered frontend page
 * (template_redirect). Managers always pass so they can preview restricted
 * references.
 */
class ApiReferenceVisibility {
	public function __construct() {
		add_filter( 'betterdocs_api_ref_can_view', [ $this, 'can_view' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'guard_frontend' ] );
	}

	/**
	 * Whether the current visitor may see a reference.
	 *
	 * @param bool     $can  Default from Free (true).
	 * @param \WP_Post $post The reference.
	 * @return bool
	 */
	public function can_view( $can, $post ) {
		$visibility = get_post_meta( $post->ID, '_bd_api_visibility', true ) ?: 'public';

		if ( 'public' === $visibility ) {
			return true;
		}

		if ( 'logged_in' === $visibility ) {
			return is_user_logged_in();
		}

		if ( 'roles' === $visibility ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$allowed = (array) get_post_meta( $post->ID, '_bd_api_visibility_roles', true );
			if ( empty( $allowed ) ) {
				// No roles chosen → treat as logged-in-only rather than locking everyone out.
				return true;
			}

			$user_roles = (array) wp_get_current_user()->roles;

			return (bool) array_intersect( $allowed, $user_roles );
		}

		return $can;
	}

	/**
	 * Block the rendered reference page when the visitor can't view it.
	 * Managers bypass (preview). Logged-out visitors are sent to log in;
	 * logged-in-but-unauthorized get a 403.
	 *
	 * @return void
	 */
	public function guard_frontend() {
		if ( ! is_singular( 'betterdocs_api_ref' ) ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) ) ) {
			return;
		}

		if ( apply_filters( 'betterdocs_api_ref_can_view', true, $post ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect(); // exits.
		}

		wp_die(
			esc_html__( 'You are not allowed to view this API reference.', 'betterdocs-pro' ),
			esc_html__( 'Restricted', 'betterdocs-pro' ),
			[ 'response' => 403 ]
		);
	}
}
