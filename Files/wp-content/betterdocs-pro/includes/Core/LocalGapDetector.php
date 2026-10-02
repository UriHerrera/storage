<?php

namespace WPDeveloper\BetterDocsPro\Core;

/**
 * On-site content gap detection — no cloud, no embeddings, no API key.
 *
 * The cloud detector (betterdocs-chat-ai) decides a gap on four things: demand,
 * coverage, cohesion, and the clustering that produces a topic in the first
 * place. Three of the four survive without embeddings:
 *
 *   demand      identical — the same search-volume SQL, the same log-scaled score
 *   clustering  IDF-weighted token overlap instead of HDBSCAN over vectors
 *   cohesion    mean pairwise token overlap instead of mean pairwise cosine
 *   coverage    a live search of the site instead of cosine-to-nearest-doc
 *
 * Coverage is the one worth explaining, because locally it is arguably the
 * stronger test. A `zero_result` signal is not an *estimate* that no document
 * covers a topic — it is the site's own search engine reporting that a real
 * reader got nothing. What that record lacks is freshness: a keyword that
 * returned nothing 60 days ago may have been answered by an article published
 * since, and without re-checking, a gap the team already closed would resurface
 * forever. So every candidate is re-searched at detection time and dropped if
 * the KB now answers it.
 *
 * What this cannot do, and what the UI says plainly rather than hiding: it
 * matches words, not meaning. "SSO login fails" and "SAML authentication error"
 * are one topic to an embedding and two topics here; an article that answers a
 * question without sharing its vocabulary reads as uncovered. That is the honest
 * difference the AI Chatbot add-on buys, and the reason the Gaps screen offers
 * it rather than pretending the two engines are equivalent.
 *
 * Results are written to the same {prefix}betterdocs_analytics_insights table in
 * the same payload shape as cloud gaps, with source = 'local', so the entire
 * existing UI — cards, status/dismiss, AI drafting, the overview's top action —
 * works unchanged.
 */
class LocalGapDetector {

	/**
	 * Grammar-only stoplist, used for clustering and for building the coverage
	 * search.
	 *
	 * Deliberately much smaller than ContentIntelligenceService's feedback
	 * stoplist. That one answers "does this comment name a subject at all?" and
	 * so strips sentiment and page-furniture words ('error', 'video', 'broken').
	 * Here those words are legitimate topic tokens — "webhook error" and "video
	 * upload error" are things somebody should document. Sharing one list would
	 * quietly reduce half the real keywords on a support KB to nothing.
	 */
	const GRAMMAR_STOP = [
		'a', 'an', 'the', 'this', 'that', 'these', 'those', 'it', 'its', 'is', 'was', 'be',
		'been', 'am', 'are', 'were', 'to', 'of', 'in', 'on', 'at', 'for', 'with', 'and',
		'or', 'but', 'not', 'no', 'so', 'if', 'as', 'by', 'from', 'you', 'your', 'we',
		'us', 'our', 'my', 'me', 'i', 'do', 'does', 'did', 'can', 'could', 'would',
		'should', 'will', 'have', 'has', 'had', 'here', 'there', 'when', 'what', 'how',
		'why', 'where', 'which', 'who', 'about', 'into', 'than', 'then', 'them', 'they',

		// Generic actions. These are the verbs readers wrap around every subject —
		// "cdn setup", "2fa setup", "dns setup" — and left in they are the single
		// most damaging token class here, because on a two-word keyword the shared
		// verb is half the vector. On the demo KB exactly that trio merged into one
		// "gap" spanning three unrelated subjects.
		//
		// IDF alone does not save it: a verb appearing in three of sixty signals
		// scores nearly as rare as the subject nouns beside it. What separates them
		// is not frequency but that the verb is never the thing an article would be
		// about, which is a judgement about vocabulary, so it belongs in a list.
		'setup', 'install', 'installing', 'installation', 'configure', 'configuring',
		'config', 'create', 'creating', 'enable', 'enabling', 'disable', 'disabling',
		'add', 'adding', 'remove', 'removing', 'delete', 'deleting', 'change',
		'changing', 'update', 'updating', 'use', 'using', 'make', 'set', 'fix',
		'fixing', 'help', 'guide', 'tutorial', 'tutorials', 'docs', 'doc',
		'documentation', 'example', 'examples', 'step', 'steps',
	];

	/** @var AnalyticsInsightStore */
	protected $store;

	/** @var array<string,int> memoised live-search counts for one detect() run */
	protected $coverage_cache = [];

