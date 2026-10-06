<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<label>
	<div class="betterdocs-customizer-toggle">
		<span class="customize-control-title betterdocs-customize-control-title betterdocs-customizer-toggle-title">
			<?php echo esc_html( $control->label ); ?>
		</span>
		<input
			type="checkbox"
			id="cb<?php echo esc_attr( $control->instance_number ); ?>"
			data-default-val="<?php echo esc_attr( $control->settings['default']->value() ); ?>"
			class="tgl tgl-<?php echo esc_attr( $control->type ); ?>"
			value="<?php echo esc_attr( $control->value() ); ?>"
			<?php
				$control->link();
				checked( $control->value() );
			?>
		/>
		<label for="cb<?php echo esc_attr( $control->instance_number ); ?>" class="tgl-btn"></label>
	</div>
	<?php if ( ! empty( $control->description ) ) : ?>
		<span class="description customize-control-description"><?php echo esc_html( $control->description ); ?></span>
	<?php endif; ?>
</label>
