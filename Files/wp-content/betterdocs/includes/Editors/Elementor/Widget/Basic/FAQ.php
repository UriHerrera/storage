<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget\Basic;

use Elementor\Widget_Base;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Group_Control_Background;
use Elementor\Plugin as ElementorPlugin;
use Elementor\Controls_Manager;
use WPDeveloper\BetterDocs\Editors\Elementor\Helper;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use WPDeveloper\BetterDocs\FrontEnd\WooProductFAQ as WooFAQRenderer;

class FAQ extends BaseWidget {

	public function get_name() {
		return 'betterdocs-faq';
	}

	public function get_title() {
		return __( 'BetterDocs FAQ', 'betterdocs' );
	}

	public function get_custom_help_url() {
		return 'http://betterdocs.co/docs/betterdocs-faq-builder-in-elementor/';
	}

	public function get_icon() {
		return 'betterdocs-icon-faq';
	}

	public function get_categories() {
		return [ 'betterdocs-elements' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'betterdocs', 'docs', 'faq', 'FAQ', 'betterdocs-faq' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-faq' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'faq_section_controls',
			[
				'label' => __( 'Layout Options', 'betterdocs' )
			]
		);

		$this->add_control(
			'faq_layout_selection',
			[
				'label'       => __( 'Select Layout', 'betterdocs' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => [
					'layout-3' => __( 'Abstract Layout', 'betterdocs' ),
					'layout-1' => __( 'Modern Layout', 'betterdocs' ),
					'layout-2' => __( 'Classic Layout', 'betterdocs' ),
					'layout-4' => __( 'Tab Layout', 'betterdocs' )
				],
				'default'     => 'layout-1',
				'label_block' => true
			]
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$this->add_control(
				'faq_taxonomy',
				[
					'label'   => __( 'FAQ Type', 'betterdocs' ),
					'type'    => Controls_Manager::SELECT,
					'options' => [
						'betterdocs_faq_category'         => __( 'General FAQ', 'betterdocs' ),
						'betterdocs_product_faq_category' => __( 'Product FAQ', 'betterdocs' ),
						'product_assignments'             => __( 'Product Assignments', 'betterdocs' ),
					],
					'default' => 'betterdocs_faq_category',
				]
			);

			$this->add_control(
				'faq_product_assignments_note',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => __( 'Groups are resolved automatically per product: a per-product assignment is shown if set, otherwise the groups assigned to the product’s categories. Manage assignments on each group in FAQ Builder → WooCommerce → Product FAQ Groups.', 'betterdocs' ),
					'content_classes' => 'elementor-control-field-description',
					'condition'       => [ 'faq_taxonomy' => 'product_assignments' ],
				]
			);
		}

		$this->add_control(
			'faq_layout_section',
			[
				'label'   => __( 'FAQ Section', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true
				],
				'default' => __( 'Frequently Asked Questions', 'betterdocs' )
			]
		);

		$this->add_control(
			'faq_section_title_tag',
			[
				'label'   => __( 'Section Title Tag', 'betterdocs' ),
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
			'faq_group_title_tag',
			[
				'label'   => __( 'Group Title Tag', 'betterdocs' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => [
					'h1' => __( 'H1', 'betterdocs' ),
					'h2' => __( 'H2', 'betterdocs' ),
					'h3' => __( 'H3', 'betterdocs' ),
					'h4' => __( 'H4', 'betterdocs' ),
					'h5' => __( 'H5', 'betterdocs' ),
					'h6' => __( 'H6', 'betterdocs' )
				],
				'condition' => [
					'faq_layout_selection' => [ 'layout-1', 'layout-2', 'layout-3' ]
				]
			]
		);

		$terms = betterdocs()->container->get( Helper::class )->get_faq_terms();

		// General FAQ include/exclude — shown always when WooCommerce is off,
		// or conditioned on FAQ Type = General FAQ when WooCommerce is on.
		$general_include_args = [
			'label'          => __( 'Include FAQ Groups', 'betterdocs' ),
			'label_block'    => true,
			'type'           => Controls_Manager::SELECT2,
			'options'        => $terms,
			'multiple'       => true,
			'default'        => '',
			'select2options' => [
				'placeholder' => __( 'Include FAQ Groups', 'betterdocs' ),
				'allowClear'  => true
			]
		];
		$general_exclude_args = [
			'label'          => __( 'Exclude FAQ Groups', 'betterdocs' ),
			'label_block'    => true,
			'type'           => Controls_Manager::SELECT2,
			'options'        => $terms,
			'multiple'       => true,
			'default'        => '',
			'select2options' => [
				'placeholder' => __( 'Exclude FAQ Groups', 'betterdocs' ),
				'allowClear'  => true
			]
		];

		if ( class_exists( 'WooCommerce' ) ) {
			$general_include_args['condition'] = [ 'faq_taxonomy' => 'betterdocs_faq_category' ];
			$general_exclude_args['condition'] = [ 'faq_taxonomy' => 'betterdocs_faq_category' ];
		}

		$this->add_control( 'select_specific_faq', $general_include_args );
		$this->add_control( 'exclude_specific_faq', $general_exclude_args );

		if ( class_exists( 'WooCommerce' ) ) {
			$product_terms = betterdocs()->query->get_faq_terms( [], 'betterdocs_product_faq_category' );

			$this->add_control(
				'select_specific_product_faq',
				[
					'label'          => __( 'Include Product FAQ Groups', 'betterdocs' ),
					'label_block'    => true,
					'type'           => Controls_Manager::SELECT2,
					'options'        => $product_terms,
					'multiple'       => true,
					'default'        => '',
					'condition'      => [ 'faq_taxonomy' => 'betterdocs_product_faq_category' ],
					'select2options' => [
						'placeholder' => __( 'Include Product FAQ Groups', 'betterdocs' ),
						'allowClear'  => true
					]
				]
			);

			$this->add_control(
				'exclude_specific_product_faq',
				[
					'label'          => __( 'Exclude Product FAQ Groups', 'betterdocs' ),
					'label_block'    => true,
					'type'           => Controls_Manager::SELECT2,
					'options'        => $product_terms,
					'multiple'       => true,
					'default'        => '',
					'condition'      => [ 'faq_taxonomy' => 'betterdocs_product_faq_category' ],
					'select2options' => [
						'placeholder' => __( 'Exclude Product FAQ Groups', 'betterdocs' ),
						'allowClear'  => true
					]
				]
			);
		}

		$this->end_controls_section();

		/**
		 * ----------------------------------------------------------
		 * Section: Container Section Settings
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_faq_container_section',
			[
				'label' => __( 'Container Section', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'section_faq_container_section_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'section_faq_container_section_margin', // Legacy control id but new control
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_faq_container_section_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper'
			]
		);

		$this->end_controls_section();

		/******* Common Section Style For Both Layouts *******/

		$this->start_controls_section(
			'faq_section_style',
			[
				'label' => __( 'FAQ Section Title', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'faq_layout_section_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-section-title' => 'color:{{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_layout_section_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-section-title'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_title_section',
			[
				'label' => __( 'FAQ Group Title', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-1', 'layout-2', 'layout-3']
				]
			]
		);

		$this->add_control(
			'faq_box_title_color',
			[
				'label'     => esc_html__( 'Title Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag' => 'color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_color_hover',
			[
				'label'     => esc_html__( 'Title Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag:hover' => 'color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-title' => 'background-color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_background_color_hover',
			[
				'label'     => esc_html__( 'Background Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-title:hover' => 'background-color:{{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_box_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_title_section_layout_4',
			[
				'label' => __( 'FAQ Group Title', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-4']
				]
			]
		);

		$this->add_control(
			'faq_box_title_color_layout_4',
			[
				'label'     => esc_html__( 'Title Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-title, .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-tab .faq-tab-title' => 'color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_color_hover_layout_4',
			[
				'label'     => esc_html__( 'Title Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-title:hover' => 'color:{{VALUE}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-tab .faq-tab-title:hover' => 'color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_background_color_layout_4',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab' => 'background-color:{{VALUE}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-tab' => 'background-color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_background_color_hover_layout_4',
			[
				'label'     => esc_html__( 'Background Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab:hover' => 'background-color:{{VALUE}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-tab:hover' => 'background-color:{{VALUE}};'
				]
			]
		);

		$this->add_control(
			'faq_box_title_background_color_active_layout_4',
			[
				'label'     => esc_html__( 'Active Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab.active' => 'background-color:{{VALUE}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-tab.active' => 'background-color:{{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_box_title_typography_layout_4',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-title, .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-tab .faq-tab-title'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_style_section',
			[
				'label' => __( 'FAQ List', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-1', 'layout-2', 'layout-3']
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_box_typography',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name'
			]
		);

		$this->add_control(
			'faq_box_term_title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name' => 'color:{{VALUE}};'
				]
			]
		);

		$this->start_controls_tabs( 'faq_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'faq_box_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'faq_box_border_normal',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post'
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'faq_box_background_normal',
				'label'    => esc_html__( 'Background', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post'
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'faq_box_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'faq_box_border_hover',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post:hover'
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'faq_box_background_hover',
				'label'    => esc_html__( 'Background', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post:hover'
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_style_section_layout_4',
			[
				'label' => __( 'FAQ List', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-4']
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_padding_layout_4',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_margin_layout_4',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_box_typography_layout_4',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name'
			]
		);

		$this->add_control(
			'faq_box_term_title_color_layout_4',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name' => 'color:{{VALUE}};'
				]
			]
		);

		$this->start_controls_tabs( 'faq_tabs_layout_4' );

		// Normal State Tab
		$this->start_controls_tab(
			'faq_box_normal_layout_4',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'faq_box_border_normal_layout_4',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group'
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'faq_box_background_normal_layout_4',
				'label'    => esc_html__( 'Background', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group'
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'faq_box_hover_layout_4',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'faq_box_border_hover_layout_4',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group:hover'
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'faq_box_background_hover_layout_4',
				'label'    => esc_html__( 'Background', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group:hover'
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_content_section',
			[
				'label' => __( 'FAQ Content', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-1', 'layout-2', 'layout-3']
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_section_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_section_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'faq_box_content_section_background',
				'label'    => esc_html__( 'Background', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content'
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_box_content_section_typography',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content'
			]
		);

		$this->add_control(
			'faq_box_content_section_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content' => 'color:{{VALUE}};'
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_content_section_layout_4',
			[
				'label' => __( 'FAQ Content', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-4']
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_section_padding_layout_4',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-main-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_section_margin_layout_4',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-main-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'faq_box_content_section_background_layout_4',
				'label'    => esc_html__( 'Background', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group.active'
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'faq_box_content_section_typography_layout_4',
				'selector' => '{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-main-content'
			]
		);

		$this->add_control(
			'faq_box_content_section_color_layout_4',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-main-content' => 'color:{{VALUE}};'
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_box_content_icon',
			[
				'label' => __( 'FAQ List Icon', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-1', 'layout-2', 'layout-3']
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_icon_height',
			[
				'label'      => esc_html__( 'Icon Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconplus' => 'height:{{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconminus'  => 'height:{{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_icon_width',
			[
				'label'      => esc_html__( 'Icon Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconplus' => 'width:{{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconminus'  => 'width:{{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'faq_box_content_icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconplus path' => 'fill:{{VALUE}} ! important;',
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconminus path'  => 'fill:{{VALUE}} ! important;',
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconplus g' => 'stroke:{{VALUE}} ! important;',
					'{{WRAPPER}} .betterdocs-faq-wrapper .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-iconminus g'  => 'stroke:{{VALUE}} ! important;'
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_group_icon_layout_4',
			[
				'label' => __( 'FAQ Group Icon', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'faq_layout_selection' => ['layout-4']
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_icon_height_layout_4',
			[
				'label'      => esc_html__( 'Icon Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-icon svg' => 'height:{{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab img' => 'height:{{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};',
				]
			]
		);

		$this->add_responsive_control(
			'faq_box_content_icon_width_layout_4',
			[
				'label'      => esc_html__( 'Icon Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-icon svg' => 'width:{{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab img' => 'width:{{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'faq_box_content_icon_color_layout_4',
			[
				'label'     => esc_html__( 'Icon Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-icon svg g path' => 'fill:{{VALUE}} ! important;'
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render_callback() {
		$control_values = $this->get_settings_for_display();

		$taxonomy = sanitize_key( $control_values['faq_taxonomy'] ?? 'betterdocs_faq_category' );

		if ( $taxonomy === 'product_assignments' ) {
			if ( ElementorPlugin::instance()->editor->is_edit_mode() ) {
				// Editor preview: show all product FAQ terms so the layout can be styled.
				betterdocs()->views->get(
					'layouts/faq',
					[
						'enable'         => true,
						'have_posts'     => true,
						'layout'         => $control_values['faq_layout_selection'],
						'shortcode_attr' => [
							'group_exclude'         => '',
							'groups'                => '',
							'class'                 => 'betterdocs-faq-' . $control_values['faq_layout_selection'],
							'faq_heading'           => $control_values['faq_layout_section'],
							'faq_section_title_tag' => $control_values['faq_section_title_tag'],
							'faq_group_title_tag'   => $control_values['faq_group_title_tag'],
							'faq_taxonomy'          => 'betterdocs_product_faq_category',
						]
					]
				);
				$this->render_editor_script();
			} else {
				$this->render_product_assignments( $control_values['faq_layout_selection'] );
			}

			return;
		}

		if ( $taxonomy === 'betterdocs_product_faq_category' ) {
			$specific_faqs = ! empty( $control_values['select_specific_product_faq'] ) ? implode( ',', $control_values['select_specific_product_faq'] ) : '';
			$faqs_exclude  = ! empty( $control_values['exclude_specific_product_faq'] ) ? implode( ',', $control_values['exclude_specific_product_faq'] ) : '';
		} else {
			$specific_faqs = ! empty( $control_values['select_specific_faq'] ) ? implode( ',', $control_values['select_specific_faq'] ) : '';
			$faqs_exclude  = ! empty( $control_values['exclude_specific_faq'] ) ? implode( ',', $control_values['exclude_specific_faq'] ) : '';
		}

		betterdocs()->views->get(
			'layouts/faq',
			[
				'enable'         => true,
				'have_posts'     => true,
				'layout'         => $control_values['faq_layout_selection'],
				'shortcode_attr' => [
					'group_exclude'         => $faqs_exclude,
					'class'                 => 'betterdocs-faq-' . $control_values['faq_layout_selection'],
					'groups'                => $specific_faqs,
					'faq_heading'           => $control_values['faq_layout_section'],
					'faq_section_title_tag' => $control_values['faq_section_title_tag'],
					'faq_group_title_tag'   => $control_values['faq_group_title_tag'],
					'faq_taxonomy'          => $taxonomy
				]
			]
		);

		if ( ElementorPlugin::instance()->editor->is_edit_mode() ) {
			$this->render_editor_script();
		}
	}

	private function render_product_assignments( $layout ) {
		if ( ! class_exists( 'WooCommerce' ) || ! is_product() ) {
			return;
		}

		// In theme-builder templates, the widget can render outside the loop,
		// so get_the_ID() can be 0 — fall back to the queried product.
		$product_id = get_the_ID();
		if ( ! $product_id ) {
			$product_id = get_queried_object_id();
		}
		if ( ! $product_id ) {
			return;
		}

		/** @var WooFAQRenderer $renderer */
		$renderer = betterdocs()->container->get( WooFAQRenderer::class );

		// This widget is the explicit placement; don't also inject into the tab/summary.
		$renderer->suppress_auto_placement();
		$renderer->render_for_product( $product_id, $layout );
	}

	protected function render_editor_script() {
		?>
			<script>
				jQuery(document).ready(function($) {
					const $tabs = $(".betterdocs-faq-tab");
					const $mobileTabs = $(".betterdocs-faq-list-wrapper .betterdocs-faq-tab");
					const $contents = $(".betterdocs-faq-list-content");

					$tabs.on("click", function () {
						const termId = $(this).data("term-id");

						// Remove active class from all tabs and hide all contents
						$tabs.removeClass("active");
						$contents.hide().removeClass("active");

						// Reset all icons to default state
						$(".betterdocs-faq-iconplus").show();
						$(".betterdocs-faq-iconminus").hide();

						// Add active class to the clicked tab and show the corresponding content
						$(this).addClass("active");
						$contents.filter(`[data-term-id="${termId}"]`).show().addClass("active");

						// Toggle icons for the active tab
						$(this).find(".betterdocs-faq-iconplus").hide();
						$(this).find(".betterdocs-faq-iconminus").show();
					});

					// Trigger click on the first tab to show the first content by default
					if ($tabs.length > 0) {
						$tabs.first().trigger("click").addClass("active");

						const $mobileFirstTab = $mobileTabs.first();
						$mobileFirstTab.addClass("active");
						$mobileFirstTab.find(".betterdocs-faq-iconplus").hide();
						$mobileFirstTab.find(".betterdocs-faq-iconminus").show();
					}

					$('.betterdocs-faq-post').on('click', function(e) {
						var current_node = $(this);
						var active_list  = $('.betterdocs-faq-group.active');

						if( ! current_node.parent().hasClass('active') ) {
							current_node.parent().addClass('active');
							current_node.children('svg').toggle();
							current_node.next().slideDown();
						}

						for( let node of active_list ) {
							if( $(node).hasClass('active') ) {
								$(node).removeClass('active');
								$(node).children('.betterdocs-faq-post').children('svg').toggle();
								$(node).children('.betterdocs-faq-main-content').slideUp();
							}
						}
					});

					$('.betterdocs-faq-post-layout-2').on('click', function(e) {
						var current_node = $(this);

						if( ! current_node.parent().hasClass('active') ) {
							current_node.parent().addClass('active');
							current_node.children('.betterdocs-faq-post-layout-2-icon-group').children('svg').toggle();
							current_node.next().slideDown();
						} else {
							current_node.parent().removeClass('active');
							current_node.children('.betterdocs-faq-post-layout-2-icon-group').children('svg').toggle();
							current_node.next().slideUp();
						}

					});
				});
			</script>
		<?php
	}
}
