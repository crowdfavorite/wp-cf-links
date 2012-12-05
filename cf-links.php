<?php
/*
Plugin Name: CF Links
Plugin URI: http://crowdfavorite.com
Description: Advanced tool for adding collections of links, including pages, posts, and external URLs.
Version: 1.4.2
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
define('CFLK_VERSION', '1.4.2');
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
		$sites = $wpdb->get_results("SELECT id, domain FROM $wpdb->site ORDER BY ID ASC", ARRAY_A);
		
		if (is_array($sites) && count($sites)) {
			foreach ($sites as $site) {
				$site_id = $site['id'];
				$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE site_id = '%s' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $site_id), ARRAY_A);
				
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
		, basename(__FILE__)
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
					cflk_process($link_data, $_POST['cflk_key'], $_POST['cflk_nicename'], $_POST['cflk_description']);
				}
				wp_redirect(admin_url('options-general.php?page=cf-links.php&cflk_page=edit&link='.urlencode($_POST['cflk_key']).'&cflk_message=updated'));
				die();
				break;
			case 'cflk_delete':
				if (!empty($_POST['cflk_key'])) {
					cflk_delete($_POST['cflk_key']);
				}
				die();
				break;
			case 'cflk_delete_key':
				if (!empty($_POST['cflk_key']) && !empty($_POST['key'])) {
					cflk_delete_key($_POST['cflk_key'], $_POST['key']);
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
				wp_redirect(admin_url('options-general.php?page=cf-links.php&cflk_page=edit&link='.$cflk_key));
				die();
				break;
			case 'cflk_edit_nicename':
				if (!empty($_POST['cflk_nicename']) && !empty($_POST['cflk_key'])) {
					cflk_edit_nicename($_POST['cflk_key'], $_POST['cflk_nicename']);
				}
				die();
				break;
			default:
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
	echo file_get_contents(CFLK_DIR.'css/admin.css');
	echo apply_filters('cflk_admin_css', '');
	exit();
}

function cflk_front_js() {
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		//jQuery('.cflk-opennewwindow a').attr('target','_blank');
		jQuery('.cflk-opennewwindow a').click(function(){
			window.open(this.href);
			return false;
		});
	});
</script>
<?php
}

function cflk_admin_js() {
	header('Content-type: text/javascript');
	echo file_get_contents(CFLK_DIR.'js/admin.js');
	echo apply_filters('cflk_admin_js', '');
	exit();
}

/**
 * 
 * Enqueue the CSS/JS in the proper place
 * 
 */
