<?php
/**
 * Markdown / HTML → core blocks, and the FAQ block's attribute encoding.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `childNodes`, `nodeType`, `nodeName`, `textContent` and `ownerDocument` are PHP's own DOM API property names; they cannot be renamed.

namespace WPDeveloper\BetterDocs\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * An agent writes markdown. Gutenberg reads blocks. This is the seam.
 *
 * Everything here is static and hook-free, so it can be unit-tested without
 * WordPress and called from an ability, a REST handler or wp-cli alike.
 *
 * Three jobs:
 *
 * 1. **Content.** {@see self::content_to_blocks()} turns markdown (via the
 *    bundled Parsedown, safe mode on) or raw HTML into serialised core blocks —
 *    `paragraph`, `heading`, `list` + `list-item`, `code`, `quote`, `image`,
 *    `table`, `separator`, and `core/html` for anything else. A doc an agent
 *    wrote therefore opens in the block editor as real, editable blocks rather
 *    than one "Classic" lump.
 * 2. **The FAQ block.** {@see self::faq_block()} writes the object form
 *    (`[{"value":5,"label":"Install"}]`) the editor expects, escaped exactly the
 *    way Gutenberg escapes block attributes, so `parse_blocks()` round-trips and
 *    the editor does not flag the block as invalid.
 * 3. **Repair.** {@see self::repair_faq_blocks()} rewrites blocks saved with the
 *    bare-id form (`[5]`) into the object form. The *renderer* accepts bare
 *    ids; the *editor* still shows an empty group picker for one, so
 *    a block written by hand or by an older tool is repaired on the way past.
 *
 * The serialised output matches what Gutenberg's own JS serialiser produces —
 * `core/` stripped from the block name, attributes through
 * `serialize_block_attributes()`, a newline between the opening comment and the
 * markup, and `\n\n` between sibling blocks — because the editor's validator
 * compares saved markup against what the block's `save()` would emit.
 *
 * **Slash the output before `wp_insert_post()` / `wp_update_post()`.** Block
 * attributes are full of backslashes (`\u0022`, `\u002d\u002d`), and those two
 * functions `wp_unslash()` their input — measured on the rig: a `betterdocs/faq`
 * block stored directly arrived as `u0022value…`, its attribute unparseable and
 * its group filter silently empty. Either pass `wp_slash( $content )`, or go
 * through the REST route (`rest_do_request()` on `wp/v2/docs`), which slashes
 * for you inside `WP_REST_Posts_Controller`. The latter is what
 * `04-CONVENTIONS.md` requires of an ability anyway.
 *
 * @since 4.9.0
 */
final class BlockBuilder {

	/**
	 * The FAQ block's name.
	 *
	 * @since 4.9.0
	 */
	const FAQ_BLOCK = 'betterdocs/faq';

	/**
	 * The two FAQ block attributes that hold a group list.
	 *
	 * @since 4.9.0
	 *
	 * @var string[]
	 */
	const FAQ_GROUP_ATTRIBUTES = [ 'includeFaqGroup', 'excludeFaqGroup' ];

	/**
	 * Markdown → sanitised HTML.
	 *
	 * Safe mode is **on**: this text came from an AI agent over the network, and
	 * markdown allows raw HTML by definition. Parsedown escapes embedded markup
	 * and filters link/image URL schemes; `wp_kses_post()` then applies
	 * WordPress' own post allow-list, so the result is no more dangerous than
	 * anything an Author could paste into the editor. Line breaks are **not**
	 * converted to `<br>` — markdown's own rule (a blank line starts a
	 * paragraph) is what an agent writing prose expects.
	 *
	 * @since 4.9.0
	 *
	 * @param string $md Markdown.
	 * @return string HTML.
	 */
	public static function markdown_to_html( $md ) {
		$md = (string) $md;

		if ( '' === trim( $md ) ) {
			return '';
		}

		if ( ! class_exists( 'Parsedown' ) ) {
			// The bundled runtime is missing; do not silently drop the content.
			return function_exists( 'wp_kses_post' ) ? wp_kses_post( $md ) : $md;
		}

		$parsedown = new \Parsedown();
		$parsedown->setSafeMode( true );
		$parsedown->setBreaksEnabled( false );

		$html = $parsedown->text( $md );

		return function_exists( 'wp_kses_post' ) ? wp_kses_post( $html ) : $html;
	}

