<?php
/**
 * Base class for the abilities BetterDocs Pro contributes.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocs\Abilities\ProStubs;

/**
 * A Pro ability takes its identity from the Free plugin's own advertisement of
 * it.
 *
 * Free registers a placeholder for every Pro tool built from
 * {@see \WPDeveloper\BetterDocs\Abilities\ProStubs::specs()} — real id, real
 * label, real capability, real input schema — so an agent on a Free-only site
 * still learns the tool exists and what it takes. Pro then replaces those
 * placeholders by id. For that swap to be invisible, the two definitions have to
 * agree exactly, and the only way to guarantee that across two separately
 * released plugins is for Pro to read the same spec Free published rather than
 * keep a second copy that can drift a schema at a time.
 *
 * So this class adopts the spec: id, label, description, capability, feature
 * name, whether the tool needs Multiple Knowledge Base, and all three schemas.
 * A subclass supplies only {@see AbilityBase::execute()} — the behaviour, which
 * is the one thing Free genuinely does not have.
 *
 * @since 4.3.0
 */
abstract class ProAbility extends AbilityBase {

	/**
	 * Feature name used in a `pro_required` refusal ("Knowledge bases need …").
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	protected $feature = '';

	/**
	 * Whether this tool needs the Multiple Knowledge Base setting, not just Pro.
	 *
	 * @since 4.3.0
	 *
	 * @var bool
	 */
	protected $kb_feature = true;

	/**
	 * Input schema, adopted from the Free spec.
	 *
	 * @since 4.3.0
	 *
	 * @var array
	 */
	protected $input_schema = [];

	/**
	 * Output schema, adopted from the Free spec.
	 *
	 * @since 4.3.0
	 *
	 * @var array
	 */
	protected $output_schema = [];

	/**
	 * Annotations, adopted from the Free spec.
	 *
	 * @since 4.3.0
	 *
	 * @var array
	 */
	protected $annotations = [];

	/**
	 * The ability id this class implements, which must be one Free advertises.
	 *
	 * Declared as a method rather than taken as a constructor argument so a
	 * subclass of a subclass — `UpdateKnowledgeBase` extends
	 * `CreateKnowledgeBase`, the way Free's `UpdateTerm` extends
	 * `CreateTerm` — changes its identity by overriding one line and inherits
	 * everything else.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	abstract protected function ability_id();

	/**
	 * @since 4.3.0
	 */
	public function __construct() {
		$id   = $this->ability_id();
		$spec = self::spec( $id );

		$this->id           = (string) $id;
		$this->requires_pro = true;
		$this->label        = isset( $spec['label'] ) ? (string) $spec['label'] : '';
		$this->description  = isset( $spec['description'] ) ? (string) $spec['description'] : '';
		$this->capability   = isset( $spec['capability'] ) ? (string) $spec['capability'] : '';
		$this->feature      = isset( $spec['feature'] ) ? (string) $spec['feature'] : $this->label;
		$this->kb_feature   = isset( $spec['kb_feature'] ) ? (bool) $spec['kb_feature'] : true;

		$this->input_schema = isset( $spec['input_schema'] ) && is_array( $spec['input_schema'] )
			? $spec['input_schema']
			: [
				'type'       => 'object',
				'properties' => [],
				'default'    => []
			];

		$this->output_schema = isset( $spec['output_schema'] ) && is_array( $spec['output_schema'] )
			? $spec['output_schema']
			: [ 'type' => 'object' ];

		$this->annotations = isset( $spec['annotations'] ) && is_array( $spec['annotations'] )
			? $spec['annotations']
			: parent::get_annotations();
	}

	/**
	 * The Free spec for one ability id, or an empty array when Free does not
	 * advertise it.
	 *
	 * An empty spec leaves `$capability` empty, which makes
	 * `AbilityBase::meets_capability_policy()` false and the Free registrar drop
	 * the ability — the right outcome for a Pro build paired with a Free that has
	 * never heard of the tool, and a much quieter one than registering something
	 * ungated.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id Ability id.
	 * @return array
	 */
	protected static function spec( $id ) {
		if ( ! class_exists( ProStubs::class ) ) {
			return [];
		}

		foreach ( ProStubs::specs() as $spec ) {
			if ( isset( $spec['id'] ) && $id === $spec['id'] ) {
				return $spec;
			}
		}

		return [];
	}

	/**
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return $this->input_schema;
	}

	/**
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return $this->output_schema;
	}

	/**
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return $this->annotations;
	}

	/**
	 * The static description plus what is true on this site.
	 *
	 * The state is re-read for **this ability's** feature rather than used as
	 * passed. `MCPTools::list()` probes once with the knowledge-base feature in
	 * mind and hands the same array to all 28 tools, which would tell
	 * `bd-get-analytics` that Multiple Knowledge Base is in its way — a setting
	 * it does not use. `ProState::get()` memoises the probe, so asking again
	 * costs nothing and `ProState::probe_count()` stays at 1 for a whole
	 * listing.
	 *
	 * @since 4.3.0
	 *
	 * @param array $pro_state Result of `ProState::get()`, as the catalog probed it.
	 * @return string
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the signature is Free's; this override answers for its own feature instead.
	public function describe( array $pro_state ) {
		return ProState::describe( $this->description, $this->pro_state() );
	}

	/**
	 * This site's Pro state, resolved for this ability's feature.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	protected function pro_state() {
		return ProState::get( $this->kb_feature );
	}
}
