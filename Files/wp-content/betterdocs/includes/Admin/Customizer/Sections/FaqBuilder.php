<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WP_Customize_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\MultiDimensionControl;

class FaqBuilder extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority = 600;

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'betterdocs_faq_section';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'FAQ', 'betterdocs' );
	}

	public function section_seperator() {
		$this->customizer->add_setting(
			'betterdocs_faq_section_seperator',
			[
				'default'           => $this->defaults['betterdocs_faq_section_seperator'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_faq_section_seperator',
				[
					'label'    => __( 'Docs Page FAQ', 'betterdocs' ),
					'settings' => 'betterdocs_faq_section_seperator',
					'section'  => 'betterdocs_faq_section',
					'priority' => 624
				]
			)
		);
	}

	public function faq_switch() {
		$this->customizer->add_setting(
			'betterdocs_faq_switch',
			[
				'default'    => $this->defaults['betterdocs_faq_switch'],
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_faq_switch',
				[
					'label'    => __( 'Enable FAQ', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_switch',
					'type'     => 'light', // light, ios, flat
					'priority' => 625
				]
			)
		);
	}

	public function select_specific_faq() {
		$this->customizer->add_setting(
			'betterdocs_select_specific_faq',
			[
				'default'    => $this->defaults['betterdocs_select_specific_faq'],
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_select_specific_faq',
				[
					'label'    => __( 'Select FAQ Groups', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_select_specific_faq',
					'type'     => 'select',
					'choices'  => betterdocs()->query->get_faq_terms(
						[
							'all' => __( 'Show All', 'betterdocs' )
						]
					),
					'priority' => 626
				]
			)
		);
	}

	public function select_faq_template() {
		$this->customizer->add_setting(
			'betterdocs_select_faq_template',
			[
				'default'           => $this->defaults['betterdocs_select_faq_template'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new RadioImageControl(
				$this->customizer,
				'betterdocs_select_faq_template',
				[
					'type'     => 'betterdocs-radio-image',
					'settings' => 'betterdocs_select_faq_template',
					'section'  => 'betterdocs_faq_section',
					'label'    => __( 'Select FAQ Layout', 'betterdocs' ),
					'priority' => 627,
					'choices'  => [
						'layout-3' => [
							'label' => __( 'Abstract Layout', 'betterdocs' ),
							'image' => $this->assets->icon( 'customizer/faq/layout-3.png', true )
						],
						'layout-1' => [
							'label' => __( 'Modern Layout', 'betterdocs' ),
							'image' => $this->assets->icon( 'customizer/faq/layout-1.png', true )
						],
						'layout-2' => [
							'label' => __( 'Classic Layout', 'betterdocs' ),
							'image' => $this->assets->icon( 'customizer/faq/layout-2.png', true )
						],
						'layout-4' => [
							'label' => __( 'Retro Layout', 'betterdocs' ),
							'image' => $this->assets->icon( 'customizer/faq/layout-4.png', true )
						],
					]
				]
			)
		);
	}

	public function faq_title_text() {
		$this->customizer->add_setting(
			'betterdocs_faq_title_text',
			[
				'default'           => $this->defaults['betterdocs_faq_title_text'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_faq_title_text',
				[
					'label'    => __( 'Section Title Text', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 628,
					'settings' => 'betterdocs_faq_title_text',
					'type'     => 'text'
				]
			)
		);
	}

	public function faq_section_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_faq_section_title_tag',
			[
				'default'           => $this->defaults['betterdocs_faq_section_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_faq_section_title_tag',
				[
					'label'    => __( 'Section Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_section_title_tag',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6'
					],
					'priority' => 629
				]
			)
		);
	}

	public function faq_title_margin() {
		$this->customizer->add_setting(
			'betterdocs_faq_title_margin',
			[
				'default'    => $this->defaults['betterdocs_faq_title_margin'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_title_margin',
				[
					'label'        => __( 'FAQ Section Title Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_title_margin',
					'priority'     => 629,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 0,
						'input2' => 0,
						'input3' => 0,
						'input4' => 0
					]
				]
			)
		);
	}

	public function faq_title_color() {
		$this->customizer->add_setting(
			'betterdocs_faq_title_color',
			[
				'default'           => $this->defaults['betterdocs_faq_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_title_color',
				[
					'label'    => __( 'Section Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_title_color',
					'priority' => 630
				]
			)
		);
	}

	public function faq_title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_faq_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_faq_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_title_font_size',
					'label'       => __( 'Section Title Font Size', 'betterdocs' ),
					'priority'    => 631,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_group_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_faq_group_title_tag',
			[
				'default'           => $this->defaults['betterdocs_faq_group_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_faq_group_title_tag',
				[
					'label'    => __( 'Group Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_group_title_tag',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6'
					],
					'priority' => 631
				]
			)
		);
	}

	public function faq_category_title_color() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_title_color',
			[
				'default'           => $this->defaults['betterdocs_faq_category_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_title_color',
				[
					'label'    => __( 'Group Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 632,
					'settings' => 'betterdocs_faq_category_title_color'
				]
			)
		);
	}

	public function faq_category_name_font_size() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_font_size',
			[
				'default'           => $this->defaults['betterdocs_faq_category_name_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_category_name_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_category_name_font_size',
					'label'       => __( 'Group Title Font Size', 'betterdocs' ),
					'priority'    => 633,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_category_name_padding() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_padding',
			[
				'default'    => $this->defaults['betterdocs_faq_category_name_padding'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_category_name_padding',
				[
					'label'        => __( 'Group Title Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_category_name_padding',
					'priority'     => 634,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	public function faq_list_color() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_color',
			[
				'default'           => $this->defaults['betterdocs_faq_list_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_color',
				[
					'label'    => __( 'FAQ List Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_color',
					'priority' => 635
				]
			)
		);
	}

	public function faq_list_background_color() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_background_color',
			[
				'default'           => $this->defaults['betterdocs_faq_list_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_background_color',
				[
					'label'    => __( 'FAQ List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_background_color',
					'priority' => 638
				]
			)
		);
	}

	public function faq_list_content_background_color() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_background_color',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_background_color',
				[
					'label'    => __( 'FAQ List Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_background_color',
					'priority' => 639
				]
			)
		);
	}

	public function faq_list_content_color() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_color',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_color',
				[
					'label'    => __( 'FAQ List Content Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_color',
					'priority' => 640
				]
			)
		);
	}

	public function faq_list_content_font_size() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_font_size',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_content_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_content_font_size',
					'label'       => __( 'FAQ List Content Font Size', 'betterdocs' ),
					'priority'    => 641,
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

	public function faq_list_font_size() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_font_size',
			[
				'default'           => $this->defaults['betterdocs_faq_list_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_font_size',
					'label'       => __( 'FAQ List Font Size', 'betterdocs' ),
					'priority'    => 636,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_list_padding() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_padding',
			[
				'default'    => $this->defaults['betterdocs_faq_list_padding'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_list_padding',
				[
					'label'        => __( 'FAQ List Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_list_padding',
					'priority'     => 637,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	/**
	 * ----------------------------------------------------------------------
	 * Product FAQ for WooCommerce controls.
	 *
	 * Styles the FAQ rendered on single WooCommerce product pages
	 * (`.betterdocs-woo-product-faq`). Only the Group Title + FAQ List
	 * controls are offered — the product FAQ has no section title, layout
	 * picker or active-group-title state, so those controls are intentionally
	 * omitted. Registered only when WooCommerce is active.
	 * ----------------------------------------------------------------------
	 */
	public function section_woo_seperator() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_section_woo_seperator',
			[
				'default'           => $this->defaults['betterdocs_faq_section_woo_seperator'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_faq_section_woo_seperator',
				[
					'label'    => __( 'Product FAQ for WooCommerce', 'betterdocs' ),
					'settings' => 'betterdocs_faq_section_woo_seperator',
					'section'  => 'betterdocs_faq_section',
					'priority' => 675
				]
			)
		);
	}

	public function faq_group_title_tag_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_group_title_tag_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_group_title_tag_woo'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_faq_group_title_tag_woo',
				[
					'label'    => __( 'Group Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_group_title_tag_woo',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6'
					],
					'priority' => 680
				]
			)
		);
	}

	public function faq_category_title_color_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_category_title_color_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_category_title_color_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_title_color_woo',
				[
					'label'    => __( 'Group Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 681,
					'settings' => 'betterdocs_faq_category_title_color_woo'
				]
			)
		);
	}

	public function faq_category_name_font_size_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_category_name_font_size_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_category_name_font_size_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_category_name_font_size_woo',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_category_name_font_size_woo',
					'label'       => __( 'Group Title Font Size', 'betterdocs' ),
					'priority'    => 682,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function faq_category_name_padding_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_category_name_padding_woo',
			[
				'default'    => $this->defaults['betterdocs_faq_category_name_padding_woo'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_category_name_padding_woo',
				[
					'label'        => __( 'Group Title Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_category_name_padding_woo',
					'priority'     => 683,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 0,
						'input3' => 20,
						'input4' => 0
					]
				]
			)
		);
	}

	public function faq_list_color_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_color_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_list_color_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_color_woo',
				[
					'label'    => __( 'FAQ List Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_color_woo',
					'priority' => 684
				]
			)
		);
	}

	public function faq_list_background_color_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_background_color_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_list_background_color_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_background_color_woo',
				[
					'label'    => __( 'FAQ List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_background_color_woo',
					'priority' => 687
				]
			)
		);
	}

	public function faq_list_content_background_color_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_content_background_color_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_background_color_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_background_color_woo',
				[
					'label'    => __( 'FAQ List Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_background_color_woo',
					'priority' => 688
				]
			)
		);
	}

	public function faq_list_content_color_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_content_color_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_color_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_color_woo',
				[
					'label'    => __( 'FAQ List Content Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_color_woo',
					'priority' => 689
				]
			)
		);
	}

	public function faq_list_content_font_size_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_content_font_size_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_font_size_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_content_font_size_woo',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_content_font_size_woo',
					'label'       => __( 'FAQ List Content Font Size', 'betterdocs' ),
					'priority'    => 690,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function faq_list_font_size_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_font_size_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_list_font_size_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_font_size_woo',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_font_size_woo',
					'label'       => __( 'FAQ List Font Size', 'betterdocs' ),
					'priority'    => 685,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function faq_list_padding_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_list_padding_woo',
			[
				'default'    => $this->defaults['betterdocs_faq_list_padding_woo'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_list_padding_woo',
				[
					'label'        => __( 'FAQ List Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_list_padding_woo',
					'priority'     => 686,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	public function faq_group_border_width_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_group_border_width_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_group_border_width_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_group_border_width_woo',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_group_border_width_woo',
					'label'       => __( 'FAQ Group Border Width', 'betterdocs' ),
					'priority'    => 676,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 20,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function faq_group_border_color_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_group_border_color_woo',
			[
				'default'           => $this->defaults['betterdocs_faq_group_border_color_woo'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_group_border_color_woo',
				[
					'label'    => __( 'FAQ Group Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_group_border_color_woo',
					'priority' => 677
				]
			)
		);
	}

	public function faq_group_padding_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_group_padding_woo',
			[
				'default'    => $this->defaults['betterdocs_faq_group_padding_woo'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_group_padding_woo',
				[
					'label'        => __( 'FAQ Group Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_group_padding_woo',
					'priority'     => 678,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 0,
						'input2' => 0,
						'input3' => 0,
						'input4' => 0
					]
				]
			)
		);
	}

	public function faq_group_margin_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->customizer->add_setting(
			'betterdocs_faq_group_margin_woo',
			[
				'default'    => $this->defaults['betterdocs_faq_group_margin_woo'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_group_margin_woo',
				[
					'label'        => __( 'FAQ Group Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_group_margin_woo',
					'priority'     => 679,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 0,
						'input2' => 0,
						'input3' => 20,
						'input4' => 0
					]
				]
			)
		);
	}

	public function faq_category_title_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_title_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_category_title_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_title_color_layout_2',
				[
					'label'    => __( 'Group Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 642,
					'settings' => 'betterdocs_faq_category_title_color_layout_2'
				]
			)
		);
	}

	public function faq_category_name_font_size_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_font_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_category_name_font_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_category_name_font_size_layout_2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_category_name_font_size_layout_2',
					'label'       => __( 'Group Title Font Size', 'betterdocs' ),
					'priority'    => 643,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_category_name_padding_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_padding_layout_2',
			[
				'default'    => $this->defaults['betterdocs_faq_category_name_padding_layout_2'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_category_name_padding_layout_2',
				[
					'label'        => __( 'Group Title Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_category_name_padding_layout_2',
					'priority'     => 644,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	public function faq_list_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_list_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_color_layout_2',
				[
					'label'    => __( 'FAQ List Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_color_layout_2',
					'priority' => 645
				]
			)
		);
	}

	public function faq_list_background_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_background_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_list_background_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_background_color_layout_2',
				[
					'label'    => __( 'FAQ List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_background_color_layout_2',
					'priority' => 648
				]
			)
		);
	}

	public function faq_list_content_background_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_background_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_background_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_background_color_layout_2',
				[
					'label'    => __( 'FAQ List Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_background_color_layout_2',
					'priority' => 649
				]
			)
		);
	}

	public function faq_list_content_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_color_layout_2',
				[
					'label'    => __( 'FAQ List Content Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_color_layout_2',
					'priority' => 650
				]
			)
		);
	}

	public function faq_list_content_font_size_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_font_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_font_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_content_font_size_layout_2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_content_font_size_layout_2',
					'label'       => __( 'FAQ List Content Font Size', 'betterdocs' ),
					'priority'    => 651,
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

	public function faq_list_font_size_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_font_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_faq_list_font_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_font_size_layout_2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_font_size_layout_2',
					'label'       => __( 'FAQ List Font Size', 'betterdocs' ),
					'priority'    => 646,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px'
						// optional suffix
					]
				]
			)
		);
	}

	public function faq_list_padding_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_padding_layout_2',
			[
				'default'    => $this->defaults['betterdocs_faq_list_padding_layout_2'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_list_padding_layout_2',
				[
					'label'        => __( 'FAQ List Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_list_padding_layout_2',
					'priority'     => 647,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	public function faq_category_title_color_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_title_color_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_category_title_color_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_title_color_layout_3',
				[
					'label'    => __( 'Group Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 652,
					'settings' => 'betterdocs_faq_category_title_color_layout_3'
				]
			)
		);
	}

	public function faq_category_name_font_size_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_font_size_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_category_name_font_size_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_category_name_font_size_layout_3',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_category_name_font_size_layout_3',
					'label'       => __( 'Group Title Font Size', 'betterdocs' ),
					'priority'    => 653,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_category_name_padding_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_padding_layout_3',
			[
				'default'    => $this->defaults['betterdocs_faq_category_name_padding_layout_3'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_category_name_padding_layout_3',
				[
					'label'        => __( 'Group Title Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_category_name_padding_layout_3',
					'priority'     => 654,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	public function faq_list_color_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_color_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_list_color_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_color_layout_3',
				[
					'label'    => __( 'FAQ List Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_color_layout_3',
					'priority' => 655
				]
			)
		);
	}

	public function faq_list_background_color_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_background_color_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_list_background_color_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_background_color_layout_3',
				[
					'label'    => __( 'FAQ List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_background_color_layout_3',
					'priority' => 658
				]
			)
		);
	}

	public function faq_list_content_background_color_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_background_color_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_background_color_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_background_color_layout_3',
				[
					'label'    => __( 'FAQ List Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_background_color_layout_3',
					'priority' => 659
				]
			)
		);
	}

	public function faq_list_content_color_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_color_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_color_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_color_layout_3',
				[
					'label'    => __( 'FAQ List Content Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_color_layout_3',
					'priority' => 660
				]
			)
		);
	}

	public function faq_list_content_font_size_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_font_size_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_font_size_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_content_font_size_layout_3',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_content_font_size_layout_3',
					'label'       => __( 'FAQ List Content Font Size', 'betterdocs' ),
					'priority'    => 661,
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

	public function faq_list_font_size_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_font_size_layout_3',
			[
				'default'           => $this->defaults['betterdocs_faq_list_font_size_layout_3'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_font_size_layout_3',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_font_size_layout_3',
					'label'       => __( 'FAQ List Font Size', 'betterdocs' ),
					'priority'    => 656,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_list_padding_layout_3() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_padding_layout_3',
			[
				'default'    => $this->defaults['betterdocs_faq_list_padding_layout_3'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_list_padding_layout_3',
				[
					'label'        => __( 'FAQ List Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_list_padding_layout_3',
					'priority'     => 657,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 24,
						'input2' => 14,
						'input3' => 24,
						'input4' => 14
					]
				]
			)
		);
	}

	public function faq_category_title_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_title_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_category_title_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_title_color_layout_4',
				[
					'label'    => __( 'Group Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 662,
					'settings' => 'betterdocs_faq_category_title_color_layout_4'
				]
			)
		);
	}

	public function faq_category_title_background_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_active_title_background_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_category_active_title_background_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_active_title_background_color_layout_4',
				[
					'label'    => __( 'Group Title Active Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 662,
					'settings' => 'betterdocs_faq_category_active_title_background_color_layout_4'
				]
			)
		);
	}

	public function faq_category_title_active_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_title_active_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_category_title_active_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_category_title_active_color_layout_4',
				[
					'label'    => __( 'Group Active Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'priority' => 662,
					'settings' => 'betterdocs_faq_category_title_active_color_layout_4'
				]
			)
		);
	}

	public function faq_category_name_font_size_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_font_size_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_category_name_font_size_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_category_name_font_size_layout_4',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_category_name_font_size_layout_4',
					'label'       => __( 'Group Title Font Size', 'betterdocs' ),
					'priority'    => 663,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_category_name_padding_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_category_name_padding_layout_4',
			[
				'default'    => $this->defaults['betterdocs_faq_category_name_padding_layout_4'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_category_name_padding_layout_4',
				[
					'label'        => __( 'Group Title Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_category_name_padding_layout_3',
					'priority'     => 664,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 20,
						'input2' => 20,
						'input3' => 20,
						'input4' => 20
					]
				]
			)
		);
	}

	public function faq_list_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_list_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_color_layout_4',
				[
					'label'    => __( 'FAQ List Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_color_layout_4',
					'priority' => 665
				]
			)
		);
	}

	public function faq_list_background_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_background_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_list_background_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_background_color_layout_4',
				[
					'label'    => __( 'FAQ List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_background_color_layout_4',
					'priority' => 668
				]
			)
		);
	}

	public function faq_list_content_background_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_background_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_background_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_background_color_layout_4',
				[
					'label'    => __( 'FAQ List Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_background_color_layout_4',
					'priority' => 669
				]
			)
		);
	}

	public function faq_list_content_color_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_color_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_color_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_faq_list_content_color_layout_4',
				[
					'label'    => __( 'FAQ List Content Color', 'betterdocs' ),
					'section'  => 'betterdocs_faq_section',
					'settings' => 'betterdocs_faq_list_content_color_layout_4',
					'priority' => 670
				]
			)
		);
	}

	public function faq_list_content_font_size_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_content_font_size_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_list_content_font_size_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_content_font_size_layout_4',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_content_font_size_layout_4',
					'label'       => __( 'FAQ List Content Font Size', 'betterdocs' ),
					'priority'    => 671,
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

	public function faq_list_font_size_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_font_size_layout_4',
			[
				'default'           => $this->defaults['betterdocs_faq_list_font_size_layout_4'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_faq_list_font_size_layout_4',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_faq_section',
					'settings'    => 'betterdocs_faq_list_font_size_layout_4',
					'label'       => __( 'FAQ List Font Size', 'betterdocs' ),
					'priority'    => 666,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function faq_list_padding_layout_4() {
		$this->customizer->add_setting(
			'betterdocs_faq_list_padding_layout_4',
			[
				'default'    => $this->defaults['betterdocs_faq_list_padding_layout_4'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_faq_list_padding_layout_4',
				[
					'label'        => __( 'FAQ List Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_faq_section',
					'settings'     => 'betterdocs_faq_list_padding_layout_3',
					'priority'     => 667,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 24,
						'input2' => 14,
						'input3' => 24,
						'input4' => 14
					]
				]
			)
		);
	}
}
