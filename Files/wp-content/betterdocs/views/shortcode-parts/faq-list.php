<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$faq_taxonomy = isset( $term->taxonomy ) ? $term->taxonomy : 'betterdocs_faq_category';
$faqs         = betterdocs()->query->get_faq_by_term( $term->term_id, $faq_taxonomy );

if ( $faqs->have_posts() ) {
	echo '<ul class="betterdocs-faq-list">';

	$faq_markup = '';
	while ( $faqs->have_posts() ) :
		$faqs->the_post();
		$faq_toggle = (bool)get_post_meta(get_the_ID(), 'faq_open_by_default', true);
		echo '<li>';
		echo '<div class="betterdocs-faq-group'.($faq_toggle ? ' active' : '').'">';
		$view_object->get( 'shortcode-parts/faq-title', [
			'faq_toggle' => $faq_toggle
		] );
		$view_object->get( 'shortcode-parts/faq-content', [
			'faq_toggle' => $faq_toggle
		] );
		echo '</div>';
		echo '</li>';

		if ( $faq_schema ) {
			$faq_json                                       = [
				'@type'          => 'Question',
				'name'           => get_the_title(),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => get_the_content()
				]
			];
			$GLOBALS['betterdocs_faq_schema_main_entity'][] = $faq_json;
		}
	endwhile;
	wp_reset_postdata();
	wp_reset_postdata();
	echo $faq_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</ul>';
} else {
	echo '<p>' . esc_html__( 'Sorry, no FAQ matched your criteria.', 'betterdocs' ) . '</p>';
}
