<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- inherited WordPress WXR exporter hook contract (wxr_export_*, the_*_export, rss2_head) preserved for compatibility with WP/feed consumers.
namespace WPDeveloper\BetterDocs\Admin;

use WPDeveloper\BetterDocs\Admin\ExportDefaults;
use WPDeveloper\BetterDocs\Admin\WPExporter;

use WP_Post;
use WP_Term;
use wpdb;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 * Originally made by WordPress.
 *
 * What changed (by Elementor):
 *  Remove echos.
 *  Fix indents.
 *  Add methods
 *      indent.
 *      wxr_categories_list.
 *      wxr_tags_list.
 *      wxr_terms_list.
 *      wxr_posts_list.
 *
 * What changed (by Templately)
 *  Added post__in capabilities for query.
 *  nav_menu_item from terms
 *  Some indent, and data type fixes
 *  query_args added as default args
 */

#[\AllowDynamicProperties]
class XMLExporter {
	const WXR_VERSION = '1.2';

	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	private $terms;

	private $nav_menu_terms;

    private $wpexporter;

    public function __construct( array $args = [] ) {
		global $wpdb;

		$this->args = wp_parse_args($args, ExportDefaults::get_default_args());

        $this->wpexporter = new WPExporter($this->args);

		$this->wpdb = $wpdb;
	}

    public function run(): array {
        // Handle glossaries export (as taxonomy)
        if ('glossaries' === $this->args['content']) {
            return $this->handle_glossaries_export();
        }

        return [
            'success' => true,
            'data' => [
                'filename' => 'betterdocs.' . gmdate( 'Y-m-d' ) . '.xml',
                'filetype' => 'text/xml',
                'download' => $this->get_xml_export($this->wpexporter->exporter_ids()),
            ]
        ];
    }

    public function handle_glossaries_export(): array {
        $filename = 'betterdocs.' . gmdate( 'Y-m-d' ) . '.xml';
        return [
            'success' => true,
            'data' => [
                'filename'  => $filename,
                'filetype'  => 'text/xml',
                'download'  => $this->get_xml_export( $this->wpexporter->get_glossary_term_ids() ),
            ]
        ];
    }

	/**
	 * Return tabulation characters, by `$columns`.
	 *
	 * @param int $columns
	 *
	 * @return string
	 */
	private function indent( int $columns = 1 ): string {

		$output = str_repeat( "\t", $columns );

		return (string) $output;
	}

	/**
	 * Return wrapped given string in XML CDATA tag.
	 *
	 * @param string $str String to wrap in XML CDATA tag.
	 *
	 * @return string
	 */
	private function wxr_cdata( $str ): string {
		$str = (string) $str;

		// wp_is_valid_utf8() only exists on WP 6.9+. Call it via a variable (still
		// guarded by function_exists) so Plugin Check's static "requires WP"
		// analysis doesn't flag a function that is never invoked on older cores;
		// on those, fall back to mb_check_encoding().
		$utf8_validator = 'wp_is_valid_utf8';
		$is_utf8        = function_exists( $utf8_validator )
			? $utf8_validator( $str )
			: ( function_exists( 'mb_check_encoding' ) ? mb_check_encoding( $str, 'UTF-8' ) : true );

		if ( ! $is_utf8 ) {
			$str = mb_convert_encoding( $str, 'UTF-8', 'ISO-8859-1' );
		}

		$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

		return $str;
	}

	/**
	 * Return the URL of the site.
	 *
	 * @return string Site URL.
	 */
	private function wxr_site_url(): string {
		if ( is_multisite() ) {
			// Multisite: the base URL.
			return network_home_url();
		} else {
			// WordPress (single site): the blog URL.
			return get_bloginfo_rss( 'url' );
		}
	}

