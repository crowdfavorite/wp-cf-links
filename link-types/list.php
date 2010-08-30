<?php

class cflk_link_list extends cflk_link_base {
	function __construct() {
		parent::__construct('list', __('List', 'cf-links'));
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-list-id'])) {
			$info = $this->get_info($data['cflk-list-id']);
			// $data['link'] = $info['link'];
			$data['title'] = $info['nicename'];
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown List', 'cflk-links');
		}
		return parent::display($data);
	}
	
	/**
	 * Admin info display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_display($data) {
		if (!empty($data['cflk-list-id'])) {
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
		$list = $this->get_dropdown((!empty($data['cflk-list-id']) ? $data['cflk-list-id'] : 0));
		return '
			<div>
				'.__('List: ', 'cf-links').$list.'
			</div>
			';
	}
	
	function update($data) {
		$data['link'] = $data['cflk-list-id'];
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
	
	function get_dropdown($selected) {
		global $cflk_links;
		$html = '<select id="cflk-dropdown-list" name="cflk-list-id">';
		$lists = $cflk_links->get_all_lists_for_blog();
		if (is_array($lists) && !empty($lists)) {
			foreach ($lists as $key => $info) {
				$html .= '<option value="'.$key.'"'.selected($selected, $key, false).'>'.$info['nicename'].'</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}
}
cflk_register_link('list','cflk_link_list');

?>