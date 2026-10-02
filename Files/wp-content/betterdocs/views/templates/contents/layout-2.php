<?php 
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$reading_text    = betterdocs()->settings->get( 'estimated_reading_time_text' );
$singular_reading_text = betterdocs()->settings->get( 'singular_estimated_reading_time_text' );
$reading_title         = betterdocs()->settings->get( 'estimated_reading_time_title' );
$article_summary 	   = betterdocs()->settings->get( 'enable_article_summary', false );
?>
<?php echo betterdocs()->settings->get( 'enable_estimated_reading_time' ) ? do_shortcode( '[betterdocs_reading_time singular_reading_text="' . $singular_reading_text . '" reading_text="' . $reading_text . '" reading_title="' . $reading_title . '"]' ) : ''; ?>
<div class="betterdocs-entry-content">
	<?php
		/**
		 * Print Icon
		 */
		$view_object->get(
			'templates/parts/print-icon',
			[
				'enable' => betterdocs()->settings->get( 'enable_print_icon', false )
			]
		);

		/**
		 * Article Summary
		 */
		if ( $article_summary ) {
			$view_object->get( 'templates/parts/article-summary' );
		}

		/**
		 * Content
		 */
		$view_object->get( 'templates/parts/content' );
		?>
</div>
