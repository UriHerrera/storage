<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current_category = get_queried_object();

if ( $current_category != null && $layout == 'layout-1' ) :

	?>
			<div class='betterdocs-content-area block-archive-list <?php echo esc_attr( $blockId ); ?>'>
				<div class="betterdocs-content-inner-area">
							<div class="betterdocs-entry-title">
							<?php
								$title_tag = tag_escape( betterdocs()->template_helper->is_valid_tag( isset( $title_tag ) ? $title_tag : 'h2' ) );
								echo wp_sprintf(
									'<%1$s class="betterdocs-entry-heading">%2$s</%1$s>',
									$title_tag, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									esc_html( $current_category->name )
								);
								echo wp_sprintf( '<p>%s</p>', wp_kses_post( $current_category->description ) );
							?>
							</div>
							<div class="betterdocs-entry-body betterdocs-taxonomy-doc-category">
							<?php $view_object->get( 'widgets/archive-list' ); ?>
							</div>
				</div>
			</div>
		<?php
	elseif ( $current_category != null && $layout == 'layout-2' ) :
		// Public action name predates the prefix convention; renaming would break third-party integrations.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'archive_handbook_list' );
	elseif ( $current_category != null && ( $layout == 'layout-3' || $layout == 'layout-4' ) ) :
		$post_query = new WP_Query( $query_args );


		// Determine CSS class and template based on layout
		$css_class = $layout == 'layout-3' ? 'doc-category-layout-7' : 'doc-category-layout-4';
		$template = $layout == 'layout-3' ? 'template-parts/archive-doc-list' : 'template-parts/archive-doc-list-2';

		echo '<div class="' . esc_attr( $blockId ) . ' ' . esc_attr( $css_class ) . '">';
		betterdocs()->views->get(
			$template,
			[
				'current_category' => $current_category,
				'post_query'       => $post_query,
				'docs_list_title_tag' => isset( $docs_list_title_tag ) ? $docs_list_title_tag : 'h2'
			]
		);

		// Common pagination logic for both layout-3 and layout-4
		if ( $pagination ) {
			$current_page = isset( $page ) ? $page : ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );
			$posts_per_page = isset( $posts_per_page ) && $posts_per_page > 0 ? $posts_per_page : 10;
			$total_pages = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / $posts_per_page );
			betterdocs()->views->get(
				'template-parts/pagination',
				[
					'total_pages'  => $total_pages,
					'link'         => ( $current_category instanceof \WP_Term ) ? get_term_link( $current_category, 'doc_category' ) : get_permalink(get_the_ID()),
					'current_page' => $current_page,
					'template'     => 'doc_category'
				]
			);
		}
		echo '</div>';
	endif;
	?>
