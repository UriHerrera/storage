<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

use WP_Error;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecParser;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecStore;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecSummary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endpoint materializer: turns a reference's active OpenAPI spec into real
 * `docs` posts (one per operation + an Introduction), grouped in
 * doc_category terms, reconciled against previous runs.
 *
 * Guarantees (see api-docs-specs/v1.5/DECISIONS.md):
 * - Writer edits are never overwritten (hash/title comparison, ADR-014).
 * - Removed operations are trashed, never deleted (ADR-015). Deleting the
 *   REFERENCE is the one exception — that path deletes outright (ADR-015a).
 * - Re-running on an identical spec performs zero content writes.
 * - Generated content is deterministic (builder contract).
 */
class Materializer {
	const LOCK_TRANSIENT = 'bd_api_materialize_lock_';
	const LOCK_TTL       = 300;

	/**
	 * @var TermManager
	 */
	protected $terms;

	/**
	 * @var OperationContentBuilder
	 */
	protected $builder;

	public function __construct() {
		$this->terms   = new TermManager();
		$this->builder = new OperationContentBuilder();

		add_action( 'betterdocs_api_spec_ingested', [ $this, 'handle_ingest' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'handle_reference_delete' ], 9, 2 );
		// Draft/publish the generated docs alongside the reference itself.
		add_action( 'transition_post_status', [ $this, 'handle_reference_status' ], 10, 3 );
	}

	/**
	 * Auto re-materialize when a new spec version lands for an enabled reference.
	 *
	 * @param int    $reference_id
	 * @param string $result  'stored'|'unchanged'
	 * @param array  $summary
	 * @return void
	 */
	public function handle_ingest( $reference_id, $result, $summary ) {
		if ( 'stored' !== $result ) {
			return;
		}

		if ( ! betterdocs()->container->get( ApiReferences::class )->is_materialized( $reference_id ) ) {
			return;
		}

		$this->materialize( $reference_id );
	}

	/**
	 * Run (or queue) a full materialization for a reference.
	 *
	 * @param int   $reference_id
	 * @param array $args { mode?: 'auto'|'inline'|'background' }
	 * @return array|WP_Error Report (inline) or status stub (background).
	 */
	public function materialize( $reference_id, array $args = [] ) {
		$reference = get_post( $reference_id );

		if ( ! $reference || 'betterdocs_api_ref' !== $reference->post_type ) {
			return new WP_Error( 'betterdocs_api_materialize_ref', __( 'API reference not found.', 'betterdocs-pro' ) );
		}

		if ( ! $this->acquire_lock( $reference_id ) ) {
			return new WP_Error(
				'betterdocs_api_materialize_locked',
				__( 'A materialization run is already in progress for this reference.', 'betterdocs-pro' )
			);
		}

		$spec = $this->load_spec( $reference_id );

		if ( is_wp_error( $spec ) ) {
			$this->release_lock( $reference_id );
			$this->write_status( $reference_id, 'error', [ 'message' => $spec->get_error_message() ] );

			return $spec;
		}

		$operations = betterdocs()->container->get( SpecSummary::class )->operations( $spec['spec'] );
		$mode       = isset( $args['mode'] ) ? $args['mode'] : 'auto';
		$inline_max = (int) apply_filters( 'betterdocs_api_materialize_inline_max', 75 );

		if ( 'background' === $mode || ( 'auto' === $mode && count( $operations ) > $inline_max ) ) {
			$queued = $this->dispatch_background( $reference_id, $operations, $spec['hash'] );

			if ( $queued ) {
				return [
					'state'    => 'running',
					'progress' => [
						'done'  => 0,
						'total' => count( $operations ) + 1
					]
				];
			}
			// Fall through to inline when no background runner is available.
		}

		$report = $this->run_inline( $reference, $spec );

		$this->release_lock( $reference_id );

		return $report;
	}

