<?php

namespace WPDeveloper\BetterDocs\Admin\Builder;

class GlobalFields {
	public static function normalize_fields( $fields, $key = '', $value = [], $return = [] ) {
		foreach ( $fields as $val => $label ) {
			if ( empty( $return[ $val ] ) && ! is_array( $label ) ) {
				$return[ $val ] = [
					'value' => $val,
					'label' => $label
				];
			} elseif ( empty( $return[ $val ] ) ) {
				$return[ $val ] = $label;
			}
			if ( ! empty( $key ) ) {
				$return[ $val ] = Rules::includes( $key, $value, false, $return[ $val ] );
			}
		}

		return $return;
	}

	public static function normalize( $arr ) {

		if ( ! empty( $arr['fields'] ) ) {
			$arr['fields'] = array_values( $arr['fields'] );
		}

		if ( ! empty( $arr['options'] ) ) {
			$arr['options'] = array_values( $arr['options'] );
		}

		if ( ! empty( $arr['tabs'] ) ) {
			$arr['tabs'] = array_values( $arr['tabs'] );
		}

		if ( is_array( $arr ) ) {
			foreach ( $arr as $key => $value ) {
				if ( is_array( $value ) ) {
					$arr[ $key ] = self::normalize( $value );
				}
			}
		}
		return $arr;
	}
}
