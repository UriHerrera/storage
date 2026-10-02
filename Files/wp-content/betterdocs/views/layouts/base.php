<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( $terms_query_args == false ) {
	return;
}
	$terms = get_terms( apply_filters( 'betterdocs_base_terms_args', $terms_query_args ) );
	/**
	 * Base Layout Before Wrapper
	 */
	do_action_ref_array( 'betterdocs_base_layout_wrapper_before', [ & $terms ] );
?>

<div
	<?php echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<?php
	/**
	 * Base Layout Before Inner Wrapper
	 */
	do_action_ref_array( 'betterdocs_base_layout_inner_wrapper_before', [ & $terms ] );
?>
	<div
		<?php echo $inner_wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
			$_defined_vars = get_defined_vars();
			$_params       = isset( $_defined_vars['params'] ) ? $_defined_vars['params'] : [];

			$nested_subcategory = isset( $nested_subcategory ) ? $nested_subcategory : false;

			$_current_queried_object_id = null;
			$_current_queried_object    = get_queried_object();

		if ( $_current_queried_object instanceof \WP_Post ) {
			$_current_terms             = get_the_terms( $_current_queried_object->ID, 'doc_category' );
			$_current_queried_object_id = isset( $_current_terms[0]->term_id ) ? $_current_terms[0]->term_id : null;
		} elseif ( $_current_queried_object instanceof \WP_Term ) {
			$_current_queried_object_id = $_current_queried_object->term_id;
		}

			$ancestors = [];

		if ( is_single() && ! empty( $_current_queried_object_id ) ) {
			$_category_ids = wp_get_post_terms( get_the_ID(), 'doc_category', [ 'fields' => 'ids' ] );
			$ancestors     = get_ancestors( $_category_ids[0], 'doc_category' );
		}

		if ( ! is_wp_error( $terms ) ) {
			do_action_ref_array( 'betterdocs_layout_base_loop_start', [ & $terms, &$_defined_vars ] );

			$_docs_query_args = [
				'posts_per_page' => isset( $_params['posts_per_page'] ) ? $_params['posts_per_page'] : 0,
				'multiple_kb'    => isset( $_params['multiple_knowledge_base'] ) ? $_params['multiple_knowledge_base'] : false,
				'kb_slug'        => isset( $_params['kb_slug'] ) ? $_params['kb_slug'] : ''
			];

			$docs_query_args = ! isset( $docs_query_args ) ? $_docs_query_args : wp_parse_args( $docs_query_args, $_docs_query_args );

			$terms_count  = count( $terms );
			$terms_number = 1;

			$_term_ids = wp_list_pluck( $terms, 'term_id' );
			if ( ! empty( $_term_ids ) ) {
				update_termmeta_cache( $_term_ids );
			}

			foreach ( $terms as $term ) {
				$_counts = betterdocs()->query->get_docs_count(
					$term,
					$nested_subcategory,
					[
						'multiple_knowledge_base' => isset( $_params['multiple_knowledge_base'] ) ? $_params['multiple_knowledge_base'] : false,
						'kb_slug'                 => isset( $_params['kb_slug'] ) ? $_params['kb_slug'] : ''
					]
				);

				if ( $_counts <= 0 ) {
					continue;
				}

				if ( $widget_type == 'category-box' || ( $widget_type == 'category-grid' && $layout == 'layout-2' ) ) {
					$_counts = [
						'counts'          => $_counts,
						'prefix'          => ! empty( $count_prefix ) ? $count_prefix : '',
						'suffix'          => ! empty( $count_suffix ) ? $count_suffix : '',
						'suffix_singular' => ! empty( $count_suffix_singular ) ? $count_suffix_singular : ''
					];
				}

				// Count Sub Terms
				$sub_terms_count = 0;
				if ( isset( $_params['taxonomy'] ) && $_params['taxonomy'] == 'doc_category' ) {
					$sub_terms_count = count( betterdocs()->query->get_all_child_term_ids( $_params['taxonomy'], $term->term_id ) );
				} elseif ( isset( $_params['taxonomy'] ) && $_params['taxonomy'] == 'knowledge_base' ) {
					$count_doc_categories = betterdocs()->query->count_doc_categories_for_knowledge_base( $term->slug );
					$sub_terms_count      = $count_doc_categories;
				}


				$permalink = apply_filters(
					'betterdocs_term_permalink',
					get_term_link( $term->term_id, $term->taxonomy ),
					$term,
					'doc_category',
					$_params
				);

				$docs_query_args['term_id']            = $term->term_id;
				$docs_query_args['term_slug']          = $term->slug;
				$docs_query_args['nested_subcategory'] = $nested_subcategory;

				$_params = wp_parse_args(
					[
						'permalink'                 => $permalink,
						'wrapper_class'             => [ $layout ],
						'term'                      => $term,
						'counts'                    => $_counts,
						'terms_count'               => $terms_count,
						'terms_number'              => $terms_number,
						'sub_terms_count'           => $sub_terms_count,
						'queried_object'            => $_current_queried_object,
						'current_queried_object_id' => $_current_queried_object_id,
						'ancestors'                 => $ancestors,
						'query_args'                => betterdocs()->query->docs_query_args( $docs_query_args )
					],
					$_params
				);

				$template_params = apply_filters( 'betterdocs_template_params', $_params, $layout, $term, $widget_type );
				$layout_filename = apply_filters( 'betterdocs_layout_filename', $layout, $layout, $widget_type );
				$layout_filename = sanitize_file_name( $layout_filename );

				betterdocs()->views->get( 'layouts/' . $widget_type . '/' . $layout_filename, $template_params );
				++$terms_number;
				--$terms_count;
			}

			do_action_ref_array( 'betterdocs_layout_base_loop_end', [ & $terms, &$_defined_vars ] );
		} else {
			betterdocs()->views->get( 'layout-parts/no-content' );
		}

			wp_reset_postdata();
		?>
	</div>
	<?php
		/**
		 * Base Layout After Inner Wrapper
		 */
		do_action_ref_array( 'betterdocs_base_layout_inner_wrapper_after', [ & $terms ] );
	?>
	<div class="clearfix"></div>
	<?php if ( isset( $is_edit_mode ) && $is_edit_mode && $widget_type == 'category-grid' ) : ?>
		<script>
			/**
			 * TODO: Not working in Elementor Editor on first Load.
			 */
			jQuery(document).ready(function($) {
				$('.betterdocs-category-grid').each(function() {
					let $grid = $(this),
						$layout_mode = $grid.data('layout-mode'),
						$column = $grid.data('column'),
						$column_space = $grid.data('column_space');
					if ($layout_mode === 'masonry') {
						let masonryItem = $(".betterdocs-single-category-wrapper", $grid);
						let total_margin = ($column - 1) * $column_space;
						masonryItem.css("width", "calc((100% - " + total_margin + "px) / " + parseInt($column) + ")");
						$grid.masonry({
							itemSelector: ".betterdocs-single-category-wrapper",
							percentPosition: true,
							gutter: $column_space
						});
					}
				});
			});
		</script>
	<?php endif; ?>
</div>
<?php
	/**
	 * Base Layout After Wrapper
	 */
	do_action_ref_array( 'betterdocs_base_layout_wrapper_after', [ & $terms ] );
?>
