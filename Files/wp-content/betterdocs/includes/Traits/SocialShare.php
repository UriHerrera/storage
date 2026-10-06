<?php
namespace WPDeveloper\BetterDocs\Traits;

trait SocialShare {
	public $view_wrapper = 'betterdocs-social-share';

	public function generate_links() {
		$_permalink = get_the_permalink();

		$_defaults = [
			'facebook'  => [
				'alt'    => 'Facebook',
				'icon'   => betterdocs()->assets->icon( 'social/facebook.svg' ),
				'icon-2' => betterdocs()->assets->icon( 'social/facebook-box-line.svg' ),
				'link'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $_permalink
			],
			'twitter'   => [
				'alt'    => 'X',
				'icon'   => betterdocs()->assets->icon( 'social/twitter.svg' ),
				'icon-2' => betterdocs()->assets->icon( 'social/twitter-x-line.svg' ),
				'link'   => 'https://twitter.com/intent/tweet?url=' . $_permalink
			],
			'linkedin'  => [
				'alt'    => 'LinkedIn',
				'icon'   => betterdocs()->assets->icon( 'social/linkedin.svg' ),
				'icon-2' => betterdocs()->assets->icon( 'social/linkedin-box-line.svg' ),
				'link'   => 'https://www.linkedin.com/shareArticle?mini=true&url=' . $_permalink
			],
			'pinterest' => [
				'alt'    => 'Pinterest',
				'icon'   => betterdocs()->assets->icon( 'social/pinterest.svg' ),
				'icon-2' => betterdocs()->assets->icon( 'social/pinterest-line.svg' ),
				'link'   => 'https://pinterest.com/pin/create/button/?url=' . $_permalink
			],
			// 'instagram' => [
			//     'alt'  => 'Instagram',
			//     'icon' => betterdocs()->assets->icon( 'social/instagram.svg' ),
			//     'icon-2' => betterdocs()->assets->icon( 'social/instagram-line.svg' ),
			//     'link' => 'https://pinterest.com/pin/create/button/?url=' . $_permalink
			// ]
		];

		return array_filter(
			$_defaults,
			function ( $key ) {
				return array_key_exists( $key, $this->attributes ) && (bool) filter_var( $this->attributes[ $key ], FILTER_VALIDATE_BOOLEAN );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	public function view_params() {
		$links                     = $this->generate_links();
		$this->attributes['links'] = $links;

		if ( isset( $this->attributes['share_title_select_layout_layout'] ) && $this->attributes['share_title_select_layout_layout'] == 'layout-2' ) { // for elementor only
			$this->view_wrapper .= ' layout-2';
		}

		if ( isset( $this->attributes['layout'] ) && $this->attributes['layout'] == 'layout-2' ) { //for blocks
			$this->view_wrapper .= ' layout-2';
		}

		return [
			'links' => $links
		];
	}
}
