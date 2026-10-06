<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="betterdocs-grid-top-row-wrapper">
    <?php
        $_shortcode_attributes = [
            'terms'                   => $term_ids,
            'column'                  => $column,
            'title_tag'               => $title_tag,
            'terms_order'             => $terms_order,
            'terms_orderby'           => esc_html( $terms_orderby ),
            'multiple_knowledge_base' => $multiple_knowledge_base,
            'show_icon'               => betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_show_category_icon' )
        ];

        $attributes = betterdocs()->template_helper->get_html_attributes( $_shortcode_attributes );
        echo do_shortcode( '[betterdocs_category_box_2 ' . $attributes . ']' );
    ?>
</div>


