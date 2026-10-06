<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var \WPDeveloper\BetterDocs\Shortcodes\ToC $widget */
if ( $post !== null ) {
	// Check if post is password protected and user hasn't provided correct password
	if ( post_password_required( $post->ID ) ) {
		// Don't show ToC for password-protected posts until password is provided
		return;
	}

	// Process content for special characters without triggering heavy content filters
	// This prevents memory issues from plugins like WPML while still handling special characters
	$processed_content = $widget->process_content_for_toc( $post->post_content );
	$toc_data = $widget->format_toc_data( $processed_content, $htags, $hierarchy );
	if ( empty( $toc_data->items ) ) {
		return;
	}
} else {
	return;
}


	$collapsible_arrow = '';
	$wrapper_classes   = [ 'betterdocs-toc' ];

if ( empty( $toc_title ) ) {
	$toc_title = betterdocs()->settings->get( 'toc_title', __( 'Table of Contents', 'betterdocs' ) );
	$toc_title = esc_html( stripslashes( $toc_title ) );
}

if ( $collapsible_on_mobile == '1' ) {
	$wrapper_classes[] = 'collapsible-sm';
	$collapsible_arrow = "<svg class='angle-icon angle-up' aria-hidden='true' focusable='false' data-prefix='fas' data-icon='angle-up' class='svg-inline--fa fa-angle-up fa-w-10' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'><path fill='currentColor' d='M177 159.7l136 136c9.4 9.4 9.4 24.6 0 33.9l-22.6 22.6c-9.4 9.4-24.6 9.4-33.9 0L160 255.9l-96.4 96.4c-9.4 9.4-24.6 9.4-33.9 0L7 329.7c-9.4-9.4-9.4-24.6 0-33.9l136-136c9.4-9.5 24.6-9.5 34-.1z'></path></svg><svg class='angle-icon angle-down' aria-hidden='true' focusable='false' data-prefix='fas' data-icon='angle-down' class='svg-inline--fa fa-angle-down fa-w-10' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'><path fill='currentColor' d='M143 352.3L7 216.3c-9.4-9.4-9.4-24.6 0-33.9l22.6-22.6c9.4-9.4 24.6-9.4 33.9 0l96.4 96.4 96.4-96.4c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9l-136 136c-9.2 9.4-24.4 9.4-33.8 0z'></path></svg>";
}

if ( $list_number == '1' ) {
	$wrapper_classes[] = 'toc-list-number';
}
?>

<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<span class="toc-title">
		<?php echo $toc_title . $collapsible_arrow; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Title Is Escaped Above & Svg's Cannot Be Escaped ?>
	</span>

	<?php echo $toc_data->print( $toc_data->items, $hierarchy, 1 ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped	-- Already Escaped Above, Svg Is Not Needed For Escaping ?>
</div>
