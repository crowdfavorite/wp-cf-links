<?php

class cflk_admin extends cflk_links {
	
	private $in_ajax = false;
	
	function __construct() {
		parent::__construct();
		// enqueue_scripts
		wp_enqueue_script('cflk-admin-css',admin_url('/index.php?page=cflk-links&cflk_action=admin_js'),array('jquery'),CFLK_PLUGIN_VERS);
		// enqueue_styles
		wp_enqueue_style('cflk-admin-js',admin_url('/index.php?page=cflk-links&cflk_action=admin_css'),array(),CFLK_PLUGIN_VERS,'all');
		// add_actions
		add_action('init', array($this,'admin_request_handler'));
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
				case 'edit':
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
		$method = !empty($_GET['cflk_page']) ? '_'.strval($_GET['cflk_page']) : '_main'; // cflk_page is legacy var name for compat
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
		
		// $lists = array(
		// 	'fake-key' => array(
		// 		'nicename' => 'Fake List',
		// 		'description' => 'This is the Fake List description',
		// 		'count' => 3
		// 	)
		// );
		
		// show list of available lists
		$html = $this->admin_wrapper_open('CF Links').'
			<p>
				<!-- <input type="button" class="button-primary" name="cflk-add-new-list" id="cflk-add-new-list" value="'.__('Add New List', 'cf-links').'" />&nbsp;|&nbsp;-->
				<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit').'" class="button-primary cflk-new-list">'.__('Add New List', 'cf-links').'</a>
				&nbsp;|&nbsp;
				<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=import').'" class="cflk-import-list">'.__('Import List', 'cf-links').'</a>
			</p>
			<table id="cflk-available-lists" class="widefat">
				<thead>
					<tr>
						<th scope="col">'.__('Available Lists', 'cf-links').'</th>
						<th scope="col" style="text-align: center;" width="120px">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				';
		if (is_array($lists) && count($lists)) {
			foreach($lists as $id => $list) {
				$html .= '
						<tr id="cflk-list-'.$id.'">
							<td class="cflk-list-info">
								<p><a href="#" class="cflk-list-title">'.$list['nicename'].'</a> | <span>'.$list['count'].' link'.($list['count'] == 1 ? '' : 's').'</span></p>
								<div id="cflk-details-'.$id.'" class="cflk-details" style="display: none">
									<p>'.$list['description'].'</p>
									<ul>
										<li><b>Template Tag</b>: <code>&lt;?php if (function_exists(&quot;cflk_links&quot;)) { cflk_links(&quot;'.$id.'&quot;); } ?&gt;</code></li>
										<li><b>Shortcode</b>: <code>[cflk_links name=&quot;'.$id.'&quot;]</code></li>
									</ul>
								</div>
							</td>
							<td class="cflk-list-actions">
								<p class="submit cflk-showhide">
									<a class="cflk-toggle" href="#cflk-details-'.$id.'">Details</a>&nbsp;|&nbsp;
									<input type="button" class="button-secondary edit" name="link-edit-'.$id.'" value="'.__('Edit', 'cf-links').'" />
								</p>
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
		$list_id = !empty($_GET['list']) ? esc_attr($_GET['list']) : false;
		$list = array();
		
		$new = $list_id == false ? true : false;
		
		if ($list_id) {
			$list = get_option($list_id,array());
		}
		
		$listdata = array_merge(array(
				'nicename' => '',
				'description' => '',
				'data' => array()
			),$list);
		
		// list details
		$html = $this->admin_wrapper_open('Edit List :: CF Links').'
			<form method="post" id="cflk-list-form" class="cflk-list-'.($new ? 'new' : 'edit').'">
				<h3>'.__('List Details','cf-links').'</h3>
				<fieldset id="cflk-edit-list-details">
					<div id="cflk-edit-list-details-display">
						<h4 class="cflk-list-name">'.$nicename.'</h4>
						<p '.($new ? ' style="display: none;"' : '').'>ID: <span class="cflk-list-slug">'.($new ? '' : $list_id).'</span></p>
						<p class="cflk-list-description">'.$description.'</p>
					</div>
					<div id="cflk-edit-list-details-edit">
						<p class="cflk-edit-list-name">
							<label for="cflk-list-name">'.__('List Name', 'cf-links').':</label>
							<input type="text" name="cflk-list-name" id="cflk-list-name" value="'.$nicename.'" />
						</p>
						<p class="cflk-edit-list-id">
							<label for="cflk-list-id">'.__('List ID', 'cf-links').':</label>
							<input type="text" name="cflk-list-id" id="cflk-list-id" value="'.$list_id.'" />
						</p>
						<p class="cflk-edit-list-description">
							<label for="cflk-list-description">'.__('Description (optional)', 'cf-links').'</label>
							<textarea name="cflk-list-description" id="cflk-list-description">'.$description.'</textarea>
						</p>
					</div>
				</fieldset>
			';
		
		// Links list 
		$html .= '
				<h3>'.__('List Items','cf-links').'</h3>
				<fieldset id="cflk-list-items">
					<ul id="cflk-list-sortable">
				';

		if (!is_array($data) || !count($data)) {
			$html .= '<li class="cflk-no-items"><p>There are no items in this list. Use the "Add Link" button to get started</p></li>';
		}
		else {
			foreach ($data as $item) {
				$html .= '
					<li class="cflk-item">'.$this->link_types[$item['type']]->_admin($item).'</li>
					';
			}
		}
		
		$html .= '
					</ul>
				</fieldset>
				<div id="cflk-list-items-footer">
					'.$this->edit_forms(true).'
					<div>
						<input type="button" class="button-secondary" name="cflk-new-list-item" id="cflk-new-list-item" value="'.__('Add Link','cf-links').'" />
					</div>
				</div>
			';
		
		// Submit
		$button_text = $new ? 'Save List' : 'Update List';
		$html .= '
				<p class="submit">
					<input type="submit" class="button-primary" name="cflk-submit" value="'.__($button_text,'cf-links').'" />
				</p>
			</form>
			'.$this->admin_wrapper_close();
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
			$forms .= '
				<li id="cflk-type-'.esc_attr($id).'"'.($i > 0 ? ' style="display: none;"' : null).'>
					'.$type->admin_form(array()).'
					<input type="hidden" name="link_type" value="'.esc_attr($id).'" />
				</li>
				';
			$i++;
		}
				
		return '
			<div id="cflk-edit-forms-wrapper" style="display: none;">
				<div class="cflk-edit-forms">
					<div class="cflk-type-select">
						<select name="cflk-types">
							'.$options.'
						</select>
					</div>
					<div class="cflk-type-forms">
						<ul>
							'.$forms.'
						</ul>
					</div>
					<div class="cflk-type-actions">
						<button class="button cflk-link-edit-done">Done</button>
						<a href="#" class="cflk-cancel">cancel</a>
					</div>
				</div>
			</div>
			';
	}
	
	function display_blocks() {
		$html = '
			<div id="cflk-display-blocks" style="display: none;">
			';
		foreach($this->link_types as $type) {
			$html .= $type->_admin(array());
		}	
		$html .= '	
			</div>
		';
		return $html;
	}
	
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
		$html = $this->admin_wrapper_open('Import List :: CF Links').'
			<form method="post" id="cflk-import-form">
				
			</form>
			'.$this->admin_wrapper_close();
		return $html;
	}
	
# functionality
	function create_list() {
		$this->save_list($new_list);
	}
	
