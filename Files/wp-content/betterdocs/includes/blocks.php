<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\FAQ;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\ToC;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\DocDate;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\Sidebar;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\Reactions;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\DocContent;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\Navigation;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\SearchForm;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\ArchiveList;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\Breadcrumbs;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CategoryBox;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\ReadingTime;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\SocialShare;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CategoryGrid;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\FeedbackForm;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\ArticleSummary;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\ArchiveHeader;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\BetterdocsPrint;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\DocAuthor;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\DocsTag;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CodeSnippet;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CodeSnippetTab;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CategorySlateLayout;

return [
	'categorygrid'      => [
		'label'      => __( 'BetterDocs Category Grid', 'betterdocs' ),
		'value'      => 'categorygrid',
		'visibility' => true,
		'object'     => CategoryGrid::class,
		'demo'       => '',
		'docs'       => ''
	],
	'categorybox'       => [
		'label'      => __( 'BetterDocs Category Box', 'betterdocs' ),
		'value'      => 'categorybox',
		'visibility' => true,
		'object'     => CategoryBox::class,
		'demo'       => '',
		'docs'       => ''
	],
	'category-slate-layout' => [
		'label'      => __( 'BetterDocs Slate Layout', 'betterdocs' ),
		'value'      => 'category-slate-layout',
		'visibility' => true,
		'object'     => CategorySlateLayout::class,
		'demo'       => '',
		'docs'       => ''
	],
	'searchform'        => [
		'label'      => __( 'BetterDocs Search Form', 'betterdocs' ),
		'value'      => 'searchform',
		'visibility' => true,
		'object'     => SearchForm::class,
		'demo'       => '',
		'docs'       => ''
	],
	'breadcrumbs'       => [
		'label'      => __( 'BetterDocs Breadcrumb', 'betterdocs' ),
		'value'      => 'breadcrumbs',
		'visibility' => true,
		'object'     => Breadcrumbs::class,
		'demo'       => '',
		'docs'       => ''
	],
	'archive-header'    => [
		'label'      => __( 'BetterDocs Archive Header', 'betterdocs' ),
		'value'      => 'archive-header',
		'visibility' => true,
		'object'     => ArchiveHeader::class,
		'demo'       => '',
		'docs'       => ''
	],
	'doc-archive-list'  => [
		'label'      => __( 'Doc Archive List', 'betterdocs' ),
		'value'      => 'doc-archive-list',
		'visibility' => true,
		'object'     => ArchiveList::class,
		'demo'       => '',
		'docs'       => ''
	],
	'doc-content'       => [
		'label'      => __( 'Doc Content', 'betterdocs' ),
		'value'      => 'doc-content',
		'visibility' => true,
		'object'     => DocContent::class,
		'demo'       => '',
		'docs'       => ''
	],
	'doc-date'          => [
		'label'      => __( 'Doc Date', 'betterdocs' ),
		'value'      => 'doc-date',
		'visibility' => true,
		'object'     => DocDate::class,
		'demo'       => '',
		'docs'       => ''
	],
	'doc-navigation'    => [
		'label'      => __( 'Doc Navigation', 'betterdocs' ),
		'value'      => 'doc-navigation',
		'visibility' => true,
		'object'     => Navigation::class,
		'demo'       => '',
		'docs'       => ''
	],
	'reactions'         => [
		'label'      => __( 'Reactions', 'betterdocs' ),
		'value'      => 'reactions',
		'visibility' => true,
		'object'     => Reactions::class,
		'demo'       => '',
		'docs'       => ''
	],
	'sidebar'           => [
		'label'      => __( 'Sidebar', 'betterdocs' ),
		'value'      => 'sidebar',
		'visibility' => true,
		'object'     => Sidebar::class,
		'demo'       => '',
		'docs'       => ''
	],
	'socialshare'       => [
		'label'      => __( 'Social Share', 'betterdocs' ),
		'value'      => 'socialshare',
		'visibility' => true,
		'object'     => SocialShare::class,
		'demo'       => '',
		'docs'       => ''
	],
	'table-of-contents' => [
		'label'      => __( 'Table of Contents', 'betterdocs' ),
		'value'      => 'table-of-contents',
		'visibility' => true,
		'object'     => ToC::class,
		'demo'       => '',
		'docs'       => ''
	],
	'article-summary'   => [
		'label'      => __( 'AI Doc Summarizer', 'betterdocs' ),
		'value'      => 'article-summary',
		'visibility' => true,
		'object'     => ArticleSummary::class,
		'demo'       => '',
		'docs'       => ''
	],
	'feedback-form'     => [
		'label'      => __( 'Feedback Form', 'betterdocs' ),
		'value'      => 'feedback-form',
		'visibility' => true,
		'object'     => FeedbackForm::class,
		'demo'       => '',
		'docs'       => ''
	],
	'betterdocs-print'  => [
		'label'      => __( 'Betterdocs Print', 'betterdocs' ),
		'value'      => 'betterdocs-print',
		'visibility' => true,
		'object'     => BetterdocsPrint::class,
		'demo'       => '',
		'docs'       => ''
	],
	'faq'               => [
		'label'      => __( 'BetterDocs FAQ', 'betterdocs' ),
		'value'      => 'faq',
		'visibility' => true,
		'object'     => FAQ::class,
		'demo'       => '',
		'docs'       => ''
	],
	'reading-time'      => [
		'label'      => __( 'Reading Time', 'betterdocs' ),
		'value'      => 'reading-time',
		'visibility' => true,
		'object'     => ReadingTime::class,
		'demo'       => '',
		'docs'       => ''
	],
	'doc-author'        => [
		'label'      => __( 'Doc Author', 'betterdocs' ),
		'value'      => 'doc-author',
		'visibility' => true,
		'object'     => DocAuthor::class,
		'demo'       => '',
		'docs'       => ''
	],
	'docs-tag'          => [
		'label'      => __( 'Docs Tag', 'betterdocs' ),
		'value'      => 'docs-tag',
		'visibility' => true,
		'object'     => DocsTag::class,
		'demo'       => '',
		'docs'       => ''
	],
	'code-snippet'      => [
		'label'      => __( 'BetterDocs Code Snippet', 'betterdocs' ),
		'value'      => 'code-snippet',
		'visibility' => true,
		'object'     => CodeSnippet::class,
		'demo'       => '',
		'docs'       => ''
	],
	'code-snippet-tab'  => [
		'label'      => __( 'BetterDocs Code Snippet Tab', 'betterdocs' ),
		'value'      => 'code-snippet-tab',
		'visibility' => true,
		'object'     => CodeSnippetTab::class,
		'demo'       => '',
		'docs'       => ''
	]
];
