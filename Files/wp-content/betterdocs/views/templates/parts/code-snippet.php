<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Template part for BetterDocs Code Snippet
 *
 * @var string $code_content
 * @var string $language
 * @var bool $show_language_label
 * @var bool $show_copy_button
 * @var bool $show_header
 * @var bool $show_line_numbers
 * @var string $theme
 * @var string $block_id (optional)
 * @var string $widget_type (optional)
 * @var string $file_name (optional)
 * @var bool $show_traffic_lights (optional)
 * @var bool $show_file_icon (optional)
 * @var string $file_icon (optional)
 * @var array $code_variants (optional) [ { language, code, file_name } ] — when
 *            more than one, the header renders a language dropdown.
 */

if ( empty( $code_content ) ) {
    return;
}

// Generate unique ID for this code snippet
$snippet_id = isset( $block_id ) ? $block_id : 'betterdocs-code-snippet-' . wp_rand( 1000, 9999 );

// Set defaults for optional parameters
// Gates the language name on the multi-language switcher. The Elementor switch
// and the block's showLanguageLabel attribute both fed this variable and nothing
// ever read it, so the control was a no-op in both editors. Default stays true,
// so existing content renders exactly as before.
$show_language_label = isset( $show_language_label ) ? $show_language_label : true;
$show_copy_button = isset( $show_copy_button ) ? $show_copy_button : true;
$show_header = isset( $show_header ) ? $show_header : true;
$file_name = isset( $file_name ) ? $file_name : '';
$show_traffic_lights = isset( $show_traffic_lights ) ? $show_traffic_lights : true;
$show_file_icon = isset( $show_file_icon ) ? $show_file_icon : true;
$file_icon = isset( $file_icon ) ? $file_icon : '';

// Sanitize inputs (preserve code content as-is for display purposes)
$language = sanitize_text_field( $language );
$theme = sanitize_text_field( $theme );
$file_name = sanitize_text_field( $file_name );
$file_icon = esc_url( $file_icon );

// Import Helper class for file icon functionality
use WPDeveloper\BetterDocs\Utils\Helper;

// Build the language list. Callers that don't pass code_variants (Elementor /
// shortcode) fall back to the single primary language/code — identical to the
// previous behaviour.
$variants = ( isset( $code_variants ) && is_array( $code_variants ) && ! empty( $code_variants ) )
    ? $code_variants
    : [
        [
            'language'  => $language,
            'code'      => $code_content,
            'file_name' => $file_name
        ]
    ];

// Normalize every entry up front. `code_variants` arrives from block attributes
// (and from Pro's API-docs generator), so a caller can legitimately hand over an
// entry missing `language` or `code` — reading them directly emitted PHP 8
// warnings mid-render. The tab template already guards this way.
$variants = array_values(
    array_filter(
        array_map(
            function ( $variant ) {
                if ( ! is_array( $variant ) ) {
                    return null;
                }

                return [
                    'language'  => isset( $variant['language'] ) ? (string) $variant['language'] : '',
                    'code'      => isset( $variant['code'] ) ? (string) $variant['code'] : '',
                    'file_name' => isset( $variant['file_name'] ) ? (string) $variant['file_name'] : ''
                ];
            },
            $variants
        )
    )
);

// Everything below indexes $variants[0]; keep the single-entry fallback so an
// empty or fully-invalid list still renders rather than fatalling.
if ( empty( $variants ) ) {
    $variants = [
        [
            'language'  => $language,
            'code'      => (string) $code_content,
            'file_name' => $file_name
        ]
    ];
}

$is_multi     = count( $variants ) > 1;
$active       = $variants[0];
$active_lang  = sanitize_text_field( $active['language'] );

// Dropdown/tab label for an entry: a custom filename overrides (so the API-docs
// cURL sample reads "cURL", not "Bash"), otherwise the language's display name.
// The block default filename ("filename.js") never wins.
$lang_label = function ( $variant ) {
    $file = isset( $variant['file_name'] ) ? (string) $variant['file_name'] : '';
    if ( '' !== $file && 'filename.js' !== $file ) {
        return $file;
    }
    return Helper::get_language_label( sanitize_text_field( isset( $variant['language'] ) ? $variant['language'] : '' ) );
};

// Enqueue necessary assets
wp_enqueue_script( 'betterdocs-code-snippet' );
wp_enqueue_style( 'betterdocs-code-snippet' );
?>

