
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
<div class="encyclopedia-list" data-first-letter="<?php echo esc_html($letter); ?>">
    <a href="<?php echo esc_url($doc['permalink']); ?>">
        <?php echo esc_html($doc['post_title']); ?>
    </a>
</div>
