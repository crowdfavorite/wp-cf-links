<?php

class cflk_link_wordpress extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('WordPress', 'cf-links');
		parent::__construct('wordpress', $this->type_display);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-wordpress-id']) || !empty($data['link'])) {
			$id = '';
			if (!empty($data['cflk-wordpress-id'])) {
				$id = $data['cflk-wordpress-id'];
			}
			else if (!empty($data['link'])) {
				$id = $data['link'];
			}
			
			if (!empty($id)) {
				$info = $this->get_info($id);
				$data['link'] = $info['link'];
				$data['title'] = $info['title'];
			}
			else {
				$data['link'] = '';
				$data['title'] = '';
			}
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown WordPress Type', 'cflk-links');
		}
		return parent::display($data);
	}
	
	function admin_display($data) {
		$title = $description = $details = '';
		
		if (!empty($data['cflk-wordpress-id'])) {
			$details = $this->get_info($data['cflk-wordpress-id']);
		}
		else if (!empty($data['link'])) {
			$details = $this->get_info($data['link']);
		}

		if (is_array($details) && !empty($details['description'])) {
			$description = $details['description'];
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
			<div class="elm-block">
				<label>'.__('Link', 'cf-links').'</label>
				'.$this->get_dropdown((!empty($data['cflk-wordpress-id']) ? $data['cflk-wordpress-id'] : (!empty($data['link']) ? intval($data['link']) : 0))).'
			</div>
		';
	}
	
	function type_display() {
		return $this->type_display;
	}
	
	function update($data) {
		if (!empty($data['cflk-wordpress-id'])) {
			$data['link'] = $data['cflk-wordpress-id'];
		}
		return $data;
	}
	
	function get_info($id) {
		$types = $this->get_types();
		if (is_array($types) && !empty($types) && !empty($types[$id])) {
			return $types[$id];
		}
		return false;
	}
	
	function get_dropdown($selected) {
		$html = '<select id="cflk-dropdown-wordpress" name="cflk-wordpress-id" class="elm-select">';
		$types = $this->get_types();
		if (is_array($types) && !empty($types)) {
			foreach ($types as $key => $info) {
				$html .= '<option value="'.$key.'"'.selected($selected, $key, false).'>'.$info['description'].'</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}
	
	function get_types() {
		return array(
			'home' => array(
				'link' => str_replace(get_bloginfo('wpurl'), get_bloginfo('home'), site_url()),
				'description' => __('Home', 'cf-links'),
				'title' => __('Home', 'cf-links')
			),
			'loginout' => array(
				'link' => (!is_user_logged_in() ? wp_login_url() : wp_logout_url()),
				'description' => __('Log In/Out', 'cf-links'),
				'title' => (!is_user_logged_in() ? __('Log In', 'cf-links') : __('Log Out', 'cf-links'))
			),
			'register' => array(
				'link' => (!is_user_logged_in() && get_option('users_can_register') ? site_url('wp-login.php?action=register','login') : (current_user_can('manage_options') ? admin_url() : '')),
				'description' => __('Register/Site Admin', 'cf-links'),
				'title' => (!is_user_logged_in() && get_option('users_can_register') ? __('Register', 'cf-links') : (current_user_can('manage_options') ? __('Site Admin', 'cf-links') : ''))
			),
			'profile' => array(
				'link' => (is_user_logged_in() ? admin_url('profile.php') : ''),
				'description' => __('Profile', 'cf-links'),
				'title' => (is_user_logged_in() ? __('Profile', 'cf-links') : '')
			),
			'main_rss' => array(
				'link' => get_feed_link(),
				'description' => __('Site RSS', 'cf-links'),
				'title' => __('Site RSS', 'cf-links')
			)
		);
	}
}
cflk_register_link('cflk_link_wordpress');

?>