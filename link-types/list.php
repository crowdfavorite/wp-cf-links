<?php

class cflk_link_list extends cflk_link_base {
	function __construct() {
		parent::__construct('list', __('List', 'cf-links'));
		add_filter('cflk_list_item_html', array($this, 'item_html'), 10, 4);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		global $cflk_links;
		if (empty($data['cflk-list-id']) || !$cflk_links->is_valid_list($data['cflk-list-id'])) {
			$data['link'] = '';
			$data['type'] = 'blank';
			$data['title'] = __('Unknown List', 'cflk-links');
		}
		
		return parent::display($data);
	}
	
	function item_html($ret, $item, $wrappers, $wrapper_class) {
		if ($item['type'] == 'list') {
			global $cflk_links;
			$list = $cflk_links->get_list($item['cflk-list-id'], array());
			if ($list != false) {
				return $list->build_list_recursive($list->hierarchical_data, array('before' => '', 'after' => ''));
			}
		}

		return $ret;
	}
	
	/**
	 * Admin info display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_display($data) {
		global $cflk_links;
		if (!empty($data['cflk-list-id']) && $data['cflk-list-id'] != $data['list_key'] && $cflk_links->is_valid_list($data['cflk-list-id'])) {
			$info = $this->get_info($data['cflk-list-id']);
			$title = $info['nicename'];
		}
		else {
			$title = __('Unknown List', 'cf-links');
		}
		
		return '
			<div>
				'.__('List:', 'cf-links').' <span class="link">'.esc_html($title).'</span>
			</div>
			';
	}
	
	function admin_form($data) {
		global $cflk_links;
		if (!$cflk_links->is_valid_list($data['cflk-list-id'])) {
			$data['cflk-list-id'] = 0;
		}
		$list = $this->get_dropdown((!empty($data['cflk-list-id']) ? $data['cflk-list-id'] : 0), $data['list_key']);
		return '
			<div>
				'.__('List: ', 'cf-links').$list.'
			</div>
			';
	}
	
	function update($data) {
		if ($data['cflk-list-id'] == $data['list_key']) {
			$data['link'] = 0;
			$data['cflk-list-id'] = 0;
		}
		else {
			$data['link'] = $data['cflk-list-id'];
		}
		return $data;
	}
	
	function get_info($id) {
		global $cflk_links;
		$lists = $cflk_links->get_all_lists_for_blog();
		if (is_array($lists) && !empty($lists) && !empty($lists[$id])) {
			return $lists[$id];
		}
		return false;
	}
	
	function get_dropdown($selected, $current_key) {
		global $cflk_links;
		$html = '<select id="cflk-dropdown-list" name="cflk-list-id">';
		$html .= '<option value="0"'.selected($selected, 0, false).'>'.__('--Select a list below--', 'cf-links').'</option>';
		$lists = $cflk_links->get_all_lists_for_blog();
		if (is_array($lists) && !empty($lists)) {
			foreach ($lists as $key => $info) {
				if ($current_key == $key) { continue; }
				$html .= '<option value="'.$key.'"'.selected($selected, $key, false).'>'.$info['nicename'].'</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}
}
cflk_register_link('list','cflk_link_list');

?>