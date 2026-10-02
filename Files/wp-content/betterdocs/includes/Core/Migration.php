<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- one-shot data/schema migrations; caching unwanted.
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_Query;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Dependencies\DI\Container;

class Migration extends Base {
	/**
	 * Database
	 * @var Database
	 */
	private $database;
	private $settings;
	private $container;

	public function __construct( Container $container ) {
		$this->container = $container;
		$this->database  = $container->get( Database::class );
		$this->settings  = $container->get( Settings::class );
	}

	public function init( $version ) {
		if ( $version > 250 ) {
			for ( $_version = 250; $_version <= $version; $_version++ ) {
				if ( method_exists( $this, "v$_version" ) ) {
					call_user_func( [ $this, "v$_version" ] );
				}
			}
		}

		$this->search_migration();
		$this->fix_search_table_collation();
		$this->backfill_search_keyword_hash();

		/**
		 * Settings Migration
		 */
		$this->settings->migration( $version );
	}

	public function search_migration() {
		global $wpdb;
		if ( ! $this->database->get( 'betterdocs_search_data_migration', false ) ) {
			$search_data = $this->database->get( 'betterdocs_search_data' );
			if ( ! empty( $search_data ) ) {
				$search_data_arr = unserialize( $search_data );
				foreach ( $search_data_arr as $key => $value ) {
					$args = [
						'post_type'        => 'docs',
						'post_status'      => 'publish',
						'posts_per_page'   => -1,
						'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts,WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- migration must run on raw posts without language filters.
						's'                => $key
					];

					$loop = new WP_Query( $args );
					if ( $loop->have_posts() ) {
						$count           = $value;
						$not_found_count = 0;
					} else {
						$count           = 0;
						$not_found_count = $value;
					}

					// Use BINARY comparison to avoid collation mismatch errors
				$keyword = $wpdb->get_var(
						$wpdb->prepare(
							"
                            SELECT keyword
                            FROM {$wpdb->prefix}betterdocs_search_keyword
                            WHERE BINARY keyword = %s",
							$key
						)
					);

					if ( $keyword == null ) {
						$insert = $wpdb->query(
							$wpdb->prepare(
								"INSERT INTO {$wpdb->prefix}betterdocs_search_keyword
                                ( keyword, keyword_hash )
                                VALUES ( %s, %s )",
								[
									$key,
									md5( $key )
								]
							)
						);

						if ( $insert ) {
							$wpdb->query(
								$wpdb->prepare(
									"INSERT INTO {$wpdb->prefix}betterdocs_search_log
                                    (keyword_id, count, not_found_count, created_at)
                                    VALUES (%d, %d, %d, %s)",
									[
										$wpdb->insert_id,
										$count,
										$not_found_count,
										gmdate( 'Y-m-d' )
									]
								)
							);
						}
					}
				}

				$this->database->save( 'betterdocs_search_data_migration', '1.0' );
			}
		}
	}

	/**
	 * Fix collation issues in search tables
	 * Converts tables to proper UTF-8 charset and collation to prevent
	 * "Illegal mix of collations" errors when searching with non-Latin characters
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function fix_search_table_collation() {
		global $wpdb;

		// Check if migration already ran
		if ( $this->database->get( 'betterdocs_search_collation_fixed', false ) ) {
			return;
		}

		// Get the WordPress default charset and collation; restrict to a safe identifier
		// alphabet because they're interpolated into ALTER TABLE statements below.
		$charset = preg_replace( '/[^A-Za-z0-9_]/', '', $wpdb->charset ? $wpdb->charset : 'utf8mb4' );
		$collate = preg_replace( '/[^A-Za-z0-9_]/', '', $wpdb->collate ? $wpdb->collate : 'utf8mb4_unicode_520_ci' );

		// Fix search_keyword table
		$search_keyword_table = $wpdb->prefix . 'betterdocs_search_keyword';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- one-shot collation migration; identifiers come from $wpdb only.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $search_keyword_table ) ) === $search_keyword_table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "ALTER TABLE `{$search_keyword_table}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collate}" );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "ALTER TABLE `{$search_keyword_table}` MODIFY keyword TEXT CHARACTER SET {$charset} COLLATE {$collate} NOT NULL" );
		}

		// Fix search_log table
		$search_log_table = $wpdb->prefix . 'betterdocs_search_log';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $search_log_table ) ) === $search_log_table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "ALTER TABLE `{$search_log_table}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collate}" );
		}

		// Mark migration as complete
		$this->database->save( 'betterdocs_search_collation_fixed', '1.0' );
	}

	/**
	 * Populate keyword_hash for rows that predate the column.
	 *
	 * The column is added by dbDelta with an empty default; until it holds the
	 * hash, insert_search_keyword() cannot find those rows by index and would
	 * insert duplicates. Runs in bounded batches so a large keyword table does
	 * not stall the request that triggers the upgrade — anything left over is
	 * picked up on the next admin load.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	public function backfill_search_keyword_hash() {
		global $wpdb;

		if ( $this->database->get( 'betterdocs_search_keyword_hash_filled', false ) ) {
			return;
		}

		$table = $wpdb->prefix . 'betterdocs_search_keyword';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- one-shot schema backfill; identifier comes from $wpdb.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		// Bail if the column never made it (dbDelta failed) — retry next load.
		if ( ! $wpdb->get_results( "SHOW COLUMNS FROM `{$table}` LIKE 'keyword_hash'" ) ) {
			return;
		}

		$batches = 0;
		do {
			$updated = $wpdb->query(
				"UPDATE `{$table}` SET keyword_hash = MD5( keyword ) WHERE keyword_hash = '' LIMIT 2000"
			);
			$batches++;
		} while ( $updated > 0 && $batches < 25 );

		// Only finish once nothing is left, so a table larger than 50k keywords
		// resumes instead of being marked done half-filled.
		$remaining = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE keyword_hash = ''" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( 0 === $remaining ) {
			$this->database->save( 'betterdocs_search_keyword_hash_filled', '1.0.3' );
		}
	}
}
