<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocs\Utils\Helper;

/**
 * Term translation linking for the React admin (WPML / Polylang).
 *
 * Powers the slide-over "Language / This is a translation of / Translate" panel
 * for the doc_category, doc_tag and knowledge_base taxonomies.
 */
class Translations extends BaseAPI {

	/** Taxonomies the React admins manage. */
	private function allowed_taxonomies() {
		return array_filter(
			[ 'doc_category', 'doc_tag', 'knowledge_base' ],
			'taxonomy_exists'
		);
	}

	public function permission_check(): bool {
		// QA-013: these routes read/write term language meta, so require the
		// doc-term management cap (Editor+) — `edit_docs` is Author-level and
		// let any author rewrite another category's language assignment. Mirrors
		// the `manage_doc_terms` guard used by PostType term operations.
		return current_user_can( 'manage_doc_terms' );
	}

	public function register() {
		$this->get( 'term-translations', [ $this, 'get_term_translations' ] );
		$this->post( 'term-language', [ $this, 'set_term_language' ] );

		add_filter( 'rest_request_before_callbacks', [ $this, 'switch_language_for_term_update' ], 10, 3 );
	}

	/**
	 * WPML lets translated terms share one slug across languages, but it
	 * deliberately skips its language filtering whenever get_term_by() is in the
	 * call stack (WPML_Term_Clauses::filter). Core wp_update_term() uses exactly
	 * that lookup for its duplicate-slug check, so updating a term whose slug has
	 * an older other-language twin via the core REST controllers always fails
	 * with duplicate_term_slug — even when the payload never touches the slug
	 * (e.g. changing only the KB order from the React admin). While such a
	 * request is being dispatched, restrict slug lookups on the edited taxonomy
	 * to the edited term's own language: the term matches itself, and genuine
	 * same-language collisions are still rejected.
	 *
	 * The term's language is read from its term_taxonomy_id directly because
	 * get_term() under WPML adjusts the ID to the current display language's
	 * translation, which would report the wrong language here.
	 */
	public function switch_language_for_term_update( $response, $handler, $request ) {
		global $wpdb;

		if ( ! $request instanceof WP_REST_Request || ! Helper::is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			return $response;
		}

		if ( ! in_array( $request->get_method(), [ 'POST', 'PUT', 'PATCH' ], true ) ) {
			return $response;
		}

		if ( ! preg_match( '#^/wp/v2/(doc_category|doc_tag|knowledge_base)/(\d+)$#', $request->get_route(), $matches ) ) {
			return $response;
		}

		$taxonomy = $matches[1];
		$ttid     = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = %s",
				absint( $matches[2] ),
				$taxonomy
			)
		);
		if ( ! $ttid ) {
			return $response;
		}

		$term_lang = apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => (int) $ttid,
			'element_type' => $taxonomy,
		] );
		if ( ! $term_lang ) {
			return $response;
		}

		$clauses_filter = function ( $clauses, $taxonomies, $args ) use ( $taxonomy, $term_lang ) {
			global $wpdb;

			// Only slug lookups on the edited taxonomy (the duplicate-slug check).
			if ( empty( $args['slug'] ) || [ $taxonomy ] !== (array) $taxonomies ) {
				return $clauses;
			}

			$clauses['join']  .= " LEFT JOIN {$wpdb->prefix}icl_translations bd_icl_t ON bd_icl_t.element_id = tt.term_taxonomy_id AND bd_icl_t.element_type = CONCAT( 'tax_', tt.taxonomy )";
			$clauses['where'] .= $wpdb->prepare( ' AND ( bd_icl_t.language_code = %s OR bd_icl_t.language_code IS NULL )', $term_lang );

			return $clauses;
		};
		add_filter( 'terms_clauses', $clauses_filter, 10, 3 );

		// WPML's save hooks stamp edited terms with the *current* language, so a
		// request arriving in another language context would silently reassign
		// the term's language. Align the context with the edited term.
		$prev_lang = apply_filters( 'wpml_current_language', null );
		if ( $term_lang !== $prev_lang ) {
			do_action( 'wpml_switch_language', $term_lang );
		}

		// Scope the workaround to this callback only: undo both the clause
		// filter and the language switch once the targeted update has run, so
		// nothing leaks into batch subrequests or response-side hooks.
		$cleanup = function ( $after_response ) use ( &$cleanup, $clauses_filter, $term_lang, $prev_lang ) {
			remove_filter( 'terms_clauses', $clauses_filter, 10 );
			if ( $term_lang !== $prev_lang ) {
				do_action( 'wpml_switch_language', $prev_lang );
			}
			remove_filter( 'rest_request_after_callbacks', $cleanup, 10 );

			return $after_response;
		};
		add_filter( 'rest_request_after_callbacks', $cleanup, 10 );

		return $response;
	}

	/**
	 * Translation group + "this is a translation of" candidates for a term (or for
	 * a target language when creating a new translation).
	 */
	public function get_term_translations( WP_REST_Request $request ) {
		$taxonomy = sanitize_key( (string) $request->get_param( 'taxonomy' ) );
		if ( ! in_array( $taxonomy, $this->allowed_taxonomies(), true ) || ! Helper::is_multilingual_active() ) {
			return rest_ensure_response( [ 'enabled' => false ] );
		}

		$default_lang = Helper::get_default_language();
		$target_lang  = sanitize_text_field( (string) $request->get_param( 'lang' ) );
		$source_lang  = sanitize_text_field( (string) $request->get_param( 'source_lang' ) ) ?: $default_lang;
		$term_id      = absint( $request->get_param( 'term_id' ) );

		$current_lang   = '';
		$translation_of = 0;
		$translations   = [];

		if ( $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$current_lang = Helper::get_term_language( $term );
				$translations = Helper::get_term_translations( $term );
				if ( ! $target_lang ) {
					$target_lang = $current_lang;
				}
				// The source-language sibling (if this term is already a translation).
				if ( isset( $translations[ $source_lang ]['term_id'] ) ) {
					$translation_of = (int) $translations[ $source_lang ]['term_id'];
				}
			}
		}

		// Candidates only matter when assigning a non-default language.
		$candidates = ( $target_lang && $target_lang !== $default_lang )
			? Helper::get_translation_candidates( $taxonomy, $target_lang, $source_lang )
			: [];

		return rest_ensure_response( [
			'enabled'        => true,
			'default_lang'   => $default_lang,
			'current_lang'   => $current_lang,
			'translation_of' => $translation_of,
			'translations'   => $translations,
			'candidates'     => $candidates,
		] );
	}

	/**
	 * Set a term's language and link it into the chosen translation group.
	 */
	public function set_term_language( WP_REST_Request $request ) {
		$taxonomy = sanitize_key( (string) $request->get_param( 'taxonomy' ) );
		$term_id  = absint( $request->get_param( 'term_id' ) );
		$lang     = sanitize_text_field( (string) $request->get_param( 'lang' ) );
		$source   = absint( $request->get_param( 'translation_of' ) );

		if ( ! in_array( $taxonomy, $this->allowed_taxonomies(), true ) || ! $term_id || ! Helper::is_multilingual_active() ) {
			return rest_ensure_response( [ 'success' => false ] );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return rest_ensure_response( [ 'success' => false ] );
		}

		Helper::link_term_translation( $term, $lang, $source );

		return rest_ensure_response( [ 'success' => true, 'lang' => $lang ] );
	}
}
