<?php

namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;

use Elementor\Controls_Manager;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor widget: BetterDocs API Code Samples.
 *
 * The Elementor Theme Builder counterpart to the `betterdocs/api-code-samples`
 * block — drop it into the sidebar column of a single-doc template and endpoint
 * docs get the same sticky Request/Response panel that classic layouts render
 * on `betterdocs_after_toc_sidebar`.
 *
 * Both editors render through Frontend::code_sample_sections() and the shared
 * views/templates/api-ref/code-samples.php, so the panel stays identical across
 * classic templates, the block, and this widget.
 */
class ApiCodeSamples extends BaseWidget {

	public function get_name() {
		return 'betterdocs-elementor-api-code-samples';
	}

	public function get_title() {
		return __( 'BetterDocs API Code Samples', 'betterdocs-pro' );
	}

	public function get_icon() {
		return 'betterdocs- eicon-code';
	}

	public function get_categories() {
		return [ 'betterdocs-elements', 'docs-single' ];
	}

	public function get_style_depends() {
		return [ 'betterdocs-api-reference' ];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/#pricing';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'api_code_samples_content',
			[
				'label' => __( 'Panels', 'betterdocs-pro' )
			]
		);

		$this->add_control(
			'show_request',
			[
				'label'        => __( 'Show Request', 'betterdocs-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'betterdocs-pro' ),
				'label_off'    => __( 'Off', 'betterdocs-pro' ),
				'return_value' => 'true',
				'default'      => 'true'
			]
		);

		$this->add_control(
			'show_response',
			[
				'label'        => __( 'Show Response', 'betterdocs-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'betterdocs-pro' ),
				'label_off'    => __( 'Off', 'betterdocs-pro' ),
				'return_value' => 'true',
				'default'      => 'true'
			]
		);

		$this->add_control(
			'api_code_samples_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Renders only on API endpoint docs. Other docs — and the editor preview on a non-endpoint template — show nothing.', 'betterdocs-pro' ),
				'content_classes' => 'elementor-descriptor'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'api_code_samples_style',
			[
				'label' => __( 'Style', 'betterdocs-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE
			]
		);

		$this->add_control(
			'panel_title_color',
			[
				'label'     => __( 'Panel Title Color', 'betterdocs-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-api-code-aside__title' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'sticky_offset',
			[
				'label'      => __( 'Sticky Offset', 'betterdocs-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 200 ]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-api-code-aside-group' => 'top: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render_callback() {
		$settings = &$this->attributes;

		$sections = betterdocs()->container->get( Frontend::class )->code_sample_sections();

		if ( null === $sections ) {
			return;
		}

		$show_request  = ! isset( $settings['show_request'] ) || 'true' === $settings['show_request'];
		$show_response = ! isset( $settings['show_response'] ) || 'true' === $settings['show_response'];

		$rendered          = $show_request && null !== $sections['request'] ? do_blocks( $sections['request'] ) : '';
		$rendered_response = $show_response && null !== $sections['response'] ? do_blocks( $sections['response'] ) : '';

		if ( '' === $rendered && '' === $rendered_response ) {
			return;
		}

		include BETTERDOCS_PRO_ABSPATH . 'views/templates/api-ref/code-samples.php';
	}
}
