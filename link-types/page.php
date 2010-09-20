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
			
			if (!empty($page_id)) {
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
			$details = get_the_title($data['cflk-page-id']);
		}

		if (!empty($details)) {
			$description = $details;
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
			'id' => 'cflk-dropdown-pages',
			'name' => 'cflk-page-id',
			'selected' => (!empty($data['cflk-page-id']) ? intval($data['cflk-page-id']) : (!empty($data['link']) ? intval($data['link']) : 0)),
			'class' => 'elm-select'
		);

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
		$data['link'] = $data['cflk-page-id'];
		return $data;
	}
}
cflk_register_link('cflk_link_page');

?>