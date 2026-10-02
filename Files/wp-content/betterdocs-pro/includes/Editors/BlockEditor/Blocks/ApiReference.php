<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;

/**
 * Gutenberg block: betterdocs/api-reference (Pro-only)
 *
 * Dynamic block — embeds a reference's Introduction + a link via the shared
 * ApiReferences::render_reference() builder, the same path the shortcode uses.
 */
class ApiReference extends Block {
	public $is_pro = true;

	protected $editor_scripts = [ 'betterdocs-pro-blocks-editor' ];

	public function get_name() {
		return 'api-reference';
	}

	public function get_default_attributes() {
		return [
			'id' => 0
		];
	}

	public function render( $attributes, $content ) {
		$id = isset( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;

		if ( ! $id ) {
			return;
		}

		echo betterdocs()->container->get( ApiReferences::class )->render_reference( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_reference() escapes its parts.
	}
}
