<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
// phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- HTML email assets must be remote.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$from_date = isset( $args['views']['from_date'] ) ? date( 'M j, Y', strtotime( $args['views']['from_date'] ) ) : ''; //phpcs:ignore
    $to_date   = isset( $args['views']['to_date'] ) ? date( 'M j, Y', strtotime( $args['views']['to_date'] ) ) : ''; //phpcs:ignore
?>
<tr style="background: #26d67d">
	<td style="padding: 20px 40px">
		<table style="width: 100%; border-spacing: 0;">
			<tr>
				<td>
					<img src="https://betterdocs.co/wp-content/uploads/2022/12/BetterDocs-logo-white.png" style="max-width: 100%; height: 35px" alt="logo" />
				</td>
				<td align="right">
					<span style="font-size: 12px; line-height: 1em; text-align: right; color: #fff; flex-direction: column; column-gap: 5px;">
						<span style="display:block; margin-bottom: 5px; font-weight: 600; font-size: 18px;">
							<?php echo wp_sprintf( '%s %s', esc_html__( 'Last', 'betterdocs' ), esc_html( $days ) ); ?>
						</span>
						<span>
							<?php
							if ( $frequency == 'betterdocs_daily' ) {
								echo esc_html( $from_date );
							} else {
								echo wp_sprintf( '%s - %s', esc_html( $from_date ), esc_html( $to_date ) );
							}
							?>
						</span>
					</span>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr style="background: #f7f9fb"> <!-- tr table body wrapper start -->
	<td style="padding: 50px">
		<table cellpadding="0" cellspacing="0" border="0" align="center">
			<tbody>
				<tr>
					<td style="text-align: center; font-size: 20px; font-weight: bold; padding-bottom: 40px;">
						<?php
							printf(
								// translators: %s is the website name.
								esc_html__( 'Your Documentation Performance of %s Website', 'betterdocs' ),
								esc_html( $bloginfo )
							);
							?>
					</td>
				</tr>
				<?php
					$_analytics_body = [
						'overview'       => function () use ( $frequency, $args, $report_email ) {
							betterdocs()->views->get(
								'admin/email/analytics/overview',
								[
									'frequency'    => $frequency,
									'args'         => $args,
									'report_email' => $report_email
								]
							);
						},
						'leading-docs'   => function () use ( $frequency, $args ) {
							betterdocs()->views->get(
								'admin/email/analytics/leading-docs',
								[
									'frequency'       => $frequency,
									'args'            => $args['docs']['current_data'],
									'total_reactions' => $args['docs']['total_current_reactions']
								]
							);
						},
						'search-keyword' => function () use ( $frequency, $args ) {
							betterdocs()->views->get(
								'admin/email/analytics/search-keyword',
								[
									'frequency' => $frequency,
									'keywords'  => $args['search']['keywords']
								]
							);
						}

					];

					$_analytics_body = apply_filters( 'betterdocs_analytics_reporting_tables', $_analytics_body, $args, $frequency, $report_email );

					if ( is_array( $_analytics_body ) ) {
						foreach ( $_analytics_body as $_analytics_func ) {
							$_analytics_func();
						}
					}

					betterdocs()->views->get(
						'admin/email/pro',
						[
							'analytics_url' => admin_url( 'admin.php?page=betterdocs-analytics' ),
							'is_pro'        => betterdocs()->is_pro_active()
						]
					);

					// body is completed in admin/email/footer.
