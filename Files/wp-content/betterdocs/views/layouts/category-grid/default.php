<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use WPDeveloper\BetterDocs\Utils\Helper;

	$attributes = [
		'data-id' => isset( $term->term_id ) ? $term->term_id : 0,
		'class'   => [ 'betterdocs-single-category-wrapper category-grid' ]
	];

	$is_active = false;
	if ( is_single() && ( $term->term_id === $current_queried_object_id || ( (bool) $nested_subcategory && in_array( $term->term_id, $ancestors ) ) ) ) {
		$is_active = true;
	} elseif ( Helper::get_tax() == 'doc_category' && $term->term_id === $current_queried_object_id || ( (bool) $nested_subcategory && Helper::get_tax() == 'doc_category' && Helper::get_the_top_most_parent( $current_queried_object_id ) == $term->term_id ) ) {
		$is_active = true;
	}
	if ( $is_active ) {
		$attributes['class'][] = 'active';
	}

	if ( isset( $wrapper_class ) && is_array( $wrapper_class ) && ! empty( $wrapper_class ) ) {
		$attributes['class'] = array_merge( $attributes['class'], $wrapper_class );
	}

	$attributes = betterdocs()->template_helper->get_html_attributes( $attributes );
	$posts_per_page = isset( $docs_query_args['posts_per_page'] ) ? $docs_query_args['posts_per_page'] : ( isset( $post_per_tab ) ? $post_per_tab : ( isset($post_per_page) ? $post_per_page : 0 ) ); // for category grid, mkb, tab
	$current_term_posts_count = isset( $counts ) && ! is_array( $counts ) ? $counts : ( isset( $counts ) && is_array( $counts ) ? $counts['counts'] : 0 );

	$is_lazy_body = ! empty( $lazy_load ) && ! $is_active;
?>

<article
	<?php echo $attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="betterdocs-single-category-inner">
		<?php
		if ( $show_header ) {
			$view_object->get( 'layout-parts/header' );
		}

		if ( $show_list ) {
			if ( $is_lazy_body ) {
				// Empty placeholder. JS injects the skeleton on click and swaps
				// in real docs once the AJAX completes (with a 1s minimum
				// display so the loading state is perceptible).
				printf(
					'<div class="betterdocs-body" data-bd-lazy="1" data-bd-term-id="%d" style="display:none;"></div>',
					(int) $term->term_id
				);
			} else {
				echo '<div class="betterdocs-body">';
				$view_object->get( 'template-parts/category-list' );
				echo '</div>';
			}
		}

		if( $posts_per_page < $current_term_posts_count ) {
			$view_object->get( 'layout-parts/footer' );
		}
		?>
	</div>
</article>
