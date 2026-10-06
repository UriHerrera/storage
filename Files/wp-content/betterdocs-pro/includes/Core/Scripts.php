<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocs\Core\Scripts as FreeScripts;

class Scripts extends FreeScripts {
    public function init() {
        $assets     = parent::init();
        $pro_assets = betterdocs_pro()->assets;

        // Shortcode CSS
        $assets->register( 'betterdocs-popular-articles', 'public/css/popular-articles.css' );
        $assets->register( 'betterdocs-related-categories', 'public/css/related-categories.css' );
        $pro_assets->register( 'archive-list-handbook', 'blocks/doc-archive-list-handbook/doc-archive-list-handbook.css' );
        $pro_assets->register( 'betterdocs-encyclopedia', 'public/css/encyclopedia.css' );
        $pro_assets->register( 'betterdocs-glossaries', 'public/css/glossaries.css' );

        // Shortcode JS
        $pro_assets->register( 'betterdocs-pro-mkb-tab-grid', 'public/js/mkb-tab-grid.js', ['betterdocs-category-grid'] );
        $pro_assets->register( 'betterdocs-related-categories', 'public/js/related-categories.js' );

        //handbook css for block edit mode & frontend
        $pro_assets->register('betterdocs-handbook-block', 'blocks/handbook/handbook.css');

        $pro_assets->register( 'betterdocs-pro', 'public/js/betterdocs.js' );

        $pro_assets->register( 'single-doc-attachments', 'public/css/attachment.css' );

        $pro_assets->register( 'single-doc-related-articles', 'public/css/related-articles.css' );

        $pro_assets->register( 'betterdocs-encyclopedia', 'public/js/encyclopedia.js', [ 'jquery' ] );

        $pro_assets->register( 'betterdocs-glossaries', 'public/js/glossaries.js', [ 'jquery' ] );

        $is_enable_glossary = betterdocs()->settings->get('enable_glossaries', false);
        $is_enable_glossary_suggestion = betterdocs()->settings->get('show_glossary_suggestions');

        $is_enable_glossary_suggestion = betterdocs()->settings->get('show_glossary_suggestions', true);

        if(!empty($is_enable_glossary) && !empty($is_enable_glossary_suggestion)){
            $pro_assets->register( 'betterdocs-glossary-suggestion', 'public/js/glossary-suggestion.js', [ 'wp-editor' ] );
        }

        $pro_assets->register( 'betterdocs-extend-search-modal', 'public/js/extend-search-modal.js', [] );

        /**
         * Localize This In Order To Know If This Block Is Arriving From Betterdocs Templates Or Not
         */
        betterdocs()->assets->localize('betterdocs-pro-mkb-tab-grid', 'betterdocsCategoryGridConfig', [
            'is_betterdocs_templates' => betterdocs()->helper->is_templates() ? true : false
        ]);

        $pro_assets->localize( 'betterdocs-encyclopedia', 'betterdocsEncyclopedia', [
            'site_url'            => site_url(),
            'ajax_url'            => admin_url( 'admin-ajax.php' ),
            '_nonce'               => wp_create_nonce('encyclopedia_nonce'),
        ] );
        $pro_assets->localize( 'betterdocs-glossaries', 'betterdocsGlossary', [
            'site_url'            => site_url(),
            'ajax_url'            => admin_url( 'admin-ajax.php' ),
            '_nonce'               => wp_create_nonce('glossary_nonce'),
        ] );

    }
}
