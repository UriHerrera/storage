<?php
    
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( ! ( $popular_search == true && $popular_search !== 'false' && ! empty( betterdocs_pro()->query->popular_search_keyword() ) ) ) {
        return;
    }

    if ( empty( $popular_search_title ) ) {
        $popular_search_title = betterdocs()->customizer->defaults->get( 'betterdocs_popular_search_text' );
    }
?>

<div class="betterdocs-popular-search-keyword">
    <span class="popular-search-title"><?php echo esc_html( $popular_search_title ); ?></span>
    <?php
        foreach ( betterdocs_pro()->query->popular_search_keyword() as $keyword ) {
            echo '<span class="popular-keyword">' . esc_html( $keyword ) . '</span>';
        }
    ?>
</div>
