<?php

class cflk_reference extends cflk_links {
	
	private $updating = false;
	
	function __construct() {
		// add_actions
		add_action('cflk_save_list', array($this, 'save_list'));
		add_action('cflk_delete_list', array($this, 'delete_list'));
		add_action('init', array($this,'admin_request_handler'), 12);
		
		// Admin filters
		add_filter('cflk_admin_import_after', array($this, 'display_import_reference'));
		add_filter('cflk_admin_messages', array($this, 'admin_messages'));
		add_filter('cflk_link_base_admin_view_editable', array($this, 'admin_edit_include_buttons'), 10, 2);
		add_filter('cflk_link_edit_allow', array($this, 'admin_edit_allow'), 10, 2);
		add_filter('cflk_edit_list_details', array($this, 'edit_list_details'), 10, 2);
		add_filter('cflk_edit_list_details_edit', array($this, 'edit_list_details_edit'), 10, 2);
		add_filter('cflk_process_list', array($this, 'process_list'), 10, 2);
		add_filter('cflk_main_list_description', array($this, 'list_description'), 10, 2);
		add_filter('cflk_process_reference', array($this, 'process_reference_page'), 10);
		add_filter('cflk_process_reference', array($this, 'process_reference_author'), 10);
		add_filter('cflk_process_reference', array($this, 'process_reference_author_rss'), 10);
		add_filter('cflk_process_reference', array($this, 'process_reference_category'), 10);
		add_filter('cflk_process_reference', array($this, 'process_reference_list'), 10);
		add_filter('cflk_admin_css', array($this, 'admin_css'), 10);
		add_filter('cflk_get_all_lists_for_blog_list', array($this, 'filter_all_lists_for_blog'), 10, 4);
		add_filter('cflk_admin_class_get_list_data', array($this, 'filter_admin_list_data'), 10, 2);
		
		// Front end filters
		add_filter('cflk_build_list_parent_before_class', array($this, 'parent_before_class'), 10, 2);
		add_filter('cflk_build_item_before_class', array($this, 'child_before_class'), 10, 2);
	}
	
	function admin_css($css = '') {
		$css .= '
		.cflk-reference-list-children {
			margin-left:20%;
			background-color: #fff;
			border: 1px solid #ccc;
			border-radius: 5px; /* Standard. IE9 */
			padding: 10px 12px 12px 12px;
			-moz-border-radius: 5px; /* FF1+ */
			-webkit-border-radius: 5px; /* Saf3+, Chrome */
			-khtml-border-radius: 5px; /* Konqueror */
		}
		';
		return $css;
	}
	
	/**
	 * Request Handler for catching submitted items
	 *
	 * @return void
	 */
	function admin_request_handler() {
		if (!empty($_POST['cflk_action'])) {
			switch($_POST['cflk_action']) {
				case 'reference_import':
					$this->process_reference(esc_attr($_POST['cflk-reference-import-list']));
					break;
			}
		}
	}
	
	/**
	 * Removing the edit buttons from the list display screen, so reference items can't be edited
	 *
	 * @param string $include 
	 * @param array $data 
	 * @return bool
	 */
	function admin_edit_allow($display, $list_id) {
		if ($this->is_reference($list_id)) {
			$display = false;
		}
		else {
			$display = true;
		}
		return $display;
	}
	
	/**
	 * Modifying the admin display messages to add reference list messages
	 *
	 * @param array $messages 
	 * @return array
	 */
	function admin_messages($messages) {
		$messages[70] = __('Reference List Created', 'cf-links');
		$messages[71] = __('Reference List Creation Error', 'cf-links');
		
		return $messages;
	}
	
