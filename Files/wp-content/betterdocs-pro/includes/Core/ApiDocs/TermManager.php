<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * doc_category term management for materialized references.
 *
 * Structure: one parent term per reference (named after it), one child term
 * per OpenAPI tag. Terms WE create carry `_bd_api_ref_id` term meta so pruning
 * can never touch user-created terms — an existing term with a matching name
 * is reused but never marked (and therefore never pruned/deleted by us).
 */
class TermManager {
	const TAXONOMY = 'doc_category';
	const KB_TAXONOMY = 'knowledge_base';

	/**
	 * @var string Current run's knowledge-base slug ('' = MKB off / none).
	 */
	protected $kb_slug = '';

	/**
	 * @var int Current run's knowledge-base term id.
	 */
	protected $kb_term_id = 0;

	/**
	 * On Multiple-KB sites, categories are only visible when they carry a
	 * `doc_category_knowledge_base` assignment and docs carry the
	 * `knowledge_base` term — so each materialized reference gets (or reuses)
	 * its own knowledge base. Call once per run, before term creation.
	 *
	 * @param \WP_Post $reference
	 * @return int KB term id (0 when MKB is off / taxonomy missing).
	 */
	public function prepare_knowledge_base( \WP_Post $reference ) {
		$this->kb_slug    = '';
		$this->kb_term_id = 0;

		if ( ! taxonomy_exists( self::KB_TAXONOMY ) || ! betterdocs()->settings->get( 'multiple_kb' ) ) {
			return 0;
		}

		/**
		 * Override the knowledge base generated endpoint docs are assigned to
		 * (return an existing KB slug, or '' to skip KB assignment entirely).
		 *
		 * @param string|null $kb_slug   Null = create/reuse one named after the reference.
		 * @param \WP_Post    $reference
		 */
		$override = apply_filters( 'betterdocs_api_docs_knowledge_base', null, $reference );

		if ( '' === $override ) {
			return 0;
		}

		if ( is_string( $override ) && null !== $override ) {
			$term = get_term_by( 'slug', $override, self::KB_TAXONOMY );

			if ( $term instanceof \WP_Term ) {
				$this->kb_slug    = $term->slug;
				$this->kb_term_id = (int) $term->term_id;

				// A filter-supplied KB still needs an order: it may itself have
				// been created by another materialize run rather than the admin.
				$this->ensure_kb_order( $this->kb_term_id );

				return $this->kb_term_id;
			}
		}

		$existing = term_exists( $reference->post_title, self::KB_TAXONOMY );

		if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
			$term_id = (int) $existing['term_id'];
		} else {
			// Slug is deliberately suffixed: a KB slug identical to the
			// reference slug collides with the permalink parser and turns the
			// reference's explorer page into a KB archive.
			$created = wp_insert_term(
				$reference->post_title,
				self::KB_TAXONOMY,
				[ 'slug' => $reference->post_name . '-docs' ]
			);

			if ( is_wp_error( $created ) ) {
				return 0;
			}

			$term_id = (int) $created['term_id'];
			update_term_meta( $term_id, '_bd_api_ref_id', (int) $reference->ID );
		}

		$this->ensure_kb_order( $term_id );

		$term = get_term( $term_id, self::KB_TAXONOMY );

		if ( $term instanceof \WP_Term ) {
			$this->kb_slug    = $term->slug;
			$this->kb_term_id = $term_id;
		}

