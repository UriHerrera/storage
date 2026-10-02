<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
	 * Template archive docs
	 *
	 * @link       https://wpdeveloper.com
	 * @since      1.0.0
	 *
	 * @package    BetterDocs
	 * @subpackage BetterDocs/public
	 */

	get_header();

	$view_object      = betterdocs()->views;
	$layout           = betterdocs()->customizer->defaults->get( 'betterdocs_archive_layout_select', 'layout-7' );
	$title_tag        = betterdocs()->customizer->defaults->get( 'betterdocs_archive_title_tag', 'h2' );
	$title_tag        = betterdocs()->template_helper->is_valid_tag( $title_tag );
	$enable_pagintion = betterdocs()->settings->get( 'archive_enable_pagination' );

	$content_area_classes = [
		'betterdocs-content-wrapper betterdocs-display-flex',
		"doc-category-$layout"
	];

	$current_category = get_queried_object();
	$term_link        = isset( $current_category->term_id ) ? get_term_link( $current_category->term_id ) : '';
	?>

<div class="betterdocs-wrapper betterdocs-taxonomy-wrapper betterdocs-category-archive-wrapper betterdocs-wraper">
	<?php betterdocs()->template_helper->search(); ?>

	<div class="<?php echo esc_attr( implode( ' ', $content_area_classes ) ); ?>">
		<?php betterdocs()->template_helper->sidebar( $layout, 'template' ); ?>

		<div id="main" class="betterdocs-content-area">
			<div class="betterdocs-content-inner-area">
				<?php
					$view_object->get(
						'templates/parts/mobile-nav',
						[
							'mobile_sidebar' => true,
							'mobile_toc'     => false
						]
					);
					/**
					 * Breadcrumbs
					 */
					$view_object->get( 'templates/parts/breadcrumbs' );
					?>

				<div class="betterdocs-entry-title">
					<?php
						echo wp_sprintf(
							'<%1$s class="betterdocs-entry-heading">%2$s</%1$s>',
							$title_tag, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							esc_html( $current_category->name )
						);
						echo wp_sprintf( '<p>%s</p>', wp_kses_post( $current_category->description ) );
						?>
				</div>

				<div class="betterdocs-entry-body betterdocs-taxonomy-doc-category">
					<ul>
						<?php
							$page = get_query_var( 'paged' ) != '' ? get_query_var( 'paged' ) : 1;
							$args = betterdocs()->query->docs_query_args(
								[
									'term_id'        => $current_category->term_id,
									'term_slug'      => $current_category->slug,
									'posts_per_page' => 10,
									'paged'          => $page,
									'orderby'        => betterdocs()->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
									'order'          => betterdocs()->settings->get( 'docs_order', 'ASC' )
								]
							);

							$custom_icon        = betterdocs()->customizer->defaults->get( 'betterdocs_archive_list_icon' );
							$settings_list_icon = betterdocs()->settings->get( 'docs_list_icon' );

							if ( ! $custom_icon && $settings_list_icon ) {
								$custom_icon = $settings_list_icon['url'];
							}

							if ( ! $enable_pagintion ) {
								$args['posts_per_page'] = -1;
								unset( $args['paged'] );
							}

							$post_query  = new WP_Query( $args );
							$total_pages = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / 10 );
							if ( $post_query->have_posts() ) :
								while ( $post_query->have_posts() ) :
									$post_query->the_post();
									if ( $custom_icon ) {
										$icon = '<img src="' . esc_url( $custom_icon ) . '" />';
									} else {
										$icon = betterdocs()->template_helper->icon();
									}
									echo wp_sprintf(
										'<li>%s<a href="%s">%s</a></li>',
										$icon, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										esc_attr( esc_url( get_the_permalink() ) ),
										betterdocs()->template_helper->kses( get_the_title() ) //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									);
								endwhile;
								wp_reset_postdata();
							endif; // $post_query->have_posts()

							betterdocs()->views->get(
								'template-parts/nested-categories',
								[
									'term_id'            => $current_category->term_id,
									'widget'             => null,
									'nested_subcategory' => betterdocs()->settings->get( 'archive_nested_subcategory' ),
									'nested_docs_query_args' => [
										'orderby' => betterdocs()->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
										'order'   => betterdocs()->settings->get( 'docs_order', 'ASC' )
									],
									'nested_terms_query' => [
										'orderby' => betterdocs()->settings->get( 'terms_orderby', 'betterdocs_order' ),
										'order'   => betterdocs()->settings->get( 'terms_order', 'ASC' )
									],
									'list_icon_url'      => $custom_icon,
									'layout_type'        => 'template'
								]
							);
							?>
					</ul>
				</div>
			</div>
			<?php
			if ( $enable_pagintion ) {
				$page = get_query_var( 'paged' ) != '' ? get_query_var( 'paged' ) : 1; //applicable for parent category only
				$view_object->get(
					'template-parts/pagination',
					[
						'total_pages'  => $total_pages,
						'link'         => $term_link,
						'current_page' => $page,
						'template'     => 'doc_category'
					]
				);
			}
			?>
		</div>
	</div>
</div>

<?php
get_footer();
