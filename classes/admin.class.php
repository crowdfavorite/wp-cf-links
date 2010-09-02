<?php

class cflk_admin extends cflk_links {
	
	private $in_ajax = false;
	private $editing_list = false;
	protected $messages = array(
		'1' => 'List Created',
		'2' => 'List Saved',
		'3' => 'List Deleted',
		'4' => 'List Imported'
	);
	
	function __construct() {
		parent::__construct();
		// enqueue_scripts
		wp_enqueue_script('cflk-admin-css',admin_url('/index.php?page=cflk-links&cflk_action=admin_js'),array('jquery'),CFLK_PLUGIN_VERS);
		wp_enqueue_script('jquery-form');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		// enqueue_styles
		wp_enqueue_style('cflk-admin-js',admin_url('/index.php?page=cflk-links&cflk_action=admin_css'),array(),CFLK_PLUGIN_VERS,'all');
		// add_actions
		add_action('init', array($this,'admin_request_handler'), 12);
		add_action('wp_ajax_cflk_ajax', array($this,'ajax_handler'));
	}
	
	function admin_request_handler() {
		// Resources
		if (!empty($_GET['cflk_action'])) {
			switch ($_GET['cflk_action']) {
				case 'admin_js':
					$this->admin_js();
					break;
				case 'admin_css':
					$this->admin_css();
					break;
			}
		}
		
		// Actions
		if (!empty($_POST['cflk_action'])) {
			switch($_POST['cflk_action']) {
				case 'edit_list':
					$this->process_list();
					break;
				case 'delete_list':
					if (!empty($_POST['list_key'])) {
						$this->delete_list(esc_attr($_POST['list_key']));
					}
					break;
			}
		}
	}
	
# pages

	/**
	 * Main Amdin page delegate
	 *
	 * @return void
	 */
	function admin() {
		$method = (!empty($_GET['cflk_page']) ? '_'.strval($_GET['cflk_page']) : '_main'); // cflk_page is legacy var name for compat
		if (method_exists($this,$method)) {
			$ret = $this->$method();
		}
		return $ret;
	}
	
