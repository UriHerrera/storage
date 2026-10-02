<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class BetterdocsPrint extends Block {
	protected $editor_styles = [
		'betterdocs-single'
	];

	protected $frontend_styles = [
		'betterdocs-single',
		'betterdocs-encyclopedia'
	];

	protected $frontend_scripts = [
		'betterdocs'
	];

	public function get_name() {
		return 'betterdocs-print';
	}

	public function get_default_attributes() {
		return [
			'blockId' => '',
			'layout'  => 'layout-1',
			'enable'  => true
		];
	}

	public function render( $attributes, $content ) {
		$layout = $attributes['layout'];
		if ( $layout == 'layout-1' ) {
			$this->views( 'widgets/print-icon' );
		} else {
			$this->views( 'templates/parts/print-icon-2' );
		}
	}
}
