<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Admin\Builder\Rules;
use WPDeveloper\BetterDocs\Admin\Builder\GlobalFields;

class SetupWizard extends Base {
	private $settings;
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue( $hook ) {
		if ( $hook !== 'betterdocs_page_betterdocs-setup' ) {
			return;
		}

		betterdocs()->assets->enqueue( 'betterdocs-quick-setup', 'admin/js/quick-setup.js' );
		betterdocs()->assets->localize( 'betterdocs-quick-setup', 'betterdocsQuickSetup', GlobalFields::normalize( $this->quickbuilder_setup() ) );
		betterdocs()->assets->localize(
			'betterdocs-quick-setup',
			'betterdocsSampleDocs',
			[
				'enabled'   => (bool) $this->settings->get( 'enable_ai_sample_docs', true ),
				'rest_url'  => esc_url_raw( rest_url() ),
				'rest_base' => esc_url_raw( get_rest_url( null, '/betterdocs/v1/sample-docs' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			]
		);
		// The layout picker writes Customizer theme_mods, which a block (FSE) theme never
		// reads — so on FSE we don't offer the picker at all (and don't spend the work
		// building its groups). The Customize step shows the same "design it in the Site
		// Editor" card the Settings > Design tab already shows there instead.
		$is_fse = (bool) betterdocs()->helper->current_theme_is_fse_theme();

		betterdocs()->assets->localize(
			'betterdocs-quick-setup',
			'betterdocsCustomizeLayouts',
			[
				'rest_url'  => esc_url_raw( rest_url() ),
				'rest_base' => esc_url_raw( get_rest_url( null, '/betterdocs/v1/customize' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'is_fse'    => $is_fse,
				'groups'    => $is_fse ? [] : $this->customize_layout_groups(),
				'fse'       => [
					'eyebrow'     => __( 'Full Site Editing', 'betterdocs' ),
					'title'       => __( 'Design with the Gutenberg Site Editor', 'betterdocs' ),
					'description' => __( 'BetterDocs ships editable templates and ready-made patterns for your documentation. Open the Site Editor to design them block by block — colors, typography and layout included.', 'betterdocs' ),
					'features'    => [
						__( 'Docs archive & category templates', 'betterdocs' ),
						__( 'Single doc layout', 'betterdocs' ),
						__( 'Ready-made BetterDocs patterns', 'betterdocs' ),
					],
					'button'      => __( 'Design with Gutenberg', 'betterdocs' ),
					'note'        => __( 'Opens in a new tab, so you can finish setup here.', 'betterdocs' ),
					// Opens the Site Editor's template list filtered to BetterDocs, rather than
					// dropping the user straight into one template (what Settings::gutenberg_link()
					// does) — from the wizard they've not chosen a template to edit yet.
					// Built by concatenation on purpose: add_query_arg() would re-encode the %2F.
					'url'         => admin_url( 'site-editor.php?p=%2Ftemplate&activeView=BetterDocs' ),
					'image'       => betterdocs()->assets->icon( 'customizer/gutenberg-preview.png', true ),
				],
			]
		);
		betterdocs()->assets->enqueue( 'betterdocs-sweetalert', 'vendor/js/sweetalert.min.js', [] );
		betterdocs()->assets->enqueue( 'betterdocs-icons', 'admin/btd-icon/style.css' );
		betterdocs()->assets->enqueue( 'betterdocs-setup-wizard-qb-css', 'admin/css/quick-setup.css' );
		betterdocs()->assets->enqueue( 'betterdocs-setup-wizard-new-css', 'admin/css/setup-wizard.css' );
		betterdocs()->assets->enqueue( 'betterdocs-setup-wizard-default-js', 'admin/js/setup-wizard.js', [ 'jquery', 'betterdocs-sweetalert' ] );

		// Localize the script with new data
		betterdocs()->assets->localize(
			'betterdocs-setup-wizard',
			'bdquicksetup',
			[
				'finish_txt'    => __( 'Finish', 'betterdocs' ),
				'next_txt'      => __( 'Next', 'betterdocs' ),
				'customizerurl' => $this->customizer_settings_url(),
				'docspageurl'   => $this->docs_page_url(),
				'currentslug'   => $this->settings->get( 'docs_slug' ),
				'redirecturl'   => admin_url( '/admin.php?page=betterdocs-settings' )
			]
		);
	}

	/**
	 * Layout-picker groups for the interactive "Customize" step. Each group's
	 * `current` reflects the saved theme_mod; only the free layouts are offered.
	 *
	 * @return array
	 */
	private function customize_layout_groups() {
		$build = function ( $key, $default, $dir, $title, $hint, $cols, array $options ) {
			$out = [];
			foreach ( $options as $value => $label ) {
				$out[] = [
					'value' => $value,
					'label' => $label,
					'image' => betterdocs()->assets->icon( "customizer/{$dir}/{$value}.png", true ),
				];
			}
			return [
				'title'   => $title,
				'hint'    => $hint,
				'cols'    => $cols,
				'key'     => $key,
				'current' => get_theme_mod( $key, $default ),
				'options' => $out,
			];
		};

		return [
			'docs'     => $build(
				'betterdocs_docs_layout_select',
				'layout-7',
				'docs-page',
				__( 'Docs Page', 'betterdocs' ),
				__( 'The main knowledge-base landing page.', 'betterdocs' ),
				4,
				[
					'layout-7' => __( 'Sleek', 'betterdocs' ),
					'layout-1' => __( 'Grid', 'betterdocs' ),
					'layout-8' => __( 'Slate', 'betterdocs' ),
					'layout-5' => __( 'Classic', 'betterdocs' ),
				]
			),
			'category' => $build(
				'betterdocs_archive_layout_select',
				'layout-7',
				'archive',
				__( 'Category Page', 'betterdocs' ),
				__( "Where a single category's articles live.", 'betterdocs' ),
				4,
				[
					'layout-7' => __( 'Sleek', 'betterdocs' ),
					'layout-1' => __( 'Classic', 'betterdocs' ),
					'layout-8' => __( 'Slate', 'betterdocs' ),
					'layout-4' => __( 'Abstract', 'betterdocs' ),
				]
			),
			'single'   => $build(
				'betterdocs_single_layout_select',
				'layout-8',
				'single',
				__( 'Single Doc', 'betterdocs' ),
				__( 'An individual article layout.', 'betterdocs' ),
				4,
				[
					'layout-8'  => __( 'Essence', 'betterdocs' ),
					'layout-9'  => __( 'Rustic', 'betterdocs' ),
					'layout-10' => __( 'Slate', 'betterdocs' ),
					'layout-1'  => __( 'Classic', 'betterdocs' ),
				]
			),
			'search'   => $build(
				'betterdocs_search_layout_select',
				'layout-2',
				'search',
				__( 'Search', 'betterdocs' ),
				__( 'How visitors search your docs.', 'betterdocs' ),
				4,
				[
					'layout-2' => __( 'Modal', 'betterdocs' ),
					'layout-1' => __( 'Classic', 'betterdocs' ),
				]
			),
		];
	}

	public function normalize_options( $options ) {
		return GlobalFields::normalize_fields( $options );
	}

	public function get_pages() {
		$_pages = betterdocs()->query->get_posts(
			[
				'post_type'      => 'page',
				'numberposts'    => -1,
				'post_status'    => 'publish',
				'posts_per_page' => -1
			]
		);

		$__pages = [];

		if ( ! empty( $_pages ) ) {
			$__pages[0] = __( 'Select a Page', 'betterdocs' );
			foreach ( $_pages->posts as $page ) {
				$__pages[ $page->ID ] = esc_html( $page->post_title );
			}
		}

		return $__pages;
	}

	public function quickbuilder_setup() {
		$existing_plugins = betterdocs()->kbmigration->knowledge_base_plugins();
		$quick_setup      = [
			'id'            => 'betterdocs_quick_setup_metabox_wrapper',
			'title'         => __( 'Betterdocs Quick Setup', 'betterdocs' ),
			'object_types'  => [ 'betterdocs' ],
			'context'       => 'normal',
			'priority'      => 'high',
			'show_header'   => false,
			'tab_number'    => true,
			'is_pro_active' => betterdocs()->is_pro_active(),
			'logoURL'       => betterdocs()->assets->icon( 'betterdocs-icon.svg', true ),
			'layout'        => 'vertical',
			'values'        => betterdocs()->is_pro_active() ? array_merge( betterdocs()->settings->get_all(), [ 'enable_credit' => true ] ) : array_merge( betterdocs()->settings->get_all(), [ 'enable_credit' => true ], $this->pro_settings_default_values() ),
			'config'        => [
				'save_locally'    => true,
				'save'            => true,
				'active'          => 'getting-started',
				'sidebar'         => false,
				'title'           => false,
				'tab_number'      => true,
				'clickable'       => true,
				'completionTrack' => true,
				'content_heading' => [],
				'step'            => [
					'show'    => true,
					'buttons' => [
						'skip'                  => __( 'Skip for now', 'betterdocs' ),
						'prev'                  => [
							'name'       => __( 'Back', 'betterdocs' ),
							'type'       => 'customize',
							'customName' => __( 'Back', 'betterdocs' ),
							'condition'  => 'getting-started',
						],
						'start'                 => [
							'name'       => 'Start',
							'type'       => 'customize',
							'customName' => __( 'Proceed to Next Step', 'betterdocs' ),
							'condition'  => 'getting-started',
							'ajax'       => [
								'on'       => 'click',
								'api'      => '/betterdocs/v1/plugin_insights',
								'data'     => [
									'product_list' => 'betterdocs'
								],
								'hideSwal' => true
							]
						],
						'next'                  => [
							'name'       => 'Next',
							'type'       => 'customize',
							'customName' => __( 'Save & Continue', 'betterdocs' ),
							'condition'  => 'getting-started',
						]
					],
					// Hide the footer step bar on the first step (its CTA is in
					// content via showSteps) and on the last step (Finalize's
					// Finish CTA is in content too, so a lone Back button looked
					// off). Back-navigation there is still available via the tabs.
					'rules'   => Rules::logicalRule(
						[
							Rules::is( 'config.active', 'getting-started', true ),
							Rules::is( 'config.active', 'finalize', true ),
						],
						'and'
					)
				]
			],
			'submit'        => [
				'show' => false
			],
			'tabs'          => apply_filters(
				'betterdocs_quick_setup_tabs',
				[
					'getting-started' => apply_filters(
						'betterdocs_quick_setup_tab_getting_started',
						[
							'id'       => 'getting-started',
							'label'    => __( 'Getting Started', 'betterdocs' ),
							'classes'  => 'getting-started',
							'priority' => 10,
							'fields'   => [
								'getting_started_header' => [
									'name'        => 'getting_started_header',
									'type'        => 'header',
									'title'       => __( 'Quick Launch', 'betterdocs' ),
									'direction'   => 'column',
									'description' => __( 'Start your Knowledge Base configuration process with an easy-to-follow setup wizard.', 'betterdocs' ),
									'icon'        => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg>',
									'priority'    => 1
								],
								'betterdocs-quick-setup-start' => [
									'name'      => 'betterdocs-quick-setup-start',
									'type'      => 'section',
									'priority'  => 2,
									'showSteps' => true,
									'fields'    => [
										'betterdocs-quick-setup-start-collapse' => [
											'name'     => 'betterdocs-quick-setup-start-collapse',
											'type'     => 'collapse',
											'priority' => 2,
											'label'    => __( 'By clicking this button, you are allowing this app to collect your information.', 'betterdocs' ),
											'collapse_title' => __( 'What do we collect?', 'betterdocs' ),
											'collapse_message' => __( 'We collect non-sensitive diagnostic data and plugin usage information. Your site URL, WordPress & PHP version, plugins & themes and email address to send you the discount coupon. This data lets us make sure this plugin always stays compatible with the most popular plugins and themes. No spam, we promise.', 'betterdocs' )
										]
									]
								]
							]
						]
					),

					'setup-page'      => apply_filters(
						'betterdocs_quick_setup_tab_setup_page',
						[
							'id'       => 'setup-page',
							'label'    => __( 'Setup Page', 'betterdocs' ),
							'classes'  => 'setup-page',
							'priority' => 30,
							'fields'   => [
								'setup_page_header' => [
									'name'        => 'setup_page_header',
									'type'        => 'header',
									'title'       => __( 'Page Setup Magic', 'betterdocs' ),
									'direction'   => 'row',
									'description' => __( 'Configure the structure and layout of your documentation pages to match your preferences for an organized Knowledge Base.', 'betterdocs' ),
									'icon'        => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M15 8a1 1 0 0 1-1-1V2a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8z"></path><path d="M20 8v12a2 2 0 0 1-2 2h-4.182"></path><path d="m3.305 19.53.923-.382"></path><path d="M4 10.592V4a2 2 0 0 1 2-2h8"></path><path d="m4.228 16.852-.924-.383"></path><path d="m5.852 15.228-.383-.923"></path><path d="m5.852 20.772-.383.924"></path><path d="m8.148 15.228.383-.923"></path><path d="m8.53 21.696-.382-.924"></path><path d="m9.773 16.852.922-.383"></path><path d="m9.773 19.148.922.383"></path><circle cx="7" cy="18" r="3"></circle></svg>',
									'priority'    => 1
								],
								'betterdocs-quick-setup-fields' => [
									'name'     => 'betterdocs-quick-setup-fields',
									'type'     => 'section',
									'priority' => 2,
									'fields'   => [
										'builtin_doc_page' => [
											'name'     => 'builtin_doc_page',
											'type'     => 'toggle',
											'label'    => __( 'Built-in Documentation Page', 'betterdocs' ),
											'enable_disable_text_active' => true,
											'default'  => 1,
											'priority' => 1,
											'label_subtitle' => sprintf(
												/* translators: %s: example knowledge base URL */
												__( 'If you disable root slug for KB Archives, your individual knowledge base URL will be like this: %s', 'betterdocs' ),
												'https://example.com/knowledgebase-1'
											)
										],
										'docs_page'        => [
											'name'     => 'docs_page',
											'label'    => __( 'Docs Page', 'betterdocs' ),
											'type'     => 'select',
											'default'  => 0,
											'priority' => 2,
											'search'   => true,
											'options'  => $this->normalize_options( $this->get_pages() ),
											'label_subtitle' => __( 'You will need to insert BetterDocs Shortcode inside the page. This page will be used as docs permalink.', 'betterdocs' ),
											'rules'    => Rules::is( 'builtin_doc_page', false )
										],
										'breadcrumb_doc_title' => [
											'name'     => 'breadcrumb_doc_title',
											'type'     => 'text',
											'label'    => __( 'Documentation Page Title', 'betterdocs' ),
											'default'  => __( 'Docs', 'betterdocs' ),
											'priority' => 3,
											'rules'    => Rules::is( 'builtin_doc_page', true )
										],
										'docs_slug'        => [
											'name'     => 'docs_slug',
											'type'     => 'text',
											'label'    => __( 'BetterDocs Root Slug', 'betterdocs' ),
											'default'  => 'docs',
											'priority' => 4,
											'rules'    => Rules::is( 'builtin_doc_page', true )
										],
										'permalink_structure' => [
											'name'     => 'permalink_structure',
											'type'     => 'permalink_structure',
											'label'    => __( 'Single Docs Permalink', 'betterdocs' ),
											'default'  => PostType::permalink_structure(),
											'priority' => 4,
											'tags'     => $this->normalize_options(
												[
													'%doc_category%'   => '%doc_category%',
													'%knowledge_base%' => '%knowledge_base%'
												]
											),
											'label_subtitle' => __( 'Make sure to keep Docs Root Slug in the Single Docs Permalink. You are not able to keep it blank. You can use the available tags from below.', 'betterdocs' )
										],

										'enable_glossaries' => [
											'name'     => 'enable_glossaries',
											'type'     => 'toggle',
											'label'    => __( 'Show Glossary', 'betterdocs' ),
											'label_subtitle' => __( 'Enable the glossary feature to allow users to look up definitions for terms used within your encyclopedia or glossaries themselves.', 'betterdocs' ),
											'enable_disable_text_active' => true,
											'default'  => false,
											'priority' => 5,
											'is_pro'   => true
										],
										'enable_encyclopedia' => [
											'name'     => 'enable_encyclopedia',
											'type'     => 'toggle',
											'label'    => __( 'Built-in Encyclopedia Page', 'betterdocs' ),
											'enable_disable_text_active' => true,
											'default'  => false,
											'priority' => 6,
											'is_pro'   => true
										],
										'enable_credit'    => [
											'name'     => 'enable_credit',
											'type'     => 'toggle',
											'label'    => __( 'Show Powered by BetterDocs', 'betterdocs' ),
											'enable_disable_text_active' => true,
											'default'  => true,
											'priority' => 7
										],
										'enable_faq_schema' => [
											'name'     => 'enable_faq_schema',
											'type'     => 'toggle',
											'label'    => __( 'FAQ Schema', 'betterdocs' ),
											'enable_disable_text_active' => true,
											'default'  => '',
											'priority' => 8
										],
										'advance_search'   => apply_filters(
											'betterdocs_advance_search_settings',
											[
												'name'     => 'advance_search',
												'type'     => 'toggle',
												'label'    => __( 'Advanced Search', 'betterdocs' ),
												'enable_disable_text_active' => true,
												'default'  => true,
												'priority' => 9,
												'is_pro'   => true
											]
										),
										'enable_disable'   => apply_filters(
											'betterdocs_setup_wizard_instant_answer',
											[
												'name'     => 'enable_disable',
												'type'     => 'toggle',
												'priority' => 10,
												'label'    => __( 'Instant Answer', 'betterdocs' ),
												'enable_disable_text_active' => true,
												'default'  => false,
												'is_pro'   => true
											]
										),
									]
								]
							]
						]
					),
					'create-content'  => apply_filters(
						'betterdocs_quick_setup_tab_create-content',
						[
							'id'       => 'create-content',
							'label'    => __( 'Create Content', 'betterdocs' ),
							'classes'  => 'create-content',
							'priority' => 40,
							'fields'   => [
								'create_content_header' => [
									'name'        => 'create_content_header',
									'type'        => 'header',
									'title'       => __( 'Content Crafting', 'betterdocs' ),
									'direction'   => 'row',
									'description' => __( 'Craft categories & articles for your Knowledge Base to efficiently organize and manage your repository with respective categories.', 'betterdocs' ),
									'icon'        => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4a1 1 0 0 1 1-1h9l5 5v3"></path><path d="M4 4v15a1 1 0 0 0 1 1h6"></path><path d="M14 3v5h5"></path><path d="M18.5 13.5a2.1 2.1 0 0 1 3 3L16 22l-4 1 1-4z"></path></svg>',
									'priority'    => 1
								],
								'create_content_generator' => [
									'name'     => 'create_content_generator',
									'type'     => 'sample_docs_generator',
									'priority' => 2
								]
							]
						]
					),
					'customize'       => apply_filters(
						'betterdocs_quick_setup_tab_customize',
						[
							'id'       => 'customize',
							// A block theme has no Customizer layouts to pick, so the step isn't
							// "Customize" there — it hands off to the Site Editor. (The tab id stays
							// `customize`: it keys the step's navigation and styling.)
							'label'    => betterdocs()->helper->current_theme_is_fse_theme()
								? __( 'Design', 'betterdocs' )
								: __( 'Customize', 'betterdocs' ),
							'classes'  => 'customize',
							'priority' => 50,
							'fields'   => [
								'customize_header' => [
									'name'        => 'customize_header',
									'type'        => 'header',
									'title'       => betterdocs()->helper->current_theme_is_fse_theme()
										? __( 'Design Your Documentation', 'betterdocs' )
										: __( 'Style Your Documentation', 'betterdocs' ),
									'direction'   => 'row',
									// On a block theme the layout picker is replaced by the Site Editor
									// hand-off, so the copy leads with what the user CAN do rather than
									// naming a Customizer they will never open.
									'description' => betterdocs()->helper->current_theme_is_fse_theme()
										? __( 'Your theme supports Full Site Editing. Design your docs archive, single doc pages and patterns right in the Gutenberg Site Editor.', 'betterdocs' )
										: __( 'Pick a layout for your docs page, categories, single articles, and search — applied when you finish setup. Fine-tune the details anytime in the Customizer.', 'betterdocs' ),
									'icon'        => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"></rect><path d="M3 9h18M9 9v11"></path></svg>',
									'priority'    => 1
								],
								'customize_layouts' => [
									'name'     => 'customize_layouts',
									'type'     => 'layout_customizer',
									'priority' => 2
								]
							]
						]
					),
					'finalize'        => apply_filters(
						'betterdocs_quick_setup_tab_finalize',
						[
							'id'       => 'finalize',
							'label'    => __( 'Finalize', 'betterdocs' ),
							'classes'  => 'finalize',
							'priority' => 60,
							'fields'   => [
								'finalize_header' => [
									'name'        => 'finalize_header',
									'type'        => 'header',
									'title'       => __( 'One last step', 'betterdocs' ),
									'direction'   => 'row',
									'description' => __( 'Review your setup below. When you\'re ready, finish to save your settings and apply BetterDocs across your site — you can change anything later in Settings.', 'betterdocs' ),
									'icon'        => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 2 2 4-4"></path><path d="m3 7 2 2 4-4"></path><path d="M13 6h8"></path><path d="M13 12h8"></path><path d="M13 18h8"></path></svg>',
									'priority'    => 1
								],
								'finalize_finish' => [
									'name'     => 'finalize_finish',
									'type'     => 'finish_panel',
									'priority' => 2
								]
							]
						]
					)
				]
			)
		];

		if ( $existing_plugins ) {
			$quick_setup['tabs']['migration'] = apply_filters(
				'betterdocs_quick_setup_tab_migration',
				[
					'id'       => 'migration',
					'label'    => __( 'Migration', 'betterdocs' ),
					'classes'  => 'migration',
					'priority' => 20,
					'fields'   => [
						'migration_header'               => [
							'name'        => 'migration_header',
							'type'        => 'header',
							'title'       => __( 'Migration', 'betterdocs' ),
							'direction'   => 'column',
							'description' => __( 'We detected another Knoledge Base Plugin installed in this site. For BetterDocs to work efficiently, we will migrate the data from the plugin listed below, and deactivate the plugins, to avoid conflict.', 'betterdocs' ),
							'icon'        => '<img src="' . betterdocs()->assets->icon( 'icons/migration.svg', true ) . '"/>',
							'priority'    => 1,
						],
						'betterdocs-quick-setup-migrate' => [
							'name'     => 'betterdocs-quick-setup-migrate',
							'type'     => 'section',
							'priority' => 2,
							'fields'   => [
								'migration_step' => [
									'name'     => 'migration_step',
									'type'     => 'migration',
									'kb'       => $existing_plugins[0][0],
									'label'    => sprintf(
										/* translators: %s is the name of the plugin being migrated. */
										__( 'Migrate %s', 'betterdocs' ),
										$existing_plugins[0][1]
									),
									'priority' => 10
								]
							]
						]
					]
				]
			);
		}

		return $quick_setup;
	}

	public function views() {
		betterdocs()->views->get( 'admin/setup-wizard' );
	}

	public function customizer_settings_url() {
		$query = [
			'autofocus[panel]' => 'betterdocs_customize_options',
			'return'           => admin_url( 'edit.php?post_type=docs' )
		];

		$docs_slug = $this->settings->get( 'docs_slug', 'docs' );
		if ( $docs_slug ) {
			$query['url'] = site_url( '/' . $docs_slug );
		}
		$customizer_link = add_query_arg( $query, admin_url( 'customize.php' ) );

		return esc_url( $customizer_link );
	}

	public function docs_page_url() {
		return esc_url( site_url( '/' . $this->settings->get( 'docs_slug', 'docs' ) ) );
	}

	/**
	 * Call This Function As Helper, When Pro Is Deactivated, To Be Used As Settings Default Values, When Betterdocs Pro Is Deactivated
	 *
	 * @return array
	 */
	public function pro_settings_default_values() {
		return [
			'multiple_kb'                => false,
			'enable_glossaries'          => false,
			'enable_encyclopedia'        => false,
			'analytics_from'             => false,
			'unique_visitor_count'       => false,
			'exclude_bot_analytics'      => false,
			'show_attachment'            => false,
			'show_related_docs'          => false,
			'advance_search'             => false,
			'child_category_exclude'     => false,
			'kb_based_search'            => false,
			'enable_disable'             => false,
			'enable_content_restriction' => false
		];
	}
}
