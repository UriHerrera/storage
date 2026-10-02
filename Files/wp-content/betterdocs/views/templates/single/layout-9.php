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
	$number_of_faqs          = betterdocs()->customizer->defaults->get( 'search_modal_query_initial_number_of_faqs' );
	$number_of_docs          = betterdocs()->customizer->defaults->get( 'search_modal_query_initial_number_of_docs' );
	// $faq_terms               = betterdocs()->customizer->defaults->get( 'search_modal_query_select_specific_faq' );
	// $doc_terms               = betterdocs()->customizer->defaults->get( 'search_modal_query_select_specific_doc_category' );
	$sidebar_switch = betterdocs()->customizer->defaults->get( 'sidebar_search_layout_7_toggle' );

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
<div class="betterdocs-wrapper betterdocs-fluid-wrapper betterdocs-single-wrapper betterdocs-single-layout-8 betterdocs-single-layout-9">
	<?php
	if ( betterdocs()->customizer->defaults->get( 'single_doc_layout_8_9_search_toogle' ) ) {
			betterdocs()->template_helper->search();
	}
	?>
	<div class="<?php echo esc_html( implode( ' ', $wrapper_class ) ); ?>">
		<?php
			betterdocs()->views->get(
				'templates/sidebars/sidebar-7',
				[
					'layout_type'    => 'template',
					'number_of_docs' => $number_of_docs,
					'number_of_faqs' => $number_of_faqs,
					// 'faq_terms'      => $faq_terms,
					// 'doc_terms'      => $doc_terms,
					'sidebar_search' => $sidebar_switch
				]
			);
			?>
		<div id="betterdocs-single-main" class="betterdocs-content-area docs-single-main docs-content-full-main">
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
					$view_object->get(
						'templates/parts/breadcrumbs',
						[
							'breadcrumbs_layout' => 'layout-2'
						]
					);
					/**
					 * Headers
					 */
					$view_object->get( 'templates/headers/layout-4' );
					$author       = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_enable_layout_9' );
					$updated_date = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_date' );
					if ( $author ) {
						$view_object->get( 'templates/parts/author', [ 'updated_date' => $updated_date ] );
					}
					$view_object->get( 'templates/contents/layout-2' );

					echo '<div class="betterdocs-entry-footer">';
					/**
					 * Tags
					 */
					echo '<div class="betterdocs-tags-print">';
					$view_object->get( 'templates/parts/tags' );
					$view_object->get(
						'templates/parts/print-icon-2',
						[
							'enable' => betterdocs()->settings->get( 'enable_print_icon', false )
						]
					);
					echo '</div>';

					/**
					 * Social Share
					 */
					do_action( 'betterdocs_docs_before_social' );

					// $view_object->get( 'templates/parts/social-2' );

					/**
					 * Feedback Form
					 */
					//$view_object->get( 'templates/feedback-form' );
					echo '</div>';
					endwhile;
					$view_object->get( 'templates/parts/navigation' );
					$view_object->get( 'templates/parts/credit' );
					$view_object->get( 'templates/parts/comment' );
				?>
			</div>
		</div> <!-- #main -->
		<?php
		if ( $enable_toc ) {
			$view_object->get(
				'templates/sidebars/sidebar-right',
				[
					'feedback'     => betterdocs()->customizer->defaults->get( 'betterdocs_post_reactions' ),
					'social_share' => true
				]
			);
		}
		?>
	</div> <!-- #primary -->
</div> <!-- .betterdocs-single-wraper -->
<?php
get_footer();
