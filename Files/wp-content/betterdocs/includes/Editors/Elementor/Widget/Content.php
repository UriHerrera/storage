<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Core\Schemes;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WPDeveloper\BetterDocs\Editors\Elementor\ContentBaseWidget;

class Content extends ContentBaseWidget {
	public function get_name() {
		return 'betterdocs-content';
	}

	public function get_title() {
		return __( 'Doc Content', 'betterdocs' );
	}

	public function has_widget_inner_wrapper():bool {
		return false;
	}

	public function get_icon() {
		return 'betterdocs-icon-Content';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'content', 'description', 'docs', 'betterdocs' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'print_btn',
			[
				'label'        => __( 'Print Button', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => '1',
				'default'      => '1'
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
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'betterdocs_content_typography',
				'selector' => '{{WRAPPER}} .betterdocs-content'
			]
		);

		$this->add_control(
			'doc_content_color',
			[
				'label'     => __( 'Doc Content Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-content'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .betterdocs-content p' => 'color: {{VALUE}}',
				]
			]
		);

		$this->add_control(
			'doc_content_title_color',
			[
				'label'     => __( 'Content Title Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-content .betterdocs-content-heading' => 'color: {{VALUE}}'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'betterdocs_content_title_typography',
				'selector' => '{{WRAPPER}} .betterdocs-content .betterdocs-content-heading'
			]
		);
		$this->end_controls_section();
	}

	protected function render_callback() {
		$this->views( 'widgets/content' );
	}

	public function view_params() {
		$toc_setting = get_transient( 'betterdocs_toc_setting' );
		$htags       = ( $toc_setting ) ? implode( ',', $toc_setting['htags'] ) : '';
		$enable_toc  = ( $htags ) ? 1 : '';

		return [
			'enable'       => (bool) $this->attributes['print_btn'],
			'htags'        => $htags,
			'enable_toc'   => $enable_toc,
			'wrapper_attr' => [
				'class' => [ 'betterdocs-entry-content' ]
			]
		];
	}

	public function show_in_panel() {
		return true;
	}
}
