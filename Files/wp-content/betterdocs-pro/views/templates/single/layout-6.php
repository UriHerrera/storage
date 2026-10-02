<?php
    /**
     * The template for single doc page
     *
     * @author  WPDeveloper
     * @package Documentation/SinglePage
     */

    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    // View template — variables come from betterdocs(_pro)()->views->get(...)
    // or extract()-equivalents in the loader. Composed HTML fragments are echoed
    // from internal data sources; both rules below are acknowledged-by-design
    // for this template surface.
    // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

    get_header();

    $mods        = betterdocs()->customizer->defaults->generate_defaults();
    $view_object = betterdocs()->views;
?>

<div class="betterdocs-wrapper betterdocs-single-wrapper betterdocs-single-layout-6 betterdocs-single-bohemian-layout betterdocs-single-wraper">
    <?php betterdocs()->template_helper->search();?>

    <div class="betterdocs-content-wrapper">
        <?php
            betterdocs()->views->get( 'templates/sidebars/sidebar-6', [
                'layout_type' => 'template'
            ] );
        ?>
        <div id="betterdocs-single-main" class="betterdocs-content-area betterdocs-single-main">
            <?php
                if (is_tax('glossaries')) {
                    $view_object->get('templates/headers/layout-7');
                    $view_object->get('templates/glossary-single/layout-1');
                } else {
                    while ( have_posts() ): the_post();
                        $view_object->get( 'templates/parts/mobile-nav', [
                            'mobile_sidebar' => true,
                            'mobile_toc' => false
                        ] );
                        $view_object->get( 'templates/headers/layout-6' );
                        $author = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_enable' );
                        $updated_date = betterdocs()->customizer->defaults->get( 'betterdocs_doc_author_date' );
                        if ( $author ) {
                            $view_object->get( 'templates/parts/author', [ 'updated_date' => $updated_date ] );
                        }
                        $view_object->get( 'templates/contents/layout-1' );
                        $view_object->get( 'templates/footer' );
                    endwhile;
                    $view_object->get( 'templates/parts/navigation' );
                    $view_object->get( 'templates/parts/credit' );
                    $view_object->get( 'templates/parts/comment' );
                }

            ?>
        </div> <!-- #main -->
    </div>
</div>
<?php
/**
 * Footer
 */
get_footer();
