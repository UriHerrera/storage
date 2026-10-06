<?php

namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use WPDeveloper\Betterdocs\Editors\Elementor\BaseWidget;

class RelatedDocs extends BaseWidget {

    public function get_name() {
        return 'betterdocs-related-docs';
    }
    public function get_icon() {
        return 'betterdocs-icon-related-docs';
    }

    public function get_categories() {
        return ['betterdocs-elements-single'];
    }

    public function get_keywords() {
        return ['betterdocs-elements', 'related', 'betterdocs', 'docs', 'related-docs'];
    }

    public function get_title() {
        return __( 'BetterDocs Related Docs', 'betterdocs-pro' );
    }

    public function get_style_depends() {
        return ['single-doc-related-articles'];
    }

    protected function register_controls() {
        $this->general_controls();
        $this->wrapper_box_layout_1();
        $this->heading_layout_1();
        $this->list_layout_1();

        $this->wrapper_box_layout_2();
        $this->heading_layout_2();
        $this->list_layout_2();


    }

    public function general_controls() {
        $this->start_controls_section(
            'section_related_docs_controls',
            [
                'label' => __( 'Controls', 'betterdocs-pro' )
            ]
        );

        $this->add_control(
            'section_related_docs_select_layout',
            [
                'label'       => esc_html__( 'Select layout', 'betterdocs-pro' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'layout-1',
                'label_block' => false,
                'options'     => [
                    'layout-1' => esc_html__( 'Layout 1', 'betterdocs-pro' ),
                    'layout-2' => esc_html__( 'Layout 2', 'betterdocs-pro' )
                ]
            ]
        );

        $this->add_control(
            'related_docs_heading',
            [
                'label'   => __( 'Heading', 'betterdocs-pro' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Related Docs', 'betterdocs-pro' )
            ]
        );

        $this->end_controls_section();
    }

    public function wrapper_box_layout_1() {
        $this->start_controls_section( 'section_related_docs_box', [
            'label' => __( 'Wrapper Box', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE,
            'condition' => [
                'section_related_docs_select_layout' => ['layout-1']
            ]
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'related_docs_box_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front'
            ]
        );

        $this->add_responsive_control(
            'related_docs_box_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'related_docs_box_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function heading_layout_1() {
        $this->start_controls_section(
            'section_related_docs_heading',
            [
                'label' => __( "Heading", "betterdocs-pro" ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'section_related_docs_select_layout' => ['layout-1']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'section_related_docs_background_heading',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .related-articles-title'
            ]
        );

        $this->add_control(
            'section_related_docs_heading_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .related-articles-title' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_related_docs_heading_typography',
                'selector' => '{{WRAPPER}} .related-articles-title'
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_heading_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_heading_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function list_layout_1() {
        $this->start_controls_section( 'section_related_docs_list', [
            'label' => __( 'List', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE,
            'condition' => [
                'section_related_docs_select_layout' => ['layout-1']
            ]
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'section_related_docs_list_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .related-articles-list li'
            ]
        );

        $this->add_control(
            'section_related_docs_list_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .related-articles-list li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_related_docs_list_typography',
                'selector' => '{{WRAPPER}} .related-articles-list li a'
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_list_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-list li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_list_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function wrapper_box_layout_2() {
        $this->start_controls_section( 'section_related_docs_box_layout_2', [
            'label' => __( 'Wrapper Box', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE,
            'condition' => [
                'section_related_docs_select_layout' => ['layout-2']
            ]
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'related_docs_box_background_layout_2',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2'
            ]
        );

        $this->add_responsive_control(
            'related_docs_box_padding_layout_2',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'related_docs_box_margin_layout_2',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function heading_layout_2() {
        $this->start_controls_section(
            'section_related_docs_heading_layout_2',
            [
                'label' => __( "Heading", "betterdocs-pro" ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'section_related_docs_select_layout' => ['layout-2']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'section_related_docs_background_heading_layout_2',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-title'
            ]
        );

        $this->add_control(
            'section_related_docs_heading_color_layout_2',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-title' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_related_docs_heading_typography_layout_2',
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-title'
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_heading_padding_layout_2',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_heading_margin_layout_2',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function list_layout_2() {
        $this->start_controls_section( 'section_related_docs_list_layout_2', [
            'label' => __( 'List', 'betterdocs-pro' ),
            'tab'   => Controls_Manager::TAB_STYLE,
            'condition' => [
                'section_related_docs_select_layout' => ['layout-2']
            ]
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'section_related_docs_list_background_layout_2',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-list li'
            ]
        );

        $this->add_control(
            'section_related_docs_list_color_layout_2',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-list li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_related_docs_list_typography_layout_2',
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-list li a'
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_list_padding_layout_2',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-list li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_list_margin_layout_2',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front.layout-2 .related-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function view_params() {
        $layout = $this->attributes['section_related_docs_select_layout'];
        $settings = &$this->attributes;

        return [
            'layout' => $layout,
            'heading' => $settings['related_docs_heading']
        ];
    }

    protected function render_callback() {
        $this->views( 'widgets/related-docs' );
    }
}
