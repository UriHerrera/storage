<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<tr class="form-field term-group-wrap batterdocs-cat-media-upload">
	<th scope="row">
		<label><?php esc_html_e( 'Category Cover Image for Handbook Layout', 'betterdocs-pro' );?></label>
	</th>
	<td>
		<input type="hidden" class="doc-category-image-id" name="term_meta[thumb-id]" value="<?php echo esc_attr( $cat_thumb_id ); ?>">
		<div class="doc-category-image-wrapper betterdocs-category-thumb">
			<?php
                if ( $cat_thumb_id ) {
                    echo '<img style="display: none" width="100" src="' . esc_url( betterdocs_pro()->assets->icon( 'full-default.png', true ) ) . '" alt="">';
                    echo wp_get_attachment_image( $cat_thumb_id, 'thumbnail', false, 'class=custom_media_image' );
                } else {
                    echo '<img width="100" src="' . esc_url( betterdocs_pro()->assets->icon( 'full-default.png', true ) ) . '" alt="">';
                }
            ?>
		</div>
		<p>
			<input
				type="button"
				class="button button-secondary betterdocs_tax_media_button" id="betterdocs_tax_media_button" name="betterdocs_tax_media_button"
				value="<?php esc_html_e( 'Add Image', 'betterdocs-pro' );?>"
			/>
			<input
				type="button"
				class="button button-secondary doc_tax_media_remove" id="doc_tax_media_remove" name="doc_tax_media_remove"
				value="<?php esc_html_e( 'Remove Image', 'betterdocs-pro' );?>"
			/>
		</p>
	</td>
</tr>
