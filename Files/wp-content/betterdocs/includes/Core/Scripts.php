<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\FrontEnd\PrintTemplate;

class Scripts extends Base {
	protected $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'init', [ $this, 'init' ], 1 );
	}

	public function init() {
		$assets = betterdocs()->assets;

		// Vendor CSS
		$assets->register( 'simplebar', 'vendor/css/simplebar.css' );

		// Vendor JS
		$assets->register( 'simplebar', 'vendor/js/simplebar.js' );

		if ( ! wp_script_is( 'clipboard', 'registered' ) ) {
			$assets->register( 'clipboard', 'vendor/js/clipboard.min.js' );
		}

		// Shortcodes Styles Registrations
		$assets->register( 'betterdocs-search', 'public/css/search.css' );
		$assets->register( 'betterdocs-search-modal', 'public/css/search-modal.css' );
		$assets->register( 'betterdocs-social-share', 'public/css/social-share.css' );
		$assets->register( 'betterdocs-feedback-form', 'public/css/feedback-form.css' );
		$assets->register( 'betterdocs-reactions', 'public/css/reactions.css' );
		$assets->register( 'betterdocs-toc', 'public/css/toc.css' );
		$assets->register( 'betterdocs-faq', 'public/css/faq.css' );
		$assets->register( 'betterdocs-category-tab-grid', 'public/css/category-tab-grid.css' );
		$assets->register( 'reading-time', 'public/css/reading-time.css' );

		// Template Parts
		$assets->register( 'betterdocs-sidebar', 'public/css/sidebar.css' );
		$assets->register( 'betterdocs-breadcrumb', 'public/css/breadcrumb.css' );
		$assets->register( 'betterdocs-single', 'public/css/single.css' );
		$assets->register( 'betterdocs-docs', 'public/css/docs.css' );
		$assets->register( 'betterdocs-pagination', 'public/css/pagination.css' );
		$assets->register( 'betterdocs-doc_category', 'public/css/tax-doc_category.css', [ 'betterdocs-breadcrumb', 'betterdocs-pagination' ] );
		$assets->register( 'betterdocs-category-archive-header', 'public/css/archive-header.css' );
		$assets->register( 'betterdocs-category-archive-doc-list', 'public/css/archive-doc-list.css' );
		$assets->register( 'betterdocs-article-summary', 'public/css/article-summary.css' );
		$assets->register( 'betterdocs-author', 'public/css/author.css' );

		$assets->register( 'betterdocs-category-grid', 'public/css/category-grid.css', [ 'simplebar' ] );
		$assets->register( 'betterdocs-category-box', 'public/css/category-box.css' );
		$assets->register( 'betterdocs-category-grid-list', 'public/css/category-grid-list.css' );
		wp_register_style( 'betterdocs-category-grid-design', false );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_category_grid_design_styles' ], 20 );
		$this->add_category_grid_design_styles();

		// JS
		$assets->register( 'betterdocs', 'public/js/betterdocs.js', [ 'jquery' ] );
		// Shortcode JS
		$assets->register( 'betterdocs-category-toggler', 'public/js/category-toggler.js', [ 'jquery' ] );
		$assets->register(
			'betterdocs-category-grid',
			'public/js/category-grid.js',
			[
				'jquery',
				'masonry',
				'simplebar',
				'betterdocs-category-toggler'
			]
		);
		$assets->register( 'betterdocs-faq', 'shortcodes/js/faq.js', [ 'jquery' ] );
		$assets->register( 'betterdocs-reactions', 'shortcodes/js/reactions.js', [ 'jquery' ] );
		$assets->register( 'betterdocs-search', 'shortcodes/js/search.js', [ 'jquery' ] );
		$assets->register( 'betterdocs-search-modal', 'shortcodes/js/search-modal.js', [ 'jquery' ] );

		/**
		 * Print template config for the single-doc Print / Save-as-PDF button.
		 *
		 * These values feed the print *popup* opened by the BetterDocs print
		 * icon. The browser's own Ctrl/Cmd+P route is handled separately by
		 * {@see PrintTemplate}, which resolves the same two values — see that
		 * class for why the two routes use different layout mechanisms.
		 *
		 * Both toggles live in `betterdocs_settings` (BetterDocs → Settings →
		 * Layout → Single Doc → General) rather than theme mods, so they stay
		 * reachable on block/FSE themes, which have no Customizer. Both default
		 * to OFF; while a toggle is off the value resolves empty and the print
		 * script renders nothing for it.
		 */
		$print_logo   = PrintTemplate::get_logo();
		$print_footer = PrintTemplate::get_footer();

		$assets->localize(
			'betterdocs',
			'betterdocsConfig',
			[
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'copy_text'         => __( 'Copied', 'betterdocs' ),
				'sticky_toc_offset' => $this->settings->get( 'sticky_toc_offset' ),
				'summary_nonce'     => wp_create_nonce( 'betterdocs_article_summary_nonce' ),
				'summary_error'     => __( 'Failed to generate doc summary. Please try again.', 'betterdocs' ),
				'print'             => [
					'logo'   => $print_logo ? esc_url( $print_logo ) : '',
					'footer' => $print_footer ? wp_kses_post( $print_footer ) : ''
				]
			]
		);

		$assets->localize(
			'betterdocs-search',
			'betterdocsSearchConfig',
			[
				// Root-relative so live search stays same-origin when the KB is
				// served on a different host than the Site Address (subdomain /
				// domain alias / reverse proxy). See Helper::frontend_ajax_url().
				'ajax_url'            => Helper::frontend_ajax_url(),
				'search_letter_limit' => $this->settings->get( 'search_letter_limit' )
			]
		);

		$assets->localize(
			'betterdocs-search-modal',
			'betterdocsSearchModalConfig',
			[
				// Same-origin (see betterdocsSearchConfig above).
				'ajax_url'               => Helper::frontend_ajax_url(),
				'rest_url' 			     => esc_url_raw(rest_url()),
				'advance_search'         => $this->settings->get( 'advance_search' ),
				'child_category_exclude' => $this->settings->get( 'child_category_exclude' ),
				'popular_keyword_limit'  => $this->settings->get( 'popular_keyword_limit' ),
				'search_letter_limit'    => $this->settings->get( 'search_letter_limit' ),
				'search_placeholder'     => $this->settings->get( 'search_placeholder' ),
				'search_button_text'     => $this->settings->get( 'search_button_text' ),
				'search_not_found_text'  => $this->settings->get( 'search_not_found_text' ),
				'kb_based_search'        => $this->settings->get( 'kb_based_search' )
			]
		);

		/**
		 * Localize This In Order To Know If This Shortcode Is Arriving From Betterdocs Templates Or Not
		 */
		betterdocs()->assets->localize(
			'betterdocs-category-grid',
			'betterdocsCategoryGridConfig',
			[
				'is_betterdocs_templates' => betterdocs()->helper->is_templates() ? true : false,
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'lazy_load_action'        => 'betterdocs_lazy_category_body'
			]
		);

		$this->blocks( $assets );
		$this->admin_assets( $assets );

		return $assets;
	}

	private function category_grid_design_color( $key, $default ) {
		$value = (string) $this->settings->get( $key, $default );

		if ( preg_match( '/\A(?:#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|transparent)\z/i', $value ) ) {
			return $value;
		}

		return $default;
	}

	private function category_grid_design_number( $key, $default, $maximum = 200 ) {
		$value = absint( $this->settings->get( $key, $default ) );

		return min( $value, $maximum );
	}

	public function enqueue_category_grid_design_styles() {
		wp_enqueue_style( 'betterdocs-category-grid-design' );
	}

	private function add_category_grid_design_styles() {
		$grid_roots = [
			'.betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper',
			'.betterdocs-category-list-wrapper .betterdocs-category-list-inner-wrapper',
			'.betterdocs-category-grid-list-wrapper .betterdocs-category-grid-list-inner-wrapper'
		];

		$selectors = [
			'root'              => [],
			'card'              => [],
			'card_parts'        => [],
			'title'             => [],
			'title_hover'       => [],
			'article'           => [],
			'article_hover'     => [],
			'count'             => [],
			'count_inner'       => [],
			'button'            => [],
			'button_hover'      => []
		];

		foreach ( $grid_roots as $grid_root ) {
			$root = 'body ' . $grid_root;

			$selectors['root'][] = $root;
			$selectors['card'][] = $root . ' > .betterdocs-single-category-wrapper .betterdocs-single-category-inner';
			$selectors['card_parts'][] = $root . ' > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header';
			$selectors['card_parts'][] = $root . ' > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body';
			$selectors['card_parts'][] = $root . ' > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-footer';
			$selectors['title'][] = $root . ' .betterdocs-category-title';
			$selectors['title'][] = $root . ' .betterdocs-category-title a';
			$selectors['title_hover'][] = $root . ' .betterdocs-category-title:hover';
			$selectors['title_hover'][] = $root . ' .betterdocs-category-title a:hover';
			$selectors['article'][] = $root . ' .betterdocs-articles-list li a';
			$selectors['article'][] = $root . ' .betterdocs-entry-body li a';
			$selectors['article_hover'][] = $root . ' .betterdocs-articles-list li a:hover';
			$selectors['article_hover'][] = $root . ' .betterdocs-entry-body li a:hover';
			$selectors['count'][] = $root . ' .betterdocs-category-items-counts';
			$selectors['count_inner'][] = $root . ' .betterdocs-category-items-counts span';
			$selectors['count'][] = $root . ' .betterdocs-category-grid-inner-wrapper > :not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts';
			$selectors['count_inner'][] = $root . ' .betterdocs-category-grid-inner-wrapper > :not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts span';
			$selectors['button'][] = $root . ' .betterdocs-footer a';
			$selectors['button'][] = $root . ' .betterdocs-footer button';
			$selectors['button_hover'][] = $root . ' .betterdocs-footer a:hover';
			$selectors['button_hover'][] = $root . ' .betterdocs-footer button:hover';
		}

		$card_background       = $this->category_grid_design_color( 'category_grid_card_background', '#ffffff' );
		$card_border_color     = $this->category_grid_design_color( 'category_grid_card_border_color', '#e7e7e7' );
		$card_border_width     = $this->category_grid_design_number( 'category_grid_card_border_width', 0 );
		$card_border_radius    = $this->category_grid_design_number( 'category_grid_card_border_radius', 0 );
		$card_padding           = $this->category_grid_design_number( 'category_grid_card_padding', 0 );
		$grid_gap              = $this->category_grid_design_number( 'category_grid_gap', 15 );
		$title_color           = $this->category_grid_design_color( 'category_grid_title_color', '#3f5876' );
		$title_hover_color     = $this->category_grid_design_color( 'category_grid_title_hover_color', '#528ffe' );
		$title_font_size       = $this->category_grid_design_number( 'category_grid_title_font_size', 20, 72 );
		$article_color         = $this->category_grid_design_color( 'category_grid_article_color', '#566e8b' );
		$article_hover_color   = $this->category_grid_design_color( 'category_grid_article_hover_color', '#528fff' );
		$article_font_size     = $this->category_grid_design_number( 'category_grid_article_font_size', 15, 72 );
		$count_background      = $this->category_grid_design_color( 'category_grid_count_background', '#528ffe1a' );
		$count_inner_background = $this->category_grid_design_color( 'category_grid_count_inner_background', '#528ffe33' );
		$count_color           = $this->category_grid_design_color( 'category_grid_count_color', '#528ffe' );
		$button_background     = $this->category_grid_design_color( 'category_grid_button_background', '#ffffff' );
		$button_color          = $this->category_grid_design_color( 'category_grid_button_color', '#528ffe' );
		$button_border_color   = $this->category_grid_design_color( 'category_grid_button_border_color', '#528ffe' );
		$button_hover_background = $this->category_grid_design_color( 'category_grid_button_hover_background', '#528ffe' );
		$button_hover_color    = $this->category_grid_design_color( 'category_grid_button_hover_color', '#ffffff' );
		$button_hover_border   = $this->category_grid_design_color( 'category_grid_button_hover_border_color', '#528ffe' );
		$button_radius         = $this->category_grid_design_number( 'category_grid_button_border_radius', 50 );
		$button_font_size      = $this->category_grid_design_number( 'category_grid_button_font_size', 16, 72 );
		$header_border_color   = $this->category_grid_design_color( 'category_grid_header_border_color', '#528ffe' );
		$header_border_width   = $this->category_grid_design_number( 'category_grid_header_border_width', 2 );

		$border_style = $this->settings->get( 'category_grid_card_border_style', 'solid' );
		if ( ! in_array( $border_style, [ 'solid', 'dashed', 'dotted' ], true ) ) {
			$border_style = 'solid';
		}

		$shadow = [
			'default' => '0 10px 100px 0 #282f6214',
			'none'    => 'none',
			'subtle'  => '0 2px 12px rgba(16,24,40,.08)',
			'strong'  => '0 12px 30px rgba(16,24,40,.16)'
		];
		$shadow_key = $this->settings->get( 'category_grid_card_shadow', 'default' );
		$shadow     = isset( $shadow[ $shadow_key ] ) ? $shadow[ $shadow_key ] : $shadow['default'];

		$css  = implode( ",\n", $selectors['root'] ) . " { --gap: {$grid_gap}; }\n";
		$css .= sprintf(
			"%s { background-color: %s; border: %dpx %s %s; border-radius: %dpx; box-shadow: %s; }\n",
			implode( ",\n", $selectors['card'] ),
			$card_background,
			$card_border_width,
			$border_style,
			$card_border_color,
			$card_border_radius,
			$shadow
		);
		$css .= sprintf( "%s { color: %s; font-size: %dpx; }\n", implode( ",\n", $selectors['title'] ), $title_color, $title_font_size );
		$css .= sprintf( "%s { color: %s; }\n", implode( ",\n", $selectors['title_hover'] ), $title_hover_color );
		$css .= sprintf( "%s { color: %s; font-size: %dpx; }\n", implode( ",\n", $selectors['article'] ), $article_color, $article_font_size );
		$css .= sprintf( "%s { color: %s; }\n", implode( ",\n", $selectors['article_hover'] ), $article_hover_color );
		$css .= sprintf( "%s { background-color: %s; }\n", implode( ",\n", $selectors['count'] ), $count_background );
		$css .= sprintf( "%s { background-color: %s; color: %s; }\n", implode( ",\n", $selectors['count_inner'] ), $count_inner_background, $count_color );
		$css .= sprintf(
			"%s { background-color: %s; border-color: %s; border-radius: %dpx; color: %s; font-size: %dpx; }\n",
			implode( ",\n", $selectors['button'] ),
			$button_background,
			$button_border_color,
			$button_radius,
			$button_color,
			$button_font_size
		);
		$css .= sprintf(
			"%s { background-color: %s; border-color: %s; color: %s; }\n",
			implode( ",\n", $selectors['button_hover'] ),
			$button_hover_background,
			$button_hover_border,
			$button_hover_color
		);

		if ( $card_padding > 0 ) {
			$css .= sprintf( "%s { padding: %dpx; }\n", implode( ",\n", $selectors['card_parts'] ), $card_padding );
		}

		$css .= sprintf(
			"body .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper.layout-1 .betterdocs-category-header .betterdocs-category-header-inner { border-bottom-color: %s; border-bottom-width: %dpx; }\n",
			$header_border_color,
			$header_border_width
		);

		wp_add_inline_style( 'betterdocs-category-grid-design', $css );
	}



	public function blocks( $assets ) {
		$assets->register( 'betterdocs-fontawesome', 'vendor/css/font-awesome5.css' );
		$assets->register( 'betterdocs-blocks-category-box', 'blocks/categorybox/default.css' );
		$assets->register( 'betterdocs-blocks-category-grid', 'blocks/categorygrid/default.css' );
		$assets->register( 'betterdocs-blocks-category-tech-layout', 'blocks/category-tech-layout/default.css' );
		$assets->register( 'betterdocs-blocks-category-slate-layout', 'blocks/category-slate-layout/default.css' );
		$assets->register( 'betterdocs-feedback-form-editor', 'blocks/feedback-form/style-feedback-editor.css' );
		$assets->register( 'betterdocs-doc-archive-list', 'blocks/doc-archive-list/default.css' );
	}

	public function admin_assets( $assets ) {
		$assets->register( 'betterdocs-article-quality-score', 'admin/js/article-quality-score.js', [ 'jquery' ] );

	}
}
