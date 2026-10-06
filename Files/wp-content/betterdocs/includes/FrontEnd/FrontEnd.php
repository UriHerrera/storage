<?php
namespace WPDeveloper\BetterDocs\FrontEnd;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\Enqueue;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class FrontEnd extends Base {
	private $container;
	private $database;
	/**
	 * Enqueue
	 * @var Enqueue
	 */
	private $assets;
	/**
	 * Settings
	 * @var Settings
	 */
	private $settings;
	private $widget_attributes = [];
	private $widget_type       = '';
	private $search_ui_assets_added = false;

	public function __construct( Container $container, Database $database, Settings $settings ) {
		$this->container = $container;
		$this->database  = $database;
		$this->settings  = $settings;

		$this->assets = $this->container->get( Enqueue::class );

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        /**
         * Update The 'Edit Site' Url In FSE Mode For Betterdocs Templates Only
         */
        if ( Helper::is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && wp_is_block_theme() ) {
            add_action( 'admin_bar_menu', [$this, 'fse_url_update'], 41, 1 );
        }

		add_filter( 'betterdocs_layout_filename', [ $this, 'layout_filename' ], 10, 2 );

		add_action( 'betterdocs_docs_before_social', [ $this, 'article_reactions' ] );

		add_action( 'betterdocs_before_render', [ $this, 'before_render' ], 11, 2 );
		add_action( 'betterdocs_after_render', [ $this, 'after_render' ], 11, 2 );
		add_action( 'betterdocs_before_shortcode_load', [ $this, 'add_shortcode_font_style' ], 10, 1 );

		//Remove Saliant Theme Script For (Delay Javascript Exection), which causes issue with betterdocs sidebar toggle, issue number (#1234)
		add_action( 'nectar_hook_before_body_close', [ $this, 'dequeue_saliant_theme_script' ], 99999 );

		//Remove our search selector from from Searchanise plugin for woocommerce, conflicts with betterdocs search (Bug Fix Card -> https://trello.com/c/lXzrtv2f/1313-client-issue-betterdocs-is-conflicting-with-the-searchanise-plugin)
		add_filter( 'se_load_search_widgets', [ $this, 'exclude_betterdocs_search' ], 10, 1 );

        //Fix Betterdocs Search Issue With HostCluster Theme
        if ( function_exists( 'hostcluster_search_filter' ) ) {
            remove_filter( 'pre_get_posts', 'hostcluster_search_filter' );
        }

        //render authors template
        add_filter( 'template_include', [$this, 'render_authors_template'], 10, 1 );
    }

    public function render_authors_template( $template_path ) {
        $post_type         = get_query_var( 'post_type' ) != null ? get_query_var( 'post_type' ) : '';
        $author_id         = get_query_var( 'author' ) != null ? get_query_var( 'author' ) : 0;
        $author_docs_count = count_user_posts( $author_id, 'docs' );

        // Only override on an actual author archive — otherwise this also fires on
        // the main docs archive (where $author_id falls back to 0) whenever any doc
        // has post_author 0, hijacking the docs/MKB archive (and its FSE template)
        // with the author template.
        if ( is_author() && $post_type == 'docs' && $author_docs_count > 0 ) {
            wp_enqueue_style('betterdocs-breadcrumb');
            $template_path = BETTERDOCS_ABSPATH . 'views/templates/authors/author.php';
        }

        return $template_path;
    }

