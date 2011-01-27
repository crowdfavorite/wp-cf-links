<?php

class cflk_link_author_rss extends cflk_link_author {
	public $type_display;
	function __construct() {
		$this->type_display = __('Author RSS', 'cf-links');
		cflk_link_base::__construct('author_rss', $this->type_display);
	}
	
	function display($data) {
		if (!empty($data['cflk-author-id']) || !empty($data['link'])) {
			$author_id = '';
			if (!empty($data['cflk-author-id'])) {
				$author_id = $data['cflk-author-id'];
			}
			else if (!empty($data['link'])) {
				$author_id = $data['link'];
			}

			if (!empty($author_id) && $this->author_exists($author_id)) {
				$data['link'] = get_author_rss_link(false, $author_id);
				$data['title'] = '<img src="'.CFLK_PLUGIN_URL.'/images/feed-icon-16x16.png" title="rss"> '.get_author_name($author_id);
			}
			else {
				$data['link'] = '';
				$data['title'] = '';
			}
		}
		else {
			$data['link'] = '';
			$data['title'] = __('Unknown Author', 'cflk-links');
		}
		return cflk_link_base::display($data);
	}
}
cflk_register_link('cflk_link_author_rss');

?>