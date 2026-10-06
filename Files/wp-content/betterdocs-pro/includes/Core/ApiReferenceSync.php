<?php

namespace WPDeveloper\BetterDocsPro\Core;

use WP_REST_Request;
use WP_REST_Server;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecIngestor;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecStore;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecFetcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Documentation — URL sync (Pro).
 *
 * A single hourly master event walks every URL-sourced reference and re-fetches
 * the ones whose interval is due (native wp_schedule_event — the API-docs branch
 * intentionally does not bundle Action Scheduler; see api-docs-specs ADR-004).
 * A manual "sync now" REST route triggers a single reference immediately. Both
 * paths run the fetched bytes through the same Free SpecIngestor the upload
 * flow uses.
 */
class ApiReferenceSync {
	const CRON_HOOK = 'betterdocs_api_ref_sync';

	/**
	 * Interval meta value → seconds. `manual` never auto-syncs.
	 *
	 * @var array<string,int>
	 */
	const INTERVALS = [
		'hourly' => HOUR_IN_SECONDS,
		'daily'  => DAY_IN_SECONDS,
		'weekly' => WEEK_IN_SECONDS
	];

	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_scheduled' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		register_deactivation_hook( BETTERDOCS_PRO_FILE, [ $this, 'unschedule' ] );
	}

	/**
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * @return void
	 */
	public function unschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * The cron worker: sync every URL reference whose interval has elapsed.
	 *
	 * @return void
	 */
	public function run_scheduled() {
		$references = get_posts(
			[
				'post_type'      => 'betterdocs_api_ref',
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_bd_api_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'url' // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		$now = time();

		foreach ( $references as $id ) {
			$interval = get_post_meta( $id, '_bd_api_sync_interval', true );

			if ( ! isset( self::INTERVALS[ $interval ] ) ) {
				continue; // manual / unset.
			}

			$status    = get_post_meta( $id, '_bd_api_sync_status', true );
			$last_sync = is_array( $status ) && ! empty( $status['time'] ) ? (int) $status['time'] : 0;

			if ( $now - $last_sync < self::INTERVALS[ $interval ] ) {
				continue; // not due yet.
			}

			$this->sync_reference( $id );
		}
	}

	/**
	 * Fetch + ingest one reference's URL spec. Records a sync-status meta the
	 * admin surfaces.
	 *
	 * @param int $id
	 * @return array|\WP_Error
	 */
	public function sync_reference( $id ) {
		$source_url = get_post_meta( $id, '_bd_api_source_url', true );

		if ( ! $source_url ) {
			return new \WP_Error( 'betterdocs_api_sync_no_url', __( 'This reference has no source URL.', 'betterdocs-pro' ) );
		}

		$store  = betterdocs()->container->get( SpecStore::class );
		$active = $store->get_active( $id );
		$etag   = $active ? $active->etag : '';
		$lastm  = $active ? $active->last_modified : '';

		$max_bytes = betterdocs()->container->get( ApiReferences::class )->max_spec_bytes();

		$fetched = ( new SpecFetcher() )->fetch( $source_url, $etag, $lastm, $max_bytes );

		if ( is_wp_error( $fetched ) ) {
			return $this->record_status( $id, 'error', $fetched->get_error_message() );
		}

		if ( ! empty( $fetched['not_modified'] ) ) {
			return $this->record_status( $id, 'unchanged', __( 'Spec unchanged since the last sync.', 'betterdocs-pro' ) );
		}

		$result = betterdocs()->container->get( SpecIngestor::class )->ingest(
			$id,
			$fetched['body'],
			$fetched['format'],
			[
				'source_url'    => $source_url,
				'etag'          => $fetched['etag'],
				'last_modified' => $fetched['last_modified']
			]
		);

		if ( is_wp_error( $result ) ) {
			return $this->record_status( $id, 'error', $result->get_error_message() );
		}

		return $this->record_status(
			$id,
			$result['result'], // 'stored' | 'unchanged'
			'stored' === $result['result']
				? __( 'Spec updated from URL.', 'betterdocs-pro' )
				: __( 'Spec unchanged.', 'betterdocs-pro' ),
			$result['summary']
		);
	}

	/**
	 * @param int    $id
	 * @param string $state 'stored'|'unchanged'|'error'
	 * @param string $message
	 * @param array  $summary
	 * @return array
	 */
	protected function record_status( $id, $state, $message, $summary = [] ) {
		$status = [
			'state'   => $state,
			'message' => $message,
			'time'    => time()
		];

		update_post_meta( $id, '_bd_api_sync_status', $status );

		return array_merge( $status, [ 'summary' => $summary ] );
	}

	/**
	 * POST /betterdocs/v1/api-ref/{id}/sync — manual "sync now".
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'betterdocs/v1',
			'/api-ref/(?P<id>[\d]+)/sync',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_sync' ],
				'permission_callback' => function () {
					return current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) );
				},
				'args'                => [
					'id' => [ 'sanitize_callback' => 'absint' ]
				]
			]
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_sync( WP_REST_Request $request ) {
		$id   = absint( $request['id'] );
		$post = get_post( $id );

		if ( ! $post || 'betterdocs_api_ref' !== $post->post_type ) {
			return new \WP_Error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ), [ 'status' => 404 ] );
		}

		$result = $this->sync_reference( $id );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 400 ] );
		}

		return new \WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
	}
}
