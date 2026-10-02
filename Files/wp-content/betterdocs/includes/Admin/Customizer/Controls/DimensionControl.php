<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class DimensionControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-dimension';

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		betterdocs()->views->get( 'admin/customizer/controls/dimension', [ 'control' => $this ] );
	}
}
