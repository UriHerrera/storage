<?php
namespace WPDeveloper\BetterDocs\Editors\BlockEditor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;

class FontLoader extends Base {

	public static $registered_blocks = [
		'betterdocs/betterdocs-print',
		'betterdocs/breadcrumb',
		'betterdocs/categorybox',
		'betterdocs/categorygrid',
		'betterdocs/category-slate-layout',
		'betterdocs/archive-doc-list',
		'betterdocs/single-doc-content',
		'betterdocs/date',
		'betterdocs/doc-navigation',
		'betterdocs/feedback-form',
		'betterdocs/reactions',
		'betterdocs/searchbox',
		'betterdocs/sidebar',
		'betterdocs/social-share',
		'betterdocs/table-of-contents',
		'betterdocs/multiple-kb',
		'betterdocs/betterdocs-encyclopedia',
		'betterdocs/glossary-single-template',
		'betterdocs/faq',
		'betterdocs/multiple-kb-tab',
		'betterdocs/categoryhandbook',
		'betterdocs/popular-docs',
		'betterdocs/related-categories',
		'betterdocs/code-snippet',
		'betterdocs/article-summary'
	];

	public static $retrieved_fonts = [];

	public function __construct() {
		add_filter( 'render_block', [ $this, 'get_betterdocs_blocks' ], 10, 2 );
		add_action( 'wp_footer', [ $this, 'enqueue_google_fonts' ], 10 );
	}

	public function get_betterdocs_blocks( $block_content, $block ) {
		if ( isset( $block['attrs'] ) ) {
			if ( in_array( $block['blockName'], self::$registered_blocks ) ) {
				$fonts                 = self::get_fonts_family( $block['attrs'] );
				self::$retrieved_fonts = array_unique( array_merge( self::$retrieved_fonts, $fonts ) );
			}
		}

		return $block_content;
	}

	public static function get_fonts_family( $attributes ) {
		$keys               = preg_grep( '/^(\w+)FontFamily/i', array_keys( $attributes ), 0 );
		$google_font_family = [];
		foreach ( $keys as $key ) {
			$google_font_family[ $attributes[ $key ] ] = $attributes[ $key ];
		}
		return $google_font_family;
	}

	public function enqueue_google_fonts() {
		if ( ! empty( self::$retrieved_fonts ) ) {
			$google_fonts      = '';
			$google_fonts_attr = ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';
			foreach ( self::$retrieved_fonts as $font ) {
				$google_fonts .= str_replace( ' ', '+', trim( $font ) ) . $google_fonts_attr . '|';
			}
			if ( ! empty( $google_fonts ) ) {
				$query_args = [
					'family' => $google_fonts
				];
				wp_register_style(
					'betterdocs-goggle-fonts',
					add_query_arg( $query_args, '//fonts.googleapis.com/css' ),
					[],
					betterdocs()->version
				);
				wp_enqueue_style( 'betterdocs-goggle-fonts' );
			}
		}
	}
}
