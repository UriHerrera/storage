<?php
/**
 * Get status ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Status;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Mcp\MCPHealth;
use WPDeveloper\BetterDocs\Mcp\MCPTools;

/**
 * Reports what BetterDocs looks like on this site.
 *
 * The first call an agent should make, and the reason every later refusal is
 * explainable rather than mysterious: it answers "is Pro here", "is Multiple
 * Knowledge Base on", "which capabilities do I hold", "what is there to work
 * with" and "which tools exist" in one round trip. An agent that calls this
 * first never has to discover a missing capability by failing a write.
 *
 * The report is assembled from {@see MCPHealth::report()} rather than
 * duplicating it — that class is already the one place that knows how to answer
 * these questions without side effects and without leaking a credential. What
 * this ability adds is the content counts, the tool catalog, Pro's role
 * settings and the human-readable `notes`.
 *
 * Read-only: it writes nothing and makes no outbound request.
 *
 * @since 4.9.0
 */
class GetStatus extends AbilityBase {

	/**
	 * Areas of BetterDocs deliberately left out of the MCP surface for now
	 * (ADR-020). Named in the response so an agent stops looking for a tool
	 * that was never built, instead of inferring one from the plugin's UI.
	 *
	 * @since 4.9.0
	 */
	const DEFERRED_V2 = [
		'ordering',
		'access-control',
		'import-export',
		'search',
		'layout',
		'ai-chatbot'
	];

	/**
	 * Taxonomy behind each `counts` key.
	 *
	 * @since 4.9.0
	 */
	const COUNTED_TAXONOMIES = [
		'doc_categories'  => 'doc_category',
		'doc_tags'        => 'doc_tag',
		'knowledge_bases' => 'knowledge_base',
		'faq_groups'      => 'betterdocs_faq_category',
		'glossaries'      => 'glossaries'
	];

	/**
	 * Pro settings that decide who holds BetterDocs' capabilities (ADR-033).
	 *
	 * @since 4.9.0
	 */
	const ROLE_SETTINGS = [ 'article_roles', 'settings_roles', 'analytics_roles', 'faq_roles' ];