	/**
	 * Process submitted reference import
	 *
	 * @param string $key 
	 * @return void
	 */
	function process_reference($key) {
		$this->errors = new cflk_error;
		global $blog_id, $cflk_links;
		
		// Get the Blog ID and List ID from the passed in key
		$split = explode('-', $key, 2);
		$ref_blog_id = $split[0];
		$ref_list_id = $split[1];
		
		// Hold the current blog id for reference
		$current_blog_id = $blog_id;
		
		// Don't allow anyone to create a reference to a list on the same blog
		if ($blog_id == $ref_blog_id) {
			wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=import&cflk_message=71'));
			exit;
		}
		
		// Get the List Data from the Reference Blog
		switch_to_blog($ref_blog_id);
		$list = $cflk_links->get_list_data($ref_list_id);
		// Allow anybody to filter the content of this list
		$list = apply_filters("cflk_process_reference", $list);
		restore_current_blog();
		
		// Check to make sure we have a valid list
		if (!is_array($list) || empty($list)) {
			wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=import&cflk_message=71'));
			exit;
		}
		
		// Add the Reference information so we know where we got this
		$list['reference_parent_blog'] = $ref_blog_id;
		$list['reference_parent_list'] = $ref_list_id;
		
		unset($list['reference_children']);
		
		// Do a check to make sure that we have a unique list id
		$unique_check = $cflk_links->check_unique_list_id($list['key']);
		$list['key'] = $unique_check['list_id'];
		$list['key'] = $unique_check['list_id'];
		
		// Save the list data
		if ($cflk_links->save_list_data($list)) {
			$list_key = $list['key'];
			unset($list);
			$this->updating = true;
			switch_to_blog($ref_blog_id);
			$list = $cflk_links->get_list_data($ref_list_id);
			$list['reference_children'][] = $current_blog_id.'-'.$list_key;
			$cflk_links->save_list_data($list);
			restore_current_blog();
			$this->updating = false;
			wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=edit&list='.$list_key.'&cflk_message=70'));
			exit;
		}
		// If we got here, something went wrong
		wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=import&cflk_message=71'));
		exit;
	}
	
	/**
	 * Process the data that inserted into the List Edit form
	 *
	 * @param array $list 
	 * @param array $post 
	 * @return array
	 */
	function process_list($list, $post) {
		// Check to make sure that we have something to process
		if (!empty($post['cflk_reference_children'])) {
			// Decode the JSON we included
			$children = cf_ajax_decode_json($post['cflk_reference_children'], true);
			// Add the child array to the list data
			if (is_array($children) && !empty($children)) {
				$list['reference_children'] = $children;
			}
		}
		return $list;
	}
	
	/**
	 * Check to see if the list has children, then update those children as needed with the new data
	 * from the parent list
	 *
	 * @param string $list_key 
	 * @param string $action | Options: 'save' or 'delete'
	 * @return void
	 */
	function update_children($list_key, $action = 'save') {
		// Get the list data
		global $cflk_links;
		$list = $cflk_links->get_list_data(esc_attr($list_key));
		// Check to make sure we have a proper list and children to update
		if (!is_array($list) || empty($list) || empty($list['reference_children'])) { return; }
		
		// Allow anybody to filter the content of this list
		$list = apply_filters("cflk_process_reference", $list);
		
		// Get the children that need updating
		$children = $list['reference_children'];
		// Process the children and update as needed
		foreach ($children as $child) {
			// Get the Blog ID and List ID
			$split = explode('-', $child, 2);
			$ref_blog_id = $split[0];
			$ref_list_id = $split[1];
			switch_to_blog($ref_blog_id);
			switch ($action) {
				case 'delete':
					// Delete the list
					delete_option($ref_list_id);
					break;
				case 'save':
				default:
					// Get the data to update
					$child_list = $cflk_links->get_list_data(esc_attr($ref_list_id));
					// Update the list data
					$child_list['nicename'] = $list['nicename'];
					$child_list['key'] = $list['key'];
					$child_list['description'] = $list['description'];
					$child_list['data'] = $list['data'];
					// Save the new list data
					$cflk_links->save_list_data($child_list);
					break;
			}
			restore_current_blog();
		}
	}
	
