<?php
/**
 * Pro abilities registrar.
 *
 * @package BetterDocsPro
 * @since   4.3.0
 */

namespace WPDeveloper\BetterDocsPro\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPDeveloper\BetterDocsPro\Abilities\Analytics\GetAnalytics;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\CreateApiReference;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\DeleteApiReference;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\GetApiReference;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\IngestApiSpec;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\ListApiReferences;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\MaterializeApiReference;
use WPDeveloper\BetterDocsPro\Abilities\ApiDocs\UpdateApiReference;
use WPDeveloper\BetterDocsPro\Abilities\Git\GetGitSyncStatus;
use WPDeveloper\BetterDocsPro\Abilities\Insights\GetSearchInsights;
use WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base\CreateKnowledgeBase;
use WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base\DeleteKnowledgeBase;
use WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base\ListKnowledgeBases;
use WPDeveloper\BetterDocsPro\Abilities\Knowledge_Base\UpdateKnowledgeBase;
use WPDeveloper\BetterDocsPro\Abilities\RelatedDocs\GetRelatedDocs;

/**
 * Hands Pro's abilities to the Free plugin's registrar.
 *
 * Pro never talks to the WordPress Abilities API itself: it registers no
 * category, no ability, no REST route and no MCP endpoint. It appends instances
 * to Free's `betterdocs_register_abilities` filter, and Free re-keys the list by
 * ability id — so these five **replace** the placeholders Free registered for
 * them (ADR-008). One server, one registry, and a tool name a client learned on
 * a Free site keeps working after Pro is activated, with no re-registration.
 *
 * @since 4.3.0
 */
final class Registrar {

	/**
	 * Hooks the filter.
	 *
	 * Free applies it from `AbilitiesRegistrar::build_abilities()`, which runs
	 * on `wp_abilities_api_init` — an action both WordPress core and the bundled
	 * Abilities API fire lazily, the first time anything reads the registry, at
	 * or after `init`. Pro is resolved from `Plugin::initialize()`, which runs on
	 * `betterdocs_init` at the end of Free's own `init` callback (priority 0), so
	 * the filter is in place well before the first read.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		add_filter( 'betterdocs_register_abilities', [ $this, 'register' ] );
	}

	/**
	 * Append Pro's abilities to the list Free is about to register.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $abilities Abilities collected so far, keyed by id.
	 * @return mixed
	 */
	public function register( $abilities ) {
		if ( ! is_array( $abilities ) ) {
			$abilities = [];
		}

		if ( ! $this->free_supports_abilities() ) {
			return $abilities;
		}

		foreach ( $this->abilities() as $ability ) {
			$abilities[ $ability->get_id() ] = $ability;
		}

		return $abilities;
	}

	/**
	 * Whether the Free plugin on this site has the abilities layer these classes
	 * are built on.
	 *
	 * Checked before anything is instantiated, because instantiating is what
	 * autoloads a class that `extends` Free's base — on an older Free that is a
	 * fatal, not a missing feature. `::class` resolves at compile time and
	 * triggers no autoload, so the guard itself is free.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	protected function free_supports_abilities() {
		return class_exists( \WPDeveloper\BetterDocs\Abilities\AbilityBase::class )
			&& class_exists( \WPDeveloper\BetterDocs\Abilities\ProStubs::class );
	}

	/**
	 * The Pro ability instances, in catalog order.
	 *
	 * @since 4.3.0
	 *
	 * @return \WPDeveloper\BetterDocs\Abilities\AbilityBase[]
	 */
	protected function abilities() {
		return [
			new CreateKnowledgeBase(),
			new UpdateKnowledgeBase(),
			new DeleteKnowledgeBase(),
			new ListKnowledgeBases(),
			new GetAnalytics(),
			new ListApiReferences(),
			new GetApiReference(),
			new CreateApiReference(),
			new UpdateApiReference(),
			new DeleteApiReference(),
			new IngestApiSpec(),
			new MaterializeApiReference(),
			new GetSearchInsights(),
			new GetGitSyncStatus(),
			new GetRelatedDocs()
		];
	}
}
