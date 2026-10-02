<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class AlphaColorControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-alpha-color';

	/**
	 * Add support for palettes to be passed in.
	 *
	 * Supported palette values are true, false, or an array of RGBa and Hex colors.
	 *
	 * @var bool
	 */
	public $palette;

	/**
	 * Add support for showing the opacity value on the slider handle.
	 *
	 * @var array
	 */
	public $show_opacity;

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-alpha-color-picker',
			'customizer/js/alpha-color-picker.js',
			[ 'jquery', 'wp-color-picker' ]
		);
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-alpha-color-picker',
			'customizer/css/alpha-color-picker.css',
			[ 'wp-color-picker' ]
		);
	}

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		betterdocs()->views->get( 'admin/customizer/controls/alpha-color', [ 'control' => $this ] );
	}
}
