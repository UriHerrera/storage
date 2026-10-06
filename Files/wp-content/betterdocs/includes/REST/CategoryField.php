<?php

namespace WPDeveloper\BetterDocs\REST;

use WPDeveloper\BetterDocs\Core\BaseAPI;

class CategoryField extends BaseAPI {
	/**
	 * @return mixed
	 */
	public function register() {
		$this->register_field(
			'doc_category',
			'doc_category_order',
			[
				'get_callback'    => [ $this, 'doc_category_order' ],
				'update_callback' => [ $this, 'update_doc_category_order' ],
				// Explicit schema — otherwise BaseAPI's default (a string "thumbnail
				// URL" schema) applies and the React slide-over's integer order write
				// fails validation with "not of type string".
				'schema'          => [
					'description' => 'BetterDocs doc category display order.',
					'type'        => [ 'integer', 'null' ],
					'context'     => [ 'view', 'edit' ],
					// Skip the built-in type check and coerce to int|null ourselves
					// (a null/empty value is a no-op upstream).
					'arg_options' => [
						'validate_callback' => '__return_true',
						'sanitize_callback' => function ( $value ) {
							return ( null === $value || '' === $value ) ? null : (int) $value;
						},
					],
				],
			]
		);
		$this->register_field(
			'doc_category',
			'thumbnail',
			[
				'get_callback' => [ $this, 'thumbnail_image' ]
			]
		);
		$this->register_field(
			'doc_category',
			'handbookthumbnail',
			[
				'get_callback' => [ $this, 'handbook_thumbnail_image' ]
			]
		);
		$this->register_field(
			'doc_category',
			'subcategories_count',
			[
				'get_callback' => [ $this, 'get_subcategory_count' ]
			]
		);
		$this->register_field(
			'doc_category',
			'total_docs_count',
			[
				'get_callback' => [ $this, 'total_docs_count' ]
			]
		);
		$this->register_field(
			'doc_category',
			'last_updated_time',
			[
				'get_callback' => [ $this, 'last_updated_time' ]
			]
		);
	}

	public function last_updated_time( $object ) {
		$date = betterdocs()->query->latest_updated_date( $object['taxonomy'], $object['slug'] );
		return $date;
	}

	public function doc_category_order( $object ) {
		// Get language-specific meta key with fallback for multilingual sites
		$meta_key = \WPDeveloper\BetterDocs\Utils\Helper::get_meta_key_with_fallback( 'doc_category_order', $object['id'] );
		$doc_category_order = get_term_meta( $object['id'], $meta_key, true );

		// If still no order found, try the base key as final fallback
		if ( '' === $doc_category_order || null === $doc_category_order ) {
			$doc_category_order = get_term_meta( $object['id'], 'doc_category_order', true );
		}

		return ( '' === $doc_category_order || null === $doc_category_order ) ? null : (int) $doc_category_order;
	}

	/**
	 * Persist the display order written from the React Doc Categories slide-over.
	 * Uses the same language-aware meta key as the reader. $term is a WP_Term.
	 */
	public function update_doc_category_order( $value, $term ) {
		if ( null === $value || '' === $value ) {
			return;
		}
		$meta_key = \WPDeveloper\BetterDocs\Utils\Helper::get_meta_key_with_fallback( 'doc_category_order', $term->term_id );
		update_term_meta( $term->term_id, $meta_key, (int) $value );
	}

	public function thumbnail_image( $object ) {
		$attachment_id = get_term_meta( $object['id'], 'doc_category_image-id', true );
		if ( ! $attachment_id ) {
			return;
		}

		return wp_get_attachment_url( $attachment_id );
	}

	public function handbook_thumbnail_image( $object ) {
		$handbook_img_id = get_term_meta( $object['id'], 'doc_category_thumb-id', true );

		if ( ! $handbook_img_id ) {
			return;
		}

		return wp_get_attachment_url( $handbook_img_id );
	}

	public function get_subcategory_count( $object ) {
		$sub_terms_count = count( betterdocs()->query->get_all_child_term_ids( 'doc_category', $object['id'] ) );

		return $sub_terms_count;
	}

	public function total_docs_count( $object ) {
		$docs_count = betterdocs()->query->get_docs_count( (object) $object, false );
		return $docs_count;
	}
}
