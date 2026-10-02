<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

/**
 * This is what output-css.php is in older version. ( 2.x )
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPDeveloper\BetterDocs\Utils\CSSGenerator;

$css = new CSSGenerator( $mods );

//Global Docs Wrapper Controls Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_background_color'
		]
	)
);

//Global Docs Wrapper Controls Background Image/Properties
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper',
	$css->properties(
		[
			'background-image' => [
				'id'         => 'betterdocs_doc_page_background_image',
				'properties' => [
					'background-size'       => 'betterdocs_doc_page_background_size',
					'background-repeat'     => 'betterdocs_doc_page_background_repeat',
					'background-attachment' => 'betterdocs_doc_page_background_attachment',
					'background-position'   => 'betterdocs_doc_page_background_position'
				]
			]
		]
	)
);

//Global Docs Wrapper Controls Padding
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_page_content_padding_top',
			'padding-right'  => 'betterdocs_doc_page_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_page_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_page_content_padding_left'
		],
		'px'
	)
);

//Global Docs Wrapper Controls Width
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'width' => 'betterdocs_doc_page_content_width'
		],
		'%'
	)
);

//Global Docs Wrapper Controls Max Width
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'max-width' => 'betterdocs_doc_page_content_max_width'
		],
		'px'
	)
);

/* CATEGORY COLUMN SETTINGS */
//Category Title Padding Bottom for only layout 1
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_page_cat_title_padding_bottom'
		],
		'px'
	)
);

//Space Between Columns Grid Layout Margin Bottom
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper.masonry > .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_page_column_space'
		],
		'px'
	)
);

//Space Between Columns Grid Layout
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper',
	$css->properties(
		[
			'--gap' => 'betterdocs_doc_page_column_space'
		]
	)
);

//Space Between Columns Box Layout
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.layout-flex',
	$css->properties(
		[
			'--gap' => 'betterdocs_doc_page_column_space'
		]
	)
);

//Doc Category Background Color for Grid Categories
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_column_bg_color'
		]
	)
);

//Doc Category Background Color for Grid Categories
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper > .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_column_bg_color'
		]
	)
);

//Doc Category Background Color for Box Categories
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper > .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_column_bg_color2'
		]
	)
);

//Doc Category Hover Background Color for Box Categories
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper > .betterdocs-single-category-wrapper:hover',
	$css->properties(
		[
			'background-color' => '%betterdocs_doc_page_column_hover_bg_color%!important'
		]
	)
);

//Doc Page Grid Category Padding
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'   => 'betterdocs_doc_page_column_padding_top',
			'padding-right' => 'betterdocs_doc_page_column_padding_right',
			'padding-left'  => 'betterdocs_doc_page_column_padding_left'
		],
		'px'
	)
);

//Doc Page Category Grid Layout 4 Column Padding ### this layout has padding bottom for 3 boxes only, Please note the grid will not have padding bottom for this layout only
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-grid-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper.category-box',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_page_column_padding_top',
			'padding-right'  => 'betterdocs_doc_page_column_padding_right',
			'padding-left'   => 'betterdocs_doc_page_column_padding_left',
			'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom'
		],
		'px'
	)
);

//Doc Page Grid Category Padding
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-body',
	$css->properties(
		[
			'padding-top'   => 'betterdocs_doc_page_column_padding_top',
			'padding-right' => 'betterdocs_doc_page_column_padding_right',
			'padding-left'  => 'betterdocs_doc_page_column_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-body:last-of-type',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-footer',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom',
			'padding-right'  => 'betterdocs_doc_page_column_padding_right',
			'padding-left'   => 'betterdocs_doc_page_column_padding_left'
		],
		'px'
	)
);

//Doc Page Box Category Padding
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_page_column_padding_top',
			'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom',
			'padding-right'  => 'betterdocs_doc_page_column_padding_right',
			'padding-left'   => 'betterdocs_doc_page_column_padding_left'
		],
		'px'
	)
);

//Grid Category Icon without Layout 4
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-4) .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header .betterdocs-category-icon .betterdocs-category-icon-img',
	$css->properties(
		[
			'height' => 'betterdocs_doc_page_cat_icon_size_layout1'
		],
		'px'
	)
);

//Box Category Icon without Layout 4
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-2 .betterdocs-category-box-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header .betterdocs-category-icon .betterdocs-category-icon-img',
	$css->properties(
		[
			'height' => 'betterdocs_doc_page_cat_icon_size_layout2'
		],
		'px'
	)
);

// Doc Layout Border Radius For Grid Templates
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-6) .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner',
	$css->properties(
		[
			'border-top-left-radius'     => 'betterdocs_doc_page_column_borderr_topleft',
			'border-top-right-radius'    => 'betterdocs_doc_page_column_borderr_topright',
			'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
		],
		'px'
	)
);

// Doc Layout Border Radius For Box Templates
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-6) .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper > .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'border-top-left-radius'     => 'betterdocs_doc_page_column_borderr_topleft',
			'border-top-right-radius'    => 'betterdocs_doc_page_column_borderr_topright',
			'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
		],
		'px'
	)
);

// Doc Layout Border Radius For Grid Template Body and Footer
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-6) .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body:last-child,.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-6) .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-footer:last-child',
	$css->properties(
		[
			'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
		],
		'px'
	)
);

//Doc Category Title Font Size for Layout 1, 2, 3, 5
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-4):not(.betterdocs-category-layout-6) .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_cat_title_font_size'
		],
		'px'
	)
);

//Doc Category Title Font Size for Layout 1, 2, 3, 5
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-4):not(.betterdocs-category-layout-6) .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_cat_title_font_size'
		],
		'px'
	)
);

//Doc Box Category Title Font Size for Layout 4
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-single-category-inner .betterdocs-category-title a, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper > .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title:has(a)',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_cat_title_font_size'
		],
		'px'
	)
);

//DocCategory Title Color For Layout 1
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_color'
		],
		''
	)
);
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_color'
		],
		''
	)
);

// DocCategory Title Border Color For Layout 1
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-inner-wrapper .betterdocs-category-header .betterdocs-category-header-inner',
	$css->properties(
		[
			'border-bottom' => '2px solid %betterdocs_doc_page_cat_title_border_color%'
		],
		''
	)
);

//DocCategory Title Color For Layout 2, 3, 4, 5, 6 || Layout 6 Specfic selector included because defaults are not working on layout 6
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-1) .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title, .betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-1) .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title:has(a), .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-single-category-inner .betterdocs-category-title a, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-category-grid-list-wrapper .betterdocs-category-grid-list-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title-counts .betterdocs-category-title a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_color2'
		],
		''
	)
);

//DocCategory Title Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_hover_color'
		],
		''
	)
);
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-title a:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-6 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_hover_color'
		],
		''
	)
);

//Doc Box Category Description Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-description',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_desc_color'
		]
	)
);

//Doc Box Category Hover Border Width for Layout 2
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.border-bottom .betterdocs-single-category-wrapper:hover',
	$css->properties(
		[
			'border-bottom' => '%betterdocs_doc_page_box_border_bottom_size%px solid transparent'
		]
	)
);

//Doc Box Category Hover Border Color for Layout 2
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-2 .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper.border-bottom .betterdocs-single-category-wrapper:hover',
	$css->properties(
		[
			'border-bottom-color' => 'betterdocs_doc_page_box_border_bottom_color'
		]
	)
);

//Doc Layout 1 Category Content Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-body,.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-footer',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_article_list_bg_color'
		]
	)
);

// Item Count Font Size (Doc Page Layout 1, 2, 3, 4, 5)
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-6) .betterdocs-single-category-wrapper .betterdocs-category-items-counts span',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_item_count_font_size'
		],
		'px'
	)
);

//Doc Layout 1 Item Count Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > *:not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts span',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_item_count_color'
		]
	)
);

//Doc Layout 2,3,4,5 Item Count Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper:not(.betterdocs-category-layout-1):not(.betterdocs-category-layout-6) .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-category-items-counts span',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_item_count_color_layout2'
		],
		''
	)
);

//Doc Layout 1 Item Count Background
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > *:not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_item_count_bg_color'
		],
		''
	)
);

//Doc Layout 1 Item Count Inner Circle Background, Border Type, Border Color, Border Width
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > *:not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts span',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_item_count_inner_bg_color',
			'border-style'     => 'betterdocs_doc_page_item_count_border_type',
			'border-color'     => 'betterdocs_doc_page_item_count_border_color'
		],
		''
	)
);

//Doc Layout 1 Item Count Inner Circle Border Width
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > *:not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts span',
	$css->properties(
		[
			'border-top-width'    => 'betterdocs_doc_page_item_count_inner_border_width_top',
			'border-right-width'  => 'betterdocs_doc_page_item_count_inner_border_width_right',
			'border-bottom-width' => 'betterdocs_doc_page_item_count_inner_border_width_bottom',
			'border-left-width'   => 'betterdocs_doc_page_item_count_inner_border_width_left'
		],
		'px'
	)
);

//Doc Layout 1 Item Count Inner Circle Width, Height
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper > *:not(.betterdocs-grid-top-row-wrapper) .betterdocs-category-items-counts span',
	$css->properties(
		[
			'width'  => 'betterdocs_doc_page_item_counter_size',
			'height' => 'betterdocs_doc_page_item_counter_size'
		],
		'px'
	)
);

//Doc Layout 2 Image Space
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-2 .betterdocs-category-icon',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_page_column_content_space_image'
		],
		'px'
	)
);

//Doc Layout 3, 5 Content Space Between Image
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-3 .betterdocs-category-icon, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-5 .betterdocs-category-icon',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_page_column_content_space_image'
		],
		'px'
	)
);

//Doc Layout 4 Content Space Between Image
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4:not(.betterdocs-category-layout-2):not(.betterdocs-mkb-layout-1) .betterdocs-category-box-inner-wrapper .betterdocs-category-icon',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_page_column_content_space_image'
		],
		'px'
	)
);

//Doc Layout 2, 3, 4, 5 Item Title Space
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-category-header .betterdocs-category-title',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_page_column_content_space_title'
		],
		'px'
	)
);

//Doc Layout 2, 3, 4, 5 Item Description Space
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-category-header .betterdocs-category-description',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_page_column_content_space_desc'
		],
		'px'
	)
);

//Doc Layout 2, 3, 4, 5 Item Counter Space
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper .betterdocs-category-box-wrapper .betterdocs-category-box-inner-wrapper .betterdocs-category-header .betterdocs-category-items-counts',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_page_column_content_space_counter'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list,.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list .betterdocs-nested-category-list',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_article_list_button_bg_color'
		],
		''
	)
);

//Doc Layout 1 Doc List Padding Top/Right/Bottom/Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list,.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li.betterdocs-nested-category-wrapper ul',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_page_article_list_padding_top_2',
			'padding-bottom' => 'betterdocs_doc_page_article_list_padding_bottom_2',
			'padding-right'  => 'betterdocs_doc_page_article_list_padding_right_2',
			'padding-left'   => 'betterdocs_doc_page_article_list_padding_left_2'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_color'
		]
	)
);

//Doc Layout 4 Docs List Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_color'
		]
	)
);

//Doc Layout 1 Doc List Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li a:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li a.active,',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_hover_color'
		]
	)
);

//Doc Layout 4 Docs List Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li a:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_hover_color'
		]
	)
);

//Doc Layout 1 Doc List Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_article_list_font_size'
		],
		'px'
	)
);

//Doc Layout 4 Docs List Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_article_list_font_size'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_doc_page_list_icon_color'
		]
	)
);

//Doc Layout 1 Doc List Icon Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_list_icon_font_size',
			'min-width' => 'betterdocs_doc_page_list_icon_font_size'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Item Margin
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li:not(.betterdocs-nested-category-wrapper)',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_page_article_list_margin_top',
			'margin-right'  => 'betterdocs_doc_page_article_list_margin_right',
			'margin-bottom' => 'betterdocs_doc_page_article_list_margin_bottom',
			'margin-left'   => 'betterdocs_doc_page_article_list_margin_left'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Item Margin
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li.betterdocs-nested-category-wrapper',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_page_article_list_margin_top'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Item Margin
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li.betterdocs-nested-category-wrapper .betterdocs-nested-category-title',
	$css->properties(
		[
			'margin-left'  => 'betterdocs_doc_page_article_list_margin_top',
			'margin-right' => 'betterdocs_doc_page_article_list_margin_top'
		],
		'px'
	)
);

//Doc Layout 4 Docs List Item Margin
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_page_article_list_margin_top',
			'margin-right'  => 'betterdocs_doc_page_article_list_margin_right',
			'margin-bottom' => 'betterdocs_doc_page_article_list_margin_bottom',
			'margin-left'   => 'betterdocs_doc_page_article_list_margin_left'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Item Padding
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li:not(.betterdocs-nested-category-wrapper)',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_page_article_list_padding_top',
			'padding-right'  => 'betterdocs_doc_page_article_list_padding_right',
			'padding-bottom' => 'betterdocs_doc_page_article_list_padding_bottom',
			'padding-left'   => 'betterdocs_doc_page_article_list_padding_left'
		],
		'px'
	)
);

//Doc Layout 1 Docs Subcategory Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title a,.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list .betterdocs-nested-category-title a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_subcategory_color'
		]
	)
);

//Doc Layout 4 Docs Subcategory Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_subcategory_color'
		]
	)
);

//Doc Layout 1 Docs Subcategory Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_subcategory_hover_color'
		]
	)
);

//Doc Layout 4 Docs Subcategory Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_subcategory_hover_color'
		]
	)
);

//Doc Layout 1 Docs Subcategory Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_article_subcategory_font_size'
		],
		'px'
	)
);

//Doc Layout 4 Docs Subcategory Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_article_subcategory_font_size'
		],
		'px'
	)
);

//Doc Layout 1 Subcategory Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'fill' => 'betterdocs_doc_page_subcategory_icon_color'
		]
	)
);

//Doc Layout 4 Subcategory Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title svg,.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'fill' => 'betterdocs_doc_page_subcategory_icon_color'
		]
	)
);

//Doc Layout 1 Subcategory Icon Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_subcategory_icon_font_size'
		],
		'px'
	)
);

//Doc Layout 4 Subcategory Icon Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_subcategory_icon_font_size'
		],
		'px'
	)
);

//Doc Layout 1 Subcategory Docs List Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_subcategory_article_list_color'
		]
	)
);

//Doc Layout 4 Subcategory Docs List Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_subcategory_article_list_color'
		]
	)
);

//Doc Layout 1 Subcategory List Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_subcategory_article_list_hover_color'
		]
	)
);

//Doc Layout 4 Subcategory List Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_subcategory_article_list_hover_color'
		]
	)
);

//Doc Layout 1 Subcategory List Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_doc_page_subcategory_article_list_icon_color'
		]
	)
);

//Doc Layout 4 Subcategory List Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-body .betterdocs-articles-list li .betterdocs-nested-category-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_doc_page_subcategory_article_list_icon_color'
		]
	)
);

//Doc Layout 1 Explore Button BackColor/Color/Border-Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-footer a',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_explore_btn_bg_color',
			'color'            => 'betterdocs_doc_page_explore_btn_color',
			'border-color'     => 'betterdocs_doc_page_explore_btn_border_color'
		],
		''
	)
);

//Doc Layout 4 Explore Button BackColor/Color/Border-Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer a',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_explore_btn_bg_color',
			'color'            => 'betterdocs_doc_page_explore_btn_color',
			'border-color'     => 'betterdocs_doc_page_explore_btn_border_color'
		]
	)
);

//Doc Layout 1 Explore Button Hover BackColor/Color/Border-Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-footer button:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-footer a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_explore_btn_hover_bg_color',
			'color'            => 'betterdocs_doc_page_explore_btn_hover_color',
			'border-color'     => 'betterdocs_doc_page_explore_btn_hover_border_color'
		],
		''
	)
);

//Doc Layout 4 Explore Button Hover BackColor/Color/Border-Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer button:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_explore_btn_hover_bg_color',
			'color'            => 'betterdocs_doc_page_explore_btn_hover_color',
			'border-color'     => 'betterdocs_doc_page_explore_btn_hover_border_color'
		]
	)
);

//Doc Layout 1 Explore Button Font Size || Border Width || Padding || Margin || Border Radius
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-footer a',
	$css->properties(
		[
			'font-size'                  => 'betterdocs_doc_page_explore_btn_font_size',
			'border-width'               => 'betterdocs_doc_page_explore_btn_border_width',
			'padding-top'                => 'betterdocs_doc_page_explore_btn_padding_top',
			'padding-right'              => 'betterdocs_doc_page_explore_btn_padding_right',
			'padding-bottom'             => 'betterdocs_doc_page_explore_btn_padding_bottom',
			'padding-left'               => 'betterdocs_doc_page_explore_btn_padding_left',
			'margin-top'                 => 'betterdocs_doc_page_explore_btn_margin_top',
			'margin-right'               => 'betterdocs_doc_page_explore_btn_margin_right',
			'margin-bottom'              => 'betterdocs_doc_page_explore_btn_margin_bottom',
			'margin-left'                => 'betterdocs_doc_page_explore_btn_margin_left',
			'border-top-left-radius'     => 'betterdocs_doc_page_explore_btn_borderr_topleft',
			'border-top-right-radius'    => 'betterdocs_doc_page_explore_btn_borderr_topright',
			'border-bottom-right-radius' => 'betterdocs_doc_page_explore_btn_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_doc_page_explore_btn_borderr_bottomleft'
		],
		'px'
	)
);

