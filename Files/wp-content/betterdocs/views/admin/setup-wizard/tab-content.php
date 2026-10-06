<div class="tab-content" id="<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
print( isset( $section['id'] ) ? esc_html( $section['id'] ) : 'default-nav' ); ?>">
	<table class="form-table" role="presentation">
		<tbody>
			<?php
			if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
				foreach ( $section['fields'] as $field ) {
					$methodName = 'callback_' . $field['type'];
					$self::$methodName( $field );
				}
			}
			?>
		</tbody>
	</table>
</div>
