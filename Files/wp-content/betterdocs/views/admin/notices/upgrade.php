<div class="betterdocs-plugin-update-message">
	<style>
		.betterdocs-plugin-update-message p:before { content: ''; margin-right: 0; display: none; }
		.betterdocs-plugin-update-message ul {
			list-style: disc;
			padding-left: 15px;
		}

		.betterdocs-plugin-update-message p:first-of-type,
		.betterdocs-plugin-update-message p.betterdocs-major-update-title {
			font-weight: 700;
			margin-top: 25px;
			margin-bottom: 10px;
		}

		.betterdocs-plugin-update-message p.betterdocs-major-update-title {
			border-bottom: 1px solid #ffb900;
			padding-bottom: 10px;
			margin-bottom: 20px;
		}
	</style>

	<?php
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( $major ) {
		printf(
			'<p class="betterdocs-major-update-title">%s</p>',
			__( 'Heads up, Please backup before upgrade!', 'betterdocs' ) //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Not Required For Ecaping Because This Is A Static String Without Html Tags
		);
	}

		/**
		 * @var object $response;
		 */
		echo isset( $response->upgrade_notice ) ? $response->upgrade_notice : ''; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Not Required For Escaping Because The Upgrade Notice Can Contain Html Tags, Which Are Needed For This File
	?>
</div>
<p style="display: none">
