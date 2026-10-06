<?php
namespace WPDeveloper\BetterDocs\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;

class Analytics extends Base {
	/**
	 * Database class
	 * @var Database
	 */
	protected $database;

	public function __construct( Database $database ) {
		$this->database = $database;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, '_enqueue' ] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'betterdocs-analytics' === $page ) {
			add_action( 'betterdocs_settings_header', [ $this, 'header' ] );
		}
	}

	public function enqueue( $hook ) {
		if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
			return;
		}

		betterdocs()->assets->enqueue( 'betterdocs-analytics', 'admin/css/analytics.css' );
	}

	public function _enqueue( $hook ) {
		if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
			return;
		}

		$this->enqueue( 'betterdocs_page_betterdocs-analytics' );
	}

	public function header() {
		betterdocs()->views->get(
			'admin/template-parts/settings-header',
			[
				'title' => __( 'BetterDocs Analytics', 'betterdocs' )
			]
		);
	}

	public function views() {
		betterdocs()->views->get( 'admin/analytics' );
	}
}
