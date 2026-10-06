<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $show_count ) {
	return;
}

	$prefix = $suffix = $suffix_singular = '';

if ( is_array( $counts ) ) {
	$prefix          = $counts['prefix'];
	$_count          = $counts['counts'];
	$suffix          = $counts['suffix'];
	$suffix_singular = $counts['suffix_singular'];
	$counts          = $_count;
}

	$prefix          = apply_filters( 'betterdocs_category_items_counts_prefix', $prefix, get_defined_vars() );
	$suffix          = apply_filters( 'betterdocs_category_items_counts_suffix', $suffix, get_defined_vars() );
	$suffix_singular = apply_filters( 'betterdocs_category_items_counts_suffix_singular', $suffix_singular, get_defined_vars() );
?>

<div data-count="<?php echo esc_attr( $counts ); ?>" class="betterdocs-category-items-counts">
	<span>
		<?php
			// Layout-only format string — not wrapped in _n() because it contains
			// nothing but positional placeholders, and translator typos in the
			// placeholder numbers (e.g. %4$s instead of %3$s) crash sprintf() on
			// PHP 8.x with ArgumentCountError.
			echo esc_html(
				sprintf(
					'%1$s %2$s %3$s',
					esc_html( $prefix ),
					esc_html( $counts ),
					esc_html( $counts === 1 ? $suffix_singular : $suffix )
				)
			);
			?>
	</span>
</div>
