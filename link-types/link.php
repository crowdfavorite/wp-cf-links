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
	function __construct() {
		parent::__construct('url', __('URL', 'cf-links'));
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
		return '
			<div>
				'.__('URL:', 'cf-links').' <span class="link">'.$data['link'].'</span>
			</div>
			';
	}
	
	/**
	 * Admin edit display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_form($data) {
		return '
			<div>
				<label>'.__($this->name.' (include <code>http://</code>)', 'cf-links').'</label>
				<input type="text" name="link" value="'.$data['link'].'" />
			</div>
			';		
	}
	
	function update($data) {
		// optional: process data before it hits the DB
		return $data;
	}
}
cflk_register_link('cflk_link');

?>