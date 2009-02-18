<?php
/*
Plugin Name: CF Links
Plugin URI: http://crowdfavorite.com
Description: Advanced options for adding links
Version: 1.1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

/*
// below is a usage example on applying a filter to the cflk_get_links_data
// simple example that changes the log in/out link text
// $links = array(
// 	'nicename' => 'full name of links list',
// 	'data' => array(
// 		array(
// 			'type' => 'link_type',
// 			'link' => 'link_value',
// 			'title' => 'link text'
// 		),
// 		...
// 	)
// );
function hn_login_cflinks_filter($links) {
	$user = wp_get_current_user();
	foreach($links['data'] as $key => $link) {
		if($link['type'] == 'wordpress' && $link['link'] == 'loginout') {
			if($user->ID == 0) {
				// user not logged in, make sure they go to the auth login page instead of wordpress'
				$links['data'][$key]['title'] = 'Log In Here';
			}
			else {
				// user logged in, make sure the logout redirect doesn't go to wp-login or wp-admin
				$links['data'][$key]['title'] = 'Log Out Here';
			}
		}
	}
	return $links;
}
add_filter('cflk_get_links_data','hn_login_cflinks_filter');

*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

/**
 * 
 * WP Admin Handling Functions
 * 
 */

load_plugin_textdomain('cf-links');
$cflk_types = array();

