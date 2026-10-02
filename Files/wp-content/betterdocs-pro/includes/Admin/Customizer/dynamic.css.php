<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDeveloper\BetterDocs\Utils\CSSGenerator;

$css = new CSSGenerator( $mods );

//Doc Layout 6 Category Image Width
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-term-img', $css->properties( [
    'flex-basis' => 'betterdocs_doc_list_img_width_layout6'
], '%' ) );

//Doc Layout 6 Category Title Padding Bottom
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-dynamic-wrapper', $css->properties( [
    'padding-bottom' => 'betterdocs_doc_page_cat_title_padding_bottom_layout6'
], 'px' ) );

//Doc Category Title Font Size for Layout 6
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-1) .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title', $css->properties( [
    'font-size' => 'betterdocs_doc_page_cat_title_font_size_layout6'
], 'px' ) );

// Item Count Font Size (Doc Page Layout 6)
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'font-size' => 'betterdocs_doc_page_item_count_font_size_layout6'
], 'px' ) );

// Item Count Color (Doc Page Layout 6)
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'color' => 'betterdocs_doc_page_item_count_color_layout6'
] ) );

//Doc Layout 6 Item Count Background
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'background-color' => 'betterdocs_doc_page_item_count_back_color_layout6'
] ) );

//Doc Layout 6 Item Count Border Style, Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'border-style' => 'betterdocs_doc_page_item_count_border_type_layout6',
    'border-color' => 'betterdocs_doc_page_item_count_border_color_layout6'
] ) );

//Doc Layout 6 Item Count Border Width (Top / Right / Bottom / Left)
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'border-top-width'    => 'betterdocs_doc_page_item_count_border_width_top_layout6',
    'border-right-width'  => 'betterdocs_doc_page_item_count_border_width_right_layout6',
    'border-bottom-width' => 'betterdocs_doc_page_item_count_border_width_bottom_layout6',
    'border-left-width'   => 'betterdocs_doc_page_item_count_border_width_left_layout6'
], 'px' ) );

//Doc Layout 6 Item Count Border Radius
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_doc_page_item_count_border_radius_top_left_layout6',
    'border-top-right-radius'    => 'betterdocs_doc_page_item_count_border_radius_top_right_layout6',
    'border-bottom-right-radius' => 'betterdocs_doc_page_item_count_border_radius_bottom_right_layout6',
    'border-bottom-left-radius'  => 'betterdocs_doc_page_item_count_border_radius_bottom_left_layout6'
], 'px' ) );

//Doc Layout 6 Item Count Margin
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'margin-top'    => 'betterdocs_doc_page_item_count_margin_top_layout6',
    'margin-right'  => 'betterdocs_doc_page_item_count_margin_right_layout6',
    'margin-bottom' => 'betterdocs_doc_page_item_count_margin_bottom_layout6',
    'margin-left'   => 'betterdocs_doc_page_item_count_margin_left_layout6'
], 'px' ) );

//Doc Layout 6 Item Count Padding
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-items-counts', $css->properties( [
    'padding-top'    => 'betterdocs_doc_page_item_count_padding_top_layout6',
    'padding-right'  => 'betterdocs_doc_page_item_count_padding_right_layout6',
    'padding-bottom' => 'betterdocs_doc_page_item_count_padding_bottom_layout6',
    'padding-left'   => 'betterdocs_doc_page_item_count_padding_left_layout6'
], 'px' ) );

// Doc Layout 4 Content Overlap
if( betterdocs()->customizer->defaults->get('betterdocs_docs_layout_select') == 'layout-4' && betterdocs()->customizer->defaults->get('betterdocs_search_layout_select') == 'layout-2' ) {
    $css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper', $css->properties( [
        'margin-top' => '-50'
    ], 'px' ) );
} else {
    $css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper', $css->properties( [
        'margin-top' => '-%betterdocs_doc_page_content_overlap%'
    ], 'px' ) );
}

//Box Category Icon for Layout 4
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-box-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header .betterdocs-category-icon .betterdocs-category-icon-img, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-box-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header .betterdocs-category-icon .betterdocs-category-icon-img, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-box-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'height' => 'betterdocs_doc_page_cat_icon_size_l_3_4'
], 'px' ) );

//Doc Category List Title Font Size for Layout 4
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title', $css->properties( [
    'font-size' => 'betterdocs_doc_page_cat_title_font_size2'
], 'px' ) );

//Doc Layout 6 List Font Size || Line Height
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'font-size'   => 'betterdocs_doc_list_font_size_layout6',
    'line-height' => 'betterdocs_doc_list_font_line_height_layout6'
], 'px' ) );

//Doc Layout 6 List Font Weight
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'font-weight' => 'betterdocs_doc_list_font_weight_layout6'
] ) );

//Doc Layout 6 Description Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_doc_list_desc_color_layout6'
] ) );

//Doc Layout 6 Description Font Size
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-description', $css->properties( [
    'font-size' => 'betterdocs_doc_list_desc_font_size_layout6'
], 'px' ) );

//Doc Layout 6 Description Font Weight
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-description', $css->properties( [
    'font-weight' => 'betterdocs_doc_list_desc_font_weight_layout6'
] ) );

//Doc Layout 6 Description Font Line Height
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-description', $css->properties( [
    'line-height' => 'betterdocs_doc_list_desc_line_height_layout6'
], 'px' ) );

//Doc Layout 6 Description Margin Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-category-description', $css->properties( [
    'margin-top'    => 'betterdocs_doc_list_desc_margin_top_layout6',
    'margin-right'  => 'betterdocs_doc_list_desc_margin_right_layout6',
    'margin-bottom' => 'betterdocs_doc_list_desc_margin_bottom_layout6',
    'margin-left'   => 'betterdocs_doc_list_desc_margin_left_layout6'
], 'px' ) );

//Doc Layout 6 List Font Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'color' => 'betterdocs_doc_list_font_color_layout6'
] ) );

//Doc Layout 6 List Hover Font Color || Background Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a:hover', $css->properties( [
    'color'            => 'betterdocs_doc_list_font_color_hover_layout6',
    'background-color' => 'betterdocs_doc_list_back_color_hover_layout6'
] ) );

//Doc Layout 6 List Hover Border Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a:hover', $css->properties( [
    'border-color' => 'betterdocs_doc_list_border_color_hover_layout6'
] ) );

//Doc Layout 6 List Margin TOP | RIGHT | BOTTOM | LEFT
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'margin-top'    => 'betterdocs_doc_list_margin_top_layout6',
    'margin-right'  => 'betterdocs_doc_list_margin_right_layout6',
    'margin-bottom' => 'betterdocs_doc_list_margin_bottom_layout6',
    'margin-left'   => 'betterdocs_doc_list_margin_left_layout6'
], 'px' ) );

//Doc Layout 6 List Padding TOP | RIGHT | BOTTOM | LEFT
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'padding-top'    => 'betterdocs_doc_list_padding_top_layout6',
    'padding-right'  => 'betterdocs_doc_list_padding_right_layout6',
    'padding-bottom' => 'betterdocs_doc_list_padding_bottom_layout6',
    'padding-left'   => 'betterdocs_doc_list_padding_left_layout6'
], 'px' ) );

//Doc Layout 6 List Border Style
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'border-style' => 'betterdocs_doc_list_border_style_layout6'
] ) );

//Doc Layout 6 List Border Width Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'border-top-width'    => 'betterdocs_doc_list_border_top_layout6',
    'border-right-width'  => 'betterdocs_doc_list_border_right_layout6',
    'border-bottom-width' => 'betterdocs_doc_list_border_bottom_layout6',
    'border-left-width'   => 'betterdocs_doc_list_border_left_layout6'
], 'px' ) );

//Doc Layout 6 List Border Width Hover Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a:hover', $css->properties( [
    'border-top-width'    => 'betterdocs_doc_list_border_hover_top_layout6',
    'border-right-width'  => 'betterdocs_doc_list_border_hover_right_layout6',
    'border-bottom-width' => 'betterdocs_doc_list_border_hover_bottom_layout6',
    'border-left-width'   => 'betterdocs_doc_list_border_hover_left_layout6'
], 'px' ) );

//Doc Layout 6 List Border Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a', $css->properties( [
    'border-top-color'    => 'betterdocs_doc_list_border_color_top_layout6',
    'border-right-color'  => 'betterdocs_doc_list_border_color_right_layout6',
    'border-bottom-color' => 'betterdocs_doc_list_border_color_bottom_layout6',
    'border-left-color'   => 'betterdocs_doc_list_border_color_left_layout6'
] ) );

//Doc Layout 6 List Arrow Height
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a svg', $css->properties( [
    'height' => 'betterdocs_doc_list_arrow_height_layout6'
], 'px' ) );

//Doc Layout 6 List Arrow Width
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a svg', $css->properties( [
    'width' => 'betterdocs_doc_list_arrow_width_layout6'
], 'px' ) );

//Doc Layout 6 List Arrow Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-body .betterdocs-articles-list li a svg', $css->properties( [
    'fill' => 'betterdocs_doc_list_arrow_color_layout6'
] ) );

//Doc Layout 6 Explore More Font Size
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a', $css->properties( [
    'font-size' => 'betterdocs_doc_list_explore_more_font_size_layout6'
], 'px' ) );

//Doc Layout 6 Explore More Line Height
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a', $css->properties( [
    'line-height' => 'betterdocs_doc_list_explore_more_font_line_height_layout6'
], 'px' ) );

//Doc Layout 6 Explore More Font Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a', $css->properties( [
    'color' => 'betterdocs_doc_list_explore_more_font_color_layout6'
] ) );

//Doc Layout 6 Explore More Font Weight
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a', $css->properties( [
    'font-weight' => 'betterdocs_doc_list_explore_more_font_weight_layout6'
] ) );

//Doc Layout 6 Explore More Padding TOP | RIGHT | BOTTOM | LEFT
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a', $css->properties( [
    'padding-top'    => 'betterdocs_doc_list_explore_more_padding_top_layout6',
    'padding-right'  => 'betterdocs_doc_list_explore_more_padding_right_layout6',
    'padding-bottom' => 'betterdocs_doc_list_explore_more_padding_bottom_layout6',
    'padding-left'   => 'betterdocs_doc_list_explore_more_padding_left_layout6'
], 'px' ) );

//Doc Layout 6 Explore More Margin TOP | RIGHT | BOTTOM | LEFT
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a', $css->properties( [
    'margin-top'    => 'betterdocs_doc_list_explore_more_margin_top_layout6',
    'margin-right'  => 'betterdocs_doc_list_explore_more_margin_right_layout6',
    'margin-bottom' => 'betterdocs_doc_list_explore_more_margin_bottom_layout6',
    'margin-left'   => 'betterdocs_doc_list_explore_more_margin_left_layout6'
], 'px' ) );

//Doc Layout 6 Explore More Arrow Height
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button svg, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a svg', $css->properties( [
    'height' => 'betterdocs_doc_list_explore_more_arrow_height_layout6'
], 'px' ) );

//Doc Layout 6 Explore More Arrow Width
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button svg, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a svg', $css->properties( [
    'width' => 'betterdocs_doc_list_explore_more_arrow_width_layout6'
], 'px' ) );

//Doc Layout 6 Explore More Arrow Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer button svg, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-inner-wrapper .betterdocs-footer a svg', $css->properties( [
    'fill' => 'betterdocs_doc_list_explore_more_arrow_color_layout6'
] ) );

//Doc Layout 5 Popular Docs Background Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper', $css->properties( [
    'background-color' => 'betterdocs_doc_page_article_list_bg_color_2'
] ) );

//Doc Layout 5 Popular Docs List Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list li a', $css->properties( [
    'color' => 'betterdocs_doc_page_article_list_color_2'
] ) );

//Doc Layout 5 Popular Docs List Hover Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list li a:hover', $css->properties( [
    'color' => 'betterdocs_doc_page_article_list_hover_color_2'
] ) );

//Doc Layout 5 Popular Docs List Font Size
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list li a', $css->properties( [
    'font-size' => 'betterdocs_doc_page_article_list_font_size_2'
], 'px' ) );

