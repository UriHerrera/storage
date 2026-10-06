<?php
/**
 * Placeholder ability for a tool that lives in BetterDocs Pro.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * A Pro tool as Free can describe it: real name, real label, real schema, real
 * capability — and an execution that refuses with the reason.
 *
 * Registering these matters more than it looks. An agent that asks a Free site
 * for its tools sees the whole BetterDocs surface, learns that knowledge bases
 * exist, and is told in the tool's own description that Pro is what is missing.
 * Without a stub the tool is simply absent and the agent concludes BetterDocs
 * cannot do it at all. `tools/list` is therefore identical in shape on every
 * site, and Pro replaces these instances by id — the name an agent
 * learned never changes.
 *
 * The refusal is the same object the description promised:
 * `pro_required` when Pro is missing or inactive, `setting_disabled` (with a
 * literal `bd-update-settings` call the agent can make) when only the Multiple
 * Knowledge Base setting is in the way, and `upstream_error` in the one case
 * that means a broken install — Pro active, feature available, yet this stub
 * still answering because Pro's registrar never replaced it.
 *
 * @since 4.9.0
 */
final class StubAbility extends AbilityBase {

	/**
	 * Whether the tool needs the Multiple Knowledge Base feature, not just Pro.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $kb_feature = true;

	/**
	 * Feature name used in the refusal sentence ("Knowledge bases need …").
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	private $feature = '';

	/**
	 * Input schema from the spec.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	private $input_schema = [];

	/**
	 * Output schema from the spec.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	private $output_schema = [];

	/**
	 * Annotations from the spec.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	private $annotations = [];

	/**
	 * @since 4.9.0
	 *
	 * @param array $spec One entry from {@see ProStubs::specs()}: `id`, `label`,
	 *                    `description`, `feature`, `capability`, `kb_feature`,
	 *                    `input_schema`, `output_schema`, `annotations`.
	 */
	public function __construct( array $spec ) {
		$this->id           = isset( $spec['id'] ) ? (string) $spec['id'] : '';
		$this->label        = isset( $spec['label'] ) ? (string) $spec['label'] : '';
		$this->description  = isset( $spec['description'] ) ? (string) $spec['description'] : '';
		$this->capability   = isset( $spec['capability'] ) ? (string) $spec['capability'] : '';
		$this->requires_pro = true;
		$this->feature      = isset( $spec['feature'] ) ? (string) $spec['feature'] : $this->label;
		$this->kb_feature   = isset( $spec['kb_feature'] ) ? (bool) $spec['kb_feature'] : true;

		$this->input_schema  = isset( $spec['input_schema'] ) && is_array( $spec['input_schema'] )
			? $spec['input_schema']
			: [
				'type'       => 'object',
				'properties' => [],
				'default'    => []
			];
		$this->output_schema = isset( $spec['output_schema'] ) && is_array( $spec['output_schema'] )
			? $spec['output_schema']
			: [ 'type' => 'object' ];
		$this->annotations   = isset( $spec['annotations'] ) && is_array( $spec['annotations'] )
			? $spec['annotations']
			: parent::get_annotations();
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return $this->input_schema;
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return $this->output_schema;
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return $this->annotations;
	}

	/**
	 * Whether the tool needs Multiple Knowledge Base, not just Pro.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function needs_kb_feature() {
		return $this->kb_feature;
	}

	/**
	 * The static description plus what is true on this site.
	 *
	 * @since 4.9.0
	 *
	 * @param array $pro_state Result of {@see ProState::get()}.
	 * @return string
	 */
	public function describe( array $pro_state ) {
		return ProState::describe( $this->description, $pro_state );
	}

	/**
	 * Refuse, with the reason this site gives.
	 *
	 * @since 4.9.0
	 *
	 * @param array $input Validated input. Unused — a stub never does the work.
	 * @return \WP_Error Always.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the signature is the contract; a stub refuses whatever it is given.
	public function execute( $input ) {
		$state = ProState::get( $this->kb_feature );

		if ( 'pro_active_setting_off' === $state['state'] ) {
			return AbilityError::setting_disabled(
				'multiple_kb',
				[ 'multiple_kb' => true ],
				__( 'Multiple Knowledge Base', 'betterdocs' ),
				$state['state']
			);
		}

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, $this->feature );
		}

		// Pro is active and the feature is available, yet Free's placeholder is
		// still the thing answering: Pro's registrar did not replace it. That is
		// a version mismatch, not a licensing or settings problem, so it must
		// not be dressed up as one.
		return AbilityError::upstream(
			__( 'BetterDocs Pro is active but did not register this ability; update BetterDocs Pro.', 'betterdocs' ),
			[
				'ability' => $this->id,
				'state'   => $state['state']
			]
		);
	}
}
