<?php
    
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
/**
     * Template Archive Docs
     * Layout 4
     *
     * @link       https://wpdeveloper.com
     * @since      1.0.0
     *
     * @package    WPDeveloper/BetterDocs
     * @subpackage BetterDocs/public
     */

    get_header();

    $title_tag    = betterdocs()->customizer->defaults->get( 'betterdocs_mkb_title_tag' );
    $title_tag    = betterdocs()->template_helper->is_valid_tag( $title_tag );
    $popular_docs = betterdocs()->customizer->defaults->get( 'betterdocs_mkb_popular_docs_switch', true );
?>

<div class="betterdocs-wrapper betterdocs-mkb-wrapper betterdocs-mkb-layout-3 betterdocs-classic-layout betterdocs-wraper">
    <?php betterdocs()->template_helper->search();?>

    <div class="betterdocs-content-wrapper betterdocs-archive-wrap betterdocs-archive-main">
        <div class="betterdocs-display-flex">
            <div class="betterdocs-article-list-wrapper">
                <?php
                    $_shortcode_attributes = [
                        'title_tag' => $title_tag,
                        'show_icon' => betterdocs()->customizer->defaults->get( 'betterdocs_mkb_page_show_category_icon' )
                    ];

                    if ( is_tax( 'knowledge_base' ) ) {
                        $_shortcode_attributes['multiple_knowledge_base'] = true;
                    }

                    $attributes = betterdocs()->template_helper->shortcode_atts( $_shortcode_attributes, 'betterdocs_multiple_kb_list', 'layout-3' );
                    echo do_shortcode( '[betterdocs_multiple_kb_list ' . $attributes . ']' );
                ?>
            </div>
            <?php
                if ( $popular_docs ) {
                    $attributes = betterdocs()->template_helper->shortcode_atts( [
                        'title'         => betterdocs()->settings->get( 'betterdocs_popular_docs_text', __( 'Popular Docs', 'betterdocs-pro' ) ),
                        'title_tag'     => betterdocs()->customizer->defaults->get( 'betterdocs_mkb_popular_title_tag' ),
                        'post_per_page' => betterdocs()->settings->get( 'betterdocs_popular_docs_number' ),
                        'list_icon_url' => ! empty( betterdocs()->customizer->defaults->get( 'betterdocs_mkb_popular_list_icon' ) ) ? betterdocs()->customizer->defaults->get( 'betterdocs_mkb_popular_list_icon' ) : ( ! empty( betterdocs()->settings->get( 'docs_list_icon' ) ) ? betterdocs()->settings->get( 'docs_list_icon' )['url'] : '' ),
                        'layout_type'   => 'template'
                    ], 'betterdocs_popular_articles', '' );

                    echo '<div class="betterdocs-popular-article-list-wrapper">';
                    echo do_shortcode( '[betterdocs_popular_articles ' . $attributes . ']' );
                    echo '</div>';
                }
            ?>
        </div>
        <?php betterdocs()->views->get( 'templates/faq-mkb' );?>
    </div>
</div>

<?php
    /**
     * Footer
     */
get_footer();
