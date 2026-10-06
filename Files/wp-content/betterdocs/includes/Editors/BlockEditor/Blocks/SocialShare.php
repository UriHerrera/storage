<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocs\Traits\SocialShare as SocialShareTrait;

class SocialShare extends Block {
	use SocialShareTrait;

	protected $map_view_vars = [
		'share_title' => 'title',
		'share_title_tag' => 'title_tag'
	];

	protected $editor_styles = [
		'betterdocs-social-share'
	];

	protected $frontend_styles = [
		'betterdocs-social-share'
	];

	public function get_name() {
		return 'social-share';
	}

	public function get_default_attributes() {
		return [
			'show_facebook_icon'  => true,
			'show_twitter_icon'   => true,
			'show_linkedin_icon'  => true,
			'show_pinterest_icon' => true,
			'show_instagram_icon' => true,
			'share_title'         => __( 'Share This Article: ', 'betterdocs' ),
			'share_title_tag'     => 'h4',
			'layout'              => 'layout-1'
		];
	}

	protected $deprecated_attributes = [
		'show_facebook_icon'  => 'facebook',
		'show_pinterest_icon' => 'pinterest',
		'show_twitter_icon'   => 'twitter',
		'show_linkedin_icon'  => 'linkedin'
	];

	public function render( $attributes, $content ) {
		$layout = $attributes['layout'];
		if ( $layout == 'layout-1' ) {
			$this->views( 'widgets/social' );
		} else {
			$this->views( 'widgets/social-2' );
		}
	}
}