function cflk_link_types() {
	global $wpdb, $cflk_types, $blog_id;
	
	$pages = get_pages();
	$categories = get_categories('get=all');
	$authors = get_users_of_blog($blog_id);

	$page_data = array();
	$category_data = array();
	$author_data = array();
	$wordpress_data = array();
	$blog_data = array();
	
	foreach ($pages as $page) {
		$page_data[$page->post_name] = array(
			'link' => $page->ID, 
			'description' => $page->post_title
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
		$author_data[$author->user_login] = array(
				'link' => $author->user_id, 
				'description' => $author->display_name
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
		$blogs = get_blog_list();
		foreach ($blogs as $blog) {
			if ($blog_id != $blog['blog_id']) {
				$details = get_blog_details($blog['blog_id']);
				$blog_data[$details->blog_id] = array(
						'link' => $blog['blog_id'], 
						'description' => $details->blogname
				);
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
	if(function_exists('get_blog_list')) {
		krsort($blog_data);
		$blog_type = array(
			'blog' => array(
				'type' => 'blog', 
				'nicename' => __('Blog','cf-links'),
				'input' => 'select', 
				'data' => $blog_data
			)
		);
		$cflk_types = array_merge($cflk_types, $blog_type);
	}
}
add_action('init', 'cflk_link_types');

function cflk_menu_items() {
	if (current_user_can('manage_options')) {
		add_options_page(
			__('CF Links', 'cf-links')
			, __('CF Links', 'cf-links')
			, 10
			, basename(__FILE__)
			, 'cflk_check_page'
		);
	}
}
add_action('admin_menu', 'cflk_menu_items');

function cflk_check_page() {
	$check_page = '';
	if (isset($_GET['cflk_page'])) {
		$check_page = $_GET['cflk_page'];
	}
	switch ($check_page) {
		case 'main':
			cflk_options_form();
			break;
		case 'edit':
			cflk_edit();
			break;
		case 'create':
			cflk_new();
			break;
		case 'import':
			cflk_import();
			break;
		default:
			cflk_options_form();
			break;
	}
}

function cflk_request_handler() {
	if (current_user_can('manage_options')) {
		if (isset($_POST['cf_action']) && $_POST['cf_action'] != '') {
			switch ($_POST['cf_action']) {
				case 'cflk_update_settings':
					if (isset($_POST['cflk'])) {
						$link_data = stripslashes_deep($_POST['cflk']);
						if (isset($_POST['cflk_key']) && $_POST['cflk_key'] != '' && isset($_POST['cflk_nicename']) && $_POST['cflk_nicename'] != '') {
							cflk_process($link_data, $_POST['cflk_key'], $_POST['cflk_nicename'], $_POST['cflk_description']);
						}
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$_POST['cflk_key'].'&cflk_message=updated');
					}
					break;
				case 'cflk_delete':
					if (isset($_POST['cflk_key']) && $_POST['cflk_key'] != '') {
						cflk_delete($_POST['cflk_key']);
					}
					break;
				case 'cflk_delete_key':
					if (isset($_POST['cflk_key']) && isset($_POST['key']) && $_POST['cflk_key'] != '' && $_POST['key'] != '') {
						cflk_delete_key($_POST['cflk_key'], $_POST['key']);
					}
					break;
				case 'cflk_insert_new':
					$nicename = '';
					$description = '';
					$data = '';
					
					if (isset($_POST['cflk_create']) && $_POST['cflk_create'] == 'new_list') {
						if (isset($_POST['cflk_nicename']) && $_POST['cflk_nicename'] != '') {
							$nicename = $_POST['cflk_nicename'];
							$data = array();
						}
					}
					if (isset($_POST['cflk_create']) && $_POST['cflk_create'] == 'import_list') {
						if (isset($_POST['cflk_import']) && $_POST['cflk_import'] != '') {
							$import = maybe_unserialize(stripslashes($_POST['cflk_import']));
							$nicename = $import['nicename'];
							$description = $import['description'];
							$data = $import['data'];
						}
					}
					if ($nicename != '' && is_array($data)) {
						$cflk_key = cflk_insert_new($nicename, $description, $data);
					}
					wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$cflk_key);
					break;
				case 'cflk_edit_nicename':
					if (isset($_POST['cflk_nicename']) && $_POST['cflk_nicename'] != '' && isset($_POST['cflk_key']) && $_POST['cflk_key'] != '') {
						cflk_edit_nicename($_POST['cflk_key'], $_POST['cflk_nicename']);
					}
					break;
				default:
					break;
			}
		}
	}
	if (!empty($_GET['cflk_page'])) {
		switch ($_GET['cflk_page']) {
			case 'cflk_admin_js':
				cflk_admin_js();
				break;
			case 'cflk_admin_css':
				cflk_admin_css();
				break;
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
add_action('init', 'cflk_request_handler');
add_action('wp_ajax_cflk_update_settings', 'cflk_request_handler');

function cflk_ajax() {
	wp_print_scripts(array('sack'));
	?>
	<script type="text/javascript">
		//<![CDATA[
		// @TODO keep the unique functions for unique functionality, but take repeated code and move it to a separate function that each of these functions then pass config variables to
		function cflkAJAXDeleteLink(cflk_key,key) {
			var cflk_sack = new sack("<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php");
			cflk_sack.execute = 1;
			cflk_sack.method = 'POST';
			cflk_sack.setVar('cf_action', 'cflk_delete_key');
			cflk_sack.setVar('key', key);
			cflk_sack.setVar('cflk_key', cflk_key);
			cflk_sack.encVar('cookie', document.cookie, false);
			cflk_sack.onError = function() {alert('AJAX error in updating settings.  Please click update button below to save your settings.');};
			cflk_sack.runAJAX();
			return true;
		}
		function cflkAJAXDeleteMain(cflk_key) {
			var cflk_main_sack = new sack("<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php");
			cflk_main_sack.execute = 1;
			cflk_main_sack.method = 'POST';
			cflk_main_sack.setVar('cf_action', 'cflk_delete');
			cflk_main_sack.setVar('cflk_key', cflk_key);
			cflk_main_sack.encVar('cookie', document.cookie, false);
			cflk_main_sack.onError = function() {alert('AJAX error in updating settings.  Please click update button below to save your settings.');};
			cflk_main_sack.runAJAX();
			return true;
		}
		function cflkAJAXSaveNicename(cflk_key, cflk_nicename) {
			var cflk_nicename_sack = new sack("<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php");
			cflk_nicename_sack.execute = 1
			cflk_nicename_sack.method = 'POST';
			cflk_nicename_sack.setVar('cf_action', 'cflk_edit_nicename');
			cflk_nicename_sack.setVar('cflk_key', cflk_key);
			cflk_nicename_sack.setVar('cflk_nicename', cflk_nicename);
			cflk_nicename_sack.encVar('cookie', document.cookie, false);
			cflk_nicename_sack.onError = function() {alert('AJAX error in updating settings.  Please click update button below to save your settings.');};
			cflk_nicename_sack.runAJAX();
			return true;
		}
		//]]>
	</script>
	<?php
}
add_action('admin_print_scripts', 'cflk_ajax');

function cflk_admin_css() {
	header('Content-type: text/css');
	?>
	#cflk-list { list-style: none; padding: 0; margin: 0; }
	#cflk-list li { margin: 0; padding: 0; }
	#cflk-list .handle { cursor: move; }
	#cflk-log { padding: 5px; border: 1px solid #ccc; }
	.cflk-info { list-style: none; }
	.cflk_edit_link {
		font-size: 10px;
		font-weight: normal;
	}
	.cflk-codebox {
		padding: 10px;
		background-color: #E4F2FD;
		border: 1px solid #C6D9E9;
	}
	.tr_holder {
		background-color: #FFF07E;
	}
	<?php
	die();
}

function cflk_admin_js() {
	header('Content-type: text/javascript');
?>
	// When the document is ready set up our sortable with its inherant function(s)
	jQuery(document).ready(function() {
		jQuery("#cflk-list").sortable({
			handle : ".handle",
			update : function () {
				jQuery("input#cflk-log").val(jQuery("#cflk-list").sortable("serialize"));
			}
		});
		jQuery('input[name="link_edit"]').click(function() {
			location.href = "<?php echo get_bloginfo('wpurl'); ?>/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link=" + jQuery(this).attr('rel');
			return false;
		});
		jQuery('tr.tr_holder').each(function() {
			jQuery('#message_import_problem').attr('style','');
		});
	});
	function deleteLink(cflk_key,linkID) {
		if (confirm('Are you sure you want to delete this?')) {
			if (cflkAJAXDeleteLink(cflk_key,linkID)) {
				jQuery('#listitem_'+linkID).remove();
				jQuery("#message_delete").attr("style","");
			}
			return false;
		}
	}
	function deleteCreated(linkID) {
		if (confirm('Are you sure you want to delete this?')) {
			jQuery('#listitem_'+linkID).remove();
			return false;
		}
	}
	function deleteMain(cflk_key) {
		if (confirm('Are you sure you want to delete this?')) {
			if (cflkAJAXDeleteMain(cflk_key)) {
				jQuery('#link_main_'+cflk_key).remove();
				jQuery("#message_delete").attr("style","");
			}
			return false;
		}
	}
	function editNicename() {
		jQuery('#cflk_nicename_h3').attr('style','display: none;');
		jQuery('#cflk_nicename_input').attr('style','');
	}
	function cancelNicename() {
		jQuery('#cflk_nicename_input').attr('style','display: none;');
		jQuery('#cflk_nicename_h3').attr('style','');
	}
	function saveNicename(cflk_key) {
		if (cflkAJAXSaveNicename(cflk_key, jQuery("#cflk_nicename_new").val())) {
			jQuery("#message").attr("style","");
			jQuery("#cflk_nicename_text").text(jQuery("#cflk_nicename_new").val());
			jQuery("#cflk_nicename").text(jQuery("#cflk_nicename_new").val());
			jQuery("#cflk_nicename_h2").text(jQuery("#cflk_nicename_new").val());
			cancelNicename();
		}
	}
	function editTitle(key) {
		jQuery('#cflk_'+key+'_title_edit').attr('style','display: none;');
		jQuery('#cflk_'+key+'_title_input').attr('style','');
	}
	function clearTitle(key) {
		jQuery('#cflk_'+key+'_title_input').attr('style','display: none;');
		jQuery('#cflk_'+key+'_title_edit').attr('style','');
		jQuery('#cflk_'+key+'_title').val('');
	}
	function editDescription() {
		jQuery('#description_text').attr('style', 'display:none;');
		jQuery('#description_edit').attr('style', '');
		jQuery('#description_edit_btn').attr('style', 'display:none;');
		jQuery('#description_cancel_btn').attr('style', '');
	}
	function cancelDescription() {
		jQuery('#description_text').attr('style', '');
		jQuery('#description_edit').attr('style', 'display:none;');
		jQuery('#description_edit_btn').attr('style', '');
		jQuery('#description_cancel_btn').attr('style', 'display:none;');
	}
	function showLinkType(key) {
		var text = jQuery('#cflk_'+key+'_title').val();
		switch (jQuery("#cflk_"+key+"_type").val()) {
			case 'url':
				jQuery("#url_"+key).attr("style", "").siblings().attr("style", "display: none;");
				editTitle(key);
				break;
			case 'rss':
				jQuery("#rss_"+key).attr("style", "").siblings().attr("style", "display: none;");
				editTitle(key);
				break;
			case 'page':
				if(text == '') {
					clearTitle(key);
				}
				jQuery("#page_"+key).attr("style", "").siblings().attr("style", "display: none;");
				break;
			case 'category':
				if(text == '') {
					clearTitle(key);
				}
				jQuery("#category_"+key).attr("style", "").siblings().attr("style", "display: none;");
				break;
			case 'wordpress':
				if(text == '') {
					clearTitle(key);
				}
				jQuery("#wordpress_"+key).attr("style", "").siblings().attr("style", "display: none;");
				break;
			case 'author_rss':
				if(text == '') {
					clearTitle(key);
				}
				jQuery("#author_rss_"+key).attr("style", "").siblings().attr("style", "display: none;");
				break;
			case 'blog':
				if(text == '') {
					clearTitle(key);
				}
				jQuery("#blog_"+key).attr("style", "").siblings().attr("style", "display: none;");
				break;
		}
	}
	function showLinkCode(key) {
		jQuery('#'+key).slideToggle();
	}
	function addLink() {
		var id = new Date().valueOf();
		var section = id.toString();
		
		var html = jQuery('#newitem_SECTION').html().replace(/###SECTION###/g, section);
		jQuery('#cflk-list').append(html);
		jQuery('#listitem_'+section).attr('style','');
	}
	function changeExportList() {
		var list = jQuery('#list-export').val();
		var btn = jQuery('#cflk-export-btn');
		btn.attr('alt', 'index.php?cflk_page=export&height=400&width=600&link='+list);	
	}
<?php
	die();
}

function cflk_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('wpurl')).'index.php?cflk_page=cflk_admin_css" />';
	echo '<script type="text/javascript" src="'.trailingslashit(get_bloginfo('wpurl')).'index.php?cflk_page=cflk_admin_js"></script>';
	echo '<link rel="stylesheet" href="'.trailingslashit(get_bloginfo('wpurl')).'/wp-includes/js/thickbox/thickbox.css" type="text/css" media="screen" />';
	
}
if (isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('thickbox');
	if (!function_exists('wp_prototype_before_jquery')) {
		function wp_prototype_before_jquery( $js_array ) {
			if ( false === $jquery = array_search( 'jquery', $js_array ) )
				return $js_array;
			if ( false === $prototype = array_search( 'prototype', $js_array ) )
				return $js_array;
			if ( $prototype < $jquery )
				return $js_array;
			unset($js_array[$prototype]);
			array_splice( $js_array, $jquery, 0, 'prototype' );
			return $js_array;
		}
	    add_filter( 'print_scripts_array', 'wp_prototype_before_jquery' );
	}	
	add_action('admin_head', 'cflk_admin_head');
}

/**
 * 
 * CF Links Admin Interface Functions
 * 
 */

function cflk_options_form() {
	global $wpdb;
	$cflk_list = cflk_get_list_links();
	$form_data = array();
	foreach ($cflk_list as $key => $cflk) {
		$form_data[$key] = array('nicename' => $cflk['nicename'], 'count' => $cflk['count']);
	}
	
	if ( isset($_GET['cflk_message']) ) {
		switch ($_GET['cflk_message']) {
			case 'create':
				print ('
					<div id="message_create" class="updated fade">
						<p>'.__('Links List Created.', 'cf-links').'</p>
					</div>
				');
				break;
			case 'delete':
				print ('
					<div id="message_delete" class="updated fade">
						<p>'.__('Links List Deleted.', 'cf-links').'</p>
					</div>
				');
				break;
			default:
				break;
		}
	}
	print ('
		<div class="wrap">
			'.cflk_nav('main').'
			<form action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post" id="cflk-form">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Links List', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="80px">'.__('Links Count', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Edit', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Delete', 'cf-links').'</th>
						</tr>
					</thead>
					<tbody>
					');
					if (count($form_data) > 0) {
						foreach ($form_data as $key => $info) {
							print ('
								<tr id="link_main_'.$key.'">
									<td style="vertical-align: middle;">
										<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$key.'" style="font-weight: bold; font-size: 20px;">'.$info['nicename'].'</a>
										<br />
										'.__('Show: ','cf-links').'<a href="#" onClick="showLinkCode(\''.$key.'-TemplateTag\')">'.__('Template Tag','cf-links').'</a> | <a href="#" onClick="showLinkCode(\''.$key.'-ShortCode\')">'.__('Shortcode','cf-links').'</a>
										<div id="'.$key.'-TemplateTag" class="cflk-codebox" style="display:none;">
											<div style="float: left;"><code>'.htmlentities('<?php if (function_exists("cflk_links")) { cflk_links("'.$key.'"); } ?>').'</code></div><div style="float: right;"><a href="#" onClick="showLinkCode(\''.$key.'-TemplateTag\')">'.__('Hide','cf-links').'</a></div><div class="clear"></div>
										</div>
										<div id="'.$key.'-ShortCode" class="cflk-codebox" style="display:none;">
											<div style="float: left;"><code>'.htmlentities('[cflk_links name="'.$key.'"]').'</code></div><div style="float: right;"><a href="#" onClick="showLinkCode(\''.$key.'-ShortCode\')">'.__('Hide','cf-links').'</a></div><div class="clear"></div>
										</div>
									</td>
									<td style="text-align: center; vertical-align: middle;" width="80px">
										'.$info['count'].'
									</td>
									<td style="text-align: center; vertical-align: middle;" width="60px">
										<p class="submit" style="border-top: none; padding: 0; margin: 0;">
											<input type="button" name="link_edit" value="'.__('Edit', 'cf-links').'" class="button-secondary edit" rel="'.$key.'" />
										</p>
									</td>
									<td style="text-align: center; vertical-align: middle;" width="60px">
										<p class="submit" style="border-top: none; padding: 0; margin: 0;">
											<input type="button" id="link_delete_'.$key.'" onclick="deleteMain(\''.$key.'\')" value="'.__('Delete', 'cf-links').'" />
										</p>
									</td>
								</tr>
							');
						}
					}
				print ('
					</tbody>
				</table>
			</form>
		</div>
	');
}

function cflk_new() {
	print ('
		<div class="wrap">
			'.cflk_nav('create').'
			<form action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post" id="cflk-create">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Link List Name', 'cf-links').'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<input type="text" name="cflk_nicename" size="55" />
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="cf_action" value="cflk_insert_new" />
					<input type="hidden" name="cflk_create" id="cflk_create" value="new_list" />
					<input type="submit" name="submit" id="cflk-submit" value="'.__('Create List', 'cf-links').'" />
				</p>
			</form>
		</div>
	');
}

function cflk_import() {
	$links_lists = cflk_get_list_links();
	print('
		<div class="wrap">
			'.cflk_nav('import').'
			<table class="widefat" style="margin-bottom:10px;">
				<thead>
					<tr>
						<th scope="col">'.__('Export Link Data','cf-links').'</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<select id="list-export" onChange="changeExportList()">
								<option value="0">Select List:</option>
							');
							foreach ($links_lists as $key => $value) {
								print('<option value="'.$key.'">'.$value['nicename'].'</option>');
							}
							print('
							</select>
							<input alt="" title="Export '.$cflk['nicename'].'" class="thickbox button" type="button" value="'.__('Export', 'cf-links').'" id="cflk-export-btn" />						
						</td>
					</tr>
				</tbody>
			</table>
			<form action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post" id="cflk-create">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Enter Data From Export', 'cf-links').'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<textarea name="cflk_import" rows="15" style="width:100%;"></textarea>
							</td>
						</tr>
					</tbody>
				</table>				
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="cf_action" value="cflk_insert_new" />
					<input type="hidden" name="cflk_create" id="cflk_create" value="import_list" />
					<input type="submit" name="submit" class="button-primary button" id="cflk-submit" value="'.__('Import List', 'cf-links').'" />
				</p>
			</form>
		</div>
	');
}

function cflk_edit() {
	global $wpdb, $cflk_types;
	
	if (isset($_GET['link']) && $_GET['link'] != '') {
		$cflk_key = $_GET['link'];
		$cflk = maybe_unserialize(get_option($cflk_key));
		is_array($cflk) ? $cflk_count = count($cflk) : $cflk_count = 0;
		
		if ( isset($_GET['cflk_message']) && $_GET['cflk_message'] = 'updated' ) {
			print ('
				<div id="message" class="updated fade">
					<p>'.__('Settings updated.', 'cf-links').'</p>
				</div>
			');
		}
		print ('
			<div id="message_delete" class="updated fade" style="display: none;">
				<p>'.__('Link deleted.', 'cf-links').'</p>
			</div>			
			<div id="message_import_problem" class="updated fade" style="display: none;">
				<p>'.__('A problem has been detected while using the import.  Please see highlighted items below to fix.', 'cf-links').'</p>
			</div>			
			<div class="wrap">
				<form action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post" id="cflk-form">
					'.cflk_nav('edit', htmlspecialchars($cflk['nicename']), $cflk_key).'
					<table class="widefat" style="margin-bottom: 10px;">
						<thead>
							<tr>
								<th scope="col" colspan="2">
									Description
								</th>
							</tr>
						</thead>
						<tr>
							<td>
								<div id="description_text">');
									if($cflk['description'] != '') {
										print('
										<p>
											'.htmlspecialchars($cflk['description']).'
										</p>
										');
									}
									else {
										print('
										<p>
											<span style="color:#999999;">
												'.__('(No description has been set for this links list.  Click the edit button to enter a description. &rarr;)','cf-links').'
											</span>
										</p>
										');
									}
									print('
								</div>
								<div id="description_edit" style="display:none;">
									<textarea name="cflk_description" rows="5" style="width:100%;">'.htmlspecialchars($cflk['description']).'</textarea>
								</div>
							</td>
							<td width="150px" style="text-align:right; vertical-align:middle;">
								<div id="description_edit_btn">
									<input type="button" class="button" id="link_description_btn" value="'.__('Edit', 'cf-links').'" onClick="editDescription()" />
								</div>
								<div id="description_cancel_btn" style="display:none;">
									<input type="button" class="button" id="link_description_cancel" value="'.__('Cancel', 'cf-links').'" onClick="cancelDescription()" />
								</div>
							</td>
						</tr>
					</table>
					<table class="widefat">
						<thead>
							<tr>
								<th scope="col" width="40px" style="text-align: center;">'.__('Order', 'cf-links').'</th>
								<th scope="col" width="90px">'.__('Type', 'cf-links').'</th>
								<th scope="col">'.__('Link', 'cf-links').'</th>
								<th scope="col" width="250px">'.__('Link Text (Optional)', 'cf-links').'</th>
								<th scope="col" style="text-align: center;" width="60px">'.__('Delete', 'cf-links').'</th>
							</tr>
						</thead>
					</table>
					<ul id="cflk-list">');
						if ($cflk_count > 0) {
							foreach ($cflk['data'] as $key => $setting) {
								$select_settings = cflk_edit_select($setting['type']);
								$tr_class = '';
								if($setting['link'] == 'HOLDER') {
									$tr_class = ' class="tr_holder"';
								}
								print ('<li id="listitem_'.$key.'">
									<table class="widefat">
										<tr'.$tr_class.'>
											<td width="40px" style="text-align: center;"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/cf-links/images/arrow_up_down.png" class="handle" alt="move" /></td>
											<td width="90px">
												<select name="cflk['.$key.'][type]" id="cflk_'.$key.'_type" onChange="showLinkType('.$key.')">');
													foreach ($cflk_types as $type) {
														print ('<option value="'.$type['type'].'" '.$select_settings[$type['type'].'_select'].'>'.$type['nicename'].'</option>');
													}
												print ('</select>
											</td>
											<td>');
												foreach ($cflk_types as $type) {
													echo cflk_get_type_input($type['type'], $type['input'], $type['data'], $select_settings[$type['type'].'_show'], $key, $setting['cat_posts'], $setting['link']);
												}
												print ('
											</td>
											<td width="250px">');
												if (htmlspecialchars($setting['title']) == '') {
													$edit_show = '';
													$input_show = ' style="display:none;"';
												}
												else {
													$edit_show = ' style="display:none;"';
													$input_show = '';
												}
												print ('
												<span id="cflk_'.$key.'_title_edit"'.$edit_show.'>
													<input type="button" class="button" id="link_edit_title_'.$key.'" value="'.__('Edit Text', 'cf-links').'" onClick="editTitle(\''.$key.'\')" />
												</span>
												<span id="cflk_'.$key.'_title_input"'.$input_show.'>
													<input type="text" id="cflk_'.$key.'_title" name="cflk['.$key.'][title]" value="'.htmlspecialchars($setting['title']).'" style="max-width: 195px;" />
													<input type="button" class="button" id="link_clear_title_'.$key.'" value="'.__('Clear', 'cf-links').'" onClick="clearTitle(\''.$key.'\')" />
												</span>
												');
											print ('
											</td>
											<td width="60px" style="text-align: center;">
												<input type="button" class="button" id="link_delete_'.$key.'" value="'.__('Delete', 'cf-links').'" onClick="deleteLink(\''.$cflk_key.'\',\''.$key.'\')" />
											</td>
										</tr>
									</table>
								</li>');
							}
						}
						print ('
					</ul>
					<table class="widefat">
						<tr>
							<td style="text-align:left;">
								<input type="button" class="button" name="link_add" id="link_add" value="'.__('Add New Link', 'cf-links').'" onClick="addLink()" />
							</td>
						</tr>
					</table>
					<p class="submit" style="border-top: none;">
						<input type="hidden" name="cf_action" value="cflk_update_settings" />
						<input type="hidden" name="cflk_key" value="'.attribute_escape($cflk_key).'" />
						<input type="submit" name="submit" id="cflk-submit" value="'.__('Update Settings', 'cf-links').'" class="button-primary button" />
					</p>
				</form>');
			print ('<div id="newitem_SECTION">
				<li id="listitem_###SECTION###" style="display:none;">
					<table class="widefat">
						<tr>
							<td width="40px" style="text-align: center;"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/cf-links/images/arrow_up_down.png" class="handle" alt="move" /></td>
							<td width="90px">
								<select name="cflk[###SECTION###][type]" id="cflk_###SECTION###_type" onChange="showLinkType(###SECTION###)">');
									foreach ($cflk_types as $type) {
										$select_settings[$type['type'].'_select'] = '';
										if ($type['type'] == 'url') {
											$select_settings[$type['type'].'_select'] = 'selected=selected';
										}
										print ('<option value="'.$type['type'].'" '.$select_settings[$type['type'].'_select'].'>'.$type['nicename'].'</option>');
									}
								print ('</select>
							</td>
							<td>');
								$key = '###SECTION###';
								foreach ($cflk_types as $type) {
									$select_settings[$type['type'].'_show'] = 'style="display: none;"';
									if ($type['type'] == 'url') {
										$select_settings[$type['type'].'_show'] = 'style=""';
									}
									echo cflk_get_type_input($type['type'], $type['input'], $type['data'], $select_settings[$type['type'].'_show'], $key, '', '');
								}
								print ('
							</td>
							<td width="250px">
								<span id="cflk_###SECTION###_title_edit" style="display: none">
									<input type="button" class="button" id="link_edit_title_###SECTION###" value="'.__('Edit Text', 'cf-links').'" onClick="editTitle(\'###SECTION###\')" />
								</span>
								<span id="cflk_###SECTION###_title_input">
									<input type="text" id="cflk_###SECTION###_title" name="cflk[###SECTION###][title]" value="" style="max-width: 195px;" />
									<input type="button" class="button" id="link_clear_title_###SECTION###" value="'.__('Clear', 'cf-links').'" onClick="clearTitle(\'###SECTION###\')" />
									<br />
									'.__('ex: Click Here','cf-links').'
								</span>
							</td>
							<td width="60px" style="text-align: center;">
								<input type="button" class="button" id="link_delete_###SECTION###" value="'.__('Delete', 'cf-links').'" onClick="deleteLink(\''.$cflk_key.'\',\'###SECTION###\')" />
							</td>
						</tr>
					</table>
				</li>
			</div>');
			print ('</div>
		');
	} else {
		print ('
			<div id="message" class="updated fade">
				<p>'.__('You were directed to this page in error.  Please <a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php">go here</a> to edit options for this plugin.', 'cf-links').'</p>
			</div>
		');
	}
}

function cflk_nav($page = '', $list = '') {
	$cflk_nav = '';
	$cflk_nav = '<div class="icon32" id="icon-link-manager"><br/></div><h2>'.__('Manage CF Links').'</h2>';
	
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
	$cflk_nav .= '
		<ul class="subsubsub">
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=main" '.$main_text.'>'.__('Links Lists', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=create" '.$add_text.'>'.__('New Links List', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=import" '.$import_text.'>'.__('Import/Export Links List', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/widgets.php">'.__('Edit Widgets','cf-links').'</a>
			</li>
		</ul>
	';
	
	if ($list != '') {
		$cflk_nav .= '<h3 style="clear:both;">'.__('Links Options', 'cf-links');
		$cflk_nav .= ' '.__('for: ','cf-links').'<span id="cflk_nicename_h3">'.$list.' <a href="#" class="cflk_edit_link" onClick="editNicename()">Edit</a></span>';
		$cflk_nav .= '<span id="cflk_nicename_input" style="display: none;">
							<input type="text" name="cflk_nicename" id="cflk_nicename" value="'.attribute_escape($list).'" />
							<input type="submit" name="submit" id="cflk-submit" class="button" value="'.__('Save', 'cf-links').'" />
							<input type="button" name="link_nicename_cancel" id="link_nicename_cancel" class="button" value="'.__('Cancel', 'cf-links').'" onClick="cancelNicename()" />					
						</span>';
		$cflk_nav .= '</h3>';
		
	}	
	return($cflk_nav);
}

function cflk_edit_select($type) {
	$select = array();
	
	$select[url_show] = 'style="display: none;"';
	$select[url_select] = '';
	$select[rss_show] = 'style="display: none;"';
	$select[rss_select] = '';
	$select[page_show] = 'style="display: none;"';
	$select[page_select] = '';
	$select[category_show] = 'style="display: none;"';
	$select[category_select] = '';
	$select[wordpress_show] = 'style="display: none;"';
	$select[wordpress_select] = '';
	$select[author_rss_show] = 'style="display: none;"';
	$select[author_rss_select] = '';
	$select[blog_show] = 'style="display: none;"';
	$select[blog_select] = '';
	
	switch ($type) {
		case 'url':
			$select[url_show] = 'style=""';
			$select[url_select] = 'selected=selected';
			break;
		case 'rss':
			$select[rss_show] = 'style=""';
			$select[rss_select] = 'selected=selected';
			break;
		case 'page':
			$select[page_show] = 'style=""';
			$select[page_select] = 'selected=selected';
			break;
		case 'category':
			$select[category_show] = 'style=""';
			$select[category_select] = 'selected=selected';
			break;	
		case 'wordpress':
			$select[wordpress_show] = 'style=""';
			$select[wordpress_select] = 'selected=selected';
			break;	
		case 'author_rss':
			$select[author_rss_show] = 'style=""';
			$select[author_rss_select] = 'selected=selected';
			break;	
		case 'blog':
			$select[blog_show] = 'style=""';
			$select[blog_select] = 'selected=selected';
			break;	
		default:
			$select[url_show] = 'style=""';
			$select[url_select] = 'selected=selected';
			break;
	}
	return $select;
}

function cflk_get_type_input($type, $input, $data, $show, $key, $show_count, $value) {
	$return = '';
	
	$return .= '<span id="'.$type.'_'.$key.'" '.$show.'>';
	switch ($input) {
		case 'text':
			$return .= '<input type="text" name="cflk['.$key.']['.$type.']" id="cflk_'.$key.'_'.$type.'" size="50" value="'.htmlspecialchars($value).'" /><br />'.$data;
			break;
		case 'select':
			$return .= '<select name="cflk['.$key.']['.$type.']" id="cflk_'.$key.'_'.$type.'" style="max-width: 410px; width: 410px;">';
			foreach ($data as $info) {
				$selected = '';
				$count_text = '';
				if ($value == $info['link']) {
					$selected = ' selected=selected';
				}
				if ($show_count == 'yes' && isset($info['count'])) {
					$count_text = ' ('.$info['count'].')';
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
					case 'author_rss':
						$type_show = 'Author';
						break;
					
				}
				$return .= '<br /><span id="holder_'.$type.'_'.$key.'" style="font-weight:bold;">'.__('Imported item ID does not exist in the system.<br />Please create a new '.$type_show.', then select it from the list above.','cf-links').'</span>';
			}
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
	?>
	<script type='text/javascript' src='<?php print (get_bloginfo('url')); ?>/wp-includes/js/jquery/jquery.js'></script>
	<script type='text/javascript' src='<?php print (get_bloginfo('url')); ?>/wp-includes/js/quicktags.js'></script>
	<script type="text/javascript">
		function cflkSetText(text) {
			var length = jQuery('#cflk_dialog_length').val();
			
			text = '<p>[cflk_links name="' + text + '"';
			if (length > 0) {
				text = text + ' length="' + length + '"';
			}
			text = text + ']</p>';
	
			parent.window.tinyMCE.execCommand("mceBeginUndoLevel");
			parent.window.tinyMCE.execCommand('mceInsertContent', false, '<p>'+text+'</p>');
			parent.window.tinyMCE.execCommand("mceEndUndoLevel");
		}
	</script>
	<p>
		<ul>
		<?
		$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
		foreach ($cflk_list as $cflk) {
			$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
			?>
			<li>
				<a href="#" onclick="cflkSetText('<?php print (htmlspecialchars($cflk->option_name)); ?>')"><?php print (htmlspecialchars($options['nicename'])); ?></a>
			</li>
			<?php
		}
		?>
		</ul>
	</p>
	<?php
	die();
}

function cflk_register_button($buttons) {
	array_push($buttons, '|', "cfLinksBtn");
	return $buttons;
}

function cflk_add_tinymce_plugin($plugin_array) {
	$plugin_array['cflinks'] = get_bloginfo('wpurl') . '/wp-content/plugins/cf-links/js/editor_plugin.js';
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
			$type = $info['type'];
			if (isset($type) && $type != '') {
				$add = array(
					'title' => stripslashes($info['title']),
					'type' => $type,
					'link' => stripslashes($info[$type]),
					'cat_posts' => ($type == 'category' && isset($info['category_posts']) && $info['category_posts'] != '' ? true : false),
				);
				array_push($new_data, $add);
			}
		}
	}
	$settings = array('nicename' => stripslashes($cflk_nicename), 'description' => stripslashes($cflk_description), 'data' => $new_data);
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
	delete_option($cflk_key);
}

function cflk_insert_new($nicename = '', $description = '', $data = array()) {
	if ($nicename == '') { return false; }
	
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
	add_option($check_name[0], $settings);
	return $check_name[0];
}

function cflk_name_check($name) {
	global $wpdb;
	$i=1;
	$option_name = 'cfl-'.sanitize_title($name);
	$title = $name;
	$original_option = $option_name;
	$original_title = $title;
	while(count($wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '".$option_name."'")) > 0) {
		$option_name = $original_option.$i;
		$title = $original_title.$i;
		$i++;
	}
	return array($option_name,$title);
}

function cflk_edit_nicename($cflk_key = '', $cflk_nicename = '') {
	if ($cflk_key != '' && $cflk_nicename != '') {
		$cflk = maybe_unserialize(get_option($cflk_key));
		if ($cflk[nicename] != $cflk_nicename) {
			$cflk[nicename] = stripslashes($cflk_nicename);
		}
		update_option($cflk_key, $cflk);
	}
}

function cflk_get_list_links() {
	global $wpdb;

	$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	$return = array();

	if (is_array($cflk_list)) {
		foreach ($cflk_list as $cflk) {
			$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
			$return[$cflk->option_name] = array(
				'nicename' => $options['nicename'], 
				'description' => $options['description'],
				'count' => count($options['data'])
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

	$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
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
		<input id="cfl-links-title-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][title]" class="widefat" type="text" value="<?php print (htmlspecialchars($title)); ?>" />
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

	$widget_ops = array('classname' => 'cf_links_widget', 'description' => __('Widget for showing links entered in the Advanced Links settings page.','cf-links'));
	$name = __('CF Links', 'cf-links');

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
// Newest Shortcode
add_shortcode('cflk_links','cflk_handle_shortcode');
// Kept in for legacy purposes
add_shortcode('cfl_links', 'cflk_handle_shortcode');

function cflk_get_links($key = null, $args = array()) {
	if (!$key) { return ''; }
	$defaults = array(
		'before' => '<ul class="cflk-list '.$key.'">',
		'after' => '</ul>',
		'location' => 'template'
	);
	$args = array_merge($defaults, $args);
	extract($args, EXTR_SKIP);
	$server_current = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
	
	$links = get_option($key);
	if (empty($links)) {
		echo 'Could not find link list: '.htmlspecialchars($key);
		return;
	}
	$links = maybe_unserialize($links);	
	$list = apply_filters('cflk_get_links_data', $links);
	$list = cflk_get_link_info($list, $key);

	if (!is_array($list)) { return ''; }

	$return = '';
	$i = 0;
	foreach ($list as $key => $data) {
		$li_class = '';

		if (is_array($data)) {
			if ($links['data'][$data['id']]['type'] == 'category') {
				$category = the_category_id(false);
				$link_cat = $links['data'][$data['id']]['link'];
				if ($link_cat == $category) {
					$li_class .= 'cflk-current-category ';
				}
			}
			if ($i == 0) {
				$li_class .= 'cflk-first ';
			}
			if ($i == (count($list) - 1)) {
				$li_class .= 'cflk-last ';
			}
			if ($server_current == str_replace(array('http://','http://www.'),'',$data['href'])) {
				$li_class .= 'cflk-current ';
			}
			$return .= '<li class="'.$data['class'].' '.$li_class.'"><a href="'.$data['href'].'">'.$data['text'].'</a></li>';
			$i++;
		}
	}
	$return = $before.$return.$after;
	$return = apply_filters('cflk_get_links', $return, $links, $args);
	return $return;
}

function cflk_links($key, $args = array()) {
	echo cflk_get_links($key, $args);
}

function cflk_get_link_info($link_list, $list_key) {
	$data = array();
	if(is_array($link_list)) {
		foreach ($link_list['data'] as $key => $link) {
			$href = '';
			$text = '';
			$type_text = '';
			$other = '';
			$sanitized_href = '';
	
			switch ($link['type']) {
				case 'url':
					$href = htmlspecialchars($link['link']);
					$type_text = htmlspecialchars($link['link']);
					break;
				case 'rss':
					$href = htmlspecialchars($link['link']);
					$type_text = htmlspecialchars($link['link']);
					break;
				case 'post':
				case 'page':
					$postinfo = get_post(htmlspecialchars($link['link']));
					if (is_a($postinfo, 'stdClass')) {
						$type_text = $postinfo->post_title;
						$href = get_permalink(htmlspecialchars($link['link']));
					}
					break;
				case 'category':
					$cat_info = get_category(htmlspecialchars($link['link']),OBJECT,'display');
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
				case 'author_rss':
					$userdata = get_userdata($link['link']);
					if (is_a($userdata, 'stdClass')) {
						$type_text = $userdata->display_name;
						$other = 'rss';
						$href = get_author_rss_link(false,$link['link']);
					}
					break;
				case 'blog':
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
				$text = htmlspecialchars($link['title']);
			}
			$class = $list_key.'_'.md5($href);
			if ($href != '') {
				array_push($data, array('id' => $key, 'href' => $href, 'text' => $text, 'class' => $class));
			}
		}
		return $data;
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
	$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '$key'");
	foreach ($cflk_list as $key => $value) {
		print ('<textarea rows="20" style="width:600px;">');
		print_r ($value->option_value);
		print ('</textarea>');
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

?>