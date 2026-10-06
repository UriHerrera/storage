<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$control_attributes = [
    'doc_style' => $doc_style,
    'dictionary_docs_per_page' => $dictionary_docs_per_page,
    'dictionary_loadmore' => $dictionary_loadmore,
    'docs_loadmore' => $dictionary_docs_loadmore,
    'current_letter' => $current_letter,
    'shown_sections' => $shown_sections,
    'total_section_pages' => $total_section_pages,
    'total_doc_pages' => $total_doc_pages,
    'start_letter_style' => $start_letter_style,
    'dictionary_loadmore_button_text' => $dictionary_loadmore_button_text,
    'dictionary_learn_more_text' => $dictionary_learn_more_text,
    'dictionary_explore_more_text' => $dictionary_explore_more_text,
    'item_heading_tag' => isset($item_heading_tag) ? $item_heading_tag : 'h2',
];

$loadmore_text = 'Load More';
if (!empty($dictionary_loadmore_button_text)) {
    $loadmore_text = $dictionary_loadmore_button_text;
}

$encode_attributes = base64_encode(json_encode($control_attributes));
?>

<?php if ($dictionary_loadmore || $docs_loadmore) : ?>
    <div class="loadmoreBtn-container">
        <?php
            // Output Load More button
            if (empty($current_letter) && !empty($dictionary_loadmore) && $shown_sections < count((array)$alphabet_range) && $total_section_pages > 0) {
                echo '<div class="encyclopedia-loadmore-btn loadMoreBtn" data-total-section-pages="' . esc_attr($total_section_pages) . '" data-control-attributes="' . esc_attr($encode_attributes) . '">' . esc_html($loadmore_text) . '</div>';
            } else if (!empty($docs_loadmore) && $total_doc_pages > 0) {
                echo '<div class="encyclopedia-loadmore-btn loadMoreDocsBtn"  data-total-doc-pages="' . esc_attr($total_doc_pages) . '" data-control-attributes="' . esc_attr($encode_attributes) . '">' . esc_html($loadmore_text) . '</div>';
            }

            ?>
    </div>
<?php endif; ?>