	/**
	 * Admin "home" page
	 *
	 * @return void
	 */
	function _main() {
		$lists = $this->get_all_lists_for_blog();
		if (is_array($lists) && function_exists('cf_sort_by_key')) {
			$lists = cf_sort_by_key($lists,'nicename');
		}
		
		// show list of available lists
		$html = $this->admin_wrapper_open().$this->admin_navigation('main').'
			<table id="cflk-available-lists" class="widefat">
				<thead>
					<tr>
						<th scope="col">'.__('Available Lists', 'cf-links').'</th>
						<th scope="col" style="text-align:center;" width="80px">'.__('Count', 'cf-links').'</th>
						<th scope="col" style="text-align:center;" width="80px">'.__('Edit', 'cf-links').'</th>
						<th scope="col" style="text-align:center;" width="80px">'.__('Delete', 'cf-links').'</th>
					</tr>
				</thead>
				<tbody>
				';
		if (is_array($lists) && count($lists)) {
			foreach($lists as $id => $list) {
				$description = '';
				
				if (!empty($list['description'])) {
					$description = '<p class="cflk-description">'.$list['description'].'</p>';
				}
				
				$html .= '
						<tr id="cflk-list-'.$id.'">
							<td class="cflk-list-info">
								<p><a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$id).'" class="cflk-list-title">'.$list['nicename'].'</a> | <span><a class="cflk-toggle" href="#cflk-details-'.$id.'">Details</a></span></p>
								<div id="cflk-details-'.$id.'" class="cflk-details" style="display: none">
									'.$description.'
									<ul class="cflk-description-items">
										<li><span class="cflk-description-item cflk-description-item-template">Template Tag:</span> <code>&lt;?php if (function_exists(&quot;cflk_links&quot;)) { cflk_links(&quot;'.$id.'&quot;); } ?&gt;</code></li>
										<li><span class="cflk-description-item cflk-description-item-shortcode">Shortcode:</span> <code>[cflk_links name=&quot;'.$id.'&quot;]</code></li>
									</ul>
								</div>
							</td>
							<td class="cflk-list-count" style="text-align:center; vertical-align:middle;">
								<p>
									'.$list['count'].'
								</p>
							</td>
							<td class="cflk-list-edit" style="text-align:center; vertical-align:middle;">
								<a class="button" href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$id).'">'.__('Edit', 'cf-links').'</a>
							</td>
							<td class="cflk-list-delete" style="text-align:center; vertical-align:middle;">
								<a class="button cflk-delete-list" id="list-delete-'.$id.'" href="#">'.__('Delete', 'cf-links').'</a>
							</td>
						</tr>
					';
			}
		}
		else {
			$html .= '
				<tr>
					<td colspan="2">
						<p>There are currently no lists to display. Use the "Add New List" button above to get started.</p>
					</td>
				</tr>
			';
		}
		$html .= '
				</tbody>
			</table>
			<p>
				<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit').'" class="button-primary cflk-new-list-footer">'.__('Add New List', 'cf-links').'</a>
			</p>
			'.
			apply_filters('cflk_admin_main_after','').
			$this->admin_wrapper_close();
		
		return $html;
	}
	
	/**
	 * Admin Edit Page
	 *
	 * @return void
	 */
	function _edit() {
		$list_id = (!empty($_GET['list']) ? esc_attr($_GET['list']) : false);
		$new = ($list_id == false ? true : false);
		$this->editing_list = $list_id;
		if ($list_id) {
			$list = $this->get_list_data($list_id);
		}

		if (empty($list)) {
			$list = array();
			if (!$new) {
				$new = true;
				$notice = '
					<div id="cflk-notice" class="error below-h2">
						<p>List ID <code>'.esc_attr($list_id).'</code> not found.</p>
					</div>
					';
			}
		}
						
		$listdata = array_merge(array(
				'nicename' => '',
				'key' => '',
				'description' => '',
				'data' => array()
			),$list);
					
		extract($listdata);
		
		// list details
		$html = $this->admin_wrapper_open('Edit List').$this->admin_navigation('edit').'
			'.(!empty($notice) ? $notice : null).'
			'.$this->admin_messages().'
			<form method="post" id="cflk-list-form" name="cflk_list_form" class="cflk-list-'.($new ? 'new' : 'edit').'" action="'.$_SERVER['REQUEST_URI'].'">
				<input type="hidden" name="cflk_action" value="edit_list" />
				<h3>'.__('List Details','cf-links').'</h3>
				<fieldset id="cflk-edit-list-details">
					<div id="cflk-edit-list-details-display">
						<p class="cflk-list-name"><b>List Title: </b> '.$nicename.'</p>
						<p '.($new ? ' style="display: none;"' : '').'><b>List Key:</b> <span class="cflk-list-slug">'.($new ? '' : $key).'</span></p>
						<p class="cflk-list-description"><b>Description:</b> '.$description.'</p>
						<p class="cflk-list-details-edit">
							<a href="#" class="button" id="cflk-edit-list-details">'.__('Edit Details', 'cf-links').'</a>
						</p>
					</div>
					<div id="cflk-edit-list-details-edit">
						<p class="cflk-edit-list-name">
							<label for="cflk_list_name">'.__('List Name', 'cf-links').':</label>
							<input type="text" name="nicename" id="cflk_list_name" value="'.$nicename.'" />
						</p>
						<p class="cflk-edit-list-key">
							<label for="cflk_list_key">'.__('List ID', 'cf-links').':</label>
							<input type="text" name="key" id="cflk_list_key" readonly="readonly" value="'.$key.'" />
						</p>
						<p class="cflk-edit-list-description">
							<label for="cflk_list_description">'.__('Description (optional)', 'cf-links').'</label>
							<textarea name="description" id="cflk_list_description">'.$description.'</textarea>
						</p>
						<p id="cflk-edit-list-details-cancel">
							<a href="#" class="button">Cancel</a>
						</p>
					</div>
				</fieldset>
			';
		
		// Links list 
		$first = true;
		$html .= '
				<div class="cflk-list-items-display">
					<h3>'.__('List Items','cf-links').'</h3>
					<div id="cflk-list-header">
						<div class="cflk-link-move">'.__('Sort', 'cf-links').'</div>
						<div class="cflk-link-type">'.__('Type', 'cf-links').'</div>
						<div class="cflk-link-data">'.__('Details', 'cf-links').'</div>
						<div class="cflk-link-edit">'.__('Edit', 'cf-links').'</div>
						<div class="cflk-link-delete">'.__('Delete', 'cf-links').'</div>
						<div class="clear"></div>
					</div>
					<fieldset id="cflk-list-items">
						<ul id="cflk-list-sortable">
							';
							if (!is_array($data) || !count($data)) {
								$html .= '
								<li class="cflk-no-items cflk-first">
									<p>'.__('There are no items in this list.  Use the "Add Link" button to get started.', 'cf-links').'</p>
								</li>
								';
							}
							else if (is_array($data) || count($data)) {
								foreach ($data as $item) {
									// Check to see if the list key is part of the data, and add it if it isn't
									if (empty($item['list_key'])) {
										$item['list_key'] = $key;
									}
									$class = '';
									if ($first) {
										$class = ' cflk-first';
										$first = false;
									}
									$html .= '
									<li id="'.$this->get_random_id($item['type']).'" class="cflk-item'.$class.'">
										'.$this->link_types[$item['type']]->_admin_view($item).'
									</li>
									';
								}
							}
							$html .= '
						</ul>
					</fieldset>
				</div>
				<input type="submit" id="cflk-list-submit-button" value="'.__($button_text,'cf-links').'" style="display: none;" />
			</form>
			<form id="cflk-new-link-form" action="">
				<div id="cflk-list-items-footer">
					'.$this->edit_forms(true).'
					<div>
						<input type="button" class="button-secondary" name="cflk-new-list-item" id="cflk-new-list-item" value="'.__('Add Link','cf-links').'" />
					</div>
				</div>
			</form>
			';
		
		// Submit
		$button_text = ($new ? 'Save List' : 'Update List');
		$html .= '
			<p class="submit">
				<input id="cflk-list-submit" type="button" class="button-primary" value="'.__($button_text,'cf-links').'" />
				<a href="#" id="cflk-list-export" class="button-primary">'.__('Export List', 'cf-links').'</a>
			</p>
			';

		// $html .= $this->export_list($list_id);
		
		$html .= $this->admin_wrapper_close();
		return $html;
	}
	
	/**
	 * Build the universal edit form list
	 *
	 * @param bool $hidden 
	 * @return string html
	 */
	function edit_forms() {
		$i = 0;
		foreach ($this->link_types as $id => $type) {
			$options .= '
				<option value="'.esc_attr($id).'">'.esc_html($type->name).'</option>
				';
			$types .= '
				<li id="cflk-type-display-'.esc_attr($id).'"'.($i > 0 ? ' style="display: none;"' : null).'>
					'.esc_html($type->name).'
				</li>
				';
			$forms .= '
				<li id="cflk-type-'.esc_attr($id).'"'.($i > 0 ? ' style="display: none;"' : null).'>
					'.$type->_admin_edit_form(array('list_key' => $this->editing_list), false).'
					<input type="hidden" name="type" value="'.esc_attr($id).'" />
				</li>
				';
			$i++;
		}
				
		return '
			<div id="cflk-edit-forms-wrapper" style="display:none;">
				<div class="cflk-edit-forms">
					<div class="cflk-type-select">
					</div>
					<div class="cflk-type-type">
						<select name="cflk-types">
							'.$options.'
						</select>
					</div>
					<div class="cflk-type-forms">
						<ul>
							'.$forms.'
						</ul>
					</div>
					<div class="cflk-type-done">
						<button class="button cflk-link-edit-done">'.__('Done', 'cf-links').'</button>
					</div>
					<div class="cflk-type-cancel">
						<button class="button cflk-cancel">'.__('Cancel', 'cf-links').'</button>
					</div>
					<div class="clear"></div>
				</div>
			</div>
			';
	}
	
	// function display_blocks() {
	// 	$html = '
	// 		<div id="cflk-display-blocks" style="display: none;">
	// 		';
	// 	foreach($this->link_types as $type) {
	// 		$html .= $type->_admin_form(array());
	// 	}	
	// 	$html .= '	
	// 		</div>
	// 	';
	// 	return $html;
	// }
	
	/**
	 * Display an individual link item
	 *
	 * @param array $data 
	 * @return string html
	 */
	function item($data) {
		$data = array_merge(array(
				'title' => '',
				'type' => '',
				'link' => '',
				'level' => '',
				'opennew' => ''
			),$data);
		extract($data);
		
		if (!empty($data['type'])) {
			// haz existing data
		}
		else {
			// new item
		}
		
		$html .= '
						<li class="cflk-item">
							<div class="cflk-item-details">
								<ul>
									<li class="cflk-item-type">'.$this->link_types[$type]->name.'</li>
									<li class="cflk-item-link">'.$this->link_types[$type]->get_link($data).'</li>
									<li class="cflk-item-title"></li>
								</ul>
							</div>
							<div class="cflk-item-edit">
								<input type="hidden" name="cflk_link[]" value="'.cf_json_encode($data).'" />
								<span class="cflk-item-type-select">
									<select name="cflk-select-type">
										<option type="url">'.__('URL','cf-links').'</option>
									</select>
								</span>
								<span class="cflk-item-type-edit"></span>
							</div>
						</li>
			';
		return $html;
	}
	
	/**
	 * Import Page
	 *
	 * @return void
	 */
	function _import() {
		// import a list
		$html = $this->admin_wrapper_open('Import List').$this->admin_navigation('import').'
			<form method="post" id="cflk-import-form">
				<p>TBD</p>
			</form>
			'.$this->admin_wrapper_close();
		return $html;
	}

	/**
	 * TinyMCE Display
	 *
	 * @return void
	 */
	function _tinymce() {
		$html = '';
		$lists = $this->get_all_lists_for_blog();
		if (is_array($lists) && function_exists('cf_sort_by_key')) {
			$lists = cf_sort_by_key($lists,'nicename');
		}
		
		if (is_array($lists) && count($lists)) {
			$html = '<ul id="cflk-list-links">';
			foreach ($lists as $id => $list) {
				$html .= '<li><a href="#" class="cflk-list-link" rel="'.esc_attr($id).'">'.esc_attr($list['nicename']).'</a></li>';
			}
			$html .= '</ul>';
		}
		return $html;
	}

