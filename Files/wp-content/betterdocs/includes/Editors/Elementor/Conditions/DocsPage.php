<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class DocsPage extends Condition_Base {

	public static function get_type() {
		return 'archive';
	}

	public function get_name() {
		return 'docs_page';
	}

	public static function get_priority() {
		return 40;
	}

	public function get_label() {
		return __( 'Docs Page', 'betterdocs' );
	}

	public function check( $args ) {
		return is_post_type_archive( 'docs' );
	}
}
