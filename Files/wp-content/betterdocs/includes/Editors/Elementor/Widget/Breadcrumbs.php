<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

class Breadcrumbs extends BaseWidget {

	public function get_name() {
		return 'betterdocs-breadcrumb';
	}

	public function get_title() {
		return __( 'Doc Breadcrumbs', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-Breadcrumbs';
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'breadcrumbs', 'internal links', 'docs', 'betterdocs' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	public function get_categories() {
		return [ 'betterdocs-elements', 'betterdocs-elements-single', 'docs-archive' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-breadcrumb' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_betterdocs_breadcrumb_layout_settings',
			[
				'label' => __( 'Layout', 'betterdocs' )
			]
		);

		$this->add_control(
			'betterdocs_breadcrumb_sidebar_layout',
			[
				'label'       => esc_html__( 'Select layout', 'betterdocs' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'layout-1',
				'label_block' => false,
				'options'     => [
					'layout-1' => esc_html__( 'Layout 1', 'betterdocs' ),
					'layout-2' => esc_html__( 'Layout 2', 'betterdocs' )
				]
			]
		);

		$this->end_controls_section(); # end of 'Select Layout'

		$this->breadcrumb_style();
		$this->icon_style();

		$this->breadcrumb_style_layout_2();
		$this->icon_style_layout_2();
	}

	public function breadcrumb_style() {
		$this->start_controls_section(
			'section_betterdocs_breadcrumbs_style',
			[
				'label'     => __( 'Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'betterdocs_breadcrumb_sidebar_layout' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'active_link_color',
			[
				'label'     => __( 'Active Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item.current span' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'active_link_color_hover',
			[
				'label'     => __( 'Active Text Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item.current span:hover' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'link_color',
			[
				'label'     => __( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-item > a' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'link_color_hover',
			[
				'label'     => __( 'Text Color Hover', 'betterdocs' ),
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
				'label'     => __( 'Alignment', 'betterdocs' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'betterdocs' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center'     => [
						'title' => __( 'Center', 'betterdocs' ),
						'icon'  => 'eicon-text-align-center'
					],
					'flex-end'   => [
						'title' => __( 'Right', 'betterdocs' ),
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
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'breadcrumb_layout_1_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-breadcrumb .betterdocs-breadcrumb-list' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function breadcrumb_style_layout_2() {
		$this->start_controls_section(
			'section_betterdocs_breadcrumbs_style_layout_2',
			[
				'label'     => __( 'Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'betterdocs_breadcrumb_sidebar_layout' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'active_link_color_layout_2',
			[
				'label'     => __( 'Active Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-item.current span' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'active_link_color_hover_layout_2',
			[
				'label'     => __( 'Active Text Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}  #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-item.current span:hover' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'link_color_layout_2',
			[
				'label'     => __( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-item > a' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'link_color_hover_layout_2',
			[
				'label'     => __( 'Text Color Hover', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-item > a:hover' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_control(
			'background_color_layout_2',
			[
				'label'     => __( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-list' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'background_hover_color_layout_2',
			[
				'label'     => __( 'Background Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-list:hover' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typography_layout_2',
				'selector' => '{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-item a,{{WRAPPER}} .betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-item span'
			]
		);

		$this->add_responsive_control(
			'alignment_layout_2',
			[
				'label'     => __( 'Alignment', 'betterdocs' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'betterdocs' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center'     => [
						'title' => __( 'Center', 'betterdocs' ),
						'icon'  => 'eicon-text-align-center'
					],
					'flex-end'   => [
						'title' => __( 'Right', 'betterdocs' ),
						'icon'  => 'eicon-text-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-list' => 'justify-content: {{VALUE}}'
				]
			]
		);

		$this->add_responsive_control(
			'breadcrumb_layout_2_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'breadcrumb_layout_2_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 0,
					'right'    => 9,
					'bottom'   => 0,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function icon_style_layout_2() {
			/**
		 * ----------------------------------------------------------
		 * Section: Icon Style
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_article_settings_layout_2',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'betterdocs_breadcrumb_sidebar_layout' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'breadcrumbs_icon_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-list .betterdocs-breadcrumb-item .icon-container svg path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'breadcrumbs_icon_size_layout_2',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
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

	public function icon_style() {
		/**
		 * ----------------------------------------------------------
		 * Section: Icon Style
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_article_settings',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'betterdocs_breadcrumb_sidebar_layout' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'breadcrumbs_icon_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-breadcrumb .breadcrumb-delimiter' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'breadcrumbs_icon_size',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
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
		$settings = &$this->attributes;

		$params = [
			'breadcrumbs_layout' => $settings['betterdocs_breadcrumb_sidebar_layout']
		];

		return $params;
	}

	protected function render_callback() {
		$this->views( 'widgets/breadcrumbs' );
	}

	public function render_plain_content() {}
}
