<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Shortcodes\ToC;
use WPDeveloper\BetterDocs\Shortcodes\FaqList;
use WPDeveloper\BetterDocs\Shortcodes\FaqTab;
use WPDeveloper\BetterDocs\Shortcodes\Reactions;
use WPDeveloper\BetterDocs\Shortcodes\FaqClassic;
use WPDeveloper\BetterDocs\Shortcodes\FaqLayoutThree;
use WPDeveloper\BetterDocs\Shortcodes\SearchForm;
use WPDeveloper\BetterDocs\Shortcodes\SearchModal;
use WPDeveloper\BetterDocs\Shortcodes\CategoryBox;
use WPDeveloper\BetterDocs\Shortcodes\SocialShare;
use WPDeveloper\BetterDocs\Shortcodes\CategoryGrid;
use WPDeveloper\BetterDocs\Shortcodes\CategoryList;
use WPDeveloper\BetterDocs\Shortcodes\FeedbackForm;
use WPDeveloper\BetterDocs\Shortcodes\ReadingTime;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;
use WPDeveloper\BetterDocs\Shortcodes\CategoryBoxThree;
use WPDeveloper\BetterDocs\Shortcodes\CategoryGridThree;

class ShortcodeFactory extends Base {
	/**
	 * Summary of Container
	 * @var Container
	 */
	private $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Shortcode Lists
	 *
	 * @since 2.5.0
	 *
	 * @return array<string>
	 */
	private function shortcode_list() {
		return apply_filters(
			'betterdocs_shortcodes',
			[
				CategoryBox::class,
				CategoryBoxThree::class,
				CategoryGrid::class,
				CategoryGridThree::class,
				CategoryList::class,
				SearchForm::class,
				SearchModal::class,
				ToC::class,
				SocialShare::class,
				FeedbackForm::class,
				FaqClassic::class,
				FaqLayoutThree::class,
				FaqList::class,
				FaqTab::class,
				Reactions::class,
				ReadingTime::class
			]
		);
	}

	/**
	 * Initialize all shortcodes
	 * @return void
	 */
	public function init() {
		if ( ! empty( $shortcodes = $this->shortcode_list() ) ) {
			foreach ( $shortcodes as $shortcode ) {
				$shortcode = $this->container->get( $shortcode );
				add_shortcode( $shortcode->get_name(), [ $shortcode, 'render_with_hooks' ] );
			}
		}
	}
}
