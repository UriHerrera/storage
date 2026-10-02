<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Serve docs as Markdown to AI agents (Advanced Analytics v1.5).
 *
 * AI crawlers and assistants consume plain Markdown far more reliably than
 * themed HTML. This endpoint exposes every doc as `.md` through three
 * permalink-agnostic mechanisms — no rewrite rules, so nothing to flush:
 *
 *   1. A `.md` suffix on any doc URL      — /docs/my-doc.md
 *   2. `Accept: text/markdown` negotiation — same URL, header only
 *   3. An explicit `?format=md` query      — /docs/my-doc/?format=md
 *
 * The `.md` suffix is stripped from REQUEST_URI on `init` (before WordPress
 * routes the request) so the doc resolves normally; the Markdown is then
 * rendered on `template_redirect` at priority 9 — AFTER AiTrafficCollector
 * (priority 1) — so an AI agent fetching the `.md` is still counted as a fetch.
 *
 * Output is a YAML front-matter block (title, canonical URL, updated date,
 * category) followed by the doc body converted from rendered HTML to Markdown.
 */
class MarkdownEndpoint {
	/** Set when the incoming request URL carried a `.md` suffix. */
	protected $md_suffix = false;

	public function __construct() {
		// Strip the `.md` suffix NOW, not on another `init` hook. This constructor
		// runs on `betterdocs_init` (fired synchronously from inside Free's own
		// `init` priority-0 callback), so a callback added to `init` priority 0 here
		// would never fire — that bucket is already being iterated — yet we still run
		// before WP::parse_request(), which is what the strip needs.
		$this->maybe_strip_md_suffix();
		// Serve at priority 100 — AFTER AiTrafficCollector::maybe_record() (priority 1,
		// so `.md` bot fetches are still counted) AND after BetterDocs Pro Content
		// Restriction / Access Control redirects (template_redirect priority 10 & 99),
		// so a restricted doc is blocked before we could emit it. (#6)
		add_action( 'template_redirect', [ $this, 'maybe_serve_markdown' ], 100 );
	}

	/**
	 * Whether the Markdown endpoint is active. Filterable so a site can opt out.
	 */
	protected function enabled() {
		return (bool) apply_filters( 'betterdocs_md_endpoint_enabled', true );
	}

	/**
	 * If the request path ends in `.md`, strip it (before routing) and remember
	 * that Markdown was asked for. Scoped to GET requests; the actual Markdown is
	 * only served when the stripped URL resolves to a singular doc, so a stray
	 * `.md` on a non-doc URL simply falls through to normal handling.
	 */
	public function maybe_strip_md_suffix() {
		if ( ! $this->enabled() || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		// Front-end GET requests only — never touch admin, AJAX or REST routing.
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && strtoupper( $_SERVER['REQUEST_METHOD'] ) !== 'GET' ) {
			return;
		}

		$uri  = wp_unslash( $_SERVER['REQUEST_URI'] );
		$parts = explode( '?', $uri, 2 );
		$path  = $parts[0];
		$query = isset( $parts[1] ) ? '?' . $parts[1] : '';

		$trimmed = rtrim( $path, '/' );
		if ( substr( $trimmed, -3 ) !== '.md' ) {
			return;
		}

		$this->md_suffix       = true;
		$new_path              = substr( $trimmed, 0, -3 );
		$new_path              = ( $new_path === '' ? '/' : trailingslashit( $new_path ) );
		$_SERVER['REQUEST_URI'] = $new_path . $query;
	}