//Doc Layout 5 Popular Title Font Size
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-popular-articles-heading', $css->properties( [
    'font-size' => 'betterdocs_doc_page_article_title_font_size_2'
], 'px' ) );

//Doc Layout 5 Popular Title Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-popular-articles-heading', $css->properties( [
    'color' => 'betterdocs_doc_page_article_title_color_2'
] ) );

//Doc Layout 5 Popular Title Hover Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-popular-articles-heading:hover', $css->properties( [
    'color' => 'betterdocs_doc_page_article_title_color_hover_2'
] ) );

//Doc Layout 5 Popular List Icon Color
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list li svg', $css->properties( [
    'fill' => 'betterdocs_doc_page_article_list_icon_color_2'
] ) );

//Doc Layout 5 Popular List Icon Font Size
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list li svg', $css->properties( [
    'min-width' => 'betterdocs_doc_page_article_list_icon_font_size_2',
    'width'     => 'betterdocs_doc_page_article_list_icon_font_size_2',
    'font-size' => 'betterdocs_doc_page_article_list_icon_font_size_2'
], 'px' ) );

//Doc Layout 5 Popular Docs Title Margin
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-popular-articles-heading', $css->properties( [
    'margin-top'    => 'betterdocs_doc_page_popular_title_margin_top',
    'margin-right'  => 'betterdocs_doc_page_popular_title_margin_right',
    'margin-bottom' => 'betterdocs_doc_page_popular_title_margin_bottom',
    'margin-left'   => 'betterdocs_doc_page_popular_title_margin_left'
], 'px' ) );

//Doc Layout 5 Popular Docs List Margin
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list', $css->properties( [
    'margin-top'    => 'betterdocs_doc_page_article_list_margin_top_2',
    'margin-right'  => 'betterdocs_doc_page_article_list_margin_right_2',
    'margin-bottom' => 'betterdocs_doc_page_article_list_margin_bottom_2',
    'margin-left'   => 'betterdocs_doc_page_article_list_margin_left_2'
], 'px' ) );

//Doc Layout 5 Popular Docs Padding
$css->add_rule( '.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-popular-article-list-wrapper .betterdocs-articles-list', $css->properties( [
    'padding-top'    => 'betterdocs_doc_page_popular_docs_padding_top',
    'padding-right'  => 'betterdocs_doc_page_popular_docs_padding_right',
    'padding-bottom' => 'betterdocs_doc_page_popular_docs_padding_bottom',
    'padding-left'   => 'betterdocs_doc_page_popular_docs_padding_left'
], 'px' ) );

// TODO:

// FIXME:

//MKB Content Area Background Color (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper', $css->properties( [
    'background-color' => 'betterdocs_mkb_background_color'
] ) );

//MKB Content Area Background Size (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper', $css->properties( [
    'background-size' => 'betterdocs_mkb_background_size'
] ) );

//MKB Content Area Background Repeat (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper', $css->properties( [
    'background-repeat' => 'betterdocs_mkb_background_repeat'
] ) );

//MKB Content Area Background Attachment (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper', $css->properties( [
    'background-attachment' => 'betterdocs_mkb_background_attachment'
] ) );

//MKB Content Area Background Position (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper', $css->properties( [
    'background-position' => 'betterdocs_mkb_background_position'
] ) );

//MKB Content Area Padding (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_content_padding_top',
    'padding-right'  => 'betterdocs_mkb_content_padding_right',
    'padding-bottom' => 'betterdocs_mkb_content_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_content_padding_left'
], 'px' ) );

//MKB Content Area Width (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper', $css->properties( [
    'width' => 'betterdocs_mkb_content_width'
], '%' ) );

//MKB Content Area Max Width (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper', $css->properties( [
    'max-width' => 'betterdocs_mkb_content_max_width'
], 'px' ) );

//MKB Content Area Background Image (Common Controls For All MKB Layouts)
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper', $css->properties( [
    'background-image' => 'betterdocs_mkb_background_image'
] ) );

//MKB Layout 1 Space Between Columns
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .layout-flex', $css->properties( [
    '--gap' => 'betterdocs_mkb_column_space'
] ) );

//MKB Layout 1 Column Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_bg_color2'
] ) );

//MKB Layout 1 Column Background Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper:hover', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_hover_bg_color'
] ) );

//MKB Layout 1 Column Padding Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_column_padding_top',
    'padding-right'  => 'betterdocs_mkb_column_padding_right',
    'padding-bottom' => 'betterdocs_mkb_column_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_column_padding_left'
], 'px' ) );

//MKB Layout 1 Icon Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'height' => 'betterdocs_mkb_cat_icon_size'
], 'px' ) );

//MKB Layout 1 Column Border Radius
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_mkb_column_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_mkb_column_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_mkb_column_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_mkb_column_borderr_bottomleft'
], 'px' ) );

//MKB Layout 1 Title Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'font-size' => 'betterdocs_mkb_cat_title_font_size'
], 'px' ) );

//MKB Layout 1 Title Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_color'
] ) );

//MKB Layout 1 Title Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_hover_color'
] ) );

//MKB Layout 1 KB Description Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-single-category-wrapper .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_mkb_desc_color'
] ) );

//MKB Layout 1 Item Count Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span', $css->properties( [
    'color' => 'betterdocs_mkb_item_count_color'
] ) );

//MKB Layout 1 Item Count Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span:hover', $css->properties( [
    'color' => 'betterdocs_mkb_item_count_color_hover'
] ) );

//MKB Layout 1 Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span', $css->properties( [
    'font-size' => 'betterdocs_mkb_item_count_font_size'
], 'px' ) );

//MKB Layout 1 Content Space Between Icon | Title | Description | Counter
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_image'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_title'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-single-category-wrapper .betterdocs-category-description', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_desc'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-1 .betterdocs-category-box-wrapper .betterdocs-category-items-counts', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_counter'
], 'px' ) );

//MKB Layout 2 Space Between Columns
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex', $css->properties( [
    '--gap' => 'betterdocs_mkb_column_space'
], '' ) );

//MKB Layout 2 Column Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_bg_color2'
] ) );

//MKB Layout 2 Column Background Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper:hover', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_hover_bg_color'
] ) );

//MKB Layout 2 Column Padding Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_column_padding_top',
    'padding-right'  => 'betterdocs_mkb_column_padding_right',
    'padding-bottom' => 'betterdocs_mkb_column_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_column_padding_left'
], 'px' ) );

//MKB Layout 2 Icon Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'height' => 'betterdocs_mkb_cat_icon_size'
], 'px' ) );

//MKB Layout 2 Column Border Radius
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_mkb_column_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_mkb_column_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_mkb_column_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_mkb_column_borderr_bottomleft'
], 'px' ) );

//MKB Layout 2 Title Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'font-size' => 'betterdocs_mkb_cat_title_font_size'
], 'px' ) );

//MKB Layout 2 Title Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_color'
] ) );

//MKB Layout 2 Title Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_hover_color'
] ) );

//MKB Layout 2 KB Description Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_mkb_desc_color'
] ) );

//MKB Layout 2 Item Count Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span', $css->properties( [
    'color' => 'betterdocs_mkb_item_count_color'
] ) );

//MKB Layout 2 Item Count Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span:hover', $css->properties( [
    'color' => 'betterdocs_mkb_item_count_color_hover'
] ) );

//MKB Layout 2 Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span', $css->properties( [
    'font-size' => 'betterdocs_mkb_item_count_font_size'
], 'px' ) );

//MKB Layout 2 Content Space Between Icon | Title | Description | Counter
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-icon', $css->properties( [
    'margin-right' => 'betterdocs_mkb_column_content_space_image'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_title'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-description', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_desc'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-2 .betterdocs-category-items-counts', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_counter'
], 'px' ) );

//MKB Layout 3 Space Between Columns
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex', $css->properties( [
    '--gap' => 'betterdocs_mkb_column_space'
], '' ) );

//MKB Layout 3 Column Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_bg_color2'
] ) );

//MKB Layout 3 Column Background Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper:hover', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_hover_bg_color'
] ) );

//MKB Layout 3 Column Padding Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_column_padding_top',
    'padding-right'  => 'betterdocs_mkb_column_padding_right',
    'padding-bottom' => 'betterdocs_mkb_column_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_column_padding_left'
], 'px' ) );

//MKB Layout 3 Icon Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'height' => 'betterdocs_mkb_cat_icon_size'
], 'px' ) );

//MKB Layout 3 Column Border Radius
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_mkb_column_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_mkb_column_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_mkb_column_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_mkb_column_borderr_bottomleft'
], 'px' ) );

//MKB Layout 3 Title Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'font-size' => 'betterdocs_mkb_cat_title_font_size'
], 'px' ) );

//MKB Layout 3 Title Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_color'
] ) );

//MKB Layout 3 Title Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_hover_color'
] ) );

//MKB Layout 3 KB Description Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_mkb_desc_color'
] ) );

//MKB Layout 3 Item Count Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span', $css->properties( [
    'color' => 'betterdocs_mkb_item_count_color'
] ) );

//MKB Layout 3 Item Count Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span:hover', $css->properties( [
    'color' => 'betterdocs_mkb_item_count_color_hover'
] ) );

//MKB Layout 3 Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-items-counts span', $css->properties( [
    'font-size' => 'betterdocs_mkb_item_count_font_size'
], 'px' ) );

//MKB Layout 3 Content Space Between Icon | Title | Description | Counter
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-header-inner .betterdocs-category-icon', $css->properties( [
    'margin-right' => 'betterdocs_mkb_column_content_space_image'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_title'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-description', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_desc'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-items-counts', $css->properties( [
    'margin-bottom' => 'betterdocs_mkb_column_content_space_counter'
], 'px' ) );

//MKB Layout 3 Popular Docs Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-content-wrapper .betterdocs-popular-article-list-wrapper', $css->properties( [
    'background-color' => 'betterdocs_mkb_popular_list_bg_color'
] ) );

//MKB Layout 3 Popular Docs List Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-popular-articles-wrapper .betterdocs-articles-list li a', $css->properties( [
    'color' => 'betterdocs_mkb_popular_list_color'
] ) );

//MKB Layout 3 Popular Docs List Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-popular-articles-wrapper .betterdocs-articles-list li a:hover', $css->properties( [
    'color' => 'betterdocs_mkb_popular_list_hover_color'
] ) );

//MKB Layout 3 Popular Docs List Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-popular-articles-wrapper .betterdocs-articles-list li a', $css->properties( [
    'font-size' => 'betterdocs_mkb_popular_list_font_size'
], 'px' ) );

//MKB Layout 3 Popular Title Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-content-wrapper .betterdocs-popular-articles-wrapper .betterdocs-popular-articles-heading', $css->properties( [
    'font-size' => 'betterdocs_mkb_popular_title_font_size'
], 'px' ) );

//MKB LAYOUT 3 Popular Title Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-content-wrapper .betterdocs-popular-articles-wrapper .betterdocs-popular-articles-heading', $css->properties( [
    'color' => 'betterdocs_mkb_popular_title_color'
] ) );

//MKB LAYOUT 3 Popular Title Color Hover
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-content-wrapper .betterdocs-popular-articles-wrapper .betterdocs-popular-articles-heading:hover', $css->properties( [
    'color' => 'betterdocs_mkb_popular_title_color_hover'
] ) );

//MKB LAYOUT 3 Popular List Icon Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-popular-articles-wrapper .betterdocs-articles-list li svg path', $css->properties( [
    'fill' => 'betterdocs_mkb_popular_list_icon_color'
] ) );

//MKB LAYOUT 3 Popular List Icon Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-popular-articles-wrapper .betterdocs-articles-list li svg', $css->properties( [
    'min-width'  => 'betterdocs_mkb_popular_list_icon_font_size',
    'min-height' => 'betterdocs_mkb_popular_list_icon_font_size'
], 'px' ) );

