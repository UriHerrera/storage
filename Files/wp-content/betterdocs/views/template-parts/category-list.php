<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
    
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use WPDeveloper\BetterDocs\Utils\Helper;

    $posts = betterdocs()->query->get_posts( $query_args, true );

    if ( ! $posts->have_posts() ) {
        wp_reset_postdata();
    }

    $_page_id = null;

    if ( is_single() ) {
        $_page_id = get_the_ID();
    }

    // if there have list icon url from settings or customizer or shortcodes attribites format it to $list_icon_name
    $settings_list_icon = betterdocs()->settings->get( 'docs_list_icon' );

    // Ensure $layout_type is set
    if ( ! isset( $layout_type ) ) {
        $layout_type = '';
    }

    // Ensure $list_icon_url is set
    if ( ! isset( $list_icon_url ) ) {
        $list_icon_url = '';
    }

    // Ensure $list_icon_name is always initialized as an array to prevent TypeError
    if ( ! isset( $list_icon_name ) || ! is_array( $list_icon_name ) ) {
        $list_icon_name = array();
    }

    #for elementor if icon is not selected from settings, and icon attributes are empty, then look for settings, if settings is empty show default icon, else show from settings. If svg from list is selected, then show svg, else show from settings, if settings is empty then show default. Not applicable for sidebar layout-2 elementor
    if ( isset( $layout_type ) && 'widget' == $layout_type ) {
        if ( isset( $sidebar_layout ) && 'layout-2' == $sidebar_layout ) { #done to avoid warning for elementor sidebar layout-2
            $list_icon_name = array();
        }

        // Ensure $list_icon_name is an array before using array_key_exists
        if ( ! is_array( $list_icon_name ) ) {
            $list_icon_name = array();
        }

        $list_icon_name = ( array_key_exists( 'value', $list_icon_name ) && array_key_exists( 'library', $list_icon_name ) || array_key_exists( 'value', $list_icon_name ) ) ? ( empty( $list_icon_name[ 'value' ] ) && empty( $list_icon_name[ 'library' ] ) || empty( $list_icon_name[ 'value' ] ) ? ( isset( $settings_list_icon[ 'url' ] ) ? array( 'value' => array( 'url' => $settings_list_icon[ 'url' ] ) ) : array() ) : $list_icon_name ) : ( isset( $list_icon_name[ 'url' ] ) ? array( 'value' => array( 'url' => $list_icon_name[ 'url' ] ) ) : ( isset( $settings_list_icon[ 'url' ] ) ? $settings_list_icon[ 'url' ] : array() ) );
    }

    // #for blocks if $list_icon_name is empty, but for category grid block
    if ( isset( $layout_type ) && 'block' == $layout_type ) {
        if ( empty( $list_icon_name ) && isset( $widget_type ) && 'category-grid' == $widget_type ) {
            $list_icon_name = array(
                'value'
            );
        }
    }

    #for blocks if $sidebar_layout is passed, then remove the list icon for layout-4
    if ( isset( $layout_type ) && 'block' == $layout_type ) {
        if ( isset( $sidebar_layout ) && 'layout-4' == $sidebar_layout ) {
            $list_icon_name = array(
                'value' => array(
                    'url' => 'list'
                )
            );
        }
    }

    if ( isset( $layout_type ) && ! empty( $layout_type ) && 'template' == $layout_type && isset( $list_icon_url ) && $list_icon_url ) {
        if ( isset( $list_icon_url ) && $list_icon_url ) {
            $list_icon_name = array(
                'value' => array(
                    'url' => $list_icon_url
                )
            );
        }
    }
?>

<ul class="betterdocs-articles-list">
	<?php
        if ( '' === $query_args[ 'posts_per_page' ] ) {
            $query_args[ 'posts_per_page' ] = get_option( 'posts_per_page' );
        }

        if ( -1 == $query_args[ 'posts_per_page' ] || $query_args[ 'posts_per_page' ] > 0 ) {
            $pos       = 'left';
            $icon      = 'list';
            $show_icon = true;

            // Check if list icon should be shown
            if ( isset( $show_list_icon ) && false === $show_list_icon ) {
                $show_icon = false;
            }

            if ( ! empty( $list_icon_position ) ) {
                if ( 'right' == $list_icon_position ) {
                    $pos = 'right';
                }
            }
            if ( ! empty( $list_icon_name ) ) {
                $icon = $list_icon_name;
            }
            while ( $posts->have_posts() ):
                $posts->the_post();
                $_link_attributes = array(
                    'href' => esc_url( get_the_permalink() )
                );

                if ( get_the_ID() === $_page_id && Helper::get_tax() != 'doc_category' ) {
                    $_link_attributes[ 'class' ] = 'active';
                }

                $_link_attributes = betterdocs()->template_helper->get_html_attributes( $_link_attributes );

                echo wp_sprintf(
                    '<li>%4$s<a %1$s><span>%2$s</span> %3$s</a></li>',
                    $_link_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    /**
                     * Filter a doc item's title markup in category/sidebar doc
                     * lists (e.g. to prepend an API method badge). Deliberately
                     * NOT core `the_title` — this must stay scoped to list items.
                     *
                     * SECURITY CONTRACT: the return value is echoed as HTML and
                     * is NOT escaped afterwards — it has to be, or a callback
                     * could not add the markup this filter exists for. The value
                     * passed in is already kses'd; a callback that wraps or
                     * appends to it is responsible for escaping whatever IT
                     * introduces. Never hand this filter unsanitised user input.
                     *
                     * @param string $title_html Kses'd title markup.
                     * @param int    $post_id
                     */
                    apply_filters( 'betterdocs_docs_list_item_title', betterdocs()->template_helper->kses( get_the_title() ), get_the_ID() ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ( $show_icon && 'right' == $pos ) ? betterdocs()->template_helper->icon( $icon ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ( $show_icon && 'left' == $pos ) ? betterdocs()->template_helper->icon( $icon ) : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                );
            endwhile;

            wp_reset_postdata();
        }
        /**
         * Nested Sub Categories
         */
        if ( (bool) $nested_subcategory && $term instanceof \WP_Term ) {
            $_params = get_defined_vars();
            $_params = isset( $_params[ 'params' ] ) ? $_params[ 'params' ] : array();
            $_params = wp_parse_args(
                array(
                    'term_id' => $term->term_id,
                    'list_icon_url' => $list_icon_url
                ),
                $_params
            );
            if ( 'widget' == $layout_type || 'block' == $layout_type ) {
                $_params[ 'list_icon_name' ] = $list_icon_name;
            }
            $view_object->get( 'template-parts/nested-categories', $_params );
        }
    ?>
</ul>
