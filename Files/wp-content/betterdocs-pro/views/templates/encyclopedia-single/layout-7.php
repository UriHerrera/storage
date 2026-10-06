<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;



/**
 * The template for single doc page
 *
 * @author WPDeveloper
 * @package Documentation/SinglePage
 */

get_header();

$view_object             = betterdocs()->views;
$enable_toc              = betterdocs()->settings->get('enable_toc');
$collapsible_toc_mobile  = betterdocs()->settings->get('collapsible_toc_mobile');
$enable_sidebar_cat_list = betterdocs()->settings->get('enable_sidebar_cat_list');

$wrapper_class = ['betterdocs-content-wrapper betterdocs-display-flex'];

if ($enable_sidebar_cat_list == 1 && $enable_toc == 1) {
    $wrapper_class[] = 'grid-col-3 sidebar-toc-enable';
} elseif ($enable_sidebar_cat_list == 1) {
    $wrapper_class[] = 'grid-col-2 sidebar-enable';
} elseif ($enable_toc == 1) {
    $wrapper_class[] = 'grid-col-2 toc-enable';
} elseif (!$enable_sidebar_cat_list && !$enable_toc) {
    $wrapper_class[] = 'grid-col-1 content-enable';
}
$docs_by_letter = EncyclopediaQuery::docs_sort_by_letter();

?>
<div class="betterdocs-wrapper betterdocs-fluid-wrapper betterdocs-single-wrapper betterdocs-single-layout-2 betterdocs-single-minimalist-layout betterdocs-single-wraper encyclopedia-single-layout betterdocs-encyclopedia-wrapper">
    <?php betterdocs()->template_helper->search(); ?>

    <?php $current_letter = strtoupper(mb_substr(get_the_title(), 0, 1)); ?>
    <?php $alphabet_list_style = betterdocs()->customizer->defaults->get('encyclopedia_alphabet_list_style', 'box'); ?>
    <?php
    // Process excluded alphabets
    $exclude_alphabets_range = betterdocs()->customizer->defaults->get('exclude_encyclopedia_dictionary_alphabets');
    $excluded_alphabets_range = [];
    if (!empty($exclude_alphabets_range)) {
        if (is_array($exclude_alphabets_range)) {
            $excluded_alphabets_range = $exclude_alphabets_range;
        } else {
            $exclude_alphabets_range = explode(',', $exclude_alphabets_range);
            $exclude_alphabets_range = array_map(function($alphabet) {
                return trim(strtoupper($alphabet));
            }, $exclude_alphabets_range);

            $enable_non_latin = betterdocs()->settings->get('encyclopedia_enable_non_latin');
            $script = betterdocs()->settings->get('encyclopedia_non_latin_option');
            $alphabet_range = EncyclopediaQuery::get_character_range($enable_non_latin, $script);

            $excluded_alphabets_range = array_diff($alphabet_range, $exclude_alphabets_range);
        }
    }

    betterdocs_pro()->views->get('layouts/encyclopedia/alphabets', [
        'docs_by_letter' => $docs_by_letter,
        'current_letter' => $current_letter,
        'alphabet_list_style' => $alphabet_list_style,
        'excluded_alphabets_range' => $excluded_alphabets_range

    ]);
    ?>
    <div class="<?php echo implode(' ', $wrapper_class); ?>">
        <div id="betterdocs-single-main" class="betterdocs-content-area docs-content-full-main">
            <div class="betterdocs-content-inner-area">
                <?php
                while (have_posts()) : the_post();
                    /**
                     * Headers
                     */
                    $view_object->get('templates/headers/layout-7');
                    $view_object->get('templates/contents/layout-2');
                    $view_object->get('templates/footer');
                endwhile;
                $view_object->get('templates/parts/navigation');
                $view_object->get('templates/parts/credit');
                $view_object->get('templates/parts/comment');
                ?>
            </div>
        </div> <!-- #main -->
        <?php
        if ($enable_toc) {
            $view_object->get('templates/sidebars/sidebar-right');
        }
        ?>
    </div> <!-- #primary -->
</div> <!-- .betterdocs-single-wraper -->
<?php
get_footer();
