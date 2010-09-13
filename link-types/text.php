<?php

class cflk_link_text extends cflk_link_base {
	function __construct() {
		parent::__construct('text', __('Text Only', 'cf-links'));
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
	
	/**
	 * Admin info display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_display($data) {
		return;
	}
	
	function admin_form($data) {
		return;
	}
	
	function update($data) {
		return $data;
	}
}
cflk_register_link('cflk_link_text');

?>