	/**
	 * The full inline run.
	 *
	 * @param \WP_Post $reference
	 * @param array    $spec { spec, hash }
	 * @return array Report.
	 */
	protected function run_inline( \WP_Post $reference, array $spec ) {
		$operations = betterdocs()->container->get( SpecSummary::class )->operations( $spec['spec'] );

		$this->write_status(
			$reference->ID,
			'running',
			[],
			[
				'done'  => 0,
				'total' => count( $operations ) + 1
			]
		);

		$report = $this->empty_report( $spec['hash'] );

		// Multiple-KB sites: generated categories/docs need a knowledge base
		// to be visible in KB-scoped sidebars/archives.
		$this->terms->prepare_knowledge_base( $reference );

		$parent_term = $this->terms->ensure_reference_term( $reference );
		if ( is_wp_error( $parent_term ) ) {
			$this->write_status( $reference->ID, 'error', [ 'message' => $parent_term->get_error_message() ] );

			return $report;
		}

		$existing = $this->existing_docs_map( $reference->ID );

		// Introduction first.
		$intro_result = $this->apply_introduction( $reference, $spec, $parent_term, $existing );
		$this->merge_result( $report, $intro_result );

		// Tag terms are ordered by first use: each takes the taxonomy-wide max+1
		// as it is created (TermManager::ensure_category_order), matching how
		// Free numbers every other category. The old spec-tags[]-position
		// numbering produced a private 1..n range that collided with unrelated
		// root categories, and it also made the inline and background runners
		// disagree — background has always ordered by first use.
		$term_docs  = [ $parent_term => isset( $intro_result['post_id'] ) ? [ $intro_result['post_id'] ] : [] ];
		$tag_terms  = [];
		$seen_keys  = [];
		$seen_ids   = [];

		foreach ( $operations as $index => $op_ref ) {
			list( $path, $method ) = $op_ref;

			$operation = $spec['spec']['paths'][ $path ][ $method ];
			$op_key    = $this->operation_key( $operation, $method, $path, $seen_ids, $report );

			if ( isset( $seen_keys[ $op_key ] ) ) {
				$report['warnings'][] = [
					'code'    => 'duplicate_operation',
					'message' => sprintf( 'Duplicate operation %s %s skipped.', strtoupper( $method ), $path )
				];
				continue;
			}
			$seen_keys[ $op_key ] = true;

			// Resolve the target terms. An operation may carry several tags, and
			// every one of them is a category the reader could browse to — filing
			// the doc under `tags[0]` alone made it unreachable from the rest.
			// The first tag stays the primary: it drives ordering and the
			// per-term doc lists below.
			$tags = isset( $operation['tags'] ) && is_array( $operation['tags'] )
				? array_values( array_unique( array_filter( array_map( 'strval', $operation['tags'] ), 'strlen' ) ) )
				: [];

			$term_ids = [];

			foreach ( $tags as $tag ) {
				if ( isset( $tag_terms[ $tag ] ) ) {
					$term_ids[] = $tag_terms[ $tag ];
					continue;
				}

				$resolved = $this->terms->ensure_tag_term(
					$reference->ID,
					$parent_term,
					$this->tag_definition( $spec['spec'], $tag )
				);

				if ( is_wp_error( $resolved ) ) {
					$report['warnings'][] = [
						'code'    => 'term_failed',
						'message' => $resolved->get_error_message()
					];
					continue;
				}

				$tag_terms[ $tag ] = $resolved;
				$term_ids[]        = $resolved;

				if ( ! in_array( $resolved, [ $parent_term ], true ) ) {
					$report['terms_created'][] = $resolved;
				}
			}

			$term_ids = array_values( array_unique( array_map( 'intval', $term_ids ) ) );

			if ( empty( $term_ids ) ) {
				$term_ids = [ (int) $parent_term ];
			}

			$term_id = $term_ids[0];

			$result = $this->apply_operation(
				$reference,
				[
					'method'    => $method,
					'path'      => $path,
					'operation' => $operation,
					'op_key'    => $op_key,
					'index'     => $index,
					'term_id'   => $term_id,
					'term_ids'  => $term_ids,
					'spec'      => $spec['spec'],
					'spec_hash' => $spec['hash']
				],
				$existing
			);

			$this->merge_result( $report, $result );

			if ( isset( $result['post_id'] ) ) {
				$term_docs[ $term_id ][] = $result['post_id'];
			}

			$this->bump_progress( $reference->ID, $index + 2, count( $operations ) + 1 );
		}

		$this->finalize( $reference, $report, $existing, $seen_keys, $term_docs, $parent_term );

		return $report;
	}