//Doc Layout 4 Explore Button Font Size || Border Width || Padding || Margin || Border Radius
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer button, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-footer a',
	$css->properties(
		[
			'font-size'                  => 'betterdocs_doc_page_explore_btn_font_size',
			'border-width'               => 'betterdocs_doc_page_explore_btn_border_width',
			'padding-top'                => 'betterdocs_doc_page_explore_btn_padding_top',
			'padding-right'              => 'betterdocs_doc_page_explore_btn_padding_right',
			'padding-bottom'             => 'betterdocs_doc_page_explore_btn_padding_bottom',
			'padding-left'               => 'betterdocs_doc_page_explore_btn_padding_left',
			'margin-top'                 => 'betterdocs_doc_page_explore_btn_margin_top',
			'margin-right'               => 'betterdocs_doc_page_explore_btn_margin_right',
			'margin-bottom'              => 'betterdocs_doc_page_explore_btn_margin_bottom',
			'margin-left'                => 'betterdocs_doc_page_explore_btn_margin_left',
			'border-top-left-radius'     => 'betterdocs_doc_page_explore_btn_borderr_topleft',
			'border-top-right-radius'    => 'betterdocs_doc_page_explore_btn_borderr_topright',
			'border-bottom-right-radius' => 'betterdocs_doc_page_explore_btn_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_doc_page_explore_btn_borderr_bottomleft'
		],
		'px'
	)
);

//Doc Layout 1 Doc List Color Hover
$css->add_rule(
	'.betterdocs-docs-archive-wrapper.betterdocs-category-layout-1 .betterdocs-content-wrapper .betterdocs-body .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_hover_color'
		]
	)
);

//Doc Layout 7 Controls Start
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box',
	$css->properties(
		[
			'background-color' => 'column_background_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_page_column_hover_bg_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_page_column_padding_top_layout_7',
			'padding-right'  => 'betterdocs_doc_page_column_padding_right_layout_7',
			'padding-bottom' => 'betterdocs_doc_page_column_padding_bottom_layout_7',
			'padding-left'   => 'betterdocs_doc_page_column_padding_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box',
	$css->properties(
		[
			'border-color' => 'column_border_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-folder-icon',
	$css->properties(
		[
			'height' => 'betterdocs_doc_page_cat_icon_size_layout_7',
			'width'  => 'betterdocs_doc_page_cat_icon_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_cat_title_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title,.betterdocs-category-grid-three-wrapper .betterdocs-category-title,.betterdocs-category-grid-three-wrapper .betterdocs-category-title a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title:hover,.betterdocs-category-grid-three-wrapper .betterdocs-category-title:hover,.betterdocs-category-grid-three-wrapper .betterdocs-category-title a:hover',
	$css->properties(
		[
			'color' => 'category_title_color_hover_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_item_count_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_item_count_hover_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_page_item_count_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update',
	$css->properties(
		[
			'font-size' => 'last_updated_time_layout_7_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update',
	$css->properties(
		[
			'color' => 'last_updated_time_layout_7_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update:hover',
	$css->properties(
		[
			'color' => 'last_updated_time_layout_7_hover_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update',
	$css->properties(
		[
			'background-color' => 'last_updated_time_layout_7_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update:hover, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update:hover',
	$css->properties(
		[
			'background-color' => 'last_updated_time_layout_7_background_hover_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform .betterdocs-searchform-input-wrap .betterdocs-search-command',
	$css->properties(
		[
			'color' => 'sidebar_search_field_placeholder_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform',
	$css->properties(
		[
			'background-color' => 'sidebar_search_field_background_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform .betterdocs-searchform-input-wrap svg',
	$css->properties(
		[
			'height' => 'sidebar_search_field_icon_size_layout_7',
			'width'  => 'sidebar_search_field_icon_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title',
	$css->properties(
		[
			'margin-top'    => 'category_title_margin_top_layout_7',
			'margin-right'  => 'category_title_margin_right_layout_7',
			'margin-bottom' => 'category_title_margin_bottom_layout_7',
			'margin-left'   => 'category_title_margin_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts, .betterdocs-wrapper.betterdocs-docs-archive-wrapper.betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'margin-top'    => 'doc_page_item_count_margin_top_layout_7',
			'margin-right'  => 'doc_page_item_count_margin_right_layout_7',
			'margin-bottom' => 'doc_page_item_count_margin_bottom_layout_7',
			'margin-left'   => 'doc_page_item_count_margin_left_layout_7'
		],
		'px'
	)
);
//Doc Layout 7 Controls End

/** Single Doc Start **/

/**
 * Single Doc Content Area Width / Maximum Width.
 *
 * One pair of settings, three selectors — each layout family caps its content
 * wrapper through a different rule in the stylesheet, so the override has to
 * match the rule it is replacing:
 *
 *   layouts 8 / 9 / 10  .betterdocs-wrapper.betterdocs-single-layout-8 ...
 *                       (9 and 10 also carry the layout-8 class)
 *   layouts 1 / 6       ...:not(.betterdocs-fluid-wrapper) — these two are the
 *                       only single layouts without the fluid class
 *   layout 5            .betterdocs-single-layout-5 ...
 *
 * The 1/6 selector is additionally scoped to .betterdocs-single-wrapper: the
 * docs-archive and category-archive wrappers are also non-fluid, and they have
 * their own width controls (betterdocs_doc_page_content_* /
 * betterdocs_archive_content_area_*) that this would otherwise override.
 *
 * Layouts 2, 3 and 4 are deliberately absent — they are full-bleed and both
 * controls are hidden for them in customizer-condition.js.
 */
/**
 * Each selector is prefixed with `body` purely to win the cascade. The base
 * caps live in common/_normalizer.scss and templates/single.scss, which are
 * @imported into a dozen front-end bundles (single.css, article-summary.css,
 * category-grid.css, reactions.css, …), so the identical 0,3,0 rule is emitted
 * many times over and several of those stylesheets load *after* this inline
 * block in wp_head. At equal specificity the last one would win and the
 * setting would silently do nothing. One element selector takes us to 0,3,1
 * and beats every copy without resorting to !important.
 */
$betterdocs_single_content_width_selectors = [
	'body .betterdocs-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper',
	'body .betterdocs-wrapper.betterdocs-single-wrapper:not(.betterdocs-fluid-wrapper) .betterdocs-content-wrapper',
	'body .betterdocs-single-layout-5 .betterdocs-content-wrapper'
];

$css->add_rule(
	$betterdocs_single_content_width_selectors,
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_content_width'
		],
		'%'
	)
);

$css->add_rule(
	$betterdocs_single_content_width_selectors,
	$css->properties(
		[
			'max-width' => 'betterdocs_single_doc_content_max_width'
		],
		'px'
	)
);

//Single Doc Common Controllers Content Area Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_area_bg_color'
		]
	)
);
//Single Doc Common Controllers Content Area Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-content-full',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_area_bg_color'
		]
	)
);

//Single Doc Common Controllers Background Image, Background Property Attachment | Size | Repeat | Position
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'background-image' => [
				'id'         => 'betterdocs_doc_single_content_area_bg_image',
				'properties' => [
					'background-size'       => 'betterdocs_doc_single_content_bg_property_size',
					'background-repeat'     => 'betterdocs_doc_single_content_bg_property_repeat',
					'background-attachment' => 'betterdocs_doc_single_content_bg_property_attachment',
					'background-position'   => 'betterdocs_doc_single_content_bg_property_position'
				]
			]
		]
	)
);

//Single Doc Common Controllers Background Image, Background Property Attachment | Size | Repeat | Position
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-content-full',
	$css->properties(
		[
			'background-image' => [
				'id'         => 'betterdocs_doc_single_content_area_bg_image',
				'properties' => [
					'background-size'       => 'betterdocs_doc_single_content_bg_property_size',
					'background-repeat'     => 'betterdocs_doc_single_content_bg_property_repeat',
					'background-attachment' => 'betterdocs_doc_single_content_bg_property_attachment',
					'background-position'   => 'betterdocs_doc_single_content_bg_property_position'
				]
			]
		]
	)
);

//Single Doc Common Controllers Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-content-wrapper,.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-content-wrapper,.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-content-full',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_content_area_padding_top',
			'padding-right'  => 'betterdocs_doc_single_content_area_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_content_area_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_content_area_padding_left'
		],
		'px'
	)
);

//Single Doc Common Controllers Content Area Padding Top | Right | Bottom | Left (Layout-3)
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper .betterdocs-content-wrapper .betterdocs-content-area .betterdocs-content-inner-area',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_3_post_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_3_post_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_3_post_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_3_post_content_padding_left'
		],
		'px'
	)
);

//Single Doc Common Controllers Content Area Padding Top | Right | Bottom | Left (Layout-3)
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-content-wrapper .betterdocs-content-area .betterdocs-content-inner-area  .betterdocs-breadcrumb',
	$css->properties(
		[
			'margin-left' => '-%betterdocs_doc_single_3_post_content_padding_left%'
		],
		'px'
	)
);

//Single Doc Common Controllers Doc Content Padding Top | Right | Bottom | Left (Layout-1)
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-content-area',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_post_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_post_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_post_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_post_content_padding_left'
		],
		'px'
	)
);

//Post Content Padding Top | Right | Bottom | Left (Layout-2)(pro)
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-content-inner-area',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_2_post_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_2_post_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_2_post_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_2_post_content_padding_left'
		],
		'px'
	)
);

//Single Doc Text Transform
$css->add_rule(
	'.betterdocs-single-wrapper .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'text-transform' => 'betterdocs_post_title_text_transform'
		]
	)
);

//Single Doc Layout 1 Post Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Post Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color'
		]
	)
);

//Single Doc Layout 1 Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb-item.current span, .betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Archive Page Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-breadcrumb-item.current span, .betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Breadcrumb Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Archive Page Breadcrumb Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Single Doc Layout 1 Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Archive Page Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Single Doc Layout 1 Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Archive Page Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Single Doc Layout 1 Breadcrumb Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color'
		]
	)
);

//Single Doc Sticky TOC Width & Layout 1, 6
$css->add_rule(
	'.sticky-toc-container',
	$css->properties(
		[
			'width' => 'betterdocs_sticky_toc_width'
		],
		'px'
	)
);

//Single Doc Sticky TOC z-index & Layout 1, 6
$css->add_rule(
	'.sticky-toc-container',
	$css->properties(
		[
			'z-index' => 'betterdocs_sticky_toc_zindex'
		]
	)
);

//Single Doc Sticky TOC Margin Top & Layout 1, 6
$css->add_rule(
	'.sticky-toc-container',
	$css->properties(
		[
			'margin-top' => 'betterdocs_sticky_toc_margin_top'
		],
		'px'
	)
);

//Single Doc Layout 1 TOC Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'background-color' => 'betterdocs_toc_bg_color'
		]
	)
);

//Single Doc Layout 1 TOC Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'padding-top' => 'betterdocs_doc_single_toc_padding_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'padding-right' => 'betterdocs_doc_single_toc_padding_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'padding-left' => 'betterdocs_doc_single_toc_padding_left'
		],
		'px'
	)
);

//Single Doc Layout 1 TOC Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 1 TOC Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color'
		]
	)
);

//Single Doc Layout 1 TOC Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 TOC List Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color'
		]
	)
);

//Single Doc Layout 1 TOC List Item Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_hover_color'
		]
	)
);

//Single Doc Layout 1 TOC Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color'
		]
	)
);

//Single Doc Layout 1 TOC List Item Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 TOC List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_list_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_list_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_list_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 1 List Number Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_number_color'
		]
	)
);

//Single Doc Layout 1 List Number Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_number_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 TOC Margin Bottom
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-entry-content .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_toc_margin_bottom'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Content Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Content Font Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color'
		]
	)
);

//Single Doc All Layouts Background color
$css->add_rule(
	'.betterdocs-shortcode.betterdocs-article-reactions',
	$css->properties(
		[
			'background-color' => 'reactions_background_color'
		]
	)
);

//Single Doc Layout 1 Reactions Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-article-reactions .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color'
		]
	)
);

//Single Doc Layout 1 Reactions Icon Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-article-reactions .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color'
		]
	)
);

//Single Doc Layout 1 Reactions Icon Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-article-reactions .betterdocs-article-reaction-links li a svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_svg_color'
		]
	)
);

//Single Doc Layout 1 Reactions Icon Hover Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-article-reactions .betterdocs-article-reaction-links li a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_hover_bg_color'
		]
	)
);

//Single Doc Layout 1 Reactions Icon Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-article-reaction-links li a:hover svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_hover_svg_color'
		]
	)
);

//Single Doc Layout 1 Social Share Title Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Feedback Icon Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .feedback-form-link .feedback-form-icon svg, .betterdocs-single-wrapper.betterdocs-single-layout-1 .feedback-form-link .feedback-form-icon img',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_feedback_icon_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Feedback Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Feedback Link Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-entry-footer .feedback-form-link:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_hover_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Feedback Link Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_link_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Feedback Form Title Font Size
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Feedback Form Title Color
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_title_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Navigation Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Navigation Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Navigation Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Navigation Arrow Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Navigation Arrow Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .docs-navigation a svg',
	$css->properties(
		[
			'min-width' => 'betterdocs_single_doc_navigation_arrow_font_size',
			'width'     => 'betterdocs_single_doc_navigation_arrow_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Last Updated Time Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Last Updated Time Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_lu_time_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Powered by Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 1 Entry Footer Powered By Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

//Single Doc Layout 1 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

//Single Doc Layout 4 Post Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Post Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color'
		]
	)
);

//Single Doc Layout 4 Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb-item.current span, .betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Breadcrumb Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Single Doc Layout 4 Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Single Doc Layout 4 Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Single Doc Layout 4 Breadcrumb Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color'
		]
	)
);

//Single Doc Layout 4 TOC Sticky TOC Width
$css->add_rule(
	'',
	$css->properties(
		[
			'' => ''
		]
	)
);

//Single Doc Layout 4 TOC Sticky Toc Z-Index
$css->add_rule(
	'',
	$css->properties(
		[
			'' => ''
		]
	)
);

//Single Doc Layout 4 TOC Sticky Toc Margin Top
$css->add_rule(
	'',
	$css->properties(
		[
			'' => ''
		]
	)
);

//Single Doc Layout 4 TOC Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'background-color' => 'betterdocs_toc_bg_color'
		]
	)
);

//Single Doc Layout 4 TOC Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'padding-top' => 'betterdocs_doc_single_toc_padding_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'padding-right' => 'betterdocs_doc_single_toc_padding_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'padding-left' => 'betterdocs_doc_single_toc_padding_left'
		],
		'px'
	)
);

//Single Doc Layout 4 TOC Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_margin_top'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_margin_right'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_margin_bottom'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_margin_left'
		]
	)
);

//Single Doc Layout 4 TOC Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color'
		]
	)
);

//Single Doc Layout 4 TOC Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 TOC List Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color'
		]
	)
);

//Single Doc Layout 4 TOC List Item Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_hover_color'
		]
	)
);

//Single Doc Layout 4 TOC Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color'
		]
	)
);

//Single Doc Layout 4 TOC List Item Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 TOC List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_list_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_list_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_list_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 4 List Number Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_number_color'
		]
	)
);

//Single Doc Layout 4 List Number Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_number_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 TOC Margin Bottom
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-entry-content .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_toc_margin_bottom'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Content Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Content Font Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color'
		]
	)
);

//Single Doc Layout 4 Reactions Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-article-reactions .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color'
		]
	)
);

//Single Doc Layout 4 Reactions Icon Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-article-reactions .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color'
		]
	)
);

//Single Doc Layout 4 Reactions Icon Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-article-reactions .betterdocs-article-reaction-links li a svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_svg_color'
		]
	)
);

//Single Doc Layout 4 Reactions Icon Hover Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-article-reactions .betterdocs-article-reaction-links li a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_hover_bg_color'
		]
	)
);

//Single Doc Layout 4 Reactions Icon Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-article-reaction-links li a:hover svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_hover_svg_color'
		]
	)
);

//Single Doc Layout 4 Social Share Title Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Feedback Icon Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .feedback-form-link .feedback-form-icon svg, .betterdocs-single-wrapper.betterdocs-single-layout-4 .feedback-form-link .feedback-form-icon img',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_feedback_icon_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Feedback Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Feedback Link Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-entry-footer .feedback-form-link:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_hover_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Feedback Link Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_link_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Feedback Form Title Font Size
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Feedback Form Title Color
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_title_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Navigation Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Navigation Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Navigation Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Navigation Arrow Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Navigation Arrow Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .docs-navigation a svg',
	$css->properties(
		[
			'min-width' => 'betterdocs_single_doc_navigation_arrow_font_size',
			'width'     => 'betterdocs_single_doc_navigation_arrow_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Last Updated Time Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Last Updated Time Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_lu_time_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Powered by Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 4 Entry Footer Powered By Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

//Single Doc Layout 4 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

//Single Doc Layout 5 Post Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Post Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color'
		]
	)
);

//Single Doc Layout 5 Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb-item.current span, .betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Breadcrumb Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Single Doc Layout 5 Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Single Doc Layout 5 Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Single Doc Layout 5 Breadcrumb Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color'
		]
	)
);

//Single Doc Layout 5 TOC Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'padding-top' => 'betterdocs_doc_single_toc_padding_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'padding-right' => 'betterdocs_doc_single_toc_padding_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'padding-left' => 'betterdocs_doc_single_toc_padding_left'
		],
		'px'
	)
);

//Single Doc Layout 5 TOC Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 5 TOC Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color'
		]
	)
);

//Single Doc Layout 5 TOC Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 TOC List Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color'
		]
	)
);

//Single Doc Layout 5 TOC Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color'
		]
	)
);

//Single Doc Layout 5 TOC List Item Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 TOC List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_list_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_list_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_list_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 5 List Number Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_number_color'
		]
	)
);

//Single Doc Layout 5 List Number Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_number_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 TOC Margin Bottom
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-entry-content .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_toc_margin_bottom'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Content Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Content Font Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color'
		]
	)
);

//Single Doc Layout 5 Reactions Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-article-reactions .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color'
		]
	)
);

//Single Doc Layout 5 Reactions Icon Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-article-reactions .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color'
		]
	)
);

//Single Doc Layout 5 Reactions Icon Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-article-reactions .betterdocs-article-reaction-links li a svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_svg_color'
		]
	)
);

//Single Doc Layout 5 Reactions Icon Hover Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-article-reactions .betterdocs-article-reaction-links li a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_hover_bg_color'
		]
	)
);

//Single Doc Layout 5 Reactions Icon Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-article-reaction-links li a:hover svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_hover_svg_color'
		]
	)
);

