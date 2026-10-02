<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use WPDeveloper\BetterDocs\Utils\Helper;

if ( ! $nested_subcategory ) {
	return;
}

$_terms_args = [
	'parent'     => $term_id,
	'hide_empty' => true
];

// User-controlled exclusion list for nested category rendering — exclude is required UX.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
if ( isset( $terms_exclude ) ) {
	$_terms_args['exclude'] = $terms_exclude;
} elseif ( isset( $exclude ) ) {
	$_terms_args['exclude'] = $exclude;
}
// phpcs:enable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude

$nested_terms_query = isset( $nested_terms_query ) ? array_merge( $_terms_args, $nested_terms_query ) : $_terms_args;

$_nested_categories = get_terms( betterdocs()->query->terms_query( apply_filters( 'betterdocs_nested_terms_args', $nested_terms_query ) ) );

if ( empty( $_nested_categories ) ) {
	return;
}

$_nested_term_ids = wp_list_pluck( $_nested_categories, 'term_id' );
if ( ! empty( $_nested_term_ids ) ) {
	update_termmeta_cache( $_nested_term_ids );
}

// Fragment cache: cache the rendered HTML of the entire nested subtree at the
// outermost invocation. The active-branch highlighting is baked into the
// rendered HTML server-side (see the $classes / inline-style computation
// below), so the cache key must vary by the current page's identity as well as
// the top-level term, caps, and kb (see the key composition further down).
// (`static` at file scope doesn't persist across the recursive include, so
// the depth tracker has to live on a global.)
global $bd_nested_depth;
if ( ! isset( $bd_nested_depth ) ) {
	$bd_nested_depth = 0;
}
$bd_is_outermost = ( $bd_nested_depth === 0 );
$bd_cache_key    = '';

