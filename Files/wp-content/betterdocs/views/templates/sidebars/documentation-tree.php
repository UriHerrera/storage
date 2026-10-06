<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract().

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! isset( $force ) || null === $force ) && isset( $layout_type ) && 'template' === $layout_type && ! betterdocs()->settings->get( 'enable_sidebar_cat_list' ) ) {
	return;
}

$nx_wrapper_attributes = [
	'class' => [
		'betterdocs-sidebar',
		'betterdocs-full-sidebar-left',
		'betterdocs-sidebar-layout-7',
		'betterdocs-background',
		'nx-docs-sidebar-host',
	],
	'id'    => 'betterdocs-full-sidebar-left',
];

if ( isset( $wrapper_attr_array ) && is_array( $wrapper_attr_array ) && ! empty( $wrapper_attr_array ) ) {
	$nx_wrapper_attributes = betterdocs()->views->merge( $wrapper_attr_array, $nx_wrapper_attributes );
}

$nx_wrapper_attributes = betterdocs()->template_helper->get_html_attributes( $nx_wrapper_attributes );
$nx_sidebar_search      = isset( $sidebar_search ) ? (bool) $sidebar_search : true;
$nx_current_post_id     = is_singular( 'docs' ) ? get_queried_object_id() : 0;
$nx_current_term_id     = is_tax( 'doc_category' ) ? get_queried_object_id() : 0;
$nx_active_term_ids     = [];

if ( $nx_current_post_id ) {
	$nx_assigned_term_ids = wp_get_post_terms( $nx_current_post_id, 'doc_category', [ 'fields' => 'ids' ] );
	if ( ! is_wp_error( $nx_assigned_term_ids ) ) {
		foreach ( $nx_assigned_term_ids as $nx_assigned_term_id ) {
			$nx_active_term_ids[] = (int) $nx_assigned_term_id;
			$nx_active_term_ids   = array_merge( $nx_active_term_ids, array_map( 'intval', get_ancestors( $nx_assigned_term_id, 'doc_category' ) ) );
		}
	}
} elseif ( $nx_current_term_id ) {
	$nx_active_term_ids = [ (int) $nx_current_term_id ];
	$nx_active_term_ids = array_merge( $nx_active_term_ids, array_map( 'intval', get_ancestors( $nx_current_term_id, 'doc_category' ) ) );
}

$nx_active_term_ids = array_values( array_unique( $nx_active_term_ids ) );
$nx_terms_orderby   = betterdocs()->settings->get( 'terms_orderby', 'betterdocs_order' );
$nx_terms_order     = betterdocs()->settings->get( 'terms_order', 'ASC' );

if ( betterdocs()->settings->get( 'alphabetically_order_term' ) ) {
	$nx_terms_orderby = 'name';
}

$nx_terms_query = betterdocs()->query->terms_query(
	[
		'taxonomy'   => 'doc_category',
		'hide_empty' => false,
		'orderby'    => $nx_terms_orderby,
		'order'      => $nx_terms_order,
	]
);
$nx_terms       = get_terms( apply_filters( 'nx_docs_sidebar_terms_args', $nx_terms_query ) );
$nx_terms       = is_wp_error( $nx_terms ) ? [] : $nx_terms;
$nx_term_map    = [];

foreach ( $nx_terms as $nx_term ) {
	$nx_term_map[ (int) $nx_term->parent ][] = $nx_term;
}

$nx_docs_orderby = betterdocs()->settings->get( 'alphabetically_order_post', 'betterdocs_order' );
$nx_docs_order   = betterdocs()->settings->get( 'docs_order', 'ASC' );
$nx_docs_cache   = [];
$nx_count_cache  = [];
$nx_document_icon_setting = betterdocs()->settings->get( 'category_grid_document_icon', [] );
$nx_document_icon_url     = '';

if ( is_array( $nx_document_icon_setting ) ) {
	if ( ! empty( $nx_document_icon_setting['url'] ) ) {
		$nx_document_icon_url = $nx_document_icon_setting['url'];
	} elseif ( isset( $nx_document_icon_setting['value'] ) && is_array( $nx_document_icon_setting['value'] ) && ! empty( $nx_document_icon_setting['value']['url'] ) ) {
		$nx_document_icon_url = $nx_document_icon_setting['value']['url'];
	}
} elseif ( is_string( $nx_document_icon_setting ) ) {
	$nx_document_icon_url = $nx_document_icon_setting;
}

$nx_document_icon = ! empty( $nx_document_icon_url ) ? [ 'value' => [ 'url' => $nx_document_icon_url ] ] : 'list';

