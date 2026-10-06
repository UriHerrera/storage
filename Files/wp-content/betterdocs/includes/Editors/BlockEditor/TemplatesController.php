<?php
namespace WPDeveloper\BetterDocs\Editors\BlockEditor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\BlockTemplate;

/**
 * BlockTypesController class.
 *
 * @internal
 */
class TemplatesController extends Base {
	protected $blockTemplate;

	/**
	 * Constructor.
	 */
	public function __construct( BlockTemplate $blockTemplate ) {
		$this->blockTemplate = $blockTemplate;
		$this->init();
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		if ( ! betterdocs()->helper->current_theme_is_fse_theme() ) {
			return;
		}
		add_filter( 'pre_get_block_template', [ $this, 'get_block_template_fallback' ], 10, 3 );
		add_filter( 'pre_get_block_file_template', [ $this, 'get_block_file_template' ], 10, 3 );
		add_filter( 'get_block_templates', [ $this, 'add_block_templates' ], 10, 3 );
		add_filter( 'taxonomy_template_hierarchy', [ $this, 'add_doc_archive_to_eligible_for_fallback_templates' ], 10, 1 );
		add_filter( 'admin_bar_menu', [ $this, 'betterdocs_update_site_editor_menu_name' ], 999 );
		//add_filter( 'wp_insert_post_data', [ $this, 'betterdocs_preserve_template_author' ], 10, 2 );
	}

	public function betterdocs_preserve_template_author( $data, $postarr ) {
		if ( 'wp_template' === $data['post_type'] && ( 'taxonomy-doc_category' === $postarr['post_name'] || 'taxonomy-knowledge_base' === $postarr['post_name'] || 'taxonomy-doc_tag' === $postarr['post_name'] ) ) {
			$data['post_author'] = get_current_user_id(); // Keep the current user as author
			$data['post_name']   = 'betterdocs'; // Maintain correct menu slug
			// Add any other fields you need to enforce here
		}
		return $data;
	}

	public function betterdocs_update_site_editor_menu_name( $wp_admin_bar ) {
		// Target the site editor menu item by ID and rename it
		$node = $wp_admin_bar->get_node( 'betterdocs/betterdocs' );
		if ( $node ) {
			$node->title = 'BetterDocs'; // Update the display name
			$wp_admin_bar->add_node( $node ); // Apply the updated node
		}
	}


	/**
	 * This function is used on the `pre_get_block_template` hook to return the fallback template from the db in case
	 * the template is eligible for it.
	 *
	 * @param \WP_Block_Template|null $template Block template object to short-circuit the default query,
	 *                                          or null to allow WP to run its normal queries.
	 * @param string                  $id Template unique identifier (example: theme_slug//template_slug).
	 * @param string                  $template_type wp_template or wp_template_part.
	 *
	 * @return object|null
	 */
	public function get_block_template_fallback( $template, $id, $template_type ) {
		$template_name_parts  = explode( '//', $id );
		list( $theme, $slug ) = $template_name_parts;

		if ( ! $this->blockTemplate->template_is_eligible_for_docs_archive_fallback( $slug ) ) {
			return null;
		}

		$wp_query_args  = [
			'post_name__in' => [ 'archive-docs', $slug ],
			'post_type'     => $template_type,
			'post_status'   => [ 'auto-draft', 'draft', 'publish', 'trash' ],
			'no_found_rows' => true,
			'tax_query'     => [  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => $theme
				]
			]
		];
		$template_query = new \WP_Query( $wp_query_args );
		$posts          = $template_query->posts;

		// If we have more than one result from the query, it means that the current template is present in the db (has
		// been customized by the user) and we should not return the `archive-docs` template.
		if ( count( $posts ) > 1 ) {
			return null;
		}

