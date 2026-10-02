<?php
namespace WPDeveloper\BetterDocs\FrontEnd;

use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Views;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

class TemplateTags extends Base {
	/**
	 * BETTERDOCS_KSES_ALLOWED_HTML
	 * @var array
	 */
	const KSES_ALLOWED_HTML = [
		'span'   => [
			'class' => [],
			'style' => []
		],
		'p'      => [
			'class' => [],
			'style' => []
		],
		'strong' => [],
		'a'      => [
			'href'  => [],
			'title' => []
		],
		'h1'     => [],
		'h2'     => [],
		'h3'     => [],
		'h4'     => [],
		'h5'     => [],
		'h6'     => [],
		'div'    => [
			'class' => [],
			'style' => []
		],
		'svg'    => [
			'xmlns'   => [],
			'width'   => [],
			'height'  => [],
			'viewBox' => [],
			'fill'    => []
		],
		'path'   => [
			'd'    => [],
			'fill' => []
		],
		'rect'   => [
			'width'  => [],
			'height' => [],
			'fill'   => []
		],
		'mask'   => [
			'id'        => [],
			'mask-type' => [],
			'maskUnits' => [],
			'x'         => [],
			'y'         => [],
			'width'     => [],
			'height'    => []
		],
		'g'      => [
			'mask' => []
		]
	];

	const ALLOWED_HTML_TAGS = [
		'article',
		'aside',
		'div',
		'footer',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'header',
		'main',
		'nav',
		'p',
		'section',
		'span'
	];

	/**
	 * Summary of Settings
	 * @var Settings
	 */
	private $settings;
	/**
	 * Summary of Defaults
	 * @var Defaults
	 */
	private $defaults;
	/**
	 * Summary of $mods
	 * @var array
	 */
	private $mods;
	/**
	 * Summary of $query
	 * @var Query
	 */
	private $query;

	/**
	 * Summary of $views
	 * @var Views
	 */
	private $views;

	public function __construct( Settings $settings, Defaults $defaults, Query $query, Views $views ) {
		$this->settings = $settings;
		$this->defaults = $defaults;
		$this->mods     = $defaults->generate_defaults();
		$this->query    = $query;
		$this->views    = $views;
	}

	public function kses( $value ) {
		return wp_kses( $value, self::KSES_ALLOWED_HTML );
	}

	public function icon( $name = 'list', $echo = false ) {
		if ( is_array( $name ) ) {
			if ( $echo ) {
				$this->icon_as_markup( $name, $echo );
				return;
			}

			return $this->icon_as_markup( $name, $echo );
		}

		if ( $echo ) {
			$this->views->get( 'icons/' . $name );
			return;
		}

		ob_start();
		$this->views->get( 'icons/' . $name );
		return ob_get_clean();
	}

	public function icon_as_markup( $icon, $echo = false, $class = [] ) {
		$_original_icon = $icon;
		if ( is_array( $_original_icon ) ) {
			$icon = '';

			if ( ! empty( $_original_icon['value']['url'] ) ) {
				$icon = $_original_icon['value']['url'];
			} elseif ( ! empty( $_original_icon['value'] ) ) {
				$icon = $_original_icon['value'];
			}
		}

		if ( empty( $icon ) ) {
			return;
		}

		$_icon_as_url = filter_var( $icon, FILTER_VALIDATE_URL );

		if ( ! $_icon_as_url ) {
			$class[] = $icon;
			$_markup = sprintf(
				'<i class="%s"></i>',
				esc_attr( implode( ' ', $class ) )
			);
		}

		if ( $_icon_as_url ) {
			$_markup = sprintf(
				'<img src="%s" class="%s" />',
				esc_attr( esc_url( $icon ) ),
				esc_attr( implode( ' ', $class ) )
			);
		}

		if ( $echo ) {
			echo wp_kses_post( $_markup );
		}

		return wp_kses_post( $_markup );
	}

