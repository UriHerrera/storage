<?php
/**
 * Abilities registrar.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers BetterDocs' abilities with the WordPress Abilities API.
 *
 * Registration is **always** on when the API is available — it is deliberately
 * not gated by the `enable_mcp` toggle. Every ability permission-checks itself
 * on each call, so registering exposes nothing; it only makes BetterDocs
 * discoverable to generic Abilities clients the way WordPress core abilities
 * are. Coupling the two meant a fresh install, where `enable_mcp` defaults to
 * off, registered zero abilities and was invisible to every connector. The
 * developer kill switch is `betterdocs_abilities_api_enabled`.
 *
 * @since 4.9.0
 */
class AbilitiesRegistrar {

	/**
	 * Ability-name prefixes that mark an ability as BetterDocs'. Free owns
	 * `betterdocs/`; Pro registers under `betterdocs-pro/` through the
	 * `betterdocs_register_abilities` filter.
	 *
	 * @since 4.9.0
	 */
	public const ABILITY_PREFIXES = [ 'betterdocs/', 'betterdocs-pro/' ];

	/**
	 * Ability category slug.
	 *
	 * @since 4.9.0
	 */
	public const CATEGORY = 'betterdocs';

	/**
	 * Whether the replay in {@see self::ensure_registered()} has already run
	 * this request. One attempt only — a replay that produced nothing will not
	 * produce anything the second time either, and the MCP server asks for the
	 * tool list more than once per request.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private static $replayed = false;

	/**
	 * Our own ability objects, by id.
	 *
	 * `wp_register_ability()` builds the runtime's `WP_Ability` from the array we
	 * hand it, so afterwards our instance is reachable only through the two
	 * callbacks. `MCPTools` needs more than that — `describe()` for the
	 * live description, `requires_pro()`, `get_capability()`, the annotations —
	 * so we keep our own map.
	 *
	 * @since 4.9.0
	 *
	 * @var array<string, AbilityBase>
	 */
	private static $instances = [];

	/**
	 * Hooks the registrar up.
	 *
	 * Both hooks are added **unconditionally** — no `function_exists( 'wp_register_ability' )`
	 * early return. The Abilities API is a set of global functions behind
	 * `function_exists` guards, so which copy owns them (core's, ours in
	 * `dependencies/`, another plugin's) is decided by load order, not by us. An
	 * early return here would leave BetterDocs permanently unregistered on any
	 * site where the API lands after the plugin. The callbacks carry the guards
	 * instead, which makes hooking free when no API ever appears.
	 *
	 * @since 4.9.0
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', [ $this, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );
	}

	/**
	 * The ability instance behind an id, when this request registered it.
	 *
	 * @since 4.9.0
	 *
	 * @param string $id Ability id.
	 * @return AbilityBase|null
	 */
	public static function instance( $id ) {
		return isset( self::$instances[ $id ] ) ? self::$instances[ $id ] : null;
	}

	/**
	 * Every ability instance this request registered, by id.
	 *
	 * @since 4.9.0
	 *
	 * @return array<string, AbilityBase>
	 */
	public static function instances() {
		return self::$instances;
	}

