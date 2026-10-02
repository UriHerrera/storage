<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend touches for materialized endpoint docs: method badges in the
 * docs sidebar/category lists (via the Free `betterdocs_docs_list_item_title`
 * filter added in v1.5 Unit 2).
 */
class Frontend {
	/**
	 * Single-doc layouts that render the right-hand ToC sidebar
	 * (`templates/sidebars/sidebar-right`). Layout-1 has only a left sidebar.
	 *
	 * @var string[]
	 */
	const RIGHT_SIDEBAR_LAYOUTS = [ 'layout-4', 'layout-5', 'layout-8', 'layout-9', 'layout-10' ];

	/**
	 * Code-sample block markup stashed off the endpoint-doc content during
	 * `the_content` (right-sidebar layouts only), rendered later into the ToC
	 * sidebar via `betterdocs_after_toc_sidebar`. Null when nothing is stashed.
	 *
	 * @var string|null
	 */
	protected $stashed_samples = null;

	/**
	 * Code Snippet Tab block markup stashed alongside `$stashed_samples`,
	 * rendered into the ToC sidebar under the request Code Samples. Null when
	 * nothing is stashed.
	 *
	 * @var string|null
	 */
	protected $stashed_response = null;

	public function __construct() {
		add_filter( 'betterdocs_docs_list_item_title', [ $this, 'method_badge' ], 10, 2 );
		// `method_badge()` injects badges into *every* doc list — category
		// archives, the sidebar, search, doc-list shortcodes — not just endpoint
		// singles, but the only thing that ever loaded their CSS was
		// `enqueue_tryit()` below, which bails off an endpoint single. Badges
		// rendered as plain text everywhere else. Register + enqueue their own
		// small stylesheet independently.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_method_badges' ], 20 );
		// Load the native renderer on endpoint-doc singles so the banner
		// "Try it" button opens the inline drawer (v3.0, ADR-031). Priority 20 —
		// after Free registers the `betterdocs-api-reference` handle (init/10).
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tryit' ], 20 );

		// Relocate the generated "Code Samples" section out of the content flow.
		// It is always re-inserted right under the Try-it banner (visible on
		// small screens / no-right-sidebar layouts); on a right-sidebar layout a
		// copy is also stashed for the sticky ToC-column panel and CSS hides the
		// inline copy on desktop. Priority 8 — before core `do_blocks` (9) so we
		// operate on raw block markup, not rendered HTML.
		add_filter( 'the_content', [ $this, 'relocate_code_samples' ], 8 );
		add_action( 'betterdocs_after_toc_sidebar', [ $this, 'render_sidebar_code_samples' ] );

		// API docs get marker body classes for the layout overrides, and their
		// Table of Contents is disabled entirely — endpoint sections (Request
		// Body / Responses / Code Samples) don't warrant a ToC, and the code
		// panel takes the ToC column's place.
		add_filter( 'body_class', [ $this, 'body_classes' ] );
		add_filter( 'pre_do_shortcode_tag', [ $this, 'suppress_toc_shortcode' ], 10, 2 );
	}

	/**
	 * The HTTP method of the queried single post when it is a materialized API
	 * ENDPOINT doc (get/post/put/delete/patch/…), or '' otherwise.
	 *
	 * Only endpoint docs carry `_bd_api_method`. The generated Introduction doc
	 * carries `_bd_api_ref_id` but NO method, so it is treated as an ordinary
	 * doc — it keeps its Table of Contents. This is the single discriminator for
	 * the API-doc layout treatment (ToC removed, request/response code panels).
	 * Uses the queried object so it is valid before/outside the loop
	 * (body_class, shortcodes).
	 *
	 * @return string Lowercase HTTP method, or '' for the intro / non-API docs.
	 */
	protected function queried_endpoint_method() {
		if ( ! is_singular( 'docs' ) ) {
			return '';
		}

		$doc_id = get_queried_object_id();

		return $doc_id ? (string) get_post_meta( $doc_id, '_bd_api_method', true ) : '';
	}