	/**
	 * HTML → serialised core blocks.
	 *
	 * Walks the top-level elements only. Anything without a mapping — a `<div>`,
	 * a `<details>`, an embed — becomes a `core/html` block holding its outer
	 * HTML, which is lossless and still editable.
	 *
	 * @since 4.9.0
	 *
	 * @param string $html HTML.
	 * @return string Serialised blocks, separated by a blank line.
	 */
	public static function html_to_blocks( $html ) {
		$html = (string) $html;

		if ( '' === trim( $html ) ) {
			return '';
		}

		$root = self::load_html( $html );

		if ( null === $root ) {
			return self::block( 'core/html', [], trim( $html ) );
		}

		$blocks = [];

		foreach ( $root->childNodes as $node ) {
			$block = self::node_to_block( $node );

			if ( '' !== $block ) {
				$blocks[] = $block;
			}
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Content in whichever format the caller declared → serialised blocks.
	 *
	 * `blocks` is returned untouched: the caller has asserted the string already
	 * contains block comments, and re-parsing it would only risk changing it.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content Raw content.
	 * @param string $format  `markdown` (default), `html` or `blocks`.
	 * @return string
	 */
	public static function content_to_blocks( $content, $format = 'markdown' ) {
		$content = (string) $content;
		$format  = strtolower( trim( (string) $format ) );

		switch ( $format ) {
			case 'blocks':
				return $content;

			case 'html':
				return self::html_to_blocks( $content );

			case 'markdown':
			default:
				return self::html_to_blocks( self::markdown_to_html( $content ) );
		}
	}

	// -------------------------------------------------------------------------
	// The FAQ block
	// -------------------------------------------------------------------------

	/**
	 * A `betterdocs/faq` block filtered to the given groups.
	 *
	 * `includeFaqGroup` is a JSON **string** inside the attribute object — a
	 * string containing JSON, not nested JSON — because that is the shape the
	 * block's `attributes.js` declares and the editor's group picker reads. The
	 * value is `[{"value":<int>,"label":"<term name>"}]`.
	 *
	 * @since 4.9.0
	 *
	 * @param array $groups      `[ [ 'id' => 5, 'label' => 'Install' ], … ]`.
	 * @param array $extra_attrs Extra block attributes, e.g. `[ 'faqLayout' => 'modern' ]`.
	 * @return string `<!-- wp:betterdocs/faq {…} /-->`
	 */
	public static function faq_block( array $groups, array $extra_attrs = [] ) {
		$attrs = $extra_attrs;

		$attrs['includeFaqGroup'] = self::encode_groups( $groups );

		return self::block( self::FAQ_BLOCK, $attrs, '' );
	}

	/**
	 * Encode a group list the way the block stores it.
	 *
	 * @since 4.9.0
	 *
	 * @param array $groups `[ [ 'id' => 5, 'label' => 'Install' ], … ]`, or bare ids.
	 * @return string JSON string, `[]` when there is nothing to encode.
	 */
	public static function encode_groups( array $groups ) {
		$encoded = [];

		foreach ( $groups as $group ) {
			if ( is_array( $group ) ) {
				$id    = isset( $group['id'] ) ? (int) $group['id'] : ( isset( $group['value'] ) ? (int) $group['value'] : 0 );
				$label = isset( $group['label'] ) ? (string) $group['label'] : '';
			} elseif ( is_numeric( $group ) ) {
				$id    = (int) $group;
				$label = '';
			} else {
				continue;
			}

			if ( $id <= 0 ) {
				continue;
			}

			$encoded[] = [
				'value' => $id,
				'label' => $label,
			];
		}

		return self::json( $encoded );
	}

	/**
	 * The groups a parsed FAQ block filters on.
	 *
	 * Accepts every shape the attribute has ever held — the object form, a bare
	 * id array, a JSON string of either, an already-decoded array, `null` and
	 * junk — and answers with a uniform list. Same normalisation as
	 * `Block::normalize_id_list()`, but keeping the labels.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $block     A parsed block (from `parse_blocks()`).
	 * @param string $attribute Which attribute to read.
	 * @return array `[ [ 'id' => int, 'label' => string|null ], … ]`
	 */
	public static function parse_faq_block_groups( array $block, $attribute = 'includeFaqGroup' ) {
		$raw = isset( $block['attrs'][ $attribute ] ) ? $block['attrs'][ $attribute ] : null;

		return self::decode_groups( $raw );
	}

	/**
	 * Decode a raw `includeFaqGroup`/`excludeFaqGroup` value.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $raw String, array or anything else.
	 * @return array `[ [ 'id' => int, 'label' => string|null ], … ]`
	 */
	public static function decode_groups( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}

		if ( ! is_array( $raw ) ) {
			return [];
		}

		$groups = [];

		foreach ( $raw as $item ) {
			if ( is_array( $item ) ) {
				$id    = isset( $item['value'] ) ? $item['value'] : ( isset( $item['id'] ) ? $item['id'] : null );
				$label = isset( $item['label'] ) ? (string) $item['label'] : null;
			} elseif ( is_scalar( $item ) ) {
				$id    = $item;
				$label = null;
			} else {
				continue;
			}

			if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
				continue;
			}

			$groups[] = [
				'id'    => (int) $id,
				'label' => $label,
			];
		}

		return $groups;
	}

