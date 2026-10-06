<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class SelectControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-select';

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		if ( empty( $this->choices ) ) {
			return;
		}

		betterdocs()->views->get( 'admin/customizer/controls/select', [ 'control' => $this ] );
	}
}
