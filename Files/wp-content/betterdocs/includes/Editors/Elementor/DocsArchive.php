<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use ElementorPro\Modules\ThemeBuilder\Documents\Archive;

class DocsArchive extends Archive {
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['location']       = 'archive';
		$properties['condition_type'] = 'doc_archive';

		return $properties;
	}

	public static function get_type() {
		return 'doc-archive';
	}

	public static function get_title() {
		return __( 'Docs Archive', 'betterdocs' );
	}

	public static function get_plural_title() {
		return __( 'Docs Archives', 'betterdocs' );
	}

	protected static function get_site_editor_icon() {
		return 'eicon-products';
	}

	public static function get_preview_as_default() {
		return 'post_type_archive/docs';
	}

	public static function get_preview_as_options() {
		$post_type_archives = [];
		$taxonomies         = [];
		$post_type          = 'docs';

		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type_object ) {
			$post_type_archives[ 'post_type_archive/' . $post_type ] = $post_type_object->label . ' ' . __( 'Archive', 'betterdocs' );
		}

		$post_type_taxonomies = get_object_taxonomies( $post_type, 'objects' );

		$post_type_taxonomies = wp_filter_object_list(
			$post_type_taxonomies,
			[
				'public'            => true,
				'show_in_nav_menus' => true
			]
		);

		foreach ( $post_type_taxonomies as $slug => $object ) {
			$taxonomies[ 'taxonomy/' . $slug ] = $object->label . ' ' . __( 'Archive', 'betterdocs' );
		}

		$options = [
			'search' => __( 'Search Results', 'betterdocs' )
		];

		$options += $taxonomies + $post_type_archives;

		return [
			'archive' => [
				'label'   => __( 'Archive', 'betterdocs' ),
				'options' => $options
			]
		];
	}

	protected static function get_editor_panel_categories() {
		$categories  = [
			'docs-archive' => [
				'title' => __( 'Docs Archive', 'betterdocs' )
			]
		];
		$categories += parent::get_editor_panel_categories();
		unset( $categories['theme-elements-archive'] );
		return $categories;
	}

	public static function get_editor_panel_config() {
		$config = parent::get_editor_panel_config();

		$config['widgets_settings']['theme-archive-title']['categories'][] = 'docs-archive';
		return $config;
	}

	protected function register_controls() {
		parent::register_controls();

		$this->update_control(
			'preview_type',
			[
				'default' => 'post_type_archive/docs'
			]
		);
	}

	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category']          = 'Docs Archive';
		$config['urls']['thumbnail'] = plugins_url( '/assets/static/admin/images/customizer/customizer-preview.png', __FILE__ );
		return $config;
	}
}
