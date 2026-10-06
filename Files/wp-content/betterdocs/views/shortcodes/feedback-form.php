<div class="form-wrapper">
	<div class="response"></div>
	<form id="betterdocs-feedback-form" class="betterdocs-feedback-form" action="" method="post">
		<p>
			<label for="message_name" class="form-name">
				<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo esc_html( $feedback_form_name_label_text ); ?> <span>*</span> <br>
				<input type="text" id="message_name" name="message_name" aria-label="<?php echo esc_html( 'Name', 'betterdocs' ); ?>" value="<?php echo esc_attr( $name ); ?>" />
			</label>
		</p>
		<p>
			<label for="message_email" class="form-email">
				<?php echo esc_html( $feedback_form_email_label_text ); ?> <span>*</span> <br>
				<input type="text" id="message_email" name="message_email" aria-label="<?php echo esc_html( 'Email', 'betterdocs' ); ?>" value="<?php echo esc_attr( $email ); ?>" />
			</label>
		</p>
		<p>
			<label for="message_text" class="form-message">
				<?php echo esc_html( $feedback_form_message_label_text ); ?> <span>*</span> <br>
				<textarea type="text" id="message_text" aria-label="<?php echo esc_html( 'Message', 'betterdocs' ); ?>" name="message_text"></textarea>
			</label>
		</p>
		<div class="feedback-from-button">
			<input type="hidden" name="submitted" value="1">
			<input
				type="submit" name="submit" class="button" aria-label="<?php echo esc_html( 'Submit', 'betterdocs' ); ?>" id="feedback_form_submit_btn"
				value="<?php echo esc_attr( $button_text ); ?>"
			/>
		</div>
	</form>
</div>