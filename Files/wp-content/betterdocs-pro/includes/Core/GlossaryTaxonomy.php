<?php

namespace WPDeveloper\BetterDocsPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registration and classic WP-admin UI for the `glossaries` taxonomy.
 *
 * Glossaries is a Pro feature, so both the taxonomy itself and the term-form
 * fields that back it live here. Free ships only the locked teaser screen
 * (`show_glossary_teaser()`); with Pro inactive the taxonomy is never
 * registered, which is what makes every glossary REST route, archive URL and
 * admin column disappear at once.
 *
 * Registration runs on `init` at priority 9 — the same priority Free uses for
 * `Core\PostType::register()`. Free's plugin file loads first, so its callback
 * (which registers the `docs` post type this taxonomy attaches to) is always
 * queued ahead of this one.
 *
 * @since 4.2.3
 */
class GlossaryTaxonomy {
	/**
	 * Post type the taxonomy is attached to.
	 * @var string
	 */
	public $post_type = 'docs';

	/**
	 * Taxonomy slug.
	 * @var string
	 */
	public $taxonomy = 'glossaries';

	public function __construct() {
		add_action( 'init', [ $this, 'register' ], 9 );

		add_action( "{$this->taxonomy}_add_form_fields", [ $this, 'add_glossary_term_fields' ] );
		add_action( "{$this->taxonomy}_edit_form_fields", [ $this, 'edit_glossary_term_fields' ] );
		add_action( "created_{$this->taxonomy}", [ $this, 'save_glossary_term_fields' ] );
		add_action( "edited_{$this->taxonomy}", [ $this, 'save_glossary_term_fields' ] );
		add_filter( "manage_edit-{$this->taxonomy}_columns", [ $this, 'add_glossary_custom_column' ] );
		add_filter( "manage_{$this->taxonomy}_custom_column", [ $this, 'manage_glossary_custom_column' ], 10, 3 );

		// Hide default description field for glossaries taxonomy
		add_action( 'admin_head', [ $this, 'hide_glossaries_default_description' ] );
	}

	/**
	 * Register the taxonomy, gated on the `enable_glossaries` setting.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! betterdocs()->settings->get( 'enable_glossaries', false ) ) {
			return;
		}

		$this->register_glossaries_taxonomy();
	}

	public function register_glossaries_taxonomy() {
		$encyclopedia_root_slug = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopedia' );

		$labels = [
			'name'              => __( 'Glossaries Terms', 'betterdocs-pro' ),
			'singular_name'     => __( 'Glossaries Term', 'betterdocs-pro' ),
			'all_items'         => __( 'Glossaries Terms', 'betterdocs-pro' ),
			'parent_item'       => __( 'Parent Glossaries Term', 'betterdocs-pro' ),
			'parent_item_colon' => __( 'Parent Glossaries Term:', 'betterdocs-pro' ),
			'edit_item'         => __( 'Edit Term', 'betterdocs-pro' ),
			'update_item'       => __( 'Update Glossary', 'betterdocs-pro' ),
			'add_new_item'      => __( 'Add New Glossaries Term', 'betterdocs-pro' ),
			'new_item_name'     => __( 'New Glossaries Term Name', 'betterdocs-pro' ),
			'menu_name'         => __( 'Glossaries', 'betterdocs-pro' )
		];

		$args = [
			'hierarchical'      => true,
			'public'            => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'has_archive'       => true,
			'rewrite'           => [
				'slug'       => $encyclopedia_root_slug,
				'with_front' => false,
			],
			'capabilities'      => [
				'manage_terms' => 'manage_doc_terms',
				'edit_terms'   => 'edit_doc_terms',
				'delete_terms' => 'delete_doc_terms',
				'assign_terms' => 'edit_docs'
			]
		];

		// Register the custom taxonomy
		register_taxonomy( $this->taxonomy, [ $this->post_type ], $args );

		// Customize rewrite rules for the custom taxonomy
		global $wp_rewrite;
		$wp_rewrite->extra_permastructs[ $this->taxonomy ]['struct'] = '/' . $encyclopedia_root_slug . '/%' . $this->taxonomy . '%';

		// Flush rewrite rules to ensure the new structure takes effect
		add_action( 'init', 'flush_rewrite_rules', 999 );
	}

	public function add_glossary_term_fields( $taxonomy ) {
		?>
		<div class="form-field term-custom-field-wrap">
			<label for="glossary_term_description"><?php esc_html_e( 'Glossary Term Description', 'betterdocs-pro' ); ?></label>
			<textarea
				name="glossary_term_description"
				id="glossary_term_description"
				rows="5"
				cols="50"
				class="large-text"
				placeholder="<?php esc_attr_e( 'Enter a description for the glossary term', 'betterdocs-pro' ); ?>"
			></textarea>
			<p class="description"><?php esc_html_e( 'Enter a description for the glossary term', 'betterdocs-pro' ); ?></p>
		</div>
		<?php wp_nonce_field( 'save_glossary_term_description', 'glossary_term_description_nonce' ); ?>
		<?php
	}

	public function edit_glossary_term_fields( $term ) {
		$glossary_term_description = get_term_meta( $term->term_id, 'glossary_term_description', true );
		?>
		<tr class="form-field term-custom-field-wrap">
			<th scope="row"><label for="glossary_term_description"><?php esc_html_e( 'Glossary Term Description', 'betterdocs-pro' ); ?></label></th>
			<td>
				<?php
				wp_editor(
					$glossary_term_description,
					'glossary_term_description',
					[
						'textarea_name' => 'glossary_term_description',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'tinymce'       => true,
						'quicktags'     => true,
					]
				);
				wp_nonce_field( 'save_glossary_term_description', 'glossary_term_description_nonce' );
				?>
				<p class="description"><?php esc_html_e( 'Enter a description for the glossary term', 'betterdocs-pro' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public function save_glossary_term_fields( $term_id ) {
		// Check if we're in admin and this is a glossaries taxonomy operation
		if ( ! is_admin() ) {
			return;
		}

		// Verify this is for glossaries taxonomy
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
		if ( $taxonomy !== $this->taxonomy ) {
			return;
		}

		// Verify nonce for security
		$nonce = isset( $_POST['glossary_term_description_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['glossary_term_description_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'save_glossary_term_description' ) ) {
			return;
		}

		// Check if 'glossary_term_description' is set in $_POST
		if ( isset( $_POST['glossary_term_description'] ) ) {
			// Sanitize the content using wp_kses_post
			$description = wp_kses_post( wp_unslash( $_POST['glossary_term_description'] ) );

			// Save the sanitized value to term meta
			update_term_meta( $term_id, 'glossary_term_description', $description );
		}
	}

	public function add_glossary_custom_column( $columns ) {
		$columns['glossary_term_description'] = __( 'Glossary Term Description', 'betterdocs-pro' );
		return $columns;
	}

	public function manage_glossary_custom_column( $content, $column_name, $term_id ) {
		if ( $column_name === 'glossary_term_description' ) {
			$content = get_term_meta( $term_id, 'glossary_term_description', true );
		}
		return $content;
	}

	/**
	 * Hide default description field for glossaries taxonomy
	 */
	public function hide_glossaries_default_description() {
		$screen = get_current_screen();
		if ( $screen && $screen->taxonomy === $this->taxonomy ) {
			?>
			<style type="text/css">
				.term-description-wrap,
				.form-field.term-description-wrap {
					display: none !important;
				}
			</style>
			<?php
		}
	}
}
