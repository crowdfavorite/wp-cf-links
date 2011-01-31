<?php

class cflk_link_author extends cflk_link_base {
	public $type_display = '';
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

			if (!empty($author_id) && $this->author_exists($author_id)) {
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
			$data['title'] = __('Unknown Author', 'cf-links');
		}
		return parent::display($data);
	}

	function admin_display($data) {
		$title = $description = $details = $id = '';
		
		if (!empty($data['cflk-author-id'])) {
			$id = $data['cflk-author-id'];
		}
		else if (!empty($data['link'])) {
			$id = $data['link'];
		}
		
		if (!empty($id) && $this->author_exists($id)) {
			$description = get_author_name($id);
		}
		else {
			return array(
				'title' => __('Missing Author ID: ', 'cf-links').$id,
				'description' => __('The Author ID is missing for this link item', 'cf-links')
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
		global $cflk_links;
		$author_id = (!empty($data['cflk-author-id']) ? intval($data['cflk-author-id']) : (!empty($data['link']) ? intval($data['link']) : 0));
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-authors',
			'name' => 'cflk-author-id',
			'selected' => $author_id,
			'include' => $cflk_links->get_authors(),
			'class' => 'elm-select'
		);

		if ($author_id == 0) {
			$args['show_option_all'] = __('Select an author from the list below', 'cf-links');
		}
		
		$dropdown = wp_dropdown_users($args);
		
		if (!$this->author_exists($author_id) && $author_id != 0) {
			$dropdown = str_replace('</select>', '', $dropdown);
			
			$dropdown .= '<option value="'.$author_id.'" selected="selected">'.__('Author ID: ', 'cf-links').$author_id.__(' does not exist', 'cf-links').'</option>';
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
		if (!empty($data['cflk-author-id'])) {
			$data['link'] = $data['cflk-author-id'];
		}
		return $data;
	}

	function author_exists($id) {
		if ($id != 0) {
			$details = get_author_name($id);
			if (!empty($details)) {
				return true;
			}
		}
		return false;
	}
}
cflk_register_link('cflk_link_author');

?>