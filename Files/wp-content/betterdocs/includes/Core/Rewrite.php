<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Post;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;

class Rewrite extends Base {
	protected $settings;
	protected $database;

	public function __construct( Settings $settings, Database $database ) {
		$this->settings = $settings;
		$this->database = $database;
	}

	public function init() {
		add_action( 'init', [ $this, 'rules' ] );
		add_action( 'betterdocs::settings::saved', [ $this, 'save_permalink_structure' ], 2, 3 );
		add_action( 'template_redirect', [ $this, 'handle_pagination_redirect' ] );
		add_filter( 'term_link', [ $this, 'taxonomy_term_link' ], 10, 3 );
	}

	/**
	 * Swap a BetterDocs taxonomy base slug for its WPML-translated value in term links.
	 *
	 * doc_tag / doc_category are registered with the default-language slug, so
	 * WordPress' core get_term_link() always builds `/docs-tag/{slug}/` etc. When
	 * WPML translates the "URL <taxonomy> tax slug" string, rewrite the base segment
	 * to the active language's slug so term links match the translated archive URL.
	 * No-op for other taxonomies, when the slug isn't translated, or when the base
	 * segment isn't present (e.g. MKB category permalinks that omit it).
	 *
	 * @param string   $termlink
	 * @param \WP_Term $term
	 * @param string   $taxonomy
	 * @return string
	 */
	public function taxonomy_term_link( $termlink, $term, $taxonomy ) {
		if ( ! is_string( $termlink ) ) {
			return $termlink;
		}

		$slug_map = [
			'doc_tag'      => trim( $this->settings->get( 'tag_slug', 'docs-tag' ), '/' ),
			'doc_category' => trim( $this->settings->get( 'category_slug', 'docs-category' ), '/' ),
		];

		if ( ! isset( $slug_map[ $taxonomy ] ) || $slug_map[ $taxonomy ] === '' ) {
			return $termlink;
		}

		$default    = $slug_map[ $taxonomy ];
		$translated = \WPDeveloper\BetterDocs\Utils\Helper::wpml_translated_tax_slug( $taxonomy, $default );

		if ( $translated === $default ) {
			return $termlink;
		}

		return preg_replace( '#/' . preg_quote( $default, '#' ) . '/#', '/' . $translated . '/', $termlink, 1 );
	}