		return $this->kb_term_id;
	}

	/**
	 * Give a knowledge base a `kb_order` — the KB counterpart of
	 * ensure_category_order().
	 *
	 * BetterDocs' default term ordering (`betterdocs_order`) resolves to
	 * `meta_key => 'kb_order'` for `knowledge_base` (Free's Query::terms_query),
	 * and a `meta_key` on get_terms() is an INNER JOIN — so a KB with no
	 * `kb_order` row is dropped from the /docs/ landing grid entirely, however
	 * many published docs it holds. Pro's own backfill
	 * (MultipleKB::default_term_order) only runs on admin screens, so a KB
	 * created by a REST-driven materialize run stays invisible until someone
	 * happens to open the KB admin page.
	 *
	 * @param int $term_id
	 * @return void
	 */
	protected function ensure_kb_order( $term_id ) {
		if ( '' !== (string) get_term_meta( $term_id, 'kb_order', true ) ) {
			return;
		}

		global $wpdb;

		$max = (int) $wpdb->get_var(
			"SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->termmeta} WHERE meta_key = 'kb_order'"
		);

		update_term_meta( $term_id, 'kb_order', $max + 1 );
	}

	/**
	 * Assign the run's knowledge base to a generated doc (no-op when none).
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function assign_doc_kb( $post_id ) {
		if ( $this->kb_term_id ) {
			wp_set_object_terms( $post_id, [ $this->kb_term_id ], self::KB_TAXONOMY );
		}
	}

	/**
	 * Stamp the MKB visibility meta on a generated doc_category term
	 * (value shape mirrors core BetterDocs: ['', '<kb-slug>']).
	 *
	 * @param int $term_id
	 * @return void
	 */
	protected function stamp_term_kb( $term_id ) {
		if ( '' !== $this->kb_slug ) {
			update_term_meta( $term_id, 'doc_category_knowledge_base', [ '', $this->kb_slug ] );
		}
	}

	/**
	 * Remove the reference's generated knowledge-base term when it holds no
	 * docs anymore (unmaterialize / reference delete).
	 *
	 * @param int $reference_id
	 * @return void
	 */
	public function prune_generated_kb( $reference_id ) {
		if ( ! taxonomy_exists( self::KB_TAXONOMY ) ) {
			return;
		}

		$terms = get_terms(
			[
				'taxonomy'   => self::KB_TAXONOMY,
				'hide_empty' => false,
				'meta_key'   => '_bd_api_ref_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $reference_id // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		foreach ( is_wp_error( $terms ) ? [] : $terms as $term ) {
			$has_posts = get_posts(
				[
					'post_type'      => 'docs',
					// Bookkeeping lookup: must see docs regardless of who is asking.
					'betterdocs_bypass_restrictions' => true,
					'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => self::KB_TAXONOMY,
							'terms'    => (int) $term->term_id
						]
					]
				]
			);

			if ( empty( $has_posts ) ) {
				wp_delete_term( $term->term_id, self::KB_TAXONOMY );
			}
		}
	}

	/**
	 * Sweep generated terms whose reference post no longer exists.
	 *
	 * `prune_empty_generated_terms()` / `prune_generated_kb()` are both keyed to
	 * one reference id, so a term is only ever reachable while its reference is.
	 * If a reference row is hard-deleted without the delete handler completing
	 * (older schema, a failed run, direct DB removal), its terms are stranded:
	 * they still carry `_bd_api_ref_id` but nothing will ever look them up
	 * again, and they surface as stray ROOT categories in every KB sidebar.
	 *
	 * Only empty terms are removed — a stranded term still holding docs is left
	 * alone, since deleting it would silently orphan real content.
	 *
	 * @return int[] Deleted term ids.
	 */
	public function prune_orphaned_terms() {
		$deleted = [];

		foreach ( [ self::TAXONOMY, self::KB_TAXONOMY ] as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'meta_key'   => '_bd_api_ref_id' // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				]
			);

			foreach ( is_wp_error( $terms ) ? [] : $terms as $term ) {
				$reference_id = (int) get_term_meta( $term->term_id, '_bd_api_ref_id', true );

				// get_post() rather than a post_status check: a TRASHED
				// reference is still restorable and must keep its terms.
				if ( $reference_id && get_post( $reference_id ) instanceof \WP_Post ) {
					continue;
				}

				$has_posts = get_posts(
					[
						'post_type'      => 'docs',
						// Bookkeeping lookup: must see docs regardless of who is asking.
						'betterdocs_bypass_restrictions' => true,
						'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							[
								'taxonomy'         => $taxonomy,
								'terms'            => (int) $term->term_id,
								'include_children' => false
							]
						]
					]
				);

				if ( empty( $has_posts ) ) {
					wp_delete_term( $term->term_id, $taxonomy );
					$deleted[] = (int) $term->term_id;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Ensure the parent term for a reference exists; persists its id in the
	 * reference's `_bd_api_parent_term_id` meta.
	 *
	 * @param \WP_Post $reference
	 * @return int|\WP_Error Term id.
	 */
	public function ensure_reference_term( \WP_Post $reference ) {
		$stored = (int) get_post_meta( $reference->ID, '_bd_api_parent_term_id', true );

		if ( $stored && get_term( $stored, self::TAXONOMY ) instanceof \WP_Term ) {
			$this->stamp_term_kb( $stored );
			$this->ensure_category_order( $stored );

			return $stored;
		}

		$term_id = $this->ensure_term(
			$reference->post_title,
			0,
			[
				'ref_id'      => $reference->ID,
				'tag'         => '',
				'description' => sprintf(
					/* translators: %s: reference title */
					__( 'API reference documentation for %s.', 'betterdocs-pro' ),
					$reference->post_title
				)
			]
		);

		if ( ! is_wp_error( $term_id ) ) {
			update_post_meta( $reference->ID, '_bd_api_parent_term_id', $term_id );
			$this->stamp_term_kb( $term_id );
			$this->ensure_category_order( $term_id );
		}

		return $term_id;
	}

	/**
	 * Terms without `doc_category_order` are dropped by the default
	 * `betterdocs_order` term ordering (meta join) — give the term an order
	 * after every existing category.
	 *
	 * `doc_category_order` is a SINGLE FLAT SEQUENCE across the whole taxonomy,
	 * not a per-parent one: Free's PostType::default_term_order() walks every
	 * term from one `get_max_taxonomy_order()` counter and never resets it for
	 * children (hence real data like `install` = 10 nested three levels deep).
	 * So children take max+1 exactly like roots do — numbering tag terms 1..n
	 * within their parent would collide with unrelated root categories.
	 *
	 * @param int $term_id
	 * @return void
	 */
	protected function ensure_category_order( $term_id ) {
		if ( '' !== (string) get_term_meta( $term_id, 'doc_category_order', true ) ) {
			return;
		}

		global $wpdb;

		$max = (int) $wpdb->get_var(
			"SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->termmeta} WHERE meta_key = 'doc_category_order'"
		);

		update_term_meta( $term_id, 'doc_category_order', $max + 1 );
	}

	/**
	 * Ensure the child term for a tag exists under the reference's parent term.
	 *
	 * Tag terms are created in spec-tag order, and each new one takes the
	 * taxonomy-wide max+1 (see ensure_category_order), so their relative order
	 * is preserved without carving out a private 1..n range that would collide
	 * with unrelated root categories.
	 *
	 * @param int    $reference_id
	 * @param int    $parent_term_id
	 * @param array  $tag   { name, description? } (from spec tags[] or bare name).
	 * @return int|\WP_Error
	 */
	public function ensure_tag_term( $reference_id, $parent_term_id, array $tag ) {
		$term_id = $this->ensure_term(
			$tag['name'],
			$parent_term_id,
			[
				'ref_id'      => $reference_id,
				'tag'         => $tag['name'],
				'description' => isset( $tag['description'] ) ? (string) $tag['description'] : ''
			]
		);

		if ( ! is_wp_error( $term_id ) ) {
			// ensure_category_order() only fills a MISSING order, so a term the
			// user has since reordered by hand keeps its position across syncs
			// (the old unconditional write clobbered it on every run).
			$this->ensure_category_order( $term_id );
			$this->stamp_term_kb( $term_id );
		}

		return $term_id;
	}

	/**
	 * Write the docs order (CSV of post ids) for a term — the mechanism the
	 * default `betterdocs_order` sorting reads.
	 *
	 * @param int   $term_id
	 * @param int[] $post_ids In desired order.
	 * @return void
	 */
	public function set_docs_order( $term_id, array $post_ids ) {
		update_term_meta( $term_id, '_docs_order', implode( ',', array_map( 'absint', $post_ids ) ) );
	}

	/**
	 * Delete generated (marker-carrying) child terms of a reference that no
	 * longer contain any posts. User terms are never touched.
	 *
	 * @param int  $reference_id
	 * @param int  $parent_term_id   Kept even when empty (deleted only on unmaterialize).
	 * @param bool $trashed_is_empty When true (unmaterialize / reference delete),
	 *                               terms holding only trashed docs count as empty;
	 *                               during a normal sync they survive for restore.
	 * @return int[] Deleted term ids.
	 */
	public function prune_empty_generated_terms( $reference_id, $parent_term_id = 0, $trashed_is_empty = false ) {
		$deleted  = [];
		$statuses = [ 'publish', 'draft', 'pending', 'private', 'future' ];

		if ( ! $trashed_is_empty ) {
			$statuses[] = 'trash';
		}

		foreach ( $this->generated_terms( $reference_id ) as $term ) {
			if ( (int) $term->term_id === (int) $parent_term_id ) {
				continue;
			}

			// count is published-only; check statuses explicitly (see param note).
			$has_posts = get_posts(
				[
					'post_type'      => 'docs',
					// Bookkeeping lookup: must see docs regardless of who is asking.
					'betterdocs_bypass_restrictions' => true,
					'post_status'    => $statuses,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy'         => self::TAXONOMY,
							'terms'            => (int) $term->term_id,
							'include_children' => false
						]
					]
				]
			);

			if ( empty( $has_posts ) ) {
				wp_delete_term( $term->term_id, self::TAXONOMY );
				$deleted[] = (int) $term->term_id;
			}
		}

		return $deleted;
	}

	/**
	 * All terms this reference generated (marker meta), parent included.
	 *
	 * @param int $reference_id
	 * @return \WP_Term[]
	 */
	public function generated_terms( $reference_id ) {
		$terms = get_terms(
			[
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'meta_key'   => '_bd_api_ref_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $reference_id // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		return is_wp_error( $terms ) ? [] : $terms;
	}

	/**
	 * Find-or-create a term. Existing terms (any origin) are reused; only
	 * terms we create get the `_bd_api_ref_id` marker.
	 *
	 * @param string $name
	 * @param int    $parent
	 * @param array  $args { ref_id, tag, description }
	 * @return int|\WP_Error
	 */
	protected function ensure_term( $name, $parent, array $args ) {
		$existing = term_exists( $name, self::TAXONOMY, $parent ?: null );

		if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
			return (int) $existing['term_id'];
		}

		$created = wp_insert_term(
			$name,
			self::TAXONOMY,
			[
				'parent'      => (int) $parent,
				'description' => isset( $args['description'] ) ? $args['description'] : ''
			]
		);

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		$term_id = (int) $created['term_id'];

		update_term_meta( $term_id, '_bd_api_ref_id', (int) $args['ref_id'] );
		update_term_meta( $term_id, '_bd_api_tag', isset( $args['tag'] ) ? (string) $args['tag'] : '' );

		return $term_id;
	}
}
