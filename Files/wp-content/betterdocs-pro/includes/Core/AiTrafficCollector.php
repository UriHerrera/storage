<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * AI-agent traffic collection (Advanced Analytics v1.5).
 *
 * AI crawlers and assistant fetchers never execute the Free JS beacon, so this
 * collector detects them server-side: on every singular-doc request the
 * User-Agent is matched against a curated catalog of AI agents and a hit is
 * recorded into {prefix}betterdocs_analytics_ai_daily — one row per
 * (agent, post, day, hour), incremented atomically. Hour granularity powers the
 * activity heatmap; last_fetch keeps the exact time of the newest hit per row
 * so the dashboard's "Last AI fetch" line stays precise.
 *
 * These hits are deliberately separate from human view counting: the bot
 * exclusion in the Free tracker keeps them out of the human tables, and this
 * path never touches _betterdocs_meta_views or the daily views rollup.
 *
 * Known limitations:
 *  - A full-page cache that serves HTML without PHP will hide those cached hits
 *    from detection (same constraint as any server-side counter).
 *  - Detection is User-Agent-based only — there is no reverse-DNS or published-IP
 *    verification, so a client can spoof any catalog agent (e.g. `curl -A GPTBot`)
 *    and inflate counts. These are analytics signals, not authenticated identity.
 */
