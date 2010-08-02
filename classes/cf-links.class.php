<?php

class cflk_links {
	protected $link_types;
	protected $lists;
		
	function __construct() {
		// enqueue_scripts
		// enqueue_styles
		
		// add_actions
		add_action('init', array($this, 'import_included_link_types'), 11);
	}
	
	/**
	 * Check to see that a link type is a valid
	 *
	 * @param string $type 
	 * @return void
	 */
	function is_valid_link_type($type) {
		return isset($this->link_types[$type]) && ($this->link_types[$type] instanceof cflk_link_base);
	}
	
	/**
	 * Retrieve a specific link type object
	 *
	 * @param string $type 
	 * @return mixed object/bool
	 */
	function get_link_type($type) {
		if ($this->is_valid_link_type($type)) {
			return $this->link_types[$type];
		}
		else {
			return false;
		}
	}
	
	/**
	 * Display a specific list
	 *
	 * @param string $list_id 
	 * @param array $args
	 * @return string html
	 */
	function get_list($list_id, $args) {
		if (!($list = $this->get_list_data($list_id))) {
			return false;
		}

		if (!isset($args['context'])) {
			$args['context'] == 'default';
		}

		$list = new cflk_list($list, $args, &$this->link_types);
		return apply_filters('cflk_get_list', $list);
	}

	/**
	 * Pull list from data base and store its raw form
	 *
	 * @param string $list_id 
	 * @return bool
	 */
	function get_list_data($list_id) {
		if (isset($this->lists[$list_id])) {
			return $this->lists[$list_id];
		}
		
		$list = maybe_unserialize(get_option($list_id));

		if (!is_array($list)) {
			$list = false;
		}
		else {
			$this->lists[$list_id] = $list;
		}
		
		return apply_filters('cflk_get_links_data', $list, $list_id);
	}
		
	/**
	 * Find all files to be imported.
	 * Logs all link type files to be imported to an array, caches results and returns
	 *
	 * Pretty much verbatim (with name changes) from Carrington Build
	 *
	 * @return array
	 */
	function find_included_link_types() {
		if ($modules = wp_cache_get('cflk_included_modules', 'cfct_build')) {
			return $modules;
		}

		$paths = apply_filters('cflk-link-dirs', array(trailingslashit(CFLK_PLUGIN_DIR).'link-types'));
		$types = array();
		foreach ($paths as $path) {
			if (is_dir($path)) {
				$types = array_merge($types, glob(trailingslashit($path).'*.php'));
			}
		}

		wp_cache_set('cflk_included_modules', $types, 'cflk_links', 3600);
		return $types;			
	}
	
	/**
	 * Include the link-type files
	 * Each link type is responsible for registering itself inside the included file
	 *
	 * @return bool
	 */
	function import_included_link_types() {
		$link_type_files = $this->find_included_link_types();
		foreach ($link_type_files as $type) {
			include($type);
		}
		return true;			
	}
	
	/**
	 * Register a link type
	 * Anyone can register a link type with this function
	 * In the event of duplicate IDs, the last man in wins.
	 *
	 * @package default
	 */
	function register_link_type($id, $classname) {
		if (class_exists($classname) && $this->link_types[$id] = new $classname()) {
			return true;
		}
		return false;
	}
	
	function footer_js() {
		echo '
<script type="text/javascript">
jQuery(function($) {
	$(".cflk-opennewwindow a").click(function(){
		window.open(this.href);
		return false;
	});
});
</script>
		';
	}
	
	function js() {} // not needed?
	function css() {} // not needed?
}

?>