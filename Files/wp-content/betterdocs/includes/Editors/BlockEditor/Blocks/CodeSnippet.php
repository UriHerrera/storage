<?php
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class CodeSnippet extends Block {

	protected $frontend_scripts = [ 'betterdocs-code-snippet' ];
	protected $frontend_styles  = [ 'betterdocs-code-snippet' ];

	/**
	 * Unique name of the block
	 * @return string
	 */
	public function get_name() {
		return 'code-snippet';
	}

	/**
	 * Block default attributes
	 * @return array
	 */
	public function get_default_attributes() {
		return [
			'blockId'            => '',
			'blockMeta'          => (object) [],
			'resOption'          => 'Desktop',
			'codeContent'        => '',
			'language'           => 'javascript',
			'codeVariants'       => [],
			'showLanguageLabel'  => true,
			'showCopyButton'     => true,
			'showHeader'         => true,
			'showLineNumbers'    => false,
			'theme'              => 'light',
			'fileName'           => 'filename.js',
			'showFileIcon'       => true,
			'fileIcon'           => '',
			'showTrafficLights'  => true
		];
	}

	/**
	 * Register scripts and styles
	 */
	public function register_scripts() {
		$this->assets_manager->register( 'betterdocs-code-snippet', 'public/css/code-snippet.css' );
		$this->assets_manager->register( 'betterdocs-code-snippet', 'public/js/code-snippet.js', [ 'wp-element' ] );

		// Self-hosted Highlight.js (vendored under assets/static/vendor/highlightjs/,
		// same convention as assets/vendor/scalar/). The frontend + editor read
		// these URLs off the localized config instead of hitting cdnjs.
		$this->assets_manager->localize(
			'betterdocs-code-snippet',
			'BetterDocsCodeSnippetConfig',
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
		// Only enqueue on block editor pages
		$screen = get_current_screen();
		if ( ! $screen || ( $screen->base !== 'post' && $screen->base !== 'site-editor' ) ) {
			return;
		}

		// Enqueue WordPress CodeMirror with basic settings
		if ( function_exists( 'wp_enqueue_code_editor' ) ) {
			wp_enqueue_code_editor( [
				'type' => 'text/html'
			] );
		}

		// Enqueue frontend assets for syntax highlighting in editor preview
		wp_enqueue_style( 'betterdocs-code-snippet' );
		wp_enqueue_script( 'betterdocs-code-snippet' );
	}

	/**
	 * Check if we're in editor mode
	 * @return bool
	 */
	public function is_editor_mode() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- REST request context check.
		$context = isset( $_REQUEST['context'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ) : '';
		return defined( 'REST_REQUEST' ) && REST_REQUEST && 'edit' === $context;
	}

	/**
	 * Render the block
	 * @param array $attributes
	 * @param string $content
	 */
	public function render( $attributes, $content ) {
		// Use the template with view_params
		$this->views( 'templates/parts/code-snippet' );
	}

	/**
	 * View parameters for the template
	 * @return array
	 */
	public function view_params() {
		$attributes = &$this->attributes;

		return [
			'code_content'       => $attributes['codeContent'],
			'language'           => $attributes['language'],
			// Combined [primary + codeVariants] list the template renders as a
			// language dropdown when it holds more than one entry.
			'code_variants'      => $this->build_variants( $attributes ),
			'show_language_label' => $attributes['showLanguageLabel'],
			'show_copy_button'   => $attributes['showCopyButton'],
			'show_header'        => isset( $attributes['showHeader'] ) ? $attributes['showHeader'] : true,
			'show_line_numbers'  => $attributes['showLineNumbers'],
			'theme'              => $attributes['theme'],
			'block_id'           => $attributes['blockId'],
			'widget_type'        => 'blocks',
			'is_editor_mode'     => $this->is_editor_mode(),
			// File Preview Header
			'file_name'          => isset( $attributes['fileName'] ) ? $attributes['fileName'] : 'filename.js',
			'show_traffic_lights' => isset( $attributes['showTrafficLights'] ) ? $attributes['showTrafficLights'] : true,
			'show_file_icon'     => isset( $attributes['showFileIcon'] ) ? $attributes['showFileIcon'] : true,
			'file_icon'          => isset( $attributes['fileIcon'] ) ? $attributes['fileIcon'] : '',
		];
	}

	/**
	 * Build the ordered language list: the primary language/code first, then
	 * each non-empty entry from `codeVariants`. One entry → single-language
	 * (renders exactly as before); more → language dropdown.
	 *
	 * @param array $attributes
	 * @return array<int, array{language: string, code: string, file_name: string}>
	 */
	protected function build_variants( $attributes ) {
		$variants = [];

		$primary_code = isset( $attributes['codeContent'] ) ? (string) $attributes['codeContent'] : '';
		if ( '' !== $primary_code ) {
			$variants[] = [
				'language'  => isset( $attributes['language'] ) ? (string) $attributes['language'] : 'javascript',
				'code'      => $primary_code,
				'file_name' => isset( $attributes['fileName'] ) ? (string) $attributes['fileName'] : ''
			];
		}

		if ( ! empty( $attributes['codeVariants'] ) && is_array( $attributes['codeVariants'] ) ) {
			foreach ( $attributes['codeVariants'] as $variant ) {
				if ( ! is_array( $variant ) ) {
					continue;
				}

				$code = isset( $variant['codeContent'] ) ? (string) $variant['codeContent'] : '';
				if ( '' === $code ) {
					continue;
				}

				$variants[] = [
					'language'  => isset( $variant['language'] ) ? (string) $variant['language'] : 'javascript',
					'code'      => $code,
					'file_name' => isset( $variant['fileName'] ) ? (string) $variant['fileName'] : ''
				];
			}
		}

		return $variants;
	}
}
