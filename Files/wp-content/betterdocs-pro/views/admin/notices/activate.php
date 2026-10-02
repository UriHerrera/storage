<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="error notice">
	<p>
		<?php
            echo wp_kses_post(
                __( '<strong>BetterDocs Pro</strong> plugin requires <strong>BetterDocs</strong> plugin to be installed. Please <strong><em>Install & Activate</em></strong> the BetterDocs plugin to access all the features.', 'betterdocs-pro' )
            );
        ?>
        <br />
        <a
            href="<?php echo esc_url( $button_url ); ?>"
            id="betterdocs-install-core" style="margin-top: 10px" class="button button-primary">
            <?php echo esc_html( $button_text ); ?>
        </a>
    </p>
</div>