	/**
	 * Check to see if the list has a parent, then update the parent as needed with the new data sent from 
	 * the child
	 *
	 * @param string $list_key 
	 * @param string $action 
	 * @return void
	 */
	function update_parent($list_key, $action = 'delete') {
		// Get the list data
		global $cflk_links, $blog_id;
		$list = $cflk_links->get_list_data(esc_attr($list_key));
		// Check to make sure we have a proper list and children to update
		if (!is_array($list) || empty($list) || empty($list['reference_parent_blog']) || empty($list['reference_parent_list'])) { return; }
		
		$child_blog_id = $blog_id;
		$child_key = $blog_id.'-'.$list_key;
		
		// Allow anybody to filter the content of this list
		$list = apply_filters("cflk_process_reference_update_parent", $list, $action);
		
		switch_to_blog($list['reference_parent_blog']);
		$parent_list = get_option($list['reference_parent_list']);
		switch ($action) {
			case 'delete':
				if (!empty($parent_list['reference_children']) && is_array($parent_list['reference_children'])) {
					$keys = array();
					foreach ($parent_list['reference_children'] as $key => $value) {
						if ($value == $child_key) {
							$keys[] = $key;
						}
					}

					if (is_array($keys) && !empty($keys)) {
						foreach ($keys as $key) {
							unset($parent_list['reference_children'][$key]);
						}
						update_option($list['reference_parent_list'], $parent_list);
					}
				}
				break;
		}
		restore_current_blog();
	}
	
	/**
	 * Run a filter on save list, if we have a list with children, update those children with the latest data
	 *
	 * @param string $key 
	 * @return void
	 */
	function save_list($key) {
		if ($this->has_children($key) && !$this->updating) {
			$this->update_children($key, 'save');
		}
	}
	
	/**
	 * Run a filter on delete list, if we have a list with children, delete all the children so we don't have
	 * orphans
	 *
	 * @param string $key 
	 * @return void
	 */
	function delete_list($key) {
		if ($this->has_children($key)) {
			$this->update_children($key, 'delete');
		}
		else if ($this->is_reference($key)) {
			$this->update_parent($key, 'delete');
		}
	}
	
	/**
	 * Display additional details about the reference list (if it is a reference list). If it is a parent list, display 
	 * that info as well
	 *
	 * @param string $content 
	 * @param string $key 
	 * @return string
	 */
	function edit_list_details($content, $key) {

		return $content;
	}
	
	/**
	 * Add additional information to the edit form, so we can make sure that the reference children stay put in the data structure
	 *
	 * @param string $content 
	 * @param string $key 
	 * @return string
	 */
	function edit_list_details_edit($content, $key) {
		global $cflk_links;
		$info = '';
		$input = '';
		
		if ($this->is_reference($key)) {
			// Get the list's data, so we can find out its parent, if it has one
			global $cflk_links;
			$list = $cflk_links->get_list_data($key);

			// Check to see if we have a parent list to get info about
			if (is_array($list) && !empty($list)) {
				// Get the details about the parent list
				switch_to_blog($list['reference_parent_blog']);
				$parent_list = $cflk_links->get_list_data($list['reference_parent_list']);
				$edit_url = admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$list['reference_parent_list']);
				$name = get_bloginfo('name');
				restore_current_blog();
				
				$info .= '<div class="elm-list-reference-link elm-item-not-editable">'.__('This list is a reference to <i>', 'cf-links').$parent_list['nicename'].__('</i> on <i>', 'cf-links').$name.__('</i>. Edit this list', 'cf-links').' <a href="'.$edit_url.'">'.__('here', 'cf-links').'</a>.</div>';
			}
			// If we don't, we're in trouble
			else {
				$info .= __('This list is an orphaned reference list.', 'cf-links');
			}
		}
		else if ($this->has_children($key)) {
			// Get the lists's data, so we can find out what children it has.
			global $cflk_links;
			$list = $cflk_links->get_list_data($key);
			if (is_array($list) && !empty($list)) {
				$blogs = '';
				$count = 1;
				foreach ($list['reference_children'] as $child) {
					// Get the Blog ID and List ID
					$split = explode('-', $child, 2);
					$ref_blog_id = $split[0];
					$ref_list_id = $split[1];
					// Get the Blog Info so we can find out the blog name
					$bloginfo = get_blog_details($ref_blog_id);
					$blogs .= '<i><a href="'.get_admin_url($bloginfo->blog_id, 'options-general.php?page='.CFLK_BASENAME).'">'.$bloginfo->blogname.'</a></i>';
					if ($count < count($list['reference_children'])) {
						$blogs .= ', ';
					}
					$count++;
				}
			}
			$info .= '<div class="cflk-reference-list-children">'.__('This list has a child on blog', 'cf-links').(count($list['reference_children']) > 1 ? 's' : '').': '.$blogs.'</div>';
		}		
		