# Delete list

	function delete_list($list_key = 0) {
		$this->errors = new cflk_error;
		
		$list = $this->get_list_data(esc_attr($list_key));
		if ($list !== false) {
			$name = $list['nicename'];
			$result = $this->delete_list_data($list_key);
			if ($result) {
				$return = array(
					'list_key' => esc_attr($list_key),
					'name' => $name
				);
			}
			else {
				$return = false;
			}
		}
		else {
			$return = false;
			$this->errors->add('invalid-list-id', __('Invalid List ID supplied for Delete List'));
		}
		return $return;
	}

# Save/Update list
	
	/**
	 * Process list data for errors and save the list data if possible
	 *
	 * @return void
	 */
	function process_list() {
		$this->errors = new cflk_error;

		$list = array(
			'nicename' => '',
			'key' => '',
			'description' => '',
			'data' => array()
		);

		// Nicename
		if (empty($_POST['nicename'])) {
			$this->errors->add('list-data', 'List Name is a Required Field');
		}
		$list['nicename'] = esc_html($_POST['nicename']);
		
		// Key - This shouldn't happen, but handle it just in case
		if (empty($_POST['key'])) {
			$key = check_unique_list_id($_POST['key']);
			$_POST['key'] = $key['list_id'];
		}
		$list['key'] = esc_attr($_POST['key']);
		
		// Description
		if (!empty($_POST['description'])) {
			$list['description'] = esc_html($_POST['description']);
		}
		
		// Data
		$list['data'] = array();
		if (!empty($_POST['cflk_links'])) {
			foreach ($_POST['cflk_links'] as $position => $link) {
				$link = cf_ajax_decode_json($link, true);
				if ($this->is_valid_link_type($link['type'])) {
					$linkdata = $this->get_link_type($link['type'])->_update($link);
					if (empty($linkdata)) {
						$this->errors->add('list-data', 'Error processing link type data');
					}
					else {
						$list['data'][] = $linkdata;
					}
				}
			}
		}

		// Save
		if (!$this->errors->have_errors()) {
			if ($this->save_list_data($list)) {
				wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=edit&list='.$list['key'].'&cflk_message=2'));
				exit;
			}
			else {
				$this->errors->add('list-save', 'There was an error saving the list');
			}
		}
	}
	
	/**
	 * Save the list data
	 *
	 * @param array $list 
	 * @return bool
	 */
	function save_list_data($list) {
		if (!get_option($list['key'])) {
			add_option($list['key'], $list, 0, 'no');
		}
		else {
			update_option($list['key'], $list);
		}
		return true;
	}
	
	function get_list_data($list_key) {
		if ($list = get_option($list_key)) {
			return $list;
		}
		return false;
	}
	
	/**
	 * Delete the list data
	 *
	 * @param string $list_id 
	 * @return bool
	 */
	function delete_list_data($list_key) {
		return delete_option($list_key);
	}

