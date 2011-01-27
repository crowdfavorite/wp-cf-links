<?php

class cflk_link_page extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('Page', 'cf-links');
		parent::__construct('page', $this->type_display);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-page-id']) || !empty($data['link'])) {
			$page_id = '';
			if (!empty($data['cflk-page-id'])) {
				$page_id = intval($data['cflk-page-id']);
			}
			else if (!empty($data['link'])) {
				$page_id = intval($data['link']);
			}
			
			if (!empty($page_id) && $this->page_exists($page_id)) {
				$data['link'] = get_permalink($page_id);
				$data['title'] = get_the_title($page_id);
			}
			else {
				$data['link'] = '';
				$data['title'] = '';
			}
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Page', 'cflk-links');
		}
		return parent::display($data);
	}
	
	function admin_display($data) {
		$description = $title = $details = '';

		if (!empty($data['cflk-page-id'])) {
			$details = get_the_title($data['cflk-page-id']);
		}
		else if (!empty($data['link'])) {
			$details = get_the_title($data['link']);
		}

		if (!empty($details)) {
			$description = $details;
		}
		else {
			$page_id = 0;
			if (!empty($data['cflk-page-id'])) {
				$page_id = $data['cflk-page-id'];
			}
			else if (!empty($data['link'])) {
				$page_id = $data['link'];
			}
			return array(
				'title' => __('Missing Page ID: '.$page_id, 'cf-links'),
				'description' => __('The Page ID is missing for this link item', 'cf-links')
			);
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
		$page_id = (!empty($data['cflk-page-id']) ? intval($data['cflk-page-id']) : (!empty($data['link']) ? intval($data['link']) : 0));
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-pages',
			'name' => 'cflk-page-id',
			'selected' => $page_id,
			'class' => 'elm-select'
		);
	
		if (!$this->page_exists($page_id)) {
			$args['option_none_value'] = $page_id;
			if ($page_id != 0) {
				$args['show_option_none'] = __('Page ID: '.$page_id.' does not exist', 'cf-links');
			}
			else {
				$args['show_option_none'] = __('Select a page from the list below', 'cf-links');
			}
		}
	
		return '
			<div class="elm-block">
				<label>'.__('Link', 'cf-links').'</label>
				'.wp_dropdown_pages($args).'
			</div>
		';
	}

	function type_display() {
		return $this->type_display;
	}
	
	function update($data) {
		if (!empty($data['cflk-page-id'])) {
			$data['link'] = $data['cflk-page-id'];
		}
		return $data;
	}
	
	function page_exists($id) {
		if ($id != 0) {
			$details = get_the_title($id);
			if (!empty($details)) {
				return true;
			}
		}
		return false;
	}
}
cflk_register_link('cflk_link_page');

?>