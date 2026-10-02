<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
// phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- HTML email assets must be remote.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
	 * @var object $keyword
	 */

if ( $keyword->count > 0 ) {
	$found_icon = '<img style="width: 14px;" src="https://betterdocs.co/wp-content/uploads/2022/12/tik.png" alt="" />';
} else {
	$found_icon = '<img style="width: 14px;" src="https://betterdocs.co/wp-content/uploads/2022/12/nontik.png" alt="" />';
}

?>

<tr style="background: #fff;">
	<td style="text-align: left; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">
		<?php echo esc_html( $keyword->keyword ); ?>
	</td>
	<td style="text-align: center; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">
		<?php echo esc_html( $keyword->total_search ); ?>
	</td>
	<td style="text-align: center; font-size: 14px; color:#1d1d1f; border-width: 1px; border-style: solid; border: none; padding: 8px 15px;">
		<?php echo wp_kses_post( $found_icon ); ?>
	</td>
</tr>
