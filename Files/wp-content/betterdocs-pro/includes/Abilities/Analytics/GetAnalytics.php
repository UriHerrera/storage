<?php
/**
 * Site-wide analytics ability.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocs\Abilities\AbilityError;
use WPDeveloper\BetterDocs\Abilities\ProState;
use WPDeveloper\BetterDocsPro\Abilities\ProAbility;

/**
 * One read of what the whole knowledge base is doing: views and reactions, the
 * docs, categories and knowledge bases people actually reach, and what they
 * searched for.
 *
 * Free's `bd-get-doc-analytics` answers for one doc; this is the site-wide
 * counterpart, and it is Pro because the tables and the rollups behind it are
 * (ADR-011).
 *
 * Every number comes from the Advanced Analytics read API
 * (`betterdocs/v1/analytics/*`) — the same routes the analytics dashboard reads,
 * so a number reported here and a number on the screen have one source. The
 * legacy `betterdocs/v1/overview` route is deliberately not used: it is a
 * `SUM( happy, sad, normal )` over three arguments, which MySQL rejects, so it
 * answers `[]` on every site (measured on WordPress 7.1 / MySQL 8.4).
 *
 * Reads only. Nothing here writes a row, and calling it twice returns the same
 * object.
 *
 * @since 4.3.0
 */
class GetAnalytics extends ProAbility {

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function ability_id() {
		return 'betterdocs-pro/get-analytics';
	}

	/**
	 * @since 4.3.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$state = $this->pro_state();

		// Unreachable while this file is loaded by an active Pro, but
		// `is_pro_active()` asks WordPress rather than the loader, and the two
		// can disagree for a request.
		if ( ProState::is_blocking( $state ) ) {
			return AbilityError::pro_required( $state, $this->feature );
		}

		$range = $this->range( $input );

		if ( is_wp_error( $range ) ) {
			return $range;
		}

		$per_page = isset( $input['per_page'] ) ? max( 1, min( 100, (int) $input['per_page'] ) ) : 10;

		$window = [];

		if ( null !== $range['start'] ) {
			$window['start_date'] = $range['start'];
		}

		if ( null !== $range['end'] ) {
			$window['end_date'] = $range['end'];
		}

		// No range means every day on record, not the dashboard's default month:
		// `bd-get-doc-analytics` answers all-time for an omitted range and the
		// two analytics tools must not disagree about what "no dates" means.
		if ( ! isset( $window['start_date'] ) || ! isset( $window['end_date'] ) ) {
			$window['days'] = 'all';
		}

		$paged = array_merge(
			$window,
			[
				'per_page' => $per_page,
				'page'     => 1
			]
		);

		$summary = $this->report( '/analytics/summary', $window );

		if ( is_wp_error( $summary ) ) {
			return $summary;
		}

		// One row is enough: only the KPI block is wanted, and the docs list this
		// route also returns is `leading_docs` under a different sort.
		$reactions = $this->report(
			'/analytics/reactions',
			array_merge(
				$window,
				[
					'per_page' => 1,
					'page'     => 1
				]
			)
		);

		if ( is_wp_error( $reactions ) ) {
			return $reactions;
		}

		$docs = $this->report( '/analytics/articles', $paged );

		if ( is_wp_error( $docs ) ) {
			return $docs;
		}

		$categories = $this->report( '/analytics/categories', $paged );

		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		$knowledge_bases = [ 'items' => [] ];

		// The knowledge-base breakdown is meaningless without Multiple Knowledge
		// Base: the taxonomy is not even registered, so the join returns nothing.
		if ( ! empty( $state['multiple_kb'] ) ) {
			$knowledge_bases = $this->report( '/analytics/knowledge-bases', $paged );

			if ( is_wp_error( $knowledge_bases ) ) {
				return $knowledge_bases;
			}
		}

		$searched = $this->report( '/analytics/search', array_merge( $paged, [ 'scope' => 'all' ] ) );

		if ( is_wp_error( $searched ) ) {
			return $searched;
		}

		$not_found = $this->report( '/analytics/search', array_merge( $paged, [ 'scope' => 'zero' ] ) );

		if ( is_wp_error( $not_found ) ) {
			return $not_found;
		}

		return [
			'range'                   => $range,
			'overview'                => $this->overview( $summary, $reactions ),
			'leading_docs'            => $this->docs( $docs ),
			'leading_categories'      => $this->terms( $categories ),
			'leading_knowledge_bases' => $this->terms( $knowledge_bases ),
			'search'                  => [
				'top'       => $this->keywords( $searched, 'searches' ),
				'not_found' => $this->keywords( $not_found, 'not_found' )
			]
		];
	}

	/**
	 * Call one Advanced Analytics read route.
	 *
	 * @since 4.3.0
	 *
	 * @param string $route  Route beneath `betterdocs/v1`.
	 * @param array  $params Query parameters.
	 * @return array|\WP_Error
	 */
	protected function report( $route, array $params ) {
		$data = $this->dispatch( 'GET', $route, $params );

		if ( is_wp_error( $data ) ) {
			return $this->map_report_error( $data );
		}

		return (array) $data;
	}