	public function icon_markup( $icon, $face = 'left', $class = [], $echo = true ) {
		if ( is_array( $icon ) ) {
			if ( ! empty( $icon['value']['url'] ) ) {
				$icon = $icon['value']['url'];
			} elseif ( ! empty( $icon['value'] ) ) {
				$icon = $icon['value'];
			}
		}

		$_icon_as_url = filter_var( $icon, FILTER_VALIDATE_URL );

		if ( empty( $class ) ) {
			$class = [
				'el-betterdocs-cg-button-icon',
				'el-betterdocs-cg-button-icon-' . $face
			];
		}

		if ( ! $_icon_as_url ) {
			$class[] = $icon;
			$_markup = sprintf(
				'<i class="%s"></i>',
				esc_attr( implode( ' ', $class ) )
			);
		}

		if ( $_icon_as_url ) {
			$_markup = sprintf(
				'<img src="%s" class="%s" />',
				esc_attr( esc_url( $icon ) ),
				esc_attr( implode( ' ', $class ) )
			);
		}

		// it's html
		if ( preg_match( '/^<.*>/', $icon, $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) ) {
			$_markup = $matches[0];
		}

		if ( ! $echo ) {
			return wp_kses_post( $_markup );
		}

		echo wp_kses_post( $_markup );
	}

	/**
	 * Is Valid Tag
	 *
	 * Returns the tag only when it is in the allowlist, otherwise 'div'. This is
	 * an output-safety control: several templates interpolate the result straight
	 * into an opening/closing tag name. It is called with values that originate
	 * in request/attribute data, which can arrive as a non-string (e.g. an array
	 * `title_tag[]=…`), so guard against that instead of letting strtolower()
	 * throw a TypeError on PHP 8 and fatal the render.
	 *
	 * @param string $tag
	 * @return string
	 */
	public function is_valid_tag( $tag ) {
		if ( ! is_string( $tag ) || '' === $tag ) {
			return 'div';
		}
		return in_array( strtolower( $tag ), self::ALLOWED_HTML_TAGS ) ? $tag : 'div';
	}

	public function option_kses( $value, $label, $current ) {
		return wp_kses(
			sprintf(
				'<option value="%1$s" %3$s>%2$s</option>',
				$value,
				$label,
				selected( $current, $value, false )
			),
			[
				'option' => [
					'value'    => [],
					'selected' => []
				]
			]
		);
	}

	public function get_html_attributes( $attributes = [] ) {
		if ( ! is_array( $attributes ) ) {
			if ( is_string( $attributes ) ) {
				return $attributes;
			}

			return '';
		}

		$rendered_attributes = [];

		foreach ( $attributes as $attribute_key => $attribute_values ) {
			if ( is_array( $attribute_values ) ) {
				$attribute_values = implode( ' ', $attribute_values );
			}

			$rendered_attributes[] = sprintf( '%1$s="%2$s"', $attribute_key, esc_attr( $attribute_values ) );
		}

		return implode( ' ', $rendered_attributes );
	}

	private function get_search_attributes( $layout_specific_attributes = [] ) {
		$search_heading = $search_subheading      = '';
		$heading_tag    = 'h2';
		$subheading_tag = 'h3';

		if ( $this->defaults->get( 'betterdocs_live_search_heading_switch', true ) ) {
			$search_heading    = $this->defaults->get( 'betterdocs_live_search_heading' );
			$search_subheading = $this->defaults->get( 'betterdocs_live_search_subheading' );
			$heading_tag       = $this->defaults->get( 'betterdocs_live_search_heading_tag' );
			$subheading_tag    = $this->defaults->get( 'betterdocs_live_search_subheading_tag' );
		}

		$default_attributes = [
			'placeholder'    => $this->settings->get( 'search_placeholder' ),
			'heading'        => $search_heading,
			'subheading'     => $search_subheading,
			'heading_tag'    => $heading_tag,
			'subheading_tag' => $subheading_tag
		];

		$attributes = array_merge( $default_attributes, $layout_specific_attributes );

		return apply_filters( 'betterdocs_search_shortcode_attributes', $attributes, $this->mods );
	}


