<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$email_feedback = betterdocs()->settings->get( 'email_feedback' );

if ( ! $email_feedback ) {
	return;
}

	// for archives
	$feedback_icon      = betterdocs()->customizer->defaults->get( 'betterdocs_single_doc_feedback_icon' );
	$feedback_title_tag = betterdocs()->customizer->defaults->get( 'betterdocs_feedback_form_title_tag' );
	$feedback_title_tag = betterdocs()->template_helper->is_valid_tag( $feedback_title_tag );

	$feedback_title = betterdocs()->settings->get( 'feedback_form_title' );
	$flink_text     = betterdocs()->settings->get( 'feedback_link_text' );
	$flink_url      = betterdocs()->settings->get( 'feedback_url' );
	$flink_url_href = $flink_url ? esc_url( $flink_url ) : '#betterdocs-form-modal';

	$shortcode_attributes = [ 'button_text' => $button_text, 'feedback_form_name_label_text' => $feedbackFormNameLabelText, 'feedback_form_email_label_text' => $feedbackFormEmailLabelText, 'feedback_form_message_label_text' => $feedbackFormMessageLabelText ];

	$flink_attribute = [
		'class' => [ 'feedback-form-link' ],
		'href'  => $flink_url_href
	];

	if ( ! $flink_url ) {
		$flink_attribute['name'] = 'betterdocs-form-modal';
	}

	$flink_attribute      = betterdocs()->template_helper->get_html_attributes( $flink_attribute );
	$shortcode_attributes = betterdocs()->template_helper->get_html_attributes( $shortcode_attributes );

	?>
<div <?php echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<a
		<?php echo $flink_attribute; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<span class="feedback-form-icon">
				<?php if ( $feedback_icon != '' ) : ?>
					<img src="<?php echo esc_attr( esc_url( $feedback_icon ) ); ?>" />
				<?php else : ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" viewBox="0 0 64 64">
						<linearGradient id="zWPy7gPuySZ8fg4Y3QF24a" x1="26" x2="26" y1="630.833" y2="619.332"
							gradientTransform="matrix(1 0 0 -1 0 654)" gradientUnits="userSpaceOnUse"
							spreadMethod="reflect">
							<stop offset="0" stop-color="#6dc7ff"></stop>
							<stop offset="1" stop-color="#e6abff"></stop>
						</linearGradient>
						<path fill="url(#zWPy7gPuySZ8fg4Y3QF24a)"
							d="M15.082,25.762l9.625,8.141c0.746,0.633,1.84,0.633,2.59,0l9.621-8.141 C37.629,25.16,37.203,24,36.27,24H15.73C14.797,24,14.371,25.16,15.082,25.762z">
						</path>
						<linearGradient id="zWPy7gPuySZ8fg4Y3QF24b" x1="26" x2="26" y1="647.5" y2="596.439"
							gradientTransform="matrix(1 0 0 -1 0 654)" gradientUnits="userSpaceOnUse"
							spreadMethod="reflect">
							<stop offset="0" stop-color="#1a6dff"></stop>
							<stop offset="1" stop-color="#c822ff"></stop>
						</linearGradient>
						<path fill="url(#zWPy7gPuySZ8fg4Y3QF24b)" d="M18,49h16v2H18V49z"></path>
						<linearGradient id="zWPy7gPuySZ8fg4Y3QF24c" x1="32" x2="32" y1="8.915" y2="55.387"
							gradientUnits="userSpaceOnUse" spreadMethod="reflect">
							<stop offset="0" stop-color="#1a6dff"></stop>
							<stop offset="1" stop-color="#c822ff"></stop>
						</linearGradient>
						<path fill="url(#zWPy7gPuySZ8fg4Y3QF24c)"
							d="M48,9c-6.134,0-11.277,4.276-12.637,10H8c-2.758,0-5,2.242-5,5v26c0,2.758,2.242,5,5,5h36 c2.758,0,5-2.242,5-5V35h2v-2h-3c-6.066,0-11-4.934-11-11s4.934-11,11-11s11,4.934,11,11v3c0,1.102-0.898,2-2,2s-2-0.898-2-2v-3 c0-3.859-3.141-7-7-7s-7,3.141-7,7s3.141,7,7,7c2.125,0,4.027-0.953,5.312-2.453C53.918,27.984,55.344,29,57,29c2.207,0,4-1.793,4-4 v-3C61,14.832,55.168,9,48,9z M5,24.109L17.086,34L5,43.891V24.109z M47,50c0,1.652-1.348,3-3,3H8c-1.652,0-3-1.348-3-3v-3.527 l13.668-11.18l4.168,3.41c0.914,0.75,2.039,1.125,3.164,1.125s2.25-0.375,3.164-1.125l4.172-3.41L47,46.473V50z M47,34.949v8.941 L34.914,34l3.618-3.12C40.691,33.18,43.668,34.694,47,34.949z M37.264,29.317l-9.365,7.835c-1.102,0.902-2.699,0.902-3.801,0 L5.699,22.098C6.25,21.434,7.07,21,8,21h27.051C35.025,21.331,35,21.662,35,22C35,24.712,35.837,27.231,37.264,29.317z M48,27 c-2.758,0-5-2.242-5-5s2.242-5,5-5s5,2.242,5,5S50.758,27,48,27z">
						</path>
					</svg>
				<?php endif; ?>
			</span>
			<?php echo $formContent; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>

	<div id="betterdocs-form-modal" class="betterdocs-modalwindow <?php echo esc_attr( $blockId ); ?>">
		<div class="modal-inner">
			<div class="modal-content">
				<a href="#" class="close"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16px" viewBox="0 0 50 50" version="1.1"><g id="surface1"><path style=" " d="M 9.15625 6.3125 L 6.3125 9.15625 L 22.15625 25 L 6.21875 40.96875 L 9.03125 43.78125 L 25 27.84375 L 40.9375 43.78125 L 43.78125 40.9375 L 27.84375 25 L 43.6875 9.15625 L 40.84375 6.3125 L 25 22.15625 Z "></path></g></svg></a>
				<?php
					echo wp_sprintf(
						'<%1$s class="feedback-form-title">%2$s</%1$s>',
						$feedback_title_tag, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						( $formTitle ) ? stripslashes( $formTitle ) : esc_html__( 'How can we help?', 'betterdocs' ) //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);

					echo wp_sprintf(
						'<div class="modal-content-inner"><!-- shortcode -->%s<!-- shortcode end --></div>',
						do_shortcode( '[betterdocs_feedback_form ' . $shortcode_attributes . ']' )
					);
					?>
			</div>


		</div>
	</div>
</div>
