<?php
/*
Plugin Name: CF Links
Plugin URI: http://crowdfavorite.com
Description: Advanced options for adding links
Version: 1.4-Ref
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);


/**
 * 
 * WP Admin Handling Functions
 * 
 */

// Constants
define('CFLK_VER', '1.4-Ref');
define('CFLK_DIR', trailingslashit(plugin_dir_path(__FILE__)));
//plugin_dir_url seems to be broken for including in theme files
if (file_exists(trailingslashit(get_template_directory()).'plugins/'.basename(dirname(__FILE__)))) {
	define('CFLK_DIR_URL', trailingslashit(trailingslashit(get_bloginfo('template_url')).'plugins/'.basename(dirname(__FILE__))));
}
else {
	define('CFLK_DIR_URL', trailingslashit(plugins_url(basename(dirname(__FILE__)))));	
}

load_plugin_textdomain('cf-links');
$cflk_types = array();
$cflk_inside_widget = false;


/**
 * Adds all of the link types and their data
 *
 * @return void
 */
function cflk_link_types() {
	global $wpdb, $cflk_types, $blog_id;
	
	$pages = get_pages(array('hierarchical' => 1));
	$categories = get_categories('get=all');
	$authors = cflk_get_authors();

	$page_data = array();
	$category_data = array();
	$author_data = array();
	$wordpress_data = array();
	$blog_data = array();
	$site_data = array();
	
	foreach ($pages as $page) {
		$page_data[$page->ID] = array(
			'link' => $page->ID, 
			'description' => $page->post_title,
			'ancestors' => get_post_ancestors($page)
		);
	}
	foreach ($categories as $category) {
		$category_data[$category->slug] = array(
				'link' => $category->term_id, 
				'description' => $category->name, 
				'count' => $category->count
		);
	}
	foreach ($authors as $author) {
		$author_data[$author['login']] = array(
				'link' => $author['id'], 
				'description' => $author['display_name']
		);
	}	
	$wordpress_data = array(
		'home' => array(
			'link' => 'home',
			'description' => __('Home','cf-links'),
		),
		'loginout' => array(
			'link' => 'loginout',
			'description' => __('Log In/Out','cf-links'),
		),
		'register' => array(
			'link' => 'register',
			'description' => __('Register/Site Admin','cf-links'),
		),
		'profile' => array(
			'link' => 'profile',
			'description' => __('Profile','cf-links'),
		),
		'main_rss' => array(
			'link' => 'main_rss',
			'description' => __('Site RSS','cf-links'),
		),
	);
	if (function_exists('get_blog_list')) {
		$sites = $wpdb->get_results($wpdb->prepare("SELECT id, domain FROM $wpdb->site ORDER BY ID ASC"), ARRAY_A);
		
		if (is_array($sites) && count($sites)) {
			foreach ($sites as $site) {
				$site_id = $site['id'];
				$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE site_id = '".$wpdb->escape($site_id)."' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC"), ARRAY_A);
				
				if (is_array($blogs)) {
					foreach ($blogs as $blog) {
						$details = get_blog_details($blog['blog_id']);
						$description = '';
						if ($details->blog_id != $site['id']) {
							$description = '&mdash; '.$details->blogname; 
						}
						else {
							$description = $details->blogname;
						}
						$blog_data[$details->blog_id] = array(
							'link' => $details->blog_id,
							'description' => $description,
						);
					}
				}
			}
		}
	}
	
	$cflk_types = array(
		'url' => array(
			'type' => 'url',
			'nicename' => __('URL','cf-links'),
			'input' => 'text',
			'data' => __('ex: http://example.com', 'cf-links'),
		),
		'rss' => array(
			'type' => 'rss',
			'nicename' => __('RSS','cf-links'),
			'input' => 'text',
			'data' => __('ex: http://example.com/feed', 'cf-links'),
		),
		'page' => array(
			'type' => 'page', 
			'nicename' => __('Page','cf-links'),
			'input' => 'select', 
			'data' => $page_data
		),
		'category' => array(
			'type' => 'category', 
			'nicename' => __('Category', 'cf-links'),
			'input' => 'select', 
			'data' => $category_data
		),
		'author' => array(
			'type' => 'author',
			'nicename' => __('Author', 'cf-links'),
			'input' => 'select', 
			'data' => $author_data
		),
		'author_rss' => array(
			'type' => 'author_rss',
			'nicename' => __('Author RSS', 'cf-links'),
			'input' => 'select', 
			'data' => $author_data
		),
		'wordpress' => array(
			'type' => 'wordpress', 
			'nicename' => __('Wordpress', 'cf-links'),
			'input' => 'select', 
			'data' => $wordpress_data
		),
	);
	if (function_exists('get_blog_list')) {
		$blog_type = array(
			'blog' => array(
				'type' => 'blog', 
				'nicename' => __('Blog','cf-links'),
				'input' => 'select', 
				'data' => $blog_data
			),
		);
		$cflk_types = array_merge($cflk_types, $blog_type);
	}
	// Allow other link types to be added
	$cflk_types = apply_filters('cflk-types',$cflk_types);
}
// Only run this function on the proper pages, since we don't need all of this data processed anywhere else
if (isset($_GET['cflk_page']) && $_GET['cflk_page'] == 'edit') {
	add_action('admin_init', 'cflk_link_types');
}

/**
 * grab list of authors
 * pulls anyone with capabilities higher than subscriber
 *
 * @return array - list of authors
 */
function cflk_get_authors() {
	global $wpdb;

	$sql = "
		SELECT DISTINCT u.ID,
			u.user_nicename,
			u.display_name,
			u.user_login
		from {$wpdb->users} AS u, 
			{$wpdb->usermeta} AS um
		WHERE u.user_login <> 'admin'
		AND u.ID = um.user_id
		AND um.meta_key LIKE '{$wpdb->prefix}capabilities'
		AND um.meta_value NOT LIKE '%subscriber%'
		ORDER BY u.user_nicename
		";
	$results = array();
	$users = $wpdb->get_results(apply_filters('cflk_get_authors_sql', $sql));
	foreach ($users as $u) {
		$results[$u->ID] = array(
			'id' => $u->ID,
			'display_name' => $u->display_name,
			'login' => $u->user_login
		);
	}
	return apply_filters('cflk_get_authors', $results);
}

function cflk_menu_items() {
	add_options_page(
		__('CF Links', 'cf-links')
		, __('CF Links', 'cf-links')
		, 10
		, 'cf-links'
		, 'cflk_check_page'
	);
}
add_action('admin_menu', 'cflk_menu_items');

