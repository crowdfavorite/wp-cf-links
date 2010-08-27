<?php

class cflk_link_blog extends cflk_link_base {
	function __construct() {
		parent::__construct('blog', __('Blog', 'cf-links'));
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-blog-id'])) {
			$details = get_blog_details(intval($data['cflk-blog-id']));
			$data['link'] = $details->siteurl;
			$data['title'] = $details->blogname;
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Blog', 'cflk-links');
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
		if (!empty($data['cflk-blog-id'])) {
			$details = get_blog_details(intval($data['cflk-blog-id']));
			$title = $details->blogname;
		}
		else {
			$title = __('Unknown Blog', 'cflk-links');
		}
		return '
			<div>
				'.__('Blog:', 'cf-links').' <span class="link">'.esc_html($title).'</span>
			</div>
			';
	}
	
	function admin_form($data) {
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-blogs',
			'name' => 'cflk-blog-id',
			'selected' => (!empty($data['cflk-blog-id']) ? intval($data['cflk-blog-id']) : 0) 
		);
		$blogs = $this->dropdown($args);
		return '
			<div>
				'.__('Blogs: ', 'cf-links').$blogs.'
			</div>
			';
	}
	
	function update($data) {
		$data['link'] = $data['cflk-blog-id'];
		return $data;
	}
	
	function dropdown($args = array()) {
		$defaults = array(
			'echo' => 1, 'selected' => 0, 'name' => 'user', 
			'class' => '', 'id' => ''
		);
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		$bloglist = cf_get_blog_list(0,'all');
		$html = '<select name="'.$name.'" id="'.$id.'">';
		if (is_array($bloglist) && !empty($bloglist)) {
			foreach ($bloglist as $blog) {
				$html .= '<option name="'.esc_attr($blog['blog_id']).'"'.selected($selected, $blog['blog_id'], false).'>'.esc_attr($blog['blogname']).'</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}
}
if ((defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE) || (defined('MULTISITE') && MULTISITE)) {
	cflk_register_link('blog','cflk_link_blog');
}

?>