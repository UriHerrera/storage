<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="warning notice">
	<p>
		<?php
            echo wp_kses_post(
                __( 'BetterDocs Analytics requires <strong>BetterDocs Free 4.7.0</strong> or newer. Please <strong><em>update</em></strong> the BetterDocs Free plugin for a smooth experience.', 'betterdocs-pro' )
            );
        ?>
    </p>
</div>