	public function search() {
		if ( ! $this->settings->get( 'live_search' ) ) {
			return;
		}

		$layout = $this->defaults->get( 'betterdocs_search_layout_select' );
		// Public hook name predates the prefix convention; renaming would break third-party integrations.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$layout = apply_filters( 'select_live_search_template', Helper::determine_search_layout( $layout ) );

		$shortcode_attributes_new = [];
		if ( $layout == 'layout-2' ) {
			$shortcode_attributes_new = [
				'number_of_faqs'     => $this->defaults->get( 'search_modal_query_initial_number_of_faqs' ),
				'number_of_docs'     => $this->defaults->get( 'search_modal_query_initial_number_of_docs' ),
				'search_button_text' => $this->defaults->get( 'search_button_text', __( 'Search', 'betterdocs' ) ),
				'enable_docs_search' => $this->settings->get('search_modal_search_type', 'all'),
			];

			if( $this->settings->get('search_modal_search_type', 'all') == 'all' ) {
				$shortcode_attributes_new['enable_docs_search'] = true;
				$shortcode_attributes_new['enable_faq_search']  = true;
			} else if( $this->settings->get('search_modal_search_type', 'all')  == 'docs' ) {
				$shortcode_attributes_new['enable_docs_search'] = true;
				$shortcode_attributes_new['enable_faq_search']  = false;
			} else if( $this->settings->get('search_modal_search_type', 'all')  == 'faq' ) {
				$shortcode_attributes_new['enable_docs_search'] = false;
				$shortcode_attributes_new['enable_faq_search']  = true;
			}

			if ( $this->defaults->get( 'search_modal_query_type' ) === 'specific_doc_ids' ) {
				$shortcode_attributes_new['doc_ids'] = $this->defaults->get( 'search_modal_query_doc_ids' );
			} elseif ( $this->defaults->get( 'search_modal_query_type' ) === 'specific_doc_term_ids' ) {
				$shortcode_attributes_new['doc_categories_ids'] = $this->defaults->get( 'search_modal_query_doc_term_ids' );
			}

			if ( $this->defaults->get( 'search_modal_faq_query_type' ) === 'specific_faq_term_ids' ) {
				$shortcode_attributes_new['faq_categories_ids'] = $this->defaults->get( 'search_modal_query_faq_term_ids' );
			}
			// Add AI Search Suggestions setting if AI Chatbot is enabled
			if( betterdocs()->helper->is_ai_chatbot_enabled() && $this->settings->get('enable_ai_powered_search') ) {
				$shortcode_attributes_new['enable_ai_powered_search'] = 'true';
			}
		}

		$_shortcode_license = $this->get_search_attributes( $shortcode_attributes_new );

		ob_start();
		betterdocs()->views->get(
			$layout == 'layout-1' ? 'layout-parts/search' : 'layout-parts/search-2',
			[
				'attributes' => $this->get_html_attributes( $_shortcode_license )
			]
		);
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	public function search_2() {
		if ( ! $this->settings->get( 'live_search' ) ) {
			return;
		}

		// Prepare attributes for search_2 layout
		$shortcode_attributes_new = [];

		// Add AI Search Suggestions setting if AI Chatbot is enabled
		if( betterdocs()->helper->is_ai_chatbot_enabled() && $this->settings->get('enable_ai_powered_search') ) {
			$shortcode_attributes_new['enable_ai_powered_search'] = 'true';
		}

		$_shortcode_license = $this->get_search_attributes( $shortcode_attributes_new );

		ob_start();
		betterdocs()->views->get(
			'layout-parts/search-2',
			[
				'attributes' => $this->get_html_attributes( $_shortcode_license )
			]
		);
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function breadcrumbs() {
	}

	public function supported_htags() {
		$_tags          = '';
		$supported_tags = $this->settings->get( 'supported_heading_tag' );

		if ( is_array( $supported_tags ) ) {
			$_tags = implode( ',', $supported_tags );
		}

		return $_tags;
	}

	public function content( $content, $supported_tags = null, $enable_toc = null ) {
		if ( null === $supported_tags ) {
			$supported_tags = $this->supported_htags();
		}

		if ( null === $enable_toc ) {
			$enable_toc = $this->settings->get( 'enable_toc' );
		}

		if ( $enable_toc && $supported_tags != '' ) {
			preg_match_all( '/(<h([' . $supported_tags . ']{1})[^>]*>).*<\/h\2>/msuU', $content, $matches, PREG_SET_ORDER );

			$index   = 0;
			$content = preg_replace_callback(
				'#<(h[' . $supported_tags . '])(.*?)>(.*?)</\1>#si',
				function ( $matches ) use ( &$index ) {
					$tag          = $matches[1];
					$heading_name = preg_replace( '/<[^<]+?>/', '', $matches[0] );
					$heading_name = str_replace( ["'", '"'], "" ,filter_var( html_entity_decode($heading_name, ENT_QUOTES, 'UTF-8') ) ); //remove apostrophe and decode the utf-8 chars to its original form
					$heading_name = ! empty( $heading_name ) ? strtolower( str_replace( ' ', '-', preg_replace( '/[^\p{L}\p{N}\s]/u', '', $heading_name ) ) ) : '';
					preg_match( '/id="(.+?)"/', $matches[0], $matched_ids );

					if ( isset( $matched_ids[1] ) ) {
						$id = strtolower( $matched_ids[1] );
					} elseif ( ! empty( $heading_name ) && $this->settings->get( 'toc_dynamic_title' ) !== false ) {
						$id = $heading_name;
					} else {
						$id = $index . '-toc-title';
					}

					$index++;
					$hash_link      = '';
					$title_link_ctc = $this->settings->get( 'title_link_ctc' );
					if ( $title_link_ctc ) {
						$hash_link = '<a href="#' . $id . '" class="batterdocs-anchor" data-clipboard-text="' . urldecode( get_permalink() ) . '#' . $id . '" data-title="' . __( 'Copy URL', 'betterdocs' ) . '">#</a>';
					}

					// Get The Class Names Using REGEX
					preg_match( '/class="([^"]*)"/', $matches[2], $class_matches );
					$classes = isset( $class_matches[1] ) ? strtolower( $class_matches[1] ) : '';

					preg_match( '/style="([^"]*)"/', $matches[0], $style_matches );
					$inline_style = isset($style_matches[0]) ? $style_matches[0] : '';
					// $classes = isset( $class_matches[1] ) ? strtolower( $class_matches[1] ) : '';

					$class = ! empty( $classes ) ? $classes . ' betterdocs-content-heading' : 'betterdocs-content-heading';

					return sprintf(
						'<%1$s class="%2$s" id="%3$s" %4$s>%5$s %6$s</%1$s>',
						$tag,
						$class,
						$id,
						$inline_style,
						$matches[3],
						$hash_link
					);
				},
				$content
			);
		}

		if ( ! empty( $content ) ) {
			return '<div id="betterdocs-single-content" class="betterdocs-content">' . $content . '</div>';
		}

		return '';
	}

	public function sidebar( $layout, $layout_type = '', $extra_params = [] ) {
		if ( ! $this->settings->get( 'enable_archive_sidebar' ) ) {
			return;
		}

		$_template_path = 'templates/sidebars/sidebar-1';
		if ( $layout == 'layout-4' ) {
			$_template_path = 'templates/sidebars/sidebar-5';
		} elseif ( $layout == 'layout-5' ) {
			$_template_path = 'templates/sidebars/sidebar-4';
		} elseif ( $layout == 'layout-7' ) {
			$_template_path = 'templates/sidebars/sidebar-7';
		} elseif ( $layout == 'layout-8' ) {
			$_template_path = 'templates/sidebars/sidebar-8';
		} elseif ( $layout == 'layout-2' ) {
			$_template_path = 'templates/sidebars/sidebar-2';
		} elseif ( $layout == 'layout-3' ) {
			$_template_path = 'templates/sidebars/sidebar-3';
		} elseif ( $layout == 'archive-sidebar' ) {
			$_template_path = 'templates/sidebars/archive-sidebar';
		}

		$_template_path = apply_filters( 'betterdocs_archive_sidebar_template', $_template_path, $layout );

		$default_params = [
			'force'       => true,
			'layout_type' => $layout_type
		];

		$default_params = array_merge( $default_params, $extra_params );

		betterdocs()->views->get( $_template_path, $default_params );
	}

	public function term_options( $taxonomy = 'doc_category', $current_term = '', $parent = false, $kb_slug = '' ) {
		$_args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'number'     => 0
		];

		if ( $parent ) {
			$_args['parent'] = 0;
		}

		// Add KB filtering if kb_slug is provided
		if ( ! empty( $kb_slug ) && $taxonomy === 'doc_category' ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- KB filtering is core functionality and requires meta_query.
			$_args['meta_query'] = [
				[
					'key'     => 'doc_category_knowledge_base',
					'value'   => serialize(strval($kb_slug)),
					'compare' => 'LIKE'
				]
			];
		}

		$_terms = $this->query->get_terms( $this->query->terms_query( $_args ) );

		if ( is_wp_error( $_terms ) ) {
			return;
		}

		$html = '';

		foreach ( $_terms as $term ) {
			$html .= $this->option_kses( $term->slug, $term->name, $current_term );
		}

		return $html;
	}

	public function category_icon( $params ) {
		if ( ! isset( $params['show_icon'], $params['term'] ) ) {
			return;
		}

		$term = $params['term'];

		$this->views->get(
			'template-parts/category-icon',
			[
				'show_icon' => $params['show_icon'],
				'term_id'   => $term->term_id
			]
		);
	}

	public function category_title( $params ) {
		if ( ! isset( $params['show_title'], $params['term'], $params['title_tag'] ) || ! $params['show_title'] ) {
			return;
		}

		$term = $params['term'];

		$this->views->get(
			'template-parts/category-title',
			[
				'title' => $term->name,
				'tag'   => $params['title_tag']
			]
		);
	}

	public function category_description( $params ) {
		if ( ! isset( $params['show_description'], $params['term'] ) && empty( $params['term']->description ) ) {
			return;
		}

		$this->views->get(
			'template-parts/category-description',
			[
				'description' => $params['term']->description
			]
		);
	}

	public function collapse_icon( $params ) {
		betterdocs()->views->get( 'icons/collapse' );
	}

	public function category_counts( $params ) {
		if ( ! isset( $params['show_count'], $params['counts'] ) ) {
			return;
		}

		$this->views->get(
			'template-parts/category-counter',
			[
				'show_count' => $params['show_count'],
				'counts'     => $params['counts']
			]
		);
	}

	public function sub_category_counts( $params ) {
		if ( ! isset( $params['show_count'], $params['counts'] ) ) {
			return;
		}

		$this->views->get(
			'template-parts/sub-category-counter',
			[
				'show_count' => $params['show_count'],
				'counts'     => $params['counts']
			]
		);
	}

	public function last_update( $params ) {
		if ( ! isset( $params['last_update'], $params['last_update'] ) ) {
			return;
		}

		$this->views->get(
			'template-parts/last-update',
			[
				'last_update' => $params['last_update']
			]
		);
	}

	public function wrapper_div( $params ) {
		if ( ! isset( $params['sequence'] ) ) {
			return;
		}

		ob_start();
		foreach ( $params['sequence'] as $sequenceName ) {
			$this->{$sequenceName}( $params );
		}
		$sequence_content = ob_get_clean();

		$classNames = isset( $params['class'] ) ? $params['class'] : '';

		echo '<div class="betterdocs-dynamic-wrapper ' . esc_attr( $classNames ) . '">';
		echo wp_kses_post( $sequence_content );
		echo '</div>';
	}

	/**
	 * Shortcode Attributes
	 * @param mixed $atts
	 * @param mixed $shortcode
	 *
	 * @return string
	 */
	public function shortcode_atts( $atts, $shortcode, $layout, ...$args ) {
		$tagname = 'betterdocs_archive_template_shortcode_params';
		if ( strpos( $layout, 'sidebar-' ) === 0 ) {
			$tagname = 'betterdocs_sidebar_template_shortcode_params';
		}

		// $tagname is selected from an internal allowlist of betterdocs_*_shortcode_params hook names above.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		$_shortcode_attributes = apply_filters( $tagname, $atts, $shortcode, $layout, $args );
		return betterdocs()->template_helper->get_html_attributes( $_shortcode_attributes );
	}

	/**
	 * Output the page header for BetterDocs full-page templates.
	 *
	 * Block (FSE) themes ship no `header.php`, so a bare `get_header()` falls through
	 * `locate_template()` to WordPress's deprecated theme-compat header (just the site
	 * title) instead of the active theme's real header. For block themes we therefore
	 * emit the canonical document head — so `wp_head()` still runs — and render the
	 * theme's `header` template part via `block_header_area()`, mirroring the block
	 * template canvas. Classic themes keep using `get_header()` unchanged. (fbs-79870)
	 *
	 * @return void
	 */
	public function header() {
		if ( ! betterdocs()->helper->current_theme_is_fse_theme() || ! function_exists( 'block_header_area' ) ) {
			get_header();
			return;
		}
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
		<?php
		block_header_area();
	}

	/**
	 * Output the page footer for BetterDocs full-page templates.
	 *
	 * Mirrors {@see TemplateTags::header()}: under block (FSE) themes render the active
	 * theme's `footer` template part via `block_footer_area()`, close the document
	 * wrapper, and fire `wp_footer()`. Classic themes keep using `get_footer()`. (fbs-79870)
	 *
	 * @return void
	 */
	public function footer() {
		if ( ! betterdocs()->helper->current_theme_is_fse_theme() || ! function_exists( 'block_footer_area' ) ) {
			get_footer();
			return;
		}
		block_footer_area();
		?>
</div><!-- .wp-site-blocks -->
<?php wp_footer(); ?>
</body>
</html>
		<?php
	}
}