	/**
	 * Translate an analytics route refusal into the typed vocabulary.
	 *
	 * @since 4.3.0
	 *
	 * @param \WP_Error $error What the route returned.
	 * @return \WP_Error
	 */
	protected function map_report_error( \WP_Error $error ) {
		$code = $error->get_error_code();

		if ( 'rest_forbidden' === $code || 'rest_cannot_view' === $code ) {
			return AbilityError::capability_missing( $this->capability, $this->permission_phrase() );
		}

		return AbilityError::upstream( $error->get_error_message(), [ 'code' => (string) $code ] );
	}

	/**
	 * The `{start, end}` the caller asked for, validated.
	 *
	 * Both are null when omitted: "all time" and "an empty string" are different
	 * answers, and the schema types them `[string, null]`.
	 *
	 * @since 4.3.0
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	protected function range( array $input ) {
		$start = $this->date_input( $input, 'start_date' );

		if ( is_wp_error( $start ) ) {
			return $start;
		}

		$end = $this->date_input( $input, 'end_date' );

		if ( is_wp_error( $end ) ) {
			return $end;
		}

		if ( null !== $start && null !== $end && $start > $end ) {
			return AbilityError::invalid_input( 'start_date', __( 'start_date is after end_date.', 'betterdocs-pro' ) );
		}

		return [
			'start' => $start,
			'end'   => $end
		];
	}

	/**
	 * One `YYYY-MM-DD` input, or null when it was not sent.
	 *
	 * `checkdate()` as well as the pattern: `2026-02-30` matches the pattern and
	 * is not a day, and MySQL would compare it as a string and answer with a
	 * range nobody asked for.
	 *
	 * @since 4.3.0
	 *
	 * @param array  $input Validated input.
	 * @param string $field Field name.
	 * @return string|null|\WP_Error
	 */
	protected function date_input( array $input, $field ) {
		if ( ! isset( $input[ $field ] ) || '' === $input[ $field ] ) {
			return null;
		}

		$value = (string) $input[ $field ];

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts )
			|| ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			return AbilityError::invalid_input(
				$field,
				sprintf(
					/* translators: %s: field name. */
					__( '%s must be a real calendar date written as YYYY-MM-DD.', 'betterdocs-pro' ),
					$field
				)
			);
		}

		return $value;
	}

	/**
	 * The overview block, from the summary and reaction KPI sets.
	 *
	 * @since 4.3.0
	 *
	 * @param array $summary   `/analytics/summary` payload.
	 * @param array $reactions `/analytics/reactions` payload.
	 * @return array
	 */
	protected function overview( array $summary, array $reactions ) {
		$kpis     = isset( $summary['kpis'] ) ? (array) $summary['kpis'] : [];
		$feelings = isset( $reactions['kpis'] ) ? (array) $reactions['kpis'] : [];

		return [
			'views'              => $this->int( $kpis, 'total_views' ),
			'unique_views'       => $this->int( $kpis, 'total_unique_views' ),
			'reactions'          => [
				'happy'  => $this->int( $feelings, 'happy' ),
				'normal' => $this->int( $feelings, 'normal' ),
				'sad'    => $this->int( $feelings, 'sad' )
			],
			'searches'           => $this->int( $kpis, 'total_searches' ),
			'searches_not_found' => $this->int( $kpis, 'total_not_found' ),
			'ai_fetches'         => $this->int( $kpis, 'total_ai_fetches' )
		];
	}

	/**
	 * `leading_docs`, from `/analytics/articles`.
	 *
	 * @since 4.3.0
	 *
	 * @param array $payload Route payload.
	 * @return array[]
	 */
	protected function docs( array $payload ) {
		$out = [];

		foreach ( $this->items( $payload ) as $item ) {
			$out[] = [
				'id'           => $this->int( $item, 'post_id' ),
				'title'        => isset( $item['title'] ) ? (string) $item['title'] : '',
				'url'          => isset( $item['permalink'] ) ? (string) $item['permalink'] : '',
				'views'        => $this->int( $item, 'views' ),
				'unique_views' => $this->int( $item, 'unique_views' ),
				'reactions'    => $this->int( $item, 'reactions' )
			];
		}

		return $out;
	}

	/**
	 * `leading_categories` / `leading_knowledge_bases`, from the term breakdowns.
	 *
	 * @since 4.3.0
	 *
	 * @param array $payload Route payload.
	 * @return array[]
	 */
	protected function terms( array $payload ) {
		$out = [];

		foreach ( $this->items( $payload ) as $item ) {
			$out[] = [
				'id'           => $this->int( $item, 'term_id' ),
				'name'         => isset( $item['name'] ) ? (string) $item['name'] : '',
				'slug'         => isset( $item['slug'] ) ? (string) $item['slug'] : '',
				'url'          => isset( $item['link'] ) ? (string) $item['link'] : '',
				'views'        => $this->int( $item, 'views' ),
				'unique_views' => $this->int( $item, 'unique_views' )
			];
		}

		return $out;
	}

	/**
	 * A keyword list, from `/analytics/search`.
	 *
	 * @since 4.3.0
	 *
	 * @param array  $payload Route payload.
	 * @param string $field   Which count this list is ranked by.
	 * @return array[]
	 */
	protected function keywords( array $payload, $field ) {
		$out = [];

		foreach ( $this->items( $payload ) as $item ) {
			$out[] = [
				'keyword' => isset( $item['keyword'] ) ? (string) $item['keyword'] : '',
				'count'   => $this->int( $item, $field )
			];
		}

		return $out;
	}

	/**
	 * The `items` array of a paginated payload.
	 *
	 * @since 4.3.0
	 *
	 * @param array $payload Route payload.
	 * @return array[]
	 */
	protected function items( array $payload ) {
		if ( ! isset( $payload['items'] ) || ! is_array( $payload['items'] ) ) {
			return [];
		}

		$items = [];

		foreach ( $payload['items'] as $item ) {
			$items[] = (array) $item;
		}

		return $items;
	}

	/**
	 * One integer out of a payload, defaulting to 0.
	 *
	 * @since 4.3.0
	 *
	 * @param array  $data Payload.
	 * @param string $key  Key to read.
	 * @return int
	 */
	protected function int( array $data, $key ) {
		return isset( $data[ $key ] ) ? (int) $data[ $key ] : 0;
	}

	/**
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function permission_phrase() {
		return __( 'read site-wide analytics', 'betterdocs-pro' );
	}
}
