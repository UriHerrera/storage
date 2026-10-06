<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

class FaqClassic extends FaqList {
	protected $layout    = 'classic';
	protected $icon_hook = 'betterdocs_faq_post_before';

	public function get_name() {
		return 'betterdocs_faq_list_classic';
	}

	public function icons( $faq_toggle ) {
		$faq_markup  = '<div class="betterdocs-faq-post-icon-group">';
		$faq_markup .= '<svg class="betterdocs-faq-iconplus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'.($faq_toggle ? " style='display:none;'" : "").'><path fill="#000000" d="M18 10h-4V6h-4v4H6v4h4v4h4v-4h4"></path></svg>';
		$faq_markup .= '<svg class="betterdocs-faq-iconminus" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'.($faq_toggle ? " style='display:inline;'" : "").'><path fill="#000000" d="M6 10h12v4H6z"></path></svg>';
		$faq_markup .= '</div>';

		echo $faq_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
