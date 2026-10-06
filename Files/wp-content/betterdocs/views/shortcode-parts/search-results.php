<div class="betterdocs-search-result-wrap">
	<ul class="docs-search-result">
		<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( $search_results->have_posts() ) {
			$input_not_found = '';
			while ( $search_results->have_posts() ) :
				$search_results->the_post();
				preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', get_the_content(), $matches );

				if ( $matches[1] ) {
					$first_img = $matches[1][0];
				} else {
					$first_img = '';
				}

				$terms      = get_the_terms( get_the_ID(), 'doc_category' );
				$terms_name = [];

				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$terms_name[] = $term->name;
					}
				}

				$all_terms           = join( ', ', $terms_name );
				$icon                = '';
				$search_result_image = betterdocs()->settings->get( 'search_result_image' );

				if ( $search_result_image == 1 && has_post_thumbnail() ) :
					$icon = get_the_post_thumbnail();
				elseif ( $search_result_image == 1 && ! empty( $first_img ) ) :
					$icon = '<img src="' . $first_img . '" alt="">';
				endif;

				// Get the correct permalink for the post's language
				$post_id = get_the_ID();
				$permalink = get_permalink( $post_id );
				
				// Handle WPML permalinks
				if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML public integration filter.
					$post_language = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $post_id, 'element_type' => 'post_docs' ) );
					
					if ( $post_language ) {
						global $sitepress;
						$current_lang = $sitepress ? $sitepress->get_current_language() : '';
						
						if ( $post_language !== $current_lang ) {
							$permalink = add_query_arg( 'lang', $post_language, $permalink );
						}
					}
				}
				// Handle TranslatePress permalinks
				elseif ( class_exists( '\TRP_Translate_Press' ) ) {
					global $TRP_LANGUAGE;
					$trp = \TRP_Translate_Press::get_trp_instance();
					if ( isset( $trp ) && method_exists( $trp, 'get_component' ) ) {
						$trp_settings = $trp->get_component( 'settings' );
						$trp_url_converter = $trp->get_component( 'url_converter' );
						
						if ( $trp_settings && $trp_url_converter && isset( $TRP_LANGUAGE ) ) {
							$settings = $trp_settings->get_settings();
							$default_lang = isset( $settings['default-language'] ) ? $settings['default-language'] : 'en_US';
							
							// If we're not on the default language, ensure the URL has the language prefix
							if ( $TRP_LANGUAGE && $TRP_LANGUAGE !== $default_lang ) {
								$permalink = $trp_url_converter->get_url_for_language( $TRP_LANGUAGE, $permalink );
							}
						}
					}
				}

                    echo '<li>' . $icon . '<a href="' . $permalink . '"><span class="betterdocs-search-title">' . betterdocs()->template_helper->kses( get_the_title() ) . '</span><br><span class="betterdocs-search-category">' . $all_terms . '</span></a></li>'; //phpcs:ignore
			endwhile;
		} else {
			$input_not_found = $search_input;
			echo '<li>' . esc_html( betterdocs()->settings->get( 'search_not_found_text' ) ) . '</li>';
		}
		?>
	</ul>
</div>