	/**
	 * Create/update/skip one operation doc. Also used by the background task.
	 *
	 * @param \WP_Post $reference
	 * @param array    $ctx      { method, path, operation, op_key, index, term_id, term_ids, spec, spec_hash }
	 * @param array    $existing op_key → post_id map (by-ref not required; trash state handled inside).
	 * @return array { action: created|updated|skipped_edited|unchanged|restored, post_id }
	 */
	public function apply_operation( \WP_Post $reference, array $ctx, array $existing ) {
		$generated = $this->builder->build_endpoint( $ctx['method'], $ctx['path'], $ctx['operation'], $ctx['spec'], $reference );

		$post_id  = isset( $existing[ $ctx['op_key'] ] ) ? (int) $existing[ $ctx['op_key'] ] : 0;
		$post     = $post_id ? get_post( $post_id ) : null;
		$restored = false;

		if ( $post && 'trash' === $post->post_status ) {
			wp_untrash_post( $post->ID );
			wp_update_post(
				[
					'ID'          => $post->ID,
					// Back to whatever the reference is now, not blindly public.
					'post_status' => $this->doc_status_for( $reference )
				]
			);
			$post     = get_post( $post->ID );
			$restored = true;
		}

		if ( ! $post ) {
			$post_id = wp_insert_post(
				[
					'post_type'    => 'docs',
					'post_status'  => $this->doc_status_for( $reference ),
					'post_title'   => $generated['title'],
					'post_name'    => sanitize_title( $generated['title'] ),
					'post_content' => wp_slash( $generated['content'] ),
					'post_excerpt' => $generated['excerpt'],
					'post_author'  => (int) $reference->post_author,
					'menu_order'   => (int) $ctx['index'] + 1
				],
				true
			);

			if ( is_wp_error( $post_id ) ) {
				return [
					'action'  => 'error',
					'warning' => [
						'code'    => 'insert_failed',
						'message' => $post_id->get_error_message()
					]
				];
			}

			wp_set_object_terms( $post_id, $this->ctx_term_ids( $ctx ), TermManager::TAXONOMY );
			$this->terms->assign_doc_kb( $post_id );
			$this->write_doc_meta( $post_id, $reference->ID, $ctx, $generated, 'endpoint' );

			return [
				'action'  => 'created',
				'post_id' => (int) $post_id
			];
		}

		// Existing doc — refresh light metadata regardless.
		wp_set_object_terms( $post->ID, $this->ctx_term_ids( $ctx ), TermManager::TAXONOMY );
		$this->terms->assign_doc_kb( $post->ID );
		update_post_meta( $post->ID, '_bd_api_method', $ctx['method'] );
		update_post_meta( $post->ID, '_bd_api_path', $ctx['path'] );

		// Bring a doc generated before the reference's current state back in
		// line. This is what repairs installs where docs were published under
		// the old always-publish behaviour: the next run corrects them without
		// anyone having to touch each doc. Applies to edited docs too — ADR-014
		// protects a writer's CONTENT, not whether the reference is public.
		$this->sync_doc_status( $post, $reference );

		if ( (int) $post->menu_order !== (int) $ctx['index'] + 1 ) {
			wp_update_post(
				[
					'ID'         => $post->ID,
					'menu_order' => (int) $ctx['index'] + 1
				]
			);
		}

		if ( $this->is_edited( $post ) ) {
			// Writer owns this doc — content/title untouched. Only count it as
			// "skipped" when the spec actually wanted to change it.
			$would_change = hash( 'sha256', $generated['content'] ) !== (string) get_post_meta( $post->ID, '_bd_api_source_hash', true );
			update_post_meta( $post->ID, '_bd_api_spec_hash', $ctx['spec_hash'] );

			return [
				'action'  => $would_change ? 'skipped_edited' : 'unchanged',
				'post_id' => (int) $post->ID
			];
		}

		$source_hash = (string) get_post_meta( $post->ID, '_bd_api_source_hash', true );

		if ( hash( 'sha256', $generated['content'] ) === $source_hash && $post->post_title === $generated['title'] ) {
			update_post_meta( $post->ID, '_bd_api_spec_hash', $ctx['spec_hash'] );

			return [
				'action'  => $restored ? 'restored' : 'unchanged',
				'post_id' => (int) $post->ID
			];
		}

		wp_update_post(
			[
				'ID'           => $post->ID,
				'post_title'   => $generated['title'],
				'post_content' => wp_slash( $generated['content'] ),
				'post_excerpt' => $generated['excerpt']
			]
		);
		$this->write_doc_meta( $post->ID, $reference->ID, $ctx, $generated, 'endpoint' );

		return [
			'action'  => $restored ? 'restored' : 'updated',
			'post_id' => (int) $post->ID
		];
	}

