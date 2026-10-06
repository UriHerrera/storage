<?php

namespace WPDeveloper\BetterDocsPro\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPDeveloper\BetterDocs\Shortcodes\SearchModal;

if ( class_exists( 'WPDeveloper\BetterDocs\Shortcodes\SearchModal' ) ) {
    class ExtendSearchModal extends SearchModal {

        protected $is_pro = true;

        /**
         * Get default attributes, including extended attributes.
         *
         * @return array
         */
        public function default_attributes() {
            // Get parent default attributes
            $attributes = parent::default_attributes();

            // Extend with additional attributes
            return $this->extend_default_attributes($attributes);
        }

        /**
         * Extend the default attributes or modify existing ones.
         *
         * @param array $attributes
         * @return array
         */
        public function extend_default_attributes( $attributes ) {
            // Add or modify default attributes.
            $additional_attributes = [
                'category_search'      => true,
                'popular_search'       => true,
                'search_button'        => true,
                'popular_search_title' => __( 'Popular Search', 'betterdocs-pro' ),
            ];

            return array_merge($attributes, $additional_attributes);
        }

        /**
         * Register script dependencies for the extended modal.
         *
         * @return array
         */
        public function get_script_depends() {
            return ['betterdocs-search-modal', 'betterdocs-extend-search-modal'];
        }

        /**
         * Render the extended search modal.
         *
         * @param mixed $atts
         * @param mixed $content
         * @return void
         */
        public function render( $atts, $content = null ) {
            // Apply the filter to modify the attributes before rendering
            add_filter( 'betterdocs_search_modal_shortcode_attributes', [$this, 'shortcode_attributes'], 10, 1 );

            parent::render( $atts, $content );
        }

        public function shortcode_attributes( $attributes ) {
            $additional_attributes = [
                'categorysearch' => $this->attributes['category_search'],
                'popularsearch' => $this->attributes['popular_search'],
                'searchbutton'  => $this->attributes['search_button']
            ];

            if ( $this->attributes['popular_search'] ) {
                $additional_attributes['popularsearchtitle'] = $this->attributes['popular_search_title'];
            }

            // Merge the existing attributes with the new ones
            return array_merge( $attributes, $additional_attributes );
        }
    }
} else {
    class ExtendSearchModal {
        public function get_name() {
            return 'betterdocs_search_modal';
        }
    }
}
