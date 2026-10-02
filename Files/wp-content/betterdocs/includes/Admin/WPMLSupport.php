<?php
namespace WPDeveloper\BetterDocs\Admin;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This class integrates with WPML's documented hook API (wpml_post_language_details,
// wpml_element_trid, wpml_get_element_translations, etc.). Those hook names are owned
// by WPML and must be used verbatim, so the plugin-prefix rule does not apply here.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

/**
 * Helpers for round-tripping WPML language data through BetterDocs
 * export / import. WPML stores per-post language data in `icl_translations`,
 * which the regular WXR / CSV export doesn't touch — so without this layer
 * imported translations come in as plain posts and lose their translation
 * group on the target site.
 *
 * Transport meta keys (synthetic — never persisted to the DB):
 * - META_LANG:        language code of this post (e.g. "en", "de").
 * - META_SOURCE_SLUG: slug of the source post when this row is a translation.
 *                    Empty when this row IS the source/original.
 */
class WPMLSupport {
	const META_LANG        = '_betterdocs_wpml_lang';
	const META_SOURCE_SLUG = '_betterdocs_wpml_source_slug';

	public static function is_active(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * Read WPML language details for one post. Returns null when WPML is off,
	 * the post type isn't translatable, or no language details exist.
	 *
	 * @return array{language_code:string, source_slug:string}|null
	 */
	public static function get_post_language_meta( int $post_id ): ?array {
		if ( ! self::is_active() || $post_id <= 0 ) {
			return null;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return null;
		}

		$details = apply_filters( 'wpml_post_language_details', null, $post_id );
		if ( ! is_array( $details ) && ! is_object( $details ) ) {
			return null;
		}
		$details = (array) $details;

		$language_code = isset( $details['language_code'] ) ? (string) $details['language_code'] : '';
		if ( $language_code === '' ) {
			return null;
		}

		// The `wpml_post_language_details` filter only exposes language_code /
		// locale / display_name / etc. — it has NO source_language_code key, so
		// we can't learn the source language from it. Resolve the whole
		// translation group instead: `wpml_get_element_translations` returns one
		// row per language, each carrying its own `source_language_code`
		// (null/empty for the original). Find this post's own row to read its
		// source language, then pull the slug of the row in that language.
		$source_slug  = '';
		$element_type = 'post_' . $post_type;
		$trid         = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );
		if ( $trid ) {
			$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
			if ( is_array( $translations ) ) {
				$source_lang = '';
				foreach ( $translations as $t ) {
					if ( isset( $t->element_id ) && (int) $t->element_id === $post_id ) {
						$source_lang = isset( $t->source_language_code ) ? (string) $t->source_language_code : '';
						break;
					}
				}

				if ( $source_lang !== '' ) {
					foreach ( $translations as $t ) {
						$t_lang = isset( $t->language_code ) ? (string) $t->language_code : '';
						$t_id   = isset( $t->element_id ) ? (int) $t->element_id : 0;
						if ( $t_lang === $source_lang && $t_id > 0 ) {
							$src = get_post( $t_id );
							if ( $src instanceof WP_Post ) {
								$source_slug = $src->post_name;
							}
							break;
						}
					}
				}
			}
		}

		return [
			'language_code' => $language_code,
			'source_slug'   => $source_slug,
		];
	}

	/**
	 * Assign WPML language to a freshly-imported post.
	 *
	 * When $source_slug is given, looks up the local source post by slug,
	 * fetches its trid, and registers this post as a translation in that
	 * group. When $source_slug is empty or the source isn't found locally,
	 * WPML creates a fresh trid (the post becomes a new source).
	 */
	public static function assign_post_language( int $post_id, string $language_code, string $source_slug = '' ): bool {
		if ( ! self::is_active() || $post_id <= 0 || $language_code === '' ) {
			return false;
		}

		global $sitepress;
		if ( ! is_object( $sitepress ) || ! method_exists( $sitepress, 'set_element_language_details' ) ) {
			return false;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return false;
		}
		$element_type = 'post_' . $post_type;

		$trid        = null;
		$source_lang = null;
		if ( $source_slug !== '' ) {
			$source_post = self::find_post_by_slug( $source_slug, $post_type );
			if ( $source_post instanceof WP_Post ) {
				$trid = apply_filters( 'wpml_element_trid', null, $source_post->ID, $element_type );
				if ( ! $trid ) {
					$trid = null;
				}

				$src_details = apply_filters( 'wpml_post_language_details', null, $source_post->ID );
				if ( is_array( $src_details ) || is_object( $src_details ) ) {
					$src_details = (array) $src_details;
					if ( ! empty( $src_details['language_code'] ) ) {
						$source_lang = (string) $src_details['language_code'];
					}
				}
			}
		}

		$sitepress->set_element_language_details( $post_id, $element_type, $trid, $language_code, $source_lang );
		return true;
	}

	/**
	 * Expand a post ID list to include all WPML translations of each post.
	 *
	 * Raw SQL queries (and `get_posts` calls without `suppress_filters`) only
	 * return the active-language row, so translations would otherwise be
	 * silently dropped from the export. No-op when WPML is inactive.
	 *
	 * @param int[] $post_ids
	 * @return int[]
	 */
	public static function expand_with_translations( array $post_ids ): array {
		if ( empty( $post_ids ) || ! self::is_active() ) {
			return $post_ids;
		}

		$expanded = $post_ids;
		foreach ( $post_ids as $post_id ) {
			$post_type = get_post_type( $post_id );
			if ( ! $post_type ) {
				continue;
			}
			$element_type = 'post_' . $post_type;
			$trid         = apply_filters( 'wpml_element_trid', null, (int) $post_id, $element_type );
			if ( ! $trid ) {
				continue;
			}
			$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
			if ( ! is_array( $translations ) ) {
				continue;
			}
			foreach ( $translations as $translation ) {
				if ( ! empty( $translation->element_id ) ) {
					$expanded[] = (int) $translation->element_id;
				}
			}
		}

		return array_values( array_unique( array_map( 'intval', $expanded ) ) );
	}

	private static function find_post_by_slug( string $slug, string $post_type ): ?WP_Post {
		$posts = get_posts( [
			'name'             => $slug,
			'post_type'        => $post_type,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- intentional: bypass WPML's own query filters to resolve the untranslated source post and avoid recursion in the WPML support layer.
			'suppress_filters' => true,
		] );
		return ! empty( $posts ) ? $posts[0] : null;
	}
}
