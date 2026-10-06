<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="betterdocs-settings-right">
	<div class="betterdocs-sidebar">
		<div class="betterdocs-sidebar-block">
			<div class="betterdocs-admin-sidebar-logo">
				<img alt="BetterDocs" src="<?php echo esc_url( betterdocs()->assets->icon( 'betterdocs-icon.svg', true ) ); ?>">
			</div>
			<div class="betterdocs-admin-sidebar-cta">
				<?php
				if ( class_exists( 'Betterdocs_Pro' ) ) {
					echo wp_kses_post(
						sprintf(
							// Translators: %s is the URL to manage the license.
							__( '<a rel="nofollow" href="%s" target="_blank">Manage License</a>', 'betterdocs' ),
							esc_url( 'https://wpdeveloper.com/account' )
						)
					);
				} else {
					echo wp_kses_post(
						sprintf(
							// Translators: %s is the URL to upgrade to the Pro version.
							__( '<a rel="nofollow" href="%s" target="_blank">Upgrade to Pro</a>', 'betterdocs' ),
							esc_url( 'https://betterdocs.co/upgrade' )
						)
					);
				}
				?>
			</div>
		</div>
		<div class="betterdocs-sidebar-block betterdocs-license-block">
			<?php do_action( 'betterdocs_licensing' ); ?>
		</div>
	</div>
</div>
