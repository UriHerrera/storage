<?php

namespace WPDeveloper\BetterDocs\Editors;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Editors\BlockEditor\FontLoader;
use WPDeveloper\BetterDocs\Editors\BlockEditor\StyleHandler;
use WPDeveloper\BetterDocs\Editors\BlockEditor\TemplatesController;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Patterns\BasePattern;

class BlockEditor extends BaseEditor {

	public function init() {
		betterdocs()->container->get( TemplatesController::class );

		add_action( 'admin_init', [ $this, 'enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

		$_blocks_category_hook = version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ? 'block_categories_all' : 'block_categories';
		add_filter( $_blocks_category_hook, [ $this, 'register_category' ], 9, 1 );

		StyleHandler::get_instance();
		FontLoader::get_instance();

		$this->register_blocks();

		add_action( 'init', [ $this, 'pattern_category' ] );

		$this->pattern_initialization();
	}

	/**
	 * Pattren category registration.
	 * @return void
	 */
	public function pattern_category() {
		register_block_pattern_category(
			'batterdocs',
			[ 'label' => __( 'BetterDocs', 'betterdocs' ) ]
		);
	}

	/**
	 * Get all the Pattrens initialized.
	 * @return void
	 */
	public function pattern_initialization() {
		$_api_classes = scandir( __DIR__ . DIRECTORY_SEPARATOR . 'BlockEditor' . DIRECTORY_SEPARATOR . 'Patterns' );

		if ( ! empty( $_api_classes ) && is_array( $_api_classes ) ) {
			foreach ( $_api_classes as $class ) {
				if ( $class == '.' || $class == '..' || $class == 'BasePattern.php' || strpos( $class, '.' ) === 0 ) {
					continue;
				}

				$classname      = basename( $class, '.php' );
				$classname      = '\\' . __NAMESPACE__ . "\\BlockEditor\\Patterns\\$classname";
				$patterns_class = betterdocs()->container->get( $classname );

				if ( $patterns_class instanceof BasePattern ) {
					$patterns_class->pattern_category();
					$patterns_class->register();
				}
			}
		}
	}

	public function admin_init() {
		$blocks = $this->get_blocks();

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block_name => $block ) {
			if ( isset( $block['object'] ) ) {
				$block_object = betterdocs()->container->get( $block['object'] );

				if ( ! $block_object->can_enable() ) {
					continue;
				}

				$block_object->register_scripts();
			}
		}
	}

	/**
	 * Only for Admin Add/Edit Pages
	 */
	public function enqueue( $hook ) {
		$editor = 'core/edit-post';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $hook === 'site-editor.php' || ( $hook === 'themes.php' && $page === 'gutenberg-edit-site' ) ) {
			$editor = 'core/edit-site';
		}

		$this->assets->register( 'betterdocs-blocks-editor-controls', 'blocks/controls.css' );
		$this->assets->register( 'betterdocs-blocks-editor', 'blocks/style-editor.css', [ 'betterdocs-blocks-editor-controls' ] );
		$this->assets->register( 'betterdocs-blocks-editor', 'blocks/editor.js', [ 'betterdocs-blocks-style-handler' ] );
		$this->assets->enqueue( 'betterdocs-blocks-actions', 'blocks/actions.js', [] );
		$this->assets->localize(
			'betterdocs-blocks-editor',
			'betterDocsBlocksHelper',
			array_merge([
				'is_pro_active'         => betterdocs()->is_pro_active(),
				'is_woocommerce_active' => class_exists( 'WooCommerce' ),
				'resturl'               => get_rest_url(),
				'editorType'            => $editor,
				'betterdocs_glossaries' => Helper::get_glossaries(),
				'docsIcon'              => isset( betterdocs()->settings->get( 'docs_list_icon' )['url'] ) ? betterdocs()->settings->get( 'docs_list_icon' )['url'] : ''
			], betterdocs()->settings->chatbot_active_localize())
		);

		if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'site-editor.php' ) {
			$this->assets->enqueue( 'fontpicker-default-theme', 'vendor/css/fonticonpicker.base-theme.react.css' );
			$this->assets->enqueue( 'fontpicker-material-theme', 'vendor/css/fonticonpicker.material-theme.react.css' );
		}
	}

	/**
	 * Add a block category
	 *
	 * @param $block_categories array
	 *
	 * @return array
	 */
	public function register_category( array $block_categories ): array {
		$categories_slugs = wp_list_pluck( $block_categories, 'slug' );

		return in_array( 'betterdocs', $categories_slugs, true ) ? $block_categories : array_merge(
			[
				[
					'slug'  => 'betterdocs',
					'title' => __( 'Betterdocs', 'betterdocs' )
				]
			],
			$block_categories
		);
	}

	/**
	 * Get Blocks
	 *
	 * @since 2.5.0
	 * @return array<array>
	 */
	public function get_blocks() {
		$config_array = require_once BETTERDOCS_ABSPATH . 'includes/blocks.php';
		return apply_filters( 'betterdocs_blocks_config', $config_array );
	}

	public function register_blocks( $enqueue = false ) {
		$blocks = $this->get_blocks();

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block_name => $block ) {
			if ( isset( $block['object'] ) ) {
				$block_object = betterdocs()->container->get( $block['object'] );

				if ( ! $block_object->can_enable() ) {
					continue;
				}

				if ( method_exists( $block_object, 'load_dependencies' ) ) {
					$block_object->load_dependencies();
				}

				if ( $enqueue && method_exists( $block_object, 'enqueue' ) ) {
					$block_object->enqueue( $this->assets );
					continue;
				}

				if ( method_exists( $block_object, 'inner_blocks' ) ) {
					$_inner_blocks = $block_object->inner_blocks();
					foreach ( $_inner_blocks as $block_name => $block ) {
						$block->register( $this->assets );
					}
				}

				$block_object->register( $this->assets );
			}
		}
	}
}
