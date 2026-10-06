<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<select class="dashboard-search-field select-kb-top" name="knowledgebase">
	<option value="all"><?php esc_html_e( 'All Knowledge Base', 'betterdocs-pro' );?></option>
	<?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter UI.
        $_current_term = ( isset( $_GET['knowledgebase'] ) ) ? sanitize_text_field( wp_unslash( $_GET['knowledgebase'] ) ) : '';
        // term_options() returns an HTML string of <option> tags from a known taxonomy slug.
        echo wp_kses( betterdocs()->template_helper->term_options( 'knowledge_base', $_current_term ), [ 'option' => [ 'value' => true, 'selected' => true ] ] );
    ?>
</select>