<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use WPML_Slug_Translation_Records_Factory;
use WPDeveloper\BetterDocs\Core\Request as FreeRequest;

/**
 * @property Settings $settings
 * @property Rewrite $rewrite
 * @method void set_perma_structure( $structure )
 * @method void set_query_vars( $vars )
 */
class Request extends FreeRequest {
    protected $mkb_enabled = false;

    public function init() {
        if ( is_admin() ) {
            return;
        }

        if ( isset( $this->settings ) && $this->settings instanceof Settings ) {
            $this->mkb_enabled = $this->settings->get( 'multiple_kb', false );
        }

        if ( $this->mkb_enabled && is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
            add_filter( 'docs_rewrite_rules', [$this, 'modify_base_url_wpml'], 10, 1 );
        }

        parent::init();

        if ( $this->mkb_enabled ) {
            $_root_structure                    = trim( $this->rewrite->get_base_slug(), '/' );
            $_knowledge_base_structure          = $_root_structure . '/%knowledge_base%';
            $_knowledge_base_category_structure = $_root_structure . '/%knowledge_base%/%doc_category%';

            $this->set_perma_structure( [
                'is_knowledge_base'          => $_knowledge_base_structure,
                'is_knowledge_base_category' => $_knowledge_base_category_structure
            ] );

            $this->set_query_vars( [
                'is_docs_feed'               => ['doc_category', 'knowledge_base'],
                'is_knowledge_base'          => ['knowledge_base'],
                'is_knowledge_base_category' => ['doc_category', 'knowledge_base']
            ] );
            
            // Hook into WordPress pre_get_posts to handle knowledge_base taxonomy queries
            // Priority 2 to run after the free plugin's setup_taxonomy_query (priority 1)
            add_action( 'pre_get_posts', [ $this, 'setup_knowledge_base_taxonomy_query' ], 2 );
            
            // Hook into template_redirect to reapply knowledge_base taxonomy flags
            // Priority 2 to run after the free plugin's reapply_taxonomy_flags (priority 1)
            add_action( 'template_redirect', [ $this, 'reapply_knowledge_base_taxonomy_flags' ], 2 );
            
            // Hook into status_header to prevent 404 for knowledge_base archives
            add_filter( 'status_header', [ $this, 'prevent_knowledge_base_404_status' ], 10, 2 );
        }
    }

    public function is_knowledge_base( $query_vars ) {
		// If we have a knowledge_base in query_vars, check if the second part is actually a post
		// Example URL: privaguide/breach-notification-privaguide
		// - privaguide = knowledge_base slug (from the URL structure)
		// - breach-notification-privaguide = could be either a KB term OR a post slug
		
		if ( isset( $query_vars['knowledge_base'] ) ) {
			$potential_post_slug = $query_vars['knowledge_base'];
			
			// Check if a post exists with this slug
			$post_exists = get_page_by_path( $potential_post_slug, OBJECT, 'docs' );
			
			if ( $post_exists ) {
				// This is actually a single post, not a knowledge_base archive
				// Return false so the next pattern (single docs) can match
				return false;
			}
			
			// No post found, check if it's a valid knowledge_base term
			$term_exists = $this->term_exists( $query_vars, 'knowledge_base' );
			return $term_exists;
		}
		
        return $this->term_exists( $query_vars, 'knowledge_base' );
    }

    public function is_knowledge_base_category( $query_vars ) {
        return $this->term_exists( $query_vars, 'doc_category' );
    }

    protected function term_exists( $query_vars, $taxonomy ) {
        if ( ! isset( $query_vars[$taxonomy] ) ) {
            return false;
        }

        // WordPress/Polylang stores non-Latin slugs URL-encoded (%e0%a6...) but the query var
        // arrives already decoded. Try the decoded form first, then the lowercase-encoded form.
        if ( term_exists( $query_vars[$taxonomy], $taxonomy ) ) {
            return true;
        }
        $encoded = strtolower( rawurlencode( $query_vars[$taxonomy] ) );
        if ( $encoded !== $query_vars[$taxonomy] && term_exists( $encoded, $taxonomy ) ) {
            return true;
        }
        return false;
    }

