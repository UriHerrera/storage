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

    $terms_orderby = betterdocs()->settings->get( 'terms_orderby' );
    $terms_order   = betterdocs()->settings->get( 'terms_order' );
    $order_term    = betterdocs()->settings->get( 'alphabetically_order_term', false );
    $title_tag     = betterdocs()->customizer->defaults->get( 'betterdocs_category_title_tag' );
    $title_tag     = betterdocs()->template_helper->is_valid_tag( $title_tag );

    if ( $order_term ) {
        $terms_orderby = 'name';
    }
?>

<div class="betterdocs-wrapper betterdocs-docs-archive-wrapper betterdocs-category-layout-4 betterdocs-modern-layout betterdocs-wraper">
    <?php betterdocs()->template_helper->search();?>

    <div class="betterdocs-content-wrapper betterdocs-archive-wrap betterdocs-archive-main">
        <?php
            $_shortcode_attributes = [
                'title_tag'     => $title_tag,
                'terms_order'   => $terms_order,
                'terms_orderby' => esc_html( $terms_orderby ),
                'layout_type'   => 'template',
                'list_icon_url' => ! empty( betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_article_list_icon' ) ) ?  betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_article_list_icon' ) : ( ! empty( betterdocs()->settings->get( 'docs_list_icon' ) ) ? betterdocs()->settings->get( 'docs_list_icon' )['url'] : '' )
            ];

            if ( is_tax( 'knowledge_base' ) ) {
				$_shortcode_attributes['multiple_knowledge_base'] = true;
            }

			$attributes = betterdocs()->template_helper->shortcode_atts( $_shortcode_attributes, 'betterdocs_category_grid_2', 'layout-3' );
            echo do_shortcode( '[betterdocs_category_grid_2 ' . $attributes . ']' );

            betterdocs()->views->get( 'templates/faq' );
        ?>
    </div>
</div>

<?php
    /**
     * Footer
     */
get_footer();
