<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( $category_search == true && $category_search !== 'false' ) {
    // Determine KB slug for filtering categories
    $kb_slug_for_filter = '';
    if ( betterdocs()->settings->get( 'multiple_kb' ) == 1 ) {
        // Priority 1: Use shortcode attribute if provided
        if ( ! empty( $kb_based_search ) && is_string( $kb_based_search ) ) {
            $kb_slug_for_filter = $kb_based_search;
        }
        // Priority 2: Use global setting and get from cookie/context
        // BUT only in template context or on appropriate pages
        elseif ( betterdocs()->settings->get( 'kb_based_search' ) == 1 ) {
            // Check if we're in a theme template (FSE or Elementor site builder)
            $is_template_context = betterdocs()->helper->is_templates();
            
            // Also check if we're on appropriate archive pages or single docs
            $is_appropriate_page = is_tax( 'knowledge_base' ) || is_tax( 'doc_category' ) || is_singular( 'docs' );
            
            // Exclude main docs archive - should always show all categories
            $is_main_docs_archive = is_post_type_archive( 'docs' );
            
            // Only filter categories if in template context OR on appropriate pages
            // NOT when used in regular page content (Elementor widget or Block on a page)
            // NOT on main docs archive
            if ( ! $is_main_docs_archive && ( $is_template_context || $is_appropriate_page ) ) {
                $kb_slug_for_filter = betterdocs_pro()->multiple_kb->get_kb_slug();
            }
        }
    }
    
    echo '<select
        class="betterdocs-search-category"
        id="betterdocs-search-category"
        aria-label="' . esc_attr__( 'Select a category', 'betterdocs-pro' ) . '">
        <option value="">' . esc_html__( 'All Categories', 'betterdocs-pro' ) . '</option>
        ' . betterdocs()->template_helper->term_options( 'doc_category', '', betterdocs()->settings->get( 'child_category_exclude' ), $kb_slug_for_filter ) . '
    </select>';
}

if ( $search_button == true && $search_button !== 'false' ) {
    echo '<input
        class="search-submit betterdocs-search-submit"
        type="submit"
        value="' . esc_html( $search_button_text ) . '"
        aria-label="' . esc_attr__( 'Submit search', 'betterdocs-pro' ) . '">';
}

// Check if kb_based_search is provided from shortcode attribute or global setting
if ( betterdocs()->settings->get( 'multiple_kb' ) == 1 ) {
    $kb_slug = '';
    
    // Priority 1: Use shortcode attribute if provided
    if ( ! empty( $kb_based_search ) && is_string( $kb_based_search ) ) {
        $kb_slug = $kb_based_search;
    }
    // Priority 2: Use global setting and get from cookie/context
    // BUT only on appropriate pages (not on main docs archive)
    elseif ( betterdocs()->settings->get( 'kb_based_search' ) == 1 ) {
        // Only use cookie-based KB slug on:
        // - Knowledge base archives
        // - Doc category archives  
        // - Single doc pages
        // NOT on main docs archive (is_post_type_archive('docs'))
        if ( is_tax( 'knowledge_base' ) || is_tax( 'doc_category' ) || is_singular( 'docs' ) ) {
            $kb_slug = betterdocs_pro()->multiple_kb->get_kb_slug();
        }
    }
    
    // Only output the hidden input if we have a KB slug
    if ( ! empty( $kb_slug ) ) {
        echo '<input
            type="hidden"
            value="' . esc_attr( $kb_slug ) . '"
            class="betterdocs-search-kbslug betterdocs-search-submit"
            aria-hidden="true">';
    }
}

