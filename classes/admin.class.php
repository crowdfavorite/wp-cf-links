<?php

class cflk_admin extends cflk_links {
	
	private $in_ajax = false;
	private $editing_list = false;
	private $messages = array(
		'1' => 'List Created',
		'2' => 'List Saved',
		'3' => 'List Deleted',
		'4' => 'List Imported',
		'5' => 'List Import Error'
	);
	public $allow_edit = true;
	
	function __construct() {
		parent::__construct();
		if (!empty($_GET['page']) && strpos($_GET['page'], 'cf-links') !== false) {
			// enqueue_scripts
			wp_enqueue_script('cflk-admin-js',admin_url('/index.php?page=cflk-links&cflk_action=admin_js'),array('jquery'),CFLK_PLUGIN_VERS);
			wp_enqueue_script('jquery-form');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-sortable');
			// enqueue_styles
			wp_enqueue_style('cflk-admin-css',admin_url('/index.php?page=cflk-links&cflk_action=admin_css'),array(),CFLK_PLUGIN_VERS,'all');
			// add_actions
		}
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
				case 'import_list':
					if (!empty($_POST['cflk-import-encoded'])) {
						$this->import_list(stripslashes($_POST['cflk-import-encoded']));
					}
					die();
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
						<th scope="col" style="text-align:center;" width="80px">'.__('Export', 'cf-links').'</th>
						<th scope="col" style="text-align:center;" width="80px">'.__('Edit', 'cf-links').'</th>
						<th scope="col" style="text-align:center;" width="80px">'.__('Delete', 'cf-links').'</th>
					</tr>
				</thead>
				<tbody>
				';
		if (is_array($lists) && count($lists)) {
			foreach($lists as $id => $list) {
				// Check to see if we should allow the edit buttons to display
				$this->allow_edit = apply_filters('cflk_link_edit_allow', $this->allow_edit, $id);
				
				$description = '';
				if (!empty($list['description'])) {
					$description = '<p>'.$list['description'].'</p>';
				}
				$description .= apply_filters('cflk_main_list_description', '', $id);
				
				$html .= '
						<tr id="cflk-list-'.$id.'">
							<td class="cflk-list-info">
								<p><a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$id).'" class="cflk-list-title">'.stripslashes($list['nicename']).'</a> | <span><a class="cflk-toggle" href="#cflk-details-'.$id.'">Details</a></span></p>
								<div id="cflk-details-'.$id.'" class="cflk-details" style="display: none">
									';
									if (!empty($description)) {
										$html .= '
										<div class="cflk-description">
											'.stripslashes($description).'
										</div>
										';
									}
									$html .= '
									<ul class="cflk-description-items">
										<li><span class="cflk-description-item cflk-description-item-template">Template Tag:</span> <code>&lt;?php if (function_exists(&quot;cflk_links&quot;)) { cflk_links(&quot;'.$id.'&quot;); } ?&gt;</code></li>
										<li><span class="cflk-description-item cflk-description-item-shortcode">Shortcode:</span> <code>[cflk name=&quot;'.$id.'&quot;]</code></li>
									</ul>
								</div>
							</td>
							<td class="cflk-list-count" style="text-align:center; vertical-align:middle;">
								<p>
									'.$list['count'].'
								</p>
							</td>
							<td class="cflk-list-export" style="text-align:center; vertical-align:middle;">
								';
								if ($this->allow_edit) {
									$html .= '<a  class="button cflk-export-list" id="list-export-'.$id.'" href="#">'.__('Export', 'cf-links').'</a>';
								}
								$html .= '
							</td>
							<td class="cflk-list-edit" style="text-align:center; vertical-align:middle;">
								';
								if ($this->allow_edit) {
									$html .= '<a class="button" href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$id).'">'.__('Edit', 'cf-links').'</a>';
								}
								else {
									$html .= '<a class="button" href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit&list='.$id).'">'.__('View', 'cf-links').'</a>';
								}
								$html .= '
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
					<td colspan="5">
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
		$missing_link_types = false;
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
		
		// Check to see if we should allow the edit buttons to display
		$this->allow_edit = apply_filters('cflk_link_edit_allow', $this->allow_edit, $list_id);

		// So we can support legacy data without the "Key" being set
		$default_key = '';
		if (!$new && empty($list['key'])) {
			$default_key = $list_id;
		}
						
		$listdata = array_merge(array(
				'nicename' => '',
				'key' => $default_key,
				'description' => '',
				'data' => array()
			),$list);
					
		extract($listdata);

		// list details
		$html = $this->admin_wrapper_open('Edit List').$this->admin_navigation('edit').'
			'.(!empty($notice) ? $notice : null).'
			'.$this->admin_messages().'
			';
		
		$html .= '
			<form method="post" id="cflk-list-form" name="cflk_list_form" class="cflk-list-'.($new ? 'new' : 'edit').'" action="'.$_SERVER['REQUEST_URI'].'">
				<fieldset class="lbl-pos-left">
					<div class="elm-block elm-width-300">
						<label class="lbl-text">'.__('Title', 'cf-links').'</label>
						'.($this->allow_edit ? '<input type="text" name="nicename" id="cflk_list_name" value="'.$nicename.'" class="elm-text" />' : $nicename).'
					</div>
					<div class="elm-block elm-width-300">
						<label class="lbl-text">'.__('List ID', 'cf-links').'</label>
						'.($this->allow_edit ? '<input type="text" name="key" id="cflk_list_key" value="'.$key.'" class="elm-text" readonly="readonly" />' : $key).'
					</div>
					<div class="elm-block elm-width-500">
						<label class="lbl-textarea">'.__('Description (optional)', 'cf-links').'</label>
						'.($this->allow_edit ? '<textarea name="description" id="cflk_list_description" rows="3" cols="40" class="elm-textarea">'.$description.'</textarea>' : $description).'
					</div>
					'.apply_filters('cflk_edit_list_details_edit', '', $key).'
					<div class="elm-block no-hover">
						<label class="lbl-textarea">'.__('List Items', 'cf-links').'</label> 
						<div class="sortable-wrapper">
							<ul id="'.($this->allow_edit ? 'menu-to-edit' : '').'" class="menu ui-sortable">
							';
							foreach ($data as $id => $item) {
								$level = 0;
								if (!empty($item['level'])) {
									$level = $item['level'];
								}
								// Check to see if the list key is part of the data, and add it if it isn't
								if (empty($item['list_key'])) {
									$item['list_key'] = $key;
								}

								// $id = $this->get_random_id($item['type']);							
								$html .= '<li id="menu-item-'.$id.'" class="cflk-item menu-item menu-item-depth-'.$level.' menu-item-edit-inactive">';
					
								if (!empty($this->link_types[$item['type']])) {
									$html .= $this->link_types[$item['type']]->_admin_view($item, $id);
								}
								else {
									$missing_link_types = true;
									$html .= $this->link_types['missing']->_admin_view($item, $id);
								}
								$html .= '
									<ul class="menu-item-transport"></ul>
									<div class="clear"></div>
								</li>
								';
							}
							$html .= '
							</ul>
							';
							if ($this->allow_edit) {
								$id = $this->get_random_id(time());
								$html .= '<div id="menu-item-new">';
								$html .= '
									<dl class="menu-item-bar">
										<dt class="menu-item-handle">
											<div class="item-view" style="text-align:center;">
												<a id="menu-item-new-button" class="button" href="#">Add New</a>
											</div>
										</dt>
									</dl>
								';
								$html .= $this->new_link_form();
								$html .= '</div>';
							}
							$html .= '
						</div>
					</div>
				</fieldset>
				<p class="submit">
					<input type="hidden" name="cflk_action" value="edit_list" />
					<input type="submit" name="Submit" class="button-primary" value="'.__('Update', 'cf-links').'" />
				</p>
			</form>
		';

		if ($missing_link_types) {
			$html .= '
			<script type="text/javascript">
				(function($) {
					$(function(){
						$("#cflk-list-items-missing-notification").show();
					});
				})(jQuery);
			</script>
			';
		}
		
		$html .= $this->admin_wrapper_close();
		return $html;
	}
	
