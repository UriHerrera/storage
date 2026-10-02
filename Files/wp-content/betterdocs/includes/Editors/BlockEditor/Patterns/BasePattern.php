<?php
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Patterns;

use WPDeveloper\BetterDocs\Utils\Base;

abstract class BasePattern extends Base {
	/**
	 * Register Pattren category.
	 * @return void
	 */
	public function pattern_category() {
		register_block_pattern_category(
			'betterdocs-docs-page',
			array( 'label' => __( 'Docs Page', 'betterdocs' ) )
		);
		register_block_pattern_category(
			'betterdocs-catgory',
			array( 'label' => __( 'Docs Category', 'betterdocs' ) )
		);
		register_block_pattern_category(
			'betterdocs-single-docs',
			array( 'label' => __( 'Single Docs', 'betterdocs' ) )
		);
		register_block_pattern_category(
			'betterdocs-multiple-kb',
			array( 'label' => __( 'Multiple KB', 'betterdocs' ) )
		);
	}

	/**
	 * Register Pattren category.
	 * @return void
	 */
	abstract public function register();
}
