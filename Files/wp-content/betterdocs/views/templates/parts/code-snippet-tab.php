<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Template part for BetterDocs Code Snippet Tab.
 *
 * Reuses the Code Snippet shell (wrapper / header / panels / copy button / dark
 * + light themes / line numbers). Instead of a language dropdown it renders a
 * row of HTTP-status tabs — one per response — each switching a code panel.
 *
 * @var array  $responses  [ { status, language, code } ] — one tab per entry.
 * @var bool   $show_copy_button
 * @var bool   $show_header
 * @var bool   $show_line_numbers
 * @var string $theme
 * @var string $block_id (optional)
 * @var string $widget_type (optional)
 */

use WPDeveloper\BetterDocs\Utils\Helper;

if ( empty( $responses ) || ! is_array( $responses ) ) {
    return;
}

// Drop entries with no code so empty tabs never render.
$responses = array_values( array_filter( $responses, function ( $r ) {
    return is_array( $r ) && '' !== (string) ( isset( $r['code'] ) ? $r['code'] : '' );
} ) );

if ( empty( $responses ) ) {
    return;
}

$snippet_id          = isset( $block_id ) && $block_id ? $block_id : 'betterdocs-code-snippet-tab-' . wp_rand( 1000, 9999 );
$show_copy_button    = isset( $show_copy_button ) ? $show_copy_button : true;
$show_header         = isset( $show_header ) ? $show_header : true;
$show_line_numbers   = isset( $show_line_numbers ) ? $show_line_numbers : false;
$theme               = sanitize_text_field( isset( $theme ) ? $theme : 'light' );

$active        = $responses[0];
$active_lang   = sanitize_text_field( isset( $active['language'] ) ? $active['language'] : 'json' );

// First digit of the status → the `--Nxx` colour bucket (2xx green, 4xx red…).
$status_class = function ( $status ) {
    $status = trim( (string) $status );
    $digit  = ( '' !== $status && ctype_digit( $status[0] ) ) ? $status[0] : '';
    return '' !== $digit ? 'betterdocs-api-response--' . $digit . 'xx' : '';
};

wp_enqueue_script( 'betterdocs-code-snippet-tab' );
wp_enqueue_style( 'betterdocs-code-snippet-tab' );
?>

<div class="betterdocs-code-snippet-wrapper betterdocs-code-snippet-tab theme-<?php echo esc_attr( $theme ); ?> <?php echo esc_attr( $snippet_id ); ?> is-multi-tab"
     id="<?php echo esc_attr( $snippet_id ); ?>"
     data-language="<?php echo esc_attr( $active_lang ); ?>"
     data-copy-button="<?php echo $show_copy_button ? 'true' : 'false'; ?>">

    <?php if ( $show_header ) : ?>
        <div class="betterdocs-code-snippet-header betterdocs-file-preview-header betterdocs-code-snippet-tab-header">
            <div class="betterdocs-file-preview-left">
                <div class="betterdocs-code-snippet-tabs" role="tablist">
                    <?php foreach ( $responses as $i => $response ) : ?>
                        <?php
                        $status  = isset( $response['status'] ) ? (string) $response['status'] : '';
                        $s_class = $status_class( $status );
                        ?>
                        <button type="button"
                                class="betterdocs-code-snippet-tab-item<?php echo 0 === $i ? ' is-active' : ''; ?> <?php echo esc_attr( $s_class ); ?>"
                                role="tab"
                                aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
                                data-tab-index="<?php echo esc_attr( $i ); ?>">
                            <span class="betterdocs-code-snippet-tab-dot <?php echo esc_attr( $s_class ); ?>"></span>
                            <span class="betterdocs-code-snippet-tab-label"><?php echo esc_html( '' !== $status ? $status : ( $i + 1 ) ); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="betterdocs-file-preview-right">
                <?php if ( $show_copy_button ) : ?>
                    <?php Helper::code_snippet_copy_button(); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="betterdocs-code-snippet-content">
        <?php foreach ( $responses as $i => $response ) : ?>
            <?php
            $v_lang = sanitize_text_field( isset( $response['language'] ) ? $response['language'] : 'json' );
            $v_code = (string) ( isset( $response['code'] ) ? $response['code'] : '' );
            ?>
            <div class="betterdocs-code-snippet-panel<?php echo 0 === $i ? ' is-active' : ''; ?>"
                 data-tab-index="<?php echo esc_attr( $i ); ?>"
                 <?php echo 0 === $i ? '' : 'hidden'; ?>>
                <?php if ( $show_line_numbers ) : ?>
                    <div class="betterdocs-code-snippet-line-numbers" aria-hidden="true">
                        <?php foreach ( range( 1, max( 1, substr_count( $v_code, "\n" ) + 1 ) ) as $line_num ) : ?>
                            <div class="line-number"><?php echo esc_html( $line_num ); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <pre class="betterdocs-code-snippet-code language-<?php echo esc_attr( $v_lang ); ?>"><code><?php echo esc_html( $v_code ); ?></code></pre>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ( $show_copy_button ) : ?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    const snippet = document.getElementById('<?php echo esc_js( $snippet_id ); ?>');
    if (snippet && window.BetterDocsCodeSnippetTab) {
        window.BetterDocsCodeSnippetTab.initCopyButton(snippet);
    }
});
</script>
<?php endif; ?>
