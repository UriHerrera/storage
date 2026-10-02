<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class DocDate extends Block {
	public $view_wrapper = 'betterdocs-updated-date-wrapper update-date';

	public function get_name() {
		return 'doc-date';
	}

	public function get_default_attributes() {
		return [
			'blockId' => ''
		];
	}

	public function render( $attributes, $content ) {
		$this->views( 'widgets/date' );
	}
}
