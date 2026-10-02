<?php
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- widget settings expose user-controlled exclusion lists; matches widget UX.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query,WordPress.DB.SlowDBQuery.slow_db_query_tax_query,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- core docs widget queries; meta/tax filtering is required functionality.
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Plugin;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Sidebar extends BaseWidget {

    public function get_name() {
        return 'betterdocs-sidebar';
    }

    public function get_title() {
        return __( 'BetterDocs Sidebar', 'betterdocs' );
    }

    public function get_icon() {
        return 'betterdocs-icon-Sidebar';
    }

    public function get_categories() {
        return ['betterdocs-elements', 'docs-archive', 'betterdocs-elements-single'];
    }

    public function get_keywords() {
        return ['betterdocs-elements', 'sidebar', 'betterdocs', 'docs'];
    }

    public function get_style_depends() {
        return ['betterdocs-sidebar', 'betterdocs-fontawesome', 'betterdocs-search-modal'];
    }

    public function get_script_depends() {
        return ['betterdocs-category-grid', 'betterdocs-search-modal'];
    }

    public function get_custom_help_url() {
        return 'https://betterdocs.co/docs/single-doc-in-elementor';
    }

    protected function register_controls() {
        /**
         * Query  Controls!
         * @source BaseWidget
         */
        $this->betterdocs_do_action();

        do_action( 'betterdocs_elementor_sidebar_layout_select', $this );

        $this->search_modal_query();
        $this->sidebar_layout_select();

        if ( ! is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' ) ) {
            $this->start_controls_section(
                'betterdocs_section_pro',
                [
                    'label' => __( 'Go Premium for More Features', 'betterdocs' )
                ]
            );

            $this->add_control(
                'betterdocs_control_get_pro',
                [
                    'label'       => __( 'Unlock more possibilities', 'betterdocs' ),
                    'type'        => Controls_Manager::CHOOSE,
                    'options'     => [
                        '1' => [
                            'title' => '',
                            'icon'  => 'fa fa-unlock-alt'
                        ]
                    ],
                    'default'     => '1',
                    'description' => '<span class="pro-feature"> Get the  <a href="https://betterdocs.co/upgrade" target="_blank">Pro version</a> for more stunning layouts and customization options.</span>'
                ]
            );

            $this->end_controls_section();
        }

        $this->wraper_setting_style();

        $this->category_items_setting_style();

        $this->icon_style();

        $this->title_style();

        $this->count_style();

        $this->list_setting();

        $this->sub_list_setting();

        $this->sticky_toc();

        //layout 5 sections & controllers
        $this->icon_style_layout_5();
        $this->title_style_layout_5();
        $this->count_style_layout_5();
        $this->list_setting_layout_5();
        $this->sub_list_setting_layout_5();
        $this->sidebar_search_layout_5();
        $this->sidebar_search_modal_layout_5();


    }

    public function search_modal_query() {
        $this->start_controls_section(
            'search_modal_query',
            [
                'label'     => __( 'Modal Query', 'betterdocs' ),
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_control(
            'search_modal_doc_query_type',
            [
                'label'       => __( 'Select Docs Type', 'betterdocs' ),
                'label_block' => true,
                'type'        => Controls_Manager::SELECT2,
                'options'     => [
                    'popular_docs'          => __('Popular Docs', 'betterdocs'),
                    'specific_doc_ids'      => __("Doc Id's", 'betterdocs'),
                    'specific_doc_term_ids' => __("Doc Category Id's", "betterdocs")
                ],
                'multiple'    => false,
                'default'     => 'popular_docs'
            ]
        );

        $this->add_control(
            'initial_docs_number',
            [
                'label'   => __( 'Number of Docs', 'betterdocs' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => '5',
                'condition'  => [
                    'search_modal_doc_query_type' => 'popular_docs'
                ]
            ]
        );


        $this->add_control(
            'search_modal_query_term_ids',
            [
                'label'   => __( "Doc Term ID's", 'betterdocs' ),
                'type'    => Controls_Manager::TEXT,
                'description' => __( 'Example: 8, 9', 'betterdocs' ),
                'default' => '',
                'condition'  => [
                    'search_modal_doc_query_type' => 'specific_doc_term_ids'
                ]
            ]
        );

        $this->add_control(
            'search_modal_query_doc_ids',
            [
                'label'   => __( "Doc ID's", 'betterdocs' ),
                'type'    => Controls_Manager::TEXT,
                'description' => __( 'Example: 15, 16', 'betterdocs' ),
                'default' => '',
                'condition'  => [
                    'search_modal_doc_query_type' => 'specific_doc_ids'
                ]
            ]
        );

        $this->add_control(
            'search_modal_faq_query_type',
            [
                'label'       => __( 'Select FAQ Type', 'betterdocs' ),
                'label_block' => true,
                'type'        => Controls_Manager::SELECT2,
                'default'     => 'default',
                'options'     => [
                    'default'                 => __('Default', 'betterdocs'),
                    'specific_faq_term_ids'   => __("Specific FAQ Term Id's", 'betterdocs'),
                ],
                'multiple'    => false,
            ]
        );

        $this->add_control(
            'search_modal_query_faq_term_ids',
            [
                'label'   => __( "FAQ Term ID's", 'betterdocs' ),
                'type'    => Controls_Manager::TEXT,
                'description' => __( 'Example: 8, 9', 'betterdocs' ),
                'default' => '',
                'condition'  => [
                    'search_modal_faq_query_type' => 'specific_faq_term_ids'
                ]
            ]
        );

        $this->add_control(
            'initial_faqs_number',
            [
                'label'   => __( "Number of FAQ's", 'betterdocs' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => '5',
                'condition'  => [
                    'search_modal_faq_query_type' => 'default'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function return_mod_terms( $accumulator, $term ) {
        $accumulator[$term->term_id] = htmlspecialchars_decode( $term->name );
        return $accumulator;
    }

    public function sidebar_layout_select() {
        /**
         * ----------------------------------------------------------
         * Section: Select Layout
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_layout_settings',
            [
                'label' => __( 'Layout', 'betterdocs' )
            ]
        );

        $this->add_control(
            'betterdocs_sidebar_layout',
            [
                'label'       => esc_html__( 'Select layout', 'betterdocs' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'layout-1',
                'label_block' => false,
                'options'     => [
                    'layout-1' => esc_html__( 'Layout 1', 'betterdocs' ),
                    'layout-2' => esc_html__( 'Layout 2', 'betterdocs' ),
                    'layout-3' => esc_html__( 'Layout 3', 'betterdocs' ),
                    'layout-4' => esc_html__( 'Layout 4', 'betterdocs' ),
                    'layout-5' => esc_html__( 'Layout 5', 'betterdocs' ),
                    'layout-6' => esc_html__( 'Layout 6', 'betterdocs' )
                ]
            ]
        );

        if ( ! is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' ) ) {
            $this->add_control(
                'betterdocs_sidebar_layout_warning_text',
                [
                    'type'            => Controls_Manager::RAW_HTML,
                    'raw'             => __( 'This layout is available in pro version only!', 'betterdocs' ),
                    'content_classes' => 'betterdocs-ea-warning',
                    'condition'       => [
                        'betterdocs_sidebar_layout' => ['layout-2', 'layout-3']
                    ]
                ]
            );
        }

        $this->add_control(
            'category_title_tag',
            [
                'label'   => __( 'Category Title Tag', 'betterdocs' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'h2',
                'options' => [
                    'h1' => __( 'H1', 'betterdocs' ),
                    'h2' => __( 'H2', 'betterdocs' ),
                    'h3' => __( 'H3', 'betterdocs' ),
                    'h4' => __( 'H4', 'betterdocs' ),
                    'h5' => __( 'H5', 'betterdocs' ),
                    'h6' => __( 'H6', 'betterdocs' )
                ]
            ]
        );

        $this->add_control(
            'sidebar_search_toggle',
            [
                'label'        => esc_html__( 'Sidebar Search', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'betterdocs' ),
                'label_off'    => esc_html__( 'Hide', 'betterdocs' ),
                'return_value' => 'true',
                'default'      => 'true',
                'condition'    => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label'        => __( 'Show Icon', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => 'true',
                'default'      => 'true',
                'condition'    => [
                    'betterdocs_sidebar_layout' => ['layout-1']
                ]
            ]
        );

        $this->add_control(
            'show_count',
            [
                'label'        => __( 'Show Count', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => 'true',
                'default'      => 'true',
                'condition'    => [
                    'betterdocs_sidebar_layout' => ['layout-1']
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Select Layout'
    }

    public function sticky_toc() {
        /**
         * ----------------------------------------------------------
         * Section: Box Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_sticky_toc',
            [
                'label'     => __( 'Sticky TOC', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4']
                ]
            ]
        );

        $this->add_control(
            'enable_sticky_toc',
            [
                'label'        => __( 'Enable Sticky TOC', 'betterdocs' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'betterdocs' ),
                'label_off'    => __( 'Hide', 'betterdocs' ),
                'return_value' => '1',
                'default'      => ''
            ]
        );

        $this->add_responsive_control(
            'toc_width',
            [
                'label'      => __( 'TOC Width', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max'  => 1000,
                        'step' => 1
                    ],
                    '%'  => [
                        'max'  => 100,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .sticky-toc-container' => 'width: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'toc_zindex', // Legacy control id but new control
            [
                'label'     => __( 'Z index', 'betterdocs' ),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 0,
                'max'       => 1000,
                'step'      => 5,
                'default'   => 320,
                'selectors' => [
                    '{{WRAPPER}} .sticky-toc-container' => 'z-index: {{VALUE}}%;'
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Card Settings'
    }

    public function wraper_setting_style() {
        $this->start_controls_section(
            'section_card_settings',
            [
                'label' => __( 'Wrapper', 'betterdocs' ),
                'tab'   => Controls_Manager::TAB_STYLE
            ]
        );

        $this->add_responsive_control(
            'column_space', // Legacy control id but new control
            [
                'label'      => __( 'Margin', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-8' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ]
            ]
        );

        $this->add_responsive_control(
            'column_padding',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-8' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ]
            ]
        );
        $this->add_control(
            'column_height',
            [
                'label'      => __( 'Height', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1000,
                        'step' => 5
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper' => 'height: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

		$this->add_responsive_control(
			'wrapper_width',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['%', 'px'],
				'range'      => [
					'%' => [
						'min' => 10,
						'max' => 100
					],
					'px' => [
						'min' => 100,
						'max' => 1200
					]
				],
				'default' => [
					'unit' => '%',
					'size' => 100
				],
				'selectors'  => [
					'{{WRAPPER}}' => 'width: {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-6']
                ]
			]
		);

        $this->add_responsive_control(
            'box_seperator_color',
            [
                'label'     => esc_html__( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-8,{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content' => 'background-color: {{VALUE}};'
                ]
            ]
        );
        $this->end_controls_section(); # end of 'Card Settings'
    }

    public function category_items_setting_style() {
        $this->start_controls_section(
            'category_items_settings',
            [
                'label'     => __( 'Category Items', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-6']
                ]
            ]
        );
        $this->start_controls_tabs( 'category_items_settings_tabs' );

        // Normal State Tab
        $this->start_controls_tab(
            'card_normal',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'card_bg_normal',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body, {{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body'
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'card_border_normal',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body, {{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body'
            ]
        );

        $this->add_responsive_control(
            'card_border_radius_normal',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow_normal',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body, {{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body'
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'card_hover',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_control(
            'card_transition',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .docs-single-cat-wrap' => 'transition: {{SIZE}}ms;'
                ]
            ]
        );

        $this->add_control(
            'box_section_body_hover',
            [
                'label'     => __( 'Body', 'betterdocs' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'card_bg_hover',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body:hover, {{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body:hover'
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'card_border_hover',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body:hover, {{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body:hover'
            ]
        );

        $this->add_responsive_control(
            'card_border_radius_hover',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body:hover'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow_hover',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body:hover, {{WRAPPER}} .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-single-category-inner .betterdocs-body:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body:hover'
            ]
        );

        $this->end_controls_tabs();
        $this->end_controls_section(); # end of 'Card Settings'
    }

    public function icon_style() {
        /**
         * ----------------------------------------------------------
         * Section: Icon Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_box_icon_style',
            [
                'label'     => __( 'Icon', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4']
                ]
            ]
        );

        $this->add_responsive_control(
            'category_settings_icon_size_normal',
            [
                'label'      => esc_html__( 'Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper:not(.layout-2) .betterdocs-category-icon' => 'height: {{SIZE}}{{UNIT}}; width: auto;',
                    '{{WRAPPER}} .betterdocs-category-icon img'                                                                                      => 'height: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'category_settings_arrow_width_normal',
            [
                'label'      => esc_html__( 'Arrow Width', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-collapse' => 'width: {{SIZE}}{{UNIT}};'
                ],
                'condition'  => [
                    'betterdocs_sidebar_layout' => ['layout-4']
                ]
            ]
        );

        $this->add_responsive_control(
            'category_settings_arrow_height_normal',
            [
                'label'      => esc_html__( 'Arrow Height', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-collapse' => 'height: {{SIZE}}{{UNIT}};'
                ],
                'condition'  => [
                    'betterdocs_sidebar_layout' => ['layout-4']
                ]
            ]
        );

        $this->start_controls_tabs( 'box_icon_styles_tab' );

        // Normal State Tab
        $this->start_controls_tab(
            'icon_normal',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => __( 'Arrow Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-category-collapse' => 'color: {{VALUE}}'
                ],
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-4']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'icon_background_normal',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-icon',
                'exclude'  => [
                    'image'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'icon_border_normal',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-icon'
            ]
        );

        $this->add_responsive_control(
            'icon_border_radius_normal',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'icon_padding',
            [
                'label'      => esc_html__( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label'              => esc_html__( 'Spacing', 'betterdocs' ),
                'type'               => Controls_Manager::DIMENSIONS,
                'size_units'         => ['px', 'em', '%'],
                'allowed_dimensions' => [
                    'top',
                    'bottom'
                ],
                'selectors'          => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-icon' => 'margin: {{TOP}}{{UNIT}} auto {{BOTTOM}}{{UNIT}} auto;'
                ]
            ]
        );

        $this->add_responsive_control(
            'arrow_padding_normal',
            [
                'label'      => esc_html__( 'Arrow Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-collapse' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
                'condition'  => [
                    'betterdocs_sidebar_layout' => ['layout-4']
                ]
            ]
        );

        $this->add_responsive_control(
            'arrow_margin_normal',
            [
                'label'      => esc_html__( 'Arrow Margin', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-collapse' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
                'condition'  => [
                    'betterdocs_sidebar_layout' => ['layout-4']
                ]
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'icon_hover',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_control(
            'arrow_color_hover',
            [
                'label'     => __( 'Arrow Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-header:hover .betterdocs-category-arrow' => 'color: {{VALUE}}'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'icon_background_hover',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-header:hover .betterdocs-category-icon'
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'icon_border_hover',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-header:hover .betterdocs-category-icon'
            ]
        );

        $this->add_responsive_control(
            'icon_border_radius_hover',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-header:hover .betterdocs-category-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]

            ]
        );

        $this->add_control(
            'category_settings_icon_size_transition',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .docs-single-cat-wrap .docs-cat-icon:hover' => 'transition: {{SIZE}}ms;'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section(); # end of 'Icon Styles'
    }

    public function icon_style_layout_5() {
        /**
         * ----------------------------------------------------------
         * Section: Icon Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_box_icon_style_layout_5',
            [
                'label'     => __( 'Icon', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_responsive_control(
            'category_settings_icon_size_normal_layout_5',
            [
                'label'      => esc_html__( 'Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon'     => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title .betterdocs-folder-icon.arrow-right svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title .betterdocs-folder-icon.arrow-right'  => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->start_controls_tabs( 'box_icon_styles_tab_layout_5' );

        // Normal State Tab
        $this->start_controls_tab(
            'icon_normal_layout_5',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'icon_background_normal_layout_5',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon',
                'exclude'  => [
                    'image'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'icon_border_normal_layout_5',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon'
            ]
        );

        $this->add_responsive_control(
            'icon_border_radius_normal_layout_5',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'icon_padding_layout_5',
            [
                'label'      => esc_html__( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'icon_spacing_layout_5',
            [
                'label'              => esc_html__( 'Margin', 'betterdocs' ),
                'type'               => Controls_Manager::DIMENSIONS,
                'size_units'         => ['px', 'em', '%'],
                'allowed_dimensions' => [
                    'top',
                    'bottom'
                ],
                'selectors'          => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon' => 'margin: {{TOP}}{{UNIT}} auto {{BOTTOM}}{{UNIT}} auto;'
                ]
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'icon_hover_layout_5',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'icon_border_hover_layout_5',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon:hover'
            ]
        );

        $this->add_responsive_control(
            'icon_border_radius_hover_layout_5',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]

            ]
        );

        $this->add_control(
            'category_settings_icon_size_transition_layout_5',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon' => 'transition: {{SIZE}}ms;'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section(); # end of 'Icon Styles'
    }

    public function title_style() {
        /**
         * ----------------------------------------------------------
         * Section: Title Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_box_title_styles',
            [
                'label'     => __( 'Title', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-6']
                ]
            ]
        );

        $this->start_controls_tabs( 'box_title_styles_tab' );

        // Normal State Tab
        $this->start_controls_tab(
            'title_normal',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_control(
            'cat_title_color_normal',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar-layout-8 .betterdocs-category-title' => 'color: {{VALUE}};'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'           => 'card_bg_normal_header',
                'types'          => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header, {{WRAPPER}} .betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'cat_title_typography_normal',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar-layout-8 .betterdocs-category-title'
            ]
        );

        $this->add_responsive_control(
            'title_spacing',
            [
                'label'      => __( 'Margin', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-category-list-wrapper .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar-layout-8 .betterdocs-category-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'title_padding',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-category-list-wrapper .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar-layout-8 .betterdocs-category-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'title_hover',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_control(
            'cat_title_color_hover',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header:hover .betterdocs-category-title a, {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header:hover .betterdocs-category-title:not(a),  {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper .betterdocs-category-header:hover .betterdocs-category-title:not(a), {{WRAPPER}} .betterdocs-sidebar-layout-8 .betterdocs-category-title:hover' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'card_bg_hover_header',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header:hover, {{WRAPPER}} .betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header:hover'
            ]
        );

        $this->add_control(
            'category_title_transition',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-header' => 'transition: {{SIZE}}ms;'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tab();

        $this->start_controls_tab(
            'card_active',
            ['label' => esc_html__( 'Active', 'betterdocs' )]
        );

        $this->add_control(
            'cat_title_active_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active .betterdocs-category-header .betterdocs-category-title a, {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active .betterdocs-category-header .betterdocs-category-title:not(a),  {{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper .betterdocs-category-header .betterdocs-category-title:not(a)' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'           => 'card_bg_active_header',
                'types'          => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active .betterdocs-category-header',
                'exclude'        => [
                    'image'
                ],
            ]
        );

        $this->add_control(
            'cat_title_active_border_color',
            [
                'label'     => esc_html__( 'Border Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#5a94ff',
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header' => 'border-color: {{VALUE}};'
                ],
                'condition' => [
                    'betterdocs_sidebar_layout' => 'layout-1'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section(); # end of 'Icon Styles'
    }

    public function title_style_layout_5() {
        /**
         * ----------------------------------------------------------
         * Section: Title Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_box_title_styles_layout_5',
            [
                'label'     => __( 'Title', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->start_controls_tabs( 'box_title_styles_tab_layout_5' );

        // Normal State Tab
        $this->start_controls_tab(
            'title_normal_layout_5',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_control(
            'cat_title_color_normal_layout_5',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title'                           => 'color: {{VALUE}};',
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a' => 'color: {{VALUE}};'
                ],
                'default'   => '#344054'
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'           => 'card_bg_normal_header_layout_5',
                'types'          => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'cat_title_typography_normal_layout_5',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a'
            ]
        );

        $this->add_responsive_control(
            'title_spacing_layout_5',
            [
                'label'      => __( 'Margin', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title'                         => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'title_padding_layout_5',
            [
                'label'      => __( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title'                         => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'title_hover_layout_5',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_control(
            'cat_title_color_hover_layout_5',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:hover, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a:hover' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'card_bg_hover_header_layout_5',
                'types'    => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner:hover, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title:hover, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header:hover',
            ]
        );

        $this->add_control(
            'category_title_transition_layout_5',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner, {{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title' => 'transition: {{SIZE}}ms;'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tab();

        $this->start_controls_tab(
            'card_active_layout_5',
            ['label' => esc_html__( 'Active', 'betterdocs' )]
        );

        $this->add_control(
            'cat_title_active_color_layout_5',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.show .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'           => 'card_bg_active_header_layout_5',
                'types'          => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.show .betterdocs-single-category-inner .betterdocs-category-header',
                'exclude'        => [
                    'image'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section(); # end of 'Icon Styles'
    }

    public function count_style() {
        /**
         * ----------------------------------------------------------
         * Section: Count Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_box_count_styles',
            [
                'label'     => __( 'Count', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'count_typography_normal',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span'
            ]
        );

        $this->start_controls_tabs( 'box_count_styles_tab' );

        // Normal State Tab
        $this->start_controls_tab(
            'count_normal',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_control(
            'count_color_normal',
            [
                'label'     => esc_html__( 'Text Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span' => 'color: {{VALUE}};'
                ],
                'default'   => '#ffffff'
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'           => 'count_box_bg',
                'types'          => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts',
                'exclude'        => [
                    'image'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'count_box_border',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts'
            ]
        );

        $this->add_responsive_control(
            'count_box_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'count_box_box_shadow',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts'
            ]
        );

        $this->add_responsive_control(
            'count_box_size',
            [
                'label'      => esc_html__( 'Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'count_spacing',
            [
                'label'      => __( 'Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_control(
            'second_color_seperator',
            [
                'label'     => esc_html__( 'Second Color', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'betterdocs_sidebar_layout' => 'layout-1'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'count_box_bg_second',
                'types'     => ['classic', 'gradient'],
                'selector'  => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
                'exclude'   => [
                    'image'
                ],
                'condition' => [
                    'betterdocs_sidebar_layout' => 'layout-1'
                ]
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'count_hover',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_control(
            'count_color_hover',
            [
                'label'     => esc_html__( 'Text Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover span' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'count_box_bg_hover',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover',
                'exclude'  => [
                    'image'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'count_box_border_hover',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover'

            ]
        );

        $this->add_responsive_control(
            'count_box_border_radius_hover',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]

            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'count_box_box_shadow_hover',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover'
            ]
        );

        $this->add_control(
            'category_count_transition',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-category-items-counts:hover' => 'transition: {{SIZE}}ms;'

                ]
            ]
        );

        $this->add_control(
            'hover_second_color_seperator',
            [
                'label'     => esc_html__( 'Second Color', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'betterdocs_sidebar_layout' => 'layout-1'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'count_box_bg_second_hover',
                'types'     => ['classic', 'gradient'],
                'selector'  => '{{WRAPPER}} .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover span',
                'exclude'   => [
                    'image'
                ],
                'condition' => [
                    'betterdocs_sidebar_layout' => 'layout-1'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section(); # end of 'Count Styles'
    }

    public function count_style_layout_5() {
        /**
         * ----------------------------------------------------------
         * Section: Count Styles
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_box_count_styles_layout_5',
            [
                'label'     => __( 'Count', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'count_typography_normal_layout_5',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span'
            ]
        );

        $this->start_controls_tabs( 'box_count_styles_tab_layout_5' );

        // Normal State Tab
        $this->start_controls_tab(
            'count_normal_layout_5',
            ['label' => esc_html__( 'Normal', 'betterdocs' )]
        );

        $this->add_control(
            'count_color_normal_layout_5',
            [
                'label'     => esc_html__( 'Text Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span' => 'color: {{VALUE}};'
                ],
                'default'   => ''
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'           => 'count_box_bg_layout_5',
                'types'          => ['classic', 'gradient'],
                'selector'       => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
                'exclude'        => [
                    'image'
                ],
                'fields_options' => [
                    'background' => [
                        'default' => 'classic'
                    ],
                    'color'      => [
                        'default' => ''
                    ]
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'count_box_border_layout_5',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts'
            ]
        );

        $this->add_responsive_control(
            'count_box_border_radius_layout_5',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'count_box_box_shadow_layout_5',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts'
            ]
        );

        $this->add_responsive_control(
            'count_box_size_layout_5',
            [
                'label'      => esc_html__( 'Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'count_spacing_layout_5',
            [
                'label'      => __( 'Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_tab();

        // Hover State Tab
        $this->start_controls_tab(
            'count_hover_layout_5',
            ['label' => esc_html__( 'Hover', 'betterdocs' )]
        );

        $this->add_control(
            'count_color_hover_layout_5',
            [
                'label'     => esc_html__( 'Text Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span:hover' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'count_box_bg_hover_layout_5',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span:hover',
                'exclude'  => [
                    'image'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'count_box_border_hover_layout_5',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover'

            ]
        );

        $this->add_responsive_control(
            'count_box_border_radius_hover_layout_5',
            [
                'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]

            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'count_box_box_shadow_hover_layout_5',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover'
            ]
        );

        $this->add_control(
            'category_count_transition_layout_5',
            [
                'label'      => __( 'Transition', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'size' => 300,
                    'unit' => '%'
                ],
                'size_units' => ['%'],
                'range'      => [
                    '%' => [
                        'max'  => 2500,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'transition: {{SIZE}}ms;'

                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section(); # end of 'Count Styles'
    }

    public function list_setting() {
        /**
         * ----------------------------------------------------------
         * Section: List Settinggs
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_article_settings',
            [
                'label'     => __( 'Category List', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-6']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'list_item_typography',
                'selector' => '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a'
            ]
        );

        $this->add_control(
            'list_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'list_hover_color',
            [
                'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a:focus' => 'color: {{VALUE}};'
                ]
            ]
        );

		$this->add_control(
			'list_hover_background_color',
			[
				'label'     => __( 'Hover Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a.active' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a:focus' => 'background-color: {{VALUE}};'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-6']
                ]
			]
		);

        $this->add_responsive_control(
            'list_margin',
            [
                'label'      => esc_html__( 'List Item Margin', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

		$this->add_responsive_control(
			'list_padding',
			[
				'label'      => __( 'List Item Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
			]
		);

        $this->add_responsive_control(
            'list_area_padding',
            [
                'label'              => esc_html__( 'List Area Padding', 'betterdocs' ),
                'type'               => Controls_Manager::DIMENSIONS,
                'allowed_dimensions' => 'vertical',
                'size_units'         => ['px', 'em', '%'],
                'selectors'          => [
                    '{{WRAPPER}} .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-body' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-5']
                ]
            ]
        );

        $this->add_control(
            'icon_settings_heading',
            [
                'label'     => esc_html__( 'Icon', 'betterdocs' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-5']
                ]
            ]
        );

        $this->add_control(
            'list_icon',
            [
                'label'   => __( 'Icon', 'betterdocs' ),
                'type'    => Controls_Manager::ICONS,
                'default' => [
                    'value'   => 'far fa-file-alt',
                    'library' => 'fa-regular'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-5']
                ]
            ]
        );

        $this->add_control(
            'list_icon_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li i'   => 'color: {{VALUE}};'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-5']
                ]
            ]
        );

        $this->add_responsive_control(
            'list_icon_size',
            [
                'label'      => __( 'Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    '%' => [
                        'max'  => 100,
                        'step' => 1
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li svg'                                   => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li .betterdocs-nested-category-title svg' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li i'                                     => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li .betterdocs-nested-category-title i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li img'                                   => 'width: {{SIZE}}{{UNIT}}; height:{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li .betterdocs-nested-category-title img' => 'width: {{SIZE}}{{UNIT}}; height:{{SIZE}}{{UNIT}};'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-5']
                ]
            ]
        );

        $this->add_responsive_control(
            'list_icon_spacing',
            [
                'label'      => esc_html__( 'Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li i'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-5']
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Column Settings'
    }

    public function list_setting_layout_5() {
        /**
         * ----------------------------------------------------------
         * Section: List Settinggs
         * ----------------------------------------------------------
         */
        $this->start_controls_section(
            'section_article_settings_layout_5',
            [
                'label'     => __( 'Category List', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'list_item_typography_layout_5',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a'
            ]
        );

        $this->add_control(
            'list_color_layout_5',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'list_background_color_layout_5',
            [
                'label'     => esc_html__( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'list_outer_background_color_layout_5',
            [
                'label'     => esc_html__( 'Outer Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#fff',
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'list_background_hover_color_layout_5',
            [
                'label'     => esc_html__( 'Background Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a:hover' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'list_hover_color_layout_5',
            [
                'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a:hover' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'list_margin_layout_5',
            [
                'label'      => esc_html__( 'List Item Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'list_area_padding_layout_5',
            [
                'label'              => esc_html__( 'List Area Padding', 'betterdocs' ),
                'type'               => Controls_Manager::DIMENSIONS,
                'allowed_dimensions' => 'vertical',
                'size_units'         => ['px', 'em', '%'],
                'selectors'          => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};'
                ]
            ]
        );

        $this->add_control(
            'list_border_color_layout_5',
            [
                'label'     => esc_html__( 'Active Border Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active.default.show:before' => 'background: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'list_border_width_layout_5',
            [
                'label'      => esc_html__( 'Active Border Width', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active.default.show:before' => 'width: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Column Settings'
    }

    /**
     * ----------------------------------------------------------
     * Section: Sub List Settinggs
     * ----------------------------------------------------------
     */
    public function sub_list_setting() {

        $this->start_controls_section(
            'section_sub_list_settings',
            [
                'label'     => __( 'Sub-Category List', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-6']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sub_list_item_typography',
                'selector' => '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list .betterdocs-nested-category-list li a, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a'
            ]
        );

        $this->add_control(
            'sub_list_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list .betterdocs-nested-category-list a, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_list_hover_color',
            [
                'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list .betterdocs-nested-category-list a:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a:hover, {{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a:focus' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_list_margin',
            [
                'label'      => esc_html__( 'List Item Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list .betterdocs-nested-category-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_list_area_padding',
            [
                'label'              => esc_html__( 'List Area Padding', 'betterdocs' ),
                'type'               => Controls_Manager::DIMENSIONS,
                'allowed_dimensions' => 'vertical',
                'size_units'         => ['px', 'em', '%'],
                'selectors'          => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list .betterdocs-nested-category-list li' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_list_icon_spacing',
            [
                'label'      => esc_html__( 'Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list .betterdocs-nested-category-list li svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Column Settings'
    }

    public function sub_list_setting_layout_5() {
        $this->start_controls_section(
            'section_sub_list_settings_layout_5',
            [
                'label'     => __( 'Sub-Category List', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sub_list_item_typography_layout_5',
                'selector' => '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a'
            ]
        );

        $this->add_control(
            'sub_list_color_layout_5',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_list_hover_color_layout_5',
            [
                'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a:hover' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_list_background_color_layout_5',
            [
                'label'     => esc_html__( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_list_background_hover_color_layout_5',
            [
                'label'     => esc_html__( 'Background Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a:hover' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_list_margin_layout_5',
            [
                'label'      => esc_html__( 'List Item Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_list_area_padding_layout_5',
            [
                'label'              => esc_html__( 'List Area Padding', 'betterdocs' ),
                'type'               => Controls_Manager::DIMENSIONS,
                'allowed_dimensions' => 'vertical',
                'size_units'         => ['px', 'em', '%'],
                'selectors'          => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_list_icon_spacing_layout_5',
            [
                'label'      => esc_html__( 'Spacing', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section(); # end of 'Column Settings'
    }

    public function sidebar_search_layout_5() {
        $this->start_controls_section(
            'sidebar_search',
            [
                'label'     => __( 'Sidebar Search', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_search_typography',
                'selector' => '{{WRAPPER}} betterdocs-search-popup .betterdocs-searchform .betterdocs-searchform-input-wrap .betterdocs-search-command'
            ]
        );

        $this->add_control(
            'sidebar_search_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-popup .betterdocs-searchform .betterdocs-searchform-input-wrap .betterdocs-search-command' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sidebar_search_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f9fafb',
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_icon_size',
            [
                'label'      => esc_html__( 'Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => [
                        'max' => 500
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-search-popup .betterdocs-searchform .betterdocs-searchform-input-wrap svg' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function sidebar_search_modal_layout_5() {
        $this->start_controls_section(
            'sidebar_search_modal',
            [
                'label'     => __( 'Sidebar Search Modal', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-5']
                ]
            ]
        );

        $this->add_control(
            'sidebar_search_modal_field',
            [
                'label'     => esc_html__( 'Search Field', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_magnifier_color',
            [
                'label'     => esc_html__( 'Magnifier Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header svg g path' => 'fill: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_field_background_color',
            [
                'label'     => esc_html__( 'Field Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_search_modal_field_typography',
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field'
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_modal_field_color',
            [
                'label'     => esc_html__( 'Field Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_modal_field_placeholder_color',
            [
                'label'     => esc_html__( 'Field Placeholder Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field::placeholder' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sidebar_search_modal_category_section',
            [
                'label'     => esc_html__( 'Search Category', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_modal_categories_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper .betterdocs-form-select' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_search_modal_categories_typography',
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper .betterdocs-form-select'
            ]
        );

        $this->add_control(
            'sidebar_search_modal_content_tabs',
            [
                'label'     => esc_html__( 'Content Tabs', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_search_modal_content_tabs_typography',
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span'
            ]
        );

        $this->add_control(
            'sidebar_search_modal_content_tabs_icon_size',
            [
                'label'      => __( 'Icon Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1000,
                        'step' => 5
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'sidebar_search_modal_content_active_tab_border',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items.active'
            ]
        );

        $this->add_control(
            'sidebar_search_modal_content_list',
            [
                'label'     => esc_html__( 'Content List', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_search_modal_content_list_typography',
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main h4'
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_modal_content_list_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main h4' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sidebar_search_modal_content_list_icon_size',
            [
                'label'      => __( 'Icon Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1000,
                        'step' => 5
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'sidebar_search_modal_content_list_border',
                'label'    => esc_html__( 'Border', 'betterdocs' ),
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list'
            ]
        );

        $this->add_control(
            'sidebar_search_modal_content_list_category',
            [
                'label'     => esc_html__( 'Content List Category', 'betterdocs' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_search_modal_content_list_category_typography',
                'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub h5'
            ]
        );

        $this->add_responsive_control(
            'sidebar_search_modal_content_list_category_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub h5' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sidebar_search_modal_content_list_category_icon_size',
            [
                'label'      => __( 'Icon Size', 'betterdocs' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1000,
                        'step' => 5
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100
                    ]
                ],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }



    public function sub_category_list_style_layout_6() {
        $this->start_controls_section(
            'section_sub_category_list_style_layout_6',
            [
                'label'     => __( 'Sub Category List', 'betterdocs' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'betterdocs_sidebar_layout' => ['layout-6']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sub_category_list_typography_layout_6',
                'selector' => '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a'
            ]
        );

        $this->add_control(
            'sub_category_list_color_layout_6',
            [
                'label'     => __( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_category_list_hover_color_layout_6',
            [
                'label'     => __( 'Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a:focus' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_category_list_hover_background_color_layout_6',
            [
                'label'     => __( 'Hover Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e8fff4',
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a:focus' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_category_list_padding_layout_6',
            [
                'label'      => esc_html__( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:has(>ul) > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_control(
            'sub_category_nested_list_heading',
            [
                'label'     => __( 'Nested List Items', 'betterdocs' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sub_category_nested_list_typography_layout_6',
                'selector' => '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a'
            ]
        );

        $this->add_control(
            'sub_category_nested_list_color_layout_6',
            [
                'label'     => __( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_category_nested_list_hover_color_layout_6',
            [
                'label'     => __( 'Hover Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a:focus' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'sub_category_nested_list_hover_background_color_layout_6',
            [
                'label'     => __( 'Hover Background Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e8fff4',
                'selectors' => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a:focus' => 'background-color: {{VALUE}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'sub_category_nested_list_padding_layout_6',
            [
                'label'      => esc_html__( 'Padding', 'betterdocs' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li ul li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    protected function render_callback() {
        $settings         = $this->get_settings_for_display();
        $this->attributes = &$settings;

        $layout = isset( $settings['betterdocs_sidebar_layout'] ) ? $settings['betterdocs_sidebar_layout'] : 'layout-1';
        $layout = str_replace( 'layout-', '', $layout );

        $sidebar_layout = $layout;

        if ( ! betterdocs()->is_pro_active() && ( $sidebar_layout == 2 || $sidebar_layout == 3 ) ) {
            $sidebar_layout = 1;
        }

        if ( $sidebar_layout == 5 ) {
            $sidebar_layout = 7;
        }

		if ( $sidebar_layout == 6 ) {
            $sidebar_layout = 8;
        }

        $this->views( 'templates/sidebars/sidebar-' . $sidebar_layout );

        if ( $settings['enable_sticky_toc'] == 1 && $layout == 1 ) {
            betterdocs()->views->get( 'templates/parts/sticky-toc' );
        }

        if ( Plugin::instance()->editor->is_edit_mode() ) {
            $this->render_editor_script();
        }
    }

    public function view_params() {
        /**
         * Localize This In Order To Know If This Block Is Arriving From Betterdocs Templates Or Not
         */
        betterdocs()->assets->localize( 'betterdocs-category-grid', 'betterdocsCategoryGridConfig', [
            'is_betterdocs_templates' => betterdocs()->helper->is_templates() ? true : false,
            // Must also carry the lazy-load endpoint. wp_localize_script concatenates
            // every `var betterdocsCategoryGridConfig = …` for this object name and the
            // last assignment wins; shipping only is_betterdocs_templates here clobbered
            // Scripts.php's full config on Elementor pages, leaving ajax_url undefined so
            // loadLazyBody() bailed and the sidebar widget's lazy-load never fired.
            'ajax_url'                => admin_url( 'admin-ajax.php' ),
            'lazy_load_action'        => 'betterdocs_lazy_category_body',
        ] );

        $settings            = &$this->attributes;
        $default_multiple_kb = (bool) betterdocs()->editor->get( 'elementor' )->multiple_kb_status();
        $kb_slug             = isset( $settings['selected_knowledge_base'] ) ? $settings['selected_knowledge_base'] : '';

        if ( $settings['betterdocs_sidebar_layout'] == 'layout-5' || $settings['betterdocs_sidebar_layout'] == 'layout-6' ) {
            $settings['list_icon'] = [
                'value' => [
                    'url' => ''
                ]
            ];
        }

        $params = [
            'wrapper_attr'   => [
                'class' => ['betterdocs-elementor-single-sidebar']
            ],
            'shortcode_attr' => [
                'layout_type'              => 'widget',
                'list_icon_url'            => '',
                'list_icon_name'           => is_array( $settings['list_icon']['value'] ) ? $settings['list_icon']['value']['url'] : $settings['list_icon']['value'],
                'terms_order'              => $settings['order'],
                'terms_orderby'            => $settings['orderby'],
                'orderby'                  => $settings['post_orderby'],
                'order'                    => $settings['post_order'],
                'terms_include'            => array_diff( $settings['include'], (array) $settings['exclude'] ),
                'terms_exclude'            => $settings['exclude'],
                'terms_offset'             => $settings['offset'],
                'nested_subcategory'       => $settings['nested_subcategory'],
                'lazy_load'                => ! empty( $settings['lazy_load'] ),
                'sidebar_list'             => true,
                'disable_customizer_style' => true,
                'posts_per_page'           => -1,
                'title_tag'                => $settings['category_title_tag'],
                'sidebar_layout'           => $settings['betterdocs_sidebar_layout']
            ]
        ];

        // Pass a KB scope only when one is explicitly selected on the widget. When none
        // is set, omit kb_slug so the betterdocs_sidebar_template_shortcode_params filter
        // (Pro/MKB) can inject the current knowledge_base context on KB templates — the
        // same path the default sidebar uses. With no KB context nothing is injected and
        // all categories render. (Passing kb_slug='' here would block that injection.)
        if ( ! empty( $kb_slug ) ) {
            $params['shortcode_attr']['kb_slug']                 = $kb_slug;
            $params['shortcode_attr']['multiple_knowledge_base'] = (bool) $default_multiple_kb;
        }

        if ( $settings['betterdocs_sidebar_layout'] == 'layout-1' ) {
            $params['shortcode_attr']['show_icon']  = $settings['show_icon'];
            $params['shortcode_attr']['show_count'] = $settings['show_count'];
        }

        if ( $settings['betterdocs_sidebar_layout'] == 'layout-4' || $settings['betterdocs_sidebar_layout'] == 'layout-3' ) {
            $params['shortcode_attr']['show_icon']  = false;
            $params['shortcode_attr']['show_count'] = false;
        }

        if ( $settings['betterdocs_sidebar_layout'] == 'layout-5' ) {
            $params['number_of_docs']                  = $settings['initial_docs_number'];
            $params['number_of_faqs']                  = $settings['initial_faqs_number'];
            $params['doc_ids']                         = $settings['search_modal_query_doc_ids'];
            $params['doc_term_ids']                    = $settings['search_modal_query_term_ids'] ;
            $params['faq_term_ids']                    = $settings['search_modal_query_faq_term_ids'];
            $params['sidebar_search']                  = $settings['sidebar_search_toggle'];
        }
        return $params;
    }

    public function render_editor_script() {
        $this->views( 'templates/sidebars/editor' );
    }
}
