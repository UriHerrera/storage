<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Error;
use WP_User;
use WPDeveloper\BetterDocs\Admin\Builder\GlobalFields;
use WPDeveloper\BetterDocs\Admin\Builder\Rules;
use WPDeveloper\BetterDocs\AI\ModelRegistry;
use WPDeveloper\BetterDocs\AI\ProviderFactory;
use WPDeveloper\BetterDocs\REST\AIEdit;
use WPDeveloper\BetterDocs\Utils\AIHelper;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Utils\Helper;

class Settings extends Base {
    public $base_key = 'betterdocs_settings';

    /**
     * Database class
     * @var Database
     */
    protected $database;

    private $deprecated = array();

    private $cannot_be_empty = array(
        'breadcrumb_doc_title',
        'docs_slug',
        'category_slug',
        'tag_slug',
        'permalink_structure',
        'docs_page'
    );

    public function __construct( Database $database ) {
        $this->database   = $database;
        $this->deprecated = $this->deprecated_settings();

        add_filter( 'betterdocs_settings_tabs', array( $this, 'import_export_settings' ) );
        add_filter( 'betterdocs_settings_tabs', array( $this, 'maybe_remove_git_tab' ), 20 );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_old' ), 99 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 99 );

        // if ( isset( $_GET['page'] ) && $_GET['page'] === 'betterdocs-settings' && ! has_action( 'betterdocs_settings_header' ) ) {
        //     add_action( 'betterdocs_settings_header', [ $this, 'header' ] );
        // }

        add_action( 'wp_ajax_betterdocs_dark_mode', array( $this, 'dark_mode' ) );
        add_filter( 'betterdocs_settings_tab_advance', array( $this, 'hide_roles_management' ), 11, 1 );
        add_action( 'betterdocs::settings::saved', array( $this, 'fallback_slugs' ), 99, 3 );
    }

    public function fallback_slugs( $_saved, $_settings, $_old_settings = array() ) {
        $_default = $this->get_default();
        foreach ( $this->cannot_be_empty as $key ) {
            if ( 'docs_page' === $key && ! $_settings[ 'builtin_doc_page' ] && empty( $_settings[ $key ] ) ) {
                $this->save( 'builtin_doc_page', true );
                continue;
            }

            if ( empty( $_settings[ $key ] ) ) {
                $this->save( $key, $_default[ $key ] );
            }
        }
    }

    /**
     * Get the settings URL for admin
     * @return string
     */
    public function url() {
        return esc_url( admin_url( 'admin.php?page=betterdocs-settings' ) );
    }

    /**
     * Settings keys holding secret API keys. These are masked before reaching
     * the browser, stripped for non-admins, and never persisted as their mask.
     *
     * Covers the AI Chatbot key plus every content-suite platform key. OpenAI's
     * is `ai_autowrite_api_key` (see ProviderFactory::key_field_for), hence the
     * dedupe.
     *
     * @return array<int,string>
     */
    public static function sensitive_api_key_fields() {
        $fields = array(
            ProviderFactory::OPENAI_KEY_FIELD,
            'ai_chatbot_api_key',
        );
        foreach ( array_keys( ModelRegistry::platforms() ) as $platform ) {
            $fields[] = ProviderFactory::key_field_for( $platform );
        }
        // Add-ons (e.g. the AI Chatbot) register their own per-platform keys here
        // so they are masked in the browser and stripped for non-admins.
        return apply_filters( 'betterdocs_sensitive_api_key_fields', array_values( array_unique( $fields ) ) );
    }

    /**
     * This method is responsible for enqueueing scripts in settings panel
     *
     * @param string $hook
     *
     * @return void
     * @since 2.5.0
     */
    public function enqueue( $hook ) {
        if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script( 'betterdocs-admin' );

        $settings = GlobalFields::normalize( $this->settings_args() );

        // Mask sensitive API keys before they reach the browser. Non-admins still get them stripped entirely below.
        $sensitive_api_keys = self::sensitive_api_key_fields();
        foreach ( $sensitive_api_keys as $api_key_field ) {
            if ( ! empty( $settings[ 'values' ][ $api_key_field ] ) ) {
                $settings[ 'values' ][ $api_key_field ] = Helper::mask_api_key( $settings[ 'values' ][ $api_key_field ] );
            }
        }

        if ( ! current_user_can( 'edit_docs_settings' ) ) {
            foreach ( $sensitive_api_keys as $api_key_field ) {
                if ( isset( $settings[ 'values' ][ $api_key_field ] ) ) {
                    unset( $settings[ 'values' ][ $api_key_field ] );
                }
            }
        }

        // Inject raw git settings to bypass get() fallback (e.g., '' -> 'docs')
        if ( isset( $settings[ 'values' ] ) ) {
            $settings[ 'values' ][ 'git_provider' ]       = $this->get_raw_field( 'git_provider', 'github' );
            $settings[ 'values' ][ 'git_repository_url' ] = $this->get_raw_field( 'git_repository_url', '' );
            $settings[ 'values' ][ 'git_branch' ]         = $this->get_raw_field( 'git_branch', '' );
            $settings[ 'values' ][ 'git_docs_directory' ] = $this->get_raw_field( 'git_docs_directory', '' );
        }

        betterdocs()->assets->localize( 'betterdocs-admin', 'betterdocsAdminSettings', $settings );
        betterdocs()->assets->enqueue( 'betterdocs-icons', 'admin/btd-icon/style.css' );
    }

    public function enqueue_old( $hook ) {
        // FREE & Pro Version Compatibility Check
        if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        $this->enqueue( 'betterdocs_page_betterdocs-settings' );
    }

    /**
     * This method is responsible for printing header in dashboard settings page.
     *
     * @param string $hook
     *
     * @return void
     * @since 2.5.0
     */
    public function header( $hook ) {
        if ( 'settings' !== $hook ) {
            return;
        }

        betterdocs()->views->get( 'admin/template-parts/settings-header' );
        betterdocs()->views->get( 'admin/template-parts/settings-header-2' );
    }

    /**
     * A list of deprecated settings keys.
     *
     * @return array
     * @since 2.5.0
     */
    public function deprecated_settings() {
        return array();
    }

    /**
     * Dynamic migration caller.
     *
     * @return void
     * @since 2.5.0
     */
    public function migration( $version ) {
        if ( $version > 250 ) {
            for ( $v = 250; $v <= $version; ++$v ) {
                $_func = "v{$v}";
                if ( method_exists( $this, $_func ) ) {
                    call_user_func( array( $this, $_func ) );
                }
            }
        }
    }

    /**
     * Migration for version 2.5.0
     *
     * @return void
     * @since 2.5.0
     */
    public function v250() {
        if ( $this->get( 'alphabetically_order_term', false ) ) {
            $this->save( 'terms_orderby', 'name' );
        }

        if ( $orderby = $this->get( 'alphabetically_order_post', false ) ) {
            if ( '1' === $orderby ) {
                $this->save( 'alphabetically_order_post', 'title' );
            }
        }
    }

    /**
     * A list of default settings data.
     *
     * @return array
     * @since 1.0.0
     *
     */
    public function get_default() {
        $_default = array(
            'multiple_kb' => '',
            'enable_ai_sample_docs' => true,
            'enable_export_faq' => true,
            'builtin_doc_page' => true,
            'breadcrumb_doc_title' => __( 'Docs', 'betterdocs' ),
            'enable_category_hierarchy_slugs' => false,
            'docs_slug' => 'docs',
            'docs_page' => 0,
            'category_slug' => 'docs-category',
            'tag_slug' => 'docs-tag',
            'permalink_structure' => 'docs/',
            'enable_faq_schema' => false,
            'live_search' => true,
            'advance_search' => false,
            'popular_keyword_limit' => 5,
            'search_letter_limit' => 3,
            'search_placeholder' => __( 'Search...', 'betterdocs' ),
            'search_not_found_text' => __( 'Sorry, no docs were found.', 'betterdocs' ),
            'search_result_image' => true,
            'search_modal_search_type' => 'all',
            'masonry_layout' => true,
            'category_grid_card_background' => '#ffffff',
            'category_grid_card_border_color' => '#e7e7e7',
            'category_grid_card_border_width' => 0,
            'category_grid_card_border_style' => 'solid',
            'category_grid_card_border_radius' => 0,
            'category_grid_card_shadow' => 'default',
            'category_grid_card_padding' => 0,
            'category_grid_gap' => 15,
            'category_grid_title_color' => '#3f5876',
            'category_grid_title_hover_color' => '#528ffe',
            'category_grid_title_font_size' => 20,
            'category_grid_article_color' => '#566e8b',
            'category_grid_article_hover_color' => '#528fff',
            'category_grid_article_font_size' => 15,
            'category_grid_header_border_color' => '#528ffe',
            'category_grid_header_border_width' => 2,
            'category_grid_count_background' => '#528ffe1a',
            'category_grid_count_inner_background' => '#528ffe33',
            'category_grid_count_color' => '#528ffe',
            'category_grid_default_icon' => array(),
            'category_grid_document_icon' => array(),
            'category_grid_nested_collapsed_icon' => array(),
            'category_grid_nested_expanded_icon' => array(),
            'category_grid_button_background' => '#ffffff',
            'category_grid_button_color' => '#528ffe',
            'category_grid_button_border_color' => '#528ffe',
            'category_grid_button_hover_background' => '#528ffe',
            'category_grid_button_hover_color' => '#ffffff',
            'category_grid_button_hover_border_color' => '#528ffe',
            'category_grid_button_border_radius' => 50,
            'category_grid_button_font_size' => 16,
            'betterdocs_search_docs_tab_color' => '#00b884',
            'betterdocs_search_faq_tab_color' => '#00b884',
            'betterdocs_search_sidebar_hover_background' => '#e8fff4',
            'betterdocs_search_modal_hover_background' => '#f6fef9',
            'betterdocs_search_field_border_color' => '#d0d5dd',
            'betterdocs_search_field_border_width' => 1,
            'betterdocs_search_field_border_style' => 'solid',
            'betterdocs_search_field_border_radius' => 4,
            'betterdocs_search_modal_border_color' => '#d0d5dd',
            'betterdocs_search_modal_border_width' => 1,
            'betterdocs_search_modal_border_style' => 'solid',
            'betterdocs_search_modal_border_radius' => 4,
            'betterdocs_search_docs_tab_icon' => array(),
            'betterdocs_search_faq_tab_icon' => array(),
            'betterdocs_font_family' => 'inherit',
            'betterdocs_font_size' => 16,
            'betterdocs_font_weight' => '400',
            'betterdocs_font_color' => '#303030',
            'betterdocs_h1_font_size' => 42,
            'betterdocs_h1_font_weight' => '700',
            'betterdocs_h1_font_color' => '#1d2939',
            'betterdocs_h2_font_size' => 32,
            'betterdocs_h2_font_weight' => '700',
            'betterdocs_h2_font_color' => '#1d2939',
            'betterdocs_h3_font_size' => 28,
            'betterdocs_h3_font_weight' => '600',
            'betterdocs_h3_font_color' => '#1d2939',
            'betterdocs_h4_font_size' => 24,
            'betterdocs_h4_font_weight' => '600',
            'betterdocs_h4_font_color' => '#1d2939',
            'betterdocs_h5_font_size' => 20,
            'betterdocs_h5_font_weight' => '600',
            'betterdocs_h5_font_color' => '#1d2939',
            'betterdocs_h6_font_size' => 18,
            'betterdocs_h6_font_weight' => '600',
            'betterdocs_h6_font_color' => '#1d2939',
            'single_doc_layout_width' => 'boxed',
            'docs_list_icon' => array(),
            'category_title_link' => false,
            'terms_orderby' => 'betterdocs_order',
            'alphabetically_order_term' => false,
            'terms_order' => 'ASC',
            'alphabetically_order_post' => 'betterdocs_order',
            'docs_order' => 'ASC',
            'nested_subcategory' => false,
            'column_number' => 3,
            'posts_number' => 10,
            'post_count' => true,
            'count_text' => __( 'Docs', 'betterdocs' ),
            'count_text_singular' => __( 'Doc', 'betterdocs' ),
            'exploremore_btn' => true,
            'exploremore_btn_txt' => __( 'Explore More', 'betterdocs' ),
            'doc_single' => 1,
            'enable_toc' => true,
            'toc_title' => __( 'Table of Contents', 'betterdocs' ),
            'toc_hierarchy' => true,
            'toc_list_number' => false,
            'toc_dynamic_title' => false,
            'enable_sticky_toc' => true,
            'sticky_toc_offset' => 100,
            'collapsible_toc_mobile' => false,
            'supported_heading_tag' => array( '1', '2', '3', '4', '5', '6' ),
            'enable_post_title' => true,
            'title_link_ctc' => true,
            'enable_breadcrumb' => true,
            'breadcrumb_home_text' => __( 'Home', 'betterdocs' ),
            'enable_breadcrumb_home_text' => true,
            'breadcrumb_home_url' => get_home_url(),
            'enable_breadcrumb_category' => true,
            'enable_breadcrumb_title' => true,
            'enable_sidebar_cat_list' => true,
            'mobile_sidebar_icon' => array(),
            'enable_print_icon' => true,
            'print_enable_logo' => false,
            'print_logo' => array(),
            'print_enable_footer' => false,
            'print_footer_text' => '',
            'enable_tags' => true,
            'email_feedback' => true,
            'feedback_link_text' => __( 'Still stuck? How can we help?', 'betterdocs' ),
            'reaction_feedback_text' => __( 'Thanks for your feedback', 'betterdocs' ),
            'feedback_url' => '',
            'feedback_form_title' => __( 'How can we help?', 'betterdocs' ),
            'email_address' => get_option( 'admin_email' ),
            'show_last_update_time' => true,
            'enable_navigation' => true,
            'enable_comment' => true,
            'enable_credit' => false,
            'enable_archive_sidebar' => true,
            'archive_nested_subcategory' => true,
            'archive_enable_pagination' => false,
            'archive_lazy_load_descendants' => false,
            'enable_content_restriction' => false,
            'enable_reporting' => false,
            'enable_sample_data' => false,
            'reporting_day' => 'monday',
            'reporting_email' => get_option( 'admin_email' ),
            'enable_write_with_ai' => true,
            'enable_faq_write_with_ai' => true,
            'enable_glossaries_write_with_ai' => true,
            'enable_docs_ai_suite' => true,
            'write_with_ai_model' => 'gpt-4o-mini',
            'ai_autowrite_api_key' => '',
            'ai_autowrite_max_token' => 2500,
            'write_with_ai_instructions' => array(
                array(
                    'id'      => 'default',
                    'title'   => __( 'Default/Core', 'betterdocs' ),
                    'content' => WriteWithAI::default_instruction_content()
                )
            ),
            'ai_edit_actions' => AIEdit::default_actions(),
            'enable_article_summary' => false,
            'article_summary_model' => 'gpt-4o-mini',
            'article_summary_max_token' => 1500,
            // Multi-platform AI (content suite). `ai_platform` selects the active
            // provider; `ai_model` is the single global model; keys are stored
            // per platform so switching never loses a saved key. OpenAI reuses
            // `ai_autowrite_api_key` above — it has always held an OpenAI key,
            // so nothing needs migrating.
            'ai_platform' => 'openai',
            'ai_model' => 'gpt-4o-mini',
            'ai_api_key_gemini' => '',
            'ai_api_key_claude' => '',
            'ai_api_key_deepseek' => '',
            'ai_api_key_openrouter' => '',
            'enable_estimated_reading_time' => true,
            'enable_encyclopedia' => false,
            'enable_glossaries' => false,
            'show_glossary_suggestions' => true,
            'enable_ai_chatbot' => false,
            'estimated_reading_time_title' => '',
            'estimated_reading_time_text' => __( 'min read', 'betterdocs' ),
            'singular_estimated_reading_time_text' => __( 'min read', 'betterdocs' ),
            'betterdocs_access_control_repeater' => array(),
            'internal_knowledge_base_type' => 'basic',
            'betterdocs_access_control_repeater_kb' => array(),
            'enable_git_integration' => false,
            /**
             * MCP master switch. Off by default; the toggle lives on the
             * BetterDocs → MCP page, not in the settings tree, and writes
             * through POST betterdocs/v1/settings.
             *
             * The key has to be listed here or `get()` cannot see it at all:
             * it answers `$default` for anything absent from the defaults
             * array, whatever the stored option holds.
             *
             * @since 4.9.0
             */
            'enable_mcp' => ''
        );

        $_default = apply_filters( 'betterdocs_default_settings', $_default );
        // $_default = apply_filters_deprecated(
        //     'betterdocs_option_default_settings', [$_default], '2.5.0',
        //     'betterdocs_default_settings', 'betterdocs_option_default_settings will be removed from v3.5.0.'
        // );

        return $_default;
    }

    /**
     * A list of default settings for pro plugins.
     *
     * @return array
     * @since 2.5.0
     */
    public function get_pro_defaults() {
        return array();
    }

    public function is_elementor_pro() {
        return is_plugin_active( 'elementor-pro/elementor-pro.php' );
    }

    /**
     * Get full site editor links for docs page.
     *
     * @return string
     * @since 3.5.8
     */
    public function gutenberg_link() {
        if ( betterdocs()->helper->current_theme_is_fse_theme() ) {
            return admin_url( 'site-editor.php?postType=wp_template&postId=betterdocs/betterdocs//archive-docs' );
        } else {
            return 'https://betterdocs.co/docs/betterdocs-provides-full-site-editor-support/';
        }
    }

    /**
     * Get elementor theme builder link for docs page.
     *
     * @return string
     * @since 3.5.8
     */
    public function elementor_link() {
        if ( $this->is_elementor_pro() ) {
            return admin_url( 'admin.php?page=elementor-app#/site-editor/templates/doc-archive' );
        } else {
            return 'https://betterdocs.co/docs/docs-page-with-elementor/';
        }
    }

    public function customizer_link() {
        $query[ 'autofocus[panel]' ] = 'betterdocs_customize_options';
        $query[ 'return' ]           = admin_url( 'edit.php?post_type=docs' );
        $builtin_doc_page            = betterdocs()->settings->get( 'builtin_doc_page' );
        $docs_slug                   = betterdocs()->settings->get( 'docs_slug' );
        $docs_page                   = betterdocs()->settings->get( 'builtin_doc_page' );

        if ( 1 == $builtin_doc_page && $docs_slug ) {
            $query[ 'url' ] = site_url( '/' . $docs_slug );
        } elseif ( 1 != $builtin_doc_page && $docs_page ) {
            $post_info      = get_post( $docs_page );
            $query[ 'url' ] = site_url( '/' . $post_info->post_name );
        }

        return add_query_arg( $query, admin_url( 'customize.php' ) );
    }