# Export List
	
	function export_list($list_key) {
		ob_start();
		?>
		<div id="cflk-popup" class="cflk-popup">
			<div class="cflk-popup-head">
				<span class="cflk-popup-close-link">
					<a href="#" class="cflk-popup-close"><?php _e('Close', 'cf-links'); ?></a>
				</span>
				<h2><?php _e('Export List', 'cf-links'); ?></h2>
			</div>
			<div class="cflk-popup-content">
				<div class="cflk-popup-body">
					<p><?php _e('Copy the data in the text area below.  Then paste this data into the <i>CF Links Import page</i> of the destination site.', 'cf-links')?></p>
					<?php
					if (!empty($list_key)) {
						$list = $this->get_list_data($list_key);
						if (!empty($list)) {
							$list = json_encode($list);
						}
						else {
							$list = __('Error, invalid list. Please try again.', 'cf-links');
						}
						?>
						<textarea id="cflk-popup-export-content" class="widefat" rows="20"><?php echo $list; ?></textarea>
						<?php
					}
					else {
						_e('Error, no list ID passed in!', 'cf-links');
					}
					?>
				</div>
				<div class="cflk-popup-body-foot">
					<a href="#" class="cflk-popup-close button-primary"><?php _e('Close', 'cf-links'); ?></a>
				</div>
			</div><!--cflk-popup-content-->
		</div><!--cflk-popup-->
		<?php
		$message = ob_get_contents();
		ob_end_clean();
		return $message;
	}

