<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;

/**
 * Lightweight, non-blocking doc-view collection for the Free Overview.
 *
 * The frontend tracker (assets/static/public/js/analytics-tracker.js) fires a
 * sendBeacon after DOMContentLoaded on single docs; the REST ingest endpoint
 * (REST/AnalyticsTracker) calls record_view() which increments the daily
 * aggregate row in {prefix}betterdocs_analytics plus the per-post views meta.
 *
 * This replaces the Pro synchronous wp_head counter so collection lives in
 * Free and a Pro upgrade is not empty. Pro adds richer collection in a later
 * unit on top of the raw events table.
 */
class AnalyticsTracker extends Base {
	/**
	 * @var \WPDeveloper\BetterDocs\Core\Settings
	 */
	protected $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tracker' ] );
	}

	/**
	 * Enqueue the beacon on single docs only and hand it the post id + nonce.
	 */
	public function enqueue_tracker() {
		if ( ! is_singular( 'docs' ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$ga4_enabled = (bool) $this->settings->get( 'analytics_ga4', false );
		$ga4_method  = (string) $this->settings->get( 'ga4_method', 'datalayer' );
		$measurement = sanitize_text_field( (string) $this->settings->get( 'ga4_measurement_id', '' ) );

		betterdocs()->assets->enqueue( 'betterdocs-analytics-tracker', 'public/js/analytics-tracker.js', [], true );
		betterdocs()->assets->localize(
			'betterdocs-analytics-tracker',
			'betterDocsTracker',
			[
				'rest_url' => rest_url( 'betterdocs/v1/analytics/view' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'post_id'  => $post_id,
				// GA4 forwarding config. `method` selects how events are sent
				// (datalayer | gtag | mp); the Measurement Protocol api_secret is
				// intentionally NEVER exposed here — it is read server-side only.
				'ga4'      => [
					'enabled'        => $ga4_enabled,
					'method'         => $ga4_method,
					'measurement_id' => ( 'gtag' === $ga4_method ) ? $measurement : ''
				]
			]
		);

		// gtag mode: load Google's gtag.js on doc pages and initialize the property
		// so the tracker's gtag('event', …) calls reach GA4 without external GTM.
		if ( $ga4_enabled && 'gtag' === $ga4_method && '' !== $measurement ) {
			wp_enqueue_script(
				'betterdocs-gtag',
				'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $measurement ),
				[],
				null,
				false
			);
			wp_add_inline_script(
				'betterdocs-gtag',
				"window.dataLayer = window.dataLayer || [];\n"
				. "function gtag(){dataLayer.push(arguments);}\n"
				. "gtag('js', new Date());\n"
				. "gtag('config', '" . esc_js( $measurement ) . "');",
				'after'
			);
		}
	}

	/**
	 * Increment the daily aggregate for a doc view. Write-only, fast.
	 *
	 * @param int $post_id     Doc post id.
	 * @param int $unique_hint 1 if the client reports this is a first view in the browser.
	 * @return bool True when a view was recorded.
	 */
	public function record_view( $post_id, $unique_hint = 0 ) {
		global $wpdb;

		$post_id = (int) $post_id;
		if ( ! $post_id || ! $this->is_eligible_visits() ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'docs' || $post->post_status !== 'publish' ) {
			return false;
		}

		$table          = $wpdb->prefix . 'betterdocs_analytics';
		$today          = gmdate( 'Y-m-d' );
		$unique_enabled = $this->settings->get( 'unique_visitor_count' ) != false;
		$unique_inc     = ( $unique_enabled && (int) $unique_hint === 1 ) ? 1 : 0;

		$row_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}betterdocs_analytics WHERE post_id = %d AND created_at = %s",
				$post_id,
				$today
			)
		);

		if ( $row_id ) {
			// Atomic increments avoid a read-modify-write race between concurrent beacons.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}betterdocs_analytics SET impressions = impressions + 1, unique_visit = unique_visit + %d WHERE id = %d",
					$unique_inc,
					$row_id
				)
			);
		} else {
			// ON DUPLICATE KEY UPDATE makes the first-view-of-day insert idempotent:
			// two concurrent beacons that both miss the SELECT above collapse into a
			// single row (incrementing) instead of racing to create duplicates,
			// wherever the (post_id, created_at) UNIQUE key is present.
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}betterdocs_analytics ( post_id, impressions, unique_visit, created_at ) VALUES ( %d, %d, %d, %s )
					ON DUPLICATE KEY UPDATE impressions = impressions + 1, unique_visit = unique_visit + VALUES( unique_visit )",
					$post_id,
					1,
					$unique_inc,
					$today
				)
			);
		}

		$views = (int) get_post_meta( $post_id, '_betterdocs_meta_views', true );
		update_post_meta( $post_id, '_betterdocs_meta_views', $views + 1 );

		return true;
	}

	/**
	 * Whether the current request should be counted, per analytics_from +
	 * exclude_bot_analytics settings. Ported from the legacy Pro counter and
	 * corrected to use get_current_user_id().
	 */
	/**
	 * Public eligibility check (analytics_from + bot exclusion) for callers like
	 * the scroll-completion path that don't go through record_view().
	 */
	public function is_eligible() {
		return $this->is_eligible_visits();
	}

	protected function is_eligible_visits() {
		$should_count   = false;
		$analytics_from = $this->settings->get( 'analytics_from', 'everyone' );
		$user_id        = get_current_user_id();

		switch ( $analytics_from ) {
			case 'everyone':
				$should_count = true;
				break;
			case 'guests':
				if ( $user_id === 0 ) {
					$should_count = true;
				}
				break;
			case 'registered_users':
				if ( $user_id > 0 ) {
					$should_count = true;
				}
				break;
		}

		if ( ! $should_count ) {
			return false;
		}

		if ( $this->settings->get( 'exclude_bot_analytics', true ) == 1 ) {
			$bots      = [ 'google', 'msnbot', 'ia_archiver', 'lycos', 'jeeves', 'scooter', 'fast-webcrawler', 'slurp@inktomi', 'turnitinbot', 'technorati', 'yahoo', 'findexa', 'findlinks', 'gaisbo', 'zyborg', 'surveybot', 'bloglines', 'blogsearch', 'pubsub', 'syndic8', 'userland', 'gigabot', 'become.com', 'baiduspider', '360spider', 'spider', 'sosospider', 'yandex',
				// AI agents / crawlers — mirror of the Pro AiTrafficCollector catalog so a
				// JS-capable or spoofed AI user-agent is never double-counted as a human
				// view (Pro records it as an AI fetch server-side; the human path is a JS
				// beacon). Free must not depend on Pro, so the tokens are duplicated here.
				// stripos is case-insensitive, so lowercase is sufficient.
				'gptbot', 'chatgpt-user', 'oai-searchbot', 'claudebot', 'anthropic-ai', 'claude-user', 'claude-web', 'claude-searchbot', 'perplexitybot', 'perplexity-user', 'google-extended', 'googleother', 'applebot-extended', 'cursor/', 'copilot', 'meta-externalagent', 'meta-externalfetcher', 'facebookbot', 'youbot', 'ccbot', 'bytespider', 'duckassistbot' ];
			$useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			if ( $useragent !== '' ) {
				foreach ( $bots as $lookfor ) {
					if ( false !== stripos( $useragent, $lookfor ) ) {
						return false;
					}
				}
			}
		}

		return $should_count;
	}
}
