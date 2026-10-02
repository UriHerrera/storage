<?php
/**
 * Update a doc ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Docs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Utils\BlockBuilder;

/**
 * Change a doc that already exists.
 *
 * Two things are worth an agent knowing, and both are in the tool description
 * because getting either wrong silently destroys work:
 *
 * - **Term arrays replace, they do not merge.** `{categories: ["How-to"]}`
 *   leaves the doc in exactly that one category. Omit the field to leave the
 *   terms alone; send the full list to change them.
 * - **`content` replaces the whole body; `append_content` adds to it.** The
 *   append path reads the doc's *raw* content first, so existing blocks come
 *   back byte-identical and only new markup is added at the end.
 *
 * Extends {@see CreateDoc} for the shared parameter building, which is the
 * only way the two can be guaranteed to accept the same fields in the same way.
 *
 * @since 4.9.0
 */
class UpdateDoc extends CreateDoc {

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		parent::__construct();

		$this->id          = 'betterdocs/update-doc';
		$this->label       = __( 'Update doc', 'betterdocs' );
		$this->description = __( 'Update a BetterDocs doc by id. Only the fields you send change. content replaces the whole body; append_content adds to the end of it. Category, tag, knowledge base and glossary term lists REPLACE what the doc has — omit them to leave the terms alone.', 'betterdocs' );
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
		$schema = parent::get_input_schema();

		$schema['required'] = [ 'id' ];

		$schema['properties']['id'] = [
			'type'        => 'integer',
			'description' => __( 'The doc id to update. Required.', 'betterdocs' )
		];

		$schema['properties']['title']['description'] = __( 'A new title. Omit to leave it alone.', 'betterdocs' );

		$schema['properties']['content']['description'] = __( 'Replacement body, in the format named by content_format. Omit to leave the body alone.', 'betterdocs' );

		$schema['properties']['append_content'] = [
			'type'        => 'string',
			'description' => __( 'Content to add to the end of the doc, in the format named by content_format. The existing blocks are left byte-identical. Cannot be combined with content.', 'betterdocs' )
		];

		$schema['properties']['status']['description'] = __( 'A new publication status. Omit to leave it alone.', 'betterdocs' );

		unset( $schema['properties']['status']['default'] );

		$schema['properties']['categories']['description']      = __( 'REPLACES the doc categories, by id or name. A name that does not exist is created. Omit to leave them alone; send [] to clear them.', 'betterdocs' );
		$schema['properties']['tags']['description']            = __( 'REPLACES the doc tags, by id or name. A name that does not exist is created. Omit to leave them alone; send [] to clear them.', 'betterdocs' );
		$schema['properties']['knowledge_bases']['description'] = __( 'REPLACES the knowledge bases, by id, slug or name. Never created here. Omit to leave them alone; send [] to clear them.', 'betterdocs' );
		$schema['properties']['glossaries']['description']      = __( 'REPLACES the glossary terms, by id or name. A name that does not exist is created. Omit to leave them alone; send [] to clear them. Needs the enable_glossaries setting on.', 'betterdocs' );

		// Put `id` first: it is the one field a reader must notice.
		$schema['properties'] = array_merge(
			[ 'id' => $schema['properties']['id'] ],
			$schema['properties']
		);

		return $schema;
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$post = $this->require_doc( isset( $input['id'] ) ? $input['id'] : 0 );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// Asked before the REST controller does, so the refusal can name the
		// capability this particular doc needs rather than the generic one.
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return AbilityError::capability_missing(
				$this->editing_capability( $post ),
				sprintf(
					/* translators: %d: doc id. */
					__( 'edit doc #%d', 'betterdocs' ),
					(int) $post->ID
				)
			);
		}

		if ( isset( $input['content'], $input['append_content'] ) ) {
			return AbilityError::invalid_input(
				'append_content',
				__( 'Send either content (replaces the body) or append_content (adds to it), not both.', 'betterdocs' )
			);
		}

		$params = $this->build_params( $input, false );

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		if ( isset( $input['append_content'] ) ) {
			$appended = $this->append( $post->ID, (string) $input['append_content'], isset( $input['content_format'] ) ? (string) $input['content_format'] : 'markdown' );

			if ( is_wp_error( $appended ) ) {
				return $appended;
			}

			$params['content'] = $appended;
		}

		if ( [] === $params ) {
			return AbilityError::invalid_input(
				'input',
				__( 'Nothing to update: send at least one field besides id.', 'betterdocs' )
			);
		}

		$updated = $this->dispatch( 'POST', '/docs/' . (int) $post->ID, $params, 'wp/v2' );

		if ( is_wp_error( $updated ) ) {
			return $this->map_rest_error(
				$updated,
				sprintf(
					/* translators: %d: doc id. */
					__( 'edit doc #%d', 'betterdocs' ),
					(int) $post->ID
				),
				$post
			);
		}

		return $this->doc_summary( (array) $updated );
	}

	/**
	 * The doc's current raw content with new markup appended.
	 *
	 * Read through `context=edit` so what comes back is the stored block markup
	 * and not the rendered HTML — appending to rendered output would rewrite
	 * every existing block.
	 *
	 * @since 4.9.0
	 *
	 * @param int    $id      Doc id.
	 * @param string $content Content to append.
	 * @param string $format  Its format.
	 * @return string|\WP_Error
	 */
	protected function append( $id, $content, $format ) {
		$current = $this->dispatch( 'GET', '/docs/' . (int) $id, [ 'context' => 'edit' ], 'wp/v2' );

		if ( is_wp_error( $current ) ) {
			return $this->map_rest_error( $current, __( 'read the doc before appending to it', 'betterdocs' ) );
		}

		$raw = isset( $current['content']['raw'] ) ? (string) $current['content']['raw'] : '';

		return BlockBuilder::append_block( $raw, BlockBuilder::content_to_blocks( $content, $format ) );
	}
}