	/**
	 * Every `betterdocs/faq` block in a post's content, however deeply nested.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content Post content.
	 * @return array `[ [ 'block' => array, 'include' => array, 'exclude' => array ], … ]`
	 */
	public static function find_faq_blocks( $content ) {
		$found = [];

		self::walk_blocks(
			parse_blocks( (string) $content ),
			static function ( array $block ) use ( &$found ) {
				if ( self::FAQ_BLOCK !== ( isset( $block['blockName'] ) ? $block['blockName'] : null ) ) {
					return;
				}

				$found[] = [
					'block'   => $block,
					'include' => self::parse_faq_block_groups( $block, 'includeFaqGroup' ),
					'exclude' => self::parse_faq_block_groups( $block, 'excludeFaqGroup' ),
				];
			}
		);

		return $found;
	}

	/**
	 * Remove every FAQ block and append fresh markup — the `replace_faq_blocks`
	 * mode of `bd-attach-faq`, and what makes that tool idempotent.
	 *
	 * Non-FAQ blocks are re-serialised from their parsed form. Core's
	 * `serialize_blocks( parse_blocks( $x ) )` is the identity on well-formed
	 * content, so a doc with no FAQ block in it comes back byte-identical apart
	 * from the appended markup.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content    Post content.
	 * @param string $new_markup Markup to append; `''` to only remove.
	 * @return string
	 */
	public static function replace_faq_blocks( $content, $new_markup = '' ) {
		$blocks = self::reject_faq_blocks( parse_blocks( (string) $content ) );
		$kept   = rtrim( serialize_blocks( $blocks ) );

		return self::append_block( $kept, $new_markup );
	}

	/**
	 * Append a block to a post's content, with one blank line between.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content Post content.
	 * @param string $markup  Block markup.
	 * @return string
	 */
	public static function append_block( $content, $markup ) {
		$content = rtrim( (string) $content );
		$markup  = trim( (string) $markup );

		if ( '' === $markup ) {
			return $content;
		}

		if ( '' === $content ) {
			return $markup;
		}

		return $content . "\n\n" . $markup;
	}

