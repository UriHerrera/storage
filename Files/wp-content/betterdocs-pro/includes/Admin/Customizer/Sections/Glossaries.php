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


class Glossaries extends Section
{
    /**
     * Section Priority
     * @var int
     */
    protected $priority           = 701;

    /**
     * Get the section id.
     * @return string
     */
    public function get_id()
    {
        return 'glossaries_settings';
    }

    /**
     * Get the title of the section.
     * @return string
     */
    public function get_title()
    {
        return __('Glossaries', 'betterdocs-pro');
    }


    public function glossaries_section_glossaries()
    {
        $this->customizer->add_setting('glossaries_section_glossaries', [
            'default'           => '',
            'sanitize_callback' => 'esc_html'
        ]);

        $this->customizer->add_control(new SeparatorControl(
            $this->customizer,
            'glossaries_section_glossaries',
            [
                'label'    => __('Glossaries', 'betterdocs-pro'),
                'settings' => 'glossaries_section_glossaries',
                'section'  => 'glossaries_settings',
                'priority' => 6
            ]
        ));
    }


    public function glossaries_color()
    {
        $this->customizer->add_setting('glossaries_color', [
            'default'           => $this->defaults['glossaries_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'glossaries_color',
                [
                    'label'    => __('Glossary Color', 'betterdocs-pro'),
                    'section'  => 'glossaries_settings',
                    'settings' => 'glossaries_color',
                    'priority' => 6
                ]
            )
        );
    }
    public function glossaries_hover_color()
    {
        $this->customizer->add_setting('glossaries_hover_color', [
            'default'           => $this->defaults['glossaries_hover_color'],
            'capability'        => 'edit_theme_options',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'glossaries_hover_color',
                [
                    'label'    => __('Glossary Hover Color', 'betterdocs-pro'),
                    'section'  => 'glossaries_settings',
                    'settings' => 'glossaries_hover_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function glossaries_tooltip_background_color()
    {
        $this->customizer->add_setting('glossaries_tooltip_background_color', [
            'default'           => $this->defaults['glossaries_tooltip_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'glossaries_tooltip_background_color',
                [
                    'label'    => __('Tooltip Background Color', 'betterdocs-pro'),
                    'section'  => 'glossaries_settings',
                    'settings' => 'glossaries_tooltip_background_color',
                    'priority' => 6
                ]
            )
        );
    }
    public function glossaries_tooltip_text_color()
    {
        $this->customizer->add_setting('glossaries_tooltip_text_color', [
            'default'           => $this->defaults['glossaries_tooltip_text_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'glossaries_tooltip_text_color',
                [
                    'label'    => __('Tooltip Text Color', 'betterdocs-pro'),
                    'section'  => 'glossaries_settings',
                    'settings' => 'glossaries_tooltip_text_color',
                    'priority' => 6
                ]
            )
        );
    }
    public function glossaries_tooltip_text_link_color()
    {
        $this->customizer->add_setting('glossaries_tooltip_text_link_color', [
            'default'           => $this->defaults['glossaries_tooltip_text_link_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ]);

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'glossaries_tooltip_text_link_color',
                [
                    'label'    => __('Tooltip Text Link Color', 'betterdocs-pro'),
                    'section'  => 'glossaries_settings',
                    'settings' => 'glossaries_tooltip_text_link_color',
                    'priority' => 6
                ]
            )
        );
    }

    public function glossaries_tooltip_font_size()
    {
        $this->customizer->add_setting('glossaries_tooltip_font_size', [
            'default'           => '16',
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ]);

        $this->customizer->add_control(
            new RangeValueControl(
                $this->customizer,
                'glossaries_tooltip_font_size',
                [
                    'type'        => 'betterdocs-range-value',
                    'section'     => 'glossaries_settings',
                    'settings'    => 'glossaries_tooltip_font_size',
                    'label'       => __('Tooltip Font Size', 'betterdocs-pro'),
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
}
