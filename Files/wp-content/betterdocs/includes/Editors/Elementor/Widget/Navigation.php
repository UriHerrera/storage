<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

class Navigation extends BaseWidget {

	public function get_name() {
		return 'betterdocs-navigation';
	}

	public function get_title() {
		return __( 'Doc Navigation', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-Navigation';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'navigation', 'betterdocs', 'docs' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-el-navigation' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {
		$this->layout_select();
		$this->style_controls();
		$this->style_controls_layout_2();
	}

	public function layout_select() {
		$this->start_controls_section(
			'navigation_section_title',
			[
				'label' => __( 'Controls', 'betterdocs' )
			]
		);

		$this->add_control(
			'navigation_layout_select',
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

		$this->end_controls_section();
	}

	public function style_controls() {
		$this->start_controls_section(
			'section_column_settings',
			[
				'label'     => __( 'Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'navigation_layout_select' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'navigation_text_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-navigation a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'navigation_text_typography',
				'selector' => '{{WRAPPER}} .docs-navigation a'
			]
		);

		$this->add_control(
			'navigation_arrow_size',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'px',
					'size' => 35
				],
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .docs-navigation svg' => 'width: {{SIZE}}px;height: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'navigation_arrow_color',
			[
				'label'     => esc_html__( 'Arrow Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-navigation svg' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function style_controls_layout_2() {
		$this->start_controls_section(
			'section_column_settings_layout_2',
			[
				'label'     => __( 'Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'navigation_layout_select' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'navigation_text_background_color_layout_2',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-navigation.layout-2' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'navigation_text_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-navigation.layout-2 a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'navigation_text_typography_layout_2',
				'selector' => '{{WRAPPER}} .docs-navigation.layout-2 a'
			]
		);

		$this->add_control(
			'navigation_arrow_size_layout_2',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'px',
					'size' => 35
				],
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .docs-navigation.layout-2 a svg' => 'width: {{SIZE}}px;height: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'navigation_arrow_color_layout_2',
			[
				'label'     => esc_html__( 'Arrow Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-navigation.layout-2 a svg' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'navigation_margin_layout_2',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .docs-navigation.layout-2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'navigation_padding_layout_2',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .docs-navigation.layout-2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render_callback() {
		$this->views( 'templates/parts/navigation' );
	}

	public function view_params() {
		$layout         = $this->attributes['navigation_layout_select'];
		$default_params = [
			'wrapper_attr' => [
				'class' => [ 'betterdocs-elementor-navigation' ]
			]
		];

		if ( $layout == 'layout-2' ) {
			$default_params['wraper_class'] = 'layout-2';
		}

		return $default_params;
	}
}
