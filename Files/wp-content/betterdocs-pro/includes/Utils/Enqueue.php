<?php
namespace WPDeveloper\BetterDocsPro\Utils;

use WPDeveloper\BetterDocs\Utils\Enqueue as FreeEnqueue;

class Enqueue extends FreeEnqueue {
	/**
	 * The plugin root this instance resolves against, captured from the
	 * constructor.
	 *
	 * Free's Enqueue keeps $plugin_url / $plugin_path **private**, so a subclass
	 * cannot read them — which is why the resolver below needs its own copy. It
	 * must NOT read the BETTERDOCS_PRO_* constants instead: this class is also the
	 * parent of the AI Chatbot add-on's Enqueue (an empty subclass), and that
	 * instance is constructed with BETTERDOCS_CHATBOT_*. Hardcoding Pro's
	 * constants made every chatbot asset resolve under Pro's root, miss on disk,
	 * fall through to the 'assets/static/' default and 404 — which took the whole
	 * chatbot widget out of Instant Answer.
	 *
	 * @var string
	 */
	protected $asset_base_url;

	/**
	 * @var string
	 */
	protected $asset_base_path;

	public function __construct( $plugin_url, $plugin_path, $version ) {
		parent::__construct( $plugin_url, $plugin_path, $version );

		$this->asset_base_url  = (string) $plugin_url;
		$this->asset_base_path = (string) $plugin_path;
	}

	/**
	 * Resolve the asset sub-folder for THIS instance's plugin root.
	 *
	 * Pro's assets moved to assets/build/ + assets/static/ in 4.1.0, but the
	 * resolver that knows about those folders only exists in Free 4.8.0+. Free
	 * 4.7.0's asset_url()/dist_path() hardcode 'assets/', so Pro 4.1.0 running on
	 * Free 4.7.0 — the ordinary "Pro updated first" state — 404'd on every Pro
	 * asset and rendered the Analytics screen blank. This override keeps Pro (and
	 * anything extending it) correct on both Free versions.
	 *
	 * @param string $filename Relative asset path, e.g. admin/js/analytics.js
	 * @return string One of assets/build/, assets/static/, assets/
	 */
	protected function asset_base( $filename ) {
		if ( '' !== $this->asset_base_path ) {
			foreach ( [ 'assets/build/', 'assets/static/', 'assets/' ] as $base ) {
				if ( file_exists( path_join( $this->asset_base_path, $base . $filename ) ) ) {
					return $base;
				}
			}
		}

		return 'assets/static/';
	}

	public function asset_url( $filename ) {
		if ( filter_var( $filename, FILTER_VALIDATE_URL ) ) {
			return $filename;
		}

		return esc_url( $this->asset_base_url . $this->asset_base( $filename ) . $filename );
	}

	public function dist_path( $file ) {
		return path_join( $this->asset_base_path, $this->asset_base( $file ) . $file );
	}

	/**
	 * Free 4.7.0 has no static_url(), so Pro cannot rely on inheriting it.
	 */
	public function static_url( $filename ) {
		if ( filter_var( $filename, FILTER_VALIDATE_URL ) ) {
			return $filename;
		}

		return esc_url( $this->asset_base_url . 'assets/static/' . $filename );
	}
}
