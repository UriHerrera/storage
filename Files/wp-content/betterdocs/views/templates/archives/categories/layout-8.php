<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
	 * Template archive docs
	 *
	 * @link       https://wpdeveloper.com
	 * @since      4.2.0
	 *
	 * @package    BetterDocs
	 * @subpackage BetterDocs/public
	 */

	get_header();

	$view_object      = betterdocs()->views;
	$layout           = betterdocs()->customizer->defaults->get( 'betterdocs_archive_layout_select', 'layout-7' );
	$title_tag           = betterdocs()->customizer->defaults->get( 'content_header_title_tag_layout7', 'h2' );
	$title_tag           = betterdocs()->template_helper->is_valid_tag( $title_tag );
	$column_title_tag    = betterdocs()->customizer->defaults->get( 'archive_column_title_tag_layout7', 'h2' );
	$column_title_tag    = betterdocs()->template_helper->is_valid_tag( $column_title_tag );
	$docs_list_title_tag = betterdocs()->customizer->defaults->get( 'archive_docs_list_title_tag_layout_7', 'h2' );
	$docs_list_title_tag = betterdocs()->template_helper->is_valid_tag( $docs_list_title_tag );
	$enable_pagintion = betterdocs()->settings->get( 'archive_enable_pagination' );
	$sidebar_switch = betterdocs()->customizer->defaults->get( 'sidebar_search_layout_7_toggle' );
	$content_area_search = betterdocs()->customizer->defaults->get( 'archive_content_area_search_toogle' );
	$enable_sidebar_search = isset( $sidebar_search ) ? $sidebar_search : true;
?>