		if ( count( $posts ) > 0 ) {
			$template = _build_block_template_result_from_post( $posts[0] );

			if ( ! is_wp_error( $template ) ) {
				$template->id          = $theme . '//' . $slug;
				$template->slug        = $slug;
				$template->title       = $this->blockTemplate->get_block_template_title( $slug );
				$template->description = $this->blockTemplate->get_block_template_description( $slug );
				// unset( $template->source ); #creates issue when saving templates for FSE, the issue is randomly when props are set for each blocks, the props does not reflect on the front-end #issue-url:- https://projects.startise.com/fbs-63129

				return $template;
			}
		}
		return $template;
	}

	/**
	 * Adds the `archive-docs` template to the `taxonomy-doc_category`, `taxonomy-doc_tag`
	 * templates to be able to fall back to it.
	 *
	 * @param array $template_hierarchy A list of template candidates, in descending order of priority.
	 */
	public function add_doc_archive_to_eligible_for_fallback_templates( $template_hierarchy ) {
		$template_slugs = array_map(
			'_strip_template_file_suffix',
			$template_hierarchy
		);

		$templates_eligible_for_fallback = array_filter(
			$template_slugs,
			[ $this->blockTemplate, 'template_is_eligible_for_docs_archive_fallback' ]
		);

		if ( count( $templates_eligible_for_fallback ) > 0 ) {
			$template_hierarchy[] = 'archive-docs';
		}

		return $template_hierarchy;
	}

	/**
	 * This function checks if there's a block template file in `betterdocs/includes/blocks/templates/`
	 * to return to pre_get_posts short-circuiting the query in Gutenberg.
	 *
	 * @param \WP_Block_Template|null $template Return a block template object to short-circuit the default query,
	 *                                               or null to allow WP to run its normal queries.
	 * @param string                  $id Template unique identifier (example: theme_slug//template_slug).
	 * @param string                  $template_type wp_template or wp_template_part.
	 *
	 * @return mixed|\WP_Block_Template|\WP_Error
	 */
	public function get_block_file_template( $template, $id, $template_type ) {

		$template_name_parts = explode( '//', $id );

		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}

		list( $template_id, $template_slug ) = $template_name_parts;

		// If we are not dealing with a BetterDocs template let's return early and let it continue through the process.
		if ( BlockTemplate::PLUGIN_SLUG !== $template_id ) {
			return $template;
		}

		// If we don't have a template let Gutenberg do its thing.
		if ( ! $this->block_template_is_available( $template_slug, $template_type ) ) {
			return $template;
		}

		$directory = $this->blockTemplate->get_templates_directory( $template_type );

		$template_file_path = $directory . '/' . $template_slug . '.html';

		$template_object = $this->blockTemplate->create_new_block_template_object( $template_file_path, $template_type, $template_slug );

		$template_built = $this->blockTemplate->build_template_result_from_file( $template_object, $template_type );

		if ( null !== $template_built ) {
			return $template_built;
		}

		// Hand back over to Gutenberg if we can't find a template.
		return $template;
	}

	/**
	 * Add the block template objects to be used.
	 *
	 * @param array $query_result Array of template objects.
	 * @param array $query Optional. Arguments to retrieve templates.
	 * @param string $template_type wp_template or wp_template_part.
	 * @return array
	 */
	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( ! $this->blockTemplate->supports_block_templates() ) {
			return $query_result;
		}

		$post_type = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$slugs     = isset( $query['slug__in'] ) ? $query['slug__in'] : [];

		$template_files = $this->get_block_templates( $slugs, $template_type );

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {
			// If we have a template which is eligible for a fallback, we need to explicitly tell Gutenberg that
			// it has a theme file (because it is using the fallback template file). And then `continue` to avoid
			// adding duplicates.
			if ( $this->blockTemplate->set_has_theme_file_if_fallback_is_available( $query_result, $template_file ) ) {
				continue;
			}

			// If the current $post_type is set (e.g. on an Edit Post screen), and isn't included in the available post_types
			// on the template file, then lets skip it so that it doesn't get added. This is typically used to hide templates
			// in the template dropdown on the Edit Post page.
			if ( $post_type &&
				isset( $template_file->post_types ) &&
				! in_array( $post_type, $template_file->post_types, true )
			) {
				continue;
			}

			// It would be custom if the template was modified in the editor, so if it's not custom we can load it from
			// the filesystem.
			if ( 'custom' !== $template_file->source ) {
				$template = $this->blockTemplate->build_template_result_from_file( $template_file, $template_type );
			} else {
				$template_file->title       = $this->blockTemplate->get_block_template_title( $template_file->slug );
				$template_file->description = $this->blockTemplate->get_block_template_description( $template_file->slug );
				$query_result[]             = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
			! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query =
			! isset( $query['area'] ) || $template_file->area === $query['area'];
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $template;
			}
		}

		// We need to remove theme (i.e. filesystem) templates that have the same slug as a customised one.
		// This only affects saved templates that were saved BEFORE a theme template with the same slug was added.
		$query_result = BlockTemplate::remove_theme_templates_with_custom_alternative( $query_result );

		/**
		 * WC templates from theme aren't included in `$this->get_block_templates()` but are handled by Gutenberg.
		 * We need to do additional search through all templates file to update title and description for WC
		 * templates that aren't listed in theme.json.
		 */
		$query_result = array_map(
			function ( $template ) {
				if ( 'theme' === $template->origin ) {
					return $template;
				}
				if ( $template->title === $template->slug ) {
					$template->title = $this->blockTemplate->get_block_template_title( $template->slug );
				}
				if ( ! $template->description ) {
					$template->description = $this->blockTemplate->get_block_template_description( $template->slug );
				}
				return $template;
			},
			$query_result
		);

		return $query_result;
	}

	/**
	 * Gets the templates saved in the database.
	 *
	 * @param array $slugs An array of slugs to retrieve templates for.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return int[]|\WP_Post[] An array of found templates.
	 */
	public function get_block_templates_from_db( $slugs = [], $template_type = 'wp_template' ) {
		$check_query_args = [
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => [  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => [ BlockTemplate::PLUGIN_SLUG, get_stylesheet() ]
				]
			]
		];

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query                = new \WP_Query( $check_query_args );
		$saved_betterdocs_templates = $check_query->posts;

		return array_map(
			function ( $saved_betterdocs_template ) {
				return $this->blockTemplate->build_template_result_from_post( $saved_betterdocs_template );
			},
			$saved_betterdocs_templates
		);
	}

	/**
	 * Get and build the block template objects from the block template files.
	 *
	 * @param array $slugs An array of slugs to retrieve templates for.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return array WP_Block_Template[] An array of block template objects.
	 */
	public function get_block_templates( $slugs = [], $template_type = 'wp_template' ) {
		$templates_from_db         = $this->get_block_templates_from_db( $slugs, $template_type );
		$templates_from_betterdocs = $this->blockTemplate->get_block_templates_from_betterdocs( $slugs, $templates_from_db, $template_type );
		$templates                 = array_merge( $templates_from_db, $templates_from_betterdocs );
		return $templates;
	}

	/**
	 * Checks whether a block template with that name exists in BetterDocs Blocks
	 *
	 * @param string $template_name Template to check.
	 * @param string  $template_type wp_template or wp_template_part.
	 *
	 * @return boolean
	 */
	public function block_template_is_available( $template_name, $template_type = 'wp_template' ) {
		if ( ! $template_name ) {
			return false;
		}
		$directory = $this->blockTemplate->get_templates_directory( $template_type ) . '/' . $template_name . '.html';

		return is_readable( $directory ) || $this->get_block_templates( [ $template_name ], $template_type );
	}
}
