<?php
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class CodeSnippetTab extends Block {

	protected $frontend_scripts = [ 'betterdocs-code-snippet-tab' ];
	protected $frontend_styles  = [ 'betterdocs-code-snippet-tab' ];

	/**
	 * Unique name of the block
	 * @return string
	 */
	public function get_name() {
		return 'code-snippet-tab';
	}

	/**
	 * Block default attributes
	 * @return array
	 */
	public function get_default_attributes() {
		return [
			'blockId'           => '',
			'blockMeta'         => (object) [],
			'resOption'         => 'Desktop',
			// [ { status, language, codeContent } ] — one status tab per entry.
			// Must mirror the editor default in `src/attributes.js`: Gutenberg
			// omits attributes that still equal their default from the saved
			// markup, so an untouched block arrives here with no `responses` at
			// all. Defaulting to an empty list rendered nothing on the frontend
			// while the editor happily showed a 200 tab.
			'responses'         => [
				[
					'status'      => '200',
					'language'    => 'json',
					'codeContent' => "{\n  \"success\": true\n}"
				]
			],
			'showCopyButton'    => true,
			'showHeader'        => true,
			'showLineNumbers'   => false,
			'theme'             => 'light'
		];
	}

	/**
	 * Register scripts and styles
	 */
	public function register_scripts() {
		$this->assets_manager->register( 'betterdocs-code-snippet-tab', 'public/css/code-snippet-tab.css' );
		$this->assets_manager->register( 'betterdocs-code-snippet-tab', 'public/js/code-snippet-tab.js', [ 'wp-element' ] );

		// Self-hosted Highlight.js (vendored under assets/static/vendor/highlightjs/) —
		// same convention as the Code Snippet block; kept self-contained so the
		// Code Snippet Tab works with no Code Snippet block on the page.
		$this->assets_manager->localize(
			'betterdocs-code-snippet-tab',
			'BetterDocsCodeSnippetTabConfig',
			[
				'hljsUrl'  => $this->assets_manager->asset_url( 'vendor/highlightjs/highlight.min.js' ),
				'cssLight' => $this->assets_manager->asset_url( 'vendor/highlightjs/github.min.css' ),
				'cssDark'  => $this->assets_manager->asset_url( 'vendor/highlightjs/github-dark.min.css' )
			]
		);

		// Enqueue CodeMirror for Gutenberg editor
		if ( is_admin() ) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 5 );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_editor_assets' ], 5 );
		}
	}

	/**
	 * Enqueue CodeMirror assets for the block editor
	 */
	public function enqueue_editor_assets() {
		$screen = get_current_screen();
		if ( ! $screen || ( $screen->base !== 'post' && $screen->base !== 'site-editor' ) ) {
			return;
		}

		if ( function_exists( 'wp_enqueue_code_editor' ) ) {
			wp_enqueue_code_editor( [
				'type' => 'text/html'
			] );
		}

		wp_enqueue_style( 'betterdocs-code-snippet-tab' );
		wp_enqueue_script( 'betterdocs-code-snippet-tab' );
	}

	/**
	 * Render the block
	 * @param array $attributes
	 * @param string $content
	 */
	public function render( $attributes, $content ) {
		$this->views( 'templates/parts/code-snippet-tab' );
	}

	/**
	 * View parameters for the template
	 * @return array
	 */
	public function view_params() {
		$attributes = &$this->attributes;

		return [
			'responses'           => $this->build_responses( $attributes ),
			'show_copy_button'    => $attributes['showCopyButton'],
			'show_header'         => isset( $attributes['showHeader'] ) ? $attributes['showHeader'] : true,
			'show_line_numbers'   => $attributes['showLineNumbers'],
			'theme'               => $attributes['theme'],
			'block_id'            => $attributes['blockId'],
			'widget_type'         => 'blocks'
		];
	}

	/**
	 * Normalise the `responses` attribute into the template's tab list. Each
	 * tab is `{ status, language, code }`; entries with empty code are dropped.
	 *
	 * @param array $attributes
	 * @return array<int, array{status: string, language: string, code: string}>
	 */
	protected function build_responses( $attributes ) {
		$tabs = [];

		if ( empty( $attributes['responses'] ) || ! is_array( $attributes['responses'] ) ) {
			return $tabs;
		}

		foreach ( $attributes['responses'] as $response ) {
			if ( ! is_array( $response ) ) {
				continue;
			}

			$code = isset( $response['codeContent'] ) ? (string) $response['codeContent'] : '';
			if ( '' === $code ) {
				continue;
			}

			$tabs[] = [
				'status'   => isset( $response['status'] ) ? (string) $response['status'] : '',
				'language' => isset( $response['language'] ) ? (string) $response['language'] : 'json',
				'code'     => $code
			];
		}

		return $tabs;
	}
}
