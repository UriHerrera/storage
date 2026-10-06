<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( is_front_page() ) {
	return;
}
	$breadcrumbs_layout         = isset( $breadcrumbs_layout ) ? $breadcrumbs_layout : 'layout-1';
	$home_text                  = betterdocs()->settings->get( 'breadcrumb_home_text', 'Home' );
	$home_url                   = betterdocs()->settings->get( 'breadcrumb_home_url' );
	$enable_breadcrumb_category = betterdocs()->settings->get( 'enable_breadcrumb_category' );
	$enable_breadcrumb_title    = betterdocs()->settings->get( 'enable_breadcrumb_title' );
	$builtin_doc_page           = betterdocs()->settings->get( 'builtin_doc_page', true );
	$docs_page                  = betterdocs()->settings->get( 'docs_page' );
	$taxanomy                   = betterdocs()->query->get_taxonomy();
	$post_type                  = 'docs';
	$docs_page_title            = __( 'Docs', 'betterdocs' );
	$li_classes                 = [ 'item-cat', 'item-custom-post-type-docs' ];
	$enable_breadcrumb_home_text = betterdocs()->settings->get('enable_breadcrumb_home_text', true);

if ( $builtin_doc_page || ( ! $builtin_doc_page && $docs_page <= 0 ) ) {
	$post_type_object = get_post_type_object( $post_type );
	$li_classes       = [ 'item-cat', 'item-custom-post-type-' . esc_attr( $post_type ) ];

	$docs_page_url   = get_post_type_archive_link( $post_type );
	$docs_page_title = $post_type_object->labels->name;
} elseif ( $docs_page ) {
	$li_classes      = [ 'item-cat item-custom-docs-page' ];
	$docs_page_url   = get_page_link( $docs_page );
	$docs_page_title = get_the_title( $docs_page );
}

	$breadcrumbs = [
		[
			'li_classes' => [ 'item-home' ],
			'url'        => $home_url,
			'text'       => stripslashes( $home_text )
		],
		[
			'li_classes' => $li_classes,
			'url'        => $docs_page_url,
			'text'       => $docs_page_title
		]
	];

	if( ! $enable_breadcrumb_home_text ) {
		unset($breadcrumbs[0]);
	}

	$breadcrumbs = apply_filters( 'betterdocs_breadcrumb_before_archives', $breadcrumbs );

	// Detect the current archive taxonomy via the queried object (reliable in Elementor theme builder context)
	$_queried_obj = get_queried_object();
	$_is_doc_tag  = $_queried_obj instanceof \WP_Term && $_queried_obj->taxonomy === 'doc_tag';

	if ( $taxanomy == 'doc_category' ) {
		if ( $enable_breadcrumb_category ) {
			$query_obj = get_queried_object();
			$term_id   = $query_obj->term_id;

			$term_parents = betterdocs()->query->get_term_parents( $term_id );
			if ( ! is_wp_error( $term_parents ) && is_array( $term_parents ) ) {
				$breadcrumbs = apply_filters( 'betterdocs_breadcrumb_after_archives', array_merge( $breadcrumbs, $term_parents ) );
			}
		}
	} elseif ( $_is_doc_tag ) {
		// Always append the current tag name: Home > Docs > Tag Title
		$tag_crumb   = [
			[
				'li_classes' => [ 'item-current', 'item-tag-' . esc_attr( $_queried_obj->term_id ), 'current' ],
				'url'        => '',
				'text'       => $_queried_obj->name,
			],
		];
		$breadcrumbs = apply_filters( 'betterdocs_breadcrumb_after_archives', array_merge( $breadcrumbs, $tag_crumb ) );
	} elseif ( is_single() ) {
		global $wp_query, $post;

		if ( $enable_breadcrumb_category ) {
			$cat_terms = [];
			if ( isset( $wp_query->query_vars['doc_category'] ) ) {
				$term = get_term_by( 'slug', $wp_query->query_vars['doc_category'], 'doc_category' );
				if ( ! empty( $term ) ) {
					$cat_terms[] = $term;
				}
			}

			if ( empty( $cat_terms ) ) {
				$cat_terms = wp_get_post_terms( $post->ID, 'doc_category' );
			}

			$breadcrumbs = apply_filters( 'betterdocs_breadcrumb_before_single_category', $breadcrumbs );

			if ( $cat_terms ) {
				$term_parents = betterdocs()->query->get_term_parents( $cat_terms[0]->term_id );
				if ( ! is_wp_error( $term_parents ) && is_array( $term_parents ) ) {
					$breadcrumbs  = array_merge( $breadcrumbs, $term_parents );
				}
			}
		}

		// Check if the post is in a category
		if ( $enable_breadcrumb_title ) {
			$breadcrumbs[] = [
				'li_classes' => [ 'item-current', 'item-' . ( $post->ID != null ? $post->ID : '' ), 'current' ],
				'url'        => '',
				'text'       => betterdocs()->template_helper->kses( get_the_title() )
			];
		}
	}

	if ( empty( $breadcrumbs ) ) {
		return;
	}

	$_wrapper_attr_array = [
		'class' => [ 'betterdocs-breadcrumb' ],
		'id'    => [ 'betterdocs-breadcrumb' ]
	];

	if ( isset( $breadcrumbs_layout ) && ! empty( $breadcrumbs_layout ) ) {
		$br_wrapper_attr_array['class'][] = 'betterdocs-breadcrumb ' . $breadcrumbs_layout;
		$br_wrapper_attr_array['id'][]    = 'betterdocs-breadcrumb';
	} else {
		$br_wrapper_attr_array = $_wrapper_attr_array;
	}


	if ( isset( $widget ) && $widget instanceof \WPDeveloper\BetterDocs\Editors\BlockEditor\Block ) {
		$br_wrapper_attr_array['class'][] = $blockId;
	}

	$wrapper_attr = betterdocs()->template_helper->get_html_attributes( $br_wrapper_attr_array );
	?>

<nav
	<?php echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="betterdocs-breadcrumb-list">
		<?php
		foreach ( $breadcrumbs as $breadcrumb ) :
			$li_classes = [ 'betterdocs-breadcrumb-item' ];
			$li_classes = ! empty( $breadcrumb['li_classes'] ) ? array_merge( $li_classes, $breadcrumb['li_classes'] ) : $li_classes;
			?>
				<li class="<?php echo implode( ' ', $li_classes ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<?php
				if ( empty( $breadcrumb['url'] ) ) {
					echo wp_kses_post( '<span>' . $breadcrumb['text'] . '</span>' );
				} else {
					echo wp_kses_post(
						wp_sprintf(
							"<a href='%s' class='bread-link' title='%s'>%s</a>",
							esc_attr( esc_url( $breadcrumb['url'] ) ),
							esc_attr( stripslashes( $breadcrumb['text'] ) ),
							$breadcrumb['text']
						)
					);
				}
				?>
				</li>
				<?php
				if ( next( $breadcrumbs ) ) :
					if ( $breadcrumbs_layout == 'layout-2' || $breadcrumbs_layout == 'layout-3' ) {
						$view_object->get( 'template-parts/breadcrumb-delimiter-2' );
					} else {
						$view_object->get( 'template-parts/breadcrumb-delimiter-1' );
					}
				endif;
				?>
		<?php endforeach; ?>
	</ul>
</nav>
