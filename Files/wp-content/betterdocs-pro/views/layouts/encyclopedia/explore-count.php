<?php 
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ($explore_count > 0 && !isset($_GET['encyclopedia_prefix'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
    <?php if (empty($explore_more_text_color)) :
        $explore_more_text_color = '#667085';
     endif;



     ?>


    <div class="encyclopedia-item explore-more-docs">
        <a href="<?php echo esc_url($explore_url); ?>" target="_blank">

            <?php printf('<span>%s</span>', str_replace('[count]', $explore_count, esc_html($dictionary_explore_more_text))); ?>

            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M7 17L17 7" stroke="<?php echo esc_attr($explore_more_text_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M8 7L17 7L17 16" stroke="<?php echo esc_attr($explore_more_text_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </a>

        <?php if ($doc_style === 'doc-grid') : ?>
            <a href="<?php echo esc_url( $explore_url ); ?>" target="_blank" class="tools-card__link-block w-inline-block"></a>
        <?php endif; ?>

    </div>

<?php endif; ?>
