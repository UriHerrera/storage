<?php

namespace WPDeveloper\BetterDocsPro\Admin\Customizer\Sections;


use WP_Customize_Control;
use WP_Customize_Image_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;


class SingleGlossaries extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority = 703;

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'single_glossaries_page_settings';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'Single Glossaries', 'betterdocs-pro' );
	}

    public function betterdocs_single_glossaries_breadcrumb_section()
    {
        $this->customizer->add_setting('betterdocs_single_glossaries_breadcrumb_section', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'betterdocs_single_glossaries_breadcrumb_section',
            [
                'label'    => __('Breadcrumb', 'betterdocs-pro'),
                'settings' => 'betterdocs_single_glossaries_breadcrumb_section',
                'section'  => 'single_glossaries_page_settings',
                'priority' => 1
            ]
        ));
    }


    public function betterdocs_single_glossaries_breadcrumbs_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_breadcrumbs_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_breadcrumbs_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_glossaries_breadcrumbs_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_breadcrumbs_font_size',
					'label'       => __( 'Font Size', 'betterdocs-pro' ),
					'priority'    => 2,
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


    public function betterdocs_single_glossaries_breadcrumb_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_breadcrumb_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_glossaries_breadcrumb_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_breadcrumb_color',
				[
					'label'    => __( 'Color', 'betterdocs-pro' ),
					'priority' => 4,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_breadcrumb_color'
				]
			)
		);
	}

    public function betterdocs_single_glossaries_breadcrumb_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_breadcrumb_hover_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_glossaries_breadcrumb_hover_color'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_breadcrumb_hover_color',
				[
					'label'    => __( 'Hover Color', 'betterdocs-pro' ),
					'priority' => 5,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_breadcrumb_hover_color'
				]
			)
		);
	}

    public function betterdocs_single_glossaries_breadcrumb_speretor_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_breadcrumb_speretor_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_glossaries_breadcrumb_speretor_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_breadcrumb_speretor_color',
				[
					'label'    => __( 'Seperator Color', 'betterdocs-pro' ),
					'priority' => 6,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_breadcrumb_speretor_color'
				]
			)
		);
	}

    public function betterdocs_single_glossaries_breadcrumb_active_item_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_breadcrumb_active_item_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_glossaries_breadcrumb_active_item_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_breadcrumb_active_item_color',
				[
					'label'    => __( 'Active Item Color', 'betterdocs-pro' ),
					'priority' => 7,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_breadcrumb_active_item_color'
				]
			)
		);
	}


    public function betterdocs_single_glossaries_title_separator()
    {
        $this->customizer->add_setting('betterdocs_single_glossaries_title_separator', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'betterdocs_single_glossaries_title_separator',
            [
                'label'    => __('Glossary Title', 'betterdocs-pro'),
                'settings' => 'betterdocs_single_glossaries_title_separator',
                'section'  => 'single_glossaries_page_settings',
                'priority' => 7
            ]
        ));
    }

    public function betterdocs_single_glossaries_post_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_post_title_tag',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_post_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_single_glossaries_post_title_tag',
				[
					'label'    => __( 'Title Tag', 'betterdocs-pro' ),
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_post_title_tag',
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
					'priority' => 8
				]
			)
		);
	}

	public function betterdocs_single_glossaries_post_title_text_transform() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_post_title_text_transform',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_post_title_text_transform'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_single_glossaries_post_title_text_transform',
				[
					'label'    => __( 'Text Transform', 'betterdocs-pro' ),
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_post_title_text_transform',
					'type'     => 'select',
					'choices'  => [
						'uppercase'  => 'Uppercase',
						'lowercase'  => 'Lowercase',
						'capitalize' => 'Capitalize',
						'none'       => 'Normal'
					],
					'priority' => 9
				]
			)
		);
	}

	public function betterdocs_single_glossaries_title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_title_font_size',
					'label'       => __( 'Font Size', 'betterdocs-pro' ),
					'priority'    => 10,
					'input_attrs' => [
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function betterdocs_single_glossaries_title_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_glossaries_title_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_color',
				[
					'label'    => __( 'Color', 'betterdocs-pro' ),
					'priority' => 11,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_title_color'
				]
			)
		);
	}

	public function betterdocs_single_glossaries_title_margin_layout() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_margin_layout',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_title_margin_layout'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_margin_layout',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_title_margin_layout',
					'label'       => __( 'Title Margin', 'betterdocs-pro' ),
					'priority'    => 12,
					'input_attrs' => [
						'id'    => 'betterdocs_single_glossaries_title_margin_layout',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_margin_top_layout',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_title_margin_top_layout'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_margin_top_layout',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_title_margin_top_layout',
					'label'       => __( 'Top', 'betterdocs-pro' ),
					'priority'    => 13,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_title_margin_layout betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_margin_right_layout',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_title_margin_right_layout'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_margin_right_layout',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_title_margin_right_layout',
					'label'       => __( 'Right', 'betterdocs-pro' ),
					'priority'    => 14,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_title_margin_layout betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_margin_bottom_layout',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_title_margin_bottom_layout'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_margin_bottom_layout',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_title_margin_bottom_layout',
					'label'       => __( 'Bottom', 'betterdocs-pro' ),
					'priority'    => 15,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_title_margin_layout betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_title_margin_left_layout',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_title_margin_left_layout'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_title_margin_left_layout',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_title_margin_left_layout',
					'label'       => __( 'Left', 'betterdocs-pro' ),
					'priority'    => 16,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_title_margin_layout betterdocs-dimension'
					]
				]
			)
		);
	}

    public function betterdocs_single_glossaries_estimate_reading_time_section() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_estimate_reading_time_section',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_estimate_reading_time_section'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_single_glossaries_estimate_reading_time_section',
				[
					'label'    => __( 'Estimated Reading Time', 'betterdocs-pro' ),
					'priority' => 16,
					'settings' => 'betterdocs_single_glossaries_estimate_reading_time_section',
					'section'  => 'single_glossaries_page_settings'
				]
			)
		);
	}

	public function betterdocs_single_glossaries_estimate_reading_time_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_estimate_reading_time_bg_color',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_estimate_reading_time_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_estimate_reading_time_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs-pro' ),
					'priority' => 17,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_estimate_reading_time_bg_color'
				]
			)
		);
	}

	public function betterdocs_single_glossaries_estimate_reading_time_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_estimate_reading_time_icon_color',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_estimate_reading_time_icon_color'],
				'capability'        => 'edit_theme_options',
                'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_estimate_reading_time_icon_color',
				[
					'label'    => __( 'Clock Icon Color', 'betterdocs-pro' ),
					'priority' => 18,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_estimate_reading_time_icon_color'
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_est_reading_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_icon_font_size',
					'label'       => __( 'Clock Icon Size', 'betterdocs-pro' ),
					'priority'    => 19,
					'input_attrs' => [
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function betterdocs_single_glossaries_estimate_reading_time_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_estimate_reading_time_color',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_estimate_reading_time_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_estimate_reading_time_color',
				[
					'label'    => __( 'Font Color', 'betterdocs-pro' ),
					'priority' => 20,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_estimate_reading_time_color'
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_est_reading_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_font_size',
					'label'       => __( 'Font Size', 'betterdocs-pro' ),
					'priority'    => 21,
					'input_attrs' => [
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_est_reading_border_radius() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_border_radius',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_border_radius'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_border_radius',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_border_radius',
					'label'       => __( 'Border Radius', 'betterdocs-pro' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 22
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_est_reading_font_weight() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_font_weight',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_font_weight'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_font_weight',
				[
					'label'    => __( 'Font Weight', 'betterdocs-pro' ),
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_content_est_reading_font_weight',
					'type'     => 'select',
					'choices'  => [
						'normal' => 'Normal',
						'100'    => '100',
						'200'    => '200',
						'300'    => '300',
						'400'    => '400',
						'500'    => '500',
						'600'    => '600',
						'700'    => '700',
						'800'    => '800',
						'900'    => '900'
					],
					'priority' => 23
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_est_reading_margin() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_margin',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_margin',
					'label'       => __( 'Margin', 'betterdocs-pro' ),
					'priority'    => 24,
					'input_attrs' => [
						'id'    => 'betterdocs_single_glossaries_content_est_reading_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_margin_top',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_margin_top',
					'label'       => __( 'Top', 'betterdocs-pro' ),
					'priority'    => 25,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_margin_right',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_margin_right',
					'label'       => __( 'Right', 'betterdocs-pro' ),
					'priority'    => 26,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs-pro' ),
					'priority'    => 27,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_margin_left',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_margin_left',
					'label'       => __( 'Left', 'betterdocs-pro' ),
					'priority'    => 28,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_est_reading_padding() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_padding',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_padding',
					'label'       => __( 'Padding', 'betterdocs-pro' ),
					'priority'    => 29,
					'input_attrs' => [
						'id'    => 'betterdocs_single_glossaries_content_est_reading_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_padding_top',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_padding_top',
					'label'       => __( 'Top', 'betterdocs-pro' ),
					'priority'    => 30,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_padding_right',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_padding_right',
					'label'       => __( 'Right', 'betterdocs-pro' ),
					'priority'    => 31,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs-pro' ),
					'priority'    => 32,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_est_reading_padding_left',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_est_reading_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_est_reading_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_est_reading_padding_left',
					'label'       => __( 'Left', 'betterdocs-pro' ),
					'priority'    => 33,
					'input_attrs' => [
						'class' => 'betterdocs_single_glossaries_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);
	}

    public function betterdocs_single_glossaries_entry_content() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_entry_content',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_entry_content'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_single_glossaries_entry_content',
				[
					'label'    => __( 'Glossary Content', 'betterdocs-pro' ),
					'priority' => 34,
					'settings' => 'betterdocs_single_glossaries_entry_content',
					'section'  => 'single_glossaries_page_settings'
				]
			)
		);
	}

	public function betterdocs_single_glossaries_content_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'single_glossaries_page_settings',
					'settings'    => 'betterdocs_single_glossaries_content_font_size',
					'label'       => __( 'Font Size', 'betterdocs-pro' ),
					'priority'    => 35,
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

	public function betterdocs_single_glossaries_content_font_color() {
		$this->customizer->add_setting(
			'betterdocs_single_glossaries_content_font_color',
			[
				'default'           => $this->defaults['betterdocs_single_glossaries_content_font_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_glossaries_content_font_color',
				[
					'label'    => __( 'Font Color', 'betterdocs-pro' ),
					'priority' => 36,
					'section'  => 'single_glossaries_page_settings',
					'settings' => 'betterdocs_single_glossaries_content_font_color'
				]
			)
		);
	}
}