if ( $bd_is_outermost ) {
	$bd_can_priv  = current_user_can( 'read_private_docs' ) ? 1 : 0;
	$bd_multi_kb  = isset( $multiple_knowledge_base ) && $multiple_knowledge_base ? 1 : 0;
	$bd_kb_slug   = isset( $kb_slug ) ? $kb_slug : '';
	$bd_cat_icon  = isset( $category_icon ) ? (string) $category_icon : '';
	// Active-state highlighting is now baked into the rendered HTML (see
	// $classes / inline style computation below). That means the cache key
	// must also vary by the current page's identity, otherwise a fragment
	// rendered while viewing page A would be served unchanged to page B with
	// the wrong .active branch.
	$bd_queried   = (int) get_queried_object_id();
	$bd_is_single = is_singular( 'docs' ) ? 1 : 0;
	$bd_version   = betterdocs()->database->get_cache_version( 'betterdocs_term_counts' );
	$bd_icon_disc  = md5( wp_json_encode( isset( $list_icon_name ) ? $list_icon_name : "" ) );
	$bd_show_icon  = ( isset( $show_list_icon ) && $show_list_icon === false ) ? 0 : 1;
	$bd_order_disc = md5( wp_json_encode( array( isset( $nested_terms_query ) ? $nested_terms_query : array(), isset( $nested_docs_query_args ) ? $nested_docs_query_args : array() ) ) );
	$bd_cache_key = 'bd_nested_frag_' . md5( "v{$bd_version}_term{$term_id}_m{$bd_multi_kb}_k{$bd_kb_slug}_p{$bd_can_priv}_i{$bd_cat_icon}_q{$bd_queried}_s{$bd_is_single}_ic{$bd_icon_disc}_si{$bd_show_icon}_o{$bd_order_disc}" );

	$bd_cached = get_transient( $bd_cache_key );
	if ( false !== $bd_cached ) {
		echo $bd_cached; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	ob_start();
}
$bd_nested_depth++;

// Ensure $layout_type is set
if ( ! isset( $layout_type ) ) {
	$layout_type = '';
}

// if there have list icon url from customizer or shortcodes attribites format it to $list_icon_name
if ( $layout_type == 'template' && isset( $list_icon_url ) && $list_icon_url ) {
	$list_icon_name = array(
		'value' => array(
			'url' => $list_icon_url
		)
	);
}

// Check if list icon should be shown
$_show_list_icon = true;
if ( isset( $show_list_icon ) && $show_list_icon === false ) {
	$_show_list_icon = false;
}

$_icon = $_show_list_icon ? betterdocs()->template_helper->icon( isset( $list_icon_name ) ? $list_icon_name : 'list' ) : '';

// Active-branch detection (mirrors master). Used to set .active class +
// display:block on each nested-category-list <ul> that's in the user's
// current branch, so the parent's body opens with the right state on
// initial paint and Sleek's CSS (.betterdocs-current-category /
// .betterdocs-nested-category-list.active) gets to apply its styling.
$_page_id              = null;
$_category_ids         = [];
$_is_single            = false;
$_is_doc_category      = false;
$_current_doc_category = null;

if ( is_single() ) {
	$_is_single    = true;
	$_page_id      = get_the_ID();
	$_category_ids = wp_get_post_terms( $_page_id, 'doc_category', [ 'fields' => 'ids' ] );
	if ( ! empty( $_category_ids ) && ! is_wp_error( $_category_ids ) ) {
		$ancestors     = get_ancestors( $_category_ids[0], 'doc_category' );
		$_category_ids = array_merge( $_category_ids, $ancestors );
	}
}

if ( is_tax( 'doc_category' ) ) {
	$_is_doc_category       = true;
	$_current_doc_category  = get_queried_object() != null ? get_queried_object()->term_id : '';
	$parent_id              = Helper::get_the_top_most_parent( $_current_doc_category );
	$_category_ids          = get_term_children( $parent_id, 'doc_category' );
	$current_category_index = array_search( $_current_doc_category, $_category_ids );
	$_category_ids          = ! is_bool( $current_category_index ) ? array_slice( $_category_ids, 0, ( $current_category_index + 1 ), true ) : []; // get the range from start to current
}

$_multiple_kb = isset( $multiple_knowledge_base ) ? $multiple_knowledge_base : false;
$_kb_slug     = isset( $kb_slug ) ? $kb_slug : '';

$_default_nested_docs_query_args = [
	'multiple_kb'    => $_multiple_kb,
	'posts_per_page' => -1
];

$nested_docs_query_args = isset( $nested_docs_query_args ) ?
	array_merge( $_default_nested_docs_query_args, $nested_docs_query_args ) :
	$_default_nested_docs_query_args;

$_nested_docs_args = apply_filters( 'betterdocs_nested_docs_args', $nested_docs_query_args );

foreach ( $_nested_categories as $_nested_category ) :
	$_is_in_active_branch = ( $_is_single && in_array( $_nested_category->term_id, $_category_ids ) )
		|| ( $_is_doc_category && in_array( $_nested_category->term_id, $_category_ids ) );
	$_ul_classes = $_is_in_active_branch
		? 'betterdocs-nested-category-list betterdocs-current-category active'
		: 'betterdocs-nested-category-list';
	$_ul_style   = $_is_in_active_branch ? 'display:block;' : 'display:none;';

	$_counts = betterdocs()->query->get_docs_count(
		$_nested_category,
		$nested_subcategory,
		[
			'multiple_knowledge_base' => $_multiple_kb,
			'kb_slug'                 => $_kb_slug,
		]
	);

	if ( $_counts <= 0 ) {
		continue;
	}

	?>
	<li class="betterdocs-nested-category-wrapper" data-bd-term-id="<?php echo (int) $_nested_category->term_id; ?>">
		<span class="betterdocs-nested-category-title">
			<?php
			if ( isset( $category_icon ) && $category_icon == 'folder' ) {
				betterdocs()->template_helper->icon( 'folder', true );
				betterdocs()->template_helper->icon( 'folder-open', true );
			} else {
				betterdocs()->template_helper->icon( 'arrow-right', true );
				betterdocs()->template_helper->icon( 'arrow-down', true );
			}
			?>
			<a href="#"><?php echo esc_html( $_nested_category->name ); ?></a>
		</span>
		<ul class="<?php echo esc_attr( $_ul_classes ); ?>" style="<?php echo esc_attr( $_ul_style ); ?>">
			<?php
				$_nested_docs_args['term_id']   = $_nested_category->term_id;
				$_nested_docs_args['term_slug'] = $_nested_category->slug;

				$_docs_query = betterdocs()->query->docs_query_args( $_nested_docs_args );

				$_docs_query = new WP_Query( $_docs_query );

			if ( $_docs_query->have_posts() ) {
				while ( $_docs_query->have_posts() ) :
					$_docs_query->the_post();
					$_attrs = [
						'href'           => esc_url( get_the_permalink() ),
						'data-bd-doc-id' => (string) get_the_ID(),
					];
					if ( $_page_id === get_the_ID() && Helper::get_tax() != 'doc_category' ) {
						$_attrs['class'] = 'active';
					}
					$_link_attributes = betterdocs()->template_helper->get_html_attributes( $_attrs );

					echo wp_sprintf(
						'<li>%s<a %s>%s</a></li>',
						$_icon, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$_link_attributes, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						// Same per-item filter as category-list.php (API method badges etc.).
						apply_filters( 'betterdocs_docs_list_item_title', betterdocs()->template_helper->kses( get_the_title() ), get_the_ID() ) //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				endwhile;
			}

				wp_reset_postdata();

				$_params = get_defined_vars();
				$_params = isset( $_params['params'] ) ? $_params['params'] : [];

				$_params = wp_parse_args(
					[
						'term_id' => $_nested_category->term_id
					],
					$_params
				);

				betterdocs()->views->get( 'template-parts/nested-categories', $_params );
			?>
		</ul>
	</li>
	<?php
endforeach;

$bd_nested_depth--;

if ( $bd_is_outermost ) {
	$bd_html = ob_get_clean();
	// Never cache an empty render. An empty buffer means every child term was
	// skipped ( $_counts <= 0 ), which is usually a transient data state — stale
	// counts mid-import, docs not yet published. The read guard treats '' as a hit
	// ( '' !== false ), so caching it would pin a blank nested block for 6 hours
	// after the data is already correct. Re-rendering once per request while empty
	// is what lets it self-heal. (#165)
	if ( '' !== $bd_html ) {
		set_transient( $bd_cache_key, $bd_html, HOUR_IN_SECONDS * 6 );
	}
	echo $bd_html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
