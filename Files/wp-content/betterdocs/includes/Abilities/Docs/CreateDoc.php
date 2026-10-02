<?php
/**
 * Create a doc ability.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities\Docs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityBase;
use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\Traits\ResolvesTerms;
use WPDeveloper\BetterDocs\Abilities\Traits\ShapesDocs;
use WPDeveloper\BetterDocs\Utils\BlockBuilder;

/**
 * Write a new doc from markdown, HTML or block markup.
 *
 * Markdown is the default because it is what a language model produces well;
 * {@see BlockBuilder} turns it into real core blocks, so the doc opens in the
 * block editor as a normal post rather than one `core/html` lump.
 *
 * The write goes through `wp/v2/docs`, never `wp_insert_post()`: the REST
 * controller slashes the content for us, and `wp_insert_post()` would
 * `wp_unslash()` the block attributes into unparseable JSON — measured, not
 * theoretical.
 *
 * @since 4.9.0
 */
class CreateDoc extends AbilityBase {

	use ResolvesTerms;
	use ShapesDocs;

	/**
	 * Statuses an agent may write.
	 *
	 * `future` and `trash` are deliberately absent: scheduling needs a date
	 * this tool does not take, and a doc is trashed with `bd-delete-doc`.
	 *
	 * @since 4.9.0
	 */
	const STATUSES = [ 'draft', 'publish', 'private', 'pending' ];

	/**
	 * Accepted content formats.
	 *
	 * @since 4.9.0
	 */
	const FORMATS = [ 'markdown', 'html', 'blocks' ];

	/**
	 * @since 4.9.0
	 */
	public function __construct() {
		$this->id          = 'betterdocs/create-doc';
		$this->label       = __( 'Create doc', 'betterdocs' );
		$this->description = __( 'Create a BetterDocs doc. Content is markdown by default and is converted to WordPress blocks. Categories, tags and glossary terms may be given by id or by name — a name that does not exist yet is created. Knowledge bases must already exist. Glossary terms need the enable_glossaries setting on.', 'betterdocs' );
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
			'idempotent'    => false,
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
			'required'             => [ 'title' ],
			'properties'           => [
				'title'           => [
					'type'        => 'string',
					'description' => __( 'The doc title. Required.', 'betterdocs' )
				],
				'content'         => [
					'type'        => 'string',
					'description' => __( 'The body, in the format named by content_format.', 'betterdocs' )
				],
				'content_format'  => [
					'type'        => 'string',
					'enum'        => self::FORMATS,
					'default'     => 'markdown',
					'description' => __( 'How to read content: markdown (default, converted to blocks), html (converted to blocks) or blocks (already serialised block markup).', 'betterdocs' )
				],
				'status'          => [
					'type'        => 'string',
					'enum'        => self::STATUSES,
					'default'     => 'draft',
					'description' => __( 'Publication status. Publishing needs the publish_docs capability.', 'betterdocs' )
				],
				'excerpt'         => [
					'type'        => 'string',
					'description' => __( 'Optional summary. Plain text.', 'betterdocs' )
				],
				'categories'      => self::term_ref_schema( __( 'Doc categories, by id or name. A name that does not exist is created.', 'betterdocs' ) ),
				'tags'            => self::term_ref_schema( __( 'Doc tags, by id or name. A name that does not exist is created.', 'betterdocs' ) ),
				'knowledge_bases' => self::term_ref_schema( __( 'Knowledge bases, by id, slug or name. Never created here — use bd-create-knowledge-base.', 'betterdocs' ) ),
				'glossaries'      => self::term_ref_schema( __( 'Glossary terms, by id or name. A name that does not exist is created. Needs the enable_glossaries setting on.', 'betterdocs' ) ),
				'author'          => [
					'type'        => 'integer',
					'description' => __( 'User id to credit. Defaults to the calling user; setting someone else needs edit_others_docs.', 'betterdocs' )
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
			'properties' => self::doc_summary_schema()
		];
	}

	/**
	 * @since 4.9.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$params = $this->build_params( $input, true );

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$created = $this->dispatch( 'POST', '/docs', $params, 'wp/v2' );

		if ( is_wp_error( $created ) ) {
			return $this->map_rest_error( $created, __( 'create a doc', 'betterdocs' ) );
		}

		if ( ! is_array( $created ) || empty( $created['id'] ) ) {
			return AbilityError::upstream( __( 'The doc was not created and WordPress reported no reason.', 'betterdocs' ) );
		}

		return $this->doc_summary( $created );
	}

	/**
	 * Turn validated input into `wp/v2/docs` parameters.
	 *
	 * Shared with {@see UpdateDoc}, which passes `$creating = false` so an
	 * absent field means "leave it alone" instead of "use the default".
	 *
	 * @since 4.9.0
	 *
	 * @param array $input    Validated input.
	 * @param bool  $creating Whether this is a create.
	 * @return array|\WP_Error
	 */
	protected function build_params( array $input, $creating ) {
		$params = [];

		if ( isset( $input['title'] ) ) {
			$params['title'] = (string) $input['title'];
		}

		if ( isset( $input['excerpt'] ) ) {
			$params['excerpt'] = (string) $input['excerpt'];
		}

		if ( isset( $input['author'] ) ) {
			$params['author'] = (int) $input['author'];
		}

		if ( $creating || isset( $input['status'] ) ) {
			$params['status'] = isset( $input['status'] ) ? (string) $input['status'] : 'draft';
		}

		if ( isset( $input['content'] ) ) {
			$format = isset( $input['content_format'] ) ? (string) $input['content_format'] : 'markdown';

			$params['content'] = BlockBuilder::content_to_blocks( (string) $input['content'], $format );
		}

		$taxonomies = [
			'categories'      => [ 'doc_category', true ],
			'tags'            => [ 'doc_tag', true ],
			'knowledge_bases' => [ 'knowledge_base', false ],
			'glossaries'      => [ 'glossaries', true ]
		];

		foreach ( $taxonomies as $field => $spec ) {
			if ( ! isset( $input[ $field ] ) ) {
				continue;
			}

			$ids = $this->resolve_terms( (array) $input[ $field ], $spec[0], $spec[1] );

			if ( is_wp_error( $ids ) ) {
				return $ids;
			}

			$params[ $spec[0] ] = $ids;
		}

		return $params;
	}
}
