<?php
/**
 * Version 2.x Fallback Classes
 *
 * This file is needed if any users uses our Pro Plugin if they are not updated Pro yet to v3+.
 */

namespace Better_Docs_Elementor\Traits {
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	trait Template_Query {
		private function get_default() {}
		private function template_options() {}
	}
}

namespace {
	define( 'BETTERDOCS_URL', '' );
	define( 'BETTERDOCS_ADMIN_URL', '' );

	class BetterDocs_DB {
		public static function get_settings() {}
	}

	class BetterDocs {}
	class BetterDocs_Helper {
		public static function term_options() {}
		public static function get_tax() {}
		public static function get_doc_terms() {}
		public static function list_query_arg() {}
		public static function faq_term_list() {}
		public static function html_tag() {}
		public static function permalink_stracture() {}
		public static function term_permalink() {}
		public static function taxonomy_object() {}
		public static function list_svg() {}
		public static function child_taxonomy_terms() {}
		public static function arrow_right_svg() {}
		public static function arrow_down_svg() {}
	}

	class BetterDocs_Elementor {
		public static function elbd_validate_html_tag() {}
		public static function get_betterdocs_multiple_kb_status() {}
	}

	function betterdocs_get_option() {}
	function betterdocs_breadcrumbs() {}
}
