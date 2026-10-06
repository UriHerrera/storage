<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class SearchForm extends Block {
	public $view_wrapper = 'betterdocs-search-form-wrapper';

	protected $editor_styles = [
		'betterdocs-search',
		'betterdocs-search-modal'
	];

	protected $frontend_styles  = [
		'betterdocs-search',
		'betterdocs-search-modal'
	];
	protected $frontend_scripts = [
		'betterdocs-search',
		'betterdocs-search-modal'
	];

	/**
	 * unique name of block
	 * @return string
	 */
	public function get_name() {
		return 'searchbox';
	}

	public function get_default_attributes() {
		return [
			'blockId'                       => '',
			'placeholderText'               => __( 'Search', 'betterdocs' ),
			'searchLayout'                  => 'layout-1',
			'searchHeading'                 => '',
			'searchSubHeading'              => '',
			'searchHeadingTag'              => 'h2',
			'searchSubHeadingTag'           => 'h3',
			'searchButtonLayout2'           => true, //for search modal — search button enabled by default
			'categorySearchLayout2'         => false, //for search modal
			'popularSearchLayout2'          => false, //for search modal,
			'initialFAQNumber'              => 5, //for search modal,
			'initialDocsNumber'             => 5, //for search modal,
			'searchModalQueryTermIds'       => '', //for search modal
			'searchModalQueryDocIds'        => '', //for search modal
			'searchModalQueriesFaqGroupIds' => ''
		];
	}

	public function render( $attributes, $content ) {
		$settings = $this->attributes;

		if ( isset( $settings['searchLayout'] ) && $settings['searchLayout'] == 'layout-1' ) {
			$this->views( 'widgets/search-form' );
		} else {
			$popular_search     = isset( $settings['popularSearchLayout2'] ) ? $settings['popularSearchLayout2'] : false;
			$category_search    = isset( $settings['categorySearchLayout2'] ) ? $settings['categorySearchLayout2'] : false;
			$search_button      = isset( $settings['searchButtonLayout2'] ) ? $settings['searchButtonLayout2'] : false;
			$number_of_docs     = isset( $settings['initialDocsNumber'] ) ? $settings['initialDocsNumber'] : 5;
			$number_of_faqs     = isset( $settings['initialFAQNumber'] ) ? $settings['initialFAQNumber'] : 5;
			$doc_categories_ids = isset( $settings['searchModalQueryTermIds'] ) ? $settings['searchModalQueryTermIds'] : '';
			$doc_ids            = isset( $settings['searchModalQueryDocIds'] ) ? $settings['searchModalQueryDocIds'] : '';
			$faq_categories_ids = isset( $settings['searchModalQueriesFaqGroupIds'] ) ? $settings['searchModalQueriesFaqGroupIds'] : '';
			$searchHeading      = isset( $settings['searchHeading'] ) ? $settings['searchHeading'] : '';
			$subHeading         = isset( $settings['searchSubHeading'] ) ? $settings['searchSubHeading'] : '';
			$search_modal_layout = isset( $settings['searchModalLayout'] ) ? $settings['searchModalLayout'] : 'layout-1';
			$search_modal_search_type = betterdocs()->settings->get('search_modal_search_type');
			$headingTag         = isset( $settings['searchHeadingTag'] ) ? $settings['searchHeadingTag'] : 'h2';
			$subHeadingTag      = isset( $settings['searchSubHeadingTag'] ) ? $settings['searchSubHeadingTag'] : 'h3';
			$search_modal_search_type = betterdocs()->settings->get('search_modal_search_type');

			// Add AI Search Suggestions parameter
			$enable_ai_suggestions = isset( $settings['enable_ai_powered_search'] ) ? $settings['enable_ai_powered_search'] : false;
			echo '<div class="' . esc_attr( $settings['blockId'] ) . '">';
			echo do_shortcode( '[betterdocs_search_modal enable_docs_search="'.($search_modal_search_type == 'all' || $search_modal_search_type == 'docs' ? true : false).'" enable_faq_search="'.($search_modal_search_type == 'all' || $search_modal_search_type == 'faq' ? true : false).'" faq_categories_ids="' . esc_attr( $faq_categories_ids ). '" doc_ids="' . esc_attr( $doc_ids ) . '" doc_categories_ids="' . esc_attr( $doc_categories_ids ) . '" number_of_docs="' . esc_attr( $number_of_docs ) . '" number_of_faqs="' . esc_attr( $number_of_faqs ) . '" search_button_text="Search" search_button="' . esc_attr( $search_button ) . '" popular_search="' . esc_attr( $popular_search ) . '" category_search="' . esc_attr( $category_search ) . '" layout="' . esc_attr( $search_modal_layout ) . '" heading="' . esc_attr( $searchHeading ) . '" subheading="' . esc_attr( $subHeading ) . '" heading_tag="' . esc_attr( $headingTag ) . '" subheading_tag="' . esc_attr( $subHeadingTag ) . '" placeholder="' . esc_attr( $settings['placeholderText'] ) . '" enable_ai_powered_search="' . esc_attr( $enable_ai_suggestions ) . '"]' );
			echo '</div>';
		}
	}

	public function view_params() {
		$settings = &$this->attributes;

		$_shortcode_attributes = apply_filters(
			'betterdocs_elementor_search_form_params',
			[
				'placeholder'       => esc_html( $settings['placeholderText'] ),
				'heading'           => isset( $settings['searchHeading'] ) ? esc_html( $settings['searchHeading'] ) : '',
				'subheading'        => isset( $settings['searchSubHeading'] ) ? esc_html( $settings['searchSubHeading'] ) : '',
				'heading_tag'       => isset( $settings['searchHeadingTag'] ) ? esc_attr( $settings['searchHeadingTag'] ) : 'h2',
				'subheading_tag'    => isset( $settings['searchSubHeadingTag'] ) ? esc_attr( $settings['searchSubHeadingTag'] ) : 'h3'
			],
			$this->attributes
		);

		$wrapper_classes = [];
		if ( isset( $settings['blockId'] ) && ! empty( $settings['blockId'] ) ) {
			$wrapper_classes[] = $settings['blockId'];
		}

		return [
			'shortcode_attr' => $_shortcode_attributes,
			'wrapper_attr'   => [
				'class' => $wrapper_classes
			]
		];
	}
}
