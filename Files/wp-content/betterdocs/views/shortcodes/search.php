<div class="betterdocs-live-search">
	<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( $heading || $subheading ) : ?>
		<div class="betterdocs-search-heading">
			<?php
			if ( ! empty( $heading ) && in_array( $heading_tag, betterdocs()->template_helper::ALLOWED_HTML_TAGS ) ) {
				echo '<' . esc_attr( $heading_tag ) . ' class="heading"> ' . esc_html( $heading ) . ' </' . esc_attr( $heading_tag ) . '>';
			}

			if ( ! empty( $subheading ) && in_array( $subheading_tag, betterdocs()->template_helper::ALLOWED_HTML_TAGS ) ) {
				echo '<' . esc_attr( $subheading_tag ) . ' class="subheading"> ' . esc_html( $subheading ) . ' </' . esc_attr( $subheading_tag ) . '>';
			}
			?>
		</div>
		<?php
		endif;
		do_action( 'betterdocs_before_live_search_form', get_defined_vars() );
	?>

	<form class="betterdocs-searchform betterdocs-advance-searchform">
		<div class="betterdocs-searchform-input-wrap">
			<svg class="docs-search-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="38px" viewBox="0 0 50 50" version="1.1">
				<g id="surface1">
					<path d="M 21 3 C 11.601563 3 4 10.601563 4 20 C 4 29.398438 11.601563 37 21 37 C 24.355469 37 27.460938 36.015625 30.09375 34.34375 L 42.375 46.625 L 46.625 42.375 L 34.5 30.28125 C 36.679688 27.421875 38 23.878906 38 20 C 38 10.601563 30.398438 3 21 3 Z M 21 7 C 28.199219 7 34 12.800781 34 20 C 34 27.199219 28.199219 33 21 33 C 13.800781 33 8 27.199219 8 20 C 8 12.800781 13.800781 7 21 7 Z "></path>
				</g>
			</svg>
			<input type="text" class="betterdocs-search-field" name="s" aria-label="<?php echo esc_html__( 'Search Input', 'betterdocs' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" value="<?php echo esc_attr( get_search_query() ); ?>">
			<svg class="docs-search-loader" width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#444b54">
				<g fill="none" fill-rule="evenodd">
					<g transform="translate(1 1)" stroke-width="2">
						<circle stroke-opacity=".5" cx="18" cy="18" r="18" />
						<path d="M36 18c0-9.94-8.06-18-18-18">
							<animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite" />
						</path>
					</g>
				</g>
			</svg>
			<svg class="docs-search-close" xmlns="http://www.w3.org/2000/svg" width="38px" viewBox="0 0 128 128">
				<path fill="#fff" d="M64 14A50 50 0 1 0 64 114A50 50 0 1 0 64 14Z" transform="rotate(-45.001 64 64.001)"></path>
				<path class="close-border" d="M64,117c-14.2,0-27.5-5.5-37.5-15.5c-20.7-20.7-20.7-54.3,0-75C36.5,16.5,49.8,11,64,11c14.2,0,27.5,5.5,37.5,15.5c10,10,15.5,23.3,15.5,37.5s-5.5,27.5-15.5,37.5C91.5,111.5,78.2,117,64,117z M64,17c-12.6,0-24.4,4.9-33.2,13.8c-18.3,18.3-18.3,48.1,0,66.5C39.6,106.1,51.4,111,64,111c12.6,0,24.4-4.9,33.2-13.8S111,76.6,111,64s-4.9-24.4-13.8-33.2S76.6,17,64,17z"></path>
				<path class="close-line" d="M53.4,77.6c-0.8,0-1.5-0.3-2.1-0.9c-1.2-1.2-1.2-3.1,0-4.2l21.2-21.2c1.2-1.2,3.1-1.2,4.2,0c1.2,1.2,1.2,3.1,0,4.2L55.5,76.7C54.9,77.3,54.2,77.6,53.4,77.6z"></path>
				<path class="close-line" d="M74.6,77.6c-0.8,0-1.5-0.3-2.1-0.9L51.3,55.5c-1.2-1.2-1.2-3.1,0-4.2c1.2-1.2,3.1-1.2,4.2,0l21.2,21.2c1.2,1.2,1.2,3.1,0,4.2C76.1,77.3,75.4,77.6,74.6,77.6z"></path>
			</svg>
		</div>

		<?php do_action( 'betterdocs_live_search_form_footer', get_defined_vars() ); ?>
		<input type="hidden" value="Search" aria-label="Search Submit" class="betterdocs-search-submit" />
	</form>

	<?php do_action( 'betterdocs_after_live_search_form', get_defined_vars() ); ?>
</div>
