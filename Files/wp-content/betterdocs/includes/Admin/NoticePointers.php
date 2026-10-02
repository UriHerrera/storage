<?php

namespace WPDeveloper\BetterDocs\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NoticePointers {
	const PROMOTION_URL = 'https://betterdocs.co/bfcm-wp-admin-pointer ';
	const BETTERDOCS_POINTER_ID = 'toplevel_page_betterdocs-dashboard';
	const DISMISS_ACTION_KEY = 'betterdocs_black_friday_pointer_2025';
	const USER_META_KEY = 'betterdocs_introduction_meta';
	const PRIORITY = 4; // Priority for this pointer (lower number = higher priority)

	public function __construct() {
		add_action( 'admin_print_footer_scripts', [ $this, 'enqueue_notice' ] );
	}

	public function enqueue_notice() {
		// Only show on specific pages: dashboard and BetterDocs screens
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		// Check if we're on allowed pages
		if ( ! $this->is_allowed_page( $screen ) ) {
			return;
		}

		// For common pages (dashboard, plugins), check priority FIRST
		$is_common_page = in_array( $screen->id, [ 'dashboard', 'plugins' ], true );
		if ( $is_common_page ) {
			$current_priority = get_option( '_wpdeveloper_plugin_pointer_priority', 999 );

			// If priority is greater than 4, update it to 4
			if ( $current_priority > 4 ) {
				update_option( '_wpdeveloper_plugin_pointer_priority', 4 );
				$current_priority = 4;
			}

			// If priority is not 4, don't display on common pages
			if ( $current_priority != 4 ) {
				return;
			}
		}

		// Check basic conditions
		if ( ! self::is_user_allowed() || self::is_dismissed() || ! self::is_campaign_time() || self::has_pro() ) {
			return;
		}

		$this->enqueue_dependencies();

		$pointer_content = '<h3>' . esc_html__( 'Black Friday Sale for BetterDocs', 'betterdocs' ) . '</h3>';
		$pointer_content .= '<p>' . esc_html__( 'Create a high-converting knowledge base & FAQ powered by AI with Instant Answers, Advanced Analytics & more.', 'betterdocs' ) . '</p>';
		$pointer_content .= sprintf(
			'<p><a class="button button-primary" href="%s" target="_blank">%s</a></p>',
			self::PROMOTION_URL,
			esc_html__( 'Claim Offer', 'betterdocs' )
		);

		$allowed_tags = [
			'h3' => [],
			'p' => [],
			'a' => [
				'class' => [],
				'target' => [ '_blank' ],
				'href' => [],
			],
		];
		?>

		<script>
			jQuery( document ).ready( function( $ ) {
				$( "#<?php echo esc_attr( self::BETTERDOCS_POINTER_ID ); ?>" ).pointer( {
					content: '<?php echo wp_kses( $pointer_content, $allowed_tags ); ?>',
					position: {
						edge: <?php echo is_rtl() ? "'right'" : "'left'"; ?>,
						align: "center"
					},
					close: function() {
						// Send AJAX request to mark as dismissed
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'betterdocs_dismiss_black_friday_pointer',
								nonce: '<?php echo esc_js( wp_create_nonce( 'betterdocs_dismiss_pointer' ) ); ?>',
								introduction_key: '<?php echo esc_attr( self::DISMISS_ACTION_KEY ); ?>'
							}
						});
					}
				} ).pointer( "open" );
			} );
		</script>
		<?php
	}

	public static function should_display_notice(): bool {
		return self::is_user_allowed() &&
			! self::is_dismissed() &&
			self::is_campaign_time();
	}

	/**
	 * Check if current page is allowed to show the pointer
	 *
	 * @param WP_Screen $screen Current screen object
	 * @return bool
	 */
	private function is_allowed_page( $screen ): bool {
		// Dashboard
		if ( $screen->id === 'dashboard' || $screen->id === 'plugins' ) {
			return true;
		}

		// BetterDocs admin pages
		$betterdocs_screens = [
			'toplevel_page_betterdocs-dashboard',
			'betterdocs_page_betterdocs-admin',
			'betterdocs_page_betterdocs-settings',
			'betterdocs_page_betterdocs-analytics',
			'betterdocs_page_betterdocs-glossaries',
			'betterdocs_page_betterdocs-faq',
			'betterdocs_page_betterdocs-ai-chatbot',
			'edit-doc_category',
			'edit-doc_tag',
			'edit-knowledge_base',
		];

		return in_array( $screen->id, $betterdocs_screens, true );
	}

	private static function is_user_allowed(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_docs' );
	}

	private static function is_campaign_time() {
		$start = new \DateTime( '2025-11-25 12:00:00', new \DateTimeZone( 'UTC' ) );
		$end = new \DateTime( '2025-12-04 23:59:59', new \DateTimeZone( 'UTC' ) );
		$now = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		return $now >= $start && $now <= $end;
	}

	private function enqueue_dependencies() {
		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );
	}

	private static function is_dismissed(): bool {
		return self::get_introduction_meta( self::DISMISS_ACTION_KEY );
	}

	/**
	 * Get introduction meta for the current user
	 *
	 * @param string|null $key Optional. Specific key to retrieve.
	 * @return mixed
	 */
	public static function get_introduction_meta( $key = null ) {
		$user_id = get_current_user_id();
		$introduction_meta = get_user_meta( $user_id, self::USER_META_KEY, true );

		if ( ! is_array( $introduction_meta ) ) {
			$introduction_meta = [];
		}

		if ( $key ) {
			return isset( $introduction_meta[ $key ] ) ? $introduction_meta[ $key ] : false;
		}

		return $introduction_meta;
	}

	/**
	 * Set introduction meta for the current user
	 *
	 * @param string $key The introduction key to set.
	 * @return void
	 */
	public static function set_introduction_viewed( $key ) {
		$user_id = get_current_user_id();
		$introduction_meta = self::get_introduction_meta();

		$introduction_meta[ $key ] = true;

		update_user_meta( $user_id, self::USER_META_KEY, $introduction_meta );
	}

	private static function has_pro(): bool {
		return is_plugin_active( 'betterdocs-pro/betterdocs-pro.php' );
	}
}

