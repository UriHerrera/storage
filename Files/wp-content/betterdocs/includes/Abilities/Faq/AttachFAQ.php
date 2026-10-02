<?php
/**
 * Attach FAQ groups to a doc ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesDocs;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesFAQs;
use WPDeveloper\BetterDocs\Utils\BlockBuilder;

/**
 * Put FAQ groups on a doc — by writing a block into its content, because that
 * is the only place the relationship exists.
 *
 * There is no doc → FAQ-group relation field in BetterDocs: a doc shows FAQs
 * because its content carries a `betterdocs/faq` block whose `includeFaqGroup`
 * attribute names the groups. So "attach" means "edit the content", and the two
 * things that follow from that are what this tool is really for:
 *
 * - **It is idempotent.** Running it twice with the same groups leaves one
 *   block, not two: `append` looks for a block that already filters on exactly
 *   this id set before adding anything.
 * - **It repairs as it goes.** A block written by any other REST client is
 *   likely to hold the bare-id form `"[5]"`, which renders correctly since
 *   4.9.0 but opens in Gutenberg with an empty group picker. Every FAQ block in
 *   the doc is rewritten to the `{value, label}` object form on the way past,
 *   and the answer says how many needed it.
 *
 * The write goes through `POST wp/v2/docs/<id>`, never `wp_update_post()`:
 * that function unslashes its input, which eats the backslashes in the block
 * attribute's JSON and leaves a block whose filter matches nothing — measured,
 * not theoretical.
 *
 * @since 4.9.0
 */
class AttachFAQ extends AbilityBase {

