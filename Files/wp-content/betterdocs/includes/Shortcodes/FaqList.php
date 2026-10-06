<?php
namespace WPDeveloper\BetterDocs\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Utils\Helper;

class FaqList extends Shortcode {
	protected $layout    = 'modern';
	protected $icon_hook = 'betterdocs_faq_post_after';

	public $icon_position = 'after';

	public function get_name() {
		return 'betterdocs_faq_list_modern';
	}

	public function get_style_depends() {
		return [ 'betterdocs-faq' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-faq' ];
	}

	protected $map_view_vars = [
		'class' => 'faq_heading_class'
	];

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return [
			'groups'                      => '',
			'class'                       => '',
			'group_exclude'               => '',
			'faq_heading'                 => __( 'Frequently Asked Questions', 'betterdocs' ),
			'faq_section_title_tag'       => 'h2',
			'faq_group_title_tag'         => 'h3',
			'faq_schema'                  => false,
			'faq_layout'                  => 'layout-1',
			'faq_section_title_color'     => null,
			'include_faq_group'           => '',
			'exclude_faq_group'           => '',
			'faq_group_title_color'       => null,
			'faq_group_title_hover_color' => null,
			'faq_list_color'              => null,
			'faq_content_color'           => null,
			'faq_icon_color'              => null,
			'faq_group_title_typography'  => null,
			'faq_per_page'                => 9,
			'show_button_icon'            => true,
			'button_icon_position'        => 'after',
			'button_color'                => '#528ffe',
			'is_gutenberg'                => false,
			'faq_taxonomy'                => 'betterdocs_faq_category',
			// Per-placement order override. Empty = use the global FAQ Builder
			// order (`betterdocs_faq_order` option). One of: default,
			// most_recent, least_recent, a_to_z, z_to_a, most_questions.
			'order'                       => ''
		];
	}

	/**
	 * Allowed per-placement order keys, mirroring the FAQ Builder header dropdown.
	 *
	 * @var string[]
	 */
	protected $allowed_order_keys = [ 'default', 'most_recent', 'least_recent', 'a_to_z', 'z_to_a', 'most_questions' ];

	public function icons( $faq_toggle ) {
		$faq_markup  = '<svg class="betterdocs-faq-iconminus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' .($faq_toggle ? " style='display:inline;'" : ""). 'stroke-width="2"><g fill="none" stroke="' . esc_attr( $this->attributes['button_color'] ) . '" stroke-linecap="round" stroke-miterlimit="10" stroke-linejoin="round"><path d="M17 12H7"></path><circle cx="12" cy="12" r="11"></circle></g></svg>';
		$faq_markup .= '<svg class="betterdocs-faq-iconplus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'.($faq_toggle ? " style='display:none;'" : "").'><g stroke-width="2" fill="none" stroke="' . esc_attr( $this->attributes['button_color'] ) . '" stroke-linecap="square" stroke-miterlimit="10"><path d="M12 7v10M17 12H7"></path><circle cx="12" cy="12" r="11"></circle></g></svg>';

		echo $faq_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render( $atts, $content = null ) {

		$this->icon_position = isset( $this->atts['button_icon_position'] ) ? $this->atts['button_icon_position'] : '';

		if ( $this->attributes['is_gutenberg'] && $this->attributes['button_icon_position'] == 'before' && $this->attributes['show_button_icon'] ) {
			add_action( 'betterdocs_faq_post_before', [ $this, 'icons' ] );
		} elseif ( $this->attributes['is_gutenberg'] && $this->attributes['button_icon_position'] == 'after' && $this->attributes['show_button_icon'] ) {
			add_action( 'betterdocs_faq_post_after', [ $this, 'icons' ] );
		} else {
			add_action( $this->icon_hook, [ $this, 'icons' ] );
		}

		// When this placement sets a valid `order`, scope it to this render only
		// (via the existing `betterdocs_faq_order_key` filter) so it overrides
		// the global FAQ Builder order without affecting other placements.
		$order_override = is_string( $this->attributes['order'] ) ? trim( $this->attributes['order'] ) : '';
		$order_filter   = null;
		if ( $order_override && in_array( $order_override, $this->allowed_order_keys, true ) ) {
			$order_filter = function () use ( $order_override ) {
				return $order_override;
			};
			add_filter( 'betterdocs_faq_order_key', $order_filter, 20 );
		}

		$this->views( 'shortcodes/faq' );

		if ( $order_filter ) {
			remove_filter( 'betterdocs_faq_order_key', $order_filter, 20 );
		}

		remove_action( $this->icon_hook, [ $this, 'icons' ] );
	}

	public function view_params() {
		$taxonomy    = sanitize_key( $this->attributes['faq_taxonomy'] );
		$terms_query = $this->query->faq_terms_query_args( $this->attributes['groups'], $this->attributes['group_exclude'], [], $taxonomy );

		$wrapper_attr = [
			'class' => [
				'betterdocs-faq-wrapper',
				'layout-' . $this->layout,
				'icon-' . $this->attributes['button_icon_position'],
				$this->attributes['class']
			]
		];

		return wp_parse_args(
			[
				'wrapper_attr'     => $wrapper_attr,
				'widget'           => $this,
				'layout'           => 'list',
				'terms_query_args' => $terms_query,
				'faq_schema'       => $this->attributes['faq_schema'] && ! Helper::seo_plugin_outputs_faq_schema()
			]
		);
	}
}
