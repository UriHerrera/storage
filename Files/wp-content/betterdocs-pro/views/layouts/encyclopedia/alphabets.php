<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;

global $wp_query;

if (is_page()) {
    $slug = isset($wp_query->query['pagename']) ? $wp_query->query['pagename'] : '';

    update_option('encyclopedia_current_page_slug', $slug);
} elseif (is_single() && empty($wp_query->query['post_type'])) {
    $slug = isset($wp_query->query['name']) ? $wp_query->query['name'] : '';
    update_option('encyclopedia_current_page_slug', $slug);
}

if (empty(get_option('encyclopedia_current_page_slug'))) {
    $slug  = betterdocs()->settings->get('encyclopedia_root_slug', 'encyclopedia');
    update_option('encyclopedia_current_page_slug', $slug);
}

$slug = get_option('encyclopedia_current_page_slug');

$url = home_url() . '/' . $slug . '/';

?>

<div class="encyclopedia-alphabets alphabets-style-<?php echo esc_attr($alphabet_list_style); ?>">
    <ul class="encyclopedia-alphabet-list">
        <?php
        $shown_sections = 0;

        $enable_non_latin = betterdocs()->settings->get('encyclopedia_enable_non_latin');
        $script   = betterdocs()->settings->get('encyclopedia_non_latin_option');
        $range   = isset( $excluded_alphabets_range ) && ! empty($excluded_alphabets_range) ? $excluded_alphabets_range : EncyclopediaQuery::get_character_range($enable_non_latin, $script);

        echo '<li class="alphabet-list-item" data-letter="all"><a href="' . esc_url($url) . '">'. esc_html__('All', 'betterdocs-pro') .'</a></li>';

        foreach ( $range as $letter) {
            $has_docs = 'class="letter-has-no-docs" href="#"';
            if (!empty($docs_by_letter[$letter])) {
                $has_docs = 'href="' . esc_url($url . '?encyclopedia_prefix=' . $letter) . '"';
            }
            $class = ($current_letter === $letter) ? 'alphabet-list-item active' : 'alphabet-list-item';
            echo '<li class="' . esc_attr($class) . '" data-letter="' . esc_attr($letter) . '"><a ' . $has_docs . '>' . esc_html($letter) . '</a></li>';
            $shown_sections++;
        }
        ?>
    </ul>
</div>
