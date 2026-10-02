<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WP_Customize_Control;
use WP_Customize_Image_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\MultiDimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;

class Sidebar extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority = 300;

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'betterdocs_sidebar_settings';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'Sidebar', 'betterdocs' );
	}

	public function sidebar_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_bg_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_bg_color'
				]
			)
		);
	}

	public function sidebar_padding() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_padding',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding',
					'label'       => __( 'Sidebar Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_top',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_right',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_left',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_border() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_borderr',
			[
				'default'           => $this->defaults['betterdocs_sidebar_borderr'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_borderr',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_borderr',
					'label'       => __( 'Sidebar Border Radius', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_borderr',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_borderr_topleft',
			[
				'default'           => $this->defaults['betterdocs_sidebar_borderr_topleft'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_borderr_topleft',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_borderr_topleft',
					'label'       => __( 'Top Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_borderr_topright',
			[
				'default'           => $this->defaults['betterdocs_sidebar_borderr_topright'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_borderr_topright',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_borderr_topright',
					'label'       => __( 'Top Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_borderr_bottomright',
			[
				'default'           => $this->defaults['betterdocs_sidebar_borderr_bottomright'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_borderr_bottomright',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_borderr_bottomright',
					'label'       => __( 'Bottom Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_borderr betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_borderr_bottomleft',
			[
				'default'           => $this->defaults['betterdocs_sidebar_borderr_bottomleft'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_borderr_bottomleft',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_borderr_bottomleft',
					'label'       => __( 'Bottom Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_borderr betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_title() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_sidebar_title',
				[
					'label'    => __( 'Sidebar Title', 'betterdocs' ),
					'settings' => 'betterdocs_sidebar_title',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	public function title_tag() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_tag',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_sidebar_title_tag',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_tag',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6',
						'p'  => 'p'
					]
				]
			)
		);
	}

	public function icon_size() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_icon_size',
			[
				'default'           => $this->defaults['betterdocs_sidebar_icon_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_icon_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_icon_size',
					'label'       => __( 'Icon Size', 'betterdocs' ),
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

	public function title_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_bg_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_bg_color',
				[
					'label'    => __( 'Title Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_bg_color'
				]
			)
		);
	}

	public function active_cat_background_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_cat_background_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_cat_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_cat_background_color',
				[
					'label'    => __( 'Active Title Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_cat_background_color'
				]
			)
		);
	}

	public function active_cat_border_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_cat_border_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_cat_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_cat_border_color',
				[
					'label'    => __( 'Active Title Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_cat_border_color'
				]
			)
		);
	}

	public function title_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_color',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_color'
				]
			)
		);
	}

	public function title_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_hover_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_hover_color',
				[
					'label'    => __( 'Title Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_hover_color'
				]
			)
		);
	}

	public function active_title_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_title_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_title_color',
				[
					'label'    => __( 'Active Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_title_color'
				]
			)
		);
	}

	public function title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_font_size',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
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

	public function title_padding() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding',
					'label'       => __( 'Title Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_title_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_top',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_right',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_left',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function title_margin() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin',
					'label'       => __( 'Title Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_title_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_top',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_right',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_left',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function item_counter_title() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_item_counter_title',
			[
				'default'           => $this->defaults['betterdocs_sidebar_item_counter_title'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_sidebar_item_counter_title',
				[
					'label'    => __( 'Sidebar Item Counter', 'betterdocs' ),
					'settings' => 'betterdocs_sidebar_item_counter_title',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	public function item_count_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_item_count_bg_color',
			[
				'default'           => $this->defaults['betterdocs_sidbebar_item_count_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidbebar_item_count_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_item_count_bg_color'
				]
			)
		);
	}

	public function item_count_inner_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_item_count_inner_bg_color',
			[
				'default'           => $this->defaults['betterdocs_sidbebar_item_count_inner_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidbebar_item_count_inner_bg_color',
				[
					'label'    => __( 'Inner Circle Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_item_count_inner_bg_color'
				]
			)
		);
	}

	public function item_counter_size() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_item_counter_size',
			[
				'default'           => $this->defaults['betterdocs_sidebar_item_counter_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_item_counter_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_item_counter_size',
					'label'       => __( 'Size (Height, Width)', 'betterdocs' ),
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

	public function item_count_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_item_count_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_item_count_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_item_count_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_item_count_color'
				]
			)
		);
	}

	public function item_count_font_size() {
		$this->customizer->add_setting(
			'betterdocs_sidebat_item_count_font_size',
			[
				'default'           => $this->defaults['betterdocs_sidebat_item_count_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebat_item_count_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebat_item_count_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
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

	public function sidebar_content() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_content',
			[
				'default'           => $this->defaults['betterdocs_sidebar_content'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_sidebar_content',
				[
					'label'    => __( 'Sidebar Content', 'betterdocs' ),
					'settings' => 'betterdocs_sidebar_content',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	public function item_list_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_item_list_bg_color',
			[
				'default'           => $this->defaults['betterdocs_sidbebar_item_list_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidbebar_item_list_bg_color',
				[
					'label'    => __( 'List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_item_list_bg_color'
				]
			)
		);
	}

	public function list_item_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_color',
				[
					'label'    => __( 'List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_color'
				]
			)
		);
	}

	public function list_item_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_hover_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_hover_color',
				[
					'label'    => __( 'List Item Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_hover_color'
				]
			)
		);
	}

	public function list_item_font_size() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_font_size',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_font_size',
					'label'       => __( 'List Item Font Size', 'betterdocs' ),
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

	public function list_icon() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_item_list_icon',
			[
				'default'    => $this->defaults['betterdocs_sidbebar_item_list_icon'],
				'capability' => 'edit_theme_options',

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_sidbebar_item_list_icon',
				[
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_item_list_icon',
					'label'    => __( 'List Icon', 'betterdocs' )
				]
			)
		);
	}

	public function list_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_icon_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_icon_color',
				[
					'label'    => __( 'List Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_icon_color'
				]
			)
		);
	}

	public function list_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_list_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_icon_font_size',
					'label'       => __( 'List Icon Font Size', 'betterdocs' ),
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

	public function list_item_margin() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin',
					'label'       => __( 'List Item Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_list_item_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_top',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_right',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_left',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function active_list_item_color() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_list_item_color',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_list_item_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_list_item_color',
				[
					'label'    => __( 'Active List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_list_item_color'
				]
			)
		);
	}

	public function sidebar_bg_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_bg_color_layout_7',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_bg_color_layout_7'
				]
			)
		);
	}

	public function sidebar_border_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_border_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_border_color_layout_7'],
				'capability'        => 'edit_theme_options',
				// 'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_border_color_layout_7',
				[
					'label'    => __( 'Border Right Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_border_color_layout_7'
				]
			)
		);
	}

	public function sidebar_padding_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_layout_7',
					'label'       => __( 'Sidebar Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_padding_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_search_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'sidebar_search_layout_7',
				[
					'label'    => __( 'Sidebar Search', 'betterdocs' ),
					'settings' => 'sidebar_search_layout_7',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	public function sidebar_search_layout_7_toggle() {
		$this->customizer->add_setting(
			'sidebar_search_layout_7_toggle',
			[
				'default'    => $this->defaults['sidebar_search_layout_7_toggle'],
				'capability' => 'edit_theme_options'

			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'sidebar_search_layout_7_toggle',
				[
					'label'    => __( 'Enable', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'sidebar_search_layout_7_toggle',
					'type'     => 'light', // light, ios, flat
				]
			)
		);
	}

	public function sidebar_search_field_placeholder_color_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_placeholder_color_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_placeholder_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'sidebar_search_field_placeholder_color_layout_7',
				[
					'label'    => __( 'Search Field Placeholder Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'sidebar_search_field_placeholder_color_layout_7'
				]
			)
		);
	}

	public function sidebar_search_field_background_color_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_background_color_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_background_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'sidebar_search_field_background_color_layout_7',
				[
					'label'    => __( 'Search Field Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'sidebar_search_field_background_color_layout_7'
				]
			)
		);
	}

	public function sidebar_search_field_icon_size_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_icon_size_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_icon_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'sidebar_search_field_icon_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_icon_size_layout_7',
					'label'       => __( 'Icon Size', 'betterdocs' ),
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

	public function sidebar_search_field_margin_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_margin_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'sidebar_search_field_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_margin_layout_7',
					'label'       => __( 'Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'sidebar_search_field_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_margin_top_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_margin_right_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_margin_left_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_search_field_padding_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_padding_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'sidebar_search_field_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_padding_layout_7',
					'label'       => __( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'sidebar_search_field_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_padding_top_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_padding_right_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_padding_left_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_search_field_command_key_padding_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_command_key_padding_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_command_key_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'sidebar_search_field_command_key_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_command_key_padding_layout_7',
					'label'       => __( 'Command Key Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'sidebar_search_field_command_key_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_command_key_padding_top_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_command_key_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_command_key_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_command_key_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_command_key_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_command_key_padding_right_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_command_key_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_command_key_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_command_key_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_command_key_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_command_key_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_command_key_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_command_key_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_command_key_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_command_key_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'sidebar_search_field_command_key_padding_left_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_command_key_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'sidebar_search_field_command_key_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'sidebar_search_field_command_key_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'sidebar_search_field_command_key_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_search_field_command_key_color_layout_7() {
		$this->customizer->add_setting(
			'sidebar_search_field_command_key_color_layout_7',
			[
				'default'           => $this->defaults['sidebar_search_field_command_key_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'sidebar_search_field_command_key_color_layout_7',
				[
					'label'    => __( 'Command Key Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'sidebar_search_field_command_key_color_layout_7'
				]
			)
		);
	}


	public function sidebar_title_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_sidebar_title_layout_7',
				[
					'label'    => __( 'Sidebar Title', 'betterdocs' ),
					'settings' => 'betterdocs_sidebar_title_layout_7',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	public function title_tag_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_tag_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_tag_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_sidebar_title_tag_layout_7',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_tag_layout_7',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6',
						'p'  => 'p'
					]
				]
			)
		);
	}

	public function icon_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_icon_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_icon_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_icon_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_icon_size_layout_7',
					'label'       => __( 'Icon Size', 'betterdocs' ),
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

	public function title_bg_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_bg_color_layout_7',
				[
					'label'    => __( 'Title Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_bg_color_layout_7'
				]
			)
		);
	}

	public function active_cat_background_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_cat_background_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_cat_background_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_cat_background_color_layout_7',
				[
					'label'    => __( 'Active Title Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_cat_background_color_layout_7'
				]
			)
		);
	}

	public function active_cat_border_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_cat_border_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_cat_border_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_cat_border_color_layout_7',
				[
					'label'    => __( 'Active Title Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_cat_border_color_layout_7'
				]
			)
		);
	}

	public function title_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_color_layout_7',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_color_layout_7'
				]
			)
		);
	}

	public function title_hover_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_hover_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_hover_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_hover_color_layout_7',
				[
					'label'    => __( 'Title Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_hover_color_layout_7'
				]
			)
		);
	}

	public function active_title_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_active_title_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_active_title_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_active_title_color_layout_7',
				[
					'label'    => __( 'Active Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_active_title_color_layout_7'
				]
			)
		);
	}

	public function title_font_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_title_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_font_size_layout_7',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
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

	public function title_padding_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_layout_7',
					'label'       => __( 'Title Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_title_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function title_margin_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_layout_7',
					'label'       => __( 'Title Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_title_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function item_counter_title_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_item_counter_title_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_item_counter_title_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_sidebar_item_counter_title_layout_7',
				[
					'label'    => __( 'Sidebar Item Counter', 'betterdocs' ),
					'settings' => 'betterdocs_sidebar_item_counter_title_layout_7',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	public function item_count_bg_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_item_count_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidbebar_item_count_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidbebar_item_count_bg_color_layout_7',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_item_count_bg_color_layout_7'
				]
			)
		);
	}

	public function item_counter_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_item_counter_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_item_counter_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_item_counter_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_item_counter_size_layout_7',
					'label'       => __( 'Size (Height, Width)', 'betterdocs' ),
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

	public function item_count_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_item_count_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_item_count_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_item_count_color_layout_7',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_item_count_color_layout_7'
				]
			)
		);
	}

	public function item_count_font_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebat_item_count_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebat_item_count_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebat_item_count_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebat_item_count_font_size_layout_7',
					'label'       => __( 'Font Size', 'betterdocs' ),
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

	public function item_count_font_padding_layout_7() {
		$this->customizer->add_setting(
			'item_count_font_padding_layout_7',
			[
				'default'           => $this->defaults['item_count_font_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'item_count_font_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'item_count_font_padding_layout_7',
					'label'       => __( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'item_count_font_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'item_count_font_padding_top_layout_7',
			[
				'default'           => $this->defaults['item_count_font_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'item_count_font_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'item_count_font_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'item_count_font_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'item_count_font_padding_right_layout_7',
			[
				'default'           => $this->defaults['item_count_font_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'item_count_font_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'item_count_font_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'item_count_font_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'item_count_font_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['item_count_font_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'item_count_font_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'item_count_font_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'item_count_font_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'item_count_font_padding_left_layout_7',
			[
				'default'           => $this->defaults['item_count_font_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'item_count_font_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'item_count_font_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'item_count_font_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_content_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_content_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_content_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_sidebar_content_layout_7',
				[
					'label'    => __( 'Sidebar Content', 'betterdocs' ),
					'settings' => 'betterdocs_sidebar_content_layout_7',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}


	public function betterdocs_sidbebar_list_bg_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_list_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidbebar_list_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidbebar_list_bg_color_layout_7',
				[
					'label'    => __( 'List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_list_bg_color_layout_7'
				]
			)
		);
	}

	public function item_list_bg_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidbebar_item_list_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidbebar_item_list_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidbebar_item_list_bg_color_layout_7',
				[
					'label'    => __( 'List Item Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidbebar_item_list_bg_color_layout_7'
				]
			)
		);
	}

	public function list_item_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_color_layout_7',
				[
					'label'    => __( 'List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_color_layout_7'
				]
			)
		);
	}

	public function list_item_hover_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_hover_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_hover_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_hover_color_layout_7',
				[
					'label'    => __( 'List Item Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_hover_color_layout_7'
				]
			)
		);
	}

	public function list_item_active_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_active_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_active_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_active_color_layout_7',
				[
					'label'    => __( 'List Item Active Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_active_color_layout_7'
				]
			)
		);
	}


	public function list_item_active_color_layout_7_2() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_active_color_layout_7_2',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_active_color_layout_7_2'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_active_color_layout_7_2',
				[
					'label'    => __( 'List Item Active Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_active_color_layout_7_2'
				]
			)
		);
	}

	public function list_item_font_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_font_size_layout_7',
					'label'       => __( 'List Item Font Size', 'betterdocs' ),
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

	public function list_item_margin_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_layout_7',
					'label'       => __( 'List Item Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_list_item_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function list_item_padding_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_padding_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_padding_layout_7',
					'label'       => __( 'List Background Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_sidebar_list_item_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_padding_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_padding_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_padding_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_sidebar_list_item_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function sidebar_tech_layout_wrapper() {
		$this->customizer->add_setting(
			'sidebar_tech_layout_wrapper',
			[
				'default'           => $this->defaults['sidebar_tech_layout_wrapper'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'sidebar_tech_layout_wrapper',
				[
					'label'    => __( 'Slate Layout Sidebar', 'betterdocs' ),
					'settings' => 'sidebar_tech_layout_wrapper',
					'section'  => 'betterdocs_sidebar_settings'
				]
			)
		);
	}

	// Layout-8 Controllers
	public function border_right_width_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_border_right_width_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_border_right_width_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_border_right_width_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_border_right_width_layout_8',
					'label'       => __( 'Border Right Width', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 10,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function border_right_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_border_right_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_border_right_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_border_right_color_layout_8',
				[
					'label'    => __( 'Border Right Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_border_right_color_layout_8'
				]
			)
		);
	}


	public function title_tag_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_tag_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_tag_layout_8'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_sidebar_title_tag_layout_8',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_tag_layout_8',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6',
						'p'  => 'p'
					]
				]
			)
		);
	}

	public function title_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_title_color_layout_8',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_title_color_layout_8'
				]
			)
		);
	}

	public function title_font_size_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_font_size_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_font_size_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_title_font_size_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_title_font_size_layout_8',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
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

	public function title_margin_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_margin_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_margin_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_margin_layout_8',
				[
					'label'        => __( 'Title Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_sidebar_settings',
					'settings'     => 'betterdocs_sidebar_title_margin_layout_8',
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

	public function title_padding_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_title_padding_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_title_padding_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_sidebar_title_padding_layout_8',
				[
					'label'        => __( 'Title Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_sidebar_settings',
					'settings'     => 'betterdocs_sidebar_title_padding_layout_8',
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

	public function collapsible_arrow_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_collapsible_arrow_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_collapsible_arrow_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_collapsible_arrow_color_layout_8',
				[
					'label'    => __( 'Collapsible Arrow Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_collapsible_arrow_color_layout_8'
				]
			)
		);
	}

	public function list_item_font_size_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_font_size_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_font_size_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_font_size_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_sidebar_settings',
					'settings'    => 'betterdocs_sidebar_list_item_font_size_layout_8',
					'label'       => __( 'List Item Font Size', 'betterdocs' ),
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

	public function list_item_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_color_layout_8',
				[
					'label'    => __( 'List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_color_layout_8'
				]
			)
		);
	}

	public function list_item_hover_background_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_hover_background_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_hover_background_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_hover_background_color_layout_8',
				[
					'label'    => __( 'List Item Hover Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_hover_background_color_layout_8'
				]
			)
		);
	}

	public function list_item_hover_color_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_hover_color_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_hover_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_hover_color_layout_8',
				[
					'label'    => __( 'List Item Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_sidebar_settings',
					'settings' => 'betterdocs_sidebar_list_item_hover_color_layout_8'
				]
			)
		);
	}

	public function list_item_margin_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_margin_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_margin_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_margin_layout_8',
				[
					'label'        => __( 'List Item Margin (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_sidebar_settings',
					'settings'     => 'betterdocs_sidebar_list_item_margin_layout_8',
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 1,
						'input2' => 0,
						'input3' => 0,
						'input4' => 0
					]
				]
			)
		);
	}

	public function list_item_padding_layout_8() {
		$this->customizer->add_setting(
			'betterdocs_sidebar_list_item_padding_layout_8',
			[
				'default'           => $this->defaults['betterdocs_sidebar_list_item_padding_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage'
			]
		);

		$this->customizer->add_control(
			new MultiDimensionControl(
				$this->customizer,
				'betterdocs_sidebar_list_item_padding_layout_8',
				[
					'label'        => __( 'List Item Padding (PX)', 'betterdocs' ),
					'section'      => 'betterdocs_sidebar_settings',
					'settings'     => 'betterdocs_sidebar_list_item_padding_layout_8',
					'input_fields' => [
						'input1' => __( 'top', 'betterdocs' ),
						'input2' => __( 'right', 'betterdocs' ),
						'input3' => __( 'bottom', 'betterdocs' ),
						'input4' => __( 'left', 'betterdocs' )
					],
					'defaults'     => [
						'input1' => 7,
						'input2' => 8,
						'input3' => 7,
						'input4' => 8
					]
				]
			)
		);
	}
}