# Import

	function import_list() {
		
	}

# Helpers

	function get_authors() {
		global $wpdb;
		$sql = "
			SELECT DISTINCT u.ID
			FROM {$wpdb->users} AS u, 
				{$wpdb->usermeta} AS um
			WHERE u.ID = um.user_id
			AND um.meta_key LIKE '{$wpdb->prefix}capabilities'
			AND um.meta_value NOT LIKE '%subscriber%'
			ORDER BY u.user_nicename
			";
		$results = '';
		$count = 1;
		$users = $wpdb->get_results($sql);
		if (is_array($users) && !empty($users)) {
			foreach($users as $u) {
				$results .= $u->ID;
				if ($count < count($users)) {
					$results .= ',';
				}
				$count++;
			}
		}
		return $results;
	}
	
	function get_all_lists_for_blog($blog = 0) {
		global $wpdb, $blog_id;

		// if we're on MU and another blog's details have been requested, change the options table assignment
		if (!is_null($blog_id) && $blog != 0) {
			if ($blog_id == 1) {
				$options = 'wp_options';
			}
			else {
				$options = 'wp_'.$blog.'_options';
			}
		}
		else {
			$options = $wpdb->options;
		}

		$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM {$options} WHERE option_name LIKE 'cfl-%'");
		$return = array();

		if (is_array($cflk_list)) {
			foreach ($cflk_list as $cflk) {
				$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
				$return[$cflk->option_name] = array(
					'nicename' => $options['nicename'], 
					'description' => $options['description'],
					'count' => count($options['data']),
					'data' => $options['data']
				);
			}
		}
		return apply_filters('cflk_get_all_lists_for_blog', (count($return) > 0 ? $return : false));
	}
		
