<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WP_Customize_Media_Control;
use WP_Customize_Control;
use WP_Customize_Image_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Customizer;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;

class ArchivePage extends Section {
	/**
	 * Section Priority
	 * @var int
	 */
	protected $priority = 400;

	/**
	 * Get the section id.
	 * @return string
	 */
	public function get_id() {
		return 'betterdocs_archive_page_settings';
	}

	/**
	 * Get the title of the section.
	 * @return string
	 */
	public function get_title() {
		return __( 'Category Archive', 'betterdocs' );
	}

	public function layout_select() {
		$this->customizer->add_setting(
			'betterdocs_archive_layout_select',
			[
				'default'           => $this->defaults['betterdocs_archive_layout_select'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new RadioImageControl(
				$this->customizer,
				'betterdocs_archive_layout_select',
				[
					'type'     => 'betterdocs-radio-image',
					'settings' => 'betterdocs_archive_layout_select',
					'section'  => 'betterdocs_archive_page_settings',
					'label'    => __( 'Select Category Archive Layout', 'betterdocs' ),
					'choices'  => apply_filters(
						'betterdocs_archive_layout_choices',
						[
							'layout-7' => [
								'label' => __( 'Sleek Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-7.png', true )
							],
							'layout-1' => [
								'label' => __( 'Classic Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-1.png', true )
							],
							'layout-8' => [
								'label' => __( 'Slate Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-8.png', true )
							],
							'layout-4' => [
								'label' => __( 'Abstract Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-4.png', true )
							],
							'layout-5' => [
								'label' => __( 'Modern Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-5.png', true )
							],
							'layout-2' => [
								'label' => __( 'Memphis Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-2.png', true ),
								'pro'   => true,
								'url'   => 'https://betterdocs.co/upgrade'
							],
							'layout-3' => [
								'label' => __( 'Neoclassic Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-3.png', true ),
								'pro'   => true,
								'url'   => 'https://betterdocs.co/upgrade'
							],
							'layout-6' => [
								'label' => __( 'Handbook Layout', 'betterdocs' ),
								'image' => $this->assets->icon( 'customizer/archive/layout-6.png', true ),
								'pro'   => true,
								'url'   => 'https://betterdocs.co/upgrade'
							],
						]
					)
				]
			)
		);
	}

	public function background_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_background_color',
			[
				'default'           => $this->defaults['betterdocs_archive_page_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_page_background_color',
				[
					'label'    => __( 'Page Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_page_background_color'
				]
			)
		);
	}

	public function background_image() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_background_image',
			[
				'default'    => $this->defaults['betterdocs_archive_page_background_image'],
				'capability' => 'edit_theme_options',
				'transport'  => 'postMessage'

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_archive_page_background_image',
				[
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_page_background_image',
					'label'    => __( 'Background Image', 'betterdocs' )
				]
			)
		);
	}

