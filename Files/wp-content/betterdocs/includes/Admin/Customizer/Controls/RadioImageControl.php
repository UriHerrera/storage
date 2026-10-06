<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class RadioImageControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-radio-image';

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery-ui-button' );
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-radio-image-select',
			'customizer/css/customizer-radio-image-select.css'
		);
	}

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		if ( empty( $this->choices ) ) {
			return;
		}

		betterdocs()->views->get( 'admin/customizer/controls/radio-image', [ 'control' => $this ] );
	}
}
