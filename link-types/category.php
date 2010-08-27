<?php

class cflk_link_category extends cflk_link_base {
	function __construct() {
		parent::__construct('category', __('Category', 'cf-links'));
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-category-id'])) {
			$category_id = intval($data['cflk-category-id']);
			$category = get_category($category_id);
			$data['link'] = get_category_link($category_id);
			$data['title'] = $category->name;
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Category', 'cflk-links');
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
		if (!empty($data['cflk-category-id'])) {
			$category = get_category($data['cflk-category-id']);
			$title = $category->name;
		}
		else {
			$title = __('Unknown Category', 'cflk-links');
		}
		return '
			<div>
				'.__('Category:', 'cf-links').' <span class="link">'.esc_html($title).'</span>
			</div>
			';
	}
	
	function admin_form($data) {
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-categories',
			'name' => 'cflk-category-id',
			'selected' => (!empty($data['cflk-category-id']) ? intval($data['cflk-category-id']) : 0) 
		);
		$categories = wp_dropdown_categories($args);
		return '
			<div>
				'.__('Categories: ', 'cf-links').$categories.'
			</div>
			';
	}
	
	function update($data) {
		$data['link'] = $data['cflk-category-id'];
		return $data;
	}
}
cflk_register_link('category','cflk_link_category');

?>