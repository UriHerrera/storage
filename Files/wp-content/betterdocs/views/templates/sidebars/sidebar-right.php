<aside id="betterdocs-sidebar-right"  class="betterdocs-sidebar betterdocs-full-sidebar-right right-sidebar-toc-wrap">
	<div data-simplebar class="layout3-toc-container right-sidebar-toc-container">
		<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Check if post is password protected and user hasn't provided correct password
			if ( post_password_required() ) {
				// Don't show ToC sidebar for password-protected posts until password is provided
				echo '<div class="betterdocs-toc-password-protected">';
				echo '<p>' . esc_html__( 'Table of Contents is available after entering the correct password.', 'betterdocs' ) . '</p>';
				echo '</div>';
			} else {
				$hierarchy     = betterdocs()->settings->get( 'toc_hierarchy' );
				$list_number   = betterdocs()->settings->get( 'toc_list_number' );
				$supported_tag = betterdocs()->settings->get( 'supported_heading_tag' );
				$htags         = $supported_tag ? implode( ',', $supported_tag ) : '';

			$attributes = betterdocs()->template_helper->get_html_attributes(
				[
					'htags'                 => "{$htags}",
					'hierarchy'             => "{$hierarchy}",
					'list_number'           => "{$list_number}",
					'collapsible_on_mobile' => false
				]
			);

			echo do_shortcode( '[betterdocs_toc ' . $attributes . ']' );

			if ( isset( $social_share ) && $social_share ) {
				echo betterdocs()->views->get( 'templates/parts/social-2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( isset( $feedback ) && $feedback ) {
				$reaction_text = betterdocs()->customizer->defaults->get( 'betterdocs_post_reactions_text_2' );
				$reaction_text_tag = betterdocs()->customizer->defaults->get( 'betterdocs_reactions_title_tag' );
				// Collect reaction values and icons
				$reactions_data = [
					'happy'       => 'betterdocs_post_reactions_happy',
					'happy_icon'  => 'betterdocs_post_reactions_happy_icon',
					'normal'      => 'betterdocs_post_reactions_normal',
					'normal_icon' => 'betterdocs_post_reactions_normal_icon',
					'sad'         => 'betterdocs_post_reactions_sad',
					'sad_icon'    => 'betterdocs_post_reactions_sad_icon'
				];

				foreach ( $reactions_data as $key => $theme_mod ) {
					$value = betterdocs()->customizer->defaults->get( $theme_mod );
					if ( $value ) {
						$args[ $key ] = $value;
					} else {
						$args[ $key ] = false;
					}
				}

				// Build the attribute string for the shortcode
				$attr = '';
				foreach ( $args as $key => $value ) {
					$attr .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
				}
				echo do_shortcode( '[betterdocs_article_reactions text="' . $reaction_text . '" text_tag="' . $reaction_text_tag . '" layout="layout-3"' . $attr . ']' );
			}
			} // End password protection check
			?>
	</div>
	<?php
	/**
	 * Fires inside the right (ToC) sidebar, after the ToC container. Extension
	 * point for add-ons that render a sticky side panel next to the ToC (e.g.
	 * BetterDocs Pro's API-docs code-sample panel).
	 */
	do_action( 'betterdocs_after_toc_sidebar' );
	?>
</aside>
