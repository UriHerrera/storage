<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
do_action( 'betterdocs_doc_category_update_form_before', $term ); ?>
<tr class="form-field term-group-wrap">
	<th scope="row">
		<label for="doc-category-id"><?php esc_html_e( 'Category Id', 'betterdocs' ); ?></label>
	</th>
	<td>
		<input
			type="text"
			id="doc-category-id"
			style="width:100px"
			name=""
			readonly
            value="<?php echo esc_attr( $_GET["tag_ID"] ); // phpcs:ignore ?>"
		/>
	</td>
</tr>
<tr class="form-field term-group-wrap">
	<th scope="row">
		<label for="doc-category-order"><?php esc_html_e( 'Order', 'betterdocs' ); ?></label>
	</th>
	<td>
		<input type="number" id="doc-category-order" style="width:100px" name="term_meta[order]" value="<?php echo $order ? esc_attr( $order ) : ''; ?>">
	</td>
</tr>
<tr class="form-field term-group-wrap batterdocs-cat-media-upload">
	<th scope="row">
		<label><?php esc_html_e( 'Category Icon', 'betterdocs' ); ?></label>
	</th>
	<td>
		<input type="hidden" class="doc-category-image-id" name="term_meta[image-id]" value="<?php echo esc_attr( $icon_id ); ?>">
		<div class="doc-category-image-wrapper" id="doc-category-image-wrapper">
			<?php
			if ( $icon_id ) {
				echo '<img style="display: none;" src="' . betterdocs()->assets->icon( 'betterdocs-cat-icon.svg', true ) . '" alt="">'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Html Icon
				echo wp_get_attachment_image( $icon_id, 'thumbnail', false, 'class=custom_media_image' );
			} else {
				echo '<img src="' . betterdocs()->assets->icon( 'betterdocs-cat-icon.svg', true ) . '" alt="">'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Html Icon
			}
			?>

		</div>
		<p>
			<input
				type="button"
				class="button button-secondary betterdocs_tax_media_button"
				id="betterdocs_tax_media_button"
				name="betterdocs_tax_media_button"
				value="<?php esc_html_e( 'Add Image', 'betterdocs' ); ?>"
			/>
			<input
				type="button"
				class="button button-secondary doc_tax_media_remove"
				id="doc_tax_media_remove"
				name="doc_tax_media_remove"
				value="<?php esc_html_e( 'Remove Image', 'betterdocs' ); ?>"
			/>
		</p>
	</td>
</tr>
<?php
do_action( 'betterdocs_doc_category_update_form_after', $term );
