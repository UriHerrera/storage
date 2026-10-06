<?php
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
	 * @var object $control
	 */

if ( $hasLabel ) :
	?>
	<span class="customize-control-title">
	<?php echo esc_html( $label ); ?>
		<a
			href="#"
			title="<?php esc_attr_e( 'Reset', 'betterdocs' ); ?>"
			class="<?php echo esc_attr( $control->type ); ?> betterdocs-customizer-reset">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" width="20px"><path d="M 25 2 C 12.321124 2 2 12.321124 2 25 C 2 37.678876 12.321124 48 25 48 C 37.678876 48 48 37.678876 48 25 A 2.0002 2.0002 0 1 0 44 25 C 44 35.517124 35.517124 44 25 44 C 14.482876 44 6 35.517124 6 25 C 6 14.482876 14.482876 6 25 6 C 30.475799 6 35.391893 8.3080175 38.855469 12 L 35 12 A 2.0002 2.0002 0 1 0 35 16 L 46 16 L 46 5 A 2.0002 2.0002 0 0 0 43.970703 2.9726562 A 2.0002 2.0002 0 0 0 42 5 L 42 9.5253906 C 37.79052 4.9067015 31.727675 2 25 2 z"></path></svg>
		</a>
	</span>
<?php endif; ?>

<?php if ( $hasDescription ) : ?>
	<span class="description customize-control-description">
		<?php echo esc_html( $description ); ?>
	</span>
<?php endif; ?>

<input type="hidden" value="" class="betterdocs-dimension-control                                                                                                                                   <?php echo esc_attr( $control->id ); ?>" data-customize-setting-link="<?php echo esc_attr( $control->id ); ?>">
<ul class="betterdocs-dimension-fields">
	<li class="betterdocs-dimension-link">
		<span class="dashicons dashicons-admin-links betterdocs-dimension-connected" data-element-connect="<?php echo esc_attr( $control->id ); ?>" title="Link Values Together"></span>
		<span class="dashicons dashicons-editor-unlink betterdocs-dimension-disconnected" data-element-connect="<?php echo esc_attr( $control->id ); ?>" title="Link Values Together"></span>
	</li>
	<li class="dimension-field">
		<input type="number" class="betterdocs-dimension-input betterdocs-dimension-input-1 disconnected" value="<?php echo esc_attr( isset( $dimension_val['input1'] ) ? $dimension_val['input1'] : '0' ); ?>" data-element-connect="<?php echo esc_attr( $control->id ); ?>" data-input="input1">
		<span class="dimension-title"><?php echo esc_html( isset( $control->input_fields['input1'] ) ? $control->input_fields['input1'] : '' ); ?></span>
	</li>
	<li class="dimension-field">
		<input type="number" class="betterdocs-dimension-input betterdocs-dimension-input-2 disconnected" value="<?php echo esc_attr( isset( $dimension_val['input2'] ) ? $dimension_val['input2'] : '0' ); ?>" data-element-connect="<?php echo esc_attr( $control->id ); ?>" data-input="input2">
		<span class="dimension-title"><?php echo esc_html( isset( $control->input_fields['input2'] ) ? $control->input_fields['input2'] : '' ); ?></span>
	</li>
	<li class="dimension-field">
		<input type="number" class="betterdocs-dimension-input betterdocs-dimension-input-3 disconnected" value="<?php echo esc_attr( isset( $dimension_val['input3'] ) ? $dimension_val['input3'] : '0' ); ?>" data-element-connect="<?php echo esc_attr( $control->id ); ?>" data-input="input3">
		<span class="dimension-title"><?php echo esc_html( isset( $control->input_fields['input3'] ) ? $control->input_fields['input3'] : '' ); ?></span>
	</li>
	<li class="dimension-field">
		<input type="number" class="betterdocs-dimension-input betterdocs-dimension-input-4 disconnected" value="<?php echo esc_attr( isset( $dimension_val['input4'] ) ? $dimension_val['input4'] : '0' ); ?>" data-element-connect="<?php echo esc_attr( $control->id ); ?>" data-input="input4">
		<span class="dimension-title"><?php echo esc_html( isset( $control->input_fields['input4'] ) ? $control->input_fields['input4'] : '' ); ?></span>
	</li>
</ul>
