<?php

class cflk_link_author extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('Author', 'cf-links');
		parent::__construct('author', $this->type_display);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-author-id']) || !empty($data['link'])) {
			$author_id = '';
			if (!empty($data['cflk-author-id'])) {
				$author_id = $data['cflk-author-id'];
			}
			else if (!empty($data['link'])) {
				$author_id = $data['link'];
			}

			if (!empty($author_id)) {
				$data['link'] = get_author_link(false, $author_id);
				$data['title'] = get_author_name($author_id);
			}
			else {
				$data['link'] = '';
				$data['title'] = '';
			}
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Author', 'cflk-links');
		}
		return parent::display($data);
	}

	function admin_display($data) {
		$title = $description = '';
		
		if (!empty($data['cflk-author-id'])) {
			$description = get_author_name($data['cflk-author-id']);
		}
		else if (!empty($data['link'])) {
			$description = get_author_name($data['link']);
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
		global $cflk_links;
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-authors',
			'name' => 'cflk-author-id',
			'selected' => (!empty($data['cflk-author-id']) ? intval($data['cflk-author-id']) : (!empty($data['link']) ? intval($data['link']) : 0)),
			'include' => $cflk_links->get_authors(),
			'class' => 'elm-select'
		);

		return '
			<div class="elm-block">
				<label>'.__('Link', 'cf-links').'</label>
				'.wp_dropdown_users($args).'
			</div>
		';
	}

	function type_display() {
		return $this->type_display;
	}
		
	function update($data) {
		$data['link'] = $data['cflk-author-id'];
		return $data;
	}
}
cflk_register_link('cflk_link_author');

?>