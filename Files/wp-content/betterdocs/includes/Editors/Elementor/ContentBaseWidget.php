<?php

namespace WPDeveloper\BetterDocs\Editors\Elementor;

use WPDeveloper\BetterDocs\Traits\EditorHelper;
use ElementorPro\Modules\ThemeBuilder\Widgets\Post_Content;

/**
 * @method render_editor_script()
 */
abstract class ContentBaseWidget extends Post_Content {
	use EditorHelper;

	abstract protected function render_callback();

	protected function render() {
		$settings         = $this->get_settings_for_display();
		$this->attributes = &$settings;

		$this->render_callback();
	}
}
