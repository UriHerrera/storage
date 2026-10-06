<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class Navigation extends Block {
	public function get_name() {
		return 'doc-navigation';
	}

	public function get_default_attributes() {
		return [
			'blockId' => ''
		];
	}

	public function render( $attributes, $content ) {
		betterdocs()->views->get(
			'templates/parts/navigation',
			[
				'wrapper_class' => 'betterdocs-elementor-navigation',
				'blockId'       => $this->attributes['blockId']
			]
		);
	}
}
