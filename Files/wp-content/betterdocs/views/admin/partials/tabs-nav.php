<div class="betterdocs-list-grid-icon tabs-nav">
	<a
		class="<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo esc_attr( $helper::is_active( 'list', $active_tab ) ); ?> icon-wrap icon-wrap-1"
		href="<?php echo esc_attr( esc_url( $url ) ); ?>&mode=list" data-toggle-target=".tab-content-1">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
			<path d="M3 12H21M3 6H21M3 18H21" stroke="#475467" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</a>
	<a
		class="<?php echo esc_attr( $helper::is_active( 'grid', $active_tab ) ); ?> icon-wrap icon-wrap-2"
		href="<?php echo esc_attr( esc_url( $url ) ); ?>&mode=grid" data-toggle-target=".tab-content-2">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
			<path d="M10 3H3V10H10V3Z" stroke="#475467" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M21 3H14V10H21V3Z" stroke="#475467" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M21 14H14V21H21V14Z" stroke="#475467" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M10 14H3V21H10V14Z" stroke="#475467" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</a>
</div>
