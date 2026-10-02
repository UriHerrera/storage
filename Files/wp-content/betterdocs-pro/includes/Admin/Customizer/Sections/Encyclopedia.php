<?php

namespace WPDeveloper\BetterDocsPro\Admin\Customizer\Sections;

use WP_Customize_Control;
use WP_Customize_Image_Control;
use WP_Customize_Media_Control;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\TitleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SelectControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\DimensionControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RadioImageControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\MultiDimensionControl;


class Encyclopedia extends Section
{
    /**
     * Section Priority
     * @var int
     */
    protected $priority           = 700;

    /**
     * Get the section id.
     * @return string
     */
    public function get_id()
    {
        return 'encyclopedia_settings';
    }

    /**
     * Get the title of the section.
     * @return string
     */
    public function get_title()
    {
        return __('Encyclopedia', 'betterdocs-pro');
    }

    public function encyclopedia_section_title_page_layout()
    {
        $this->customizer->add_setting('encyclopedia_section_title_page_layout', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'encyclopedia_section_title_page_layout',
            [
                'label'    => __('Encyclopedia Page Layout', 'betterdocs-pro'),
                'settings' => 'encyclopedia_section_title_page_layout',
                'section'  => 'encyclopedia_settings',
                'priority' => 1
            ]
        ));
    }


    public function encyclopedia_doc_style()
    {
        $this->customizer->add_setting('encyclopedia_doc_style', [
            'default'           => $this->defaults['encyclopedia_doc_style'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => [$this->sanitizer, 'select']
        ]);


        $this->customizer->add_control(
            new RadioImageControl(
                $this->customizer,
                'encyclopedia_doc_style',
                [
                    'type'     => 'betterdocs-radio-image',
                    'settings' => 'encyclopedia_doc_style',
                    'section'  => 'encyclopedia_settings',
                    'label'    => __('Select Layout', 'betterdocs-pro'),
                    'priority' => 1,
                    'choices'  =>  [
                        'doc-grid' => [
                            'label' => __('Modern Layout', 'betterdocs-pro'),
                            'image' => $this->assets->icon('customizer/encyclopedia/grid.png', true)
                        ],
                        'doc-list' => [
                            'label' => __('Classic Layout', 'betterdocs-pro'),
                            'image' => $this->assets->icon('customizer/encyclopedia/list.png', true),
                            'pro'   => false
                        ],
                        'doc-list-2' => [
                            'label' => __('Retro Layout', 'betterdocs-pro'),
                            'image' => $this->assets->icon('customizer/encyclopedia/list.png', true),
                            'pro'   => false
                        ],
                    ]
                ]
            )
        );
    }

    public function encyclopedia_letter_style()
    {
        $this->customizer->add_setting('encyclopedia_letter_style', [
            'default'           => $this->defaults['encyclopedia_letter_style'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => [$this->sanitizer, 'select']
        ]);

        $this->customizer->add_control(
            new WP_Customize_Control(
                $this->customizer,
                'encyclopedia_letter_style',
                [
                    'label'    => __('Letter Appearance:', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_letter_style',
                    'type'     => 'select',
                    'choices'  => [
                        'alphabet-list-view'      => __('Simple', 'betterdocs-pro'),
                        'alphabet-big-view'       => __('Large Cap', 'betterdocs-pro'),
                        'alphabet-big-round-view' => __('Rounded Cap', 'betterdocs-pro'),
                    ],
                    'priority' => 2
                ]
            )
        );
    }

    public function encyclopedia_alphabet_list_style()
    {
        $this->customizer->add_setting('encyclopedia_alphabet_list_style', [
            'default'           => $this->defaults['encyclopedia_alphabet_list_style'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => [$this->sanitizer, 'select']
        ]);

        $this->customizer->add_control(
            new WP_Customize_Control(
                $this->customizer,
                'encyclopedia_alphabet_list_style',
                [
                    'label'    => __('Navigation Style:', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_alphabet_list_style',
                    'type'     => 'select',
                    'choices'  => [
                        'default' => __('Default', 'betterdocs-pro'),
                        'box'     => __('Box', 'betterdocs-pro'),
                        'plain'     => __('Plain', 'betterdocs-pro'),
                    ],
                    'priority' => 3
                ]
            )
        );
    }


    public function exclude_encyclopedia_dictionary_alphabets()
    {
        $this->customizer->add_setting('exclude_encyclopedia_dictionary_alphabets', [
            'default'           => $this->defaults['exclude_encyclopedia_dictionary_alphabets'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'exclude_encyclopedia_dictionary_alphabets',
                [
                    'label'    => __('Exclude Encyclopedia Letters', 'betterdocs-pro'),
                    'priority' => 3,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'exclude_encyclopedia_dictionary_alphabets',
                    'type'     => 'text'
                ]
            )
        );
    }

    public function encyclopedia_section_encyclopedia_page()
    {
        $this->customizer->add_setting('encyclopedia_section_encyclopedia_page', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'encyclopedia_section_encyclopedia_page',
            [
                'label'    => __('Encyclopedia Page', 'betterdocs-pro'),
                'settings' => 'encyclopedia_section_encyclopedia_page',
                'section'  => 'encyclopedia_settings',
                'priority' => 4
            ]
        ));
    }

    public function encyclopedia_dictionary_loadmore()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_loadmore', [
            'default'    => $this->defaults['encyclopedia_dictionary_loadmore'],
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new ToggleControl(
                $this->customizer,
                'encyclopedia_dictionary_loadmore',
                [
                    'label'    => __('Encyclopedia Load More', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_loadmore',
                    'type'     => 'light', // light, ios, flat
                    'priority' => 4
                ]
            )
        );
    }

    public function encyclopedia_dictionary_per_page()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_per_page', [
            'default'           => $this->defaults['encyclopedia_dictionary_per_page'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'encyclopedia_dictionary_per_page',
                [
                    'label'    => __('Encyclopedia Per Page', 'betterdocs-pro'),
                    'priority' => 5,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_per_page',
                    'type'     => 'text'
                ]
            )
        );
    }

    public function encyclopedia_background_color()
    {
        $this->customizer->add_setting('encyclopedia_background_color', [
            'default'           => $this->defaults['encyclopedia_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'encyclopedia_background_color',
                [
                    'label'    => __('Encyclopedia Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_background_color',
                    'priority' => 5
                ]
            )
        );
    }

    public function encyclopedia_padding()
    {
        $this->customizer->add_setting('encyclopedia_padding', [
            'default'    => $this->defaults['encyclopedia_padding'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'encyclopedia_padding',
                [
                    'label'        => __('Encyclopedia Padding', 'betterdocs-pro'),
                    'section'      => 'encyclopedia_settings',
                    'settings'     => 'encyclopedia_padding',
                    'priority'     => 5,
                    'input_fields' => [
                        'input1' => __('top', 'betterdocs-pro'),
                        'input2' => __('right', 'betterdocs-pro'),
                        'input3' => __('bottom', 'betterdocs-pro'),
                        'input4' => __('left', 'betterdocs-pro')
                    ],
                    'defaults'     => [
                        'input1' => 20,
                        'input2' => 15,
                        'input3' => 20,
                        'input4' => 15
                    ]
                ]
            )
        );
    }
    public function encyclopedia_margin()
    {
        $this->customizer->add_setting('encyclopedia_margin', [
            'default'    => $this->defaults['encyclopedia_margin'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'encyclopedia_margin',
                [
                    'label'        => __('Encyclopedia Margin', 'betterdocs-pro'),
                    'section'      => 'encyclopedia_settings',
                    'settings'     => 'encyclopedia_margin',
                    'priority'     => 5,
                    'input_fields' => [
                        'input1' => __('top', 'betterdocs-pro'),
                        'input2' => __('right', 'betterdocs-pro'),
                        'input3' => __('bottom', 'betterdocs-pro'),
                        'input4' => __('left', 'betterdocs-pro')
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
    public function encyclopedia_border_radius()
    {
        $this->customizer->add_setting('encyclopedia_border_radius', [
            'default'    => $this->defaults['encyclopedia_border_radius'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'encyclopedia_border_radius',
                [
                    'label'        => __('Encyclopedia Border Radius', 'betterdocs-pro'),
                    'section'      => 'encyclopedia_settings',
                    'settings'     => 'encyclopedia_border_radius',
                    'priority'     => 5,
                    'input_fields' => [
                        'input1' => __('top', 'betterdocs-pro'),
                        'input2' => __('right', 'betterdocs-pro'),
                        'input3' => __('bottom', 'betterdocs-pro'),
                        'input4' => __('left', 'betterdocs-pro')
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


    public function encyclopedia_item_background_color()
    {
        $this->customizer->add_setting('encyclopedia_item_background_color', [
            'default'           => $this->defaults['encyclopedia_item_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'encyclopedia_item_background_color',
                [
                    'label'    => __('Encyclopedia Item Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_item_background_color',
                    'priority' => 5
                ]
            )
        );
    }

    public function encyclopedia_item_heading_tag()
    {
        $this->customizer->add_setting('encyclopedia_item_heading_tag', [
            'default'           => $this->defaults['encyclopedia_item_heading_tag'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => [$this->sanitizer, 'choices']
        ]);

        $this->customizer->add_control(
            new WP_Customize_Control(
                $this->customizer,
                'encyclopedia_item_heading_tag',
                [
                    'label'    => __('Encyclopedia Item Title Tag', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_item_heading_tag',
                    'type'     => 'select',
                    'choices'  => [
                        'h1' => 'h1',
                        'h2' => 'h2',
                        'h3' => 'h3',
                        'h4' => 'h4',
                        'h5' => 'h5',
                        'h6' => 'h6'
                    ],
                    'priority' => 6
                ]
            )
        );
    }

    public function encyclopedia_section_individual_encyclopedia_page()
    {
        $this->customizer->add_setting('encyclopedia_section_individual_encyclopedia_page', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'encyclopedia_section_individual_encyclopedia_page',
            [
                'label'    => __('Individual Encyclopedia Page', 'betterdocs-pro'),
                'settings' => 'encyclopedia_section_individual_encyclopedia_page',
                'section'  => 'encyclopedia_settings',
                'priority' => 6
            ]
        ));
    }

    public function encyclopedia_dictionary_docs_loadmore()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_docs_loadmore', [
            'default'    => $this->defaults['encyclopedia_dictionary_docs_loadmore'],
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new ToggleControl(
                $this->customizer,
                'encyclopedia_dictionary_docs_loadmore',
                [
                    'label'    => __('Individual Encyclopedia Page Load More', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_docs_loadmore',
                    'type'     => 'light', // light, ios, flat
                    'priority' => 6
                ]
            )
        );
    }

    public function encyclopedia_dictionary_docs_per_page()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_docs_per_page', [
            'default'           => $this->defaults['encyclopedia_dictionary_docs_per_page'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'encyclopedia_dictionary_docs_per_page',
                [
                    'label'    => __('Individual Encyclopedia Page Docs Per Section', 'betterdocs-pro'),
                    'priority' => 6,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_docs_per_page',
                    'type'     => 'text'
                ]
            )
        );
    }

    public function encyclopedia_dictionary_docs_per_page_layout_3()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_docs_per_page_layout_3', [
            'default'           => $this->defaults['encyclopedia_dictionary_docs_per_page_layout_3'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'encyclopedia_dictionary_docs_per_page_layout_3',
                [
                    'label'    => __('Individual Encyclopedia Page Docs Per Section', 'betterdocs-pro'),
                    'priority' => 6,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_docs_per_page_layout_3',
                    'type'     => 'text'
                ]
            )
        );
    }


    public function encyclopedia_section_navigation_items()
    {
        $this->customizer->add_setting('encyclopedia_section_navigation_items', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'encyclopedia_section_navigation_items',
            [
                'label'    => __('Encyclopedia Navigation Items', 'betterdocs-pro'),
                'settings' => 'encyclopedia_section_navigation_items',
                'section'  => 'encyclopedia_settings',
                'priority' => 6
            ]
        ));
    }

    public function alphabets_padding()
    {
        $this->customizer->add_setting('alphabets_padding', [
            'default'    => $this->defaults['alphabets_padding'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'alphabets_padding',
                [
                    'label'        => __('Navigation Items Padding', 'betterdocs-pro'),
                    'section'      => 'encyclopedia_settings',
                    'settings'     => 'alphabets_padding',
                    'priority'     => 6,
                    'input_fields' => [
                        'input1' => __('top', 'betterdocs-pro'),
                        'input2' => __('right', 'betterdocs-pro'),
                        'input3' => __('bottom', 'betterdocs-pro'),
                        'input4' => __('left', 'betterdocs-pro')
                    ],
                    'defaults'     => [
                        'input1' => 15,
                        'input2' => 20,
                        'input3' => 15,
                        'input4' => 20
                    ]
                ]
            )
        );
    }
    public function alphabets_margin()
    {
        $this->customizer->add_setting('alphabets_margin', [
            'default'    => $this->defaults['alphabets_margin'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ]);

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'alphabets_margin',
                [
                    'label'        => __('Navigation Items Margin', 'betterdocs-pro'),
                    'section'      => 'encyclopedia_settings',
                    'settings'     => 'alphabets_margin',
                    'priority'     => 6,
                    'input_fields' => [
                        'input1' => __('top', 'betterdocs-pro'),
                        'input2' => __('right', 'betterdocs-pro'),
                        'input3' => __('bottom', 'betterdocs-pro'),
                        'input4' => __('left', 'betterdocs-pro')
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 30,
                        'input4' => 0
                    ]
                ]
            )
        );
    }

    public function alphabets_background_color()
    {
        $this->customizer->add_setting('alphabets_background_color', [
            'default'           => $this->defaults['alphabets_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_background_color',
                [
                    'label'    => __('Navigation Items Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_background_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_background_color_layout_3(){
        $this->customizer->add_setting('alphabets_background_color_layout_3', [
            'default'           => $this->defaults['alphabets_background_color_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_background_color_layout_3',
                [
                    'label'    => __('Navigation Items Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_background_color_layout_3',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_font_size()
    {
        $this->customizer->add_setting('alphabets_link_font_size', [
            'default'           => $this->defaults['alphabets_link_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'alphabets_link_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'alphabets_link_font_size',
                    'label'       => __('Navigation Items Link Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function navigation_wrapper_border_bottom_color()
    {
        $this->customizer->add_setting('navigation_wrapper_border_bottom_color', [
            'default'           => $this->defaults['navigation_wrapper_border_bottom_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'navigation_wrapper_border_bottom_color',
                [
                    'label'    => __('Navigation Wrapper Border Bottom Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'navigation_wrapper_border_bottom_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_color()
    {
        $this->customizer->add_setting('alphabets_link_color', [
            'default'           => $this->defaults['alphabets_link_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_color',
                [
                    'label'    => __('Navigation Items Link Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_disabled_color()
    {
        $this->customizer->add_setting('alphabets_link_disabled_color', [
            'default'           => $this->defaults['alphabets_link_disabled_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_disabled_color',
                [
                    'label'    => __('Navigation Items Link Color (Disabled)', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_disabled_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_bg_color()
    {
        $this->customizer->add_setting('alphabets_link_bg_color', [
            'default'           => $this->defaults['alphabets_link_bg_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_bg_color',
                [
                    'label'    => __('Navigation Items Link Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_bg_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_active_color()
    {
        $this->customizer->add_setting('alphabets_link_active_color', [
            'default'           => $this->defaults['alphabets_link_active_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_active_color',
                [
                    'label'    => __('Navigation Items Active Link Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_active_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_active_color_layout_3()
    {
        $this->customizer->add_setting('alphabets_link_active_color_layout_3', [
            'default'           => $this->defaults['alphabets_link_active_color_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_active_color_layout_3',
                [
                    'label'    => __('Navigation Items Active Link Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_active_color_layout_3',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_active_bg_color()
    {
        $this->customizer->add_setting('alphabets_link_active_bg_color', [
            'default'           => $this->defaults['alphabets_link_active_bg_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_active_bg_color',
                [
                    'label'    => __('Navigation Items Active Link Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_active_bg_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function alphabets_link_active_bg_color_layout_3()
    {
        $this->customizer->add_setting('alphabets_link_active_bg_color_layout_3', [
            'default'           => $this->defaults['alphabets_link_active_bg_color_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'alphabets_link_active_bg_color_layout_3',
                [
                    'label'    => __('Navigation Items Active Link Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'alphabets_link_active_bg_color_layout_3',
                    'priority' => 6
                ]
            )
        );
    }


    public function encyclopedia_section_section_appearance()
    {
        $this->customizer->add_setting('encyclopedia_section_section_appearance', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'encyclopedia_section_section_appearance',
            [
                'label'    => __('Individual Section Appearance', 'betterdocs-pro'),
                'settings' => 'encyclopedia_section_section_appearance',
                'section'  => 'encyclopedia_settings',
                'priority' => 6
            ]
        ));
    }

    public function start_letter_font_size()
    {
        $this->customizer->add_setting('start_letter_font_size', [
            'default'           => $this->defaults['start_letter_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'start_letter_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'start_letter_font_size',
                    'label'       => __('Individual Letter Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
                    'input_attrs' => [
                        'class'  => '',
                        'min'    => 0,
                        'max'    => 100,
                        'step'   => 1,
                        'suffix' => 'px' // optional suffix
                    ]
                ]
            )
        );
    }

    public function start_letter_font_size_layout_3()
    {
        $this->customizer->add_setting('start_letter_font_size_layout_3', [
            'default'           => $this->defaults['start_letter_font_size_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'start_letter_font_size_layout_3',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'start_letter_font_size_layout_3',
                    'label'       => __('Individual Letter Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
                    'input_attrs' => [
                        'class'  => '',
                        'min'    => 0,
                        'max'    => 100,
                        'step'   => 1,
                        'suffix' => 'px' // optional suffix
                    ]
                ]
            )
        );
    }


    public function start_letter_color()
    {
        $this->customizer->add_setting('start_letter_color', [
            'default'           => $this->defaults['start_letter_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'start_letter_color',
                [
                    'label'    => __('Individual Letter Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'start_letter_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function start_letter_color_layout_3()
    {
        $this->customizer->add_setting('start_letter_color_layout_3', [
            'default'           => $this->defaults['start_letter_color_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'start_letter_color_layout_3',
                [
                    'label'    => __('Individual Letter Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'start_letter_color_layout_3',
                    'priority' => 6
                ]
            )
        );
    }

    public function start_letter_bg_color()
    {
        $this->customizer->add_setting('start_letter_bg_color', [
            'default'           => $this->defaults['start_letter_bg_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'start_letter_bg_color',
                [
                    'label'    => __('Individual Letter Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'start_letter_bg_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function start_letter_bg_color_layout_3()
    {
        $this->customizer->add_setting('start_letter_bg_color_layout_3', [
            'default'           => $this->defaults['start_letter_bg_color_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'start_letter_bg_color_layout_3',
                [
                    'label'    => __('Individual Letter Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'start_letter_bg_color_layout_3',
                    'priority' => 6
                ]
            )
        );
    }

    public function start_letter_inner_bg_color()
    {
        $this->customizer->add_setting('start_letter_inner_bg_color', [
            'default'           => $this->defaults['start_letter_inner_bg_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'start_letter_inner_bg_color',
                [
                    'label'    => __('Individual Letter Inner Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'start_letter_inner_bg_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function item_title_color()
    {
        $this->customizer->add_setting('item_title_color', [
            'default'           => $this->defaults['item_title_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'item_title_color',
                [
                    'label'    => __('Docs Title Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'item_title_color',
                    'priority' => 6
                ]
            )
        );
    }


    public function item_title_color_layout_3()
    {
        $this->customizer->add_setting('item_title_color_layout_3', [
            'default'           => $this->defaults['item_title_color_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'item_title_color_layout_3',
                [
                    'label'    => __('Docs Title Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'item_title_color_layout_3',
                    'priority' => 6
                ]
            )
        );
    }

    public function item_title_font_size()
    {
        $this->customizer->add_setting('item_title_font_size', [
            'default'           => $this->defaults['item_title_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'item_title_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'item_title_font_size',
                    'label'       => __('Docs Title Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function item_title_font_size_layout_3()
    {
        $this->customizer->add_setting('item_title_font_size_layout_3', [
            'default'           => $this->defaults['item_title_font_size_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'item_title_font_size_layout_3',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'item_title_font_size_layout_3',
                    'label'       => __('Docs Title Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function item_excerpt_font_size()
    {
        $this->customizer->add_setting('item_excerpt_font_size', [
            'default'           => $this->defaults['item_excerpt_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'item_excerpt_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'item_excerpt_font_size',
                    'label'       => __('Docs Excerpt Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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


    public function item_excerpt_color()
    {
        $this->customizer->add_setting('item_excerpt_color', [
            'default'           => $this->defaults['item_excerpt_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'item_excerpt_color',
                [
                    'label'    => __('Docs Excerpt Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'item_excerpt_color',
                    'priority' => 6
                ]
            )
        );
    }
    public function item_excerpt_bg_color()
    {
        $this->customizer->add_setting('item_excerpt_bg_color', [
            'default'           => $this->defaults['item_excerpt_bg_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'item_excerpt_bg_color',
                [
                    'label'    => __('Docs Excerpt Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'item_excerpt_bg_color',
                    'priority' => 6
                ]
            )
        );
    }
    public function item_excerpt_border_color()
    {
        $this->customizer->add_setting('item_excerpt_border_color', [
            'default'           => $this->defaults['item_excerpt_border_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'item_excerpt_border_color',
                [
                    'label'    => __('Docs Excerpt border Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'item_excerpt_border_color',
                    'priority' => 6
                ]
            )
        );
    }


    public function item_link_font_size()
    {
        $this->customizer->add_setting('item_link_font_size', [
            'default'           => $this->defaults['item_link_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'item_link_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'item_link_font_size',
                    'label'       => __('Learn More Link Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function item_link_color()
    {
        $this->customizer->add_setting('item_link_color', [
            'default'           => $this->defaults['item_link_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'item_link_color',
                [
                    'label'    => __('Learn More Link Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'item_link_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function encyclopedia_dictionary_learn_more_text()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_learn_more_text', [
            'default'           => $this->defaults['encyclopedia_dictionary_learn_more_text'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'encyclopedia_dictionary_learn_more_text',
                [
                    'label'    => __('Learn More Text', 'betterdocs-pro'),
                    'priority' => 6,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_learn_more_text',
                    'type'     => 'text'
                ]
            )
        );
    }

    public function encyclopedia_dictionar_explore_more_text()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_explore_more_text', [
            'default'           => $this->defaults['encyclopedia_dictionary_explore_more_text'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'encyclopedia_dictionary_explore_more_text',
                [
                    'label'    => __('Explore More Text', 'betterdocs-pro'),
                    'priority' => 6,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_explore_more_text',
                    'type'     => 'text'
                ]
            )
        );
    }

    public function explore_more_font_size()
    {
        $this->customizer->add_setting('explore_more_font_size', [
            'default'           => $this->defaults['explore_more_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'explore_more_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'explore_more_font_size',
                    'label'       => __('Explore More Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function explore_more_font_size_layout_3()
    {
        $this->customizer->add_setting('explore_more_font_size_layout_3', [
            'default'           => $this->defaults['explore_more_font_size_layout_3'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'explore_more_font_size_layout_3',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'explore_more_font_size_layout_3',
                    'label'       => __('Explore More Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function explore_more_text_color()
    {
        $this->customizer->add_setting('explore_more_text_color', [
            'default'           => $this->defaults['explore_more_text_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'explore_more_text_color',
                [
                    'label'    => __('Explore More Text Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'explore_more_text_color',
                    'priority' => 6
                ]
            )
        );
    }


    public function encyclopedia_section_loadmore_button()
    {
        $this->customizer->add_setting('encyclopedia_section_loadmore_button', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'encyclopedia_section_loadmore_button',
            [
                'label'    => __('Load More Button', 'betterdocs-pro'),
                'settings' => 'encyclopedia_section_loadmore_button',
                'section'  => 'encyclopedia_settings',
                'priority' => 6
            ]
        ));
    }


    public function encyclopedia_dictionary_loadmore_button_text()
    {
        $this->customizer->add_setting('encyclopedia_dictionary_loadmore_button_text', [
            'default'           => $this->defaults['encyclopedia_dictionary_loadmore_button_text'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(
            new SelectControl(
                $this->customizer,
                'encyclopedia_dictionary_loadmore_button_text',
                [
                    'label'    => __('Load More Text', 'betterdocs-pro'),
                    'priority' => 6,
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'encyclopedia_dictionary_loadmore_button_text',
                    'type'     => 'text'
                ]
            )
        );
    }


    public function loadmore_button_text_font_size()
    {
        $this->customizer->add_setting('loadmore_button_text_font_size', [
            'default'           => $this->defaults['loadmore_button_text_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'loadmore_button_text_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'encyclopedia_settings',
                    'settings'    => 'loadmore_button_text_font_size',
                    'label'       => __('Load More Text Font Size', 'betterdocs-pro'),
                    'priority'    => 6,
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

    public function loadmore_button_text_color()
    {
        $this->customizer->add_setting('loadmore_button_text_color', [
            'default'           => $this->defaults['loadmore_button_text_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'loadmore_button_text_color',
                [
                    'label'    => __('Load More Text Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'loadmore_button_text_color',
                    'priority' => 6
                ]
            )
        );
    }


    public function loadmore_button_bg_color()
    {
        $this->customizer->add_setting('loadmore_button_bg_color', [
            'default'           => $this->defaults['loadmore_button_bg_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'loadmore_button_bg_color',
                [
                    'label'    => __('Load More Background Color', 'betterdocs-pro'),
                    'section'  => 'encyclopedia_settings',
                    'settings' => 'loadmore_button_bg_color',
                    'priority' => 6
                ]
            )
        );
    }
}
