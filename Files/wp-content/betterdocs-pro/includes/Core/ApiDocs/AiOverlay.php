<?php

namespace WPDeveloper\BetterDocsPro\Core\ApiDocs;

use WP_Error;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecRefResolver;
use WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecSummary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * F10.1 + F10.3 — the AI spec overlay (ADR-021, ADR-024).
 *
 * Plans which operations lack descriptions / example slots, orchestrates
 * chunked generation through the AiClient ladder, stores results in
 * `_bd_api_ai_overlay` reference meta, and merges them read-side into the
 * served spec (Scalar explorer) and the materializer's spec (endpoint docs).
 * The stored raw spec is never mutated; spec-authored content always wins.
 */
class AiOverlay {
	const META_OVERLAY = '_bd_api_ai_overlay';
	const META_STATUS  = '_bd_api_ai_status';

	const LOCK_TRANSIENT = 'bd_api_ai_lock_';
	const LOCK_TTL       = 300;

	/**
	 * Chunks at or below this run inline in the REST request; more go to the
	 * background queue (ADR-023). Filter: betterdocs_api_ai_inline_max_chunks.
	 */
	const INLINE_MAX_CHUNKS = 3;

	/**
	 * Description prose cap (characters) — prompt asks for 2-4 sentences,
	 * this is the hard stop against runaway output.
	 */
	const MAX_DESCRIPTION = 2000;

	/**
	 * Serialized-example cap (bytes) per slot.
	 */
	const MAX_EXAMPLE_BYTES = 20000;

	/**
	 * @var AiClient
	 */
	protected $client;

	/**
	 * @var SpecRefResolver
	 */
	protected $resolver;

	/**
	 * @var SpecSummary
	 */
	protected $summary;

	public function __construct() {
		$this->client   = new AiClient();
		$this->resolver = betterdocs()->container->get( SpecRefResolver::class );
		$this->summary  = betterdocs()->container->get( SpecSummary::class );

		// Read-side merges (ADR-021): explorer + endpoint docs.
		add_filter( 'betterdocs_api_ref_served_spec', [ $this, 'merge_served' ], 10, 2 );
		add_filter( 'betterdocs_api_ref_spec_etag_seed', [ $this, 'etag_seed' ], 10, 2 );
		add_filter( 'betterdocs_api_docs_spec', [ $this, 'merge' ], 10, 2 );
	}

	/* ---------------------------------------------------------------- */
	/* Read-side merge                                                   */
	/* ---------------------------------------------------------------- */

	/**
	 * @param array    $spec
	 * @param \WP_Post $post
	 * @return array
	 */
	public function merge_served( array $spec, $post ) {
		return $this->merge( $spec, $post->ID );
	}

	/**
	 * @param string   $seed
	 * @param \WP_Post $post
	 * @return string
	 */
	public function etag_seed( $seed, $post ) {
		$overlay = $this->get_overlay( $post->ID );

		if ( empty( $overlay['ops'] ) ) {
			return $seed;
		}

		return $seed . '|ai:' . md5( wp_json_encode( $overlay ) );
	}

