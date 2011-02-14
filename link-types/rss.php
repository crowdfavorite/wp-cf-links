<?php

class cflk_link_rss extends cflk_link {
	public $type_display;
	function __construct() {
		$this->type_display = __('RSS', 'cf-links');
		cflk_link_base::__construct('rss', $this->type_display);
	}
	
	function display($data) {
		$data['title'] = '<img src="'.CFLK_PLUGIN_URL.'/images/feed-icon-16x16.png" title="rss" /> '.$data['title'];
		return parent::display($data);
	}
}

cflk_register_link('cflk_link_rss');

?>