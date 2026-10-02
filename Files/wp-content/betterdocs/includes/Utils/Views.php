<?php

namespace WPDeveloper\BetterDocs\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Exception;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class Views extends Base {
	public $path         = '';
	public $_view_type   = 'free';
	protected $container = '';

	protected $layout_directory;

	private $nested = 0;

	public function __construct( $path, Container $container, $layout_directory = 'layouts/' ) {
		$this->path             = $path;
		$this->container        = $container;
		$this->layout_directory = $this->path . $layout_directory;
	}

	public function get_elementor_box_layouts() {
		$layouts = $this->get_layouts( 'category-box' );

		if ( isset( $layouts['layout-4'] ) ) {
			$layouts['layout-4'] = __( 'Sleek Layout', 'betterdocs' );
		}

		if ( isset( $layouts['default'] ) ) {
			$layouts['default'] = __( 'Box Layout', 'betterdocs' );
		}

		if ( isset( $layouts['layout-2'] ) ) {
			$layouts['layout-2'] = __( 'Card Layout', 'betterdocs' );
		}

		if ( isset( $layouts['layout-3'] ) ) {
			$layouts['layout-3'] = __( 'Classic Layout', 'betterdocs' );
		}

		return $layouts;
	}

	public function get_layouts( $_local_dir = 'category-grid', $is_pro = false ) {
		if ( empty( $_local_dir ) ) {
			$_local_dir = 'category-grid';
		}

		$dir = $this->layout_directory . $_local_dir;

		if ( ! is_dir( $dir ) && ! $is_pro ) {
			throw new Exception( esc_html( $dir ) . ': directory not exists.' );
		}

		return $this->normalize_scandir( $dir );
	}

	public function get_default_layout( $local_dir = 'category-grid', $layouts = [] ) {
		if ( empty( $layouts ) || ! is_array( $layouts ) ) {
			$layouts = $this->get_layouts( $local_dir );
		}

		$layout_keys = array_reverse( array_keys( $layouts ) );
		return strtolower( array_pop( $layout_keys ) );
	}

	public function normalize_scandir( $dir ) {
		$_layouts_dir = scandir( $dir );
		if ( ! empty( $_layouts_dir ) ) {
			$_return_value = [];
			foreach ( $_layouts_dir as $file ) {
				if ( $file == '.' || $file == '..' ) {
					continue;
				}

				$uniq_name = basename( $file, '.php' );
				$label     = ucwords( str_replace( [ '-', '_' ], ' ', $uniq_name ) );

				$_return_value[ $uniq_name ] = $label;
			}

			return $_return_value;
		}
		return [];
	}

	public function path( $name, $default = '' ) {
		$this->_view_type = 'free';
		$name             = str_replace( $this->path, '', $name );

		// Sanitize: strip any directory traversal sequences to prevent LFI.
		$name = str_replace( array( '../', '..\\' ), '', $name );

		$_filename = $this->path . $name . '.php';

		if ( ! file_exists( $_filename ) ) {
			$_filename = $this->path . $default . '.php';
		}

		if ( file_exists( $_filename ) ) {
			// Verify the resolved path stays within the plugin's base directory.
			$_real_path = realpath( $_filename );
			$_base_path = realpath( $this->path );

			if ( $_real_path === false || $_base_path === false || strpos( $_real_path, $_base_path ) !== 0 ) {
				return null;
			}

			return $_filename;
		}
	}

	public $params = [];

	public function get( $name, $params = [] ) {
		$_view_file_path = $this->path( $name );

		if ( ! $_view_file_path ) {
			return __( 'View file path not available.', 'betterdocs' );
		}

		if ( file_exists( $_view_file_path ) ) {
			if ( isset( $params['_view_file_path'] ) ) {
				unset( $params['_view_file_path'] );
			}

			$view_object = $this;
			$helper      = Helper::get_instance();
			$_view_type  = $this->_view_type;
			$params      = empty( $params ) ? $this->params : array_merge( $this->params, $params );

			extract( $params );
			$this->params = array_merge( $this->params, $params );
			include $_view_file_path;
		} else {
			return __( 'View file not exists.', 'betterdocs' );
		}
	}

	public function merge( $a, $b ) {
		return Helper::merge( $a, $b );
	}
}
