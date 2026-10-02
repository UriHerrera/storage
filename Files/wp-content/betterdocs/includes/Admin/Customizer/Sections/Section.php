<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\Enqueue;
use WPDeveloper\BetterDocs\Admin\Customizer\Sanitizer;

abstract class Section {
	protected $customizer = null;
	protected $defaults   = null;
	/**
	 * Customizer Panel ID
	 *
	 * @var string
	 */
	protected $panel_id = 'betterdocs_customize_options';

	/**
	 * Sanitizer
	 *
	 * @var Sanitizer
	 */
	protected $sanitizer;

	/**
	 * Settings
	 * @var Settings
	 */
	protected $settings;
	protected $priority = 100;

	abstract public function get_id();
	abstract public function get_title();

	/**
	 * Enqueue Manager
	 *
	 * @var Enqueue
	 */
	protected $assets;

	public function __construct( Sanitizer $sanitizer, Settings $settings ) {
		$this->sanitizer = $sanitizer;
		$this->settings  = $settings;
		$this->assets    = betterdocs()->assets;
	}

	public function set_customizer( $customizer, $defaults ) {
		$this->customizer = $customizer;
		$this->defaults   = $defaults;
	}

	public function section() {
		$this->customizer->add_section(
			$this->get_id(),
			[
				'title'    => $this->get_title(),
				'priority' => $this->priority,
				'panel'    => $this->panel_id
			]
		);
	}

	public function register( $customizer, $defaults ) {
		$this->set_customizer( $customizer, $defaults );
		$_methods = get_class_methods( $this );
		if ( ! empty( $_methods ) ) {
			$this->section();

			foreach ( $_methods as $method ) {
				if ( in_array( $method, [ 'get_id', 'section', 'get_title', 'register', 'set_customizer', '__construct' ] ) ) {
					continue;
				}

				$this->{$method}();
			}
		}
	}
}
