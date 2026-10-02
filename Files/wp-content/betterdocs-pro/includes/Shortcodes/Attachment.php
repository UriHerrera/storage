<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;
use WPDeveloper\BetterDocs\Core\Shortcode;

class Attachment extends Shortcode {

    public function get_name() {
        return 'betterdocs_attachments';
    }

    public function default_attributes() {
        return [
            'post_id'                     => get_the_ID(),
            'show_attachments'            => betterdocs()->settings->get( 'show_attachment' ),
            'default_attachment_name'     => betterdocs()->settings->get( 'attachment_file_name_format' ),
            'show_attachment_icon'        => betterdocs()->settings->get( 'show_attachment_icon' ),
            'show_attachment_size'        => betterdocs()->settings->get( 'show_attachment_size' ),
            'show_attachments_in_new_tab' => betterdocs()->settings->get( 'attachment_new_tab' ),
            'attachment_heading_label'    => betterdocs()->settings->get( 'attachment_label' )
        ];
    }

    public function render( $atts, $content = null ) {
        $this->views( 'templates/parts/attachment' );
    }

    /**
     * Whether the current viewer may see a given post's attachment list.
     *
     * post_id is an attacker-controllable shortcode attribute, so
     * [betterdocs_attachments post_id=<private doc>] placed on any public post
     * would otherwise disclose that doc's attachment names/URLs/sizes — an IDOR
     * that also works as a media-library enumeration oracle. Require the target
     * to be a docs post the viewer can actually read (published/public, or one
     * they may edit); anything else discloses nothing.
     *
     * @param int $post_id
     * @return bool
     */
    protected function can_read_attachments( $post_id ) {
        if ( $post_id <= 0 ) {
            return false;
        }

        $post = get_post( $post_id );
        if ( ! $post || 'docs' !== $post->post_type ) {
            return false;
        }

        // A user who may edit the doc always sees its attachments.
        if ( current_user_can( 'edit_post', $post_id ) ) {
            return true;
        }

        // Otherwise only a publicly-viewable doc: a public post status and no
        // password. (read_post is unsuitable here — it requires the 'read' cap
        // even for public posts, so anonymous visitors would fail it on ordinary
        // published docs.)
        $status_obj = get_post_status_object( get_post_status( $post ) );
        if ( ! $status_obj || ! $status_obj->public || ! empty( $post->post_password ) ) {
            return false;
        }

        // Respect BetterDocs content restriction where it resolves a set (defence
        // in depth; the doc's own template_redirect already gates direct views).
        if ( function_exists( 'betterdocs_pro' ) ) {
            $restricted = array_map( 'intval', (array) betterdocs_pro()->get_restricted_doc_ids() );
            if ( in_array( (int) $post_id, $restricted, true ) ) {
                return false;
            }
        }

        return true;
    }

    public function view_params() {
        // Gate the meta read on a read check for the target doc (see
        // can_read_attachments) instead of trusting the post_id attribute.
        $post_id          = (int) $this->attributes['post_id'];
        $attachments_data = $this->can_read_attachments( $post_id )
            ? get_post_meta( $post_id, '_betterdocs_attachments', true )
            : '';
        $icon_obj         = [
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

        return [
            'attachments_data'        => $attachments_data,
            'attachment_switch'       => $this->attributes['show_attachments'],
            'show_attachment_size'    => $this->attributes['show_attachment_size'],
            'show_attachment_icon'    => $this->attributes['show_attachment_icon'],
            'icon_obj'                => $icon_obj,
            'attachment_default_name' => $this->attributes['default_attachment_name'],
            'attachment_label'        => $this->attributes['attachment_heading_label'],
            'attachment_new_tab'      => $this->attributes['show_attachments_in_new_tab']
        ];
    }

}
