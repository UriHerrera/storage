<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$attributes = [
		'href'    => esc_url( $permalink ),
		'data-id' => isset( $term->term_id ) ? esc_attr( $term->term_id ) : 0,
		'class'   => [ 'category-box category-' . esc_attr( $term->slug ) ]
	];

	if ( ! isset( $column ) ) {
		$column = 4;
	}

	if ( $terms_number % $column == 0 ) {
		$wrapper_class[] = 'no-border-right';
	}

	if ( $terms_count <= $reminder ) {
		$wrapper_class[] = 'no-border-bottom';
	}

	if ( $reminder == 0 && $terms_count <= $column ) {
		$wrapper_class[] = 'no-border-bottom';
	}

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
