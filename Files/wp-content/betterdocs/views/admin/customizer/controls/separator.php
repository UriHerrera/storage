<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<label>
	<h4 class="betterdocs-customize-control-separator"><?php echo esc_html( $control->label ); ?></h4>
	<?php if ( ! empty( $control->description ) ) : ?>
		<span class="description customize-control-description">
			<?php echo esc_html( $control->description ); ?>
		</span>
	<?php endif; ?>
</label>