//Single Doc Layout 5 Social Share Title Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Feedback Icon Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .feedback-form-link .feedback-form-icon svg, .betterdocs-single-wrapper.betterdocs-single-layout-5 .feedback-form-link .feedback-form-icon img',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_feedback_icon_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Feedback Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Feedback Link Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-entry-footer .feedback-form-link:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_hover_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Feedback Link Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_link_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Feedback Form Title Font Size
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Feedback Form Title Color
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_title_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Navigation Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Navigation Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Navigation Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Navigation Arrow Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Navigation Arrow Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .docs-navigation a svg',
	$css->properties(
		[
			'min-width' => 'betterdocs_single_doc_navigation_arrow_font_size',
			'width'     => 'betterdocs_single_doc_navigation_arrow_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Last Updated Time Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Last Updated Time Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_lu_time_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Powered by Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 5 Entry Footer Powered By Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

//Single Doc Layout 5 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

//Single Doc Layout 2 Post Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Post Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color'
		]
	)
);

//Single Doc Layout 2 Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb-item.current span, .betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Breadcrumb Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Single Doc Layout 2 Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Single Doc Layout 2 Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Single Doc Layout 2 Breadcrumb Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color'
		]
	)
);

//Single Doc Layout 2 TOC Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'padding-top' => 'betterdocs_doc_single_toc_padding_top'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'padding-right' => 'betterdocs_doc_single_toc_padding_right'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'padding-left' => 'betterdocs_doc_single_toc_padding_left'
		]
	)
);

//Single Doc Layout 4 TOC Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'background-color' => 'betterdocs_toc_bg_color'
		]
	)
);

//Single Doc Layout 2 TOC Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_margin_top'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_margin_right'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_margin_bottom'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_margin_left'
		]
	)
);

//Single Doc Layout 2 TOC Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color'
		]
	)
);

//Single Doc Layout 2 TOC Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 TOC List Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color'
		]
	)
);

//Single Doc Layout 4 TOC List Item Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_hover_color'
		]
	)
);

//Single Doc Layout 2 TOC Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color'
		]
	)
);

//Single Doc Layout 2 TOC List Item Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 TOC List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_list_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_list_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_list_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 2 List Number Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_number_color'
		]
	)
);

//Single Doc Layout 2 List Number Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_number_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 TOC Margin Bottom
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-entry-content .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_toc_margin_bottom'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Content Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Content Font Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color'
		]
	)
);

//Single Doc Layout 2 Reactions Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-article-reactions .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color'
		]
	)
);

//Single Doc Layout 2 Reactions Icon Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-article-reactions .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color'
		]
	)
);

//Single Doc Layout 2 Reactions Icon Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-article-reactions .betterdocs-article-reaction-links li a svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_svg_color'
		]
	)
);

//Single Doc Layout 2 Reactions Icon Hover Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-article-reactions .betterdocs-article-reaction-links li a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_hover_bg_color'
		]
	)
);

//Single Doc Layout 2 Reactions Icon Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-article-reaction-links li a:hover svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_hover_svg_color'
		]
	)
);

//Single Doc Layout 2 Social Share Title Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Feedback Icon Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .feedback-form-link .feedback-form-icon svg, .betterdocs-single-wrapper.betterdocs-single-layout-2 .feedback-form-link .feedback-form-icon img',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_feedback_icon_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Feedback Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Feedback Link Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-entry-footer .feedback-form-link:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_hover_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Feedback Link Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_link_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Feedback Form Title Font Size
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Feedback Form Title Color
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_title_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Navigation Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Navigation Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Navigation Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Navigation Arrow Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Navigation Arrow Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .docs-navigation a svg',
	$css->properties(
		[
			'min-width' => 'betterdocs_single_doc_navigation_arrow_font_size',
			'width'     => 'betterdocs_single_doc_navigation_arrow_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Last Updated Time Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Last Updated Time Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_lu_time_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Powered by Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 2 Entry Footer Powered By Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

//Single Doc Layout 2 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

//Single Doc Layout 3 Post Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Post Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color'
		]
	)
);

//Single Doc Layout 3 Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb-item.current span, .betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Breadcrumb Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Single Doc Layout 3 Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Single Doc Layout 3 Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Single Doc Layout 3 Breadcrumb Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color'
		]
	)
);

//Single Doc Layout 3 TOC Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'padding-top' => 'betterdocs_doc_single_toc_padding_top'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'padding-right' => 'betterdocs_doc_single_toc_padding_right'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'padding-left' => 'betterdocs_doc_single_toc_padding_left'
		]
	)
);

//Single Doc Layout 3 TOC Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'background-color' => 'betterdocs_toc_bg_color'
		]
	)
);

//Single Doc Layout 3 TOC Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_margin_top'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_margin_right'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_margin_bottom'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_margin_left'
		]
	)
);

//Single Doc Layout 3 TOC Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color'
		]
	)
);

//Single Doc Layout 3 TOC Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 TOC List Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color'
		]
	)
);

//Single Doc Layout 3 TOC List Item Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_hover_color'
		]
	)
);

//Single Doc Layout 3 TOC Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color'
		]
	)
);

//Single Doc Layout 3 TOC List Item Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 TOC List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_list_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_list_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_list_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 3 List Number Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_number_color'
		]
	)
);

//Single Doc Layout 3 List Number Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_number_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 TOC Margin Bottom
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-entry-content .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_toc_margin_bottom'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Content Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Content Font Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color'
		]
	)
);

//Single Doc Layout 3 Reactions Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-article-reactions .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color'
		]
	)
);

//Single Doc Layout 3 Reactions Icon Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-article-reactions .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color'
		]
	)
);

//Single Doc Layout 3 Reactions Icon Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-article-reactions .betterdocs-article-reaction-links li a svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_svg_color'
		]
	)
);

//Single Doc Layout 3 Reactions Icon Hover Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-article-reactions .betterdocs-article-reaction-links li a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_hover_bg_color'
		]
	)
);

//Single Doc Layout 3 Reactions Icon Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-article-reaction-links li a:hover svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_hover_svg_color'
		]
	)
);

//Single Doc Layout 3 Social Share Title Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Feedback Icon Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .feedback-form-link .feedback-form-icon svg, .betterdocs-single-wrapper.betterdocs-single-layout-3 .feedback-form-link .feedback-form-icon img',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_feedback_icon_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Feedback Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Feedback Link Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-entry-footer .feedback-form-link:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_hover_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Feedback Link Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_link_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Feedback Form Title Font Size
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Feedback Form Title Color
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_title_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Navigation Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Navigation Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Navigation Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Navigation Arrow Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Navigation Arrow Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .docs-navigation a svg',
	$css->properties(
		[
			'min-width' => 'betterdocs_single_doc_navigation_arrow_font_size',
			'width'     => 'betterdocs_single_doc_navigation_arrow_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Last Updated Time Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Last Updated Time Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_lu_time_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Powered by Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 3 Entry Footer Powered By Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

//Single Doc Layout 3 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

//Single Doc Layout 6 Post Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Post Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-single-title .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color'
		]
	)
);

//Single Doc Layout 6 Breadcrumb Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a, .betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb-item.current span, .betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Breadcrumb Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color'
		]
	)
);

//Single Doc Layout 6 Breadcrumb Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color'
		]
	)
);

//Single Doc Layout 6 Breadcrumb Seperator Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb .breadcrumb-delimiter',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color'
		]
	)
);

//Single Doc Layout 6 Breadcrumb Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color'
		]
	)
);

//Single Doc Layout 6 TOC Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'padding-top' => 'betterdocs_doc_single_toc_padding_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'padding-right' => 'betterdocs_doc_single_toc_padding_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'padding-left' => 'betterdocs_doc_single_toc_padding_left'
		],
		'px'
	)
);

//Single Doc Layout 6 TOC Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 6 TOC Title Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color'
		]
	)
);

//Single Doc Layout 6 TOC Title Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc > .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 TOC List Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color'
		]
	)
);

//Single Doc Layout 6 TOC Active Item Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color'
		]
	)
);

//Single Doc Layout 6 TOC List Item Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 TOC List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-top' => 'betterdocs_doc_single_toc_list_margin_top'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-right' => 'betterdocs_doc_single_toc_list_margin_right'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc .toc-list li a',
	$css->properties(
		[
			'margin-left' => 'betterdocs_doc_single_toc_list_margin_left'
		],
		'px'
	)
);

//Single Doc Layout 6 List Number Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_number_color'
		]
	)
);

//Single Doc Layout 6 List Number Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-toc > .toc-list li a:before',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_number_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 TOC Margin Bottom
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-entry-content .betterdocs-toc',
	$css->properties(
		[
			'margin-bottom' => 'betterdocs_toc_margin_bottom'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Content Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Content Font Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color'
		]
	)
);

//Single Doc Layout 6 Reactions Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-article-reactions .betterdocs-article-reactions-heading .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color'
		]
	)
);

//Single Doc Layout 6 Reactions Icon Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-article-reactions .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color'
		]
	)
);

//Single Doc Layout 6 Reactions Icon Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-article-reactions .betterdocs-article-reaction-links li a svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_svg_color'
		]
	)
);

//Single Doc Layout 6 Reactions Icon Hover Background Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-article-reactions .betterdocs-article-reaction-links li a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_hover_bg_color'
		]
	)
);

//Single Doc Layout 6 Reactions Icon Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-article-reaction-links li a:hover svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_post_reactions_icon_hover_svg_color'
		]
	)
);

