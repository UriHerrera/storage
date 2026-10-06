<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="betterdocs-cross-domain-code">
    <aside id="betterdocs-ia"></aside>
    <link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>"> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
    <link rel="stylesheet" href="<?php echo esc_url( $chatbot_css_url ); ?>"> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
    <?php // Generated CSS string from CSSGenerator — already sanitized for CSS context. ?>
    <style type="text/css"><?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
    <script> window.betterdocs = {{BETTERDOCS_CONFIG}} </script>
    <script> window.betterdocsAIChatbot = {{CHATBOT_CONFIG}} </script>
    <script src="<?php echo esc_url( $js_url ); ?>"></script> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
</div>

