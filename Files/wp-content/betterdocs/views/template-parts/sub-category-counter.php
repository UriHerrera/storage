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

$prefix                    = apply_filters( 'betterdocs_category_items_counts_prefix', $prefix, get_defined_vars() );
$suffix                    = apply_filters( 'betterdocs_category_items_counts_suffix', $suffix, get_defined_vars() );
$suffix_singular           = apply_filters( 'betterdocs_category_items_counts_suffix_singular', $suffix_singular, get_defined_vars() );
$subcategory_singular_text = isset( $subcategory_text ) ? $subcategory_text : __( 'Sub Category', 'betterdocs' ); // Default singular subcategory text.
$subcategory_plural_text   = isset( $subcategories_text ) ? $subcategories_text : __( 'Sub Categories', 'betterdocs' ); // Default plural subcategory text.

// These are not always supplied by the caller (e.g. TemplateTags::sub_category_counts()
// only passes show_count + counts). Default them so PHP 8.x does not emit
// "Undefined variable" warnings; the defaults preserve the existing branch outcomes.
$taxonomy        = isset( $taxonomy ) ? $taxonomy : '';
$sub_terms_count = isset( $sub_terms_count ) ? $sub_terms_count : 0;
?>

<div data-count="<?php echo esc_attr( $counts ); ?>" class="betterdocs-sub-category-items-counts">
	<?php
	if ( $sub_terms_count > 0 ) {
		echo '<span>';
		if ( $taxonomy === 'knowledge_base' ) {
			/* translators: %s: Number of categories. */
			echo esc_html(
				sprintf(
				// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.MismatchedPlaceholders
					_n(
						'%1$s Category',
						'%1$s Categories',
						$sub_terms_count,
						'betterdocs'
					),
					number_format_i18n( $sub_terms_count )
				)
			);	
		} else {
			// Layout-only format string — not wrapped in _n() because it contains
			// nothing but positional placeholders, and translator typos in the
			// placeholder numbers crash sprintf() on PHP 8.x with ArgumentCountError.
			echo esc_html(
				sprintf(
					'%1$s %2$s',
					esc_html( $sub_terms_count ),
					esc_html( $sub_terms_count === 1 ? $subcategory_singular_text : $subcategory_plural_text )
				)
			);
		}
		echo '</span> <span>|</span>';
	}
	?>
	<span>
		<?php
		// Layout-only format string — not wrapped in _n() because it contains
		// nothing but positional placeholders, and translator typos in the
		// placeholder numbers crash sprintf() on PHP 8.x with ArgumentCountError.
		echo esc_html(
			sprintf(
				'%2$s %1$s %3$s',
				esc_html( $counts ),
				esc_html( $prefix ),
				esc_html( $counts === 1 ? $suffix_singular : $suffix )
			)
		);
		?>
	</span>
</div>
