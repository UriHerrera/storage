<?php
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
	 * @var object $docs
	 */
?>
<tr style="background: #fff;">
	<td style="text-align: left; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">
		<a style="text-decoration:none;color:#222;" href="<?php echo esc_attr( esc_url( get_permalink( $docs->ID ) ) ); ?>">
			<?php echo esc_html( $docs->title ); ?>
		</a>
	</td>
	<td style="text-align: center; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">
		<?php echo esc_html( $docs->total_views ); ?>
	</td>
	<td style="text-align: center; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">
		<?php echo esc_html( $docs->total_unique_visit ); ?>
	</td>
	<?php
	if ( $total_reactions > 0 ) {
		echo '<td style="text-align: center; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">' . esc_html( $docs->total_reactions ) . '</td>';
	}
	?>
</tr>