$nx_get_docs = static function ( $nx_term ) use ( &$nx_docs_cache, $nx_docs_orderby, $nx_docs_order ) {
	$nx_term_id = (int) $nx_term->term_id;
	if ( isset( $nx_docs_cache[ $nx_term_id ] ) ) {
		return $nx_docs_cache[ $nx_term_id ];
	}

	$nx_query_args = betterdocs()->query->docs_query_args(
		[
			'term_id'        => $nx_term_id,
			'term_slug'      => $nx_term->slug,
			'posts_per_page' => -1,
			'orderby'        => $nx_docs_orderby,
			'order'          => $nx_docs_order,
		]
	);
	$nx_query = new WP_Query( $nx_query_args );
	$nx_docs_cache[ $nx_term_id ] = $nx_query->posts;

	return $nx_docs_cache[ $nx_term_id ];
};

$nx_get_count = static function ( $nx_term ) use ( &$nx_count_cache ) {
	$nx_term_id = (int) $nx_term->term_id;
	if ( ! isset( $nx_count_cache[ $nx_term_id ] ) ) {
		$nx_count_cache[ $nx_term_id ] = (int) betterdocs()->query->get_docs_count( $nx_term, true );
	}

	return $nx_count_cache[ $nx_term_id ];
};

$nx_render_category_icon = static function ( $nx_term ) {
	$nx_image_id = (int) get_term_meta( $nx_term->term_id, 'doc_category_image-id', true );
	if ( $nx_image_id ) {
		echo wp_kses_post(
			wp_get_attachment_image(
				$nx_image_id,
				'thumbnail',
				false,
				[
					'class'    => 'nx-docs-tree__category-image',
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
				]
			)
		);
		return;
	}
	?>
	<svg class="nx-docs-tree__folder" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.75 5.5h5.1l1.7 2h9.7v11H3.75z" fill="currentColor"/><path d="M3.75 7.5h16.5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
	<?php
};

