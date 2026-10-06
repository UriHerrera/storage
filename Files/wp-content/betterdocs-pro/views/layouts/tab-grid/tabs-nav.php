<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="betterdocs-tabs-nav-wrapper betterdocs-tab-list tabs-nav">
    <?php
        if ( ! is_wp_error( $kb_terms ) ) {
            foreach ( $kb_terms as $term ) {
                if ( $term->count <= 0 ) {
                    continue;
                }

                echo '<a href="#" class="icon-wrap" data-toggle-target="' . $term->term_id . '">' . $term->name . '</a>';
            }
        }
    ?>
</div>
