<?php

namespace WPDeveloper\BetterDocs\Editors;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\Enqueue;

abstract class BaseEditor extends Base {
	protected $is_pro_active = false;

	/**
	 * Settings
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Settings
	 * @var Enqueue
	 */
	protected $assets;

	public function __construct( Settings $settings, Enqueue $enqueue ) {
		$this->settings      = $settings;
		$this->assets        = $enqueue;
		$this->is_pro_active = betterdocs()->is_pro_active();
	}

	abstract public function init();
}
