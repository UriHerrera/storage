<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<div class="form-field term-group">
    <label><?php esc_html_e( 'Knowledge Base', 'betterdocs-pro' );?></label>
    <select id="doc-category-kb" class="doc-category-kb" name="doc_category_kb[]" multiple="multiple">
        <option value="" selected><?php esc_html_e( 'No Knowledge Base', 'betterdocs-pro' );?></option>';
        <?php
            // option_kses() returns a kses-sanitized <option> string.
            foreach ( $terms as $_term ) {
                echo betterdocs()->template_helper->option_kses( $_term->slug, $_term->name, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        ?>
    </select>
</div>
