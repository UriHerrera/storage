<?php

namespace WPDeveloper\BetterDocsPro\Insights;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product-usage analytics collector (Pro tier).
 *
 * Hooks the shared `betterdocs_insights_data` filter (exposed by Free's
 * `WPDeveloper\BetterDocs\Insights\Insights::get_data()`) and contributes Pro-only
 * signals. Because this class only loads when Pro is active, its keys appear in the
 * wpinsight payload iff Pro is active — that is how the Free/Pro separation stays
 * clean (no runtime is_plugin_active checks needed).
 *
 * Runs at priority 20 so it sees the groups seeded by the Free collector (priority 10)
 * and can merge Pro-gated flags into `bd_features` without clobbering Free keys.
 *
 * MAINTENANCE CONVENTION: when a new Pro feature/setting is introduced, add its
 * flag/metric here and update betterdocs/docs/insights-tracking.md.
 *
 * @since 3.8.5
 */
class Collector {
	const CACHE_KEY = 'betterdocs_pro_insights_cache';

	/**
	 * Pro-gated boolean feature flags merged into `bd_features`.
	 *
	 * @var string[]
	 */
	const FEATURE_FLAGS = [
		'multiple_kb',
		'advance_search',
		'enable_content_restriction',
		'enable_glossaries',
		'enable_encyclopedia',
		'enable_git_integration',
		'collect_analytics_data',
		'unique_visitor_count',
		'exclude_bot_analytics',
		'enable_disable',   // Instant Answer master toggle
		'ia_reaction',
		'show_attachment',
		'show_related_docs',
		'enable_write_with_ai_git', // "Write with AI from Git" master toggle (3.9.5)
	];

	public function __construct() {
		add_filter( 'betterdocs_insights_data', [ $this, 'collect' ], 20, 1 );
		add_action( 'betterdocs::settings::saved', [ $this, 'flush_cache' ] );
	}

	/**
	 * @param array $body
	 * @return array
	 */
	public function collect( $body ) {
		$cached = get_transient( self::CACHE_KEY );
		if ( ! is_array( $cached ) ) {
			$cached = [
				'features'    => $this->features(),
				'bd_pro'      => $this->pro(),
				'bd_api_docs' => $this->api_docs(),
			];
			set_transient( self::CACHE_KEY, $cached, DAY_IN_SECONDS );
		}

		$body = (array) $body;

		// All metric groups live inside `optional_data` (wpinsight persists only
		// the base fields + that single field, JSON-encoded in Insights::get_data()).
		$opt = ( isset( $body['optional_data'] ) && is_array( $body['optional_data'] ) ) ? $body['optional_data'] : [];

		// Merge Pro flags into the bd_features group seeded by Free (don't clobber).
		$opt['bd_features'] = array_merge(
			isset( $opt['bd_features'] ) && is_array( $opt['bd_features'] ) ? $opt['bd_features'] : [],
			$cached['features']
		);

		$opt['bd_pro'] = $cached['bd_pro'];

		// `?? []` guards the window after a plugin update where a pre-existing
		// transient (cached under the OLD shape, before bd_api_docs existed) is
		// still live: the key is simply absent until the 24h TTL lapses or a
		// settings save flushes the cache, and must not fatal in the meantime.
		$opt['bd_api_docs'] = $cached['bd_api_docs'] ?? [];

		$body['optional_data'] = $opt;

		return $body;
	}

	public function flush_cache() {
		delete_transient( self::CACHE_KEY );
	}

	protected function features() {
		$settings = betterdocs()->settings;
		$flags    = [];
		foreach ( self::FEATURE_FLAGS as $key ) {
			$flags[ $key ] = (int) (bool) $settings->get( $key, false );
		}
		return $flags;
	}

	protected function pro() {
		$kb_count = 0;
		if ( taxonomy_exists( 'knowledge_base' ) ) {
			$terms    = get_terms( [ 'taxonomy' => 'knowledge_base', 'hide_empty' => false ] );
			$kb_count = is_wp_error( $terms ) ? 0 : count( $terms );
		}

		return [
			'is_pro_active'    => 1,
			'kb_count'         => $kb_count,
			'internal_kb_type' => (string) betterdocs()->settings->get( 'internal_knowledge_base_type', '' ),
			'license_status'   => (string) get_option( 'betterdocs_pro_software__license_status', '' ),
			'analytics'        => $this->analytics(),
		];
	}

