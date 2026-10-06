<div class="feedback-update-form">
	<?php
		
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
betterdocs()->views->get( 'templates/feedback-parts/form' );

		betterdocs()->views->get( 'template-parts/update-date' );
	?>
</div>