//MKB LAYOUT 3 Popular Docs Title Margin
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-content-wrapper .betterdocs-popular-articles-wrapper .betterdocs-popular-articles-heading', $css->properties( [
    'margin-top'    => 'betterdocs_mkb_popular_title_margin_top',
    'margin-right'  => 'betterdocs_mkb_popular_title_margin_right',
    'margin-bottom' => 'betterdocs_mkb_popular_title_margin_bottom',
    'margin-left'   => 'betterdocs_mkb_popular_title_margin_left'
], 'px' ) );

//MKB LAYOUT 3 Popular Docs Padding | //MKB LAYOUT 3 Popular Docs List Margin
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-3 .betterdocs-popular-articles-wrapper .betterdocs-articles-list', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_popular_docs_padding_top',
    'padding-right'  => 'betterdocs_mkb_popular_docs_padding_right',
    'padding-bottom' => 'betterdocs_mkb_popular_docs_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_popular_docs_padding_left',
    'margin-top'     => 'betterdocs_mkb_popular_list_margin_top',
    'margin-right'   => 'betterdocs_mkb_popular_list_margin_right',
    'margin-bottom'  => 'betterdocs_mkb_popular_list_margin_bottom',
    'margin-left'    => 'betterdocs_mkb_popular_list_margin_left'
], 'px' ) );

//MKB Layout 4 Space Between Columns
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper', $css->properties( [
    'gap' => 'betterdocs_mkb_column_space'
], 'px' ) );

//MKB Layout 4 Column Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_bg_color2'
] ) );

//MKB Layout 4 Column Background Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner:hover', $css->properties( [
    'background-color' => 'betterdocs_mkb_column_hover_bg_color'
] ) );

//MKB Layout 4 Column Padding Top | Right | Bottom | Left -> (Header & Body & Footer)
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header', $css->properties( [
    'padding-top'   => 'betterdocs_mkb_column_padding_top',
    'padding-right' => 'betterdocs_mkb_column_padding_right',
    'padding-left'  => 'betterdocs_mkb_column_padding_left'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-body', $css->properties( [
    'padding-right' => 'betterdocs_mkb_column_padding_right',
    'padding-left'  => 'betterdocs_mkb_column_padding_left'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-footer', $css->properties( [
    'padding-right'  => 'betterdocs_mkb_column_padding_right',
    'padding-bottom' => 'betterdocs_mkb_column_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_column_padding_left'
], 'px' ) );

//MKB Layout 4 Icon Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'height' => 'betterdocs_mkb_cat_icon_size'
], 'px' ) );

//MKB Layout 4 Tab List Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a', $css->properties( [
    'background-color' => 'betterdocs_mkb_list_bg_color'
] ) );

//MKB Layout 4 Tab List Background Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a:hover', $css->properties( [
    'background-color' => 'betterdocs_mkb_list_bg_hover_color'
] ) );

//MKB Layout 4 Tab List Font Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a', $css->properties( [
    'color' => 'betterdocs_mkb_tab_list_font_color'
] ) );

//MKB Layout 4 Active Tab List Font Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a.active', $css->properties( [
    'color' => 'betterdocs_mkb_tab_list_font_color_active'
] ) );

//MKB Layout 4 Active Tab List Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a.active', $css->properties( [
    'background-color' => 'betterdocs_mkb_tab_list_back_color_active'
] ) );

//MKB Layout 4 Tab List Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a', $css->properties( [
    'font-size' => 'betterdocs_mkb_list_font_size'
], 'px' ) );

//MKB Layout 4 Tab List Padding Top | Right | Bottom | Left && MKB Layout 4 Tab List Margin Top | Right | Bottom | Left && MKB Layout 4 Tab List Border Radius Top Left | Top Right | Bottom Right | Bottom Left
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-tab-list a', $css->properties( [
    'padding-top'                => 'betterdocs_mkb_list_column_padding_top',
    'padding-right'              => 'betterdocs_mkb_list_column_padding_right',
    'padding-bottom'             => 'betterdocs_mkb_list_column_padding_bottom',
    'padding-left'               => 'betterdocs_mkb_list_column_padding_left',
    'margin-top'                 => 'betterdocs_mkb_tab_list_margin_top',
    'margin-right'               => 'betterdocs_mkb_tab_list_margin_right',
    'margin-bottom'              => 'betterdocs_mkb_tab_list_margin_bottom',
    'margin-left'                => 'betterdocs_mkb_tab_list_margin_left',
    'border-top-left-radius'     => 'betterdocs_mkb_tab_list_border_topleft',
    'border-top-right-radius'    => 'betterdocs_mkb_tab_list_border_topright',
    'border-bottom-right-radius' => 'betterdocs_mkb_tab_list_border_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_mkb_tab_list_border_bottomleft'
], 'px' ) );

//MKB Layout 4 Column Border Radius
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_mkb_column_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_mkb_column_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_mkb_column_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_mkb_column_borderr_bottomleft'
], 'px' ) );

//MKB Layout 4 Title Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-title:not(a)', $css->properties( [
    'font-size' => 'betterdocs_mkb_cat_title_font_size'
], 'px' ) );
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-title a', $css->properties( [
    'font-size' => 'betterdocs_mkb_cat_title_font_size'
], 'px' ) );

//MKB Layout 4 Title Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-title:not(a)', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_color'
] ) );

//MKB Layout 4 Title Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-title a', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_color'
] ) );

//MKB Layout 4 Title Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-title:not(a):hover', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_hover_color'
] ) );

//MKB Layout 4 Title Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-title a:hover', $css->properties( [
    'color' => 'betterdocs_mkb_cat_title_hover_color'
] ) );

//MKB Layout 4 KB Description Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_mkb_desc_color'
] ) );

// MKB Layout 4 Content Space Between Icon | Title | Description | Counter
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-icon', $css->properties( [
    'margin-right' => 'betterdocs_mkb_column_content_space_image'
], 'px' ) );

// $css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
//     'margin-bottom' => 'betterdocs_mkb_column_content_space_title'
// ], 'px' ) );

// $css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-description', $css->properties( [
//     'margin-bottom' => 'betterdocs_mkb_column_content_space_desc'
// ], 'px' ) );

// $css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-category-items-counts', $css->properties( [
//     'margin-bottom' => 'betterdocs_mkb_column_content_space_counter'
// ], 'px' ) );

//MKB Layout 4 Docs List Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-categories-wrap.multiple-kb .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
    'color' => 'betterdocs_mkb_column_list_color'
] ) );

//MKB Layout 4 Docs List Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
    'color' => 'betterdocs_mkb_column_list_hover_color'
] ) );

//MKB Layout 4 Docs List Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
    'font-size' => 'betterdocs_mkb_column_list_font_size'
], 'px' ) );

//MKB Layout 4 Docs List Margin
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li:not(.betterdocs-nested-category-wrapper)', $css->properties( [
    'margin-top'    => 'betterdocs_mkb_column_list_margin_top',
    'margin-right'  => 'betterdocs_mkb_column_list_margin_right',
    'margin-bottom' => 'betterdocs_mkb_list_margin_bottom',
    'margin-left'   => 'betterdocs_mkb_list_margin_left'
], 'px' ) );

//MKB Doc Layout 4 Explore More Button Text Color || MKB Doc Layout 4 Explore More Button Border Color || MKB Doc Layout 4 Explore More Button Background Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-footer a', $css->properties( [
    'color'            => 'betterdocs_mkb_tab_list_explore_btn_color',
    'border-color'     => 'betterdocs_mkb_tab_list_explore_btn_border_color',
    'background-color' => 'betterdocs_mkb_tab_list_explore_btn_bg_color'
] ) );

//MKB Doc Layout 4 Explore More Button Text Hover Color || MKB Doc Layout 4 Explore More Button Background Hover Color
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-footer a:hover', $css->properties( [
    'color'            => 'betterdocs_mkb_tab_list_explore_btn_hover_color',
    'border-color'     => 'betterdocs_mkb_tab_list_explore_btn_hover_border_color',
    'background-color' => 'betterdocs_mkb_tab_list_explore_btn_hover_bg_color'
] ) );

//MKB Doc Layout 4 Explore More Button Padding || MKB Doc Layout 4 Explore More Button Font Size
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-footer a', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_tab_list_explore_btn_padding_top',
    'padding-right'  => 'betterdocs_mkb_tab_list_explore_btn_padding_right',
    'padding-bottom' => 'betterdocs_mkb_tab_list_explore_btn_padding_bottom',
    'padding-left'   => 'betterdocs_mkb_tab_list_explore_btn_padding_left',
    'font-size'      => 'betterdocs_mkb_tab_list_explore_btn_font_size'
], 'px' ) );

//MKB Doc Layout 4 Explore More Button Border Radius Top Left | Top Right | Bottom right | Bottom left
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-4 .betterdocs-footer a', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_mkb_tab_list_explore_btn_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_mkb_tab_list_explore_btn_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_mkb_tab_list_explore_btn_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_mkb_tab_list_explore_btn_borderr_bottomleft'
], 'px' ) );

/** MKB Layout 5 Controls Start **/
$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box', $css->properties( [
    'background-color' => 'column_mkb_background_color_layout_5'
] ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box:hover', $css->properties( [
    'background-color' => 'betterdocs_mkb_page_column_hover_bg_color_layout_5'
] ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box', $css->properties( [
    'padding-top'    => 'betterdocs_mkb_page_column_padding_top_layout_5',
    'padding-right'  => 'betterdocs_mkb_page_column_padding_right_layout_5',
    'padding-bottom' => 'betterdocs_mkb_page_column_padding_bottom_layout_5',
    'padding-left'   => 'betterdocs_mkb_page_column_padding_left_layout_5'
], 'px' ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box', $css->properties( [
    'border-color' => 'column_mkb_border_color_layout_5'
] ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-folder-icon', $css->properties( [
    'height' => 'betterdocs_mkb_page_cat_icon_size_layout_5',
    'width'  => 'betterdocs_mkb_page_cat_icon_size_layout_5'
], 'px' ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title', $css->properties( [
    'font-size' => 'betterdocs_mkb_page_cat_title_font_size_layout_5'
], 'px' ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_mkb_page_cat_title_color_layout_5'
] ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_mkb_page_cat_title_hover_color'
] ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts', $css->properties( [
    'font-size' => 'betterdocs_mkb_page_item_count_font_size_layout_5'
], 'px' ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts', $css->properties( [
    'color' => 'betterdocs_mkb_page_item_count_color_layout_5'
] ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts:hover', $css->properties( [
    'color' => 'betterdocs_mkb_page_item_count_hover_color_layout_5'
] ) );

/** MKB Layout 5 Controls End **/

/** MKB Layout 6 Controls Start **/
$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update', $css->properties( [
    'font-size' => 'mkb_last_updated_time_layout_5_font_size'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update', $css->properties( [
    'color' => 'mkb_last_updated_time_layout_5_color'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update:hover', $css->properties( [
    'color' => 'mkb_last_updated_time_layout_5_hover_color'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update', $css->properties( [
    'background-color' => 'mkb_last_updated_time_layout_5_background_color'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update:hover', $css->properties( [
    'background-color' => 'mkb_last_updated_time_layout_5_background_hover_color'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update', $css->properties( [
    'padding-top'    => 'mkb_last_updated_time_layout_5_padding_top',
    'padding-right'  => 'mkb_last_updated_time_layout_5_padding_right',
    'padding-bottom' => 'mkb_last_updated_time_layout_5_padding_bottom',
    'padding-left'   => 'mkb_last_updated_time_layout_5_padding_left',
    'margin-top'     => 'mkb_last_updated_time_layout_5_margin_top',
    'margin-right'   => 'mkb_last_updated_time_layout_5_margin_right',
    'margin-bottom'  => 'mkb_last_updated_time_layout_5_margin_bottom',
    'margin-left'    => 'mkb_last_updated_time_layout_5_margin_left'
], 'px' ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts', $css->properties( [
    'margin-top'     => 'mkb_page_item_count_margin_layout_5_margin_top',
    'margin-right'   => 'mkb_page_item_count_margin_layout_5_margin_right',
    'margin-bottom'  => 'mkb_page_item_count_margin_layout_5_margin_bottom',
    'margin-left'    => 'mkb_page_item_count_margin_layout_5_margin_left'
], 'px' ) );

