<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor;

use Elementor\Widget_Base;
use WPDeveloper\BetterDocs\Traits\EditorHelper;

/**
 * @method render_editor_script()
 */
abstract class BaseWidget extends Widget_Base {
	use EditorHelper;

	public function __construct( $data = [], $args = null ) {
		// $is_type_instance = $this->is_type_instance();
		// dump( $is_type_instance, $data, $args );

		// if( $is_type_instance && $args !== null ) {
		//     $data['settings']['nested_posts_per_page'] = 5;
		// }

		parent::__construct( $data, $args );

		// if( $is_type_instance && $args !== null ) {
		//     // $this->set_settings('nested_posts_per_page', 5);
		//     // $this->query = betterdocs()->query;
		// }
	}

	public function has_widget_inner_wrapper():bool {
		return false;
	}

	public function betterdocs_do_action() {
		/**
		 * Query  Controls!
		 * @source includes/elementor-helper.php
		 */
		do_action( 'betterdocs/elementor/widgets/query', $this, 'doc_category' );
	}

	abstract protected function render_callback();

	protected function render() {
		$settings         = $this->get_settings_for_display();
		$this->attributes = &$settings;

		$this->reset_attributes();

		do_action_ref_array( 'betterdocs_before_render', [ &$this, 'elementor' ] );

		$this->render_callback();

		do_action_ref_array( 'betterdocs_after_render', [ &$this, 'elementor' ] );
	}
}
