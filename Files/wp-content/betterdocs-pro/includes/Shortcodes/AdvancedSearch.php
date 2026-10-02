<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;
use WPDeveloper\BetterDocs\Shortcodes\SearchForm;

class AdvancedSearch extends SearchForm{
	public function get_script_depends() {
		$handlers = [ 'betterdocs-search', 'betterdocs-pro' ];

		if ( is_tax() ) {
			$handlers[] = 'betterdocs-glossaries';
		}
		return $handlers;
	}
}
