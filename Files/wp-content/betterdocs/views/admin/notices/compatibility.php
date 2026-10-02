<div class="warning notice">
	<p>
		<?php
			
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
printf(
				/* translators: %s: installed BetterDocs Pro version */
				__( 'You are using <strong>BetterDocs Pro %s</strong>. BetterDocs Analytics requires <strong>BetterDocs Pro 4.0.0</strong> or newer — please <strong><em>update</em></strong> BetterDocs Pro for a smooth experience.', 'betterdocs' ), //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string with required HTML tags; version arg is escaped.
				esc_html( $version )
			);
			?>
	</p>
</div>
