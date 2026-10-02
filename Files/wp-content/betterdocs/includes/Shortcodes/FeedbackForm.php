<?php
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

class FeedbackForm extends Shortcode {
    protected $html_attributes = [];

    public function __construct( Settings $settings, Query $query, Helper $helper, Defaults $defaults ) {
        parent::__construct( $settings, $query, $helper, $defaults );

        add_action( 'wp_ajax_nopriv_betterdocs_feedback_form_submit', [$this, 'submit'] );
        add_action( 'wp_ajax_betterdocs_feedback_form_submit', [$this, 'submit'] );
    }

    public function get_style_depends() {
        return ['betterdocs-feedback-form'];
    }

    public function get_script_depends() {
        return ['betterdocs'];
    }

    public function submit() {
		if ( ! betterdocs()->settings->get( 'email_feedback' ) ) {
			wp_send_json_error( __( 'Email feedback is disabled.', 'betterdocs' ) );
		}

        check_ajax_referer( 'betterdocs_submit_data', 'security' );

        $postID       = isset( $_POST['postID'] ) ? absint( wp_unslash( $_POST['postID'] ) ) : 0;
        $article      = get_the_title( $postID );
        $name         = isset( $_POST['message_name'] ) ? sanitize_text_field( wp_unslash( $_POST['message_name'] ) ) : '';
        $email        = isset( $_POST['message_email'] ) ? sanitize_email( wp_unslash( $_POST['message_email'] ) ) : '';
        $message_text = isset( $_POST['message_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message_text'] ) ) : '';

        $_errors = [];

        //validate presence of name
        if ( empty( $name ) ) {
            $_errors['name'] = __( 'Please enter your name.', 'betterdocs' );
        }

        //validate email
        if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $_errors['email'] = __( 'Enter a valid email address.', 'betterdocs' );
        }

        //validate presence of message
        if ( empty( $message_text ) ) {
            $_errors['message'] = __( 'Please write your message.', 'betterdocs' );
        }

        if ( ! empty( $_errors ) ) {
            $_errors = [
                'success' => false,
                'message' => $_errors
            ];

            wp_send_json_error( $_errors );
        }

        //php mailer variables
        $to      = $this->settings->get( 'email_address', get_option( 'admin_email' ) );
        $subject = wp_sprintf( '%s %s', __( 'Feedback message from', 'betterdocs' ), get_bloginfo( 'name' ) );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: $name <{$email}>",
            "Reply-To: $email"
        ];

        $_response = [
            'success' => true
        ];

        ob_start();
        betterdocs()->views->get(
            'admin/email/feedback-email',
            [
                'name'         => $name,
                'article'      => $article,
                'message_text' => $message_text
            ]
        );
        $message = ob_get_clean();

        // @todo: check strip tags: wp_mail( $to, $subject, strip_tags( $message ), $headers )

        if ( wp_mail( $to, $subject, $message, $headers ) ) {
            $_response['message'] = __( 'Thanks! Your message has been sent.', 'betterdocs' );
        } else {
            $_response['success'] = false;
            $_response['message'] = __( 'Message was not sent. Try Again.', 'betterdocs' );
        }

        wp_send_json_success( $_response );
    }

    public function get_name() {
        return 'betterdocs_feedback_form';
    }

    /**
     * Summary of default_attributes
     * @return array
     */
    public function default_attributes() {
        return [
            'button_text'                      => __( 'Send', 'betterdocs' ),
            'feedback_form_name_label_text'    => __( 'Name:', 'betterdocs' ),
            'feedback_form_email_label_text'   => __( 'Email:', 'betterdocs' ),
            'feedback_form_message_label_text' => __( 'Message:', 'betterdocs' )
        ];
    }

    public function render( $atts, $content = null ) {
        $this->views( 'shortcodes/feedback-form' );
    }

    public function view_params() {
        $params = $this->attributes;
        $name   = '';
        $email  = '';

        if ( is_user_logged_in() ) {
            $userdata = get_userdata( get_current_user_id() );
            $name     = $userdata->first_name . ' ' . $userdata->last_name;
            $email    = $userdata->user_email;
        }

        $params['name']  = $name;
        $params['email'] = $email;

        return $params;
    }
}
