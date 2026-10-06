<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$post_id          = get_the_ID();
	$est_reading_text = ! empty( get_post_meta( $post_id, '_betterdocs_est_reading_text', true ) ) ? get_post_meta( $post_id, '_betterdocs_est_reading_text', true ) : '';
?>
<div class="est-reading-box-wrapper">
	<p>You can add estimated reading time to this documentation so visitors can know how much time they need to read this.</p>
	<div class="est-reading-box-outer-wrapper">
		<input name="estimated_reading_text" class="ert-text-field-class" value="<?php echo esc_attr( $est_reading_text ); ?>" placeholder="<?php echo esc_html__( 'Add Reading Time:⌛ Min', 'betterdocs' ); ?>"/>
	</div>
</div>