//Single Doc Layout 6 Social Share Title Text Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Feedback Icon Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .feedback-form-link .feedback-form-icon svg, .betterdocs-single-wrapper.betterdocs-single-layout-6 .feedback-form-link .feedback-form-icon img',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_feedback_icon_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Feedback Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Feedback Link Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-entry-footer .feedback-form-link:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_link_hover_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Feedback Link Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-entry-footer .feedback-form-link',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_link_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Feedback Form Title Font Size
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_feedback_title_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Feedback Form Title Color
$css->add_rule(
	'#betterdocs-form-modal .modal-inner .modal-content .feedback-form-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_feedback_title_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Navigation Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Navigation Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Navigation Hover Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Navigation Arrow Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Navigation Arrow Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .docs-navigation a svg',
	$css->properties(
		[
			'min-width' => 'betterdocs_single_doc_navigation_arrow_font_size',
			'width'     => 'betterdocs_single_doc_navigation_arrow_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Last Updated Time Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Last Updated Time Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-entry-footer .update-date',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_lu_time_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Powered by Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Powered By Font Size
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

//Single Doc Layout 6 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-6 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

/** Single Doc Layout 8, 9 Start **/

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-search-modal-layout-1, .betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'width' => 'single_doc_layout_8_9_search_width'
		],
		'%'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-search-modal-layout-1, .betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'max-width' => 'single_doc_layout_8_9_search_max_width'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-search-modal-layout-1, .betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'margin-top'    => 'single_doc_layout_8_9_search_margin_top',
			'margin-bottom' => 'single_doc_layout_8_9_search_margin_bottom'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-search-modal-layout-1, .betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'margin-left'  => 'auto',
			'margin-right' => 'auto'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-search-modal-layout-1, .betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'padding-top'    => 'single_doc_layout_8_9_search_padding_top',
			'padding-right'  => 'single_doc_layout_8_9_search_padding_right',
			'padding-bottom' => 'single_doc_layout_8_9_search_padding_bottom',
			'padding-left'   => 'single_doc_layout_8_9_search_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title',
	$css->properties(
		[
			'text-transform' => 'betterdocs_post_title_text_transform_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_title_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_title_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc',
	$css->properties(
		[
			'background-color' => 'betterdocs_toc_bg_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_toc_padding_top_layout_8_9',
			'padding-right'  => 'betterdocs_doc_single_toc_padding_right_layout_8_9',
			'padding-bottom' => 'betterdocs_doc_single_toc_padding_bottom_layout_8_9',
			'padding-left'   => 'betterdocs_doc_single_toc_padding_left_layout_8_9',
			'margin-top'     => 'betterdocs_doc_single_toc_margin_top_layout_8_9',
			'margin-right'   => 'betterdocs_doc_single_toc_margin_right_layout_8_9',
			'margin-bottom'  => 'betterdocs_doc_single_toc_margin_bottom_layout_8_9',
			'margin-left'    => 'betterdocs_doc_single_toc_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc .toc-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc .toc-title',
	$css->properties(
		[
			'color' => 'betterdocs_toc_title_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc .toc-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc .toc-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_title_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a:hover, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_toc_list_item_hover_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a.active, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a:focus, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a.active, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a:focus',
	$css->properties(
		[
			'color' => 'betterdocs_toc_active_item_color_layout_8_9'
		]
	)
);


$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-sidebar.betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a.active::after, .betterdocs-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-sidebar.betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a.active::after',
	$css->properties(
		[
			'background' => 'toc_active_item_border_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-layout-8.betterdocs-single-layout-10 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a.active, .betterdocs-single-layout-8.betterdocs-single-layout-10 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a:hover',
	$css->properties(
		[
			'background-color' => 'toc_item_hover_bg_color_layout_10'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_toc_list_item_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc ul li a',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_toc_list_margin_top_layout_8_9',
			'margin-right'  => 'betterdocs_doc_single_toc_list_margin_right_layout_8_9',
			'margin-bottom' => 'betterdocs_doc_single_toc_list_margin_bottom_layout_8_9',
			'margin-left'   => 'betterdocs_doc_single_toc_list_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-layout-8 .betterdocs-content-area .betterdocs.reading-time,.betterdocs-single-layout-9 .betterdocs-content-area .betterdocs.reading-time',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_est_reading_bg_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time',
	$css->properties(
		[
			'border-radius' => 'betterdocs_doc_single_content_est_reading_border_radius'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_content_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content',
	$css->properties(
		[
			'color' => 'betterdocs_single_content_font_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-social-share .betterdocs-social-share-heading .betterdocs-social-share-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_social_share_text_color_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_navigation_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a:hover, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_navigation_hover_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a svg, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a svg',
	$css->properties(
		[
			'fill' => 'betterdocs_single_doc_navigation_arrow_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a svg, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .docs-navigation a svg',
	$css->properties(
		[
			'width' => 'betterdocs_single_doc_navigation_arrow_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-article-reactions .betterdocs-article-reactions-box',
	$css->properties(
		[
			'background-color' => 'reactions_background_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-article-reactions',
	$css->properties(
		[
			'background-color' => 'reactions_background_color_layout_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-article-reactions .betterdocs-article-reactions-box p, .betterdocs-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-article-reactions .betterdocs-article-reactions-sidebar h5,.betterdocs-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-article-reactions .betterdocs-article-reactions-sidebar .betterdocs-reactions-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_post_reactions_text_color_layout_8_9'
		]
	)
);
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-article-reactions .betterdocs-article-reactions-box .layout-2 li a.betterdocs-emoji, .betterdocs-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-article-reactions.layout-2 .betterdocs-article-reactions-sidebar .betterdocs-article-reaction-links li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_post_reactions_icon_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_attachment_content_bg_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_attachment_content_padding_top_layout_8_9',
			'padding-right'  => 'betterdocs_doc_single_attachment_content_padding_right_layout_8_9',
			'padding-bottom' => 'betterdocs_doc_single_attachment_content_padding_bottom_layout_8_9',
			'padding-left'   => 'betterdocs_doc_single_attachment_content_padding_left_layout_8_9',
			'margin-top'     => 'betterdocs_doc_single_attachment_content_margin_top_layout_8_9',
			'margin-right'   => 'betterdocs_doc_single_attachment_content_margin_right_layout_8_9',
			'margin-bottom'  => 'betterdocs_doc_single_attachment_content_margin_bottom_layout_8_9',
			'margin-left'    => 'betterdocs_doc_single_attachment_content_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_attachment_label_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_attachment_label_padding_top_layout_8_9',
			'padding-right'  => 'betterdocs_doc_single_attachment_label_padding_right_layout_8_9',
			'padding-bottom' => 'betterdocs_doc_single_attachment_label_padding_bottom_layout_8_9',
			'padding-left'   => 'betterdocs_doc_single_attachment_label_padding_left_layout_8_9',
			'margin-top'     => 'betterdocs_doc_single_attachment_label_margin_top_layout_8_9',
			'margin-right'   => 'betterdocs_doc_single_attachment_label_margin_right_layout_8_9',
			'margin-bottom'  => 'betterdocs_doc_single_attachment_label_margin_bottom_layout_8_9',
			'margin-left'    => 'betterdocs_doc_single_attachment_label_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_single_attachment_list_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_single_attachment_list_font_weight_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_attachment_list_extension_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_single_attachment_list_extension_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_single_attachment_list_extension_font_weight_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_attachment_list_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_attachment_list_background_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-wrapper .attachment-list .attachment-details',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_attachment_list_padding_top_layout_8_9',
			'padding-right'  => 'betterdocs_doc_single_attachment_list_padding_right_layout_8_9',
			'padding-bottom' => 'betterdocs_doc_single_attachment_list_padding_bottom_layout_8_9',
			'padding-left'   => 'betterdocs_doc_single_attachment_list_padding_left_layout_8_9',
			'margin-top'     => 'betterdocs_doc_single_attachment_list_margin_top_layout_8_9',
			'margin-right'   => 'betterdocs_doc_single_attachment_list_margin_right_layout_8_9',
			'margin-bottom'  => 'betterdocs_doc_single_attachment_list_margin_bottom_layout_8_9',
			'margin-left'    => 'betterdocs_doc_single_attachment_list_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_related_docs_content_bg_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front',
	$css->properties(
		[
			'paddin-top'    => 'betterdocs_doc_single_related_docs_content_padding_top_layout_8_9',
			'paddin-right'  => 'betterdocs_doc_single_related_docs_content_padding_right_layout_8_9',
			'paddin-bottom' => 'betterdocs_doc_single_related_docs_content_padding_bottom_layout_8_9',
			'paddin-left'   => 'betterdocs_doc_single_related_docs_content_padding_left_layout_8_9',
			'margin-top'    => 'betterdocs_doc_single_related_docs_content_margin_top_layout_8_9',
			'margin-right'  => 'betterdocs_doc_single_related_docs_content_margin_right_layout_8_9',
			'margin-bottom' => 'betterdocs_doc_single_related_docs_content_margin_bottom_layout_8_9',
			'margin-left'   => 'betterdocs_doc_single_related_docs_content_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_related_docs_label_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .related-articles-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-attachment-heading',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_related_docs_label_padding_top_layout_8_9',
			'padding-right'  => 'betterdocs_doc_single_related_docs_label_padding_right_layout_8_9',
			'padding-bottom' => 'betterdocs_doc_single_related_docs_label_padding_bottom_layout_8_9',
			'padding-left'   => 'betterdocs_doc_single_related_docs_label_padding_left_layout_8_9',
			'margin-top'     => 'betterdocs_doc_single_related_docs_label_margin_top_layout_8_9',
			'margin-right'   => 'betterdocs_doc_single_related_docs_label_margin_right_layout_8_9',
			'margin-bottom'  => 'betterdocs_doc_single_related_docs_label_margin_bottom_layout_8_9',
			'margin-left'    => 'betterdocs_doc_single_related_docs_label_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_related_docs_list_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li a',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_related_docs_list_font_weight_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li a, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_related_docs_list_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_related_docs_list_background_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-related-articles-container-front .related-articles-list li',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_related_docs_list_padding_top_layout_8_9',
			'padding-right'  => 'betterdocs_doc_single_related_docs_list_padding_right_layout_8_9',
			'padding-bottom' => 'betterdocs_doc_single_related_docs_list_padding_bottom_layout_8_9',
			'padding-left'   => 'betterdocs_doc_single_related_docs_list_padding_left_layout_8_9',
			'margin-top'     => 'betterdocs_doc_single_related_docs_list_margin_top_layout_8_9',
			'margin-right'   => 'betterdocs_doc_single_related_docs_list_margin_right_layout_8_9',
			'margin-bottom'  => 'betterdocs_doc_single_related_docs_list_margin_bottom_layout_8_9',
			'margin-left'    => 'betterdocs_doc_single_related_docs_list_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-credit p',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_powered_by_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-credit p',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

//Single Doc Layout 6 Entry Footer Powered By Link Color
$css->add_rule(
	'.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-credit p a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_powered_by_link_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-header .betterdocs-entry-title',
	$css->properties(
		[
			'margin-top'    => 'single_doc_title_margin_top_layout_8_9',
			'margin-right'  => 'single_doc_title_margin_right_layout_8_9',
			'margin-bottom' => 'single_doc_title_margin_bottom_layout_8_9',
			'margin-left'   => 'single_doc_title_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc .toc-title, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-toc .toc-title',
	$css->properties(
		[
			'margin-top'    => 'toc_title_margin_top_layout_8_9',
			'margin-right'  => 'toc_title_margin_right_layout_8_9',
			'margin-bottom' => 'toc_title_margin_bottom_layout_8_9',
			'margin-left'   => 'toc_title_margin_left_layout_8_9'
		],
		'px'
	)
);

//Article Summary Title Background Color
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_article_summary_title_bg_color'
		]
	)
);

//Article Summary Title Color
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-header .betterdocs-summary-title',
	$css->properties(
		[
			'color' => 'betterdocs_article_summary_title_color'
		]
	)
);

//Article Summary Title Font Size
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-header .betterdocs-summary-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_article_summary_title_font_size'
		],
		'px'
	)
);

//Article Summary Content Color
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-loading, .betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-content, .betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-content .betterdocs-summary-text .summary-content p, .betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-content p',
	$css->properties(
		[
			'color' => 'betterdocs_article_summary_content_color'
		]
	)
);

//Article Summary Content Font Size
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-loading, .betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-content,.betterdocs-entry-content .betterdocs-article-summary .betterdocs-summary-content p, .betterdocs-article-summary .betterdocs-summary-text p',
	$css->properties(
		[
			'font-size' => 'betterdocs_article_summary_content_font_size'
		],
		'px'
	)
);

//Article Summary Background Color
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary',
	$css->properties(
		[
			'background-color' => 'betterdocs_article_summary_bg_color'
		]
	)
);

//Article Summary Border Color
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary',
	$css->properties(
		[
			'border-color' => 'betterdocs_article_summary_border_color'
		]
	)
);

//Article Summary Border Width
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary',
	$css->properties(
		[
			'border-width' => 'betterdocs_article_summary_border_width'
		],
		'px'
	)
);

//Article Summary Border Radius
$css->add_rule(
	'.betterdocs-entry-content .betterdocs-article-summary',
	$css->properties(
		[
			'border-radius' => 'betterdocs_article_summary_border_radius'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content, .betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content',
	$css->properties(
		[
			'padding-top'    => 'single_content_padding_top_layout_8_9',
			'padding-right'  => 'single_content_padding_right_layout_8_9',
			'padding-bottom' => 'single_content_padding_bottom_layout_8_9',
			'padding-left'   => 'single_content_padding_left_layout_8_9',
			'margin-top'     => 'single_content_margin_top_layout_8_9',
			'margin-right'   => 'single_content_margin_right_layout_8_9',
			'margin-bottom'  => 'single_content_margin_bottom_layout_8_9',
			'margin-left'    => 'single_content_margin_left_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-article-reactions .betterdocs-article-reactions-box',
	$css->properties(
		[
			'margin-top'          => 'post_reactions_margin_top_layout_8',
			'margin-right'        => 'post_reactions_margin_right_layout_8',
			'margin-bottom'       => 'post_reactions_margin_bottom_layout_8',
			'margin-left'         => 'post_reactions_margin_left_layout_8',
			'padding-top'         => 'post_reactions_padding_top_layout_8',
			'padding-right'       => 'post_reactions_padding_right_layout_8',
			'padding-bottom'      => 'post_reactions_padding_bottom_layout_8',
			'padding-left'        => 'post_reactions_padding_left_layout_8',
			'border-top-width'    => 'post_reactions_border_top_layout_8',
			'border-right-width'  => 'post_reactions_border_right_layout_8',
			'border-bottom-width' => 'post_reactions_border_bottom_layout_8',
			'border-left-width'   => 'post_reactions_border_left_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .betterdocs-entry-footer .betterdocs-social-share .betterdocs-social-share-links',
	$css->properties(
		[
			'margin-top'     => 'post_social_share_margin_top_layout_8',
			'margin-right'   => 'post_social_share_margin_right_layout_8',
			'margin-bottom'  => 'post_social_share_margin_bottom_layout_8',
			'margin-left'    => 'post_social_share_margin_left_layout_8',
			'padding-top'    => 'post_social_share_padding_top_layout_8',
			'padding-right'  => 'post_social_share_padding_right_layout_8',
			'padding-bottom' => 'post_social_share_padding_bottom_layout_8',
			'padding-left'   => 'post_social_share_padding_left_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .update-date',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_lu_time_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .docs-content-full-main .betterdocs-content-inner-area .update-date',
	$css->properties(
		[
			'font-size' => 'single_doc_lu_time_font_size_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-article-reactions',
	$css->properties(
		[
			'margin-top'     => 'post_reactions_margin_top_layout_9',
			'margin-right'   => 'post_reactions_margin_right_layout_9',
			'margin-bottom'  => 'post_reactions_margin_bottom_layout_9',
			'margin-left'    => 'post_reactions_margin_left_layout_9',
			'padding-top'    => 'post_reactions_padding_top_layout_9',
			'padding-right'  => 'post_reactions_padding_right_layout_9',
			'padding-bottom' => 'post_reactions_padding_bottom_layout_9',
			'padding-left'   => 'post_reactions_padding_left_layout_9',
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-wrapper.betterdocs-single-layout-9 .betterdocs-content-wrapper .betterdocs-full-sidebar-right .right-sidebar-toc-container .simplebar-content .betterdocs-social-share',
	$css->properties(
		[
			'margin-top'     => 'post_social_share_margin_top_layout_9',
			'margin-right'   => 'post_social_share_margin_right_layout_9',
			'margin-bottom'  => 'post_social_share_margin_bottom_layout_9',
			'margin-left'    => 'post_social_share_margin_left_layout_9',
			'padding-top'    => 'post_social_share_padding_top_layout_9',
			'padding-right'  => 'post_social_share_padding_right_layout_9',
			'padding-bottom' => 'post_social_share_padding_bottom_layout_9',
			'padding-left'   => 'post_social_share_padding_left_layout_9'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform',
	$css->properties(
		[
			'margin-top'     => 'sidebar_search_field_margin_top_layout_7',
			'margin-right'   => 'sidebar_search_field_margin_right_layout_7',
			'margin-bottom'  => 'sidebar_search_field_margin_bottom_layout_7',
			'margin-left'    => 'sidebar_search_field_margin_left_layout_7',
			'padding-top'    => 'sidebar_search_field_padding_top_layout_7',
			'padding-right'  => 'sidebar_search_field_padding_right_layout_7',
			'padding-bottom' => 'sidebar_search_field_padding_bottom_layout_7',
			'padding-left'   => 'sidebar_search_field_padding_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform .command-key',
	$css->properties(
		[
			'padding-top'    => 'sidebar_search_field_command_key_padding_top_layout_7',
			'padding-right'  => 'sidebar_search_field_command_key_padding_right_layout_7',
			'padding-bottom' => 'sidebar_search_field_command_key_padding_bottom_layout_7',
			'padding-left'   => 'sidebar_search_field_command_key_padding_left_layout_7'
		],
		'px'
	)
);


$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform .command-key',
	$css->properties(
		[
			'color' => 'sidebar_search_field_command_key_color_layout_7',
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'padding-top'    => 'item_count_font_padding_top_layout_7',
			'padding-right'  => 'item_count_font_padding_right_layout_7',
			'padding-bottom' => 'item_count_font_padding_bottom_layout_7',
			'padding-left'   => 'item_count_font_padding_left_layout_7'
		],
		'px'
	)
);
/** Single Doc Layout 8, 9 End **/

/** Single Doc End **/

/** SideBar Controls Start **/

//Sidebar Background Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_bg_color'
		]
	)
);

// //Sidebar Background Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_bg_color'
// ] ) );

//Sidebar Padding Top | Right | Bottom | Left Layout 1
$css->add_rule(
	'.betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_padding_top',
			'padding-right'  => 'betterdocs_sidebar_padding_right',
			'padding-bottom' => 'betterdocs_sidebar_padding_bottom',
			'padding-left'   => 'betterdocs_sidebar_padding_left'
		],
		'px'
	)
);

// //Sidebar Padding Top | Right | Bottom | Left Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_padding_left'
// ], 'px' ) );

//Sidebar Border Radius Top Left | Top Right | Bottom Right | Bottom Left Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content',
	$css->properties(
		[
			'border-top-left-radius'     => 'betterdocs_sidebar_borderr_topleft',
			'border-top-right-radius'    => 'betterdocs_sidebar_borderr_topright',
			'border-bottom-right-radius' => 'betterdocs_sidebar_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_sidebar_borderr_bottomleft'
		],
		'px'
	)
);

// //Sidebar Border Radius Top Left | Top Right | Bottom Right | Bottom Left Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'border-top-left-radius'     => 'betterdocs_sidebar_borderr_topleft',
//     'border-top-right-radius'    => 'betterdocs_sidebar_borderr_topright',
//     'border-bottom-right-radius' => 'betterdocs_sidebar_borderr_bottomright',
//     'border-bottom-left-radius'  => 'betterdocs_sidebar_borderr_bottomleft'
// ], 'px' ) );

//Sidebar Icon Size Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-icon .betterdocs-category-icon-img',
	$css->properties(
		[
			'height' => 'betterdocs_sidebar_icon_size'
		],
		'px'
	)
);

// //Sidebar Icon Size Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-category-icon .betterdocs-category-icon-img', $css->properties( [
//     'height' => 'betterdocs_sidebar_icon_size'
// ], 'px' ) );

//Sidebar Title Background Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_title_bg_color'
		]
	)
);

// //Sidebar Title Background Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_title_bg_color'
// ] ) );

//Sidebar Active Title Background Color | Border Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_active_cat_background_color',
			'border-color'     => 'betterdocs_sidebar_active_cat_border_color'
		]
	)
);

// //Sidebar Active Title Background Color | Border Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_active_cat_background_color',
//     'border-color'     => 'betterdocs_sidebar_active_cat_border_color'
// ] ) );

//Sidebar Title Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color'
		]
	)
);

// //Sidebar Title Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_color'
// ] ) );

//Sidebar Title Hover Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color'
		]
	)
);

// //Sidebar Title Hover Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_hover_color'
// ] ) );

//Sidebar Active Title Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content.betterdocs-category-sidebar .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content.betterdocs-category-sidebar .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_title_color'
		]
	)
);

// //Sidebar Active Title Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_title_color'
// ] ) );

//Sidebar Title Hover Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color'
		]
	)
);

// //Sidebar Title Hover Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_hover_color'
// ] ) );

//Sidebar Title Font Size Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a), .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list-inner .betterdocs-sidebar-list.betterdocs-sidebar-layout-6 .betterdocs-sidebar-list-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size'
		],
		'px'
	)
);

// //Sidebar Title Font Size Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_title_font_size'
// ], 'px' ) );

//Sidebar Title Padding Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_title_padding_top',
			'padding-right'  => 'betterdocs_sidebar_title_padding_right',
			'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
			'padding-left'   => 'betterdocs_sidebar_title_padding_left'
		],
		'px'
	)
);

// //Sidebar Title Padding Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_title_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_title_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_title_padding_left'
// ], 'px' ) );

//Sidebar Title Margin Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_title_margin_top',
			'margin-right'  => 'betterdocs_sidebar_title_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_title_margin_left'
		],
		'px'
	)
);

// //Sidebar Title Margin Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_title_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_title_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_title_margin_left'
// ], 'px' ) );

// SIDEBAR ITEM COUNTER Background Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_count_bg_color'
		]
	)
);

// // SIDEBAR ITEM COUNTER Background Color Layout 1 (Single Doc)
// $css->add_rule( ' .betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_count_bg_color'
// ] ) );

// SIDEBAR ITEM COUNTER Inner Circle Background Color | Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_count_inner_bg_color',
			'color'            => 'betterdocs_sidebar_item_count_color'
		]
	)
);

// // SIDEBAR ITEM COUNTER Inner Circle Background Color | Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_count_inner_bg_color',
//     'color'            => 'betterdocs_sidebar_item_count_color'
// ] ) );

// SIDEBAR ITEM COUNTER Size (Height | Width) Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'height' => 'betterdocs_sidebar_item_counter_size',
			'width'  => 'betterdocs_sidebar_item_counter_size'
		],
		'px'
	)
);

// // SIDEBAR ITEM COUNTER Size (Height | Width) Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span', $css->properties( [
//     'height' => 'betterdocs_sidebar_item_counter_size',
//     'width'  => 'betterdocs_sidebar_item_counter_size'
// ] ) );

// SIDEBAR ITEM COUNTER Font Size Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebat_item_count_font_size'
		],
		'px'
	)
);

// // SIDEBAR ITEM COUNTER Font Size Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span', $css->properties( [
//     'font-size' => 'betterdocs_sidebat_item_count_font_size'
// ], 'px' ) );

//Sidebar Content Background Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-sidebar-content .betterdocs-body',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
		]
	)
);

// //Sidebar Content Background Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-body', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
// ] ) );

//Sidebar Content List Item Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color'
		]
	)
);

// //Sidebar Content List Item Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_color'
// ] ) );

//Sidebar Content List Item Hover Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color'
		]
	)
);

// //Sidebar Content List Item Hover Color Layout 1 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_hover_color'
// ] ) );

//Sidebar Content List Item Font Size Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Font Size Layout  (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_item_font_size'
// ], 'px' ) );

//Sidebar Content List Item Icon Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_sidebar_list_icon_color'
		]
	)
);

// //Sidebar Content List Item Icon Color (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'fill' => 'betterdocs_sidebar_list_icon_color'
// ] ) );

//Sidebar Content List Item Icon Font Size Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_icon_font_size',
			'min-width' => 'betterdocs_sidebar_list_icon_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Icon Font Size (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_icon_font_size'
// ], 'px' ) );

//Sidebar Content List Item Margin Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
			'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
		],
		'px'
	)
);

// //Sidebar Content List Item Margin (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
// ], 'px' ) );

//Sidebar Content Active List Item Color Layout 1
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-1 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_list_item_color'
		]
	)
);

// //Sidebar Content Active List Item Color (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-1 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_list_item_color'
// ] ) );

//Sidebar Background Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_bg_color'
		]
	)
);

// //Sidebar Background Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_bg_color'
// ] ) );

// //Sidebar Padding Top | Right | Bottom | Left Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_padding_left'
// ], 'px' ) );

//Sidebar Title Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color'
		]
	)
);

// //Sidebar Title Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_color'
// ] ) );

//Sidebar Title Background Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_title_bg_color'
		]
	)
);

// //Sidebar Title Background Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_title_bg_color'
// ] ) );

//Sidebar Active Title Background Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_active_cat_background_color'
		]
	)
);

// //Sidebar Active Title Background Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_active_cat_background_color'
// ] ) );

//Sidebar Title Hover Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color'
		]
	)
);

// //Sidebar Title Hover Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover, .betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_hover_color'
// ] ) );

//Sidebar Active Title Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_title_color'
		]
	)
);

// //Sidebar Active Title Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_title_color'
// ] ) );

//Sidebar Title Font Size Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size'
		],
		'px'
	)
);

// //Sidebar Title Font Size Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_title_font_size'
// ], 'px' ) );

//Sidebar Title Padding Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_title_padding_top',
			'padding-right'  => 'betterdocs_sidebar_title_padding_right',
			'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
			'padding-left'   => 'betterdocs_sidebar_title_padding_left'
		],
		'px'
	)
);

// //Sidebar Title Padding Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_title_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_title_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_title_padding_left'
// ], 'px' ) );

//Sidebar Title Margin Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_title_margin_top',
			'margin-right'  => 'betterdocs_sidebar_title_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_title_margin_left'
		],
		'px'
	)
);