	/**
	 * Registers the BetterDocs ability category.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function register_category() {
		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( self::CATEGORY ) ) {
			return;
		}

		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			[
				'label'       => __( 'BetterDocs', 'betterdocs' ),
				'description' => __( 'Create and manage docs, categories, tags, FAQs, knowledge bases, settings and analytics.', 'betterdocs' )
			]
		);
	}

	/**
	 * Registers BetterDocs' abilities.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function register_abilities() {
		if ( ! AbilityBase::abilities_enabled() ) {
			return;
		}

		foreach ( $this->build_abilities() as $id => $ability ) {
			// Recorded before the duplicate check: the instance map is what
			// MCPTools reads, and an ability someone else registered first is
			// still ours to describe.
			self::$instances[ $id ] = $ability;

			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $id ) ) {
				continue;
			}

			$ability->register();
		}
	}

	/**
	 * Builds the final `[ id => AbilityBase ]` map: Free abilities, then Pro
	 * stubs, then whatever the filter returns.
	 *
	 * Kept separate from {@see self::register_abilities()} so the composition
	 * rules — by-id replacement, and the two skips — are testable without a
	 * WordPress registry to register into.
	 *
	 * The result is re-keyed by `get_id()` **after** the filter, which is what
	 * makes Pro's registrar work: it returns real ability objects carrying the
	 * same ids as Free's stubs, and they replace the stubs by name whether the
	 * filter kept our keys or appended with numeric ones.
	 *
	 * @since 4.9.0
	 *
	 * @return array<string, AbilityBase>
	 */
	public function build_abilities() {
		$list = [];

		foreach ( array_merge( $this->free_abilities(), $this->stub_abilities() ) as $ability ) {
			if ( $ability instanceof AbilityBase ) {
				$list[ $ability->get_id() ] = $ability;
			}
		}

		/**
		 * Filters the abilities BetterDocs registers.
		 *
		 * Return objects with the same ids to replace them — this is how Pro
		 * swaps Free's placeholder stubs for the real implementations without
		 * the tool name ever changing.
		 *
		 * @since 4.9.0
		 *
		 * @param array<string, AbilityBase> $list Abilities by id.
		 */
		$filtered = apply_filters( 'betterdocs_register_abilities', $list );

		$final = [];

		foreach ( (array) $filtered as $ability ) {
			if ( ! $ability instanceof AbilityBase ) {
				continue;
			}

			if ( ! $ability->meets_capability_policy() || ! $ability->is_enabled() ) {
				continue;
			}

			$final[ $ability->get_id() ] = $ability;
		}

		return $final;
	}

	/**
	 * Free's own abilities.
	 *
	 * @since 4.9.0
	 *
	 * @return AbilityBase[]
	 */
	protected function free_abilities() {
		return [
			new Status\GetStatus(),
			new Docs\CreateDoc(),
			new Docs\UpdateDoc(),
			new Docs\GetDoc(),
			new Docs\ListDocs(),
			new Docs\DeleteDoc(),
			new Terms\CreateTerm(),
			new Terms\UpdateTerm(),
			new Terms\DeleteTerm(),
			new Terms\ListTerms(),
			new Faq\CreateFAQGroup(),
			new Faq\UpdateFAQGroup(),
			new Faq\DeleteFAQGroup(),
			new Faq\ListFAQGroups(),
			new Faq\CreateFAQ(),
			new Faq\UpdateFAQ(),
			new Faq\DeleteFAQ(),
			new Faq\ListFAQs(),
			new Faq\AttachFAQ(),
			new Settings\GetSettingsSchema(),
			new Settings\GetSettings(),
			new Settings\UpdateSettings(),
			new Analytics\GetDocAnalytics()
		];
	}

	/**
	 * Placeholder abilities for the Pro-only tools, so the catalog is the same
	 * shape on every site. Pro replaces them by id.
	 *
	 * @since 4.9.0
	 *
	 * @return AbilityBase[]
	 */
	protected function stub_abilities() {
		$stubs = [];

		foreach ( ProStubs::specs() as $spec ) {
			$stubs[] = new StubAbility( $spec );
		}

		return $stubs;
	}

	/**
	 * Guarantees BetterDocs' abilities are in the registry, replaying
	 * registration once if they are not.
	 *
	 * `wp_abilities_api_init` fires exactly once per request, from the lazy
	 * registry singleton of whichever copy owns the globals. If a foreign copy
	 * owns them and fires before our hook is attached, our callback never runs:
	 * the registry fills up with everyone else's abilities and `tools/list`
	 * answers `[]` while auth, discovery and `initialize` all report success.
	 *
	 * Reading `wp_get_abilities()` first forces that lazy init, so by the time we
	 * decide to replay, the action has fired and `wp_register_ability()` will
	 * accept us. Per-ability `wp_has_ability()` checks keep the replay idempotent,
	 * so it is a no-op after a healthy hook run.
	 *
	 * The replay is skipped entirely when core owns the registry: core's
	 * `wp_register_ability()` refuses anything outside the running
	 * `wp_abilities_api_init` action, so replaying there registers nothing and
	 * only logs (ADR-032). The count is still returned, and the diagnostic
	 * summary is logged under `WP_DEBUG` when it is zero.
	 *
	 * @since 4.9.0
	 *
	 * @param callable|null $replay Optional replay routine, for tests.
	 * @return int BetterDocs abilities in the registry afterwards.
	 */
	public static function ensure_registered( $replay = null ) {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return 0;
		}

		// Forces the registry's lazy init, and with it `wp_abilities_api_init`.
		$count = self::count_registered();

