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

$doc_style;
$start_letter_style;
$start_letter_style_;
$alphabet_list_style;
$dictionary_loadmore;
$dictionary_per_page;
$dictionary_docs_loadmore;
$dictionary_loadmore_button_text;
$dictionary_learn_more_text;
$dictionary_explore_more_text;
$dictionary_docs_per_page;
$explore_more_text_color;
$is_customizer;
$show_heading;
$heading_text;
$heading_tag;


if ($doc_style === 'doc-list' && empty($is_customizer)) {
    $start_letter_style = $start_letter_style_;
}

$glossary_layout = betterdocs()->customizer->defaults->get('encyclopedia_doc_style');

// Check if the encyclopedia_prefix parameter is set
$current_letter           = isset($_GET['encyclopedia_prefix']) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['encyclopedia_prefix'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$dictionary_docs_per_page = isset( $widget_type ) && $widget_type == 'shortcode' ? betterdocs()->customizer->defaults->get('encyclopedia_dictionary_docs_per_page_layout_3') : $dictionary_docs_per_page;
$docs_by_letter           = EncyclopediaQuery::docs_sort_by_letter($dictionary_docs_per_page);


$total_doc_pages = 0;
$total_section_pages = 0;

if (!empty($current_letter)) {
    $current_letter_docs = EncyclopediaQuery::get_current_letter_docs($current_letter, "");
    $total_doc_pages = intval(count($current_letter_docs) / intval($dictionary_docs_per_page));
}


$max_sections = intval($dictionary_per_page);

if (empty($current_letter)) {
    $total_section_pages = intval(count(EncyclopediaQuery::docs_sort_by_letter()) / $max_sections);
}

$class_extra_    = isset($widget_type) && $widget_type == 'shortcode' && $glossary_layout === 'doc-list-2' ? ' layout-3' : '';
?>

<div class="betterdocs-encyclopedia-wrapper<?php echo $class_extra_; ?>">
    <?php if (!empty($show_heading) && !empty($heading_text)) : ?>
        <div class="betterdocs-encyclopedia-heading">
            <?php
            $title_tag = !empty($heading_tag) ? $heading_tag : 'h2';
            echo '<' . betterdocs()->template_helper->is_valid_tag($title_tag) . ' class="betterdocs-encyclopedia-title-tag">' . esc_html($heading_text) . '</' . betterdocs()->template_helper->is_valid_tag($title_tag) . '>';
            ?>
        </div>
    <?php endif; ?>
    <div class="betterdocs-laxical-container">
        <div class="encyclopedia-filter-content">

            <?php
            betterdocs_pro()->views->get('layouts/encyclopedia/alphabets', [
                'docs_by_letter' => $docs_by_letter,
                'current_letter' => $current_letter,
                'alphabet_list_style' => $alphabet_list_style
            ]);
            ?>

            <div class="w-dyn-list">
                <div class="betterdocs-encyclopedia layout-card layout-<?php echo esc_attr($doc_style); ?>" id="encyclopedia-container">
                    <?php
                    $shown_sections = 0;

                    // Define the alphabet range
                    // $alphabet_range = range('A', 'Z');

                    $enable_non_latin = betterdocs()->settings->get('encyclopedia_enable_non_latin');
                    $script   = betterdocs()->settings->get('encyclopedia_non_latin_option');
                    $alphabet_range = isset( $excluded_alphabets_range ) && ! empty( $excluded_alphabets_range )  ? $excluded_alphabets_range  : EncyclopediaQuery::get_character_range($enable_non_latin, $script);

                    // Output docs by letter
                    foreach ($alphabet_range as $letter) {
                        if ($shown_sections >= $max_sections) {
                            break;
                        }

                        if ($current_letter && $current_letter !== $letter) {
                            continue; // Skip to the next iteration if the letter doesn't match the query
                        }

                        $explore_count = count(EncyclopediaQuery::get_current_letter_docs($letter, "")) - $dictionary_docs_per_page;

                        if (!empty($docs_by_letter[$letter])) {
                            if ($start_letter_style === 'alphabet-list-view') {
                                betterdocs_pro()->views->get('layouts/encyclopedia/letter', [
                                    'letter' => $letter,
                                    'start_letter_style' => $start_letter_style
                                ]);
                            }

                            echo "<div class='encyclopedia-section-{$letter} section-item'>";
                            // Output letter start

                            if ($start_letter_style !== 'alphabet-list-view') {
                                betterdocs_pro()->views->get('layouts/encyclopedia/letter', [
                                    'letter' => $letter,
                                    'start_letter_style' => $start_letter_style
                                ]);
                            }


                            // Output docs for the letter
                            foreach ($docs_by_letter[$letter] as $doc) {
                                $excerpt = $doc['post_excerpt'];
                                betterdocs_pro()->views->get("layouts/encyclopedia/$doc_style", [
                                    'letter' => $letter,
                                    'doc' => $doc,
                                    'excerpt' => $excerpt,
                                    'dictionary_learn_more_text' => $dictionary_learn_more_text,
                                    'item_heading_tag' => $item_heading_tag,
                                ]);
                            }

                            betterdocs_pro()->views->get("layouts/encyclopedia/explore-count", [
                                'explore_count' => $explore_count,
                                'explore_url' => '?encyclopedia_prefix=' . $letter . '',
                                'doc_style' => $doc_style,
                                'dictionary_explore_more_text' => $dictionary_explore_more_text,
                                'explore_more_text_color' => $explore_more_text_color,
                            ]);

                            echo '</div>';
                            $shown_sections++;
                        }
                    }
                    ?>
                </div>

                <?php
                betterdocs_pro()->views->get("layouts/encyclopedia/loadmore", [
                    'doc_style' => $doc_style,
                    'current_letter' => $current_letter,
                    'dictionary_docs_per_page' => $dictionary_docs_per_page,
                    'dictionary_loadmore' => $dictionary_loadmore,
                    'docs_loadmore' => $dictionary_docs_loadmore,
                    'current_letter' => $current_letter,
                    'shown_sections' => $shown_sections,
                    'alphabet_range' => $alphabet_range,
                    'total_section_pages' => $total_section_pages,
                    'total_doc_pages' => $total_doc_pages,
                    'start_letter_style' => $start_letter_style,
                    'start_letter_style_' => $start_letter_style_,
                    'dictionary_loadmore_button_text' => $dictionary_loadmore_button_text,
                    'item_heading_tag' => $item_heading_tag,


                ]);

                ?>

            </div>


            <?php
            if (!empty($is_customizer)) {
                $alphabets_background_color;
                $encyclopedia_background_color;
                $encyclopedia_item_background_color;
                $alphabets_link_color;
                $alphabets_link_disabled_color;
                $alphabets_link_bg_color;
                $alphabets_link_active_bg_color;
                $alphabets_link_active_color;
                $start_letter_color;
                $start_letter_bg_color;
                $start_letter_inner_bg_color;
                $item_title_color;
                $item_excerpt_color;
                $item_link_color;
                $explore_more_text_color;
                $alphabets_link_font_size;
                $start_letter_font_size;
                $item_title_font_size;
                $item_excerpt_font_size;
                $item_link_font_size;
                $explore_more_font_size;
                $loadmore_button_text_font_size;
                $loadmore_button_text_color;
                $loadmore_button_bg_color;

                ?>

            <?php

            }
            ?>
        </div>
    </div>
</div>