	/**
	 * Handle pagination redirect
	 * Redirects pagination attempts to main archive
	 */
	public function handle_pagination_redirect() {
		if ( ! is_post_type_archive( 'docs' ) ) {
			return;
		}

		$paged = get_query_var( 'paged' );
		if ( $paged > 1 ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	public function remove_knowledge_base_placeholder( $permalink ) {
		$permalink_array = $this->permalink_structure( $permalink, 'arraywithpercent' );
		$permalink_array = array_filter(
			$permalink_array,
			function ( $item ) {
				return $item !== '%knowledge_base%';
			}
		);

		return trailingslashit( implode( '/', $permalink_array ) );
	}

	/**
	 * This method is hooked with an action called 'betterdocs::settings::saved'
	 *
	 * @since 2.5.0
	 *
	 * @param bool $_saved
	 * @param array $_settings
	 * @param array $_old_settings
	 *
	 * @return void
	 */
	public function save_permalink_structure( $_saved, $_settings, $_old_settings ) {
		if ( ! isset( $_settings['permalink_structure'] ) ) {
			return;
		}

		$_permalink_structure = $_settings['permalink_structure'];

		/**
		 * TODO: Let's re-check later to optimize it.
		 */
		if ( ! betterdocs()->is_pro_active() ) {
			$_permalink_structure = $this->remove_knowledge_base_placeholder( $_permalink_structure );
		}

		if ( $_permalink_structure !== $_old_settings['permalink_structure'] ) {
			$this->settings->save( 'permalink_structure', $this->permalink_structure( $_permalink_structure ) );
		}

		$old_docs_page     = isset( $_old_settings['docs_page'] ) ? $_old_settings['docs_page'] : '';
		$current_docs_page = isset( $_settings['docs_page'] ) ? $_settings['docs_page'] : '';

		/**
		 * This block of code decides whether it needs to be flushed or not.
		 * Flush happens after register the post type.
		 */
		switch ( true ) {
			case $_permalink_structure !== $_old_settings['permalink_structure']:
			case $_settings['docs_slug'] !== $_old_settings['docs_slug']:
			case $_settings['builtin_doc_page'] !== $_old_settings['builtin_doc_page']:
			case $current_docs_page !== $old_docs_page:
			case $_settings['tag_slug'] !== $_old_settings['tag_slug']:
			case $_settings['category_slug'] !== $_old_settings['category_slug']:
				$this->database->set_transient( 'betterdocs_flush_rewrite_rules', true );
				break;
		}
	}

	public function permalink_structure( $structure = '', $output = 'string' ) {
		if ( empty( $structure ) ) {
			$structure = $this->settings->get( 'permalink_structure', 'docs' );
		}

		$docs_slug = $this->get_base_slug();

		$_structure_array = explode( '%', $structure );
		if ( $_structure_array[0] == '/' ) {
			$structure = $docs_slug . $structure;
		} elseif ( $_structure_array[0] == '' ) {
			$structure = $docs_slug . '/' . $structure;
		}

		$structure = trim( $structure, '/' );

		if ( $output === 'string' ) {
			return $structure;
		}

		if ( $output === 'array' ) {
			$structure = explode( '/', $structure );
			$structure = array_map(
				function ( $item ) {
					return trim( $item, '/%' );
				},
				$structure
			);
		}

		if ( $output === 'arraywithpercent' ) {
			$structure = explode( '/', $structure );
			$structure = array_map(
				function ( $item ) {
					return trim( $item, '/' );
				},
				$structure
			);
		}

		return $structure;
	}

	public function hierarchy_based_slug_switch() {
		$hierarchy_slug = $this->settings->get( 'enable_category_hierarchy_slugs', false );
		return $hierarchy_slug;
	}

	public function get_base_slug() {
		$docs_slug        = $this->settings->get( 'docs_slug', 'docs' );
		$docs_page        = $this->settings->get( 'docs_page', 0 );
		$builtin_doc_page = $this->settings->get( 'builtin_doc_page', true );

		if ( ! $builtin_doc_page && $docs_page !== 0 ) {
			$post_info = get_post( $docs_page );
			$docs_slug = $post_info instanceof WP_Post ? $post_info->post_name : $docs_slug;
		}

		$docs_slug = trim( $docs_slug, '/' );

		return $docs_slug;
	}

	public function normalzie_doc_perma_structure( $structure ) {
		$structure = $this->permalink_structure( $structure, 'arraywithpercent' );
		$structure = array_reduce(
			$structure,
			function ( $carry, $item ) {
				if ( strpos( $item, '%' ) !== false ) {
					$carry[] = $item;
				} elseif ( $carry && strpos( end( $carry ), '%' ) === false ) {
						$carry[ count( $carry ) - 1 ] .= "/$item";
				} else {
					$carry[] = $item;
				}

				return $carry;
			},
			[]
		);

		$structure[] = '%docs%';

		$group = array_filter(
			$structure,
			function ( $item ) {
				return strpos( $item, '%' ) === 0;
			}
		);

		return [
			'raw'   => $structure,
			'group' => $group
		];
	}

	public function make_regex( $segments ) {
		return array_reduce(
			$segments,
			function ( $carry, $item ) {
				$carry .= ( strpos( $item, '%' ) !== false ? '([^/]+)' : $item ) . '/';
				return $carry;
			},
			'^'
		) . '?$';
	}

	public function make_query( $segments ) {
		$query   = [];
		$matchId = 1;

		foreach ( $segments as $segment ) {
			$query[] = trim( $segment, '%' ) . '=$matches[' . $matchId . ']';
			++$matchId;
		}

		return 'index.php?' . implode( '&', $query ) . '&post_type=docs';
	}

	public function rules() {
		/**
		 * docs can be dynamic / as its a root slug
		 *
		 * https://url/docs/kb-slug
		 * https://url/docs/kb-slug/category-slug
		 * https://url/docs-category/category-slug
		 *
		 * Docs Visit:
		 * docs can be dynamic / as its a root slug
		 * or docs can be change by settings.
		 *
		 * https://url/docs/(docs-slug)
		 * https://url/docs/(cat-slug)/(docs-slug)
		 * https://url/docs/(kb-slug)/(docs-slug)
		 * https://url/docs/(kb-slug)/(cat-slug)/(docs-slug)
		 * https://url/docs/(cat-slug)/(kb-slug)/(docs-slug)
		 */

		$base 			= $this->get_base_slug();
		$hierarchy_slug = $this->hierarchy_based_slug_switch();

		// Add rule to handle pagination attempts
		$this->add_rewrite_rule(
			'^' . $base . '/page/([0-9]+)/?$',
			'index.php?post_type=docs',
			'top'
		);

        $base = $this->get_base_slug();
        $this->add_rewrite_rule( '^' . $base . '/(feed|rdf|rss|rss2|atom)/?$', 'index.php?post_type=docs&feed=$matches[1]' );
        $this->add_rewrite_rule( '^' . $base . '/authors/([0-9]+)/?$', 'index.php?post_type=docs&author=$matches[1]' );
		$this->add_rewrite_rule( '^' . $base . '/authors/([0-9]+)/page/([0-9]+)/?$', 'index.php?post_type=docs&author=$matches[1]&page=$matches[2]' );

		/**
		 * This code of blocks used to determine single docs permalink.
		 */
		$_docs_perma_struct    = betterdocs()->settings->get( 'permalink_structure', 'docs' );
		$_normalized_structure = $this->normalzie_doc_perma_structure( $_docs_perma_struct );

		if( $hierarchy_slug ) { // register hierarchy based slug rewrite rule, for single doc permalink
			// When MKB or WPML is used, the actual base for single docs might differ from the generic $base
			$perma_base = isset( $_normalized_structure['raw'][0] ) && strpos( $_normalized_structure['raw'][0], '%' ) === false 
				? $_normalized_structure['raw'][0] 
				: $base;
				
			$this->add_rewrite_rule( '^' . $perma_base . '/(.+?)/([^/]+)(?:/([0-9]+))?/?$', 'index.php?doc_category=$matches[1]&docs=$matches[2]&post_type=docs', 'top' );
			
			// Removed flush_rewrite_rules() from here. Calling flush_rewrite_rules() on the 'init' hook causes 
			// infinite database updates and destroys custom rules when WPML or MKB dynamically filters keys.
		}

		$_feed_regex   = $_normalized_structure['raw'];
		$_feed_regex[] = '(feed|rdf|rss|rss2|atom)';
		$_feed_group   = $_normalized_structure['group'];
		$_feed_group[] = '%feed%';

		$this->add_rewrite_rule( $this->make_regex( $_feed_regex ), $this->make_query( $_feed_group ) );

		$this->add_rewrite_rule(
			$this->make_regex( $_normalized_structure['raw'] ),
			$this->make_query( $_normalized_structure['group'] )
		);

		$this->add_rewrite_rule( '^' . $base . '/(feed|rdf|rss|rss2|atom)/?$', 'index.php?post_type=docs&feed=$matches[1]' );
	}

	public function add_rewrite_rule( $regex, $query, $after = 'top' ) {
		add_rewrite_rule( $regex, $query, $after );
	}

	public function docs_type_rewrite( $rewrite, $slug ) {
		$permalink           = betterdocs()->settings->get( 'permalink_structure', 'docs/' );
		$permalink_structure = $this->permalink_structure( $permalink, 'array' );
		$permalink           = apply_filters( 'betterdocs_docs_type_rewrite_permalink', $permalink, $slug, $permalink_structure );

		/**
		 * TODO: Let's re-check later for optimization.
		 */
		if ( ! betterdocs()->is_pro_active() ) {
			$permalink = $this->remove_knowledge_base_placeholder( $permalink );
		}

		return apply_filters(
			'betterdocs_docs_rewrite',
			[
				'slug'       => trim( $permalink, '/' ),
				'with_front' => false
			],
			$slug
		);
	}
}
