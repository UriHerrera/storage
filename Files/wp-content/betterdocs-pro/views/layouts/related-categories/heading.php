<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<h2 class="betterdocs-related-categories-heading">
    <?php echo betterdocs()->template_helper->kses( $heading ); ?>
</h2>
