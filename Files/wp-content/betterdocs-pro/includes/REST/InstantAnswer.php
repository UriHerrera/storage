<?php

namespace WPDeveloper\BetterDocsPro\REST;

use WP_Error;
use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocsPro\Core\InstantAnswer as CoreInstantAnswer;

class InstantAnswer extends BaseAPI {

    /**
     * Aggregate upload ceiling for a single /ask submission.
     *
     * The per-file limit (5 MB) was previously the only size control, so one
     * accepted request could persist 10 x 5 MB = 50 MB into public uploads.
     */
    const MAX_TOTAL_UPLOAD_BYTES = 10 * MB_IN_BYTES;

    public function register() {
        $this->post( '/ask', [$this, 'ask'] );
        $this->post( '/feedback', [$this, 'feedback'] );
    }

    /**
     * Deliberately public — BaseAPI::permission_check() now fails closed, and
     * these two routes must stay open.
     *
     * /ask and /feedback back the front-end Instant Answer form, which is for
     * logged-out visitors by definition. Abuse control is not a capability
     * check here: it is the honeypot, reCAPTCHA verification and the per-IP
     * check_rate_limit() inside each handler.
     *
     * @return bool
     */
    public function permission_check() {
        return true;
    }

    public function sanitize( $post_data = [] ) {
        if ( ! empty( $post_data ) ) {
            $sanitized_data = [];
            foreach ( $post_data as $key => $data ) {
                // $_POST is attacker-shaped: `name[]=x` makes $data an array, and
                // both stripslashes() and sanitize_email() are string-typed, so
                // this threw an uncaught TypeError. sanitize() runs before
                // check_rate_limit(), so that was an unauthenticated 500 that could
                // not even be throttled. A contact form carries no meaning for a
                // non-scalar, so drop it rather than flattening — email then fails
                // the is_email() check below and the request ends as a clean 400.
                if ( ! is_scalar( $data ) ) {
                    continue;
                }
                $data = (string) $data;

                if ( $key === 'email' ) {
                    $sanitized_data[$key] = sanitize_email( $data );
                } else {
                    $sanitized_data[$key] = esc_html( stripslashes( $data ) );
                }
            }

            return $sanitized_data;
        }
        return [];
    }

    public function ready_subject( $sanitized_data, $ask_subject ) {
        $ask_subject = ! empty( $ask_subject ) ? $ask_subject : '[ia_subject]';

        $_subject_data = '';
        if ( isset( $sanitized_data['subject'] ) ) {
            $_subject_data = $sanitized_data['subject'];
        }
        $subject = str_replace( '[ia_subject]', $_subject_data, $ask_subject );

        $_email_data = '';
        if ( isset( $sanitized_data['email'] ) ) {
            $_email_data = $sanitized_data['email'];
        }
        $subject = str_replace( '[ia_email]', $_email_data, $subject );

        $_name_data = '';
        if ( isset( $sanitized_data['name'] ) ) {
            $_name_data = $sanitized_data['name'];
        }
        $subject = str_replace( '[ia_name]', $_name_data, $subject );

        return $subject;
    }

