<?php

namespace WPDeveloper\BetterDocsPro\REST;

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Doc-listing primitives (meta_key/meta_query/tax_query, post__not_in, exclude)
// are intrinsic to BetterDocs' KB / category / encyclopedia / popular-docs filters.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\Helper;

class KnowledgeBaseField extends BaseAPI {
    public function register() {
        $this->register_field( 'doc_category', 'knowledge_base', [
            'get_callback'    => [$this, 'knowledge_base_collection'],
            'update_callback' => [$this, 'update_knowledge_base_collection'],
            'schema'          => [
                'description' => 'Knowledge Base slugs assigned to this doc category',
                'type'        => 'array',
                'items'       => [ 'type' => 'string' ],
                'context'     => [ 'view', 'edit' ],
            ],
        ] );

        $this->register_field( 'doc_category', 'knowledge_base_ids', [
            'get_callback' => [$this, 'knowledge_base_collection_ids']
        ] );

        $this->register_field( 'doc_category', 'knowledge_base_titles', [
            'get_callback' => [$this, 'knowledge_base_collection_titles']
        ] );

        $this->register_field( 'docs', 'knowledge_base_info', [
            'get_callback' => [$this, 'get_knowledge_base_info']
        ] );

        $this->register_field( 'docs', 'knowledge_base_slug', [
            'get_callback' => [$this, 'get_knowledge_base_slug']
        ] );

        $this->register_field( 'knowledge_base', 'subcategories_count', [
            'get_callback' => [$this, 'get_subcategory_count']
        ] );

        $this->register_field( 'knowledge_base', 'total_docs_count', [
            'get_callback' => [$this, 'total_docs_count']
        ] );

        $this->register_field( 'knowledge_base', 'last_updated_time', [
            'get_callback' => [$this, 'last_updated_time']
        ] );

        $this->register_field( 'knowledge_base', 'knowledge_base_image', [
            'get_callback' => [$this, 'get_kb_image_url']
        ] );

        // Read/write a KB term's language for the React language filter + selector.
        $this->register_field( 'knowledge_base', 'lang', [
            'get_callback'    => [$this, 'get_kb_lang'],
            'update_callback' => [$this, 'set_kb_lang'],
            'schema'          => [ 'type' => [ 'string', 'null' ], 'context' => [ 'view', 'edit' ] ],
        ] );

        add_filter( 'rest_docs_query', [$this, 'filter_docs_query'], 10, 2 );
    }

    public function get_kb_lang( $object ) {
        if ( ! Helper::is_multilingual_active() ) {
            return null;
        }
        $term = get_term( $object['id'], 'knowledge_base' );
        return ( $term && ! is_wp_error( $term ) ) ? Helper::get_term_language( $term ) : '';
    }

    public function set_kb_lang( $value, $term ) {
        if ( ! current_user_can( 'edit_knowledge_base_terms' ) || ! Helper::is_multilingual_active() ) {
            return;
        }
        Helper::set_term_language( $term, sanitize_text_field( (string) $value ) );
    }

    /**
     * Resolve the Knowledge Base icon attachment id to a URL for the React admin.
     */
    public function get_kb_image_url( $object ) {
        $icon_id = get_term_meta( $object['id'], 'knowledge_base_image-id', true );
        if ( empty( $icon_id ) ) {
            return '';
        }
        $url = wp_get_attachment_image_url( $icon_id, 'thumbnail' );
        return $url ? $url : '';
    }

    public function get_subcategory_count( $object ) {
        $subcategories = get_terms( [
            'taxonomy' => 'doc_category',
            'parent'   => $object['id']
        ] );
        return count( ( $subcategories ) );
    }

    public function total_docs_count( $object ) {
        $args = [
            'post_type' => 'docs',
            'fields'    => 'ids',
            'tax_query' => [
                [
                    'taxonomy'         => 'knowledge_base',
                    'field'            => 'term_id',
                    'terms'            => $object['id'],
                    'include_children' => true,
                    'operator'         => 'IN'
                ]
            ]
        ];

        // Include private posts for users with read_private_docs capability
        $args['post_status'] = ['publish'];
		if( current_user_can( 'read_private_docs' ) ) {
			$args['post_status'][] = 'private';
		}

        $docs = get_posts( $args );
        return count( $docs );
    }

    public function last_updated_time( $object ) {
        $date = betterdocs()->query->latest_updated_date( $object['taxonomy'], $object['slug'] );
        return $date;
    }

    public function knowledge_base_collection( $object ) {
        $knowledgebases = get_term_meta( $object['id'], 'doc_category_knowledge_base', true );
        if ( empty( $knowledgebases ) || ! is_array( $knowledgebases ) ) {
            return [];
        }
        return $knowledgebases;
    }