		// On WordPress 6.9+, where core owns the registry, `wp_register_ability()`
		// only works *while* `wp_abilities_api_init` is running — core checks
		// `doing_action()`, not `did_action()`. A replay after the fact therefore
		// cannot register anything; it would only write one `_doing_it_wrong()`
		// notice per ability into the log. Say so once instead (ADR-032).
		if ( 'core' === Runtime::owner()['source'] ) {
			if ( 0 === $count && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
				error_log( '[BD-MCP] No BetterDocs abilities registered and WordPress core owns the Abilities API, so registration cannot be replayed. ' . self::summary() );
			}

			return $count;
		}

		if ( $count > 0 || self::$replayed ) {
			return $count;
		}

		// Before the registry has initialised, `wp_register_ability()` refuses
		// and calls `_doing_it_wrong()`. Nothing to replay into yet.
		if ( ! function_exists( 'did_action' ) || ! did_action( 'wp_abilities_api_init' ) ) {
			return $count;
		}

		self::$replayed = true;

		if ( ! AbilityBase::abilities_enabled() ) {
			return 0;
		}

		if ( null === $replay ) {
			$registrar = new self();
			$replay    = static function () use ( $registrar ) {
				$registrar->register_category();
				$registrar->register_abilities();
			};
		}

		$replay();

		$count = self::count_registered();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
			error_log( '[BD-MCP] BetterDocs abilities were missing from the registry; replayed registration. ' . self::summary() );
		}

		return $count;
	}

	/**
	 * How many BetterDocs abilities the registry currently holds.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	public static function count_registered() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return 0;
		}

		$count = 0;

		foreach ( wp_get_abilities() as $ability ) {
			if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
				continue;
			}

			foreach ( self::ABILITY_PREFIXES as $prefix ) {
				if ( 0 === strpos( (string) $ability->get_name(), $prefix ) ) {
					++$count;
					break;
				}
			}
		}

		return $count;
	}

	/**
	 * Diagnostic snapshot of the Abilities API as this request sees it.
	 *
	 * Feeds `bd-get-status`, the MCP self-test and the debug log, so "nothing
	 * registered" is distinguishable from "everything filtered out" without
	 * shell access.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public static function diagnostics() {
		$available = function_exists( 'wp_get_abilities' );
		$owner     = Runtime::owner();

		// Read the registry BEFORE `hook_fired`: `wp_get_abilities()` forces the
		// lazy singleton's init, which is what fires `wp_abilities_api_init`.
		// Reading `did_action()` first would report `hook_fired => false` in the
		// same snapshot that already counts registered abilities — an
		// internally inconsistent line for the one scenario this exists for.
		$total      = $available ? count( wp_get_abilities() ) : 0;
		$betterdocs = self::count_registered();

		return [
			'api_available' => $available,
			'owner'         => $owner,
			'foreign'       => 'foreign' === $owner['source'],
			'hook_fired'    => function_exists( 'did_action' ) ? (bool) did_action( 'wp_abilities_api_init' ) : false,
			'total'         => $total,
			'betterdocs'    => $betterdocs,
			'replayed'      => self::$replayed
		];
	}

	/**
	 * One-line, human-readable form of {@see self::diagnostics()}.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function summary() {
		$d      = self::diagnostics();
		$owners = [
			'core'    => __( 'WordPress core', 'betterdocs' ),
			'bundled' => __( "BetterDocs' bundled runtime", 'betterdocs' ),
			'foreign' => __( 'another plugin', 'betterdocs' ),
			'none'    => __( 'nobody', 'betterdocs' )
		];

		$source = $d['owner']['source'];

		return sprintf(
			'Abilities API: %s; owner: %s (%s); abilities total: %d, betterdocs: %d; init fired: %s; replayed: %s',
			$d['api_available'] ? 'present' : 'missing',
			isset( $owners[ $source ] ) ? $owners[ $source ] : $source,
			'' !== $d['owner']['path'] ? $d['owner']['path'] : 'unknown path',
			$d['total'],
			$d['betterdocs'],
			$d['hook_fired'] ? 'yes' : 'no',
			$d['replayed'] ? 'yes' : 'no'
		);
	}

	/**
	 * Clears the per-request statics. Tests only.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public static function reset() {
		self::$instances = [];
		self::$replayed  = false;
	}
}
