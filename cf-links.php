<?php
/*
Plugin Name: Advanced Links
Plugin URI: 
Description: Advanced options for adding links
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1');
// ini_set('error_reporting', E_ALL);

load_plugin_textdomain('cf-links');

function links_menu_items() {
	if (current_user_can('manage_options')) {
		add_options_page(
			__('Advanced Links', 'cf-links')
			, __('Advanced Links', 'cf-links')
			, 10
			, basename(__FILE__)
			, 'links_check_page'
		);
	}
}
add_action('admin_menu', 'links_menu_items');

function links_check_page() {
	if(isset($_GET['links_page'])) {
		$check_page = $_GET['links_page'];
	}
	else {
		$check_page = '';
	}
	switch($check_page) {
		case 'main':
			links_options_form();
			break;
		case 'edit':
			links_edit();
			break;
		case 'create':
			links_new();
			break;
		default:
			links_options_form();
			break;
	}
}

function links_request_handler() {
	if(current_user_can('manage_options')) {
		if(isset($_POST['links_action']) && $_POST['links_action'] != '') {
			switch($_POST['links_action']) {
				case 'links_update_settings':
					if(isset($_POST['links'])) {
						$link_data = stripslashes_deep($_POST['links']);
						if(isset($_POST['links_key']) && $_POST['links_key'] != '' && isset($_POST['links_nicename']) && $_POST['links_nicename'] != '') {
							links_process($link_data, $_POST['links_key'], $_POST['links_nicename']);
						}
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&links_page=edit&link='.$_POST['links_key'].'&links_message=updated');
					}
					break;
				case 'links_delete':
					if(isset($_POST['links_key']) && $_POST['links_key'] != '') {
						links_delete($_POST['links_key']);
					}
					break;
				case 'links_delete_key':
					if(isset($_POST['links_key']) && isset($_POST['key']) && $_POST['links_key'] != '' && $_POST['key'] != '') {
						links_delete_key($_POST['links_key'], $_POST['key']);
					}
					break;
				case 'links_insert_new':
					if(isset($_POST['links_nicename']) && $_POST['links_nicename'] != '') {
						$links_key = links_insert_new($_POST['links_nicename']);
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&links_page=edit&link='.$links_key);
					}
					break;
				case 'links_edit_nicename':
					if(isset($_POST['links_nicename']) && $_POST['links_nicename'] != '' && isset($_POST['links_key']) && $_POST['links_key'] != '') {
						links_edit_nicename($_POST['links_key'], $_POST['links_nicename']);
					}
					break;
				default:
					break;
			}
		}
	}
	if(!empty($_GET['links_page'])) {
		switch($_GET['links_page']) {
			case 'links_admin_js':
				links_admin_js();
				break;
			case 'links_admin_css':
				links_admin_css();
				break;
			case 'dialog':
				links_dialog();
				break;
			default:
				break;
		}
	}
}
add_action('init', 'links_request_handler');
add_action('wp_ajax_links_update_settings', 'links_request_handler');

wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui', get_bloginfo('url').'/wp-content/plugins/cf-links/js/jquery-ui.js', 'jquery');
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

function links_ajax() {
	wp_print_scripts(array('sack'));
	?>
	<script type="text/javascript">
		//<![CDATA[
		function linksAJAXDeleteLink(links_key,key) {
			var links_sack = new sack("<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php");
			links_sack.execute = 1;
			links_sack.method = 'POST';
			links_sack.setVar('links_action', 'links_delete_key');
			links_sack.setVar('key', key);
			links_sack.setVar('links_key', links_key);
			links_sack.encVar('cookie', document.cookie, false);
			links_sack.onError = function() {alert('AJAX error in updating settings.  Please click update button below to save your settings.');};
			links_sack.runAJAX();
			return true;
		}
		function linksAJAXDeleteMain(links_key) {
			var links_main_sack = new sack("<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php");
			links_main_sack.execute = 1;
			links_main_sack.method = 'POST';
			links_main_sack.setVar('links_action', 'links_delete');
			links_main_sack.setVar('links_key', links_key);
			links_main_sack.encVar('cookie', document.cookie, false);
			links_main_sack.onError = function() {alert('AJAX error in updating settings.  Please click update button below to save your settings.');};
			links_main_sack.runAJAX();
			return true;
		}
		function linksAJAXSaveNicename(links_key, links_nicename) {
			var links_nicename_sack = new sack("<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php");
			links_nicename_sack.execute = 1
			links_nicename_sack.method = 'POST';
			links_nicename_sack.setVar('links_action', 'links_edit_nicename');
			links_nicename_sack.setVar('links_key', links_key);
			links_nicename_sack.setVar('links_nicename', links_nicename);
			links_nicename_sack.encVar('cookie', document.cookie, false);
			links_nicename_sack.onError = function() {alert('AJAX error in updating settings.  Please click update button below to save your settings.');};
			links_nicename_sack.runAJAX();
			return true;
		}
		//]]>
	</script>
	<?php
}
add_action('admin_print_scripts', 'links_ajax');

function links_process($link_data = array(), $link_key = '', $link_nicename = '') {
	$new_data = array();
	foreach($link_data as $key => $info) {
		if($info['type'] == '') {
			unset($link_data[$key]);
		} else {
			$check_ok = true;
			$title = stripslashes($info['title']);
			$type = $info['type'];
			$link = '';
			switch($type) {
				case 'url':
					if($info['link'] != '') { $link = stripslashes($info['link']); } else { $check_ok = false; }
					break;
				case 'rss':
					if($info['rss'] != '') { $link = stripslashes($info['rss']); } else { $check_ok = false; }
					break;
				case 'post':
					if($info['post'] != '') { $link = stripslashes($info['post']); } else { $check_ok = false; }
					break;
				case 'page':
					if($info['page'] != '') { $link = stripslashes($info['page']); } else { $check_ok = false; }
					break;
				case 'category':
					if($info['category'] != '') { $link = stripslashes($info['category']); } else { $check_ok = false; }
					break;
				case 'wordpress':
					if($info['wordpress'] != '') { $link = stripslashes($info['wordpress']); } else { $check_ok = false; }
					break;
				case 'author_rss':
					if($info['author_rss'] != '') { $link = stripslashes($info['author_rss']); } else { $check_ok = false; }
					break;
				default:
					if($info['link'] != '') { $link = stripslashes($info['link']); } else { $check_ok = false; }
					break;
			}
			if($check_ok) {
				$link = trim(strip_tags(stripslashes($link)));
				array_push($new_data,array('title' => $title, 'type' => $type, 'link' => $link));
			}
		}
	}
	if($link_key != '' && $link_nicename != '') {
		$settings = array('nicename' => stripslashes($link_nicename), 'data' => $new_data);
		update_option($link_key, serialize($settings));
	}
}

function links_delete_key($links_key, $remove_key) {
	$links = maybe_unserialize(get_option($links_key));
	foreach($links['data'] as $key => $value) {
		if($key == $remove_key) {
			unset($links['data'][$key]);
		}
	}
	update_option($links_key, serialize($links));
	return true;
}

function links_delete($links_key) {
	if($links_key != '') {
		delete_option($links_key);
	}
	$delete_keys = array();
	$widgets = maybe_unserialize(get_option('cf_links_widget'));
	$sidebars = maybe_unserialize(get_option('sidebars_widgets'));
	foreach($widgets as $key => $widget) {
		if($widget[select] == $links_key) {
			array_push($delete_keys, $key);
		}
	}
	if($delete_keys != '') {
		foreach($delete_keys as $key) {
			unset($widgets[$key]);
			foreach($sidebars as $sidebars_key => $sidebar) {
				if(is_array($sidebar)) {
					$check_key = 'cf-links-'.$key;
					foreach($sidebar as $sidebar_key => $item) {
						if($item == $check_key) {
							unset($sidebar[$sidebar_key]);
						}
					}
					$sidebars[$sidebars_key] = $sidebar;
				}
			}
		}
		update_option('sidebars_widgets', $sidebars);
	}
	
}

function links_insert_new($links_nicename) {
	$check_name = links_name_check(stripslashes($links_nicename));
	$settings = array('nicename' => $check_name[1], 'data' => array('1' => array('title' => 'Name Here', 'link' => 'http://example.com', 'type' => 'url')));
	add_option($check_name[0], $settings);
	return $check_name[0];
}

function links_name_check($name) {
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

function links_edit_nicename($links_key = '', $links_nicename = '') {
	if($links_key != '' && $links_nicename != '') {
		$links = maybe_unserialize(get_option($links_key));
		if($links[nicename] != $links_nicename) {
			$links[nicename] = stripslashes($links_nicename);
		}
		update_option($links_key, $links);
	}
}

function links_widget( $args, $widget_args = 1 ) {
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
	if($title == '') {
		echo $before_widget;
	}
	else {
		echo $before_widget . $before_title . $title . $after_title;
	}
	links_widget_data($select);
	echo $after_widget;
}

function links_widget_control( $widget_args = 1 ) {
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

	$links_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	$form_data = array();
	foreach($links_list as $links) {
		$options = maybe_unserialize(maybe_unserialize($links->option_value));
		$push = array('option_name' => $links->option_name, 'nicename' => $options['nicename']);
		array_push($form_data,$push);
	}
?>
		<p>
			<label for="cfl-links-title-<?php echo $number; ?>"><?php _e('Title: ', 'cf-links'); ?></label>
			<br />
			<input id="cfl-links-title-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][title]" class="widefat" type="text" value="<?php print(htmlspecialchars($title)); ?>" />
		</p>
		<p>
			<label for="cfl-links-select-<?php echo $number; ?>"><?php _e('Links List: ', 'cf-links'); ?></label>
			<br />
			<select id="cfl-links-select-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][select]" class="widefat">
				<?php
				foreach($form_data as $data => $info) {
					if($info['option_name'] == $select) {
						$selected = 'selected=selected';
					} 
					else {
						$selected = '';
					}
					?>
					<option value="<?php print(htmlspecialchars($info['option_name'])); ?>" <?php print($selected); ?>><?php print(htmlspecialchars($info['nicename'])); ?></option>
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

function links_widget_register() {
	if ( !$options = get_option('cf_links_widget') )
		$options = array();

	$widget_ops = array('classname' => 'cf_links_widget', 'description' => __('Widget for showing links entered in the Advanced Links settings page.','cf-links'));
	$name = __('CF Links', 'cf-links');

	$id = false;
	foreach ( array_keys($options) as $o ) {
		if ( !isset($options[$o]['title']) )
			continue;
		$id = "cf-links-$o";
		wp_register_sidebar_widget( $id, $name, 'links_widget', $widget_ops, array( 'number' => $o ) );
		wp_register_widget_control( $id, $name, 'links_widget_control', array( 'id_base' => 'cf-links' ), array( 'number' => $o ) );
	}
	if ( !$id ) {
		wp_register_sidebar_widget( 'cf-links-1', $name, 'links_widget', $widget_ops, array( 'number' => -1 ) );
		wp_register_widget_control( 'cf-links-1', $name, 'links_widget_control', array( 'id_base' => 'cf-links' ), array( 'number' => -1 ) );
	}
}
add_action( 'widgets_init', 'links_widget_register' );

function links_dialog() {
	global $wpdb;
	?>
	<script type='text/javascript' src='<?php print(get_bloginfo('url')); ?>/wp-includes/js/quicktags.js'></script>
	<script type="text/javascript">
		function linksSetText(text) {
			text = '<p>[cfl_links name="' + text + '"]</p>';
	
			parent.window.tinyMCE.execCommand("mceBeginUndoLevel");
			parent.window.tinyMCE.execCommand('mceInsertContent', false, '<p>'+text+'</p>');
			parent.window.tinyMCE.execCommand("mceEndUndoLevel");
		}
	</script>
	<?
	$links_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	foreach($links_list as $links) {
		$options = maybe_unserialize(maybe_unserialize($links->option_value));
		?>
		<li>
			<a href="#" onclick="linksSetText('<?php print(htmlspecialchars($links->option_name)); ?>')"><?php print(htmlspecialchars($options['nicename'])); ?></a>
		</li>
		<?php
	}
	die();
}

function links_register_button($buttons) {
	array_push($buttons, '|', "cfLinksBtn");
	return $buttons;
}

function links_add_tinymce_plugin($plugin_array) {
	$plugin_array['cflinks'] = get_bloginfo('wpurl') . '/wp-content/plugins/cf-links/editor_plugin.js';
	return $plugin_array;
}

function links_addtinymce() {
   if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
     return;
   if (get_user_option('rich_editing') == 'true') {
     add_filter("mce_external_plugins", "links_add_tinymce_plugin");
     add_filter('mce_buttons', 'links_register_button');
   }
}
add_action('init', 'links_addtinymce');
 
function get_links_template($link_key = '') {
	global $wpdb;
	if($link_key != '') {
		$links = maybe_unserialize(get_option($link_key));
		if(is_array($links)) {
			$links_get = '';
			$links_get .= '<div class="'.$link_key.'_get"><ul class="links_main_get">';
			foreach($links['data'] as $key => $link) {
				$links_get .= links_get_by_type($link,$link_key,$key);
			}
			$links_get .= '</ul></div>';
			return $links_get;
		}
	}
}

function links_template($link_key = '', $before = 'DEFAULT', $after = 'DEFAULT') {
	if($link_key != '') {
		$links = maybe_unserialize(get_option($link_key));
		if(is_array($links)) {
			if($before == 'DEFAULT') {
				print('<div class="'.$link_key.'">');
			}
			else {
				print($before);
			}
			print('<ul class="links_main">');
			foreach($links['data'] as $key => $link) {
				print(links_get_by_type($link,$link_key,$key));
			}
			print('</ul>');
			if($after == 'DEFAULT') {
				print('</div>');
			}
			else {
				print($after);
			}
		}
	}
}

function links_widget_data($link_key = '') {
	if($link_key != '') {
		$links = maybe_unserialize(get_option($link_key));
		if(is_array($links)) {
			print('<ul class="links_main_widget">');
			foreach($links['data'] as $key => $link) {
				print(links_get_by_type($link,$link_key,$key));
			}
			print('</ul>');
		}
	}
}

function links_get_by_type($link = array(),$link_key,$key) {
	global $wpdb;
	switch($link['type']) {
		case 'url':
			$links_get .= '<li class="links_'.$link_key.'_'.$key.'_get">';
			$links_get .= '<a href="'.htmlspecialchars($link['link']).'">';
			if(htmlspecialchars($link['title']) == '') {
				$links_get .= htmlspecialchars($link['link']);
			}
			else {
				$links_get .= htmlspecialchars($link['title']);
			}
			$links_get .= '</a>';
			$links_get .= '</li>';
			break;
		case 'rss':
			$links_get .= '<li class="links_'.$link_key.'_'.$key.'_get">';
			$links_get .= '<a href="'.htmlspecialchars($link['link']).'" style="text-decoration: none; vertical-align: middle;"><img src="'.get_bloginfo('url').'/wp-content/plugins/cf-links/images/feed-icon-16x16.png" style="border: 0;" border="0" /></a><a href="'.htmlspecialchars($link['link']).'" style="vertical-align: top;"> ';
			if(htmlspecialchars($link['title']) == '') {
				$links_get .= htmlspecialchars($link['link']);
			}
			else {
				$links_get .= htmlspecialchars($link['title']);
			}
			$links_get .= '</a>';
			$links_get .= '</li>';
			break;
		case 'post':
		case 'page':
			$links_get .= '<li class="links_'.$link_key.'_'.$key.'_get">';
			$links_get .= '<a href="'.get_permalink(htmlspecialchars($link['link'])).'">';
			if(htmlspecialchars($link['title']) == '') {
				$postinfo = get_post($link['link']);
				$links_get .= $postinfo->post_title;
			}
			else {
				$links_get .= htmlspecialchars($link['title']);
			}
			$links_get .= '</a>';
			$links_get .= '</li>';
			break;
		case 'category':
			$links_get .= '<li class="links_'.$link_key.'_'.$key.'_get">';
			$links_get .= '<a href="'.get_category_link(htmlspecialchars($link['link'])).'">';
			if(htmlspecialchars($link['title']) == '') {
				$links_get .= get_cat_name($link['link']);
			}
			else {
				$links_get .= htmlspecialchars($link['title']);
			}
			$links_get .= '</a>';
			$links_get .= '</li>';
			break;
		case 'wordpress':
			switch($link['link']) {
				case 'home':
					$get_link = links_get_home();
					break;
				case 'loginout':
					$get_link = links_get_loginout();
					break;
				case 'register':
					$get_link = links_get_register();
					break;
				case 'profile':
					$get_link = links_get_profile();
					break;
				case 'main_rss':
					$get_link = links_get_site_rss();
					break;
			}
			if($get_link['link'] != '' && $get_link['text'] != '') {
				$links_get .= '<li class="links_'.$link_key.'_'.$key.'_get">';
				if($link['link'] == 'main_rss') {
					$links_get .= '<a href="'.$get_link['link'].'"><img src="'.get_bloginfo('url').'/wp-content/plugins/cf-links/images/feed-icon-16x16.png" style="border: 0;" border="0" /></a>';
				}
				$links_get .= '<a href="'.$get_link['link'].'" style="vertical-align: top;"> ';
				if(htmlspecialchars($link['title']) == '') {
					$links_get .= $get_link['text'];
				}
				else {
					$links_get .= $link['title'];
				}
				$links_get .= '</a>';
				$links_get .= '</li>';
			}
			break;
		case 'author_rss':
			$links_get .= '<li class="links_'.$link_key.'_'.$key.'_get">';
			$links_get .= '<a href="'.get_author_rss_link(0,$link['link']).'" style="text-decoration: none; vertical-align: middle;"><img src="'.get_bloginfo('url').'/wp-content/plugins/cf-links/images/feed-icon-16x16.png" style="border: 0;" border="0" /></a><a href="'.get_author_rss_link(0,$link['link']).'" style="vertical-align: top;"> ';
			if(htmlspecialchars($link['title']) == '') {
				$links_get .= get_userdata($link['link'])->display_name;
			}
			else {
				$links_get .= htmlspecialchars($link['title']);
			}
			$links_get .= '</a>';
			$links_get .= '</li>';
			break;
		default:
			break;
	}
	return $links_get;
}

function links_get_home() {
	$link = get_bloginfo('url');
	$text = 'Home';
	return array('text' => $text,'link' => $link);
}

function links_get_site_rss() {
	$link = get_bloginfo('rss2_url');
	$text = get_bloginfo('name');
	return array('text' => $text,'link' => $link);
}

function links_get_loginout() {
	$link = '';
	$text = '';
	if(!is_user_logged_in()) {
		$text = 'Log in';
		$link = site_url('wp-login.php','login');
	}
	else {
		$text = 'Log Out';
		$link = site_url('wp-login.php?action=logout','login');
	}
	return array('text' => $text,'link' => $link);
}

function links_get_register() {
	$link = '';
	$text = '';
	if(!is_user_logged_in()) {
		if(get_option('users_can_register')) {
			$text = 'Register';
			$link = site_url('wp-login.php?action=register','login');
		}
		else {
			$text = '';
			$link = '';
		}
	}
	else {
		if(current_user_can('manage_options')) {
			$text = 'Site Admin';
			$link = admin_url();
		}
	}
	return array('text' => $text,'link' => $link);
}

function links_get_profile() {
	$link = '';
	$text = '';
	if(!is_user_logged_in()) {
		$text = '';
		$link = '';
	}
	else {
		$text = 'Profile';
		$link = admin_url('profile.php');
	}
	return array('text' => $text,'link' => $link);
}

function links_filter_content($content) {
	global $wpdb;
	$links_list = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	if(is_array($links_list)) {
		foreach($links_list as $links) {
			$check = strpos($content, '[cfl_links name="'.$links->option_name.'"]');
			if($check !== false) {
				$content = str_replace('[cfl_links name="'.$links->option_name.'"]', get_links_template($links->option_name), $content);
			}
		}
	}
	return $content;
}
add_filter('the_content', 'links_filter_content');

function links_admin_css() {
	header('Content-type: text/css');
	?>
	#links-list { list-style: none; padding: 0; margin: 0; }
	#links-list li { margin: 0; padding: 0; }
	#links-list .handle { cursor: move; }
	#links-log { padding: 5px; border: 1px solid #ccc; }
	.link-info { list-style: none; }
	.links_button {
		font-family: "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana, sans-serif;
		padding: 3px 5px;
		font-size: 12px;
		line-height: 1.5em;
		border-width: 1px;
		border-style: solid;
		-moz-border-radius: 3px;
		-khtml-border-radius: 3px;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		cursor: pointer;
		text-decoration: none;	
		border-color: #80b5d0;
		background-color: #E5E5E5;
		color: #224466;
	}
	.links_button:hover {
		color: #D54E21;
		border-color: #535353;
	}
	.links_edit_link {
		font-size: 12px;
	}
	.links-codebox {
		padding: 10px;
		background-color: #E4F2FD;
		border: 1px solid #C6D9E9;
	}
	<?php
	die();
}

function links_admin_js() {
	global $wpdb;
	$posts = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_title ASC");
	$pages = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='page' ORDER BY post_title ASC");
	$categories = $wpdb->get_results("SELECT $wpdb->terms.name, $wpdb->terms.term_id FROM $wpdb->term_taxonomy left join $wpdb->terms on $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id where $wpdb->term_taxonomy.taxonomy = 'category'");
	$authors = get_users_of_blog($wpdb->blog_id);
	$post_select = '';
	$page_select = '';
	$category_select = '';
	$wordpress_select = '';
	$author_rss_select = '';

	foreach($posts as $post) {
		$post_select .= '<option value="'.$post->ID.'">'.addslashes($post->post_title).'</option>';
	}
	foreach($pages as $page) {
		$page_select .= '<option value="'.$page->ID.'">'.addslashes($page->post_title).'</option>';
	}
	foreach($categories as $category) {
		$category_select .= '<option value="'.$category->term_id.'">'.addslashes($category->name).'</option>';
	}
	$wordpress_select .= '<option value="home">'.__('Home','cf-links').'</option>';
	$wordpress_select .= '<option value="loginout">'.__('Log In/Out','cf-links').'</option>';
	$wordpress_select .= '<option value="register">'.__('Register/Site Admin','cf-links').'</option>';
	$wordpress_select .= '<option value="profile">'.__('Profile','cf-links').'</option>';
	$wordpress_select .= '<option value="main_rss">'.__('Site RSS','cf-links').'</option>';
	foreach($authors as $author) {
		$author_rss_select .= '<option value="'.$author->user_id.'">'.addslashes($author->display_name).'</option>';
	}
	
	header('Content-type: text/javascript');
?>
	var linksMainURL = '<?php bloginfo('url'); ?>';
	// When the document is ready set up our sortable with its inherant function(s)
	jQuery(document).ready(function() {
		jQuery("#links-list").sortable({
			handle : ".handle",
			update : function () {
				jQuery("input#links-log").val(jQuery("#links-list").sortable("serialize"));
			}
		});
		jQuery('input[name="link_edit"]').click(function() {
			location.href = "<?php echo get_bloginfo('wpurl'); ?>/wp-admin/options-general.php?page=cf-links.php&links_page=edit&link=" + jQuery(this).attr('rel');
			return false;
		});
	});
	function deleteLink(links_key,linkID) {
		if(confirm('Are you sure you want to delete this?')) {
			if(linksAJAXDeleteLink(links_key,linkID)) {
				jQuery('#listitem_'+linkID).remove();
				jQuery("#message_delete").attr("style","");
			}
			return false;
		}
	}
	function deleteCreated(linkID) {
		if(confirm('Are you sure you want to delete this?')) {
			jQuery('#listitem_'+linkID).remove();
			return false;
		}
	}
	function deleteMain(links_key) {
		if(confirm('Are you sure you want to delete this?')) {
			if(linksAJAXDeleteMain(links_key)) {
				jQuery('#link_main_'+links_key).remove();
				jQuery("#message_delete").attr("style","");
			}
			return false;
		}
	}
	function editNicename() {
		jQuery('#links_nicename_h3').attr('style','display: none;');
		jQuery('#links_nicename_input').attr('style','');
	}
	function cancelNicename() {
		jQuery('#links_nicename_input').attr('style','display: none;');
		jQuery('#links_nicename_h3').attr('style','');
	}
	function saveNicename(links_key) {
		if(linksAJAXSaveNicename(links_key, jQuery("#links_nicename_new").val())) {
			jQuery("#message").attr("style","");
			jQuery("#links_nicename_text").text(jQuery("#links_nicename_new").val());
			jQuery("#links_nicename").text(jQuery("#links_nicename_new").val());
			jQuery("#links_nicename_h2").text(jQuery("#links_nicename_new").val());
			cancelNicename();
		}
	}
	function editTitle(key) {
		jQuery('#links_'+key+'_title_edit').attr('style','display: none;');
		jQuery('#links_'+key+'_title_input').attr('style','');
	}
	function cancelTitle(key) {
		jQuery('#links_'+key+'_title_input').attr('style','display: none;');
		jQuery('#links_'+key+'_title_edit').attr('style','');
	}
	function showLinkType(key) {
		var type = jQuery("#links_"+key+"_type").val();
		if(type == "url") {
			jQuery("#url_"+key).attr("style", "");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#post_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "rss") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "");
			jQuery("#post_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "post") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#post_"+key).attr("style", "");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "page") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#post_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "category") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#post_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "wordpress") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#post_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "author_rss") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#post_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "");
		}
	}
	function showLinkCode(key) {
		jQuery('#'+key).slideToggle();
	}
	function addLink() {
		var id = new Date().valueOf();
		var section = id.toString();
		var html = '<li id="listitem_###SECTION###">\
						<table class="widefat">\
							<tr>\
								<td width="40px" style="text-align: center;">\
									<img src="'+linksMainURL+'/wp-content/plugins/cf-links/images/arrow_up_down.png" class="handle" alt="move" />\
								</td>\
								<td width="90px">\
									<select name="links[###SECTION###][type]" id="links_###SECTION###_type" onChange="showLinkType(###SECTION###)">\
										<option value="url">URL</option>\
										<option value="rss">RSS Feed</option>\
										<option value="post">Post</option>\
										<option value="page">Page</option>\
										<option value="category">Category</option>\
										<option value="wordpress">WordPress</option>\
										<option value="author_rss">Author RSS</option>\
									</select>\
								</td>\
								<td>\
									<span id="url_###SECTION###">\
										<input type="text" name="links[###SECTION###][link]" id="links_###SECTION###_link" size="50" value="" />\
										<br />\
										<?php _e('ex: http://example.com', 'cf-links'); ?>\
									</span>\
									<span id="rss_###SECTION###" style="display: none;">\
										<input type="text" name="links[###SECTION###][rss]" id="links_###SECTION###_rss" size="50" value="" />\
										<br />\
										<?php _e('ex: http://example.com/feed', 'cf-links'); ?>\
									</span>\
									<span id="post_###SECTION###" style="display: none;">\
										<select name="links[###SECTION###][post]" id="links_###SECTION###_post" style="width: 410px;">\
											<?php echo $post_select; ?>\
										</select>\
									</span>\
									<span id="page_###SECTION###" style="display: none;">\
										<select name="links[###SECTION###][page]" id="links_###SECTION###_page" style="width: 410px;">\
											<?php echo $page_select; ?>\
										</select>\
									</span>\
									<span id="category_###SECTION###" style="display: none;">\
										<select name="links[###SECTION###][category]" id="links_###SECTION###_category" style="width: 410px;">\
											<?php echo $category_select; ?>\
										</select>\
									</span>\
									<span id="wordpress_###SECTION###" style="display: none;">\
										<select name="links[###SECTION###][wordpress]" id="links_###SECTION###_wordpress" style="width: 410px;">\
											<?php echo $wordpress_select; ?>\
										</select>\
									</span>\
									<span id="author_rss_###SECTION###" style="display: none;">\
										<select name="links[###SECTION###][author_rss]" id="links_###SECTION###_author_rss" style="width: 410px;">\
											<?php echo $author_rss_select; ?>\
										</select>\
									</span>\
								</td>\
								<td width="250px">\
									<span id="links_###SECTION###_title_edit">\
										<input type="button" class="links_button" id="link_edit_title_###SECTION###" value="<?php _e('Edit Title', 'cf-links') ?>" onClick="editTitle(###SECTION###)" />\
									</span>\
									<span id="links_###SECTION###_title_input" style="display: none">\
										<input type="text" name="links[###SECTION###][title]" value="" />\
										<input type="button" class="links_button" id="link_cancel_title_###SECTION###" value="<?php _e('Cancel', 'cf-links') ?>" onClick="cancelTitle(###SECTION###)" />\
									</span>\
								<td width="60px" style="text-align: center;">\
									<input type="button" class="links_button" id="link_delete_###SECTION###" value="<?php _e('Delete', 'cf-links'); ?>" onClick="deleteCreated(###SECTION###)" />\
								</td>\
							</tr>\
						</table>\
					</li>';		
		html = html.replace(/###SECTION###/g, section);
		jQuery('#links-list').append(html);
	}
<?php
	die();
}

function links_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'index.php?links_page=links_admin_css" />';
	echo '<script type="text/javascript" src="'.trailingslashit(get_bloginfo('url')).'index.php?links_page=links_admin_js"></script>';
}
if(isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	add_action('admin_head', 'links_admin_head');
}

function links_options_form() {
	global $wpdb;

	$links_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	$form_data = array();
	foreach($links_list as $links) {
		$options = maybe_unserialize(maybe_unserialize($links->option_value));
		$push = array('option_name' => $links->option_name, 'nicename' => $options['nicename'], 'count' => count($options['data']));
		array_push($form_data,$push);
	}
	if ( isset($_GET['links_message']) ) {
		switch($_GET['links_message']) {
			case 'create':
				print('
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery("#message_create").attr("style","");
						});
					</script>
				');
				break;
			case 'delete':
				print('
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery("#message_delete").attr("style","");
						});
					</script>
				');
				break;
			default:
				break;
		}
	}
	print('
		<div id="message_create" class="updated fade" style="display: none;">
			<p>'.__('Links List Created.', 'cf-links').'</p>
		</div>
		<div id="message_delete" class="updated fade" style="display: none;">
			<p>'.__('Links List Deleted.', 'cf-links').'</p>
		</div>
		<div class="wrap">
			'.links_head('main').'
			<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="links-form">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Links List', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="80px">'.__('Links Count', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Edit', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Delete', 'cf-links').'</th>
						</tr>
					</thead>
				</table>
				<ul id="links-list">');
					if(count($form_data) > 0) {
						foreach($form_data as $data => $info) {
							print('<li id="link_main_'.$info['option_name'].'">
								<table class="widefat">
									<tr>
										<td style="vertical-align: middle;">
											<a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=cf-links.php&links_page=edit&link='.$info['option_name'].'" style="font-weight: bold; font-size: 20px;">'.$info['nicename'].'</a>
											<br />
											'.__('Show: ','cf-links').'<a href="#" onClick="showLinkCode(\''.$info['option_name'].'-TemplateTag\')">'.__('Template Tag','cf-links').'</a> | <a href="#" onClick="showLinkCode(\''.$info['option_name'].'-ShortCode\')">'.__('Shortcode','cf-links').'</a>
											<div id="'.$info['option_name'].'-TemplateTag" class="links-codebox" style="display:none;">
												<div style="float: left;"><code>'.htmlentities('<?php if(function_exists("links_template")) { links_template("'.$info['option_name'].'"); } ?>').'</code></div><div style="float: right;"><a href="#" onClick="showLinkCode(\''.$info['option_name'].'-TemplateTag\')">'.__('Hide','cf-links').'</a></div><div class="clear"></div>
											</div>
											<div id="'.$info['option_name'].'-ShortCode" class="links-codebox" style="display:none;">
												<div style="float: left;"><code>'.htmlentities('[cfl_links name="'.$info['option_name'].'"]').'</code></div><div style="float: right;"><a href="#" onClick="showLinkCode(\''.$info['option_name'].'-ShortCode\')">'.__('Hide','cf-links').'</a></div><div class="clear"></div>
											</div>
										</td>
										<td style="text-align: center; vertical-align: middle;" width="80px">
											'.$info['count'].'
										</td>
										<td style="text-align: center; vertical-align: middle;" width="60px">
											<p class="submit" style="border-top: none; padding: 0; margin: 0;">
												<input type="button" name="link_edit" value="'.__('Edit', 'cf-links').'" class="button-secondary edit" rel="'.$info['option_name'].'" />
											</p>
										</td>
										<td style="text-align: center; vertical-align: middle;" width="60px">
											<p class="submit" style="border-top: none; padding: 0; margin: 0;">
												<input type="button" id="link_delete_'.$info['option_name'].'" onclick="deleteMain(\''.$info['option_name'].'\')" value="'.__('Delete', 'cf-links').'" />
											</p>
										</td>
									</tr>
								</table>
							</li>');
						}
					}
				print('
				</ul>
			</form>
		</div>
	');
}

function links_new() {
	print('
		<div class="wrap">
			'.links_head('create').'
			<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="links-create">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Link List Name', 'cf-links').'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><input type="text" name="links_nicename" size="55" />
						</tr>
					</tbody>
				</table>
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="links_action" value="links_insert_new" />
					<input type="submit" name="submit" id="links-submit" value="'.__('Create New Link List', 'cf-links').'" />
				</p>
			</form>
		</div>
	');
}

function links_edit() {
	global $wpdb;
	
	if(isset($_GET['link']) && $_GET['link'] != '') {
		$links_key = $_GET['link'];
		$links = maybe_unserialize(get_option($links_key));
		is_array($links) ? $links_count = count($links) : $links_count = 0;
		
		$posts = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_title ASC");
		$pages = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='page' ORDER BY post_title ASC");
		$cat_params = array('hide_empty' => false);
		$categories = get_categories($cat_params);
		$wordpress = array(
					'home' => array(
								  'type' => 'home',
								  'text' => 'Home')
					,'loginout' => array(
								  'type' => 'loginout', 
							  	  'text' => 'Log In/Out')
					,'register' => array(
						   		  'type' => 'register', 
								  'text' => 'Register/Site Admin')
					,'profile' => array(
						   		  'type' => 'profile', 
								  'text' => 'Profile')
					,'main_rss' => array(
						   		  'type' => 'main_rss', 
								  'text' => 'Site RSS')
					);
		$authors = get_users_of_blog($wpdb->blog_id);
	
		if ( isset($_GET['links_message']) && $_GET['links_message'] = 'updated' ) {
			print('
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#message").attr("style","");
					});
				</script>
			');
		}
		print('
			<div id="message" class="updated fade" style="display: none;">
				<p>'.__('Settings updated.', 'cf-links').'</p>
			</div>
			<div id="message_delete" class="updated fade" style="display: none;">
				<p>'.__('Link deleted.', 'cf-links').'</p>
			</div>			
			<div class="wrap">
				<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="links-form">
					'.links_head('edit', htmlspecialchars($links['nicename']), $links_key).'
					<table class="widefat">
						<thead>
							<tr>
								<th scope="col" width="40px" style="text-align: center;">'.__('Order', 'cf-links').'</th>
								<th scope="col" width="90px">'.__('Type', 'cf-links').'</th>
								<th scope="col">'.__('Link', 'cf-links').'</th>
								<th scope="col" width="250px">'.__('Link Title (Optional)', 'cf-links').'</th>
								<th scope="col" style="text-align: center;" width="60px">'.__('Delete', 'cf-links').'</th>
							</tr>
						</thead>
					</table>
					<ul id="links-list">');
						if($links_count > 0) {
							foreach($links['data'] as $key => $setting) {
								$select_settings = links_edit_select($setting['type']);
								print('<li id="listitem_'.$key.'">
									<table class="widefat">
										<tr>
											<td width="40px" style="text-align: center;"><img src="'.get_bloginfo('url').'/wp-content/plugins/cf-links/images/arrow_up_down.png" class="handle" alt="move" /></td>
											<td width="90px">
												<select name="links['.$key.'][type]" id="links_'.$key.'_type" onChange="showLinkType('.$key.')">
													<option value="url" '.$select_settings[url_select].'>'.__('URL','cf-links').'</option>
													<option value="rss" '.$select_settings[rss_select].'>'.__('RSS Feed','cf-links').'</option>
													<option value="post" '.$select_settings[post_select].'>'.__('Post','cf-links').'</option>
													<option value="page" '.$select_settings[page_select].'>'.__('Page','cf-links').'</option>
													<option value="category" '.$select_settings[category_select].'>'.__('Category','cf-links').'</option>
													<option value="wordpress" '.$select_settings[wordpress_select].'>'.__('WordPress','cf-links').'</option>
													<option value="author_rss" '.$select_settings[author_rss_select].'>'.__('Author RSS','cf-links').'</option>
												</select>
											</td>
											<td>
												<span id="url_'.$key.'" '.$select_settings[url_show].'>
													<input type="text" name="links['.$key.'][link]" id="links_'.$key.'_link" size="50" value="'.htmlspecialchars($setting['link']).'" />
													<br />
													'.__('ex: http://example.com', 'cf-links').'
												</span>
												<span id="rss_'.$key.'" '.$select_settings[rss_show].'>
													<input type="text" name="links['.$key.'][rss]" id="links'.$key.'_rss" size="50" value="'.htmlspecialchars($setting['link']).'" />
													<br />
													'.__('ex: http://example.com/feed', 'cf-links').'
													
												</span>
												<span id="post_'.$key.'" '.$select_settings[post_show].'>
													<select name="links['.$key.'][post]" id="links_'.$key.'_post" style="width: 410px;">');
													foreach($posts as $post) {				
														if($setting['link'] == $post->ID) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$post->ID.'" '.$selected.'>'.$post->post_title.'</option>');
													}
													print('
													</select>
												</span>
												<span id="page_'.$key.'" '.$select_settings[page_show].'>
													<select name="links['.$key.'][page]" id="links_'.$key.'_page" style="width: 410px;">');
													foreach($pages as $page) {		
														if($setting['link'] == $page->ID) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$page->ID.'" '.$selected.'>'.$page->post_title.'</option>');
													}
													print('
													</select>
												</span>
												<span id="category_'.$key.'" '.$select_settings[category_show].'>
													<select name="links['.$key.'][category]" id="links_'.$key.'_category" style="width: 410px;">');
													foreach($categories as $category) {
														if($setting['link'] == $category->term_id) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$category->term_id.'" '.$selected.'>'.$category->name.'</option>');
													}
													print('
													</select>
												</span>
												<span id="wordpress_'.$key.'" '.$select_settings[wordpress_show].'>
													<select name="links['.$key.'][wordpress]" id="links_'.$key.'_wordpress" style="width: 410px;">');
													foreach($wordpress as $wp) {
														if($setting['link'] == $wp[type]) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$wp[type].'" '.$selected.'>'.$wp[text].'</option>');
													}
													print('
													</select>
												</span>
												<span id="author_rss_'.$key.'" '.$select_settings[author_rss_show].'>
													<select name="links['.$key.'][author_rss]" id="links_'.$key.'_author_rss" style="width: 410px;">');
													foreach($authors as $author) {
														if($setting['link'] == $author->user_id) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$author->user_id.'" '.$selected.'>'.$author->display_name.'</option>');
													}
													print('
													</select>
												</span>
											</td>
											<td width="250px">');
												if(htmlspecialchars($setting['title']) == '') {
													print('
													<span id="links_'.$key.'_title_edit">
														<input type="button" class="links_button" id="link_edit_title_'.$key.'" value="'.__('Edit Title', 'cf-links').'" onClick="editTitle(\''.$key.'\')" />
													</span>
													<span id="links_'.$key.'_title_input" style="display: none">
														<input type="text" name="links['.$key.'][title]" value="'.htmlspecialchars($setting['title']).'" />
														<input type="button" class="links_button" id="link_cancel_title_'.$key.'" value="'.__('Cancel', 'cf-links').'" onClick="cancelTitle(\''.$key.'\')" />
													</span>
													');
												}
												else {
													print('
													<input type="text" size="28" name="links['.$key.'][title]" value="'.htmlspecialchars($setting['title']).'" />
													');
												}
											print('
											</td>
											<td width="60px" style="text-align: center;">
												<input type="button" class="links_button" id="link_delete_'.$key.'" value="'.__('Delete', 'cf-links').'" onClick="deleteLink(\''.$links_key.'\',\''.$key.'\')" />
											</td>
										</tr>
									</table>
								</li>');
							}
						}
						print('
					</ul>
					<table class="widefat">
						<tr>
							<td>
								<p class="submit" style="border-top: none; padding:0; margin:0;">
									<input type="button" name="link_add" id="link_add" value="'.__('Add New Link', 'cf-links').'" onClick="addLink()" />
								</p>
							</td>
						</tr>
					</table>
					<p class="submit" style="border-top: none;">
						<input type="hidden" name="links_action" value="links_update_settings" />
						<input type="hidden" name="links_key" value="'.attribute_escape($links_key).'" />
						<input type="submit" name="submit" id="links-submit" value="'.__('Update Settings', 'cf-links').'" />
					</p>
				</form>
			</div>
		');
	} else {
		print('
			<div id="message" class="updated fade">
				<p>'.__('You were directed to this page in error.  Please <a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=cf-links.php">go here</a> to edit options for this plugin.', 'cf-links').'</p>
			</div>
		');
	}
}

function links_head($page = '', $list = '') {
	$links_head = '';
	$links_head .= '<h2>'.__('Links Options', 'cf-links');
	if($list != '') {
		$links_head .= ' '.__('for: ','cf-links').'<span id="links_nicename_h3">'.$list.' <a href="#" class="links_edit_link" onClick="editNicename()">Edit</a></span>';
		$links_head .= '<span id="links_nicename_input" style="display: none;">
							<input type="text" name="links_nicename" id="links_nicename" value="'.attribute_escape($list).'" />
							<input type="submit" name="submit" id="links-submit" class="links_button" value="'.__('Save', 'cf-links').'" />
							<input type="button" name="link_nicename_cancel" id="link_nicename_cancel" class="links_button" value="'.__('Cancel', 'cf-links').'" onClick="cancelNicename()" />					
						</span>';
		$links_head .= '</h2>';
		
	}
	else {
		$links_head .= '</h2>';
	}
	
	$main_text = '';
	$add_text = '';
	
	switch ($page) {
		case 'main':
			$main_text = 'class="current"';
			break;
		case 'create':
			$add_text = ' class="current"';
			break;
		default:
			break;
	}
	$links_head .= '
		<ul class="subsubsub">
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&links_page=main" '.$main_text.'>'.__('Links Lists', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&links_page=create" '.$add_text.'>'.__('Add Links List', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/widgets.php">'.__('Edit Widgets','cf-links').'</a>
			</li>
		</ul>
	';
	return($links_head);
}

function links_edit_select($type) {
	$select = array();
	switch($type) {
		case 'url':
			$select[url_show] = 'style=""';
			$select[url_select] = 'selected=selected';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			$select[author_rss_show] = 'style="display: none;"';
			$select[author_rss_select] = '';
			break;
		case 'rss':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style=""';
			$select[rss_select] = 'selected=selected';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			$select[author_rss_show] = 'style="display: none;"';
			$select[author_rss_select] = '';
			break;
		case 'post':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style=""';
			$select[post_select] = 'selected=selected';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			$select[author_rss_show] = 'style="display: none;"';
			$select[author_rss_select] = '';
			break;
		case 'page':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style=""';
			$select[page_select] = 'selected=selected';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			$select[author_rss_show] = 'style="display: none;"';
			$select[author_rss_select] = '';
			break;
		case 'category':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style=""';
			$select[category_select] = 'selected=selected';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			$select[author_rss_show] = 'style="display: none;"';
			$select[author_rss_select] = '';
			break;	
		case 'wordpress':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style=""';
			$select[wordpress_select] = 'selected=selected';
			$select[author_rss_show] = 'style="display: none;"';
			$select[author_rss_select] = '';
			break;	
		case 'author_rss':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			$select[author_rss_show] = 'style=""';
			$select[author_rss_select] = 'selected=selected';
			break;	
		default:
			$select[url_show] = 'style=""';
			$select[url_select] = 'selected=selected';
			$select[rss_show] = 'style="display: none;"';
			$select[rss_select] = '';
			$select[post_show] = 'style="display: none;"';
			$select[post_select] = '';
			$select[page_show] = 'style="display: none;"';
			$select[page_select] = '';
			$select[category_show] = 'style="display: none;"';
			$select[category_select] = '';
			$select[wordpress_show] = 'style="display: none;"';
			$select[wordpress_select] = '';
			break;
	}
	return $select;
}
?>