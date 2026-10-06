<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BetterDocs AI Doc Summarizer Elementor Widget
 */
class ArticleSummary extends Widget_Base {

	/**
	 * Get widget name
	 *
	 * @return string Widget name
	 */
	public function get_name() {
		return 'betterdocs-article-summary';
	}

	/**
	 * Get widget title
	 *
	 * @return string Widget title
	 */
	public function get_title() {
		return __( 'AI Doc Summarizer', 'betterdocs' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string Widget icon
	 */
	public function get_icon() {
		return 'betterdocs-icon-ai-summary';
	}

	/**
	 * Get widget categories
	 *
	 * @return array Widget categories
	 */
	public function get_categories() {
		return [ 'betterdocs-elements', 'docs-archive', 'betterdocs-elements-single' ];
	}

	/**
	 * Get widget keywords
	 *
	 * @return array Widget keywords
	 */
	public function get_keywords() {
		return [ 'betterdocs', 'article', 'summary', 'ai', 'documentation', 'ai summary', 'doc summary', 'article summary', 'ai doc summarizer' ];
	}

	/**
	 * Get widget style dependencies
	 *
	 * @return array Widget style dependencies
	 */
	public function get_style_depends() {
		return [ 'betterdocs-article-summary' ];
	}

	/**
	 * Get widget script dependencies
	 *
	 * @return array Widget script dependencies
	 */
	public function get_script_depends() {
		return [ 'betterdocs' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/configure-ai-doc-summarizer-for-betterdocs/';
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'custom_title',
			[
				'label'       => __( 'Custom Title', 'betterdocs' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Doc Summary', 'betterdocs' ),
				'description' => __( 'Leave empty to use default title', 'betterdocs' ),
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'        => __( 'Show Title', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'betterdocs' ),
				'label_off'    => __( 'Hide', 'betterdocs' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		// Title Style Section
		$this->start_controls_section(
			'section_title_style',
			[
				'label'     => __( 'Title Style', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_bg_color',
			[
				'label'     => __( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f9fafb',
				'selectors' => [
					'{{WRAPPER}} .betterdocs-article-summary .betterdocs-summary-header' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'label'    => __( 'Typography', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-summary-title',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2c3e50',
				'selectors' => [
					'{{WRAPPER}} .betterdocs-summary-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-summary .betterdocs-summary-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// $this->add_responsive_control(
		// 	'title_margin',
		// 	[
		// 		'label'      => __( 'Margin', 'betterdocs' ),
		// 		'type'       => Controls_Manager::DIMENSIONS,
		// 		'size_units' => [ 'px', 'em', '%' ],
		// 		'selectors'  => [
		// 			'{{WRAPPER}} .betterdocs-summary-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
		// 		],
		// 	]
		// );

		$this->end_controls_section();

		// Icon Style Section
		$this->start_controls_section(
			'section_icon_style',
			[
				'label' => __( 'Icon Style', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Arrow Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#98A2B3',
				'selectors' => [
					'{{WRAPPER}} .betterdocs-summary-arrow .angle-icon path' => 'stroke: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Arrow Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 24,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 12,
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-summary-arrow .angle-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ai_icon_size',
			[
				'label'      => __( 'AI Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 16,
						'max' => 32,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-summary-title img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Content Style Section
		$this->start_controls_section(
			'section_content_style',
			[
				'label' => __( 'Content Style', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'label'    => __( 'Typography', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-summary-text p, {{WRAPPER}} .betterdocs-summary-loading',
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => __( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#555555',
				'selectors' => [
					'{{WRAPPER}} .betterdocs-summary-text p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'loading_color',
			[
				'label'     => __( 'Loading Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#666666',
				'selectors' => [
					'{{WRAPPER}} .betterdocs-summary-loading' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'thinking_icon_size',
			[
				'label'      => __( 'Thinking Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 16,
						'max' => 32,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-summary-loading img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 20,
					'right'  => 20,
					'bottom' => 20,
					'left'   => 20,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-summary-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-summary-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Box Style Section
		$this->start_controls_section(
			'section_box_style',
			[
				'label' => __( 'Box Style', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'box_background',
				'label'    => __( 'Background', 'betterdocs' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-article-summary',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'box_border',
				'label'    => __( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-article-summary',
			]
		);

		$this->add_responsive_control(
			'box_border_radius',
			[
				'label'      => __( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-summary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'box_shadow',
				'label'    => __( 'Box Shadow', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-article-summary',
			]
		);

		$this->add_responsive_control(
			'box_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-summary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'box_margin',
			[
				'label'      => __( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-article-summary' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Always show in Elementor editor mode
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			// Prepare template variables for editor
			$template_vars = [
				'post_id' => get_the_ID() ?: 1, // Use dummy ID in editor
				'custom_title' => $settings['custom_title'] ?? '',
				'show_title' => ( $settings['show_title'] ?? 'yes' ) === 'yes',
				'widget_type' => 'elementor',
				'is_editor_mode' => true,
			];

			// Load template
			betterdocs()->views->get( 'templates/parts/article-summary', $template_vars );
			return;
		}

		// For frontend, check if it's a docs post type
		if ( get_post_type() !== 'docs' ) {
			return;
		}

		// Check if AI Doc Summarizer feature is enabled
		$article_summary = betterdocs()->article_summary;
		if ( ! $article_summary || ! $article_summary->is_enabled() ) {
			return;
		}

		// Prepare template variables for frontend
		$template_vars = [
			'post_id' => get_the_ID(),
			'custom_title' => $settings['custom_title'] ?? '',
			'show_title' => ( $settings['show_title'] ?? 'yes' ) === 'yes',
			'widget_type' => 'elementor',
			'is_editor_mode' => false,
		];

		// Load template (template will check global setting)
		betterdocs()->views->get( 'templates/parts/article-summary', $template_vars );
	}

	/**
	 * Render widget output in the editor
	 */
	protected function content_template() {
		?>
		<#
		var titleText = settings.custom_title || '<?php echo esc_js( __( 'Doc Summary', 'betterdocs' ) ); ?>';
		var showTitle = settings.show_title === 'yes';
		#>

		<div class="betterdocs-article-summary betterdocs-elementor betterdocs-editor-mode" id="betterdocs-article-summary" data-post-id="1" data-post-type="docs">
			<# if ( showTitle ) { #>
				<div class="betterdocs-summary-header" id="betterdocs-summary-toggle">
					<h3 class="betterdocs-summary-title">
						<img src="<?php echo esc_url( betterdocs()->assets->icon( 'ai-summary-icon.svg' ) ); ?>" alt="<?php echo esc_attr__( 'AI Doc Summarizer', 'betterdocs' ); ?>" />
						{{{ titleText }}}
						<span class="betterdocs-summary-arrow">
							<svg class="angle-icon angle-right" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
								<path d="M6 9L12 15L18 9" stroke="#98A2B3" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<svg class="angle-icon angle-down" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M18 15L12 9L6 15" stroke="#98A2B3" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</h3>
				</div>
			<# } #>

			<div class="betterdocs-summary-content" id="betterdocs-summary-content" style="display: block;">
				<div class="betterdocs-summary-loading" id="betterdocs-summary-loading" style="display: none;">
					<img src="<?php echo esc_url( betterdocs()->assets->icon( 'thinking-icon.svg' ) ); ?>" alt="<?php echo esc_attr__( 'AI Doc Summarizer Thinking', 'betterdocs' ); ?>" />
					<?php echo esc_html__( 'Thinking...', 'betterdocs' ); ?>
				</div>
				<div class="betterdocs-summary-text" id="betterdocs-summary-text">
					<div class="betterdocs-summary-preview">
						<p><?php echo esc_html__( 'This is a preview of the AI Doc Summarizer widget. The actual AI-generated summary will appear here when viewed on the frontend.', 'betterdocs' ); ?></p>
						<p><?php echo esc_html__( 'The summary will be automatically generated based on the article content using OpenAI technology.', 'betterdocs' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<style>
			/* Always show Article Summary as expanded in Elementor editor */
			.betterdocs-article-summary.betterdocs-editor-mode .betterdocs-summary-content {
				display: block !important;
			}
			.betterdocs-article-summary.betterdocs-editor-mode .angle-icon.angle-right {
				display: none !important;
			}
			.betterdocs-article-summary.betterdocs-editor-mode .angle-icon.angle-down {
				display: block !important;
			}
		</style>
		<?php
	}
}
