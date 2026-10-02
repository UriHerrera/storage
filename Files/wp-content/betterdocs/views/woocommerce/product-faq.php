<?php
/**
 * Product FAQ list rendered on single WooCommerce product pages.
 *
 * Mirrors views/shortcodes/faq.php but pulls terms from BOTH the general and
 * product FAQ taxonomies (group IDs are globally unique), and reuses the shared
 * faq-term-title / faq-list parts so the markup and styles match the shortcode.
 *
 * @var int[]  $group_ids     Resolved FAQ group term IDs to display.
 * @var string $layout        Layout class (layout-1..3; layout-4/Retro is not
 *                            offered for product FAQs).
 * @var bool   $enable_schema  Whether to emit FAQPage JSON-LD.
 * @var \WPDeveloper\BetterDocs\Utils\Views $view_object
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $group_ids ) ) {
	return;
}

// Respect the FAQ Builder's ordering preference (manual drag-drop by default),
// mirroring the regular FAQ shortcode instead of a hardcoded alphabetical sort.
$terms = get_terms( array_merge(
	[
		'taxonomy'   => [ 'betterdocs_faq_category', 'betterdocs_product_faq_category' ],
		'include'    => $group_ids,
		'hide_empty' => false,
	],
	betterdocs()->query->faq_terms_order_clause()
) );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

// Mirror the FAQ block/shortcode wrapper classes so the saved layout actually
// applies: `layout-classic`/`layout-modern` drives Classic vs Modern styling and
// `betterdocs-faq-<layout>` drives the Abstract (layout-3) styles. Passing the
// raw "layout-3" alone matched no CSS, so every layout looked the same.
$layout_variant = ( 'layout-2' === $layout ) ? 'layout-classic' : 'layout-modern';
$wrapper_class  = trim(
	'betterdocs-faq-wrapper betterdocs-woo-product-faq ' . $layout_variant
	. ' betterdocs-faq-' . esc_attr( $layout ) . ' icon-after'
);
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>">
	<div class="betterdocs-faq-inner-wrapper">
		<?php
		// Group Title Tag is driven by the "Product FAQ for WooCommerce"
		// customizer control so the heading element is configurable.
		$faq_group_title_tag = get_theme_mod( 'betterdocs_faq_group_title_tag_woo', 'h3' );

		$faq_json = '';
		if ( $enable_schema ) {
			$faq_json = [
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => [],
			];

			$GLOBALS['betterdocs_faq_schema_main_entity'] = [];
		}

		foreach ( $terms as $term ) {
			// Skip groups with no *published* FAQs. $term->count can include
			// drafts/other statuses, which would render the group title above an
			// empty "no FAQ" body. The display query is publish-only, so gate on
			// it directly.
			$faqs = betterdocs()->query->get_faq_by_term( $term->term_id, $term->taxonomy );
			$has_published = $faqs->have_posts();
			wp_reset_postdata();
			if ( ! $has_published ) {
				continue;
			}

			$view_object->get( 'shortcode-parts/faq-term-title', [
				'title'               => $term->name,
				'faq_group_title_tag' => $faq_group_title_tag,
			] );

			$view_object->get( 'shortcode-parts/faq-list', [
				'term'       => $term,
				'faq_schema' => $enable_schema,
				'faq_json'   => $enable_schema ? $faq_json['mainEntity'] : '',
			] );
		}

		if ( $enable_schema ) {
			$faq_json['mainEntity'] = $GLOBALS['betterdocs_faq_schema_main_entity'];
			echo '<script type="application/ld+json">' . wp_json_encode( $faq_json ) . '</script>';
		}
		?>
	</div>
</div>