# Messages

	/**
	 * Build a WordPress style messages div based on GET param.
	 * Supports comma separated message IDs in the GET param.
	 * Message IDs are defined at the top of this class.
	 *
	 * @return html
	 */
	function get_messages_html($msg_id = 0) {
		$html = '';
		if (!empty($_GET['cflk_message'])) {
			$messages = explode(',', $_GET['cflk_message']);
			$messages = array_map('intval', $messages);

			$html .= '<div class="cflk-message updated fade below-h2">';
			foreach($messages as $message_id) {
				if (!empty($this->messages[$message_id])) {
					$html .= '<p>'.$this->messages[$message_id].'</p>';
				}
			$html .= '</div>';
			}
		}
		else if ($msg_id && !empty($this->messages[$msg_id])) {
			$html .= '
			<div class="cflk-message updated fade below-h2">
				<p>'.$this->messages[$msg_id].'</p>
			<div>
			';
		}
		return $html;
	}

	/**
	 * Display admin messages, notices and error message
	 *
	 * @return void
	 */
	function admin_messages() {
		$html = '';
		if (!empty($_GET['cflk_message'])) {
			$html .= $this->get_messages_html();
		}
		if (!empty($this->errors) && $this->errors instanceof WP_Error) {
			$html .= $this->errors->html();
		}
		return $html;
	}

# Display Helpers

	function option_post_value($key, $value) {
		return (isset($_POST[$key])) ? esc_attr($_POST[$key]) : esc_attr($value);
	}

	function admin_wrapper_open($title="") {
		if (!empty($title)) {
			$title = ' || '.$title;
		}
		return '
			<div id="cflk-wrap" class="wrap">
				<h2>'.screen_icon().__('CF Links'.$title, 'cf-links').'</h2>
			';
	}
	
	function admin_wrapper_close() {
		return '</div>';
	}
	
	function admin_navigation($location = 'main') {
		$main_class = $new_list_class = $import_list_class = '';
		switch ($location) {
			case 'new-list':
				$new_list_class = ' current';
				break;
			case 'import':
				$import_list_class = ' current';
				break;
			case 'edit':
				break;
			case 'main':
			default:
				$main_class = ' current';
				break;
		}
		
		return '
			<div class="cflk-navigation">
				<ul class="subsubsub">
					<li>
						<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME).'" class="cflk-main'.$main_class.'">'.__('Lists', 'cf-links').'</a>&nbsp;|
					</li>
					<li>
						<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit').'" class="cflk-new-list'.$new_list_class.'">'.__('Add New List', 'cf-links').'</a>&nbsp;|
					</li>
					<li>
						<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=import').'" class="cflk-import-list'.$import_list_class.'">'.__('Import List', 'cf-links').'</a>&nbsp|
					</li>
					<li>
						<a href="'.admin_url('widgets.php').'" class="cflk-widget-link">'.__('Edit Widgets', 'cf-links').'</a>
					</li>
				</ul>
				<div class="clear"></div>
			</div>
		';
	}
	
