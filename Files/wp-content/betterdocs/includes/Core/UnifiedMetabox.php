<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Core\Settings;

/**
 * Unified Metabox for BetterDocs
 *
 * This class creates a tabbed metabox interface that consolidates:
 * - Article Quality Analysis
 * - Estimated Reading Time
 * - Related Docs (from Pro)
 *
 * @since 3.7.0
 */
class UnifiedMetabox extends Base {

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Active tabs
	 *
	 * @var array
	 */
	private $tabs = [];

	/**
	 * Constructor
	 *
	 * @param Settings $settings Settings instance
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		if ( ! is_admin() ) {
			return;
		}

		// Register the unified metabox
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ], 5 );

		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register the unified metabox
	 */
	public function register_metabox() {
		// Build tabs array
		$this->build_tabs();

		// Only add metabox if there are tabs to show
		if ( empty( $this->tabs ) ) {
			return;
		}

		add_meta_box(
			'betterdocs-unified-metabox',
			__( 'BetterDocs', 'betterdocs' ),
			[ $this, 'render_metabox' ],
			'docs',
			'normal',
			'high'
		);
	}

	/**
	 * Build the tabs array based on enabled features
	 */
	private function build_tabs() {
		$this->tabs = [];

		// Article Quality Analysis tab (always available)
		$this->tabs['quality-analysis'] = [
			'id'       => 'quality-analysis',
			'label'    => __( 'Article Quality Analysis', 'betterdocs' ),
			'callback' => [ $this, 'render_quality_analysis_tab' ],
			'priority' => 10
		];

		// Estimated Reading Time tab
		if ( $this->settings->get( 'enable_estimated_reading_time' ) ) {
			$this->tabs['reading-time'] = [
				'id'       => 'reading-time',
				'label'    => __( 'Estimated Reading Time', 'betterdocs' ),
				'callback' => [ $this, 'render_reading_time_tab' ],
				'priority' => 20
			];
		}

		// Attachments tab (from Pro)
		if ( $this->settings->get( 'show_attachment' ) ) {
			$this->tabs['attachments'] = [
				'id'       => 'attachments',
				'label'    => __( 'Attachments', 'betterdocs' ),
				'callback' => [ $this, 'render_attachments_tab' ],
				'priority' => 30
			];
		}

		// Related Docs tab (from Pro)
		if ( $this->settings->get( 'show_related_docs' ) ) {
			$this->tabs['related-docs'] = [
				'id'       => 'related-docs',
				'label'    => __( 'Related Docs', 'betterdocs' ),
				'callback' => [ $this, 'render_related_docs_tab' ],
				'priority' => 40
			];
		}

		// Allow other plugins/features to add tabs
		$this->tabs = apply_filters( 'betterdocs_unified_metabox_tabs', $this->tabs );

		// Sort tabs by priority
		uasort( $this->tabs, function( $a, $b ) {
			return ( $a['priority'] ?? 999 ) - ( $b['priority'] ?? 999 );
		});
	}

	/**
	 * Render the unified metabox
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_metabox( $post ) {
		if ( empty( $this->tabs ) ) {
			return;
		}

		$first_tab = array_key_first( $this->tabs );
		?>
		<div class="betterdocs-unified-metabox">
			<div class="betterdocs-metabox-tabs">
				<?php foreach ( $this->tabs as $tab_id => $tab ) : ?>
					<button
						type="button"
						class="betterdocs-metabox-tab <?php echo $tab_id === $first_tab ? 'active' : ''; ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>"
					>
						<span class="tab-label"><?php echo esc_html( $tab['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="betterdocs-metabox-content">
				<?php foreach ( $this->tabs as $tab_id => $tab ) : ?>
					<div
						class="betterdocs-metabox-panel <?php echo $tab_id === $first_tab ? 'active' : ''; ?>"
						data-panel="<?php echo esc_attr( $tab_id ); ?>"
					>
						<?php
						if ( is_callable( $tab['callback'] ) ) {
							call_user_func( $tab['callback'], $post );
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Article Quality Analysis tab
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_quality_analysis_tab( $post ) {
		// Hook for Article Quality Score to render its content
		do_action( 'betterdocs_quality_analysis_tab_content', $post );
	}

	/**
	 * Render Estimated Reading Time tab
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_reading_time_tab( $post ) {
		echo '<div class="betterdocs-reading-time-content">';
		// Hook for Estimated Reading Time to render its content
		do_action( 'betterdocs_reading_time_tab_content', $post );
		echo '</div>';
	}

	/**
	 * Render Attachments tab
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_attachments_tab( $post ) {
		echo '<div class="betterdocs-attachments-content">';
		// Hook for Attachments (Pro) to render its content
		do_action( 'betterdocs_attachments_tab_content', $post );
		echo '</div>';
	}

	/**
	 * Render Related Docs tab
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_related_docs_tab( $post ) {
		echo '<div class="betterdocs-related-docs-content">';
		// Hook for Related Docs (Pro) to render its content
		do_action( 'betterdocs_related_docs_tab_content', $post );
		echo '</div>';
	}

	/**
	 * Enqueue assets for the unified metabox
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_assets( $hook ) {
		global $post_type;

		// Only load on docs edit screens
		if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post_type === 'docs' ) {
			betterdocs()->assets->enqueue( 'betterdocs-unified-metabox', 'admin/js/unified-metabox.js' );

			// Enqueue styles
			wp_enqueue_style(
				'betterdocs-unified-metabox',
				BETTERDOCS_ABSURL . 'assets/build/admin/css/unified-metabox.css',
				[],
				BETTERDOCS_VERSION
			);
		}
	}

	/**
	 * Get AI icon SVG
	 *
	 * @return string SVG icon markup
	 */
	private function get_ai_icon() {
		return '<svg width="16" height="16" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g filter="url(#filter0_d_13132_3862)">
				<path d="M4.5 15.9986C4.5 15.5068 4.89984 15.1128 5.38925 15.0642C9.59729 14.647 12.9438 11.3004 13.361 7.09237C13.4096 6.60296 13.8036 6.20312 14.2954 6.20312C14.7872 6.20312 15.1812 6.60296 15.2297 7.09237C15.6469 11.3004 18.9935 14.647 23.2015 15.0642C23.6909 15.1128 24.0907 15.5068 24.0907 15.9986C24.0907 16.4904 23.6909 16.8844 23.2015 16.9329C18.9935 17.3501 15.6469 20.6967 15.2297 24.9048C15.1812 25.3942 14.7872 25.794 14.2954 25.794C13.8036 25.794 13.4096 25.3942 13.361 24.9048C12.9438 20.6967 9.59729 17.3501 5.38925 16.9329C4.89984 16.8844 4.5 16.4904 4.5 15.9986Z" fill="url(#paint0_linear_13132_3862)"/>
			</g>
			<defs>
				<linearGradient id="paint0_linear_13132_3862" x1="14.2954" y1="6.20312" x2="14.2954" y2="25.794" gradientUnits="userSpaceOnUse">
					<stop stop-color="#6C5CE7"/>
					<stop offset="1" stop-color="#A29BFE"/>
				</linearGradient>
			</defs>
		</svg>';
	}
}

