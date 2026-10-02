<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer;

use WP_Customize_Setting;
use WPDeveloper\BetterDocs\Utils\Base;

class Sanitizer extends Base {
	/**
	 * Sanitize options like value. Works for Select.
	 *
	 * @since 1.0.0
	 *
	 * @param string $input
	 * @param WP_Customize_Setting $setting
	 *
	 * @return string
	 */
	public function select( $input, $setting ) {
		$input   = sanitize_key( $input );
		$choices = $setting->manager->get_control( $setting->id )->choices;

		//return input if valid or return default option
		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}

	/**
	 * Choice
	 *
	 * @param mixed $input
	 * @param mixed $setting
	 * @return string
	 */

	public function choices( $input, $setting ) {
		return $this->select( $input, $setting );
	}

	/**
	 *
	 * Sanitize checkbox values
	 *
	 * @since 1.0.0
	 */
	public function checkbox( $input ) {
		return $input ? '1' : false;
	}

	/**
	 * Sanitize integers
	 * @since 1.0.0
	 *
	 * @param int $input
	 *
	 * @return int
	 */
	public function integer( $input ) {
		return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
	}

	/**
	 * Sanitize Float
	 * @param float $input
	 * @since 1.0.0
	 *
	 * @return float
	 */
	public function float( $input ) {
		return filter_var( $input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
	}

	/**
	 * Sanitize checkbox values
	 * @param mixed $color
	 * @return string
	 */
	public function rgba( $color ) {
		if ( empty( $color ) || is_array( $color ) ) {
			return 'rgba(0,0,0,0)';
		}

		// If string does not start with 'rgba', then treat as hex
		// sanitize the hex color and finally convert hex to rgba
		if ( false === strpos( $color, 'rgba' ) ) {
			return sanitize_hex_color( $color );
		}

		// By now we know the string is formatted as an rgba color so we need to further sanitize it.
		$color = str_replace( ' ', '', $color );
		sscanf( $color, 'rgba(%d,%d,%d,%f)', $red, $green, $blue, $alpha );
		return 'rgba(' . $red . ',' . $green . ',' . $blue . ',' . $alpha . ')';
	}
}
