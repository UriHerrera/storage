<?php

namespace WPDeveloper\BetterDocs\Utils;

abstract class Base {
	/**
	 * Plugin Instance
	 * @var array
	 */
	protected static $_instance = [];

	/**
	 * Create a plugin instance.
	 *
	 * @since 2.5.0
	 * @param mixed ...$args
	 *
	 * @return static
	 *
	 * @suppress PHP0441
	 */
	public static function get_instance( ...$args ) {
		$class = get_called_class();

		if ( ! isset( static::$_instance[ $class ] ) || static::$_instance[ $class ] == null ) {
			static::$_instance[ $class ] = new static( ...$args );
		}

		return static::$_instance[ $class ];
	}
}