	/**
	 * Build the universal edit form list
	 *
	 * @param bool $hidden 
	 * @return string html
	 */
	function new_link_form() {
		$options = '';
		$forms = '';
		$title_field = '';
		$custom_class_field = '';
		$new_window_fields = '';
		$i = 0;
		if (is_array($this->link_types) && !empty($this->link_types)) {
			foreach ($this->link_types as $id => $type) {
				if ($id == 'missing') { continue; }
				$options .= '<option value="'.esc_attr($id).'">'.esc_html($type->name).'</option>';
				$forms .= '<li id="cflk-type-'.esc_attr($id).'"'.($i > 0 ? ' style="display:none;"' : null).'>'.$type->admin_form('', 'new').'</li>';
				
				if ($i == 0) {
					$title_field = $type->title_field('', 'new');
					$custom_class_field = $type->custom_class_field('', 'new');
					$new_window_field = $type->new_window_field('', 'new');
				}
				
				$i++;
			}
		}
		
		return '
		<div id="new-item-edit" class="item-edit cflk-edit-link-form cflk-new-edit-form">
			<div class="elm-block elm-width-200">
				<label for="new-type-selector">'.__('Link Type', 'cf-links').'</label>
				<select class="elm-select" id="new-type-selector" name="type">'.$options.'</option></select>
			</div>
			<ul class="cflk-edit-forms" style="margin-left:0;">
				'.$forms.'
			</ul>
			'.$title_field.'
			'.$custom_class_field.'
			'.$new_window_field.'
			<div class="edit-actions">
				<a href="#" class="edit-done button">'.__('Done', 'cf-links').'</a>
				<a href="#" class="edit-remove lnk-remove">'.__('Cancel', 'cf-links').'</a>				
			</div>
			<input type="hidden" id="new-level" name="level" value="0" />
		</div>
		';
	}
	
