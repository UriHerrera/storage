<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use ElementorPro\Plugin;
use Elementor\Core\Schemes;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use ElementorPro\Base\Base_Widget_Trait as BaseWidgetTrait;

class Title extends BaseWidget {
	use BaseWidgetTrait;

	public $view_wrapper = 'betterdocs-entry-title';

	public function get_name() {
		return 'betterdocs-title';
	}

	public function get_title() {
		return __( 'Doc Title', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-title';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'title', 'heading', 'betterdocs', 'docs' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_title',
			[
				'label' => __( 'Title', 'betterdocs' )
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => __( 'Title', 'betterdocs' ),
				'type'        => Controls_Manager::TEXTAREA,
				'dynamic'     => [
					'active' => true
				],
				'placeholder' => __( 'Enter your title', 'betterdocs' ),
				'default'     => __( 'Add Your Heading Text Here', 'betterdocs' )
			]
		);

		$this->add_control(
			'link',
			[
				'label'     => __( 'Link', 'betterdocs' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => [
					'active' => true
				],
				'default'   => [
					'url' => ''
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'header_size',
			[
				'label'   => __( 'HTML Tag', 'betterdocs' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p'
				],
				'default' => 'h2'
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'     => __( 'Alignment', 'betterdocs' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => __( 'Left', 'betterdocs' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center'  => [
						'title' => __( 'Center', 'betterdocs' ),
						'icon'  => 'eicon-text-align-center'
					],
					'right'   => [
						'title' => __( 'Right', 'betterdocs' ),
						'icon'  => 'eicon-text-align-right'
					],
					'justify' => [
						'title' => __( 'Justified', 'betterdocs' ),
						'icon'  => 'eicon-text-align-justify'
					]
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'view',
			[
				'label'   => __( 'View', 'betterdocs' ),
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'traditional'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_style',
			[
				'label' => __( 'Title', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-entry-title, .betterdocs-entry-title a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .betterdocs-entry-title, .betterdocs-entry-title a'
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .betterdocs-entry-title'
			]
		);

		$this->add_control(
			'blend_mode',
			[
				'label'     => __( 'Blend Mode', 'betterdocs' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''            => __( 'Normal', 'betterdocs' ),
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity'
				],
				'selectors' => [
					'{{WRAPPER}} .betterdocs-entry-title' => 'mix-blend-mode: {{VALUE}}'
				],
				'separator' => 'none'
			]
		);

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__( 'Title Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => false
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-entry-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Title Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => false
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-entry-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();

		$this->update_control(
			'title',
			[
				'dynamic' => [
					'default' => Plugin::elementor()->dynamic_tags->tag_data_to_tag_text( null, 'betterdocs-title-tag' )
				]
			],
			[
				'recursive' => true
			]
		);

		$this->update_control(
			'header_size',
			[
				'default' => 'h1'
			]
		);
	}

	public function render_callback() {
		$this->views( 'widgets/title' );
	}

	public function view_params() {
		$this->add_inline_editing_attributes( 'title' );
		$title = $this->attributes['title'];

		if ( ! empty( $this->attributes['link']['url'] ) ) {
			$this->add_link_attributes( 'url', $this->attributes['link'] );
			$title = sprintf( '<a %1$s>%2$s</a>', $this->get_render_attribute_string( 'url' ), $title );
		}

		return [
			'tag'          => $this->attributes['header_size'],
			'title'        => $title,
			'wrapper_attr' => $this->get_render_attributes( 'title' )
		];
	}

	public function render_plain_content() {}
}
