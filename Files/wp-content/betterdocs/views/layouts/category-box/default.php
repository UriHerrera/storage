<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$attributes = [
		'href'    => esc_url( $permalink ),
		'data-id' => isset( $term->term_id ) ? $term->term_id : 0,
		'class'   => [ 'betterdocs-single-category-wrapper category-box' ]
	];

	if ( isset( $wrapper_class ) && is_array( $wrapper_class ) && ! empty( $wrapper_class ) ) {
		$attributes['class'] = array_merge( $attributes['class'], $wrapper_class );
	}

	$attributes = betterdocs()->template_helper->get_html_attributes( $attributes );
	?>

<a
	<?php echo $attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="betterdocs-single-category-inner">
		<?php $view_object->get( 'layout-parts/header' ); ?>
	</div>
</a>