if (is_admin() && !empty($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('cflk-admin-js', site_url('index.php?cf_action=cflk_admin_js'), array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'), CFLK_VERSION);
	wp_enqueue_style('cflk-admin-css', site_url('index.php?cf_action=cflk_admin_css'), array(), CFLK_VERSION);
	
	if (!empty($_GET['cflk_page']) && $_GET['cflk_page'] == 'import') {
		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox', site_url('wp-includes/js/thickbox/thickbox.css'), array());
	}
}


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
 * This is the Import.  It provides the ability Import a list from another location, and display list data to export to another location
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

function cflk_get_type_input($type_array, $show, $key, $show_count, $value) {
	$return = '';
	extract($type_array);
	
	$return .= '<span id="'.$type.'_'.$key.'" '.($show == $nicename ? '' : 'style="display: none;"').'>';
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
		case 'select-modal':
			foreach($data as $item) {
				if($item['link'] == $value) {
					$display = $item['description'];
					break;
				}
			}
			$return .= '
				<input type="hidden" name="cflk['.$key.']['.$type.']" id="cflk-'.$type.'-'.$key.'-value" value="'.strip_tags($value).'"/>
				<div class="select-modal-display"><span>'.$display.'</span> <input type="button" class="button" id="edit-'.$key.'-'.$type.'" name="edit-'.$key.'-'.$type.'" value="Edit" onclick="cflk_edit_select_modal(\''.$key.'\',\''.$value.'\',\''.$type.'\'); return false;"/></div>
				';
			
			break;
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

function cflk_process($cflk_data = array(), $cflk_key = '', $cflk_nicename = '', $cflk_description = '') {
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
	while(count($wpdb->get_results($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%s'", $option_name))) > 0) {
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
			);
		}
		return $return;
	}
	return false;
}

/**
 * 
 * CF Links Widget Handling Functions
 * 
 */

function cflk_widget( $args, $widget_args = 1 ) {
	global $cflk_inside_widget;
	$cflk_inside_widget = true;
	extract( $args, EXTR_SKIP );
	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widget_args );
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	extract( $widget_args, EXTR_SKIP );

	$options = get_option('cf_links_widget');
	if ( !isset($options[$number]) )
		return;
	$title = $options[$number]['title'];
	$select = $options[$number]['select'];
	
	echo $before_widget;
	if (!empty($title)) {
		echo $before_title . $title . $after_title;
	}
	echo cflk_get_links($select);
	echo $after_widget;
	$cflk_inside_widget = false;
}

function cflk_widget_control( $widget_args = 1 ) {
	global $wp_registered_widgets, $wpdb;
	static $updated = false;

	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widget_args );
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	extract( $widget_args, EXTR_SKIP );

	$options = get_option('cf_links_widget');
	if ( !is_array($options) )
		$options = array();

	if ( !$updated && !empty($_POST['sidebar']) ) {
		$sidebar = (string) $_POST['sidebar'];
		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( isset($sidebars_widgets[$sidebar]) )
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();

		foreach ( $this_sidebar as $_widget_id ) {
			if ( 'cf_links_widget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
				if ( !in_array( "cf-links-$widget_number", $_POST['widget-id'] ) )
					unset($options[$widget_number]);
			}
		}
		foreach ( (array) $_POST['cfl-links'] as $widget_number => $cfl_links_instance ) {
			if ( !isset($cfl_links_instance['title']) && isset($options[$widget_number]) )
				continue;
			$title = trim(strip_tags(stripslashes($cfl_links_instance['title'])));
			$select = $cfl_links_instance['select'];
			$options[$widget_number] = compact('title','select');
		}
		update_option('cf_links_widget', $options);
		$updated = true;
	}
	if ( -1 == $number ) { 
		$title = '';
		$select = '';
		$number = '%i%';
	} else {
		$title = attribute_escape($options[$number]['title']);
		$select = $options[$number]['select'];
	}

	$cflk_list = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",'cfl-%'));
	$form_data = array();
	foreach ($cflk_list as $cflk) {
		$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
		$push = array('option_name' => $cflk->option_name, 'nicename' => $options['nicename']);
		array_push($form_data,$push);
	}
	?>
	<p>
		<label for="cfl-links-title-<?php echo $number; ?>"><?php _e('Title: ', 'cf-links'); ?></label>
		<br />
		<input id="cfl-links-title-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][title]" class="widefat" type="text" value="<?php print (strip_tags($title)); ?>" />
	</p>
	<p>
		<label for="cfl-links-select-<?php echo $number; ?>"><?php _e('Links List: ', 'cf-links'); ?></label>
		<br />
		<select id="cfl-links-select-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][select]" class="widefat">
			<?php
			foreach ($form_data as $data => $info) {
				if ($info['option_name'] == $select) {
					$selected = 'selected=selected';
				} 
				else {
					$selected = '';
				}
				?>
				<option value="<?php print (htmlspecialchars($info['option_name'])); ?>" <?php print ($selected); ?>><?php print (htmlspecialchars($info['nicename'])); ?></option>
				<?php
			}
			?>
		</select>
	</p>
	<p>
		<a href="<?php bloginfo('wpurl') ?>/wp-admin/options-general.php?page=cf-links.php"><?php _e('Edit Links','cf-links') ?></a>
	</p>
	<input type="hidden" id="cfl-links-submit-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][submit]" value="1" />
<?php
}

function cflk_widget_register() {
	if ( !$options = get_option('cf_links_widget') )
		$options = array();

	$widget_ops = array('classname' => 'cf_links_widget', 'description' => __('Widget for showing links entered in the Advanced Links settings page (Version 1.0, upgrade to 2.0 to continue functionality).','cf-links'));
	$name = __('CF Links 1.0', 'cf-links');

	$id = false;
	foreach ( array_keys($options) as $o ) {
		if ( !isset($options[$o]['title']) )
			continue;
		$id = "cf-links-$o";
		wp_register_sidebar_widget( $id, $name, 'cflk_widget', $widget_ops, array( 'number' => $o ) );
		wp_register_widget_control( $id, $name, 'cflk_widget_control', array( 'id_base' => 'cf-links' ), array( 'number' => $o ) );
	}
	if ( !$id ) {
		wp_register_sidebar_widget( 'cf-links-1', $name, 'cflk_widget', $widget_ops, array( 'number' => -1 ) );
		wp_register_widget_control( 'cf-links-1', $name, 'cflk_widget_control', array( 'id_base' => 'cf-links' ), array( 'number' => -1 ) );
	}
}
add_action( 'widgets_init', 'cflk_widget_register' );

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
	
	if (!is_array($list) || $list == FALSE) { echo 'Could not find link list: '.htmlspecialchars($key); return; }
	
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
					if (isset($postinfo->post_status) && in_array($postinfo->post_status, array('publish', 'inherit'))) {
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
					else if (is_a($userdata, 'WP_User')) {
						$type_text = $userdata->data->display_name;
						$other = 'rss';
						$href = get_author_feed_link($userdata->data->ID);
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
