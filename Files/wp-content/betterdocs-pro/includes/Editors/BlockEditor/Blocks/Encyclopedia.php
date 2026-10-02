<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;

class Encyclopedia extends Block {

    public $is_pro = true;

    protected $editor_scripts = array(
        'betterdocs-pro-blocks-editor',
        'betterdocs-encyclopedia',
        'betterdocs-glossary-suggestion'
    );

    protected $editor_styles = array(
        'betterdocs-fontawesome',
        'betterdocs-pro-blocks-editor',
        'betterdocs-encyclopedia'
    );

    protected $frontend_styles = array(
        'betterdocs-fontawesome',
        'betterdocs-encyclopedia'
    );

    protected $frontend_scripts = array(
        'betterdocs-encyclopedia'
    );

    /**
     * unique name of block
     * @return string
     */
    public function get_name() {
        return 'betterdocs-encyclopedia';
    }

    public function get_default_attributes() {
        return array(
            'blockId' => '',
            'docStyle' => 'doc-grid',
            'startLetterStyle' => 'alphabet-big-round-view',
            'startLetterStyle_' => 'alphabet-list-view',
            'alphabetListStyle' => 'box',
            'dictionaryLoadMore' => true,
            'dictionaryPerPage' => '5',
            'dictionaryDocsLoadMore' => true,
            'dictionaryDocsPerPage' => '10',
            'excludeAlphabets' => '',
            'itemHeadingTag' => 'h2',
            'showHeading' => false,
            'headingText' => 'Encyclopedia',
            'headingTag' => 'h2'
        );
    }

    public function render( $attributes, $content ) {
        $attributes = &$this->attributes;

        $exclude_alphabets_range = empty( $attributes[ 'excludeAlphabets' ] ) ? array() : $this->string_to_array( $attributes[ 'excludeAlphabets' ] );

        $enable_non_latin = betterdocs()->settings->get( 'encyclopedia_enable_non_latin' );
        $script           = betterdocs()->settings->get( 'encyclopedia_non_latin_option' );
        $alphabet_range   = EncyclopediaQuery::get_character_range( $enable_non_latin, $script );

        $filtered_alphabet_range = ! empty( $exclude_alphabets_range ) ? array_diff( $alphabet_range, $exclude_alphabets_range ) : array();

        $default_controls = array(
            'doc_style' => isset( $attributes[ 'docStyle' ] ) ? $attributes[ 'docStyle' ] : '',
            'start_letter_style' => isset( $attributes[ 'startLetterStyle' ] ) ? $attributes[ 'startLetterStyle' ] : '',
            'start_letter_style_' => isset( $attributes[ 'startLetterStyle_' ] ) ? $attributes[ 'startLetterStyle_' ] : '',
            'alphabet_list_style' => isset( $attributes[ 'alphabetListStyle' ] ) ? $attributes[ 'alphabetListStyle' ] : '',
            'dictionary_loadmore' => isset( $attributes[ 'dictionaryLoadMore' ] ) ? $attributes[ 'dictionaryLoadMore' ] : '',
            'dictionary_per_page' => isset( $attributes[ 'dictionaryPerPage' ] ) ? $attributes[ 'dictionaryPerPage' ] : '',
            'dictionary_loadmore_button_text' => isset( $attributes[ 'dictionaryLoadMoreText' ] ) ? $attributes[ 'dictionaryLoadMoreText' ] : 'Load More',
            'dictionary_learn_more_text' => isset( $attributes[ 'dictionaryLearnMoreText' ] ) ? $attributes[ 'dictionaryLearnMoreText' ] : 'Learn More',
            'dictionary_explore_more_text' => isset( $attributes[ 'dictionaryExploreMoreText' ] ) ? $attributes[ 'dictionaryExploreMoreText' ] : 'Explore [count] More Docs',
            'dictionary_docs_loadmore' => isset( $attributes[ 'dictionaryDocsLoadMore' ] ) ? $attributes[ 'dictionaryDocsLoadMore' ] : '',
            'dictionary_docs_per_page' => isset( $attributes[ 'dictionaryDocsPerPage' ] ) ? $attributes[ 'dictionaryDocsPerPage' ] : '',
            'explore_more_text_color' => isset( $attributes[ 'exploreMoreTextColor' ] ) ? $attributes[ 'exploreMoreTextColor' ] : '',
            'excluded_alphabets_range' => $filtered_alphabet_range,
            'item_heading_tag' => isset( $attributes[ 'itemHeadingTag' ] ) ? $attributes[ 'itemHeadingTag' ] : 'h2',
            'show_heading' => isset( $attributes[ 'showHeading' ] ) ? $attributes[ 'showHeading' ] : false,
            'heading_text' => isset( $attributes[ 'headingText' ] ) ? $attributes[ 'headingText' ] : 'Encyclopedia',
            'heading_tag' => isset( $attributes[ 'headingTag' ] ) ? $attributes[ 'headingTag' ] : 'h2',
            'is_customizer' => false
        );

        // echo do_shortcode("[betterdocs_encyclopedia $convertedString  /]");
        echo '<div class="betterdocs-blocks ' . esc_attr( $attributes[ 'blockId' ] ) . ' betterdocs-pro">';
        betterdocs_pro()->views->get( 'layouts/encyclopedia/default', $default_controls );
        echo '</div>';
    }
}