	/**
	 * Body classes for API ENDPOINT docs (those with an HTTP method):
	 * `betterdocs-api-doc` (ToC removed, full-bleed content), plus
	 * `betterdocs-api-has-aside` when the active layout carries the right-hand
	 * code panel. The Introduction doc has no method, so it gets no classes and
	 * renders like a normal doc — keeping its Table of Contents.
	 *
	 * @param string[] $classes
	 * @return string[]
	 */
	public function body_classes( $classes ) {
		if ( '' === $this->queried_endpoint_method() ) {
			return $classes;
		}

		$classes[] = 'betterdocs-api-doc';

		if ( $this->has_right_sidebar() ) {
			$classes[] = 'betterdocs-api-has-aside';
		}

		return $classes;
	}

	/**
	 * Short-circuit the `[betterdocs_toc]` shortcode to nothing on API ENDPOINT
	 * docs so no Table of Contents renders (sidebar or mobile). The Introduction
	 * doc (no method) renders its ToC normally.
	 *
	 * @param false|string $output Short-circuit value (false = render normally).
	 * @param string       $tag    Shortcode tag.
	 * @return false|string
	 */
	public function suppress_toc_shortcode( $output, $tag ) {
		if ( 'betterdocs_toc' === $tag && '' !== $this->queried_endpoint_method() ) {
			return '';
		}

		return $output;
	}

	/**
	 * The owning reference ID when we are rendering a materialized endpoint doc
	 * in the main loop, or 0 otherwise.
	 *
	 * @return int
	 */
	protected function current_endpoint_ref_id() {
		if ( ! is_singular( 'docs' ) || ! is_main_query() ) {
			return 0;
		}

		$doc_id = get_the_ID();
		if ( ! $doc_id ) {
			return 0;
		}

		// Block templates render the post content without opening a loop, so
		// in_the_loop() is false there. Outside a loop only trust the queried
		// object, so secondary content on the page is still never rewritten.
		if ( ! in_the_loop() && $doc_id !== get_queried_object_id() ) {
			return 0;
		}

		return (int) get_post_meta( $doc_id, '_bd_api_ref_id', true );
	}

	/**
	 * Whether the active single-doc layout renders the right-hand ToC sidebar.
	 *
	 * @return bool
	 */
	protected function has_right_sidebar() {
		// Block themes never load the classic single-doc templates, so the
		// Customizer layout selector below says nothing about what will render.
		// There the aside exists exactly when the api-code-samples block is in
		// the resolved template.
		if ( $this->is_block_theme() ) {
			return $this->aside_block_present();
		}

		if ( 1 != betterdocs()->settings->get( 'enable_toc' ) ) {
			return false;
		}

		$layout = get_theme_mod( 'betterdocs_single_layout_select', 'layout-8' );

		return in_array( $layout, self::RIGHT_SIDEBAR_LAYOUTS, true );
	}

