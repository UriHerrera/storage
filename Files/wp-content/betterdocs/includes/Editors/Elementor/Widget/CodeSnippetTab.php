<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

class CodeSnippetTab extends BaseWidget {

    public function get_name() {
        return 'betterdocs-code-snippet-tab';
    }

    public function get_title() {
        return __( 'BetterDocs Code Snippet Tab', 'betterdocs' );
    }

    public function get_categories() {
        return [ 'betterdocs-elements' ];
    }

    public function get_keywords() {
        return [ 'betterdocs-elements', 'response', 'api', 'http', 'status', 'code', 'betterdocs' ];
    }

    public function get_icon() {
        return 'betterdocs-icon-code-snippet';
    }

    public function get_style_depends() {
        return [ 'betterdocs-code-snippet-tab' ];
    }

    public function get_script_depends() {
        return [ 'betterdocs-code-snippet-tab' ];
    }

    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Responses', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $language_options = [
            'json'        => __( 'JSON', 'betterdocs' ),
            'xml'         => __( 'XML', 'betterdocs' ),
            'yaml'        => __( 'YAML', 'betterdocs' ),
            'javascript'  => __( 'JavaScript', 'betterdocs' ),
            'php'         => __( 'PHP', 'betterdocs' ),
            'python'      => __( 'Python', 'betterdocs' ),
            'bash'        => __( 'Bash', 'betterdocs' ),
            'html'        => __( 'HTML', 'betterdocs' ),
            'text'        => __( 'Plain Text', 'betterdocs' ),
        ];

        $response_repeater = new Repeater();

        $response_repeater->add_control(
            'response_status',
            [
                'label'       => __( 'Status / Response type', 'betterdocs' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '200',
                'placeholder' => __( 'e.g. 200, 404', 'betterdocs' ),
                'description' => __( 'Shown as the tab label. A colored dot is derived from the first digit (2xx green, 4xx/5xx red).', 'betterdocs' ),
            ]
        );

        $response_repeater->add_control(
            'response_language',
            [
                'label'   => __( 'Language', 'betterdocs' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'json',
                'options' => $language_options,
            ]
        );

        $response_repeater->add_control(
            'response_code',
            [
                'label'       => __( 'Response Body', 'betterdocs' ),
                'type'        => Controls_Manager::CODE,
                'language'    => 'html',
                'rows'        => 12,
                'default'     => '',
                'placeholder' => __( 'Paste the example response here…', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'responses',
            [
                'label'       => __( 'Responses', 'betterdocs' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $response_repeater->get_controls(),
                'default'     => [
                    [
                        'response_status'   => '200',
                        'response_language' => 'json',
                        'response_code'     => "{\n  \"success\": true\n}",
                    ],
                ],
                'title_field' => '{{{ response_status }}}',
                'description' => __( 'Add one entry per HTTP status. Each becomes a tab. Click Add Item for another response.', 'betterdocs' ),
            ]
        );

        $this->end_controls_section();

        // Appearance Section
        $this->start_controls_section(
            'display_options_section',
            [
                'label' => __( 'Appearance', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'theme',
            [
                'label'   => __( 'Theme', 'betterdocs' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'light',
                'options' => [
                    'light' => __( 'Light', 'betterdocs' ),
                    'dark'  => __( 'Dark', 'betterdocs' ),
                ],
                'description' => __( 'Choose light or dark styling for the response block.', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'show_header',
            [
                'label'        => __( 'Show header bar', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Toggle the status tabs + copy button header.', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'show_copy_button',
            [
                'label'        => __( 'Enable copy button', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_line_numbers',
            [
                'label'        => __( 'Show line numbers', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $this->end_controls_section();

        // Style Tab - Wrapper
        $this->start_controls_section(
            'wrapper_style_section',
            [
                'label' => __( 'Wrapper', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'wrapper_margin',
            [
                'label'      => __( 'Margin', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wrapper_padding',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'wrapper_background_color',
            [
                'label'     => __( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-wrapper' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'wrapper_border_color',
            [
                'label'     => __( 'Border Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-wrapper' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wrapper_border_radius',
            [
                'label'      => __( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-wrapper' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab - Header
        $this->start_controls_section(
            'header_style_section',
            [
                'label'     => __( 'Header', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'header_padding',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-header.betterdocs-file-preview-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'header_background_color',
            [
                'label'     => __( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-header.betterdocs-file-preview-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'tab_typography',
                'label'    => __( 'Tab Typography', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-code-snippet-tab-item',
            ]
        );

        $this->add_control(
            'copy_button_color',
            [
                'label'     => __( 'Copy Button Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-copy-button' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_copy_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab - Code Content Area
        $this->start_controls_section(
            'code_content_style_section',
            [
                'label' => __( 'Code Content Area', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'code_content_padding',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-code' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'code_content_background_color',
            [
                'label'     => __( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-code' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    public function view_params() {
        $settings = $this->get_settings_for_display();

        $responses = [];
        if ( ! empty( $settings['responses'] ) && is_array( $settings['responses'] ) ) {
            foreach ( $settings['responses'] as $response ) {
                $code = isset( $response['response_code'] ) ? (string) $response['response_code'] : '';
                if ( '' === $code ) {
                    continue;
                }
                $responses[] = [
                    'status'   => isset( $response['response_status'] ) ? (string) $response['response_status'] : '',
                    'language' => isset( $response['response_language'] ) ? (string) $response['response_language'] : 'json',
                    'code'     => $code
                ];
            }
        }

        return [
            'responses'           => $responses,
            'show_copy_button'    => $settings['show_copy_button'] === 'yes',
            'show_header'         => isset( $settings['show_header'] ) ? $settings['show_header'] === 'yes' : true,
            'show_line_numbers'   => $settings['show_line_numbers'] === 'yes',
            'theme'               => $settings['theme'],
            'widget_type'         => 'elementor'
        ];
    }

    protected function render_callback() {
        $this->views( 'widgets/code-snippet-tab' );
    }
}
