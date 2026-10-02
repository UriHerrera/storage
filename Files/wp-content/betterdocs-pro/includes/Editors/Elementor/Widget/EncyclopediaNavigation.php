<?php

namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;

// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;

class EncyclopediaNavigation extends BaseWidget {

    public function get_name() {
        return 'betterdocs-encyclopedia-navigation';
    }

    public function get_title() {
        return __( 'Encyclopedia Navigation', 'betterdocs-pro' );
    }

    public function get_icon() {
        return 'betterdocs-icon-Navigation';
    }

    public function get_categories() {
        return ['betterdocs-elements', 'docs-archive'];
    }

    public function get_keywords() {
        return ['betterdocs-elements', 'encyclopedia', 'betterdocs', 'docs', 'betterdocs-encyclopedia-navigation'];
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
        /**
         * Query Popular Articles
         */
        $this->start_controls_section(
            'encyclopedia_section_controls',
            [
                'label' => __( 'Encyclopedia Controls', 'betterdocs-pro' )
            ]
        );

        $this->add_control(
            'encyclopedia_alphabet_list_style',
            [
                'label'    => __( 'Navigation Style', 'betterdocs-pro' ),
                'type'     => \Elementor\Controls_Manager::SELECT,
                'options'  => [
                    'box'     => __( 'Box', 'betterdocs-pro' ),
                    'default' => __( 'Default', 'betterdocs-pro' )
                ],
                'default'  => 'box',
                'priority' => 16
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'alphabets_style_control_sections',
            [
                'label' => __( 'Navigation', 'betterdocs-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE
            ]
        );

        // Main Container Heading
        $this->add_control(
            'alphabets_container_heading',
            [
                'label'     => __( 'Navigation Container', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        // Main Container Background Color Controls
        $this->add_control(
            'alphabets_container_background_color',
            [
                'label'     => __( 'Background Color', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .encyclopedia-alphabet-list,{{WRAPPER}} .encyclopedia-alphabets' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        // Main Container Padding Controls
        $this->add_control(
            'alphabets_container_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .encyclopedia-alphabets' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        // Main Container Margin Controls
        $this->add_control(
            'alphabets_container_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .encyclopedia-alphabets' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        // Main Container Heading
        $this->add_control(
            'alphabets_link_heading',
            [
                'label'     => __( 'Navigation Link', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        // Typography Controls for Alphabet Links
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'alphabet_typography',
                'selector' => '{{WRAPPER}} .encyclopedia-alphabet-list a'

            ]
        );

        // Color Controls for Alphabet Links
        $this->add_control(
            'alphabet_color',
            [
                'label'     => __( 'Color', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .encyclopedia-alphabet-list a' => 'color: {{VALUE}};'
                ]
            ]
        );
        $this->add_control(
            'alphabet_acitve_color',
            [
                'label'     => __( 'Active Color', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} li.alphabet-list-item.active a' => 'color: {{VALUE}}!important;'
                ]
            ]
        );
        $this->add_control(
            'alphabet_acitve_bg_color',
            [
                'label'     => __( 'Active Background Color', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} li.alphabet-list-item.active' => 'background-color: {{VALUE}}!important;'
                ]
            ]
        );

        $this->add_control(
            'alphabet_disabled_color',
            [
                'label'     => __( 'Disabled Color', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .encyclopedia-alphabet-list a.letter-has-no-docs' => 'color: {{VALUE}};'
                ]
            ]
        );

        // Background Color Controls for Alphabet Links
        $this->add_control(
            'alphabet_background_color',
            [
                'label'     => __( 'Background Color', 'betterdocs-pro' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .encyclopedia-alphabet-list a' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        // Padding Controls for Alphabet Links
        $this->add_control(
            'alphabet_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .encyclopedia-alphabet-list a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        // Margin Controls for Alphabet Links
        $this->add_control(
            'alphabet_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .encyclopedia-alphabet-list a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();

    }

    protected function render_callback() {

        $settings = &$this->attributes;

        betterdocs_pro()->views->get( 'layouts/encyclopedia/navigation', [
            'alphabet_list_style' => $settings['encyclopedia_alphabet_list_style'],
            'docs_by_letter'      => EncyclopediaQuery::docs_sort_by_letter(),
            'current_letter'      => strtoupper( substr( get_the_title(), 0, 1 ) )
        ] );
    }

    public function view_params() {
        $settings = &$this->attributes;

        $multiple_kb_status = betterdocs()->editor->get( 'elementor' )->multiple_kb_status();

        $class   = ['betterdocs-popular-articles-wrapper'];
        $class[] = $multiple_kb_status ? 'multiple-kb' : 'single-kb';

        return [
            'wrapper_attr'       => [
                'class' => $class
            ],
            'nested_subcategory' => false,
            'list_icon_name'     => 'list',
            'title_tag'          => $settings['popular-layout-title-tag'],
            'title'              => $settings['popular_docs_name'],
            'query_args'         => $this->betterdocs( 'query' )->docs_query_args( [
                'post_type'      => 'docs',
                'posts_per_page' => $settings['popular_posts_number'],
                'meta_key'       => '_betterdocs_meta_views',
                'orderby'        => 'meta_value_num',
                'order'          => $settings['articles_sort']
            ], ['tax_query'] )
        ];
    }
}