    /**
     * Parse the request
     *
     * @param \WP $wp
     * @return void
     */
    public function parse( $wp ) {
        if ( is_admin() ) {
            return;
        }

        parent::parse( $wp );

        /**
         * Decide which MKB is belong to this doc.
         */
        if ( $this->mkb_enabled ) {
            if ( isset( $wp->query_vars['name'], $wp->query_vars['post_type'] ) && $wp->query_vars['post_type'] === 'docs' ) {
                $_kb_slug = isset( $_COOKIE['last_knowledge_base'] ) ? trim( sanitize_text_field( wp_unslash( $_COOKIE['last_knowledge_base'] ) ) ) : '';

                if ( isset( $wp->query_vars['knowledge_base'] ) ) {
                    $_kb_slug = $wp->query_vars['knowledge_base'];
                }

                if ( $_kb_slug != '' ) {
                    $wp->query_vars['knowledge_base'] = $_kb_slug;
                } else {
                    $post = get_page_by_path( $wp->query_vars['name'], OBJECT, 'docs' );
                    if ( ! isset( $wp->query_vars['knowledge_base'] ) ) {
                        if ( isset( $post->ID ) ) {
                            $terms = wp_get_post_terms( $post->ID, 'knowledge_base' );
                            if ( is_array( $terms ) && ! empty( $terms ) ) {
                                $wp->query_vars['knowledge_base'] = $terms[0]->slug;
                            }
                        }
                    }
                }

                if ( ! isset( $wp->query_vars['doc_category'] ) ) {
                    $post = get_page_by_path( $wp->query_vars['name'], OBJECT, 'docs' );
                    if ( isset( $post->ID ) ) {
                        $terms     = wp_get_post_terms( $post->ID, 'doc_category' );
                        $_cat_slug = betterdocs()->query->get_doc_term_from_kb( $terms, $_kb_slug );

                        if ( $_cat_slug ) {
                            $wp->query_vars['doc_category'] = $_cat_slug;
                        }
                    }
                }
            }
        }
    }

    /**
     * Modify The Base Url Based On WPML Base Url Translation When The Following Structure Is Set As Single Doc Permalink (base_url/%knowledge_base%)
     *
     * @return void
     */
    public function modify_base_url_wpml( $rewrite_rules ) {
        // WPML_Slug_Translation_Records_Factory ships only with the WPML String
        // Translation addon, not WPML core. Without this guard, sites running WPML
        // core alone fatal on every front-end request (parse_request → docs_rewrite_rules).
        if ( ! class_exists( '\WPML_Slug_Translation_Records_Factory' ) ) {
            return $rewrite_rules;
        }

        $records_factory = new \WPML_Slug_Translation_Records_Factory();
        $records         = $records_factory->create( 'taxonomy' );
        $data            = $records->get_slug( 'knowledge_base' );
        $base_slug       = ! empty( $data->get_value( wpml_get_current_language() ) ) ? $data->get_value( wpml_get_current_language() ) : '';
        if ( ! empty( $base_slug ) ) {
            $single_docs_values = isset( $rewrite_rules['is_single_docs'] ) ? $rewrite_rules['is_single_docs'] : '';
            $single_doc_structure = explode( '/', $single_docs_values );
            unset( $single_doc_structure[0] );
            $rewrite_rules['is_single_docs'] = $base_slug . '/' . join( '/', $single_doc_structure );
        }
        return $rewrite_rules;
    }
    
    /**
     * Handle knowledge_base taxonomy query setup
     * Hooked into pre_get_posts action (WordPress standard hook)
     *
     * @param \WP_Query $query The WordPress query object
     */
    public function setup_knowledge_base_taxonomy_query( $query ) {
        if ( ! $this->mkb_enabled ) {
            return;
        }
        
        // Only run on main query and not in admin
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }
        