    /**
     * Verify a reCAPTCHA v3 token against Google's siteverify endpoint.
     * Returns true when verification passes (or is disabled). Returns a
     * WP_Error for missing/invalid/low-score tokens so the REST response
     * surfaces the rejection with an appropriate HTTP status.
     *
     * @return true|WP_Error
     */
    protected function verify_recaptcha( WP_REST_Request $request ) {
        $enabled    = (bool) $this->settings->get( 'enable_recaptcha', false );
        $site_key   = trim( (string) $this->settings->get( 'recaptcha_site_key', '' ) );
        $secret_key = trim( (string) $this->settings->get( 'recaptcha_secret_key', '' ) );

        if ( ! $enabled ) {
            return true;
        }

        // Fail closed: if the toggle is on but keys are missing (e.g. reached via a
        // direct DB write or migration that bypasses the settings-save validator),
        // reject rather than silently letting every request through.
        if ( $site_key === '' || $secret_key === '' ) {
            return new WP_Error(
                'recaptcha_misconfigured',
                __( 'Verification is currently unavailable. Please try again later.', 'betterdocs-pro' ),
                [ 'status' => 503 ]
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public REST /ask endpoint; abuse protection is reCAPTCHA + per-IP rate limiting + honeypot, not a nonce (anonymous visitors have none). Token is sanitized and then verified with Google.
        $token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) ) : '';
        if ( $token === '' ) {
            return new WP_Error(
                'recaptcha_failed',
                __( 'Verification failed. Please try again.', 'betterdocs-pro' ),
                [ 'status' => 400 ]
            );
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 5,
            'body'    => [
                'secret'   => $secret_key,
                'response' => $token,
                'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''
            ]
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'recaptcha_unreachable',
                __( 'Could not reach the verification service. Please try again later.', 'betterdocs-pro' ),
                [ 'status' => 503 ]
            );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) || empty( $body['success'] ) ) {
            return new WP_Error(
                'recaptcha_failed',
                __( 'Verification failed. Please try again.', 'betterdocs-pro' ),
                [ 'status' => 400 ]
            );
        }

        if ( isset( $body['action'] ) && $body['action'] !== 'betterdocs_ask' ) {
            return new WP_Error(
                'recaptcha_failed',
                __( 'Verification failed. Please try again.', 'betterdocs-pro' ),
                [ 'status' => 400 ]
            );
        }

        $threshold = (float) $this->settings->get( 'recaptcha_score_threshold', 0.5 );
        $score     = isset( $body['score'] ) ? (float) $body['score'] : 0.0;
        if ( $score < $threshold ) {
            return new WP_Error(
                'recaptcha_failed',
                __( 'Verification failed. Please try again.', 'betterdocs-pro' ),
                [ 'status' => 400 ]
            );
        }

        return true;
    }

    /**
     * Throttle anonymous Ask submissions per IP using a transient counter.
     * The /ask route is intentionally public, so this is the only volume brake
     * when reCAPTCHA is disabled. Returns true when within the limit, or a
     * WP_Error (429) once the limit is exceeded.
     *
     * @return true|WP_Error
     */
    protected function check_rate_limit( $bucket = 'ask', $cap = 5, $window = null ) {
        $window = $window === null ? 10 * MINUTE_IN_SECONDS : $window;

        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        // Fail closed. Returning true here meant every request without a usable
        // client address bypassed the only volume control this public endpoint
        // has.
        if ( $ip === '' ) {
            return new WP_Error(
                'ask_rate_limited',
                __( 'Too many requests. Please try again in a few minutes.', 'betterdocs-pro' ),
                [ 'status' => 429 ]
            );
        }

        $key = 'betterdocs_ia_' . $bucket . '_' . md5( $ip );

        // Reserve the slot before doing any work. Transients are not atomic, so
        // tightly-timed concurrent requests can still overshoot slightly; this
        // bounds sustained abuse rather than a burst of a few.
        $count = (int) get_transient( $key );
        if ( $count >= $cap ) {
            return new WP_Error(
                'ask_rate_limited',
                __( 'Too many requests. Please try again in a few minutes.', 'betterdocs-pro' ),
                [ 'status' => 429 ]
            );
        }

        set_transient( $key, $count + 1, $window );
        return true;
    }

    /**
     * This method is responsible for sending emails to site admin from ask form in IA.
     * @param mixed $request
     * @return mixed
     */
    public function ask( WP_REST_Request $request ) {
        // Honeypot: a hidden field real users never fill. Silently accept (and drop)
        // bot submissions so the trap is not revealed.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public REST /ask endpoint; bot honeypot check, no nonce by design (see verify_recaptcha()/check_rate_limit()).
        if ( ! empty( $_POST['betterdocs_hp'] ) ) {
            return true;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public REST /ask endpoint; payload is sanitized in sanitize(). Abuse protection is reCAPTCHA + per-IP rate limiting + honeypot, not a nonce.
        $sanitized_data = $this->sanitize( $_POST );
        if ( empty( $sanitized_data ) || empty( $sanitized_data['email'] ) || ! is_email( $sanitized_data['email'] ) ) {
            return new WP_Error(
                'ask_invalid',
                __( 'Please provide a valid email address.', 'betterdocs-pro' ),
                [ 'status' => 400 ]
            );
        }

        // Enforce the admin "GDPR Consent" toggle server-side. The checkbox in the
        // widget is UX only; without this the unauthenticated endpoint still accepts
        // a submission that never consented — a direct POST, or any non-React client,
        // bypasses it. Mirrors how the File Upload toggle is enforced below.
        $gdpr_enabled = wp_validate_boolean( $this->settings->get( 'chat_tab_gdpr_switch', false ) );
        if ( $gdpr_enabled ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public REST /ask endpoint; see the honeypot note above.
            $consent_given = isset( $_POST['gdpr_consent'] ) && wp_validate_boolean( wp_unslash( $_POST['gdpr_consent'] ) );

            if ( ! $consent_given ) {
                return new WP_Error(
                    'ask_consent_required',
                    __( 'Please provide your consent to continue.', 'betterdocs-pro' ),
                    [ 'status' => 400 ]
                );
            }

            // Record proof of consent next to the message. These submissions are
            // emailed rather than stored, so the notification is the record — and a
            // record has to say *what* was agreed to: consent to wording that was
            // edited afterwards is not consent to the wording shown today.
            $sanitized_data['gdpr_consent'] = sprintf(
                /* translators: 1: consent timestamp in UTC, 2: the consent text the visitor agreed to. */
                __( 'Given at %1$s UTC — "%2$s"', 'betterdocs-pro' ),
                gmdate( 'Y-m-d H:i:s' ),
                wp_strip_all_tags( CoreInstantAnswer::gdpr_consent_text( $this->settings ) )
            );
        }

        $rate_limit_check = $this->check_rate_limit();
        if ( is_wp_error( $rate_limit_check ) ) {
            return $rate_limit_check;
        }

        $recaptcha_check = $this->verify_recaptcha( $request );
        if ( is_wp_error( $recaptcha_check ) ) {
            return $recaptcha_check;
        }

        $ask_subject = $this->settings->get( 'ask_subject', '[ia_subject]' );
        $to          = $this->settings->get( 'ask_email', get_bloginfo( 'admin_email' ) );

        // The mail *header* wants plain text, so the entities sanitize() added
        // have to come back off — otherwise admins read "Question about
        // &quot;pricing&quot;". Decoding alone would re-arm the payload, so strip
        // any tags it exposes and collapse line breaks (a bare CR/LF in a Subject
        // is header injection).
        $subject = wp_strip_all_tags(
            html_entity_decode( $this->ready_subject( $sanitized_data, $ask_subject ), ENT_QUOTES, 'UTF-8' ),
            true
        );

        // Deliberately NOT written back into $sanitized_data: the body is sent as
        // Content-Type: text/html, so feeding it the decoded subject turned every
        // esc_html() in sanitize() into a no-op and let an unauthenticated
        // question inject markup straight into the admin's inbox. The escaped
        // value renders identically in an HTML body — "&quot;" displays as '"' —
        // so the body keeps the safe form and loses nothing.

        $files = $request->get_file_params();
        // Enforce the admin "File Upload" toggle server-side; without this the
        // unauthenticated endpoint accepts uploads even when the feature is OFF.
        $file_upload_enabled = wp_validate_boolean( $this->settings->get( 'chat_tab_file_upload_switch', true ) );
        if ( $file_upload_enabled && ! empty( $files['file'] ) ) {
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            // Mirror the client-side allowlist (png/gif/jpg/jpeg/pdf) and 5 MB size
            // limit on the server, and cap the file count, so the client controls
            // cannot be bypassed by a direct request.
            $allowed_mimes = [
                'jpg|jpeg' => 'image/jpeg',
                'gif'      => 'image/gif',
                'png'      => 'image/png',
                'pdf'      => 'application/pdf'
            ];
            $max_file_size = 5 * MB_IN_BYTES;
            $max_files     = 10;

            $upload_overrides = [
                'test_form'                => false,
                'mimes'                    => $allowed_mimes,
                // Store under a random, unguessable name so private attachments
                // don't get predictable public URLs.
                'unique_filename_callback' => [$this, 'ia_unique_filename']
            ];
            $new_files  = $files['file'];
            $movedFiles = [];
            $processed  = 0;
            $total_bytes = 0;
            foreach ( $new_files['name'] as $key => $value ) {
                if ( empty( $new_files['name'][$key] ) ) {
                    continue;
                }
                if ( ++$processed > $max_files ) {
                    break;
                }
                $file_size = (int) $new_files['size'][$key];
                if ( $file_size > $max_file_size ) {
                    continue;
                }
                // Bound the request as a whole, not just each file individually.
                if ( $total_bytes + $file_size > self::MAX_TOTAL_UPLOAD_BYTES ) {
                    break;
                }
                $total_bytes += $file_size;
                $file = [
                    'name'     => $new_files['name'][$key],
                    'type'     => $new_files['type'][$key],
                    'tmp_name' => $new_files['tmp_name'][$key],
                    'error'    => $new_files['error'][$key],
                    'size'     => $new_files['size'][$key]
                ];
                $movedFile = wp_handle_upload( $file, $upload_overrides );
                if ( ! isset( $movedFile['error'] ) ) {
                    $movedFiles[] = $movedFile;
                }
            }
            if ( ! empty( $movedFiles ) ) {
                $sanitized_data['files'] = $movedFiles;
            }
        }
        $body    = $this->mail_body( $sanitized_data );
        $name    = isset( $sanitized_data['name'] ) ? html_entity_decode( $sanitized_data['name'], ENT_QUOTES, 'UTF-8' ) : '';
        // Strip CR/LF so a crafted name cannot inject extra mail headers (e.g. Bcc:).
        $name    = trim( preg_replace( '/[\r\n]+/', ' ', $name ) );
        $from    = $sanitized_data['email'];
        if ( ! is_email( $from ) ) {
            $from = $to;
        }
        $headers = ['Content-Type: text/html; charset=UTF-8', "From: $name <$from>", 'Reply-To: ' . $from];
        if ( wp_mail( $to, $subject, $body, $headers ) ) {
            return true;
        }

        // The notification is the only thing that ever references these uploads
        // (the template links them by URL). If it could not be sent, nothing will
        // point at them again — remove them instead of leaving unreachable files
        // accumulating in the uploads directory.
        $this->discard_uploads( $sanitized_data['files'] ?? [] );

        return new WP_Error(
            'ask_mail_failed',
            __( 'Your message could not be sent. Please try again later.', 'betterdocs-pro' ),
            [ 'status' => 500 ]
        );
    }

    /**
     * Delete Ask-form uploads that no delivered email refers to.
     *
     * @param array $files Entries as returned by wp_handle_upload().
     * @return void
     */
    protected function discard_uploads( $files ) {
        if ( ! is_array( $files ) ) {
            return;
        }

        foreach ( $files as $file ) {
            if ( ! empty( $file['file'] ) && file_exists( $file['file'] ) ) {
                wp_delete_file( $file['file'] );
            }
        }
    }

    /**
     * Generate a random, unguessable filename for IA Ask-form uploads while
     * preserving the original extension, so private attachments can't be reached
     * via predictable /wp-content/uploads/YYYY/MM/<original-name> URLs.
     *
     * @param string $dir
     * @param string $name
     * @param string $ext
     * @return string
     */
    public function ia_unique_filename( $dir, $name, $ext ) {
        return wp_generate_password( 20, false ) . $ext;
    }

    public function mail_body( $data ) {
        if ( empty( $data ) ) {
            return '';
        }

        ob_start();
        betterdocs_pro()->views->get( 'admin/email/ia', ['all_data' => $data] );
        return ob_get_clean();
    }

    /**
     * Save Global Feedback
     * @param WP_REST_Request $request
     * @return bool
     */
    public function feedback( WP_REST_Request $request ) {
        $feelings        = isset( $request['feelings'] ) ? $request['feelings'] : 'happy';
        $allowed_feelings = ['happy', 'normal', 'sad'];

        if ( ! in_array( $feelings, $allowed_feelings, true ) ) {
            return new WP_Error(
                'betterdocs_invalid_feelings',
                __( 'Invalid feedback value.', 'betterdocs-pro' ),
                ['status' => 400]
            );
        }

        // The route is public and inherits a permissive permission check, and the
        // target is a single site-wide option rather than feedback bound to a
        // document or session. Without a brake, anyone could drive the site's
        // sentiment counters to any value they liked and generate unlimited
        // option writes. Ten submissions per IP per hour leaves normal use
        // untouched.
        $rate_limit_check = $this->check_rate_limit( 'feedback', 10, HOUR_IN_SECONDS );
        if ( is_wp_error( $rate_limit_check ) ) {
            return $rate_limit_check;
        }

        $feedback = get_option( '_betterdocs_feelings', [] );

        $feedback[$feelings] = ( isset( $feedback[$feelings] ) ? intval( $feedback[$feelings] ) : 0 ) + 1;
        if ( update_option( '_betterdocs_feelings', $feedback, 'no' ) ) {
            return true;
        }
        return false;
    }
}
