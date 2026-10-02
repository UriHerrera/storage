<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class SeparatorControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'separator';

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		betterdocs()->views->get( 'admin/customizer/controls/separator', [ 'control' => $this ] );
	}
}