// The hot path below is a single upsert per AI hit — caching would defeat it.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
class AiTrafficCollector {
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'maybe_record' ], 1 );

		// Keep the AI Visibility site checks (llms.txt + robots.txt loopback fetches)
		// warm off a cron tick so the dashboard report never pays that ~1s cost lazily
		// on the request path (#10). The handler is a static method on the REST class
		// (autoloaded on demand — no rest_api_init needed); this collector is
		// instantiated on every load, so the action is attached during cron requests.
		add_action( 'betterdocs_warm_ai_visibility_checks', [ 'WPDeveloper\\BetterDocsPro\\REST\\AnalyticsAiTraffic', 'warm_site_checks' ] );
		if ( ! wp_next_scheduled( 'betterdocs_warm_ai_visibility_checks' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'betterdocs_warm_ai_visibility_checks' );
		}
	}

	/**
	 * Curated AI agent catalog. Ids double as the icon keys in the analytics
	 * React app (common/ai-icons.js AGENT_GLYPHS) — keep them in sync.
	 *
	 * `match` entries are case-insensitive User-Agent substrings; the first
	 * matching agent wins, so more specific tokens must precede generic ones.
	 *
	 * @return array[]
	 */
	public static function catalog() {
		$agents = [
			[ 'id' => 'chatgpt',      'name' => 'ChatGPT',            'company' => 'OpenAI',       'category' => 'Chat AI',   'color' => '#007A59', 'ua' => 'ChatGPT-User/*',      'match' => [ 'ChatGPT-User' ] ],
			[ 'id' => 'oai-search',   'name' => 'OAI-SearchBot',      'company' => 'OpenAI',       'category' => 'Search AI', 'color' => '#1D9F7E', 'ua' => 'OAI-SearchBot/*',     'match' => [ 'OAI-SearchBot' ] ],
			[ 'id' => 'gptbot',       'name' => 'GPTBot',             'company' => 'OpenAI',       'category' => 'Crawler',   'color' => '#10A37F', 'ua' => 'GPTBot/*',            'match' => [ 'GPTBot' ] ],
			[ 'id' => 'claude-user',  'name' => 'Claude-User',        'company' => 'Anthropic',    'category' => 'Chat AI',   'color' => '#C7633D', 'ua' => 'Claude-User/*',       'match' => [ 'Claude-User', 'Claude-Web', 'Claude-SearchBot' ] ],
			[ 'id' => 'claudebot',    'name' => 'ClaudeBot',          'company' => 'Anthropic',    'category' => 'Crawler',   'color' => '#D97757', 'ua' => 'ClaudeBot/*',         'match' => [ 'ClaudeBot', 'anthropic-ai' ] ],
			[ 'id' => 'perplexity',   'name' => 'PerplexityBot',      'company' => 'Perplexity',   'category' => 'Search AI', 'color' => '#1FB8CD', 'ua' => 'PerplexityBot/*',     'match' => [ 'PerplexityBot', 'Perplexity-User' ] ],
			[ 'id' => 'google-ext',   'name' => 'Google-Extended',    'company' => 'Google',       'category' => 'Search AI', 'color' => '#4285F4', 'ua' => 'Google-Extended',     'match' => [ 'Google-Extended', 'GoogleOther', 'Google-CloudVertexBot' ] ],
			[ 'id' => 'applebot-ext', 'name' => 'Applebot-Extended',  'company' => 'Apple',        'category' => 'Search AI', 'color' => '#64748B', 'ua' => 'Applebot-Extended',   'match' => [ 'Applebot-Extended' ] ],
			[ 'id' => 'cursor',       'name' => 'Cursor',             'company' => 'Anysphere',    'category' => 'Dev Tool',  'color' => '#8B5CF6', 'ua' => 'Cursor/*',            'match' => [ 'Cursor/' ] ],
			[ 'id' => 'copilot',      'name' => 'Copilot',            'company' => 'Microsoft',    'category' => 'Dev Tool',  'color' => '#0078D4', 'ua' => 'Copilot-User/*',      'match' => [ 'Copilot' ] ],
			[ 'id' => 'meta-ai',      'name' => 'Meta-ExternalAgent', 'company' => 'Meta',         'category' => 'Crawler',   'color' => '#1877F2', 'ua' => 'Meta-ExternalAgent/*','match' => [ 'meta-externalagent', 'meta-externalfetcher', 'FacebookBot' ] ],
			[ 'id' => 'youbot',       'name' => 'YouBot',             'company' => 'You.com',      'category' => 'Search AI', 'color' => '#7C3AED', 'ua' => 'YouBot/*',            'match' => [ 'YouBot' ] ],
			[ 'id' => 'ccbot',        'name' => 'CCBot',              'company' => 'Common Crawl', 'category' => 'Crawler',   'color' => '#94A3B8', 'ua' => 'CCBot/*',             'match' => [ 'CCBot' ] ],
			[ 'id' => 'bytespider',   'name' => 'Bytespider',         'company' => 'ByteDance',    'category' => 'Crawler',   'color' => '#EC4899', 'ua' => 'Bytespider/*',        'match' => [ 'Bytespider' ] ],
			[ 'id' => 'duck-assist',  'name' => 'DuckAssistBot',      'company' => 'DuckDuckGo',   'category' => 'Search AI', 'color' => '#DE5833', 'ua' => 'DuckAssistBot/*',     'match' => [ 'DuckAssistBot' ] ],
		];

		/**
		 * Extend or prune the detected AI agent list. Added agents render with a
		 * generic bot icon in the dashboard unless a glyph for the id exists.
		 *
		 * @param array[] $agents
		 */
		return apply_filters( 'betterdocs_ai_traffic_agents', $agents );
	}

	/**
	 * Catalog id for the given User-Agent, or null when it is not a known AI agent.
	 *
	 * @param string $useragent
	 * @return string|null
	 */
	public static function match_agent( $useragent ) {
		if ( $useragent === '' ) {
			return null;
		}
		foreach ( self::catalog() as $agent ) {
			foreach ( (array) $agent['match'] as $token ) {
				if ( false !== stripos( $useragent, $token ) ) {
					return $agent['id'];
				}
			}
		}
		return null;
	}

	/**
	 * Detect an AI agent on a singular-doc request and record the fetch.
	 */
	public function maybe_record() {
		if ( ! is_singular( 'docs' ) || is_preview() ) {
			return;
		}

		// Respect the AI-traffic tracking toggle (default on). (#15)
		if ( betterdocs()->settings->get( 'track_ai_traffic', true ) == false ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$agent_id  = self::match_agent( $useragent );
		if ( $agent_id === null ) {
			return;
		}

		$this->record( $agent_id, (int) $post_id );
	}

	/**
	 * Atomic hourly upsert — concurrent bot hits collapse into one row via the
	 * (agent, post, day, hour) unique key, and last_fetch tracks the newest hit.
	 *
	 * @param string $agent_id Catalog agent id.
	 * @param int    $post_id  Doc post id.
	 */
	public function record( $agent_id, $post_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}betterdocs_analytics_ai_daily
					( agent, post_id, fetches, stat_date, stat_hour, last_fetch )
				VALUES ( %s, %d, 1, %s, %d, %s )
				ON DUPLICATE KEY UPDATE fetches = fetches + 1, last_fetch = VALUES( last_fetch )",
				$agent_id,
				$post_id,
				gmdate( 'Y-m-d' ),
				(int) gmdate( 'G' ),
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}
}