	public function __construct( AnalyticsInsightStore $store = null ) {
		$this->store = $store ? $store : new AnalyticsInsightStore();
	}

	// -----------------------------------------------------------------------
	// Entry point
	// -----------------------------------------------------------------------

	/**
	 * Detect gaps from collected query signals and mirror them into the insights
	 * table (source = 'local'), pruning local rows that no longer appear.
	 *
	 * @param array $signals { text, intent, source, weight } from
	 *                       ContentIntelligenceService::collect_query_signals()
	 * @return int number of gaps stored
	 */
	public function run( array $signals ) {
		$gaps = $this->detect( $signals );

		$keep = [];
		foreach ( $gaps as $gap ) {
			// Same hash formula the cloud path uses, on purpose: a site that gains
			// or loses the chatbot keeps the accept/dismiss decisions it already
			// made about a topic instead of having every gap reappear as new.
			$hash   = md5( 'gap:' . implode( '|', $gap['queries'] ) );
			$keep[] = $hash;
			$this->store->upsert( 'gap', $hash, (float) $gap['demand'], $gap, [ 'source' => 'local' ] );
		}

		$this->prune( $keep );
		return count( $gaps );
	}

	/**
	 * Drop local 'new' gap rows that are no longer detected. Rows the user has
	 * acted on (accepted/dismissed/done) are left alone.
	 */
	protected function prune( array $keep_hashes ) {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics_insights';

		if ( empty( $keep_hashes ) ) {
			$wpdb->query( "DELETE FROM {$table} WHERE type = 'gap' AND source = 'local' AND status = 'new'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return;
		}
		$ph = implode( ',', array_fill( 0, count( $keep_hashes ), '%s' ) );
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE type = 'gap' AND source = 'local' AND status = 'new' AND object_hash NOT IN ( {$ph} )", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$keep_hashes
		) );
	}

	// -----------------------------------------------------------------------
	// Detection
	// -----------------------------------------------------------------------

	/**
	 * The full pipeline: tokenize → cluster → cohesion gate → coverage gate →
	 * score. Returns cloud-shaped gap payloads.
	 *
	 * @param array $signals
	 * @return array<int,array>
	 */
	public function detect( array $signals ) {
		$this->coverage_cache = [];

		$min_signals = (int) apply_filters( 'betterdocs_ci_local_min_cluster_size', 3 );
		$sim_bar     = (float) apply_filters( 'betterdocs_ci_local_similarity', 0.34 );
		$cohesion_bar= (float) apply_filters( 'betterdocs_ci_local_min_cohesion', 0.30 );
		$max_gaps    = (int) apply_filters( 'betterdocs_ci_local_max_gaps', 30 );

		// 1. Normalize. A signal with no topical token left cannot be clustered,
		//    cannot be searched for, and cannot be written about — drop it rather
		//    than let it drift to the centre of the space and bridge real topics.
		$items = [];
		foreach ( $signals as $sig ) {
			$text = isset( $sig['text'] ) ? trim( (string) $sig['text'] ) : '';
			if ( '' === $text ) {
				continue;
			}
			$tokens = $this->tokenize( $text );
			if ( empty( $tokens ) ) {
				continue;
			}
			$items[] = [
				'text'   => $text,
				'tokens' => $tokens,
				'intent' => isset( $sig['intent'] ) ? (string) $sig['intent'] : 'other',
				'weight' => max( 1, (int) ( $sig['weight'] ?? 1 ) ),
			];
		}
		if ( empty( $items ) ) {
			return [];
		}

		$idf   = $this->idf( $items );
		$total = 0;
		foreach ( $items as $it ) {
			$total += $it['weight'];
		}

		// 2. Cluster. Leader clustering, seeded highest-volume-first.
		//
		//    Not connected components: overlap is not transitive, and a chain of
		//    "password reset" → "reset email" → "email settings" merges two
		//    unrelated topics into one useless cluster. Seeding by weight means
		//    the loudest phrasing anchors the topic, which is also the phrasing
		//    the card should be titled with.
		$order = array_keys( $items );
		usort( $order, function ( $a, $b ) use ( $items ) {
			return $items[ $b ]['weight'] <=> $items[ $a ]['weight'];
		} );

		$assigned = [];
		$clusters = [];
		foreach ( $order as $seed ) {
			if ( isset( $assigned[ $seed ] ) ) {
				continue;
			}
			$members         = [ $seed ];
			$assigned[$seed] = true;
			foreach ( $order as $cand ) {
				if ( isset( $assigned[ $cand ] ) ) {
					continue;
				}
				if ( $this->similarity( $items[ $seed ]['tokens'], $items[ $cand ]['tokens'], $idf ) >= $sim_bar ) {
					$members[]        = $cand;
					$assigned[$cand]  = true;
				}
			}
			$clusters[] = $members;
		}

		// 3. Score each cluster. Below the size bar a cluster is not yet a topic,
		//    but its members may still qualify individually on volume alone.
		$promote_bar = max(
			(float) apply_filters( 'betterdocs_ci_local_singleton_min_weight', 5 ),
			0.05 * $total
		);

		$gaps  = [];
		$loose = [];
		foreach ( $clusters as $members ) {
			if ( count( $members ) < $min_signals ) {
				$loose = array_merge( $loose, $members );
				continue;
			}

			$cohesion = $this->cohesion( $members, $items, $idf );
			if ( $cohesion < $cohesion_bar ) {
				$loose = array_merge( $loose, $members );
				continue;
			}

			$gap = $this->build_gap( $members, $items, $cohesion, 'cluster' );
			if ( $gap ) {
				$gaps[] = $gap;
			}
		}

		foreach ( $loose as $m ) {
			if ( $items[ $m ]['weight'] < $promote_bar ) {
				continue;
			}
			$gap = $this->build_gap( [ $m ], $items, 1.0, 'singleton' );
			if ( $gap ) {
				$gaps[] = $gap;
			}
		}

		// 4. Demand, relative to the loudest gap this run and log-scaled — search
		//    volume is heavy-tailed, and a linear ratio against one runaway term
		//    flattens every other real gap to ~1 on the meter. Same formula as the
		//    cloud so the two engines produce meters that read alike.
		$max_volume = 1;
		foreach ( $gaps as $g ) {
			$max_volume = max( $max_volume, $g['search_volume'] );
		}
		$log_max = log( 1 + $max_volume );

		foreach ( $gaps as &$g ) {
			$g['demand'] = $log_max > 0
				? (int) min( 100, round( ( log( 1 + $g['search_volume'] ) / $log_max ) * 100 ) )
				: 0;

			// Confidence without a doc_distance to lean on. The cloud asks "is it
			// uncovered AND in demand"; the uncovered half is already a hard gate
			// here (the live search found nothing), so the second axis is instead
			// how many independent kinds of evidence point at the topic. A term
			// that shows up as a failed search AND in a written complaint is a
			// materially safer bet than one loud keyword.
			//
			// Corroboration means a topic is supported by more than one *kind* of
			// evidence, or by a volume nobody could call marginal. Counting cluster
			// members does not qualify: a cluster cannot exist below $min_signals,
			// so "at least three phrasings" is true of every cluster ever built and
			// grading on it made all six gaps on the demo KB High — which carries
			// exactly as much information as grading them all Low. Five is a real
			// threshold; three was a restatement of the entry requirement.
			//
			// Overwhelming volume corroborates on its own, though. Requiring a
			// second kind of evidence from the loudest term on the site gets it
			// backwards: 958 unanswered searches for one word is the most certain
			// finding here, and it was landing at Medium purely because nobody had
			// also complained about it in writing.
			$corroborated = count( $g['signals'] ) >= 2 || $g['signal_count'] >= 5 || $g['demand'] >= 85;

			// Demand is relative to the loudest gap in the same run and log-scaled,
			// which compresses hard: on the demo KB the *smallest* surviving gap
			// (36 searches against a top of 958) still scores 53. A bar of 50 on
			// that scale passes almost everything, so it sits higher here than the
			// cloud's — where a separate doc_distance axis does part of the work.
			$in_demand = $g['demand'] >= 65;
			$g['confidence'] = ( $corroborated && $in_demand )
				? 'High'
				: ( ( $corroborated || $in_demand ) ? 'Medium' : 'Low' );
		}
		unset( $g );

		usort( $gaps, function ( $a, $b ) {
			return $b['search_volume'] <=> $a['search_volume'];
		} );

		return array_slice( $gaps, 0, $max_gaps );
	}

	/**
	 * Assemble one gap payload, after the coverage gate.
	 *
	 * @return array|null null when the KB already answers the topic
	 */
	protected function build_gap( array $members, array $items, $cohesion, $kind ) {
		// Most-searched phrasing first: the card is titled with queries[0].
		usort( $members, function ( $a, $b ) use ( $items ) {
			return $items[ $b ]['weight'] <=> $items[ $a ]['weight'];
		} );

		$terms = $this->cluster_terms( $members, $items );
		if ( empty( $terms ) ) {
			return null;
		}

		$signals = [];
		$volume  = 0;
		foreach ( $members as $m ) {
			$intent             = $items[ $m ]['intent'] ?: 'other';
			$signals[ $intent ] = ( $signals[ $intent ] ?? 0 ) + $items[ $m ]['weight'];
			$volume            += $items[ $m ]['weight'];
		}

		// Coverage. A cluster built mostly from zero-result searches has to still
		// return nothing; one built from thin-result or feedback signals is judged
		// against the same thin-results bar those signals were collected under, so
		// the two halves of the pipeline agree on what "covered" means.
		$dominant = array_keys( $signals, max( $signals ) )[0];
		$allowed  = ( 'zero_result' === $dominant )
			? 0
			: (int) apply_filters( 'betterdocs_ci_low_yield_max_results', 2.0 );

		if ( $this->live_result_count( $terms ) > $allowed ) {
			return null; // an article answers this now — not a gap
		}

		$queries = [];
		foreach ( array_slice( $members, 0, 6 ) as $m ) {
			$queries[] = $items[ $m ]['text'];
		}

		return [
			'queries'       => $queries,
			'signal_count'  => count( $members ),
			'search_volume' => (int) $volume,
			'kind'          => $kind,
			'signals'       => $signals,
			// How this row was produced. The UI badges it, and it is the flag that
			// stops a lexical score being read as a semantic one.
			'method'        => 'lexical',
			// Semantic-only measures. Explicitly null rather than absent or faked:
			// there is no vector space here, so there is no cosine to a nearest doc
			// and no cosine cohesion. The UI hides them instead of printing a
			// number that would look comparable to the cloud's and is not.
			'doc_distance'  => null,
			'cohesion'      => null,
			// The lexical equivalent, under its own key so nothing can confuse the
			// two scales.
			'lexical_cohesion' => round( (float) $cohesion, 3 ),
			// What was actually searched for to decide coverage — the audit trail
			// for "why is this still listed when I wrote that article?".
			'coverage_terms'   => $terms,
		];
	}

	// -----------------------------------------------------------------------
	// Text
	// -----------------------------------------------------------------------

	/**
	 * Lowercase → split → drop grammar and 1–2 character noise → light singularize.
	 *
	 * The stemming is deliberately crude. "webhooks"/"webhook" and
	 * "categories"/"category" are the overwhelming majority of what a real KB
	 * search log needs collapsed; a full Porter stemmer costs far more and, on
	 * short search keywords, mostly produces stems no human recognises in the
	 * coverage search that follows.
	 *
	 * @return array<int,string>
	 */
	public function tokenize( $text ) {
		$stop  = (array) apply_filters( 'betterdocs_ci_local_stopwords', self::GRAMMAR_STOP );
		$words = preg_split( '/[^\p{L}\p{N}\-]+/u', mb_strtolower( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );

		$out = [];
		foreach ( (array) $words as $w ) {
			if ( mb_strlen( $w ) < 3 || in_array( $w, $stop, true ) ) {
				continue;
			}
			$out[ $this->singularize( $w ) ] = true;
		}
		return array_keys( $out );
	}

	protected function singularize( $word ) {
		$len = mb_strlen( $word );
		if ( $len > 4 && 'ies' === mb_substr( $word, -3 ) ) {
			return mb_substr( $word, 0, -3 ) . 'y';
		}
		if ( $len > 4 && ( 'ses' === mb_substr( $word, -3 ) || 'xes' === mb_substr( $word, -3 ) || 'hes' === mb_substr( $word, -3 ) ) ) {
			return mb_substr( $word, 0, -2 );
		}
		if ( $len > 3 && 's' === mb_substr( $word, -1 ) && 'ss' !== mb_substr( $word, -2 ) ) {
			return mb_substr( $word, 0, -1 );
		}
		return $word;
	}

	/**
	 * Inverse document frequency across the signal set.
	 *
	 * Without it every shared word counts the same, and on a KB search log the
	 * most-shared words are the least informative ones — on a hosting KB, half
	 * the queries contain "wordpress". Two questions about entirely different
	 * subjects would then look similar because both mention the product. IDF
	 * makes the rare, subject-bearing tokens carry the match.
	 *
	 * @return array<string,float>
	 */
	protected function idf( array $items ) {
		$n  = max( 1, count( $items ) );
		$df = [];
		foreach ( $items as $it ) {
			foreach ( $it['tokens'] as $t ) {
				$df[ $t ] = ( $df[ $t ] ?? 0 ) + 1;
			}
		}
		$idf = [];
		foreach ( $df as $t => $c ) {
			// +1 inside the log keeps a token present in every signal at a small
			// positive weight rather than exactly zero, so two signals sharing only
			// ubiquitous words still score above two sharing nothing at all.
			$idf[ $t ] = log( 1 + ( $n / $c ) );
		}
		return $idf;
	}

	/**
	 * IDF-weighted cosine over the two token sets — 0.0 (nothing in common) to
	 * 1.0 (identical token sets).
	 */
	protected function similarity( array $a, array $b, array $idf ) {
		if ( empty( $a ) || empty( $b ) ) {
			return 0.0;
		}
		$shared = 0.0;
		$lookup = array_flip( $b );
		foreach ( $a as $t ) {
			if ( isset( $lookup[ $t ] ) ) {
				$shared += ( $idf[ $t ] ?? 0.0 );
			}
		}
		if ( $shared <= 0 ) {
			return 0.0;
		}
		$na = 0.0;
		foreach ( $a as $t ) {
			$na += ( $idf[ $t ] ?? 0.0 );
		}
		$nb = 0.0;
		foreach ( $b as $t ) {
			$nb += ( $idf[ $t ] ?? 0.0 );
		}
		$denom = sqrt( $na * $nb );
		return $denom > 0 ? min( 1.0, $shared / $denom ) : 0.0;
	}

	/**
	 * Mean pairwise similarity inside a cluster — the lexical analogue of the
	 * cloud's cosine cohesion, and the gate that stops a bag of loosely related
	 * keywords being presented as one topic.
	 */
	protected function cohesion( array $members, array $items, array $idf ) {
		$n = count( $members );
		if ( $n < 2 ) {
			return 1.0;
		}
		$sum   = 0.0;
		$pairs = 0;
		for ( $i = 0; $i < $n; $i++ ) {
			for ( $j = $i + 1; $j < $n; $j++ ) {
				$sum += $this->similarity( $items[ $members[ $i ] ]['tokens'], $items[ $members[ $j ] ]['tokens'], $idf );
				$pairs++;
			}
		}
		return $pairs > 0 ? $sum / $pairs : 1.0;
	}

	/**
	 * The tokens that define a cluster, most-shared first — what gets searched
	 * for in the coverage test.
	 *
	 * Capped at four: a coverage search AND-matches its terms, so throwing every
	 * token of every member at it guarantees zero results and would wave through
	 * gaps the KB genuinely covers.
	 *
	 * @return array<int,string>
	 */
	protected function cluster_terms( array $members, array $items ) {
		$freq = [];
		foreach ( $members as $m ) {
			foreach ( $items[ $m ]['tokens'] as $t ) {
				$freq[ $t ] = ( $freq[ $t ] ?? 0 ) + 1;
			}
		}
		if ( empty( $freq ) ) {
			return [];
		}
		arsort( $freq );
		$max = (int) apply_filters( 'betterdocs_ci_local_coverage_terms', 4 );
		return array_slice( array_keys( $freq ), 0, $max );
	}

	// -----------------------------------------------------------------------
	// Coverage
	// -----------------------------------------------------------------------

	/**
	 * How many published docs the site returns for these terms, right now.
	 *
	 * `betterdocs_bypass_restrictions` is not optional. ContentRestrictions
	 * filters `pre_get_posts` for the current user, and this runs on cron as
	 * nobody — without the bypass the query returns 0 for every term on a site
	 * using restrictions, every cluster passes the coverage gate, and the screen
	 * fills with gaps for articles that exist.
	 *
	 * @param array $terms
	 * @return int
	 */
	protected function live_result_count( array $terms ) {
		if ( empty( $terms ) ) {
			// No searchable term means no evidence either way. Treated as covered
			// so an unusable cluster is dropped rather than reported.
			return PHP_INT_MAX;
		}

		$key = implode( ' ', $terms );
		if ( isset( $this->coverage_cache[ $key ] ) ) {
			return $this->coverage_cache[ $key ];
		}

		$q = new \WP_Query( [
			'post_type'              => 'docs',
			'post_status'            => 'publish',
			's'                      => $key,
			// AND across terms, not phrase matching: the terms come from different
			// members of the cluster and rarely appear as a literal phrase, but a
			// doc that mentions all of them is genuinely about the topic.
			'sentence'               => false,
			'posts_per_page'         => 3,
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'betterdocs_bypass_restrictions' => true,
		] );

		$count = (int) $q->found_posts;
		wp_reset_postdata();

		$this->coverage_cache[ $key ] = $count;
		return $count;
	}
}
