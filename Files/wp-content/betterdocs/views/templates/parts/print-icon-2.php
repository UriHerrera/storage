<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $enable ) {
	return;
}

	$wrapper_class = [
		'class' => 'betterdocs-print-pdf-2'
	];

	if ( isset( $widget_type ) && $widget_type == 'blocks' ) {
		$wrapper_class['class'] .= ' ' . $blockId;
	}

	$wrapper_class = betterdocs()->template_helper->get_html_attributes( $wrapper_class );
	?>

<div <?php echo $wrapper_class; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="betterdocs-print-btn-2">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<mask id="mask0_7329_2211" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="20">
			<rect width="20" height="20" fill="#D9D9D9"/>
			</mask>
			<g mask="url(#mask0_7329_2211)">
			<path d="M13.3332 6.66667V4.16667H6.6665V6.66667H4.99984V2.5H14.9998V6.66667H13.3332ZM14.9998 10.4167C15.2359 10.4167 15.4339 10.3368 15.5936 10.1771C15.7533 10.0174 15.8332 9.81944 15.8332 9.58333C15.8332 9.34722 15.7533 9.14931 15.5936 8.98958C15.4339 8.82986 15.2359 8.75 14.9998 8.75C14.7637 8.75 14.5658 8.82986 14.4061 8.98958C14.2464 9.14931 14.1665 9.34722 14.1665 9.58333C14.1665 9.81944 14.2464 10.0174 14.4061 10.1771C14.5658 10.3368 14.7637 10.4167 14.9998 10.4167ZM13.3332 15.8333V12.5H6.6665V15.8333H13.3332ZM14.9998 17.5H4.99984V14.1667H1.6665V9.16667C1.6665 8.45833 1.90956 7.86458 2.39567 7.38542C2.88178 6.90625 3.47206 6.66667 4.1665 6.66667H15.8332C16.5415 6.66667 17.1353 6.90625 17.6144 7.38542C18.0936 7.86458 18.3332 8.45833 18.3332 9.16667V14.1667H14.9998V17.5ZM16.6665 12.5V9.16667C16.6665 8.93056 16.5866 8.73264 16.4269 8.57292C16.2672 8.41319 16.0693 8.33333 15.8332 8.33333H4.1665C3.93039 8.33333 3.73248 8.41319 3.57275 8.57292C3.41303 8.73264 3.33317 8.93056 3.33317 9.16667V12.5H4.99984V10.8333H14.9998V12.5H16.6665Z" fill="#667085"/>
			</g>
		</svg>
	</span>
</div>
