<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- core block query; meta/tax filtering required.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- block exposes user-driven exclusion controls.
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class CategorySlateLayout extends Block {

    public $view_wrapper = 'betterdocs-category-slate-layout-block';

    protected $editor_styles = [
        'betterdocs-blocks-category-slate-layout'
    ];

    protected $frontend_styles = [
        'betterdocs-blocks-category-slate-layout',
        'betterdocs-category-grid',
        'betterdocs-fontawesome'
    ];
    /**
     * Name of the block.
     *
     * @var string
     */
    protected $name = 'category-slate-layout';

    /**
     * Unique ID for the block.
     *
     * @var string
     */
    protected $unique_id = 'betterdocs_category_slate_layout';

    public function get_name() {
        return $this->name;
    }

    public function get_unique_id() {
        return $this->unique_id;
    }

    /**
     * Check if the block can be enabled.
     *
     * @return bool
     */
    public function can_enable() {
        return true;
    }

    /**
     * Register scripts and styles for the block.
     */
    public function register_scripts() {
        // The styles are already registered in Scripts.php blocks() method
        // This method is called to ensure the block is properly initialized
    }

    /**
     * Get default attributes for the block.
     *
     * @return array
     */
    public function get_default_attributes() {
        return [
            'blockId'                 => '',
            'categories'              => [],
            'includeCategories'       => '',
            'excludeCategories'       => '',
            'gridPerPage'             => 9,
            'orderBy'                 => 'doc_category_order',
            'order'                   => 'asc',
            'layout'                  => 'layout-3',
            'showTitle'               => true,
            'titleTag'                => 'h2',
            'showIcon'                => true,
            'categoryIcon'            => 'folder',
            'categoryTitleLink'       => true,
            'gridColumns'             => 4,
            'gridSpace'               => 48,
            'selectKB'                => '',
            'postsPerPage'            => 5,
            'postsOrderBy'            => 'betterdocs_order',
            'postsOrder'              => 'asc',
        ];
    }

    /**
     * Render method required by Block class.
     *
     * @param array $attributes Block attributes.
     * @param string $content Block content.
     * @return void
     */
    public function render( $attributes, $content ) {
		$attributes = &$this->attributes;
		echo '<div class="betterdocs-blocks betterdocs-category-layout-8 ' . esc_attr( $attributes['blockId'] ) . '">';
        $this->views( 'layouts/base' );
		echo '</div>';
    }

    public function view_params() {
        $attributes = &$this->attributes;

        // Sanitize the layout attribute to prevent path traversal (LFI).
        $attributes['layout'] = sanitize_file_name( $attributes['layout'] );

        $terms_object = [
            'taxonomy'   => 'doc_category',
            'order'      => $attributes['order'],
            'orderby'    => $attributes['orderBy'],
            'number'     => isset( $attributes['gridPerPage'] ) ? $attributes['gridPerPage'] : 9,
            'hide_empty' => true // Hide empty categories on frontend
        ];

        if ( 'doc_category_order' === $attributes['orderBy'] ) {
            $terms_object['meta_key'] = 'doc_category_order';
            $terms_object['orderby']  = 'meta_value_num';
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
            'betterdocs-category-grid-three-wrapper',
            'betterdocs-blocks-grid'
        ];

        $layout_class = ( $attributes['layout'] === 'default' ) ? 'layout-1' : $attributes['layout'];

        $_inner_wrapper_classes = [
            'betterdocs-category-grid-inner-wrapper',
            'layout-flex',
            'docs-col-4',
            $layout_class,
            'betterdocs-column-' . ($attributes['gridColumnsRange'] ?? 3),
        ];

        $wrapper_attr = [
            'class' => $_wrapper_classes
        ];

        $inner_wrapper_attr = [
            'class'                     => $_inner_wrapper_classes,
            'data-column_desktop'       => $attributes['gridColumnsRange'] ?? 3,
            'data-column_space_desktop' => $attributes['gridSpaceRange'] ?? 48,
        ];

        $docs_query = [
            'orderby'            => isset( $attributes['postsOrderBy'] ) ? $attributes['postsOrderBy'] : 'betterdocs_order',
            'order'              => isset( $attributes['postsOrder'] ) ? $attributes['postsOrder'] : 'asc',
            'posts_per_page'     => isset( $attributes['postsPerPage'] ) ? $attributes['postsPerPage'] : 5,
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
            'layout'                  => $attributes['layout'],
            'multiple_knowledge_base' => ( $default_multiple_kb && ! empty( $kb_slug ) ) ? true : false,
            'kb_slug'                 => $kb_slug,
            'layout_type'             => 'block',
            'show_title'              => $attributes['showTitle'],
            'title_tag'               => $attributes['titleTag'],
            'show_icon'               => $attributes['showIcon'],
            'category_icon'           => $attributes['categoryIcon'],
            'category_title_link'     => $attributes['categoryTitleLink'],
            'show_count'              => false,
            'show_header'             => true,
            'show_list'               => true,
        ];
    }
}