// //Sidebar Title Margin Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_title_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_title_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_title_margin_left'
// ], 'px' ) );

//Sidebar Content Background Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-body',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
		]
	)
);

// //Sidebar Content Background Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-body', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
// ] ) );

//Sidebar Content List Item Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color'
		]
	)
);

// //Sidebar Content List Item Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_color'
// ] ) );

//Sidebar Content List Item Hover Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color'
		]
	)
);

// //Sidebar Content List Item Hover Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_hover_color'
// ] ) );

//Sidebar Content List Item Font Size Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Font Size Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_item_font_size'
// ], 'px' ) );

//Sidebar Content List Item Icon Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_sidebar_list_icon_color'
		]
	)
);

// //Sidebar Content List Item Icon Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'fill' => 'betterdocs_sidebar_list_icon_color'
// ] ) );

//Sidebar Content List Item Icon Font Size Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_icon_font_size',
			'min-width' => 'betterdocs_sidebar_list_icon_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Icon Font Size Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_icon_font_size'
// ], 'px' ) );

//Sidebar Content List Item Margin Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
			'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom'
		],
		'px'
	)
);

//Sidebar Content List Item Margin Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li:not(.betterdocs-nested-category-wrapper)',
	$css->properties(
		[
			'margin-right' => 'betterdocs_sidebar_list_item_margin_right',
			'margin-left'  => 'betterdocs_sidebar_list_item_margin_left'
		],
		'px'
	)
);

//Sidebar Content List Item Margin Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li.betterdocs-nested-category-wrapper .betterdocs-nested-category-title',
	$css->properties(
		[
			'margin-right' => 'betterdocs_sidebar_list_item_margin_right',
			'margin-left'  => 'betterdocs_sidebar_list_item_margin_left'
		],
		'px'
	)
);

// //Sidebar Content List Item Margin Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
// ], 'px' ) );

//Sidebar Content Active List Item Color Layout 5
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_list_item_color'
		]
	)
);

// //Sidebar Content Active List Item Color Layout 5 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-5 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_list_item_color'
// ] ) );

//Sidebar Background Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_bg_color'
		]
	)
);

// //Sidebar Background Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_bg_color'
// ] ) );

// //Sidebar Padding Top | Right | Bottom | Left Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_padding_left'
// ], 'px' ) );

//Sidebar Title Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color'
		]
	)
);

// //Sidebar Title Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_color'
// ] ) );

//Sidebar Title Background Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_title_bg_color'
		]
	)
);

// //Sidebar Title Background Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_title_bg_color'
// ] ) );

//Sidebar Active Title Background Color Layout 4
// $css->add_rule( '.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_active_cat_background_color'
// ] ) );

// //Sidebar Active Title Background Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_active_cat_background_color'
// ] ) );

//Sidebar Title Hover Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color'
		]
	)
);

// //Sidebar Title Hover Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover, .betterdocs-content-wrapper.doc-category-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_hover_color'
// ] ) );

//Sidebar Active Title Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_title_color'
		]
	)
);

// //Sidebar Active Title Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_title_color'
// ] ) );

//Sidebar Title Font Size Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size'
		],
		'px'
	)
);

// //Sidebar Title Font Size Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_title_font_size'
// ], 'px' ) );

//Sidebar Title Padding Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_title_padding_top',
			'padding-right'  => 'betterdocs_sidebar_title_padding_right',
			'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
			'padding-left'   => 'betterdocs_sidebar_title_padding_left'
		],
		'px'
	)
);

// //Sidebar Title Padding Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_title_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_title_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_title_padding_left'
// ], 'px' ) );

//Sidebar Title Margin Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_title_margin_top',
			'margin-right'  => 'betterdocs_sidebar_title_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_title_margin_left'
		],
		'px'
	)
);

// //Sidebar Title Margin Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_title_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_title_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_title_margin_left'
// ], 'px' ) );

//Sidebar Content Background Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-sidebar-content .betterdocs-body',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
		]
	)
);

// //Sidebar Content Background Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-body', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
// ] ) );

//Sidebar Content List Item Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color'
		]
	)
);

// //Sidebar Content List Item Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_color'
// ] ) );

//Sidebar Content List Item Hover Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color'
		]
	)
);

// //Sidebar Content List Item Hover Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_hover_color'
// ] ) );

//Sidebar Content List Item Font Size Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Font Size Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_item_font_size'
// ], 'px' ) );

//Sidebar Content List Item Icon Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_sidebar_list_icon_color'
		]
	)
);

// //Sidebar Content List Item Icon Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'fill' => 'betterdocs_sidebar_list_icon_color'
// ] ) );

//Sidebar Content List Item Icon Font Size Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_icon_font_size',
			'min-width' => 'betterdocs_sidebar_list_icon_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Icon Font Size Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_icon_font_size'
// ], 'px' ) );

//Sidebar Content List Item Margin Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
			'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
		],
		'px'
	)
);

// //Sidebar Content List Item Margin Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
// ], 'px' ) );

//Sidebar Content Active List Item Color Layout 4
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-4 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_list_item_color'
		]
	)
);

// //Sidebar Content Active List Item Color Layout 4 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-4 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_list_item_color'
// ] ) );

//Sidebar Background Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-category-list-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_bg_color'
		]
	)
);

// //Sidebar Background Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-category-list-wrapper', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_bg_color'
// ] ) );

// //Sidebar Padding Top | Right | Bottom | Left Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-category-list-wrapper', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_padding_left'
// ], 'px' ) );

//Sidebar Title Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color'
		]
	)
);

// //Sidebar Title Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_color'
// ] ) );

//Sidebar Title Hover Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-5 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color'
		]
	)
);

// //Sidebar Title Hover Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover, .betterdocs-content-wrapper.doc-category-layout-5 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_hover_color'
// ] ) );

//Sidebar Active Title Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_title_color'
		]
	)
);

// //Sidebar Active Title Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_title_color'
// ] ) );

//Sidebar Title Background Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_title_bg_color'
		]
	)
);

// //Sidebar Title Background Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_title_bg_color'
// ] ) );

//Sidebar Title Font Size Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size'
		],
		'px'
	)
);

// //Sidebar Title Font Size Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_title_font_size'
// ], 'px' ) );

//Sidebar Title Padding Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_title_padding_top',
			'padding-right'  => 'betterdocs_sidebar_title_padding_right',
			'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
			'padding-left'   => 'betterdocs_sidebar_title_padding_left'
		],
		'px'
	)
);

// //Sidebar Title Padding Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_title_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_title_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_title_padding_left'
// ], 'px' ) );

//Sidebar Title Margin Layout 2
// $css->add_rule( '.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_title_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_title_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_title_margin_left'
// ], 'px' ) );

// //Sidebar Title Margin Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_title_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_title_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_title_margin_left'
// ], 'px' ) );

//Sidebar Content Background Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-body',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
		]
	)
);

// //Sidebar Content Background Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-body', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
// ] ) );

//Sidebar Content List Item Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color'
		]
	)
);

// //Sidebar Content List Item Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_color'
// ] ) );

//Sidebar Content List Item Hover Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color'
		]
	)
);

// //Sidebar Content List Item Hover Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_hover_color'
// ] ) );

//Sidebar Content List Item Font Size Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Font Size Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_item_font_size'
// ], 'px' ) );

//Sidebar Content List Item Icon Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_sidebar_list_icon_color'
		]
	)
);

// //Sidebar Content List Item Icon Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'fill' => 'betterdocs_sidebar_list_icon_color'
// ] ) );

//Sidebar Content List Item Icon Font Size Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_icon_font_size',
			'min-width' => 'betterdocs_sidebar_list_icon_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Icon Font Size Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_icon_font_size'
// ], 'px' ) );

//Sidebar Content List Item Margin Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
			'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
		],
		'px'
	)
);

// //Sidebar Content List Item Margin Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
// ], 'px' ) );

//Sidebar Content Active List Item Color Layout 2
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-2 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_list_item_color'
		]
	)
);

// //Sidebar Content Active List Item Color Layout 2 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-2 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_list_item_color'
// ] ) );

//Sidebar Background Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_bg_color'
		]
	)
);

// //Sidebar Background Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_bg_color'
// ] ) );

// //Sidebar Padding Top | Right | Bottom | Left Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_padding_left'
// ], 'px' ) );

//Sidebar Title Background Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_title_bg_color'
		]
	)
);

// //Sidebar Title Background Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_title_bg_color'
// ] ) );

//Sidebar Active Title Background Color | Border Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_active_cat_background_color',
			'border-color'     => '#528fff'
		]
	)
);

// //Sidebar Active Title Background Color | Border Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_active_cat_background_color',
//     'border'           => 'none'
// ] ) );

//Sidebar Title Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color'
		]
	)
);

// //Sidebar Title Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_color'
// ] ) );

//Sidebar Title Hover Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a):hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color'
		]
	)
);

// //Sidebar Title Hover Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_title_hover_color'
// ] ) );

//Sidebar Active Title Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_title_color'
		]
	)
);

// //Sidebar Active Title Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper.active .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_title_color'
// ] ) );

//Sidebar Title Font Size Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a, .betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:not(a)',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size'
		],
		'px'
	)
);

// //Sidebar Title Font Size Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_title_font_size'
// ], 'px' ) );

//Sidebar Title Padding Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_title_padding_top',
			'padding-right'  => 'betterdocs_sidebar_title_padding_right',
			'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
			'padding-left'   => 'betterdocs_sidebar_title_padding_left'
		],
		'px'
	)
);

// //Sidebar Title Padding Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'padding-top'    => 'betterdocs_sidebar_title_padding_top',
//     'padding-right'  => 'betterdocs_sidebar_title_padding_right',
//     'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom',
//     'padding-left'   => 'betterdocs_sidebar_title_padding_left'
// ], 'px' ) );

//Sidebar Title Margin Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_title_margin_top',
			'margin-right'  => 'betterdocs_sidebar_title_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_title_margin_left'
		],
		'px'
	)
);

// //Sidebar Title Margin Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_title_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_title_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_title_margin_left'
// ], 'px' ) );

//Sidebar Content Background Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-sidebar-content .betterdocs-body',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
		]
	)
);

// //Sidebar Content Background Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-sidebar-content .betterdocs-body', $css->properties( [
//     'background-color' => 'betterdocs_sidbebar_item_list_bg_color'
// ] ) );

//Sidebar Content List Item Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color'
		]
	)
);

// //Sidebar Content List Item Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_color'
// ] ) );

//Sidebar Content List Item Hover Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color'
		]
	)
);

// //Sidebar Content List Item Hover Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a:hover', $css->properties( [
//     'color' => 'betterdocs_sidebar_list_item_hover_color'
// ] ) );

//Sidebar Content List Item Font Size Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size'
		],
		'px'
	)
);

// //Sidebar Content List Item Font Size Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_item_font_size'
// ], 'px' ) );

//Sidebar Content List Item Icon Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-single-category-wrapper .betterdocs-articles-list li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_sidebar_list_icon_color'
		]
	)
);

// //Sidebar Content List Item Icon Font Size Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-articles-list li svg', $css->properties( [
//     'font-size' => 'betterdocs_sidebar_list_icon_font_size'
// ], 'px' ) );

//Sidebar Content List Item Margin Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
			'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
			'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
			'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
		],
		'px'
	)
);

// //Sidebar Content List Item Margin Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li', $css->properties( [
//     'margin-top'    => 'betterdocs_sidebar_list_item_margin_top',
//     'margin-right'  => 'betterdocs_sidebar_list_item_margin_right',
//     'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom',
//     'margin-left'   => 'betterdocs_sidebar_list_item_margin_left'
// ], 'px' ) );

//Sidebar Content Active List Item Color Layout 3
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-3 .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_list_item_color'
		]
	)
);

// //Sidebar Content Active List Item Color Layout 3 (Single Doc)
// $css->add_rule( '.betterdocs-single-wrapper.betterdocs-single-layout-3 .betterdocs-sidebar .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-articles-list li a.active', $css->properties( [
//     'color' => 'betterdocs_sidebar_active_list_item_color'
// ] ) );

//Sidebar Layout 7 Controls Start
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_bg_color_layout_7'
		]
	)
);
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-single-layout-8 .betterdocs-content-wrapper .betterdocs-sidebar.betterdocs-sidebar-layout-7',
	$css->properties(
		[
			'border-color' => 'betterdocs_sidebar_border_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_padding_top_layout_7',
			'padding-right'  => 'betterdocs_sidebar_padding_right_layout_7',
			'padding-bottom' => 'betterdocs_sidebar_padding_bottom_layout_7',
			'padding-left'   => 'betterdocs_sidebar_padding_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-folder-icon',
	$css->properties(
		[
			'width' => 'betterdocs_sidebar_icon_size_layout_7',
			'height' => 'betterdocs_sidebar_icon_size_layout_7',
		],
		'px'
	)
);

// $css->add_rule( '.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header', $css->properties( [
//     'background-color' => 'betterdocs_sidebar_title_bg_color_layout_7'
// ] ) );

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.show .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_active_cat_background_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.active.default.show::before',
	$css->properties(
		[
			'background' => 'betterdocs_sidebar_active_cat_border_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_list_bg_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_hover_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper.show .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_active_title_color_layout_7'
		]
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size_layout_7'
		],
		'px'
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_title_padding_top_layout_7',
			'padding-right'  => 'betterdocs_sidebar_title_padding_right_layout_7',
			'padding-bottom' => 'betterdocs_sidebar_title_padding_bottom_layout_7',
			'padding-left'   => 'betterdocs_sidebar_title_padding_left_layout_7'
		],
		'px'
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_title_margin_top_layout_7',
			'margin-right'  => 'betterdocs_sidebar_title_margin_right_layout_7',
			'margin-bottom' => 'betterdocs_sidebar_title_margin_bottom_layout_7',
			'margin-left'   => 'betterdocs_sidebar_title_margin_left_layout_7'
		],
		'px'
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_count_bg_color_layout_7'
		]
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'height' => 'betterdocs_sidebar_item_counter_size_layout_7',
			'width'  => 'betterdocs_sidebar_item_counter_size_layout_7'
		],
		'px'
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_item_count_color_layout_7'
		]
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebat_item_count_font_size_layout_7'
		],
		'px'
	)
);
$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidbebar_item_list_bg_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a.active:before',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_list_item_active_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a.active',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_list_item_active_color_layout_7_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li a',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_sidebar_list_item_margin_top_layout_7',
			'margin-right'  => 'betterdocs_sidebar_list_item_margin_right_layout_7',
			'margin-bottom' => 'betterdocs_sidebar_list_item_margin_bottom_layout_7',
			'margin-left'   => 'betterdocs_sidebar_list_item_margin_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform',
	$css->properties(
		[
			'margin-top'     => 'sidebar_search_field_margin_top_layout_7',
			'margin-right'   => 'sidebar_search_field_margin_right_layout_7',
			'margin-bottom'  => 'sidebar_search_field_margin_bottom_layout_7',
			'margin-left'    => 'sidebar_search_field_margin_left_layout_7',
			'padding-top'    => 'sidebar_search_field_padding_top_layout_7',
			'padding-right'  => 'sidebar_search_field_padding_right_layout_7',
			'padding-bottom' => 'sidebar_search_field_padding_bottom_layout_7',
			'padding-left'   => 'sidebar_search_field_padding_left_layout_7',
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform .command-key',
	$css->properties(
		[
			'padding-top'    => 'sidebar_search_field_command_key_padding_top_layout_7',
			'padding-right'  => 'sidebar_search_field_command_key_padding_right_layout_7',
			'padding-bottom' => 'sidebar_search_field_command_key_padding_bottom_layout_7',
			'padding-left'   => 'sidebar_search_field_command_key_padding_left_layout_7',
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search.betterdocs-search-popup .betterdocs-searchform .command-key',
	$css->properties(
		[
			'color' => 'sidebar_search_field_command_key_color_layout_7'
		]
	)
);


$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-7 .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-items-counts span',
	$css->properties(
		[
			'padding-top'    => 'item_count_font_padding_top_layout_7',
			'padding-right'  => 'item_count_font_padding_right_layout_7',
			'padding-bottom' => 'item_count_font_padding_bottom_layout_7',
			'padding-left'   => 'item_count_font_padding_left_layout_7',
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-full-sidebar-left .betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-category-grid-inner-wrapper .betterdocs-single-category-wrapper .betterdocs-single-category-inner',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_sidebar_list_item_padding_top_layout_7',
			'padding-right'  => 'betterdocs_sidebar_list_item_padding_right_layout_7',
			'padding-bottom' => 'betterdocs_sidebar_list_item_padding_bottom_layout_7',
			'padding-left'   => 'betterdocs_sidebar_list_item_padding_left_layout_7',
		],
		'px'
	)
);

//Sidebar Layout 7 Controls End

//Sidebar Layout 8 Controls Start
$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8',
	$css->properties(
		[
			'border-right-width' => 'betterdocs_sidebar_border_right_width_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8',
	$css->properties(
		[
			'border-right-color' => 'betterdocs_sidebar_border_right_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_title_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_title_font_size_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'margin'    => 'betterdocs_sidebar_title_margin_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'padding'    => 'betterdocs_sidebar_title_padding_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-sidebar.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-category-header .betterdocs-category-header-inner .betterdocs-category-collapse',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_collapsible_arrow_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_sidebar_list_item_hover_background_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_sidebar_list_item_hover_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a',
	$css->properties(
		[
			'font-size' => 'betterdocs_sidebar_list_item_font_size_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a',
	$css->properties(
		[
			'margin'    => 'betterdocs_sidebar_list_item_margin_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-full-sidebar-left.betterdocs-sidebar-layout-8 .betterdocs-sidebar-content .betterdocs-single-category-wrapper .betterdocs-single-category-inner .betterdocs-body .betterdocs-articles-list li:not(:has(>ul)) a',
	$css->properties(
		[
			'padding'    => 'betterdocs_sidebar_list_item_padding_layout_8'
		],
		'px'
	)
);

//Sidebar Layout 8 Controls End

/** SideBar Controls End **/

//Attachment Content Background Color (Single Doc)
$css->add_rule(
	'.betterdocs-attachment-wrapper',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_attachment_content_bg_color'
		]
	)
);

//Attachment Content Padding Top | Right | Bottom | Left (Single Doc)
$css->add_rule(
	'.betterdocs-attachment-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_attachment_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_attachment_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_attachment_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_attachment_content_padding_left'
		],
		'px'
	)
);

//Attachment Content Margin Top | Right | Bottom | Left (Single Doc)
$css->add_rule(
	'.betterdocs-attachment-wrapper',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_attachment_content_margin_top',
			'margin-right'  => 'betterdocs_doc_single_attachment_content_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_attachment_content_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_attachment_content_margin_left'
		],
		'px'
	)
);

//Attachment Label Color (Single Doc)
$css->add_rule(
	'.betterdocs-attachment-wrapper .betterdocs-attachment-heading',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_attachment_label_color'
		],
		'px'
	)
);

//Attachment Label Padding (Single Doc)
$css->add_rule(
	'.betterdocs-attachment-wrapper .betterdocs-attachment-heading',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_attachment_label_padding_top',
			'padding-right'  => 'betterdocs_doc_single_attachment_label_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_attachment_label_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_attachment_label_padding_left'
		],
		'px'
	)
);

