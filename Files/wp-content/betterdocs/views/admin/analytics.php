<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>


<div class="betterdocs-settings-wrap">
	<?php do_action( 'betterdocs_settings_header' ); ?>
	<div class="betterdocs-left-right-settings">
		<div class="betterdocs-settings">
			<div class="betterdocs-settings-menu">
				<ul>
					<li class="active" data-tab="overview"><a href="#overview"><?php esc_html_e( 'Overview', 'betterdocs' ); ?></a></li>
					<li class="" data-tab="reactions"><a href="#reactions"><?php esc_html_e( 'Reactions', 'betterdocs' ); ?></a></li>
					<li class="" data-tab="keyword_search"><a href="#keyword_search"><?php esc_html_e( 'Keyword Search', 'betterdocs' ); ?></a></li>
				</ul>
			</div>

			<div class="betterdocs-settings-content betterdocs-analytics-teaser">
				<div class="betterdocs-settings-content-inner">
					<div class="betterdocs-settings-form-wrapper">
						<form method="post" id="betterdocs-settings-form" action="#">
							<div id="betterdocs-overview" class="betterdocs-settings-tab active">
								<img src="<?php echo betterdocs()->assets->icon( 'analytics/overview.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" alt="">
								<div class="overlay">
									<?php
										$view_object->get( 'admin/analytics-parts/overlay' )
									?>
								</div>
							</div>
							<div id="betterdocs-reactions" class="betterdocs-settings-tab">
								<img src="<?php echo betterdocs()->assets->icon( 'analytics/reactions.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" alt="">
								<div class="overlay">
									<?php
										$view_object->get( 'admin/analytics-parts/overlay' )
									?>
								</div>
							</div>
							<div id="betterdocs-keyword_search" class="betterdocs-settings-tab">
								<img src="<?php echo betterdocs()->assets->icon( 'analytics/search.png', true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a markup so escaping is not needed here ?>" alt="">
								<div class="overlay">
									<?php
										$view_object->get( 'admin/analytics-parts/overlay' )
									?>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<?php //include BETTERDOCS_ADMIN_DIR_PATH . 'partials/betterdocs-settings-blocks.php'; ?>
		</div>
	</div>
</div>
