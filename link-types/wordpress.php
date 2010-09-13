<?php

class cflk_link_wordpress extends cflk_link_base {
	function __construct() {
		parent::__construct('wordpress', __('WordPress', 'cf-links'));
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-wordpress-id'])) {
			$info = $this->get_info($data['cflk-wordpress-id']);
			$data['link'] = $info['link'];
			$data['title'] = $info['title'];
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown WordPress Type', 'cflk-links');
		}
		return parent::display($data);
	}
	
	/**
	 * Admin info display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_display($data) {
		if (!empty($data['cflk-wordpress-id'])) {
			$info = $this->get_info($data['cflk-wordpress-id']);
			$title = $info['description'];
		}
		else {
			$title = __('Unknown WordPress Type', 'cf-links');
		}
		
		return '
			<div>
				'.__('Link:', 'cf-links').' <span class="link">'.esc_html($title).'</span>
			</div>
			';
	}
	
	function admin_form($data) {
		$wordpress = $this->get_dropdown((!empty($data['cflk-wordpress-id']) ? $data['cflk-wordpress-id'] : 0));
		return '
			<div>
				'.__('Link: ', 'cf-links').$wordpress.'
			</div>
			';
	}
	
	function update($data) {
		$data['link'] = $data['cflk-wordpress-id'];
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
		$html = '<select id="cflk-dropdown-wordpress" name="cflk-wordpress-id">';
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