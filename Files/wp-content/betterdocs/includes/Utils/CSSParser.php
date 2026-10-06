<?php

namespace WPDeveloper\BetterDocs\Utils;

class CSSParser {
	private static $instance;

	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Recursive function for parsing blocks
	 */
	public static function betterdocs_block_style_recursive( $block, &$betterdocs_blocks ) {
		if ( count( $block ) > 0 ) {
			foreach ( $block as $item ) {
				$attributes = $item['attrs'];

				$blockId = '';
				if ( isset( $attributes['blockId'] ) && ! empty( $attributes['blockId'] ) ) {
					$blockId = $attributes['blockId'];
				}
				$blockMeta = '';
				if ( isset( $attributes['blockMeta'] ) && ! empty( $attributes['blockMeta'] ) ) {
					$blockMeta = $attributes['blockMeta'];
				}
				$commonStyles = '';
				if ( isset( $attributes['commonStyles'] ) && ! empty( $attributes['commonStyles'] ) ) {
					$commonStyles = $attributes['commonStyles'];
				}
				$customCss = '';
				if ( isset( $attributes['customCss'] ) && ! empty( $attributes['customCss'] ) ) {
					$customCss = $attributes['customCss'];
				}

				if ( isset( $attributes['ref'] ) && ! empty( $attributes['ref'] ) && $item['blockName'] === 'core/block' ) {
					$reusable_block   = get_post( $attributes['ref'] );
					$reusable_content = ! empty( $reusable_block ) ? parse_blocks( $reusable_block->post_content ) : [];
					$reusable_blocks  = [];
					$betterdocs_blocks['reusableBlocks'][ $attributes['ref'] ] = self::betterdocs_block_style_recursive( $reusable_content, $reusable_blocks );
				} elseif ( isset( $item['innerBlocks'] ) && count( $item['innerBlocks'] ) > 0 ) {
						self::betterdocs_block_style_recursive( $item['innerBlocks'], $betterdocs_blocks );
					if ( isset( $attributes['blockMeta'] ) && ! empty( $attributes['blockMeta'] ) ) {
						$betterdocs_blocks[ $blockId ] = [
							'blockMeta'    => $blockMeta,
							'commonStyles' => $commonStyles,
							'customCss'    => $customCss
						];
					}
				} elseif ( isset( $attributes['blockMeta'] ) && ! empty( $attributes['blockMeta'] ) ) {
					$betterdocs_blocks[ $blockId ] = [
						'blockMeta'    => $blockMeta,
						'commonStyles' => $commonStyles,
						'customCss'    => $customCss
					];
				}
			}
		}

		return $betterdocs_blocks;
	}

	/**
	 * Blockarray to Style Array Function
	 */
	public static function blocks_to_style_array( $blocks, $styles = [] ) {
		if ( is_array( $blocks ) && count( $blocks ) > 0 ) {
			foreach ( $blocks as $blockId => $block ) {
				if ( 'reusableBlocks' === $blockId ) {
					if ( is_array( $block ) && count( $block ) > 0 ) {
						foreach ( $block as $reusableId => $reusableBlock ) {
							self::blocks_to_style_array( $reusableBlock, $styles );
						}
					}
				} else {
					$styles[ $blockId ] = [
						'desktop'   => '',
						'tab'       => '',
						'mobile'    => '',
						'customCss' => ''
					];

					if ( is_array( $block ) && count( $block ) > 0 ) {
						foreach ( $block as $value ) {
							if ( is_array( $value ) && count( $value ) > 0 ) {
								if ( isset( $value['desktop'] ) ) {
									$styles[ $blockId ]['desktop'] .= $value['desktop'];
								}
								if ( isset( $value['desktopHover'] ) ) {
									$styles[ $blockId ]['desktop'] .= $value['desktopHover'];
								}
								if ( isset( $value['tab'] ) ) {
									$styles[ $blockId ]['tab'] .= $value['tab'];
								}
								if ( isset( $value['tabHover'] ) ) {
									$styles[ $blockId ]['tab'] .= $value['tabHover'];
								}
								if ( isset( $value['mobile'] ) ) {
									$styles[ $blockId ]['mobile'] .= $value['mobile'];
								}
								if ( isset( $value['mobileHover'] ) ) {
									$styles[ $blockId ]['mobile'] .= $value['mobileHover'];
								}
							} elseif ( isset( $block['customCss'] ) && is_string( $block['customCss'] ) && strlen( $block['customCss'] ) > 0 ) {
								$styles[ $blockId ]['customCss'] .= $block['customCss'];
							}
						}
					}
				}
			}
		}

		return $styles;
	}

	/**
	 * Enqueue frontend css for post if have one
	 *
	 * @param array
	 *
	 * @return string
	 * @since 1.0.2
	 */
	public static function build_css( $style_object ) {
		$block_styles = $style_object;

		$css = '';
		foreach ( $block_styles as $block_style_key => $block_style ) {
			if ( ! empty( $block_css = (array) $block_style ) ) {
				$css .= sprintf( '/* %1$s Starts */', $block_style_key );
				foreach ( $block_css as $media => $style ) {
					switch ( $media ) {
						case 'desktop':
							$css .= preg_replace( '/\s+/', ' ', $style );
							break;
						case 'tab':
							$css .= ' @media(max-width: 1024px){';
							$css .= preg_replace( '/\s+/', ' ', $style );
							$css .= '}';
							break;
						case 'mobile':
							$css .= ' @media(max-width: 767px){';
							$css .= preg_replace( '/\s+/', ' ', $style );
							$css .= '}';
							break;
						case 'customCss':
							$css .= preg_replace( '/\s+/', ' ', $style );
							break;
					}
				}
				$css .= sprintf( '/* =%1$s= Ends */', $block_style_key );
			}
		}

		return trim( $css );
	}

	/**
	 * Helper function to get string between 2 string
	 * @since 3.3.0
	 */
	public static function get_between_data( $string, $start, $end ) {
		$pos_string   = stripos( $string, $start );
		$substr_data  = substr( $string, $pos_string );
		$string_two   = substr( $substr_data, strlen( $start ) );
		$second_pos   = stripos( $string_two, $end );
		$string_three = substr( $string_two, 0, $second_pos );

		// remove whitespaces from result
		$result_unit = trim( $string_three );

		// return result_unit
		return $result_unit;
	}
}
