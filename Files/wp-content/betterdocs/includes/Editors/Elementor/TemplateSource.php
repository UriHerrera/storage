<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\TemplateLibrary\Source_Base;

/**
 * Working with elementor plugin
 *
 *
 * @since      1.3.0
 * @package    BetterDocs
 * @subpackage BetterDocs/elementor
 * @author     WPDeveloper <support@wpdeveloper.com>
 */
class TemplateSource extends Source_Base {
	protected $template_prefix = 'betterdocs_';
	protected $cloud_url       = 'https://betterdocs.co/wp-json/bd_cloud/v1/';

	/**
	 * Local template IDs (templates bundled with the plugin)
	 */
	const LOCAL_TAG_ARCHIVE_ID = 'betterdocs_local_tag_archive_1';

	public function get_prefix() {
		return $this->template_prefix;
	}

	public function get_id() {
		return 'betterdocs-templates';
	}

	public function get_title() {
		return __( 'BetterDocs Templates', 'betterdocs' );
	}

	public function register_data() {}

	public function get_items( $args = [] ) {
		$_cache_key = 'betterdocs_remote_templates_v1';
		$templates  = get_transient( $_cache_key );

		if ( false === $templates ) {
			$templates = [];

			// Fetch remote templates
			$url            = $this->cloud_url . 'templates';
			$response       = wp_remote_get( $url, [ 'timeout' => 60 ] );
			$body           = wp_remote_retrieve_body( $response );
			$body           = json_decode( $body, true );
			$templates_data = ! empty( $body['data'] ) ? $body['data'] : [];

			if ( ! empty( $templates_data ) ) {
				foreach ( $templates_data as $template_data ) {
					$templates[] = $this->prepare_template( $template_data );
				}
			}

			set_transient( $_cache_key, $templates, DAY_IN_SECONDS );
		}

		// Note: local bundled templates are NOT included here.
		// They are injected exclusively via Elementor.php::get_templates() → http_response filter
		// to avoid duplicates (Elementor calls get_items() on registered sources separately).

		if ( ! empty( $args ) ) {
			$templates = wp_list_filter( $templates, $args );
		}

		return $templates;
	}

	/**
	 * Returns locally bundled premade templates (no remote fetch required).
	 *
	 * @return array
	 */
	public function get_local_templates() {
		return [
			[
				'accessLevel'     => 0,
				'template_id'     => self::LOCAL_TAG_ARCHIVE_ID,
				'source'          => 'remote',
				'type'            => 'block',
				'subtype'         => 'Docs Archive',
				'title'           => __( 'BetterDocs Tag Archive', 'betterdocs' ),
				'thumbnail'       => plugins_url( '/assets/static/admin/images/templates/tag-archive-thumb.png', BETTERDOCS_PLUGIN_FILE ),
				'date'            => '2024-01-01',
				'author'          => 'WPDeveloper',
				'tags'            => [ 'Docs Archive' ],
				'isPro'           => false,
				'popularityIndex' => 0,
				'trendIndex'      => 0,
				'hasPageSettings' => false,
				'url'             => plugins_url( '/assets/static/admin/images/templates/tag-archive-thumb.png', BETTERDOCS_PLUGIN_FILE ),
				'favorite'        => false,
			],
		];
	}

	public function prepare_template( $template_data ) {
		return [
			'accessLevel'     => 0,
			'template_id'     => $template_data['template_id'],
			'source'          => 'remote',
			'type'            => $template_data['type'],
			'subtype'         => $template_data['subtype'],
			'title'           => $template_data['title'],
			'thumbnail'       => $template_data['thumbnail'],
			'date'            => $template_data['date'],
			'author'          => $template_data['author'],
			'tags'            => $template_data['tags'],
			'isPro'           => ( 1 == $template_data['isPro'] ),
			'popularityIndex' => (int) $template_data['popularityIndex'],
			'trendIndex'      => (int) $template_data['trendIndex'],
			'hasPageSettings' => ( 1 == $template_data['hasPageSettings'] ),
			'url'             => $template_data['url'],
			'favorite'        => ( 1 == $template_data['favorite'] )
		];
	}

	/**
	 * Get remote template.
	 *
	 * Retrieve a single remote template from betterdocs.co
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int $template_id The template ID.
	 *
	 * @return array Remote template.
	 */
	public function get_item( $template_id ) {
		$templates = $this->get_items();

		return $templates[ $template_id ];
	}

	public function save_item( $template_data ) {
		return false;
	}

	public function update_item( $new_data ) {
		return false;
	}

