<?php
namespace WPDeveloper\BetterDocsPro\Utils;

use WPDeveloper\BetterDocs\Utils\BlockTemplate as BlockTemplateFree;
use WPDeveloper\BetterDocsPro\Core\MultipleKB;
/**
 * Utility methods used for serving block templates from BetterDocs Blocks.
 * {@internal This class and its methods should only be used within the class-block-template-controller.php and is not intended for public use.}
 */
class BlockTemplate extends BlockTemplateFree {

    private $multipleKB;

    public function __construct( MultipleKB $multipleKB ) {
        $this->multipleKB = $multipleKB;
    }

    const ELIGIBLE_FOR_DOC_ARCHIVE_FALLBACK = [ 'taxonomy-knowledge_base', 'taxonomy-doc_category', 'taxonomy-doc_tag' ];

    public function get_templates_directory_pro( $template_type = 'wp_template' ) {
        return BETTERDOCS_PRO_FSE_TEMPLATES_PATH . DIRECTORY_SEPARATOR;
    }

    public function get_plugin_block_template_types() {
        $plugin_template_types = parent::get_plugin_block_template_types();

        if ( ! $this->multipleKB->is_enable ) {
            return $plugin_template_types;
        }

        $plugin_template_types['archive-docs'] = [
            'title'       => _x( 'Multiple KB', 'Template name', 'betterdocs-pro' ),
            'description' => __( 'Template used to display Knowledge Bases.', 'betterdocs-pro' )
        ];

        $plugin_template_types['taxonomy-knowledge_base'] = [
            'title'       => _x( 'Docs Page', 'Template name', 'betterdocs-pro' ),
            'description' => __( 'Template used to display Docs Page.', 'betterdocs-pro' )
        ];

        return $plugin_template_types;
    }

    public function get_templates_fils_from_betterdocs( $template_type ) {
        // The Multiple-KB template swap is handled in get_template_paths() (which the
        // parent calls), so there is nothing extra — and no fragile index math — to do
        // here. This method previously dropped a Free template by hardcoded array
        // index and re-merged the Pro directory, which depended on the directory
        // iterator order and removed the wrong file on some filesystems.
        return parent::get_templates_fils_from_betterdocs( $template_type );
    }

    /**
     * Finds all nested template part file paths in a theme's directory.
     *
     * @param string $base_directory The theme's file path.
     * @return array $path_list A list of paths to all template part files.
     */
    public function get_template_paths( $base_directory ) {
        $path_list = parent::get_template_paths( $base_directory );

        if ( ! $this->multipleKB->is_enable || ! file_exists( BETTERDOCS_PRO_FSE_TEMPLATES_PATH ) ) {
            return $path_list;
        }

        // Multiple KB is enabled: Pro ships its own archive-docs (multiple-kb) and
        // taxonomy-knowledge_base templates that must override the Free ones.
        // Collect the Pro template files, drop any incoming file that shares a
        // basename with them, then prepend Pro's so they win during slug
        // resolution. This is matched by FILENAME rather than array index because
        // the RecursiveDirectoryIterator order is filesystem-dependent — the old
        // index-based unset() removed the wrong file on some servers, leaving the
        // Free archive-docs (categorygrid) to load even with Multiple KB enabled.
        $_pro_templates    = [];
        $nested_files      = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( BETTERDOCS_PRO_FSE_TEMPLATES_PATH ) );
        $nested_html_files = new \RegexIterator( $nested_files, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH );
        foreach ( $nested_html_files as $path => $file ) {
            $_pro_templates[] = $path;
        }

        if ( empty( $_pro_templates ) ) {
            return $path_list;
        }

        $_pro_basenames = array_map( 'basename', $_pro_templates );
        $path_list      = array_filter(
            $path_list,
            function ( $path ) use ( $_pro_basenames ) {
                return ! in_array( basename( $path ), $_pro_basenames, true );
            }
        );

        return array_merge( $_pro_templates, array_values( $path_list ) );
    }

    public function convert_slug_to_title( $template_slug ) {
        $title = parent::convert_slug_to_title($template_slug);

        if ( ! $this->multipleKB->is_enable ) {
            return $title;
        }

        if ($template_slug === 'archive-docs') {
            return __('Multiple KB', 'betterdocs-pro');
        }

        if ($template_slug === 'taxonomy-knowledge_base') {
            return __('Docs Page', 'betterdocs-pro');
        }

        return $title;
    }


}