function cflk_check_page() {
	$check_page = '';
	if (isset($_GET['cflk_page'])) {
		$check_page = $_GET['cflk_page'];
	}
	switch ($check_page) {
		case 'edit':
			cflk_edit();
			break;
		case 'create':
			cflk_new();
			break;
		case 'import':
			cflk_import();
			break;
		case 'main':
		default:
			cflk_options_form();
			break;
	}
}

function cflk_request_handler() {
	if (current_user_can('manage_options') && !empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cflk_update_settings':
				$link_data = array(); 
				if (!empty($_POST['cflk']) && is_array($_POST['cflk'])) {
					$link_data = stripslashes_deep($_POST['cflk']);
				}
				if (!empty($_POST['cflk_key']) && !empty($_POST['cflk_nicename'])) {
					cflk_process($link_data, $_POST['cflk_key'], $_POST['cflk_nicename'], $_POST['cflk_description'], $_POST['cflk_reference_children']);
				}
				wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=edit&link='.urlencode($_POST['cflk_key']).'&cflk_message=updated'));
				die();
				break;
			case 'cflk_delete':
				if (isset($_POST['cflk_key']) && $_POST['cflk_key'] != '') {
					cflk_delete($_POST['cflk_key']);
				}
				die();
				break;
			case 'cflk_delete_key':
				if (!empty($_POST['cflk_key'])) {
					cflk_delete($_POST['cflk_key']);
				}
				die();
				break;
			case 'cflk_insert_new':
				$nicename = '';
				$description = '';
				$data = '';
			
				if (!empty($_POST['cflk_create']) && $_POST['cflk_create'] == 'new_list') {
					if (!empty($_POST['cflk_nicename'])) {
						$nicename = $_POST['cflk_nicename'];
						$data = array();
					}
				}
				if (!empty($_POST['cflk_create']) && $_POST['cflk_create'] == 'import_list') {
					if (!empty($_POST['cflk_import'])) {
						$import = maybe_unserialize(stripslashes(unserialize(urldecode($_POST['cflk_import']))));
						$nicename = $import['nicename'];
						$description = $import['description'];
						$data = $import['data'];
					}
				}
				if ($nicename != '' && is_array($data)) {
					$cflk_key = cflk_insert_new($nicename, $description, $data);
				}
				wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=edit&link='.$cflk_key));
				die();
				break;
			case 'cflk_edit_nicename':
				if (!empty($_POST['cflk_nicename']) && !empty($_POST['cflk_key'])) {
					cflk_edit_nicename($_POST['cflk_key'], $_POST['cflk_nicename']);
				}
				die();
				break;
			case 'cflk_insert_reference':
				if (!empty($_POST['cflk_reference_list'])) {
					$cflk_key = cflk_insert_reference($_POST['cflk_reference_list']);
				}
				if ($cflk_key) {
					wp_redirect($blogurl.'/wp-admin/options-general.php?page=cf-links&cflk_page=edit&link='.$cflk_key);
				}
				else {
					wp_redirect($blogurl.'/wp-admin/options-general.php?page=cf-links&cflk_page=create');
				}
				die();
				break;
		}
	}
	if (!empty($_GET['cflk_page'])) {
		switch ($_GET['cflk_page']) {
			case 'dialog':
				cflk_dialog();
				break;
			case 'export':
				cflk_export_list($_GET['link']);
				break;
			default:
				break;
		}
	}
}
add_action('wp_loaded', 'cflk_request_handler');
add_action('wp_ajax_cflk_update_settings', 'cflk_request_handler');

function cflk_resources_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cflk_admin_js':
				cflk_admin_js();
				die();
				break;
			case 'cflk_admin_css':
				cflk_admin_css();
				die();
				break;
			case 'cflk_front_js':
				cflk_front_js();
				die();
				break;
		}
	}
}
add_action('init', 'cflk_resources_handler', 1);

function cflk_admin_css() {
	header('Content-type: text/css');
	include('css/admin.css');
	echo apply_filters('cflk_admin_css', '');
	exit();
}

function cflk_front_js() {
	include('js/front.js');
}

function cflk_admin_js() {
	header('Content-type: text/javascript');
	include('js/admin.js');
	echo apply_filters('cflk_admin_js', '');
	exit();
}

function cflk_admin_enqueue_scripts($hook = '') {
	switch ($hook) {
		case 'settings_page_cf-links':
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('thickbox');
			wp_enqueue_script('cflk-admin-js', admin_url('?cf_action=cflk_admin_js'), array('jquery', 'jquery-ui-core'), CFLK_VER);
			wp_enqueue_style('cflk-admin-css', admin_url('?cf_action=cflk_admin_css'), array(), CFLK_VER);
			wp_enqueue_style('thickbox', includes_url('js/thickbox/thickbox.css'));
			break;
	}
}
add_action('admin_enqueue_scripts', 'cflk_admin_enqueue_scripts');

/**
 * 
 * CF Links Admin Interface Functions
 * 
 */

/**
 * This is the main options page display for the plugin.  This page displays information about all of the lists that have been created
 *
 * @return void
 */
function cflk_options_form() {
	$cflk_list = cflk_get_list_links();
	$form_data = array();
	
	if (is_array($cflk_list) && !empty($cflk_list)) {
		foreach ($cflk_list as $key => $cflk) {
			$form_data[$key] = array('nicename' => $cflk['nicename'], 'count' => $cflk['count']);
		}
	}
	
	if (!empty($_GET['cflk_message'])) {
		switch ($_GET['cflk_message']) {
			case 'create':
				include('views/message-list-create.php');
				break;
			case 'delete':
				include('views/message-list-delete.php');
				break;
		}
	}
	include('views/options-form.php');
}

/**
 * This is the new List form.
 *
 * @return void
 */
function cflk_new() {
	include('views/new-list.php');
}

/**
 * This is the Import.  It provides the ability Import a list from another location, and display list data to export to another location.
 * 
 * Also included is the ability to import a "Reference" list from another blog if this plugin is installed in a WordPress Network instance
 *
 * @return void
 */
function cflk_import() {
	$links_lists = cflk_get_list_links();
	include('views/import.php');
}

/**
 * This is the List Edit form. This displays the large form for editing all of the links in a list
 *
 * @return void
 */