	public function delete_template( $template_id ) {
		return false;
	}

	public function export_template( $template_id ) {
		return false;
	}

	public function get_data( array $args, $context = 'display' ) {
		// Serve local bundled templates without a remote HTTP request
		if ( isset( $args['template_id'] ) && $args['template_id'] === self::LOCAL_TAG_ARCHIVE_ID ) {
			return $this->get_local_tag_archive_data();
		}

		$file_url = $this->cloud_url . 'template/' . $args['template_id'];
		$request  = wp_remote_get( $file_url );
		$response = wp_remote_retrieve_body( $request );
		$body     = json_decode( $response, true );
		$data     = ! empty( $body['data'] ) ? $body['data'] : false;

		$result = [];

		$result['content']       = $this->replace_elements_ids( $data );
		$result['content']       = $this->process_export_import_content( $result['content'], 'on_import' );
		$result['page_settings'] = [];

		return $result;
	}

	/**
	 * Returns the Elementor template content for the Tag Archive premade template.
	 * Layout (mirrors the screenshot): Search bar (full-width) + Sidebar / Content two-column area.
	 *
	 * @return array
	 */
	protected function get_local_tag_archive_data() {
		$content = [
			// ── Row 1: Search bar (full-width container) ──────────────────────
			[
				'id'       => 'bdta0001',
				'elType'   => 'container',
				'settings' => [
					'flex_direction'  => 'row',
					'content_width'   => 'full',
					'padding'         => [
						'unit'     => 'px',
						'top'      => '20',
						'right'    => '20',
						'bottom'   => '20',
						'left'     => '20',
						'isLinked' => false,
					],
				],
				'elements' => [
					[
						'id'         => 'bdta0003',
						'elType'     => 'widget',
						'widgetType' => 'betterdocs-search-form',
						'settings'   => [],
						'elements'   => [],
					],
				],
			],

			// ── Row 2: Sidebar (33 %) + Content (67 %) ───────────────────────
			[
				'id'       => 'bdta0010',
				'elType'   => 'container',
				'settings' => [
					'flex_direction' => 'row',
					'content_width'  => 'full',
					'gap'            => [
						'unit' => 'px',
						'size' => 0,
					],
					'padding'        => [
						'unit'     => 'px',
						'top'      => '30',
						'right'    => '20',
						'bottom'   => '40',
						'left'     => '20',
						'isLinked' => false,
					],
				],
				'elements' => [

					// Left container – BetterDocs Sidebar
					[
						'id'       => 'bdta0011',
						'elType'   => 'container',
						'settings' => [
							'flex_direction' => 'column',
							'content_width'  => 'full',
							'width'          => [
								'unit' => '%',
								'size' => 33,
							],
						],
						'elements' => [
							[
								'id'         => 'bdta0012',
								'elType'     => 'widget',
								'widgetType' => 'betterdocs-sidebar',
								'settings'   => [],
								'elements'   => [],
							],
						],
					],

					// Right container – Breadcrumbs + Tag title + Doc list
					[
						'id'       => 'bdta0020',
						'elType'   => 'container',
						'settings' => [
							'flex_direction' => 'column',
							'content_width'  => 'full',
							'width'          => [
								'unit' => '%',
								'size' => 67,
							],
							'padding'        => [
								'unit'     => 'px',
								'top'      => '0',
								'right'    => '0',
								'bottom'   => '0',
								'left'     => '30',
								'isLinked' => false,
							],
						],
						'elements' => [
							// Breadcrumbs
							[
								'id'         => 'bdta0021',
								'elType'     => 'widget',
								'widgetType' => 'betterdocs-breadcrumb',
								'settings'   => [],
								'elements'   => [],
							],

							// Archive title – shows "Docs Tag: <tag-name>" dynamically
							[
								'id'         => 'bdta0022',
								'elType'     => 'widget',
								'widgetType' => 'theme-archive-title',
								'settings'   => [
									'title_size' => 'h2',
								],
								'elements'   => [],
							],

							// Docs list for the current tag
							[
								'id'         => 'bdta0023',
								'elType'     => 'widget',
								'widgetType' => 'betterdocs-category-archive-list',
								'settings'   => [
									'section_betterdocs_archive_list_layout' => 'layout-1',
								],
								'elements'   => [],
							],
						],
					],
				],
			],
		];

		$result                  = [];
		$result['content']       = $this->replace_elements_ids( $content );
		$result['content']       = $this->process_export_import_content( $result['content'], 'on_import' );
		$result['page_settings'] = [];

		return $result;
	}
}