	/**
	 * Does this request want Markdown? True for a `.md` suffix, an
	 * `Accept: text/markdown` header, or an explicit `?format=md`.
	 */
	protected function wants_markdown() {
		if ( $this->md_suffix ) {
			return true;
		}
		if ( isset( $_GET['format'] ) && strtolower( sanitize_key( wp_unslash( $_GET['format'] ) ) ) === 'md' ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			$accept = strtolower( (string) wp_unslash( $_SERVER['HTTP_ACCEPT'] ) );
			if ( false !== strpos( $accept, 'text/markdown' ) || false !== strpos( $accept, 'text/x-markdown' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the current singular doc as Markdown and stop, when requested.
	 */
	public function maybe_serve_markdown() {
		if ( ! $this->enabled() || ! is_singular( 'docs' ) || is_preview() ) {
			return;
		}
		if ( ! $this->wants_markdown() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post || $post->post_type !== 'docs' ) {
			return;
		}

		// Respect visibility: password-protected docs are not served as raw Markdown.
		if ( post_password_required( $post ) ) {
			return;
		}

		// Never emit a doc the current user is not entitled to read. This mirrors the
		// theme path's BetterDocs Pro Content Restriction / Access Control gate; on top
		// of running after those redirects (priority 100), we re-check explicitly so no
		// restriction mode (redirect / content-replacement) can leak a restricted doc
		// via .md, Accept: text/markdown, or ?format=md. (#6)
		if ( function_exists( 'betterdocs_pro' ) ) {
			$restricted = betterdocs_pro()->get_restricted_doc_ids();
			if ( is_array( $restricted ) && in_array( (int) $post->ID, array_map( 'intval', $restricted ), true ) ) {
				return;
			}
		}

		$markdown = $this->render_markdown( $post );

		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: text/markdown; charset=utf-8' );
			header( 'X-Content-Type-Options: nosniff' );
			nocache_headers();
		}

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text Markdown body.
		exit;
	}

	/**
	 * Build the full Markdown document (YAML front matter + body) for a doc.
	 *
	 * @param \WP_Post $post
	 * @return string
	 */
	protected function render_markdown( $post ) {
		$title = get_the_title( $post );

		$terms    = get_the_terms( $post->ID, 'doc_category' );
		$category = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';

		$front = [
			'title'   => $title,
			'url'     => get_permalink( $post ),
			'updated' => get_post_modified_time( 'Y-m-d', true, $post ),
		];
		if ( $category !== '' ) {
			$front['category'] = $category;
		}

		$out = "---\n";
		foreach ( $front as $key => $value ) {
			$out .= $key . ': ' . $this->yaml_scalar( $value ) . "\n";
		}
		$out .= "---\n\n";

		$out .= '# ' . $title . "\n\n";

		// Render blocks/shortcodes to HTML first, then convert to Markdown.
		$html = apply_filters( 'the_content', $post->post_content );
		$out .= $this->html_to_markdown( $html );

		return rtrim( $out ) . "\n";
	}

	/**
	 * Quote a YAML scalar only when needed (contains a character that would
	 * otherwise break the mapping).
	 */
	protected function yaml_scalar( $value ) {
		$value = (string) $value;
		if ( $value === '' ) {
			return '""';
		}
		if ( preg_match( '/[:#\-\[\]\{\}&\*!\|>\'"%@`]/', $value ) || preg_match( '/^\s|\s$/', $value ) ) {
			return '"' . str_replace( '"', '\"', $value ) . '"';
		}
		return $value;
	}

	/**
	 * Convert an HTML fragment to Markdown via a DOM walk. Handles the common
	 * doc elements (headings, paragraphs, lists, links, images, emphasis, code,
	 * blockquotes, rules); unknown tags are unwrapped to their text.
	 *
	 * @param string $html
	 * @return string
	 */
	protected function html_to_markdown( $html ) {
		if ( trim( (string) $html ) === '' ) {
			return '';
		}
		if ( ! class_exists( '\DOMDocument' ) ) {
			return trim( wp_strip_all_tags( $html ) );
		}

		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML(
			'<?xml encoding="utf-8"?><body>' . $html . '</body>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$md   = $body ? $this->children_md( $body ) : wp_strip_all_tags( $html );

		$md = preg_replace( "/[ \t]+\n/", "\n", $md ); // strip trailing spaces
		$md = preg_replace( "/\n{3,}/", "\n\n", $md );  // collapse blank runs
		return trim( $md );
	}

	/**
	 * @param \DOMNode $node
	 * @return string
	 */
	protected function children_md( $node ) {
		$out = '';
		foreach ( $node->childNodes as $child ) {
			$out .= $this->node_to_md( $child );
		}
		return $out;
	}

	/**
	 * @param \DOMNode $node
	 * @return string
	 */
	protected function node_to_md( $node ) {
		if ( $node->nodeType === XML_TEXT_NODE ) {
			return preg_replace( '/\s+/', ' ', $node->nodeValue );
		}
		if ( $node->nodeType !== XML_ELEMENT_NODE ) {
			return '';
		}

		$tag   = strtolower( $node->nodeName );
		$inner = $this->children_md( $node );

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$level = (int) substr( $tag, 1 );
				return "\n\n" . str_repeat( '#', $level ) . ' ' . trim( $inner ) . "\n\n";
			case 'p':
				return "\n\n" . trim( $inner ) . "\n\n";
			case 'br':
				return "  \n";
			case 'hr':
				return "\n\n---\n\n";
			case 'strong':
			case 'b':
				return '**' . trim( $inner ) . '**';
			case 'em':
			case 'i':
				return '*' . trim( $inner ) . '*';
			case 'a':
				$href = $node->getAttribute( 'href' );
				$text = trim( $inner );
				return $href !== '' ? '[' . $text . '](' . $href . ')' : $text;
			case 'img':
				$src = $node->getAttribute( 'src' );
				$alt = $node->getAttribute( 'alt' );
				return $src !== '' ? '![' . $alt . '](' . $src . ')' : '';
			case 'code':
				// Inline code; block code is handled by the <pre> branch.
				return '`' . trim( $node->textContent ) . '`';
			case 'pre':
				return "\n\n```\n" . rtrim( $node->textContent ) . "\n```\n\n";
			case 'blockquote':
				$quote = trim( $this->children_md( $node ) );
				return "\n\n" . preg_replace( '/^/m', '> ', $quote ) . "\n\n";
			case 'ul':
				return "\n\n" . $this->list_md( $node, false ) . "\n\n";
			case 'ol':
				return "\n\n" . $this->list_md( $node, true ) . "\n\n";
			case 'script':
			case 'style':
				return '';
			default:
				return $inner; // unwrap unknown containers (div/span/section/…)
		}
	}

	/**
	 * @param \DOMNode $node    The <ul>/<ol> element.
	 * @param bool     $ordered
	 * @return string
	 */
	protected function list_md( $node, $ordered ) {
		$lines = [];
		$i     = 1;
		foreach ( $node->childNodes as $li ) {
			if ( $li->nodeType !== XML_ELEMENT_NODE || strtolower( $li->nodeName ) !== 'li' ) {
				continue;
			}
			$marker  = $ordered ? ( $i++ . '. ' ) : '- ';
			$content = trim( $this->children_md( $li ) );
			// Collapse blank lines inside the item so a nested list attaches directly
			// under its parent marker (no intervening blank line → tight list), then
			// indent wrapped/nested lines under the marker.
			$content = preg_replace( "/\n{2,}/", "\n", $content );
			$content = preg_replace( "/\n/", "\n  ", $content );
			$lines[] = $marker . $content;
		}
		return implode( "\n", $lines );
	}
}
