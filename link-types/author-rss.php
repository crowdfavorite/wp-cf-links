<?php

class cflk_link_author_rss extends cflk_link_base {
	function __construct() {
		parent::__construct('author_rss', __('Author RSS', 'cf-links'));
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-author-id'])) {
			$data['link'] = get_author_rss_link(false, $data['cflk-author-id']);
			$data['title'] = get_author_name($data['cflk-author-id']);
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Author', 'cflk-links');
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
		if (!empty($data['cflk-author-id'])) {
			$title = get_author_name($data['cflk-author-id']);
		}
		else {
			$title = __('Unknown Author', 'cf-links');
		}
		
		return '
			<div>
				'.__('Author:', 'cf-links').' <span class="link">'.esc_html($title).'</span>
			</div>
			';
	}
	
	function admin_form($data) {
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-authors',
			'name' => 'cflk-author-id',
			'selected' => (!empty($data['cflk-author-id']) ? intval($data['cflk-author-id']) : 0),
			'include' => $this->get_users()
		);
		$authors = wp_dropdown_users($args);
		return '
			<div>
				'.__('Authors: ', 'cf-links').$authors.'
			</div>
			';
	}
	
	function update($data) {
		$data['link'] = $data['cflk-author-id'];
		return $data;
	}
	
	function get_users() {
		global $wpdb;
		$sql = "
			SELECT DISTINCT u.ID
			FROM {$wpdb->users} AS u, 
				{$wpdb->usermeta} AS um
			WHERE u.ID = um.user_id
			AND um.meta_key LIKE '{$wpdb->prefix}capabilities'
			AND um.meta_value NOT LIKE '%subscriber%'
			ORDER BY u.user_nicename
			";
		$results = '';
		$count = 1;
		$users = $wpdb->get_results($sql);
		if (is_array($users) && !empty($users)) {
			foreach($users as $u) {
				$results .= $u->ID;
				if ($count < count($users)) {
					$results .= ',';
				}
				$count++;
			}
		}
		return $results;
	}
}
cflk_register_link('author_rss','cflk_link_author_rss');

?>