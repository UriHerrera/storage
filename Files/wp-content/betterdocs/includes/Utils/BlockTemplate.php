<?php
namespace WPDeveloper\BetterDocs\Utils;

/**
 * Utility methods used for serving block templates from BetterDocs Blocks.
 * {@internal This class and its methods should only be used within the class-block-template-controller.php and is not intended for public use.}
 */
class BlockTemplate {
	const ELIGIBLE_FOR_DOC_ARCHIVE_FALLBACK = [ 'taxonomy-doc_category', 'taxonomy-doc_tag' ];

	/**
	 * BetterDocs plugin slug
	 *
	 * This is used to save templates to the DB which are stored against this value in the wp_terms table.
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'betterdocs/betterdocs';

	/**
	 * Gets the directory where templates of a specific template type can be found.
	 *
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return string
	 */
	public function get_templates_directory( $template_type = 'wp_template' ) {
		return BETTERDOCS_FSE_TEMPLATES_PATH . DIRECTORY_SEPARATOR;
	}

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public function flatten_blocks( &$blocks ) {
		$all_blocks = [];
		$queue      = [];
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}
		$queue_count = count( $queue );

		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}

			$queue_count = count( $queue );
		}

		return $all_blocks;
	}

	/**
	 * Parses wp_template content and injects the current theme's
	 * stylesheet as a theme attribute into each wp_template_part
	 *
	 * @param string $template_content serialized wp_template content.
	 *
	 * @return string Updated wp_template content.
	 */
	public function inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = $this->flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			if (
				'core/template-part' === $block['blockName'] &&
				! isset( $block['attrs']['theme'] )
			) {
				$block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
				$has_updated_content     = true;
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $template_content;
	}

	/**
	 * Build a unified template object based a post Object.
	 * Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
	 *
	 * @param \WP_Post $post Template post.
	 *
	 * @return \WP_Block_Template|\WP_Error Template.
	 */
	public static function build_template_result_from_post( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! $terms ) {
			return new \WP_Error( 'template_missing_theme', __( 'No theme is defined for this template.', 'betterdocs' ) );
		}

		$theme          = $terms[0]->name;
		$has_theme_file = true;

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $theme . '//' . $post->post_name;
		$template->theme          = $theme;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = $has_theme_file;
		$template->is_custom      = false;
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

		if ( 'wp_template_part' === $post->post_type ) {
			$type_terms = get_the_terms( $post, 'wp_template_part_area' );
			if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
				$template->area = $type_terms[0]->name;
			}
		}

		// We are checking 'woocommerce' to maintain classic templates which are saved to the DB,
		// prior to updating to use the correct slug.
		// More information found here: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423.
		if ( self::PLUGIN_SLUG === $theme ) {
			$template->origin = 'plugin';
		}

		/*
		* Run the block hooks algorithm introduced in WP 6.4 on the template content.
		*/
		if ( function_exists( 'inject_ignored_hooked_blocks_metadata_attributes' ) ) {
			$hooked_blocks = get_hooked_blocks();
			if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
				$before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
				$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
				$blocks               = parse_blocks( $template->content );
				$template->content    = traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
			}
		}

		return $template;
	}

	/**
	 * Build a unified template object based on a theme file.
	 *
	 * @internal Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
	 *
	 * @param array|object $template_file Theme file.
	 * @param string       $template_type wp_template or wp_template_part.
	 *
	 * @return \WP_Block_Template Template.
	 */
	public function build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		// If the theme has an archive-products.html template but does not have product taxonomy templates
		// then we will load in the archive-product.html template from the theme to use for product taxonomies on the frontend.
		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : self::PLUGIN_SLUG . '//' . $template_file->slug;
		$template->theme   = $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG;
		$template->content = self::inject_theme_attribute_in_content( $template_content );
		// Remove the term description block from the archive-product template
		// as the Product Catalog/Shop page doesn't have a description.
		if ( 'archive-docs' === $template_file->slug ) {
			$template->content = str_replace( '<!-- wp:term-description {"align":"wide"} /-->', '', $template->content );
		}
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : self::get_block_template_title( $template_file->slug );
		$template->description    = ! empty( $template_file->description ) ? $template_file->description : self::get_block_template_description( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = $template_file->source;
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';

		/*
		* Run the block hooks algorithm introduced in WP 6.4 on the template content.
		*/
		if ( function_exists( 'inject_ignored_hooked_blocks_metadata_attributes' ) ) {
			$before_block_visitor = '_inject_theme_attribute_in_template_part_block';
			$after_block_visitor  = null;
			$hooked_blocks        = get_hooked_blocks();
			if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
				$before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
				$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
			}
			$blocks            = parse_blocks( $template->content );
			$template->content = traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
		}

		return $template;
	}

	/**
	 * Build a new template object so that we can make BetterDocs Blocks default templates available in the current theme should they not have any.
	 *
	 * @param string $template_file Block template file path.
	 * @param string $template_type wp_template or wp_template_part.
	 * @param string $template_slug Block template slug e.g. single-docs.
	 * @param bool   $template_is_from_theme If the block template file is being loaded from the current theme instead of BetterDocs Blocks.
	 *
	 * @return object Block template object.
	 */
	public function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
		$theme_name = wp_get_theme()->get( 'TextDomain' );

		$new_template_item = [
			'slug'        => $template_slug,
			'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : self::PLUGIN_SLUG . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
			// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'title'       => $this->convert_slug_to_title( $template_slug ),
			'description' => '',
			'post_types'  => [] // Don't appear in any Edit Post template selector dropdown.
		];

		return (object) $new_template_item;
	}

	/**
	 * Finds all nested template part file paths in a theme's directory.
	 *
	 * @param string $base_directory The theme's file path.
	 * @return array $path_list A list of paths to all template part files.
	 */
	public function get_template_paths( $base_directory ) {
		$path_list = [];
		if ( file_exists( $base_directory ) ) {
			$nested_files      = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base_directory ) );
			$nested_html_files = new \RegexIterator( $nested_files, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH );
			foreach ( $nested_html_files as $path => $file ) {
				$path_list[] = $path;
			}
		}
		return $path_list;
	}

	/**
	 * Returns template titles.
	 *
	 * @param string $template_slug The templates slug (e.g. single-docs).
	 * @return string Human friendly title.
	 */
	public function get_block_template_title( $template_slug ) {
		$plugin_template_types = $this->get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['title'];
		} else {
			// Human friendly title converted from the slug.
			return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	/**
	 * Returns template descriptions.
	 *
	 * @param string $template_slug The templates slug (e.g. single-docs).
	 * @return string Template description.
	 */
	public function get_block_template_description( $template_slug ) {
		$plugin_template_types = $this->get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['description'];
		}
		return '';
	}

	public function get_templates_fils_from_betterdocs( $template_type ) {
		$directory      = $this->get_templates_directory( $template_type );
		$template_files = $this->get_template_paths( $directory );
		return $template_files;
	}

	/**
	 * Gets the templates from the BetterDocs blocks directory
	 *
	 * @param string[] $slugs An array of slugs to filter templates by. Templates whose slug does not match will not be returned.
	 * @param array    $already_found_templates Templates that have already been found, these are customised templates that are loaded from the database.
	 * @param string   $template_type wp_template or wp_template_part.
	 *
	 * @return array Templates from the BetterDocs blocks plugin directory.
	 */
	public function get_block_templates_from_betterdocs( $slugs, $already_found_templates, $template_type = 'wp_template' ) {

		$template_files = $this->get_templates_fils_from_betterdocs( $template_type );
		$templates      = [];

		// Check if BetterDocs Pro is not installed
		if ( ! betterdocs()->is_pro_active() || ( betterdocs()->is_pro_active() && betterdocs()->settings->get( 'multiple_kb' ) == false ) ) {
			// Remove the 'taxonomy-knowledge_base.html' file
			$template_files = array_filter(
				$template_files,
				function ( $file ) {
					return strpos( $file, 'taxonomy-knowledge_base.html' ) === false;
				}
			);
		}

		foreach ( $template_files as $template_file ) {
			$template_slug = $this->generate_template_slug_from_path( $template_file );

			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			// If the the template is already in the list (i.e. it came from the
			// database) then we should not overwrite it with the one from the filesystem.
			if (
				count(
					array_filter(
						$already_found_templates,
						function ( $template ) use ( $template_slug ) {
							$template_obj = (object) $template; //phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
							return $template_obj->slug === $template_slug;
						}
					)
				) > 0 ) {
				continue;
			}

			// At this point the template only exists in the Blocks filesystem and has not been saved in the DB,
			// or superseded by the theme.
			$templates[] = $this->create_new_block_template_object( $template_file, $template_type, $template_slug );
		}
		return $templates;
	}

	/**
	 * Returns a filtered list of plugin template types, containing their
	 * localized titles and descriptions.
	 *
	 * @return array The plugin template types.
	 */
	public function get_plugin_block_template_types() {
		$plugin_template_types = [
			'archive-docs'          => [
				'title'       => _x( 'Docs Page', 'Template name', 'betterdocs' ),
				'description' => __( 'Template used to display Docs Page.', 'betterdocs' )
			],
			'taxonomy-doc_category' => [
				'title'       => _x( 'Docs Category', 'Template name', 'betterdocs' ),
				'description' => __( 'Template used to display docs by category.', 'betterdocs' )
			],
			'taxonomy-doc_tag'      => [
				'title'       => _x( 'Docs Tag', 'Template name', 'betterdocs' ),
				'description' => __( 'Template used to display docs by tag.', 'betterdocs' )
			],
			'single-docs'           => [
				'title'       => _x( 'Single Docs', 'Template name', 'betterdocs' ),
				'description' => __( 'Template used to display the single docs.', 'betterdocs' )
			]
		];

		return $plugin_template_types;
	}

	/**
	 * Converts template slugs into readable titles.
	 *
	 * @param string $template_slug The templates slug (e.g. single-docs).
	 * @return string Human friendly title converted from the slug.
	 */
	public function convert_slug_to_title( $template_slug ) {
		switch ( $template_slug ) {
			case 'single-docs':
				return __( 'Single Docs', 'betterdocs' );
			case 'archive-docs':
				return __( 'Docs Page', 'betterdocs' );
			case 'taxonomy-doc_category':
				return __( 'Docs Category', 'betterdocs' );
			case 'taxonomy-doc_tag':
				return __( 'Docs Tag', 'betterdocs' );
			default:
				// Replace all hyphens and underscores with spaces.
				return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	/**
	 * Converts template paths into a slug
	 *
	 * @param string $path The template's path.
	 * @return string slug
	 */
	public function generate_template_slug_from_path( $path ) {
		$template_extension = '.html';

		return basename( $path, $template_extension );
	}

	/**
	 * Checks to see if they are using a compatible version of WP, or if not they have a compatible version of the Gutenberg plugin installed.
	 *
	 * @return boolean
	 */
	public function supports_block_templates() {
		if (
			! betterdocs()->helper->current_theme_is_fse_theme() &&
			( ! function_exists( 'gutenberg_supports_block_templates' ) || ! gutenberg_supports_block_templates() )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves a single unified template object using its id.
	 *
	 * @param string $id            Template unique identifier (example: theme_slug//template_slug).
	 * @param string $template_type Optional. Template type: `'wp_template'` or '`wp_template_part'`.
	 *                             Default `'wp_template'`.
	 *
	 * @return \WP_Block_Template|null Template.
	 */
	public static function get_block_template( $id, $template_type ) {
		if ( function_exists( 'get_block_template' ) ) {
			return get_block_template( $id, $template_type );
		}

		if ( function_exists( 'gutenberg_get_block_template' ) ) {
			return gutenberg_get_block_template( $id, $template_type );
		}

		return null;
	}

	/**
	 * Checks if we can fallback to the `archive-docs` template for a given slug
	 *
	 * `taxonomy-doc_category`, `taxonomy-knowledge_base` and `taxonomy-doc_tag` templates can generally use the
	 * `archive-docs` as a fallback if there are no specific overrides.
	 *
	 * @param string $template_slug Slug to check for fallbacks.
	 * @return boolean
	 */
	public function template_is_eligible_for_docs_archive_fallback( $template_slug ) {
		return in_array( $template_slug, self::ELIGIBLE_FOR_DOC_ARCHIVE_FALLBACK, true );
	}

	/**
	 * Sets the `has_theme_file` to `true` for templates with fallbacks
	 *
	 * There are cases (such as tags and categories) in which fallback templates
	 * can be used; so, while *technically* the theme doesn't have a specific file
	 * for them, it is important that we tell Gutenberg that we do, in fact,
	 * have a theme file (i.e. the fallback one).
	 *
	 * **Note:** this function changes the array that has been passed.
	 *
	 * It returns `true` if anything was changed, `false` otherwise.
	 *
	 * @param array  $query_result Array of template objects.
	 * @param object $template A specific template object which could have a fallback.
	 *
	 * @return boolean
	 */
	public function set_has_theme_file_if_fallback_is_available( $query_result, $template ) {
		foreach ( $query_result as &$query_result_template ) {
			if (
				$query_result_template->slug === $template->slug
				&& $query_result_template->theme === $template->theme
			) {
				if ( $this->template_is_eligible_for_docs_archive_fallback( $template->slug ) ) {
					$query_result_template->has_theme_file = true;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Removes templates that were added to a theme's block-templates directory, but already had a customised version saved in the database.
	 *
	 * @param \WP_Block_Template[]|\stdClass[] $templates List of templates to run the filter on.
	 *
	 * @return array List of templates with duplicates removed. The customised alternative is preferred over the theme default.
	 */
	public static function remove_theme_templates_with_custom_alternative( $templates ) {

		// Get the slugs of all templates that have been customised and saved in the database.
		$customised_template_slugs = array_map(
			function ( $template ) {
				return $template->slug;
			},
			array_values(
				array_filter(
					$templates,
					function ( $template ) {
						// This template has been customised and saved as a post.
						return 'custom' === $template->source;
					}
				)
			)
		);

		// Remove theme (i.e. filesystem) templates that have the same slug as a customised one. We don't need to check
		// for `betterdocs` in $template->source here because betterdocs templates won't have been added to $templates
		// if a saved version was found in the db. This only affects saved templates that were saved BEFORE a theme
		// template with the same slug was added.
		return array_values(
			array_filter(
				$templates,
				function ( $template ) use ( $customised_template_slugs ) {
					// This template has been customised and saved as a post, so return it.
					return ! ( 'theme' === $template->source && in_array( $template->slug, $customised_template_slugs, true ) );
				}
			)
		);
	}
}
