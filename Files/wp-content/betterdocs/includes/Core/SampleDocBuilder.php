<?php

namespace WPDeveloper\BetterDocs\Core;

use WP_Error;

/**
 * SampleDocBuilder — Phase 4 of the AI "Generate Sample Docs" feature.
 *
 * Turns the proxy's structured response into real BetterDocs content:
 * doc/FAQ categories (terms) + articles (posts), preserving order. Everything
 * created is flagged with the `_betterdocs_sample` meta so it can be removed
 * cleanly via undo().
 *
 * @since 4.5.3
 */
class SampleDocBuilder {
	/** Meta key flagging sample posts and terms we created. */
	const SAMPLE_META = '_betterdocs_sample';

	/**
	 * Post type + taxonomy per content type.
	 *
	 * @var array
	 */
	const MAP = [
		'docs'        => [ 'post_type' => 'docs', 'taxonomy' => 'doc_category' ],
		'faq'         => [ 'post_type' => 'betterdocs_faq', 'taxonomy' => 'betterdocs_faq_category' ],
		'product_faq' => [ 'post_type' => 'betterdocs_faq', 'taxonomy' => 'betterdocs_product_faq_category' ],
	];

	/** Ordering rank so an intro then a quickstart always lead their category. */
	const TYPE_RANK = [ 'intro' => 0, 'quickstart' => 1 ];