$css->add_rule( '.betterdocs-wrapper.betterdocs-mkb-wrapper.betterdocs-mkb-layout-5 .betterdocs-content-wrapper .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title', $css->properties( [
    'margin-top'     => 'mkb_category_title_margin_layout_5_margin_top',
    'margin-right'   => 'mkb_category_title_margin_layout_5_margin_right',
    'margin-bottom'  => 'mkb_category_title_margin_layout_5_margin_bottom',
    'margin-left'    => 'mkb_category_title_margin_layout_5_margin_left'
], 'px' ) );

/** MKB Layout 6 Controls End **/

/**********************MKB-END**************************/

//Doc Layout 3 BG Hover Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-content-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper:hover', $css->properties( [
    'background-color' => 'betterdocs_doc_page_column_hover_bg_color'
] ) );

//Doc layout 3 BG Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-content-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
    'background-color' => 'betterdocs_doc_page_column_bg_color2'
] ) );

//Doc Layout 3 Column Padding Top | Right | Bottom | Left
//Doc Layout 3 Column Border Radius Top Left | Top Right | Bottom Right | Bottom Left
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-content-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
    'padding-top'                => 'betterdocs_doc_page_column_padding_top',
    'padding-right'              => 'betterdocs_doc_page_column_padding_right',
    'padding-bottom'             => 'betterdocs_doc_page_column_padding_bottom',
    'padding-left'               => 'betterdocs_doc_page_column_padding_left',
    'border-top-left-radius'     => 'betterdocs_doc_page_column_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_doc_page_column_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
], 'px' ) );

// //Doc Layout 3 Tap Box Icon Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
//     'height' => 'betterdocs_doc_page_cat_icon_size_l_3_4',
//     'width'  => 'betterdocs_doc_page_cat_icon_size_l_3_4'
// ], 'px' ) );

// //Doc Layout 3 Category Title Font Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
//     'font-size' => 'betterdocs_doc_page_cat_title_font_size'
// ], 'px' ) );

// //Doc Layout 3 Category Title Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
//     'color' => 'betterdocs_doc_page_cat_title_color2'
// ] ) );

// //Doc Layout 3 Category Title Hover Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title:hover', $css->properties( [
//     'color' => 'betterdocs_doc_page_cat_title_hover_color'
// ] ) );

//Doc Layout 3 Category Title Description Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-description', $css->properties( [
//     'color' => 'betterdocs_doc_page_cat_desc_color'
// ] ) );

// //Doc Layout 3 Item Count Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-items-counts', $css->properties( [
//     'color' => 'betterdocs_doc_page_item_count_color_layout2'
// ] ) );

// //Doc Layout 3 Item Font Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-items-counts', $css->properties( [
//     'font-size' => 'betterdocs_doc_page_item_count_font_size'
// ], 'px' ) );

//Doc Layout 4 Spacing Between Columns
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 ', $css->properties( [
    'margin' => ''
], 'px' ) );

// //Doc Layout 4 Column Background Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-grid-top-row-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper.layout-flex .betterdocs-single-category-wrapper', $css->properties( [
//     'background-color' => 'betterdocs_doc_page_column_bg_color'
// ] ) );

//Doc Layout 4 Column Hover Background Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-grid-top-row-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper:hover', $css->properties( [
//     'background-color' => 'betterdocs_doc_page_column_hover_bg_color'
// ] ) );

// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-grid-top-row-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header-inner:hover', $css->properties( [
//     'background-color' => 'betterdocs_doc_page_column_hover_bg_color'
// ] ) );

// //Doc Layout 4 Column Padding
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-grid-top-row-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
//     'padding-top'    => 'betterdocs_doc_page_column_padding_top',
//     'padding-right'  => 'betterdocs_doc_page_column_padding_right',
//     'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom',
//     'padding-left'   => 'betterdocs_doc_page_column_padding_bottom'
// ], 'px' ) );

// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'padding-top'   => 'betterdocs_doc_page_column_padding_top',
//     'padding-right' => 'betterdocs_doc_page_column_padding_right',
//     'padding-left'  => 'betterdocs_doc_page_column_padding_bottom'
// ], 'px' ) );

// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body', $css->properties( [
//     'padding-right'  => 'betterdocs_doc_page_column_padding_right',
//     'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom',
//     'padding-left'   => 'betterdocs_doc_page_column_padding_bottom'
// ], 'px' ) );

// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
//     'width' => 'auto'
// ] ) );

// //Doc Layout 4 Column Border Radius
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-content-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-grid-top-row-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper, .betterdocs-wrapper.betterdocs-category-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner', $css->properties( [
//     'border-top-left-radius'     => 'betterdocs_doc_page_column_borderr_topleft',
//     'border-top-right-radius'    => 'betterdocs_doc_page_column_borderr_topright',
//     'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
//     'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
// ], 'px' ) );

// //Doc Layout 4 Category Title Font Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .category-grid .betterdocs-category-title', $css->properties( [
//     'font-size' => 'betterdocs_doc_page_cat_title_font_size'
// ], 'px' ) );

// //Doc Layout 4 Category Title Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .category-grid .betterdocs-category-title', $css->properties( [
//     'color' => 'betterdocs_doc_page_cat_title_color2'
// ] ) );

// //Doc Layout 4 Category Title Hover Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .category-grid .betterdocs-category-title:hover', $css->properties( [
//     'color' => 'betterdocs_doc_page_cat_title_hover_color'
// ] ) );

//Doc Layout 4 Category Description Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 ', $css->properties( [
    '' => ''
], 'px' ) );

//Doc Layout 4 Category Content Background Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer', $css->properties( [
    'background-color' => 'betterdocs_doc_page_article_list_bg_color'
] ) );

// //Doc Layout 4 Item Count Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-items-counts', $css->properties( [
//     'color' => 'betterdocs_doc_page_item_count_color_layout2'
// ] ) );

// //Doc Layout 4 Font Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-items-counts', $css->properties( [
//     'font-size' => 'betterdocs_doc_page_item_count_font_size'
// ], 'px' ) );

//Doc Layout 4 Docs List Item Hover Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
    'color' => 'betterdocs_doc_page_article_list_hover_color'
] ) );

//Doc Layout 4 Button Hover Text Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer button:hover, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer a:hover ', $css->properties( [
    'color' => 'betterdocs_doc_page_explore_btn_hover_color'
] ) );

//Doc Layout 4 Button Hover Border Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer button:hover, .betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer a:hover', $css->properties( [
    'border-color' => 'betterdocs_doc_page_explore_btn_hover_border_color'
] ) );

//Doc Layout 5 Button BG Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-content-wrapper .betterdocs-display-flex .betterdocs-article-list-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
    'background-color' => 'betterdocs_doc_page_column_bg_color2'
] ) );

//Doc layout 5 Hover Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-content-wrapper .betterdocs-display-flex .betterdocs-article-list-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper:hover', $css->properties( [
    'background-color' => 'betterdocs_doc_page_column_hover_bg_color'
] ) );

//Doc Layout 5 Column Padding Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-content-wrapper .betterdocs-display-flex .betterdocs-article-list-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
    'padding-top'    => 'betterdocs_doc_page_column_padding_top',
    'padding-right'  => 'betterdocs_doc_page_column_padding_right',
    'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom',
    'padding-left'   => 'betterdocs_doc_page_column_padding_left'
], 'px' ) );

// //Doc Layout 5 Top Box Icon Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
//     'height' => 'betterdocs_doc_page_cat_icon_size_l_3_4'
// ], 'px' ) );

$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
    'width' => 'auto'
] ) );

//Doc Layout 5 Column Border Radius
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-content-wrapper .betterdocs-display-flex .betterdocs-article-list-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_doc_page_column_borderr_topleft',
    'border-top-right-radius'    => 'betterdocs_doc_page_column_borderr_topright',
    'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
], 'px' ) );

// //Doc Layout 5 Category Title Font Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
//     'font-size' => 'betterdocs_doc_page_cat_title_font_size'
// ], 'px' ) );

//Doc Layout 5 Category Title Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_doc_page_cat_title_color2'
] ) );

//Doc Layout 5 Category Title Hover Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_doc_page_cat_title_hover_color'
] ) );

//Doc Layout 5 Category Description Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-description', $css->properties( [
//     'color' => 'betterdocs_doc_page_cat_desc_color'
// ] ) );

// //Doc Layout 5 Item Count Color
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-items-counts', $css->properties( [
//     'color' => 'betterdocs_doc_page_item_count_color_layout2'
// ] ) );

// //Doc Layout 5 Font Size
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-items-counts', $css->properties( [
//     'font-size' => 'betterdocs_doc_page_item_count_font_size'
// ], 'px' ) );

// //Doc Layout 6 Category Title Padding Bottom
// $css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .layout-6.betterdocs-category-grid-list-inner-wrapper .betterdocs-dynamic-wrapper', $css->properties( [
//     'padding-bottom' => 'betterdocs_doc_page_cat_title_padding_bottom_layout6'
// ], 'px' ) );

//Doc Layout 6 Category Title Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .layout-6.betterdocs-category-grid-list-inner-wrapper .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_doc_page_cat_title_color2'
] ) );

//Doc Layout 6 Category Title Hover Color
$css->add_rule( '.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .layout-6.betterdocs-category-grid-list-inner-wrapper .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_doc_page_cat_title_hover_color'
] ) );

//Sidebar Bohemian Layout Background Color
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-sidebar-list-wrapper', $css->properties( [
    'background-color' => 'betterdocs_sidebar_bg_color_layout6'
] ) );

//Sidebar Bohemian Layout Active Background Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-body', $css->properties( [
    'background-color' => 'betterdocs_sidebar_active_bg_color_layout6'
] ) );

//Sidebar Bohemian Layout Active Background Border Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-category-header, .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-body', $css->properties( [
    'border-color' => 'betterdocs_sidebar_active_bg_border_color_layout6'
] ) );

//Sidebar Bohemian Layout Sidebar Padding | Margin | Border Radius
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-sidebar-list-wrapper', $css->properties( [
    'padding-top'                => 'betterdocs_sidebar_padding_top_layout6',
    'padding-right'              => 'betterdocs_sidebar_padding_right_layout6',
    'padding-bottom'             => 'betterdocs_sidebar_padding_bottom_layout6',
    'padding-left'               => 'betterdocs_sidebar_padding_left_layout6',
    'margin-top'                 => 'betterdocs_sidebar_margin_top_layout6',
    'margin-right'               => 'betterdocs_sidebar_margin_right_layout6',
    'margin-bottom'              => 'betterdocs_sidebar_margin_bottom_layout6',
    'margin-left'                => 'betterdocs_sidebar_margin_left_layout6',
    'border-top-left-radius'     => 'betterdocs_sidebar_border_radius_top_left_layout6',
    'border-top-right-radius'    => 'betterdocs_sidebar_border_radius_top_right_layout6',
    'border-bottom-right-radius' => 'betterdocs_sidebar_border_radius_bottom_right_layout6',
    'border-bottom-left-radius'  => 'betterdocs_sidebar_border_radius_bottom_left_layout6'
], 'px' ) );

//Sidebar Bohemian Layout Title Background Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header', $css->properties( [
    'background-color' => 'betterdocs_sidebar_title_bg_color_layout6'
] ) );

//Sidebar Bohemian Layout Active Title Background Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-category-header', $css->properties( [
    'background-color' => 'betterdocs_sidebar_active_title_bg_color_layout6'
] ) );

//Sidebar Bohemian Layout Title Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title', $css->properties( [
    'color' => 'betterdocs_sidebar_title_color_layout6'
] ) );

//Sidebar Bohemian Layout Title Hover Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:hover', $css->properties( [
    'color' => 'betterdocs_sidebar_title_hover_color_layout6'
] ) );

//Sidebar Bohemian Layout Title Font Size | Font Line Height
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title', $css->properties( [
    'font-size'   => 'betterdocs_sidebar_title_font_size_layout6',
    'line-height' => 'betterdocs_sidebar_title_font_line_height_layout6'
], 'px' ) );

