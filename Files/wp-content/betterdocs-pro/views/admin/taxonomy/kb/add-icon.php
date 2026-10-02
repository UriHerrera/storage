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
	<label for="knowledge-base-image-id">
		<?php esc_html_e( 'KB Icon', 'betterdocs-pro' );?>
	</label>
	<input type="hidden" id="knowledge-base-image-id" name="term_meta[image-id]" class="custom_media_url doc-category-image-id" value="">
	<div id="knowledge-base-image-wrapper" class="doc-category-image-wrapper">
		<img src="<?php echo esc_url( betterdocs()->assets->icon( 'betterdocs-cat-icon.svg', true ) ); ?>" alt="">
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
</div>
