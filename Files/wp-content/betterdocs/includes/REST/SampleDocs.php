<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Core\SiteProfiler;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Utils\AIUsage;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

/**
 * REST surface for the AI "Generate Sample Docs" feature (Phase 2).
 *
 * Routes (namespace betterdocs/v1):
 *   POST sample-docs/detect    → SiteProfiler profile + locally suggested categories
 *   POST sample-docs/generate  → signs + forwards to the hosted proxy, returns articles
 *   POST sample-docs/insert    → DocBuilder creates the terms/posts (Phase 4)
 *   POST sample-docs/undo      → DocBuilder removes the flagged sample content (Phase 4)
 *
 * @since 4.5.3
 */
class SampleDocs extends BaseAPI {
	/** Hard caps mirrored on the proxy (Phase 0 contract) — legacy single-call docs. */
	const MAX_CATEGORIES          = 3;
	const MAX_ARTICLES_PER_CATEGORY = 3;

	/**
	 * FAQ caps — mirrored on the proxy. Upper bounds only: the AI designs the groups
	 * and the questions from the site's real content and right-sizes below these. The
	 * ceiling is generous (6 groups / 18 questions) so a content-rich site can get a
	 * real FAQ, but the AI is told to produce only as many as the content warrants and
	 * never to pad to the cap — a small site still gets a small FAQ.
	 */
	const MAX_FAQ_CATEGORIES            = 6;
	const MAX_FAQ_ARTICLES_PER_CATEGORY = 3;
	const MAX_FAQ_ARTICLES_TOTAL        = 18;

	/**
	 * Product FAQ caps. Higher than the general FAQ on purpose: this tab writes ONE group
	 * per real product category (and attaches it to that category), so the group cap has
	 * to cover every category the store actually has — with a smaller group cap, a
	 * store with many product categories simply lost half of them.
	 */
	const MAX_PRODUCT_FAQ_CATEGORIES     = 12;
	const MAX_PRODUCT_FAQ_ARTICLES_TOTAL = 36;

	/**
	 * How many detected site subjects (doc categories, key pages, product categories)
	 * to offer as FAQ scope chips. They are topics to cover, NOT groups — the AI decides
	 * how many groups a set of topics warrants (folding several into one, or dropping a
	 * thin one) and produces at most MAX_FAQ_CATEGORIES groups of its own design.
	 */
	const MAX_FAQ_TOPICS = 6;

	/** Product-category chips offered on the WooCommerce tab (one group is written per kept chip). */
	const MAX_PRODUCT_FAQ_TOPICS = 12;

	/**
	 * Deep "full knowledge base" caps (docs only) — mirrored on the proxy. These are
	 * upper bounds only: the AI right-sizes the KB to what the site actually needs and
	 * may return fewer.
	 */
	const MAX_KB_CATEGORIES            = 6;
	const MAX_KB_ARTICLES_PER_CATEGORY = 4;
	const MAX_KB_ARTICLES_TOTAL        = 18;

	/** Default per-category palette/icons (matches the design mockup). */
	const PALETTE = [ '#00B884', '#3B82F6', '#8B5CF6', '#F59E0B', '#0EA5E9', '#EF4444' ];

	/**
	 * @var SiteProfiler
	 */
	protected $profiler;

	public function __construct( Settings $settings, Container $container, SiteProfiler $profiler ) {
		parent::__construct( $settings, $container );
		$this->profiler = $profiler;
	}

	public function permission_check(): bool {
		return current_user_can( 'edit_docs_settings' );
	}

	public function register() {
		$this->post( 'sample-docs/detect', [ $this, 'detect' ] );
		$this->post( 'sample-docs/generate', [ $this, 'generate' ] );
		// Deep KB (docs) — the multi-call outline→expand flow.
		$this->post( 'sample-docs/outline', [ $this, 'outline' ] );
		$this->post( 'sample-docs/article', [ $this, 'article' ] );
		$this->post( 'sample-docs/insert', [ $this, 'insert' ] );
		$this->post( 'sample-docs/undo', [ $this, 'undo' ] );
	}

	/**
	 * Detection step — returns the site profile and locally-derived suggested
	 * categories (no AI). Feeds the design's "detecting" + "detected profile" screens.
	 */
	public function detect( WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return $this->error( 'feature_disabled', __( 'AI sample docs is disabled.', 'betterdocs' ), 403 );
		}

		$content_type = $this->content_type( $request );

		// Every tab now reads its topic chips off the site's REAL content — the General FAQ
		// and docs tabs from doc categories + key pages, the WooCommerce tab from product
		// categories — so detect() needs the content digest for all of them. It's cached
		// separately (and the docs outline call reuses the same cache), so this is one scan
		// per site, not one per click.
		$profile = $this->profiler->build( (bool) $request->get_param( 'fresh' ), true );

		/**
		 * Telemetry: site detection ran.
		 *
		 * @param string $type         Detected site type.
		 * @param string $content_type docs|faq
		 */
		do_action( 'betterdocs_sample_docs_detected', $profile['type'] ?? 'general', $content_type );

