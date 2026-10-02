<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor;

use WPDeveloper\BetterDocs\Core\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Utils\Base;

class Helper extends Base {
	/**
	 * Summary of query
	 * @var Query
	 */
	private $query;

	public function __construct( Query $query ) {
		$this->query = $query;
	}

	public function orderby_options() {
		$orderby = [
			'ID'               => 'Docs ID',
			'author'           => 'Docs Author',
			'title'            => 'Title',
			'date'             => 'Date',
			'modified'         => 'Last Modified Date',
			'parent'           => 'Parent Id',
			'rand'             => 'Random',
			'comment_count'    => 'Comment Count',
			'menu_order'       => 'Menu Order',
			'betterdocs_order' => 'BetterDocs Order'
		];

		return $orderby;
	}

	/**
	 * Get Post Categories
	 *
	 * @return array
	 */
	public function get_terms( $taxonomy = 'category', $key = 'term_id' ) {
		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => true
			]
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				// Decode HTML entities (e.g. "&amp;" → "&") so the select2 control
				// shows the real term name instead of the escaped entity.
				$options[ $term->{$key} ] = wp_specialchars_decode( $term->name, ENT_QUOTES );
			}
		}

		return $options;
	}

	public function get_faq_terms() {
		return $this->query->get_faq_terms();
	}
}