function cflk_edit() {
	if (empty($_GET['link'])) {
		include('views/message-list-error.php');
		return;
	}
	global $wpdb, $cflk_types;
	
	$cflk_key = $_GET['link'];
	$cflk = maybe_unserialize(get_option($cflk_key));
	is_array($cflk) ? $cflk_count = count($cflk) : $cflk_count = 0;
	
	// Check to see if this is a reference list
	if (!isset($cflk['reference'])) {
		$cflk['reference'] = false;
	}
	
	include('views/edit.php');
}

function cflk_nav($page = '', $list = '') {
	$main_text = '';
	$add_text = '';
	$import_text = '';
	
	switch ($page) {
		case 'main':
			$main_text = 'class="current"';
			break;
		case 'create':
			$add_text = ' class="current"';
			break;
		case 'import':
			$import_text = ' class="current"';
			break;
		default:
			break;
	}
	
	ob_start();
	include('views/nav.php');
	$cflk_nav = ob_get_clean();
	return $cflk_nav;
}

function cflk_edit_select($type) {
	$select = array();
	
	$select['url_show'] = 'style="display: none;"';
	$select['url_select'] = '';
	$select['rss_show'] = 'style="display: none;"';
	$select['rss_select'] = '';
	$select['page_show'] = 'style="display: none;"';
	$select['page_select'] = '';
	$select['category_show'] = 'style="display: none;"';
	$select['category_select'] = '';
	$select['wordpress_show'] = 'style="display: none;"';
	$select['wordpress_select'] = '';
	$select['author_show'] = 'style="display: none;"';
	$select['author_select'] = '';
	$select['author_rss_show'] = 'style="display: none;"';
	$select['author_rss_select'] = '';
	$select['blog_show'] = 'style="display: none;"';
	$select['blog_select'] = '';
	
	switch ($type) {
		case 'url':
			$select['url_show'] = 'style=""';
			$select['url_select'] = 'selected=selected';
			break;
		case 'rss':
			$select['rss_show'] = 'style=""';
			$select['rss_select'] = 'selected=selected';
			break;
		case 'page':
			$select['page_show'] = 'style=""';
			$select['page_select'] = 'selected=selected';
			break;
		case 'category':
			$select['category_show'] = 'style=""';
			$select['category_select'] = 'selected=selected';
			break;	
		case 'wordpress':
			$select['wordpress_show'] = 'style=""';
			$select['wordpress_select'] = 'selected=selected';
			break;	
		case 'author':
			$select['author_show'] = 'style=""';
			$select['author_select'] = 'selected=selected';
			break;	
		case 'author_rss':
			$select['author_rss_show'] = 'style=""';
			$select['author_rss_select'] = 'selected=selected';
			break;	
		case 'blog':
			$select['blog_show'] = 'style=""';
			$select['blog_select'] = 'selected=selected';
			break;	
		default:
			$select['url_show'] = 'style=""';
			$select['url_select'] = 'selected=selected';
			break;
	}
	return apply_filters('cflk-edit-select',$select,$type);
}

function cflk_get_type_input($type_array, $show, $key, $show_count, $value, $reference = '') {
	$return = '';
	extract($type_array);
	
	$return .= '<span id="'.$type.'_'.$key.'" '.($show == $nicename ? '' : 'style="display: none;"').'>';
	if (!$reference) {
		switch ($input) {
			case 'text':
				$return .= '<input type="text" name="cflk['.$key.']['.$type.']" id="cflk_'.$key.'_'.$type.'" size="50" value="'.strip_tags($value).'" /><br />'.$data;
				break;
			case 'select':
				$return .= '<select name="cflk['.$key.']['.$type.']" id="cflk_'.$key.'_'.$type.'" style="max-width: 410px; width: 90%;">';
				foreach ($data as $info) {
					$selected = '';
					$count_text = '';
					if ($value == $info['link']) {
						$selected = ' selected=selected';
					}
					if ($show_count == 'yes' && isset($info['count'])) {
						$count_text = ' ('.$info['count'].')';
					}
					if($type == 'page' && isset($info['ancestors']) && count($info['ancestors'])) {
						$info['description'] = str_repeat('&nbsp;',count($info['ancestors'])*3).$info['description'];
					}
					$return .= '<option value="'.$info['link'].'"'.$selected.'>'.$info['description'].$count_text.'</option>';
				}
				if($value == 'HOLDER' && $show == 'style=""') {
					$return .= '<option value="HOLDER" selected=selected>'.__('IMPORTED ITEM DOES NOT EXIST, PLEASE CHOOSE ANOTHER ITEM', 'cf-links').'</option>';
				}
				$return .= '</select>';
				if ($value == 'HOLDER' && $show == 'style=""') {
					switch ($type) {
						case 'page':
							$type_show = 'Page';
							break;
						case 'category':
							$type_show = 'Category';
							break;
						case 'author':
							$type_show = 'Author';
							break;
						case 'author_rss':
							$type_show = 'Author RSS';
							break;
					
					}
					$return .= '<br /><span id="holder_'.$type.'_'.$key.'" style="font-weight:bold;">'.__('Imported item ID does not exist in the system.<br />Please create a new '.$type_show.', then select it from the list above.','cf-links').'</span>';
				}
				break;
		}
	}
	else {
		$return .= htmlspecialchars($value);
	}
	$return .= '</span>';
	return $return;
}

/**
 * 
 * CF Links TinyMCE Handling Functions
 * 
 */

function cflk_dialog() {
	global $wpdb;
	$cflk_list = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",'cfl-%'));
	include('views/dialog.php');
	die();
}

function cflk_register_button($buttons) {
	array_push($buttons, '|', "cfLinksBtn");
	return $buttons;
}

function cflk_add_tinymce_plugin($plugin_array) {
	$plugin_array['cflinks'] = CFLK_DIR_URL.'js/editor_plugin.js';
	return $plugin_array;
}

function cflk_addtinymce() {
   if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		return;
   if (get_user_option('rich_editing') == 'true') {
		add_filter("mce_external_plugins", "cflk_add_tinymce_plugin");
		add_filter('mce_buttons', 'cflk_register_button');
   }
}
add_action('init', 'cflk_addtinymce');

/**
 * 
 * CF Links Data Handling Functions
 * 
 */

