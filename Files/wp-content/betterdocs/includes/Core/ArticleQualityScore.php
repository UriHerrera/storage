<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\AIHelper;
use WPDeveloper\BetterDocs\Utils\AIUsage;
use WPDeveloper\BetterDocs\Core\Settings;

class ArticleQualityScore extends Base {

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * AIHelper instance
	 *
	 * @var AIHelper
	 */
	private $ai_helper;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->ai_helper = new AIHelper( $settings );

		// Only initialize for docs post type in admin
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			add_action( 'wp_ajax_betterdocs_analyze_quality', [ $this, 'ajax_analyze_article_quality' ] );
			add_action( 'wp_ajax_betterdocs_save_quality_analysis', [ $this, 'ajax_save_quality_analysis' ] );
			add_action( 'wp_ajax_betterdocs_check_cached_analysis', [ $this, 'ajax_check_cached_analysis' ] );

			// Hook into unified metabox
			add_action( 'betterdocs_quality_analysis_tab_content', [ $this, 'render_quality_analysis_content' ] );
		}
	}

	/**
	 * Enqueue admin scripts for quality score functionality
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		// Only load on docs edit screens
		if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post_type === 'docs' ) {
			betterdocs()->assets->enqueue( 'betterdocs-article-quality-score', 'admin/js/article-quality-score.js' );

			// Localize script with AJAX data
			wp_localize_script(
				'betterdocs-article-quality-score',
				'betterdocsQualityScore',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'betterdocs_quality_score_nonce' ),
					'post_id'  => get_the_ID(),
					'strings'  => [
						'analysing'  => __( 'Analysing your docs with BetterDocs AI...', 'betterdocs' ),
						'error'      => __( 'Error', 'betterdocs' ),
						'regenerate' => __( 'Regenerate', 'betterdocs' ),
						'analyze'    => __( 'Analyze with BetterDocs AI', 'betterdocs' ),
					]
				]
			);
		}
	}

	/**
	 * Render quality analysis content for the unified metabox tab
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_quality_analysis_content( $post ) {
		$nonce = wp_create_nonce( 'betterdocs_quality_score_nonce' );
		$cached_analysis = get_post_meta( $post->ID, '_betterdocs_article_quality_analysis', true );
		$has_results = ! empty( $cached_analysis ) && isset( $cached_analysis['overall_score'] );

		?>
		<div class="betterdocs-quality-analysis-content" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<!-- Loading State -->
			<div class="quality-loading" style="display: none;">
				<div class="quality-loading-spinner">
					<div class="quality-spinner is-active">
						<div class="quality-spinner-color"></div>
						<div class="quality-spinner-mask"></div>
					</div>
				</div>
				<p><?php esc_html_e( 'Analysing your docs with BetterDocs AI...', 'betterdocs' ); ?></p>
			</div>

			<!-- Results State (always rendered, hidden if no results) -->
			<div class="quality-results" style="<?php echo $has_results ? '' : 'display: none;'; ?>">
				<?php if ( $has_results ) : ?>
					<?php $this->render_quality_results( $cached_analysis ); ?>
				<?php endif; ?>
			</div>

			<!-- Empty State -->
			<div class="quality-empty" style="<?php echo $has_results ? 'display: none;' : ''; ?>">
				<h4><?php esc_html_e( 'Docs quality analysis', 'betterdocs' ); ?></h4>
				<p><?php esc_html_e( 'Analyze your docs quality with BetterDocs AI to get actionable insights and improvements', 'betterdocs' ); ?></p>
				<span class="betterdocs-analyze-btn" id="betterdocs-analyze-quality">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_13783_29413)">
						<g filter="url(#filter0_d_13783_29413)">
						<path d="M3.32812 10.2207C3.32812 9.93383 3.56132 9.70404 3.84675 9.67574C6.30094 9.43242 8.2527 7.48064 8.49602 5.02643C8.52431 4.741 8.75411 4.50781 9.04093 4.50781C9.32776 4.50781 9.55755 4.741 9.58585 5.02643C9.82917 7.48064 11.7809 9.43242 14.2351 9.67574C14.5205 9.70404 14.7537 9.93383 14.7537 10.2207C14.7537 10.5075 14.5205 10.7373 14.2351 10.7656C11.7809 11.0089 9.82917 12.9607 9.58585 15.4149C9.55755 15.7003 9.32776 15.9335 9.04093 15.9335C8.75411 15.9335 8.52431 15.7003 8.49602 15.4149C8.2527 12.9607 6.30094 11.0089 3.84675 10.7656C3.56132 10.7373 3.32812 10.5075 3.32812 10.2207Z" fill="url(#paint0_linear_13783_29413)"></path>
						</g>
						<g filter="url(#filter1_d_13783_29413)">
						<path d="M13.6133 3.93261C13.6133 3.84656 13.6832 3.77762 13.7689 3.76913C14.5051 3.69613 15.0907 3.1106 15.1636 2.37434C15.1721 2.28871 15.2411 2.21875 15.3271 2.21875C15.4132 2.21875 15.4821 2.28871 15.4906 2.37434C15.5636 3.1106 16.1491 3.69613 16.8854 3.76913C16.971 3.77762 17.041 3.84656 17.041 3.93261C17.041 4.01865 16.971 4.08759 16.8854 4.09608C16.1491 4.16908 15.5636 4.75462 15.4906 5.49088C15.4821 5.5765 15.4132 5.64646 15.3271 5.64646C15.2411 5.64646 15.1721 5.5765 15.1636 5.49088C15.0907 4.75462 14.5051 4.16908 13.7689 4.09608C13.6832 4.08759 13.6133 4.01865 13.6133 3.93261Z" fill="url(#paint1_linear_13783_29413)"></path>
						</g>
						<g filter="url(#filter2_d_13783_29413)">
						<path d="M12.4727 15.9336C12.4727 15.8188 12.5659 15.7269 12.6801 15.7156C13.6618 15.6183 14.4425 14.8376 14.5398 13.8559C14.5511 13.7417 14.643 13.6484 14.7578 13.6484C14.8725 13.6484 14.9644 13.7417 14.9757 13.8559C15.0731 14.8376 15.8538 15.6183 16.8355 15.7156C16.9496 15.7269 17.0429 15.8188 17.0429 15.9336C17.0429 16.0483 16.9496 16.1402 16.8355 16.1515C15.8538 16.2489 15.0731 17.0296 14.9757 18.0113C14.9644 18.1254 14.8725 18.2187 14.7578 18.2187C14.643 18.2187 14.5511 18.1254 14.5398 18.0113C14.4425 17.0296 13.6618 16.2489 12.6801 16.1515C12.5659 16.1402 12.4727 16.0483 12.4727 15.9336Z" fill="url(#paint2_linear_13783_29413)"></path>
						</g>
						</g>
						<defs>
						<filter id="filter0_d_13783_29413" x="-1.24214" y="3.36525" width="20.5663" height="20.5624" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
						<feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
						<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"></feColorMatrix>
						<feOffset dy="3.4277"></feOffset>
						<feGaussianBlur stdDeviation="2.28513"></feGaussianBlur>
						<feComposite in2="hardAlpha" operator="out"></feComposite>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.00392157 0 0 0 0 0.137255 0 0 0 0.13 0"></feColorMatrix>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_13783_29413"></feBlend>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_13783_29413" result="shape"></feBlend>
						</filter>
						<filter id="filter1_d_13783_29413" x="9.04302" y="1.07618" width="12.5663" height="12.5702" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
						<feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
						<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"></feColorMatrix>
						<feOffset dy="3.4277"></feOffset>
						<feGaussianBlur stdDeviation="2.28513"></feGaussianBlur>
						<feComposite in2="hardAlpha" operator="out"></feComposite>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.00392157 0 0 0 0 0.137255 0 0 0 0.13 0"></feColorMatrix>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_13783_29413"></feBlend>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_13783_29413" result="shape"></feBlend>
						</filter>
						<filter id="filter2_d_13783_29413" x="7.90239" y="12.5059" width="13.7108" height="13.7108" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
						<feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
						<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"></feColorMatrix>
						<feOffset dy="3.4277"></feOffset>
						<feGaussianBlur stdDeviation="2.28513"></feGaussianBlur>
						<feComposite in2="hardAlpha" operator="out"></feComposite>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.00392157 0 0 0 0 0.137255 0 0 0 0.13 0"></feColorMatrix>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_13783_29413"></feBlend>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_13783_29413" result="shape"></feBlend>
						</filter>
						<linearGradient id="paint0_linear_13783_29413" x1="9.04093" y1="4.50781" x2="9.04093" y2="15.9335" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CD93FF"></stop>
						<stop offset="1" stop-color="#A741FF"></stop>
						</linearGradient>
						<linearGradient id="paint1_linear_13783_29413" x1="15.3271" y1="2.21875" x2="15.3271" y2="5.64646" gradientUnits="userSpaceOnUse">
						<stop stop-color="#D099FF"></stop>
						<stop offset="1" stop-color="#CE96FF"></stop>
						</linearGradient>
						<linearGradient id="paint2_linear_13783_29413" x1="14.7578" y1="13.6484" x2="14.7578" y2="18.2187" gradientUnits="userSpaceOnUse">
						<stop stop-color="#D099FF"></stop>
						<stop offset="1" stop-color="#CE96FF"></stop>
						</linearGradient>
						<clipPath id="clip0_13783_29413">
						<rect width="20" height="20" fill="white"></rect>
						</clipPath>
						</defs>
					</svg>
					<?php esc_html_e( 'Analyze with BetterDocs AI', 'betterdocs' ); ?>
				</span>
			</div>

			<!-- Error State -->
		<div class="quality-error" style="display: none;">
			<div class="error-content">
				<div class="error-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-6 h-6" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
				</div>
				<div class="error-text">
					<strong><?php esc_html_e( 'Configuration Required', 'betterdocs' ); ?></strong>
					<div class="error-message"></div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=betterdocs-settings#betterdocs-ai' ) ); ?>" class="error-action-button" style="display: none;">
						<?php esc_html_e( 'Go to Settings', 'betterdocs' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
					</a>
				</div>
			</div>
		</div>
		</div>
		<?php
	}

	/**
	 * Render quality results
	 *
	 * @param array $analysis Analysis data
	 */
	private function render_quality_results( $analysis ) {
		$overall_score = $analysis['overall_score'] ?? 0;
		$detailed_scores = $analysis['detailed_scores'] ?? $analysis['scores'] ?? [];
		$suggestions = $analysis['suggestions'] ?? [];

		// Extract suggestions from feedback if available
		if ( empty( $suggestions ) && isset( $analysis['feedback'] ) ) {
			$feedback = $analysis['feedback'];
			if ( isset( $feedback['strengths'] ) && is_array( $feedback['strengths'] ) ) {
				$suggestions = array_merge( $suggestions, $feedback['strengths'] );
			}
			if ( isset( $feedback['improvements'] ) && is_array( $feedback['improvements'] ) ) {
				$suggestions = array_merge( $suggestions, $feedback['improvements'] );
			}
			if ( isset( $feedback['suggestions'] ) && is_array( $feedback['suggestions'] ) ) {
				$suggestions = array_merge( $suggestions, $feedback['suggestions'] );
			}
		}

		$score_color = $this->get_score_status_class( $overall_score );
		$circumference = 2 * M_PI * 54; // radius = 54
		$offset = $circumference - ( $overall_score / 100 ) * $circumference;
		?>

		<div class="quality-results-header">
			<h4><?php esc_html_e( 'Docs quality analysis results', 'betterdocs' ); ?></h4>
			<button type="button" class="betterdocs-regenerate-quality" id="betterdocs-regenerate-quality">
				<svg
					width="16"
					height="22"
					viewBox="0 0 16 22"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
					d="M1.1 15.05C0.733333 14.4167 0.458333 13.7667 0.275 13.1C0.0916667 12.4333 0 11.75 0 11.05C0 8.81667 0.775 6.91667 2.325 5.35C3.875 3.78333 5.76667 3 8 3H8.175L6.575 1.4L7.975 0L11.975 4L7.975 8L6.575 6.6L8.175 5H8C6.33333 5 4.91667 5.5875 3.75 6.7625C2.58333 7.9375 2 9.36667 2 11.05C2 11.4833 2.05 11.9083 2.15 12.325C2.25 12.7417 2.4 13.15 2.6 13.55L1.1 15.05ZM8.025 22L4.025 18L8.025 14L9.425 15.4L7.825 17H8C9.66667 17 11.0833 16.4125 12.25 15.2375C13.4167 14.0625 14 12.6333 14 10.95C14 10.5167 13.95 10.0917 13.85 9.675C13.75 9.25833 13.6 8.85 13.4 8.45L14.9 6.95C15.2667 7.58333 15.5417 8.23333 15.725 8.9C15.9083 9.56667 16 10.25 16 10.95C16 13.1833 15.225 15.0833 13.675 16.65C12.125 18.2167 10.2333 19 8 19H7.825L9.425 20.6L8.025 22Z"
					fill="#475467"
					/>
				</svg>
			</button>
		</div>

		<div class="quality-results-body">
			<!-- Left Column: Circular Progress Score -->
			<div class="score-column">
				<h3 class="score-column-title"><?php esc_html_e( 'Overall Score', 'betterdocs' ); ?></h3>
				<div class="progress-ring-wrapper">
					<svg class="progress-ring" viewBox="0 0 120 120">
						<circle class="progress-ring-bg" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
						<circle class="progress-ring-circle <?php echo esc_attr( $score_color ); ?>" cx="60" cy="60" r="54" fill="none" stroke-width="12" stroke-linecap="round" style="stroke-dasharray: <?php echo esc_attr( $circumference ); ?>; stroke-dashoffset: <?php echo esc_attr( $offset ); ?>; transform: rotate(-90deg); transform-origin: 50% 50%; transition: stroke-dashoffset 0.5s ease-in-out;"></circle>
					</svg>
					<div class="score-text-wrapper">
						<span class="score-value <?php echo esc_attr( $score_color ); ?>"><?php echo esc_html( $overall_score ); ?></span>
						<span class="score-label"><?php esc_html_e( 'out of 100', 'betterdocs' ); ?></span>
					</div>
				</div>
				<div class="score-feedback <?php echo esc_attr( $score_color ); ?>">
					<?php echo esc_html( $this->get_score_status_text( $overall_score ) ); ?>
				</div>
				<p class="score-description">
					<?php esc_html_e( 'This score is a weighted average of clarity, relevance, structure, and other quality factors.', 'betterdocs' ); ?>
				</p>
			</div>

			<!-- Right Column: Score Breakdown & Suggestions -->
			<div class="details-column">
				<?php if ( ! empty( $detailed_scores ) ) : ?>
					<div class="section">
						<h3 class="section-title"><?php esc_html_e( 'Score Breakdown', 'betterdocs' ); ?></h3>
						<div class="score-breakdown-list">
							<?php foreach ( $detailed_scores as $criterion => $score ) : ?>
								<div class="score-item">
									<div class="score-name-group">
										<span class="score-name"><?php echo esc_html( ucfirst( $criterion ) ); ?></span>
										<span class="score-percentage"><?php echo esc_html( $score ); ?>%</span>
									</div>
									<div class="score-bar-group">
										<div class="score-bar-wrapper">
											<div class="score-bar-fill <?php echo esc_attr( $this->get_score_status_class( $score ) ); ?>" style="width: <?php echo esc_attr( $score ); ?>%"></div>
										</div>

									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! empty( $suggestions ) ) : ?>
		<div class="improvement-section section">
			<h3 class="section-title"><?php esc_html_e( 'Actionable Insights', 'betterdocs' ); ?></h3>
			<div class="improvements-list">
				<?php foreach ( $suggestions as $suggestion ) : ?>
					<div class="improvement-item">
						<div class="improvement-icon">
							<svg
								width="16"
								height="16"
								viewBox="0 0 16 16"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<rect width="16" height="16" rx="8" fill="#D0D5DD" />
								<path
								d="M5 8.88L6.67619 10.8L10.8667 6"
								stroke="white"
								strokeWidth="1.2"
								strokeLinecap="round"
								strokeLinejoin="round"
								/>
							</svg>
						</div>
						<span class="improvement-text"><?php echo esc_html( $suggestion ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
		<?php
	}





	/**
	 * Get CSS class for score status
	 *
	 * @param int $score Overall score
	 * @return string CSS class
	 */
	private function get_score_status_class( $score ) {
		if ( $score >= 80 ) return 'green';
		if ( $score >= 50 ) return 'yellow';
		if ( $score >= 30 ) return 'orange';
		return 'red';
	}

	/**
	 * Get text for score status
	 *
	 * @param int $score Overall score
	 * @return string Status text
	 */
	private function get_score_status_text( $score ) {
		if ( $score >= 80 ) return __( 'Excellent', 'betterdocs' );
		if ( $score >= 60 ) return __( 'Good', 'betterdocs' );
		if ( $score >= 40 ) return __( 'Needs Improvement', 'betterdocs' );
		return __( 'Poor', 'betterdocs' );
	}

	/**
	 * AJAX handler for docs quality analysis
	 */
	public function ajax_analyze_article_quality() {
		// Verify nonce
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'betterdocs_quality_score_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'betterdocs' ) ] );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( empty( $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID', 'betterdocs' ) ] );
		}

		// SECURITY: Check if user can edit THIS SPECIFIC post (prevents IDOR)
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'betterdocs' ) ] );
		}

		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'docs' ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post or post type', 'betterdocs' ) ] );
		}

		// Check if current content is provided (from editor, may be unsaved)
		$current_content = isset( $_POST['current_content'] ) ? wp_kses_post( wp_unslash( $_POST['current_content'] ) ) : '';
		$current_title = isset( $_POST['current_title'] ) ? sanitize_text_field( wp_unslash( $_POST['current_title'] ) ) : '';
		
		// Use current content if provided, otherwise use saved post content
		if ( ! empty( $current_content ) ) {
			$content = $current_content;
			$title = ! empty( $current_title ) ? $current_title : $post->post_title;
		} else {
			$content = $post->post_content;
			$title = $post->post_title;
		}

		if ( empty( $content ) ) {
			wp_send_json_error( [ 'message' => __( 'Post content is empty. Please add content to your post and try again.', 'betterdocs' ) ] );
		}

		// Strip HTML tags and shortcodes for analysis
		$clean_content = wp_strip_all_tags( do_shortcode( $content ) );
		$clean_content = preg_replace( '/\s+/', ' ', trim( $clean_content ) );

		if ( strlen( $clean_content ) < 50 ) {
			wp_send_json_error( [ 'message' => __( 'Content is too short for meaningful analysis. Please add more content (at least 50 characters) and try again.', 'betterdocs' ) ] );
		}

		// Perform AI analysis
		$analysis_result = $this->ai_helper->analyze_article_quality( $clean_content, $title );

		if ( is_wp_error( $analysis_result ) ) {
			wp_send_json_error( [ 'message' => $analysis_result->get_error_message() ] );
		}

		// Save the analysis result
		$saved = $this->ai_helper->save_article_quality_score( $post_id, $analysis_result );

		if ( ! $saved ) {
			wp_send_json_error( [ 'message' => __( 'Failed to save analysis result', 'betterdocs' ) ] );
		}

		// Count a successful fresh analysis.
		AIUsage::record( 'quality_score', $post_id );

		// Return success with the analysis data
		wp_send_json_success( [
			'message' => __( 'Docs analyzed successfully', 'betterdocs' ),
			'data' => $analysis_result
		] );
	}

