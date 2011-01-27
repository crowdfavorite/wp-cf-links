<?php
/*
Plugin Name: CF Links v2 Dev
Plugin URI: http://crowdfavorite.com
Description: A dynamic list builder.
Version: 2.0b
Author: crowdfavorite
Author URI: http://crowdfavorite.com/ 
*/

load_plugin_textdomain('cf-links');

## Constants

	define('CFLK_PLUGIN_VERS',2.0);
	define('CFLK_BASENAME',basename(__FILE__,'.php'));
	define('CFLK_PLUGIN_DIR',trailingslashit(WP_PLUGIN_DIR).CFLK_BASENAME);
	define('CFLK_PLUGIN_URL',trailingslashit(WP_PLUGIN_URL).CFLK_BASENAME);
	
## Includes

	include('lib/cf-json/cf-json.php');
	include('classes/message.class.php');
	include('classes/list.class.php');
	include('classes/cf-links.class.php');
	include('classes/link.class.php');
	include('classes/widget.class.php');
	if (is_admin()) {
		include('classes/error.class.php');
		include('classes/admin.class.php');
	}
	
	// Include the Reference class if we are in the admin and in a multisite environment
	if (defined('MULTISITE') && MULTISITE) {
		include('classes/reference.class.php');
	}
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-draggable');
	wp_enqueue_script('jquery-ui-droppable');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('postbox');
	
	
## Testing Includes
	
	// if (is_admin()) {
	// 	include('lib/tests.php');
	// }

## Retained functions & filters (if in name only)
	
	/**
	 * Template Tag
	 *
	 * $args is an array of parameters that can effect the list output
	 * - 'context': supply a helper context name for sensitive filtering. ie: header, footer, sidebar, etc...
	 * - 'before': @deprecated kept for legacy reasons. Use filter on `cflk_wrappers` instead.
	 * - 'after': @deprecated kept for legacy reasons. Use filter on `cflk_wrappers` instead.
	 * - 'child_before': @deprecated kept for legacy reasons. Use filter on `cflk_wrappers` instead.
	 * - 'child_after': @deprecated kept for legacy reasons. Use filter on `cflk_wrappers` instead.
	 *
	 * @param string $list_id 
	 * @param array $args 
	 * @return void
	 */
	function cflk_links($list_id, $args = array()) {
		echo apply_filters('cflk_links', cflk_get_links($list_id, $args));
	} 
		
	/**
	 * Template tag - returns
	 *
	 * @see cflk_links for full documentation
	 *
	 * @param string $list_id 
	 * @param array $args 
	 * @return string html
	 */
	function cflk_get_links($list_id, $args = array()) {
		global $cflk_links;

		$list = $cflk_links->get_list($list_id, $args);

		if ($list != false) {
			return apply_filters('cflk_get_links', $list->display());
		}
		else {
			return '';
		}
	} 
		
	// retain as accessor function? Not needed
	// function cflk_get_list_links($list) {} 
	
	// retain as accessor function? Not needed
	// function cflk_get_links_data($list_id) {} 
	
	function cflk_menu_items() {
		if (current_user_can('manage_options')) {
			add_options_page(
				__('CF Links', 'cf-links')
				, __('CF Links', 'cf-links')
				, 10
				, CFLK_BASENAME
				, 'cflk_admin'
			);
		}
	}
	add_action('admin_menu','cflk_menu_items');
	
	
	function cflk_handle_shortcode($attrs, $content=null) {
		// Check to make sure we have something to filter
		if (is_array($attrs)) {
			$key = '';
			// Find the legacy value for the key
			if (!empty($attrs['name'])) {
				$key = stripslashes($attrs['name']);
			}
			// Find the key using the new method
			else if (!empty($attrs['key'])) {
				$key = stripslashes($attrs['key']);
			}
			
			// Check to see if we have a key to search for and display
			if (!empty($key)) {
				return cflk_get_links($key);
			}
		}
		return false;
	}
	add_shortcode('cflk', 'cflk_handle_shortcode');
	// Legacy Shortcodes
	add_shortcode('cflk_links', 'cflk_handle_shortcode');
	add_shortcode('cfl_links', 'cflk_handle_shortcode');
	
## Functions

	/**
	 * Init the links object
	 * Start an admin object when in the admin
	 *
	 * @return void
	 */
	function cflk_init() {
		global $cflk_links;
		if (is_admin()) {
			$class = 'cflk_admin';
		}
		else {
			$class = 'cflk_links';
		}
		$cflk_links = new $class();

		if (defined('MULTISITE') && MULTISITE && class_exists('cflk_reference')) {
			$cflk_reference = new cflk_reference();
		}

		$cflk_links->import_included_link_types();
	}
	add_action('plugins_loaded','cflk_init',1);
	
	/**
	 * Show the admin page
	 * All page delegation done within the object
	 */
	function cflk_admin() {
		global $cflk_links;
		echo $cflk_links->admin();
	}
	
	function cflk_register_link($classname) {
		global $cflk_links;
		return $cflk_links->register_link_type($classname);
	}
	