	/**
	 * Create terms + posts from the (already sanitized) categories array.
	 *
	 * Two passes so the AI's intra-KB cross-links resolve to real permalinks: pass 1
	 * creates every term + post (capturing each article's declared slug → post id);
	 * pass 2 rewrites the `#bd-link--slug` sentinels in each body to the sibling's real
	 * permalink (neutralizing any that never got created) and writes the final blocks.
	 *
	 * @param array  $categories   [{ name, slug?, description, articles:[{title,slug?,type?,content_html,excerpt}] }]
	 * @param string $content_type 'docs' | 'faq'
	 * @return array|WP_Error      Summary on success.
	 */
	public function build( array $categories, $content_type = 'docs' ) {
		$map = $this->map( $content_type );
		if ( null === $map ) {
			return new WP_Error( 'invalid_content_type', __( 'Unknown content type.', 'betterdocs' ) );
		}

		if ( empty( $categories ) ) {
			return new WP_Error( 'no_categories', __( 'No categories to create.', 'betterdocs' ) );
		}

		// Idempotency guard: if sample content for this type already exists (e.g. a
		// duplicate insert call, which the UI prevents but a direct REST call does
		// not), return the existing summary instead of creating duplicate posts.
		// Regeneration is expected to undo() first, which clears these.
		$existing = $this->existing_sample( $map );
		if ( $existing['categories'] > 0 || $existing['articles'] > 0 ) {
			return array_merge( [ 'content_type' => $content_type, 'already_exists' => true ], $existing );
		}

		$created_terms  = [];
		$created_posts  = [];
		$slug_to_id     = []; // declared article slug → created post id (cross-link map)
		$pending        = []; // [ post_id => raw content_html ] to finalize in pass 2
		$cat_posts      = []; // term_id → [ post_id, … ] in intended reading order
		$order          = 0;  // global menu_order so intro/quickstart lead the whole KB
		$order_meta_key = $this->category_order_meta_key( $map['taxonomy'] );

		/**
		 * Status sample content is created with. Draft by default so AI-generated docs
		 * are never auto-published on a live site — the owner reviews each and publishes
		 * it manually. Drafts are non-public/non-indexable but still show in the admin
		 * dashboard (categories via hide_empty=false; docs via the edit_docs status set),
		 * so they can be reviewed. Each post is flagged `_betterdocs_sample` (used by
		 * undo). Filterable for sites that prefer to publish immediately.
		 *
		 * @param string $status       Post status ('draft').
		 * @param string $content_type docs|faq|product_faq
		 */
		$post_status = apply_filters( 'betterdocs_sample_docs_post_status', 'draft', $content_type );

		// -- Pass 1: create terms + posts (bodies still carry link sentinels) --------
		foreach ( $categories as $cat_index => $category ) {
			if ( empty( $category['name'] ) ) {
				continue;
			}

			$term_id = $this->ensure_term( $category, $map['taxonomy'] );
			if ( is_wp_error( $term_id ) || ! $term_id ) {
				continue;
			}
			$created_terms[] = $term_id;

			$articles = isset( $category['articles'] ) && is_array( $category['articles'] ) ? $category['articles'] : [];
			foreach ( $this->order_articles( $articles ) as $article ) {
				$title = is_array( $article ) ? ( $article['title'] ?? '' ) : (string) $article;
				if ( '' === trim( $title ) ) {
					continue;
				}

				$content = is_array( $article ) ? ( $article['content_html'] ?? '' ) : '';
				$excerpt = is_array( $article ) ? ( $article['excerpt'] ?? '' ) : '';
				$slug    = is_array( $article ) && ! empty( $article['slug'] ) ? sanitize_title( $article['slug'] ) : '';

				$postarr = [
					'post_type'    => $map['post_type'],
					'post_title'   => wp_strip_all_tags( $title ),
					// Placeholder now; the real blocks are written in pass 2 once the
					// full slug → id map exists.
					'post_content' => '',
					'post_excerpt' => sanitize_text_field( $excerpt ),
					'post_status'  => $post_status,
					'menu_order'   => $order++,
				];
				// Give drafts a real slug up front — WP only derives post_name on publish,
				// so without this a draft's get_permalink() (used to resolve cross-links)
				// would be an ugly ?p=ID URL.
				if ( '' !== $slug ) {
					$postarr['post_name'] = $slug;
				}

				$post_id = wp_insert_post( $postarr, true );

				if ( is_wp_error( $post_id ) ) {
					continue;
				}

				wp_set_object_terms( $post_id, [ (int) $term_id ], $map['taxonomy'] );
				update_post_meta( $post_id, self::SAMPLE_META, 1 );

				// Record which FAQ tab this belongs to, so it stays in that tab's
				// "Uncategorized" bucket if its group is ever deleted. FAQBuilder syncs this
				// on set_object_terms too, but stamping it here does not depend on that hook
				// being loaded — and a generated FAQ that ends up scope-less silently
				// defaults into the GENERAL tab, which is how store FAQs from the old
				// product generator ended up mixed in with general ones.
				if ( 'docs' !== $content_type ) {
					update_post_meta(
						$post_id,
						'_betterdocs_faq_scope',
						'product_faq' === $content_type ? 'product' : 'general'
					);
				}

				if ( '' !== $slug && ! isset( $slug_to_id[ $slug ] ) ) {
					$slug_to_id[ $slug ] = $post_id;
				}

				$pending[ $post_id ]      = (string) $content;
				$created_posts[]          = $post_id;
				$cat_posts[ $term_id ][] = $post_id; // preserve intro/quickstart-first order
			}
		}

		// -- Pass 2: resolve cross-links, convert to blocks, write final content ------
		foreach ( $pending as $post_id => $raw ) {
			$resolved = $this->resolve_cross_links( $raw, $slug_to_id );
			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => $this->html_to_blocks( $resolved ),
				]
			);
		}

		// -- Pass 3: pin category display order to the KB's designed reading order -----
		// Done LAST, after every term + post exists, because BetterDocs re-seeds
		// `doc_category_order` to max+1 via the created_doc_category hook as each later
		// category is created — which would otherwise bump the first category (Getting
		// Started) to the end.
		//
		// Order is 1-based on purpose: BetterDocs' default_term_order() (which runs on
		// the admin dashboard load) treats a term whose order fails `! get_term_meta()`
		// as "unordered" and reassigns it to max+1. A 0 value is falsy in PHP, so a
		// first category at 0 would get bumped to the end on the very next page load.
		// Starting at 1 keeps every value truthy and the intended order stable.
		foreach ( array_values( $created_terms ) as $position => $term_id ) {
			update_term_meta( $term_id, $order_meta_key, $position + 1 );
		}

		// Pin the WITHIN-category item order for EVERY content type. BetterDocs orders
		// items in a category by a term-meta id list (docs: `_docs_order`; FAQ &
		// product FAQ: `_betterdocs_faq_order`), and its insert hooks PREPEND each new
		// item — so left alone the order comes out reversed, AND for FAQs it is never
		// seeded at all, which is why publishing a drafted sample FAQ reshuffled the
		// list to the top. Overwrite it here with the intended generation order so the
		// list stays put through activation, exactly as docs do.
		$order_key = $this->within_category_order_meta_key( $map['taxonomy'] );
		if ( $order_key ) {
			foreach ( $cat_posts as $term_id => $post_ids ) {
				update_term_meta(
					$term_id,
					'doc_category' === $map['taxonomy'] ? $this->docs_order_meta_key( $term_id ) : $order_key,
					implode( ',', array_map( 'intval', $post_ids ) )
				);
			}
		}

		/**
		 * Fires after sample content is created (telemetry/integration hook).
		 *
		 * @param array  $created_posts
		 * @param array  $created_terms
		 * @param string $content_type
		 */
		do_action( 'betterdocs_sample_docs_created', $created_posts, $created_terms, $content_type );

		return [
			'content_type' => $content_type,
			'categories'   => count( $created_terms ),
			'articles'     => count( $created_posts ),
			'term_ids'     => $created_terms,
			'post_ids'     => $created_posts,
		];
	}

	/**
	 * Stable-sort a category's articles so an "intro" then a "quickstart" always lead,
	 * with everything else keeping its given order.
	 *
	 * @return array
	 */
	protected function order_articles( array $articles ) {
		$articles = array_values( $articles );
		$indexed  = [];
		foreach ( $articles as $i => $article ) {
			$type       = is_array( $article ) && ! empty( $article['type'] ) ? (string) $article['type'] : '';
			$rank       = isset( self::TYPE_RANK[ $type ] ) ? self::TYPE_RANK[ $type ] : 2;
			$indexed[] = [ 'rank' => $rank, 'i' => $i, 'article' => $article ];
		}
		usort(
			$indexed,
			function ( $a, $b ) {
				return $a['rank'] === $b['rank'] ? ( $a['i'] <=> $b['i'] ) : ( $a['rank'] <=> $b['rank'] );
			}
		);
		return array_column( $indexed, 'article' );
	}

	/**
	 * Rewrite `#bd-link--slug` cross-link sentinels to real permalinks. Slugs that were
	 * never created (e.g. a skipped article) have their surrounding <a> stripped so no
	 * dangling sentinel is ever published.
	 *
	 * @param string $html
	 * @param array  $slug_to_id declared slug → post id
	 * @return string
	 */
	protected function resolve_cross_links( $html, array $slug_to_id ) {
		$html = (string) $html;
		if ( false === strpos( $html, '#bd-link--' ) ) {
			return $html;
		}

		// Resolve known slugs to permalinks. The slug class stops at the closing quote,
		// so this matches the whole slug (no partial-prefix collisions).
		$html = preg_replace_callback(
			'/#bd-link--([a-z0-9\-]+)/i',
			function ( $m ) use ( $slug_to_id ) {
				$slug = strtolower( $m[1] );
				if ( isset( $slug_to_id[ $slug ] ) ) {
					$url = $this->permalink_for( $slug_to_id[ $slug ] );
					if ( $url ) {
						return esc_url( $url );
					}
				}
				// Leave the sentinel in place so the dangling-anchor sweep below removes it.
				return '#bd-link--' . $slug;
			},
			$html
		);

		// Strip any anchor whose href is still an unresolved sentinel, keeping its text.
		$html = preg_replace(
			'/<a\b[^>]*href=("|\')#bd-link--[a-z0-9\-]+\1[^>]*>(.*?)<\/a>/is',
			'$2',
			$html
		);

		return $html;
	}

	/**
	 * Pretty permalink for a post that may still be a draft. get_permalink() returns
	 * an ugly `?p=ID` URL for unpublished posts, so for cross-links we build the
	 * would-be published permalink (stable through review → publish).
	 *
	 * @param int $post_id
	 * @return string
	 */
	protected function permalink_for( $post_id ) {
		$status = get_post_status( $post_id );
		if ( in_array( $status, [ 'publish', 'future', 'private' ], true ) ) {
			return (string) get_permalink( $post_id );
		}

		if ( ! function_exists( 'get_sample_permalink' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$sample = get_sample_permalink( $post_id );
		$post   = get_post( $post_id );
		if ( is_array( $sample ) && ! empty( $sample[0] ) && $post instanceof \WP_Post ) {
			$name = '' !== $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
			return str_replace( [ '%pagename%', '%postname%' ], $name, $sample[0] );
		}

		return (string) get_permalink( $post_id );
	}

	/**
	 * Remove exactly the sample content we created for a content type.
	 *
	 * @param string $content_type 'docs' | 'faq'
	 * @return array Counts removed.
	 */
	public function undo( $content_type = 'docs' ) {
		$map = $this->map( $content_type );
		if ( null === $map ) {
			return [ 'categories' => 0, 'articles' => 0 ];
		}

		// 1. Delete flagged posts. Scope by taxonomy as well as post type: General
		// FAQs and Product FAQs share the `betterdocs_faq` post type, so the
		// taxonomy is what keeps "undo" from removing the other scope's samples.
		$posts = get_posts(
			[
				'post_type'      => $map['post_type'],
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::SAMPLE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => $map['taxonomy'],
						'operator' => 'EXISTS',
					],
				],
			]
		);
		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// 2. Delete flagged terms.
		$terms = get_terms(
			[
				'taxonomy'   => $map['taxonomy'],
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_key'   => self::SAMPLE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		$term_count = 0;
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $map['taxonomy'] );
				$term_count++;
			}
		}

		do_action( 'betterdocs_sample_docs_removed', $content_type );

		return [
			'content_type' => $content_type,
			'categories'   => $term_count,
			'articles'     => count( $posts ),
		];
	}

	/* --------------------------------------------------------------------- */

	/**
	 * Collect the sample content already created for a content type, so build()
	 * stays idempotent against duplicate insert calls.
	 *
	 * @return array { categories, articles, term_ids, post_ids }
	 */
	protected function existing_sample( array $map ) {
		$post_ids = get_posts(
			[
				'post_type'      => $map['post_type'],
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::SAMPLE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => $map['taxonomy'],
						'operator' => 'EXISTS',
					],
				],
			]
		);

		$term_ids = get_terms(
			[
				'taxonomy'   => $map['taxonomy'],
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_key'   => self::SAMPLE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		$term_ids = is_wp_error( $term_ids ) ? [] : $term_ids;

		return [
			'categories' => count( $term_ids ),
			'articles'   => count( $post_ids ),
			'term_ids'   => array_map( 'intval', $term_ids ),
			'post_ids'   => array_map( 'intval', $post_ids ),
		];
	}

	/**
	 * Term-meta key BetterDocs orders categories by, per taxonomy. Docs use
	 * `doc_category_order` (with the multilingual fallback BetterDocs itself uses);
	 * FAQ and product-FAQ categories use `order`.
	 *
	 * @param string $taxonomy
	 * @return string
	 */
	protected function category_order_meta_key( $taxonomy ) {
		if ( 'doc_category' === $taxonomy ) {
			$helper = 'WPDeveloper\\BetterDocs\\Utils\\Helper';
			if ( class_exists( $helper ) && method_exists( $helper, 'get_meta_key_with_fallback' ) ) {
				return $helper::get_meta_key_with_fallback( 'doc_category_order' );
			}
			return 'doc_category_order';
		}
		return 'order';
	}

	/**
	 * Term-meta key BetterDocs orders items *within* a category by, per taxonomy:
	 * docs → `_docs_order` (multilingual-aware, see docs_order_meta_key()); FAQ and
	 * product FAQ → `_betterdocs_faq_order`. Empty string if the taxonomy has no such
	 * ordering meta.
	 *
	 * @param string $taxonomy
	 * @return string
	 */
	protected function within_category_order_meta_key( $taxonomy ) {
		if ( 'doc_category' === $taxonomy ) {
			return '_docs_order';
		}
		if ( in_array( $taxonomy, [ 'betterdocs_faq_category', 'betterdocs_product_faq_category' ], true ) ) {
			return '_betterdocs_faq_order';
		}
		return '';
	}

	/**
	 * Term-meta key BetterDocs orders docs *within* a category by (`_docs_order`),
	 * using the same multilingual fallback the plugin itself applies.
	 *
	 * @param int $term_id
	 * @return string
	 */
	protected function docs_order_meta_key( $term_id ) {
		$helper = 'WPDeveloper\\BetterDocs\\Utils\\Helper';
		if ( class_exists( $helper ) && method_exists( $helper, 'get_meta_key_with_fallback' ) ) {
			return $helper::get_meta_key_with_fallback( '_docs_order', $term_id );
		}
		return '_docs_order';
	}

	/**
	 * Create the category term (or reuse an existing one) and flag it if new.
	 *
	 * @return int|WP_Error
	 */
	protected function ensure_term( array $category, $taxonomy ) {
		$name     = sanitize_text_field( $category['name'] );
		$existing = term_exists( $name, $taxonomy );

		if ( $existing && ! empty( $existing['term_id'] ) ) {
			// Still (re)assign: a re-run reuses the existing group term, and returning early
			// left it assigned to nothing (so its FAQs showed on no product page at all).
			$this->route_product_assignment( (int) $existing['term_id'], $category, $taxonomy );
			return (int) $existing['term_id'];
		}

		$inserted = wp_insert_term(
			$name,
			$taxonomy,
			[ 'description' => sanitize_text_field( $category['description'] ?? '' ) ]
		);

		if ( is_wp_error( $inserted ) ) {
			return $inserted;
		}

		$term_id = (int) $inserted['term_id'];
		update_term_meta( $term_id, self::SAMPLE_META, 1 );

		$this->route_product_assignment( $term_id, $category, $taxonomy );

		return $term_id;
	}

	/**
	 * Decide where a generated Product FAQ group is shown on the storefront.
	 *
	 * Two layers:
	 *  - Store-wide (Layer 1, `all_products`) → flag the group to show on EVERY product
	 *    page, once. This is the consolidated Payments/Shipping/Returns/Orders group.
	 *  - Per-category (Layer 2) → attach to the one product category it is about.
	 *
	 * A generated group with neither would be invisible on the storefront, so every
	 * product FAQ group we create is routed to exactly one of the two.
	 *
	 * @param int    $term_id
	 * @param array  $category The generated payload (carries `all_products` / `product_category`).
	 * @param string $taxonomy
	 */
	protected function route_product_assignment( $term_id, array $category, $taxonomy ) {
		if ( 'betterdocs_product_faq_category' !== $taxonomy ) {
			return;
		}

		if ( ! empty( $category['all_products'] ) ) {
			// Show on every product page — the dormant front-end mechanism in
			// WooProductFAQ::get_group_ids_for_product(). Reuse the constant, not the raw key.
			update_term_meta( $term_id, FAQBuilder::GROUP_ALL_PRODUCTS_META, true );
			// Make sure a re-run that switched a group to store-wide drops any stale
			// per-category assignment.
			delete_term_meta( $term_id, FAQBuilder::GROUP_PRODUCT_CATS_META );
			return;
		}

		$this->assign_product_category( $term_id, $category, $taxonomy );
	}

	/**
	 * Attach a generated Product FAQ group to the WooCommerce product category it was
	 * written about (matched by name, which is what the AI was given).
	 *
	 * @param int    $term_id  The FAQ group term.
	 * @param array  $category The generated category payload (carries `product_category`).
	 * @param string $taxonomy The FAQ taxonomy being built.
	 */
	protected function assign_product_category( $term_id, array $category, $taxonomy ) {
		if ( 'betterdocs_product_faq_category' !== $taxonomy || ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}

		// The AI echoes back the exact product category name it was given; the group's own
		// name is the fallback (it's derived from that category, but the model may have
		// prettified it — "Tshirts" → "T-Shirts with Logo"), so try both.
		$candidates = [];
		foreach ( [ $category['product_category'] ?? '', $category['name'] ?? '' ] as $candidate ) {
			$candidate = trim( html_entity_decode( wp_strip_all_tags( (string) $candidate ), ENT_QUOTES, 'UTF-8' ) );
			if ( '' !== $candidate ) {
				$candidates[] = $candidate;
			}
		}
		if ( empty( $candidates ) ) {
			return;
		}

		$product_cat = null;
		foreach ( $candidates as $candidate ) {
			$product_cat = get_term_by( 'name', $candidate, 'product_cat' );
			if ( ! $product_cat ) {
				$product_cat = get_term_by( 'slug', sanitize_title( $candidate ), 'product_cat' );
			}
			if ( ! $product_cat ) {
				$product_cat = $this->match_product_cat_loosely( $candidate );
			}
			if ( $product_cat && ! is_wp_error( $product_cat ) ) {
				break;
			}
		}

		if ( ! $product_cat || is_wp_error( $product_cat ) ) {
			return;
		}

		update_term_meta( $term_id, '_betterdocs_faq_group_product_cats', [ (int) $product_cat->term_id ] );
	}

	/**
	 * Last-resort product-category match for a group name the model reworded ("T-Shirts
	 * with Logo" → "Tshirts", "Music & Audio" → "Music"). Compares on a letters-and-digits
	 * only, lower-cased key, then falls back to containment. Returns the term or null.
	 *
	 * A wrong assignment would publish an FAQ on the wrong product pages, so only an
	 * unambiguous single match is accepted.
	 *
	 * @return \WP_Term|null
	 */
	protected function match_product_cat_loosely( $name ) {
		$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		$key = function ( $value ) {
			return preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $value ) );
		};

		$needle = $key( $name );
		if ( '' === $needle ) {
			return null;
		}

		$matches = [];
		foreach ( $terms as $term ) {
			$hay = $key( $term->name );
			if ( '' === $hay ) {
				continue;
			}
			if ( $hay === $needle || false !== strpos( $needle, $hay ) || false !== strpos( $hay, $needle ) ) {
				$matches[] = $term;
			}
		}

		// Ambiguous (e.g. "Clothing" also matching "Clothing Accessories") — assign nothing
		// rather than the wrong category.
		return 1 === count( $matches ) ? $matches[0] : null;
	}

	/**
	 * Convert the proxy's content HTML into clean Gutenberg block markup.
	 * Each top-level element becomes its matching core block; unknown nodes
	 * fall back to a paragraph. Empty input yields an empty paragraph block.
	 *
	 * @return string
	 */
	protected function html_to_blocks( $html ) {
		// Sanitize our OWN input rather than trusting the caller. node_to_block() emits
		// $dom->saveHTML() verbatim for every known tag (p, h1-h6, ul, ol, blockquote,
		// pre), so any attribute on those elements — including an onclick — is copied
		// straight into post_content. Today the REST layer kses's the AI response before
		// it ever gets here, so nothing leaks; this makes that a property of the method
		// instead of a property of its one current caller. wp_kses_post() is idempotent,
		// so the existing double-sanitization costs only a pass over the string, and it
		// preserves the `#bd-link--slug` cross-link fragments the builder resolves.
		$html = wp_kses_post( trim( (string) $html ) );
		if ( '' === trim( $html ) ) {
			return "<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->";
		}

		if ( ! class_exists( '\DOMDocument' ) ) {
			return wp_kses_post( $html );
		}

		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8"?><div id="bd-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$root = $dom->getElementById( 'bd-root' );
		if ( ! $root ) {
			return wp_kses_post( $html );
		}

		$blocks = '';
		foreach ( $root->childNodes as $node ) {
			$blocks .= $this->node_to_block( $node, $dom );
		}

		return '' !== trim( $blocks ) ? $blocks : wp_kses_post( $html );
	}

	/**
	 * Map a single DOM node to a core block string.
	 *
	 * @return string
	 */
	protected function node_to_block( \DOMNode $node, \DOMDocument $dom ) {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = trim( $node->textContent );
			return '' === $text ? '' : "<!-- wp:paragraph --><p>" . esc_html( $text ) . "</p><!-- /wp:paragraph -->";
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag  = strtolower( $node->nodeName );
		$html = $dom->saveHTML( $node );

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$level = (int) substr( $tag, 1 );
				return "<!-- wp:heading {\"level\":{$level}} -->{$html}<!-- /wp:heading -->";
			case 'ul':
				return "<!-- wp:list -->{$html}<!-- /wp:list -->";
			case 'ol':
				return "<!-- wp:list {\"ordered\":true} -->{$html}<!-- /wp:list -->";
			case 'blockquote':
				return "<!-- wp:quote -->{$html}<!-- /wp:quote -->";
			case 'pre':
				return "<!-- wp:preformatted -->{$html}<!-- /wp:preformatted -->";
			case 'p':
				return "<!-- wp:paragraph -->{$html}<!-- /wp:paragraph -->";
			default:
				return "<!-- wp:paragraph --><p>" . wp_kses_post( $node->textContent ) . "</p><!-- /wp:paragraph -->";
		}
	}

	/**
	 * @return array|null
	 */
	protected function map( $content_type ) {
		return isset( self::MAP[ $content_type ] ) ? self::MAP[ $content_type ] : null;
	}
}
