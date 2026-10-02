<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( empty( $args ) || ! is_array( $args ) ) {
	return;
}

?>

<tr>
	<td style="text-align: left; font-size: 20px; padding-top: 40px;">
		<?php esc_html_e( 'Top Performing Docs', 'betterdocs' ); ?>
	</td>
</tr>
<tr>
	<td style="padding:0">
		<table style="width: 100%; border-collapse: separate; border-spacing: 2px; padding-top: 20px;">
			<tr style="background: #fff;">
				<th style="text-align: left; font-size: 14px; font-weight: 600; color:#6e6e73; text-transform: capitalize; padding: 8px 15px; width: 48%;">
					<?php esc_html_e( 'Doc Title', 'betterdocs' ); ?>
				</th>
				<th style="text-align: center; font-size: 14px; font-weight: 600; color:#6e6e73; text-transform: capitalize; padding: 8px 8px;">
					<?php esc_html_e( 'Total Views', 'betterdocs' ); ?>
				</th>
				<th style="text-align: center; font-size: 14px; font-weight: 600; color:#6e6e73; text-transform: capitalize; padding: 8px 8px;">
					<?php esc_html_e( 'Unique Views', 'betterdocs' ); ?>
				</th>
				<?php
				if ( $total_reactions > 0 ) {
					echo '<th style="text-align: center; font-size: 14px; font-weight: 600; color:#6e6e73; text-transform: capitalize; padding: 8px 15px;">' . esc_html__( 'Reactions', 'betterdocs' ) . '</th>';
				}
				?>
			</tr>
			<?php
			foreach ( $args as $docs ) {
				betterdocs()->views->get(
					'admin/email/analytics/parts/single-docs',
					[
						'docs'            => $docs,
						'total_reactions' => $total_reactions
					]
				);
			}
			?>
		</table>
	</td>
</tr>
