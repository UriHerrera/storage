<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
do_action( 'betterdocs_doc_category_add_form_before' ); ?>

<div class="form-field term-group">
	<label for="doc-category-order">
		<?php esc_html_e( 'Order', 'betterdocs' ); ?>
	</label>
	<input type="number" id="doc-category-order" style="width:100px" name="term_meta[order]" value="">
</div>

<div class="form-field term-group">
	<label>
		<?php esc_html_e( 'Category Icon', 'betterdocs' ); ?>
	</label>
	<input type="hidden" name="term_meta[image-id]" class="doc-category-image-id" value="">
	<div class="doc-category-image-wrapper">
		<img src="<?php echo betterdocs()->assets->icon( 'betterdocs-cat-icon.svg', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="">
	</div>
	<p>
		<input type="button" id="betterdocs_tax_media_button"
			class="button button-secondary betterdocs_tax_media_button"
			name="betterdocs_tax_media_button"
			value="<?php esc_html_e( 'Add Image', 'betterdocs' ); ?>"
		/>
		<input type="button"
			id="doc_tax_media_remove"
			class="button button-secondary doc_tax_media_remove"
			name="doc_tax_media_remove"
			value="<?php esc_html_e( 'Remove Image', 'betterdocs' ); ?>"
		/>
	</p>
</div>

<?php
do_action( 'betterdocs_doc_category_add_form_after' );
