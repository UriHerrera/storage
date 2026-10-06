<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="betterdocs-tabgrid-contents-wrapper">
    <?php
        $grid_wrapper_attr = [
            'class' => [
                'betterdocs-category-grid-wrapper'
            ]
        ];

        $grid_wrapper_attr = betterdocs()->template_helper->get_html_attributes( $grid_wrapper_attr );

        $inner_wrapper_attr = [
            'class' => [
                'betterdocs-category-grid-inner-wrapper'
            ]
        ];

        $inner_wrapper_attr = betterdocs()->template_helper->get_html_attributes( $inner_wrapper_attr );

        $_defined_vars = get_defined_vars();
        $_params       = isset( $_defined_vars['params'] ) ? $_defined_vars['params'] : [];
        if ( ! is_wp_error( $kb_terms ) ) {
            foreach ( $kb_terms as $term ) {
                if ( $term->count <= 0 ) {
                    continue;
                }

                $terms_query = betterdocs()->query->terms_query( [
                    'multiple_kb'        => true,
                    'taxonomy'           => 'doc_category',
                    'kb_slug'            => $term->slug,
                    'order'              => $terms_order,
                    'orderby'            => $terms_orderby,
                    'nested_subcategory' => $nested_subcategory,
                    'number'             => isset( $number ) ? $number : 0
                ] );

                $params = wp_parse_args( [
                    'wrapper_attr'            => $grid_wrapper_attr,
                    'inner_wrapper_attr'      => $inner_wrapper_attr,
                    'layout'                  => 'default',
                    'multiple_knowledge_base' => true,
                    'widget_type'             => 'category-grid'
                ], $_params );

                $params['terms_query_args'] = $terms_query;
                $params['kb_slug']          = $term->slug;

                $params = $widget->normalize_attributes( $params, $widget->map_view_vars );
            ?>
            <div data-tab_target="<?php echo esc_attr( $term->term_id );?>" class="<?php echo esc_attr( $term->slug );?> betterdocs-tabgrid-content-wrapper" data-mkb-slug="<?php echo esc_attr( $term->slug ); ?>">
                <div class="betterdocs-tabgrid-content-inner-wrapper">
                    <?php betterdocs()->views->get( 'layouts/base', $params );?>
                </div>
            </div>
        <?php }
    } ?>
</div>
