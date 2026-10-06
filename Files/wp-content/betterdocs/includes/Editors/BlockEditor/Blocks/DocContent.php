<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class DocContent extends Block {
	public function get_name() {
		return 'doc-content';
	}

	protected $editor_styles = [
		'betterdocs-single',
		'betterdocs-encyclopedia'

	];

	public function get_default_attributes() {
		return [
			'blockId'         => '',
			'show_print_icon' => true
		];
	}

	public function render( $attributes, $content ) {
		$this->views( 'widgets/content' );
	}

	public function view_params() {
		$toc_setting = get_transient( 'betterdocs_toc_setting' );
		$htags       = ( $toc_setting ) ? implode( ',', $toc_setting['htags'] ) : '';
		$enable_toc  = ( $htags ) ? 1 : '';

		return [
			'wrapper_attr' => [
				'class' => [ 'betterdocs-entry-content' ]
			],
			'enable'       => $this->attributes['show_print_icon'],
			'enable_toc'   => $enable_toc,
			'htags'        => $htags
		];
	}
}
