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
use WPDeveloper\BetterDocs\Traits\SocialShare as SocialShareTrait;
use Elementor\Group_Control_Box_Shadow;

class DocShare extends BaseWidget {
	use SocialShareTrait;

	protected $map_view_vars = [
		'share_title' => 'title',
		'share_title_tag' => 'title_tag'
	];

	public function get_name() {
		return 'betterdocs-doc-share';
	}

	public function get_title() {
		return __( 'Doc Share', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-Social-Share';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'share', 'betterdocs', 'heading', 'docs' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-social-share' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {
		$this->share_controls();

		$this->box_style_layout_1();
		$this->box_container_wrapper();
		$this->box_style_layout_2();

		$this->box_title_layout_1();
		$this->box_title_layout_2();

		$this->icon_style();
		$this->icon_style_layout_2();
	}

	public function box_style_layout_1() {
		$this->start_controls_section(
			'section_column_settings',
			[
				'label'     => __( 'Box Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'share_title_select_layout_layout' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'share_box_width',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 2000,
						'step' => 1
					],
					'%'  => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1
					],
					'em' => [
						'min'  => 100,
						'max'  => 100,
						'step' => 1
					]
				],
				'default'    => [
					'unit' => '%',
					'size' => 100
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share' => 'width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'share_box_height',
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
					'{{WRAPPER}} .betterdocs-social-share' => 'height: {{SIZE}}px;'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'share_box_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-social-share'
			]
		);

		$this->add_responsive_control(
			'share_box_space', // Legacy control id but new control
			[
				'label'      => __( 'Box Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'share_box_padding',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'share_box_border_normal',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-social-share'
			]
		);

		$this->add_responsive_control(
			'share_box_radius_normal',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'share_box_shadow_normal',
				'selector' => '{{WRAPPER}} .betterdocs-social-share'
			]
		);

		$this->end_controls_section();
	}

	public function box_container_wrapper() {
		$this->start_controls_section(
			'section_box_container_layout_2',
			[
				'label'     => __( 'Wrapper Container', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'share_title_select_layout_layout' => [ 'layout-2' ]
				]
			]
		);

		$this->add_responsive_control(
			'section_box_container_layout_2_padding',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'section_box_container_layout_2_margin',
			[
				'label'      => __( 'Box Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_box_container_layout_2_background_color',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-social-share.layout-2'
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
					'share_title_select_layout_layout' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'share_box_width_layout_2',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 2000,
						'step' => 1
					],
					'%'  => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1
					],
					'em' => [
						'min'  => 100,
						'max'  => 100,
						'step' => 1
					]
				],
				'default'    => [
					'unit' => '%',
					'size' => 100
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2' => 'width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'share_box_height_layout_2',
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
					'{{WRAPPER}} .betterdocs-social-share.layout-2' => 'height: {{SIZE}}px;'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'share_box_background_layout_2',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2'
			]
		);

		$this->add_responsive_control(
			'share_box_space_layout_2', // Legacy control id but new control
			[
				'label'      => __( 'Box Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'share_box_padding_layout_2',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'share_box_border_normal_layout_2',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2'
			]
		);

		$this->add_responsive_control(
			'share_box_radius_normal_layout_2',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'share_box_shadow_normal_layout_2',
				'selector' => '{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2'
			]
		);

		$this->end_controls_section();
	}

	public function box_title_layout_1() {
		$this->start_controls_section(
			'section_title_settings',
			[
				'label'     => __( 'Title', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'share_title_select_layout_layout' => [ 'layout-1' ]
				]
			]
		);

		$this->add_control(
			'share_box_title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-social-share-heading .betterdocs-social-share-title-tag' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'share_box_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-social-share-heading .betterdocs-social-share-title-tag'
			]
		);

		$this->end_controls_section();
	}

	public function box_title_layout_2() {
		$this->start_controls_section(
			'section_title_settings_layout_2',
			[
				'label'     => __( 'Title', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'share_title_select_layout_layout' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'share_box_title_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-heading.layout-2 h5' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'share_box_title_typography_layout_2',
				'selector' => '{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-heading.layout-2 h5'
			]
		);

		$this->add_responsive_control(
			'section_title_layout_2_padding',
			[
				'label'      => __( 'Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} betterdocs-social-share.layout-2 .betterdocs-social-share-heading.layout-2 h5' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'section_title_layout_2_margin',
			[
				'label'      => __( 'Box Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} betterdocs-social-share.layout-2 .betterdocs-social-share-heading.layout-2 h5' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function share_controls() {
		$this->start_controls_section(
			'section_options',
			[
				'label' => __( 'Controls', 'betterdocs' )
			]
		);

		$this->add_control(
			'share_title_select_layout_layout',
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

		$this->add_control(
			'share_title',
			[
				'label'       => __( 'Title', 'betterdocs' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Share This Article :', 'betterdocs' ),
				'placeholder' => __( 'Type share title here', 'betterdocs' )
			]
		);

		$this->add_control(
			'share_title_tag',
			[
				'label'   => __( 'Title Tag', 'betterdocs' ),
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
			'facebook',
			[
				'label'        => __( 'Facebook', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => '1',
				'default'      => '1'
			]
		);

		$this->add_control(
			'twitter',
			[
				'label'        => __( 'Twitter', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => '1',
				'default'      => '1'
			]
		);

		$this->add_control(
			'linkedin',
			[
				'label'        => __( 'Linkedin', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => '1',
				'default'      => '1'
			]
		);

		$this->add_control(
			'pinterest',
			[
				'label'        => __( 'Pinterest', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => '1',
				'default'      => '1'
			]
		);

		$this->end_controls_section();
	}

	public function icon_style() {
		$this->start_controls_section(
			'section_icon_settings',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'share_title_select_layout_layout' => [ 'layout-1' ]
				]
			]
		);

		$this->add_responsive_control(
			'share_icon_space', // Legacy control id but new control
			[
				'label'      => __( 'Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share-links li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'share_icon_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share-links li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'share_share_icon_area',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share-links li a img' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
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
					'share_title_select_layout_layout' => [ 'layout-2' ]
				]
			]
		);

		$this->add_responsive_control(
			'share_icon_space_layout_2', // Legacy control id but new control
			[
				'label'      => __( 'Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2 li a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'share_icon_padding_layout_2',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2 li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'share_share_icon_area_layout_2',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2 li a, {{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2 li img' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'share_share_icon_inner_area_layout_2',
			[
				'label'      => __( 'Inner Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-social-share.layout-2 .betterdocs-social-share-links.layout-2 li a img' => 'height: {{SIZE}}px;width: {{SIZE}}px;'
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render_callback() {
		$settings = $this->attributes;
		if ( $settings['share_title_select_layout_layout'] == 'layout-1' ) {
			$this->views( 'widgets/social' );
		} else {
			$this->views( 'widgets/social-2' );
		}
	}
}
