<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WP_Customize_Control;
use WP_Customize_Media_Control;
use WP_Customize_Image_Control;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Admin\Customizer\Sanitizer;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\MultiDimensionControl;

class DocsPage extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority           = 100;
	protected $nested_subcategory = false;

	public function __construct( Sanitizer $sanitizer, Settings $settings ) {
		parent::__construct( $sanitizer, $settings );
		$this->nested_subcategory = $this->settings->get( 'nested_subcategory', false );
	}

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'betterdocs_doc_page_settings';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'Docs Page', 'betterdocs' );
	}

	public function category_layout() {
		$this->customizer->add_setting(
			'betterdocs_docs_layout_select',
			[
				'default'           => $this->defaults['betterdocs_docs_layout_select'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new RadioImageControl(
				$this->customizer,
				'betterdocs_docs_layout_select',
				[
					'type'     => 'betterdocs-radio-image',
					'settings' => 'betterdocs_docs_layout_select',
					'section'  => 'betterdocs_doc_page_settings',
					'label'    => __( 'Select Category Layout', 'betterdocs' ),
					'priority' => 2,
					'choices'  => apply_filters(
						'betterdocs_docs_layout_select_choices',
						[
							'layout-7' => [
								'label' => __( 'Sleek Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-7.png', true )
							],
							'layout-1' => [
								'label' => __( 'Grid Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-1.png', true )
							],
							'layout-8' => [
								'label' => __( 'Slate Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-8.png', true )
							],
							'layout-2' => [
								'label' => __( 'Box Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-2.png', true )
							],
							'layout-3' => [
								'label' => __( 'Card Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-3.png', true ),
								'pro'   => true,
								'url'   => 'https://betterdocs.co/upgrade'
							],
							'layout-4' => [
								'label' => __( 'Modern Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-4.png', true ),
								'pro'   => true,
								'url'   => 'https://betterdocs.co/upgrade'
							],
							'layout-5' => [
								'label' => __( 'Classic Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-5.png', true ),
								'pro'   => true,
								'url'   => 'https://betterdocs.co/upgrade'
							],
							'layout-6' => [
								'label' => __( 'Handbook Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/docs-page/layout-6.png', true ),
								'pro'   => ! betterdocs()->is_pro_active(),
								'url'   => 'https://betterdocs.co/upgrade'
							],
						]
					)
				]
			)
		);
	}

	public function content_area_background() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_background_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_background_color',
				[
					'label'    => __( 'Content Area Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_background_color',
					'priority' => 2
				]
			)
		);
	}

	public function content_area_background_image() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_background_image',
			[
				'default'    => $this->defaults['betterdocs_doc_page_background_image'],
				'capability' => 'edit_theme_options',
				'transport'  => 'postMessage'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_doc_page_background_image',
				[
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_background_image',
					'label'    => __( 'Background Image', 'betterdocs' ),
					'priority' => 3
				]
			)
		);
	}

	public function background_property() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_background_property',
			[
				'default'           => $this->defaults['betterdocs_doc_page_background_property'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_background_property',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_background_property',
					'label'       => __( 'Background Property', 'betterdocs' ),
					'priority'    => 4,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_background_property',
						'class' => 'betterdocs-select'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_background_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_background_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_page_background_size',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_background_size',
					'label'       => __( 'Size', 'betterdocs' ),
					'priority'    => 5,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_background_property betterdocs-select'
					],
					'choices'     => [
						'auto'    => __( 'auto', 'betterdocs' ),
						'length'  => __( 'length', 'betterdocs' ),
						'cover'   => __( 'cover', 'betterdocs' ),
						'contain' => __( 'contain', 'betterdocs' ),
						'initial' => __( 'initial', 'betterdocs' ),
						'inherit' => __( 'inherit', 'betterdocs' )
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_background_repeat',
			[
				'default'           => $this->defaults['betterdocs_doc_page_background_repeat'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_page_background_repeat',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_background_repeat',
					'label'       => __( 'Repeat', 'betterdocs' ),
					'priority'    => 6,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_background_property betterdocs-select'
					],
					'choices'     => [
						'no-repeat' => __( 'no-repeat', 'betterdocs' ),
						'initial'   => __( 'initial', 'betterdocs' ),
						'inherit'   => __( 'inherit', 'betterdocs' ),
						'repeat'    => __( 'repeat', 'betterdocs' ),
						'repeat-x'  => __( 'repeat-x', 'betterdocs' ),
						'repeat-y'  => __( 'repeat-y', 'betterdocs' )
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_background_attachment',
			[
				'default'           => $this->defaults['betterdocs_doc_page_background_attachment'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_page_background_attachment',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_background_attachment',
					'label'       => __( 'Attachment', 'betterdocs' ),
					'priority'    => 7,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_background_property betterdocs-select'
					],
					'choices'     => [
						'initial' => __( 'initial', 'betterdocs' ),
						'inherit' => __( 'inherit', 'betterdocs' ),
						'scroll'  => __( 'scroll', 'betterdocs' ),
						'fixed'   => __( 'fixed', 'betterdocs' ),
						'local'   => __( 'local', 'betterdocs' )
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_background_position',
			[
				'default'           => $this->defaults['betterdocs_doc_page_background_position'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_page_background_position',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_background_position',
					'label'       => __( 'Position', 'betterdocs' ),
					'priority'    => 8,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_background_property betterdocs-select'
					],
					'choices'     => [
						'left top'      => __( 'left top', 'betterdocs' ),
						'left center'   => __( 'left center', 'betterdocs' ),
						'left bottom'   => __( 'left bottom', 'betterdocs' ),
						'right top'     => __( 'right top', 'betterdocs' ),
						'right center'  => __( 'right center', 'betterdocs' ),
						'right bottom'  => __( 'right bottom', 'betterdocs' ),
						'center top'    => __( 'center top', 'betterdocs' ),
						'center center' => __( 'center center', 'betterdocs' ),
						'center bottom' => __( 'center bottom', 'betterdocs' )
					]
				]
			)
		);
	}

	public function content_area_padding() {

		$this->customizer->add_setting(
			'betterdocs_doc_page_content_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_page_content_padding'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_padding',
					'label'       => __( 'Content Area Padding', 'betterdocs' ),
					'priority'    => 9,
					'input_attrs' => [
						'id'    => 'betterdocs-doc-page-content-padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_content_padding_top',
			apply_filters(
				'betterdocs_doc_page_content_padding_top',
				[
					'default'           => $this->defaults['betterdocs_doc_page_content_padding_top'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 9,
					'input_attrs' => [
						'class' => 'betterdocs-doc-page-content-padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_content_padding_right',
			apply_filters(
				'betterdocs_doc_page_content_padding_right',
				[
					'default'           => $this->defaults['betterdocs_doc_page_content_padding_right'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 9,
					'input_attrs' => [
						'class' => 'betterdocs-doc-page-content-padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_content_padding_bottom',
			apply_filters(
				'betterdocs_doc_page_content_padding_bottom',
				[
					'default'           => $this->defaults['betterdocs_doc_page_content_padding_bottom'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 9,
					'input_attrs' => [
						'class' => 'betterdocs-doc-page-content-padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_content_padding_left',
			apply_filters(
				'betterdocs_doc_page_content_padding_left',
				[
					'default'           => $this->defaults['betterdocs_doc_page_content_padding_left'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 9,
					'input_attrs' => [
						'class' => 'betterdocs-doc-page-content-padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function content_area_width() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_content_width',
			apply_filters(
				'betterdocs_doc_page_content_width',
				[
					'default'           => $this->defaults['betterdocs_doc_page_content_width'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]

				]
			)
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_content_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_width',
					'label'       => __( 'Content Area Width', 'betterdocs' ),
					'priority'    => 14,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					]
				]
			)
		);
	}

	public function content_area_max_width() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_content_max_width',
			apply_filters(
				'betterdocs_doc_page_content_max_width',
				[
					'default'           => $this->defaults['betterdocs_doc_page_content_max_width'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_content_max_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_content_max_width',
					'label'       => __( 'Content Area Maximum Width', 'betterdocs' ),
					'priority'    => 15,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 100,
						'max'    => 1600,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function category_column_settings() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_settings',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_settings'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_page_column_settings',
				[
					'label'    => __( 'Category Column Settings', 'betterdocs' ),
					'priority' => 16,
					'settings' => 'betterdocs_doc_page_column_settings',
					'section'  => 'betterdocs_doc_page_settings'
				]
			)
		);
	}

	public function category_column_number() {
		$this->customizer->add_setting(
			'betterdocs_sleek_docs_page_column_number',
			[
				'default'           => $this->defaults['betterdocs_sleek_docs_page_column_number'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_sleek_docs_page_column_number',
				[
					'label'    => __( 'Category Column Number', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_sleek_docs_page_column_number',
					'type'     => 'select',
					'choices'  => [
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
					],
					'priority' => 16
				]
			)
		);
	}

	public function category_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_category_title_tag',
			[
				'default'           => $this->defaults['betterdocs_category_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_category_title_tag',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_category_title_tag',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6',
						'p'  => 'p'
					],
					'priority' => 16
				]
			)
		);
	}

	public function category_title_padding_bottom() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_padding_bottom'],
				'transport'         => 'postMessage',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_padding_bottom',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_cat_title_padding_bottom',
					'label'       => __( 'Category Title Padding Bottom', 'betterdocs' ),
					'priority'    => 17,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 0,
						'max'    => 500,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function space_between_columns() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_space',
			apply_filters(
				'betterdocs_doc_page_column_space',
				[
					'default'           => $this->defaults['betterdocs_doc_page_column_space'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_column_space',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_space',
					'label'       => __( 'Spacing Between Columns', 'betterdocs' ),
					'priority'    => 17,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function column_background_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_column_bg_color',
				[
					'label'    => __( 'Column Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_column_bg_color',
					'priority' => 18
				]
			)
		);
	}

	public function column_background_color_layout2() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_bg_color2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_bg_color2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_column_bg_color2',
				[
					'label'    => __( 'Column Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_column_bg_color2',
					'priority' => 18
				]
			)
		);
	}

	public function column_background_color_layout_7() {
		$this->customizer->add_setting(
			'column_background_color_layout_7',
			[
				'default'           => $this->defaults['column_background_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'column_background_color_layout_7',
				[
					'label'    => __( 'Column Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'column_background_color_layout_7',
					'priority' => 18
				]
			)
		);
	}

	public function colum_background_color_hover() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_hover_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_hover_bg_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_column_hover_bg_color',
				[
					'label'    => __( 'Column Hover Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_column_hover_bg_color',
					'priority' => 18
				]
			)
		);
	}

	public function colum_background_color_hover_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_hover_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_hover_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_column_hover_bg_color_layout_7',
				[
					'label'    => __( 'Column Hover Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_column_hover_bg_color_layout_7',
					'priority' => 18
				]
			)
		);
	}

	public function column_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding',
					'label'       => __( 'Column Padding', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_column_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function column_padding_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_layout_7',
					'label'       => __( 'Column Padding', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_column_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_padding_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function column_border_color_layout_7() {
		$this->customizer->add_setting(
			'column_border_color_layout_7',
			[
				'default'           => $this->defaults['column_border_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'column_border_color_layout_7',
				[
					'label'    => __( 'Column Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'column_border_color_layout_7',
					'priority' => 18
				]
			)
		);
	}

	public function show_category_icon() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_show_category_icon',
			[
				'default'           => $this->defaults['betterdocs_doc_page_show_category_icon'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_doc_page_show_category_icon',
				[
					'label'    => __( 'Show Category Icon', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_show_category_icon',
					'type'     => 'light', // light, ios, flat
					'priority' => 23
				]
			)
		);
	}

	public function category_icon_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_icon_size_layout1',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_icon_size_layout1'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_cat_icon_size_layout1',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_cat_icon_size_layout1',
					'label'       => __( 'Icon Size', 'betterdocs' ),
					'priority'    => 23,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 200,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function category_icon_size_layout2() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_icon_size_layout2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_icon_size_layout2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_cat_icon_size_layout2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_cat_icon_size_layout2',
					'label'       => __( 'Icon Size', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 200,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function category_icon_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_icon_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_icon_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_cat_icon_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_cat_icon_size_layout_7',
					'label'       => __( 'Icon Size', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 200,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function column_border_radius() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_borderr',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_borderr'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_column_borderr',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_borderr',
					'label'       => __( 'Column Border Radius', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_column_borderr',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_borderr_topleft',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_borderr_topleft'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_borderr_topleft',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_borderr_topleft',
					'label'       => __( 'Top Left', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_borderr_topright',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_borderr_topright'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_borderr_topright',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_borderr_topright',
					'label'       => __( 'Top Right', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_borderr_bottomright',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_borderr_bottomright'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_borderr_bottomright',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_borderr_bottomright',
					'label'       => __( 'Bottom Right', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_borderr_bottomleft',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_borderr_bottomleft'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_borderr_bottomleft',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_borderr_bottomleft',
					'label'       => __( 'Bottom Left', 'betterdocs' ),
					'priority'    => 24,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_borderr betterdocs-dimension'
					]
				]
			)
		);
	}

	public function category_title_font_size() {

		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_cat_title_font_size',
					'label'       => __( 'Category Title Font Size', 'betterdocs' ),
					'priority'    => 25,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function category_title_font_size_layout_7() {

		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_cat_title_font_size_layout_7',
					'label'       => __( 'Category Title Font Size', 'betterdocs' ),
					'priority'    => 25,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function category_title_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_color',
				[
					'label'    => __( 'Category Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_title_color',
					'priority' => 26
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_color2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_color2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_color2',
				[
					'label'    => __( 'Category Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_title_color2',
					'priority' => 26
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_color_layout_7',
				[
					'label'    => __( 'Category Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_title_color_layout_7',
					'priority' => 26
				]
			)
		);
	}

	public function category_title_color_hover() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_hover_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_hover_color',
				[
					'label'    => __( 'Category Title Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_title_hover_color',
					'priority' => 26
				]
			)
		);
	}

	public function category_title_margin_layout_7() {
		$this->customizer->add_setting(
			'category_title_margin_layout_7',
			[
				'default'           => $this->defaults['category_title_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'category_title_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'category_title_margin_layout_7',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 26,
					'input_attrs' => [
						'id'    => 'category-title-margin-layout-7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_title_margin_top_layout_7',
			[
				'default'           => $this->defaults['category_title_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_title_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'category_title_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 26,
					'input_attrs' => [
						'class' => 'category-title-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_title_margin_right_layout_7',
			[
				'default'           => $this->defaults['category_title_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_title_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'category_title_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 26,
					'input_attrs' => [
						'class' => 'category-title-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_title_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['category_title_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_title_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'category_title_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 26,
					'input_attrs' => [
						'class' => 'category-title-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_title_margin_left_layout_7',
			[
				'default'           => $this->defaults['category_title_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_title_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'category_title_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 26,
					'input_attrs' => [
						'class' => 'category-title-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function category_title_color_hover_layout_7() {
		$this->customizer->add_setting(
			'category_title_color_hover_layout_7',
			[
				'default'           => $this->defaults['category_title_color_hover_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'category_title_color_hover_layout_7',
				[
					'label'    => __( 'Category Title Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'category_title_color_hover_layout_7',
					'priority' => 26
				]
			)
		);
	}

	public function category_title_border_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_title_border_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_title_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_cat_title_border_color',
				[
					'label'    => __( 'Category Title Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_title_border_color',
					'priority' => 27
				]
			)
		);
	}

	public function category_description() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_desc',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_desc'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_doc_page_cat_desc',
				[
					'label'    => __( 'Category Description', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_desc',
					'type'     => 'light', // light, ios, flat
					'priority' => 28
				]
			)
		);
	}

	public function category_description_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_cat_desc_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_cat_desc_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_cat_desc_color',
				[
					'label'    => __( 'Category Description Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_cat_desc_color',
					'priority' => 28
				]
			)
		);
	}
	public function border_bottom_toggle() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_box_border_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_box_border_bottom'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_doc_page_box_border_bottom',
				[
					'label'    => __( 'Border Bottom Hover', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_box_border_bottom',
					'type'     => 'light', // light, ios, flat
					'priority' => 28
				]
			)
		);
	}

	public function border_bottom_width_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_box_border_bottom_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_box_border_bottom_size'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_box_border_bottom_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_box_border_bottom_size',
					'label'       => __( 'Border Bottom Hover Width', 'betterdocs' ),
					'priority'    => 28,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					]
				]
			)
		);

		// Border Bottom Color
		$this->customizer->add_setting(
			'betterdocs_doc_page_box_border_bottom_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_box_border_bottom_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_box_border_bottom_color',
				[
					'label'    => __( 'Border Bottom Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_box_border_bottom_color',
					'priority' => 28
				]
			)
		);
	}

	public function article_list_background_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_bg_color',
				[
					'label'    => __( 'Category Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_article_list_bg_color',
					'priority' => 28
				]
			)
		);
	}

	public function item_count_title() {
		$this->customizer->add_setting(
			'betterdocs_item_counter_title',
			[
				'default'           => $this->defaults['betterdocs_item_counter_title'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_item_counter_title',
				[
					'label'    => __( 'Category Item Counter', 'betterdocs' ),
					'settings' => 'betterdocs_item_counter_title',
					'section'  => 'betterdocs_doc_page_settings',
					'priority' => 28
				]
			)
		);
	}

	public function doc_page_item_count_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function doc_page_item_count_font_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_font_size_layout_7',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function doc_page_item_count_margin_layout_7() {
		$this->customizer->add_setting(
			'doc_page_item_count_margin_layout_7',
			[
				'default'           => $this->defaults['doc_page_item_count_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'doc_page_item_count_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'doc_page_item_count_margin_layout_7',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'id'    => 'doc-page-item-count-margin-layout-7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'doc_page_item_count_margin_top_layout_7',
			[
				'default'           => $this->defaults['doc_page_item_count_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'doc_page_item_count_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'doc_page_item_count_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'class' => 'doc-page-item-count-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'doc_page_item_count_margin_right_layout_7',
			[
				'default'           => $this->defaults['doc_page_item_count_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'doc_page_item_count_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'doc_page_item_count_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'class' => 'doc-page-item-count-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'doc_page_item_count_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['doc_page_item_count_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'doc_page_item_count_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'doc_page_item_count_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'class' => 'doc-page-item-count-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'doc_page_item_count_margin_left_layout_7',
			[
				'default'           => $this->defaults['doc_page_item_count_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'doc_page_item_count_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'doc_page_item_count_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 30,
					'input_attrs' => [
						'class' => 'doc-page-item-count-margin-layout-7 betterdocs-dimension'
					]
				]
			)
		);
	}


	public function doc_page_item_count_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_color',
				[
					'label'    => __( 'Item Count Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_color',
					'priority' => 29
				]
			)
		);
	}

	public function doc_page_item_count_color_layout2() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_color_layout2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_color_layout2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_color_layout2',
				[
					'label'    => __( 'Item Count Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_color_layout2',
					'priority' => 29
				]
			)
		);
	}

	public function doc_page_item_count_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_color_layout_7',
				[
					'label'    => __( 'Item Count Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_color_layout_7',
					'priority' => 29
				]
			)
		);
	}

	public function doc_page_item_count_hover_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_hover_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_hover_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_hover_color_layout_7',
				[
					'label'    => __( 'Item Count Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_hover_color_layout_7',
					'priority' => 29
				]
			)
		);
	}

	public function last_updated_time_layout_7() {
		$this->customizer->add_setting(
			'last_updated_time_layout_7',
			[
				'default'           => $this->defaults['last_updated_time_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'last_updated_time_layout_7',
				[
					'label'    => __( 'Last Updated Time', 'betterdocs' ),
					'settings' => 'last_updated_time_layout_7',
					'section'  => 'betterdocs_doc_page_settings',
					'priority' => 31
				]
			)
		);
	}

	public function last_updated_time_layout_7_font_size() {
		$this->customizer->add_setting(
			'last_updated_time_layout_7_font_size',
			[
				'default'           => $this->defaults['last_updated_time_layout_7_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'last_updated_time_layout_7_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'last_updated_time_layout_7_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 31,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function last_updated_time_layout_7_color() {
		$this->customizer->add_setting(
			'last_updated_time_layout_7_color',
			[
				'default'           => $this->defaults['last_updated_time_layout_7_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'last_updated_time_layout_7_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'last_updated_time_layout_7_color',
					'priority' => 31
				]
			)
		);
	}

	public function last_updated_time_layout_7_hover_color() {
		$this->customizer->add_setting(
			'last_updated_time_layout_7_hover_color',
			[
				'default'           => $this->defaults['last_updated_time_layout_7_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'last_updated_time_layout_7_hover_color',
				[
					'label'    => __( 'Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'last_updated_time_layout_7_hover_color',
					'priority' => 31
				]
			)
		);
	}

	public function last_updated_time_layout_7_background_color() {
		$this->customizer->add_setting(
			'last_updated_time_layout_7_background_color',
			[
				'default'           => $this->defaults['last_updated_time_layout_7_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'last_updated_time_layout_7_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'last_updated_time_layout_7_background_color',
					'priority' => 31
				]
			)
		);
	}

	public function last_updated_time_layout_7_background_hover_color() {
		$this->customizer->add_setting(
			'last_updated_time_layout_7_background_hover_color',
			[
				'default'           => $this->defaults['last_updated_time_layout_7_background_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'last_updated_time_layout_7_background_hover_color',
				[
					'label'    => __( 'Background Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'last_updated_time_layout_7_background_hover_color',
					'priority' => 31
				]
			)
		);
	}


	public function doc_page_item_count_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_bg_color',
					'priority' => 31
				]
			)
		);
	}

	public function doc_page_item_count_inner_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_inner_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_inner_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_inner_bg_color',
				[
					'label'    => __( 'Inner Circle Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_inner_bg_color',
					'priority' => 31
				]
			)
		);
	}

	public function doc_page_item_count_border_type() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_border_type',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_border_type'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_page_item_count_border_type',
				[
					'label'    => __( 'Inner Circle Border Type', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_border_type',
					'type'     => 'select',
					'choices'  => [
						'none'   => 'none',
						'solid'  => 'solid',
						'dashed' => 'dashed',
						'dotted' => 'dotted',
						'double' => 'double',
						'groove' => 'groove',
						'ridge'  => 'ridge',
						'inset'  => 'inset',
						'outset' => 'outset'
					],
					'priority' => 31
				]
			)
		);
	}

	public function doc_page_item_count_border_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_border_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_border_color'],
				'transport'         => 'postMessage',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_border_color',
				[
					'label'    => __( 'Inner Circle Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_item_count_border_color',
					'priority' => 31
				]
			)
		);
	}

	public function doc_page_item_count_inner_border_width() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_inner_border_width',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_inner_border_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_inner_border_width',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_inner_border_width',
					'label'       => __( 'Inner Circle Border Width', 'betterdocs' ),
					'priority'    => 31,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_item_count_inner_border_width',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_inner_border_width_top',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_inner_border_width_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_inner_border_width_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_inner_border_width_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 31,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_item_count_inner_border_width betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_inner_border_width_right',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_inner_border_width_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_inner_border_width_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_inner_border_width_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 31,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_item_count_inner_border_width betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_inner_border_width_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_inner_border_width_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_inner_border_width_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_inner_border_width_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 31,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_item_count_inner_border_width betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_item_count_inner_border_width_left',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_count_inner_border_width_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_item_count_inner_border_width_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_count_inner_border_width_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 31,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_item_count_inner_border_width betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_item_counter_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_item_counter_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_item_counter_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_item_counter_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_item_counter_size',
					'label'       => __( 'Counter Size (Height, Width)', 'betterdocs' ),
					'priority'    => 32,
					'input_attrs' => [
						'class'  => '',
						'min'    => 10,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	// TODO:

	//FIXME:

	public function doc_page_column_content_space() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_column_content_space',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_content_space'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_column_content_space',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_content_space',
					'label'       => __( 'Content Space Between', 'betterdocs' ),
					'priority'    => 33,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_column_content_space',
						'class' => 'betterdocs_doc_page_column_content_space betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_content_space_image',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_content_space_image'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_content_space_image',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_content_space_image',
					'label'       => __( 'Image', 'betterdocs' ),
					'priority'    => 33,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_content_space betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_content_space_title',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_content_space_title'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_content_space_title',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_content_space_title',
					'label'       => __( 'Title', 'betterdocs' ),
					'priority'    => 33,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_content_space betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_content_space_desc',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_content_space_desc'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_content_space_desc',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_content_space_desc',
					'label'       => __( 'Description', 'betterdocs' ),
					'priority'    => 33,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_content_space betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_column_content_space_counter',
			[
				'default'           => $this->defaults['betterdocs_doc_page_column_content_space_counter'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_column_content_space_counter',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_column_content_space_counter',
					'label'       => __( 'Counter', 'betterdocs' ),
					'priority'    => 33,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_column_content_space betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_article_list_settings() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_settings',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_settings'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_settings',
				[
					'label'    => __( 'Docs List', 'betterdocs' ),
					'settings' => 'betterdocs_doc_page_article_list_settings',
					'section'  => 'betterdocs_doc_page_settings',
					'priority' => 33
				]
			)
		);
	}

	public function doc_page_article_list_button_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_button_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_button_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_button_bg_color',
				[
					'label'    => __( 'Docs List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_article_list_button_bg_color',
					'priority' => 34
				]
			)
		);
	}

	public function doc_page_article_list_icon() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_icon',
			[
				'default'    => $this->defaults['betterdocs_doc_page_article_list_icon'],
				'capability' => 'edit_theme_options',

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_doc_page_article_list_icon',
				[
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_article_list_icon',
					'label'    => __( 'List Icon', 'betterdocs' ),
					'priority' => 34
				]
			)
		);
	}

	public function doc_page_article_list_padding_2() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_2',
					'label'       => __( 'Docs List Padding', 'betterdocs' ),
					'priority'    => 34,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_article_list_padding_2',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_top_2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_top_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_top_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_top_2',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 34,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding_2 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_right_2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_right_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_right_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_right_2',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 34,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding_2 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_bottom_2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_bottom_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_bottom_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_bottom_2',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 34,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding_2 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_left_2',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_left_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_left_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_left_2',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 34,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding_2 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_article_list_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_color',
				[
					'label'    => __( 'Docs List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_article_list_color',
					'priority' => 35
				]
			)
		);
	}

	public function doc_page_article_list_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_hover_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_hover_color',
				[
					'label'    => __( 'Docs List Item Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_article_list_hover_color',
					'priority' => 36
				]
			)
		);
	}

	public function doc_page_article_list_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_font_size',
					'label'       => __( 'Docs List Item Font Size', 'betterdocs' ),
					'priority'    => 37,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function doc_page_list_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_list_icon_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_list_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_list_icon_color',
				[
					'label'    => __( 'List Item Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_list_icon_color',
					'priority' => 38
				]
			)
		);
	}

	public function doc_page_list_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_list_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_list_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_list_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_list_icon_font_size',
					'label'       => __( 'List Item Icon Font Size', 'betterdocs' ),
					'priority'    => 39,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function doc_page_article_list_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_margin',
					'label'       => __( 'Docs List Item Margin', 'betterdocs' ),
					'priority'    => 40,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_article_list_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 40,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 40,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 40,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 40,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_article_list_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding',
					'label'       => __( 'Docs List Item Padding', 'betterdocs' ),
					'priority'    => 44,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_article_list_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 44,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 44,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 44,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_article_list_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_page_article_list_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_article_list_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_article_list_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 44,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_article_list_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_article_subcategory_color() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_article_subcategory_color',
				[
					'default'           => $this->defaults['betterdocs_doc_page_article_subcategory_color'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
				]
			);

			$this->customizer->add_control(
				new AlphaColorControl(
					$this->customizer,
					'betterdocs_doc_page_article_subcategory_color',
					[
						'label'    => __( 'Docs Subcategory Color', 'betterdocs' ),
						'section'  => 'betterdocs_doc_page_settings',
						'settings' => 'betterdocs_doc_page_article_subcategory_color',
						'priority' => 44
					]
				)
			);
		}
	}

	public function doc_page_article_subcategory_hover_color() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_article_subcategory_hover_color',
				[
					'default'           => $this->defaults['betterdocs_doc_page_article_subcategory_hover_color'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
				]
			);

			$this->customizer->add_control(
				new AlphaColorControl(
					$this->customizer,
					'betterdocs_doc_page_article_subcategory_hover_color',
					[
						'label'    => __( 'Docs Subcategory Hover Color', 'betterdocs' ),
						'section'  => 'betterdocs_doc_page_settings',
						'settings' => 'betterdocs_doc_page_article_subcategory_hover_color',
						'priority' => 44
					]
				)
			);
		}
	}

	public function doc_page_article_subcategory_font_size() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_article_subcategory_font_size',
				[
					'default'           => $this->defaults['betterdocs_doc_page_article_subcategory_font_size'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]

				]
			);

			$this->customizer->add_control(
				new RangeValueControl(
					$this->customizer,
					'betterdocs_doc_page_article_subcategory_font_size',
					[
						'type'        => 'betterdocs-range-value',
						'section'     => 'betterdocs_doc_page_settings',
						'settings'    => 'betterdocs_doc_page_article_subcategory_font_size',
						'label'       => __( 'Docs Subcategory Font Size', 'betterdocs' ),
						'priority'    => 44,
						'input_attrs' => [
							'class'  => '',
							'min'    => 0,
							'max'    => 50,
							'step'   => 1,
							'suffix' => 'px' //optional suffix
						]
					]
				)
			);
		}
	}

	public function doc_page_subcategory_icon_color() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_subcategory_icon_color',
				[
					'default'           => $this->defaults['betterdocs_doc_page_subcategory_icon_color'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
				]
			);

			$this->customizer->add_control(
				new AlphaColorControl(
					$this->customizer,
					'betterdocs_doc_page_subcategory_icon_color',
					[
						'label'    => __( 'Subcategory Icon Color', 'betterdocs' ),
						'section'  => 'betterdocs_doc_page_settings',
						'settings' => 'betterdocs_doc_page_subcategory_icon_color',
						'priority' => 44
					]
				)
			);
		}
	}

	public function doc_page_subcategory_icon_font_size() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_subcategory_icon_font_size',
				[
					'default'           => $this->defaults['betterdocs_doc_page_subcategory_icon_font_size'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]

				]
			);

			$this->customizer->add_control(
				new RangeValueControl(
					$this->customizer,
					'betterdocs_doc_page_subcategory_icon_font_size',
					[
						'type'        => 'betterdocs-range-value',
						'section'     => 'betterdocs_doc_page_settings',
						'settings'    => 'betterdocs_doc_page_subcategory_icon_font_size',
						'label'       => __( 'Subcategory Icon Font Size', 'betterdocs' ),
						'priority'    => 44,
						'input_attrs' => [
							'class'  => '',
							'min'    => 0,
							'max'    => 50,
							'step'   => 1,
							'suffix' => 'px' //optional suffix
						]
					]
				)
			);
		}
	}

	public function doc_page_subcategory_article_list_color() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_subcategory_article_list_color',
				[
					'default'           => $this->defaults['betterdocs_doc_page_subcategory_article_list_color'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
				]
			);

			$this->customizer->add_control(
				new AlphaColorControl(
					$this->customizer,
					'betterdocs_doc_page_subcategory_article_list_color',
					[
						'label'    => __( 'Subcategory Docs List Color', 'betterdocs' ),
						'section'  => 'betterdocs_doc_page_settings',
						'settings' => 'betterdocs_doc_page_subcategory_article_list_color',
						'priority' => 44
					]
				)
			);
		}
	}

	public function doc_page_subcategory_article_list_hover_color() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_subcategory_article_list_hover_color',
				[
					'default'           => $this->defaults['betterdocs_doc_page_subcategory_article_list_hover_color'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
				]
			);

			$this->customizer->add_control(
				new AlphaColorControl(
					$this->customizer,
					'betterdocs_doc_page_subcategory_article_list_hover_color',
					[
						'label'    => __( 'Subcategory List Hover Color', 'betterdocs' ),
						'section'  => 'betterdocs_doc_page_settings',
						'settings' => 'betterdocs_doc_page_subcategory_article_list_hover_color',
						'priority' => 44
					]
				)
			);
		}
	}

	public function doc_page_subcategory_article_list_icon_color() {
		if ( $this->nested_subcategory ) {
			$this->customizer->add_setting(
				'betterdocs_doc_page_subcategory_article_list_icon_color',
				[
					'default'           => $this->defaults['betterdocs_doc_page_subcategory_article_list_icon_color'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
				]
			);

			$this->customizer->add_control(
				new AlphaColorControl(
					$this->customizer,
					'betterdocs_doc_page_subcategory_article_list_icon_color',
					[
						'label'    => __( 'Subcategory List Icon Color', 'betterdocs' ),
						'section'  => 'betterdocs_doc_page_settings',
						'settings' => 'betterdocs_doc_page_subcategory_article_list_icon_color',
						'priority' => 44
					]
				)
			);
		}
	}

	public function doc_page_explore_btn() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn',
				[
					'label'    => __( 'Explore More Button', 'betterdocs' ),
					'settings' => 'betterdocs_doc_page_explore_btn',
					'section'  => 'betterdocs_doc_page_settings',
					'priority' => 45
				]
			)
		);
	}

	public function doc_page_explore_btn_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_bg_color',
				[
					'label'    => __( 'Button Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_explore_btn_bg_color',
					'priority' => 46
				]
			)
		);
	}

	public function doc_page_explore_btn_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_color',
				[
					'label'    => __( 'Button Text Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_explore_btn_color',
					'priority' => 47
				]
			)
		);
	}

	public function doc_page_explore_btn_border_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_border_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_border_color',
				[
					'label'    => __( 'Button Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_explore_btn_border_color',
					'priority' => 48
				]
			)
		);
	}

	public function doc_page_explore_btn_hover_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_hover_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_hover_bg_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_hover_bg_color',
				[
					'label'    => __( 'Button Hover Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_explore_btn_hover_bg_color',
					'priority' => 49
				]
			)
		);
	}

	public function doc_page_explore_btn_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_hover_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_hover_color',
				[
					'label'    => __( 'Button Hover Text Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_explore_btn_hover_color',
					'priority' => 50
				]
			)
		);
	}

	public function doc_page_explore_btn_hover_border_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_hover_border_color',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_hover_border_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_hover_border_color',
				[
					'label'    => __( 'Button Hover Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_doc_page_explore_btn_hover_border_color',
					'priority' => 51
				]
			)
		);
	}

	public function doc_page_explore_btn_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_font_size',
					'label'       => __( 'Button Font Size', 'betterdocs' ),
					'priority'    => 52,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function doc_page_explore_btn_border_width() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_border_width',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_border_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_border_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_border_width',
					'label'       => __( 'Button Border Width', 'betterdocs' ),
					'priority'    => 52,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function doc_page_explore_btn_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_padding',
					'label'       => __( 'Button Padding', 'betterdocs' ),
					'priority'    => 53,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_explore_btn_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 53,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 53,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 53,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 53,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_explore_btn_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_margin',
					'label'       => __( 'Button Margin', 'betterdocs' ),
					'priority'    => 57,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_explore_btn_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 57,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 57,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 57,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 57,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_page_explore_btn_borderr() {
		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_borderr',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_borderr'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_borderr',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_borderr',
					'label'       => __( 'Button Border Radius', 'betterdocs' ),
					'priority'    => 58,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_page_explore_btn_borderr',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_borderr_topleft',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_borderr_topleft'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_borderr_topleft',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_borderr_topleft',
					'label'       => __( 'Top Left', 'betterdocs' ),
					'priority'    => 58.1,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_borderr_topright',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_borderr_topright'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_borderr_topright',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_borderr_topright',
					'label'       => __( 'Top Right', 'betterdocs' ),
					'priority'    => 58.2,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_borderr_bottomright',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_borderr_bottomright'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_borderr_bottomright',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_borderr_bottomright',
					'label'       => __( 'Bottom Right', 'betterdocs' ),
					'priority'    => 58.3,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_page_explore_btn_borderr_bottomleft',
			[
				'default'           => $this->defaults['betterdocs_doc_page_explore_btn_borderr_bottomleft'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_page_explore_btn_borderr_bottomleft',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_doc_page_explore_btn_borderr_bottomleft',
					'label'       => __( 'Bottom Left', 'betterdocs' ),
					'priority'    => 58.4,
					'input_attrs' => [
						'class' => 'betterdocs_doc_page_explore_btn_borderr betterdocs-dimension'
					]
				]
			)
		);
	}

	// Layout-8 specific controllers
	public function column_number_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_column_number_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_column_number_layout_8'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_docs_page_column_number_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_docs_page_column_number_layout_8',
					'label'       => __( 'Column Number', 'betterdocs' ),
					'priority'    => 16,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 1,
						'max'    => 8,
						'step'   => 1
					]
				]
			)
		);
	}

	public function column_gap_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_column_gap_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_column_gap_layout_8'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_docs_page_column_gap_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_docs_page_column_gap_layout_8',
					'label'       => __( 'Gap Between Columns', 'betterdocs' ),
					'priority'    => 16,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1
					]
				]
			)
		);
	}

	public function icon_size_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_icon_size_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_icon_size_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_docs_page_icon_size_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_docs_page_icon_size_layout_8',
					'label'       => __( 'Icon Size', 'betterdocs' ),
					'priority'    => 17,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 10,
						'max'    => 100,
						'step'   => 1
					]
				]
			)
		);
	}

	public function category_title_font_size_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_category_title_font_size_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_category_title_font_size_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_docs_page_category_title_font_size_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_docs_page_category_title_font_size_layout_8',
					'label'       => __( 'Category Title Font Size', 'betterdocs' ),
					'priority'    => 18,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 10,
						'max'    => 50,
						'step'   => 1
					]
				]
			)
		);
	}

	public function docs_list_item_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_docs_list_item_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_docs_list_item_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_docs_page_docs_list_item_color_layout_8',
				[
					'label'    => __( 'Docs List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_docs_page_docs_list_item_color_layout_8',
					'priority' => 35
				]
			)
		);
	}

	public function docs_list_item_hover_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_docs_list_item_hover_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_docs_list_item_hover_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_docs_page_docs_list_item_hover_color_layout_8',
				[
					'label'    => __( 'Docs List Item Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_doc_page_settings',
					'settings' => 'betterdocs_docs_page_docs_list_item_hover_color_layout_8',
					'priority' => 36
				]
			)
		);
	}

	public function docs_list_item_font_size_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_docs_list_item_font_size_layout_8',
			[
				'default'           => $this->defaults['betterdocs_docs_page_docs_list_item_font_size_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_docs_page_docs_list_item_font_size_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_doc_page_settings',
					'settings'    => 'betterdocs_docs_page_docs_list_item_font_size_layout_8',
					'label'       => __( 'Docs List Item Font Size', 'betterdocs' ),
					'priority'    => 37,
					'input_attrs' => [
						'class'  => 'betterdocs-range-value',
						'min'    => 10,
						'max'    => 30,
						'step'   => 1
					]
				]
			)
		);
	}

	public function docs_list_item_margin_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_docs_page_docs_list_item_margin_layout_8',
			[
				'default'    => $this->defaults['betterdocs_docs_page_docs_list_item_margin_layout_8'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_docs_page_docs_list_item_margin_layout_8',
				[
					'label'        => __( 'Docs List Item Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_doc_page_settings',
					'settings'     => 'betterdocs_docs_page_docs_list_item_margin_layout_8',
					'priority'     => 40,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 24,
						'input2' => 0,
						'input3' => 0,
						'input4' => 0
					]
				]
			)
		);
	}
}
