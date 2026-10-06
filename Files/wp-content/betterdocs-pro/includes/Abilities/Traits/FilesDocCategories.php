<?php
/**
 * Filing doc categories into knowledge bases.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;

/**
 * The four knowledge-base tools all do the same two things: read the Pro state
 * before touching anything, and move a knowledge base's slug in and out of the
 * `doc_category_knowledge_base` term meta on doc categories.
 *
 * That meta is the whole of the relationship. A knowledge base has no term
 * hierarchy to its categories — Pro's own reader
 * (`MultipleKB::modify_doc_category_rest_query()`) answers "which categories are
 * in this knowledge base?" with a `LIKE` over that meta, and the permalink
 * builder reads the same key. So a knowledge base whose slug changes, or which
 * is deleted, has to have its slug rewritten out of every category that carries
 * it, or those categories keep pointing at a name nothing resolves.
 *
 * The meta stores **slugs**, never ids: Free's sanitiser (BetterDocs 4.9.0,
 * `PostType::sanitize_category_knowledge_bases()`) drops numeric entries exactly
 * so an id written here cannot masquerade as one.
 *
 * @since 4.3.0
 */
trait FilesDocCategories {

	/**
	 * The taxonomy these tools own.
	 *
	 * A method rather than a class constant: constants inside a trait are PHP
	 * 8.2 syntax and this plugin's floor is 7.4.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function kb_taxonomy() {
		return 'knowledge_base';
	}

	/**
	 * The doc-category term meta that records knowledge-base membership.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function kb_meta_key() {
		return 'doc_category_knowledge_base';
	}

	/**
	 * The refusal this site owes the caller before any work starts, or null when
	 * the feature is usable.
	 *
	 * With Pro loaded the only reachable refusal is the setting: Multiple
	 * Knowledge Base off, which unregisters the taxonomy. The `pro_required`
	 * branch is kept because `betterdocs()->is_pro_active()` asks WordPress
	 * whether the plugin is active rather than whether this file was loaded, and
	 * the two can disagree for a request. An unlicensed Pro is never a refusal
	 * (ADR-004).
	 *
	 * @since 4.3.0
	 *
	 * @return \WP_Error|null
	 */
	protected function kb_blocked() {
		$state = ProState::get( true );

		if ( 'pro_active_setting_off' === $state['state'] ) {
			return AbilityError::setting_disabled(
				'multiple_kb',
				[ 'multiple_kb' => true ],
				__( 'Multiple Knowledge Base', 'betterdocs-pro' ),
				$state['state']
			);
		}

		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, __( 'Knowledge bases', 'betterdocs-pro' ) );
		}

		return null;
	}

	/**
	 * The `{id, name, slug, description, url, categories}` object all four tools
	 * answer with.
	 *
	 * @since 4.3.0
	 *
	 * @param array $term       A `wp/v2/knowledge_base` item.
	 * @param array $categories Category summaries filed under it.
	 * @return array
	 */
	protected function kb_shape( array $term, array $categories ) {
		return [
			'id'          => isset( $term['id'] ) ? (int) $term['id'] : 0,
			'name'        => isset( $term['name'] ) ? (string) $term['name'] : '',
			'slug'        => isset( $term['slug'] ) ? (string) $term['slug'] : '',
			'description' => isset( $term['description'] ) ? (string) $term['description'] : '',
			'url'         => isset( $term['link'] ) ? (string) $term['link'] : '',
			'count'       => isset( $term['count'] ) ? (int) $term['count'] : 0,
			'categories'  => array_values( $categories )
		];
	}

	/**
	 * The doc categories currently carrying a knowledge base's slug.
	 *
	 * Answered through Pro's own `knowledge_base` filter on
	 * `wp/v2/doc_category`, so the definition of membership is the same one the
	 * front end and the admin screens use. Paged, because the answer decides
	 * which categories get rewritten when a knowledge base is renamed or
	 * deleted, and a truncated answer would leave the rest pointing at a dead
	 * slug.
	 *
	 * That filter is a `LIKE` over the serialised meta, so it also matches a
	 * category filed under a knowledge base whose slug merely *contains* this
	 * one (`kb` matching `u15-kb`). Each row is therefore re-checked against its
	 * own meta before it is reported. Writing is safe either way — every write
	 * goes through {@see self::rewrite_category_membership()}, which compares
	 * slugs exactly and writes nothing when the list is unchanged — but a list
	 * that over-reports membership would still be a wrong answer.
	 *
	 * @since 4.3.0
	 *
	 * @param string $slug Knowledge-base slug.
	 * @return array[]|\WP_Error List of `{id, name, slug}`.
	 */
	protected function assigned_categories( $slug ) {
		$slug = (string) $slug;

		if ( '' === $slug ) {
			return [];
		}

		$per_page = 100;
		$page     = 1;
		$pages    = 1;
		$items    = [];

		// 20 pages of 100 is two thousand doc categories in one knowledge base;
		// past that something is wrong with the site, not with the paging.
		while ( $page <= $pages && $page <= 20 ) {
			$response = $this->dispatch_doc_categories(
				[
					'context'        => 'view',
					'knowledge_base' => $slug,
					'hide_empty'     => false,
					'orderby'        => 'name',
					'order'          => 'asc',
					'page'           => $page,
					'per_page'       => $per_page
				]
			);

			if ( $response->is_error() ) {
				return $this->map_kb_error( $response->as_error(), 'doc_category', __( 'list the doc categories in a knowledge base', 'betterdocs-pro' ) );
			}

			foreach ( (array) $response->get_data() as $term ) {
				$term = (array) $term;

				if ( ! in_array( $slug, $this->membership_of( $term ), true ) ) {
					continue;
				}

				$items[] = [
					'id'   => isset( $term['id'] ) ? (int) $term['id'] : 0,
					'name' => isset( $term['name'] ) ? (string) $term['name'] : '',
					'slug' => isset( $term['slug'] ) ? (string) $term['slug'] : ''
				];
			}

			$headers = $response->get_headers();
			$pages   = isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1;
			++$page;
		}

		return $items;
	}

	/**
	 * Run a `wp/v2/doc_category` collection request with this tool's own
	 * parameters intact.
	 *
	 * `InstantAnswer::order_ia_doc_taxonomies()` hooks `rest_doc_category_query`
	 * at priority 10 and, whenever `$_GET` is empty, replaces `number`,
	 * `hide_empty`, `orderby`, `order` and `meta_key` with the Instant Answer
	 * widget's own settings. `$_GET` is empty for *every* in-process
	 * `rest_do_request()`, which is how an ability calls a route, so without
	 * this a knowledge base holding more than ten doc categories would silently
	 * report ten — and the seven-eleventh would keep a dead slug after a rename.
	 * Free's `bd-list-terms` hit the same wall (ADR-047) and restores the same
	 * way: a filter at a later priority, matched on request identity so nothing
	 * else in the page is affected.
	 *
	 * The `meta_query` Multiple Knowledge Base adds at priority 11 is left
	 * alone — that is the membership filter this method exists to use.
	 *
	 * @since 4.3.0
	 *
	 * @param array $params Query parameters.
	 * @return \WP_REST_Response
	 */
	protected function dispatch_doc_categories( array $params ) {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/doc_category' );

		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_query_params( $params );

		$restore = function ( $args, $filtered_request ) use ( $request, $params ) {
			if ( $filtered_request !== $request ) {
				return $args;
			}

			$args['number']     = (int) $params['per_page'];
			$args['offset']     = ( (int) $params['page'] - 1 ) * (int) $params['per_page'];
			$args['hide_empty'] = (bool) $params['hide_empty'];
			$args['orderby']    = (string) $params['orderby'];
			$args['order']      = strtoupper( (string) $params['order'] );

			unset( $args['meta_key'] );

			return $args;
		};

		add_filter( 'rest_doc_category_query', $restore, 20, 2 );

		$response = rest_do_request( $request );

		remove_filter( 'rest_doc_category_query', $restore, 20 );

		return $response;
	}

	/**
	 * Add a knowledge base's slug to one doc category's membership meta.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $category_id Doc category term id.
	 * @param string $slug        Knowledge-base slug.
	 * @return bool|\WP_Error True when the meta was written, false when it already said this.
	 */
	protected function file_category( $category_id, $slug ) {
		return $this->rewrite_category_membership( $category_id, $slug, '' );
	}

	/**
	 * Remove a knowledge base's slug from one doc category's membership meta.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $category_id Doc category term id.
	 * @param string $slug        Knowledge-base slug.
	 * @return bool|\WP_Error True when the meta was written, false when it already said this.
	 */
	protected function unfile_category( $category_id, $slug ) {
		return $this->rewrite_category_membership( $category_id, '', $slug );
	}

	/**
	 * Read one doc category's membership meta, swap one slug for another, and
	 * write it back — but only when the list actually changed.
	 *
	 * Read-modify-write rather than a blind overwrite, because a doc category can
	 * belong to several knowledge bases and this tool is only ever entitled to
	 * speak for its own.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $category_id Doc category term id.
	 * @param string $add         Slug to add, or '' for none.
	 * @param string $remove      Slug to remove, or '' for none.
	 * @return bool|\WP_Error True when the meta was written, false when nothing changed.
	 */
	protected function rewrite_category_membership( $category_id, $add, $remove ) {
		$category_id = (int) $category_id;
		$add         = (string) $add;
		$remove      = (string) $remove;

		$term = $this->dispatch( 'GET', '/doc_category/' . $category_id, [ 'context' => 'view' ], 'wp/v2' );

		if ( is_wp_error( $term ) ) {
			return $this->map_kb_error( $term, 'doc_category', __( 'read a doc category', 'betterdocs-pro' ) );
		}

		$current = $this->membership_of( (array) $term );
		$updated = $current;

		if ( '' !== $remove ) {
			$updated = array_values( array_diff( $updated, [ $remove ] ) );
		}

		if ( '' !== $add && ! in_array( $add, $updated, true ) ) {
			$updated[] = $add;
		}

		if ( $updated === $current ) {
			return false;
		}

		$written = $this->dispatch(
			'POST',
			'/doc_category/' . $category_id,
			[
				'meta' => [ $this->kb_meta_key() => $updated ]
			],
			'wp/v2'
		);

		if ( is_wp_error( $written ) ) {
			return $this->map_kb_error( $written, 'doc_category', __( 'file a doc category into a knowledge base', 'betterdocs-pro' ) );
		}

		return true;
	}

	/**
	 * The knowledge-base slugs a doc category item carries.
	 *
	 * @since 4.3.0
	 *
	 * @param array $term A `wp/v2/doc_category` item.
	 * @return string[]
	 */
	protected function membership_of( array $term ) {
		$key = $this->kb_meta_key();
		$raw = isset( $term['meta'][ $key ] ) ? $term['meta'][ $key ] : [];

		if ( ! is_array( $raw ) ) {
			$raw = '' === $raw || null === $raw ? [] : [ $raw ];
		}

		$slugs = [];

		foreach ( $raw as $slug ) {
			if ( is_scalar( $slug ) && '' !== (string) $slug ) {
				$slugs[] = (string) $slug;
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * The term id WordPress reports when a name is already taken, or 0 when the
	 * error is something else.
	 *
	 * `term_exists` is what makes these tools find-or-create: an agent that runs
	 * the same "make sure these knowledge bases exist" batch twice gets the same
	 * ids back rather than a refusal.
	 *
	 * @since 4.3.0
	 *
	 * @param \WP_Error $error What the controller returned.
	 * @return int
	 */
	protected function existing_term_id( \WP_Error $error ) {
		if ( 'term_exists' !== $error->get_error_code() ) {
			return 0;
		}

		$data = $error->get_error_data();

		if ( is_array( $data ) && ! empty( $data['term_id'] ) ) {
			return (int) $data['term_id'];
		}

		// `wp_insert_term()` puts the id in the data directly; the REST
		// controller adds the `term_id` key on top. Accept either.
		return is_scalar( $data ) ? (int) $data : 0;
	}

	/**
	 * Translate a `wp/v2/<taxonomy>` refusal into the typed vocabulary.
	 *
	 * @since 4.3.0
	 *
	 * @param \WP_Error $error    What the controller returned.
	 * @param string    $taxonomy Taxonomy in play.
	 * @param string    $what     What the caller was trying to do, as a phrase.
	 * @return \WP_Error
	 */
	protected function map_kb_error( \WP_Error $error, $taxonomy, $what ) {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();
		$data    = (array) $error->get_error_data();

		$taxonomy_object = get_taxonomy( $taxonomy );

		switch ( $code ) {
			case 'rest_term_invalid':
			case 'rest_term_invalid_id':
				return AbilityError::not_found( $this->term_object_name( $taxonomy ), isset( $data['id'] ) ? $data['id'] : 0 );

			case 'rest_cannot_create':
				return AbilityError::capability_missing( $this->taxonomy_capability( $taxonomy_object, 'edit_terms', 'edit_knowledge_base_terms' ), $what );

			case 'rest_cannot_update':
				// The membership meta carries its own `auth_callback`, so this one
				// code covers two different capabilities and the answer has to say
				// which of them the caller is actually missing.
				if ( isset( $data['key'] ) && $this->kb_meta_key() === $data['key'] ) {
					return AbilityError::capability_missing(
						'manage_knowledge_base_terms',
						__( 'file a doc category into a knowledge base', 'betterdocs-pro' )
					);
				}

				return AbilityError::capability_missing( $this->taxonomy_capability( $taxonomy_object, 'edit_terms', 'edit_knowledge_base_terms' ), $what );

			case 'rest_cannot_delete':
				return AbilityError::capability_missing( $this->taxonomy_capability( $taxonomy_object, 'delete_terms', 'delete_knowledge_base_terms' ), $what );

			case 'rest_forbidden':
			case 'rest_forbidden_context':
				return AbilityError::capability_missing( $this->taxonomy_capability( $taxonomy_object, 'manage_terms', 'manage_knowledge_base_terms' ), $what );

			case 'rest_invalid_param':
				$field = isset( $data['params'] ) && is_array( $data['params'] ) ? (string) key( $data['params'] ) : '';

				return AbilityError::invalid_input(
					'' !== $field ? $field : 'input',
					'' !== $field && isset( $data['params'][ $field ] ) ? (string) $data['params'][ $field ] : $message
				);

			default:
				return AbilityError::upstream( $message, [ 'code' => (string) $code ] );
		}
	}

	/**
	 * One capability off a taxonomy object, with a fallback for the case where
	 * the taxonomy is not registered any more.
	 *
	 * @since 4.3.0
	 *
	 * @param \WP_Taxonomy|null $taxonomy_object Taxonomy object, or null.
	 * @param string            $cap             Capability key on the object (`edit_terms`, …).
	 * @param string            $fallback        Capability to name when the object is gone.
	 * @return string
	 */
	protected function taxonomy_capability( $taxonomy_object, $cap, $fallback ) {
		if ( $taxonomy_object && isset( $taxonomy_object->cap->$cap ) ) {
			return (string) $taxonomy_object->cap->$cap;
		}

		return $fallback;
	}
}