//Attachment Label Margin (Single Doc)
$css->add_rule(
	'.betterdocs-attachment-wrapper .betterdocs-attachment-heading',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_attachment_label_margin_top',
			'margin-right'  => 'betterdocs_doc_single_attachment_label_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_attachment_label_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_attachment_label_margin_left'
		],
		'px'
	)
);

//Attachment List Font Size
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_single_attachment_list_font_size'
		],
		'px'
	)
);

//Attachment List Font Weight
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_single_attachment_list_font_weight'
		]
	)
);

//Attachment List Extension Font Size
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_single_attachment_list_extension_font_size'
		],
		'px'
	)
);

//Attachment List Extension Font Weight
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_single_attachment_list_extension_font_weight'
		]
	)
);

//Attachment List Extension Color
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name, .betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-size',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_attachment_list_extension_color'
		]
	)
);

//Attachment List Color
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details a .attachment-name',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_attachment_list_color'
		]
	)
);

//Attachment List Background Color
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_attachment_list_background_color'
		]
	)
);

//Attachment List Padding
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_attachment_list_padding_top',
			'padding-right'  => 'betterdocs_doc_single_attachment_list_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_attachment_list_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_attachment_list_padding_left'
		],
		'px'
	)
);

//Attachment List Margin
$css->add_rule(
	'.betterdocs-attachment-wrapper .attachment-list .attachment-details',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_attachment_list_margin_top',
			'margin-right'  => 'betterdocs_doc_single_attachment_list_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_attachment_list_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_attachment_list_margin_left'
		],
		'px'
	)
);

//Related Docs Content Background Color (Single Doc)
$css->add_rule(
	'.betterdocs-related-articles-container-front',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_related_docs_content_bg_color'
		]
	)
);

//Related Docs Content Padding Top | Right | Bottom | Left (Single Doc)
$css->add_rule(
	'.betterdocs-related-articles-container-front',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_related_docs_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_related_docs_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_related_docs_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_related_docs_content_padding_left'
		],
		'px'
	)
);

//Related Docs Content Margin Top | Right | Bottom | Left (Single Doc)
$css->add_rule(
	'.betterdocs-related-articles-container-front',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_related_docs_content_margin_top',
			'margin-right'  => 'betterdocs_doc_single_related_docs_content_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_related_docs_content_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_related_docs_content_margin_left'
		],
		'px'
	)
);

//Related Docs Label Color (Single Doc)
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-title',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_related_docs_label_color'
		]
	)
);

//Related Docs Label Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-title',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_related_docs_label_padding_top',
			'padding-right'  => 'betterdocs_doc_single_related_docs_label_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_related_docs_label_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_related_docs_label_padding_left'
		],
		'px'
	)
);

//Related Docs Label Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-title',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_related_docs_label_margin_top',
			'margin-right'  => 'betterdocs_doc_single_related_docs_label_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_related_docs_label_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_related_docs_label_margin_left'
		],
		'px'
	)
);

//Related Docs List Font Size
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_related_docs_list_font_size'
		],
		'px'
	)
);

//Related Docs List Font Weight
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-list li a',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_related_docs_list_font_weight'
		]
	)
);

//Related Docs List Color
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_related_docs_list_color'
		]
	)
);

//Related Docs List Background Color
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-list li',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_related_docs_list_background_color'
		]
	)
);

//Related Docs List Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-list li',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_related_docs_list_padding_top',
			'padding-right'  => 'betterdocs_doc_single_related_docs_list_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_related_docs_list_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_related_docs_list_padding_left'
		],
		'px'
	)
);

//Related Docs List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-related-articles-container-front .related-articles-list li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_related_docs_list_margin_top',
			'margin-right'  => 'betterdocs_doc_single_related_docs_list_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_related_docs_list_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_related_docs_list_margin_left'
		],
		'px'
	)
);

/** Archive Page Controls Start **/

//Archive Page Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-category-archive-wrapper,.tax-doc_category .betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-archive-layout-7',
	$css->properties(
		[
			'background-color' => 'betterdocs_archive_page_background_color'
		]
	)
);

//Archive Page Background Image/Properties
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-category-archive-wrapper',
	$css->properties(
		[
			'background-image' => [
				'id'         => 'betterdocs_archive_page_background_image',
				'properties' => [
					'background-size'       => 'betterdocs_archive_page_background_size',
					'background-repeat'     => 'betterdocs_archive_page_background_repeat',
					'background-attachment' => 'betterdocs_archive_page_background_attachment',
					'background-position'   => 'betterdocs_archive_page_background_position'
				]
			]
		]
	)
);

//Archive Content Area Width
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'width' => 'betterdocs_archive_content_area_width'
		],
		'%'
	)
);

// //Archive Content Area Maximum Width
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'max-width' => 'betterdocs_archive_content_area_max_width'
		],
		'px'
	)
);

//Category Archive Padding (since betterdocs revamped version)
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-wrapper',
	$css->properties(
		[
			'padding-top'    => 'category_archive_padding_top',
			'padding-right'  => 'category_archive_padding_right',
			'padding-bottom' => 'category_archive_padding_bottom',
			'padding-left'   => 'category_archive_padding_left'
		],
		'px'
	)
);

//Archive Content Area Background Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area',
	$css->properties(
		[
			'background-color' => 'betterdocs_archive_content_background_color'
		]
	)
);

//Archive Content Area Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_archive_content_margin_top',
			'margin-right'  => 'betterdocs_archive_content_margin_right',
			'margin-bottom' => 'betterdocs_archive_content_margin_bottom',
			'margin-left'   => 'betterdocs_archive_content_margin_left'
		],
		'px'
	)
);

//Archive Content Area Padding Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_archive_content_padding_top',
			'padding-right'  => 'betterdocs_archive_content_padding_right',
			'padding-bottom' => 'betterdocs_archive_content_padding_bottom',
			'padding-left'   => 'betterdocs_archive_content_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-archive-layout-7 .betterdocs-search-modal-layout-1, .betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-archive-layout-8 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'max-width'      => 'archive_search_max_width',
			'margin-top'     => 'archive_search_margin_top',
			'margin-bottom'  => 'archive_search_margin_bottom',
			'padding-top'    => 'archive_search_padding_top',
			'padding-right'  => 'archive_search_padding_right',
			'padding-bottom' => 'archive_search_padding_bottom',
			'padding-left'   => 'archive_search_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-archive-layout-7 .betterdocs-search-modal-layout-1, .betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-archive-layout-8 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'width' => 'archive_search_width'
		],
		'%'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper.betterdocs-archive-layout-7 .betterdocs-search-modal-layout-1',
	$css->properties(
		[
			'margin-left'  => 'auto',
			'margin-right' => 'auto'
		]
	)
);

//Archive Content Border Radius
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area',
	$css->properties(
		[
			'border-radius' => 'betterdocs_archive_content_border_radius'
		],
		'px'
	)
);

//Archive Title Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-title .betterdocs-entry-heading',
	$css->properties(
		[
			'color' => 'betterdocs_archive_title_color'
		]
	)
);

//Archive Title Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-title .betterdocs-entry-heading',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_title_font_size'
		],
		'px'
	)
);

//Archive Archive Title Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-title .betterdocs-entry-heading',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_archive_title_margin_top',
			'margin-right'  => 'betterdocs_archive_title_margin_right',
			'margin-bottom' => 'betterdocs_archive_title_margin_bottom',
			'margin-left'   => 'betterdocs_archive_title_margin_left'
		],
		'px'
	)
);

//Archive Description Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-title p:not(.betterdocs-entry-heading)',
	$css->properties(
		[
			'color' => 'betterdocs_archive_description_color'
		]
	)
);

//Archive Description Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-title p:not(.betterdocs-entry-heading)',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_description_font_size'
		],
		'px'
	)
);

//Archive Description Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-title p:not(.betterdocs-entry-heading)',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_archive_description_margin_top',
			'margin-right'  => 'betterdocs_archive_description_margin_right',
			'margin-bottom' => 'betterdocs_archive_description_margin_bottom',
			'margin-left'   => 'betterdocs_archive_description_margin_left'
		],
		'px'
	)
);

//Archive List Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body ul li svg#Layer_1',
	$css->properties(
		[
			'fill' => 'betterdocs_archive_list_icon_color'
		]
	)
);

//Archive List Icon Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body ul li svg#Layer_1',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_list_icon_font_size',
			'min-width' => 'betterdocs_archive_list_icon_font_size'
		],
		'px'
	)
);

//Archive List Item Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body ul li a',
	$css->properties(
		[
			'color' => 'betterdocs_archive_list_item_color'
		]
	)
);

//Archive List Item Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body ul li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_list_item_hover_color'
		]
	)
);

//Archive List Item Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body ul li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_list_item_font_size'
		],
		'px'
	)
);

//Archive Docs List Margin Top | Right | Bottom | Left
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body ul li',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_archive_article_list_margin_top',
			'margin-right'  => 'betterdocs_archive_article_list_margin_right',
			'margin-bottom' => 'betterdocs_archive_article_list_margin_bottom',
			'margin-left'   => 'betterdocs_archive_article_list_margin_left'
		],
		'px'
	)
);

//Archive Docs Subcategory Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a',
	$css->properties(
		[
			'color' => 'betterdocs_archive_article_subcategory_color'
		]
	)
);

//Archive Docs Subcategory Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_article_subcategory_hover_color'
		]
	)
);

//Archive Docs Subcategory Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-title a',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_article_subcategory_font_size'
		],
		'px'
	)
);

//Archive Subcategory Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'fill' => 'betterdocs_archive_subcategory_icon_color'
		]
	)
);

//Archive Subcategory Icon Font Size
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_subcategory_icon_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-title svg',
	$css->properties(
		[
			'width' => 'betterdocs_archive_subcategory_icon_font_size'
		],
		'px'
	)
);

//Archive Subcategory Docs List Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_archive_subcategory_article_list_color'
		]
	)
);

//Archive Subcategory List Hover Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_subcategory_article_list_hover_color'
		]
	)
);

//Archive Subcategory List Icon Color
$css->add_rule(
	'.betterdocs-wrapper.betterdocs-taxonomy-wrapper .betterdocs-content-area .betterdocs-content-inner-area .betterdocs-entry-body .betterdocs-nested-category-wrapper .betterdocs-nested-category-list li svg#Layer_1',
	$css->properties(
		[
			'fill' => 'betterdocs_archive_subcategory_article_list_icon_color'
		]
	)
);

//Archive Title Hover Color
$css->add_rule(
	'',
	$css->properties(
		[
			'color' => ''
		]
	)
);

