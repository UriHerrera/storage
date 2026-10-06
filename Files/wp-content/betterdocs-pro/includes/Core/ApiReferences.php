<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WPDeveloper\BetterDocsPro\Core\ApiDocs\AccentPalette;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\ProxyAllowlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Documentation — the "API Reference" entity.
 *
 * One reference = one OpenAPI spec + display settings + a public URL
 * (`/docs/api/{slug}/` by default). The raw spec lives in the
 * `{prefix}betterdocs_api_specs` table and is only ever served through the
 * gated REST route — never as a file URL.
 *
 * Free ships this base (capped at one reference, ten rendered endpoints);
 * Pro extends this class through the DI container to lift the caps.
 */
class ApiReferences {
	/**
	 * Post type name.
	 *
	 * @var string
	 */
	public $post_type = 'betterdocs_api_ref';

	public function __construct() {
		// Priority 8: before the `docs` CPT (priority 9) so the more specific
		// `docs/api/…` rewrite rules are matched ahead of the docs rules.
		add_action( 'init', [ $this, 'register' ], 8 );
		add_action( 'before_delete_post', [ $this, 'cleanup_spec_rows' ], 10, 2 );
		// The reference has no template of its own — every operation + the intro
		// are native `docs` posts. Its URL 301s to the generated Introduction doc,
		// which renders through the standard single-doc template + sidebar.
		add_action( 'template_redirect', [ $this, 'redirect_to_intro' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		// Also register in the block editor: the `betterdocs/api-tryit` block
		// lists `betterdocs-api-reference` as an editor style so its server-side
		// preview looks exactly like the frontend banner. Priority 5 so the
		// handle exists before the block's editor styles are enqueued.
		add_action( 'enqueue_block_assets', [ $this, 'register_assets' ], 5 );

		// Admin: add the "API Docs" React page + register its screen so the
		// admin SPA bundle loads there (all via existing filters — no core edits).
		// Free ships an "API Docs" teaser menu + route of its own; this tells it to
		// stand down so Pro's real screen is the only one registered.
		add_filter( 'betterdocs_pro_has_api_docs', '__return_true' );
		add_filter( 'betterdocs_admin_menu', [ $this, 'admin_menu' ], 10, 3 );
		add_filter( 'betterdocs_admin_screens', [ $this, 'admin_screen_hooks' ] );
		add_filter( 'betterdocs_admin_screen_slugs', [ $this, 'admin_screen_slugs' ] );
		// The API Docs admin screen is a Pro micro-frontend injected into Free's
		// dashboard app (route via the `betterdocs_routes` JS filter).
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_app' ], 20 );
		// Priority 5: the chatbot registers its own `sync-new-docs` handler at the
		// default priority 10, so this runs first and can narrow that run to a
		// single reference's docs before it starts.
		add_action( 'admin_init', [ $this, 'scope_chatbot_sync' ], 5 );
		// Materialization destination: honor the reference's KB/Category choice.
		add_filter( 'betterdocs_api_docs_knowledge_base', [ $this, 'resolve_kb' ], 10, 2 );
	}

	/**
	 * Narrow a chatbot "sync new docs" run to one API reference's docs.
	 *
	 * The button in the Endpoint Docs panel is a deep link into the chatbot's own
	 * sync trigger, which embeds its entire site-wide pending queue. From the
	 * drawer of a single API that is the wrong scope twice over: the count meant
	 * nothing, and one click re-embedded every unsynced doc on the site.
	 *
	 * The chatbot's existing scope mechanism is a post type — no use here, since
	 * generated endpoint docs are ordinary `docs`, indistinguishable by type. So
	 * it grew a companion ID scope (`ai_chatbot_sync_ids`), and this sets it for
	 * the request before the chatbot's handler reads it. Everything not in the
	 * list stays queued for a later run; the chatbot clears the option when the
	 * run ends, so it can never leak into the next sync.
	 *
	 * @return void
	 */
	public function scope_chatbot_sync() {
		// Deliberately no nonce check here: this only narrows a run, and the
		// chatbot's own handler still verifies the `ai_chatbot_embed` nonce and
		// the capability before starting anything. Verifying twice would mean
		// duplicating that contract in a second plugin.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['sync-new-docs'] ) || '1' !== (string) $_GET['sync-new-docs'] ) {
			return;
		}

		$reference_id = isset( $_GET['bd_api_ref'] ) ? absint( $_GET['bd_api_ref'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $reference_id ) {
			// An unscoped sync (Settings page, or any other trigger). Clear any
			// stale scope so a normal run is never silently narrowed.
			delete_option( 'ai_chatbot_sync_ids' );
			return;
		}

		$reference = get_post( $reference_id );

		if ( ! $reference || $this->post_type !== $reference->post_type ) {
			delete_option( 'ai_chatbot_sync_ids' );
			return;
		}

		$ids = array_values(
			array_map(
				'intval',
				betterdocs()->container->get( ApiDocs\Materializer::class )->existing_docs_map( $reference_id )
			)
		);

		if ( empty( $ids ) ) {
			delete_option( 'ai_chatbot_sync_ids' );
			return;
		}

		update_option( 'ai_chatbot_sync_ids', $ids, false );
	}

	/**
	 * Add the "API Docs" submenu (rendered by the admin SPA shell, routed in
	 * React to `betterdocs-api-docs`).
	 *
	 * @param array    $pages
	 * @param callable $callback The Admin::output SPA-shell callback.
	 * @param array    $parent_slug
	 * @return array
	 */
	public function admin_menu( $pages, $callback, $parent_slug = [] ) {
		$pages['api_docs'] = wp_parse_args(
			$parent_slug,
			[
				'page_title' => __( 'API Docs', 'betterdocs-pro' ),
				'menu_title' => __( 'API Docs', 'betterdocs-pro' ),
				'capability' => apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ),
				'menu_slug'  => 'betterdocs-api-docs',
				'callback'   => $callback
			]
		);

		return $pages;
	}

	/**
	 * @param string[] $screens Hook suffixes that load the admin SPA bundle.
	 * @return string[]
	 */
	public function admin_screen_hooks( $screens ) {
		$screens[] = 'betterdocs_page_betterdocs-api-docs';

		return $screens;
	}

	/**
	 * @param string[] $slugs Prefix-stripped screen ids getting the body class.
	 * @return string[]
	 */
	public function admin_screen_slugs( $slugs ) {
		$slugs[] = 'betterdocs-api-docs';

		return $slugs;
	}

	/**
	 * The reference URL (`/docs/api/{slug}/`) 301s to its generated Introduction
	 * doc — a native `docs` post that renders through the standard single-doc
	 * template with the normal knowledge-base sidebar (endpoints grouped by tag).
	 * There is no custom API-reference template or sidebar renderer.
	 *
	 * A non-materialized reference (no intro yet) falls through to the theme's
	 * default single template.
	 *
	 * @return void
	 */
	public function redirect_to_intro() {
		if ( ! is_singular( $this->post_type ) || is_preview() ) {
			return;
		}

		$intro_id = (int) get_post_meta( get_queried_object_id(), '_bd_api_intro_doc_id', true );

		if ( $intro_id && 'publish' === get_post_status( $intro_id ) ) {
			$target = get_permalink( $intro_id );
			if ( $target ) {
				wp_safe_redirect( $target, 301 );
				exit;
			}
		}
	}

	/**
	 * Register (not enqueue) the render assets; render_reference() enqueues
	 * them wherever a reference actually appears (template, shortcode, block).
	 *
	 * @return void
	 */
	public function register_assets() {
		// Register the Try-it modal bundle + the API-docs stylesheet (endpoint-doc
		// banner/tables/method badges) — served from Pro's assets dir. Pro's
		// Frontend::enqueue_tryit enqueues them on the materialized doc singles.
		betterdocs_pro()->assets->register( 'betterdocs-api-reference', 'public/js/api-reference.js', [ 'wp-element', 'wp-i18n' ] );

		// Registered by hand rather than through the assets manager: that gives
		// every stylesheet the plugin version, so a rebuilt CSS keeps the old
		// `?ver=` and browsers serve the stale copy until the next release. The
		// JS is fine (webpack writes a content hash into its .asset.php); the CSS
		// has no such file, so key it on the built file's mtime.
		$css = 'public/css/api-reference.css';

		wp_register_style(
			'betterdocs-api-reference',
			betterdocs_pro()->assets->asset_url( $css ),
			[],
			$this->asset_version( $css )
		);

		// Method badges alone. Doc lists render these on category archives, the
		// sidebar and search — views with no reason to load the bundle above —
		// so they get their own ~1KB stylesheet. Both share the same rules via
		// the `_api-method-badge` partial.
		$badge_css = 'public/css/api-method-badge.css';

		wp_register_style(
			'betterdocs-api-method-badge',
			betterdocs_pro()->assets->asset_url( $badge_css ),
			[],
			$this->asset_version( $badge_css )
		);
	}

	/**
	 * Cache-busting version for a built asset: its mtime, falling back to the
	 * plugin version when the file is missing.
	 *
	 * @param string $file Path under Pro's `assets/`.
	 * @return string
	 */
	protected function asset_version( $file ) {
		$path = BETTERDOCS_PRO_ABSPATH . 'assets/' . $file;

		return file_exists( $path ) ? (string) filemtime( $path ) : BETTERDOCS_PRO_VERSION;
	}

	/**
	 * Frontend embed of a reference (shortcode / block). The reference itself is
	 * a native knowledge base — its own page renders through the standard doc
	 * template — so the embed shows the generated Introduction content inline
	 * plus a link to the full reference. No server-side spec renderer.
	 *
	 * @param int $reference_id
	 * @return string
	 */
	public function render_reference( $reference_id ) {
		$post = get_post( $reference_id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return '';
		}

		// Same gate as the reference's own page (ApiReferenceVisibility::
		// guard_frontend). Without it the shortcode and the
		// betterdocs/api-reference block rendered a restricted reference's
		// introduction to anyone who could load the page it was embedded on,
		// while the reference's own permalink correctly answered 403.
		if (
			! current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) )
			&& ! apply_filters( 'betterdocs_api_ref_can_view', true, $post )
		) {
			return '';
		}