	function edit_forms2() {
		$i = 0;
		
		foreach ($this->link_types as $id => $type) {
			if ($id == 'missing') { continue; }
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
					'.$type->_admin_edit_form(array('list_key' => $this->editing_list), false, true).'
					<input type="hidden" name="type" value="'.esc_attr($id).'" />
				</li>
				';
			if ($i == 0) {
				$opennew .= $type->new_window_field();
			}
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
					<div class="cflk-type-opennew">
						'.$opennew.'
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
			<form method="post" id="cflk-import-form" name="cflk_import_form" class="cflk-import" action="'.$_SERVER['REQUEST_URI'].'">
				<table id="cflk-import" class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Input the data here to be imported.  This data should be copied from an export of a CF Links List.', 'cf-links').'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<textarea name="cflk-import-encoded" id="cflk-import-encoded" class="cflk-import-encoded widefat" rows="10"></textarea>						
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input id="cflk-import-submit" type="submit" class="button-primary" value="'.__('Import Data','cf-links').'" />
					<input type="hidden" name="cflk_action" value="import_list" />
				</p>
			</form>
			'.apply_filters('cflk_admin_import_after','').$this->admin_wrapper_close();
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
				$html .= '<li><a href="#" class="cflk-list-link" rel="'.esc_attr($id).'">'.stripslashes($list['nicename']).'</a></li>';
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
			'custom-class' => '',
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
				$data = cf_ajax_decode_json($link['data'], true);
				if (!empty($link['level'])) {
					$data['level'] = $link['level'];
				}
				else {
					$data['level'] = 0;
				}
				if ($this->is_valid_link_type($data['type'])) {
					$linkdata = $this->get_link_type($data['type'])->_update($data);
					if (empty($linkdata)) {
						$this->errors->add('list-data', 'Error processing link type data');
					}
					else {
						$list['data'][] = $linkdata;
					}
				}
				else {
					// If we don't have a valid link type, using the missing link type to keep the data intact until
					// the link type is valid in the system
					$linkdata = $this->get_link_type('missing')->_update($link);
					if (empty($linkdata)) {
						$this->errors->add('list-data', 'Error processing link type data');
					}
					else {
						$list['data'][] = $linkdata;
					}
				}
			}
		}
		
