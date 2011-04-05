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
	public $show_edit_button = true;
	public $show_custom_class_field = true;
	public $editable = true;
	
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
		if ($data['type'] == 'link' || $data['type'] == 'rss') {
			
			error_log(__FUNCTION__.'-top || data');
			error_log(print_r($data, true));
		}
		$link = '';
		if (!empty($data['link'])) {
			$link .= '<a href="'.$data['link'].'">';
			if (!empty($data['title'])) {
				$link .= $data['title'];
			}
			else {
				$link .= $data['link'];
			}
			$link .= '</a>';
		}
		else if (!empty($data['title'])) {
			$link .= $data['title'];
		}
		return $link;
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
if ($data['type'] == 'rss') {
	error_log(__FUNCTION__.'-top | data');
	error_log(print_r($data, true));
}
		$link = apply_filters('cflk-link-'.$this->id.'-item', $this->display($data), $data);
		$link_data = array(
			'link' => $link,
			'class' => $data['class']
		);
		return $link_data;
	}
	
// Admin

	/**
	 * Link admin view
	 *
	 * @param string $data 
	 * @return void
	 */
	function _admin_view($data, $item_id = '') {
		global $cflk_links;
		$custom_class = $opennew = $description = $title = '';
		
		$info = $this->admin_display($data);
		
		if (empty($item_id)) {
			$item_id = $cflk_links->get_random_id(time());
		}
		
		if (is_array($info) && !empty($info)) {
			$title = strip_tags(stripslashes($info['title']));
			$description = strip_tags(stripslashes($info['description']));
		}
		
		if (!empty($data['custom-class'])) {
			$custom_class = ' &middot; <span class="item-details-custom-class">'.__('Class', 'cf-links').': '.strip_tags(stripslashes($data['custom-class'])).'</span>';
		}
	
		if (intval($data['opennew']) == 1) {
			$opennew = ' &middot; <span class="item-details-newwin">'.__('New Window', 'cf-links').'</span>';
		}
		
		$level = 0;
		if (!empty($data['level'])) {
			$level = $data['level'];
		}
		
		$html = '
			<dl class="menu-item-bar">
				<dt class="menu-item-handle">
					<span class="item-view">
						'.($cflk_links->allow_edit ? '<span class="item-actions"><a href="#">Edit</a></span>' : '').'
						<p class="item-title">'.$title.'</p>
						<p>'.$this->type_display().': '.$description.$custom_class.$opennew.'</p>
						<input type="hidden" class="clfk-link-data" name="cflk_links['.$item_id.'][data]" value="'.(!empty($data) ? esc_attr(cf_json_encode($data)) : null).'" />
						<input class="menu-item-depth" type="hidden" name="cflk_links['.$item_id.'][level]" value="'.$level.'" />
					</span>
				</dt>
			</dl>
		';
	
		return $html;
	}

	/**
	 * Link admin edit form
	 *
	 * @param string $mode 
	 * @param array $data 
	 * @return string html
	 */
	function _admin_edit_form($data, $include_buttons = true, $new = false) {
		global $cflk_links;
		$id = $cflk_links->get_random_id($this->id_base.$this->id);
		
		$level = 0;
		if (!empty($data['level'])) {
			$level = $data['level'];
		}
		
		$html = '
			<div id="'.$id.'-item-edit" class="item-edit cflk-edit-link-form">
				<div class="edit-inputs">
					'.$this->admin_form($data, $id).'
					'.$this->title_field($data, $id).'
					'.$this->custom_class_field($data, $id).'
					'.$this->new_window_field($data, $id).'
				</div>
				<div class="edit-actions">
					<a href="#" class="edit-done button">'.__('Done', 'cf-links').'</a>
					<a href="#" class="edit-remove lnk-remove">'.__('Remove', 'cf-links').'</a>					
				</div>
				<input type="hidden" id="'.$id.'-type" name="type" value="'.$this->id.'" />
				<input type="hidden" id="'.$id.'-level" name="level" value="'.$level.'" />
			</div>
		';
		
		return $html;
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

	function title_field($data, $item_id = 0) {
		$title = '';
		if (is_array($data) && !empty($data['title'])) {
			$title = $data['title'];
		}
		return '
			<div class="elm-block elm-width-330">
				<label for="'.$item_id.'-title">'.__('Title', 'cf-links').'</label>
				<input type="text" class="elm-text" id="'.$item_id.'-title" name="title" value="'.strip_tags(stripslashes($title)).'" />
			</div>
		';
	}

	function custom_class_field($data, $item_id = 0) {
		$custom_class = '';
		if (is_array($data) && !empty($data['custom-class'])) {
			$custom_class = $data['custom-class'];
		}
		return '
			<div class="elm-block elm-width-330">
				<label for="'.$item_id.'-custom-class">'.__('Custom Class', 'cf-links').'</label>
				<input type="text" class="elm-text" id="'.$item_id.'-custom-class" name="custom-class" value="'.strip_tags(stripslashes($custom_class)).'" />
			</div>
		';
	}
	
	function new_window_field($data = array(), $item_id = 0) {
		$opennew = '';
		if (is_array($data) && !empty($data['opennew']) && $data['opennew']) {
			$opennew = ' checked="checked"';
		}
		return '
			<div class="elm-block has-checkbox">
				<input type="checkbox" class="elm-checkbox" id="'.$item_id.'-opennew" name="opennew" value="1" '.$opennew.' />
				<label for="'.$item_id.'-opennew" class="lbl-checkbox">'.__('Open in new window', 'cf-links').'</label>
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