	/**
	 * Create/update the Introduction doc (same edit-preservation cycle).
	 *
	 * @param \WP_Post $reference
	 * @param array    $spec { spec, hash }
	 * @param int      $parent_term
	 * @param array    $existing op_key map — introduction uses key '__introduction'.
	 * @return array
	 */
	protected function apply_introduction( \WP_Post $reference, array $spec, $parent_term, array $existing ) {
		$generated = $this->builder->build_introduction( $spec['spec'], $reference );

		$ctx = [
			'method'    => '',
			'path'      => '',
			'operation' => [],
			'op_key'    => '__introduction',
			'index'     => -1,
			'term_id'   => $parent_term,
			'spec'      => $spec['spec'],
			'spec_hash' => $spec['hash']
		];

		$post_id = isset( $existing['__introduction'] ) ? (int) $existing['__introduction'] : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( $post && 'trash' === $post->post_status ) {
			wp_untrash_post( $post->ID );
			wp_update_post(
				[
					'ID'          => $post->ID,
					// Back to whatever the reference is now, not blindly public.
					'post_status' => $this->doc_status_for( $reference )
				]
			);
			$post = get_post( $post->ID );
		}

		if ( ! $post ) {
			$post_id = wp_insert_post(
				[
					'post_type'    => 'docs',
					'post_status'  => $this->doc_status_for( $reference ),
					'post_title'   => $generated['title'],
					'post_name'    => sanitize_title( $reference->post_name . '-introduction' ),
					'post_content' => wp_slash( $generated['content'] ),
					'post_excerpt' => $generated['excerpt'],
					'post_author'  => (int) $reference->post_author,
					'menu_order'   => 0
				],
				true
			);

			if ( is_wp_error( $post_id ) ) {
				return [
					'action'  => 'error',
					'warning' => [
						'code'    => 'intro_failed',
						'message' => $post_id->get_error_message()
					]
				];
			}

			wp_set_object_terms( $post_id, [ (int) $parent_term ], TermManager::TAXONOMY );
			$this->terms->assign_doc_kb( $post_id );
			$this->write_doc_meta( $post_id, $reference->ID, $ctx, $generated, 'introduction' );
			update_post_meta( $reference->ID, '_bd_api_intro_doc_id', (int) $post_id );

			return [
				'action'  => 'created',
				'post_id' => (int) $post_id
			];
		}

		update_post_meta( $reference->ID, '_bd_api_intro_doc_id', (int) $post->ID );
		wp_set_object_terms( $post->ID, [ (int) $parent_term ], TermManager::TAXONOMY );
		$this->terms->assign_doc_kb( $post->ID );
		$this->sync_doc_status( $post, $reference );

		if ( $this->is_edited( $post ) ) {
			update_post_meta( $post->ID, '_bd_api_spec_hash', $spec['hash'] );

			return [
				'action'  => 'skipped_edited',
				'post_id' => (int) $post->ID
			];
		}

		$source_hash = (string) get_post_meta( $post->ID, '_bd_api_source_hash', true );

		if ( hash( 'sha256', $generated['content'] ) === $source_hash && $post->post_title === $generated['title'] ) {
			return [
				'action'  => 'unchanged',
				'post_id' => (int) $post->ID
			];
		}

		wp_update_post(
			[
				'ID'           => $post->ID,
				'post_title'   => $generated['title'],
				'post_content' => wp_slash( $generated['content'] ),
				'post_excerpt' => $generated['excerpt']
			]
		);
		$this->write_doc_meta( $post->ID, $reference->ID, $ctx, $generated, 'introduction' );

		return [
			'action'  => 'updated',
			'post_id' => (int) $post->ID
		];
	}

	/**
	 * Post-apply: trash removed docs, prune empty generated terms, write
	 * ordering metas, persist the report.
	 */
	protected function finalize( \WP_Post $reference, array &$report, array $existing, array $seen_keys, array $term_docs, $parent_term ) {
		// Trash docs whose operations no longer exist.
		foreach ( $existing as $op_key => $post_id ) {
			if ( '__introduction' === $op_key || isset( $seen_keys[ $op_key ] ) ) {
				continue;
			}

			$post = get_post( $post_id );

			if ( $post && 'trash' !== $post->post_status ) {
				wp_trash_post( $post_id );
				$report['trashed'][] = (int) $post_id;
			}
		}

		// Ordering: parent term (introduction + untagged ops), then tag terms.
		foreach ( $term_docs as $term_id => $post_ids ) {
			$this->terms->set_docs_order( (int) $term_id, $post_ids );
		}

		$report['terms_removed'] = $this->terms->prune_empty_generated_terms( $reference->ID, $parent_term );

		$report['totals'] = [
			'created'    => count( $report['created'] ),
			'updated'    => count( $report['updated'] ),
			'skipped'    => count( $report['skipped_edited'] ),
			'trashed'    => count( $report['trashed'] ),
			'restored'   => count( $report['restored'] ),
			'operations' => count( $seen_keys )
		];

		$this->write_status( $reference->ID, 'complete', $this->trim_report( $report ) );

		/**
		 * Fires after a materialization run completes.
		 *
		 * @param int   $reference_id
		 * @param array $report
		 */
		do_action( 'betterdocs_api_docs_materialized', $reference->ID, $report );
	}

	/**
	 * Trash all generated docs, prune terms, reset reference metas.
	 *
	 * @param int $reference_id
	 * @return array { trashed: int, terms_removed: int }
	 */
	public function unmaterialize( $reference_id ) {
		$trashed = 0;

		foreach ( $this->generated_doc_ids( $reference_id ) as $post_id ) {
			$post = get_post( $post_id );

			if ( $post && 'trash' !== $post->post_status ) {
				wp_trash_post( $post_id );
				$trashed++;
			}
		}

		$parent_term   = (int) get_post_meta( $reference_id, '_bd_api_parent_term_id', true );
		$terms_removed = $this->terms->prune_empty_generated_terms( $reference_id, 0, true );
		$this->terms->prune_generated_kb( $reference_id );

		update_post_meta( $reference_id, '_bd_api_materialize', '0' );
		delete_post_meta( $reference_id, '_bd_api_parent_term_id' );
		delete_post_meta( $reference_id, '_bd_api_intro_doc_id' );
		$this->write_status(
			$reference_id,
			'idle',
			[
				'message' => sprintf( 'Removed: %d docs trashed.', $trashed )
			]
		);

		return [
			'trashed'       => $trashed,
			'terms_removed' => count( $terms_removed ),
			'parent_term'   => $parent_term
		];
	}

