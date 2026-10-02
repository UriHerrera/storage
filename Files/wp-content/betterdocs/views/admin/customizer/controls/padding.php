<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="number-field">
	<?php
		$controller_id = esc_attr( $control->id );
		$val_top       = get_theme_mod( $control->id . '_top' );
		$val_left      = get_theme_mod( $control->id . '_left' );
	?>
<?php if ( ! empty( $control->label ) ) : ?>
	<span class="customize-control-title">
		<?php echo esc_html( $control->label ); ?>
	</span>
	<a
		href="#"
		title="<?php echo esc_html__( 'Reset', 'betterdocs' ); ?>"
		class="<?php echo esc_html( $control->type ); ?> betterdocs-customizer-reset">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" width="20px"><path d="M 25 2 C 12.321124 2 2 12.321124 2 25 C 2 37.678876 12.321124 48 25 48 C 37.678876 48 48 37.678876 48 25 A 2.0002 2.0002 0 1 0 44 25 C 44 35.517124 35.517124 44 25 44 C 14.482876 44 6 35.517124 6 25 C 6 14.482876 14.482876 6 25 6 C 30.475799 6 35.391893 8.3080175 38.855469 12 L 35 12 A 2.0002 2.0002 0 1 0 35 16 L 46 16 L 46 5 A 2.0002 2.0002 0 0 0 43.970703 2.9726562 A 2.0002 2.0002 0 0 0 42 5 L 42 9.5253906 C 37.79052 4.9067015 31.727675 2 25 2 z"></path></svg>
	</a>
	<?php endif; ?>
	<ul>
		<li id="<?php echo esc_attr( $controller_id ) . '_top'; ?>" class="customize-control">
			<input
				type="number"
				data-default-val="20"
				class="<?php echo esc_attr( $control->type ); ?>"
				value="20"
				data-customize-setting-link="<?php echo esc_attr( $controller_id ) . '_top'; ?>"
			/>
		</li>
		<li id="<?php echo esc_attr( $controller_id ) . '_left'; ?>" class="customize-control">
			<input
				type="number"
				data-default-val="20"
				class="<?php echo esc_attr( $control->type ); ?>"
				value="20"
				data-customize-setting-link="<?php echo esc_attr( $controller_id ) . '_left'; ?>"
			/>
		</li>
	</ul>
</div>
