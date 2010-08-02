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
class cflk_link_base {
	protected $id_base = 'cflk-';
	public $id;
	public $name;
	
	public $show_new_window_field = true;
	public $show_title_field = true;
	
	function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}
	
// User Definable functions

	/**
	 * Standard front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		return '<li class="'.trim($data['class']).'"><a href="'.$data['link'].'">'.(!empty($data['title']) ? $data['title'] : $data['link']).'</a></li>';
	}
	
	/**
	 * Admin info display
	 *
	 * @param array $data
	 * @return string html
	 */
	function admin_display($data) {
		trigger_error('::admin_display() should be overriden in child class. Do not call this parent method directly.', E_USER_ERROR);
	}
	
	/**
	 * Admin edit display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_form($data) {
		trigger_error('::admin_form() should be overriden in child class. Do not call this parent method directly.', E_USER_ERROR);
	}
	
	/**
	 * Modify the data before its saves
	 *
	 * @param array $data 
	 * @return array
	 */
	function update($data) {
		// process
		return $data;
	}
	
// Display

	function _display($data) {
		$data['class'] = $this->id_base.$this->id.' '.$this->get_unique_class($data);
		$link = apply_filters('cflk-link-'.$this->id.'-item', $this->display($data), $data);
		return $link;
	}
	
// Admin
	/**
	 * Link admin edit form
	 *
	 * @param string $mode 
	 * @param array $data 
	 * @return string html
	 */
	function _admin_edit_form($data, $include_buttons = true) {
		$html = '
			<div class="'.$this->id_base.$this->id.' cflk-edit-link-form">
				<fieldset>
					'.$this->admin_form($data);
		
		if ($this->show_title_field) {
			$html .= $this->title_field($data);
		}
		if ($this->show_new_window_field) {
			$html .= $this->new_window_field($data);
		}
		$html .= '
				</fieldset>';
		if ($include_buttons) {
			$html .= '
					<div>
						<input type="hidden" name="type" value="'.$this->id.'" />
						<input type="button" class="button cflk-edit-done" value="'.__('Done', 'cf-links').'" /> | <a href="#" class="cflk-edit-cancel">'.__('cancel', 'cf-links').'</a>
					</div>';
		}
		$html .= '
			</div>';
		return $html;
	}

	/**
	 * Link admin view
	 *
	 * @param string $data 
	 * @return void
	 * @author Shawn Parker
	 */
	function _admin_view($data) {
		return '
			<div class="'.$this->id_base.$this->id.' cflk-link-data-display">
				<div class="'.$this->id_base.$this->id.'-data">
					'.$this->admin_display($data).'
					<div>'.__('Title:', 'cf-links').' <span class="title">'.$data['title'].'</span></div>
					<div>'.__('New Window:', 'cf-links').' <span class="newwin">'.(intval($data['opennew']) == 1 ? 'Yes' : 'No').'</div>
				</div>
				<div class="cflk-link-edit">
					<button class="button cflk-edit-link">'.__('Edit', 'cf-links').'</button> | <a class="cflk-delete-link" href="#">'.__('delete', 'cf-links').'</a>
				</div>
				<input type="hidden" class="clfk-link-data" name="cflk_links[]" value="'.(!empty($data) ? esc_attr(cf_json_encode($data)) : null).'" />
			</div>
			';
	}
	


	function _update($data) {
		if (!empty($data['opennew'])) {
			$data['opennew'] = (bool) $data['opennew'];
		}
		return $this->update($data);
	}

// amdin helpers

	function get_unique_class($data) {
		$class = $data['class'];
		return $data['list_id'].'_'.md5($data['href']);					
	}

	function title_field($data) {
		return '
			<div>
				<label>'.__('Title', 'cf-links').'</label>
				<input type="text" name="title" value="'.esc_html($data['title']).'" />
			</div>
		';
	}
	
	function new_window_field($data) {
		return '
			<div>
				<label>
					<input type="checkbox" name="opennew" value="1" '.($data['opennew'] ? ' checked="checked"' : null).' />
					'.__('Open Link in New Window', 'cf-links').'
				</label>
			</div>
		';
	}
	
	//function admin_js() {
	//	// optional: return js to be contatenated
	//	return '';
	//}
	
	//function admin_css() {
	//	// optional: return css to be contatenated
	//	return '';
	//}
}

?>