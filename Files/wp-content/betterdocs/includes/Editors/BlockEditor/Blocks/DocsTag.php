<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;


class DocsTag extends Block {
	protected $editor_styles   = [ 'betterdocs-docs' ];
	protected $frontend_styles = [ 'betterdocs-docs' ];

	public function get_name() {
		return 'docs-tag';
	}

	public function get_default_attributes() {
		return [
			'blockId' => ''
		];
	}

	public function render( $attributes, $content ) {
		$this->views( 'templates/parts/tags' );
	}
}
