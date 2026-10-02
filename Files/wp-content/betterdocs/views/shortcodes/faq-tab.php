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

			echo '<div class="betterdocs-faq-tab-wrapper">';
				foreach ( $terms as $term ) {
					if ( $term->count <= 0 ) {
						continue;
					}
					$faq_icon_url = get_term_meta($term->term_id, 'faq_group_icon', true);
					echo '<div class="betterdocs-faq-tab" data-term-id="' . esc_attr( $term->term_id ) . '">';
						echo ( ! empty( $faq_icon_url ) ) ? '<img src="' . esc_url( $faq_icon_url ) . '" width="24" height="24" class="faq-group-image" />' : '';
						echo '<span class="faq-tab-title">' . esc_html( $term->name ) . '</span>';
					echo '</div>';
				}
			echo '</div>';

			echo '<div class="betterdocs-faq-list-wrapper">';
			foreach ( $terms as $term ) {
				if ( $term->count <= 0 ) {
					continue;
				}

				$faq_icon_url = get_term_meta($term->term_id, 'faq_group_icon', true);
				echo '<div class="betterdocs-faq-tab" data-term-id="' . esc_attr( $term->term_id ) . '">';
					echo ( ! empty( $faq_icon_url ) ) ? '<img src="' . esc_url( $faq_icon_url ) . '" width="24" height="24" class="faq-group-image" />' : '';
					echo '<span class="faq-tab-title">' . esc_html( $term->name ) . '</span>';

					$faq_markup  = '<svg class="betterdocs-faq-iconplus" width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_8028_2975)">
						<path d="M5.5 7.5L10.5 12.5L15.5 7.5" stroke="#707E95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</g>
						<defs>
						<clipPath id="clip0_8028_2975">
						<rect width="20" height="20" fill="white" transform="translate(0.5)"/>
						</clipPath>
						</defs>
						</svg>';
					$faq_markup .= '<svg class="betterdocs-faq-iconminus" width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M15.5 12.5L10.5 7.5L5.5 12.5" stroke="#707E95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>';

					echo $faq_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				echo '</div>';

				// faq list
				echo '<div class="betterdocs-faq-list-content" data-term-id="' . esc_attr( $term->term_id ) . '" style="display: none;">';
				$view_object->get(
					'shortcode-parts/faq-list',
					[
						'term'       => $term,
						'faq_schema' => $faq_schema,
						'faq_json'   => $faq_schema ? $faq_json['mainEntity'] : ''
					]
				);
				echo '</div>';
			}
			echo '</div>';

			if ( $faq_schema ) {
				$faq_json['mainEntity'] = $GLOBALS['betterdocs_faq_schema_main_entity'];
				echo '<script type="application/ld+json">' . wp_json_encode( $faq_json ) . '</script>';
			}
		}
		?>
	</div>
</div>
