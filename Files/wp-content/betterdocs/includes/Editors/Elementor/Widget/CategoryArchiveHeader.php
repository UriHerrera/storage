<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Plugin;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use WP_Query;

class CategoryArchiveHeader extends BaseWidget {
	public function get_name() {
		return 'doc-category-archive-header';
	}

	public function get_title() {
		return __( 'Doc Category Archive Header', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-archive-header';
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'archive', 'docs', 'betterdocs', 'archive-header', 'doc-archive-header' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	public function get_categories() {
		return [ 'betterdocs-elements', 'docs-archive' ];
	}

	public function get_style_depends() {
		return [
			'betterdocs-category-archive-header'
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_betterdocs_category_archive_header_general_settings',
			[
				'label' => __( 'General', 'betterdocs' )
			]
		);

		$this->add_control(
			'section_betterdocs_category_archive_header_title_tag',
			[
				'label'   => __( 'Select Tag', 'betterdocs' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => [
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
			'section_betterdocs_category_archive_header_show_icon',
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
			'section_betterdocs_category_archive_header_show_count',
			[
				'label'        => __( 'Show Count', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => 'true'
			]
		);

		$this->end_controls_section();

		$this->wrapper_header();
		$this->archive_title();
		$this->archive_icon();
		$this->archive_count();
	}

	public function wrapper_header() {
		$this->start_controls_section(
			'section_betterdocs_category_archive_wrapper_header',
			[
				'label' => __( 'Header Wrapper', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_betterdocs_category_archive_header_color',
				'types'    => [ 'classic', 'gradient' ],
				'label'    => esc_html__( 'Background Color', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-main-category-folder'
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_betterdocs_category_archive_header_hover_color',
				'types'    => [ 'classic', 'gradient' ],
				'label'    => esc_html__( 'Background Hover Color', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-main-category-folder:hover'
			]
		);

		$this->add_responsive_control(
			'section_betterdocs_category_archive_header_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-main-category-folder' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'section_betterdocs_category_archive_header_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-main-category-folder' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function archive_title() {
		$this->start_controls_section(
			'section_betterdocs_category_archive_title',
			[
				'label' => __( 'Title', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'section_betterdocs_category_archive_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title'
			]
		);

		$this->add_control(
			'section_betterdocs_category_archive_title_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'section_betterdocs_category_archive_title_hover_color',
			[
				'label'     => esc_html__( 'Color Hover', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title:hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function archive_icon() {
		$this->start_controls_section(
			'section_betterdocs_category_archive_icon',
			[
				'label' => __( 'Icon', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_betterdocs_category_archive_icon_background',
				'types'    => [ 'classic', 'gradient' ],
				'label'    => esc_html__( 'Background Color', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-icon .betterdocs-folder-icon'
			]
		);
		$this->add_control(
			'section_betterdocs_category_archive_icon_size',
			[
				'label'      => __( 'Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-icon .betterdocs-folder-icon' => 'height:{{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
				]
			]
		);
		$this->end_controls_section();
	}

	public function archive_count() {
		$this->start_controls_section(
			'section_betterdocs_category_archive_count',
			[
				'label' => __( 'Count', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'section_betterdocs_category_archive_count_typography',
				'selector' => '{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts'
			]
		);

		$this->add_control(
			'section_betterdocs_category_archive_count_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'section_betterdocs_category_archive_count_hover_color',
			[
				'label'     => esc_html__( 'Color Hover', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts:hover' => 'color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function view_params() {
		$settings             = &$this->attributes;
		$settings['taxonomy'] = 'doc_category';

		// Check if Elementor is in editor mode
		if ( Plugin::instance()->editor->is_edit_mode() ) {
			// Fallback: Get the doc_category with the highest assigned posts in editor mode
			$args             = [
				'taxonomy'   => 'doc_category',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => false,
				'number'     => 1 // Only get the top category
			];
			$categories       = get_terms( $args );
			$current_category = ! empty( $categories ) ? $categories[0] : null;
		} else {
			$current_category = get_queried_object();
		}

		if ( ! $current_category ) {
			return [];
		}

		// Ensure we have a term object, not a post type object
		if ( ! isset( $current_category->term_id ) || ! isset( $current_category->slug ) ) {
			// If it's not a term object, try to get the first category as fallback
			$args             = [
				'taxonomy'   => 'doc_category',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => false,
				'number'     => 1
			];
			$categories       = get_terms( $args );
			$current_category = ! empty( $categories ) ? $categories[0] : null;

			if ( ! $current_category ) {
				return [];
			}
		}

		$args = betterdocs()->query->docs_query_args(
			[
				'term_id'        => $current_category->term_id,
				'term_slug'      => $current_category->slug,
				'posts_per_page' => -1
			]
		);

		$post_query = new WP_Query( $args );

		$_nested_categories = betterdocs()->query->get_child_term_ids_by_parent_id( 'doc_category', $current_category->term_id );

		if ( $_nested_categories ) {
			$sub_terms_count = count( explode( ',', $_nested_categories ) );
		} else {
			$sub_terms_count = 0;
		}

		$params = [
			'current_category' => $current_category,
			'found_posts'      => $post_query->found_posts,
			'sub_terms_count'  => $sub_terms_count,
			'show_icon'        => $settings['section_betterdocs_category_archive_header_show_icon'],
			'title_tag'        => $settings['section_betterdocs_category_archive_header_title_tag'],
			'show_count'       => $settings['section_betterdocs_category_archive_header_show_count']
		];

		return $params;
	}

	public function render_callback() {
		$this->views( 'template-parts/archive-header' );
	}
}
