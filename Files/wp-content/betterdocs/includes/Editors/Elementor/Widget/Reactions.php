<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Reactions extends BaseWidget {
	public $view_wrapper = 'betterdocs-article-reactions';

	public function get_name() {
		return 'betterdocs-reactions';
	}

	public function get_title() {
		return __( 'Doc Reactions', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-Reactions';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'reaction', 'betterdocs', 'heading', 'docs' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-reactions' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-reactions' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'reactions_layout_options',
			[
				'label' => __( 'Layout Options', 'betterdocs' )
			]
		);

		$this->add_control(
			'reactions_layout_template',
			[
				'label'       => __( 'Select Layout', 'betterdocs' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => [
					'layout-1' => esc_html__( 'Layout 1', 'betterdocs' ),
					'layout-2' => esc_html__( 'Layout 2', 'betterdocs' ),
					'layout-3' => esc_html__( 'Layout 3', 'betterdocs' )
				],
				'default'     => 'layout-1',
				'label_block' => true
			]
		);

		$this->add_control(
			'happy',
			[
				'label'        => esc_html__( 'Happy', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'betterdocs' ),
				'label_off'    => esc_html__( 'Hide', 'betterdocs' ),
				'return_value' => true,
				'default'      => true,
				'condition'    => [
					'reactions_layout_template' => [ 'layout-1' ,'layout-2', 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'happy_icon',
			[
				'label'     => esc_html__( 'Happy Icon', 'betterdocs' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => [
					'reactions_layout_template' => [ 'layout-1', 'layout-2', 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'normal',
			[
				'label'        => esc_html__( 'Normal', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'betterdocs' ),
				'label_off'    => esc_html__( 'Hide', 'betterdocs' ),
				'return_value' => true,
				'default'      => true,
				'condition'    => [
					'reactions_layout_template' => [ 'layout-1', 'layout-2', 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'normal_icon',
			[
				'label'     => esc_html__( 'Normal Icon', 'betterdocs' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => [
					'reactions_layout_template' => [ 'layout-1', 'layout-2', 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'sad',
			[
				'label'        => esc_html__( 'Sad', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'betterdocs' ),
				'label_off'    => esc_html__( 'Hide', 'betterdocs' ),
				'return_value' => true,
				'default'      => true,
				'condition'    => [
					'reactions_layout_template' => [ 'layout-1', 'layout-2', 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'sad_icon',
			[
				'label'     => esc_html__( 'Sad Icon', 'betterdocs' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => [
					'reactions_layout_template' => [ 'layout-1', 'layout-2', 'layout-3' ]
				]
			]
		);

		$this->end_controls_section();

		$this->box_style_layout_1();
		$this->title_style_layout_1();
		$this->icon_style_layout_1();

		$this->box_style_layout_2();
		$this->title_style_layout_2();
		$this->icon_style_layout_2();

		$this->box_style_layout_3();
		$this->title_style_layout_3();
		$this->icon_style_layout_3();
	}

	public function box_style_layout_1() {
		$this->start_controls_section(
			'section_column_settings',
			[
				'label'     => __( 'Box Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'reaction_box_width',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 2500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_height',
			[
				'label'      => __( 'Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'height: {{SIZE}}px;'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'reaction_box_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions'
			]
		);

		$this->add_responsive_control(
			'reaction_box_space', // Legacy control id but new control
			[
				'label'      => __( 'Box Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'reaction_box_padding',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'reaction_box_border_normal',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions'
			]
		);

		$this->add_responsive_control(
			'reaction_box_radius_normal',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'reaction_box_shadow_normal',
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions'
			]
		);

		$this->end_controls_section();
	}

	public function box_style_layout_2() {
		$this->start_controls_section(
			'section_column_settings_layout_2',
			[
				'label'     => __( 'Box Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'reaction_box_width_layout_2',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 2500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions' => 'width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_height_layout_2',
			[
				'label'      => __( 'Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions' => 'height: {{SIZE}}px;'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'reaction_box_background_layout_2',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions'
			]
		);

		$this->add_responsive_control(
			'reaction_box_space_layout_2', // Legacy control id but new control
			[
				'label'      => __( 'Box Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'reaction_box_padding_layout_2',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'reaction_box_border_normal_layout_2',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions .betterdocs-article-reactions-box'
			]
		);

		$this->add_responsive_control(
			'reaction_box_radius_normal_layout_2',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}}  .betterdocs-elementor.betterdocs-article-reactions .betterdocs-article-reactions-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'reaction_box_shadow_normal_layout_2',
				'selector' => '{{WRAPPER}} .betterdocs-elementor.betterdocs-article-reactions'
			]
		);

		$this->end_controls_section();
	}

	public function box_style_layout_3() {
		$this->start_controls_section(
			'section_column_settings_layout_3',
			[
				'label'     => __( 'Box Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'reaction_box_width_layout_3',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 2500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_height_layout_3',
			[
				'label'      => __( 'Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'height: {{SIZE}}px;'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'reaction_box_background_layout_3',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions'
			]
		);

		$this->add_responsive_control(
			'reaction_box_space_layout_3', // Legacy control id but new control
			[
				'label'      => __( 'Box Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'reaction_box_padding_layout_3',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'reaction_box_border_normal_layout_3',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions-box'
			]
		);

		$this->add_responsive_control(
			'reaction_box_radius_normal_layout_3',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}}  .betterdocs-article-reactions-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'reaction_box_shadow_normal_layout_3',
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions-box'
			]
		);

		$this->end_controls_section();
	}

	public function title_style_layout_1() {
		$this->start_controls_section(
			'section_title_settings',
			[
				'label'     => __( 'Title', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'reaction_text',
			[
				'label'   => __( 'Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'What are your feelings', 'betterdocs' )
			]
		);

		$this->add_control(
			'reaction_text_tag',
			[
				'label'   => __( 'Text Tag', 'betterdocs' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h5',
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
			]
		);

		$this->add_control(
			'reaction_box_title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'reaction_box_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag'
			]
		);

		$this->end_controls_section();
	}

	public function title_style_layout_2() {
		$this->start_controls_section(
			'section_title_settings_layout_2',
			[
				'label'     => __( 'Title', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'reaction_text_layout_2',
			[
				'label'   => __( 'Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'What are your feelings', 'betterdocs' )
			]
		);

		$this->add_control(
			'reaction_text_tag_layout_2',
			[
				'label'   => __( 'Text Tag', 'betterdocs' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h5',
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
			]
		);

		$this->add_control(
			'reaction_box_title_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box p' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'reaction_box_title_typography_layout_2',
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box p'
			]
		);

		$this->end_controls_section();
	}

	public function title_style_layout_3() {
		$this->start_controls_section(
			'section_title_settings_layout_3',
			[
				'label'     => __( 'Title', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'reaction_text_layout_3',
			[
				'label'   => __( 'Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Was it helpful ?', 'betterdocs' )
			]
		);

		$this->add_control(
			'reaction_text_tag_layout_3',
			[
				'label'   => __( 'Text Tag', 'betterdocs' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h5',
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
			]
		);

		$this->add_control(
			'reaction_box_title_color_layout_3',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-reactions-title-tag' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'reaction_box_title_typography_layout_3',
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-reactions-title-tag'
			]
		);

		$this->end_controls_section();
	}


	public function icon_style_layout_1() {
		$this->start_controls_section(
			'section_icon_settings',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_area',
			[
				'label'      => __( 'Area', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reaction-links li a' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_size',
			[
				'label'      => __( 'Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reaction-links li a svg' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_background',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reaction-links li a' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_hover_background',
			[
				'label'     => esc_html__( 'Hover Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reaction-links li a:hover' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reaction-links li a svg path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reaction-links li a:hover svg path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function icon_style_layout_2() {
		$this->start_controls_section(
			'section_icon_settings_layout_2',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_area_layout_2',
			[
				'label'      => __( 'Area', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box .layout-2 li a.betterdocs-emoji' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_background_layout_2',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box .layout-2 li a.betterdocs-emoji' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_hover_background_layout_2',
			[
				'label'     => esc_html__( 'Hover Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box .layout-2 li a.betterdocs-emoji:hover' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box .layout-2 li a.betterdocs-emoji svg path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_hover_color_layout_2',
			[
				'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-box .layout-2 li a.betterdocs-emoji svg path:hover' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function icon_style_layout_3() {
		$this->start_controls_section(
			'section_icon_settings_layout_3',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'reactions_layout_template' => [ 'layout-3' ]
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_area_layout_3',
			[
				'label'      => __( 'Area', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions .layout-3 li a.betterdocs-emoji svg' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_background_layout_3',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .layout-3 li a.betterdocs-emoji' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_hover_background_layout_3',
			[
				'label'     => esc_html__( 'Hover Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .layout-3 li a.betterdocs-emoji:hover' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_color_layout_3',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-article-reaction-links li a svg path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'reaction_box_icon_hover_color_layout_3',
			[
				'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-article-reaction-links li a svg:hover path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'reaction_box_icon_text_layout_3',
				'selector' => '{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-article-reaction-links.layout-3 li .betterdocs-tooltip'
			]
		);

		$this->add_responsive_control(
			'reaction_box_icon_main_wrapper_padding_layout_3', // Legacy control id but new control
			[
				'label'      => __( 'List Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-article-reaction-links.layout-3 li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'reaction_box_icon_main_wrapper_margin_layout_3', // Legacy control id but new control
			[
				'label'      => __( 'List Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-article-reaction-links.layout-3 li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function generate_attributes() {
		$attributes = [
			'class' => [
				$this->attributes['reactions_layout_template']
			]
		];

		return $attributes;
	}

	protected function render_callback() {
		$layout = &$this->attributes['reactions_layout_template'];
		if ( $layout == 'layout-1' ) {
			$this->views( 'widgets/reactions' );
		} elseif ( $layout == 'layout-2' ) {
			$this->views( 'widgets/reactions-2' );
		} else {
			$this->views( 'widgets/reactions-3' );
		}
	}

	public function view_params() {
		$settings     = &$this->attributes;
		$wrapper_attr = $this->generate_attributes();
		return [
			'wrapper_attr'   => $wrapper_attr,
			'reactions_text' => $settings['reactions_layout_template'] == 'layout-1' ? $this->attributes['reaction_text'] : ( $settings['reactions_layout_template'] == 'layout-2' ? $this->attributes['reaction_text_layout_2'] : $this->attributes['reaction_text_layout_3'] ),
			'text_tag'       => $settings['reactions_layout_template'] == 'layout-1' ? $this->attributes['reaction_text_tag'] : ( $settings['reactions_layout_template'] == 'layout-2' ? $this->attributes['reaction_text_tag_layout_2'] : $this->attributes['reaction_text_tag_layout_3'] ),
			'happy'          => $settings['happy'],
			'happy_icon'     => is_array( $settings['happy_icon'] ) ? $settings['happy_icon']['url'] : '',
			'normal'         => $settings['normal'],
			'normal_icon'    => is_array( $settings['normal_icon'] ) ? $settings['normal_icon']['url'] : '',
			'sad'            => $settings['sad'],
			'sad_icon'       => is_array( $settings['sad_icon'] ) ? $settings['sad_icon']['url'] : ''
		];
	}
}
