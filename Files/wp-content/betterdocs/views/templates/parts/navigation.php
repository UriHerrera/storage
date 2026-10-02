<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

    
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! betterdocs()->settings->get( 'enable_navigation' ) ) {
        return;
    }

    // Get current post
    $current_post = get_post();
    if ( ! $current_post || 'docs' !== $current_post->post_type ) {
        return;
    }

    // Get the doc_category terms for current post
    $terms = get_the_terms( $current_post->ID, 'doc_category' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return;
    }

    // Use the first term for navigation
    $current_term = $terms[ 0 ];

    // Build query args same as category-list.php
    $query_args = betterdocs()->query->docs_query_args(
        array(
            'post_type' => 'docs',
            'posts_per_page' => -1,
            'term_id' => $current_term->term_id,
            'term_slug' => $current_term->slug,
            'orderby' => betterdocs()->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
            'order' => betterdocs()->settings->get( 'docs_order', 'ASC' )
        )
    );

    // Get posts using the same method as category-list.php
    $posts = betterdocs()->query->get_posts( $query_args, true );

    if ( ! $posts->have_posts() ) {
        wp_reset_postdata();
        return;
    }

    // Build array of post IDs in order
    $post_ids = array();
    while ( $posts->have_posts() ) {
        $posts->the_post();
        $post_ids[  ] = get_the_ID();
    }
    wp_reset_postdata();

    // Find current post position
    $current_index = array_search( $current_post->ID, $post_ids, true );

    if ( false === $current_index ) {
        return;
    }

    // Get previous and next post IDs with circular navigation
    $total_posts = count( $post_ids );
    $last_index  = $total_posts - 1;

    // If current is first item, prev wraps to last item
    $prev_post_id = ( 0 === $current_index ) ? $post_ids[ $last_index ] : $post_ids[ $current_index - 1 ];

    // If current is last item, next wraps to first item
    $next_post_id = ( $last_index === $current_index ) ? $post_ids[ 0 ] : $post_ids[ $current_index + 1 ];

    // SVG icons
    $prev_icon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="42px" viewBox="0 0 50 50" version="1.1"><g id="surface1"><path style=" " d="M 11.957031 13.988281 C 11.699219 14.003906 11.457031 14.117188 11.28125 14.308594 L 1.015625 25 L 11.28125 35.691406 C 11.527344 35.953125 11.894531 36.0625 12.242188 35.976563 C 12.589844 35.890625 12.867188 35.625 12.964844 35.28125 C 13.066406 34.933594 12.972656 34.5625 12.71875 34.308594 L 4.746094 26 L 48 26 C 48.359375 26.003906 48.695313 25.816406 48.878906 25.503906 C 49.058594 25.191406 49.058594 24.808594 48.878906 24.496094 C 48.695313 24.183594 48.359375 23.996094 48 24 L 4.746094 24 L 12.71875 15.691406 C 13.011719 15.398438 13.09375 14.957031 12.921875 14.582031 C 12.753906 14.203125 12.371094 13.96875 11.957031 13.988281 Z "></path></g></svg>';
    $next_icon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="42px" viewBox="0 0 50 50" version="1.1"><g id="surface1"><path style=" " d="M 38.035156 13.988281 C 37.628906 13.980469 37.257813 14.222656 37.09375 14.59375 C 36.933594 14.96875 37.015625 15.402344 37.300781 15.691406 L 45.277344 24 L 2.023438 24 C 1.664063 23.996094 1.328125 24.183594 1.148438 24.496094 C 0.964844 24.808594 0.964844 25.191406 1.148438 25.503906 C 1.328125 25.816406 1.664063 26.003906 2.023438 26 L 45.277344 26 L 37.300781 34.308594 C 36.917969 34.707031 36.933594 35.339844 37.332031 35.722656 C 37.730469 36.105469 38.363281 36.09375 38.746094 35.691406 L 49.011719 25 L 38.746094 14.308594 C 38.5625 14.109375 38.304688 13.996094 38.035156 13.988281 Z "></path></g></svg>';

    // Build navigation HTML
    $nav = '';

    if ( $prev_post_id ) {
        $prev_post  = get_post( $prev_post_id );
        $prev_title = get_the_title( $prev_post_id );
        $prev_link  = get_permalink( $prev_post_id );
        $nav .= sprintf(
            '<a href="%s" rel="prev">%s %s</a>',
            esc_url( $prev_link ),
            $prev_icon,
            esc_html( $prev_title )
        );
    }

    if ( $next_post_id ) {
        $next_post  = get_post( $next_post_id );
        $next_title = get_the_title( $next_post_id );
        $next_link  = get_permalink( $next_post_id );
        $nav .= sprintf(
            '<a href="%s" rel="next">%s %s</a>',
            esc_url( $next_link ),
            esc_html( $next_title ),
            $next_icon
        );
    }

    $wrapper_attr_array = array();

    if ( isset( $widget_type ) && 'betterdocs-navigation' !== $widget_type ) {
        $wrapper_attr_array = array( 'class' => array() );
    }

    if ( ! empty( $wrapper_attr_array ) ) {
        $wrapper_attr_array[ 'class' ][  ] = 'docs-navigation';
        if ( isset( $wraper_class ) ) {
            $wrapper_attr_array[ 'class' ][  ] = $wraper_class;
        }
        if ( isset( $widget_type ) && 'blocks' === $widget_type ) {
            $wrapper_attr_array[ 'class' ][  ] = $blockId;
        }
    }
    $wrapper_attr = betterdocs()->template_helper->get_html_attributes( $wrapper_attr_array );

    if ( empty( $nav ) ) {
        return;
    }
?>

<div
	<?php echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped    ?>>
	<?php echo apply_filters( 'betterdocs_single_post_nav', $nav ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped    ?>
</div>
