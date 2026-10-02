<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$attributes = [
		'data-id' => isset( $term->term_id ) ? $term->term_id : 0,
		'class'   => [ 'betterdocs-single-category-wrapper category-grid' ]
	];

	if ( isset( $wrapper_class ) && is_array( $wrapper_class ) && ! empty( $wrapper_class ) ) {
		$attributes['class'] = array_merge( $attributes['class'], $wrapper_class );
	}

	$attributes = betterdocs()->template_helper->get_html_attributes( $attributes );
	$posts_per_page = isset( $docs_query_args['posts_per_page'] ) ? $docs_query_args['posts_per_page'] : ( isset( $post_per_tab ) ? $post_per_tab : ( isset($post_per_page) ? $post_per_page : 0 ) ); // for category grid, mkb, tab
	$current_term_posts_count = isset( $counts ) && ! is_array( $counts ) ? $counts : ( isset( $counts ) && is_array( $counts ) ? $counts['counts'] : 0 );
?>

<article
	<?php echo $attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		if ( $show_header ) {
			$view_object->get( 'layout-parts/header' );
		}

		if ( $show_list ) {
			$view_object->get( 'template-parts/category-list-2' );
		}
		?>
</article>
