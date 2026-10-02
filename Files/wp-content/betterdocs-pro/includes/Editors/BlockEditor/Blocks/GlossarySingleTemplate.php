<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocsPro\Utils\EncyclopediaQuery;


class GlossarySingleTemplate extends Block
{

    public $is_pro = true;



    /**
     * unique name of block
     * @return string
     */
    public function get_name()
    {
        return 'glossary-single-template';
    }

    protected $editor_styles = [
        'betterdocs-encyclopedia'
    ];

    protected $frontend_styles = [
        'betterdocs-encyclopedia'
    ];

    public function get_default_attributes()
    {
        return [
            'titleTag' => 'h2', // Empty means use customizer setting
        ];
    }

    public function render($attributes, $content)
    {
        $view_object             = betterdocs()->views;
        $docs_by_letter = EncyclopediaQuery::docs_sort_by_letter();

        // Get title tag from block attributes or use customizer setting
        $title_tag = !empty($attributes['titleTag']) ? $attributes['titleTag'] : '';

        ?>
        <div class="betterdocs-wrapper">
            <?php if (is_tax('glossaries')) : ?>
                <?php betterdocs()->template_helper->search(); ?>
                <?php $current_letter = strtoupper(mb_substr(get_the_title(), 0, 1)); ?>
                <?php $alphabet_list_style = betterdocs()->customizer->defaults->get('encyclopedia_alphabet_list_style', 'box'); ?>
                <?php $excluded_alphabets_range = $this->process_excluded_alphabets(); ?>
                <?php
                            betterdocs_pro()->views->get('layouts/encyclopedia/alphabets', [
                                'docs_by_letter' => $docs_by_letter,
                                'current_letter' => $current_letter,
                                'alphabet_list_style' => $alphabet_list_style,
                                'excluded_alphabets_range' => $excluded_alphabets_range

                            ]);

                            ?>
            <?php endif; ?>
            <div id="betterdocs-single-main" class="glossary-single-content-area betterdocs-content-area docs-content-full-main">
                <div class="betterdocs-content-inner-area">
                    <?php

                            if (is_tax('glossaries')) {
                                $view_object->get('templates/headers/layout-7', [
                                    'block_title_tag' => $title_tag
                                ]);
                                $view_object->get('templates/glossary-single/layout-1');
                            } else { ?>
                        <div class="glossary-template-archive-title">
                            <h1><?php the_archive_title(); ?></h1>

                        </div>
                        <div class="beetterdocs-glossary-archive-container">

                            <?php while (have_posts()) : the_post();
                                            ?>

                                <div class="beetterdocs-glossary-archive-content">
                                    <header class="betterdocs-entry-header">
                                        <div class="docs-single-title">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php
                                                                $view_object->get(
                                                                    'templates/parts/title',
                                                                    [
                                                                        'tag' => betterdocs()->customizer->defaults->get('betterdocs_post_title_tag')
                                                                    ]
                                                                );
                                                                ?>
                                            </a>
                                            <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail(); ?></a>

                                        </div>
                                    </header> <!-- .entry-header -->
                                    <div class="betterdocs-entry-content">
                                        <?php the_excerpt(); ?>
                                        <a href="<?php the_permalink(); ?>"><?php echo esc_html__('Read More', 'betterdocs-pro'); ?></a>
                                    </div>

                                </div>

                            <?php
                                        endwhile;
                                        ?>
                        </div>
                    <?php
                            }

                            ?>
                </div>
            </div>
        </div> <!-- .betterdocs-single-wraper -->

<?php
    }

    private function process_excluded_alphabets()
    {
        $exclude_alphabets_range = betterdocs()->customizer->defaults->get('exclude_encyclopedia_dictionary_alphabets');

        if (empty($exclude_alphabets_range)) {
            return [];
        }

        // If it's already an array, return it
        if (is_array($exclude_alphabets_range)) {
            return $exclude_alphabets_range;
        }

        // Convert comma-separated string to array
        $exclude_alphabets_range = explode(',', $exclude_alphabets_range);
        $exclude_alphabets_range = array_map(function($alphabet) {
            return trim(strtoupper($alphabet));
        }, $exclude_alphabets_range);

        // Get the full alphabet range
        $enable_non_latin = betterdocs()->settings->get('encyclopedia_enable_non_latin');
        $script = betterdocs()->settings->get('encyclopedia_non_latin_option');
        $alphabet_range = EncyclopediaQuery::get_character_range($enable_non_latin, $script);

        // Return the filtered alphabet range (excluding the excluded letters)
        return array_diff($alphabet_range, $exclude_alphabets_range);
    }
}
