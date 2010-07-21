<?php

class cflk_link_rss extends cflk_link {
	function __construct() {
		$this->id = 'rss';
		$this->name = 'RSS';
	}
	function display($data) {
		$data['text'] = '<img src="/path/to/rss/icon.png" title="rss">'.$data['text'];
		return parent::dislay($data);
	}
}

cflk_register_link('rss','cflk_link_rss');

?>