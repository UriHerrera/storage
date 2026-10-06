<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Core\Shortcode;

class Reactions extends Shortcode {
	public $view_wrapper = 'betterdocs-article-reactions';

	public function get_name() {
		return 'betterdocs_article_reactions';
	}

	public function get_style_depends() {
		return [ 'betterdocs-reactions' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-reactions' ];
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return [
			'text'        => '',
			'text_tag'    => 'h5',
			'layout'      => 'layout-1',
			'happy'       => true,
			'happy_icon'  => '',
			'normal'      => true,
			'normal_icon' => '',
			'sad'         => true,
			'sad_icon'    => '',
		];
	}

	public function generate_attributes() {
		$attributes = [
			'class' => [
				$this->attributes['layout']
			]
		];

		return $attributes;
	}

	/**
	 * Summary of render
	 *
	 * @param mixed $atts
	 * @param mixed $content
	 * @return mixed
	 */
	public function render( $atts, $content = null ) {
		$args = [
			'happy'       => $atts['happy'],
			'happy_icon'  => $atts['happy_icon'],
			'normal'      => $atts['normal'],
			'normal_icon' => $atts['normal_icon'],
			'sad'         => $atts['sad'],
			'sad_icon'    => $atts['sad_icon']
		];

		if ( isset( $atts['layout'] ) && $atts['layout'] == 'layout-2' ) {
			$this->views( 'widgets/reactions-2', $args );
		} elseif ( isset( $atts['layout'] ) && $atts['layout'] == 'layout-3' ) {
				$this->views( 'widgets/reactions-3', $args );
		} else {
			$this->views( 'widgets/reactions', $args );
		}
	}

	public function view_params() {
		$wrapper_attr   = $this->generate_attributes();
		$reactions_text = ! empty( $this->attributes['text'] ) ? $this->attributes['text'] : $this->customizer->get(
			'betterdocs_post_reactions_text',
			__( 'What are your Feelings', 'betterdocs' )
		);
		$text_tag = ! empty( $this->attributes['text_tag'] ) ? $this->attributes['text_tag'] : $this->customizer->get(
			'betterdocs_reactions_title_tag',
			'h5'
		);

		return [
			'wrapper_attr'   => $wrapper_attr,
			'reactions_text' => $reactions_text,
			'text_tag'       => $text_tag
		];
	}
}
