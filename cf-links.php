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
	function cflk_links($list_id, $args) {
		echo cflk_get_links($list_id, $args);
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
	function cflk_get_links($list_id, $args) {
		global $cflk_links;

		$list = $cflk_links->get_list($list_id, $args);

		if ($list != false) {
			return $list->display();
		}
		else {
			return '';
		}
	} 
		
	// retain as accessor function?
	function cflk_get_list_links($list) {} 
	
	// retain as accessor function?
	function cflk_get_links_data($list) {} 
	
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
	
	
	function cflk_add_readme() {}
	function cflk_readme() {}
	function cflk_handle_shortcode() {}
	
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
	}
	add_action('plugins_loaded','cflk_init');
	
	/**
	 * Show the admin page
	 * All page delegation done within the object
	 */
	function cflk_admin() {
		global $cflk_links;
		echo $cflk_links->admin();
	}
	
	function cflk_register_link($id, $classname) {
		global $cflk_links;
		return $cflk_links->register_link_type($id, $classname);
	}
	
	function cflk_tinymce_dialog() {} // replaces cflk_dialog()
	function cflk_tinymce_register_button() {} // replaces cflk_register_button()
	function cflk_tinymce_add_pluing() {} // replaces cflk_add_tinymce_button
	function cflk_tinymce_add() {} // replaces cflk_addtinymce

?>