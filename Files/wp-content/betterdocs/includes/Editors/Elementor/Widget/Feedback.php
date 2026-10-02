<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;

class Feedback extends BaseWidget {

	public function get_name() {
		return 'betterdocs-feedback';
	}

	public function get_title() {
		return __( 'Doc Feedback', 'betterdocs' );
	}

	public function get_icon() {
		return 'betterdocs-icon-feedback';
	}

	public function get_categories() {
		return [ 'betterdocs-elements-single' ];
	}

	public function get_keywords() {
		return [ 'betterdocs-elements', 'feedback', 'betterdocs', 'docs' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-feedback-form' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {

		/**
		 * ----------------------------------------------------------
		 * Section: Layout Options
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_layout_options',
			[
				'label' => __( 'General ', 'betterdocs' )
			]
		);


		$this->add_control(
			'feedback_form_name_label_text',
			[
				'label'   => __( 'Label Name Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Name:', 'betterdocs' )
			]
		);


		$this->add_control(
			'feedback_form_email_label_text',
			[
				'label'   => __( 'Label Email Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Email:', 'betterdocs' )
			]
		);

		$this->add_control(
			'feedback_form_message_label_text',
			[
				'label'   => __( 'Label Message Text', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Message:', 'betterdocs' )
			]
		);

		$this->add_control(
			'feedback_link_title',
			[
				'label'   => __( 'Content', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Still stuck? How can we help?', 'betterdocs' )
			]
		);
		$this->add_control(
			'feedback_form_title',
			[
				'label'   => __( 'Feedback Form Title', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'How can we help?', 'betterdocs' )
			]
		);

		$this->add_control(
			'feedback_form_button_text',
			[
				'label'       => __( 'Button Text', 'betterdocs' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Send',
				'placeholder' => __( 'Enter button text', 'betterdocs' ),
				'title'       => __( 'Enter button text here', 'betterdocs' )
			]
		);

		$this->add_control(
			'feedback_form_icon_image',
			[
				'label'   => esc_html__( 'Choose Feedback Icon Image', 'betterdocs' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => '',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_card_settings_text',
			[
				'label' => __( 'Text', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'feedback_text_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .feedback-form-link' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feedback_text_typography',
				'selector' => '{{WRAPPER}} .feedback-form-link'
			]
		);

		$this->end_controls_section();

		$this->icon_section();

		$this->feedback_form();
	}

	public function icon_section() {
		$this->start_controls_section(
			'section_icon_options',
			[
				'label' => __( 'Icon ', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'feedback_icon_color',
			[
				'label' => esc_html__( 'Color', 'betterdocs' ),
				'type'  => Controls_Manager::COLOR
			]
		);

		$this->add_control(
			'feedback_text_icon_size',
			[
				'label'      => __( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 1000,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .feedback-form-icon svg' => 'width: {{SIZE}}px;height: {{SIZE}}px;',
					'{{WRAPPER}} .feedback-form-icon img' => 'width: {{SIZE}}px;height: {{SIZE}}px;'
				]
			]
		);

		$this->end_controls_section();
	}

	public function feedback_form() {
		/**
		 * ----------------------------------------------------------
		 * Section: Box Styles
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_card_settings',
			[
				'label' => __( 'FeedBack Form', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'feedback_form_width',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 1000,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-modalwindow .modal-content' => 'width: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'feedback_form_height',
			[
				'label'      => __( 'Height', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 1000,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-modalwindow .modal-content' => 'height: {{SIZE}}px;'
				]
			]
		);

		$this->add_control(
			'feedback_form_heading',
			[
				'label' => esc_html__( 'Heading', 'betterdocs' ),
				'type'  => Controls_Manager::HEADING
			]
		);

		$this->add_control(
			'feedback_form_heading_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #betterdocs-form-modal .modal-inner .modal-content .feedback-form-title' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feedback_form_heading_typography',
				'selector' => '{{WRAPPER}} #betterdocs-form-modal .modal-inner .modal-content .feedback-form-title'
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
					'{{WRAPPER}} #betterdocs-form-modal .modal-inner .modal-content .feedback-form-title' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'feedback_form_label',
			[
				'label' => esc_html__( 'Lable', 'betterdocs' ),
				'type'  => Controls_Manager::HEADING
			]
		);

		$this->add_control(
			'feedback_form_label_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-modalwindow .betterdocs-feedback-form label' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feedback_form_label_typography',
				'selector' => '{{WRAPPER}} .betterdocs-modalwindow .betterdocs-feedback-form label'
			]
		);

		$this->add_control(
			'feedback_form_label_space',
			[
				'label'      => __( 'Space', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max'  => 500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-modalwindow .betterdocs-feedback-form label input,{{WRAPPER}} .betterdocs-modalwindow .betterdocs-feedback-form label textarea' => 'margin-top: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'feedback_form_button',
			[
				'label' => esc_html__( 'Button', 'betterdocs' ),
				'type'  => Controls_Manager::HEADING
			]
		);

		$this->add_control(
			'feedback_form_button_background',
			[
				'label'     => esc_html__( 'Background', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-feedback-form #feedback_form_submit_btn' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'feedback_form_button_color',
			[
				'label'     => esc_html__( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-feedback-form #feedback_form_submit_btn' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feedback_form_button_typography',
				'selector' => '{{WRAPPER}} .betterdocs-feedback-form #feedback_form_submit_btn'
			]
		);

		$this->add_responsive_control(
			'feedback_form_button_width',
			[
				'label'      => esc_html__( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 500,
						'step' => 1
					],
					'%'  => [
						'min' => 0,
						'max' => 100
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-feedback-form #feedback_form_submit_btn' => 'width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'feedback_form_button_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-feedback-form #feedback_form_submit_btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'feedback_form_button_align',
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
					'{{WRAPPER}} .betterdocs-feedback-form .feedback-from-button' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render_callback() {
		$settings = $this->get_settings_for_display();

		betterdocs()->views->get(
			'templates/feedback-parts/form',
			[
				'widget'   => $this,
				'settings' => $settings
			]
		);

		if ( Plugin::instance()->editor->is_edit_mode() ) {
			$this->render_editor_script();
		}
	}

	public function render_editor_script() {
		betterdocs()->views->get( 'templates/feedback-parts/editor' );
	}
}