## TinyMCE Functionality

	function cflk_tinymce_dialog() {
		global $cflk_links;
		$lists = $cflk_links->_tinymce();
		?>
		<html>
			<head>
				<title><?php _e('Select CF Links List', 'cf-links'); ?></title>
				<script type="text/javascript" src="<?php echo includes_url('js/jquery/jquery.js'); ?>"></script>
				<script type="text/javascript" src="<?php echo includes_url('js/tinymce/tiny_mce_popup.js'); ?>"></script>
				<script type="text/javascript" src="<?php echo includes_url('js/quicktags.js'); ?>"></script>
				<script type="text/javascript">
					;(function($) {
						$(function() {
							$(".cflk-list-link").live('click', function() {
								var key = $(this).attr('rel');
								cflk_insert(key);
							});
						});
						
						function cflk_insert(key) {
							tinyMCEPopup.execCommand("mceBeginUndoLevel");
							tinyMCEPopup.execCommand("mceInsertContent", false, '<p>[cflk key="'+key+'"]</p>');
							tinyMCEPopup.execCommand("mceEndUndoLevel");
							tinyMCEPopup.close();
							return false;
						}
					})(jQuery);
				</script>
			</head>
			<body id="cflink">
				<?php
				if (!empty($lists)) {
					echo '<p>'.__('Click on the Links List title below to add the shortcode to the content of the post.', 'cf-links').'</p>';
					echo '<p>'.$lists.'</p>';
				}
				else {
					echo '<p>'.__('No Links Lists have been setup.  Please create a Links List before proceeding.', 'cf-links').'</p>';
				}
				?>
			</body>
		</html>
		<?php
	}
	
	function cflk_tinymce_register_button($buttons) {
		array_push($buttons, '|', "cfLinksBtn");
		return $buttons;
	}
	
	function cflk_tinymce_add_plugin($plugin_array) {
		$plugin_array['cflinks'] = CFLK_PLUGIN_URL.'/js/editor_plugin.js';
		return $plugin_array;
	}
	
	function cflk_tinymce_add() {
		// Don't bother doing this stuff if the current user lacks permissions
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) { return; }

		// Add only in Rich Editor mode
		if (get_user_option('rich_editing') == 'true') {
			add_filter("mce_external_plugins", "cflk_tinymce_add_plugin");
			add_filter('mce_buttons', 'cflk_tinymce_register_button');
		}
		
		// Get the Dialog Box Content based on $_GET var
		if (!empty($_GET['cf_action'])) {
			switch ($_GET['cf_action']) {
				case 'cflk-dialog':
					cflk_tinymce_dialog();
					die();
					break;
			}
		}
	}
	add_action('init', 'cflk_tinymce_add');
	
## Auxiliary

if (!function_exists('cf_get_blog_list') && ((defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE) || (defined('MULTISITE') && MULTISITE))) {
	/**
	 * Since our friends on the WordPress core team thought that this function was too dangerous for us 
	 * poor developers to use, it is included here so we can use it.
	 *
	 * @param string $start 
	 * @param string $num 
	 * @return array
	 */
	function cf_get_blog_list($start = 0, $num = 10) {
		global $wpdb;

		// $blogs = get_site_option("blog_list");
		$update = false;
		if (is_array($blogs)) {
			if (($blogs['time'] + 60) < time()) { // cache for 60 seconds.
				$update = true;
			}
		} else {
			$update = true;
		}

		if ($update == true) {
			unset($blogs);
			$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A);

			foreach ((array) $blogs as $details) {
				$blog_list[$details['blog_id']] = $details;
				if ($details['blog_id'] == 1) {
					$blog_list[$details['blog_id']]['postcount'] = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->base_prefix."posts WHERE post_status='publish' AND post_type='post'");
					$blog_list[$details['blog_id']]['blogname'] = $wpdb->get_var("SELECT option_value FROM ".$wpdb->base_prefix."options WHERE option_name='blogname'");
				}
				else {
					$blog_list[$details['blog_id']]['postcount'] = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->base_prefix.$details['blog_id']."_posts WHERE post_status='publish' AND post_type='post'");
					$blog_list[$details['blog_id']]['blogname'] = $wpdb->get_var("SELECT option_value FROM ".$wpdb->base_prefix.$details['blog_id']."_options WHERE option_name='blogname'");
				}
			}
			unset($blogs);
			$blogs = $blog_list;
			update_site_option("blog_list", $blogs);
		}

		if (false == is_array($blogs))
			return array();

		if ($num == 'all')
			return array_slice($blogs, $start, count($blogs));
		else
			return array_slice($blogs, $start, $num);
	}
}

/**
 * Add some information to the "Right Now" section of the WP Admin Dashboard.  This will make it easier to
 * get into the Links edit screen.
 */
function cflk_rightnow_end() {
	global $cflk_links;
	$count = count($cflk_links->get_all_lists_for_blog());
	$link = admin_url('options-general.php?page='.CFLK_BASENAME);
	?>
	<tr>
		<td class="first b b-tags"><a href="<?php echo $link; ?>"><?php echo $count; ?></a></td>
		<td class="t tags"><a href="<?php echo $link; ?>"><?php _e('CF Links List', 'cf-links'); echo ($count == 1) ? '' : 's'; ?></a></td>
	</tr>
	<?php
}
add_action('right_now_content_table_end', 'cflk_rightnow_end');

/**
 * Add functionality for the CF Readme plugin
 */
function cflk_add_readme() {}
function cflk_readme() {}


?>