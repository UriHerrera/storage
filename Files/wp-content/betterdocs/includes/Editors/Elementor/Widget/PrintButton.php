<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Controls_Manager;



class PrintButton extends BaseWidget {

	public function get_name() {
		return 'betterdocs-print-button';
	}

	public function get_title() {
		return __( 'Print Button', 'betterdocs' );
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'print', 'docs', 'betterdocs' ];
	}

	public function get_icon() {
		return 'betterdocs-icon-printer';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'print_button_section',
			[
				'label' => __( 'Icon', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'print_button_size',
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
					'{{WRAPPER}} .betterdocs-print-pdf-2 .betterdocs-print-btn-2 svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'print_button_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-print-pdf-2 .betterdocs-print-btn-2 svg' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function view_params() {
		return [
			'enable' => true
		];
	}

	public function render_callback() {
		$this->views( 'templates/parts/print-icon-2' );
	}
}
