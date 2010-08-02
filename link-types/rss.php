<?php

class cflk_link_rss extends cflk_link {
	function __construct() {
		cflk_link_base::__construct('rss', __('RSS', 'cf-links'));
	}
	
	function display($data) {
		$data['title'] = '<img src="/path/to/rss/icon.png" title="rss">'.$data['title'];
		return parent::display($data);
	}
}

cflk_register_link('rss','cflk_link_rss');

?>