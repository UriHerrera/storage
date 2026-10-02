<?php
namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

class EncyclopediaBreadcrumb extends BaseWidget {

    public function get_name() {
        return 'betterdocs-encyclopedia-breadcrumb';
    }

    public function get_title() {
        return __( 'Betterdocs Encyclopedia Breadcrumbs', 'betterdocs-pro' );
    }

    public function get_icon() {
        return 'betterdocs-icon-Breadcrumbs';
    }

    public function get_keywords() {
        return ['betterdocs-elements', 'breadcrumbs', 'internal links', 'docs', 'betterdocs', 'breadcrumb', 'encyclopedia'];
    }

    public function get_custom_help_url() {
        return 'https://betterdocs.co/docs/single-doc-in-elementor';
    }

    public function get_categories() {
        return ['betterdocs-elements', 'betterdocs-elements-single', 'docs-archive'];
    }

    public function get_style_depends() {
        return ['betterdocs-breadcrumb'];
    }

    protected function register_controls() {
        $this->breadcrumb_style();
        $this->icon_style();
    }

    public function breadcrumb_style() {
        $this->start_controls_section(
            'section_betterdocs_encyclopedia_breadcrumbs_style',
            [
                'label' => __( 'Style', 'betterdocs-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE
            ]
        );

        $this->add_control(
            'active_link_color',
            [
                'label'     => __( 'Active Text Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item.current span' => 'color: {{VALUE}}'
                ]
            ]
        );

        $this->add_control(
            'active_link_color_hover',
            [
                'label'     => __( 'Active Text Hover Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item.current span:hover' => 'color: {{VALUE}}'
                ]
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label'     => __( 'Text Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item > a' => 'color: {{VALUE}}'
                ]
            ]
        );

        $this->add_control(
            'link_color_hover',
            [
                'label'     => __( 'Text Color Hover', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item > a:hover' => 'color: {{VALUE}}'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'text_typography',
                'selector' => '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item a,{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item span'
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label'     => __( 'Alignment', 'betterdocs-pro' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => __( 'Left', 'betterdocs-pro' ),
                        'icon'  => 'eicon-text-align-left'
                    ],
                    'center'     => [
                        'title' => __( 'Center', 'betterdocs-pro' ),
                        'icon'  => 'eicon-text-align-center'
                    ],
                    'flex-end'   => [
                        'title' => __( 'Right', 'betterdocs-pro' ),
                        'icon'  => 'eicon-text-align-right'
                    ]
                ],
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-list' => 'justify-content: {{VALUE}}'
                ]
            ]
        );

        $this->add_responsive_control(
            'breadcrumb_layout_1_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'breadcrumb_layout_1_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-list' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function icon_style() {
        /**
         * ----------------------------------------------------------
         * Section: Icon Style
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_betterdocs_encyclopedia_article_settings',
            [
                'label' => __( 'Icon', 'betterdocs-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE
            ]
        );

        $this->add_control(
            'breadcrumbs_icon_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .breadcrumb-delimiter' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'breadcrumbs_icon_size',
            [
                'label'      => __( 'Size', 'betterdocs-pro' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    '%' => [
                        'max'  => 100,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-breadcrumb .breadcrumb-delimiter .breadcrumb-delimiter-icon' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Column Settings'
    }

    public function view_params() {
        $doc_title      = get_the_title();
        $current_letter = strtoupper( mb_substr( get_the_title(), 0, 1 ) );
        $doc_title      = get_the_title();
        if ( is_tax( 'glossaries' ) ) {
            $term           = get_queried_object();
            $doc_title      = $term->name;
            $current_letter = strtoupper( mb_substr( $doc_title, 0, 1 ) );
        }
        $encyclopdeia_page_title = betterdocs()->settings->get( 'encyclopedia_page_title', 'Encyclopedia' );
        $slug                    = get_option( 'encyclopedia_current_page_slug' );
        $encyclopdeia_title      = home_url() . '/' . $slug . '/';
        $encyclopdeia_url        = home_url() . '/' . $slug . '/';
        $current_letter_url      = home_url() . '/' . $slug . '/?encyclopedia_prefix=' . $current_letter;
        return [
            'doc_title'               => $doc_title,
            'encyclopdeia_page_title' => $encyclopdeia_page_title,
            'slug'                    => $slug,
            'encyclopdeia_title'      => $encyclopdeia_title,
            'encyclopdeia_url'        => $encyclopdeia_url,
            'current_letter_url'      => $current_letter_url,
            'current_letter'          => $current_letter
        ];
    }

    protected function render_callback() {
        $this->views( 'templates/parts/encyclopedia-breadcrumb' );
    }

    public function render_plain_content() {}
}
