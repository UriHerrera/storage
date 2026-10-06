
<?php 

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$learnmore_text = 'Learn More';

if(!empty($dictionary_learn_more_text))
{
    $learnmore_text = $dictionary_learn_more_text;
}

$letter = isset( $letter ) ? $letter : '';
?>
<div class="encyclopedia-item" data-first-letter="<?php echo esc_attr($letter); ?>">

    <div class="tools-card">
        <a href="<?php echo esc_url($doc['permalink']); ?>">
            <?php
            $title_tag = !empty($item_heading_tag) ? $item_heading_tag : 'h2';
            echo '<' . betterdocs()->template_helper->is_valid_tag($title_tag) . ' class="heading-small tools-card__title-text encyclopedia-item-title-tag">' . esc_html($doc['post_title']) . '</' . betterdocs()->template_helper->is_valid_tag($title_tag) . '>';
            ?>
        </a>
        <div class="top-tools-card">
            <p class="text-size-small tools-card__sample-text tooltip-content"><?php echo esc_html($excerpt); ?></p>
            <div class="tools-card_link-container">
                <a href="<?php echo esc_url($doc['permalink']); ?>" class="text-style-link"><?php echo esc_html($learnmore_text); ?><span class="text-style-link tools-card_arrow">→</span></a>
            </div>
        </div>
    </div>

</div>