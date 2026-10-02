<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="betterdocs-alpha-color-picker">
	<?php
		// Output the label and description if they were passed in.
	if ( isset( $control->label ) && '' !== $control->label ) {
		echo '<span class="customize-control-title betterdocs-customize-control-title">' . esc_html( $control->label ) . '</span>';
	}
	if ( isset( $control->description ) && '' !== $control->description ) {
		echo '<span class="description customize-control-description">' . esc_html( $control->label ) . '</span>';
	}

		// Process the palette
	if ( is_array( $control->palette ) ) {
		$palette = implode( '|', $control->palette );
	} else {
		// Default to true.
		$palette = ( false === $control->palette || 'false' === $control->palette ) ? 'false' : 'true';
	}
		// Support passing show_opacity as string or boolean. Default to true.
		$show_opacity = ( false === $control->show_opacity || 'false' === $control->show_opacity ) ? 'false' : 'true';
		// Begin the output.
	?>
	<input
		type="text"
		class="betterdocs-alpha-color-control"
		data-show-opacity="<?php echo esc_attr( $show_opacity ); ?>"
		data-palette="<?php echo esc_attr( $palette ); ?>"
		data-default-color="<?php echo esc_attr( $control->settings['default']->default ); ?>"
		<?php esc_attr( $control->link() ); ?>
	/>
</div>
