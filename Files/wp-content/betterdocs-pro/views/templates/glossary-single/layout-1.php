<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
use  WPDeveloper\BetterDocsPro\Utils\Helper;
echo betterdocs()->settings->get('enable_estimated_reading_time') ? do_shortcode('[betterdocs_reading_time]') : '';
?>
<div class="betterdocs-entry-content" data-postid="<?php echo esc_attr(get_the_id()); ?>">
    <?php

    // Get the current term object
    $term = get_queried_object();

    // Display the term description
    $custom_description = get_term_meta( $term->term_id, 'glossary_term_description', true );

    if (!empty($term->description) || !empty($custom_description)) {
        $description = $term->description;
        if(!empty($custom_description)) {
            $description = $custom_description;
        }

        if(!empty($description) && $description  !== 'undefined') {
            echo '<div class="term-description">' . Helper::strip_javascript_inline(wpautop($description)) . '</div>';
        }

    }
    ?>
</div>