	/**
	 * Reference deletion: unlink writer-edited docs (keep them), permanently
	 * delete the rest.
	 *
	 * Hooked on `before_delete_post`, so this only ever runs when the reference
	 * itself is being permanently deleted — trashing a reference leaves its docs
	 * untouched.
	 *
	 * The generated docs are DELETED, not trashed (ADR-015a). ADR-015's
	 * trash-is-the-review-queue rule still governs the sync path, where a doc
	 * disappearing from the spec is a reversible editorial event. Deleting the
	 * reference is not: the admin confirms "you won't be able to revert this",
	 * the reference row and its spec are hard-deleted, and leaving 260 orphaned
	 * docs in Trash contradicted that prompt while also leaving the reader's
	 * category tree populated until someone emptied Trash by hand.
	 *
	 * Docs already in Trash are deleted too — they are our generated output,
	 * and skipping them is what left debris behind.
	 *
	 * @param int           $post_id
	 * @param \WP_Post|null $post
	 * @return void
	 */
	public function handle_reference_delete( $post_id, $post = null ) {
		$post = $post ?: get_post( $post_id );

		if ( ! $post || 'betterdocs_api_ref' !== $post->post_type ) {
			return;
		}

		foreach ( $this->generated_doc_ids( $post_id ) as $doc_id ) {
			$doc = get_post( $doc_id );

			if ( ! $doc ) {
				continue;
			}

			if ( $this->is_edited( $doc ) ) {
				// Writer-owned: keep the doc, remove our linkage. The list must
				// cover EVERY key write_doc_meta() writes — `_bd_api_generated_hash`
				// was missing, so detached docs kept a stray marker forever and
				// is_edited() still read as true on any later pass.
				foreach ( [ '_bd_api_ref_id', '_bd_api_operation_key', '_bd_api_operation_id', '_bd_api_method', '_bd_api_path', '_bd_api_doc_type', '_bd_api_generated_hash', '_bd_api_source_hash', '_bd_api_generated_title', '_bd_api_spec_hash' ] as $key ) {
					delete_post_meta( $doc_id, $key );
				}
			} else {
				// force = true: skip Trash entirely. wp_delete_post() also clears
				// the doc's term relationships, so prune_empty_generated_terms()
				// below sees the terms as genuinely empty.
				wp_delete_post( $doc_id, true );
			}
		}

		$this->terms->prune_empty_generated_terms( $post_id, 0, true );
		$this->terms->prune_generated_kb( $post_id );

		// Sweep debris left by earlier deletes that never got this far. Doing it
		// here (rather than on every materialize run) keeps it off the hot path
		// while still guaranteeing a reference delete leaves no strays behind.
		$this->terms->prune_orphaned_terms();
	}

	/**
	 * @return TermManager
	 */
	public function terms() {
		return $this->terms;
	}

	/**
	 * Public wrappers for the background process (same code path as inline).
	 */
	public function apply_introduction_public( \WP_Post $reference, array $spec, $parent_term, array $existing ) {
		return $this->apply_introduction( $reference, $spec, $parent_term, $existing );
	}

	public function finalize_public( \WP_Post $reference, array &$report, array $existing, array $seen_keys, array $term_docs, $parent_term ) {
		$this->finalize( $reference, $report, $existing, $seen_keys, $term_docs, $parent_term );
	}

	/* ---------------------------------------------------------------- */
	/* Helpers                                                           */
	/* ---------------------------------------------------------------- */

	/**
	 * Whether a generated doc has been edited by a writer (ADR-014).
	 *
	 * @param \WP_Post $post
	 * @return bool
	 */
	public function is_edited( \WP_Post $post ) {
		$generated_hash  = (string) get_post_meta( $post->ID, '_bd_api_generated_hash', true );
		$generated_title = (string) get_post_meta( $post->ID, '_bd_api_generated_title', true );

		if ( '' === $generated_hash ) {
			return false; // Nothing recorded yet — treat as ours.
		}

		return hash( 'sha256', (string) $post->post_content ) !== $generated_hash
			|| ( '' !== $generated_title && $post->post_title !== $generated_title );
	}

