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
		add_filter( 'body_class', [ $this, 'body_classes' ] );

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
				if ( isset( $this->widget_attributes['terms_orderby'] ) ) {
					$args['orderby'] = $this->widget_attributes['terms_orderby'];
				}

				if ( isset( $this->widget_attributes['terms_order'] ) ) {
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
		if ( $this->is_custom_docs_page() ) {
			wp_enqueue_style( 'betterdocs-category-grid' );
			$this->add_font_family_style( 'betterdocs-category-grid' );
			wp_add_inline_style(
				'betterdocs-category-grid',
				'body.betterdocs-custom-docs-page .row.heading-title.hentry, body.betterdocs-custom-docs-page .row:has(> .blog_next_prev_buttons) { display: none !important; }'
			);
		}

		if ( is_singular( 'docs' ) ) {
			wp_enqueue_style( 'betterdocs-single' );
			$this->add_font_family_style( 'betterdocs-single' );
			$single_doc_css = '';

			if ( 'wide' === $this->settings->get( 'single_doc_layout_width', 'boxed' ) ) {
				$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-content-wrapper { width: 100% !important; max-width: none !important; }';
			}

			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-category-header .betterdocs-category-header-inner { gap: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts { margin-left: auto !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-icon { align-items: center !important; display: inline-flex !important; flex: 0 0 47px !important; height: 47px !important; justify-content: center !important; margin: 0 !important; width: 47px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-icon img, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-icon svg { margin: 0 !important; max-height: 100% !important; max-width: 100% !important; object-fit: contain !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon { align-items: center !important; display: inline-flex !important; justify-content: center !important; margin: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title { gap: 4px !important; margin-left: 0 !important; margin-right: 0 !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title img, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title svg { margin: 0 !important; object-fit: contain !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-category-folder-icon { align-items: center !important; display: inline-flex !important; flex: 0 0 20px !important; height: 20px !important; justify-content: center !important; margin: 0 !important; object-fit: contain !important; width: 20px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-category-folder-icon img, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-category-folder-icon svg { height: 100% !important; margin: 0 !important; max-height: 100% !important; max-width: 100% !important; object-fit: contain !important; width: 100% !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-category-toggle-icon { flex: 0 0 15px !important; height: 15px !important; margin: 0 !important; width: 15px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-category-toggle-icon.arrow-down { display: none !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title.is-expanded .betterdocs-category-toggle-icon.arrow-right { display: none !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title.is-expanded .betterdocs-category-toggle-icon.arrow-down { display: inline-flex !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-nested-category-list.active:before, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-single-category-wrapper.active.default.show:before, body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-articles-list li a.active:before { display: none !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title .betterdocs-folder-icon.arrow-right { background: transparent !important; border-radius: 0 !important; height: 15px !important; width: 15px !important; }';
			$single_doc_css .= 'body .betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-body .betterdocs-nested-category-title .betterdocs-folder-icon.arrow-right svg { width: 15px !important; }';

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
			wp_enqueue_script( 'betterdocs-category-grid' );

			if ( is_singular( 'docs' ) ) {
				wp_add_inline_script(
					'betterdocs-category-grid',
					"(function($) {\n" .
					"    function syncNestedCategoryToggle($title) {\n" .
					"        var $list = $title.next('.betterdocs-nested-category-list');\n" .
					"        var expanded = $list.hasClass('active') || $list.is(':visible');\n" .
					"        $title.toggleClass('is-expanded', expanded);\n" .
					"    }\n" .
					"\n" .
					"    $(function() {\n" .
					"        $('.betterdocs-sidebar-layout-7 .betterdocs-nested-category-title').each(function() {\n" .
					"            syncNestedCategoryToggle($(this));\n" .
					"        });\n" .
					"    });\n" .
					"\n" .
					"    $(document).on('click.betterdocsSidebarToggle', '.betterdocs-sidebar-layout-7 .betterdocs-nested-category-title', function() {\n" .
					"        var $title = $(this);\n" .
					"        window.setTimeout(function() { syncNestedCategoryToggle($title); }, 0);\n" .
					"        window.setTimeout(function() { syncNestedCategoryToggle($title); }, 400);\n" .
					"    });\n" .
					"})(jQuery);",
					'after'
				);
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

		return $css . 'body .betterdocs-wrapper pre, body .betterdocs-wrapper pre *, body .betterdocs-wrapper code, body .betterdocs-wrapper code * { font-family: monospace !important; }';
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
			$this->add_font_family_style( $handle );
		}
	}

	public function layout_filename( $filename, $origin_layout ) {
		$filename = ( $origin_layout === 'layout-2' ) ? 'default' : $filename;
		return $filename;
	}

	/**
	 * Identify the custom page configured as the BetterDocs documentation root.
	 *
	 * @return bool
	 */
	private function is_custom_docs_page() {
		if ( $this->settings->get( 'builtin_doc_page', true ) ) {
			return false;
		}

		$docs_page_id = absint( $this->settings->get( 'docs_page', 0 ) );

		return $docs_page_id > 0 && absint( get_queried_object_id() ) === $docs_page_id;
	}

	/**
	 * Mark the custom Docs Page so theme-level page decorations can be scoped to it.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_classes( $classes ) {
		if ( $this->is_custom_docs_page() ) {
			$classes[] = 'betterdocs-custom-docs-page';
		}

		return $classes;
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
