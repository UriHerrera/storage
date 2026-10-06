<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

class FaqLayoutThree extends FaqList {
	protected $layout    = 'layout-3';
	protected $icon_hook = 'betterdocs_faq_post_after';

	public function get_name() {
		return 'betterdocs_faq_list_layout_3';
	}

	public function icons( $faq_toggle ) {
		$faq_markup  = '<svg class="betterdocs-faq-iconplus" width="21" height="20" viewBox="0 0 21 20"'.($faq_toggle ? " style='display:none;'" : "").' fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_8028_2975)">
                    <path d="M5.5 7.5L10.5 12.5L15.5 7.5" stroke="#707E95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_8028_2975">
                    <rect width="20" height="20" fill="white" transform="translate(0.5)"/>
                    </clipPath>
                    </defs>
                    </svg>';
		$faq_markup .= '<svg class="betterdocs-faq-iconminus" width="21" height="20" viewBox="0 0 21 20"'.($faq_toggle ? " style='display:inline;'" : "").' fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.5 12.5L10.5 7.5L5.5 12.5" stroke="#707E95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>';

		echo $faq_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
