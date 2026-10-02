<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class ToC extends Block {

	public $view_wrapper = 'betterdocs-toc-block';

	public function get_name() {
		return 'table-of-contents';
	}

	public function get_default_attributes() {
		return [
			'blockId'                       => '',
			'toc_supported_tags'            => [
				[
					'value' => 1,
					'label' => 'H1'
				],
				[
					'value' => 2,
					'label' => 'H2'
				],
				[
					'value' => 3,
					'label' => 'H3'
				],
				[
					'value' => 4,
					'label' => 'H4'
				],
				[
					'value' => 5,
					'label' => 'H5'
				]
			],
			'toc_list_heirarchy'            => true,
			'toc_list_number'               => true,
			'toc_collapsible_small_devices' => true,
			'toc_title_text'                => 'Table Of Contents'
		];
	}

	public function render( $attributes, $content ) {
		// Check if post is password protected and user hasn't provided correct password
		// Skip check in editor mode
		if ( ! $this->is_editor_mode() && post_password_required() ) {
			// Don't show ToC for password-protected posts until password is provided
			return '';
		}

		$this->views( 'widgets/toc' );
	}

	/**
	 * Check if we're in editor mode
	 *
	 * @return bool
	 */
	private function is_editor_mode() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- REST request context check.
		$context = isset( $_REQUEST['context'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ) : '';
		return defined( 'REST_REQUEST' ) && REST_REQUEST && 'edit' === $context;
	}

	public function view_params() {
		$htags = [];

		if ( ! empty( $this->attributes['toc_supported_tags'] ) ) {
			$htags = array_map(
				function ( $item ) {
					return $item['value'];
				},
				$this->attributes['toc_supported_tags']
			);
		}

		$toc_setting = [
			'htags'       => $htags,
			'hierarchy'   => $this->attributes['toc_list_heirarchy'],
			'list_number' => $this->attributes['toc_list_number']
		];

		//set TOC data in Transient, whenever TOC(block) is called.
		set_transient( 'betterdocs_toc_setting', $toc_setting );

		$htags = implode( ',', $htags );

		$attributes = betterdocs()->template_helper->get_html_attributes(
			[
				'htags'                 => $htags,
				'hierarchy'             => $this->attributes['toc_list_heirarchy'],
				'list_number'           => $this->attributes['toc_list_number'],
				'collapsible_on_mobile' => $this->attributes['toc_collapsible_small_devices'],
				'toc_title'             => $this->attributes['toc_title_text']
			]
		);

		return [
			'attributes' => $attributes
		];
	}
}
