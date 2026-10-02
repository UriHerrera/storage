<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$_defined_vars    = get_defined_vars();

	$_layout_sequence = [ 'category_icon', 'category_title', 'category_counts' ];

	if ( $layout === 'layout-2' && $widget_type == 'category-grid' ) {
		$_layout_sequence = [ 'category_counts', 'category_title' ];
	}

	if ( $layout === 'layout-3' && $widget_type == 'category-grid' ) {
		$_layout_sequence = [ 'category_icon', 'category_title' ];
	}

	$_defined_vars    = apply_filters( 'betterdocs_header_defined_vars', $_defined_vars, $layout, $widget_type );
	$_layout_sequence = apply_filters( 'betterdocs_header_layout_sequence', $_layout_sequence, $layout, $widget_type, $_defined_vars );
?>

<div class="betterdocs-category-header">
	<div class="betterdocs-category-header-inner">
		<?php
		foreach ( $_layout_sequence as $sequenceName ) {
			if ( is_array( $sequenceName ) && ! empty( $sequenceName ) ) {
				betterdocs()->template_helper->wrapper_div( array_merge( $_defined_vars, $sequenceName ) );
			} else {
				betterdocs()->template_helper->{$sequenceName}( $_defined_vars );
			}
		}
		?>
	</div>
</div>
