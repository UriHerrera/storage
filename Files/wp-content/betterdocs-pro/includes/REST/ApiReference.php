<?php

namespace WPDeveloper\BetterDocsPro\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Request;
use WP_REST_Server;
use WPDeveloper\BetterDocs\Core\BaseAPI;
use WPDeveloper\BetterDocsPro\Core\ApiReferences;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecParser;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecStore;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecSummary;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecValidator;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecIngestor;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\BrandPropagator;
use WPDeveloper\BetterDocsPro\Core\ApiDocs\Materializer;

/**
 * API Documentation — reference management + spec ingestion.
 *
 * Auto-discovered from includes/REST/. Admin routes are capability-gated
 * (`betterdocs_api_ref_capability`, default manage_options); the public
 * spec-serving route lives in Unit 5 and carries its own visibility check.
 */
class ApiReference extends BaseAPI {
	/**
	 * Accepted upload extensions.
	 *
	 * @var string[]
	 */
	const EXTENSIONS = [ 'json', 'yaml', 'yml' ];

	public function register() {
		$id_arg = [
			'id' => [
				'validate_callback' => function ( $param ) {
					return is_numeric( $param );
				},
				'sanitize_callback' => 'absint'
			]
		];

		$this->get( '/api-ref', [ $this, 'list_references' ] );
		$this->post( '/api-ref', [ $this, 'create_reference' ] );
		$this->get( '/api-ref/(?P<id>[\d]+)', [ $this, 'get_reference' ], $id_arg );
		$this->post( '/api-ref/(?P<id>[\d]+)', [ $this, 'update_reference' ], $id_arg );
		$this->register_endpoint( '/api-ref/(?P<id>[\d]+)', [ $this, 'delete_reference' ], $id_arg, WP_REST_Server::DELETABLE );
		$this->post( '/api-ref/(?P<id>[\d]+)/spec-file', [ $this, 'upload_spec' ], $id_arg );
		$this->get( '/api-ref/(?P<id>[\d]+)/spec', [ $this, 'serve_spec' ], $id_arg );
		$this->post( '/api-ref/(?P<id>[\d]+)/resume', [ $this, 'resume_materialize' ], $id_arg );
	}