<div class="betterdocs-wrapper betterdocs-taxonomy-wrapper betterdocs-archive-layout-8 betterdocs-category-archive-wrapper betterdocs-wraper">
	<?php
		if ( betterdocs()->customizer->defaults->get( 'archive_search_toogle' ) ) {
			betterdocs()->template_helper->search();
		}
		$content_area_classes = [
			'betterdocs-content-wrapper',
			"doc-category-layout-8"
		];

		$current_category = get_queried_object();
		$term_link        = isset( $current_category->term_id ) ? get_term_link( $current_category->term_id ) : '';
		$page             = get_query_var( 'paged' ) != '' ? get_query_var( 'paged' ) : 1;
		$args             = betterdocs()->query->docs_query_args(
			[
				'term_id'        => $current_category->term_id,
				'term_slug'      => $current_category->slug,
				'posts_per_page' => 10,
				'paged'          => $page,
				'orderby'        => betterdocs()->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
				'order'          => betterdocs()->settings->get( 'docs_order', 'ASC' )
			]
		);

		if ( ! $enable_pagintion ) {
			$args['posts_per_page'] = -1;
			unset( $args['paged'] );
		}

		$post_query         = new WP_Query( $args );
		$total_pages        = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / 10 );
		$_nested_categories = betterdocs()->query->get_child_term_ids_by_parent_id( 'doc_category', $current_category->term_id );
		if ( $_nested_categories ) {
			$sub_terms_count = count( explode( ',', $_nested_categories ) );
		} else {
			$sub_terms_count = 0;
		}
	?>

	<div class="<?php echo esc_attr( implode( ' ', $content_area_classes ) ); ?>">
		<?php
		betterdocs()->template_helper->sidebar(
			'layout-8',
			'template',
			[
				'shortcode_attr' => [
					'nested_subcategory' => betterdocs()->settings->get( 'archive_nested_subcategory' )
				]
			]
		);
		?>

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
					echo '<div class="betterdocs-breadcrumb-search">';
					/**
					 * Breadcrumbs
					 */
					$view_object->get(
						'templates/parts/breadcrumbs',
						[
							'breadcrumbs_layout' => 'layout-3'
						]
					);

					if ( $content_area_search ) {
						$search_attributes = [
							'placeholder'     	 => betterdocs()->settings->get( 'search_placeholder' ),
							'number_of_faqs'     => betterdocs()->customizer->defaults->get( 'search_modal_query_initial_number_of_faqs' ),
							'number_of_docs'     => betterdocs()->customizer->defaults->get( 'search_modal_query_initial_number_of_docs' ),
							'search_button_text' => betterdocs()->customizer->defaults->get( 'search_button_text', __( 'Search', 'betterdocs' ) ),
							'enable_docs_search' => betterdocs()->settings->get('search_modal_search_type', 'all'),
						];

						if( betterdocs()->settings->get('search_modal_search_type', 'all') == 'all' ) {
							$search_attributes['enable_docs_search'] = true;
							$search_attributes['enable_faq_search']  = true;
						} else if( betterdocs()->settings->get('search_modal_search_type', 'all')  == 'docs' ) {
							$search_attributes['enable_docs_search'] = true;
							$search_attributes['enable_faq_search']  = false;
						} else if( betterdocs()->settings->get('search_modal_search_type', 'all')  == 'faq' ) {
							$search_attributes['enable_docs_search'] = false;
							$search_attributes['enable_faq_search']  = true;
						}

						if ( betterdocs()->customizer->defaults->get( 'search_modal_query_type' ) === 'specific_doc_ids' ) {
							$search_attributes['doc_ids'] = betterdocs()->customizer->defaults->get( 'search_modal_query_doc_ids' );
						} elseif ( betterdocs()->customizer->defaults->get( 'search_modal_query_type' ) === 'specific_doc_term_ids' ) {
							$search_attributes['doc_categories_ids'] = betterdocs()->customizer->defaults->get( 'search_modal_query_doc_term_ids' );
						}

						if ( betterdocs()->customizer->defaults->get( 'search_modal_faq_query_type' ) === 'specific_faq_term_ids' ) {
							$search_attributes['faq_categories_ids'] = betterdocs()->customizer->defaults->get( 'search_modal_query_faq_term_ids' );
						}

						if( betterdocs()->helper->is_ai_chatbot_enabled() && betterdocs()->settings->get('enable_ai_powered_search') ) {
							$search_attributes['enable_ai_powered_search'] = true;
						}

						$search_attributes = betterdocs()->template_helper->get_html_attributes( $search_attributes );

						echo do_shortcode( '[betterdocs_search_modal ' . $search_attributes . ' layout="docs-archive" search_button="true"]' );
					}
					echo '</div>';

					$view_object->get(
						'template-parts/archive-header',
						[
							'taxonomy'         => 'doc_category',
							'current_category' => $current_category,
							'sub_terms_count'  => $sub_terms_count,
							'show_icon'        => true,
							'title_tag'        => $title_tag,
							'show_count'       => true,
							'found_posts'      => $post_query->found_posts
						]
					);

					if ( $_nested_categories ) {
						echo '<div class="betterdocs-archive-wrap betterdocs-archive-main betterdocs-categories-folder">';
						$attributes = betterdocs()->template_helper->shortcode_atts(
							[
								'title_tag'     => $column_title_tag,
								'terms'         => $_nested_categories,
								'terms_order'   => betterdocs()->settings->get( 'terms_order', 'ASC' ),
								'terms_orderby' => betterdocs()->settings->get( 'terms_orderby', 'betterdocs_order' ),
								'last_update'   => true,
								'column'        => 3,
								'show_icon'     => betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_show_category_icon' )
							],
							'betterdocs_category_box_3',
							'layout-4'
						);

						if ( betterdocs()->settings->get( 'archive_nested_subcategory' ) ) {
							echo do_shortcode( '[betterdocs_category_box_3 ' . $attributes . ']' );
						}
						echo '</div>';
					}

					$view_object->get(
						'template-parts/archive-doc-list-2',
						[
							'post_query' => $post_query,
							'docs_list_title_tag' => $docs_list_title_tag
						]
					);
					?>

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
</div>

<?php
get_footer();
