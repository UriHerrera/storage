<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\EncyclopediaBreadcrumb;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\Attachment;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\MultipleKB;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\PopularDocs;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\AdvancedSearch;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\Handbook;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\RelatedDocs;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\PopularView;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\MultipleKBTab;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\ArchiveHandBookList;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\RelatedCategories;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\Encyclopedia;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\EncyclopediaDescription;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\GlossarySingleTemplate;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\EncyclopediaNavigation;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\ApiReference;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\ApiTryit;
use WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks\ApiCodeSamples;

add_filter( 'betterdocs_pro_blocks_config', function ( $blocks ) {
    $blocks['doc-archive-list']['object'] = ArchiveHandBookList::class;
    $blocks['searchform']['object']       = AdvancedSearch::class;
    return $blocks;
} );

return [
    'api-reference' => [
        'label'      => __( 'BetterDocs API Reference', 'betterdocs-pro' ),
        'value'      => 'api-reference',
        'visibility' => true,
        'object'     => ApiReference::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'api-tryit' => [
        'label'      => __( 'BetterDocs API Try-it', 'betterdocs-pro' ),
        'value'      => 'api-tryit',
        'visibility' => true,
        'object'     => ApiTryit::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'api-code-samples' => [
        'label'      => __( 'BetterDocs API Code Samples', 'betterdocs-pro' ),
        'value'      => 'api-code-samples',
        'visibility' => true,
        'object'     => ApiCodeSamples::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'related-docs' => [
        'label'      => __( 'BetterDocs Related Docs', 'betterdocs-pro' ),
        'value'      => 'related-docs',
        'visibility' => true,
        'object'     => RelatedDocs::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'attachment'       => [
        'label'      => __( 'BetterDocs Attachment', 'betterdocs-pro' ),
        'value'      => 'attachment',
        'visibility' => true,
        'object'     => Attachment::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'multiple-kb'     => [
        'label'      => __( 'BetterDocs Multiple KB', 'betterdocs-pro' ),
        'value'      => 'multiple-kb',
        'visibility' => true,
        'object'     => MultipleKB::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'popular-docs' => [
        'label'      => __( 'Betterdocs Popular Docs', 'betterdocs-pro' ),
        'value'      => 'popular-docs',
        'visibility' => true,
        'object'     => PopularDocs::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'multiple-kb-tab' => [
        'label'      => __( 'Betterdocs Multiple KB Tab', 'betterdocs-pro' ),
        'value'      => 'multiple-kb-tab',
        'visibility' => true,
        'object'     => MultipleKBTab::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'handbook'       => [
        'label'      => __( 'BetterDocs Category Handbook', 'betterdocs-pro' ),
        'value'      => 'handbook',
        'visibility' => true,
        'object'     => Handbook::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'related-categories' => [
        'label'      => __( 'Betterdocs Related Categories', 'betterdocs-pro' ),
        'value'      => 'related-categories',
        'visibility' => true,
        'object'     => RelatedCategories::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'betterdocs-encyclopedia' => [
        'label'      => __( 'BetterDocs Encyclopedia', 'betterdocs-pro' ),
        'value'      => 'betterdocs-encyclopedia',
        'visibility' => true,
        'object'     => Encyclopedia::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'betterdocs-encyclopedia-navigation' => [
        'label'      => __( 'BetterDocs Encyclopedia Navigation', 'betterdocs-pro' ),
        'value'      => 'betterdocs-encyclopedia-navigation',
        'visibility' => true,
        'object'     => EncyclopediaNavigation::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'encyclopedia-breadcrumb' => [
        'label'      => __( 'BetterDocs Encyclopedia Breadcrumb', 'betterdocs-pro' ),
        'value'      => 'encyclopedia-breadcrumb',
        'visibility' => true,
        'object'     => EncyclopediaBreadcrumb::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'betterdocs-encyclopedia-description' => [
        'label'      => __( 'BetterDocs Encyclopedia Description', 'betterdocs-pro' ),
        'value'      => 'betterdocs-encyclopedia-description',
        'visibility' => true,
        'object'     => EncyclopediaDescription::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'glossary-single-template' => [
        'label'      => __( 'Glossary Single Template', 'betterdocs-pro' ),
        'value'      => 'glossary-single-template',
        'visibility' => true,
        'object'     => GlossarySingleTemplate::class,
        'demo'       => '',
        'docs'       => ''
    ]

];
