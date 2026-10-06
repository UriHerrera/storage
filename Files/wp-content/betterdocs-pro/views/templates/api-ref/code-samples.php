<?php
/**
 * API Documentation — sticky code panels for the right ToC sidebar.
 *
 * Rendered by WPDeveloper\BetterDocsPro\Core\ApiDocs\Frontend::render_sidebar_code_samples().
 * Both panels live in one sticky wrapper so Request + Response scroll and stick
 * together as a single column.
 *
 * @var string $rendered          Pre-rendered request Code Samples block HTML.
 * @var string $rendered_response Pre-rendered Code Snippet Tab block HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $rendered ) && empty( $rendered_response ) ) {
	return;
}
?>
<div class="betterdocs-api-code-aside-group">
	<?php if ( ! empty( $rendered ) ) : ?>
		<div class="betterdocs-api-code-aside">
			<h3 class="betterdocs-api-code-aside__title"><?php esc_html_e( 'Request', 'betterdocs-pro' ); ?></h3>
			<div class="betterdocs-api-code-aside__body">
				<?php echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks() output. ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $rendered_response ) ) : ?>
		<div class="betterdocs-api-code-aside betterdocs-api-code-aside--response">
			<h3 class="betterdocs-api-code-aside__title"><?php esc_html_e( 'Response', 'betterdocs-pro' ); ?></h3>
			<div class="betterdocs-api-code-aside__body">
				<?php echo $rendered_response; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks() output. ?>
			</div>
		</div>
	<?php endif; ?>
</div>
