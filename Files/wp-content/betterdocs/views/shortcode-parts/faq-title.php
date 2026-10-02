<div class="betterdocs-faq-post">
	<?php
		
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
		 * do_action(')
		 */

		do_action( 'betterdocs_faq_post_before', $faq_toggle );
	?>
	<p class="betterdocs-faq-post-name">
		<?php echo esc_html( get_the_title() ); ?>
	</p>
	<?php
		/**
		 * do_action(')
		 */

		do_action( 'betterdocs_faq_post_after', $faq_toggle );
	?>
</div>
