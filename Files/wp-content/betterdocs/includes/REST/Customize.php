<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;

/**
 * REST surface for the Quick Setup "Customize" step.
 *
 * Layouts are stored as WordPress theme_mods (not betterdocs_settings), so the
 * wizard's settings save can't touch them — this endpoint persists a single
 * layout choice immediately via set_theme_mod, validated against an allowlist.
 *
 * @since 4.5.3
 */
class Customize extends BaseAPI {
	/**
	 * theme_mod key => allowed layout values (the free layouts offered in the wizard).
	 *
	 * @var array
	 */
	const ALLOWED = [
		'betterdocs_docs_layout_select'    => [ 'layout-7', 'layout-1', 'layout-8', 'layout-5' ],
		'betterdocs_archive_layout_select' => [ 'layout-7', 'layout-1', 'layout-8', 'layout-4' ],
		'betterdocs_single_layout_select'  => [ 'layout-8', 'layout-9', 'layout-10', 'layout-1' ],
		'betterdocs_search_layout_select'  => [ 'layout-2', 'layout-1' ],
	];

	public function permission_check(): bool {
		return current_user_can( 'edit_theme_options' );
	}

	public function register() {
		$this->post( 'customize/layouts', [ $this, 'save_layouts' ] );
	}

	/**
	 * Persist the chosen layouts as theme_mods (called once, on wizard Finish).
	 * Each { key => value } pair is validated against the allowlist; unknown
	 * keys/values are skipped.
	 */
	public function save_layouts( WP_REST_Request $request ) {
		$layouts = (array) $request->get_param( 'layouts' );
		$saved   = [];

		foreach ( $layouts as $key => $value ) {
			$value = (string) $value;
			if ( isset( self::ALLOWED[ $key ] ) && in_array( $value, self::ALLOWED[ $key ], true ) ) {
				set_theme_mod( $key, $value );
				$saved[ $key ] = $value;

				/**
				 * Fires after a layout is saved from the Quick Setup customize step.
				 *
				 * @param string $key
				 * @param string $value
				 */
				do_action( 'betterdocs_customize_layout_saved', $key, $value );
			}
		}

		return $this->success( [ 'saved' => $saved ] );
	}
}
