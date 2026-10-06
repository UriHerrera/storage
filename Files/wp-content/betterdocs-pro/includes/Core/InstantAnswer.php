<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }


// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\CSSGenerator;
use WPDeveloper\BetterDocs\Utils\Helper;

class InstantAnswer extends Base {
    /**
     * @var Settings
     */
    private $settings;
    private $is_visible = true;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'preview_scripts' ) );
            add_action( 'admin_footer', array( $this, 'add_preview' ) );
            add_action( 'betterdocs_setup_wizard_instant_answer', array( $this, 'setup_wizard_instant_answer' ) );
        }

        if ( ! $this->settings->get( 'enable_disable', false ) ) {
            return;
        }

        add_filter( 'rest_doc_category_query', array( $this, 'order_ia_doc_taxonomies' ), 10, 2 );

        if ( ! is_admin() ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
            add_action( 'wp_footer', array( $this, 'add_ia' ) );
        }

        //Added to whitelist I/A styles on Thrive Builder Theme(fix)
        add_filter( 'tcb_lp_strip_css_whitelist', array( $this, 'white_list_css_thrive_builder' ), 10, 2 );
    }

    public function setup_wizard_instant_answer( $fields ) {
        $fields[ 'default' ] = true;

        return $fields;
    }

    public function white_list_css_thrive_builder( $style_handles, $tcb_landing_page ) {
        if ( ! in_array( 'betterdocs-instant-answer', $style_handles ) ) {
            array_push( $style_handles, 'betterdocs-instant-answer' );
        }

        return $style_handles;
    }

    public function preview_scripts( $hook ) {
        if ( method_exists( betterdocs(), 'is_betterdocs_screen' ) && ! betterdocs()->is_betterdocs_screen( $hook ) ) {
            return;
        }

        betterdocs_pro()->assets->enqueue( 'betterdocs-instant-answer', 'public/css/instant-answer.css' );
        wp_add_inline_style( 'betterdocs-instant-answer', self::inline_style( $this->settings ) );

        betterdocs_pro()->assets->enqueue( 'betterdocs-instant-answer', 'public/js/instant-answer.js' );

        // Same-origin endpoints: this widget is rendered on THIS site.
        betterdocs_pro()->assets->localize( 'betterdocs-instant-answer', 'betterdocs', $this->localize_settings( true ) );
    }

    public function add_preview() {
        $preview_visiable      = $this->settings->get( 'ia_enable_preview', false );
        $preview_visiable_main = $this->settings->get( 'enable_disable', false );

        $style = '';

        if ( false === $preview_visiable || false == $preview_visiable_main ) {
            $style = 'style="display: none"';
        }

        printf(
            '<aside id="betterdocs-ia" class="betterdocs-ia %s" %s"></aside>',
            esc_attr( 'betterdocs-' . $this->settings->get( 'chat_position', 'right' ) ),
            esc_attr( $style )
        );
    }

    private function is_page( $conditions = array() ) {
        return is_page() && ! empty( $conditions ) && ( in_array( "all", $conditions ) || is_page( $conditions ) );
    }

    private function is_post_type_archive( $conditions = array() ) {
        return is_archive() && ! is_tax() && ! is_category() && ! is_tag() && ! empty( $conditions ) && ( in_array( "all", $conditions ) || is_post_type_archive( $conditions ) );
    }

    private function is_other_archive( $conditions = array() ) {
        return is_archive() && ! empty( $conditions ) && ( in_array( "post", $conditions ) && is_date() || is_author() || is_day() );
    }

    private function is_taxonomy( $conditions = array(), $queried_object = null ) {
        return ( is_tax() || is_category() || is_tag() ) && ! empty( $conditions ) && ( in_array( "all", $conditions ) || in_array( $queried_object->taxonomy, $conditions ) );
    }

    private function is_home_archive( $conditions = array() ) {
        return is_home() && ! empty( $conditions ) && ( in_array( "all", $conditions ) || in_array( "post", $conditions ) );
    }

    private function is_post_type_product_archive( $conditions = array(), $queried_object = null ) {
        return is_archive() && ! empty( $conditions ) && ( in_array( "product", $conditions ) && get_taxonomy( $queried_object->taxonomy )->object_type[ 0 ] === 'product' );
    }

    private function is_singular( $conditions = array() ) {
        return ! is_page() && is_singular() && ! empty( $conditions ) && ( in_array( "all", $conditions ) || is_singular( $conditions ) );
    }

    public function ia_conditions() {
        $display_ia_pages    = $this->settings->get_raw_field( 'display_ia_pages' );
        $display_ia_archives = $this->settings->get_raw_field( 'display_ia_archives' );
        $display_ia_texonomy = $this->settings->get_raw_field( 'display_ia_texonomy' );
        $display_ia_single   = $this->settings->get_raw_field( 'display_ia_single' );
        $queried_object      = get_queried_object();

        if ( $this->is_page( $display_ia_pages ) ) {
            return true;
        } elseif ( $this->is_taxonomy( $display_ia_texonomy, $queried_object ) ) {
            return true;
        } elseif ( $this->is_post_type_archive( $display_ia_archives ) ) {
            return true;
        } elseif ( $this->is_home_archive( $display_ia_archives ) ) {
            return true;
        } elseif ( $this->is_other_archive( $display_ia_archives ) ) {
            return true;
        } elseif ( $this->is_post_type_product_archive( $display_ia_archives, $queried_object ) ) {
            return true;
        } elseif ( $this->is_singular( $display_ia_single ) ) {
            return true;
        }

        return false;
    }

    public function add_ia( $preview_visiable = null ) {
        if ( betterdocs()->helper->is_custom_docs_page() ) {
            return;
        }

        if ( '' === $preview_visiable && ! $this->is_visible ) {
            return;
        }

        $style = '';
        if ( false === $preview_visiable ) {
            $style = 'style="display: none"';
        }

        printf(
            '<aside id="betterdocs-ia" class="betterdocs-ia %s" %s"></aside>',
            esc_attr( 'betterdocs-' . $this->settings->get( 'chat_position', 'right' ) ),
            esc_attr( $style )
        );
    }

    public function order_ia_doc_taxonomies( $args, $request ) {
        if ( empty( $args[ 'include' ] ) && empty( $_GET ) && 'doc_category' == $args[ 'taxonomy' ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tax_limit            = $this->settings->get( 'doc_category_limit', 10 );
            $args[ 'number' ]     = $tax_limit;
            $args[ 'hide_empty' ] = 1;
            $args[ 'meta_key' ]   = 'doc_category_order';
            $args[ 'orderby' ]    = 'meta_value_num';
            $args[ 'order' ]      = 'ASC';
        }

        return $args;
    }

    public function scripts( $hook ) {
        if ( betterdocs()->helper->is_custom_docs_page() ) {
            $this->is_visible = false;
            return;
        }

        $this->is_visible = $this->ia_conditions();

        if ( ! is_admin() && ! $this->is_visible ) {
            return;
        }

        betterdocs_pro()->assets->enqueue( 'betterdocs-instant-answer', 'public/css/instant-answer.css' );
        wp_add_inline_style( 'betterdocs-instant-answer', self::inline_style( $this->settings ) );

        betterdocs_pro()->assets->enqueue( 'betterdocs-instant-answer', 'public/js/instant-answer.js' );

        // Same-origin endpoints: this widget is rendered on THIS site, which may
        // be served on a different host than the Site Address (subdomain / alias
        // / reverse proxy). Absolute URLs would make every request cross-origin
        // and the search would silently return nothing.
        betterdocs_pro()->assets->localize( 'betterdocs-instant-answer', 'betterdocs', $this->localize_settings( true ) );
    }

    public static function inline_style( $settings ) {
        $_all_settings = $settings->get_all();
        $css           = new CSSGenerator( $_all_settings );

        /**
         * Common Style Settings (Primary Color)
         */
        $css->add_rule( '.betterdocs-ia-launcher-wrapper .betterdocs-ia-launcher, .betterdocs-ia-main-content .chat-container .betterdocs-chatbot-header, .betterdocs-ia-common-header, .betterdocs-ia-common-header .betterdocs-ia-search .betterdocs-ia-search-icon, .betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-submit button, .betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-footer .betterdocs-ia-footer-feedback', $css->properties( array(
            'background-color' => 'ia_luncher_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header , .betterdocs-ia-tabs .active p', $css->properties( array(
            'color' => 'ia_luncher_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header .content-icon-expand svg path, .betterdocs-ia-docs-content .content-icon svg path, .betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header .content-icon-back svg path', $css->properties( array(
            'fill' => 'ia_luncher_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tabs li.active svg g path, .betterdocs-ia-tabs .betterdocs-ia-chatbot.active .chatbotpath, .top-content .chat-icon .chatbotpath, .betterdocs-ia-main-content .message-content .avatar path', $css->properties( array(
            'fill' => 'ia_luncher_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tabs .betterdocs-ia-chatbot.active .chatbotpathstroke, .top-content .chat-icon .chatbotpathstroke', $css->properties( array(
            'stroke' => 'ia_luncher_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-main-content .chat-container .chat-content-wrapper .chat-footer .message-input button, .betterdocs-ia-main-content .chat-container .chat-content-wrapper .chat-body .message.sent .query, .chat-container .chat-content-wrapper .chat-body .message.failed .query', $css->properties( array(
            'background-color' => 'ia_luncher_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-launcher-wrapper .betterdocs-ia-launcher:hover', $css->properties( array(
            'background-color' => 'ia_luncher_bg_hover'
        ) ) );

        $css->add_rule( '.betterdocs-ia-common-header .betterdocs-title:not(:last-child)', $css->properties( array(
            'color' => 'ia_heading_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-common-header h2', $css->properties( array(
            'color' => 'ia_heading_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-common-header .betterdocs-info', $css->properties( array(
            'color' => 'ia_sub_heading_color'
        ) ) );

        if ( isset( $_all_settings[ 'header_background_image' ][ 'url' ] ) ) {
            $css->add_rule( '.betterdocs-ia-common-header', $css->properties( array(
                'background-image' => $_all_settings[ 'header_background_image' ][ 'url' ]
            ) ) );
        }

        /**
         * Card Style Settings (All Common Cards)
         */

        $css->add_rule( '.betterdocs-ia-docs .betterdocs-ia-docs-heading .doc-title', $css->properties( array(
            'color' => 'ia_card_title_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-docs .betterdocs-ia-docs-heading', $css->properties( array(
            'background-color' => 'ia_card_title_background_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-docs-content .content-item .content-title', $css->properties( array(
            'color' => 'ia_card_title_list_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-docs-content .content-item p', $css->properties( array(
            'color' => 'ia_card_list_description_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-docs-content', $css->properties( array(
            'background-color' => 'ia_card_list_background_color'
        ) ) );

        /**
         * Search Style Settings (All Common Search Box)
         */
        $css->add_rule( '.betterdocs-ia-common-header .betterdocs-ia-search, .betterdocs-ia-common-header .betterdocs-ia-search .betterdocs-ia-search-field', $css->properties( array(
            'background-color' => 'ia_searchbox_bg'
        ) ) );

        $css->add_rule( '.betterdocs-ia-common-header .betterdocs-ia-search .betterdocs-ia-search-field::placeholder', $css->properties( array(
            'color' => 'ia_search_box_placeholder_text_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-common-header .betterdocs-ia-search .betterdocs-ia-search-field', $css->properties( array(
            'color' => 'ia_search_box_input_text_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-common-header .betterdocs-ia-search .betterdocs-ia-search-icon svg', $css->properties( array(
            'fill' => 'ia_searc_icon_color'
        ) ) );

        /**
         * All Tabs Style Settings (All Common Tabs)
         */

        $css->add_rule( '.betterdocs-ia-tabs', $css->properties( array(
            'background-color' => 'ia_launcher_tabs_background_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tabs li p', $css->properties( array(
            'color' => 'ia_launcher_tabs_text_color'
        ) ) );

        /**
         * Message Style Settings
         */

        $css->add_rule( '.betterdocs-ia-tab-message-container .message__header .header__content h4', $css->properties( array(
            'color' => 'ia_message_tab_title_font_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tab-message-container .message__header .header__content p', $css->properties( array(
            'color' => 'ia_message_tab_subtitle_font_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-group .ia-input, .betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-group > textarea', $css->properties( array(
            'background-color' => 'ia_ask_bg_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-email-group p, .betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-name-group p, .betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-subject-group p, .betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-message-group p', $css->properties( array(
            'color' => 'ia_message_input_label_text_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-attachments-group button', $css->properties( array(
            'background-color' => 'ia_message_upload_button_background_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-attachments-group p', $css->properties( array(
            'color' => 'ia_message_upload_text_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-tab-message-container .betterdocs-ia-feedback-form .betterdocs-ia-gdpr-group label', $css->properties( array(
            'color' => 'ia_message_tab_gdpr_text_color'
        ) ) );

        /**
         * Single Doc Style Settings
         */

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-content .doc-title', $css->properties( array(
            'color' => 'ia_single_doc_title_font_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header.on-scroll h2', $css->properties( array(
            'color' => 'ia_single_title_header_font_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header', $css->properties( array(
            'background-color' => 'ia_single_doc_title_header_bg_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header .content-icon-back, .betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-header .content-icon-expand', $css->properties( array(
            'background-color' => 'ia_single_icons_bg_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-footer .betterdocs-ia-footer-feedback .betterdocs-ia-reaction-group .ia-reaction', $css->properties( array(
            'background-color' => 'ia_reaction_primary_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-footer .betterdocs-ia-footer-feedback .betterdocs-ia-reaction-group .ia-reaction .betterdocs-emo', $css->properties( array(
            'fill' => 'ia_reaction_secondary_color'
        ) ) );

        $css->add_rule( '.betterdocs-ia-single-docs-wrapper .betterdocs-ia-singleDoc-footer .betterdocs-ia-footer-feedback p', $css->properties( array(
            'color' => 'ia_reaction_title_color'
        ) ) );

        /**
         * Position Css
         */

        if ( isset( $_all_settings[ 'chat_position' ] ) ) {
            if ( 'right' == $_all_settings[ 'chat_position' ] ) {
                $css->add_rule( '.betterdocs-ia-launcher-wrapper', $css->properties( array(
                    'right' => '20'
                ), 'px' ) );

                $css->add_rule( '.betterdocs-ia-main-wrapper', $css->properties( array(
                    'right' => '20'
                ), 'px' ) );
            }

            if ( 'left' == $_all_settings[ 'chat_position' ] ) {
                $css->add_rule( '.betterdocs-ia-launcher-wrapper', $css->properties( array(
                    'left' => '20'
                ), 'px' ) );

                $css->add_rule( '.betterdocs-ia-main-wrapper', $css->properties( array(
                    'left' => '20'
                ), 'px' ) );
            }
        }

        return $css->get_output( true );
    }

    public static function snippet() {
        ob_start();
        betterdocs_pro()->views->get( 'admin/ia-snippet', array(
            'styles' => self::inline_style( betterdocs()->settings ),
            // Absolute URLs on purpose — this snippet is pasted onto a THIRD-PARTY
            // site, so its endpoints must point back to this one.
            'scripts' => self::get_instance( betterdocs()->settings )->localize_settings(),
            'chatbot_scripts' => self::get_instance( betterdocs()->settings )->localize_chatbot(),
            'cross_domain_language' => ''
        ) );

        return ob_get_clean();
    }

    /**
     * Get the base code snippet for cross domain embedding.
     * This returns the raw HTML code that will be dynamically updated
     * with language parameter in the React component.
     *
     * @return string
     */
    public static function snippet_base() {
        ob_start();
        betterdocs_pro()->views->get( 'admin/ia-snippet-base', array(
            'styles' => self::inline_style( betterdocs()->settings ),
            'css_url' => betterdocs_pro()->assets->asset_url( 'public/css/instant-answer.css' ),
            'chatbot_css_url' => betterdocs_pro()->assets->asset_url( 'public/css/ai-chatbot.css' ),
            'js_url' => betterdocs_pro()->assets->asset_url( 'public/js/instant-answer-cd.js' )
        ) );

        return ob_get_clean();
    }

    /**
     * The markup the GDPR consent field is allowed to contain.
     *
     * The consent copy usually needs a link to a privacy policy, so this is not
     * a plain-text field. Kept in one place because the value is sanitized both
     * on save (Settings::save_settings) and on read (gdpr_consent_text) — two
     * copies of an allowlist drift, and the one that drifts is the hole.
     *
     * @return array<string, array<string, array>>
     */
    public static function gdpr_allowed_html() {
        return array(
            'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array(), 'class' => array() ),
            'strong' => array(),
            'em'     => array(),
            'span'   => array( 'class' => array() ),
            'br'     => array()
        );
    }

    /**
     * The default consent copy.
     *
     * @return string
     */
    public static function gdpr_default_text() {
        return __( 'I consent to my data being used to respond to my inquiry.', 'betterdocs-pro' );
    }

    /**
     * Sanitize the GDPR consent copy for storage or output.
     *
     * Beyond the allowlist this forces `rel="noopener noreferrer"` onto any link
     * that opens in a new tab: `wp_kses` can permit the `rel` attribute but
     * cannot require it, and `target="_blank"` without it hands the opened page
     * a `window.opener` handle back to this one.
     *
     * @param string $text Raw consent copy.
     * @return string
     */
    public static function sanitize_gdpr_text( $text ) {
        $text = wp_kses( stripslashes( (string) $text ), self::gdpr_allowed_html() );

        return preg_replace_callback(
            '#<a\s[^>]*target=(["\'])_blank\1[^>]*>#i',
            function ( $matches ) {
                $tag = $matches[0];
                if ( preg_match( '#\srel=#i', $tag ) ) {
                    // Replace whatever rel was supplied rather than trusting it.
                    return preg_replace( '#\srel=(["\']).*?\1#i', ' rel="noopener noreferrer"', $tag );
                }

                return preg_replace( '#\s*/?>$#', ' rel="noopener noreferrer"$0', $tag );
            },
            $text
        );
    }

    /**
     * The consent copy to show, falling back to the default when it is blank.
     *
     * An admin who enables the toggle and then clears the text would otherwise
     * get a mandatory, unlabeled checkbox that blocks Send with no explanation
     * of what is being consented to.
     *
     * @param object $settings Settings instance to read from.
     * @return string
     */
    public static function gdpr_consent_text( $settings ) {
        $text = self::sanitize_gdpr_text( $settings->get( 'chat_tab_gdpr_text', self::gdpr_default_text() ) );

        return '' !== trim( wp_strip_all_tags( $text ) ) ? $text : self::gdpr_default_text();
    }

    /**
     * Make a localized endpoint same-origin, when the caller asked for it.
     *
     * The Instant Answer REST/AJAX endpoints are built from get_rest_url() /
     * admin_url(), which always resolve against the configured Site Address host.
     * When the knowledge base is served on a DIFFERENT host than the Site Address
     * — a subdomain (faq.example.com), a domain alias, or a reverse proxy — every
     * Instant Answer request (search included) becomes cross-origin, gets blocked
     * or redirected by the browser, and the panel silently returns no results.
     *
     * Root-relative URLs resolve against the current page's origin instead, which
     * keeps those requests same-origin (and lets the auth cookie + REST nonce ride
     * along). Falls back to the absolute URL when running against an older free
     * BetterDocs that predates Helper::relative_url().
     *
     * @param string $url         Absolute URL.
     * @param bool   $same_origin Whether the caller wants the root-relative form.
     * @return string
     */
    private function maybe_relative_url( $url, $same_origin ) {
        if ( ! $same_origin || ! method_exists( Helper::class, 'relative_url' ) ) {
            return $url;
        }

        return Helper::relative_url( $url );
    }

    /**
     * Build the `window.betterdocs` config consumed by the Instant Answer app.
     *
     * @param bool $same_origin True when the config is localized for the widget
     *                          rendered ON this site, so the endpoints can be
     *                          root-relative and stay same-origin regardless of
     *                          the Site Address host. MUST stay false for the
     *                          cross-domain embed snippet, whose config is pasted
     *                          onto a THIRD-PARTY site and therefore needs the
     *                          absolute URLs back to this site.
     * @return array
     */
    public function localize_settings( $same_origin = false ) {
        $url = $this->maybe_relative_url( $this->get_rest_url(), $same_origin );

        $search_settings = $answer_settings = $chat_settings = $launcher_settings = $thanks_settings = $branding_settings = $response_settings = array();

        /**
         * Chat Settings
         */
        $chat_settings[ 'show' ] = $this->settings->get( 'chat_tab_visibility_switch', false );

        $chat_tab_icon = $this->settings->get( 'chat_tab_icon', array() );
        if ( ! empty( $chat_tab_icon[ 'url' ] ) ) {
            $chat_settings[ 'icon' ] = esc_url( $chat_tab_icon[ 'url' ] );
        }

        $chat_settings[ 'label' ]        = stripslashes( $this->settings->get( 'chat_tab_title', __( 'Ask', 'betterdocs-pro' ) ) );
        $chat_settings[ 'subtitle' ]     = stripslashes( $this->settings->get( 'chat_subtitle_one', __( 'Stuck with something? Send us a message.', 'betterdocs-pro' ) ) );
        $chat_settings[ 'subtitle_two' ] = stripslashes( $this->settings->get( 'chat_subtitle_two', __( 'Generally, we reply within 24-48 hours.', 'betterdocs-pro' ) ) );
        $chat_settings[ 'gdpr_enabled' ] = (bool) $this->settings->get( 'chat_tab_gdpr_switch', false );
        $chat_settings[ 'gdpr_text' ]    = self::gdpr_consent_text( $this->settings );

        /**
         * Answer Tab Settings
         */
        if ( $this->settings->get( 'answer_tab_visibility_switch', false ) ) {
            $answer_settings[ 'show' ] = false;
        }

        $answer_tab_icon = $this->settings->get( 'answer_tab_icon', array() );
        if ( ! empty( $answer_tab_icon[ 'url' ] ) ) {
            $answer_settings[ 'icon' ] = esc_url( $answer_tab_icon[ 'url' ] );
        }

        $answer_settings[ 'label' ]    = stripslashes( $this->settings->get( 'answer_tab_title', __( 'Answer', 'betterdocs-pro' ) ) );
        $answer_settings[ 'subtitle' ] = stripslashes( $this->settings->get( 'answer_tab_subtitle', __( 'Instant Answer', 'betterdocs-pro' ) ) );

        /**
         * Search Settings
         */
        $search_settings[ 'show' ] = $this->settings->get( 'search_visibility_switch', false );

        $search_settings[ 'SEARCH_URL' ]         = $this->maybe_relative_url( $this->get_rest_url( true ), $same_origin );
        $search_settings[ 'SEARCH_PLACEHOLDER' ] = stripslashes( $this->settings->get( 'search_placeholder_text', __( 'Search...', 'betterdocs-pro' ) ) );
        $search_settings[ 'OOPS' ]               = stripslashes( $this->settings->get( 'search_not_found_1', __( 'Oops...', 'betterdocs-pro' ) ) );
        $search_settings[ 'NOT_FOUND' ]          = stripslashes( $this->settings->get( 'search_not_found_2', __( "We couldn’t find any docs that match your search. Try searching for a new term.", 'betterdocs-pro' ) ) );

        // dump( $search_settings['SEARCH_URL'] );

        /**
         * Response Settings
         */
        if ( $this->settings->get( 'disable_response', false ) ) {
            $response_settings[ 'show' ] = false;
        }
        // $response_settings['title'] = stripslashes( $this->settings->get( 'response_title', __( 'Thanks for the feedback', 'betterdocs-pro' ) ) );

        if ( $this->settings->get( 'disable_response_icon', false ) ) {
            $response_settings[ 'icon' ][ 'show' ] = false;
        }

        $instant_answer = array(
            'IA_NONCE' => wp_create_nonce( 'wp_rest' ),
            'AJAX_URL' => $this->maybe_relative_url( admin_url( 'admin-ajax.php' ), $same_origin ),
            'SITE_TITLE' => get_bloginfo( 'name' ),
            // Every Instant Answer request (search, docs, categories, search
            // analytics) is built on BASE_URL, so this is the one that decides
            // whether search works on a subdomain / aliased host.
            'BASE_URL' => $this->maybe_relative_url( get_rest_url( null ), $same_origin ),
            'CHAT' => $chat_settings,
            'ANSWER' => $answer_settings,
            'URL' => $url,
            'SEARCH' => $search_settings,
            'HOME_DOCS_SWITCH' => $this->settings->get( 'ia_home_docs_visibility' ),
            'HOME_FAQ_SWITCH' => $this->settings->get( 'ia_home_faq_visibility' ),
            'HOME_TAB_TITLE' => $this->settings->get( 'home_tab_title' ),
            'HOME_CONTENT' => $this->settings->get( 'content_type', 'docs' ),
            'HOME_CONTENT_DOC_CATEGORY_TITLE' => $this->settings->get( 'home_doc_category_text', 'Doc Categories' ),
            'HOME_CONTENT_DOCS_TITLE' => $this->settings->get( 'home_docs_text', 'Docs' ),
            'HOME_TITLE' => $this->settings->get( 'home_content_title' ),
            'HOME_SUBTITLE' => $this->settings->get( 'home_content_subtitle' ),
            'FEEDBACK' => array(
                'DISPLAY' => $this->settings->get( 'ia_reaction', true ),
                'SUCCESS' => stripslashes( $this->settings->get( 'response_title', __( 'Thanks for the feedback', 'betterdocs-pro' ) ) ),
                'TEXT' => stripslashes( $this->settings->get( 'reaction_title', __( 'How did you feel?', 'betterdocs-pro' ) ) ),
                'URL' => $this->maybe_relative_url( get_rest_url( null, '/betterdocs/v1/feedback' ), $same_origin )
            ),
            'RESPONSE' => $response_settings,
            'ASKFORM' => array(
                'NAME' => __( 'Name', 'betterdocs-pro' ),
                'EMAIL' => __( 'Email Address', 'betterdocs-pro' ),
                'SUBJECT' => __( 'Subject', 'betterdocs-pro' ),
                'TEXTAREA' => __( 'How can we help?', 'betterdocs-pro' ),
                'ATTACHMENT' => __( 'Accepts .gif, .jpeg, png, pdf, jpg and .png', 'betterdocs-pro' ),
                'SENDING' => __( 'Sending', 'betterdocs-pro' ),
                'SEND' => __( 'Send', 'betterdocs-pro' ),
                'FILE_UPLOAD_SWITCH' => $this->settings->get( 'chat_tab_file_upload_switch' )
            ),
            'ASK_URL' => $this->maybe_relative_url( get_rest_url( null, '/betterdocs/v1/ask' ), $same_origin ),
            'RECAPTCHA' => array(
                'ENABLED'  => (bool) $this->settings->get( 'enable_recaptcha', false ),
                'SITE_KEY' => (string) $this->settings->get( 'recaptcha_site_key', '' ),
                'ACTION'   => 'betterdocs_ask'
            ),
            'HOME_FAQ' => array(
                'FAQ_URL' => $this->maybe_relative_url( $this->get_faq_rest_url(), $same_origin ),
                'HOME_FAQ_CONTENT_TYPE' => $this->settings->get( 'ia_home_faq_content_type' )
            ),
            'FAQ' => array(
                'faq-title' => $this->settings->get( 'ia_resources_faq_title' ),
                'faq-switch' => $this->settings->get( 'ia_resources_faq_switch' ),
                'faq_content_type' => $this->settings->get( 'ia_resources_faq_content_type' ),
                // 'faq-group-number' => $this->settings->get( 'ia_resources_faq_group_number' ),
                // 'faq-list-number'  => $this->settings->get( 'ia_resources_faq_list_number' ),
                'faq-terms' => $this->settings->get( 'ia_resources_faq_group' ),
                'faq-list' => $this->settings->get( 'ia_resources_faq_list' ),
                'faq-terms-order' => $this->settings->get( 'faq_terms_order' ),
                'faq-terms-order-by' => $this->settings->get( 'faq_terms_orderby' ),
                'faq-list-orderby' => $this->settings->get( 'ia_faq_list_order_by' ),
                'faq-list-order' => $this->settings->get( 'ia_faq_list_order' )
            ),
            'DOC_CATEGORY' => array(
                'doc-title' => $this->settings->get( 'ia_resources_doc_category_title_text' ),
                'doc-terms' => $this->settings->get( 'ia_resources_doc_categories' ),
                'doc-category-switch' => $this->settings->get( 'ia_resources_doc_categories_switch' ),
                'doc-terms-order' => $this->settings->get( 'ia_terms_order' ),
                'doc-terms-order-by' => $this->settings->get( 'ia_terms_orderby' ),
                'doc-subcategory-switch' => $this->settings->get( 'ia_resources_doc_subcategories_switch' )
            ),
            'RESOURCES_TITLE' => $this->settings->get( 'ia_resources_general_content_title' ),
            'RESOURCES_TAB_TITLE' => $this->settings->get( 'ia_resource_general_tab_title' ),
            'HEADER_ICON' => $this->settings->get( 'header_background_image', array() ),
            'HEADER_LOGO' => $this->settings->get( 'upload_header_logo', array() ),
            'TAB_HOME_ICON' => $this->settings->get( 'upload_home_icon', array() ),
            'TAB_MESSAGE_ICON' => $this->settings->get( 'upload_sendmessage_icon', array() ),
            'TAB_RESOURCE_ICON' => $this->settings->get( 'upload_resource_icon', array() ),
            'TAB_AI_CHATBOT' => $this->settings->get( 'enable_ai_chatbot' ),
            'CHATBOT_LICENSE' => get_option( 'betterdocs_chatbot_software__license_status' ),
            'PRO_LICENSE' => get_option( 'betterdocs_pro_software__license_status' ),
            'PRO_ACTIVE' => is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' ),
        );

        if ( method_exists( $this->settings, 'chatbot_active_localize' ) ) {
            $instant_answer = array_merge( $instant_answer, $this->settings->chatbot_active_localize() );
        }

        /**
         * Launcher Settings
         */
        $launcher_open_icon = $this->settings->get( 'launcher_open_icon', array() );
        if ( ! empty( $launcher_open_icon[ 'url' ] ) ) {
            $launcher_settings[ 'open_icon' ] = $launcher_open_icon[ 'url' ];
        }

        $launcher_close_icon = $this->settings->get( 'launcher_close_icon', array() );
        if ( ! empty( $launcher_close_icon[ 'url' ] ) ) {
            $launcher_settings[ 'close_icon' ] = $launcher_close_icon[ 'url' ];
        }

        if ( ! empty( $launcher_settings ) ) {
            $instant_answer = array_merge( $instant_answer, array( 'LAUNCHER' => $launcher_settings ) );
        }

        /**
         * Branding Settings
         */
        $branding_settings[ 'show' ] = $this->settings->get( 'ia_branding' );
        $instant_answer              = array_merge( $instant_answer, array( 'BRANDING' => $branding_settings ) );

        /**
         * Thanks Settings
         */
        $_thanks_title = $this->settings->get( 'ask_thanks_title', '' );
        if ( ! empty( $_thanks_title ) ) {
            $thanks_settings[ 'title' ] = stripslashes( $_thanks_title );
        }

        $_thanks_text = $this->settings->get( 'ask_thanks_text', '' );
        if ( ! empty( $_thanks_text ) ) {
            $thanks_settings[ 'text' ] = stripslashes( $_thanks_text );
        }

        if ( ! empty( $thanks_settings ) ) {
            $instant_answer = array_merge( $instant_answer, array( 'THANKS' => $thanks_settings ) );
        }

        return $instant_answer;
    }

    public function localize_chatbot() {
        $chatbot_settings                      = array();
        $chatbot_settings[ 'rest_url' ]        = esc_url_raw( rest_url() );
        $chatbot_settings[ 'title' ]           = stripslashes( $this->settings->get( 'ai_chatbot_title', __( 'We typically reply in a few minutes', 'betterdocs-pro' ) ) );
        $chatbot_settings[ 'subtitle' ]        = stripslashes( $this->settings->get( 'ai_chatbot_subtitle', __( 'We help your business grow by connecting you to your customers.', 'betterdocs-pro' ) ) );
        $chatbot_settings[ 'welcome_message' ] = stripslashes( $this->settings->get( 'ai_chatbot_welcome_message', sprintf(
            __( 'Hi, welcome to BetterDocs 👋 You’re speaking with AI Agent - I’m here to answer your questions & help you out.', 'betterdocs-pro' ),
            get_bloginfo( 'name' ) // This dynamically inserts the site title
        ) ) );
        $chatbot_settings[ 'ai_chatbot_icon' ]   = $this->settings->get( 'ai_chatbot_icon', array() );
        $chatbot_settings[ 'ai_chatbot_avatar' ] = $this->settings->get( 'ai_chatbot_avatar', array() );

        return $chatbot_settings;
    }

    public function get_rest_url( $is_search = false ) {
        $_query_strings_array = array();
        $_content_type        = $this->settings->get( 'content_type', 'docs' );
        $_base_url            = get_rest_url( null, 'wp/v2/docs' );

        // WPML compatibility: read the current language via WPML's own filter contract.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        if ( has_filter( 'wpml_current_language' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            $lang = apply_filters( 'wpml_current_language', null );
            if ( $lang ) {
                $_query_strings_array[ 'lang' ] = $lang;
            }
        }

        switch ( $_content_type ) {
            case 'docs':
                $_content_list = $this->settings->get( 'docs_list', array() );
                if ( ! empty( $_content_list ) && ! in_array( 'all', $_content_list ) ) {
                    $_query_strings_array[ 'include' ] = $_content_list;
                }
                if ( ! empty( $_content_list ) && in_array( 'all', $_content_list ) ) {
                    $_query_strings_array[ 'per_page' ] = 100;
                }
                break;
            case 'docs_categories':
                if ( ! $is_search ) {
                    $_base_url = get_rest_url( null, 'wp/v2/doc_category' );
                }
                $_content_list = $this->settings->get( 'doc_category_list', array() );
                if ( ! empty( $_content_list ) && ! in_array( 'all', $_content_list ) ) {
                    $_cats_list = implode( ',', $_content_list );

                    $_query_strings_array[ $is_search ? 'doc_category' : 'include' ] = $_cats_list;
                }

                $_category_orderby = $this->settings->get( 'doc_category_list_orderby' );
                $_category_order   = $this->settings->get( 'doc_category_list_order' );

                if ( ! empty( $_category_orderby ) ) {
                    $_query_strings_array[ 'orderby' ] = $_category_orderby;
                }

                $_category_order = $this->settings->get( 'doc_category_list_order', array() );
                if ( ! empty( $_category_order ) && 'doc_category_order' != $_category_orderby ) {
                    $_query_strings_array[ 'order' ] = $_category_order;
                }

                if ( ! empty( $_content_list ) && in_array( 'all', $_content_list ) ) {
                    $_cats_list = implode( ',', $_content_list );

                    $_query_strings_array[ 'per_page' ] = 100;
                }
                break;
            default:
                $_query_strings_array[ 'per_page' ] = 10;
                break;
        }

        $_query_strings_array = apply_filters( 'betterdocs_ia_query_string_array', $_query_strings_array, $_content_type, $is_search, $_content_list );
        $_query_string        = preg_replace( array( '/%5B[0-9]%5D/', '/%2C/' ), array(
            '[]',
            ','
        ), http_build_query( $_query_strings_array, '', '&' ) );
        $_parsed_url = wp_parse_url( (string) $_base_url );

        if ( ! empty( $_query_string ) ) {
            $_parsed_url[ 'query' ] = isset( $_parsed_url[ 'query' ] ) ? "{$_parsed_url[ 'query' ]}&$_query_string" : $_query_string;
        }

        return $this->unparse_url( $_parsed_url );
    }

    public function get_faq_rest_url() {
        $faq_base_url = '';
        $param        = '?';
        $content_type = $this->settings->get( 'ia_home_faq_content_type' );
        if ( 'faq-list' == $content_type ) {
            $faq_base_url = 'wp/v2/betterdocs_faq';
            $faq_list     = $this->settings->get( 'ia_home_faq_list' );
            if ( in_array( 'all', $faq_list ) ) {
                $param .= 'per_page=100';
                $faq_base_url .= $param;
            } else {
                foreach ( $faq_list as $index => $faq_id ) {
                    if ( 0 == $index ) {
                        $param .= "include[]={$faq_id}";
                    } else {
                        $param .= "&include[]={$faq_id}";
                    }
                }
                $faq_base_url .= $param;
            }
        } else {
            $faq_base_url = 'wp/v2/betterdocs_faq_category';
            $faq_group    = $this->settings->get( 'ia_home_faq_group' );
            if ( in_array( 'all', $faq_group ) ) {
                $param .= 'per_page=100';
                $faq_base_url .= $param;
            } else {
                foreach ( $faq_group as $index => $faq_id ) {
                    if ( 0 == $index ) {
                        $param .= "include={$faq_id}";
                    } else {
                        $param .= ",{$faq_id}";
                    }
                }
                $faq_base_url .= $param;
            }
        }
        // WPML compatibility: read the current language via WPML's own filter contract.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        if ( has_filter( 'wpml_current_language' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            $lang = apply_filters( 'wpml_current_language', null );
            if ( $lang ) {
                $faq_base_url .= '&lang=' . $lang;
            }
        }

        return get_rest_url( null, $faq_base_url );
    }

    /**
     * Unparse URL
     *
     * @param array $parsed_url
     *
     * @return string of url
     * @since 1.0.0
     */
    public static function unparse_url( $parsed_url ) {
        $scheme   = isset( $parsed_url[ 'scheme' ] ) ? $parsed_url[ 'scheme' ] . '://' : '';
        $host     = isset( $parsed_url[ 'host' ] ) ? $parsed_url[ 'host' ] : '';
        $port     = isset( $parsed_url[ 'port' ] ) ? ':' . $parsed_url[ 'port' ] : '';
        $user     = isset( $parsed_url[ 'user' ] ) ? $parsed_url[ 'user' ] : '';
        $pass     = isset( $parsed_url[ 'pass' ] ) ? ':' . $parsed_url[ 'pass' ] : '';
        $pass     = ( $user || $pass ) ? "$pass@" : '';
        $path     = isset( $parsed_url[ 'path' ] ) ? $parsed_url[ 'path' ] : '';
        $query    = isset( $parsed_url[ 'query' ] ) ? '?' . $parsed_url[ 'query' ] : '';
        $fragment = isset( $parsed_url[ 'fragment' ] ) ? '#' . $parsed_url[ 'fragment' ] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
