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

	$view_object          = betterdocs()->views;
	$layout               = betterdocs()->customizer->defaults->get( 'betterdocs_archive_layout_select', 'layout-7' );
	$title_tag            = betterdocs()->customizer->defaults->get( 'betterdocs_archive_title_tag', 'h2' );
	$title_tag            = betterdocs()->template_helper->is_valid_tag( $title_tag );
	$current_category     = get_queried_object();
	$content_area_classes = [
		'betterdocs-content-wrapper betterdocs-display-flex',
		"doc-category-$layout"
	];
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
					<?php echo wp_sprintf( '<%1$s class="docs-cat-heading">%2$s</%1$s>', 'h3', $current_category->name ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="betterdocs-entry-body betterdocs-taxonomy-doc-category">
					<?php
						$_tax_query = [
							[
								'taxonomy' => 'doc_tag',
								'field'    => 'slug',
								'terms'    => isset( $current_category->slug ) ? $current_category->slug : ''
							]
						];
						$post_query = betterdocs()->query->get_posts(
							[
								'term_id'        => isset( $current_category->term_id ) ? $current_category->term_id : '',
								'term_slug'      => isset( $current_category->slug ) ? $current_category->slug : '',
								'posts_per_page' => -1,
								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- tag-archive listing requires tax filtering by design.
								'tax_query'      => apply_filters( 'betterdocs_tag_tax_query', $_tax_query, $current_category )
							]
						);

						$custom_icon        = betterdocs()->customizer->defaults->get( 'betterdocs_archive_list_icon' );
						$settings_list_icon = betterdocs()->settings->get( 'docs_list_icon' );
						if ( ! $custom_icon && $settings_list_icon ) {
							$custom_icon = $settings_list_icon['url'];
						}

						if ( $post_query->have_posts() ) :
							?>
					<ul>
							<?php
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
							?>
					</ul>
							<?php
						else :
							echo '<p class="nothing-here">' . esc_html__( 'Sorry, no docs were found.', 'betterdocs' ) . '</p>';
						endif; // $post_query->have_posts()
						?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
get_footer();