	/**
	 * Write all generated-doc metas. The generated hash is computed from the
	 * content RE-READ from the DB after the write (kses/filters mutate it).
	 */
	protected function write_doc_meta( $post_id, $reference_id, array $ctx, array $generated, $doc_type ) {
		$stored = get_post( $post_id );

		update_post_meta( $post_id, '_bd_api_ref_id', (int) $reference_id );
		update_post_meta( $post_id, '_bd_api_operation_key', $ctx['op_key'] );
		update_post_meta( $post_id, '_bd_api_operation_id', isset( $ctx['operation']['operationId'] ) ? (string) $ctx['operation']['operationId'] : '' );
		update_post_meta( $post_id, '_bd_api_method', (string) $ctx['method'] );
		update_post_meta( $post_id, '_bd_api_path', (string) $ctx['path'] );
		update_post_meta( $post_id, '_bd_api_doc_type', $doc_type );
		update_post_meta( $post_id, '_bd_api_generated_hash', hash( 'sha256', (string) $stored->post_content ) );
		// Hash of what the builder generated pre-storage — change detection on
		// later runs compares freshly-generated content against THIS.
		update_post_meta( $post_id, '_bd_api_source_hash', hash( 'sha256', $generated['content'] ) );
		update_post_meta( $post_id, '_bd_api_generated_title', $stored->post_title );
		update_post_meta( $post_id, '_bd_api_spec_hash', (string) $ctx['spec_hash'] );
	}

	/**
	 * op_key → post_id map for all docs generated from a reference
	 * (any status, trash included).
	 *
	 * @param int $reference_id
	 * @return array<string,int>
	 */
	public function existing_docs_map( $reference_id ) {
		$map = [];

		foreach ( $this->generated_doc_ids( $reference_id ) as $post_id ) {
			$doc_type = get_post_meta( $post_id, '_bd_api_doc_type', true );

			if ( 'introduction' === $doc_type ) {
				$map['__introduction'] = (int) $post_id;
				continue;
			}

			$key = (string) get_post_meta( $post_id, '_bd_api_operation_key', true );

			if ( '' !== $key && ! isset( $map[ $key ] ) ) {
				$map[ $key ] = (int) $post_id;
			}
		}

		return $map;
	}

	/**
	 * The post status generated docs should carry for a given reference.
	 *
	 * Generated docs inherit the reference's published state. They used to be
	 * inserted as `publish` unconditionally, so saving a reference as a draft
	 * still put its entire documentation set live on public URLs — the admin
	 * showed "Draft" while anonymous visitors could read every endpoint.
	 *
	 * Only `publish` maps to `publish`; draft, pending, private and trash all
	 * map to `draft`, which is the safe direction — a reference that is not
	 * publicly readable must not have publicly readable docs.
	 *
	 * @param \WP_Post $reference
	 * @return string 'publish'|'draft'
	 */
	public function doc_status_for( \WP_Post $reference ) {
		/**
		 * Filter the status generated docs take from their reference.
		 *
		 * @param string   $status    'publish' or 'draft'.
		 * @param \WP_Post $reference The API reference.
		 */
		return apply_filters(
			'betterdocs_api_docs_status',
			'publish' === $reference->post_status ? 'publish' : 'draft',
			$reference
		);
	}

	/**
	 * Move one generated doc to the status its reference implies.
	 *
	 * Trashed docs are left alone — trash is the sync path's review queue
	 * (ADR-015) and quietly reviving one here would undo that.
	 *
	 * @param \WP_Post $post
	 * @param \WP_Post $reference
	 * @return bool Whether the status changed.
	 */
	protected function sync_doc_status( \WP_Post $post, \WP_Post $reference ) {
		if ( 'trash' === $post->post_status ) {
			return false;
		}

		$target = $this->doc_status_for( $reference );

		if ( $post->post_status === $target ) {
			return false;
		}

		wp_update_post(
			[
				'ID'          => $post->ID,
				'post_status' => $target
			]
		);

		return true;
	}

	/**
	 * Propagate a reference's status change to the docs it generated.
	 *
	 * Without this, publishing a draft reference left its docs invisible and
	 * un-publishing a live one left every endpoint publicly readable — the
	 * status shown in the admin and the status readers actually experience
	 * would drift apart the moment anyone used the Status control.
	 *
	 * Hooked on `transition_post_status`, so it covers the block editor, Quick
	 * Edit, the API Docs drawer and wp-cli alike.
	 *
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 * @return void
	 */
	public function handle_reference_status( $new_status, $old_status, $post ) {
		if ( ! $post instanceof \WP_Post || 'betterdocs_api_ref' !== $post->post_type ) {
			return;
		}

		if ( $new_status === $old_status ) {
			return;
		}

		// Deleting/trashing a reference is handled by handle_reference_delete();
		// trashing should not drag the docs along, since restoring the reference
		// is expected to bring everything back as it was.
		if ( 'trash' === $new_status || 'trash' === $old_status ) {
			return;
		}

		foreach ( $this->generated_doc_ids( $post->ID ) as $doc_id ) {
			$doc = get_post( $doc_id );

			if ( $doc instanceof \WP_Post ) {
				$this->sync_doc_status( $doc, $post );
			}
		}
	}

