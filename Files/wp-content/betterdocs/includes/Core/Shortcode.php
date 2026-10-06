<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Traits\EditorHelper;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

abstract class Shortcode extends Base {
	use EditorHelper;

	/**
	 * Global Settings Reference.
	 * @var Settings
	 */
	protected $settings;
	/**
	 * Global Query Reference.
	 * @var Query
	 */
	protected $query;
	/**
	 * Utility Helper
	 * @var Helper
	 */
	protected $helper;
	/**
	 * Customizer Defaults.
	 * @var Defaults
	 */
	protected $customizer;

	public function __construct( Settings $settings, Query $query, Helper $helper, Defaults $defaults ) {
		$this->settings   = $settings;
		$this->query      = $query;
		$this->helper     = $helper;
		$this->customizer = $defaults;

		// add_action( 'betterdocs_before_shortcode_load', [$this, 'enqueue_scripts'], 11, 1 );
	}

	/**
	 * Shortcode Tag Name.
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * A list of default attributes.
	 * @return array
	 */
	abstract public function default_attributes();

	/**
	 * Individual shortcode render callback.
	 * @param mixed $atts
	 * @param mixed $content
	 * @return mixed
	 */
	abstract public function render( $atts, $content = null );

	public function get_style_depends() {
		return [];
	}

	public function get_script_depends() {
		return [];
	}

	public function enqueue_scripts() {
		foreach ( $this->get_style_depends() as $handle ) {
			wp_enqueue_style( $handle );
		}

		foreach ( $this->get_script_depends() as $handle ) {
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Shortcode render callback, which is hooked with add_shortcode function.
	 *
	 * @param mixed $atts
	 * @param mixed $content
	 *
	 * @return bool|string
	 */
	final public function render_with_hooks( $atts, $content = null ) {
		$this->enqueue_scripts();

		$atts = $this->remove_deprecated_attributes( $atts );
		$this->transform_attribute_types( $atts );

		$this->client_attributes = empty( $atts ) ? [] : $atts;
		do_action( 'betterdocs_before_shortcode_load', $this );
		$this->set_attributes( $atts );

		// reset attributes;
		$this->reset_attributes();

		do_action_ref_array( 'betterdocs_before_render', [ & $this, 'shortcode' ] );
		ob_start();
		$this->render( $atts, $content = null );
		$content = ob_get_clean();

		do_action_ref_array( 'betterdocs_after_render', [ & $this, 'shortcode' ] );

		return $content;
	}

	protected function transform_attribute_types( &$atts ) {
		if ( is_array( $atts ) ) {
			$_default_attributes = $this->default_attributes();

			foreach ( $atts as $key => $value ) {
				if ( isset( $_default_attributes[ $key ] ) ) {
					$type = gettype( $_default_attributes[ $key ] );

					if ( $type === 'boolean' ) {
						$atts[ $key ] = $atts[ $key ] == 'false' || $atts[ $key ] == '' ? false : true;
					}
				}
			}
		}
	}

	/**
	 * This method is responsive for set shortcode attributes in a property.
	 * @param mixed $atts
	 * @return array
	 */
	private function set_attributes( $atts ) {
		$this->attributes = shortcode_atts(
			apply_filters( 'betterdocs_shortcodes_default_atts', $this->default_attributes(), $this->get_name() ),
			$atts
		);

		return $this->attributes;
	}

	public function isset( $key, $value = null ) {
		if ( null === $value ) {
			return isset( $this->attributes[ $key ] ) && ! empty( $this->attributes[ $key ] );
		}

		return isset( $this->attributes[ $key ] ) && $this->attributes[ $key ] == $value;
	}
}
