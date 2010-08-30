<?php

function cflk_create_test_lists_request_handler() {
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cflk_create_test_lists':
				if (!empty($_POST['cflk-create-test']) && is_array($_POST['cflk-create-test'])) {
					cflk_create_test_list($_POST['cflk-create-test']);
				}
				break;
		}
	}
}
add_action('init', 'cflk_create_test_lists_request_handler');

function cflk_create_test_list($data = array()) {
	if (!is_array($data) || empty($data)) { return false; }
	global $cflk_links;
	$default_links = array(
		0 => array(
	      'link' => 'google.com',
	      'title' => 'Google (open new)',
	      'opennew' => true,
	      'type' => 'url',
		),
		1 => array(
	      'link' => 'google.com',
	      'title' => 'Google (not open new)',
	      'opennew' => false,
	      'type' => 'url',
		),
	    2 => array(
	      'link' => 'crowdfavorite.com/feed',
	      'title' => 'Crowd Favorite Feed (open new)',
	      'opennew' => true,
	      'type' => 'rss',
	    ),
	    3 => array(
	      'link' => 'crowdfavorite.com/feed',
	      'title' => 'Crowd Favorite Feed (not open new)',
	      'opennew' => false,
	      'type' => 'rss',
	    ),
		4 => 
	    array (
	      'cflk-author-id' => '1',
	      'title' => 'Author 1 (open new)',
	      'type' => 'author',
	      'link' => '1',
	      'opennew' => true,
	    ),
		5 => 
	    array (
	      'cflk-author-id' => '1',
	      'title' => 'Author 1 (not open new)',
	      'type' => 'author',
	      'link' => '1',
	      'opennew' => false,
	    ),
	    6 => 
	    array (
	      'cflk-wordpress-id' => 'home',
	      'title' => 'Home Link (open new)',
	      'type' => 'wordpress',
	      'link' => 'home',
	      'opennew' => true,
	    ),
	    7 => 
	    array (
	      'cflk-wordpress-id' => 'home',
	      'title' => 'Home Link (not open new)',
	      'type' => 'wordpress',
	      'link' => 'home',
	      'opennew' => false,
	    ),
	    8 => 
	    array (
	      'cflk-page-id' => '2',
	      'title' => 'About Us (open new)',
	      'opennew' => true,
	      'type' => 'page',
	      'link' => '2',
	    ),
	    9 => 
	    array (
	      'cflk-page-id' => '2',
	      'title' => 'About Us (not open new)',
	      'opennew' => true,
	      'type' => 'page',
	      'link' => '2',
	    ),
	    10 => 
	    array (
	      'cflk-wordpress-id' => 'loginout',
	      'title' => '',
	      'type' => 'wordpress',
	      'link' => 'loginout',
	    )
	);
	
	$name = stripslashes($data['name']);
	$count = stripslashes($data['number']);
	$info = $cflk_links->check_unique_list_id(sanitize_title($name));
	$data = array();
	
	for ($i = 0; $i < $count; $i++) {
		$data[] = $default_links[rand(0, 10)];
	}
	
	$list = array(
		'nicename' => $name,
		'key' => $info['list_id'],
		'description' => 'Test List Created List with '.$count.' items',
		'data' => $data
	);
	if ($cflk_links->save_list_data($list)) {
		wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=edit&list='.$list['key'].'&cflk_message=2'));
		exit;
	}
}

function cflk_create_test_lists($html) {
	
	$items_dropdown = '<select name="cflk-create-test[number]" id="cflk-create-test-lists-number">';
	for ($i = 1; $i <= 10; $i++) {
		$items_dropdown .= '<option value="'.$i.'">'.$i.'</option>';
	}
	for ($i = 20; $i <= 100; $i=$i+10) {
		$items_dropdown .= '<option value="'.$i.'">'.$i.'</option>';
	}
	for ($i = 150; $i <= 1000; $i=$i+50) {
		$items_dropdown .= '<option value="'.$i.'">'.$i.'</option>';
	}
	$items_dropdown .= '</select>';
	
	
	$html .= '
		<div id="cflk_create_test_lists">
			<h2>'.__('Create Test Lists', 'cf-links').'</h2>
			<form id="cflk-create-test-lists" name="cflk-create-test-lists" action="" method="post">
				<p>
					<label for="cflk-create-test-lists-number">'.__('How many items should I put in the list?', 'cf-links').'</label>
					'.$items_dropdown.'
				</p>
				<p>
					<label for="cflk-create-test-lists-name">'.__('What would you like to name your list?', 'cf-links').'</label>
					<input type="text" name="cflk-create-test[name]" id="cflk-create-test-lists-name" value="" />
				</p>
				<p>
					<input type="hidden" name="cf_action" value="cflk_create_test_lists" />
					<input type="submit" name="submit" class="button-primary" value="'.__('Create List', 'cf-links').'" />
				</p>
			</form>
		</div>
	';
	return $html;
}
add_filter('cflk_admin_main_after', 'cflk_create_test_lists');

?>