		$inner    = '';
		$intro_id = (int) get_post_meta( $post->ID, '_bd_api_intro_doc_id', true );

		// The generated docs are ordinary `docs` posts, so Access & Restrictions
		// (Internal Knowledge Base) governs them exactly as it governs every
		// other doc — restrict the category or KB they live in and they stop
		// being readable, in queries and on their own permalinks alike.
		//
		// This embed is the one place that would have side-stepped that, because
		// it prints the introduction's content directly instead of going through
		// a docs query. get_restricted_doc_ids() is the canonical list (it covers
		// both the basic and advanced modes and returns nothing for admins or
		// when the feature is off), so the embed now honours the same setting.
		$restricted = function_exists( 'betterdocs_pro' ) ? (array) betterdocs_pro()->get_restricted_doc_ids() : [];

		if ( $intro_id && 'publish' === get_post_status( $intro_id ) && ! in_array( $intro_id, $restricted, true ) ) {
			$intro = get_post( $intro_id );
			$inner = do_blocks( (string) $intro->post_content );
		}

		$inner .= sprintf(
			'<p class="betterdocs-apiref-embed__more"><a href="%1$s">%2$s</a></p>',
			esc_url( get_permalink( $post->ID ) ),
			esc_html__( 'Open the full API reference →', 'betterdocs-pro' )
		);

