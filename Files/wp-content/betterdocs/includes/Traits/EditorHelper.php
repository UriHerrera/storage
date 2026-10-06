<?php

namespace WPDeveloper\BetterDocs\Traits;

use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

/**
 * Description
 *
 * @since 1.0.0
 * @package PackageName
 *
 * @method mixed betterdocs( $key = '' )
 */
trait EditorHelper {

	/**
	 * A list of deprecated attributes.
	 * <old attributes as key, new attributes as value>
	 * @var array<string, string>
	 */
	protected $deprecated_attributes = [];

	/**
	 * A list of paired attributes.
	 * @var array
	 */
	public $attributes = [];

	/**
	 * A list of attributes which is passed when a shortcode is used.
	 * @var array
	 */
	protected $client_attributes = [];

	/**
	 * A list of attributes which can be mapped as variables to the view.
	 * key as old variable, value to get as variable in views.
	 *
	 * @var array<string, string> An associative array containing string keys and string values.
	 */
	protected $map_view_vars = [];

	/**
	 * A list of attributes for markup.
	 * Which will converted to html attributes like string for use in html markup.
	 *
	 * @var array<string, mixed>
	 */
	protected $html_attributes = [ 'wrapper_attr', 'inner_wrapper_attr' ];

	/**
	 * Summary of view_params
	 * @return array
	 */
	protected function view_params() {
		return [];
	}

	public function reset_attributes() {
	}

	public function betterdocs( $key = 'settings' ) {
		switch ( $key ) {
			case 'settings':
				return betterdocs()->container->get( Settings::class );
			case 'query':
				return betterdocs()->container->get( Query::class );
			case 'helper':
				return betterdocs()->container->get( Helper::class );
			case 'customizer':
				return betterdocs()->container->get( Defaults::class );
		}
	}

	public function merge( $a, $b ) {
		return Helper::merge( $a, $b );
	}

	/**
	 * Define views for shortcode|elementor|blocks
	 * @param string $name
	 * @return void
	 */
	final protected function views( $name ) {
		/**
		 * Set widget_type property
		 */
		$this->get_widget_type();

		betterdocs()->views->get( $name, $this->get_view_params() );
	}

	/**
	 * If a shortcode has any deprecated attributes, we need to remap with new attributes
	 *
	 * @param mixed $atts
	 * @return mixed
	 */
	private function remove_deprecated_attributes( &$atts, $trigger_error = true, $not_remove_old = true ) {
		if ( empty( $this->deprecated_attributes ) || empty( $atts ) ) {
			return $atts;
		}

		foreach ( $this->deprecated_attributes as $oldAttr => $newAttr ) {
			if ( array_key_exists( $oldAttr, $atts ) ) {
				if ( $trigger_error ) {
					$this->error( $oldAttr, $newAttr );
				}

				$atts[ $newAttr ] = $atts[ $oldAttr ];
				if ( $not_remove_old ) {
					unset( $atts[ $oldAttr ] );
				}
			}
		}

		return $atts;
	}

	private function get_widget_type() {
		$_is_shortcode = ( $this instanceof Shortcode );
		$_is_elementor = ( $this instanceof BaseWidget );
		$_is_block     = ( $this instanceof Block );

		if ( $_is_shortcode ) {
			$this->widget_type = 'shortcode';
		} elseif ( $_is_elementor ) {
			$this->widget_type = 'elementor';
		} elseif ( $_is_block ) {
			$this->widget_type = 'blocks';
		}

		return $this->widget_type;
	}

	public function default_classnames() {
		switch ( $this->get_widget_type() ) {
			case 'blocks':
				return 'betterdocs-blocks ' . ( isset( $this->attributes['blockId'] ) ? $this->attributes['blockId'] : '' );
			case 'elementor':
				return 'betterdocs-elementor';
			case 'shortcode':
				return 'betterdocs-shortcode';
		}

		return '';
	}

	private function get_view_params() {
		$_origin_params = $this->view_params();

		if ( isset( $_origin_params['wrapper_attr'] ) ) {
			$_origin_params['wrapper_attr']['class'][] = $this->default_classnames();
		} else {
			$_origin_params['wrapper_attr'] = [
				'class' => [ $this->default_classnames() ]
			];
		}

		if ( isset( $this->is_pro ) && $this->is_pro ) {
			$_origin_params['wrapper_attr']['class'][] = 'betterdocs-pro';
		}

		if ( property_exists( $this, 'view_wrapper' ) ) {
			$_origin_params['wrapper_attr']['class'][] = $this->view_wrapper;
		}

		if ( ! empty( $this->html_attributes ) ) {
			foreach ( $this->html_attributes as $attr ) {
				if ( array_key_exists( $attr, $_origin_params ) ) {
					$_origin_params[ "{$attr}_array" ] = $_origin_params[ $attr ];
					$_origin_params[ $attr ]           = betterdocs()->template_helper->get_html_attributes( $_origin_params[ $attr ] );
				}
			}
		}

		$_view_params = wp_parse_args(
			[
				'widget'    => &$this,
				'widget_id' => $this->get_name()
			],
			$_origin_params
		);

		return wp_parse_args( $_view_params, $this->normalize_attributes( $this->attributes, $this->map_view_vars ) );
	}

	/**
	 * This method is a re-mapper or prettier for attributes variables.
	 *
	 * @param mixed $attributes
	 * @param mixed $mixed_settings
	 *
	 * @return mixed
	 */
	final public function normalize_attributes( $attributes, $mixed_settings = [] ) {
		$mixed_settings = array_merge(
			[
				'icon'               => 'show_icon',
				'nested_subcategory' => 'nested_subcategory',
				'posts_per_grid'     => 'posts_per_page',
				'orderby'            => 'post_orderby',
				'order'              => 'post_order',
				'list_icon'          => ''
			],
			$mixed_settings
		);

		$_return_value = [];

		foreach ( $mixed_settings as $old_key => $new_key ) {
			if ( isset( $attributes[ $old_key ] ) && ! empty( $new_key ) ) {
				$_return_value[ $new_key ] = $attributes[ $old_key ];
				unset( $attributes[ $old_key ] );
			}
		}

		return array_merge( $attributes, $_return_value );
	}

	/**
	 * Log error in debug.log if its enabled.
	 *
	 * @param string $key
	 * @param string $new_key
	 * @return void
	 */
	protected function error( $key, $new_key ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Developer-only deprecation notice; gated by WP_DEBUG so production never reaches it.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'"<strong>%1$s</strong>" attribute used in a %4$s called "%2$s" is no longer supported and will be removed in a future version. Instead, you should use the attribute <strong>%3$s</strong>.',
					esc_textarea( $key ), //to escape anything into strings
					esc_html( $this->get_name() ),
					esc_textarea( $new_key ), //to escape anything into strings
					esc_html( $this->get_widget_type() )
				),
				E_USER_NOTICE
			);
		}
	}

	/**
	 * Check if any attributes has passed.
	 *
	 * example: [shortcode_name attr1="value1"]
	 * this function will return true for $key attr1.
	 * before default value sets in attributes we had to check if user pass anything as attributes.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->client_attributes[ $key ] );
	}

	/**
	 * Get attribute as property.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->attributes[ $key ] ) ) {
			return $this->attributes[ $key ];
		}

		if ( property_exists( $this, $key ) ) {
			return $this->{$key};
		}

		return null;
	}

	/**
	 * Set property as attributes
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->attributes[ $key ] = $value;
	}

	/**
	 * Check if a property is set in attributes list.
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->attributes[ $key ] );
	}
}
