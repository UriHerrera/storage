<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="betterdocs-faq-main-content"<?php echo $faq_toggle ? ' style="display:block;"' : ''; ?>>
	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- intentional WP core filter integration to render FAQ content.
	echo wp_kses_post( apply_filters( 'the_content', get_the_content() ) );
	?>
</div>