	use ShapesDocs;
	use ShapesFAQs;

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/attach-faq';
		$this->label       = __( 'Attach FAQ groups to a doc', 'betterdocs' );
		$this->description = __( 'Show FAQ groups on a doc. Attaching writes a betterdocs/faq block into the doc content (there is no relation field). Re-running with the same groups is a no-op. Use replace_faq_blocks to narrow to a different set. Bare-id FAQ blocks already in the doc are repaired to the form the block editor can read.', 'betterdocs' );
		$this->capability  = 'edit_docs';
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_annotations() {
		return [
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 2.0,
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
			'required'             => [ 'doc_id' ],
			'properties'           => [
				'doc_id'            => [
					'type'        => 'integer',
					'description' => __( 'The doc to attach the FAQ groups to. Required.', 'betterdocs' )
				],
				'group_ids'         => [
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'description' => __( 'FAQ groups to show, by id.', 'betterdocs' )
				],
				'group_names'       => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => __( 'FAQ groups to show, by name or slug. They must already exist — this tool never creates one; use bd-create-faq-group.', 'betterdocs' )
				],
				'mode'              => [
					'type'        => 'string',
					'enum'        => [ 'append', 'replace_faq_blocks' ],
					'default'     => 'append',
					'description' => __( 'append adds a block unless one already filters on exactly these groups. replace_faq_blocks removes every FAQ block in the doc first, so the doc ends up showing these groups and nothing else; with no groups at all it just removes them.', 'betterdocs' )
				],
				'layout'            => [
					'type'        => 'string',
					'enum'        => [ 'layout-1', 'layout-2', 'layout-3', 'layout-4' ],
					'description' => __( 'FAQ block layout: layout-1 Modern, layout-2 Classic, layout-3 Abstract, layout-4 Tab. Defaults to the block\'s own layout-1.', 'betterdocs' )
				],
				'exclude_group_ids' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'description' => __( 'FAQ groups to leave out of this block, by id — the block\'s excludeFaqGroup attribute.', 'betterdocs' )
				]
			],
			'default'              => []
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'doc_id'          => [ 'type' => 'integer' ],
				'url'             => [ 'type' => 'string' ],
				'mode'            => [ 'type' => 'string' ],
				'faq_groups'      => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'   => [ 'type' => 'integer' ],
							'name' => [ 'type' => 'string' ]
						]
					]
				],
				'changed'         => [ 'type' => 'boolean' ],
				'repaired_blocks' => [ 'type' => 'integer' ],
				'faq_blocks_now'  => [
					'type'  => 'array',
					'items' => [
						'type'  => 'array',
						'items' => [ 'type' => 'integer' ]
					]
				]
			]
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$doc_id = isset( $input['doc_id'] ) ? (int) $input['doc_id'] : 0;
		$mode   = isset( $input['mode'] ) ? (string) $input['mode'] : 'append';

		$doc = $this->require_doc( $doc_id );

		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		if ( ! current_user_can( 'edit_post', $doc_id ) ) {
			return AbilityError::capability_missing(
				$this->editing_capability( $doc ),
				sprintf(
					/* translators: %d: doc id. */
					__( 'attach FAQ groups to doc #%d', 'betterdocs' ),
					$doc_id
				)
			);
		}

		$groups = $this->resolve_groups( $input, $mode );

		if ( is_wp_error( $groups ) ) {
			return $groups;
		}

		$excluded = $this->resolve_excluded( $input );

		if ( is_wp_error( $excluded ) ) {
			return $excluded;
		}

		$item = $this->dispatch( 'GET', '/docs/' . $doc_id, [ 'context' => 'edit' ], 'wp/v2' );

		if ( is_wp_error( $item ) ) {
			return $this->map_rest_error( $item, __( 'read the doc\'s content', 'betterdocs' ), $doc );
		}

		$item = (array) $item;
		$raw  = isset( $item['content']['raw'] ) ? (string) $item['content']['raw'] : '';

		// Repair first, so an existing bare-id block is compared and counted in
		// the form the editor can read — and so the "nothing changed" answer
		// below is about this call's groups, not about a legacy encoding.
		$repaired = BlockBuilder::repair_faq_blocks( $raw, [ $this, 'group_label' ] );
		$repairs  = $this->count_repairs( $raw, $repaired );

		$content = $this->apply( $repaired, $groups, $excluded, $mode, $input );
		$changed = $content !== $raw;

		if ( $changed ) {
			$written = $this->dispatch( 'POST', '/docs/' . $doc_id, [ 'content' => $content ], 'wp/v2' );

			if ( is_wp_error( $written ) ) {
				return $this->map_rest_error( $written, __( 'write the doc\'s content', 'betterdocs' ), $doc );
			}

			// Read back rather than trusting what was sent: the controller has
			// its own filters, and `faq_blocks_now` is meant to describe the doc
			// as it now is.
			$after = $this->dispatch( 'GET', '/docs/' . $doc_id, [ 'context' => 'edit' ], 'wp/v2' );

			if ( ! is_wp_error( $after ) ) {
				$after   = (array) $after;
				$content = isset( $after['content']['raw'] ) ? (string) $after['content']['raw'] : $content;
				$item    = $after;
			}
		}

		return [
			'doc_id'          => $doc_id,
			'url'             => isset( $item['link'] ) ? (string) $item['link'] : '',
			'mode'            => $mode,
			'faq_groups'      => array_map(
				static function ( array $group ) {
					return [
						'id'   => (int) $group['id'],
						'name' => (string) $group['label']
					];
				},
				$groups
			),
			'changed'         => $changed,
			'repaired_blocks' => $repairs,
			'faq_blocks_now'  => $this->blocks_now( $content )
		];
	}

	/**
	 * The term name for a group id — the label the Gutenberg picker shows.
	 *
	 * Public because {@see BlockBuilder::repair_faq_blocks()} takes it as a
	 * callable.
	 *
	 * @since 4.9.0
	 *
	 * @param int $id Term id.
	 * @return string
	 */
	public function group_label( $id ) {
		$summary = $this->group_summary( (int) $id );

		return null === $summary ? '' : $summary['name'];
	}

	/**
	 * Turn `group_ids` and `group_names` into `[ [ 'id', 'label' ], … ]`.
	 *
	 * Names are find-only: attaching a group that does not exist is a mistake
	 * worth reporting, and a silently created empty group would render as an
	 * empty FAQ section on a published doc.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $input Validated input.
	 * @param string $mode  `append` or `replace_faq_blocks`.
	 * @return array|\WP_Error
	 */
	protected function resolve_groups( array $input, $mode ) {
		$refs = array_merge(
			isset( $input['group_ids'] ) ? (array) $input['group_ids'] : [],
			isset( $input['group_names'] ) ? (array) $input['group_names'] : []
		);

		if ( empty( $refs ) ) {
			if ( 'replace_faq_blocks' === $mode ) {
				// "Show these groups and nothing else", with an empty list, is
				// a legitimate instruction: remove every FAQ block.
				return [];
			}

			return AbilityError::invalid_input(
				'group_ids',
				__( 'Name at least one FAQ group, by id in group_ids or by name in group_names.', 'betterdocs' )
			);
		}

		$groups = [];

		foreach ( $refs as $ref ) {
			$id = $this->resolve_group( $ref, false );

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$groups[ (int) $id ] = [
				'id'    => (int) $id,
				'label' => $this->group_label( $id )
			];
		}

		return array_values( $groups );
	}

	/**
	 * The `exclude_group_ids` input, resolved the same way.
	 *
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	protected function resolve_excluded( array $input ) {
		if ( empty( $input['exclude_group_ids'] ) ) {
			return [];
		}

		$excluded = [];

		foreach ( (array) $input['exclude_group_ids'] as $ref ) {
			$id = $this->resolve_group( $ref, false );

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$excluded[ (int) $id ] = [
				'id'    => (int) $id,
				'label' => $this->group_label( $id )
			];
		}

		return array_values( $excluded );
	}

	/**
	 * The content this call wants the doc to have.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content  Repaired content.
	 * @param array  $groups   Groups to attach.
	 * @param array  $excluded Groups to exclude.
	 * @param string $mode     `append` or `replace_faq_blocks`.
	 * @param array  $input    Validated input.
	 * @return string
	 */
	protected function apply( $content, array $groups, array $excluded, $mode, array $input ) {
		if ( 'replace_faq_blocks' === $mode ) {
			return BlockBuilder::replace_faq_blocks( $content, $this->markup( $groups, $excluded, $input ) );
		}

		if ( $this->already_attached( $content, $groups ) ) {
			return $content;
		}

		return BlockBuilder::append_block( $content, $this->markup( $groups, $excluded, $input ) );
	}

	/**
	 * The block markup for this call, or `''` when there is nothing to write.
	 *
	 * @since 4.9.0
	 *
	 * @param array $groups   Groups to attach.
	 * @param array $excluded Groups to exclude.
	 * @param array $input    Validated input.
	 * @return string
	 */
	protected function markup( array $groups, array $excluded, array $input ) {
		if ( empty( $groups ) ) {
			return '';
		}

		$attrs = [];

		if ( isset( $input['layout'] ) && '' !== $input['layout'] ) {
			$attrs['faqLayout'] = (string) $input['layout'];
		}

		if ( ! empty( $excluded ) ) {
			$attrs['excludeFaqGroup'] = BlockBuilder::encode_groups( $excluded );
		}

		return BlockBuilder::faq_block( $groups, $attrs );
	}

	/**
	 * Whether the doc already carries a block filtering on exactly these
	 * groups.
	 *
	 * Order-insensitive, and about the **id set** only: a block on the same
	 * groups with a different layout is still that attachment, and adding a
	 * second copy of it is what an agent re-running its own instruction must
	 * not cause.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content Post content.
	 * @param array  $groups  Groups to attach.
	 * @return bool
	 */
	protected function already_attached( $content, array $groups ) {
		$wanted = $this->ids_of( $groups );

		foreach ( BlockBuilder::find_faq_blocks( $content ) as $found ) {
			if ( $this->ids_of( $found['include'] ) === $wanted ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A sorted, unique id list for comparison.
	 *
	 * @since 4.9.0
	 *
	 * @param array $groups `[ [ 'id' => int, … ], … ]`.
	 * @return int[]
	 */
	protected function ids_of( array $groups ) {
		$ids = [];

		foreach ( $groups as $group ) {
			if ( isset( $group['id'] ) ) {
				$ids[] = (int) $group['id'];
			}
		}

		$ids = array_values( array_unique( $ids ) );

		sort( $ids );

		return $ids;
	}

	/**
	 * How many FAQ blocks the repair pass rewrote.
	 *
	 * `BlockBuilder::repair_faq_blocks()` answers with content, not a count —
	 * correctly, since it must return the input byte-for-byte when nothing
	 * needed doing. The blocks are in the same order either way, so comparing
	 * the two group attributes block by block is the count.
	 *
	 * @since 4.9.0
	 *
	 * @param string $before Content as it was stored.
	 * @param string $after  Content after the repair pass.
	 * @return int
	 */
	protected function count_repairs( $before, $after ) {
		if ( $before === $after ) {
			return 0;
		}

		$was = BlockBuilder::find_faq_blocks( $before );
		$now = BlockBuilder::find_faq_blocks( $after );
		$out = 0;

		foreach ( $was as $index => $block ) {
			if ( ! isset( $now[ $index ] ) ) {
				continue;
			}

			foreach ( [ 'includeFaqGroup', 'excludeFaqGroup' ] as $attribute ) {
				$old = isset( $block['block']['attrs'][ $attribute ] ) ? $block['block']['attrs'][ $attribute ] : null;
				$new = isset( $now[ $index ]['block']['attrs'][ $attribute ] ) ? $now[ $index ]['block']['attrs'][ $attribute ] : null;

				if ( $old !== $new ) {
					++$out;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * The group ids each FAQ block in the content filters on, in document
	 * order.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content Post content.
	 * @return array[]
	 */
	protected function blocks_now( $content ) {
		$out = [];

		foreach ( BlockBuilder::find_faq_blocks( $content ) as $found ) {
			$out[] = $this->ids_of( $found['include'] );
		}

		return $out;
	}
}
