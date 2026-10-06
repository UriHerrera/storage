<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Core\Shortcode;

class PostContent extends Shortcode {
	protected $html_attributes = [];

	public function get_name() {
		return 'betterdocs_post_content';
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return [
			'post_id'                => get_the_ID(),
			'htags'                  => 'h1,h2,h3,h4,h5,h6',
			'enable_toc'             => '',
			'toc_hierarchy'          => '',
			'list_number'            => '',
			'display_toc_on_top'     => '',
			'collapsible_toc_mobile' => ''
		];
	}

	public function render( $atts, $content = null ) {
		$post = get_post( $this->attributes['post_id'] );

		return betterdocs()->template_helper->content(
			$post->post_content,
			$this->attributes['htags'],
			$this->attributes['enable_toc']
		);
	}
}
