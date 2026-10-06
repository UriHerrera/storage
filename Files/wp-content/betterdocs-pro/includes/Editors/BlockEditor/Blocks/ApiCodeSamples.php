<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg block: betterdocs/api-code-samples (Pro-only)
 *
 * The Request/Response code panel that classic single-doc layouts render into
 * the right ToC sidebar via the `betterdocs_after_toc_sidebar` action. Block
 * themes never load those PHP templates, so that action never fires — this
 * block is the FSE equivalent: drop it into the right-hand column of the
 * single-doc template and endpoint docs get the same sticky panel.
 *
 * It reads the sections straight out of the post content rather than the stash
 * Frontend fills during `the_content`. A block template can render its columns
 * in any order, so the stash may still be empty when this runs; parsing the
 * post is order-independent.
 */
class ApiCodeSamples extends Block {
	public $is_pro = true;

	protected $editor_scripts = [ 'betterdocs-pro-blocks-editor' ];
	// The panel is the real frontend markup in the editor preview too, so the
	// canvas needs the real stylesheet.
	protected $editor_styles   = [ 'betterdocs-api-reference', 'betterdocs-pro-blocks-editor' ];
	protected $frontend_styles = [ 'betterdocs-api-reference' ];

	/**
	 * Keep in sync with block.json — the Block base passes this to
	 * register_block_type(), overriding whatever block.json declares.
	 *
	 * @var array
	 */
	public $attributes = [
		'showRequest'  => [
			'type'    => 'boolean',
			'default' => true
		],
		'showResponse' => [
			'type'    => 'boolean',
			'default' => true
		]
	];

	public function get_name() {
		return 'api-code-samples';
	}

	public function get_default_attributes() {
		return [
			'showRequest'  => true,
			'showResponse' => true
		];
	}

	public function render( $attributes, $content ) {
		$sections = betterdocs()->container->get( Frontend::class )->code_sample_sections();

		if ( null === $sections ) {
			return;
		}

		$show_request  = ! isset( $attributes['showRequest'] ) || $attributes['showRequest'];
		$show_response = ! isset( $attributes['showResponse'] ) || $attributes['showResponse'];

		$rendered          = $show_request && null !== $sections['request'] ? do_blocks( $sections['request'] ) : '';
		$rendered_response = $show_response && null !== $sections['response'] ? do_blocks( $sections['response'] ) : '';

		if ( '' === $rendered && '' === $rendered_response ) {
			return;
		}

		// get_block_wrapper_attributes() escapes its own values.
		echo '<div ' . get_block_wrapper_attributes( [ 'class' => 'betterdocs-api-code-samples-block' ] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		include BETTERDOCS_PRO_ABSPATH . 'views/templates/api-ref/code-samples.php';
		echo '</div>';
	}
}
