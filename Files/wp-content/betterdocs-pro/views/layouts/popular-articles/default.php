<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<aside
    <?php echo $wrapper_attr; ?>>
    <?php
        $tag = betterdocs()->template_helper->is_valid_tag( $title_tag );
        echo wp_kses_post( '<' . $tag . ' class="betterdocs-popular-articles-heading">' . $title . '</' . $tag . '>' );

        $view_object->get( 'template-parts/category-list' );
    ?>
</aside>
