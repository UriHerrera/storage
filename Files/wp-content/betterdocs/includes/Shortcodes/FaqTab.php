<?php
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Shortcodes\FaqLayoutThree;

class FaqTab extends FaqLayoutThree {
	protected $layout    = 'layout-4';

	public function get_name() {
		return 'betterdocs_faq_tab';
	}

	public function render( $atts, $content = null ) {

		$this->icon_position = isset( $this->atts['button_icon_position'] ) ? $this->atts['button_icon_position'] : '';

		if ( $this->attributes['is_gutenberg'] && $this->attributes['button_icon_position'] == 'before' && $this->attributes['show_button_icon'] ) {
			add_action( 'betterdocs_faq_post_before', [ $this, 'icons' ] );
		} elseif ( $this->attributes['is_gutenberg'] && $this->attributes['button_icon_position'] == 'after' && $this->attributes['show_button_icon'] ) {
			add_action( 'betterdocs_faq_post_after', [ $this, 'icons' ] );
		} else {
			add_action( $this->icon_hook, [ $this, 'icons' ] );
		}

		$this->views( 'shortcodes/faq-tab' );

		remove_action( $this->icon_hook, [ $this, 'icons' ] );
	}
}
