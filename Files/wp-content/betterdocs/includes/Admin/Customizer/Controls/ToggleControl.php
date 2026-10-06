<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class ToggleControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-toggle';

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-toggle-control',
			'customizer/js/customizer-toggle-control.js',
			[ 'jquery' ]
		);
		betterdocs()->assets->enqueue(
			'betterdocs-pure-css-toggle-buttons',
			'customizer/css/customizer-toggle-buttons.css'
		);

		$css = '
			.disabled-control-title {
				color: #a0a5aa;
			}
			input[type=checkbox].tgl-light:checked + .tgl-btn {
				background: #37de89;
			}
			input[type=checkbox].tgl-light + .tgl-btn {
				background: #a0a5aa;
			}
			input[type=checkbox].tgl-light + .tgl-btn:after {
				background: #f7f7f7;
			}

			input[type=checkbox].tgl-ios:checked + .tgl-btn {
				background: #37de89;
			}

			input[type=checkbox].tgl-flat:checked + .tgl-btn {
				border: 4px solid #37de89;
			}
			input[type=checkbox].tgl-flat:checked + .tgl-btn:after {
				background: #37de89;
			}
		';
		wp_add_inline_style( 'pure-css-toggle-buttons', $css );
	}

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		betterdocs()->views->get( 'admin/customizer/controls/toggle', [ 'control' => $this ] );
	}
}
