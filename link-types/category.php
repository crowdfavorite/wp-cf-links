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
			
			if (!empty($category_id) && $this->category_exists($category_id)) {
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
			$data['title'] = __('Unknown Category', 'cf-links');
		}
		return parent::display($data);
	}
	
	function admin_display($data) {
		$description = $title = $details = $id = '';
		
		if (!empty($data['cflk-category-id'])) {
			$id = $data['cflk-category-id'];
		}
		else if (!empty($data['link'])) {
			$id = $data['link'];
		}
		
		if (!empty($id) && $this->category_exists($id)) {
			$details = get_category($id);
		}
		else {
			return array(
				'title' => __('Missing Category ID: ', 'cf-links').$id,
				'description' => __('The Category ID is missing for this link item', 'cf-links')
			);
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
		$id = (!empty($data['cflk-category-id']) ? intval($data['cflk-category-id']) : (!empty($data['link']) ? intval($data['link']) : 0));
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-categories',
			'name' => 'cflk-category-id',
			'selected' => $id,
			'class' => 'elm-select'
		);
		
		if ($id == 0) {
			$args['show_option_all'] = __('Select an author from the list below', 'cf-links');
		}
		
		$dropdown = wp_dropdown_categories($args);

		if (!$this->category_exists($id) && $id != 0) {
			$dropdown = str_replace('</select>', '', $dropdown);
			
			$dropdown .= '<option value="'.$id.'" selected="selected">'.__('Category ID: ', 'cf-links').$id.__(' does not exist', 'cf-links').'</option>';
			$dropdown .= '</select>';
		}

		return '
			<div class="elm-block">
				<label>'.__('Link', 'cf-links').'</label>
				'.$dropdown.'
			</div>
		';
	}

	function type_display() {
		return $this->type_display;
	}

	function update($data) {
		if (!empty($data['cflk-category-id'])) {
			$data['link'] = $data['cflk-category-id'];
		}
		return $data;
	}
	
	function category_exists($id) {
		if ($id != 0 && $category = get_category($id)) {
			return true;
		}
		return false;
	}
}
cflk_register_link('cflk_link_category');

?>