	/**
	 * Rewrite bare-id FAQ blocks into the object form.
	 *
	 * A block saved as `{"includeFaqGroup":"[5]"}` renders correctly but opens
	 * in Gutenberg with an empty group picker, because the editor's
	 * `edit.js` maps `faq.value` over the decoded array. This resolves each id to
	 * its term name through `$label_for_id` and writes the object form back.
	 *
	 * Returns the content **unchanged** — byte-identical, not merely equivalent —
	 * when nothing needed repair, so a caller can report "no change" honestly.
	 *
	 * @since 4.9.0
	 *
	 * @param string   $content      Post content.
	 * @param callable $label_for_id `fn( int $id ): string` — usually the term name.
	 * @return string
	 */
	public static function repair_faq_blocks( $content, callable $label_for_id ) {
		$content = (string) $content;
		$blocks  = parse_blocks( $content );
		$changed = false;

		$blocks = self::map_blocks(
			$blocks,
			static function ( array $block ) use ( $label_for_id, &$changed ) {
				if ( self::FAQ_BLOCK !== ( isset( $block['blockName'] ) ? $block['blockName'] : null ) ) {
					return $block;
				}

				foreach ( self::FAQ_GROUP_ATTRIBUTES as $attribute ) {
					if ( ! isset( $block['attrs'][ $attribute ] ) ) {
						continue;
					}

					$groups = self::decode_groups( $block['attrs'][ $attribute ] );

					if ( empty( $groups ) || ! self::needs_repair( $groups ) ) {
						continue;
					}

					$repaired = [];

					foreach ( $groups as $group ) {
						$label = ( null === $group['label'] || '' === $group['label'] )
							? (string) call_user_func( $label_for_id, $group['id'] )
							: $group['label'];

						$repaired[] = [
							'id'    => $group['id'],
							'label' => $label,
						];
					}

					$encoded = self::encode_groups( $repaired );

					if ( $encoded !== $block['attrs'][ $attribute ] ) {
						$block['attrs'][ $attribute ] = $encoded;
						$changed                      = true;
					}
				}

				return $block;
			}
		);

		return $changed ? serialize_blocks( $blocks ) : $content;
	}

	// -------------------------------------------------------------------------
	// Block serialisation
	// -------------------------------------------------------------------------

	/**
	 * Serialise one block the way Gutenberg's JS serialiser does.
	 *
	 * Three details matter, and all three come from
	 * `@wordpress/blocks/src/api/serializer.js`: `core/` is stripped from the
	 * name, attributes go through the same escaping as
	 * `serialize_block_attributes()` (`--`, `<`, `>`, `&`, `\"` and `\\` become
	 * unicode escapes, so the JSON can live inside an HTML comment), and the
	 * inner markup is wrapped in newlines. Getting any of them wrong makes the
	 * editor show "This block contains unexpected or invalid content".
	 *
	 * @since 4.9.0
	 *
	 * @param string $name       Full block name, e.g. `core/paragraph`.
	 * @param array  $attrs      Attributes; `[]` for none.
	 * @param string $inner_html Inner markup; `''` produces the void form.
	 * @return string
	 */
	public static function block( $name, array $attrs, $inner_html ) {
		$short      = 0 === strpos( $name, 'core/' ) ? substr( $name, 5 ) : $name;
		$serialized = empty( $attrs ) ? '' : self::serialize_attributes( $attrs ) . ' ';

		if ( '' === $inner_html ) {
			return sprintf( '<!-- wp:%s %s/-->', $short, $serialized );
		}

		return sprintf(
			"<!-- wp:%s %s-->\n%s\n<!-- /wp:%s -->",
			$short,
			$serialized,
			$inner_html,
			$short
		);
	}