	/**
	 * Which of those four settings grants which capability, for the three Pro
	 * reconciles individually ({@see \WPDeveloper\BetterDocs\Core\Roles::PRO_RECONCILED_CAPS};
	 * Pro's own `get_selected_roles()` is the source). Everything else belongs
	 * to the `edit_docs` bundle and is granted by `article_roles`.
	 *
	 * A remedy that names the wrong setting is worse than no remedy: an agent
	 * told to add a role to `article_roles` to gain `read_docs_analytics` makes
	 * a write that cannot work.
	 *
	 * @since 4.9.0
	 */
	const CAPABILITY_ROLE_SETTING = [
		'edit_docs_settings'  => 'settings_roles',
		'read_docs_analytics' => 'analytics_roles',
		'read_faq_builder'    => 'faq_roles'
	];

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/get-status';
		$this->label       = __( 'Get status', 'betterdocs' );
		$this->description = __( 'Get BetterDocs status: versions, Pro state, Multiple Knowledge Base state, content counts, your capabilities (held and missing by name), registered tools. Call this first.', 'betterdocs' );
		$this->capability  = 'edit_docs';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.0,
			'openWorldHint' => false
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [],
			// Required, not decorative: `WP_Ability::normalize_input()` turns a
			// missing input into this default, and without it a client that
			// simply calls the ability with no arguments — which is the only
			// sensible way to call one that takes none — fails validation with
			// "input is not of type object". Core's own no-argument abilities
			// declare it for the same reason (ADR-030).
			'default'              => []
		];
	}

	/**
	 * The full contract.
	 *
	 * Declared in full because core validates the *output* against this schema
	 * too ({@see \WP_Ability::execute()}), so it is an executable promise rather
	 * than documentation: a key that stops being emitted, or changes type,
	 * fails the call instead of quietly changing what an agent reads.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		$string_list = [
			'type'  => 'array',
			'items' => [ 'type' => 'string' ]
		];

		return [
			'type'       => 'object',
			'required'   => [
				'free_version',
				'pro',
				'multiple_kb',
				'enable_glossaries',
				'enable_mcp',
				'endpoints',
				'counts',
				'capabilities',
				'abilities',
				'tools',
				'deferred_v2',
				'notes'
			],
			'properties' => [
				'free_version'      => [ 'type' => 'string' ],
				'pro'               => [
					'type'       => 'object',
					'properties' => [
						'installed'      => [ 'type' => 'boolean' ],
						'active'         => [ 'type' => 'boolean' ],
						'version'        => [ 'type' => [ 'string', 'null' ] ],
						'license_status' => [ 'type' => 'string' ],
						'licensed'       => [ 'type' => 'boolean' ],
						'state'          => [ 'type' => 'string' ]
					]
				],
				'multiple_kb'       => [ 'type' => 'boolean' ],
				'enable_glossaries' => [ 'type' => 'boolean' ],
				'enable_mcp'        => [ 'type' => 'boolean' ],
				'endpoints'         => [
					'type'       => 'object',
					'properties' => [
						'mcp'       => [ 'type' => 'string' ],
						'mcp_rest'  => [ 'type' => 'string' ],
						'authorize' => [ 'type' => 'string' ]
					]
				],
				'counts'            => [
					'type'       => 'object',
					'properties' => [
						'docs'            => [
							'type'       => 'object',
							'properties' => [
								'created'   => [ 'type' => 'integer' ],
								'published' => [ 'type' => 'integer' ]
							]
						],
						'faqs'            => [
							'type'       => 'object',
							'properties' => [
								'created'   => [ 'type' => 'integer' ],
								'published' => [ 'type' => 'integer' ]
							]
						],
						'doc_categories'  => [ 'type' => [ 'integer', 'null' ] ],
						'doc_tags'        => [ 'type' => [ 'integer', 'null' ] ],
						// Null when Multiple Knowledge Base is off: the
						// taxonomy is not registered, so there is nothing to
						// count and 0 would be a lie.
						'knowledge_bases' => [ 'type' => [ 'integer', 'null' ] ],
						'faq_groups'      => [ 'type' => [ 'integer', 'null' ] ],
						// Null when enable_glossaries is off, for the same
						// reason: the taxonomy is not registered.
						'glossaries'      => [ 'type' => [ 'integer', 'null' ] ]
					]
				],
				'capabilities'      => [
					'type'       => 'object',
					'properties' => [
						'required'    => $string_list,
						'held'        => $string_list,
						'missing'     => $string_list,
						// Pro's role settings when Pro is active (ADR-033),
						// null on a Free-only site where the roles hold the
						// capabilities directly.
						'governed_by' => [ 'type' => [ 'object', 'null' ] ],
						'user_id'     => [ 'type' => 'integer' ],
						'user_roles'  => $string_list
					]
				],
				'abilities'         => [
					'type'       => 'object',
					'properties' => [
						'api_available' => [ 'type' => 'boolean' ],
						'owner'         => [ 'type' => 'object' ],
						'foreign'       => [ 'type' => 'boolean' ],
						'total'         => [ 'type' => 'integer' ],
						'betterdocs'    => [ 'type' => 'integer' ],
						'replayed'      => [ 'type' => 'boolean' ]
					]
				],
				'tools'             => [
					'type'       => 'object',
					'properties' => [
						'count' => [ 'type' => 'integer' ],
						'names' => $string_list
					]
				],
				'deferred_v2'       => $string_list,
				'notes'             => $string_list
			]
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array
	 */
	public function execute( $input ) {
		$health = $this->health()->report();

		$pro       = isset( $health['plugin']['pro'] ) && is_array( $health['plugin']['pro'] ) ? $health['plugin']['pro'] : [];
		$abilities = isset( $health['abilities'] ) && is_array( $health['abilities'] ) ? $health['abilities'] : [];
		$mcp       = isset( $health['mcp'] ) && is_array( $health['mcp'] ) ? $health['mcp'] : [];

		// `owner` comes from the health report's `runtime` section rather than
		// from `AbilitiesRegistrar::diagnostics()`, because that section is
		// where the version is made meaningful: `Runtime::owner()` reports null
		// when core owns the API, and the health report substitutes WordPress'
		// own version there (ADR-024). The two must not disagree.
		$owner = isset( $health['runtime']['abilities_api'] ) && is_array( $health['runtime']['abilities_api'] )
			? $health['runtime']['abilities_api']
			: [];

		$multiple_kb  = ! empty( $pro['multiple_kb'] );
		$glossaries   = taxonomy_exists( 'glossaries' );
		$counts       = $this->counts();
		$capabilities = $this->capabilities( $health, ! empty( $pro['active'] ) );
		$tools        = $this->tools();

		return [
			'free_version'      => defined( 'BETTERDOCS_VERSION' ) ? (string) BETTERDOCS_VERSION : '',
			'pro'               => [
				'installed'      => ! empty( $pro['installed'] ),
				'active'         => ! empty( $pro['active'] ),
				'version'        => isset( $pro['version'] ) && null !== $pro['version'] ? (string) $pro['version'] : null,
				'license_status' => isset( $pro['license_status'] ) ? (string) $pro['license_status'] : '',
				'licensed'       => ! empty( $pro['licensed'] ),
				'state'          => isset( $pro['state'] ) ? (string) $pro['state'] : ''
			],
			'multiple_kb'       => $multiple_kb,
			// Reported from the registered taxonomy rather than the stored
			// option: the setting only takes effect on the next request, and what
			// a tool can do right now is what the taxonomy says.
			'enable_glossaries' => $glossaries,
			'enable_mcp'        => ! empty( $mcp['enabled'] ),
			'endpoints'         => [
				'mcp'       => isset( $mcp['endpoint'] ) ? (string) $mcp['endpoint'] : '',
				'mcp_rest'  => isset( $mcp['endpoint_rest'] ) ? (string) $mcp['endpoint_rest'] : '',
				'authorize' => isset( $mcp['authorize_url'] ) ? (string) $mcp['authorize_url'] : ''
			],
			'counts'            => $counts,
			'capabilities'      => $capabilities,
			'abilities'         => [
				'api_available' => ! empty( $abilities['api_available'] ),
				'owner'         => $owner,
				'foreign'       => ! empty( $abilities['foreign'] ),
				'total'         => isset( $abilities['total'] ) ? (int) $abilities['total'] : 0,
				'betterdocs'    => isset( $abilities['betterdocs'] ) ? (int) $abilities['betterdocs'] : 0,
				'replayed'      => ! empty( $abilities['replayed'] )
			],
			'tools'             => $tools,
			'deferred_v2'       => self::DEFERRED_V2,
			'notes'             => $this->notes( $pro, $multiple_kb, $counts, $capabilities, $tools, $mcp, $glossaries )
		];
	}

	/**
	 * The health report this ability is a view onto.
	 *
	 * Constructed rather than resolved from the container: `MCPHealth` holds no
	 * state and takes no collaborators, and an ability that can be built by
	 * `new` in a test is worth more than a shared instance.
	 *
	 * @since 4.9.0
	 *
	 * @return MCPHealth
	 */
	protected function health() {
		return new MCPHealth();
	}

	/**
	 * How much content there is to work with.
	 *
	 * `wp_count_posts()` and `get_terms( fields => count )` are used rather than
	 * the `betterdocs/v1/docs-faq-count` route, which answers the same question
	 * with six `WP_Query`s at `posts_per_page => -1` (ADR-043): this ability is
	 * the one an agent is told to call first, so it must stay cheap on a site
	 * with fifty thousand docs. Two consequences, both deliberate:
	 *
	 * - `faqs` counts every `betterdocs_faq` post, where that route subtracts
	 *   Product FAQs. On a site not using the WooCommerce tab the numbers are
	 *   identical.
	 * - `created` follows the same privilege rule as that route (an agent
	 *   without the "others'" capability sees published counts only), widened
	 *   with `edit_others_docs` the way the FAQ Builder routes are — a
	 *   docs-only role holds no core post capabilities at all.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function counts() {
		$counts = [
			'docs' => $this->post_counts( 'docs' ),
			'faqs' => $this->post_counts( 'betterdocs_faq' )
		];

		foreach ( self::COUNTED_TAXONOMIES as $key => $taxonomy ) {
			$counts[ $key ] = $this->term_count( $taxonomy );
		}

		return $counts;
	}

	/**
	 * `{created, published}` for one post type.
	 *
	 * @since 4.9.0
	 *
	 * @param string $post_type Post type name.
	 * @return array
	 */
	protected function post_counts( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return [
				'created'   => 0,
				'published' => 0
			];
		}

		$counts    = (array) wp_count_posts( $post_type );
		$published = isset( $counts['publish'] ) ? (int) $counts['publish'] : 0;

		if ( ! $this->sees_all_statuses() ) {
			return [
				'created'   => $published,
				'published' => $published
			];
		}

		// The same set `WP_Query`'s `post_status => 'any'` counts: everything
		// except the statuses flagged `exclude_from_search` (trash, auto-draft).
		$created = 0;

		foreach ( get_post_stati( [ 'exclude_from_search' => false ] ) as $status ) {
			$created += isset( $counts[ $status ] ) ? (int) $counts[ $status ] : 0;
		}

		return [
			'created'   => $created,
			'published' => $published
		];
	}

	/**
	 * Number of terms in a taxonomy, or null when it is not registered.
	 *
	 * @since 4.9.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return int|null
	 */
	protected function term_count( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}

		$count = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'count'
			]
		);

		return is_wp_error( $count ) ? null : (int) $count;
	}

	/**
	 * Whether this user may be told how much unpublished content exists.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	protected function sees_all_statuses() {
		return current_user_can( 'edit_others_docs' ) || current_user_can( 'edit_others_posts' );
	}

	/**
	 * Which capabilities this user holds, and what decides that.
	 *
	 * @since 4.9.0
	 *
	 * @param array $health     The health report.
	 * @param bool  $pro_active Whether Pro is active.
	 * @return array
	 */
	protected function capabilities( array $health, $pro_active ) {
		$caps = isset( $health['capabilities'] ) && is_array( $health['capabilities'] ) ? $health['capabilities'] : [];
		$user = isset( $health['user'] ) && is_array( $health['user'] ) ? $health['user'] : [];

		return [
			'required'    => isset( $caps['required'] ) ? array_values( (array) $caps['required'] ) : [],
			'held'        => isset( $caps['held'] ) ? array_values( (array) $caps['held'] ) : [],
			'missing'     => isset( $caps['missing'] ) ? array_values( (array) $caps['missing'] ) : [],
			'governed_by' => $pro_active ? $this->role_settings() : null,
			'user_id'     => isset( $caps['user_id'] ) ? (int) $caps['user_id'] : 0,
			'user_roles'  => isset( $user['roles'] ) ? array_values( array_map( 'strval', (array) $user['roles'] ) ) : []
		];
	}

	/**
	 * Pro's four role settings, the thing that actually grants the capabilities
	 * on a Pro site (ADR-033).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function role_settings() {
		$settings = [];

		foreach ( self::ROLE_SETTINGS as $key ) {
			$value = betterdocs()->settings->get( $key );

			$settings[ $key ] = is_array( $value ) ? array_values( array_map( 'strval', $value ) ) : [];
		}

		return $settings;
	}

	/**
	 * The tool catalog, from the same source `tools/list` uses.
	 *
	 * Same source on purpose: if a read-only credential is in play the catalog
	 * is the short one, and an agent reading this ability's answer sees exactly
	 * what it is allowed to call.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function tools() {
		$names = [];

		foreach ( MCPTools::list() as $tool ) {
			if ( isset( $tool['name'] ) ) {
				$names[] = (string) $tool['name'];
			}
		}

		return [
			'count' => count( $names ),
			'names' => $names
		];
	}

	/**
	 * The distinct Pro role settings that govern a list of capabilities, in the
	 * order {@see self::ROLE_SETTINGS} declares them.
	 *
	 * @since 4.9.0
	 *
	 * @param string[] $capabilities Capability names.
	 * @return string[]
	 */
	protected function settings_governing( array $capabilities ) {
		$settings = [];

		foreach ( $capabilities as $capability ) {
			$settings[] = isset( self::CAPABILITY_ROLE_SETTING[ $capability ] )
				? self::CAPABILITY_ROLE_SETTING[ $capability ]
				: 'article_roles';
		}

		return array_values( array_intersect( self::ROLE_SETTINGS, $settings ) );
	}

	/**
	 * Plain-language hints an agent can act on.
	 *
	 * Every note names the tool call or the person that fixes what it describes;
	 * a note that only states a fact belongs in the structured fields above.
	 *
	 * @since 4.9.0
	 *
	 * @param array $pro          Pro state.
	 * @param bool  $multiple_kb  Whether Multiple Knowledge Base is on.
	 * @param array $counts       Content counts.
	 * @param array $capabilities Capability report.
	 * @param array $tools        Tool catalog.
	 * @param array $mcp          The health report's `mcp` section.
	 * @param bool  $glossaries   Whether the glossaries taxonomy is registered.
	 * @return string[]
	 */
	protected function notes( array $pro, $multiple_kb, array $counts, array $capabilities, array $tools, array $mcp, $glossaries = true ) {
		$notes = [];

		if ( ! $glossaries ) {
			$notes[] = __( 'Glossaries are off: the glossaries taxonomy is not registered, so the term tools and the doc tools refuse a glossary until bd-update-settings {enable_glossaries:true}. It takes effect from the next request.', 'betterdocs' );
		}

		if ( empty( $pro['active'] ) ) {
			$notes[] = empty( $pro['installed'] )
				? __( 'BetterDocs Pro is not installed: the knowledge base and site-wide analytics tools are listed but will refuse.', 'betterdocs' )
				: __( 'BetterDocs Pro is installed but not active: the knowledge base and site-wide analytics tools are listed but will refuse until an administrator activates it.', 'betterdocs' );
		} elseif ( ! $multiple_kb ) {
			$notes[] = __( 'Multiple Knowledge Base is off: knowledge base tools will refuse until bd-update-settings {multiple_kb:true}. It takes effect from the next request.', 'betterdocs' );
		}

		if ( ! empty( $capabilities['missing'] ) ) {
			$notes[] = sprintf(
				/* translators: %s: comma-separated list of capability names. */
				__( 'You do not hold: %s. Tools gated on those will refuse with capability_missing.', 'betterdocs' ),
				implode( ', ', $capabilities['missing'] )
			);

			if ( null === $capabilities['governed_by'] ) {
				$notes[] = __( "An administrator can grant them to this user's role with do_action( 'betterdocs_grant_caps', 'the-role' ).", 'betterdocs' );
			} else {
				$notes[] = sprintf(
					/* translators: 1: comma-separated setting names, 2: comma-separated role names. */
					__( "On a Pro site those come from %1\$s: an administrator can add this user's role (%2\$s) to them with bd-update-settings — see capabilities.governed_by for what each holds today.", 'betterdocs' ),
					implode( ', ', $this->settings_governing( $capabilities['missing'] ) ),
					'' !== implode( ', ', $capabilities['user_roles'] ) ? implode( ', ', $capabilities['user_roles'] ) : __( 'none', 'betterdocs' )
				);
			}
		}

		if ( empty( $mcp['enabled'] ) ) {
			$notes[] = __( 'The MCP server is switched off (BetterDocs → MCP): the abilities are registered but the MCP endpoint refuses every request.', 'betterdocs' );
		}

		if ( MCPTools::is_read_only() ) {
			$notes[] = __( 'This credential is read-only: write tools are not listed and calling one is refused with read_only_credential.', 'betterdocs' );
		}

		if ( null === $counts['knowledge_bases'] ) {
			$notes[] = __( 'The knowledge_base taxonomy is not registered on this site, so knowledge base counts and tools are unavailable.', 'betterdocs' );
		}

		if ( empty( $tools['names'] ) ) {
			$notes[] = __( 'No BetterDocs tools are registered: the Abilities API may be owned by another plugin. See the abilities section.', 'betterdocs' );
		}

		return $notes;
	}
}