$nx_render_document_icon = static function () use ( $nx_document_icon ) {
	echo betterdocs()->template_helper->icon( $nx_document_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
};

$nx_render_terms = null;
$nx_render_terms = static function ( $nx_parent_id, $nx_depth = 0 ) use ( &$nx_render_terms, $nx_term_map, $nx_active_term_ids, $nx_current_post_id, $nx_current_term_id, $nx_get_docs, $nx_get_count, $nx_render_category_icon, $nx_render_document_icon ) {
	if ( empty( $nx_term_map[ $nx_parent_id ] ) ) {
		return;
	}

	foreach ( $nx_term_map[ $nx_parent_id ] as $nx_term ) {
		$nx_term_id  = (int) $nx_term->term_id;
		$nx_docs     = $nx_get_docs( $nx_term );
		$nx_children = isset( $nx_term_map[ $nx_term_id ] ) ? array_values( array_filter( $nx_term_map[ $nx_term_id ], static function ( $nx_child ) use ( $nx_get_count ) { return $nx_get_count( $nx_child ) > 0; } ) ) : [];
		$nx_count    = $nx_get_count( $nx_term );

		if ( $nx_count < 1 && empty( $nx_docs ) && empty( $nx_children ) ) {
			continue;
		}

		$nx_is_top      = 0 === $nx_depth;
		$nx_is_open     = in_array( $nx_term_id, $nx_active_term_ids, true );
		$nx_is_current  = $nx_current_term_id === $nx_term_id;
		$nx_has_content = ! empty( $nx_docs ) || ! empty( $nx_children );
		$nx_panel_id    = 'nx-docs-branch-' . $nx_term_id;
		$nx_row_class   = $nx_is_top ? 'nx-docs-tree__row nx-docs-tree__row--root' : 'nx-docs-tree__row nx-docs-tree__row--category';
		?>
		<li class="<?php echo $nx_is_top ? 'nx-docs-tree__section' : 'nx-docs-tree__category'; ?><?php echo $nx_is_open ? ' is-open' : ''; ?>" data-term-id="<?php echo esc_attr( (string) $nx_term_id ); ?>">
			<div class="<?php echo esc_attr( $nx_row_class ); ?>" style="--nx-depth:<?php echo esc_attr( (string) $nx_depth ); ?>">
				<span class="nx-docs-tree__icon"><?php $nx_render_category_icon( $nx_term ); ?></span>
				<span class="nx-docs-tree__category-link"<?php echo $nx_is_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $nx_term->name ); ?></span>
				<?php if ( $nx_is_top ) : ?>
					<span class="nx-docs-tree__count" aria-label="<?php echo esc_attr( sprintf( _n( '%d document', '%d documents', $nx_count, 'betterdocs' ), $nx_count ) ); ?>"><?php echo esc_html( (string) $nx_count ); ?></span>
				<?php endif; ?>
				<?php if ( $nx_has_content ) : ?>
					<button type="button" class="nx-docs-tree__toggle" aria-controls="<?php echo esc_attr( $nx_panel_id ); ?>" aria-expanded="<?php echo $nx_is_open ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( $nx_is_open ? __( 'Collapse %s', 'betterdocs' ) : __( 'Expand %s', 'betterdocs' ), $nx_term->name ) ); ?>" data-expand-label="<?php echo esc_attr( sprintf( __( 'Expand %s', 'betterdocs' ), $nx_term->name ) ); ?>" data-collapse-label="<?php echo esc_attr( sprintf( __( 'Collapse %s', 'betterdocs' ), $nx_term->name ) ); ?>"><span aria-hidden="true"></span></button>
				<?php endif; ?>
			</div>

			<?php if ( $nx_has_content ) : ?>
				<div id="<?php echo esc_attr( $nx_panel_id ); ?>" class="nx-docs-tree__branch"<?php echo $nx_is_open ? '' : ' hidden'; ?>>
					<ul class="nx-docs-tree__items">
						<?php foreach ( $nx_docs as $nx_doc ) :
							$nx_doc_active = $nx_current_post_id === (int) $nx_doc->ID;
							?>
							<li class="nx-docs-tree__document<?php echo $nx_doc_active ? ' is-current' : ''; ?>">
								<a class="nx-docs-tree__document-link" href="<?php echo esc_url( get_permalink( $nx_doc ) ); ?>" style="--nx-depth:<?php echo esc_attr( (string) ( $nx_depth + 1 ) ); ?>"<?php echo $nx_doc_active ? ' aria-current="page"' : ''; ?>>
									<span class="nx-docs-tree__document-marker"><?php $nx_render_document_icon(); ?></span>
									<span><?php echo esc_html( get_the_title( $nx_doc ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
						<?php $nx_render_terms( $nx_term_id, $nx_depth + 1 ); ?>
					</ul>
				</div>
			<?php endif; ?>
		</li>
		<?php
	}
};
?>
<aside <?php echo $nx_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr__( 'Documentation navigation', 'betterdocs' ); ?>">
	<div class="betterdocs-sidebar-content betterdocs-height nx-docs-sidebar">
		<?php
		if ( $nx_sidebar_search ) {
			$nx_search_placeholder = betterdocs()->settings->get( 'search_placeholder' );
			$nx_search_type        = betterdocs()->settings->get( 'search_modal_search_type' );
			$nx_ai_search          = betterdocs()->helper->is_ai_chatbot_enabled() && betterdocs()->settings->get( 'enable_ai_powered_search', false );
			$nx_search_shortcode   = sprintf(
				'[betterdocs_search_modal enable_docs_search="%1$s" enable_faq_search="%2$s" faq_categories_ids="%3$s" doc_ids="%4$s" doc_categories_ids="%5$s" number_of_docs="%6$s" number_of_faqs="%7$s" layout="sidebar" placeholder="%8$s" enable_ai_powered_search="%9$s"]',
				in_array( $nx_search_type, [ 'all', 'docs' ], true ) ? 'true' : 'false',
				in_array( $nx_search_type, [ 'all', 'faq' ], true ) ? 'true' : 'false',
				esc_attr( sanitize_text_field( isset( $faq_term_ids ) ? $faq_term_ids : '' ) ),
				esc_attr( sanitize_text_field( isset( $doc_ids ) ? $doc_ids : '' ) ),
				esc_attr( sanitize_text_field( isset( $doc_term_ids ) ? $doc_term_ids : '' ) ),
				esc_attr( (string) ( isset( $number_of_docs ) ? $number_of_docs : '' ) ),
				esc_attr( (string) ( isset( $number_of_faqs ) ? $number_of_faqs : '' ) ),
				esc_attr( $nx_search_placeholder ),
				$nx_ai_search ? 'true' : 'false'
			);
			echo do_shortcode( $nx_search_shortcode );
		}
		?>
		<nav class="nx-docs-tree" aria-label="<?php echo esc_attr__( 'Documentation categories', 'betterdocs' ); ?>">
			<ul class="nx-docs-tree__root">
				<?php $nx_render_terms( 0 ); ?>
			</ul>
		</nav>
	</div>
</aside>
