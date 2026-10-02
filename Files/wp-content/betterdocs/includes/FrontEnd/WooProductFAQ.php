<?php

namespace WPDeveloper\BetterDocs\FrontEnd;

use WPDeveloper\BetterDocs\Utils\Views;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\FAQBuilder;
use WPDeveloper\BetterDocs\REST\WooProductFAQ as WooFAQSettings;

/**
 * Renders Product FAQ groups on single WooCommerce product pages.
 *
 * Placement, layout and schema come from the display settings saved by the
 * WooCommerce → Display Settings tab. Each Product FAQ group declares which
 * product categories and which individual products it appears on (set in the
 * Create/Update FAQ Group screen). A product shows every group assigned to it
 * directly, plus every group assigned to one of its product categories.
 */
class WooProductFAQ {
	/**
	 * @var Views
	 */
	protected $views;

	/**
	 * Resolved display settings.
	 *
	 * @var array
	 */
	protected $settings = [];

	/**
	 * Per-request cache of resolved group IDs, keyed by product ID.
	 *
	 * @var array
	 */
	protected $resolved = [];

	/**
	 * Per-request render guard, keyed by product ID. Shared across the
	 * automatic placement hooks and the FAQ block/Elementor widget
	 * (product_assignments mode) so the FAQ renders at most once per product
	 * even when both placement paths are active.
	 *
	 * @var array
	 */
	protected static $rendered = [];

	/**
	 * Layout being rendered for the current product, so icons() can emit the
	 * matching per-layout accordion icon (mirroring the FAQ shortcodes).
	 *
	 * @var string
	 */
	protected $render_layout = 'layout-1';

	public function __construct( Views $views ) {
		$this->views = $views;
		add_action( 'init', [ $this, 'init' ], 20 );
	}

	/**
	 * Wire the placement hooks once WooCommerce is loaded and the feature is on.
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->settings = WooFAQSettings::get_display_settings();

		if ( empty( $this->settings['enable'] ) ) {
			return;
		}

		switch ( $this->settings['placement'] ) {
			case 'before_summary':
				add_action( 'woocommerce_before_single_product_summary', [ $this, 'render_hooked' ], 25 );
				break;
			case 'after_summary':
				add_action( 'woocommerce_after_single_product_summary', [ $this, 'render_hooked' ], 15 );
				break;
			case 'product_tab':
			default:
				add_filter( 'woocommerce_product_tabs', [ $this, 'add_product_tab' ] );
				break;
		}
	}

	/**
	 * Product FAQ group term IDs to display for a product, in priority order
	 * (first non-empty wins): groups assigned to the product directly override
	 * groups inherited from the product's categories. A product with no matching
	 * group shows nothing. Returns a list of term IDs (may be empty).
	 *
	 * @param int $product_id
	 * @return int[]
	 */
	public function get_group_ids_for_product( $product_id ) {
		$product_id = (int) $product_id;

		if ( isset( $this->resolved[ $product_id ] ) ) {
			return $this->resolved[ $product_id ];
		}

		$product_cats = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
		$product_cats = is_wp_error( $product_cats ) ? [] : array_map( 'intval', $product_cats );

		$groups = get_terms( [
			'taxonomy'   => 'betterdocs_product_faq_category',
			'hide_empty' => false,
		] );

		$direct    = []; // groups assigned to this product directly
		$inherited = []; // groups assigned via one of the product's categories
		$all       = []; // groups flagged to show on every product
		if ( ! is_wp_error( $groups ) && ! empty( $groups ) ) {
			// QA-014: prime term meta for every group in a single query so the
			// per-group get_term_meta() lookups below read from cache instead of
			// issuing O(N) queries (100+ on a 50-group store).
			update_meta_cache( 'term', wp_list_pluck( $groups, 'term_id' ) );

			foreach ( $groups as $group ) {
				// Respect the group's enable/disable toggle (term meta 'status').
				// '0' means the user disabled it in the builder, so it must not
				// render on the product page (even if flagged "all products").
				// An unset/empty status is treated as enabled — the default
				// applied when a group is created.
				if ( '0' === (string) get_term_meta( $group->term_id, 'status', true ) ) {
					continue;
				}

				// Groups flagged to show on every product.
				if ( get_term_meta( $group->term_id, FAQBuilder::GROUP_ALL_PRODUCTS_META, true ) ) {
					$all[] = (int) $group->term_id;
					continue;
				}

				$assigned_products = get_term_meta( $group->term_id, FAQBuilder::GROUP_PRODUCTS_META, true );
				$assigned_products = is_array( $assigned_products ) ? array_map( 'intval', $assigned_products ) : [];

				$assigned_cats = get_term_meta( $group->term_id, FAQBuilder::GROUP_PRODUCT_CATS_META, true );
				$assigned_cats = is_array( $assigned_cats ) ? array_map( 'intval', $assigned_cats ) : [];

				if ( in_array( $product_id, $assigned_products, true ) ) {
					$direct[] = (int) $group->term_id;
				} elseif ( array_intersect( $product_cats, $assigned_cats ) ) {
					$inherited[] = (int) $group->term_id;
				}
			}
		}

		// Per-product assignment takes priority; fall back to category inheritance.
		// "All products" groups always show, on top of whatever else matched.
		$matched = ! empty( $direct ) ? $direct : $inherited;
		$matched = array_merge( $matched, $all );

		// Optional extension point for add-ons to amend the resolved groups.
		$matched = apply_filters( 'betterdocs_woo_product_faq_group_ids', $matched, $product_id );

		return $this->resolved[ $product_id ] = $this->sanitize_ids( $matched );
	}

