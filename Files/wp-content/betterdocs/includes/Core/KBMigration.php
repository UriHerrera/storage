<?php
namespace WPDeveloper\BetterDocs\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Query;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Admin\Builder\Rules;

class KBMigration extends Base {
	public $existing_plugins;
	public $migrated_plugins;
	public $active = 'helpscout';

	public function __construct() {
		$this->existing_plugins = $this->knowledge_base_plugins();
		$this->migrated_plugins = $this->get_migrated_plugins();

		add_filter( 'betterdocs_settings_tabs', [ $this, 'migration_settings' ], 10, 1 );
		if ( ! $this->existing_plugins || ( $this->existing_plugins && in_array( $this->existing_plugins[0][0], $this->migrated_plugins ) ) ) {
			return;
		}
		$this->active = 'kb-migration';
		//add_filter( 'admin_notices', [ $this, 'migration_notice' ], 10, 1 );
		add_filter( 'betterdocs_migration_tab_sections', [ $this, 'kb_migration_settings' ], 10, 1 );
	}

	public function migration_notice() {
		$screen = get_current_screen()->id;
		if ( $screen === 'betterdocs_page_betterdocs-setup' ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible">
			<?php
			printf(
				'<p>%s<a class="button button-primary betterdocs-migration-notice" href="%s">%s</a><a class="button" href="#">%s</a><a class="button" href="#">%s</a></p>',
				esc_html(
					sprintf(
					/* translators: %1$s and %2$s are the name of the existing plugin. */
						__(
							'Whoops! You are already using %1$s on your website. To migrate your %2$s data to BetterDocs, click here ',
							'betterdocs'
						),
						'<strong>' . esc_html( $this->existing_plugins[0][1] ) . '</strong>',
						'<strong>' . esc_html( $this->existing_plugins[0][1] ) . '</strong>'
					)
				),
				esc_url( admin_url( 'admin.php?page=betterdocs-settings#migration' ) ),
				esc_html__( 'Start Migration', 'betterdocs' ),
				esc_html__( 'Maybe Later', 'betterdocs' ),
				esc_html__( 'Never Show Again', 'betterdocs' )
			);
			?>
		</div>
		<?php
	}


	public function migration_settings( $args ) {
		/**
		 * License Tab
		 */
		$args['tab-migration'] = apply_filters(
			'betterdocs_settings_tab_migration',
			[
				'id'       => 'tab-migration',
				'name'     => 'tab-migration',
				'classes'  => 'tab-migration',
				'label'    => __( 'Migration', 'betterdocs' ),
				'priority' => 80,
				'fields'   => [
					'sections-migration' => [
						'name'     => 'sections-migration',
						'type'     => 'section',
						'label'    => __( 'Migration', 'betterdocs' ),
						'priority' => 30,
						'fields'   => [
							'all-migration' => [
								'id'              => 'all-tab-migration',
								'name'            => 'all-tab-migration',
								'label'           => __( 'Migration Settings', 'betterdocs' ),
								'classes'         => 'tab-layout',
								'type'            => 'tab',
								'active'          => $this->active,
								'completionTrack' => true,
								'sidebar'         => false,
								'save'            => false,
								'title'           => false,
								'config'          => [
									'active'  => $this->active,
									'sidebar' => false,
									'title'   => false
								],
								'submit'          => [
									'show' => false
								],
								'step'            => [
									'show' => false
								],
								'priority'        => 20,
								'fields'          => apply_filters(
									'betterdocs_migration_tab_sections',
									[
										'helpscout' => [
											'id'       => 'helpscout',
											'name'     => 'helpscout',
											'type'     => 'section',
											'label'    => __( 'Help Scout', 'betterdocs' ),
											'priority' => 2,
											'fields'   => [
												'helpscout_api_key' => [
													'name' => 'helpscout_api_key',
													'type' => 'text',
													'label' => __( 'Docs API Key', 'betterdocs' ),
													'label_subtitle' => sprintf(
														/* translators: %s is a URL to the Help Scout migration documentation. */
														__( 'Check out this <a target="_blank" href="%s">documentation</a> to retrieve your Help Scout Docs API Key.', 'betterdocs' ),
														esc_url( 'https://betterdocs.co/docs/help-scout-migration-feature-with-betterdocs/' )
													),
													'default' => '',
													'priority' => 1,
												],
												'helpscout_collection_id' => [
													'name' => 'helpscout_collection_id',
													'type' => 'text',
													'label' => __( 'Collection ID', 'betterdocs' ),
													'label_subtitle' => sprintf(
														/* translators: %s is a URL to the Help Scout migration documentation. */
														__( 'Check out this <a target="_blank" href="%s">documentation</a> to retrieve your Help Scout Collection ID.', 'betterdocs' ),
														esc_url( 'https://betterdocs.co/docs/help-scout-migration-feature-with-betterdocs/' )
													),
													'default' => '',
													'priority' => 2,
												],
												'helpscout_action' => apply_filters(
													'betterdocs_helpscout_migration_action',
													[
														'name' => 'helpscout_action',
														'text' => [
															'normal'  => __( 'Migrate Help Scout Docs', 'betterdocs' ),
															'saved'   => __( 'Migrate Help Scout Docs', 'betterdocs' ),
															'loading' => __( 'Migrating...', 'betterdocs' ),
														],
														'type' => 'button',
														'priority' => 3,
														'ajax' => [
															'on'   => 'click',
															'api'  => '/betterdocs/v1/helpscout-migration',
															'data' => [
																'helpscout_api_key'       => '@helpscout_api_key',
																'helpscout_collection_id' => '@helpscout_collection_id'
															],
															'swal' => [
																'text' => __( 'Migration process has started in the background.', 'betterdocs' ),
																'icon' => 'success',
																'autoClose' => 2000
															]
														]
													]
												)
											]
										]
									]
								)
							]
						]
					]
				]
			]
		);

		return $args;
	}

	public function kb_migration_settings( $args ) {
		/**
		 * License Tab
		 */
		$args['kb-migration'] = [
			'id'       => 'kb-migration',
			'name'     => 'kb-migration',
			'type'     => 'section',
			'label'    => $this->existing_plugins[0][1],
			'priority' => 1,
			'fields'   => [
				'kb-migration-section' => [
					'name'     => 'kb-migration-section',
					'classes'  => 'kb-migration-section',
					'type'     => 'section',
					'priority' => 0,
					'save'     => false,
					'fields'   => apply_filters(
						'betterdocs_migration_fields',
						[
							'migration_header' => [
								'name'        => 'migration_header',
								'type'        => 'header',
								'title'       => __( 'Migration', 'betterdocs' ),
								'direction'   => 'column',
								'description' => sprintf(
									/* translators: %1$s is the name of the existing knowledge base plugin. */
									__( 'We found that there is another Knowledge Base plugin, %1$s, installed on this website. To migrate your knowledge base to BetterDocs from %2$s, click the "Start Migration" button and all the documentation will be migrated to BetterDocs.', 'betterdocs' ),
									esc_html( $this->existing_plugins[0][1] ),
									esc_html( $this->existing_plugins[0][1] )
								),
								'icon'        => '<img src="' . betterdocs()->assets->icon( 'icons/migration.svg', true ) . '"/>',
								'priority'    => 1,
							],
							'migration_action' => [
								'name'     => 'migration_action',
								'type'     => 'button',
								'text'     => [
									'normal'  => __( 'Start Migration', 'betterdocs' ),
									'saved'   => __( 'Migrated', 'betterdocs' ),
									'loading' => __( 'Migrating...', 'betterdocs' ),
								],
								'ajax'     => [
									'on'     => 'click',
									'api'    => '/betterdocs/v1/migrate',
									'data'   => [
										'existing_plugins' => $this->existing_plugins[0][1],
									],
									'swal'   => [
										'text'      => __( 'Migration completed successfully.', 'betterdocs' ),
										'icon'      => 'success',
										'autoClose' => 1000
									],
									'reload' => admin_url( 'admin.php?page=betterdocs-settings' ),
								],
								'priority' => 2,
							]
						]
					)
				]
			]
		];

		return $args;
	}

	public function migrate() {
		$existing_plugins      = $this->knowledge_base_plugins();
		$existing_plugins_data = $this->existing_plugins_data( $existing_plugins[0][0] );
		if ( isset( $existing_plugins_data['name'] ) && isset( $existing_plugins_data['url'] ) ) {
			if ( $existing_plugins_data['name'] === 'echo-knowledge-base' ) {
				$this->eco_knowledgebase_migration();
			} elseif ( $existing_plugins_data['name'] === 'pressapps-knowledge-base' ) {
				$this->pressapps_migration();
			} elseif ( $existing_plugins_data['name'] === 'wedocs' ) {
				$this->wedocs_migration();
			}
			$this->update_option( $existing_plugins_data['name'] );
			deactivate_plugins( $existing_plugins_data['url'] );
		}
	}

	public function knowledge_base_plugins() {
		$plugins = [];

		if ( Helper::is_plugin_active( 'wedocs/wedocs.php' ) ) {
			$plugins[] = [ 'wedocs', 'weDocs' ];
		}
		if ( Helper::is_plugin_active( 'bsf-docs/bsf-docs.php' ) ) {
			$plugins[] = [ 'bsf-docs', 'BSF docs' ];
		}
		if ( Helper::is_plugin_active( 'documentor-lite/documentor-lite.php' ) ) {
			$plugins[] = [ 'documentor-lite', 'Documentor' ];
		}
		if ( Helper::is_plugin_active( 'echo-knowledge-base/echo-knowledge-base.php' ) ) {
			$plugins[] = [ 'echo-knowledge-base', 'Echo Knowledge Base' ];
		}
		if ( Helper::is_plugin_active( 'pressapps-knowledge-base/pressapps-knowledge-base.php' ) ) {
			$plugins[] = [ 'pressapps-knowledge-base', 'PressApps Knowledge Base' ];
		}

		return $plugins;
	}

	public function insert_terms_hierarchically( $existing_term, $new_term, $parentId = 0 ) {
		$into = [];
		$cats = get_terms(
			[
				'taxonomy'   => $existing_term,
				'hide_empty' => false
			]
		);
		if ( $cats ) {
			foreach ( $cats as $i => $cat ) {
				if ( $cat->parent == $parentId ) {
					$into[ $cat->term_id ] = $cat;
					unset( $cats[ $i ] );
					$doc_parent_term = term_exists( $cat->name, $new_term );
					wp_insert_term(
						$cat->name,
						$new_term,
						[
							'alias_of'    => $cat->slug,
							'description' => $cat->description,
							'slug'        => $cat->slug,
							'parent'      => $cat->parent
						]
					);
				}
			}
			if ( $cats ) {
				foreach ( $cats as $i => $cat ) {
					$get_existing_term = get_term_by( 'id', $cat->parent, $existing_term );
					$doc_parent_term   = term_exists( $get_existing_term->name, $new_term );
					wp_insert_term(
						$cat->name,
						$new_term,
						[
							'alias_of'    => $cat->slug,
							'description' => $cat->description,
							'slug'        => $cat->slug,
							'parent'      => $doc_parent_term['term_id']
						]
					);
				}
			}
		}
	}

	public function insert_posts( $existing_post, $existing_cat, $existing_tag ) {
		$args      = [
			'post_type'      => $existing_post,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		];
		$postslist = get_posts( $args );
		if ( $postslist ) {
			foreach ( $postslist as $post ) {
				// Check if a post with the same title exists using WP_Query
				$query_args   = [
					'post_type'      => 'docs',
					'post_status'    => 'any',
					'title'          => $post->post_title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				];
				$existing_doc = new WP_Query( $query_args );

				if ( ! $existing_doc->have_posts() ) {
					$post_args = [
						'post_type'             => 'docs',
						'post_title'            => $post->post_title,
						'post_content'          => $post->post_content,
						'post_status'           => $post->post_status,
						'post_author'           => $post->post_author,
						'post_date'             => $post->post_date,
						'post_date_gmt'         => $post->post_date_gmt,
						'post_excerpt'          => $post->post_excerpt,
						'comment_status'        => $post->comment_status,
						'ping_status'           => $post->ping_status,
						'post_password'         => $post->post_password,
						'post_name'             => $post->post_name,
						'to_ping'               => $post->to_ping,
						'pinged'                => $post->pinged,
						'post_modified'         => $post->post_modified,
						'post_modified_gmt'     => $post->post_modified_gmt,
						'post_content_filtered' => $post->post_content_filtered,
						'post_parent'           => $post->post_parent,
						'post_mime_type'        => $post->post_mime_type,
						'comment_count'         => $post->comment_count,
						'filter'                => $post->filter,
					];
					// Insert the post into the database
					$result = wp_insert_post( $post_args );
					if ( $result && ! is_wp_error( $result ) ) {
						$cat_list = wp_get_post_terms( $post->ID, $existing_cat, [ 'fields' => 'all' ] );
						if ( $cat_list ) {
							$post_id = $result;
							wp_set_object_terms( $post_id, [ $cat_list[0]->name ], 'doc_category', false );
						}
						$tag_list = wp_get_post_terms( $post->ID, $existing_tag, [ 'fields' => 'all' ] );
						if ( $tag_list ) {
							$post_id = $result;
							wp_set_object_terms( $post_id, [ $tag_list[0]->name ], 'doc_tag', false );
						}
					}
				}

				// Reset post data after query
				wp_reset_postdata();
			}
		}
	}

	public function existing_plugins_data( $plugins ) {
		$plugins_data = [];
		if ( $plugins === 'wedocs' ) {
			$plugins_data['name'] = 'wedocs';
			$plugins_data['url']  = 'wedocs/wedocs.php';
		}
		if ( $plugins === 'bsf-docs' ) {
			$plugins_data['name'] = 'bsf-docs';
			$plugins_data['url']  = 'bsf-docs/bsf-docs.php';
		}
		if ( $plugins === 'documentor-lite' ) {
			$plugins_data['name'] = 'documentor-lite';
			$plugins_data['url']  = 'documentor-lite/documentor-lite.php';
		}
		if ( $plugins === 'echo-knowledge-base' ) {
			$plugins_data['name'] = 'echo-knowledge-base';
			$plugins_data['url']  = 'echo-knowledge-base/echo-knowledge-base.php';
		}
		if ( $plugins === 'pressapps-knowledge-base' ) {
			$plugins_data['name'] = 'pressapps-knowledge-base';
			$plugins_data['url']  = 'pressapps-knowledge-base/pressapps-knowledge-base.php';
		}
		return $plugins_data;
	}

	public function eco_knowledgebase_migration() {
		$this->insert_terms_hierarchically( 'epkb_post_type_1_category', 'doc_category' );
		$this->insert_terms_hierarchically( 'epkb_post_type_1_tag', 'doc_tag' );
		$this->insert_posts( 'epkb_post_type_1', 'epkb_post_type_1_category', 'epkb_post_type_1_tag' );
	}

	public function pressapps_migration() {
		$this->insert_terms_hierarchically( 'knowledgebase_category', 'doc_category' );
		$this->insert_terms_hierarchically( 'knowledgebase_tags', 'doc_tag' );
		$this->insert_posts( 'knowledgebase', 'knowledgebase_category', 'knowledgebase_tags' );
	}

	public function wedocs_migration() {
		// Step 1: Get posts with no parent (main posts)
		$args_step1 = array(
			'post_type'      => 'docs',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post_parent'    => 0,
		);

		$posts_step1 = get_posts( $args_step1 );

		// Multidimensional array to store posts
		$nested_posts = array();

		// First Dimension key: 'multiple_kb'
		foreach ( $posts_step1 as $post_step1 ) {
			$nested_posts['multiple_kb'][ $post_step1->ID ] = array(
				'post'         => $post_step1,
				'doc_category' => array(), // Initialize the second dimension
			);

			// Step 2: 'doc_category'
			$args_step2 = array(
				'post_type'      => 'docs',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => $post_step1->ID,
			);

			$posts_step2 = get_posts( $args_step2 );

			foreach ( $posts_step2 as $post_step2 ) {
				$nested_posts['multiple_kb'][ $post_step1->ID ]['doc_category'][ $post_step2->ID ] = array(
					'post' => $post_step2,
					'docs' => array(), // Initialize the third dimension
				);

				// Step 3: 'docs'
				$args_step3 = array(
					'post_type'      => 'docs', // Replace with your actual post type
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => $post_step2->ID,
				);

				$posts_step3 = get_posts( $args_step3 );

				foreach ( $posts_step3 as $post_step3 ) {
					$nested_posts['multiple_kb'][ $post_step1->ID ]['doc_category'][ $post_step2->ID ]['docs'][ $post_step3->ID ] = array(
						'post' => $post_step3,
					);
					// Continue with additional dimensions if needed
				}
			}
		}

		// Now, $nested_posts contains the posts organized in a multidimensional array with the specified key names

		foreach ( $nested_posts['multiple_kb'] as $multiple_kb_id => $multiple_kb_data ) {
			// Step 1: Insert posts as terms under 'knowledge_base'
			$knowledge_base_id = wp_insert_term( $multiple_kb_data['post']->post_title, 'knowledge_base' );

			if ( ! is_wp_error( $knowledge_base_id ) ) {
				wp_delete_post( $multiple_kb_data['post']->ID, true );
				$knowledge_base_term = get_term( $knowledge_base_id['term_id'], 'knowledge_base' );
			}

			foreach ( $multiple_kb_data['doc_category'] as $doc_category_id => $doc_category_data ) {

				// Step 2: Insert posts as terms under 'doc_category' and set term meta
				$doc_category_id = wp_insert_term( $doc_category_data['post']->post_title, 'doc_category' );

				if ( ! is_wp_error( $doc_category_id ) ) {
					$doc_category_term = get_term( $doc_category_id['term_id'], 'doc_category' );
					wp_delete_post( $doc_category_data['post']->ID, true );
					$doc_category_kb = rest_sanitize_array( array( $knowledge_base_term->slug ) );
					// Set term meta for 'knowledge_base_doc_category'
					update_term_meta( $doc_category_id['term_id'], 'doc_category_knowledge_base', $doc_category_kb );
				}

				foreach ( $doc_category_data['docs'] as $docs_id => $docs_data ) {
					wp_set_post_terms( $docs_data['post']->ID, array( $knowledge_base_term->term_id ), 'knowledge_base' );
					wp_set_post_terms( $docs_data['post']->ID, array( $doc_category_term->term_id ), 'doc_category' );
					// // Step 3: Update posts to assign correct parent terms
					// wp_update_post(array(
					//     'ID' => $docs_data['post']->ID,
					//     'post_parent' => 0,
					// ));
				}
			}
		}
	}

	/**
	 * Updates the 'betterdocs_migration' option with migrated knowledge base plugin names.
	 *
	 * This function checks if the option exists and is a serialized array. If the option exists,
	 * it unserializes the current value, merges it with the new values, serializes the merged array,
	 * and updates the option. If the option doesn't exist or is not a serialized array, it creates
	 * a new option with the serialized format.
	 *
	 * @param string $kb_name The name of the knowledge base to be added or updated.
	 *
	 * @return void
	 */
	public function update_option( $kb_name ) {
		$existing_value = get_option( 'betterdocs_migration' );
		$new_values     = array( $kb_name );

		if ( $existing_value !== false && is_serialized( $existing_value ) ) {
			$existing_values_array = unserialize( $existing_value );
			$merged_values         = array_merge( $existing_values_array, $new_values );

			update_option( 'betterdocs_migration', serialize( $merged_values ) );
		} else {
			update_option( 'betterdocs_migration', serialize( $new_values ) );
		}
	}

	public function get_migrated_plugins() {
		$existing_value = get_option( 'betterdocs_migration' );

		if ( $existing_value ) {
			return unserialize( $existing_value );
		}

		return [];
	}
}
