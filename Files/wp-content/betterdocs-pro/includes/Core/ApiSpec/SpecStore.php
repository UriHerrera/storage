<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiSpec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence for raw specs in `{prefix}betterdocs_api_specs`.
 *
 * One active row per reference (ADR-006: specs live in the DB only — an
 * uploaded file is read, validated and discarded, so no uploads-path URL ever
 * exists). A couple of superseded rows are kept for the v1.5 sync differ,
 * older ones are pruned on write.
 */
class SpecStore {
	/**
	 * Superseded rows kept per reference (the active row excluded).
	 */
	const KEEP_INACTIVE = 2;

	/**
	 * @return string
	 */
	protected function table() {
		global $wpdb;

		return $wpdb->prefix . 'betterdocs_api_specs';
	}

	/**
	 * The active spec row for a reference.
	 *
	 * @param int $reference_id
	 * @return object|null
	 */
	public function get_active( $reference_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is our own prefix + literal.
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE reference_id = %d AND is_active = 1 ORDER BY id DESC LIMIT 1",
				$reference_id
			)
		);
	}

	/**
	 * The newest superseded spec row for a reference — what the active spec
	 * replaced. Fuel for the v2.0 changelog differ (rows are retained for
	 * exactly this: see KEEP_INACTIVE).
	 *
	 * @param int $reference_id
	 * @return object|null
	 */
	public function get_previous( $reference_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is our own prefix + literal.
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE reference_id = %d AND is_active = 0 ORDER BY id DESC LIMIT 1",
				$reference_id
			)
		);
	}

	/**
	 * Store a new raw spec as the active one.
	 *
	 * @param int    $reference_id
	 * @param string $raw
	 * @param string $format 'json'|'yaml'
	 * @param array  $args   { source_url?, etag?, last_modified? }
	 *
	 * @return string 'unchanged'|'stored'|'error'
	 */
	public function save( $reference_id, $raw, $format, $args = [] ) {
		global $wpdb;

		$hash   = hash( 'sha256', $raw );
		$active = $this->get_active( $reference_id );
		$now    = current_time( 'mysql', true );

		if ( $active && $active->hash === $hash ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->update(
				$this->table(),
				[ 'fetched_at' => $now ] + $this->sync_columns( $args ),
				[ 'id' => $active->id ]
			);

			return 'unchanged';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table()} SET is_active = 0 WHERE reference_id = %d AND is_active = 1",
				$reference_id
			)
		);

		$inserted = $wpdb->insert(
			$this->table(),
			[
				'reference_id' => $reference_id,
				'raw'          => $raw,
				'format'       => $format,
				'hash'         => $hash,
				'fetched_at'   => $now,
				'is_active'    => 1,
				'created_at'   => $now
			] + $this->sync_columns( $args )
		);

		if ( false === $inserted ) {
			return 'error';
		}

		$this->prune( $reference_id );

		return 'stored';
	}

	/**
	 * Remove every row for a reference (called when the reference is deleted).
	 *
	 * @param int $reference_id
	 * @return void
	 */
	public function delete_for( $reference_id ) {
		global $wpdb;

		$wpdb->delete( $this->table(), [ 'reference_id' => $reference_id ], [ '%d' ] );
	}

	/**
	 * @param array $args
	 * @return array
	 */
	protected function sync_columns( $args ) {
		$columns = [];

		foreach ( [ 'source_url', 'etag', 'last_modified' ] as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$columns[ $key ] = (string) $args[ $key ];
			}
		}

		return $columns;
	}

	/**
	 * Keep the active row + the newest KEEP_INACTIVE superseded rows.
	 *
	 * @param int $reference_id
	 * @return void
	 */
	protected function prune( $reference_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stale = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$this->table()} WHERE reference_id = %d AND is_active = 0 ORDER BY id DESC LIMIT 100 OFFSET %d",
				$reference_id,
				self::KEEP_INACTIVE
			)
		);

		if ( empty( $stale ) ) {
			return;
		}

		$ids = implode( ',', array_map( 'absint', $stale ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ids are absint()ed above.
		$wpdb->query( "DELETE FROM {$this->table()} WHERE id IN ({$ids})" );
	}
}