    /**
     * Persist the Knowledge Base assignment from the React Doc Categories slide-over.
     * Stores an array of KB slugs in `doc_category_knowledge_base` to match the
     * shape every downstream MKB query reads.
     *
     * @param array    $value KB slugs sent by the client.
     * @param \WP_Term $term  The doc_category term being updated.
     */
    public function update_knowledge_base_collection( $value, $term ) {
        if ( ! current_user_can( 'edit_doc_terms' ) ) {
            return;
        }

        $slugs = array_values( array_filter( array_map( 'sanitize_title', (array) $value ) ) );
        update_term_meta( $term->term_id, 'doc_category_knowledge_base', $slugs );
    }

    /**
     * Resolve the assigned KB slugs to their term titles for display in the
     * React Doc Categories table column.
     */
    public function knowledge_base_collection_titles( $object ) {
        $slugs = get_term_meta( $object['id'], 'doc_category_knowledge_base', true );
        if ( empty( $slugs ) || ! is_array( $slugs ) ) {
            return [];
        }

        $titles = [];
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'knowledge_base' );
            if ( $term && ! is_wp_error( $term ) ) {
                $titles[] = $term->name;
            }
        }
        return $titles;
    }

    public function knowledge_base_collection_ids( $object ) {
        $knowledge_base_ids = [];
        $knowledgebases = get_term_meta( $object['id'], 'doc_category_knowledge_base', true );

        if( empty( $knowledgebases ) || ! is_array( $knowledgebases ) ) {
            return[];
        }

        foreach( $knowledgebases as $knowledge_base_slug) {
            if ( ! is_string( $knowledge_base_slug ) && ! is_numeric( $knowledge_base_slug ) ) {
                continue;
            }
            $term = get_term_by('slug', $knowledge_base_slug, 'knowledge_base');
            if( ! empty( $term ) && ! is_wp_error( $term ) ) {
                array_push($knowledge_base_ids, $term->term_id);
            }
        }

        return $knowledge_base_ids;
    }

    public function get_knowledge_base_info( $object, $field_name, $request ) {
        $knowledge_base_terms     = [];
        $knowledgebase_categories = ! empty( $object['knowledge_base'] ) ? $object['knowledge_base'] : [];

        if ( ! is_array( $knowledgebase_categories ) ) {
            return $knowledge_base_terms;
        }

        foreach ( $knowledgebase_categories as $knowledge_base_category_id ) {
            $term = get_term( $knowledge_base_category_id );
            if ( $term && ! is_wp_error( $term ) ) {
                $term_link = get_term_link( $term );
                $knowledge_base_terms[] = [
                    'term_name' => $term->name,
                    'term_url'  => is_wp_error( $term_link ) ? '' : $term_link,
                    'term_slug' => $term->slug
                ];
            }
        }

        return $knowledge_base_terms;
    }

    public function get_knowledge_base_slug( $object, $field_name, $request ) {
        $knowledge_base_slugs     = [];
        $knowledgebase_categories = ! empty( $object['knowledge_base'] ) ? $object['knowledge_base'] : [];

        if ( ! is_array( $knowledgebase_categories ) ) {
            return $knowledge_base_slugs;
        }

        foreach ( $knowledgebase_categories as $knowledge_base_category_id ) {
            $term = get_term( $knowledge_base_category_id );
            if ( $term && ! is_wp_error( $term ) ) {
                $knowledge_base_slugs[] = $term->slug;
            }
        }

        return $knowledge_base_slugs;
    }

    /**
     * Filter the docs query by knowledge_base, and knowledge_base_slug parameters.
     *
     * @param array $args The query arguments.
     * @param WP_REST_Request $request The current REST API request.
     * @return array Modified query arguments.
     */
    public function filter_docs_query( $args, $request ) {
        // Filter by knowledge_base
        if ( isset( $request['knowledge_base'] ) ) {
            $knowledge_base = $request['knowledge_base'];

            if ( ! isset( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) {
                $args['tax_query'] = [];
            }

            $args['tax_query'][] = [
                'taxonomy' => 'knowledge_base',
                'field'    => 'term_id',
                'terms'    => $knowledge_base
            ];
        }

        // Filter by knowledge_base_slug
        if ( isset( $request['knowledge_base_slug'] ) ) {
            $knowledge_base_slug = $request['knowledge_base_slug'];

            if ( ! isset( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) {
                $args['tax_query'] = [];
            }

            $args['tax_query'][] = [
                'taxonomy' => 'knowledge_base',
                'field'    => 'slug',
                'terms'    => $knowledge_base_slug
            ];
        }

        return $args;
    }
}
