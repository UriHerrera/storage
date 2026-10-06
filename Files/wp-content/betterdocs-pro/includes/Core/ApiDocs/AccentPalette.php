<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Documentation — the brand accent palette for the Try-it and Send buttons.
 *
 * Two admin-facing colours: the brand accent and the text colour used on top of
 * it. They are set per API reference in the API Docs Create/Edit drawer (the
 * default for everything that reference generates) and per block in the Try-it
 * block's Style panel (an override for one banner). Everything else — the
 * button gradient's two stops, the hover pair and the hover glow — is derived
 * here, so an author picks one colour and both buttons follow.
 *
 * Scope is deliberately narrow: only the banner's **Try-it button** and the
 * modal's **Send button** are brand-coloured. Method badges, status colours and
 * accent links stay on the stock palette.
 *
 * The palette is emitted as CSS custom properties in a `style` attribute on the
 * banner element rather than as a document-level `:root` block. That is
 * deliberate: branding is per reference, and a single page can legitimately show
 * banners from two different references (an embed, a hand-placed block), which a
 * global `:root` override could not express. The Try-it modal — which mounts at
 * the document root, outside any banner — copies the vars off the button that
 * opened it (see react-src/public/api-reference.js).
 *
 * The SCSS declares the same variables with the stock BetterDocs green, so a
 * reference with no accent set emits no style attribute at all and looks exactly
 * as it always has. Because the palette is read at render time it applies to
 * every endpoint doc of the reference instantly, with no re-materialization.
 *
 * @see partials/_api-endpoint-doc.scss for the variable defaults.
 */
final class AccentPalette {

	/**
	 * Stock BetterDocs green — must match `--betterdocs-api-accent-strong` in
	 * partials/_api-endpoint-doc.scss.
	 */
	const DEFAULT_ACCENT = '#00b884';

	/**
	 * Stock button text colour — matches `--betterdocs-api-accent-contrast`.
	 */
	const DEFAULT_CONTRAST = '#ffffff';

	/**
	 * The colours an API reference hands to the blocks it generates.
	 *
	 * @param int $ref_id API reference post ID.
	 * @return array{accent:string, contrast:string} Normalized hexes, '' when unset.
	 */
	public static function reference_colors( $ref_id ) {
		$ref_id = (int) $ref_id;

		if ( ! $ref_id ) {
			return array(
				'accent'   => '',
				'contrast' => ''
			);
		}

		$accent   = self::normalize( (string) get_post_meta( $ref_id, '_bd_api_accent_color', true ) );
		$contrast = self::normalize( (string) get_post_meta( $ref_id, '_bd_api_accent_text_color', true ) );

		return array(
			'accent'   => null === $accent ? '' : $accent,
			'contrast' => null === $contrast ? '' : $contrast
		);
	}

	/**
	 * Build the palette for one API reference, ready for a `style` attribute.
	 *
	 * @param int $ref_id API reference post ID.
	 * @return string e.g. `--betterdocs-api-accent:#7a3bdc;…`, or '' for stock.
	 */
	public static function for_reference( $ref_id ) {
		$colors = self::reference_colors( $ref_id );

		return self::style( $colors['accent'], $colors['contrast'] );
	}

	/**
	 * Build the palette for one block instance.
	 *
	 * The block's own Style panel wins; whatever it leaves empty falls back to
	 * the owning reference's branding, so a doc generated before a colour was
	 * picked still follows the reference.
	 *
	 * @param string $accent   Block attribute (may be '').
	 * @param string $contrast Block attribute (may be '').
	 * @param int    $ref_id   Owning API reference.
	 * @return string Declarations for a `style` attribute, or '' when stock.
	 */
	public static function for_block( $accent, $contrast, $ref_id ) {
		$inherited = self::reference_colors( $ref_id );

		if ( null === self::normalize( $accent ) ) {
			$accent = $inherited['accent'];
		}

		if ( null === self::normalize( $contrast ) ) {
			$contrast = $inherited['contrast'];
		}

		return self::style( $accent, $contrast );
	}

