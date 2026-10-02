<?php
namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;
use WPDeveloper\BetterDocs\FrontEnd\WooProductFAQ as WooFAQRenderer;

class FAQ extends Block {
	protected $editor_scripts = [
		'betterdocs-faq',
		'betterdocs-blocks-editor'
	];

	protected $editor_styles = [
		'betterdocs-faq',
		'betterdocs-blocks-editor'
	];

	protected $frontend_styles = [
		'betterdocs-faq'
	];

	protected $frontend_scripts = [
		'betterdocs-faq'
	];

	/**
	 * unique name of block
	 * @return string
	 */
	public function get_name() {
		return 'faq';
	}

	public function get_default_attributes() {
		return [
			'blockId'                 => '',
			'resOption'               => 'Desktop',
			'blockRoot'               => 'better_docs',
			'blockMeta'               => null,
			'faqLayout'               => 'layout-1',
			'faqSectionText'          => 'Frequently Asked Questions',
			'faqSectionTitleTag'      => 'h2',
			'faqGroupTitleTag'        => 'h3',
			'faqSectionTitleColor'    => null,
			// JSON string of `[{value:int, label:string}]`; a bare id array (`[5]`) is also accepted since 4.9.0.
			'includeFaqGroup'         => '',
			'excludeFaqGroup'         => '',
			'faqGroupTitleColor'      => null,
			'faqGroupTitleHoverColor' => null,
			'faqListColor'            => null,
			'faqContentColor'         => null,
			'faqIconColor'            => null,
			'faqGroupTitleTypography' => null,
			'faqPerPage'              => 9,

			'showButtonIcon'          => true,
			'buttonColor'             => '#528ffe',
			'faqTaxonomy'             => 'betterdocs_faq_category'
		];
	}

	public function render( $attributes, $content ) {
		if ( ( $this->attributes['faqTaxonomy'] ?? '' ) === 'product_assignments' ) {
			$this->render_product_assignments();
			return;
		}

		add_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_sequence' ], 10, 4 );
		$this->views( 'layouts/faq' );
		remove_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_sequence' ], 10 );
	}

	private function render_product_assignments() {
		if ( ! class_exists( 'WooCommerce' ) || ! is_product() ) {
			return;
		}

		// In FSE templates, top-level blocks render outside the loop, so
		// get_the_ID() can be 0 — fall back to the queried product.
		$product_id = get_the_ID();
		if ( ! $product_id ) {
			$product_id = get_queried_object_id();
		}
		if ( ! $product_id ) {
			return;
		}

		/** @var WooFAQRenderer $renderer */
		$renderer = betterdocs()->container->get( WooFAQRenderer::class );

		// This block is the explicit placement; don't also inject into the tab/summary.
		$renderer->suppress_auto_placement();
		$renderer->render_for_product( $product_id, $this->attributes['faqLayout'] ?? null );
	}

	/**
	 * Flatten a group-selection attribute into a comma separated list of term ids.
	 *
	 * The attribute is a JSON string. The editor writes
	 * `[{"value":5,"label":"Install"}]`; REST clients and other programmatic
	 * writers commonly write the bare form `[5]`. Both are accepted — before
	 * 4.9.0 the bare form flattened to an empty string, which
	 * `Query::faq_terms_query_args()` reads as "no filter", so every group
	 * rendered and the block warned on every view.
	 *
	 * @since 4.9.0 Bare ids are accepted alongside `{ value, label }` objects;
	 *              ids are cast with `absint()` and de-duplicated.
	 *
	 * @param mixed $json JSON encoded list of term ids or `{ value, label }` objects.
	 *
	 * @return string Comma separated term ids, or '' when there is nothing to filter by.
	 */
	public function get_groups_ids( $json ) {
		$data = is_string( $json ) ? json_decode( $json, true ) : $json;
		$ids  = array_filter( self::normalize_id_list( $data ), 'is_numeric' );
		$ids  = array_unique( array_map( 'absint', $ids ) );

		return implode( ',', $ids );
	}

	public function view_params() {
		$attributes = &$this->attributes;

		// Sanitize the layout attribute to prevent path traversal (LFI).
		$attributes['faqLayout'] = sanitize_file_name( $attributes['faqLayout'] );
		$exclude    = $this->get_groups_ids( $attributes['excludeFaqGroup'] );
		$include    = $this->get_groups_ids( $attributes['includeFaqGroup'] );
		$taxonomy   = sanitize_key( $attributes['faqTaxonomy'] ?? 'betterdocs_faq_category' );

		$terms_query = betterdocs()->query->faq_terms_query_args( $include, $exclude, [], $taxonomy );

		return wp_parse_args(
			[
				'enable'           => true,
				'have_posts'       => true,
				'widget'           => $this,
				'layout'           => $attributes['faqLayout'],
				'terms_query_args' => $terms_query,
				'shortcode_attr'   => [
					'group_exclude'               => $exclude,
					'class'                       => 'betterdocs-faq-' . $attributes['faqLayout'] . ' ' . $attributes['blockId'],
					'groups'                      => $include,
					'is_gutenberg'                => true,
					'faq_heading'                 => $attributes['faqSectionText'],
					'faq_section_title_tag'       => $attributes['faqSectionTitleTag'],
					'faq_group_title_tag'         => $attributes['faqGroupTitleTag'],
					'faq_section_title_color'     => $attributes['faqSectionTitleColor'],
					'include_faq_group'           => $this->string_to_array( $attributes['includeFaqGroup'] ),
					'exclude_faq_group'           => $this->string_to_array( $attributes['excludeFaqGroup'] ),
					'faq_group_title_color'       => $attributes['faqGroupTitleColor'],
					'faq_group_title_hover_color' => $attributes['faqGroupTitleHoverColor'],
					'faq_list_color'              => $attributes['faqListColor'],
					'faq_content_color'           => $attributes['faqContentColor'],
					'faq_icon_color'              => $attributes['faqIconColor'],
					'faq_group_title_typography'  => $attributes['faqGroupTitleTypography'],
					'faq_per_page'                => $attributes['faqPerPage'],
					'show_button_icon'            => $attributes['showButtonIcon'],
					'button_icon_position'        => $attributes['faqLayout'] == 'layout-1' || $attributes['faqLayout'] == 'layout-3' || $attributes['faqLayout'] == 'layout-4' ? 'after' : 'before',
					'button_color'                => $attributes['buttonColor'],
					'faq_taxonomy'                => $taxonomy,
					// Per-block order; empty falls back to the global FAQ order.
					'order'                       => isset( $attributes['faqOrder'] ) ? $attributes['faqOrder'] : ''
				]

			]
		);
	}

	public function header_sequence( $_layout_sequence, $layout, $widget_type, $_defined_vars ) {
		$_new_layout_sequence = [
			[
				'class'    => 'betterdocs-category-title-counts',
				'sequence' => [ 'category_title', 'category_counts' ]
			],
			'category_description'
		];

		return $_new_layout_sequence;
	}
}
