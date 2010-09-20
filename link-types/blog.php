<?php

class cflk_link_blog extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('Blog', 'cf-links');
		parent::__construct('blog', $this->type_display);
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		if (!empty($data['cflk-blog-id']) || !empty($data['link'])) {
			$blog_id = '';
			if (!empty($data['cflk-blog-id'])) {
				$blog_id = $data['cflk-blog-id'];
			}
			else if (!empty($data['link'])) {
				$blog_id = $data['link'];
			}

			if (!empty($blog_id)) {
				$details = get_blog_details(intval($blog_id));
				$data['link'] = $details->siteurl;
				$data['title'] = $details->blogname;
			}
			else {
				$data['link'] = '';
				$data['title'] = '';
			}
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Blog', 'cflk-links');
		}
		return parent::display($data);
	}
	
	function admin_display($data) {
		$description = $title = $details = '';
		
		if (!empty($data['cflk-blog-id'])) {
			$details = get_blog_details(intval($data['cflk-blog-id']));
		}
		else if (!empty($data['link'])) {
			$details = get_blog_details(intval($data['link']));
		}
		
		if (!empty($details)) {
			$description = $details->blogname;
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
		$args = array(
			'echo' => false,
			'id' => 'cflk-dropdown-blogs',
			'name' => 'cflk-blog-id',
			'selected' => (!empty($data['cflk-blog-id']) ? intval($data['cflk-blog-id']) : (!empty($data['link']) ? intval($data['link']) : 0)),
			'class' => 'elm-select'
		);

		return '
			<div class="elm-block">
				<label>'.__('Link', 'cf-links').'</label>
				'.$this->dropdown($args).'
			</div>
		';
	}

	function type_display() {
		return $this->type_display;
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
		$html = '<select name="'.$name.'" id="'.$id.'" class="'.$class.'">';
		$html .= '<option value="0">'.__('--Select Blog--', 'cf-links').'</option>';

		if (is_array($bloglist) && !empty($bloglist)) {
			foreach ($bloglist as $blog) {
				$html .= '<option value="'.esc_attr($blog['blog_id']).'"'.selected($selected, $blog['blog_id'], false).'>'.esc_attr($blog['blogname']).'</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}
}
if ((defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE) || (defined('MULTISITE') && MULTISITE)) {
	cflk_register_link('cflk_link_blog');
}

?>