function cflk_process($cflk_data = array(), $cflk_key = '', $cflk_nicename = '', $cflk_description = '', $cflk_reference_children = array()) {
	if ($cflk_key == '' && $cflk_nicename == '') { return false; }
	$new_data = array();
	foreach ($cflk_data as $key => $info) {
		if ($info['type'] == '') {
			unset($cflk_data[$key]);
		} else {
			$opennew = false;
			$type = $info['type'];
			if (isset($info['opennew'])) {
				$opennew = true;
			}

			$nofollow = false;
			if (isset($info['nofollow'])) {
				$nofollow = true;
			}

			if (isset($type) && $type != '') {
				$new_data[] = array(
					'title' => stripslashes($info['title']),
					'type' => $type,
					'link' => stripslashes($info[$type]),
					'cat_posts' => ($type == 'category' && isset($info['category_posts']) && $info['category_posts'] != '' ? true : false),
					'level' => intval($info['level']),
					'nofollow' => $nofollow,
					'opennew' => $opennew
				);
			}
		}
	}
	$settings = array(
		'key' => $cflk_key,
		'nicename' => stripslashes($cflk_nicename), 
		'description' => stripslashes($cflk_description), 
		'reference_children' => $cflk_reference_children,
		'data' => $new_data
	);
	$settings = apply_filters('cflk_save_list_settings',$settings);
	do_action('cflk_save_list', $settings);
	update_option($cflk_key, $settings);
}

function cflk_delete_key($cflk_key, $remove_key) {
	$cflk = maybe_unserialize(get_option($cflk_key));
	if(isset($cflk['data'][$remove_key])) {
		unset($cflk['data'][$remove_key]);
	}
	update_option($cflk_key, $cflk);
	return true;
}

function cflk_delete($cflk_key) {
	if ($cflk_key == '') { return false; }
	$delete_keys = array();
	$widgets = maybe_unserialize(get_option('cf_links_widget'));
	$sidebars = maybe_unserialize(get_option('sidebars_widgets'));
	
	if (is_array($widgets) && is_array($sidebars)) {
		foreach ($widgets as $key => $widget) {
			if ($widget['select'] == $cflk_key) {
				unset($widgets[$key]);
				foreach ($sidebars as $sidebars_key => $sidebar) {
					if (is_array($sidebar)) {
						foreach ($sidebar as $sb_key => $value) {
							if($value == 'cf-links-'.$key) {
								unset($sidebar[$sb_key]);
							}
						}
						$sidebars[$sidebars_key] = $sidebar;
					}
				}
				update_option('sidebars_widgets', $sidebars);
			}
		}
	}
	do_action('cflk_delete_list',$cflk_key);
	$res = delete_option($cflk_key);
	
	// send response
	header('Content-type: text/javascript');
	ob_end_clean();
	if($res) {
		//echo '{"success":true}'; // would like to use this, but SACK doesn't seem to like json
		echo '1';
	}
	else {
		//echo '{"success":false}';
		echo '0';
	}
	exit;
}

function cflk_insert_new($nicename = '', $description = '', $data = array(), $insert_key = false) {
	if ($nicename == '') { return false; }
	
	// check to see if a specific key was requested and if that specific key already exists
	if($insert_key !== false) {
		$check_list = get_option($key);
		if(is_array($check_list)) { return false; }
	}
	
	$pages = $categories = $authors = $blogs = array();
	$page_object = get_pages();
	foreach ($page_object as $page) {
		array_push ($pages, $page->ID);
	}
	$category_object = get_categories('get=all');
	foreach ($category_object as $category) {
		array_push ($categories, $category->term_id);
	}
	$author_object = get_users_of_blog($wpdb->blog_id);
	foreach ($author_object as $author) {
		array_push ($authors, $author->user_id);
	}
	if (function_exists ('get_blog_list')) {
		$blog_object = get_blog_list();
		foreach ($blog_object as $key => $blog) {
			array_push ($blogs, $blog['blog_id']);
		}
	}
	
	$check_name = cflk_name_check(stripslashes($nicename));
	foreach ($data as $key => $item) {
		if ($item['type'] == 'page') {
			if(!in_array($item['link'],$pages)) {
				$item['link'] = 'HOLDER';
			}
		}
		if ($item['type'] == 'category') {
			if(!in_array($item['link'],$categories)) {
				$item['link'] = 'HOLDER';
			}
		}
		if ($item['type'] == 'author') {
			if(!in_array($item['link'],$authors)) {
				$item['link'] = 'HOLDER';
			}
		}
		if ($item['type'] == 'author_rss') {
			if(!in_array($item['link'],$authors)) {
				$item['link'] = 'HOLDER';
			}
		}
		if (function_exists('get_blog_list')) {
			if ($item['type'] == 'blog') {
				if(!in_array($item['link'],$blogs)) {
					$item['link'] = 'HOLDER';
				}
			}
		}
		$data[$key]['link'] = $item['link'];
	}
	$settings = array('nicename' => $check_name[1], 'description' => $description, 'data' => $data);

	// if key hasn't already been defined, pull the value from the name check routine
	if(!$insert_key) { 
		$insert_key = $check_name[0];
	}
	
	// insert and return
	add_option($insert_key, $settings);
	return $insert_key;
}

