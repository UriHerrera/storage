<?php
    
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$betterdocs_config = $scripts;
    if ( ! empty( $cross_domain_language ) ) {
    $betterdocs_config[ 'CROSS_DOMAIN_LANG' ] = $cross_domain_language;
    }

    $chatbot_config = $chatbot_scripts;
    if ( ! empty( $cross_domain_language ) ) {
    $chatbot_config[ 'lang' ] = $cross_domain_language;
    }
?>
<div class="betterdocs-cross-domain-code">
    <aside id="betterdocs-ia"></aside>
    <link rel="stylesheet" href="<?php echo esc_url( betterdocs_pro()->assets->asset_url( 'public/css/instant-answer.css' ) ); ?>"> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
    <link rel="stylesheet" href="<?php echo esc_url( betterdocs_pro()->assets->asset_url( 'public/css/ai-chatbot.css' ) ); ?>"> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
    <?php // Generated CSS string from CSSGenerator — already sanitized for CSS context. ?>
    <style type="text/css"><?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
    <?php // wp_json_encode produces JSON-safe output for embedding in <script>. ?>
    <script> window.betterdocs = <?php echo wp_json_encode( $betterdocs_config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> </script>
    <script> window.betterdocsAIChatbot = <?php echo wp_json_encode( $chatbot_config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> </script>
    <script src="<?php echo esc_url( betterdocs_pro()->assets->asset_url( 'public/js/instant-answer-cd.js' ) ); ?>"></script> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
</div>
