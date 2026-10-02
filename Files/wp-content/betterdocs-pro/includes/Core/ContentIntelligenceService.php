<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Client for the betterdocs-chat-ai Content Intelligence API (v2.0).
 *
 * Powers the cloud modules (Gaps, Duplicates). Standalone by design (ADR-v2-04):
 * when the AI Chatbot add-on is active it reuses that add-on's already-minted
 * bearer token + encrypted key + Qdrant collection (so we never double-embed);
 * otherwise it mints its own token from the Pro license. Detection is cloud-side
 * and CPU-only, so no API key is needed to READ results — only the embedding of
 * query signals uses the site's own key (forwarded encrypted, same transport as
 * the chatbot).
 *
 * Flow (weekly + on-demand):
 *   1. push recent zero-result search keywords as query signals
 *   2. trigger analysis
 *   3. fetch results and mirror gap/duplicate rows into
 *      {prefix}betterdocs_analytics_insights (source = 'cloud')
 *
 * ---------------------------------------------------------------------------
 * Two engines, one screen
 * ---------------------------------------------------------------------------
 * Gaps do not require the AI Chatbot add-on. When it is present, detection is
 * semantic and runs in the cloud (above). When it is not, {@see LocalGapDetector}
 * produces the same rows on-site from the same search analytics, lexically.
 *
 * The split is decided by where document vectors can come from, not by what the
 * user has paid for. Nothing in this plugin embeds documents — the ADR-v2-04
 * "standalone sync client" was never written, so the Qdrant collection is
 * populated exclusively by the chatbot add-on. Without it the cloud holds zero
 * doc vectors, `nearest_doc_cosine()` returns 0.0 for every query, and every
 * cluster clears the coverage gate as a confident gap. Cloud mode with no
 * documents is not degraded, it is wrong, so {@see gap_mode()} refuses to enter
 * it and {@see ingest_results()} rejects a payload that reports no posts.
 *
 * The modes are exclusive. Running both would list the same topic twice under
 * two different scores, and the source column exists so each engine only ever
 * prunes its own rows.
 */
class ContentIntelligenceService {
	const API_URL        = 'http://dev-ai-chatbot.betterdocs.co';
	const TOKEN_OPT      = 'betterdocs_ci_bearer_token';
	const LAST_SYNC_OPT  = 'betterdocs_ci_last_sync';
	const MODE_OPT       = 'betterdocs_ci_gap_mode';
	const CLOUD_POSTS_OPT = 'betterdocs_ci_cloud_posts';
	const SYNC_HOOK      = 'betterdocs_ci_cloud_sync';
	// Same transport key the chatbot uses so the service can decrypt (parity required).
	const ENCRYPTION_KEY = '0kXsYZmsHgvIB85miXWlq3nigYYD4PktOSVvOs3vlbA=';

	/** @var AnalyticsInsightStore */
	protected $store;