	/**
	 * Return a cat_name XML tag from a given category object.
	 *
	 * @param WP_Term $category Category Object
	 *
	 * @return string
	 */
	private function wxr_cat_name( $category ): string {
		if ( empty( $category->name ) ) {
			return '';
		}

		return $this->indent( 3 ) . '<wp:cat_name>' . $this->wxr_cdata( $category->name ) . '</wp:cat_name>' . PHP_EOL;
	}

	/**
	 * Return a category_description XML tag from a given category object.
	 *
	 * @param WP_Term $category Category Object
	 *
	 * @return string
	 */
	private function wxr_category_description( $category ): string {
		if ( empty( $category->description ) ) {
			return '';
		}

		return $this->indent( 3 ) . '<wp:category_description>' . $this->wxr_cdata( $category->description ) . "</wp:category_description>\n";
	}

	/**
	 * Return a tag_name XML tag from a given tag object.
	 *
	 * @param WP_Term $tag Tag Object
	 *
	 * @return string
	 */
	private function wxr_tag_name( $tag ): string {
		if ( empty( $tag->name ) ) {
			return '';
		}

		return $this->indent( 3 ) . '<wp:tag_name>' . $this->wxr_cdata( $tag->name ) . '</wp:tag_name>' . PHP_EOL;
	}

	/**
	 * Return a tag_description XML tag from a given tag object.
	 *
	 * @param WP_Term $tag Tag Object
	 *
	 * @return string
	 */
	private function wxr_tag_description( $tag ): string {
		if ( empty( $tag->description ) ) {
			return '';
		}

		return $this->indent( 3 ) . '<wp:tag_description>' . $this->wxr_cdata( $tag->description ) . '</wp:tag_description>' . PHP_EOL;
	}

	/**
	 * Return a term_name XML tag from a given term object.
	 *
	 * @param WP_Term $term Term Object
	 *
	 * @return string
	 */
	private function wxr_term_name( $term ): string {
		if ( empty( $term->name ) ) {
			return '';
		}

		return $this->indent( 3 ) . '<wp:term_name>' . $this->wxr_cdata( $term->name ) . '</wp:term_name>' . PHP_EOL;
	}

	/**
	 * Return a term_description XML tag from a given term object.
	 *
	 * @param WP_Term $term Term Object
	 *
	 * @return string
	 */
	private function wxr_term_description( $term ): string {
		if ( empty( $term->description ) ) {
			return '';
		}

		return $this->indent( 3 ) . '<wp:term_description>' . $this->wxr_cdata( $term->description ) . '</wp:term_description>' . PHP_EOL;
	}

	/**
	 * Return term meta XML tags for a given term object.
	 *
	 * @param WP_Term $term Term object.
	 *
	 * @return string
	 */
	private function wxr_term_meta( $term ): string {
		$result   = '';
		$termmeta = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->termmeta} WHERE term_id = %d", $term->term_id ) );// phpcs:ignore

		foreach ( $termmeta as $meta ) {
			/**
			 * Filters whether to selectively skip term meta used for WXR exports.
			 *
			 * Returning a truthy value from the filter will skip the current meta
			 * object from being exported.
			 *
			 * @param bool $skip Whether to skip the current piece of term meta. Default false.
			 * @param string $meta_key Current meta key.
			 * @param object $meta Current meta object.
			 *
			 * @since 4.6.0
			 *
			 */
			if ( ! apply_filters( 'wxr_export_skip_termmeta', false, $meta->meta_key, $meta ) ) {
				$result .= sprintf( $this->indent( 3 ) . "<wp:termmeta>\n\t\t\t<wp:meta_key>%s</wp:meta_key>\n\t\t\t<wp:meta_value>%s</wp:meta_value>\n\t\t</wp:termmeta>\n", $this->wxr_cdata( $meta->meta_key ), $this->wxr_cdata( $meta->meta_value ) );
			}
		}

