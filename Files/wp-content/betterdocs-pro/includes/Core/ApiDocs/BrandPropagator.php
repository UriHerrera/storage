<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Documentation — push a reference's branding onto the docs it generated.
 *
 * Generated endpoint docs carry their branding as block attributes: the accent
 * pair on the `betterdocs/api-tryit` banner, and the light/dark mode on every
 * `betterdocs/code-snippet` and `betterdocs/code-snippet-tab`. That is what
 * makes them editable per block — but it would normally mean re-generating
 * every doc after a branding change. Instead this rewrites just those
 * attributes across the whole reference in a single pass — no spec fetch, no
 * content regeneration, nothing else in the doc touched.
 *
 * Hash bookkeeping (ADR-014):
 *  - Un-edited docs get both hashes refreshed, so the next materialize() sees
 *    them as up-to-date rather than rewriting every doc a second time.
 *  - Writer-edited docs get the colours too (branding is site-wide, not a
 *    content decision) but keep their hashes, so they stay "edited" and
 *    materialize() still refuses to overwrite them.
 */
final class BrandPropagator {

	/**
	 * Everything a reference's branding decides about its generated blocks.
	 *
	 * @param int $reference_id
	 * @return array{accent:string, contrast:string, code_theme:string}
	 */
	public static function snapshot( $reference_id ) {
		$colors = AccentPalette::reference_colors( $reference_id );

		return array(
			'accent'     => $colors['accent'],
			'contrast'   => $colors['contrast'],
			'code_theme' => self::code_theme( $reference_id )
		);
	}

	/**
	 * The reference's Code Snippet colour mode — 'light' (default) | 'dark'.
	 *
	 * @param int $reference_id
	 * @return string
	 */
	public static function code_theme( $reference_id ) {
		return 'dark' === get_post_meta( (int) $reference_id, '_bd_api_code_theme', true ) ? 'dark' : 'light';
	}

	/**
	 * Apply a reference's current branding to every doc it generated.
	 *
	 * @param int $reference_id
	 * @return array{docs:int, changed:int} Docs scanned / docs rewritten.
	 */
	public static function apply( $reference_id ) {
		$reference_id = (int) $reference_id;
		$report       = array(
			'docs'    => 0,
			'changed' => 0
		);

		if ( ! $reference_id ) {
			return $report;
		}

		$branding = self::snapshot( $reference_id );

		$doc_ids = get_posts(
			array(
				'post_type'        => 'docs',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array(
					array(
						'key'   => '_bd_api_ref_id',
						'value' => $reference_id
					)
				)
			)
		);

		foreach ( $doc_ids as $doc_id ) {
			++$report['docs'];

			$post = get_post( $doc_id );

			if ( ! $post || false === strpos( (string) $post->post_content, 'wp:betterdocs/' ) ) {
				continue;
			}

			$found   = false;
			$blocks  = self::restyle( parse_blocks( (string) $post->post_content ), $branding, $found );
			$content = $found ? serialize_blocks( $blocks ) : (string) $post->post_content;

			if ( $content === (string) $post->post_content ) {
				continue;
			}

			$was_edited = self::is_edited( $post );

			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => wp_slash( $content )
				)
			);

			if ( ! $was_edited ) {
				// Re-read: kses and the save filters can mutate what we wrote, and
				// the generated hash must describe what is actually stored.
				$stored = get_post( $post->ID );

				update_post_meta( $post->ID, '_bd_api_generated_hash', hash( 'sha256', (string) $stored->post_content ) );
				update_post_meta( $post->ID, '_bd_api_source_hash', hash( 'sha256', $content ) );
			}

			++$report['changed'];
		}

		/**
		 * Fires after a reference's branding has been pushed to its docs.
		 *
		 * @param int   $reference_id
		 * @param array $report   { docs, changed }
		 * @param array $branding { accent, contrast, code_theme }
		 */
		do_action( 'betterdocs_api_brand_propagated', $reference_id, $report, $branding );

		return $report;
	}

	/**
	 * Whether the reference's branding differs from a snapshot taken before the
	 * write — i.e. whether apply() has anything to do.
	 *
	 * @param int   $reference_id
	 * @param array $before snapshot() as it was before the write.
	 * @return bool
	 */
	public static function changed( $reference_id, array $before ) {
		$after = self::snapshot( $reference_id );

		foreach ( array( 'accent', 'contrast', 'code_theme' ) as $key ) {
			$was = isset( $before[ $key ] ) ? $before[ $key ] : '';

			if ( $after[ $key ] !== $was ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Rewrite the branded attributes on every block we own, at any nesting
	 * depth: the accent pair on the Try-it banner, the colour mode on the code
	 * snippet blocks.
	 *
	 * @param array[] $blocks   Parsed blocks.
	 * @param array   $branding { accent, contrast, code_theme }
	 * @param bool    $found    Set to true when at least one block was rewritten.
	 * @return array[]
	 */
	private static function restyle( array $blocks, array $branding, &$found ) {
		foreach ( $blocks as $i => $block ) {
			$name  = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

			if ( 'betterdocs/api-tryit' === $name ) {
				unset( $attrs['accentColor'], $attrs['accentTextColor'] );

				// An unset colour means "inherit the reference", so drop the key
				// entirely rather than writing an empty string — that is also what
				// keeps an unbranded reference's markup byte-identical to what the
				// builder emits.
				if ( '' !== $branding['accent'] ) {
					$attrs['accentColor'] = $branding['accent'];
				}

				if ( '' !== $branding['contrast'] ) {
					$attrs['accentTextColor'] = $branding['contrast'];
				}

				$blocks[ $i ]['attrs'] = $attrs;
				$found                 = true;
			} elseif ( 'betterdocs/code-snippet' === $name || 'betterdocs/code-snippet-tab' === $name ) {
				// `theme` is always written (the builder always emits it), so the
				// key order the builder uses is preserved by assigning in place.
				$attrs['theme']        = $branding['code_theme'];
				$blocks[ $i ]['attrs'] = $attrs;
				$found                 = true;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $i ]['innerBlocks'] = self::restyle( $block['innerBlocks'], $branding, $found );
			}
		}

		return $blocks;
	}

	/**
	 * Same test as Materializer::is_edited(), without booting the materializer
	 * (which registers hooks we don't want on a plain colour change).
	 *
	 * @param \WP_Post $post
	 * @return bool
	 */
	private static function is_edited( \WP_Post $post ) {
		$generated_hash  = (string) get_post_meta( $post->ID, '_bd_api_generated_hash', true );
		$generated_title = (string) get_post_meta( $post->ID, '_bd_api_generated_title', true );

		if ( '' === $generated_hash ) {
			return false;
		}

		return hash( 'sha256', (string) $post->post_content ) !== $generated_hash
			|| ( '' !== $generated_title && $post->post_title !== $generated_title );
	}
}
