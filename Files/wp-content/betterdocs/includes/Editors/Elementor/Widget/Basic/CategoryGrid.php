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

class CategoryGrid extends BaseWidget {
	use TemplateQuery;

	public function get_name() {
		return 'betterdocs-category-grid';
	}

	public function get_title() {
		return __( 'BetterDocs Category Grid', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-category-grid';
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
			'category-grid'
		];
	}

	public function get_style_depends() {
		return [ 'betterdocs-category-grid', 'betterdocs-el-category-grid', 'betterdocs-fontawesome' ];
	}

	public function get_script_depends() {
		return [ 'masonry', 'betterdocs-el-category-grid' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/betterdocs-category-grid';
	}

	protected function register_controls() {
		/**
		 * Query  Controls!
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

		$this->add_control(
			'layout_template',
			[
				'label'       => __( 'Select Layout', 'betterdocs' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => betterdocs()->views->get_layouts(),
				'default'     => betterdocs()->views->get_default_layout(),
				'label_block' => true
			]
		);

		$this->add_control(
			'layout_mode',
			[
				'label'       => __( 'Layout Mode', 'betterdocs' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => [
					'grid'          => __( 'Grid', 'betterdocs' ),
					'fit-to-screen' => __( 'Fit to Screen', 'betterdocs' ),
					'masonry'       => __( 'Masonry', 'betterdocs' )
				],
				'default'     => 'grid',
				'label_block' => true
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
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper' => '--column: {{VALUE}};'
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
				'default'     => 10,
				'condition'   => [
					'layout_mode' => 'masonry'
				],
				'render_type' => 'template',
				'selectors'   => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper' => 'margin-bottom: {{VALUE}}px;',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper'                                     => '--gap: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'show_header',
			[
				'label'        => __( 'Show Header', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true'
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
					'show_header' => 'true'
				]
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'        => __( 'Show Title', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true',
				'condition'    => [
					'show_header' => 'true'
				]
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
				],
				'condition' => [
					'show_title'  => 'true',
					'show_header' => 'true'
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
					'show_header' => 'true'
				]
			]
		);

		$this->add_control(
			'count_prefix',
			[
				'label'     => __( 'Prefix', 'betterdocs' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'condition' => [
					'show_count'      => 'true',
					'layout_template' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'count_suffix',
			[
				'label'     => __( 'Suffix', 'betterdocs' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'condition' => [
					'show_count'      => 'true',
					'layout_template' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'count_suffix_singular',
			[
				'label'     => __( 'Suffix Singular', 'betterdocs' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'condition' => [
					'show_count'      => 'true',
					'layout_template' => [ 'layout-2' ]
				]
			]
		);

		$this->add_control(
			'show_list',
			[
				'label'        => __( 'Show List', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true'
			]
		);

		$this->add_control(
			'show_button',
			[
				'label'        => __( 'Show Button', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true'
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

		$this->add_control(
			'button_text',
			[
				'label'     => __( 'Button Text', 'betterdocs' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [
					'active' => true
				],
				'default'   => __( 'Explore More', 'betterdocs' ),
				'condition' => [
					'show_button' => 'true'
				]
			]
		);

		$this->end_controls_section(); #end of section 'Layout Options'

		/**
		 * ----------------------------------------------------------
		 * Section: Container Section Settings
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_grid_container_section',
			[
				'label' => __( 'Container Section', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_responsive_control(
			'section_grid_container_section_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'section_grid_container_section_margin', // Legacy control id but new control
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-category-grid-inner-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_card_container_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-category-grid-inner-wrapper'
			]
		);

		$this->end_controls_section();

		/**
		 * ----------------------------------------------------------
		 * Section: Column Settings
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_column_settings',
			[
				'label' => __( 'Grid', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->start_controls_tabs( 'grid_style_tab' );

		// Normal State Tab
		$this->start_controls_tab(
			'grid_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'grid_bg', // Legacy control id 'content_area_bg'
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner'
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'grid_box_shadow',
				'label'    => __( 'Box Shadow', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner'
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'grid_border',
				'label'    => __( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner'
			]
		);

		$this->add_responsive_control(
			'grid_border_radius',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'grid_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'grid_bg_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner:hover'
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'grid_hover_box_shadow',
				'label'    => __( 'Box Shadow', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner:hover'
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'grid_hover_border',
				'label'    => __( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner:hover'
			]
		);

		$this->add_responsive_control(
			'grid_hover_border_radius',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs(); # end of $this->start_controls_tabs('grid_style_tab');

		$this->add_responsive_control(
			'grid_padding',
			[
				'label'      => __( 'Grid Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'separator'  => 'before'
			]
		);

		$this->add_responsive_control(
			'column_padding', // Legacy control id
			[
				'label'      => __( 'Grid Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper>.betterdocs-single-category-wrapper .betterdocs-single-category-inner' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition'  => [
					'layout_mode' => [ 'grid', 'fit-to-screen' ]
				]
			]
		);

		$this->end_controls_section(); # end of 'Column Settings'

		$this->start_controls_section(
			'section_icon_settings',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_icon'       => 'true',
					'layout_template' => 'default'
				]
			]
		);

		$this->start_controls_tabs( 'icon_settings_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'icon_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_icon_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_icon_border', // Legacy control name change it with 'border_size' if anything happens.
				'label'    => __( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon'
			]
		);

		$this->add_control(
			'header_icon_border_radius',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
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
				'name'     => 'header_icon_bg_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_icon_border_hover', // Legacy control name change it with 'border_size' if anything happens.
				'label'    => __( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon:hover'
			]
		);

		$this->add_control(
			'header_icon_border_radius_hover',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'header_icon_size',
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
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon .betterdocs-category-icon-img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				],
				'separator'  => 'before'
			]
		);

		$this->add_responsive_control(
			'header_icon_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'header_icon_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Column Settings'

		/**
		 * ----------------------------------------------------------
		 * Section: Title Settinggs
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_title_settings',
			[
				'label'     => __( 'Title', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_title' => 'true'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'cat_list_typography',
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-title a, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-title:not(a)'
			]
		);

		$this->start_controls_tabs( 'title_settings_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'title_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_control(
			'cat_title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-title a, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-title:not(a)' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'cat_title_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper:not(.layout-2) .betterdocs-category-header, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-header .betterdocs-category-title',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'title_border', // Legacy control name change it with 'border_size' if anything happens.
				'label'          => __( 'Border', 'betterdocs' ),
				'fields_options' => [
					'border' => [
						'default' => 'solid'
					],
					'width'  => [
						'default' => [
							'top'      => '0',
							'right'    => '0',
							'bottom'   => '2',
							'left'     => '0',
							'isLinked' => false
						]
					],
					'color'  => [
						'default' => '#8E0AE9'
					]
				],
				'selector'       => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper:not(.layout-2) .betterdocs-category-header-inner',
				'condition'      => [
					'layout_template' => 'default'
				]
			]
		);

		$this->add_control(
			'title_border_radius',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-header .betterdocs-category-title'      => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header'       => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
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
			'cat_title_color_hover',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-title a:hover, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-title:not(a):hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'cat_title_bg_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper:not(.layout-2) .betterdocs-category-header:hover, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-header .betterdocs-category-title:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'title_border_hover', // Legacy control name change it with 'border_size' if anything happens.
				'label'    => __( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header-inner:hover, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-header .betterdocs-category-title:hover'
			]
		);

		$this->add_control(
			'title_border_radius_hover',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-header .betterdocs-category-title:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'cat_title_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-single-category-wrapper.layout-2 .betterdocs-category-title'                                 => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'
				],
				'separator'  => 'before'
			]
		);

		$this->add_responsive_control(
			'cat_title_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-single-category-wrapper.layout-2 .betterdocs-category-title'                                 => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Title Settings'

		/**
		 * ----------------------------------------------------------
		 * Section: Count Settinggs
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_count_settings',
			[
				'label'     => __( 'Count', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_count' => 'true'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'count_font_size',
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts span'
			]
		);

		$this->add_responsive_control(
			'count_size',
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
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'default'
				]
			]
		);

		$this->start_controls_tabs( 'count_settings_tabs' );

		// Normal State Tab
		$this->start_controls_tab(
			'count_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_control(
			'count_color',
			[
				'label'     => esc_html__( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts span' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'count_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                    {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts,
                    {{WRAPPER}} .betterdocs-elementor .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts
                ',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_control(
			'count_ticker_color',
			[
				'label'     => esc_html__( 'Ticker Background', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:after' => 'border-top-color: {{VALUE}};'
				],
				'condition' => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->add_responsive_control(
			'count_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->add_responsive_control(
			'count_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->add_responsive_control(
			'count_height',
			[
				'label'      => __( 'Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'%' => [
						'max'  => 100,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor.betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-category-items-counts' => 'height: {{SIZE}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->add_responsive_control(
			'count_width',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'%' => [
						'max'  => 300,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor.betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-category-items-counts' => 'width: {{SIZE}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'count_border', // Legacy control name change it with 'border_size' if anything happens.
				'label'     => __( 'Border', 'betterdocs' ),
				'selector'  => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts',
				'condition' => [
					'layout_template' => 'default'
				]
			]
		);

		$this->add_control(
			'count_border_radius',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'default'
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
					'layout_template' => 'default'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'count_bg_second',
				'types'     => [ 'classic', 'gradient' ],
				'selector'  => '
                    {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts span
                ',
				'exclude'   => [
					'image'
				],
				'condition' => [
					'layout_template' => 'default'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'count_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_control(
			'count_transition',
			[
				'label'      => __( 'Transition', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'size' => '300',
					'unit' => 'px'
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 10000,
						'step' => 100
					]
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts'       => 'transition: {{SIZE}}ms;',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts:after' => 'transition: {{SIZE}}ms;'
				]
			]
		);

		$this->add_control(
			'count_color_hover',
			[
				'label'     => esc_html__( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts:hover span' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'count_bg_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts:hover,
                    {{WRAPPER}} .betterdocs-elementor.betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_control(
			'count_ticker_color_hover',
			[
				'label'     => esc_html__( 'Ticker Background', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-items-counts:hover:after' => 'border-top-color: {{VALUE}};'
				],
				'condition' => [
					'layout_template' => 'layout-2'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'count_border_hover', // Legacy control name change it with 'border_size' if anything happens.
				'label'     => __( 'Border', 'betterdocs' ),
				'selector'  => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-items-counts:hover',
				'condition' => [
					'layout_template' => 'default'
				]
			]
		);

		$this->add_control(
			'count_border_radius_hover',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .layout-2 .betterdocs-category-items-count:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition'  => [
					'layout_template' => 'default'
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
					'layout_template' => 'default'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'count_bg_second_hover',
				'types'     => [ 'classic', 'gradient' ],
				'selector'  => '
                    {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-category-items-counts:hover span
                ',
				'exclude'   => [
					'image'
				],
				'condition' => [
					'layout_template' => 'default'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section(); # end of 'Count Settings'

		/**
		 * ----------------------------------------------------------
		 * Section: List Settinggs
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_article_settings',
			[
				'label'     => __( 'List', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_list' => 'true'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'list_item_typography',
				'selector' => '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a'
			]
		);

		$this->add_control(
			'list_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'list_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'list_margin',
			[
				'label'      => esc_html__( 'List Item Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'list_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_responsive_control(
			'list_area_padding',
			[
				'label'              => esc_html__( 'List Area Padding', 'betterdocs' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'allowed_dimensions' => 'vertical',
				'size_units'         => [ 'px', 'em', '%' ],
				'selectors'          => [
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body' => 'padding: {{TOP}}{{UNIT}} {{BOTTOM}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'icon_settings_heading',
			[
				'label'     => esc_html__( 'Icon', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
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
				]
			]
		);

		$this->add_control(
			'list_icon_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li svg' => 'fill: {{VALUE}};',
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li i'   => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'list_icon_size',
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
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li svg' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li i'   => 'font-size: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'list_icon_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li i'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Column Settings'

		/**
		 * ----------------------------------------------------------
		 * Section: Nested List Settinggs
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_nested_list_settings',
			[
				'label'     => __( 'Nested List', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'nested_subcategory' => 'true'
				]
			]
		);

		$this->add_control(
			'section_nested_list_title',
			[
				'label' => esc_html__( 'Title', 'betterdocs' ),
				'type'  => Controls_Manager::HEADING
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'nested_list_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a'
			]
		);

		$this->add_control(
			'nested_list_title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'nested_list_title_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'nested_list_title_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title'
			]
		);

		$this->add_responsive_control(
			'nested_list_title_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'section_nested_list_icon',
			[
				'label'     => esc_html__( 'Icon', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'nested_list_title_closed_icon',
			[
				'label'   => __( 'Collapse Icon', 'betterdocs' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-angle-right',
					'library' => 'fa-regular'
				]
			]
		);

		$this->add_control(
			'nested_list_title_open_icon',
			[
				'label'   => __( 'Open Icon', 'betterdocs' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-angle-down',
					'library' => 'fa-regular'
				]
			]
		);

		$this->add_control(
			'nested_list_icon_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}.betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title .toggle-arrow' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'nested_list_icon_size',
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
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title .toggle-arrow'    => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title img.toggle-arrow' => 'width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'nested_list_icon_margin',
			[
				'label'      => esc_html__( 'Area Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-articles-list .betterdocs-nested-category-wrapper .betterdocs-nested-category-title .toggle-arrow' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Column Settings'

		/**
		 * ----------------------------------------------------------
		 * Section: Button Settings
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_button_settings',
			[
				'label'     => __( 'Button', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_button' => 'true'
				]
			]
		);

		$this->add_control(
			'show_button_icon',
			[
				'label'        => __( 'Show Icon', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true'
			]
		);

		$this->add_control(
			'button_icon',
			[
				'label'     => __( 'Icon', 'betterdocs' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-angle-right',
					'library' => 'fa-solid'
				],
				'condition' => [
					'show_button_icon' => 'true'
				]
			]
		);

		$this->add_control(
			'icon_position',
			[
				'label'     => __( 'Icon Position', 'betterdocs' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'after',
				'options'   => [
					'before' => __( 'Before', 'betterdocs' ),
					'after'  => __( 'After', 'betterdocs' )
				],
				'condition' => [
					'show_button_icon' => 'true'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography_normal',
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a'
			]
		);

		$this->start_controls_tabs(
			'button_settings_tabs'
		);

		// Normal State Tab
		$this->start_controls_tab(
			'button_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_control(
			'button_color_normal',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper  .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-footer a'         => 'color: {{VALUE}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'button_background_normal',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border_normal',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' =>
				'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a'
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a'                                                                                => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a'                                                                                => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'button_area_margin',
			[
				'label'      => esc_html__( 'Area Spacing', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'button_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_control(
			'button_transition',
			[
				'label'      => __( 'Transition', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'size' => '300',
					'unit' => 'px'
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 10000,
						'step' => 100
					]
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a' => 'transition: {{SIZE}}ms;'
				]
			]
		);

		$this->add_control(
			'button_color_hover',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a:hover'                                                                                      => 'color: {{VALUE}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a:hover:hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'button_background_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border_hover',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a:hover, {{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a:hover'
			]
		);

		$this->add_responsive_control(
			'button_hover_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a:hover'                                                                                => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.layout-2 .betterdocs-single-category-inner .betterdocs-footer a:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'button_text_alignment',
			[
				'label'     => __( 'Text Alignment', 'betterdocs' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'betterdocs' ),
						'icon'  => 'fa fa-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'betterdocs' ),
						'icon'  => 'fa fa-align-center'
					],
					'right'  => [
						'title' => __( 'Right', 'betterdocs' ),
						'icon'  => 'fa fa-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer a' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'button_alignment',
			[
				'label'     => __( 'Button Alignment', 'betterdocs' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'betterdocs' ),
						'icon'  => 'fa fa-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'betterdocs' ),
						'icon'  => 'fa fa-align-center'
					],
					'end'    => [
						'title' => __( 'Right', 'betterdocs' ),
						'icon'  => 'fa fa-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .betterdocs-elementor .betterdocs-category-grid-inner-wrapper .betterdocs-footer' => 'align-self: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Button Settings'
	}

	protected function render_callback() {
		$this->views( 'layouts/base' );
	}

	public function view_params() {
		$settings = &$this->attributes;

		$wrapper_attr = [
			'id'    => 'el-betterdocs-cat-grid-' . esc_attr( $this->get_id() ),
			'class' => [ 'betterdocs-category-grid-wrapper' ]
		];

		$inner_wrapper_attr = [
			'class'                     => [
				'betterdocs-category-grid-inner-wrapper',
				'betterdocs-category-grid',
				$settings['layout_mode'],
				$settings['layout_mode'] == 'grid' ? 'layout-flex' : ''
			],
			'data-layout-mode'          => $settings['layout_mode'],
			'data-column_space_desktop' => $settings['grid_space'],
			'data-column_space_tab'     => isset( $settings['grid_space_tablet'] ) ? $settings['grid_space_tablet'] : $settings['grid_space'],
			'data-column_space_mobile'  => isset( $settings['grid_space_mobile'] ) ? $settings['grid_space_mobile'] : $settings['grid_space'],
			'data-column'               => $settings['grid_column'],
			'data-column_desktop'       => $settings['grid_column'],
			'data-column_tab'           => isset( $settings['grid_column_tablet'] ) ? $settings['grid_column_tablet'] : $settings['grid_column'],
			'data-column_mobile'        => isset( $settings['grid_column_mobile'] ) ? $settings['grid_column_mobile'] : $settings['grid_column']
		];

		$default_multiple_kb = (bool) betterdocs()->editor->get( 'elementor' )->multiple_kb_status();

		if ( $settings['layout_template'] == 'Layout_2' ) {
			$settings['layout_template'] = 'layout-2';
		}

		if ( $settings['layout_template'] == 'Layout_Default' ) {
			$settings['layout_template'] = 'default';
		}

		$is_edit_mode = ElementorPlugin::instance()->editor->is_edit_mode();

		$terms_query = [
			'hide_empty'         => true, // Let template handle editor mode logic
			'taxonomy'           => 'doc_category',
			'orderby'            => $settings['orderby'],
			'order'              => $settings['order'],
			'offset'             => $settings['offset'],
			'number'             => $settings['grid_per_page'],
			'nested_subcategory' => $settings['nested_subcategory']
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
				$meta_value = $object->slug;
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
			'bd_category_grid_wrapper',
			$wrapper_attr
		);

		$this->add_render_attribute(
			'bd_category_grid_inner',
			$inner_wrapper_attr
		);

		$terms_query_args = $this->betterdocs( 'query' )->terms_query( $terms_query );

		$default_params = [
			'wrapper_attr'            => $this->get_render_attributes( 'bd_category_grid_wrapper' ),
			'inner_wrapper_attr'      => $this->get_render_attributes( 'bd_category_grid_inner' ),
			'widget_type'             => 'category-grid',
			'layout'                  => sanitize_file_name( $settings['layout_template'] ),
			'is_edit_mode'            => $is_edit_mode,
			'terms_query_args'        => $terms_query_args,
			'list_icon_name'          => $settings['list_icon'],
			'button_icon_position'    => $settings['icon_position'],
			'term_icon_meta_key'      => 'doc_category_image-id',
			'multiple_knowledge_base' => ( $default_multiple_kb && ! empty( $kb_slug ) ) ? true : false,
			'kb_slug'                 => $kb_slug,
			'docs_query_args'         => [
				'posts_per_page'     => $settings['post_per_page'],
				'orderby'            => $settings['post_orderby'],
				'order'              => $settings['post_order'],
				'nested_subcategory' => $settings['nested_subcategory']
			],
			'nested_terms_query'      => [
				'number' => $settings['subcategory_per_grid']
			],
			'nested_docs_query_args'  => [
				'orderby'        => $settings['post_orderby'],
				'order'          => $settings['post_order'],
				'posts_per_page' => $settings['post_per_subcat']
			],
			'category_title_link'     => $settings['category_link'],
			'layout_type'             => 'widget',
			'list_icon_url'           => '',
			'lazy_load'               => ! empty( $settings['lazy_load'] )
		];

		if ( $settings['layout_template'] == 'layout-2' ) {
			$default_params['count_prefix']          = $settings['count_prefix'];
			$default_params['count_suffix']          = $settings['count_suffix'];
			$default_params['count_suffix_singular'] = $settings['count_suffix_singular'];
		}

		$params = wp_parse_args( $default_params, $settings );

		return $params;
	}
}