		return sprintf(
			'<div class="betterdocs-api-reference betterdocs-apiref-embed" aria-label="%1$s">%2$s</div>',
			esc_attr( $post->post_title ),
			$inner
		);
	}

	/**
	 * Render the endpoint "Try-it" banner (method + path + button) to a string.
	 *
	 * The single source of truth for the banner markup — used by the
	 * `betterdocs/api-tryit` dynamic block and by OperationContentBuilder's
	 * fallback. Anything the caller doesn't override is read from the owning
	 * reference's own meta at render time, so re-branding a reference in the API
	 * Docs drawer re-skins all of its endpoint docs with no rebuild. Then runs
	 * the `betterdocs_api_tryit_banner` filter for programmatic override.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Endpoint path.
	 * @param int    $ref_id Owning reference ID.
	 * @param array  $args   { label?, show_button?, accent_style? } —
	 *                       per-instance overrides (defaults come from the
	 *                       reference's meta).
	 * @return string
	 */
	public function render_tryit_banner( $method, $path, $ref_id, array $args = array() ) {
		$ref_id = (int) $ref_id;

		$stored_label = (string) get_post_meta( $ref_id, '_bd_api_tryit_label', true );

		$label = array_key_exists( 'label', $args )
			? (string) $args['label']
			: ( '' !== $stored_label ? $stored_label : __( 'Try it', 'betterdocs-pro' ) );

		// Unset meta means "never configured" — default to showing the button.
		$stored_enabled = (string) get_post_meta( $ref_id, '_bd_api_tryit_enabled', true );

		$show_button = array_key_exists( 'show_button', $args )
			? (bool) $args['show_button']
			: ( '0' !== $stored_enabled );

		$accent_style = array_key_exists( 'accent_style', $args )
			? (string) $args['accent_style']
			: AccentPalette::for_reference( $ref_id );

		$use_proxy = ProxyAllowlist::enabled_for( $ref_id );

		$template = BETTERDOCS_PRO_ABSPATH . 'views/templates/api-ref/tryit-banner.php';

		if ( ! file_exists( $template ) ) {
			return '';
		}

		$method = strtolower( (string) $method );
		$path   = (string) $path;

		ob_start();
		include $template;
		$html = trim( (string) ob_get_clean() );

		/**
		 * Filter the rendered Try-it banner HTML.
		 *
		 * @param string $html   Banner markup.
		 * @param string $method HTTP method (lowercase).
		 * @param string $path   Endpoint path.
		 * @param int    $ref_id Owning reference ID.
		 * @param string $label  Button label.
		 */
		return (string) apply_filters( 'betterdocs_api_tryit_banner', $html, $method, $path, $ref_id, $label );
	}

	/**
	 * Enqueue the API Docs admin micro-frontend. Loaded before the Free
	 * dashboard app so its `betterdocs_routes` filter is registered when the app
	 * renders (dequeue/enqueue/re-enqueue puts `betterdocs-admin` after it).
	 *
	 * Loaded on *every* BetterDocs screen, not just this page. Free's Routes.js
	 * only intercepts an admin-menu click when the target page is among the
	 * registered React routes; anything else falls through to a full page load.
	 * While this bundle was page-scoped the route did not exist until you were
	 * already on the page, so clicking "API Docs" from Dashboard/Categories/etc.
	 * hard-reloaded while every Free menu item navigated in-app.
	 *
	 * The stylesheet has to come along for the same reason: once the route is
	 * client-side you can land on this screen without ever requesting it, so the
	 * CSS must already be on the page or the table renders unstyled. It is
	 * namespaced under .bd-api-*, and the one rule that reaches the shared admin
	 * header is scoped to this page's body class.
	 *
	 * Cost is ~183KB against the ~4MB dashboard.js already loading here.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function enqueue_admin_app( $hook ) {
		// Same gate Free uses to register `betterdocs-admin` (Core\Admin::scripts);
		// register_api_docs_screen() puts this page's hook on that list.
		if ( ! betterdocs()->is_betterdocs_screen( $hook ) ) {
			return;
		}

		wp_dequeue_script( 'betterdocs-admin' );
		betterdocs_pro()->assets->enqueue( 'betterdocs-api-docs', 'admin/js/api-docs.js' );
		betterdocs_pro()->assets->enqueue( 'betterdocs-api-docs', 'admin/css/api-docs.css' );
		wp_enqueue_script( 'betterdocs-admin' );
	}

	/**
	 * Enqueue + localize the Try-it modal bundle. Shared by the reference
	 * renderer and Pro's endpoint-doc singles, so both mount the same modal.
	 *
	 * @return void
	 */
	public function enqueue_tryit_assets() {
		// The native sections reuse the endpoint-doc base styling (banner,
		// tables, method badges) that ships in the single-doc stylesheet, so
		// the reference renders correctly even as a shortcode/block embed.
		wp_enqueue_style( 'betterdocs-single' );

		betterdocs_pro()->assets->enqueue( 'betterdocs-api-reference', 'public/js/api-reference.js', [ 'wp-element', 'wp-i18n' ] );

		// Registered (with an mtime version) in register_assets — which has not
		// run on every path that reaches here, so make sure of it first.
		if ( ! wp_style_is( 'betterdocs-api-reference', 'registered' ) ) {
			$this->register_assets();
		}

		wp_enqueue_style( 'betterdocs-api-reference' );

		betterdocs_pro()->assets->localize(
			'betterdocs-api-reference',
			'betterdocsApiRef',
			$this->frontend_config()
		);
	}

	/**
	 * Config localized to the native renderer (explorer + endpoint-doc drawer).
	 * Public so Pro's endpoint-doc mount reuses the exact same values.
	 *
	 * @return array
	 */
	public function frontend_config() {
		return [
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			// Base for `{restBase}{id}/spec` used by the endpoint-doc Try-it modal.
			'restBase' => rest_url( 'betterdocs/v1/api-ref/' ),
			// Proxy base only — whether a request actually goes through it is per
			// reference (`data-proxy` on the banner button, `ref` on the call), so
			// one page can hold an API that proxies and one that calls direct.
			'proxyUrl' => rest_url( 'betterdocs/v1/api-ref/proxy' ),
		];
	}

	/**
	 * Deleting a reference removes its stored specs.
	 *
	 * @param int           $post_id
	 * @param \WP_Post|null $post
	 * @return void
	 */
	public function cleanup_spec_rows( $post_id, $post = null ) {
		$post = $post ?: get_post( $post_id );

		if ( $post && $post->post_type === $this->post_type ) {
			( new ApiSpec\SpecStore() )->delete_for( $post_id );
		}
	}

	/**
	 * Register the post type + meta, and queue a one-time rules flush.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_post_type();
		$this->register_meta();

		// The generated CPT rules land *after* the extra_rules_top rules that
		// Rewrite::rules() (init 10) registers — and its broad
		// `^docs/([^/]+)/?$` knowledge-base rule would swallow `docs/api/{slug}`.
		// Adding this rule here (init 8) puts it ahead of those in insertion order.
		add_rewrite_rule(
			'^' . $this->rewrite_base() . '/([^/]+)/?$',
			'index.php?' . $this->post_type . '=$matches[1]',
			'top'
		);

		$this->maybe_flush_rewrite_rules();
		$this->migrate_proxy_settings();
	}

	/**
	 * Carry the retired site-wide Try-it proxy settings onto the references.
	 *
	 * The proxy used to be one switch + one host list in BetterDocs Settings →
	 * API Documentation. That tab is gone (both live on each reference now), so
	 * without this an upgrade would silently stop forwarding requests that used
	 * to work. Runs once; only fills references that have no proxy meta yet.
	 *
	 * @return void
	 */
	protected function migrate_proxy_settings() {
		if ( get_option( 'betterdocs_api_proxy_migrated' ) ) {
			return;
		}

		// Claim it first — a failure here must not retry on every request.
		update_option( 'betterdocs_api_proxy_migrated', 1, false );

		$settings = get_option( 'betterdocs_settings', [] );
		$settings = is_array( $settings ) ? $settings : [];
		$enabled  = ! empty( $settings['api_ref_proxy_enabled'] );
		$hosts    = ProxyAllowlist::format(
			ProxyAllowlist::parse( isset( $settings['api_ref_proxy_allowlist'] ) ? (string) $settings['api_ref_proxy_allowlist'] : '' )
		);

		if ( ! $enabled && '' === $hosts ) {
			return;
		}

		$references = get_posts(
			[
				'post_type'   => $this->post_type,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids'
			]
		);

		foreach ( $references as $ref_id ) {
			if ( '' === (string) get_post_meta( $ref_id, '_bd_api_proxy_enabled', true ) ) {
				update_post_meta( $ref_id, '_bd_api_proxy_enabled', $enabled ? '1' : '0' );
			}

			if ( '' === (string) get_post_meta( $ref_id, '_bd_api_proxy_hosts', true ) ) {
				update_post_meta( $ref_id, '_bd_api_proxy_hosts', $hosts );
			}
		}
	}

	/**
	 * Public URL base for references. `/docs/api/{slug}/` per the PRD, filterable.
	 *
	 * @return string
	 */
	public function rewrite_base() {
		return apply_filters( 'betterdocs_api_ref_rewrite_base', 'docs/api' );
	}

	/**
	 * How many references this install may create. Pro-only feature → unlimited
	 * (filterable).
	 *
	 * @return int
	 */
	public function max_references() {
		return (int) apply_filters( 'betterdocs_api_ref_max_references', PHP_INT_MAX );
	}

	/**
	 * How many operations the spec route may serve. Pro-only → unlimited
	 * (filterable).
	 *
	 * @return int
	 */
	public function max_rendered_operations() {
		return (int) apply_filters( 'betterdocs_api_ref_max_operations', PHP_INT_MAX );
	}

	/**
	 * Upload/spec size cap in bytes (10 MB, filterable).
	 *
	 * @return int
	 */
	public function max_spec_bytes() {
		return (int) apply_filters( 'betterdocs_api_ref_max_spec_bytes', 10 * MB_IN_BYTES );
	}

	/**
	 * Register the betterdocs_api_ref post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = [
			'name'               => __( 'API References', 'betterdocs-pro' ),
			'singular_name'      => __( 'API Reference', 'betterdocs-pro' ),
			'add_new_item'       => __( 'Add New API Reference', 'betterdocs-pro' ),
			'new_item'           => __( 'New API Reference', 'betterdocs-pro' ),
			'edit_item'          => __( 'Edit API Reference', 'betterdocs-pro' ),
			'view_item'          => __( 'View API Reference', 'betterdocs-pro' ),
			'all_items'          => __( 'API References', 'betterdocs-pro' ),
			'search_items'       => __( 'Search API References', 'betterdocs-pro' ),
			'not_found'          => __( 'No API references found', 'betterdocs-pro' ),
			'not_found_in_trash' => __( 'No API references found in trash', 'betterdocs-pro' )
		];

		register_post_type(
			$this->post_type,
			[
				'labels'              => $labels,
				'description'         => __( 'OpenAPI-powered API reference documentation', 'betterdocs-pro' ),
				'public'              => true,
				'publicly_queryable'  => true,
				// Managed exclusively from the BetterDocs admin app + REST surface.
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				// The reference page itself stays out of WP search in v1.0;
				// per-endpoint docs become searchable in v1.5.
				'exclude_from_search' => true,
				'query_var'           => true,
				'capability_type'     => [ 'doc', 'docs' ],
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'has_archive'         => false,
				'supports'            => [ 'title' ],
				'rewrite'             => [
					'slug'       => $this->rewrite_base(),
					'with_front' => false,
					'feeds'      => false,
					'pages'      => false
				]
			]
		);
	}

	/**
	 * Reference settings, stored as post meta.
	 *
	 * @return void
	 */
	public function register_meta() {
		$string_meta = [
			'_bd_api_source'        => [ $this, 'sanitize_source' ],
			'_bd_api_source_url'    => 'esc_url_raw',
			'_bd_api_sync_interval' => [ $this, 'sanitize_sync_interval' ],
			'_bd_api_visibility'    => [ $this, 'sanitize_visibility' ],
			'_bd_api_theme'         => [ $this, 'sanitize_theme' ],
			// Materialization destination: 'kb' (own KB) | 'category' (under the
			// chosen existing KB, _bd_api_kb_slug). Read by resolve_kb().
			'_bd_api_kb_mode'       => [ $this, 'sanitize_kb_mode' ],
			'_bd_api_kb_slug'       => 'sanitize_title',
			// Original upload format: 'openapi' (native) | 'postman' (converted at
			// ingest). The stored spec is always OpenAPI; this just badges the row.
			'_bd_api_source_kind'   => [ $this, 'sanitize_source_kind' ],
			// Try-it banner branding, set per reference in the Create/Edit drawer
			// and read at render time — so changing them re-skins every endpoint
			// doc of this reference with no rebuild. Empty accent = stock green.
			'_bd_api_accent_color'      => [ $this, 'sanitize_hex_color' ],
			'_bd_api_accent_text_color' => [ $this, 'sanitize_hex_color' ],
			'_bd_api_tryit_label'       => 'sanitize_text_field',
			// '1' | '0' — string, mirroring _bd_api_materialize, because an empty
			// boolean meta is indistinguishable from "never set".
			'_bd_api_tryit_enabled'     => [ $this, 'sanitize_flag' ],
			// 'light' | 'dark' — the theme the generated Code Snippet / Code
			// Snippet Tab blocks are created with. Unlike the accent, this one IS
			// baked into the blocks (it is their own attribute), so changing it
			// rewrites them across the reference's docs in one pass.
			'_bd_api_code_theme'        => [ $this, 'sanitize_code_theme' ],
			// Try-it proxy, per reference: '1'|'0' plus the hosts it may forward
			// to (one bare hostname per line). Enabling one API's proxy must not
			// open it for another, hence per reference rather than a site option.
			'_bd_api_proxy_enabled'     => [ $this, 'sanitize_flag' ],
			'_bd_api_proxy_hosts'       => [ $this, 'sanitize_proxy_hosts' ]
		];

		foreach ( $string_meta as $key => $sanitize ) {
			register_post_meta(
				$this->post_type,
				$key,
				[
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => [ $this, 'meta_auth' ]
				]
			);
		}

		register_post_meta(
			$this->post_type,
			'_bd_api_visibility_roles',
			[
				'type'              => 'array',
				'single'            => true,
				'sanitize_callback' => [ $this, 'sanitize_roles' ],
				'auth_callback'     => [ $this, 'meta_auth' ]
			]
		);

		// Cached normalized summary of the active spec (version, operation
		// count, tags, servers, hash) — written by the ingestion flow only.
		register_post_meta(
			$this->post_type,
			'_bd_api_spec_summary',
			[
				'type'          => 'object',
				'single'        => true,
				'auth_callback' => [ $this, 'meta_auth' ]
			]
		);

		// Endpoint materialization (Pro writes these; Free registers them so
		// the base can reason about state — see is_materialized()).
		register_post_meta(
			$this->post_type,
			'_bd_api_materialize',
			[
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return '1' === (string) $value ? '1' : '0';
				},
				'auth_callback'     => [ $this, 'meta_auth' ]
			]
		);

		// Materialization run state: { state, time, progress{done,total}, report }.
		register_post_meta(
			$this->post_type,
			'_bd_api_materialize_status',
			[
				'type'          => 'object',
				'single'        => true,
				'auth_callback' => [ $this, 'meta_auth' ]
			]
		);

		foreach ( [ '_bd_api_parent_term_id', '_bd_api_intro_doc_id' ] as $int_key ) {
			register_post_meta(
				$this->post_type,
				$int_key,
				[
					'type'              => 'integer',
					'single'            => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => [ $this, 'meta_auth' ]
				]
			);
		}

		// AI overlay: { ops: { op_key: { sig, description?, examples? } }, hash }.
		// Read-side only — merged into the served spec + the materializer load.
		register_post_meta(
			$this->post_type,
			'_bd_api_ai_overlay',
			[
				'type'          => 'object',
				'single'        => true,
				'auth_callback' => [ $this, 'meta_auth' ]
			]
		);

		// AI generation run state: { state, time, progress{done,total}, report }.
		register_post_meta(
			$this->post_type,
			'_bd_api_ai_status',
			[
				'type'          => 'object',
				'single'        => true,
				'auth_callback' => [ $this, 'meta_auth' ]
			]
		);
	}

	/**
	 * Whether a reference has endpoint-docs materialization enabled.
	 *
	 * @param int $reference_id
	 * @return bool
	 */
	public function is_materialized( $reference_id ) {
		return '1' === get_post_meta( $reference_id, '_bd_api_materialize', true );
	}

	public function meta_auth() {
		return current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) );
	}

	public function sanitize_source( $value ) {
		return in_array( $value, [ 'upload', 'url' ], true ) ? $value : 'upload';
	}

	public function sanitize_sync_interval( $value ) {
		return in_array( $value, [ 'manual', 'hourly', 'daily', 'weekly' ], true ) ? $value : 'manual';
	}

	public function sanitize_visibility( $value ) {
		return in_array( $value, [ 'public', 'logged_in', 'roles' ], true ) ? $value : 'public';
	}

	public function sanitize_theme( $value ) {
		return in_array( $value, [ 'auto', 'light', 'dark' ], true ) ? $value : 'auto';
	}

	public function sanitize_roles( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_intersect( array_map( 'sanitize_key', $value ), array_keys( wp_roles()->roles ) ) );
	}

	public function sanitize_kb_mode( $value ) {
		return 'category' === $value ? 'category' : 'kb';
	}

	public function sanitize_source_kind( $value ) {
		return in_array( $value, [ 'postman', 'swagger' ], true ) ? $value : 'openapi';
	}

	/**
	 * `#rrggbb` or '' (unset — falls back to the stock palette).
	 *
	 * @param string $value
	 * @return string
	 */
	public function sanitize_hex_color( $value ) {
		$hex = AccentPalette::normalize( $value );

		return null === $hex ? '' : $hex;
	}

	/**
	 * '1' | '0' string flag.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function sanitize_flag( $value ) {
		return ( '0' === $value || 0 === $value || false === $value ) ? '0' : '1';
	}

	/**
	 * Code Snippet colour mode — 'light' (default) | 'dark'.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function sanitize_code_theme( $value ) {
		return 'dark' === $value ? 'dark' : 'light';
	}

	/**
	 * Proxy allowlist — normalized to one bare hostname per line, so what the
	 * proxy compares against is exactly what the admin sees stored back.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function sanitize_proxy_hosts( $value ) {
		return ProxyAllowlist::format( ProxyAllowlist::parse( (string) $value ) );
	}

	/**
	 * Resolve the knowledge base a reference's generated docs go into, from its
	 * Create-drawer choice. `betterdocs_api_docs_knowledge_base` filter callback:
	 *  - category mode + a chosen KB slug → reuse that existing KB (no auto-create)
	 *  - otherwise → null: TermManager auto-creates a KB named after the reference.
	 *
	 * @param string|null $slug
	 * @param \WP_Post     $reference
	 * @return string|null
	 */
	public function resolve_kb( $slug, $reference ) {
		if ( 'category' === get_post_meta( $reference->ID, '_bd_api_kb_mode', true ) ) {
			$chosen = get_post_meta( $reference->ID, '_bd_api_kb_slug', true );
			if ( $chosen ) {
				return $chosen;
			}
		}

		return $slug;
	}

	/**
	 * Flush rewrite rules once after this CPT first ships (or its rules change),
	 * using the same transient the docs post type consumes on the next init.
	 *
	 * @return void
	 */
	protected function maybe_flush_rewrite_rules() {
		if ( get_option( 'betterdocs_api_ref_rules_version' ) === '1.0.0' ) {
			return;
		}

		update_option( 'betterdocs_api_ref_rules_version', '1.0.0' );
		betterdocs()->database->set_transient( 'betterdocs_flush_rewrite_rules', true );
	}
}
