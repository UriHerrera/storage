<?php
namespace WPDeveloper\BetterDocs\Utils;

class Enqueue extends Base {
	private $plugin_url;
	private $plugin_path;
	private $version;

	public function __construct( $plugin_url, $plugin_path, $version ) {
		$this->plugin_url  = $plugin_url;
		$this->plugin_path = $plugin_path;
		$this->version     = $version;
	}

	public function enqueue( $handle, $filename, $dependencies = [], $args = null ) {
		$config = $this->asset_config( $filename, $dependencies, $args );
		$this->call_wp_func( 'wp_enqueue', $handle, $config );
	}

	public function register( $handle, $filename, $dependencies = [], $args = null ) {
		$config = $this->asset_config( $filename, $dependencies, $args );
		$this->call_wp_func( 'wp_register', $handle, $config );
	}

	public function localize( $handle, $name, $args ) {
		wp_localize_script( $handle, $name, $args );
	}

	private function call_wp_func( $action, $handle, $config ) {
		call_user_func(
			$action . '_' . $config['type'],
			$handle,
			$config['url'],
			$config['dependencies'],
			$config['version'],
			$config['args']
		);

		if ( 'script' === $config['type'] && in_array( 'wp-i18n', $config['dependencies'], true ) ) {
			$url = isset( $config['url'] ) ? $config['url'] : '';
			preg_match( '~/plugins/([^/]+)~', $url, $matches );
			$url_portion = isset( $matches[1] ) ? $matches[1] : '';
			if ( $url_portion == 'betterdocs-pro' ) {
				wp_set_script_translations( $handle, 'betterdocs-pro' );
			} elseif ( $url_portion == 'betterdocs-ai-chatbot' ) {
				wp_set_script_translations( $handle, 'betterdocs-ai-chatbot' );
			} elseif ( $url_portion == 'betterdocs' ) {
				wp_set_script_translations( $handle, 'betterdocs' );
			}
		}
	}

	public function asset_config( $filename, $dependencies = [], $args = null ) {
		$is_js             = preg_match( '/\.js$/', $filename );
		$basename          = preg_replace( '/\.\w+$/', '', $filename );
		$url               = $this->asset_url( $filename );
		$version           = $this->version;
		$asset_config_path = $this->dist_path( $basename . '.asset.php' );

		if ( file_exists( $asset_config_path ) ) {
			$asset_config = require $asset_config_path;

			if ( $is_js ) {
				$dependencies = array_unique( array_merge( $asset_config['dependencies'], $dependencies ) );
			}
			$version = $asset_config['version'];
		} else {
			// webpack only emits `<name>.asset.php` (with its content hash) for JS
			// entries, so stylesheets used to fall through to the plugin version
			// and never cache-busted between builds — browsers kept serving a
			// stale CSS against freshly-changed markup. Fall back to the file's
			// mtime so any rebuilt asset gets a new URL.
			$asset_path = $this->dist_path( $filename );

			if ( file_exists( $asset_path ) ) {
				$version = (string) filemtime( $asset_path );
			}
		}

		return [
			'url'          => $url,
			'dependencies' => $dependencies,
			'version'      => $version,
			'type'         => $is_js ? 'script' : 'style',
			'args'         => null !== $args ? $args : ( $is_js ? true : 'all' )
		];
	}

	/**
	 * Resolve the assets sub-folder for a file: webpack output lives under
	 * assets/build/, committed static assets under assets/static/. Prefer the
	 * build copy when present, otherwise fall back to static. This lets every
	 * enqueue/register call use the same relative path regardless of which
	 * folder the file ends up in.
	 *
	 * The final `assets/` fallback matters for the Free/Pro version skew this
	 * class is shared across. Pro constructs this same Enqueue against its OWN
	 * plugin path, so when Free 4.8.0 runs alongside a Pro that predates the
	 * Node 24 restructure, Pro's files are still flat under assets/. Without
	 * this branch every one of them resolved to a non-existent assets/static/
	 * path and 404'd. Ordering is deliberate: a matched pair never reaches the
	 * legacy branch, so this costs nothing in the normal case.
	 *
	 * Kept `protected` so a paired plugin can refine the search order.
	 */
	protected function asset_base( $filename ) {
		foreach ( [ 'assets/build/', 'assets/static/', 'assets/' ] as $base ) {
			if ( file_exists( path_join( $this->plugin_path, $base . $filename ) ) ) {
				return $base;
			}
		}

		// Nothing on disk — keep the historical default so a genuinely missing
		// file produces the same (broken) URL it always did, not a new shape.
		return 'assets/static/';
	}

	public function asset_url( $filename ) {
		if ( filter_var( $filename, FILTER_VALIDATE_URL ) ) {
			return $filename;
		}

		return esc_url( $this->plugin_url . $this->asset_base( $filename ) . $filename );
	}

	/**
	 * Explicit URL for a committed static asset under assets/static/.
	 */
	public function static_url( $filename ) {
		if ( filter_var( $filename, FILTER_VALIDATE_URL ) ) {
			return $filename;
		}

		return esc_url( $this->plugin_url . 'assets/static/' . $filename );
	}

	public function dist_path( $file ) {
		return path_join( $this->plugin_path, $this->asset_base( $file ) . $file );
	}

	public function icon( $name, $is_admin = false ) {
		$_path = $is_admin ? 'assets/static/admin/images/' : 'assets/static/images/';
		return esc_url( $this->plugin_url . $_path . $name . '?v=' . $this->version );
	}
}
