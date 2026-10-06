<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- core block query; meta/tax filtering required.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- block exposes user-driven exclusion controls.
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class CategoryGrid extends Block {
	protected $editor_scripts = [
		'betterdocs-blocks-editor',
		'betterdocs-categorygrid'
	];

	protected $editor_styles = [
		'betterdocs-blocks-editor',
		'betterdocs-fontawesome',
		'betterdocs-blocks-category-grid'
	];

	protected $frontend_scripts = [
		'betterdocs-category-grid'
	];
	protected $frontend_styles  = [
		'betterdocs-fontawesome',
		'betterdocs-blocks-category-grid'
	];

	/**
	 * unique name of block
	 * @return string
	 */
	public function get_name() {
		return 'categorygrid';
	}

	public function register_scripts() {
		//when thrive editor mode is active, do not enqueue this script, this causes an issue with thrive edit mode
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only editor detection.
		$check_for_thrive = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		if ( $check_for_thrive === 'architect' ) {
			return;
		}

		$this->assets_manager->enqueue(
			'betterdocs-categorygrid',
			'blocks/categorygrid/frontend.js',
			[ 'masonry' ]
		);
	}

	public function get_default_attributes() {
		return [
			'blockId'                 => '',
			'categories'              => [],
			'includeCategories'       => '',
			'excludeCategories'       => '',
			'gridPerPage'             => 9,
			'subCategoryPerGrid'      => 2,
			'orderBy'                 => 'doc_category_order',
			'order'                   => 'asc',
			'layout'                  => 'default',
			'showTitle'               => true,
			'titleTag'                => 'h2',
			'showCount'               => true,
			'showIcon'                => true,
			'showList'                => true,
			'layoutMode'              => 'grid',
			'showHeader'              => true,
			'showButton'              => true,
			'listIcon'                => 'far fa-file-alt',
			'postsPerPage'            => 5,
			'postsOrderBy'            => 'betterdocs_order',
			'postsOrder'              => 'asc',
			'enableNestedSubcategory' => false,
			// Default follows the global "Lazy Load Sidebar Docs & Subcategories"
			// setting, mirroring the Elementor sidebar widget's control default, so
			// enabling the setting lazy-loads the block too unless overridden.
			'enableLazyLoad'          => (bool) betterdocs()->settings->get( 'archive_lazy_load_descendants' ),
			'postPerSubcategory'      => 3,
			'buttonIconPosition'      => 'after',
			'buttonIcon'              => 'fas fa-angle-right',
			'buttonPosition'          => 'after',
			'showButtonIcon'          => true,
			'colRange'                => 3,
			'TABcolRange'             => 2,
			'MOBcolRange'             => 1,
			'gridSpaceRange'          => 10,
			'TABgridSpaceRange'       => 10,
			'MOBgridSpaceRange'       => 10,
			'buttonText'              => __( 'Explore More', 'betterdocs' ),
			'categoryTitleLink'       => false,
			'selectKB'                => '',
			'listIconImageUrl'        => '',
			'prefix'                  => '',
			'suffix'                  => '',
			'suffixSingular'          => ''
		];
	}

	public function render( $attributes, $content ) {
		$this->views( 'layouts/base' );
	}

	public function view_params() {
		$attributes = &$this->attributes;

		// Sanitize the layout attribute to prevent path traversal (LFI).
		$attributes['layout'] = sanitize_file_name( $attributes['layout'] );

		$terms_object = [
			'taxonomy'   => 'doc_category',
			'order'      => $attributes['order'],
			'orderby'    => $attributes['orderBy'],
			'number'     => isset( $attributes['gridPerPage'] ) ? $attributes['gridPerPage'] : 5,
			'hide_empty' => true
		];

		if ( 'doc_category_order' === $attributes['orderBy'] ) {
			// Use betterdocs_order which handles fallback logic automatically
			$terms_object['orderby'] = 'betterdocs_order';
		}

		if ( $attributes['enableNestedSubcategory'] ) {
			$terms_object['parent'] = 0;
		}

		$includes = $this->string_to_array( $attributes['includeCategories'] );
		$excludes = $this->string_to_array( $attributes['excludeCategories'] );

		if ( ! empty( $includes ) ) {
			$terms_object['include'] = array_diff( $includes, (array) $excludes );
		}

		if ( ! empty( $excludes ) ) {
			$terms_object['exclude'] = $excludes;
		}

		$_wrapper_classes = [
			'betterdocs-category-grid-wrapper',
			'betterdocs-blocks-grid'
		];

		$layout_class = ( $attributes['layout'] === 'default' ) ? 'layout-1' : $attributes['layout'];

		$_inner_wrapper_classes = [
			'betterdocs-category-grid-inner-wrapper',
			'layout-flex',
			$layout_class,
			$attributes['layoutMode'],
			'betterdocs-column-' . $attributes['colRange'],
			'betterdocs-column-tablet-' . $attributes['TABcolRange'],
			'betterdocs-column-mobile-' . $attributes['MOBcolRange']
		];

		$wrapper_attr = [
			'class' => $_wrapper_classes
		];

		$inner_wrapper_attr = [
			'class'                     => $_inner_wrapper_classes,
			'data-column_desktop'       => $attributes['colRange'],
			'data-column_tab'           => $attributes['TABcolRange'],
			'data-column_mobile'        => $attributes['MOBcolRange'],
			'data-column_space_desktop' => $attributes['gridSpaceRange'],
			'data-colomn_space_tab'     => $attributes['TABgridSpaceRange'],
			'data-colomn_space_mobile'  => $attributes['MOBgridSpaceRange']
		];

		$docs_query = [
			'orderby'            => $attributes['postsOrderBy'],
			'order'              => $attributes['postsOrder'],
			'posts_per_page'     => $attributes['postsPerPage'],
			'nested_subcategory' => $attributes['enableNestedSubcategory']
		];

		$default_multiple_kb = betterdocs()->settings->get( 'multiple_kb' );

		$select_kb = isset( $attributes['selectKB'] ) ? $attributes['selectKB'] : '';
		if ( is_string( $select_kb ) && $select_kb !== '' ) {
			$decoded   = json_decode( $select_kb, true );
			$select_kb = is_array( $decoded ) ? $decoded : [];
		}
		$kb_slug = is_array( $select_kb ) && isset( $select_kb['value'] ) ? $select_kb['value'] : '';

		if ( is_tax( 'knowledge_base' ) && $default_multiple_kb == 1 ) {
			$object                     = get_queried_object();
			$kb_slug                    = $object->slug;
		}

		if ( ! empty( $kb_slug ) && $default_multiple_kb == 1 ) {
			$terms_object['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => 'doc_category_knowledge_base',
					'value'   => $kb_slug,
					'compare' => 'LIKE'
				]
			];
		}

		/**
		 * Add This Attribute When Using Outside Betterdocs Templates Only
		 */
		if ( $default_multiple_kb == 1 && ( ! empty( $kb_slug ) ) && ( ! betterdocs()->helper->is_templates() ) ) {
			$inner_wrapper_attr['data-mkb-slug'] = $kb_slug;
		}

		return [
			'wrapper_attr'            => $wrapper_attr,
			'inner_wrapper_attr'      => $inner_wrapper_attr,
			'terms_query_args'        => betterdocs()->query->terms_query( $terms_object ),
			'docs_query_args'         => $docs_query,
			'widget_type'             => 'category-grid',
			'multiple_knowledge_base' => ( $default_multiple_kb && ! empty( $kb_slug ) ) ? true : false,
			'kb_slug'                 => $kb_slug,
			'nested_terms_query'      => [
				'number' => $attributes['subCategoryPerGrid']
			],
			'list_icon_url'           => '', // not needed for blocks, provided the prop for formality
			'layout_type'             => 'block',
			'nested_docs_query_args'  => [
				'orderby'        => $attributes['postsOrderBy'],
				'order'          => $attributes['postsOrder'],
				'posts_per_page' => $attributes['postPerSubcategory']
			],
			'list_icon_name'          => ! empty( $attributes['listIconImageUrl'] ) ? [ 'value' => [ 'url' => str_replace( 'blob:', '', $attributes['listIconImageUrl'] ) ] ] : ( ! empty( $attributes['listIcon'] ) ? [ 'value' => [ 'url' => $attributes['listIcon'] ] ] : ( ! empty( betterdocs()->settings->get( 'docs_list_icon' ) ) ? [ 'value' => [ 'url' => betterdocs()->settings->get( 'docs_list_icon' )['url'] ] ] : [] ) ),
			'button_icon'             => [
				'value' => $attributes['buttonIcon']
			],
			'category_title_link'     => $attributes['categoryTitleLink'],
			'count_prefix'            => $attributes['prefix'],
			'count_suffix'            => $attributes['suffix'],
			'count_suffix_singular'   => $attributes['suffixSingular'],
			'title_tag'               => $attributes['titleTag'],
			'lazy_load'               => ! empty( $attributes['enableLazyLoad'] )
		];
	}
}