        // Check if this is a knowledge_base request
        if ( isset( $query->query_vars['knowledge_base'] ) && ! empty( $query->query_vars['knowledge_base'] ) ) {
            // If this is already identified as singular, don't touch it
            if ( $query->is_singular() || $query->is_singular ) {
                $query->is_404 = false;
                return;
            }
            
            // If we have 'name', 'docs', or 'p' query vars, this is a single post request
            // Don't interfere - let the free plugin handle it
            if ( ( isset( $query->query_vars['name'] ) && ! empty( $query->query_vars['name'] ) ) ||
                 ( isset( $query->query_vars['docs'] ) && ! empty( $query->query_vars['docs'] ) ) ||
                 ( isset( $query->query_vars['p'] ) && $query->query_vars['p'] > 0 ) ) {
                return;
            }
            
            // If we also have doc_category, this is a knowledge_base_category archive
            // The free plugin handles doc_category, so we only need to handle pure knowledge_base archives
            if ( ! isset( $query->query_vars['doc_category'] ) ) {
                $query->is_tax = true;
                $query->is_archive = true;
                $query->is_home = false;
                $query->is_404 = false;
                
                $term = $this->get_term_by_slug_or_encoded( $query->query_vars['knowledge_base'], 'knowledge_base' );
                if ( $term ) {
                    $query->queried_object = $term;
                    $query->queried_object_id = $term->term_id;
                    
                    // Set up tax_query using proper WP_Tax_Query class
                    if ( ! isset( $query->tax_query ) || ! is_a( $query->tax_query, 'WP_Tax_Query' ) ) {
                        $tax_query_args = [
                            [
                                'taxonomy' => 'knowledge_base',
                                'field' => 'slug',
                                'terms' => [ $term->slug ]
                            ]
                        ];
                        $query->tax_query = new \WP_Tax_Query( $tax_query_args );
                        $query->tax_query->queried_terms = [
                            'knowledge_base' => [
                                'terms' => [ $term->slug ],
                                'field' => 'slug'
                            ]
                        ];
                    }
                }
            }
        }
    }
    
    /**
     * Re-apply knowledge_base taxonomy flags on template_redirect
     * Hooked into template_redirect action (WordPress standard hook)
     */
    public function reapply_knowledge_base_taxonomy_flags() {
        if ( ! $this->mkb_enabled ) {
            return;
        }
        
        global $wp_query;
        
        // Check if we have knowledge_base in query vars
        if ( isset( $wp_query->query_vars['knowledge_base'] ) && ! empty( $wp_query->query_vars['knowledge_base'] ) ) {
            // If this is already identified as singular, don't touch it
            if ( $wp_query->is_singular() || $wp_query->is_singular ) {
                $wp_query->is_404 = false;
                return;
            }
            
            // If we have 'name', 'docs', or 'p' query vars, this is a single post request
            // Don't interfere - let the free plugin handle it
            if ( ( isset( $wp_query->query_vars['name'] ) && ! empty( $wp_query->query_vars['name'] ) ) ||
                 ( isset( $wp_query->query_vars['docs'] ) && ! empty( $wp_query->query_vars['docs'] ) ) ||
                 ( isset( $wp_query->query_vars['p'] ) && $wp_query->query_vars['p'] > 0 ) ) {
                return;
            }
            
            // Only handle pure knowledge_base archives (no doc_category)
            if ( ! isset( $wp_query->query_vars['doc_category'] ) || empty( $wp_query->query_vars['doc_category'] ) ) {
                // Re-apply the taxonomy flags
                $wp_query->is_tax = true;
                $wp_query->is_archive = true;
                $wp_query->is_home = false;
                $wp_query->is_404 = false;
                
                // Ensure the queried object is set
                if ( ! isset( $wp_query->queried_object ) || ! $wp_query->queried_object ) {
                    $term = $this->get_term_by_slug_or_encoded( $wp_query->query_vars['knowledge_base'], 'knowledge_base' );
                    if ( $term ) {
                        $wp_query->queried_object = $term;
                        $wp_query->queried_object_id = $term->term_id;
                        
                        // Set up tax_query using proper WP_Tax_Query class
                        if ( ! isset( $wp_query->tax_query ) || ! is_a( $wp_query->tax_query, 'WP_Tax_Query' ) ) {
                            $tax_query_args = [
                                [
                                    'taxonomy' => 'knowledge_base',
                                    'field' => 'slug',
                                    'terms' => [ $term->slug ]
                                ]
                            ];
                            $wp_query->tax_query = new \WP_Tax_Query( $tax_query_args );
                            $wp_query->tax_query->queried_terms = [
                                'knowledge_base' => [
                                    'terms' => [ $term->slug ],
                                    'field' => 'slug'
                                ]
                            ];
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Get a term by its slug, falling back to the URL-encoded form.
     *
     * WordPress/Polylang stores non-Latin slugs as lowercase percent-encoded strings
     * (e.g. %e0%a6%ac%e0%a7%87%e0%a6%9f%e0%a6%be%e0%a6%b0...) but the query var
     * arrives already decoded. Try the decoded form first, then the encoded form.
     *
     * This method is duplicated here so the pro plugin works even when running
     * alongside an older version of the free plugin that does not yet define it
     * in the parent class.
     *
     * @param string $slug     Slug to look up (may be decoded Unicode).
     * @param string $taxonomy Taxonomy name.
     * @return \WP_Term|false
     */
    protected function get_term_by_slug_or_encoded( $slug, $taxonomy ) {
        $term = get_term_by( 'slug', $slug, $taxonomy );
        if ( $term ) {
            return $term;
        }
        // Fallback: WordPress/Polylang stores non-Latin slugs as lowercase percent-encoded strings.
        $encoded = strtolower( rawurlencode( $slug ) );
        if ( $encoded !== $slug ) {
            $term = get_term_by( 'slug', $encoded, $taxonomy );
        }
        return $term ? $term : false;
    }

    /**
     * Prevent 404 for knowledge_base archives
     * Hooked into status_header filter (WordPress standard hook)
     *
     * @param string $status_header The HTTP status header
     * @param int $code The HTTP status code
     * @return string The modified status header
     */
    public function prevent_knowledge_base_404_status( $status_header, $code ) {
        if ( ! $this->mkb_enabled ) {
            return $status_header;
        }
        
        global $wp_query;
        
        // Check if we have knowledge_base query var and it's not a single post
        if ( $code == 404 && 
             isset( $wp_query->query_vars['knowledge_base'] ) && 
             ! empty( $wp_query->query_vars['knowledge_base'] ) && 
             ! isset( $wp_query->query_vars['name'] ) ) {
            return 'HTTP/1.1 200 OK';
        }
        
        return $status_header;
    }
}
