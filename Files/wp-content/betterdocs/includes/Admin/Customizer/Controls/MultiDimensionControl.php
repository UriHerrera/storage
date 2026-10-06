<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Controls;

use WP_Customize_Control;

class MultiDimensionControl extends WP_Customize_Control {
	/**
	 * Control name.
	 * @var string
	 */
	public $type = 'betterdocs-multi-dimension';

	public $defaults;
	public $input_fields;

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		betterdocs()->assets->enqueue(
			'betterdocs-customizer-dimension-control',
			'customizer/js/customizer-dimension-control.js',
			[ 'jquery' ]
		);
	}

	/**
	 * Render the control's content.
	 *
	 * @version 1.0.0
	 */
	public function render_content() {
		if ( $this->value() ) {
			if ( is_array( $this->value() ) ) {
				$dimension_val = $this->value();
			} else {
				$dimension_val = (array) json_decode( $this->value() );
			}
		} else {
			$dimension_val = $this->defaults;
		}

		betterdocs()->views->get(
			'admin/customizer/controls/multidimension',
			[
				'control'        => $this,
				'hasLabel'       => isset( $this->label ) && '' !== $this->label,
				'label'          => $this->label,
				'hasDescription' => isset( $this->description ) && '' !== $this->description,
				'description'    => $this->description,
				'dimension_val'  => $dimension_val
			]
		);
	}
}
