<?php

namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

class EncyclopediaDescription extends BaseWidget {

    public function get_name() {
        return 'betterdocs-encyclopedia-description';
    }

    public function get_title() {
        return __( 'BetterDocs Encyclopedia Description', 'betterdocs-pro' );
    }

    public function get_icon() {
        return 'betterdocs- eicon-text-field';
    }

    public function get_categories() {
        return ['betterdocs-elements', 'betterdocs-encyclopedia', 'docs-archive'];
    }

    public function get_style_depends() {
        return ['betterdocs-encyclopedia'];
    }

    public function get_script_depends() {
        return ['betterdocs-encyclopedia'];
    }

    public function get_custom_help_url() {
        return 'https://betterdocs.co/#pricing';
    }

    protected function register_controls() {
        $this->start_controls_section(
            'encyclopedia_description_section_controls',
            [
                'label' => __( 'Styles', 'betterdocs-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE
            ]
        );

        $this->add_control(
            'encyclopedia_description_color_control',
            [
                'label'     => __( 'Color', 'betterdocs-pro' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .term-description p' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'label'    => __( 'Typography', 'betterdocs-pro' ),
                'name'     => 'encyclopedia_description_color_typo',
                'selector' => '{{WRAPPER}} .term-description p'
            ]
        );

        $this->end_controls_section();
    }

    public function view_params() {
        $term = get_queried_object();

        $term_id            = isset( $term->term_id ) ? $term->term_id : 0;
        $custom_description = get_term_meta( $term_id, 'glossary_term_description', true );

        $description = ! empty( $custom_description ) ? $custom_description : ( isset( $term->description ) ? $term->description : '' );

        return [
            'description' => $description
        ];
    }

    public function render_callback() {
        $this->views( 'templates/parts/encyclopedia-description' );
    }
}
