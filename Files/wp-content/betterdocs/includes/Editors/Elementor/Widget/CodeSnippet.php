<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

class CodeSnippet extends BaseWidget {

    public function get_name() {
        return 'betterdocs-code-snippet';
    }

    public function get_title() {
        return __( 'BetterDocs Code Snippet', 'betterdocs' );
    }

    public function get_categories() {
        return [ 'betterdocs-elements' ];
    }

    public function get_keywords() {
        return [ 'betterdocs-elements', 'code', 'snippet', 'syntax', 'highlight', 'programming', 'betterdocs' ];
    }

    public function get_icon() {
        return 'betterdocs-icon-code-snippet';
    }

    public function get_style_depends() {
        return [ 'betterdocs-code-snippet' ];
    }

    public function get_script_depends() {
        return [ 'betterdocs-code-snippet' ];
    }

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/configure-code-snippet-in-betterdocs/';
	}

    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Code Snippet', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'code_content',
            [
                'label'       => __( 'Code', 'betterdocs' ),
                'type'        => Controls_Manager::CODE,
                'language'    => 'html', // Default language for CodeMirror
                'rows'        => 15,
                'default'     => "// Paste or type your code here…",
                'placeholder' => __( 'Enter your code snippet here...', 'betterdocs' ),
                'description' => __( 'Enter your code snippet. Use Tab for indentation and enjoy syntax highlighting in the editor.', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'language',
            [
                'label'   => __( 'Language', 'betterdocs' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'javascript',
                'options' => [
                    'javascript'  => __( 'JavaScript', 'betterdocs' ),
                    'php'         => __( 'PHP', 'betterdocs' ),
                    'python'      => __( 'Python', 'betterdocs' ),
                    'java'        => __( 'Java', 'betterdocs' ),
                    'ruby'        => __( 'Ruby', 'betterdocs' ),
                    'curl'        => __( 'cURL', 'betterdocs' ),
                    'bash'        => __( 'Bash', 'betterdocs' ),
                    'json'        => __( 'JSON', 'betterdocs' ),
                    'yaml'        => __( 'YAML', 'betterdocs' ),
                    'html'        => __( 'HTML', 'betterdocs' ),
                    'css'         => __( 'CSS', 'betterdocs' ),
                    'sql'         => __( 'SQL', 'betterdocs' ),
                    'xml'         => __( 'XML', 'betterdocs' ),
                    'cpp'         => __( 'C++', 'betterdocs' ),
                    'csharp'      => __( 'C#', 'betterdocs' ),
                    'go'          => __( 'Go', 'betterdocs' ),
                    'rust'        => __( 'Rust', 'betterdocs' ),
                    'swift'       => __( 'Swift', 'betterdocs' ),
                    'kotlin'      => __( 'Kotlin', 'betterdocs' ),
                    'typescript'  => __( 'TypeScript', 'betterdocs' ),
                ],
                'description' => __( 'Choose language for syntax highlighting.', 'betterdocs' ),
            ]
        );

        // Additional languages → renders a Mintlify-style language dropdown.
        $variant_repeater = new Repeater();

        $variant_repeater->add_control(
            'variant_language',
            [
                'label'   => __( 'Language', 'betterdocs' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'php',
                'options' => [
                    'javascript'  => __( 'JavaScript', 'betterdocs' ),
                    'php'         => __( 'PHP', 'betterdocs' ),
                    'python'      => __( 'Python', 'betterdocs' ),
                    'java'        => __( 'Java', 'betterdocs' ),
                    'ruby'        => __( 'Ruby', 'betterdocs' ),
                    'curl'        => __( 'cURL', 'betterdocs' ),
                    'bash'        => __( 'Bash', 'betterdocs' ),
                    'json'        => __( 'JSON', 'betterdocs' ),
                    'yaml'        => __( 'YAML', 'betterdocs' ),
                    'html'        => __( 'HTML', 'betterdocs' ),
                    'css'         => __( 'CSS', 'betterdocs' ),
                    'sql'         => __( 'SQL', 'betterdocs' ),
                    'xml'         => __( 'XML', 'betterdocs' ),
                    'cpp'         => __( 'C++', 'betterdocs' ),
                    'csharp'      => __( 'C#', 'betterdocs' ),
                    'go'          => __( 'Go', 'betterdocs' ),
                    'rust'        => __( 'Rust', 'betterdocs' ),
                    'swift'       => __( 'Swift', 'betterdocs' ),
                    'kotlin'      => __( 'Kotlin', 'betterdocs' ),
                    'typescript'  => __( 'TypeScript', 'betterdocs' ),
                ],
            ]
        );

        $variant_repeater->add_control(
            'variant_code',
            [
                'label'       => __( 'Code', 'betterdocs' ),
                'type'        => Controls_Manager::CODE,
                'language'    => 'html',
                'rows'        => 10,
                'default'     => '',
                'placeholder' => __( 'Paste or type your code here…', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'code_variants',
            [
                'label'       => __( 'Additional Languages', 'betterdocs' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $variant_repeater->get_controls(),
                'default'     => [],
                'title_field' => '{{{ variant_language }}}',
                'description' => __( 'Add the same snippet in other languages. A language dropdown appears when you add one or more.', 'betterdocs' ),
            ]
        );

        $this->end_controls_section();

        // Display Options Section
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
                'description' => __( 'Choose light or dark styling for the code snippet block.', 'betterdocs' ),
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
                'description'  => __( 'Toggle the filename header and copy button.', 'betterdocs' ),
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
                'description'  => __( 'Show a one-click copy-to-clipboard icon.', 'betterdocs' ),
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
                'description'  => __( 'Display line numbers in the code block.', 'betterdocs' ),
            ]
        );

        $this->end_controls_section();

        // File Preview Header Section
        $this->start_controls_section(
            'file_preview_section',
            [
                'label' => __( 'File Preview Header', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_language_label',
            [
                'label'        => __( 'Show Language Label', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Display the programming language label in the header.', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'file_name',
            [
                'label' => __( 'File Name', 'betterdocs' ),
                'type' => Controls_Manager::TEXT,
                'default' => 'filename.js',
                'placeholder' => __( 'Enter filename with extension', 'betterdocs' ),
                'description' => __( 'Enter the filename to display in the header (e.g., hero-section.tsx)', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'show_traffic_lights',
            [
                'label' => __( 'Show window buttons', 'betterdocs' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'betterdocs' ),
                'label_off' => __( 'Hide', 'betterdocs' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __( 'Display macOS-style close/minimize/maximize circles.', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'show_file_icon',
            [
                'label' => __( 'Show language icon', 'betterdocs' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'betterdocs' ),
                'label_off' => __( 'Hide', 'betterdocs' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __( 'Display the default icon for this file type.', 'betterdocs' ),
            ]
        );

        $this->add_control(
            'file_icon',
            [
                'label' => __( 'Custom language icon', 'betterdocs' ),
                'type' => Controls_Manager::MEDIA,
                'media_types' => [ 'image' ],
                'description' => __( 'Upload a custom icon to override the default.', 'betterdocs' ),
                'condition' => [
                    'show_file_icon' => 'yes',
                ],
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
            'wrapper_border_width',
            [
                'label'      => __( 'Border Width', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-wrapper' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
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

        $this->add_control(
            'header_border_color',
            [
                'label'     => __( 'Border Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-header.betterdocs-file-preview-header' => 'border-bottom-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'header_border_width',
            [
                'label'      => __( 'Border Width', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-header.betterdocs-file-preview-header' => 'border-bottom-width: {{SIZE}}{{UNIT}}; border-bottom-style: solid;',
                ],
                'description' => __( 'Controls border bottom width', 'betterdocs' ),
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'file_name_typography',
                'label'    => __( 'File Name Typography', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-code-snippet-header .file-name-text',
            ]
        );

		$this->add_control(
            'file_name_color',
            [
                'label'     => __( 'File Name Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
				'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-header .file-name-text' => 'color: {{VALUE}};',
                ],
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

        $this->add_control(
            'copy_button_border_color',
            [
                'label'     => __( 'Copy Button Border Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-copy-button' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'show_copy_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();



        // Style Tab - Line Numbers
        $this->start_controls_section(
            'line_numbers_style_section',
            [
                'label'     => __( 'Line Numbers', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_line_numbers' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'line_numbers_padding',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-line-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'line_numbers_color',
            [
                'label'     => __( 'Line Number Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-line-numbers .line-number' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'line_numbers_background_color',
            [
                'label'     => __( 'Line Number Background', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-line-numbers' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'line_numbers_border_color',
            [
                'label'     => __( 'Border Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-code-snippet-line-numbers' => 'border-right-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'line_numbers_border_width',
            [
                'label'      => __( 'Border Width', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-code-snippet-line-numbers' => 'border-right-width: {{SIZE}}{{UNIT}}; border-right-style: solid;',
                ],
                'description' => __( 'Controls border right width', 'betterdocs' ),
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

        // Combined [primary + additional] language list — the shared template
        // renders a dropdown when it holds more than one entry.
        $variants = [];
        if ( ! empty( $settings['code_content'] ) ) {
            $variants[] = [
                'language'  => $settings['language'],
                'code'      => $settings['code_content'],
                'file_name' => isset( $settings['file_name'] ) ? $settings['file_name'] : ''
            ];
        }
        if ( ! empty( $settings['code_variants'] ) && is_array( $settings['code_variants'] ) ) {
            foreach ( $settings['code_variants'] as $variant ) {
                $code = isset( $variant['variant_code'] ) ? (string) $variant['variant_code'] : '';
                if ( '' === $code ) {
                    continue;
                }
                $variants[] = [
                    'language'  => isset( $variant['variant_language'] ) ? $variant['variant_language'] : 'javascript',
                    'code'      => $code,
                    'file_name' => ''
                ];
            }
        }

        return [
            'code_content'       => $settings['code_content'],
            'language'           => $settings['language'],
            'code_variants'      => $variants,
            'show_language_label' => $settings['show_language_label'] === 'yes',
            'show_copy_button'   => $settings['show_copy_button'] === 'yes',
            'show_header'        => isset( $settings['show_header'] ) ? $settings['show_header'] === 'yes' : true,
            'show_line_numbers'  => $settings['show_line_numbers'] === 'yes',
            'theme'              => $settings['theme'],
            'widget_type'        => 'elementor',
            // File Preview Header
            'file_name'          => $settings['file_name'],
            'show_traffic_lights' => $settings['show_traffic_lights'] === 'yes',
            'show_file_icon'     => $settings['show_file_icon'] === 'yes',
            'file_icon'          => isset( $settings['file_icon']['url'] ) ? $settings['file_icon']['url'] : '',
        ];
    }

    protected function render_callback() {
        $this->views( 'widgets/code-snippet' );
    }
}
