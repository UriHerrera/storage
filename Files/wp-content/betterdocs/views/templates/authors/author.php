<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

	
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
wp_enqueue_style('betterdocs-pagination');

    $author_id         = get_query_var( 'author' );
    $page              = get_query_var( 'page' );
    $enable_pagination = false;

    $default_args = [
        'post_type'      => 'docs',
        'author'         => $author_id,
        'posts_per_page' => -1
    ];

    if ( ! empty( $page ) ) { // if page is found enable pagination
        $default_args['paged']          = $page;
        $default_args['posts_per_page'] = 10;
        $enable_pagination              = true;
    }

    $post_query = new WP_Query( $default_args );

    $author_docs_count = count_user_posts( $author_id, 'docs' );
    $author_name       = get_the_author_meta( 'nicename', $author_id );
    $author_avatar_tag = get_avatar( $author_id, 40 );


    get_header();

?>
    <div class="betterdocs-wrapper betterdocs-taxonomy-wrapper betterdocs-archive-layout-7 betterdocs-category-archive-wrapper betterdocs-wraper">
        <div class="betterdocs-content-wrapper betterdocs-display-flex doc-category-layout-7">
            <div id="main" class="betterdocs-content-area">
                <div class="betterdocs-content-inner-area">
                    <?php

                        betterdocs()->views->get( 'template-parts/author-header', [
                            'author_name'       => $author_name,
                            'author_docs_count' => $author_docs_count,
                            'avatar_tag'        => $author_avatar_tag
                        ] );

                        betterdocs()->views->get( 'template-parts/author-archive-doc-list', [
                            'post_query' => $post_query
                        ] );

                        if ( $enable_pagination ) {
                            global $wp;
                            $remove_the_last_two_vars = explode( '/', $wp->request );
                            $new_link 			      = home_url() .'/'. implode( '/', array_slice( $remove_the_last_two_vars, 0, ( count( $remove_the_last_two_vars ) - 2 ) ) ) . '/';
                            $total_pages  			  = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / 10 );

                            betterdocs()->views->get(
                                'template-parts/pagination',
                                [
                                    'total_pages'  => $total_pages,
                                    'link'         => $new_link,
                                    'current_page' => isset( $page ) ? $page : 1,
                                    'template'     => 'doc_category' //bypass the template to be used in authors template
                                ]
                            );
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

<?php
    get_footer();
?>