    public function category_grid_design_fields() {
        return array(
            'category_grid_card_background' => array(
                'name' => 'category_grid_card_background',
                'type' => 'colorpicker',
                'label' => __( 'Card Background', 'betterdocs' ),
                'default' => '#ffffff',
                'priority' => 1
            ),
            'category_grid_card_border_color' => array(
                'name' => 'category_grid_card_border_color',
                'type' => 'colorpicker',
                'label' => __( 'Card Border Color', 'betterdocs' ),
                'default' => '#e7e7e7',
                'priority' => 2
            ),
            'category_grid_card_border_width' => array(
                'name' => 'category_grid_card_border_width',
                'type' => 'number',
                'label' => __( 'Card Border Width', 'betterdocs' ),
                'label_subtitle' => __( 'Set to 0 to hide the border.', 'betterdocs' ),
                'default' => 0,
                'priority' => 3
            ),
            'category_grid_card_border_style' => array(
                'name' => 'category_grid_card_border_style',
                'type' => 'select',
                'label' => __( 'Card Border Style', 'betterdocs' ),
                'default' => 'solid',
                'priority' => 4,
                'options' => $this->normalize_options( array(
                    'solid' => __( 'Solid', 'betterdocs' ),
                    'dashed' => __( 'Dashed', 'betterdocs' ),
                    'dotted' => __( 'Dotted', 'betterdocs' )
                ) )
            ),
            'category_grid_card_border_radius' => array(
                'name' => 'category_grid_card_border_radius',
                'type' => 'number',
                'label' => __( 'Card Border Radius', 'betterdocs' ),
                'label_subtitle' => __( 'Enter the radius in pixels.', 'betterdocs' ),
                'default' => 0,
                'priority' => 5
            ),
            'category_grid_card_shadow' => array(
                'name' => 'category_grid_card_shadow',
                'type' => 'select',
                'label' => __( 'Card Shadow', 'betterdocs' ),
                'default' => 'default',
                'priority' => 6,
                'options' => $this->normalize_options( array(
                    'default' => __( 'Default', 'betterdocs' ),
                    'none' => __( 'None', 'betterdocs' ),
                    'subtle' => __( 'Subtle', 'betterdocs' ),
                    'strong' => __( 'Strong', 'betterdocs' )
                ) )
            ),
            'category_grid_card_padding' => array(
                'name' => 'category_grid_card_padding',
                'type' => 'number',
                'label' => __( 'Card Padding', 'betterdocs' ),
                'label_subtitle' => __( 'Set to 0 to keep the layout default. Enter the padding in pixels.', 'betterdocs' ),
                'default' => 0,
                'priority' => 7
            ),
            'category_grid_gap' => array(
                'name' => 'category_grid_gap',
                'type' => 'number',
                'label' => __( 'Card Gap', 'betterdocs' ),
                'label_subtitle' => __( 'Set the space between category cards in pixels.', 'betterdocs' ),
                'default' => 15,
                'priority' => 8
            ),
            'category_grid_title_color' => array(
                'name' => 'category_grid_title_color',
                'type' => 'colorpicker',
                'label' => __( 'Category Title Color', 'betterdocs' ),
                'default' => '#3f5876',
                'priority' => 10
            ),
            'category_grid_title_hover_color' => array(
                'name' => 'category_grid_title_hover_color',
                'type' => 'colorpicker',
                'label' => __( 'Category Title Hover Color', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 11
            ),
            'category_grid_title_font_size' => array(
                'name' => 'category_grid_title_font_size',
                'type' => 'number',
                'label' => __( 'Category Title Font Size', 'betterdocs' ),
                'label_subtitle' => __( 'Enter the size in pixels.', 'betterdocs' ),
                'default' => 20,
                'priority' => 12
            ),
            'category_grid_article_color' => array(
                'name' => 'category_grid_article_color',
                'type' => 'colorpicker',
                'label' => __( 'Document Link Color', 'betterdocs' ),
                'default' => '#566e8b',
                'priority' => 13
            ),
            'category_grid_article_hover_color' => array(
                'name' => 'category_grid_article_hover_color',
                'type' => 'colorpicker',
                'label' => __( 'Document Link Hover Color', 'betterdocs' ),
                'default' => '#528fff',
                'priority' => 14
            ),
            'category_grid_article_font_size' => array(
                'name' => 'category_grid_article_font_size',
                'type' => 'number',
                'label' => __( 'Document Link Font Size', 'betterdocs' ),
                'label_subtitle' => __( 'Enter the size in pixels.', 'betterdocs' ),
                'default' => 15,
                'priority' => 15
            ),
            'category_grid_header_border_color' => array(
                'name' => 'category_grid_header_border_color',
                'type' => 'colorpicker',
                'label' => __( 'Category Header Divider Color', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 16
            ),
            'category_grid_header_border_width' => array(
                'name' => 'category_grid_header_border_width',
                'type' => 'number',
                'label' => __( 'Category Header Divider Width', 'betterdocs' ),
                'label_subtitle' => __( 'Set to 0 to hide the divider.', 'betterdocs' ),
                'default' => 2,
                'priority' => 17
            ),
            'category_grid_count_background' => array(
                'name' => 'category_grid_count_background',
                'type' => 'colorpicker',
                'label' => __( 'Document Count Background', 'betterdocs' ),
                'default' => '#528ffe1a',
                'priority' => 18
            ),
            'category_grid_count_inner_background' => array(
                'name' => 'category_grid_count_inner_background',
                'type' => 'colorpicker',
                'label' => __( 'Document Count Inner Background', 'betterdocs' ),
                'default' => '#528ffe33',
                'priority' => 19
            ),
            'category_grid_count_color' => array(
                'name' => 'category_grid_count_color',
                'type' => 'colorpicker',
                'label' => __( 'Document Count Color', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 20
            ),
            'category_grid_default_icon' => array(
                'name' => 'category_grid_default_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Default Category Icon', 'betterdocs' ),
                'label_subtitle' => __( 'Upload the icon used when a category does not have its own icon.', 'betterdocs' ),
                'priority' => 21
            ),
            'category_grid_document_icon' => array(
                'name' => 'category_grid_document_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Document List Icon', 'betterdocs' ),
                'label_subtitle' => __( 'Upload the icon shown beside document links.', 'betterdocs' ),
                'priority' => 22
            ),
            'category_grid_nested_collapsed_icon' => array(
                'name' => 'category_grid_nested_collapsed_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Nested Category Collapsed Icon', 'betterdocs' ),
                'label_subtitle' => __( 'Upload the icon shown beside a collapsed nested category.', 'betterdocs' ),
                'priority' => 23
            ),
            'category_grid_nested_expanded_icon' => array(
                'name' => 'category_grid_nested_expanded_icon',
                'type' => 'media',
                'value' => '',
                'label' => __( 'Nested Category Expanded Icon', 'betterdocs' ),
                'label_subtitle' => __( 'Upload the icon shown beside an expanded nested category.', 'betterdocs' ),
                'priority' => 24
            ),
            'category_grid_button_background' => array(
                'name' => 'category_grid_button_background',
                'type' => 'colorpicker',
                'label' => __( 'Explore More Background', 'betterdocs' ),
                'default' => '#ffffff',
                'priority' => 25
            ),
            'category_grid_button_color' => array(
                'name' => 'category_grid_button_color',
                'type' => 'colorpicker',
                'label' => __( 'Explore More Text Color', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 26
            ),
            'category_grid_button_border_color' => array(
                'name' => 'category_grid_button_border_color',
                'type' => 'colorpicker',
                'label' => __( 'Explore More Border Color', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 27
            ),
            'category_grid_button_hover_background' => array(
                'name' => 'category_grid_button_hover_background',
                'type' => 'colorpicker',
                'label' => __( 'Explore More Hover Background', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 28
            ),
            'category_grid_button_hover_color' => array(
                'name' => 'category_grid_button_hover_color',
                'type' => 'colorpicker',
                'label' => __( 'Explore More Hover Text Color', 'betterdocs' ),
                'default' => '#ffffff',
                'priority' => 29
            ),
            'category_grid_button_hover_border_color' => array(
                'name' => 'category_grid_button_hover_border_color',
                'type' => 'colorpicker',
                'label' => __( 'Explore More Hover Border Color', 'betterdocs' ),
                'default' => '#528ffe',
                'priority' => 30
            ),
            'category_grid_button_border_radius' => array(
                'name' => 'category_grid_button_border_radius',
                'type' => 'number',
                'label' => __( 'Explore More Border Radius', 'betterdocs' ),
                'label_subtitle' => __( 'Enter the radius in pixels.', 'betterdocs' ),
                'default' => 50,
                'priority' => 31
            ),
            'category_grid_button_font_size' => array(
                'name' => 'category_grid_button_font_size',
                'type' => 'number',
                'label' => __( 'Explore More Font Size', 'betterdocs' ),
                'label_subtitle' => __( 'Enter the size in pixels.', 'betterdocs' ),
                'default' => 16,
                'priority' => 32
            )
        );
    }

    public function design_tab() {
        $settings = array();

        $font_weight_options = $this->normalize_options( array(
            '300' => __( 'Light', 'betterdocs' ),
            '400' => __( 'Regular', 'betterdocs' ),
            '500' => __( 'Medium', 'betterdocs' ),
            '600' => __( 'Semi-bold', 'betterdocs' ),
            '700' => __( 'Bold', 'betterdocs' ),
            '800' => __( 'Extra-bold', 'betterdocs' )
        ) );

        $heading_typography = array(
            'h1' => array(
                'label' => __( 'Heading 1', 'betterdocs' ),
                'size' => 42,
                'weight' => '700',
                'color' => '#1d2939'
            ),
            'h2' => array(
                'label' => __( 'Heading 2', 'betterdocs' ),
                'size' => 32,
                'weight' => '700',
                'color' => '#1d2939'
            ),
            'h3' => array(
                'label' => __( 'Heading 3', 'betterdocs' ),
                'size' => 28,
                'weight' => '600',
                'color' => '#1d2939'
            ),
            'h4' => array(
                'label' => __( 'Heading 4', 'betterdocs' ),
                'size' => 24,
                'weight' => '600',
                'color' => '#1d2939'
            ),
            'h5' => array(
                'label' => __( 'Heading 5', 'betterdocs' ),
                'size' => 20,
                'weight' => '600',
                'color' => '#1d2939'
            ),
            'h6' => array(
                'label' => __( 'Heading 6', 'betterdocs' ),
                'size' => 18,
                'weight' => '600',
                'color' => '#1d2939'
            )
        );

        $typography_fields = array(
            'betterdocs_font_family' => array(
                'name' => 'betterdocs_font_family',
                'type' => 'select',
                'label' => __( 'Font Family', 'betterdocs' ),
                'label_subtitle' => __( 'This font family is used throughout BetterDocs.', 'betterdocs' ),
                'default' => 'inherit',
                'priority' => 1,
                'options' => $this->normalize_options( array(
                    'inherit' => __( 'Theme Default', 'betterdocs' ),
                    'system' => __( 'System UI', 'betterdocs' ),
                    'arial' => __( 'Arial', 'betterdocs' ),
                    'georgia' => __( 'Georgia', 'betterdocs' ),
                    'inter' => __( 'Inter', 'betterdocs' ),
                    'roboto' => __( 'Roboto', 'betterdocs' ),
                    'ibm-plex-sans' => __( 'IBM Plex Sans', 'betterdocs' )
                ) )
            ),
            'betterdocs_font_size' => array(
                'name' => 'betterdocs_font_size',
                'type' => 'number',
                'label' => __( 'Body Text Font Size', 'betterdocs' ),
                'label_subtitle' => __( 'Enter the body text size in pixels.', 'betterdocs' ),
                'default' => 16,
                'priority' => 2
            ),
            'betterdocs_font_weight' => array(
                'name' => 'betterdocs_font_weight',
                'type' => 'select',
                'label' => __( 'Body Text Font Weight', 'betterdocs' ),
                'label_subtitle' => __( 'Choose the weight used by body text.', 'betterdocs' ),
                'default' => '400',
                'priority' => 3,
                'options' => $font_weight_options
            ),
            'betterdocs_font_color' => array(
                'name' => 'betterdocs_font_color',
                'type' => 'colorpicker',
                'label' => __( 'Body Text Color', 'betterdocs' ),
                'label_subtitle' => __( 'Choose the color used by body text.', 'betterdocs' ),
                'default' => '#303030',
                'priority' => 4
            )
        );

        $typography_priority = 5;
        foreach ( $heading_typography as $heading => $heading_defaults ) {
            $typography_fields[ 'betterdocs_' . $heading . '_font_size' ] = array(
                'name' => 'betterdocs_' . $heading . '_font_size',
                'type' => 'number',
                'label' => sprintf( __( '%s Font Size', 'betterdocs' ), $heading_defaults['label'] ),
                'label_subtitle' => sprintf( __( 'Enter the %s size in pixels.', 'betterdocs' ), strtolower( $heading_defaults['label'] ) ),
                'default' => $heading_defaults['size'],
                'priority' => $typography_priority++
            );
            $typography_fields[ 'betterdocs_' . $heading . '_font_weight' ] = array(
                'name' => 'betterdocs_' . $heading . '_font_weight',
                'type' => 'select',
                'label' => sprintf( __( '%s Font Weight', 'betterdocs' ), $heading_defaults['label'] ),
                'label_subtitle' => sprintf( __( 'Choose the weight used by %s.', 'betterdocs' ), strtolower( $heading_defaults['label'] ) ),
                'default' => $heading_defaults['weight'],
                'priority' => $typography_priority++,
                'options' => $font_weight_options
            );
            $typography_fields[ 'betterdocs_' . $heading . '_font_color' ] = array(
                'name' => 'betterdocs_' . $heading . '_font_color',
                'type' => 'colorpicker',
                'label' => sprintf( __( '%s Color', 'betterdocs' ), $heading_defaults['label'] ),
                'label_subtitle' => sprintf( __( 'Choose the color used by %s.', 'betterdocs' ), strtolower( $heading_defaults['label'] ) ),
                'default' => $heading_defaults['color'],
                'priority' => $typography_priority++
            );
        }

        $settings[ 'betterdocs_typography' ] = array(
            'name' => 'betterdocs_typography',
            'type' => 'section',
            'label' => __( 'Typography', 'betterdocs' ),
            'label_subtitle' => __( 'Choose the global font family and separate body and heading styles for BetterDocs. Theme Default inherits your active theme font.', 'betterdocs' ),
            'priority' => 1,
            'fields' => $typography_fields
        );

        $settings[ 'single_doc_design' ] = array(
            'name' => 'single_doc_design',
            'type' => 'section',
            'label' => __( 'Single Documents', 'betterdocs' ),
            'label_subtitle' => __( 'Choose whether single documents use the standard boxed width or the full available responsive width.', 'betterdocs' ),
            'priority' => 2,
            'fields' => array(
                'single_doc_layout_width' => array(
                    'name' => 'single_doc_layout_width',
                    'type' => 'select',
                    'label' => __( 'Single Document Width', 'betterdocs' ),
                    'label_subtitle' => __( 'The wide layout removes the default maximum width from single-document content.', 'betterdocs' ),
                    'default' => 'boxed',
                    'priority' => 1,
                    'options' => $this->normalize_options( array(
                        'boxed' => __( 'Boxed', 'betterdocs' ),
                        'wide' => __( 'Wide and Responsive', 'betterdocs' )
                    ) )
                )
            )
        );

        $settings[ 'category_grid_design' ] = array(
            'name' => 'category_grid_design',
            'type' => 'section',
            'label' => __( 'Category Grid', 'betterdocs' ),
            'label_subtitle' => __( 'Control the category grid appearance directly from BetterDocs. These settings also apply to category-grid shortcodes used with WPBakery.', 'betterdocs' ),
            'priority' => 3,
            'fields' => $this->category_grid_design_fields()
        );

        $settings[ 'search_design' ] = array(
            'name' => 'search_design',
            'type' => 'section',
            'label' => __( 'Search', 'betterdocs' ),
            'label_subtitle' => __( 'Adjust the BetterDocs search field, search dialog, and Docs and FAQ tabs.', 'betterdocs' ),
            'priority' => 4,
            'fields' => array(
                'betterdocs_search_docs_tab_color' => array(
                    'name' => 'betterdocs_search_docs_tab_color',
                    'type' => 'colorpicker',
                    'label' => __( 'Docs Tab Color', 'betterdocs' ),
                    'default' => '#00b884',
                    'priority' => 1
                ),
                'betterdocs_search_faq_tab_color' => array(
                    'name' => 'betterdocs_search_faq_tab_color',
                    'type' => 'colorpicker',
                    'label' => __( 'FAQ Tab Color', 'betterdocs' ),
                    'default' => '#00b884',
                    'priority' => 2
                ),
                'betterdocs_search_sidebar_hover_background' => array(
                    'name' => 'betterdocs_search_sidebar_hover_background',
                    'type' => 'colorpicker',
                    'label' => __( 'Sidebar Hover Background', 'betterdocs' ),
                    'default' => '#e8fff4',
                    'priority' => 13
                ),
                'betterdocs_search_modal_hover_background' => array(
                    'name' => 'betterdocs_search_modal_hover_background',
                    'type' => 'colorpicker',
                    'label' => __( 'Search Modal Hover Background', 'betterdocs' ),
                    'default' => '#f6fef9',
                    'priority' => 14
                ),
                'betterdocs_search_field_border_color' => array(
                    'name' => 'betterdocs_search_field_border_color',
                    'type' => 'colorpicker',
                    'label' => __( 'Search Field Border Color', 'betterdocs' ),
                    'default' => '#d0d5dd',
                    'priority' => 3
                ),
                'betterdocs_search_field_border_width' => array(
                    'name' => 'betterdocs_search_field_border_width',
                    'type' => 'number',
                    'label' => __( 'Search Field Border Width', 'betterdocs' ),
                    'label_subtitle' => __( 'Set to 0 to hide the border.', 'betterdocs' ),
                    'default' => 1,
                    'priority' => 4
                ),
                'betterdocs_search_field_border_style' => array(
                    'name' => 'betterdocs_search_field_border_style',
                    'type' => 'select',
                    'label' => __( 'Search Field Border Style', 'betterdocs' ),
                    'default' => 'solid',
                    'priority' => 5,
                    'options' => $this->normalize_options( array(
                        'solid' => __( 'Solid', 'betterdocs' ),
                        'dashed' => __( 'Dashed', 'betterdocs' ),
                        'dotted' => __( 'Dotted', 'betterdocs' )
                    ) )
                ),
                'betterdocs_search_field_border_radius' => array(
                    'name' => 'betterdocs_search_field_border_radius',
                    'type' => 'number',
                    'label' => __( 'Search Field Border Radius', 'betterdocs' ),
                    'label_subtitle' => __( 'Enter the radius in pixels.', 'betterdocs' ),
                    'default' => 4,
                    'priority' => 6
                ),
                'betterdocs_search_modal_border_color' => array(
                    'name' => 'betterdocs_search_modal_border_color',
                    'type' => 'colorpicker',
                    'label' => __( 'Search Dialog Border Color', 'betterdocs' ),
                    'default' => '#d0d5dd',
                    'priority' => 7
                ),
                'betterdocs_search_modal_border_width' => array(
                    'name' => 'betterdocs_search_modal_border_width',
                    'type' => 'number',
                    'label' => __( 'Search Dialog Border Width', 'betterdocs' ),
                    'label_subtitle' => __( 'Set to 0 to hide the border.', 'betterdocs' ),
                    'default' => 1,
                    'priority' => 8
                ),
                'betterdocs_search_modal_border_style' => array(
                    'name' => 'betterdocs_search_modal_border_style',
                    'type' => 'select',
                    'label' => __( 'Search Dialog Border Style', 'betterdocs' ),
                    'default' => 'solid',
                    'priority' => 9,
                    'options' => $this->normalize_options( array(
                        'solid' => __( 'Solid', 'betterdocs' ),
                        'dashed' => __( 'Dashed', 'betterdocs' ),
                        'dotted' => __( 'Dotted', 'betterdocs' )
                    ) )
                ),
                'betterdocs_search_modal_border_radius' => array(
                    'name' => 'betterdocs_search_modal_border_radius',
                    'type' => 'number',
                    'label' => __( 'Search Dialog Border Radius', 'betterdocs' ),
                    'label_subtitle' => __( 'Enter the radius in pixels.', 'betterdocs' ),
                    'default' => 4,
                    'priority' => 10
                ),
                'betterdocs_search_docs_tab_icon' => array(
                    'name' => 'betterdocs_search_docs_tab_icon',
                    'type' => 'media',
                    'value' => '',
                    'label' => __( 'Docs Tab Icon', 'betterdocs' ),
                    'label_subtitle' => __( 'Upload the icon shown beside the Docs tab.', 'betterdocs' ),
                    'priority' => 11
                ),
                'betterdocs_search_faq_tab_icon' => array(
                    'name' => 'betterdocs_search_faq_tab_icon',
                    'type' => 'media',
                    'value' => '',
                    'label' => __( 'FAQ Tab Icon', 'betterdocs' ),
                    'label_subtitle' => __( 'Upload the icon shown beside the FAQ tab.', 'betterdocs' ),
                    'priority' => 12
                )
            )
        );

        $settings[ 'gutenberg_link' ] = array(
            'name' => 'gutenberg_link',
            'type' => 'action',
            'action' => 'betterdocs_settings_gutenberg_link',
            'button' => betterdocs()->helper->current_theme_is_fse_theme() ? __( 'Design with Gutenberg', 'betterdocs' ) : __( 'Learn More', 'betterdocs' ),
            'url' => $this->gutenberg_link(),
            'customizer_img' => betterdocs()->assets->icon( 'customizer/gutenberg-preview.png', true ),
            'priority' => 20
        );

        $settings[ 'elementor_link' ] = array(
            'name' => 'elementor_link',
            'type' => 'action',
            'action' => 'betterdocs_settings_elementor_link',
            'button' => $this->is_elementor_pro() ? __( 'Design with Elementor', 'betterdocs' ) : __( 'Learn More', 'betterdocs' ),
            'url' => $this->elementor_link(),
            'customizer_img' => betterdocs()->assets->icon( 'customizer/elementor-preview.png', true ),
            'priority' => 21
        );

        if ( ! betterdocs()->helper->current_theme_is_fse_theme() ) {
            $settings[ 'customizer_link' ] = array(
                'name' => 'customizer_link',
                'type' => 'action',
                'action' => 'betterdocs_settings_customizer_link',
                'button' => __( 'Customize in BetterDocs', 'betterdocs' ),
                'url' => $this->customizer_link(),
                'customizer_img' => betterdocs()->assets->icon( 'customizer/customizer-preview.png', true ),
                'priority' => 22
            );
        }

        return $settings;
    }

    /**
     * Get All Roles
     * dynamically
     *
     * @return array
     */
    public function get_roles() {
        $roles = wp_roles()->role_names;
        unset( $roles[ 'subscriber' ] );

        return $roles;
    }

    /**
     * Set dark mode
     *
     * @return void
     * @since 1.0.0
     */
    public function dark_mode() {
        if ( ! check_ajax_referer( 'doc_cat_order_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }

        if ( isset( $_POST[ 'mode' ] ) ) {
            $mode = sanitize_text_field( wp_unslash( $_POST[ 'mode' ] ) );
            if ( $this->save( 'dark_mode', rest_sanitize_boolean( $mode ) ) ) {
                wp_send_json_success();
            }
        }

        wp_send_json_error();
    }

    public function get_normalized_value( $key, $value, $default = null ) {
        $_origin_value = $_value = $value;

        if ( in_array( $_value, array( 'on', 'off', '1', 'false', 'true' ), true ) ) {
            switch ( $_value ) {
                case 'on':
                case 'ON':
                case '1':
                case 'true':
                    $_value = true;
                    break;
                case 'off':
                case 'OFF':
                case '':
                case 'false':
                    $_value = false;
                    break;
            }
        }

        $this->type_validation( $_value, $default );

        return $_value;
    }

    public function get_normalized_values( $values, $default_values = array() ) {
        if ( empty( $values ) ) {
            return array();
        }

        $_settings = array();
        foreach ( $values as $key => $value ) {
            $_default_value    = isset( $default_values[ $key ] ) ? $default_values[ $key ] : null;
            $_settings[ $key ] = $this->get_normalized_value( $key, $value, $_default_value );
        }

        return $_settings;
    }

    public function get_all( $raw = false ) {
        $_default_settings = $raw ? array() : array_merge( $this->get_default(), $this->get_pro_defaults() );
        $_settings         = $this->database->get( $this->base_key, $_default_settings );

        return $this->get_normalized_values( $_settings, $_default_settings );
    }

    public function type_validation( &$value, $defaultValue = null ) {
        if ( null !== $defaultValue ) {
            /**
             * Check if value is not in same type
             */
            $_default_type = gettype( $defaultValue );

            if ( ! ( is_scalar( $defaultValue ) && is_scalar( $value ) ) && empty( $value ) ) {
                $value = $defaultValue;
            }

            settype( $value, $_default_type );
        }
    }

    /**
     * Get settings value by key
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $get_all
     *
     * @return mixed
     * @since 2.5.0
     *
     */
    public function get( $key, $default = null ) {
        $_default_settings = array_merge( $this->get_default(), $this->get_pro_defaults() );
        $_settings         = $this->database->get( $this->base_key, $_default_settings );

        $_value = $default;
        switch ( true ) {
            // Check if it's a PRO Option
            case ! isset( $_default_settings[ $key ] ):
                $_value = $default;
                break;
            // Check if it's a FREE Option and not in DB.
            case ! isset( $_settings[ $key ] ) && isset( $_default_settings[ $key ] ):
                $_value = null !== $default ? $default : $_default_settings[ $key ];
                break;
            // Check if it's a FREE Option
            case isset( $_settings[ $key ] ) && isset( $_default_settings[ $key ] ):
                $_value = $_settings[ $key ];
                break;
        }

        $_value = $this->get_normalized_value( $key, $_value, isset( $_default_settings[ $key ] ) ? $_default_settings[ $key ] : null );

        if ( gettype( $_value ) === 'string' ) {
            if ( empty( $_value ) && null != $default ) {
                $_value = $default;
            } elseif ( empty( $_value ) && null === $default && isset( $_default_settings[ $key ] ) ) {
                $_value = $_default_settings[ $key ];
            }
        }

        return $_value;
    }

    public function get_raw_field( $key, $default = null ) {
        $_settings = $this->database->get( $this->base_key, array() );

        if ( isset( $_settings[ $key ] ) ) {
            return $this->get_normalized_value( $key, $_settings[ $key ], $default );
        }

        return $default;
    }

    public function save( $key, $value ) {
        $_settings         = $this->database->get( $this->base_key, array() );
        $_settings[ $key ] = $value;

        return $this->database->save( $this->base_key, $_settings );
    }

    public function save_settings( $settings ) {
        $existing_plugins = betterdocs()->kbmigration->knowledge_base_plugins();
        if ( ! current_user_can( 'edit_docs_settings' ) ) {
            return new WP_Error( 'unauthorized_action', __( 'You don\'t have any rights for saving settings.', 'betterdocs' ) );
        }

        // The frontend sends the currently-active settings tab as a transport-only hint so
        // tab-scoped validation (the AI min-token floor below) only runs when that tab is being
        // saved. It must never be persisted into the settings option.
        $active_tab = '';
        if ( array_key_exists( '_active_tab', $settings ) ) {
            $active_tab = (string) $settings[ '_active_tab' ];
            unset( $settings[ '_active_tab' ] );
        }

        // Quickbuilder sends back the full UI-loaded payload. To avoid overwriting values that we
        // asynchronously updated in the DB via AJAX (like Git integration fields), we strictly remove them
        // from the incoming save payload so that the freshly loaded `$_old_settings` values persist.
        $exclude_ajax_keys = array( 'git_provider', 'git_repository_url', 'git_branch', 'git_docs_directory' );
        foreach ( $exclude_ajax_keys as $key ) {
            if ( array_key_exists( $key, $settings ) ) {
                unset( $settings[ $key ] );
            }
            if ( isset( $settings[ 'values' ] ) && is_array( $settings[ 'values' ] ) && array_key_exists( $key, $settings[ 'values' ] ) ) {
                unset( $settings[ 'values' ][ $key ] );
            }
        }

        $_old_settings = $this->database->get( $this->base_key, $this->get_default() );

        // The frontend only ever sees masked API keys. A submitted value that still looks like a
        // mask (contains a run of asterisks — no real key does) means "unchanged": restore the
        // stored original so a mask string is never persisted. Guarding on the mask SHAPE rather
        // than strict equality with mask(stored) closes the corruption loop QA hit: once a mask
        // slips into storage, mask(mask) === mask, so an equality-only guard would faithfully
        // preserve the corrupted value forever (round-2 follow-up #1). When the stored value is
        // itself mask-shaped it is unrecoverable — clear it so the UI and the GeoIP/GA4 status
        // surfaces honestly report a missing key instead of failing downstream with 401s.
        $sensitive_api_keys = self::sensitive_api_key_fields();
        foreach ( $sensitive_api_keys as $api_key_field ) {
            if ( ! isset( $settings[ $api_key_field ] ) ) {
                continue;
            }
            $incoming       = trim( (string) $settings[ $api_key_field ] );
            $stored         = isset( $_old_settings[ $api_key_field ] ) ? (string) $_old_settings[ $api_key_field ] : '';
            $stored_is_mask = '' !== $stored && preg_match( '/\*{4,}/', $stored );

            if ( '' !== $incoming && preg_match( '/\*{4,}/', $incoming ) ) {
                $settings[ $api_key_field ] = $stored_is_mask ? '' : $stored;
            }
        }

        // Minimum-token policy: reject saves where a feature's max_token field is below the
        // per-model floor defined by AIHelper::get_min_tokens(). The model may be unchanged in
        // this submission, so fall back to the stored value when it isn't part of the payload.
        // Both token fields live under the AI Content Suite tab, so enforce the floor only when
        // that tab is the one being saved — saving any other settings page never trips it (e.g.
        // old users whose stored value predates the floor). When the active-tab hint is absent
        // (stale cached JS, the quick-setup wizard, or any programmatic caller) the loop is
        // skipped and the save is allowed through.
        $token_pairs = array(
            array(
                'tab' => 'tab-betterdocs-ai',
                'context' => 'write_with_ai',
                'token_key' => 'ai_autowrite_max_token',
                'model_key' => 'ai_model',
                'label' => __( 'Write with AI', 'betterdocs' )
            ),
            array(
                'tab' => 'tab-betterdocs-ai',
                'context' => 'article_summary',
                'token_key' => 'article_summary_max_token',
                'model_key' => 'ai_model',
                'label' => __( 'AI Doc Summarizer', 'betterdocs' )
            )
        );
        foreach ( $token_pairs as $pair ) {
            if ( $active_tab !== $pair[ 'tab' ] ) {
                continue;
            }
            if ( ! array_key_exists( $pair[ 'token_key' ], $settings ) ) {
                continue;
            }
            $incoming_tokens = (int) $settings[ $pair[ 'token_key' ] ];
            $incoming_model  = isset( $settings[ $pair[ 'model_key' ] ] )
            ? $settings[ $pair[ 'model_key' ] ]
            : ( isset( $_old_settings[ $pair[ 'model_key' ] ] ) ? $_old_settings[ $pair[ 'model_key' ] ] : '' );
            $min = AIHelper::get_min_tokens( $pair[ 'context' ], $incoming_model );
            if ( $min > 0 && $incoming_tokens < $min ) {
                return new WP_Error(
                    'min_tokens_below_threshold',
                    sprintf(
                        /* translators: 1: feature label, 2: minimum tokens, 3: model id */
                        __( '%1$s max tokens must be at least %2$d for %3$s.', 'betterdocs' ),
                        $pair[ 'label' ],
                        $min,
                        $incoming_model
                    )
                );
            }
        }

        // @todo: sanitize the data before inject in DB.
        $_normalized_settings = $this->get_normalized_values( $settings );
        if ( $existing_plugins && isset( $_normalized_settings[ 'migration_step' ] ) && true == $_normalized_settings[ 'migration_step' ] ) {
            betterdocs()->kbmigration->migrate();
        }
        $_settings = wp_parse_args( $_normalized_settings, $_old_settings );

        // Detect whether this save actually changes the effective settings.
        //
        // The stored option and the submitted payload are normalized differently:
        // an optional field can be ABSENT from storage yet arrive as '' (e.g.
        // Feedback URL), and array fields can be stored empty ( [] ) while their
        // normalized/default form is non-empty (e.g. Instant Answer's
        // display_ia_texonomy defaults to ['all']). Comparing the raw arrays
        // ( $_settings != $_old_settings ) therefore reported a phantom change on
        // every save, leaving the tab perpetually "dirty" and always toasting
        // "Changes Saved Successfully." instead of "There are no changes to be
        // saved." — see WPDevelopers/betterdocs-pro#78.
        //
        // Compare like-for-like instead: fill defaults on both sides and run both
        // through the same normalization, so semantically-equal states (absent vs
        // '', [] vs ['all'], 'on' vs true) collapse to identical values and only a
        // real edit registers. This is also more reliable than update_option()'s
        // return, which is false whenever values serialize identically under object
        // caching or type coercion even when the user did change something (#49).
        $_defaults       = array_merge( $this->get_default(), $this->get_pro_defaults() );
        $_old_normalized = $this->get_normalized_values( wp_parse_args( $_old_settings, $_defaults ), $_defaults );
        $_new_normalized = $this->get_normalized_values( wp_parse_args( $_settings, $_defaults ), $_defaults );
        $_has_changes    = $_new_normalized != $_old_normalized;

        $_saved = $this->database->save( $this->base_key, $_settings );

        do_action_ref_array( 'betterdocs::settings::saved', array( $_saved, $_settings, $_old_settings, &$this ) );

        // The success / no-changes toast reflects whether the user made a real
        // change, not update_option()'s (unreliable) return value.
        return $_has_changes;
    }

    public function views( $hook ) {
        return betterdocs()->views->get( 'admin/settings' );
    }

    public function save_default_settings() {
        $_settings = $this->get_all();

        return $this->database->save( $this->base_key, $_settings );
    }

    public function get_pages() {
        $_pages = betterdocs()->query->get_posts( array(
            'post_type' => 'page',
            'numberposts' => -1,
            'post_status' => 'publish',
            'posts_per_page' => -1
        ) );

        $__pages = array();

        if ( ! empty( $_pages ) ) {
            $__pages[ 0 ] = __( 'Select a Page', 'betterdocs' );
            foreach ( $_pages->posts as $page ) {
                $__pages[ $page->ID ] = esc_html( $page->post_title );
            }
        }

        return $__pages;
    }

    public function settings_args() {
        $wp_roles = $this->normalize_options( $this->get_roles() );

        $roles_for_ikb = $this->normalize_options( array_merge( array( 'all' => __( 'All logged in users', 'betterdocs' ) ), wp_roles()->role_names ) );

        unset( $roles_for_ikb[ 'administrator' ] );

        $settings = array(
            'id' => 'betterdocs_settings_metabox_wrapper',
            'title' => __( 'betterdocs', 'betterdocs' ),
            'object_types' => array( 'betterdocs' ),
            'context' => 'normal',
            'priority' => 'high',
            'show_header' => false,
            'tabnumber' => false,
            'is_pro_active' => betterdocs()->is_pro_active(),
            'logoURL' => betterdocs()->assets->icon( 'betterdocs-icon.svg', true ),
            'layout' => 'vertical',
            'config' => array(
                'active' => 'tab-general',
                'sidebar' => true,
                'title' => false
            ),
            'submit' => array(
                'show' => true,
                'label' => __( 'Save', 'betterdocs' ),
                'loadingLabel' => __( 'Saving...', 'betterdocs' ),
                'class' => 'save-settings',
                'rules' => Rules::logicalRule( array(
                    Rules::is( 'config.active', 'tab-shortcodes', true ),
                    Rules::is( 'config.active', 'tab-instant-answer', true ),
                    Rules::is( 'config.active', 'tab-import-export', true ),
                    Rules::is( 'config.active', 'tab-migration', true )
                ), 'and' )
            ),
            'values' => betterdocs()->is_pro_active() ? $this->get_all() : array_merge( $this->get_all(), $this->pro_settings_default_values() ),
            'tabs' => apply_filters( 'betterdocs_settings_tabs', array(
                'tab-general' => apply_filters( 'betterdocs_settings_tab_general', array(
                    'id' => 'tab-general',
                    'label' => __( 'General', 'betterdocs' ),
                    'classes' => 'tab-general',
                    'priority' => 10,
                    'fields' => array(
                        'title-general' => apply_filters( 'betterdocs_encyclopedia_settings', array(
                            'name' => 'title-general-tab',
                            'type' => 'section',
                            'label' => __( 'General Settings', 'betterdocs' ),
                            'priority' => 10,
                            'fields' => array(
                                'multiple_kb' => apply_filters( 'betterdocs_multi_kb_settings', array(
                                    'name' => 'multiple_kb',
                                    'type' => 'toggle',
                                    'label' => __( 'Multiple Knowledge Base', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => '',
                                    'priority' => 1,
                                    'is_pro' => true
                                ) ),
                                'builtin_doc_page' => array(
                                    'name' => 'builtin_doc_page',
                                    'type' => 'toggle',
                                    'label' => __( 'Built-in Documentation Page', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => 1,
                                    'priority' => 2
                                ),
                                'enable_category_hierarchy_slugs' => array(
                                    'name' => 'enable_category_hierarchy_slugs',
                                    'type' => 'toggle',
                                    'label' => __( 'Enable Category Hierarchy Slug', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => false,
                                    'priority' => 3,
                                    'label_subtitle' => __( "Shows Hierarchy Based Permalink Slugs For Doc Categories & Single Doc Page", 'betterdocs' )
                                ),
                                'breadcrumb_doc_title' => array(
                                    'name' => 'breadcrumb_doc_title',
                                    'type' => 'text',
                                    'label' => __( 'Documentation Page Title', 'betterdocs' ),
                                    'default' => __( 'Docs', 'betterdocs' ),
                                    'priority' => 4,
                                    'rules' => Rules::is( 'builtin_doc_page', true )
                                ),
                                'docs_slug' => array(
                                    'name' => 'docs_slug',
                                    'type' => 'text',
                                    'label' => __( 'BetterDocs Root Slug', 'betterdocs' ),
                                    'default' => 'docs',
                                    'priority' => 5,
                                    'rules' => Rules::is( 'builtin_doc_page', true )
                                ),
                                'docs_page' => array(
                                    'name' => 'docs_page',
                                    'label' => __( 'Docs Page', 'betterdocs' ),
                                    'type' => 'select',
                                    'default' => 0,
                                    'priority' => 6,
                                    'search' => true,
                                    'options' => $this->normalize_options( $this->get_pages() ),
                                    'label_subtitle' => __( 'You will need to insert BetterDocs Shortcode inside the page. This page will be used as docs permalink.', 'betterdocs' ),
                                    'rules' => Rules::is( 'builtin_doc_page', false )
                                ),

                                'category_slug' => array(
                                    'name' => 'category_slug',
                                    'type' => 'text',
                                    'label' => __( 'Custom Category Slug', 'betterdocs' ),
                                    'default' => 'docs-category',
                                    'priority' => 7
                                ),
                                'tag_slug' => array(
                                    'name' => 'tag_slug',
                                    'type' => 'text',
                                    'label' => __( 'Custom Tag Slug', 'betterdocs' ),
                                    'default' => 'docs-tag',
                                    'priority' => 8
                                ),
                                'permalink_structure' => array(
                                    'name' => 'permalink_structure',
                                    'type' => 'permalink_structure',
                                    'label' => __( 'Single Docs Permalink', 'betterdocs' ),
                                    'default' => PostType::permalink_structure(),
                                    'priority' => 9,
                                    'tags' => $this->normalize_options( array(
                                        '%doc_category%' => '%doc_category%',
                                        '%knowledge_base%' => '%knowledge_base%'
                                    ) ),
                                    'label_subtitle' => __( 'Make sure to keep Docs Root Slug in the Single Docs Permalink. You are not able to keep it blank. You can use the available tags from below.', 'betterdocs' )
                                ),
                                'enable_glossaries' => array(
                                    'name' => 'enable_glossaries',
                                    'type' => 'toggle',
                                    'label' => __( 'Show Glossary', 'betterdocs' ),
                                    'label_subtitle' => __( 'Enable the glossary feature to allow users to look up definitions for terms used within your encyclopedia or glossaries themselves.', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => false,
                                    'priority' => 10,
                                    'is_pro' => true
                                ),
                                'enable_encyclopedia' => array(
                                    'name' => 'enable_encyclopedia',
                                    'type' => 'toggle',
                                    'label' => __( 'Built-in Encyclopedia Page', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => false,
                                    'priority' => 11,
                                    'is_pro' => true
                                ),
                                'enable_faq_schema' => array(
                                    'name' => 'enable_faq_schema',
                                    'type' => 'toggle',
                                    'label' => __( 'FAQ Schema', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => '',
                                    'priority' => 12
                                ),
                                'analytics_from' => array(
                                    'name' => 'analytics_from',
                                    'type' => 'select',
                                    'label' => __( 'Analytics From', 'betterdocs' ),
                                    'options' => $this->normalize_options( array(
                                        'everyone' => __( 'Everyone', 'betterdocs' ),
                                        'guests' => __( 'Guests Only', 'betterdocs' ),
                                        'registered_users' => __( 'Registered Users Only', 'betterdocs' )
                                    ) ),
                                    'default' => 'everyone',
                                    'priority' => 13,
                                    'is_pro' => true
                                ),
                                'unique_visitor_count' => array(
                                    'name' => 'unique_visitor_count',
                                    'type' => 'toggle',
                                    'label' => __( 'Unique Visitor Count', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => false,
                                    'priority' => 14,
                                    'is_pro' => true
                                ),
                                'exclude_bot_analytics' => array(
                                    'name' => 'exclude_bot_analytics',
                                    'type' => 'toggle',
                                    'label' => __( 'Exclude Bot Analytics', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => false,
                                    'priority' => 15,
                                    'is_pro' => true
                                )

                            )
                        ) )
                    )
                ) ),
                'tab-layout' => apply_filters( 'betterdocs_settings_tab_layout', array(
                    'id' => 'tab-layout',
                    'label' => __( 'Layout', 'betterdocs' ),
                    'classes' => 'tab-layout',
                    'priority' => 20,
                    'fields' => array(
                        'title-layout' => array(
                            'name' => 'title-layout-tab',
                            'type' => 'section',
                            'label' => __( 'Layout Settings', 'betterdocs' ),
                            'priority' => 20,
                            'fields' => array(
                                'tab-sidebar-layout' => apply_filters( 'betterdocs_settings_tab_sidebar_layout', array(
                                    'id' => 'tab-sidebar-layout',
                                    'name' => 'tab_sidebar_layout',
                                    'label' => __( 'Layout Settings', 'betterdocs' ),
                                    'classes' => 'tab-layout',
                                    'type' => 'tab',
                                    'active' => 'layout_documentation_page',
                                    'completionTrack' => true,
                                    'sidebar' => false,
                                    'save' => false,
                                    'title' => false,
                                    'config' => array(
                                        'active' => 'layout_documentation_page',
                                        'sidebar' => false,
                                        'title' => false
                                    ),
                                    'submit' => array(
                                        'show' => false
                                    ),
                                    'step' => array(
                                        'show' => false
                                    ),
                                    'priority' => 20,
                                    'fields' => array(
                                        'layout_documentation_page' => array(
                                            'id' => 'layout_documentation_page',
                                            'name' => 'layout_documentation_page',
                                            'type' => 'section',
                                            'label' => __( 'Documentation Page', 'betterdocs' ),
                                            'priority' => 1,
                                            'fields' => array(
                                                'tab-nested-layout-1' => array(
                                                    'id' => 'tab-nested-layout-1',
                                                    'name' => 'tab_nested_layout_1',
                                                    'label' => __( 'Documentation Page', 'betterdocs' ),
                                                    'classes' => 'tab-nested-layout',
                                                    'type' => 'tab',
                                                    'active' => 'layout_documentation_page_general',
                                                    'completionTrack' => true,
                                                    'sidebar' => false,
                                                    'save' => false,
                                                    'title' => false,
                                                    'config' => array(
                                                        'active' => 'layout_documentation_page_general',
                                                        'sidebar' => false,
                                                        'title' => false
                                                    ),
                                                    'submit' => array(
                                                        'show' => false
                                                    ),
                                                    'step' => array(
                                                        'show' => false
                                                    ),
                                                    'priority' => 1,
                                                    'fields' => array(
                                                        'layout_documentation_page_general' => array(
                                                            'id' => 'layout_documentation_page_general',
                                                            'name' => 'layout_documentation_page_general',
                                                            'type' => 'section',
                                                            'label' => __( 'General', 'betterdocs' ),
                                                            'priority' => 1,
                                                            'fields' => array(
                                                                'docs_list_icon' => array(
                                                                    'name' => 'docs_list_icon',
                                                                    'type' => 'media',
                                                                    'value' => '',
                                                                    'label' => __( 'Docs List Icon', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Upload your own preferred document list icon', 'betterdocs' ),
                                                                    'priority' => 0
                                                                ),
                                                                'category_title_link' => array(
                                                                    'name' => 'category_title_link',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Category Title Link', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'This setting is applicable for category grid layout', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => false,
                                                                    'priority' => 0
                                                                ),
                                                                'masonry_layout' => array(
                                                                    'name' => 'masonry_layout',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Masonry', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 1
                                                                ),
                                                                'archive_lazy_load_descendants' => array(
                                                                    'name' => 'archive_lazy_load_descendants',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Lazy Load Sidebar Docs & Subcategories', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Applies to the Sidebar layout wherever it appears — single docs, category archives, and the Sidebar widget/block. Off by default: enable to render only the active category upfront and fetch the rest on click (or on scroll for the Memphis layout), cutting initial HTML size on large knowledge bases.', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => false,
                                                                    'priority' => 2
                                                                ),
                                                                'nested_subcategory' => array(
                                                                    'name' => 'nested_subcategory',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Nested Sub Category', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 3
                                                                ),
                                                                'column_number' => array(
                                                                    'name' => 'column_number',
                                                                    'type' => 'number',
                                                                    'label' => __( 'Number Of Columns', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'This setting is not applicable for sleek layout.', 'betterdocs' ),
                                                                    'default' => 3,
                                                                    'priority' => 4
                                                                ),
                                                                'posts_number' => apply_filters( 'betterdocs_posts_number', array(
                                                                    'name' => 'posts_number',
                                                                    'type' => 'number',
                                                                    'label' => __( 'Number Of Docs', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'This setting is not applicable for handbook layout.', 'betterdocs' ),
                                                                    'default' => 10,
                                                                    'priority' => 5
                                                                ) ),
                                                                'post_count' => array(
                                                                    'name' => 'post_count',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Doc Count', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 6
                                                                ),
                                                                'count_text' => array(
                                                                    'name' => 'count_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Count Text', 'betterdocs' ),
                                                                    'default' => __( 'Docs', 'betterdocs' ),
                                                                    'priority' => 7
                                                                ),
                                                                'count_text_singular' => array(
                                                                    'name' => 'count_text_singular',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Count Text Singular', 'betterdocs' ),
                                                                    'default' => __( 'Doc', 'betterdocs' ),
                                                                    'priority' => 8
                                                                ),
                                                                'exploremore_btn' => array(
                                                                    'name' => 'exploremore_btn',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Explore More Button', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => true,
                                                                    'priority' => 9
                                                                ),
                                                                'exploremore_btn_txt' => array(
                                                                    'name' => 'exploremore_btn_txt',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Explore More Button Text', 'betterdocs' ),
                                                                    'default' => __( 'Explore More', 'betterdocs' ),
                                                                    'priority' => 10,
                                                                    'rules' => Rules::is( 'exploremore_btn', true )
                                                                ),
                                                                'betterdocs_popular_docs_text' => array(
                                                                    'name' => 'betterdocs_popular_docs_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Popular Docs Text', 'betterdocs' ),
                                                                    'default' => __( 'Popular Docs', 'betterdocs' ),
                                                                    'priority' => 11,
                                                                    'is_pro' => true
                                                                ),
                                                                'betterdocs_popular_docs_number' => array(
                                                                    'name' => 'betterdocs_popular_docs_number',
                                                                    'type' => 'number',
                                                                    'label' => __( 'Popular Docs Number', 'betterdocs' ),
                                                                    'default' => 10,
                                                                    'priority' => 12,
                                                                    'is_pro' => true
                                                                )
                                                            )
                                                        ),
                                                        'layout_documentation_page_search' => array(
                                                            'id' => 'layout_documentation_page_search',
                                                            'name' => 'layout_documentation_page_search',
                                                            'type' => 'section',
                                                            'label' => __( 'Search', 'betterdocs' ),
                                                            'priority' => 2,
                                                            'fields' => array(
                                                                'live_search' => array(
                                                                    'name' => 'live_search',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Live Search', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 1
                                                                ),
                                                                'advance_search' => apply_filters( 'betterdocs_advance_search_settings', array(
                                                                    'name' => 'advance_search',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Advanced Search', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 2,
                                                                    'is_pro' => true
                                                                ) ),
                                                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- legacy public filter name for category-exclude settings field, retained for back-compat.
                                                                'child_category_exclude' => apply_filters( 'child_category_exclude', array(
                                                                    'name' => 'child_category_exclude',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Exclude Child Terms In Category Search', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 3,
                                                                    'is_pro' => true
                                                                ) ),
                                                                'popular_keyword_limit' => apply_filters( 'betterdocs_popular_keyword_limit_settings', array(
                                                                    'name' => 'popular_keyword_limit',
                                                                    'type' => 'number',
                                                                    'label' => __( 'Minimum amount of Keywords Search', 'betterdocs' ),
                                                                    'default' => 5,
                                                                    'priority' => 4,
                                                                    'is_pro' => true
                                                                ) ),
                                                                'search_letter_limit' => array(
                                                                    'name' => 'search_letter_limit',
                                                                    'type' => 'number',
                                                                    'label' => __( 'Minimum Character Limit For Search Result', 'betterdocs' ),
                                                                    'priority' => 5,
                                                                    'default' => 3
                                                                ),
                                                                'search_placeholder' => array(
                                                                    'name' => 'search_placeholder',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Search Placeholder', 'betterdocs' ),
                                                                    'default' => __( 'Search..', 'betterdocs' ),
                                                                    'priority' => 6
                                                                ),
                                                                'search_button_text' => apply_filters( 'betterdocs_search_button_text', array(
                                                                    'name' => 'search_button_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Search Button Text', 'betterdocs' ),
                                                                    'priority' => 7,
                                                                    'default' => __( 'Search', 'betterdocs' )
                                                                ) ),
                                                                'search_not_found_text' => array(
                                                                    'name' => 'search_not_found_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Search Not Found Text', 'betterdocs' ),
                                                                    'default' => 'Sorry, no docs were found.',
                                                                    'priority' => 8
                                                                ),
                                                                'search_result_image' => array(
                                                                    'name' => 'search_result_image',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Search Result Image', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 9
                                                                ),
                                                                'kb_based_search' => apply_filters( 'betterdocs_kb_based_search_settings', array(
                                                                    'name' => 'kb_based_search',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'KB Based Search', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 10,
                                                                    'is_pro' => true,
                                                                    'rules' => Rules::is( 'multiple_kb', true )
                                                                ) ),
                                                                'search_modal_search_type' => array(
                                                                    'name' => 'search_modal_search_type',
                                                                    'type' => 'select',
                                                                    'label' => __( 'Search Modal Source', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Choose the content type to be shown in the search modal. <br> <b>Note:</b> This is only applicable on Modal Search.', 'betterdocs' ),
                                                                    'default' => 'all',
                                                                    'options' => $this->normalize_options( array(
                                                                        'all' => __( 'Both (Docs + FAQs)', 'betterdocs' ),
                                                                        'docs' => __( 'Docs only', 'betterdocs' ),
                                                                        'faq' => __( 'FAQs only', 'betterdocs' )
                                                                    ) ),
                                                                    'priority' => 11
                                                                )
                                                            )
                                                        ),
                                                        'layout_documentation_page_order_by' => array(
                                                            'id' => 'layout_documentation_page_order_by',
                                                            'name' => 'layout_documentation_page_order_by',
                                                            'type' => 'section',
                                                            'label' => __( 'Order By', 'betterdocs' ),
                                                            'priority' => 3,
                                                            'fields' => array(
                                                                'terms_orderby' => array(
                                                                    'name' => 'terms_orderby',
                                                                    'type' => 'select',
                                                                    'label' => __( 'Terms Order By', 'betterdocs' ),
                                                                    'default' => 'betterdocs_order',
                                                                    'options' => $this->normalize_options( apply_filters( 'betterdocs_terms_orderby_options', array(
                                                                        'none' => __( 'No order', 'betterdocs' ),
                                                                        'name' => __( 'Name', 'betterdocs' ),
                                                                        'slug' => __( 'Slug', 'betterdocs' ),
                                                                        'term_group' => __( 'Term Group', 'betterdocs' ),
                                                                        'term_id' => __( 'Term ID', 'betterdocs' ),
                                                                        'id' => __( 'ID', 'betterdocs' ),
                                                                        'description' => __( 'Description', 'betterdocs' ),
                                                                        'parent' => __( 'Parent', 'betterdocs' ),
                                                                        'betterdocs_order' => __( 'BetterDocs Order', 'betterdocs' )
                                                                    ) ) ),
                                                                    'priority' => 1
                                                                ),
                                                                'terms_order' => array(
                                                                    'name' => 'terms_order',
                                                                    'type' => 'select',
                                                                    'label' => __( 'Terms Order', 'betterdocs' ),
                                                                    'default' => 'ASC',
                                                                    'options' => $this->normalize_options( array(
                                                                        'ASC' => 'Ascending',
                                                                        'DESC' => 'Descending'
                                                                    ) ),
                                                                    'priority' => 3,
                                                                    'rules' => Rules::includes( 'terms_orderby', 'betterdocs_order', true )
                                                                ),
                                                                'alphabetically_order_post' => array(
                                                                    'name' => 'alphabetically_order_post',
                                                                    'type' => 'select',
                                                                    'label' => __( 'Docs Order By', 'betterdocs' ),
                                                                    'default' => 'betterdocs_order',
                                                                    'options' => $this->normalize_options( array(
                                                                        'none' => __( 'No order', 'betterdocs' ),
                                                                        'ID' => __( 'Docs ID', 'betterdocs' ),
                                                                        'author' => __( 'Docs Author', 'betterdocs' ),
                                                                        'title' => __( 'Title', 'betterdocs' ),
                                                                        'date' => __( 'Date', 'betterdocs' ),
                                                                        'modified' => __( 'Last Modified Date', 'betterdocs' ),
                                                                        'parent' => __( 'Parent Id', 'betterdocs' ),
                                                                        'rand' => __( 'Random', 'betterdocs' ),
                                                                        'comment_count' => __( 'Comment Count', 'betterdocs' ),
                                                                        'menu_order' => __( 'Menu Order', 'betterdocs' ),
                                                                        'betterdocs_order' => __( 'BetterDocs Order', 'betterdocs' )
                                                                    ) ),
                                                                    'priority' => 4
                                                                ),
                                                                'docs_order' => array(
                                                                    'name' => 'docs_order',
                                                                    'type' => 'select',
                                                                    'label' => __( 'Docs Order', 'betterdocs' ),
                                                                    'default' => 'ASC',
                                                                    'options' => $this->normalize_options( array(
                                                                        'ASC' => 'Ascending',
                                                                        'DESC' => 'Descending'
                                                                    ) ),
                                                                    'priority' => 5,
                                                                    'rules' => Rules::includes( 'alphabetically_order_post', 'betterdocs_order', true )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        ),
                                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- legacy public filter name for single-doc setting section, retained for back-compat.
                                        'layout_single_doc' => apply_filters( 'single_doc_setting_section', array(
                                            'id' => 'layout_single_doc',
                                            'name' => 'layout_single_doc',
                                            'type' => 'section',
                                            'label' => __( 'Single Doc', 'betterdocs' ),
                                            'priority' => 2,
                                            'fields' => array(
                                                'tab-nested-layout-2' => array(
                                                    'id' => 'tab-nested-layout-2',
                                                    'name' => 'tab_nested_layout_2',
                                                    'label' => __( 'Single Doc', 'betterdocs' ),
                                                    'classes' => 'tab-nested-layout',
                                                    'type' => 'tab',
                                                    'active' => 'layout_single_doc_general',
                                                    'completionTrack' => true,
                                                    'sidebar' => false,
                                                    'save' => false,
                                                    'title' => false,
                                                    'config' => array(
                                                        'active' => 'layout_single_doc_general',
                                                        'sidebar' => false,
                                                        'title' => false
                                                    ),
                                                    'submit' => array(
                                                        'show' => false
                                                    ),
                                                    'step' => array(
                                                        'show' => false
                                                    ),
                                                    'priority' => 20,
                                                    'fields' => array(
                                                        'layout_single_doc_general' => array(
                                                            'id' => 'layout_single_doc_general',
                                                            'name' => 'layout_single_doc_general',
                                                            'type' => 'section',
                                                            'label' => __( 'General', 'betterdocs' ),
                                                            'priority' => 5,
                                                            'fields' => array(
                                                                'enable_post_title' => array(
                                                                    'name' => 'enable_post_title',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Doc Title', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 1
                                                                ),
                                                                'enable_sidebar_cat_list' => array(
                                                                    'name' => 'enable_sidebar_cat_list',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Sidebar Category List', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 2
                                                                ),
                                                                'mobile_sidebar_icon' => array(
                                                                    'name' => 'mobile_sidebar_icon',
                                                                    'type' => 'media',
                                                                    'value' => '',
                                                                    'label' => __( 'Responsive Sidebar Button Icon', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Upload the icon used to open the category sidebar on responsive layouts.', 'betterdocs' ),
                                                                    'priority' => 2,
                                                                    'rules' => Rules::is( 'enable_sidebar_cat_list', true )
                                                                ),
                                                                'enable_print_icon' => array(
                                                                    'name' => 'enable_print_icon',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Print Icon', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 3
                                                                ),
                                                                'print_enable_logo' => array(
                                                                    'name' => 'print_enable_logo',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Logo on Printed Doc', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Show a logo at the top of the printed / PDF page', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 0,
                                                                    'priority' => 4
                                                                ),
                                                                'print_logo' => array(
                                                                    'name' => 'print_logo',
                                                                    'type' => 'media',
                                                                    'value' => '',
                                                                    'label' => __( 'Print Logo', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Leave empty to use your site logo, or the site icon when no site logo is set', 'betterdocs' ),
                                                                    'priority' => 5,
                                                                    'rules' => Rules::is( 'print_enable_logo', true )
                                                                ),
                                                                'print_enable_footer' => array(
                                                                    'name' => 'print_enable_footer',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Footer on Printed Doc', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Show a footer on every page of the printed / PDF document', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 0,
                                                                    'priority' => 6
                                                                ),
                                                                'print_footer_text' => array(
                                                                    'name' => 'print_footer_text',
                                                                    'type' => 'textarea',
                                                                    'label' => __( 'Print Footer Text', 'betterdocs' ),
                                                                    'label_subtitle' => __( 'Leave empty to use the site name and current year', 'betterdocs' ),
                                                                    'default' => '',
                                                                    'priority' => 7,
                                                                    'rules' => Rules::is( 'print_enable_footer', true )
                                                                ),
                                                                'enable_tags' => array(
                                                                    'name' => 'enable_tags',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Tags', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 8
                                                                ),
                                                                'show_last_update_time' => array(
                                                                    'name' => 'show_last_update_time',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Last Update Time', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 9
                                                                ),
                                                                'enable_navigation' => array(
                                                                    'name' => 'enable_navigation',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Navigation', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 10
                                                                ),
                                                                'enable_comment' => array(
                                                                    'name' => 'enable_comment',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Comment', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 11
                                                                ),
                                                                'enable_credit' => array(
                                                                    'name' => 'enable_credit',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Show Powered by BetterDocs', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 12
                                                                ),
                                                                'reaction_feedback_text' => array(
                                                                    'name' => 'reaction_feedback_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Reaction Feedback Text', 'betterdocs' ),
                                                                    'default' => __( 'Thanks for your feedback.', 'betterdocs' ),
                                                                    'priority' => 13
                                                                ),
                                                                'enable_estimated_reading_time' => array(
                                                                    'name' => 'enable_estimated_reading_time',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Estimated Reading Time', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 0,
                                                                    'priority' => 14
                                                                ),
                                                                'estimated_reading_time_title' => array(
                                                                    'name' => 'estimated_reading_time_title',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Estimated Reading Time Title', 'betterdocs' ),
                                                                    'default' => '',
                                                                    'priority' => 15,
                                                                    'rules' => Rules::is( 'enable_estimated_reading_time', true )
                                                                ),
                                                                'estimated_reading_time_text' => array(
                                                                    'name' => 'estimated_reading_time_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Estimated Reading Time Text', 'betterdocs' ),
                                                                    'default' => __( 'min read', 'betterdocs' ),
                                                                    'priority' => 16,
                                                                    'rules' => Rules::is( 'enable_estimated_reading_time', true )
                                                                ),
                                                                'singular_estimated_reading_time_text' => array(
                                                                    'name' => 'singular_estimated_reading_time_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Estimated Reading Time Text Singular', 'betterdocs' ),
                                                                    'default' => __( 'min read', 'betterdocs' ),
                                                                    'priority' => 17,
                                                                    'rules' => Rules::is( 'enable_estimated_reading_time', true )
                                                                )
                                                            )
                                                        ),
                                                        'layout_single_doc_TOC' => array(
                                                            'id' => 'layout_single_doc_TOC',
                                                            'name' => 'layout_single_doc_TOC',
                                                            'type' => 'section',
                                                            'label' => __( 'TOC', 'betterdocs' ),
                                                            'priority' => 5,
                                                            'fields' => array(
                                                                'enable_toc' => array(
                                                                    'name' => 'enable_toc',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Table of Contents', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 1
                                                                ),
                                                                'toc_title' => array(
                                                                    'name' => 'toc_title',
                                                                    'type' => 'text',
                                                                    'label' => __( 'TOC Title', 'betterdocs' ),
                                                                    'default' => __( 'Table of Contents', 'betterdocs' ),
                                                                    'priority' => 2,
                                                                    'rules' => Rules::is( 'enable_toc', true )

                                                                ),
                                                                'toc_hierarchy' => array(
                                                                    'name' => 'toc_hierarchy',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'TOC Hierarchy', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 3,
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                ),
                                                                'toc_list_number' => array(
                                                                    'name' => 'toc_list_number',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'TOC List Number', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 4,
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                ),
                                                                'toc_dynamic_title' => array(
                                                                    'name' => 'toc_dynamic_title',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Show TOC Title in Anchor Links', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 0,
                                                                    'priority' => 5,
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                ),
                                                                'enable_sticky_toc' => array(
                                                                    'name' => 'enable_sticky_toc',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Sticky TOC', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 6,
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                ),
                                                                'collapsible_toc_mobile' => array(
                                                                    'name' => 'collapsible_toc_mobile',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Collapsible TOC on small devices', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => '',
                                                                    'priority' => 7,
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                ),
                                                                'title_link_ctc' => array(
                                                                    'name' => 'title_link_ctc',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Title Link Copy To Clipboard', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 8
                                                                ),
                                                                'supported_heading_tag' => array(
                                                                    'name' => 'supported_heading_tag',
                                                                    'label' => __( 'TOC Supported Heading Tag', 'betterdocs' ),
                                                                    'type' => 'checkbox-select',
                                                                    'multiple' => true,
                                                                    'priority' => 10,
                                                                    'default' => array(
                                                                        '1',
                                                                        '2',
                                                                        '3',
                                                                        '4',
                                                                        '5',
                                                                        '6'
                                                                    ),
                                                                    'options' => $this->normalize_options( array(
                                                                        '1' => 'H1',
                                                                        '2' => 'H2',
                                                                        '3' => 'H3',
                                                                        '4' => 'H4',
                                                                        '5' => 'H5',
                                                                        '6' => 'H6'
                                                                    ) ),
                                                                    'priority' => 9,
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                ),
                                                                'sticky_toc_offset' => array(
                                                                    'name' => 'sticky_toc_offset',
                                                                    'type' => 'number',
                                                                    'label' => __( 'Content Offset', 'betterdocs' ),
                                                                    'default' => 100,
                                                                    'priority' => 10,
                                                                    'label_subtitle' => __( 'content offset from top on scroll.', 'betterdocs' ),
                                                                    'rules' => Rules::is( 'enable_toc', true )
                                                                )
                                                            )
                                                        ),
                                                        'layout_single_doc_breadcrumb' => array(
                                                            'id' => 'layout_single_doc_breadcrumb',
                                                            'name' => 'layout_single_doc_breadcrumb',
                                                            'type' => 'section',
                                                            'label' => __( 'Breadcrumb', 'betterdocs' ),
                                                            'priority' => 5,
                                                            'fields' => array(
                                                                'enable_breadcrumb' => array(
                                                                    'name' => 'enable_breadcrumb',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Breadcrumb', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 1
                                                                ),
                                                                'enable_breadcrumb_home_text' => array(
                                                                    'name' => 'enable_breadcrumb_home_text',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Enable Breadcrumb Home Text', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => true,
                                                                    'priority' => 2,
                                                                    'rules' => Rules::is( 'enable_breadcrumb', true )
                                                                ),
                                                                'breadcrumb_home_text' => array(
                                                                    'name' => 'breadcrumb_home_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Breadcrumb Home Text', 'betterdocs' ),
                                                                    'default' => __( 'Home', 'betterdocs' ),
                                                                    'priority' => 3,
                                                                    'rules' => Rules::logicalRule(
                                                                        array(
                                                                            Rules::is( 'enable_breadcrumb', true ),
                                                                            Rules::is( 'enable_breadcrumb_home_text', true )
                                                                        ),
                                                                        'and'
                                                                    )
                                                                ),
                                                                'breadcrumb_home_url' => array(
                                                                    'name' => 'breadcrumb_home_url',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Breadcrumb Home URL', 'betterdocs' ),
                                                                    'priority' => 4,
                                                                    'default' => get_home_url(),
                                                                    'rules' => Rules::is( 'enable_breadcrumb', true )
                                                                ),
                                                                'enable_breadcrumb_category' => array(
                                                                    'name' => 'enable_breadcrumb_category',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Category on Breadcrumb', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 5,
                                                                    'rules' => Rules::is( 'enable_breadcrumb', true )
                                                                ),
                                                                'enable_breadcrumb_title' => array(
                                                                    'name' => 'enable_breadcrumb_title',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Title on Breadcrumb', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 6,
                                                                    'rules' => Rules::is( 'enable_breadcrumb', true )
                                                                )
                                                            )
                                                        ),
                                                        'layout_single_doc_email_feedback' => array(
                                                            'id' => 'layout_single_doc_email_feedback',
                                                            'name' => 'layout_single_doc_email_feedback',
                                                            'type' => 'section',
                                                            'label' => __( 'Email Feedback', 'betterdocs' ),
                                                            'priority' => 5,
                                                            'fields' => array(
                                                                'email_feedback' => array(
                                                                    'name' => 'email_feedback',
                                                                    'type' => 'toggle',
                                                                    'label' => __( 'Email Feedback', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => 1,
                                                                    'priority' => 1
                                                                ),
                                                                'feedback_link_text' => array(
                                                                    'name' => 'feedback_link_text',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Feedback Link Text', 'betterdocs' ),
                                                                    'default' => __( 'Still stuck? How can we help?', 'betterdocs' ),
                                                                    'priority' => 2,
                                                                    'rules' => Rules::is( 'email_feedback', true )
                                                                ),
                                                                'feedback_form_title' => array(
                                                                    'name' => 'feedback_form_title',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Feedback Form Title', 'betterdocs' ),
                                                                    'default' => __( 'Still stuck? How can we help?', 'betterdocs' ),
                                                                    'priority' => 3,
                                                                    'rules' => Rules::is( 'email_feedback', true )
                                                                ),
                                                                'email_address' => array(
                                                                    'name' => 'email_address',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Email Address', 'betterdocs' ),
                                                                    'default' => get_option( 'admin_email' ),
                                                                    'priority' => 4,
                                                                    'label_subtitle' => __( 'The email address where the Feedback form will be sent', 'betterdocs' ),
                                                                    'rules' => Rules::is( 'email_feedback', true )
                                                                ),
                                                                'feedback_url' => array(
                                                                    'name' => 'feedback_url',
                                                                    'type' => 'text',
                                                                    'label' => __( 'Feedback URL', 'betterdocs' ),
                                                                    'default' => '',
                                                                    'priority' => 5,
                                                                    'rules' => Rules::is( 'email_feedback', true )
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                            'id' => 'layout_single_doc_attachments',
                                                            'name' => 'layout_single_doc_attachments',
                                                            'type' => 'section',
                                                            'label' => __( 'Attachments', 'betterdocs' ),
                                                            'priority' => 6,
                                                            'fields' => apply_filters( 'betterdocs_single_doc_attachments', array(
                                                                'show_attachment' => array(
                                                                    'name' => 'show_attachment',
                                                                    'type' => 'toggle',
                                                                    'is_pro' => true,
                                                                    'priority' => 1,
                                                                    'label' => __( 'Show Attachment', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => false
                                                                )
                                                            ) )
                                                        ),
                                                        array(
                                                            'id' => 'layout_single_doc_related_docs',
                                                            'name' => 'layout_single_doc_related_docs',
                                                            'type' => 'section',
                                                            'label' => __( 'Related Docs', 'betterdocs' ),
                                                            'priority' => 7,
                                                            'fields' => apply_filters( 'betterdocs_single_doc_related_docs', array(
                                                                'show_related_docs' => array(
                                                                    'name' => 'show_related_docs',
                                                                    'type' => 'toggle',
                                                                    'is_pro' => true,
                                                                    'priority' => 1,
                                                                    'label' => __( 'Show Related Docs', 'betterdocs' ),
                                                                    'enable_disable_text_active' => true,
                                                                    'default' => false
                                                                )
                                                            ) )
                                                        )
                                                    )
                                                )
                                            )
                                        ) ),
                                        'layout_archive_page' => array(
                                            'id' => 'layout_archive_page',
                                            'name' => 'layout_archive_page',
                                            'type' => 'section',
                                            'label' => __( 'Archive Page', 'betterdocs' ),
                                            'priority' => 3,
                                            'fields' => array(
                                                'enable_archive_sidebar' => array(
                                                    'name' => 'enable_archive_sidebar',
                                                    'type' => 'toggle',
                                                    'label' => __( 'Sidebar Category List', 'betterdocs' ),
                                                    'enable_disable_text_active' => true,
                                                    'default' => 1,
                                                    'priority' => 31
                                                ),
                                                'archive_nested_subcategory' => array(
                                                    'name' => 'archive_nested_subcategory',
                                                    'type' => 'toggle',
                                                    'label' => __( 'Nested Subcategory', 'betterdocs' ),
                                                    'enable_disable_text_active' => true,
                                                    'default' => 1,
                                                    'priority' => 32
                                                ),
                                                'archive_enable_pagination' => array(
                                                    'name' => 'archive_enable_pagination',
                                                    'type' => 'toggle',
                                                    'label' => __( 'Enable Pagination', 'betterdocs' ),
                                                    'enable_disable_text_active' => true,
                                                    'default' => false,
                                                    'priority' => 33
                                                )
                                            )
                                        )
                                    )
                                ) )
                            )
                        )
                    )
                ) ),
                'tab-design' => apply_filters( 'betterdocs_settings_tab_design', array(
                    'id' => 'tab-design',
                    'label' => __( 'Design', 'betterdocs' ),
                    'priority' => 30,
                    'fields' => array(
                        'title-design' => array(
                            'name' => 'title-design-tab',
                            'type' => 'section',
                            'label' => __( 'Design', 'betterdocs' ),
                            'priority' => 30,
                            'fields' => $this->design_tab()
                        )
                    )
                ) ),
                'tab-shortcodes' => apply_filters( 'betterdocs_settings_tab_shortcodes', array(
                    'label' => __( 'Shortcodes', 'betterdocs' ),
                    'id' => 'tab-shortcodes',
                    'classes' => 'tab-shortcodes',
                    'priority' => 40,
                    'fields' => array(
                        'title-shortcodes' => array(
                            'name' => 'title-shortcodes-tab',
                            'type' => 'section',
                            'label' => __( 'Shortcodes', 'betterdocs' ),
                            'priority' => 40,
                            'searchable' => true,
                            'searchPlaceholder' => __( 'Search for shortcode', 'betterdocs' ),
                            'searchNotFoundMessage' => '<img src="' . betterdocs()->assets->icon( 'not-found.svg', true ) . '"/><p>' . __( 'No Shortcodes Found with these keywords', 'betterdocs' ) . '</p>',
                            'fields' => apply_filters( 'betterdocs_shortcode_fields', array(
                                'search_form' => array(
                                    'name' => 'search_form',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'Search Form', 'betterdocs' ),
                                    'default' => '[betterdocs_search_form]',
                                    'readOnly' => true,
                                    'priority' => 1,
                                    'description' => __( '[betterdocs_search_form placeholder="Search..." heading="Heading" subheading="Subheading" category_search="true" search_button="true" popular_search="true"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'feedback_form' => array(
                                    'name' => 'feedback_form',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'Feedback Form', 'betterdocs' ),
                                    'default' => '[betterdocs_feedback_form]',
                                    'readOnly' => true,
                                    'priority' => 2,
                                    'description' => __( '[betterdocs_feedback_form button_text="Send"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'category_grid' => array(
                                    'name' => 'category_grid',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'Category Grid- Layout 1', 'betterdocs' ),
                                    'default' => '[betterdocs_category_grid]',
                                    'readOnly' => true,
                                    'priority' => 3,
                                    'description' => __( '[betterdocs_category_grid show_count="true" show_icon="true" masonry="true" column="3" posts_per_page="5" nested_subcategory="true" terms="term_ID, term_ID" terms_orderby="" terms_order="" multiple_knowledge_base="" kb_slug="" title_tag="h2" orderby="" order="" ]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'category_box' => array(
                                    'name' => 'category_box',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'Category Box- Layout 2', 'betterdocs' ),
                                    'default' => '[betterdocs_category_box]',
                                    'readOnly' => true,
                                    'priority' => 4,
                                    'description' => __( '[betterdocs_category_box orderby="" column="" nested_subcategory="" terms="" terms_orderby="" show_icon="" kb_slug="" title_tag="h2" multiple_knowledge_base="false" border_bottom="false"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'category_list' => array(
                                    'name' => 'category_list',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'Category List', 'betterdocs' ),
                                    'default' => '[betterdocs_category_list]',
                                    'readOnly' => true,
                                    'priority' => 5,
                                    'description' => __( '[betterdocs_category_list orderby="" order="" posts_per_page="" nested_subcategory="" terms="" terms_orderby="" terms_order="" kb_slug="" multiple_knowledge_base="false" title_tag="h2"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'faq_modern_layout' => array(
                                    'name' => 'faq_modern_layout',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'FAQ Layout - 1', 'betterdocs' ),
                                    'default' => '[betterdocs_faq_list_modern]',
                                    'readOnly' => true,
                                    'priority' => 13,
                                    'description' => __( '[betterdocs_faq_list_modern groups="group_id" class="" group_exclude="group_id" faq_heading="Frequently Asked Questions"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'faq_classic_layout' => array(
                                    'name' => 'faq_classic_layout',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'FAQ Layout - 2', 'betterdocs' ),
                                    'default' => '[betterdocs_faq_list_classic]',
                                    'readOnly' => true,
                                    'priority' => 14,
                                    'description' => __( '[betterdocs_faq_list_classic groups="group_id" class="" group_exclude="group_id" faq_heading="Frequently Asked Questions"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'betterdocs_faq_list_layout_3' => array(
                                    'name' => 'betterdocs_faq_list_layout_3',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'FAQ Layout - 3', 'betterdocs' ),
                                    'default' => '[betterdocs_faq_list_layout_3 class="faq-doc betterdocs-faq-layout-3"]',
                                    'readOnly' => true,
                                    'priority' => 15,
                                    'description' => __( '[betterdocs_faq_list_layout_3 class="faq-doc betterdocs-faq-layout-3" groups="group_id" class="" group_exclude="group_id" faq_heading="Frequently Asked Questions"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                ),
                                'betterdocs_faq_tab' => array(
                                    'name' => 'betterdocs_faq_tab',
                                    'type' => 'copy-to-clipboard',
                                    'label' => __( 'FAQ Layout - 4', 'betterdocs' ),
                                    'default' => '[betterdocs_faq_tab class="faq-doc betterdocs-faq-layout-4"]',
                                    'readOnly' => true,
                                    'priority' => 16,
                                    'description' => __( '[betterdocs_faq_tab class="faq-doc betterdocs-faq-layout-4" groups="group_id" group_exclude="group_id" faq_heading="Frequently Asked Questions"]', 'betterdocs' ),
                                    'descriptionLabel' => __( 'Example with parameters:', 'betterdocs' ),
                                    'descriptionCopyable' => true
                                )
                            ) )
                        )
                    )
                ) ),
                'tab-advance-settings' => apply_filters( 'betterdocs_settings_tab_advance', array(
                    'id' => 'tab-advance-settings',
                    'label' => __( 'Access & Restrictions', 'betterdocs' ),
                    'classes' => 'tab-layout',
                    'priority' => 50,
                    'fields' => array(
                        'title_advance_settings' => array(
                            'name' => 'title-advance-settings-tab',
                            'type' => 'section',
                            'label' => __( 'Access & Restrictions', 'betterdocs' ),
                            'priority' => 50,
                            'fields' => array(
                                'tab-advanced-settings' => array(
                                    'id' => 'tab-advanced-settings',
                                    'name' => 'tab-advanced-settings',
                                    'label' => __( 'Access & Restrictions', 'betterdocs' ),
                                    'classes' => 'tab-layout',
                                    'type' => 'tab',
                                    'active' => 'global_role_management',
                                    'completionTrack' => true,
                                    'sidebar' => false,
                                    'save' => false,
                                    'title' => false,
                                    'config' => array(
                                        'active' => '',
                                        'sidebar' => false,
                                        'title' => false
                                    ),
                                    'submit' => array(
                                        'show' => false
                                    ),
                                    'step' => array(
                                        'show' => false
                                    ),
                                    'priority' => 50,
                                    'fields' => array(
                                        'global_role_management' => array(
                                            'id' => 'global_role_management',
                                            'name' => 'global_role_management',
                                            'type' => 'section',
                                            'label' => __( 'Global Role Management', 'betterdocs' ),
                                            'priority' => 1,
                                            'fields' => array(
                                                'article_roles' => array(
                                                    'name' => 'article_roles',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Who Can Write Docs?', 'betterdocs' ),
                                                    'priority' => 1,
                                                    'multiple' => true,
                                                    'search' => true,
                                                    'is_pro' => true,
                                                    'default' => array( 'administrator' ),
                                                    'options' => $wp_roles
                                                ),
                                                'settings_roles' => array(
                                                    'name' => 'settings_roles',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Who Can Edit Settings?', 'betterdocs' ),
                                                    'priority' => 2,
                                                    'multiple' => true,
                                                    'is_pro' => true,
                                                    'search' => true,
                                                    'default' => array( 'administrator' ),
                                                    'options' => $wp_roles
                                                ),
                                                'analytics_roles' => array(
                                                    'name' => 'analytics_roles',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Who Can Check Analytics?', 'betterdocs' ),
                                                    'priority' => 3,
                                                    'multiple' => true,
                                                    'is_pro' => true,
                                                    'search' => true,
                                                    'default' => array( 'administrator' ),
                                                    'options' => $wp_roles
                                                ),
                                                'faq_roles' => array(
                                                    'name' => 'faq_roles',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Who Can Check FAQ Builder?', 'betterdocs' ),
                                                    'priority' => 4,
                                                    'multiple' => true,
                                                    'is_pro' => true,
                                                    'search' => true,
                                                    'default' => array( 'administrator' ),
                                                    'options' => $wp_roles
                                                )

                                            )
                                        ),
                                        'internal_knowledge_base' => array(
                                            'id' => 'internal_knowledge_base',
                                            'name' => 'internal_knowledge_base',
                                            'type' => 'section',
                                            'label' => __( 'Internal Knowledge Base', 'betterdocs' ),
                                            'priority' => 1,
                                            'fields' => apply_filters( 'betterdocs_settings_content_restriction_fields', array(
                                                'enable_content_restriction' => array(
                                                    'name' => 'enable_content_restriction',
                                                    'type' => 'toggle',
                                                    'is_pro' => true,
                                                    'priority' => 5,
                                                    'label' => __( 'Internal Knowledge Base', 'betterdocs' ),
                                                    'enable_disable_text_active' => true,
                                                    'default' => array( 'all' )
                                                ),
                                                'internal_knowledge_base_type' => array(
                                                    'name' => 'internal_knowledge_base_type',
                                                    'type' => 'radio-card',
                                                    'label' => __( 'Choose a Rule Type', 'betterdocs' ),
                                                    'label_subtitle' => __( 'Choose a rule type: apply access globally (Basic), or configure detailed restrictions (Advanced) for docs, categories, and KBs.', 'betterdocs' ),
                                                    'priority' => 6,
                                                    'multiple' => true,
                                                    'search' => true,
                                                    'default' => array( 'all' ),
                                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                                    'options' => array(
                                                        array(
                                                            'label' => 'Basic',
                                                            'value' => 'basic'
                                                        ),
                                                        array(
                                                            'label' => 'Advanced',
                                                            'value' => 'advanced'
                                                        )
                                                    ),
                                                    'filterValue' => 'all',
                                                    'rules' => Rules::is( 'enable_content_restriction', true )
                                                ),
                                                'content_visibility' => array(
                                                    'name' => 'content_visibility',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Restrict Access to', 'betterdocs' ),
                                                    'label_subtitle' => __( 'Only selected User Roles will be able to view your Knowledge Base', 'betterdocs' ),
                                                    'is_pro' => true,
                                                    'priority' => 7,
                                                    'multiple' => true,
                                                    'search' => true,
                                                    'default' => array( 'all' ),
                                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                                    'options' => $roles_for_ikb,
                                                    'rules' => Rules::logicalRule(
                                                        array(
                                                            Rules::is( 'enable_content_restriction', true ),
                                                            Rules::is( 'internal_knowledge_base_type', 'basic' )
                                                        ),
                                                        'and'
                                                    ),
                                                    'filterValue' => 'all'
                                                ),
                                                'restrict_template' => array(
                                                    'name' => 'restrict_template',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Restriction on Docs', 'betterdocs' ),
                                                    'label_subtitle' => __( 'Selected Docs pages will be restricted', 'betterdocs' ),
                                                    'is_pro' => true,
                                                    'priority' => 8,
                                                    'multiple' => true,
                                                    'search' => true,
                                                    'default' => array( 'all' ),
                                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                                    'options' => $this->get_texanomy(),
                                                    'rules' => Rules::logicalRule(
                                                        array(
                                                            Rules::is( 'enable_content_restriction', true ),
                                                            Rules::is( 'internal_knowledge_base_type', 'basic' )
                                                        ),
                                                        'and'
                                                    ),
                                                    'filterValue' => 'all'
                                                ),
                                                'restrict_category' => array(
                                                    'name' => 'restrict_category',
                                                    'type' => 'checkbox-select',
                                                    'label' => __( 'Restriction on Docs Categories', 'betterdocs' ),
                                                    'label_subtitle' => __( 'Selected Docs categories will be restricted', 'betterdocs' ),
                                                    'is_pro' => true,
                                                    'priority' => 9,
                                                    'multiple' => true,
                                                    'search' => true,
                                                    'default' => array( 'all' ),
                                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                                    'options' => $this->get_terms( 'doc_category' ),
                                                    'rules' => Rules::logicalRule(
                                                        array(
                                                            Rules::is( 'enable_content_restriction', true ),
                                                            Rules::is( 'internal_knowledge_base_type', 'basic' )
                                                        ),
                                                        'and'
                                                    ),
                                                    'filterValue' => 'all'
                                                ),
                                                'restricted_redirect_url' => array(
                                                    'name' => 'restricted_redirect_url',
                                                    'type' => 'text',
                                                    'label' => __( 'Redirect URL', 'betterdocs' ),
                                                    'label_subtitle' => __( 'Set a custom URL to redirect users without permissions when they try to access internal knowledge base. By default, restricted content will redirect to the "404 not found" page', 'betterdocs' ),
                                                    'default' => '',
                                                    'placeholder' => 'https://',
                                                    'is_pro' => true,
                                                    'priority' => 20,
                                                    'rules' => Rules::logicalRule(
                                                        array(
                                                            Rules::is( 'enable_content_restriction', true )
                                                        ),
                                                        'and'
                                                    )
                                                )
                                            ) )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ) ),
                'tab-email-reporting' => apply_filters( 'betterdocs_settings_tab_email_reporting', array(
                    'id' => 'tab-email-reporting',
                    'label' => __( 'Email Reporting', 'betterdocs' ),
                    'priority' => 60,
                    'fields' => array(
                        'title-email-reporting' => array(
                            'name' => 'title-email-reporting-tab',
                            'type' => 'section',
                            'label' => __( 'Email Reporting', 'betterdocs' ),
                            'priority' => 60,
                            'fields' => array(
                                'enable_reporting' => array(
                                    'name' => 'enable_reporting',
                                    'label' => __( 'Email Reporting', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'type' => 'toggle',
                                    'priority' => 1,
                                    'default' => 0
                                ),
                                'reporting_frequency' => apply_filters( 'betterdocs_reporting_frequency_settings', array(
                                    'name' => 'reporting_frequency',
                                    'type' => 'select',
                                    'label' => __( 'Reporting Frequency', 'betterdocs' ),
                                    'default' => 'betterdocs_weekly',
                                    'priority' => 2,
                                    'is_pro' => true,
                                    'options' => $this->normalize_options( array(
                                        'betterdocs_daily' => __( 'Once Daily', 'betterdocs' ),
                                        'betterdocs_weekly' => __( 'Once Weekly', 'betterdocs' ),
                                        'betterdocs_monthly' => __( 'Once Monthly', 'betterdocs' )
                                    ) ),
                                    'rules' => Rules::is( 'enable_reporting', true )
                                ) ),
                                'reporting_day' => array(
                                    'name' => 'reporting_day',
                                    'type' => 'select',
                                    'label' => __( 'Reporting Day', 'betterdocs' ),
                                    'default' => 'monday',
                                    'rules' => Rules::logicalRule( array(
                                        Rules::is( 'enable_reporting', true ),
                                        Rules::is( 'reporting_frequency', 'betterdocs_weekly' )
                                    ), 'and' ),
                                    'priority' => 3,
                                    'options' => $this->normalize_options( array(
                                        'sunday' => __( 'Sunday', 'betterdocs' ),
                                        'monday' => __( 'Monday', 'betterdocs' ),
                                        'tuesday' => __( 'Tuesday', 'betterdocs' ),
                                        'wednesday' => __( 'Wednesday', 'betterdocs' ),
                                        'thursday' => __( 'Thursday', 'betterdocs' ),
                                        'friday' => __( 'Friday', 'betterdocs' ),
                                        'saturday' => __( 'Saturday', 'betterdocs' )
                                    ) ),
                                    'label_subtitle' => __( 'This is only applicable for the “Weekly” report', 'betterdocs' )
                                ),
                                'select_reporting_data' => apply_filters( 'betterdocs_select_reporting_data_settings', array(
                                    'name' => 'select_reporting_data',
                                    'type' => 'checkbox-select',
                                    'label' => __( 'Select Reporting Data', 'betterdocs' ),
                                    'priority' => 4,
                                    'multiple' => true,
                                    'options' => $this->normalize_options( array(
                                        'overview' => 'Overview',
                                        'top-docs' => 'Top Docs',
                                        'most-search' => 'Most Searched Keywords'
                                    ) ),
                                    'default' => array( 'overview', 'top-docs', 'most-search' ),
                                    'is_pro' => true,
                                    'rules' => Rules::is( 'enable_reporting', true )
                                ) ),
                                'reporting_email' => array(
                                    'name' => 'reporting_email',
                                    'type' => 'text',
                                    'label' => __( 'Reporting Email', 'betterdocs' ),
                                    'default' => get_option( 'admin_email' ),
                                    'priority' => 5,
                                    'rules' => Rules::is( 'enable_reporting', true )
                                ),
                                'reporting_subject' => apply_filters( 'betterdocs_reporting_subject_settings', array(
                                    'name' => 'reporting_subject',
                                    'type' => 'textarea',
                                    'label' => __( 'Reporting Email Subject', 'betterdocs' ),
                                    'default' => wp_sprintf(  // Translators: %s is replaced with the website name.
                                        __( 'Your Documentation Performance of %s Website', 'betterdocs' ), get_bloginfo( 'name' ) ),
                                    'priority' => 6,
                                    'is_pro' => true,
                                    'rules' => Rules::is( 'enable_reporting', true )
                                ) ),
                                'test_report' => array(
                                    'name' => 'test_report',
                                    'label' => __( 'Reporting Test', 'betterdocs' ),
                                    'text' => __( 'Test Report', 'betterdocs' ),
                                    'type' => 'button',
                                    'priority' => 7,
                                    'rules' => Rules::is( 'enable_reporting', true ),
                                    'ajax' => array(
                                        'on' => 'click',
                                        'api' => '/betterdocs/v1/reporting-test',
                                        'data' => array(
                                            'enable_reporting' => '@enable_reporting',
                                            'select_reporting_data' => '@select_reporting_data',
                                            'reporting_subject' => '@reporting_subject',
                                            'reporting_email' => '@reporting_email',
                                            'reporting_day' => '@reporting_day',
                                            'reporting_frequency' => '@reporting_frequency'
                                        ),
                                        'swal' => array(
                                            'text' => __( 'Successfully Sent a Test Report in Your Email.', 'betterdocs' ),
                                            'icon' => 'success',
                                            'autoClose' => 2000
                                        )
                                    )
                                )
                            )
                        )
                    )
                ) ),
                'tab-git-sync' => apply_filters( 'betterdocs_settings_tab_github_integration', array(
                    'id' => 'tab-git-sync',
                    'label' => __( 'Git Sync', 'betterdocs' ),
                    'priority' => 60,
                    'fields' => array(
                        'title-git-sync' => array(
                            'name' => 'title-git-sync-tab',
                            'type' => 'section',
                            'label' => __( 'Git Sync Settings', 'betterdocs' ),
                            'priority' => 60,
                            'fields' => array(
                                'enable_git_integration' => array(
                                    'name' => 'enable_git_integration',
                                    'type' => 'toggle',
                                    'label' => __( 'Enable Git Sync', 'betterdocs' ),
                                    'enable_disable_text_active' => true,
                                    'default' => false,
                                    'priority' => 1,
                                    'is_pro' => true,
                                    'label_subtitle' => __( 'Enable bidirectional synchronization between BetterDocs and Git repositories', 'betterdocs' )
                                )
                            )
                        )
                    )
                ) ),
                'tab-instant-answer' => apply_filters( 'betterdocs_settings_tab_instant_answer', array(
                    'id' => 'tab-instant-answer',
                    'name' => 'tab-instant-answer',
                    'type' => 'section',
                    'label' => __( 'Instant Answer', 'betterdocs' ),
                    'save' => false,
                    'priority' => 70,
                    'fields' => array(
                        'title-instant-answer' => array(
                            'name' => 'title-instant-answer-tab',
                            'type' => 'section',
                            'label' => __( 'Instant Answer', 'betterdocs' ),
                            'priority' => 80,
                            'save' => false,
                            'showSubmit' => false,
                            'fields' => apply_filters( 'betterdocs_instant_answer_fields', array(
                                'enable_disable_wrapper' => array(
                                    'name' => 'enable_disable_wrapper',
                                    'type' => 'section',
                                    'priority' => 0,
                                    'save' => false,
                                    'fields' => array(
                                        'enable_disable' => array(
                                            'name' => 'enable_disable',
                                            'type' => 'toggle',
                                            'priority' => 100,
                                            'description' => __( 'Enable Instant Answer', 'betterdocs' ),
                                            'enable_disable_text_active' => false,
                                            'default' => false,
                                            'is_pro' => true
                                        )
                                    )
                                )
                            ) )
                        )
                    )
                ) ),
                'tab-betterdocs-ai' => array(
                    'id' => 'tab-betterdocs-ai',
                    'name' => 'tab-betterdocs-ai',
                    'type' => 'section',
                    'label' => __( 'AI Content Suite', 'betterdocs' ),
                    'priority' => 74,
                    'fields' => array(
                        'sections-betterdocs-ai' => array(
                            'name' => 'sections-betterdocs-ai',
                            'type' => 'section',
                            'label' => __( 'AI Content Suite', 'betterdocs' ),
                            'priority' => 10,
                            'fields' => array(
                                'all-betterdocs-ai' => array(
                                    'id' => 'all-tab-betterdocs-ai',
                                    'name' => 'all-tab-betterdocs-ai',
                                    'label' => __( 'AI Content Suite Settings', 'betterdocs' ),
                                    'classes' => 'tab-layout',
                                    'type' => 'tab',
                                    'active' => 'open-ai-settings',
                                    'completionTrack' => true,
                                    'sidebar' => false,
                                    'save' => false,
                                    'title' => false,
                                    'config' => array(
                                        'active' => 'open-ai-settings',
                                        'sidebar' => false,
                                        'title' => false
                                    ),
                                    'submit' => array(
                                        'show' => false
                                    ),
                                    'step' => array(
                                        'show' => false
                                    ),
                                    'priority' => 20,
                                    'fields' => apply_filters(
                                        'betterdocs_migration_tab_sections',
                                        array(
                                            'open-ai-settings' => array(
                                                'id' => 'open-ai-settings',
                                                'name' => 'open-ai-settings',
                                                'type' => 'section',
                                                'label' => __( 'AI Platform & API', 'betterdocs' ),
                                                'priority' => 1,
                                                'fields' => array(
                                                    'ai_platform' => array(
                                                        'name' => 'ai_platform',
                                                        'type' => 'select',
                                                        'label' => __( 'AI Platform', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Choose which AI platform powers Write with AI, AI Edit, the Doc Summarizer and Quality Score. The model list and API key field below adapt to your choice.', 'betterdocs' ),
                                                        'priority' => 1,
                                                        'multiple' => false,
                                                        'default' => 'openai',
                                                        // DeepSeek and OpenRouter are temporarily hidden from the
                                                        // dropdown. Filter the options here, NOT ModelRegistry::platforms() —
                                                        // sensitive_api_key_fields() loops the registry to mask each
                                                        // platform's key field, so trimming the registry would silently
                                                        // un-mask those keys. Re-enable later by dropping the array_diff_key.
                                                        'options' => GlobalFields::normalize_fields(
                                                            array_diff_key( ModelRegistry::platforms(), array_flip( array( 'deepseek', 'openrouter' ) ) )
                                                        )
                                                    ),
                                                    // OpenAI's key field keeps its original name so installs
                                                    // that already saved a Write with AI key keep it.
                                                    'ai_autowrite_api_key' => array(
                                                        'name' => 'ai_autowrite_api_key',
                                                        'type' => 'text',
                                                        'label' => __( 'OpenAI API Key', 'betterdocs' ),
                                                        'label_subtitle' => sprintf( /* translators: %s: documentation URL */ __( 'Check out this <a target="_blank" href="%s">documentation</a> to generate your OpenAI API key.', 'betterdocs' ), esc_url( 'https://betterdocs.co/docs/write-with-ai/' ) ),
                                                        'default' => '',
                                                        'priority' => 2,
                                                        'rules' => Rules::is( 'ai_platform', 'openai' )
                                                    ),
                                                    'ai_api_key_gemini' => array(
                                                        'name' => 'ai_api_key_gemini',
                                                        'type' => 'text',
                                                        'label' => __( 'Google Gemini API Key', 'betterdocs' ),
                                                        'label_subtitle' => sprintf( /* translators: %s: Google AI Studio URL */ __( 'Generate a key from <a target="_blank" href="%s">Google AI Studio</a>.', 'betterdocs' ), esc_url( 'https://aistudio.google.com/app/apikey' ) ),
                                                        'default' => '',
                                                        'priority' => 2,
                                                        'rules' => Rules::is( 'ai_platform', 'gemini' )
                                                    ),
                                                    'ai_api_key_claude' => array(
                                                        'name' => 'ai_api_key_claude',
                                                        'type' => 'text',
                                                        'label' => __( 'Anthropic Claude API Key', 'betterdocs' ),
                                                        'label_subtitle' => sprintf( /* translators: %s: Anthropic Console URL */ __( 'Generate a key from the <a target="_blank" href="%s">Anthropic Console</a>.', 'betterdocs' ), esc_url( 'https://console.anthropic.com/settings/keys' ) ),
                                                        'default' => '',
                                                        'priority' => 2,
                                                        'rules' => Rules::is( 'ai_platform', 'claude' )
                                                    ),
                                                    'ai_api_key_deepseek' => array(
                                                        'name' => 'ai_api_key_deepseek',
                                                        'type' => 'text',
                                                        'label' => __( 'DeepSeek API Key', 'betterdocs' ),
                                                        'label_subtitle' => sprintf( /* translators: %s: DeepSeek platform URL */ __( 'Generate a key from the <a target="_blank" href="%s">DeepSeek platform</a>.', 'betterdocs' ), esc_url( 'https://platform.deepseek.com/api_keys' ) ),
                                                        'default' => '',
                                                        'priority' => 2,
                                                        'rules' => Rules::is( 'ai_platform', 'deepseek' )
                                                    ),
                                                    'ai_api_key_openrouter' => array(
                                                        'name' => 'ai_api_key_openrouter',
                                                        'type' => 'text',
                                                        'label' => __( 'OpenRouter API Key', 'betterdocs' ),
                                                        'label_subtitle' => sprintf( /* translators: %s: OpenRouter keys URL */ __( 'Generate a key from <a target="_blank" href="%s">OpenRouter</a>.', 'betterdocs' ), esc_url( 'https://openrouter.ai/keys' ) ),
                                                        'default' => '',
                                                        'priority' => 2,
                                                        'rules' => Rules::is( 'ai_platform', 'openrouter' )
                                                    ),
                                                    'ai_model' => array(
                                                        'name' => 'ai_model',
                                                        'type' => 'platform_model_select',
                                                        'label' => __( 'Model', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Available models depend on the selected platform.', 'betterdocs' ),
                                                        'priority' => 10,
                                                        'default' => 'gpt-4o-mini',
                                                        'catalogue' => ModelRegistry::catalogue(),
                                                        'defaults' => ModelRegistry::defaults()
                                                    )
                                                )
                                            ),
                                            'write-with-ai' => array(
                                                'id' => 'write-with-ai',
                                                'name' => 'write-with-ai',
                                                'type' => 'section',
                                                'label' => __( 'Write with AI', 'betterdocs' ),
                                                'priority' => 5,
                                                'fields' => array(
                                                  'write-with-ai-subtabs' => array(
                                                    'id' => 'write-with-ai-subtabs',
                                                    'name' => 'write_with_ai_subtabs',
                                                    'label' => __( 'Write with AI', 'betterdocs' ),
                                                    'classes' => 'tab-nested-layout',
                                                    'type' => 'tab',
                                                    'active' => 'wwa-configuration',
                                                    'completionTrack' => true,
                                                    'sidebar' => false,
                                                    'save' => false,
                                                    'title' => false,
                                                    'config' => array(
                                                        'active' => 'wwa-configuration',
                                                        'sidebar' => false,
                                                        'title' => false
                                                    ),
                                                    'submit' => array(
                                                        'show' => false
                                                    ),
                                                    'step' => array(
                                                        'show' => false
                                                    ),
                                                    'priority' => 5,
                                                    'fields' => array(
                                                      'wwa-configuration' => array(
                                                        'id' => 'wwa-configuration',
                                                        'name' => 'wwa-configuration',
                                                        'type' => 'section',
                                                        'label' => __( 'Configuration', 'betterdocs' ),
                                                        'priority' => 1,
                                                        'fields' => array(
                                                    'enable_write_with_ai' => array(
                                                        'name' => 'enable_write_with_ai',
                                                        'type' => 'toggle',
                                                        'priority' => 0,
                                                        'label' => __( 'Write Docs with AI', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Generate AI based Documentation in your Gutenberg Editor', 'betterdocs' ),
                                                        'enable_disable_text_active' => true,
                                                        'default' => true
                                                    ),
                                                    'enable_faq_write_with_ai' => array(
                                                        'name' => 'enable_faq_write_with_ai',
                                                        'type' => 'toggle',
                                                        'priority' => 5,
                                                        'label' => __( 'Write FAQ with AI', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Generate AI based FAQ in your Editor', 'betterdocs' ),
                                                        'enable_disable_text_active' => true,
                                                        'default' => true
                                                    ),
                                                    'enable_glossaries_write_with_ai' => array(
                                                        'name' => 'enable_glossaries_write_with_ai',
                                                        'type' => 'toggle',
                                                        'priority' => 6,
                                                        'label' => __( 'Write Glossaries with AI', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Generate AI based Glossary definitions from the Glossaries admin page', 'betterdocs' ),
                                                        'enable_disable_text_active' => true,
                                                        'default' => true,
                                                        'is_pro' => true
                                                    ),
                                                    'enable_docs_ai_suite' => array(
                                                        'name' => 'enable_docs_ai_suite',
                                                        'type' => 'toggle',
                                                        'priority' => 7,
                                                        'label' => __( 'Enhance Docs with AI', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Add BetterDocs AI actions for suggesting categories, tags and excerpts inside the native Docs editor panels.', 'betterdocs' ),
                                                        'enable_disable_text_active' => true,
                                                        'default' => true
                                                    ),
                                                    'ai_autowrite_max_token' => array(
                                                        'name' => 'ai_autowrite_max_token',
                                                        'type' => 'min_token_number',
                                                        'label' => __( 'Set Max Tokens', 'betterdocs' ),
                                                        'label_subtitle' => sprintf(  // translators: %s is a link to more information about token limits.
                                                            __( 'Documentation will be generated based on the Token Limits you have set. For more information on Token Limits, you can check out this <a target="_blank" href="%s">link</a>.', 'betterdocs' ), esc_url( 'https://platform.openai.com/account/limits' ) ),
                                                        'default' => 2500,
                                                        'priority' => 10,
                                                        'model_field' => 'ai_model',
                                                        'context' => 'write_with_ai',
                                                        'min_token_map' => AIHelper::get_min_tokens_map( 'write_with_ai' ),
                                                        'classes' => 'wprf-type-text'
                                                    )
                                                        )
                                                      ),
                                                      'wwa-personalize' => array(
                                                        'id' => 'wwa-personalize',
                                                        'name' => 'wwa-personalize',
                                                        'type' => 'section',
                                                        'label' => __( 'Write AI Personalize', 'betterdocs' ),
                                                        'priority' => 5,
                                                        'fields' => array(
                                                            'write_with_ai_instructions' => array(
                                                                'name' => 'write_with_ai_instructions',
                                                                'type' => 'wwa_instructions',
                                                                'label' => __( 'Instructions', 'betterdocs' ),
                                                                'label_subtitle' => __( 'Add reusable instruction sets to steer how BetterDocs AI writes. The "Default/Core" set is always applied; extra sets can be picked in the Write with AI popup.', 'betterdocs' ),
                                                                'priority' => 1,
                                                                'default' => array(
                                                                    array(
                                                                        'id'      => 'default',
                                                                        'title'   => __( 'Default/Core', 'betterdocs' ),
                                                                        'content' => WriteWithAI::default_instruction_content()
                                                                    )
                                                                )
                                                            )
                                                        )
                                                      ),
                                                      'edit-ai-personalize' => array(
                                                        'id' => 'edit-ai-personalize',
                                                        'name' => 'edit-ai-personalize',
                                                        'type' => 'section',
                                                        'label' => __( 'Edit AI Personalize', 'betterdocs' ),
                                                        'priority' => 7,
                                                        'fields' => array(
                                                            'ai_edit_actions' => array(
                                                                'name' => 'ai_edit_actions',
                                                                'type' => 'ai_edit_actions',
                                                                'label' => __( 'Actions', 'betterdocs' ),
                                                                'label_subtitle' => __( 'Choose which Edit with AI actions appear in the editor. Toggle actions on or off, edit each action\'s instruction, or add your own custom actions. All predefined actions are enabled by default.', 'betterdocs' ),
                                                                'priority' => 1,
                                                                'default' => AIEdit::default_actions()
                                                            )
                                                        )
                                                      )
                                                    )
                                                  )
                                                )
                                            ),
                                            'article-summary' => array(
                                                'id' => 'article-summary',
                                                'name' => 'article-summary',
                                                'type' => 'section',
                                                'label' => __( 'AI Doc Summarizer', 'betterdocs' ),
                                                'priority' => 10,
                                                'fields' => array(
                                                    'enable_article_summary' => array(
                                                        'name' => 'enable_article_summary',
                                                        'type' => 'toggle',
                                                        'priority' => 1,
                                                        'label' => __( 'AI Powered Doc Summarizer', 'betterdocs' ),
                                                        'label_subtitle' => __( 'Enable AI-powered article summaries on single doc pages. Requires OpenAI API key.', 'betterdocs' ),
                                                        'enable_disable_text_active' => true,
                                                        'default' => false
                                                    ),
                                                    'article_summary_max_token' => array(
                                                        'name' => 'article_summary_max_token',
                                                        'type' => 'min_token_number',
                                                        'label' => __( 'Set Max Tokens', 'betterdocs' ),
                                                        'label_subtitle' => sprintf(  // translators: %s is a link to more information about token limits.
                                                            __( 'Single Doc summarizer will be generated based on the token limits you have set. For more information on Token Limits, you can check out this <a target="_blank" href="%s">link</a>.', 'betterdocs' ), esc_url( 'https://platform.openai.com/account/limits' ) ),
                                                        'default' => 1500,
                                                        'priority' => 5,
                                                        'model_field' => 'ai_model',
                                                        'context' => 'article_summary',
                                                        'min_token_map' => AIHelper::get_min_tokens_map( 'article_summary' ),
                                                        'classes' => 'wprf-type-text'
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'tab-ai-chatbot' => apply_filters( 'betterdocs_settings_tab_ai_chatbot', array(
                    'id' => 'tab-ai-chatbot',
                    'name' => 'tab-ai-chatbot',
                    'type' => 'section',
                    'label' => __( 'AI Chatbot', 'betterdocs' ),
                    'submit' => array(
                        'show' => false
                    ),
                    'save' => false,
                    'priority' => 75,
                    'fields' => array(
                        'title-ai-chatbot-tab' => array(
                            'name' => 'title-ai-chatbot-tab',
                            'type' => 'section',
                            'label' => __( 'AI Chatbot', 'betterdocs' ),
                            'priority' => 80,
                            'submit' => array(
                                'show' => false
                            ),
                            'save' => false,
                            'fields' => apply_filters( 'betterdocs_ai_chatbot_fields', array(
                                'ai_chatbot_fields' => array(
                                    'name' => 'ai_chatbot_fields',
                                    'type' => 'section',
                                    'priority' => 0,
                                    'fields' => array(
                                        'enable_ai_chatbot' => array(
                                            'name' => 'enable_ai_chatbot',
                                            'label' => __( 'AI Chatbot', 'betterdocs' ),
                                            'description' => __( 'Enable AI Chatbot', 'betterdocs' ),
                                            'type' => 'toggle',
                                            'priority' => 5,
                                            'default' => 0,
                                            'is_pro' => true,
                                            'is_license_active' => false,
                                            'disabled' => ! betterdocs()->is_pro_active() || ! defined( 'BETTERDOCS_CHATBOT_FILE' )
                                        ),
                                        'instant_answer_warning' => array(
                                            'name' => 'instant_answer_warning',
                                            'type' => 'html',
                                            'priority' => 6,
                                            'html' => sprintf(
                                                '<div class="betterdocs-ia-warning" style="margin:8px 0 0;padding:12px 16px;background:#fffaeb;border-left:4px solid #fdb022;border-radius:4px;display:flex;align-items:flex-start;gap:10px;"><span style="font-size:18px;line-height:1;flex-shrink:0;color:#b54708;">&#9888;</span><div style="font-size:13px;color:#202223;line-height:1.5;">%1$s<br><a href="?page=betterdocs-settings&amp;tab=tab-instant-answer" style="display:inline-block;margin-top:6px;color:#5a6bff;text-decoration:none;font-weight:500;">%2$s &rarr;</a></div></div>',
                                                esc_html__( 'Instant Answer is currently disabled. The AI Chatbot won\'t appear on your site until Instant Answer is enabled.', 'betterdocs' ),
                                                esc_html__( 'Enable Instant Answer', 'betterdocs' )
                                            ),
                                            'rules' => Rules::logicalRule( array(
                                                Rules::is( 'enable_ai_chatbot', true ),
                                                Rules::is( 'enable_disable', false ),
                                            ), 'and' ),
                                        )
                                    )
                                )
                            ) )
                        )
                    )
                ) )
            ) ),
            'TAB_AI_CHATBOT' => get_option( 'enable_ai_chatbot' ),
            'CHATBOT_LICENSE' => get_option( 'betterdocs_chatbot_software__license_status' ),
            'PRO_ACTIVE' => is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' ),
            'PRO_LICENSE' => get_option( 'betterdocs_pro_software__license_status' )
        );

        $settings = array_merge( $settings, $this->chatbot_active_localize() );

        return apply_filters( 'betterdocs_settings_args', $settings );
    }

    /**
     * @return array
     */
    public function chatbot_active_localize() {
        $chatbot_active        = is_plugin_active( 'betterdocs-ai-chatbot/betterdocs-ai-chatbot.php' );
        $chatbot_license_valid = get_option( 'betterdocs_chatbot_software__license_status' ) === 'valid';
        $chatbot_enabled       = $this->get( 'enable_ai_chatbot', false );

        // AI Search Suggestions are enabled if all conditions are met
        $ai_search_suggestions_enabled = betterdocs()->helper->is_ai_chatbot_enabled();

        return array(
            'CHATBOT_ACTIVE' => $chatbot_active,
            'CHATBOT_LICENSE_VALID' => $chatbot_license_valid,
            'CHATBOT_ENABLED' => $chatbot_enabled,
            'AI_SEARCH_SUGGESTIONS_ENABLED' => $ai_search_suggestions_enabled
        );
    }

    /**
     * Call This Function As Helper, When Pro Is Deactivated, To Be Used As Settings Default Values, When Betterdocs Pro Is Deactivated
     *
     * @return array
     */
    public function pro_settings_default_values() {
        return array(
            'multiple_kb' => false,
            'enable_glossaries' => false,
            'enable_encyclopedia' => false,
            'analytics_from' => false,
            'unique_visitor_count' => false,
            'exclude_bot_analytics' => false,
            'show_attachment' => false,
            'show_related_docs' => false,
            'advance_search' => false,
            'child_category_exclude' => false,
            'kb_based_search' => false,
            'enable_disable' => false,
            'enable_content_restriction' => false
        );
    }

    public function import_export_settings( $settings ) {
        if ( ! current_user_can( 'import' ) ) {
            return $settings;
        }

        $settings[ 'tab-import-export' ] = apply_filters( 'betterdocs_settings_tab_import_export', array(
            'id' => 'tab-import-export',
            'name' => 'tab-import-export',
            'classes' => 'tab-import-export',
            'label' => __( 'Import / Export', 'betterdocs' ),
            'priority' => 80,
            'fields' => array(
                'sections-import-export' => array(
                    'name' => 'sections-import-export',
                    'type' => 'section',
                    'label' => __( 'Import / Export', 'betterdocs' ),
                    'priority' => 30,
                    'fields' => array(
                        'all-tab-import-export' => array(
                            'id' => 'all-tab-import-export',
                            'name' => 'all-tab-import-export',
                            'label' => __( 'Import Export Settings', 'betterdocs' ),
                            'classes' => 'tab-layout',
                            'type' => 'tab',
                            'active' => 'import',
                            'completionTrack' => true,
                            'sidebar' => false,
                            'save' => false,
                            'title' => false,
                            'config' => array(
                                'active' => 'import',
                                'sidebar' => false,
                                'title' => false
                            ),
                            'submit' => array(
                                'show' => false
                            ),
                            'step' => array(
                                'show' => false
                            ),
                            'priority' => 20,
                            'fields' => array(
                                'import' => array(
                                    'id' => 'import',
                                    'name' => 'import',
                                    'type' => 'section',
                                    'label' => __( 'Import', 'betterdocs' ),
                                    'priority' => 1,
                                    'fields' => array(
                                        'import_tab_nested' => array(
                                            'id' => 'import_tab_nested',
                                            'name' => 'import_tab_nested',
                                            'label' => __( 'Import', 'betterdocs' ),
                                            'classes' => 'tab-nested-layout',
                                            'type' => 'tab',
                                            'active' => 'import_docs_nested',
                                            'completionTrack' => true,
                                            'sidebar' => false,
                                            'save' => false,
                                            'title' => false,
                                            'config' => array(
                                                'active' => 'import_docs_nested',
                                                'sidebar' => false,
                                                'title' => false
                                            ),
                                            'submit' => array(
                                                'show' => false
                                            ),
                                            'step' => array(
                                                'show' => false
                                            ),
                                            'priority' => 1,
                                            'fields' => array(
                                                'import_docs_nested' => array(
                                                    'id' => 'import_docs_nested',
                                                    'name' => 'import_docs_nested',
                                                    'type' => 'section',
                                                    'label' => __( 'Import Docs', 'betterdocs' ),
                                                    'priority' => 1,
                                                    'fields' => array(
                                                        'import_docs' => array(
                                                            'name' => 'import_docs',
                                                            'type' => 'importerupload',
                                                            'label' => __( 'Import Docs', 'betterdocs' ),
                                                            'label_subtitle' => wp_sprintf(  // Translators: %1$s is a link to download a sample CSV file.
                                                                __( 'To import your Docs, please upload the .xml / .csv file here. <a href="%1$s">Download sample csv file</a>', 'betterdocs' ), betterdocs()->assets->icon( 'BetterDocs-sample-data.csv', true ) ),
                                                            'text' => array(
                                                                'normal' => __( 'Proceed', 'betterdocs' ),
                                                                'saved' => __( 'Proceed', 'betterdocs' ),
                                                                'loading' => __( 'Importing...', 'betterdocs' ),
                                                                'exists_notice' => __( 'It seems like documentations with same slugs already exist on your website. What would you like to do?', 'betterdocs' )
                                                            ),
                                                            'ajax' => array(
                                                                'on' => 'click',
                                                                'api' => '/betterdocs/v1/import-docs',
                                                                'swal' => array(
                                                                    'text' => __( 'Import completed successfully.', 'betterdocs' ),
                                                                    'icon' => 'success',
                                                                    'autoClose' => 2000
                                                                )
                                                            ),
                                                            'file_type' => '.xml, .csv',
                                                            'priority' => 1
                                                        )
                                                    )
                                                ),

                                                'import_settings_nested' => array(
                                                    'id' => 'import_settings_nested',
                                                    'name' => 'import_settings_nested',
                                                    'type' => 'section',
                                                    'label' => __( 'Import Settings', 'betterdocs' ),
                                                    'priority' => 1,
                                                    'fields' => array(
                                                        'settings_importer' => array(
                                                            'name' => 'settings_importer',
                                                            'type' => 'settingsuploader',
                                                            'label' => __( 'Import Settings', 'betterdocs' ),
                                                            'label_subtitle' => __( 'To import BetterDocs Settings, please upload BetterDocs settings you have exported from another website in .json format', 'betterdocs' ),
                                                            'reset' => __( 'Change', 'betterdocs' ),
                                                            'priority' => 1
                                                        ),
                                                        'import_settings' => array(
                                                            'name' => 'import_settings',
                                                            'type' => 'button',
                                                            'rules' => Rules::is( 'settings_importer', null, true ),
                                                            'text' => array(
                                                                'normal' => __( 'Proceed', 'betterdocs' ),
                                                                'saved' => __( 'Proceed', 'betterdocs' ),
                                                                'loading' => __( 'Importing...', 'betterdocs' )
                                                            ),
                                                            'ajax' => array(
                                                                'on' => 'click',
                                                                'api' => '/betterdocs/v1/import-settings',
                                                                'data' => array(
                                                                    'settings' => '@settings_importer'
                                                                ),
                                                                'swal' => array(
                                                                    'text' => __( 'Import completed successfully.', 'betterdocs' ),
                                                                    'icon' => 'success',
                                                                    'autoClose' => 1000
                                                                ),
                                                                'reload' => true
                                                            ),
                                                            'priority' => 2
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                'export' => array(
                                    'id' => 'export',
                                    'name' => 'export',
                                    'type' => 'section',
                                    'label' => __( 'Export', 'betterdocs' ),
                                    'priority' => 1,
                                    'fields' => array(
                                        'export_tab_nested' => array(
                                            'id' => 'export_tab_nested',
                                            'name' => 'export_tab_nested',
                                            'label' => __( 'Export', 'betterdocs' ),
                                            'classes' => 'tab-nested-layout',
                                            'type' => 'tab',
                                            'active' => 'export_docs_nested',
                                            'completionTrack' => true,
                                            'sidebar' => false,
                                            'save' => false,
                                            'title' => false,
                                            'config' => array(
                                                'active' => 'export_docs_nested',
                                                'sidebar' => false,
                                                'title' => false
                                            ),
                                            'submit' => array(
                                                'show' => false
                                            ),
                                            'step' => array(
                                                'show' => false
                                            ),
                                            'priority' => 1,
                                            'fields' => array(
                                                'export_docs_nested' => array(
                                                    'id' => 'export_docs_nested',
                                                    'name' => 'export_docs_nested',
                                                    'type' => 'section',
                                                    'label' => __( 'Export Docs', 'betterdocs' ),
                                                    'priority' => 1,
                                                    'fields' => apply_filters( 'betterdocs_export_fields', array(
                                                        'export_type' => array(
                                                            'name' => 'export_type',
                                                            'label' => __( 'Select Docs Type', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Choose an export type: All Docs, a specific Knowledge Base, or a Doc Category', 'betterdocs' ),
                                                            'type' => 'select',
                                                            'default' => 'docs',
                                                            'priority' => 3,
                                                            'search' => true,
                                                            'options' => $this->normalize_options( apply_filters( 'betterdocs_export_type_options', array(
                                                                'docs' => __( 'Docs', 'betterdocs' ),
                                                                'doc_category' => __( 'Docs Category', 'betterdocs' ),
                                                                'glossaries' => __( 'Glossaries', 'betterdocs' )
                                                            ) ) )
                                                        ),
                                                        'export_docs' => array(
                                                            'name' => 'export_docs',
                                                            'type' => 'checkbox-select',
                                                            'label' => __( 'Select Docs', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Selected docs will be included in the export.', 'betterdocs' ),
                                                            'priority' => 4,
                                                            'multiple' => true,
                                                            'search' => true,
                                                            'default' => array( 'all' ),
                                                            'placeholder' => __( 'Select any', 'betterdocs' ),
                                                            'options' => array_merge( array(
                                                                array(
                                                                    'value' => 'all',
                                                                    'label' => 'All'
                                                                )
                                                            ), $this->docs() ),
                                                            'filterValue' => 'all',
                                                            'rules' => Rules::is( 'export_type', 'docs' )
                                                        ),
                                                        'export_categories' => array(
                                                            'name' => 'export_categories',
                                                            'type' => 'checkbox-select',
                                                            'label' => __( 'Select Categories', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Selected categories and its docs will be included in the export.', 'betterdocs' ),
                                                            'priority' => 6,
                                                            'multiple' => true,
                                                            'search' => true,
                                                            'default' => array( 'all' ),
                                                            'placeholder' => __( 'Select any', 'betterdocs' ),
                                                            'options' => $this->get_terms( 'doc_category', false ),
                                                            'filterValue' => 'all',
                                                            'rules' => Rules::is( 'export_type', 'doc_category' )
                                                        ),
                                                        'export_glossaries' => array(
                                                            'name' => 'export_glossaries',
                                                            'type' => 'checkbox-select',
                                                            'label' => __( 'Select Glossaries', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Selected glossary terms will be exported.', 'betterdocs' ),
                                                            'priority' => 7,
                                                            'multiple' => true,
                                                            'search' => true,
                                                            'default' => array( 'all' ),
                                                            'placeholder' => __( 'Select any', 'betterdocs' ),
                                                            'options' => $this->get_terms( 'glossaries' ),
                                                            'filterValue' => 'all',
                                                            'rules' => Rules::is( 'export_type', 'glossaries' )
                                                        ),
                                                        'file_type' => array(
                                                            'name' => 'file_type',
                                                            'label' => __( 'Select File Type', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Choose a file type', 'betterdocs' ),
                                                            'type' => 'select',
                                                            'default' => 'xml',
                                                            'priority' => 8,
                                                            'search' => true,
                                                            'options' => $this->normalize_options( apply_filters( 'betterdocs_export_file_type_options', array(
                                                                'xml' => __( '.xml', 'betterdocs' ),
                                                                'csv' => __( '.csv', 'betterdocs' )
                                                            ) ) )
                                                        ),
                                                        'enable_export_faq' => array(
                                                            'name' => 'enable_export_faq',
                                                            'type' => 'toggle',
                                                            'priority' => 9,
                                                            'label' => __( 'Export FAQ', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Export FAQ Related Terms & Posts', 'betterdocs' ),
                                                            'enable_disable_text_active' => true,
                                                            'default' => true,
                                                            'rules' => Rules::is( 'export_type', 'glossaries', true )
                                                        ),
                                                        'export_docs_btn' => array(
                                                            'name' => 'export_docs_btn',
                                                            'text' => array(
                                                                'normal' => __( 'Proceed', 'betterdocs' ),
                                                                'saved' => __( 'Proceed', 'betterdocs' ),
                                                                'loading' => __( 'Exporting...', 'betterdocs' )
                                                            ),
                                                            'type' => 'button',
                                                            'priority' => 10,
                                                            'ajax' => array(
                                                                'on' => 'click',
                                                                'api' => '/betterdocs/v1/export-docs',
                                                                'data' => array(
                                                                    'export_type' => '@export_type',
                                                                    'export_docs' => '@export_docs',
                                                                    'export_kbs' => '@export_kbs',
                                                                    'export_categories' => '@export_categories',
                                                                    'export_glossaries' => '@export_glossaries',
                                                                    'file_type' => '@file_type',
                                                                    'enable_export_faq' => '@enable_export_faq'
                                                                ),
                                                                'swal' => array(
                                                                    'text' => __( 'Exported Successfully.', 'betterdocs' ),
                                                                    'icon' => 'success',
                                                                    'autoClose' => 2000
                                                                )
                                                            )
                                                        )
                                                    ) )
                                                ),

                                                'export_settings_nested' => array(
                                                    'id' => 'export_settings_nested',
                                                    'name' => 'export_settings_nested',
                                                    'type' => 'section',
                                                    'label' => __( 'Export Settings', 'betterdocs' ),
                                                    'priority' => 1,
                                                    'fields' => array(
                                                        'export_settings' => array(
                                                            'name' => 'export_settings',
                                                            'label' => __( 'Export Settings', 'betterdocs' ),
                                                            'label_subtitle' => __( 'Simply click on “Export Settings” button to download your BetterDocs settings in .json format', 'betterdocs' ),
                                                            'text' => array(
                                                                'normal' => __( 'Export Settings', 'betterdocs' ),
                                                                'saved' => __( 'Export Settings', 'betterdocs' ),
                                                                'loading' => __( 'Exporting...', 'betterdocs' )
                                                            ),
                                                            'type' => 'button',
                                                            'priority' => 8,
                                                            'ajax' => array(
                                                                'on' => 'click',
                                                                'api' => '/betterdocs/v1/export-settings',
                                                                'data' => array(
                                                                    'betterdocs_settings' => get_option( 'betterdocs_settings' )
                                                                ),
                                                                'swal' => array(
                                                                    'text' => __( 'File downloaded successfully.', 'betterdocs' ),
                                                                    'icon' => 'success',
                                                                    'autoClose' => 2000
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        ) );

        return $settings;
    }

    public function maybe_remove_git_tab( $tabs ) {
        $pro_active  = is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' );
        $pro_has_git = apply_filters( 'betterdocs_pro_has_git_integration', false );

        if ( $pro_active && ! $pro_has_git ) {
            unset( $tabs[ 'tab-git-sync' ] );
        }

        return $tabs;
    }

    public function normalize_options( $options ) {
        return GlobalFields::normalize_fields( $options );
    }

    public function get_texanomy() {
        $docs_tax = $this->database->get_cache( 'betterdocs::settings::taxonomies' );

        if ( $docs_tax ) {
            return $docs_tax;
        }

        $taxonomies = get_taxonomies( array(
            'object_type' => array( 'docs' )
        ), 'objects' );

        $docs_tax = array(
            'all' => 'All Docs Archive',
            'docs' => 'Docs Page'
        );
        foreach ( $taxonomies as $key => $value ) {
            $docs_tax[ $key ] = $value->label;
        }
        unset( $docs_tax[ 'doc_tag' ] );

        $docs_tax = $this->normalize_options( $docs_tax );
        if ( count( $docs_tax ) > 2 ) {
            $this->database->set_cache( 'betterdocs::settings::taxonomies', $docs_tax );
        }

        return $docs_tax;
    }

    public function get_terms( $taxonomy, $hide_empty = true ) {
        $_cache_key = 'betterdocs::settings::terms::' . trim( $taxonomy );
        $docs_tax   = $this->database->get_cache( $_cache_key );

        if ( $docs_tax ) {
            return $docs_tax;
        }

        $get_terms = get_terms( array(
            'taxonomy' => $taxonomy,
            'hide_empty' => $hide_empty
        ) );

        $terms = array(
            'all' => 'All'
        );

        if ( ! empty( $get_terms ) && ! is_wp_error( $get_terms ) ) {
            foreach ( $get_terms as $value ) {
                if ( isset( $value->slug ) && isset( $value->name ) ) {
                    $terms[ $value->slug ] = $value->name;
                }
            }
        }

        $terms = $this->normalize_options( $terms );
        if ( count( $terms ) > 1 ) {
            $this->database->set_cache( $_cache_key, $terms );
        }

        return $terms;
    }

    public function get_terms_with_ids( $taxonomy ) {
        $_cache_key = 'betterdocs::settings::terms::ids' . trim( $taxonomy );
        $docs_tax   = $this->database->get_cache( $_cache_key );

        if ( $docs_tax ) {
            return $docs_tax;
        }

        $get_terms = get_terms( array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'suppress_filter' => true
        ) );

        $terms = array();

        if ( ! empty( $get_terms ) && ! is_wp_error( $get_terms ) ) {
            foreach ( $get_terms as $value ) {
                if ( isset( $value->term_id ) && isset( $value->name ) ) {
                    $terms[ $value->term_id ] = $value->name;
                }
            }
        }

        $terms = $this->normalize_options( $terms );
        if ( count( $terms ) > 1 ) {
            $this->database->set_cache( $_cache_key, $terms );
        }

        return $terms;
    }

    /**
     * Get all docs
     */
    public function docs() {
        $docs = $this->database->get_cache( 'betterdocs::settings::all_docs' );

        if ( $docs ) {
            return $docs;
        }

        $docs = array();

        $_docs = get_posts( array(
            'post_type' => 'docs',
            'numberposts' => -1,
            'posts_per_page' => -1
        ) );

        if ( ! empty( $_docs ) ) {
            foreach ( $_docs as $doc ) {
                $docs[ $doc->ID ] = betterdocs()->template_helper->kses( $doc->post_title );
            }
            $docs = GlobalFields::normalize_fields( $docs );
            $this->database->set_cache( 'betterdocs::settings::all_docs', $docs );
        }

        return $docs;
    }

    public function hide_roles_management( $tabData = array() ) {
        global $current_user;

        if ( $current_user instanceof WP_User && ! in_array( 'administrator', $current_user->roles ) ) {
            unset( $tabData[ 'fields' ][ 'title-advance-settings' ][ 'fields' ][ 'article_roles' ] );
            unset( $tabData[ 'fields' ][ 'title-advance-settings' ][ 'fields' ][ 'settings_roles' ] );
            unset( $tabData[ 'fields' ][ 'title-advance-settings' ][ 'fields' ][ 'analytics_roles' ] );
            unset( $tabData[ 'fields' ][ 'title-advance-settings' ][ 'fields' ][ 'faq_roles' ] );
        }

        return $tabData;
    }
}
