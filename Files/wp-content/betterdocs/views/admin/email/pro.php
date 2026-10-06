<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
// phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- HTML email assets must be remote.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$image_url = '';
if ( ! $is_pro ) :
	$image_url = 'https://betterdocs.co/wp-content/uploads/2022/11/Analytics.gif';
	?>
	<tr>
		<td style="padding-top: 40px;">
			<table style="width: 100%; background: rgba(38, 214, 125, 0.1); padding: 20px 20px 20px 20px; border-radius: 5px; border-spacing: 0;">
				<tr>
					<td style="display: flex; gap: 10px;">
						<span style="margin-top: 4px; margin-right: 3px;">💡</span>
						<p style="float: right; font-size: 14px; line-height: 1.7em; font-weight: 400; text-align: justify; margin: 0;">
							<?php
							printf(
								/* translators: 1: Feature name (e.g., Email Reporting) 2: Version name (e.g. FREE) */
								esc_html__( 'This advanced %1$s feature is part of our premium built-in Analytics tool available only with BetterDocs PRO, but you are getting it for %2$s!', 'betterdocs' ),
								'<span style="font-weight: 700; color: #1d1d1f; text-decoration: none;">' . esc_html__( 'Email Reporting', 'betterdocs' ) . '</span>',
								'<span style="font-weight: 700; color: #1d1d1f; text-decoration: none;">' . esc_html__( 'FREE', 'betterdocs' ) . '</span>'
							);
							?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td style="padding-top: 40px;">
			<table style="width: 100%; background: #fff; padding: 10px; border-spacing: 0;">
				<tr>
					<td>
						<img style="display: block; max-width: 100%;" src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr_e( 'View Analytics', 'betterdocs' ); ?>">
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="center" style="padding-top: 30px;">
			<a href="https://betterdocs.co/upgrade" style="text-decoration: none; font-size: 12px; font-weight: 600; color: #fff; padding: 16px 22px; border: none; background: #26d67d; border-radius: 10px; cursor: pointer; display: inline-block; letter-spacing: 1px;">
				<?php esc_html_e( 'Unlock BetterDocs Analytics', 'betterdocs' ); ?> <span style="padding-left: 5px;">🔐</span>
			</a>
		</td>
	</tr>
<?php else : ?>
	<tr>
		<td style="padding-top: 40px;" align="center">
			<a style="display:inline-block; text-decoration: none; font-size: 12px; font-weight: 600; color: #fff; padding: 16px 22px; border: none; background: #26d67d; border-radius: 10px; cursor: pointer; display: inline-block; letter-spacing: 1px;" href="<?php echo esc_url( $analytics_url ); ?>" target="_blank">
				<?php esc_html_e( 'View Analytics', 'betterdocs' ); ?>
			</a>
		</td>
	</tr>
<?php endif; ?>
<tr>
	<td style="padding-top: 20px;">
		<table style="border-spacing: 0;">
			<tr>
				<td style="display: flex;">
					<span style="margin-right: 10px; margin-top: 5px;">📚</span>
					<p style="text-align: justify; font-size: 14px; line-height: 1.7em; font-weight: 400; margin: 0;">
						<?php
						printf(
							/* translators: 1. Complete Guide To BetterDocs Analytics 2: Key Customer Service Metrics To Measure Performance 3: How To Use BetterDocs Internal Knowledge Base  */
							esc_html__( 'Want to know what more you can do with BetterDocs? Check out these tutorials: %1$s, %2$s, and %3$s.', 'betterdocs' ),
							'<a href="https://betterdocs.co/blog/improved-betterdocs-analytics/" style="font-weight: 500; color: #1d1d1f; font-style: italic;">' . esc_html__( 'Complete Guide To BetterDocs Analytics', 'betterdocs' ) . '</a>',
							'<a href="https://betterdocs.co/blog/key-customer-service-metrics/" style="font-weight: 500; color: #1d1d1f; font-style: italic;">' . esc_html__( 'Key Customer Service Metrics To Measure Performance', 'betterdocs' ) . '</a>',
							'<a href="https://betterdocs.co/blog/internal-documentation-wordpress/" style="font-weight: 500; color: #1d1d1f; font-style: italic;">' . esc_html__( 'How To Use BetterDocs Internal Knowledge Base', 'betterdocs' ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
	</td>
</tr>