	/**
	 * Block attributes as an HTML-comment-safe JSON string.
	 *
	 * Delegates to core's `serialize_block_attributes()` when it is loaded and
	 * reproduces it otherwise, so the unit suite — which runs without WordPress —
	 * exercises the same escaping the editor will see.
	 *
	 * @since 4.9.0
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	public static function serialize_attributes( array $attrs ) {
		if ( function_exists( 'serialize_block_attributes' ) ) {
			return serialize_block_attributes( $attrs );
		}

		return strtr(
			self::json( $attrs ),
			[
				'\\\\' => '\\u005c',
				'--'   => '\\u002d\\u002d',
				'<'    => '\\u003c',
				'>'    => '\\u003e',
				'&'    => '\\u0026',
				'\\"'  => '\\u0022',
			]
		);
	}

	// -------------------------------------------------------------------------
	// HTML → block mapping
	// -------------------------------------------------------------------------

	/**
	 * Parse an HTML fragment and return the element the nodes hang off.
	 *
	 * The `<?xml encoding>` processing instruction is how you tell libxml the
	 * fragment is UTF-8 without `mb_convert_encoding( …, 'HTML-ENTITIES' )`,
	 * which PHP 8.2 deprecates. The wrapper `<div>` gives a single root, so
	 * `documentElement` is unambiguous.
	 *
	 * @since 4.9.0
	 *
	 * @param string $html HTML fragment.
	 * @return \DOMElement|null
	 */
	private static function load_html( $html ) {
		if ( ! class_exists( '\DOMDocument' ) ) {
			return null;
		}

		$dom      = new \DOMDocument( '1.0', 'UTF-8' );
		$previous = libxml_use_internal_errors( true );

		$loaded = $dom->loadHTML(
			'<?xml encoding="utf-8" ?><div>' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded || ! $dom->documentElement ) {
			return null;
		}

		return $dom->documentElement;
	}

	/**
	 * One top-level DOM node → one serialised block.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node Node.
	 * @return string Empty when the node carries nothing.
	 */
	private static function node_to_block( \DOMNode $node ) {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = trim( $node->textContent );

			return '' === $text ? '' : self::block( 'core/paragraph', [], '<p>' . esc_html( $text ) . '</p>' );
		}

		if ( XML_COMMENT_NODE === $node->nodeType || XML_PI_NODE === $node->nodeType ) {
			return '';
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag = strtolower( $node->nodeName );

		switch ( $tag ) {
			case 'p':
				return self::paragraph_block( $node );

			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$level = (int) substr( $tag, 1 );

				return self::block(
					'core/heading',
					[ 'level' => $level ],
					sprintf( '<h%1$d class="wp-block-heading">%2$s</h%1$d>', $level, self::inner_html( $node ) )
				);

			case 'ul':
			case 'ol':
				return self::list_block( $node );

			case 'pre':
				return self::block(
					'core/code',
					[],
					'<pre class="wp-block-code"><code>' . self::code_text( $node ) . '</code></pre>'
				);

			case 'blockquote':
				return self::block(
					'core/quote',
					[],
					'<blockquote class="wp-block-quote">' . self::inner_blocks_of( $node ) . '</blockquote>'
				);

			case 'img':
				return self::image_block( $node );

			case 'figure':
				return self::figure_block( $node );

			case 'table':
				return self::block(
					'core/table',
					[],
					'<figure class="wp-block-table">' . self::outer_html( $node ) . '</figure>'
				);

			case 'hr':
				return self::block(
					'core/separator',
					[],
					'<hr class="wp-block-separator has-alpha-channel-opacity"/>'
				);

			default:
				return self::block( 'core/html', [], self::outer_html( $node ) );
		}
	}

	/**
	 * A `<p>` — or, when it holds nothing but an image, an image block.
	 *
	 * Markdown's `![alt](src)` on its own line produces `<p><img …></p>`, and a
	 * paragraph-wrapped image is not what the author meant.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The `<p>`.
	 * @return string
	 */
	private static function paragraph_block( \DOMNode $node ) {
		$image = self::only_child_image( $node );

		if ( null !== $image ) {
			return self::image_block( $image );
		}

		$inner = self::inner_html( $node );

		if ( '' === trim( $inner ) ) {
			return '';
		}

		return self::block( 'core/paragraph', [], '<p>' . $inner . '</p>' );
	}