function cflk_name_check($name) {
	global $wpdb;
	$i=1;
	$option_name = 'cfl-'.sanitize_title($name);
	$title = $name;
	$original_option = $option_name;
	$original_title = $title;
	while(count($wpdb->get_results($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '".$wpdb->escape($option_name)."'"))) > 0) {
		$option_name = $original_option.$i;
		$title = $original_title.$i;
		$i++;
	}
	return array($option_name,$title);
}

function cflk_edit_nicename($cflk_key = '', $cflk_nicename = '') {
	if ($cflk_key != '' && $cflk_nicename != '') {
		$cflk = maybe_unserialize(get_option($cflk_key));
		if ($cflk['nicename'] != $cflk_nicename) {
			$cflk['nicename'] = stripslashes($cflk_nicename);
		}
		update_option($cflk_key, $cflk);
	}
}

function cflk_get_list_links($blog = 0) {
	global $wpdb, $blog_id;
	
	// if we're on MU and another blog's details have been requested, change the options table assignment
	if (!is_null($blog_id) && $blog != 0) {
		$options = 'wp_'.$wpdb->escape($blog).'_options';
	}
	else {
		$options = $wpdb->options;
	}
	
	$cflk_list = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM {$options} WHERE option_name LIKE %s", 'cfl-%'));
	$return = array();

	if (is_array($cflk_list)) {
		foreach ($cflk_list as $cflk) {
			$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
			$return[$cflk->option_name] = array(
				'nicename' => $options['nicename'], 
				'description' => $options['description'],
				'count' => count($options['data']),
				'reference' => $options['reference'],
				'data' => $options['data']
			);
		}
		return $return;
	}
	return false;
}

function cflk_insert_reference($reference = '') {
	global $blog_id;
	
	if ($reference == '') { return false; }
	$match = explode('-', $reference, 2);
	
	$current_blog = $blog_id;
	
	switch_to_blog($match[0]);

	$links = maybe_unserialize(get_option($match[1]));
	
	if (is_array($links)) {
		$key = $match[1];
		$nicename = $links['nicename'];
		$description = $links['description'];
		$data = array();
		
		if (!is_array($links['reference_children'])) {
			$links['reference_children'] = array();
		}

		foreach ($links['data'] as $link_data) {
			$type = $link_data['type'];
			$link = $link_data['link'];
			$title = $link_data['title'];
			$opennew = $link_data['opennew'];

			if ($link_data['type'] == 'page' || $link_data['type'] == 'author' || $link_data['type'] == 'author_rss' || $link_data['type'] == 'category' || $link_data['type'] == 'wordpress' || $link_data['type'] == 'blog') {
				$reference_data = cflk_reference_get_link_data($link_data['type'], $link_data['link']);
				$type = $reference_data['type'];
				$link = $reference_data['link'];
				$title = $reference_data['title'];
			}

			$data[] = array(
				'title' => $title,
				'type' => $type,
				'link' => $link,
				'cat_posts' => $link_data['cat_posts'],
				'opennew' => $opennew
			);
		}
		
		restore_current_blog();

		$check_name = cflk_name_check(stripslashes($nicename));
		$settings = array(
			'nicename' => $check_name[1], 
			'description' => $description, 
			'reference' => true,
			'reference_parent_blog' => $match[0],
			'reference_parent_list' => $match[1],
			'data' => $data
		);

		// if key hasn't already been defined, pull the value from the name check routine
		if(!$insert_key) { 
			$insert_key = $check_name[0];
		}
		
		// insert and return
		add_option($insert_key, $settings);
		
		// Now that we know the key that the reference will use, tell the parent of its existence
		switch_to_blog($match[0]);
		array_push($links['reference_children'],$current_blog.'-'.$check_name[0]);
		update_option($match[1],$links);
		restore_current_blog();
		
		return $insert_key;
	}
	return false;
}

function cflk_reference_get_link_data($link_type, $link_link) {
	$type = '';
	$title = '';
	if ($link_type == 'page') {
		$type = 'url';
		$link = get_page_link($link_link);
		$postinfo = get_post(htmlspecialchars($link_link));
		if (is_a($postinfo, 'stdClass')) {
			$title = $postinfo->post_title;
		}
	}
	if ($link_type == 'author') {
		$type = 'url';
		$link = get_author_posts_url($link_link);
		$userdata = get_userdata($link_link);
		if (is_a($userdata, 'stdClass')) {
			$title = $userdata->display_name;
		}
	}
	if ($link_type == 'author_rss') {
		$type = 'url';
		$link = get_author_feed_link($link_link);
		$userdata = get_userdata($link_link);
		if (is_a($userdata, 'stdClass')) {
			$title = $userdata->display_name;
		}
	}
	if ($link_type == 'category') {
		$type = 'url';
		$link = get_category_link($link_link);
		$cat_info = get_category(intval($link_link),OBJECT,'display');
		if (is_a($cat_info,'stdClass')) {
			$title = attribute_escape($cat_info->cat_name);
			if ($link_data['cat_posts']) {
				$title .= ' ('.$link_cat_info->count.')';
			}
		}
	}
	if ($link_type == 'wordpress') {
		$type = 'url';
		$wp_info = cflk_get_wp_type($link_link);
		$link = $wp_info['link'];
		$title = $wp_info['text'];
	}
	if ($link_type == 'blog') {
		$type = 'url';
		$blog_info = cflk_get_blog_type($link_link);
		$link = $blog_info['link'];
		$title = $blog_info['text'];
	}
	
	$return = array(
		'type' => $type,
		'link' => $link,
		'title' => $title,
	);
	return $return;
}

function cflk_reference_children_update($settings) {
	if (is_array($settings['reference_children']) && !empty($settings['reference_children'])) {
		global $blog_id;
		$current_blog = $blog_id;
		foreach ($settings['reference_children'] as $child) {
			$child_info = explode('-', $child, 2);
			$child_blog_id = $child_info[0];
			$child_key = $child_info[1];
			
			switch_to_blog($child_blog_id);
			$links = maybe_unserialize(get_option($child_key));
			restore_current_blog();

			if (is_array($links)) {
				$nicename = $links['nicename'];
				$description = $links['description'];
				
				if (!empty($links['reference_parent_blog'])) {
					$reference_parent_blog = $links['reference_parent_blog'];
				}
				else {
					$reference_parent_blog = $current_blog;
				}
				if (!empty($links['reference_parent_list'])) {
					$reference_parent_list = $links['reference_parent_list'];
				}
				else {
					$reference_parent_list = $child_key;
				}

				$data = array();

				foreach ($settings['data'] as $link_data) {
					$type = $link_data['type'];
					$link = $link_data['link'];
					$title = $link_data['title'];
					$opennew = $link_data['opennew'];
	
					if ($link_data['type'] == 'page' || $link_data['type'] == 'author' || $link_data['type'] == 'author_rss' || $link_data['type'] == 'category' || $link_data['type'] == 'wordpress' || $link_data['type'] == 'blog') {
						$reference_data = cflk_reference_get_link_data($link_data['type'], $link_data['link']);
						$type = $reference_data['type'];
						$link = $reference_data['link'];
						if (empty($title)) {
							$title = $reference_data['title'];
						}
					}
	
					$data[] = array(
						'title' => $title,
						'type' => $type,
						'link' => $link,
						'cat_posts' => $link_data['cat_posts'],
						'opennew' => $opennew
					);
				}

				$update = array(
					'nicename' => $nicename, 
					'description' => $description, 
					'reference' => true,
					'reference_parent_blog' => $reference_parent_blog,
					'reference_parent_list' => $reference_parent_list,
					'data' => $data
				);
				
				switch_to_blog($child_blog_id);
				update_option($child_key,$update);
				restore_current_blog();
			}
		}
	}
}
add_action('cflk_save_list','cflk_reference_children_update');

function cflk_find_children($settings) {
	global $wpdb,$blog_id;
	if (!function_exists ('get_blog_list')) { return $settings; }
	
	$parent_blog = $blog_id;
	
	if (!is_array($settings['reference_children'])) {
		$settings['reference_children'] = array();
	}
	$blog_list = array();
	$sites = $wpdb->get_results($wpdb->prepare("SELECT id, domain FROM $wpdb->site ORDER BY ID ASC"), ARRAY_A);

	if (is_array($sites) && count($sites)) {
		foreach ($sites as $site) {
			$site_id = $site['id'];
			$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE site_id = '$site_id' AND public = '1' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC"), ARRAY_A);
			
			if (is_array($blogs)) {
				foreach ($blogs as $blog) {
					if ($blog['blog_id'] != $blog_id) {
						$blog_list[] = $blog['blog_id'];
					}
				}
			}
		}
	}

	// Now that we have the sites list, lets go through and make sure that we know about all of the children
	foreach ($blog_list as $blog) {
		switch_to_blog($blog);
		$links = maybe_unserialize(get_option($settings['key']));
		if (is_array($links) && !empty($links)) {
			if (!$links['reference']) { 
				restore_current_blog();
				continue; 
			}
			$ref_check = $blog.'-'.$settings['key'];
			// Keep this in place, we might need this if the children lose their way and we need to force them back into line
			// if ($links['reference'] == '1' && !in_array($ref_check, $settings['reference_children'])) {
			// 	$settings['reference_children'][] = $ref_check;
			// }
			if ($links['reference_parent_blog'] == $parent_blog && $links['reference_parent_list'] == $settings['key'] && !in_array($ref_check,$settings['reference_children'])) {
				$settings['reference_children'][] = $ref_check;
			}
		}
		restore_current_blog();
	}
	restore_current_blog();
	return $settings;
}
add_filter('cflk_save_list_settings','cflk_find_children',1);

function cflk_reference_children_delete($cflk_key) {
	global $blog_id;
	$this_blog = $blog_id;
	$links = maybe_unserialize(get_option($cflk_key));
	
	switch_to_blog($links['reference_parent_blog']);
	$parent_links = maybe_unserialize(get_option($links['reference_parent_list']));
	foreach ($parent_links['reference_children'] as $child) {
		if ($child == $this_blog.'-'.$cflk_key) {
		}
	}
	restore_current_blog();
}
global $wpmu_version;
if(!is_null($wpmu_version)) {
	add_action('cflk_delete_list','cflk_reference_children_delete');
}

/**
 * 
 * CF Links Widget Handling Functions
 * 
 */

/**
 * new WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class cflk_Widget extends WP_Widget {
	function cflk_Widget() {
		$widget_ops = array( 'classname' => 'cflk-widget', 'description' => 'Widget for showing links entered in the CF Links settings page.' );
		$this->WP_Widget( 'cflk-widget', 'CF Links', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		global $cflk_inside_widget;
		$cflk_inside_widget = true;
		$title = esc_attr( $instance['title'] );
		$content = cflk_get_links($instance['list_key']);
		
		if (empty($content)) { return; }
		echo $before_widget;
		if (!empty($title)) {
			echo $before_title . $title . $after_title;
		}
		echo $content;
		echo $after_widget;
		$cflk_inside_widget = false;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['list_key'] = strip_tags($new_instance['list_key']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args((array) $instance, array('title' => '', 'list_key' => ''));

		$title = esc_attr( $instance['title'] );
		$links_lists = cflk_get_list_links();
		$links_select = '';
		if (is_array($links_lists) && !empty($links_lists)) {
			foreach ($links_lists as $key => $links_list) {
				$links_select .= '<option value="'.$key.'"'.selected($instance['list_key'], $key, false).'>'.$links_list['nicename'].'</option>';
			}
		}
		if (!empty($links_select)) {
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'cf-links'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('list_key'); ?>"><?php _e('Links List: ', 'cf-links'); ?></label>
				<select id="<?php echo $this->get_field_id('list_key'); ?>" name="<?php echo $this->get_field_name('list_key'); ?>">
					<option value="0">--Select Links List--</option>
					<?php echo $links_select; ?>
				</select>
			</p>
			<p>
				<a href="<?php bloginfo('wpurl') ?>/wp-admin/options-general.php?page=cf-links"><?php _e('Edit Links','cf-links') ?></a>
			</p>
			<?php
		}
		else {
			?>
			<p>
				<?php _e('No Links Lists have been setup.  Please <a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links">setup a links list</a> before proceeding.', 'cf-links'); ?>
			</p>
			<?php
		}
	}
}

add_action( 'widgets_init', create_function( '', "register_widget('cflk_Widget');" ) );

/**
 * 
 * CF Links Data Retrieval Functions
 * 
 */

function cflk_handle_shortcode($attrs, $content=null) {
	if (is_array($attrs) && isset($attrs['name'])) {
		return cflk_get_links($attrs['name']);
	}
	return false;
}
// Main Shortcodes
add_shortcode('cflk', 'cflk_handle_shortcode',11);
add_shortcode('cflk_links','cflk_handle_shortcode',11);
// Kept in for legacy purposes
add_shortcode('cfl_links', 'cflk_handle_shortcode',11);

/**
 * Check if a givet links list exists
 * @param string $key - id of the list being targeted
 * @return bool
 */
function cflk_links_list_exists($key) {
	$list = cflk_get_links_data($key);
	if (is_array($list)) {
		return true;
	} else {
		return false;
	}
}

/**
 * Return all relevant data about a list
 *
 * @param string $key - id of the list being targeted
 * @return mixed - array or false.
 */
function cflk_get_links_data($key) {
	$links = get_option($key);
	if (empty($links)) {
		return false;
	}
	$links = maybe_unserialize($links);
	$links['key'] = $key;
	$links = cflk_get_link_info($links);
	return apply_filters('cflk_get_links_data', $links);
}

/**
 * Build a links list based on the passed in key and args.
 * This function is called as a template tag and inside widgets.
 *
 * @param string $key 
 * @param array $args 
 * @return html
 */
function cflk_get_links($key = null, $args = array()) {
	if (!$key) { return ''; }
	
	$defaults = array(
		'before' => '<ul class="cflk-list '.$key.'">',
		'after' => '</ul>',
		'child_before' => '<ul class="cflk-list-nested">',
		'child_after' => '</ul>',
		'location' => 'template'
	);
	$args = apply_filters('cflk_list_args',array_merge($defaults, $args),$key);
	$args['server_current'] = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

	// make sure we have a level designator in the list
	$args['before'] = cflk_ul_ensure_level_class($args['before']);
	$args['child_before'] = cflk_ul_ensure_level_class($args['child_before']);
	$args['key'] = $key;
	
	$list = cflk_get_links_data($key);
	if (!is_array($list)) { return ''; }
	
	$ret = '';
	$i = 0;
	$listcount = 0;
	
	// Process the list to see if the href is empty, if it is remove it from the list
	// so we don't have extra <li>'s that are not needed
	foreach ($list['data'] as $key => $data) {
		if (empty($data['href']) && empty($data['title'])) {
			unset($list['data'][$key]);
			// This is here so we don't have array keys that go from 0 to 2 when an item is unset.
			// This is a problem when we go through and print out the list because it needs all of
			// the keys in order so it doesn't miss them, or something like that
			$list['data'] = array_merge($list['data']);
		}
	}
	$ret = cflk_build_list_items($list['data'], $args);
	$ret = apply_filters('cflk_get_links', $ret, $list, $args);
	return $ret;
}

/**
 * Make sure we have a level-x classname on the $args['before'] for proper listed nest labeling
 * Pulls the <ul> tag from the $before var with substr because it is more reliable than pulling
 * the UL via regex with all the permutations and different wrappers and other items that can be
 * included in the $before variable - SP
 *
 * if no classname is present, we add the entire class attribute, else we add to the existing attribute
 *
 * @param string $before
 * @return string
 */
function cflk_ul_ensure_level_class($before) {
	// fish out the <ul> and its attributes
	$ul_start = strpos($before,'<ul');
	$ul_end = (strpos($before,'>',$ul_start)+1)-$ul_start;
	$ul = substr($before,$ul_start,$ul_end);
	
	// munge
	if(!preg_match("|class=|", $ul)) {
		// add a class attribute
		$ul_n = preg_replace("|(<ul.*?)(>)|","$1 class=\"level-0\"$2",$ul);
	}
	elseif(!preg_match("|class=\".*?(level-[0-9])\"|",$ul)) {
		// modify the existing class attribute
		$ul_n = preg_replace("|(<ul.*?class=\".*?)(\".*?>)|","$1 level-0$2",$ul);
	}
	
	return str_replace($ul,$ul_n,$before);
}

/**
 * recursive function for putting together list items and handling nesting.
 * Does some cleanup on the previous iteration of the code, but is not a full rewrite.
 * Handles a flat list so we don't have to reformat the data array - time permitting
 * this will be modified to work off a nested data array to ease the recursion limits
 * on finding the first/last item in nested lists. 
 *
 * Because the data array is flat, we need the global for array positioning after recursing.
 *
 * @param array $items 
 * @param array $args 
 * @param int $start 
 * @return html
 */
function cflk_build_list_items(&$items,$args,$start=0) {
	global $cflk_i;
	extract($args, EXTR_SKIP);

	// increment the level
	$before = ($start == 0 ? $args['before'] : $args['child_before']);
	$ret = preg_replace("|(level-[0-9])|","level-".$items[$start]['level'],$before);
	
	for($cflk_i = $start; $cflk_i < count($items), $data = $items[$cflk_i]; $cflk_i++) {
		if (is_array($data)) {
			$li_class = '';
			if ($data['type'] == 'category') {
				if ($data['link'] == the_category_id(false)) {
					$li_class .= 'cflk-current-category ';
				}
			}
			
			// see if we're first or last
			if(!isset($items[$cflk_i-1])) {
				$li_class .= 'cflk-first ';
			}
			elseif(!isset($items[$cflk_i+1])) {
				$li_class .= 'cflk-last ';
			}
			
			// see if we're the current page
			/* Wordpress urls always have a trailingslash, make sure we have one on the $data['href'] */
			if ($server_current == str_replace(array('http://', 'http://www.'), '', trailingslashit($data['href']))) {
				$li_class .= apply_filters('cflk_li_class_current', 'cflk-current');
			}
			
			// build & filter link
			$link = '';
			if (!empty($data['href'])) {
				$rel = '';
				if (!empty($data['rel'])) {
					$rel = ' rel="'.$data['rel'].'"';
				}
				$link .= '<a href="'.$data['href'].'" class="a-level-'.$data['level'].'"'.$rel.'>';
			}
			$link .= strip_tags($data['text']);
			if (!empty($data['href'])) {
				$link .= '</a>';
			}
			$link = apply_filters('cflk-link-item',$link,$data,$key);
			
			// put it all together
			$ret .= '<li class="'.$data['class'].' '.$li_class.'">'.$link;
			if($items[$cflk_i+1]['level'] > $data['level']) {
				$ret .= cflk_build_list_items($items,$args,++$cflk_i);
			}
			$ret .= '</li>';

			// if we're at the end of this level then break the loop
			if(!isset($items[$cfkl_i+1]) || $items[$cflk_i+1]['level'] < $data['level']) {
				break;
			}
		}
	}
	$after = ($start == 0 ? $args['after'] : $args['child_after']);
	return $ret.$after;
}

function cflk_links($key, $args = array()) {
	echo cflk_get_links($key, $args);
}

function cflk_get_link_info($link_list,$merge=true) {
	$data = array();
	
	if(is_array($link_list)) {
		foreach ($link_list['data'] as $key => $link) {
			// legacy compatability: add level if not present - SP
			if(!isset($link['level'])) { $link['level'] == 0; }
			
			$href = '';
			$text = '';
			$type_text = '';
			$other = '';
			$sanitized_href = '';

			// 'type' is everything up to the first -
			$type = (false ===  strpos($link['type'],'-') ? $link['type'] : substr($link['type'],0,strpos($link['type'],'-')));
			switch ($type) {
				case 'url':
					$href = htmlspecialchars($link['link']);
					$type_text = strip_tags($link['link']);
					break;
				case 'rss':
					$href = htmlspecialchars($link['link']);
					$type_text = strip_tags($link['link']);
					$other = 'rss';
					break;
				case 'post':
				case 'page':
					$postinfo = get_post(htmlspecialchars($link['link']));
					if (is_a($postinfo, 'stdClass') && in_array($postinfo->post_status, array('publish', 'inherit'))) {
						$type_text = $postinfo->post_title;
						$href = get_permalink(htmlspecialchars($link['link']));
					}
					break;
				case 'category':
					$cat_info = get_category(intval($link['link']),OBJECT,'display');
					if (is_a($cat_info,'stdClass')) {
						$href = get_category_link($cat_info->term_id);
						$type_text = attribute_escape($cat_info->cat_name);
						if ($link['cat_posts']) {
							$type_text .= ' ('.$link_cat_info->count.')';
						}
					}
					break;
				case 'wordpress':
					$get_link = cflk_get_wp_type($link['link']);
					if (is_array($get_link)) {
						$href = $get_link['link'];
						$type_text = $get_link['text'];
						if ($link['link'] == 'main_rss') {
							$other = 'rss';
						}
					}
					break;
				case 'author':
					$userdata = get_userdata($link['link']);
					if (is_a($userdata, 'stdClass')) {
						$type_text = $userdata->display_name;
						$href = get_author_posts_url($link['link'], $userdata->user_nicename);
					}
					else if (is_a($userdata, 'WP_User')) {
						$type_text = $userdata->data->display_name;
						$href = get_author_posts_url($userdata->data->ID, $userdata->data->user_nicename);
					}
					break;
				case 'author_rss':
					$userdata = get_userdata($link['link']);
					if (is_a($userdata, 'stdClass')) {
						$type_text = $userdata->display_name;
						$other = 'rss';
						$href = get_author_feed_link($link['link']);
					}
					break;
				case 'blog':
				case 'site':
					$bloginfo = cflk_get_blog_type($link['link']);
					if (is_array($bloginfo)) {
						$href = $bloginfo['link'];
						$type_text = $bloginfo['text'];
					}
					break;
				default:
					break;
			}
			
			if (empty($link['title'])) {
				$text = $type_text;
			}
			else {
				$text = strip_tags($link['title']);
			}
			$class = $link_list['key'].'_'.md5($href);
			if ($other == 'rss') {
				$class .= ' cflk-feed';
			}
			if ($link['opennew']) {
				$class .= ' cflk-opennewwindow';
				add_action('wp_footer', 'cflk_front_js');				
			}
			
			$rel = '';
			if ($link['nofollow']) {
				$rel .= 'nofollow';
			}

			
			if ($href != '') {
				// removed array push to preserve data key associations for later merging
				$data[$key] = array('id' => $key, 'href' => $href, 'text' => $text, 'class' => $class, 'rel' => $rel);
			}

		}
		if($merge) {
			// return the entire link list merged with the new data
			foreach($link_list['data'] as $key => $list_item) {
				if (is_array($data[$key])) {
					$link_list['data'][$key] = array_merge($list_item,$data[$key]);
				}
			}
			return $link_list;
		}
		else {
			// return just the new data
			return $data;
		}
	}
}

function cflk_get_blog_type($id) {
	if (!empty($id) && $id != 0) {
		$link = '';
		$text = '';
	
		$details = get_blog_details($id);
		$link = $details->siteurl;
		$text = $details->blogname;
		
		if ($link != '' && $text != '') {
			return array('text' => $text, 'link' => $link);
		}
	}
	return false;
}

function cflk_get_wp_type($type) {
	$link = '';
	$text = '';
	switch ($type) {
		case 'home':
			$link = get_bloginfo('url');
			$text = 'Home';
			break;
		case 'loginout':
			// wordpress 2.7 adds convenience functions around the login/logout urls
			global $wp_version;
			if (!is_user_logged_in()) {
				$link = (version_compare($wp_version,'2.7','<') ? site_url('wp-login.php','login') : wp_login_url());
				$text = 'Log in';
			}
			else {
				$link = (version_compare($wp_version,'2.7','<') ? site_url('wp-login.php?action=logout','login') : wp_logout_url());
				$text = 'Log Out';
			}
			break;
		case 'register':
			if (!is_user_logged_in()) {
				if (get_option('users_can_register')) {
					$link = site_url('wp-login.php?action=register','login');
					$text = 'Register';
				}
			}
			else {
				if (current_user_can('manage_options')) {
					$link = admin_url();
					$text = 'Site Admin';
				}
			}
			break;
		case 'profile':
			if (is_user_logged_in()) {
				$link = admin_url('profile.php');
				$text = 'Profile';
			}
			break;
		case 'main_rss':
			$text = get_bloginfo('name');
			$link = get_bloginfo('rss2_url');
			break;
	}
	if ($link != '' && $text != '') {
		return array('text' => $text, 'link' => $link);
	}
	return false;
}

function cflk_export_list($key) {
	global $wpdb;
	$cflk_list = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '".$wpdb->escape($key)."'"));
	foreach ($cflk_list as $key => $value) {
		$export = urlencode(serialize($value->option_value));
		?>
		<textarea rows="20" style="width:600px;"><?php echo $export; ?></textarea>
		<?php
	}
	die();
}

/**
 * cflk_deactivate_blog - This function runs when a blog is deactivated.  It will go through all of the lists on the entire site and
 * remove the deactivated blog from any links list.
 *
 * @param string $id - Blog ID to be removed
 * @return void
 */
function cflk_deactivate_blog($id) {
	global $cflk_types;
	
	switch_to_blog($id);
	$url = get_bloginfo('url');
	$name = get_bloginfo('name');
	restore_current_blog();
	
	foreach ($cflk_types['blog']['data'] as $blog) {
		$blog_id = $blog['link'];
		$links_lists = cflk_get_list_links($blog_id);
		
		foreach ($links_lists as $key => $list) {
			foreach ($list['data'] as $item_key => $item) {
				if ($item['type'] == 'blog' && $item['link'] == $id) {
					unset($list['data'][$item_key]);
				}
				if ($item['type'] == 'url' && $item['link'] == $url && $item['title'] == $name) {
					unset($list['data'][$item_key]);
				}
			}
			
			switch_to_blog($blog_id);
			update_option($key, $list);
			restore_current_blog($blog_id);
		}
	}
}
add_action('deactivate_blog', 'cflk_deactivate_blog');
add_action('archive_blog', 'cflk_deactivate_blog');

/**
 * 
 * CF Links Deprecated Functions
 * 
 */

function get_links_template($key) {
	return cflk_get_links($key);
}

function links_template($key, $before = '', $after = '') {
	$args = array();
	if($before != '') {
		$args['before'] = $before;
	}
	if($after != '') {
		$args['after'] = $after;
	}
	echo cflk_get_links($key, $args);
}


// CF README HANDLING

/**
 * Enqueue the readme function
 */
function cflk_add_readme() {
	if (function_exists('cfreadme_enqueue')) {
		cfreadme_enqueue('cf-links', 'cflk_readme');
	}
}
add_action('admin_init', 'cflk_add_readme');

/**
 * return the contents of the links readme file
 * replace the image urls with full paths to this plugin install
 *
 * @return string
 */
function cflk_readme() {
	$file = realpath(dirname(__FILE__)).'/readme/readme.txt';
	if (is_file($file) && is_readable($file)) {
		$markdown = file_get_contents($file);
		$markdown = preg_replace('|!\[(.*?)\]\((.*?)\)|','![$1]('.WP_PLUGIN_URL.'/cf-links/readme/$2)',$markdown);
		return $markdown;
	}
	return null;
}

?>
