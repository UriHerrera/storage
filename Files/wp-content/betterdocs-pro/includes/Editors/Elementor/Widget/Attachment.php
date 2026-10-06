<?php

namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;
use WPDeveloper\Betterdocs\Editors\Elementor\BaseWidget;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Controls_Manager;


class Attachment extends BaseWidget {
    public function get_name() {
        return 'betterdocs-attachment';
    }

    public function get_icon() {
        return 'betterdocs-icon-attachment';
    }

    public function get_categories() {
        return ['betterdocs-elements-single'];
    }

    public function get_keywords() {
        return ['betterdocs-elements', 'betterdocs-attachment', 'betterdocs', 'docs', 'attachment'];
    }

    public function get_title() {
        return __( 'BetterDocs Attachment', 'betterdocs-pro' );
    }

    public function get_style_depends() {
        return ['single-doc-attachments'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_attachment_controls',
            [
                'label' => __( 'Controls', 'betterdocs-pro' )
            ]
        );

        $this->add_control(
            'attachment_label_text',
            [
                'label'   => __( 'Attachment Label', 'betterdocs-pro' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Attachments', 'betterdocs-pro' )
            ]
        );

        $this->add_control(
            'attachment_default_filename_format',
            [
                'label'   => __( 'Default FileName Format', 'betterdocs-pro' ),
                'type'    => Controls_Manager::TEXT,
                'default' => ''
            ]
        );

        $this->add_control(
            'show_attachment_size',
            [
                'label'        => __( 'Show Attachment Size', 'betterdocs-pro' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs-pro' ),
                'label_off'    => __( 'Hide', 'betterdocs-pro' ),
                'return_value' => 'true',
                'default'      => 'true'
            ]
        );

        $this->add_control(
            'show_attachment_icon',
            [
                'label'        => __( 'Show Attachment Icon', 'betterdocs-pro' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs-pro' ),
                'label_off'    => __( 'Hide', 'betterdocs-pro' ),
                'return_value' => 'true',
                'default'      => 'true'
            ]
        );

        $this->add_control(
            'open_attachment_in_new_tab',
            [
                'label'        => __( 'Open Attachment In New Tab', 'betterdocs-pro' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs-pro' ),
                'label_off'    => __( 'Hide', 'betterdocs-pro' ),
                'return_value' => 'true',
                'default'      => 'false'
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section('section_attachment_box', [
            'label' => __( 'Attachment Box', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE
        ]);

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'attachment_box_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-attachment-wrapper'
            ]
        );

        $this->add_responsive_control(
            'attachment_box_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-attachment-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'attachment_box_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-attachment-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'section_attachment_label_styles', [
            'label' => __( 'Attachment Label', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'attachment_label_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-attachment-heading'
            ]
        );

        $this->add_control(
            'attachment_label_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-attachment-heading' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'attachment_label_typography',
                'selector' => '{{WRAPPER}} .betterdocs-attachment-heading'
            ]
        );

        $this->add_responsive_control(
            'attachment_label_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-attachment-heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'attachment_label_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-attachment-heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'section_attachment_list_styles', [
            'label' => __( 'Attachment List', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'attachment_list_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .attachment-list .attachment-details'
            ]
        );

        $this->add_control(
            'attachment_list_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .attachment-list .attachment-details a .attachment-name, {{WRAPPER}} .attachment-list .attachment-details a .attachment-size' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'attachment_list_typography',
                'selector' => '{{WRAPPER}} .attachment-list .attachment-details a .attachment-name, {{WRAPPER}} .attachment-list .attachment-details a .attachment-size'
            ]
        );

        $this->add_responsive_control(
            'attachment_list_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .attachment-list .attachment-details' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'attachment_list_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .attachment-list .attachment-details' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );


        $this->end_controls_section();
    }

    protected function render_callback() {
        $this->views( 'widgets/attachment' );
    }

    public function view_params() {
        $settings = &$this->attributes;

        return [
            'attachment_heading_label'    => $settings['attachment_label_text'],
            'default_attachment_name'     => $settings['attachment_default_filename_format'],
            'show_attachment_icon'        => $settings['show_attachment_icon'] == 'true' ? true : false,
            'show_attachment_size'        => $settings['show_attachment_size'] == 'true' ? true : false,
            'show_attachments_in_new_tab' => $settings['open_attachment_in_new_tab'] == 'true' ? true : false,
            'show_attachments'            => true
        ];
    }
}
