<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use ElementorPro\Modules\Woocommerce\Tags\Base_Tag;

class TitleTag extends Base_Tag {
	public function get_name() {
		return 'betterdocs-title-tag';
	}

	public function get_title() {
		return __( 'Doc Title', 'betterdocs' );
	}

	public function render() {
		if ( get_post_type( get_the_ID() ) != 'docs' ) {
			return;
		}

		echo esc_html( betterdocs()->template_helper->kses( get_the_title() ) );
	}
}