	/**
	 * Fill spec gaps from the stored overlay. Pure — no writes.
	 *
	 * @param array $spec
	 * @param int   $reference_id
	 * @return array
	 */
	public function merge( array $spec, $reference_id ) {
		$overlay = $this->get_overlay( $reference_id );

		if ( empty( $overlay['ops'] ) || empty( $spec['paths'] ) ) {
			return $spec;
		}

		foreach ( $this->index_operations( $spec ) as $key => $op ) {
			if ( empty( $overlay['ops'][ $key ] ) || ! is_array( $overlay['ops'][ $key ] ) ) {
				continue;
			}

			$entry     = $overlay['ops'][ $key ];
			$operation = &$spec['paths'][ $op['path'] ][ $op['method'] ];

			// Prose gaps.
			if ( ! empty( $entry['description'] ) && $this->lacks_description( $operation ) ) {
				$operation['description'] = (string) $entry['description'];
			}

			if ( ! empty( $entry['summary'] ) && empty( $operation['summary'] ) ) {
				$operation['summary'] = (string) $entry['summary'];
			}

			// Example gaps (media-level `example`, JSON media only — ADR-024).
			if ( ! empty( $entry['examples']['request'] ) && is_array( $entry['examples']['request'] ) ) {
				foreach ( $entry['examples']['request'] as $media_type => $example ) {
					if ( isset( $operation['requestBody']['content'][ $media_type ] )
						&& $this->slot_is_gap( $operation['requestBody']['content'][ $media_type ], $spec ) ) {
						$operation['requestBody']['content'][ $media_type ]['example'] = $example;
					}
				}
			}

			if ( ! empty( $entry['examples']['responses'] ) && is_array( $entry['examples']['responses'] ) ) {
				foreach ( $entry['examples']['responses'] as $code => $medias ) {
					if ( ! is_array( $medias ) ) {
						continue;
					}

					foreach ( $medias as $media_type => $example ) {
						if ( isset( $operation['responses'][ $code ]['content'][ $media_type ] )
							&& $this->slot_is_gap( $operation['responses'][ $code ]['content'][ $media_type ], $spec ) ) {
							$operation['responses'][ $code ]['content'][ $media_type ]['example'] = $example;
						}
					}
				}
			}

			unset( $operation );
		}

		return $spec;
	}

	/* ---------------------------------------------------------------- */
	/* Planning                                                          */
	/* ---------------------------------------------------------------- */

	/**
	 * What generation would touch: compact op digests per task.
	 *
	 * @param array $spec    Parsed (un-merged) spec.
	 * @param array $targets Subset of ['descriptions','examples'].
	 * @return array { descriptions: digest[], examples: digest[] }
	 */
	public function plan( array $spec, array $targets ) {
		$plan = [
			'descriptions' => [],
			'examples'     => []
		];

		$context_ops = $this->index_operations( $spec );

		foreach ( $context_ops as $key => $op ) {
			$operation = $this->resolver->resolve( $op['operation'], $spec );

			if ( in_array( 'descriptions', $targets, true ) && $this->lacks_description( $operation ) ) {
				$plan['descriptions'][] = $this->description_digest( $key, $op, $operation );
			}

			if ( in_array( 'examples', $targets, true ) ) {
				$digest = $this->example_digest( $key, $op, $operation, $spec );

				if ( null !== $digest ) {
					$plan['examples'][] = $digest;
				}
			}
		}

		return $plan;
	}

	/**
	 * @param array $operation Resolved.
	 * @return bool
	 */
	protected function lacks_description( array $operation ) {
		return empty( $operation['description'] ) || '' === trim( (string) $operation['description'] );
	}

	/**
	 * Whether a media object is still an example gap: no media example(s),
	 * no root-level schema example/default (which synthesis would surface).
	 *
	 * @param array $media Media object (maybe unresolved schema).
	 * @param array $spec  For $ref resolution.
	 * @return bool
	 */
	protected function slot_is_gap( $media, array $spec ) {
		if ( ! is_array( $media ) ) {
			return false;
		}

		if ( array_key_exists( 'example', $media ) || ! empty( $media['examples'] ) ) {
			return false;
		}

		$schema = isset( $media['schema'] ) && is_array( $media['schema'] ) ? $this->resolver->resolve( $media['schema'], $spec ) : [];

		return ! array_key_exists( 'example', $schema ) && ! array_key_exists( 'default', $schema );
	}

	/**
	 * @param string $key
	 * @param array  $op        { method, path, operation (raw) }
	 * @param array  $operation Resolved.
	 * @return array
	 */
	protected function description_digest( $key, array $op, array $operation ) {
		$params = [];

		if ( isset( $operation['parameters'] ) && is_array( $operation['parameters'] ) ) {
			foreach ( array_slice( $operation['parameters'], 0, 15 ) as $param ) {
				if ( ! is_array( $param ) || empty( $param['name'] ) ) {
					continue;
				}

				$params[] = [
					'name'        => (string) $param['name'],
					'in'          => isset( $param['in'] ) ? (string) $param['in'] : 'query',
					'required'    => ! empty( $param['required'] ),
					'type'        => isset( $param['schema']['type'] ) ? (string) $param['schema']['type'] : '',
					'description' => isset( $param['description'] ) ? $this->snip( (string) $param['description'], 160 ) : ''
				];
			}
		}

		$responses = [];

		if ( isset( $operation['responses'] ) && is_array( $operation['responses'] ) ) {
			foreach ( $operation['responses'] as $code => $response ) {
				$responses[ (string) $code ] = is_array( $response ) && isset( $response['description'] )
					? $this->snip( (string) $response['description'], 120 )
					: '';
			}
		}

		return [
			'key'        => $key,
			'method'     => strtoupper( $op['method'] ),
			'path'       => $op['path'],
			'summary'    => isset( $operation['summary'] ) ? (string) $operation['summary'] : '',
			'tag'        => isset( $operation['tags'][0] ) ? (string) $operation['tags'][0] : '',
			'sig'        => $this->signature( $op['operation'] ),
			'parameters' => $params,
			'responses'  => $responses
		];
	}

