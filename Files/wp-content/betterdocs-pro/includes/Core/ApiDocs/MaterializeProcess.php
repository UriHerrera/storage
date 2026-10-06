<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

use WPDeveloper\BetterDocs\Admin\BackgroundProcess\WP_Background_Process;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecSummary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background materialization for large specs (> inline threshold).
 *
 * One queue item per operation, then a terminal `finalize` item. Reuses
 * Free's vendored WP_Background_Process (HelpScout precedent, ADR-016) —
 * batches run via async loopback/cron, ~one item at a time with the parsed
 * spec memoized per hash inside Materializer::load_spec().
 */
class MaterializeProcess extends WP_Background_Process {
	/**
	 * @var string
	 */
	protected $prefix = 'betterdocs_pro';

	/**
	 * @var string
	 */
	protected $action = 'bd_api_materialize';

	/**
	 * @var Materializer|null Lazily resolved in task().
	 */
	protected $materializer = null;

	/**
	 * Queue a full run. Called from Materializer::dispatch_background().
	 *
	 * @param Materializer $materializer
	 * @param int          $reference_id
	 * @param array        $operations   [ [path, method], … ] canonical order.
	 * @param string       $spec_hash
	 * @return bool
	 */
	public static function dispatch_for( Materializer $materializer, $reference_id, array $operations, $spec_hash ) {
		$process = betterdocs_pro()->container->get( self::class );

		// Counts the DOCS this run produces — one per operation plus the
		// Introduction — so the progress denominator matches what the reader
		// ends up with, and matches the inline runner's total for the same spec.
		//
		// It deliberately does NOT count the terminal `finalize` queue item.
		// That item is bookkeeping (ordering, pruning, the report); it creates no
		// doc, and handle() never calls record() for it — so counting it left
		// `done` peaking at total-1 and the bar frozen just short of full on
		// every single run.
		$total = count( $operations ) + 1;

		$materializer->write_status(
			$reference_id,
			'running',
			[],
			[
				'done'  => 0,
				'total' => $total
			]
		);

		$process->push_to_queue(
			[
				'phase' => 'intro',
				'ref'   => (int) $reference_id,
				'hash'  => (string) $spec_hash,
				'total' => $total
			]
		);

		foreach ( $operations as $index => $op_ref ) {
			$process->push_to_queue(
				[
					'phase'  => 'op',
					'ref'    => (int) $reference_id,
					'hash'   => (string) $spec_hash,
					'path'   => $op_ref[0],
					'method' => $op_ref[1],
					'index'  => (int) $index,
					'total'  => $total
				]
			);
		}

		$process->push_to_queue(
			[
				'phase' => 'finalize',
				'ref'   => (int) $reference_id,
				'hash'  => (string) $spec_hash,
				'total' => $total
			]
		);

		$process->save()->dispatch();

		// The immediate async loopback only authenticates when dispatched from
		// a user-0 context (its nonce is minted by the dispatching user, and
		// the cookie-less loopback verifies as user 0). From wp-admin/REST the
		// queue would otherwise idle until the healthcheck interval — schedule
		// an immediate cron tick (user 0) as a fast-start.
		if ( ! wp_next_scheduled( $process->cron_hook() ) || wp_next_scheduled( $process->cron_hook() ) > time() + MINUTE_IN_SECONDS ) {
			wp_schedule_single_event( time(), $process->cron_hook() );
		}

		return true;
	}

	/**
	 * @return string The healthcheck cron hook (protected upstream).
	 */
	public function cron_hook() {
		return $this->cron_hook_identifier;
	}

