<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- live reaction analytics writes; cache would defeat the purpose.
namespace WPDeveloper\BetterDocs\REST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class Feedback extends BaseAPI {
	/**
	 * The reaction/feedback beacon is public (logged-out visitors react), so it is
	 * gated by a wp_rest nonce the frontend sends via the X-WP-Nonce header —
	 * mirroring the analytics view beacon ({@see REST\AnalyticsTracker}). Without
	 * this it inherited BaseAPI::permission_check() (return true) and could be
	 * scripted anonymously to forge reaction counts and flood the Pro feedback
	 * table (one row per call via the betterdocs_feedback_recorded action). A light
	 * salted-IP throttle bounds abuse even if a nonce is harvested from a page.
	 */
	public function permission_check( $request = null ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return false;
		}

		$nonce = $request->get_header( 'x_wp_nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return false;
		}

		// Defense-in-depth: cap reactions per client (salted IP hash, raw IP never
		// stored) so a harvested nonce can't be scripted into a table flood.
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key  = 'bd_feedback_rl_' . substr( wp_hash( $ip ), 0, 20 );
		$hits = (int) get_transient( $key );
		if ( $hits >= 120 ) {
			return new \WP_Error( 'bd_feedback_throttled', __( 'Too many reactions — please try again in a moment.', 'betterdocs' ), [ 'status' => 429 ] );
		}
		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * @return mixed
	 */
	public function register() {
		$this->post(
			'/feedback/(?P<id>\d+)',
			[ $this, 'save' ],
			[
				'id'       => [
					'type'              => 'integer',
					'validate_callback' => function ( $param, $request, $key ) {
						return ! empty( $param ) && is_numeric( $param ) && get_post( $param ) !== null;
					},
					'required'          => false,
					'default'           => null
				],
				'feelings' => [
					'type'              => 'string',
					'validate_callback' => function ( $param, $request, $key ) {
						$allowed_feelings = [ 'happy', 'sad', 'normal' ];
						return in_array( $param, $allowed_feelings );
					},
					'required'          => true
				]
			]
		);

		$this->register_field(
			'docs',
			'word_count',
			[
				'get_callback' => [ $this, 'get_word_count' ]
			]
		);

		$this->register_field(
			'docs',
			'total_views',
			[
				'get_callback' => [ $this, 'get_total_views' ]
			]
		);

		$this->register_field(
			'docs',
			'reactions',
			[
				'get_callback' => [ $this, 'get_reaction_count' ]
			]
		);

		$this->register_field(
			'docs',
			'author_info',
			[
				'get_callback' => [ $this, 'get_author_info' ]
			]
		);

		$this->register_field(
			'docs',
			'doc_category_info',
			[
				'get_callback' => [ $this, 'get_doc_category_info' ]
			]
		);

		$this->register_field(
			'docs',
			'doc_tag_info',
			[
				'get_callback' => [ $this, 'get_doc_tag_info' ]
			]
		);

		// $this->register_field( 'docs', 'author_list', [
		//     'get_callback' => [$this, 'get_author_list']
		// ] );
	}

	public function get_author_list( $object, $field_name, $request ) {
		$args  = [
			'fields' => [
				'ID',
				'user_login',
				'display_name'
			]
		];
		$users = get_users( $args );
		return $users;
	}

