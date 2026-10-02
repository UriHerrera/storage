<?php

namespace WPDeveloper\BetterDocs\FrontEnd;

/**
 * Print template — the logo header and page footer shown when a visitor prints
 * (or saves as PDF) a page.
 *
 * There are two ways a doc gets printed, and this class feeds both from the
 * same settings so they render identically:
 *
 *  1. The browser's own Print command (Ctrl/Cmd + P) on the live page. Handled
 *     here: the markup is emitted on `wp_footer` and pushed into the `@page`
 *     margin box with `position: fixed`, which browsers repeat on every printed
 *     page. This is the only route available on pages that carry no BetterDocs
 *     print button — the API Reference pages, FSE templates, archives, and any
 *     other page a reader decides to print.
 *  2. BetterDocs' own print icon, which opens a popup holding just the doc
 *     content. That route builds its own header/footer in `betterdocs.js` from
 *     `betterdocsConfig.print`, using a table `<tfoot>` — it owns the whole
 *     document there, and `<tfoot>` reserves space more reliably than a fixed
 *     element when the footer text wraps to several lines.
 *
 * The popup appends its own `@page` rule *after* the styles it copies from this
 * page, so route 2's margins keep winning inside the popup and the two
 * mechanisms never fight.
 *
 * @since 4.9.1
 */
class PrintTemplate {
	/**
	 * Vertical space reserved inside the printed page margins, in millimetres.
	 * Keep these in sync with the offsets in {@see self::styles()}.
	 */
	const HEADER_SPACE = 22;
	const FOOTER_SPACE = 18;
	const EDGE_SPACE   = 12;

	public function __construct() {
		add_action( 'wp_footer', [ $this, 'render' ], 99 );
	}

	/**
	 * Whether the print template should be emitted on the current request.
	 *
	 * Defaults to every front-end page — a reader can print any page, and the
	 * whole point of this route is to cover the ones without a print button.
	 * Filter `betterdocs_print_template_enabled` to narrow it, e.g. to docs
	 * only:
	 *
	 *     add_filter( 'betterdocs_print_template_enabled', function ( $on ) {
	 *         return $on && ( is_singular( 'docs' ) || is_post_type_archive( 'docs' ) );
	 *     } );
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = ! is_admin() && ! is_feed() && ! is_embed();

		return (bool) apply_filters( 'betterdocs_print_template_enabled', $enabled );
	}

	/**
	 * Resolved logo URL for the printed page, or '' when disabled/unavailable.
	 *
	 * Resolution order: the `print_logo` setting, the theme's Custom Logo, the
	 * Elementor kit's site logo (themes without custom-logo support keep theirs
	 * there), then the Site Icon.
	 *
	 * @return string
	 */
	public static function get_logo() {
		$enabled = apply_filters(
			'betterdocs_print_enable_logo',
			betterdocs()->settings->get( 'print_enable_logo', false )
		);

		if ( ! $enabled ) {
			return '';
		}

		$logo = '';

		$setting = betterdocs()->settings->get( 'print_logo' );
		if ( ! empty( $setting['url'] ) ) {
			$logo = (string) $setting['url'];
		}

		if ( '' === $logo ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );

			if ( ! $logo_id ) {
				$kit_id = (int) get_option( 'elementor_active_kit' );
				if ( $kit_id ) {
					$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
					if ( ! empty( $kit_settings['site_logo']['id'] ) ) {
						$logo_id = (int) $kit_settings['site_logo']['id'];
					}
				}
			}

			if ( $logo_id ) {
				$logo = (string) wp_get_attachment_image_url( $logo_id, 'medium' );
			}
		}

		if ( '' === $logo && has_site_icon() ) {
			$logo = get_site_icon_url( 512 );
		}

