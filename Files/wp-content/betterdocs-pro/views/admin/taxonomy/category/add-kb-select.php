
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ($manage_docs_terms) {
    ?>
    <div class="form-field term-group">
        <label><?php esc_html_e('Knowledge Base', 'betterdocs-pro') ?></label>
        <select id="doc-category-kb" class="doc-category-kb" name="doc_category_kb[]" multiple="multiple">
            <option value="" selected><?php esc_html_e('No Knowledge Base', 'betterdocs-pro') ?></option>
            <?php foreach ($manage_docs_terms as $term) {
                echo '<option value="' . esc_attr($term->slug) . '">' . esc_html( $term->name ) . '</option>';
            } ?>
        </select>
    </div>
<?php
}