//Sidebar Bohemian Layout Title Font Weight
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title', $css->properties( [
    'font-weight' => 'betterdocs_sidebar_title_font_weight_layout6'
] ) );

//Sidebar Bohemian Layout Title Padding | Title Margin
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title', $css->properties( [
    'padding-top'    => 'betterdocs_sidebar_title_padding_top_layout6',
    'padding-right'  => 'betterdocs_sidebar_title_padding_right_layout6',
    'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom_layout6',
    'padding-left'   => 'betterdocs_sidebar_title_padding_left_layout6',
    'margin-top'     => 'betterdocs_sidebar_title_margin_top_layout6',
    'margin-right'   => 'betterdocs_sidebar_title_margin_right_layout6',
    'margin-bottom'  => 'betterdocs_sidebar_title_margin_bottom_layout6',
    'margin-left'    => 'betterdocs_sidebar_title_margin_left_layout6'
], 'px' ) );

//Sidebar Bohemian Layout Term List Border Type | Border Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6', $css->properties( [
    'border-style' => 'betterdocs_sidebar_term_list_border_type_layout6',
    'border-color' => 'betterdocs_sidebar_term_border_width_color_layout6'
] ) );

// Sidebar Term List Border Counter Color Layout 6(For Single Doc Layout 6)
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'border-color' => 'betterdocs_sidebar_term_border_color_counter_layout6'
] ) );

// Sidebar Term List Active Border Counter Color Layout 6(For Single Doc Layout 6)
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'border-color' => 'betterdocs_sidebar_term_border_active_color_counter_layout6'
] ) );

// Sidebar Term List Active Background Counter Color Layout 6(For Single Doc Layout 6)
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'background-color' => 'betterdocs_sidebar_term_active_background_counter_color_layout6'
] ) );

// Sidebar Term List Active Counter Text Color Layout 6(For Single Doc Layout 6)
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6.active .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span', $css->properties( [
    'color' => 'betterdocs_sidebar_term_active_counter_text_color_layout6'
] ) );

//Sidebar Bohemian Layout Term List Border Width (Top | Right | Bottom | Left)
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6', $css->properties( [
    'border-top-width'    => 'betterdocs_sidebar_term_border_top_width_layout6',
    'border-right-width'  => 'betterdocs_sidebar_term_border_right_width_layout6',
    'border-bottom-width' => 'betterdocs_sidebar_term_border_bottom_width_layout6',
    'border-left-width'   => 'betterdocs_sidebar_term_border_left_width_layout6'
], 'px' ) );

//Sidebar Bohemian Layout List Item Color
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li a', $css->properties( [
    'color' => 'betterdocs_sidebar_term_list_item_color_layout6'
] ) );

//Sidebar Bohemian Layout List Item Color Hover
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li a:hover', $css->properties( [
    'color' => 'betterdocs_sidebar_term_list_item_hover_color_layout6'
] ) );

//Sidebar Bohemian Layout List Item Font Size
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li a', $css->properties( [
    'font-size' => 'betterdocs_sidebar_term_list_item_font_size_layout6'
], 'px' ) );

//Sidebar Bohemian Layout List Icon Color
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li svg', $css->properties( [
    'fill' => 'betterdocs_sidebar_term_list_item_icon_color_layout6'
] ) );

//Sidebar Bohemian Layout List Item Icon Size
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li svg', $css->properties( [
    'height' => 'betterdocs_sidebar_term_list_item_icon_size_layout6',
    'width'  => 'betterdocs_sidebar_term_list_item_icon_size_layout6'
] ) );

//Sidebar Bohemian Layout List Item Padding
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li', $css->properties( [
    'padding-top'    => 'betterdocs_sidebar_term_list_item_padding_top_layout6',
    'padding-right'  => 'betterdocs_sidebar_term_list_item_padding_right_layout6',
    'padding-bottom' => 'betterdocs_sidebar_term_list_item_padding_bottom_layout6',
    'padding-left'   => 'betterdocs_sidebar_term_list_item_padding_left_layout6'
], 'px' ) );

//Sidebar Bohemian Layout Active List Item Color
$css->add_rule( '.betterdocs-single-layout-6 .betterdocs-articles-list li a.active', $css->properties( [
    'color' => 'betterdocs_sidebar_term_list_active_item_color_layout6'
] ) );

//Sidebar Bohemian Layout Count Border Style | Count Font Weight
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'border-style' => 'betterdocs_sidebar_term_item_counter_border_type_layout6',
    'font-weight'  => 'betterdocs_sidebar_term_item_counter_font_weight_layout6'
] ) );

//Sidebar Bohemian Layout Count Border Width | Count Font Size
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'border-width' => 'betterdocs_sidebar_term_item_counter_border_width_layout6',
    'font-size'    => 'betterdocs_sidebar_term_item_counter_font_size_layout6'
], 'px' ) );

//Sidebar Bohemian Layout Count Font Line Height
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'line-height' => 'betterdocs_sidebar_term_item_counter_font_line_height_layout6'
], 'px' ) );

// Sidebar Bohemian Layout Count Background Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'background-color' => 'betterdocs_sidebar_term_item_counter_back_color_layout6'
] ) );

// Sidebar Bohemian Layout Count Font Color
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-layout-6 .betterdocs-category-items-counts span', $css->properties( [
    'color' => 'betterdocs_sidebar_term_item_counter_color_layout6'
] ) );

//Sidebar Bohemian Layout Count Border Radius | Padding | Margin
$css->add_rule( '.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_sidebar_term_item_counter_border_radius_top_left_layout6',
    'border-top-right-radius'    => 'betterdocs_sidebar_term_item_counter_border_radius_top_right_layout6',
    'border-bottom-right-radius' => 'betterdocs_sidebar_term_item_counter_border_radius_bottom_right_layout6',
    'border-bottom-left-radius'  => 'betterdocs_sidebar_term_item_counter_border_radius_bottom_left_layout6',
    'padding-top'                => 'betterdocs_sidebar_term_item_counter_padding_top_layout6',
    'padding-right'              => 'betterdocs_sidebar_term_item_counter_padding_right_layout6',
    'padding-bottom'             => 'betterdocs_sidebar_term_item_counter_padding_bottom_layout6',
    'padding-left'               => 'betterdocs_sidebar_term_item_counter_padding_left_layout6',
    'margin-top'                 => 'betterdocs_sidebar_term_item_counter_margin_top_layout6',
    'margin-right'               => 'betterdocs_sidebar_term_item_counter_margin_right_layout6',
    'margin-bottom'              => 'betterdocs_sidebar_term_item_counter_margin_bottom_layout6',
    'margin-left'                => 'betterdocs_sidebar_term_item_counter_margin_left_layout6'
], 'px' ) );

//FAQ Common Controls(MKB)
$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper .betterdocs-faq-section-title', $css->properties( [
    'font-size' => 'betterdocs_faq_title_font_size_mkb_layout_1'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper .betterdocs-faq-section-title', $css->properties( [
    'color' => 'betterdocs_faq_title_color_mkb_layout_1'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper .betterdocs-faq-section-title', $css->properties( [
    'margin' => 'betterdocs_faq_title_margin_mkb_layout_1'
], 'px' ) );

/**
 * FAQ Layout 1 Customizer CSS (MKB)
 */
$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag', $css->properties( [
    'color' => 'betterdocs_faq_category_title_color_mkb_layout_1'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag', $css->properties( [
    'font-size' => 'betterdocs_faq_category_name_font_size_mkb_layout_1'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag', $css->properties( [
    'padding' => 'betterdocs_faq_category_name_padding_mkb_layout_1'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name', $css->properties( [
    'color' => 'betterdocs_faq_list_color_mkb_layout_1'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name', $css->properties( [
    'font-size' => 'betterdocs_faq_list_font_size_mkb_layout_1'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post', $css->properties( [
    'background-color' => 'betterdocs_faq_list_background_color_mkb_layout_1'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post', $css->properties( [
    'padding' => 'betterdocs_faq_list_padding_mkb_layout_1'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content', $css->properties( [
    'background-color' => 'betterdocs_faq_list_content_background_color_mkb_layout_1',
    'color'            => 'betterdocs_faq_list_content_color_mkb_layout_1'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content', $css->properties( [
    'font-size' => 'betterdocs_faq_list_content_font_size_mkb_layout_1'
], 'px' ) );

/**
 * FAQ Layout 2 Customizer CSS
 */

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag', $css->properties( [
    'color' => 'betterdocs_faq_category_title_color_mkb_layout_2'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag', $css->properties( [
    'font-size' => 'betterdocs_faq_category_name_font_size_mkb_layout_2'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag', $css->properties( [
    'padding' => 'betterdocs_faq_category_name_padding_mkb_layout_2'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name', $css->properties( [
    'color' => 'betterdocs_faq_list_color_mkb_layout_2'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name', $css->properties( [
    'font-size' => 'betterdocs_faq_list_font_size_mkb_layout_2'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group', $css->properties( [
    'background-color' => 'betterdocs_faq_list_background_color_mkb_layout_2'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post', $css->properties( [
    'padding' => 'betterdocs_faq_list_padding_mkb_layout_2'
], 'px' ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content', $css->properties( [
    'background-color' => 'betterdocs_faq_list_content_background_color_mkb_layout_2',
    'color'            => 'betterdocs_faq_list_content_color_mkb_layout_2'
] ) );

$css->add_rule( '.betterdocs-mkb-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content', $css->properties( [
    'font-size' => 'betterdocs_faq_list_content_font_size_mkb_layout_2'
], 'px' ) );

//Advanced Search Live Start

//Category Select Settings Font Size
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform select', $css->properties( [
    'font-size' => 'betterdocs_category_select_font_size'
], 'px' ) );

//Category Select Settings Font Weight
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform select', $css->properties( [
    'font-weight' => 'betterdocs_category_select_font_weight'
] ) );

//Category Select Settings Font Text Transform
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform select', $css->properties( [
    'text-transform' => 'betterdocs_category_select_text_transform'
] ) );

//Category Select Settings Font Color
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform select', $css->properties( [
    'color' => 'betterdocs_category_select_text_color'
] ) );

//Search Button Settings Font Size
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'font-size' => 'betterdocs_new_search_button_font_size'
], 'px' ) );

//Search Button Settings Font Letter Spacing
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'letter-spacing' => 'betterdocs_new_search_button_letter_spacing'
], 'px' ) );

//Search Button Settings Font Weight
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'font-weight' => 'betterdocs_new_search_button_font_weight'
] ) );

//Search Button Settings Font Text Transform
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'text-transform' => 'betterdocs_new_search_button_text_transform'
] ) );

//Search Button Settings Text Color
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'color' => 'betterdocs_search_button_text_color'
] ) );

//Search Button Settings Background Color
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'background-color' => 'betterdocs_search_button_background_color'
] ) );

//Search Button Settings Background Hover Color
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit:hover', $css->properties( [
    'background-color' => 'betterdocs_search_button_background_color_hover'
] ) );

//Search Button Settings Border Radius
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_search_button_borderr_left_top',
    'border-top-right-radius'    => 'betterdocs_search_button_borderr_right_top',
    'border-bottom-right-radius' => 'betterdocs_search_button_borderr_right_bottom',
    'border-bottom-left-radius'  => 'betterdocs_search_button_borderr_left_bottom'
], 'px' ) );

//Search Button Settings Padding
$css->add_rule( '.betterdocs-live-search .betterdocs-searchform .search-submit', $css->properties( [
    'padding-top'    => 'betterdocs_search_button_padding_top',
    'padding-right'  => 'betterdocs_search_button_padding_right',
    'padding-bottom' => 'betterdocs_search_button_padding_bottom',
    'padding-left'   => 'betterdocs_search_button_padding_left'
], 'px' ) );

