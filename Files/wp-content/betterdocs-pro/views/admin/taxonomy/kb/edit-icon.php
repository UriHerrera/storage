<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
do_action( 'betterdocs_knowledge_base_update_form_before', $term ); ?>

<tr class="form-field term-group-wrap batterdocs-cat-media-upload">
	<th scope="row">
		<label for="knowledge-base-image-id">
			<?php esc_html_e( 'KB Icon', 'betterdocs-pro' );?>
		</label>
	</th>
	<td>
		<input class="doc-category-image-id" type="hidden" id="knowledge-base-image-id" name="term_meta[image-id]" value="<?php echo esc_attr( $icon_id ); ?>" />
		<div id="knowledge-base-image-wrapper" class="doc-category-image-wrapper">
			<?php
                if ( $icon_id ) {
                    echo '<img style="display: none;" src="' . esc_url( betterdocs()->assets->icon( 'betterdocs-cat-icon.svg', true ) ) . '" alt="">';
                    echo wp_get_attachment_image( $icon_id, 'thumbnail', false, 'class=custom_media_image' );
                } else {
                    echo '<img src="'. esc_url( betterdocs()->assets->icon('betterdocs-cat-icon.svg', true) ) .'" alt="">';
                }
            ?>
		</div>
		<p>
			<input
				type="button"
				class="button button-secondary betterdocs_tax_media_button" id="betterdocs_tax_media_button"
				name="betterdocs_tax_media_button"
				value="<?php esc_html_e('Add Image', 'betterdocs-pro'); ?>" />
			<input
				type="button"
				class="button button-secondary doc_tax_media_remove" id="doc_tax_media_remove"
				name="doc_tax_media_remove"
				value="<?php esc_html_e('Remove Image', 'betterdocs-pro'); ?>"
			/>
		</p>
	</td>
</tr>
