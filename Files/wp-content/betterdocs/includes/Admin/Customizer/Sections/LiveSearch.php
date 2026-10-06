<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WP_Customize_Control;
use WP_Customize_Image_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\MultiDimensionControl;

class LiveSearch extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority = 500;

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'betterdocs_live_search_settings';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'Live Search', 'betterdocs' );
	}

	public function betterdocs_search_layout_select() {
		$this->customizer->add_setting(
			'betterdocs_search_layout_select',
			[
				'default'           => $this->defaults['betterdocs_search_layout_select'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new RadioImageControl(
				$this->customizer,
				'betterdocs_search_layout_select',
				[
					'type'     => 'betterdocs-radio-image',
					'settings' => 'betterdocs_search_layout_select',
					'section'  => 'betterdocs_live_search_settings',
					'label'    => __( 'Select Search Layout', 'betterdocs' ),
					'choices'  => [
						'layout-2' => [
							'label' => __( 'Modal Layout', 'betterdocs' ),
							'image' => $this->assets->icon( 'customizer/search/layout-2.png', true ),
							'url'   => 'https://betterdocs.co/upgrade'
						],
						'layout-1' => [
							'label' => __( 'Classic Layout', 'betterdocs' ),
							'image' => $this->assets->icon( 'customizer/search/layout-1.png', true )
						]
					],
					'priority' => 499
				]
			)
		);
	}

	public function search_heading_switch() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading_switch',
			[
				'default'    => $this->defaults['betterdocs_live_search_heading_switch'],
				'capability' => 'edit_theme_options'

			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_live_search_heading_switch',
				[
					'label'    => __( 'Search Heading', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_heading_switch',
					'type'     => 'light', // light, ios, flat
					'priority' => 501
				]
			)
		);
	}

	public function search_heading() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading',
			[
				'default'           => $this->defaults['betterdocs_live_search_heading'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_heading',
				[
					'label'    => __( 'Heading', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_heading',
					'type'     => 'text',
					'priority' => 502
				]
			)
		);
	}

	public function heading_tag() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading_tag',
			[
				'default'           => $this->defaults['betterdocs_live_search_heading_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_live_search_heading_tag',
				[
					'label'    => __( 'Heading Tags', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_heading_tag',
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
					'priority' => 502
				]
			)
		);
	}

	public function heading_font_size() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading_font_size',
			[
				'default'           => $this->defaults['betterdocs_live_search_heading_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_live_search_heading_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_heading_font_size',
					'label'       => __( 'Heading Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 503
				]
			)
		);
	}

	public function betterdocs_live_search_heading_font_size_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading_font_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_heading_font_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_live_search_heading_font_size_layout_2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_heading_font_size_layout_2',
					'label'       => __( 'Heading Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 503
				]
			)
		);
	}

	public function heading_font_color() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading_font_color',
			[
				'default'           => $this->defaults['betterdocs_live_search_heading_font_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_live_search_heading_font_color',
				[
					'label'    => __( 'Heading Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_heading_font_color',
					'priority' => 504
				]
			)
		);
	}

	public function betterdocs_live_search_heading_font_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_heading_font_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_heading_font_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_live_search_heading_font_color_layout_2',
				[
					'label'    => __( 'Heading Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_heading_font_color_layout_2',
					'priority' => 504
				]
			)
		);
	}

	public function heading_margin() {
		$this->customizer->add_setting(
			'betterdocs_search_heading_margin',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_heading_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin',
					'label'       => __( 'Heading Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_heading_margin',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_top',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_heading_margin betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_right',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_heading_margin betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_heading_margin betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_left',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_heading_margin betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);
	}

	public function heading_margin_layout_2() {
		$this->customizer->add_setting(
			'heading_margin_layout_2',
			[
				'default'           => $this->defaults['heading_margin_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'heading_margin_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'heading_margin_layout_2',
					'label'       => __( 'Heading Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'heading_margin_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_top_layout_2',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'heading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_right_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_right_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_right_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_right_layout_2',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'heading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_bottom_layout_2',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'heading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_heading_margin_left_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_heading_margin_left_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_heading_margin_left_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_heading_margin_left_layout_2',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'heading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 505
				]
			)
		);
	}


	public function search_subheading() {
		$this->customizer->add_setting(
			'betterdocs_live_search_subheading',
			[
				'default'           => $this->defaults['betterdocs_live_search_subheading'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_subheading',
				[
					'label'    => __( 'Sub Heading', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_subheading',
					'type'     => 'text',
					'priority' => 506
				]
			)
		);
	}

	public function subheading_tag() {
		$this->customizer->add_setting(
			'betterdocs_live_search_subheading_tag',
			[
				'default'           => $this->defaults['betterdocs_live_search_subheading_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_live_search_subheading_tag',
				[
					'label'    => __( 'Sub Heading Tags', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_subheading_tag',
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
					'priority' => 507
				]
			)
		);
	}

	public function subheading_font_size() {
		$this->customizer->add_setting(
			'betterdocs_live_search_subheading_font_size',
			[
				'default'           => $this->defaults['betterdocs_live_search_subheading_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_live_search_subheading_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_subheading_font_size',
					'label'       => __( 'Sub Heading Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 507
				]
			)
		);
	}

	public function betterdocs_live_search_subheading_font_size_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_subheading_font_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_subheading_font_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_live_search_subheading_font_size_layout_2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_subheading_font_size_layout_2',
					'label'       => __( 'Sub Heading Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 507
				]
			)
		);
	}

	public function subheading_font_color() {
		$this->customizer->add_setting(
			'betterdocs_live_search_subheading_font_color',
			[
				'default'           => $this->defaults['betterdocs_live_search_subheading_font_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_live_search_subheading_font_color',
				[
					'label'    => __( 'Sub Heading Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_subheading_font_color',
					'priority' => 508
				]
			)
		);
	}

	public function betterdocs_live_search_subheading_font_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_subheading_font_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_subheading_font_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_live_search_subheading_font_color_layout_2',
				[
					'label'    => __( 'Sub Heading Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_subheading_font_color_layout_2',
					'priority' => 508
				]
			)
		);
	}

	public function subheading_margin() {
		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_subheading_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin',
					'label'       => __( 'Sub Heading Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_subheading_margin',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_top',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_right',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_left',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);
	}

	public function betterdocs_search_subheading_margin_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_layout_2',
					'label'       => __( 'Sub Heading Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_subheading_margin_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_top_layout_2',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_right_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_right_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_right_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_right_layout_2',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_bottom_layout_2',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_subheading_margin_left_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_subheading_margin_left_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_subheading_margin_left_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_subheading_margin_left_layout_2',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_subheading_margin betterdocs-dimension'
					],
					'priority'    => 509
				]
			)
		);
	}


	public function background_color() {
		$this->customizer->add_setting(
			'betterdocs_live_search_background_color',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_live_search_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_background_color',
					'priority' => 510
				]
			)
		);
	}

	public function betterdocs_live_search_background_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_background_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_live_search_background_color_layout_2',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_background_color_layout_2',
					'priority' => 510
				]
			)
		);
	}

	public function background_image() {
		$this->customizer->add_setting(
			'betterdocs_live_search_background_image',
			[
				'default'    => $this->defaults['betterdocs_live_search_background_image'],
				'capability' => 'edit_theme_options',
				'transport'  => 'postMessage'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_live_search_background_image',
				[
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_background_image',
					'label'    => __( 'Background Image', 'betterdocs' ),
					'priority' => 511
				]
			)
		);
	}

	public function background_live_search_image_layout_2() {
		$this->customizer->add_setting(
			'background_live_search_image_layout_2',
			[
				'default'    => $this->defaults['background_live_search_image_layout_2'],
				'capability' => 'edit_theme_options',
				'transport'  => 'postMessage'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'background_live_search_image_layout_2',
				[
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'background_live_search_image_layout_2',
					'label'    => __( 'Background Image', 'betterdocs' ),
					'priority' => 511
				]
			)
		);
	}

	public function background_property() {
		$this->customizer->add_setting(
			'betterdocs_live_search_background_property',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_property'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_live_search_background_property',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_property',
					'label'       => __( 'Background Property', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_live_search_background_property',
						'class' => 'betterdocs-select'
					],
					'priority'    => 512
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_size',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_size',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_size',
					'label'       => __( 'Size', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property betterdocs-select'
					],
					'choices'     => [
						'auto'    => __( 'auto', 'betterdocs' ),
						'length'  => __( 'length', 'betterdocs' ),
						'cover'   => __( 'cover', 'betterdocs' ),
						'contain' => __( 'contain', 'betterdocs' ),
						'initial' => __( 'initial', 'betterdocs' ),
						'inherit' => __( 'inherit', 'betterdocs' )
					],
					'priority'    => 513
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_repeat',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_repeat'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_repeat',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_repeat',
					'label'       => __( 'Repeat', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property betterdocs-select'
					],
					'choices'     => [
						'no-repeat' => __( 'no-repeat', 'betterdocs' ),
						'initial'   => __( 'initial', 'betterdocs' ),
						'inherit'   => __( 'inherit', 'betterdocs' ),
						'repeat'    => __( 'repeat', 'betterdocs' ),
						'repeat-x'  => __( 'repeat-x', 'betterdocs' ),
						'repeat-y'  => __( 'repeat-y', 'betterdocs' )
					],
					'priority'    => 513
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_attachment',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_attachment'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_attachment',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_attachment',
					'label'       => __( 'Attachment', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property betterdocs-select'
					],
					'choices'     => [
						'initial' => __( 'initial', 'betterdocs' ),
						'inherit' => __( 'inherit', 'betterdocs' ),
						'scroll'  => __( 'scroll', 'betterdocs' ),
						'fixed'   => __( 'fixed', 'betterdocs' ),
						'local'   => __( 'local', 'betterdocs' )
					],
					'priority'    => 513
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_position',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_position'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'esc_html'

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_position',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_position',
					'label'       => __( 'Position', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property betterdocs-select'
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
					],
					'priority'    => 513
				]
			)
		);
	}

	public function background_property_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_background_property_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_property_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_live_search_background_property_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_property_layout_2',
					'label'       => __( 'Background Property', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_live_search_background_property_layout_2',
						'class' => 'betterdocs-select'
					],
					'priority'    => 512
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_size_layout_2',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_size_layout_2',
					'label'       => __( 'Size', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property betterdocs-select'
					],
					'choices'     => [
						'auto'    => __( 'auto', 'betterdocs' ),
						'length'  => __( 'length', 'betterdocs' ),
						'cover'   => __( 'cover', 'betterdocs' ),
						'contain' => __( 'contain', 'betterdocs' ),
						'initial' => __( 'initial', 'betterdocs' ),
						'inherit' => __( 'inherit', 'betterdocs' )
					],
					'priority'    => 513
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_repeat_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_repeat_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_repeat_layout_2',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_repeat_layout_2',
					'label'       => __( 'Repeat', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property_layout_2 betterdocs-select'
					],
					'choices'     => [
						'no-repeat' => __( 'no-repeat', 'betterdocs' ),
						'initial'   => __( 'initial', 'betterdocs' ),
						'inherit'   => __( 'inherit', 'betterdocs' ),
						'repeat'    => __( 'repeat', 'betterdocs' ),
						'repeat-x'  => __( 'repeat-x', 'betterdocs' ),
						'repeat-y'  => __( 'repeat-y', 'betterdocs' )
					],
					'priority'    => 513
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_attachment_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_attachment_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_attachment_layout_2',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_attachment',
					'label'       => __( 'Attachment', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property_layout_2 betterdocs-select'
					],
					'choices'     => [
						'initial' => __( 'initial', 'betterdocs' ),
						'inherit' => __( 'inherit', 'betterdocs' ),
						'scroll'  => __( 'scroll', 'betterdocs' ),
						'fixed'   => __( 'fixed', 'betterdocs' ),
						'local'   => __( 'local', 'betterdocs' )
					],
					'priority'    => 513
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_background_position_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_background_position_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'esc_html'

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_live_search_background_position_layout_2',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_background_position_layout_2',
					'label'       => __( 'Position', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_background_property_layout_2 betterdocs-select'
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
					],
					'priority'    => 513
				]
			)
		);
	}

	public function custom_background_switch() {
		$this->customizer->add_setting(
			'betterdocs_live_search_custom_background_switch',
			[
				'default'    => $this->defaults['betterdocs_live_search_custom_background_switch'],
				'capability' => 'edit_theme_options'

			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_live_search_custom_background_switch',
				[
					'label'    => __( 'Custom Background Image Size', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_live_search_custom_background_switch',
					'type'     => 'light', // light, ios, flat
					'priority' => 515
				]
			)
		);
	}

	public function custom_background_width() {
		$this->customizer->add_setting(
			'betterdocs_live_search_custom_background_width',
			[
				'default'           => $this->defaults['betterdocs_live_search_custom_background_width'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_live_search_custom_background_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_custom_background_width',
					'label'       => __( 'Background Image Width', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 200,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					],
					'priority'    => 516
				]
			)
		);
	}

	public function custom_background_height() {
		$this->customizer->add_setting(
			'betterdocs_live_search_custom_background_height',
			[
				'default'           => $this->defaults['betterdocs_live_search_custom_background_height'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_live_search_custom_background_height',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_custom_background_height',
					'label'       => __( 'Background Image Height', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 200,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					],
					'priority'    => 517
				]
			)
		);
	}

	public function search_margin() {
		$this->customizer->add_setting(
			'betterdocs_live_search_margin',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_live_search_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin',
					'label'       => __( 'Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_live_search_margin',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_margin_top',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_margin betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_margin_right',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_margin betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_margin betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_margin_left',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_margin betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);
	}

	public function search_margin_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_margin_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_live_search_margin_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_layout_2',
					'label'       => __( 'Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_live_search_margin_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_margin_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_margin_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_top_layout_2',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_margin_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_margin_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_margin_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_margin_bottom_layout_2',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_margin_layout_2 betterdocs-dimension'
					],
					'priority'    => 518
				]
			)
		);
	}

	public function search_padding() {
		$this->customizer->add_setting(
			'betterdocs_live_search_padding',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_live_search_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding',
					'label'       => __( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_live_search_padding',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_top',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_right',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_left',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);
	}

	public function search_padding_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_live_search_padding_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_live_search_padding_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_layout_2',
					'label'       => __( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_live_search_padding_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_top_layout_2',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_right_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_right_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_right_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_right_layout_2',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_bottom_layout_2',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_live_search_padding_left_layout_2',
			[
				'default'           => $this->defaults['betterdocs_live_search_padding_left_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_live_search_padding_left_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_live_search_padding_left_layout_2',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_live_search_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 519
				]
			)
		);
	}

	public function field_settings() {
		$this->customizer->add_setting(
			'betterdocs_search_field_settings',
			[
				'default'           => $this->defaults['betterdocs_search_field_settings'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_search_field_settings',
				[
					'label'    => __( 'Search Field Settings', 'betterdocs' ),
					'settings' => 'betterdocs_search_field_settings',
					'section'  => 'betterdocs_live_search_settings',
					'priority' => 530
				]
			)
		);
	}

	public function field_settings_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_field_settings_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_settings_layout_2'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_search_field_settings_layout_2',
				[
					'label'    => __( 'Search Field Settings', 'betterdocs' ),
					'settings' => 'betterdocs_search_field_settings_layout_2',
					'section'  => 'betterdocs_live_search_settings',
					'priority' => 530
				]
			)
		);
	}


	public function field_background_color() {
		$this->customizer->add_setting(
			'betterdocs_search_field_background_color',
			[
				'default'           => $this->defaults['betterdocs_search_field_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_field_background_color',
				[
					'label'    => __( 'Search Field Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_field_background_color',
					'priority' => 531
				]
			)
		);
	}

	public function field_background_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_field_background_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_background_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_field_background_color_layout_2',
				[
					'label'    => __( 'Search Field Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_field_background_color_layout_2',
					'priority' => 531
				]
			)
		);
	}

	public function field_font_size() {
		$this->customizer->add_setting(
			'betterdocs_search_field_font_size',
			[
				'default'           => $this->defaults['betterdocs_search_field_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_search_field_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_font_size',
					'label'       => __( 'Search Field Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 532
				]
			)
		);
	}

	public function field_color() {
		$this->customizer->add_setting(
			'betterdocs_search_field_color',
			[
				'default'           => $this->defaults['betterdocs_search_field_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_field_color',
				[
					'label'    => __( 'Search Text Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_field_color',
					'priority' => 533
				]
			)
		);
	}

	public function placeholder_color() {
		$this->customizer->add_setting(
			'betterdocs_search_placeholder_color',
			[
				'default'           => $this->defaults['betterdocs_search_placeholder_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_placeholder_color',
				[
					'label'    => __( 'Search Placeholder Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_placeholder_color',
					'priority' => 533
				]
			)
		);
	}

	public function placeholder_color_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_placeholder_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_placeholder_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_placeholder_color_layout_2',
				[
					'label'    => __( 'Search Placeholder Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_placeholder_color_layout_2',
					'priority' => 533
				]
			)
		);
	}

	public function field_padding() {
		$this->customizer->add_setting(
			'betterdocs_search_field_padding',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_field_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding',
					'label'       => __( 'Search Field Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_field_padding',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_top',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_right',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_left',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);
	}

	public function field_padding_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_field_padding_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_field_padding_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_layout_2',
					'label'       => __( 'Search Field Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_field_padding_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_top_layout_2',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_right_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_right_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_right_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_right_layout_2',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_bottom_layout_2',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_field_padding_left_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_field_padding_left_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_field_padding_left_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_padding_left_layout_2',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_field_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 534
				]
			)
		);
	}

	// public function search_button_settings_layout_2() {
	//     $this->customizer->add_setting( 'betterdocs_search_button_section_layout_2', [
	//         'default'           => $this->defaults['betterdocs_search_button_section_layout_2'],
	//         'sanitize_callback' => 'esc_html'
	//     ] );

	//     $this->customizer->add_control( new SeparatorControl(
	//         $this->customizer, 'betterdocs_search_button_section_layout_2', [
	//             'label'    => esc_html__( 'Search Button Settings', 'betterdocs' ),
	//             'settings' => 'betterdocs_search_button_section_layout_2',
	//             'section'  => 'betterdocs_live_search_settings',
	//             'priority' => 576
	//         ]
	//     ) );
	// }

	public function search_button_font_size_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_new_search_button_font_size_layout_2',
			[
				'default'           => $this->defaults['betterdocs_new_search_button_font_size_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_new_search_button_font_size_layout_2',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_new_search_button_font_size_layout_2',
					'label'       => esc_html__( 'Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 200,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 578
				]
			)
		);
	}

	public function search_button_font_weight_layout_2() {
		//Search Button Font Weight
		$this->customizer->add_setting(
			'betterdocs_new_search_button_font_weight_layout_2',
			[
				'default'           => $this->defaults['betterdocs_new_search_button_font_weight_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_new_search_button_font_weight_layout_2',
				[
					'label'    => esc_html__( 'Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_new_search_button_font_weight_layout_2',
					'type'     => 'select',
					'choices'  => [
						'100' => '100',
						'200' => '200',
						'300' => '300',
						'400' => '400',
						'500' => '500',
						'600' => '600',
						'700' => '700',
						'800' => '800',
						'900' => '900'
					],
					'priority' => 579
				]
			)
		);
	}

	public function search_button_text_transform_layout_2() {
		//Search Button Text Transform
		$this->customizer->add_setting(
			'betterdocs_new_search_button_text_transform_layout_2',
			[
				'default'           => $this->defaults['betterdocs_new_search_button_text_transform_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_new_search_button_text_transform_layout_2',
				[
					'label'    => esc_html__( 'Font Text Transform', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_new_search_button_text_transform_layout_2',
					'type'     => 'select',
					'choices'  => [
						'none'       => 'none',
						'capitalize' => 'capitalize',
						'uppercase'  => 'uppercase',
						'lowercase'  => 'lowercase',
						'initial'    => 'initial',
						'inherit'    => 'inherit'
					],
					'priority' => 580
				]
			)
		);
	}

	public function search_button_text_color_layout_2() {
		// Search Button Text Color
		$this->customizer->add_setting(
			'betterdocs_search_button_text_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_text_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_button_text_color_layout_2',
				[
					'label'    => esc_html__( 'Text Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_button_text_color_layout_2',
					'priority' => 582
				]
			)
		);
	}

	public function search_button_background_color_layout_2() {
		// Search Button Background Color
		$this->customizer->add_setting(
			'betterdocs_search_button_background_color_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_background_color_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_button_background_color_layout_2',
				[
					'label'    => esc_html__( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_button_background_color_layout_2',
					'priority' => 583
				]
			)
		);
	}

	public function search_button_background_color_hover_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_background_color_hover_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_background_color_hover_layout_2'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_button_background_color_hover_layout_2',
				[
					'label'    => esc_html__( 'Background Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_button_background_color_hover_layout_2',
					'priority' => 583
				]
			)
		);
	}

	public function search_button_border_radius_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_borderr_radius_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_borderr_radius_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_button_borderr_radius_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_borderr_radius_layout_2',
					'label'       => esc_html__( 'Border Radius', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_button_borderr_radius_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 584
				]
			)
		);
	}

	public function border_radius_top_left_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_borderr_left_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_borderr_left_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_borderr_left_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_borderr_left_top_layout_2',
					'label'       => esc_html__( 'Left Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_borderr_radius_layout_2 betterdocs-dimension'
					],
					'priority'    => 584
				]
			)
		);
	}

	public function border_radius_top_right_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_borderr_right_top_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_borderr_right_top_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_borderr_right_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_borderr_right_top_layout_2',
					'label'       => esc_html__( 'Right Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_borderr_radius_layout_2 betterdocs-dimension'
					],
					'priority'    => 584
				]
			)
		);
	}

	public function border_radius_bottom_left_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_borderr_left_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_borderr_left_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_borderr_left_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_borderr_left_bottom_layout_2',
					'label'       => esc_html__( 'Left Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_borderr_radius_layout_2 betterdocs-dimension'
					],
					'priority'    => 584
				]
			)
		);
	}

	public function border_radius_bottom_right_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_borderr_right_bottom_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_borderr_right_bottom_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_borderr_right_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_borderr_right_bottom_layout_2',
					'label'       => esc_html__( 'Right Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_borderr_radius_layout_2 betterdocs-dimension'
					],
					'priority'    => 584
				]
			)
		);
	}

	public function button_padding_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_padding_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_padding_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_button_padding_layout_2',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_padding_layout_2',
					'label'       => esc_html__( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_button_padding_layout_2',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 589
				]
			)
		);
	}

	public function button_padding_top_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_padding_top_layout_2',
			apply_filters(
				'betterdocs_search_button_padding_top_layout_2',
				[
					'default'           => $this->defaults['betterdocs_search_button_padding_top_layout_2'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_padding_top_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_padding_top_layout_2',
					'label'       => esc_html__( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_padding betterdocs-dimension'
					],
					'priority'    => 589
				]
			)
		);
	}

	public function button_padding_right_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_padding_right_layout_2',
			apply_filters(
				'betterdocs_search_button_padding_right_layout_2',
				[
					'default'           => $this->defaults['betterdocs_search_button_padding_right_layout_2'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_padding_right_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_padding_right_layout_2',
					'label'       => esc_html__( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 589
				]
			)
		);
	}

	public function button_padding_bottom_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_padding_bottom_layout_2',
			apply_filters(
				'betterdocs_search_button_padding_bottom_layout_2',
				[
					'default'           => $this->defaults['betterdocs_search_button_padding_bottom_layout_2'],
					'capability'        => 'edit_theme_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_padding_bottom_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_padding_bottom_layout_2',
					'label'       => esc_html__( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_padding betterdocs-dimension'
					],
					'priority'    => 589
				]
			)
		);
	}

	public function button_padding_left_layout_2() {
		$this->customizer->add_setting(
			'betterdocs_search_button_padding_left_layout_2',
			[
				'default'           => $this->defaults['betterdocs_search_button_padding_left_layout_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_button_padding_left_layout_2',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_button_padding_left_layout_2',
					'label'       => esc_html__( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_button_padding_layout_2 betterdocs-dimension'
					],
					'priority'    => 589
				]
			)
		);
	}


	public function field_border_radius() {
		$this->customizer->add_setting(
			'betterdocs_search_field_border_radius',
			[
				'default'           => $this->defaults['betterdocs_search_field_border_radius'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_search_field_border_radius',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_field_border_radius',
					'label'       => __( 'Search Field Border Radius', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 539
				]
			)
		);
	}

	public function icon_size() {
		$this->customizer->add_setting(
			'betterdocs_search_icon_size',
			[
				'default'           => $this->defaults['betterdocs_search_icon_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_search_icon_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_icon_size',
					'label'       => __( 'Search Icon Size', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 540
				]
			)
		);
	}

	public function icon_color() {
		$this->customizer->add_setting(
			'betterdocs_search_icon_color',
			[
				'default'           => $this->defaults['betterdocs_search_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_icon_color',
				[
					'label'    => __( 'Search Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_icon_color',
					'priority' => 541
				]
			)
		);
	}

	public function icon_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_search_icon_hover_color',
			[
				'default'           => $this->defaults['betterdocs_search_icon_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_icon_hover_color',
				[
					'label'    => __( 'Search Icon Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_icon_hover_color',
					'priority' => 542
				]
			)
		);
	}

	public function close_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_search_close_icon_color',
			[
				'default'           => $this->defaults['betterdocs_search_close_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_close_icon_color',
				[
					'label'    => __( 'Close Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_close_icon_color',
					'priority' => 543
				]
			)
		);
	}

	public function close_icon_border_color() {
		$this->customizer->add_setting(
			'betterdocs_search_close_icon_border_color',
			[
				'default'           => $this->defaults['betterdocs_search_close_icon_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_close_icon_border_color',
				[
					'label'    => __( 'Close Icon Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_close_icon_border_color',
					'priority' => 544
				]
			)
		);
	}

	public function result_settings() {
		$this->customizer->add_setting(
			'betterdocs_search_result_settings',
			[
				'default'           => $this->defaults['betterdocs_search_result_settings'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_search_result_settings',
				[
					'label'    => __( 'Search Result Settings', 'betterdocs' ),
					'settings' => 'betterdocs_search_result_settings',
					'section'  => 'betterdocs_live_search_settings',
					'priority' => 545
				]
			)
		);
	}

	public function result_width() {
		$this->customizer->add_setting(
			'betterdocs_search_result_width',
			[
				'default'           => $this->defaults['betterdocs_search_result_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_search_result_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_width',
					'label'       => __( 'Search Result Box Width', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					],
					'priority'    => 546
				]
			)
		);
	}

	public function result_max_width() {
		$this->customizer->add_setting(
			'betterdocs_search_result_max_width',
			[
				'default'           => $this->defaults['betterdocs_search_result_max_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_search_result_max_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_max_width',
					'label'       => __( 'Search Result Box Maximum Width', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 1000,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 547
				]
			)
		);
	}

	public function result_background_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_background_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_background_color',
				[
					'label'    => __( 'Search Result Box Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_background_color',
					'priority' => 548
				]
			)
		);
	}

	public function result_border_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_border_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_border_color',
				[
					'label'    => __( 'Search Result Box Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_border_color',
					'priority' => 549
				]
			)
		);
	}

	public function result_item_font_size() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_font_size',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_search_result_item_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_item_font_size',
					'label'       => __( 'Search Result Item Font Size', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 550
				]
			)
		);
	}

	public function result_item_font_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_font_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_font_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_item_font_color',
				[
					'label'    => __( 'Search Result Item Font Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_item_font_color',
					'priority' => 551
				]
			)
		);
	}

	public function result_item_cat_font_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_cat_font_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_cat_font_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_item_cat_font_color',
				[
					'label'    => __( 'Search Result Item Category Font Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_item_cat_font_color',
					'priority' => 552
				]
			)
		);
	}

	public function result_item_padding() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_padding',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_search_result_item_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_item_padding',
					'label'       => __( 'Search Result Item Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_search_result_item_padding',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 553
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_result_item_padding_top',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_result_item_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_item_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_result_item_padding betterdocs-dimension'
					],
					'priority'    => 553
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_result_item_padding_right',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_result_item_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_item_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_result_item_padding betterdocs-dimension'
					],
					'priority'    => 553
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_result_item_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_result_item_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_item_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_result_item_padding betterdocs-dimension'
					],
					'priority'    => 553
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_search_result_item_padding_left',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_search_result_item_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'betterdocs_search_result_item_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_search_result_item_padding betterdocs-dimension'
					],
					'priority'    => 553
				]
			)
		);
	}

	public function result_item_border_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_border_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_item_border_color',
				[
					'label'    => __( 'Search Result Item Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_item_border_color',
					'priority' => 558
				]
			)
		);
	}

	public function result_item_hover_font_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_hover_font_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_hover_font_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_item_hover_font_color',
				[
					'label'    => __( 'Search Result Item Font Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_item_hover_font_color',
					'priority' => 559
				]
			)
		);
	}

	public function result_item_hover_background_color() {
		$this->customizer->add_setting(
			'betterdocs_search_result_item_hover_background_color',
			[
				'default'           => $this->defaults['betterdocs_search_result_item_hover_background_color'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_search_result_item_hover_background_color',
				[
					'label'    => __( 'Item Background Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'betterdocs_search_result_item_hover_background_color',
					'priority' => 560
				]
			)
		);
	}

	public function modal_wrapper_section() {
		$this->customizer->add_setting(
			'modal_wrapper_section',
			[
				'default'           => $this->defaults['modal_wrapper_section'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'modal_wrapper_section',
				[
					'label'    => __( 'Modal Wrapper', 'betterdocs' ),
					'priority' => 617,
					'settings' => 'modal_wrapper_section',
					'section'  => 'betterdocs_live_search_settings'
				]
			)
		);
	}

	public function modal_wrapper_background_color() {
		$this->customizer->add_setting(
			'modal_wrapper_background_color',
			[
				'default'           => $this->defaults['modal_wrapper_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'modal_wrapper_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 618,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'modal_wrapper_background_color',
				]
			)
		);
	}

	public function modal_wrapper_padding() {
		$this->customizer->add_setting(
			'modal_wrapper_padding',
			[
				'default'    => $this->defaults['modal_wrapper_padding'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'modal_wrapper_padding',
				[
					'label'        => __( 'Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'modal_wrapper_padding',
					'priority'     => 619,
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

	public function modal_wrapper_margin() {
		$this->customizer->add_setting(
			'modal_wrapper_margin',
			[
				'default'    => $this->defaults['modal_wrapper_margin'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'modal_wrapper_margin',
				[
					'label'        => __( 'Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'modal_wrapper_margin',
					'priority'     => 620,
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

	public function search_field_wrapper() {
		$this->customizer->add_setting(
			'search_field_wrapper',
			[
				'default'           => $this->defaults['search_field_wrapper'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'search_field_wrapper',
				[
					'label'    => __( 'Modal Search Field', 'betterdocs' ),
					'priority' => 621,
					'settings' => 'search_field_wrapper',
					'section'  => 'betterdocs_live_search_settings'
				]
			)
		);
	}


	public function search_field_modal_background_color() {
		$this->customizer->add_setting(
			'search_field_modal_background_color',
			[
				'default'           => $this->defaults['search_field_modal_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_field_modal_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 622,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_field_modal_background_color',
				]
			)
		);
	}

	public function search_field_modal_padding() {
		$this->customizer->add_setting(
			'search_field_modal_padding',
			[
				'default'    => $this->defaults['search_field_modal_padding'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_field_modal_padding',
				[
					'label'        => __( 'Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_field_modal_padding',
					'priority'     => 623,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 5,
						'input2' => 5,
						'input3' => 5,
						'input4' => 5
					]
				]
			)
		);
	}

	public function search_field_modal_margin() {
		$this->customizer->add_setting(
			'search_field_modal_margin',
			[
				'default'    => $this->defaults['search_field_modal_margin'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_field_modal_margin',
				[
					'label'        => __( 'Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_field_modal_margin',
					'priority'     => 624,
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


	public function search_field_modal_text_color() {
		$this->customizer->add_setting(
			'search_field_modal_text_color',
			[
				'default'           => $this->defaults['search_field_modal_text_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_field_modal_text_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 625,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_field_modal_text_color',
				]
			)
		);
	}

	public function search_field_modal_text_font_size() {
		$this->customizer->add_setting(
			'search_field_modal_text_font_size',
			[
				'default'           => $this->defaults['search_field_modal_text_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_field_modal_text_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_field_modal_text_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 626,
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

	public function search_field_modal_maginifier_icon_size() {
		$this->customizer->add_setting(
			'search_field_modal_maginifier_icon_size',
			[
				'default'           => $this->defaults['search_field_modal_maginifier_icon_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_field_modal_maginifier_icon_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_field_modal_maginifier_icon_size',
					'label'       => __( 'Maginifier Icon Size', 'betterdocs' ),
					'priority'    => 627,
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


	public function search_field_categories_wrapper() {
		$this->customizer->add_setting(
			'search_field_categories_wrapper',
			[
				'default'           => $this->defaults['search_field_categories_wrapper'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'search_field_categories_wrapper',
				[
					'label'    => __( 'Modal Search Categories', 'betterdocs' ),
					'priority' => 628,
					'settings' => 'search_field_categories_wrapper',
					'section'  => 'betterdocs_live_search_settings'
				]
			)
		);
	}

	public function search_field_categories_text_color() {
		$this->customizer->add_setting(
			'search_field_categories_text_color',
			[
				'default'           => $this->defaults['search_field_categories_text_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_field_categories_text_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 629,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_field_categories_text_color',
				]
			)
		);
	}

	public function search_field_categories_background_color() {
		$this->customizer->add_setting(
			'search_field_categories_background_color',
			[
				'default'           => $this->defaults['search_field_categories_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_field_categories_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 630,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_field_categories_background_color',
				]
			)
		);
	}


	public function search_field_categories_font_size() {
		$this->customizer->add_setting(
			'search_field_categories_font_size',
			[
				'default'           => $this->defaults['search_field_categories_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_field_categories_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_field_categories_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
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

	public function search_modal_content_tabs() {
		$this->customizer->add_setting(
			'search_modal_content_tabs',
			[
				'default'           => $this->defaults['search_modal_content_tabs'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'search_modal_content_tabs',
				[
					'label'    => __( 'Modal Content Tabs', 'betterdocs' ),
					'priority' => 632,
					'settings' => 'search_modal_content_tabs',
					'section'  => 'betterdocs_live_search_settings'
				]
			)
		);
	}


	public function search_modal_content_tabs_text_color() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_text_color',
			[
				'default'           => $this->defaults['search_modal_content_tabs_text_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_text_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 633,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_text_color',
				]
			)
		);
	}

	public function search_modal_content_tabs_background_color() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_background_color',
			[
				'default'           => $this->defaults['search_modal_content_tabs_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 634,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_background_color',
				]
			)
		);
	}

	public function search_modal_content_tabs_text_font_size() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_text_font_size',
			[
				'default'           => $this->defaults['search_modal_content_tabs_text_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_modal_content_tabs_text_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_modal_content_tabs_text_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 635,
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

	public function search_modal_content_tabs_margin() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_margin',
			[
				'default'    => $this->defaults['search_modal_content_tabs_margin'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_modal_content_tabs_margin',
				[
					'label'        => __( 'Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_modal_content_tabs_margin',
					'priority'     => 636,
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

	public function search_modal_content_tabs_padding() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_padding',
			[
				'default'    => $this->defaults['search_modal_content_tabs_padding'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_modal_content_tabs_padding',
				[
					'label'        => __( 'Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_modal_content_tabs_padding',
					'priority'     => 637,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 0,
						'input2' => 28,
						'input3' => 0,
						'input4' => 28
					]
				]
			)
		);
	}

	public function search_modal_content_tabs_border() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_border',
			[
				'default'    => $this->defaults['search_modal_content_tabs_border'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_modal_content_tabs_border',
				[
					'label'        => __( 'Border', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_modal_content_tabs_border',
					'priority'     => 638,
					'input_fields' => [
						'input1' => __( 'border top', 'betterdocs' ),
						'input2' => __( 'border right', 'betterdocs' ),
						'input3' => __( 'border bottom', 'betterdocs' ),
						'input4' => __( 'border left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 0,
						'input2' => 0,
						'input3' => 1,
						'input4' => 0
					]
				]
			)
		);
	}

	public function search_modal_content_tabs_border_color() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_border_color',
			[
				'default'           => $this->defaults['search_modal_content_tabs_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_border_color',
				[
					'label'    => __( 'Border Color', 'betterdocs' ),
					'priority' => 639,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_border_color',
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list',
				[
					'label'    => __( 'Modal Content Docs List', 'betterdocs' ),
					'priority' => 640,
					'settings' => 'search_modal_content_tabs_docs_list',
					'section'  => 'betterdocs_live_search_settings'
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list_font_size() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_font_size',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_modal_content_tabs_docs_list_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 641,
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

	public function search_modal_content_tabs_docs_list_color() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_color',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 642,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_docs_list_color',
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list_background_color() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_background_color',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 643,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_docs_list_background_color',
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list_background_color_hover() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_background_color_hover',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_background_color_hover'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_background_color_hover',
				[
					'label'    => __( 'Hover Background Color', 'betterdocs' ),
					'priority' => 644,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_docs_list_background_color_hover',
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list_padding() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_padding',
			[
				'default'    => $this->defaults['search_modal_content_tabs_docs_list_padding'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_padding',
				[
					'label'        => __( 'Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_modal_content_tabs_docs_list_padding',
					'priority'     => 644,
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 16,
						'input2' => 24,
						'input3' => 16,
						'input4' => 24
					]
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list_margin() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_margin',
			[
				'default'    => $this->defaults['search_modal_content_tabs_docs_list_margin'],
				'transport'  => 'postMessage',
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_margin',
				[
					'label'        => __( 'Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_live_search_settings',
					'settings'     => 'search_modal_content_tabs_docs_list_margin',
					'priority'     => 645,
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

	public function search_modal_content_tabs_docs_list_icon_size() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_icon_size',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_icon_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_icon_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_modal_content_tabs_docs_list_icon_size',
					'label'       => __( 'Icon Size', 'betterdocs' ),
					'priority'    => 646,
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

	public function search_modal_content_tabs_docs_list_category_font_size() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_category_font_size',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_category_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_category_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_modal_content_tabs_docs_list_category_font_size',
					'label'       => __( 'List Category Font Size', 'betterdocs' ),
					'priority'    => 647,
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

	public function search_modal_content_tabs_docs_list_category_color() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_category_color',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_category_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_category_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 648,
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_content_tabs_docs_list_category_color',
				]
			)
		);
	}

	public function search_modal_content_tabs_docs_list_category_icon_size() {
		$this->customizer->add_setting(
			'search_modal_content_tabs_docs_list_category_icon_size',
			[
				'default'           => $this->defaults['search_modal_content_tabs_docs_list_category_icon_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'search_modal_content_tabs_docs_list_category_icon_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_live_search_settings',
					'settings'    => 'search_modal_content_tabs_docs_list_category_icon_size',
					'label'       => __( 'List Category Font Size', 'betterdocs' ),
					'priority'    => 649,
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


	public function search_modal_query_section() {
		$this->customizer->add_setting(
			'search_modal_query_section',
			[
				'default'           => $this->defaults['search_modal_query_section'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'search_modal_query_section',
				[
					'label'    => __( 'Search Modal Query', 'betterdocs' ),
					'priority' => 700,
					'settings' => 'search_modal_query_section',
					'section'  => 'betterdocs_live_search_settings'
				]
			)
		);
	}

	public function search_modal_query_type() {
		$this->customizer->add_setting(
			'search_modal_query_type',
			[
				'default'    => $this->defaults['search_modal_query_type'],
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'search_modal_query_type',
				[
					'label'    => __( 'Select Docs Type', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_query_type',
					'type'     => 'select',
					'choices'  => [
						'popular_docs'          => __( 'Popular Docs', 'betterdocs' ),
						'specific_doc_ids'      => __( "Doc Id's", 'betterdocs' ),
						'specific_doc_term_ids' => __( "Doc Category Id's", 'betterdocs' )
					],
					'priority' => 701
				]
			)
		);
	}

	public function search_modal_query_initial_number_of_docs() {
		$this->customizer->add_setting(
			'search_modal_query_initial_number_of_docs',
			[
				'default'           => $this->defaults['search_modal_query_initial_number_of_docs'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'search_modal_query_initial_number_of_docs',
				[
					'label'    => __( 'Number of Docs', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_query_initial_number_of_docs',
					'type'     => 'number',
					'priority' => 701
				]
			)
		);
	}

	public function search_modal_query_doc_ids() {
		$this->customizer->add_setting(
			'search_modal_query_doc_ids',
			[
				'default'           => $this->defaults['search_modal_query_doc_ids'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'search_modal_query_doc_ids',
				[
					'label'    => __( "Doc Id's", 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_query_doc_ids',
					'type'     => 'text',
					'priority' => 702
				]
			)
		);
	}


	public function search_modal_query_doc_term_ids() {
		$this->customizer->add_setting(
			'search_modal_query_doc_term_ids',
			[
				'default'           => $this->defaults['search_modal_query_doc_term_ids'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'search_modal_query_doc_term_ids',
				[
					'label'    => __( "Doc Category Id's", 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_query_doc_term_ids',
					'type'     => 'text',
					'priority' => 702
				]
			)
		);
	}

	public function search_modal_faq_query_type() {
		$this->customizer->add_setting(
			'search_modal_faq_query_type',
			[
				'default'    => $this->defaults['search_modal_faq_query_type'],
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'search_modal_faq_query_type',
				[
					'label'    => __( 'Select FAQ Type', 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_faq_query_type',
					'type'     => 'select',
					'choices'  => [
						'default'               => __( 'Default', 'betterdocs' ),
						'specific_faq_term_ids' => __( "FAQ Group Id's", 'betterdocs' ),
					],
					'priority' => 703
				]
			)
		);
	}

	public function search_modal_query_initial_number_of_faqs() {
		$this->customizer->add_setting(
			'search_modal_query_initial_number_of_faqs',
			[
				'default'           => $this->defaults['search_modal_query_initial_number_of_faqs'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'search_modal_query_initial_number_of_faqs',
				[
					'label'    => __( "Number of FAQ's", 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_query_initial_number_of_faqs',
					'type'     => 'number',
					'priority' => 703
				]
			)
		);
	}

	public function search_modal_query_faq_term_ids() {
		$this->customizer->add_setting(
			'search_modal_query_faq_term_ids',
			[
				'default'           => $this->defaults['search_modal_query_faq_term_ids'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'search_modal_query_faq_term_ids',
				[
					'label'    => __( "FAQ Group Id's", 'betterdocs' ),
					'section'  => 'betterdocs_live_search_settings',
					'settings' => 'search_modal_query_faq_term_ids',
					'type'     => 'text',
					'priority' => 703
				]
			)
		);
	}


	// public function search_modal_query_select_specific_doc_category() {
	//     $this->customizer->add_setting( 'search_modal_query_select_specific_doc_category', [
	//         'default'    => $this->defaults['search_modal_query_select_specific_doc_category'],
	//         'capability' => 'edit_theme_options'
	//     ] );

	//     $this->customizer->add_control(
	//         new WP_Customize_Control(
	//             $this->customizer,
	//             'search_modal_query_select_specific_doc_category',
	//             [
	//                 'label'    => __( 'Select Doc Category Initial Posts', 'betterdocs' ),
	//                 'section'  => 'betterdocs_live_search_settings',
	//                 'settings' => 'search_modal_query_select_specific_doc_category',
	//                 'type'     => 'select',
	//                 'choices'  => betterdocs()->query->get_doc_terms( [
	//                     '' => __( 'Select Doc Term', 'betterdocs' )
	//                 ] ),
	//                 'priority' => 702
	//             ]
	//         )
	//     );
	// }

	// public function search_modal_query_select_specific_faq() {
	//     $this->customizer->add_setting( 'search_modal_query_select_specific_faq', [
	//         'default'    => $this->defaults['search_modal_query_select_specific_faq'],
	//         'capability' => 'edit_theme_options'
	//     ] );

	//     $this->customizer->add_control(
	//         new WP_Customize_Control(
	//             $this->customizer,
	//             'search_modal_query_select_specific_faq',
	//             [
	//                 'label'    => __( 'Select FAQ Initial Posts', 'betterdocs' ),
	//                 'section'  => 'betterdocs_live_search_settings',
	//                 'settings' => 'search_modal_query_select_specific_faq',
	//                 'type'     => 'select',
	//                 'choices'  => betterdocs()->query->get_faq_terms( [
	//                     '' => __( 'Select FAQ Term', 'betterdocs' )
	//                 ] ),
	//                 'priority' => 703
	//             ]
	//         )
	//     );
	// }
}
