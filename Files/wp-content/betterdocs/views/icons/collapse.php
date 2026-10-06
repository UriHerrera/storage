<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$collapse_term_name = isset( $term->name ) ? $term->name : __( 'category', 'betterdocs' );
$collapse_expanded  = empty( $is_lazy_body );
$collapse_target    = isset( $category_body_id ) ? $category_body_id : '';
?>
<button type="button" class="betterdocs-category-collapse" aria-expanded="<?php echo $collapse_expanded ? 'true' : 'false'; ?>"<?php echo '' !== $collapse_target ? ' aria-controls="' . esc_attr( $collapse_target ) . '"' : ''; ?> data-expand-label="<?php echo esc_attr( sprintf( __( 'Expand %s', 'betterdocs' ), $collapse_term_name ) ); ?>" data-collapse-label="<?php echo esc_attr( sprintf( __( 'Collapse %s', 'betterdocs' ), $collapse_term_name ) ); ?>" aria-label="<?php echo esc_attr( sprintf( $collapse_expanded ? __( 'Collapse %s', 'betterdocs' ) : __( 'Expand %s', 'betterdocs' ), $collapse_term_name ) ); ?>">
	<span class="betterdocs-category-chevron" aria-hidden="true"></span>
</button>
