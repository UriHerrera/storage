<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;


class Tags extends BaseWidget {
	public function get_name() {
		return 'betterdocs-tags';
	}

	public function get_title() {
		return __( 'Betterdocs Tags', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-Tag';
	}

	public function get_categories() {
		return [ 'betterdocs-elements', 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-tags', 'tags', 'betterdocs', 'doc-tag' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_wrapper_settings',
			[
				'label' => __( 'Wrapper', 'betterdocs' )
			]
		);

		$this->add_responsive_control(
			'betterdocs_tags_wrapper_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-tags' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'betterdocs_tags_wrapper_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-tags' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'betterdocs_tags_wrapper_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-tags'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'betterdocs_tags_settings',
			[
				'label' => __( 'Tags', 'betterdocs' )
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'betterdocs_tags_typography',
				'selector' => '{{WRAPPER}} .betterdocs-tags a'
			]
		);

		$this->add_responsive_control(
			'betterdocs_tags_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-tags a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'betterdocs_tags_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-tags a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'betterdocs_tags_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-tags a'
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'betterdocs_tags_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-tags a'
			]
		);

		$this->add_control(
			'betterdocs_tags_color',
			[
				'label'     => __( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-tags a' => 'color: {{VALUE}}'
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render_callback() {
		$this->views( 'templates/parts/tags' );
	}
}