	/**
	 * Digest of an op's missing example slots, or null when it has none.
	 *
	 * @param string $key
	 * @param array  $op
	 * @param array  $operation Resolved.
	 * @param array  $spec
	 * @return array|null
	 */
	protected function example_digest( $key, array $op, array $operation, array $spec ) {
		$request   = [];
		$responses = [];

		if ( isset( $operation['requestBody']['content'] ) && is_array( $operation['requestBody']['content'] ) ) {
			foreach ( $operation['requestBody']['content'] as $media_type => $media ) {
				if ( $this->is_json_media( $media_type ) && $this->slot_is_gap( $media, $spec ) && isset( $media['schema'] ) ) {
					$request[ $media_type ] = $this->trim_schema( $this->resolver->resolve( $media['schema'], $spec ) );
				}
			}
		}

		if ( isset( $operation['responses'] ) && is_array( $operation['responses'] ) ) {
			foreach ( $operation['responses'] as $code => $response ) {
				if ( ! is_array( $response ) || empty( $response['content'] ) || ! is_array( $response['content'] ) ) {
					continue;
				}

				foreach ( $response['content'] as $media_type => $media ) {
					if ( $this->is_json_media( $media_type ) && $this->slot_is_gap( $media, $spec ) && isset( $media['schema'] ) ) {
						$responses[ (string) $code ][ $media_type ] = $this->trim_schema( $this->resolver->resolve( $media['schema'], $spec ) );
					}
				}
			}
		}

		if ( empty( $request ) && empty( $responses ) ) {
			return null;
		}

		return [
			'key'       => $key,
			'method'    => strtoupper( $op['method'] ),
			'path'      => $op['path'],
			'summary'   => isset( $operation['summary'] ) ? (string) $operation['summary'] : '',
			'sig'       => $this->signature( $op['operation'] ),
			'request'   => $request,
			'responses' => $responses
		];
	}

	/* ---------------------------------------------------------------- */
	/* Generation                                                        */
	/* ---------------------------------------------------------------- */