	public function background_properties() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_background_property',
			[
				'default'           => $this->defaults['betterdocs_archive_page_background_property'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_page_background_property',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_background_property',
					'label'       => __( 'Background Property', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_page_background_property',
						'class' => 'betterdocs-select'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_page_background_size',
			[
				'default'           => $this->defaults['betterdocs_archive_page_background_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_archive_page_background_size',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_background_size',
					'label'       => __( 'Size', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_background_property betterdocs-select'
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
			'betterdocs_archive_page_background_repeat',
			[
				'default'           => $this->defaults['betterdocs_archive_page_background_repeat'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_archive_page_background_repeat',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_background_repeat',
					'label'       => __( 'Repeat', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_background_property betterdocs-select'
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
			'betterdocs_archive_page_background_attachment',
			[
				'default'           => $this->defaults['betterdocs_archive_page_background_attachment'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_archive_page_background_attachment',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_background_attachment',
					'label'       => __( 'Attachment', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_background_property betterdocs-select'
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
			'betterdocs_archive_page_background_position',
			[
				'default'           => $this->defaults['betterdocs_archive_page_background_position'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'esc_html'

			]
		);

		$this->customizer->add_control(
			new SelectControl(
				$this->customizer,
				'betterdocs_archive_page_background_position',
				[
					'type'        => 'betterdocs-select',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_background_position',
					'label'       => __( 'Position', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_background_property betterdocs-select'
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

	public function content_area_width() {
		$this->customizer->add_setting(
			'betterdocs_archive_content_area_width',
			[
				'default'           => $this->defaults['betterdocs_archive_content_area_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_content_area_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_area_width',
					'label'       => __( 'Category Archive Width', 'betterdocs' ), //Renamed From 'Content Area Width' to 'Category Archive Width' @since betterdocs revamp version
					'input_attrs' => [
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
			'betterdocs_archive_content_area_max_width',
			[
				'default'           => $this->defaults['betterdocs_archive_content_area_max_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_content_area_max_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_area_max_width',
					'label'       => __( 'Category Archive Maximum Width', 'betterdocs' ), //Renamed From 'Content Area Maximum Width' to 'Category Archive Maximum Width' @since betterdocs revamp version
					'input_attrs' => [
						'min'    => 0,
						'max'    => 3000,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	/**
	 * Since Betterdocs Revamped Version
	 */
	public function category_archive_padding() {
		$this->customizer->add_setting(
			'category_archive_padding',
			[
				'default'           => $this->defaults['category_archive_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'category_archive_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'category_archive_padding',
					'label'       => __( 'Category Archive Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'category_archive_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_archive_padding_top',
			[
				'default'           => $this->defaults['category_archive_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_archive_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'category_archive_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_archive_padding_right',
			[
				'default'           => $this->defaults['category_archive_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_archive_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'category_archive_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_archive_padding_bottom',
			[
				'default'           => $this->defaults['category_archive_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_archive_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'category_archive_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'category_archive_padding_left',
			[
				'default'           => $this->defaults['category_archive_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'category_archive_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'category_archive_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function archive_search_wrapper() {
		$this->customizer->add_setting(
			'archive_search_wrapper',
			[
				'default'           => $this->defaults['archive_search_wrapper'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'archive_search_wrapper',
				[
					'label'    => __( 'Search', 'betterdocs' ),
					'settings' => 'archive_search_wrapper',
					'section'  => 'betterdocs_archive_page_settings'
				]
			)
		);
	}

	public function archive_search_toogle() {
		$this->customizer->add_setting(
			'archive_search_toogle',
			[
				'default'           => $this->defaults['archive_search_toogle'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'archive_search_toogle',
				[
					'label'    => __( 'Enable Search Section', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_search_toogle',
					'type'     => 'light', // light, ios, flat
				]
			)
		);
	}

	public function archive_search_width() {
		$this->customizer->add_setting(
			'archive_search_width',
			[
				'default'           => $this->defaults['archive_search_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_search_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_width',
					'label'       => __( 'Archive Search Width', 'betterdocs' ), //Renamed From 'Content Area Width' to 'Category Archive Width' @since betterdocs revamp version
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => '%' //optional suffix
					]
				]
			)
		);
	}

	public function archive_search_max_width() {
		$this->customizer->add_setting(
			'archive_search_max_width',
			[
				'default'           => $this->defaults['archive_search_max_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_search_max_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_max_width',
					'label'       => __( 'Category Search Max Width', 'betterdocs' ), //Renamed From 'Content Area Width' to 'Category Archive Width' @since betterdocs revamp version
					'input_attrs' => [
						'min'    => 0,
						'max'    => 3000,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}


	public function archive_search_margin() {
		$this->customizer->add_setting(
			'archive_search_margin',
			[
				'default'           => $this->defaults['archive_search_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_search_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_margin',
					'label'       => __( 'Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_search_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_search_margin_top',
			[
				'default'           => $this->defaults['archive_search_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_search_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_search_margin betterdocs-dimension'
					]
				]
			)
		);

		// $this->customizer->add_setting( 'archive_search_margin_right', [
		//     'default'           => $this->defaults['archive_search_margin_right'],
		//     'capability'        => 'edit_theme_options',
		//     'transport'         => 'postMessage',
		//     'sanitize_callback' => [$this->sanitizer, 'integer']

		// ] );

		// $this->customizer->add_control( new DimensionControl(
		//     $this->customizer, 'archive_search_margin_right', [
		//         'type'        => 'betterdocs-dimension',
		//         'section'     => 'betterdocs_archive_page_settings',
		//         'settings'    => 'archive_search_margin_right',
		//         'label'       => __( 'Right', 'betterdocs' ),
		//         'input_attrs' => [
		//             'class' => 'archive_search_margin betterdocs-dimension'
		//         ]
		//     ] )
		// );

		$this->customizer->add_setting(
			'archive_search_margin_bottom',
			[
				'default'           => $this->defaults['archive_search_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_search_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_search_margin betterdocs-dimension'
					]
				]
			)
		);

		// $this->customizer->add_setting( 'archive_search_margin_left', [
		//     'default'           => $this->defaults['archive_search_margin_left'],
		//     'capability'        => 'edit_theme_options',
		//     'transport'         => 'postMessage',
		//     'sanitize_callback' => [$this->sanitizer, 'integer']

		// ] );

		// $this->customizer->add_control( new DimensionControl(
		//     $this->customizer, 'archive_search_margin_left', [
		//         'type'        => 'betterdocs-dimension',
		//         'section'     => 'betterdocs_archive_page_settings',
		//         'settings'    => 'archive_search_margin_left',
		//         'label'       => __( 'Left', 'betterdocs' ),
		//         'input_attrs' => [
		//             'class' => 'archive_search_margin_left betterdocs-dimension'
		//         ]
		//     ] )
		// );
	}

	public function archive_search_padding() {
		$this->customizer->add_setting(
			'archive_search_padding',
			[
				'default'           => $this->defaults['archive_search_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_search_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_padding',
					'label'       => __( 'Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_search_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_search_padding_top',
			[
				'default'           => $this->defaults['archive_search_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_search_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_search_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_search_padding_right',
			[
				'default'           => $this->defaults['archive_search_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_search_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_search_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_search_padding_bottom',
			[
				'default'           => $this->defaults['archive_search_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_search_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_search_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_search_padding_left',
			[
				'default'           => $this->defaults['archive_search_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_search_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_search_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_search_padding_left betterdocs-dimension'
					]
				]
			)
		);
	}

	public function content_area_settings() {
		$this->customizer->add_setting(
			'betterdocs_archive_content_area_settings',
			[
				'default'           => $this->defaults['betterdocs_archive_content_area_settings'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'betterdocs_archive_content_area_settings',
				[
					'label'    => __( 'Content Area', 'betterdocs' ),
					'settings' => 'betterdocs_archive_content_area_settings',
					'section'  => 'betterdocs_archive_page_settings'
				]
			)
		);
	}

	public function archive_content_area_search_toogle() {
		$this->customizer->add_setting(
			'archive_content_area_search_toogle',
			[
				'default'           => $this->defaults['archive_content_area_search_toogle'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'checkbox' ]
			]
		);

		$this->customizer->add_control(
			new ToggleControl(
				$this->customizer,
				'archive_content_area_search_toogle',
				[
					'label'    => __( 'Enable Content Area Search', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_content_area_search_toogle',
					'type'     => 'light', // light, ios, flat
				]
			)
		);
	}

	public function content_background_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_content_background_color',
			[
				'default'           => $this->defaults['betterdocs_archive_content_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_content_background_color',
				[
					'label'    => __( 'Content Area Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_content_background_color'
				]
			)
		);
	}

	public function content_margin() {
		$this->customizer->add_setting(
			'betterdocs_archive_content_margin',
			[
				'default'           => $this->defaults['betterdocs_archive_content_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_content_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_margin',
					'label'       => __( 'Content Area Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_content_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_margin_top',
			[
				'default'           => $this->defaults['betterdocs_archive_content_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_margin_right',
			[
				'default'           => $this->defaults['betterdocs_archive_content_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_archive_content_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_margin_left',
			[
				'default'           => $this->defaults['betterdocs_archive_content_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function content_padding() {
		$this->customizer->add_setting(
			'betterdocs_archive_content_padding',
			[
				'default'           => $this->defaults['betterdocs_archive_content_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_content_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_padding',
					'label'       => __( 'Content Area Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_content_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_padding_top',
			[
				'default'           => $this->defaults['betterdocs_archive_content_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_padding_right',
			[
				'default'           => $this->defaults['betterdocs_archive_content_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_padding_bottom',
			[
				'default'           => $this->defaults['betterdocs_archive_content_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_content_padding_left',
			[
				'default'           => $this->defaults['betterdocs_archive_content_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_content_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_content_padding betterdocs-dimension'
					]
				]
			)
		);
	}


	public function content_area_padding_layout_7() {
		$this->customizer->add_setting(
			'content_area_padding_layout_7',
			[
				'default'           => $this->defaults['content_area_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'content_area_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_area_padding_layout_7',
					'label'       => __( 'Content Area Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'content_area_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'content_area_padding_top_layout_7',
			[
				'default'           => $this->defaults['content_area_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'content_area_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_area_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'content_area_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'content_area_padding_right_layout_7',
			[
				'default'           => $this->defaults['content_area_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'content_area_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_area_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'content_area_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'content_area_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['content_area_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'content_area_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_area_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'content_area_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'content_area_padding_left_layout_7',
			[
				'default'           => $this->defaults['content_area_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'content_area_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_area_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'content_area_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function content_border_radius() {
		$this->customizer->add_setting(
			'betterdocs_archive_content_border_radius',
			[
				'default'           => $this->defaults['betterdocs_archive_content_border_radius'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_content_border_radius',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_content_border_radius',
					'label'       => __( 'Archive Content Border Radius', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function content_header_layout_7() {
		$this->customizer->add_setting(
			'content_header_layout_7',
			[
				'default'           => $this->defaults['content_header_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'content_header_layout_7',
				[
					'label'    => __( 'Content Header', 'betterdocs' ),
					'settings' => 'content_header_layout_7',
					'section'  => 'betterdocs_archive_page_settings'
				]
			)
		);
	}

	public function content_header_title_tag_layout7() {
		$this->customizer->add_setting(
			'content_header_title_tag_layout7',
			[
				'default'           => $this->defaults['content_header_title_tag_layout7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'content_header_title_tag_layout7',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'content_header_title_tag_layout7',
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

	public function content_header_background_layout_7() {
		$this->customizer->add_setting(
			'content_header_background_layout_7',
			[
				'default'           => $this->defaults['content_header_background_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'content_header_background_layout_7',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'content_header_background_layout_7'
				]
			)
		);
	}

	public function content_header_background_hover_layout_7() {
		$this->customizer->add_setting(
			'content_header_background_hover_layout_7',
			[
				'default'           => $this->defaults['content_header_background_hover_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'content_header_background_hover_layout_7',
				[
					'label'    => __( 'Background Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'content_header_background_hover_layout_7'
				]
			)
		);
	}

	public function content_header_background_image_size() {
		$this->customizer->add_setting(
			'content_header_background_image_size_layout_7',
			[
				'default'           => $this->defaults['content_header_background_image_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'content_header_background_image_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_header_background_image_size_layout_7',
					'label'       => __( 'Image Size', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function content_header_background_title_font_size() {
		$this->customizer->add_setting(
			'content_header_background_title_font_size_layout_7',
			[
				'default'           => $this->defaults['content_header_background_title_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'content_header_background_title_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_header_background_title_font_size_layout_7',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function content_header_background_title_color() {
		$this->customizer->add_setting(
			'content_header_background_title_color_layout_7',
			[
				'default'           => $this->defaults['content_header_background_title_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'content_header_background_title_color_layout_7',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'content_header_background_title_color_layout_7',
				]
			)
		);
	}

	public function content_header_background_count_font_size() {
		$this->customizer->add_setting(
			'content_header_background_count_font_size_layout_7',
			[
				'default'           => $this->defaults['content_header_background_count_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'content_header_background_count_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'content_header_background_count_font_size_layout_7',
					'label'       => __( 'Count Font Size', 'betterdocs' ),
					'input_attrs' => [
						'min'    => 0,
						'max'    => 50,
						'step'   => 1,
						'suffix' => 'px'
					]
				]
			)
		);
	}

	public function content_header_background_count_color() {
		$this->customizer->add_setting(
			'content_header_background_count_color_layout_7',
			[
				'default'           => $this->defaults['content_header_background_count_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'content_header_background_count_color_layout_7',
				[
					'label'    => __( 'Count Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'content_header_background_count_color_layout_7',
				]
			)
		);
	}

	public function archive_category_column_settings() {
		$this->customizer->add_setting(
			'archive_category_column_settings_layout_7',
			[
				'default'           => $this->defaults['archive_category_column_settings_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'archive_category_column_settings_layout_7',
				[
					'label'    => __( 'Category Column Settings', 'betterdocs' ),
					'settings' => 'archive_category_column_settings_layout_7',
					'section'  => 'betterdocs_archive_page_settings'
				]
			)
		);
	}

	public function archive_column_title_tag_layout7() {
		$this->customizer->add_setting(
			'archive_column_title_tag_layout7',
			[
				'default'           => $this->defaults['archive_column_title_tag_layout7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'archive_column_title_tag_layout7',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_column_title_tag_layout7',
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

	public function archive_column_background_color_layout_7() {
		$this->customizer->add_setting(
			'archive_column_background_color_layout_7',
			[
				'default'           => $this->defaults['archive_column_background_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_column_background_color_layout_7',
				[
					'label'    => __( 'Column Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_column_background_color_layout_7',
				]
			)
		);
	}


	public function archive_column_background_color_hover_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_archive_column_hover_bg_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_column_hover_bg_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_column_hover_bg_color_layout_7',
				[
					'label'    => __( 'Column Hover Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_column_hover_bg_color_layout_7',
				]
			)
		);
	}

	public function archive_column_padding_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_column_padding_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_column_padding_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_page_column_padding_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_column_padding_layout_7',
					'label'       => __( 'Column Padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_page_column_padding_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_page_column_padding_top_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_column_padding_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_page_column_padding_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_column_padding_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_page_column_padding_right_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_column_padding_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_page_column_padding_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_column_padding_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_page_column_padding_bottom_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_column_padding_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_page_column_padding_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_column_padding_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_page_column_padding_left_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_column_padding_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_page_column_padding_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_column_padding_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_page_column_padding_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function archive_column_border_color_layout_7() {
		$this->customizer->add_setting(
			'archive_column_border_color_layout_7',
			[
				'default'           => $this->defaults['archive_column_border_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_column_border_color_layout_7',
				[
					'label'    => __( 'Column Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_column_border_color_layout_7',
				]
			)
		);
	}


	public function archive_category_icon_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_cat_icon_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_cat_icon_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_page_cat_icon_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_cat_icon_size_layout_7',
					'label'       => __( 'Icon Size', 'betterdocs' ),
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


	public function archive_category_title_font_size_layout_7() {

		$this->customizer->add_setting(
			'betterdocs_archive_page_cat_title_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_cat_title_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_page_cat_title_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_cat_title_font_size_layout_7',
					'label'       => __( 'Category Title Font Size', 'betterdocs' ),
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

	public function archive_category_title_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_cat_title_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_cat_title_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_page_cat_title_color_layout_7',
				[
					'label'    => __( 'Category Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_page_cat_title_color_layout_7',
				]
			)
		);
	}

	public function archive_category_title_color_hover_layout_7() {
		$this->customizer->add_setting(
			'archive_category_title_color_hover_layout_7',
			[
				'default'           => $this->defaults['archive_category_title_color_hover_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_category_title_color_hover_layout_7',
				[
					'label'    => __( 'Category Title Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_category_title_color_hover_layout_7',
				]
			)
		);
	}

	public function archive_category_margin_layout_7() {
		$this->customizer->add_setting(
			'archive_category_margin_layout_7',
			[
				'default'           => $this->defaults['archive_category_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_category_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_category_margin_layout_7',
					'label'       => __( 'Archive Title Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_category_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_category_margin_top_layout_7',
			[
				'default'           => $this->defaults['archive_category_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_category_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_category_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_category_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_category_margin_right_layout_7',
			[
				'default'           => $this->defaults['archive_category_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_category_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_category_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_category_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_category_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['archive_category_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_category_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_category_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_category_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_category_margin_left_layout_7',
			[
				'default'           => $this->defaults['archive_category_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_category_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_category_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_category_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function archive_page_item_count_font_size_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_item_count_font_size_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_item_count_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_page_item_count_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_page_item_count_font_size_layout_7',
					'label'       => __( 'Column Count Font Size', 'betterdocs' ),
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


	public function archive_doc_page_item_count_color_layout_7() {
		$this->customizer->add_setting(
			'archive_betterdocs_doc_page_item_count_color_layout_7',
			[
				'default'           => $this->defaults['archive_betterdocs_doc_page_item_count_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_betterdocs_doc_page_item_count_color_layout_7',
				[
					'label'    => __( 'Column Count Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_betterdocs_doc_page_item_count_color_layout_7',
				]
			)
		);
	}

	public function archive_page_item_count_hover_color_layout_7() {
		$this->customizer->add_setting(
			'betterdocs_archive_page_item_count_hover_color_layout_7',
			[
				'default'           => $this->defaults['betterdocs_archive_page_item_count_hover_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_page_item_count_hover_color_layout_7',
				[
					'label'    => __( 'Column Count Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_page_item_count_hover_color_layout_7',
				]
			)
		);
	}

	public function archive_page_item_count_margin_layout_7() {
		$this->customizer->add_setting(
			'archive_page_item_count_margin_layout_7',
			[
				'default'           => $this->defaults['archive_page_item_count_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_page_item_count_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_page_item_count_margin_layout_7',
					'label'       => __( 'Column Count Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_page_item_count_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_page_item_count_margin_top_layout_7',
			[
				'default'           => $this->defaults['archive_page_item_count_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_page_item_count_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_page_item_count_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_page_item_count_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_page_item_count_margin_right_layout_7',
			[
				'default'           => $this->defaults['archive_page_item_count_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_page_item_count_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_page_item_count_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_page_item_count_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_page_item_count_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['archive_page_item_count_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_page_item_count_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_page_item_count_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_page_item_count_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_page_item_count_margin_left_layout_7',
			[
				'default'           => $this->defaults['archive_page_item_count_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_page_item_count_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_page_item_count_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_page_item_count_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}

	public function archive_last_updated_time_layout_7() {
		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'archive_last_updated_time_layout_7',
				[
					'label'    => __( 'Last Updated Time', 'betterdocs' ),
					'settings' => 'archive_last_updated_time_layout_7',
					'section'  => 'betterdocs_archive_page_settings',
				]
			)
		);
	}

	public function archive_last_updated_time_layout_7_font_size() {
		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_font_size',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_font_size',
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

	public function archive_last_updated_time_layout_7_color() {
		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_color',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_last_updated_time_layout_7_color',
				]
			)
		);
	}

	public function archive_last_updated_time_layout_7_background_color() {
		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_background_color',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_last_updated_time_layout_7_background_color',
				]
			)
		);
	}

	public function archive_last_updated_time_layout_7_margin() {
		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_margin',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_margin',
					'label'       => __( 'Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_last_updated_time_layout_7_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_margin_top',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_margin_right',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_margin_bottom',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_margin_left',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function archive_last_updated_time_layout_7_padding() {
		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_padding',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_padding'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_padding',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_padding',
					'label'       => __( 'Count padding', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_last_updated_time_layout_7_padding',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_padding_top',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_padding_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_padding_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_padding_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_padding_right',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_padding_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_padding_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_padding_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_padding_bottom',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_padding_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_padding_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_padding_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_padding betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_last_updated_time_layout_7_padding_left',
			[
				'default'           => $this->defaults['archive_last_updated_time_layout_7_padding_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_last_updated_time_layout_7_padding_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_last_updated_time_layout_7_padding_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_last_updated_time_layout_7_padding betterdocs-dimension'
					]
				]
			)
		);
	}


	public function archive_docs_list_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_layout_7'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'archive_docs_list_layout_7',
				[
					'label'    => __( 'Archive Docs List', 'betterdocs' ),
					'settings' => 'archive_docs_list_layout_7',
					'section'  => 'betterdocs_archive_page_settings',
				]
			)
		);
	}

	public function archive_docs_list_title_tag_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_title_tag_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_tag_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'archive_docs_list_title_tag_layout_7',
				[
					'label'    => __( 'Docs List Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_list_title_tag_layout_7',
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

	public function archive_docs_list_title_color_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_title_color_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_list_title_color_layout_7',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_list_title_color_layout_7',
				]
			)
		);
	}

	public function archive_docs_list_title_hover_color_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_title_hover_color_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_hover_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_list_title_hover_color_layout_7',
				[
					'label'    => __( 'Title Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_list_title_hover_color_layout_7',
				]
			)
		);
	}

	public function archive_docs_list_title_font_size_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_title_font_size_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_list_title_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_title_font_size_layout_7',
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

	public function archive_docs_list_title_margin_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_title_margin_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_docs_list_title_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_title_margin_layout_7',
					'label'       => __( 'Title Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_docs_list_title_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_title_margin_top_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_title_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_title_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_title_margin_right_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_title_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_title_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_title_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_title_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_title_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_title_margin_left_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_title_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_title_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_title_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_title_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}


	public function archive_docs_list_icon_size_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_icon_size_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_icon_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_list_icon_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_icon_size_layout_7',
					'label'       => __( 'List Icon Size', 'betterdocs' ),
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

	public function archive_docs_list_excerpt_font_size_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_excerpt_font_size_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_font_size_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_list_excerpt_font_size_layout_7',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_excerpt_font_size_layout_7',
					'label'       => __( 'List Excerpt Font Size', 'betterdocs' ),
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

	public function archive_docs_list_excerpt_font_color_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_excerpt_font_color_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_font_color_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_list_excerpt_font_color_layout_7',
				[
					'label'    => __( 'List Excerpt Font Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_list_excerpt_font_color_layout_7',
				]
			)
		);
	}

	public function archive_docs_list_border_width_layout_8() {
		$this->customizer->add_setting(
			'archive_docs_list_border_width_layout_8',
			[
				'default'           => $this->defaults['archive_docs_list_border_width_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_list_border_width_layout_8',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_border_width_layout_8',
					'label'       => __( 'List Border Width', 'betterdocs' ),
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

	public function archive_docs_list_border_color_layout_8() {
		$this->customizer->add_setting(
			'archive_docs_list_border_color_layout_8',
			[
				'default'           => $this->defaults['archive_docs_list_border_color_layout_8'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_list_border_color_layout_8',
				[
					'label'    => __( 'List Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_list_border_color_layout_8',
				]
			)
		);
	}

	public function archive_docs_list_excerpt_margin_layout_7() {
		$this->customizer->add_setting(
			'archive_docs_list_excerpt_margin_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_margin_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'archive_docs_list_excerpt_margin_layout_7',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_excerpt_margin_layout_7',
					'label'       => __( 'List Excerpt Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'archive_docs_list_excerpt_margin_layout_7',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_excerpt_margin_top_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_margin_top_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_excerpt_margin_top_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_excerpt_margin_top_layout_7',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_excerpt_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_excerpt_margin_right_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_margin_right_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_excerpt_margin_right_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_excerpt_margin_right_layout_7',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_excerpt_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_excerpt_margin_bottom_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_margin_bottom_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_excerpt_margin_bottom_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_excerpt_margin_bottom_layout_7',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_excerpt_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'archive_docs_list_excerpt_margin_left_layout_7',
			[
				'default'           => $this->defaults['archive_docs_list_excerpt_margin_left_layout_7'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'archive_docs_list_excerpt_margin_left_layout_7',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_list_excerpt_margin_left_layout_7',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'archive_docs_list_excerpt_margin_layout_7 betterdocs-dimension'
					]
				]
			)
		);
	}



	public function title_tag() {
		$this->customizer->add_setting(
			'betterdocs_archive_title_tag',
			[
				'default'           => $this->defaults['betterdocs_archive_title_tag'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'select' ]
			]
		);

		$this->customizer->add_control(
			new WP_Customize_Control(
				$this->customizer,
				'betterdocs_archive_title_tag',
				[
					'label'    => __( 'Category Title Tag', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_title_tag',
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

	public function title_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_title_color',
			[
				'default'           => $this->defaults['betterdocs_archive_title_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_title_color',
				[
					'label'    => __( 'Title Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_title_color'
				]
			)
		);
	}

	public function title_font_size() {
		$this->customizer->add_setting(
			'betterdocs_archive_title_font_size',
			[
				'default'           => $this->defaults['betterdocs_archive_title_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_title_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_title_font_size',
					'label'       => __( 'Title Font Size', 'betterdocs' ),
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

	public function title_margin() {
		$this->customizer->add_setting(
			'betterdocs_archive_title_margin',
			[
				'default'           => $this->defaults['betterdocs_archive_title_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_title_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_title_margin',
					'label'       => __( 'Archive Title Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_title_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_title_margin_top',
			[
				'default'           => $this->defaults['betterdocs_archive_title_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_title_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_title_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_title_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_title_margin_right',
			[
				'default'           => $this->defaults['betterdocs_archive_title_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_title_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_title_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_title_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_title_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_archive_title_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_title_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_title_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_title_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_title_margin_left',
			[
				'default'           => $this->defaults['betterdocs_archive_title_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_title_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_title_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_title_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function description_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_description_color',
			[
				'default'           => $this->defaults['betterdocs_archive_description_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_description_color',
				[
					'label'    => __( 'Description Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_description_color'
				]
			)
		);
	}

	public function description_font_size() {
		$this->customizer->add_setting(
			'betterdocs_archive_description_font_size',
			[
				'default'           => $this->defaults['betterdocs_archive_description_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_description_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_description_font_size',
					'label'       => __( 'Description Font Size', 'betterdocs' ),
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

	public function description_margin() {
		$this->customizer->add_setting(
			'betterdocs_archive_description_margin',
			[
				'default'           => $this->defaults['betterdocs_archive_description_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_description_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_description_margin',
					'label'       => __( 'Archive Description Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_description_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_description_margin_top',
			[
				'default'           => $this->defaults['betterdocs_archive_description_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_description_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_description_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_description_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_description_margin_right',
			[
				'default'           => $this->defaults['betterdocs_archive_description_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_description_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_description_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_description_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_description_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_archive_description_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_description_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_description_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_description_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_description_margin_left',
			[
				'default'           => $this->defaults['betterdocs_archive_description_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_description_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_description_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_description_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function list_icon() {
		$this->customizer->add_setting(
			'betterdocs_archive_list_icon',
			[
				'default'    => $this->defaults['betterdocs_archive_list_icon'],
				'capability' => 'edit_theme_options',

			]
		);

		$this->customizer->add_control(
			new WP_Customize_Image_Control(
				$this->customizer,
				'betterdocs_archive_list_icon',
				[
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_list_icon',
					'label'    => __( 'List Icon', 'betterdocs' )
				]
			)
		);
	}

	public function list_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_list_icon_color',
			[
				'default'           => $this->defaults['betterdocs_archive_list_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_list_icon_color',
				[
					'label'    => __( 'List Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_list_icon_color'
				]
			)
		);
	}

	public function list_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_archive_list_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_archive_list_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_list_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_list_icon_font_size',
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

	public function list_item_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_list_item_color',
			[
				'default'           => $this->defaults['betterdocs_archive_list_item_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_list_item_color',
				[
					'label'    => __( 'List Item Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_list_item_color'
				]
			)
		);
	}

	public function list_item_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_list_item_hover_color',
			[
				'default'           => $this->defaults['betterdocs_archive_list_item_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_list_item_hover_color',
				[
					'label'    => __( 'List Item Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_list_item_hover_color'
				]
			)
		);
	}

	public function list_item_font_size() {
		$this->customizer->add_setting(
			'betterdocs_archive_list_item_font_size',
			[
				'default'           => $this->defaults['betterdocs_archive_list_item_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_list_item_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_list_item_font_size',
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

	public function list_margin() {
		$this->customizer->add_setting(
			'betterdocs_archive_article_list_margin',
			[
				'default'           => $this->defaults['betterdocs_archive_article_list_margin'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new TitleControl(
				$this->customizer,
				'betterdocs_archive_article_list_margin',
				[
					'type'        => 'betterdocs-title',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_article_list_margin',
					'label'       => __( 'Docs List Margin', 'betterdocs' ),
					'input_attrs' => [
						'id'    => 'betterdocs_archive_article_list_margin',
						'class' => 'betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_article_list_margin_top',
			[
				'default'           => $this->defaults['betterdocs_archive_article_list_margin_top'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_article_list_margin_top',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_article_list_margin_top',
					'label'       => __( 'Top', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_article_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_article_list_margin_right',
			[
				'default'           => $this->defaults['betterdocs_archive_article_list_margin_right'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_article_list_margin_right',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_article_list_margin_right',
					'label'       => __( 'Right', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_article_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_article_list_margin_bottom',
			[
				'default'           => $this->defaults['betterdocs_archive_article_list_margin_bottom'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_article_list_margin_bottom',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_article_list_margin_bottom',
					'label'       => __( 'Bottom', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_article_list_margin betterdocs-dimension'
					]
				]
			)
		);

		$this->customizer->add_setting(
			'betterdocs_archive_article_list_margin_left',
			[
				'default'           => $this->defaults['betterdocs_archive_article_list_margin_left'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new DimensionControl(
				$this->customizer,
				'betterdocs_archive_article_list_margin_left',
				[
					'type'        => 'betterdocs-dimension',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_article_list_margin_left',
					'label'       => __( 'Left', 'betterdocs' ),
					'input_attrs' => [
						'class' => 'betterdocs_archive_article_list_margin betterdocs-dimension'
					]
				]
			)
		);
	}

	public function article_subcategory_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_article_subcategory_color',
			[
				'default'           => $this->defaults['betterdocs_archive_article_subcategory_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_article_subcategory_color',
				[
					'label'    => __( 'Docs Subcategory Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_article_subcategory_color',
					'priority' => 44
				]
			)
		);
	}

	public function article_subcategory_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_article_subcategory_hover_color',
			[
				'default'           => $this->defaults['betterdocs_archive_article_subcategory_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_article_subcategory_hover_color',
				[
					'label'    => __( 'Docs Subcategory Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_article_subcategory_hover_color',
					'priority' => 44
				]
			)
		);
	}

	public function article_subcategory_font_size() {
		$this->customizer->add_setting(
			'betterdocs_archive_article_subcategory_font_size',
			[
				'default'           => $this->defaults['betterdocs_archive_article_subcategory_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_article_subcategory_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_article_subcategory_font_size',
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

	public function subcategory_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_subcategory_icon_color',
			[
				'default'           => $this->defaults['betterdocs_archive_subcategory_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_subcategory_icon_color',
				[
					'label'    => __( 'Subcategory Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_subcategory_icon_color',
					'priority' => 44
				]
			)
		);
	}

	public function subcategory_icon_font_size() {
		$this->customizer->add_setting(
			'betterdocs_archive_subcategory_icon_font_size',
			[
				'default'           => $this->defaults['betterdocs_archive_subcategory_icon_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'betterdocs_archive_subcategory_icon_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'betterdocs_archive_subcategory_icon_font_size',
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

	public function subcategory_article_list_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_subcategory_article_list_color',
			[
				'default'           => $this->defaults['betterdocs_archive_subcategory_article_list_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_subcategory_article_list_color',
				[
					'label'    => __( 'Subcategory Docs List Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_subcategory_article_list_color',
					'priority' => 44
				]
			)
		);
	}

	public function article_list_hover_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_subcategory_article_list_hover_color',
			[
				'default'           => $this->defaults['betterdocs_archive_subcategory_article_list_hover_color'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_subcategory_article_list_hover_color',
				[
					'label'    => __( 'Subcategory List Hover Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_subcategory_article_list_hover_color',
					'priority' => 44
				]
			)
		);
	}

	public function subcategory_article_list_icon_color() {
		$this->customizer->add_setting(
			'betterdocs_archive_subcategory_article_list_icon_color',
			[
				'default'           => $this->defaults['betterdocs_archive_subcategory_article_list_icon_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'betterdocs_archive_subcategory_article_list_icon_color',
				[
					'label'    => __( 'Subcategory List Icon Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'betterdocs_archive_subcategory_article_list_icon_color',
					'priority' => 44
				]
			)
		);
	}

	public function archive_docs_pagination() {
		$this->customizer->add_setting(
			'archive_docs_pagination',
			[
				'default'           => $this->defaults['archive_docs_pagination'],
				'sanitize_callback' => 'esc_html'
			]
		);

		$this->customizer->add_control(
			new SeparatorControl(
				$this->customizer,
				'archive_docs_pagination',
				[
					'label'    => __( 'Archive Docs Pagination', 'betterdocs' ),
					'settings' => 'archive_docs_pagination',
					'section'  => 'betterdocs_archive_page_settings',
					'priority' => 45
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_height() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_height',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_height'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]
			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_height',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_pagination_wrapper_height',
					'label'       => __( 'Height', 'betterdocs' ),
					'priority'    => 45,
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_width() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_width',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_width'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_width',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_pagination_wrapper_width',
					'label'       => __( 'Width', 'betterdocs' ),
					'priority'    => 45,
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_font_size() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_font_size',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_font_size'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'integer' ]

			]
		);

		$this->customizer->add_control(
			new RangeValueControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_font_size',
				[
					'type'        => 'betterdocs-range-value',
					'section'     => 'betterdocs_archive_page_settings',
					'settings'    => 'archive_docs_pagination_wrapper_font_size',
					'label'       => __( 'Font Size', 'betterdocs' ),
					'priority'    => 45,
					'input_attrs' => [
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px' //optional suffix
					]
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_color() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_color',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_color',
				[
					'label'    => __( 'Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_pagination_wrapper_color',
					'priority' => 45
				]
			)
		);
	}


	public function archive_docs_pagination_wrapper_active_color() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_active_color',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_active_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_active_color',
				[
					'label'    => __( 'Active Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_pagination_wrapper_active_color',
					'priority' => 45,
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_active_border_color() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_active_border_color',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_active_border_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_active_border_color',
				[
					'label'    => __( 'Active Border Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_pagination_wrapper_active_border_color',
					'priority' => 45
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_background_color_sleek() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_background_color_sleek',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_background_color_sleek'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_background_color_sleek',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_pagination_wrapper_background_color_sleek',
					'priority' => 45
				]
			)
		);
	}

	public function archive_docs_pagination_wrapper_background_color() {
		$this->customizer->add_setting(
			'archive_docs_pagination_wrapper_background_color',
			[
				'default'           => $this->defaults['archive_docs_pagination_wrapper_background_color'],
				'capability'        => 'edit_theme_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => [ $this->sanitizer, 'rgba' ]
			]
		);

		$this->customizer->add_control(
			new AlphaColorControl(
				$this->customizer,
				'archive_docs_pagination_wrapper_background_color',
				[
					'label'    => __( 'Background Color', 'betterdocs' ),
					'section'  => 'betterdocs_archive_page_settings',
					'settings' => 'archive_docs_pagination_wrapper_background_color',
					'priority' => 45
				]
			)
		);
	}
}
