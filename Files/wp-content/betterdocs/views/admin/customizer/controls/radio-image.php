<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( empty( $control->choices ) ) {
	return;
}

	$name = '_customize-radio-' . $control->id;
?>

<?php if ( ! empty( $control->label ) ) : ?>
	<span class="customize-control-title betterdocs-customize-control-title">
		<?php echo esc_html( $control->label ); ?>
	</span>
<?php endif; ?>
<?php if ( ! empty( $control->description ) ) : ?>
	<span class="description customize-control-description">
		<?php echo esc_html( $control->description ); ?>
	</span>
<?php endif; ?>

<div id="input_<?php echo esc_attr( $control->id ); ?>" class="image ui-buttonset">
<?php
foreach ( $control->choices as $value => $label ) {
	if ( isset( $label['pro'] ) && $label['pro'] === true ) :
		?>
	<label class="image-select" id="<?php echo esc_attr( $control->id . $value ); ?>">
		<a
			target="_blank"
			href="<?php echo esc_url( $label['url'] ); ?>">
			<img src="<?php echo esc_url( $label['image'] ); ?>" alt="">
		</a>
		<span class="go-pro"><?php esc_html_e( 'Go Pro', 'betterdocs' ); ?></span>
	</label>
<?php else : ?>
	<input
		type="radio"
		class="image-select"
		value="<?php echo esc_attr( $value ); ?>"
		id="<?php echo esc_attr( $control->id . $value ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
		<?php
			$control->link();
			checked( $control->value(), $value );
		?>
			>
		<label for="<?php echo esc_attr( $control->id . $value ); ?>">
			<img
				src="<?php echo esc_url( $label['image'] ); ?>"
				alt="<?php echo esc_attr( $value ); ?>"
				title="<?php echo esc_attr( isset( $label['label'] ) ? $label['label'] : $value ); ?>" />
		</label>
	</input>
	<?php
	endif;
} // endforeach
?>
</div>
<script>
	jQuery(document).ready(function($) {
		$( '[id="input_<?php echo esc_attr( $control->id ); ?>"]' ).buttonset();
	});
</script>
