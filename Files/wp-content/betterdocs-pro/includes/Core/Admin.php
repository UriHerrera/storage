<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Admin\Analytics;
use Elementor\Plugin;
use WPDeveloper\BetterDocsPro\Dependencies\WPDeveloper\Licensing\Manager as LicenseManager;

class Admin extends Base {

    private $settings;
    private $container;

    public function __construct( Settings $settings, Container $container ) {
        $this->settings  = $settings;
        $this->container = $container;

        if ( ! is_admin() ) {
            return;
        }

        add_action( 'in_admin_header', function ( $hook ) {
            global $current_screen;

            $screens = [
                'toplevel_page_betterdocs-dashboard',
                'betterdocs_page_betterdocs-admin',
                'betterdocs_page_betterdocs-settings',
                'betterdocs_page_betterdocs-faq',
                'betterdocs_page_betterdocs-analytics',
                'betterdocs_page_betterdocs-glossaries',
                'edit-doc_category',
                'edit-doc_tag'
            ];

            if ( ! in_array( $current_screen->id, $screens ) ) {
                return;
            }

            // Re-add the LicenseManager SDK notice (activation prompt, errors).
            if ( betterdocs_pro()->get_license_manager() instanceof LicenseManager ) {
                add_action( 'admin_notices', [ betterdocs_pro()->get_license_manager(), 'admin_notices' ] );
            }

            // Re-add our custom license-expired notice.
            add_action( 'admin_notices', [ betterdocs_pro(), 'license_notice' ] );

            // Re-add the Free/Pro analytics version-compatibility notice so it
            // renders inside the BetterDocs panels like the license/review notices
            // (CacheBank wiped it with every other admin_notices at priority 10).
            add_action( 'admin_notices', [ betterdocs_pro(), 'compatibility_notices' ] );
        }, 999 );

        /**
         * Restrict Users To View This Column If They Do Not Have Analytics Capability
         */
        if ( current_user_can( 'read_docs_analytics' ) ) {
            add_filter( 'manage_docs_posts_columns', [ $this, 'views_columns' ] );
            add_filter( 'manage_docs_posts_custom_column', [ $this, 'manage_views_columns' ], 10, 2 );
        }

        // add_filter( 'plugin_action_links_betterdocs/betterdocs.php', [$this, 'insert_plugin_links'] );
        add_filter( 'admin_body_class', [ $this, 'admin_body_classes' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

        /**
         * doc_category extra meta fields
         */

        add_action( 'betterdocs_doc_category_add_form_after', [ $this, 'handbook_layout_cover_image' ] );
        add_action( 'betterdocs_doc_category_update_form_after', [
            $this,
            'update_handbook_layout_cover_image'
        ], 10, 1 );

        //save attachments
        add_action( 'save_post_docs', [ $this, 'save_docs_attachments' ], 10, 1 );

        add_action( 'save_post_docs', [ $this, 'save_related_articles' ], 11, 1 );

        if ( $this->settings->get( 'show_attachment' ) ) {
            // Hook into unified metabox instead of creating separate metabox
            add_action( 'betterdocs_attachments_tab_content', [ $this, 'render_attchments_markup' ] );
        }

        if ( $this->settings->get( 'show_related_docs' ) ) {
            // Hook into unified metabox instead of creating separate metabox
            add_action( 'betterdocs_related_docs_tab_content', [ $this, 'render_related_articles_markup' ] );
        }

        if ( $this->settings->get( 'multiple_kb' ) ) {
            /**
             * Add Multiple KB Menu on Dashboad Sidebar
             */
            add_filter( 'betterdocs_admin_menu', [ $this, 'menu' ], 100 );

            // Offer "Switch to BetterDocs UI" on the classic Knowledge Base
            // taxonomy screen, pointing at the Multiple KB React page (only
            // registered when this setting is on, hence inside this block).
            add_filter( 'betterdocs_classic_switch_page', [ $this, 'classic_switch_page' ], 10, 2 );

            // phpcs:disable WordPress.Security.NonceVerification.Recommended -- list-mode UI flag, not a state-changing operation.
            $admin_list_mode = isset( $_GET['mode'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['mode'] ) ) ) : '';
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $admin_list_mode !== 'list' ) {
                add_action( 'betterdocs_admin_header_before_end', [ $this, 'add_knowledge_base_filter' ] );
            }
            if ( $admin_list_mode === 'list' ) {
                add_action( 'betterdocs_admin_filter_after_category', [ $this, 'filter_by_kb' ] );
            }
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- list-mode UI flag.
        if ( isset( $_GET['mode'] ) && trim( sanitize_text_field( wp_unslash( $_GET['mode'] ) ) ) === 'list' ) {
            add_action( 'betterdocs_admin_filter_before_submit', [ $this, 'filter_by_view' ] );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    public function render_attchments_markup() {
        betterdocs_pro()->views->get( 'admin/attachments/attachment' );
    }

    public function render_related_articles_markup() {
        betterdocs_pro()->views->get( 'admin/related-articles/related-articles' );
    }

    public function save_docs_attachments( $post_id ) {
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if( did_action('elementor/document/before_save') ) { // do not save in elementor edit mode screen, causes issue for saving attachments
            return;
        }

        // Hooked to save_post_docs which fires after WP-core's post-edit nonce verification.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( empty( $_POST['betterdocs_article_attachments'] ) || ! is_array( $_POST['betterdocs_article_attachments'] ) ) {
            // An absent attachments field means one of two very different things,
            // and they must not be conflated:
            //   (a) the user removed every attachment in the metabox and saved, or
            //   (b) this save never included the attachments metabox at all —
            //       Quick Edit, Bulk Edit, a REST/`wp/v2/docs` update, a
            //       programmatic wp_update_post(), or the block editor's main REST
            //       save (its meta-box loader posts the metabox separately).
            //
            // Only (a) should clear the saved attachments; clearing on (b) would
            // silently wipe a doc's attachments on routine edits. The metabox view
            // ships its own nonce, so its presence is the reliable signal that the
            // attachments metabox was the thing submitted. No nonce → leave the
            // stored attachments untouched.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $nonce = isset( $_POST['betterdocs_attachments_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['betterdocs_attachments_nonce'] ) ) : '';
            if ( $nonce && wp_verify_nonce( $nonce, 'betterdocs_attachments_metabox' ) ) {
                // Metabox submitted with nothing selected → persist the empty
                // state so the front end stops rendering the old set. Use [] (not
                // delete_post_meta) so the render check
                // count( (array) get_post_meta( …, true ) ) sees 0 rather than the
                // [''] a deleted key's '' return produces (a phantom empty box).
                update_post_meta( $post_id, '_betterdocs_attachments', [] );
            }
            return;
        }
        // Each entry is a JSON-encoded attachment object. Do NOT integer-coerce
        // them: absint() of a JSON string is 0, so array_map( 'absint', ... )
        // stores [0,0], which renders as "(.undefined)" rows / an empty download
        // box and fatals on PHP 8 (property_exists() on an int). Sanitize and
        // keep the valid JSON entries instead.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $attachments = self::sanitize_attachments_meta( wp_unslash( $_POST['betterdocs_article_attachments'] ) );
        // Re-slash before storing: update_post_meta() (via update_metadata) runs
        // wp_unslash() on the value internally. Passing the already-unslashed JSON
        // would strip the JSON's own escape backslashes a second time (\" \/ \uXXXX),
        // corrupting any attachment whose JSON contains escapes — e.g. the media
        // title (F-006 data-loss fix) or the large, deeply-nested objects media
        // plugins like Real Media Library return (escaped quotes in
        // `compat`/`sizes`/`nonces`). The corrupted string then fails json_decode
        // on read, so sanitize drops it and the attachment "disappears" after
        // save/refresh — and Migration::v392() permanently deletes the bad row.
        // wp_slash() cancels the internal unslash.
        update_post_meta( $post_id, '_betterdocs_attachments', wp_slash( $attachments ) );
    }

    /**
     * Keep only valid attachment entries before persisting/rendering.
     *
     * Each entry is a JSON-encoded attachment object. A corrupt entry decodes to
     * a non-object (e.g. an integer-coerced "0") or to a media item with an empty
     * id; those render as "(.undefined)" rows and an empty download box. Real
     * media items have a truthy `id`; external files carry a `contentType`.
     *
     * This is deliberately a NON-DESTRUCTIVE validity filter: valid entries are
     * preserved verbatim. It is reused by the one-time DB cleanup migration
     * ({@see Migration::v392()}), which rewrites every row it changes, and by the
     * editor. Rebuilding or dropping entries whose media has since been deleted
     * would silently discard a doc's attachment list on upgrade, so the XSS risk
     * in the media display fields is handled where it renders — escaped in
     * views/templates/parts/attachment.php — not by mutating stored data here.
     *
     * @param mixed $attachments Array of JSON strings (already unslashed).
     * @return array Re-indexed array of the valid original entries.
     */
    public static function sanitize_attachments_meta( $attachments ) {
        if ( ! is_array( $attachments ) ) {
            return [];
        }

        $clean = [];
        foreach ( $attachments as $attachment ) {
            $decoded = is_string( $attachment ) ? json_decode( $attachment ) : $attachment;

            if ( ! is_object( $decoded ) ) {
                continue;
            }

            // Media entries need a truthy id; external files are identified by a
            // contentType. Anything else is a corrupt row.
            if ( ! property_exists( $decoded, 'contentType' ) && empty( $decoded->id ) ) {
                continue;
            }

            $clean[] = $attachment;
        }

        return array_values( $clean );
    }

    public function save_related_articles( $post_id ) {
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        // Hooked to save_post_docs which fires after WP-core's post-edit nonce verification.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( empty( $_POST['betterdocs_related_articles'] ) || ! is_array( $_POST['betterdocs_related_articles'] ) ) {
            // Mirror save_docs_attachments(): an absent related-articles field is
            // either "the user removed every related article in the metabox" or
            // "this save path never included the metabox" (Quick Edit, Bulk Edit, a
            // REST update, a programmatic wp_update_post()). Only the former should
            // clear the saved set, proven by the metabox's own nonce; on every
            // other path leave the stored related articles untouched so a routine
            // edit never wipes them.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $nonce = isset( $_POST['betterdocs_related_articles_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['betterdocs_related_articles_nonce'] ) ) : '';
            if ( $nonce && wp_verify_nonce( $nonce, 'betterdocs_related_articles_metabox' ) ) {
                // [] (not delete_post_meta) so the render check
                // count( (array) get_post_meta( …, true ) ) at Admin.php ~405 sees
                // 0, not the [''] a deleted key's '' return produces (phantom box).
                update_post_meta( $post_id, '_betterdocs_related_articles', [] );
            }
            return;
        }
        // Each entry is a JSON-encoded related-article object (the metabox posts
        // value={JSON.stringify(article)}). As with attachments, absint() would
        // coerce every entry to 0; keep the valid JSON objects instead.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $related_articles = self::sanitize_json_object_meta( wp_unslash( $_POST['betterdocs_related_articles'] ) );
        // Re-slash before storing (see save_docs_attachments): update_post_meta()
        // unslashes internally, so unslashed JSON would lose its escape backslashes
        // and fail json_decode on read.
        update_post_meta( $post_id, '_betterdocs_related_articles', wp_slash( $related_articles ) );
    }

    /**
     * Keep only entries that decode to a JSON object (drops absint-coerced 0s
     * and other non-object junk). Used for the related-articles meta.
     *
     * Static so the one-time DB cleanup migration ({@see Migration::v392()})
     * can reuse the exact same rule when scrubbing already-corrupt post meta.
     *
     * @param mixed $items Array of JSON strings (already unslashed).
     * @return array Re-indexed array of the valid original entries.
     */
    public static function sanitize_json_object_meta( $items ) {
        if ( ! is_array( $items ) ) {
            return [];
        }

        $clean = [];
        foreach ( $items as $item ) {
            $decoded = is_string( $item ) ? json_decode( $item ) : $item;
            if ( is_object( $decoded ) ) {
                $clean[] = $item;
            }
        }

        return array_values( $clean );
    }

    public function views_columns( $columns ) {
        $new_columns = [];

        foreach ( $columns as $key => $value ) {
            if ( $key == 'date' ) {
                $new_columns['betterdocs_views'] = __( 'Views', 'betterdocs-pro' );
            }
            $new_columns[ $key ] = $value;
        }

        return $new_columns;
    }

    public function manage_views_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'betterdocs_views':
                $views         = (int) $this->container->get( Analytics::class )->get_views( $post_id );
                $analytics_url = admin_url( 'admin.php?page=betterdocs-analytics&betterdocs=' . $post_id . '&comparison_factor=views,feelings' );
                echo ! empty( $views ) ? '<a href="' . esc_url( $analytics_url ) . '">' . esc_html( $views ) . '</a>' : 0;
                break;
        }
    }

    public function insert_plugin_links( $links ) {
        if ( isset( $links['deactivate'] ) ) {
            /* translators: %s: plugin name */
            $links['deactivate'] = sprintf( __( 'Required by %s', 'betterdocs-pro' ), 'BetterDocs Pro' );
        }

        return $links;
    }

    public function admin_body_classes( $classes ) {
        return $classes . ' betterdocs-pro ';
    }

    public function handbook_layout_cover_image() {
        betterdocs()->views->get( 'admin/taxonomy/handbook-layout-image-add' );
    }

    public function update_handbook_layout_cover_image( $term ) {
        $cat_thumb_id = get_term_meta( $term->term_id, 'doc_category_thumb-id', true );
        betterdocs()->views->get( 'admin/taxonomy/handbook-layout-image-update', [
            'cat_thumb_id' => $cat_thumb_id
        ] );
    }

    public function menu( $menus ) {
        $menus['multiple_kb'] = Helper::normalize_menu(
            __( 'Multiple KB', 'betterdocs-pro' ),
            'betterdocs-knowledge-base',
            'manage_knowledge_base_terms',
            [ betterdocs_pro()->multiple_kb, 'render_kb_admin_page' ]
        );

        return $menus;
    }

    public function enqueue( $hook ) {
        if ( $hook == 'post-new.php' && get_post_type() == 'docs' || $hook == 'post.php' && get_post_type() == 'docs' ) {
            wp_enqueue_media();
            $parsed_array    = [];
            $attachment_meta = count( (array) get_post_meta( get_the_ID(), '_betterdocs_attachments', true ) ) > 0 ? get_post_meta( get_the_ID(), '_betterdocs_attachments', true ) : [];

            if ( is_array( $attachment_meta ) ) {
                //Parse JSON Attachments (skip corrupt/zero entries so the editor
                //never shows "(.undefined)" rows for legacy bad data).
                $attachment_meta = self::sanitize_attachments_meta( $attachment_meta );
                foreach ( $attachment_meta as $attachment ) {
                    array_push( $parsed_array, json_decode( $attachment ) );
                }
            }

            //Icons Array
            $icon_obj = [
                'image'       => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="image" height="16" width="16" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_image_icon' )
                ],
                'pdf'         => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="pdf" height="16" width="16" viewBox="0 0 512 512"><path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 144-208 0c-35.3 0-64 28.7-64 64l0 144-48 0c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zM176 352l32 0c30.9 0 56 25.1 56 56s-25.1 56-56 56l-16 0 0 32c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-48 0-80c0-8.8 7.2-16 16-16zm32 80c13.3 0 24-10.7 24-24s-10.7-24-24-24l-16 0 0 48 16 0zm96-80l32 0c26.5 0 48 21.5 48 48l0 64c0 26.5-21.5 48-48 48l-32 0c-8.8 0-16-7.2-16-16l0-128c0-8.8 7.2-16 16-16zm32 128c8.8 0 16-7.2 16-16l0-64c0-8.8-7.2-16-16-16l-16 0 0 96 16 0zm80-112c0-8.8 7.2-16 16-16l48 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-32 0 0 32 32 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-32 0 0 48c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-64 0-64z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_pdf_icon' )
                ],
                'zip'         => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="zip" height="16" width="12" viewBox="0 0 384 512"><path d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM96 48c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm-6.3 71.8c3.7-14 16.4-23.8 30.9-23.8h14.8c14.5 0 27.2 9.7 30.9 23.8l23.5 88.2c1.4 5.4 2.1 10.9 2.1 16.4c0 35.2-28.8 63.7-64 63.7s-64-28.5-64-63.7c0-5.5 .7-11.1 2.1-16.4l23.5-88.2zM112 336c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H112z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_zip_icon' )
                ],
                'external'    => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="external" height="16" width="16" viewBox="0 0 512 512"><path d="M432 320H400a16 16 0 0 0 -16 16V448H64V128H208a16 16 0 0 0 16-16V80a16 16 0 0 0 -16-16H48A48 48 0 0 0 0 112V464a48 48 0 0 0 48 48H400a48 48 0 0 0 48-48V336A16 16 0 0 0 432 320zM488 0h-128c-21.4 0-32.1 25.9-17 41l35.7 35.7L135 320.4a24 24 0 0 0 0 34L157.7 377a24 24 0 0 0 34 0L435.3 133.3 471 169c15 15 41 4.5 41-17V24A24 24 0 0 0 488 0z"/></svg>',
                    'payload'  => []
                ],
                'video'       => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="video" height="16" width="18" viewBox="0 0 576 512"><path d="M336.2 64H47.8C21.4 64 0 85.4 0 111.8v288.4C0 426.6 21.4 448 47.8 448h288.4c26.4 0 47.8-21.4 47.8-47.8V111.8c0-26.4-21.4-47.8-47.8-47.8zm189.4 37.7L416 177.3v157.4l109.6 75.5c21.2 14.6 50.4-.3 50.4-25.8V127.5c0-25.4-29.1-40.4-50.4-25.8z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_video_icon' )
                ],
                'audio'       => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="audio" height="16" width="12" viewBox="0 0 384 512"><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm-64 268c0 10.7-12.9 16-20.5 8.5L104 376H76c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h28l35.5-36.5c7.6-7.6 20.5-2.2 20.5 8.5v136zm33.2-47.6c9.1-9.3 9.1-24.1 0-33.4-22.1-22.8 12.2-56.2 34.4-33.5 27.2 27.9 27.2 72.4 0 100.4-21.8 22.3-56.9-10.4-34.4-33.5zm86-117.1c54.4 55.9 54.4 144.8 0 200.8-21.8 22.4-57-10.3-34.4-33.5 36.2-37.2 36.3-96.5 0-133.8-22.1-22.8 12.3-56.3 34.4-33.5zM384 121.9v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_audio_icon' )
                ],
                'msword'      => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="msword" height="16" width="12" viewBox="0 0 384 512"><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm57.1 120H305c7.7 0 13.4 7.1 11.7 14.7l-38 168c-1.2 5.5-6.1 9.3-11.7 9.3h-38c-5.5 0-10.3-3.8-11.6-9.1-25.8-103.5-20.8-81.2-25.6-110.5h-.5c-1.1 14.3-2.4 17.4-25.6 110.5-1.3 5.3-6.1 9.1-11.6 9.1H117c-5.6 0-10.5-3.9-11.7-9.4l-37.8-168c-1.7-7.5 4-14.6 11.7-14.6h24.5c5.7 0 10.7 4 11.8 9.7 15.6 78 20.1 109.5 21 122.2 1.6-10.2 7.3-32.7 29.4-122.7 1.3-5.4 6.1-9.1 11.7-9.1h29.1c5.6 0 10.4 3.8 11.7 9.2 24 100.4 28.8 124 29.6 129.4-.2-11.2-2.6-17.8 21.6-129.2 1-5.6 5.9-9.5 11.5-9.5zM384 121.9v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_wordfile_icon' )
                ],
                'application' => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="default" height="16" width="12" viewBox="0 0 384 512"><path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_default_icon' )
                ],
                'csv'         => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="csv" height="16" width="12" viewBox="0 0 384 512"><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm-96 144c0 4.4-3.6 8-8 8h-8c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h8c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8h-8c-26.5 0-48-21.5-48-48v-32c0-26.5 21.5-48 48-48h8c4.4 0 8 3.6 8 8v16zm44.3 104H160c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h12.3c6 0 10.4-3.5 10.4-6.6 0-1.3-.8-2.7-2.1-3.8l-21.9-18.8c-8.5-7.2-13.3-17.5-13.3-28.1 0-21.3 19-38.6 42.4-38.6H200c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8h-12.3c-6 0-10.4 3.5-10.4 6.6 0 1.3 .8 2.7 2.1 3.8l21.9 18.8c8.5 7.2 13.3 17.5 13.3 28.1 0 21.3-19 38.6-42.4 38.6zM256 264v20.8c0 20.3 5.7 40.2 16 56.9 10.3-16.7 16-36.6 16-56.9V264c0-4.4 3.6-8 8-8h16c4.4 0 8 3.6 8 8v20.8c0 35.5-12.9 68.9-36.3 94.1-3 3.3-7.3 5.1-11.7 5.1s-8.7-1.9-11.7-5.1c-23.4-25.2-36.3-58.6-36.3-94.1V264c0-4.4 3.6-8 8-8h16c4.4 0 8 3.6 8 8zm121-159L279.1 7c-4.5-4.5-10.6-7-17-7H256v128h128v-6.1c0-6.3-2.5-12.4-7-16.9z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_csv_icon' )
                ],
                'text'        => [
                    'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="default" height="16" width="12" viewBox="0 0 384 512"><path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>',
                    'payload'  => betterdocs()->settings->get( 'attachment_default_icon' )
                ]
            ];

            betterdocs_pro()->assets->enqueue( 'betterdocs-attachments', 'admin/css/attachment.css', [ 'betterdocs-fontawesome' ], 'all' );
            betterdocs_pro()->assets->enqueue( 'betterdocs-attachments', 'admin/js/attachment.js', [
                'jquery',
                'jquery-ui-draggable',
                'jquery-ui-droppable'
            ] );
            betterdocs_pro()->assets->localize( 'betterdocs-attachments', 'betterdocsAttachments', [
                'attachment_data'     => $parsed_array,
                'attachment_icons'    => $icon_obj,
                'attachment_settings' => [
                    'show_attachment_size'                => betterdocs()->settings->get( 'show_attachment_size' ),
                    'show_attachment_icon'                => betterdocs()->settings->get( 'show_attachment_icon' ),
                    'attachment_default_file_format_name' => betterdocs()->settings->get( 'attachment_file_name_format' )
                ]
            ] );

            //Register related articles related assets
            $parsed_related_articles = [];
            $related_articles_meta   = count( (array) get_post_meta( get_the_ID(), '_betterdocs_related_articles', true ) ) > 0 ? get_post_meta( get_the_ID(), '_betterdocs_related_articles', true ) : [];

            if ( is_array( $related_articles_meta ) ) {
                //Parse JSON Related Articles
                foreach ( $related_articles_meta as $related_article ) {
                    array_push( $parsed_related_articles, json_decode( $related_article ) );
                }
            }

            betterdocs_pro()->assets->enqueue( 'betterdocs-related-articles', 'admin/css/related-articles.css', [ 'betterdocs-fontawesome' ], 'all' );
            betterdocs_pro()->assets->enqueue( 'betterdocs-related-articles', 'admin/js/related-articles.js', [] );
            betterdocs_pro()->assets->localize( 'betterdocs-related-articles', 'betterdocsRelatedArticles', [
                'related_articles_data' => $parsed_related_articles,
                'current_post_id' => get_the_ID(),
                'post_type' => get_post_type(),
                'is_docs_edit_page' => get_post_type() === 'docs',
                // Boolean only — never expose the key itself to the editor JS.
                // Mirrors AIHelper::has_api_key() (reads `ai_autowrite_api_key`).
                // Generating AI suggestions requires a key, so the metabox gates
                // its Generate action on this and links to the AI settings tab.
                'has_api_key' => ! empty( betterdocs()->settings->get( 'ai_autowrite_api_key', '' ) ),
                'settings_url' => admin_url( 'admin.php?page=betterdocs-settings#betterdocs-ai' ),
                'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG
            ] );


        }

        global $current_screen;

        $_params = apply_filters('betterdocs_pro_localize_script',[
            'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
            'doc_cat_order_nonce'        => wp_create_nonce( 'doc_cat_order_nonce' ),
            'knowledge_base_order_nonce' => wp_create_nonce( 'knowledge_base_order_nonce' ),
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pagination read for admin UI localization.
            'paged'                      => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 0,
            'menu_title'                 => __( 'Switch to BetterDocs UI', 'betterdocs-pro' ),
            'multiple_kb'                => betterdocs_pro()->multiple_kb->is_enable
        ]);

        if ( isset( $current_screen->taxonomy ) ) {
            $_params['per_page_id'] = "edit_{$current_screen->taxonomy}_per_page";
        }

        betterdocs_pro()->assets->enqueue( 'betterdocs-pro-admin', 'admin/css/betterdocs-admin.css' );
        betterdocs_pro()->assets->enqueue( 'betterdocs-pro-admin', 'admin/js/betterdocs.js', [ 'jquery' ] );
        betterdocs_pro()->assets->localize( 'betterdocs-pro-admin', 'betterdocs_pro_admin', $_params );


    }

    /**
     * Map the classic Knowledge Base taxonomy screen to the Multiple KB React
     * page for the Free "Switch to BetterDocs UI" button.
     *
     * @param string $page React page slug resolved by Free, or '' for none.
     * @param string $hook Current admin page hook.
     * @return string
     */
    public function classic_switch_page( $page, $hook ) {
        if ( 'edit-tags.php' === $hook ) {
            $screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
            $taxonomy = $screen && ! empty( $screen->taxonomy )
                ? $screen->taxonomy
                : ( isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( 'knowledge_base' === $taxonomy ) {
                return 'betterdocs-knowledge-base';
            }
        }
        return $page;
    }

    public function add_knowledge_base_filter() {
        betterdocs()->views->get( 'admin/header-parts/kb' );
    }

    public function filter_by_kb() {
        betterdocs()->views->get( 'admin/header-parts/filter-by-kb' );
    }

    public function filter_by_view() {
        betterdocs()->views->get( 'admin/header-parts/filter-by-view' );
    }
}