	/**
	 * Drain the queue synchronously, in the caller's request.
	 *
	 * The async loopback and WP-Cron are both best-effort: a host that blocks
	 * loopback requests, or an install running with DISABLE_WP_CRON and no
	 * external ticker, will queue a run that nothing ever picks up. This is the
	 * recovery path — it does the same work handle() would, bounded by a time
	 * budget so the calling request still returns.
	 *
	 * @param int $budget_seconds Wall-clock budget for this pass.
	 * @return int Items processed.
	 */
	public function drain( $budget_seconds = 10 ) {
		$started   = time();
		$processed = 0;

		while ( ! $this->is_queue_empty() ) {
			if ( ( time() - $started ) >= $budget_seconds || $this->memory_exceeded() ) {
				break;
			}

			$batch = $this->get_batch();

			if ( empty( $batch->data ) ) {
				$this->delete( $batch->key );
				continue;
			}

			foreach ( $batch->data as $key => $item ) {
				$task = $this->task( $item );

				if ( false !== $task ) {
					$batch->data[ $key ] = $task;
				} else {
					unset( $batch->data[ $key ] );
				}

				++$processed;

				if ( ( time() - $started ) >= $budget_seconds || $this->memory_exceeded() ) {
					break;
				}
			}

			if ( ! empty( $batch->data ) ) {
				$this->update( $batch->key, $batch->data );
			} else {
				$this->delete( $batch->key );
			}
		}

		if ( $this->is_queue_empty() ) {
			$this->complete();
		}

		return $processed;
	}

	/**
	 * Is there anything left to run? Public so the recovery check outside this
	 * class can tell "stalled mid-run" from "finished but the status meta was
	 * never closed out".
	 *
	 * @return bool
	 */
	public function has_queue() {
		return ! $this->is_queue_empty();
	}

	/**
	 * Process one queue item.
	 *
	 * @param array $item
	 * @return false Remove the item from the queue.
	 */
	protected function task( $item ) {
		if ( ! is_array( $item ) || empty( $item['ref'] ) ) {
			return false;
		}

		$materializer = $this->materializer();
		$reference    = get_post( (int) $item['ref'] );

		if ( ! $reference || 'betterdocs_api_ref' !== $reference->post_type ) {
			return false;
		}

		// Refresh the lock while the queue drains so REST calls still 409.
		set_transient( Materializer::LOCK_TRANSIENT . $reference->ID, time(), Materializer::LOCK_TTL );

		$spec = $materializer->load_spec( $reference->ID );

		if ( is_wp_error( $spec ) || $spec['hash'] !== $item['hash'] ) {
			// Spec replaced mid-run — drop the stale item; the new ingest
			// triggered its own run.
			return false;
		}

		$state = $this->run_state( $reference->ID );

		if ( 'intro' === $item['phase'] ) {
			$result = $this->apply_intro( $materializer, $reference, $spec );
			$this->record( $reference->ID, $state, $result, 1, $item['total'] );

			return false;
		}

		if ( 'op' === $item['phase'] ) {
			$operation = isset( $spec['spec']['paths'][ $item['path'] ][ $item['method'] ] )
				? $spec['spec']['paths'][ $item['path'] ][ $item['method'] ]
				: null;

			if ( ! is_array( $operation ) ) {
				return false;
			}

			$result = $this->apply_op( $materializer, $reference, $spec, $item, $state );
			$this->record( $reference->ID, $state, $result, (int) $item['index'] + 2, $item['total'] );

			return false;
		}

		if ( 'finalize' === $item['phase'] ) {
			$this->finalize( $materializer, $reference, $spec, $state );
			$materializer->release_lock( $reference->ID );
		}

		return false;
	}

	/* ---------------------------------------------------------------- */

	protected function materializer() {
		if ( null === $this->materializer ) {
			$this->materializer = betterdocs_pro()->container->get( Materializer::class );
		}

		return $this->materializer;
	}

	/**
	 * The accumulating run-state option (op keys/term docs survive between
	 * batches; too large for the status meta).
	 */
	protected function run_state( $reference_id ) {
		$state = get_option( 'bd_api_materialize_state_' . $reference_id );

		if ( ! is_array( $state ) ) {
			$state = [
				'report'      => [],
				'seen_keys'   => [],
				'seen_ids'    => [],
				'tag_terms'   => [],
				'term_docs'   => [],
				'parent_term' => 0
			];
		}

		return $state;
	}

	protected function save_state( $reference_id, array $state ) {
		update_option( 'bd_api_materialize_state_' . $reference_id, $state, false );
	}