	/**
	 * Management routes need the capability; the spec route is public — it is
	 * what the frontend renderer fetches — and enforces the reference's
	 * visibility itself in serve_spec().
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function permission_check( $request = null ) {
		if ( $request instanceof WP_REST_Request
			&& 'GET' === $request->get_method()
			&& preg_match( '#/api-ref/\d+/spec$#', $request->get_route() ) ) {
			return true;
		}

		return current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) );
	}

	/**
	 * GET /api-ref/{id}/spec — THE spec URL (PRD D8). Serves the active spec
	 * as JSON, visibility-checked, Free-capped, ETag'd.
	 */
	public function serve_spec( WP_REST_Request $request ) {
		$post    = $this->find_reference( $request['id'] );
		$manager = current_user_can( apply_filters( 'betterdocs_api_ref_capability', 'manage_options' ) );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// Drafts are only visible to managers (preview flow).
		if ( 'publish' !== $post->post_status && ! $manager ) {
			return $this->error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ), 404 );
		}

		/**
		 * Visibility gate. Free references are always public; Pro tightens
		 * this filter for logged_in / role-restricted references (Unit 11).
		 */
		$can_view = apply_filters( 'betterdocs_api_ref_can_view', true, $post, $request );

		if ( ! $can_view && ! $manager ) {
			return $this->error(
				'betterdocs_api_ref_restricted',
				__( 'You are not allowed to view this API reference.', 'betterdocs-pro' ),
				is_user_logged_in() ? 403 : 401
			);
		}

		$active = $this->container->get( SpecStore::class )->get_active( $post->ID );

		if ( ! $active ) {
			return $this->error( 'betterdocs_api_spec_none', __( 'This reference has no spec yet.', 'betterdocs-pro' ), 404 );
		}

		$is_public = 'public' === ( get_post_meta( $post->ID, '_bd_api_visibility', true ) ?: 'public' );
		$is_pro    = betterdocs()->is_pro_active();
		// A public spec is still owner-editable, so a long shared-cache lifetime
		// means an edit (or a visibility change) stays live on intermediaries
		// well after the site has moved on. Revalidation keeps the ETag's
		// bandwidth win without the staleness window.
		$cache_control = $is_public ? 'public, max-age=0, must-revalidate' : 'no-store, private';
		$max_ops   = $this->references()->max_rendered_operations();

		$visibility = (string) ( get_post_meta( $post->ID, '_bd_api_visibility', true ) ?: 'public' );

		/**
		 * ETag identity: spec bytes + everything that changes the response
		 * shape. Consumers of `betterdocs_api_ref_served_spec` (Pro's AI
		 * overlay) must append their own version marker here or cached
		 * responses go stale.
		 *
		 * Visibility is part of the identity: without it, switching a reference
		 * from public to logged_in leaves the ETag unchanged, so a shared or CDN
		 * cache keeps serving — and can even 304-revalidate — the public copy it
		 * already holds. The response would be restricted; the cached bytes
		 * would not be.
		 */
		$etag_seed = apply_filters(
			'betterdocs_api_ref_spec_etag_seed',
			$active->hash . '|' . $max_ops . '|' . ( $is_pro ? 'pro' : 'free' ) . '|' . $visibility,
			$post
		);
		$etag      = '"' . md5( $etag_seed ) . '"';

		if ( $is_public && $request->get_header( 'if_none_match' ) === $etag ) {
			$not_modified = new \WP_REST_Response( null, 304 );
			$not_modified->header( 'ETag', $etag );

			return $not_modified;
		}

		$parsed = $this->container->get( SpecParser::class )->parse( $active->raw, $active->format );

		if ( is_wp_error( $parsed ) ) {
			return $this->error( 'betterdocs_api_spec_unreadable', __( 'The stored spec could not be parsed — re-upload it.', 'betterdocs-pro' ), 500 );
		}

		$spec = $parsed['spec'];

		/**
		 * The served document, before Free capping. Pro merges the AI overlay
		 * (generated descriptions/examples) here so the Scalar explorer shows
		 * them without the stored spec ever being mutated.
		 *
		 * @param array           $spec
		 * @param \WP_Post        $post    The reference.
		 * @param WP_REST_Request $request
		 */
		$spec = apply_filters( 'betterdocs_api_ref_served_spec', $spec, $post, $request );

		$truncated = $this->container->get( SpecSummary::class )->truncate( $spec, $max_ops );
		$spec      = $truncated['spec'];

		$spec['_betterdocs'] = [
			'capped'   => $truncated['rendered'] < $truncated['total'],
			'total'    => $truncated['total'],
			'rendered' => $truncated['rendered'],
			'powered'  => ! $is_pro
		];

		$response = new \WP_REST_Response( $spec, 200 );
		$response->header( 'ETag', $etag );
		$response->header( 'Cache-Control', $cache_control );
		// Restricted specs vary by who is asking; say so, so a shared cache can
		// never key one user's copy for everyone.
		$response->header( 'Vary', 'Cookie' );

		return $response;
	}

	/**
	 * GET /api-ref
	 */
	/**
	 * Manual "finish this run" — drains a queued materialization synchronously.
	 *
	 * The automatic recovery in list_references() only fires while somebody has
	 * the admin screen open. This is the explicit escape hatch for a run that
	 * stalled and was left, with a larger budget since the caller is waiting on
	 * it deliberately.
	 *
	 * @param WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function resume_materialize( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( get_post_type( $id ) !== $this->references()->post_type ) {
			return $this->error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ), 404 );
		}

		$materializer = betterdocs()->container->get( Materializer::class );

		// Forced: the caller asked for this run to finish, so it drains even if
		// the last tick was recent enough that the poll would have left it alone.
		$materializer->recover_if_stalled( $id, 20, true );

		$status = get_post_meta( $id, '_bd_api_materialize_status', true );

		return $this->success(
			[
				'status' => is_array( $status ) ? $status : [ 'state' => 'idle' ]
			]
		);
	}

	public function list_references( WP_REST_Request $request ) {
		// -1 rather than 100: max_references() is PHP_INT_MAX in Pro, so a fixed
		// cap silently dropped references past the hundredth from the admin
		// table — they still existed, still generated docs, and simply could not
		// be seen or edited. The row payload is small (no spec bytes) and the
		// post type is admin-only, so an unbounded fetch is the honest choice
		// until this screen grows real pagination.
		$references = get_posts(
			[
				'post_type'      => $this->references()->post_type,
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC'
			]
		);

		// The admin polls this route while a run is going, which makes it the
		// natural heartbeat for a queue nothing else is advancing. Each poll
		// gives a stalled run a short synchronous push — see
		// Materializer::recover_if_stalled().
		$materializer = betterdocs()->container->get( Materializer::class );
		foreach ( $references as $reference ) {
			$materializer->recover_if_stalled( $reference->ID );
		}

		return $this->success(
			[
				'references'     => array_map( [ $this, 'prepare_reference' ], $references ),
				'max_references' => $this->references()->max_references(),
				'is_pro'         => betterdocs()->is_pro_active(),
				'roles'          => $this->editable_roles()
			]
		);
	}

	/**
	 * Role slug → display name, for the Pro visibility role picker.
	 *
	 * @return array<string,string>
	 */
	protected function editable_roles() {
		$roles = [];

		foreach ( wp_roles()->roles as $slug => $role ) {
			$roles[ $slug ] = translate_user_role( $role['name'] );
		}

		return $roles;
	}

	/**
	 * POST /api-ref  { title, slug?, status? }
	 */
	public function create_reference( WP_REST_Request $request ) {
		$title = sanitize_text_field( (string) $request->get_param( 'title' ) );

		// The name is optional: an OpenAPI document already carries one in
		// `info.title` (SpecValidator rejects a spec without it), so making the
		// user retype it is busywork. The reference has to exist before the spec
		// can be posted to it, though, so it is created under a placeholder and
		// flagged — SpecIngestor renames it the moment a spec lands. The flag is
		// what keeps that rename from ever touching a name the user did type.
		$auto_title = '' === $title;

		if ( $auto_title ) {
			$title = __( 'Untitled API', 'betterdocs-pro' );
		}

		$existing = $this->reference_count();
		if ( $existing >= $this->references()->max_references() ) {
			return $this->error(
				'betterdocs_api_ref_limit',
				__( 'The free version supports one API Reference. Upgrade to BetterDocs Pro for unlimited references.', 'betterdocs-pro' ),
				403,
				[ 'upgrade' => true ]
			);
		}

		// Honor the chosen status (the Create drawer offers Draft / Publish), but
		// default to publish. Defaulting to draft meant a successful import
		// produced a reference and a full set of endpoint docs that resolved
		// nowhere on the frontend, with nothing saying why — the caller has to
		// ask for draft now, rather than fall into it.
		$status = 'draft' === $request->get_param( 'status' ) ? 'draft' : 'publish';

		$post_id = wp_insert_post(
			[
				'post_type'   => $this->references()->post_type,
				'post_title'  => $title,
				'post_name'   => sanitize_title( (string) $request->get_param( 'slug' ) ),
				'post_status' => $status
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $this->error( 'betterdocs_api_ref_create_failed', $post_id->get_error_message(), 500 );
		}

		update_post_meta( $post_id, '_bd_api_source', 'upload' );

		if ( $auto_title ) {
			update_post_meta( $post_id, '_bd_api_title_auto', '1' );
		}

		$result = $this->apply_settings( $post_id, $request );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success( $this->prepare_reference( get_post( $post_id ) ) );
	}

	/**
	 * GET /api-ref/{id}
	 */
	public function get_reference( WP_REST_Request $request ) {
		$post = $this->find_reference( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		return $this->success( $this->prepare_reference( $post, true ) );
	}

	/**
	 * POST /api-ref/{id}  { title?, slug?, status?, source?, source_url?, sync_interval?, visibility?, visibility_roles?, theme? }
	 */
	public function update_reference( WP_REST_Request $request ) {
		$post = $this->find_reference( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$update = [ 'ID' => $post->ID ];

		if ( null !== $request->get_param( 'title' ) ) {
			$title = sanitize_text_field( (string) $request->get_param( 'title' ) );

			// Clearing the name falls back to the spec's `info.title`, matching
			// create. Only when the spec cannot supply one (no spec uploaded yet)
			// is the existing title kept, so a reference can never end up nameless.
			if ( '' === $title ) {
				$summary = get_post_meta( $post->ID, '_bd_api_spec_summary', true );
				$title   = is_array( $summary ) && ! empty( $summary['title'] )
					? sanitize_text_field( (string) $summary['title'] )
					: $post->post_title;
			}

			$update['post_title'] = $title;
		}
		if ( null !== $request->get_param( 'slug' ) ) {
			$update['post_name'] = sanitize_title( (string) $request->get_param( 'slug' ) );
		}
		if ( null !== $request->get_param( 'status' ) ) {
			$status                = (string) $request->get_param( 'status' );
			$update['post_status'] = in_array( $status, [ 'publish', 'draft' ], true ) ? $status : 'draft';
		}

		if ( count( $update ) > 1 ) {
			$updated = wp_update_post( $update, true );

			if ( is_wp_error( $updated ) ) {
				return $this->error( 'betterdocs_api_ref_update_failed', $updated->get_error_message(), 500 );
			}
		}

		$result = $this->apply_settings( $post->ID, $request );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success( $this->prepare_reference( get_post( $post->ID ), true ) );
	}

	/**
	 * DELETE /api-ref/{id}
	 */
	public function delete_reference( WP_REST_Request $request ) {
		$post = $this->find_reference( $request['id'] );

		if ( is_wp_error( $post ) ) {
			// DELETE is idempotent: a reference that is already gone means the
			// caller's intent is satisfied, so report success instead of 404.
			// Removing a large reference deletes every doc it generated (260 on
			// a real spec, several seconds), and a second DELETE landing in that
			// window used to surface "Delete failed — API reference not found"
			// over a delete that had in fact worked.
			if ( 'betterdocs_api_ref_not_found' === $post->get_error_code() ) {
				return $this->success( __( 'Reference deleted.', 'betterdocs-pro' ) );
			}

			return $post;
		}

		// Spec rows are removed by ApiReferences' before_delete_post hook.
		$deleted = wp_delete_post( $post->ID, true );

		if ( ! $deleted ) {
			return $this->error( 'betterdocs_api_ref_delete_failed', __( 'The reference could not be deleted.', 'betterdocs-pro' ), 500 );
		}

		return $this->success( __( 'Reference deleted.', 'betterdocs-pro' ) );
	}

	/**
	 * POST /api-ref/{id}/spec-file — multipart upload, field name `spec`.
	 *
	 * The file is read, parsed, validated and discarded — it is never moved
	 * into uploads (ADR-006), so content-level validation replaces the
	 * wp_handle_upload mime dance (YAML has no reliable mime type anyway).
	 */
	public function upload_spec( WP_REST_Request $request ) {
		$post = $this->find_reference( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$files = $request->get_file_params();

		if ( empty( $files['spec'] ) || ! is_array( $files['spec'] ) ) {
			return $this->error( 'betterdocs_api_spec_no_file', __( 'No spec file received (multipart field "spec").', 'betterdocs-pro' ), 400 );
		}

		$file = $files['spec'];

		if ( ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return $this->error( 'betterdocs_api_spec_upload_failed', __( 'The upload did not complete — try again.', 'betterdocs-pro' ), 400 );
		}

		$max_bytes = $this->references()->max_spec_bytes();

		if ( (int) $file['size'] > $max_bytes ) {
			return $this->error(
				'betterdocs_api_spec_too_large',
				sprintf(
					/* translators: %s: maximum allowed size */
					__( 'The spec exceeds the %s limit.', 'betterdocs-pro' ),
					size_format( $max_bytes )
				),
				400,
				betterdocs()->is_pro_active() ? [] : [ 'upgrade' => true ]
			);
		}

		$extension = strtolower( (string) pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $extension, self::EXTENSIONS, true ) ) {
			return $this->error( 'betterdocs_api_spec_bad_extension', __( 'Only .json, .yaml and .yml files are accepted.', 'betterdocs-pro' ), 400 );
		}

		$raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- local tmp upload.

		return $this->ingest( $post->ID, (string) $raw, 'json' === $extension ? 'json' : 'yaml' );
	}

	/**
	 * Shared ingestion → HTTP response. Delegates the parse/validate/store
	 * pipeline to SpecIngestor (also used by Pro's URL sync) and maps its
	 * result / WP_Error onto the REST envelope.
	 *
	 * @param int         $reference_id
	 * @param string      $raw
	 * @param string|null $format
	 * @param array       $store_args
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function ingest( $reference_id, $raw, $format = null, $store_args = [] ) {
		$result = $this->container->get( SpecIngestor::class )->ingest( $reference_id, $raw, $format, $store_args );

		if ( is_wp_error( $result ) ) {
			$data   = $result->get_error_data();
			$errors = ( is_array( $data ) && isset( $data['errors'] ) )
				? $data['errors']
				: [ [ 'pointer' => '', 'message' => $result->get_error_message() ] ];

			return $this->error(
				$result->get_error_code(),
				$result->get_error_message(),
				400,
				[ 'errors' => $errors ]
			);
		}

		return $this->success( $result );
	}

	/**
	 * Meta settings shared by create/update, with the Free gates.
	 *
	 * @param int             $post_id
	 * @param WP_REST_Request $request
	 * @return true|\WP_Error
	 */
	protected function apply_settings( $post_id, WP_REST_Request $request ) {
		$branding_before = BrandPropagator::snapshot( $post_id );

		// Pro-only feature — no tier gating. The meta sanitize_callbacks validate
		// each value (source upload|url, interval manual|hourly|daily|weekly, …).
		foreach ( [
			'source'            => '_bd_api_source',
			'source_url'        => '_bd_api_source_url',
			'sync_interval'     => '_bd_api_sync_interval',
			// Try-it banner branding — per reference, read at render time so a
			// colour change re-skins this reference's docs with no rebuild.
			'accent_color'      => '_bd_api_accent_color',
			'accent_text_color' => '_bd_api_accent_text_color',
			'tryit_label'       => '_bd_api_tryit_label',
			'tryit_enabled'     => '_bd_api_tryit_enabled',
			'code_theme'        => '_bd_api_code_theme',
			// Try-it proxy, per reference (was a site-wide setting).
			'proxy_enabled'     => '_bd_api_proxy_enabled',
			'proxy_hosts'       => '_bd_api_proxy_hosts'
		] as $param => $meta_key ) {
			if ( null !== $request->get_param( $param ) ) {
				update_post_meta( $post_id, $meta_key, $request->get_param( $param ) );
			}
		}

		// Destination (Multiple-KB): kb_mode = kb|category; kb_slug = chosen KB
		// when in category mode. Consumed by the betterdocs_api_docs_knowledge_base
		// filter during materialization.
		if ( null !== $request->get_param( 'kb_mode' ) ) {
			update_post_meta( $post_id, '_bd_api_kb_mode', 'category' === $request->get_param( 'kb_mode' ) ? 'category' : 'kb' );
		}
		if ( null !== $request->get_param( 'kb_slug' ) ) {
			update_post_meta( $post_id, '_bd_api_kb_slug', sanitize_title( (string) $request->get_param( 'kb_slug' ) ) );
		}

		// One branding change repaints every doc this reference generated: the
		// colours and the code-snippet mode live in each doc's blocks (so they
		// are editable there), so they are rewritten in a single pass rather
		// than by re-generating.
		if ( BrandPropagator::changed( $post_id, $branding_before ) ) {
			BrandPropagator::apply( $post_id );
		}

		return true;
	}

	/**
	 * @param mixed $id
	 * @return \WP_Post|\WP_Error
	 */
	protected function find_reference( $id ) {
		$post = get_post( absint( $id ) );

		if ( ! $post || $post->post_type !== $this->references()->post_type ) {
			return $this->error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ), 404 );
		}

		return $post;
	}

	/**
	 * @param \WP_Post $post
	 * @param bool     $with_spec_state
	 * @return array
	 */
	protected function prepare_reference( $post, $with_spec_state = false ) {
		$summary = get_post_meta( $post->ID, '_bd_api_spec_summary', true );

		// Generation state travels with the list so the table can show a row as
		// "Generating…" without the drawer being open. Reading it here (rather
		// than tracking it in React when the run is kicked off) is what makes the
		// state survive closing the drawer, navigating away, or a full reload —
		// the background run keeps going server-side either way. Only state +
		// progress: the full report is the drawer's business, via ApiMaterialize.
		$materialize = get_post_meta( $post->ID, '_bd_api_materialize_status', true );
		$progress    = is_array( $materialize ) && isset( $materialize['progress'] ) && is_array( $materialize['progress'] )
			? $materialize['progress']
			: null;

		$data = [
			'id'               => $post->ID,
			'title'            => $post->post_title,
			'slug'             => $post->post_name,
			'status'           => $post->post_status,
			'permalink'        => get_permalink( $post ),
			'source'           => get_post_meta( $post->ID, '_bd_api_source', true ) ?: 'upload',
			'source_url'       => get_post_meta( $post->ID, '_bd_api_source_url', true ),
			'source_kind'      => get_post_meta( $post->ID, '_bd_api_source_kind', true ) ?: 'openapi',
			'sync_interval'    => get_post_meta( $post->ID, '_bd_api_sync_interval', true ) ?: 'manual',
			'kb_mode'          => get_post_meta( $post->ID, '_bd_api_kb_mode', true ) ?: 'kb',
			'kb_slug'          => get_post_meta( $post->ID, '_bd_api_kb_slug', true ),
			// Try-it banner branding ('' accent = stock palette).
			'accent_color'      => get_post_meta( $post->ID, '_bd_api_accent_color', true ),
			'accent_text_color' => get_post_meta( $post->ID, '_bd_api_accent_text_color', true ),
			'tryit_label'       => get_post_meta( $post->ID, '_bd_api_tryit_label', true ),
			'tryit_enabled'     => '0' !== (string) get_post_meta( $post->ID, '_bd_api_tryit_enabled', true ),
			'code_theme'        => 'dark' === get_post_meta( $post->ID, '_bd_api_code_theme', true ) ? 'dark' : 'light',
			'proxy_enabled'     => '1' === (string) get_post_meta( $post->ID, '_bd_api_proxy_enabled', true ),
			'proxy_hosts'       => get_post_meta( $post->ID, '_bd_api_proxy_hosts', true ),
			'summary'          => is_array( $summary ) ? $summary : null,
			'sync_status'      => get_post_meta( $post->ID, '_bd_api_sync_status', true ) ?: null,
			'materialized'     => $this->references()->is_materialized( $post->ID ),
			'materialize_status' => is_array( $materialize ) ? [
				'state'    => isset( $materialize['state'] ) ? (string) $materialize['state'] : 'idle',
				'progress' => $progress ? [
					'done'  => isset( $progress['done'] ) ? (int) $progress['done'] : 0,
					'total' => isset( $progress['total'] ) ? (int) $progress['total'] : 0
				] : null
			] : null
		];

		if ( $with_spec_state ) {
			$active = $this->container->get( SpecStore::class )->get_active( $post->ID );

			$data['spec'] = $active
				? [
					'format'     => $active->format,
					'hash'       => $active->hash,
					'fetched_at' => $active->fetched_at
				]
				: null;
		}

		return $data;
	}

	/**
	 * @return int
	 */
	protected function reference_count() {
		$counts = (array) wp_count_posts( $this->references()->post_type );
		unset( $counts['trash'], $counts['auto-draft'] );

		return array_sum( array_map( 'intval', $counts ) );
	}

	/**
	 * @return ApiReferences
	 */
	protected function references() {
		return $this->container->get( ApiReferences::class );
	}
}