# Ajax Accessors

	function ajax_handler() {
		$this->in_ajax = true;
		
		$method = 'ajax_'.strval($_POST['func']);
		if (method_exists($this, $method)) {
			$args = cf_ajax_decode_json($_POST['args'], true);
			$result = $this->$method($args);
			if (!($result instanceof cflk_message)) {
				$result = new cflk_message(array(
					'success' => false,
					'message' => __('An unknown error occured during processing.', 'cf-links')
				));
			}
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'message' => __('Invalid method call.', 'cf-links')
			));
		}
		$result->send();
	}
	
	function ajax_export_list($args) {
		if (is_array($args) && !empty($args) && !empty($args['list_key'])) {
			$export = $this->export_list(esc_attr($args['list_key']));
			if ($export) {
				$result = new cflk_message(array(
					'success' => true,
					'message' => $export
				));
			}
			else {
				$result = new cflk_message(array(
					'success' => false,
					'message' => __('Could not export list "'.esc_attr($args['list_key']).'".', 'cf-links')
				));
			}
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'message' => __('Could not process empty ID.', 'cf-links')
			));
		}
		return $result;
	}
	
	function ajax_delete_list($args) {
		if (!empty($args['list_key'])) {
			$processed = $this->delete_list(esc_attr($args['list_key']));
			if ($processed) {
				$result = new cflk_message(array(
					'success' => true,
					'html_message' => $this->get_messages_html(3)
				));
			}
			else {
				$result = new cflk_message(array(
					'success' => false,
					'message' => __('Could not delete list "'.esc_attr($args['list_key']).'".', 'cf-links')
				));
			}
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'message' => __('Could not process empty ID.', 'cf-links')
			));
		}
		return $result;
	}
	
	function ajax_autocomplete($args) {
		
	}
	
	/**
	 * Return the link's admin form state
	 *
	 * @param array $args 
	 * @return object cflk_message
	 */
	function ajax_get_link_edit_form($args) {
		$data = $args['form_data'];
		if (empty($data['list_key'])) {
			$data['list_key'] = $this->editing_list;
		}
		if (!empty($data['type']) && $this->is_valid_link_type($data['type'])) {
			$link_form = $this->get_link_type($data['type'])->_admin_edit_form($data);
			if ($link_form != false) {
				$result = new cflk_message(array(
					'success' => true,
					'html' => $link_form,
					'id' => $args['link_id'],
				));				
			}
			else {
				$result = new cflk_message(array(
					'success' => false,
					'link_type' => $data['type'],
					'message' => __('Could not get link type form.', 'cf-links')
				));				
			}
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'link_type' => $data['type'],
				'message' => __('Invalid Link Type in Request.', 'cf-links')
			));			
		}
		
		return $result;
	}
	
	/**
	 * Return the link's admin view state
	 *
	 * @param array $args 
	 * @return object cflk_message
	 */
	function ajax_get_link_view($args) {
		parse_str($args['form_data'], $data);
		if (!empty($data['type']) && $this->is_valid_link_type($data['type'])) {
			$link_view = $this->get_link_type($data['type'])->_admin_view($data);
			if ($link_view != false) {
				$result = new cflk_message(array(
					'success' => true,
					'link_type' => $data['type'],
					'html' => $link_view,
					'id' => (empty($args['id']) ? $this->get_random_id($data['type']) : $args['id']),
					'new_link' => (empty($args['id']) ? true : false)
				));
			}
			else {
				$result = new cflk_message(array(
					'success' => false,
					'link_type' => $data['type'],
					'message' => __('Could not get link type formatting.', 'cf-links')
				));
			}
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'link_type' => $data['type'],
				'message' => __('Invalid Link Type in Request.', 'cf-links')
			));			
		}
		return $result;
	}
	
	/**
	 * Take the incoming list name and pass it off for a unique name check
	 *
	 * @param array $args 
	 * @return void
	 */
	function ajax_check_unique_list_id($args) {
		if (!empty($args['name'])) {
			$processed = $this->check_unique_list_id($args['name']);
			$result = new cflk_message(array(
				'success' => true,
				'list_id' => $processed['list_id'],
				'list_name' => $processed['list_name'],
				'list_name_orig' => $args['name']
			));
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'message' => __('Could not process empty value.', 'cf-links')
			));
		}
		return $result;
	}
	
	/**
	 * Take a list name and process it to create a unique ID
	 * Also alters name to reflect incremeneted list ID if ID was altered to avoid conflicts
	 *
	 * @param string $name 
	 * @return array
	 */
	function check_unique_list_id($name) {
		global $wpdb;
		$list_id = $id = 'cfl-'.sanitize_title($name);
		$list_name = $name;

		$i=1;
		while(count($wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '".$list_id."'")) > 0) {
			$list_id = $id.'-'.$i;
			$list_name = $title.' '.$i;
			$i++;
		}

		return compact('list_id', 'list_name');
	}
	
	/**
	 * Generic id creator
	 * Generic ID used for admin list display to assist in editing/creating links
	 */
	function get_random_id($salt) {
		return $salt.'-'.md5(strval(rand()).$salt);
	}
	
	/**
	 * Send the Admin JS
	 *
	 * @return void
	 */
	function admin_js() {
		$js = file_get_contents(CFLK_PLUGIN_DIR.'/js/admin.js').PHP_EOL.PHP_EOL;
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/lib/cf-json/js/json2.js');
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/js/jquery.hotkeys-0.7.9.js');
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/js/jquery.DOMWindow.js');
		
		header('content-type: application/javascript');
		echo $js;
		exit;
	}
	
	/**
	 * Send the Admin CSS
	 *
	 * @return void
	 */
	function admin_css() {
		$css = file_get_contents(CFLK_PLUGIN_DIR.'/css/admin.css');
		
		header('content-type: text/css');
		echo $css;
		exit;
	}
}

?>