		if ($this->has_children($key)) {
			$list = $cflk_links->get_list_data(esc_attr($key));
			$content .= '
				<input type="hidden" name="cflk_reference_children" value="'.(!empty($list['reference_children']) ? esc_attr(cf_json_encode($list['reference_children'])) : null).'" />
			';
		}

		if (!empty($info)) {
			$content .= '
				<div class="elm-block">
					<label class="lbl-input">'.__('Reference', 'cf-links').'</label>
					'.$info.'
					'.$input.'
				</div>
			';
		}

		return $content;
	}
	
	/**
	 * Add more information to the List description section to display information about reference lists.  For children show where 
	 * the parent is.  For parents, show who the children are.
	 *
	 * @param string $description 
	 * @param string $key 
	 * @return string
	 */
	function list_description($description, $key) {
		if ($this->is_reference($key)) {
			// Get the list's data, so we can find out its parent, if it has one
			global $cflk_links;
			$list = $cflk_links->get_list_data($key);
			$description .= '<p><b>'.__('Reference List', 'cf-links').':</b> ';

			// Check to see if we have a parent list to get info about
			if (is_array($list) && !empty($list)) {
				// Get the details about the parent list
				switch_to_blog($list['reference_parent_blog']);
				$parent_list = $cflk_links->get_list_data($list['reference_parent_list']);
				$edit_url = admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$list['reference_parent_list']);
				$name = get_bloginfo('name');
				restore_current_blog();
				
				$description .= '<div class="elm-list-description elm-item-not-editable">'.__('This list is a reference to <i>', 'cf-links').$parent_list['nicename'].__('</i> on <i>', 'cf-links').$name.__('</i>. Edit this list', 'cf-links').' <a href="'.$edit_url.'">'.__('here', 'cf-links').'</a>.</div>';
			}
			// If we don't, we're in trouble
			else {
				$description .= __('This list is an orphaned reference list.', 'cf-links');
			}
			$description .= '</p>';
		}
		else if ($this->has_children($key)) {
			$description .= '<p><b>'.__('Reference List', 'cf-links').':</b> ';
			// Get the lists's data, so we can find out what children it has.
			global $cflk_links;
			$list = $cflk_links->get_list_data($key);
			if (is_array($list) && !empty($list)) {
				$blogs = '';
				$count = 1;
				foreach ($list['reference_children'] as $child) {
					// Get the Blog ID and List ID
					$split = explode('-', $child, 2);
					$ref_blog_id = $split[0];
					$ref_list_id = $split[1];
					// Get the Blog Info so we can find out the blog name
					$bloginfo = get_blog_details($ref_blog_id);
					$blogs .= '<i><a href="'.get_admin_url($bloginfo->blog_id, 'options-general.php?page='.CFLK_BASENAME).'">'.$bloginfo->blogname.'</a></i>';
					if ($count < count($list['reference_children'])) {
						$blogs .= ', ';
					}
					$count++;
				}
			}
			$description .= __('This list has children on blog', 'cf-links').(count($list['reference_children']) > 1 ? 's' : '').': '.$blogs;
			$description .= '</p>';
		}
		return $description;
	}
	
	/**
	 * Display the options for importing a reference list
	 *
	 * @param string $content 
	 * @return string
	 */
	function display_import_reference($content) {
		$content .= '
		<form method="post" id="cflk-reference-import-form" name="cflk_reference_import_form" class="cflk-reference-import" action="'.$_SERVER['REQUEST_URI'].'">
			<table id="cflk-reference-import" class="widefat">
				<thead>
					<tr>
						<th scope="col">'.__('Select a list below to be imported as a reference list.', 'cf-links').'</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							'.$this->get_dropdown_lists().'
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input id="cflk-reference-import-submit" type="submit" class="button-primary" value="'.__('Create Reference','cf-links').'" />
				<input type="hidden" name="cflk_action" value="reference_import" />
			</p>
		</form>
		';
		return $content;
	}
	
	/**
	 * Get all of the lists, for all of the blogs in a dropdown
	 *
	 * @return string
	 */
	function get_dropdown_lists() {
		global $blog_id, $cflk_links;
		$initial_blog_id = $blog_id;
		$bloglist = cf_get_blog_list(0, 'all');
		$options = '';
		if (is_array($bloglist) && !empty($bloglist)) {
			foreach ($bloglist as $blog) {
				if ($blog['blog_id'] == $initial_blog_id) { continue; }
				switch_to_blog($blog['blog_id']);
				$lists = $cflk_links->get_all_lists_for_blog();
				$blogname = $blog['blogname'];
				if (is_array($lists) && !empty($lists)) {
					foreach ($lists as $key => $list) {
						if ($list['reference']) { continue; }
						$options .= '<option value="'.esc_attr($blog['blog_id']).'-'.esc_attr($key).'">'.esc_attr($list['nicename'].' -- '.$blogname).'</option>';
					}
				}
				restore_current_blog();
			}
		}
		
		if (!empty($options)) {
			return '
			<select name="cflk-reference-import-list" id="cflk-reference-import-list">
				'.$options.'
			</select>
			';
		}
		return '';
	}
	
	/**
	 * Filter all of the lists being retrieved for a blog, so we can add in the information about this being a reference list
	 *
	 * @param array $list 
	 * @param string $list_id 
	 * @param array $options 
	 * @param string $blog 
	 * @return array
	 */
	function filter_all_lists_for_blog($list = array(), $list_id = '', $options = array(), $blog = 0) {
		if (empty($list) || !is_array($list) || empty($options) || !is_array($options)) { return $list; }
		if (!empty($options['reference']) && $options['reference']) {
			$list['reference'] = true;
			$list['reference_parent_blog'] = $options['reference_parent_blog'];
			$list['reference_parent_list'] = $options['reference_parent_list'];
		}
		return $list;
	}
	
	function filter_admin_list_data($list = array(), $list_key = '') {
		if (empty($list) || empty($list_key) || !is_array($list['reference_children']) || empty($list['reference_children'])) { return $list; }
		// If we have a parent list, remove the child data as that probably won't be relevant to the destination
		unset($list['reference_children']);
		return $list;
	}
	
	/**
	 * Check to see wether a list is a reference list or not by key
	 *
	 * @param string $key 
	 * @return bool
	 */
	function is_reference($key) {
		global $cflk_links;
		$list = $cflk_links->get_list_data(esc_attr($key));
		if (is_array($list) && !empty($list) && !empty($list['reference_parent_blog']) && !empty($list['reference_parent_list'])) {
			return true;
		}
		return false;
	}
	
	/**
	 * Check to see wether a list is a parent list or not by key
	 *
	 * @param string $key 
	 * @return bool
	 */
	function has_children($key) {
		global $cflk_links;
		$list = $cflk_links->get_list_data(esc_attr($key));
		if (is_array($list) && !empty($list) && is_array($list['reference_children']) && !empty($list['reference_children'])) {
			return true;
		}
		return false;
	}
	
	/**
	 *
	 * Front End Display Filters
	 *
	 */

	/**
	 * Function to add a custom class to the image display for reference lists
	 *
	 * @param string $class
	 * @param string $key
	 * @return string
	 */
	function parent_before_class($class, $key) {
		if (!empty($key) && $this->is_reference($key)) {
			$class .= ' cflk-reference-list';
		}
		return $class;
	}
	
	/**
	 * Update the class being applied to the link to remove the "fixed" class for reference items, to add back the
	 * original link type class for easier handling
	 *
	 * @param string $class
	 * @param string $item
	 * @return string
	 */
	function child_before_class($class, $item) {
		if (is_array($item) && !empty($item) && !empty($item['original'])) {
			$class = str_replace('cflk-'.$item['type'], '', $class);
			$class .= ' cflk-'.$item['original'];
		}
		return $class;
	}

	
	/**
	 * 
	 * Link Type Filters
	 * 
	 */
	
	/**
	 * Filter the Page type to change the type to URL so the link will display properly on child blogs
	 *
	 * @param array $list 
	 * @return array
	 */
	function process_reference_page($list) {
		if (is_array($list) && !empty($list) && is_array($list['data']) && !empty($list['data'])) {
			foreach ($list['data'] as $key => $item) {
				if (!is_array($item) || empty($item) || empty($item['type']) || $item['type'] != 'page') { continue; }
				// Get the original post info
				$link = get_permalink($item['link']);
				$title = get_the_title($item['link']);
				// Update the item with the new info
				$list['data'][$key]['type'] = 'url';
				$list['data'][$key]['link'] = $link;
				// Save the original type, so we process it later
				$list['data'][$key]['original'] = $item['type'];
				// We only need to filter the title if we don't already have a title
				if (empty($list['data'][$key]['title'])) {
					$list['data'][$key]['title'] = $title;
				}
			}
		}
		return $list;
	}

	/**
	 * Filter the Author type to change the type to URL so the link will display properly on child blogs
	 *
	 * @param array $list 
	 * @return array
	 */
	function process_reference_author($list) {
		if (is_array($list) && !empty($list) && is_array($list['data']) && !empty($list['data'])) {
			foreach ($list['data'] as $key => $item) {
				if (!is_array($item) || empty($item) || empty($item['type']) || $item['type'] != 'author') { continue; }
				// Get the original author info
				$link = get_author_link(false, $item['link']);
				$title = get_author_name($item['link']);
				// Update the item with the new info
				$list['data'][$key]['type'] = 'url';
				$list['data'][$key]['link'] = $link;
				// Save the original type, so we process it later
				$list['data'][$key]['original'] = $item['type'];
				// We only need to filter the title if we don't already have a title
				if (empty($list['data'][$key]['title'])) {
					$list['data'][$key]['title'] = $title;
				}
			}
		}
		return $list;
	}

	/**
	 * Filter the Author RSS type to change the type to URL so the link will display properly on child blogs
	 *
	 * @param array $list 
	 * @return array
	 */
	function process_reference_author_rss($list) {
		if (is_array($list) && !empty($list) && is_array($list['data']) && !empty($list['data'])) {
			foreach ($list['data'] as $key => $item) {
				if (!is_array($item) || empty($item) || empty($item['type']) || $item['type'] != 'author_rss') { continue; }
				// Get the original author info
				$link =  get_author_rss_link(false, $item['link']);
				$title = get_author_name($item['link']);
				// Update the item with the new info
				$list['data'][$key]['type'] = 'url';
				$list['data'][$key]['link'] = $link;
				// Save the original type, so we process it later
				$list['data'][$key]['original'] = $item['type'];
				// We only need to filter the title if we don't already have a title
				if (empty($list['data'][$key]['title'])) {
					$list['data'][$key]['title'] = $title;
				}
			}
		}
		return $list;
	}
	
	/**
	 * Filter the Category type to change the type to URL so the link will display properly on child blogs
	 *
	 * @param array $list 
	 * @return array
	 */
	function process_reference_category($list) {
		if (is_array($list) && !empty($list) && is_array($list['data']) && !empty($list['data'])) {
			foreach ($list['data'] as $key => $item) {
				if (!is_array($item) || empty($item) || empty($item['type']) || $item['type'] != 'category') { continue; }
				// Get the original author info
				$category = get_category($item['link']);
				$link = get_category_link($item['link']);
				$title = $category->name;
				// Update the item with the new info
				$list['data'][$key]['type'] = 'url';
				$list['data'][$key]['link'] = $link;
				// Save the original type, so we process it later
				$list['data'][$key]['original'] = $item['type'];
				// We only need to filter the title if we don't already have a title
				if (empty($list['data'][$key]['title'])) {
					$list['data'][$key]['title'] = $title;
				}
			}
		}
		return $list;
	}

	/**
	 * :: TODO:: Filter the List type
	 *
	 * @param array $list 
	 * @return array
	 */
	function process_reference_list($list) {
		
		return $list;
	}

}
?>