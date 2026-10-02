<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class RangeValueControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-range-value';

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-range-value-control',
			'customizer/js/customizer-range-value-control.js',
			[ 'jquery' ]
		);
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-range-value-control',
			'customizer/css/customizer-range-value-control.css'
		);
	}

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 *
	 */
	public function render_content() {
		betterdocs()->views->get( 'admin/customizer/controls/range-value', [ 'control' => $this ] );
	}
}