		$list = apply_filters('cflk_process_list', $list, $_POST);

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
		do_action('cflk_save_list', $list['key']);
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
		do_action('cflk_delete_list', $list_key);
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

	function import_list($data) {
		$data = json_decode($data, true);
		
		if (get_option($data['key'])) {
			$processed = $this->check_unique_list_id($data['key']);
			$data['key'] = $processed['list_id'];
			$data['list_key'] = $processed['list_id'];
		}
		
		if ($this->save_list_data($data)) {
			wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=edit&list='.$data['key'].'&cflk_message=4'));
			exit;
		}
		wp_redirect(admin_url('options-general.php?page=cf-links&cflk_page=import&cflk_message=5'));
		exit;
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
		$this->messages = apply_filters('cflk_admin_messages', $this->messages);
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
			<div class="wrap">
				<h2>'.screen_icon().__('CF Links'.$title, 'cf-links').'</h2>
				<div id="cf">
			';
	}
	
	function admin_wrapper_close() {
		return '</div></div>';
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
			<div id="cf-header">
				<ul id="cf-nav">
					<li>
						<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME).'" class="cflk-main'.$main_class.'">'.__('Lists', 'cf-links').'</a>
					</li>
					<li>
						<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=edit').'" class="cflk-new-list'.$new_list_class.'">'.__('Add New List', 'cf-links').'</a>
					</li>
					<li>
						<a href="'.admin_url('options-general.php?page='.CFLK_BASENAME.'&cflk_page=import').'" class="cflk-import-list'.$import_list_class.'">'.__('Import List', 'cf-links').'</a>
					</li>
					<li>
						<a href="'.admin_url('widgets.php').'" class="cflk-widget-link">'.__('Edit Widgets', 'cf-links').'</a>
					</li>
				</ul>
			</div><!-- #cf-options-tabs -->
			<div class="clear"></div>
		';
	}
	
# Ajax Accessors

	function ajax_handler() {
		$this->in_ajax = true;
		
		$method = 'ajax_'.strval($_POST['func']);
		if (method_exists($this, $method)) {
			$args = cf_ajax_decode_json(stripslashes($_POST['args']), true);
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
		parse_str(stripslashes($args['form_data']), $data);
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
		$sanitized_name = sanitize_title($name);
		
		// Do a check to make sure that "cfl" is the first part of the key
		$check = explode('-', $sanitized_name, 2);
		if ($check[0] != 'cfl') {
			$sanitized_name = sanitize_title('cfl-'.$sanitized_name);
		}
		
		$list_id = $id = $sanitized_name;
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
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/js/nav-menu.js').PHP_EOL.PHP_EOL;
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/lib/cf-json/js/json2.js');
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/js/jquery.hotkeys-0.7.9.js');
		$js .= file_get_contents(CFLK_PLUGIN_DIR.'/js/jquery.DOMWindow.js');
		
		// Get the Link Types Admin JS
		if (is_array($this->link_types) && !empty($this->link_types)) {
			$js .= '
// Link type Admin JS

;(function($) {
';
			foreach ($this->link_types as $key => $type) {
				if (method_exists($this->link_types[$key], 'admin_js')) {
					$js .= $this->link_types[$key]->admin_js();
				}
			}
			$js .= '
})(jQuery);
';
		}
		
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
		// $css .= file_get_contents(CFLK_PLUGIN_DIR.'/css/nav-menu.css');
		$css .= file_get_contents(CFLK_PLUGIN_DIR.'/css/form-elements.css');
		$css .= file_get_contents(CFLK_PLUGIN_DIR.'/css/styles.css');
		$css .= file_get_contents(CFLK_PLUGIN_DIR.'/css/utility.css');
		
		// Get the Link Types Admin CSS
		if (is_array($this->link_types) && !empty($this->link_types)) {
			$css .= '
/* Link Type Admin CSS */ 
			
';
			foreach ($this->link_types as $key => $type) {
				if (method_exists($this->link_types[$key], 'admin_css')) {
					$css .= $this->link_types[$key]->admin_css();
				}
			}
		}
		
		header('content-type: text/css');
		echo $css;
		exit;
	}
}

?>