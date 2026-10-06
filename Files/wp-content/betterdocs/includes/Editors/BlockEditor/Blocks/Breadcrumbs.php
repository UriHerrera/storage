<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class Breadcrumbs extends Block {

	public $editor_styles   = [ 'betterdocs-breadcrumb' ];
	public $frontend_styles = [ 'betterdocs-breadcrumb' ];

	public function get_name() {
		return 'breadcrumb';
	}

	public function get_default_attributes() {
		return [
			'blockId' => '',
			'layout'  => 'layout-1'
		];
	}

	public function render( $attributes, $content ) {
		$this->views( 'widgets/breadcrumbs' );
	}

	public function view_params() {
		return [
			'breadcrumbs_layout' => $this->attributes['layout'],
			'blockId'            => $this->attributes['blockId']
		];
	}
}