/**
 * AJAX handler for saving quality analysis results
 */
public function ajax_save_quality_analysis() {
	// Verify nonce
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'betterdocs_quality_score_nonce' ) ) {
		wp_send_json_error( __( 'Security check failed', 'betterdocs' ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
	// JSON payload validated/decoded below.
	$analysis_data = isset( $_POST['analysis_data'] ) ? wp_unslash( $_POST['analysis_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw JSON parsed via json_decode below.

	if ( ! $post_id || ! $analysis_data ) {
		wp_send_json_error( __( 'Invalid data provided', 'betterdocs' ) );
	}

	// SECURITY: Check if user can edit THIS SPECIFIC post (prevents IDOR)
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'betterdocs' ) );
	}

	// Decode the analysis data
	$decoded_data = json_decode( $analysis_data, true );
	if ( ! $decoded_data ) {
		wp_send_json_error( __( 'Invalid analysis data format', 'betterdocs' ) );
	}

	// Add timestamp and post modified date to the analysis data
	$post = get_post( $post_id );
	$decoded_data['analysis_timestamp'] = current_time( 'timestamp' );
	$decoded_data['post_modified'] = $post->post_modified;

	// Save the analysis data as post meta
	$meta_key = '_betterdocs_article_quality_analysis';
	$saved = update_post_meta( $post_id, $meta_key, $decoded_data );

	if ( $saved !== false ) {
		wp_send_json_success( [
			'message' => __( 'Analysis results saved successfully', 'betterdocs' ),
			'data' => $decoded_data
		] );
	} else {
		wp_send_json_error( __( 'Failed to save analysis results', 'betterdocs' ) );
	}
}

/**
 * AJAX handler for checking cached analysis results
 */
public function ajax_check_cached_analysis() {
	// Verify nonce
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'betterdocs_quality_score_nonce' ) ) {
		wp_send_json_error( __( 'Security check failed', 'betterdocs' ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;

	if ( ! $post_id ) {
		wp_send_json_error( __( 'Invalid post ID', 'betterdocs' ) );
	}

	// SECURITY: Check if user can edit THIS SPECIFIC post (prevents IDOR)
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'betterdocs' ) );
	}

	// Get the post and its last modified time
	$post = get_post( $post_id );
	if ( ! $post ) {
		wp_send_json_error( __( 'Post not found', 'betterdocs' ) );
	}

	// Get cached analysis data
	$cached_analysis = get_post_meta( $post_id, '_betterdocs_article_quality_analysis', true );

	// Check if we have cached data and if the post hasn't been modified since analysis
	if ( $cached_analysis &&
		 isset( $cached_analysis['post_modified'] ) &&
		 $cached_analysis['post_modified'] === $post->post_modified ) {

		// Return cached data
		wp_send_json_success( [
			'has_cache' => true,
			'message' => __( 'Using cached analysis results', 'betterdocs' ),
			'data' => $cached_analysis
		] );
	} else {
		// No cache or post has been updated
		wp_send_json_success( [
			'has_cache' => false,
			'message' => __( 'No valid cache found, new analysis required', 'betterdocs' )
		] );
	}
}

}
