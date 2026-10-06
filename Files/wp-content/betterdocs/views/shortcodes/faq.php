<div
	<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contains attriutes and values together which are required here ?>>
	<?php
		$section_tag = betterdocs()->template_helper->is_valid_tag( $faq_section_title_tag );
		echo wp_kses_post( '<' . $section_tag . ' class="' . esc_attr( $faq_heading_class ) . ' betterdocs-faq-section-title">' . esc_html( $faq_heading ) . '</' . $section_tag . '>' );
	?>

	<div class="betterdocs-faq-inner-wrapper">
		<?php
			$terms    = get_terms( $terms_query_args );
			$faq_json = '';

		if ( ! is_wp_error( $terms ) ) {
			$GLOBALS['betterdocs_faq_schema'] = [];
			if ( $faq_schema ) {
				$faq_json = [
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => []
				];

				$GLOBALS['betterdocs_faq_schema_main_entity'] = $faq_json['mainEntity'];
			}

			foreach ( $terms as $term ) {
				if ( $term->count <= 0 ) {
					continue;
				}

				// title
				$view_object->get(
					'shortcode-parts/faq-term-title',
					[
						'title' => $term->name
					]
				);

				// faq list
				$view_object->get(
					'shortcode-parts/faq-list',
					[
						'term'       => $term,
						'faq_schema' => $faq_schema,
						'faq_json'   => $faq_schema ? $faq_json['mainEntity'] : ''
					]
				);
			}

			if ( $faq_schema ) {
				$faq_json['mainEntity'] = $GLOBALS['betterdocs_faq_schema_main_entity'];
				echo '<script type="application/ld+json">' . wp_json_encode( $faq_json ) . '</script>';
			}
		}
		?>
	</div>
</div>