	public function analytics_by_post_id( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					sum(impressions) as totalViews,
					sum(unique_visit) as totalUniqueViews,
					sum(happy + sad + normal) as totalReactions,
					sum(happy) as totalHappy,
					sum(normal) as totalNormal,
					sum(sad) as totalSad
				FROM {$wpdb->prefix}betterdocs_analytics
				WHERE post_id = %d",
				(int) $post_id
			)
		);
	}

	public function get_word_count( $object, $field_name, $request ) {
		return str_word_count( trim( wp_strip_all_tags( get_post_field( 'post_content', $object['id'] ) ) ) );
	}

	public function get_total_views( $object, $field_name, $request ) {
		$analytics = $this->analytics_by_post_id( $object['id'] );

		if ( ! empty( $analytics ) ) {
			return isset( $analytics[0]->totalViews ) ? $analytics[0]->totalViews : 0;
		} else {
			return 0;
		}
	}

	public function get_reaction_count( $object, $field_name, $request ) {
		$analytics = $this->analytics_by_post_id( $object['id'] );

		if ( ! empty( $analytics ) ) {
			return [
				'happy'  => isset( $analytics[0]->totalHappy ) ? $analytics[0]->totalHappy : 0,
				'normal' => isset( $analytics[0]->totalNormal ) ? $analytics[0]->totalNormal : 0,
				'sad'    => isset( $analytics[0]->totalSad ) ? $analytics[0]->totalSad : 0
			];
		} else {
			return [
				'happy'  => 0,
				'normal' => 0,
				'sad'    => 0
			];
		}
	}

	public function save( WP_REST_Request $request ) {
		global $wpdb;
		$docs_id           = isset( $request['id'] ) ? (int) $request['id'] : null;
		$valid_feelings    = [ 'happy', 'normal', 'sad' ];
		$requested_feeling = isset( $request['feelings'] ) ? (string) $request['feelings'] : 'happy';
		$feelings          = in_array( $requested_feeling, $valid_feelings, true ) ? $requested_feeling : 'happy';
		$analytics_table   = $wpdb->prefix . 'betterdocs_analytics';
		if ( $docs_id !== null && get_post( $docs_id ) && get_option( 'betterdocs_db_version' ) == true ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $analytics_table = $wpdb->prefix + literal; per-request reaction lookup, no cache layer applies.
			$post_id = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$analytics_table} WHERE created_at = %s AND post_id = %d",
					gmdate( 'Y-m-d' ),
					$docs_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( ! empty( $post_id ) ) {
				$feelings_increment = (int) $post_id[0]->{$feelings} + 1;
				// $feelings is validated above against $valid_feelings allowlist — safe to interpolate as column identifier.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $analytics_table = $wpdb->prefix + literal; per-request reaction counter, no cache layer applies.
				$insert             = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$analytics_table} SET {$feelings} = %d WHERE created_at = %s AND post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$feelings_increment,
						gmdate( 'Y-m-d' ),
						$docs_id
					)
				);
			} else {
				// $feelings is validated above against $valid_feelings allowlist — safe to interpolate as column identifier.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $analytics_table = $wpdb->prefix + literal; per-request reaction counter, no cache layer applies.
				$insert = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$analytics_table} ( post_id, {$feelings}, created_at ) VALUES ( %d, %d, %s )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$docs_id,
						1,
						gmdate( 'Y-m-d' )
					)
				);
			}

			if ( $insert == true ) {
				/**
				 * Fires after a reaction is recorded into the daily aggregate.
				 * Pro hooks this to write a per-item row into the feedback inbox
				 * table (betterdocs_analytics_feedback).
				 *
				 * @param int    $docs_id  Doc post id.
				 * @param string $feelings happy|sad|normal.
				 */
				do_action( 'betterdocs_feedback_recorded', (int) $docs_id, $feelings );
				return true;
			}
		}
		return false;
	}

	public function get_author_info( $object, $field_name, $request ) {
		$author_id = isset( $object['author'] ) ? $object['author'] : '';
		if ( ! empty( $author_id ) ) {
			return [
				'name'            => get_the_author_meta( 'display_name', $author_id ),
				'author_nicename' => get_the_author_meta( 'nicename', $author_id ),
				'author_url'      => get_author_posts_url( $author_id )
			];
		}
		return [];
	}

	public function get_doc_category_info( $object, $field_name, $request ) {
		$category_term_names = [];
		$doc_categories      = ! empty( $object['doc_category'] ) ? $object['doc_category'] : [];
		foreach ( $doc_categories as $doc_category_id ) {
			array_push(
				$category_term_names,
				[
					'term_name' => get_term( $doc_category_id )->name,
					'term_url'  => get_term_link( $doc_category_id )
				]
			);
		}
		return $category_term_names;
	}

	public function get_doc_tag_info( $object, $field_name, $request ) {
		$doc_tag_term_names = [];
		$doc_tags           = ! empty( $object['doc_tag'] ) ? $object['doc_tag'] : [];
		foreach ( $doc_tags as $tag_id ) {
			array_push(
				$doc_tag_term_names,
				[
					'term_name' => get_term( $tag_id )->name,
					'term_url'  => get_term_link( $tag_id )
				]
			);
		}
		return $doc_tag_term_names;
	}
}
