<?php

namespace WPDeveloper\BetterDocs\Admin;

use WPDeveloper\BetterDocs\Admin\ExportDefaults;
use WP_Term;
use wpdb;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SQL building helpers below interpolate WP-provided $wpdb table identifiers
 * (`$wpdb->posts`, `$wpdb->term_relationships`, `$wpdb->term_taxonomy`) and use
 * dynamic %d placeholder lists derived from integer arrays. Suppressing the
 * related PHPCS notices class-wide rather than per-call.
 *
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
#[\AllowDynamicProperties]
class WPExporter {
	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var wpdb
	 */
	private $wpdb;

    public function __construct( array $args = [] ) {
		global $wpdb;

		$this->args = wp_parse_args($args, ExportDefaults::get_default_args());

		$this->wpdb = $wpdb;
	}

    public function exporter_ids(): array {
        // Build the base query
        $query_parts = $this->build_base_query();
        $where = $query_parts['where'];
        $join = $query_parts['join'];

		// Handle post status
		$where .= $this->build_status_condition();

		// Handle specific posts selection
		if ( ! empty( $this->args['post__in'] ) ) {
			$where .= $this->build_post_in_condition();
		}

		// Handle category terms
		if ( isset( $this->args['category_terms'] ) ) {
			$category_query = $this->build_category_query();
			$join          .= $category_query['join'];
			$where         .= $category_query['where'];
		}

		// Handle knowledge base terms
		if ( isset( $this->args['kb_terms'] ) ) {
			$kb_query = $this->build_kb_query();
			$join    .= $kb_query['join'];
			$where   .= $kb_query['where'];
		}

		// Handle additional filters (author, dates, meta)
		$where .= $this->build_additional_filters();

		// $join/$where are composed from prepared fragments above; identifiers are WP-provided.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$post_ids = $this->wpdb->get_col( "SELECT DISTINCT {$this->wpdb->posts}.ID FROM {$this->wpdb->posts} $join WHERE $where" );

		// Handle FAQ posts separately
		$faq_post_ids = [];
		if ( ! empty( $this->args['include_faq'] ) ) {
			$faq_post_ids = $this->get_faq_posts();
		}

		// Combine post IDs
		$all_post_ids = array_merge( $post_ids, $faq_post_ids );

		$all_post_ids = WPMLSupport::expand_with_translations( $all_post_ids );

		// Handle featured images
		$thumbnail_ids = $this->get_thumbnail_ids( $all_post_ids );

        // Generate final post IDs array
        return array_unique(array_merge($all_post_ids, $thumbnail_ids));

    }

