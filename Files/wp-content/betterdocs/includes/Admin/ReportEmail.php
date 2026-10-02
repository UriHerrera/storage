<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- weekly/monthly analytics aggregation; runs once per email send.
namespace WPDeveloper\BetterDocs\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Error;
use WP_Query;
use stdClass;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Views;
use WPDeveloper\BetterDocs\Core\Settings;

/**
 * This class is responsible for sending weekly email with reports
 *
 * @since 1.4.4
 */
class ReportEmail extends Base {
	/**
	 * Get a single Instance of Analytics
	 * @var Settings
	 */
	public $settings;

	private static $current_timestamp = null;
	private static $current_date      = null;

	/**
	 * Initially Invoked by Default.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'betterdocs::settings::saved', [ $this, 'after_save_settings' ] );

		if ( $this->settings->get( 'enable_reporting', false ) ) {
			$this->init();
		}
	}

	public function init() {
		add_filter( 'cron_schedules', [ $this, 'schedules_cron' ] );
		add_action( 'admin_init', [ $this, 'activate' ] );
		add_action( 'betterdocs_weekly_email_reporting', [ $this, 'send_email' ] );
	}

	public function after_save_settings() {
		if ( $this->settings->get( 'enable_reporting', false ) ) {
			$this->activate();
		} else {
			$this->deactivate( true );
		}
	}

	public function reporting( $request ) {
		if ( ! boolval( $request->get_param( 'disable_reporting' ) ) ) {
			if ( $request->has_param( 'reporting_email' ) ) {
				$email = $request->get_param( 'reporting_email' );
			}
			$email = $this->receiver_email_address( $email );

			if ( ! empty( $email ) ) {
				$is_send = $this->send_email( $request->get_param( 'reporting_frequency' ), true, $email );
				if ( $is_send && ! is_wp_error( $is_send ) ) {
					return [ 'message' => __( 'Successfully Sent an Email', 'betterdocs' ) ];
				} elseif ( is_wp_error( $is_send ) ) {
					return $is_send;
				} else {
					return $this->error( 'betterdocs_unknown_reason', __( 'Email cannot be sent for some reason.', 'betterdocs' ) );
				}
			}
			return $this->error( 'betterdocs_unknown_reason', __( 'Invalid email address.', 'betterdocs' ) );
		} else {
			return $this->error( 'betterdocs_disabled_reporting', __( 'You have to enable Reporting first.', 'betterdocs' ) );
		}
	}

	public function error( $code, $message ) {
		return new WP_Error( $code, $message );
	}

	private static function timestamps( $date = false ) {
		if ( is_null( self::$current_timestamp ) ) {
			self::$current_timestamp = current_time( 'timestamp' );
		}
		if ( $date ) {
			if ( is_null( self::$current_date ) ) {
				self::$current_date = current_time( 'Y-m-d' );
			}
			return self::$current_date;
		}
		return self::$current_timestamp;
	}

	public function create_date( $count = '-7days' ) {
		return gmdate( 'Y-m-d', strtotime( $count, self::timestamps() ) );
	}

	public function get_views( $start_date, $end_date = null ) {
		global $wpdb;
		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sum(impressions) as views, sum(unique_visit) as unique_visit, sum(happy + sad + normal) as reactions
				FROM {$wpdb->prefix}betterdocs_analytics
				WHERE created_at BETWEEN %s AND %s",
				(string) $start_date,
				(string) $end_date
			)
		);
		return $query;
	}

	public function get_search( $start_date, $end_date = null ) {
		global $wpdb;
		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SUM(count + not_found_count) as search_count, SUM(count) as search_found, SUM(not_found_count) as search_not_found_count
				FROM {$wpdb->prefix}betterdocs_search_log as search_log
				WHERE search_log.created_at BETWEEN %s AND %s",
				(string) $start_date,
				(string) $end_date
			)
		);

		return $query;
	}

	public function count_new_docs( $start_date, $end_date = null ) {
		$args  = [
			'post_type'      => 'docs',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'date_query'     => [
				[
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true
				]
			]
		];
		$query = new WP_Query( $args );

		return $query->found_posts;
	}

	public function get_search_keywords( $start_date, $end_date = null ) {
		global $wpdb;
		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT search_keyword.keyword, search_log.keyword_id,
					SUM(search_log.count + search_log.not_found_count) as total_search,
					SUM(search_log.count) as count,
					SUM(search_log.not_found_count) as not_found
				FROM {$wpdb->prefix}betterdocs_search_keyword as search_keyword
				JOIN {$wpdb->prefix}betterdocs_search_log as search_log ON search_keyword.id = search_log.keyword_id
				WHERE search_log.created_at BETWEEN %s AND %s
				GROUP BY search_log.keyword_id
				ORDER BY count DESC LIMIT 5",
				(string) $start_date,
				(string) $end_date
			)
		);

		return $query;
	}

	public function get_docs_items( $request ) {
		$prepared_post = new stdClass();

		if ( isset( $request->ID ) ) {
			$prepared_post->ID = $request->ID;
		}

		if ( isset( $request->post_title ) ) {
			$prepared_post->title = betterdocs()->template_helper->kses( $request->post_title );
		}

		if ( isset( $request->total_views ) ) {
			$prepared_post->total_views = $request->total_views;
		}

		if ( isset( $request->total_unique_visit ) ) {
			$prepared_post->total_unique_visit = $request->total_unique_visit;
		}

		if ( isset( $request->total_reactions ) ) {
			$prepared_post->total_reactions = $request->total_reactions;
		}

		if ( isset( $request->ID ) ) {
			$prepared_post->link = get_permalink( $request->ID );
		}

		return $prepared_post;
	}

	public function get_leading_docs( $start_date, $end_date = null ) {
		global $wpdb;

		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT docs.ID, docs.post_author, docs.post_title,
					SUM(analytics.impressions) as total_views,
					SUM(analytics.unique_visit) as total_unique_visit,
					SUM(analytics.happy + analytics.sad + analytics.normal) as total_reactions
				FROM {$wpdb->posts} as docs
				JOIN {$wpdb->prefix}betterdocs_analytics as analytics ON docs.ID = analytics.post_id
				WHERE post_type = %s AND post_status = %s
				AND analytics.created_at BETWEEN %s AND %s
				GROUP BY analytics.post_id
				ORDER BY total_views DESC LIMIT 5",
				'docs',
				'publish',
				(string) $start_date,
				(string) $end_date
			)
		);

		$docs = [];
		foreach ( $query as $key => $value ) {
			$docs[ $key ] = $this->get_docs_items( $value );
		}

		return $docs;
	}

	/**
	 * Calculate Total BetterDocs Views
	 *
	 * @return array
	 */
	public function get_data( $frequency = 'betterdocs_weekly' ) {
		if ( $frequency == 'betterdocs_daily' ) {
			$start_date          = $this->create_date( 'last day' );
			$end_date            = $this->create_date( 'last day' );
			$previous_start_date = $this->create_date( 'last day last day' );
			$previous_end_date   = $this->create_date( 'last day last day' );
		}

		if ( $frequency == 'betterdocs_weekly' ) {
			$start_date          = $this->create_date( '-7days' );
			$end_date            = $this->create_date( 'last day' );
			$previous_start_date = $this->create_date( '-14days' );
			$previous_end_date   = $this->create_date( '-7days' );
		}
		if ( $frequency == 'betterdocs_monthly' ) {
			$previous_start_date = $this->create_date( 'first day of last month last month' );
			$previous_end_date   = $this->create_date( 'last day of last month last month' );
			$start_date          = $this->create_date( 'first day of last month' );
			$end_date            = $this->create_date( 'last day of last month' );
		}

		$views_current_data           = $this->get_views( $start_date, $end_date );
		$views_previous_data          = $this->get_views( $previous_start_date, $previous_end_date );
		$search_current_data          = $this->get_search( $start_date, $end_date );
		$search_previous_data         = $this->get_search( $previous_start_date, $previous_end_date );
		$docs_current_data            = $this->get_leading_docs( $start_date, $end_date );
		$docs_total_current_reactions = array_sum( array_column( $docs_current_data, 'total_reactions' ) );
		$search_keyword_data          = $this->get_search_keywords( $start_date, $end_date );
		$count_new_docs_current       = $this->count_new_docs( $start_date, $end_date );
		$count_new_docs_previous      = $this->count_new_docs( $previous_start_date, $previous_end_date );

		$from_date = $start_date;
		$to_date   = $frequency == 'betterdocs_daily' ? $start_date : $end_date;

		$data = [
			'views'    => [
				'from_date'     => $from_date,
				'to_date'       => $to_date,
				'current_data'  => $views_current_data,
				'previous_data' => $views_previous_data
			],
			'search'   => [
				'from_date'     => $from_date,
				'to_date'       => $to_date,
				'current_data'  => $search_current_data,
				'previous_data' => $search_previous_data,
				'keywords'      => $search_keyword_data
			],
			'new_docs' => [
				'from_date'     => $from_date,
				'to_date'       => $to_date,
				'current_data'  => $count_new_docs_current,
				'previous_data' => $count_new_docs_previous
			],
			'docs'     => [
				'from_date'               => $from_date,
				'to_date'                 => $to_date,
				'current_data'            => $docs_current_data,
				'total_current_reactions' => $docs_total_current_reactions
			]
		];
		return $data;
	}