//Popular Search Setting Search Margin
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword', $css->properties( [
    'margin-top'    => 'betterdocs_popular_search_margin_top',
    'margin-right'  => 'betterdocs_popular_search_margin_right',
    'margin-bottom' => 'betterdocs_popular_search_margin_bottom',
    'margin-left'   => 'betterdocs_popular_search_margin_left'
], 'px' ) );

//Popular Search Setting Title Color
$css->add_rule( '.betterdocs-search-form-wrapper .betterdocs-popular-search-keyword .popular-search-title', $css->properties( [
    'color' => 'betterdocs_popular_search_title_text_color'
] ) );

//Popular Search Setting Title Font Size
$css->add_rule( '.betterdocs-search-form-wrapper .betterdocs-popular-search-keyword .popular-search-title', $css->properties( [
    'font-size' => 'betterdocs_popular_search_title_font_size'
], 'px' ) );

//Popular Search Setting Keyword Font Size
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'font-size' => 'betterdocs_popular_search_font_size'
], 'px' ) );

//Popular Search Setting Keyword Border Type
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'border-style' => 'betterdocs_popular_search_keyword_border'
] ) );

//Popular Search Setting Keyword Border Color
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'border-color' => 'betterdocs_popular_search_keyword_border_color'
] ) );

//Popular Search Setting Keyword Border Width
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'border-top-width'    => 'betterdocs_popular_search_keyword_border_width_top',
    'border-right-width'  => 'betterdocs_popular_search_keyword_border_width_right',
    'border-bottom-width' => 'betterdocs_popular_search_keyword_border_width_bottom',
    'border-left-width'   => 'betterdocs_popular_search_keyword_border_width_left'
], 'px' ) );

//Popular Search Setting Keyword Background Color
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'background-color' => 'betterdocs_popular_search_background_color'
] ) );

//Popular Search Setting Keyword Text Color
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'color' => 'betterdocs_popular_keyword_text_color'
] ) );

//Popular Search Setting Keyword Border Radius
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_popular_keyword_border_radius_left_top',
    'border-top-right-radius'    => 'betterdocs_popular_keyword_border_radius_right_top',
    'border-bottom-right-radius' => 'betterdocs_popular_keyword_border_radius_right_bottom',
    'border-bottom-left-radius'  => 'betterdocs_popular_keyword_border_radius_left_bottom'
], 'px' ) );

//Popular Search Setting Keyword Padding
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'padding-top'    => 'betterdocs_popular_search_padding_top',
    'padding-right'  => 'betterdocs_popular_search_padding_right',
    'padding-bottom' => 'betterdocs_popular_search_padding_bottom',
    'padding-left'   => 'betterdocs_popular_search_padding_left'
], 'px' ) );

//Popular Search Setting Keyword Margin
$css->add_rule( '.betterdocs-live-search .betterdocs-popular-search-keyword .popular-keyword', $css->properties( [
    'margin-top'    => 'betterdocs_popular_search_keyword_margin_top',
    'margin-right'  => 'betterdocs_popular_search_keyword_margin_right',
    'margin-bottom' => 'betterdocs_popular_search_keyword_margin_bottom',
    'margin-left'   => 'betterdocs_popular_search_keyword_margin_left'
], 'px' ) );

//Advanced Search Live End

//Category Archive Page Start (Pro)

//Content Area Background Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area', $css->properties( [
    'background-color' => 'betterdocs_archive_content_background_color'
] ) );

//Content Area Margin || Content Area Padding || Archive Content Border Radius
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area', $css->properties( [
    'margin-top'     => 'betterdocs_archive_content_margin_top',
    'margin-right'   => 'betterdocs_archive_content_margin_right',
    'margin-bottom'  => 'betterdocs_archive_content_margin_bottom',
    'margin-left'    => 'betterdocs_archive_content_margin_left',
    'padding-top'    => 'betterdocs_archive_content_padding_top',
    'padding-right'  => 'betterdocs_archive_content_padding_right',
    'padding-bottom' => 'betterdocs_archive_content_padding_bottom',
    'padding-left'   => 'betterdocs_archive_content_padding_left',
    'border-radius'  => 'betterdocs_archive_content_border_radius'
], 'px' ) );

//Inner Content Background Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info', $css->properties( [
    'background-color' => 'betterdocs_archive_inner_content_back_color_layout2'
] ) );

//Content Image Size
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-category-image', $css->properties( [
    'width' => 'betterdocs_archive_inner_content_image_size_layout2'
], '%' ) );

//Content Image Padding Top | Right | Bottom | Left || Content Image Margin Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-category-image img', $css->properties( [
    'padding-top'    => 'betterdocs_archive_inner_content_image_padding_top_layout2',
    'padding-right'  => 'betterdocs_archive_inner_content_image_padding_right_layout2',
    'padding-bottom' => 'betterdocs_archive_inner_content_image_padding_bottom_layout2',
    'padding-left'   => 'betterdocs_archive_inner_content_image_padding_left_layout2',
    'margin-top'     => 'betterdocs_archive_inner_content_image_margin_top_layout2',
    'margin-right'   => 'betterdocs_archive_inner_content_image_margin_right_layout2',
    'margin-bottom'  => 'betterdocs_archive_inner_content_image_margin_bottom_layout2',
    'margin-left'    => 'betterdocs_archive_inner_content_image_margin_left_layout2'
], 'px' ) );

//Title Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-entry-title .betterdocs-category-title-counts .betterdocs-entry-heading', $css->properties( [
    'color' => 'betterdocs_archive_title_color_layout2'
] ) );

//Archive Title Margin Top | Right | Bottom | Left || Title Font Size
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-entry-title .betterdocs-category-title-counts .betterdocs-entry-heading', $css->properties( [
    'margin-top'    => 'betterdocs_archive_title_margin_top_layout2',
    'margin-right'  => 'betterdocs_archive_title_margin_right_layout2',
    'margin-bottom' => 'betterdocs_archive_title_margin_bottom_layout2',
    'margin-left'   => 'betterdocs_archive_title_margin_left_layout2',
    'font-size'     => 'betterdocs_archive_title_font_size_layout2'
], 'px' ) );

//Description Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_archive_description_color_layout2'
] ) );

//Archive Description Margin || Description Font Size
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-description', $css->properties( [
    'margin-top'    => 'betterdocs_archive_description_margin_top_layout2',
    'margin-right'  => 'betterdocs_archive_description_margin_right_layout2',
    'margin-bottom' => 'betterdocs_archive_description_margin_bottom_layout2',
    'margin-left'   => 'betterdocs_archive_description_margin_left_layout2',
    'font-size'     => 'betterdocs_archive_description_font_size_layout2'
], 'px' ) );

//List Item Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a p', $css->properties( [
    'color' => 'betterdocs_archive_list_item_color_layout2'
] ) );

//List Item Hover Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a p:hover', $css->properties( [
    'color' => 'betterdocs_archive_list_item_color_hover_layout2'
] ) );

//List Background Color Hover || List Background Border Color Hover
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li:hover', $css->properties( [
    'background-color' => 'betterdocs_archive_list_back_color_hover_layout2',
    'border-color'     => 'betterdocs_archive_list_border_color_hover_layout2'
] ) );

//List Item Border Width Hover Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li:hover', $css->properties( [
    'border-top-width'    => 'betterdocs_archive_list_border_width_top_hover_layout2',
    'border-right-width'  => 'betterdocs_archive_list_border_width_right_hover_layout2',
    'border-bottom-width' => 'betterdocs_archive_list_border_width_bottom_hover_layout2',
    'border-left-width'   => 'betterdocs_archive_list_border_width_left_hover_layout2'
], 'px' ) );

//List Border Style
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li', $css->properties( [
    'border-style' => 'betterdocs_archive_list_border_style_layout2'
] ) );

//List Border Width Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li', $css->properties( [
    'border-top-width'    => 'betterdocs_archive_list_border_width_top_layout2',
    'border-right-width'  => 'betterdocs_archive_list_border_width_right_layout2',
    'border-bottom-width' => 'betterdocs_archive_list_border_width_bottom_layout2',
    'border-left-width'   => 'betterdocs_archive_list_border_width_left_layout2'
], 'px' ) );

//List Border Color Top
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li', $css->properties( [
    'border-top-color'    => 'betterdocs_archive_list_border_width_color_top_layout2',
    'border-right-color'  => 'betterdocs_archive_list_border_width_color_right_layout2',
    'border-bottom-color' => 'betterdocs_archive_list_border_width_color_bottom_layout2',
    'border-left-color'   => 'betterdocs_archive_list_border_width_color_left_layout2'
] ) );

//Docs List Margin Top | Right | Bottom | Left || List Item Font Size
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a p', $css->properties( [
    'margin-top'    => 'betterdocs_archive_article_list_margin_top_layout2',
    'margin-right'  => 'betterdocs_archive_article_list_margin_right_layout2',
    'margin-bottom' => 'betterdocs_archive_article_list_margin_bottom_layout2',
    'margin-left'   => 'betterdocs_archive_article_list_margin_left_layout2',
    'font-size'     => 'betterdocs_archive_list_item_font_size_layout2'
], 'px' ) );

//List Font Weight
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a p', $css->properties( [
    'font-weight' => 'betterdocs_archive_article_list_font_weight_layout2'
] ) );

//List Item Line Height
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a p', $css->properties( [
    'line-height' => 'betterdocs_archive_list_item_line_height_layout2'
], 'px' ) );

//List Item Arrow Height || Docs List Arrow Margin Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a .toggle-arrow', $css->properties( [
    'height'        => 'betterdocs_archive_list_item_arrow_height_layout2',
    'width'         => 'betterdocs_archive_list_item_arrow_width_layout2',
    'margin-top'    => 'betterdocs_archive_article_list_arrow_margin_top_layout2',
    'margin-right'  => 'betterdocs_archive_article_list_arrow_margin_right_layout2',
    'margin-bottom' => 'betterdocs_archive_article_list_arrow_margin_bottom_layout2',
    'margin-left'   => 'betterdocs_archive_article_list_arrow_margin_left_layout2'
], 'px' ) );

//List Item Arrow Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > a .toggle-arrow', $css->properties( [
    'fill' => 'betterdocs_archive_list_item_arrow_color_layout2'
] ) );

//Excerpt Font Weight
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > p', $css->properties( [
    'font-weight' => 'betterdocs_archive_article_list_excerpt_font_weight_layout2'
] ) );

//Excerpt Line Height
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > p', $css->properties( [
    'line-height' => 'betterdocs_archive_article_list_excerpt_font_line_height_layout2'
], 'px' ) );

//Excerpt Font Size
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > p', $css->properties( [
    'font-size' => 'betterdocs_archive_article_list_excerpt_font_size_layout2'
], 'px' ) );

//Excerpt Font Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > p', $css->properties( [
    'color' => 'betterdocs_archive_article_list_excerpt_font_color_layout2'
] ) );

//Excerpt Margin Top | Right | Bottom | Left || Excerpt Padding Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-taxonomy-doc-category .betterdocs-body ul li > p', $css->properties( [
    'margin-top'     => 'betterdocs_archive_article_list_excerpt_margin_top_layout2',
    'margin-right'   => 'betterdocs_archive_article_list_excerpt_margin_right_layout2',
    'margin-bottom'  => 'betterdocs_archive_article_list_excerpt_margin_bottom_layout2',
    'margin-left'    => 'betterdocs_archive_article_list_excerpt_margin_left_layout2',
    'padding-top'    => 'betterdocs_archive_article_list_excerpt_padding_top_layout2',
    'padding-right'  => 'betterdocs_archive_article_list_excerpt_padding_right_layout2',
    'padding-bottom' => 'betterdocs_archive_article_list_excerpt_padding_bottom_layout2',
    'padding-left'   => 'betterdocs_archive_article_list_excerpt_padding_left_layout2'
], 'px' ) );

//Count Font Weight || Count Font Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-entry-title .betterdocs-category-title-counts .betterdocs-category-items-counts', $css->properties( [
    'font-weight' => 'betterdocs_archive_article_list_counter_font_weight_layout2',
    'color'       => 'betterdocs_archive_article_list_counter_font_color_layout2'
] ) );

