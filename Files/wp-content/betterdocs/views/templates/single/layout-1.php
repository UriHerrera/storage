<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	/**
	 * The template for single doc page
	 *
	 * @author  WPDeveloper
	 * @package Documentation/SinglePage
	 */

	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

	betterdocs()->template_helper->header();

	$mods        = betterdocs()->customizer->defaults->generate_defaults();
	$view_object = betterdocs()->views;
?>

<div class="betterdocs-wrapper betterdocs-single-wrapper betterdocs-single-layout-1 betterdocs-single-classic-layout betterdocs-single-wraper">
	<?php betterdocs()->template_helper->search(); ?>

	<div class="betterdocs-content-wrapper betterdocs-display-flex">
		<?php
			betterdocs()->views->get(
				'templates/sidebars/sidebar-1',
				[
					'layout_type' => 'template'
				]
			);
			?>

		<div id="betterdocs-single-main" class="betterdocs-content-area">
			<?php
			while ( have_posts() ) :
				the_post();
				$view_object->get(
					'templates/parts/mobile-nav',
					[
						'mobile_sidebar' => true,
						'mobile_toc'     => false
					]
				);
				$view_object->get( 'templates/parts/breadcrumbs' );
				$view_object->get( 'templates/headers/layout-1' );
				$author       = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_enable' );
				$updated_date = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_date' );
				if ( $author ) {
					$view_object->get( 'templates/parts/author', [ 'updated_date' => $updated_date ] );
				}
				$view_object->get( 'templates/contents/layout-1' );
				$view_object->get( 'templates/footer' );
				endwhile;
				$view_object->get( 'templates/parts/navigation' );
				$view_object->get( 'templates/parts/credit' );
				$view_object->get( 'templates/parts/comment' );
			?>
		</div> <!-- #main -->
	</div>
</div>
<?php
	/**
	 * Footer
	 */
betterdocs()->template_helper->footer();