	/**
	 * Adds a custom cron schedule for Weekly.
	 *
	 * @param array $schedules An array of non-default cron schedules.
	 * @return array Filtered array of non-default cron schedules.
	 */
	function schedules_cron( $schedules = [] ) {
		$schedules['betterdocs_weekly']  = [
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'betterdocs' )
		];
		$schedules['betterdocs_daily']   = [
			'interval' => 86400,
			'display'  => __( 'Once Daily', 'betterdocs' )
		];
		$schedules['betterdocs_monthly'] = [
			'interval' => 2635200,
			'display'  => __( 'Once Monthly', 'betterdocs' )
		];
		return $schedules;
	}

	/**
	 * Set Email Receiver mail address
	 * By Default, Admin Email Address
	 * Admin can set Custom email from NotificationX Advanced Settings Panel
	 *
	 * @return string|array<string>
	 */
	public function receiver_email_address( $email = '' ) {
		if ( empty( $email ) ) {
			$email = $this->settings->get( 'reporting_email', '' );
			if ( empty( $email ) ) {
				$email = get_option( 'admin_email' );
			}
		}
		if ( strpos( $email, ',' ) !== false ) {
			$email = str_replace( ' ', '', $email );
			$email = explode( ',', $email );
		} else {
			$email = trim( $email );
		}
		return $email;
	}

	/**
	 * Set Email Subject
	 * By Default, subject will be "Weekly Reporting for NotificationX"
	 * Admin can set Custom Subject from NotificationX Advanced Settings Panel
	 *
	 * @return string
	 */
	public function email_subject() {
		return wp_sprintf(
			// Translators: %s is replaced with the website name.
			__( 'Your Documentation Performance of %s Website', 'betterdocs' ),
			get_bloginfo( 'name' )
		);
	}

	/**
	 * Enable Cron Function
	 * Hook: admin_init
	 */
	public function activate() {
		$day       = $this->settings->get( 'reporting_day', 'monday' );
		$frequency = $this->settings->get( 'reporting_frequency', 'betterdocs_weekly' );

		if ( $frequency === 'betterdocs_weekly' ) {
			$datetime = strtotime( "next $day 9AM", current_time( 'timestamp' ) );
			$this->schedule_event( $datetime, $frequency, 'betterdocs_weekly_email_reporting' );
		}
	}

	/**
	 * Execute Cron Function
	 * Hook: admin_init
	 */
	public function send_email( $frequency = 'betterdocs_weekly', $test = false, $email = null ) {
		// if ( ! $this->settings->get( 'enable_analytics', false ) && ! $test ) {
		//     return $this->error( 'betterdocs_disabled_analytics', __( 'Analytics disabled. No data found.', 'betterdocs' ) );
		// }

		$data = $this->get_data( $frequency );
		if ( empty( $data ) && ! $test ) {
			return $this->error( 'betterdocs_no_reporting_data', __( 'No data found.', 'betterdocs' ) );
		}

		$to = null == $email ? $this->receiver_email_address() : $email;
		if ( empty( $to ) ) {
			return $this->error( 'betterdocs_reporting_email', __( 'No email found.', 'betterdocs' ) );
		}

		$subject = $this->email_subject();

		ob_start();
		betterdocs()->views->get( 'admin/email/header' );

		betterdocs()->views->get(
			'admin/email/body',
			[
				'days'         => $this->frequency( $frequency ),
				'bloginfo'     => get_bloginfo( 'name' ),
				'frequency'    => $frequency,
				'args'         => $data,
				'report_email' => $this
			]
		);

		betterdocs()->views->get( 'admin/email/footer' );
		$message     = ob_get_clean();
		$admin_email = get_option( 'admin_email' );
		$headers     = [ 'Content-Type: text/html; charset=UTF-8', "From: BetterDocs <{$admin_email}>" ];

		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Disable Cron Function
	 * Hook: plugin_deactivation
	 */
	public function deactivate( $clear_hook = 'betterdocs_weekly_email_reporting', $filter = null ) {
		if ( is_string( $clear_hook ) ) {
			$this->clear_scheduled_hook( $clear_hook );
		} elseif ( $clear_hook === true ) {
			$deactivate_hooks = [
				'betterdocs_daily_email_reporting',
				'betterdocs_weekly_email_reporting',
				'betterdocs_monthly_email_reporting'
			];

			array_walk(
				$deactivate_hooks,
				function ( $item ) use ( $filter ) {
					if ( ! ( $filter !== null && $filter === $item ) ) {
						$this->clear_scheduled_hook( $item );
					}
				}
			);
		}
	}

	public function schedule_event( $timestamp, $frequency, $hook ) {
		if ( ! wp_next_scheduled( $hook ) ) {
			wp_schedule_event( $timestamp, $frequency, $hook );

			$this->deactivate( true, $hook );
		}
	}

	public function clear_scheduled_hook( $hook ) {
		wp_clear_scheduled_hook( $hook );
	}

	public function percentage( $previous, $current ) {
		if ( $previous == 0 ) {
			$factor = $current * 100;
		} elseif ( $current == 0 ) {
			$factor = $previous * 100;
		} else {
			$factor = ( ( (int) $current - (int) $previous ) / (int) $previous ) * 100;
		}
		return number_format( $factor, 2, '.', '' );
	}

	public function frequency( $frequency = 'betterdocs_weekly' ) {
		switch ( $frequency ) {
			case 'betterdocs_weekly':
				$days_ago = '7 days';
				break;
			case 'betterdocs_daily':
				$days_ago = '1 day';
				break;
			case 'betterdocs_monthly':
				$initial_timestamp  = strtotime( 'first day of last month', current_time( 'timestamp' ) );
				$days_in_last_month = cal_days_in_month( CAL_GREGORIAN, (int) gmdate( 'm', $initial_timestamp ), (int) gmdate( 'Y', $initial_timestamp ) );
				$days_ago           = $days_in_last_month . ' days';
				break;
		}

		return $days_ago;
	}

	public function test_email_report( $request ) {
		$email = $this->send_email( $request->get_param( 'reporting_frequency' ), true, $request->get_param( 'reporting_email' ) );

		$response = [
			'status' => 'success',
			'data'   => [
				'context' => [
					'test_report' => $email
				]
			]
		];

		if ( $email ) {
			return $response;
		} else {
			$response['status'] = 'error';
			return $response;
		}
	}
}