	/**
	 * Full generation run for a reference: prune → plan → chunk → inline or
	 * background (ADR-023).
	 *
	 * @param int    $reference_id
	 * @param array  $targets Subset of ['descriptions','examples'].
	 * @param string $mode    'auto'|'inline'|'background'
	 * @return array|WP_Error Report (inline) or running-status stub (background).
	 */
	public function generate( $reference_id, array $targets, $mode = 'auto' ) {
		$reference = get_post( $reference_id );

		if ( ! $reference || 'betterdocs_api_ref' !== $reference->post_type ) {
			return new WP_Error( 'betterdocs_api_ref_not_found', __( 'API reference not found.', 'betterdocs-pro' ) );
		}

		if ( $this->is_locked( $reference_id ) ) {
			return new WP_Error( 'betterdocs_api_ai_locked', __( 'An AI generation run is already in progress for this reference.', 'betterdocs-pro' ) );
		}

		$spec = $this->load_raw_spec( $reference_id );

		if ( is_wp_error( $spec ) ) {
			return $spec;
		}

		$this->prune( $reference_id, $spec['spec'] );

		$plan   = $this->plan( $spec['spec'], $targets );
		$chunks = [];

		foreach ( [ 'descriptions', 'examples' ] as $task ) {
			foreach ( array_chunk( $plan[ $task ], AiClient::CHUNK_SIZE ) as $chunk ) {
				$chunks[] = [
					'task'   => $task,
					'digest' => $chunk
				];
			}
		}

		$context = [
			'api_title'       => $reference->post_title,
			'api_description' => isset( $spec['spec']['info']['description'] ) ? (string) $spec['spec']['info']['description'] : ''
		];

		if ( empty( $chunks ) ) {
			$report = [
				'mode'      => $this->client->mode(),
				'generated' => 0,
				'failed'    => [],
				'errors'    => [],
				'message'   => __( 'Nothing to generate — every operation already has descriptions and examples.', 'betterdocs-pro' )
			];
			$this->write_status( $reference_id, 'complete', $report );

			return $report;
		}

		$this->acquire_lock( $reference_id );

		$inline_max = (int) apply_filters( 'betterdocs_api_ai_inline_max_chunks', self::INLINE_MAX_CHUNKS );

		if ( 'background' === $mode || ( 'auto' === $mode && count( $chunks ) > $inline_max ) ) {
			AiProcess::dispatch_for( $reference_id, $spec['hash'], $chunks );

			return [
				'state'    => 'running',
				'progress' => [
					'done'  => 0,
					'total' => count( $chunks )
				]
			];
		}

		$report = [
			'mode'      => $this->client->mode(),
			'generated' => 0,
			'failed'    => [],
			'errors'    => []
		];

		foreach ( $chunks as $chunk ) {
			$fragment = $this->run_chunk( $reference_id, $chunk['task'], $chunk['digest'], $context );

			$this->merge_report( $report, $fragment );
		}

		$state = ( $report['generated'] > 0 || empty( $report['errors'] ) ) ? 'complete' : 'error';

		$this->write_status( $reference_id, $state, $report );
		$this->release_lock( $reference_id );

		return $report;
	}

	/**
	 * @param array $report By-ref accumulator.
	 * @param array $fragment run_chunk() result.
	 * @return void
	 */
	public function merge_report( array &$report, array $fragment ) {
		if ( isset( $fragment['error'] ) ) {
			$report['errors'][] = $fragment['error']->get_error_message();

			return;
		}

		$report['generated'] += (int) $fragment['generated'];
		$report['failed']     = array_merge( $report['failed'], $fragment['failed'] );

		if ( isset( $fragment['meta']['credits_remaining'] ) && null !== $fragment['meta']['credits_remaining'] ) {
			$report['credits_remaining'] = (int) $fragment['meta']['credits_remaining'];
		}
	}

	/**
	 * The active spec, parsed but NOT overlay-merged (planning must see the
	 * real gaps — the materializer's load_spec() would hand back our own
	 * merge).
	 *
	 * @param int $reference_id
	 * @return array|WP_Error { spec: array, hash: string }
	 */
	public function load_raw_spec( $reference_id ) {
		$active = betterdocs()->container->get( \WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecStore::class )->get_active( $reference_id );

		if ( ! $active ) {
			return new WP_Error( 'betterdocs_api_ai_no_spec', __( 'This reference has no spec yet.', 'betterdocs-pro' ) );
		}

		$parsed = betterdocs()->container->get( \WPDeveloper\BetterDocsPro\Core\ApiSpec\SpecParser::class )->parse( $active->raw, $active->format );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		return [
			'spec' => $parsed['spec'],
			'hash' => (string) $active->hash
		];
	}

	/**
	 * @param int $reference_id
	 * @return bool
	 */
	public function is_locked( $reference_id ) {
		return false !== get_transient( self::LOCK_TRANSIENT . $reference_id );
	}

	/**
	 * @param int $reference_id
	 * @return void
	 */
	public function acquire_lock( $reference_id ) {
		set_transient( self::LOCK_TRANSIENT . $reference_id, time(), self::LOCK_TTL );
	}

	/**
	 * @param int $reference_id
	 * @return void
	 */
	public function release_lock( $reference_id ) {
		delete_transient( self::LOCK_TRANSIENT . $reference_id );
	}

