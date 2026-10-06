<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<tr class="form-field term-group-wrap">
    <th scope="row">
        <label><?php esc_html_e( 'Knowledge Base', 'betterdocs-pro' );?></label>
    </th>
    <td>
        <select id="doc-category-kb" class="doc-category-kb" name="doc_category_kb[]" multiple="multiple">
            <option value="" selected><?php esc_html_e( 'No Knowledge Base', 'betterdocs-pro' );?></option>';
            <?php
                foreach ( $terms as $_term ) {
                    $is_selected = false;
                    if ( is_array( $knowledge_base ) ) {
                        // $knowledge_base usually holds the raw database string (e.g. %e9%a1%b9...)
                        // $_term->slug could be either urlencoded OR decoded unicode (项目01)
                        if ( in_array( $_term->slug, $knowledge_base, true ) ||
                             in_array( urlencode( $_term->slug ), $knowledge_base, true ) ||
                             in_array( strtolower( urlencode( $_term->slug ) ), $knowledge_base, true ) ||
                             in_array( urldecode( $_term->slug ), $knowledge_base, true ) ) {
                            $is_selected = true;
                        }
                    }

                    $_selected_slug = $is_selected ? $_term->slug : '';
                    // option_kses() returns a kses-sanitized <option> string.
                    echo betterdocs()->template_helper->option_kses( $_term->slug, $_term->name, $_selected_slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
            ?>
        </select>
    </td>
</tr>
