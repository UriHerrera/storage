<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_Query;
use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;

/**
 * REST endpoints backing the WooCommerce "Product FAQ" tab.
 *
 * Provides the option sources used by the Create/Update Product FAQ Group screen
 * (product categories + product search) and the Display Settings read/write.
 * Group assignments themselves are stored on each Product FAQ group term and
 * saved through the FAQ Builder create/update category endpoints.
 */
class WooProductFAQ extends BaseAPI {
	/**
	 * Option key holding the WooCommerce FAQ display settings.
	 */
	const DISPLAY_OPTION = 'betterdocs_woo_faq_display';

	/**
	 * Default display settings. Shared with the frontend renderer (Step 7) so
	 * saved + unsaved sites behave identically.
	 *
	 * @return array
	 */
	public static function display_defaults() {
		return [
			'enable'        => true,
			'placement'     => 'product_tab',
			'tab_title'     => __( 'FAQ', 'betterdocs' ),
			'layout'        => 'layout-3',
			'enable_schema' => true,
		];
	}

	/**
	 * Saved display settings merged over the defaults.
	 *
	 * @return array
	 */
	public static function get_display_settings() {
		$saved = get_option( self::DISPLAY_OPTION, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return wp_parse_args( $saved, self::display_defaults() );
	}

	/**
	 * Admin-only: assignments are part of the FAQ management surface.
	 */
	public function permission_check() {
		return current_user_can( 'edit_others_posts' );
	}

	public function register() {
		// The whole tab is WooCommerce-only — no point exposing the routes
		// when WooCommerce (and therefore product_cat) is absent.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->get( 'woo-product-faq/product-categories', [ $this, 'get_product_categories' ], [
			'search' => [ 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
		] );

		// Note: deliberately NOT 'product-search' — Pro registers that path. Use
		// a distinct route so the two never collide while Pro is active.
		$this->get( 'woo-product-faq/assignable-products', [ $this, 'search_products' ], [
			'q'       => [ 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
			'include' => [ 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
		] );

		$this->get( 'woo-product-faq/display-settings', [ $this, 'get_display_settings_route' ] );

		$this->post( 'woo-product-faq/display-settings', [ $this, 'save_display_settings' ], [
			'settings' => [ 'type' => 'object', 'required' => true ],
		] );
	}

	/**
	 * GET /woo-product-faq/product-categories
	 *
	 * WooCommerce product categories as selector options ({ id, name }) for the
	 * Create/Update Product FAQ Group screen. Optional `search` narrows by name.
	 */
	public function get_product_categories( WP_REST_Request $request ) {
		$search = (string) $request->get_param( 'search' );

		$args = [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => 100,
		];
		if ( '' !== $search ) {
			$args['search'] = $search;
		}

		// Drop WooCommerce's default "Uncategorized" product category — it is a
		// placeholder, not a real category to assign a FAQ group to.
		$default_cat = (int) get_option( 'default_product_cat', 0 );
		if ( $default_cat ) {
			$args['exclude'] = [ $default_cat ];
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			$terms = [];
		}

		$data = array_values( array_map( function ( $term ) {
			return [
				'id'   => (int) $term->term_id,
				'name' => $term->name,
			];
		}, $terms ) );

		return $this->success( $data );
	}

	/**
	 * GET /woo-product-faq/product-search
	 *
	 * Searchable product lookup for the Products selector. Pass `q` to search by
	 * title, or `include` (comma-separated IDs) to resolve specific products to
	 * { id, name } for prefilling the chips when editing a group.
	 */
	public function search_products( WP_REST_Request $request ) {
		$include = array_filter( array_map( 'intval', explode( ',', (string) $request->get_param( 'include' ) ) ) );

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		];

		if ( ! empty( $include ) ) {
			$args['post__in']       = $include;
			$args['posts_per_page'] = count( $include );
			$args['orderby']        = 'post__in';
		} else {
			$args['s'] = (string) $request->get_param( 'q' );
		}

		$query = new WP_Query( $args );

		$results = array_map( function ( $product ) {
			return [
				'id'   => (int) $product->ID,
				'name' => $product->post_title,
			];
		}, $query->posts );

		return $this->success( $results );
	}

	/**
	 * GET /woo-product-faq/display-settings
	 */
	public function get_display_settings_route( WP_REST_Request $request ) {
		return $this->success( self::get_display_settings() );
	}

	/**
	 * POST /woo-product-faq/display-settings
	 *
	 * Persist the WooCommerce FAQ display settings (sanitized against the known
	 * keys; unknown keys are dropped).
	 */
	public function save_display_settings( WP_REST_Request $request ) {
		$incoming = (array) $request->get_param( 'settings' );
		$defaults = self::display_defaults();

		$allowed_placements = [ 'product_tab', 'after_summary', 'before_summary' ];
		$allowed_layouts    = [ 'layout-1', 'layout-2', 'layout-3' ];

		$placement = isset( $incoming['placement'] ) ? sanitize_key( $incoming['placement'] ) : $defaults['placement'];
		if ( ! in_array( $placement, $allowed_placements, true ) ) {
			$placement = $defaults['placement'];
		}

		$layout = isset( $incoming['layout'] ) ? sanitize_key( $incoming['layout'] ) : $defaults['layout'];
		if ( ! in_array( $layout, $allowed_layouts, true ) ) {
			$layout = $defaults['layout'];
		}

		$tab_title = isset( $incoming['tab_title'] ) ? sanitize_text_field( $incoming['tab_title'] ) : $defaults['tab_title'];
		if ( '' === $tab_title ) {
			$tab_title = $defaults['tab_title'];
		}

		$settings = [
			'enable'        => ! empty( $incoming['enable'] ),
			'placement'     => $placement,
			'tab_title'     => $tab_title,
			'layout'        => $layout,
			'enable_schema' => ! empty( $incoming['enable_schema'] ),
		];

		update_option( self::DISPLAY_OPTION, $settings );

		return $this->success( $settings );
	}
}
