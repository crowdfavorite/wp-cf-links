<?php

class cflk_list {
	protected $list;
	protected $args;
	protected $current_list;
	protected $link_types;
	
	protected $hierarchal_data;
	
	function __construct($list, $args, $link_types) {
		$this->list = $list;
		$this->args = $args;
		$this->current_list = $this->list['key'];
		$this->link_types = $link_types;
		$this->hierarchical_data = $this->format_hierarchical_list($list['data']);
	}
	
	/**
	 * Build the actual list
	 * Preformats data as hierarchal data before building
	 *
	 * @param array $list 
	 * @param array $args 
	 * @param int $level 
	 * @return string html
	 */
	function display() {
		$html = $this->build_list_recursive($this->hierarchical_data, $this->args);
		return $html;
	}
	
	/**
	 * Return a default set of wrappers with filter applied
	 *
	 * @param string $list_key 
	 * @param string $args 
	 * @param string $level 
	 * @return void
	 */
	function get_wrappers($list_key, $args, $level) {
		$defaults = array(
			'parent_before' => '<ul class="{parent_class}">',
			'parent_after' => '</ul>',
			'child_before' => '<li class="{child_class}">',
			'child_after' => '</li>'
		);
		
		// legacy data handling: honor before & after for parent wrappers
		if ($level == 0 && isset($args['before']) && isset($args['after'])) {
			$defaults['parent_before'] = $args['before'];
			$defaults['parent_after'] = $args['after'];
		}
		if ($level > 0 && isset($args['child_before']) && isset($args['child_after'])) {
			$defaults['parent_before'] = $args['child_before'];
			$defaults['parent_after'] = $args['child_after'];
		}
		
		// parse in classes
		// pass in classes
		return apply_filters('cflk_wrappers', $defaults, $list_key, $args, $level);
	}
	
	public function apply_class($item, $class) {
		return preg_replace('/(\{.*?\})/', $class, $item);
	}
	
	/**
	 * Recursively build list items
	 *
	 * @todo needing recursive action takes the item wrapper away from the item
	 *
	 * @param array $items 
	 * @return string html
	 */
	function build_list_recursive($items, $args, $level = 0) {
		// @TODO run this after figuring out the classes, pass in classes
		$wrappers = $this->get_wrappers($this->current_list, $args, $level);
		$ret = '';
		
		foreach ($items as $key => $item) {
			$wrapper_class = array(
				'cflk-item-level-'.$level,					
				);
			
			// see if we're first or last
			if (!isset($items[$key-1])) {
				$wrapper_class[] = ' cflk-first';
			}
			elseif (!isset($items[$key+1])) {
				$wrapper_class[] = 'cflk-last';
			}				
			
			$item['class'] .= ' a-level-'.$level;
			$item['list_id'] = $this->current_list;
			
			if (!empty($item['opennew'])) {
				$item['class'] .= ' cflk-opennewwindow';
				add_action('wp_footer',array($this,'footer_js'));
			}
			
			$ret .= $this->build_item_recursive($item, $wrappers, $wrapper_class);
		}
		
		$ret .= $wrappers['parent_after'];

		return apply_filters('cflk_list_html', $ret, $items, $args, $level);
	}
	
	function build_item_recursive($item, $wrappers, $wrapper_class) {
		// This filter is so we can get in before the display of the item, and mess with its data.  Perhaps changing the type, or something similar
		$item = apply_filters('cflk_get_links_data', $item);
		$link_data = $this->link_types[$item['type']]->_display($item);
		$ret .= $this->apply_class($wrappers['child_before'], implode(' ', $wrapper_class).$item['class'].' '.$link_data['class']).$link_data['link'];
		if (isset($item['children'])) {
			$ret .= $this->build_list_recursive($item['children'], $args, $item['level']++);
		}
		$ret .= $wrappers['child_after'];
		return apply_filters('cflk_list_item_html', $ret, $item, $wrappers, $wrapper_class);
	}
	
	/**
	 * Takes $links['data'] and formats it as a hierarchal array. 
	 * Useful for when the links data is being used for outside purposes.
	 * It falls down on the parents aspect when dealing with links that are not IDs, 
	 * but still works nicely
	 *
	 * @example $hierarchal = format_heirarchal_list($links['data']);
	 * @param array $links 
	 * @param int $level 
	 * @param int $start 
	 * @return array
	 */
	function format_hierarchical_list(&$links, $level = 0, $start = 0, $ancestors = array()) {
		$parent = null;
		if ($start > 0 ) {
			array_push($ancestors,$links[($start-1)]['link']);
			$parent = $links[($start-1)]['link'];
		}

		$ret = array();
		for ($i = $start; $i < count($links); $i++) {
			$current = $i;
			if ($links[$i]['level'] == $level) {
				$links[$i]['ancestors'] = $ancestors;
				$links[$i]['parent'] = $parent;
				$ret[$i] = $links[$i];

				// go deeper or stop
				if ($links[$i+1]['level'] > $level) {
					$children = format_hierarchical_list($links, $links[$i+1]['level'], $i+1, $ancestors);
					$ret[$i]['children'] = $children['ret'];
					$i = $children['i'];
				}
				elseif ($links[$i+1]['level'] < $level) {
					break;
				}
			}
			if (!isset($links[$i]['class'])) {
				$links[$i]['class'] = '';
			}
		}

		if ($i == count($links) && $start == 0) {
			return $ret;
		}
		else {
			return array('ret' => $ret, 'i' => $i);
		}
	}
	
	function footer_js() {
		?>
		<script type="text/javascript">
			;(function($) {
				$(function() {
					$(".cflk-opennewwindow a").live('click', function() {
						window.open($(this).attr('href'));
						return false;
					});
				});
			})(jQuery);
		</script>
		<?php
	}
}

?>