//Archive Layout 7 & 8 Controls Start
$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder',
	$css->properties(
		[
			'background-color' => 'content_header_background_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder:hover',
	$css->properties(
		[
			'background-color' => 'content_header_background_hover_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-icon .betterdocs-folder-icon, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-icon .betterdocs-folder-icon',
	$css->properties(
		[
			'height' => 'content_header_background_image_size_layout_7',
			'width'  => 'content_header_background_image_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'font-size' => 'content_header_background_title_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-category-title',
	$css->properties(
		[
			'color' => 'content_header_background_title_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'font-size' => 'content_header_background_count_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-main-category-folder .betterdocs-category-header-inner .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'color' => 'content_header_background_count_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box',
	$css->properties(
		[
			'background-color' => 'archive_column_background_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_archive_column_hover_bg_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_archive_page_column_padding_top_layout_7',
			'padding-right'  => 'betterdocs_archive_page_column_padding_right_layout_7',
			'padding-bottom' => 'betterdocs_archive_page_column_padding_bottom_layout_7',
			'padding-left'   => 'betterdocs_archive_page_column_padding_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box',
	$css->properties(
		[
			'border-color' => 'archive_column_border_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-folder-icon, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-folder-icon, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-folder-icon, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-folder-icon',
	$css->properties(
		[
			'height' => 'betterdocs_archive_page_cat_icon_size_layout_7',
			'width'  => 'betterdocs_archive_page_cat_icon_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_page_cat_title_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title',
	$css->properties(
		[
			'color' => 'betterdocs_archive_page_cat_title_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title:hover',
	$css->properties(
		[
			'color' => 'archive_category_title_color_hover_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-category-title',
	$css->properties(
		[
			'margin-top'    => 'archive_category_margin_top_layout_7',
			'margin-right'  => 'archive_category_margin_right_layout_7',
			'margin-bottom' => 'archive_category_margin_bottom_layout_7',
			'margin-left'   => 'archive_category_margin_bottom_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_page_item_count_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'color' => 'archive_betterdocs_doc_page_item_count_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_page_item_count_hover_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .update-date,.betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update',
	$css->properties(
		[
			'font-size' => 'archive_last_updated_time_layout_7_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .update-date,.betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update',
	$css->properties(
		[
			'color' => 'archive_last_updated_time_layout_7_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .update-date,.betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-last-update',
	$css->properties(
		[
			'background-color' => 'archive_last_updated_time_layout_7_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a',
	$css->properties(
		[
			'color' => 'archive_docs_list_title_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a:hover, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a:hover',
	$css->properties(
		[
			'color' => 'archive_docs_list_title_hover_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a',
	$css->properties(
		[
			'font-size' => 'archive_docs_list_title_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title span, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title span',
	$css->properties(
		[
			'height' => 'archive_docs_list_icon_size_layout_7',
			'width'  => 'archive_docs_list_icon_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list p, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list p',
	$css->properties(
		[
			'font-size' => 'archive_docs_list_excerpt_font_size_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list p, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list p',
	$css->properties(
		[
			'color' => 'archive_docs_list_excerpt_font_color_layout_7'
		]
	)
);

$css->add_rule(
	'.betterdocs-archive-layout-8 .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list',
	$css->properties(
		[
			'border-bottom-width' => 'archive_docs_list_border_width_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-archive-layout-8 .doc-category-layout-8 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list',
	$css->properties(
		[
			'border-bottom-color' => 'archive_docs_list_border_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-content-wrapper.doc-category-layout-7 #main.betterdocs-content-area .betterdocs-content-inner-area',
	$css->properties(
		[
			'padding-top'    => 'content_area_padding_top_layout_7',
			'padding-right'  => 'content_area_padding_right_layout_7',
			'padding-bottom' => 'content_area_padding_bottom_layout_7',
			'padding-left'   => 'content_area_padding_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .betterdocs-entry-title a',
	$css->properties(
		[
			'padding-top'    => 'archive_docs_list_title_margin_top_layout_7',
			'padding-right'  => 'archive_docs_list_title_margin_right_layout_7',
			'padding-bottom' => 'archive_docs_list_title_margin_bottom_layout_7',
			'padding-left'   => 'archive_docs_list_title_margin_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .betterdocs-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts, .betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-content-wrapper .betterdocs-shortcode .betterdocs-categories-folder.layout-4 .category-box .betterdocs-single-category-inner .betterdocs-category-header-inner .betterdocs-category-title-counts .betterdocs-sub-category-items-counts',
	$css->properties(
		[
			'margin-top'    => 'archive_page_item_count_margin_top_layout_7',
			'margin-right'  => 'archive_page_item_count_margin_right_layout_7',
			'margin-bottom' => 'archive_page_item_count_margin_bottom_layout_7',
			'margin-left'   => 'archive_page_item_count_margin_left_layout_7'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .update-date',
	$css->properties(
		[
			'margin-top'    => 'archive_last_updated_time_layout_7_margin_top',
			'margin-right'  => 'archive_last_updated_time_layout_7_margin_right',
			'margin-bottom' => 'archive_last_updated_time_layout_7_margin_bottom',
			'margin-left'   => 'archive_last_updated_time_layout_7_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list .update-date',
	$css->properties(
		[
			'padding-top'    => 'archive_last_updated_time_layout_7_padding_top',
			'padding-right'  => 'archive_last_updated_time_layout_7_padding_right',
			'padding-bottom' => 'archive_last_updated_time_layout_7_padding_bottom',
			'padding-left'   => 'archive_last_updated_time_layout_7_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper.betterdocs-wraper .doc-category-layout-7 .betterdocs-title-excerpt-lists .betterdocs-title-excerpt-list p',
	$css->properties(
		[
			'margin-top'    => 'archive_docs_list_excerpt_margin_top_layout_7',
			'margin-right'  => 'archive_docs_list_excerpt_margin_right_layout_7',
			'margin-bottom' => 'archive_docs_list_excerpt_margin_bottom_layout_7',
			'margin-left'   => 'archive_docs_list_excerpt_margin_left_layout_7'
		],
		'px'
	)
);

//Archive Layout 7 Controls End

/** Archive Page Controls End **/

$css->add_rule(
	'.betterdocs-category-box.single-kb .docs-single-cat-wrap .docs-cat-title:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_hover_color'
		],
		''
	)
);

$css->add_rule(
	'.betterdocs-category-grid-layout-6 .betterdocs-term-info .betterdocs-term-title',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_color2'
		],
		''
	)
);

$css->add_rule(
	'.betterdocs-category-grid-layout-6 .betterdocs-term-info .betterdocs-term-title:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_cat_title_hover_color'
		],
		''
	)
);

$css->add_rule(
	'.betterdocs-categories-wrap.single-kb .docs-item-container li',
	$css->properties(
		[
			'margin-top'     => 'betterdocs_doc_page_article_list_margin_top',
			'margin-right'   => 'betterdocs_doc_page_article_list_margin_right',
			'margin-bottom'  => 'betterdocs_doc_page_article_list_margin_bottom',
			'margin-left'    => 'betterdocs_doc_page_article_list_margin_left',
			'padding-top'    => 'betterdocs_doc_page_article_list_padding_top',
			'padding-right'  => 'betterdocs_doc_page_article_list_padding_right',
			'padding-bottom' => 'betterdocs_doc_page_article_list_padding_bottom',
			'padding-left'   => 'betterdocs_doc_page_article_list_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-categories-wrap.single-kb .docs-item-container .docs-sub-cat-title',
	$css->properties(
		[
			'margin-top'   => 'betterdocs_doc_page_article_list_margin_top',
			'margin-right' => 'betterdocs_doc_page_article_list_margin_right',
			'margin-left'  => 'betterdocs_doc_page_article_list_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-popular-list.single-kb ul li',
	$css->properties(
		[
			'margin-top'     => 'betterdocs_doc_page_article_list_margin_top',
			'margin-right'   => 'betterdocs_doc_page_article_list_margin_right',
			'margin-bottom'  => 'betterdocs_doc_page_article_list_margin_bottom',
			'margin-left'    => 'betterdocs_doc_page_article_list_margin_left',
			'padding-top'    => 'betterdocs_doc_page_article_list_padding_top',
			'padding-right'  => 'betterdocs_doc_page_article_list_padding_right',
			'padding-bottom' => 'betterdocs_doc_page_article_list_padding_bottom',
			'padding-left'   => 'betterdocs_doc_page_article_list_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-categories-wrap.single-kb li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_hover_color'
		],
		''
	)
);

$css->add_rule(
	'.betterdocs-popular-list.single-kb ul li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_doc_page_article_list_hover_color'
		],
		''
	)
);

$css->add_rule(
	'.betterdocs-categories-wrap.single-kb .docs-single-cat-wrap .docs-item-container',
	$css->properties(
		[
			'border-bottom-right-radius' => 'betterdocs_doc_page_column_borderr_bottomright',
			'border-bottom-left-radius'  => 'betterdocs_doc_page_column_borderr_bottomleft'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-bg .betterdocs-content-area, .betterdocs-single-bg .betterdocs-content-full',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_area_bg_color',
			'background-image' => [
				'id'         => 'betterdocs_doc_single_content_area_bg_image',
				'properties' => [
					'background-size'       => 'betterdocs_doc_single_content_bg_property_size',
					'background-repeat'     => 'betterdocs_doc_single_content_bg_property_repeat',
					'background-attachment' => 'betterdocs_doc_single_content_bg_property_attachment',
					'background-position'   => 'betterdocs_doc_single_content_bg_property_position'
				]
			]
		]
	)
);

$css->add_rule(
	'.betterdocs-single-layout4 .betterdocs-content-full',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_content_area_padding_top',
			'padding-right'  => 'betterdocs_doc_single_content_area_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_content_area_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_content_area_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-layout4 .betterdocs-content-full',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_area_bg_color',
			'background-image' => [
				'id'         => 'betterdocs_doc_single_content_area_bg_image',
				'properties' => [
					'background-size'       => 'betterdocs_doc_single_content_bg_property_size',
					'background-repeat'     => 'betterdocs_doc_single_content_bg_property_repeat',
					'background-attachment' => 'betterdocs_doc_single_content_bg_property_attachment',
					'background-position'   => 'betterdocs_doc_single_content_bg_property_position'
				]
			]
		]
	)
);

$css->add_rule(
	'.betterdocs-single-layout5 .betterdocs-content-full',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_content_area_padding_top',
			'padding-right'  => 'betterdocs_doc_single_content_area_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_content_area_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_content_area_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-layout5 .betterdocs-content-full',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_area_bg_color',
			'background-image' => [
				'id'         => 'betterdocs_doc_single_content_area_bg_image',
				'properties' => [
					'background-size'       => 'betterdocs_doc_single_content_bg_property_size',
					'background-repeat'     => 'betterdocs_doc_single_content_bg_property_repeat',
					'background-attachment' => 'betterdocs_doc_single_content_bg_property_attachment',
					'background-position'   => 'betterdocs_doc_single_content_bg_property_position'
				]
			]
		]
	)
);

$css->add_rule(
	'.betterdocs-single-layout2 .docs-content-full-main .doc-single-content-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_2_post_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_2_post_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_2_post_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_2_post_content_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-single-layout3 .docs-content-full-main .doc-single-content-wrapper',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_3_post_content_padding_top',
			'padding-right'  => 'betterdocs_doc_single_3_post_content_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_3_post_content_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_3_post_content_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper.betterdocs-single-wraper',
	$css->properties(
		[
			'background-color' => 'betterdocs_archive_page_background_color',
			'background-image' => [
				'id'         => 'betterdocs_archive_page_background_image',
				'properties' => [
					'background-size'       => 'betterdocs_archive_page_background_size',
					'background-repeat'     => 'betterdocs_archive_page_background_repeat',
					'background-attachment' => 'betterdocs_archive_page_background_attachment',
					'background-position'   => 'betterdocs_archive_page_background_position'
				]
			]
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper.betterdocs-single-wraper .docs-listing-main .docs-category-listing',
	$css->properties(
		[
			'background-color' => 'betterdocs_archive_content_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper.betterdocs-single-wraper .docs-listing-main .docs-category-listing',
	$css->properties(
		[
			'margin-top'     => 'betterdocs_archive_content_margin_top',
			'margin-right'   => 'betterdocs_archive_content_margin_right',
			'margin-bottom'  => 'betterdocs_archive_content_margin_bottom',
			'margin-left'    => 'betterdocs_archive_content_margin_left',
			'padding-top'    => 'betterdocs_archive_content_padding_top',
			'padding-right'  => 'betterdocs_archive_content_padding_right',
			'padding-bottom' => 'betterdocs_archive_content_padding_bottom',
			'padding-left'   => 'betterdocs_archive_content_padding_left',
			'border-radius'  => 'betterdocs_archive_content_border_radius'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-category-listing .docs-cat-title .docs-cat-heading',
	$css->properties(
		[
			'color' => 'betterdocs_archive_title_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-category-listing .docs-cat-title .docs-cat-heading',
	$css->properties(
		[
			'font-size'     => 'betterdocs_archive_title_font_size',
			'margin-top'    => 'betterdocs_archive_title_margin_top',
			'margin-right'  => 'betterdocs_archive_title_margin_right',
			'margin-bottom' => 'betterdocs_archive_title_margin_bottom',
			'margin-left'   => 'betterdocs_archive_title_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-category-listing .docs-cat-title p',
	$css->properties(
		[
			'color' => 'betterdocs_archive_description_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-category-listing .docs-cat-title p',
	$css->properties(
		[
			'font-size'     => 'betterdocs_archive_description_font_size',
			'margin-top'    => 'betterdocs_archive_description_margin_top',
			'margin-right'  => 'betterdocs_archive_description_margin_right',
			'margin-bottom' => 'betterdocs_archive_description_margin_bottom',
			'margin-left'   => 'betterdocs_archive_description_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list ul li, .betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat-title',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_archive_article_list_margin_top',
			'margin-right'  => 'betterdocs_archive_article_list_margin_right',
			'margin-bottom' => 'betterdocs_archive_article_list_margin_bottom',
			'margin-left'   => 'betterdocs_archive_article_list_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list ul li svg',
	$css->properties(
		[
			'fill' => 'betterdocs_archive_list_icon_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list ul li svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_list_icon_font_size',
			'min-width' => 'betterdocs_archive_list_icon_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list ul li a',
	$css->properties(
		[
			'color' => 'betterdocs_archive_list_item_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list ul li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_list_item_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list ul li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_list_item_hover_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat li a',
	$css->properties(
		[
			'color' => 'betterdocs_archive_subcategory_article_list_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_subcategory_article_list_hover_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat li svg#Layer_1',
	$css->properties(
		[
			'fill' => 'betterdocs_archive_subcategory_article_list_icon_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat-title svg',
	$css->properties(
		[
			'fill' => 'betterdocs_archive_subcategory_icon_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat-title svg',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_subcategory_icon_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat-title a',
	$css->properties(
		[
			'color' => 'betterdocs_archive_article_subcategory_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat-title a',
	$css->properties(
		[
			'font-size' => 'betterdocs_archive_article_subcategory_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-wraper .docs-listing-main .docs-category-listing .docs-list .docs-sub-cat-title a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_archive_article_subcategory_hover_color'
		]
	)
);

//Live Search Start

$css->add_rule(
	'.betterdocs-wrapper .betterdocs-search-form-wrapper:not(.betterdocs-elementor)',
	$css->properties(
		[
			'background-color' => 'betterdocs_live_search_background_color',
			'background-image' => [
				'id'         => 'betterdocs_live_search_background_image',
				'properties' => [
					'background-repeat'     => 'betterdocs_live_search_background_repeat',
					'background-attachment' => 'betterdocs_live_search_background_attachment',
					'background-position'   => 'betterdocs_live_search_background_position'
				]
			]
		]
	)
);

$css->add_rule(
	'.betterdocs-wrapper .betterdocs-search-form-wrapper:not(.betterdocs-elementor)',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_live_search_padding_top',
			'padding-right'  => 'betterdocs_live_search_padding_right',
			'padding-bottom' => 'betterdocs_live_search_padding_bottom',
			'padding-left'   => 'betterdocs_live_search_padding_left',
			'margin-top'     => 'betterdocs_live_search_margin_top',
			'margin-right'   => 'betterdocs_live_search_margin_right',
			'margin-bottom'  => 'betterdocs_live_search_margin_bottom',
			'margin-left'    => 'betterdocs_live_search_margin_left'
		],
		'px'
	)
);

if ( $mods['betterdocs_live_search_custom_background_switch'] ) {
	$css->add_rule(
		'.betterdocs-wrapper .betterdocs-search-form-wrapper:not(.betterdocs-elementor)',
		$css->properties(
			[
				'background-size' => '%betterdocs_live_search_custom_background_width%% %betterdocs_live_search_custom_background_height%%'
			]
		)
	);
} elseif ( $mods['betterdocs_live_search_background_size'] ) {
	$css->add_rule(
		'.betterdocs-wrapper .betterdocs-search-form-wrapper:not(.betterdocs-elementor)',
		$css->properties(
			[
				'background-size' => 'betterdocs_live_search_background_size'
			]
		)
	);
}

$css->add_rule(
	'.betterdocs-search-heading h2.heading, .betterdocs-search-heading h1.heading, .betterdocs-search-heading h3.heading, .betterdocs-search-heading h4.heading, .betterdocs-search-heading h5.heading, .betterdocs-search-heading h6.heading, .betterdocs-search-heading p.heading',
	$css->properties(
		[
			'line-height' => '1.2',
			'color'       => 'betterdocs_live_search_heading_font_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-heading h2.heading, .betterdocs-search-heading h1.heading, .betterdocs-search-heading h3.heading, .betterdocs-search-heading h4.heading, .betterdocs-search-heading h5.heading, .betterdocs-search-heading h6.heading, .betterdocs-search-heading p.heading',
	$css->properties(
		[
			'font-size'     => 'betterdocs_live_search_heading_font_size',
			'margin-top'    => 'betterdocs_search_heading_margin_top',
			'margin-right'  => 'betterdocs_search_heading_margin_right',
			'margin-bottom' => 'betterdocs_search_heading_margin_bottom',
			'margin-left'   => 'betterdocs_search_heading_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-heading h3.subheading, .betterdocs-search-heading h2.subheading, .betterdocs-search-heading h1.subheading, .betterdocs-search-heading h4.subheading, .betterdocs-search-heading h5.subheading, .betterdocs-search-heading h6.subheading, .betterdocs-search-heading p.subheading',
	$css->properties(
		[
			'line-height' => '1.2',
			'color'       => 'betterdocs_live_search_subheading_font_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-heading h3.subheading, .betterdocs-search-heading h2.subheading, .betterdocs-search-heading h1.subheading, .betterdocs-search-heading h4.subheading, .betterdocs-search-heading h5.subheading, .betterdocs-search-heading h6.subheading, .betterdocs-search-heading p.subheading',
	$css->properties(
		[
			'font-size'     => 'betterdocs_live_search_subheading_font_size',
			'margin-top'    => 'betterdocs_search_subheading_margin_top',
			'margin-right'  => 'betterdocs_search_subheading_margin_right',
			'margin-bottom' => 'betterdocs_search_subheading_margin_bottom',
			'margin-left'   => 'betterdocs_search_subheading_margin_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform',
	$css->properties(
		[
			'background-color' => 'betterdocs_search_field_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform',
	$css->properties(
		[
			'border-radius'  => 'betterdocs_search_field_border_radius',
			'padding-top'    => 'betterdocs_search_field_padding_top',
			'padding-right'  => 'betterdocs_search_field_padding_right',
			'padding-bottom' => 'betterdocs_search_field_padding_bottom',
			'padding-left'   => 'betterdocs_search_field_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-field',
	$css->properties(
		[
			'font-size' => 'betterdocs_search_field_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-field',
	$css->properties(
		[
			'color' => 'betterdocs_search_field_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-field:focus',
	$css->properties(
		[
			'color' => 'betterdocs_search_field_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-field::placeholder',
	$css->properties(
		[
			'color' => 'betterdocs_search_placeholder_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform svg.docs-search-icon',
	$css->properties(
		[
			'fill' => 'betterdocs_search_icon_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform svg.docs-search-icon',
	$css->properties(
		[
			'height' => 'betterdocs_search_icon_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .docs-search-close path.close-line',
	$css->properties(
		[
			'fill' => 'betterdocs_search_close_icon_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .docs-search-close path.close-border',
	$css->properties(
		[
			'fill' => 'betterdocs_search_close_icon_border_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .docs-search-loader',
	$css->properties(
		[
			'stroke' => 'betterdocs_search_close_icon_border_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform svg.docs-search-icon:hover',
	$css->properties(
		[
			'fill' => 'betterdocs_search_icon_hover_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .docs-search-result',
	$css->properties(
		[
			'width' => 'betterdocs_search_result_width'
		],
		'%'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .docs-search-result',
	$css->properties(
		[
			'max-width' => 'betterdocs_search_result_max_width'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .docs-search-result',
	$css->properties(
		[
			'background-color' => 'betterdocs_search_result_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .docs-search-result',
	$css->properties(
		[
			'border-color' => 'betterdocs_search_result_border_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-result-wrap::before',
	$css->properties(
		[
			'border-color' => 'transparent transparent %betterdocs_search_result_background_color%'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li',
	$css->properties(
		[
			'border-color' => 'betterdocs_search_result_item_border_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li a',
	$css->properties(
		[
			'font-size'      => 'betterdocs_search_result_item_font_size',
			'padding-top'    => 'betterdocs_search_result_item_padding_top',
			'padding-right'  => 'betterdocs_search_result_item_padding_right',
			'padding-bottom' => 'betterdocs_search_result_item_padding_bottom',
			'padding-left'   => 'betterdocs_search_result_item_padding_left'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li',
	$css->properties(
		[
			'font-size' => 'betterdocs_search_result_item_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li a .betterdocs-search-title',
	$css->properties(
		[
			'color' => 'betterdocs_search_result_item_font_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li a .betterdocs-search-category',
	$css->properties(
		[
			'color' => 'betterdocs_search_result_item_cat_font_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_search_result_item_hover_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-live-search .betterdocs-searchform .betterdocs-search-result-wrap .docs-search-result li a span:hover',
	$css->properties(
		[
			'color' => 'betterdocs_search_result_item_hover_font_color'
		]
	)
);

//Live Search End
/**
 * For Docs Layout 4 Search Padding Bottom
 */
$css->add_rule(
	'.betterdocs-docs-archive-wrapper.betterdocs-category-layout-4 .betterdocs-search-form-wrapper:not(.betterdocs-elementor)',
	$css->properties(
		[
			'padding-bottom' => 'calc(%betterdocs_live_search_padding_bottom%px + 80px)'
		]
	)
);

//FAQ Common Controls
$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper .betterdocs-faq-section-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_title_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper .betterdocs-faq-section-title',
	$css->properties(
		[
			'color' => 'betterdocs_faq_title_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper .betterdocs-faq-section-title, .betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-section-title',
	$css->properties(
		[
			'margin' => 'betterdocs_faq_title_margin'
		],
		'px'
	)
);

//Breadcrumb Layout 2 Controls Start
$css->add_rule(
	'#betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 ul.betterdocs-breadcrumb-list .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'font-size' => 'betterdocs_single_doc_breadcrumbs_font_size_layout_8_9'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 ul.betterdocs-breadcrumb-list .betterdocs-breadcrumb-item a',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'#betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 ul.betterdocs-breadcrumb-list .betterdocs-breadcrumb-item .icon-container svg path',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_speretor_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'#betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 ul.betterdocs-breadcrumb-list .betterdocs-breadcrumb-item a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_hover_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'#betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-list .betterdocs-breadcrumb-item.current span',
	$css->properties(
		[
			'color' => 'betterdocs_single_doc_breadcrumb_active_item_color_layout_8_9'
		]
	)
);

$css->add_rule(
	'#betterdocs-breadcrumb.betterdocs-breadcrumb.layout-2 .betterdocs-breadcrumb-list',
	$css->properties(
		[
			'background' => 'betterdocs_single_doc_breadcrumb_background_color_layout_8_9'
		]
	)
);
//Breadcrumb Layout 2 Controls End

/**
 * FAQ Layout 1 Customizer CSS
 */
$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_faq_category_title_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_category_name_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_category_name_padding'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post',
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_list_padding'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_content_background_color',
			'color'            => 'betterdocs_faq_list_content_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-1 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_content_font_size'
		],
		'px'
	)
);

/**
 * Product FAQ for WooCommerce Customizer CSS
 *
 * Targets the product-page FAQ wrapper (`.betterdocs-woo-product-faq`), which
 * reuses the shared faq parts, so a single control set styles the Group Title
 * + FAQ List for whichever layout (1–3) the product FAQ uses.
 *
 * Note on specificity: the static front-end faq.css is enqueued during product
 * render and printed in the footer — i.e. AFTER this inline <style> — and its
 * layout-scoped rules reach (0,7,1). To win regardless of source order each
 * selector includes the product layout class (`.betterdocs-faq-layout-{1,2,3}`)
 * so it out-specifies the static rule rather than relying on cascade order.
 */
$woo_faq_selector = function ( $suffix ) {
	$prefixes = [
		'.betterdocs-faq-wrapper.betterdocs-woo-product-faq.betterdocs-faq-layout-1',
		'.betterdocs-faq-wrapper.betterdocs-woo-product-faq.betterdocs-faq-layout-2',
		'.betterdocs-faq-wrapper.betterdocs-woo-product-faq.betterdocs-faq-layout-3'
	];

	return implode(
		', ',
		array_map(
			function ( $prefix ) use ( $suffix ) {
				return $prefix . ' ' . $suffix;
			},
			$prefixes
		)
	);
};

// Group Title.
$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag' ),
	$css->properties(
		[
			'color' => 'betterdocs_faq_category_title_color_woo'
		]
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag' ),
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_category_name_font_size_woo'
		],
		'px'
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag' ),
	$css->properties(
		[
			'padding' => 'betterdocs_faq_category_name_padding_woo'
		],
		'px'
	)
);

// FAQ List — question row.
$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name' ),
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_color_woo'
		]
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name' ),
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_font_size_woo'
		],
		'px'
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post' ),
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_background_color_woo'
		]
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post' ),
	$css->properties(
		[
			'padding' => 'betterdocs_faq_list_padding_woo'
		],
		'px'
	)
);

// FAQ List — answer content.
$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content' ),
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_content_background_color_woo',
			'color'            => 'betterdocs_faq_list_content_color_woo'
		]
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content' ),
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_content_font_size_woo'
		],
		'px'
	)
);

// FAQ Group wrapper (the accordion item). border-style is emitted as a literal
// so the border renders on every product layout once a width is set (the static
// border is layout-3 only); width/padding/margin default to 0.
$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group' ),
	$css->properties(
		[
			'border-width' => 'betterdocs_faq_group_border_width_woo'
		],
		'px'
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group' ),
	$css->properties(
		[
			'border-style' => 'solid'
		]
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group' ),
	$css->properties(
		[
			'border-color' => 'betterdocs_faq_group_border_color_woo'
		]
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group' ),
	$css->properties(
		[
			'padding' => 'betterdocs_faq_group_padding_woo'
		],
		'px'
	)
);

$css->add_rule(
	$woo_faq_selector( '.betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group' ),
	$css->properties(
		[
			'margin' => 'betterdocs_faq_group_margin_woo'
		],
		'px'
	)
);

/**
 * FAQ Layout 2 Customizer CSS
 */

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_faq_category_title_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_category_name_font_size_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_category_name_padding_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_font_size_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group',
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_background_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_content_background_color_layout_2',
			'color'            => 'betterdocs_faq_list_content_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_content_font_size_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-2 .betterdocs-faq-inner-wrapper .betterdocs-faq-list > li .betterdocs-faq-group.active .betterdocs-faq-post',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_list_padding_layout_2'
		],
		'px'
	)
);

//est reading time bg color
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time',
	$css->properties(
		[
			'background-color' => 'betterdocs_doc_single_content_est_reading_bg_color'
		]
	)
);

//est reading time color
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time p',
	$css->properties(
		[
			'color' => 'betterdocs_doc_single_content_est_reading_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time p svg path',
	$css->properties(
		[
			'fill' => 'betterdocs_doc_single_content_est_reading_icon_color'
		]
	)
);

//est reading font-size
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time p',
	$css->properties(
		[
			'font-size' => 'betterdocs_doc_single_content_est_reading_font_size'
		],
		'px'
	)
);

//est reading font-size
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time p svg',
	$css->properties(
		[
			'width' => 'betterdocs_doc_single_content_est_reading_icon_font_size'
		],
		'px'
	)
);

//est reading time font-weight
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time p',
	$css->properties(
		[
			'font-weight' => 'betterdocs_doc_single_content_est_reading_font_weight'
		]
	)
);

//est reading time margin top, right, bottom, left
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_doc_single_content_est_reading_margin_top',
			'margin-right'  => 'betterdocs_doc_single_content_est_reading_margin_right',
			'margin-bottom' => 'betterdocs_doc_single_content_est_reading_margin_bottom',
			'margin-left'   => 'betterdocs_doc_single_content_est_reading_margin_left'
		],
		'px'
	)
);

//est reading time padding top, right, bottom, left
$css->add_rule(
	'.betterdocs-content-area .betterdocs.reading-time',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_doc_single_content_est_reading_padding_top',
			'padding-right'  => 'betterdocs_doc_single_content_est_reading_padding_right',
			'padding-bottom' => 'betterdocs_doc_single_content_est_reading_padding_bottom',
			'padding-left'   => 'betterdocs_doc_single_content_est_reading_padding_left'
		],
		'px'
	)
);

//search modal customizer styles start
$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details, .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list',
	$css->properties(
		[
			'background-color' => 'modal_wrapper_background_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details',
	$css->properties(
		[
			'padding' => 'modal_wrapper_padding',
			'margin'  => 'modal_wrapper_margin'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header',
	$css->properties(
		[
			'background-color' => 'search_field_modal_background_color',
			'color'            => 'search_field_modal_text_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field',
	$css->properties(
		[
			'color' => 'search_field_modal_text_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field',
	$css->properties(
		[
			'padding'   => 'search_field_modal_padding',
			'margin'    => 'search_field_modal_margin',
			'font-size' => 'search_field_modal_text_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper',
	$css->properties(
		[
			'background-color' => 'search_field_categories_background_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper .betterdocs-form-select',
	$css->properties(
		[
			'color' => 'search_field_categories_text_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper .betterdocs-form-select',
	$css->properties(
		[
			'font-size' => 'search_field_categories_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span',
	$css->properties(
		[
			'color' => 'search_modal_content_tabs_text_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab',
	$css->properties(
		[
			'background-color' => 'search_modal_content_tabs_background_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span',
	$css->properties(
		[
			'font-size' => 'search_modal_content_tabs_text_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .doc-search-icon',
	$css->properties(
		[
			'width' => 'search_field_modal_maginifier_icon_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab',
	$css->properties(
		[
			'margin'       => 'search_modal_content_tabs_margin',
			'padding'      => 'search_modal_content_tabs_padding',
			'border-width' => 'search_modal_content_tabs_border'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab',
	$css->properties(
		[
			'border-color' => 'search_modal_content_tabs_border_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main h4',
	$css->properties(
		[
			'font-size' => 'search_modal_content_tabs_docs_list_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main h4',
	$css->properties(
		[
			'color' => 'search_modal_content_tabs_docs_list_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list',
	$css->properties(
		[
			'background-color' => 'search_modal_content_tabs_docs_list_background_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list:hover',
	$css->properties(
		[
			'background-color' => 'search_modal_content_tabs_docs_list_background_color_hover'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list',
	$css->properties(
		[
			'padding' => 'search_modal_content_tabs_docs_list_padding',
			'margin'  => 'search_modal_content_tabs_docs_list_margin'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main svg',
	$css->properties(
		[
			'width' => 'search_modal_content_tabs_docs_list_icon_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub h5',
	$css->properties(
		[
			'font-size' => 'search_modal_content_tabs_docs_list_category_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub h5',
	$css->properties(
		[
			'color' => 'search_modal_content_tabs_docs_list_category_color'
		]
	)
);

$css->add_rule(
	'#betterdocs-search-modal .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub svg',
	$css->properties(
		[
			'height' => 'search_modal_content_tabs_docs_list_category_icon_size',
			'width'  => 'search_modal_content_tabs_docs_list_category_icon_size'
		],
		'px'
	)
);

//search modal customizer styles end

//search layout 2 styles
$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-header .search-heading',
	$css->properties(
		[
			'color' => 'betterdocs_live_search_heading_font_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-header .search-heading',
	$css->properties(
		[
			'font-size' => 'betterdocs_live_search_heading_font_size_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-header .search-heading',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_search_heading_margin_top_layout_2',
			'margin-right'  => 'betterdocs_search_heading_margin_right_layout_2',
			'margin-bottom' => 'betterdocs_search_heading_margin_bottom_layout_2',
			'margin-left'   => 'betterdocs_search_heading_margin_left_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-header search-subheading',
	$css->properties(
		[
			'color' => 'betterdocs_live_search_subheading_font_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-header search-subheading',
	$css->properties(
		[
			'font-size' => 'betterdocs_live_search_subheading_font_size_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-header search-subheading',
	$css->properties(
		[
			'margin-top'    => 'betterdocs_search_subheading_margin_top_layout_2',
			'margin-right'  => 'betterdocs_search_subheading_margin_right_layout_2',
			'margin-bottom' => 'betterdocs_search_subheading_margin_bottom_layout_2',
			'margin-left'   => 'betterdocs_search_subheading_margin_left_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1',
	$css->properties(
		[
			'background-color' => 'betterdocs_live_search_background_color_layout_2',
			'background-image' => [
				'id'         => 'background_live_search_image_layout_2',
				'properties' => [
					'background-size'       => 'betterdocs_live_search_background_size_layout_2',
					'background-repeat'     => 'betterdocs_live_search_background_repeat_layout_2',
					'background-attachment' => 'betterdocs_live_search_background_attachment_layout_2',
					'background-position'   => 'betterdocs_live_search_background_position_layout_2'
				]
			]
		]
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1',
	$css->properties(
		[
			'margin-top'     => 'betterdocs_live_search_margin_top_layout_2',
			'margin-bottom'  => 'betterdocs_live_search_margin_bottom_layout_2',
			'padding-top'    => 'betterdocs_live_search_padding_top_layout_2',
			'padding-right'  => 'betterdocs_live_search_padding_right_layout_2',
			'padding-bottom' => 'betterdocs_live_search_padding_bottom_layout_2',
			'padding-left'   => 'betterdocs_live_search_padding_left_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-bar',
	$css->properties(
		[
			'background-color' => 'betterdocs_search_field_background_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-search-layout-1 .search-bar .search-input-wrapper .search-input',
	$css->properties(
		[
			'color' => 'betterdocs_search_placeholder_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-bar',
	$css->properties(
		[
			'padding-top'    => 'betterdocs_search_field_padding_top_layout_2',
			'padding-right'  => 'betterdocs_search_field_padding_right_layout_2',
			'padding-bottom' => 'betterdocs_search_field_padding_bottom_layout_2',
			'padding-left'   => 'betterdocs_search_field_padding_left_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-bar .search-button',
	$css->properties(
		[
			'font-size' => 'betterdocs_new_search_button_font_size_layout_2'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-bar .search-button',
	$css->properties(
		[
			'font-weight'      => 'betterdocs_new_search_button_font_weight_layout_2',
			'text-transform'   => 'betterdocs_new_search_button_text_transform_layout_2',
			'color'            => 'betterdocs_search_button_text_color_layout_2',
			'background-color' => 'betterdocs_search_button_background_color_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-bar .search-button:hover',
	$css->properties(
		[
			'background-color' => 'betterdocs_search_button_background_color_hover_layout_2'
		]
	)
);

$css->add_rule(
	'.betterdocs-search-modal-layout-1 .betterdocs-search-layout-1 .search-bar .search-button',
	$css->properties(
		[
			'border-top-left-radius'     => 'betterdocs_search_button_borderr_left_top_layout_2',
			'border-top-right-radius'    => 'betterdocs_search_button_borderr_right_top_layout_2',
			'border-bottom-right-radius' => 'betterdocs_search_button_borderr_right_bottom_layout_2',
			'border-bottom-left-radius'  => 'betterdocs_search_button_borderr_left_bottom_layout_2',
			'padding-top'                => 'betterdocs_search_button_padding_top_layout_2',
			'padding-right'              => 'betterdocs_search_button_padding_right_layout_2',
			'padding-bottom'             => 'betterdocs_search_button_padding_bottom_layout_2',
			'padding-left'               => 'betterdocs_search_button_padding_left_layout_2'
		],
		'px'
	)
);

//FAQ LAYOUT 3 Controls Start
$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'color' => 'betterdocs_faq_category_title_color_layout_3'
		]
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_category_name_font_size_layout_3'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-title .betterdocs-faq-title-tag',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_category_name_padding_layout_3'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_color_layout_3'
		]
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group',
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_background_color_layout_3'
		]
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'background-color' => 'betterdocs_faq_list_content_background_color_layout_3'
		]
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_content_color_layout_3'
		]
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_content_font_size_layout_3'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_font_size_layout_3'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-faq-wrapper.betterdocs-faq-layout-3.layout-layout-3 .betterdocs-faq-inner-wrapper .betterdocs-faq-list>li .betterdocs-faq-group',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_list_padding_layout_3'
		],
		'px'
	)
);
//FAQ LAYOUT 3 Controls End

//FAQ LAYOUT 4 Controls Start

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-title',
	$css->properties(
		[
			'color' => 'betterdocs_faq_category_title_color_layout_4'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab.active .faq-tab-title',
	$css->properties(
		[
			'color' => 'betterdocs_faq_category_title_active_color_layout_4'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab .faq-tab-title',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_category_name_font_size_layout_4'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_category_name_padding_layout_4'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_color_layout_4'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group.active',
	$css->properties(
		[
			'background' => 'betterdocs_faq_list_content_background_color_layout_4'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'color' => 'betterdocs_faq_list_content_color_layout_4'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-main-content',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_content_font_size_layout_4'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group .betterdocs-faq-post .betterdocs-faq-post-name',
	$css->properties(
		[
			'font-size' => 'betterdocs_faq_list_font_size_layout_4'
		],
		'px'
	)
);

$css->add_rule(
	'',
	$css->properties(
		[
			'' => ''
		],
		''
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group',
	$css->properties(
		[
			'background' => 'betterdocs_faq_list_background_color_layout_4'
		]
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-list-wrapper .betterdocs-faq-list-content .betterdocs-faq-list li .betterdocs-faq-group',
	$css->properties(
		[
			'padding' => 'betterdocs_faq_list_padding_layout_4'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-docs-archive-wrapper .betterdocs-faq-wrapper.betterdocs-faq-layout-4 .betterdocs-faq-inner-wrapper .betterdocs-faq-tab-wrapper .betterdocs-faq-tab.active',
	$css->properties(
		[
			'background' => 'betterdocs_faq_category_active_title_background_color_layout_4'
		]
	)
);

//FAQ LAYOUT 4 Controls End

//Archive Page Pagination Controls
$css->add_rule(
	'.betterdocs-category-archive-wrapper .betterdocs-pagination ul li a',
	$css->properties(
		[
			'height'    => 'archive_docs_pagination_wrapper_height',
			'width'     => 'archive_docs_pagination_wrapper_width',
			'font-size' => 'archive_docs_pagination_wrapper_font_size'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper .betterdocs-pagination ul li a',
	$css->properties(
		[
			'color' => 'archive_docs_pagination_wrapper_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper .doc-category-layout-7 .betterdocs-pagination ul li a, .betterdocs-category-archive-wrapper .doc-category-layout-8 .betterdocs-pagination ul li a',
	$css->properties(
		[
			'background-color' => 'archive_docs_pagination_wrapper_background_color_sleek'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper .betterdocs-pagination ul li a',
	$css->properties(
		[
			'background-color' => 'archive_docs_pagination_wrapper_background_color'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-archive-wrapper .betterdocs-pagination ul li.active a',
	$css->properties(
		[
			'color'        => 'archive_docs_pagination_wrapper_active_color',
			'border-color' => 'archive_docs_pagination_wrapper_active_border_color'
		]
	)
);

// Layout-8 specific CSS rules
$css->add_rule(
	'.betterdocs-category-layout-8 .betterdocs-category-grid-three-wrapper .layout-flex',
	$css->properties(
		[
			'gap' => 'betterdocs_docs_page_column_gap_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-grid-inner-wrapper .betterdocs-category-icon .betterdocs-folder-icon',
	$css->properties(
		[
			'width' => 'betterdocs_docs_page_icon_size_layout_8',
			'height' => 'betterdocs_docs_page_icon_size_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-grid-three-wrapper .betterdocs-category-title, .betterdocs-category-grid-three-wrapper .betterdocs-category-title a',
	$css->properties(
		[
			'font-size' => 'betterdocs_docs_page_category_title_font_size_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a',
	$css->properties(
		[
			'color' => 'betterdocs_docs_page_docs_list_item_color_layout_8',
		]
	)
);

$css->add_rule(
	'.betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a',
	$css->properties(
		[
			'font-size' => 'betterdocs_docs_page_docs_list_item_font_size_layout_8'
		],
		'px'
	)
);

$css->add_rule(
	'.betterdocs-category-grid-three-wrapper .betterdocs-articles-list li a:hover',
	$css->properties(
		[
			'color' => 'betterdocs_docs_page_docs_list_item_hover_color_layout_8'
		]
	)
);

$css->add_rule(
	'.betterdocs-category-grid-three-wrapper .betterdocs-articles-list li',
	$css->properties(
		[
			'margin'    => 'betterdocs_docs_page_docs_list_item_margin_layout_8'
		],
		'px'
	)
);