	/**
	 * API Documentation (v1.0+) adoption + volume.
	 *
	 * Answers the two questions the generic content counts cannot: how many
	 * installs actually use the feature, and how many docs it generated. The
	 * feature stores an OpenAPI spec as a `betterdocs_api_ref` CPT post and
	 * materializes each operation into an ordinary `post_type = docs` post tagged
	 * with `_bd_api_ref_id` meta — so those endpoint docs are already folded into
	 * `bd_counts.docs` and are otherwise indistinguishable from hand-written docs.
	 * Counting the meta separates them out.
	 *
	 * Booleans + counts only — no spec contents, URLs or titles are ever shipped.
	 *
	 * @return array{enabled:int,references:int,api_docs_created:int,avg_docs_per_reference:float}
	 */
	protected function api_docs() {
		global $wpdb;

		$references = (int) ( wp_count_posts( 'betterdocs_api_ref' )->publish ?? 0 );

		// One indexed COUNT over the meta that links a materialized endpoint doc
		// back to its API Reference. DISTINCT because a doc could carry the meta
		// once, but the JOIN is defensive against duplicate rows.
		$api_docs_created = (int) $wpdb->get_var(
			"SELECT COUNT( DISTINCT p.ID )
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
			 WHERE p.post_type = 'docs' AND p.post_status = 'publish' AND pm.meta_key = '_bd_api_ref_id'"
		);

		return [
			// Adoption: does this install actually use API Documentation? wpinsight
			// aggregates this across installs to answer "how many users use it".
			'enabled'                => (int) ( $references > 0 ),
			'references'             => $references,
			'api_docs_created'       => $api_docs_created,
			'avg_docs_per_reference' => $references > 0 ? round( $api_docs_created / $references, 2 ) : 0,
		];
	}

	/**
	 * Advanced Analytics (3.9.5) posture. Role access is reported as booleans only —
	 * the actual role names are never shipped.
	 *
	 * Two generations of keys ship together on purpose:
	 *
	 * - `*_roles_gated` — the ORIGINAL metric, left byte-for-byte as it was so the
	 *   series already collected stays continuous and comparable. Do not "fix" it;
	 *   its value is only meaningful if it keeps behaving exactly as it always has.
	 * - `*_roles_broadened` — the corrected metric (see roles_broadened()). This is
	 *   the one to read for data collected from 4.1.0 onward.
	 *
	 * Retire `*_roles_gated` once the WPInsight dashboard no longer reads it.
	 */
	protected function analytics() {
		$settings = betterdocs()->settings;

		$default        = [ 'administrator' ];
		$article_roles  = (array) $settings->get( 'article_roles', $default );
		$settings_roles = (array) $settings->get( 'settings_roles', $default );

		return [
			// Legacy — frozen deliberately, including the order-sensitivity, so the
			// historical series does not shift meaning underneath the dashboard.
			'article_roles_gated'      => (int) ( array_values( $article_roles ) !== $default ),
			'settings_roles_gated'     => (int) ( array_values( $settings_roles ) !== $default ),
			// Corrected replacements.
			'article_roles_broadened'  => $this->roles_broadened( $article_roles ),
			'settings_roles_broadened' => $this->roles_broadened( $settings_roles ),
		];
	}

	/**
	 * Has this allow-list been opened up beyond administrators?
	 *
	 * Deliberately NOT "does it differ from the default". Core\Roles::reset_settings()
	 * rewrites an empty list back to ['administrator'] and re-appends 'administrator'
	 * whenever it is missing, so the saved value always contains it and is never
	 * empty — a list *narrower* than the administrator-only default is unreachable.
	 * The only real question is therefore whether any other role was added, which is
	 * what the `*_broadened` keys report: 1 = someone else can get in, 0 = admins only.
	 *
	 * These keys supersede `article_roles_gated` / `settings_roles_gated`, which
	 * compared against the default with `!==` and so reported 1 for a *widened*
	 * list — labelling loosened access as "gated", the exact inverse of the truth.
	 * A new key rather than a re-polarised old one, so the historical series is
	 * never rewritten: the old keys keep shipping unchanged alongside these.
	 *
	 * @param array $roles Saved role slugs.
	 * @return int 1 when a non-administrator role is present, else 0.
	 */
	private function roles_broadened( array $roles ) {
		$roles = array_unique( array_filter( array_map( 'strval', $roles ) ) );

		// array_diff is order- and duplicate-insensitive, so ['editor','administrator']
		// and ['administrator','editor','editor'] both report the same.
		return (int) ( ! empty( array_diff( $roles, [ 'administrator' ] ) ) );
	}
}