		return $this->success(
			[
				'profile'    => $profile,
				'categories' => $this->suggested_categories( $profile, $content_type ),
			]
		);
	}

	/**
	 * Generation step — signs the request and forwards it to the hosted proxy,
	 * which calls OpenAI and returns structured categories + articles.
	 */
	public function generate( WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return $this->error( 'feature_disabled', __( 'AI sample docs is disabled.', 'betterdocs' ), 403 );
		}

		$content_type = $this->content_type( $request );
		$profile      = (array) $request->get_param( 'profile' );
		// For a general FAQ the incoming list is the owner's kept TOPICS (scope), not the
		// groups to produce — so it is capped at MAX_FAQ_TOPICS, which is separate from the
		// group cap. The AI decides how many of at most MAX_FAQ_CATEGORIES groups those
		// topics warrant, folding several into one or dropping a thin one.
		$categories = $this->sanitize_categories(
			(array) $request->get_param( 'categories' ),
			false,
			'docs' === $content_type
				? $this->max_categories( $content_type )
				: ( 'product_faq' === $content_type ? self::MAX_PRODUCT_FAQ_TOPICS : self::MAX_FAQ_TOPICS ),
			$this->max_articles( $content_type )
		);

		if ( empty( $profile ) ) {
			$profile = $this->profiler->build();
		}

		// Product FAQs used to be generated deterministically from the store's WooCommerce
		// SETTINGS (StoreFaqContent) — which produced generic store policy (Payments,
		// Shipping, Returns) and said nothing about what the store actually sells. They
		// now go through the AI like every other type, grounded in the real products and
		// product categories from the content digest.

		// FAQ / product FAQ (and the legacy docs fallback) are grounded in the site's REAL
		// content —
		// the same homepage/About/page/post/product excerpts the deep docs flow uses —
		// so questions and answers are specific to what the site offers, not generic.
		// Enrich the profile when the client didn't send the content digest.
		if ( empty( $profile['content'] ) ) {
			$profile = $this->profiler->build( (bool) $request->get_param( 'fresh' ), true );
		}
		if ( empty( $categories ) ) {
			$categories = $this->suggested_categories( $profile, $content_type );
		}

		// The store-wide "Shipping, Returns & Payments" group is offered on the WooCommerce
		// profile screen as a chip so the owner can see (and remove) it. It is NOT a product
		// category, so pull it out of what goes to the AI, and remember whether the owner
		// kept it — the deterministic group is only prepended below if they did.
		$include_store_wide = false;
		if ( 'product_faq' === $content_type ) {
			$kept = [];
			foreach ( $categories as $cat ) {
				if ( ! empty( $cat['all_products'] ) ) {
					$include_store_wide = true;
				} else {
					$kept[] = $cat;
				}
			}
			$categories = $kept;
		}

		// Subjects the owner removed on the profile screen must be skipped, not merely
		// left out of the hint list — the AI sees the whole content digest and would
		// otherwise write about them anyway.
		$scope = $this->topic_scope( $profile, $content_type, $categories );

		$payload = [
			'profile'    => $profile,
			'categories' => $categories,
			'options'    => [
				'content_type'           => $content_type,
				'maxCategories'          => $this->max_categories( $content_type ),
				'maxArticlesPerCategory' => $this->max_articles( $content_type ),
				'locale'                 => isset( $profile['site']['locale'] ) ? $profile['site']['locale'] : get_locale(),
				// Optional one-line steer from the owner ("what should these FAQs cover?").
				'intent'                 => sanitize_text_field( (string) $request->get_param( 'intent' ) ),
				'exclude_topics'         => $scope['exclude'],
			],
		];

		$response = $this->call_proxy( $payload, $content_type );

		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();

			/**
			 * Telemetry: generation failed (e.g. quota_exceeded, proxy_error).
			 *
			 * @param string $content_type docs|faq
			 * @param string $code         Error code.
			 */
			do_action( 'betterdocs_sample_docs_generation_failed', $content_type, $code );

			// NOTE: no store-policy fallback for the Product tab. StoreFaqContent generates
			// generic store policy (Payments/Shipping/Returns), which is exactly what the
			// product FAQ was fixed to stop producing — silently serving it on a proxy
			// outage would just reintroduce the bug under a different trigger. Surface the
			// error instead and let the user retry.

			// Typed errors the UI maps to the quota / fallback screens.
			$data   = $response->get_error_data();
			$status = is_array( $data ) && isset( $data['status'] ) ? $data['status'] : 502;

			return $this->error( $code, $response->get_error_message(), $status, [ 'fallback' => 'static' ] );
		}

		/**
		 * Telemetry: generation succeeded.
		 *
		 * @param string $content_type docs|faq
		 * @param int    $count        Number of categories returned.
		 */
		$categories = $response['categories'];

		// Layer 1 (WooCommerce only): prepend ONE deterministic, settings-grounded
		// store-wide group (payments/shipping/returns/orders) flagged to show on every
		// product page. The AI writes the per-category product groups (Layer 2); the
		// store-wide policy answers come from the real Woo settings, not the AI, so they
		// can never invent a return window or gateway the store doesn't have.
		if ( 'product_faq' === $content_type && $include_store_wide ) {
			$store_wide = $this->store_wide_group( $profile );
			if ( ! empty( $store_wide ) ) {
				array_unshift( $categories, $store_wide );
			}
		}

		do_action( 'betterdocs_sample_docs_generated', $content_type, count( $categories ) );

		// Usage telemetry: one successful generation, bucketed by content type
		// (docs/faq/product_faq). No single post here, so post_id = 0.
		AIUsage::record( 'sample_docs', 0, $content_type );

		return $this->success(
			[
				'content_type' => $content_type,
				'categories'   => $categories,
				'meta'         => isset( $response['meta'] ) ? $response['meta'] : [],
			]
		);
	}

	/**
	 * The consolidated store-wide product FAQ group (Layer 1), tagged so the builder
	 * flags it "show on all products". Built deterministically from the store's real
	 * WooCommerce settings via StoreFaqContent — no AI, no hallucinated policy.
	 *
	 * @return array|null  A sanitized category array with `all_products => true`, or null.
	 */
	protected function store_wide_group( array $profile ) {
		$store = $this->store_faq();
		if ( null === $store ) {
			return null;
		}

		$group = $store->generate_consolidated( $profile );
		if ( empty( $group['articles'] ) ) {
			return null;
		}

		// Reuse the standard sanitizer (one group, its own question count — not the
		// per-category caps), then tag it for the all-products routing in the builder.
		$clean = $this->sanitize_categories( [ $group ], true, 1, count( $group['articles'] ) );
		if ( empty( $clean[0] ) ) {
			return null;
		}

		$clean[0]['all_products'] = true;
		return $clean[0];
	}

	/**
	 * Lazily resolve the deterministic store-FAQ generator (Layer 1 answers).
	 *
	 * @return \WPDeveloper\BetterDocs\Core\StoreFaqContent|null
	 */
	protected function store_faq() {
		$class = 'WPDeveloper\\BetterDocs\\Core\\StoreFaqContent';
		if ( ! class_exists( $class ) ) {
			return null;
		}
		return $this->container->get( $class );
	}

	/**
	 * Deep KB step 1 — design the whole knowledge base. Sends the content-enriched
	 * site profile to the proxy's /outline endpoint and returns the information
	 * architecture + a job_token the article step reuses.
	 */
	public function outline( WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return $this->error( 'feature_disabled', __( 'AI sample docs is disabled.', 'betterdocs' ), 403 );
		}

		// The deep flow is documentation-only; FAQ/product_faq keep the single-call path.
		$profile = (array) $request->get_param( 'profile' );
		if ( empty( $profile ) || empty( $profile['content'] ) ) {
			// Enrich with the real content digest the outline call needs.
			$profile = $this->profiler->build( (bool) $request->get_param( 'fresh' ), true );
		}

		$intent = sanitize_text_field( (string) $request->get_param( 'intent' ) );

		// Honor the topics the owner kept on the profile screen. The outline call used to
		// send NO categories at all, so removing a chip did nothing to the generated KB —
		// the deep flow silently lost the "skip this category" behaviour the single-call
		// flow had. Both the kept and the REMOVED subjects go to the proxy: naming what to
		// skip is what actually keeps it out, since the AI still sees the whole content
		// digest and would otherwise design that category right back in.
		$scope = $this->topic_scope( $profile, 'docs', (array) $request->get_param( 'categories' ) );

		$payload = [
			'profile' => $profile,
			'options' => [
				'content_type'   => 'docs',
				'locale'         => isset( $profile['site']['locale'] ) ? $profile['site']['locale'] : get_locale(),
				// Upper bounds only — the proxy prompt tells the AI to right-size the KB
				// to what the site genuinely needs and return fewer when appropriate.
				'max_categories' => self::MAX_KB_CATEGORIES,
				'max_articles'   => self::MAX_KB_ARTICLES_TOTAL,
				'intent'         => $intent,
				'topics'         => $scope['include'],
				'exclude_topics' => $scope['exclude'],
			],
		];

		$parsed = $this->proxy_request( 'outline', $payload, 30 );
		if ( is_wp_error( $parsed ) ) {
			$data     = $parsed->get_error_data();
			$http     = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 502;
			$upstream = is_array( $data ) && isset( $data['upstream'] ) ? (int) $data['upstream'] : $http;
			do_action( 'betterdocs_sample_docs_generation_failed', 'docs', $parsed->get_error_code() );
			return $this->error( $parsed->get_error_code(), $parsed->get_error_message(), $http, [ 'fallback' => 'static', 'proxy_status' => $upstream ] );
		}

		if ( empty( $parsed['outline']['categories'] ) || empty( $parsed['job_token'] ) ) {
			return $this->error( 'proxy_error', __( 'The AI service returned an unexpected response.', 'betterdocs' ), 502 );
		}

		$outline = $this->sanitize_outline( $parsed['outline'] );

		do_action( 'betterdocs_sample_docs_generated', 'docs', count( $outline['categories'] ) );

		// Usage telemetry: the deep-KB outline is the once-per-generation success point
		// for the docs flow (article() runs per-article and must NOT be counted).
		AIUsage::record( 'sample_docs', 0, 'docs' );

		return $this->success(
			[
				'content_type' => 'docs',
				'job_token'    => sanitize_text_field( (string) $parsed['job_token'] ),
				'outline'      => $outline,
				'meta'         => isset( $parsed['meta'] ) ? $parsed['meta'] : [],
			]
		);
	}

	/**
	 * Deep KB step 2 — expand one article of a previously issued outline. Thin pass-
	 * through to the proxy's /article endpoint; the React flow loops it per index.
	 */
	public function article( WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return $this->error( 'feature_disabled', __( 'AI sample docs is disabled.', 'betterdocs' ), 403 );
		}

		$job_token = sanitize_text_field( (string) $request->get_param( 'job_token' ) );
		$index     = (int) $request->get_param( 'index' );

		if ( '' === $job_token || $index < 0 ) {
			return $this->error( 'bad_request', __( 'A job token and article index are required.', 'betterdocs' ), 400 );
		}

		$parsed = $this->proxy_request( 'article', [ 'job_token' => $job_token, 'index' => $index ], 30 );
		if ( is_wp_error( $parsed ) ) {
			$data     = $parsed->get_error_data();
			$http     = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 502;
			$upstream = is_array( $data ) && isset( $data['upstream'] ) ? (int) $data['upstream'] : $http;
			return $this->error( $parsed->get_error_code(), $parsed->get_error_message(), $http, [ 'proxy_status' => $upstream ] );
		}

		if ( empty( $parsed['article']['content_html'] ) ) {
			return $this->error( 'proxy_error', __( 'The AI service returned an unexpected response.', 'betterdocs' ), 502 );
		}

		return $this->success(
			[
				'index'   => isset( $parsed['index'] ) ? (int) $parsed['index'] : $index,
				'article' => $this->sanitize_kb_article( $parsed['article'] ),
				'meta'    => isset( $parsed['meta'] ) ? $parsed['meta'] : [],
			]
		);
	}

	/**
	 * Insert step — DocBuilder creates the terms/posts. Wired in Phase 4.
	 */
	public function insert( WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return $this->error( 'feature_disabled', __( 'AI sample docs is disabled.', 'betterdocs' ), 403 );
		}

		$builder = $this->builder();
		if ( null === $builder ) {
			return $this->error( 'not_implemented', __( 'Inserting sample docs is not available yet.', 'betterdocs' ), 501 );
		}

		$content_type = $this->content_type( $request );

		$raw = (array) $request->get_param( 'categories' );

		// Docs come from the deep outline→expand flow (up to 8 categories × 6 articles);
		// the 3×3 sanitizer would silently truncate them. FAQ/product_faq keep the caps.
		if ( 'docs' === $content_type ) {
			$categories = $this->sanitize_kb_categories( $raw );
		} elseif ( 'product_faq' === $content_type ) {
			// The store-wide group (Layer 1) is one extra group on top of the per-category
			// cap, so it must not count against it — split it out, cap the per-category
			// groups, then re-attach it. Otherwise the last product category is dropped.
			$store_wide = [];
			$per_cat    = [];
			foreach ( $raw as $cat ) {
				if ( ! empty( $cat['all_products'] ) ) {
					$store_wide[] = $cat;
				} else {
					$per_cat[] = $cat;
				}
			}
			$categories = $this->sanitize_categories(
				$per_cat,
				true,
				$this->max_categories( $content_type ),
				$this->max_articles( $content_type ),
				$this->max_articles_total( $content_type )
			);
			if ( ! empty( $store_wide[0] ) ) {
				$clean = $this->sanitize_categories( [ $store_wide[0] ], true, 1, count( (array) ( $store_wide[0]['articles'] ?? [] ) ) );
				if ( ! empty( $clean[0] ) ) {
					$clean[0]['all_products'] = true;
					array_unshift( $categories, $clean[0] );
				}
			}
		} else {
			$categories = $this->sanitize_categories(
				$raw,
				true,
				$this->max_categories( $content_type ),
				$this->max_articles( $content_type ),
				$this->max_articles_total( $content_type )
			);
		}

		$result = $builder->build( $categories, $content_type );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_code(), $result->get_error_message(), 400 );
		}

		return $this->success( $result );
	}

	/**
	 * Undo step — remove exactly the sample content we created. Wired in Phase 4.
	 */
	public function undo( WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return $this->error( 'feature_disabled', __( 'AI sample docs is disabled.', 'betterdocs' ), 403 );
		}

		$builder = $this->builder();
		if ( null === $builder ) {
			return $this->error( 'not_implemented', __( 'Removing sample docs is not available yet.', 'betterdocs' ), 501 );
		}

		$content_type = $this->content_type( $request );
		return $this->success( $builder->undo( $content_type ) );
	}

	/**
	 * Lazily resolve the DocBuilder (added in Phase 4) so this class loads even
	 * before the builder exists.
	 *
	 * @return \WPDeveloper\BetterDocs\Core\SampleDocBuilder|null
	 */
	protected function builder() {
		$class = 'WPDeveloper\\BetterDocs\\Core\\SampleDocBuilder';
		if ( ! class_exists( $class ) ) {
			return null;
		}
		return $this->container->get( $class );
	}

	/* --------------------------------------------------------------------- */
	/* Proxy plumbing                                                         */
	/* --------------------------------------------------------------------- */

	/**
	 * Legacy single-call generation — sign + POST to the proxy and validate the
	 * { categories, meta } shape (used by the FAQ path).
	 *
	 * @return array|\WP_Error Parsed { categories, meta } on success.
	 */
	protected function call_proxy( array $payload, $content_type = 'docs' ) {
		$parsed = $this->proxy_request( '', $payload );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		if ( empty( $parsed['categories'] ) || ! is_array( $parsed['categories'] ) ) {
			return $this->error( 'proxy_error', __( 'The AI service returned an unexpected response.', 'betterdocs' ), 502 );
		}

		// Enforce caps + sanitize defensively on our side too — with THIS content type's
		// caps. Sanitizing an FAQ with the docs 3x3 defaults would silently throw away
		// every group and question the proxy right-sized beyond the third.
		$parsed['categories'] = $this->sanitize_categories(
			$parsed['categories'],
			true,
			$this->max_categories( $content_type ),
			$this->max_articles( $content_type ),
			$this->max_articles_total( $content_type )
		);

		return $parsed;
	}

	/**
	 * Sign + POST a payload to a hosted-proxy action ('' = legacy generate, 'outline',
	 * 'article'), with one retry on transport failure. Returns the parsed JSON array on
	 * a 2xx response, or a typed WP_Error otherwise. Shape validation is the caller's job.
	 *
	 * @param string $action       Sub-path under v1/sample-docs ('' | 'outline' | 'article').
	 * @param array  $payload       Request body.
	 * @param int    $timeout_base  Base HTTP timeout in seconds (per single OpenAI call).
	 * @return array|\WP_Error
	 */
	protected function proxy_request( $action, array $payload, $timeout_base = 20 ) {
		$url     = $this->proxy_endpoint( $action );
		$secret  = $this->proxy_secret();
		$body    = wp_json_encode( $payload );
		$timeout = $this->request_timeout( $timeout_base );

		$args = [
			'timeout' => $timeout,
			'headers' => [
				'Content-Type'           => 'application/json',
				'Accept'                 => 'application/json',
				'X-BetterDocs-Site'      => esc_url_raw( home_url() ),
				'X-BetterDocs-License'   => $this->license_key(),
				'X-BetterDocs-Signature' => hash_hmac( 'sha256', $body, $secret ),
			],
			'body'    => $body,
		];

		$attempts = 0;
		$response = null;
		while ( $attempts < 2 ) {
			$attempts++;

			// Give this attempt a fresh execution budget: without it a hung upstream
			// trips PHP's max_execution_time mid-cURL and the route dies with a raw
			// 500 critical error instead of the typed JSON the modal understands.
			$reset = function_exists( 'set_time_limit' ) && @set_time_limit( $timeout + 15 );

			$response = wp_remote_post( $url, $args );

			// Retry ONLY on a genuine transport failure (no HTTP response). A 5xx may
			// mean the proxy already called OpenAI and spent tokens, so re-POSTing
			// would risk double-billing — treat any received status as final.
			if ( ! is_wp_error( $response ) ) {
				break;
			}

			// If the time limit could not be reset (disabled by the host), a second
			// full-length attempt could still fatal mid-cURL — surface the transport
			// error instead of risking the retry.
			if ( ! $reset ) {
				break;
			}
		}

		if ( is_wp_error( $response ) ) {
			return $this->error( 'proxy_unreachable', __( 'Could not reach the BetterDocs AI service. Please try again.', 'betterdocs' ), 502, [ 'upstream' => 0 ] );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$parsed = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 429 === $status || ( isset( $parsed['status'] ) && 'quota_exceeded' === $parsed['status'] ) ) {
			return $this->error( 'quota_exceeded', __( 'You have used your free AI generation for this site.', 'betterdocs' ), 429, [ 'upstream' => 429 ] );
		}

		if ( $status >= 400 || ! is_array( $parsed ) ) {
			$message = isset( $parsed['message'] ) ? $parsed['message'] : __( 'The AI service returned an unexpected response.', 'betterdocs' );
			// Preserve the real upstream status under a distinct key so callers (and the
			// UI) can special-case e.g. 404 (old proxy → classic fallback) or 410 (job
			// expired → regenerate); error() itself overwrites data['status'] with $status.
			return $this->error( 'proxy_error', $message, 502, [ 'upstream' => $status ? $status : 502 ] );
		}

		return $parsed;
	}

	/**
	 * HTTP timeout (seconds) for a proxy call, kept safely below PHP's
	 * max_execution_time so a hung upstream returns a clean WP_Error (typed
	 * JSON + static fallback in the wizard) instead of fataling mid-cURL.
	 *
	 * @param int $base Base timeout in seconds for a single OpenAI call.
	 * @return int
	 */
	protected function request_timeout( $base = 20 ) {
		$timeout  = max( 5, (int) $base );
		$max_exec = (int) ini_get( 'max_execution_time' );

		if ( $max_exec > 0 ) {
			$timeout = min( $timeout, max( 5, $max_exec - 10 ) );
		}

		/** Filter the HTTP timeout (seconds) for hosted AI proxy requests. */
		return (int) apply_filters( 'betterdocs_ai_proxy_timeout', $timeout );
	}

	/**
	 * Full proxy endpoint URL for an action ('' | 'outline' | 'article').
	 *
	 * @return string
	 */
	protected function proxy_endpoint( $action = '' ) {
		$base = get_option( 'betterdocs_ai_proxy_url', 'https://api.betterdocs.co/ai' );
		/** Filter the hosted proxy base URL. */
		$base = apply_filters( 'betterdocs_ai_proxy_url', $base );
		$url  = trailingslashit( $base ) . 'v1/sample-docs';
		return '' !== $action ? $url . '/' . ltrim( (string) $action, '/' ) : $url;
	}

	protected function proxy_secret() {
		$secret = get_option( 'betterdocs_ai_proxy_secret', 'betterdocs-local-dev-secret' );
		/** Filter the HMAC shared secret used to sign proxy requests. */
		return apply_filters( 'betterdocs_ai_proxy_secret', $secret );
	}

	protected function license_key() {
		/** Filter the license key sent to the proxy for per-site identity. */
		return apply_filters( 'betterdocs_ai_proxy_license', (string) get_option( 'betterdocs_pro_licenses', '' ) );
	}

	/* --------------------------------------------------------------------- */
	/* Helpers                                                                */
	/* --------------------------------------------------------------------- */

	protected function is_enabled() {
		return (bool) $this->settings->get( 'enable_ai_sample_docs', true );
	}

	protected function content_type( WP_REST_Request $request ) {
		$type = $request->get_param( 'content_type' );
		if ( 'faq' === $type || 'product_faq' === $type ) {
			return $type;
		}
		return 'docs';
	}

	/**
	 * Per-content-type category cap. Product FAQs cover the four store-ops groups
	 * (Payments & Billing, Shipping & Delivery, Returns & Refunds, Orders & Account);
	 * a general FAQ is right-sized by the AI up to the FAQ cap; docs keep the legacy
	 * single-call cap (the real KB goes through outline→expand).
	 *
	 * @return int
	 */
	protected function max_categories( $content_type ) {
		if ( 'product_faq' === $content_type ) {
			return self::MAX_PRODUCT_FAQ_CATEGORIES;
		}
		return 'docs' === $content_type ? self::MAX_CATEGORIES : self::MAX_FAQ_CATEGORIES;
	}

	/**
	 * Per-content-type cap on entries within one category/group.
	 *
	 * @return int
	 */
	protected function max_articles( $content_type ) {
		return 'docs' === $content_type ? self::MAX_ARTICLES_PER_CATEGORY : self::MAX_FAQ_ARTICLES_PER_CATEGORY;
	}

	/**
	 * Per-content-type cap on total entries (0 = no separate total cap).
	 *
	 * @return int
	 */
	protected function max_articles_total( $content_type ) {
		if ( 'product_faq' === $content_type ) {
			return self::MAX_PRODUCT_FAQ_ARTICLES_TOTAL;
		}
		return 'docs' === $content_type ? 0 : self::MAX_FAQ_ARTICLES_TOTAL;
	}

	/**
	 * Deterministic, type-aware suggested categories (no AI) for the profile screen.
	 *
	 * @return array
	 */
	protected function suggested_categories( array $profile, $content_type ) {
		// WooCommerce Product FAQ: the chips are the store's REAL product categories —
		// this tab writes FAQs about what the store SELLS. (It used to show the canned
		// store-policy groups from StoreFaqContent: Payments, Shipping, Returns…)
		if ( 'product_faq' === $content_type ) {
			return $this->product_topics( $profile );
		}

		// General FAQ AND docs both show the SUBJECTS actually detected on the site (doc
		// categories, key pages) as scope chips — no canned lists. Docs used to show a
		// hardcoded preset structure ("Getting Started / Shipping & Delivery / Product
		// Guides") with placeholder article titles, which the profile screen counted as a
		// fixed "3 categories, 9 docs". That count was always misleading: the deep
		// outline flow designs and right-sizes the REAL knowledge base from the site
		// content, so the preset numbers never matched what got generated. The owner
		// prunes the detected subjects to scope generation; the proxy designs the rest.
		return $this->detected_topics( $profile );
	}

	/**
	 * Subjects actually detected ON the site, for the GENERAL FAQ profile screen — doc
	 * categories first (what the site already documents is what people ask about), then
	 * real key pages (Pricing, Security, Integrations…). These are SUBJECTS, not FAQ
	 * group names: the AI designs the groups, the questions and the answers itself.
	 * Pruning a chip scopes the FAQ.
	 *
	 * Product categories are NOT included: they are the WooCommerce tab's material
	 * (see product_topics()), and a general site FAQ has no business offering to write
	 * about "Hoodies".
	 *
	 * Returns [] on a site with no usable content — the AI then works from the site
	 * profile alone rather than from a canned list.
	 *
	 * @return array
	 */
	/**
	 * Subjects for the WooCommerce (Product FAQ) tab: what the store actually SELLS.
	 *
	 * Real product categories first (they map 1:1 onto the FAQ group the AI designs, so
	 * the group can then be assigned to that product category and show on those product
	 * pages), falling back to product names on a store with no categories. Never store
	 * policy — a shopper on a product page asks about the product.
	 *
	 * @return array
	 */
	protected function product_topics( array $profile ) {
		$woo     = isset( $profile['woocommerce'] ) && is_array( $profile['woocommerce'] ) ? $profile['woocommerce'] : [];
		$content = isset( $profile['content'] ) && is_array( $profile['content'] ) ? $profile['content'] : [];

		$names = [];

		if ( ! empty( $woo['product_categories'] ) ) {
			$names = array_map( 'strval', (array) $woo['product_categories'] );
		}

		// No product categories (a small store selling a handful of items): fall back to
		// the products themselves.
		if ( empty( $names ) ) {
			if ( ! empty( $content['products'] ) && is_array( $content['products'] ) ) {
				foreach ( $content['products'] as $product ) {
					if ( ! empty( $product['title'] ) ) {
						$names[] = (string) $product['title'];
					}
				}
			} elseif ( ! empty( $woo['sample_products'] ) ) {
				$names = array_map( 'strval', (array) $woo['sample_products'] );
			}
		}

		$chips = $this->topic_chips( $names, self::MAX_PRODUCT_FAQ_TOPICS );

		// Offer the always-available store-wide group as the FIRST chip, so the owner can
		// see — and, by removing it, opt out of — the deterministic "Shipping, Returns &
		// Payments" group that shows on every product page. It is NOT a product category;
		// the `all_products` flag tells generate() to route it to the store-wide layer
		// rather than the AI.
		$store = $this->store_faq();
		if ( $store ) {
			$def = $store->consolidated_definition();
			array_unshift(
				$chips,
				[
					'id'           => 'sd_storewide',
					'name'         => $def['name'],
					'icon'         => isset( $def['icon'] ) ? sanitize_key( $def['icon'] ) : 'truck',
					'color'        => self::PALETTE[0],
					'all_products' => true,
					// Real question titles so the profile screen shows a count.
					'articles'     => array_values( (array) $def['questions'] ),
				]
			);
		}

		return $chips;
	}

	protected function detected_topics( array $profile ) {
		$topics  = isset( $profile['topics'] ) && is_array( $profile['topics'] ) ? $profile['topics'] : [];
		$content = isset( $profile['content'] ) && is_array( $profile['content'] ) ? $profile['content'] : [];

		$names = [];

		// 1. What the site already documents.
		if ( ! empty( $topics['doc_categories'] ) ) {
			$names = array_merge( $names, array_map( 'strval', (array) $topics['doc_categories'] ) );
		}

		// 2. Real key pages — minus the boilerplate every WP site has, which nobody
		// writes an FAQ group about.
		if ( ! empty( $content['pages'] ) && is_array( $content['pages'] ) ) {
			foreach ( $content['pages'] as $page ) {
				$title = isset( $page['title'] ) ? (string) $page['title'] : '';
				if ( '' !== $title && ! $this->is_boilerplate_page( $title ) ) {
					$names[] = $title;
				}
			}
		}

		// NOTE: product categories are deliberately NOT topics here. They belong to the
		// WooCommerce (Product FAQ) tab, which is generated per product category — pulling
		// them into the General FAQ made a general site FAQ offer to write about
		// "Clothing", "Hoodies", "Music".

		return $this->topic_chips( $names );
	}

	/**
	 * Turn a raw list of subject names into profile-screen chips: de-duped
	 * case-insensitively (first spelling wins) and bounded to MAX_FAQ_TOPICS.
	 *
	 * @return array
	 */
	protected function topic_chips( array $names, $max = self::MAX_FAQ_TOPICS ) {
		$max = max( 1, (int) $max );
		$seen  = [];
		$clean = [];
		foreach ( $names as $name ) {
			$name = trim( wp_strip_all_tags( (string) $name ) );
			$key  = strtolower( $name );
			if ( '' === $name || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$clean[]      = $name;
			if ( count( $clean ) >= $max ) {
				break;
			}
		}

		$cats = [];
		foreach ( $clean as $i => $name ) {
			$cats[] = [
				'id'       => sanitize_key( 'sd_' . $i ),
				'name'     => sanitize_text_field( $name ),
				'icon'     => 'help',
				'color'    => self::PALETTE[ $i % count( self::PALETTE ) ],
				// No seeded questions: the AI writes them. The FAQ profile screen counts
				// topics, not questions, so nothing needs a placeholder here.
				'articles' => [],
			];
		}

		return $cats;
	}

	/**
	 * Pages that are site *plumbing* — a cart, a login form, an index — and so carry no
	 * subject anyone asks a question about.
	 *
	 * Policy pages (privacy, terms, refunds) are deliberately NOT skipped: they are
	 * plumbing for documentation but they are prime FAQ material ("Do you sell my data?",
	 * "Can I get a refund?"). Skipping them meant a site with no docs yet — the normal
	 * case when generating samples — detected zero topics, while the AI, which reads the
	 * whole content digest rather than these chips, went on to build "Privacy & Data" and
	 * "Terms & Licensing" groups from them anyway.
	 *
	 * @return bool
	 */
	protected function is_boilerplate_page( $title ) {
		$skip = [ 'home', 'homepage', 'front page', 'sample page', 'blog', 'news', 'shop', 'store', 'cart', 'checkout', 'my account', 'account', 'login', 'log in', 'register', 'sign up', 'search results', '404', 'page not found' ];
		return in_array( strtolower( trim( wp_strip_all_tags( (string) $title ) ) ), $skip, true );
	}

	/**
	 * What the owner kept, and what they REMOVED, on the profile screen.
	 *
	 * Removing a chip must actually skip that subject — for docs, FAQ and product FAQ
	 * alike. Sending only the kept list isn't enough: the AI also sees the site's full
	 * content digest, so a removed subject happily reappears unless it is named as
	 * off-limits. The removed set is derived server-side by diffing the deterministic
	 * suggestion list against what the client sent back, so no client change (and no
	 * trust in the client) is needed.
	 *
	 * @param array  $kept         Categories the client sent back (each with a 'name').
	 * @param string $content_type docs|faq|product_faq
	 * @return array { include: string[], exclude: string[] }
	 */
	protected function topic_scope( array $profile, $content_type, array $kept ) {
		$suggested = $this->suggested_categories( $profile, $content_type );

		$kept_names = [];
		foreach ( $kept as $cat ) {
			$name = is_array( $cat ) ? ( isset( $cat['name'] ) ? $cat['name'] : '' ) : $cat;
			$name = trim( wp_strip_all_tags( (string) $name ) );
			if ( '' !== $name ) {
				$kept_names[ $this->normalize_category_name( $name ) ] = $name;
			}
		}

		$exclude = [];
		foreach ( $suggested as $cat ) {
			// The store-wide chip is not a real topic the AI writes about (it's the
			// deterministic Layer-1 group), so never push it into the AI's exclude list.
			if ( is_array( $cat ) && ! empty( $cat['all_products'] ) ) {
				continue;
			}
			$name = isset( $cat['name'] ) ? (string) $cat['name'] : '';
			if ( '' !== $name && ! isset( $kept_names[ $this->normalize_category_name( $name ) ] ) ) {
				$exclude[] = $name;
			}
		}

		return [
			'include' => array_values( $kept_names ),
			'exclude' => $exclude,
		];
	}

	/**
	 * Keep only the generated categories the user left selected on the profile
	 * screen, matched by normalized name. Falls back to the full generated set when
	 * the selection is empty or nothing matches, so we never return zero categories.
	 *
	 * @param array $generated Categories produced by the deterministic generator.
	 * @param array $selected  The user's kept categories (each with a 'name').
	 * @return array
	 */
	protected function filter_selected_categories( array $generated, array $selected ) {
		if ( empty( $selected ) ) {
			return $generated;
		}

		$wanted = [];
		foreach ( $selected as $cat ) {
			if ( is_array( $cat ) && ! empty( $cat['name'] ) ) {
				$wanted[ $this->normalize_category_name( $cat['name'] ) ] = true;
			}
		}
		if ( empty( $wanted ) ) {
			return $generated;
		}

		$filtered = array_values(
			array_filter(
				$generated,
				function ( $group ) use ( $wanted ) {
					return ! empty( $group['name'] ) && isset( $wanted[ $this->normalize_category_name( $group['name'] ) ] );
				}
			)
		);

		return ! empty( $filtered ) ? $filtered : $generated;
	}

	/**
	 * Normalize a category name for loose matching between the user's selection and
	 * the generated set (case/whitespace/tag-insensitive).
	 *
	 * @return string
	 */
	protected function normalize_category_name( $name ) {
		return strtolower( trim( wp_strip_all_tags( (string) $name ) ) );
	}

	/**
	 * Sanitize + cap an incoming categories array (used both for the user's edited
	 * list and the proxy response).
	 *
	 * @param bool $with_content Keep article body HTML (proxy response) vs titles only.
	 * @param int  $max          Category cap (defaults to MAX_CATEGORIES; FAQ uses more).
	 * @param int  $max_articles Per-category entry cap.
	 * @param int  $max_total    Total entry cap across all categories (0 = none).
	 * @return array
	 */
	protected function sanitize_categories( array $categories, $with_content = false, $max = self::MAX_CATEGORIES, $max_articles = self::MAX_ARTICLES_PER_CATEGORY, $max_total = 0 ) {
		$max          = max( 1, (int) $max );
		$max_articles = max( 1, (int) $max_articles );
		$max_total    = max( 0, (int) $max_total );
		$total        = 0;
		$clean        = [];
		foreach ( array_slice( $categories, 0, $max ) as $i => $cat ) {
			if ( empty( $cat['name'] ) || ( $max_total > 0 && $total >= $max_total ) ) {
				continue;
			}

			$articles = [];
			$raw      = isset( $cat['articles'] ) && is_array( $cat['articles'] ) ? $cat['articles'] : [];
			foreach ( array_slice( $raw, 0, $max_articles ) as $article ) {
				if ( $max_total > 0 && $total >= $max_total ) {
					break;
				}
				if ( is_array( $article ) ) {
					$entry = [
						'title'   => sanitize_text_field( isset( $article['title'] ) ? $article['title'] : '' ),
						'excerpt' => sanitize_text_field( isset( $article['excerpt'] ) ? $article['excerpt'] : '' ),
					];
					if ( $with_content ) {
						$entry['content_html'] = wp_kses_post( isset( $article['content_html'] ) ? $article['content_html'] : '' );
					}
					if ( '' !== $entry['title'] ) {
						$articles[] = $entry;
						$total++;
					}
				} else {
					$title = sanitize_text_field( $article );
					if ( '' !== $title ) {
						$articles[] = $with_content ? [ 'title' => $title, 'content_html' => '', 'excerpt' => '' ] : $title;
						$total++;
					}
				}
			}

			$clean[] = [
				'id'          => sanitize_key( isset( $cat['id'] ) ? $cat['id'] : 'sd_' . $i ),
				'name'        => sanitize_text_field( $cat['name'] ),
				'description' => isset( $cat['description'] ) ? sanitize_text_field( $cat['description'] ) : '',
				'icon'        => isset( $cat['icon'] ) ? sanitize_key( $cat['icon'] ) : 'book',
				'color'       => sanitize_hex_color( isset( $cat['color'] ) ? $cat['color'] : '' ) ?: self::PALETTE[ $i % count( self::PALETTE ) ],
				// Product FAQ: the WooCommerce product category this group is about, so the
				// builder can attach the group to it (the FAQ then shows on those product
				// pages instead of nowhere).
				'product_category' => isset( $cat['product_category'] ) ? sanitize_text_field( $cat['product_category'] ) : '',
				// Product FAQ Layer 1: the consolidated store-wide group, flagged so the
				// builder assigns it to "all products" rather than one category.
				'all_products'     => ! empty( $cat['all_products'] ),
				'articles'    => $articles,
			];
		}

		return $clean;
	}

	/* --------------------------------------------------------------------- */
	/* Deep KB sanitizers                                                     */
	/* --------------------------------------------------------------------- */

	/**
	 * Sanitize the proxy's designed outline for the client: clamp counts, keep the
	 * per-article specs (index/type/slug/summary), and assign a display color per
	 * category. Does NOT run the 3×3 sanitizer.
	 *
	 * @return array { total_articles, categories:[ { id, name, slug, icon, color, description, articles:[…] } ] }
	 */
	protected function sanitize_outline( array $outline ) {
		$cats_in = isset( $outline['categories'] ) && is_array( $outline['categories'] ) ? $outline['categories'] : [];

		$categories = [];
		$total      = 0;
		foreach ( array_slice( $cats_in, 0, self::MAX_KB_CATEGORIES ) as $i => $cat ) {
			if ( empty( $cat['name'] ) || $total >= self::MAX_KB_ARTICLES_TOTAL ) {
				continue;
			}

			$articles = [];
			$arts_in  = isset( $cat['articles'] ) && is_array( $cat['articles'] ) ? $cat['articles'] : [];
			foreach ( array_slice( $arts_in, 0, self::MAX_KB_ARTICLES_PER_CATEGORY ) as $art ) {
				if ( $total >= self::MAX_KB_ARTICLES_TOTAL || empty( $art['title'] ) ) {
					continue;
				}
				$links = [];
				if ( isset( $art['links'] ) && is_array( $art['links'] ) ) {
					foreach ( $art['links'] as $l ) {
						$links[] = sanitize_title( (string) $l );
					}
				}
				$articles[] = [
					'index'   => isset( $art['index'] ) ? (int) $art['index'] : $total,
					'type'    => sanitize_key( isset( $art['type'] ) ? $art['type'] : 'guide' ),
					'title'   => sanitize_text_field( $art['title'] ),
					'slug'    => sanitize_title( isset( $art['slug'] ) ? $art['slug'] : $art['title'] ),
					'summary' => sanitize_text_field( isset( $art['summary'] ) ? $art['summary'] : '' ),
					'links'   => array_values( array_filter( $links ) ),
				];
				$total++;
			}

			if ( empty( $articles ) ) {
				continue;
			}

			$categories[] = [
				'id'          => sanitize_key( isset( $cat['id'] ) ? 'sd_' . $cat['id'] : 'sd_' . $i ),
				'name'        => sanitize_text_field( $cat['name'] ),
				'slug'        => sanitize_title( isset( $cat['slug'] ) ? $cat['slug'] : $cat['name'] ),
				'icon'        => sanitize_key( isset( $cat['icon'] ) ? $cat['icon'] : 'book' ),
				'color'       => self::PALETTE[ count( $categories ) % count( self::PALETTE ) ],
				'description' => sanitize_text_field( isset( $cat['description'] ) ? $cat['description'] : '' ),
				'articles'    => $articles,
			];
		}

		return [
			'total_articles' => $total,
			'categories'     => $categories,
		];
	}

	/**
	 * Sanitize a single expanded article. Keeps the `#bd-link--slug` cross-link
	 * sentinels (fragment hrefs survive wp_kses_post) for the builder to resolve.
	 *
	 * @return array { title, slug, type, category_slug, content_html, excerpt }
	 */
	protected function sanitize_kb_article( array $article ) {
		return [
			'title'         => sanitize_text_field( isset( $article['title'] ) ? $article['title'] : '' ),
			'slug'          => sanitize_title( isset( $article['slug'] ) ? $article['slug'] : '' ),
			'type'          => sanitize_key( isset( $article['type'] ) ? $article['type'] : 'guide' ),
			'category_slug' => sanitize_title( isset( $article['category_slug'] ) ? $article['category_slug'] : '' ),
			'content_html'  => wp_kses_post( isset( $article['content_html'] ) ? $article['content_html'] : '' ),
			'excerpt'       => sanitize_text_field( isset( $article['excerpt'] ) ? $article['excerpt'] : '' ),
		];
	}

	/**
	 * Sanitize + cap an assembled deep KB (categories with expanded articles) for
	 * insertion. Preserves article slug/type (needed for cross-link resolution and
	 * intro/quickstart ordering) and keeps content HTML.
	 *
	 * @return array
	 */
	protected function sanitize_kb_categories( array $categories ) {
		$clean = [];
		$total = 0;

		foreach ( array_slice( $categories, 0, self::MAX_KB_CATEGORIES ) as $i => $cat ) {
			if ( empty( $cat['name'] ) || $total >= self::MAX_KB_ARTICLES_TOTAL ) {
				continue;
			}

			$articles = [];
			$raw      = isset( $cat['articles'] ) && is_array( $cat['articles'] ) ? $cat['articles'] : [];
			foreach ( array_slice( $raw, 0, self::MAX_KB_ARTICLES_PER_CATEGORY ) as $article ) {
				if ( $total >= self::MAX_KB_ARTICLES_TOTAL || ! is_array( $article ) || empty( $article['title'] ) ) {
					continue;
				}
				$articles[] = [
					'title'        => sanitize_text_field( $article['title'] ),
					'slug'         => sanitize_title( isset( $article['slug'] ) ? $article['slug'] : $article['title'] ),
					'type'         => sanitize_key( isset( $article['type'] ) ? $article['type'] : 'guide' ),
					'content_html' => wp_kses_post( isset( $article['content_html'] ) ? $article['content_html'] : '' ),
					'excerpt'      => sanitize_text_field( isset( $article['excerpt'] ) ? $article['excerpt'] : '' ),
				];
				$total++;
			}

			if ( empty( $articles ) ) {
				continue;
			}

			$clean[] = [
				'id'          => sanitize_key( isset( $cat['id'] ) ? $cat['id'] : 'sd_' . $i ),
				'name'        => sanitize_text_field( $cat['name'] ),
				'slug'        => sanitize_title( isset( $cat['slug'] ) ? $cat['slug'] : $cat['name'] ),
				'description' => isset( $cat['description'] ) ? sanitize_text_field( $cat['description'] ) : '',
				'icon'        => isset( $cat['icon'] ) ? sanitize_key( $cat['icon'] ) : 'book',
				'color'       => sanitize_hex_color( isset( $cat['color'] ) ? $cat['color'] : '' ) ?: self::PALETTE[ $i % count( self::PALETTE ) ],
				'articles'    => $articles,
			];
		}

		return $clean;
	}
}