//Count Font Line Height || Count Font Size
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-entry-title .betterdocs-category-title-counts .betterdocs-category-items-counts', $css->properties( [
    'line-height' => 'betterdocs_archive_article_list_counter_font_line_height_layout2',
    'font-size'   => 'betterdocs_archive_article_list_counter_font_size_layout2'
], 'px' ) );

//Count Border Radius Top Left | Top Right | Bottom Right | Bottom Left || Count Margin Top | Right | Bottom | Left || Count Padding Top | Right | Bottom | Left
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-entry-title .betterdocs-category-title-counts .betterdocs-category-items-counts', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_archive_article_list_counter_border_radius_top_left_layout2',
    'border-top-right-radius'    => 'betterdocs_archive_article_list_counter_border_radius_top_right_layout2',
    'border-bottom-right-radius' => 'betterdocs_archive_article_list_counter_border_radius_bottom_right_layout2',
    'border-bottom-left-radius'  => 'betterdocs_archive_article_list_counter_border_radius_bottom_left_layout2',
    'margin-top'                 => 'betterdocs_archive_article_list_counter_margin_top_layout2',
    'margin-right'               => 'betterdocs_archive_article_list_counter_margin_right_layout2',
    'margin-bottom'              => 'betterdocs_archive_article_list_counter_margin_bottom_layout2',
    'margin-left'                => 'betterdocs_archive_article_list_counter_margin_left_layout2',
    'padding-top'                => 'betterdocs_archive_article_list_counter_padding_top_layout2',
    'padding-right'              => 'betterdocs_archive_article_list_counter_padding_right_layout2',
    'padding-bottom'             => 'betterdocs_archive_article_list_counter_padding_bottom_layout2',
    'padding-left'               => 'betterdocs_archive_article_list_counter_padding_left_layout2'
], 'px' ) );

//Count Border Color || Count Background Color
$css->add_rule( '.betterdocs-content-wrapper.doc-category-layout-6 .betterdocs-content-area .betterdocs-category-heading .betterdocs-category-info .betterdocs-entry-title .betterdocs-category-title-counts .betterdocs-category-items-counts', $css->properties( [
    'border-color'     => 'betterdocs_archive_article_list_counter_border_color_layout2',
    'background-color' => 'betterdocs_archive_article_list_counter_back_color_layout2'
] ) );

//Category Archive Page End (Pro)

//Other Categories Start (Pro)

// Archive Page Category Other Categories Title Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a', $css->properties( [
    'color' => 'betterdocs_archive_other_categories_title_color'
] ) );

// Archive Page Category Other Categories Hover Title Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a:hover', $css->properties( [
    'color' => 'betterdocs_archive_other_categories_title_hover_color'
] ) );

// Archive Page Category Other Categories Title Font Style(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a', $css->properties( [
    'font-weight' => 'betterdocs_archive_other_categories_title_font_weight'
] ) );

// Archive Page Category Other Categories Title Font Size(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a', $css->properties( [
    'font-size' => 'betterdocs_archive_other_categories_title_font_size'
], 'px' ) );

// Archive Page Category Other Categories Title Line Height(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a', $css->properties( [
    'line-height' => 'betterdocs_archive_other_categories_title_line_height'
], 'px' ) );

// Archive Page Category Other Categories Image Size(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-thumb-image', $css->properties( [
    'width' => 'betterdocs_archive_other_categories_image_size'
], '%' ) );

// Archive Page Category Other Categories Title Padding(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a', $css->properties( [
    'padding-top'    => 'betterdocs_archive_other_categories_title_padding_top',
    'padding-right'  => 'betterdocs_archive_other_categories_title_padding_right',
    'padding-bottom' => 'betterdocs_archive_other_categories_title_padding_bottom',
    'padding-left'   => 'betterdocs_archive_other_categories_title_padding_left'
], 'px' ) );

// Archive Page Category Other Categories Title Margin(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-title a', $css->properties( [
    'margin-top'    => 'betterdocs_archive_other_categories_title_margin_top',
    'margin-right'  => 'betterdocs_archive_other_categories_title_margin_right',
    'margin-bottom' => 'betterdocs_archive_other_categories_title_margin_bottom',
    'margin-left'   => 'betterdocs_archive_other_categories_title_margin_left'
], 'px' ) );

// Archive Page Category Other Categories Count(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts span', $css->properties( [
    'color' => 'betterdocs_archive_other_categories_count_color'
] ) );

// Archive Page Category Other Categories Count Background Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts', $css->properties( [
    'background-color' => 'betterdocs_archive_other_categories_count_back_color'
] ) );

// Archive Page Category Other Categories Count Background Hover Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts:hover', $css->properties( [
    'background-color' => 'betterdocs_archive_other_categories_count_back_color_hover'
] ) );

// Archive Page Category Other Categories Count Line Height(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts', $css->properties( [
    'line-height' => 'betterdocs_archive_other_categories_count_line_height'
], 'px' ) );

// Archive Page Category Other Categories Count Font Weight (Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts', $css->properties( [
    'font-weight' => 'betterdocs_archive_other_categories_count_font_weight'
] ) );

// Archive Page Category Other Categories Count Font Size(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts span', $css->properties( [
    'font-size' => 'betterdocs_archive_other_categories_count_font_size'
], 'px' ) );

// Archive Page Category Other Categories Count Border Radius(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_archive_other_categories_count_border_radius_topleft',
    'border-top-right-radius'    => 'betterdocs_archive_other_categories_count_border_radius_topright',
    'border-bottom-right-radius' => 'betterdocs_archive_other_categories_count_border_radius_bottomright',
    'border-bottom-left-radius'  => 'betterdocs_archive_other_categories_count_border_radius_bottomleft'
], 'px' ) );

// Archive Page Category Other Categories Count Padding(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts', $css->properties( [
    'padding-top'    => 'betterdocs_archive_other_categories_count_padding_top',
    'padding-right'  => 'betterdocs_archive_other_categories_count_padding_right',
    'padding-bottom' => 'betterdocs_archive_other_categories_count_padding_bottom',
    'padding-left'   => 'betterdocs_archive_other_categories_count_padding_left'
], 'px' ) );

// Archive Page Category Other Categories Count Margin(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-single-related-category-inner .betterdocs-category-header .betterdocs-category-items-counts', $css->properties( [
    'margin-top'    => 'betterdocs_archive_other_categories_count_margin_top',
    'margin-right'  => 'betterdocs_archive_other_categories_count_margin_right',
    'margin-bottom' => 'betterdocs_archive_other_categories_count_margin_bottom',
    'margin-left'   => 'betterdocs_archive_other_categories_count_margin_left'
], 'px' ) );

// Archive Page Category Other Categories Description Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-category-grid-list-inner-wrapper.layout-6 .betterdocs-single-related-category .betterdocs-category-description', $css->properties( [
    'color' => 'betterdocs_archive_other_categories_description_color'
] ) );

// Archive Page Category Other Categories Description Font Weight(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-category-grid-list-inner-wrapper.layout-6 .betterdocs-single-related-category .betterdocs-category-description', $css->properties( [
    'font-weight' => 'betterdocs_archive_other_categories_description_font_weight'
] ) );

// Archive Page Category Other Categories Description Font Size(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-category-grid-list-inner-wrapper.layout-6 .betterdocs-single-related-category .betterdocs-category-description', $css->properties( [
    'font-size' => 'betterdocs_archive_other_categories_description_font_size'
], 'px' ) );

// Archive Page Category Other Categories Description Line Height(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-category-grid-list-inner-wrapper.layout-6 .betterdocs-single-related-category .betterdocs-category-description', $css->properties( [
    'line-height' => 'betterdocs_archive_other_categories_description_line_height'
], 'px' ) );

// Archive Page Category Other Categories Description Padding(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-category-grid-list-inner-wrapper.layout-6 .betterdocs-single-related-category .betterdocs-category-description', $css->properties( [
    'padding-top'    => 'betterdocs_archive_other_categories_description_padding_top',
    'padding-right'  => 'betterdocs_archive_other_categories_description_padding_right',
    'padding-bottom' => 'betterdocs_archive_other_categories_description_padding_bottom',
    'padding-left'   => 'betterdocs_archive_other_categories_description_padding_left'
], 'px' ) );

// Archive Page Category Other Categories Button Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button', $css->properties( [
    'color' => 'betterdocs_archive_other_categories_button_color'
] ) );

// Archive Page Category Other Categories Button Background Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button', $css->properties( [
    'background-color' => 'betterdocs_archive_other_categories_button_back_color'
] ) );

// Archive Page Category Other Categories Button Background Hover Color(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button:hover', $css->properties( [
    'background-color' => 'betterdocs_archive_other_categories_button_back_color_hover'
] ) );

// Archive Page Category Other Categories Button Font Weight(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button', $css->properties( [
    'font-weight' => 'betterdocs_archive_other_categories_button_font_weight'
] ) );

// Archive Page Category Other Categories Button Font Size(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button', $css->properties( [
    'font-size' => 'betterdocs_archive_other_categories_button_font_size'
], 'px' ) );

// Archive Page Category Other Categories Button Font Line Height(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button', $css->properties( [
    'line-height' => 'betterdocs_archive_other_categories_button_font_line_height'
], 'px' ) );

// Archive Page Category Other Categories Button Border Radius(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper .betterdocs-load-more-button', $css->properties( [
    'border-top-left-radius'     => 'betterdocs_archive_other_categories_button_border_radius_top_left',
    'border-top-right-radius'    => 'betterdocs_archive_other_categories_button_border_radius_top_right',
    'border-bottom-right-radius' => 'betterdocs_archive_other_categories_button_border_radius_bottom_right',
    'border-bottom-left-radius'  => 'betterdocs_archive_other_categories_button_border_radius_bottom_left'
], 'px' ) );

// Archive Page Category Other Categories Button Padding(Archive Page Layout 2)
$css->add_rule( '.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-load-more-wrapper', $css->properties( [
    'padding-top'    => 'betterdocs_archive_other_categories_button_padding_top',
    'padding-right'  => 'betterdocs_archive_other_categories_button_padding_right',
    'padding-bottom' => 'betterdocs_archive_other_categories_button_padding_bottom',
    'padding-left'   => 'betterdocs_archive_other_categories_button_padding_left'
], 'px' ) );

//Other Categories End (Pro)

// Encyclopedia

$css->add_rule( '.betterdocs-encyclopedia-wrapper .encyclopedia-alphabets', $css->properties( [
    'padding' => 'alphabets_padding'
], 'px' ) );
$css->add_rule( '.betterdocs-encyclopedia-wrapper .encyclopedia-alphabets', $css->properties( [
    'margin' => 'alphabets_margin'
], 'px' ) );

$css->add_rule( 'body .betterdocs-encyclopedia-wrapper ', $css->properties( [
    'padding' => 'encyclopedia_padding'
], 'px' ) );

$css->add_rule( 'body .betterdocs-encyclopedia-wrapper ', $css->properties( [
    'margin' => 'encyclopedia_margin'
], 'px' ) );

