<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract().

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * API Documentation — the endpoint "Try-it" banner (method + path + button).
 *
 * Rendered by the dynamic `betterdocs/api-tryit` block (and the materializer's
 * fallback). Customize by overriding this template, filtering
 * `betterdocs_api_tryit_banner`, or per reference in the API Docs drawer.
 *
 * @var string $method       Lowercase HTTP method.
 * @var string $path         Endpoint path.
 * @var int    $ref_id       Owning reference ID (drawer needs it).
 * @var string $label        Try-it button label.
 * @var bool   $show_button  Whether to render the Try-it button.
 * @var string $accent_style Brand accent custom properties ('' = stock palette).
 * @var bool   $use_proxy    Route this reference's requests through the proxy.
 *
 * When the proxy is on, the button also carries a short-lived per-reference
 * grant (`data-proxy-token`). The proxy route is unauthenticated by necessity —
 * readers of public docs have no session — so this is what stops it being a
 * server-side request primitive for anyone who knows the URL. It is emitted
 * only here, where the playground is actually offered.
 */

$method       = strtolower( (string) ( isset( $method ) ? $method : 'get' ) );
$path         = (string) ( isset( $path ) ? $path : '' );
$ref_id       = (int) ( isset( $ref_id ) ? $ref_id : 0 );
$label        = (string) ( isset( $label ) && '' !== $label ? $label : __( 'Try it', 'betterdocs-pro' ) );
$accent_style = (string) ( isset( $accent_style ) ? $accent_style : '' );
$use_proxy    = ! empty( $use_proxy );
?>
<div class="betterdocs-api-endpoint-banner betterdocs-api-endpoint-doc"<?php echo '' !== $accent_style ? ' style="' . esc_attr( $accent_style ) . '"' : ''; ?>><span class="betterdocs-api-method betterdocs-api-method--<?php echo esc_attr( $method ); ?>"><?php echo esc_html( strtoupper( $method ) ); ?></span><code class="betterdocs-api-endpoint-path"><?php echo esc_html( $path ); ?></code><?php if ( ! empty( $show_button ) && $ref_id ) : ?><button type="button" class="betterdocs-api-tryit" data-ref-id="<?php echo esc_attr( $ref_id ); ?>" data-method="<?php echo esc_attr( $method ); ?>" data-path="<?php echo esc_attr( $path ); ?>"<?php echo $use_proxy ? ' data-proxy="1" data-proxy-token="' . esc_attr( \WPDeveloper\BetterDocsPro\Core\ApiDocs\ProxyAllowlist::token( $ref_id ) ) . '"' : ''; ?>><?php echo esc_html( $label ); ?></button><?php endif; ?></div>
