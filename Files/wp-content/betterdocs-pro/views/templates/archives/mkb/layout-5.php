<?php
    
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
/**
     * Template archive docs
     *
     * @link       https://wpdeveloper.com
     * @since      1.0.0
     *
     * @package    WPDeveloper/BetterDocs
     * @subpackage BetterDocs/public
     */

    get_header();

    $title_tag      = betterdocs()->customizer->defaults->get( 'betterdocs_mkb_title_tag' );
    $title_tag      = betterdocs()->template_helper->is_valid_tag( $title_tag );
    $column         = betterdocs()->customizer->defaults->get( 'betterdocs_sleek_mkb_column_number' );
?>

<div class="betterdocs-wrapper betterdocs-mkb-wrapper betterdocs-mkb-layout-5 betterdocs-box-layout betterdocs-wraper">
    <?php betterdocs()->template_helper->search(); ?>

    <div class="betterdocs-content-wrapper betterdocs-archive-wrap betterdocs-archive-main">
        <?php
            $attributes = betterdocs()->template_helper->shortcode_atts( [
                'title_tag'     => $title_tag,
                'show_icon'     =>  betterdocs()->customizer->defaults->get('betterdocs_mkb_page_show_category_icon'),
                'category_icon' => 'folder',
                'column'        => $column,
            ], 'betterdocs_multiple_kb_3', 'layout-4' );

            echo do_shortcode( '[betterdocs_multiple_kb_3 ' . $attributes . ']' );

            betterdocs()->views->get( 'templates/faq-mkb' );
        ?>
    </div>
</div>

<?php
    /**
     * Footer
     */
get_footer();
