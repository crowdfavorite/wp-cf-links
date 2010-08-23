<?php

class cflk_link_page extends cflk_link_base {
	function __construct() {
		parent::__construct('page', __('Page', 'cf-links'));
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-page-id'])) {
			$page_id = intval($data['cflk-page-id']);
			$data['link'] = get_permalink($page_id);
			$data['title'] = get_the_title($page_id);
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Page', 'cflk-links');
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
		if (!empty($data['cflk-page-id'])) {
			$page = get_the_title($data['cflk-page-id']);
		}
		else {
			$page = __('Unknown Page', 'cflk-links');
		}
		return '
			<div>
				'.__('Page:', 'cf-links').' <span class="link">'.esc_html($page).'</span>
			</div>
			';
	}
	
	function admin_display_type() {
		return $this->name;
	}
	
	function admin_form($data) {
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-pages',
			'name' => 'cflk-page-id',
			'selected' => (!empty($data['cflk-page-id']) ? intval($data['cflk-page-id']) : 0) 
		);
		$pages = wp_dropdown_pages($args);
		return '
			<div>
				'.__('Page: ', 'cf-links').$pages.'
			</div>
			';
	}
	
	function update($data) {
		$data['link'] = get_permalink($data['cflk-page-id']);
		return $data;
	}
}
cflk_register_link('page','cflk_link_page');

?>