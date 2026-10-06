<?php 
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$permalink = rtrim($doc['permalink'], '/'); 

$learnmore_text = 'Learn More ';

if(!empty($dictionary_learn_more_text))
{
    $learnmore_text = $dictionary_learn_more_text;
}

$letter = isset( $letter ) ? $letter : '';
?>

<div class="encyclopedia-item" data-first-letter="<?php echo esc_attr($letter); ?>">
    <div class="tools-card">
        <div class="top-tools-card">
            <?php
            $title_tag = !empty($item_heading_tag) ? $item_heading_tag : 'h2';
            echo '<' . betterdocs()->template_helper->is_valid_tag($title_tag) . ' class="heading-small tools-card__title-text encyclopedia-item-title-tag">' . esc_html($doc['post_title']) . '</' . betterdocs()->template_helper->is_valid_tag($title_tag) . '>';
            ?>
            <p class="text-size-small tools-card__sample-text"><?php echo esc_html($excerpt); ?></p>
        </div>
        <div class="tools-card_link-container">
            <a href="<?php echo esc_url($permalink ); ?>" class="text-style-link"><?php echo esc_html($learnmore_text); ?></a>
            <a href="#" class="text-style-link tools-card_arrow">→</a>
        </div>
        <a href="<?php echo esc_url($permalink ); ?>" class="tools-card__link-block w-inline-block"></a>
    </div>
</div>