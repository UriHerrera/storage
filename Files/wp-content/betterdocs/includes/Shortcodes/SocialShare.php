<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Traits\SocialShare as SocialShareTrait;

class SocialShare extends Shortcode {
	use SocialShareTrait;

	protected $deprecated_attributes = [
		'facebook_sharing'  => 'facebook',
		'pinterest_sharing' => 'pinterest',
		'twitter_sharing'   => 'twitter',
		'linkedin_sharing'  => 'linkedin'
	];

	public function get_name() {
		return 'betterdocs_social_share';
	}

	public function get_style_depends() {
		return [ 'betterdocs-social-share' ];
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return [
			'layout'    => 'layout-1',
			'title'     => __( 'Share This Article :', 'betterdocs' ),
			'title_tag' => 'h5',
			'facebook'  => '1',
			'twitter'   => '1',
			'linkedin'  => '1',
			'pinterest' => '1',
			'instagram' => '1',
		];
	}

	public function render( $atts, $content = null ) {
		if ( isset( $atts['layout'] ) && $atts['layout'] == 'layout-2' ) {
			$this->views( 'widgets/social-2' );
		} else {
			$this->views( 'widgets/social' );
		}
	}
}
