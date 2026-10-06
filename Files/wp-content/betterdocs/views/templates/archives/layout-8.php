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
	 * @package    WPDeveloper/BetterDocs
	 * @subpackage BetterDocs/public
	 */

	get_header();

	$terms_orderby = betterdocs()->settings->get( 'terms_orderby' );
	$terms_order   = betterdocs()->settings->get( 'terms_order' );
	$order_term    = betterdocs()->settings->get( 'alphabetically_order_term', false ); // removed settings value
	$title_tag     = betterdocs()->customizer->defaults->get( 'betterdocs_category_title_tag' );
	$title_tag     = betterdocs()->template_helper->is_valid_tag( $title_tag );
	$border_bottom = betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_box_border_bottom' );
	$column        = betterdocs()->customizer->defaults->get( 'betterdocs_docs_page_column_number_layout_8' );
	$category_title_link = betterdocs()->settings->get( 'category_title_link' );

	if ( $order_term ) {
		$terms_orderby = 'name';
	}
?>

<div class="betterdocs-wrapper betterdocs-docs-archive-wrapper betterdocs-category-layout-8 betterdocs-box-layout betterdocs-wraper">
	<?php betterdocs()->template_helper->search(); ?>

	<div class="betterdocs-content-wrapper betterdocs-archive-wrap betterdocs-archive-main">
		<?php
			$attributes = betterdocs()->template_helper->shortcode_atts(
				[
					'title_tag'     => "$title_tag",
					'terms_order'   => "$terms_order",
					'terms_orderby' => esc_html( $terms_orderby ),
					'last_update'   => true,
					'column'        => $column,
					'category_title_link' => $category_title_link,
					'show_icon'     => betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_show_category_icon' )
				],
				'betterdocs_category_grid_3',
				'layout-3'
			);

			echo do_shortcode( '[betterdocs_category_grid_3 ' . $attributes . ']' );

			/**
			 * @todo faq views will here.
			 */
			betterdocs()->views->get( 'templates/faq' );
			?>
	</div>
</div>

<?php
	/**
	 * Footer
	 */
get_footer();