    public function fse_url_update( &$wp_admin_bar ) {
        $site_edit_node = $wp_admin_bar->get_node( 'site-editor' );

        if ( empty( $site_edit_node ) ) {
            return;
        }

        if ( is_post_type_archive( 'docs' ) || is_tax( 'knowledge_base' ) || is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) || is_singular( 'docs' ) ) {
            $href = isset( $site_edit_node->href ) ? $site_edit_node->href : '';
            if ( ! empty( $href ) ) {
                $href                 = $href . '&lang=' . ICL_LANGUAGE_CODE;
                $site_edit_node->href = $href;
                $wp_admin_bar->add_node( $site_edit_node );
            }
        }
    }

	public function exclude_betterdocs_search( $options ) {
		$options['search_input'] = $options['search_input'] . ':not(.betterdocs-search-field)';
		return $options;
	}
	public function before_render( $widget, $widget_type ) {
		$this->widget_attributes = isset( $widget->attributes ) ? $widget->attributes : [];
		$this->widget_type       = $widget_type;

		/**
		 * This line of code will run for reactions shortcode, elementor widget and blocks
		 */
		if ( strpos( $widget->get_name(), 'reactions' ) !== false ) {
			$this->localize_reactions_data();
		}

		/**
		 * This line of code will run for Feedback form Shortcode, Elementor widget and blocks
		 */
		if ( strpos( $widget->get_name(), 'feedback_form' ) ) {
			$this->localize_feedback_form_data();
		}

		add_filter( 'betterdocs_nested_terms_args', [ $this, 'terms_args' ], 11, 1 );
		add_filter( 'betterdocs_nested_docs_args', [ $this, 'docs_args' ], 11, 1 );
	}

	public function dequeue_saliant_theme_script() {
		if ( is_singular( 'docs' ) || is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) ) {
			wp_dequeue_script( 'salient-delay-js' );
		}
	}

	public function after_render( $widget, $widget_type ) {
		remove_filter( 'betterdocs_nested_terms_args', [ $this, 'terms_args' ], 11 );
		remove_filter( 'betterdocs_nested_docs_args', [ $this, 'docs_args' ], 11 );

		$this->widget_attributes = [];
		$this->widget_type       = '';
	}

	public function terms_args( $args ) {
		switch ( $this->widget_type ) {
			case 'shortcode':
				if ( ! empty( $this->widget_attributes['terms_orderby'] ) ) {
					$args['orderby'] = $this->widget_attributes['terms_orderby'];
				}

				if ( ! empty( $this->widget_attributes['terms_order'] ) ) {
					$args['order'] = $this->widget_attributes['terms_order'];
				}
				break;
			case 'blocks':
				if ( isset( $this->widget_attributes['orderBy'] ) ) {
					if ( $this->widget_attributes['orderBy'] == 'doc_category_order' ) {
						// Use betterdocs_order which handles fallback logic automatically
						$args['orderby'] = 'betterdocs_order';
					} else {
						$args['orderby'] = $this->widget_attributes['orderBy'];
					}
				}
				if ( isset( $this->widget_attributes['order'] ) ) {
					$args['order'] = $this->widget_attributes['order'];
				}
				break;
			case 'elementor':
				$args['orderby'] = $this->widget_attributes['orderby'];
				$args['order']   = $this->widget_attributes['order'];
				break;
		}

		return $args;
	}

	public function docs_args( $args ) {
		switch ( $this->widget_type ) {
			case 'shortcode':
				if ( isset( $this->widget_attributes['orderby'] ) ) {
					$args['orderby'] = $this->widget_attributes['orderby'];
				}

				if ( isset( $this->widget_attributes['order'] ) ) {
					$args['order'] = $this->widget_attributes['order'];
				}
				break;
			case 'blocks':
				if ( isset( $this->widget_attributes['postsOrderBy'] ) ) {
					$args['orderby'] = $this->widget_attributes['postsOrderBy'];
				}
				if ( isset( $this->widget_attributes['postsOrder'] ) ) {
					$args['order'] = $this->widget_attributes['postsOrder'];
				}

				//This is for archive category block only
				if ( isset( $this->widget_attributes['orderby'] ) ) {
					$args['orderby'] = $this->widget_attributes['orderby'];
				}
				if ( isset( $this->widget_attributes['order'] ) ) {
					$args['order'] = $this->widget_attributes['order'];
				}
				break;
			case 'elementor':
				$args['orderby'] = $this->widget_attributes['post_orderby'];
				$args['order']   = $this->widget_attributes['post_order'];
				break;
		}

		return $args;
	}

	public function article_reactions() {
		$args          = [];
		$single_layout = $this->database->get_theme_mod( 'betterdocs_single_layout_select', true );
		if ( in_array( $single_layout, [ 'layout-8', 'layout-10' ], true ) ) {
			return;
		}
		$reactions     = $this->database->get_theme_mod( 'betterdocs_post_reactions', true );

		// Get the title tag from customizer
		$text_tag = $this->database->get_theme_mod( 'betterdocs_reactions_title_tag', 'h5' );

		// Collect reaction values and icons
		$reactions_data = [
			'happy'       => 'betterdocs_post_reactions_happy',
			'happy_icon'  => 'betterdocs_post_reactions_happy_icon',
			'normal'      => 'betterdocs_post_reactions_normal',
			'normal_icon' => 'betterdocs_post_reactions_normal_icon',
			'sad'         => 'betterdocs_post_reactions_sad',
			'sad_icon'    => 'betterdocs_post_reactions_sad_icon'
		];

		foreach ( $reactions_data as $key => $theme_mod ) {
			$value = betterdocs()->customizer->defaults->get( $theme_mod );
			if ( $value ) {
				$args[ $key ] = $value;
			} else {
				$args[ $key ] = false;
			}
		}

		// Build the attribute string for the shortcode
		$attr = '';
		foreach ( $args as $key => $value ) {
			$attr .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		// Render the shortcode based on layout and reactions availability
		if ( ( $single_layout == 'layout-8' || $single_layout == 'layout-10' ) && $reactions ) {
			echo do_shortcode( '[betterdocs_article_reactions text_tag="' . esc_attr( $text_tag ) . '" layout="layout-2"' . $attr . ']' );
		} elseif ( $single_layout == 'layout-9' && $reactions ) {
			echo '';
		} elseif ( $reactions ) {
			echo do_shortcode( '[betterdocs_article_reactions text_tag="' . esc_attr( $text_tag ) . '"' . $attr . ']' );
		}
	}


	public function enqueue_scripts() {
		if ( betterdocs()->helper->is_custom_docs_page() ) {
			wp_enqueue_style( 'betterdocs-category-grid' );
			return;
		}

		if ( is_singular( 'docs' ) ) {
			wp_enqueue_style( 'betterdocs-single' );
			wp_enqueue_style( 'nx-documentation-sidebar', BETTERDOCS_ABSURL . 'assets/build/public/css/documentation-sidebar.css', [], BETTERDOCS_VERSION );
			wp_enqueue_script( 'nx-documentation-sidebar', BETTERDOCS_ABSURL . 'assets/build/public/js/documentation-sidebar.js', [], BETTERDOCS_VERSION, true );
			$this->add_font_family_style( 'betterdocs-single' );
			if ( $this->settings->get( 'live_search' ) ) {
				wp_enqueue_style( 'betterdocs-search-modal' );
				wp_enqueue_script( 'betterdocs-search-modal' );
				$this->add_search_ui_assets();
			}
			$single_doc_css = '';

			if ( 'wide' === $this->settings->get( 'single_doc_layout_width', 'boxed' ) ) {
				$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-content-wrapper { width: 100% !important; max-width: none !important; }';
			}

			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta { align-items: center !important; display: flex !important; flex-wrap: wrap !important; gap: 8px !important; margin-bottom: 24px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta .reading-time, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta .betterdocs-view-count { background: #f2f4f7 !important; border: 1px solid #d0d5dd !important; border-radius: 16px !important; display: inline-block !important; margin: 0 !important; padding: 5px 10px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta .reading-time p, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta .betterdocs-view-count p { align-items: center !important; color: #475467 !important; display: flex !important; font-size: 14px !important; font-weight: 400 !important; gap: 5px !important; line-height: 16px !important; margin: 0 !important; padding: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta .reading-time p svg, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-document-meta .betterdocs-view-count p svg { color: #475467 !important; height: 14px !important; margin: 0 !important; width: 14px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation { align-items: stretch !important; background: transparent !important; border: 0 !important; display: grid !important; gap: 16px !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; margin: 40px 0 !important; padding: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a { align-items: center !important; background: #f9fafb !important; border: 1px solid #eaecf0 !important; border-radius: 4px !important; box-sizing: border-box !important; color: #667085 !important; display: grid !important; gap: 16px !important; min-height: 80px !important; padding: 16px 20px !important; text-align: left !important; text-decoration: none !important; transition: background-color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .2s ease !important; width: auto !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a[rel="prev"] { grid-template-areas: "icon content" !important; grid-template-columns: auto minmax(0, 1fr) !important; margin: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a[rel="next"] { grid-template-areas: "content icon" !important; grid-template-columns: minmax(0, 1fr) auto !important; margin: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:only-child { grid-column: 1 / -1 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation .betterdocs-navigation-content { display: flex !important; flex-direction: column !important; grid-area: content !important; min-width: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation .betterdocs-navigation-icon { align-items: center !important; color: #98a2b3 !important; display: inline-flex !important; grid-area: icon !important; justify-content: center !important; line-height: 1 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation .betterdocs-navigation-icon svg { fill: currentColor !important; height: 18px !important; margin: 0 !important; min-width: 18px !important; width: 18px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation .betterdocs-navigation-label { color: #667085 !important; font-size: 12px !important; font-weight: 400 !important; line-height: 1.4 !important; margin: 0 0 6px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation .betterdocs-navigation-title { color: #1d2939 !important; font-size: 16px !important; font-weight: 600 !important; line-height: 1.4 !important; overflow: hidden !important; text-overflow: ellipsis !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:hover { background: #ffffff !important; border-color: #00b8d9 !important; box-shadow: 0 4px 12px rgba(0, 184, 217, .12) !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:hover .betterdocs-navigation-icon, body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:hover .betterdocs-navigation-title { color: #00b8d9 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:focus-visible { border-color: #00b8d9 !important; box-shadow: 0 0 0 3px rgba(0, 184, 217, .2) !important; outline: none !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:focus-visible .betterdocs-navigation-icon, body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:focus-visible .betterdocs-navigation-title { color: #00b8d9 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation > a:active { background: #e8fbff !important; box-shadow: none !important; transform: translateY(1px) !important; }';
			$single_doc_css .= '@media only screen and (max-width: 768px) { body .betterdocs-wrapper.betterdocs-single-wrapper .docs-navigation { gap: 12px !important; grid-template-columns: 1fr !important; margin: 30px 0 !important; } }';

			if ( $this->settings->get( 'enable_toc' ) ) {
				$sticky_toc_offset = absint( $this->settings->get( 'sticky_toc_offset', 100 ) );
				$sticky_toc_position = $this->settings->get( 'enable_sticky_toc' ) ? 'sticky' : 'static';
				$sticky_toc_top = $this->settings->get( 'enable_sticky_toc' ) ? $sticky_toc_offset . 'px' : 'auto';

				$single_doc_css .= '@media only screen and (min-width: 769px) { ';
				$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-layout-8:not(.betterdocs-single-layout-9) .betterdocs-content-wrapper .betterdocs-full-sidebar-right { align-self: flex-start !important; position: ' . $sticky_toc_position . ' !important; top: ' . $sticky_toc_top . ' !important; }';
				$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-layout-8:not(.betterdocs-single-layout-9) .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container { max-height: none !important; overflow: visible !important; position: static !important; top: auto !important; }';
				$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-layout-8:not(.betterdocs-single-layout-9) .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc { max-height: none !important; overflow: visible !important; position: static !important; top: auto !important; }';
				$single_doc_css .= '}';
			}

			if ( $single_doc_css ) {
				wp_add_inline_style( 'betterdocs-single', $single_doc_css );
			}
			wp_enqueue_style( 'betterdocs-article-summary' );
			wp_enqueue_style( 'betterdocs-encyclopedia' );
			wp_enqueue_style( 'betterdocs-glossaries' );
			wp_enqueue_script( 'clipboard' );
			wp_enqueue_script( 'betterdocs-glossaries' );
		}

		if ( is_post_type_archive( 'docs' ) ) {
			wp_enqueue_style( 'betterdocs-category-grid' ); //category grid shortcode is supposed to enqueue this style, but this is called again to fix flicking of UI on Docs Page
			$this->add_font_family_style( 'betterdocs-category-grid' );
			wp_enqueue_style( 'betterdocs-docs' );
		}

		if ( is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) ) {
			wp_enqueue_style( 'betterdocs-doc_category' );
			$this->add_font_family_style( 'betterdocs-doc_category' );
		}

		if ( is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) || is_singular( 'docs' ) ) {
			wp_enqueue_style( 'simplebar' );
			wp_enqueue_script( 'simplebar' );

			if ( is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) ) {
				wp_enqueue_script( 'betterdocs-category-grid' );
			}
		}

		if ( is_post_type_archive( 'docs' ) || is_singular( 'docs' ) || is_tax( 'doc_category' ) || is_tax( 'doc_tag' ) || is_tax( 'knowledge_base' ) ) {
			wp_enqueue_script( 'betterdocs' );
		}

		if ( is_tax( 'glossaries' ) ) {
			wp_enqueue_style( 'betterdocs-encyclopedia' );
			wp_enqueue_style( 'betterdocs-single' );
			$this->add_font_family_style( 'betterdocs-single' );
			wp_enqueue_style( 'betterdocs-glossaries' );
		}
	}

	private function get_font_family_css() {
		$font_families = array(
			'inherit' => 'inherit',
			'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'arial' => 'Arial, Helvetica, sans-serif',
			'georgia' => 'Georgia, "Times New Roman", serif',
			'inter' => '"Inter", sans-serif',
			'roboto' => 'Roboto, Arial, sans-serif',
			'ibm-plex-sans' => '"IBM Plex Sans", sans-serif'
		);
		$font_key = $this->settings->get( 'betterdocs_font_family', 'inherit' );
		$font_family = isset( $font_families[ $font_key ] ) ? $font_families[ $font_key ] : $font_families['inherit'];

		$css = 'body .betterdocs-wrapper, body .betterdocs-wrapper * { font-family: ' . $font_family . ' !important; }';
		$css .= $this->get_typography_rule(
			array(
				'size' => 'betterdocs_font_size',
				'weight' => 'betterdocs_font_weight',
				'color' => 'betterdocs_font_color'
			),
			array(
				'size' => 16,
				'weight' => '400',
				'color' => '#303030'
			),
			array(
				'body .betterdocs-wrapper .betterdocs-content',
				'body .betterdocs-wrapper .betterdocs-content p',
				'body .betterdocs-wrapper .betterdocs-content li',
				'body .betterdocs-wrapper .betterdocs-content blockquote',
				'body .betterdocs-wrapper .betterdocs-content td',
				'body .betterdocs-wrapper .betterdocs-content th',
				'body .betterdocs-wrapper .betterdocs-content figcaption'
			)
		);

		$heading_defaults = array(
			'h1' => array( 'size' => 42, 'weight' => '700', 'color' => '#1d2939' ),
			'h2' => array( 'size' => 32, 'weight' => '700', 'color' => '#1d2939' ),
			'h3' => array( 'size' => 28, 'weight' => '600', 'color' => '#1d2939' ),
			'h4' => array( 'size' => 24, 'weight' => '600', 'color' => '#1d2939' ),
			'h5' => array( 'size' => 20, 'weight' => '600', 'color' => '#1d2939' ),
			'h6' => array( 'size' => 18, 'weight' => '600', 'color' => '#1d2939' )
		);

		foreach ( $heading_defaults as $heading => $defaults ) {
			$heading_selector = 'body .betterdocs-wrapper .betterdocs-content ' . $heading;
			$selectors        = array( $heading_selector, $heading_selector . ' a' );

			if ( 'h1' === $heading ) {
				$selectors[] = 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-entry-title';
				$selectors[] = 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-entry-title a';
				$selectors[] = 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-entry-title h1';
			}

			$css .= $this->get_typography_rule(
				array(
					'size' => 'betterdocs_' . $heading . '_font_size',
					'weight' => 'betterdocs_' . $heading . '_font_weight',
					'color' => 'betterdocs_' . $heading . '_font_color'
				),
				$defaults,
				$selectors
			);
		}

		return $css . 'body .betterdocs-wrapper pre, body .betterdocs-wrapper pre *, body .betterdocs-wrapper code, body .betterdocs-wrapper code *, body .betterdocs-wrapper .enlighter-code, body .betterdocs-wrapper .enlighter-code *, body .betterdocs-wrapper .enlighter, body .betterdocs-wrapper .enlighter * { font-family: "Source Code Pro", "Liberation Mono", monospace !important; }';
	}

	private function add_search_ui_assets() {
		if ( $this->search_ui_assets_added ) {
			return;
		}

		$this->search_ui_assets_added = true;
		wp_add_inline_style( 'betterdocs-search-modal', $this->get_search_ui_css() );
		wp_add_inline_script( 'betterdocs-search-modal', $this->get_search_ui_script(), 'after' );
	}

	private function get_search_ui_css() {
		$field_border_color = $this->get_search_color( 'betterdocs_search_field_border_color', '#d0d5dd' );
		$field_border_width = $this->get_search_border_width( 'betterdocs_search_field_border_width', 1 );
		$field_border_style = $this->get_search_border_style( 'betterdocs_search_field_border_style', 'solid' );
		$field_border_radius = $this->get_search_border_radius( 'betterdocs_search_field_border_radius', 4 );
		$modal_border_color = $this->get_search_color( 'betterdocs_search_modal_border_color', '#d0d5dd' );
		$modal_border_width = $this->get_search_border_width( 'betterdocs_search_modal_border_width', 1 );
		$modal_border_style = $this->get_search_border_style( 'betterdocs_search_modal_border_style', 'solid' );
		$modal_border_radius = $this->get_search_border_radius( 'betterdocs_search_modal_border_radius', 4 );
		$docs_tab_color     = $this->get_search_color( 'betterdocs_search_docs_tab_color', '#00b884' );
		$faq_tab_color      = $this->get_search_color( 'betterdocs_search_faq_tab_color', '#00b884' );
		$modal_hover_background = $this->get_search_color( 'betterdocs_search_modal_hover_background', '#f6fef9' );

		$css  = '.betterdocs-search-popup .betterdocs-searchform, .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header { background: #ffffff !important; border: ' . $field_border_width . 'px ' . $field_border_style . ' ' . $field_border_color . ' !important; border-radius: ' . $field_border_radius . 'px !important; box-shadow: 0 1px 2px rgba(16, 24, 40, .06) !important; }';
		$css .= '.betterdocs-search-popup .betterdocs-searchform .betterdocs-search-command { color: #344054 !important; }';
		$css .= '.betterdocs-search-popup .betterdocs-searchform .betterdocs-searchform-input-wrap ::placeholder { color: #667085 !important; opacity: 1 !important; }';
		$css .= '.betterdocs-search-popup .betterdocs-searchform .command-key { background: #f2f4f7 !important; border: 1px solid #d0d5dd !important; color: #344054 !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-search-field { color: #344054 !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-search-field::placeholder { color: #667085 !important; opacity: 1 !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .esc-button { background: #f2f4f7 !important; border: 1px solid #d0d5dd !important; color: #344054 !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details { animation: betterdocs-search-dialog-in .2s ease-out both; border: ' . $modal_border_width . 'px ' . $modal_border_style . ' ' . $modal_border_color . ' !important; border-radius: ' . $modal_border_radius . 'px !important; box-shadow: 0 24px 48px rgba(16, 24, 40, .2), 0 8px 16px rgba(16, 24, 40, .12) !important; }';
		$css .= '@keyframes betterdocs-search-dialog-in { from { opacity: 0; transform: translate(-50%, -12px); } to { opacity: 1; transform: translate(-50%, 0); } }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="docs"].active { border-bottom-color: ' . $docs_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="docs"].active > span { color: ' . $docs_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="docs"]:hover > span { color: ' . $docs_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="docs"].active > .tab-icon svg path { fill: ' . $docs_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="faq"].active { border-bottom-color: ' . $faq_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="faq"].active > span { color: ' . $faq_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="faq"]:hover > span { color: ' . $faq_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="faq"].active > .tab-icon svg path { fill: ' . $faq_tab_color . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-item-list:hover, .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-item-list:focus-within { background: ' . $modal_hover_background . ' !important; }';
		$css .= '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-configured-icon { display: block !important; flex: 0 0 16px !important; height: 16px !important; max-height: 16px !important; max-width: 16px !important; object-fit: contain !important; image-rendering: auto !important; width: 16px !important; }';

		$css .= $this->get_search_tab_icon_css( 'docs', $this->get_search_icon_url( 'betterdocs_search_docs_tab_icon' ) );
		$css .= $this->get_search_tab_icon_css( 'faq', $this->get_search_icon_url( 'betterdocs_search_faq_tab_icon' ) );

		return $css;
	}

	private function get_search_tab_icon_css( $type, $icon_url ) {
		if ( empty( $icon_url ) ) {
			return '';
		}

		$icon_url = str_replace( array( '\\', '"', "'", '(', ')' ), '', $icon_url );

		return '.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="' . $type . '"] .tab-icon { background-image: url("' . $icon_url . '") !important; background-position: center !important; background-repeat: no-repeat !important; background-size: contain !important; display: inline-block !important; font-size: 0 !important; height: 20px !important; width: 20px !important; }' .
			'.betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-tab-items[data-betterdocs-search-tab="' . $type . '"] .tab-icon svg { display: none !important; }';
	}

	private function get_search_color( $key, $default ) {
		$value = sanitize_hex_color( $this->settings->get( $key, $default ) );

		return $value ? $value : $default;
	}

	private function get_search_border_width( $key, $default ) {
		return min( 20, absint( $this->settings->get( $key, $default ) ) );
	}

	private function get_search_border_radius( $key, $default ) {
		return min( 40, absint( $this->settings->get( $key, $default ) ) );
	}

	private function get_search_border_style( $key, $default ) {
		$value = $this->settings->get( $key, $default );

		return in_array( $value, array( 'solid', 'dashed', 'dotted' ), true ) ? $value : $default;
	}

	private function get_search_icon_url( $key ) {
		$icon = $this->settings->get( $key, array() );

		if ( is_array( $icon ) ) {
			if ( ! empty( $icon['url'] ) ) {
				return esc_url_raw( $icon['url'] );
			}
			if ( isset( $icon['value'] ) && is_array( $icon['value'] ) && ! empty( $icon['value']['url'] ) ) {
				return esc_url_raw( $icon['value']['url'] );
			}
		}

		return is_string( $icon ) ? esc_url_raw( $icon ) : '';
	}

	private function get_search_result_icon_config() {
		$config = array(
			'document'   => $this->get_search_icon_url( 'category_grid_document_icon' ),
			'categories' => array()
		);
		$default_icon = $this->get_search_icon_url( 'category_grid_default_icon' );
		$terms        = get_terms(
			array(
				'taxonomy'   => 'doc_category',
				'hide_empty' => false
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $config;
		}

		foreach ( $terms as $term ) {
			$icon_url     = '';
			$term_icon_id = get_term_meta( $term->term_id, 'doc_category_image-id', true );

			if ( $term_icon_id ) {
				$icon_url = wp_get_attachment_image_url( $term_icon_id, 'thumbnail' );
			}

			if ( empty( $icon_url ) ) {
				$icon_url = $default_icon;
			}

			if ( ! empty( $icon_url ) ) {
				$config['categories'][ strtolower( wp_strip_all_tags( $term->name ) ) ] = esc_url_raw( $icon_url );
			}
		}

		return $config;
	}

	private function get_search_ui_script() {
		$icon_config = wp_json_encode(
			$this->get_search_result_icon_config(),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		$icon_config = $icon_config ? $icon_config : '{}';
		$script      = <<<'JS'
(function () {
    if (window.betterdocsSearchUiReady) {
        return;
    }

    window.betterdocsSearchUiReady = true;

    var searchIconConfig = __SEARCH_ICON_CONFIG__;

    document.addEventListener('keydown', function (event) {
        if ((event.metaKey || event.ctrlKey) && event.key && event.key.toLowerCase() === 'k') {
            event.preventDefault();
        }
    }, true);

    function markSearchTabs() {
        document.querySelectorAll('.betterdocs-search-tabs .betterdocs-tab-items').forEach(function (tab) {
            var label = (tab.textContent || '').trim().toLowerCase();
            tab.setAttribute('data-betterdocs-search-tab', label === 'faq' ? 'faq' : 'docs');
        });
    }

    function replaceSearchIcon(svg, url) {
        if (!svg || !url || !svg.parentNode) {
            return;
        }

        var image = document.createElement('img');
        image.className = 'betterdocs-search-configured-icon';
        image.src = url;
        image.alt = '';
        image.setAttribute('aria-hidden', 'true');
        svg.parentNode.replaceChild(image, svg);
    }

    function getCategoryIconUrl(text) {
        var categories = searchIconConfig.categories || {};
        var labels = (text || '').split(',');

        for (var index = 0; index < labels.length; index++) {
            var label = labels[index].trim().toLowerCase();
            if (categories[label]) {
                return categories[label];
            }
        }

        return '';
    }

    function applySearchResultIcons() {
        document.querySelectorAll('.betterdocs-search-wrapper .betterdocs-search-item-content').forEach(function (item) {
            replaceSearchIcon(item.querySelector('.content-main svg'), searchIconConfig.document || '');

            var categoryText = item.querySelector('.content-sub h5');
            replaceSearchIcon(item.querySelector('.content-sub svg'), getCategoryIconUrl(categoryText ? categoryText.textContent : ''));
        });
    }

    markSearchTabs();
    applySearchResultIcons();

    if (window.MutationObserver) {
        new MutationObserver(function () {
            markSearchTabs();
            applySearchResultIcons();
        }).observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
}());
JS;

		return str_replace( '__SEARCH_ICON_CONFIG__', $icon_config, $script );
	}

	private function get_typography_rule( $settings_keys, $defaults, $selectors ) {
		$font_size = absint( $this->settings->get( $settings_keys['size'], $defaults['size'] ) );
		$font_size = $font_size > 0 ? $font_size : $defaults['size'];
		$font_weights = array( '300', '400', '500', '600', '700', '800' );
		$font_weight = (string) $this->settings->get( $settings_keys['weight'], $defaults['weight'] );
		$font_weight = in_array( $font_weight, $font_weights, true ) ? $font_weight : $defaults['weight'];
		$font_color = sanitize_hex_color( $this->settings->get( $settings_keys['color'], $defaults['color'] ) );
		$font_color = $font_color ? $font_color : $defaults['color'];

		return implode( ', ', $selectors ) . ' { color: ' . $font_color . ' !important; font-size: ' . $font_size . 'px !important; font-weight: ' . $font_weight . ' !important; }';
	}

	private function add_font_family_style( $handle ) {
		wp_add_inline_style( $handle, $this->get_font_family_css() );
	}

	public function add_shortcode_font_style( $shortcode ) {
		if ( ! is_object( $shortcode ) || ! method_exists( $shortcode, 'get_style_depends' ) ) {
			return;
		}

		foreach ( (array) $shortcode->get_style_depends() as $handle ) {
			if ( betterdocs()->helper->is_custom_docs_page() && 'betterdocs-category-grid' === $handle ) {
				continue;
			}
			$this->add_font_family_style( $handle );
			if ( 'betterdocs-search-modal' === $handle ) {
				$this->add_search_ui_assets();
			}
		}
	}

	public function layout_filename( $filename, $origin_layout ) {
		$filename = ( $origin_layout === 'layout-2' ) ? 'default' : $filename;
		return $filename;
	}

	public function init() {
		$this->container->get( TemplateLoader::class )->init();

		add_filter( 'betterdocs_articles_args', [ $this, 'article_args' ], 11, 3 );
	}

	public function article_args( $args, $term_id, $_origin_args ) {
		if ( null == $term_id || isset( $args['orderby'] ) ) {
			return $args;
		}

		$post__in = betterdocs()->query->get_docs_order_by_terms( $term_id );

		if ( ! empty( $post__in ) ) {
			$args['orderby']  = 'post__in';
			$args['post__in'] = $post__in;
		}

		return $args;
	}

	public function localize_reactions_data() {
		$this->assets->localize(
			'betterdocs-reactions',
			'betterdocsReactionsConfig',
			[
				'post_id'  => get_the_ID(),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'FEEDBACK' => [
					'DISPLAY' => true,
					'TEXT'    => esc_html__( 'How did you feel?', 'betterdocs' ),
					'SUCCESS' => betterdocs()->settings->get( 'reaction_feedback_text', __( 'Thanks for your feedback', 'betterdocs' ) ),
					'URL'     => get_rest_url( null, '/betterdocs/v1/feedback' )
				]
			]
		);
	}

	public function localize_feedback_form_data() {
		$this->assets->localize(
			'betterdocs',
			'betterdocsSubmitFormConfig',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'post_id'  => get_the_ID(),
				'nonce'    => wp_create_nonce( 'betterdocs_submit_data' )
			]
		);
	}
}
