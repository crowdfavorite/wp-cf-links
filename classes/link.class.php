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
		if (empty($data['link']) && empty($data['title'])) {
			return '';
		}
		return (!empty($data['link']) ? '<a href="'.$data['link'].'">' : '').(!empty($data['title']) ? $data['title'] : $data['link']).(!empty($data['link']) ? '</a>' : '');
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
		$link_data = array(
			'link' => $link,
			'class' => $data['class']
		);
		return $link_data;
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
				';
				if ($include_buttons) {
					$html .= '
					<div class="cflk-link-move">
							<img src="'.plugins_url('cf-links/images/arrow_up_down.png').'" class="cflk-link-move-img handle" />
					</div>
					';
				}
				if ($include_buttons) {
					$html .= '
					<div class="cflk-link-type">
						'.$this->admin_display_type().'
					</div>
					';
				}
				$html .= '
				<fieldset class="cflk-link-data '.$this->id_base.$this->id.'-data" style="border:0;">
					'.$this->admin_form($data);
					if ($this->show_title_field) {
						$html .= $this->title_field($data);
					}
					if ($this->show_new_window_field) {
						$html .= $this->new_window_field($data);
					}
					$html .= '
				</fieldset>
				';
				if ($include_buttons) {
					$html .= '
					<div class="cflk-link-edit">
							<button class="button cflk-edit-done">'.__('Done', 'cf-links').'</button>
					</div>
					';
				}
				if ($include_buttons) {
					$html .= '
					<div class="cflk-link-delete">
							<button class="button cflk-edit-cancel">'.__('Cancel', 'cf-links').'</button>
					</div>
					';
				}
				$html .= '
				<input type="hidden" name="type" value="'.$this->id.'" />
				<div class="clear"></div>
			</div>';
		return $html;
	}

	/**
	 * Link admin view
	 *
	 * @param string $data 
	 * @return void
	 */
	function _admin_view($data) {
		// If there is no title to display, don't display the title div.  This keeps the admin interface a little cleaner
		$title = '';
		if (!empty($data['title'])) {
			$title = '<div>'.__('Title:', 'cf-links').' <span class="title">'.$data['title'].'</span></div>';
		}

		return '
			<div class="'.$this->id_base.$this->id.' cflk-link-data-display">
				<div class="cflk-link-move">
					<img src="'.plugins_url('cf-links/images/arrow_up_down.png').'" class="cflk-link-move-img handle" />
				</div>
				<div class="cflk-link-type">
					'.$this->admin_display_type().'
				</div>
				<div class="cflk-link-data '.$this->id_base.$this->id.'-data">
					'.$this->admin_display($data).'
					'.$title.'
					<div>'.__('New Window:', 'cf-links').' <span class="newwin">'.(intval($data['opennew']) == 1 ? 'Yes' : 'No').'</span></div>
				</div>
				<div class="cflk-link-edit">
					<button class="button cflk-edit-link">'.__('Edit', 'cf-links').'</button>
				</div>
				<div class="cflk-link-delete">
					<button class="button cflk-delete-link">'.__('Delete', 'cf-links').'</button>
					<input type="hidden" class="clfk-link-data" name="cflk_links[]" value="'.(!empty($data) ? esc_attr(cf_json_encode($data)) : null).'" />
				</div>
				<div class="clear"></div>
			</div>
			';
	}
	
	
	function _update($data) {
		if (!empty($data['opennew'])) {
			$data['opennew'] = (bool) $data['opennew'];
		}
		return $this->update($data);
	}

// admin helpers

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
	
	function admin_display_type() {
		return $this->name;
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