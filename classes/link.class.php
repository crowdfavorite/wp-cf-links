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
	function _admin($data, $edit = false) {
		if ($edit) {
			$html = '
				<div class="'.$this->id_base.$this->id.' cflk-edit-link-form">
					'.$this->admin_form($data).'
					<div>
						<input type="hidden" name="type" value="'.$this->id.'" />
						<input type="button" class="button cflk-edit-done" value="'.__('Done', 'cf-links').'" /> | <a href="#" class="cflk-edit-cancel">'.__('cancel', 'cf-links').'</a>
					</div>
				</div>
				';
		}
		else {
			$html = '
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
				'.__('Type:', 'cf-links').' <span class="type">'.$this->name.'</span><br />
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
			<fieldset>
				<div>
					<label>'.__($this->name.' (include <code>http://</code>)', 'cf-links').'</label>
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
	
	function _update($data) {
		if (!empty($data['opennew'])) {
			$data['opennew'] = (bool) $data['opennew'];
		}
		return $this->update($data);
	}
	
	function update($data) {
		// process
		return $data;
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