	/**
	 * Whether any of the given FAQ groups contains at least one *published* FAQ.
	 *
	 * A group can be assigned to a product (directly or via its category) while
	 * every FAQ inside it is still a draft. The display query only pulls
	 * published FAQs, so in that case the tab/section would render with just its
	 * title and no content. Use this to suppress the tab/section entirely until
	 * there is something published to show.
	 *
	 * @param int[] $group_ids
	 * @return bool
	 */
	protected function groups_have_published_faqs( $group_ids ) {
		$group_ids = $this->sanitize_ids( $group_ids );
		if ( empty( $group_ids ) ) {
			return false;
		}

		// Group term IDs are globally unique across both FAQ taxonomies; an OR
		// query matches whichever taxonomy each ID belongs to (mismatched IDs are
		// simply ignored).
		$query = new \WP_Query( [
			'post_type'      => 'betterdocs_faq',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => [
				'relation' => 'OR',
				[
					'taxonomy' => 'betterdocs_faq_category',
					'field'    => 'term_id',
					'terms'    => $group_ids,
				],
				[
					'taxonomy' => 'betterdocs_product_faq_category',
					'field'    => 'term_id',
					'terms'    => $group_ids,
				],
			],
		] );

		return $query->have_posts();
	}

	/**
	 * Normalize a list of group IDs to unique positive integers.
	 *
	 * @param mixed $ids
	 * @return int[]
	 */
	protected function sanitize_ids( $ids ) {
		$ids = array_map( 'intval', (array) $ids );
		$ids = array_filter( $ids, function ( $id ) {
			return $id > 0;
		} );
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Add the FAQ tab to the single product tabs.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_product_tab( $tabs ) {
		global $product;
		$product_id = $product ? $product->get_id() : 0;

		$group_ids = $this->get_group_ids_for_product( $product_id );
		if ( empty( $group_ids ) || ! $this->groups_have_published_faqs( $group_ids ) ) {
			return $tabs;
		}

		$tabs['betterdocs_product_faq'] = [
			'title'    => $this->settings['tab_title'],
			'priority' => 50,
			'callback' => [ $this, 'render_tab' ],
		];

		return $tabs;
	}

	/**
	 * Tab callback.
	 */
	public function render_tab() {
		global $product;
		$this->render( $product ? $product->get_id() : 0 );
	}

	/**
	 * Action callback for the before/after summary placements.
	 */
	public function render_hooked() {
		global $product;
		$this->render( $product ? $product->get_id() : 0 );
	}

	/**
	 * Thin wrapper used by the automatic placement hooks (tab / before / after
	 * summary). Delegates to the guarded renderer.
	 *
	 * @param int $product_id
	 */
	public function render( $product_id ) {
		$this->render_for_product( $product_id );
	}

	/**
	 * Render the product FAQ markup, at most once per product per request.
	 *
	 * Shared entry point for both the automatic placement hooks and the FAQ
	 * block / Elementor widget (product_assignments mode). The render guard
	 * means whichever path runs first wins; subsequent calls for the same
	 * product are no-ops, preventing duplicate output.
	 *
	 * @param int         $product_id
	 * @param string|null $layout Layout override (layout-1..3). Null uses the
	 *                            layout from display settings.
	 */
	public function render_for_product( $product_id, $layout = null ) {
		$product_id = (int) $product_id;

		if ( ! empty( self::$rendered[ $product_id ] ) ) {
			return;
		}

		$group_ids = $this->get_group_ids_for_product( $product_id );
		if ( empty( $group_ids ) || ! $this->groups_have_published_faqs( $group_ids ) ) {
			return;
		}

		self::$rendered[ $product_id ] = true;

		// $this->settings is populated by init() when the feature is enabled;
		// fall back to the saved settings when a block/widget renders while
		// automatic placement is off.
		$settings = ! empty( $this->settings ) ? $this->settings : WooFAQSettings::get_display_settings();

		if ( $layout === null ) {
			$layout = $settings['layout'];
		}

		$allowed = [ 'layout-1', 'layout-2', 'layout-3' ];
		$layout  = sanitize_key( (string) $layout );
		if ( ! in_array( $layout, $allowed, true ) ) {
			$layout = 'layout-1';
		}

		wp_enqueue_style( 'betterdocs-faq' );
		wp_enqueue_script( 'betterdocs-faq' );

		// Render the accordion icon that matches the chosen layout, in the same
		// position the layout's shortcode uses: Classic places it before the
		// question, Modern/Abstract after.
		$this->render_layout = $layout;
		$icon_hook           = ( 'layout-2' === $layout ) ? 'betterdocs_faq_post_before' : 'betterdocs_faq_post_after';
		add_action( $icon_hook, [ $this, 'icons' ] );

		$this->views->get( 'woocommerce/product-faq', [
			'group_ids'     => $group_ids,
			'layout'        => $layout,
			'enable_schema' => ! empty( $settings['enable_schema'] ) && ! Helper::seo_plugin_outputs_faq_schema(),
		] );

		remove_action( $icon_hook, [ $this, 'icons' ] );
	}

	/**
	 * Remove the automatic placement hooks. Called by the FAQ block / Elementor
	 * widget when they render the product FAQ themselves, so the same FAQ is not
	 * also injected into the product tab / summary.
	 */
	public function suppress_auto_placement() {
		remove_action( 'woocommerce_before_single_product_summary', [ $this, 'render_hooked' ], 25 );
		remove_action( 'woocommerce_after_single_product_summary', [ $this, 'render_hooked' ], 15 );
		remove_filter( 'woocommerce_product_tabs', [ $this, 'add_product_tab' ] );
	}

	/**
	 * Accordion plus/minus icons, mirroring the FAQ shortcode markup so the
	 * shared faq.js toggle works.
	 *
	 * @param bool $faq_toggle
	 */
	public function icons( $faq_toggle ) {
		switch ( $this->render_layout ) {
			case 'layout-2': // Classic — mirrors FaqClassic::icons().
				$markup  = '<div class="betterdocs-faq-post-icon-group">';
				$markup .= '<svg class="betterdocs-faq-iconplus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' . ( $faq_toggle ? " style='display:none;'" : '' ) . '><path fill="#000000" d="M18 10h-4V6h-4v4H6v4h4v4h4v-4h4"></path></svg>';
				$markup .= '<svg class="betterdocs-faq-iconminus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' . ( $faq_toggle ? " style='display:inline;'" : '' ) . '><path fill="#000000" d="M6 10h12v4H6z"></path></svg>';
				$markup .= '</div>';
				break;

			case 'layout-3': // Abstract — mirrors FaqLayoutThree::icons().
				$markup  = '<svg class="betterdocs-faq-iconplus" width="21" height="20" viewBox="0 0 21 20"' . ( $faq_toggle ? " style='display:none;'" : '' ) . ' fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_8028_2975)"><path d="M5.5 7.5L10.5 12.5L15.5 7.5" stroke="#707E95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_8028_2975"><rect width="20" height="20" fill="white" transform="translate(0.5)"/></clipPath></defs></svg>';
				$markup .= '<svg class="betterdocs-faq-iconminus" width="21" height="20" viewBox="0 0 21 20"' . ( $faq_toggle ? " style='display:inline;'" : '' ) . ' fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 12.5L10.5 7.5L5.5 12.5" stroke="#707E95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
				break;

			case 'layout-1': // Modern — mirrors FaqList::icons() (default button colour).
			default:
				$markup  = '<svg class="betterdocs-faq-iconminus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' . ( $faq_toggle ? " style='display:inline;'" : '' ) . ' stroke-width="2"><g fill="none" stroke="#528ffe" stroke-linecap="round" stroke-miterlimit="10" stroke-linejoin="round"><path d="M17 12H7"></path><circle cx="12" cy="12" r="11"></circle></g></svg>';
				$markup .= '<svg class="betterdocs-faq-iconplus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' . ( $faq_toggle ? " style='display:none;'" : '' ) . '><g stroke-width="2" fill="none" stroke="#528ffe" stroke-linecap="square" stroke-miterlimit="10"><path d="M12 7v10M17 12H7"></path><circle cx="12" cy="12" r="11"></circle></g></svg>';
				break;
		}

		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
