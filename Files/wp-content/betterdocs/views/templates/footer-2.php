<div class="betterdocs-entry-footer">
	<?php
		
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
		 * Tags
		 */
		echo '<div class="betterdocs-tags-print">';
			$view_object->get( 'templates/parts/tags' );
			$view_object->get(
				'templates/parts/print-icon-2',
				[
					'enable' => betterdocs()->settings->get( 'enable_print_icon', false )
				]
			);
			echo '</div>';
			/**
			 * Social Share
			 */
			do_action( 'betterdocs_docs_before_social' );
			$view_object->get( 'templates/parts/social-2' );


			/**
			 * Feedback Form
			 */
			//$view_object->get( 'templates/feedback-form' );
			?>
</div> <!-- .entry-footer -->