$css->add_rule( 'body .betterdocs-encyclopedia-wrapper ', $css->properties( [
    'border-radius' => 'encyclopedia_border_radius'
], 'px' ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .encyclopedia-alphabets, .betterdocs-encyclopedia-wrapper .encyclopedia-alphabets.alphabets-style-box', $css->properties( [
    'background-color' => 'alphabets_background_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper ul.encyclopedia-alphabet-list a', $css->properties( [
    'color' => 'alphabets_link_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper ul.encyclopedia-alphabet-list a.letter-has-no-docs', $css->properties( [
    'color' => 'alphabets_link_disabled_color'
] ) );

$css->add_rule( 'body .betterdocs-encyclopedia-wrapper', $css->properties( [
    'background-color' => 'encyclopedia_background_color'
] ) );
$css->add_rule( 'body .encyclopedia-item', $css->properties( [
    'background-color' => 'encyclopedia_item_background_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper ul.encyclopedia-alphabet-list a', $css->properties( [
    'font-size' => 'alphabets_link_font_size'
], 'px' ) );

// Background-color properties
$css->add_rule( '.betterdocs-encyclopedia-wrapper', $css->properties( [
    'background-color' => 'encyclopedia_background_color'
] ) );

$css->add_rule( '.encyclopedia-item', $css->properties( [
    'background-color' => 'encyclopedia_item_background_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .encyclopedia-alphabets', $css->properties( [
    'background-color' => 'alphabets_background_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper.layout-3 .encyclopedia-alphabets', $css->properties( [
    'background-color' => 'alphabets_background_color_layout_3',
    'box-shadow' => 'none'
] ) );

$css->add_rule( 'ul.encyclopedia-alphabet-list a', $css->properties( [
    'background-color' => 'alphabets_link_bg_color'
] ) );
$css->add_rule( '.betterdocs-encyclopedia-wrapper .encyclopedia-alphabets li.alphabet-list-item.active, .betterdocs-encyclopedia-wrapper .encyclopedia-alphabets.alphabets-style-box li.alphabet-list-item.active', $css->properties( [
    'background' => 'alphabets_link_active_bg_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper.layout-3 .encyclopedia-alphabets li.alphabet-list-item.active', $css->properties( [
    'background' => 'alphabets_link_active_bg_color_layout_3'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .encyclopedia-alphabets li.alphabet-list-item.active a, .betterdocs-encyclopedia-wrapper .encyclopedia-alphabets.alphabets-style-box li.alphabet-list-item.active a', $css->properties( [
    'color' => 'alphabets_link_active_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper.layout-3 .encyclopedia-alphabets li.alphabet-list-item.active a', $css->properties( [
    'color' => 'alphabets_link_active_color_layout_3',
    'box-shadow' => '0px 1px 2px 0px rgba(0, 0, 0, 0.25)'
] ) );

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .layout-doc-list-2 .section-item .letter-start.alphabet-big-round-view span', $css->properties([
    'color' => 'start_letter_color_layout_3'
]));

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .layout-doc-list-2 .section-item .letter-start.alphabet-big-round-view span', $css->properties([
    'font-size' => 'start_letter_font_size_layout_3'
], 'px'));

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .layout-doc-list-2 .section-item .letter-start.alphabet-big-round-view', $css->properties([
    'background-color' => 'start_letter_bg_color_layout_3'
]));

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .betterdocs-encyclopedia.layout-doc-list-2 .section-item .encyclopedia-list a', $css->properties([
    'color' => 'item_title_color_layout_3'
]));

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .betterdocs-encyclopedia.layout-doc-list-2 .section-item .encyclopedia-list a', $css->properties([
    'font-size' => 'item_title_font_size_layout_3'
], 'px'));

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .encyclopedia-item.explore-more-docs a span', $css->properties([
    'font-size' => 'explore_more_font_size_layout_3'
], 'px' ) );

$css->add_rule('.betterdocs-encyclopedia-wrapper.layout-3 .encyclopedia-alphabets', $css->properties([
    'border-bottom' => '2px solid %navigation_wrapper_border_bottom_color%',
    'border-radius' => 'unset'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .letter-start, .betterdocs-encyclopedia-wrapper .letter-start.alphabet-big-round-view', $css->properties( [
    'background-color' => 'start_letter_bg_color'
] ) );
$css->add_rule( '.betterdocs-encyclopedia-wrapper .letter-start.alphabet-big-round-view span', $css->properties( [
    'background-color' => 'start_letter_inner_bg_color'
] ) );

$css->add_rule( '.loadmoreBtn-container .encyclopedia-loadmore-btn', $css->properties( [
    'background-color' => 'loadmore_button_bg_color'
] ) );
$css->add_rule( '.glossary-tooltip-overlay', $css->properties( [
    'background-color' => 'glossaries_tooltip_background_color'
] ) );

$css->add_rule( '.glossary-tooltip-overlay::before', $css->properties( [
    'border-top-color' => 'glossaries_tooltip_background_color'
] ) );

$css->add_rule( '.glossary-tooltip-overlay', $css->properties( [
    'color' => 'glossaries_tooltip_text_color'
] ) );

$css->add_rule( '.glossary-tooltip-overlay a', $css->properties( [
    'color' => 'glossaries_tooltip_text_link_color'
] ) );

$css->add_rule('.glossary-tooltip-container a', $css->properties([
    'color' => 'glossaries_color'
]));
$css->add_rule('.glossary-tooltip-container a:hover', $css->properties([
    'color' => 'glossaries_hover_color'
] ) );
$css->add_rule( '.glossary-tooltip-overlay', $css->properties( [
    'font-size' => 'glossaries_tooltip_font_size'
], 'px' ) );

// Color properties
$css->add_rule( 'ul.encyclopedia-alphabet-list a', $css->properties( [
    'color' => 'alphabets_link_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .letter-start span, .betterdocs-encyclopedia-wrapper .letter-start.alphabet-big-round-view span', $css->properties( [
    'color' => 'start_letter_color'
] ) );

$css->add_rule( 'ul.encyclopedia-alphabet-list a.letter-has-no-docs', $css->properties( [
    'color' => 'alphabets_link_disabled_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .tools-card__title-text.heading-small, .betterdocs-encyclopedia-wrapper .layout-doc-list .heading-small.tools-card__title-text', $css->properties( [
    'color' => 'item_title_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .tools-card__sample-text.text-size-small', $css->properties( [
    'color' => 'item_excerpt_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .layout-doc-list .top-tools-card', $css->properties( [
    'background-color' => 'item_excerpt_bg_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .layout-doc-list .top-tools-card::before', $css->properties( [
    'border-top-color' => 'item_excerpt_bg_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .layout-doc-list .top-tools-card', $css->properties( [
    'border-top-color' => 'item_excerpt_border_color'
] ) );

$css->add_rule( '.betterdocs-encyclopedia .tools-card_link-container .text-style-link', $css->properties( [
    'color' => 'item_link_color'
] ) );
$css->add_rule( '.betterdocs-encyclopedia .encyclopedia-item.explore-more-docs a span, .betterdocs-encyclopedia-wrapper.layout-3 .betterdocs-encyclopedia .encyclopedia-item.explore-more-docs a span', $css->properties( [
    'color' => 'explore_more_text_color'
] ) );
$css->add_rule( '.loadmoreBtn-container .encyclopedia-loadmore-btn', $css->properties( [
    'color' => 'loadmore_button_text_color'
] ) );

// Font-size properties
$css->add_rule( 'ul.encyclopedia-alphabet-list a', $css->properties( [
    'font-size' => 'alphabets_link_font_size'
], 'px' ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .letter-start span, .betterdocs-encyclopedia-wrapper .letter-start.alphabet-big-round-view span', $css->properties( [
    'font-size' => 'start_letter_font_size'
], 'px' ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .tools-card__title-text.heading-small', $css->properties( [
    'font-size' => 'item_title_font_size'
], 'px' ) );

$css->add_rule( '.betterdocs-encyclopedia-wrapper .tools-card__sample-text.text-size-small', $css->properties( [
    'font-size' => 'item_excerpt_font_size'
], 'px' ) );

$css->add_rule( '.betterdocs-encyclopedia .tools-card_link-container .text-style-link', $css->properties( [
    'font-size' => 'item_link_font_size'
], 'px' ) );

$css->add_rule( '.encyclopedia-item.explore-more-docs a span', $css->properties( [
    'font-size' => 'explore_more_font_size'
], 'px' ) );

$css->add_rule( '.loadmoreBtn-container .encyclopedia-loadmore-btn', $css->properties( [
    'font-size' => 'loadmore_button_text_font_size'
], 'px' ) );

//modal search popular controls start
$css->add_rule( '#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-popular-keywords .keywords span', $css->properties( [
    'color' => 'betterdocs_modal_popular_keyword_text_color'
] ) );

$css->add_rule( '#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-popular-keywords .keywords span', $css->properties( [
    'background-color' => 'betterdocs_modal_popular_keyword_background_color'
] ) );

$css->add_rule( '#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-popular-keywords .keywords span', $css->properties( [
    'font-size' => 'betterdocs_modal_popular_keyword_font_size'
], 'px' ) );

$css->add_rule( '#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-popular-keywords .keywords span', $css->properties( [
    'margin' => 'search_modal_content_tabs_docs_list_margin'
], 'px' ) );

//modal search popular controls end

//betterdocs single glossaries start
$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-breadcrumb-item.current span', $css->properties( [
    'font-size' => 'betterdocs_single_glossaries_breadcrumbs_font_size'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-breadcrumb .betterdocs-breadcrumb-item a', $css->properties( [
    'color' => 'betterdocs_single_glossaries_breadcrumb_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover', $css->properties( [
    'color' => 'betterdocs_single_glossaries_breadcrumb_hover_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-breadcrumb .breadcrumb-delimiter', $css->properties( [
    'fill' => 'betterdocs_single_glossaries_breadcrumb_speretor_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-breadcrumb-item.current span', $css->properties( [
    'color' => 'betterdocs_single_glossaries_breadcrumb_active_item_color'
] ) );


$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .docs-single-title .betterdocs-entry-title', $css->properties( [
    'text-transform' => 'betterdocs_single_glossaries_post_title_text_transform'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .docs-single-title .betterdocs-entry-title', $css->properties( [
    'font-size' => 'betterdocs_single_glossaries_title_font_size'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .docs-single-title .betterdocs-entry-title', $css->properties( [
    'color' => 'betterdocs_single_glossaries_title_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .docs-single-title .betterdocs-entry-title', $css->properties( [
    'margin-top' => 'betterdocs_single_glossaries_title_margin_top_layout',
    'margin-right' => 'betterdocs_single_glossaries_title_margin_right_layout',
    'margin-bottom' => 'betterdocs_single_glossaries_title_margin_bottom_layout',
    'margin-left' => 'betterdocs_single_glossaries_title_margin_left_layout',
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time', $css->properties( [
    'background-color' => 'betterdocs_single_glossaries_estimate_reading_time_bg_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time p svg path', $css->properties( [
    'fill' => 'betterdocs_single_glossaries_estimate_reading_time_icon_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time p svg', $css->properties( [
    'font-size' => 'betterdocs_single_glossaries_content_est_reading_icon_font_size'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time p', $css->properties( [
    'color' => 'betterdocs_single_glossaries_estimate_reading_time_color'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time p', $css->properties( [
    'font-size' => 'betterdocs_single_glossaries_content_est_reading_font_size'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time', $css->properties( [
    'border-radius' => 'betterdocs_single_glossaries_content_est_reading_border_radius'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time p', $css->properties( [
    'font-weight' => 'betterdocs_single_glossaries_content_est_reading_font_weight'
] ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-content-area .betterdocs.reading-time', $css->properties( [
    'margin-top' => 'betterdocs_single_glossaries_content_est_reading_margin_top',
    'margin-right' => 'betterdocs_single_glossaries_content_est_reading_margin_right',
    'margin-bottom' => 'betterdocs_single_glossaries_content_est_reading_margin_bottom',
    'margin-left' => 'betterdocs_single_glossaries_content_est_reading_margin_left',
    'padding-top' => 'betterdocs_single_glossaries_content_est_reading_padding_top',
    'padding-right' => 'betterdocs_single_glossaries_content_est_reading_padding_right',
    'padding-bottom' => 'betterdocs_single_glossaries_content_est_reading_padding_bottom',
    'padding-left' => 'betterdocs_single_glossaries_content_est_reading_padding_left'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-entry-content .term-description p', $css->properties( [
    'font-size' => 'betterdocs_single_glossaries_content_font_size'
], 'px' ) );

$css->add_rule( '.encyclopedia-single-layout.betterdocs-encyclopedia-wrapper.betterdocs-single-wrapper .betterdocs-entry-content .term-description p', $css->properties( [
    'color' => 'betterdocs_single_glossaries_content_font_color'
] ) );

//betterdocs single glossaries end
