<?php

/**
 * Basic link is:
 * - editable href
 * - editable link text
 *
 * Follows object model as CF Post Meta
 *
 * @package default
 */
class cflk_link extends cflk_link_base {
	public $type_display = '';
	function __construct() {
		$this->type_display = __('URL', 'cf-links');
		parent::__construct('url', $this->type_display);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		return parent::display($data);
	}
	
	function admin_display($data) {
		$description = $title = '';
		
		if (!empty($data['link'])) {
			$description = $data['link'];
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
		return '
			<div class="elm-block elm-width-330">
				<label>'.__('Link (include', 'cf-links').' <code>http://</code>)</label>
				<input type="text" name="link" value="'.strip_tags(stripslashes($data['link'])).'" class="elm-text" />
			</div>
		';		
	}

	function type_display() {
		return $this->type_display;
	}
	
	function update($data) {
		// optional: process data before it hits the DB
		return $data;
	}
}
cflk_register_link('cflk_link');

?>