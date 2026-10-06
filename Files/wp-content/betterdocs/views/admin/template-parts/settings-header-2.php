<div class="betterdocs-settings-header-2">
	<div class="betterdocs-header-full">
        <h2 class="title"><?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo isset( $title ) ? $title : __('Settings', 'betterdocs'); //phpcs:ignore ?></h2>
		<label class="betterdocs-setting-mode-change-button">
			<input class="betterdocs-mode-toggle" id="betterdocs-mode-toggle" type="checkbox">
			<span><?php echo __( 'Mode', 'betterdocs' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Not needed to be ecaped, because its a static string ?></span>
		</label>
	</div>
</div>
