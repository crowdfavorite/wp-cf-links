<?php

class cflk_link_category extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('Category', 'cf-links');
		parent::__construct('category', $this->type_display);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-category-id']) || !empty($data['link'])) {
			$category_id = '';
			if (!empty($data['cflk-category-id'])) {
				$category_id = intval($data['cflk-category-id']);
			}
			else if (!empty($data['link'])) {
				$category_id = intval($data['link']);
			}
			
			if (!empty($category_id)) {
				$category = get_category($category_id);
				$data['link'] = get_category_link($category_id);
				$data['title'] = $category->name;
			}
			else {
				$data['link'] = '';
				$data['title'] = '';
			}
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Category', 'cflk-links');
		}
		return parent::display($data);
	}
	
	function admin_display($data) {
		$description = $title = $details = '';
		
		if (!empty($data['cflk-category-id'])) {
			$details = get_category($data['cflk-category-id']);
		}
		else if (!empty($data['link'])) {
			$details = get_category($data['link']);
		}
		
		if (!empty($details)) {
			$description = $details->name;
		}
		
		if (!empty($data['title'])) {
			$title = $data['title'];
		}
		else {
			$title = $description;
		}
		
		return array(
			'title' => $title,
			'description' => $description
		);
	}

	function admin_form($data) {
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-categories',
			'name' => 'cflk-category-id',
			'selected' => (!empty($data['cflk-category-id']) ? intval($data['cflk-category-id']) : (!empty($data['link']) ? intval($data['link']) : 0)),
			'class' => 'elm-select'
		);

		return '
			<div class="elm-block">
				<label>'.__('Link', 'cf-links').'</label>
				'.wp_dropdown_categories($args).'
			</div>
		';
	}

	function type_display() {
		return $this->type_display;
	}

	function update($data) {
		$data['link'] = $data['cflk-category-id'];
		return $data;
	}
}
cflk_register_link('cflk_link_category');

?>