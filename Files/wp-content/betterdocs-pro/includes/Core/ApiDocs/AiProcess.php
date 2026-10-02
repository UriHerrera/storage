<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPDeveloper\BetterDocs\Admin\BackgroundProcess\WP_Background_Process;

/**
 * Background AI generation for large plans (> inline chunk threshold,
 * ADR-023). One queue item per chunk (op keys only — digests are re-planned
 * at task time against the hash-checked spec), then a terminal `finalize`
 * item. Same vendored WP_Background_Process + user-0 cron fast-start as the
 * v1.5 materialize queue.
 */
class AiProcess extends WP_Background_Process {
	/**
	 * @var string
	 */
	protected $prefix = 'betterdocs_pro';

	/**
	 * @var string
	 */
	protected $action = 'bd_api_ai';

	/**
	 * Queue a full run. Caller (AiOverlay::generate) holds the lock already.
	 *
	 * @param int    $reference_id
	 * @param string $spec_hash
	 * @param array  $chunks [ { task, digest: [ { key, … } ] } ]
	 * @return bool
	 */
	public static function dispatch_for( $reference_id, $spec_hash, array $chunks ) {
		$process = betterdocs_pro()->container->get( self::class );
		$overlay = betterdocs_pro()->container->get( AiOverlay::class );

		$total = count( $chunks ) + 1; // + finalize.

		$overlay->write_status(
			$reference_id,
			'running',
			[],
			[
				'done'  => 0,
				'total' => $total
			]
		);

		delete_option( 'bd_api_ai_state_' . $reference_id );

		foreach ( $chunks as $index => $chunk ) {
			$process->push_to_queue(
				[
					'phase' => 'chunk',
					'ref'   => (int) $reference_id,
					'hash'  => (string) $spec_hash,
					'task'  => (string) $chunk['task'],
					'keys'  => wp_list_pluck( $chunk['digest'], 'key' ),
					'index' => (int) $index,
					'total' => $total
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

		// Same fast-start as MaterializeProcess: the async loopback only
		// authenticates from user-0 contexts — kick an immediate cron tick.
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
	 * Process one queue item.
	 *
	 * @param array $item
	 * @return false Remove the item from the queue.
	 */
	protected function task( $item ) {
		if ( ! is_array( $item ) || empty( $item['ref'] ) ) {
			return false;
		}

		$reference_id = (int) $item['ref'];
		$overlay      = betterdocs_pro()->container->get( AiOverlay::class );

		// Keep REST callers 409ing while the queue drains.
		set_transient( AiOverlay::LOCK_TRANSIENT . $reference_id, time(), AiOverlay::LOCK_TTL );

		$spec = $overlay->load_raw_spec( $reference_id );

		if ( is_wp_error( $spec ) || $spec['hash'] !== $item['hash'] ) {
			// Spec replaced mid-run — drop the stale item.
			if ( 'finalize' === $item['phase'] ) {
				$overlay->release_lock( $reference_id );
				delete_option( 'bd_api_ai_state_' . $reference_id );
			}

			return false;
		}

		if ( 'finalize' === $item['phase'] ) {
			$report = $this->state( $reference_id );
			$state  = ( $report['generated'] > 0 || empty( $report['errors'] ) ) ? 'complete' : 'error';

			$report['mode'] = betterdocs_pro()->container->get( AiOverlay::class )->client()->mode();

			$overlay->write_status( $reference_id, $state, $report );
			$overlay->release_lock( $reference_id );
			delete_option( 'bd_api_ai_state_' . $reference_id );

			return false;
		}

		// chunk: re-plan this task and filter to the queued keys.
		$plan   = $overlay->plan( $spec['spec'], [ $item['task'] ] );
		$keys   = array_flip( (array) $item['keys'] );
		$digest = array_values( array_filter( $plan[ $item['task'] ], static function ( $op ) use ( $keys ) {
			return isset( $keys[ $op['key'] ] );
		} ) );

		$report = $this->state( $reference_id );

		if ( $digest ) {
			$reference = get_post( $reference_id );
			$fragment  = $overlay->run_chunk(
				$reference_id,
				$item['task'],
				$digest,
				[
					'api_title'       => $reference ? $reference->post_title : '',
					'api_description' => isset( $spec['spec']['info']['description'] ) ? (string) $spec['spec']['info']['description'] : ''
				]
			);

			$overlay->merge_report( $report, $fragment );
		}

		update_option( 'bd_api_ai_state_' . $reference_id, $report, false );

		$status = $overlay->get_status( $reference_id );

		if ( is_array( $status ) ) {
			$overlay->write_status(
				$reference_id,
				'running',
				$report,
				[
					'done'  => (int) $item['index'] + 1,
					'total' => (int) $item['total']
				]
			);
		}

		return false;
	}

	/**
	 * @param int $reference_id
	 * @return array
	 */
	protected function state( $reference_id ) {
		$state = get_option( 'bd_api_ai_state_' . $reference_id );

		return is_array( $state ) ? $state : [
			'generated' => 0,
			'failed'    => [],
			'errors'    => []
		];
	}
}
