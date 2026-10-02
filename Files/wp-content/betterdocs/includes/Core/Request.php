<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;

class Request extends Base {
	/**
	 * Flag for already parsed or not
	 *
	 * Specially needed for those who don't update pro yet.
	 * @var boolean
	 */
	protected static $already_parsed = false;

	/**
	 * List of BetterDocs Perma Structure
	 * @var array
	 */
	private $perma_structure = [];

	/**
	 * List of BetterDocs Query Vars Agains Page Structure.
	 * @var array
	 */
	private $query_vars = [];

	/**
	 * List of Query Variables from $wp->query_vars.
	 * @var array
	 */
	private $wp_query_vars = [];

	/**
	 * Stores query vars from a request that was rejected as invalid (wrong KB/category slug).
	 * Used to block canonical redirects for those invalid URLs.
	 * @var array|null
	 */
	private $invalid_request_query_vars = null;

	/**
	 * Rewrite Class Reference of BetterDocs
	 * @var Rewrite
	 */
	protected $rewrite;

	/**
	 * Settings Class Reference of BetterDocs
	 * @var Settings
	 */
	protected $settings;

	public function __construct( Rewrite $rewrite, Settings $settings ) {
		$this->rewrite  = $rewrite;
		$this->settings = $settings;
	}

	public function init() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'validate_request_path' ], 1 );

        $this->perma_structure = [
            'is_docs'          => trim( $this->rewrite->get_base_slug(), '/' ),
            'is_docs_feed'     => trim( $this->rewrite->get_base_slug(), '/' ) . '/%feed%',
            'is_docs_category' => trim( $this->settings->get( 'category_slug', 'docs-category' ), '/' ) . '/%doc_category%',
            'is_docs_tag'      => trim( $this->settings->get( 'tag_slug', 'docs-tag' ), '/' ) . '/%doc_tag%',
            'is_single_docs'   => trim( $this->settings->get( 'permalink_structure', 'docs' ), '/' ) . '/%name%',
            'is_docs_author'   => trim( $this->rewrite->get_base_slug(), '/' ) . '/authors/%author%'
        ];

        $this->query_vars = [
            'is_docs'          => ['post_type'],
            'is_docs_feed'     => ['doc_category'],
            'is_docs_category' => ['doc_category'],
            'is_docs_tag'      => ['doc_tag'],
            'is_single_docs'   => ['name', 'docs', 'post_type', 'pagename', 'page_id'],
            'is_docs_author'   => ['post_type', 'author']
        ];

		add_action( 'parse_request', [ $this, 'parse' ] );

		/**
		 * Hook into pre_get_posts to set up taxonomy queries for category archives
		 */
		add_action( 'pre_get_posts', [ $this, 'setup_taxonomy_query' ], 1 );

		/**
		 * Hook into pre_get_posts at priority 20 to enforce 404 for invalid KB/category slugs.
		 * This runs before WordPress resolves templates but after parse_request sets query vars.
		 */
		add_action( 'pre_get_posts', [ $this, 'enforce_404_for_invalid_docs' ], 20 );

		/**
		 * Hook into template_redirect to re-apply taxonomy query flags
		 * This runs after pre_get_posts to ensure the flags stick
		 */
		add_action( 'template_redirect', [ $this, 'reapply_taxonomy_flags' ], 1 );

		/**
		 * Hook into status_header to prevent 404 for valid taxonomy archives
		 */
		add_filter( 'status_header', [ $this, 'prevent_404_status' ], 10, 2 );

		/**
		 * Hook into wp to ensure tax_query is always initialized
		 * This prevents null reference errors from WPML and other plugins
		 */
		add_action( 'wp', [ $this, 'ensure_tax_query_initialized' ], 1 );

		/**
		 * This is for Backward compatibility if pro not updated.
		 */
		add_action( 'parse_request', [ $this, 'backward_compability' ], 11 );

		/**
		 * Make Compatible With Permalink Manager Plugin
		 */
		add_filter( 'permalink_manager_detected_element_id', [ $this, 'provide_compatibility' ], 10, 3 );

		/**
		 * Hook into redirect_canonical to prevent redirects for invalid category-post combinations
		 */
		add_filter( 'redirect_canonical', [ $this, 'prevent_canonical_redirect_for_invalid_docs' ], 10, 2 );

		/**
		 * Hook into redirect_guess_404_permalink to prevent WordPress from guessing a redirect
		 * when an invalid KB/category slug results in a 404.
		 */
		add_filter( 'redirect_guess_404_permalink', [ $this, 'prevent_guess_404_redirect_for_invalid_docs' ], 10 );

		/**
		 * Hook into WPML's redirect filter to prevent WPML from redirecting invalid docs URLs
		 * to the canonical URL. This is the actual source of the redirect when WPML is active.
		 */
		add_filter( 'wpml_is_redirected', [ $this, 'prevent_wpml_redirect_for_invalid_docs' ], 10, 3 );

		/**
		 * Final catch-all: hook into wp_redirect to block any redirect for invalid docs URLs.
		 * This fires for ALL WordPress redirects regardless of source.
		 */
		add_filter( 'wp_redirect', [ $this, 'prevent_any_redirect_for_invalid_docs' ], 10, 2 );

		/**
		 * Hook into template_redirect to validate category-post relationships
		 * Priority 0 to run before WordPress canonical redirect (priority 10)
		 */
		add_action( 'template_redirect', [ $this, 'validate_single_docs_category_redirect' ], 0 );

		/**
		 * Hook into template_redirect to 301 non-canonical single doc URLs.
		 * Priority 5: after the validation above (0) so requests already headed for
		 * a 404 are left alone, and before WordPress canonical redirect (10).
		 */
		add_action( 'template_redirect', [ $this, 'redirect_to_canonical_docs_url' ], 5 );
	}

	public function provide_compatibility( $element_id, $uri_parts, $request_url ) {
		if ( $request_url == $this->settings->get( 'docs_slug' ) ) {
			$element_id = '';
		}
		return $element_id;
	}

	/**
	 * Enforce 404 for invalid docs KB/category URLs via pre_get_posts.
	 *
	 * This fires before WordPress determines the template, allowing us to mark
	 * the main query as a 404 when an invalid KB or category slug was detected
	 * during parse_request.
	 *
	 * @param WP_Query $query
	 */
	public function enforce_404_for_invalid_docs( $query ) {
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}
		if ( $this->invalid_request_query_vars !== null ) {
			$query->set_404();
			status_header( 404 );
			nocache_headers();

			// Kill ALL query vars that could cause WordPress to route to a doc/taxonomy template.
			// Without clearing these, WordPress still tries to build a tax_query from
			// doc_category/knowledge_base, selects the wrong template, and loads it
			// with a null post — causing PHP warnings in post-template functions.
			$query->set( 'name', '' );
			$query->set( 'pagename', '' );
			$query->set( 'p', -1 );
			$query->set( 'docs', '' );
			$query->set( 'doc_category', '' );
			$query->set( 'doc_tag', '' );
			$query->set( 'knowledge_base', '' );
			$query->set( 'post_type', '' );

			// Reset all routing flags — only is_404 should remain true.
			$query->is_single    = false;
			$query->is_singular  = false;
			$query->is_archive   = false;
			$query->is_tax       = false;
			$query->is_home      = false;
			$query->is_404       = true;
		}
	}

	/**
	 * Prevent canonical redirect for invalid docs category-post combinations
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $requested_url The requested URL.
	 * @return string|false The redirect URL or false to prevent redirect.
	 */
	public function prevent_canonical_redirect_for_invalid_docs( $redirect_url, $requested_url ) {
		global $wp_query;

		// IMPORTANT: By the time redirect_canonical fires, both $requested_url and $_SERVER['REQUEST_URI']
		// have already had the invalid KB slug stripped (resulting in double slashes like /docs//base/post/).
		// The only place we captured the original invalid slugs was during is_single_docs() at parse_request time.
		// So we use the stored invalid_request_query_vars to detect and block invalid redirects.
		if ( $this->invalid_request_query_vars !== null ) {
			return false; // Block the redirect, show 404 instead
		}

		$actual_url = home_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );

		// Legacy check: if post_type=docs is already set in query vars, validate category
		if ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === 'docs' &&
			 isset( $wp_query->query_vars['doc_category'] ) && isset( $wp_query->query_vars['name'] ) ) {

			$doc_category = $wp_query->query_vars['doc_category'];
			$post_name = $wp_query->query_vars['name'];

			// Get the post
			$post_id = ! empty( $wp_query->query_vars['p'] ) ? absint( $wp_query->query_vars['p'] ) : 0;
			$post = $post_id > 0 ? get_post( $post_id ) : get_page_by_path( $post_name, OBJECT, 'docs' );
			
			if ( ! $post ) {
				return false; // Post doesn't exist, show 404
			}

			// Get post's categories
			$post_categories = wp_get_post_terms( $post->ID, 'doc_category' );
			
			if ( empty( $post_categories ) || is_wp_error( $post_categories ) ) {
				// Post has no categories - only allow if URL is 'uncategorized'
				if ( $doc_category !== 'uncategorized' ) {
					return false;
				}
			} else {
				// Post has categories - check if it belongs to the category in URL
				$category_slugs = wp_list_pluck( $post_categories, 'slug' );
				
				// Handle hierarchical categories: check if any part of the path matches
				$category_parts = explode('/', trim($doc_category, '/'));
				$found_match = false;
				
				foreach ( $category_parts as $cat_slug ) {
					if ( in_array( $cat_slug, $category_slugs ) ) {
						$found_match = true;
						break;
					}
				}
				
				if ( ! $found_match ) {
					return false; // Post doesn't belong to this category, show 404
				}
			}
		}

		return $redirect_url;
	}

	/**
	 * Prevent redirect_guess_404_permalink for invalid docs KB/category URLs
	 *
	 * @param string|false $redirect_url The guessed redirect URL, or false.
	 * @return string|false
	 */
	public function prevent_guess_404_redirect_for_invalid_docs( $redirect_url ) {
		if ( $this->invalid_request_query_vars !== null ) {
			return false; // Don't guess a redirect for invalid docs URLs
		}
		return $redirect_url;
	}

	/**
	 * Prevent WPML from redirecting invalid docs KB/category URLs to canonical URLs.
	 *
	 * WPML detects that the request URL doesn't match the post's canonical permalink
	 * and issues a 301 redirect. We must block this when the URL has an invalid KB slug.
	 *
	 * @param string|false $redirect The redirect URL or false.
	 * @param int          $post_id  The post ID.
	 * @param WP_Query     $q        The query object.
	 * @return string|false
	 */
	public function prevent_wpml_redirect_for_invalid_docs( $redirect, $post_id, $q ) {
		// If we already detected an invalid KB/category slug during parse_request, block the redirect
		if ( $this->invalid_request_query_vars !== null ) {
			return false;
		}
		return $redirect;
	}

	/**
	 * Catch-all to prevent ANY WordPress wp_redirect() call for invalid docs URLs.
	 *
	 * This fires for all wp_redirect() calls regardless of source (canonical, WPML,
	 * redirect_guess_404_permalink, etc.). Returning empty string cancels the redirect.
	 *
	 * @param string $location The redirect URL.
	 * @param int    $status   The HTTP status code.
	 * @return string The redirect URL or empty string to cancel.
	 */
	public function prevent_any_redirect_for_invalid_docs( $location, $status ) {
		if ( $this->invalid_request_query_vars !== null ) {
			return ''; // Returning empty string cancels the redirect in wp_redirect()
		}
		return $location;
	}

	/**
	 * Validate single docs category relationship on template_redirect and force 404 if invalid
	 */
	public function validate_single_docs_category_redirect() {
		global $wp_query, $wp;

		// Use stored invalid query vars from parse time (most reliable approach)
		if ( $this->invalid_request_query_vars !== null ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();

			// We must actually serve the 404 template — set_404() alone doesn't stop the current template.
			// Hook into template_include to return the 404 template instead.
			add_filter( 'template_include', function( $template ) {
				$not_found = get_404_template();
				return $not_found ? $not_found : $template;
			}, 999 );
			return;
		}

		// Legacy check: if post_type=docs is already set in query vars, validate category.
		// `name` must be a non-empty string — WP populates it with '' for taxonomy archive
		// requests (e.g. comma-separated multi-category URLs like /docs-category/a,b/),
		// which `isset()` would treat as present and incorrectly trigger this single-doc branch.
		if ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === 'docs' &&
			 isset( $wp_query->query_vars['doc_category'] ) && ! empty( $wp_query->query_vars['name'] ) ) {

			$doc_category = $wp_query->query_vars['doc_category'];
			$post_name = $wp_query->query_vars['name'];

			// Get the post selected during request parsing when available.
			$post_id = ! empty( $wp_query->query_vars['p'] ) ? absint( $wp_query->query_vars['p'] ) : 0;
			$post = $post_id > 0 ? get_post( $post_id ) : get_page_by_path( $post_name, OBJECT, 'docs' );

			if ( ! $post ) {
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
				return;
			}

			// Get post's categories
			$post_categories = wp_get_post_terms( $post->ID, 'doc_category' );
			
			if ( empty( $post_categories ) || is_wp_error( $post_categories ) ) {
				// Post has no categories - only allow if URL is 'uncategorized'
				if ( $doc_category !== 'uncategorized' ) {
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
					return;
				}
			} else {
				// Post has categories - check if it belongs to the category in URL
				$category_slugs = wp_list_pluck( $post_categories, 'slug' );
				
				// Handle hierarchical categories: check if any part of the path matches
				$category_parts = explode('/', trim($doc_category, '/'));
				$found_match = false;
				
				foreach ( $category_parts as $cat_slug ) {
					if ( in_array( $cat_slug, $category_slugs ) ) {
						$found_match = true;
						break;
					}
				}
				
				if ( ! $found_match ) {
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
					return;
				}
			}
		}
	}

	/**
	 * 301 redirect a single doc to its canonical permalink.
	 *
	 * With `enable_category_hierarchy_slugs` enabled the single doc rewrite rule
	 * captures every segment between the base and the doc slug into `doc_category`
	 * (see Rewrite::rules), and the category validation above only requires ONE of
	 * those segments to match. That looseness is deliberate — a strict match would
	 * 404 legitimate Multiple KB and WPML URLs, where the KB slug and translated
	 * segments share the same capture group — but it also means a doc resolves on an
	 * unlimited number of URLs, e.g. /docs/anything/real-category/doc-slug/.
	 *
	 * Rather than tightening the match, this compares the requested category path
	 * against every path the doc legitimately has and redirects the rest. Every URL
	 * that resolves today keeps resolving; only the extra ones collapse.
	 */
	public function redirect_to_canonical_docs_url() {
		global $wp_query;

		/**
		 * Allow the canonical redirect to be disabled.
		 *
		 * @param bool $enabled Whether non-canonical single doc URLs should 301.
		 */
		if ( ! apply_filters( 'betterdocs_enable_canonical_redirect', true ) ) {
			return;
		}

		if ( is_admin() || wp_doing_ajax() || is_feed() || is_embed() || is_preview() || is_customize_preview() ) {
			return;
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( $request_method !== 'GET' ) {
			return;
		}

		if ( ! is_singular( 'docs' ) || is_404() ) {
			return;
		}

		/**
		 * The request is already headed for a 404 — leave it alone. Note that
		 * prevent_any_redirect_for_invalid_docs() cancels every wp_redirect() while
		 * this flag is set, so the redirect below would be swallowed anyway.
		 */
		if ( $this->invalid_request_query_vars !== null ) {
			return;
		}

		$requested_category = isset( $wp_query->query_vars['doc_category'] ) ? $wp_query->query_vars['doc_category'] : '';
		if ( ! is_string( $requested_category ) || $requested_category === '' ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$valid_paths = $this->get_valid_category_paths( $post_id );
		if ( empty( $valid_paths ) ) {
			return;
		}

		$requested_category = urldecode( trim( $requested_category, '/' ) );

		/**
		 * Under Multiple KB the knowledge base segment is parsed into its own query
		 * var, so put it back in front of the category chain before comparing —
		 * otherwise a crossed `/kb-a/category-of-kb-b/doc/` would look canonical.
		 */
		$requested_kb = isset( $wp_query->query_vars['knowledge_base'] ) ? $wp_query->query_vars['knowledge_base'] : '';
		if ( is_string( $requested_kb ) && $requested_kb !== '' ) {
			$requested_category = urldecode( trim( $requested_kb, '/' ) ) . '/' . $requested_category;
		}

		if ( in_array( $requested_category, $valid_paths, true ) ) {
			return; // Already canonical (or another legitimate category of this doc).
		}

		$canonical = get_permalink( $post_id );
		if ( ! $canonical ) {
			return;
		}

		// Keep the multipage segment the rewrite rule captured.
		$page = isset( $wp_query->query_vars['page'] ) ? absint( $wp_query->query_vars['page'] ) : 0;
		if ( $page > 1 ) {
			$canonical = trailingslashit( $canonical ) . user_trailingslashit( $page, 'single_paged' );
		}

		/**
		 * Belt and braces: never redirect a URL onto itself. The allowed set is built
		 * from the same term chains get_permalink() uses, so this should be
		 * unreachable, but a third party filtering the permalink could otherwise turn
		 * a redirect into a loop.
		 */
		$requested_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$canonical_path = wp_parse_url( $canonical, PHP_URL_PATH );
		if ( untrailingslashit( urldecode( (string) $requested_path ) ) === untrailingslashit( urldecode( (string) $canonical_path ) ) ) {
			return;
		}

		$query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		if ( $query_string !== '' ) {
			$canonical .= ( strpos( $canonical, '?' ) === false ? '?' : '&' ) . $query_string;
		}

		if ( wp_safe_redirect( $canonical, 301 ) ) {
			exit;
		}
	}

	/**
	 * Every category path a doc can legitimately be reached at.
	 *
	 * Includes the full parent/child chain of each assigned category, and — because
	 * the hierarchy rewrite rule folds the knowledge base slug into `doc_category`
	 * for the `docs/%knowledge_base%/%doc_category%` structure — the KB-prefixed
	 * variants too.
	 *
	 * @param int $post_id The doc id.
	 * @return string[] Normalised (urldecoded, unslashed) category paths.
	 */
	protected function get_valid_category_paths( $post_id ) {
		$cat_terms = wp_get_object_terms( $post_id, 'doc_category' );

		if ( is_wp_error( $cat_terms ) ) {
			return [];
		}

		$paths     = [];
		$cat_paths = [];

		if ( empty( $cat_terms ) ) {
			$paths[] = 'uncategorized';
		} else {
			foreach ( $cat_terms as $cat_term ) {
				$path = PostType::build_category_path( $cat_term );

				if ( $path !== '' ) {
					$paths[]                         = $path;
					$cat_paths[ $cat_term->term_id ] = $path;
				}
			}
		}

		if ( taxonomy_exists( 'knowledge_base' ) ) {
			$kb_terms = wp_get_object_terms( $post_id, 'knowledge_base', [ 'fields' => 'slugs' ] );

			if ( ! is_wp_error( $kb_terms ) && ! empty( $kb_terms ) ) {
				$kb_paths = [];

				/**
				 * Read the KB association once per category rather than once per
				 * KB/category pair. The wp_get_object_terms() call above primes the
				 * term meta cache for these terms (`update_term_meta_cache` defaults
				 * to true), so these reads are cache hits and add no queries.
				 *
				 * The association mirrors how PostType::post_link() picks the
				 * category via `doc_category_knowledge_base`. Without it a doc in
				 * KB A / category A and KB B / category B would treat the crossed
				 * `/kb-a/category-b/doc/` as canonical. A term with no association
				 * meta is unassigned rather than KB specific, so it stays valid
				 * under every KB — post_link() falls back the same way.
				 */
				$term_kbs = [];

				if ( ! empty( $cat_paths ) ) {
					foreach ( array_keys( $cat_paths ) as $term_id ) {
						$meta                 = get_term_meta( $term_id, 'doc_category_knowledge_base', true );
						$term_kbs[ $term_id ] = ( ! empty( $meta ) && is_array( $meta ) ) ? $meta : null;
					}
				}

				foreach ( $kb_terms as $kb_slug ) {
					if ( empty( $cat_paths ) ) {
						// Uncategorised doc: the KB slug is the only prefix there is.
						foreach ( $paths as $path ) {
							$kb_paths[] = $kb_slug . '/' . $path;
						}
						continue;
					}

					foreach ( $cat_paths as $term_id => $path ) {
						if ( $term_kbs[ $term_id ] !== null && ! in_array( $kb_slug, $term_kbs[ $term_id ], true ) ) {
							continue;
						}

						$kb_paths[] = $kb_slug . '/' . $path;
					}
				}

				$paths = array_merge( $paths, $kb_paths );
			}
		}

		/**
		 * Non-Latin slugs are stored URL encoded while the requested path arrives
		 * decoded, so normalise both sides before comparing.
		 */
		$paths = array_map(
			function ( $path ) {
				return urldecode( trim( $path, '/' ) );
			},
			$paths
		);

		/**
		 * Filter the category paths a doc is allowed to be reached at.
		 *
		 * @param string[] $paths   Valid category paths.
		 * @param int      $post_id The doc id.
		 */
		return array_values( array_unique( apply_filters( 'betterdocs_valid_docs_category_paths', $paths, $post_id ) ) );
	}

	/**
	 * Check if a URL matches a BetterDocs single docs permalink structure
	 * but has invalid KB/category slugs that don't match the post.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if the URL is a BetterDocs docs URL with invalid slugs.
	 */
	protected function is_invalid_docs_url( $url ) {
		// Get the path from the URL
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		
		// Check each permalink structure
		foreach ( $this->perma_structure as $_type => $structure ) {
			if ( $_type !== 'is_single_docs' ) {
				continue;
			}
			
			$_perma_vars = $this->is_perma_valid_for( $structure, $path );
			if ( ! $_perma_vars ) {
				continue;
			}
			
			// URL matches the single docs structure - now validate the slugs
			$name = isset( $_perma_vars['docs'] ) ? $_perma_vars['docs'] : ( isset( $_perma_vars['name'] ) ? $_perma_vars['name'] : '' );
			if ( empty( $name ) ) {
				continue;
			}
			
			// Check if the post exists
			$post = get_page_by_path( $name, OBJECT, 'docs' );
			if ( ! $post ) {
				return false; // Post doesn't exist at all - not our concern
			}
			
			// Validate knowledge_base slug if present
			if ( isset( $_perma_vars['knowledge_base'] ) && ! empty( $_perma_vars['knowledge_base'] ) ) {
				$post_kbs = wp_get_post_terms( $post->ID, 'knowledge_base', [ 'fields' => 'slugs' ] );
				if ( ! is_wp_error( $post_kbs ) && ! in_array( $_perma_vars['knowledge_base'], $post_kbs ) ) {
					return true; // Invalid KB slug
				}
			}
			
			// Validate doc_category slug if present
			if ( isset( $_perma_vars['doc_category'] ) && ! empty( $_perma_vars['doc_category'] ) ) {
				$category_parts = explode( '/', trim( $_perma_vars['doc_category'], '/' ) );
				$post_categories = wp_get_post_terms( $post->ID, 'doc_category', [ 'fields' => 'slugs' ] );
				
				if ( ! is_wp_error( $post_categories ) ) {
					$found = false;
					foreach ( $category_parts as $cat_slug ) {
						if ( in_array( $cat_slug, $post_categories ) ) {
							$found = true;
							break;
						}
					}
					if ( ! $found ) {
						return true; // Invalid category slug
					}
				}
			}
		}
		
		return false;
	}

	/**
	 * Set up taxonomy query for category archives
	 * This ensures WordPress recognizes requests with doc_category or knowledge_base as taxonomy archives
	 *
	 * @param \WP_Query $query The WordPress query object
	 */
	public function setup_taxonomy_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $this->invalid_request_query_vars !== null ) {
			return;
		}

		// Check if this is a doc_category request
		if ( isset( $query->query_vars['doc_category'] ) && ! empty( $query->query_vars['doc_category'] ) ) {
			// If this is already identified as singular, don't override it
			if ( $query->is_singular() || $query->is_singular ) {

				// Ensure it's not marked as 404
				$query->is_404 = false;
				return;
			}
			
			// Check if we have a post ID set (p query var)
			if ( isset( $query->query_vars['p'] ) && $query->query_vars['p'] > 0 ) {
				// Security check: if this is a private post and user can't read private docs, show 404
				$post = get_post( $query->query_vars['p'] );
				if ( $post && $post->post_status === 'private' && ! current_user_can( 'read_private_docs' ) ) {

					$query->is_404 = true;
					$query->is_single = false;
					$query->is_singular = false;
					return;
				}
				
				// Explicitly set this as a single post, not an archive or 404
				$query->is_single = true;
				$query->is_singular = true;
				$query->is_404 = false;
				$query->is_archive = false;
				$query->is_tax = false;
				return;
			}
			
			// Check if we have 'docs' query var (alternative to 'name')
			if ( isset( $query->query_vars['docs'] ) && ! empty( $query->query_vars['docs'] ) ) {
				// Explicitly set this as a single post
				$query->is_single = true;
				$query->is_singular = true;
				$query->is_404 = false;
				$query->is_archive = false;
				$query->is_tax = false;
				return;
			}
			
			// If 'name' is set, check if a post with that name exists
			// This prevents private docs from being incorrectly treated as category archives
			if ( isset( $query->query_vars['name'] ) && ! empty( $query->query_vars['name'] ) ) {
				$post_exists = get_page_by_path( $query->query_vars['name'], OBJECT, 'docs' );

				if ( $post_exists ) {
					// A post exists - this is a single doc request, not a category archive
					// Don't set taxonomy flags
					return;
				}
			}
			
			// Only set taxonomy flags if none of the above conditions are met (pure category archive)
			if ( ( ! isset( $query->query_vars['name'] ) || empty( $query->query_vars['name'] ) ) &&
				 ( ! isset( $query->query_vars['p'] ) || $query->query_vars['p'] <= 0 ) &&
				 ( ! isset( $query->query_vars['docs'] ) || empty( $query->query_vars['docs'] ) ) ) {

				// Set this as a taxonomy query
				$query->is_tax = true;
				$query->is_archive = true;
				$query->is_home = false;
				$query->is_404 = false; // Important: reset 404 flag
				
				// WordPress/Polylang may store non-Latin slugs URL-encoded (%e0%a6...) while the
				// query var arrives decoded (বেটারডক্স). Try both forms so the term lookup succeeds.
				$term = $this->get_term_by_slug_or_encoded( $query->query_vars['doc_category'], 'doc_category' );
				if ( $term ) {
					$query->queried_object = $term;
					$query->queried_object_id = $term->term_id;
					
					// Set up tax_query using proper WP_Tax_Query class
					if ( ! isset( $query->tax_query ) || ! is_a( $query->tax_query, 'WP_Tax_Query' ) ) {
						$tax_query_args = [
							[
								'taxonomy' => 'doc_category',
								'field' => 'slug',
								'terms' => [ $term->slug ]
							]
						];
						$query->tax_query = new \WP_Tax_Query( $tax_query_args );
						$query->tax_query->queried_terms = [
							'doc_category' => [
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
	 * Validate that the requested path matches the expected documentation root.
	 * This prevents URLs with invalid prefixes (e.g., /invalid/docs/...) from showing archive templates.
	 */
	public function validate_request_path() {
		if ( is_admin() || ! is_main_query() ) {
			return;
		}

		global $wp;
		// $wp->request contains the path relative to site root, without query string
		$request_path = isset( $wp->request ) ? urldecode( $wp->request ) : '';

		// Normalize request path: remove index.php/ and leading/trailing slashes
		$request_path = trim( preg_replace( '#^index\.php(/|$)#', '', $request_path ), '/' );

		// If the request path is empty, this is a query-string-only request (e.g. /?post_type=docs).
		// There is no URL prefix to validate in that case, so bail early.
		if ( $request_path === '' ) {
			return;
		}

		// Normalize base slug
		$docs_slug = $this->rewrite->get_base_slug();
		
		// If user is using a custom page as root, use that page's path
		if ( ! $this->settings->get( 'builtin_doc_page', true ) ) {
			 $docs_page_id = $this->settings->get( 'docs_page', 0 );
			 if ( $docs_page_id ) {
				 $page_path = get_page_uri( $docs_page_id );
				 if ( $page_path ) {
					 $docs_slug = $page_path;
				 }
			 }
		}
		
		$docs_slug = trim( $docs_slug, '/' );
		
		if ( empty( $docs_slug ) ) {
			return; 
		}

		// Check if this is a query we should validate
		$is_docs_query = is_singular( 'docs' ) || is_post_type_archive( 'docs' ) || is_tax( [ 'doc_category', 'knowledge_base', 'doc_tag' ] );
		$looks_like_docs_url = strpos( $request_path, $docs_slug ) !== false;

		// We validate if it's explicitly a docs query, OR if it looks like a docs URL but fell back to home/archive
		if ( ! $is_docs_query && ! ( $looks_like_docs_url && is_home() ) ) {
			return;
		}

		// If WordPress already correctly resolved this as a single docs post, trust that resolution.
		// The post was found and is valid — no need to validate the URL prefix at all.
		// Path validation is only meaningful for archive/tax pages whose URL leaked past the docs slug.
		if ( is_singular( 'docs' ) ) {
			return;
		}

		// Check if request path strictly starts with docs slug, category slug, or tag slug
		// Using # as delimiter, need to preg_quote
		$valid_slugs = [
			$docs_slug,
			trim( $this->settings->get( 'category_slug', 'docs-category' ), '/' ),
			trim( $this->settings->get( 'tag_slug', 'docs-tag' ), '/' )
		];

		// WPML/Polylang translate the registered rewrite slug per active language,
		// while $docs_slug from settings is always the default-language slug. Include
		// the post type's / taxonomies' currently-registered rewrite slugs so that
		// translated archive URLs (e.g. /en/support/ when settings.docs_slug is
		// "soporte") are accepted instead of being 404'd.
		$docs_pt = get_post_type_object( 'docs' );
		if ( $docs_pt && ! empty( $docs_pt->rewrite['slug'] ) ) {
			$valid_slugs[] = trim( $docs_pt->rewrite['slug'], '/' );
		}
		foreach ( [ 'doc_category', 'doc_tag', 'knowledge_base' ] as $tax ) {
			$tax_obj = get_taxonomy( $tax );
			if ( $tax_obj && ! empty( $tax_obj->rewrite['slug'] ) ) {
				$valid_slugs[] = trim( $tax_obj->rewrite['slug'], '/' );
			}
		}

		// Belt-and-suspenders for WPML's slug-translation feature, which may store
		// the translated slug separately from the post type's rewrite['slug'].
		if ( has_filter( 'wpml_get_translated_slug' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML-owned filter; name must be used verbatim.
			$wpml_slug = apply_filters( 'wpml_get_translated_slug', $docs_slug, 'docs' );
			if ( is_string( $wpml_slug ) && $wpml_slug !== '' ) {
				$valid_slugs[] = trim( $wpml_slug, '/' );
			}
		}

		$valid_prefixes = array_unique( array_filter( $valid_slugs ) );
		$valid_prefixes = array_map( function ( $slug ) { return preg_quote( $slug, '#' ); }, $valid_prefixes );

		// Allow optional language prefixes (e.g. /en/, /pt-br/) for WPML/Polylang/TranslatePress compatibility
		$lang_pattern = '(?:[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,4})?/)?';

		$prefix_pattern = '#^' . $lang_pattern . '(' . implode( '|', $valid_prefixes ) . ')(/|$)#';

		if ( ! preg_match( $prefix_pattern, $request_path ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Re-apply taxonomy flags on template_redirect
	 * This ensures the flags stick even if WordPress or other plugins reset them
	 */
	public function reapply_taxonomy_flags() {
		global $wp_query;

		// If we found invalid query vars, do not mess with the query flags.
		if ( $this->invalid_request_query_vars !== null ) {
			return;
		}

		// Check if we have doc_category or knowledge_base in query vars
		if ( isset( $wp_query->query_vars['doc_category'] ) && ! empty( $wp_query->query_vars['doc_category'] ) ) {
			// If this is already identified as singular, don't override it
			if ( $wp_query->is_singular() || $wp_query->is_singular ) {

				// Ensure it's not marked as 404
				$wp_query->is_404 = false;
				return;
			}
			
			// Check if we have a post ID set (p query var)
			if ( isset( $wp_query->query_vars['p'] ) && $wp_query->query_vars['p'] > 0 ) {

				
				// Security check: if this is a private post and user can't read private docs, show 404
				$post = get_post( $wp_query->query_vars['p'] );
				if ( $post && $post->post_status === 'private' && ! current_user_can( 'read_private_docs' ) ) {

					$wp_query->is_404 = true;
					$wp_query->is_single = false;
					$wp_query->is_singular = false;
					return;
				}
				
				// Explicitly set this as a single post, not an archive or 404
				$wp_query->is_single = true;
				$wp_query->is_singular = true;
				$wp_query->is_404 = false;
				$wp_query->is_archive = false;
				$wp_query->is_tax = false;
				return;
			}
			
			// Check if we have 'docs' query var (alternative to 'name')
			if ( isset( $wp_query->query_vars['docs'] ) && ! empty( $wp_query->query_vars['docs'] ) ) {

				// Verify the requested doc actually exists (and is visible to the
				// current user) before forcing a single-doc render. Category-in-path
				// permalinks put the doc slug in the `docs` var alongside a valid
				// doc_category; without this guard a missing slug under a real
				// category rendered the single template against a null post — an
				// HTTP 200 soft-404 plus "read property on null" warnings on every
				// (often bot) hit. See betterdocs/betterdocs#169.
				$doc_post    = get_page_by_path( $wp_query->query_vars['docs'], OBJECT, 'docs' );
				$doc_visible = $doc_post
					&& ( 'private' !== $doc_post->post_status || current_user_can( 'read_private_docs' ) );

				if ( ! $doc_visible ) {
					// No such doc (or private and not permitted) — serve a genuine
					// 404 instead of a single render against a null post.
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
					add_filter( 'template_include', function( $template ) {
						$not_found = get_404_template();
						return $not_found ? $not_found : $template;
					}, 999 );
					return;
				}

				// Explicitly set this as a single post
				$wp_query->is_single         = true;
				$wp_query->is_singular       = true;
				$wp_query->is_404            = false;
				$wp_query->is_archive        = false;
				$wp_query->is_tax            = false;
				$wp_query->queried_object    = $doc_post;
				$wp_query->queried_object_id = $doc_post->ID;
				return;
			}
			
			// If 'name' is set, check if a post with that name exists
			// This prevents posts from being incorrectly treated as category archives
			// (important when post slug == category slug, e.g. docs/old/new/new)
			if ( isset( $wp_query->query_vars['name'] ) && ! empty( $wp_query->query_vars['name'] ) ) {
				$post_exists = get_page_by_path( $wp_query->query_vars['name'], OBJECT, 'docs' );

				if ( $post_exists ) {
					// A post exists - explicitly mark as single post and clear any taxonomy flags.
					// Without this, WP may leave is_tax=true (set during parse_request because
					// doc_category is also present), causing redirect_canonical to redirect
					// the correct single-post URL to the category archive URL.
					$wp_query->is_single        = true;
					$wp_query->is_singular      = true;
					$wp_query->is_404           = false;
					$wp_query->is_archive       = false;
					$wp_query->is_tax           = false;
					$wp_query->queried_object    = $post_exists;
					$wp_query->queried_object_id = $post_exists->ID;
					return;
				}
			}
			
			// Only set taxonomy flags if none of the above conditions are met (pure category archive)
			if ( ( ! isset( $wp_query->query_vars['name'] ) || empty( $wp_query->query_vars['name'] ) ) &&
				 ( ! isset( $wp_query->query_vars['p'] ) || $wp_query->query_vars['p'] <= 0 ) &&
				 ( ! isset( $wp_query->query_vars['docs'] ) || empty( $wp_query->query_vars['docs'] ) ) ) {

				// Ensure the queried object is set or fetch the term
				$term = null;
				if ( isset( $wp_query->queried_object ) && $wp_query->queried_object ) {
					$term = $wp_query->queried_object;
				} else {
					// WordPress/Polylang may store non-Latin slugs URL-encoded; try both forms.
					$term = $this->get_term_by_slug_or_encoded( $wp_query->query_vars['doc_category'], 'doc_category' );
				}

				// Only if the term effectively exists, we set the flags
				if ( $term && ! is_wp_error( $term ) ) {
					// Also validate knowledge_base if present
					if ( isset( $wp_query->query_vars['knowledge_base'] ) && ! empty( $wp_query->query_vars['knowledge_base'] ) ) {
						if ( ! $this->get_term_by_slug_or_encoded( $wp_query->query_vars['knowledge_base'], 'knowledge_base' ) ) {
							return;
						}
					}

					// Re-apply the taxonomy flags
					$wp_query->is_tax = true;
					$wp_query->is_archive = true;
					$wp_query->is_home = false;
					$wp_query->is_404 = false;

					if ( ! isset( $wp_query->queried_object ) || ! $wp_query->queried_object ) {
						$wp_query->queried_object = $term;
						$wp_query->queried_object_id = $term->term_id;
						
						// Set up tax_query using proper WP_Tax_Query class
						if ( ! isset( $wp_query->tax_query ) || ! is_a( $wp_query->tax_query, 'WP_Tax_Query' ) ) {
							$tax_query_args = [
								[
									'taxonomy' => 'doc_category',
									'field' => 'slug',
									'terms' => [ $term->slug ]
								]
							];
							$wp_query->tax_query = new \WP_Tax_Query( $tax_query_args );
							$wp_query->tax_query->queried_terms = [
								'doc_category' => [
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
	 * Debug template redirect to see the query state
	 */
	/**
	 * Prevent 404 status for valid taxonomy archives
	 * 
	 * @param string $status_header The HTTP status header
	 * @param int $code The HTTP status code
	 * @return string The modified status header
	 */
	public function prevent_404_status( $status_header, $code ) {
		global $wp_query;

		// If we've explicitly marked this request as invalid (malformed KB/category slug), respect the 404!
		if ( $this->invalid_request_query_vars !== null ) {
			return $status_header;
		}

		// If a 404 is being sent but the queried object is a valid single docs post,
		// override with 200. This guards against false 404s on single docs pages.
		if ( $code == 404 &&
			isset( $wp_query->queried_object ) &&
			$wp_query->queried_object instanceof \WP_Post &&
			$wp_query->queried_object->post_type === 'docs' &&
			in_array( $wp_query->queried_object->post_status, [ 'publish', 'private' ], true )
		) {
			// Only allow if the current user can actually read this post
			if ( 'publish' === $wp_query->queried_object->post_status ||
				current_user_can( 'read_private_posts', $wp_query->queried_object->ID ) ) {
				return 'HTTP/1.1 200 OK';
			}
		}

		// If this is a 404 but we have doc_category or doc_tag query vars, change it to 200
		// We check the query vars instead of is_tax because the flags get reset by WordPress
		if ( $code == 404 && (
			(isset($wp_query->query_vars['doc_category']) && ! empty($wp_query->query_vars['doc_category'])) ||
			(isset($wp_query->query_vars['doc_tag']) && ! empty($wp_query->query_vars['doc_tag']))
		) ) {
			// A single-doc request (category-in-path permalinks carry the doc slug
			// in the `docs`/`name` var alongside doc_category) must resolve to a real
			// doc. A valid doc is already served 200 by the queried-object branch
			// above; if we reach here with a single-doc indicator but no resolvable
			// post, the doc does not exist — keep the genuine 404 rather than forcing
			// a soft-404 200 off the category term alone. See betterdocs/betterdocs#169.
			$single_doc_slug = '';
			if ( isset( $wp_query->query_vars['docs'] ) && ! empty( $wp_query->query_vars['docs'] ) ) {
				$single_doc_slug = $wp_query->query_vars['docs'];
			} elseif ( isset( $wp_query->query_vars['name'] ) && ! empty( $wp_query->query_vars['name'] ) ) {
				$single_doc_slug = $wp_query->query_vars['name'];
			}
			if ( '' !== $single_doc_slug && ! get_page_by_path( $single_doc_slug, OBJECT, 'docs' ) ) {
				return $status_header;
			}

			// Validate existence before forcing 200
			// Use encoded fallback so Bengali/Arabic/CJK slugs are found correctly.
			if ( isset($wp_query->query_vars['doc_category']) && ! empty($wp_query->query_vars['doc_category']) ) {
				$term = $this->get_term_by_slug_or_encoded( $wp_query->query_vars['doc_category'], 'doc_category' );
				if ( ! $term || is_wp_error( $term ) ) {
					return $status_header;
				}
			}
			if ( isset($wp_query->query_vars['doc_tag']) && ! empty($wp_query->query_vars['doc_tag']) ) {
				$term = $this->get_term_by_slug_or_encoded( $wp_query->query_vars['doc_tag'], 'doc_tag' );
				if ( ! $term || is_wp_error( $term ) ) {
					return $status_header;
				}
			}
			if ( isset($wp_query->query_vars['knowledge_base']) && ! empty($wp_query->query_vars['knowledge_base']) ) {
				$term = $this->get_term_by_slug_or_encoded( $wp_query->query_vars['knowledge_base'], 'knowledge_base' );
				if ( ! $term || is_wp_error( $term ) ) {
					return $status_header;
				}
			}

			return 'HTTP/1.1 200 OK';
		}

		return $status_header;
	}

	/**
	 * Ensure tax_query is always initialized as an object
	 * This prevents null reference errors from WPML and other plugins
	 * Only applies to BetterDocs post type and taxonomies
	 */
	public function ensure_tax_query_initialized() {
		global $wp_query;
		
		// Only apply to BetterDocs-related queries
		$is_betterdocs_query = false;
		
		// Check if this is a docs post type query
		if ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === 'docs' ) {
			$is_betterdocs_query = true;
		}
		
		// Check if this is a BetterDocs taxonomy query
		if ( isset( $wp_query->query_vars['doc_category'] ) && ! empty( $wp_query->query_vars['doc_category'] ) ) {
			$is_betterdocs_query = true;
		}
		
		if ( isset( $wp_query->query_vars['doc_tag'] ) && ! empty( $wp_query->query_vars['doc_tag'] ) ) {
			$is_betterdocs_query = true;
		}
		
		if ( isset( $wp_query->query_vars['knowledge_base'] ) && ! empty( $wp_query->query_vars['knowledge_base'] ) ) {
			$is_betterdocs_query = true;
		}
		
		// Check if queried object is a BetterDocs taxonomy term
		if ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->taxonomy ) ) {
			if ( in_array( $wp_query->queried_object->taxonomy, [ 'doc_category', 'doc_tag', 'knowledge_base' ] ) ) {
				$is_betterdocs_query = true;
			}
		}
		
		// Only proceed if this is a BetterDocs-related query
		if ( ! $is_betterdocs_query ) {
			return;
		}
		
		// Only initialize if it's not already a proper WP_Tax_Query instance
		if ( ! isset( $wp_query->tax_query ) || ! is_a( $wp_query->tax_query, 'WP_Tax_Query' ) ) {
			// Create a proper WP_Tax_Query instance with empty queries
			$wp_query->tax_query = new \WP_Tax_Query( [] );
			$wp_query->tax_query->queried_terms = [];
		}
		
		// For WPML compatibility: if queried_object is null, set it to an empty object
		// but only if we're actually on a taxonomy page (is_tax is true)
		if ( ! isset( $wp_query->queried_object ) && $wp_query->is_tax ) {
			// Create a minimal WP_Term-like object to prevent errors
			$wp_query->queried_object = new \stdClass();
			$wp_query->queried_object->term_id = 0;
			$wp_query->queried_object->name = '';
			$wp_query->queried_object->slug = '';
			$wp_query->queried_object->term_group = 0;
			$wp_query->queried_object->term_taxonomy_id = 0;
			$wp_query->queried_object->taxonomy = 'doc_category';
			$wp_query->queried_object->description = '';
			$wp_query->queried_object->parent = 0;
			$wp_query->queried_object->count = 0;
			$wp_query->queried_object->filter = 'raw';
		}
	}

	protected function is_docs( &$query_vars ) {
		if ( ! $this->settings->get( 'builtin_doc_page', true ) ) {
			$query_vars['post_type'] = 'page';
			$query_vars['name']      = trim( $this->rewrite->get_base_slug(), '/' );
		}

		return $query_vars;
	}

	public function is_docs_feed( $query_vars ) {
		global $wp_rewrite;
		return isset( $query_vars['feed'] ) && in_array( $query_vars['feed'], $wp_rewrite->feeds );
	}

    public function is_docs_author( $query_vars ) {
        return isset( $query_vars['author'] ) ? true : false;
    }

    protected function is_single_docs( &$query_vars ) {
        // Check for both 'name' and 'docs' query variables
        if ( ! isset( $query_vars['name'] ) && ! isset( $query_vars['docs'] ) ) {
            return false;
        }

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- frontend single-doc URL resolution; queries vary per request and run on `parse_request`, before object cache is reliable.
		global $wpdb;
		$name = isset( $query_vars['docs'] ) ? $query_vars['docs'] : $query_vars['name'];


		// If doc_category is specified in the URL, validate that the post belongs to that category
	if ( isset( $query_vars['doc_category'] ) ) {
		$doc_category = $query_vars['doc_category'];


		// Handle hierarchical category slugs (e.g., parent/child/grandchild)
		$category_parts = explode('/', trim($doc_category, '/'));
		$target_category_slug = end($category_parts); // Get the last part as the target category


		// First, check if the post exists.
		// When MKB is active, multiple translated posts share the same slug — one per KB.
		// We MUST join the KB taxonomy so we select the post for the correct language/KB.
		// Polylang/multilingual plugins store post_name URL-encoded; try the encoded form first.
		$_encoded_name = rawurlencode( $name );

		if ( isset( $query_vars['knowledge_base'] ) && ! empty( $query_vars['knowledge_base'] ) ) {
			// KB-aware lookup: only select the post that is assigned to this KB.
			// Also join doc_category when present so that, on Polylang/WPML sites
			// where multiple translated posts share both the same post_name and
			// the same KB term (e.g. all language variants assigned to the
			// "advice" KB), we land on the translation whose doc_category matches
			// the URL — not the one the DB happens to return first.
			$_kb_slug     = $query_vars['knowledge_base'];
			$_kb_slug_enc = strtolower( rawurlencode( $_kb_slug ) );

			$_cat_target     = $target_category_slug;
			$_cat_target_enc = strtolower( rawurlencode( $_cat_target ) );

			$_post_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->term_relationships} tr_kb ON tr_kb.object_id = p.ID
					INNER JOIN {$wpdb->term_taxonomy} tt_kb ON tt_kb.term_taxonomy_id = tr_kb.term_taxonomy_id AND tt_kb.taxonomy = 'knowledge_base'
					INNER JOIN {$wpdb->terms} t_kb ON t_kb.term_id = tt_kb.term_id
					INNER JOIN {$wpdb->term_relationships} tr_cat ON tr_cat.object_id = p.ID
					INNER JOIN {$wpdb->term_taxonomy} tt_cat ON tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id AND tt_cat.taxonomy = 'doc_category'
					INNER JOIN {$wpdb->terms} t_cat ON t_cat.term_id = tt_cat.term_id
					WHERE p.post_name = %s AND p.post_type = 'docs'
					AND t_kb.slug IN (%s, %s)
					AND t_cat.slug IN (%s, %s)
					LIMIT 1",
					esc_sql( $_encoded_name ),
					esc_sql( $_kb_slug ),
					esc_sql( $_kb_slug_enc ),
					esc_sql( $_cat_target_enc ),
					esc_sql( $_cat_target )
				)
			);
			// Fallback: post_name stored as decoded Unicode
			if ( ! $_post_id && $_encoded_name !== $name ) {
				$_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->term_relationships} tr_kb ON tr_kb.object_id = p.ID
						INNER JOIN {$wpdb->term_taxonomy} tt_kb ON tt_kb.term_taxonomy_id = tr_kb.term_taxonomy_id AND tt_kb.taxonomy = 'knowledge_base'
						INNER JOIN {$wpdb->terms} t_kb ON t_kb.term_id = tt_kb.term_id
						INNER JOIN {$wpdb->term_relationships} tr_cat ON tr_cat.object_id = p.ID
						INNER JOIN {$wpdb->term_taxonomy} tt_cat ON tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id AND tt_cat.taxonomy = 'doc_category'
						INNER JOIN {$wpdb->terms} t_cat ON t_cat.term_id = tt_cat.term_id
						WHERE p.post_name = %s AND p.post_type = 'docs'
						AND t_kb.slug IN (%s, %s)
						AND t_cat.slug IN (%s, %s)
						LIMIT 1",
						esc_sql( $name ),
						esc_sql( $_kb_slug ),
						esc_sql( $_kb_slug_enc ),
						esc_sql( $_cat_target_enc ),
						esc_sql( $_cat_target )
					)
				);
			}

			// Fallback to the KB-only lookup (no category filter) when nothing
			// matched both KB + category. Keeps single-language behaviour intact
			// and lets the category-validation block below handle any genuine
			// mismatch by setting invalid_request_query_vars.
			if ( ! $_post_id ) {
				$_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
						INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'knowledge_base'
						INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
						WHERE p.post_name = %s AND p.post_type = 'docs'
						AND t.slug IN (%s, %s)
						LIMIT 1",
						esc_sql( $_encoded_name ),
						esc_sql( $_kb_slug ),
						esc_sql( $_kb_slug_enc )
					)
				);
				if ( ! $_post_id && $_encoded_name !== $name ) {
					$_post_id = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT p.ID FROM {$wpdb->posts} p
							INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
							INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'knowledge_base'
							INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
							WHERE p.post_name = %s AND p.post_type = 'docs'
							AND t.slug IN (%s, %s)
							LIMIT 1",
							esc_sql( $name ),
							esc_sql( $_kb_slug ),
							esc_sql( $_kb_slug_enc )
						)
					);
				}
			}
		} else {
			// No KB in URL — disambiguate via doc_category. WPML/Polylang assign each
			// translated post the same post_name (e.g. "spacious-family-home..."),
			// so a plain `WHERE post_name = ... LIMIT 1` returns whichever language
			// the DB serves first. When that pick does not belong to the category in
			// the URL, the validation below falsely fires a 404 even though the
			// correctly-translated post exists. Join doc_category so we land on the
			// translation that actually owns the requested category.
			$_cat_target     = $target_category_slug;
			$_cat_target_enc = strtolower( rawurlencode( $_cat_target ) );
			$_post_id        = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
					INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'doc_category'
					INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
					WHERE p.post_name = %s AND p.post_type = 'docs'
					AND t.slug IN (%s, %s)
					LIMIT 1",
					esc_sql( $_encoded_name ),
					esc_sql( $_cat_target_enc ),
					esc_sql( $_cat_target )
				)
			);
			if ( ! $_post_id && $_encoded_name !== $name ) {
				$_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
						INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'doc_category'
						INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
						WHERE p.post_name = %s AND p.post_type = 'docs'
						AND t.slug IN (%s, %s)
						LIMIT 1",
						esc_sql( $name ),
						esc_sql( $_cat_target_enc ),
						esc_sql( $_cat_target )
					)
				);
			}

			// Fallback: post exists with this name but not under the requested
			// category. Let the category-validation block below handle the 404 so
			// we keep the existing single-language behaviour intact.
			if ( ! $_post_id ) {
				$_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1",
						esc_sql( $_encoded_name ),
						'docs'
					)
				);
				if ( ! $_post_id && $_encoded_name !== $name ) {
					$_post_id = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1",
							esc_sql( $name ),
							'docs'
						)
					);
				}
			}
		}

		// If post exists, validate it belongs to the category in the URL
	if ( $_post_id > 0 ) {
		
		// When hierarchical slugs are enabled, check if post belongs to any category in the path
		$has_category = false;
		
		if ( $this->settings->get( 'enable_category_hierarchy_slugs' ) && count($category_parts) > 1 ) {

			// Check if post belongs to ANY category in the hierarchy path
			// For example, if URL is "update/overview", check for both "update" and "overview"
			$category_slugs_to_check = $category_parts;
			
			foreach ( $category_slugs_to_check as $cat_slug ) {

				// rawurlencode produces uppercase hex (%E0%...) but WP/Polylang stores lowercase (%e0%).
				// Always normalise to lowercase so the slug IN (...) comparison succeeds.
				$_encoded_cat = strtolower( rawurlencode( $cat_slug ) );
				$cat_check = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
						INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
						INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
						WHERE tr.object_id = %d AND t.slug IN (%s, %s) AND tt.taxonomy = %s",
						$_post_id,
						esc_sql( $_encoded_cat ),
						esc_sql( $cat_slug ),
						'doc_category'
					)
				);
				
				if ( $cat_check > 0 ) {
					// If knowledge_base is set, verify the category belongs to that KB
					if ( isset( $query_vars['knowledge_base'] ) ) {

						// Get the term ID - use our helper that tries both decoded and encoded forms.
						$term = $this->get_term_by_slug_or_encoded( $cat_slug, 'doc_category' );
						if ( $term ) {
							$term_kbs = get_term_meta( $term->term_id, 'doc_category_knowledge_base', true );

							// Primary check: category's stored KB meta includes the requested KB.
							if ( is_array( $term_kbs ) && in_array( $query_vars['knowledge_base'], $term_kbs ) ) {
								$has_category = true;
								break;
							}
							
							// Fallback: for Polylang/multilingual sites the term meta may store the
							// original-language KB slug while the URL uses the translated slug.
							// Verify instead that the post is actually assigned to the requested KB.
							// wp_get_post_terms may return slugs URL-encoded (Polylang) or decoded (standard WP).
							// Normalise everything to lowercase for comparison.
							$kb_slug_url = $query_vars['knowledge_base'];
							$kb_slug_enc = strtolower( rawurlencode( $kb_slug_url ) );
							$post_kbs = wp_get_post_terms( $_post_id, 'knowledge_base', [ 'fields' => 'slugs' ] );
							$post_kbs_lower = array_map( 'strtolower', is_array( $post_kbs ) ? $post_kbs : [] );
							if ( ! is_wp_error( $post_kbs ) &&
								 ( in_array( $kb_slug_url, $post_kbs_lower ) || in_array( $kb_slug_enc, $post_kbs_lower ) ) ) {
								$has_category = true;
								break;
							}
						}
					} else {

						// No KB in URL, so any category match is valid
						$has_category = true;
						break;
					}
				}
			}
			} else {
			// Non-hierarchical or single category - check only the target category
			// Always lowercase-encode so the slug matches WP/Polylang's stored lowercase hex.
			$_encoded_target = strtolower( rawurlencode( $target_category_slug ) );
			$has_category = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
					WHERE tr.object_id = %d AND t.slug IN (%s, %s) AND tt.taxonomy = %s",
					$_post_id,
					esc_sql( $_encoded_target ),
					esc_sql( $target_category_slug ),
					'doc_category'
				)
			);
			
		// If knowledge_base is set and category was found, verify the POST belongs to that KB.
			// We use the post's actual KB taxonomy terms as the source of truth,
			// NOT the doc_category_knowledge_base meta (which can be stale or misconfigured).
			// Only block if the post is explicitly assigned to OTHER KBs that don't include the requested one.
			if ( $has_category && isset( $query_vars['knowledge_base'] ) ) {
				$post_kbs = wp_get_post_terms( $_post_id, 'knowledge_base', [ 'fields' => 'slugs' ] );
				if ( ! is_wp_error( $post_kbs ) && ! empty( $post_kbs ) ) {
					$kb_slug         = $query_vars['knowledge_base'];
					// PHP's rawurlencode() produces uppercase (%E0%A6...) but WordPress/Polylang stores
					// slugs with lowercase hex (%e0%a6...). Normalise both sides to lowercase.
					$kb_slug_encoded = strtolower( rawurlencode( $kb_slug ) );
					$post_kbs_lower  = array_map( 'strtolower', $post_kbs );
					// Check decoded form (standard WP) and encoded form (Polylang).
					if ( ! in_array( $kb_slug, $post_kbs_lower ) && ! in_array( $kb_slug_encoded, $post_kbs_lower ) ) {
						$has_category = false;
					}
				}
			}


		}

		// Special handling for uncategorized docs
		if ( ! $has_category && $target_category_slug === 'uncategorized' ) {
			// Check if the post has no categories assigned at all
			$category_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tr.object_id = %d AND tt.taxonomy = %s",
					$_post_id,
					'doc_category'
				)
			);

			// If post has no categories, allow it for uncategorized URL
			if ( $category_count == 0 ) {
				$has_category = true;
			}
		}


		// If post doesn't belong to the target category, return false (404)
		if ( ! $has_category ) {
			// Remember these query vars so we can block any canonical redirect for this invalid URL
			$this->invalid_request_query_vars = $query_vars;
			return false;
		}

		// If hierarchical slugs are enabled and we found a post, validate the full hierarchy
		if ( $this->settings->get( 'enable_category_hierarchy_slugs' ) && count($category_parts) > 1 ) {
			// Get the post's category terms
			$post_categories = wp_get_object_terms( $_post_id, 'doc_category' );

			if ( ! empty( $post_categories ) ) {
				$found_valid_hierarchy = false;

				foreach ( $post_categories as $post_category ) {
					// Build the hierarchy path for this category
					$hierarchy_path = [];
					$current_term = $post_category;

					// Build path from child to parent
					while ( $current_term ) {
						array_unshift( $hierarchy_path, $current_term->slug );
						$current_term = $current_term->parent ? get_term( $current_term->parent, 'doc_category' ) : null;
					}

					// Check if this hierarchy matches the URL structure.
					// WordPress/Polylang store non-Latin term slugs URL-encoded
					// (%e0%a6...) while $doc_category arrives decoded from
					// is_perma_valid_for. Compare in the decoded form so Bengali,
					// Arabic, CJK, etc. hierarchies actually match.
					$built_path = implode('/', $hierarchy_path);
					$built_path_norm = urldecode( $built_path );
					$doc_cat_norm    = urldecode( $doc_category );

					// Allow partial path matching to accommodate KB-prefixed URLs or partial hierarchies.
					// Using substr for broad PHP version compatibility (equivalent to str_ends_with).
					$is_suffix = strlen( $built_path_norm ) > 0 && substr( $doc_cat_norm, -strlen( $built_path_norm ) ) === $built_path_norm;
					$is_prefix = strlen( $doc_cat_norm ) > 0 && substr( $built_path_norm, -strlen( $doc_cat_norm ) ) === $doc_cat_norm;

					if ( $built_path_norm === $doc_cat_norm || $is_suffix || $is_prefix ) {
						$found_valid_hierarchy = true;
						break;
					}
				}

				// If no valid hierarchy found, return false (404)
				if ( ! $found_valid_hierarchy ) {
					return false;
				}
			}
		}
	}
		} else {
			// First, check if the post exists.
			// When MKB is active, multiple translated posts share the same slug — one per KB.
			// Join the KB taxonomy when knowledge_base is in the URL to find the right post.
			$_encoded_name = rawurlencode( $name );

			if ( isset( $query_vars['knowledge_base'] ) && ! empty( $query_vars['knowledge_base'] ) ) {
				$_kb_slug     = $query_vars['knowledge_base'];
				$_kb_slug_enc = strtolower( rawurlencode( $_kb_slug ) );
				$_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
						INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'knowledge_base'
						INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
						WHERE p.post_name = %s AND p.post_type = 'docs'
						AND t.slug IN (%s, %s)
						LIMIT 1",
						esc_sql( $_encoded_name ),
						esc_sql( $_kb_slug ),
						esc_sql( $_kb_slug_enc )
					)
				);
				if ( ! $_post_id && $_encoded_name !== $name ) {
					$_post_id = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT p.ID FROM {$wpdb->posts} p
							INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
							INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'knowledge_base'
							INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
							WHERE p.post_name = %s AND p.post_type = 'docs'
							AND t.slug IN (%s, %s)
							LIMIT 1",
							esc_sql( $name ),
							esc_sql( $_kb_slug ),
							esc_sql( $_kb_slug_enc )
						)
					);
				}
			} else {
				$_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1",
						esc_sql( $_encoded_name ),
						'docs'
					)
				);
				if ( ! $_post_id && $_encoded_name !== $name ) {
					$_post_id = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1",
							esc_sql( $name ),
							'docs'
						)
					);
				}
			}


			// If knowledge_base is set, validate the post actually belongs to that KB.
			// wp_get_post_terms may return slugs URL-encoded (Polylang) or decoded (standard WP).
			// Check both forms so the match works regardless of storage format.
			if ( $_post_id > 0 && isset( $query_vars['knowledge_base'] ) ) {
				$post_kbs = wp_get_post_terms( $_post_id, 'knowledge_base', [ 'fields' => 'slugs' ] );
				if ( ! is_wp_error( $post_kbs ) && ! empty( $post_kbs ) ) {
					$kb_slug         = $query_vars['knowledge_base'];
					$kb_slug_encoded = strtolower( rawurlencode( $kb_slug ) );
					$post_kbs_lower  = array_map( 'strtolower', $post_kbs );
					if ( ! in_array( $kb_slug, $post_kbs_lower ) && ! in_array( $kb_slug_encoded, $post_kbs_lower ) ) {
						// Remember these query vars so we can block any canonical redirect for this invalid URL
						$this->invalid_request_query_vars = $query_vars;
						return false;
					}
				}
				// If post has no KB terms → allow it (not assigned to any KB explicitly).
			}
		}

		if ( $_post_id > 0 ) {
			// Preserve the post selected with the requested category/KB so WordPress cannot resolve a duplicate slug to another document.
			$query_vars['p'] = $_post_id;
		}

		return $_post_id > 0;
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	protected function is_docs_category( $query_vars ) {
		$result = $this->term_exists( $query_vars, 'doc_category' );
		return $result;
	}

	protected function is_docs_tag( $query_vars ) {
		return $this->term_exists( $query_vars, 'doc_tag' );
	}

	protected function term_exists( $query_vars, $taxonomy ) {
		if ( ! isset( $query_vars[ $taxonomy ] ) ) {
			return false;
		}

		// WordPress/Polylang stores non-Latin slugs URL-encoded (%e0%a6...) but the query var
		// arrives already decoded (e.g. বেটারডক্স). Try the decoded form first, then encoded.
		if ( term_exists( $query_vars[ $taxonomy ], $taxonomy ) ) {
			return true;
		}
		$encoded = strtolower( rawurlencode( $query_vars[ $taxonomy ] ) );
		if ( $encoded !== $query_vars[ $taxonomy ] && term_exists( $encoded, $taxonomy ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Look up a taxonomy term by slug, trying both the raw (possibly Unicode-decoded) form
	 * and the lowercase URL-encoded form that WordPress/Polylang stores for non-Latin slugs.
	 *
	 * @param string $slug     Slug to look up (may be decoded Unicode, e.g. বেটারডক্স).
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

	public function set_perma_structure( $structures = [] ) {
		$this->perma_structure = array_merge( $this->perma_structure, $structures );
	}

	public function set_query_vars( $query_vars = [] ) {
		$this->query_vars = array_merge( $this->query_vars, $query_vars );
	}

	public function backward_compability( $wp ) {
		if ( static::$already_parsed ) {
			return;
		}

		$this->permalink_magic( $wp );
	}

	public function parse( $wp ) {
		static::$already_parsed = true;

		// An API Reference rewrite rule already matched (/docs/api/{slug}).
		// The permalink magic below re-interprets the raw path against the
		// docs/category/KB structures and would hijack the request whenever a
		// doc_category or knowledge_base term shares the reference's slug —
		// an explicit CPT match always wins.
		if ( isset( $wp->query_vars['betterdocs_api_ref'] ) ) {
			return;
		}

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- legacy public filter name, retained for back-compat with Pro/extensions.
        $this->perma_structure = apply_filters('docs_rewrite_rules', $this->perma_structure);

        $this->permalink_magic( $wp );
    }

	protected function permalink_magic( $wp ) {
		$this->wp_query_vars = $wp->query_vars;

		if ( ! empty( $this->perma_structure ) ) {
			$_valid = [];

			// Normalize request path: remove index.php/ and leading/trailing slashes.
			// urldecode is correct here: $wp->request arrives decoded by PHP/Apache, and the structure
			// regex patterns (e.g. "docs/%knowledge_base%") are plain ASCII, so matching works fine.
			// DB lookups handle the encoding separately below.
			$request = isset( $wp->request ) ? urldecode( $wp->request ) : '';
			$request = trim( preg_replace( '#^index\.php(/|$)#', '', $request ), '/' );

			// Strip pagination segment (/page/N) before matching permalink structures.
			// When hierarchy slugs are enabled, the (.+?) regex for %doc_category% would
			// otherwise capture "/page/2" as part of the category slug, breaking pagination.
			$paged = 0;
			if ( preg_match( '#/page/([0-9]+)/?$#', $request, $page_matches ) ) {
				$paged   = intval( $page_matches[1] );
				$request = preg_replace( '#/page/[0-9]+/?$#', '', $request );
			}

			// Strip optional language prefix injected by Polylang/WPML (e.g. "en/", "bn/", "pt-br/")
			// so that "bn/docs/..." matches the structure "docs/..." correctly.
			$request_without_lang = preg_replace( '#^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8})?/#', '', $request );

			// When WPML translates a taxonomy base slug per language (e.g. doc_tag
			// "docs-tag" -> "docs-tag-bn"), the incoming URL uses the translated slug,
			// but perma_structure is built from the default-language slug. Map the
			// leading translated slug back to its default so the raw structures match.
			// No-op on non-WPML sites and when the slug isn't translated.
			$request_canonical = $this->canonicalize_translated_slug( $request_without_lang );

			foreach ( $this->perma_structure as $_type => $structure ) {
				// First try the raw (possibly language-prefixed) request, then the lang-stripped variant.
				// This ensures we still match non-multilingual sites without stripping valid slugs.
				$_perma_vars = $this->is_perma_valid_for( $structure, $request );
				if ( ! $_perma_vars && $request_without_lang !== $request ) {
					$_perma_vars = $this->is_perma_valid_for( $structure, $request_without_lang );
				}
				if ( ! $_perma_vars && $request_canonical !== $request_without_lang ) {
					$_perma_vars = $this->is_perma_valid_for( $structure, $request_canonical );
				}

                // $_valid = empty( $_valid ) && $_perma_vars ? [ 'type' => $_type, 'query_vars' => $_perma_vars ] : $_valid;
                if ( ( $_perma_vars && method_exists( $this, $_type ) && call_user_func_array( [$this, $_type], [ & $_perma_vars] ) ) ) {

                    // dump( $_type, $_perma_vars );
                    if ( $_type === 'is_single_docs' || $_type == 'is_docs_feed' || $_type == 'is_docs_author' ) {
                        $_perma_vars['post_type'] = 'docs';
                    }
                    $_valid = ['type' => $_type, 'query_vars' => $_perma_vars];

					// Single doc match is definitive — stop here so later category/KB archive
					// structures cannot overwrite it (e.g. is_knowledge_base_category).
					if ( $_type === 'is_single_docs' ) {
						break;
					}
                }
            }

			$type       = isset( $_valid['type'] ) ? $_valid['type'] : '';
			$query_vars = isset( $_valid['query_vars'] ) ? $_valid['query_vars'] : [];

			// Inject the paged query var if a /page/N segment was stripped from the request.
			if ( $paged > 0 && ! empty( $type ) ) {
				$query_vars['paged'] = $paged;
			}

			if ( ! empty( $type ) ) {
				unset( $this->query_vars[ $type ] );
				array_map(
					function ( $_vars ) use ( &$wp ) {
						array_map(
							function ( $_var ) use ( &$wp ) {
								unset( $wp->query_vars[ $_var ] );
							},
							$_vars
						);
					},
					$this->query_vars
				);
			}

            $wp->query_vars = is_array( $query_vars ) ? array_merge( $wp->query_vars, $query_vars ) : $wp->query_vars;

            // Fallback
            if ( ! empty( $_valid ) ) {
                unset( $wp->query_vars['attachment'] );
            }
        }
    }

	/**
	 * Map a leading WPML-translated taxonomy base slug back to its default-language
	 * value so the default-language perma_structure patterns can match a translated URL.
	 *
	 * Only the first path segment is considered (the taxonomy base). Returns the
	 * request unchanged when WPML is inactive or the leading segment isn't a
	 * translated BetterDocs slug.
	 *
	 * @param string $request Language-stripped request path (no leading/trailing slash).
	 * @return string
	 */
	private function canonicalize_translated_slug( $request ) {
		if ( $request === '' || strpos( $request, '/' ) === false ) {
			return $request;
		}

		$slug_map = [
			'doc_tag'      => trim( $this->settings->get( 'tag_slug', 'docs-tag' ), '/' ),
			'doc_category' => trim( $this->settings->get( 'category_slug', 'docs-category' ), '/' ),
		];

		$segments = explode( '/', $request );

		foreach ( $slug_map as $taxonomy => $default ) {
			if ( $default === '' ) {
				continue;
			}

			$translated = Helper::wpml_translated_tax_slug( $taxonomy, $default );
			if ( $translated !== $default && $segments[0] === $translated ) {
				$segments[0] = $default;
				return implode( '/', $segments );
			}
		}

		return $request;
	}

	/**
	 * This method is responsible for checking a structure is valid again a request.
	 *
	 * @param string $structure
	 * @param string $request
	 * @return array|bool
	 */
	private function is_perma_valid_for( $structure, $request ) {
		if ( empty( $structure ) ) {
			return false;
		}

		$_tags                 = explode( '/', trim( $structure, '/' ) );
		$_replace_matched_tags = [];

		$_replace_tags = array_filter(
			$_tags,
			function ( $item ) use ( &$_replace_matched_tags ) {
				$_is_valid = strpos( $item, '%' ) !== false;
				if ( $_is_valid ) {
					$_replace_matched_tags[] = trim( $item, '%' );
				}
				return $_is_valid;
			}
		);

		// First, preg_quote the structure to safely use it in a regex
		$_perma_structure = preg_quote( $structure, '#' );
		
		// Since our placeholders like %name% contain characters (like %) that preg_quote escapes,
		// we must also preg_quote the tags before searching for them in the escaped structure.
		foreach ( $_replace_tags as $tag ) {
			$tag_escaped = preg_quote( $tag, '#' );
			$replacement = '([^/]+)';
			
			// If hierarchical slugs are enabled, allow slashes in the %doc_category% placeholder
			if ( $tag === '%doc_category%' && $this->settings->get( 'enable_category_hierarchy_slugs' ) ) {
				$replacement = '(.+?)';
			}
			
			$_perma_structure = str_replace( $tag_escaped, $replacement, $_perma_structure );
		}

		preg_match( "#^$_perma_structure$#", $request, $matches );

		if ( empty( $matches ) || ! is_array( $matches ) ) {
			return false;
		}

		if ( count( $matches ) === 1 ) {
			return [ 'post_type' => 'docs' ];
		}

		unset( $matches[0] );

		return array_combine( $_replace_matched_tags, $matches );
	}
}
