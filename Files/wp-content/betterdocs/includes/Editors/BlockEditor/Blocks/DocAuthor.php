<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class DocAuthor extends Block {

	public $editor_styles   = [ 'betterdocs-author' ];
	public $frontend_styles = [ 'betterdocs-author' ];

	public function get_name() {
		return 'doc-author';
	}

	public function get_default_attributes() {
		return [
			'blockId' => ''
		];
	}

	public function render( $attributes, $content ) {
		$blockId = $attributes['blockId'];
		echo '<div class="' . esc_attr( $blockId ) . '">';
		$this->views( 'widgets/author' );
		echo '</div>';
	}

	public function view_params() {

		return [];
	}
}
