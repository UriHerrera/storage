<?php
/**
 * Ability base class.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base implementation every BetterDocs ability extends.
 *
 * An ability declares what it is (id, label, description), who may call it
 * (one capability), what it takes and returns (JSON Schema), and what it does
 * (`execute()`). The Abilities API registers it; the MCP server reads the same
 * registry as its tool catalog, so one definition serves both.
 *
 * Most abilities reuse BetterDocs' own REST controllers through
 * {@see self::dispatch()} rather than reimplementing doc/term/FAQ logic. That
 * keeps validation, sanitisation and side effects in one place, and means REST
 * fixes reach MCP for free.
 *
 * @since 4.9.0
 */
abstract class AbilityBase {

	/**
	 * Default REST namespace {@see self::dispatch()} targets.
	 *
	 * @since 4.9.0
	 */
	protected const NS = 'betterdocs/v1';

	// The six constants below belong to `Traits\ShapesTerms` and
	// `Traits\ShapesFAQs` by subject, and live here because **PHP 7.4 traits
	// cannot carry constants** — that is PHP 8.2 syntax and this plugin's floor is
	// 7.4. Every `self::` use of them sits in a class that extends this one, so
	// each reference still resolves, through inheritance instead of the trait.

	/**
	 * The taxonomies the Terms abilities address. Knowledge bases are
	 * deliberately not among them: they are the Pro family's own four tools
	 * (ADR-003). `glossaries` is here rather than in a tool family of its own
	 * because it is an ordinary hierarchical taxonomy on `docs` carrying the
	 * same four capabilities as the other two (ADR-061); it exists only while
	 * `enable_glossaries` is on, which {@see Traits\ResolvesTerms::taxonomy_available()}
	 * answers for.
	 *
	 * @since 4.9.0
	 */
	protected const TERM_TAXONOMIES = [ 'doc_category', 'doc_tag', 'glossaries' ];

	/**
	 * The setting that registers the glossary taxonomy. A Free setting, so its
	 * absence is a `setting_disabled` refusal and never a Pro state (ADR-061).
	 *
	 * @since 4.9.0
	 */
	protected const GLOSSARY_SETTING = 'enable_glossaries';

	/**
	 * The glossary taxonomy's own description meta.
	 *
	 * BetterDocs drains the term's native `description` into this key and blanks
	 * the column (`BetterDocsPro\Core\Glossaries::update_glossary_term()`), and both the admin
	 * screen and the A–Z front end read this first, so for `glossaries` the
	 * tools' `description` field is this meta (ADR-061).
	 *
	 * @since 4.9.0
	 */
	protected const GLOSSARY_DESCRIPTION_META = 'glossary_term_description';

	/**
	 * The doc-category term meta recording knowledge-base membership, registered
	 * for REST.
	 *
	 * @since 4.9.0
	 */
	protected const KB_META_KEY = 'doc_category_knowledge_base';

	/**
	 * The FAQ group taxonomy the FAQ abilities address. The WooCommerce Product
	 * FAQ groups (`betterdocs_product_faq_category`) share the post type but are a
	 * separate taxonomy and a separate feature; they are not exposed here.
	 *
	 * @since 4.9.0
	 */
	protected const GROUP_TAXONOMY = 'betterdocs_faq_category';

	/**
	 * The FAQ post type.
	 *
	 * @since 4.9.0
	 */
	protected const FAQ_POST_TYPE = 'betterdocs_faq';

	/**
	 * REST namespace of the FAQ Builder routes. `betterdocs`, with no version
	 * segment — `FAQBuilder::$namespace` has been that since 1.0.
	 *
	 * @since 4.9.0
	 */
	protected const FAQ_NS = 'betterdocs';

	/**
	 * Post statuses an FAQ may be written with.
	 *
	 * @since 4.9.0
	 */
	protected const FAQ_STATUSES = [ 'publish', 'draft', 'pending', 'private' ];

	/**
	 * Unique ability identifier, e.g. `betterdocs/create-doc`.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Human-readable label. Becomes the MCP tool `title`.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Static description. {@see self::describe()} may vary it per Pro state.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Ability category slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $category = AbilitiesRegistrar::CATEGORY;

	/**
	 * Capability that gates this ability. **Subclasses must set one** — there is
	 * deliberately no default, so an ability cannot ship ungated by omission.
	 *
	 * There is deliberately no blanket `manage_options` gate.
	 * Each ability declares the capability that already governs its feature
	 * (`edit_docs`, `manage_doc_terms`, `edit_docs_settings`, …) so an editor or
	 * author reaches exactly what the WordPress admin already lets them reach.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $capability = '';

	/**
	 * Whether the feature behind this ability needs BetterDocs Pro.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	protected $requires_pro = false;

	/**
	 * JSON Schema for the ability's input.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	abstract public function get_input_schema();

	/**
	 * JSON Schema for the ability's output.
	 *
	 * Note that the Abilities API validates the returned value against this, so
	 * a schema narrower than what {@see self::execute()} actually returns turns
	 * a successful call into a validation error.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	abstract public function get_output_schema();

	/**
	 * Do the work.
	 *
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error Plain data on success — never a `{success, data}` envelope.
	 */
	abstract public function execute( $input );