<div class="betterdocs-code-snippet-wrapper theme-<?php echo esc_attr( $theme ); ?> <?php echo esc_attr( $snippet_id ); ?><?php echo $is_multi ? ' is-multi-lang' : ''; ?>"
     id="<?php echo esc_attr( $snippet_id ); ?>"
     data-language="<?php echo esc_attr( $active_lang ); ?>"
     <?php echo $is_multi ? 'data-multi-lang="true"' : ''; ?>
     data-copy-button="<?php echo $show_copy_button ? 'true' : 'false'; ?>">

    <?php if ( $show_header ) : ?>
        <div class="betterdocs-code-snippet-header betterdocs-file-preview-header">
        <div class="betterdocs-file-preview-left">
            <?php if ( $show_traffic_lights ) : ?>
                <div class="betterdocs-traffic-lights">
                    <span class="traffic-light traffic-light-red"></span>
                    <span class="traffic-light traffic-light-yellow"></span>
                    <span class="traffic-light traffic-light-green"></span>
                </div>
            <?php endif; ?>

            <?php if ( ! $is_multi ) : ?>
                <div class="betterdocs-file-info">
                    <?php if ( $show_file_icon ) : ?>
                        <div class="betterdocs-file-icon">
                            <?php if ( ! empty( $file_icon ) ) : ?>
                                <img src="<?php echo esc_url( $file_icon ); ?>" alt="<?php esc_attr_e( 'File icon', 'betterdocs' ); ?>" />
                            <?php else : ?>
                                <span class="betterdocs-file-icon-emoji"><?php echo esc_html( Helper::get_file_icon_by_language( $active_lang ) ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $file_name ) ) : ?>
                        <div class="betterdocs-file-name">
                            <span class="file-name-text"><?php echo esc_html( $file_name ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="betterdocs-file-preview-right">
            <?php if ( $is_multi ) : ?>
                <div class="betterdocs-code-lang-dropdown">
                    <button type="button" class="betterdocs-code-lang-toggle" aria-haspopup="listbox" aria-expanded="false">
                        <span class="betterdocs-code-lang-current">
                            <?php if ( $show_file_icon ) : ?>
                                <span class="betterdocs-file-icon-emoji"><?php echo esc_html( Helper::get_file_icon_by_language( $active_lang ) ); ?></span>
                            <?php endif; ?>
                            <?php if ( $show_language_label ) : ?>
                                <span class="betterdocs-code-lang-label"><?php echo esc_html( $lang_label( $active ) ); ?></span>
                            <?php endif; ?>
                        </span>
                        <svg class="betterdocs-code-lang-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <ul class="betterdocs-code-lang-menu" role="listbox">
                        <?php foreach ( $variants as $i => $variant ) : ?>
                            <?php $v_lang = sanitize_text_field( $variant['language'] ); ?>
                            <li class="betterdocs-code-lang-option<?php echo 0 === $i ? ' is-active' : ''; ?>"
                                role="option"
                                data-lang-index="<?php echo esc_attr( $i ); ?>">
                                <?php if ( $show_file_icon ) : ?>
                                    <span class="betterdocs-file-icon-emoji"><?php echo esc_html( Helper::get_file_icon_by_language( $v_lang ) ); ?></span>
                                <?php endif; ?>
                                <span class="betterdocs-code-lang-label"><?php echo esc_html( $lang_label( $variant ) ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( $show_copy_button ) : ?>
                <?php Helper::code_snippet_copy_button(); ?>
            <?php endif; ?>
        </div>
        </div>
    <?php endif; ?>

    <div class="betterdocs-code-snippet-content">
        <?php foreach ( $variants as $i => $variant ) : ?>
            <?php
            $v_lang = sanitize_text_field( $variant['language'] );
            $v_code = (string) $variant['code'];
            ?>
            <div class="betterdocs-code-snippet-panel<?php echo 0 === $i ? ' is-active' : ''; ?>"
                 data-lang-index="<?php echo esc_attr( $i ); ?>"
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

<?php
// Add inline script for copy functionality if copy button is enabled
if ( $show_copy_button ) :
?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize copy functionality for this specific snippet
    const snippet = document.getElementById('<?php echo esc_js( $snippet_id ); ?>');
    if (snippet && window.BetterDocsCodeSnippet) {
        window.BetterDocsCodeSnippet.initCopyButton(snippet);
    }
});
</script>
<?php endif; ?>
