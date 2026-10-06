<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * Read/write helper for {prefix}betterdocs_analytics_insights (Content
 * Intelligence, Analytics v2.0).
 *
 * One row per detected item (stale article, health score, content gap,
 * duplicate pair). The `(type, object_hash, kb_id, lang)` unique key lets a
 * nightly re-scan UPSERT an existing item in place instead of duplicating it,
 * while the user-driven `status` lifecycle (new → accepted/dismissed/done) is
 * preserved across re-scans: upsert() only sets status on INSERT, never on
 * UPDATE, so a dismissed insight stays dismissed even if it re-qualifies.
 */
class AnalyticsInsightStore {
	/**
	 * Stable md5 natural key for an insight, so re-scans map to the same row.
	 * For a single-post insight pass the post id; for a pair (duplicates) pass
	 * both ids — order-independent so (A,B) and (B,A) collapse to one row.
	 */
	public function object_hash( $type, $primary, $secondary = null ) {
		if ( $secondary === null ) {
			return md5( $type . ':' . (int) $primary );
		}
		$ids = [ (int) $primary, (int) $secondary ];
		sort( $ids );
		return md5( $type . ':' . $ids[0] . ':' . $ids[1] );
	}

	/**
	 * Insert or refresh an insight. On a duplicate natural key only the computed
	 * fields (score, payload, source, object_id, updated_at) are overwritten —
	 * the lifecycle `status` and original `created_at` are left intact.
	 *
	 * @param string $type        gap|stale|health|duplicate|rewrite
	 * @param string $object_hash from object_hash()
	 * @param float  $score       module score (e.g. staleness 0–100, health 0–100)
	 * @param array  $payload     view-shaped data (JSON-encoded)
	 * @param array  $args        object_id, source (local|cloud), kb_id, lang
	 */
	public function upsert( $type, $object_hash, $score, array $payload, array $args = [] ) {
		global $wpdb;

		$now = current_time( 'mysql', true );

		return $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}betterdocs_analytics_insights
					( type, object_id, object_hash, score, status, source, payload, kb_id, lang, created_at, updated_at )
				VALUES ( %s, %d, %s, %f, 'new', %s, %s, %d, %s, %s, %s )
				ON DUPLICATE KEY UPDATE
					object_id = VALUES( object_id ),
					score = VALUES( score ),
					source = VALUES( source ),
					payload = VALUES( payload ),
					updated_at = VALUES( updated_at )",
				$type,
				isset( $args['object_id'] ) ? (int) $args['object_id'] : 0,
				$object_hash,
				(float) $score,
				isset( $args['source'] ) ? (string) $args['source'] : 'local',
				wp_json_encode( $payload ),
				isset( $args['kb_id'] ) ? (int) $args['kb_id'] : 0,
				isset( $args['lang'] ) ? (string) $args['lang'] : '',
				$now,
				$now
			)
		);
	}

	/**
	 * Prune auto-detected rows of a type whose object_hash is no longer in
	 * $keep_hashes — e.g. an article that is no longer stale. Only 'new' rows are
	 * removed; anything the user has actioned (accepted/dismissed/done) is kept as
	 * history. Passing an empty keep-set clears all 'new' rows of that type.
	 */
	public function prune_missing( $type, array $keep_hashes ) {
		global $wpdb;

		if ( empty( $keep_hashes ) ) {
			return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}betterdocs_analytics_insights WHERE type = %s AND status = 'new'",
					$type
				)
			);
		}

		$placeholders = implode( ',', array_fill( 0, count( $keep_hashes ), '%s' ) );
		$params       = array_merge( [ $type ], array_values( $keep_hashes ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}betterdocs_analytics_insights
				WHERE type = %s AND status = 'new' AND object_hash NOT IN ( {$placeholders} )",
				$params
			)
		);
	}

	/**
	 * Update the lifecycle status of one insight (dashboard actions). Returns the
	 * number of rows changed (0 if the id/status was invalid or unchanged).
	 */
	public function set_status( $id, $status ) {
		global $wpdb;

		$allowed = [ 'new', 'accepted', 'dismissed', 'done' ];
		if ( ! in_array( $status, $allowed, true ) ) {
			return 0;
		}

		return (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}betterdocs_analytics_insights
				SET status = %s, updated_at = %s WHERE id = %d",
				$status,
				current_time( 'mysql', true ),
				(int) $id
			)
		);
	}
}
