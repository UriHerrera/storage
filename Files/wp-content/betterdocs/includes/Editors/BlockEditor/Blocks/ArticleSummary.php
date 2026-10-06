<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class ArticleSummary extends Block {

	protected $frontend_styles = [
		'betterdocs-article-summary'
	];

	/**
	 * Get block name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'article-summary';
	}

	/**
	 * Get default attributes
	 *
	 * @return array
	 */
	public function get_default_attributes() {
		return [
			'blockId'           => '',
			'customTitle'       => '',
			'showTitle'         => true,
			'titleColor'        => '#2c3e50',
			'titleFontSize'     => 16,
			'titleFontWeight'   => '600',
			'backgroundColor'   => '#ffffff',
			'borderColor'       => '#e1e5e9',
			'borderWidth'       => 1,
			'borderRadius'      => 8,
			'padding'           => null,
			'margin'            => null,
			'contentColor'      => '#555555',
			'contentFontSize'   => 14,
			'contentLineHeight' => 1.6,
			'loadingColor'      => '#666666',
			'iconColor'         => 'inherit',
			'iconSize'          => 14,
		];
	}

	/**
	 * Render the block
	 *
	 * @param array $attributes Block attributes
	 * @param string $content Block content
	 * @return void
	 */
	public function render( $attributes, $content ) {
		// For frontend, check if it's a docs post type
		if ( get_post_type() !== 'docs' ) {
			return;
		}

		// Check if Article Summary feature is enabled (skip in editor mode)
		if ( ! $this->is_editor_mode() ) {
			$article_summary = betterdocs()->article_summary;
			if ( ! $article_summary || ! $article_summary->is_enabled() ) {
				return;
			}
		}

		// Use the template with view_params
		$this->views( 'templates/parts/article-summary' );
	}

	/**
	 * Check if we're in editor mode
	 *
	 * @return bool
	 */
	private function is_editor_mode() {
		// Check if we're in Gutenberg editor.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- REST request context check; nonce verified by REST cookie auth.
		$context = isset( $_REQUEST['context'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ) : '';
		return defined( 'REST_REQUEST' ) && REST_REQUEST && 'edit' === $context;
	}

	/**
	 * Get view parameters for the template
	 *
	 * @return array
	 */
	public function view_params() {
		// Extract basic attributes
		$block_id = $this->attributes['blockId'] ?? '';
		$custom_title = $this->attributes['customTitle'] ?? '';
		$show_title = $this->attributes['showTitle'] ?? true;

		return [
			'post_id' => get_the_ID(),
			'custom_title' => $custom_title,
			'show_title' => $show_title,
			'widget_type' => 'blocks',
			'blockId' => $block_id,
			'is_editor_mode' => $this->is_editor_mode(),
		];
	}


}
