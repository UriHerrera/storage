<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $show_icon ) {
	return;
}

	$category_icon_meta_key = empty( $term_icon_meta_key ) ? 'doc_category_image-id' : $term_icon_meta_key;
	$cat_icon_id            = get_term_meta( $term_id, $category_icon_meta_key, true );

	$attr['alt'] = 'betterdocs-category-icon';

if ( isset( $cat_icon_id ) && ( isset( $category_icon ) && ( $category_icon == 'folder' || $category_icon == 'folder-open' ) ) ) {
	$attr['class'] = [ 'betterdocs-category-folder-img' ];
} else {
	$attr['class'] = [ 'betterdocs-category-icon-img' ];
}

if ( $cat_icon_id ) {
	$icon_url    = wp_get_attachment_image_url( $cat_icon_id, 'thumbnail' );
	$attr['alt'] = get_post_meta( $cat_icon_id, '_wp_attachment_image_alt', true );
} else {
	$icon_url = betterdocs()->assets->icon( 'betterdocs-cat-icon.svg', true );
}

	$attr['src']      = esc_url( $icon_url );
	$image_attributes = betterdocs()->template_helper->get_html_attributes( $attr );
?>

<div class="betterdocs-category-icon">
	<?php
	if ( isset( $category_icon ) && $category_icon == 'folder' ) {
		if ( $cat_icon_id ) {
			echo '<span class="betterdocs-folder-icon">';
			echo wp_kses_post( '<img ' . $image_attributes . ' />' );
			echo '</span>';
		} else {
			betterdocs()->template_helper->icon( 'folder', true );
		}
	} elseif ( isset( $category_icon ) && $category_icon == 'folder-open' ) {
		if ( $cat_icon_id ) {
			echo '<span class="betterdocs-folder-icon">';
			echo wp_kses_post( '<img ' . $image_attributes . ' />' );
			echo '</span>';
		} else {
			betterdocs()->template_helper->icon( 'folder-open', true );
		}
	} else {
		echo wp_kses_post( '<img ' . $image_attributes . ' />' );
	}
	?>
</div>
