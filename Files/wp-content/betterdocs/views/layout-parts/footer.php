<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $show_button ) {
	return;
}
?>

<div class="betterdocs-footer">
	<?php
	if ( $show_button ) {
		$view_object->get( 'template-parts/explore-more-btn' );
	}
	?>
</div>
