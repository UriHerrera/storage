<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Core\Shortcode;

class ReadingTime extends Shortcode {
	public function get_name() {
		return 'betterdocs_reading_time';
	}

	public function get_style_depends() {
		return [ 'reading-time' ];
	}

	public function default_attributes() {
		return [
			'reading_title'         => '',
			'reading_text'          => __( 'min read', 'betterdocs' ),
			'singular_reading_text' => __( 'min read', 'betterdocs' ),
			'content'               => __( 'Reading Time', 'betterdocs' ),
			'the_content'           => get_the_content()
		];
	}

	public function render( $atts, $content = null ) {
		$this->views( 'shortcodes/reading-time' );
	}

	public function view_params() {
		$single_doc_content        = $this->attributes['the_content'];
		$content_without_html_tags = wp_strip_all_tags( $single_doc_content );
		preg_match_all( '/<[^>]*>|[\p{L}\p{M}]+/u', $content_without_html_tags, $matches );
		$reading_total_words = ! empty( $matches[0] ) ? count( $matches[0] ) : count( [] );

		//lets assume an adult reads 200 words per minute, based on this information calculate the reading time
		$minutes = floor( $reading_total_words / 200 );
		$seconds = floor( $reading_total_words % 200 / ( 200 / 60 ) );
		$minutes = $minutes != 0 && $seconds >= 30 ? ( $minutes + 1 ) : ( $minutes != 0 && $minutes != 1 && $seconds < 30 ? ( $minutes - 1 ) : ( $minutes == 1 ? $minutes : '< ' . 1 ) );
		$post_id = get_the_ID();

		$est_reading_text = ! empty( get_post_meta( $post_id, '_betterdocs_est_reading_text', true ) ) ? get_post_meta( $post_id, '_betterdocs_est_reading_text', true ) : '';
		$calculate_time   = $minutes . ' ' . ( (int) $minutes <= 1 ? $this->attributes['singular_reading_text'] : $this->attributes['reading_text'] );
		$calculate_time   = ! empty( $est_reading_text ) ? $est_reading_text : $calculate_time;

		return [
			'reading_title' => $this->attributes['reading_title'],
			'time'          => $calculate_time
		];
	}
}
