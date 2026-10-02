<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! betterdocs()->settings->get( 'enable_post_title' ) ) {
	return;
}
?>

<header class="betterdocs-entry-header">
	<div class="docs-single-title">
		<?php

			/**
			 * Title
			 */
			$view_object->get(
				'templates/parts/title',
				[
					'tag' => betterdocs()->customizer->defaults->get( 'betterdocs_post_title_tag' )
				]
			);
			?>
	</div>
</header> <!-- .entry-header -->
