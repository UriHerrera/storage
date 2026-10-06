<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! betterdocs()->settings->get( 'enable_credit' ) ) {
	return;
}

?>

<div class="betterdocs-credit">
	<p>
		<?php
			printf(
				'%s <a href="%s" target="_blank">%s</a>',
				esc_html__( 'Powered by ', 'betterdocs' ),
				esc_attr( esc_url( 'https://betterdocs.co' ) ),
				esc_html__( 'BetterDocs', 'betterdocs' )
			);
			?>
	</p>
</div>
