<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget\Basic;


// Doc/category-listing primitives (meta_query/tax_query, post__not_in/exclude)
// are intrinsic to BetterDocs' KB / category / FAQ filters and intentional.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Plugin as ElementorPlugin;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use WPDeveloper\BetterDocs\Editors\Elementor\Traits\TemplateQuery;

class CategorySlateLayout extends BaseWidget {
	use TemplateQuery;

	public function get_name() {
		return 'betterdocs-category-slate-layout';
	}

	public function get_title() {
		return __( 'BetterDocs Slate Layout', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-category-slate-layout';
	}

	public function get_categories() {
		return [ 'docs-archive', 'betterdocs-elements' ];
	}

	public function get_keywords() {
		return [
			'knowledgebase',
			'knowledge base',
			'documentation',
			'Doc',
			'kb',
			'betterdocs',
			'docs',
			'category-slate',
			'slate-layout'
		];
	}

	public function get_style_depends() {
		return [ 'betterdocs-category-grid', 'betterdocs-el-category-grid', 'betterdocs-fontawesome' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-el-category-grid' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/';
	}

	protected function register_controls() {
		/**
		 * Query Controls!
		 * @source BaseWidget
		 */
		$this->betterdocs_do_action();

		/**
		 * ----------------------------------------------------------
		 * Section: Layout Options
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'select_layout',
			[
				'label' => __( 'Layout Options', 'betterdocs' )
			]
		);

		$this->add_responsive_control(
			'grid_column',
			[
				'label'              => __( 'Grid Column', 'betterdocs' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => '3',
				'tablet_default'     => '2',
				'mobile_default'     => '1',
				'options'            => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6'
				],
				'prefix_class'       => 'elementor-grid%s-',
				'render_type'        => 'template',
				'frontend_available' => true,
				'label_block'        => true,
				'selectors'          => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper' => 'grid-template-columns: repeat({{VALUE}}, 1fr);'
				]
			]
		);

		$this->add_responsive_control(
			'grid_space',
			[
				'label'       => __( 'Grid Space', 'betterdocs' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'default'     => 48,
				'render_type' => 'template',
				'selectors'   => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper' => 'margin-bottom: {{VALUE}}px;',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper'                                     => 'gap: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Category Icon', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true'
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'     => __( 'Select Tag', 'betterdocs' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => [
					'h1'   => __( 'H1', 'betterdocs' ),
					'h2'   => __( 'H2', 'betterdocs' ),
					'h3'   => __( 'H3', 'betterdocs' ),
					'h4'   => __( 'H4', 'betterdocs' ),
					'h5'   => __( 'H5', 'betterdocs' ),
					'h6'   => __( 'H6', 'betterdocs' ),
					'span' => __( 'Span', 'betterdocs' ),
					'p'    => __( 'P', 'betterdocs' ),
					'div'  => __( 'Div', 'betterdocs' )
				]
			]
		);

		$this->add_control(
			'category_link',
			[
				'label'        => __( 'Category Title Link', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'betterdocs' ),
				'label_off'    => __( 'Off', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => false
			]
		);

		$this->end_controls_section(); # end of 'Layout Options'

		/**
		 * ----------------------------------------------------------
		 * Section: Container Style
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_container_style',
			[
				'label' => __( 'Container', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'container_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex'
			]
		);

		$this->add_responsive_control(
			'container_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex'
			]
		);

		$this->end_controls_section(); # end of 'Container Style'

		/**
		 * ----------------------------------------------------------
		 * Section: Grid Style
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_grid_style',
			[
				'label' => __( 'Grid', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'grid_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex .betterdocs-single-category-wrapper',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_responsive_control(
			'grid_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex .betterdocs-single-category-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'grid_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex .betterdocs-single-category-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'grid_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex .betterdocs-single-category-wrapper'
			]
		);

		$this->add_responsive_control(
			'grid_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex .betterdocs-single-category-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'grid_box_shadow',
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .layout-flex .betterdocs-single-category-wrapper'
			]
		);

		$this->end_controls_section(); # end of 'Grid Style'

		/**
		 * ----------------------------------------------------------
		 * Section: Category Icon Style
		 * ----------------------------------------------------------
		 */
		$this->category_icon_style();

		/**
		 * ----------------------------------------------------------
		 * Section: Title Style
		 * ----------------------------------------------------------
		 */
		$this->title_style();

		/**
		 * ----------------------------------------------------------
		 * Section: List Style
		 * ----------------------------------------------------------
		 */
		$this->list_style();
	}

	protected function category_icon_style() {
		$this->start_controls_section(
			'section_category_icon_style',
			[
				'label' => __( 'Category Icon', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_responsive_control(
			'category_icon_size',
			[
				'label'      => esc_html__( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->start_controls_tabs( 'category_icon_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'icon_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'icon_background_normal',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon',
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
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon'
			]
		);

		$this->add_responsive_control(
			'icon_border_radius_normal',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'icon_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'icon_spacing',
			[
				'label'              => esc_html__( 'Spacing', 'betterdocs' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => [ 'px', 'em', '%' ],
				'selectors'          => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'icon_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'icon_background_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon:hover'
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'icon_border_hover',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon:hover'
			]
		);

		$this->add_responsive_control(
			'icon_border_radius_hover',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'icon_transition',
			[
				'label'      => __( 'Transition', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'size' => 300,
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 2500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon' => 'transition: {{SIZE}}ms;'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section(); # end of 'Category Icon Style'
	}

	protected function title_style() {
		$this->start_controls_section(
			'section_title_style',
			[
				'label' => __( 'Title', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))'
			]
		);

		$this->start_controls_tabs( 'title_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'title_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'title_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'title_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a:hover, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'title_background_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a:hover, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'title_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))'
			]
		);

		$this->add_responsive_control(
			'title_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title a, {{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-category-title:not(:has(a))' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Title Style'
	}

	protected function list_style() {
		$this->start_controls_section(
			'section_list_style',
			[
				'label' => __( 'List', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'list_typography',
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a'
			]
		);

		$this->start_controls_tabs( 'list_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'list_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_control(
			'list_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'list_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'list_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_control(
			'list_hover_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a:hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'list_background_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'list_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'list_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'list_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list'
			]
		);

		$this->add_responsive_control(
			'list_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-three-wrapper .betterdocs-articles-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'List Style'
	}

	protected function render_callback() {
		$this->views( 'layouts/base' );
	}

	public function view_params() {
		$settings = &$this->attributes;

		$wrapper_attr = [
			'id'    => 'el-betterdocs-cat-tech-' . esc_attr( $this->get_id() ),
			'class' => [ 'betterdocs-category-grid-three-wrapper' ]
		];

		$inner_wrapper_attr = [
			'class' => [
				'betterdocs-category-grid-inner-wrapper',
				'betterdocs-category-grid',
				'layout-flex',
				'docs-col-4',
			],
		];

		$default_multiple_kb = (bool) betterdocs()->editor->get( 'elementor' )->multiple_kb_status();

		$is_edit_mode = ElementorPlugin::instance()->editor->is_edit_mode();

		$terms_query = [
			'hide_empty'         => true, // Let template handle editor mode logic
			'taxonomy'           => 'doc_category',
			'orderby'            => $settings['orderby'],
			'order'              => $settings['order'],
			'offset'             => $settings['offset'],
			'number'             => $settings['box_per_page'],
			'nested_subcategory' => false
		];

		if ( $settings['include'] ) {
			$terms_query['include'] = array_diff( $settings['include'], (array) $settings['exclude'] );
		}

		if ( $settings['exclude'] ) {
			$terms_query['exclude'] = $settings['exclude'];
		}

		if ( $default_multiple_kb ) { // Let template handle editor mode logic
			$object = get_queried_object();
			if ( empty( $settings['selected_knowledge_base'] ) && is_tax( 'knowledge_base' ) ) {
				$meta_value = isset( $object->slug ) ? $object->slug : '';
			} else {
				$meta_value = $settings['selected_knowledge_base'];
			}

			// Only apply meta_query if we have a valid knowledge base value
			if ( ! empty( $meta_value ) ) {
				$terms_query['meta_query'] = [
					'relation' => 'OR',
					[
						'key'     => 'doc_category_knowledge_base',
						'value'   => $meta_value,
						'compare' => 'LIKE'
					]
				];
			}
		}

		$kb_slug = isset( $meta_value ) ? $meta_value : ( isset( $settings['selected_knowledge_base'] ) ? $settings['selected_knowledge_base'] : '' );

		/**
		 * Add This Attribute When Using Outside Betterdocs Templates Only
		 */
		if ( $default_multiple_kb == 1 && ( ! empty( $kb_slug ) ) && ( ! betterdocs()->helper->is_templates() ) ) {
			$inner_wrapper_attr['data-mkb-slug'] = $kb_slug;
		}

		$this->add_render_attribute(
			'bd_category_tech_wrapper',
			$wrapper_attr
		);

		$this->add_render_attribute(
			'bd_category_tech_inner',
			$inner_wrapper_attr
		);

		$terms_query_args = $this->betterdocs( 'query' )->terms_query( $terms_query );

		$default_params = [
			'wrapper_attr'            => $this->get_render_attributes( 'bd_category_tech_wrapper' ),
			'inner_wrapper_attr'      => $this->get_render_attributes( 'bd_category_tech_inner' ),
			'widget_type'             => 'category-grid',
			'layout'                  => 'layout-3',
			'is_edit_mode'            => $is_edit_mode,
			'terms_query_args'        => $terms_query_args,
			'docs_query_args'         => [
				'posts_per_page'     => isset( $settings['post_per_page'] ) ? $settings['post_per_page'] : 5,
				'orderby'            => isset( $settings['post_orderby'] ) ? $settings['post_orderby'] : 'betterdocs_order',
				'order'              => isset( $settings['post_order'] ) ? $settings['post_order'] : 'ASC',
				'nested_subcategory' => false
			],
			'grid_column'     		  => $settings['grid_column'],
			'show_count'              => false,
			'count_suffix'            => '',
			'count_suffix_singular'   => '',
			'show_header'     		  => true,
			'show_list'     		  => true,
			'show_title'              => true,
			'show_icon'     		  => $settings['show_icon'],
			'category_icon'           => 'folder',
			'title_tag'     		  => $settings['title_tag'],
			'category_title_link'     => $settings['category_link'],
			'layout_type'             => 'widget',
			'multiple_knowledge_base' => ( $default_multiple_kb && ! empty( $kb_slug ) ) ? true : false,
			'kb_slug'                 => $kb_slug
		];

		return apply_filters( 'betterdocs_elementor_category_tech_layout_params', $default_params, $this );
	}
}
