<?php
/*
Plugin Name: CF Links
Plugin URI: 
Description: Advanced options for adding links
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

/*
// below is a usage example on applying a filter to the cf_links_widget_data

/**
 * Trim the links list in the widget if its a certain page
 * 
 * @param $html - the html built by the links_widget_data function
 * @param $links - the array of links data built by links_widget_data function
 * @param $args - the supporting arguments used to build the list 
 * 		$args contains: 'link_key' - the links list keyname 
 *						'key' - ?
 * 						'length' - ?
 *						'type' -> 'widget'  
 * / //<!-- there's a space here to keep the commenting correct, remove the space if you copy this
function my_links_filter($html,$links,$args) {
	if($args['link_key'] == 'cfl-main-nav-for-sidebar') {
		// see if our link to be filtered is present
		$filtered = false;
		foreach($links['data'] as $key => $link) {
			if($link['type'] == 'page' && $link['link'] == 10) { 
				unset($links['data'][$key]); 
				$filtered = true;
			}
		}
		// if we acted on the array then rebuild the list
		if($filtered) {
			$html = links_build_ul($links,$args);
		}
	}
	return $html;
}
add_filter('cflk_widget_data','my_links_filter',10,3); // applies filter only to widget output
add_filter('cflk_template_data','my_links_filter',10,3); // applies filter only to template tag output

*/

// ini_set('display_errors', '1');
// ini_set('error_reporting', E_ALL);

/**
 * 
 * WP Admin Handling Functions
 * 
 */

load_plugin_textdomain('cf-links');
$cflk_types = array();

function cflk_link_types() {
	global $wpdb, $cflk_types;
	
	$pages = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='page' ORDER BY post_title ASC");
	$categories = $wpdb->get_results("SELECT $wpdb->terms.name, $wpdb->terms.term_id FROM $wpdb->term_taxonomy left join $wpdb->terms on $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id where $wpdb->term_taxonomy.taxonomy = 'category'");
	$authors = get_users_of_blog($wpdb->blog_id);
	
	$page_type = array();
	$category_type = array();
	$author_type = array();
	$wordpress_type = array();
	
	foreach($pages as $page) {
		$type = array(sanitize_title($page->post_title) => array('link' => $page->ID, 'description' => $page->post_title));
		$page_type = array_merge($page_type, $type);
	}

	foreach($categories as $category) {
		$type = array(sanitize_title($category->name) => array('link' => $category->term_id, 'description' => $category->name, 'count' => $category->count));
		$category_type = array_merge($category_type, $type);
	}

	foreach($authors as $author) {
		$type = array(sanitize_title($author->display_name) => array('link' => $author->user_id, 'description' => $author->display_name));
		$author_type = array_merge($author_type, $type);
	}	
	
	$wordpress_type = array_merge($wordpress_type, array(sanitize_title('home') => array('link' => 'home', 'description' => __('Home','cf-links'))));
	$wordpress_type = array_merge($wordpress_type, array(sanitize_title('loginout') => array('link' => 'loginout', 'description' => __('Log In/Out','cf-links'))));
	$wordpress_type = array_merge($wordpress_type, array(sanitize_title('register') => array('link' => 'register', 'description' => __('Register/Site Admin','cf-links'))));
	$wordpress_type = array_merge($wordpress_type, array(sanitize_title('profile') => array('link' => 'profile', 'description' => __('Profile','cf-links'))));
	$wordpress_type = array_merge($wordpress_type, array(sanitize_title('main_rss') => array('link' => 'main_rss', 'description' => __('Site RSS','cf-links'))));
	
	$cflk_types = array_merge($cflk_types, array('page' => $page_type));
	$cflk_types = array_merge($cflk_types, array('category' => $category_type));
	$cflk_types = array_merge($cflk_types, array('author' => $author_type));
	$cflk_types = array_merge($cflk_types, array('wordpress' => $wordpress_type));
	
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
	if(isset($_GET['cflk_page'])) {
		$check_page = $_GET['cflk_page'];
	}
	switch($check_page) {
		case 'main':
			cflk_options_form();
			break;
		case 'edit':
			cflk_edit();
			break;
		case 'create':
			cflk_new();
			break;
		default:
			cflk_options_form();
			break;
	}
}

function cflk_request_handler() {
	if(current_user_can('manage_options')) {
		if(isset($_POST['cf_action']) && $_POST['cf_action'] != '') {
			switch($_POST['cf_action']) {
				case 'cflk_update_settings':
					if(isset($_POST['cflk'])) {
						$link_data = stripslashes_deep($_POST['cflk']);
						if(isset($_POST['cflk_key']) && $_POST['cflk_key'] != '' && isset($_POST['cflk_nicename']) && $_POST['cflk_nicename'] != '') {
							cflk_process($link_data, $_POST['cflk_key'], $_POST['cflk_nicename']);
						}
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$_POST['cflk_key'].'&cflk_message=updated');
					}
					break;
				case 'cflk_delete':
					if(isset($_POST['cflk_key']) && $_POST['cflk_key'] != '') {
						cflk_delete($_POST['cflk_key']);
					}
					break;
				case 'cflk_delete_key':
					if(isset($_POST['cflk_key']) && isset($_POST['key']) && $_POST['cflk_key'] != '' && $_POST['key'] != '') {
						cflk_delete_key($_POST['cflk_key'], $_POST['key']);
					}
					break;
				case 'cflk_insert_new':
					if(isset($_POST['cflk_nicename']) && $_POST['cflk_nicename'] != '') {
						$cflk_key = cflk_insert_new($_POST['cflk_nicename']);
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$cflk_key);
					}
					break;
				case 'cflk_edit_nicename':
					if(isset($_POST['cflk_nicename']) && $_POST['cflk_nicename'] != '' && isset($_POST['cflk_key']) && $_POST['cflk_key'] != '') {
						cflk_edit_nicename($_POST['cflk_key'], $_POST['cflk_nicename']);
					}
					break;
				default:
					break;
			}
		}
	}
	if(!empty($_GET['cflk_page'])) {
		switch($_GET['cflk_page']) {
			case 'cflk_admin_js':
				cflk_admin_js();
				break;
			case 'cflk_admin_css':
				cflk_admin_css();
				break;
			case 'dialog':
				cflk_dialog();
				break;
			default:
				break;
		}
	}
}
add_action('init', 'cflk_request_handler');
add_action('wp_ajax_cflk_update_settings', 'cflk_request_handler');

wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui', get_bloginfo('url').'/wp-content/plugins/cf-links/js/jquery-ui.js', 'jquery');
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

function cflk_ajax() {
	wp_print_scripts(array('sack'));
	?>
	<script type="text/javascript">
		//<![CDATA[
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
	#cflk -list { list-style: none; padding: 0; margin: 0; }
	#cflk-list li { margin: 0; padding: 0; }
	#cflk-list .handle { cursor: move; }
	#cflk-log { padding: 5px; border: 1px solid #ccc; }
	.cflk-info { list-style: none; }
	.cflk_button {
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
	.cflk_button:hover {
		color: #D54E21;
		border-color: #535353;
	}
	.cflk_edit_link {
		font-size: 12px;
	}
	.cflk-codebox {
		padding: 10px;
		background-color: #E4F2FD;
		border: 1px solid #C6D9E9;
	}
	<?php
	die();
}

function cflk_admin_js() {
	global $wpdb;
	
	$pages = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='page' ORDER BY post_title ASC");
	$categories = $wpdb->get_results("SELECT $wpdb->terms.name, $wpdb->terms.term_id FROM $wpdb->term_taxonomy left join $wpdb->terms on $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id where $wpdb->term_taxonomy.taxonomy = 'category'");
	$authors = get_users_of_blog($wpdb->blog_id);
	$page_select = '';
	$category_select = '';
	$wordpress_select = '';
	$author_rss_select = '';
	
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
	var cflkMainURL = '<?php bloginfo('url'); ?>';
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
	});
	function deleteLink(cflk_key,linkID) {
		if(confirm('Are you sure you want to delete this?')) {
			if(cflkAJAXDeleteLink(cflk_key,linkID)) {
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
	function deleteMain(cflk_key) {
		if(confirm('Are you sure you want to delete this?')) {
			if(cflkAJAXDeleteMain(cflk_key)) {
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
		if(cflkAJAXSaveNicename(cflk_key, jQuery("#cflk_nicename_new").val())) {
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
	function cancelTitle(key) {
		jQuery('#cflk_'+key+'_title_input').attr('style','display: none;');
		jQuery('#cflk_'+key+'_title_edit').attr('style','');
	}
	function showLinkType(key) {
		var type = jQuery("#cflk_"+key+"_type").val();
		if(type == "url") {
			jQuery("#url_"+key).attr("style", "");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "rss") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "page") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "category") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "");
			jQuery("#wordpress_"+key).attr("style", "display: none;");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "wordpress") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
			jQuery("#page_"+key).attr("style", "display: none;");													
			jQuery("#category_"+key).attr("style", "display: none;");
			jQuery("#wordpress_"+key).attr("style", "");
			jQuery("#author_rss_"+key).attr("style", "display: none;");
		}
		if(type == "author_rss") {
			jQuery("#url_"+key).attr("style", "display: none;");
			jQuery("#rss_"+key).attr("style", "display: none;");
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
									<img src="'+cflkMainURL+'/wp-content/plugins/cf-links/images/arrow_up_down.png" class="handle" alt="move" />\
								</td>\
								<td width="90px">\
									<select name="cflk[###SECTION###][type]" id="cflk_###SECTION###_type" onChange="showLinkType(###SECTION###)">\
										<option value="url">URL</option>\
										<option value="rss">RSS Feed</option>\
										<option value="page">Page</option>\
										<option value="category">Category</option>\
										<option value="wordpress">WordPress</option>\
										<option value="author_rss">Author RSS</option>\
									</select>\
								</td>\
								<td>\
									<span id="url_###SECTION###">\
										<input type="text" name="cflk[###SECTION###][link]" id="cflk_###SECTION###_link" size="50" value="" />\
										<br />\
										<?php _e('ex: http://example.com', 'cf-links'); ?>\
									</span>\
									<span id="rss_###SECTION###" style="display: none;">\
										<input type="text" name="cflk[###SECTION###][rss]" id="cflk_###SECTION###_rss" size="50" value="" />\
										<br />\
										<?php _e('ex: http://example.com/feed', 'cf-links'); ?>\
									</span>\
									<span id="page_###SECTION###" style="display: none;">\
										<select name="cflk[###SECTION###][page]" id="cflk_###SECTION###_page" style="width: 410px;">\
											<?php echo $page_select; ?>\
										</select>\
									</span>\
									<span id="category_###SECTION###" style="display: none;">\
										<select name="cflk[###SECTION###][category]" id="cflk_###SECTION###_category" style="width: 410px;">\
											<?php echo $category_select; ?>\
										</select>\
										<br />\
										<?php _e('Display category post count: ','cf-links'); ?> <input type="checkbox" name="cflk[###SECTION###][category_posts]" id="cflk_###SECTION###_category" class="widefat"/>\
									</span>\
									<span id="wordpress_###SECTION###" style="display: none;">\
										<select name="cflk[###SECTION###][wordpress]" id="cflk_###SECTION###_wordpress" style="width: 410px;">\
											<?php echo $wordpress_select; ?>\
										</select>\
									</span>\
									<span id="author_rss_###SECTION###" style="display: none;">\
										<select name="cflk[###SECTION###][author_rss]" id="cflk_###SECTION###_author_rss" style="width: 410px;">\
											<?php echo $author_rss_select; ?>\
										</select>\
									</span>\
								</td>\
								<td width="250px">\
									<span id="cflk_###SECTION###_title_edit">\
										<input type="button" class="cflk_button" id="link_edit_title_###SECTION###" value="<?php _e('Edit Title', 'cf-links') ?>" onClick="editTitle(###SECTION###)" />\
									</span>\
									<span id="cflk_###SECTION###_title_input" style="display: none">\
										<input type="text" name="cflk[###SECTION###][title]" value="" style="max-width: 195px;" />\
										<input type="button" class="cflk_button" id="link_cancel_title_###SECTION###" value="<?php _e('Cancel', 'cf-links') ?>" onClick="cancelTitle(###SECTION###)" />\
									</span>\
								<td width="60px" style="text-align: center;">\
									<input type="button" class="cflk_button" id="link_delete_###SECTION###" value="<?php _e('Delete', 'cf-links'); ?>" onClick="deleteCreated(###SECTION###)" />\
								</td>\
							</tr>\
						</table>\
					</li>';		
		html = html.replace(/###SECTION###/g, section);
		jQuery('#cflk-list').append(html);
	}
<?php
	die();
}

function cflk_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'index.php?cflk_page=cflk_admin_css" />';
	echo '<script type="text/javascript" src="'.trailingslashit(get_bloginfo('url')).'index.php?cflk_page=cflk_admin_js"></script>';
	echo '<link rel="stylesheet" href="'.trailingslashit(get_bloginfo('url')).'/wp-includes/js/thickbox/thickbox.css" type="text/css" media="screen" />';
	
}
if(isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	add_action('admin_head', 'cflk_admin_head');
}

/**
 * 
 * CF Links Admin Interface Functions
 * 
 */

function cflk_options_form() {
	global $wpdb;
	
	$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	$form_data = array();
	foreach($cflk_list as $cflk) {
		$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
		$push = array('option_name' => $cflk->option_name, 'nicename' => $options['nicename'], 'count' => count($options['data']));
		array_push($form_data,$push);
	}
	if ( isset($_GET['cflk_message']) ) {
		switch($_GET['cflk_message']) {
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
			'.cflk_head('main').'
			<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="cflk-form">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Links List', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="80px">'.__('Links Count', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Edit', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Export', 'cf-links').'</th>
							<th scope="col" style="text-align: center;" width="60px">'.__('Delete', 'cf-links').'</th>
						</tr>
					</thead>
				</table>
				<ul id="cflk-list">');
					if(count($form_data) > 0) {
						foreach($form_data as $data => $info) {
							print('<li id="link_main_'.$info['option_name'].'">
								<table class="widefat">
									<tr>
										<td style="vertical-align: middle;">
											<a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=edit&link='.$info['option_name'].'" style="font-weight: bold; font-size: 20px;">'.$info['nicename'].'</a>
											<br />
											'.__('Show: ','cf-links').'<a href="#" onClick="showLinkCode(\''.$info['option_name'].'-TemplateTag\')">'.__('Template Tag','cf-links').'</a> | <a href="#" onClick="showLinkCode(\''.$info['option_name'].'-ShortCode\')">'.__('Shortcode','cf-links').'</a>
											<div id="'.$info['option_name'].'-TemplateTag" class="cflk-codebox" style="display:none;">
												<div style="float: left;"><code>'.htmlentities('<?php if(function_exists("cflk_template")) { cflk_template("'.$info['option_name'].'"); } ?>').'</code></div><div style="float: right;"><a href="#" onClick="showLinkCode(\''.$info['option_name'].'-TemplateTag\')">'.__('Hide','cf-links').'</a></div><div class="clear"></div>
											</div>
											<div id="'.$info['option_name'].'-ShortCode" class="cflk-codebox" style="display:none;">
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
												<input type="button" name="link_export" value="'.__('Export', 'cf-links').'" class="button-secondary export thickbox" title="'.__('Export Links List','cf-links').'" rel="'.$info['option_name'].'" alt="#TB_inline?height=300&width=400&inlineId=content-'.$data.'" />
											</p>
										</td>
										<td style="text-align: center; vertical-align: middle;" width="60px">
											<p class="submit" style="border-top: none; padding: 0; margin: 0;">
												<input type="button" id="link_delete_'.$info['option_name'].'" onclick="deleteMain(\''.$info['option_name'].'\')" value="'.__('Delete', 'cf-links').'" />
											</p>
										</td>
									</tr>
								</table>
								<div style="display:none;" id="content-'.$data.'">
									<p><pre>');
									print_r(cflk_export_list($info['option_name']));
									print('</pre></p>
								</div>
							</li>');
						}
					}
				print('
				</ul>
			</form>
		</div>
	');
}

function cflk_new() {
	print('
		<div class="wrap">
			'.cflk_head('create').'
			<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="cflk-create">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col">'.__('Link List Name', 'cf-links').'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><input type="text" name="cflk_nicename" size="55" />
						</tr>
					</tbody>
				</table>
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="cf_action" value="cflk_insert_new" />
					<input type="submit" name="submit" id="cflk-submit" value="'.__('Create New Link List', 'cf-links').'" />
				</p>
			</form>
		</div>
	');
}

function cflk_edit() {
	global $wpdb, $cflk_types;
	
	print('<pre>');
	print_r($cflk_types);
	print('</pre>');
	
	if(isset($_GET['link']) && $_GET['link'] != '') {
		$cflk_key = $_GET['link'];
		$cflk = maybe_unserialize(get_option($cflk_key));
		is_array($cflk) ? $cflk_count = count($cflk) : $cflk_count = 0;
		
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
		
		if ( isset($_GET['cflk_message']) && $_GET['cflk_message'] = 'updated' ) {
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
				<form action="'.get_bloginfo('url').'/wp-admin/options-general.php" method="post" id="cflk-form">
					'.cflk_head('edit', htmlspecialchars($cflk['nicename']), $cflk_key).'
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
					<ul id="cflk-list">');
						if($cflk_count > 0) {
							foreach($cflk['data'] as $key => $setting) {
								$select_settings = cflk_edit_select($setting['type']);
								print('<li id="listitem_'.$key.'">
									<table class="widefat">
										<tr>
											<td width="40px" style="text-align: center;"><img src="'.get_bloginfo('url').'/wp-content/plugins/cf-links/images/arrow_up_down.png" class="handle" alt="move" /></td>
											<td width="90px">
												<select name="cflk['.$key.'][type]" id="cflk_'.$key.'_type" onChange="showLinkType('.$key.')">
													<option value="url" '.$select_settings[url_select].'>'.__('URL','cf-links').'</option>
													<option value="rss" '.$select_settings[rss_select].'>'.__('RSS Feed','cf-links').'</option>
													<option value="page" '.$select_settings[page_select].'>'.__('Page','cf-links').'</option>
													<option value="category" '.$select_settings[category_select].'>'.__('Category','cf-links').'</option>
													<option value="wordpress" '.$select_settings[wordpress_select].'>'.__('WordPress','cf-links').'</option>
													<option value="author_rss" '.$select_settings[author_rss_select].'>'.__('Author RSS','cf-links').'</option>
												</select>
											</td>
											<td>
												<span id="url_'.$key.'" '.$select_settings[url_show].'>
													<input type="text" name="cflk['.$key.'][link]" id="cflk_'.$key.'_link" size="50" value="'.htmlspecialchars($setting['link']).'" />
													<br />
													'.__('ex: http://example.com', 'cf-links').'
												</span>
												<span id="rss_'.$key.'" '.$select_settings[rss_show].'>
													<input type="text" name="cflk['.$key.'][rss]" id="cflk'.$key.'_rss" size="50" value="'.htmlspecialchars($setting['link']).'" />
													<br />
													'.__('ex: http://example.com/feed', 'cf-links').'
													
												</span>
												<span id="page_'.$key.'" '.$select_settings[page_show].'>
													<select name="cflk['.$key.'][page]" id="cflk_'.$key.'_page" style="width: 410px;">');
													foreach($cflk_types['page'] as $page) {
														if($setting['link'] == $page['link']) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$page['link'].'" '.$selected.'>'.$page['description'].'</option>');
													}
													print('
													</select>
												</span>
												<span id="category_'.$key.'" '.$select_settings[category_show].'>
													<select name="cflk['.$key.'][category]" id="cflk_'.$key.'_category" style="width: 410px;">');
													foreach($cflk_types['category'] as $category) {
														if($setting['link'] == $category['link']) {
															$selected = 'selected=selected';
														} else {
															$selected = '';
														}
														print('<option value="'.$category['link'].'" '.$selected.'>'.$category['description']);
														if($setting['cat_posts']) {
															print(' ('.$category->count.')');
														}
														print('</option>');
													}
													print('
													</select>
													<br />
													');
													if($setting['cat_posts']) {
														$cat_posts_selected = 'checked=checked';
													}
													else {
														$cat_posts_selected = '';
													}
													print( __('Display category post count: ','cf-links').' <input type="checkbox" '.$cat_posts_selected.' name="cflk['.$key.'][category_posts]" id="cflk_'.$key.'_category" class="widefat"/>
												</span>
												<span id="wordpress_'.$key.'" '.$select_settings[wordpress_show].'>
													<select name="cflk['.$key.'][wordpress]" id="cflk_'.$key.'_wordpress" style="width: 410px;">');
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
													<select name="cflk['.$key.'][author_rss]" id="cflk_'.$key.'_author_rss" style="width: 410px;">');
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
													<span id="cflk_'.$key.'_title_edit">
														<input type="button" class="cflk_button" id="link_edit_title_'.$key.'" value="'.__('Edit Title', 'cf-links').'" onClick="editTitle(\''.$key.'\')" />
													</span>
													<span id="cflk_'.$key.'_title_input" style="display: none">
														<input type="text" name="cflk['.$key.'][title]" value="'.htmlspecialchars($setting['title']).'" style="max-width: 195px;" />
														<input type="button" class="cflk_button" id="link_cancel_title_'.$key.'" value="'.__('Cancel', 'cf-links').'" onClick="cancelTitle(\''.$key.'\')" />
													</span>
													');
												}
												else {
													print('
													<input type="text" size="28" name="cflk['.$key.'][title]" value="'.htmlspecialchars($setting['title']).'" />
													');
												}
											print('
											</td>
											<td width="60px" style="text-align: center;">
												<input type="button" class="cflk_button" id="link_delete_'.$key.'" value="'.__('Delete', 'cf-links').'" onClick="deleteLink(\''.$cflk_key.'\',\''.$key.'\')" />
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
						<input type="hidden" name="cf_action" value="cflk_update_settings" />
						<input type="hidden" name="cflk_key" value="'.attribute_escape($cflk_key).'" />
						<input type="submit" name="submit" id="cflk-submit" value="'.__('Update Settings', 'cf-links').'" />
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

function cflk_head($page = '', $list = '') {
	$cflk_head = '';
	$cflk_head .= '<h2>'.__('Links Options', 'cf-links');
	if($list != '') {
		$cflk_head .= ' '.__('for: ','cf-links').'<span id="cflk_nicename_h3">'.$list.' <a href="#" class="cflk_edit_link" onClick="editNicename()">Edit</a></span>';
		$cflk_head .= '<span id="cflk_nicename_input" style="display: none;">
							<input type="text" name="cflk_nicename" id="cflk_nicename" value="'.attribute_escape($list).'" />
							<input type="submit" name="submit" id="cflk-submit" class="cflk_button" value="'.__('Save', 'cf-links').'" />
							<input type="button" name="link_nicename_cancel" id="link_nicename_cancel" class="cflk_button" value="'.__('Cancel', 'cf-links').'" onClick="cancelNicename()" />					
						</span>';
		$cflk_head .= '</h2>';
		
	}
	else {
		$cflk_head .= '</h2>';
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
	$cflk_head .= '
		<ul class="subsubsub">
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=main" '.$main_text.'>'.__('Links Lists', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cf-links.php&cflk_page=create" '.$add_text.'>'.__('Add Links List', 'cf-links').'</a> | 
			</li>
			<li>
				<a href="'.get_bloginfo('wpurl').'/wp-admin/widgets.php">'.__('Edit Widgets','cf-links').'</a>
			</li>
		</ul>
	';
	return($cflk_head);
}

function cflk_edit_select($type) {
	$select = array();
	switch($type) {
		case 'url':
			$select[url_show] = 'style=""';
			$select[url_select] = 'selected=selected';
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
			break;
		case 'rss':
			$select[url_show] = 'style="display: none;"';
			$select[url_select] = '';
			$select[rss_show] = 'style=""';
			$select[rss_select] = 'selected=selected';
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

function cflk_export_list($cflk_key) {
	return maybe_unserialize(get_option($cflk_key));
}

function cflk_build_select($types, $link, $show_count) {
	$return = '';	
	foreach($types as $type) {
		$selected = '';
		if($link == $type['link']) {
			$selected = ' selected=selected';
		}
		$return .= '<option value="'.$type['link'].'"'.$selected.'>'.$type['description'];
		if(isset($type['count'])) {
			if($show_count) {
				$return .= ' ('.$type['count'].')';
			}
		}
		$return .= '</option>';
	}
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
	<script type='text/javascript' src='<?php print(get_bloginfo('url')); ?>/wp-includes/js/jquery/jquery.js'></script>
	<script type='text/javascript' src='<?php print(get_bloginfo('url')); ?>/wp-includes/js/quicktags.js'></script>
	<script type="text/javascript">
		function cflkSetText(text) {
			var length = jQuery('#cflk_dialog_length').val();
			
			text = '<p>[cflk_links name="' + text + '"';
			if(length > 0) {
				text = text + ' length="' + length + '"';
			}
			text = text + ']</p>';
	
			parent.window.tinyMCE.execCommand("mceBeginUndoLevel");
			parent.window.tinyMCE.execCommand('mceInsertContent', false, '<p>'+text+'</p>');
			parent.window.tinyMCE.execCommand("mceEndUndoLevel");
		}
	</script>
	<p>
		<h3><?php _e('First select the length of the link text.','cf-links')?></h3>
		<label for="cflk_dialog_length"><?php _e('Link Length: ', 'cf-links'); ?></label>
		<select id="cflk_dialog_length" class="widefat">
			<?php
			for($i = 0; $i <= 100; $i++) {
				?>
				<option value="<?php print(htmlspecialchars($i)); ?>"><?php print(htmlspecialchars($i)); ?></option>
				<?php
			}
			?>
		</select>
		<br />
		<small>
			<?php _e('(To not shorten the link text, leave length at 0.)','cf-links')?>
		</small>
	</p>
	<p>
		<h3><?php _e('Then click the list name to insert.','cf-links'); ?></h3>
		<?
		$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
		foreach($cflk_list as $cflk) {
			$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
			?>
			<li>
				<a href="#" onclick="cflkSetText('<?php print(htmlspecialchars($cflk->option_name)); ?>')"><?php print(htmlspecialchars($options['nicename'])); ?></a>
			</li>
			<?php
		}
		?>
	</p>
	<p>
		<h3><?php _e('Finally, click the "X" box at the top right of the window to close.','cf-links'); ?></h3>
	</p>
	<?php
	die();
}

function cflk_register_button($buttons) {
	array_push($buttons, '|', "cfLinksBtn");
	return $buttons;
}

function cflk_add_tinymce_plugin($plugin_array) {
	$plugin_array['cflinks'] = get_bloginfo('wpurl') . '/wp-content/plugins/cf-links/editor_plugin.js';
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

function cflk_process($cflk_data = array(), $cflk_key = '', $cflk_nicename = '') {
	$new_data = array();
	foreach($cflk_data as $key => $info) {
		if($info['type'] == '') {
			unset($cflk_data[$key]);
		} else {
			$check_ok = true;
			$title = stripslashes($info['title']);
			$type = $info['type'];
			$cflk = '';
			$cat_posts = false;
			switch($type) {
				case 'url':
					if($info['link'] != '') { $cflk = stripslashes($info['link']); } else { $check_ok = false; }
					break;
				case 'rss':
					if($info['rss'] != '') { $cflk = stripslashes($info['rss']); } else { $check_ok = false; }
					break;
				case 'post':
					if($info['post'] != '') { $cflk = stripslashes($info['post']); } else { $check_ok = false; }
					break;
				case 'page':
					if($info['page'] != '') { $cflk = stripslashes($info['page']); } else { $check_ok = false; }
					break;
				case 'category':
					if($info['category'] != '') { $cflk = stripslashes($info['category']); } else { $check_ok = false; }
					if($info['category_posts'] != '') { $cat_posts = true; } else { $cat_posts = false; }
					break;
				case 'wordpress':
					if($info['wordpress'] != '') { $cflk = stripslashes($info['wordpress']); } else { $check_ok = false; }
					break;
				case 'author_rss':
					if($info['author_rss'] != '') { $cflk = stripslashes($info['author_rss']); } else { $check_ok = false; }
					break;
				default:
					if($info['link'] != '') { $cflk = stripslashes($info['link']); } else { $check_ok = false; }
					break;
			}
			if($check_ok) {
				$cflk = trim(strip_tags(stripslashes($cflk)));
				array_push($new_data,array('title' => $title, 'type' => $type, 'link' => $cflk, 'cat_posts' => $cat_posts));
			}
		}
	}
	if($cflk_key != '' && $cflk_nicename != '') {
		$settings = array('nicename' => stripslashes($cflk_nicename), 'data' => $new_data);
		update_option($cflk_key, $settings);
	}
}

function cflk_delete_key($cflk_key, $remove_key) {
	$cflk = maybe_unserialize(get_option($cflk_key));
	foreach($cflk['data'] as $key => $value) {
		if($key == $remove_key) {
			unset($cflk['data'][$key]);
		}
	}
	update_option($cflk_key, $cflk);
	return true;
}

function cflk_delete($cflk_key) {
	if($cflk_key != '') {
		delete_option($cflk_key);
	}
	$delete_keys = array();
	$widgets = maybe_unserialize(get_option('cf_links_widget'));
	$sidebars = maybe_unserialize(get_option('sidebars_widgets'));
	foreach($widgets as $key => $widget) {
		if($widget[select] == $cflk_key) {
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

function cflk_insert_new($cflk_nicename) {
	$check_name = cflk_name_check(stripslashes($cflk_nicename));
	$settings = array('nicename' => $check_name[1], 'data' => array('1' => array('title' => 'Name Here', 'link' => 'http://example.com', 'type' => 'url')));
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
	if($cflk_key != '' && $cflk_nicename != '') {
		$cflk = maybe_unserialize(get_option($cflk_key));
		if($cflk[nicename] != $cflk_nicename) {
			$cflk[nicename] = stripslashes($cflk_nicename);
		}
		update_option($cflk_key, $cflk);
	}
}

function cflk_get_list_links() {
	global $wpdb;

	$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	$return = array();
	if(is_array($cflk_list)) {
		foreach($cflk_list as $cflk) {
			$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
			$push = array('option_name' => $cflk->option_name, 'nicename' => $options['nicename'], 'count' => count($options['data']));
			array_push($return,$push);
		}
		
		if(is_array($return)) {
			return $return;
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
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
	$length = $options[$number]['length'];
	
	echo $before_widget;
	if(!empty($title)) {
		echo $before_title . $title . $after_title;
	}
	echo cflk_widget_data($select,$length);
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
			$length = $cfl_links_instance['length'];
			$options[$widget_number] = compact('title','select','length');
		}
		update_option('cf_links_widget', $options);
		$updated = true;
	}
	if ( -1 == $number ) { 
		$title = '';
		$select = '';
		$length = 0;
		$number = '%i%';
	} else {
		$title = attribute_escape($options[$number]['title']);
		$select = $options[$number]['select'];
		$length = $options[$number]['length'];
	}

	$cflk_list = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
	$form_data = array();
	foreach($cflk_list as $cflk) {
		$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
		$push = array('option_name' => $cflk->option_name, 'nicename' => $options['nicename']);
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
		<label for="cfl-links-length-<?php echo $number; ?>"><?php _e('Link Length: ', 'cf-links'); ?></label>
		<br />
		<select id="cfl-links-length-<?php echo $number; ?>" name="cfl-links[<?php echo $number; ?>][length]" class="widefat">
			<?php
			for($i = 0; $i <= 100; $i++) {
				if($i == $length) {
					$selected_length = 'selected=selected';
				}
				else {
					$selected_length = '';
				}
				?>
				<option value="<?php print(htmlspecialchars($i)); ?>" <?php print($selected_length); ?>><?php print(htmlspecialchars($i)); ?></option>
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

function cflk_li_links($cflk_key = '', $args = array()) {
	echo cflk_build_li($cflk_key, $args);
}

function cflk_get_li_links($cflk_key = '', $args = array()) {
	return cflk_build_li($cflk_key, $args);
}

function cflk_ul_links($cflk_key = '', $args = array()) {
	echo cflk_build_ul($cflk_key, $args);
}

function cflk_get_ul_links($cflk_key = '', $args = array()) {
	return cflk_build_ul($cflk_key, $args);
}

function cflk_handle_shortcode($attrs, $content=null) {
	$length = 0;
	if(is_array($attrs) && isset($attrs['name'])) {
		if(isset($attrs['length'])) {
			$length = $attrs['length'];
		}
		return cflk_get_template($attrs['name'], $length);
	}
	return false;
}
// Newest Shortcode
add_shortcode('cflk_links','cflk_handle_shortcode');
// Kept in for legacy purposes
add_shortcode('cfl_links', 'cflk_handle_shortcode');

function cflk_template($cflk_key = '', $length = 0, $before = 'DEFAULT', $after = 'DEFAULT') {
	echo cflk_get_template($cflk_key, $length, $before, $after);
}

function cflk_get_template($cflk_key = '', $length = 0, $before = 'DEFAULT', $after = 'DEFAULT') {
	$return = '';
	if(!isset($cflk_key)) { return false; }
	$args = array(
				'length' => $length,
				'location' => 'template',
				'class' => 'cflk-main '.$cflk_key
			);
	$ul_list = cflk_build_ul($cflk_key, $args);

	$return .= ($before == 'DEFAULT' ? '<div class="'.$cflk_key.'">' : $before);
	$return .= apply_filters('cflk_template_data', $ul_list, $args);
	$return .= ($after == 'DEFAULT' ? '</div>' : $after);
	return $return;
}

function cflk_widget_data($cflk_key = '', $length = 0) {
	$return = '';
	if(!isset($cflk_key)) { return false; }
	$args = array(
				'length' => $length,
				'location' => 'widget',
				'class' => 'cflk-main-widget '.$cflk_key
			);
	$ul_list = cflk_build_ul($cflk_key, $args);
	return apply_filters('cflk_widget_data', $ul_list, $args);
}

function cflk_get_links($key = null, $args = array()) {
	if (!$cflk_key) { return ''; }
	$defaults = array(
		'before' => '<ul class="cflk-list '.$key.'">',
		'after' => '</ul>'
	);
	$args = array_merge($defaults, $args);
	extract($args, EXTR_SKIP);

	$list = maybe_unserialize(get_option($key));
	$list = apply_filters('cflk_get_links_data', $links);

// call function that preps list data

	$list = TODO_new_function($list);

	if (!is_array($list)) { return ''; }

	$return = '';

	$i = 0;
	foreach ($list as $key => $data) {
		$li_class = '';
//		$link = cflk_get_by_type($data, $cflk_key, $key, $length, $location);
		if (is_array($link)) {
			if ($i == 0) {
				$li_class .= 'cflk-first ';
			}
			if ($i == (count($list) - 1)) {
				$li_class .= 'cflk-last ';
			}
			if ($link['current']) {
				$li_class .= 'cflk-current ';
			}
			$return .= '<li class="'.$li_class.'">'.$link['link'].'</li>';
		}
		$i++;
	}
	
	$return = $before.$return.$after;

	$return = apply_filters('cflk_get_links', $return, $links, $args);
	return $return;
}
function cflk_links($key, $args = array()) {
	echo cflk_get_links($key, $args);
}

function cflk_build_ul($cflk_key = NULL, $args = array()) {
	$defaults = array(
					'class' => 'cflk-list',
					'before' => '',
					'after' => ''
				);
	$args = array_merge($defaults, $args);
	extract($args, EXTR_SKIP);
	$return = '';

	if(!$cflk_key) { return false; }
	$return .= '<ul class="'.$class.'">';
	$return .= cflk_build_li($cflk_key, $args);
	$return .= '</ul>';
	
	return $return;
}

function cflk_build_li($cflk_key = NULL, $args = array()) {
	$defaults = array(
					'length' => 0,
					'location' => 'template',
				);
	$args = array_merge($defaults, $args);
	extract($args, EXTR_SKIP);
	$cflk_list = maybe_unserialize(get_option($cflk_key));
	if(!is_array($cflk_list)) { return false; }
	$return = '';

	if(!$cflk_key || !is_array($cflk_list)) { return false; }

	foreach($cflk_list['data'] as $key => $data) {
		$li_class = '';
		$link = cflk_get_by_type($data, $cflk_key, $key, $length, $location);
		if(is_array($link)) {
			if($key == 0) {
				$li_class .= 'cflk-first-li ';
			}
			if($key == (count($cflk_list['data']) - 1)) {
				$li_class .= 'cflk-last-li ';
			}
			if($link['current']) {
				$li_class .= 'cflk-current-li ';
			}
			$return .= '<li class="'.$li_class.'">'.$link['link'].'</li>';
		}
	}
	return $return;
}

function cflk_get_by_type($link = array(),$link_key,$key,$sub_length=0,$location='template') {
	$return = '';
	$link_info = '';
	$current_css = '';
	$current = false;
	$link_info = cflk_get_link_info($link);
	if(is_array($link_info)) {
		$server_current = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$href_current = str_replace(array('http://','http://www.'),'',$link_info['href']);
		if($server_current == $href_current) {
			$current = true;
			$current_css = 'cflk-current-href';
		}
		if($_SERVER['HTTP_HOST'].$_SERV)
		if($link_info['other'] == 'rss') {
			$return .= '<a href="'.$link_info['href'].'" class="rss-href-'.$location.'-'.$link_key.'-'.$key.' rss-href-'.$location.'-'.$link_key.' rss-href-'.$location.' '.$current_css.'"><img src="'.get_bloginfo('url').'/wp-content/plugins/cf-links/images/feed-icon-16x16.png" class="rss-image-'.$location.'-'.$link_key.'-'.$key.' rss-image-'.$location.'-'.$link_key.' rss-image-'.$location.'" /></a> ';
		}
		$return .= '<a href="'.$link_info['href'].'" class="href-'.$location.'-'.$link_key.'-'.$key.' href-'.$location.'-'.$link_key.' href-'.$location.' '.$current_css.'" title="'.$link_info['text'].'">';
		if($sub_length > 0) {
			if(strlen($link_info['text']) > $sub_length) {
				$return .= substr($link_info['text'],0,$sub_length).'&hellip;';
			}
			else {
				$return .= $link_info['text'];
			}
		}
		else {
			$return .= $link_info['text'];
		}
		$return .= '</a>';
		$return_array = array('link' => $return, 'current' => $current);
		return $return_array;
	}
	return false;
}

function cflk_get_link_info($link) {
	$href = '';
	$text = '';
	$type_text = '';
	$other = '';
	
	switch($link['type']) {
		case 'url':
			$href = htmlspecialchars($link['link']);
			$type_text = htmlspecialchars($link['link']);
			break;
		case 'rss':
			$href = htmlspecialchars($link['link']);
			$type_text = htmlspecialchars($link['link']);
			$other = 'rss';
			break;
		case 'post':
		case 'page':
			$href = get_permalink(htmlspecialchars($link['link']));
			$postinfo = get_post(htmlspecialchars($link['link']));
			$type_text = $postinfo->post_title;
			break;
		case 'category':
			$cat_info = get_category(htmlspecialchars($link['link']),OBJECT,'display');
			$href = get_category_link($cat_info->term_id);
			$type_text = attribute_escape($link_cat_info->cat_name);
			if($link['cat_posts']) {
				$type_text .= ' ('.$link_cat_info->count.')';
			}
			break;
		case 'wordpress':
			$get_link = cflk_get_wp_type($link['link']);
			if(is_array($get_link)) {
				$href = $get_link['link'];
				$type_text = $get_link['text'];
				if($link['link'] == 'main_rss') {
					$other = 'rss';
				}
			}
			break;
		case 'author_rss':
			$href = get_author_rss_link(false,$link['link']);
			$userdata = get_userdata($link['link']);
			$type_text = $userdata->display_name;
			$other = 'rss';
			break;
		default:
			break;
	}
	
	if(empty($link['title'])) {
		$text = $type_text;
	}
	else {
		$text = htmlspecialchars($link['title']);
	}
	
	if(!empty($href) && !empty($text)) {
		return array('href' => $href, 'text' => $text, 'other' => $other);
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
			if(!is_user_logged_in()) {
				$link = wp_login_url();
				$text = 'Log in';
			}
			else {
				$link = wp_logout_url();
				$text = 'Log Out';
			}
			break;
		case 'register':
			if(!is_user_logged_in()) {
				if(get_option('users_can_register')) {
					$link = site_url('wp-login.php?action=register','login');
					$text = 'Register';
				}
			}
			else {
				if(current_user_can('manage_options')) {
					$link = admin_url();
					$text = 'Site Admin';
				}
			}
			break;
		case 'profile':
			if(is_user_logged_in()) {
				$link = admin_url('profile.php');
				$text = 'Profile';
			}
			break;
		case 'main_rss':
			$text = get_bloginfo('name');
			$link = get_bloginfo('rss2_url');
			break;
	}
	if($link != '' && $text != '') {
		return array('text' => $text, 'link' => $link);
	}
	return false;
}


// DO NOT ACTIVATE THIS UNTIL A NEW SOLUTION IS IN PLACE.  TOO MANY POSTS KILLS THE PAGE.
// $posts = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_title ASC");
// $post_select = '';
// foreach($posts as $post) {
// 	$post_select .= '<option value="'.$post->ID.'">'.addslashes($post->post_title).'</option>';
// }
// if(type == "post") {
// 	jQuery("#url_"+key).attr("style", "display: none;");
// 	jQuery("#rss_"+key).attr("style", "display: none;");
// 	jQuery("#post_"+key).attr("style", "");
// 	jQuery("#page_"+key).attr("style", "display: none;");													
// 	jQuery("#category_"+key).attr("style", "display: none;");
// 	jQuery("#wordpress_"+key).attr("style", "display: none;");
// 	jQuery("#author_rss_"+key).attr("style", "display: none;");
// }
// <option value="post">Post</option>\
// <span id="post_###SECTION###" style="display: none;">\
	// <select name="links[###SECTION###][post]" id="links_###SECTION###_post" style="width: 410px;">\
		// <?php echo $post_select; \
// 	</select>\
// </span>\
// DO NOT ACTIVATE THIS UNTIL A NEW SOLUTION IS IN PLACE.  TOO MANY POSTS KILLS THE PAGE.



// DO NOT ACTIVATE THIS UNTIL A NEW SOLUTION IS IN PLACE.  TOO MANY POSTS KILLS THE PAGE.
// case 'post':
// 	$select[url_show] = 'style="display: none;"';
// 	$select[url_select] = '';
// 	$select[rss_show] = 'style="display: none;"';
// 	$select[rss_select] = '';
// 	$select[post_show] = 'style=""';
// 	$select[post_select] = 'selected=selected';
// 	$select[page_show] = 'style="display: none;"';
// 	$select[page_select] = '';
// 	$select[category_show] = 'style="display: none;"';
// 	$select[category_select] = '';
// 	$select[wordpress_show] = 'style="display: none;"';
// 	$select[wordpress_select] = '';
// 	$select[author_rss_show] = 'style="display: none;"';
// 	$select[author_rss_select] = '';
// 	break;
// DO NOT ACTIVATE THIS UNTIL A NEW SOLUTION IS IN PLACE.  TOO MANY POSTS KILLS THE PAGE.


// DO NOT ACTIVATE THIS UNTIL A NEW SOLUTION IS IN PLACE.  TOO MANY POSTS KILLS THE PAGE.
// $posts = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_title ASC");
// <option value="post" '.$select_settings[post_select].'>'.__('Post','cf-links').'</option>
// <span id="post_'.$key.'" '.$select_settings[post_show].'>
// 	<select name="links['.$key.'][post]" id="links_'.$key.'_post" style="width: 410px;">');
// 	foreach($posts as $post) {				
// 		if($setting['link'] == $post->ID) {
// 			$selected = 'selected=selected';
// 		} else {
// 			$selected = '';
// 		}
// 		print('<option value="'.$post->ID.'" '.$selected.'>'.$post->post_title.'</option>');
// 	}
// 	print('
// 	</select>
// </span>
// DO NOT ACTIVATE THIS UNTIL A NEW SOLUTION IS IN PLACE.  TOO MANY POSTS KILLS THE PAGE.


// function cflk_filter_content($content) {
// 	global $wpdb;
// 	$cflk_list = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'cfl-%'");
// 	if(is_array($cflk_list)) {
// 		foreach($cflk_list as $cflk) {
// 			$check = strpos($content, '[cfl_links name="'.$cflk->option_name.'"]');
// 			if($check !== false) {
// 				$content = str_replace('[cfl_links name="'.$cflk->option_name.'"]', cflk_get_template($cflk->option_name), $content);
// 			}
// 		}
// 	}
// 	return $content;
// }
// add_filter('the_content', 'cflk_filter_content');
// function cflk_get_template($link_key = '',$length = 0) {
// 	if($link_key != '') {
// 		$cflk = maybe_unserialize(get_option($link_key));
// 		if(is_array($cflk)) {
// 			$cflk_get = '';
// 			$cflk_get .= '<div class="'.$link_key.'_get"><ul class="cflk_main_get">';
// 			foreach($cflk['data'] as $key => $link) {
// 				$cflk_get .= cflk_get_by_type($link,$link_key,$key,$length,'template');
// 			}
// 			$cflk_get .= '</ul></div>';
// 			return $cflk_get;
// 		}
// 	}
// }
// function cflk_template($link_key = '', $length = 0, $before = 'DEFAULT', $after = 'DEFAULT') {
// 	if($link_key != '') {
// 		$cflk = maybe_unserialize(get_option($link_key));
// 		if(is_array($cflk)) {
// 			echo ($before == 'DEFAULT' ? '<div class="'.$link_key.'">' : $before);
// 
// 			$args = array('link_key' => $link_key, 
// 						  'length' => $length, 
// 						  'type' => 'template', 
// 						  'class' => 'cflk-main');
// 			$html = cflk_build_ul($cflk,$args);
// 			echo apply_filters('cflk_template_data',$html,$cflk,$args);
// 			
// 			echo ($after == 'DEFAULT' ? '</div>' : $after);
// 		}
// 	}
// }
// function cflk_widget_data($link_key = '',$length = 0) {
// 	$html = '';
// 	if($link_key != '') {
// 		$cflk = maybe_unserialize(get_option($link_key));
// 		if(is_array($cflk)) {
// 			$args = array('link_key' => $link_key, 
// 						  'length' => $length,
// 						  'type' => 'widget',
// 						  'class' => 'cflk-main-widget');
// 			$html = cflk_build_ul($cflk,$args);
// 		}
// 	}
// 	return apply_filters('cflk_widget_data',$html,$cflk,$args);
// }
// function cflk_build_ul($cflk, $args = array()) {
// 	$defaults = array(
// 			'link_key' => null,
// 			'type' => 'widget',
// 			'length' => 0,
// 			'class' => 'cflk-list'
// 		);
// 	$args = array_merge($defaults,$args);
// 	extract( $args, EXTR_SKIP );
// 	$html = '<ul class="'.$class.'">';
// 	foreach($cflk['data'] as $key => $link) {
// 		$link = cflk_get_by_type($link,$link_key,$key,$length,$type);
// 		if($link) {
// 			$html .= '<li>'.$link.'</li>';
// 		}
// 	}
// 	$html .= '</ul>';
// 	return $html;
// }



?>