	/**
	 * `ul`/`ol` → `core/list` with one `core/list-item` per `li`.
	 *
	 * A nested list becomes a `core/list` **inside** its parent list item, which
	 * is the WP ≥ 6.2 shape.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The list element.
	 * @return string
	 */
	private static function list_block( \DOMNode $node ) {
		$ordered = 'ol' === strtolower( $node->nodeName );
		$items   = [];

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType || 'li' !== strtolower( $child->nodeName ) ) {
				continue;
			}

			$text   = '';
			$nested = '';

			foreach ( $child->childNodes as $part ) {
				if ( XML_ELEMENT_NODE === $part->nodeType && in_array( strtolower( $part->nodeName ), [ 'ul', 'ol' ], true ) ) {
					$nested .= self::list_block( $part );
					continue;
				}

				$text .= self::outer_html( $part );
			}

			$items[] = self::block(
				'core/list-item',
				[],
				'<li>' . trim( $text ) . $nested . '</li>'
			);
		}

		if ( empty( $items ) ) {
			return '';
		}

		$tag   = $ordered ? 'ol' : 'ul';
		$attrs = $ordered ? [ 'ordered' => true ] : [];

		return self::block(
			'core/list',
			$attrs,
			sprintf( '<%1$s class="wp-block-list">%2$s</%1$s>', $tag, implode( "\n\n", $items ) )
		);
	}

	/**
	 * `img` → `core/image`.
	 *
	 * No `id` attribute: the image is a URL, not an attachment in this site's
	 * media library, and claiming an id the site does not have is worse than
	 * claiming none.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The `<img>`.
	 * @return string
	 */
	private static function image_block( \DOMNode $node ) {
		$src = $node instanceof \DOMElement ? $node->getAttribute( 'src' ) : '';
		$alt = $node instanceof \DOMElement ? $node->getAttribute( 'alt' ) : '';

		if ( '' === $src ) {
			return '';
		}

		return self::block(
			'core/image',
			[],
			sprintf(
				'<figure class="wp-block-image"><img src="%s" alt="%s"/></figure>',
				esc_url( $src ),
				esc_attr( $alt )
			)
		);
	}

	/**
	 * A `<figure>` — route it by what it wraps, so an HTML-format doc that
	 * already used figures does not become a wall of `core/html`.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The `<figure>`.
	 * @return string
	 */
	private static function figure_block( \DOMNode $node ) {
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$tag = strtolower( $child->nodeName );

			if ( 'img' === $tag ) {
				return self::image_block( $child );
			}

			if ( 'table' === $tag ) {
				return self::block(
					'core/table',
					[],
					'<figure class="wp-block-table">' . self::outer_html( $child ) . '</figure>'
				);
			}
		}

		return self::block( 'core/html', [], self::outer_html( $node ) );
	}

	/**
	 * The inner paragraphs of a blockquote, as nested blocks.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The `<blockquote>`.
	 * @return string
	 */
	private static function inner_blocks_of( \DOMNode $node ) {
		$blocks = [];

		foreach ( $node->childNodes as $child ) {
			$block = self::node_to_block( $child );

			if ( '' !== $block ) {
				$blocks[] = $block;
			}
		}

		if ( empty( $blocks ) ) {
			$text = trim( self::inner_html( $node ) );

			if ( '' === $text ) {
				return '';
			}

			$blocks[] = self::block( 'core/paragraph', [], '<p>' . $text . '</p>' );
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * The single `<img>` a paragraph wraps, if that is all it holds.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The `<p>`.
	 * @return \DOMNode|null
	 */
	private static function only_child_image( \DOMNode $node ) {
		$image = null;

		foreach ( $node->childNodes as $child ) {
			if ( XML_TEXT_NODE === $child->nodeType ) {
				if ( '' !== trim( $child->textContent ) ) {
					return null;
				}

				continue;
			}

			if ( XML_ELEMENT_NODE === $child->nodeType && 'img' === strtolower( $child->nodeName ) && null === $image ) {
				$image = $child;
				continue;
			}

			return null;
		}

		return $image;
	}

	/**
	 * The text of a `<pre>`, entity-escaped for a `<code>` element.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node The `<pre>`.
	 * @return string
	 */
	private static function code_text( \DOMNode $node ) {
		return esc_html( $node->textContent );
	}

	/**
	 * A node's children, serialised.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node Node.
	 * @return string
	 */
	private static function inner_html( \DOMNode $node ) {
		$html = '';

		foreach ( $node->childNodes as $child ) {
			$html .= self::outer_html( $child );
		}

		return $html;
	}

	/**
	 * A node, serialised.
	 *
	 * @since 4.9.0
	 *
	 * @param \DOMNode $node Node.
	 * @return string
	 */
	private static function outer_html( \DOMNode $node ) {
		$html = $node->ownerDocument->saveHTML( $node );

		return false === $html ? '' : $html;
	}

	// -------------------------------------------------------------------------
	// Block-tree helpers
	// -------------------------------------------------------------------------

	/**
	 * Whether any group in the list is missing its label.
	 *
	 * @since 4.9.0
	 *
	 * @param array $groups Decoded groups.
	 * @return bool
	 */
	private static function needs_repair( array $groups ) {
		foreach ( $groups as $group ) {
			if ( null === $group['label'] || '' === $group['label'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Call `$visitor` on every block, innermost included.
	 *
	 * @since 4.9.0
	 *
	 * @param array    $blocks  Parsed blocks.
	 * @param callable $visitor `fn( array $block ): void`.
	 * @return void
	 */
	private static function walk_blocks( array $blocks, callable $visitor ) {
		foreach ( $blocks as $block ) {
			call_user_func( $visitor, $block );

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::walk_blocks( $block['innerBlocks'], $visitor );
			}
		}
	}

	/**
	 * Rewrite every block through `$mapper`, innermost first.
	 *
	 * @since 4.9.0
	 *
	 * @param array    $blocks Parsed blocks.
	 * @param callable $mapper `fn( array $block ): array`.
	 * @return array
	 */
	private static function map_blocks( array $blocks, callable $mapper ) {
		$mapped = [];

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::map_blocks( $block['innerBlocks'], $mapper );
			}

			$mapped[] = call_user_func( $mapper, $block );
		}

		return $mapped;
	}

	/**
	 * Drop every FAQ block from a parsed tree, at any depth.
	 *
	 * A removed inner block also has to lose its `null` placeholder in the
	 * parent's `innerContent`, or `serialize_block()` walks off the end of
	 * `innerBlocks`.
	 *
	 * @since 4.9.0
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private static function reject_faq_blocks( array $blocks ) {
		$kept = [];

		foreach ( $blocks as $block ) {
			$name = isset( $block['blockName'] ) ? $block['blockName'] : null;

			if ( self::FAQ_BLOCK === $name ) {
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$before               = count( $block['innerBlocks'] );
				$block['innerBlocks'] = self::reject_faq_blocks( $block['innerBlocks'] );

				if ( count( $block['innerBlocks'] ) !== $before ) {
					$block['innerContent'] = self::rebuild_inner_content( $block, $before - count( $block['innerBlocks'] ) );
				}
			}

			$kept[] = $block;
		}

		return $kept;
	}

	/**
	 * Drop `$removed` of the `null` placeholders from a block's `innerContent`.
	 *
	 * @since 4.9.0
	 *
	 * @param array $block   The parent block.
	 * @param int   $removed How many inner blocks went away.
	 * @return array
	 */
	private static function rebuild_inner_content( array $block, $removed ) {
		$content = isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ? $block['innerContent'] : [];
		$rebuilt = [];

		foreach ( $content as $chunk ) {
			if ( null === $chunk && $removed > 0 ) {
				--$removed;
				continue;
			}

			$rebuilt[] = $chunk;
		}

		return $rebuilt;
	}

	/**
	 * `wp_json_encode()` with the two flags block attributes are written with:
	 * unescaped slashes (a URL in an attribute stays readable) and unescaped
	 * unicode (so `\u65e5` does not appear where `日` belongs). Both match
	 * core's `serialize_block_attributes()`.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function json( $value ) {
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return false === $json ? '[]' : $json;
	}
}