	/**
	 * @param int $reference_id
	 * @return int[]
	 */
	protected function generated_doc_ids( $reference_id ) {
		return get_posts(
			[
				'post_type'      => 'docs',
				// Bookkeeping lookup: must see docs regardless of who is asking.
				'betterdocs_bypass_restrictions' => true,
				// 'any' excludes trash — trashed docs MUST be found so a
				// returning operation untrashes instead of duplicating.
				'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_bd_api_ref_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => (string) $reference_id // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
	}

	/**
	 * Stable reconciliation key for an operation.
	 *
	 * @param array  $operation
	 * @param string $method
	 * @param string $path
	 * @param array  $seen_ids By-ref operationId registry (duplicate detection).
	 * @param array  $report   By-ref (duplicate warnings).
	 * @return string
	 */
	public function operation_key( array $operation, $method, $path, array &$seen_ids, array &$report ) {
		$fallback = 'hash:' . md5( strtolower( $method ) . ' ' . $path );

		if ( empty( $operation['operationId'] ) ) {
			return $fallback;
		}

		$op_id = (string) $operation['operationId'];

		if ( isset( $seen_ids[ $op_id ] ) ) {
			$report['warnings'][] = [
				'code'    => 'duplicate_operation_id',
				'message' => sprintf( 'Duplicate operationId "%s" — %s %s keyed by path instead.', $op_id, strtoupper( $method ), $path )
			];

			return $fallback;
		}

		$seen_ids[ $op_id ] = true;

		return $op_id;
	}

	/**
	 * @param int $reference_id
	 * @return array|WP_Error { spec: array, hash: string }
	 */
	public function load_spec( $reference_id ) {
		$active = betterdocs()->container->get( SpecStore::class )->get_active( $reference_id );

		if ( ! $active ) {
			return new WP_Error( 'betterdocs_api_materialize_no_spec', __( 'This reference has no spec yet.', 'betterdocs-pro' ) );
		}

		static $cache = [];

		if ( ! isset( $cache[ $active->hash ] ) ) {
			$parsed = betterdocs()->container->get( SpecParser::class )->parse( $active->raw, $active->format );

			if ( is_wp_error( $parsed ) ) {
				return $parsed;
			}

			$cache[ $active->hash ] = $parsed['spec'];
		}

		/**
		 * The spec endpoint docs are built from — applied outside the parse
		 * cache so consumers (the v2.0 AI overlay) always see live state.
		 *
		 * @param array $spec
		 * @param int   $reference_id
		 */
		return [
			'spec' => apply_filters( 'betterdocs_api_docs_spec', $cache[ $active->hash ], (int) $reference_id ),
			'hash' => (string) $active->hash
		];
	}

	protected function tag_definition( array $spec, $tag_name ) {
		if ( isset( $spec['tags'] ) && is_array( $spec['tags'] ) ) {
			foreach ( $spec['tags'] as $tag ) {
				if ( is_array( $tag ) && isset( $tag['name'] ) && (string) $tag['name'] === $tag_name ) {
					return $tag;
				}
			}
		}

		return [ 'name' => $tag_name ];
	}

	protected function empty_report( $spec_hash ) {
		return [
			'spec_hash'      => $spec_hash,
			'created'        => [],
			'updated'        => [],
			'skipped_edited' => [],
			'trashed'        => [],
			'restored'       => [],
			'terms_created'  => [],
			'terms_removed'  => [],
			'warnings'       => [],
			'totals'         => []
		];
	}

	protected function merge_result( array &$report, array $result ) {
		$buckets = [
			'created'        => 'created',
			'updated'        => 'updated',
			'skipped_edited' => 'skipped_edited',
			'restored'       => 'restored'
		];

		if ( isset( $result['warning'] ) ) {
			$report['warnings'][] = $result['warning'];
		}

		if ( isset( $result['action'], $buckets[ $result['action'] ], $result['post_id'] ) ) {
			$report[ $buckets[ $result['action'] ] ][] = (int) $result['post_id'];
		}
	}

	protected function trim_report( array $report ) {
		foreach ( [ 'created', 'updated', 'skipped_edited', 'trashed', 'restored', 'terms_created', 'terms_removed' ] as $key ) {
			if ( isset( $report[ $key ] ) && count( $report[ $key ] ) > 100 ) {
				$report[ $key ] = array_slice( $report[ $key ], 0, 100 );
			}
		}

		if ( isset( $report['warnings'] ) && count( $report['warnings'] ) > 50 ) {
			$report['warnings'] = array_slice( $report['warnings'], 0, 50 );
		}

		return $report;
	}

	/**
	 * @param int    $reference_id
	 * @param string $state idle|running|complete|error
	 * @param array  $report
	 * @param array  $progress
	 */
	public function write_status( $reference_id, $state, array $report = [], array $progress = [] ) {
		update_post_meta(
			$reference_id,
			'_bd_api_materialize_status',
			[
				'state'    => $state,
				'time'     => time(),
				'progress' => $progress,
				'report'   => $report
			]
		);
	}

	/**
	 * Every category an operation belongs to, primary first.
	 *
	 * @param array $ctx
	 * @return int[]
	 */
	protected function ctx_term_ids( array $ctx ) {
		$ids = isset( $ctx['term_ids'] ) && is_array( $ctx['term_ids'] ) ? $ctx['term_ids'] : [];

		if ( empty( $ids ) && isset( $ctx['term_id'] ) ) {
			$ids = [ $ctx['term_id'] ];
		}

		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	protected function bump_progress( $reference_id, $done, $total ) {
		$status = get_post_meta( $reference_id, '_bd_api_materialize_status', true );

		if ( is_array( $status ) && 'running' === ( isset( $status['state'] ) ? $status['state'] : '' ) ) {
			$status['progress'] = [
				'done'  => (int) $done,
				'total' => (int) $total
			];
			// Move the clock on every doc: `time` is what recover_if_stalled()
			// measures against, so it has to mean "last progress", not "queued".
			$status['time'] = time();
			update_post_meta( $reference_id, '_bd_api_materialize_status', $status );
		}
	}

	public function acquire_lock( $reference_id ) {
		if ( get_transient( self::LOCK_TRANSIENT . $reference_id ) ) {
			return false;
		}

		set_transient( self::LOCK_TRANSIENT . $reference_id, time(), self::LOCK_TTL );

		return true;
	}

	public function release_lock( $reference_id ) {
		delete_transient( self::LOCK_TRANSIENT . $reference_id );
	}

	/**
	 * Queue a background run. Returns false when unavailable (fallback inline).
	 * Implemented in Unit 8 (MaterializeProcess).
	 *
	 * @param int   $reference_id
	 * @param array $operations
	 * @param string $spec_hash
	 * @return bool
	 */
	/**
	 * Seconds without progress before a `running` background run is treated as
	 * stalled and drained in-request.
	 */
	const STALL_SECONDS = 45;

	/**
	 * Rescue a background run that nothing is advancing.
	 *
	 * dispatch_background() can only report that the queue was *saved*; whether
	 * anything picks it up depends on the host. A blocked loopback, DISABLE_WP_CRON
	 * with no external ticker, or a persistent object cache dropping the queue
	 * option all leave the run parked at `running` with zero docs, forever.
	 *
	 * So progress is verified rather than assumed: any read of the reference
	 * (the admin list polls while a run is going) calls this, and if the run has
	 * not advanced for STALL_SECONDS it drains the queue synchronously within a
	 * small budget. The poll itself then becomes the thing driving the run.
	 *
	 * @param int  $reference_id
	 * @param int  $budget_seconds Wall-clock budget for this pass.
	 * @param bool $force          Skip the staleness check (manual resume).
	 * @return bool Whether a recovery pass ran.
	 */
	public function recover_if_stalled( $reference_id, $budget_seconds = 8, $force = false ) {
		$status = get_post_meta( $reference_id, '_bd_api_materialize_status', true );

		if ( ! is_array( $status ) || 'running' !== ( isset( $status['state'] ) ? $status['state'] : '' ) ) {
			return false;
		}

		$last = isset( $status['time'] ) ? (int) $status['time'] : 0;

		if ( ! $force && $last && ( time() - $last ) < self::STALL_SECONDS ) {
			return false; // Still moving — leave it alone.
		}

		if ( ! class_exists( MaterializeProcess::class ) ) {
			$this->write_status(
				$reference_id,
				'error',
				[ 'message' => __( 'The background runner is unavailable and the run could not be completed.', 'betterdocs-pro' ) ]
			);

			return false;
		}

		$process = betterdocs_pro()->container->get( MaterializeProcess::class );

		if ( ! $process->has_queue() ) {
			// Nothing left to run, but the status was never closed out — the
			// worker died between its last item and finalize.
			$this->write_status(
				$reference_id,
				'error',
				[ 'message' => __( 'The background run stopped before it finished. Re-generate the docs to complete it.', 'betterdocs-pro' ) ],
				isset( $status['progress'] ) && is_array( $status['progress'] ) ? $status['progress'] : []
			);

			return false;
		}

		$process->drain( $budget_seconds );

		return true;
	}

	protected function dispatch_background( $reference_id, array $operations, $spec_hash ) {
		if ( ! class_exists( MaterializeProcess::class ) ) {
			return false;
		}

		return MaterializeProcess::dispatch_for( $this, $reference_id, $operations, $spec_hash );
	}
}