	/**
	 * Run one task chunk through the ladder and store the surviving entries.
	 *
	 * @param int    $reference_id
	 * @param string $task   'descriptions'|'examples'
	 * @param array  $chunk  Digests (≤ AiClient::CHUNK_SIZE).
	 * @param array  $context
	 * @return array { generated: int, failed: string[], meta: array } |
	 *               array{ error: WP_Error } on a whole-chunk failure.
	 */
	public function run_chunk( $reference_id, $task, array $chunk, array $context = [] ) {
		$result = $this->client->generate_ops( $task, $chunk, $context );

		// LLM formatting misses are transient — one retry on a malformed reply
		// (and only on that; quota/transport errors are final). ADR-023 still
		// holds: never more than one.
		if ( is_wp_error( $result ) && 'betterdocs_api_ai_bad_json' === $result->get_error_code() ) {
			$result = $this->client->generate_ops( $task, $chunk, $context );
		}

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result ];
		}

		$entries = [];
		$failed  = [];

		foreach ( $chunk as $digest ) {
			$key     = $digest['key'];
			$payload = isset( $result['ops'][ $key ] ) && is_array( $result['ops'][ $key ] ) ? $result['ops'][ $key ] : null;
			$entry   = null !== $payload ? $this->validate_entry( $task, $digest, $payload ) : null;

			if ( null === $entry ) {
				$failed[] = strtoupper( $digest['method'] ) . ' ' . $digest['path'];
				continue;
			}

			$entries[ $key ] = $entry + [ 'sig' => $digest['sig'] ];
		}

		if ( $entries ) {
			$this->store_entries( $reference_id, $task, $entries );
		}

		return [
			'generated' => count( $entries ),
			'failed'    => $failed,
			'meta'      => $result['meta']
		];
	}

	/**
	 * Validate + sanitize one model-returned op payload against its digest.
	 *
	 * @param string $task
	 * @param array  $digest
	 * @param array  $payload
	 * @return array|null Normalized entry fragment, or null (reported failed).
	 */
	protected function validate_entry( $task, array $digest, array $payload ) {
		if ( 'descriptions' === $task ) {
			$description = isset( $payload['description'] ) && is_string( $payload['description'] )
				? trim( $payload['description'] )
				: '';

			if ( '' === $description ) {
				return null;
			}

			$entry = [ 'description' => $this->snip( $description, self::MAX_DESCRIPTION ) ];

			if ( '' === $digest['summary'] && ! empty( $payload['summary'] ) && is_string( $payload['summary'] ) ) {
				$entry['summary'] = $this->snip( wp_strip_all_tags( trim( $payload['summary'] ) ), 120 );
			}

			return $entry;
		}

		// Examples: accept only the slots we asked for, shallow-validated.
		$examples = [
			'request'   => [],
			'responses' => []
		];

		foreach ( $digest['request'] as $media_type => $schema ) {
			if ( isset( $payload['request'][ $media_type ] )
				&& $this->example_matches( $payload['request'][ $media_type ], $schema ) ) {
				$examples['request'][ $media_type ] = $payload['request'][ $media_type ];
			}
		}

		foreach ( $digest['responses'] as $code => $medias ) {
			foreach ( $medias as $media_type => $schema ) {
				if ( isset( $payload['responses'][ $code ][ $media_type ] )
					&& $this->example_matches( $payload['responses'][ $code ][ $media_type ], $schema ) ) {
					$examples['responses'][ $code ][ $media_type ] = $payload['responses'][ $code ][ $media_type ];
				}
			}
		}

		if ( empty( $examples['request'] ) && empty( $examples['responses'] ) ) {
			return null;
		}

		return [ 'examples' => $examples ];
	}

	/**
	 * Shallow schema check (ADR-024): root type matches, required root
	 * properties present, size-capped.
	 *
	 * @param mixed $example
	 * @param array $schema Resolved, trimmed.
	 * @return bool
	 */
	protected function example_matches( $example, array $schema ) {
		$encoded = wp_json_encode( $example );

		if ( false === $encoded || strlen( $encoded ) > self::MAX_EXAMPLE_BYTES ) {
			return false;
		}

		$type = isset( $schema['type'] ) ? (string) $schema['type'] : '';

		switch ( $type ) {
			case 'object':
				if ( ! is_array( $example ) || ( ! empty( $example ) && array_keys( $example ) === range( 0, count( $example ) - 1 ) ) ) {
					return false;
				}

				if ( ! empty( $schema['required'] ) && is_array( $schema['required'] ) ) {
					foreach ( $schema['required'] as $prop ) {
						if ( ! array_key_exists( (string) $prop, $example ) ) {
							return false;
						}
					}
				}

				return true;
			case 'array':
				return is_array( $example ) && ( empty( $example ) || array_keys( $example ) === range( 0, count( $example ) - 1 ) );
			case 'string':
				return is_string( $example );
			case 'integer':
				return is_int( $example );
			case 'number':
				return is_int( $example ) || is_float( $example );
			case 'boolean':
				return is_bool( $example );
			default:
				return true; // Untyped/composed schema — accept anything encodable.
		}
	}

	/* ---------------------------------------------------------------- */
	/* Storage + status                                                  */
	/* ---------------------------------------------------------------- */

	/**
	 * @param int $reference_id
	 * @return array { ops: array }
	 */
	public function get_overlay( $reference_id ) {
		$overlay = get_post_meta( $reference_id, self::META_OVERLAY, true );

		return is_array( $overlay ) && isset( $overlay['ops'] ) && is_array( $overlay['ops'] )
			? $overlay
			: [ 'ops' => [] ];
	}

	/**
	 * Merge task entries into the stored overlay (per-field: a descriptions
	 * run never clobbers stored examples and vice versa).
	 *
	 * @param int    $reference_id
	 * @param string $task
	 * @param array  $entries op_key => fragment (incl. sig).
	 * @return void
	 */
	public function store_entries( $reference_id, $task, array $entries ) {
		$overlay = $this->get_overlay( $reference_id );

		foreach ( $entries as $key => $fragment ) {
			$existing = isset( $overlay['ops'][ $key ] ) && is_array( $overlay['ops'][ $key ] ) ? $overlay['ops'][ $key ] : [];

			$overlay['ops'][ $key ] = array_merge( $existing, $fragment );
		}

		update_post_meta( $reference_id, self::META_OVERLAY, $overlay );
	}

	/**
	 * Drop entries whose operation vanished or whose gap closed (the author
	 * wrote the description / example into the spec) — ADR-021.
	 *
	 * @param int   $reference_id
	 * @param array $spec Parsed (un-merged) spec.
	 * @return int Pruned entry count.
	 */
	public function prune( $reference_id, array $spec ) {
		$overlay = $this->get_overlay( $reference_id );

		if ( empty( $overlay['ops'] ) ) {
			return 0;
		}

		$ops    = $this->index_operations( $spec );
		$pruned = 0;

		foreach ( $overlay['ops'] as $key => $entry ) {
			if ( ! isset( $ops[ $key ] ) ) {
				unset( $overlay['ops'][ $key ] );
				$pruned++;
				continue;
			}

			$operation = $this->resolver->resolve( $ops[ $key ]['operation'], $spec );

			if ( isset( $entry['description'] ) && ! $this->lacks_description( $operation ) ) {
				unset( $overlay['ops'][ $key ]['description'], $overlay['ops'][ $key ]['summary'] );
				$pruned++;
			}

			$fields = array_diff( array_keys( (array) $overlay['ops'][ $key ] ), [ 'sig' ] );

			if ( empty( $fields ) ) {
				unset( $overlay['ops'][ $key ] );
			}
		}

		update_post_meta( $reference_id, self::META_OVERLAY, $overlay );

		return $pruned;
	}

	/**
	 * @param int $reference_id
	 * @return void
	 */
	public function clear( $reference_id ) {
		delete_post_meta( $reference_id, self::META_OVERLAY );
		delete_post_meta( $reference_id, self::META_STATUS );
	}

	/**
	 * @param int    $reference_id
	 * @param string $state 'idle'|'running'|'complete'|'error'
	 * @param array  $report
	 * @param array  $progress { done, total }
	 * @return void
	 */
	public function write_status( $reference_id, $state, array $report = [], array $progress = [] ) {
		update_post_meta(
			$reference_id,
			self::META_STATUS,
			[
				'state'    => $state,
				'time'     => time(),
				'progress' => $progress,
				'report'   => $report
			]
		);
	}

	/**
	 * @param int $reference_id
	 * @return array|null
	 */
	public function get_status( $reference_id ) {
		$status = get_post_meta( $reference_id, self::META_STATUS, true );

		return is_array( $status ) ? $status : null;
	}

	/* ---------------------------------------------------------------- */
	/* Helpers                                                           */
	/* ---------------------------------------------------------------- */

	/**
	 * op_key => { method, path, operation } — same identity rules as
	 * SpecDiff / the materializer.
	 *
	 * @param array $spec
	 * @return array<string, array>
	 */
	public function index_operations( array $spec ) {
		$index    = [];
		$seen_ids = [];

		foreach ( $this->summary->operations( $spec ) as $pair ) {
			list( $path, $method ) = $pair;

			$operation = $spec['paths'][ $path ][ $method ];
			$key       = 'hash:' . md5( strtolower( $method ) . ' ' . $path );

			if ( ! empty( $operation['operationId'] ) && ! isset( $seen_ids[ (string) $operation['operationId'] ] ) ) {
				$key                                            = (string) $operation['operationId'];
				$seen_ids[ (string) $operation['operationId'] ] = true;
			}

			$index[ $key ] = [
				'method'    => $method,
				'path'      => $path,
				'operation' => $operation
			];
		}

		return $index;
	}

	/**
	 * @param string $media_type
	 * @return bool
	 */
	protected function is_json_media( $media_type ) {
		return false !== strpos( strtolower( (string) $media_type ), 'json' );
	}

	/**
	 * Compact, depth-capped schema for prompts: enough to generate faithful
	 * values without shipping the whole component tree.
	 *
	 * @param array $schema Resolved.
	 * @param int   $depth
	 * @return array
	 */
	public function trim_schema( array $schema, $depth = 0 ) {
		$keep = [];

		foreach ( [ 'type', 'format', 'enum', 'required', 'example', 'default' ] as $field ) {
			if ( array_key_exists( $field, $schema ) ) {
				$keep[ $field ] = $schema[ $field ];
			}
		}

		if ( isset( $schema['description'] ) ) {
			$keep['description'] = $this->snip( (string) $schema['description'], 120 );
		}

		if ( $depth >= 3 ) {
			return $keep;
		}

		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( array_slice( $schema['properties'], 0, 30, true ) as $name => $prop ) {
				if ( is_array( $prop ) ) {
					$keep['properties'][ $name ] = $this->trim_schema( $prop, $depth + 1 );
				}
			}
		}

		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$keep['items'] = $this->trim_schema( $schema['items'], $depth + 1 );
		}

		foreach ( [ 'allOf', 'oneOf', 'anyOf' ] as $poly ) {
			if ( isset( $schema[ $poly ] ) && is_array( $schema[ $poly ] ) ) {
				foreach ( array_slice( $schema[ $poly ], 0, 5 ) as $i => $part ) {
					if ( is_array( $part ) ) {
						$keep[ $poly ][ $i ] = $this->trim_schema( $part, $depth + 1 );
					}
				}
			}
		}

		return $keep;
	}

	/**
	 * Stable signature of the raw operation node (stale-entry detection).
	 *
	 * @param array $operation Raw (un-resolved) node.
	 * @return string
	 */
	public function signature( array $operation ) {
		return md5( wp_json_encode( $this->ksort_deep( $operation ) ) );
	}

	/**
	 * @param mixed $node
	 * @return mixed
	 */
	protected function ksort_deep( $node ) {
		if ( ! is_array( $node ) ) {
			return $node;
		}

		$is_assoc = array_keys( $node ) !== range( 0, count( $node ) - 1 );

		foreach ( $node as $key => $value ) {
			$node[ $key ] = $this->ksort_deep( $value );
		}

		if ( $is_assoc ) {
			ksort( $node );
		}

		return $node;
	}

	/**
	 * @param string $text
	 * @param int    $max
	 * @return string
	 */
	protected function snip( $text, $max ) {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $max );
		}

		return substr( $text, 0, $max );
	}

	/**
	 * @return AiClient
	 */
	public function client() {
		return $this->client;
	}
}
