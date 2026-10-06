<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="dimension-field">
	<input
		type="number"
		data-default-val="<?php echo esc_attr( $control->settings['default']->value() ); ?>"
		value="<?php echo esc_attr( $control->value() ); ?>"
		<?php
			$control->input_attrs();
			$control->link();
		?>
	/>
	<span class="customize-control-title betterdocs-customize-control-title">
		<?php echo esc_html( $control->label ); ?>
	</span>
</div>
