<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DocAuthor extends BaseWidget {

	public function get_name() {
		return 'doc-author';
	}

	public function get_title() {
		return __( 'Doc Author', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-Doc-Author';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'date', 'docs', 'betterdocs', 'author', 'doc-author' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-author' ];
	}

	protected function register_controls() {
		$this->wrapper_box();
		$this->author_styles();
		$this->updated_time_styles();
	}

	public function wrapper_box() {
		$this->start_controls_section(
			'author_updated_time_wrapper',
			[
				'label' => __( 'Header Wrapper', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'author_updated_time_wrapper_background_color',
				'types'    => [ 'classic', 'gradient' ],
				'label'    => esc_html__( 'Background Color', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-author-date'
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'author_updated_time_wrapper_background_hover_color',
				'types'    => [ 'classic', 'gradient' ],
				'label'    => esc_html__( 'Background Hover Color', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-author-date:hover'
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'author_updated_time_wrapper_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-author-date'
			]
		);

		$this->add_responsive_control(
			'author_updated_time_wrapper_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-author-date' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'author_updated_time_wrapper_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-author-date' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function author_styles() {
		$this->start_controls_section(
			'author_updated_time_author_section',
			[
				'label' => __( 'Author', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'author_updated_time_author_typography',
				'selector' => '{{WRAPPER}} .betterdocs-author-date .betterdocs-author > span'
			]
		);

		$this->add_control(
			'author_updated_time_author_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-author-date .betterdocs-author > span' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'author_updated_time_author_icon_size',
			[
				'label'      => __( 'Author Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-author-date .betterdocs-author .author-avatar img' => 'height:{{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function updated_time_styles() {
		$this->start_controls_section(
			'updated_time_section',
			[
				'label' => __( 'Updated Time', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'updated_time_typography',
				'selector' => '{{WRAPPER}} .betterdocs-author-date .update-date'
			]
		);

		$this->add_control(
			'updated_time_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-author-date .update-date' => 'color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}


	protected function render_callback() {
		$this->views( 'widgets/author' );
	}
}
