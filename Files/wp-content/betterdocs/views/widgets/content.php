<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! is_singular( 'docs' ) ) {
	return;
}
?>

<div
	<?php echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	if ( isset( $widget_type ) && $widget_type != 'blocks' ) {
		betterdocs()->views->get(
			'templates/parts/print-icon',
			[
				'enable' => (bool) $enable
			]
		);
	}

		// The Content For A Docs
		$view_object->get(
			'templates/parts/content',
			[
				'htags'      => $htags,
				'enable_toc' => $enable_toc
			]
		);
		?>
</div>
