<div class="overlay-content">
	<h3>
		<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
esc_html_e( 'Unlock Analytics With BetterDocs PRO', 'betterdocs' ); ?>
		<img src="<?php echo betterdocs()->assets->icon( 'analytics/lock.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" id="img-teaser" alt="">
	</h3>
	<p class="teaser-text teaser-desc">
		<?php esc_html_e( 'Scale your support tickets tracking the performance of your knowledge base BetterDocs Analytics.', 'betterdocs' ); ?>
	</p>
	<ul class="analytics-list">
		<li>
			<div class="analytics-item">
				<img src="<?php echo betterdocs()->assets->icon( 'analytics/graph.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" id="img-teaser-1" alt="">
				<p class="teaser-text"><?php esc_html_e( 'Measure performance of your knowledge base', 'betterdocs' ); ?></p>
			</div>
		</li>
		<li>
			<div class="analytics-item">
				<img src="<?php echo betterdocs()->assets->icon( 'analytics/magifier.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" id="img-teaser-2" alt="">
				<p class="teaser-text"><?php esc_html_e( 'Identify most viewed docs and doc categories', 'betterdocs' ); ?></p>
			</div>
		</li>
		<li>
			<div class="analytics-item">
				<img src="<?php echo betterdocs()->assets->icon( 'analytics/globe.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" id="img-teaser-4" alt="">
				<p class="teaser-text"><?php esc_html_e( 'Identify most used keywords searched by site visitors', 'betterdocs' ); ?> </p>
			</div>
		</li>
	</ul>
	<div class="analytics-upgrade-btn">
		<a href="https://betterdocs.co/upgrade"><?php esc_html_e( 'Upgrade To BetterDocs PRO', 'betterdocs' ); ?></a>
	</div>
</div>