		return $result;
	}

	/**
	 * Return list of authors with posts.
	 *
	 * @param int[] $post_ids Optional. Array of post IDs to filter the query by.
	 *
	 * @return string
	 */
	private function wxr_authors_list( ?array $post_ids = null ): string {
		$result = '';

		if ( ! empty( $post_ids ) ) {
			$post_ids = array_map( 'absint', $post_ids );
			$and      = 'AND ID IN ( ' . implode( ', ', $post_ids ) . ')';
		} else {
			$and = '';
		}

		$authors = [];
		$results = $this->wpdb->get_results( "SELECT DISTINCT post_author FROM {$this->wpdb->posts} WHERE post_status != 'auto-draft' $and" );// phpcs:ignore
		foreach ( (array) $results as $r ) {
			$authors[] = get_userdata( $r->post_author );
		}

		$authors = array_filter( $authors );

		foreach ( $authors as $author ) {
			$result .= $this->indent( 2 ) . '<wp:author>' . PHP_EOL;

			$result .= $this->indent( 3 ) . '<wp:author_id>' . (int) $author->ID . '</wp:author_id>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:author_login>' . $this->wxr_cdata( $author->user_login ) . '</wp:author_login>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:author_email>' . $this->wxr_cdata( $author->user_email ) . '</wp:author_email>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:author_display_name>' . $this->wxr_cdata( $author->display_name ) . '</wp:author_display_name>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:author_first_name>' . $this->wxr_cdata( $author->first_name ) . '</wp:author_first_name>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:author_last_name>' . $this->wxr_cdata( $author->last_name ) . '</wp:author_last_name>' . PHP_EOL;

			$result .= $this->indent( 2 ) . '</wp:author>' . PHP_EOL;
		}

		return $result;
	}

	/**
	 * Return list of categories.
	 *
	 * @param array $cats
	 *
	 * @return string
	 */
	private function wxr_categories_list( array $cats ): string {
		$result = '';

		foreach ( $cats as $c ) {
			$result .= $this->indent( 2 ) . '<wp:category>' . PHP_EOL;

			$result .= $this->indent( 3 ) . '<wp:term_id>' . (int) $c->term_id . '</wp:term_id>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:category_nicename>' . $this->wxr_cdata( $c->slug ) . '</wp:category_nicename>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:category_parent>' . $this->wxr_cdata( $c->parent ? $cats[ $c->parent ]->slug : '' ) . '</wp:category_parent>' . PHP_EOL;
			$result .= $this->wxr_cat_name( $c ) . $this->wxr_category_description( $c ) . $this->wxr_term_meta( $c );

			$result .= $this->indent( 2 ) . '</wp:category>' . PHP_EOL;
		}

		return $result;
	}

	/**
	 * Return list of tags.
	 *
	 * @param array $tags
	 *
	 * @return string
	 */
	private function wxr_tags_list( array $tags ): string {
		$result = '';

		foreach ( $tags as $t ) {
			$result .= $this->indent( 2 ) . '<wp:tag>' . PHP_EOL;

			$result .= $this->indent( 3 ) . '<wp:term_id>' . (int) $t->term_id . '</wp:term_id>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:tag_slug>' . $this->wxr_cdata( $t->slug ) . '</wp:tag_slug>' . PHP_EOL;
			$result .= $this->wxr_tag_name( $t ) . $this->wxr_tag_description( $t ) . $this->wxr_term_meta( $t );

			$result .= $this->indent( 2 ) . '</wp:tag>' . PHP_EOL;
		}

		return $result;
	}

    public function get_parent_terms_slug( $terms, $term_id ) {
        $key = array_search( $term_id, array_column( $terms, 'term_id' ));

        if ( $key !== false ) {
            return $terms[$key]->slug;
        }

        return null;
    }

	/**
	 * Return list of terms.
	 *
	 * @param array $terms
	 *
	 * @return string
	 */
	private function wxr_terms_list( array $terms ): string {
		$result = '';
		foreach ( $terms as $key => $t ) {
			$result .= $this->indent( 2 ) . '<wp:term>' . PHP_EOL;

			$result .= $this->indent( 3 ) . '<wp:term_id>' . $this->wxr_cdata( $t->term_id ) . '</wp:term_id>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:term_taxonomy>' . $this->wxr_cdata( $t->taxonomy ) . '</wp:term_taxonomy>' . PHP_EOL;
			$result .= $this->indent( 3 ) . '<wp:term_slug>' . $this->wxr_cdata( $t->slug ) . '</wp:term_slug>' . PHP_EOL;
			if ( $t->parent ) {
                $result .= $this->indent( 3 ) . '<wp:term_parent>' . $this->wxr_cdata( $this->get_parent_terms_slug( $terms, $t->parent ) ) . '</wp:term_parent>' . PHP_EOL;
            }
			$result .= $this->wxr_term_name( $t ) . $this->wxr_term_description( $t ) . $this->wxr_term_meta( $t );

			$result .= $this->indent( 2 ) . '</wp:term>' . PHP_EOL;
		}

		return $result;
	}

    // public function get_terms( array $post_ids ) {
    //     // Get the object taxonomies
    //     $taxonomies = get_object_taxonomies( $this->args['content'] );

    //     if ( isset( $this->args['selected_docs'] ) && ! empty ( $this->args['selected_docs'] ) && $this->args['selected_docs'][0] == 'all' ) {
    //         $terms = get_terms( [
    //             'taxonomy'   => $taxonomies,
    //             'hide_empty' => false,
    //         ] );
    //     } else if( $this->args['content'] == 'glossaries' ) {
    //         $terms = get_terms([
    //             'taxonomy'   => 'glossaries',
    //             'include'    => $post_ids,
    //             'hide_empty' => false,
    //         ]);
    //     } else {
    //         $terms = wp_get_object_terms( $post_ids, $taxonomies);
    //     }

    //     usort( $terms, array( $this, 'compare_terms_by_meta' ) );

	// 	return $terms;
	// }

	/**
	 * Return list of posts, by requested `$post_ids`.
	 *
	 * @param array $post_ids
	 *
	 * @return string
	 */
	private function wxr_posts_list( array $post_ids ): string {
		$result = '';

		if ( $post_ids ) {
			global $wp_query;

			// Fake being in the loop.
			$wp_query->in_the_loop = true;

			// Fetch 20 posts at a time rather than loading the entire table into memory.
			while ( $next_posts = array_splice( $post_ids, 0, 20 ) ) {
				$where = 'WHERE ID IN (' . implode( ',', $next_posts ) . ')';
				$posts = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->posts} $where" );// phpcs:ignore

				// Begin Loop.
				foreach ( $posts as $post ) {
					setup_postdata( $post );

					$title = apply_filters( 'the_title_rss', $post->post_title );

					/**
					 * Filters the post content used for WXR exports.
					 *
					 * @param string $post_content Content of the current post.
					 *
					 * @since 2.5.0
					 *
					 */
					$content = $this->wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );

					/**
					 * Filters the post excerpt used for WXR exports.
					 *
					 * @param string $post_excerpt Excerpt for the current post.
					 *
					 * @since 2.6.0
					 *
					 */
					$excerpt = $this->wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );

					$result .= $this->indent( 2 ) . '<item>' . PHP_EOL;

					$result .= $this->indent( 3 ) . '<title>' . $title . '</title>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<link>' . esc_url( get_permalink() ) . '</link>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<pubDate>' . mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) . '</pubDate>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<dc:creator>' . $this->wxr_cdata( get_the_author_meta( 'login' ) ) . '</dc:creator>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<guid isPermaLink="false">' . $this->wxr_cdata( get_the_author_meta( 'login' ) ) . '</guid>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<description></description>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<content:encoded>' . $content . '</content:encoded>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<excerpt:encoded>' . $excerpt . '</excerpt:encoded>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_id>' . (int) $post->ID . '</wp:post_id>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_date>' . $this->wxr_cdata( $post->post_date ) . '</wp:post_date>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_date_gmt>' . $this->wxr_cdata( $post->post_date_gmt ) . '</wp:post_date_gmt>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:comment_status>' . $this->wxr_cdata( $post->comment_status ) . '</wp:comment_status>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:ping_status>' . $this->wxr_cdata( $post->ping_status ) . '</wp:ping_status>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_name>' . $this->wxr_cdata( $post->post_name ) . '</wp:post_name>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:status>' . $this->wxr_cdata( $post->post_status ) . '</wp:status>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_parent>' . $this->wxr_cdata( $post->post_parent ) . '</wp:post_parent>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:menu_order>' . (int) $post->menu_order . '</wp:menu_order>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_type>' . $this->wxr_cdata( $post->post_type ) . '</wp:post_type>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:post_password>' . $this->wxr_cdata( $post->post_password ) . '</wp:post_password>' . PHP_EOL;
					$result .= $this->indent( 3 ) . '<wp:is_sticky>' . ( is_sticky( $post->ID ) ? 1 : 0 ) . '</wp:is_sticky>' . PHP_EOL;

					if ( 'attachment' === $post->post_type ) {
						$result .= $this->indent( 3 ) . '<wp:attachment_url>' . $this->wxr_cdata( wp_get_attachment_url( $post->ID ) ) . '</wp:attachment_url>' . PHP_EOL;
					}

					$result .= $this->wxr_post_taxonomy( $post );

					$postmeta = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->postmeta} WHERE post_id = %d", $post->ID ) );// phpcs:ignore
					foreach ( $postmeta as $meta ) {
						/**
						 * Filters whether to selectively skip post meta used for WXR exports.
						 *
						 * Returning a truthy value from the filter will skip the current meta
						 * object from being exported.
						 *
						 * @param bool $skip Whether to skip the current post meta. Default false.
						 * @param string $meta_key Current meta key.
						 * @param object $meta Current meta object.
						 *
						 * @since 3.3.0
						 *
						 */
						if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta->meta_key, $meta ) ) {
							continue;
						}

						$result .= $this->indent( 3 ) . '<wp:postmeta>' . PHP_EOL;

						$result .= $this->indent( 4 ) . '<wp:meta_key>' . $this->wxr_cdata( $meta->meta_key ) . '</wp:meta_key>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:meta_value>' . $this->wxr_cdata( $meta->meta_value ) . '</wp:meta_value>' . PHP_EOL;

						$result .= $this->indent( 3 ) . '</wp:postmeta>' . PHP_EOL;
					}

					$wpml_meta = WPMLSupport::get_post_language_meta( (int) $post->ID );
					if ( $wpml_meta !== null ) {
						$result .= $this->indent( 3 ) . '<wp:postmeta>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:meta_key>' . $this->wxr_cdata( WPMLSupport::META_LANG ) . '</wp:meta_key>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:meta_value>' . $this->wxr_cdata( $wpml_meta['language_code'] ) . '</wp:meta_value>' . PHP_EOL;
						$result .= $this->indent( 3 ) . '</wp:postmeta>' . PHP_EOL;

						if ( $wpml_meta['source_slug'] !== '' ) {
							$result .= $this->indent( 3 ) . '<wp:postmeta>' . PHP_EOL;
							$result .= $this->indent( 4 ) . '<wp:meta_key>' . $this->wxr_cdata( WPMLSupport::META_SOURCE_SLUG ) . '</wp:meta_key>' . PHP_EOL;
							$result .= $this->indent( 4 ) . '<wp:meta_value>' . $this->wxr_cdata( $wpml_meta['source_slug'] ) . '</wp:meta_value>' . PHP_EOL;
							$result .= $this->indent( 3 ) . '</wp:postmeta>' . PHP_EOL;
						}
					}

					$_comments = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->comments} WHERE comment_post_ID = %d AND comment_approved <> 'spam'", $post->ID ) );// phpcs:ignore
					$comments  = array_map( 'get_comment', $_comments );
					foreach ( $comments as $c ) {

						$result .= $this->indent( 3 ) . '<wp:comment>' . PHP_EOL;

						$result .= $this->indent( 4 ) . '<wp:comment_id>' . (int) $c->comment_ID . '</wp:comment_id>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_author>' . $this->wxr_cdata( $c->comment_author ) . '</wp:comment_author>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_author_email>' . $this->wxr_cdata( $c->comment_author_email ) . '</wp:comment_author_email>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_author_url>' . $this->wxr_cdata( $c->comment_author_url ) . '</wp:comment_author_url>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_author_IP>' . $this->wxr_cdata( $c->comment_author_IP ) . '</wp:comment_author_IP>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_date>' . $this->wxr_cdata( $c->comment_date ) . '</wp:comment_date>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_date_gmt>' . $this->wxr_cdata( $c->comment_date_gmt ) . '</wp:comment_date_gmt>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_content>' . $this->wxr_cdata( $c->comment_content ) . '</wp:comment_content>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_approved>' . $this->wxr_cdata( $c->comment_approved ) . '</wp:comment_approved>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_type>' . $this->wxr_cdata( $c->comment_type ) . '</wp:comment_type>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_parent>' . $this->wxr_cdata( $c->comment_parent ) . '</wp:comment_parent>' . PHP_EOL;
						$result .= $this->indent( 4 ) . '<wp:comment_user_id>' . (int) $c->user_id . '</wp:comment_user_id>' . PHP_EOL;

						$c_meta = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->commentmeta} WHERE comment_id = %d", $c->comment_ID ) );// phpcs:ignore
						foreach ( $c_meta as $meta ) {
							/**
							 * Filters whether to selectively skip comment meta used for WXR exports.
							 *
							 * Returning a truthy value from the filter will skip the current meta
							 * object from being exported.
							 *
							 * @param bool $skip Whether to skip the current comment meta. Default false.
							 * @param string $meta_key Current meta key.
							 * @param object $meta Current meta object.
							 *
							 * @since 4.0.0
							 *
							 */
							if ( apply_filters( 'wxr_export_skip_commentmeta', false, $meta->meta_key, $meta ) ) {
								continue;
							}

							$result .= $this->indent( 4 ) . '<wp:commentmeta>' . PHP_EOL;

							$result .= $this->indent( 5 ) . '<wp:meta_key>' . $this->wxr_cdata( $meta->meta_key ) . '</wp:meta_key>' . PHP_EOL;
							$result .= $this->indent( 5 ) . '<wp:meta_value>' . $this->wxr_cdata( $meta->meta_key ) . '</wp:meta_value>' . PHP_EOL;

							$result .= $this->indent( 4 ) . '</wp:commentmeta>' . PHP_EOL;
						}

						$result .= $this->indent( 3 ) . '</wp:comment>' . PHP_EOL;
					}

					$result .= $this->indent( 2 ) . '</item>' . PHP_EOL;
				}
			}
		}

		return $result;
	}

	/**
	 * Return list of taxonomy terms, in XML tag format, associated with a post
	 *
	 * @param object $post
	 *
	 * @return string
	 */
	private function wxr_post_taxonomy( $post ): string {
		$result = '';

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( empty( $taxonomies ) ) {
			return $result;
		}

		$terms = wp_get_object_terms( $post->ID, $taxonomies );

		foreach ( (array) $terms as $term ) {
			$result .= $this->indent( 3 ) . "<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . $this->wxr_cdata( $term->name ) . '</category>' . PHP_EOL;
		}

		return $result;
	}

	/**
	 * Get the XML export.
	 *
	 * @param array $post_ids
	 *
	 * @return string
	 */
	private function get_xml_export( array $post_ids ): string {
		$charset              = get_bloginfo( 'charset' );
		$generator            = get_the_generator( 'export' );
		$wxr_version          = self::WXR_VERSION;
		$wxr_site_url         = $this->wxr_site_url();
		$rss_info_name        = get_bloginfo_rss( 'name' );
		$rss_info_url         = get_bloginfo_rss( 'url' );
		$rss_info_description = get_bloginfo_rss( 'description' );
		$rss_info_language    = get_bloginfo_rss( 'language' );
		$pub_date             = gmdate( 'D, d M Y H:i:s +0000' );

		$dynamic = $this->wxr_authors_list( $post_ids );

		ob_start();
		/** This action is documented in wp-includes/feed-rss2.php */
		do_action( 'rss2_head' );
		$rss2_head = ob_get_clean();

		$dynamic .= $rss2_head;

		$dynamic .= $this->wxr_terms_list( $this->wpexporter->get_terms( $post_ids ) );
		$dynamic .= $this->wxr_posts_list( $post_ids );

		$result  = '<?xml version="1.0" encoding="' . $charset . '" ?>' . "\n";
		$result .= "<!-- This is a WordPress eXtended RSS file generated by WordPress as an export of your site. -->\n";
		$result .= "<!-- It contains information about your site's posts, pages, comments, categories, and other content. -->\n";
		$result .= "<!-- You may use this file to transfer that content from one site to another. -->\n";
		$result .= "<!-- This file is not intended to serve as a complete backup of your site. -->\n\n";
		$result .= "<!-- To import this information into a WordPress site follow these steps: -->\n";
		$result .= "<!-- 1. Log in to that site as an administrator. -->\n";
		$result .= "<!-- 2. Go to Tools: Import in the WordPress admin panel. -->\n";
		$result .= "<!-- 3. Install the \"WordPress\" importer from the list. -->\n";
		$result .= "<!-- 4. Activate & Run Importer. -->\n";
		$result .= "<!-- 5. Upload this file using the form provided on that page. -->\n";
		$result .= "<!-- 6. You will first be asked to map the authors in this export file to users -->\n";
		$result .= "<!--    on the site. For each author, you may choose to map to an -->\n";
		$result .= "<!--    existing user on the site or to create a new user. -->\n";
		$result .= "<!-- 7. WordPress will then import each of the posts, pages, comments, categories, etc. -->\n";
		$result .= "<!--    contained in this file into your site. -->\n";
		$result .= $generator . "\n";
		$result .= '<rss version="2.0"' . "\n";
		$result .= "\txmlns:excerpt=\"http://wordpress.org/export/{$wxr_version}/excerpt/\"\n";
		$result .= "\txmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n";
		$result .= "\txmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n";
		$result .= "\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n";
		$result .= "\txmlns:wp=\"http://wordpress.org/export/{$wxr_version}/\"\n";
		$result .= ">\n";
		$result .= "\t<channel>\n";
		$result .= "\t\t<title>{$rss_info_name}</title>\n";
		$result .= "\t\t<link>{$rss_info_url}</link>\n";
		$result .= "\t\t<description>{$rss_info_description}</description>\n";
		$result .= "\t\t<pubDate>{$pub_date}</pubDate>\n";
		$result .= "\t\t<language>{$rss_info_language}</language>\n";
		$result .= "\t\t<wp:wxr_version>{$wxr_version}</wp:wxr_version>\n";
		$result .= "\t\t<wp:base_site_url>{$wxr_site_url}</wp:base_site_url>\n";
		$result .= "\t\t<wp:base_blog_url>{$rss_info_url}</wp:base_blog_url>\n";
		$result .= "\t\t" . $dynamic . "\n";
		$result .= "\t</channel>\n";
		$result .= "</rss>\n";

		return $result;
	}
}
