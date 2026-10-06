<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="<?php echo esc_attr($blockId); ?> betterdocs-entry-body betterdocs-taxonomy-doc-category">
    <div class="layout-6 betterdocs-category-grid-list-inner-wrapper">
        <div class="betterdocs-single-category-inner">
            <?php
                // Use the pre-processed query args from the block (includes orderby/order)
                $args = isset( $query_args ) ? $query_args : betterdocs()->query->docs_query_args( [
                    'term_id'        => isset( $term->term_id ) ? $term->term_id : '',
                    'term_slug'      => isset( $term->slug ) ? $term->slug : '',
                    'posts_per_page' => $posts_per_page
                ] );

                $post_query = new WP_Query( $args );

                if ( $post_query->have_posts() ):
            ?>
            <div class="betterdocs-body">
                <ul>
                    <?php
                        while ( $post_query->have_posts() ): $post_query->the_post();
                            echo wp_sprintf(
                                '<li><a href="%s"><p>%s</p> %s</a><p>%s</p></li>',
                                esc_attr( esc_url( get_the_permalink() ) ),
                                betterdocs()->template_helper->kses( get_the_title() ),
                                betterdocs()->template_helper->icon( 'arrow-right', false ),
                                wp_trim_words( get_the_content(), 20 )
                            );
                        endwhile;
                        wp_reset_postdata();
                    ?>
                </ul>
                <?php
                    endif;
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// Add pagination support for Layout 2 (handbook layout)
if ( isset( $pagination ) && $pagination ) {
    $current_term = isset( $term ) ? $term : '';
    $posts_per_page = isset( $posts_per_page ) && $posts_per_page > 0 ? $posts_per_page : 10;
    $total_pages = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / $posts_per_page );

    $link = ! is_wp_error( get_term_link( $current_term, 'doc_category' ) ) ? get_term_link( $current_term, 'doc_category' ) : ( get_the_ID() != false ? get_permalink( get_the_ID() ) : '' );

    betterdocs()->views->get(
        'template-parts/pagination',
        [
            'total_pages'  => $total_pages,
            'link'         => $link,
            'current_page' => isset( $page ) ? $page : 1,
            'template'     => 'doc_category'
        ]
    );
}
?>
