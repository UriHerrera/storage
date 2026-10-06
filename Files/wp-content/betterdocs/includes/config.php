<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Utils\Views;
use WPDeveloper\BetterDocs\Utils\Enqueue;
use WPDeveloper\BetterDocs\Editors\Editor;
use WPDeveloper\BetterDocs\Editors\Elementor;
use WPDeveloper\BetterDocs\Editors\BlockEditor;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\AI\ProviderFactory;
return [
	Enqueue::class => new Enqueue( BETTERDOCS_ABSURL, BETTERDOCS_ABSPATH, BETTERDOCS_VERSION ),
	// Resolves the active AI platform (OpenAI / Gemini / Claude / DeepSeek / OpenRouter).
	// Pro / add-ons can swap providers via the `betterdocs_ai_providers` filter, or
	// replace the whole factory via the `betterdocs_container_config` filter.
	ProviderFactory::class => function ( $container ) {
		return new ProviderFactory( $container->get( Settings::class ) );
	},
	Views::class   => function ( $container ) {
		return new Views( BETTERDOCS_ABSPATH . 'views/', $container );
	},
	Editor::class  => function ( $container ) {
		return new Editor(
			$container,
			[
				'elementor'   => Elementor::class,
				'blockEditor' => BlockEditor::class
			]
		);
	},
];