	/**
	 * Developer kill switch for the whole ability surface.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function abilities_enabled() {
		/**
		 * Filters whether BetterDocs registers any abilities at all.
		 *
		 * @since 4.9.0
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'betterdocs_abilities_api_enabled', true );
	}

	/**
	 * Whether this particular ability may be registered and executed.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		/**
		 * Filters whether one ability is enabled.
		 *
		 * @since 4.9.0
		 *
		 * @param bool         $enabled Whether the ability surface is on.
		 * @param string       $id      Ability id.
		 * @param AbilityBase $ability The ability instance.
		 */
		return (bool) apply_filters( 'betterdocs_ability_enabled', self::abilities_enabled(), $this->id, $this );
	}

	/**
	 * Permission callback handed to the Abilities API.
	 *
	 * Returns a plain bool on purpose. The Abilities API discards a `WP_Error`
	 * from this callback and answers with its own generic
	 * `ability_invalid_permissions` — and calls `_doing_it_wrong()` on the way,
	 * so returning a typed error here would only add log noise. The typed
	 * `capability_missing` lives in {@see self::execute_wrapper()}, which is the
	 * path the MCP server takes.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function permission_callback() {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return current_user_can( $this->capability );
	}

	/**
	 * Safety net against an ability that declares no gate at all.
	 *
	 * This only asserts that *some* capability was named; the capability itself is
	 * per-ability (see {@see self::$capability}).
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function meets_capability_policy() {
		return is_string( $this->capability ) && '' !== $this->capability;
	}

	/**
	 * Description for the current Pro state.
	 *
	 * Defaults to the static description. Stubs and the Pro knowledge-base
	 * abilities override this to say what is actually true on *this* site — that
	 * Pro is missing, inactive, or that a setting is off — so the tool list an
	 * agent reads is never misleading.
	 *
	 * @since 4.9.0
	 *
	 * @param array $pro_state Result of `ProState::get()`.
	 * @return string
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the state is the whole point of the override; the default just ignores it.
	public function describe( array $pro_state ) {
		return $this->description;
	}

	/**
	 * Whether the feature behind this ability needs Pro.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function requires_pro() {
		return (bool) $this->requires_pro;
	}

	/**
	 * MCP-compatible annotations. Subclasses override.
	 *
	 * Key names are the Abilities API's; `MCPTools` maps them onto the
	 * MCP spelling (`readOnlyHint`, `destructiveHint`, `idempotentHint`) when it
	 * builds `tools/list`.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => false,
			'priority'      => 2.0,
			'openWorldHint' => false
		];
	}

	/**
	 * Run the ability with the surrounding contract: capability gate, action
	 * hooks, and a `\Throwable` net.
	 *
	 * Registered as the ability's `execute_callback`, so on the Abilities API
	 * path the capability check here is a second opinion the runtime has already
	 * formed. It matters on the MCP path, which calls this directly and needs
	 * the typed `capability_missing` rather than a bare false.
	 *
	 * `\Throwable`, not `\Exception`: a `TypeError` from a controller must come
	 * back as a typed `upstream_error` naming the real reason, never as a fatal
	 * that takes the JSON-RPC response with it.
	 *
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute_wrapper( $input ) {
		if ( ! $this->is_enabled() || ! current_user_can( $this->capability ) ) {
			return AbilityError::capability_missing( $this->capability, $this->permission_phrase() );
		}

		/**
		 * Fires before an ability executes.
		 *
		 * @since 4.9.0
		 *
		 * @param string $id    Ability id.
		 * @param array  $input Validated input.
		 */
		do_action( 'betterdocs_before_ability_execute', $this->id, $input );

		try {
			$output = $this->execute( $input );
		} catch ( \Throwable $e ) {
			$output = AbilityError::upstream( $e->getMessage(), [ 'ability' => $this->id ] );
		}

		/**
		 * Fires after an ability executes, whatever the outcome.
		 *
		 * @since 4.9.0
		 *
		 * @param string           $id     Ability id.
		 * @param array            $input  Validated input.
		 * @param array|\WP_Error  $output What the ability returned.
		 */
		do_action( 'betterdocs_after_ability_execute', $this->id, $input, $output );

		return $output;
	}

	/**
	 * Call one of BetterDocs' own REST routes in-process and return its data.
	 *
	 * `rest_do_request()` still runs the route's `permission_callback`, but skips
	 * the cookie-nonce check that only applies to real HTTP requests — correct
	 * here, because the MCP server has already set the current user to whoever
	 * granted the credential.
	 *
	 * Returns the **unwrapped** payload: an ability answers with plain data, not a
	 * `{success, data}` envelope.
	 *
	 * @since 4.9.0
	 *
	 * @param string $method         HTTP verb.
	 * @param string $route          Route beneath the namespace, e.g. `/docs`.
	 * @param array  $params         Query params for GET, body params otherwise.
	 * @param string $rest_namespace REST namespace. Defaults to `betterdocs/v1`; the FAQ
	 *                               routes live under the bare `betterdocs` namespace.
	 * @return mixed|\WP_Error Response data, or the error the route produced.
	 */
	protected function dispatch( $method, $route, array $params = [], $rest_namespace = self::NS ) {
		$method  = strtoupper( $method );
		$request = new \WP_REST_Request( $method, '/' . trim( $rest_namespace, '/' ) . $route );

		$request->set_header( 'Content-Type', 'application/json' );

		if ( 'GET' === $method ) {
			$request->set_query_params( $params );
		} else {
			$request->set_body( wp_json_encode( $params ) );
			$request->set_body_params( $params );
		}

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return $response->get_data();
	}

	/**
	 * The same call as {@see self::dispatch()}, but handing back the whole
	 * response instead of its data.
	 *
	 * Listing tools need it: `X-WP-Total` and `X-WP-TotalPages` live on the
	 * response, and a page that reports the size of the page it returned rather
	 * than the number of matches makes a client stop paging early.
	 *
	 * @since 4.9.0
	 *
	 * @param string $method         HTTP verb.
	 * @param string $route          Route beneath the namespace, e.g. `/docs`.
	 * @param array  $params         Query params for GET, body params otherwise.
	 * @param string $rest_namespace REST namespace.
	 * @return \WP_REST_Response
	 */
	protected function dispatch_response( $method, $route, array $params = [], $rest_namespace = self::NS ) {
		$method  = strtoupper( $method );
		$request = new \WP_REST_Request( $method, '/' . trim( $rest_namespace, '/' ) . $route );

		$request->set_header( 'Content-Type', 'application/json' );

		if ( 'GET' === $method ) {
			$request->set_query_params( $params );
		} else {
			$request->set_body( wp_json_encode( $params ) );
			$request->set_body_params( $params );
		}

		return rest_do_request( $request );
	}

	/**
	 * Whether a controller refused a page because it is past the last one.
	 *
	 * `WP_REST_Posts_Controller::get_items()` answers `rest_post_invalid_page_number`
	 * the moment `page` exceeds the available pages and there is at least one
	 * match; the terms controller does not (it pages by offset and returns an
	 * empty page), so only the post-backed list tools ever meet this. It is not
	 * a "not found": the collection exists, the page is merely empty. The terms
	 * code is recognised too, for any controller that later adopts the same rule.
	 *
	 * @since 4.9.0
	 *
	 * @param \WP_Error $error The controller error.
	 * @return bool
	 */
	protected function is_page_out_of_range( \WP_Error $error ) {
		return in_array(
			$error->get_error_code(),
			[ 'rest_post_invalid_page_number', 'rest_term_invalid_page_number' ],
			true
		);
	}

	/**
	 * An empty page carrying the listing's real totals.
	 *
	 * The out-of-range error carries no counts, so the same query is re-run at
	 * page 1 to read `X-WP-Total` / `X-WP-TotalPages` from the headers. The
	 * result keeps paging honest: no items, the real `total` and `total_pages`,
	 * and the `page` / `per_page` the caller asked for. A page past the end is a
	 * normal empty answer, not an error (ADR-059, finding C).
	 *
	 * @since 4.9.0
	 *
	 * @param string $route          Route passed to dispatch_response (e.g. '/docs').
	 * @param array  $params         Query params of the failed request.
	 * @param int    $page           The requested (out-of-range) page.
	 * @param int    $per_page       Requested per_page.
	 * @param string $rest_namespace REST namespace.
	 * @return array
	 */
	protected function empty_page( $route, array $params, $page, $per_page, $rest_namespace = self::NS ) {
		$params['page'] = 1;
		$probe          = $this->dispatch_response( 'GET', $route, $params, $rest_namespace );
		$headers        = $probe->is_error() ? [] : $probe->get_headers();

		return [
			'items'       => [],
			'total'       => isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : 0,
			'total_pages' => isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1,
			'page'        => (int) $page,
			'per_page'    => (int) $per_page
		];
	}

	/**
	 * Register the ability with the WordPress Abilities API.
	 *
	 * The `function_exists()` guard is inside the callback, never at hook
	 * registration time: which copy of the API owns the global functions is
	 * decided by load order, so a copy that lands late must still find us hooked
	 * (see `AbilitiesRegistrar`).
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			$this->id,
			[
				'label'               => $this->label,
				'description'         => $this->description,
				'category'            => $this->category,
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'permission_callback' => [ $this, 'permission_callback' ],
				'execute_callback'    => [ $this, 'execute_wrapper' ],
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => $this->get_annotations(),
					'mcp'          => [
						'public' => false
					],
					'requires_pro' => $this->requires_pro()
				]
			]
		);
	}

	/**
	 * Ability id.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Human-readable label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Static description.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Capability this ability is gated on.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return $this->capability;
	}

	/**
	 * The phrase `capability_missing` uses to say what was being attempted.
	 * Defaults to the label, lowercased; subclasses may override for a better
	 * sentence.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return '' !== $this->label ? lcfirst( $this->label ) : $this->id;
	}
}
