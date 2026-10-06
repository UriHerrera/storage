<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
// phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- HTML email assets must be remote.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( empty( $args ) ) {
	return;
}

	$neutral_arrow = '';
	$up_arrow      = '<img style="margin-left:5px" src="https://betterdocs.co/wp-content/uploads/2022/12/polygon-up.png" alt="" />';
	$down_arrow    = '<img style="margin-left:5px" src="https://betterdocs.co/wp-content/uploads/2022/12/polygon-down.png" alt="" />';

	$view_arrow = $search_arrow = $reactions_arrow = $docs_arrow = $up_arrow;

	$views = $prev_views = $reactions = $prev_reactions = $total_search = $prev_total_search = $total_docs = $prev_total_docs = 0;

if ( isset( $args['views']['current_data'][0]->views ) ) {
	$views = number_format( $args['views']['current_data'][0]->views );
}
if ( isset( $args['views']['previous_data'][0]->views ) ) {
	$prev_views = number_format( $args['views']['previous_data'][0]->views );
}

	$percentage_views = $report_email->percentage( $prev_views, $views );

if ( isset( $args['views']['current_data'][0]->reactions ) ) {
	$reactions = number_format( $args['views']['current_data'][0]->reactions );
}
if ( isset( $args['views']['previous_data'][0]->reactions ) ) {
	$prev_reactions = number_format( $args['views']['previous_data'][0]->reactions );
}

	$percentage_reactions = $report_email->percentage( $prev_reactions, $reactions );

	$view_color = $search_color = $reactions_color = $docs_color = '#34cf8a';
if ( $percentage_views == '0.00' ) {
	$view_color = '#f7d070';
	$view_arrow = $neutral_arrow;
} elseif ( $views < $prev_views ) {
	$view_color = '#ff616c';
	$view_arrow = $down_arrow;
}

if ( $percentage_reactions == '0.00' ) {
	$reactions_color = '#f7d070';
	$reactions_arrow = $neutral_arrow;
} elseif ( $reactions < $prev_reactions ) {
	$reactions_color = '#ff616c';
	$reactions_arrow = $down_arrow;
}

if ( isset( $args['search']['current_data'][0]->search_count ) ) {
	$total_search = number_format( $args['search']['current_data'][0]->search_count );
}
if ( isset( $args['search']['previous_data'][0]->search_count ) ) {
	$prev_total_search = number_format( $args['search']['previous_data'][0]->search_count );
}

	$percentage_total_search = $report_email->percentage( $prev_total_search, $total_search );

if ( $percentage_total_search == '0.00' ) {
	$search_color = '#f7d070';
	$search_arrow = $neutral_arrow;
} elseif ( $total_search < $prev_total_search ) {
	$search_color = '#ff616c';
	$search_arrow = $down_arrow;
}

if ( isset( $args['new_docs']['current_data'] ) ) {
	$total_docs = number_format( $args['new_docs']['current_data'] );
}
if ( isset( $args['new_docs']['previous_data'] ) ) {
	$prev_total_docs = number_format( $args['new_docs']['previous_data'] );
}

	$percentage_total_docs = $report_email->percentage( $prev_total_docs, $total_docs );

if ( $percentage_total_docs == '0.00' ) {
	$docs_color = '#f7d070';
	$docs_arrow = $neutral_arrow;
} elseif ( $total_docs < $prev_total_docs ) {
	$docs_color = '#ff616c';
	$docs_arrow = $down_arrow;
}

	$days_ago        = esc_html( $report_email->frequency( $frequency ) );
	$view_color      = esc_attr( $view_color );
	$reactions_color = esc_attr( $reactions_color );
	$search_color    = esc_attr( $search_color );
?>