    // private function handle_glossaries_export(): array {
    public function get_glossary_term_ids(): array {
        if ( isset( $this->args['glossary_terms'] ) && ( count( $this->args['glossary_terms'] ) > 0 ) ) {
            $glossary_term_ids = [];
            foreach( $this->args['glossary_terms'] as $glossary_slug ) {
                $term_object = get_term_by('slug', $glossary_slug , 'glossaries');
                if( isset( $term_object->term_id ) && ! empty( $term_object->term_id ) ){
                    array_push($glossary_term_ids, $term_object->term_id);
                }
            }
        } else {
            // $this->wpdb->term_taxonomy is a WP-core table identifier; %s placeholder binds taxonomy name.
            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $glossary_term_ids = $this->wpdb->get_col(
                $this->wpdb->prepare(
                    "SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE taxonomy = %s",
                    (string) $this->args['content']
                )
            );
            // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        }

        return $glossary_term_ids;
    }

    // The query-builder methods below interpolate only $wpdb core table names
    // ({$this->wpdb->posts}, etc.) — never user input — and bind every value via
    // %s/%d placeholders, so the InterpolatedNotPrepared warnings are spurious.
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    public function build_base_query(): array {
        $where = $this->wpdb->prepare("{$this->wpdb->posts}.post_type = %s", 'docs');
        return [
            'where' => $where,
            'join' => ''
        ];
    }

    public function build_status_condition(): string {
        if ($this->args['status']) {
            return $this->wpdb->prepare(" AND {$this->wpdb->posts}.post_status = %s", $this->args['status']);
        }
        return " AND {$this->wpdb->posts}.post_status != 'auto-draft'";
    }

    public function build_post_in_condition(): string {
        $ids = $this->args['post__in'];
        $ids_placeholder = implode(', ', array_fill(0, count($ids), '%d'));
        return $this->wpdb->prepare(" AND {$this->wpdb->posts}.ID IN ($ids_placeholder)", $ids);
    }

    public function build_category_query(): array {
        $join = "INNER JOIN {$this->wpdb->term_relationships} tr ON ({$this->wpdb->posts}.ID = tr.object_id)
                 INNER JOIN {$this->wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

		$where = " AND tt.taxonomy = 'doc_category'";

		if ( ! empty( $this->args['category_terms'] ) ) {
			$term_ids = [];
			foreach ( $this->args['category_terms'] as $term_id ) {
				$term = term_exists( $term_id, 'doc_category' );
				if ( $term ) {
					$term_ids[] = $term['term_taxonomy_id'];
				}
			}

			if ( ! empty( $term_ids ) ) {
				$term_placeholder = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
				$where           .= $this->wpdb->prepare( " AND tt.term_taxonomy_id IN ($term_placeholder)", $term_ids );
			}
		}

		return [
			'join'  => $join,
			'where' => $where
		];
	}

    public function build_kb_query(): array {
        $join = "INNER JOIN {$this->wpdb->term_relationships} tr ON ({$this->wpdb->posts}.ID = tr.object_id)
                 INNER JOIN {$this->wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

		$where = " AND tt.taxonomy = 'knowledge_base'";

		if ( ! empty( $this->args['kb_terms'] ) ) {
			$term_ids = [];
			foreach ( $this->args['kb_terms'] as $term_id ) {
				$term = term_exists( $term_id, 'knowledge_base' );
				if ( $term ) {
					$term_ids[] = $term['term_taxonomy_id'];
				}
			}

			if ( ! empty( $term_ids ) ) {
				$term_placeholder = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
				$where           .= $this->wpdb->prepare( " AND tt.term_taxonomy_id IN ($term_placeholder)", $term_ids );
			}
		}

		return [
			'join'  => $join,
			'where' => $where
		];
	}

    public function build_additional_filters(): string {
        $where = '';

		if ( $this->args['author'] ) {
			$where .= $this->wpdb->prepare( " AND {$this->wpdb->posts}.post_author = %d", $this->args['author'] );
		}

		if ( $this->args['start_date'] ) {
			$where .= $this->wpdb->prepare(
				" AND {$this->wpdb->posts}.post_date >= %s",
				gmdate( 'Y-m-d', strtotime( $this->args['start_date'] ) )
			);
		}

		if ( $this->args['end_date'] ) {
			$where .= $this->wpdb->prepare(
				" AND {$this->wpdb->posts}.post_date < %s",
				gmdate( 'Y-m-d', strtotime( '+1 month', strtotime( $this->args['end_date'] ) ) )
			);
		}

		return $where;
	}

    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    public function get_faq_posts(): array {
        return get_posts([
            'numberposts'      => -1,
            'post_type'        => 'betterdocs_faq',
            'fields'           => 'ids',
            'post_status'      => 'publish',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- intentional: export the raw, untranslated FAQ set so multilingual filters don't drop or swap rows during export.
            'suppress_filters' => true,
        ]);
    }

    public function get_thumbnail_ids(array $post_ids): array {
        $thumbnail_ids = [];

		if ( ! empty( $this->args['include_post_featured_image_as_attachment'] ) ) {
			foreach ( $post_ids as $post_id ) {
				$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
				if ( $thumbnail_id && ! in_array( $thumbnail_id, $post_ids, true ) ) {
					$thumbnail_ids[] = $thumbnail_id;
				}
			}
		}

		return $thumbnail_ids;
	}

	/**
	 * Return tabulation characters, by `$columns`.
	 *
	 * @param int $columns
	 *
	 * @return string
	 */
	public function indent( int $columns = 1 ): string {

		$output = str_repeat( "\t", $columns );

		return (string) $output;
	}

    /**
     * Retrieve terms associated with the specified object IDs and sort them based on term meta.
     *
     * @param array $post_ids An array of object IDs.
     * @return array An array of WP_Term objects sorted based on term meta.
     */
	public function get_terms(array $post_ids = []) {
        // Get the object taxonomies
        $taxonomies = get_object_taxonomies($this->args['content']);

        $terms = [];

        // Handle glossaries
        if ($this->args['content'] == 'glossaries') {
            $terms = get_terms([
                'taxonomy'   => 'glossaries',
                'include'    => $post_ids,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms)) {
                return [];
            }

            // Include parent terms for hierarchical glossaries
            $parent_terms = [];
            foreach ($terms as $term) {
                if (!empty($term->parent)) {
                    $parent = get_term($term->parent, 'glossaries');
                    if (!is_wp_error($parent)) {
                        $parent_terms[] = $parent;
                    }
                }
            }

            if (!empty($parent_terms)) {
                $terms = array_merge($terms, $parent_terms);
                $terms = array_unique($terms, SORT_REGULAR);
            }
        } elseif ($this->args['content'] == 'docs') { // Handle docs
            if (isset($this->args['selected_docs']) && !empty($this->args['selected_docs'])) {
                if ($this->args['selected_docs'][0] == 'all') {
                    // Fetch all terms
                    $terms = get_terms([
                        'taxonomy'   => $taxonomies,
                        'hide_empty' => false,
                    ]);
                } else {
                    // Fetch terms for specific post IDs
                    $terms = wp_get_object_terms($this->args['selected_docs'], $taxonomies);
                }
            }
        } elseif (in_array($this->args['content'], ['knowledge_base', 'doc_category'])) {
            // Handle both knowledge_base and doc_category cases
            $terms_key = $this->args['content'] == 'doc_category' ? 'category_terms' : 'kb_terms';

            if (isset($this->args[$terms_key]) && !empty($this->args[$terms_key])) {
                $terms_arg = [
                    'taxonomy'   => $taxonomies,
                    'hide_empty' => false,
                ];

                if ($this->args[$terms_key][0] !== 'all') {
                    $terms_arg['slug'] = $this->args[$terms_key];
                }

                $terms = get_terms($terms_arg);
            }
        }

        // Include both parent and child terms for hierarchical structures
        if (!empty($terms) && is_array($terms)) {
            $additional_terms = [];

            foreach ($terms as $term) {
                // Get parent terms
                if (!empty($term->parent)) {
                    $ancestors = get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');
                    foreach ($ancestors as $ancestor_id) {
                        $parent = get_term($ancestor_id, $term->taxonomy);
                        if (!is_wp_error($parent)) {
                            $additional_terms[] = $parent;
                        }
                    }
                }

                // Get child terms
                $children = get_term_children($term->term_id, $term->taxonomy);
                if (!is_wp_error($children)) {
                    foreach ($children as $child_id) {
                        $child = get_term($child_id, $term->taxonomy);
                        if (!is_wp_error($child)) {
                            $additional_terms[] = $child;
                        }
                    }
                }
            }

            if (!empty($additional_terms)) {
                $terms = array_merge($terms, $additional_terms);
                $terms = array_unique($terms, SORT_REGULAR);
            }
        }

        // Sort terms if not empty
        if (!empty($terms) && is_array($terms)) {
            usort($terms, array($this, 'compare_terms_by_meta'));
        }

        return $terms;
    }

	/**
	 * Compare terms based on their associated term meta values.
	 *
	 * @param WP_Term $a The first term object.
	 * @param WP_Term $b The second term object.
	 * @return int Returns a negative value if $a is less than $b,
	 *             a positive value if $a is greater than $b, or 0 if they are equal.
	 *             Additionally, prioritize sorting terms by taxonomy order,
	 *             with 'doc_category' terms appearing before other taxonomy terms.
	 */
	public function compare_terms_by_meta( $a, $b ) {
		// Define the order of taxonomies
		$taxonomy_order = array(
			'doc_category'   => 0,
			'knowledge_base' => 1,
			'doc_tag'        => 2,
		);

		// Get the taxonomy order for terms $a and $b
		$order_a = isset( $taxonomy_order[ $a->taxonomy ] ) ? $taxonomy_order[ $a->taxonomy ] : PHP_INT_MAX;
		$order_b = isset( $taxonomy_order[ $b->taxonomy ] ) ? $taxonomy_order[ $b->taxonomy ] : PHP_INT_MAX;

		// If the taxonomies have different order, sort by order
		if ( $order_a !== $order_b ) {
			return $order_a - $order_b;
		}

		// If the taxonomies have the same order, sort by meta value
		$taxonomy_order_meta = array(
			'doc_category'   => 'doc_category_order',
			'knowledge_base' => 'kb_order'
		);

		if ( isset( $taxonomy_order_meta[ $a->taxonomy ] ) && isset( $taxonomy_order_meta[ $b->taxonomy ] ) ) {
			$meta_a = intval( get_term_meta( $a->term_id, $taxonomy_order_meta[ $a->taxonomy ], true ) );
			$meta_b = intval( get_term_meta( $b->term_id, $taxonomy_order_meta[ $b->taxonomy ], true ) );

			return $meta_a - $meta_b;
		}

        return 0; // Default to no sorting if meta keys are not defined
    }
}