	/**
	 * @return bool
	 */
	protected function is_block_theme() {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Whether the resolved block template places the api-code-samples block.
	 *
	 * `locate_block_template()` fills `$_wp_current_template_content` during the
	 * `template_include` filter — before template-canvas.php calls body_class()
	 * — so this is already answerable by the time the body classes are built.
	 *
	 * @return bool
	 */
	protected function aside_block_present() {
		global $_wp_current_template_content;

		return is_string( $_wp_current_template_content )
			&& false !== strpos( $_wp_current_template_content, 'betterdocs/api-code-samples' );
	}

	/**
	 * The endpoint doc's Request/Response code sections, as serialized blocks.
	 *
	 * Parsed straight from the post so a caller isn't tied to `the_content`
	 * having already run — the api-code-samples block can render before the
	 * content block in a block template.
	 *
	 * @param int $post_id Defaults to the queried post.
	 * @return array{request: ?string, response: ?string}|null Null when this
	 *                                                         isn't an endpoint doc.
	 */
	public function code_sample_sections( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		$post    = $post_id ? get_post( $post_id ) : null;

		// Keyed off the post alone, never the loop or the main query: the editor
		// previews this block through the block-renderer REST route, where none
		// of that state exists.
		if ( ! $post || 'docs' !== $post->post_type ) {
			return null;
		}

		if ( ! get_post_meta( $post->ID, '_bd_api_ref_id', true ) ) {
			return null;
		}

		$blocks = parse_blocks( $post->post_content );

		$request = $this->extract_section( $blocks, function ( $block ) {
			return 'betterdocs/code-snippet' === $block['blockName'] && ! empty( $block['attrs']['codeVariants'] );
		} );

		$response = $this->extract_section( $blocks, function ( $block ) {
			return 'betterdocs/code-snippet-tab' === $block['blockName'];
		} );

		return [
			'request'  => null !== $request ? serialize_block( end( $request ) ) : null,
			'response' => null !== $response ? serialize_block( end( $response ) ) : null
		];
	}

	/**
	 * Pull the generated "Code Samples" section (heading + the multi-language
	 * code-snippet block) out of an endpoint doc's content and place it where
	 * the active layout wants it.
	 *
	 * @param string $content
	 * @return string
	 */
	public function relocate_code_samples( $content ) {
		if ( ! $this->current_endpoint_ref_id() ) {
			return $content;
		}

		$has_samples  = false !== strpos( $content, 'betterdocs/code-snippet' );
		$has_response = false !== strpos( $content, 'betterdocs/code-snippet-tab' );

		if ( ! $has_samples && ! $has_response ) {
			return $content;
		}

		$blocks       = parse_blocks( $content );
		$right_sidebar = $this->has_right_sidebar();

		// The request Code Samples block is the one code-snippet block carrying
		// `codeVariants` (multi-language). The response example is the single
		// code-snippet-tab block. Both move together into the sidebar; other
		// single-language JSON examples are left in place.
		$request_section = $this->extract_section( $blocks, function ( $block ) {
			return 'betterdocs/code-snippet' === $block['blockName'] && ! empty( $block['attrs']['codeVariants'] );
		} );

		$response_section = $this->extract_section( $blocks, function ( $block ) {
			return 'betterdocs/code-snippet-tab' === $block['blockName'];
		} );

		if ( null === $request_section && null === $response_section ) {
			return $content;
		}

		// On a right-sidebar layout, stash the code block(s) for the sticky
		// ToC-column panel (rendered via betterdocs_after_toc_sidebar). The
		// inline copies below still render — CSS hides them on desktop and
		// reveals them when the right sidebar collapses on smaller screens.
		if ( $right_sidebar ) {
			if ( null !== $request_section ) {
				$this->stashed_samples = serialize_block( end( $request_section ) );
			}
			if ( null !== $response_section ) {
				$this->stashed_response = serialize_block( end( $response_section ) );
			}
		}

		// Always place inline copies right after the Try-it banner, else at the
		// top. Wrap each so CSS can toggle it against the sidebar panel per
		// breakpoint. The banner is a `betterdocs/api-tryit` block (v3.3); older
		// generated docs still carry the legacy core/html banner — match both.
		$insert_at = 0;
		foreach ( $blocks as $i => $block ) {
			$is_banner = 'betterdocs/api-tryit' === $block['blockName']
				|| ( 'core/html' === $block['blockName'] && false !== strpos( (string) $block['innerHTML'], 'betterdocs-api-endpoint-banner' ) );

			if ( $is_banner ) {
				$insert_at = $i + 1;
				break;
			}
		}

		$inline = [];
		if ( null !== $request_section ) {
			$inline[] = $this->wrap_group( $request_section, 'betterdocs-api-code-inline' );
		}
		if ( null !== $response_section ) {
			$inline[] = $this->wrap_group( $response_section, 'betterdocs-api-response-inline' );
		}

		if ( $inline ) {
			array_splice( $blocks, $insert_at, 0, $inline );
		}

		return serialize_blocks( $blocks );
	}

	/**
	 * Splice the first top-level block matching `$match` (plus its immediately
	 * preceding core/heading) out of `$blocks`, returning the removed section
	 * (heading + block), or null when no block matches.
	 *
	 * @param array[]  $blocks Passed by reference — the match is removed.
	 * @param callable $match  fn( array $block ): bool
	 * @return array[]|null
	 */
	protected function extract_section( array &$blocks, callable $match ) {
		$index = null;
		foreach ( $blocks as $i => $block ) {
			if ( $match( $block ) ) {
				$index = $i;
				break;
			}
		}

		if ( null === $index ) {
			return null;
		}

		$section_start = $index;
		if ( $index > 0 && 'core/heading' === $blocks[ $index - 1 ]['blockName'] ) {
			$section_start = $index - 1;
		}

		return array_splice( $blocks, $section_start, $index - $section_start + 1 );
	}

	/**
	 * Wrap a list of blocks in a core/group with a className so the group's
	 * rendered `<div class="wp-block-group {class}">` is CSS-targetable.
	 *
	 * @param array[] $inner_blocks
	 * @param string  $class
	 * @return array Block array for serialize_blocks().
	 */
	protected function wrap_group( array $inner_blocks, $class ) {
		$open  = '<div class="wp-block-group ' . $class . '">';
		$close = '</div>';

		// innerContent interleaves literal HTML (strings) with one null per inner
		// block, in order — serialize_blocks() replaces each null with the
		// serialized child.
		$inner_content = [ $open ];
		foreach ( $inner_blocks as $_ ) {
			$inner_content[] = null;
		}
		$inner_content[] = $close;

		return [
			'blockName'    => 'core/group',
			'attrs'        => [ 'className' => $class ],
			'innerBlocks'  => array_values( $inner_blocks ),
			'innerHTML'    => $open . $close,
			'innerContent' => $inner_content
		];
	}

	/**
	 * Render the stashed code-sample panel inside the right ToC sidebar.
	 *
	 * @return void
	 */
	public function render_sidebar_code_samples() {
		if ( null === $this->stashed_samples && null === $this->stashed_response ) {
			return;
		}

		$rendered          = null !== $this->stashed_samples ? do_blocks( $this->stashed_samples ) : '';
		$rendered_response = null !== $this->stashed_response ? do_blocks( $this->stashed_response ) : '';

		include BETTERDOCS_PRO_ABSPATH . 'views/templates/api-ref/code-samples.php';
	}

	/**
	 * Enqueue the method-badge stylesheet on any view that can list docs.
	 *
	 * Endpoint-doc singles are skipped — `enqueue_tryit()` loads the full
	 * reference bundle there, which already carries these rules.
	 *
	 * @return void
	 */
	public function enqueue_method_badges() {
		if ( ! wp_style_is( 'betterdocs-api-method-badge', 'registered' ) ) {
			return;
		}

		// `enqueue_tryit()` loads the full reference bundle here, which already
		// carries these rules — checked directly rather than via `wp_style_is()`
		// because both run on the same hook priority and this one runs first.
		if ( $this->is_endpoint_single() ) {
			return;
		}

		// Views that render a doc list without help from a shortcode or block.
		// `knowledge_base` belongs here as much as `doc_category` does — a KB
		// archive IS the category-grid layout, so it lists docs and renders
		// badges; leaving it out is what made them appear unstyled there.
		// Anything not identifiable on this hook (a page holding a shortcode,
		// block or widget) is covered by ensure_badge_styles() at render time.
		$lists_docs = is_singular( 'docs' )
			|| is_post_type_archive( 'docs' )
			|| is_tax( 'doc_category' )
			|| is_tax( 'doc_tag' )
			|| is_tax( 'knowledge_base' )
			|| is_search();

		if ( ! $lists_docs ) {
			return;
		}

		wp_enqueue_style( 'betterdocs-api-method-badge' );
	}

	/**
	 * Make sure the badge rules reach the page, whatever rendered the list.
	 *
	 * The up-front enqueue can only cover views identifiable on
	 * `wp_enqueue_scripts`. Doc lists also come from the shortcodes, the
	 * Gutenberg blocks, the Elementor widgets and the sidebar/related-docs
	 * partials — any of which can sit on an arbitrary page, and all of which
	 * render during the template, after `wp_head` has printed the stylesheets.
	 *
	 * A late wp_enqueue_style() is not dependable there: it needs the theme to
	 * call wp_footer(), and even when that happens the badge renders unstyled
	 * until the footer loads. So once styles have been printed the rules go out
	 * inline instead — ~1KB, emitted at most once per request, and read only
	 * when a badge is actually being rendered.
	 *
	 * @return void
	 */
	public function ensure_badge_styles() {
		static $done = false;

		if ( $done ) {
			return;
		}

		// The full reference bundle already carries these rules.
		if ( wp_style_is( 'betterdocs-api-reference', 'enqueued' ) || wp_style_is( 'betterdocs-api-method-badge', 'enqueued' ) ) {
			$done = true;
			return;
		}

		if ( ! wp_style_is( 'betterdocs-api-method-badge', 'registered' ) ) {
			$done = true;
			return;
		}

		// Still ahead of the head being printed — the normal pipeline works.
		if ( ! did_action( 'wp_head' ) ) {
			wp_enqueue_style( 'betterdocs-api-method-badge' );
			$done = true;
			return;
		}

		$path = BETTERDOCS_PRO_ABSPATH . 'assets/build/public/css/api-method-badge.css';
		$css  = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( '' !== $css ) {
			printf(
				'<style id="betterdocs-api-method-badge-inline">%s</style>',
				$css // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- our own built stylesheet.
			);
		} else {
			// Unreadable for some reason — fall back to the late enqueue rather
			// than dropping the styling altogether.
			wp_enqueue_style( 'betterdocs-api-method-badge' );
		}

		$done = true;
	}

	/**
	 * Whether the current request is a single, materialized endpoint doc.
	 *
	 * @return bool
	 */
	protected function is_endpoint_single() {
		if ( ! is_singular( 'docs' ) ) {
			return false;
		}

		$doc_id = get_queried_object_id();

		return $doc_id && (bool) get_post_meta( $doc_id, '_bd_api_ref_id', true );
	}

	/**
	 * Enqueue the native reference app + localize its config on a materialized
	 * endpoint doc, so the banner's Try-it drawer can mount.
	 *
	 * @return void
	 */
	public function enqueue_tryit() {
		if ( ! $this->is_endpoint_single() ) {
			return;
		}

		wp_enqueue_script( 'betterdocs-api-reference' );
		wp_enqueue_style( 'betterdocs-api-reference' );

		wp_localize_script(
			'betterdocs-api-reference',
			'betterdocsApiRef',
			betterdocs()->container->get( \WPDeveloper\BetterDocsPro\Core\ApiReferences::class )->frontend_config()
		);
	}

	/**
	 * Prepend a method badge to endpoint docs in doc lists.
	 *
	 * @param string $title_html
	 * @param int    $post_id
	 * @return string
	 */
	public function method_badge( $title_html, $post_id ) {
		$method = get_post_meta( $post_id, '_bd_api_method', true );

		if ( '' === (string) $method ) {
			return $title_html;
		}

		// Guarantees the rules are present for THIS badge, whichever shortcode,
		// block, widget or template produced the list — including the ones that
		// render after the head has been printed.
		$this->ensure_badge_styles();

		$method = strtolower( (string) $method );
		$label  = 'delete' === $method ? 'DEL' : strtoupper( $method );

		return sprintf(
			'<span class="betterdocs-api-method betterdocs-api-method--%1$s">%2$s</span>%3$s',
			esc_attr( $method ),
			esc_html( $label ),
			$title_html
		);
	}
}