<tr>
	<td>
		<table style="width: 100%; border-spacing: 0;">
			<tr>
				<td>
					<span style="display: flex; align-items: center; background: #fff; margin-right: 7px; height: 100%; padding: 20px;">
						<span>
							<img src="https://betterdocs.co/wp-content/uploads/2022/12/eye-solid.png" style="max-width: 100%; width: 45px; height: auto; margin-right: 15px;" alt="eye_logo">
						</span>
						<span style="margin-top: 5px;">
							<h3 style="font-size: 12px; line-height: 1em; color: #6e6e73; font-weight: 600; text-transform: uppercase; padding-bottom: 5px; margin: 0;"><?php esc_html_e( 'Total Views', 'betterdocs' ); ?></h3>
							<h2 style="font-size: 22px; line-height: 1em; font-weight: 700; color: #1d1d1f; margin: 0;">
								<?php
									echo esc_html( $views );
									echo wp_kses_post( $view_arrow );
								?>
								<span style="font-size: 14px; line-height: 1.1em; font-weight: 600; color: <?php echo esc_attr( $view_color ); ?>; margin-left: 5px;">
									<?php echo esc_html( $percentage_views ); ?>%
								</span>
							</h2>
						</span>
					</span>
				</td>
				<td>
					<span style="display: flex; align-items: center; background: #fff; margin-left: 7px; height: 100%; padding: 20px;">
						<span>
							<img src="https://betterdocs.co/wp-content/uploads/2022/12/search.png" style="max-width: 100%; width: 45px; height: auto; margin-right: 15px;" alt="eye_logo">
						</span>
						<span style="margin-top: 5px;">
							<h3 style="font-size: 12px; line-height: 1em; color: #6e6e73; font-weight: 600; text-transform: uppercase; padding-bottom: 5px; margin: 0;"><?php esc_html_e( 'Total Searches', 'betterdocs' ); ?></h3>
							<h2 style="font-size: 22px; line-height: 1em; font-weight: 700; color: #1d1d1f; margin: 0;">
								<?php
									echo esc_html( $total_search );
									echo wp_kses_post( $search_arrow );
								?>
								<span style="font-size: 14px; line-height: 1.1em; font-weight: 600; color: <?php echo esc_attr( $search_color ); ?>; margin-left: 5px;">
									<?php echo esc_html( $percentage_total_search ); ?>%
								</span>
							</h2>
						</span>

					</span>
				</td>
			</tr>
			<tr>
				<td style="padding-top: 15px;">
					<span style="display: flex; align-items: center; background: #fff; margin-right: 7px; height: 100%; padding: 20px;">
						<span>
							<img src="https://betterdocs.co/wp-content/uploads/2022/12/search.png" style="max-width: 100%; width: 45px; height: auto; margin-right: 15px;" alt="eye_logo">
						</span>
						<span style="margin-top: 5px;">
							<h3 style="font-size: 12px; line-height: 1em; color: #6e6e73; font-weight: 600; text-transform: uppercase; padding-bottom: 5px; margin: 0;"><?php esc_html_e( 'Total Reactions', 'betterdocs' ); ?></h3>
							<h2 style="font-size: 22px; line-height: 1em; font-weight: 700; color: #1d1d1f; margin: 0;">
								<?php
									echo esc_html( $reactions );
									echo wp_kses_post( $reactions_arrow );
								?>
								<span style="font-size: 14px; line-height: 1.1em; font-weight: 600; color: <?php echo esc_attr( $reactions_color ); ?>; margin-left: 5px;">
									<?php echo esc_html( $percentage_reactions ); ?>%
								</span>
							</h2>
						</span>

					</span>
				</td>
				<td style="padding-top: 15px;">
					<span style="display: flex; align-items: center; background: #fff; margin-left: 7px; height: 100%; padding: 20px;">
						<span>
							<img src="https://betterdocs.co/wp-content/uploads/2022/12/file-.png" style="max-width: 100%; width: 45px; height: auto; margin-right: 15px;" alt="eye_logo">
						</span>
						<span style="margin-top: 5px;">
							<h3 style="font-size: 12px; line-height: 1em; color: #6e6e73; font-weight: 600; text-transform: uppercase; padding-bottom: 5px; margin: 0;"><?php esc_html_e( 'Newly Created Docs', 'betterdocs' ); ?></h3>
							<h2
								style="font-size: 22px; line-height: 1em; font-weight: 700; color: #1d1d1f; margin: 0;">
								<?php
									echo esc_html( $total_docs );
									echo wp_kses_post( $docs_arrow );
								?>
								<span style="font-size: 14px; line-height: 1.1em; font-weight: 600; color: <?php echo esc_attr( $docs_color ); ?>; margin-left: 5px;">
									<?php echo esc_html( $percentage_total_docs ); ?>%
								</span>
							</h2>
						</span>
					</span>
				</td>
			</tr>
		</table>
	</td>
</tr>
