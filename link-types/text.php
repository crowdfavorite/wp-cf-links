<?php

class cflk_link_text extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('Text Only', 'cf-links');
		parent::__construct('text', $this->type_display);
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
		
		if (!empty($data['title'])) {
			$description = $title = $data['title'];
		}
		
		return array(
			'title' => $title,
			'description' => $description
		);
	}	

	function admin_form($data) {
		return;
	}

	function type_display() {
		return $this->type_display;
	}
	
	function update($data) {
		return $data;
	}
}
cflk_register_link('cflk_link_text');

?>