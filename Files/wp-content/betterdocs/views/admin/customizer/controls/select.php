<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<select
	<?php $control->link(); ?>
	data-default-val="<?php echo esc_attr( $control->settings['default']->value() ); ?>"
	<?php echo $control->input_attrs(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	foreach ( $control->choices as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
	}
	?>
</select>

<?php if ( ! empty( $control->label ) ) : ?>
	<span class="customize-control-title betterdocs-customize-control-title">
		<?php echo esc_html( $control->label ); ?>
	</span>
<?php endif; ?>
