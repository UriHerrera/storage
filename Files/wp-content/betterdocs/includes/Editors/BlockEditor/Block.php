<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Traits\EditorHelper;
use WPDeveloper\BetterDocs\Utils\Enqueue;

/**
 * Description
 *
 * @method void register_scripts()
 * @property-read mixed $attributes
 *
 * @since 2.5.0
 */
abstract class Block extends Base {
	use EditorHelper;

	public $is_pro = false;

	/**
	 * Enqueue
	 * @var Enqueue
	 */
	protected $assets_manager = null;

	protected $dir = '';

	protected $editor_scripts = [ 'betterdocs-blocks-editor' ];

	protected $editor_styles = [ 'betterdocs-blocks-editor' ];

	protected $frontend_styles  = [];
	protected $frontend_scripts = [];

	/**
	 * unique name of block
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Block can be enabled or not.
	 *
	 * Override if needed.
	 *
	 * @return bool
	 */
	public function can_enable() {
		return true;
	}

	public function path( $name = '' ) {
		if ( empty( $name ) ) {
			$name = $this->get_name();
		}

		return BETTERDOCS_BLOCKS_DIRECTORY . $name;
	}

	public function register_block_type( $name, ...$args ) {
		return register_block_type(
			apply_filters_ref_array( 'betterdocs.blocks.path', [ $this->path( $name ), $name, &$this ] ),
			...$args
		);
	}

	public function load_frontend_styles() {
		if ( empty( $this->frontend_styles ) ) {
			return;
		}

		foreach ( $this->frontend_styles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}

	public function load_frontend_scripts() {
		if ( empty( $this->frontend_scripts ) ) {
			return;
		}

		foreach ( $this->frontend_scripts as $handle ) {
			wp_enqueue_script( $handle );
		}
	}

	public function load_scripts() {
		$this->load_frontend_styles();
		$this->load_frontend_scripts();
	}

	/**
	 * Flatten a decoded selection attribute into a plain list of values.
	 *
	 * Selection attributes are saved by the editor as `[{ value, label }]`
	 * objects, but a bare list of values — `[5, 7]` — is what REST clients and
	 * other programmatic writers naturally produce. Both shapes are accepted:
	 * an array element contributes its `$key`, a scalar element contributes
	 * itself, and elements that carry no value are dropped.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed  $decoded Decoded attribute value. Anything that is not an array yields an empty list.
	 * @param string $key     Key holding the value on object elements. Default 'value'.
	 *
	 * @return array Re-indexed list of values.
	 */
	public static function normalize_id_list( $decoded, $key = 'value' ) {
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$_values = array_map(
			function ( $item ) use ( $key ) {
				return is_array( $item ) ? ( isset( $item[ $key ] ) ? $item[ $key ] : null ) : $item;
			},
			$decoded
		);

		return array_values(
			array_filter(
				$_values,
				function ( $value ) {
					return null !== $value;
				}
			)
		);
	}

	/**
	 * Decode a JSON attribute string into a list of values.
	 *
	 * @since 4.9.0 Bare-value lists (`"[5]"`) are accepted alongside the
	 *              editor's `[{ value, label }]` form, and no longer warn.
	 *
	 * @param mixed  $data JSON encoded attribute value; non-strings yield an empty list.
	 * @param string $key  Key holding the value on object elements. Default 'value'.
	 *
	 * @return array
	 */
	public function string_to_array( $data = [], $key = 'value' ) {
		$_return_val = [];

		if ( is_string( $data ) && ! empty( $data ) ) {
			$_return_val = self::normalize_id_list( json_decode( $data, true ), $key );
		}

		return $_return_val;
	}

	public function register( $assets_manager ) {
		$this->assets_manager = $assets_manager;

		$_args = [];

		if ( method_exists( $this, 'register_scripts' ) ) {
			$this->register_scripts();
		}

		if ( method_exists( $this, 'render_callback' ) ) {
			$_args['render_callback'] = function ( $attributes, $content ) {
				if ( ! is_admin() ) {
					$this->load_scripts();
				}
				return $this->render_callback( $attributes, $content );
			};
		}

		if ( ( ! empty( $this->frontend_scripts ) || ! empty( $this->frontend_styles ) ) && ! method_exists( $this, 'render_callback' ) ) {
			$_args['render_callback'] = function ( $attributes, $content ) {
				if ( ! is_admin() ) {
					$this->load_scripts();
				}
				return $content;
			};
		}

		$_args['editor_script']         = $this->editor_scripts;
		$_args['editor_script_handles'] = $this->editor_scripts;

		$_args['editor_style']         = $this->editor_styles;
		$_args['editor_style_handles'] = $this->editor_styles;

		if ( property_exists( $this, 'attributes' ) ) {
			$_args['attributes'] = $this->attributes;
		}

		return $this->register_block_type( $this->get_name(), $_args );
	}

	public function get_default_attributes() {
		return [];
	}

	abstract public function render( $attributes, $content );

	public function render_callback( $attributes, $content ) {
		$this->attributes = wp_parse_args( $attributes, $this->get_default_attributes() );
		$this->attributes = $this->remove_deprecated_attributes( $this->attributes, false, false );

		do_action_ref_array( 'betterdocs_before_render', [ & $this, 'blocks' ] );

		ob_start();
		$this->render( $this->attributes, $content );
		$content = ob_get_clean();

		do_action_ref_array( 'betterdocs_after_render', [ & $this, 'blocks' ] );

		return $content;
	}

	public function normalize_attributes( $attributes, $mixed_settings = [] ) {
		$mixed_settings = wp_parse_args(
			[
				'prefix'                  => 'count_prefix',
				'suffix'                  => 'count_suffix',
				'suffixSingular'          => 'count_suffix_singular',
				'showIcon'                => 'show_icon',
				'showTitle'               => 'show_title',
				'titleTag'                => 'title_tag',
				'showCount'               => 'show_count',
				'showButton'              => 'show_button',
				'showButtonIcon'          => 'show_button_icon',
				'buttonIconPosition'      => 'button_icon_position',
				'buttonText'              => 'button_text',
				'buttonIcon'              => 'button_icon',
				'showList'                => 'show_list',
				'showHeader'              => 'show_header',
				'postsPerPage'            => 'post_per_page',
				'postsOrderBy'            => 'post_orderby',
				'postsOrder'              => 'post_order',
				'enableNestedSubcategory' => 'nested_subcategory'
			],
			$mixed_settings
		);

		$_return_value = [];

		foreach ( $mixed_settings as $key => $old_key ) {
			if ( isset( $attributes[ $key ] ) ) {
				$_return_value[ $old_key ] = $attributes[ $key ];
				unset( $attributes[ $key ] );
			}
		}

		return array_merge( $attributes, $_return_value );
	}
}