	protected function apply_intro( Materializer $materializer, \WP_Post $reference, array $spec ) {
		$state = $this->run_state( $reference->ID );

		$terms = $materializer->terms();
		$terms->prepare_knowledge_base( $reference );
		$parent = $terms->ensure_reference_term( $reference );

		if ( is_wp_error( $parent ) ) {
			return [ 'action' => 'error' ];
		}

		$state['parent_term'] = (int) $parent;
		$state['report']      = $this->empty_report( $spec['hash'] );

		// Introduction via the materializer's own protected cycle: reuse
		// apply_operation-equivalent by calling materialize-intro through a
		// public wrapper.
		$result = $materializer->apply_introduction_public( $reference, $spec, $parent, $materializer->existing_docs_map( $reference->ID ) );

		if ( isset( $result['post_id'] ) ) {
			$state['term_docs'][ $parent ] = [ (int) $result['post_id'] ];
		}

		$this->merge( $state['report'], $result );
		$this->save_state( $reference->ID, $state );

		return $result;
	}

	protected function apply_op( Materializer $materializer, \WP_Post $reference, array $spec, array $item, array &$state ) {
		$operation = $spec['spec']['paths'][ $item['path'] ][ $item['method'] ];

		// Each queued task may run in a fresh request — re-derive the KB
		// context (cheap + idempotent) so docs/terms get their assignment.
		$materializer->terms()->prepare_knowledge_base( $reference );

		$op_key = $materializer->operation_key( $operation, $item['method'], $item['path'], $state['seen_ids'], $state['report'] );

		if ( isset( $state['seen_keys'][ $op_key ] ) ) {
			$this->save_state( $reference->ID, $state );

			return [ 'action' => 'duplicate' ];
		}
		$state['seen_keys'][ $op_key ] = true;

		$tag     = isset( $operation['tags'][0] ) ? (string) $operation['tags'][0] : '';
		$term_id = (int) $state['parent_term'];

		if ( '' !== $tag ) {
			if ( isset( $state['tag_terms'][ $tag ] ) ) {
				$term_id = (int) $state['tag_terms'][ $tag ];
			} else {
				$created = $materializer->terms()->ensure_tag_term( $reference->ID, $state['parent_term'], [ 'name' => $tag ] );

				if ( ! is_wp_error( $created ) ) {
					$term_id                     = (int) $created;
					$state['tag_terms'][ $tag ]  = $term_id;
					$state['report']['terms_created'][] = $term_id;
				}
			}
		}

		$result = $materializer->apply_operation(
			$reference,
			[
				'method'    => $item['method'],
				'path'      => $item['path'],
				'operation' => $operation,
				'op_key'    => $op_key,
				'index'     => (int) $item['index'],
				'term_id'   => $term_id,
				'spec'      => $spec['spec'],
				'spec_hash' => $spec['hash']
			],
			$materializer->existing_docs_map( $reference->ID )
		);

		if ( isset( $result['post_id'] ) ) {
			$state['term_docs'][ $term_id ][] = (int) $result['post_id'];
		}

		$this->merge( $state['report'], $result );
		$this->save_state( $reference->ID, $state );

		return $result;
	}

	protected function finalize( Materializer $materializer, \WP_Post $reference, array $spec, array $state ) {
		$materializer->finalize_public(
			$reference,
			$state['report'],
			$materializer->existing_docs_map( $reference->ID ),
			$state['seen_keys'],
			$state['term_docs'],
			(int) $state['parent_term']
		);

		delete_option( 'bd_api_materialize_state_' . $reference->ID );
	}

	protected function record( $reference_id, array $state, array $result, $done, $total ) {
		$status = get_post_meta( $reference_id, '_bd_api_materialize_status', true );

		if ( is_array( $status ) ) {
			$status['state']    = 'running';
			$status['time']     = time();
			$status['progress'] = [
				'done'  => (int) $done,
				'total' => (int) $total
			];
			update_post_meta( $reference_id, '_bd_api_materialize_status', $status );
		}
	}

	protected function merge( array &$report, array $result ) {
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

	/**
	 * Nothing extra on completion — finalize is an explicit queue item so a
	 * multi-batch run finalizes exactly once.
	 */
	protected function complete() {
		parent::complete();
	}
}
