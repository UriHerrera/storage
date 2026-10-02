<?php
namespace WPDeveloper\BetterDocs\Utils;

class Node {
	public $title;
	public $tag;
	public $tag_number;
	public $key;
	/**
	 * Summary of $items
	 * @var array
	 */
	public $items;

	public function print( $items, $toc_hierarchy, $level ) {
		$html = '';
		if ( ! empty( $items ) ) {
			$toc_hierarchy_class = ( $toc_hierarchy != 'off' && $toc_hierarchy != '' ) ? 'toc-list betterdocs-hierarchial-toc' : 'toc-list';
			$html               .= $level === 1 ? '<ul class = "' . $toc_hierarchy_class . '">' : '<ul class = "betterdocs-toc-list-level-' . $level . '">';
			foreach ( $items as $item ) {
				$html .= '<li class = "betterdocs-toc-heading-level-' . ( $item->key ) . '">';
				$html .= '<a href="#' . esc_attr( $item->tag_number ) . '">' . esc_html( wp_strip_all_tags( $item->title ) ) . '</a>';
				if ( ! empty( $items ) ) {
					$html .= $this->print( $item->items, $toc_hierarchy, ++$level );
				} else {
					$level = 1;
					break;
				}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}
		return $html;
	}
}
