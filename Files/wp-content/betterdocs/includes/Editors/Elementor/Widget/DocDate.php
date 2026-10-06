<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;

class DocDate extends BaseWidget {
	public $view_wrapper = 'betterdocs-updated-date-wrapper update-date';

	public function get_name() {
		return 'betterdocs-doc-date';
	}

	public function get_title() {
		return __( 'Doc Date', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-date';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'date', 'docs', 'betterdocs' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {

		$this->start_controls_section(
			'doc_date_general_section',
			[
				'label' => __( 'General', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_CONTENT
			]
		);

		$this->add_control(
			'doc_date_general_updated_text',
			[
				'label'   => __( 'Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Updated on', 'betterdocs' )
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_column_settings',
			[
				'label' => __( 'Style', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'update_date_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .update-date' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'update_date_typography',
				'selector' => '{{WRAPPER}} .update-date'
			]
		);

		$this->add_responsive_control(
			'update_date_alignment',
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

		$this->end_controls_section();
	}

	protected function render_callback() {
		$this->views( 'widgets/date' );
	}

	public function view_params() {
		return [
			'doc_date_text' => $this->attributes['doc_date_general_updated_text']
		];
	}
}
