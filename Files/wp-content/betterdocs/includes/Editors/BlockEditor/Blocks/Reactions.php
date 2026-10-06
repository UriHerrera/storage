<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

use function cli\err;

class Reactions extends Block {

	protected $editor_scripts = [
		'betterdocs-reactions'
	];

	protected $editor_styles = [
		'betterdocs-reactions'
	];

	protected $frontend_scripts = [
		'betterdocs-reactions'
	];
	protected $frontend_styles  = [
		'betterdocs-reactions'
	];

	public function get_name() {
		return 'reactions';
	}

	public function get_default_attributes() {
		return [
			'blockId'           => 'layout-1',
			'reaction_text'     => __( 'What are your feelings', 'betterdocs' ),
			'reaction_text_tag' => 'h4',
			'layout'            => 'layout-1',
			'show_happy_icon'   => true,
			'happy_icon_url'    => '',
			'show_neutral_icon' => true,
			'neutral_icon_url'  => '',
			'show_sad_icon'     => true,
			'sad_icon_url'      => ''
		];
	}

	public function render( $attributes, $content ) {
		$layout = $attributes['layout'];
		if ( $layout == 'layout-1' ) {
			$this->views( 'widgets/reactions' );
		} elseif ( $layout == 'layout-2' ) {
			$this->views( 'widgets/reactions-2' );
		} else {
			$this->views( 'widgets/reactions-3' );
		}
	}

	public function view_params() {
		$wrapper_class = [
			'class' => []
		];

		$default_params = [
			'reactions_text' => $this->attributes['reaction_text'],
			'text_tag'       => $this->attributes['reaction_text_tag'],
			'wrapper_attr'   => &$wrapper_class
		];

		if ( $this->attributes['layout'] == 'layout-1' ) {
			$wrapper_class['class'][] = 'betterdocs-article-reactions';
			$default_params['happy']       = $this->attributes['show_happy_icon'];
			$default_params['happy_icon']  = $this->attributes['happy_icon_url'];
			$default_params['normal']      = $this->attributes['show_neutral_icon'];
			$default_params['normal_icon'] = $this->attributes['neutral_icon_url'];
			$default_params['sad']         = $this->attributes['show_sad_icon'];
			$default_params['sad_icon']    = $this->attributes['sad_icon_url'];
		}

		if ( $this->attributes['layout'] == 'layout-2' ) {
			$default_params['happy']       = $this->attributes['show_happy_icon'];
			$default_params['happy_icon']  = $this->attributes['happy_icon_url'];
			$default_params['normal']      = $this->attributes['show_neutral_icon'];
			$default_params['normal_icon'] = $this->attributes['neutral_icon_url'];
			$default_params['sad']         = $this->attributes['show_sad_icon'];
			$default_params['sad_icon']    = $this->attributes['sad_icon_url'];
		}

		if ( $this->attributes['layout'] == 'layout-3' ) {
			$default_params['happy']       = $this->attributes['show_happy_icon'];
			$default_params['happy_icon']  = $this->attributes['happy_icon_url'];
			$default_params['normal']      = $this->attributes['show_neutral_icon'];
			$default_params['normal_icon'] = $this->attributes['neutral_icon_url'];
			$default_params['sad']         = $this->attributes['show_sad_icon'];
			$default_params['sad_icon']    = $this->attributes['sad_icon_url'];
			$wrapper_class['class'][]      = 'betterdocs-article-reactions-blocks';
		}

		return $default_params;
	}
}