	function save_list($listdata) {
		foreach ($listdata as $item) {
			$item->update();
		}
	}
	
	function export_list() {}
	function import_list() {}
	function get_authors() {}
	
	function get_all_lists_for_blog($blog = 0) {
		global $wpdb, $blog_id;

		// if we're on MU and another blog's details have been requested, change the options table assignment
		if (!is_null($blog_id) && $blog != 0) {
			$options = 'wp_'.$blog.'_options';
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
				);
			}
		}
		return apply_filters('cflk_get_all_lists_for_blog', count($return) > 0 ? $return : false);
	}
		
# Display Helpers
	function admin_wrapper_open($title="CF Links") {
		return '
			<div id="cflk-wrap" class="wrap">
				<h2>'.__($title,'cf-links').'</h2>
			';
	}
	
	function admin_wrapper_close() {
		return '</div>';
	}
	
# Ajax Accessors
	function ajax_handler() {
		$this->in_ajax = true;
		
		$method = 'ajax_'.strval($_POST['func']);

		if (method_exists($this, $method)) {
			$args = cf_ajax_decode_json($_POST['args'], true);
			$result = $this->$method($args);
			if (!($result instanceof cflk_message)) {
				// build error message
			}
		}
		else {
			// build error message
		}
		$result->send();
	}
	
	function ajax_export_list($args) {
		
	}
	
	function ajax_delete_list($args) {
		
	}
	
	function ajax_autocomplete($args) {
		
	}
	
	function ajax_get_link_view($args) {
		parse_str($args['form_data'], $data);
		if (!empty($data['link_type']) && $this->is_valid_link_type($data['link_type'])) {
			$link_view = $this->get_link_type($data['link_type'])->_admin($data);
			if ($link_view != false) {
				$result = new cflk_message(array(
					'success' => true,
					'html' => $link_view
				));
			}
			else {
				$result = new cflk_message(array(
					'success' => false,
					'message' => 'Could not get link type formatting'
				));
			}
		}
		else {
			$result = new cflk_message(array(
				'success' => false,
				'message' => 'Invalid Link Type in Request'
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
				'message' => 'Could not process empty value'
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
	 * Send the Admin JS
	 *
	 * @return void
	 */
	function admin_js() {
		$js = file_get_contents(CFLK_PLUGIN_DIR.'/js/admin.js').PHP_EOL.PHP_EOL;
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/lib/cf-json/js/json2.js');
		
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