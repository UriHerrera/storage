<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
	 * The template for single doc page
	 *
	 * @author WPDeveloper
	 * @package Documentation/SinglePage
	 */

	get_header();

	$view_object             = betterdocs()->views;
	$enable_toc              = betterdocs()->settings->get( 'enable_toc' );
	$collapsible_toc_mobile  = betterdocs()->settings->get( 'collapsible_toc_mobile' );
	$enable_sidebar_cat_list = betterdocs()->settings->get( 'enable_sidebar_cat_list' );

	$wrapper_class = [ 'betterdocs-content-wrapper betterdocs-display-flex' ];

if ( $enable_sidebar_cat_list == 1 && $enable_toc == 1 ) {
	$wrapper_class[] = 'grid-col-3 sidebar-toc-enable';
} elseif ( $enable_sidebar_cat_list == 1 ) {
	$wrapper_class[] = 'grid-col-2 sidebar-enable';
} elseif ( $enable_toc == 1 ) {
	$wrapper_class[] = 'grid-col-2 toc-enable';
} elseif ( ! $enable_sidebar_cat_list && ! $enable_toc ) {
	$wrapper_class[] = 'grid-col-1 content-enable';
}

?>
<div class="betterdocs-wrapper betterdocs-fluid-wrapper betterdocs-single-wrapper betterdocs-single-layout-4 betterdocs-single-abstract-layout betterdocs-single-wraper">
	<?php betterdocs()->template_helper->search(); ?>
	<div class="<?php echo esc_html( implode( ' ', $wrapper_class ) ); ?>">
		<?php
			betterdocs()->views->get(
				'templates/sidebars/sidebar-4',
				[
					'layout_type' => 'template'
				]
			);
			?>
		<div id="betterdocs-single-main" class="betterdocs-content-area docs-content-full-main">
			<div class="betterdocs-content-inner-area">
				<?php
				while ( have_posts() ) :
					the_post();
					$view_object->get(
						'templates/parts/mobile-nav',
						[
							'mobile_sidebar' => true,
							'mobile_toc'     => true
						]
					);
					$view_object->get( 'templates/parts/breadcrumbs' );
					/**
					 * Headers
					 */
					$view_object->get( 'templates/headers/layout-4' );
					$author       = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_enable' );
					$updated_date = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_date' );
					if ( $author ) {
						$view_object->get( 'templates/parts/author', [ 'updated_date' => $updated_date ] );
					}
					$view_object->get( 'templates/contents/layout-2' );
					$view_object->get( 'templates/footer' );
					endwhile;
					$view_object->get( 'templates/parts/navigation' );
					$view_object->get( 'templates/parts/credit' );
					$view_object->get( 'templates/parts/comment' );
				?>
			</div>
		</div> <!-- #main -->
		<?php
		if ( $enable_toc ) {
			$view_object->get( 'templates/sidebars/sidebar-right' );
		}
		?>
	</div> <!-- #primary -->
</div> <!-- .betterdocs-single-wraper -->
<?php
get_footer();
