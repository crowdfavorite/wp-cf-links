<?php

	function cflk_test_list_data($list, $list_id) {
		if ($list_id == 'cfl-test-list') {
			$list = array(
				'key' => 'cfl-test-list',
				'nicename' => 'Fake List',
				'description' => 'This is the Fake List description',
				'count' => 1,
				'data' => array(
					array(
						'type' => 'url',
						'title' => 'My URL',
						'link' => 'http://example.com',
						'level' => 0,
						'opennew' => false
					)
				)
			);
		}
		return $list;
	}
	add_filter('cflk_get_links_data','cflk_test_list_data', 10, 2);

?>