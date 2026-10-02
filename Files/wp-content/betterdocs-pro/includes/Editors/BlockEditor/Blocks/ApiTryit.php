<?php

namespace WPDeveloper\BetterDocsPro\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\AccentPalette;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg block: betterdocs/api-tryit (Pro-only)
 *
 * Dynamic block — the endpoint "Try-it" banner (method + path + the drawer
 * trigger button). The materializer emits it per endpoint (attrs method/path/
 * refId); the label, show/hide and accent palette come from the owning
 * reference's meta at render time, so re-branding applies to every endpoint doc
 * without a rebuild. Also insertable manually. The banner markup lives in
 * ApiReferences::render_tryit_banner() (single source of truth), and the editor
 * previews it through that same renderer via <ServerSideRender>, so the block
 * looks identical in the editor and on the frontend.
 */
class ApiTryit extends Block {
	public $is_pro = true;

	protected $editor_scripts = [ 'betterdocs-pro-blocks-editor' ];
	// The frontend stylesheet is an editor style too — the editor preview is the
	// real banner markup, so it needs the real banner CSS inside the canvas.
	protected $editor_styles    = [ 'betterdocs-api-reference', 'betterdocs-pro-blocks-editor' ];
	protected $frontend_scripts = [ 'betterdocs-api-reference' ];
	protected $frontend_styles  = [ 'betterdocs-api-reference' ];

	/**
	 * Attribute schema.
	 *
	 * The Block base always passes its own `$attributes` into
	 * register_block_type(), which overrides whatever block.json declares — so
	 * an empty one would leave the block with no server-side schema. That is
	 * harmless for normal rendering (undeclared attributes pass through), but
	 * the block-renderer REST route <ServerSideRender> uses rejects any
	 * attribute the schema doesn't know (`additionalProperties: false`). Keep
	 * this in sync with block.json.
	 *
	 * @var array
	 */
	public $attributes = [
		'method' => [
			'type'    => 'string',
			'default' => 'get'
		],
		'path'   => [
			'type'    => 'string',
			'default' => ''
		],
		'refId'  => [
			'type'    => 'number',
			'default' => 0
		],
		'label'  => [
			'type'    => 'string',
			'default' => ''
		],
		// Style panel. Empty means "inherit the reference's branding", which is
		// what keeps a single branding change repainting every doc.
		'accentColor'     => [
			'type'    => 'string',
			'default' => ''
		],
		'accentTextColor' => [
			'type'    => 'string',
			'default' => ''
		]
	];

	/**
	 * Guard so N blocks on one page localize the drawer config once.
	 *
	 * @var bool
	 */
	private static $assets_done = false;

	public function get_name() {
		return 'api-tryit';
	}

	public function get_default_attributes() {
		return [
			'method'          => 'get',
			'path'            => '',
			'refId'           => 0,
			'label'           => '',
			'accentColor'     => '',
			'accentTextColor' => ''
		];
	}

	public function render( $attributes, $content ) {
		$method = isset( $attributes['method'] ) ? (string) $attributes['method'] : 'get';
		$path   = isset( $attributes['path'] ) ? (string) $attributes['path'] : '';
		$ref_id = isset( $attributes['refId'] ) ? (int) $attributes['refId'] : 0;

		// Per-instance overrides. Anything left empty falls back to the owning
		// reference's branding inside render_tryit_banner().
		$args = [];
		if ( isset( $attributes['label'] ) && '' !== $attributes['label'] ) {
			$args['label'] = (string) $attributes['label'];
		}

		$args['accent_style'] = AccentPalette::for_block(
			isset( $attributes['accentColor'] ) ? (string) $attributes['accentColor'] : '',
			isset( $attributes['accentTextColor'] ) ? (string) $attributes['accentTextColor'] : '',
			$ref_id
		);

		if ( '' === $path || ! $ref_id ) {
			return;
		}

		$references = betterdocs()->container->get( ApiReferences::class );

		// On an endpoint-doc single, Frontend::enqueue_tryit() has already
		// enqueued + localized the drawer. A hand-placed block on an ordinary
		// page gets neither (the block's $frontend_scripts pulls the bundle in
		// but nothing localizes `betterdocsApiRef`), so the drawer would refuse
		// to open. Do it here, once per request.
		if ( ! self::$assets_done ) {
			self::$assets_done = true;
			$references->enqueue_tryit_assets();
		}

		echo $references->render_tryit_banner( $method, $path, $ref_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_tryit_banner() escapes its parts.
	}
}
