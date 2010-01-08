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
class cflk_link {
	protected $id_base = 'cflk-';
	public $id;
	public $name;
	
	function __construct() {
		$this->id = 'url';
		$this->name = __('URL','cf-links');
	}
	
// Process 
	/**
	 * Pre-process the data array
	 * Standard link has the href already, but a post type, for example, would need to generate the permalink here
	 *
	 * @param array $data 
	 * @return array
	 */
	function process($data) {
		$data['href'] = $data['link'];
		$data['class'] .= ' '.$this->id_base.$this->id.' '.$this->get_unique_class($data);
		return $data;
	}
	
	function get_unique_class($data) {
		$class = $data['class'];
		return $data['list_id'].'_'.md5($data['href']);					
	}
	
// Display
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		$data = $this->process($data);
		$html = '<li class="'.trim($data['class']).'"><a href="'.$data['href'].'"'.$javascript.'>'.$data['title'].'</a></li>';
		return apply_filters('cflk-link-'.$this->id.'-item', $html, $data);
	}
	
// Admin
	/**
	 * Admin display delegation
	 *
	 * @param string $mode 
	 * @param array $data 
	 * @return string html
	 */
	function _admin($data) {
		$html = '
			<div class="'.$this->id_base.$this->id.'">
				<div class="'.$this->id_base.$this->id.'-data">
					'.$this->admin_display($data).'
					<div>Title: <span class="title">'.$data['title'].'</span></div>
					<div>New Window: <span class="newwin">'.intval($data['newwin']).'</div>
				</div>
				<div class="cflk-link-edit">
					<button class="button cflk-edit-link">Edit</button> | <a class="cflk-delete-link" href="#">delete</a>
				</div>
				<input type="hidden" name="cflk-links[]" value="'.(!empty($data) ? cf_json_encode($data) : null).'" />
			</div>
			';

		return $html;
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
				Type: <span class="type">'.$data['link_type'].'</span><br />
				URL: <span class="link">'.$data['link'].'</span>
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
			<fieldset>
				<div>
					<label>'.$this->name.' (include <code>http://</code>)</label>
					<input type="text" name="link" value="'.$data['link'].'" />
				</div>
				'.$this->title_field($data).'
				'.$this->new_window_field($data).'
			</fieldset>
			';		
	}
	
	function title_field($data) {
		return '
			<div>
				<label>Title</label>
				<input type="text" name="title" value="'.esc_html($data['title']).'" />
			</div>
		';
	}
	
	function new_window_field($data) {
		return '
			<div>
				<label>
					<input type="checkbox" name="opennew" value="1" '.($data['opennew'] ? ' checked="checked"' : null).' />
					Open Link in New Window
				</label>
			</div>
		';
	}
	
	function update($new_data, $old_data) {
		// process
		return $new_data;
	}
	
	function admin_js() {
		// optional: return js to be contatenated
		return '';
	}
	
	function admin_css() {
		// optional: return css to be contatenated
		return '';
	}
}

?>