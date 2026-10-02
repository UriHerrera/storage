<?php
namespace WPDeveloper\BetterDocs\Traits;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


trait CategoryBox {
	public function header_sequence( $_layout_sequence, $layout, $widget_type, $_defined_vars ) {
		if ( $layout === 'layout-2' ) {
			$_layout_sequence = [ 'category_icon', 'category_title', 'category_counts' ];
		}

		if ( $layout === 'layout-3' ) {
			$_layout_sequence = [
				'category_icon',
				[
					'class'    => 'betterdocs-category-title-counts',
					'sequence' => [ 'category_title', 'category_description', 'category_counts' ]
				]
			];
		}

		if ( $layout === 'layout-4' ) {
			$_layout_sequence = [
				'category_icon',
				[
					'class'    => 'betterdocs-category-title-counts',
					'sequence' => [ 'category_title', 'category_description', 'sub_category_counts', 'last_update' ]
				]
			];
		}

		return $_layout_sequence;
	}

	public function layout_filename( $filename, $origin_layout ) {
		return $origin_layout == 'layout-4' || $origin_layout === 'layout-3' || $origin_layout === 'layout-2' ? 'default' : $filename;
	}

	public function add_filter( $eligible ) {
		if ( ! $eligible ) {
			return;
		}

		add_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_sequence' ], 10, 4 );
		add_filter( 'betterdocs_layout_filename', [ $this, 'layout_filename' ], 10, 2 );
	}

	public function remove_filter( $eligible ) {
		if ( ! $eligible ) {
			return;
		}

		remove_filter( 'betterdocs_header_layout_sequence', [ $this, 'header_sequence' ], 10 );
		remove_filter( 'betterdocs_layout_filename', [ $this, 'layout_filename' ], 10 );
	}
}