		return (string) apply_filters( 'betterdocs_print_logo', $logo );
	}

	/**
	 * Resolved footer markup for the printed page, or '' when disabled.
	 *
	 * Falls back to "© <year> <site name>" when the setting is left empty.
	 *
	 * @return string
	 */
	public static function get_footer() {
		$enabled = apply_filters(
			'betterdocs_print_enable_footer',
			betterdocs()->settings->get( 'print_enable_footer', false )
		);

		if ( ! $enabled ) {
			return '';
		}

		$footer = (string) betterdocs()->settings->get( 'print_footer_text', '' );

		if ( '' === trim( $footer ) ) {
			$footer = sprintf( '&copy; %1$s %2$s', gmdate( 'Y' ), get_bloginfo( 'name' ) );
		}

		return (string) apply_filters( 'betterdocs_print_footer', $footer );
	}

	/**
	 * Fallback print CSS, used when the table wrap below never runs.
	 *
	 * `position: fixed` is what makes a browser repeat an element on every
	 * printed page, and the negative offsets lift the boxes out of the content
	 * flow into the margin `@page` reserves for them.
	 *
	 * This is a fallback and not the primary mechanism because Chrome drops one
	 * instance at each end: measured over an 8-page print, the header rendered
	 * on pages 1-7 and the footer on pages 2-8 — the last page lost its logo and
	 * the first page lost its footer. Laying the boxes out inside the page area
	 * instead (`top:0`/`bottom:0`) does repeat on every page, but then the text
	 * runs underneath them. `transform: translateY()` in place of the negative
	 * offsets behaves identically to the negative offsets. The correct CSS
	 * answer — `@page` margin boxes such as `@top-center` — is implemented by no
	 * browser, so {@see self::script()} does the job properly and this rule set
	 * only covers the case where its events never fire.
	 *
	 * @param bool $has_logo
	 * @param bool $has_footer
	 * @return string
	 */
	protected static function styles( $has_logo, $has_footer ) {
		$top    = $has_logo ? self::HEADER_SPACE : self::EDGE_SPACE;
		$bottom = $has_footer ? self::FOOTER_SPACE : self::EDGE_SPACE;

		$css = '.betterdocs-print-running-header,.betterdocs-print-running-footer{display:none}'
			. '@media print{'
			. sprintf( '@page{margin:%1$dmm %2$dmm %3$dmm}', $top, self::EDGE_SPACE, $bottom )
			. '.betterdocs-print-running-header,.betterdocs-print-running-footer{'
			. 'display:block;position:fixed;left:0;right:0;margin:0;padding:0;'
			. 'text-align:center;background:none;border:0}';

		if ( $has_logo ) {
			$css .= '.betterdocs-print-running-header{top:-16mm;height:14mm;line-height:0}'
				. '.betterdocs-print-running-header img{'
				. 'max-height:14mm;max-width:60%;width:auto;height:auto;display:inline-block}';
		}

		if ( $has_footer ) {
			$css .= '.betterdocs-print-running-footer{'
				. 'bottom:-13mm;height:11mm;overflow:hidden;'
				. 'font-size:9pt;line-height:1.35;color:#555}'
				. '.betterdocs-print-running-footer p{margin:0;font-size:inherit;line-height:inherit}';
		}

		return $css . '}';
	}

	/**
	 * Print CSS for the wrapped (table) layout.
	 *
	 * Emitted inert as `media="not all"`; {@see self::script()} flips it to
	 * `print` once it has actually built the table, and back again afterwards.
	 * Because this sheet comes after the fallback one, its `@page` margin and
	 * its `position: static` reset both win while the wrap is in place.
	 *
	 * Browsers repeat `table-header-group` / `table-footer-group` on every page
	 * *and* keep the flowing content clear of them, which is the behaviour the
	 * fixed-position fallback cannot deliver.
	 *
	 * @param bool $has_logo
	 * @param bool $has_footer
	 * @return string
	 */
	protected static function wrapped_styles( $has_logo, $has_footer ) {
		$css = sprintf( '@page{margin:%1$dmm}', self::EDGE_SPACE )
			. '.betterdocs-print-running-header,.betterdocs-print-running-footer{'
			. 'display:block;position:static;top:auto;bottom:auto;left:auto;right:auto;'
			. 'height:auto;transform:none;text-align:center}'
			. '#betterdocs-print-layout{width:100%;border-collapse:collapse;border:0}'
			. '#betterdocs-print-layout>thead>tr>td,'
			. '#betterdocs-print-layout>tfoot>tr>td,'
			. '#betterdocs-print-layout>tbody>tr>td{padding:0;border:0}'
			. '#betterdocs-print-layout>thead{display:table-header-group}'
			. '#betterdocs-print-layout>tfoot{display:table-footer-group}';

		if ( $has_logo ) {
			$css .= '.betterdocs-print-running-header{padding:0 0 6mm;line-height:0}'
				. '.betterdocs-print-running-header img{'
				. 'max-height:16mm;max-width:60%;width:auto;height:auto;display:inline-block}';
		}

		if ( $has_footer ) {
			$css .= '.betterdocs-print-running-footer{'
				. 'padding:5mm 0 0;font-size:9pt;line-height:1.35;color:#555}'
				. '.betterdocs-print-running-footer p{margin:0;font-size:inherit;line-height:inherit}';
		}

		return $css;
	}

	/**
	 * The wrap/unwrap helper.
	 *
	 * Inlined rather than shipped as a bundle on purpose: it has to be present
	 * on every front-end page, and a separate request site-wide for a
	 * print-only helper of this size costs more than it saves.
	 *
	 * On `beforeprint` it moves the page's rendered content into a single table
	 * cell, with the logo as `<thead>` and the footer as `<tfoot>`, then
	 * activates {@see self::wrapped_styles()}. On `afterprint` it puts
	 * everything back. `matchMedia('print')` covers Safari, which has no
	 * `beforeprint`; the `state` guard makes a double fire harmless.
	 *
	 * @return string
	 */
	protected static function script() {
		return <<<'JS'
(function(){
"use strict";
var SKIP={SCRIPT:1,STYLE:1,LINK:1,TEMPLATE:1,NOSCRIPT:1},state=null;
function cell(parent,tag){
	var section=document.createElement(tag),row=document.createElement("tr"),td=document.createElement("td");
	row.appendChild(td);section.appendChild(row);parent.appendChild(section);return td;
}
function wrap(){
	if(state||!document.body){return;}
	var header=document.querySelector(".betterdocs-print-running-header");
	var footer=document.querySelector(".betterdocs-print-running-footer");
	if(!header&&!footer){return;}
	var body=document.body,content=[],node=body.firstChild;
	while(node){
		if(node!==header&&node!==footer&&!(node.nodeType===1&&SKIP[node.tagName])){content.push(node);}
		node=node.nextSibling;
	}
	if(!content.length){return;}
	var table=document.createElement("table");
	table.id="betterdocs-print-layout";
	if(header){cell(table,"thead").appendChild(header);}
	if(footer){cell(table,"tfoot").appendChild(footer);}
	var host=cell(table,"tbody");
	body.insertBefore(table,content[0]);
	for(var i=0;i<content.length;i++){host.appendChild(content[i]);}
	var sheet=document.getElementById("betterdocs-print-template-wrapped");
	if(sheet){sheet.media="print";}
	state={table:table,host:host,header:header,footer:footer,sheet:sheet};
}
function unwrap(){
	if(!state){return;}
	var body=document.body,table=state.table;
	while(state.host.firstChild){body.insertBefore(state.host.firstChild,table);}
	if(state.header){body.insertBefore(state.header,table);}
	if(state.footer){body.insertBefore(state.footer,table);}
	body.removeChild(table);
	if(state.sheet){state.sheet.media="not all";}
	state=null;
}
window.addEventListener("beforeprint",wrap);
window.addEventListener("afterprint",unwrap);
if(window.matchMedia){
	var mq=window.matchMedia("print"),onChange=function(e){e.matches?wrap():unwrap();};
	if(mq.addEventListener){mq.addEventListener("change",onChange);}
	else if(mq.addListener){mq.addListener(onChange);}
}
})();
JS;
	}

	/**
	 * Emit the running header/footer, their print styles, and the wrap helper
	 * just before </body>.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$logo   = self::get_logo();
		$footer = self::get_footer();

		if ( '' === $logo && '' === $footer ) {
			return;
		}

		$has_logo   = '' !== $logo;
		$has_footer = '' !== $footer;

		if ( $has_logo ) {
			printf(
				'<div class="betterdocs-print-running-header" aria-hidden="true"><img src="%s" alt="" /></div>',
				esc_url( $logo )
			);
		}

		if ( $has_footer ) {
			printf(
				'<div class="betterdocs-print-running-footer" aria-hidden="true">%s</div>',
				wp_kses_post( $footer )
			);
		}

		printf(
			'<style id="betterdocs-print-template">%s</style>',
			self::styles( $has_logo, $has_footer )
		);

		printf(
			'<style id="betterdocs-print-template-wrapped" media="not all">%s</style>',
			self::wrapped_styles( $has_logo, $has_footer )
		);

		wp_print_inline_script_tag( self::script(), [ 'id' => 'betterdocs-print-template-js' ] );
	}
}