	/**
	 * Build the palette declarations for an explicit accent pair.
	 *
	 * @param string $accent   Brand accent hex ('' = stock).
	 * @param string $contrast Text-on-accent hex ('' = stock).
	 * @return string Declarations for a `style` attribute, or '' when stock.
	 */
	public static function style( $accent, $contrast = '' ) {
		$accent   = self::normalize( $accent );
		$contrast = self::normalize( $contrast );

		// Nothing customized — let the stylesheet's hand-tuned greens stand.
		if ( null === $accent && null === $contrast ) {
			return '';
		}

		if ( null === $accent ) {
			$accent = self::DEFAULT_ACCENT;
		}

		if ( null === $contrast ) {
			$contrast = self::DEFAULT_CONTRAST;
		}

		if ( self::DEFAULT_ACCENT === $accent && self::DEFAULT_CONTRAST === $contrast ) {
			return '';
		}

		$vars = [
			'--betterdocs-api-accent'              => self::mix( $accent, '#ffffff', 0.09 ),
			'--betterdocs-api-accent-strong'       => $accent,
			'--betterdocs-api-accent-hover'        => self::mix( $accent, '#000000', 0.06 ),
			'--betterdocs-api-accent-hover-strong' => self::mix( $accent, '#000000', 0.22 ),
			'--betterdocs-api-accent-contrast'     => $contrast,
			'--betterdocs-api-accent-shadow'       => self::rgba( $accent, 0.4 )
		];

		$out = '';
		foreach ( $vars as $name => $value ) {
			$out .= $name . ':' . $value . ';';
		}

		/**
		 * Filter the Try-it banner's accent palette declarations.
		 *
		 * @param string $out      CSS declarations for the banner's style attribute.
		 * @param string $accent   Normalized accent hex.
		 * @param string $contrast Normalized text-on-accent hex.
		 */
		return (string) apply_filters( 'betterdocs_api_accent_style', $out, $accent, $contrast );
	}

	/**
	 * Normalize a user-supplied colour to a lowercase 6-digit hex.
	 *
	 * Accepts `#abc`, `abc`, `#aabbcc`, `AABBCC`, and the `rgb()` / `rgba()`
	 * strings BetterDocs' shared ColorControl emits. Alpha is dropped: the
	 * palette derives a hover pair and a glow from the base colour, which needs
	 * opaque channels, and a translucent button over an unknown background
	 * cannot be reasoned about.
	 *
	 * @param string $hex
	 * @return string|null `#rrggbb`, or null when unparseable/empty.
	 */
	public static function normalize( $hex ) {
		$hex = strtolower( trim( (string) $hex ) );

		if ( 0 === strpos( $hex, 'rgb' ) && preg_match( '/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/', $hex, $m ) ) {
			// A fully transparent colour is "no colour", not black.
			if ( isset( $m[4] ) && 0.0 === (float) $m[4] ) {
				return null;
			}

			$out = '#';
			foreach ( array( $m[1], $m[2], $m[3] ) as $channel ) {
				$out .= str_pad( dechex( max( 0, min( 255, (int) round( (float) $channel ) ) ) ), 2, '0', STR_PAD_LEFT );
			}

			return $out;
		}

		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) && ctype_xdigit( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return null;
		}

		return '#' . $hex;
	}

	/**
	 * Split `#rrggbb` into [r, g, b] ints.
	 *
	 * @param string $hex Normalized hex.
	 * @return int[]
	 */
	private static function channels( $hex ) {
		$hex = ltrim( $hex, '#' );

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) )
		);
	}

	/**
	 * Blend two colours — the lighten/darken primitive.
	 *
	 * @param string $hex    Normalized base hex.
	 * @param string $with   Normalized hex to blend toward (#ffffff / #000000).
	 * @param float  $weight 0..1 — how much of `$with` to mix in.
	 * @return string `#rrggbb`
	 */
	private static function mix( $hex, $with, $weight ) {
		$base   = self::channels( $hex );
		$target = self::channels( $with );
		$weight = max( 0.0, min( 1.0, (float) $weight ) );
		$out    = '#';

		foreach ( $base as $i => $channel ) {
			$value = (int) round( $channel + ( ( $target[ $i ] - $channel ) * $weight ) );
			$out  .= str_pad( dechex( max( 0, min( 255, $value ) ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}

	/**
	 * `rgba()` string for a hex at a given alpha (used for the hover glow).
	 *
	 * @param string $hex   Normalized hex.
	 * @param float  $alpha 0..1
	 * @return string
	 */
	private static function rgba( $hex, $alpha ) {
		list( $r, $g, $b ) = self::channels( $hex );

		return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim( rtrim( number_format( (float) $alpha, 2, '.', '' ), '0' ), '.' ) );
	}
}
