<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WP_Customize_Control;
use WP_Customize_Image_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\NumberControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;

class SingleDoc extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority = 101;

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'betterdocs_single_docs_settings';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'Single Doc', 'betterdocs' );
	}

	public function single_layout_select() {

		$encyclopeia_suorce = betterdocs()->settings->get( 'encyclopedia_source', 'docs' );

		$this->customizer->add_setting(
			'betterdocs_single_layout_select',
			[
				'default'           => $this->defaults['betterdocs_single_layout_select'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$choices = [
			'layout-8' => [
				'label' => __( 'Essence Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-8.png', true ),
			],
			'layout-9' => [
				'label' => __( 'Rustic Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-9.png', true ),
			],
			'layout-10' => [
				'label' => __( 'Slate Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-10.png', true ),
			],
			'layout-1' => [
				'label' => __( 'Classic Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-1.png', true )
			],
			'layout-4' => [
				'label' => __( 'Abstract Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-4.png', true ),
				'pro'   => false
			],
			'layout-5' => [
				'label' => __( 'Modern Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-5.png', true ),
				'pro'   => false
			],
			'layout-2' => [
				'label' => __( 'Minimalist Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-2.png', true ),
				'pro'   => true,
				'url'   => 'https://betterdocs.co/upgrade'
			],
			'layout-3' => [
				'label' => __( 'Artisan Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-3.png', true ),
				'pro'   => true,
				'url'   => 'https://betterdocs.co/upgrade'
			],
			'layout-6' => [
				'label' => __( 'Bohemian Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/single/layout-6.png', true ),
				'pro'   => true,
				'url'   => 'https://betterdocs.co/upgrade'
			],
		];

		if ( $encyclopeia_suorce == 'docs' ) {
			$choices['layout-7'] = [
				'label' => __( 'Encyclopedia Layout', 'betterdocs' ),
				'image' => $this->assets->icon( 'customizer/encyclopedia/layout-7.png', true ),
				'pro'   => true,
				'url'   => 'https://betterdocs.co/upgrade'
			];
		}

		$this->customizer->add_control(
			new RadioImageControl(
				$this->customizer,
				'betterdocs_single_layout_select',
				[
					'type'     => 'betterdocs-radio-image',
					'settings' => 'betterdocs_single_layout_select',
					'section'  => 'betterdocs_single_docs_settings',
					'label'    => __( 'Select Layout', 'betterdocs' ),
					'priority' => 102,
					'choices'  => apply_filters( 'betterdocs_single_layout_select_choices', $choices )
				]
			)
		);
	}

	/**
	 * Content area width for the single doc, mirroring the Docs Page pair.
	 *
	 * Only meaningful on the layouts whose content wrapper is actually capped
	 * by the stylesheet — 1, 5, 6, 8, 9 and 10. Layouts 2, 3 and 4 are
	 * full-bleed by design and have no width to constrain, so both controls are
	 * hidden for them in customizer-condition.js (the codebase drives control
	 * visibility from JS bound to the layout setting, not active_callback).
	 */
	public function single_doc_content_area_width() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_content_width',
			apply_filters(
				'betterdocs_single_doc_content_width',
				[
					'default'           => $this->defaults['betterdocs_single_doc_content_width'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_content_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_content_width',
					'label'       => __( 'Content Area Width', 'betterdocs' ),
					'priority'    => 103,
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

	public function single_doc_content_area_max_width() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_content_max_width',
			apply_filters(
				'betterdocs_single_doc_content_max_width',
				[
					'default'           => $this->defaults['betterdocs_single_doc_content_max_width'],
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => [ $this->sanitizer, 'integer' ]
				]
			)
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_content_max_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_content_max_width',
					'label'       => __( 'Content Area Maximum Width', 'betterdocs' ),
					'priority'    => 103,
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

	public function doc_single_content_area_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_area_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_content_area_bg_color',
				[
					'label'    => __( 'Content Area Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_area_bg_color',
					'priority' => 103
				]
			)
		);
	}

	public function doc_single_content_area_bg_image() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_bg_image',
			[
				'default'    => $this->defaults['betterdocs_doc_single_content_area_bg_image'],
				'capability' => 'edit_theme_options',
				'transport'  => 'postMessage'
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_doc_single_content_area_bg_image',
				[
					'label'    => __( 'Background Image', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_area_bg_image',
					'priority' => 103
				]
			)
		);
	}

	public function doc_single_content_bg_property() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_bg_property',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_bg_property'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_content_bg_property',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_bg_property',
					'priority'    => 103,
					'label'       => __( 'Background Property', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_content_bg_property',
						'class' => 'betterdocs-select'
					]
				]
			)
		);
	}

	public function doc_single_content_bg_property_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_bg_property_size',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_bg_property_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_single_content_bg_property_size',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_bg_property_size',
					'priority'    => 103,
					'label'       => __( 'Size', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_bg_property betterdocs-select'
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
	}

	public function doc_single_content_bg_property_repeat() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_bg_property_repeat',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_bg_property_repeat'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_single_content_bg_property_repeat',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_bg_property_repeat',
					'priority'    => 103,
					'label'       => __( 'Repeat', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_bg_property betterdocs-select'
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
	}

	public function doc_single_content_bg_property_attachment() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_bg_property_attachment',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_bg_property_attachment'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_single_content_bg_property_attachment',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_bg_property_attachment',
					'priority'    => 103,
					'label'       => __( 'Attachment', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_bg_property betterdocs-select'
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
	}

	public function doc_single_content_bg_property_position() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_bg_property_position',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_bg_property_position'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'esc_html'

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_doc_single_content_bg_property_position',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_bg_property_position',
					'priority'    => 103,
					'label'       => __( 'Position', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_bg_property betterdocs-select'
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

	public function doc_single_content_area_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_area_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_content_area_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_area_padding',
					'label'       => __( 'Content Area Padding', 'betterdocs' ),
					'priority'    => 104,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_content_area_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_area_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_area_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_area_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 104,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_area_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_area_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_area_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_area_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 104,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_area_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_area_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_area_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_area_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 104,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_area_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_area_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_area_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_area_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_area_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 104,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_area_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_post_content_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_post_content_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_post_content_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_post_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_post_content_padding',
					'label'       => __( 'Doc Content Padding', 'betterdocs' ),
					'priority'    => 109,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_post_content_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_post_content_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_post_content_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_post_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_post_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 109,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_post_content_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_post_content_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_post_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_post_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 109,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_post_content_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_post_content_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_post_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_post_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 109,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_post_content_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_post_content_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_post_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_post_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 109,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_post_content_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_2_post_content_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_2_post_content_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_2_post_content_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_2_post_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_2_post_content_padding',
					'label'       => __( 'Post Content Padding', 'betterdocs' ),
					'priority'    => 114,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_2_post_content_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_2_post_content_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_2_post_content_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_2_post_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_2_post_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 114,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_2_post_content_padding  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_2_post_content_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_2_post_content_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_2_post_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_2_post_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 114,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_2_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_2_post_content_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_2_post_content_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_2_post_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_2_post_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 114,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_2_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_2_post_content_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_2_post_content_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_2_post_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_2_post_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 114,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_2_post_content_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_3_post_content_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_3_post_content_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_3_post_content_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_3_post_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_3_post_content_padding',
					'label'       => __( 'Content Area Inner Padding', 'betterdocs' ),
					'priority'    => 119,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_3_post_content_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_3_post_content_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_3_post_content_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_3_post_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_3_post_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 119,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_3_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_3_post_content_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_3_post_content_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_3_post_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_3_post_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 119,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_3_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_3_post_content_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_3_post_content_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_3_post_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_3_post_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 119,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_3_post_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_3_post_content_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_3_post_content_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_3_post_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_3_post_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 119,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_3_post_content_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function single_doc_layout_8_9_search_wrapper() {
		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_wrapper',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_wrapper'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'single_doc_layout_8_9_search_wrapper',
				[
					'label'    => __( 'Search', 'betterdocs' ),
					'settings' => 'single_doc_layout_8_9_search_wrapper',
					'section'  => 'betterdocs_single_docs_settings',
					'priority' => 124
				]
			)
		);
	}

	public function single_doc_layout_8_9_search_toogle() {
		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_toogle',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_toogle'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'single_doc_layout_8_9_search_toogle',
				[
					'label'    => __( 'Enable', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'single_doc_layout_8_9_search_toogle',
					'type'     => 'light', // light, ios, flat,
					'priority' => 124
				]
			)
		);
	}

	public function single_doc_layout_8_9_search_width() {
		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_width',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'single_doc_layout_8_9_search_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_width',
					'label'       => __( 'Search Width', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					],
					'priority'    => 124
				]
			)
		);
	}

	public function single_doc_layout_8_9_search_max_width() {
		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_max_width',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_max_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'single_doc_layout_8_9_search_max_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_max_width',
					'label'       => __( 'Search Max Width', 'betterdocs' ), //Renamed From 'Content Area Width' to 'Category Archive Width' @since betterdocs revamp version
					'input_attrs' => [
						'min'    => 0,
						'max'    => 3000,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 124
				]
			)
		);
	}


	public function single_doc_layout_8_9_search_margin() {
		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_margin',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'single_doc_layout_8_9_search_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_margin',
					'label'       => __( 'Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'single_doc_layout_8_9_search_margin',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_margin_top',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_layout_8_9_search_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'single_doc_layout_8_9_search_margin betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_margin_bottom',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_layout_8_9_search_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'single_doc_layout_8_9_search_margin betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);
	}

	public function single_doc_layout_8_9_search_padding() {
		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_padding',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'single_doc_layout_8_9_search_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_padding',
					'label'       => __( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'single_doc_layout_8_9_search_padding',
						'class' => 'betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_padding_top',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_layout_8_9_search_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'single_doc_layout_8_9_search_padding betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_padding_right',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_layout_8_9_search_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'single_doc_layout_8_9_search_padding betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_padding_bottom',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_layout_8_9_search_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'single_doc_layout_8_9_search_padding betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_layout_8_9_search_padding_left',
			[
				'default'           => $this->defaults['single_doc_layout_8_9_search_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_layout_8_9_search_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_layout_8_9_search_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'single_doc_layout_8_9_search_padding betterdocs-dimension'
					],
					'priority'    => 124
				]
			)
		);
	}

	public function single_doc_title() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_title',
			[
				'default'           => $this->defaults['betterdocs_single_doc_title'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_single_doc_title',
				[
					'label'    => __( 'Post Title', 'betterdocs' ),
					'priority' => 124,
					'settings' => 'betterdocs_single_doc_title',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function post_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_post_title_tag',
			[
				'default'           => $this->defaults['betterdocs_post_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_post_title_tag',
				[
					'label'    => __( 'Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_title_tag',
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
					'priority' => 124
				]
			)
		);
	}

	public function post_title_text_transform() {
		$this->customizer->add_setting(
			'betterdocs_post_title_text_transform',
			[
				'default'           => $this->defaults['betterdocs_post_title_text_transform'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_post_title_text_transform',
				[
					'label'    => __( 'Text Transform', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_title_text_transform',
					'type'     => 'select',
					'choices'  => [
						'uppercase'  => 'Uppercase',
						'lowercase'  => 'Lowercase',
						'capitalize' => 'Capitalize',
						'none'       => 'Normal'
					],
					'priority' => 124
				]
			)
		);
	}

	public function single_doc_title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_title_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 125,
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

	public function single_doc_title_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_title_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_doc_title_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_title_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 126,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_title_color'
				]
			)
		);
	}

	public function post_title_text_transform_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_post_title_text_transform_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_post_title_text_transform_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_post_title_text_transform_layout_8_9',
				[
					'label'    => __( 'Text Transform', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_title_text_transform_layout_8_9',
					'type'     => 'select',
					'choices'  => [
						'uppercase'  => 'Uppercase',
						'lowercase'  => 'Lowercase',
						'capitalize' => 'Capitalize',
						'none'       => 'Normal'
					],
					'priority' => 124
				]
			)
		);
	}

	public function single_doc_title_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_title_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_doc_title_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_title_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_title_font_size_layout_8_9',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 125,
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

	public function single_doc_title_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_title_color_layout_8_9',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_single_doc_title_color_layout_8_9'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_title_color_layout_8_9',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'priority' => 126,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_title_color_layout_8_9'
				]
			)
		);
	}

	public function single_doc_title_margin_layout_8_9() {
		$this->customizer->add_setting(
			'single_doc_title_margin_layout_8_9',
			[
				'default'           => $this->defaults['single_doc_title_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'single_doc_title_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_title_margin_layout_8_9',
					'label'       => __( 'Title Margin', 'betterdocs' ),
					'priority'    => 126,
					'input_attrs' => [
						'id'    => 'single_doc_title_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_title_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['single_doc_title_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_title_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_title_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 126,
					'input_attrs' => [
						'class' => 'single_doc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_title_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['single_doc_title_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_title_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_title_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 126,
					'input_attrs' => [
						'class' => 'single_doc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_title_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['single_doc_title_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_title_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_title_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 126,
					'input_attrs' => [
						'class' => 'single_doc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_doc_title_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['single_doc_title_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_doc_title_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_doc_title_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 126,
					'input_attrs' => [
						'class' => 'single_doc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_article_summary_title() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_article_summary_title',
			[
				'default'           => $this->defaults['betterdocs_doc_single_article_summary_title'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_article_summary_title',
				[
					'label'    => __( 'Doc Summary', 'betterdocs' ),
					'priority' => 130,
					'settings' => 'betterdocs_doc_single_article_summary_title',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function article_summary_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_bg_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_article_summary_bg_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_article_summary_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 130,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_article_summary_bg_color'
				]
			)
		);
	}

	public function article_summary_border_color() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_border_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_article_summary_border_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_article_summary_border_color',
				[
					'label'    => __( 'Border Color', 'betterdocs' ),
					'priority' => 130,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_article_summary_border_color'
				]
			)
		);
	}

	public function article_summary_border_width() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_border_width',
			[
				'default'           => $this->defaults['betterdocs_article_summary_border_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_article_summary_border_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_article_summary_border_width',
					'label'       => __( 'Border Width', 'betterdocs' ),
					'priority'    => 130,
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

	public function article_summary_border_radius() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_border_radius',
			[
				'default'           => $this->defaults['betterdocs_article_summary_border_radius'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_article_summary_border_radius',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_article_summary_border_radius',
					'label'       => __( 'Border Radius', 'betterdocs' ),
					'priority'    => 130,
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

	public function article_summary_title_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_title_bg_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_article_summary_title_bg_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_article_summary_title_bg_color',
				[
					'label'    => __( 'Title Background Color', 'betterdocs' ),
					'priority' => 130,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_article_summary_title_bg_color'
				]
			)
		);
	}

	public function article_summary_title_color() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_title_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_article_summary_title_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_article_summary_title_color',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'priority' => 130,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_article_summary_title_color'
				]
			)
		);
	}

	public function article_summary_title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_article_summary_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_article_summary_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_article_summary_title_font_size',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
					'priority'    => 130,
					'input_attrs' => [
						'class'  => '',
						'min'    => 12,
						'max'    => 32,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function article_summary_content_color() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_content_color',
			[
				'default'           => $this->defaults['betterdocs_article_summary_content_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_article_summary_content_color',
				[
					'label'    => __( 'Content Color', 'betterdocs' ),
					'priority' => 130,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_article_summary_content_color'
				]
			)
		);
	}

	public function article_summary_content_font_size() {
		$this->customizer->add_setting(
			'betterdocs_article_summary_content_font_size',
			[
				'default'           => $this->defaults['betterdocs_article_summary_content_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_article_summary_content_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_article_summary_content_font_size',
					'label'       => __( 'Content Font Size', 'betterdocs' ),
					'priority'    => 130,
					'input_attrs' => [
						'class'  => '',
						'min'    => 10,
						'max'    => 24,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function doc_single_toc_title() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_title',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_title'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_toc_title',
				[
					'label'    => __( 'Table of Contents', 'betterdocs' ),
					'priority' => 132,
					'settings' => 'betterdocs_doc_single_toc_title',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function sticky_toc_width() {
		$this->customizer->add_setting(
			'betterdocs_sticky_toc_width',
			[
				'default'           => $this->defaults['betterdocs_sticky_toc_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sticky_toc_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_sticky_toc_width',
					'label'       => __( 'Sticky Toc Width', 'betterdocs' ),
					'priority'    => 133,
					'input_attrs' => [
						'class'  => '',
						'min'    => 100,
						'max'    => 500,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function sticky_toc_zindex() {
		$this->customizer->add_setting(
			'betterdocs_sticky_toc_zindex',
			[
				'default'           => $this->defaults['betterdocs_sticky_toc_zindex'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new NumberControl(
				$this->customizer,
				'betterdocs_sticky_toc_zindex',
				[
					'type'     => 'betterdocs-number',
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_sticky_toc_zindex',
					'label'    => __( 'Sticky Toc z-index', 'betterdocs' ),
					'priority' => 134
				]
			)
		);
	}

	public function sticky_toc_margin_top() {
		$this->customizer->add_setting(
			'betterdocs_sticky_toc_margin_top',
			[
				'default'           => $this->defaults['betterdocs_sticky_toc_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_sticky_toc_margin_top',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_sticky_toc_margin_top',
					'label'       => __( 'Sticky Toc Margin Top', 'betterdocs' ),
					'priority'    => 135,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 500,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function toc_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_toc_bg_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_bg_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 136,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_bg_color'
				]
			)
		);
	}

	public function doc_single_toc_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding',
					'label'       => __( 'Content Area Padding', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_toc_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_toc_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin',
					'label'       => __( 'Content Area Margin', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_toc_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function toc_title_color() {
		$this->customizer->add_setting(
			'betterdocs_toc_title_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_title_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_title_color',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'priority' => 142,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_title_color'
				]
			)
		);
	}

	public function toc_title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_toc_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_toc_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_toc_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_toc_title_font_size',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
					'priority'    => 143,
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

	public function toc_list_item_color() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_item_color',
			[
				'default'           => $this->defaults['betterdocs_toc_list_item_color'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_list_item_color',
				[
					'label'    => __( 'List Item Color', 'betterdocs' ),
					'priority' => 144,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_list_item_color'
				]
			)
		);
	}

	public function toc_list_item_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_item_hover_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_list_item_hover_color'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_list_item_hover_color',
				[
					'label'    => __( 'List Item Hover Color', 'betterdocs' ),
					'priority' => 145,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_list_item_hover_color'
				]
			)
		);
	}

	public function toc_active_item_color() {
		$this->customizer->add_setting(
			'betterdocs_toc_active_item_color',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_active_item_color'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_active_item_color',
				[
					'label'    => __( 'Active Item Color', 'betterdocs' ),
					'priority' => 146,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_active_item_color'
				]
			)
		);
	}

	public function toc_list_item_font_size() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_item_font_size',
			[
				'default'           => $this->defaults['betterdocs_toc_list_item_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_toc_list_item_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_toc_list_item_font_size',
					'label'       => __( 'List Item Font Size', 'betterdocs' ),
					'priority'    => 147,
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

	public function doc_single_toc_list_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin',
					'label'       => __( 'TOC List Margin', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_toc_list_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function toc_list_number_color() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_number_color',
			[
				'default'           => $this->defaults['betterdocs_toc_list_number_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_list_number_color',
				[
					'label'    => __( 'List Number Color', 'betterdocs' ),
					'priority' => 153,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_list_number_color'
				]
			)
		);
	}

	public function toc_list_number_font_size() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_number_font_size',
			[
				'default'           => $this->defaults['betterdocs_toc_list_number_font_size'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_toc_list_number_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_toc_list_number_font_size',
					'label'       => __( 'List Number Font Size', 'betterdocs' ),
					'priority'    => 154,
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

	public function toc_margin_bottom() {
		$this->customizer->add_setting(
			'betterdocs_toc_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_toc_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_toc_margin_bottom',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_toc_margin_bottom',
					'label'       => __( 'TOC Margin Bottom', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 500,
						'step'   => 1,
						'suffix' => 'px' // optional suffix
					]
				]
			)
		);
	}

	public function doc_single_toc_title_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_title_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_title_layout_8_9'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_toc_title_layout_8_9',
				[
					'label'    => __( 'Table of Contents', 'betterdocs' ),
					'priority' => 132,
					'settings' => 'betterdocs_doc_single_toc_title_layout_8_9',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	// public function sticky_toc_width_layout_8_9() {
	//     $this->customizer->add_setting( 'betterdocs_sticky_toc_width_layout_8_9', [
	//         'default'           => $this->defaults['betterdocs_sticky_toc_width_layout_8_9'],
	//         'capability'        => 'edit_theme_options',
	//         'transport'         => 'postMessage',
	//         'sanitize_callback' => [$this->sanitizer, 'integer']

	//     ] );

	//     $this->customizer->add_control( new RangeValueControl(
	//         $this->customizer, 'betterdocs_sticky_toc_width_layout_8_9', [
	//             'type'        => 'betterdocs-range-value',
	//             'section'     => 'betterdocs_single_docs_settings',
	//             'settings'    => 'betterdocs_sticky_toc_width_layout_8_9',
	//             'label'       => __( 'Sticky Toc Width', 'betterdocs' ),
	//             'priority'    => 133,
	//             'input_attrs' => [
	//                 'class'  => '',
	//                 'min'    => 100,
	//                 'max'    => 500,
	//                 'step'   => 1,
	//                 'suffix' => 'px' //optional suffix
	//             ]
	//         ] )
	//     );
	// }

	// public function sticky_toc_zindex_layout_8_9() {
	//     $this->customizer->add_setting( 'betterdocs_sticky_toc_zindex_layout_8_9', [
	//         'default'           => $this->defaults['betterdocs_sticky_toc_zindex_layout_8_9'],
	//         'capability'        => 'edit_theme_options',
	//         'transport'         => 'postMessage',
	//         'sanitize_callback' => [$this->sanitizer, 'integer']
	//     ] );

	//     $this->customizer->add_control( new NumberControl(
	//         $this->customizer, 'betterdocs_sticky_toc_zindex_layout_8_9', [
	//             'type'     => 'betterdocs-number',
	//             'section'  => 'betterdocs_single_docs_settings',
	//             'settings' => 'betterdocs_sticky_toc_zindex_layout_8_9',
	//             'label'    => __( 'Sticky Toc z-index', 'betterdocs' ),
	//             'priority' => 134
	//         ] )
	//     );
	// }

	// public function sticky_toc_margin_top_layout_8_9() {
	//     $this->customizer->add_setting( 'betterdocs_sticky_toc_margin_top_layout_8_9', [
	//         'default'           => $this->defaults['betterdocs_sticky_toc_margin_top_layout_8_9'],
	//         'capability'        => 'edit_theme_options',
	//         'transport'         => 'postMessage',
	//         'sanitize_callback' => [$this->sanitizer, 'integer']

	//     ] );

	//     $this->customizer->add_control( new RangeValueControl(
	//         $this->customizer, 'betterdocs_sticky_toc_margin_top_layout_8_9', [
	//             'type'        => 'betterdocs-range-value',
	//             'section'     => 'betterdocs_single_docs_settings',
	//             'settings'    => 'betterdocs_sticky_toc_margin_top_layout_8_9',
	//             'label'       => __( 'Sticky Toc Margin Top', 'betterdocs' ),
	//             'priority'    => 135,
	//             'input_attrs' => [
	//                 'class'  => '',
	//                 'min'    => 0,
	//                 'max'    => 500,
	//                 'step'   => 1,
	//                 'suffix' => 'px' //optional suffix
	//             ]
	//         ] )
	//     );
	// }

	public function toc_bg_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_bg_color_layout_8_9',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_bg_color_layout_8_9'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_bg_color_layout_8_9',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 136,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_bg_color_layout_8_9'
				]
			)
		);
	}

	public function doc_single_toc_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_layout_8_9',
					'label'       => __( 'Content Area Padding', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_toc_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 137,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_toc_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_layout_8_9',
					'label'       => __( 'Content Area Margin', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_toc_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 141,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function toc_title_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_title_color_layout_8_9',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_title_color_layout_8_9'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_title_color_layout_8_9',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'priority' => 142,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_title_color_layout_8_9'
				]
			)
		);
	}

	public function toc_title_margin_layout_8_9() {
		$this->customizer->add_setting(
			'toc_title_margin_layout_8_9',
			[
				'default'           => $this->defaults['toc_title_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'toc_title_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'toc_title_margin_layout_8_9',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 142,
					'input_attrs' => [
						'id'    => 'toc_title_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'toc_title_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['toc_title_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'toc_title_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'toc_title_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 142,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'toc_title_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['toc_title_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'toc_title_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'toc_title_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 142,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'toc_title_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['toc_title_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'toc_title_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'toc_title_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 142,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'toc_title_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['toc_title_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'toc_title_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'toc_title_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 142,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function toc_title_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_title_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_toc_title_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_toc_title_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_toc_title_font_size_layout_8_9',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
					'priority'    => 143,
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

	public function toc_list_item_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_item_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_toc_list_item_color_layout_8_9'],
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_list_item_color_layout_8_9',
				[
					'label'    => __( 'List Item Color', 'betterdocs' ),
					'priority' => 144,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_list_item_color_layout_8_9'
				]
			)
		);
	}

	public function toc_list_item_hover_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_item_hover_color_layout_8_9',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_list_item_hover_color_layout_8_9'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_list_item_hover_color_layout_8_9',
				[
					'label'    => __( 'List Item Hover Color', 'betterdocs' ),
					'priority' => 145,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_list_item_hover_color_layout_8_9'
				]
			)
		);
	}

	public function toc_active_item_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_active_item_color_layout_8_9',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['betterdocs_toc_active_item_color_layout_8_9'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_toc_active_item_color_layout_8_9',
				[
					'label'    => __( 'Active Item Color', 'betterdocs' ),
					'priority' => 146,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_toc_active_item_color_layout_8_9'
				]
			)
		);
	}

	public function toc_active_item_border_color_layout_8_9() {
		$this->customizer->add_setting(
			'toc_active_item_border_color_layout_8_9',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['toc_active_item_border_color_layout_8_9'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'toc_active_item_border_color_layout_8_9',
				[
					'label'    => __( 'Active Item Border Color', 'betterdocs' ),
					'priority' => 146,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'toc_active_item_border_color_layout_8_9'
				]
			)
		);
	}

	public function toc_item_hover_bg_color_layout_10() {
		$this->customizer->add_setting(
			'toc_item_hover_bg_color_layout_10',
			[
				'capability'        => 'edit_theme_options',
				'default'           => $this->defaults['toc_item_hover_bg_color_layout_10'],
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'toc_item_hover_bg_color_layout_10',
				[
					'label'    => __( 'Active Item Background Color', 'betterdocs' ),
					'priority' => 146,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'toc_item_hover_bg_color_layout_10'
				]
			)
		);
	}

	public function toc_list_item_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_toc_list_item_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_toc_list_item_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_toc_list_item_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_toc_list_item_font_size_layout_8_9',
					'label'       => __( 'List Item Font Size', 'betterdocs' ),
					'priority'    => 147,
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

	public function doc_single_toc_list_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_layout_8_9',
					'label'       => __( 'TOC List Margin', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_toc_list_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_toc_list_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_toc_list_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_toc_list_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_toc_list_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 148,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_toc_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_author_section() {
		$this->customizer->add_setting(
			'betterdocs_doc_author_section',
			[
				'default'           => $this->defaults['betterdocs_doc_author_section'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_author_section',
				[
					'label'    => __( 'Author', 'betterdocs' ),
					'priority' => 155,
					'settings' => 'betterdocs_doc_author_section',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function doc_author_enable() {
		$this->customizer->add_setting(
			'betterdocs_doc_author_enable',
			[
				'default'           => $this->defaults['betterdocs_doc_author_enable'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_doc_author_enable',
				[
					'label'    => __( 'Enable Author Info', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_author_enable',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function doc_author_enable_layout_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_author_enable_layout_9',
			[
				'default'           => $this->defaults['betterdocs_doc_author_enable_layout_9'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_doc_author_enable_layout_9',
				[
					'label'    => __( 'Enable Author Info', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_author_enable_layout_9',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function doc_author_date() {
		$this->customizer->add_setting(
			'betterdocs_doc_author_date',
			[
				'default'           => $this->defaults['betterdocs_doc_author_date'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_doc_author_date',
				[
					'label'    => __( 'Updated Date', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_author_date',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}


	public function doc_estimate_reading_time_section() {
		$this->customizer->add_setting(
			'betterdocs_doc_estimate_reading_time_section',
			[
				'default'           => $this->defaults['betterdocs_doc_estimate_reading_time_section'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_estimate_reading_time_section',
				[
					'label'    => __( 'Estimated Reading Time', 'betterdocs' ),
					'priority' => 155,
					'settings' => 'betterdocs_doc_estimate_reading_time_section',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function doc_erstimate_reading_time_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_bg_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_est_reading_bg_color'
				]
			)
		);
	}

	public function doc_erstimate_reading_time_bg_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_bg_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_bg_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_bg_color_layout_8_9',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_est_reading_bg_color_layout_8_9'
				]
			)
		);
	}

	public function doc_estimate_reading_time_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_icon_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_icon_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_icon_color',
				[
					'label'    => __( 'Clock Icon Color', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_est_reading_icon_color'
				]
			)
		);
	}

	public function betterdocs_doc_single_content_est_reading_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_icon_font_size',
					'label'       => __( 'Clock Icon Size', 'betterdocs' ),
					'priority'    => 155,
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

	public function doc_estimate_reading_time_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_color',
				[
					'label'    => __( 'Font Color', 'betterdocs' ),
					'priority' => 155,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_est_reading_color'
				]
			)
		);
	}

	public function betterdocs_doc_single_content_est_reading_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 155,
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

	public function betterdocs_doc_single_content_est_reading_border_radius() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_border_radius',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_border_radius'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_border_radius',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_border_radius',
					'label'       => __( 'Border Radius', 'betterdocs' ),
					'input_attrs' => [
						'class'  => '',
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					],
					'priority'    => 155
				]
			)
		);
	}

	public function betterdocs_doc_single_content_est_reading_font_weight() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_font_weight',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_font_weight'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_font_weight',
				[
					'label'    => __( 'Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_content_est_reading_font_weight',
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
					'priority' => 155
				]
			)
		);
	}

	public function betterdocs_doc_single_content_est_reading_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_margin',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_content_est_reading_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_content_est_reading_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_padding',
					'label'       => __( 'Padding', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_content_est_reading_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_content_est_reading_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_content_est_reading_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_content_est_reading_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_content_est_reading_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 155,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_content_est_reading_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function doc_single_entry_content() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_entry_content',
			[
				'default'           => $this->defaults['betterdocs_doc_single_entry_content'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_entry_content',
				[
					'label'    => __( 'Entry Content', 'betterdocs' ),
					'priority' => 156,
					'settings' => 'betterdocs_doc_single_entry_content',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function single_content_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_content_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_content_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_content_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_content_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 157,
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

	public function single_content_font_color() {
		$this->customizer->add_setting(
			'betterdocs_single_content_font_color',
			[
				'default'           => $this->defaults['betterdocs_single_content_font_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_content_font_color',
				[
					'label'    => __( 'Font Color', 'betterdocs' ),
					'priority' => 158,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_content_font_color'
				]
			)
		);
	}

	public function doc_single_entry_content_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_entry_content_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_entry_content_layout_8_9'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_entry_content_layout_8_9',
				[
					'label'    => __( 'Entry Content', 'betterdocs' ),
					'priority' => 156,
					'settings' => 'betterdocs_doc_single_entry_content_layout_8_9',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function single_content_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_content_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_content_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_content_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_content_font_size_layout_8_9',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 157,
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

	public function single_content_font_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_content_font_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_content_font_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_content_font_color_layout_8_9',
				[
					'label'    => __( 'Font Color', 'betterdocs' ),
					'priority' => 158,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_content_font_color_layout_8_9'
				]
			)
		);
	}

	public function single_content_padding_layout_8_9() {
		$this->customizer->add_setting(
			'single_content_padding_layout_8_9',
			[
				'default'           => $this->defaults['single_content_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'single_content_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_padding_layout_8_9',
					'label'       => __( 'Padding', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'id'    => 'single_content_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['single_content_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'single_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['single_content_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'single_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['single_content_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['single_content_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function single_content_margin_layout_8_9() {
		$this->customizer->add_setting(
			'single_content_margin_layout_8_9',
			[
				'default'           => $this->defaults['single_content_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'single_content_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_margin_layout_8_9',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'id'    => 'single_content_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['single_content_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'single_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['single_content_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'single_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['single_content_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'single_content_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['single_content_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'single_content_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'single_content_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 158,
					'input_attrs' => [
						'class' => 'toc_title_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function social_share_title() {
		$this->customizer->add_setting(
			'betterdocs_social_share_title',
			[
				'default'           => '',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_social_share_title',
				[
					'label'    => __( 'Social Share', 'betterdocs' ),
					'priority' => 164,
					'settings' => 'betterdocs_social_share_title',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function social_share_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_social_share_title_tag',
			[
				'default'           => $this->defaults['betterdocs_social_share_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_social_share_title_tag',
				[
					'label'    => __( 'Social Share Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_social_share_title_tag',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6'
					],
					'priority' => 165
				]
			)
		);
	}

	public function post_social_share() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share',
			[
				'default'           => $this->defaults['betterdocs_post_social_share'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_social_share',
				[
					'label'    => __( 'Enable Social Sharing?', 'betterdocs' ),
					'priority' => 165,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function social_sharing_text() {
		$this->customizer->add_setting(
			'betterdocs_social_sharing_text',
			[
				'default'           => $this->defaults['betterdocs_social_sharing_text'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_social_sharing_text',
				[
					'label'    => __( 'Social Sharing Title', 'betterdocs' ),
					'priority' => 166,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_social_sharing_text',
					'type'     => 'text'
				]
			)
		);
	}

	public function post_social_share_text_color() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share_text_color',
			[
				'default'           => $this->defaults['betterdocs_post_social_share_text_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_social_share_text_color',
				[
					'label'    => __( 'Title Text Color', 'betterdocs' ),
					'priority' => 167,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share_text_color'
				]
			)
		);
	}

	public function post_social_share_text_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share_text_color_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_post_social_share_text_color_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_social_share_text_color_color_layout_8_9',
				[
					'label'    => __( 'Title Text Color', 'betterdocs' ),
					'priority' => 167,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share_text_color_color_layout_8_9'
				]
			)
		);
	}

	public function post_social_share_margin_layout_8() {
		$this->customizer->add_setting(
			'post_social_share_margin_layout_8',
			[
				'default'           => $this->defaults['post_social_share_margin_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_social_share_margin_layout_8',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_layout_8',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'id'    => 'post_social_share_margin_layout_8',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_top_layout_8',
			[
				'default'           => $this->defaults['post_social_share_margin_top_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_top_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_top_layout_8',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_right_layout_8',
			[
				'default'           => $this->defaults['post_social_share_margin_right_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_right_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_right_layout_8',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_bottom_layout_8',
			[
				'default'           => $this->defaults['post_social_share_margin_bottom_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_bottom_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_bottom_layout_8',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_left_layout_8',
			[
				'default'           => $this->defaults['post_social_share_margin_left_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_left_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_left_layout_8',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function post_social_share_margin_layout_9() {
		$this->customizer->add_setting(
			'post_social_share_margin_layout_9',
			[
				'default'           => $this->defaults['post_social_share_margin_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_social_share_margin_layout_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_layout_9',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'id'    => 'post_social_share_margin_layout_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_top_layout_9',
			[
				'default'           => $this->defaults['post_social_share_margin_top_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_top_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_top_layout_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_right_layout_9',
			[
				'default'           => $this->defaults['post_social_share_margin_right_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_right_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_right_layout_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_bottom_layout_9',
			[
				'default'           => $this->defaults['post_social_share_margin_bottom_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_bottom_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_bottom_layout_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_margin_left_layout_9',
			[
				'default'           => $this->defaults['post_social_share_margin_left_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_margin_left_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_margin_left_layout_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}


	public function post_social_share_padding_layout_8() {
		$this->customizer->add_setting(
			'post_social_share_padding_layout_8',
			[
				'default'           => $this->defaults['post_social_share_padding_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_social_share_padding_layout_8',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_layout_8',
					'label'       => __( 'Padding', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'id'    => 'post_social_share_padding_layout_8',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_top_layout_8',
			[
				'default'           => $this->defaults['post_social_share_padding_top_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_top_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_top_layout_8',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_right_layout_8',
			[
				'default'           => $this->defaults['post_social_share_padding_right_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_right_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_right_layout_8',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_bottom_layout_8',
			[
				'default'           => $this->defaults['post_social_share_padding_bottom_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_bottom_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_bottom_layout_8',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_left_layout_8',
			[
				'default'           => $this->defaults['post_social_share_padding_left_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_left_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_left_layout_8',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function post_social_share_padding_layout_9() {
		$this->customizer->add_setting(
			'post_social_share_padding_layout_9',
			[
				'default'           => $this->defaults['post_social_share_padding_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_social_share_padding_layout_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_layout_9',
					'label'       => __( 'Padding', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'id'    => 'post_social_share_padding_layout_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_top_layout_9',
			[
				'default'           => $this->defaults['post_social_share_padding_top_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_top_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_top_layout_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_right_layout_9',
			[
				'default'           => $this->defaults['post_social_share_padding_right_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_right_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_right_layout_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_bottom_layout_9',
			[
				'default'           => $this->defaults['post_social_share_padding_bottom_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_bottom_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_bottom_layout_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_social_share_padding_left_layout_9',
			[
				'default'           => $this->defaults['post_social_share_padding_left_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_social_share_padding_left_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_social_share_padding_left_layout_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 167,
					'input_attrs' => [
						'class' => 'post_social_share_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function post_social_share_facebook() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share_facebook',
			[
				'default'           => $this->defaults['betterdocs_post_social_share_facebook'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_social_share_facebook',
				[
					'label'    => __( 'Facebook Sharing', 'betterdocs' ),
					'priority' => 168,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share_facebook',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_social_share_twitter() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share_twitter',
			[
				'default'           => $this->defaults['betterdocs_post_social_share_twitter'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_social_share_twitter',
				[
					'label'    => __( 'Twitter Sharing', 'betterdocs' ),
					'priority' => 169,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share_twitter',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_social_share_linkedin() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share_linkedin',
			[
				'default'           => $this->defaults['betterdocs_post_social_share_linkedin'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_social_share_linkedin',
				[
					'label'    => __( 'Linkedin Sharing', 'betterdocs' ),
					'priority' => 170,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share_linkedin',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_social_share_pinterest() {
		$this->customizer->add_setting(
			'betterdocs_post_social_share_pinterest',
			[
				'default'           => $this->defaults['betterdocs_post_social_share_pinterest'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_social_share_pinterest',
				[
					'label'    => __( 'Pinterest Sharing', 'betterdocs' ),
					'priority' => 171,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_social_share_pinterest',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function doc_single_entry_footer() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_entry_footer',
			[
				'default'           => $this->defaults['betterdocs_doc_single_entry_footer'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_entry_footer',
				[
					'label'    => __( 'Entry Footer', 'betterdocs' ),
					'priority' => 172,
					'settings' => 'betterdocs_doc_single_entry_footer',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function single_doc_feedback_label_name_text(){
		$this->customizer->add_setting(
			'single_doc_feedback_label_name_text',
			[
				'default'           => $this->defaults['single_doc_feedback_label_name_text'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'single_doc_feedback_label_name_text',
				[
					'label'    => __( 'Label Name Text', 'betterdocs' ),
					'priority' => 173,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'single_doc_feedback_label_name_text',
					'type'     => 'text'
				]
			)
		);
	}

	public function single_doc_feedback_label_email_text(){
		$this->customizer->add_setting(
			'single_doc_feedback_label_email_text',
			[
				'default'           => $this->defaults['single_doc_feedback_label_email_text'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'single_doc_feedback_label_email_text',
				[
					'label'    => __( 'Label Email Text', 'betterdocs' ),
					'priority' => 173,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'single_doc_feedback_label_email_text',
					'type'     => 'text'
				]
			)
		);
	}

	public function single_doc_feedback_label_message_text(){
		$this->customizer->add_setting(
			'single_doc_feedback_label_message_text',
			[
				'default'           => $this->defaults['single_doc_feedback_label_message_text'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'single_doc_feedback_label_message_text',
				[
					'label'    => __( 'Label Message Text', 'betterdocs' ),
					'priority' => 173,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'single_doc_feedback_label_message_text',
					'type'     => 'text'
				]
			)
		);
	}

	public function single_doc_feedback_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_feedback_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_feedback_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_feedback_icon_font_size',
					'label'       => __( 'Feedback Icon Size', 'betterdocs' ),
					'priority'    => 173,
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

	public function single_doc_feedback_icon() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_icon',
			[
				'default'    => $this->defaults['betterdocs_single_doc_feedback_icon'],
				'capability' => 'edit_theme_options'
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_single_doc_feedback_icon',
				[
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_feedback_icon',
					'label'    => __( 'Feedback Icon', 'betterdocs' ),
					'priority' => 173
				]
			)
		);
	}

	public function single_doc_feedback_link_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_link_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_feedback_link_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_feedback_link_color',
				[
					'label'    => __( 'Feedback Link Color', 'betterdocs' ),
					'priority' => 174,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_feedback_link_color'
				]
			)
		);
	}

	public function single_doc_feedback_link_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_link_hover_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_feedback_link_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_feedback_link_hover_color',
				[
					'label'    => __( 'Feedback Link Hover Color', 'betterdocs' ),
					'priority' => 175,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_feedback_link_hover_color'
				]
			)
		);
	}

	public function single_doc_feedback_link_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_link_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_feedback_link_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_feedback_link_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_feedback_link_font_size',
					'label'       => __( 'Feedback Link Font Size', 'betterdocs' ),
					'priority'    => 175,
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

	public function feedback_form_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_feedback_form_title_tag',
			[
				'default'           => $this->defaults['betterdocs_feedback_form_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_feedback_form_title_tag',
				[
					'label'    => __( 'Feedback Form Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_feedback_form_title_tag',
					'priority' => 175,
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

	public function single_doc_feedback_title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_feedback_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_feedback_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_feedback_title_font_size',
					'label'       => __( 'Feedback Form Title Font Size', 'betterdocs' ),
					'priority'    => 175,
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

	public function single_doc_feedback_title_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_feedback_title_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_feedback_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_feedback_title_color',
				[
					'label'    => __( 'Feedback Form Title Color', 'betterdocs' ),
					'priority' => 175,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_feedback_title_color'
				]
			)
		);
	}

	public function single_doc_navigation_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_color',
				[
					'label'    => __( 'Navigation Color', 'betterdocs' ),
					'priority' => 177,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_navigation_color'
				]
			)
		);
	}

	public function single_doc_navigation_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_navigation_font_size',
					'label'       => __( 'Navigation Font Size', 'betterdocs' ),
					'priority'    => 178,
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

	public function single_doc_navigation_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_hover_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_hover_color',
				[
					'label'    => __( 'Navigation Hover Color', 'betterdocs' ),
					'priority' => 179,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_navigation_hover_color'
				]
			)
		);
	}

	public function single_doc_navigation_arrow_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_arrow_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_arrow_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_arrow_color',
				[
					'label'    => __( 'Navigation Arrow Color', 'betterdocs' ),
					'priority' => 180,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_navigation_arrow_color'
				]
			)
		);
	}

	public function single_doc_navigation_arrow_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_arrow_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_arrow_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_arrow_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_navigation_arrow_font_size',
					'label'       => __( 'Navigation Arrow Font Size', 'betterdocs' ),
					'priority'    => 181,
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

	public function single_doc_navigation_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_color_layout_8_9',
				[
					'label'    => __( 'Navigation Color', 'betterdocs' ),
					'priority' => 177,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_navigation_color_layout_8_9'
				]
			)
		);
	}

	public function single_doc_navigation_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_navigation_font_size_layout_8_9',
					'label'       => __( 'Navigation Font Size', 'betterdocs' ),
					'priority'    => 178,
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

	public function single_doc_navigation_hover_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_hover_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_hover_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_hover_color_layout_8_9',
				[
					'label'    => __( 'Navigation Hover Color', 'betterdocs' ),
					'priority' => 179,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_navigation_hover_color_layout_8_9'
				]
			)
		);
	}

	public function single_doc_navigation_arrow_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_arrow_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_arrow_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_arrow_color_layout_8_9',
				[
					'label'    => __( 'Navigation Arrow Color', 'betterdocs' ),
					'priority' => 180,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_navigation_arrow_color_layout_8_9'
				]
			)
		);
	}

	public function single_doc_navigation_arrow_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_navigation_arrow_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_single_doc_navigation_arrow_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_navigation_arrow_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_navigation_arrow_font_size_layout_8_9',
					'label'       => __( 'Navigation Arrow Font Size', 'betterdocs' ),
					'priority'    => 181,
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

	public function betterdocs_single_doc_lu_time_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_lu_time_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_lu_time_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_lu_time_color',
				[
					'label'    => __( 'Last Updated Time Color', 'betterdocs' ),
					'priority' => 182,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_lu_time_color'
				]
			)
		);
	}

	public function single_doc_lu_time_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_lu_time_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_lu_time_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_lu_time_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_lu_time_font_size',
					'label'       => __( 'Last Updated Time Font Size', 'betterdocs' ),
					'priority'    => 183,
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



	public function single_doc_powered_by_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_powered_by_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_powered_by_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_powered_by_color',
				[
					'label'    => __( 'Powered by Color', 'betterdocs' ),
					'priority' => 184,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_powered_by_color'
				]
			)
		);
	}

	public function single_doc_powered_by_font_size() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_powered_by_font_size',
			[
				'default'           => $this->defaults['betterdocs_single_doc_powered_by_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_single_doc_powered_by_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_single_doc_powered_by_font_size',
					'label'       => __( 'Powered By Font Size', 'betterdocs' ),
					'priority'    => 185,
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

	public function single_doc_powered_by_link_color() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_powered_by_link_color',
			[
				'default'           => $this->defaults['betterdocs_single_doc_powered_by_link_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_single_doc_powered_by_link_color',
				[
					'label'    => __( 'Powered By Link Color', 'betterdocs' ),
					'priority' => 186,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_single_doc_powered_by_link_color'
				]
			)
		);
	}

	public function reactions_title() {
		$this->customizer->add_setting(
			'betterdocs_reactions_title',
			[
				'default'           => '',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_reactions_title',
				[
					'label'    => __( 'Reactions', 'betterdocs' ),
					'priority' => 159,
					'settings' => 'betterdocs_reactions_title',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function reactions_title_tag() {
		$this->customizer->add_setting(
			'betterdocs_reactions_title_tag',
			[
				'default'           => $this->defaults['betterdocs_reactions_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_reactions_title_tag',
				[
					'label'    => __( 'Reactions Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_reactions_title_tag',
					'type'     => 'select',
					'choices'  => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6'
					],
					'priority' => 160
				]
			)
		);
	}

	public function post_reactions() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions',
			[
				'default'           => $this->defaults['betterdocs_post_reactions'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_reactions',
				[
					'label'    => __( 'Enable Reactions?', 'betterdocs' ),
					'priority' => 160,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_reactions_happy() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_happy',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_happy'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_reactions_happy',
				[
					'label'    => __( 'Enable Happy?', 'betterdocs' ),
					'priority' => 160,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_happy',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_reactions_happy_icon() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_happy_icon',
			[
				'default'    => $this->defaults['betterdocs_post_reactions_happy_icon'],
				'capability' => 'edit_theme_options'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_post_reactions_happy_icon',
				[
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_happy_icon',
					'label'    => __( 'Happy Icon', 'betterdocs' ),
					'priority' => 160
				]
			)
		);
	}

	public function post_reactions_normal() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_normal',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_normal'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_reactions_normal',
				[
					'label'    => __( 'Enable Normal?', 'betterdocs' ),
					'priority' => 160,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_normal',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_reactions_normal_icon() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_normal_icon',
			[
				'default'    => $this->defaults['betterdocs_post_reactions_normal_icon'],
				'capability' => 'edit_theme_options'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_post_reactions_normal_icon',
				[
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_normal_icon',
					'label'    => __( 'Normal Icon', 'betterdocs' ),
					'priority' => 160
				]
			)
		);
	}

	public function post_reactions_sad() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_sad',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_sad'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'betterdocs_post_reactions_sad',
				[
					'label'    => __( 'Enable Sad?', 'betterdocs' ),
					'priority' => 160,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_sad',
					'type'     => 'light' // light, ios, flat
				]
			)
		);
	}

	public function post_reactions_sad_icon() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_sad_icon',
			[
				'default'    => $this->defaults['betterdocs_post_reactions_sad_icon'],
				'capability' => 'edit_theme_options'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_post_reactions_sad_icon',
				[
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_sad_icon',
					'label'    => __( 'Sad Icon', 'betterdocs' ),
					'priority' => 160
				]
			)
		);
	}

	public function post_reactions_text() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_text',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_text'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_post_reactions_text',
				[
					'label'    => __( 'Reactions Title', 'betterdocs' ),
					'priority' => 161,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_text',
					'type'     => 'text'
				]
			)
		);
	}

	public function post_reactions_text_2() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_text_2',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_text_2'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_post_reactions_text_2',
				[
					'label'    => __( 'Reactions Title', 'betterdocs' ),
					'priority' => 161,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_text_2',
					'type'     => 'text'
				]
			)
		);
	}

	public function reactions_background_color() {
		$this->customizer->add_setting(
			'reactions_background_color',
			[
				'default'           => $this->defaults['reactions_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'reactions_background_color',
				[
					'label'    => __( 'Reactions Background Color', 'betterdocs' ),
					'priority' => 162,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'reactions_background_color'
				]
			)
		);
	}

	public function post_reactions_text_color() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_text_color',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_text_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_text_color',
				[
					'label'    => __( 'Reactions Text Color', 'betterdocs' ),
					'priority' => 162,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_text_color'
				]
			)
		);
	}

	public function post_reactions_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_icon_color',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_icon_color',
				[
					'label'    => __( 'Reactions Icon Background Color', 'betterdocs' ),
					'priority' => 163,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_icon_color'
				]
			)
		);
	}

	public function post_reactions_icon_svg_color() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_icon_svg_color',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_icon_svg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_icon_svg_color',
				[
					'label'    => __( 'Reactions Icon Color', 'betterdocs' ),
					'priority' => 163,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_icon_svg_color'
				]
			)
		);
	}

	public function post_reactions_icon_hover_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_icon_hover_bg_color',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_icon_hover_bg_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_icon_hover_bg_color',
				[
					'label'    => __( 'Reactions Icon Hover Background Color', 'betterdocs' ),
					'priority' => 163,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_icon_hover_bg_color'
				]
			)
		);
	}

	public function post_reactions_icon_hover_svg_color() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_icon_hover_svg_color',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_icon_hover_svg_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_icon_hover_svg_color',
				[
					'label'    => __( 'Reactions Icon Hover Color', 'betterdocs' ),
					'priority' => 163,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_icon_hover_svg_color'
				]
			)
		);
	}

	public function reactions_background_color_layout_8() {
		$this->customizer->add_setting(
			'reactions_background_color_layout_8',
			[
				'default'           => $this->defaults['reactions_background_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'reactions_background_color_layout_8',
				[
					'label'    => __( 'Reactions Background Color', 'betterdocs' ),
					'priority' => 162,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'reactions_background_color_layout_8'
				]
			)
		);
	}

	public function reactions_background_color_layout_9() {
		$this->customizer->add_setting(
			'reactions_background_color_layout_9',
			[
				'default'           => $this->defaults['reactions_background_color_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'reactions_background_color_layout_9',
				[
					'label'    => __( 'Reactions Background Color', 'betterdocs' ),
					'priority' => 162,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'reactions_background_color_layout_9'
				]
			)
		);
	}

	public function post_reactions_text_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_text_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_text_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_text_color_layout_8_9',
				[
					'label'    => __( 'Reactions Text Color', 'betterdocs' ),
					'priority' => 162,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_text_color_layout_8_9'
				]
			)
		);
	}

	public function post_reactions_icon_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_post_reactions_icon_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_post_reactions_icon_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_post_reactions_icon_color_layout_8_9',
				[
					'label'    => __( 'Reactions Icon Background Color', 'betterdocs' ),
					'priority' => 163,
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_post_reactions_icon_color_layout_8_9'
				]
			)
		);
	}

	public function post_reactions_margin_layout_8() {
		$this->customizer->add_setting(
			'post_reactions_margin_layout_8',
			[
				'default'           => $this->defaults['post_reactions_margin_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_reactions_margin_layout_8',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_layout_8',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'post_reactions_margin_layout_8',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_top_layout_8',
			[
				'default'           => $this->defaults['post_reactions_margin_top_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_top_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_top_layout_8',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_right_layout_8',
			[
				'default'           => $this->defaults['post_reactions_margin_right_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_right_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_right_layout_8',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_bottom_layout_8',
			[
				'default'           => $this->defaults['post_reactions_margin_bottom_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_bottom_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_bottom_layout_8',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_left_layout_8',
			[
				'default'           => $this->defaults['post_reactions_margin_left_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_left_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_left_layout_8',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function post_reactions_margin_layout_9() {
		$this->customizer->add_setting(
			'post_reactions_margin_layout_9',
			[
				'default'           => $this->defaults['post_reactions_margin_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_reactions_margin_layout_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_layout_9',
					'label'       => __( 'Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'post_reactions_margin_layout_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_top_layout_9',
			[
				'default'           => $this->defaults['post_reactions_margin_top_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_top_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_top_layout_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_right_layout_9',
			[
				'default'           => $this->defaults['post_reactions_margin_right_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_right_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_right_layout_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_bottom_layout_9',
			[
				'default'           => $this->defaults['post_reactions_margin_bottom_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_bottom_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_bottom_layout_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_margin_left_layout_9',
			[
				'default'           => $this->defaults['post_reactions_margin_left_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_margin_left_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_margin_left_layout_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_margin_layout_9 betterdocs-dimension'
					]
				]
			)
		);
	}


	public function post_reactions_padding_layout_8() {
		$this->customizer->add_setting(
			'post_reactions_padding_layout_8',
			[
				'default'           => $this->defaults['post_reactions_padding_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_reactions_padding_layout_8',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_layout_8',
					'label'       => __( 'Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'post_reactions_padding_layout_8',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_top_layout_8',
			[
				'default'           => $this->defaults['post_reactions_padding_top_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_top_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_top_layout_8',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_right_layout_8',
			[
				'default'           => $this->defaults['post_reactions_padding_right_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_right_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_right_layout_8',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_bottom_layout_8',
			[
				'default'           => $this->defaults['post_reactions_padding_bottom_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_bottom_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_bottom_layout_8',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_left_layout_8',
			[
				'default'           => $this->defaults['post_reactions_padding_left_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_left_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_left_layout_8',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function post_reactions_padding_layout_9() {
		$this->customizer->add_setting(
			'post_reactions_padding_layout_9',
			[
				'default'           => $this->defaults['post_reactions_padding_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_reactions_padding_layout_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_layout_9',
					'label'       => __( 'Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'post_reactions_padding_layout_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_top_layout_9',
			[
				'default'           => $this->defaults['post_reactions_padding_top_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_top_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_top_layout_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_right_layout_9',
			[
				'default'           => $this->defaults['post_reactions_padding_right_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_right_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_right_layout_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_bottom_layout_9',
			[
				'default'           => $this->defaults['post_reactions_padding_bottom_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_bottom_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_bottom_layout_8',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_padding_left_layout_9',
			[
				'default'           => $this->defaults['post_reactions_padding_left_layout_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_padding_left_layout_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_padding_left_layout_8',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_padding_layout_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function post_reactions_border_layout_8() {
		$this->customizer->add_setting(
			'post_reactions_border_layout_8',
			[
				'default'           => $this->defaults['post_reactions_border_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'post_reactions_border_layout_8',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_border_layout_8',
					'label'       => __( 'Border', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'post_reactions_border_layout_8',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_border_top_layout_8',
			[
				'default'           => $this->defaults['post_reactions_border_top_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_border_top_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_border_top_layout_8',
					'label'       => __( 'Border Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_border_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_border_right_layout_8',
			[
				'default'           => $this->defaults['post_reactions_border_right_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_border_right_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_border_right_layout_8',
					'label'       => __( 'Border Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_border_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_border_bottom_layout_8',
			[
				'default'           => $this->defaults['post_reactions_border_bottom_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_border_bottom_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_border_bottom_layout_8',
					'label'       => __( 'Border Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_border_layout_8 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'post_reactions_border_left_layout_8',
			[
				'default'           => $this->defaults['post_reactions_border_left_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'post_reactions_border_left_layout_8',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'post_reactions_border_left_layout_8',
					'label'       => __( 'Border Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'post_reactions_border_layout_8 betterdocs-dimension'
					]
				]
			)
		);
	}

	// public function post_reactions_icon_svg_color_layout_8_9() {
	//     $this->customizer->add_setting( 'betterdocs_post_reactions_icon_svg_color_layout_8_9', [
	//         'default'           => $this->defaults['betterdocs_post_reactions_icon_svg_color_layout_8_9'],
	//         'capability'        => 'edit_theme_options',
	//         'transport'         => 'postMessage',
	//         'sanitize_callback' => [$this->sanitizer, 'rgba']
	//     ] );

	//     $this->customizer->add_control(
	//         new AlphaColorControl(
	//             $this->customizer,
	//             'betterdocs_post_reactions_icon_svg_color_layout_8_9',
	//             [
	//                 'label'    => __( 'Reactions Icon Color', 'betterdocs' ),
	//                 'priority' => 163,
	//                 'section'  => 'betterdocs_single_docs_settings',
	//                 'settings' => 'betterdocs_post_reactions_icon_svg_color_layout_8_9'
	//             ]
	//         )
	//     );
	// }

	// public function post_reactions_icon_hover_bg_color_layout_8_9() {
	//     $this->customizer->add_setting( 'betterdocs_post_reactions_icon_hover_bg_color_layout_8_9', [
	//         'default'           => $this->defaults['betterdocs_post_reactions_icon_hover_bg_color_layout_8_9'],
	//         'capability'        => 'edit_theme_options',
	//         'sanitize_callback' => [$this->sanitizer, 'rgba']
	//     ] );

	//     $this->customizer->add_control(
	//         new AlphaColorControl(
	//             $this->customizer,
	//             'betterdocs_post_reactions_icon_hover_bg_color_layout_8_9',
	//             [
	//                 'label'    => __( 'Reactions Icon Hover Background Color', 'betterdocs' ),
	//                 'priority' => 163,
	//                 'section'  => 'betterdocs_single_docs_settings',
	//                 'settings' => 'betterdocs_post_reactions_icon_hover_bg_color_layout_8_9'
	//             ]
	//         )
	//     );
	// }

	// public function post_reactions_icon_hover_svg_color_layout_8_9() {
	//     $this->customizer->add_setting( 'betterdocs_post_reactions_icon_hover_svg_color_layout_8_9', [
	//         'default'           => $this->defaults['betterdocs_post_reactions_icon_hover_svg_color_layout_8_9'],
	//         'capability'        => 'edit_theme_options',
	//         'sanitize_callback' => [$this->sanitizer, 'rgba']
	//     ] );

	//     $this->customizer->add_control(
	//         new AlphaColorControl(
	//             $this->customizer,
	//             'betterdocs_post_reactions_icon_hover_svg_color_layout_8_9',
	//             [
	//                 'label'    => __( 'Reactions Icon Hover Color', 'betterdocs' ),
	//                 'priority' => 163,
	//                 'section'  => 'betterdocs_single_docs_settings',
	//                 'settings' => 'betterdocs_post_reactions_icon_hover_svg_color_layout_8_9'
	//             ]
	//         )
	//     );
	// }

	public function attachments_heading() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_attachment_heading',
			[
				'default'           => '',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_single_doc_attachment_heading',
				[
					'label'    => __( 'Attachments', 'betterdocs' ),
					'priority' => 163,
					'settings' => 'betterdocs_single_doc_attachment_heading',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_content_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_bg_color',
				[
					'label'    => __( 'Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_content_bg_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_content_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding',
					'label'       => __( 'Content Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_content_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_content_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin',
					'label'       => __( 'Content Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_content_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_label_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_color',
				[
					'label'    => __( 'Label Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_label_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_label_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding',
					'label'       => __( 'Label Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_label_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_label_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin',
					'label'       => __( 'Label Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_label_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_font_size',
					'label'       => __( 'List Font Size', 'betterdocs' ),
					'priority'    => 163,
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

	public function betterdocs_doc_single_attachment_list_font_weight() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_font_weight',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_font_weight'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_font_weight',
				[
					'label'    => __( 'List Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_font_weight',
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
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_extension_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_extension_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_extension_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_extension_color',
				[
					'label'    => __( 'Extension Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_extension_color',
					'priority' => 163
				]
			)
		);
	}
	public function betterdocs_doc_single_attachment_list_extension_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_extension_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_extension_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_extension_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_extension_font_size',
					'label'       => __( 'Extension Font Size', 'betterdocs' ),
					'priority'    => 163,
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

	public function betterdocs_doc_single_attachment_list_extension_font_weight() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_extension_font_weight',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_extension_font_weight'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_extension_font_weight',
				[
					'label'    => __( 'Extension Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_extension_font_weight',
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
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_color',
				[
					'label'    => __( 'List Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_background_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_background_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_background_color',
				[
					'label'    => __( 'List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_background_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding',
					'label'       => __( 'List Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_list_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin',
					'label'       => __( 'List Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_list_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function attachments_heading_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_single_doc_attachment_heading_layout_8_9',
			[
				'default'           => '',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_single_doc_attachment_heading_layout_8_9',
				[
					'label'    => __( 'Attachments', 'betterdocs' ),
					'priority' => 163,
					'settings' => 'betterdocs_single_doc_attachment_heading_layout_8_9',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_content_bg_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_bg_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_bg_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_bg_color_layout_8_9',
				[
					'label'    => __( 'Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_content_bg_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_content_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_layout_8_9',
					'label'       => __( 'Content Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_content_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding_layout_8_9  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_content_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_layout_8_9',
					'label'       => __( 'Content Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_content_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin_layout_8_9  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_content_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_content_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_content_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_content_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_label_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_color_layout_8_9',
				[
					'label'    => __( 'Label Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_label_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_label_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_layout_8_9',
					'label'       => __( 'Label Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_label_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding_layout_8_9  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_label_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_layout_8_9',
					'label'       => __( 'Label Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_label_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin_layout_8_9  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_label_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_label_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_label_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_label_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_label_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_font_size_layout_8_9',
					'label'       => __( 'List Font Size', 'betterdocs' ),
					'priority'    => 163,
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

	public function betterdocs_doc_single_attachment_list_font_weight_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_font_weight_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_font_weight_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_font_weight_layout_8_9',
				[
					'label'    => __( 'List Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_font_weight_layout_8_9',
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
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_extension_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_extension_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_extension_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_extension_color_layout_8_9',
				[
					'label'    => __( 'Extension Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_extension_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_extension_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_extension_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_extension_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_extension_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_extension_font_size_layout_8_9',
					'label'       => __( 'Extension Font Size', 'betterdocs' ),
					'priority'    => 163,
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

	public function betterdocs_doc_single_attachment_list_extension_font_weight_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_extension_font_weight_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_extension_font_weight_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_extension_font_weight_layout_8_9',
				[
					'label'    => __( 'Extension Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_extension_font_weight_layout_8_9',
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
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_color_layout_8_9',
				[
					'label'    => __( 'List Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_background_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_background_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_background_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_background_color_layout_8_9',
				[
					'label'    => __( 'List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_attachment_list_background_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_layout_8_9',
					'label'       => __( 'List Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_list_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding_layout_8_9  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_attachment_list_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_layout_8_9',
					'label'       => __( 'List Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_attachment_list_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin_layout_8_9  betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_attachment_list_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_attachment_list_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_attachment_list_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_attachment_list_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_attachment_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_heading() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_heading',
			[
				'default'           => 'betterdocs_doc_single_related_docs_heading',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_heading',
				[
					'label'    => __( 'Related Docs', 'betterdocs' ),
					'priority' => 163,
					'settings' => 'betterdocs_doc_single_related_docs_heading',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_content_bg_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_bg_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_bg_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_bg_color',
				[
					'label'    => __( 'Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_content_bg_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_content_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding',
					'label'       => __( 'Content Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_content_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_content_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin',
					'label'       => __( 'Content Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_content_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin_left betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_label_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_color',
				[
					'label'    => __( 'Label Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_label_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_label_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding',
					'label'       => __( 'Label Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_label_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_label_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin',
					'label'       => __( 'Label Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_label_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin_right betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_related_docs_list_font_size() {
		$this->customizer->add_setting(
			'betterdocs_doc_related_docs_list_font_size',
			[
				'default'           => $this->defaults['betterdocs_doc_related_docs_list_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_related_docs_list_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_related_docs_list_font_size',
					'label'       => __( 'List Font Size', 'betterdocs' ),
					'priority'    => 163,
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

	public function betterdocs_doc_related_docs_list_font_weight() {
		$this->customizer->add_setting(
			'betterdocs_doc_related_docs_list_font_weight',
			[
				'default'           => $this->defaults['betterdocs_doc_related_docs_list_font_weight'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_related_docs_list_font_weight',
				[
					'label'    => __( 'List Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_related_docs_list_font_weight',
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
					'priority' => 163
				]
			)
		);
	}


	public function betterdocs_doc_single_related_docs_list_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_color',
				[
					'label'    => __( 'List Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_list_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_list_background_color() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_background_color',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_background_color',
				[
					'label'    => __( 'List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_list_background_color',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_list_padding() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding',
					'label'       => __( 'List Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_list_padding',
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_list_margin() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin',
					'label'       => __( 'List Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_list_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_top',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_right',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_left',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_heading_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_heading_layout_8_9',
			[
				'default'           => 'betterdocs_doc_single_related_docs_heading_layout_8_9',
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_heading_layout_8_9',
				[
					'label'    => __( 'Related Docs', 'betterdocs' ),
					'priority' => 163,
					'settings' => 'betterdocs_doc_single_related_docs_heading_layout_8_9',
					'section'  => 'betterdocs_single_docs_settings'
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_content_bg_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_bg_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_bg_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_bg_color_layout_8_9',
				[
					'label'    => __( 'Content Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_content_bg_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_content_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_layout_8_9',
					'label'       => __( 'Content Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_content_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_content_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_layout_8_9',
					'label'       => __( 'Content Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_content_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_content_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_content_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_content_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_content_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_content_margin_left_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_label_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_color_layout_8_9',
				[
					'label'    => __( 'Label Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_label_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_label_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_layout_8_9',
					'label'       => __( 'Label Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_label_padding_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_label_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_layout_8_9',
					'label'       => __( 'Label Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_label_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin_right_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_label_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_label_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_label_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_label_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_label_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_related_docs_list_font_size_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_related_docs_list_font_size_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_related_docs_list_font_size_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_doc_related_docs_list_font_size_layout_8_9',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_related_docs_list_font_size_layout_8_9',
					'label'       => __( 'List Font Size', 'betterdocs' ),
					'priority'    => 163,
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

	public function betterdocs_doc_related_docs_list_font_weight_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_related_docs_list_font_weight_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_related_docs_list_font_weight_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'choices' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_doc_related_docs_list_font_weight_layout_8_9',
				[
					'label'    => __( 'List Font Weight', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_related_docs_list_font_weight_layout_8_9',
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
					'priority' => 163
				]
			)
		);
	}


	public function betterdocs_doc_single_related_docs_list_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_color_layout_8_9',
				[
					'label'    => __( 'List Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_list_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_list_background_color_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_background_color_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_background_color_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_background_color_layout_8_9',
				[
					'label'    => __( 'List Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_single_docs_settings',
					'settings' => 'betterdocs_doc_single_related_docs_list_background_color_layout_8_9',
					'priority' => 163
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_list_padding_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_layout_8_9',
					'label'       => __( 'List Padding', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_list_padding_layout_8_9',
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_padding_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_padding_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_padding_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_padding_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_padding betterdocs-dimension'
					]
				]
			)
		);
	}

	public function betterdocs_doc_single_related_docs_list_margin_layout_8_9() {
		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_layout_8_9',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_layout_8_9',
					'label'       => __( 'List Margin', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'id'    => 'betterdocs_doc_single_related_docs_list_margin_layout_8_9',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_top_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_top_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_top_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_top_layout_8_9',
					'label'       => __( 'Top', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_right_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_right_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_right_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_right_layout_8_9',
					'label'       => __( 'Right', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_bottom_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_bottom_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_bottom_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_bottom_layout_8_9',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_doc_single_related_docs_list_margin_left_layout_8_9',
			[
				'default'           => $this->defaults['betterdocs_doc_single_related_docs_list_margin_left_layout_8_9'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_doc_single_related_docs_list_margin_left_layout_8_9',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_single_docs_settings',
					'settings'    => 'betterdocs_doc_single_related_docs_list_margin_left_layout_8_9',
					'label'       => __( 'Left', 'betterdocs' ),
					'priority'    => 163,
					'input_attrs' => [
						'class' => 'betterdocs_doc_single_related_docs_list_margin_layout_8_9 betterdocs-dimension'
					]
				]
			)
		);
	}
}