	public function __construct() {
		$this->store = new AnalyticsInsightStore();
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::SYNC_HOOK, [ $this, 'run_sync' ] );
	}

	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( self::SYNC_HOOK ) ) {
			as_schedule_recurring_action( time() + 3 * HOUR_IN_SECONDS, WEEK_IN_SECONDS, self::SYNC_HOOK, [], 'betterdocs' );
		}
	}

	/**
	 * Bare, www-stripped host — must match the domain bound into the JWT.
	 */
	public function get_domain() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return preg_replace( '/^www\./', '', (string) $host );
	}

	/**
	 * Is the AI Chatbot add-on active and configured? If so we reuse its token,
	 * key, and Qdrant collection rather than embedding a second copy.
	 */
	public function chatbot_active() {
		return class_exists( '\WPDeveloper\BetterDocsChatbot\Core\AIChatbot' );
	}

	/**
	 * Is the chatbot both installed AND holding an embedding key?
	 *
	 * The class alone is not enough. An activated-but-unconfigured chatbot has
	 * embedded nothing, so the collection is empty and cloud gaps would be
	 * fabricated from a zero doc set — see the class docblock.
	 */
	public function chatbot_configured() {
		return $this->chatbot_active() && '' !== (string) betterdocs()->settings->get( 'ai_chatbot_api_key', '' );
	}

	/**
	 * Which engine detects gaps on this site: 'cloud' (semantic) or 'local'
	 * (lexical, from search analytics).
	 *
	 * @return string
	 */
	public function gap_mode() {
		$mode = $this->cloud_gaps_available() ? 'cloud' : 'local';

		/**
		 * Force a gap detection engine.
		 *
		 * Mainly for support and testing — a site can be pinned to 'local' to
		 * compare the two engines side by side. Pinning to 'cloud' without the
		 * chatbot yields gaps computed against no documents; see the class
		 * docblock before doing it.
		 *
		 * @param string $mode 'cloud' | 'local'
		 */
		$mode = (string) apply_filters( 'betterdocs_ci_gap_mode', $mode );
		return 'cloud' === $mode ? 'cloud' : 'local';
	}

	/**
	 * Can this site produce semantic gaps at all? Needs a chatbot to have
	 * embedded the documents, and a token to talk to the service.
	 *
	 * The key check is deliberately not the whole test. A key can go missing for
	 * reasons that have nothing to do with the collection — it was rotated, a
	 * settings save dropped it, an option was restored from an older backup —
	 * while the documents it embedded are still sitting in Qdrant. Gating on the
	 * key alone flips such a site to local, prunes its cloud gaps, and flips back
	 * the moment the key returns, so the Gaps screen churns because of an
	 * unrelated settings blip. Documents already embedded are what cloud mode
	 * actually needs; the key is only needed to embed *new* query signals.
	 */
	public function cloud_gaps_available() {
		if ( ! $this->chatbot_active() || '' === (string) $this->get_token() ) {
			return false;
		}
		// A working chatbot setup ({@see chatbot_ready()}) is what makes the cloud
		// usable — the same signal chatbot_state() reads for 'ready', so gap_mode()
		// and the banner can never disagree (a working site is cloud mode, not a
		// locked "get the add-on"). Documents the cloud already holds keep it
		// available across a later key blip.
		return $this->chatbot_ready() || $this->cloud_has_documents();
	}

	/**
	 * Has the cloud ever analysed real documents for this site?
	 *
	 * Recorded from `stats.posts` on each ingest. Falls back to the presence of
	 * cloud-sourced insight rows when that has never been written — an install
	 * that was syncing happily before this option existed should not be demoted
	 * to lexical detection on upgrade just because the counter is new.
	 */
	public function cloud_has_documents() {
		$known = get_option( self::CLOUD_POSTS_OPT, null );
		if ( null !== $known ) {
			return (int) $known > 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE source = 'cloud' AND type IN ( 'gap', 'duplicate' )" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		) > 0;
	}

	/**
	 * A precise, user-facing readiness state for the AI Chatbot-powered cloud
	 * modules (semantic Gaps, Duplicates & Overlaps).
	 *
	 * Distinct from {@see gap_mode()}: gap_mode answers *which* engine runs and
	 * is what locks a module; this answers *why* the cloud engine is not running
	 * yet, which is the thing the UI has to actually tell the user. Before this
	 * existed, every not-cloud site — no add-on, add-on unlicensed, licensed but
	 * unconfigured, configured but not yet synced — collapsed into one silent
	 * "local mode / Needs the AI Chatbot add-on" message, so an installed,
	 * licensed chatbot looked identical to no chatbot at all and the screen only
	 * ever cleared after the first successful sync. The states are ordered from
	 * furthest-from-ready to ready; the first matching one wins.
	 *
	 *   'none'         Add-on not installed/active. On Free+Pro the cloud modules
	 *                  are simply not part of the plan — keep the add-on hint.
	 *   'unlicensed'   Add-on active but not licensed — the chatbot OR the Pro
	 *                  licence is not 'valid' (never activated, expired, invalid).
	 *                  CTA: the licence tab.
	 *   'unconfigured' Licensed, but the chatbot is not a working setup yet —
	 *                  disabled, or its provider key does not check out ({@see
	 *                  chatbot_ready()}). A freshly-activated licence lands here.
	 *                  CTA: the AI Chatbot setup tab.
	 *   'ready'        The chatbot is a working setup (or the cloud already holds
	 *                  this site's rows). No banner — the modules show their own
	 *                  results and "Sync now" empty states. There is deliberately
	 *                  no separate "first sync pending" state: gating on the CI
	 *                  cloud sync left set-up sites stuck under a permanent
	 *                  "first sync in progress" strip.
	 *
	 * Detection is deliberately local and side-effect-free (no remote token mint):
	 * it runs on every Content Intelligence page load and REST call.
	 *
	 * Licensing is decided by the licence *status* alone (chatbot AND Pro must be
	 * 'valid') — never the bearer token, which the chatbot mints lazily during
	 * setup, so gating on it misreads a fresh valid licence as unlicensed. Whether
	 * the chatbot is a *working* setup is delegated to {@see chatbot_ready()}, which
	 * reads the chatbot's own key-valid verdict rather than a fragile option string.
	 *
	 * @return string One of none|unlicensed|unconfigured|ready.
	 */
	public function chatbot_state() {
		if ( ! $this->chatbot_active() ) {
			return 'none';
		}

		// Licence status only — never the token (minted lazily; see docblock).
		$chatbot_licensed = 'valid' === (string) get_option( 'betterdocs_chatbot_software__license_status', '' );
		$pro_licensed     = 'valid' === (string) get_option( 'betterdocs_pro_software__license_status', '' );
		if ( ! $chatbot_licensed || ! $pro_licensed ) {
			return 'unlicensed';
		}

		// Working setup, or the cloud already holds this site's rows → 'ready', no
		// banner. We deliberately do NOT gate this on a completed CI cloud sync:
		// the sync is a scheduled/on-demand job, and blocking on it left a set-up
		// site stuck under a persistent "first sync in progress" strip even after
		// it had data. Once the chatbot works, the modules show their own results
		// and their own "Sync now" empty states — the Content IQ screen has nothing
		// left to nag about.
		if ( $this->chatbot_ready() || $this->cloud_has_documents() ) {
			return 'ready';
		}

		// Licensed, but the chatbot is not a working setup yet (disabled, or its
		// provider key does not check out). CTA: finish setup.
		return 'unconfigured';
	}

	/**
	 * Is the AI Chatbot a working, finished setup — enabled with a provider key
	 * that actually checks out?
	 *
	 * Reads the chatbot's OWN readiness verdict rather than poking option strings.
	 * This is deliberate: `betterdocs_ai_chatbot_embedding_signature` proved
	 * unreliable — it survives a provider switch (so it reads "set up" on a site
	 * whose current key is broken) and is absent on some builds (so a genuinely
	 * synced site reads "finish setup", the exact bug this fixes).
	 * `AIChatbot::is_api_key_valid()` is the cached result of a live key check
	 * against the active provider, and `is_ai_chatbot_bg_complete === 'complete'`
	 * means the background embed finished — either is a trustworthy "it works".
	 * Older chatbot builds without those fall back to a minted token / embed
	 * signature.
	 *
	 * @return bool
	 */
	public function chatbot_ready() {
		if ( ! $this->chatbot_active() ) {
			return false;
		}

		$cb = '\WPDeveloper\BetterDocsChatbot\Core\AIChatbot';

		$enabled = method_exists( $cb, 'is_ai_chatbot_enabled' )
			? (bool) $cb::is_ai_chatbot_enabled()
			: (bool) betterdocs()->settings->get( 'enable_ai_chatbot', false );
		if ( ! $enabled ) {
			return false;
		}

		if ( method_exists( $cb, 'is_api_key_valid' ) ) {
			if ( (bool) $cb::is_api_key_valid() ) {
				return true;
			}
			// The key check can be stale/unrun even on a working site; a completed
			// background embed is the second trustworthy "it works" signal.
			return 'complete' === (string) get_option( 'is_ai_chatbot_bg_complete', '' );
		}

		// Older chatbot builds: no readiness helper — fall back to a real token or
		// an embed signature.
		$token = (string) get_option( 'ai_chatbot_request_bareer_token', '' );
		return ( '' !== $token && 'Invalid License' !== $token )
			|| '' !== (string) get_option( 'betterdocs_ai_chatbot_embedding_signature', '' );
	}

	/**
	 * Credentials for a generative AI action (gap title drafting).
	 *
	 * Distinct from {@see openai_data()}, which carries the *embedding* key and
	 * must stay pinned to whatever model built the Qdrant collection — swapping
	 * that one would put query vectors in a different space from the documents
	 * and silently invalidate every distance.
	 *
	 * This ladder exists so nobody is asked for a second key they have already
	 * given us: a site running the chatbot has a working key on file, and making
	 * it also fill in AI Content Suite before it can draft a gap title is busywork.
	 * Chatbot first when configured, AI Content Suite otherwise.
	 *
	 * @return array{platform:string,api_key:string,model:string,source:string}|null
	 */
	public function ai_action_credentials() {
		if ( $this->chatbot_configured() ) {
			$key   = (string) betterdocs()->settings->get( 'ai_chatbot_api_key', '' );
			$model = (string) betterdocs()->settings->get( 'ai_chatbot_chat_model', 'gpt-4o-mini' );
			return [
				'platform' => $this->platform_for_model( $model ),
				'api_key'  => $key,
				'model'    => $model,
				'source'   => 'chatbot',
			];
		}

		$factory  = new \WPDeveloper\BetterDocs\AI\ProviderFactory( betterdocs()->settings );
		$platform = $factory->active_platform();
		$key      = (string) $factory->api_key_for( $platform );
		if ( '' === $key ) {
			return null;
		}
		return [
			'platform' => $platform,
			'api_key'  => $key,
			'model'    => (string) $factory->active_model( $platform ),
			'source'   => 'content_suite',
		];
	}

	/**
	 * Infer the provider platform from a chat model id.
	 *
	 * The chatbot stores a bare model name with no platform beside it, so the
	 * name is the only thing available. Prefix matching covers every model the
	 * chatbot settings offer; anything unrecognised falls back to OpenAI, which
	 * is what that field has always held.
	 */
	protected function platform_for_model( $model ) {
		$model = mb_strtolower( (string) $model );
		$map   = [
			'gemini'  => 'gemini',
			'claude'  => 'claude',
			'deepseek'=> 'deepseek',
		];
		foreach ( $map as $prefix => $platform ) {
			if ( 0 === strpos( $model, $prefix ) ) {
				return $platform;
			}
		}
		return 'openai';
	}

	/**
	 * Base URL of the AI service. Overridable for local/staging via the
	 * BETTERDOCS_CI_API_URL constant or the betterdocs_ci_api_url filter.
	 */
	public function api_url() {
		$url = defined( 'BETTERDOCS_CI_API_URL' ) ? BETTERDOCS_CI_API_URL : self::API_URL;
		return untrailingslashit( (string) apply_filters( 'betterdocs_ci_api_url', $url ) );
	}

	/**
	 * The site's OpenAI/embedding config, encrypted for transport. Reuses the
	 * chatbot's shared settings when present; returns null when no key is set
	 * (cloud detection still runs — only query-signal embedding needs it).
	 */
	public function openai_data() {
		$raw_key = betterdocs()->settings->get( 'ai_chatbot_api_key', '' );
		if ( empty( $raw_key ) ) {
			return null;
		}
		return [
			'openai_api_key'         => $this->encrypt( $raw_key ),
			'openai_chat_model'      => betterdocs()->settings->get( 'ai_chatbot_chat_model', 'gpt-4o-mini' ),
			'openai_embedding_model' => betterdocs()->settings->get( 'ai_chatbot_embed_model', 'text-embedding-3-small' ),
		];
	}

	/**
	 * AES-256-CBC + HMAC-SHA256, base64( iv . hmac . ciphertext ) — the exact
	 * envelope the service's decrypt() expects.
	 */
	protected function encrypt( $plaintext ) {
		$key            = base64_decode( self::ENCRYPTION_KEY );
		$cipher         = 'AES-256-CBC';
		$iv             = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $cipher ) );
		$ciphertext_raw = openssl_encrypt( $plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv );
		$hmac           = hash_hmac( 'sha256', $ciphertext_raw, $key, true );
		return base64_encode( $iv . $hmac . $ciphertext_raw );
	}

	/**
	 * A valid bearer token: the chatbot's when present, else our own (minted from
	 * the Pro license and cached). Returns '' when no license is available.
	 */
	public function get_token() {
		if ( $this->chatbot_active() ) {
			$shared = get_option( 'ai_chatbot_request_bareer_token' );
			if ( ! empty( $shared ) ) {
				return $shared;
			}
		}

		$token = get_option( self::TOKEN_OPT );
		if ( ! empty( $token ) ) {
			return $token;
		}
		return $this->mint_token();
	}

	protected function mint_token() {
		$license = get_option( 'betterdocs_pro_software__license' );
		if ( empty( $license ) ) {
			return '';
		}
		$response = wp_remote_post( $this->api_url() . '/token/new', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'license' => $license,
				'item_id' => defined( 'BETTERDOCS_PRO_SL_ITEM_ID' ) ? BETTERDOCS_PRO_SL_ITEM_ID : 342422,
				'url'     => $this->get_domain(),
				'version' => defined( 'BETTERDOCS_PRO_VERSION' ) ? BETTERDOCS_PRO_VERSION : '',
			] ),
			'timeout' => 30,
		] );
		if ( is_wp_error( $response ) ) {
			return '';
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['status'], $body['token'] ) && 'success' === $body['status'] ) {
			update_option( self::TOKEN_OPT, $body['token'] );
			return $body['token'];
		}
		return '';
	}

	/**
	 * Authenticated POST/GET to a service endpoint. Returns the decoded body, or
	 * a WP_Error. Re-mints the token once on a 401.
	 */
	protected function request( $path, $args = [], $method = 'POST', $is_retry = false ) {
		$token = $this->get_token();
		if ( empty( $token ) ) {
			return new \WP_Error( 'no_token', 'No service token available' );
		}

		$url  = $this->api_url() . $path;
		$opts = [
			'method'  => $method,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			],
			'timeout' => 60,
		];
		if ( 'GET' === $method && ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		} elseif ( ! empty( $args ) ) {
			$opts['body'] = wp_json_encode( $args );
		}

		$response = wp_remote_request( $url, $opts );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 401 === $code && ! $is_retry ) {
			// Token expired/invalid — force a re-mint and retry once.
			delete_option( self::TOKEN_OPT );
			return $this->request( $path, $args, $method, true );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * The site's recent zero-result search keywords — the cheapest, highest-signal
	 * gap input (collected by the Phase 0 search pipeline).
	 *
	 * `weight` carries how many times readers actually searched the term. Gap
	 * demand is scored on that volume, not on how many different ways a topic
	 * happened to be phrased, so a term searched thousands of times ranks as the
	 * gap it is instead of counting as a single signal.
	 *
	 * Reads the Pro rollup first, then falls back to the legacy Free tables.
	 * Both are written on every search — `insert_search_keyword()` writes
	 * search_keyword/search_log synchronously in Free, while the rollup depends
	 * on Pro's aggregator cron having run. Without the fallback, a stalled cron
	 * leaves Gaps silently empty on a site whose search data is perfectly intact.
	 *
	 * @return array<int,array{text:string,intent:string,source:string,weight:int}>
	 */
	protected function collect_query_signals( $limit = 100 ) {
		$since = gmdate( 'Y-m-d', strtotime( '-90 days' ) );

		$rows = $this->zero_result_rollup( $since, $limit );
		if ( empty( $rows ) ) {
			$rows = $this->zero_result_legacy( $since, $limit );
		}

		$out = [];
		foreach ( (array) $rows as $r ) {
			$keyword = trim( (string) $r->keyword );
			if ( '' === $keyword ) {
				continue;
			}
			$out[] = [
				// The rollup caps keywords at varchar(191); the legacy column is
				// TEXT, so match the tighter of the two for consistent hashing.
				'text'   => mb_substr( $keyword, 0, 191 ),
				'intent' => 'zero_result',
				'source' => 'search',
				'weight' => max( 1, (int) $r->zeroes ),
			];
		}

		// A zero-result search is the loudest miss but not the only one. Two more
		// signals carry real reader intent, and both are text we can embed into the
		// same space as the docs:
		//
		//   low_yield — searched, got results, but so few that the KB clearly only
		//               brushes the topic. Distinct from zero_result: something
		//               matched, so the reader isn't told "nothing found", they are
		//               handed a thin answer and leave.
		//   feedback  — an unhappy reader typing what they actually wanted. The
		//               highest-intent text in the whole product; nobody writes a
		//               complaint about a topic they don't care about.
		//
		// Merged, not concatenated: the same wording can legitimately arrive from
		// more than one source, and clustering the duplicate twice would inflate a
		// topic's apparent demand.
		$out = $this->merge_signals(
			$out,
			$this->low_yield_signals( $since, $limit ),
			$this->feedback_signals( $since, $limit )
		);

		/**
		 * Filter the query signals sent to the analysis service.
		 *
		 * The escape hatch for signals this plugin cannot see — an on-site chat
		 * transcript, a help-desk export, a support inbox. Entries must match the
		 * shape below; anything malformed is dropped by merge_signals().
		 *
		 * @param array  $out   { text, intent, source, weight }
		 * @param string $since Y-m-d lower bound of the collection window.
		 */
		return apply_filters( 'betterdocs_ci_query_signals', $out, $since );
	}

	/**
	 * Merge signal lists, collapsing duplicates by normalized text.
	 *
	 * Weight is summed rather than maxed: the same phrase failing as a search AND
	 * showing up in a complaint is genuinely more demand than either alone. The
	 * surviving row keeps the intent of its strongest contributor, so a topic that
	 * is mostly zero-result still reads as zero-result in the payload.
	 *
	 * @param array ...$lists
	 * @return array<int,array{text:string,intent:string,source:string,weight:int}>
	 */
	protected function merge_signals( ...$lists ) {
		$byKey = [];
		foreach ( $lists as $list ) {
			foreach ( (array) $list as $sig ) {
				$text = isset( $sig['text'] ) ? trim( (string) $sig['text'] ) : '';
				if ( '' === $text ) {
					continue;
				}
				$key    = mb_strtolower( $text );
				$weight = max( 1, (int) ( $sig['weight'] ?? 1 ) );

				if ( ! isset( $byKey[ $key ] ) ) {
					$byKey[ $key ] = $sig;
					$byKey[ $key ]['text']   = $text;
					$byKey[ $key ]['weight'] = $weight;
					continue;
				}

				// Stronger contributor owns the label.
				if ( $weight > (int) $byKey[ $key ]['weight'] ) {
					$byKey[ $key ]['intent'] = $sig['intent'] ?? $byKey[ $key ]['intent'];
					$byKey[ $key ]['source'] = $sig['source'] ?? $byKey[ $key ]['source'];
				}
				$byKey[ $key ]['weight'] += $weight;
			}
		}

		$out = array_values( $byKey );
		usort( $out, function ( $a, $b ) {
			return (int) $b['weight'] <=> (int) $a['weight'];
		} );
		return $out;
	}

	/**
	 * Searches that returned results, but too few to be a real answer.
	 *
	 * The "high-bounce" half of the PRD's signal list, expressed with data that
	 * actually exists. The obvious implementation — searches with no click-through
	 * — is NOT usable: `click_through_count` is created and preserved but never
	 * computed (no frontend click event exists yet), so on a real site it is 0 for
	 * every row and "no clicks" would select the entire search log. Guarded on
	 * `site_has_click_data()` for the day that lands; until then thin-result
	 * volume is the honest proxy.
	 *
	 * @param string $since Y-m-d
	 * @param int    $limit
	 */
	protected function low_yield_signals( $since, $limit ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_search';

		// Only rows that found something (else it is already a zero_result signal)
		// and whose average hit count sits at or below the thin-results bar.
		$max_results = (float) apply_filters( 'betterdocs_ci_low_yield_max_results', 2.0 );

		$clause = 'zero_result_count = 0 AND results_count_avg > 0 AND results_count_avg <= %f';
		$params = [ $since, $max_results, $limit ];

		if ( $this->site_has_click_data() ) {
			// Real CTR available: a search with results and no click is a stronger
			// miss than a thin one, so widen the net to include it.
			$clause   = '( ' . $clause . ' OR ( results_count_avg > 0 AND click_through_count = 0 ) )';
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT keyword, SUM( search_count ) AS hits
				FROM {$table}
				WHERE stat_date >= %s AND {$clause}
				GROUP BY keyword_hash, keyword
				HAVING hits > 0
				ORDER BY hits DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				...$params
			)
		);

		$out = [];
		foreach ( (array) $rows as $r ) {
			$keyword = trim( (string) $r->keyword );
			if ( '' === $keyword ) {
				continue;
			}
			$out[] = [
				'text'   => mb_substr( $keyword, 0, 191 ),
				'intent' => 'low_yield',
				'source' => 'search',
				'weight' => max( 1, (int) $r->hits ),
			];
		}
		return $out;
	}

	/**
	 * Whether this site has genuine search click-through data.
	 *
	 * `click_through_count` is written by nothing today, so a plain
	 * `click_through_count = 0` test would match every row ever recorded. One
	 * cheap probe distinguishes "no clicks" from "clicks were never measured".
	 */
	protected function site_has_click_data() {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_search';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE click_through_count > 0 LIMIT 1" ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Unhappy feedback comments — readers stating, in their own words, what the
	 * documentation failed to give them.
	 *
	 * Only `sad` is collected. `normal` skews to "fine but…" notes about tone or
	 * formatting, which cluster into topics no article would fix, and `happy`
	 * comments describe content that already exists. Very short comments ("no",
	 * "bad") are dropped: they carry no topic and would embed as noise near the
	 * centre of the space, where they can bridge unrelated clusters.
	 *
	 * @param string $since Y-m-d
	 * @param int    $limit
	 */
	/**
	 * Does this comment name a SUBJECT, or only judge the article?
	 *
	 * A gap is a topic somebody should write about. "Needs a video", "still get
	 * an error", "very helpful thanks" are real feedback and completely useless
	 * here — they carry no subject, so nothing can be written in response. Left
	 * in, they cluster anyway: they share a register (short, first-person, about
	 * the page rather than the field), which reads to the embedding as a coherent
	 * topic. On the demo site they produced a gap scoring 88 demand whose three
	 * sample queries were "Very helpful, thanks!", "I still get an error
	 * following this." and "Needs a video." — indistinguishable, by cohesion,
	 * from a genuine cluster about API rate limiting.
	 *
	 * Cohesion cannot filter it (0.688 for that cluster against 0.690 for the
	 * real one) and neither can length. What separates them is whether any word
	 * survives once the meta-vocabulary is removed: complaints about a doc's form
	 * reduce to nothing, complaints about missing content keep their nouns —
	 * "SAML", "failover", "regions".
	 *
	 * Deliberately a small, editable list rather than anything cleverer: the
	 * alternative is an LLM call on every comment, and this runs in the no-key
	 * detection path.
	 */
	protected function comment_names_a_topic( $comment ) {
		$stop = apply_filters( 'betterdocs_ci_feedback_stopwords', [
			// grammar
			'i', 'a', 'an', 'the', 'this', 'that', 'these', 'those', 'it', 'its', 'is', 'was',
			'be', 'been', 'am', 'are', 'were', 'to', 'of', 'in', 'on', 'at', 'for', 'with',
			'and', 'or', 'but', 'not', 'no', 'so', 'if', 'as', 'by', 'from', 'you', 'your',
			'we', 'us', 'our', 'my', 'me', 'do', 'does', 'did', 'can', 'could', 'would',
			'should', 'will', 'get', 'got', 'have', 'has', 'had', 'here', 'there', 'more',
			'some', 'any', 'all', 'still', 'just', 'very', 'too', 'much', 'please', 'need',
			'needs', 'needed', 'want', 'wants', 'following', 'follow', 'when', 'what', 'how',
			// sentiment — judging the article, not naming a subject
			'thanks', 'thank', 'helpful', 'unhelpful', 'great', 'good', 'bad', 'nice',
			'useless', 'awesome', 'terrible', 'love', 'hate', 'confusing', 'confused',
			'unclear', 'clear', 'wrong', 'right', 'better', 'worse', 'work', 'works',
			'working', 'broken', 'error', 'errors', 'issue', 'issues', 'problem', 'problems',
			'perfect', 'excellent', 'brilliant', 'amazing', 'fantastic', 'poor', 'worst',
			'best', 'simple', 'easy', 'hard', 'difficult', 'quick', 'fast', 'slow',
			'super', 'really', 'quite', 'walkthrough', 'overview', 'writeup',
			// form — about the page's presentation, not its subject
			'video', 'videos', 'screenshot', 'screenshots', 'image', 'images', 'picture',
			'pictures', 'diagram', 'diagrams', 'font', 'layout', 'typo', 'typos', 'link',
			'links', 'page', 'pages', 'article', 'articles', 'doc', 'docs', 'documentation',
			'guide', 'guides', 'tutorial', 'section', 'step', 'steps', 'detail', 'details',
			'explain', 'explains', 'explained', 'explanation', 'example', 'examples',
			'information', 'info', 'content', 'read', 'find', 'found', 'cannot', 'nothing',
			'where', 'why', 'about',
		] );

		$min_topical = (int) apply_filters( 'betterdocs_ci_feedback_min_topical_words', 2 );

		$words = preg_split( '/[^a-z0-9\-]+/', mb_strtolower( $comment ), -1, PREG_SPLIT_NO_EMPTY );
		$topical = 0;
		foreach ( (array) $words as $w ) {
			if ( mb_strlen( $w ) < 3 || in_array( $w, $stop, true ) ) {
				continue;
			}
			$topical++;
			if ( $topical >= $min_topical ) {
				return true;
			}
		}
		return false;
	}

	protected function feedback_signals( $since, $limit ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_feedback';

		$min_chars = (int) apply_filters( 'betterdocs_ci_feedback_min_chars', 12 );

		// Weight is read downstream as *search volume*, and the two signals count
		// different things: a zero-result weight is how many people searched (here,
		// up to 968), while a feedback weight is how many people typed the exact
		// same sentence — almost always 1. On one linear scale feedback can never
		// compete: a four-comment cluster scores demand 23 against a bar of 50, and
		// the singleton promote bar sits at 5% of total volume. Left raw, the signal
		// is collected, embedded, and then quietly ignored.
		//
		// So convert to search-equivalents rather than shipping a number that means
		// something else. A written complaint is far stronger evidence of unmet need
		// than one silent search — the reader hit the gap, then cared enough to say
		// so. 25 puts a four-comment cluster around demand 67, which competes with a
		// mid-volume keyword without drowning genuinely high-volume ones.
		$weight_factor = max( 1, (int) apply_filters( 'betterdocs_ci_feedback_weight_factor', 25 ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment, COUNT(*) AS mentions
				FROM {$table}
				WHERE feeling = 'sad'
				  AND comment IS NOT NULL AND comment <> ''
				  AND CHAR_LENGTH( comment ) >= %d
				  AND created_at >= %s
				GROUP BY comment
				ORDER BY mentions DESC, id DESC
				LIMIT %d",
				$min_chars,
				$since . ' 00:00:00',
				$limit
			)
		);

		$out = [];
		foreach ( (array) $rows as $r ) {
			$comment = trim( wp_strip_all_tags( (string) $r->comment ) );
			if ( '' === $comment || ! $this->comment_names_a_topic( $comment ) ) {
				continue;
			}
			$out[] = [
				// Same 191 cap as search keywords so one hashing rule covers every
				// signal; a longer complaint still embeds meaningfully from its
				// opening sentence.
				'text'   => mb_substr( $comment, 0, 191 ),
				'intent' => 'feedback',
				'source' => 'feedback',
				// Search-equivalents, not comment count — see $weight_factor above.
				'weight' => max( 1, (int) $r->mentions ) * $weight_factor,
			];
		}
		return $out;
	}

	/**
	 * Zero-result keywords from the Pro rollup (betterdocs_analytics_search).
	 */
	protected function zero_result_rollup( $since, $limit ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT keyword, SUM( zero_result_count ) AS zeroes
				FROM {$wpdb->prefix}betterdocs_analytics_search
				WHERE zero_result_count > 0 AND stat_date >= %s
				GROUP BY keyword_hash, keyword
				ORDER BY zeroes DESC
				LIMIT %d",
				$since,
				$limit
			)
		);
	}

	/**
	 * Zero-result keywords from the legacy Free tables, written since 3.9.4.
	 */
	protected function zero_result_legacy( $since, $limit ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT k.keyword AS keyword, SUM( l.not_found_count ) AS zeroes
				FROM {$wpdb->prefix}betterdocs_search_log l
				INNER JOIN {$wpdb->prefix}betterdocs_search_keyword k ON k.id = l.keyword_id
				WHERE l.not_found_count > 0 AND l.created_at >= %s
				GROUP BY l.keyword_id
				ORDER BY zeroes DESC
				LIMIT %d",
				$since,
				$limit
			)
		);
	}

	/**
	 * Does the site hold any signal a gap could be detected from?
	 *
	 * Lets the Gaps screen tell "nothing to analyse yet" apart from "analysed,
	 * found nothing" — two very different messages for the reader.
	 *
	 * Covers every signal collect_query_signals() actually reads, not just
	 * zero-result searches. A site whose readers always find *something* but keep
	 * complaining in feedback has plenty to analyse, and telling it "no reader
	 * searches to analyze yet" would be false.
	 *
	 * @return bool
	 */
	public function has_query_signals() {
		global $wpdb;
		$since = gmdate( 'Y-m-d', strtotime( '-90 days' ) );

		$rollup = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}betterdocs_analytics_search
				WHERE stat_date >= %s AND ( zero_result_count > 0 OR ( results_count_avg > 0 AND results_count_avg <= %f ) )",
				$since,
				(float) apply_filters( 'betterdocs_ci_low_yield_max_results', 2.0 )
			)
		);
		if ( $rollup > 0 ) {
			return true;
		}

		$legacy = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}betterdocs_search_log
				WHERE not_found_count > 0 AND created_at >= %s",
				$since
			)
		);
		if ( $legacy > 0 ) {
			return true;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}betterdocs_analytics_feedback
				WHERE feeling = 'sad' AND comment IS NOT NULL AND comment <> '' AND created_at >= %s",
				$since . ' 00:00:00'
			)
		) > 0;
	}

	/**
	 * Weekly (and manual) cloud sync: push signals → trigger analysis → ingest
	 * whatever results are already available. Because analysis is async, results
	 * from the PREVIOUS run are ingested each time (eventually consistent).
	 *
	 * Also starts the per-doc Health and Stale passes. Those have their own daily
	 * schedules, but "Analyze now" has to mean the whole screen refreshes: gaps
	 * were the only thing this ran, so a user staring at a red F on a fresh site
	 * could press it as often as they liked and the score would not move until the
	 * nightly pass. Both entry points are idempotent — each bails when its own
	 * pass is already walking — so a repeated press cannot stack scans.
	 *
	 * Returns a report of what actually happened so the REST layer can tell the
	 * user the truth instead of an unconditional success.
	 *
	 * @return array{mode:string,gaps:?int,scans:array<int,string>,errors:array<int,string>,last_sync:int}
	 */
	public function run_sync() {
		$mode = $this->gap_mode();
		$this->reconcile_mode( $mode );

		$gaps   = null;
		$errors = [];

		if ( 'local' === $mode ) {
			$gaps = (int) $this->run_local_gaps();
		} else {
			$errors = $this->run_cloud_sync();
		}

		$scans = $this->start_content_scans();
		$now   = time();
		update_option( self::LAST_SYNC_OPT, $now );

		return [
			'mode'      => $mode,
			'gaps'      => $gaps,
			'scans'     => $scans,
			'errors'    => $errors,
			'last_sync' => $now,
		];
	}

	/**
	 * Kick the Health and Stale walkers, returning the names of the ones started.
	 *
	 * Resolved through Pro's container rather than constructed here: both classes
	 * register Action Scheduler hooks in their constructor, and a second instance
	 * would register a second set and run every chunk twice.
	 *
	 * @return array<int,string>
	 */
	protected function start_content_scans() {
		$started = [];

		foreach ( [ 'health' => ContentHealthScorer::class, 'stale' => AnalyticsStaleScanner::class ] as $name => $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}

			$scanner = betterdocs()->container->get( $class );
			if ( ! $scanner instanceof $class ) {
				continue;
			}

			$scanner->start_scan();
			$started[] = $name;
		}

		return $started;
	}

	/**
	 * Detect gaps on-site, no cloud involved.
	 *
	 * Duplicates are not attempted here: unlike gaps, there is no honest lexical
	 * stand-in for "these two articles say the same thing" — near-duplicate prose
	 * routinely shares little vocabulary, and title overlap alone produces pairs
	 * that embarrass the product. The Duplicates screen stays a chatbot feature
	 * and says so, rather than shipping a worse version of itself.
	 */
	protected function run_local_gaps() {
		return ( new LocalGapDetector() )->run( $this->collect_query_signals() );
	}

	/**
	 * The original cloud path: ingest last run's results, push signals, trigger
	 * the next analysis.
	 *
	 * Collects rather than discards the transport errors. Every call here can fail
	 * on a missing token, a network error or a 4xx from the service, and swallowing
	 * that is what let a sync whose every request failed still report success.
	 *
	 * @return array<int,string> Human-readable failures; empty when the run was clean.
	 */
	protected function run_cloud_sync() {
		$domain = $this->get_domain();
		$errors = [];

		// 1. Ingest whatever the last analysis produced.
		$ingested = $this->ingest_results();
		if ( is_wp_error( $ingested ) ) {
			$errors[] = $ingested->get_error_message();
		}

		// 2. Push fresh query signals (needs the embedding key).
		$openai = $this->openai_data();
		$signals = $this->collect_query_signals();
		if ( $openai && ! empty( $signals ) ) {
			$pushed = $this->request( '/v1/insights/query-signals', [
				'domain'      => $domain,
				'queries'     => $signals,
				'openai_data' => $openai,
			] );
			if ( is_wp_error( $pushed ) ) {
				$errors[] = $pushed->get_error_message();
			}
		}

		// 3. Trigger a fresh analysis (rate-limited server-side to 1/day).
		$analyzed = $this->request( '/v1/insights/analyze', [ 'domain' => $domain ] );
		if ( is_wp_error( $analyzed ) ) {
			$errors[] = $analyzed->get_error_message();
		}

		return $errors;
	}

	/**
	 * Clear out the other engine's undecided rows when the mode changes.
	 *
	 * A site that installs or removes the chatbot switches engine, and the rows
	 * the previous one left behind are not merely stale — they were scored on a
	 * different scale. Left in place the screen mixes cosine-derived confidence
	 * with token-overlap confidence and sorts them against each other.
	 *
	 * Only `new` rows go. An accepted or dismissed gap records a decision a human
	 * made about a topic, and that decision survives a change of detector.
	 */
	protected function reconcile_mode( $mode ) {
		$previous = (string) get_option( self::MODE_OPT, '' );
		if ( $previous === $mode ) {
			return;
		}
		update_option( self::MODE_OPT, $mode );
		if ( '' === $previous ) {
			return; // first run — nothing from a previous engine to clear
		}

		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';
		$stale = ( 'cloud' === $mode ) ? 'local' : 'cloud';
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE type = 'gap' AND source = %s AND status = 'new'", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$stale
		) );
	}

	/**
	 * Pull the latest results and mirror gap/duplicate rows into the local
	 * insights table (source = 'cloud'), pruning cloud rows that no longer appear.
	 *
	 * Hands the transport error back to the caller so a failed fetch can be
	 * reported. An empty-but-valid payload is not an error: analysis is async, so
	 * "nothing ready yet" is the normal state of an early run.
	 *
	 * @return \WP_Error|null
	 */
	public function ingest_results() {
		$domain = $this->get_domain();
		$body   = $this->request( '/v1/insights/results', [ 'domain' => $domain ], 'GET' );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		if ( empty( $body['results'] ) ) {
			return null;
		}
		$results = $body['results'];

		// A gap is "readers want this and no document covers it". The second half
		// is decided by the query centroid's cosine to the nearest document, and
		// with no documents in the collection that cosine is 0.0 — so every
		// cluster reads as maximally uncovered and arrives labelled High
		// confidence. That happens whenever the collection is empty or still
		// building (a fresh install, a re-embed in progress, a wiped collection),
		// and it is indistinguishable in the payload from a genuine finding.
		//
		// Bailing takes duplicates with it, which is what we want: zero posts means
		// zero pairs, so there is nothing to ingest, and returning early leaves the
		// rows from the last good run in place instead of pruning them away.
		//
		// An older service that doesn't report stats gets the benefit of the doubt;
		// an explicit zero does not.
		$posts_seen = isset( $results['stats']['posts'] ) ? (int) $results['stats']['posts'] : null;
		if ( null !== $posts_seen ) {
			// Also what cloud_has_documents() reads, so a later key blip cannot
			// demote a site whose collection is demonstrably populated.
			update_option( self::CLOUD_POSTS_OPT, $posts_seen );
		}
		if ( null !== $posts_seen && $posts_seen < 1 ) {
			return; // leave the existing rows alone rather than replace them with noise
		}

		$keep_gap = [];
		foreach ( (array) ( $results['gaps'] ?? [] ) as $gap ) {
			$queries = (array) ( $gap['queries'] ?? [] );
			$hash    = md5( 'gap:' . implode( '|', $queries ) );
			$keep_gap[] = $hash;
			$this->store->upsert( 'gap', $hash, (float) ( $gap['demand'] ?? 0 ), [
				'queries'       => $queries,
				'demand'        => (int) ( $gap['demand'] ?? 0 ),
				'signal_count'  => (int) ( $gap['signal_count'] ?? 0 ),
				'search_volume' => (int) ( $gap['search_volume'] ?? 0 ),
				'confidence'    => $gap['confidence'] ?? 'Low',
				'doc_distance'  => $gap['doc_distance'] ?? null,
				'cohesion'      => $gap['cohesion'] ?? null,
				'kind'          => $gap['kind'] ?? 'cluster',
				// Per-intent evidence: { zero_result: n, low_yield: n, feedback: n }.
				// This is what lets the UI say WHY a gap is credible instead of only
				// how loud it is. Null on an older service that doesn't send it.
				'signals'       => isset( $gap['signals'] ) && is_array( $gap['signals'] ) ? $gap['signals'] : null,
			], [ 'source' => 'cloud' ] );
		}
		$this->prune_cloud( 'gap', $keep_gap );

		$keep_dup = [];
		foreach ( (array) ( $results['duplicates'] ?? [] ) as $dup ) {
			$hash = $this->store->object_hash( 'duplicate', $dup['a_id'] ?? 0, $dup['b_id'] ?? 0 );
			$keep_dup[] = $hash;
			$this->store->upsert( 'duplicate', $hash, (float) ( $dup['similarity'] ?? 0 ), [
				'a_id'          => (int) ( $dup['a_id'] ?? 0 ),
				'a_title'       => $dup['a_title'] ?? '',
				'b_id'          => (int) ( $dup['b_id'] ?? 0 ),
				'b_title'       => $dup['b_title'] ?? '',
				'similarity'    => (float) ( $dup['similarity'] ?? 0 ),
				'title_overlap' => $dup['title_overlap'] ?? null,
				// object_id records the first doc of the pair so the row is at least
				// attributable to a post like every other insight type. It is not what
				// scopes the pair to a knowledge base — a duplicate is about two docs,
				// and the endpoint matches either side (see duplicate_items()).
			], [ 'source' => 'cloud', 'object_id' => (int) ( $dup['a_id'] ?? 0 ) ] );
		}
		$this->prune_cloud( 'duplicate', $keep_dup );
	}

	/**
	 * Drop cloud-sourced 'new' rows of a type no longer present in the latest
	 * results (user-actioned rows are kept).
	 */
	protected function prune_cloud( $type, array $keep_hashes ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';
		if ( empty( $keep_hashes ) ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table} WHERE type = %s AND source = 'cloud' AND status = 'new'",
				$type
			) );
			return;
		}
		$ph     = implode( ',', array_fill( 0, count( $keep_hashes ), '%s' ) );
		$params = array_merge( [ $type ], array_values( $keep_hashes ) );
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE type = %s AND source = 'cloud' AND status = 'new' AND object_hash NOT IN ( {$ph} )",
			$params
		) );
	}
}
