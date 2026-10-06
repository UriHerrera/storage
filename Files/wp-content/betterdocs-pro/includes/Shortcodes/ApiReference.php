<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;

/**
 * [betterdocs_api_reference id="123"]
 *
 * Renders an API Reference anywhere. Thin wrapper over
 * ApiReferences::render_reference() (the same builder the single template and
 * the block use), so the container + Scalar enqueue + Free capping are shared.
 */
class ApiReference extends Shortcode {
	public function get_name() {
		return 'betterdocs_api_reference';
	}

	public function default_attributes() {
		return [
			'id' => 0
		];
	}

	public function render( $atts, $content = null ) {
		$id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;

		if ( ! $id ) {
			return;
		}

		echo betterdocs()->container->get( ApiReferences::class )->render_reference( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_reference() escapes its parts.
	}
}
