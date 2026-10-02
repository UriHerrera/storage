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
	<label>
		<?php esc_html_e( 'Category Cover Image for Handbook Layout', 'betterdocs-pro' );?>
	</label>
	<input type="hidden" class="doc-category-image-id" name="term_meta[thumb-id]" value="">
	<div class="doc-category-image-wrapper betterdocs-category-thumb">
		<img width="100" src="<?php echo esc_url( betterdocs_pro()->assets->icon( 'full-default.png', true ) ); ?>" alt="">
	</div>
	<p>
		<input
			type="button" class="button button-secondary betterdocs_tax_media_button"
			id="betterdocs_cat_thumb_button" name="betterdocs_cat_thumb_button"
			value="<?php esc_html_e( 'Add Image', 'betterdocs-pro' );?>"
		/>
		<input
			type="button" class="button button-secondary doc_tax_media_remove" id="doc_cat_thumb_remove"
			name="doc_cat_thumb_remove"
			value="<?php esc_html_e( 'Remove Image', 'betterdocs-pro' );?>"
		/>
	</p>
</div>
