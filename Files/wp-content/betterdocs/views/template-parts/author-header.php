<?php
    
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( empty( $author_name ) || empty( $author_docs_count ) ) {
        return;
    }
?>

<div class="betterdocs-main-category-folder">
    <div class="betterdocs-category-header">
        <div class="betterdocs-category-header-inner">
            <div class="betterdocs-category-icon">
                <?php echo wp_kses_post( $avatar_tag ); ?>
            </div>
            <h2 class="betterdocs-category-title"><?php echo esc_html( $author_name ); ?></h2>
            <div data-count="2" class="betterdocs-sub-category-items-counts">
                <span><?php echo esc_html( $author_docs_count . ' ' . __( 'Docs', 'betterdocs' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
