<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
/**
 * Template part for displaying Article Summary
 *
 * @author  WPDeveloper
 * @package BetterDocs/Templates/Parts
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check if Article Summary feature is enabled (skip check in editor mode)
if ( ! isset( $is_editor_mode ) || ! $is_editor_mode ) {
	$article_summary = betterdocs()->article_summary;
	if ( ! $article_summary || ! $article_summary->is_enabled() ) {
		return;
	}
}

// Set default parameters
$post_id = isset( $post_id ) ? $post_id : get_the_ID();
$custom_title = isset( $custom_title ) ? $custom_title : '';
$show_title = isset( $show_title ) ? $show_title : true;
$widget_type = isset( $widget_type ) ? $widget_type : 'template';
$blockId = isset( $blockId ) ? $blockId : '';
$is_editor_mode = isset( $is_editor_mode ) ? $is_editor_mode : false;

// Check if the document has content (skip check in editor mode)
if ( ! $is_editor_mode ) {
	$post_content = get_post_field( 'post_content', $post_id );

	// Return early if no content exists
	if ( empty( $post_content ) || trim( wp_strip_all_tags( $post_content ) ) === '' ) {
		return;
	}
}

// Check if post is password protected and user hasn't provided correct password
// This applies to all contexts: templates, Gutenberg blocks, and Elementor widgets
if ( ! $is_editor_mode && post_password_required( $post_id ) ) {
	// Don't show article summary for password-protected posts until password is provided
	// The post_password_required() function checks both if post has password and if correct password cookie exists
	return;
}

// Generate wrapper classes - always start with base class
$wrapper_classes = [ 'betterdocs-article-summary' ];

// Add widget-specific classes
if ( $widget_type === 'blocks' && ! empty( $blockId ) ) {
	$wrapper_classes[] = $blockId;
}
if ( $widget_type === 'elementor' ) {
	$wrapper_classes[] = 'betterdocs-elementor';
}

// Add editor mode class if in editor
if ( $is_editor_mode ) {
	$wrapper_classes[] = 'betterdocs-editor-mode';
}

// Ensure we always have the base article summary class
if ( ! in_array( 'betterdocs-article-summary', $wrapper_classes ) ) {
	array_unshift( $wrapper_classes, 'betterdocs-article-summary' );
}

// Create separate collapsible arrow SVG icons
$arrow_right = '<svg class="angle-icon angle-right" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M6 9L12 15L18 9" stroke="#98A2B3" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';

$arrow_down = '<svg class="angle-icon angle-down" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M18 15L12 9L6 15" stroke="#98A2B3" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';

// Determine title text
$title_text = ! empty( $custom_title ) ? $custom_title : __( 'Doc Summary', 'betterdocs' );

// Generate wrapper attributes - always use our classes for consistency
$wrapper_attr = 'class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';

// If custom wrapper attributes are provided (from blocks/elementor), merge them
if ( isset( $custom_wrapper_attr ) && ! empty( $custom_wrapper_attr ) && is_string( $custom_wrapper_attr ) ) {
	// Extract any additional attributes but keep our classes
	if ( strpos( $custom_wrapper_attr, 'style=' ) !== false ) {
		preg_match( '/style="([^"]*)"/', $custom_wrapper_attr, $style_matches );
		if ( ! empty( $style_matches[1] ) ) {
			$wrapper_attr .= ' style="' . esc_attr( $style_matches[1] ) . '"';
		}
	}
}

// Ensure style variables are strings
$title_style = isset( $title_style ) && is_string( $title_style ) ? $title_style : '';
$content_style = isset( $content_style ) && is_string( $content_style ) ? $content_style : '';
$icon_style = isset( $icon_style ) && is_string( $icon_style ) ? $icon_style : '';
$loading_style = isset( $loading_style ) && is_string( $loading_style ) ? $loading_style : '';
?>

<div <?php echo $wrapper_attr; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> id="betterdocs-article-summary" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-post-type="<?php echo esc_attr( get_post_type( $post_id ) ); ?>">
	<?php if ( $show_title ) : ?>
		<div class="betterdocs-summary-header" id="betterdocs-summary-toggle">
			<span class="betterdocs-summary-title" <?php echo ! empty( $title_style ) ? 'style="' . esc_attr( $title_style ) . '"' : ''; ?>>
				<img src="<?php echo esc_url( betterdocs()->assets->icon( 'ai-summary-icon.svg' ) ); ?>" alt="<?php echo esc_attr__( 'AI Doc Summarizer', 'betterdocs' ); ?>" />
				<?php echo esc_html( $title_text ); ?>
				<span class="betterdocs-summary-arrow" <?php echo ! empty( $icon_style ) ? 'style="' . esc_attr( $icon_style ) . '"' : ''; ?>>
					<?php echo $arrow_right; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG icons cannot be escaped ?>
					<?php echo $arrow_down; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG icons cannot be escaped ?>
				</span>
			</span>
		</div>
	<?php endif; ?>

	<div class="betterdocs-summary-content" id="betterdocs-summary-content" style="display: <?php echo $is_editor_mode ? 'block' : 'none'; ?>;">
		<div class="betterdocs-summary-loading" id="betterdocs-summary-loading" style="display: none;" <?php echo ! empty( $loading_style ) ? 'data-style="' . esc_attr( $loading_style ) . '"' : ''; ?>>
			<img src="<?php echo esc_url( betterdocs()->assets->icon( 'thinking-spinner.gif' ) ); ?>" alt="<?php echo esc_attr__( 'AI Doc Summarizer Thinking', 'betterdocs' ); ?>" />
			<span class="betterdocs-thinking-text">
				<?php echo esc_html__( 'Thinking', 'betterdocs' ); ?><span class="betterdocs-thinking-dots"></span>
			</span>
		</div>
		<div class="betterdocs-summary-text" id="betterdocs-summary-text" style="display: <?php echo $is_editor_mode ? 'block' : 'none'; ?>;" <?php echo ! empty( $content_style ) ? 'data-style="' . esc_attr( $content_style ) . '"' : ''; ?>>
			<?php if ( $is_editor_mode ) : ?>
				<div class="betterdocs-summary-preview">
					<p><?php echo esc_html__( 'This is a preview of the AI Doc Summarizer. The actual AI-generated summary will appear here when viewed on the frontend.', 'betterdocs' ); ?></p>
					<p><?php echo esc_html__( 'The summary will be automatically generated based on the article content using OpenAI technology.', 'betterdocs' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( $is_editor_mode ) : ?>
<style>
	/* Always show Article Summary as expanded in editor mode */
	.betterdocs-article-summary.betterdocs-editor-mode .betterdocs-summary-content {
		display: block !important;
	}
	.betterdocs-article-summary.betterdocs-editor-mode .angle-icon.angle-right {
		display: none !important;
	}
	.betterdocs-article-summary.betterdocs-editor-mode .angle-icon.angle-down {
		display: block !important;
	}
</style>
<?php endif; ?>

<style>
/* Animated thinking dots */
.betterdocs-thinking-dots {
	display: inline-block;
	min-width: 20px;
	text-align: left;
}

.betterdocs-thinking-dots::after {
	content: '';
	animation: betterdocs-thinking-dots 1.5s infinite;
}

@keyframes betterdocs-thinking-dots {
	0% { content: ''; }
	16.66% { content: '.'; }
	33.33% { content: '..'; }
	50% { content: '...'; }
	66.66% { content: '....'; }
	83.33% { content: '.....'; }
	100% { content: ''; }
}

/* Ensure smooth animation */
.betterdocs-summary-loading {
	display: flex;
	align-items: center;
	gap: 1px;
}

.betterdocs-thinking-text {
	display: flex;
	align-items: baseline;
}
</style>
